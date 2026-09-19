<?php
/**
 * Database Diagnostic Tool
 * Checks if billing tables exist and shows their structure
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$conn = $db->getConnection();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3B82F6; padding-bottom: 10px; }
        h2 { color: #3B82F6; margin-top: 30px; }
        .status { padding: 15px; border-radius: 8px; margin: 15px 0; }
        .success { background: #D1FAE5; border: 2px solid #10B981; color: #065F46; }
        .error { background: #FEE2E2; border: 2px solid #EF4444; color: #991B1B; }
        .warning { background: #FEF3C7; border: 2px solid #F59E0B; color: #92400E; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 12px; text-align: left; border: 1px solid #E5E7EB; }
        th { background: #3B82F6; color: white; font-weight: 600; }
        tr:nth-child(even) { background: #F9FAFB; }
        .btn { display: inline-block; padding: 12px 24px; background: #3B82F6; color: white; text-decoration: none; border-radius: 8px; margin: 10px 5px; font-weight: 600; }
        .btn:hover { background: #2563EB; }
        .btn-success { background: #10B981; }
        .btn-success:hover { background: #059669; }
        code { background: #F3F4F6; padding: 2px 6px; border-radius: 4px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Billing Database Diagnostic</h1>
        
        <?php
        // Check if billing tables exist
        $tables_to_check = [
            'billing_products',
            'billing_bank_details',
            'billing_company_settings',
            'billing_invoices',
            'billing_invoice_items'
        ];
        
        $existing_tables = [];
        $missing_tables = [];
        
        foreach ($tables_to_check as $table) {
            $result = $conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='$table'");
            if ($result && $result->fetch()) {
                $existing_tables[] = $table;
            } else {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            echo '<div class="status success">✅ All billing tables exist!</div>';
        } else {
            echo '<div class="status error">❌ Missing tables: ' . implode(', ', $missing_tables) . '</div>';
            echo '<a href="init-billing-db.php" class="btn btn-success">Create Missing Tables</a>';
        }
        ?>
        
        <h2>📊 Existing Tables</h2>
        <table>
            <tr>
                <th>Table Name</th>
                <th>Row Count</th>
                <th>Status</th>
            </tr>
            <?php foreach ($existing_tables as $table): 
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $count_result ? $count_result->fetch(PDO::FETCH_ASSOC)['count'] : 0;
            ?>
            <tr>
                <td><code><?php echo $table; ?></code></td>
                <td><?php echo $count; ?> rows</td>
                <td><?php echo $count > 0 ? '✅ Has data' : '⚠️ Empty'; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        
        <?php if (in_array('billing_invoice_items', $existing_tables)): ?>
        <h2>🔍 Invoice Items Table Structure</h2>
        <?php
        $columns = $conn->query("PRAGMA table_info(billing_invoice_items)")->fetchAll(PDO::FETCH_ASSOC);
        ?>
        <table>
            <tr>
                <th>Column Name</th>
                <th>Type</th>
                <th>Not Null</th>
                <th>Default</th>
            </tr>
            <?php foreach ($columns as $col): ?>
            <tr>
                <td><code><?php echo $col['name']; ?></code></td>
                <td><?php echo $col['type']; ?></td>
                <td><?php echo $col['notnull'] ? 'Yes' : 'No'; ?></td>
                <td><?php echo $col['dflt_value'] ?? 'NULL'; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
        
        <?php if (in_array('billing_invoices', $existing_tables)): ?>
        <h2>📄 Recent Invoices</h2>
        <?php
        $invoices = $db->fetchAll("SELECT id, invoice_number, invoice_type, customer_name, grand_total, created_at FROM billing_invoices ORDER BY id DESC LIMIT 10");
        if (empty($invoices)) {
            echo '<div class="status warning">⚠️ No invoices found in database</div>';
        } else {
        ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Invoice #</th>
                <th>Type</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Items Count</th>
                <th>Created</th>
            </tr>
            <?php foreach ($invoices as $inv): 
                $items_count = $conn->query("SELECT COUNT(*) as count FROM billing_invoice_items WHERE invoice_id = " . $inv['id'])->fetch(PDO::FETCH_ASSOC)['count'];
            ?>
            <tr>
                <td><?php echo $inv['id']; ?></td>
                <td><?php echo $inv['invoice_number']; ?></td>
                <td><?php echo $inv['invoice_type']; ?></td>
                <td><?php echo $inv['customer_name']; ?></td>
                <td>₹<?php echo number_format($inv['grand_total'], 2); ?></td>
                <td><?php echo $items_count > 0 ? "✅ $items_count items" : "❌ 0 items"; ?></td>
                <td><?php echo $inv['created_at']; ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php } ?>
        <?php endif; ?>
        
        <?php if (in_array('billing_products', $existing_tables)): ?>
        <h2>📦 Products</h2>
        <?php
        $products = $db->fetchAll("SELECT * FROM billing_products WHERE is_active = 1 LIMIT 10");
        if (empty($products)) {
            echo '<div class="status warning">⚠️ No products found. Please add products first!</div>';
            echo '<a href="manage-products.php" class="btn">Add Products</a>';
        } else {
            echo '<div class="status success">✅ ' . count($products) . ' active products found</div>';
        }
        ?>
        <?php endif; ?>
        
        <?php if (in_array('billing_company_settings', $existing_tables)): ?>
        <h2>🏢 Company Settings</h2>
        <?php
        $company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = 1");
        if (!$company) {
            echo '<div class="status error">❌ No company settings found. Please configure company first!</div>';
        } else {
            echo '<div class="status success">✅ Company: ' . htmlspecialchars($company['company_name']) . '</div>';
        }
        ?>
        <?php endif; ?>
        
        <h2>🔧 Actions</h2>
        <a href="init-billing-db.php" class="btn btn-success">Run Database Initialization</a>
        <a href="index.php" class="btn">Back to Billing Dashboard</a>
        
        <h2>💡 Troubleshooting</h2>
        <div class="status warning">
            <strong>If invoices show "No items found":</strong>
            <ol>
                <li>Check that <code>billing_invoice_items</code> table exists</li>
                <li>Verify items are being saved when creating invoices</li>
                <li>Run database initialization to ensure all columns exist</li>
                <li>Check error logs for database errors</li>
            </ol>
        </div>
    </div>
</body>
</html>
