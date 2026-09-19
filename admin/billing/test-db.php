<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();

echo "<h1>Database Test</h1>";
echo "<h2>Testing Billing Tables</h2>";

// Test if tables exist
$tables = ['billing_products', 'billing_bank_details', 'billing_company_settings', 'billing_invoices', 'billing_invoice_items'];

foreach ($tables as $table) {
    try {
        $result = $db->fetchAll("SELECT * FROM $table LIMIT 1");
        echo "<p style='color: green;'>✓ Table '$table' exists - " . count($result) . " rows found</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Table '$table' ERROR: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";
echo "<h2>Products Count</h2>";
try {
    $products = $db->fetchAll("SELECT * FROM billing_products");
    echo "<p>Total products: " . count($products) . "</p>";
    foreach ($products as $p) {
        echo "<p>- {$p['product_name']} (HSN: {$p['hsn_code']}, GST: {$p['gst_percentage']}%)</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Companies Count</h2>";
try {
    $companies = $db->fetchAll("SELECT * FROM billing_company_settings");
    echo "<p>Total companies: " . count($companies) . "</p>";
    foreach ($companies as $c) {
        echo "<p>- {$c['company_name']}</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Bank Accounts Count</h2>";
try {
    $banks = $db->fetchAll("SELECT * FROM billing_bank_details");
    echo "<p>Total bank accounts: " . count($banks) . "</p>";
    foreach ($banks as $b) {
        echo "<p>- {$b['bank_name']} - {$b['account_number']}</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='init-billing-db.php'>Run Database Initialization</a></p>";
echo "<p><a href='index.php'>Back to Billing Dashboard</a></p>";
?>
