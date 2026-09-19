<?php
/**
 * Import Existing Hardcoded Offer to Database
 * Run this once to make the existing offer editable
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

// Check if offer already exists
$existing = $db->fetchOne("SELECT * FROM offers WHERE coupon_code = 'NEW15'");

if (!$existing) {
    // Insert the existing offer from the website
    $result = $db->execute(
        "INSERT INTO offers (title, description, discount_percentage, coupon_code, valid_from, valid_until, is_active) 
         VALUES (?, ?, ?, ?, ?, ?, ?)",
        [
            'Limited Time Offer!',
            'Get discount on all services - Free Inspection, Same Day Service, 5 Year Warranty, Quality Materials',
            15,
            'NEW15',
            date('Y-m-d'),
            date('Y-m-d', strtotime('+6 months')),
            1
        ],
        'ssisssi'
    );
    
    if ($result['success']) {
        $_SESSION['success_message'] = 'Existing offer imported successfully! You can now edit it from Offers Management.';
    } else {
        $error_detail = isset($result['error']) ? $result['error'] : 'Unknown error';
        $_SESSION['error_message'] = 'Failed to import offer: ' . $error_detail;
    }
} else {
    $_SESSION['info_message'] = 'Offer already exists in the database.';
}

header('Location: offers-management.php');
exit;
