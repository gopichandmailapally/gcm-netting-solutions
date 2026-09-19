<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Add Company';
include '../includes/header.php';

$db = Database::getInstance();

// Check if editing
$editing = false;
$company = null;
if (isset($_GET['id'])) {
    $editing = true;
    $company = $db->fetchOne("SELECT * FROM billing_company_settings WHERE id = ?", [intval($_GET['id'])]);
    if (!$company) {
        $_SESSION['error_message'] = "Company not found!";
        header('Location: company-settings.php');
        exit;
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $logo_path = $editing ? $company['company_logo_path'] : '';
        
        // Handle logo upload
        if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/company-logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
            
            if (in_array($file_extension, $allowed_extensions)) {
                // Delete old logo if editing
                if ($editing && $logo_path && file_exists('../../' . $logo_path)) {
                    unlink('../../' . $logo_path);
                }
                
                $new_filename = 'company_' . time() . '.' . $file_extension;
                $logo_path = 'uploads/company-logos/' . $new_filename;
                move_uploaded_file($_FILES['company_logo']['tmp_name'], '../../' . $logo_path);
            }
        }
        
        if ($editing) {
            $result = $db->execute("UPDATE billing_company_settings SET company_name = ?, company_address = ?, company_phone = ?, company_email = ?, company_gstin = ?, company_pan = ?, company_logo_path = ?, color_theme = ?, invoice_prefix = ?, estimation_prefix = ?, warranty_prefix = ?, default_terms_conditions = ?, default_warranty_terms = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$_POST['company_name'], $_POST['company_address'], $_POST['company_phone'], $_POST['company_email'], $_POST['company_gstin'], $_POST['company_pan'], $logo_path, $_POST['color_theme'], $_POST['invoice_prefix'], $_POST['estimation_prefix'], $_POST['warranty_prefix'], $_POST['default_terms_conditions'], $_POST['default_warranty_terms'], $company['id']]
            );
            $_SESSION['success_message'] = "Company updated successfully!";
        } else {
            $result = $db->execute("INSERT INTO billing_company_settings (company_name, company_address, company_phone, company_email, company_gstin, company_pan, company_logo_path, color_theme, invoice_prefix, estimation_prefix, warranty_prefix, default_terms_conditions, default_warranty_terms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$_POST['company_name'], $_POST['company_address'], $_POST['company_phone'], $_POST['company_email'], $_POST['company_gstin'], $_POST['company_pan'], $logo_path, $_POST['color_theme'], $_POST['invoice_prefix'], $_POST['estimation_prefix'], $_POST['warranty_prefix'], $_POST['default_terms_conditions'], $_POST['default_warranty_terms']]
            );
            $_SESSION['success_message'] = "Company added successfully!";
        }
        
        header('Location: company-settings.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}
?>

<div class="add-company-page">
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-<?php echo $editing ? 'edit' : 'plus'; ?>"></i> <?php echo $editing ? 'Edit' : 'Add'; ?> Company</h1>
            <p><?php echo $editing ? 'Update company information' : 'Add a new company for billing'; ?></p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="window.location.href='company-settings.php'">
                <i class="fas fa-arrow-left"></i> Back to Companies
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" enctype="multipart/form-data">
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-building"></i> Company Information</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Company Name *</label>
                    <input type="text" name="company_name" class="form-control" value="<?php echo $editing ? htmlspecialchars($company['company_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label>Company Logo</label>
                    <?php if ($editing && $company['company_logo_path']): ?>
                        <div style="margin-bottom: 10px;">
                            <img src="<?php echo SITE_URL . '/' . $company['company_logo_path']; ?>" style="max-width: 200px; max-height: 80px; border: 2px solid #E2E8F0; border-radius: 8px; padding: 8px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" name="company_logo" id="companyLogo" class="form-control" accept="image/*">
                    <small>Recommended: PNG with transparent background, 200x80px</small>
                    <div id="logoPreview" style="margin-top: 10px;"></div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="company_address" class="form-control" rows="3"><?php echo $editing ? htmlspecialchars($company['company_address']) : ''; ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-image"></i> Company Logo</label>
                        <input type="file" name="company_logo" class="form-control" accept="image/*">
                        <small>Upload company logo (JPG, PNG, GIF, SVG)</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-palette"></i> Invoice Color Theme *</label>
                        <select name="color_theme" class="form-control" required>
                            <option value="blue" <?php echo ($editing && $company['color_theme'] == 'blue') ? 'selected' : ''; ?>>🔵 Professional Blue (Default)</option>
                            <option value="green" <?php echo ($editing && $company['color_theme'] == 'green') ? 'selected' : ''; ?>>🟢 Success Green</option>
                            <option value="purple" <?php echo ($editing && $company['color_theme'] == 'purple') ? 'selected' : ''; ?>>🟣 Royal Purple</option>
                            <option value="orange" <?php echo ($editing && $company['color_theme'] == 'orange') ? 'selected' : ''; ?>>🟠 Elegant Orange</option>
                            <option value="teal" <?php echo ($editing && $company['color_theme'] == 'teal') ? 'selected' : ''; ?>>🔷 Modern Teal</option>
                            <option value="red" <?php echo ($editing && $company['color_theme'] == 'red') ? 'selected' : ''; ?>>🔴 Bold Red</option>
                            <option value="navy" <?php echo ($editing && $company['color_theme'] == 'navy') ? 'selected' : ''; ?>>🔵 Corporate Navy</option>
                            <option value="gold" <?php echo ($editing && $company['color_theme'] == 'gold') ? 'selected' : ''; ?>>🟡 Luxury Gold</option>
                        </select>
                        <small>Choose color theme for all invoices and documents</small>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="company_email" class="form-control" value="<?php echo $editing ? htmlspecialchars($company['company_email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>GSTIN</label>
                        <input type="text" name="company_gstin" class="form-control" placeholder="22AAAAA0000A1Z5" value="<?php echo $editing ? htmlspecialchars($company['company_gstin']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>PAN</label>
                        <input type="text" name="company_pan" class="form-control" placeholder="AAAAA0000A" value="<?php echo $editing ? htmlspecialchars($company['company_pan']) : ''; ?>">
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-cog"></i> Invoice Settings</h2>
            </div>
            <div class="card-body">
                <div class="form-row form-row-three">
                    <div class="form-group">
                        <label>Invoice Prefix *</label>
                        <input type="text" name="invoice_prefix" class="form-control" value="<?php echo $editing ? htmlspecialchars($company['invoice_prefix']) : 'INV'; ?>" required>
                        <small>e.g., INV, BILL, TAX</small>
                    </div>
                    <div class="form-group">
                        <label>Estimation Prefix *</label>
                        <input type="text" name="estimation_prefix" class="form-control" value="<?php echo $editing ? htmlspecialchars($company['estimation_prefix']) : 'EST'; ?>" required>
                        <small>e.g., EST, QUOT, QTN</small>
                    </div>
                    <div class="form-group">
                        <label>Warranty Prefix *</label>
                        <input type="text" name="warranty_prefix" class="form-control" value="<?php echo $editing ? htmlspecialchars($company['warranty_prefix']) : 'WAR'; ?>" required>
                        <small>e.g., WAR, CERT, WRT</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-file-alt"></i> Default Terms</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label>Terms & Conditions</label>
                    <textarea name="default_terms_conditions" class="form-control" rows="4" placeholder="Payment terms, delivery terms, etc."><?php echo $editing ? htmlspecialchars($company['default_terms_conditions']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Warranty Terms</label>
                    <textarea name="default_warranty_terms" class="form-control" rows="4" placeholder="Warranty coverage, exclusions, etc."><?php echo $editing ? htmlspecialchars($company['default_warranty_terms']) : ''; ?></textarea>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-large">
                <i class="fas fa-save"></i> <?php echo $editing ? 'Update' : 'Save'; ?> Company
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='company-settings.php'">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </form>
</div>

<style>
.add-company-page { padding: 20px; animation: fadeInUp 0.6s ease; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.form-row-three { grid-template-columns: 1fr 1fr 1fr; }

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

textarea.form-control {
    resize: vertical;
    min-height: 100px;
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
    .form-row, .form-row-three {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Logo preview
document.getElementById('companyLogo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').innerHTML = '<div style="margin-top: 10px;"><strong>Preview:</strong><br><img src="' + e.target.result + '" style="max-width: 200px; max-height: 80px; border: 2px solid #E2E8F0; border-radius: 8px; padding: 8px; margin-top: 8px;"></div>';
        }
        reader.readAsDataURL(file);
    }
});
</script>

<?php include '../includes/footer.php'; ?>
