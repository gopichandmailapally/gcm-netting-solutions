<?php
/**
 * Initialize Billing Database Tables
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

$success_count = 0;
$error_count = 0;
$errors = [];

// Create tables directly
$tables = [
    // Products Table
    "CREATE TABLE IF NOT EXISTS billing_products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        product_name VARCHAR(255) NOT NULL,
        hsn_code VARCHAR(50) NOT NULL,
        gst_percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
        default_rate DECIMAL(10,2) DEFAULT 0,
        unit VARCHAR(50) DEFAULT 'sqft',
        is_active TINYINT(1) DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Bank Details Table
    "CREATE TABLE IF NOT EXISTS billing_bank_details (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        bank_name VARCHAR(255) NOT NULL,
        account_number VARCHAR(100) NOT NULL,
        ifsc_code VARCHAR(50) NOT NULL,
        branch_name VARCHAR(255),
        account_holder_name VARCHAR(255),
        upi_id VARCHAR(100),
        is_default TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Company Settings Table
    "CREATE TABLE IF NOT EXISTS billing_company_settings (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        company_name VARCHAR(255) NOT NULL,
        company_address TEXT,
        company_phone VARCHAR(20),
        company_email VARCHAR(100),
        company_gstin VARCHAR(50),
        company_pan VARCHAR(50),
        company_logo_path VARCHAR(255),
        invoice_prefix VARCHAR(20) DEFAULT 'INV',
        estimation_prefix VARCHAR(20) DEFAULT 'EST',
        warranty_prefix VARCHAR(20) DEFAULT 'WAR',
        next_invoice_number INTEGER DEFAULT 1,
        next_estimation_number INTEGER DEFAULT 1,
        next_warranty_number INTEGER DEFAULT 1,
        default_terms_conditions TEXT,
        default_warranty_terms TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Invoices Table
    "CREATE TABLE IF NOT EXISTS billing_invoices (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_number VARCHAR(100) NOT NULL UNIQUE,
        invoice_type VARCHAR(50) NOT NULL,
        customer_name VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(20),
        customer_email VARCHAR(255),
        customer_address TEXT,
        customer_gstin VARCHAR(50),
        invoice_date DATE NOT NULL,
        due_date DATE,
        subtotal DECIMAL(12,2) NOT NULL DEFAULT 0,
        cgst_amount DECIMAL(12,2) DEFAULT 0,
        sgst_amount DECIMAL(12,2) DEFAULT 0,
        igst_amount DECIMAL(12,2) DEFAULT 0,
        total_gst DECIMAL(12,2) DEFAULT 0,
        grand_total DECIMAL(12,2) NOT NULL DEFAULT 0,
        notes TEXT,
        terms_conditions TEXT,
        warranty_period VARCHAR(50),
        warranty_terms TEXT,
        bank_details_id INTEGER,
        status VARCHAR(50) DEFAULT 'draft',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )",
    
    // Invoice Items Table
    "CREATE TABLE IF NOT EXISTS billing_invoice_items (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        invoice_id INTEGER NOT NULL,
        product_id INTEGER,
        product_name VARCHAR(255) NOT NULL,
        description TEXT,
        hsn_code VARCHAR(50),
        quantity DECIMAL(10,2) NOT NULL,
        unit VARCHAR(50),
        rate DECIMAL(10,2) NOT NULL DEFAULT 0,
        amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        gst_percentage DECIMAL(5,2) DEFAULT 0,
        gst_amount DECIMAL(12,2) DEFAULT 0,
        total_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )"
];

// Execute table creation
foreach ($tables as $sql) {
    try {
        $conn->exec($sql);
        $success_count++;
    } catch (PDOException $e) {
        $error_count++;
        $errors[] = "Table creation error: " . $e->getMessage();
    }
}

// Insert default products
$products = [
    ['Anti Bird Net', '39269099', 18, 0, 'sqft'],
    ['Coconut Safety Net', '39269099', 18, 0, 'sqft'],
    ['Construction Safety Net', '39269099', 18, 0, 'sqft'],
    ['Pigeon Safety Net', '39269099', 18, 0, 'sqft'],
    ['Children Safety Net', '39269099', 18, 0, 'sqft'],
    ['Duct Area Safety Net', '39269099', 18, 0, 'sqft'],
    ['Monkey Safety Net', '39269099', 18, 0, 'sqft'],
    ['Sports Net', '39269099', 18, 0, 'sqft'],
    ['Garrage Safety Net', '39269099', 18, 0, 'sqft']
];

try {
    $stmt = $conn->prepare("INSERT INTO billing_products (product_name, hsn_code, gst_percentage, default_rate, unit, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    foreach ($products as $product) {
        $stmt->execute($product);
        $success_count++;
    }
} catch (PDOException $e) {
    $error_count++;
    $errors[] = "Product insert error: " . $e->getMessage();
}

// Insert default company settings
try {
    $check = $conn->query("SELECT COUNT(*) FROM billing_company_settings")->fetchColumn();
    if ($check == 0) {
        $conn->exec("INSERT INTO billing_company_settings (company_name, company_address, company_phone, company_email, invoice_prefix, estimation_prefix, warranty_prefix) VALUES ('GCM Netting Solutions', 'Chennai', '', '', 'INV', 'EST', 'WAR')");
        $success_count++;
    }
} catch (PDOException $e) {
    $error_count++;
    $errors[] = "Company settings error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialize Billing Database</title>
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }
        h1 {
            color: #1E293B;
            margin-bottom: 30px;
            font-size: 32px;
        }
        .success {
            background: linear-gradient(135deg, #10B981, #059669);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .error {
            background: linear-gradient(135deg, #EF4444, #DC2626);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .info {
            background: #F0F9FF;
            border: 2px solid #3B82F6;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        li {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧾 Billing Database Initialization</h1>
        
        <?php if ($error_count === 0): ?>
            <div class="success">
                <h2>✅ Success!</h2>
                <p><strong><?php echo $success_count; ?></strong> database statements executed successfully.</p>
            </div>
            
            <div class="info">
                <h3>📋 Created Tables:</h3>
                <ul>
                    <li>✅ billing_products - Product catalog with HSN and GST</li>
                    <li>✅ billing_bank_details - Bank account information</li>
                    <li>✅ billing_invoices - Main invoices/bills table</li>
                    <li>✅ billing_invoice_items - Invoice line items</li>
                    <li>✅ billing_company_settings - Company configuration</li>
                </ul>
                
                <h3>📦 Default Data:</h3>
                <ul>
                    <li>✅ 9 Products with HSN codes and GST rates</li>
                    <li>✅ Company settings initialized</li>
                </ul>
            </div>
        <?php else: ?>
            <div class="error">
                <h2>⚠️ Errors Occurred</h2>
                <p><strong><?php echo $error_count; ?></strong> errors, <strong><?php echo $success_count; ?></strong> successful.</p>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="index.php" class="btn">📊 Go to Billing Dashboard</a>
            <a href="../dashboard.php" class="btn" style="background: linear-gradient(135deg, #64748B, #475569); margin-left: 10px;">🏠 Admin Dashboard</a>
        </div>
    </div>
</body>
</html>
