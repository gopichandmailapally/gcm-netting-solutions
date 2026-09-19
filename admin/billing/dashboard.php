<?php
/**
 * Complete Billing Dashboard
 * Main hub for all billing operations
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

try {
    // Get statistics
    $total_invoices = $db->fetchOne("SELECT COUNT(*) as count FROM billing_invoices WHERE invoice_type LIKE 'invoice%'")['count'] ?? 0;
    $total_estimations = $db->fetchOne("SELECT COUNT(*) as count FROM billing_invoices WHERE invoice_type LIKE 'estimation%'")['count'] ?? 0;
    $total_revenue = $db->fetchOne("SELECT SUM(grand_total) as total FROM billing_invoices WHERE invoice_type LIKE 'invoice%' AND status != 'cancelled'")['total'] ?? 0;
    $pending_payments = $db->fetchOne("SELECT COUNT(*) as count FROM billing_invoices WHERE status = 'draft' AND invoice_type LIKE 'invoice%'")['count'] ?? 0;
    
    // Recent invoices
    $recent_invoices = $db->fetchAll("SELECT * FROM billing_invoices ORDER BY created_at DESC LIMIT 10");
    
    // Monthly revenue
    $monthly_revenue = $db->fetchAll("
        SELECT 
            DATE_FORMAT(invoice_date, '%Y-%m') as month,
            SUM(grand_total) as total
        FROM billing_invoices
        WHERE invoice_type LIKE 'invoice%' 
        AND status != 'cancelled'
        AND invoice_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY month
        ORDER BY month DESC
    ");
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$page_title = 'Billing Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-panel.css">
</head>
<body>
    <div style="padding: 20px; max-width: 1400px; margin: 0 auto;" class="fade-in-up">
        <div class="page-header">
            <h1><i class="fas fa-file-invoice-dollar"></i> Billing Dashboard</h1>
            <a href="../dashboard.php" class="btn btn-secondary"><i class="fas fa-home"></i> Admin Home</a>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>Database Error: <?php echo htmlspecialchars($error_message); ?></span>
            </div>
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <span>Please run the database setup: <code>mysql -u u271370596_gcm -p u271370596_gcm < sql/complete-billing-system.sql</code></span>
            </div>
        <?php else: ?>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card info">
                <div class="stat-icon"><i class="fas fa-file-invoice"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($total_invoices); ?></div>
                    <div class="stat-label">Total Invoices</div>
                </div>
            </div>
            
            <div class="stat-card warning">
                <div class="stat-icon"><i class="fas fa-file-alt"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($total_estimations); ?></div>
                    <div class="stat-label">Estimations</div>
                </div>
            </div>
            
            <div class="stat-card success">
                <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                <div class="stat-content">
                    <div class="stat-value">₹<?php echo number_format($total_revenue, 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>
            
            <div class="stat-card danger">
                <div class="stat-icon"><i class="fas fa-clock"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($pending_payments); ?></div>
                    <div class="stat-label">Pending Payments</div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="content-card">
            <div class="card-header"><h2>Create New Document</h2></div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="create-invoice-gst.php" class="quick-action-card">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <h3>Invoice with GST</h3>
                    </a>
                    
                    <a href="create-invoice-no-gst.php" class="quick-action-card">
                        <i class="fas fa-file-invoice"></i>
                        <h3>Invoice (No GST)</h3>
                    </a>
                    
                    <a href="create-estimation-gst.php" class="quick-action-card">
                        <i class="fas fa-calculator"></i>
                        <h3>Estimation with GST</h3>
                    </a>
                    
                    <a href="create-estimation-no-gst.php" class="quick-action-card">
                        <i class="fas fa-file-alt"></i>
                        <h3>Estimation (No GST)</h3>
                    </a>
                    
                    <a href="create-cash-bill.php" class="quick-action-card">
                        <i class="fas fa-money-bill-wave"></i>
                        <h3>Cash Bill</h3>
                    </a>
                    
                    <a href="create-warranty.php" class="quick-action-card">
                        <i class="fas fa-shield-alt"></i>
                        <h3>Warranty Card</h3>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Management Links -->
        <div class="content-card">
            <div class="card-header"><h2>Manage</h2></div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="invoices-list.php" class="quick-action-card">
                        <i class="fas fa-list"></i>
                        <h3>All Invoices</h3>
                    </a>
                    
                    <a href="products.php" class="quick-action-card">
                        <i class="fas fa-box"></i>
                        <h3>Products</h3>
                    </a>
                    
                    <a href="company-settings.php" class="quick-action-card">
                        <i class="fas fa-building"></i>
                        <h3>Company Settings</h3>
                    </a>
                    
                    <a href="bank-details.php" class="quick-action-card">
                        <i class="fas fa-university"></i>
                        <h3>Bank Details</h3>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Invoices -->
        <div class="content-card">
            <div class="card-header">
                <h2>Recent Documents</h2>
                <a href="invoices-list.php" class="btn btn-primary btn-sm">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_invoices)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Documents Yet</h3>
                        <p>Create your first invoice or estimation to get started</p>
                        <a href="create-invoice-gst.php" class="btn btn-primary">Create Invoice</a>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Number</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_invoices as $invoice): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong></td>
                                <td>
                                    <?php
                                    $type_labels = [
                                        'invoice_gst' => 'Invoice (GST)',
                                        'invoice_no_gst' => 'Invoice',
                                        'estimation_gst' => 'Estimation (GST)',
                                        'estimation_no_gst' => 'Estimation',
                                        'cash_bill' => 'Cash Bill',
                                        'warranty' => 'Warranty'
                                    ];
                                    echo $type_labels[$invoice['invoice_type']] ?? $invoice['invoice_type'];
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                <td><?php echo date('d M Y', strtotime($invoice['invoice_date'])); ?></td>
                                <td><strong>₹<?php echo number_format($invoice['grand_total'], 2); ?></strong></td>
                                <td>
                                    <?php
                                    $status_classes = [
                                        'draft' => 'badge-secondary',
                                        'sent' => 'badge-info',
                                        'paid' => 'badge-success',
                                        'cancelled' => 'badge-danger',
                                        'converted' => 'badge-warning'
                                    ];
                                    $class = $status_classes[$invoice['status']] ?? 'badge-secondary';
                                    ?>
                                    <span class="badge <?php echo $class; ?>"><?php echo ucfirst($invoice['status']); ?></span>
                                </td>
                                <td>
                                    <a href="view-invoice.php?id=<?php echo $invoice['id']; ?>" class="btn-icon primary" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Monthly Revenue Chart -->
        <?php if (!empty($monthly_revenue)): ?>
        <div class="content-card">
            <div class="card-header"><h2>Monthly Revenue (Last 6 Months)</h2></div>
            <div class="card-body">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($monthly_revenue as $month): ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                            <td><strong>₹<?php echo number_format($month['total'], 2); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</body>
</html>
