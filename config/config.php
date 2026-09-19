<?php
/**
 * GCM Netting Solutions - Main Configuration File
 * All site-wide settings and constants
 */

// Prevent direct access
if (!defined('GCM_INIT')) {
    define('GCM_INIT', true);
}

// Enable Security Protection
require_once __DIR__ . '/../includes/security-protection.php';

// Auto-set admin session name for every admin page.
// Must be called before session_start(); session_name() is a no-op after the
// session has already been started, so this is safe to call unconditionally.
if (session_status() === PHP_SESSION_NONE) {
    $gcm_script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($gcm_script, '/admin/') !== false) {
        session_name('GCM_ADMIN_SESSION');
    }
    unset($gcm_script);
}

// Prevent direct access
if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

// Environment Configuration
$is_local = false;

define('ENV', $is_local ? 'development' : 'production');
define('DEBUG_MODE', $is_local); // Enable debug in local environment

// Database Configuration - PRODUCTION (Hostinger)
// Set GCM_DB_PASS, GCM_SMTP_PASS, GCM_GEMINI_KEY as server environment variables
// to avoid storing credentials in source code.
define('DB_HOST', 'localhost');
define('DB_USER', 'gcmsafetynets_user');
define('DB_PASS', 't856zxMjLey8bpU5');
define('DB_NAME', 'gcmnettingsolutions_db');
define('DB_CHARSET', 'utf8mb4');

// Site Configuration - Auto-detect URL
if (!empty($_SERVER['HTTP_HOST'])) {
    $protocol = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')) ? 'https' : 'http';
    define('SITE_URL', $protocol . '://' . $_SERVER['HTTP_HOST']);
} else {
    define('SITE_URL', 'https://www.gcmnettingsolutions.com');
}
define('SITE_NAME', 'GCM Netting Solutions');
define('SITE_TAGLINE', 'Chennai\'s Largest Netting Solutions Provider');
define('SITE_DESCRIPTION', 'Professional safety nets, pigeon nets, bird nets, invisible grills, sports nets, and cloth hangers installation in Chennai');

// Contact Information
define('COMPANY_NAME', 'GCM Netting Solutions');
define('COMPANY_PHONE', '9912399224');
define('COMPANY_WHATSAPP', '919912399224');
define('COMPANY_EMAIL', 'gcmsafetynets@gmail.com');
define('COMPANY_EMAIL_SECONDARY', 'contact@gcmnettingsolutions.com');
define('COMPANY_WEBSITE', 'gcmnettingsolutions.com');
define('COMPANY_ADDRESS', 'No. 42, Anna Salai, Mount Road, Chennai - 600002, Tamil Nadu, India');

// Admin Configuration
define('ADMIN_DIR', 'admin');
define('ADMIN_SESSION_TIMEOUT', 3600); // 1 hour
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 900); // 15 minutes

// Email Configuration (SMTP)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_ENCRYPTION', 'tls'); // 'tls' for STARTTLS/587, 'ssl' for SMTPS/465
define('SMTP_USERNAME', getenv('GCM_SMTP_USER') ?: 'gopichandmailapally@gmail.com');
define('SMTP_PASSWORD', getenv('GCM_SMTP_PASS') ?: 'sahllxpuznrluyfj');
define('SMTP_FROM_NAME', 'GCM Netting Solutions');
define('SMTP_FROM_EMAIL', 'gopichandmailapally@gmail.com');

// API Keys - ENTER YOUR REAL KEY IN API SETTINGS PAGE!
define('GEMINI_API_KEY', getenv('GCM_GEMINI_KEY') ?: 'AIzaSyDe_g6vocFcjHSACzqyZBP0ysd5HGfsfnQ');
define('GEMINI_MODEL', 'gemini-2.0-flash-001'); // Model to use (verified from API)
define('API_TIMEOUT', 30); // Timeout in seconds
define('API_RETRY_COUNT', 3); // Number of retries
define('MAX_TOKENS', 4096); // Maximum tokens per request
define('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-001:generateContent');
define('GOOGLE_RECAPTCHA_SITE_KEY', 'your-recaptcha-site-key');
define('GOOGLE_RECAPTCHA_SECRET_KEY', 'your-recaptcha-secret-key');

// File Upload Configuration
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif']);
define('ALLOWED_VIDEO_TYPES', ['video/mp4', 'video/webm', 'video/ogg']);
define('UPLOAD_DIR', dirname(__DIR__) . '/assets/img/uploads/');
define('GALLERY_DIR', UPLOAD_DIR . 'gallery/');
define('SERVICE_IMG_DIR', UPLOAD_DIR . 'services/');
define('BLOG_IMG_DIR', UPLOAD_DIR . 'blogs/');

// Page Generation Configuration
define('MIN_WORD_COUNT', 1200);
define('MAX_WORD_COUNT', 1600);
define('GENERATE_BATCH_SIZE', 50); // Number of pages to generate in one batch
define('GEMINI_REQUEST_DELAY', 1); // Seconds to wait between API calls (rate limiting)

// SEO Configuration
define('DEFAULT_META_TITLE_SUFFIX', ' | GCM Netting Solutions Chennai');
define('DEFAULT_META_KEYWORDS', 'safety nets, pigeon nets, bird nets, invisible grills, Chennai');
define('SITEMAP_PATH', dirname(__DIR__) . '/sitemap.xml');
define('ROBOTS_TXT_PATH', dirname(__DIR__) . '/robots.txt');

// Pagination
define('REVIEWS_PER_PAGE', 20);
define('BLOGS_PER_PAGE', 12);
define('GALLERY_PER_PAGE', 24);

// Cache Configuration
define('ENABLE_CACHE', true);
define('CACHE_DURATION', 3600); // 1 hour
define('CACHE_DIR', dirname(__DIR__) . '/cache/');

// Session Configuration
define('SESSION_NAME', 'GCM_SESSION');
define('SESSION_COOKIE_LIFETIME', 0); // Browser session
define('SESSION_COOKIE_PATH', '/');
define('SESSION_COOKIE_DOMAIN', '');
define('SESSION_COOKIE_SECURE', true); // HTTPS only
define('SESSION_COOKIE_HTTPONLY', true);
define('SESSION_COOKIE_SAMESITE', 'Strict');

// Pseudo-cron secret — authenticates visitor-triggered content auto-generation (no cron job needed)
define('PSEUDO_CRON_TOKEN', 'gcm-pcron-' . substr(md5(DB_PASS . DB_NAME . 'gcm2026'), 0, 18));

// Security
define('CSRF_TOKEN_NAME', 'gcm_csrf_token');
define('CSRF_TOKEN_TIME', 7200); // 2 hours
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_HASH_COST', 12);
define('ENABLE_HONEYPOT', true); // Anti-spam honeypot field

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error Reporting
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', dirname(__DIR__) . '/logs/php-error.log');
}

function gcm_should_inject_seo_head_tags() {
    if (PHP_SAPI === 'cli') return false;
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method !== 'GET' && $method !== 'HEAD') return false;
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($uri, '/admin/') === 0 || strpos($uri, '/api/') === 0) return false;
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    if (strpos($script, '/admin/') !== false || strpos($script, '/api/') !== false) return false;
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if ($accept && stripos($accept, 'text/html') === false && stripos($accept, '*/*') === false) return false;
    return true;
}

function gcm_build_canonical_url_from_request() {
    $req = $_SERVER['REQUEST_URI'] ?? '/';
    $req = explode('#', $req, 2)[0];
    $req = explode('?', $req, 2)[0];
    if ($req !== '/' && substr($req, -4) === '.php') {
        $req = substr($req, 0, -4);
    }
    return rtrim(SITE_URL, '/') . ($req ?: '/');
}

function gcm_inject_seo_head_tags($buffer) {
    if (!is_string($buffer) || $buffer === '') return $buffer;
    if (stripos($buffer, '</head>') === false) return $buffer;

    $inserts = '';

    if (stripos($buffer, 'rel="canonical"') === false && stripos($buffer, "rel='canonical'") === false) {
        $canon = gcm_build_canonical_url_from_request();
        $inserts .= "\n    <link rel=\"canonical\" href=\"" . htmlspecialchars($canon, ENT_QUOTES, 'UTF-8') . "\">";
    }

    if (stripos($buffer, 'name="robots"') === false && stripos($buffer, "name='robots'") === false) {
        if (stripos($buffer, 'noindex') === false) {
            $inserts .= "\n    <meta name=\"robots\" content=\"index,follow\">";
        }
    }

    if ($inserts === '') return $buffer;
    return preg_replace('/<\/head>/i', $inserts . "\n</head>", $buffer, 1);
}

if (gcm_should_inject_seo_head_tags() && !defined('GCM_DISABLE_SEO_HEAD_INJECT')) {
    ob_start('gcm_inject_seo_head_tags');
}

// Autoloader for classes (if using)
spl_autoload_register(function ($class) {
    $file = dirname(__DIR__) . '/classes/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Helper Functions
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function generate_csrf_token() {
    if (empty($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
        $_SESSION[CSRF_TOKEN_NAME . '_time'] = time();
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function verify_csrf_token($token) {
    if (empty($_SESSION[CSRF_TOKEN_NAME]) || empty($_SESSION[CSRF_TOKEN_NAME . '_time'])) {
        return false;
    }
    
    if (time() - $_SESSION[CSRF_TOKEN_NAME . '_time'] > CSRF_TOKEN_TIME) {
        unset($_SESSION[CSRF_TOKEN_NAME]);
        unset($_SESSION[CSRF_TOKEN_NAME . '_time']);
        return false;
    }
    
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

function create_slug($string) {
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    $string = trim($string, '-');
    return $string;
}

function generate_meta_title($keyword, $area) {
    return ucwords($keyword) . ' in ' . $area . ', Chennai' . DEFAULT_META_TITLE_SUFFIX;
}

function generate_meta_description($keyword, $area) {
    return 'Professional ' . strtolower($keyword) . ' installation in ' . $area . ', Chennai. Quality materials, expert installation, affordable prices. Call ' . COMPANY_PHONE . ' for free quote.';
}

function log_activity($user_id, $action, $details = '') {
    try {
        $db = Database::getInstance();
        $ip = get_client_ip();
        $db->execute(
            "INSERT INTO admin_security_logs (admin_id, action, details, ip_address) VALUES (?, ?, ?, ?)",
            [$user_id, $action, $details, $ip]
        );
    } catch (Exception $e) {
        error_log('log_activity error: ' . $e->getMessage());
    }
}

function send_email($to, $subject, $body, $reply_to_email = '', $reply_to_name = '') {
    $autoload_path = dirname(__DIR__) . '/vendor/autoload.php';

    if (file_exists($autoload_path)) {
        require_once $autoload_path;
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = str_replace(' ', '', SMTP_PASSWORD);
            $mail->SMTPSecure = SMTP_ENCRYPTION;
            $mail->Port       = SMTP_PORT;
            $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            $mail->addAddress($to);
            if ($reply_to_email) {
                $mail->addReplyTo($reply_to_email, $reply_to_name ?: $reply_to_email);
            }
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->AltBody = strip_tags($body);
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[Email/PHPMailer] ' . $mail->ErrorInfo);
            return false;
        }
    }

    /* ── Native SMTP (no PHPMailer) — try 587 then 465 ──── */
    $_sp = str_replace(' ', '', SMTP_PASSWORD);
    // Port 587 (STARTTLS) is allowed by most shared hosts; try it first
    $smtpOk = _smtp_send_native($to, $subject, $body, SMTP_FROM_EMAIL, SMTP_FROM_NAME, SMTP_HOST, 587, SMTP_USERNAME, $_sp, $reply_to_email);
    if (!$smtpOk) {
        error_log('[Email] Port 587 failed, retrying on port 465 (SSL)...');
        $smtpOk = _smtp_send_native($to, $subject, $body, SMTP_FROM_EMAIL, SMTP_FROM_NAME, SMTP_HOST, 465, SMTP_USERNAME, $_sp, $reply_to_email);
    }
    if ($smtpOk) return true;

    /* ── PHP mail() fallback (Hostinger MTA) ────────────── */
    error_log('[Email] SMTP failed, trying php mail() fallback for: ' . $to);
    // Use the hosted domain address — Hostinger's server is SPF-authorized for gcmnettingsolutions.com,
    // NOT for gcmsafetynets@gmail.com. Gmail From causes SPF rejection on shared hosting.
    $mf_email = 'noreply@gcmnettingsolutions.com';
    $mf_name  = SMTP_FROM_NAME;
    $reply_to_label = $reply_to_email ? ($reply_to_name ?: $reply_to_email) : SMTP_FROM_NAME;
    $reply_to_addr  = $reply_to_email ?: SMTP_FROM_EMAIL;
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=UTF-8' . "\r\n";
    $headers .= 'From: '     . $mf_name . ' <' . $mf_email . ">\r\n";
    $headers .= 'Reply-To: ' . $reply_to_label . ' <' . $reply_to_addr . ">\r\n";
    $headers .= 'X-Mailer: PHP/' . PHP_VERSION . "\r\n";
    $mailOk = @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers, '-f' . $mf_email);
    if (!$mailOk) {
        error_log('[Email] php mail() also failed for: ' . $to);
    }
    return $mailOk;
}

function _smtp_read($socket): string {
    $out = '';
    while ($line = fgets($socket, 512)) {
        $out .= $line;
        // SMTP multi-line: last line has a space (not '-') after the 3-digit code
        if (strlen($line) < 4 || $line[3] !== '-') break;
    }
    return $out;
}

function _smtp_ok($socket, string $expect): bool {
    $resp = _smtp_read($socket);
    if (substr($resp, 0, 3) !== $expect) {
        error_log('[SMTP] Expected ' . $expect . ', got: ' . trim($resp));
        return false;
    }
    return true;
}

function _smtp_send_native(string $to, string $subject, string $htmlBody,
    string $fromEmail, string $fromName, string $host, int $port,
    string $username, string $password, string $replyTo = ''): bool {
    try {
        $errno = $errstr = '';
        // Port 465 = SMTPS (direct SSL); Port 587 = STARTTLS (plain then upgrade)
        if ($port === 465) {
            $ctx    = stream_context_create(['ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]]);
            $socket = @stream_socket_client(
                'ssl://' . $host . ':' . $port,
                $errno, $errstr, 15,
                STREAM_CLIENT_CONNECT,
                $ctx
            );
        } else {
            $socket = @fsockopen($host, $port, $errno, $errstr, 15);
        }
        if (!$socket) {
            error_log("[SMTP] Connect failed ({$errno}): {$errstr}");
            return false;
        }
        stream_set_timeout($socket, 15);

        if (!_smtp_ok($socket, '220')) { fclose($socket); return false; }

        fwrite($socket, "EHLO gcmsafetynets.in\r\n");
        if (!_smtp_ok($socket, '250')) { fclose($socket); return false; }

        // STARTTLS only needed for port 587
        if ($port !== 465) {
            fwrite($socket, "STARTTLS\r\n");
            if (!_smtp_ok($socket, '220')) { fclose($socket); return false; }

            $ctx = stream_context_create(['ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]]);
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT)) {
                error_log('[SMTP] TLS upgrade failed');
                fclose($socket);
                return false;
            }

            fwrite($socket, "EHLO gcmsafetynets.in\r\n");
            if (!_smtp_ok($socket, '250')) { fclose($socket); return false; }
        }

        fwrite($socket, "AUTH LOGIN\r\n");
        if (!_smtp_ok($socket, '334')) { fclose($socket); return false; }
        fwrite($socket, base64_encode($username) . "\r\n");
        if (!_smtp_ok($socket, '334')) { fclose($socket); return false; }
        fwrite($socket, base64_encode($password) . "\r\n");
        if (!_smtp_ok($socket, '235')) {
            error_log('[SMTP] Auth failed — check Gmail App Password');
            fclose($socket);
            return false;
        }

        fwrite($socket, "MAIL FROM:<{$fromEmail}>\r\n");
        if (!_smtp_ok($socket, '250')) { fclose($socket); return false; }

        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        if (!_smtp_ok($socket, '250')) { fclose($socket); return false; }

        fwrite($socket, "DATA\r\n");
        if (!_smtp_ok($socket, '354')) { fclose($socket); return false; }

        $enc  = '=?UTF-8?B?' . base64_encode($subject)  . '?=';
        $from = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $msg  = "From: {$from} <{$fromEmail}>\r\n"
              . "To: {$to}\r\n"
              . "Subject: {$enc}\r\n"
              . "MIME-Version: 1.0\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "Content-Transfer-Encoding: base64\r\n";
        if ($replyTo) $msg .= "Reply-To: {$replyTo}\r\n";
        $msg .= "\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.\r\n";

        fwrite($socket, $msg);
        if (!_smtp_ok($socket, '250')) { fclose($socket); return false; }

        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        return true;
    } catch (\Throwable $e) {
        error_log('[SMTP] Exception: ' . $e->getMessage());
        return false;
    }
}

function get_client_ip() {
    // REMOTE_ADDR is the only non-spoofable IP value.
    // HTTP_CLIENT_IP and HTTP_X_FORWARDED_FOR can be set by any client.
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'Unknown';
}

// Initialize session with secure settings.
// Admin pages get GCM_ADMIN_SESSION; frontend pages get GCM_SESSION (SESSION_NAME).
// By starting the session here, all individual admin page session_start() calls become no-ops.
$_gcm_in_admin = (
    strpos($_SERVER['SCRIPT_FILENAME'] ?? '', DIRECTORY_SEPARATOR . 'admin' . DIRECTORY_SEPARATOR) !== false ||
    strpos($_SERVER['PHP_SELF'] ?? '', '/admin/') !== false
);
if (session_status() === PHP_SESSION_NONE) {
    if ($_gcm_in_admin) {
        ini_set('session.cookie_path', '/');
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_samesite', 'Lax');
        session_name('GCM_ADMIN_SESSION');
    } else {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => SESSION_COOKIE_LIFETIME,
            'path'     => SESSION_COOKIE_PATH,
            'domain'   => SESSION_COOKIE_DOMAIN,
            'secure'   => SESSION_COOKIE_SECURE,
            'httponly' => SESSION_COOKIE_HTTPONLY,
            'samesite' => SESSION_COOKIE_SAMESITE,
        ]);
    }
    session_start();
} elseif ($_gcm_in_admin && session_name() !== 'GCM_ADMIN_SESSION') {
    // An API file called session_start() early with the wrong name (e.g. PHPSESSID).
    // Close that session and reopen under the correct GCM_ADMIN_SESSION name so
    // $_SESSION['admin_logged_in'] is readable.
    session_write_close();
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
unset($_gcm_in_admin);

// Run security protection AFTER session is properly started
if (class_exists('SecurityProtection')) {
    SecurityProtection::init();
}

// Regenerate session ID periodically
if (!isset($_SESSION['created'])) {
    $_SESSION['created'] = time();
} elseif (time() - $_SESSION['created'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['created'] = time();
}

// ── Auto-create generated-pages directory structure (runs once per boot) ──
// This means generated-pages/ does NOT need to be in the deploy ZIP.
// All service pages and pillar pages on Hostinger survive redeployment.
(function() {
    $gp = __DIR__ . '/../generated-pages';
    if (!is_dir($gp)) { @mkdir($gp, 0755, true); }

    $idx = $gp . '/index.php';
    if (!file_exists($idx)) {
        @file_put_contents($idx,
            "<?php\nhttp_response_code(403);\nheader('Location: ../index.php');\nexit;\n");
    }

    $htc = $gp . '/.htaccess';
    if (!file_exists($htc)) {
        @file_put_contents($htc,
            "Options -Indexes\n" .
            "<FilesMatch \"\\.php\$\">\n    Order Allow,Deny\n    Allow from all\n</FilesMatch>\n" .
            "<Limit DELETE>\n    Order Allow,Deny\n    Deny from all\n</Limit>\n");
    }

    // ── Auto-create /uploads/ with security .htaccess ────────────────
    $up = __DIR__ . '/../uploads';
    if (!is_dir($up)) { @mkdir($up, 0755, true); }
    $up_htaccess = $up . '/.htaccess';
    if (!file_exists($up_htaccess)) {
        @file_put_contents($up_htaccess,
            "# Block all script execution\n" .
            "<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|cgi|sh|py)$\">\n" .
            "    Order allow,deny\n    Deny from all\n</FilesMatch>\n" .
            "Options -Indexes\n");
    }

    // ── Auto-create data/reviews and data/reviews/pending ────────────
    // Reviews are stored as JSON files; these directories must exist.
    $rv = __DIR__ . '/../data/reviews';
    $pv = $rv . '/pending';
    foreach ([$rv, $pv] as $d) {
        if (!is_dir($d)) @mkdir($d, 0755, true);
        if (!file_exists($d . '/index.php')) {
            @file_put_contents($d . '/index.php', "<?php header('Location: /'); exit;");
        }
    }
})();

// ── Visitor-triggered auto content generation (no system cron) ──
// Runs rarely and is protected by a lock inside api/pseudo-cron.php
if (rand(1, 25) === 1) {
    if (function_exists('fastcgi_finish_request')) @fastcgi_finish_request();
    define('PCRON_INCLUDED', 1);
    @include_once __DIR__ . '/../api/pseudo-cron.php';
    if (function_exists('_pcron_check')) { @_pcron_check(); }
}
