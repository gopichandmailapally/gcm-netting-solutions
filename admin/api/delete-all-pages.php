<?php
/**
 * Delete All Generated Pages API
 * Removes all pillar pages and area pages (files + database)
 */

define('GCM_INIT', true);

ob_start();
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// ── PERMANENTLY BLOCKED — AI Content Security Protection ──
ob_clean();
http_response_code(403);
echo json_encode([
    'success'   => false,
    'protected' => true,
    'message'   => '🛡️ BLOCKED by AI Content Security. All 12,032+ service pages are AI-protected. Bulk deletion of all pages is permanently disabled. To delete individual pages, use the delete button on each page (requires Security PIN + admin email approval).',
]);
exit;

if (false) { // dead code below — kept for reference only
try {
    require_once __DIR__ . '/../../config/config.php';
    
    // Get request data
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $confirm = $input['confirm'] ?? '';
    
    if ($action !== 'delete_all' || $confirm !== 'yes') {
        ob_clean();
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }
    
    // Database connection
    if (!isset($conn)) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            $conn = null;
        } else {
            $conn->set_charset(DB_CHARSET);
        }
    }
    
    $root_dir = dirname(dirname(dirname(__FILE__)));
    $files_deleted = 0;
    $db_deleted = 0;
    $errors = [];
    
    // 1. Delete all PHP files (except core files)
    $exclude_files = [
        'index.php', 'about.php', 'contact.php', 'services.php', 
        'blogs.php', 'gallery.php', 'all-areas.php', 'sitemap.php',
        'robots.txt', '.htaccess'
    ];
    
    $php_files = glob($root_dir . '/*.php');
    
    foreach ($php_files as $file) {
        $filename = basename($file);
        
        // Skip excluded files
        if (in_array($filename, $exclude_files)) {
            continue;
        }
        
        // Delete the file
        if (unlink($file)) {
            $files_deleted++;
        } else {
            $errors[] = "Failed to delete: " . $filename;
        }
    }
    
    // 2. Delete all records from database
    if ($conn) {
        try {
            $result = $conn->query("DELETE FROM generated_pages");
            $db_deleted = $conn->affected_rows;
            
            // Reset auto_increment
            $conn->query("ALTER TABLE generated_pages AUTO_INCREMENT = 1");
            
        } catch (Exception $e) {
            $errors[] = "Database deletion failed: " . $e->getMessage();
        }
    }
    
    ob_clean();
    echo json_encode([
        'success' => true,
        'files_deleted' => $files_deleted,
        'db_deleted' => $db_deleted,
        'errors' => $errors,
        'message' => "Deleted {$files_deleted} files and {$db_deleted} database entries"
    ]);
    
} catch (Exception $e) {
    ob_clean();
    echo json_encode([
        'error' => 'Deletion failed: ' . $e->getMessage()
    ]);
}
} // end if(false)
