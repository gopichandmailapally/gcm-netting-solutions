<?php
/**
 * Security Dashboard - Monitor and manage admin security
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../includes/security.php';

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
$db = Database::getInstance();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $csrf_token = $_POST['csrf_token'] ?? '';
    
    if ($security->verifyCSRFToken($csrf_token)) {
        if ($action === 'unblock_ip') {
            $ip = $_POST['ip'] ?? '';
            if ($ip) {
                $conn = $db->getConnection();
                $stmt = $conn->prepare("DELETE FROM admin_blocked_ips WHERE ip_address = ?");
                $stmt->bind_param('s', $ip);
                $stmt->execute();
                $stmt->close();
                
                $security->logSecurityEvent($_SESSION['admin_id'], 'ip_unblocked', 'IP: ' . $ip);
                $message = "IP address unblocked successfully!";
            }
        } elseif ($action === 'block_ip') {
            $ip = $_POST['ip'] ?? '';
            $reason = $_POST['reason'] ?? 'Manually blocked';
            if ($ip) {
                $security->blockIP($ip, $reason, null, true);
                $security->logSecurityEvent($_SESSION['admin_id'], 'ip_blocked', 'IP: ' . $ip . ', Reason: ' . $reason);
                $message = "IP address blocked successfully!";
            }
        } elseif ($action === 'clear_old_logs') {
            $security->cleanOldRecords();
            $security->logSecurityEvent($_SESSION['admin_id'], 'logs_cleaned', 'Old security records cleaned');
            $message = "Old security records cleaned successfully!";
        }
    }
}

// Get security statistics
$stats = $security->getSecurityStats();

// Get recent failed login attempts
$failed_logins = $db->fetchAll(
    "SELECT username, ip_address, user_agent, attempt_time 
     FROM admin_login_attempts 
     WHERE success = 0 
     ORDER BY attempt_time DESC 
     LIMIT 20"
);

// Get blocked IPs
$blocked_ips = $db->fetchAll(
    "SELECT ip_address, reason, blocked_until, permanent, created_at 
     FROM admin_blocked_ips 
     WHERE permanent = 1 OR blocked_until > NOW()
     ORDER BY created_at DESC"
);

// Get recent security events
$security_events = $db->fetchAll(
    "SELECT sl.*, au.username 
     FROM admin_security_logs sl 
     LEFT JOIN admin_users au ON sl.admin_id = au.id 
     ORDER BY sl.created_at DESC 
     LIMIT 50"
);

// Get active sessions
$active_sessions = $db->fetchAll(
    "SELECT s.*, au.username 
     FROM admin_sessions s 
     LEFT JOIN admin_users au ON s.admin_id = au.id 
     WHERE s.last_activity > DATE_SUB(NOW(), INTERVAL 1 HOUR)
     ORDER BY s.last_activity DESC"
);

$page_title = 'Security Dashboard';
include '../includes/header.php';
?>

<style>
.security-dashboard {
    padding: 20px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.stat-card .stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    margin-bottom: 16px;
}

.stat-card h3 {
    font-size: 32px;
    font-weight: 700;
    margin: 0 0 8px 0;
}

.stat-card p {
    color: #64748B;
    font-size: 14px;
    margin: 0;
}

.security-section {
    background: white;
    border-radius: 12px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.security-section h2 {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 20px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: #F8FAFC;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    font-size: 13px;
    color: #475569;
    border-bottom: 2px solid #E2E8F0;
}

.data-table td {
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

.badge-danger {
    background: #FEE2E2;
    color: #991B1B;
}

.badge-warning {
    background: #FEF3C7;
    color: #92400E;
}

.badge-success {
    background: #D1FAE5;
    color: #065F46;
}

.badge-info {
    background: #DBEAFE;
    color: #1E40AF;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
}

.btn-danger {
    background: #EF4444;
    color: white;
}

.btn-success {
    background: #10B981;
    color: white;
}

.btn-primary {
    background: #3B82F6;
    color: white;
}

.alert {
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #D1FAE5;
    color: #065F46;
    border: 1px solid #6EE7B7;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #94A3B8;
}

.action-buttons {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
}

@media (max-width: 1024px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>

<div class="security-dashboard">
    <div class="page-header">
        <h1><i class="fas fa-shield-alt"></i> Security Dashboard</h1>
        <p>Monitor and manage admin panel security</p>
    </div>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <!-- Statistics -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3><?php echo $stats['failed_logins_today']; ?></h3>
            <p>Failed Logins Today</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                <i class="fas fa-ban"></i>
            </div>
            <h3><?php echo $stats['blocked_ips']; ?></h3>
            <p>Blocked IP Addresses</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #10B981, #059669);">
                <i class="fas fa-users"></i>
            </div>
            <h3><?php echo $stats['active_sessions']; ?></h3>
            <p>Active Sessions</p>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #3B82F6, #2563EB);">
                <i class="fas fa-history"></i>
            </div>
            <h3><?php echo $stats['security_events_today']; ?></h3>
            <p>Security Events Today</p>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="action-buttons">
        <button class="btn-primary btn-small" onclick="showBlockIPModal()">
            <i class="fas fa-ban"></i> Block IP Address
        </button>
        <form method="POST" style="display:inline;" onsubmit="return confirm('Clean old security records?');">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
            <input type="hidden" name="action" value="clear_old_logs">
            <button type="submit" class="btn-small" style="background:#64748B;color:white;">
                <i class="fas fa-broom"></i> Clean Old Records
            </button>
        </form>
    </div>
    
    <!-- Recent Failed Login Attempts -->
    <div class="security-section">
        <h2><i class="fas fa-user-times"></i> Recent Failed Login Attempts</h2>
        <?php if (empty($failed_logins)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle" style="font-size:48px;color:#10B981;"></i>
                <p>No failed login attempts</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>IP Address</th>
                            <th>User Agent</th>
                            <th>Time</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($failed_logins as $attempt): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($attempt['username']); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($attempt['ip_address']); ?></code></td>
                            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;" title="<?php echo htmlspecialchars($attempt['user_agent']); ?>">
                                <?php echo htmlspecialchars(substr($attempt['user_agent'], 0, 50)); ?>
                            </td>
                            <td><?php echo date('M d, Y H:i:s', strtotime($attempt['attempt_time'])); ?></td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
                                    <input type="hidden" name="action" value="block_ip">
                                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($attempt['ip_address']); ?>">
                                    <input type="hidden" name="reason" value="Multiple failed login attempts">
                                    <button type="submit" class="btn-danger btn-small" onclick="return confirm('Block this IP?');">
                                        <i class="fas fa-ban"></i> Block
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Blocked IP Addresses -->
    <div class="security-section">
        <h2><i class="fas fa-ban"></i> Blocked IP Addresses</h2>
        <?php if (empty($blocked_ips)): ?>
            <div class="empty-state">
                <i class="fas fa-shield-alt" style="font-size:48px;color:#3B82F6;"></i>
                <p>No blocked IP addresses</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>IP Address</th>
                            <th>Reason</th>
                            <th>Type</th>
                            <th>Blocked Until</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_ips as $blocked): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($blocked['ip_address']); ?></code></td>
                            <td><?php echo htmlspecialchars($blocked['reason']); ?></td>
                            <td>
                                <?php if ($blocked['permanent']): ?>
                                    <span class="badge badge-danger">Permanent</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Temporary</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                if ($blocked['permanent']) {
                                    echo 'Never';
                                } else {
                                    echo date('M d, Y H:i:s', strtotime($blocked['blocked_until']));
                                }
                                ?>
                            </td>
                            <td>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
                                    <input type="hidden" name="action" value="unblock_ip">
                                    <input type="hidden" name="ip" value="<?php echo htmlspecialchars($blocked['ip_address']); ?>">
                                    <button type="submit" class="btn-success btn-small" onclick="return confirm('Unblock this IP?');">
                                        <i class="fas fa-check"></i> Unblock
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Active Sessions -->
    <div class="security-section">
        <h2><i class="fas fa-users"></i> Active Sessions</h2>
        <?php if (empty($active_sessions)): ?>
            <div class="empty-state">
                <i class="fas fa-user-slash" style="font-size:48px;color:#94A3B8;"></i>
                <p>No active sessions</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>IP Address</th>
                            <th>Last Activity</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($active_sessions as $session): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($session['username'] ?? 'Unknown'); ?></strong></td>
                            <td><code><?php echo htmlspecialchars($session['ip_address']); ?></code></td>
                            <td><?php echo date('M d, Y H:i:s', strtotime($session['last_activity'])); ?></td>
                            <td>
                                <?php if ($session['session_id'] === session_id()): ?>
                                    <span class="badge badge-success">Current Session</span>
                                <?php else: ?>
                                    <span class="badge badge-info">Active</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Security Events -->
    <div class="security-section">
        <h2><i class="fas fa-history"></i> Recent Security Events</h2>
        <?php if (empty($security_events)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list" style="font-size:48px;color:#94A3B8;"></i>
                <p>No security events</p>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>IP Address</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($security_events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['username'] ?? 'System'); ?></td>
                            <td>
                                <?php
                                $action_badges = [
                                    'login_success' => 'badge-success',
                                    'login_failed' => 'badge-danger',
                                    'logout' => 'badge-info',
                                    'password_changed' => 'badge-warning',
                                    'username_changed' => 'badge-warning',
                                    'ip_blocked' => 'badge-danger',
                                    'ip_unblocked' => 'badge-success'
                                ];
                                $badge_class = $action_badges[$event['action']] ?? 'badge-info';
                                ?>
                                <span class="badge <?php echo $badge_class; ?>">
                                    <?php echo htmlspecialchars(str_replace('_', ' ', ucwords($event['action'], '_'))); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($event['details']); ?></td>
                            <td><code><?php echo htmlspecialchars($event['ip_address']); ?></code></td>
                            <td><?php echo date('M d, Y H:i:s', strtotime($event['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Block IP Modal -->
<div id="blockIPModal" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.5);z-index:9999;align-items:center;justify-content:center;">
    <div style="background:white;border-radius:12px;padding:24px;max-width:500px;width:90%;">
        <h3 style="margin:0 0 20px 0;"><i class="fas fa-ban"></i> Block IP Address</h3>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($security->generateCSRFToken()); ?>">
            <input type="hidden" name="action" value="block_ip">
            
            <div style="margin-bottom:16px;">
                <label style="display:block;margin-bottom:8px;font-weight:600;">IP Address</label>
                <input type="text" name="ip" required pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$" 
                       style="width:100%;padding:12px;border:2px solid #E2E8F0;border-radius:8px;"
                       placeholder="192.168.1.1">
            </div>
            
            <div style="margin-bottom:20px;">
                <label style="display:block;margin-bottom:8px;font-weight:600;">Reason</label>
                <input type="text" name="reason" required 
                       style="width:100%;padding:12px;border:2px solid #E2E8F0;border-radius:8px;"
                       placeholder="Suspicious activity">
            </div>
            
            <div style="display:flex;gap:12px;">
                <button type="submit" class="btn-danger" style="flex:1;padding:12px;border-radius:8px;border:none;cursor:pointer;font-weight:600;">
                    <i class="fas fa-ban"></i> Block IP
                </button>
                <button type="button" onclick="hideBlockIPModal()" style="flex:1;padding:12px;border-radius:8px;border:none;cursor:pointer;font-weight:600;background:#E2E8F0;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function showBlockIPModal() {
    document.getElementById('blockIPModal').style.display = 'flex';
}

function hideBlockIPModal() {
    document.getElementById('blockIPModal').style.display = 'none';
}

// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php include '../includes/footer.php'; ?>
