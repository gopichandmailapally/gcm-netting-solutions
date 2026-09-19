<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'All Invoices & Documents';
include '../includes/header.php';

$db = Database::getInstance();

// Get filter
$filter = isset($_GET['type']) ? $_GET['type'] : 'all';

// Build query
$query = "SELECT * FROM billing_invoices";
if ($filter != 'all') {
    $query .= " WHERE invoice_type = ?";
    $invoices = $db->fetchAll($query . " ORDER BY created_at DESC", [$filter]);
} else {
    $invoices = $db->fetchAll($query . " ORDER BY created_at DESC");
}
?>

<div class="invoices-list-page">
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-file-invoice"></i> All Invoices & Documents</h1>
            <p>View, download, and share all your saved documents</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <a href="?type=all" class="tab <?php echo $filter == 'all' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i> All Documents (<?php echo count($db->fetchAll("SELECT * FROM billing_invoices")); ?>)
        </a>
        <a href="?type=invoice_gst" class="tab <?php echo $filter == 'invoice_gst' ? 'active' : ''; ?>">
            <i class="fas fa-file-invoice-dollar"></i> GST Invoices
        </a>
        <a href="?type=estimation_gst" class="tab <?php echo $filter == 'estimation_gst' ? 'active' : ''; ?>">
            <i class="fas fa-calculator"></i> GST Estimations
        </a>
        <a href="?type=estimation" class="tab <?php echo $filter == 'estimation' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i> Estimations
        </a>
        <a href="?type=cash_bill" class="tab <?php echo $filter == 'cash_bill' ? 'active' : ''; ?>">
            <i class="fas fa-money-bill"></i> Cash Bills
        </a>
        <a href="?type=warranty" class="tab <?php echo $filter == 'warranty' ? 'active' : ''; ?>">
            <i class="fas fa-certificate"></i> Warranty Cards
        </a>
    </div>
    
    <!-- Invoices Table -->
    <div class="content-card">
        <?php if (count($invoices) > 0): ?>
            <table class="invoices-table">
                <thead>
                    <tr>
                        <th>Document #</th>
                        <th>Type</th>
                        <th>Customer</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($invoices as $invoice): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($invoice['invoice_number']); ?></strong>
                        </td>
                        <td>
                            <?php
                            $type_labels = [
                                'invoice_gst' => '<span class="badge badge-primary">GST Invoice</span>',
                                'estimation_gst' => '<span class="badge badge-info">GST Estimation</span>',
                                'estimation' => '<span class="badge badge-secondary">Estimation</span>',
                                'cash_bill' => '<span class="badge badge-success">Cash Bill</span>',
                                'warranty' => '<span class="badge badge-warning">Warranty</span>'
                            ];
                            echo $type_labels[$invoice['invoice_type']] ?? $invoice['invoice_type'];
                            ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($invoice['customer_name']); ?><br>
                            <small style="color: #64748B;"><?php echo htmlspecialchars($invoice['customer_phone']); ?></small>
                        </td>
                        <td><?php echo date('d-M-Y', strtotime($invoice['invoice_date'])); ?></td>
                        <td><strong>₹<?php echo number_format($invoice['grand_total'], 2); ?></strong></td>
                        <td>
                            <?php
                            $status_labels = [
                                'draft' => '<span class="badge badge-secondary">Draft</span>',
                                'sent' => '<span class="badge badge-info">Sent</span>',
                                'paid' => '<span class="badge badge-success">Paid</span>',
                                'cancelled' => '<span class="badge badge-danger">Cancelled</span>'
                            ];
                            echo $status_labels[$invoice['status']] ?? $invoice['status'];
                            ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="view-invoice.php?id=<?php echo $invoice['id']; ?>" class="btn-icon" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="view-invoice.php?id=<?php echo $invoice['id']; ?>&print=1" target="_blank" class="btn-icon" title="Print">
                                    <i class="fas fa-print"></i>
                                </a>
                                <button class="btn-icon" onclick="shareWhatsApp(<?php echo $invoice['id']; ?>, '<?php echo addslashes($invoice['invoice_number']); ?>', '<?php echo addslashes($invoice['customer_name']); ?>', <?php echo $invoice['grand_total']; ?>)" title="Share">
                                    <i class="fab fa-whatsapp"></i>
                                </button>
                                <button class="btn-icon danger" onclick="deleteInvoice(<?php echo $invoice['id']; ?>, '<?php echo addslashes($invoice['invoice_number']); ?>')" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-file-invoice" style="font-size: 64px; color: #CBD5E1; margin-bottom: 20px;"></i>
                <h3>No Documents Found</h3>
                <p>Start creating invoices, estimations, or bills from the dashboard</p>
                <button class="btn btn-primary" onclick="window.location.href='index.php'">
                    <i class="fas fa-plus"></i> Create Document
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.invoices-list-page { padding: 20px; animation: fadeInUp 0.6s ease; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.tab {
    padding: 12px 20px;
    background: white;
    border: 2px solid #E2E8F0;
    border-radius: 10px;
    text-decoration: none;
    color: #64748B;
    font-weight: 600;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.tab:hover {
    border-color: #3B82F6;
    color: #3B82F6;
    transform: translateY(-2px);
}

.tab.active {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: white;
    border-color: #3B82F6;
}

.invoices-table {
    width: 100%;
    border-collapse: collapse;
}

.invoices-table th {
    background: #F8FAFC;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    color: #475569;
    border-bottom: 2px solid #E2E8F0;
}

.invoices-table td {
    padding: 12px;
    border-bottom: 1px solid #E2E8F0;
}

.invoices-table tr:hover {
    background: #F8FAFC;
}

.badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-block;
}

.badge-primary { background: #DBEAFE; color: #1E40AF; }
.badge-info { background: #E0F2FE; color: #0369A1; }
.badge-secondary { background: #F1F5F9; color: #475569; }
.badge-success { background: #D1FAE5; color: #065F46; }
.badge-warning { background: #FEF3C7; color: #92400E; }
.badge-danger { background: #FEE2E2; color: #991B1B; }

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 36px;
    height: 36px;
    border: none;
    background: #F1F5F9;
    color: #64748B;
    border-radius: 8px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-icon:hover {
    background: #3B82F6;
    color: white;
    transform: translateY(-2px);
}

.btn-icon.danger:hover {
    background: #EF4444;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state h3 {
    font-size: 20px;
    color: #475569;
    margin: 10px 0;
}

.empty-state p {
    color: #94A3B8;
    margin-bottom: 20px;
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
}

.alert-success {
    background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
    color: #065F46;
    border: 2px solid #10B981;
}
</style>

<script>
function shareWhatsApp(id, invoiceNumber, customerName, amount) {
    const message = `Invoice ${invoiceNumber} for ${customerName}\nAmount: ₹${amount.toFixed(2)}\n\nView invoice: ${window.location.origin}/admin/billing/view-invoice.php?id=${id}`;
    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(message)}`;
    window.open(whatsappUrl, '_blank');
}

function deleteInvoice(id, invoiceNumber) {
    if (confirm(`Are you sure you want to delete invoice ${invoiceNumber}?`)) {
        window.location.href = `delete-invoice.php?id=${id}`;
    }
}
</script>

<?php include '../includes/footer.php'; ?>
