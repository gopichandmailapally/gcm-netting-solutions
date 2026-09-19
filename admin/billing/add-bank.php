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
        $is_default = isset($_POST['is_default']) ? 1 : 0;
        if ($is_default) {
            $db->execute("UPDATE billing_bank_details SET is_default = 0");
        }
        
        $editing = isset($_POST['bank_id']) && !empty($_POST['bank_id']);
        
        if ($editing) {
            $db->execute("UPDATE billing_bank_details SET bank_name = ?, account_number = ?, ifsc_code = ?, branch_name = ?, account_holder_name = ?, upi_id = ?, is_default = ? WHERE id = ?",
                [$_POST['bank_name'], $_POST['account_number'], $_POST['ifsc_code'], $_POST['branch_name'], $_POST['account_holder_name'], $_POST['upi_id'], $is_default, intval($_POST['bank_id'])]
            );
            $_SESSION['success_message'] = "Bank details updated successfully!";
        } else {
            $db->execute("INSERT INTO billing_bank_details (bank_name, account_number, ifsc_code, branch_name, account_holder_name, upi_id, is_default) VALUES (?, ?, ?, ?, ?, ?, ?)",
                [$_POST['bank_name'], $_POST['account_number'], $_POST['ifsc_code'], $_POST['branch_name'], $_POST['account_holder_name'], $_POST['upi_id'], $is_default]
            );
            $_SESSION['success_message'] = "Bank details added successfully!";
        }
        
        header('Location: bank-details.php');
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

// Check if editing
$editing = false;
$bank = null;
if (isset($_GET['id'])) {
    $editing = true;
    $bank = $db->fetchOne("SELECT * FROM billing_bank_details WHERE id = ?", [intval($_GET['id'])]);
    if (!$bank) {
        $_SESSION['error_message'] = "Bank account not found!";
        header('Location: bank-details.php');
        exit;
    }
}

$page_title = 'Add Bank Account';
include '../includes/header.php';
?>

<div class="add-bank-page">
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-<?php echo $editing ? 'edit' : 'plus'; ?>"></i> <?php echo $editing ? 'Edit' : 'Add'; ?> Bank Account</h1>
            <p><?php echo $editing ? 'Update bank account information' : 'Add a new bank account for invoices'; ?></p>
        </div>
        <div class="header-actions">
            <button class="btn btn-secondary" onclick="window.location.href='bank-details.php'">
                <i class="fas fa-arrow-left"></i> Back to Bank Details
            </button>
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
            <input type="hidden" name="bank_id" value="<?php echo $bank['id']; ?>">
        <?php endif; ?>
        <div class="content-card">
            <div class="card-header">
                <h2><i class="fas fa-university"></i> Bank Account Information</h2>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label><i class="fas fa-university"></i> Bank Name *</label>
                    <input type="text" name="bank_name" class="form-control" value="<?php echo $editing ? htmlspecialchars($bank['bank_name']) : ''; ?>" required placeholder="e.g., HDFC Bank">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Account Holder Name *</label>
                    <input type="text" name="account_holder_name" class="form-control" value="<?php echo $editing ? htmlspecialchars($bank['account_holder_name']) : ''; ?>" required placeholder="e.g., GCM Netting Solutions">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Account Number *</label>
                        <input type="text" name="account_number" class="form-control" value="<?php echo $editing ? htmlspecialchars($bank['account_number']) : ''; ?>" required placeholder="e.g., 50200012345678">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-code"></i> IFSC Code *</label>
                        <input type="text" name="ifsc_code" class="form-control" value="<?php echo $editing ? htmlspecialchars($bank['ifsc_code']) : ''; ?>" required placeholder="e.g., HDFC0001234">
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-map-marker-alt"></i> Branch Name</label>
                    <input type="text" name="branch_name" class="form-control" value="<?php echo $editing ? htmlspecialchars($bank['branch_name']) : ''; ?>" placeholder="e.g., Chennai Main Branch">
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-mobile-alt"></i> UPI ID (Optional)</label>
                    <input type="text" name="upi_id" class="form-control" value="<?php echo $editing ? htmlspecialchars($bank['upi_id']) : ''; ?>" placeholder="e.g., gcmsafetynets@upi">
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_default" <?php echo ($editing && $bank['is_default']) ? 'checked' : ''; ?>>
                        <span><i class="fas fa-star"></i> Set as default bank account</span>
                    </label>
                    <small>Default account will be pre-selected in GST invoices and estimations</small>
                </div>
            </div>
        </div>
        
        <div class="form-actions">
            <button type="submit" class="btn btn-success btn-large">
                <i class="fas fa-save"></i> <?php echo $editing ? 'Update' : 'Save'; ?> Bank Details
            </button>
            <button type="button" class="btn btn-secondary" onclick="window.location.href='bank-details.php'">
                <i class="fas fa-times"></i> Cancel
            </button>
        </div>
    </form>
</div>

<style>
.add-bank-page { padding: 20px; animation: fadeInUp 0.6s ease; }
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

.checkbox-label {
    display: flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
    font-weight: 600;
    color: #475569;
    padding: 12px;
    background: #F8FAFC;
    border-radius: 8px;
    transition: all 0.3s;
}

.checkbox-label:hover {
    background: #F0F9FF;
    border-color: #3B82F6;
}

.checkbox-label input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
}

.checkbox-label i {
    color: #F59E0B;
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
