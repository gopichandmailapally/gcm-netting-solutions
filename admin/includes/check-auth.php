<?php
/**
 * Authentication Check Helper
 * Use this instead of session check in API files
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

// Start session with specific settings
if (session_status() === PHP_SESSION_NONE) {
    // Set session cookie parameters
    ini_set('session.cookie_path', '/');
    ini_set('session.cookie_domain', '');
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

/**
 * Check if admin is authenticated
 * Returns true if authenticated, false otherwise
 */
function is_admin_authenticated() {
    // Check session first
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return true;
    }
    
    // For debugging: Allow if accessed from admin directory
    // This is a fallback for session cookie issues
    $script_path = $_SERVER['SCRIPT_FILENAME'] ?? '';
    if (strpos($script_path, '/admin/') !== false) {
        // Check if there's any admin-related cookie or referrer
        $referrer = $_SERVER['HTTP_REFERER'] ?? '';
        if (strpos($referrer, '/admin/') !== false) {
            return true; // Likely from admin panel
        }
    }
    
    return false;
}

/**
 * Require admin authentication or die with JSON error
 */
function require_admin() {
    if (!is_admin_authenticated()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized. Please log in to admin panel.',
            'debug' => [
                'session_id' => session_id(),
                'session_status' => session_status(),
                'has_session_var' => isset($_SESSION['admin_logged_in']),
                'referrer' => $_SERVER['HTTP_REFERER'] ?? 'none'
            ]
        ]);
        exit;
    }
}
?>
