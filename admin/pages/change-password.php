<?php
/**
 * Change Password — replaced by Account Manager.
 * This file is kept only to redirect old bookmarks / links.
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
header('Location: ' . SITE_URL . '/admin/pages/account-manager.php', true, 301);
exit;

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$security = new AdminSecurity();

$page_title = 'Account Settings';

$message = '';
$error = '';

// Load database
require_once '../../config/database.php';
try {
    $db = Database::getInstance();
} catch (Exception $e) {
    error_log("Database connection error in change-password.php: " . $e->getMessage());
    die("Database connection error. Please contact administrator.");
}

// Get current admin user from database
$admin_id = $_SESSION['admin_id'] ?? null;
$current_user = null;

if ($admin_id) {
    $current_user = $db->fetchOne(
        "SELECT * FROM admin_users WHERE id = ? AND is_active = 1",
        [$admin_id],
        'i'
    );
}

if (!$current_user) {
    // Fallback: try to get by username from session
    $admin_username = $_SESSION['admin_username'] ?? 'admin';
    $current_user = $db->fetchOne(
        "SELECT * FROM admin_users WHERE username = ? AND is_active = 1",
        [$admin_username],
        's'
    );
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    // Verify CSRF token
    if (!$security->verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please refresh the page and try again.';
        $security->logSecurityEvent($_SESSION['admin_id'], 'csrf_token_invalid', 'Action: ' . $action);
    }
    elseif ($action === 'change_username') {
        $new_username = trim($_POST['new_username'] ?? '');
        $current_password = $_POST['current_password'] ?? '';
        
        if (empty($new_username) || empty($current_password)) {
            $error = 'All fields are required.';
        } elseif (!password_verify($current_password, $current_user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($new_username) < 3) {
            $error = 'Username must be at least 3 characters long.';
        } else {
            // Check if username already exists
            $existing = $db->fetchOne(
                "SELECT id FROM admin_users WHERE username = ? AND id != ?",
                [$new_username, $current_user['id']],
                'si'
            );
            
            if ($existing) {
                $error = 'Username already taken.';
            } else {
                try {
                    $conn = $db->getConnection();
                    $stmt = $conn->prepare("UPDATE admin_users SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    
                    if ($stmt->execute([$new_username, $current_user['id']])) {
                        $_SESSION['admin_username'] = $new_username;
                        $current_user['username'] = $new_username;
                        $message = 'Username changed successfully!';
                        
                        // Log security event
                        try {
                            $security->logSecurityEvent($current_user['id'], 'username_changed', 'Old: ' . $current_user['username'] . ', New: ' . $new_username);
                        } catch (Exception $e) {
                            error_log("Failed to log security event: " . $e->getMessage());
                        }
                    } else {
                        $error = 'Failed to update username. Please try again.';
                        $errorInfo = $stmt->errorInfo();
                        error_log("Username update failed: " . ($errorInfo[2] ?? 'Unknown error'));
                    }
                } catch (Exception $e) {
                    $error = 'An error occurred while changing username. Please try again.';
                    error_log("Username change error: " . $e->getMessage());
                }
            }
        }
    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif (!password_verify($current_password, $current_user['password_hash'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New passwords do not match.';
        } else {
            // Validate password strength
            $validation = $security->validatePasswordStrength($new_password);
            if (!$validation['valid']) {
                $error = 'Password does not meet security requirements:<br>' . implode('<br>', $validation['errors']);
            }
        }
        
        if (empty($error)) {
            try {
                // Use strong password hashing
                $new_password_hash = $security->hashPassword($new_password);
                
                $conn = $db->getConnection();
                $stmt = $conn->prepare("UPDATE admin_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                
                if ($stmt->execute([$new_password_hash, $current_user['id']])) {
                    $message = 'Password changed successfully!';
                    
                    // Log security event
                    try {
                        $security->logSecurityEvent($current_user['id'], 'password_changed', 'Password updated successfully');
                    } catch (Exception $e) {
                        error_log("Failed to log security event: " . $e->getMessage());
                    }
                    
                    // Invalidate all other sessions for this user
                    try {
                        $session_id = session_id();
                        $stmt2 = $conn->prepare("DELETE FROM admin_sessions WHERE admin_id = ? AND session_id != ?");
                        $stmt2->execute([$current_user['id'], $session_id]);
                    } catch (Exception $e) {
                        error_log("Failed to invalidate sessions: " . $e->getMessage());
                    }
                } else {
                    $error = 'Failed to update password. Please try again.';
                    $errorInfo = $stmt->errorInfo();
                    error_log("Password update failed: " . ($errorInfo[2] ?? 'Unknown error'));
                }
            } catch (Exception $e) {
                $error = 'An error occurred while changing password. Please try again.';
                error_log("Password change error: " . $e->getMessage());
            }
        }
    }
}

$page_title = 'Account Settings';
include '../includes/header.php';
?>

<div class="change-password-container">
    <div class="page-header">
        <h1><i class="fas fa-user-cog"></i> Account Settings</h1>
        <p>Update your username and password</p>
    </div>
    
    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <!-- Change Username Section -->
    <div class="settings-card">
        <h3><i class="fas fa-user"></i> Change Username</h3>
        <p>Current username: <strong><?php echo htmlspecialchars($current_user['username'] ?? 'Unknown'); ?></strong></p>
        <form method="POST" action="" class="settings-form">
            <input type="hidden" name="action" value="change_username">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
            
            <div class="form-group">
                <label for="new_username">New Username</label>
                <input 
                    type="text" 
                    id="new_username" 
                    name="new_username" 
                    class="form-control"
                    placeholder="Enter new username"
                    required
                    minlength="3"
                >
            </div>
            
            <div class="form-group">
                <label for="current_password_username">Current Password (to confirm)</label>
                <input 
                    type="password" 
                    id="current_password_username" 
                    name="current_password" 
                    class="form-control"
                    placeholder="Enter current password"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Username
            </button>
        </form>
    </div>
    
    <!-- Change Password Section -->
    <div class="settings-card">
        <h3><i class="fas fa-lock"></i> Change Password</h3>
        <p>Update your account password for enhanced security</p>
        
        <div class="password-requirements" style="background:#FEF3C7;padding:16px;border-radius:8px;margin-bottom:20px;">
            <h4 style="margin:0 0 12px 0;color:#92400E;"><i class="fas fa-shield-alt"></i> Password Requirements</h4>
            <ul style="margin:0;padding-left:20px;color:#92400E;font-size:13px;">
                <li>At least 12 characters long</li>
                <li>Contains uppercase letters (A-Z)</li>
                <li>Contains lowercase letters (a-z)</li>
                <li>Contains numbers (0-9)</li>
                <li>Contains special characters (!@#$%^&*)</li>
                <li>Not a common password</li>
            </ul>
        </div>
        
        <form method="POST" action="" class="settings-form">
            <input type="hidden" name="action" value="change_password">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
                
            <div class="form-group">
                <label for="current_password_password">Current Password</label>
                <input 
                    type="password" 
                    id="current_password_password" 
                    name="current_password" 
                    class="form-control"
                    placeholder="Enter current password"
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="new_password">New Password</label>
                <input 
                    type="password" 
                    id="new_password" 
                    name="new_password" 
                    class="form-control"
                    placeholder="Enter new password (min 12 characters)"
                    required
                    minlength="12"
                >
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <input 
                    type="password" 
                    id="confirm_password" 
                    name="confirm_password" 
                    class="form-control"
                    placeholder="Re-enter new password"
                    required
                >
            </div>
            
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Update Password
            </button>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<style>
.change-password-container {
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1E293B;
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.page-header p {
    color: #64748B;
    font-size: 15px;
}

.alert {
    padding: 16px 20px;
    border-radius: 12px;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 15px;
}

.alert-success {
    background: #D1FAE5;
    color: #065F46;
    border: 1px solid #6EE7B7;
}

.alert-error {
    background: #FEE2E2;
    color: #991B1B;
    border: 1px solid #FCA5A5;
}

.password-form-wrapper {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: 30px;
}

.password-requirements {
    background: #F8FAFC;
    padding: 24px;
    border-radius: 12px;
    height: fit-content;
}

.password-requirements h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 10px;
    color: #0066CC;
}

.password-requirements ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.password-requirements li {
    padding: 8px 0;
    font-size: 14px;
    color: #64748B;
    display: flex;
    align-items: center;
    gap: 10px;
}

.password-requirements li i {
    color: #00CC66;
}

.security-tips {
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #E2E8F0;
}

.security-tips h4 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 8px;
    color: #FF6600;
}

.security-tips li {
    font-size: 13px;
}

.password-form-card {
    background: #FFFFFF;
    padding: 32px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: flex;
    align-items: center;
    gap: 8px;
    font-weight: 600;
    color: #1E293B;
    margin-bottom: 8px;
    font-size: 14px;
}

.password-input-wrapper {
    position: relative;
    display: flex;
    align-items: center;
}

.form-control {
    width: 100%;
    padding: 14px 50px 14px 16px;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    font-size: 15px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #0066CC;
    box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
}

.toggle-visibility {
    position: absolute;
    right: 12px;
    background: none;
    border: none;
    color: #64748B;
    cursor: pointer;
    padding: 8px;
    font-size: 16px;
}

.toggle-visibility:hover {
    color: #0066CC;
}

.password-strength {
    margin-top: 8px;
    height: 4px;
    background: #E2E8F0;
    border-radius: 2px;
    overflow: hidden;
}

.password-strength-bar {
    height: 100%;
    transition: all 0.3s;
}

.password-match {
    margin-top: 8px;
    font-size: 13px;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 15px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #0066CC, #0052A3);
    color: #FFFFFF;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
}

.btn-secondary {
    background: #E2E8F0;
    color: #1E293B;
}

.btn-secondary:hover {
    background: #CBD5E1;
}

.btn-large {
    padding: 14px 32px;
    font-size: 16px;
}

@media (max-width: 768px) {
    .password-form-wrapper {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    
    let strength = 0;
    let color = '';
    let text = '';
    
    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^A-Za-z0-9]/.test(password)) strength++;
    
    if (strength <= 2) {
        color = '#FF4444';
        text = 'Weak';
    } else if (strength === 3) {
        color = '#FFA500';
        text = 'Fair';
    } else if (strength === 4) {
        color = '#00BFFF';
        text = 'Good';
    } else {
        color = '#00CC66';
        text = 'Strong';
    }
    
    const percentage = (strength / 5) * 100;
    strengthBar.innerHTML = `<div class="password-strength-bar" style="width: ${percentage}%; background: ${color};"></div>`;
    strengthBar.innerHTML += `<span style="font-size: 12px; color: ${color}; margin-top: 4px; display: block;">${text} password</span>`;
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    const matchIndicator = document.getElementById('passwordMatch');
    
    if (confirmPassword === '') {
        matchIndicator.innerHTML = '';
    } else if (newPassword === confirmPassword) {
        matchIndicator.innerHTML = '<span style="color: #00CC66;"><i class="fas fa-check-circle"></i> Passwords match</span>';
    } else {
        matchIndicator.innerHTML = '<span style="color: #FF4444;"><i class="fas fa-times-circle"></i> Passwords do not match</span>';
    }
});
</script>

<?php include '../includes/footer.php'; ?>
