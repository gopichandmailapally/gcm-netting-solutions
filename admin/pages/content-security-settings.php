<?php
/**
 * Content Security Settings
 * Manage API key and generation password
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../includes/security.php';
require_once '../includes/content-security.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$security = new AdminSecurity();
$contentSecurity = new ContentSecurity();

$message = '';
$error = '';
$show_api_key = false;
$revealed_api_key = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if (!$security->verifyCSRFToken($csrf_token)) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        switch ($action) {
            case 'save_settings':
                $api_key = trim($_POST['api_key'] ?? '');
                $generation_password = $_POST['generation_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($api_key) || empty($generation_password)) {
                    $error = 'All fields are required.';
                } elseif (strlen($api_key) < 20) {
                    $error = 'API key appears to be invalid (too short).';
                } elseif ($generation_password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($generation_password) < 8) {
                    $error = 'Generation password must be at least 8 characters.';
                } else {
                    if ($contentSecurity->saveAPISettings($api_key, $generation_password, $_SESSION['admin_id'])) {
                        $message = 'API settings saved successfully! Your API key is now encrypted and secure.';
                        $security->logSecurityEvent($_SESSION['admin_id'], 'api_settings_updated', 'API key and generation password updated');
                    } else {
                        $error = 'Failed to save settings. Please try again.';
                    }
                }
                break;
                
            case 'reveal_api_key':
                $password = $_POST['reveal_password'] ?? '';
                
                if ($contentSecurity->verifyGenerationPassword($password)) {
                    $revealed_api_key = $contentSecurity->getAPIKey($password);
                    $show_api_key = true;
                    $security->logSecurityEvent($_SESSION['admin_id'], 'api_key_revealed', 'API key viewed');
                } else {
                    $error = 'Invalid password. Cannot reveal API key.';
                }
                break;
                
            case 'delete_settings':
                $password = $_POST['delete_password'] ?? '';
                
                if ($contentSecurity->verifyGenerationPassword($password)) {
                    $settings_file = dirname(dirname(__DIR__)) . '/config/content-security-settings.json';
                    if (file_exists($settings_file)) {
                        unlink($settings_file);
                        $message = 'API settings deleted successfully.';
                        $security->logSecurityEvent($_SESSION['admin_id'], 'api_settings_deleted', 'API key and password removed');
                    }
                } else {
                    $error = 'Invalid password. Cannot delete settings.';
                }
                break;
        }
    }
}

$has_settings = $contentSecurity->hasAPISettings();
$masked_key = $has_settings ? $contentSecurity->getMaskedAPIKey() : null;
$stats = $contentSecurity->getContentStats();
$audit_logs = $contentSecurity->getAPIAuditLogs(20);

$page_title = 'Content Security Settings';
include '../includes/header.php';
?>

<style>
.security-settings {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.settings-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
    margin-bottom: 24px;
}

.settings-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.settings-card h2 {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
    color: #1E293B;
}

.form-control {
    width: 100%;
    padding: 12px;
    border: 2px solid #E2E8F0;
    border-radius: 8px;
    font-size: 14px;
}

.form-control:focus {
    outline: none;
    border-color: #3B82F6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s;
}

.btn-primary {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.btn-danger {
    background: #EF4444;
    color: white;
}

.btn-success {
    background: #10B981;
    color: white;
}

.btn-secondary {
    background: #64748B;
    color: white;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 12px;
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

.alert-warning {
    background: #FEF3C7;
    color: #92400E;
    border: 1px solid #FDE68A;
}

.api-key-display {
    background: #F8FAFC;
    padding: 16px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    word-break: break-all;
    margin: 16px 0;
    border: 2px solid #E2E8F0;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 24px;
}

.stat-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 12px;
    text-align: center;
}

.stat-box h3 {
    font-size: 32px;
    margin: 0 0 8px 0;
}

.stat-box p {
    margin: 0;
    opacity: 0.9;
    font-size: 14px;
}

.audit-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 16px;
}

.audit-table th {
    background: #F8FAFC;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    border-bottom: 2px solid #E2E8F0;
}

.audit-table td {
    padding: 12px;
    border-bottom: 1px solid #E2E8F0;
    font-size: 13px;
}

.badge {
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
}

.badge-success {
    background: #D1FAE5;
    color: #065F46;
}

.badge-danger {
    background: #FEE2E2;
    color: #991B1B;
}

.info-box {
    background: #DBEAFE;
    border: 1px solid #93C5FD;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.info-box h4 {
    margin: 0 0 12px 0;
    color: #1E40AF;
    display: flex;
    align-items: center;
    gap: 8px;
}

.info-box ul {
    margin: 0;
    padding-left: 20px;
    color: #1E40AF;
}

.info-box li {
    margin-bottom: 8px;
}

@media (max-width: 768px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    .stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="security-settings">
    <div class="page-header">
        <h1><i class="fas fa-shield-alt"></i> Content Security Settings</h1>
        <p>Manage API key and generation password for secure content creation</p>
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
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-box" style="background: linear-gradient(135deg, #10B981, #059669);">
            <h3><?php echo $stats['total_locked']; ?></h3>
            <p>Locked Content Items</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #3B82F6, #2563EB);">
            <h3><?php echo $has_settings ? 'Active' : 'Not Set'; ?></h3>
            <p>API Key Status</p>
        </div>
        <div class="stat-box" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
            <h3><?php echo count($audit_logs); ?></h3>
            <p>Recent Actions</p>
        </div>
    </div>
    
    <div class="settings-grid">
        <!-- API Key & Password Settings -->
        <div class="settings-card">
            <h2><i class="fas fa-key"></i> API Key & Password</h2>
            
            <?php if (!$has_settings): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No API settings configured. Set up your Gemini API key and generation password.
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Security Features</h4>
                <ul>
                    <li>API key encrypted with AES-256</li>
                    <li>Password hashed with Argon2id</li>
                    <li>Password required to view/delete key</li>
                    <li>All actions logged for audit</li>
                </ul>
            </div>
            
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
                <input type="hidden" name="action" value="save_settings">
                
                <div class="form-group">
                    <label for="api_key">
                        <i class="fas fa-key"></i> Gemini API Key
                        <?php if ($masked_key): ?>
                            <span style="color:#10B981;font-size:12px;margin-left:8px;">
                                <i class="fas fa-check-circle"></i> Currently: <?php echo htmlspecialchars($masked_key); ?>
                            </span>
                        <?php endif; ?>
                    </label>
                    <input 
                        type="text" 
                        id="api_key" 
                        name="api_key" 
                        class="form-control"
                        placeholder="AIzaSy..."
                        <?php echo $has_settings ? '' : 'required'; ?>
                    >
                    <small style="color:#64748B;font-size:12px;">Get your API key from <a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a></small>
                </div>
                
                <div class="form-group">
                    <label for="generation_password">
                        <i class="fas fa-lock"></i> Generation Password
                    </label>
                    <input 
                        type="password" 
                        id="generation_password" 
                        name="generation_password" 
                        class="form-control"
                        placeholder="Enter secure password (min 8 chars)"
                        minlength="8"
                        <?php echo $has_settings ? '' : 'required'; ?>
                    >
                    <small style="color:#64748B;font-size:12px;">This password will be required to delete locked content and view API key</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        class="form-control"
                        placeholder="Re-enter password"
                        minlength="8"
                        <?php echo $has_settings ? '' : 'required'; ?>
                    >
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $has_settings ? 'Update Settings' : 'Save Settings'; ?>
                </button>
            </form>
        </div>
        
        <!-- View & Manage API Key -->
        <div class="settings-card">
            <h2><i class="fas fa-eye"></i> View & Manage API Key</h2>
            
            <?php if (!$has_settings): ?>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i>
                    Configure API settings first to view and manage your key.
                </div>
            <?php else: ?>
                <!-- Reveal API Key -->
                <div style="margin-bottom:24px;">
                    <h3 style="font-size:16px;margin-bottom:12px;">
                        <i class="fas fa-eye"></i> Reveal API Key
                    </h3>
                    <p style="color:#64748B;font-size:13px;margin-bottom:12px;">
                        Enter your generation password to view the full API key
                    </p>
                    
                    <?php if ($show_api_key && $revealed_api_key): ?>
                        <div class="api-key-display">
                            <strong>Your API Key:</strong><br>
                            <?php echo htmlspecialchars($revealed_api_key); ?>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Keep this key secret! Don't share it or commit it to version control.
                        </div>
                    <?php else: ?>
                        <form method="POST" style="display:inline-block;width:100%;">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
                            <input type="hidden" name="action" value="reveal_api_key">
                            
                            <div class="form-group">
                                <input 
                                    type="password" 
                                    name="reveal_password" 
                                    class="form-control"
                                    placeholder="Enter generation password"
                                    required
                                >
                            </div>
                            
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-eye"></i> Reveal API Key
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                
                <hr style="border:none;border-top:1px solid #E2E8F0;margin:24px 0;">
                
                <!-- Delete Settings -->
                <div>
                    <h3 style="font-size:16px;margin-bottom:12px;color:#EF4444;">
                        <i class="fas fa-trash"></i> Delete API Settings
                    </h3>
                    <p style="color:#64748B;font-size:13px;margin-bottom:12px;">
                        Permanently delete your API key and generation password
                    </p>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure? This will delete your API key and password. All locked content will remain locked.');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
                        <input type="hidden" name="action" value="delete_settings">
                        
                        <div class="form-group">
                            <input 
                                type="password" 
                                name="delete_password" 
                                class="form-control"
                                placeholder="Enter generation password to confirm"
                                required
                            >
                        </div>
                        
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Delete Settings
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Locked Content Overview -->
    <?php if ($stats['total_locked'] > 0): ?>
    <div class="settings-card">
        <h2><i class="fas fa-lock"></i> Locked Content Overview</h2>
        <p style="color:#64748B;margin-bottom:16px;">Content items protected from accidental deletion</p>
        
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;">
            <?php foreach ($stats['by_type'] as $type => $count): ?>
            <div style="background:#F8FAFC;padding:16px;border-radius:8px;border:2px solid #E2E8F0;">
                <div style="font-size:24px;font-weight:700;color:#3B82F6;"><?php echo $count; ?></div>
                <div style="color:#64748B;font-size:13px;text-transform:capitalize;">
                    <?php echo htmlspecialchars(str_replace('_', ' ', $type)); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Audit Log -->
    <div class="settings-card">
        <h2><i class="fas fa-history"></i> Recent Activity</h2>
        
        <?php if (empty($audit_logs)): ?>
            <p style="color:#94A3B8;text-align:center;padding:40px;">No activity recorded yet</p>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Status</th>
                            <th>Details</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($audit_logs as $log): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                            <td><?php echo htmlspecialchars(str_replace('_', ' ', ucwords($log['action'], '_'))); ?></td>
                            <td>
                                <?php if ($log['success']): ?>
                                    <span class="badge badge-success">Success</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Failed</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;">
                                <?php echo htmlspecialchars($log['details']); ?>
                            </td>
                            <td><?php echo date('M d, H:i', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
