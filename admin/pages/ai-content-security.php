<?php
/**
 * AI Content Security Dashboard
 * Manage protection settings, view protected content, audit log
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

require_once '../includes/ai-content-protection.php';
$protection = new AIContentProtection();

$admin_id  = $_SESSION['admin_id'] ?? 0;
$message   = '';
$msg_type  = 'success';

/* ── Handle POST actions ────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* Set / change security PIN */
    if ($action === 'set_pin') {
        $new_pin  = trim($_POST['new_pin']  ?? '');
        $conf_pin = trim($_POST['confirm_pin'] ?? '');
        if (!preg_match('/^\d{6}$/', $new_pin)) {
            $message  = 'PIN must be exactly 6 digits.';
            $msg_type = 'danger';
        } elseif ($new_pin !== $conf_pin) {
            $message  = 'PINs do not match.';
            $msg_type = 'danger';
        } else {
            $r = $protection->setProtectionPin($new_pin, $admin_id);
            if ($r['success']) {
                $message = 'Security PIN set successfully! Your AI content is now protected.';
            } else {
                $message  = $r['error'];
                $msg_type = 'danger';
            }
        }
    }

    /* Forgot PIN — reset via admin password */
    if ($action === 'forgot_pin') {
        $admin_pass = trim($_POST['admin_password'] ?? '');
        $new_pin    = trim($_POST['new_pin']        ?? '');
        $conf_pin   = trim($_POST['confirm_pin']    ?? '');
        $db         = Database::getInstance();
        $admin      = $db->fetchOne("SELECT password_hash FROM admin_users WHERE id = ?", [$admin_id]);
        if (!$admin || !password_verify($admin_pass, $admin['password_hash'])) {
            $message  = 'Incorrect admin password.';
            $msg_type = 'danger';
        } elseif (!preg_match('/^\d{6}$/', $new_pin)) {
            $message  = 'New PIN must be exactly 6 digits.';
            $msg_type = 'danger';
        } elseif ($new_pin !== $conf_pin) {
            $message  = 'New PINs do not match.';
            $msg_type = 'danger';
        } else {
            $r = $protection->setProtectionPin($new_pin, $admin_id);
            $message  = $r['success'] ? 'Security PIN reset successfully!' : ($r['error'] ?? 'Error');
            $msg_type = $r['success'] ? 'success' : 'danger';
        }
    }

    /* Update settings */
    if ($action === 'update_settings') {
        $protection->updateSetting('auto_protect_on_generate', isset($_POST['auto_protect']) ? '1' : '0', $admin_id);
        $protection->updateSetting('require_pin_to_delete',    isset($_POST['require_pin'])  ? '1' : '0', $admin_id);
        $protection->updateSetting('require_reason_to_delete', isset($_POST['require_reason'])? '1' : '0', $admin_id);
        $cooldown = max(0, (int)($_POST['cooldown_hours'] ?? 24));
        $protection->updateSetting('deletion_cooldown_hours', (string)$cooldown, $admin_id);
        $message = 'Security settings saved.';
    }

    /* Manual unlock */
    if ($action === 'manual_unlock') {
        $ctype = $_POST['content_type'] ?? '';
        $cid   = $_POST['content_id']   ?? '';
        $pin   = $_POST['pin']          ?? '';
        $r     = $protection->manualUnlock($ctype, $cid, $pin, $admin_id);
        if ($r['success']) {
            $message = 'Content unlocked successfully.';
        } else {
            $message  = $r['error'];
            $msg_type = 'danger';
        }
    }

    /* Manual lock */
    if ($action === 'manual_lock') {
        $ctype  = $_POST['content_type']  ?? '';
        $cid    = $_POST['content_id']    ?? '';
        $ctitle = $_POST['content_title'] ?? $cid;
        $protection->manualLock($ctype, $cid, $ctitle, $admin_id);
        $message = 'Content locked.';
    }

    /* Sync all AI content into protection table */
    if ($action === 'sync_all') {
        $added   = $protection->syncAllContent();
        $message = $added > 0
            ? "Sync complete! {$added} new items registered and protected."
            : 'Sync complete. All existing content is already registered.';
    }
}

/* ── Load data ──────────────────────────────────────── */
$stats       = $protection->getStats();
$real        = $protection->getRealCounts();   // same counts as Content Protection page
$groupCounts = $protection->getProtectedCountByGroup();
// Cap AI-generated protected count at real total (excludes orphaned DB rows)
$stats['total_protected'] = min($stats['total_protected'], $real['total']);
$settings   = $protection->getAllSettings();
$protected_list = $protection->getProtectedContent(null, 200);
$audit_log      = $protection->getAuditLog(60);
$has_pin        = $protection->hasPinSet();

/* ── Per-category generation stats (filesystem-based, same as view-generated-pages.php) */
$acs_cat_stats   = [];
$acs_total_areas = 188;
try {
    require_once '../../config/database.php';
    $db = Database::getInstance();
    $acs_total_areas = (int)($db->fetchOne("SELECT COUNT(*) as c FROM service_areas WHERE is_active=1")['c'] ?? 188);

    /* Build keyword_slug → category + name map */
    $acs_kw_slug_map = [];    /* slug → category */
    $acs_kw_cnt_map  = [];    /* category → keyword count */
    foreach (($db->fetchAll("SELECT keyword_slug, category FROM seo_service_keywords WHERE is_active=1") ?: []) as $_k) {
        if (!empty($_k['keyword_slug'])) {
            $acs_kw_slug_map[$_k['keyword_slug']] = $_k['category'];
            $acs_kw_cnt_map[$_k['category']] = ($acs_kw_cnt_map[$_k['category']] ?? 0) + 1;
        }
    }

    /* Count filesystem files per category — identical to view-generated-pages.php fallback */
    $_acs_gen_dir = dirname(dirname(dirname(__FILE__))) . '/generated-pages/';
    $_acs_cat_counts = [];
    foreach (glob($_acs_gen_dir . '*.php') ?: [] as $_acs_f) {
        $_acs_fn    = basename($_acs_f, '.php');
        if ($_acs_fn === 'index') continue;
        $_acs_parts  = explode('-', $_acs_fn);
        $_acs_in_pos = array_search('in', $_acs_parts);
        if ($_acs_in_pos === false || $_acs_in_pos < 1) continue;
        for ($_klen = $_acs_in_pos; $_klen >= 1; $_klen--) {
            $_cand = implode('-', array_slice($_acs_parts, 0, $_klen));
            if (isset($acs_kw_slug_map[$_cand])) {
                $_cat = $acs_kw_slug_map[$_cand];
                $_acs_cat_counts[$_cat] = ($_acs_cat_counts[$_cat] ?? 0) + 1;
                break;
            }
        }
    }

    /* Build final stats array */
    $all_cats = ['PIGEON NETS','BIRD NETS','SAFETY NETS','SPORTS NETS','INVISIBLE GRILLS','CLOTH HANGERS'];
    foreach ($all_cats as $_cat) {
        $kws   = $acs_kw_cnt_map[$_cat]    ?? 0;
        $pages = $_acs_cat_counts[$_cat]   ?? 0;
        $acs_cat_stats[$_cat] = [
            'total_pages' => $pages,
            'kw_count'    => $kws,
            'possible'    => $kws * $acs_total_areas,
        ];
    }
} catch (\Exception $e) {}

$page_title = 'AI Content Security';
include '../includes/header.php';
?>

<style>
/* ── Page-level styles ─────────────────────────────── */
.acs-page { max-width: 1300px; }

/* ── lv- Hero ───────────────────────────────────────── */
.lv-hero{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:28px 32px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-left:6px solid #ff6600;}
.lv-hero h1{font-size:26px;font-weight:800;color:#1e293b;margin:0 0 4px;}.lv-hero p{color:#64748b;font-size:14px;margin:0;}
.lv-badge{background:#fff7ed;border:1.5px solid #fed7aa;border-radius:12px;padding:12px 18px;text-align:center;flex-shrink:0;}
.lv-badge-num{font-size:28px;font-weight:800;color:#ea580c;}.lv-badge-lbl{font-size:11px;color:#c2410c;font-weight:600;}

/* ── Stats strip ─────────────────────────────────────── */
.acs-stats { display: grid; grid-template-columns: repeat(5,1fr); gap: 0; background:#fff; border-radius:14px; box-shadow:0 4px 20px rgba(0,0,0,.07); overflow:hidden; margin-bottom: 20px; }
.acs-stat  { padding: 22px 18px; text-align: center; position: relative; }
.acs-stat + .acs-stat::before { content:''; position:absolute; left:0; top:20%; bottom:20%; width:1px; background:#e2e8f0; }
.acs-stat .icon { display:none; }
.acs-stat .val  { font-size: 32px; font-weight: 800; color: #1E293B; line-height: 1; }
.acs-stat .lbl  { font-size: 12px; color: #64748B; margin-top: 5px; font-weight:600; }
.acs-stat.blue .val   { color: #667eea; }
.acs-stat.orange .val { color: #FF6600; }
.acs-stat.red .val    { color: #EF4444; }
.acs-stat.green .val  { color: #10B981; }

.acs-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 30px; }
@media(max-width:900px){ .acs-grid { grid-template-columns: 1fr; } }
@media(max-width:768px){
    .acs-stats { grid-template-columns: repeat(3,1fr) !important; overflow: visible; border-radius: 14px; }
    .acs-stat + .acs-stat::before { display: none; }
    .acs-stat { border: 1px solid #f1f5f9; border-radius: 10px; margin: 0; }
    .lv-hero { flex-direction: column; padding: 18px 16px; }
    .lv-badge { align-self: flex-start; }
    .form-row { grid-template-columns: 1fr !important; }
    .acs-pin-two-cols { grid-template-columns: 1fr !important; }
    .table-scroll { overflow-x: auto; }
    .protected-table { min-width: 520px; }
}
@media(max-width:480px){
    .acs-stats { grid-template-columns: repeat(2,1fr) !important; gap: 0; }
    .acs-stat { padding: 14px 10px; }
    .acs-stat .val { font-size: 22px; }
    .acs-stat .lbl { font-size: 10px; }
    .acs-grid { gap: 14px; }
    .acs-card { padding: 16px; }
}

.acs-card { background: #fff; border-radius: 14px; padding: 24px; box-shadow: 0 2px 12px rgba(0,0,0,.07); }
.acs-card h3 { font-size: 15px; font-weight: 800; color: #1E293B; margin-bottom: 18px; padding-bottom:12px; border-bottom:1px solid #f1f5f9; display: flex; align-items: center; gap: 8px; }
.acs-card h3 i { color: #FF6600; }

.pin-status { display: flex; align-items: center; gap: 10px; padding: 12px 16px; border-radius: 10px; margin-bottom: 16px; font-size: 13px; font-weight: 600; }
.pin-status.set   { background: rgba(16,185,129,.1);  color: #059669; }
.pin-status.notset{ background: rgba(239,68,68,.1);   color: #DC2626; }

.form-row   { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; text-transform: uppercase; letter-spacing: .5px; }
.form-group input[type=text],
.form-group input[type=number],
.form-group input[type=password] {
    width: 100%; padding: 10px 14px; border: 1.5px solid #E2E8F0; border-radius: 8px;
    font-size: 14px; color: #1E293B; transition: border-color .2s;
}
.form-group input:focus { outline: none; border-color: #667eea; }

.toggle-row { display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #F1F5F9; }
.toggle-row:last-child { border-bottom: none; }
.toggle-label { font-size: 13px; color: #374151; }
.toggle-label small { display: block; color: #94A3B8; font-size: 11px; margin-top: 2px; }
.toggle { position: relative; display: inline-block; width: 44px; height: 24px; }
.toggle input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; inset: 0; background: #CBD5E1; border-radius: 24px; cursor: pointer; transition: .3s; }
.toggle-slider::before { content: ''; position: absolute; width: 18px; height: 18px; left: 3px; bottom: 3px; background: #fff; border-radius: 50%; transition: .3s; }
.toggle input:checked + .toggle-slider { background: #10B981; }
.toggle input:checked + .toggle-slider::before { transform: translateX(20px); }

.btn-primary { background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; border: none; padding: 10px 22px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; transition: opacity .2s; }
.btn-primary:hover { opacity: .9; }
.btn-danger  { background: linear-gradient(135deg,#EF4444,#DC2626); color: #fff; border: none; padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; }
.btn-success { background: linear-gradient(135deg,#10B981,#059669); color: #fff; border: none; padding: 7px 14px; border-radius: 7px; font-size: 12px; font-weight: 600; cursor: pointer; }

.protected-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.protected-table th { background: #F8FAFC; padding: 10px 14px; text-align: left; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748B; border-bottom: 2px solid #E2E8F0; }
.protected-table td { padding: 10px 14px; border-bottom: 1px solid #F1F5F9; color: #374151; vertical-align: middle; }
.protected-table tr:last-child td { border-bottom: none; }
.protected-table tr:hover td { background: #FAFBFF; }

.badge-type { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; }
.badge-page   { background: rgba(102,126,234,.12); color: #667eea; }
.badge-blog   { background: rgba(16,185,129,.12);  color: #059669; }
.badge-review { background: rgba(245,158,11,.12);  color: #D97706; }
.badge-faq    { background: rgba(99,102,241,.12);  color: #6366F1; }
.badge-other  { background: rgba(100,116,139,.12); color: #475569; }

.lock-icon { color: #FF6600; font-size: 14px; margin-right: 4px; }

.audit-item { display: flex; gap: 12px; padding: 10px 0; border-bottom: 1px solid #F1F5F9; }
.audit-item:last-child { border-bottom: none; }
.audit-dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; margin-top: 5px; }
.audit-dot.approved { background: #10B981; }
.audit-dot.blocked  { background: #EF4444; }
.audit-dot.locked   { background: #FF6600; }
.audit-dot.unlocked { background: #3B82F6; }
.audit-dot.other    { background: #CBD5E1; }
.audit-content .title { font-size: 13px; font-weight: 600; color: #1E293B; }
.audit-content .meta  { font-size: 11px; color: #94A3B8; margin-top: 2px; }
.audit-content .reason{ font-size: 12px; color: #64748B; margin-top: 3px; font-style: italic; }

.table-scroll { overflow-x: auto; max-height: 420px; overflow-y: auto; }

.alert { padding: 13px 18px; border-radius: 10px; margin-bottom: 20px; font-size: 13px; font-weight: 500; display: flex; align-items: center; gap: 10px; }
.alert-success { background: rgba(16,185,129,.1); color: #065F46; border: 1px solid rgba(16,185,129,.2); }
.alert-danger  { background: rgba(239,68,68,.1);  color: #7F1D1D; border: 1px solid rgba(239,68,68,.2); }

/* Modal */
.modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.55); z-index: 9999; align-items: center; justify-content: center; }
.modal-overlay.active { display: flex; }
.modal-box { background: #fff; border-radius: 16px; padding: 28px; width: 420px; max-width: 95%; box-shadow: 0 20px 60px rgba(0,0,0,.2); }
.modal-box h3 { font-size: 16px; font-weight: 700; color: #1E293B; margin-bottom: 6px; }
.modal-box p  { font-size: 13px; color: #64748B; margin-bottom: 18px; }
.modal-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 18px; }
.btn-cancel { background: #F1F5F9; color: #475569; border: none; padding: 10px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; }
</style>

<div class="acs-page">

<!-- Hero -->
<div class="lv-hero">
    <div>
        <h1><i class="fas fa-robot" style="color:#ff6600;margin-right:8px;"></i>AI Content Security</h1>
        <p>Protect AI-generated content with PIN, manage deletion rules, and monitor audit activity</p>
    </div>
    <div style="display:flex;gap:12px;flex-shrink:0;">
        <div class="lv-badge">
            <div class="lv-badge-num"><?php echo number_format($real['total']); ?></div>
            <div class="lv-badge-lbl">AI GENERATED</div>
        </div>
        <div class="lv-badge" style="background:#f0fdf4;border-color:#86efac;">
            <div class="lv-badge-num" style="color:#059669;"><?php echo number_format($real['uploaded_total']); ?></div>
            <div class="lv-badge-lbl" style="color:#047857;">UPLOADED</div>
        </div>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $msg_type; ?>">
    <i class="fas fa-<?php echo $msg_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
    <?php echo htmlspecialchars($message); ?>
</div>
<?php endif; ?>

<!-- Stats Row -->
<div class="acs-stats">
    <div class="acs-stat blue">
        <div><div class="val"><?php echo number_format($real['total']); ?></div><div class="lbl">AI Generated Items</div></div>
    </div>
    <div class="acs-stat" style="--ac:#059669;">
        <div><div class="val" style="color:#059669;"><?php echo number_format($real['uploaded_total']); ?></div><div class="lbl">Uploaded Items</div></div>
    </div>
    <div class="acs-stat orange">
        <div><div class="val"><?php echo number_format($stats['total_protected']); ?></div><div class="lbl">AI Content Protected</div></div>
    </div>
    <div class="acs-stat red">
        <div><div class="val"><?php echo $stats['blocked_attempts']; ?></div><div class="lbl">Blocked Deletions (7d)</div></div>
    </div>
    <div class="acs-stat <?php echo $has_pin ? 'green' : 'red'; ?>">
        <div><div class="val" style="font-size:18px;"><?php echo $has_pin ? 'ACTIVE' : 'NOT SET'; ?></div><div class="lbl">Security PIN</div></div>
    </div>
</div>

<!-- ══ Section 1: AI Generated Content ══ -->
<div class="acs-card" style="margin-bottom:20px;">
    <h3>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#667eea,#764ba2);margin-right:10px;flex-shrink:0;"><i class="fas fa-robot" style="color:white;font-size:14px;"></i></span>
        AI Generated Content
        <span style="margin-left:auto;font-size:12px;font-weight:700;color:#10b981;background:#f0fdf4;padding:4px 10px;border-radius:20px;border:1px solid #86efac;">
            <i class="fas fa-shield-alt"></i> <?php echo number_format(min($groupCounts['ai_generated'], $real['total'])); ?> / <?php echo number_format($real['total']); ?> protected
        </span>
    </h3>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:11px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle" style="color:#10b981;font-size:18px;"></i>
        <span style="color:#065f46;font-size:13px;font-weight:600;">All new AI content is automatically protected on generation &mdash; no manual sync required.</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
        <?php
        $ai_breakdown = [
            ['label'=>'Service Pages', 'val'=>$real['service_pages'], 'icon'=>'fa-cogs',          'color'=>'#667eea'],
            ['label'=>'Blog Posts',    'val'=>$real['blogs'],         'icon'=>'fa-newspaper',      'color'=>'#10b981'],
            ['label'=>'Reviews',       'val'=>$real['reviews'],       'icon'=>'fa-star',           'color'=>'#f59e0b'],
            ['label'=>'FAQs',          'val'=>$real['faqs'],          'icon'=>'fa-question-circle', 'color'=>'#6366f1'],
            ['label'=>'Pillar Pages',  'val'=>$real['pillar_pages'],  'icon'=>'fa-file-code',      'color'=>'#06b6d4'],
        ];
        foreach ($ai_breakdown as $b): ?>
        <div style="background:#f8fafc;border-radius:10px;padding:16px 18px;border:1px solid #e2e8f0;text-align:center;">
            <i class="fas <?php echo $b['icon']; ?>" style="color:<?php echo $b['color']; ?>;font-size:22px;margin-bottom:8px;display:block;"></i>
            <div style="font-size:24px;font-weight:800;color:<?php echo $b['color']; ?>;"><?php echo number_format($b['val']); ?></div>
            <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:3px;"><?php echo $b['label']; ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ══ Section 2: Uploaded Content ══ -->
<div class="acs-card" style="margin-bottom:24px;">
    <h3>
        <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:9px;background:linear-gradient(135deg,#10b981,#059669);margin-right:10px;flex-shrink:0;"><i class="fas fa-upload" style="color:white;font-size:14px;"></i></span>
        Uploaded Content
        <span style="margin-left:auto;font-size:12px;font-weight:700;color:#10b981;background:#f0fdf4;padding:4px 10px;border-radius:20px;border:1px solid #86efac;">
            <i class="fas fa-shield-alt"></i> <?php echo number_format($groupCounts['uploaded']); ?> / <?php echo number_format($real['uploaded_total']); ?> protected
        </span>
    </h3>
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:11px 16px;margin-bottom:18px;display:flex;align-items:center;gap:10px;">
        <i class="fas fa-check-circle" style="color:#10b981;font-size:18px;"></i>
        <span style="color:#065f46;font-size:13px;font-weight:600;">All uploaded content (videos, images) is automatically protected on upload &mdash; same PIN applies.</span>
    </div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;">
        <div style="background:#f8fafc;border-radius:10px;padding:16px 18px;border:1px solid #e2e8f0;text-align:center;">
            <i class="fas fa-video" style="color:#ec4899;font-size:22px;margin-bottom:8px;display:block;"></i>
            <div style="font-size:24px;font-weight:800;color:#ec4899;"><?php echo number_format($real['videos']); ?></div>
            <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:3px;">Video Links</div>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:16px 18px;border:1px solid #e2e8f0;text-align:center;">
            <i class="fas fa-images" style="color:#8b5cf6;font-size:22px;margin-bottom:8px;display:block;"></i>
            <div style="font-size:24px;font-weight:800;color:#8b5cf6;"><?php echo number_format($groupCounts['uploaded']); ?></div>
            <div style="font-size:12px;color:#64748b;font-weight:600;margin-top:3px;">Protected Records</div>
        </div>
    </div>
</div>

<!-- Main Grid -->
<div class="acs-grid">

    <!-- PIN Setup -->
    <div class="acs-card acs-pin-card">
        <div class="acs-pin-header">
            <div class="acs-pin-title-row">
                <div class="acs-pin-icon"><i class="fas fa-shield-alt"></i></div>
                <div>
                    <h3 style="margin:0 0 3px;">Security PIN Setup</h3>
                    <p style="margin:0;font-size:13px;color:#64748B;">6-digit PIN to protect AI-generated content</p>
                </div>
            </div>
            <div class="pin-status-badge <?php echo $has_pin ? 'set' : 'notset'; ?>">
                <i class="fas fa-<?php echo $has_pin ? 'check-circle' : 'exclamation-circle'; ?>"></i>
                <?php echo $has_pin ? 'PIN Active' : 'Not Set'; ?>
            </div>
        </div>

        <form method="POST" id="pinSetupForm">
            <input type="hidden" name="action" value="set_pin">
            <input type="hidden" name="new_pin"     id="sec_pin">
            <input type="hidden" name="confirm_pin" id="sec_pin2">

            <?php if ($has_pin): ?>
            <!-- PIN active — show collapsed state by default -->
            <div id="pinActiveInfo" style="padding:18px 24px;">
                <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:14px 18px;margin-bottom:14px;display:flex;align-items:center;gap:12px;">
                    <i class="fas fa-check-circle" style="color:#10b981;font-size:20px;"></i>
                    <div>
                        <div style="font-weight:700;color:#065f46;font-size:14px;">Security PIN is Active</div>
                        <div style="font-size:12px;color:#16a34a;margin-top:2px;">Your AI content is protected. Enter PIN when deleting any AI content.</div>
                    </div>
                </div>
            </div>
            <div id="pinChangeBoxes" style="display:none;">
                <div class="acs-pin-columns">
                    <div class="acs-pin-col">
                        <label class="otp-pin-label">New PIN</label>
                        <div class="otp-boxes" id="otp_sec_pin"></div>
                    </div>
                    <div class="acs-pin-sep"><span>must match</span></div>
                    <div class="acs-pin-col">
                        <label class="otp-pin-label">Confirm PIN</label>
                        <div class="otp-boxes" id="otp_sec_pin2"></div>
                    </div>
                </div>
                <div class="sec-pin-match" id="secPinMatch" style="display:none;"></div>
            </div>
            <div class="acs-pin-actions">
                <button type="button" class="acs-btn-save" id="btnChangePinToggle" onclick="togglePinChange()">
                    <i class="fas fa-edit"></i> Change PIN
                </button>
                <button type="button" class="acs-btn-save" id="btnSavePin" onclick="submitSecPin()" style="display:none;background:linear-gradient(135deg,#10b981,#059669);">
                    <i class="fas fa-shield-alt"></i> Save New PIN
                </button>
                <button type="button" class="acs-btn-forgot" onclick="openForgotSecPin()">
                    <i class="fas fa-lock-open"></i> Forgot PIN?
                </button>
            </div>
            <?php else: ?>
            <!-- No PIN set — show input immediately -->
            <div class="acs-pin-columns">
                <div class="acs-pin-col">
                    <label class="otp-pin-label">Create PIN</label>
                    <div class="otp-boxes" id="otp_sec_pin"></div>
                </div>
                <div class="acs-pin-sep"><span>must match</span></div>
                <div class="acs-pin-col">
                    <label class="otp-pin-label">Confirm PIN</label>
                    <div class="otp-boxes" id="otp_sec_pin2"></div>
                </div>
            </div>
            <div class="sec-pin-match" id="secPinMatch" style="display:none;"></div>
            <div class="acs-pin-actions">
                <button type="button" class="acs-btn-save" onclick="submitSecPin()">
                    <i class="fas fa-shield-alt"></i> Save PIN
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- Protection Settings -->
    <div class="acs-card">
        <h3><i class="fas fa-sliders-h"></i> Protection Settings</h3>
        <form method="POST">
            <input type="hidden" name="action" value="update_settings">
            <div class="toggle-row">
                <div class="toggle-label">Auto-protect AI content on generation<small>New AI content is locked immediately after generation</small></div>
                <label class="toggle"><input type="checkbox" name="auto_protect" <?php echo ($settings['auto_protect_on_generate'] ?? '1') == '1' ? 'checked' : ''; ?>><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
                <div class="toggle-label">Require PIN to delete<small>Deletion requires the security PIN above</small></div>
                <label class="toggle"><input type="checkbox" name="require_pin" <?php echo ($settings['require_pin_to_delete'] ?? '1') == '1' ? 'checked' : ''; ?>><span class="toggle-slider"></span></label>
            </div>
            <div class="toggle-row">
                <div class="toggle-label">Require reason to delete<small>Admin must provide a written reason before deleting</small></div>
                <label class="toggle"><input type="checkbox" name="require_reason" <?php echo ($settings['require_reason_to_delete'] ?? '1') == '1' ? 'checked' : ''; ?>><span class="toggle-slider"></span></label>
            </div>
            <div class="form-group" style="margin-top:14px;">
                <label>Deletion Cooldown Period (hours)</label>
                <input type="number" name="cooldown_hours" value="<?php echo (int)($settings['deletion_cooldown_hours'] ?? 24); ?>" min="0" max="720">
                <small style="color:#94A3B8;font-size:11px;">AI content cannot be deleted for this many hours after generation. Set 0 to disable.</small>
            </div>
            <button type="submit" class="btn-primary"><i class="fas fa-save"></i> Save Settings</button>
        </form>
    </div>
</div>

<!-- How PIN is used -->
<div class="acs-card" style="margin-bottom:24px;border-left:4px solid #6366f1;">
    <h3><i class="fas fa-info-circle" style="color:#6366f1;"></i> How the Security PIN Works</h3>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;margin-bottom:8px;">
        <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;border:1px solid #e2e8f0;">
            <div style="font-size:20px;margin-bottom:6px;">📝</div>
            <strong style="font-size:13px;color:#1e293b;">Step 1 — Request Deletion</strong>
            <p style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.6;">When you click Delete on any AI content (Blogs, Reviews, FAQs, Service Pages), a <strong>PIN entry modal</strong> appears before anything is deleted.</p>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;border:1px solid #e2e8f0;">
            <div style="font-size:20px;margin-bottom:6px;">🔐</div>
            <strong style="font-size:13px;color:#1e293b;">Step 2 — Enter PIN + Reason</strong>
            <p style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.6;">You enter your <strong>6-digit security PIN</strong> (set above) and provide a reason for deletion. Wrong PIN = deletion blocked immediately.</p>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;border:1px solid #e2e8f0;">
            <div style="font-size:20px;margin-bottom:6px;">📧</div>
            <strong style="font-size:13px;color:#1e293b;">Step 3 — Email Approval</strong>
            <p style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.6;">Even with a correct PIN, content is <strong>NOT deleted immediately</strong>. An approval email is sent to <code style="background:#e2e8f0;padding:1px 5px;border-radius:4px;font-size:11px;">gopichandmailapally@gmail.com</code>.</p>
        </div>
        <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;border:1px solid #e2e8f0;">
            <div style="font-size:20px;margin-bottom:6px;">✅</div>
            <strong style="font-size:13px;color:#1e293b;">Step 4 — Click Approve Link</strong>
            <p style="font-size:12px;color:#64748b;margin-top:4px;line-height:1.6;">Only after you click the <strong>Approve link in the email</strong> does the content get permanently deleted. You can also cancel from the email.</p>
        </div>
    </div>
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:11px 16px;font-size:12px;color:#1e40af;">
        <i class="fas fa-shield-alt" style="margin-right:6px;"></i>
        <strong>Where the PIN is checked:</strong> Manage Blogs → Delete, Manage Reviews → Delete, Manage FAQs → Delete, View Generated Pages → Delete. The PIN blocks deletion at every entry point.
    </div>
</div>

<!-- Category Breakdown -->
<?php if (!empty($acs_cat_stats)): ?>
<div class="acs-card" style="margin-bottom:24px;">
    <h3><i class="fas fa-chart-pie"></i> Content by Category</h3>
    <?php
    $acs_cat_icons = [
        'PIGEON NETS'      => ['icon'=>'fas fa-dove',        'color'=>'#667eea'],
        'BIRD NETS'        => ['icon'=>'fas fa-crow',        'color'=>'#10B981'],
        'SAFETY NETS'      => ['icon'=>'fas fa-shield-alt',  'color'=>'#F59E0B'],
        'SPORTS NETS'      => ['icon'=>'fas fa-futbol',      'color'=>'#EF4444'],
        'INVISIBLE GRILLS' => ['icon'=>'fas fa-grip-lines',  'color'=>'#8B5CF6'],
        'CLOTH HANGERS'    => ['icon'=>'fas fa-tshirt',      'color'=>'#06B6D4'],
    ];
    ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:12px;">
    <?php foreach ($acs_cat_icons as $cat => $meta):
        $d = $acs_cat_stats[$cat] ?? ['total_pages'=>0,'kw_count'=>0,'possible'=>0];
        $pct = $d['possible'] > 0 ? min(100, round($d['total_pages'] / $d['possible'] * 100)) : 0;
    ?>
    <div style="background:#f8fafc;border-radius:10px;padding:14px 16px;border:1px solid #e2e8f0;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
            <i class="<?php echo $meta['icon']; ?>" style="color:<?php echo $meta['color']; ?>;font-size:20px;"></i>
            <strong style="font-size:12px;color:#1e293b;"><?php echo $cat; ?></strong>
            <span style="margin-left:auto;font-size:13px;font-weight:800;color:<?php echo $meta['color']; ?>"><?php echo number_format($d['total_pages']); ?></span>
        </div>
        <div style="background:#e2e8f0;border-radius:4px;height:5px;">
            <div style="background:<?php echo $meta['color']; ?>;width:<?php echo $pct; ?>%;height:5px;border-radius:4px;"></div>
        </div>
        <div style="font-size:11px;color:#94a3b8;margin-top:5px;"><?php echo $pct; ?>% complete &middot; <?php echo $d['kw_count']; ?> keywords &times; <?php echo $acs_total_areas; ?> areas</div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Protected Content Table -->
<div class="acs-card" style="margin-bottom:24px;">
    <h3><i class="fas fa-shield-alt"></i> Protected AI Content
        <span style="margin-left:auto;font-size:12px;font-weight:400;color:#94A3B8;"><?php echo count($protected_list); ?> items</span>
    </h3>
    <!-- Search filter -->
    <div style="margin-bottom:12px;">
        <input type="text" id="acsSearch" placeholder="&#128269; Search by title or type..." oninput="filterAcsTable(this.value)"
               style="width:100%;padding:9px 14px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;color:#1e293b;box-sizing:border-box;">
    </div>
    <?php if (empty($protected_list)): ?>
        <div style="text-align:center;padding:40px 20px;color:#94A3B8;">
            <i class="fas fa-robot" style="font-size:36px;margin-bottom:12px;display:block;opacity:.4;"></i>
            No AI-generated content is currently being tracked.<br>
            <small>Content gets auto-tracked when generated via the admin panel.</small>
        </div>
    <?php else: ?>
    <div class="table-scroll">
        <table class="protected-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Type</th>
                    <th>Title / ID</th>
                    <th>Generated</th>
                    <th>Protected At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody id="acsTableBody">
            <?php foreach ($protected_list as $i => $item):
                $typeClass = match($item['content_type']) {
                    'page','service_page' => 'badge-page',
                    'blog'               => 'badge-blog',
                    'review'             => 'badge-review',
                    'faq'                => 'badge-faq',
                    default              => 'badge-other',
                };
                // For service pages the content_id is the slug
                $view_url = '';
                if (in_array($item['content_type'], ['service_page', 'page'])) {
                    $view_url = SITE_URL . '/generated-pages/' . htmlspecialchars($item['content_id']) . '.php';
                }
            ?>
                <tr data-search="<?php echo strtolower(htmlspecialchars($item['content_title'] . ' ' . $item['content_type'])); ?>">
                    <td style="color:#94A3B8;font-size:12px;"><?php echo $i+1; ?></td>
                    <td><span class="badge-type <?php echo $typeClass; ?>"><?php echo htmlspecialchars($item['content_type']); ?></span></td>
                    <td>
                        <i class="fas fa-lock lock-icon"></i>
                        <?php echo htmlspecialchars($item['content_title'] ?: $item['content_id']); ?>
                        <?php if ($view_url): ?>
                        <a href="<?php echo $view_url; ?>" target="_blank" style="margin-left:8px;color:#6366f1;font-size:11px;" title="View live page">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                    <td style="color:#64748B;font-size:12px;"><?php echo htmlspecialchars($item['generated_by']); ?></td>
                    <td style="color:#64748B;font-size:12px;"><?php echo date('d M Y H:i', strtotime($item['protected_at'])); ?></td>
                    <td>
                        <button class="btn-danger"
                            onclick="openUnlockModal('<?php echo htmlspecialchars($item['content_type']); ?>','<?php echo htmlspecialchars($item['content_id']); ?>')">
                            <i class="fas fa-unlock-alt"></i> Unlock
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Audit Log -->
<div class="acs-card">
    <h3><i class="fas fa-history"></i> Security Audit Log
        <span style="margin-left:auto;font-size:12px;font-weight:400;color:#94A3B8;">Last 60 events</span>
    </h3>
    <?php if (empty($audit_log)): ?>
        <p style="color:#94A3B8;text-align:center;padding:30px 0;font-size:13px;">No audit events yet.</p>
    <?php else: ?>
    <div style="max-height:380px;overflow-y:auto;">
        <?php foreach ($audit_log as $log):
            $dot = match(true) {
                str_contains($log['action'], 'approved') || str_contains($log['action'], 'unlock')     => 'unlocked',
                str_contains($log['action'], 'blocked')  || str_contains($log['action'], 'failed')     => 'blocked',
                str_contains($log['action'], 'lock')                                                    => 'locked',
                str_contains($log['action'], 'delete')                                                  => 'approved',
                default                                                                                  => 'other',
            };
            $actionLabel = match($log['action']) {
                'delete_approved'  => '✅ Deletion Approved',
                'delete_blocked'   => '🚫 Deletion Blocked',
                'manual_lock'      => '🔒 Manually Locked',
                'manual_unlock'    => '🔓 Manually Unlocked',
                'unlock_failed'    => '❌ Unlock Failed',
                'pin_changed'      => '🔑 PIN Changed',
                'setting_changed'  => '⚙️ Setting Changed',
                default            => '📋 ' . ucfirst(str_replace('_', ' ', $log['action'])),
            };
        ?>
        <div class="audit-item">
            <div class="audit-dot <?php echo $dot; ?>"></div>
            <div class="audit-content">
                <div class="title"><?php echo $actionLabel; ?>
                    <?php if ($log['content_type']): ?>
                        <span class="badge-type badge-<?php echo $log['content_type'] ?? 'other'; ?>" style="margin-left:6px;"><?php echo htmlspecialchars($log['content_type']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="meta">
                    <?php echo htmlspecialchars($log['content_title'] ?: $log['content_id']); ?>
                    &nbsp;·&nbsp; IP: <?php echo htmlspecialchars($log['ip_address']); ?>
                    &nbsp;·&nbsp; <?php echo date('d M Y H:i', strtotime($log['created_at'])); ?>
                </div>
                <?php if ($log['reason'] && $log['reason'] !== 'null'): ?>
                <div class="reason">"<?php echo htmlspecialchars($log['reason']); ?>"</div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

</div><!-- .acs-page -->

<!-- Unlock Modal -->
<div class="modal-overlay" id="unlockModal">
    <div class="modal-box acs-modal-box">
        <div class="acs-modal-header">
            <div class="acs-modal-title-row">
                <div class="acs-modal-icon"><i class="fas fa-unlock-alt"></i></div>
                <div>
                    <h3>Unlock AI Content</h3>
                    <p>Enter your 6-digit security PIN</p>
                </div>
            </div>
            <button class="acs-modal-close" onclick="closeUnlockModal()">✕</button>
        </div>
        <form method="POST" id="unlockForm">
            <input type="hidden" name="action"       value="manual_unlock">
            <input type="hidden" name="content_type" id="unlock_type">
            <input type="hidden" name="content_id"   id="unlock_id">
            <input type="hidden" name="pin"          id="unlock_pin">
            <div class="acs-modal-body">
                <div class="otp-big-wrap" id="otp_unlock"></div>
                <div class="acs-pin-error" id="unlockError" style="display:none;"></div>
            </div>
            <div class="acs-modal-footer">
                <button type="button" class="acs-btn-cancel" onclick="closeUnlockModal()"><i class="fas fa-times"></i> Cancel</button>
                <button type="button" class="acs-btn-unlock" onclick="submitUnlock()"><i class="fas fa-unlock-alt"></i> Unlock Content</button>
            </div>
        </form>
    </div>
</div>

<!-- Forgot Security PIN Modal -->
<div class="modal-overlay" id="forgotSecPinModal">
    <div class="modal-box acs-modal-box">
        <div class="acs-modal-header" style="background:linear-gradient(135deg,#7C3AED,#5B21B6);">
            <div class="acs-modal-title-row">
                <div class="acs-modal-icon"><i class="fas fa-lock-open"></i></div>
                <div>
                    <h3>Reset Security PIN</h3>
                    <p>Verify with admin password first</p>
                </div>
            </div>
            <button class="acs-modal-close" onclick="closeModal('forgotSecPinModal')">✕</button>
        </div>
        <form method="POST" id="forgotSecPinForm">
            <input type="hidden" name="action"      value="forgot_pin">
            <input type="hidden" name="new_pin"     id="forgot_sec_pin">
            <input type="hidden" name="confirm_pin" id="forgot_sec_pin2">
            <div class="acs-modal-body">
                <div class="form-group" style="margin-bottom:18px;">
                    <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Admin Password</label>
                    <div class="acs-pass-wrap">
                        <input type="password" name="admin_password" id="forgot_admin_pw" class="acs-input" placeholder="Enter your admin password">
                        <button type="button" class="acs-eye-btn" onclick="toggleAcsPass('forgot_admin_pw',this)"><i class="fas fa-eye"></i></button>
                    </div>
                </div>
                <div class="acs-pin-two-cols">
                    <div>
                        <label class="otp-pin-label">New 6-Digit PIN</label>
                        <div class="otp-boxes" id="otp_forgot_pin"></div>
                        <input type="hidden" id="forgot_sec_pin">
                    </div>
                    <div>
                        <label class="otp-pin-label">Confirm New PIN</label>
                        <div class="otp-boxes" id="otp_forgot_pin2"></div>
                        <input type="hidden" id="forgot_sec_pin2">
                    </div>
                </div>
                <div class="acs-pin-error" id="forgotSecError" style="display:none;"></div>
            </div>
            <div class="acs-modal-footer">
                <button type="button" class="acs-btn-cancel" onclick="closeModal('forgotSecPinModal')"><i class="fas fa-times"></i> Cancel</button>
                <button type="submit" class="acs-btn-unlock" style="background:linear-gradient(135deg,#7C3AED,#5B21B6);"><i class="fas fa-sync"></i> Reset PIN</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ====== ACS PIN SETUP CARD ====== */
.acs-pin-card { padding:0 !important; overflow:hidden; }
.acs-pin-header { background:linear-gradient(135deg,#1E293B,#334155); padding:20px 24px; display:flex; justify-content:space-between; align-items:center; }
.acs-pin-title-row { display:flex; align-items:center; gap:14px; }
.acs-pin-icon { width:44px; height:44px; background:rgba(255,255,255,0.15); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
.acs-pin-title-row h3 { color:#fff; }
.acs-pin-title-row p  { color:rgba(255,255,255,0.65); }
.pin-status-badge { padding:5px 14px; border-radius:20px; font-size:12px; font-weight:700; display:flex; align-items:center; gap:6px; }
.pin-status-badge.set    { background:rgba(16,185,129,0.2); color:#10B981; border:1px solid rgba(16,185,129,0.4); }
.pin-status-badge.notset { background:rgba(239,68,68,0.2);  color:#EF4444; border:1px solid rgba(239,68,68,0.4); }
.acs-pin-columns { display:flex; flex-direction:column; gap:18px; padding:24px 24px 0; }
.acs-pin-col { width:100%; }
.acs-pin-sep { display:none; }
.otp-pin-label { font-size:11px; font-weight:700; color:#64748B; text-transform:uppercase; letter-spacing:0.6px; display:block; margin-bottom:8px; }
.sec-pin-match { margin:12px 24px 0; font-size:13px; font-weight:600; padding:8px 12px; border-radius:8px; }
.sec-pin-match.ok  { background:#ECFDF5; color:#059669; }
.sec-pin-match.bad { background:#FEF2F2; color:#DC2626; }
.acs-pin-actions { display:flex; gap:10px; align-items:center; padding:20px 24px 24px; flex-wrap:wrap; }
.acs-btn-save   { background:linear-gradient(135deg,#1E293B,#334155); color:#fff; border:none; padding:11px 22px; border-radius:10px; cursor:pointer; font-weight:700; font-size:14px; display:flex; align-items:center; gap:8px; box-shadow:0 4px 12px rgba(30,41,59,0.3); transition:all 0.2s; }
.acs-btn-save:hover { transform:translateY(-1px); box-shadow:0 6px 18px rgba(30,41,59,0.4); }
.acs-btn-forgot { background:transparent; border:2px solid #CBD5E1; color:#64748B; padding:9px 18px; border-radius:10px; cursor:pointer; font-weight:600; font-size:13px; display:flex; align-items:center; gap:7px; transition:all 0.2s; }
.acs-btn-forgot:hover { border-color:#7C3AED; color:#7C3AED; background:#F5F3FF; }

/* ====== OTP BOXES (shared) ====== */
.otp-boxes { display:flex; gap:7px; }
.otp-box { width:42px; height:52px; border:2px solid #E2E8F0; border-radius:10px; font-size:20px; font-weight:700; text-align:center; color:#1E293B; background:#F8FAFC; transition:all 0.2s; outline:none; caret-color:transparent; }
.otp-box:focus   { border-color:#4F46E5; background:#fff; box-shadow:0 0 0 3px rgba(79,70,229,0.15); transform:scale(1.06); }
.otp-box.filled  { border-color:#7C3AED; background:#F5F3FF; color:#4F46E5; }
.otp-box.match   { border-color:#10B981; background:#ECFDF5; color:#059669; }
.otp-box.mismatch{ border-color:#EF4444; background:#FEF2F2; color:#DC2626; }

/* ====== ACS MODALS ====== */
.modal-overlay { display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:20px; }
.modal-overlay.active { display:flex; }
.acs-modal-box { background:#fff; border-radius:20px; width:100%; max-width:440px; box-shadow:0 24px 80px rgba(0,0,0,0.28); overflow:hidden; animation:acsModalIn 0.28s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes acsModalIn { from { opacity:0; transform:scale(0.85) translateY(20px); } to { opacity:1; transform:scale(1) translateY(0); } }
.acs-modal-header { background:linear-gradient(135deg,#1E293B,#334155); padding:20px 22px; display:flex; justify-content:space-between; align-items:center; }
.acs-modal-title-row { display:flex; align-items:center; gap:13px; }
.acs-modal-icon { width:42px; height:42px; background:rgba(255,255,255,0.15); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; }
.acs-modal-title-row h3 { margin:0 0 2px; font-size:17px; color:#fff; font-weight:700; }
.acs-modal-title-row p  { margin:0; font-size:12px; color:rgba(255,255,255,0.65); }
.acs-modal-close { background:rgba(255,255,255,0.15); border:none; width:30px; height:30px; border-radius:50%; cursor:pointer; color:#fff; font-size:15px; display:flex; align-items:center; justify-content:center; transition:background 0.2s; }
.acs-modal-close:hover { background:rgba(255,255,255,0.3); }
.acs-modal-body { padding:26px 26px 16px; text-align:center; }
.acs-modal-footer { display:flex; gap:10px; justify-content:flex-end; padding:14px 22px 20px; }
.otp-big-wrap { display:flex; gap:10px; justify-content:center; margin-bottom:4px; }
.otp-big-box { width:50px; height:62px; border:2px solid #E2E8F0; border-radius:12px; font-size:26px; font-weight:700; text-align:center; color:#1E293B; background:#F8FAFC; transition:all 0.2s; outline:none; caret-color:transparent; }
.otp-big-box:focus  { border-color:#4F46E5; background:#fff; box-shadow:0 0 0 4px rgba(79,70,229,0.15); transform:scale(1.06); }
.otp-big-box.filled { border-color:#7C3AED; background:#EDE9FE; }
.acs-pin-error { margin-top:12px; background:#FEF2F2; border:1px solid #FECACA; color:#DC2626; padding:8px 12px; border-radius:8px; font-size:13px; font-weight:600; }
.acs-btn-cancel { background:#F1F5F9; color:#64748B; border:2px solid #E2E8F0; padding:9px 18px; border-radius:9px; cursor:pointer; font-weight:600; font-size:13px; display:flex; align-items:center; gap:6px; transition:all 0.2s; }
.acs-btn-cancel:hover { background:#E2E8F0; }
.acs-btn-unlock { background:linear-gradient(135deg,#1E293B,#334155); color:#fff; border:none; padding:10px 20px; border-radius:9px; cursor:pointer; font-weight:700; font-size:13px; display:flex; align-items:center; gap:7px; box-shadow:0 4px 12px rgba(30,41,59,0.3); transition:all 0.2s; }
.acs-btn-unlock:hover { transform:translateY(-1px); }
.acs-pin-two-cols { display:grid; grid-template-columns:1fr 1fr; gap:14px; text-align:left; margin-bottom:12px; }
.acs-pass-wrap { display:flex; border:2px solid #E2E8F0; border-radius:8px; overflow:hidden; }
.acs-pass-wrap:focus-within { border-color:#4F46E5; }
.acs-input { flex:1; border:none; padding:10px 12px; font-size:14px; outline:none; }
.acs-eye-btn { background:#F1F5F9; border:none; border-left:2px solid #E2E8F0; padding:0 12px; cursor:pointer; color:#64748B; }
.acs-eye-btn:hover { background:#E2E8F0; }
</style>

<script>
/* Table search filter */
function filterAcsTable(q) {
    q = q.toLowerCase().trim();
    document.querySelectorAll('#acsTableBody tr').forEach(function(row) {
        var text = (row.getAttribute('data-search') || '').toLowerCase();
        row.style.display = !q || text.includes(q) ? '' : 'none';
    });
}

/* ============================
   OTP BOX BUILDER (shared)
============================= */
function buildACSBoxes(containerId, hiddenId, size, onComplete) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    const hidden = document.getElementById(hiddenId);
    container.innerHTML = '';
    const cls = size === 'big' ? 'otp-big-box' : 'otp-box';
    const boxes = [];
    for (let i = 0; i < 6; i++) {
        const inp = document.createElement('input');
        inp.type = 'tel'; inp.maxLength = 1; inp.className = cls;
        inp.setAttribute('inputmode','numeric'); inp.pattern = '[0-9]';
        inp.addEventListener('input', () => {
            inp.value = inp.value.replace(/\D/,'').slice(-1);
            inp.classList.toggle('filled', !!inp.value);
            if (!inp.value) inp.classList.remove('match','mismatch');
            if (inp.value && i < 5) boxes[i+1].focus();
            syncH(); if (onComplete) onComplete();
        });
        inp.addEventListener('keydown', e => {
            if (e.key==='Backspace' && !inp.value && i>0) {
                boxes[i-1].value=''; boxes[i-1].classList.remove('filled','match','mismatch');
                boxes[i-1].focus(); syncH(); if(onComplete)onComplete();
            }
        });
        inp.addEventListener('paste', e => {
            e.preventDefault();
            const p=(e.clipboardData||window.clipboardData).getData('text').replace(/\D/g,'').slice(0,6);
            p.split('').forEach((ch,j)=>{ if(boxes[j]){ boxes[j].value=ch; boxes[j].classList.add('filled'); } });
            syncH(); if(onComplete)onComplete();
            if(boxes[Math.min(p.length,5)]) boxes[Math.min(p.length,5)].focus();
        });
        container.appendChild(inp); boxes.push(inp);
    }
    function syncH() { if(hidden) hidden.value = boxes.map(b=>b.value).join(''); }
    function clearAll() { boxes.forEach(b=>{ b.value=''; b.className=cls; }); if(hidden) hidden.value=''; }
    function setStates(s) { boxes.forEach(b=>{ if(b.value){ b.classList.remove('filled','match','mismatch'); b.classList.add(s); } }); }
    return { boxes, clearAll, setStates, syncH };
}

/* ============================
   PIN SETUP (inline card)
============================= */
let _secOtp1, _secOtp2;
let _pinChangeOpen = false;

function togglePinChange() {
    _pinChangeOpen = !_pinChangeOpen;
    const boxes  = document.getElementById('pinChangeBoxes');
    const toggle = document.getElementById('btnChangePinToggle');
    const save   = document.getElementById('btnSavePin');
    if (_pinChangeOpen) {
        boxes.style.display  = 'block';
        toggle.style.display = 'none';
        save.style.display   = 'flex';
        if (!_secOtp1) {
            _secOtp1 = buildACSBoxes('otp_sec_pin',  'sec_pin',  'normal', checkSecMatch);
            _secOtp2 = buildACSBoxes('otp_sec_pin2', 'sec_pin2', 'normal', checkSecMatch);
        }
        setTimeout(() => _secOtp1 && _secOtp1.boxes[0].focus(), 100);
    } else {
        boxes.style.display  = 'none';
        toggle.style.display = 'flex';
        save.style.display   = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    /* If PIN not set, init boxes immediately */
    if (document.getElementById('otp_sec_pin') &&
        document.getElementById('otp_sec_pin').parentElement.closest('#pinChangeBoxes') === null) {
        _secOtp1 = buildACSBoxes('otp_sec_pin',  'sec_pin',  'normal', checkSecMatch);
        _secOtp2 = buildACSBoxes('otp_sec_pin2', 'sec_pin2', 'normal', checkSecMatch);
    }
});
function checkSecMatch() {
    const p1 = document.getElementById('sec_pin').value;
    const p2 = document.getElementById('sec_pin2').value;
    const msg = document.getElementById('secPinMatch');
    if (p1.length===6 && p2.length===6) {
        if (p1===p2) { msg.className='sec-pin-match ok'; msg.textContent='✅ PINs match — ready to save!'; msg.style.display='block'; _secOtp1.setStates('match'); _secOtp2.setStates('match'); }
        else         { msg.className='sec-pin-match bad'; msg.textContent='❌ PINs do not match'; msg.style.display='block'; _secOtp2.setStates('mismatch'); }
    } else { msg.style.display='none'; }
}
function submitSecPin() {
    const p1 = document.getElementById('sec_pin').value;
    const p2 = document.getElementById('sec_pin2').value;
    if (!/^\d{6}$/.test(p1)) { alert('Enter all 6 digits of your PIN.'); return; }
    if (p1 !== p2)            { alert('PINs do not match. Please re-enter.'); return; }
    document.getElementById('pinSetupForm').submit();
}

/* ============================
   UNLOCK MODAL
============================= */
let _unlockOtp;
function openUnlockModal(type, id) {
    document.getElementById('unlock_type').value = type;
    document.getElementById('unlock_id').value   = id;
    document.getElementById('unlockError').style.display = 'none';
    _unlockOtp = buildACSBoxes('otp_unlock', 'unlock_pin', 'big', null);
    document.getElementById('unlockModal').classList.add('active');
    setTimeout(()=>_unlockOtp && _unlockOtp.boxes[0].focus(), 250);
}
function closeUnlockModal() { document.getElementById('unlockModal').classList.remove('active'); }
function submitUnlock() {
    const pin = document.getElementById('unlock_pin').value;
    if (!/^\d{6}$/.test(pin)) {
        const e = document.getElementById('unlockError');
        e.textContent = 'Enter all 6 digits of your security PIN.';
        e.style.display = 'block'; return;
    }
    document.getElementById('unlockForm').submit();
}
document.getElementById('unlockModal').addEventListener('click', function(e) {
    if (e.target === this) closeUnlockModal();
});

/* ============================
   FORGOT SECURITY PIN MODAL
============================= */
let _forgotSecOtp1, _forgotSecOtp2;
function openForgotSecPin() {
    _forgotSecOtp1 = buildACSBoxes('otp_forgot_pin',  'forgot_sec_pin',  'normal', null);
    _forgotSecOtp2 = buildACSBoxes('otp_forgot_pin2', 'forgot_sec_pin2', 'normal', null);
    document.getElementById('forgot_admin_pw').value = '';
    document.getElementById('forgotSecError').style.display = 'none';
    document.getElementById('forgotSecPinModal').classList.add('active');
}
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function toggleAcsPass(id, btn) {
    const f=document.getElementById(id), i=btn.querySelector('i');
    if(f.type==='password'){f.type='text'; i.className='fas fa-eye-slash';}
    else{f.type='password'; i.className='fas fa-eye';}
}
document.getElementById('forgotSecPinModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal('forgotSecPinModal');
});
</script>

<?php include '../includes/footer.php'; ?>
