<?php
/**
 * Billing Dashboard - Main Page
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

$page_title = 'Billing Dashboard';
include '../includes/header.php';

$db = Database::getInstance();

// Get statistics
$total_invoices = $db->fetchOne("SELECT COUNT(*) as count FROM billing_invoices WHERE invoice_type LIKE '%invoice%'")['count'] ?? 0;
$total_estimations = $db->fetchOne("SELECT COUNT(*) as count FROM billing_invoices WHERE invoice_type LIKE '%estimation%'")['count'] ?? 0;
$total_warranties = $db->fetchOne("SELECT COUNT(*) as count FROM billing_invoices WHERE invoice_type = 'warranty'")['count'] ?? 0;
$total_products = $db->fetchOne("SELECT COUNT(*) as count FROM billing_products WHERE is_active = 1")['count'] ?? 0;

// Get recent invoices
$recent_invoices = $db->fetchAll("
    SELECT * FROM billing_invoices 
    ORDER BY created_at DESC 
    LIMIT 10
");

// Get total revenue (from paid invoices)
$revenue_result = $db->fetchOne("
    SELECT SUM(grand_total) as total 
    FROM billing_invoices 
    WHERE status = 'paid' AND invoice_type LIKE '%invoice%'
");
$total_revenue = $revenue_result['total'] ?? 0;
?>

<div class="billing-dashboard">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-file-invoice-dollar"></i> Billing Dashboard</h1>
            <p>Manage invoices, estimations, and warranty cards</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="window.location.href='products.php'">
                <i class="fas fa-box"></i> Manage Products
            </button>
            <button class="btn btn-success" onclick="showQuickCreate()">
                <i class="fas fa-plus"></i> Quick Create
            </button>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_invoices); ?></h3>
                <p>Total Invoices</p>
                <div class="stat-footer">
                    <a href="invoices.php" class="stat-link">View All →</a>
                </div>
            </div>
        </div>
        
        <div class="stat-card green">
            <div class="stat-icon">
                <i class="fas fa-calculator"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_estimations); ?></h3>
                <p>Estimations</p>
                <div class="stat-footer">
                    <a href="estimations.php" class="stat-link">View All →</a>
                </div>
            </div>
        </div>
        
        <div class="stat-card purple">
            <div class="stat-icon">
                <i class="fas fa-certificate"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_warranties); ?></h3>
                <p>Warranty Cards</p>
                <div class="stat-footer">
                    <a href="warranties.php" class="stat-link">View All →</a>
                </div>
            </div>
        </div>
        
        <div class="stat-card orange">
            <div class="stat-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="stat-content">
                <h3>₹<?php echo number_format($total_revenue, 2); ?></h3>
                <p>Total Revenue</p>
                <div class="stat-footer">
                    <span class="text-muted">From paid invoices</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        </div>
        <div class="card-body">
            <div class="quick-actions-grid">
                <a href="create-estimation.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #3B82F6, #2563EB);">
                        <i class="fas fa-calculator"></i>
                    </div>
                    <h3>Estimation</h3>
                    <p>Create quotation without GST</p>
                </a>
                
                <a href="create-estimation-gst.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #10B981, #059669);">
                        <i class="fas fa-percent"></i>
                    </div>
                    <h3>Estimation with GST</h3>
                    <p>GST quotation with tax breakdown</p>
                </a>
                
                <a href="create-cash-bill.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>Cash Bill</h3>
                    <p>Simple cash bill without GST</p>
                </a>
                
                <a href="create-invoice-gst.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <h3>Invoice with GST</h3>
                    <p>Complete GST tax invoice</p>
                </a>
                
                <a href="create-warranty.php" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #EC4899, #DB2777);">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h3>Warranty Card</h3>
                    <p>Generate warranty certificate</p>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Recent Invoices -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Recent Documents</h2>
            <a href="invoices-list.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-list"></i> View All
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_invoices)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No documents yet</h3>
                    <p>Create your first invoice or estimation to get started</p>
                    <button class="btn btn-primary" onclick="window.location.href='create-invoice-gst.php'">
                        <i class="fas fa-plus"></i> Create Invoice
                    </button>
                </div>
            <?php else: ?>
                <div class="invoices-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Invoice #</th>
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
                                        <span class="badge badge-<?php echo str_replace('_', '-', $invoice['invoice_type']); ?>">
                                            <?php echo ucwords(str_replace('_', ' ', $invoice['invoice_type'])); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                                    <td><?php echo date('M j, Y', strtotime($invoice['invoice_date'])); ?></td>
                                    <td><strong>₹<?php echo number_format($invoice['grand_total'], 2); ?></strong></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $invoice['status']; ?>">
                                            <?php echo ucfirst($invoice['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" onclick="viewInvoice(<?php echo $invoice['id']; ?>)" title="View">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn-icon" onclick="printInvoice(<?php echo $invoice['id']; ?>)" title="Print">
                                                <i class="fas fa-print"></i>
                                            </button>
                                            <button class="btn-icon" onclick="downloadInvoice(<?php echo $invoice['id']; ?>)" title="Download">
                                                <i class="fas fa-download"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Settings & Configuration -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-cog"></i> Settings & Configuration</h2>
        </div>
        <div class="card-body">
            <div class="settings-grid">
                <a href="products.php" class="setting-card">
                    <i class="fas fa-box"></i>
                    <h4>Products</h4>
                    <p>Manage products, HSN codes, and GST rates</p>
                    <span class="badge"><?php echo $total_products; ?> Products</span>
                </a>
                
                <a href="bank-details.php" class="setting-card">
                    <i class="fas fa-university"></i>
                    <h4>Bank Details</h4>
                    <p>Configure bank account information</p>
                </a>
                
                <a href="company-settings.php" class="setting-card">
                    <i class="fas fa-building"></i>
                    <h4>Company Settings</h4>
                    <p>Update company info, GSTIN, and logo</p>
                </a>
                
                <a href="invoice-settings.php" class="setting-card">
                    <i class="fas fa-file-alt"></i>
                    <h4>Invoice Settings</h4>
                    <p>Configure invoice numbering and templates</p>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Billing Dashboard Styles */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.billing-dashboard {
    padding: 20px;
    animation: fadeInUp 0.6s ease;
}

/* Quick Actions Grid */
.quick-actions-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.action-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
    backdrop-filter: blur(10px);
    padding: 28px;
    border-radius: 16px;
    border: 2px solid #E2E8F0;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    text-decoration: none;
    display: block;
    position: relative;
    overflow: hidden;
}

.action-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s;
}

.action-card:hover::before {
    left: 100%;
}

.action-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 16px 48px rgba(59, 130, 246, 0.2);
    border-color: #3B82F6;
}

.action-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 16px;
    border-radius: 16px;
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    animation: float 3s ease-in-out infinite;
}

.action-card h3 {
    font-size: 17px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 8px;
}

.action-card p {
    font-size: 14px;
    color: #64748B;
    margin: 0;
}

/* Settings Grid */
.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.setting-card {
    background: linear-gradient(135deg, #F8FAFC 0%, #FFFFFF 100%);
    padding: 24px;
    border-radius: 12px;
    border: 2px solid #E2E8F0;
    transition: all 0.3s;
    text-decoration: none;
    display: block;
}

.setting-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
    border-color: #3B82F6;
}

.setting-card i {
    font-size: 32px;
    color: #3B82F6;
    margin-bottom: 12px;
}

.setting-card h4 {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 8px;
}

.setting-card p {
    font-size: 14px;
    color: #64748B;
    margin-bottom: 12px;
}

.setting-card .badge {
    display: inline-block;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

/* Invoices Table */
.invoices-table {
    overflow-x: auto;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table thead {
    background: linear-gradient(135deg, #F1F5F9, #E2E8F0);
}

.data-table th {
    padding: 14px;
    text-align: left;
    font-weight: 700;
    color: #1E293B;
    font-size: 14px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 14px;
    border-bottom: 1px solid #E2E8F0;
    color: #475569;
}

.data-table tbody tr {
    transition: all 0.3s;
}

.data-table tbody tr:hover {
    background: linear-gradient(135deg, #F0F9FF, #E0F2FE);
    transform: scale(1.01);
}

.badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.badge-estimation {
    background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
    color: #1E40AF;
}

.badge-estimation-gst {
    background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
    color: #065F46;
}

.badge-cash-bill {
    background: linear-gradient(135deg, #FEF3C7, #FDE68A);
    color: #92400E;
}

.badge-invoice {
    background: linear-gradient(135deg, #E9D5FF, #D8B4FE);
    color: #6B21A8;
}

.badge-invoice-gst {
    background: linear-gradient(135deg, #FECACA, #FCA5A5);
    color: #991B1B;
}

.badge-warranty {
    background: linear-gradient(135deg, #FBCFE8, #F9A8D4);
    color: #9F1239;
}

.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 600;
}

.status-draft {
    background: #F1F5F9;
    color: #475569;
}

.status-sent {
    background: #DBEAFE;
    color: #1E40AF;
}

.status-paid {
    background: #D1FAE5;
    color: #065F46;
}

.status-cancelled {
    background: #FEE2E2;
    color: #991B1B;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(135deg, #F1F5F9, #E2E8F0);
    color: #475569;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-icon:hover {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: #FFFFFF;
    transform: scale(1.1);
}

.stat-link {
    color: #3B82F6;
    text-decoration: none;
    font-size: 13px;
    font-weight: 600;
    transition: all 0.3s;
}

.stat-link:hover {
    color: #2563EB;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 64px;
    color: #CBD5E1;
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 15px;
    color: #94A3B8;
    margin-bottom: 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .quick-actions-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .settings-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .quick-actions-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showQuickCreate() {
    // Scroll to quick actions section
    document.querySelector('.quick-actions-grid').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'center' 
    });
}

function viewInvoice(id) {
    window.location.href = 'view-invoice.php?id=' + id;
}

function printInvoice(id) {
    window.open('print-invoice.php?id=' + id, '_blank');
}

function downloadInvoice(id) {
    window.location.href = 'download-invoice.php?id=' + id;
}
</script>

<?php include '../includes/footer.php'; ?>
