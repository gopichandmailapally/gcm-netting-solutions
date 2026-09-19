<?php
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Bank Details';
include '../includes/header.php';

$db = Database::getInstance();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_bank') {
            $is_default = isset($_POST['is_default']) ? 1 : 0;
            if ($is_default) {
                $db->execute("UPDATE billing_bank_details SET is_default = 0");
            }
            $db->execute("INSERT INTO billing_bank_details (bank_name, account_number, ifsc_code, branch_name, account_holder_name, upi_id, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$_POST['bank_name'], $_POST['account_number'], $_POST['ifsc_code'], $_POST['branch_name'], $_POST['account_holder_name'], $_POST['upi_id'], $is_default]
            );
            $_SESSION['success_message'] = "Bank details added successfully!";
            header('Location: bank-details.php');
            exit;
        }
        elseif ($_POST['action'] === 'update_bank') {
            $id = intval($_POST['bank_id']);
            $is_default = isset($_POST['is_default']) ? 1 : 0;
            if ($is_default) {
                $db->execute("UPDATE billing_bank_details SET is_default = 0");
            }
            $db->execute("UPDATE billing_bank_details SET bank_name = ?, account_number = ?, ifsc_code = ?, branch_name = ?, account_holder_name = ?, upi_id = ?, is_default = ? WHERE id = ?",
                [$_POST['bank_name'], $_POST['account_number'], $_POST['ifsc_code'], $_POST['branch_name'], $_POST['account_holder_name'], $_POST['upi_id'], $is_default, $id]
            );
            $_SESSION['success_message'] = "Bank details updated successfully!";
            header('Location: bank-details.php');
            exit;
        }
        elseif ($_POST['action'] === 'delete_bank') {
            $id = intval($_POST['bank_id']);
            $db->execute("DELETE FROM billing_bank_details WHERE id = ?", [$id]);
            $_SESSION['success_message'] = "Bank details deleted successfully!";
            header('Location: bank-details.php');
            exit;
        }
    }
}

$banks = $db->fetchAll("SELECT * FROM billing_bank_details ORDER BY is_default DESC, bank_name ASC");
?>

<div class="bank-details-page">
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-university"></i> Bank Details</h1>
            <p>Manage bank account information for invoices</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="window.location.href='index.php'">
                <i class="fas fa-arrow-left"></i> Back
            </button>
            <button class="btn btn-primary" onclick="window.location.href='add-bank.php'">
                <i class="fas fa-plus"></i> Add Bank Account
            </button>
        </div>
    </div>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="content-card" style="background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%); border: 2px solid #F59E0B;">
        <div class="card-body">
            <h3 style="color: #92400E; margin-bottom: 12px;">
                <i class="fas fa-info-circle"></i> Bank Details Usage
            </h3>
            <ul style="color: #78350F; line-height: 1.8; margin: 0; padding-left: 20px;">
                <li>Bank details are <strong>mandatory for GST invoices and estimations</strong></li>
                <li>Set one account as <strong>default</strong> for quick selection</li>
                <li>Include UPI ID for digital payment options</li>
                <li>Bank details will be printed on invoices and estimations</li>
            </ul>
        </div>
    </div>
    
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-list"></i> Bank Accounts (<?php echo count($banks); ?>)</h2>
        </div>
        <div class="card-body">
            <?php if (empty($banks)): ?>
                <div class="empty-state">
                    <i class="fas fa-university"></i>
                    <h3>No bank accounts added</h3>
                    <p>Add your first bank account to include in invoices</p>
                    <button class="btn btn-primary" onclick="showAddBankModal()">
                        <i class="fas fa-plus"></i> Add Bank Account
                    </button>
                </div>
            <?php else: ?>
                <div class="banks-grid">
                    <?php foreach ($banks as $bank): ?>
                        <div class="bank-card <?php echo $bank['is_default'] ? 'default' : ''; ?>">
                            <?php if ($bank['is_default']): ?>
                                <div class="default-badge">
                                    <i class="fas fa-star"></i> Default
                                </div>
                            <?php endif; ?>
                            <div class="bank-header">
                                <i class="fas fa-university"></i>
                                <h3><?php echo htmlspecialchars($bank['bank_name']); ?></h3>
                            </div>
                            <div class="bank-details">
                                <div class="detail-row">
                                    <span class="label">Account Holder:</span>
                                    <span class="value"><?php echo htmlspecialchars($bank['account_holder_name']); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">Account Number:</span>
                                    <span class="value"><code><?php echo htmlspecialchars($bank['account_number']); ?></code></span>
                                </div>
                                <div class="detail-row">
                                    <span class="label">IFSC Code:</span>
                                    <span class="value"><code><?php echo htmlspecialchars($bank['ifsc_code']); ?></code></span>
                                </div>
                                <?php if ($bank['branch_name']): ?>
                                    <div class="detail-row">
                                        <span class="label">Branch:</span>
                                        <span class="value"><?php echo htmlspecialchars($bank['branch_name']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($bank['upi_id']): ?>
                                    <div class="detail-row">
                                        <span class="label">UPI ID:</span>
                                        <span class="value"><code><?php echo htmlspecialchars($bank['upi_id']); ?></code></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="bank-actions">
                                <button class="btn btn-sm btn-primary" onclick="window.location.href='add-bank.php?id=<?php echo $bank['id']; ?>'">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="deleteBank(<?php echo $bank['id']; ?>, '<?php echo htmlspecialchars($bank['bank_name']); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Bank Modal -->
<div id="bankModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"><i class="fas fa-plus"></i> Add Bank Account</h2>
            <button class="modal-close" onclick="closeBankModal()">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" id="formAction" value="add_bank">
            <input type="hidden" name="bank_id" id="bankId">
            
            <div class="modal-body">
                <div class="form-group">
                    <label><i class="fas fa-university"></i> Bank Name *</label>
                    <input type="text" name="bank_name" id="bankName" class="form-control" required placeholder="e.g., HDFC Bank">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Account Holder Name *</label>
                    <input type="text" name="account_holder_name" id="accountHolderName" class="form-control" required placeholder="e.g., GCM Netting Solutions">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Account Number *</label>
                        <input type="text" name="account_number" id="accountNumber" class="form-control" required placeholder="e.g., 50200012345678">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-code"></i> IFSC Code *</label>
                        <input type="text" name="ifsc_code" id="ifscCode" class="form-control" required placeholder="e.g., HDFC0001234">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Branch Name</label>
                    <input type="text" name="branch_name" id="branchName" class="form-control" placeholder="e.g., Chennai Main Branch">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-mobile-alt"></i> UPI ID (Optional)</label>
                    <input type="text" name="upi_id" id="upiId" class="form-control" placeholder="e.g., gcmsafetynets@upi">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_default" id="isDefault">
                        <span>Set as default bank account</span>
                    </label>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeBankModal()">
                    <i class="fas fa-times"></i> Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Bank Details
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
            <input type="hidden" name="action" value="delete_bank">
            <input type="hidden" name="bank_id" id="deleteBankId">
            
            <div class="modal-body">
                <p style="font-size: 16px; color: #475569;">
                    Are you sure you want to delete <strong id="deleteBankName"></strong>?
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
.bank-details-page { padding: 20px; animation: fadeInUp 0.6s ease; }
@keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

.banks-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px; }

.bank-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
    backdrop-filter: blur(10px);
    padding: 24px;
    border-radius: 16px;
    border: 2px solid #E2E8F0;
    transition: all 0.3s;
    position: relative;
}

.bank-card.default {
    border-color: #F59E0B;
    background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
}

.bank-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
}

.default-badge {
    position: absolute;
    top: 12px;
    right: 12px;
    background: linear-gradient(135deg, #F59E0B, #D97706);
    color: white;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
}

.bank-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 2px solid #E2E8F0;
}

.bank-header i {
    font-size: 28px;
    color: #3B82F6;
}

.bank-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin: 0;
}

.bank-details {
    margin-bottom: 16px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    font-size: 14px;
}

.detail-row .label {
    color: #64748B;
    font-weight: 500;
}

.detail-row .value {
    color: #1E293B;
    font-weight: 600;
}

.detail-row code {
    background: #F1F5F9;
    padding: 2px 8px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
}

.bank-actions {
    display: flex;
    gap: 8px;
}

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 8px;
    cursor: pointer;
    font-weight: 600;
    color: #475569;
}

.checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.6); backdrop-filter: blur(4px); }
.modal-content { background: white; margin: 5% auto; max-width: 700px; border-radius: 20px; box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3); animation: slideDown 0.3s ease; }
@keyframes slideDown { from { transform: translateY(-50px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header { padding: 24px 28px; border-bottom: 2px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #F8FAFC, #F1F5F9); border-radius: 20px 20px 0 0; }
.modal-header h2 { margin: 0; font-size: 22px; color: #1E293B; }
.modal-close { background: none; border: none; font-size: 32px; color: #94A3B8; cursor: pointer; transition: all 0.3s; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; border-radius: 8px; }
.modal-close:hover { background: #FEE2E2; color: #EF4444; transform: rotate(90deg); }
.modal-body { padding: 28px; }
.modal-footer { padding: 20px 28px; border-top: 2px solid #E2E8F0; display: flex; justify-content: flex-end; gap: 12px; background: #F8FAFC; border-radius: 0 0 20px 20px; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-weight: 600; }
.alert-success { background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: #065F46; border: 2px solid #10B981; }
.empty-state { text-align: center; padding: 60px 20px; }
.empty-state i { font-size: 64px; color: #CBD5E1; margin-bottom: 20px; }
.empty-state h3 { font-size: 20px; font-weight: 700; color: #475569; margin-bottom: 10px; }
.empty-state p { font-size: 15px; color: #94A3B8; margin-bottom: 24px; }

@media (max-width: 768px) {
    .banks-grid { grid-template-columns: 1fr; }
    .form-row { grid-template-columns: 1fr; }
}
</style>

<script>
function showAddBankModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus"></i> Add Bank Account';
    document.getElementById('formAction').value = 'add_bank';
    document.querySelector('#bankModal form').reset();
    document.getElementById('bankId').value = '';
    document.getElementById('bankModal').style.display = 'block';
}

function editBank(bank) {
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Edit Bank Account';
    document.getElementById('formAction').value = 'update_bank';
    document.getElementById('bankId').value = bank.id;
    document.getElementById('bankName').value = bank.bank_name;
    document.getElementById('accountHolderName').value = bank.account_holder_name;
    document.getElementById('accountNumber').value = bank.account_number;
    document.getElementById('ifscCode').value = bank.ifsc_code;
    document.getElementById('branchName').value = bank.branch_name || '';
    document.getElementById('upiId').value = bank.upi_id || '';
    document.getElementById('isDefault').checked = bank.is_default == 1;
    document.getElementById('bankModal').style.display = 'block';
}

function closeBankModal() {
    document.getElementById('bankModal').style.display = 'none';
}

function deleteBank(id, name) {
    document.getElementById('deleteBankId').value = id;
    document.getElementById('deleteBankName').textContent = name;
    document.getElementById('deleteModal').style.display = 'block';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
}

window.onclick = function(event) {
    if (event.target === document.getElementById('bankModal')) closeBankModal();
    if (event.target === document.getElementById('deleteModal')) closeDeleteModal();
}
</script>

<?php include '../includes/footer.php'; ?>
