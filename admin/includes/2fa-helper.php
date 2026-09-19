<?php
/**
 * 2FA Helper — shared functions for 2-step verification
 */
if (!defined('GCM_INIT')) die('Direct access not permitted');

// ── Settings ───────────────────────────────────────────────────────
function gcm_2fa_settings(): array {
    $f = dirname(dirname(__DIR__)) . '/data/2fa-settings.json';
    $defaults = [
        'enabled'              => false,
        'email_otp'            => true,
        'admin_email'          => 'gopichandmailapally@gmail.com',
        'otp_expiry_minutes'   => 10,
        'trusted_device_days'  => 30,
        'smtp_host'            => 'smtp.hostinger.com',
        'smtp_port'            => 587,
        'smtp_encryption'      => 'tls',
        'smtp_username'        => '',
        'smtp_password'        => '',
        'smtp_from_name'       => 'GCM Netting Solutions Admin',
    ];
    if (!file_exists($f)) return $defaults;
    return array_merge($defaults, json_decode(file_get_contents($f), true) ?: []);
}

function gcm_save_2fa_settings(array $s): bool {
    $f = dirname(dirname(__DIR__)) . '/data/2fa-settings.json';
    return file_put_contents($f, json_encode($s, JSON_PRETTY_PRINT)) !== false;
}

// ── DB tables (auto-create) ────────────────────────────────────────
function gcm_2fa_init_tables($pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_otp_codes (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        otp_type    VARCHAR(10)  NOT NULL,
        otp_code    VARCHAR(6)   NOT NULL,
        session_token VARCHAR(64) NOT NULL,
        is_used     TINYINT(1)   DEFAULT 0,
        expires_at  DATETIME     NOT NULL,
        created_at  DATETIME     DEFAULT CURRENT_TIMESTAMP,
        KEY idx_st (session_token, otp_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS admin_trusted_devices (
        id           INT AUTO_INCREMENT PRIMARY KEY,
        device_token VARCHAR(64)  NOT NULL,
        device_name  VARCHAR(255),
        browser      VARCHAR(100),
        ip_address   VARCHAR(45),
        last_used    DATETIME     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        trusted_at   DATETIME     DEFAULT CURRENT_TIMESTAMP,
        expires_at   DATETIME     NOT NULL,
        UNIQUE KEY idx_tok (device_token),
        KEY idx_exp (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

// ── OTP helpers ────────────────────────────────────────────────────
function gcm_generate_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function gcm_store_otp($pdo, string $session_token, string $type, string $otp, int $expiry_min): void {
    $pdo->prepare("UPDATE admin_otp_codes SET is_used=1 WHERE session_token=? AND otp_type=? AND is_used=0")
        ->execute([$session_token, $type]);
    $pdo->prepare("INSERT INTO admin_otp_codes (otp_type,otp_code,session_token,expires_at) VALUES (?,?,?,DATE_ADD(NOW(),INTERVAL ? MINUTE))")
        ->execute([$type, $otp, $session_token, $expiry_min]);
}

function gcm_verify_otp($pdo, string $session_token, string $type, string $entered): bool {
    $st = $pdo->prepare("SELECT id FROM admin_otp_codes WHERE session_token=? AND otp_type=? AND otp_code=? AND is_used=0 AND expires_at>NOW() ORDER BY id DESC LIMIT 1");
    $st->execute([$session_token, $type, trim($entered)]);
    $row = $st->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $pdo->prepare("UPDATE admin_otp_codes SET is_used=1 WHERE id=?")->execute([$row['id']]);
        return true;
    }
    return false;
}

// ── SMTP mailer (no library needed) ───────────────────────────────
function gcm_smtp_send(string $to, string $subject, string $body, array $s): bool {
    $host  = $s['host'];
    $port  = (int)($s['port'] ?? 587);
    $enc   = strtolower($s['encryption'] ?? 'tls');
    $user  = $s['username'];
    $pass  = $s['password'];
    $from  = $s['username'];
    $fname = $s['from_name'] ?? 'GCM Admin';
    try {
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        if ($enc === 'ssl') {
            $sock = @stream_socket_client("ssl://{$host}:{$port}", $errno, $errstr, 20, STREAM_CLIENT_CONNECT, $ctx);
        } else {
            $sock = @stream_socket_client("tcp://{$host}:{$port}", $errno, $errstr, 20);
        }
        if (!$sock) return false;
        stream_set_timeout($sock, 20);
        $r = function() use ($sock) { return fgets($sock, 1024); };
        $w = function(string $cmd) use ($sock) { fwrite($sock, $cmd . "\r\n"); };
        $r(); // greeting
        $ehlo = gethostname() ?: 'localhost';
        $w("EHLO {$ehlo}");
        while ($line = $r()) { if (substr($line,3,1) === ' ') break; }
        if ($enc === 'tls') {
            $w('STARTTLS');
            $r();
            stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $w("EHLO {$ehlo}");
            while ($line = $r()) { if (substr($line,3,1) === ' ') break; }
        }
        $w('AUTH LOGIN');     $r();
        $w(base64_encode($user)); $r();
        $w(base64_encode($pass));
        $auth = $r();
        if (substr($auth,0,3) !== '235') { fclose($sock); return false; }
        $w("MAIL FROM:<{$from}>"); $r();
        $w("RCPT TO:<{$to}>");    $r();
        $w('DATA');              $r();
        $date    = date('r');
        $domain  = substr(strrchr($from, '@'), 1) ?: 'gcmsafetynets.in';
        $msg_id  = !empty($s['message_id']) ? $s['message_id'] : uniqid('otp', true);
        $msg  = "Date: {$date}\r\n"
              . "Message-ID: <{$msg_id}@{$domain}>\r\n"
              . "From: {$fname} <{$from}>\r\n"
              . "To: {$to}\r\n"
              . "Subject: {$subject}\r\n"
              . "MIME-Version: 1.0\r\n"
              . "Content-Type: text/html; charset=UTF-8\r\n"
              . "\r\n"
              . $body
              . "\r\n.";
        $w($msg);
        $resp = $r();
        $w('QUIT'); $r(); fclose($sock);
        return substr($resp,0,3) === '250';
    } catch (\Throwable $e) { return false; }
}

// ── Send OTPs ──────────────────────────────────────────────────────
function gcm_send_email_otp(string $to, string $otp, int $expiry_min): bool {
    $cfg     = gcm_2fa_settings();
    $unique  = uniqid('otp', true);
    $subject = "GCM Admin OTP — " . date('d M H:i:s');
    $body    = "<!DOCTYPE html><html><body style='font-family:Arial,sans-serif;background:#f1f5f9;padding:40px 0;margin:0;'>"
             . "<div style='max-width:460px;margin:0 auto;background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.12);'>"
             . "<div style='background:linear-gradient(135deg,#667eea,#764ba2);padding:32px 28px;text-align:center;'>"
             . "<div style='font-size:44px;'>&#x1F510;</div>"
             . "<h2 style='color:#fff;margin:10px 0 4px;font-size:22px;'>Admin Login OTP</h2>"
             . "<p style='color:#c4b5fd;font-size:13px;margin:0;'>GCM Netting Solutions Admin Panel</p>"
             . "</div>"
             . "<div style='padding:36px 32px;text-align:center;'>"
             . "<p style='color:#475569;font-size:15px;margin:0 0 20px;'>Your one-time password is:</p>"
             . "<div style='background:#f8fafc;border:2.5px dashed #6366f1;border-radius:14px;padding:22px;margin:0 0 22px;'>"
             . "<div style='font-size:44px;font-weight:900;letter-spacing:14px;color:#1e293b;font-family:monospace;'>{$otp}</div>"
             . "</div>"
             . "<p style='color:#94a3b8;font-size:13px;'>Expires in <strong>{$expiry_min} minutes</strong>. Never share this OTP.</p>"
             . "</div></div></body></html>";
    // Try SMTP first if configured
    if (!empty($cfg['smtp_username']) && !empty($cfg['smtp_password'])) {
        return gcm_smtp_send($to, $subject, $body, [
            'message_id' => $unique,
            'host'       => $cfg['smtp_host']       ?? 'smtp.hostinger.com',
            'port'       => $cfg['smtp_port']       ?? 587,
            'encryption' => $cfg['smtp_encryption'] ?? 'tls',
            'username'   => $cfg['smtp_username'],
            'password'   => $cfg['smtp_password'],
            'from_name'  => $cfg['smtp_from_name']  ?? 'GCM Netting Solutions Admin',
        ]);
    }
    // Fallback: PHP mail()
    $from_addr = !empty($cfg['smtp_username']) ? $cfg['smtp_username'] : 'admin@gcmsafetynets.in';
    $domain    = substr(strrchr($from_addr, '@'), 1) ?: 'gcmsafetynets.in';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: GCM Netting Solutions Admin <{$from_addr}>\r\n";
    $headers .= "Reply-To: {$from_addr}\r\n";
    $headers .= "Return-Path: {$from_addr}\r\n";
    $headers .= "Message-ID: <{$unique}@{$domain}>\r\n";
    $headers .= "X-OTP-Ref: " . time() . "\r\n";
    return (bool)@mail($to, $subject, $body, $headers, "-f{$from_addr}");
}

// ── Send OTP and store it ─────────────────────────────────────────
function gcm_dispatch_otps($pdo, string $session_token, array $cfg): array {
    $expiry  = (int)($cfg['otp_expiry_minutes'] ?? 10);
    $results = [];
    if (!empty($cfg['email_otp'])) {
        $otp = gcm_generate_otp();
        gcm_store_otp($pdo, $session_token, 'email', $otp, $expiry);
        $results['email'] = gcm_send_email_otp($cfg['admin_email'], $otp, $expiry);
    }
    return $results;
}

// ── Trusted device ─────────────────────────────────────────────────
function gcm_is_trusted_device($pdo): bool {
    $token = $_COOKIE['gcm_td'] ?? '';
    if (strlen($token) < 32) return false;
    $st = $pdo->prepare("SELECT id FROM admin_trusted_devices WHERE device_token=? AND expires_at>NOW() LIMIT 1");
    $st->execute([$token]);
    if ($st->fetch(PDO::FETCH_ASSOC)) {
        $pdo->prepare("UPDATE admin_trusted_devices SET last_used=NOW() WHERE device_token=?")->execute([$token]);
        return true;
    }
    return false;
}

function gcm_create_trusted_device($pdo, int $days): void {
    $token = bin2hex(random_bytes(32));
    $ua    = $_SERVER['HTTP_USER_AGENT'] ?? '';
    // Detect browser
    $browser = 'Unknown';
    if (stripos($ua,'Edg/') !== false)       $browser = 'Edge';
    elseif (stripos($ua,'Chrome') !== false)  $browser = 'Chrome';
    elseif (stripos($ua,'Firefox') !== false) $browser = 'Firefox';
    elseif (stripos($ua,'Safari') !== false)  $browser = 'Safari';
    // Detect device
    $dev = 'PC';
    if (stripos($ua,'iPhone') !== false || stripos($ua,'iPad') !== false) $dev = 'iOS';
    elseif (stripos($ua,'Android') !== false) $dev = 'Android';
    elseif (stripos($ua,'Macintosh') !== false) $dev = 'Mac';
    elseif (stripos($ua,'Windows') !== false)  $dev = 'Windows';
    $device_name = "{$dev} — {$browser}";
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
    $pdo->prepare("INSERT INTO admin_trusted_devices (device_token,device_name,browser,ip_address,expires_at) VALUES (?,?,?,?,DATE_ADD(NOW(),INTERVAL ? DAY))")
        ->execute([$token, $device_name, $browser, $ip, $days]);
    setcookie('gcm_td', $token, time() + ($days * 86400), '/', '', true, true);
}
