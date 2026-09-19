<?php
/**
 * Logo Management
 * Upload and manage website logos
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Logo Management';
include '../includes/header.php';
?>

<style>
/* ── Page wrapper ───────────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero header card ───────────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; }

/* ── Solution banner ────────────────────────────────── */
.solution-banner { background: linear-gradient(135deg, rgba(102,126,234,.08) 0%, rgba(118,75,162,.05) 100%); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #3730a3; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #4338ca; font-size: 13.5px; margin: 0; line-height: 1.6; }

/* ── Stat cards ─────────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; text-align: left !important; border-left: none !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; }
.stat-card .sub { font-size: 11px !important; color: #94a3b8 !important; margin: 3px 0 0 !important; }

/* ── Section card ───────────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sec-num.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

/* ── Action buttons ──────────────────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; margin-top: 20px; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 13px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 10px 30px rgba(102,126,234,.45); }
.btn-action.red    { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 6px 20px rgba(239,68,68,.35); }
.btn-action.red:hover    { box-shadow: 0 10px 30px rgba(239,68,68,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; box-shadow: none; transform: none; }

/* ── Logo upload area ────────────────────────────────── */
.logo-upload-area { display: flex; gap: 28px; align-items: flex-start; }
.logo-preview-box { flex: 0 0 280px; border-radius: 14px; overflow: hidden; border: 2px solid #e2e8f0; }
.logo-preview-box.dark-bg { background: linear-gradient(135deg,#1e293b,#334155); }
.logo-preview-box.light-bg { background: #f8fafc; }
.logo-preview-inner { height: 160px; display: flex; align-items: center; justify-content: center; padding: 24px; }
.logo-preview-inner img { max-width: 100%; max-height: 100%; object-fit: contain; }
.no-logo-placeholder { text-align: center; color: #94a3b8; }
.no-logo-placeholder i { font-size: 40px; display: block; margin-bottom: 10px; }
.no-logo-placeholder p { margin: 0; font-size: 13px; }
.logo-info-list { padding: 14px 16px; border-top: 1px solid #e2e8f0; background: #f8fafc; }
.logo-info-row { display: flex; justify-content: space-between; font-size: 12.5px; padding: 5px 0; border-bottom: 1px solid #f1f5f9; }
.logo-info-row:last-child { border-bottom: none; }
.logo-info-row .lbl { color: #64748b; font-weight: 600; }
.logo-info-row .val { color: #1e293b; font-family: monospace; font-size: 12px; }
.logo-upload-controls { flex: 1; }
.logo-upload-controls h4 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
.logo-upload-controls p  { font-size: 13.5px; color: #64748b; margin: 0 0 16px; line-height: 1.6; }
.upload-specs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 18px; }
.spec-badge { display: inline-flex; align-items: center; gap: 5px; padding: 5px 12px; background: #f0f4ff; border: 1px solid #c7d2fe; border-radius: 20px; font-size: 12px; font-weight: 600; color: #4338ca; }
.logo-status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-bottom: 16px; }
.logo-status-badge.active   { background: #d1fae5; color: #065f46; }
.logo-status-badge.inactive { background: #fee2e2; color: #991b1b; }
.file-input-hidden { display: none; }

/* ── Tip card ─────────────────────────────────────────── */
.tip-bar { background: linear-gradient(135deg,rgba(245,158,11,.08),rgba(251,191,36,.05)); border: 1px solid rgba(245,158,11,.25); border-left: 5px solid #f59e0b; border-radius: 12px; padding: 16px 20px; display: flex; align-items: flex-start; gap: 12px; }
.tip-bar .tip-icon { width: 36px; height: 36px; background: linear-gradient(135deg,#f59e0b,#d97706); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; flex-shrink: 0; margin-top: 2px; }
.tip-bar strong { color: #92400e; font-size: 13.5px; font-weight: 700; display: block; margin-bottom: 3px; }
.tip-bar p { margin: 0; color: #78350f; font-size: 13px; line-height: 1.6; }

@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } .logo-upload-area { flex-direction: column; } .logo-preview-box { flex: none; width: 100%; } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } .seo-hero { flex-direction: column; } .seo-section-body { padding: 20px 18px; } }
</style>

<?php

// Define logo upload directory
$upload_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/';

// Check current logos and favicon
$logo_exists = file_exists($upload_dir . 'logo.png');
$footer_logo_exists = file_exists($upload_dir . 'logo-footer.png');
$favicon_exists = file_exists($upload_dir . 'favicon.png');
?>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-image"></i> Logo Management</h1>
        <p>Upload &amp; manage website logos and favicon &mdash; PNG format with transparent background</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-paint-brush" style="margin-right:6px;"></i>Brand Identity</span>
</div>

<!-- Solution Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-info-circle"></i></div>
    <div>
        <strong>Upload Guidelines</strong>
        <p>Use <strong>PNG format</strong> with transparent background &nbsp;&middot;&nbsp; Header/Footer logos: 200&ndash;400px wide, 60&ndash;80px tall &nbsp;&middot;&nbsp; Favicon: 512&times;512px square &nbsp;&middot;&nbsp; Footer: use white/light version &nbsp;&middot;&nbsp; Max file size: <strong>2MB</strong></p>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-desktop"></i></div>
        <div class="stat-text-wrap">
            <h3>Header Logo</h3>
            <div class="value"><?php echo $logo_exists ? '✓' : '✗'; ?></div>
            <p class="sub"><?php echo $logo_exists ? 'Uploaded &amp; Active' : 'Not uploaded yet'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-compass"></i></div>
        <div class="stat-text-wrap">
            <h3>Footer Logo</h3>
            <div class="value"><?php echo $footer_logo_exists ? '✓' : '✗'; ?></div>
            <p class="sub"><?php echo $footer_logo_exists ? 'Uploaded &amp; Active' : 'Not uploaded yet'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-star"></i></div>
        <div class="stat-text-wrap">
            <h3>Favicon</h3>
            <div class="value"><?php echo $favicon_exists ? '✓' : '✗'; ?></div>
            <p class="sub"><?php echo $favicon_exists ? 'Uploaded &amp; Active' : 'Not uploaded yet'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Active</h3>
            <div class="value"><?php echo (int)$logo_exists + (int)$footer_logo_exists + (int)$favicon_exists; ?></div>
            <p class="sub">of 3 brand assets</p>
        </div>
    </div>
</div>

<!-- Section 1: Header Logo -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-desktop" style="color:#667eea;margin-right:8px;"></i>Header Logo
            <span class="logo-status-badge <?php echo $logo_exists ? 'active' : 'inactive'; ?>" style="margin-left:12px;font-size:12px;">
                <i class="fas fa-<?php echo $logo_exists ? 'check' : 'times'; ?>"></i>
                <?php echo $logo_exists ? 'Active' : 'Not Set'; ?>
            </span>
        </h2>
    </div>
    <div class="seo-section-body">
        <div class="logo-upload-area">
            <div class="logo-preview-box light-bg">
                <div class="logo-preview-inner">
                    <?php if ($logo_exists): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/logo.png?v=<?php echo time(); ?>" alt="Header Logo">
                    <?php else: ?>
                        <div class="no-logo-placeholder"><i class="fas fa-image"></i><p>No logo uploaded</p></div>
                    <?php endif; ?>
                </div>
                <div class="logo-info-list">
                    <div class="logo-info-row"><span class="lbl">Used in</span><span class="val">Header (desktop &amp; mobile)</span></div>
                    <div class="logo-info-row"><span class="lbl">File path</span><span class="val">/uploads/logo.png</span></div>
                    <?php if ($logo_exists): ?><div class="logo-info-row"><span class="lbl">Dimensions</span><span class="val" id="logoDimensions">Loading…</span></div><?php endif; ?>
                </div>
            </div>
            <div class="logo-upload-controls">
                <h4><i class="fas fa-desktop" style="color:#667eea;margin-right:6px;"></i>Header Logo</h4>
                <p>Displayed at the top of every page. Use a dark/coloured logo on a light background. Recommended size: 300&times;70 px.</p>
                <div class="upload-specs">
                    <span class="spec-badge"><i class="fas fa-file-image"></i> PNG only</span>
                    <span class="spec-badge"><i class="fas fa-ruler"></i> 200&ndash;400px wide</span>
                    <span class="spec-badge"><i class="fas fa-arrows-alt-v"></i> 60&ndash;80px tall</span>
                    <span class="spec-badge"><i class="fas fa-weight"></i> Max 2MB</span>
                </div>
                <div class="action-row">
                    <form id="headerLogoForm" enctype="multipart/form-data">
                        <input type="file" id="headerLogoFile" name="logo" accept="image/png" class="file-input-hidden" onchange="previewLogo('header')">
                        <label for="headerLogoFile" class="btn-action purple">
                            <i class="fas fa-upload"></i> <?php echo $logo_exists ? 'Replace Logo' : 'Upload Logo'; ?>
                        </label>
                    </form>
                    <?php if ($logo_exists): ?>
                    <button type="button" class="btn-action red" onclick="deleteLogo('header')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 2: Footer Logo -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num teal">2</div>
        <h2><i class="fas fa-compass" style="color:#06b6d4;margin-right:8px;"></i>Footer Logo
            <span class="logo-status-badge <?php echo $footer_logo_exists ? 'active' : 'inactive'; ?>" style="margin-left:12px;font-size:12px;">
                <i class="fas fa-<?php echo $footer_logo_exists ? 'check' : 'times'; ?>"></i>
                <?php echo $footer_logo_exists ? 'Active' : 'Not Set'; ?>
            </span>
        </h2>
    </div>
    <div class="seo-section-body">
        <div class="logo-upload-area">
            <div class="logo-preview-box dark-bg">
                <div class="logo-preview-inner">
                    <?php if ($footer_logo_exists): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/logo-footer.png?v=<?php echo time(); ?>" alt="Footer Logo">
                    <?php else: ?>
                        <div class="no-logo-placeholder" style="color:#94a3b8;"><i class="fas fa-image"></i><p>No logo uploaded</p></div>
                    <?php endif; ?>
                </div>
                <div class="logo-info-list" style="background:#1e293b;border-top-color:#334155;">
                    <div class="logo-info-row" style="border-bottom-color:#334155;"><span class="lbl" style="color:#94a3b8;">Used in</span><span class="val" style="color:#e2e8f0;">Website footer</span></div>
                    <div class="logo-info-row"><span class="lbl" style="color:#94a3b8;">File path</span><span class="val" style="color:#e2e8f0;">/uploads/logo-footer.png</span></div>
                    <?php if ($footer_logo_exists): ?><div class="logo-info-row"><span class="lbl" style="color:#94a3b8;">Dimensions</span><span class="val" style="color:#e2e8f0;" id="footerLogoDimensions">Loading…</span></div><?php endif; ?>
                </div>
            </div>
            <div class="logo-upload-controls">
                <h4><i class="fas fa-compass" style="color:#06b6d4;margin-right:6px;"></i>Footer Logo</h4>
                <p>Displayed in the website footer. Use a <strong>white or light-coloured</strong> version of your logo since the footer has a dark background.</p>
                <div class="upload-specs">
                    <span class="spec-badge"><i class="fas fa-file-image"></i> PNG only</span>
                    <span class="spec-badge"><i class="fas fa-moon"></i> White/light version</span>
                    <span class="spec-badge"><i class="fas fa-ruler"></i> 200&ndash;400px wide</span>
                    <span class="spec-badge"><i class="fas fa-weight"></i> Max 2MB</span>
                </div>
                <div class="action-row">
                    <form id="footerLogoForm" enctype="multipart/form-data">
                        <input type="file" id="footerLogoFile" name="logo" accept="image/png" class="file-input-hidden" onchange="previewLogo('footer')">
                        <label for="footerLogoFile" class="btn-action purple">
                            <i class="fas fa-upload"></i> <?php echo $footer_logo_exists ? 'Replace Logo' : 'Upload Logo'; ?>
                        </label>
                    </form>
                    <?php if ($footer_logo_exists): ?>
                    <button type="button" class="btn-action red" onclick="deleteLogo('footer')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Favicon -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">3</div>
        <h2><i class="fas fa-star" style="color:#f59e0b;margin-right:8px;"></i>Favicon
            <span class="logo-status-badge <?php echo $favicon_exists ? 'active' : 'inactive'; ?>" style="margin-left:12px;font-size:12px;">
                <i class="fas fa-<?php echo $favicon_exists ? 'check' : 'times'; ?>"></i>
                <?php echo $favicon_exists ? 'Active' : 'Not Set'; ?>
            </span>
        </h2>
    </div>
    <div class="seo-section-body">
        <div class="logo-upload-area">
            <div class="logo-preview-box light-bg">
                <div class="logo-preview-inner">
                    <?php if ($favicon_exists): ?>
                        <img src="<?php echo SITE_URL; ?>/uploads/favicon.png?v=<?php echo time(); ?>" alt="Favicon" style="max-width:80px;max-height:80px;">
                    <?php else: ?>
                        <div class="no-logo-placeholder"><i class="fas fa-star"></i><p>No favicon</p></div>
                    <?php endif; ?>
                </div>
                <div class="logo-info-list">
                    <div class="logo-info-row"><span class="lbl">Used in</span><span class="val">Browser tab icon</span></div>
                    <div class="logo-info-row"><span class="lbl">File path</span><span class="val">/uploads/favicon.png</span></div>
                    <?php if ($favicon_exists): ?><div class="logo-info-row"><span class="lbl">Dimensions</span><span class="val" id="faviconDimensions">Loading…</span></div><?php endif; ?>
                </div>
            </div>
            <div class="logo-upload-controls">
                <h4><i class="fas fa-star" style="color:#f59e0b;margin-right:6px;"></i>Favicon (Browser Tab Icon)</h4>
                <p>The small icon shown in browser tabs, bookmarks, and search results. Must be a <strong>square PNG</strong>. Google recommends 512&times;512 px for best compatibility.</p>
                <div class="upload-specs">
                    <span class="spec-badge"><i class="fas fa-file-image"></i> PNG only</span>
                    <span class="spec-badge"><i class="fas fa-expand"></i> 512&times;512 px</span>
                    <span class="spec-badge"><i class="fas fa-vector-square"></i> Square shape</span>
                    <span class="spec-badge"><i class="fas fa-weight"></i> Max 2MB</span>
                </div>
                <div class="action-row">
                    <form id="faviconForm" enctype="multipart/form-data">
                        <input type="file" id="faviconFile" name="logo" accept="image/png" class="file-input-hidden" onchange="previewLogo('favicon')">
                        <label for="faviconFile" class="btn-action purple">
                            <i class="fas fa-upload"></i> <?php echo $favicon_exists ? 'Replace Favicon' : 'Upload Favicon'; ?>
                        </label>
                    </form>
                    <?php if ($favicon_exists): ?>
                    <button type="button" class="btn-action red" onclick="deleteLogo('favicon')">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tip -->
<div class="tip-bar" style="margin-bottom:24px;">
    <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
    <div>
        <strong>Quick Tip</strong>
        <p>Using the same branding for both header and footer? Upload the same file twice. If your header logo is dark, create a white version for the footer since the footer has a dark background.</p>
    </div>
</div>

</div>


<script>
// Get logo dimensions
window.addEventListener('DOMContentLoaded', function() {
    <?php if ($logo_exists): ?>
    const headerImg = new Image();
    headerImg.src = '<?php echo SITE_URL; ?>/uploads/logo.png?v=' + Date.now();
    headerImg.onload = function() {
        document.getElementById('logoDimensions').textContent = this.width + ' × ' + this.height + ' px';
    };
    <?php endif; ?>
    
    <?php if ($footer_logo_exists): ?>
    const footerImg = new Image();
    footerImg.src = '<?php echo SITE_URL; ?>/uploads/logo-footer.png?v=' + Date.now();
    footerImg.onload = function() {
        document.getElementById('footerLogoDimensions').textContent = this.width + ' × ' + this.height + ' px';
    };
    <?php endif; ?>
    
    <?php if ($favicon_exists): ?>
    const faviconImg = new Image();
    faviconImg.src = '<?php echo SITE_URL; ?>/uploads/favicon.png?v=' + Date.now();
    faviconImg.onload = function() {
        document.getElementById('faviconDimensions').textContent = this.width + ' × ' + this.height + ' px';
    };
    <?php endif; ?>
});

function setLogoLoading(labelFor, loading) {
    const label = document.querySelector('label[for="' + labelFor + '"]');
    if (!label) return;
    if (loading) {
        label._originalHTML = label.innerHTML;
        label.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
        label.style.pointerEvents = 'none';
        label.style.opacity = '0.7';
    } else {
        if (label._originalHTML) label.innerHTML = label._originalHTML;
        label.style.pointerEvents = '';
        label.style.opacity = '';
    }
}

function previewLogo(type) {
    const inputId = type === 'favicon' ? 'faviconFile' : type + 'LogoFile';
    const fileInput = document.getElementById(inputId);
    const file = fileInput.files[0];

    if (!file) return;

    // Validate file type (PNG, JPG, WebP)
    const allowed = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
    if (!allowed.includes(file.type)) {
        GCMAlert.warning('Please upload a PNG, JPG, or WebP image file.', 'Invalid File Type');
        fileInput.value = '';
        return;
    }

    // Validate file size (5MB)
    if (file.size > 5 * 1024 * 1024) {
        GCMAlert.warning('File size must be less than 5MB.', 'File Too Large');
        fileInput.value = '';
        return;
    }

    // Show loading state
    setLogoLoading(inputId, true);

    const formData = new FormData();
    formData.append('logo', file);
    formData.append('type', type);

    fetch('../api/upload-logo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text().then(text => {
        try { return JSON.parse(text); }
        catch (e) { throw new Error('Server returned invalid response: ' + text.substring(0, 120)); }
    }))
    .then(data => {
        setLogoLoading(inputId, false);
        if (data.success) {
            GCMAlert.success('Logo uploaded and saved successfully!', 'Upload Complete', () => location.reload());
        } else {
            GCMAlert.error((data.message || 'Upload failed') + (data.details ? '\n\n' + data.details : ''), 'Upload Failed');
        }
    })
    .catch(error => {
        setLogoLoading(inputId, false);
        GCMAlert.error('Upload error: ' + error.message, 'Upload Failed');
        console.error('Upload error:', error);
    });
}

function deleteLogo(type) {
    const names = { header: 'Header Logo', footer: 'Footer Logo', favicon: 'Favicon' };
    GCMAlert.confirm(
        'Are you sure you want to permanently delete the <strong>' + (names[type] || type) + '</strong>?',
        'Delete Logo',
        function() {
            fetch('../api/delete-logo.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({type: type})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    GCMAlert.success('Logo deleted successfully.', 'Deleted', () => location.reload());
                } else {
                    GCMAlert.error(data.message || 'Delete failed.', 'Error');
                }
            })
            .catch(err => GCMAlert.error('Network error: ' + err.message, 'Error'));
        }
    );
}
</script>

<?php include '../includes/footer.php'; ?>
