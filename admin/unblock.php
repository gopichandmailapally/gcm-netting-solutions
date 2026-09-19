<?php
/**
 * EMERGENCY IP UNBLOCK — DELETE THIS FILE AFTER USE
 * Visit: gcmsafetynets.in/admin/unblock.php?key=gcmunblock2024
 */
ob_start();
header('Content-Type: text/plain; charset=utf-8');

if (($_GET['key'] ?? '') !== 'gcmunblock2024') {
    die("Usage: /admin/unblock.php?key=gcmunblock2024");
}

define('GCM_INIT', true);
require_once '../config/config.php';

echo "=== EMERGENCY UNBLOCK ===\n\n";

/* Show ALL IP headers so we can see what login.php detects vs what we detect */
echo "--- IP DETECTION ---\n";
echo "REMOTE_ADDR             : " . ($_SERVER['REMOTE_ADDR']            ?? '-') . "\n";
echo "HTTP_X_FORWARDED_FOR    : " . ($_SERVER['HTTP_X_FORWARDED_FOR']   ?? '-') . "\n";
echo "HTTP_X_REAL_IP          : " . ($_SERVER['HTTP_X_REAL_IP']         ?? '-') . "\n";
echo "HTTP_CLIENT_IP          : " . ($_SERVER['HTTP_CLIENT_IP']         ?? '-') . "\n";
echo "AdminSecurity getClientIP would use: ";
/* Replicate AdminSecurity::getClientIP() exactly */
if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
    $detectedIP = trim($_SERVER['HTTP_CLIENT_IP']);
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $detectedIP = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
    $detectedIP = trim($_SERVER['HTTP_X_REAL_IP']);
} else {
    $detectedIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}
echo $detectedIP . "\n\n";

try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    /* Show ALL current rows in admin_blocked_ips BEFORE deleting */
    echo "--- CURRENT BLOCKED IPs (before clear) ---\n";
    $rows = $pdo->query("SELECT * FROM admin_blocked_ips")->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "(table is empty)\n";
    } else {
        foreach ($rows as $row) {
            echo "  ip={$row['ip_address']}  permanent={$row['permanent']}  blocked_until={$row['blocked_until']}  reason={$row['reason']}\n";
        }
    }
    echo "\n";

    /* DELETE ALL rows from admin_blocked_ips (no filter — clears permanent and temporary) */
    $deleted = $pdo->exec("DELETE FROM admin_blocked_ips");
    echo "admin_blocked_ips   : deleted ALL {$deleted} row(s)\n";

    /* Clear ALL login attempt history */
    $deleted2 = $pdo->exec("DELETE FROM admin_login_attempts");
    echo "admin_login_attempts: cleared all {$deleted2} row(s)\n";

    /* Clear SecurityProtection rate-limit files from /tmp */
    $tmpDir = sys_get_temp_dir();
    $files = glob($tmpDir . '/rate_limit_*.txt') ?: [];
    $cleared = 0;
    foreach ($files as $f) { if (@unlink($f)) $cleared++; }
    echo "rate_limit files    : cleared {$cleared} file(s) from {$tmpDir}\n";

    echo "\nSUCCESS: All blocks cleared.\n";
    echo "Now visit: https://gcmsafetynets.in/admin/login.php?bypass=gcmunblock2024\n";
    echo "(The bypass= parameter skips the block check for this one visit)\n";
    echo "\n*** DELETE THIS FILE (unblock.php) AFTER LOGGING IN ***\n";

} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
