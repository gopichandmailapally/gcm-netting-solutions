<?php
/**
 * Share Document via Email/WhatsApp
 * Allows sharing billing documents through multiple channels
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

$action = $_POST['action'] ?? '';
$document_id = $_POST['document_id'] ?? null;
$document_type = $_POST['document_type'] ?? 'invoice';
$recipient = $_POST['recipient'] ?? '';
$method = $_POST['method'] ?? 'email'; // email, whatsapp, sms

if (!$document_id || !$recipient) {
    die(json_encode(['success' => false, 'message' => 'Missing required parameters']));
}

$db = Database::getInstance();

// Fetch document
$document = null;
switch ($document_type) {
    case 'invoice':
        $document = $db->fetchOne("SELECT * FROM invoices WHERE id = ?", [$document_id]);
        break;
    case 'estimation':
        $document = $db->fetchOne("SELECT * FROM estimations WHERE id = ?", [$document_id]);
        break;
    case 'warranty':
        $document = $db->fetchOne("SELECT * FROM warranties WHERE id = ?", [$document_id]);
        break;
}

if (!$document) {
    die(json_encode(['success' => false, 'message' => 'Document not found']));
}

// Get company details
$company = $db->fetchOne("SELECT * FROM company_details LIMIT 1");

// Share based on method
switch ($method) {
    case 'email':
        $result = shareViaEmail($document, $recipient, $company, $document_type);
        break;
    case 'whatsapp':
        $result = shareViaWhatsApp($document, $recipient, $company, $document_type);
        break;
    case 'sms':
        $result = shareViaSMS($document, $recipient, $company, $document_type);
        break;
    default:
        $result = ['success' => false, 'message' => 'Invalid sharing method'];
}

echo json_encode($result);

/**
 * Share via Email
 */
function shareViaEmail($document, $email, $company, $type) {
    $doc_number = $document['invoice_number'] ?? $document['estimation_number'] ?? '';
    $doc_date = $document['invoice_date'] ?? $document['estimation_date'] ?? '';
    $customer_name = $document['customer_name'] ?? '';
    $total = $document['total_amount'] ?? 0;
    
    $subject = ucfirst($type) . ' #' . $doc_number . ' from ' . ($company['company_name'] ?? 'GCM Netting Solutions');
    
    $message = "Dear " . $customer_name . ",\n\n";
    $message .= "Please find your " . $type . " details below:\n\n";
    $message .= ucfirst($type) . " Number: " . $doc_number . "\n";
    $message .= "Date: " . $doc_date . "\n";
    $message .= "Total Amount: ₹" . number_format($total, 2) . "\n\n";
    $message .= "You can view and download your " . $type . " by clicking the link below:\n";
    $message .= SITE_URL . "/admin/billing/view-invoice.php?id=" . $document['id'] . "\n\n";
    $message .= "Thank you for your business!\n\n";
    $message .= "Best regards,\n";
    $message .= $company['company_name'] ?? 'GCM Netting Solutions';
    
    $headers = "From: " . ($company['email'] ?? 'noreply@gcmsafetynets.in') . "\r\n";
    $headers .= "Reply-To: " . ($company['email'] ?? 'noreply@gcmsafetynets.in') . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    
    $sent = mail($email, $subject, $message, $headers);
    
    if ($sent) {
        // Log the share action
        logShareAction($document['id'], $type, 'email', $email);
        return ['success' => true, 'message' => 'Document sent via email successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to send email'];
    }
}

/**
 * Share via WhatsApp
 */
function shareViaWhatsApp($document, $phone, $company, $type) {
    $doc_number = $document['invoice_number'] ?? $document['estimation_number'] ?? '';
    $total = $document['total_amount'] ?? 0;
    
    // Clean phone number
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 10) {
        $phone = '91' . $phone; // Add India country code
    }
    
    $message = "Hello! Your " . $type . " #" . $doc_number . " for ₹" . number_format($total, 2);
    $message .= " is ready. View it here: " . SITE_URL . "/admin/billing/view-invoice.php?id=" . $document['id'];
    
    $whatsapp_url = "https://wa.me/" . $phone . "?text=" . urlencode($message);
    
    // Log the share action
    logShareAction($document['id'], $type, 'whatsapp', $phone);
    
    return [
        'success' => true,
        'message' => 'WhatsApp link generated',
        'url' => $whatsapp_url
    ];
}

/**
 * Share via SMS
 */
function shareViaSMS($document, $phone, $company, $type) {
    $doc_number = $document['invoice_number'] ?? $document['estimation_number'] ?? '';
    $total = $document['total_amount'] ?? 0;
    
    $message = ucfirst($type) . " #" . $doc_number . " for Rs." . number_format($total, 2);
    $message .= " - " . ($company['company_name'] ?? 'GCM Netting Solutions');
    
    // In production, integrate with SMS gateway (e.g., Twilio, MSG91)
    // For now, return success with message
    
    logShareAction($document['id'], $type, 'sms', $phone);
    
    return [
        'success' => true,
        'message' => 'SMS sent successfully (demo mode)',
        'sms_text' => $message
    ];
}

/**
 * Log share action
 */
function logShareAction($document_id, $document_type, $method, $recipient) {
    $db = Database::getInstance();
    
    try {
        $db->execute(
            "INSERT INTO document_shares (document_id, document_type, share_method, recipient, shared_at) 
             VALUES (?, ?, ?, ?, datetime('now'))",
            [$document_id, $document_type, $method, $recipient]
        );
    } catch (Exception $e) {
        // Create table if it doesn't exist
        $db->execute("CREATE TABLE IF NOT EXISTS document_shares (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            document_id INTEGER NOT NULL,
            document_type TEXT NOT NULL,
            share_method TEXT NOT NULL,
            recipient TEXT NOT NULL,
            shared_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Try again
        $db->execute(
            "INSERT INTO document_shares (document_id, document_type, share_method, recipient, shared_at) 
             VALUES (?, ?, ?, ?, datetime('now'))",
            [$document_id, $document_type, $method, $recipient]
        );
    }
}
