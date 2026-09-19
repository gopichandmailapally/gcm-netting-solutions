<?php
/**
 * Products Management - Manage HSN Codes and GST Rates
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

$page_title = 'Manage Products';
include '../includes/header.php';

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_product') {
            $product_name = sanitize_input($_POST['product_name']);
            $hsn_code = sanitize_input($_POST['hsn_code']);
            $gst_percentage = floatval($_POST['gst_percentage']);
            $default_rate = floatval($_POST['default_rate'] ?? 0);
            $unit = sanitize_input($_POST['unit'] ?? 'sqft');
            
            $db->execute(
                "INSERT INTO billing_products (product_name, hsn_code, gst_percentage, default_rate, unit) VALUES (?, ?, ?, ?, ?)",
                [$product_name, $hsn_code, $gst_percentage, $default_rate, $unit]
            );
            
            $_SESSION['success_message'] = "Product added successfully!";
            header('Location: products.php');
            exit;
        }
        elseif ($_POST['action'] === 'update_product') {
            $id = intval($_POST['product_id']);
            $product_name = sanitize_input($_POST['product_name']);
            $hsn_code = sanitize_input($_POST['hsn_code']);
            $gst_percentage = floatval($_POST['gst_percentage']);
            $default_rate = floatval($_POST['default_rate'] ?? 0);
            $unit = sanitize_input($_POST['unit'] ?? 'sqft');
            
            $db->execute(
                "UPDATE billing_products SET product_name = ?, hsn_code = ?, gst_percentage = ?, default_rate = ?, unit = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$product_name, $hsn_code, $gst_percentage, $default_rate, $unit, $id]
            );
            
            $_SESSION['success_message'] = "Product updated successfully!";
            header('Location: products.php');
            exit;
        }
        elseif ($_POST['action'] === 'delete_product') {
            $id = intval($_POST['product_id']);
            $db->execute("UPDATE billing_products SET is_active = 0 WHERE id = ?", [$id]);
            
            $_SESSION['success_message'] = "Product deleted successfully!";
            header('Location: products.php');
            exit;
        }
    }
}

// Get all products (including is_active = NULL or 0 for debugging)
$products = $db->fetchAll("SELECT * FROM billing_products ORDER BY product_name ASC");
?>

<div class="products-page">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-box"></i> Manage Products</h1>
            <p>Configure products, HSN codes, and GST rates</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </button>
            <button class="btn btn-primary" onclick="window.location.href='add-product.php'">
                <i class="fas fa-plus"></i> Add New Product
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <!-- Info Card -->
    <div class="content-card" style="background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%); border: 2px solid #3B82F6;">
        <div class="card-body">
            <h3 style="color: #1E293B; margin-bottom: 12px;">
                <i class="fas fa-info-circle"></i> Important Information
            </h3>
            <ul style="color: #475569; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li><strong>HSN Code:</strong> Harmonized System of Nomenclature code for GST compliance</li>
                <li><strong>GST %:</strong> Goods and Services Tax percentage (5%, 12%, 18%, 28%)</li>
                <li><strong>Default Rate:</strong> Optional default price per unit for quick billing</li>
                <li><strong>Unit:</strong> Measurement unit (sqft, unit, meter, etc.)</li>
                <li><strong>⚠️ Changes take effect immediately</strong> - All new bills will use updated HSN and GST rates</li>
            </ul>
        </div>
    </div>
    
    <!-- Products Table -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Products List (<?php echo count($products); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fas fa-box-open"></i>
                    <h3>No products found</h3>
                    <p>Add your first product to get started with billing</p>
                    <button class="btn btn-primary" onclick="showAddProductModal()">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>
            <?php else: ?>
                <div class="products-table">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>HSN Code</th>
                                <th>GST %</th>
                                <th>Default Rate</th>
                                <th>Unit</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['product_name']); ?></strong>
                                    </td>
                                    <td>
                                        <code style="background: #F1F5F9; padding: 4px 8px; border-radius: 4px; font-weight: 600;">
                                            <?php echo htmlspecialchars($product['hsn_code']); ?>
                                        </code>
                                    </td>
                                    <td>
                                        <span class="gst-badge gst-<?php echo $product['gst_percentage']; ?>">
                                            <?php echo $product['gst_percentage']; ?>%
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($product['default_rate'] > 0): ?>
                                            ₹<?php echo number_format($product['default_rate'], 2); ?>
                                        <?php else: ?>
                                            <span style="color: #94A3B8;">Not set</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="unit-badge"><?php echo htmlspecialchars($product['unit']); ?></span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn-icon" onclick="window.location.href='add-product.php?id=<?php echo $product['id']; ?>'" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn-icon danger" onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['product_name']); ?>')" title="Delete">
                                                <i class="fas fa-trash"></i>
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
</div>

<!-- Add/Edit Product Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus"></i> Add New Product</h2>
            <button class="modal-close" onclick="closeProductModal()">&times;</button>
        </div>
        <form id="productForm" method="POST">
            <input type="hidden" name="action" id="formAction" value="add_product">
            <input type="hidden" name="product_id" id="productId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-box"></i> Product Name *</label>
                    <input type="text" name="product_name" id="productName" class="form-control" required placeholder="e.g., Anti Bird Net">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-barcode"></i> HSN Code *</label>
                        <input type="text" name="hsn_code" id="hsnCode" class="form-control" required placeholder="e.g., 39269099">
                        <small>8-digit HSN code for GST</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-percent"></i> GST % *</label>
                        <select name="gst_percentage" id="gstPercentage" class="form-control" required>
                            <option value="0">0% (Exempt)</option>
                            <option value="5">5%</option>
                            <option value="12">12%</option>
                            <option value="18">18%</option>
                            <option value="28">28%</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-rupee-sign"></i> Default Rate (Optional)</label>
                        <input type="number" name="default_rate" id="defaultRate" class="form-control" step="0.01" min="0" placeholder="0.00">
                        <small>Default price per unit</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-ruler"></i> Unit *</label>
                        <select name="unit" id="unit" class="form-control" required>
                            <option value="sqft">Square Feet (sqft)</option>
                            <option value="unit">Unit</option>
                            <option value="meter">Meter</option>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="piece">Piece</option>
                            <option value="set">Set</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeProductModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Product
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h2>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="delete_product">
            <input type="hidden" name="product_id" id="deleteProductId">
            
            <div class="modal-body">
                <p style="font-size: 16px; color: #475569;">
                    Are you sure you want to delete <strong id="deleteProductName"></strong>?
                </p>
                <p style="color: #EF4444; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> This action cannot be undone.
                </p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                    Cancel
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i> Delete Product
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Products Page Styles */
.products-page {
    padding: 20px;
    animation: fadeInUp 0.6s ease;
}

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

.products-table {
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

.gst-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 700;
}

.gst-5 {
    background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
    color: #065F46;
}

.gst-12 {
    background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
    color: #1E40AF;
}

.gst-18 {
    background: linear-gradient(135deg, #FEF3C7, #FDE68A);
    color: #92400E;
}

.gst-28 {
    background: linear-gradient(135deg, #FECACA, #FCA5A5);
    color: #991B1B;
}

.gst-0 {
    background: linear-gradient(135deg, #F1F5F9, #E2E8F0);
    color: #475569;
}

.unit-badge {
    display: inline-block;
    background: linear-gradient(135deg, #E9D5FF, #D8B4FE);
    color: #6B21A8;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-icon {
    width: 36px;
    height: 36px;
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

.btn-icon.danger:hover {
    background: linear-gradient(135deg, #EF4444, #DC2626);
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.modal-content {
    background: white;
    margin: 5% auto;
    max-width: 700px;
    border-radius: 20px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    padding: 24px 28px;
    border-bottom: 2px solid #E2E8F0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: linear-gradient(135deg, #F8FAFC, #F1F5F9);
    border-radius: 20px 20px 0 0;
}

.modal-header h2 {
    margin: 0;
    font-size: 22px;
    color: #1E293B;
}

.modal-close {
    background: none;
    border: none;
    font-size: 32px;
    color: #94A3B8;
    cursor: pointer;
    transition: all 0.3s;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.modal-close:hover {
    background: #FEE2E2;
    color: #EF4444;
    transform: rotate(90deg);
}

.modal-body {
    padding: 28px;
}

.modal-footer {
    padding: 20px 28px;
    border-top: 2px solid #E2E8F0;
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    background: #F8FAFC;
    border-radius: 0 0 20px 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-weight: 600;
    animation: slideDown 0.3s ease;
}

.alert-success {
    background: linear-gradient(135deg, #D1FAE5, #A7F3D0);
    color: #065F46;
    border: 2px solid #10B981;
}

.alert i {
    font-size: 20px;
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

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .modal-content {
        margin: 10% 20px;
    }
}
</style>

<script>
function showAddProductModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add New Product';
    document.getElementById('formAction').value = 'add_product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('productModal').style.display = 'block';
}

function editProduct(product) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Product';
    document.getElementById('formAction').value = 'update_product';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.product_name;
    document.getElementById('hsnCode').value = product.hsn_code;
    document.getElementById('gstPercentage').value = product.gst_percentage;
    document.getElementById('defaultRate').value = product.default_rate || '';
    document.getElementById('unit').value = product.unit;
    document.getElementById('productModal').style.display = 'block';
}

function closeProductModal() {
    document.getElementById('productModal').style.display = 'none';
}

function deleteProduct(id, name) {
    document.getElementById('deleteProductId').value = id;
    document.getElementById('deleteProductName').textContent = name;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const productModal = document.getElementById('productModal');
    const deleteModal = document.getElementById('deleteModal');
    if (event.target === productModal) {
        closeProductModal();
    }
    if (event.target === deleteModal) {
        closeDeleteModal();
    }
}
</script>

<?php include '../includes/footer.php'; ?>
