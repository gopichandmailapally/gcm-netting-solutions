<?php
/**
 * DIAGNOSTIC FILE — Delete after use
 * Upload to server and visit: /admin/pages/test-blogs-debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo '<pre style="font-family:monospace;font-size:13px;padding:20px;">';
echo "=== MANAGE-BLOGS DIAGNOSTIC ===\n\n";

// 1. PHP Info
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Execution: " . ini_get('max_execution_time') . "s\n\n";

// 2. Test config.php load
echo "--- STEP 1: Loading config.php ---\n";
try {
    define('GCM_INIT', true);
    require_once '../../config/config.php';
    echo "config.php: OK\n";
    echo "SITE_URL: " . SITE_URL . "\n";
    echo "Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'ACTIVE' : 'NOT active') . "\n";
    echo "Session name: " . session_name() . "\n\n";
} catch (Throwable $e) {
    echo "config.php ERROR: " . $e->getMessage() . " (line " . $e->getLine() . " in " . $e->getFile() . ")\n";
    echo '</pre>';
    exit;
}

// 3. Test session/login
echo "--- STEP 2: Session / Login check ---\n";
echo "_SESSION keys: " . implode(', ', array_keys($_SESSION ?: [])) . "\n";
echo "admin_logged_in: " . (isset($_SESSION['admin_logged_in']) ? var_export($_SESSION['admin_logged_in'], true) : 'NOT SET') . "\n\n";

// 4. Test blogs directory
echo "--- STEP 3: Blogs directory ---\n";
$blogs_dir = dirname(dirname(__DIR__)) . '/data/blogs';
echo "Blogs dir path: $blogs_dir\n";
echo "Exists: " . (file_exists($blogs_dir) ? 'YES' : 'NO') . "\n";
echo "Is dir: " . (is_dir($blogs_dir) ? 'YES' : 'NO') . "\n";
echo "Readable: " . (is_readable($blogs_dir) ? 'YES' : 'NO') . "\n";

if (is_dir($blogs_dir)) {
    $files = glob($blogs_dir . '/*.json');
    echo "glob() result type: " . gettype($files) . "\n";
    if ($files === false) {
        echo "glob() returned FALSE (permission issue!)\n";
    } else {
        echo "JSON files found: " . count($files) . "\n";
        $total_size = 0;
        foreach ($files as $f) $total_size += filesize($f);
        echo "Total size: " . round($total_size / 1024, 1) . " KB\n";
        if (count($files) > 0) {
            echo "Sample file: " . basename($files[0]) . " (" . round(filesize($files[0])/1024,1) . " KB)\n";
        }
    }
}

echo "\n";

// 5. Test header.php load
echo "--- STEP 4: Test header include ---\n";
$header_file = dirname(__DIR__) . '/includes/header.php';
echo "Header path: $header_file\n";
echo "Header exists: " . (file_exists($header_file) ? 'YES' : 'NO') . "\n\n";

// 6. Test reading blog files
echo "--- STEP 5: Reading blog files (memory test) ---\n";
$mem_before = memory_get_usage(true);
$blogs = [];
if (is_dir($blogs_dir)) {
    $files = glob($blogs_dir . '/*.json') ?: [];
    foreach ($files as $file) {
        $bname = basename($file);
        if ($bname === 'index.json' || $bname === 'stats.json') continue;
        $raw = file_get_contents($file);
        if (!$raw) continue;
        $blog_data = json_decode($raw, true);
        if ($blog_data && isset($blog_data['title'])) {
            $blogs[] = [
                'file_name'  => $bname,
                'title'      => $blog_data['title'] ?? '',
                'author'     => $blog_data['author'] ?? 'GCM Netting Solutions',
                'excerpt'    => $blog_data['excerpt'] ?? '',
                'created_at' => $blog_data['created_at'] ?? '',
            ];
        }
    }
}
$mem_after = memory_get_usage(true);
echo "Blogs loaded: " . count($blogs) . "\n";
echo "Memory used for loading: " . round(($mem_after - $mem_before) / 1024 / 1024, 2) . " MB\n";
echo "Total memory usage: " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
echo "Memory limit: " . ini_get('memory_limit') . "\n\n";

// 7. Test pagination
echo "--- STEP 6: Pagination math ---\n";
$per_page    = 25;
$total_blogs = count($blogs);
$total_pages = max(1, (int)ceil($total_blogs / $per_page));
$paged       = array_slice($blogs, 0, $per_page);
echo "Total blogs: $total_blogs\n";
echo "Per page: $per_page\n";
echo "Total pages: $total_pages\n";
echo "First page count: " . count($paged) . "\n\n";

echo "=== ALL PRE-RENDER TESTS PASSED ===\n\n";

// 7. Test date() on actual blog created_at values (PHP 8.3 type-safety check)
echo "--- STEP 7: date() call test on real blog data ---\n";
$date_errors = 0;
$empty_dates = 0;
foreach ($blogs as $i => $blog) {
    $ts = @strtotime($blog['created_at'] ?? '');
    if ($ts === false) {
        $date_errors++;
        if ($date_errors <= 3) {
            echo "  Bad created_at in blog #{$i}: '" . $blog['created_at'] . "' (file: {$blog['file_name']})\n";
        }
    }
    if (empty($blog['created_at'])) {
        $empty_dates++;
    }
}
echo "Blogs with bad/unparseable created_at: $date_errors\n";
echo "Blogs with empty created_at: $empty_dates\n";
echo "Testing date() on strtotime(false): ";
try {
    $bad_ts = @strtotime('');
    $result = @date('M j, Y', $bad_ts ?: 0);
    echo "OK → '$result'\n";
} catch (Throwable $e) {
    echo "FATAL: " . $e->getMessage() . "\n";
}
echo "\n";

// 8. Read PHP error log (last 40 lines)
echo "--- STEP 8: PHP Error Log (last 40 lines) ---\n";
$log_file = dirname(dirname(__DIR__)) . '/logs/php-error.log';
if (file_exists($log_file)) {
    $lines = file($log_file);
    $last = array_slice($lines, -40);
    foreach ($last as $line) {
        echo htmlspecialchars(trim($line)) . "\n";
    }
} else {
    echo "Error log not found at: $log_file\n";
}
echo "\n";

// 9. Test actual header.php include with output buffering
echo "--- STEP 9: header.php include test ---\n";
$page_title = 'Test';
ob_start();
$header_error = null;
try {
    include dirname(__DIR__) . '/includes/header.php';
    $header_out = ob_get_clean();
    echo "header.php: OK (" . strlen($header_out) . " bytes output)\n";
} catch (Throwable $e) {
    ob_end_clean();
    echo "header.php ERROR: " . $e->getMessage() . " (line " . $e->getLine() . " in " . $e->getFile() . ")\n";
}
echo "\n";

echo "=== DIAGNOSIS COMPLETE ===\n\n";

// 10. Verify manage-blogs.php version on server
echo "--- STEP 10: Server's manage-blogs.php version check ---\n";
$mbf = __DIR__ . '/manage-blogs.php';
if (file_exists($mbf)) {
    $lines = file($mbf);
    echo "File size: " . filesize($mbf) . " bytes\n";
    echo "Line count: " . count($lines) . "\n";
    echo "First 8 lines:\n";
    foreach (array_slice($lines, 0, 8) as $l) echo "  " . htmlspecialchars(rtrim($l)) . "\n";
} else {
    echo "manage-blogs.php NOT FOUND at: $mbf\n";
}
echo '</pre>';
