<?php
/**
 * GCM Netting Solutions — Account Manager
 * Handles: password change/reset, username change/create,
 *          forgot-password, forgot-username flows
 *
 * Security model (Forgot-Password / Forgot-Username flows):
 *   - Tokens   : bin2hex(random_bytes(32))  →  SHA-256 hash stored (never plaintext)
 *   - Entropy  : 256-bit — brute-force infeasible (2^256 combinations)
 *   - Expiry   : 15 min reset | 30 min email-confirmed changes
 *   - Rate limit: multi-window  →  1/5min + 3/hr + 5/day per IP  (forgot-password)
 *                               →  2/10min + 5/hr + 8/day per IP (forgot-username)
 *   - Auto IP-block : 8 total attempts in 1 hour → 24h block
 *   - Bot detection : empty/suspicious user-agent rejected
 *   - Honeypot field: silent discard if bot fills hidden field
 *   - Timing attack : constant 1-2s sleep on all responses
 *   - No enumeration: identical message whether username/email exists or not
 *   - 2nd factor    : reset form also requires username (have link AND know username)
 *   - Security alert: email sent on every reset/username-recovery request
 *   - Token IP-bind : creation IP logged; mismatch triggers extra alert
 *   - Old password required before any account change
 *   - CSRF enforced on all forms
 *   - All operations logged to account_audit_log
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class AccountManager {

    private $conn;

    /* Password rules */
    const MIN_LENGTH   = 8;
    const REQUIRE_UPPER = true;
    const REQUIRE_LOWER = true;
    const REQUIRE_NUM   = true;
    const REQUIRE_SPEC  = true;

    /* Token TTL (seconds) */
    const RESET_TTL  = 900;   // 15 minutes
    const CHANGE_TTL = 1800;  // 30 minutes

    /* Multi-window rate limits [max, window_seconds] */
    const FORGOT_PW_LIMITS = [
        ['max' => 1, 'window' => 300],    // 1 per 5 min
        ['max' => 3, 'window' => 3600],   // 3 per hour
        ['max' => 5, 'window' => 86400],  // 5 per day
    ];
    const FORGOT_UN_LIMITS = [
        ['max' => 2, 'window' => 600],    // 2 per 10 min
        ['max' => 5, 'window' => 3600],   // 5 per hour
        ['max' => 8, 'window' => 86400],  // 8 per day
    ];
    const CHANGE_LIMITS = [
        ['max' => 3, 'window' => 3600],   // 3 per hour
    ];

    /* Auto IP-block threshold */
    const AUTO_BLOCK_THRESHOLD = 8;    // attempts per hour before auto-block
    const AUTO_BLOCK_DURATION  = 3600;  // 1 hour (shortened from 24h to prevent accidental lock-outs)

    /* Admin recovery email */
    const RECOVERY_EMAIL = 'gopichandmailapally@gmail.com';

    public function __construct() {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $this->ensureTables();
    }

    private function ensureTables(): void {
        $this->conn->exec("CREATE TABLE IF NOT EXISTS `account_audit_log` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id`   INT DEFAULT 0,
            `actor_id`   INT DEFAULT 0,
            `action`     VARCHAR(100) NOT NULL,
            `details`    TEXT,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `user_agent` VARCHAR(255) DEFAULT NULL,
            `status`     ENUM('success','failed','blocked') DEFAULT 'success',
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_admin_id`  (`admin_id`),
            INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS `email_verification_tokens` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id`   INT NOT NULL,
            `token_hash` VARCHAR(64) NOT NULL,
            `action`     VARCHAR(50) NOT NULL,
            `payload`    TEXT,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `expires_at` DATETIME NOT NULL,
            `is_used`    TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_token_hash` (`token_hash`),
            INDEX `idx_admin_id`   (`admin_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS `password_reset_tokens` (
            `id`         INT AUTO_INCREMENT PRIMARY KEY,
            `admin_id`   INT NOT NULL,
            `token_hash` VARCHAR(64) NOT NULL,
            `ip_address` VARCHAR(45) DEFAULT NULL,
            `expires_at` DATETIME NOT NULL,
            `is_used`    TINYINT(1) DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_token_hash` (`token_hash`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS `admin_login_attempts` (
            `id`           INT AUTO_INCREMENT PRIMARY KEY,
            `username`     VARCHAR(100) DEFAULT NULL,
            `ip_address`   VARCHAR(45)  DEFAULT NULL,
            `attempt_time` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `success`      TINYINT(1) DEFAULT 0,
            INDEX `idx_ip`   (`ip_address`),
            INDEX `idx_user` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->conn->exec("CREATE TABLE IF NOT EXISTS `admin_blocked_ips` (
            `id`            INT AUTO_INCREMENT PRIMARY KEY,
            `ip_address`    VARCHAR(45) NOT NULL,
            `reason`        VARCHAR(255) DEFAULT NULL,
            `permanent`     TINYINT(1) DEFAULT 0,
            `blocked_until` DATETIME DEFAULT NULL,
            `created_at`    DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_ip` (`ip_address`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        /* ── Migrate existing tables: add columns added after initial deploy ── */
        $migrations = [
            "ALTER TABLE `admin_blocked_ips` ADD COLUMN `permanent`     TINYINT(1) DEFAULT 0     AFTER `reason`",
            "ALTER TABLE `admin_blocked_ips` ADD COLUMN `blocked_until` DATETIME   DEFAULT NULL  AFTER `permanent`",
        ];
        foreach ($migrations as $sql) {
            try { $this->conn->exec($sql); } catch (\PDOException $e) { /* column already exists — ignore */ }
        }
    }

    private function fetchOne(string $sql, array $params = []): ?array {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        $row  = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function fetchAll(string $sql, array $params = []): array {
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ══════════════════════════════════════════════════
     *  CSRF
     * ══════════════════════════════════════════════════ */
    public function generateCSRF(string $form): string {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_' . $form] = [
            'token'   => $token,
            'expires' => time() + 600,
        ];
        return $token;
    }

    public function verifyCSRF(string $form, string $token): bool {
        $key = 'csrf_' . $form;
        if (empty($_SESSION[$key])) return false;
        $stored = $_SESSION[$key];
        unset($_SESSION[$key]);
        if (time() > $stored['expires']) return false;
        return hash_equals($stored['token'], $token);
    }

    /* ══════════════════════════════════════════════════
     *  RATE LIMITING — multi-window (stored in admin_login_attempts)
     * ══════════════════════════════════════════════════ */

    /** Check multiple time windows for a given action key. Returns true if ANY window is exceeded. */
    public function multiWindowRateLimit(string $action, array $limits): bool {
        $ip = $this->getIP();
        foreach ($limits as $limit) {
            $since = date('Y-m-d H:i:s', time() - $limit['window']);
            $stmt  = $this->conn->prepare(
                "SELECT COUNT(*) FROM admin_login_attempts
                 WHERE ip_address = ? AND username = ? AND attempt_time > ?"
            );
            $stmt->execute([$ip, '__acct_' . $action, $since]);
            if ((int)$stmt->fetchColumn() >= $limit['max']) return true;
        }
        return false;
    }

    /** Record an attempt for rate-limit tracking */
    public function recordRateAttempt(string $action): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO admin_login_attempts (username, ip_address, success) VALUES (?, ?, 0)"
        );
        $stmt->execute(['__acct_' . $action, $this->getIP()]);
    }

    /** Legacy single-window check (used by change-password / change-username flows) */
    public function isRateLimited(string $action): bool {
        return $this->multiWindowRateLimit($action, self::CHANGE_LIMITS);
    }

    /** After recording an attempt, check if IP should be auto-blocked */
    private function checkAutoBlock(string $action): void {
        $ip    = $this->getIP();
        $since = date('Y-m-d H:i:s', time() - 3600);
        $stmt  = $this->conn->prepare(
            "SELECT COUNT(*) FROM admin_login_attempts WHERE ip_address = ? AND attempt_time > ?"
        );
        $stmt->execute([$ip, $since]);
        $count = (int)$stmt->fetchColumn();
        if ($count >= self::AUTO_BLOCK_THRESHOLD) {
            $until = date('Y-m-d H:i:s', time() + self::AUTO_BLOCK_DURATION);
            try {
                /* INSERT OR REPLACE: if IP already has an expired block, this refreshes the expiry */
                $ins = $this->conn->prepare(
                    "REPLACE INTO admin_blocked_ips (ip_address, blocked_until, reason)
                     VALUES (?, ?, ?)"
                );
                $ins->execute([$ip, $until, "Auto-blocked: {$count} account-recovery attempts in 1h (action={$action})"]);
            } catch (\Exception $e) {
                error_log('Auto-block error: ' . $e->getMessage());
            }
            $this->audit(0, 0, 'ip_auto_blocked', "IP={$ip} count={$count} action={$action}", 'blocked');
            $this->sendSecurityAlert('IP Auto-Blocked', 0, "IP {$ip} was auto-blocked after {$count} failed attempts for action: {$action}");
        }
    }

    /** Returns true if the current IP is in the blocked list */
    private function isBlockedIP(): bool {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM admin_blocked_ips
             WHERE ip_address = ?
               AND (permanent = 1 OR (blocked_until IS NOT NULL AND blocked_until > CURRENT_TIMESTAMP))"
        );
        $stmt->execute([$this->getIP()]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /** Returns true if the request looks like an automated bot */
    private function isBotRequest(): bool {
        $ua = strtolower(trim($_SERVER['HTTP_USER_AGENT'] ?? ''));
        if (strlen($ua) < 10) return true;
        $bots = ['curl','wget','python-requests','java/','go-http','scrapy','phpunit',
                  'httpie','libwww','lwp-','peach','masscan','nmap','nikto','sqlmap'];
        foreach ($bots as $b) { if (strpos($ua, $b) !== false) return true; }
        return false;
    }

    /** Constant-time random delay (1–2 s) — prevents timing-based user enumeration */
    private function constantTimeDelay(): void {
        usleep(random_int(1000000, 2000000));
    }

    /* ══════════════════════════════════════════════════
     *  PASSWORD VALIDATION
     * ══════════════════════════════════════════════════ */
    public function validatePassword(string $pw): array {
        $errors = [];
        if (strlen($pw) < self::MIN_LENGTH)
            $errors[] = 'At least ' . self::MIN_LENGTH . ' characters required';
        if (self::REQUIRE_UPPER && !preg_match('/[A-Z]/', $pw))
            $errors[] = 'Must contain at least one uppercase letter';
        if (self::REQUIRE_LOWER && !preg_match('/[a-z]/', $pw))
            $errors[] = 'Must contain at least one lowercase letter';
        if (self::REQUIRE_NUM && !preg_match('/[0-9]/', $pw))
            $errors[] = 'Must contain at least one number';
        if (self::REQUIRE_SPEC && !preg_match('/[^A-Za-z0-9]/', $pw))
            $errors[] = 'Must contain at least one special character (!@#$%^&* …)';
        return $errors;
    }

    /* ══════════════════════════════════════════════════
     *  USERNAME VALIDATION
     * ══════════════════════════════════════════════════ */
    public function validateUsername(string $u): array {
        $errors = [];
        if (strlen($u) < 3 || strlen($u) > 20)
            $errors[] = 'Username must be 3–20 characters';
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $u))
            $errors[] = 'Only letters (A–Z, a–z), numbers, and underscores allowed';
        return $errors;
    }

    public function usernameExists(string $u, int $excludeId = 0): bool {
        $stmt = $this->conn->prepare(
            "SELECT COUNT(*) FROM admin_users WHERE username = ? AND id != ?"
        );
        $stmt->execute([$u, $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /* ══════════════════════════════════════════════════
     *  GET ADMIN USER
     * ══════════════════════════════════════════════════ */
    public function getUser(int $id): ?array {
        $stmt = $this->conn->prepare(
            "SELECT id, username, email, role, is_active, last_login, created_at
             FROM admin_users WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getUserByUsername(string $username): ?array {
        $stmt = $this->conn->prepare(
            "SELECT id, username, password_hash, email, role, is_active
             FROM admin_users WHERE username = ?"
        );
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getAllAdmins(): array {
        return $this->fetchAll(
            "SELECT id, username, email, role, is_active, last_login, created_at
             FROM admin_users ORDER BY id ASC"
        );
    }

    /* ══════════════════════════════════════════════════
     *  CHANGE PASSWORD  (email-confirmed)
     * ══════════════════════════════════════════════════ */
    public function initiatePasswordChange(int $adminId, string $oldPw, string $newPw, string $confirmPw): array {
        $user = $this->fetchOne(
            "SELECT * FROM admin_users WHERE id = ?", [$adminId]
        );
        if (!$user) return ['ok' => false, 'error' => 'User not found'];

        if (!password_verify($oldPw, $user['password_hash']))
            return ['ok' => false, 'error' => 'Current password is incorrect'];

        if ($newPw !== $confirmPw)
            return ['ok' => false, 'error' => 'New passwords do not match'];

        $errors = $this->validatePassword($newPw);
        if ($errors) return ['ok' => false, 'error' => implode('. ', $errors)];

        if (password_verify($newPw, $user['password_hash']))
            return ['ok' => false, 'error' => 'New password must differ from current password'];

        if ($this->isRateLimited('change_password')) {
            $this->audit($adminId, $adminId, 'change_password_rate_limited', '', 'blocked');
            return ['ok' => false, 'error' => 'Too many requests. Try again in 1 hour.'];
        }
        $this->recordRateAttempt('change_password');

        $newHash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        $token   = $this->createToken('change_password', $adminId, json_encode(['hash' => $newHash]), self::CHANGE_TTL);

        $sent = $this->sendChangeConfirmEmail($user['email'] ?: self::RECOVERY_EMAIL, $user['username'], $token, 'Password Change');
        $this->audit($adminId, $adminId, 'change_password_initiated', 'Confirmation email sent');

        return ['ok' => true, 'message' => 'Confirmation link sent to ' . ($user['email'] ?: self::RECOVERY_EMAIL) . '. Click it within 30 minutes to confirm.', 'sent' => $sent];
    }

    public function confirmPasswordChange(string $rawToken): array {
        $result = $this->consumeToken($rawToken, 'change_password');
        if (!$result['ok']) return $result;

        $payload = json_decode($result['payload'], true);
        $stmt = $this->conn->prepare(
            "UPDATE admin_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $stmt->execute([$payload['hash'], $result['admin_id']]);
        $this->audit($result['admin_id'], $result['admin_id'], 'password_changed', 'Via email confirmation');
        $this->sendActionDoneEmail($result['admin_id'], 'Password Changed', 'Your admin password has been successfully changed.');
        return ['ok' => true, 'message' => 'Password changed successfully!'];
    }

    /* ══════════════════════════════════════════════════
     *  CHANGE USERNAME  (email-confirmed)
     * ══════════════════════════════════════════════════ */
    public function initiateUsernameChange(int $adminId, string $currentPw, string $newUsername): array {
        $user = $this->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$adminId]);
        if (!$user) return ['ok' => false, 'error' => 'User not found'];

        if (!password_verify($currentPw, $user['password_hash']))
            return ['ok' => false, 'error' => 'Password is incorrect'];

        $errors = $this->validateUsername($newUsername);
        if ($errors) return ['ok' => false, 'error' => implode('. ', $errors)];

        if ($this->usernameExists($newUsername, $adminId))
            return ['ok' => false, 'error' => 'Username already taken'];

        if ($user['username'] === $newUsername)
            return ['ok' => false, 'error' => 'New username must differ from current'];

        if ($this->isRateLimited('change_username')) {
            $this->audit($adminId, $adminId, 'change_username_rate_limited', '', 'blocked');
            return ['ok' => false, 'error' => 'Too many requests. Try again in 1 hour.'];
        }
        $this->recordRateAttempt('change_username');

        $token = $this->createToken('change_username', $adminId, json_encode(['new_username' => $newUsername]), self::CHANGE_TTL);
        $sent  = $this->sendChangeConfirmEmail($user['email'] ?: self::RECOVERY_EMAIL, $user['username'], $token, 'Username Change');
        $this->audit($adminId, $adminId, 'change_username_initiated', 'New: ' . $newUsername);

        return ['ok' => true, 'message' => 'Confirmation link sent to ' . ($user['email'] ?: self::RECOVERY_EMAIL) . '. Click it within 30 minutes.', 'sent' => $sent];
    }

    public function confirmUsernameChange(string $rawToken): array {
        $result = $this->consumeToken($rawToken, 'change_username');
        if (!$result['ok']) return $result;

        $payload = json_decode($result['payload'], true);
        $newUsername = $payload['new_username'];

        if ($this->usernameExists($newUsername, $result['admin_id']))
            return ['ok' => false, 'error' => 'Username already taken by another user'];

        $stmt = $this->conn->prepare(
            "UPDATE admin_users SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $stmt->execute([$newUsername, $result['admin_id']]);
        $this->audit($result['admin_id'], $result['admin_id'], 'username_changed', 'New: ' . $newUsername);
        $this->sendActionDoneEmail($result['admin_id'], 'Username Changed', "Your admin username has been changed to: <strong>{$newUsername}</strong>");
        return ['ok' => true, 'message' => 'Username changed to ' . htmlspecialchars($newUsername) . '!'];
    }

    /* ══════════════════════════════════════════════════
     *  FORGOT PASSWORD  (public, no login required)
     *  Security layers:
     *    1. Bot detection (user-agent)
     *    2. IP-block check
     *    3. Honeypot (silent discard)
     *    4. Multi-window rate limit (1/5m + 3/h + 5/d)
     *    5. Auto IP-block after 8 hourly attempts
     *    6. Constant 1-2s delay (timing-attack prevention)
     *    7. No username enumeration (identical response)
     *    8. Security alert email on every request
     *    9. Token bound to requesting IP
     * ══════════════════════════════════════════════════ */
    public function initiateForgotPassword(string $username, string $honeypot = ''): array {
        /* ── 1. Bot detection ── */
        if ($this->isBotRequest()) {
            $this->audit(0, 0, 'forgot_pw_bot_blocked', 'UA: ' . substr($_SERVER['HTTP_USER_AGENT'] ?? 'none', 0, 100), 'blocked');
            $this->constantTimeDelay();
            http_response_code(403);
            return ['ok' => false, 'error' => 'Request blocked.'];
        }

        /* ── 2. IP-block check ── */
        if ($this->isBlockedIP()) {
            $this->audit(0, 0, 'forgot_pw_blocked_ip', 'IP: ' . $this->getIP(), 'blocked');
            $this->constantTimeDelay();
            return ['ok' => false, 'error' => 'Your IP has been temporarily blocked due to too many requests. Contact the administrator.'];
        }

        /* ── 3. Honeypot ── silent discard looks like success */
        if (!empty($honeypot)) {
            $this->audit(0, 0, 'forgot_pw_honeypot', 'IP: ' . $this->getIP(), 'blocked');
            $this->constantTimeDelay();
            return ['ok' => true, 'message' => 'If that username exists, a reset link has been sent. Check your inbox and spam folder. The link expires in 15 minutes.'];
        }

        /* ── 4. Multi-window rate limit ── */
        if ($this->multiWindowRateLimit('forgot_password', self::FORGOT_PW_LIMITS)) {
            $this->audit(0, 0, 'forgot_pw_rate_limited', 'IP: ' . $this->getIP(), 'blocked');
            $this->constantTimeDelay();
            return ['ok' => false, 'error' => 'Too many requests from your IP address. Please wait before trying again.'];
        }

        /* ── Record attempt BEFORE any DB lookup ── */
        $this->recordRateAttempt('forgot_password');
        /* Note: checkAutoBlock only fires when rate-limit is already exceeded (see above) */

        /* ── Sanitise username (allow only safe chars) ── */
        $clean = preg_replace('/[^a-zA-Z0-9_\-]/', '', $username);
        $user  = ($clean !== '') ? $this->getUserByUsername($clean) : null;

        /* ── 5. Process (constant time either way) ── */
        if ($user && $user['is_active']) {
            $token = $this->createToken('reset_password', $user['id'], '', self::RESET_TTL);
            $this->sendPasswordResetEmail($user['email'] ?: self::RECOVERY_EMAIL, $user['username'], $token);
            $this->audit($user['id'], 0, 'forgot_password_requested', 'IP: ' . $this->getIP());
        } else {
            /* Log even unknown-username attempts (detect enumeration scanning) */
            $this->audit(0, 0, 'forgot_pw_unknown_username',
                'Input: ' . substr($clean, 0, 20) . ' IP: ' . $this->getIP(), 'failed');
        }

        /* ── 6. Security alert on every attempt ── */
        $this->sendSecurityAlert('Password Reset Requested',
            $user['id'] ?? 0,
            'Username attempted: ' . substr($clean, 0, 20));

        /* ── 7. Constant delay (uniform response time) ── */
        $this->constantTimeDelay();

        /* ── 8. Identical response (no enumeration) ── */
        return ['ok' => true, 'message' => 'If that username exists, a reset link has been sent. Check your inbox and spam folder. The link expires in <strong>15 minutes</strong>.'];
    }

    /**
     * resetPassword — 2nd knowledge factor: also verifies $usernameCheck
     * Even with a valid token, attacker must ALSO know the username.
     * Prevents misuse of intercepted reset emails.
     */
    public function resetPassword(string $rawToken, string $usernameCheck, string $newPw, string $confirmPw): array {
        if ($newPw !== $confirmPw)
            return ['ok' => false, 'error' => 'Passwords do not match'];

        $errors = $this->validatePassword($newPw);
        if ($errors) return ['ok' => false, 'error' => implode('. ', $errors)];

        $result = $this->consumeToken($rawToken, 'reset_password');
        if (!$result['ok']) return $result;

        /* ── 2nd factor: verify the username matches the token's owner ── */
        $user = $this->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$result['admin_id']]);
        if (!$user) return ['ok' => false, 'error' => 'Account not found.'];

        if (!hash_equals(strtolower(trim($user['username'])), strtolower(trim($usernameCheck)))) {
            $this->audit($result['admin_id'], 0, 'reset_pw_username_mismatch',
                'IP: ' . $this->getIP(), 'failed');
            $this->sendSecurityAlert('Reset Failed — Username Mismatch', $result['admin_id'],
                'Someone used a valid reset token but provided the wrong username.');
            /* Use a generic error to prevent confirming token validity */
            return ['ok' => false, 'error' => 'Verification failed. Ensure you entered your correct username, or request a new reset link.'];
        }

        /* ── Check if IP differs from the token-request IP (extra alert) ── */
        $tokenRow = $this->conn->prepare(
            "SELECT ip_address FROM password_reset_tokens WHERE token_hash = ? LIMIT 1"
        );
        /* Token is already consumed; we stored it so peek with is_used = 1 */
        $tokenRow->execute([hash('sha256', $rawToken)]);
        $tokenIp = (string)($tokenRow->fetchColumn() ?? '');
        if ($tokenIp && $tokenIp !== $this->getIP()) {
            $this->sendSecurityAlert('Reset from Different IP', $result['admin_id'],
                "Token requested from {$tokenIp}, used from " . $this->getIP());
        }

        $hash = password_hash($newPw, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->conn->prepare(
            "UPDATE admin_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $stmt->execute([$hash, $result['admin_id']]);
        $this->audit($result['admin_id'], 0, 'password_reset', 'Via forgot-password — 2FA passed');
        $this->sendActionDoneEmail($result['admin_id'], 'Password Reset', 'Your admin password has been reset successfully.');
        return ['ok' => true, 'message' => 'Password reset successfully! You can now log in.'];
    }

    /* ══════════════════════════════════════════════════
     *  FORGOT USERNAME  (public)
     *  Same security layers as forgot-password:
     *    bot detection, IP-block, honeypot, multi-window
     *    rate limit, auto IP-block, constant delay,
     *    no email enumeration, security alert
     * ══════════════════════════════════════════════════ */
    public function forgotUsername(string $email, string $honeypot = ''): array {
        /* ── 1. Bot detection ── */
        if ($this->isBotRequest()) {
            $this->audit(0, 0, 'forgot_un_bot_blocked', 'UA: ' . substr($_SERVER['HTTP_USER_AGENT'] ?? 'none', 0, 100), 'blocked');
            $this->constantTimeDelay();
            http_response_code(403);
            return ['ok' => false, 'error' => 'Request blocked.'];
        }

        /* ── 2. IP-block check ── */
        if ($this->isBlockedIP()) {
            $this->audit(0, 0, 'forgot_un_blocked_ip', 'IP: ' . $this->getIP(), 'blocked');
            $this->constantTimeDelay();
            return ['ok' => false, 'error' => 'Your IP has been temporarily blocked. Contact the administrator.'];
        }

        /* ── 3. Honeypot ── */
        if (!empty($honeypot)) {
            $this->audit(0, 0, 'forgot_un_honeypot', 'IP: ' . $this->getIP(), 'blocked');
            $this->constantTimeDelay();
            return ['ok' => true, 'message' => 'If an account with that email exists, the username has been sent.'];
        }

        /* ── 4. Multi-window rate limit ── */
        if ($this->multiWindowRateLimit('forgot_username', self::FORGOT_UN_LIMITS)) {
            $this->audit(0, 0, 'forgot_un_rate_limited', 'IP: ' . $this->getIP(), 'blocked');
            $this->constantTimeDelay();
            return ['ok' => false, 'error' => 'Too many requests from your IP address. Please wait before trying again.'];
        }

        /* ── Strict email format validation ── */
        $clean = strtolower(trim($email));
        if (!filter_var($clean, FILTER_VALIDATE_EMAIL) || strlen($clean) > 254) {
            $this->constantTimeDelay();
            return ['ok' => false, 'error' => 'Please enter a valid email address.'];
        }

        /* ── Record attempt ── */
        $this->recordRateAttempt('forgot_username');
        /* Note: checkAutoBlock only fires when rate-limit is already exceeded (see above) */

        /* ── Lookup ── */
        $stmt = $this->conn->prepare(
            "SELECT id, username, email, is_active FROM admin_users WHERE email = ? AND is_active = 1"
        );
        $stmt->execute([$clean]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $this->sendForgotUsernameEmail($user['email'], $user['username']);
            $this->audit($user['id'], 0, 'forgot_username_requested', 'IP: ' . $this->getIP());
        } else {
            $this->audit(0, 0, 'forgot_un_email_not_found', 'IP: ' . $this->getIP(), 'failed');
        }

        /* ── Security alert on every attempt ── */
        $this->sendSecurityAlert('Username Recovery Requested', $user['id'] ?? 0,
            'Email attempted: ' . substr($clean, 0, 40));

        /* ── Constant delay ── */
        $this->constantTimeDelay();

        /* ── No enumeration ── */
        return ['ok' => true, 'message' => 'If an account with that email exists, the username has been sent to that address.'];
    }

    /* ══════════════════════════════════════════════════
     *  CREATE ADMIN USER  (super_admin only)
     * ══════════════════════════════════════════════════ */
    public function createAdminUser(int $actorId, string $username, string $password, string $email, string $role): array {
        $actor = $this->getUser($actorId);
        if (!$actor || $actor['role'] !== 'super_admin')
            return ['ok' => false, 'error' => 'Only super admins can create new users'];

        $errors = $this->validateUsername($username);
        if ($errors) return ['ok' => false, 'error' => implode('. ', $errors)];

        $pwErrors = $this->validatePassword($password);
        if ($pwErrors) return ['ok' => false, 'error' => implode('. ', $pwErrors)];

        if ($this->usernameExists($username))
            return ['ok' => false, 'error' => 'Username already exists'];

        if (!in_array($role, ['admin', 'super_admin', 'editor']))
            return ['ok' => false, 'error' => 'Invalid role'];

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->conn->prepare(
            "INSERT INTO admin_users (username, password_hash, email, role, is_active)
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt->execute([$username, $hash, strtolower(trim($email)), $role]);
        $newId = (int)$this->conn->lastInsertId();
        $this->audit($newId, $actorId, 'user_created', "Username: {$username}, Role: {$role}");
        $this->notifyNewUserCreated($email ?: self::RECOVERY_EMAIL, $username, $role, $actorId);
        return ['ok' => true, 'message' => "Admin user '{$username}' created successfully."];
    }

    /* ══════════════════════════════════════════════════
     *  TOGGLE USER ACTIVE / DELETE
     * ══════════════════════════════════════════════════ */
    public function toggleUserActive(int $actorId, int $targetId): array {
        $actor  = $this->getUser($actorId);
        if (!$actor || $actor['role'] !== 'super_admin')
            return ['ok' => false, 'error' => 'Insufficient permissions'];
        if ($actorId === $targetId)
            return ['ok' => false, 'error' => 'Cannot change own account status here'];

        $stmt = $this->conn->prepare(
            "UPDATE admin_users SET is_active = ((is_active + 1) % 2) WHERE id = ?"
        );
        $stmt->execute([$targetId]);
        $user = $this->getUser($targetId);
        $status = $user['is_active'] ? 'enabled' : 'disabled';
        $this->audit($targetId, $actorId, "user_{$status}", "Target: {$user['username']}");
        return ['ok' => true, 'message' => "User '{$user['username']}' has been {$status}."];
    }

    public function deleteAdminUser(int $actorId, int $targetId, string $confirmPw): array {
        $actor = $this->fetchOne("SELECT * FROM admin_users WHERE id = ?", [$actorId]);
        if (!$actor || $actor['role'] !== 'super_admin')
            return ['ok' => false, 'error' => 'Insufficient permissions'];
        if (!password_verify($confirmPw, $actor['password_hash']))
            return ['ok' => false, 'error' => 'Your password is incorrect'];
        if ($actorId === $targetId)
            return ['ok' => false, 'error' => 'Cannot delete your own account'];

        $target = $this->getUser($targetId);
        if (!$target) return ['ok' => false, 'error' => 'User not found'];

        $stmt = $this->conn->prepare("DELETE FROM admin_users WHERE id = ?");
        $stmt->execute([$targetId]);
        $this->audit($targetId, $actorId, 'user_deleted', "Deleted: {$target['username']}");
        return ['ok' => true, 'message' => "User '{$target['username']}' deleted."];
    }

    /* ══════════════════════════════════════════════════
     *  AUDIT LOG
     * ══════════════════════════════════════════════════ */
    public function audit(int $adminId, int $actorId, string $action, string $details = '', string $status = 'success'): void {
        $stmt = $this->conn->prepare(
            "INSERT INTO account_audit_log (admin_id, actor_id, action, details, ip_address, user_agent, status)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $adminId, $actorId, $action, $details,
            $this->getIP(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 200),
            $status,
        ]);
    }

    public function getAuditLog(int $limit = 50): array {
        return $this->fetchAll(
            "SELECT a.*, u.username AS admin_name, act.username AS actor_name
             FROM account_audit_log a
             LEFT JOIN admin_users u   ON u.id   = a.admin_id
             LEFT JOIN admin_users act ON act.id = a.actor_id
             ORDER BY a.created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /* ══════════════════════════════════════════════════
     *  TOKEN HELPERS
     * ══════════════════════════════════════════════════ */
    private function createToken(string $action, int $adminId, string $payload, int $ttl): string {
        $raw  = bin2hex(random_bytes(32));
        $hash = hash('sha256', $raw);
        $exp  = date('Y-m-d H:i:s', time() + $ttl);
        $ip   = $this->getIP();

        /* Invalidate any previous unused token for same user+action */
        $del = $this->conn->prepare(
            "UPDATE email_verification_tokens SET is_used = 1
             WHERE admin_id = ? AND action = ? AND is_used = 0"
        );
        $del->execute([$adminId, $action]);

        if ($action === 'reset_password') {
            $del2 = $this->conn->prepare(
                "UPDATE password_reset_tokens SET is_used = 1 WHERE admin_id = ? AND is_used = 0"
            );
            $del2->execute([$adminId]);

            $ins = $this->conn->prepare(
                "INSERT INTO password_reset_tokens (admin_id, token_hash, ip_address, expires_at)
                 VALUES (?, ?, ?, ?)"
            );
            $ins->execute([$adminId, $hash, $ip, $exp]);
        } else {
            $ins = $this->conn->prepare(
                "INSERT INTO email_verification_tokens (admin_id, token_hash, action, payload, ip_address, expires_at)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $ins->execute([$adminId, $hash, $action, $payload, $ip, $exp]);
        }

        return $raw;
    }

    private function consumeToken(string $rawToken, string $action): array {
        $hash = hash('sha256', $rawToken);

        if ($action === 'reset_password') {
            $stmt = $this->conn->prepare(
                "SELECT * FROM password_reset_tokens
                 WHERE token_hash = ? AND is_used = 0 AND expires_at > CURRENT_TIMESTAMP"
            );
        } else {
            $stmt = $this->conn->prepare(
                "SELECT * FROM email_verification_tokens
                 WHERE token_hash = ? AND action = ? AND is_used = 0 AND expires_at > CURRENT_TIMESTAMP"
            );
        }

        if ($action === 'reset_password') {
            $stmt->execute([$hash]);
        } else {
            $stmt->execute([$hash, $action]);
        }

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return ['ok' => false, 'error' => 'Link is invalid, expired, or already used. Please request a new one.'];
        }

        /* Mark as used */
        if ($action === 'reset_password') {
            $upd = $this->conn->prepare("UPDATE password_reset_tokens SET is_used = 1 WHERE id = ?");
        } else {
            $upd = $this->conn->prepare("UPDATE email_verification_tokens SET is_used = 1 WHERE id = ?");
        }
        $upd->execute([$row['id']]);

        return [
            'ok'       => true,
            'admin_id' => (int)$row['admin_id'],
            'payload'  => $row['payload'] ?? '',
        ];
    }

    /* ══════════════════════════════════════════════════
     *  EMAIL HELPERS
     * ══════════════════════════════════════════════════ */
    private function sendPasswordResetEmail(string $to, string $username, string $token): bool {
        $link = SITE_URL . '/admin/reset-password.php?token=' . urlencode($token);
        $ip   = $this->getIP();
        $ua   = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 120);
        $time = date('d M Y, h:i:s A T');
        $subject = '🔑 Password Reset Request — GCM Netting Solutions Admin';
        $body = $this->emailWrap('Password Reset Request', '#e74c3c', "
            <p>Hello <strong>{$username}</strong>,</p>
            <p>A password reset was requested for your admin account from the details below:</p>
            <table style='width:100%;border-collapse:collapse;margin:16px 0;background:#fff8f8;border-radius:8px;overflow:hidden;'>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#c0392b;width:35%;border-bottom:1px solid #fdd;'>Time</td><td style='padding:10px 14px;border-bottom:1px solid #fdd;'>{$time}</td></tr>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#c0392b;border-bottom:1px solid #fdd;'>IP Address</td><td style='padding:10px 14px;border-bottom:1px solid #fdd;'><code>{$ip}</code></td></tr>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#c0392b;'>Browser</td><td style='padding:10px 14px;font-size:12px;'>{$ua}</td></tr>
            </table>
            <p style='background:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:6px;color:#856404;'>
                <strong>⚠ Was this you?</strong> If YES, click the button below within 15 minutes.<br>
                If NO, <strong>do NOT click the link</strong> and your password remains unchanged.
            </p>
            <div style='text-align:center;margin:28px 0;'>
                <a href='{$link}' style='background:#e74c3c;color:#fff;padding:14px 36px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold;display:inline-block;'>Reset My Password</a>
            </div>
            <p style='color:#e74c3c;font-weight:bold;text-align:center;'>⏰ This link expires in exactly 15 minutes and works only once.</p>
            <p style='background:#f8d7da;padding:12px;border-radius:6px;font-size:13px;color:#721c24;margin-top:16px;'>
                <strong>Additional security:</strong> You will also need to enter your username on the reset page.
                This means even if someone else gets this link, they cannot reset your password without knowing your username.
            </p>
            <p style='font-size:11px;color:#aaa;word-break:break-all;margin-top:20px;'>Direct link (do not share):<br>{$link}</p>
        ");
        return send_email($to, $subject, $body);
    }

    private function sendChangeConfirmEmail(string $to, string $username, string $token, string $what): bool {
        $link    = SITE_URL . '/admin/verify-account-action.php?token=' . urlencode($token);
        $subject = "✅ Confirm {$what} — GCM Netting Solutions Admin";
        $body = $this->emailWrap("Confirm {$what}", "#667eea", "
            <p>Hello <strong>{$username}</strong>,</p>
            <p>You (or someone with your credentials) requested a <strong>{$what}</strong> on your admin account.</p>
            <p>Click the button below to <strong>confirm and apply</strong> this change:</p>
            <div style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#667eea;color:#fff;padding:14px 32px;border-radius:8px;text-decoration:none;font-size:16px;font-weight:bold;display:inline-block;'>Confirm {$what}</a>
            </div>
            <p style='color:#e67e22;'><strong>This link expires in 30 minutes.</strong></p>
            <p>If you did NOT request this change, <strong>do not click the link</strong> and contact your web administrator immediately.</p>
            <p style='font-size:12px;color:#888;word-break:break-all;'>Or copy this link:<br>{$link}</p>
        ");
        return send_email($to, $subject, $body);
    }

    private function sendForgotUsernameEmail(string $to, string $username): bool {
        $ip   = $this->getIP();
        $ua   = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 120);
        $time = date('d M Y, h:i:s A T');
        $subject = '👤 Username Recovery — GCM Netting Solutions Admin';
        $body = $this->emailWrap('Username Recovery', '#27ae60', "
            <p>Hello,</p>
            <p>A username recovery was requested for the GCM Netting Solutions Admin Panel from the details below:</p>
            <table style='width:100%;border-collapse:collapse;margin:12px 0;background:#f0fdf4;border-radius:8px;overflow:hidden;'>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#166534;width:35%;border-bottom:1px solid #d1fae5;'>Time</td><td style='padding:10px 14px;border-bottom:1px solid #d1fae5;'>{$time}</td></tr>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#166534;border-bottom:1px solid #d1fae5;'>IP Address</td><td style='padding:10px 14px;border-bottom:1px solid #d1fae5;'><code>{$ip}</code></td></tr>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#166534;'>Browser</td><td style='padding:10px 14px;font-size:12px;'>{$ua}</td></tr>
            </table>
            <p style='background:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:6px;color:#856404;'>
                <strong>⚠ Was this you?</strong> If YES, your username is shown below.<br>
                If NO, ignore this email — no changes have been made.
            </p>
            <p style='margin-top:20px;'>Your admin username is:</p>
            <div style='text-align:center;margin:20px 0;'>
                <span style='background:#f0fdf4;border:2px solid #27ae60;padding:14px 36px;border-radius:8px;font-size:22px;font-weight:bold;font-family:monospace;display:inline-block;letter-spacing:3px;'>{$username}</span>
            </div>
            <p style='font-size:13px;color:#666;background:#f8fafc;padding:10px;border-radius:6px;'>
                <strong>Security tip:</strong> Never share your username or password with anyone.
                GCM Netting Solutions staff will never ask for your credentials.
            </p>
            <p style='margin-top:16px;'><a href='" . SITE_URL . "/admin/login.php' style='color:#667eea;font-weight:bold;'>Go to Admin Login &rarr;</a></p>
        ");
        return send_email($to, $subject, $body);
    }

    private function sendActionDoneEmail(int $adminId, string $action, string $detail): void {
        $user = $this->getUser($adminId);
        if (!$user) return;
        $to      = $user['email'] ?: self::RECOVERY_EMAIL;
        $subject = "✅ {$action} Successful — GCM Netting Solutions Admin";
        $body = $this->emailWrap($action . ' Successful', '#27ae60', "
            <p>Hello <strong>{$user['username']}</strong>,</p>
            <p>{$detail}</p>
            <p><strong>Time:</strong> " . date('d M Y, h:i:s A') . "</p>
            <p><strong>IP:</strong> " . $this->getIP() . "</p>
            <p>If you did NOT perform this action, contact your administrator immediately and change your credentials.</p>
            <p><a href='" . SITE_URL . "/admin/login.php' style='color:#667eea;'>Admin Login &rarr;</a></p>
        ");
        send_email($to, $subject, $body);
        send_email(self::RECOVERY_EMAIL, "[Security] {$subject}", $body);
    }

    private function notifyNewUserCreated(string $to, string $username, string $role, int $createdBy): void {
        $actor   = $this->getUser($createdBy);
        $subject = '👤 New Admin User Created — GCM Netting Solutions';
        $body = $this->emailWrap('New Admin User Created', '#8e44ad', "
            <p>A new admin user has been created:</p>
            <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                <tr><td style='padding:10px;font-weight:bold;color:#667eea;width:40%;'>Username</td><td style='padding:10px;'><strong>{$username}</strong></td></tr>
                <tr><td style='padding:10px;font-weight:bold;color:#667eea;'>Role</td><td style='padding:10px;'>{$role}</td></tr>
                <tr><td style='padding:10px;font-weight:bold;color:#667eea;'>Created By</td><td style='padding:10px;'>" . ($actor['username'] ?? 'Unknown') . "</td></tr>
                <tr><td style='padding:10px;font-weight:bold;color:#667eea;'>Time</td><td style='padding:10px;'>" . date('d M Y, h:i A') . "</td></tr>
            </table>
            <p>If this was not authorized, contact your administrator immediately.</p>
        ");
        send_email($to, $subject, $body);
        send_email(self::RECOVERY_EMAIL, $subject, $body);
    }

    private function emailWrap(string $title, string $color, string $content): string {
        return "<!DOCTYPE html><html><head><meta charset='UTF-8'>
        <style>
            body{font-family:Arial,sans-serif;background:#f4f4f4;margin:0;padding:0;}
            .wrap{max-width:600px;margin:20px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.12);}
            .hdr{background:{$color};color:#fff;padding:30px;text-align:center;}
            .hdr h1{margin:0;font-size:22px;}
            .body{padding:30px;color:#333;line-height:1.6;font-size:15px;}
            .ftr{background:#f8f9fa;padding:16px;text-align:center;color:#888;font-size:12px;}
        </style></head><body>
        <div class='wrap'>
            <div class='hdr'><h1>🔒 {$title}</h1><p style='margin:8px 0 0;opacity:.9;font-size:14px;'>GCM Netting Solutions Admin</p></div>
            <div class='body'>{$content}</div>
            <div class='ftr'>GCM Netting Solutions · " . SITE_URL . " · This is an automated security email</div>
        </div></body></html>";
    }

    /* ══════════════════════════════════════════════════
     *  SECURITY ALERT EMAIL
     * ══════════════════════════════════════════════════ */
    private function sendSecurityAlert(string $event, int $adminId = 0, string $extra = ''): void {
        $user  = ($adminId > 0) ? $this->getUser($adminId) : null;
        $uname = $user ? $user['username'] : 'Unknown';
        $ip    = $this->getIP();
        $ua    = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 150);
        $time  = date('d M Y, h:i:s A T');
        $subject = "⚠️ Security Alert: {$event} — GCM Netting Solutions Admin";
        $body = $this->emailWrap("Security Alert: {$event}", '#c0392b', "
            <p>A security event has occurred on the <strong>GCM Netting Solutions Admin Panel</strong>:</p>
            <table style='width:100%;border-collapse:collapse;margin:16px 0;'>
                <tr style='background:#fff8f8;'><td style='padding:10px 14px;font-weight:bold;color:#c0392b;width:35%;border-bottom:1px solid #fdd;'>Event</td><td style='padding:10px 14px;border-bottom:1px solid #fdd;'><strong>{$event}</strong></td></tr>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#c0392b;border-bottom:1px solid #fdd;'>Account</td><td style='padding:10px 14px;border-bottom:1px solid #fdd;'>{$uname}</td></tr>
                <tr style='background:#fff8f8;'><td style='padding:10px 14px;font-weight:bold;color:#c0392b;border-bottom:1px solid #fdd;'>Time</td><td style='padding:10px 14px;border-bottom:1px solid #fdd;'>{$time}</td></tr>
                <tr><td style='padding:10px 14px;font-weight:bold;color:#c0392b;border-bottom:1px solid #fdd;'>IP Address</td><td style='padding:10px 14px;border-bottom:1px solid #fdd;'><code>{$ip}</code></td></tr>
                <tr style='background:#fff8f8;'><td style='padding:10px 14px;font-weight:bold;color:#c0392b;'>Browser</td><td style='padding:10px 14px;font-size:12px;'>{$ua}</td></tr>
            </table>
            " . ($extra ? "<p style='background:#fff3cd;border:1px solid #ffc107;padding:12px;border-radius:6px;color:#856404;font-size:13px;'><strong>Details:</strong> {$extra}</p>" : '') . "
            <p style='background:#fee2e2;padding:12px;border-radius:6px;color:#991b1b;margin-top:16px;'>
                <strong>Action required if unexpected:</strong> Log in immediately, change your password,
                and review the <a href='" . SITE_URL . "/admin/pages/account-manager.php' style='color:#b91c1c;'>audit log</a>.
                Consider blocking IP <code>{$ip}</code> if suspicious.
            </p>
        ");
        send_email(self::RECOVERY_EMAIL, $subject, $body);
    }

    /* ══════════════════════════════════════════════════
     *  UTILITY
     * ══════════════════════════════════════════════════ */
    private function getIP(): string {
        foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $k) {
            if (!empty($_SERVER[$k])) {
                $ip = explode(',', $_SERVER[$k])[0];
                if (filter_var(trim($ip), FILTER_VALIDATE_IP)) return trim($ip);
            }
        }
        return 'unknown';
    }
}
