<?php
/**
 * Secure API Key Management Page
 * Password-protected API key settings
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/api-key-manager.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$api_manager = new APIKeyManager();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Set security password
        if (isset($_POST['set_password'])) {
            if (empty($_POST['new_password']) || empty($_POST['confirm_password'])) {
                throw new Exception("Please enter and confirm password");
            }
            
            if ($_POST['new_password'] !== $_POST['confirm_password']) {
                throw new Exception("Passwords do not match");
            }
            
            if (strlen($_POST['new_password']) < 8) {
                throw new Exception("Password must be at least 8 characters");
            }
            
            if ($api_manager->setPassword($_POST['new_password'])) {
                $_SESSION['success_message'] = "Security password set successfully!";
            } else {
                throw new Exception("Failed to set password");
            }
        }
        
        // Save API key
        if (isset($_POST['save_api_key'])) {
            if (empty($_POST['key_name']) || empty($_POST['key_value'])) {
                throw new Exception("Key name and value are required");
            }
            
            if (empty($_POST['security_password'])) {
                throw new Exception("Security password is required");
            }
            
            $api_manager->saveKey(
                $_POST['key_name'],
                $_POST['key_value'],
                $_POST['key_type'] ?? 'gemini',
                $_POST['security_password']
            );
            
            $_SESSION['success_message'] = "API key saved successfully!";
        }
        
        // Test API key
        if (isset($_POST['test_api_key'])) {
            if (empty($_POST['key_name']) || empty($_POST['security_password'])) {
                throw new Exception("Key name and password are required");
            }
            
            $test_result = $api_manager->testKey($_POST['key_name'], $_POST['security_password']);
            
            if ($test_result['success']) {
                $_SESSION['success_message'] = $test_result['message'];
            } else {
                throw new Exception($test_result['message']);
            }
        }
        
        // Delete API key
        if (isset($_POST['delete_api_key'])) {
            if (empty($_POST['key_name']) || empty($_POST['security_password'])) {
                throw new Exception("Key name and password are required");
            }
            
            $api_manager->deleteKey($_POST['key_name'], $_POST['security_password']);
            $_SESSION['success_message'] = "API key deleted successfully!";
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
    }
}

// Get all API keys
$api_keys = $api_manager->getAllKeys();
$password_set = $api_manager->isPasswordSet();

$page_title = 'API Key Settings';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin-panel.css">
</head>
<body>
    <div style="padding: 20px; max-width: 1200px; margin: 0 auto;" class="fade-in-up">
        <div class="page-header">
            <h1><i class="fas fa-key"></i> Secure API Key Management</h1>
            <a href="../dashboard.php" class="btn btn-secondary"><i class="fas fa-home"></i> Dashboard</a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Security Password Setup -->
        <?php if (!$password_set): ?>
        <div class="content-card">
            <div class="card-header"><h2><i class="fas fa-shield-alt"></i> Set Security Password (Required)</h2></div>
            <div class="card-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><strong>Important:</strong> Set a strong security password to protect your API keys. This password will be required to view, edit, or delete API keys.</span>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="set_password" value="1">
                    
                    <div class="form-group">
                        <label>New Security Password *</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="Minimum 8 characters">
                    </div>
                    
                    <div class="form-group">
                        <label>Confirm Password *</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-large">
                        <i class="fas fa-lock"></i> Set Security Password
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        
        <!-- Add/Update API Key -->
        <div class="content-card">
            <div class="card-header"><h2><i class="fas fa-plus-circle"></i> Add/Update API Key</h2></div>
            <div class="card-body">
                <form method="POST" id="apiKeyForm">
                    <input type="hidden" name="save_api_key" value="1">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Key Name *</label>
                            <select name="key_name" class="form-control" required>
                                <option value="gemini_api_key">Gemini API Key (Main)</option>
                                <option value="openai_api_key">OpenAI API Key (Backup)</option>
                                <option value="custom_api_key">Custom API Key</option>
                            </select>
                            <small style="color: #666;">This key will be used for all AI features (blogs, SEO, content generation)</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Key Type</label>
                            <select name="key_type" class="form-control">
                                <option value="gemini">Gemini AI</option>
                                <option value="openai">OpenAI</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>API Key Value *</label>
                        <textarea name="key_value" class="form-control" rows="3" required placeholder="Paste your API key here (will be encrypted)"></textarea>
                        <small style="color: #666;">Get free Gemini API key: <a href="https://makersuite.google.com/app/apikey" target="_blank">https://makersuite.google.com/app/apikey</a></small>
                    </div>
                    
                    <div class="form-group">
                        <label>Security Password * <i class="fas fa-lock"></i></label>
                        <input type="password" name="security_password" class="form-control" required placeholder="Enter your security password">
                        <small style="color: #666;">Required to save API key</small>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success btn-large">
                            <i class="fas fa-save"></i> Save API Key (Encrypted)
                        </button>
                        <button type="button" class="btn btn-info" onclick="showTestForm()">
                            <i class="fas fa-vial"></i> Test API Key
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Current API Keys -->
        <div class="content-card">
            <div class="card-header"><h2><i class="fas fa-list"></i> Current API Keys</h2></div>
            <div class="card-body">
                <?php if (empty($api_keys)): ?>
                    <div class="empty-state">
                        <i class="fas fa-key"></i>
                        <h3>No API Keys Configured</h3>
                        <p>Add your first API key above to enable AI features</p>
                    </div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Key Name</th>
                                <th>Type</th>
                                <th>Status</th>
                                <th>Usage Count</th>
                                <th>Last Used</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($api_keys as $key): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($key['key_name']); ?></strong></td>
                                <td><?php echo ucfirst($key['key_type']); ?></td>
                                <td>
                                    <span class="badge <?php echo $key['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $key['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td><?php echo number_format($key['usage_count']); ?></td>
                                <td><?php echo $key['last_used'] ? date('d M Y H:i', strtotime($key['last_used'])) : 'Never'; ?></td>
                                <td><?php echo date('d M Y', strtotime($key['created_at'])); ?></td>
                                <td>
                                    <button onclick="testKey('<?php echo htmlspecialchars($key['key_name']); ?>')" class="btn-icon primary" title="Test">
                                        <i class="fas fa-vial"></i>
                                    </button>
                                    <button onclick="deleteKey('<?php echo htmlspecialchars($key['key_name']); ?>')" class="btn-icon danger" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Security Features -->
        <div class="content-card">
            <div class="card-header"><h2><i class="fas fa-shield-alt"></i> Security Features</h2></div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                    <div style="padding: 20px; background: #f0f9ff; border-radius: 12px; border: 2px solid #3b82f6;">
                        <h3 style="color: #1e40af; margin-bottom: 10px;"><i class="fas fa-lock"></i> Encrypted Storage</h3>
                        <p style="color: #666; margin: 0;">API keys are encrypted using AES-256-CBC encryption</p>
                    </div>
                    
                    <div style="padding: 20px; background: #f0fdf4; border-radius: 12px; border: 2px solid #10b981;">
                        <h3 style="color: #065f46; margin-bottom: 10px;"><i class="fas fa-key"></i> Password Protected</h3>
                        <p style="color: #666; margin: 0;">Security password required for all operations</p>
                    </div>
                    
                    <div style="padding: 20px; background: #fef3c7; border-radius: 12px; border: 2px solid #f59e0b;">
                        <h3 style="color: #92400e; margin-bottom: 10px;"><i class="fas fa-history"></i> Access Logging</h3>
                        <p style="color: #666; margin: 0;">All access attempts are logged with IP and timestamp</p>
                    </div>
                    
                    <div style="padding: 20px; background: #fee2e2; border-radius: 12px; border: 2px solid #ef4444;">
                        <h3 style="color: #991b1b; margin-bottom: 10px;"><i class="fas fa-ban"></i> Cannot Be Removed Easily</h3>
                        <p style="color: #666; margin: 0;">Password required to delete keys</p>
                    </div>
                </div>
            </div>
        </div>
        
        <?php endif; ?>
    </div>
    
    <!-- Test Modal -->
    <div id="testModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 30px; border-radius: 16px; max-width: 500px; width: 90%;">
            <h2 style="margin-bottom: 20px;"><i class="fas fa-vial"></i> Test API Key</h2>
            <form method="POST" id="testForm">
                <input type="hidden" name="test_api_key" value="1">
                <input type="hidden" name="key_name" id="test_key_name">
                
                <div class="form-group">
                    <label>Security Password *</label>
                    <input type="password" name="security_password" class="form-control" required>
                </div>
                
                <div style="display: flex; gap: 12px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check"></i> Test Key
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeTestModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    function testKey(keyName) {
        document.getElementById('test_key_name').value = keyName;
        document.getElementById('testModal').style.display = 'flex';
    }
    
    function closeTestModal() {
        document.getElementById('testModal').style.display = 'none';
    }
    
    function deleteKey(keyName) {
        if (!confirm('Are you sure you want to delete this API key? This action cannot be undone.')) {
            return;
        }
        
        const password = prompt('Enter security password to delete:');
        if (!password) return;
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="delete_api_key" value="1">
            <input type="hidden" name="key_name" value="${keyName}">
            <input type="hidden" name="security_password" value="${password}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
    
    function showTestForm() {
        const keyName = document.querySelector('select[name="key_name"]').value;
        testKey(keyName);
    }
    </script>
</body>
</html>
