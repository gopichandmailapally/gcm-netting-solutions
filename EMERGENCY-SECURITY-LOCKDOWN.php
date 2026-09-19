<?php
/**
 * EMERGENCY SECURITY LOCKDOWN
 * Upload this file to public_html/ and run it immediately
 * URL: https://gcmsafetynets.in/EMERGENCY-SECURITY-LOCKDOWN.php
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

// SECURITY: Set a temporary access password
$EMERGENCY_PASSWORD = 'GCM@EMERGENCY@2026'; // Change this!

// Check password
if (!isset($_POST['emergency_password']) || $_POST['emergency_password'] !== $EMERGENCY_PASSWORD) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Emergency Security Lockdown</title>
        <style>
            body { font-family: Arial; background: #1a1a1a; color: #fff; padding: 50px; text-align: center; }
            .container { max-width: 500px; margin: 0 auto; background: #2a2a2a; padding: 40px; border-radius: 10px; }
            input { width: 100%; padding: 15px; margin: 20px 0; font-size: 16px; border: 2px solid #444; background: #333; color: #fff; border-radius: 5px; }
            button { width: 100%; padding: 15px; background: #dc3545; color: #fff; border: none; font-size: 18px; font-weight: bold; cursor: pointer; border-radius: 5px; }
            button:hover { background: #c82333; }
            h1 { color: #dc3545; }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>🚨 EMERGENCY SECURITY LOCKDOWN</h1>
            <p>Enter emergency password to proceed</p>
            <form method="POST">
                <input type="password" name="emergency_password" placeholder="Emergency Password" required>
                <button type="submit">UNLOCK</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// EMERGENCY ACTIONS
$actions_taken = [];
$errors = [];

try {
    $db = Database::getInstance();
    
    // 1. GET ALL SECURITY LOGS
    $security_logs = $db->fetchAll("SELECT * FROM admin_security_logs ORDER BY created_at DESC LIMIT 100");
    
    // 2. GET ALL LOGIN ATTEMPTS
    $login_attempts = $db->fetchAll("SELECT * FROM admin_login_attempts ORDER BY attempt_time DESC LIMIT 100");
    
    // 3. GET ALL ACTIVE SESSIONS
    $active_sessions = $db->fetchAll("SELECT * FROM admin_sessions ORDER BY last_activity DESC");
    
    // 4. GET ALL ADMIN USERS
    $admin_users = $db->fetchAll("SELECT * FROM admin_users");
    
    // 5. GET BLOCKED IPS
    $blocked_ips = $db->fetchAll("SELECT * FROM admin_blocked_ips");
    
    $actions_taken[] = "Retrieved security logs and session data";
    
} catch (Exception $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

// EMERGENCY ACTIONS TO TAKE
if (isset($_POST['action'])) {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'kill_all_sessions':
            try {
                $db->execute("DELETE FROM admin_sessions");
                $actions_taken[] = "✅ KILLED ALL ADMIN SESSIONS - All users logged out";
            } catch (Exception $e) {
                $errors[] = "Failed to kill sessions: " . $e->getMessage();
            }
            break;
            
        case 'change_admin_password':
            $new_password = $_POST['new_password'] ?? '';
            if (strlen($new_password) >= 12) {
                try {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $db->execute("UPDATE admin_users SET password_hash = ? WHERE username = 'admin'", [$hashed]);
                    $actions_taken[] = "✅ CHANGED ADMIN PASSWORD - Use new password to login";
                } catch (Exception $e) {
                    $errors[] = "Failed to change password: " . $e->getMessage();
                }
            } else {
                $errors[] = "Password must be at least 12 characters";
            }
            break;
            
        case 'block_ip':
            $ip_to_block = $_POST['ip_to_block'] ?? '';
            if (!empty($ip_to_block)) {
                try {
                    $db->execute(
                        "INSERT OR REPLACE INTO admin_blocked_ips (ip_address, reason, permanent) VALUES (?, ?, 1)",
                        [$ip_to_block, 'Emergency lockdown - Suspicious activity']
                    );
                    $actions_taken[] = "✅ BLOCKED IP: $ip_to_block";
                } catch (Exception $e) {
                    $errors[] = "Failed to block IP: " . $e->getMessage();
                }
            }
            break;
            
        case 'enable_security':
            try {
                // Create .htaccess to protect admin
                $htaccess_content = "# Emergency Security Protection\n";
                $htaccess_content .= "# Block suspicious IPs\n";
                $htaccess_content .= "Order Allow,Deny\n";
                $htaccess_content .= "Allow from all\n";
                
                // Add blocked IPs from database
                foreach ($blocked_ips as $blocked) {
                    $htaccess_content .= "Deny from {$blocked['ip_address']}\n";
                }
                
                file_put_contents(__DIR__ . '/admin/.htaccess', $htaccess_content);
                $actions_taken[] = "✅ ENABLED .htaccess PROTECTION for admin folder";
            } catch (Exception $e) {
                $errors[] = "Failed to create .htaccess: " . $e->getMessage();
            }
            break;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Emergency Security Lockdown - GCM Netting Solutions</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Arial; background: #0a0a0a; color: #fff; padding: 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 { color: #dc3545; font-size: 32px; margin-bottom: 10px; }
        .alert { padding: 20px; margin: 20px 0; border-radius: 8px; }
        .alert-danger { background: #dc3545; }
        .alert-success { background: #28a745; }
        .alert-warning { background: #ffc107; color: #000; }
        .section { background: #1a1a1a; padding: 30px; margin: 20px 0; border-radius: 10px; border: 2px solid #333; }
        .section h2 { color: #fff; margin-bottom: 20px; font-size: 24px; }
        .action-btn { padding: 15px 30px; background: #dc3545; color: #fff; border: none; font-size: 16px; font-weight: bold; cursor: pointer; border-radius: 5px; margin: 10px 5px; }
        .action-btn:hover { background: #c82333; }
        .action-btn.success { background: #28a745; }
        .action-btn.success:hover { background: #218838; }
        input[type="text"], input[type="password"] { padding: 12px; font-size: 16px; border: 2px solid #444; background: #2a2a2a; color: #fff; border-radius: 5px; margin: 10px 5px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #333; }
        th { background: #2a2a2a; color: #fff; font-weight: bold; }
        tr:hover { background: #2a2a2a; }
        .ip { font-family: monospace; background: #2a2a2a; padding: 5px 10px; border-radius: 3px; }
        .timestamp { color: #888; font-size: 14px; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-failed { color: #dc3545; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚨 EMERGENCY SECURITY LOCKDOWN</h1>
        <p style="color: #888; margin-bottom: 30px;">GCM Netting Solutions Admin Panel - Security Management</p>
        
        <?php if (!empty($actions_taken)): ?>
            <div class="alert alert-success">
                <strong>✅ Actions Taken:</strong><br>
                <?php foreach ($actions_taken as $action): ?>
                    • <?php echo htmlspecialchars($action); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>❌ Errors:</strong><br>
                <?php foreach ($errors as $error): ?>
                    • <?php echo htmlspecialchars($error); ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- EMERGENCY ACTIONS -->
        <div class="section">
            <h2>⚡ EMERGENCY ACTIONS</h2>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="emergency_password" value="<?php echo htmlspecialchars($EMERGENCY_PASSWORD); ?>">
                <input type="hidden" name="action" value="kill_all_sessions">
                <button type="submit" class="action-btn" onclick="return confirm('Kill all admin sessions? This will log out everyone including you!')">
                    🔴 KILL ALL SESSIONS
                </button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="emergency_password" value="<?php echo htmlspecialchars($EMERGENCY_PASSWORD); ?>">
                <input type="hidden" name="action" value="change_admin_password">
                <input type="password" name="new_password" placeholder="New Admin Password (12+ chars)" required>
                <button type="submit" class="action-btn">
                    🔑 CHANGE ADMIN PASSWORD
                </button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="emergency_password" value="<?php echo htmlspecialchars($EMERGENCY_PASSWORD); ?>">
                <input type="hidden" name="action" value="enable_security">
                <button type="submit" class="action-btn success">
                    🛡️ ENABLE .HTACCESS PROTECTION
                </button>
            </form>
        </div>
        
        <!-- ACTIVE SESSIONS -->
        <div class="section">
            <h2>👥 ACTIVE SESSIONS (<?php echo count($active_sessions ?? []); ?>)</h2>
            <?php if (!empty($active_sessions)): ?>
                <table>
                    <tr>
                        <th>Admin ID</th>
                        <th>Session ID</th>
                        <th>IP Address</th>
                        <th>User Agent</th>
                        <th>Last Activity</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach ($active_sessions as $session): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($session['admin_id']); ?></td>
                            <td><span class="ip"><?php echo htmlspecialchars(substr($session['session_id'], 0, 20)); ?>...</span></td>
                            <td><span class="ip"><?php echo htmlspecialchars($session['ip_address']); ?></span></td>
                            <td><?php echo htmlspecialchars(substr($session['user_agent'] ?? 'Unknown', 0, 50)); ?></td>
                            <td class="timestamp"><?php echo htmlspecialchars($session['last_activity'] ?? 'Unknown'); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="emergency_password" value="<?php echo htmlspecialchars($EMERGENCY_PASSWORD); ?>">
                                    <input type="hidden" name="action" value="block_ip">
                                    <input type="hidden" name="ip_to_block" value="<?php echo htmlspecialchars($session['ip_address']); ?>">
                                    <button type="submit" class="action-btn" style="padding: 8px 15px; font-size: 14px;">BLOCK IP</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="color: #888;">No active sessions found.</p>
            <?php endif; ?>
        </div>
        
        <!-- RECENT LOGIN ATTEMPTS -->
        <div class="section">
            <h2>🔐 RECENT LOGIN ATTEMPTS (Last 50)</h2>
            <?php if (!empty($login_attempts)): ?>
                <table>
                    <tr>
                        <th>Username</th>
                        <th>IP Address</th>
                        <th>Status</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                    <?php foreach (array_slice($login_attempts, 0, 50) as $attempt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($attempt['username']); ?></td>
                            <td><span class="ip"><?php echo htmlspecialchars($attempt['ip_address']); ?></span></td>
                            <td class="<?php echo $attempt['success'] ? 'status-active' : 'status-failed'; ?>">
                                <?php echo $attempt['success'] ? '✅ SUCCESS' : '❌ FAILED'; ?>
                            </td>
                            <td class="timestamp"><?php echo htmlspecialchars($attempt['attempt_time']); ?></td>
                            <td>
                                <?php if (!$attempt['success']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="emergency_password" value="<?php echo htmlspecialchars($EMERGENCY_PASSWORD); ?>">
                                        <input type="hidden" name="action" value="block_ip">
                                        <input type="hidden" name="ip_to_block" value="<?php echo htmlspecialchars($attempt['ip_address']); ?>">
                                        <button type="submit" class="action-btn" style="padding: 8px 15px; font-size: 14px;">BLOCK IP</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="color: #888;">No login attempts found.</p>
            <?php endif; ?>
        </div>
        
        <!-- BLOCKED IPS -->
        <div class="section">
            <h2>🚫 BLOCKED IP ADDRESSES (<?php echo count($blocked_ips ?? []); ?>)</h2>
            <?php if (!empty($blocked_ips)): ?>
                <table>
                    <tr>
                        <th>IP Address</th>
                        <th>Reason</th>
                        <th>Blocked Until</th>
                        <th>Permanent</th>
                    </tr>
                    <?php foreach ($blocked_ips as $blocked): ?>
                        <tr>
                            <td><span class="ip"><?php echo htmlspecialchars($blocked['ip_address']); ?></span></td>
                            <td><?php echo htmlspecialchars($blocked['reason'] ?? 'No reason'); ?></td>
                            <td class="timestamp"><?php echo htmlspecialchars($blocked['blocked_until'] ?? 'N/A'); ?></td>
                            <td><?php echo $blocked['permanent'] ? '✅ YES' : '❌ NO'; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php else: ?>
                <p style="color: #888;">No blocked IPs found.</p>
            <?php endif; ?>
        </div>
        
        <!-- MANUAL IP BLOCK -->
        <div class="section">
            <h2>🚫 MANUALLY BLOCK IP ADDRESS</h2>
            <form method="POST">
                <input type="hidden" name="emergency_password" value="<?php echo htmlspecialchars($EMERGENCY_PASSWORD); ?>">
                <input type="hidden" name="action" value="block_ip">
                <input type="text" name="ip_to_block" placeholder="Enter IP Address (e.g., 192.168.1.1)" required>
                <button type="submit" class="action-btn">BLOCK THIS IP</button>
            </form>
        </div>
        
        <div class="alert alert-warning">
            <strong>⚠️ IMPORTANT:</strong><br>
            • After securing your site, DELETE this file from your server<br>
            • Change the emergency password in this file before using<br>
            • Check security logs regularly for suspicious activity<br>
            • Keep your admin password strong (12+ characters, mixed case, numbers, symbols)
        </div>
    </div>
</body>
</html>
