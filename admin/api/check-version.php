<?php
/**
 * VERSION CHECKER
 * This will tell you if you uploaded the correct API files
 */

header('Content-Type: text/html; charset=utf-8');

echo '<html><head><title>API Version Check</title>';
echo '<style>body{font-family:monospace;padding:20px;background:#f5f5f5;} .box{background:white;padding:20px;margin:10px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);} .good{color:#10b981;font-weight:bold;} .bad{color:#ef4444;font-weight:bold;}</style>';
echo '</head><body>';

echo '<h1>🔍 API Files Version Check</h1>';

// Check generate-sitemap-simple.php
echo '<div class="box">';
echo '<h2>1. generate-sitemap-simple.php</h2>';
$sitemap_file = __DIR__ . '/generate-sitemap-simple.php';
if (file_exists($sitemap_file)) {
    $content = file_get_contents($sitemap_file);
    
    // Check for the fix
    if (strpos($content, "session_name('GCM_ADMIN_SESSION')") !== false) {
        echo '<p class="good">✅ FIXED VERSION (has session_name fix)</p>';
        echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($sitemap_file)) . '</p>';
    } else {
        echo '<p class="bad">❌ OLD VERSION (missing session_name fix)</p>';
        echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($sitemap_file)) . '</p>';
        echo '<p class="bad">You need to UPLOAD the fixed version!</p>';
    }
    
    // Show session code
    echo '<details><summary>Click to see session code</summary><pre>';
    preg_match('/\/\/ Start session.*?}/s', $content, $matches);
    echo htmlspecialchars($matches[0] ?? 'Not found');
    echo '</pre></details>';
} else {
    echo '<p class="bad">❌ FILE NOT FOUND!</p>';
}
echo '</div>';

// Check seo-auto-scanner.php
echo '<div class="box">';
echo '<h2>2. seo-auto-scanner.php</h2>';
$scanner_file = __DIR__ . '/seo-auto-scanner.php';
if (file_exists($scanner_file)) {
    $content = file_get_contents($scanner_file);
    
    if (strpos($content, "session_name('GCM_ADMIN_SESSION')") !== false) {
        echo '<p class="good">✅ FIXED VERSION (has session_name fix)</p>';
        echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($scanner_file)) . '</p>';
    } else {
        echo '<p class="bad">❌ OLD VERSION (missing session_name fix)</p>';
        echo '<p>Last modified: ' . date('Y-m-d H:i:s', filemtime($scanner_file)) . '</p>';
        echo '<p class="bad">You need to UPLOAD the fixed version!</p>';
    }
    
    echo '<details><summary>Click to see session code</summary><pre>';
    preg_match('/\/\/ Start session.*?}/s', $content, $matches);
    echo htmlspecialchars($matches[0] ?? 'Not found');
    echo '</pre></details>';
} else {
    echo '<p class="bad">❌ FILE NOT FOUND!</p>';
}
echo '</div>';

echo '<div class="box">';
echo '<h2>3. What To Do</h2>';
echo '<p>If you see ❌ OLD VERSION above:</p>';
echo '<ol>';
echo '<li>Go to your FTP/cPanel</li>';
echo '<li>Navigate to <code>/public_html/admin/api/</code></li>';
echo '<li>Upload the fixed files from your local computer</li>';
echo '<li><strong>Make sure to OVERWRITE the existing files</strong></li>';
echo '<li>Come back here and refresh to verify</li>';
echo '</ol>';
echo '<p>If you see ✅ FIXED VERSION for both files, then the problem is something else!</p>';
echo '</div>';

echo '<hr><p><a href="../dashboard.php">← Back to Dashboard</a> | <a href="javascript:location.reload()">🔄 Refresh Check</a></p>';

echo '</body></html>';
