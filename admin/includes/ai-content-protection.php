<?php
/**
 * GCM Netting Solutions - AI Content Protection System
 * Prevents easy deletion of AI-generated content.
 * Fully SQLite-compatible (PDO).
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

class AIContentProtection {

    private $db;
    private $pin_file;

    public function __construct() {
        require_once dirname(dirname(__DIR__)) . '/config/database.php';
        $this->db      = Database::getInstance();
        $this->pin_file = dirname(dirname(__DIR__)) . '/config/.ai_protection_pin';
        $this->initTables();
    }

    /* ──────────────────────────────────────────────────────
     * TABLE SETUP
     * ────────────────────────────────────────────────────── */
    private function initTables() {
        $conn = $this->db->getConnection();

        /* Protected content registry */
        try { $conn->exec("CREATE TABLE IF NOT EXISTS ai_protected_content (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            content_type VARCHAR(100) NOT NULL,
            content_id   VARCHAR(255) NOT NULL,
            content_title VARCHAR(500),
            content_path VARCHAR(500),
            generated_by VARCHAR(50)  DEFAULT 'gemini',
            is_protected TINYINT(1)   DEFAULT 1,
            protected_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
            protected_by INT          DEFAULT 0,
            UNIQUE KEY uq_type_id (content_type, content_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(\Exception $e) { error_log('[ai-protect] ' . $e->getMessage()); }

        /* Deletion audit log */
        try { $conn->exec("CREATE TABLE IF NOT EXISTS ai_deletion_log (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            content_type  VARCHAR(100),
            content_id    VARCHAR(255),
            content_title VARCHAR(500),
            action        VARCHAR(100),
            admin_id      INT,
            ip_address    VARCHAR(45),
            reason        TEXT,
            pin_correct   TINYINT(1) DEFAULT 0,
            created_at    DATETIME   DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(\Exception $e) { error_log('[ai-protect] ' . $e->getMessage()); }

        /* Security settings */
        try { $conn->exec("CREATE TABLE IF NOT EXISTS ai_security_settings (
            key_name   VARCHAR(100) NOT NULL PRIMARY KEY,
            key_value  TEXT,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(\Exception $e) { error_log('[ai-protect] ' . $e->getMessage()); }

        /* Pending deletions table — email approval flow */
        try { $conn->exec("CREATE TABLE IF NOT EXISTS ai_pending_deletions (
            id           INT AUTO_INCREMENT PRIMARY KEY,
            token        VARCHAR(64) NOT NULL UNIQUE,
            content_type VARCHAR(100),
            content_id   VARCHAR(255),
            content_title VARCHAR(500),
            admin_id     INT DEFAULT 0,
            ip_address   VARCHAR(45),
            reason       TEXT,
            status       ENUM('pending','approved','expired','cancelled') DEFAULT 'pending',
            requested_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            approved_at  DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch(\Exception $e) { error_log('[ai-protect] ' . $e->getMessage()); }

        /* Seed default settings if missing */
        $existing = $this->db->fetchOne("SELECT COUNT(*) AS c FROM ai_security_settings");
        if (!$existing || (int)$existing['c'] === 0) {
            try {
                $conn->exec(
                    "INSERT IGNORE INTO ai_security_settings (key_name, key_value) VALUES
                     ('auto_protect_on_generate', '1'),
                     ('require_pin_to_delete', '1'),
                     ('deletion_cooldown_hours', '24'),
                     ('require_reason_to_delete', '1'),
                     ('pin_hash', '')"
                );
            } catch(\Exception $e) { error_log('[ai-protect seed] ' . $e->getMessage()); }
        }
    }

    /* ──────────────────────────────────────────────────────
     * PIN MANAGEMENT
     * ────────────────────────────────────────────────────── */

    public function setProtectionPin($plain_pin, $admin_id) {
        if (strlen($plain_pin) < 4) {
            return ['success' => false, 'error' => 'PIN must be at least 4 characters.'];
        }
        $hash = password_hash($plain_pin, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->execute(
            "INSERT INTO ai_security_settings (key_name, key_value, updated_at)
             VALUES ('pin_hash', ?, NOW())
             ON DUPLICATE KEY UPDATE key_value = VALUES(key_value), updated_at = NOW()",
            [$hash]
        );
        $this->logAction('pin_changed', 0, $admin_id, 'Security PIN updated', 1);
        return ['success' => true];
    }

    public function verifyPin($plain_pin) {
        $row = $this->db->fetchOne(
            "SELECT key_value FROM ai_security_settings WHERE key_name = 'pin_hash'"
        );
        if (!$row || empty($row['key_value'])) {
            return false;
        }
        return password_verify($plain_pin, $row['key_value']);
    }

    public function hasPinSet() {
        $row = $this->db->fetchOne(
            "SELECT key_value FROM ai_security_settings WHERE key_name = 'pin_hash'"
        );
        return $row && !empty($row['key_value']);
    }

    /* ──────────────────────────────────────────────────────
     * PROTECT CONTENT (called after AI generates anything)
     * ────────────────────────────────────────────────────── */

    public function protect($content_type, $content_id, $title = '', $path = '', $admin_id = 0) {
        return $this->db->execute(
            "INSERT INTO ai_protected_content
             (content_type, content_id, content_title, content_path, is_protected, protected_at, protected_by)
             VALUES (?, ?, ?, ?, 1, NOW(), ?)
             ON DUPLICATE KEY UPDATE
             is_protected = 1, protected_at = NOW(), content_title = VALUES(content_title)",
            [$content_type, $content_id, $title, $path, $admin_id]
        );
    }

    /* ──────────────────────────────────────────────────────
     * CHECK PROTECTION
     * ────────────────────────────────────────────────────── */

    public function isProtected($content_type, $content_id) {
        $row = $this->db->fetchOne(
            "SELECT is_protected FROM ai_protected_content
             WHERE content_type = ? AND content_id = ?",
            [$content_type, $content_id]
        );
        return $row ? (bool)(int)$row['is_protected'] : false;
    }

    /* ──────────────────────────────────────────────────────
     * REQUEST DELETE — validates PIN + reason, logs attempt
     * Returns ['allowed' => bool, 'error' => string]
     * ────────────────────────────────────────────────────── */

    public function requestDelete($content_type, $content_id, $title, $pin, $reason, $admin_id) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

        $require_pin    = (bool)(int)$this->getSetting('require_pin_to_delete');
        $require_reason = (bool)(int)$this->getSetting('require_reason_to_delete');
        $cooldown_h     = (int)$this->getSetting('deletion_cooldown_hours');

        /* Reason check */
        if ($require_reason && trim($reason) === '') {
            $this->logAction('delete_blocked', $content_id, $admin_id, 'No reason provided', 0, $content_type, $title, $ip);
            return ['allowed' => false, 'error' => 'A deletion reason is required.'];
        }

        /* PIN check */
        if ($require_pin) {
            if (!$this->hasPinSet()) {
                $this->logAction('delete_blocked', $content_id, $admin_id, 'No PIN set — configure PIN first', 0, $content_type, $title, $ip);
                return ['allowed' => false, 'error' => 'Security PIN not configured. Set it in AI Content Security settings.'];
            }
            if (!$this->verifyPin($pin)) {
                $this->logAction('delete_blocked', $content_id, $admin_id, 'Wrong PIN entered', 0, $content_type, $title, $ip);
                return ['allowed' => false, 'error' => 'Incorrect security PIN. Deletion blocked.'];
            }
        }

        /* Cooldown check — content must be older than X hours */
        if ($cooldown_h > 0) {
            $row = $this->db->fetchOne(
                "SELECT protected_at FROM ai_protected_content
                 WHERE content_type = ? AND content_id = ?",
                [$content_type, $content_id]
            );
            if ($row) {
                $generated_ts = strtotime($row['protected_at']);
                $elapsed_h    = (time() - $generated_ts) / 3600;
                if ($elapsed_h < $cooldown_h) {
                    $remaining = ceil($cooldown_h - $elapsed_h);
                    $this->logAction('delete_blocked', $content_id, $admin_id,
                        "Cooldown: {$remaining}h remaining", 0, $content_type, $title, $ip);
                    return [
                        'allowed' => false,
                        'error'   => "This AI content is under a {$cooldown_h}-hour protection period. Please wait {$remaining} more hour(s) before deleting."
                    ];
                }
            }
        }

        /* All checks passed */
        $this->logAction('delete_approved', $content_id, $admin_id, $reason, 1, $content_type, $title, $ip);

        /* Remove protection record */
        $this->db->execute(
            "DELETE FROM ai_protected_content WHERE content_type = ? AND content_id = ?",
            [$content_type, $content_id]
        );

        return ['allowed' => true];
    }

    /* ──────────────────────────────────────────────────────
     * MANUAL LOCK / UNLOCK
     * ────────────────────────────────────────────────────── */

    public function manualLock($content_type, $content_id, $title, $admin_id) {
        $result = $this->protect($content_type, $content_id, $title, '', $admin_id);
        if ($result) {
            $this->logAction('manual_lock', $content_id, $admin_id, 'Manually locked', 1, $content_type, $title);
        }
        return $result;
    }

    public function manualUnlock($content_type, $content_id, $pin, $admin_id) {
        if (!$this->verifyPin($pin)) {
            $this->logAction('unlock_failed', $content_id, $admin_id, 'Wrong PIN', 0, $content_type);
            return ['success' => false, 'error' => 'Incorrect PIN.'];
        }
        $this->db->execute(
            "UPDATE ai_protected_content SET is_protected = 0 WHERE content_type = ? AND content_id = ?",
            [$content_type, $content_id]
        );
        $this->logAction('manual_unlock', $content_id, $admin_id, 'Manually unlocked', 1, $content_type);
        return ['success' => true];
    }

    /* ──────────────────────────────────────────────────────
     * SETTINGS HELPERS
     * ────────────────────────────────────────────────────── */

    public function getSetting($key) {
        $row = $this->db->fetchOne(
            "SELECT key_value FROM ai_security_settings WHERE key_name = ?", [$key]
        );
        return $row ? $row['key_value'] : null;
    }

    public function updateSetting($key, $value, $admin_id = 0) {
        $this->db->execute(
            "INSERT INTO ai_security_settings (key_name, key_value, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE key_value = VALUES(key_value), updated_at = NOW()",
            [$key, $value]
        );
        $this->logAction('setting_changed', $key, $admin_id, "Set {$key} = {$value}", 1);
    }

    public function getAllSettings() {
        $rows = $this->db->fetchAll("SELECT key_name, key_value FROM ai_security_settings WHERE key_name != 'pin_hash'");
        $out  = [];
        foreach ($rows as $r) {
            $out[$r['key_name']] = $r['key_value'];
        }
        return $out;
    }

    /* ──────────────────────────────────────────────────────
     * STATS & LISTINGS
     * ────────────────────────────────────────────────────── */

    public function getStats() {
        return [
            'total_protected' => (int)($this->db->fetchOne(
                "SELECT COUNT(*) AS c FROM ai_protected_content WHERE is_protected = 1"
            )['c'] ?? 0),
            'by_type'         => $this->db->fetchAll(
                "SELECT content_type, COUNT(*) AS c FROM ai_protected_content WHERE is_protected = 1 GROUP BY content_type"
            ),
            'recent_deletions'=> (int)($this->db->fetchOne(
                "SELECT COUNT(*) AS c FROM ai_deletion_log WHERE action = 'delete_approved' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )['c'] ?? 0),
            'blocked_attempts'=> (int)($this->db->fetchOne(
                "SELECT COUNT(*) AS c FROM ai_deletion_log WHERE action = 'delete_blocked' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            )['c'] ?? 0),
        ];
    }

    public function getProtectedContent($type = null, $limit = 100) {
        if ($type) {
            return $this->db->fetchAll(
                "SELECT * FROM ai_protected_content WHERE is_protected = 1 AND content_type = ? ORDER BY protected_at DESC LIMIT ?",
                [$type, $limit]
            );
        }
        return $this->db->fetchAll(
            "SELECT * FROM ai_protected_content WHERE is_protected = 1 ORDER BY protected_at DESC LIMIT ?",
            [$limit]
        );
    }

    public function getAuditLog($limit = 50) {
        return $this->db->fetchAll(
            "SELECT * FROM ai_deletion_log ORDER BY created_at DESC LIMIT ?",
            [$limit]
        );
    }

    /* ──────────────────────────────────────────────────────
     * EMAIL APPROVAL FLOW
     * Call this instead of requestDelete() for PIN+email workflow.
     * Returns ['allowed'=>false,'pending'=>true,'message'=>'...']
     *   or    ['allowed'=>false,'error'=>'...']
     * ────────────────────────────────────────────────────── */

    const APPROVAL_EMAIL = 'gopichandmailapally@gmail.com';

    public function requestDeleteWithApproval($content_type, $content_id, $title, $pin, $reason, $admin_id) {
        $ip             = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $require_pin    = (bool)(int)$this->getSetting('require_pin_to_delete');
        $require_reason = (bool)(int)$this->getSetting('require_reason_to_delete');
        $cooldown_h     = (int)$this->getSetting('deletion_cooldown_hours');

        /* Reason check */
        if ($require_reason && trim($reason) === '') {
            $this->logAction('delete_blocked', $content_id, $admin_id, 'No reason provided', 0, $content_type, $title, $ip);
            return ['allowed' => false, 'error' => 'A deletion reason is required.'];
        }

        /* PIN check */
        if ($require_pin) {
            if (!$this->hasPinSet()) {
                return ['allowed' => false, 'error' => 'Security PIN not configured. Set it in AI Content Security settings.'];
            }
            if (!$this->verifyPin($pin)) {
                $this->logAction('delete_blocked', $content_id, $admin_id, 'Wrong PIN entered', 0, $content_type, $title, $ip);
                return ['allowed' => false, 'error' => 'Incorrect security PIN. Deletion blocked.'];
            }
        }

        /* Cooldown check */
        if ($cooldown_h > 0) {
            $row = $this->db->fetchOne(
                "SELECT protected_at FROM ai_protected_content WHERE content_type=? AND content_id=?",
                [$content_type, $content_id]
            );
            if ($row) {
                $elapsed_h = (time() - strtotime($row['protected_at'])) / 3600;
                if ($elapsed_h < $cooldown_h) {
                    $remaining = ceil($cooldown_h - $elapsed_h);
                    $this->logAction('delete_blocked', $content_id, $admin_id,
                        "Cooldown: {$remaining}h remaining", 0, $content_type, $title, $ip);
                    return ['allowed' => false,
                        'error' => "Content is under a {$cooldown_h}h protection period. Wait {$remaining} more hour(s)."];
                }
            }
        }

        /* All checks passed — create pending approval token */
        $token = bin2hex(random_bytes(32));   // 64-char secure token
        $this->db->execute(
            "INSERT INTO ai_pending_deletions
             (token, content_type, content_id, content_title, admin_id, ip_address, reason, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')",
            [$token, $content_type, $content_id, $title, $admin_id, $ip, $reason]
        );

        /* Send approval email */
        $site_url    = defined('SITE_URL') ? SITE_URL : 'https://gcmsafetynets.in';
        $approve_url = $site_url . '/admin/api/approve-content-delete.php?token=' . $token;
        $cancel_url  = $site_url . '/admin/api/approve-content-delete.php?token=' . $token . '&action=cancel';
        $type_label  = ucwords(str_replace('_', ' ', $content_type));
        $short_title = mb_substr($title, 0, 120);
        $requested   = date('d M Y H:i:s');

        $subject = "[GCM Admin] Deletion Approval Required — {$type_label}";
        $body    = "=== AI CONTENT DELETION APPROVAL ===\n\n"
                 . "A deletion request has been submitted and requires your approval.\n\n"
                 . "Content Type : {$type_label}\n"
                 . "Title        : {$short_title}\n"
                 . "Reason       : " . (trim($reason) ?: '(none given)') . "\n"
                 . "Requested At : {$requested}\n"
                 . "Admin IP     : {$ip}\n\n"
                 . "--- TO APPROVE DELETION ---\n"
                 . "Click this link (valid for 24 hours):\n"
                 . $approve_url . "\n\n"
                 . "--- TO CANCEL ---\n"
                 . $cancel_url . "\n\n"
                 . "If you did not request this, ignore this email. The content will NOT be deleted.\n\n"
                 . "-- GCM Netting Solutions Admin System --\n"
                 . $site_url;

        $headers = implode("\r\n", [
            'From: GCM Admin <noreply@gcmsafetynets.in>',
            'Reply-To: noreply@gcmsafetynets.in',
            'X-Mailer: PHP/' . PHP_VERSION,
            'Content-Type: text/plain; charset=UTF-8',
        ]);

        @mail(self::APPROVAL_EMAIL, $subject, $body, $headers);
        $this->logAction('delete_pending', $content_id, $admin_id,
            'Email approval sent for: ' . $short_title, 1, $content_type, $title, $ip);

        return [
            'allowed'  => false,
            'pending'  => true,
            'token'    => $token,
            'message'  => 'Deletion request submitted! An approval email has been sent to the admin. Content will be deleted only after email approval.',
        ];
    }

    /* ──────────────────────────────────────────────────────
     * APPROVE PENDING DELETION (called from approval link)
     * ────────────────────────────────────────────────────── */

    public function approvePendingDeletion($token) {
        $row = $this->db->fetchOne(
            "SELECT * FROM ai_pending_deletions WHERE token=? AND status='pending'", [$token]
        );
        if (!$row) {
            return ['success' => false, 'error' => 'Invalid or already-processed token.'];
        }
        /* Expire tokens older than 24 hours */
        if (strtotime($row['requested_at']) < time() - 86400) {
            $this->db->execute("UPDATE ai_pending_deletions SET status='expired' WHERE token=?", [$token]);
            return ['success' => false, 'error' => 'This approval link has expired (24-hour limit).'];
        }

        /* Mark approved */
        $this->db->execute(
            "UPDATE ai_pending_deletions SET status='approved', approved_at=NOW() WHERE token=?", [$token]
        );

        /* Remove protection record */
        $this->db->execute(
            "DELETE FROM ai_protected_content WHERE content_type=? AND content_id=?",
            [$row['content_type'], $row['content_id']]
        );

        /* Perform actual deletion */
        $del_result = $this->performActualDeletion($row['content_type'], $row['content_id']);

        $this->logAction('delete_approved', $row['content_id'], (int)$row['admin_id'],
            'Approved via email link. ' . $del_result, 1, $row['content_type'], $row['content_title']);

        return [
            'success'       => true,
            'content_type'  => $row['content_type'],
            'content_id'    => $row['content_id'],
            'content_title' => $row['content_title'],
            'del_result'    => $del_result,
        ];
    }

    public function cancelPendingDeletion($token) {
        $row = $this->db->fetchOne(
            "SELECT * FROM ai_pending_deletions WHERE token=? AND status='pending'", [$token]
        );
        if (!$row) return ['success' => false, 'error' => 'Invalid or already-processed token.'];
        $this->db->execute("UPDATE ai_pending_deletions SET status='cancelled' WHERE token=?", [$token]);
        return ['success' => true, 'content_title' => $row['content_title']];
    }

    /* ──────────────────────────────────────────────────────
     * PHYSICAL DELETION — removes file / DB row per type
     * ────────────────────────────────────────────────────── */

    private function performActualDeletion($content_type, $content_id) {
        $root_dir = dirname(dirname(__DIR__));
        $data_dir = $root_dir . '/data/';
        $msgs     = [];

        switch ($content_type) {
            case 'service_page':
                try {
                    $this->db->execute(
                        "DELETE FROM generated_pages WHERE slug=? OR (keyword_id>0 AND area_id>0 AND CONCAT(
                            (SELECT keyword_slug FROM seo_service_keywords WHERE id=keyword_id LIMIT 1),'-',
                            (SELECT area_slug    FROM service_areas          WHERE id=area_id    LIMIT 1)
                        )=?)",
                        [$content_id, $content_id]
                    );
                    $msgs[] = 'DB row removed';
                } catch(\Exception $e) { $msgs[] = 'DB error: ' . $e->getMessage(); }
                $file = $root_dir . '/generated-pages/' . basename($content_id) . '.php';
                if (file_exists($file)) { @unlink($file); $msgs[] = 'File deleted'; }
                break;

            case 'blog':
                $file = $data_dir . 'blogs/' . basename($content_id) . '.json';
                if (file_exists($file)) { @unlink($file); $msgs[] = 'JSON deleted'; }
                try { $this->db->execute("DELETE FROM ai_blogs WHERE slug=?", [$content_id]); } catch(\Exception $e) {}
                break;

            case 'review':
                $file = $data_dir . 'reviews/' . basename($content_id) . '.json';
                if (file_exists($file)) { @unlink($file); $msgs[] = 'JSON deleted'; }
                try { $this->db->execute("DELETE FROM ai_reviews WHERE slug=?", [$content_id]); } catch(\Exception $e) {}
                break;

            case 'faq':
                $file = $data_dir . 'faqs/' . basename($content_id) . '.json';
                if (file_exists($file)) { @unlink($file); $msgs[] = 'JSON deleted'; }
                try { $this->db->execute("DELETE FROM faqs WHERE slug=?", [$content_id]); } catch(\Exception $e) {}
                break;

            case 'pillar_page':
                $file = $root_dir . '/' . basename($content_id) . '.php';
                if (file_exists($file)) { @unlink($file); $msgs[] = 'File deleted'; }
                break;

            default:
                $msgs[] = 'Unknown content type — no physical deletion performed';
        }

        return implode('; ', $msgs) ?: 'No action taken';
    }

    /* ──────────────────────────────────────────────────────
     * PRUNE STALE — delete protection rows for missing content
     * ────────────────────────────────────────────────────── */

    private function pruneStale($content_type, array $valid_ids, $conn) {
        try {
            $stmt = $conn->prepare(
                "SELECT content_id FROM ai_protected_content WHERE content_type = ?"
            );
            $stmt->execute([$content_type]);
            $existing = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        } catch(\Exception $e) { return; }

        $stale = array_values(array_diff($existing, $valid_ids));
        if (empty($stale)) return;

        foreach (array_chunk($stale, 500) as $chunk) {
            $ph = implode(',', array_fill(0, count($chunk), '?'));
            try {
                $conn->prepare(
                    "DELETE FROM ai_protected_content WHERE content_type=? AND content_id IN ($ph)"
                )->execute(array_merge([$content_type], $chunk));
            } catch(\Exception $e) {}
        }
    }

    /* ──────────────────────────────────────────────────────
     * SYNC — register ALL existing AI content into protection table
     * ────────────────────────────────────────────────────── */

    public function syncAllContent() {
        $root_dir  = dirname(dirname(__DIR__));
        $data_dir  = $root_dir . '/data/';
        $gen_dir   = $root_dir . '/generated-pages/';
        $added     = 0;
        $conn      = $this->db->getConnection();
        $skip_json = ['index.json', 'stats.json'];

        /* ── 0. Prune stale entries (content deleted since last sync) ─────── */

        // FAQs — check filesystem
        $valid_faq = array_values(array_map(
            fn($f) => basename($f, '.json'),
            array_filter(glob($data_dir . 'faqs/*.json') ?: [],
                fn($f) => !in_array(basename($f), $skip_json))
        ));
        $this->pruneStale('faq', $valid_faq, $conn);

        // Blogs
        $valid_blog = array_values(array_map(
            fn($f) => basename($f, '.json'),
            array_filter(glob($data_dir . 'blogs/*.json') ?: [],
                fn($f) => !in_array(basename($f), $skip_json))
        ));
        $this->pruneStale('blog', $valid_blog, $conn);

        // Reviews
        $valid_review = array_values(array_map(
            fn($f) => basename($f, '.json'),
            array_filter(glob($data_dir . 'reviews/*.json') ?: [],
                fn($f) => !in_array(basename($f), $skip_json))
        ));
        $this->pruneStale('review', $valid_review, $conn);

        // Service pages — filesystem (same source of truth as getRealCounts)
        $valid_svc = array_values(array_map(
            fn($f) => basename($f, '.php'),
            array_filter(glob($gen_dir . '*.php') ?: [],
                fn($f) => basename($f) !== 'index.php')
        ));
        $this->pruneStale('service_page', $valid_svc, $conn);

        // Pillar pages
        $valid_pillar = [];
        foreach (($this->db->fetchAll(
            "SELECT keyword_slug FROM seo_service_keywords WHERE is_active=1"
        ) ?: []) as $_k) {
            if (!empty($_k['keyword_slug']) &&
                file_exists($root_dir . '/' . $_k['keyword_slug'] . '.php')) {
                $valid_pillar[] = $_k['keyword_slug'];
            }
        }
        $this->pruneStale('pillar_page', $valid_pillar, $conn);

        /* Also remove any leftover integer-ID service_page rows */
        try { $conn->exec(
            "DELETE FROM ai_protected_content WHERE content_type='service_page' AND content_id REGEXP '^[0-9]+$'"
        ); } catch(\Exception $e) {}

        /* Helper: upsert with ON DUPLICATE KEY so is_protected=0 rows get re-protected */
        $upsert_sql = function($type) {
            return "INSERT INTO ai_protected_content
                 (content_type, content_id, content_title, content_path, is_protected, protected_at, protected_by)
                 VALUES ('{$type}', ?, ?, ?, 1, NOW(), 0)
                 ON DUPLICATE KEY UPDATE is_protected = 1";
        };

        /* 1. Service Pages — from DB using page_slug column (no integer fallback) */
        try {
            $rows = $this->db->fetchAll(
                "SELECT gp.id,
                        COALESCE(gp.page_slug,
                            IF(kw.keyword_slug IS NOT NULL AND sa.area_slug IS NOT NULL,
                               CONCAT(kw.keyword_slug,'-in-',sa.area_slug), NULL)) AS slug,
                        CONCAT(COALESCE(kw.keyword_name,'Service Page'),' in ',COALESCE(sa.area_name,'Area')) AS title
                 FROM generated_pages gp
                 LEFT JOIN seo_service_keywords kw ON gp.keyword_id = kw.id
                 LEFT JOIN service_areas        sa ON gp.area_id   = sa.id
                 WHERE gp.keyword_id > 0 AND gp.area_id > 0"
            ) ?: [];
            $stmt = $conn->prepare($upsert_sql('service_page'));
            foreach ($rows as $r) {
                $slug = (string)($r['slug'] ?? '');
                if (empty($slug)) continue; /* skip unresolvable — FS sync handles it */
                $title = $r['title'] ?? $slug;
                $path  = 'generated-pages/' . $slug . '.php';
                try { if ($stmt->execute([$slug, $title, $path])) $added++; } catch(\Exception $e) {}
            }
        } catch(\Exception $e) { error_log('[sync-svc-db] ' . $e->getMessage()); }

        /* 1b. Service Pages — filesystem fallback (same logic as view-generated-pages.php) */
        try {
            $kw_fs_map = [];
            foreach (($this->db->fetchAll(
                "SELECT keyword_slug, keyword_name FROM seo_service_keywords WHERE is_active=1"
            ) ?: []) as $_k) {
                if (!empty($_k['keyword_slug'])) $kw_fs_map[$_k['keyword_slug']] = $_k['keyword_name'];
            }
            $stmt_fs = $conn->prepare($upsert_sql('service_page'));
            foreach (glob($gen_dir . '*.php') ?: [] as $_f) {
                $fn = basename($_f, '.php');
                if ($fn === 'index') continue;
                $parts  = explode('-', $fn);
                $in_pos = array_search('in', $parts);
                if ($in_pos === false || $in_pos < 1) continue;
                $kslug = null;
                for ($klen = $in_pos; $klen >= 1; $klen--) {
                    $cand = implode('-', array_slice($parts, 0, $klen));
                    if (isset($kw_fs_map[$cand])) { $kslug = $cand; break; }
                }
                $area_name  = ucwords(str_replace('-', ' ', implode('-', array_slice($parts, $in_pos + 1))));
                $kw_name    = isset($kslug) ? $kw_fs_map[$kslug] : $fn;
                $title_fs   = $kw_name . ' in ' . $area_name;
                $path_fs    = 'generated-pages/' . basename($_f);
                try { if ($stmt_fs->execute([$fn, $title_fs, $path_fs])) $added++; } catch(\Exception $e) {}
            }
        } catch(\Exception $e) { error_log('[sync-svc-fs] ' . $e->getMessage()); }

        /* 2. Blogs — from JSON files */
        try {
            $skip  = ['index.json','stats.json'];
            $files = glob($data_dir . 'blogs/*.json') ?: [];
            $stmt2 = $conn->prepare($upsert_sql('blog'));
            foreach ($files as $f) {
                if (in_array(basename($f), $skip)) continue;
                $slug  = basename($f, '.json');
                $title = $slug;
                $data  = @json_decode(@file_get_contents($f), true);
                if ($data && isset($data['title'])) $title = $data['title'];
                try { if ($stmt2->execute([$slug, $title, 'data/blogs/' . basename($f)])) $added++; } catch(\Exception $e) {}
            }
        } catch(\Exception $e) { error_log('[sync-blog] ' . $e->getMessage()); }

        /* 3. Reviews — from JSON files */
        try {
            $skip  = ['index.json','stats.json'];
            $files = glob($data_dir . 'reviews/*.json') ?: [];
            $stmt3 = $conn->prepare($upsert_sql('review'));
            foreach ($files as $f) {
                if (in_array(basename($f), $skip)) continue;
                $slug  = basename($f, '.json');
                $title = $slug;
                $data  = @json_decode(@file_get_contents($f), true);
                if ($data) {
                    $name = $data['customer_name'] ?? ($data['name'] ?? '');
                    $cat  = $data['category'] ?? '';
                    if ($name) $title = $name . ($cat ? ' — ' . $cat : '');
                }
                try { if ($stmt3->execute([$slug, $title, 'data/reviews/' . basename($f)])) $added++; } catch(\Exception $e) {}
            }
        } catch(\Exception $e) { error_log('[sync-rev] ' . $e->getMessage()); }

        /* 4. FAQs — from JSON files */
        try {
            $skip  = ['index.json','stats.json'];
            $files = glob($data_dir . 'faqs/*.json') ?: [];
            $stmt4 = $conn->prepare($upsert_sql('faq'));
            foreach ($files as $f) {
                if (in_array(basename($f), $skip)) continue;
                $slug  = basename($f, '.json');
                $title = $slug;
                $data  = @json_decode(@file_get_contents($f), true);
                if ($data && isset($data['question'])) $title = $data['question'];
                try { if ($stmt4->execute([$slug, $title, 'data/faqs/' . basename($f)])) $added++; } catch(\Exception $e) {}
            }
        } catch(\Exception $e) { error_log('[sync-faq] ' . $e->getMessage()); }

        /* 5. Pillar pages — keyword slug PHP files in site root */
        try {
            $kw_rows = $this->db->fetchAll(
                "SELECT keyword_slug, keyword_name FROM seo_service_keywords WHERE is_active=1"
            ) ?: [];
            $stmt5 = $conn->prepare($upsert_sql('pillar_page'));
            foreach ($kw_rows as $kw) {
                $slug  = $kw['keyword_slug'];
                $title = $kw['keyword_name'];
                $path  = $slug . '.php';
                if (!file_exists($root_dir . '/' . $path)) continue;
                try { if ($stmt5->execute([$slug, $title, $path])) $added++; } catch(\Exception $e) {}
            }
        } catch(\Exception $e) { error_log('[sync-pillar] ' . $e->getMessage()); }

        return $added;
    }

    /* ──────────────────────────────────────────────────────
     * REAL COUNTS — same logic as content-export.php
     * ────────────────────────────────────────────────────── */

    public function getRealCounts() {
        $root_dir = dirname(dirname(__DIR__));
        $data_dir = $root_dir . '/data/';
        $gen_dir  = $root_dir . '/generated-pages/';
        $skip     = ['index.json','stats.json'];

        /* Service pages */
        $svc_db = 0; $svc_files = 0;
        try { $svc_db = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS c FROM generated_pages WHERE keyword_id>0 AND area_id>0"
        )['c'] ?? 0); } catch(\Exception $e) {}
        if (is_dir($gen_dir)) {
            foreach (glob($gen_dir . '*.php') ?: [] as $f) {
                if (basename($f) !== 'index.php') $svc_files++;
            }
        }
        $svc = max($svc_db, $svc_files);

        /* Blogs */
        $blogs = count(array_filter(glob($data_dir . 'blogs/*.json') ?: [],
            fn($f) => !in_array(basename($f), $skip)));

        /* Reviews */
        $reviews = count(array_filter(glob($data_dir . 'reviews/*.json') ?: [],
            fn($f) => !in_array(basename($f), $skip)));

        /* FAQs */
        $faqs = count(array_filter(glob($data_dir . 'faqs/*.json') ?: [],
            fn($f) => !in_array(basename($f), $skip)));

        /* Pillar pages */
        $pillars = 0;
        try {
            $kw_rows = $this->db->fetchAll(
                "SELECT keyword_slug FROM seo_service_keywords WHERE is_active=1"
            ) ?: [];
            $slugs = array_column($kw_rows, 'keyword_slug');
            foreach (glob($root_dir . '/*.php') ?: [] as $f) {
                if (in_array(basename($f, '.php'), $slugs)) $pillars++;
            }
        } catch(\Exception $e) {}

        /* Videos */
        $videos = count(array_filter(glob($data_dir . 'videos/*.json') ?: [],
            fn($f) => !in_array(basename($f), $skip)));

        return [
            'service_pages'  => $svc,
            'blogs'          => $blogs,
            'reviews'        => $reviews,
            'faqs'           => $faqs,
            'pillar_pages'   => $pillars,
            'videos'         => $videos,
            'total'          => $svc + $blogs + $reviews + $faqs + $pillars,
            'uploaded_total' => $videos,
        ];
    }

    /* ──────────────────────────────────────────────────────
     * COUNT PROTECTED RECORDS BY GROUP
     * ────────────────────────────────────────────────────── */
    public function getProtectedCountByGroup(): array {
        $ai_types       = "'service_page','blog','review','faq','pillar_page'";
        $upload_types   = "'video','image','logo'";
        $ai = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS c FROM ai_protected_content WHERE is_protected=1 AND content_type IN ($ai_types)"
        )['c'] ?? 0);
        $uploaded = (int)($this->db->fetchOne(
            "SELECT COUNT(*) AS c FROM ai_protected_content WHERE is_protected=1 AND content_type IN ($upload_types)"
        )['c'] ?? 0);
        return ['ai_generated' => $ai, 'uploaded' => $uploaded];
    }

    /* ──────────────────────────────────────────────────────
     * INTERNAL LOGGING
     * ────────────────────────────────────────────────────── */

    private function logAction($action, $content_id, $admin_id, $reason, $pin_correct,
                               $content_type = '', $content_title = '', $ip = '') {
        if (empty($ip)) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
        $this->db->execute(
            "INSERT INTO ai_deletion_log
             (content_type, content_id, content_title, action, admin_id, ip_address, reason, pin_correct)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$content_type, (string)$content_id, $content_title, $action, $admin_id, $ip, $reason, $pin_correct ? 1 : 0]
        );
    }
}
