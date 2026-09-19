<?php
/**
 * Generate Share Token for Invoice
 * Creates unique token for public sharing
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

header('Content-Type: application/json');

$invoice_id = $_POST['invoice_id'] ?? null;

if (!$invoice_id) {
    die(json_encode(['success' => false, 'message' => 'Invoice ID required']));
}

$db = Database::getInstance();

// Check if invoice exists
$invoice = $db->fetchOne("SELECT * FROM billing_invoices WHERE id = ?", [$invoice_id]);

if (!$invoice) {
    die(json_encode(['success' => false, 'message' => 'Invoice not found']));
}

// Check if share token already exists
if (!empty($invoice['share_token'])) {
    $share_token = $invoice['share_token'];
} else {
    // Generate unique share token
    $share_token = bin2hex(random_bytes(16));
    
    // Update invoice with share token
    try {
        $db->execute(
            "UPDATE billing_invoices SET share_token = ? WHERE id = ?",
            [$share_token, $invoice_id]
        );
    } catch (Exception $e) {
        // Add share_token column if it doesn't exist
        try {
            $db->execute("ALTER TABLE billing_invoices ADD COLUMN share_token VARCHAR(64) UNIQUE");
            $db->execute(
                "UPDATE billing_invoices SET share_token = ? WHERE id = ?",
                [$share_token, $invoice_id]
            );
        } catch (Exception $e2) {
            die(json_encode(['success' => false, 'message' => 'Failed to generate share token']));
        }
    }
}

// Generate public URL
$public_url = SITE_URL . '/admin/billing/public-view-invoice.php?token=' . $share_token;

echo json_encode([
    'success' => true,
    'share_token' => $share_token,
    'public_url' => $public_url,
    'message' => 'Share link generated successfully'
]);
