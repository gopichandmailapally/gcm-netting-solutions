<?php
/**
 * Admin Panel - Main Entry Point
 * Redirects to login page
 */

define('GCM_INIT', true);
require_once '../config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    // Redirect to dashboard
    header('Location: dashboard.php');
    exit;
} else {
    // Redirect to login page
    header('Location: login.php');
    exit;
}
