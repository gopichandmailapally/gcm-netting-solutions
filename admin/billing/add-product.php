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

// Handle form submission BEFORE any output
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if table exists first
        $tableCheck = $db->fetchOne("SELECT name FROM sqlite_master WHERE type='table' AND name='billing_products'");
        if (!$tableCheck) {
            throw new Exception("Billing tables not initialized! Please run init-billing-db.php first.");
        }
        
        $editing = isset($_POST['product_id']) && !empty($_POST['product_id']);
        
        if ($editing) {
            $result = $db->execute("UPDATE billing_products SET product_name = ?, hsn_code = ?, gst_percentage = ?, default_rate = ?, unit = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$_POST['product_name'], $_POST['hsn_code'], floatval($_POST['gst_percentage']), floatval($_POST['default_rate']), $_POST['unit'], intval($_POST['product_id'])]
            );
            if ($result === false) {
                throw new Exception("Failed to update product in database.");
            }
            $_SESSION['success_message'] = "Product updated successfully!";
        } else {
            $result = $db->execute("INSERT INTO billing_products (product_name, hsn_code, gst_percentage, default_rate, unit, is_active) VALUES (?, ?, ?, ?, ?, 1)",
                [$_POST['product_name'], $_POST['hsn_code'], floatval($_POST['gst_percentage']), floatval($_POST['default_rate']), $_POST['unit']]
            );
            if ($result === false) {
                throw new Exception("Failed to insert product into database.");
            }
            $insertId = $db->lastInsertId();
            $_SESSION['success_message'] = "Product added successfully!";
        }
        
        header('Location: products.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Check if editing
$editing = false;
$product = null;
if (isset($_GET['id'])) {
    $editing = true;
    $product = $db->fetchOne("SELECT * FROM billing_products WHERE id = ?", [intval($_GET['id'])]);
    if (!$product) {
        $_SESSION['error_message'] = "Product not found!";
        header('Location: products.php');
        exit;
    }
}

$page_title = 'Add Product';
include '../includes/header.php';
?>

<div class="add-product-page">
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-<?php echo $editing ? 'edit' : 'plus'; ?>"></i> <?php echo $editing ? 'Edit' : 'Add'; ?> Product</h1>
            <p><?php echo $editing ? 'Update product information' : 'Add a new product to the catalog'; ?></p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="window.location.href='products.php'">
                <i class="fas fa-arrow-left"></i> Back to Products
            </button>
        </div>
    </div>
    
    <div class="content-card" style="background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%); border: 2px solid #3B82F6;">
        <div class="card-body">
            <h3 style="color: #1E40AF; margin-bottom: 12px;">
                <i class="fas fa-info-circle"></i> Important Information
            </h3>
            <ul style="color: #1E3A8A; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li><strong>HSN Code:</strong> 8-digit Harmonized System of Nomenclature code for GST compliance</li>
                <li><strong>GST %:</strong> Goods and Services Tax percentage (0%, 5%, 12%, 18%, 28%)</li>
                <li><strong>Default Rate:</strong> Optional default price per unit for quick billing</li>
                <li><strong>Unit:</strong> Measurement unit (sqft, unit, meter, kg, etc.)</li>
                <li><strong>⚠️ Changes take effect immediately</strong> - All new bills will use updated HSN and GST rates</li>
            </ul>
        </div>
    </div>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST">
        <?php if ($editing): ?>
            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
        <?php endif; ?>
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-box"></i> Product Information</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><i class="fas fa-box"></i> Product Name *</label>
                    <input type="text" name="product_name" class="form-control" value="<?php echo $editing ? htmlspecialchars($product['product_name']) : ''; ?>" required placeholder="e.g., Anti Bird Net">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-barcode"></i> HSN Code *</label>
                        <input type="text" name="hsn_code" class="form-control" value="<?php echo $editing ? htmlspecialchars($product['hsn_code']) : ''; ?>" required placeholder="e.g., 39269099">
                        <small>8-digit HSN code for GST</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-percent"></i> GST % *</label>
                        <select name="gst_percentage" class="form-control" required>
                            <option value="0" <?php echo ($editing && $product['gst_percentage'] == 0) ? 'selected' : ''; ?>>0% (Exempt)</option>
                            <option value="5" <?php echo ($editing && $product['gst_percentage'] == 5) ? 'selected' : ''; ?>>5%</option>
                            <option value="12" <?php echo ($editing && $product['gst_percentage'] == 12) ? 'selected' : ''; ?>>12%</option>
                            <option value="18" <?php echo ($editing && $product['gst_percentage'] == 18) ? 'selected' : ''; ?>>18%</option>
                            <option value="28" <?php echo ($editing && $product['gst_percentage'] == 28) ? 'selected' : ''; ?>>28%</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-rupee-sign"></i> Default Rate (Optional)</label>
                        <input type="number" name="default_rate" class="form-control" value="<?php echo $editing ? $product['default_rate'] : ''; ?>" step="0.01" min="0" placeholder="0.00">
                        <small>Default price per unit</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-ruler"></i> Unit *</label>
                        <select name="unit" class="form-control" required>
                            <option value="sqft" <?php echo ($editing && $product['unit'] == 'sqft') ? 'selected' : ''; ?>>Square Feet (sqft)</option>
                            <option value="unit" <?php echo ($editing && $product['unit'] == 'unit') ? 'selected' : ''; ?>>Unit</option>
                            <option value="meter" <?php echo ($editing && $product['unit'] == 'meter') ? 'selected' : ''; ?>>Meter</option>
                            <option value="kg" <?php echo ($editing && $product['unit'] == 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                            <option value="piece" <?php echo ($editing && $product['unit'] == 'piece') ? 'selected' : ''; ?>>Piece</option>
                            <option value="set" <?php echo ($editing && $product['unit'] == 'set') ? 'selected' : ''; ?>>Set</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-large">
                <i class="fas fa-save"></i> <?php echo $editing ? 'Update' : 'Save'; ?> Product
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='products.php'">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </form>
</div>

<style>
.add-product-page { padding: 20px; animation: fadeInUp 0.6s ease; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #475569;
    font-size: 14px;
}

.form-group label i {
    color: #3B82F6;
    margin-right: 6px;
}

.form-group small {
    display: block;
    margin-top: 6px;
    color: #94A3B8;
    font-size: 12px;
}

.form-control {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #E2E8F0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
    font-family: inherit;
}

.form-control:focus {
    outline: none;
    border-color: #3B82F6;
    box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 12px;
    padding: 20px;
    background: #F8FAFC;
    border-radius: 12px;
}

.btn-large {
    padding: 14px 32px;
    font-size: 16px;
    font-weight: 600;
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

.alert-error {
    background: linear-gradient(135deg, #FEE2E2, #FECACA);
    color: #991B1B;
    border: 2px solid #EF4444;
}

@media (max-width: 768px) {
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include '../includes/footer.php'; ?>
