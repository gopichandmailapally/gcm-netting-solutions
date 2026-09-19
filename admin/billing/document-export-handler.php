<?php
/**
 * Document Export Handler
 * Handles exporting billing documents to multiple formats
 * Supports: PDF, Excel, Word, CSV, JSON
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

// Get request parameters
$document_id = $_GET['id'] ?? null;
$document_type = $_GET['type'] ?? 'invoice'; // invoice, estimation, warranty, cash_bill
$export_format = $_GET['format'] ?? 'pdf'; // pdf, excel, word, csv, json

if (!$document_id) {
    die(json_encode(['error' => 'Document ID required']));
}

$db = Database::getInstance();

// Fetch document data based on type
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
    case 'cash_bill':
        $document = $db->fetchOne("SELECT * FROM cash_bills WHERE id = ?", [$document_id]);
        break;
}

if (!$document) {
    die(json_encode(['error' => 'Document not found']));
}

// Get company details
$company = $db->fetchOne("SELECT * FROM company_details LIMIT 1");

// Get items
$items = [];
switch ($document_type) {
    case 'invoice':
        $items = $db->fetchAll("SELECT * FROM invoice_items WHERE invoice_id = ?", [$document_id]);
        break;
    case 'estimation':
        $items = $db->fetchAll("SELECT * FROM estimation_items WHERE estimation_id = ?", [$document_id]);
        break;
}

// Export based on format
switch ($export_format) {
    case 'pdf':
        exportToPDF($document, $items, $company, $document_type);
        break;
    case 'excel':
        exportToExcel($document, $items, $company, $document_type);
        break;
    case 'word':
        exportToWord($document, $items, $company, $document_type);
        break;
    case 'csv':
        exportToCSV($document, $items, $company, $document_type);
        break;
    case 'json':
        exportToJSON($document, $items, $company, $document_type);
        break;
    default:
        die(json_encode(['error' => 'Invalid export format']));
}

/**
 * Export to PDF (HTML to PDF conversion)
 */
function exportToPDF($document, $items, $company, $type) {
    // Generate HTML content
    $html = generateDocumentHTML($document, $items, $company, $type);
    
    // Set headers for PDF download
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $type . '_' . $document['invoice_number'] . '.pdf"');
    
    // Simple HTML to PDF conversion (for production, use libraries like TCPDF or mPDF)
    // For now, we'll use browser print functionality
    echo '<html><head><style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; font-weight: bold; }
        .header { text-align: center; margin-bottom: 30px; }
        .total { font-size: 18px; font-weight: bold; }
    </style></head><body>';
    echo $html;
    echo '<script>window.print();</script></body></html>';
}

/**
 * Export to Excel (CSV format compatible with Excel)
 */
function exportToExcel($document, $items, $company, $type) {
    $filename = $type . '_' . $document['invoice_number'] . '.csv';
    
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Header
    fputcsv($output, ['GCM Netting Solutions - ' . strtoupper($type)]);
    fputcsv($output, ['']);
    fputcsv($output, ['Document Number', $document['invoice_number'] ?? $document['estimation_number'] ?? '']);
    fputcsv($output, ['Date', $document['invoice_date'] ?? $document['estimation_date'] ?? '']);
    fputcsv($output, ['Customer', $document['customer_name'] ?? '']);
    fputcsv($output, ['']);
    
    // Items header
    fputcsv($output, ['Item', 'Description', 'Quantity', 'Rate', 'Amount']);
    
    // Items
    foreach ($items as $item) {
        fputcsv($output, [
            $item['item_name'],
            $item['description'] ?? '',
            $item['quantity'],
            $item['rate'],
            $item['amount']
        ]);
    }
    
    fputcsv($output, ['']);
    fputcsv($output, ['Subtotal', '', '', '', $document['subtotal'] ?? '']);
    if (isset($document['gst_amount'])) {
        fputcsv($output, ['GST', '', '', '', $document['gst_amount']]);
    }
    fputcsv($output, ['Total', '', '', '', $document['total_amount'] ?? '']);
    
    fclose($output);
}

/**
 * Export to Word (HTML format)
 */
function exportToWord($document, $items, $company, $type) {
    $filename = $type . '_' . $document['invoice_number'] . '.doc';
    
    header('Content-Type: application/msword');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    echo generateDocumentHTML($document, $items, $company, $type);
}

/**
 * Export to CSV
 */
function exportToCSV($document, $items, $company, $type) {
    exportToExcel($document, $items, $company, $type); // Same as Excel
}

/**
 * Export to JSON
 */
function exportToJSON($document, $items, $company, $type) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="' . $type . '_' . $document['invoice_number'] . '.json"');
    
    $data = [
        'document_type' => $type,
        'document' => $document,
        'items' => $items,
        'company' => $company
    ];
    
    echo json_encode($data, JSON_PRETTY_PRINT);
}

/**
 * Generate HTML content for document
 */
function generateDocumentHTML($document, $items, $company, $type) {
    $html = '<div class="header">';
    $html .= '<h1>' . ($company['company_name'] ?? 'GCM Netting Solutions') . '</h1>';
    $html .= '<p>' . ($company['address'] ?? '') . '</p>';
    $html .= '<p>GST: ' . ($company['gst_number'] ?? '') . '</p>';
    $html .= '<h2>' . strtoupper($type) . '</h2>';
    $html .= '</div>';
    
    $html .= '<table>';
    $html .= '<tr><td><strong>Document No:</strong></td><td>' . ($document['invoice_number'] ?? $document['estimation_number'] ?? '') . '</td></tr>';
    $html .= '<tr><td><strong>Date:</strong></td><td>' . ($document['invoice_date'] ?? $document['estimation_date'] ?? '') . '</td></tr>';
    $html .= '<tr><td><strong>Customer:</strong></td><td>' . ($document['customer_name'] ?? '') . '</td></tr>';
    $html .= '</table>';
    
    $html .= '<table>';
    $html .= '<thead><tr><th>Item</th><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr></thead>';
    $html .= '<tbody>';
    
    foreach ($items as $item) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($item['item_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($item['description'] ?? '') . '</td>';
        $html .= '<td>' . $item['quantity'] . '</td>';
        $html .= '<td>₹' . number_format($item['rate'], 2) . '</td>';
        $html .= '<td>₹' . number_format($item['amount'], 2) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    
    $html .= '<div class="total">';
    $html .= '<p>Subtotal: ₹' . number_format($document['subtotal'] ?? 0, 2) . '</p>';
    if (isset($document['gst_amount'])) {
        $html .= '<p>GST: ₹' . number_format($document['gst_amount'], 2) . '</p>';
    }
    $html .= '<p><strong>Total: ₹' . number_format($document['total_amount'] ?? 0, 2) . '</strong></p>';
    $html .= '</div>';
    
    return $html;
}
