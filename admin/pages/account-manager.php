<?php
/**
 * Account Manager — Admin Panel Page
 * Change Password, Change Username, Add User, Manage Users, Audit Log
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_samesite', 'Strict');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ' . SITE_URL . '/admin/login.php'); exit;
}

require_once '../includes/account-manager.php';
$am       = new AccountManager();
$adminId  = (int)($_SESSION['admin_id'] ?? 0);
$adminUser = $am->getUser($adminId);
$isSuperAdmin = ($adminUser['role'] ?? '') === 'super_admin';

/* CSRF tokens for each tab form */
$csrf_cp  = $am->generateCSRF('cp');
$csrf_cu  = $am->generateCSRF('cu');
$csrf_add = $am->generateCSRF('add');

/* Messages */
$msg_cp = $msg_cu = $msg_add = [];

$users     = $am->getAllAdmins();
$auditLog  = $am->getAuditLog(30);

$page_title = 'Account Manager';
require_once '../includes/header.php';
?>

<div class="page-header">
    <div>
        <h1><i class="fas fa-user-shield"></i> Account Manager</h1>
        <p class="subtitle">Secure password, username &amp; user management — all changes verified by email</p>
    </div>
</div>

<!-- ── TABS ─────────────────────────────────────────── -->
<div class="acct-tabs" id="acctTabs">
    <button class="atab active" data-tab="change-pw">
        <i class="fas fa-key"></i> Change Password
    </button>
    <button class="atab" data-tab="change-un">
        <i class="fas fa-user-edit"></i> Change Username
    </button>
    <?php if ($isSuperAdmin): ?>
    <button class="atab" data-tab="add-user">
        <i class="fas fa-user-plus"></i> Add Admin User
    </button>
    <button class="atab" data-tab="manage-users">
        <i class="fas fa-users"></i> Manage Users
    </button>
    <?php endif; ?>
    <button class="atab" data-tab="audit">
        <i class="fas fa-history"></i> Audit Log
    </button>
</div>

<!-- ════════════════════════════════════════════════════
     TAB 1 — CHANGE PASSWORD
     ════════════════════════════════════════════════════ -->
<div class="atab-pane active" id="tab-change-pw">
    <div class="acct-card">
        <div class="acct-card-header">
            <span class="ach-icon" style="background:linear-gradient(135deg,#667eea,#764ba2)"><i class="fas fa-lock"></i></span>
            <div>
                <h3>Change Your Password</h3>
                <p>A confirmation link will be sent to your email before the change takes effect</p>
            </div>
        </div>
        <div class="acct-card-body">
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Email-confirmed &bull; Bcrypt(cost=12) &bull; Strength enforced &bull; Old password required</span>
            </div>
            <div id="msg-cp"></div>
            <form id="form-cp" class="acct-form">
                <input type="hidden" name="csrf_token" id="csrf_cp" value="<?php echo htmlspecialchars($csrf_cp); ?>">
                <input type="hidden" name="action" value="change_password">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Current Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="old_password" class="form-control" placeholder="Your current password" required autocomplete="current-password">
                            <i class="fas fa-eye pw-eye"></i>
                        </div>
                    </div>
                </div>
                <div class="form-row two-col">
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="new_password" id="cp_new" class="form-control" placeholder="Min 8 chars" required autocomplete="new-password" oninput="checkPwStrength(this)">
                            <i class="fas fa-eye pw-eye"></i>
                        </div>
                        <div class="pw-strength-bar"><div id="cp_bar"></div></div>
                        <div class="pw-strength-label" id="cp_label"></div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-check-double"></i> Confirm New Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required autocomplete="new-password">
                            <i class="fas fa-eye pw-eye"></i>
                        </div>
                    </div>
                </div>
                <div class="pw-rules">
                    <strong>Password requirements:</strong>
                    <span class="rule" id="r-len">&#10005; 8+ chars</span>
                    <span class="rule" id="r-upper">&#10005; Uppercase</span>
                    <span class="rule" id="r-lower">&#10005; Lowercase</span>
                    <span class="rule" id="r-num">&#10005; Number</span>
                    <span class="rule" id="r-spec">&#10005; Special char</span>
                </div>
                <button type="submit" class="btn-acct btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Confirmation Email
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════
     TAB 2 — CHANGE USERNAME
     ════════════════════════════════════════════════════ -->
<div class="atab-pane" id="tab-change-un">
    <div class="acct-card">
        <div class="acct-card-header">
            <span class="ach-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706)"><i class="fas fa-user-edit"></i></span>
            <div>
                <h3>Change Your Username</h3>
                <p>Current username: <strong><?php echo htmlspecialchars($adminUser['username'] ?? ''); ?></strong> &bull; Confirmation email required</p>
            </div>
        </div>
        <div class="acct-card-body">
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Email-confirmed &bull; Unique check &bull; Password required &bull; 3-20 chars alphanumeric</span>
            </div>
            <div id="msg-cu"></div>
            <form id="form-cu" class="acct-form">
                <input type="hidden" name="csrf_token" id="csrf_cu" value="<?php echo htmlspecialchars($csrf_cu); ?>">
                <input type="hidden" name="action" value="change_username">
                <div class="form-row two-col">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> New Username</label>
                        <input type="text" name="new_username" class="form-control" placeholder="3-20 chars, A-Z a-z 0-9 _" required autocomplete="off" pattern="[a-zA-Z0-9_]{3,20}" title="3-20 chars, letters A-Z a-z, numbers, underscores">
                        <small class="field-hint">Letters (A–Z, a–z), numbers, underscores</small>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-lock"></i> Confirm with Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="current_password" class="form-control" placeholder="Your current password" required autocomplete="current-password">
                            <i class="fas fa-eye pw-eye"></i>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn-acct btn-warning">
                    <i class="fas fa-paper-plane"></i> Send Confirmation Email
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════
     TAB 3 — ADD ADMIN USER  (super_admin only)
     ════════════════════════════════════════════════════ -->
<?php if ($isSuperAdmin): ?>
<div class="atab-pane" id="tab-add-user">
    <div class="acct-card">
        <div class="acct-card-header">
            <span class="ach-icon" style="background:linear-gradient(135deg,#10b981,#059669)"><i class="fas fa-user-plus"></i></span>
            <div>
                <h3>Create New Admin User</h3>
                <p>Super admin only &bull; Notification sent to new user &amp; recovery email</p>
            </div>
        </div>
        <div class="acct-card-body">
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>Super admin only &bull; Password strength enforced &bull; Email alert on creation</span>
            </div>
            <div id="msg-add"></div>
            <form id="form-add" class="acct-form">
                <input type="hidden" name="csrf_token" id="csrf_add" value="<?php echo htmlspecialchars($csrf_add); ?>">
                <input type="hidden" name="action" value="create_user">
                <div class="form-row two-col">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Username</label>
                        <input type="text" name="new_username" class="form-control" placeholder="Letters A-Z a-z, 0-9, _" required pattern="[a-zA-Z0-9_]{3,20}">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email Address</label>
                        <input type="email" name="new_email" class="form-control" placeholder="user@example.com" required>
                    </div>
                </div>
                <div class="form-row two-col">
                    <div class="form-group">
                        <label><i class="fas fa-key"></i> Password</label>
                        <div class="pw-wrap">
                            <input type="password" name="new_password" class="form-control" placeholder="Strong password" required autocomplete="new-password" oninput="checkPwStrength(this,'au_bar','au_label')">
                            <i class="fas fa-eye pw-eye"></i>
                        </div>
                        <div class="pw-strength-bar"><div id="au_bar"></div></div>
                        <div class="pw-strength-label" id="au_label"></div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-id-badge"></i> Role</label>
                        <select name="new_role" class="form-control">
                            <option value="admin">Admin</option>
                            <option value="editor">Editor</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="btn-acct btn-success">
                    <i class="fas fa-user-plus"></i> Create Admin User
                </button>
            </form>
        </div>
    </div>
</div>

<!-- ════════════════════════════════════════════════════
     TAB 4 — MANAGE USERS  (super_admin only)
     ════════════════════════════════════════════════════ -->
<div class="atab-pane" id="tab-manage-users">
    <div class="acct-card">
        <div class="acct-card-header">
            <span class="ach-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed)"><i class="fas fa-users-cog"></i></span>
            <div>
                <h3>Manage Admin Users</h3>
                <p>Enable/disable accounts &bull; Delete users &bull; Super admin only</p>
            </div>
        </div>
        <div class="acct-card-body" style="padding:0;">
            <div id="msg-manage" style="padding:16px 24px;"></div>
            <div style="overflow-x:auto;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr id="urow-<?php echo $u['id']; ?>">
                            <td><?php echo $u['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($u['username']); ?></strong>
                                <?php if ($u['id'] == $adminId): ?><span class="badge-you">You</span><?php endif; ?></td>
                            <td><?php echo htmlspecialchars($u['email'] ?? '—'); ?></td>
                            <td><span class="badge-role badge-<?php echo $u['role']; ?>"><?php echo ucfirst(str_replace('_',' ',$u['role'])); ?></span></td>
                            <td><span class="badge-status badge-<?php echo $u['is_active']?'active':'inactive'; ?>"><?php echo $u['is_active']?'Active':'Disabled'; ?></span></td>
                            <td><?php echo $u['last_login'] ? date('d M y H:i', strtotime($u['last_login'])) : 'Never'; ?></td>
                            <td><?php echo date('d M y', strtotime($u['created_at'])); ?></td>
                            <td class="actions-cell">
                                <?php if ($u['id'] != $adminId): ?>
                                <button class="btn-icon btn-toggle" title="<?php echo $u['is_active']?'Disable':'Enable'; ?> user"
                                    onclick="toggleUser(<?php echo $u['id']; ?>, this)">
                                    <i class="fas fa-<?php echo $u['is_active']?'ban':'check-circle'; ?>"></i>
                                </button>
                                <button class="btn-icon btn-delete" title="Delete user"
                                    onclick="confirmDeleteUser(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['username'],ENT_QUOTES); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php else: ?>
                                <span style="color:#94a3b8;font-size:12px;">Current</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════
     TAB 5 — AUDIT LOG
     ════════════════════════════════════════════════════ -->
<div class="atab-pane" id="tab-audit">
    <div class="acct-card">
        <div class="acct-card-header">
            <span class="ach-icon" style="background:linear-gradient(135deg,#64748b,#475569)"><i class="fas fa-history"></i></span>
            <div>
                <h3>Account Audit Log</h3>
                <p>Last 30 account-related actions — tamper-proof record</p>
            </div>
        </div>
        <div class="acct-card-body" style="padding:0;">
            <div style="overflow-x:auto;">
                <table class="acct-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Action</th>
                            <th>Target User</th>
                            <th>Performed By</th>
                            <th>Status</th>
                            <th>IP</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($auditLog)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:30px;color:#94a3b8;">No audit records yet</td></tr>
                        <?php else: foreach ($auditLog as $log): ?>
                        <tr>
                            <td style="white-space:nowrap;"><?php echo date('d M y H:i', strtotime($log['created_at'])); ?></td>
                            <td><span class="audit-action"><?php echo htmlspecialchars(str_replace('_',' ',ucfirst($log['action']))); ?></span></td>
                            <td><?php echo htmlspecialchars($log['admin_name'] ?? '—'); ?></td>
                            <td><?php echo htmlspecialchars($log['actor_name'] ?? 'System'); ?></td>
                            <td><span class="badge-status badge-<?php echo $log['status']==='success'?'active':'inactive'; ?>"><?php echo ucfirst($log['status']); ?></span></td>
                            <td><code style="font-size:12px;"><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></code></td>
                            <td style="font-size:13px;color:#64748b;"><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ── DELETE USER MODAL ─────────────────────────────── -->
<div class="modal-overlay" id="deleteModal" style="display:none;">
    <div class="modal-box">
        <div class="modal-hdr" style="background:#e74c3c;">
            <i class="fas fa-exclamation-triangle"></i>
            <h3>Confirm User Deletion</h3>
        </div>
        <div class="modal-body">
            <p>You are about to permanently delete admin user: <strong id="del-uname"></strong></p>
            <p style="color:#e74c3c;margin-top:8px;">This action <strong>cannot be undone</strong>. Enter your password to confirm.</p>
            <div class="form-group" style="margin-top:16px;">
                <label>Your Password</label>
                <div class="pw-wrap">
                    <input type="password" id="del-pw" class="form-control" placeholder="Your current password" autocomplete="current-password">
                    <i class="fas fa-eye pw-eye"></i>
                </div>
            </div>
            <div id="del-msg"></div>
        </div>
        <div class="modal-footer">
            <button class="btn-acct btn-secondary" onclick="closeDeleteModal()">Cancel</button>
            <button class="btn-acct btn-danger" id="del-confirm-btn" onclick="executeDeleteUser()">
                <i class="fas fa-trash"></i> Delete User
            </button>
        </div>
    </div>
</div>

<style>
/* ── Account Manager — Complete SEO System Theme ──── */

/* Hero header */
.page-header{background:white;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:32px 36px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:20px;border-left:6px solid transparent;border-image:linear-gradient(180deg,#667eea,#764ba2) 1;}
.page-header h1{font-size:28px;font-weight:800;color:#1e293b;display:flex;align-items:center;gap:12px;margin:0 0 4px;}
.page-header h1 i{color:#667eea;}
.page-header .subtitle{color:#64748b;font-size:14px;margin:0;}

/* Tabs */
.acct-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px;background:white;padding:8px;border-radius:14px;box-shadow:0 2px 12px rgba(0,0,0,.06);}
.atab{background:transparent;border:none;padding:10px 18px;border-radius:10px;cursor:pointer;font-size:13.5px;font-weight:600;color:#64748b;display:flex;align-items:center;gap:8px;transition:.2s;}
.atab:hover{background:#f1f5f9;color:#334155;}
.atab.active{background:linear-gradient(135deg,#667eea,#764ba2);color:white;box-shadow:0 4px 14px rgba(102,126,234,.35);}
.atab.active i{color:white;}
.atab-pane{display:none;}
.atab-pane.active{display:block;}

/* Card */
.acct-card{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;margin-bottom:24px;}
.acct-card-header{display:flex;align-items:center;gap:16px;padding:22px 28px;border-bottom:1px solid #f1f5f9;background:white;}
.ach-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:18px;color:white;flex-shrink:0;}
.acct-card-header h3{font-size:18px;font-weight:700;color:#1e293b;margin:0 0 4px;}
.acct-card-header p{font-size:13px;color:#64748b;margin:0;}
.acct-card-body{padding:28px;}

/* Security badge */
.security-badge{display:flex;align-items:center;gap:10px;background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:10px 14px;margin-bottom:22px;font-size:13px;color:#1e40af;}
.security-badge i{font-size:16px;flex-shrink:0;}

/* Forms */
.acct-form .form-row{margin-bottom:0;}
.acct-form .two-col{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
@media(max-width:640px){.acct-form .two-col{grid-template-columns:1fr;}}
.form-group{margin-bottom:20px;}
.form-group label{display:flex;align-items:center;gap:6px;font-weight:600;color:#334155;margin-bottom:7px;font-size:13px;}
.form-control{width:100%;padding:11px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:14px;transition:.25s;font-family:inherit;background:white;}
.form-control:focus{outline:none;border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.1);}
select.form-control{appearance:none;background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2394a3b8'%3E%3Cpath stroke-width='2' d='M19 9l-7 7-7-7'/%3E%3C/svg%3E") no-repeat right 12px center/18px white;}
.field-hint{font-size:12px;color:#94a3b8;margin-top:5px;display:block;}
.pw-wrap{position:relative;}
.pw-wrap input{padding-right:42px;}
.pw-eye{position:absolute;right:13px;top:50%;transform:translateY(-50%);color:#94a3b8;cursor:pointer;font-size:15px;}
.pw-strength-bar{height:5px;background:#e2e8f0;border-radius:3px;margin-top:7px;overflow:hidden;}
.pw-strength-bar div{height:100%;border-radius:3px;transition:width .3s,background .3s;width:0;}
.pw-strength-label{font-size:12px;margin-top:4px;font-weight:600;}
.pw-rules{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:20px;}
.rule{background:#f1f5f9;padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600;color:#94a3b8;transition:.2s;}
.rule.ok{background:#d1fae5;color:#059669;}

/* Buttons */
.btn-acct{display:inline-flex;align-items:center;gap:8px;padding:12px 24px;border:none;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer;transition:.25s;text-decoration:none;}
.btn-acct:hover{transform:translateY(-2px);}
.btn-primary{background:linear-gradient(135deg,#667eea,#764ba2);color:white;box-shadow:0 6px 18px rgba(102,126,234,.35);}
.btn-primary:hover{box-shadow:0 10px 28px rgba(102,126,234,.45);}
.btn-warning{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;box-shadow:0 6px 18px rgba(245,158,11,.35);}
.btn-warning:hover{box-shadow:0 10px 28px rgba(245,158,11,.45);}
.btn-success{background:linear-gradient(135deg,#10b981,#059669);color:white;box-shadow:0 6px 18px rgba(16,185,129,.35);}
.btn-success:hover{box-shadow:0 10px 28px rgba(16,185,129,.45);}
.btn-danger{background:linear-gradient(135deg,#ef4444,#dc2626);color:white;box-shadow:0 6px 18px rgba(239,68,68,.35);}
.btn-danger:hover{box-shadow:0 10px 28px rgba(239,68,68,.45);}
.btn-secondary{background:#f1f5f9;color:#334155;border:1px solid #e2e8f0;box-shadow:none;}
.btn-secondary:hover{background:#e2e8f0;transform:none;}

/* Table */
.acct-table{width:100%;border-collapse:collapse;}
.acct-table th{background:#f8fafc;padding:12px 16px;text-align:left;font-size:11px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;border-bottom:1.5px solid #e2e8f0;}
.acct-table td{padding:13px 16px;border-bottom:1px solid #f1f5f9;font-size:14px;color:#334155;vertical-align:middle;}
.acct-table tbody tr:hover{background:#f8faff;}

/* Badges */
.badge-you{background:#eff6ff;color:#3b82f6;padding:2px 8px;border-radius:10px;font-size:11px;font-weight:700;margin-left:6px;}
.badge-role{padding:3px 10px;border-radius:10px;font-size:12px;font-weight:700;}
.badge-super_admin{background:#f3e8ff;color:#7c3aed;}
.badge-admin{background:#dbeafe;color:#1d4ed8;}
.badge-editor{background:#fef3c7;color:#b45309;}
.badge-status{padding:3px 10px;border-radius:10px;font-size:12px;font-weight:700;}
.badge-active{background:#d1fae5;color:#059669;}
.badge-inactive{background:#fee2e2;color:#dc2626;}
.audit-action{font-size:13px;font-weight:600;color:#334155;}
.actions-cell{display:flex;gap:6px;align-items:center;}
.btn-icon{width:32px;height:32px;border:none;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:13px;transition:.2s;}
.btn-toggle{background:#eff6ff;color:#3b82f6;}
.btn-toggle:hover{background:#dbeafe;}
.btn-delete{background:#fee2e2;color:#dc2626;}
.btn-delete:hover{background:#fecaca;}

/* Alerts */
.alert-msg{padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:14px;display:flex;align-items:flex-start;gap:10px;}
.alert-msg.ok{background:#f0fdf4;border:1.5px solid #86efac;color:#065f46;}
.alert-msg.err{background:#fff5f5;border:1.5px solid #fca5a5;color:#991b1b;}
.alert-msg.info{background:#eff6ff;border:1.5px solid #93c5fd;color:#1e40af;}

/* Modal */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9000;display:flex;align-items:center;justify-content:center;padding:20px;}
.modal-box{background:white;border-radius:18px;overflow:hidden;max-width:460px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.3);}
.modal-hdr{display:flex;align-items:center;gap:12px;padding:20px 24px;color:white;}
.modal-hdr i{font-size:22px;}
.modal-hdr h3{font-size:18px;font-weight:700;}
.modal-body{padding:24px;}
.modal-body p{font-size:14px;color:#374151;line-height:1.6;}
.modal-footer{display:flex;justify-content:flex-end;gap:12px;padding:16px 24px;background:#f8fafc;}
</style>

<script>
/* ── Tab switching ──────────────────────────────────── */
document.querySelectorAll('.atab').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.atab').forEach(b => b.classList.remove('active'));
        document.querySelectorAll('.atab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        document.getElementById('tab-' + btn.dataset.tab).classList.add('active');
    });
});

/* ── Password show/hide ─────────────────────────────── */
document.querySelectorAll('.pw-eye').forEach(eye => {
    eye.addEventListener('click', function() {
        const inp = this.previousElementSibling;
        if (!inp || inp.tagName !== 'INPUT') return;
        inp.type = inp.type === 'password' ? 'text' : 'password';
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    });
});

/* ── Password strength checker ──────────────────────── */
function checkPwStrength(input, barId = 'cp_bar', labelId = 'cp_label') {
    const v = input.value;
    let score = 0;
    const tests = [
        [/[A-Z]/, 'r-upper'], [/[a-z]/, 'r-lower'],
        [/[0-9]/, 'r-num'],   [/[^A-Za-z0-9]/, 'r-spec'],
    ];
    // Length rule
    const rLen = document.getElementById('r-len');
    if (rLen) { rLen.classList.toggle('ok', v.length >= 8); }
    tests.forEach(([re, id]) => {
        const el = document.getElementById(id);
        const ok = re.test(v);
        if (ok) score++;
        if (el) { el.textContent = (ok ? '✔' : '✘') + ' ' + el.textContent.replace(/^[✔✘] /, ''); el.classList.toggle('ok', ok); }
    });
    if (v.length >= 8) score++;
    const bar   = document.getElementById(barId);
    const label = document.getElementById(labelId);
    if (!bar || !label) return;
    const pct    = Math.min(100, score * 20);
    const colors = ['#ef4444','#ef4444','#f97316','#f59e0b','#22c55e','#16a34a'];
    const labels = ['','Very Weak','Weak','Fair','Good','Strong'];
    bar.style.width  = pct + '%';
    bar.style.background = colors[score] || '#22c55e';
    label.textContent = labels[score] || 'Strong';
    label.style.color = colors[score] || '#22c55e';
}

/* ── Generic AJAX form submit ───────────────────────── */
function submitForm(formId, msgId, csrfId) {
    const form = document.getElementById(formId);
    const msgDiv = document.getElementById(msgId);
    if (!form) return;

    form.addEventListener('submit', async e => {
        e.preventDefault();
        const btn = form.querySelector('button[type=submit]');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending…';
        msgDiv.innerHTML = '';

        const fd = new FormData(form);
        try {
            const res  = await fetch('../api/account-api.php', {method:'POST', body: fd});
            const data = await res.json();
            if (data.ok) {
                msgDiv.innerHTML = `<div class="alert-msg ok"><i class="fas fa-check-circle"></i> ${data.message}</div>`;
                form.reset();
                /* refresh CSRF */
                if (csrfId) {
                    const nr = await fetch('../api/account-api.php', {method:'POST', body: new URLSearchParams({action:'refresh_csrf', form: csrfId.replace('csrf_','')})});
                    const nd = await nr.json();
                    if (nd.token) document.getElementById(csrfId).value = nd.token;
                }
            } else {
                msgDiv.innerHTML = `<div class="alert-msg err"><i class="fas fa-exclamation-circle"></i> ${data.error || 'An error occurred'}</div>`;
            }
        } catch(err) {
            msgDiv.innerHTML = `<div class="alert-msg err"><i class="fas fa-times-circle"></i> Network error. Please try again.</div>`;
        }
        btn.disabled = false;
        btn.innerHTML = btn.dataset.label || '<i class="fas fa-paper-plane"></i> Send';
    });
}

document.addEventListener('DOMContentLoaded', () => {
    /* Store original button labels */
    document.querySelectorAll('.btn-acct[type=submit]').forEach(b => { b.dataset.label = b.innerHTML; });

    submitForm('form-cp',  'msg-cp',  'csrf_cp');
    submitForm('form-cu',  'msg-cu',  'csrf_cu');
    submitForm('form-add', 'msg-add', 'csrf_add');
});

/* ── Toggle user active ─────────────────────────────── */
async function toggleUser(userId, btn) {
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action', 'toggle_user');
    fd.append('target_id', userId);
    try {
        const r = await fetch('../api/account-api.php', {method:'POST', body: fd});
        const d = await r.json();
        const msgDiv = document.getElementById('msg-manage');
        if (d.ok) {
            msgDiv.innerHTML = `<div class="alert-msg ok"><i class="fas fa-check-circle"></i> ${d.message}</div>`;
            setTimeout(() => location.reload(), 1200);
        } else {
            msgDiv.innerHTML = `<div class="alert-msg err"><i class="fas fa-exclamation-circle"></i> ${d.error}</div>`;
            btn.disabled = false;
        }
    } catch { btn.disabled = false; }
}

/* ── Delete user modal ──────────────────────────────── */
let _delUserId = null;
function confirmDeleteUser(id, username) {
    _delUserId = id;
    document.getElementById('del-uname').textContent = username;
    document.getElementById('del-pw').value = '';
    document.getElementById('del-msg').innerHTML = '';
    document.getElementById('deleteModal').style.display = 'flex';
}
function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    _delUserId = null;
}
async function executeDeleteUser() {
    const pw  = document.getElementById('del-pw').value;
    const btn = document.getElementById('del-confirm-btn');
    if (!pw) { document.getElementById('del-msg').innerHTML = '<div class="alert-msg err">Password is required</div>'; return; }
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action', 'delete_user');
    fd.append('target_id', _delUserId);
    fd.append('confirm_password', pw);
    try {
        const r = await fetch('../api/account-api.php', {method:'POST', body: fd});
        const d = await r.json();
        if (d.ok) {
            closeDeleteModal();
            document.getElementById('msg-manage').innerHTML = `<div class="alert-msg ok"><i class="fas fa-check-circle"></i> ${d.message}</div>`;
            const row = document.getElementById('urow-' + _delUserId);
            if (row) row.remove();
        } else {
            document.getElementById('del-msg').innerHTML = `<div class="alert-msg err">${d.error}</div>`;
            btn.disabled = false;
        }
    } catch { btn.disabled = false; }
}
/* Close modal on backdrop click */
document.getElementById('deleteModal')?.addEventListener('click', e => { if(e.target.id === 'deleteModal') closeDeleteModal(); });
</script>

<?php require_once '../includes/footer.php'; ?>
