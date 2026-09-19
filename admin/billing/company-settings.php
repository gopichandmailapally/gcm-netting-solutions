<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Company Settings';
include '../includes/header.php';

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_company') {
            $logo_path = '';
            
            // Handle logo upload
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/company-logos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    $new_filename = 'company_' . time() . '.' . $file_extension;
                    $logo_path = 'uploads/company-logos/' . $new_filename;
                    move_uploaded_file($_FILES['company_logo']['tmp_name'], '../../' . $logo_path);
                }
            }
            
            $db->execute("INSERT INTO billing_company_settings (company_name, company_address, company_phone, company_email, company_gstin, company_pan, company_logo_path, color_theme, invoice_prefix, estimation_prefix, warranty_prefix, default_terms_conditions, default_warranty_terms) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [$_POST['company_name'], $_POST['company_address'], $_POST['company_phone'], $_POST['company_email'], $_POST['company_gstin'], $_POST['company_pan'], $logo_path, $_POST['color_theme'] ?? 'blue', $_POST['invoice_prefix'], $_POST['estimation_prefix'], $_POST['warranty_prefix'], $_POST['default_terms_conditions'], $_POST['default_warranty_terms']]
            );
            
            $_SESSION['success_message'] = "Company added successfully!";
            header('Location: company-settings.php');
            exit;
        }
        elseif ($_POST['action'] === 'update_company') {
            $id = intval($_POST['company_id']);
            $company = $db->fetchOne("SELECT company_logo_path FROM billing_company_settings WHERE id = ?", [$id]);
            $logo_path = $company['company_logo_path'];
            
            // Handle logo upload
            if (isset($_FILES['company_logo']) && $_FILES['company_logo']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../../uploads/company-logos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                $file_extension = strtolower(pathinfo($_FILES['company_logo']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'svg'];
                
                if (in_array($file_extension, $allowed_extensions)) {
                    // Delete old logo
                    if ($logo_path && file_exists('../../' . $logo_path)) {
                        unlink('../../' . $logo_path);
                    }
                    
                    $new_filename = 'company_' . time() . '.' . $file_extension;
                    $logo_path = 'uploads/company-logos/' . $new_filename;
                    move_uploaded_file($_FILES['company_logo']['tmp_name'], '../../' . $logo_path);
                }
            }
            
            $db->execute("UPDATE billing_company_settings SET company_name = ?, company_address = ?, company_phone = ?, company_email = ?, company_gstin = ?, company_pan = ?, company_logo_path = ?, color_theme = ?, invoice_prefix = ?, estimation_prefix = ?, warranty_prefix = ?, default_terms_conditions = ?, default_warranty_terms = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?",
                [$_POST['company_name'], $_POST['company_address'], $_POST['company_phone'], $_POST['company_email'], $_POST['company_gstin'], $_POST['company_pan'], $logo_path, $_POST['color_theme'] ?? 'blue', $_POST['invoice_prefix'], $_POST['estimation_prefix'], $_POST['warranty_prefix'], $_POST['default_terms_conditions'], $_POST['default_warranty_terms'], $id]
            );
            
            $_SESSION['success_message'] = "Company updated successfully!";
            header('Location: company-settings.php');
            exit;
        }
        elseif ($_POST['action'] === 'delete_company') {
            $id = intval($_POST['company_id']);
            $company = $db->fetchOne("SELECT company_logo_path FROM billing_company_settings WHERE id = ?", [$id]);
            
            // Delete logo file
            if ($company['company_logo_path'] && file_exists('../../' . $company['company_logo_path'])) {
                unlink('../../' . $company['company_logo_path']);
            }
            
            $db->execute("DELETE FROM billing_company_settings WHERE id = ?", [$id]);
            $_SESSION['success_message'] = "Company deleted successfully!";
            header('Location: company-settings.php');
            exit;
        }
    }
}

$companies = $db->fetchAll("SELECT * FROM billing_company_settings ORDER BY id ASC");
?>

<div class="company-settings-page">
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-building"></i> Company Settings</h1>
            <p>Manage multiple companies for billing</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <button class="btn btn-primary" onclick="window.location.href='add-company.php'">
                <i class="fas fa-plus"></i> Add Company
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="content-card" style="background: linear-gradient(135deg, #E0F2FE 0%, #DBEAFE 100%); border: 2px solid #3B82F6;">
        <div class="card-body">
            <h3 style="color: #1E40AF; margin-bottom: 12px;">
                <i class="fas fa-info-circle"></i> Multi-Company Support
            </h3>
            <ul style="color: #1E3A8A; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li><strong>Add multiple companies</strong> to generate bills for different businesses</li>
                <li><strong>Upload company logo</strong> - Will appear on all invoices and documents</li>
                <li><strong>Set GSTIN & PAN</strong> for GST compliance</li>
                <li><strong>Customize invoice prefixes</strong> for each company</li>
                <li><strong>Default terms & conditions</strong> auto-fill in documents</li>
            </ul>
        </div>
    </div>
    
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Companies (<?php echo count($companies); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($companies)): ?>
                <div class="empty-state">
                    <i class="fas fa-building"></i>
                    <h3>No companies added</h3>
                    <p>Add your first company to start generating invoices</p>
                    <button class="btn btn-primary" onclick="showAddCompanyModal()">
                        <i class="fas fa-plus"></i> Add Company
                    </button>
                </div>
            <?php else: ?>
                <div class="companies-grid">
                    <?php foreach ($companies as $company): ?>
                        <div class="company-card">
                            <div class="company-header">
                                <?php if ($company['company_logo_path']): ?>
                                    <img src="<?php echo SITE_URL . '/' . $company['company_logo_path']; ?>" alt="Logo" class="company-logo">
                                <?php else: ?>
                                    <div class="company-logo-placeholder">
                                        <i class="fas fa-building"></i>
                                    </div>
                                <?php endif; ?>
                                <h3><?php echo htmlspecialchars($company['company_name']); ?></h3>
                            </div>
                            
                            <div class="company-details">
                                <?php if ($company['company_address']): ?>
                                    <div class="detail-row">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo nl2br(htmlspecialchars($company['company_address'])); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($company['company_phone']): ?>
                                    <div class="detail-row">
                                        <i class="fas fa-phone"></i>
                                        <span><?php echo htmlspecialchars($company['company_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($company['company_email']): ?>
                                    <div class="detail-row">
                                        <i class="fas fa-envelope"></i>
                                        <span><?php echo htmlspecialchars($company['company_email']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($company['company_gstin']): ?>
                                    <div class="detail-row">
                                        <i class="fas fa-file-invoice"></i>
                                        <span><strong>GSTIN:</strong> <?php echo htmlspecialchars($company['company_gstin']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($company['company_pan']): ?>
                                    <div class="detail-row">
                                        <i class="fas fa-id-card"></i>
                                        <span><strong>PAN:</strong> <?php echo htmlspecialchars($company['company_pan']); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="prefixes">
                                    <span class="prefix-badge">INV: <?php echo $company['invoice_prefix']; ?></span>
                                    <span class="prefix-badge">EST: <?php echo $company['estimation_prefix']; ?></span>
                                    <span class="prefix-badge">WAR: <?php echo $company['warranty_prefix']; ?></span>
                                </div>
                            </div>
                            
                            <div class="company-actions">
                                <button class="btn btn-sm btn-primary" onclick="window.location.href='add-company.php?id=<?php echo $company['id']; ?>'">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <?php if ($company['id'] != 1): ?>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCompany(<?php echo $company['id']; ?>, '<?php echo htmlspecialchars($company['company_name']); ?>')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Company Modal -->
<div id="companyModal" class="modal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus"></i> Add Company</h2>
            <button class="modal-close" onclick="closeCompanyModal()">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" id="formAction" value="add_company">
            <input type="hidden" name="company_id" id="companyId">
            
            <div class="modal-body">
                <div class="form-section">
                    <h3><i class="fas fa-building"></i> Company Information</h3>
                    
                    <div class="form-group">
                        <label>Company Name *</label>
                        <input type="text" name="company_name" id="companyName" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Company Logo</label>
                        <input type="file" name="company_logo" id="companyLogo" class="form-control" accept="image/*">
                        <small>Recommended: PNG with transparent background, 200x80px</small>
                        <div id="logoPreview" style="margin-top: 10px;"></div>
                    </div>
                    
                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="company_address" id="companyAddress" class="form-control" rows="3"></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" name="company_phone" id="companyPhone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="company_email" id="companyEmail" class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>GSTIN</label>
                            <input type="text" name="company_gstin" id="companyGstin" class="form-control" placeholder="22AAAAA0000A1Z5">
                        </div>
                        <div class="form-group">
                            <label>PAN</label>
                            <input type="text" name="company_pan" id="companyPan" class="form-control" placeholder="AAAAA0000A">
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-cog"></i> Invoice Settings</h3>
                    
                    <div class="form-row form-row-three">
                        <div class="form-group">
                            <label>Invoice Prefix *</label>
                            <input type="text" name="invoice_prefix" id="invoicePrefix" class="form-control" value="INV" required>
                            <small>e.g., INV, BILL, TAX</small>
                        </div>
                        <div class="form-group">
                            <label>Estimation Prefix *</label>
                            <input type="text" name="estimation_prefix" id="estimationPrefix" class="form-control" value="EST" required>
                            <small>e.g., EST, QUOT, QTN</small>
                        </div>
                        <div class="form-group">
                            <label>Warranty Prefix *</label>
                            <input type="text" name="warranty_prefix" id="warrantyPrefix" class="form-control" value="WAR" required>
                            <small>e.g., WAR, CERT, WRT</small>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3><i class="fas fa-file-alt"></i> Default Terms</h3>
                    
                    <div class="form-group">
                        <label>Terms & Conditions</label>
                        <textarea name="default_terms_conditions" id="defaultTerms" class="form-control" rows="3" placeholder="Payment terms, delivery terms, etc."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Warranty Terms</label>
                        <textarea name="default_warranty_terms" id="defaultWarrantyTerms" class="form-control" rows="3" placeholder="Warranty coverage, exclusions, etc."></textarea>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeCompanyModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Company
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 500px;">
        <div class="modal-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h2>
            <button class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="delete_company">
            <input type="hidden" name="company_id" id="deleteCompanyId">
            
            <div class="modal-body">
                <p style="font-size: 16px; color: #475569;">
                    Are you sure you want to delete <strong id="deleteCompanyName"></strong>?
                </p>
                <p style="color: #EF4444; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> This will also delete the company logo.
                </p>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-trash"></i> Delete</button>
            </div>
        </form>
    </div>
</div>

<style>
.company-settings-page { padding: 20px; animation: fadeInUp 0.6s ease; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.companies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 24px; }

.company-card {
    background: linear-gradient(135deg, #FFFFFF 0%, #F8FAFC 100%);
    padding: 28px;
    border-radius: 16px;
    border: 2px solid #E2E8F0;
    transition: all 0.3s;
}

.company-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
    border-color: #3B82F6;
}

.company-header {
    text-align: center;
    padding-bottom: 20px;
    border-bottom: 2px solid #E2E8F0;
    margin-bottom: 20px;
}

.company-logo {
    max-width: 200px;
    max-height: 80px;
    margin-bottom: 12px;
}

.company-logo-placeholder {
    width: 80px;
    height: 80px;
    margin: 0 auto 12px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: white;
}

.company-header h3 {
    font-size: 20px;
    font-weight: 700;
    color: #1E293B;
    margin: 0;
}

.company-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 8px 0;
    font-size: 14px;
    color: #475569;
}

.detail-row i {
    color: #3B82F6;
    margin-top: 2px;
    width: 16px;
}

.prefixes {
    display: flex;
    gap: 8px;
    margin-top: 12px;
    flex-wrap: wrap;
}

.prefix-badge {
    background: linear-gradient(135deg, #E0F2FE, #DBEAFE);
    color: #1E40AF;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}

.company-actions {
    display: flex;
    gap: 8px;
}

.form-section {
    margin-bottom: 28px;
    padding-bottom: 28px;
    border-bottom: 2px solid #E2E8F0;
    clear: both;
    width: 100%;
    display: block;
}

.form-section:last-child {
    border-bottom: none;
    margin-bottom: 0;
    padding-bottom: 0;
}

.form-section h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 16px;
    display: block;
    width: 100%;
}

.modal-large {
    max-width: 900px;
}

.form-row-three {
    grid-template-columns: 1fr 1fr 1fr;
}

.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); overflow-y: auto; }
.modal-content { background: white; margin: 3% auto; max-width: 900px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: slideDown 0.3s ease; }
@keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header { padding: 24px 28px; border-bottom: 2px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #F8FAFC, #F1F5F9); border-radius: 20px 20px 0 0; }
.modal-header h2 { margin: 0; font-size: 22px; color: #1E293B; }
.modal-close { background: none; border: none; font-size: 32px; color: #94A3B8; cursor: pointer; transition: all 0.3s; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
.modal-close:hover { background: #FEE2E2; color: #EF4444; transform: rotate(90deg); }
.modal-body { 
    padding: 28px; 
    max-height: 70vh; 
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0;
}
.modal-footer { padding: 20px 28px; border-top: 2px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 12px; background: #F8FAFC; border-radius: 0 0 20px 20px; }
.form-group {
    margin-bottom: 20px;
    width: 100%;
    display: block;
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
    margin-top: 4px;
    color: #94A3B8;
    font-size: 12px;
}

.form-control {
    width: 100%;
    padding: 10px 14px;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; width: 100%; }
.form-row.three-cols { grid-template-columns: 1fr 1fr 1fr; }
.alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 600; }
.alert-success { background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: #065F46; border: 2px solid #10B981; }
.empty-state { text-align: center; padding: 60px 20px; }
.empty-state i { font-size: 64px; color: #CBD5E1; margin-bottom: 20px; }
.empty-state h3 { font-size: 20px; font-weight: 700; color: #475569; margin-bottom: 10px; }
.empty-state p { font-size: 15px; color: #94A3B8; margin-bottom: 24px; }

@media (max-width: 768px) {
    .companies-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
    .form-row.three-cols { grid-template-columns: 1fr; }
    .form-row-three { grid-template-columns: 1fr; }
    .modal-content { margin: 5% 10px; max-width: 100%; }
}
</style>

<script>
function showAddCompanyModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Company';
    document.getElementById('formAction').value = 'add_company';
    document.querySelector('#companyModal form').reset();
    document.getElementById('companyId').value = '';
    document.getElementById('logoPreview').innerHTML = '';
    document.getElementById('companyModal').style.display = 'block';
}

function editCompany(company) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Company';
    document.getElementById('formAction').value = 'update_company';
    document.getElementById('companyId').value = company.id;
    document.getElementById('companyName').value = company.company_name;
    document.getElementById('companyAddress').value = company.company_address || '';
    document.getElementById('companyPhone').value = company.company_phone || '';
    document.getElementById('companyEmail').value = company.company_email || '';
    document.getElementById('companyGstin').value = company.company_gstin || '';
    document.getElementById('companyPan').value = company.company_pan || '';
    document.getElementById('invoicePrefix').value = company.invoice_prefix;
    document.getElementById('estimationPrefix').value = company.estimation_prefix;
    document.getElementById('warrantyPrefix').value = company.warranty_prefix;
    document.getElementById('defaultTerms').value = company.default_terms_conditions || '';
    document.getElementById('defaultWarrantyTerms').value = company.default_warranty_terms || '';
    
    if (company.company_logo_path) {
        document.getElementById('logoPreview').innerHTML = '<img src="<?php echo SITE_URL; ?>/' + company.company_logo_path + '" style="max-width: 200px; max-height: 80px;">';
    }
    
    document.getElementById('companyModal').style.display = 'block';
}

function closeCompanyModal() {
    document.getElementById('companyModal').style.display = 'none';
}

function deleteCompany(id, name) {
    document.getElementById('deleteCompanyId').value = id;
    document.getElementById('deleteCompanyName').textContent = name;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

// Logo preview
document.getElementById('companyLogo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('logoPreview').innerHTML = '<img src="' + e.target.result + '" style="max-width: 200px; max-height: 80px; border: 2px solid #E2E8F0; border-radius: 8px; padding: 8px;">';
        }
        reader.readAsDataURL(file);
    }
});

window.onclick = function(event) {
    if (event.target === document.getElementById('companyModal')) closeCompanyModal();
    if (event.target === document.getElementById('deleteModal')) closeDeleteModal();
}
</script>

<?php include '../includes/footer.php'; ?>
