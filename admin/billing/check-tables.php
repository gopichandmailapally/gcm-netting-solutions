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

?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Billing Tables</title>
    <style>
        body { font-family: Arial; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn-danger { background: #f44336; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Billing Database Status</h1>
        
        <?php
        // Check for billing tables
        $tables = ['billing_products', 'billing_bank_details', 'billing_company_settings', 'billing_invoices', 'billing_invoice_items'];
        $allExist = true;
        
        echo "<h2>Table Status:</h2>";
        echo "<table>";
        echo "<tr><th>Table Name</th><th>Status</th><th>Row Count</th></tr>";
        
        foreach ($tables as $table) {
            $check = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
            if ($check) {
                $count = $db->fetchOne("SELECT COUNT(*) as cnt FROM $table");
                echo "<tr><td>$table</td><td class='success'>✅ EXISTS</td><td>{$count['cnt']}</td></tr>";
            } else {
                echo "<tr><td>$table</td><td class='error'>❌ MISSING</td><td>-</td></tr>";
                $allExist = false;
            }
        }
        echo "</table>";
        
        if (!$allExist) {
            echo "<div style='background: #fff3cd; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>⚠️ Tables are Missing!</h3>";
            echo "<p>The billing tables have not been created yet. You need to run the database initialization.</p>";
            echo "<a href='init-billing-db.php' class='btn'>🚀 Initialize Database Now</a>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 20px; border-radius: 5px; margin: 20px 0;'>";
            echo "<h3>✅ All Tables Exist!</h3>";
            echo "<p>The billing system is properly set up.</p>";
            echo "</div>";
            
            // Show recent products
            echo "<h2>Recent Products:</h2>";
            $products = $db->fetchAll("SELECT * FROM billing_products ORDER BY id DESC LIMIT 5");
            if (count($products) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Product Name</th><th>HSN</th><th>GST %</th><th>Active</th></tr>";
                foreach ($products as $p) {
                    echo "<tr>";
                    echo "<td>{$p['id']}</td>";
                    echo "<td>{$p['product_name']}</td>";
                    echo "<td>{$p['hsn_code']}</td>";
                    echo "<td>{$p['gst_percentage']}%</td>";
                    echo "<td>" . ($p['is_active'] ? '✅' : '❌') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No products found. <a href='add-product.php'>Add your first product</a></p>";
            }
            
            // Show companies
            echo "<h2>Companies:</h2>";
            $companies = $db->fetchAll("SELECT * FROM billing_company_settings");
            if (count($companies) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Company Name</th><th>GSTIN</th></tr>";
                foreach ($companies as $c) {
                    echo "<tr>";
                    echo "<td>{$c['id']}</td>";
                    echo "<td>{$c['company_name']}</td>";
                    echo "<td>{$c['company_gstin']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No companies found. <a href='add-company.php'>Add your first company</a></p>";
            }
            
            // Show bank accounts
            echo "<h2>Bank Accounts:</h2>";
            $banks = $db->fetchAll("SELECT * FROM billing_bank_details");
            if (count($banks) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Bank Name</th><th>Account Number</th><th>Default</th></tr>";
                foreach ($banks as $b) {
                    echo "<tr>";
                    echo "<td>{$b['id']}</td>";
                    echo "<td>{$b['bank_name']}</td>";
                    echo "<td>{$b['account_number']}</td>";
                    echo "<td>" . ($b['is_default'] ? '✅' : '') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No bank accounts found. <a href='add-bank.php'>Add your first bank account</a></p>";
            }
        }
        ?>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="btn">📊 Billing Dashboard</a>
            <a href="products.php" class="btn">📦 Products</a>
            <a href="company-settings.php" class="btn">🏢 Companies</a>
            <a href="bank-details.php" class="btn">🏦 Bank Accounts</a>
        </div>
    </div>
</body>
</html>
