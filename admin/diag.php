<?php
/**
 * TEMPORARY DIAGNOSTIC + CACHE CLEAR - DELETE AFTER USE
 * Visit: gcmsafetynets.in/admin/diag.php
 */
ob_start();

// ── Clear OPcache so updated PHP files take effect ──────────
$opcacheCleared = false;
if (function_exists('opcache_reset')) {
    opcache_reset();
    $opcacheCleared = true;
}
// Also invalidate specific files
$files = [
    __DIR__ . '/forgot-password.php',
    __DIR__ . '/forgot-username.php',
    __DIR__ . '/reset-password.php',
];
if (function_exists('opcache_invalidate')) {
    foreach ($files as $f) {
        opcache_invalidate($f, true);
    }
}

register_shutdown_function(function() {
    $err = error_get_last();
    // Force 200 so Litespeed shows our output instead of its 500 page
    http_response_code(200);
    $buf = ob_get_clean();
    echo $buf;
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        echo '<div style="background:#c0392b;color:#fff;padding:20px;font-family:monospace;font-size:14px;margin-top:10px">';
        echo '<b>FATAL ERROR</b><br>';
        echo 'Message: ' . htmlspecialchars($err['message']) . '<br>';
        echo 'File: ' . htmlspecialchars($err['file']) . '<br>';
        echo 'Line: ' . $err['line'];
        echo '</div>';
    } else {
        echo '<div style="background:#27ae60;color:#fff;padding:20px;font-family:monospace;font-size:14px;margin-top:10px"><b>ALL STEPS PASSED - No fatal errors</b></div>';
    }
});

echo '<pre style="font-family:monospace;font-size:13px;background:#1e1e1e;color:#00ff00;padding:20px">';
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "OPcache reset: " . ($opcacheCleared ? "YES - cache cleared!" : "NOT available") . "\n\n";

echo "Step 1: define GCM_INIT... ";
define('GCM_INIT', true);
echo "OK\n";

echo "Step 2: require config.php... ";
require_once '../config/config.php';
echo "OK\n";

echo "Step 3: require account-manager.php... ";
require_once 'includes/account-manager.php';
echo "OK\n";

echo "Step 4: new AccountManager()... ";
$am = new AccountManager();
echo "OK\n";

echo "Step 5: generateCSRF()... ";
$csrf = $am->generateCSRF('diag_test');
echo "OK (token=" . substr($csrf, 0, 8) . "...)\n";

echo "\n--- File verification ---\n";
foreach (['forgot-password.php', 'forgot-username.php'] as $fname) {
    $path = __DIR__ . '/' . $fname;
    if (!file_exists($path)) { echo "$fname: NOT FOUND on disk!\n"; continue; }
    $mtime   = date('Y-m-d H:i:s', filemtime($path));
    $content = file_get_contents($path);
    $hasOb   = strpos($content, 'ob_start') !== false;
    $lines   = substr_count($content, "\n");
    echo "$fname: modified=$mtime lines=$lines ob_start=" . ($hasOb ? "YES\n" : "NO (OLD VERSION!)\n");
    echo "  First 60 chars: " . json_encode(substr($content, 0, 60)) . "\n";
}

echo "\nDiagnostic PASSED. Pages should work.\n";
echo "</pre>";
