<?php
/**
 * Admin Logout - Secure Version
 */
define('GCM_INIT', true);
require_once '../config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Try to log logout event if security class is available
try {
    require_once 'includes/security.php';
    $security = new AdminSecurity();
    
    if (isset($_SESSION['admin_id'])) {
        $security->logSecurityEvent($_SESSION['admin_id'], 'logout', 'User logged out');
    }
    
    // Destroy session securely
    $security->destroySession();
} catch (Exception $e) {
    error_log("Logout security log failed: " . $e->getMessage());
}

// Clear all session data
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy session
session_destroy();

// Redirect to login page
header('Location: login.php?logged_out=1');
exit;
