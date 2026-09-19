<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$db = Database::getInstance();

echo "<h1>Billing System Test</h1>";

// Test 1: Check if POST is working
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>✅ POST Request Received</h2>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";
    
    // Test 2: Try to save a simple invoice
    try {
        $company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = 1");
        echo "<h3>Company Settings:</h3>";
        echo "<pre>";
        print_r($company);
        echo "</pre>";
        
        $invoice_number = $company['invoice_prefix'] . str_pad($company['next_invoice_number'], 4, '0', STR_PAD_LEFT);
        echo "<h3>Generated Invoice Number: $invoice_number</h3>";
        
        $result = $db->execute("INSERT INTO billing_invoices (invoice_number, invoice_type, customer_name, customer_phone, invoice_date, subtotal, grand_total, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$invoice_number, 'test', 'Test Customer', '1234567890', date('Y-m-d'), 1000, 1000, 'draft']
        );
        
        if ($result) {
            $invoice_id = $db->lastInsertId();
            echo "<h3>✅ Invoice Saved! ID: $invoice_id</h3>";
            
            // Update counter
            $db->execute("UPDATE billing_company_settings SET next_invoice_number = next_invoice_number + 1 WHERE id = 1");
            
            echo "<h3>✅ Counter Updated</h3>";
        } else {
            echo "<h3>❌ Failed to save invoice</h3>";
        }
        
    } catch (Exception $e) {
        echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    }
    
} else {
    echo "<h2>No POST data - showing form</h2>";
}

// Show all invoices
echo "<h2>All Invoices in Database:</h2>";
$invoices = $db->fetchAll("SELECT * FROM billing_invoices");
echo "<pre>";
print_r($invoices);
echo "</pre>";

echo "<h2>All Products:</h2>";
$products = $db->fetchAll("SELECT * FROM billing_products");
echo "<pre>";
print_r($products);
echo "</pre>";

echo "<h2>All Bank Details:</h2>";
$banks = $db->fetchAll("SELECT * FROM billing_bank_details");
echo "<pre>";
print_r($banks);
echo "</pre>";
?>

<h2>Test Form</h2>
<form method="POST" action="">
    <button type="submit" style="padding: 20px; font-size: 18px; background: #10B981; color: white; border: none; border-radius: 8px; cursor: pointer;">
        Test Save Invoice
    </button>
</form>

<hr>
<a href="create-invoice-gst.php" style="display: inline-block; padding: 15px 30px; background: #3B82F6; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;">
    Go to Create Invoice Page
</a>
