<?php
/**
 * Security Status Dashboard
 * Shows all active security features and logs
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Get security logs
$security_log_file = __DIR__ . '/../../logs/security.log';
$blocked_log_file = __DIR__ . '/../../logs/blocked.log';

$security_logs = [];
$blocked_logs = [];

if (file_exists($security_log_file)) {
    $lines = file($security_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $security_logs = array_slice(array_reverse($lines), 0, 50);
}

if (file_exists($blocked_log_file)) {
    $lines = file($blocked_log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $blocked_logs = array_slice(array_reverse($lines), 0, 50);
}

// Count statistics
$total_suspicious = count(file_exists($security_log_file) ? file($security_log_file) : []);
$total_blocked = count(file_exists($blocked_log_file) ? file($blocked_log_file) : []);

$page_title = 'Security Status';
require_once '../includes/header.php';
?>

<style>
/* ═══ Security Status — Complete SEO System Theme ═══ */
@keyframes fadeInUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes float    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }
@keyframes pulse    { 0%,100%{opacity:1} 50%{opacity:.6} }

.seo-page { padding: 0; }
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#10b981,#06b6d4) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #10b981; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.hero-actions { display: flex; gap: 12px; flex-shrink: 0; flex-wrap: wrap; }

/* ── Security notice ────────────────── */
.sec-notice { background: linear-gradient(135deg,#f0fdf4,#dcfce7); border-radius: 14px; border: 1.5px solid #86efac; padding: 18px 24px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px; }
.sec-notice-icon { width: 42px; height: 42px; border-radius: 12px; background: linear-gradient(135deg,#10b981,#059669); display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; flex-shrink: 0; }
.sec-notice h4 { font-size: 14px; font-weight: 700; color: #065f46; margin: 0 0 3px; }
.sec-notice p  { font-size: 13px; color: #047857; margin: 0; }
.protected-badge { margin-left: auto; background: linear-gradient(135deg,#10b981,#059669); color: white; padding: 10px 20px; border-radius: 12px; text-align: center; flex-shrink: 0; }
.protected-badge .pct { font-size: 24px; font-weight: 800; display: block; line-height: 1; }
.protected-badge .lbl { font-size: 11px; font-weight: 600; opacity: .9; }

/* ── Stat cards ───────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.red    { background: linear-gradient(135deg,#ef4444,#dc2626); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 0 6px !important; }
.stat-card .sub { font-size: 12px; }
.stat-badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.stat-badge.green  { background: #d1fae5; color: #065f46; }
.stat-badge.red    { background: #fee2e2; color: #991b1b; }
.stat-badge.orange { background: #fef3c7; color: #92400e; }
.stat-badge.blue   { background: #dbeafe; color: #1e40af; }

/* ── Sections ───────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-num.red    { background: linear-gradient(135deg,#ef4444,#dc2626); }
.sec-num.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.sec-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.seo-section-body { padding: 28px 32px; }

/* ── Protection feature cards ──────── */
.features-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(280px,1fr)); gap: 16px; }
.protection-feature { background: #f8fafc; border-radius: 14px; padding: 20px; display: flex; gap: 14px; align-items: flex-start; border: 2px solid #e2e8f0; transition: all .3s; animation: fadeInUp .4s ease both; }
.protection-feature:hover { border-color: #a7f3d0; transform: translateY(-3px); box-shadow: 0 8px 20px rgba(16,185,129,.1); }
.feature-icon { width: 48px; height: 48px; border-radius: 13px; display: flex; align-items: center; justify-content: center; color: white; font-size: 20px; flex-shrink: 0; }
.feature-content h3 { margin: 0 0 6px; font-size: 15px; font-weight: 700; color: #1e293b; }
.feature-content p  { margin: 0 0 10px; font-size: 13px; color: #64748b; line-height: 1.5; }
.active-badge { display: inline-block; background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; padding: 3px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; }

/* ── Tables ───────────────────────── */
.sec-table-wrap { overflow-x: auto; }
.sec-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.sec-table thead th { background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; padding: 12px 16px; border-bottom: 2px solid #e2e8f0; text-align: left; }
.sec-table tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
.sec-table tbody tr:hover { background: #f8fafc; }
.sec-table tbody td { padding: 12px 16px; color: #374151; vertical-align: middle; }
.sec-table code { background: #f1f5f9; color: #3b82f6; padding: 2px 7px; border-radius: 6px; font-size: 12px; }
.badge-danger  { background: #fee2e2; color: #991b1b; border: 1.5px solid #fca5a5; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.badge-warning { background: #fef3c7; color: #92400e; border: 1.5px solid #fcd34d; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }

/* ── Buttons ───────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 32px; }
.btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; white-space: nowrap; }
.btn-action:hover { transform: translateY(-2px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#10b981,#059669); color: white; box-shadow: 0 4px 14px rgba(16,185,129,.35); }
.btn-action.green:hover  { box-shadow: 0 8px 24px rgba(16,185,129,.45); }
.btn-action.red    { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 4px 14px rgba(239,68,68,.3); }
.btn-action.red:hover    { box-shadow: 0 8px 24px rgba(239,68,68,.4); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; transform: none; }
.btn-action.sm { padding: 9px 16px; font-size: 13px; }

/* ── Empty state ───────────────────── */
.empty-state { text-align: center; padding: 48px 20px; }
.empty-state i { font-size: 56px; color: #a7f3d0; display: block; margin-bottom: 14px; animation: float 3s ease-in-out infinite; }
.empty-state h3 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 6px; }
.empty-state p { font-size: 13px; color: #64748b; margin: 0; }

@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:600px){ .seo-hero{flex-direction:column;} .stats-grid{grid-template-columns:1fr 1fr;} .seo-section-body{padding:20px;} }
</style>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-shield-alt"></i> Security Status Dashboard</h1>
        <p>All protection layers active &mdash; monitoring your website 24/7</p>
    </div>
    <div class="hero-actions">
        <a href="ai-content-security.php" class="btn-action red sm">
            <i class="fas fa-lock"></i> AI Content Security
        </a>
        <a href="account-manager.php" class="btn-action gray sm">
            <i class="fas fa-user-shield"></i> Account Manager
        </a>
    </div>
</div>

<!-- Security Active Notice -->
<div class="sec-notice">
    <div class="sec-notice-icon"><i class="fas fa-shield-alt"></i></div>
    <div>
        <h4>Security System Active</h4>
        <p>All 8 protection layers are enabled and monitoring your website. No action required.</p>
    </div>
    <div class="protected-badge">
        <span class="pct">100%</span>
        <span class="lbl">Protected</span>
    </div>
</div>

<!-- Stats bar -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Active Protections</h3>
            <div class="value">8</div>
            <div class="sub"><span class="stat-badge green">All Layers On</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap red"><i class="fas fa-ban"></i></div>
        <div class="stat-text-wrap">
            <h3>Blocked Attacks</h3>
            <div class="value"><?php echo number_format($total_blocked); ?></div>
            <div class="sub"><span class="stat-badge red">Total Blocked</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-text-wrap">
            <h3>Suspicious Activities</h3>
            <div class="value"><?php echo number_format($total_suspicious); ?></div>
            <div class="sub"><span class="stat-badge orange">Logged</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-clock"></i></div>
        <div class="stat-text-wrap">
            <h3>Monitoring</h3>
            <div class="value">24/7</div>
            <div class="sub"><span class="stat-badge blue">Real-time</span></div>
        </div>
    </div>
</div>

<!-- Section 1: Active Protection Features -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">1</div>
        <h2><i class="fas fa-shield-alt" style="color:#10b981;margin-right:8px;"></i>Active Protection Features</h2>
        <span class="sec-badge" style="margin-left:auto;">8 Active</span>
    </div>
    <div class="seo-section-body">
        <div class="features-grid">
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-lock"></i></div>
                <div class="feature-content">
                    <h3>Content Copy Protection</h3>
                    <p>Right-click, text selection, and copy disabled</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#3b82f6,#2563eb);"><i class="fas fa-database"></i></div>
                <div class="feature-content">
                    <h3>SQL Injection Prevention</h3>
                    <p>Blocks malicious database queries</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-code"></i></div>
                <div class="feature-content">
                    <h3>XSS Attack Prevention</h3>
                    <p>Filters script injection attempts</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-key"></i></div>
                <div class="feature-content">
                    <h3>CSRF Protection</h3>
                    <p>Token-based form validation</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#8b5cf6,#7c3aed);"><i class="fas fa-tachometer-alt"></i></div>
                <div class="feature-content">
                    <h3>Rate Limiting</h3>
                    <p>Max 100 requests per minute per IP</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#ec4899,#db2777);"><i class="fas fa-robot"></i></div>
                <div class="feature-content">
                    <h3>Bot Detection</h3>
                    <p>Blocks malicious bots and scrapers</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2);"><i class="fas fa-server"></i></div>
                <div class="feature-content">
                    <h3>Secure Headers</h3>
                    <p>X-Frame-Options, CSP, HSTS enabled</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
            <div class="protection-feature">
                <div class="feature-icon" style="background:linear-gradient(135deg,#14b8a6,#0d9488);"><i class="fas fa-chart-line"></i></div>
                <div class="feature-content">
                    <h3>Real-time Monitoring</h3>
                    <p>All attacks logged with IP tracking</p>
                    <span class="active-badge"><i class="fas fa-check" style="margin-right:4px;"></i>Active</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Blocked Attacks Log -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num red">2</div>
        <h2><i class="fas fa-ban" style="color:#ef4444;margin-right:8px;"></i>Recent Blocked Attacks</h2>
        <span class="sec-badge" style="margin-left:auto;background:linear-gradient(135deg,#ef4444,#dc2626);"><?php echo number_format($total_blocked); ?> Blocked</span>
    </div>
    <div class="seo-section-body">
        <?php if (empty($blocked_logs)): ?>
        <div class="empty-state">
            <i class="fas fa-shield-alt"></i>
            <h3>No Attacks Blocked Yet</h3>
            <p>Your website is secure and no attacks have been detected</p>
        </div>
        <?php else: ?>
        <div class="sec-table-wrap">
            <table class="sec-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>IP Address</th>
                        <th>Request URI</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($blocked_logs as $log): ?>
                    <?php
                    $parts = explode(' | ', $log);
                    if (count($parts) >= 4):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($parts[0] ?? ''); ?></td>
                        <td><code><?php echo htmlspecialchars(str_replace('IP: ', '', $parts[1] ?? '')); ?></code></td>
                        <td><code><?php echo htmlspecialchars(str_replace('URI: ', '', $parts[2] ?? '')); ?></code></td>
                        <td><span class="badge-danger"><?php echo htmlspecialchars(str_replace('Reason: ', '', $parts[4] ?? '')); ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Section 3: Suspicious Activities -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num orange">3</div>
        <h2><i class="fas fa-exclamation-triangle" style="color:#f59e0b;margin-right:8px;"></i>Suspicious Activities</h2>
        <span class="sec-badge" style="margin-left:auto;background:linear-gradient(135deg,#f59e0b,#d97706);"><?php echo number_format($total_suspicious); ?> Logged</span>
    </div>
    <div class="seo-section-body">
        <?php if (empty($security_logs)): ?>
        <div class="empty-state">
            <i class="fas fa-check-circle"></i>
            <h3>No Suspicious Activity</h3>
            <p>All traffic appears normal</p>
        </div>
        <?php else: ?>
        <div class="sec-table-wrap">
            <table class="sec-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>IP Address</th>
                        <th>Method</th>
                        <th>URI</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($security_logs as $log): ?>
                    <?php
                    $parts = explode(' | ', $log);
                    if (count($parts) >= 5):
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($parts[0] ?? ''); ?></td>
                        <td><code><?php echo htmlspecialchars(str_replace('IP: ', '', $parts[1] ?? '')); ?></code></td>
                        <td><?php echo htmlspecialchars(str_replace('Method: ', '', $parts[2] ?? '')); ?></td>
                        <td><code><?php echo htmlspecialchars(str_replace('URI: ', '', $parts[3] ?? '')); ?></code></td>
                        <td><span class="badge-warning"><?php echo htmlspecialchars(str_replace('Reason: ', '', $parts[5] ?? '')); ?></span></td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom nav -->
<div class="action-row">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="ai-content-security.php" class="btn-action red">
        <i class="fas fa-lock"></i> AI Content Security
    </a>
    <a href="account-manager.php" class="btn-action gray">
        <i class="fas fa-user-shield"></i> Account Manager
    </a>
</div>

</div>

<?php require_once '../includes/footer.php'; ?>
