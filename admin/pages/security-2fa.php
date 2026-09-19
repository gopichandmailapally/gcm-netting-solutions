<?php
/**
 * 2FA & Trusted Devices Settings Page
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../includes/2fa-helper.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); exit;
}

$page_title = '2FA & Trusted Devices';
include '../includes/header.php';

// Load settings + trusted devices
$cfg = gcm_2fa_settings();
$trusted_devices = [];
try {
    $db  = Database::getInstance();
    $pdo = $db->getConnection();
    gcm_2fa_init_tables($pdo);
    $st = $pdo->query("SELECT * FROM admin_trusted_devices WHERE expires_at > NOW() ORDER BY trusted_at DESC");
    $trusted_devices = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
    // Mark current device
    $current_token = $_COOKIE['gcm_td'] ?? '';
} catch (\Throwable $e) { $trusted_devices = []; $current_token = ''; }
?>
<style>
.tfa-hero{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:26px 30px;margin-bottom:22px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-left:6px solid #667eea;}
.tfa-hero h1{font-size:24px;font-weight:800;color:#1e293b;margin:0 0 4px;}
.tfa-hero p{color:#64748b;font-size:13px;margin:0;}
.tfa-badge{background:#eff6ff;border:1.5px solid #c7d2fe;border-radius:12px;padding:12px 20px;text-align:center;flex-shrink:0;}
.tfa-badge-val{font-size:26px;font-weight:900;color:#4f46e5;}
.tfa-badge-lbl{font-size:11px;color:#6366f1;font-weight:600;}
.tfa-card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:22px;overflow:hidden;}
.tfa-card-hdr{padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:10px;}
.tfa-card-hdr h2{font-size:15px;font-weight:800;color:#1e293b;margin:0;flex:1;}
.tfa-card-body{padding:20px 24px;}
.tfa-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid #f8fafc;gap:20px;}
.tfa-row:last-child{border-bottom:none;}
.tfa-row-left{flex:1;}
.tfa-row-title{font-size:14px;font-weight:700;color:#1e293b;}
.tfa-row-desc{font-size:12px;color:#64748b;margin-top:3px;}
.tfa-toggle{position:relative;width:52px;height:28px;flex-shrink:0;}
.tfa-toggle input{opacity:0;width:0;height:0;}
.tfa-toggle-slider{position:absolute;inset:0;background:#e2e8f0;border-radius:14px;cursor:pointer;transition:.3s;}
.tfa-toggle-slider::before{content:'';position:absolute;width:20px;height:20px;background:#fff;border-radius:50%;left:4px;top:4px;transition:.3s;box-shadow:0 2px 4px rgba(0,0,0,.2);}
.tfa-toggle input:checked+.tfa-toggle-slider{background:#667eea;}
.tfa-toggle input:checked+.tfa-toggle-slider::before{transform:translateX(24px);}
.tfa-input{width:100%;padding:10px 14px;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;color:#1e293b;outline:none;transition:border-color .2s;}
.tfa-input:focus{border-color:#667eea;box-shadow:0 0 0 3px rgba(102,126,234,.12);}
.tfa-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
@media(max-width:700px){.tfa-grid{grid-template-columns:1fr;}}
.tfa-label{font-size:12px;font-weight:700;color:#475569;margin-bottom:6px;display:block;}
.tfa-hint{font-size:11px;color:#94a3b8;margin-top:4px;}
.tfa-save-btn{display:inline-flex;align-items:center;gap:8px;padding:11px 26px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;transition:opacity .2s;}
.tfa-save-btn:hover{opacity:.88;}
.tfa-save-status{font-size:13px;margin-left:14px;color:#64748b;}
.tfa-callmebot-box{background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:12px;padding:16px 20px;margin-top:14px;}
.tfa-callmebot-box h4{font-size:13px;font-weight:800;color:#065f46;margin:0 0 8px;display:flex;align-items:center;gap:7px;}
.tfa-callmebot-box ol{padding-left:18px;font-size:12.5px;color:#166534;line-height:2;}
.tfa-callmebot-box code{background:#dcfce7;padding:2px 6px;border-radius:4px;font-size:12px;font-family:monospace;}
/* Trusted devices table */
.tfa-table{width:100%;border-collapse:collapse;}
.tfa-table th{padding:10px 14px;text-align:left;font-size:12px;font-weight:700;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;}
.tfa-table td{padding:11px 14px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.tfa-table tr:last-child td{border-bottom:none;}
.tfa-table tr:hover td{background:#fafafa;}
.badge-current{background:#dcfce7;color:#166534;font-size:10px;font-weight:700;padding:2px 8px;border-radius:5px;margin-left:6px;}
.badge-exp{background:#fef3c7;color:#92400e;font-size:10px;font-weight:700;padding:2px 8px;border-radius:5px;}
.btn-remove{padding:5px 14px;border:none;background:#fee2e2;color:#b91c1c;border-radius:7px;font-size:12px;font-weight:700;cursor:pointer;transition:background .2s;}
.btn-remove:hover{background:#fecaca;}
.tfa-empty{text-align:center;padding:36px;color:#94a3b8;font-size:13px;}
.enabled-pill{display:inline-flex;align-items:center;gap:6px;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;}
.enabled-pill.on{background:#dcfce7;color:#166534;}
.enabled-pill.off{background:#f1f5f9;color:#64748b;}
.tfa-warn-box{background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;padding:14px 18px;font-size:13px;color:#92400e;display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;}
</style>

<!-- Hero -->
<div class="tfa-hero">
    <div>
        <h1><i class="fas fa-shield-check" style="color:#667eea;margin-right:8px;"></i>2FA & Trusted Devices</h1>
        <p>Two-step verification protects admin access — new devices require Email + WhatsApp OTP</p>
    </div>
    <div class="tfa-badge">
        <div class="tfa-badge-val">
            <span class="enabled-pill <?php echo $cfg['enabled'] ? 'on' : 'off'; ?>">
                <i class="fas fa-<?php echo $cfg['enabled'] ? 'lock' : 'lock-open'; ?>"></i>
                <?php echo $cfg['enabled'] ? 'ENABLED' : 'DISABLED'; ?>
            </span>
        </div>
        <div class="tfa-badge-lbl"><?php echo count($trusted_devices); ?> trusted device(s)</div>
    </div>
</div>


<!-- ── 2FA Toggle & Config ──────────────────────────────────────── -->
<div class="tfa-card">
    <div class="tfa-card-hdr">
        <h2><i class="fas fa-cog" style="color:#667eea;"></i> 2FA Configuration</h2>
    </div>
    <div class="tfa-card-body">

        <div class="tfa-row">
            <div class="tfa-row-left">
                <div class="tfa-row-title">Enable 2-Step Verification</div>
                <div class="tfa-row-desc">When ON, new/untrusted devices must verify with OTP before logging in</div>
            </div>
            <label class="tfa-toggle">
                <input type="checkbox" id="cfg_enabled" <?php echo $cfg['enabled']?'checked':''; ?>>
                <span class="tfa-toggle-slider"></span>
            </label>
        </div>

        <div class="tfa-row">
            <div class="tfa-row-left">
                <div class="tfa-row-title"><i class="fas fa-envelope" style="color:#667eea;"></i> Email OTP</div>
                <div class="tfa-row-desc">Send a 6-digit OTP to the admin email address below</div>
            </div>
            <label class="tfa-toggle">
                <input type="checkbox" id="cfg_email_otp" <?php echo $cfg['email_otp']?'checked':''; ?>>
                <span class="tfa-toggle-slider"></span>
            </label>
        </div>

        <div class="tfa-grid" style="margin-top:20px;">
            <div>
                <label class="tfa-label">Admin Email (OTP recipient)</label>
                <input type="email" id="cfg_email" class="tfa-input" value="<?php echo htmlspecialchars($cfg['admin_email']); ?>" placeholder="admin@example.com">
                <div class="tfa-hint">Email where OTP codes will be sent</div>
            </div>
            <div>
                <label class="tfa-label">OTP Expiry (minutes)</label>
                <input type="number" id="cfg_expiry" class="tfa-input" value="<?php echo (int)$cfg['otp_expiry_minutes']; ?>" min="5" max="30">
                <div class="tfa-hint">How long each OTP is valid (5–30 min)</div>
            </div>
            <div>
                <label class="tfa-label">Trusted Device Duration (days)</label>
                <input type="number" id="cfg_td_days" class="tfa-input" value="<?php echo (int)$cfg['trusted_device_days']; ?>" min="1" max="90">
                <div class="tfa-hint">How many days a trusted device stays trusted (1–90)</div>
            </div>
        </div>

        <div style="margin-top:22px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
            <button class="tfa-save-btn" onclick="saveSettings()">
                <i class="fas fa-save"></i> Save Settings
            </button>
            <button type="button" onclick="testOtp('email')" style="display:inline-flex;align-items:center;gap:7px;padding:11px 20px;background:#eff6ff;color:#1d4ed8;border:1.5px solid #bfdbfe;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                <i class="fas fa-envelope"></i> Test Email OTP
            </button>
            <span class="tfa-save-status" id="save-status"></span>
        </div>
        <div id="test-result" style="display:none;margin-top:12px;padding:12px 16px;border-radius:10px;font-size:13px;"></div>

        <!-- SMTP Config -->
        <?php $smtp_saved = !empty($cfg['smtp_username']) && !empty($cfg['smtp_password']); ?>
        <div style="background:#fffbeb;border:1.5px solid #fde68a;border-radius:12px;padding:16px 20px;margin-top:20px;">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:14px;">
                <h4 style="font-size:13px;font-weight:800;color:#92400e;margin:0;display:flex;align-items:center;gap:8px;">
                    <i class="fas fa-server"></i> Email SMTP Settings
                    <span style="font-size:11px;font-weight:600;background:#fef3c7;padding:2px 8px;border-radius:5px;">Required for reliable email OTP delivery</span>
                </h4>
                <?php if ($smtp_saved): ?>
                <button type="button" id="smtp-edit-btn" onclick="smtpEdit()" style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#fff;color:#92400e;border:1.5px solid #f59e0b;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;">
                    <i class="fas fa-pencil-alt"></i> Edit
                </button>
                <?php endif; ?>
            </div>

            <!-- ── Saved view (shown when SMTP is configured) ── -->
            <div id="smtp-view" style="<?php echo $smtp_saved ? '' : 'display:none;'; ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px 24px;">
                    <div>
                        <div style="font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Host</div>
                        <div style="font-size:13px;color:#1e293b;font-weight:600;" id="view-host"><?php echo htmlspecialchars($cfg['smtp_host'] ?? 'smtp.hostinger.com'); ?>:<span id="view-port"><?php echo (int)($cfg['smtp_port'] ?? 587); ?></span> (<span id="view-enc"><?php echo strtoupper($cfg['smtp_encryption'] ?? 'TLS'); ?></span>)</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Username</div>
                        <div style="font-size:13px;color:#1e293b;font-weight:600;" id="view-user"><?php echo htmlspecialchars($cfg['smtp_username'] ?? ''); ?></div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">Password</div>
                        <div style="font-size:13px;color:#1e293b;font-weight:600;letter-spacing:3px;">●●●●●●●●●●●●</div>
                    </div>
                    <div>
                        <div style="font-size:11px;font-weight:700;color:#92400e;text-transform:uppercase;letter-spacing:.5px;margin-bottom:3px;">From Name</div>
                        <div style="font-size:13px;color:#1e293b;font-weight:600;" id="view-fname"><?php echo htmlspecialchars($cfg['smtp_from_name'] ?? 'GCM Netting Solutions Admin'); ?></div>
                    </div>
                </div>
                <div style="margin-top:12px;display:flex;align-items:center;gap:8px;font-size:12px;color:#166534;background:#dcfce7;border-radius:8px;padding:9px 14px;">
                    <i class="fas fa-check-circle"></i> SMTP configured — emails will be sent via authenticated SMTP (not PHP mail)
                </div>
            </div>

            <!-- ── Edit form (shown when not configured or Edit clicked) ── -->
            <div id="smtp-edit" style="<?php echo $smtp_saved ? 'display:none;' : ''; ?>">
                <?php if (!$smtp_saved): ?>
                <p style="font-size:12px;color:#92400e;margin:0 0 14px;">PHP mail() often goes to spam. Enter your Hostinger email SMTP credentials to send OTPs directly and reliably.</p>
                <?php endif; ?>
                <div class="tfa-grid">
                    <div>
                        <label class="tfa-label">SMTP Host</label>
                        <input type="text" id="cfg_smtp_host" class="tfa-input" value="<?php echo htmlspecialchars($cfg['smtp_host'] ?? 'smtp.hostinger.com'); ?>" placeholder="smtp.hostinger.com">
                        <div class="tfa-hint">Hostinger: <strong>smtp.hostinger.com</strong></div>
                    </div>
                    <div>
                        <label class="tfa-label">SMTP Port</label>
                        <input type="number" id="cfg_smtp_port" class="tfa-input" value="<?php echo (int)($cfg['smtp_port'] ?? 587); ?>">
                        <div class="tfa-hint">587 (TLS) or 465 (SSL)</div>
                    </div>
                    <div>
                        <label class="tfa-label">Encryption</label>
                        <select id="cfg_smtp_enc" class="tfa-input">
                            <option value="tls"  <?php echo ($cfg['smtp_encryption']??'tls')==='tls'  ?'selected':''; ?>>TLS (port 587) — recommended</option>
                            <option value="ssl"  <?php echo ($cfg['smtp_encryption']??'')==='ssl'  ?'selected':''; ?>>SSL (port 465)</option>
                            <option value="none" <?php echo ($cfg['smtp_encryption']??'')==='none' ?'selected':''; ?>>None (port 25)</option>
                        </select>
                    </div>
                    <div>
                        <label class="tfa-label">SMTP Username (email address)</label>
                        <input type="email" id="cfg_smtp_user" class="tfa-input" value="<?php echo htmlspecialchars($cfg['smtp_username'] ?? ''); ?>" placeholder="admin@gcmsafetynets.in">
                        <div class="tfa-hint">Your Hostinger email address</div>
                    </div>
                    <div>
                        <label class="tfa-label">SMTP Password</label>
                        <input type="text" id="cfg_smtp_pass" class="tfa-input" value="<?php echo htmlspecialchars($cfg['smtp_password'] ?? ''); ?>" placeholder="Email account password" autocomplete="off">
                        <div class="tfa-hint">Password for the email account above</div>
                    </div>
                    <div>
                        <label class="tfa-label">From Name</label>
                        <input type="text" id="cfg_smtp_fname" class="tfa-input" value="<?php echo htmlspecialchars($cfg['smtp_from_name'] ?? 'GCM Netting Solutions Admin'); ?>">
                    </div>
                </div>
                <div style="margin-top:14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                    <button type="button" onclick="saveSmtp()" style="display:inline-flex;align-items:center;gap:7px;padding:11px 22px;background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;border:none;border-radius:10px;font-size:13px;font-weight:700;cursor:pointer;">
                        <i class="fas fa-save"></i> Save SMTP Settings
                    </button>
                    <?php if ($smtp_saved): ?>
                    <button type="button" onclick="smtpCancel()" style="padding:10px 18px;background:#f8fafc;color:#64748b;border:1.5px solid #e2e8f0;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>
                    <?php endif; ?>
                    <span id="smtp-save-status" style="font-size:13px;font-weight:600;"></span>
                </div>
                <div style="margin-top:12px;background:#fef3c7;border-radius:8px;padding:10px 14px;font-size:12px;color:#78350f;">
                    <strong>How to get SMTP credentials:</strong> Hostinger → Email Accounts → Create account (e.g. <code>admin@gcmsafetynets.in</code>) → use that email + password above
                </div>
            </div>
        </div>

    </div>
</div>

<!-- ── Trusted Devices ─────────────────────────────────────────── -->
<div class="tfa-card">
    <div class="tfa-card-hdr">
        <h2><i class="fas fa-laptop" style="color:#667eea;"></i> Trusted Devices</h2>
        <span style="font-size:12px;color:#64748b;"><?php echo count($trusted_devices); ?> active device(s)</span>
    </div>
    <div class="tfa-card-body" style="padding:0;">
        <?php if (empty($trusted_devices)): ?>
        <div class="tfa-empty"><i class="fas fa-desktop" style="font-size:28px;display:block;margin-bottom:10px;"></i>No trusted devices yet.<br>After your next 2FA login, check "Don't ask OTP again on this device".</div>
        <?php else: ?>
        <div style="overflow-x:auto;">
        <table class="tfa-table">
            <thead>
                <tr>
                    <th>Device</th>
                    <th>IP Address</th>
                    <th>Trusted Since</th>
                    <th>Expires</th>
                    <th>Last Used</th>
                    <th></th>
                </tr>
            </thead>
            <tbody id="devices-tbody">
            <?php foreach ($trusted_devices as $dev):
                $is_current = (!empty($current_token) && $dev['device_token'] === $current_token);
                $exp_days   = max(0, (int)round((strtotime($dev['expires_at']) - time()) / 86400));
            ?>
            <tr id="dev-row-<?php echo $dev['id']; ?>">
                <td>
                    <i class="fas fa-<?php echo strpos($dev['device_name'],'iOS')!==false||strpos($dev['device_name'],'Android')!==false?'mobile-alt':'laptop'; ?>" style="color:#667eea;margin-right:6px;"></i>
                    <?php echo htmlspecialchars($dev['device_name'] ?? 'Unknown'); ?>
                    <?php if ($is_current): ?><span class="badge-current">THIS DEVICE</span><?php endif; ?>
                </td>
                <td style="color:#64748b;"><?php echo htmlspecialchars($dev['ip_address'] ?? '–'); ?></td>
                <td style="color:#64748b;"><?php echo date('M j, Y', strtotime($dev['trusted_at'])); ?></td>
                <td><?php if ($exp_days <= 3): ?><span class="badge-exp"><?php echo $exp_days; ?> day(s)</span><?php else: ?><span style="color:#64748b;"><?php echo $exp_days; ?> days</span><?php endif; ?></td>
                <td style="color:#64748b;"><?php echo $dev['last_used'] ? date('M j, H:i', strtotime($dev['last_used'])) : '–'; ?></td>
                <td>
                    <button class="btn-remove" onclick="removeDevice(<?php echo $dev['id']; ?>, this)">
                        <i class="fas fa-trash"></i> Remove
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
async function testOtp(channel) {
    const box = document.getElementById('test-result');
    box.style.display = 'block';
    box.style.background = '#f8fafc'; box.style.border = '1.5px solid #e2e8f0'; box.style.color = '#475569';
    box.textContent = `Sending test ${channel} OTP…`;
    try {
        const r = await fetch('../api/test-2fa-otp.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({channel})});
        const d = await r.json();
        box.textContent = d.message || (d.success ? 'Sent!' : 'Failed');
        if (d.success) { box.style.background='#dcfce7'; box.style.border='1.5px solid #bbf7d0'; box.style.color='#166534'; }
        else           { box.style.background='#fee2e2'; box.style.border='1.5px solid #fecaca'; box.style.color='#991b1b'; }
    } catch(e) { box.textContent = 'Network error.'; box.style.background='#fee2e2'; box.style.color='#991b1b'; }
}

function smtpEdit() {
    document.getElementById('smtp-view').style.display = 'none';
    document.getElementById('smtp-edit').style.display = 'block';
    document.getElementById('smtp-edit-btn').style.display = 'none';
}
function smtpCancel() {
    document.getElementById('smtp-edit').style.display = 'none';
    document.getElementById('smtp-view').style.display = 'block';
    document.getElementById('smtp-edit-btn').style.display = 'inline-flex';
    document.getElementById('smtp-save-status').textContent = '';
}
async function saveSmtp() {
    const st = document.getElementById('smtp-save-status');
    st.textContent = 'Saving…'; st.style.color = '#94a3b8';
    const body = {
        smtp_host:       document.getElementById('cfg_smtp_host').value,
        smtp_port:       document.getElementById('cfg_smtp_port').value,
        smtp_encryption: document.getElementById('cfg_smtp_enc').value,
        smtp_username:   document.getElementById('cfg_smtp_user').value,
        smtp_password:   document.getElementById('cfg_smtp_pass').value,
        smtp_from_name:  document.getElementById('cfg_smtp_fname').value,
    };
    if (!body.smtp_username || !body.smtp_password) {
        st.textContent = '❌ Username and password are required'; st.style.color = '#ef4444'; return;
    }
    try {
        const r = await fetch('../api/save-2fa-settings.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
        const d = await r.json();
        if (d.success) {
            st.textContent = '✅ SMTP saved!'; st.style.color = '#10b981';
            // Update view panel
            document.getElementById('view-host').innerHTML  = body.smtp_host + ':<span id="view-port">' + body.smtp_port + '</span> (<span id="view-enc">' + body.smtp_encryption.toUpperCase() + '</span>)';
            document.getElementById('view-user').textContent  = body.smtp_username;
            document.getElementById('view-fname').textContent = body.smtp_from_name;
            setTimeout(() => {
                document.getElementById('smtp-edit').style.display = 'none';
                document.getElementById('smtp-view').style.display = 'block';
                const editBtn = document.getElementById('smtp-edit-btn');
                if (editBtn) { editBtn.style.display = 'inline-flex'; }
                else {
                    // First time saving — inject Edit button
                    const hdr = document.querySelector('#smtp-view').closest('.tfa-card-body, div').querySelector('div');
                    const btn = document.createElement('button');
                    btn.id = 'smtp-edit-btn'; btn.type = 'button'; btn.onclick = smtpEdit;
                    btn.innerHTML = '<i class="fas fa-pencil-alt"></i> Edit';
                    btn.setAttribute('style','display:inline-flex;align-items:center;gap:6px;padding:7px 16px;background:#fff;color:#92400e;border:1.5px solid #f59e0b;border-radius:8px;font-size:12px;font-weight:700;cursor:pointer;');
                }
                st.textContent = '';
            }, 1200);
        } else {
            st.textContent = '❌ ' + (d.message || 'Save failed'); st.style.color = '#ef4444';
        }
    } catch(e) { st.textContent = '❌ Network error'; st.style.color = '#ef4444'; }
}

async function saveSettings() {
    const status = document.getElementById('save-status');
    status.textContent = 'Saving…'; status.style.color = '#94a3b8';
    const body = {
        enabled:             document.getElementById('cfg_enabled').checked,
        email_otp:           document.getElementById('cfg_email_otp').checked,
        admin_email:         document.getElementById('cfg_email').value,
        otp_expiry_minutes:  document.getElementById('cfg_expiry').value,
        trusted_device_days: document.getElementById('cfg_td_days').value,
        smtp_host:           document.getElementById('cfg_smtp_host').value,
        smtp_port:           document.getElementById('cfg_smtp_port').value,
        smtp_encryption:     document.getElementById('cfg_smtp_enc').value,
        smtp_username:       document.getElementById('cfg_smtp_user').value,
        smtp_password:       document.getElementById('cfg_smtp_pass').value,
        smtp_from_name:      document.getElementById('cfg_smtp_fname').value,
    };
    try {
        const r = await fetch('../api/save-2fa-settings.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body)});
        const d = await r.json();
        status.textContent = d.success ? '✅ Saved!' : '❌ ' + d.message;
        status.style.color  = d.success ? '#10b981' : '#ef4444';
        if (d.success) setTimeout(() => location.reload(), 1200);
    } catch(e) { status.textContent = '❌ Network error'; status.style.color='#ef4444'; }
}

async function removeDevice(id, btn) {
    if (!confirm('Remove this trusted device? It will need to verify OTP again on next login.')) return;
    btn.disabled = true; btn.textContent = 'Removing…';
    try {
        const r = await fetch('../api/remove-trusted-device.php', {method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({id})});
        const d = await r.json();
        if (d.success) {
            document.getElementById('dev-row-' + id)?.remove();
        } else { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash"></i> Remove'; alert('Failed to remove device.'); }
    } catch(e) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-trash"></i> Remove'; }
}
</script>

<?php include '../includes/footer.php'; ?>
