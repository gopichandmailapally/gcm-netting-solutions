<?php
/**
 * Admin Authentication Check - Secure Version
 * Include this file at the top of every admin page
 */

if (!defined('GCM_INIT')) {
    define('GCM_INIT', true);
}

// Load security system
require_once __DIR__ . '/security.php';
$security = new AdminSecurity();

// Validate session security
if (!$security->validateSession()) {
    // Session invalid or expired
    $security->destroySession();
    header('Location: ' . (defined('SITE_URL') ? SITE_URL : '') . '/admin/login.php?timeout=1');
    exit;
}

// Check if IP is blocked
if ($security->isIPBlocked()) {
    $security->destroySession();
    http_response_code(403);
    die('Access denied. Your IP has been blocked.');
}

// Regenerate session ID periodically (every 30 minutes)
if (!isset($_SESSION['last_regeneration']) || (time() - $_SESSION['last_regeneration']) > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// Function to check admin role
function check_admin_role($required_role = 'admin') {
    $roles = ['editor' => 1, 'admin' => 2, 'super_admin' => 3];
    $user_role = $_SESSION['admin_role'] ?? 'editor';
    
    if (!isset($roles[$user_role]) || !isset($roles[$required_role])) {
        return false;
    }
    
    return $roles[$user_role] >= $roles[$required_role];
}

// Function to require specific role
function require_role($role) {
    if (!check_admin_role($role)) {
        global $security;
        $security->logSecurityEvent($_SESSION['admin_id'] ?? 0, 'unauthorized_access_attempt', 'Required role: ' . $role);
        http_response_code(403);
        die('Access denied. Insufficient permissions.');
    }
}

// Function to get CSRF token for forms
function get_csrf_token() {
    global $security;
    return $security->generateCSRFToken();
}

// Function to verify CSRF token
function verify_csrf_token($token) {
    global $security;
    return $security->verifyCSRFToken($token);
}
