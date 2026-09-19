<?php
/**
 * Sitemap Generator
 * Automatically generate sitemap.xml for all pages
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../api/sitemap-functions.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Sitemap Generator';
include '../includes/header.php';

// Get statistics – filesystem is authoritative
$root_sm = dirname(dirname(__DIR__));

// Area pages: *-in-*.php files
$_gen_dir_sm  = $root_sm . '/generated-pages/';
$_area_sm     = is_dir($_gen_dir_sm) ? (glob($_gen_dir_sm . '*-in-*.php') ?: []) : [];
$_all_sm      = is_dir($_gen_dir_sm) ? (glob($_gen_dir_sm . '*.php')      ?: []) : [];
$_pillar_sm   = array_filter($_all_sm, fn($f) => basename($f) !== 'index.php' && strpos(basename($f), '-in-') === false);
$count_area   = count($_area_sm);
$count_pillar = count($_pillar_sm);
$count_service_pages = $count_area + $count_pillar;

// Blogs
$count_blogs = 0;
$blogs_dir_sm = $root_sm . '/data/blogs';
if (is_dir($blogs_dir_sm)) {
    $blog_files_sm = array_filter(glob($blogs_dir_sm . '/*.json') ?: [], fn($f) => !in_array(basename($f), ['index.json','stats.json']));
    $count_blogs   = count($blog_files_sm);
}

// DB fallback counts (when filesystem is empty)
if ($count_service_pages === 0 || $count_blogs === 0) {
    try {
        require_once '../../config/database.php';
        $dbi = Database::getInstance();

        if ($count_service_pages === 0) {
            try {
                $svc_db = (int)($dbi->fetchOne("SELECT COUNT(*) AS c FROM generated_pages WHERE is_published = 1")['c'] ?? 0);
            } catch (\Throwable $e) {
                $svc_db = 0;
            }
            if ($svc_db > 0) {
                $count_service_pages = $svc_db;
            } else {
                // fallback: keywords × areas
                $kwc = (int)($dbi->fetchOne("SELECT COUNT(*) AS c FROM seo_service_keywords WHERE is_active = 1")['c'] ?? 0);
                $arc = (int)($dbi->fetchOne("SELECT COUNT(*) AS c FROM service_areas WHERE is_active = 1")['c'] ?? 0);
                if ($kwc > 0 && $arc > 0) {
                    $count_service_pages = ($kwc * $arc) + $kwc; // area pages + pillar pages
                }
            }
        }

        if ($count_blogs === 0) {
            $bc = 0;
            try {
                $bc = (int)($dbi->fetchOne("SELECT COUNT(*) AS c FROM ai_blogs WHERE is_published = 1")['c'] ?? 0);
            } catch (\Throwable $e) {
                $bc = 0;
            }
            if ($bc === 0) {
                try {
                    $bc = (int)($dbi->fetchOne("SELECT COUNT(*) AS c FROM blog_posts WHERE is_published = 1")['c'] ?? 0);
                } catch (\Throwable $e) {
                    $bc = 0;
                }
            }
            if ($bc > 0) {
                $count_blogs = $bc;
            }
        }
    } catch (\Throwable $e) {
        // ignore
    }
}

// FAQs
$count_faqs = 0;
$faqs_dir_sm = $root_sm . '/data/faqs';
if (is_dir($faqs_dir_sm)) {
    $faq_files_sm = array_filter(glob($faqs_dir_sm . '/*.json') ?: [], fn($f) => !in_array(basename($f), ['index.json','stats.json']));
    $count_faqs   = count($faq_files_sm);
}

$total_static = 13; // static pages + videos.php included in sitemap
$total_urls   = $total_static + $count_service_pages + $count_blogs;

// Keep legacy aliases for display
$total_pages = ['count' => $count_service_pages];
$total_blogs = ['count' => $count_blogs];
$total_faqs  = ['count' => $count_faqs];

// Load current auto-refresh settings
$sm_settings = gcm_sitemap_settings();

// Check if sitemap exists
$sitemap_path     = $root_sm . '/sitemap.xml';
$sitemap_exists   = file_exists($sitemap_path);
$sitemap_size     = $sitemap_exists ? filesize($sitemap_path) : 0;
$sitemap_modified = $sitemap_exists ? date('M j, Y g:i A', filemtime($sitemap_path)) : 'Never';
// Overwrite total_urls with actual count from sitemap file if it exists
if ($sitemap_exists) {
    $sm_count = substr_count(file_get_contents($sitemap_path), '<url>') ?: $total_urls;
    $total_urls = $sm_count;
}
?>

<div class="sm-page">

<!-- Hero -->
<div class="sm-hero">
    <div class="sm-hero-left">
        <h1><i class="fas fa-sitemap"></i> Sitemap Generator</h1>
        <p>Generate &amp; submit your XML sitemap to all search engines</p>
    </div>
    <button class="sm-btn sm-btn-green" onclick="generateSitemap()">
        <i class="fas fa-sync-alt"></i> Generate Sitemap Now
    </button>
</div>

<!-- Stats unified container -->
<div class="sm-stats">
    <div class="sm-stat">
        <div class="sm-stat-icon si-blue"><i class="fas fa-file-code"></i></div>
        <div class="sm-stat-text">
            <div class="sm-stat-val"><?php echo number_format($total_urls); ?></div>
            <div class="sm-stat-lbl">Total URLs in Sitemap</div>
        </div>
    </div>
    <div class="sm-stat">
        <div class="sm-stat-icon si-green"><i class="fas fa-globe"></i></div>
        <div class="sm-stat-text">
            <div class="sm-stat-val"><?php echo number_format($count_service_pages); ?></div>
            <div class="sm-stat-lbl">Service Pages</div>
        </div>
    </div>
    <div class="sm-stat">
        <div class="sm-stat-icon si-purple"><i class="fas fa-blog"></i></div>
        <div class="sm-stat-text">
            <div class="sm-stat-val"><?php echo number_format($count_blogs); ?></div>
            <div class="sm-stat-lbl">Blog Posts</div>
        </div>
    </div>
    <div class="sm-stat">
        <div class="sm-stat-icon si-orange"><i class="fas fa-clock"></i></div>
        <div class="sm-stat-text">
            <div class="sm-stat-val sm-stat-date"><?php echo $sitemap_modified; ?></div>
            <div class="sm-stat-lbl">Last Generated</div>
        </div>
    </div>
</div>

<!-- Sitemap Status -->
<div class="sm-section">
    <div class="sm-section-head">
        <div class="sm-sec-icon sci-blue"><i class="fas fa-info-circle"></i></div>
        <h2>Sitemap Status</h2>
        <span class="sm-status-pill <?php echo $sitemap_exists ? 'pill-active' : 'pill-inactive'; ?>">
            <?php echo $sitemap_exists ? 'Active' : 'Not Generated'; ?>
        </span>
    </div>
    <div class="sm-section-body">
        <div class="sm-info-list">
            <div class="sm-info-row">
                <span class="sm-info-lbl"><i class="fas fa-file"></i> File Status</span>
                <?php if ($sitemap_exists): ?>
                    <span class="sm-badge sm-badge-green">✓ Exists</span>
                <?php else: ?>
                    <span class="sm-badge sm-badge-red">✗ Not Found</span>
                <?php endif; ?>
            </div>
            <div class="sm-info-row">
                <span class="sm-info-lbl"><i class="fas fa-link"></i> Sitemap URL</span>
                <a href="<?php echo SITE_URL; ?>/sitemap.xml" target="_blank" class="sm-link">
                    <?php echo SITE_URL; ?>/sitemap.xml <i class="fas fa-external-link-alt" style="font-size:11px;"></i>
                </a>
            </div>
            <div class="sm-info-row">
                <span class="sm-info-lbl"><i class="fas fa-database"></i> File Size</span>
                <span><?php echo $sitemap_exists ? number_format($sitemap_size / 1024, 2) . ' KB' : 'N/A'; ?></span>
            </div>
            <div class="sm-info-row">
                <span class="sm-info-lbl"><i class="fas fa-calendar"></i> Last Modified</span>
                <span><?php echo $sitemap_modified; ?></span>
            </div>
            <div class="sm-info-row">
                <span class="sm-info-lbl"><i class="fas fa-list"></i> Total URLs</span>
                <span class="sm-url-count"><?php echo number_format($total_urls); ?> URLs</span>
            </div>
        </div>
        <div class="sm-action-row">
            <button class="sm-btn sm-btn-purple" onclick="generateSitemap()">
                <i class="fas fa-sync-alt"></i> Generate Sitemap
            </button>
            <?php if ($sitemap_exists): ?>
            <a href="<?php echo SITE_URL; ?>/sitemap.xml" target="_blank" class="sm-btn sm-btn-blue">
                <i class="fas fa-eye"></i> View Sitemap
            </a>
            <button class="sm-btn sm-btn-green" onclick="submitToGoogle()">
                <i class="fab fa-google"></i> Submit to Google
            </button>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- What's Included -->
<div class="sm-section">
    <div class="sm-section-head">
        <div class="sm-sec-icon sci-green"><i class="fas fa-list-check"></i></div>
        <h2>What's Included in Sitemap</h2>
    </div>
    <div class="sm-section-body">
        <div class="sm-inc-grid">
            <div class="sm-inc-card">
                <div class="sm-inc-top sic-blue">
                    <div class="sm-inc-icon"><i class="fas fa-home"></i></div>
                    <h4>Static Pages</h4>
                </div>
                <div class="sm-inc-body">
                    <p>Homepage, About, Contact, FAQs, Gallery, Videos, Reviews, Blogs, All Areas, Estimation, Privacy Policy, Terms</p>
                    <span class="sm-badge sm-badge-blue"><?php echo $total_static; ?> pages</span>
                </div>
            </div>
            <div class="sm-inc-card">
                <div class="sm-inc-top sic-green">
                    <div class="sm-inc-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <h4>Area Service Pages</h4>
                </div>
                <div class="sm-inc-body">
                    <p>All area-specific service pages (e.g. pigeon-nets-in-kondapur) — 64 keywords × 188 areas</p>
                    <span class="sm-badge sm-badge-green"><?php echo number_format($count_area); ?> pages</span>
                </div>
            </div>
            <div class="sm-inc-card">
                <div class="sm-inc-top" style="background:linear-gradient(135deg,rgba(245,158,11,.07),rgba(217,119,6,.04));border-bottom:3px solid #f59e0b;">
                    <div class="sm-inc-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-star"></i></div>
                    <h4 style="color:#d97706;">Pillar Pages</h4>
                </div>
                <div class="sm-inc-body">
                    <p>One pillar/keyword page per service (e.g. pigeon-nets, sports-nets) — top-priority pages</p>
                    <span class="sm-badge" style="background:#fef3c7;color:#92400e;"><?php echo number_format($count_pillar); ?> pages</span>
                </div>
            </div>
            <div class="sm-inc-card">
                <div class="sm-inc-top sic-purple">
                    <div class="sm-inc-icon"><i class="fas fa-blog"></i></div>
                    <h4>Blog Posts</h4>
                </div>
                <div class="sm-inc-body">
                    <p>All AI-generated blog posts including daily auto-generated content</p>
                    <span class="sm-badge sm-badge-purple"><?php echo number_format($count_blogs); ?> posts</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Generation Progress -->
<div id="generationProgress" class="sm-section" style="display:none;">
    <div class="sm-section-head">
        <div class="sm-sec-icon sci-amber"><i class="fas fa-spinner fa-spin"></i></div>
        <h2>Generating Sitemap...</h2>
    </div>
    <div class="sm-section-body">
        <div class="sm-prog-wrap">
            <div class="sm-prog-bar"><div class="sm-prog-fill" id="progressBar"></div></div>
            <p class="sm-prog-text" id="progressText">Initializing...</p>
        </div>
    </div>
</div>

<!-- Auto-Refresh Settings -->
<div class="sm-section">
    <div class="sm-section-head">
        <div class="sm-sec-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-sync-alt"></i></div>
        <h2>Auto-Refresh Settings</h2>
        <span style="margin-left:auto;font-size:12px;color:#64748b;">Controls automatic sitemap updates when new content is generated</span>
    </div>
    <div class="sm-section-body">
        <div class="sm-tips-grid" style="margin-bottom:20px;">
            <div class="sm-tip" style="background:#f0fdf4;border-color:#10b981;">
                <div class="sm-tip-dot" style="background:linear-gradient(135deg,#10b981,#059669);">
                    <i class="fas fa-robot"></i>
                </div>
                <div style="flex:1;">
                    <p style="font-weight:700;margin-bottom:6px;color:#065f46;">Auto-Regenerate Sitemap</p>
                    <p style="margin:0;">Automatically regenerate sitemap.xml whenever a new blog post or AI content is created (daily cron, single blog, bulk blogs).</p>
                </div>
                <label class="sm-toggle" title="Enable/disable auto-regeneration">
                    <input type="checkbox" id="toggleAutoRefresh" <?php echo !empty($sm_settings['auto_refresh']) ? 'checked' : ''; ?> onchange="saveSitemapSettings()">
                    <span class="sm-toggle-slider"></span>
                </label>
            </div>
            <div class="sm-tip" style="background:#eff6ff;border-color:#3b82f6;">
                <div class="sm-tip-dot" style="background:linear-gradient(135deg,#3b82f6,#2563eb);">
                    <i class="fab fa-google"></i>
                </div>
                <div style="flex:1;">
                    <p style="font-weight:700;margin-bottom:6px;color:#1e40af;">Auto-Ping Search Engines</p>
                    <p style="margin:0;">After each auto-regeneration, automatically ping Google &amp; Bing with the updated sitemap URL so they re-crawl faster.</p>
                </div>
                <label class="sm-toggle" title="Enable/disable auto-ping">
                    <input type="checkbox" id="toggleAutoPing" <?php echo !empty($sm_settings['auto_ping']) ? 'checked' : ''; ?> onchange="saveSitemapSettings()">
                    <span class="sm-toggle-slider"></span>
                </label>
            </div>
        </div>
        <div id="smSettingsStatus" style="font-size:13px;color:#64748b;padding:10px 0;">
            <?php if (!empty($sm_settings['last_refresh'])): ?>
                Last auto-refresh: <strong><?php echo htmlspecialchars($sm_settings['last_refresh']); ?></strong>
                &nbsp;|&nbsp; URLs at that time: <strong><?php echo number_format($sm_settings['last_refresh_urls'] ?? 0); ?></strong>
            <?php else: ?>
                No auto-refresh recorded yet.
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- SEO Tips -->
<div class="sm-section">
    <div class="sm-section-head">
        <div class="sm-sec-icon sci-amber"><i class="fas fa-lightbulb"></i></div>
        <h2>SEO Best Practices</h2>
    </div>
    <div class="sm-section-body">
        <div class="sm-tips-grid">
            <div class="sm-tip"><div class="sm-tip-dot"><i class="fas fa-check"></i></div><p><strong>Auto-Generate:</strong> Sitemap is automatically generated when you create new pages</p></div>
            <div class="sm-tip"><div class="sm-tip-dot"><i class="fas fa-check"></i></div><p><strong>Submit to Google:</strong> After generating, submit to Google Search Console</p></div>
            <div class="sm-tip"><div class="sm-tip-dot"><i class="fas fa-check"></i></div><p><strong>Update Regularly:</strong> Regenerate sitemap whenever you add new content</p></div>
            <div class="sm-tip"><div class="sm-tip-dot"><i class="fas fa-check"></i></div><p><strong>Check Errors:</strong> Monitor Search Console for indexing issues</p></div>
        </div>
    </div>
</div>

</div>

<style>
/* ── Page wrapper ──────────────────────────────── */
.sm-page { padding: 0; }

/* ── Hero ──────────────────────────────────────── */
.sm-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 30px 36px; margin-bottom: 22px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#3b82f6,#2563eb) 1; }
.sm-hero h1 { font-size: 30px; font-weight: 800; color: #1e293b; margin: 0 0 5px; }
.sm-hero h1 i { color: #3b82f6; margin-right: 10px; }
.sm-hero p { color: #64748b; font-size: 14px; margin: 0; }

/* ── Buttons ───────────────────────────────────── */
.sm-btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px; border: none; border-radius: 12px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.sm-btn:hover { transform: translateY(-2px); text-decoration: none; }
.sm-btn-green  { background: linear-gradient(135deg,#10b981,#059669); color: white; box-shadow: 0 6px 18px rgba(16,185,129,.35); }
.sm-btn-purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 18px rgba(102,126,234,.35); }
.sm-btn-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; box-shadow: 0 6px 18px rgba(59,130,246,.35); }
.sm-btn-green:hover  { box-shadow: 0 10px 26px rgba(16,185,129,.45); }
.sm-btn-purple:hover { box-shadow: 0 10px 26px rgba(102,126,234,.45); }
.sm-btn-blue:hover   { box-shadow: 0 10px 26px rgba(59,130,246,.45); }

/* ── Stats unified container ───────────────────── */
.sm-stats { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 22px; }
.sm-stat { padding: 26px 22px; display: flex; align-items: center; gap: 16px; position: relative; transition: background .2s; }
.sm-stat:hover { background: #f8faff; }
.sm-stat + .sm-stat::before { content:''; position:absolute; left:0; top:15%; bottom:15%; width:1px; background:#e2e8f0; }
.sm-stat-icon { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.si-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.si-green  { background: linear-gradient(135deg,#10b981,#059669); }
.si-purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.si-orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sm-stat-val { font-size: 1.9rem; font-weight: 800; color: #1e293b; line-height: 1.1; }
.sm-stat-date { font-size: 1rem !important; line-height: 1.35 !important; }
.sm-stat-lbl { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-top: 4px; }

/* ── Section card ──────────────────────────────── */
.sm-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 22px; overflow: hidden; }
.sm-section-head { padding: 20px 28px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.sm-sec-icon { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; color: white; flex-shrink: 0; }
.sci-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sci-green  { background: linear-gradient(135deg,#10b981,#059669); }
.sci-purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sci-amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sm-section-head h2 { font-size: 17px; font-weight: 700; color: #1e293b; margin: 0; }
.sm-status-pill { padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; margin-left: auto; }
.pill-active   { background: #d1fae5; color: #065f46; }
.pill-inactive { background: #fee2e2; color: #991b1b; }
.sm-section-body { padding: 24px 28px; }

/* ── Info rows ─────────────────────────────────── */
.sm-info-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
.sm-info-row { display: flex; justify-content: space-between; align-items: center; padding: 14px 18px; background: #f8fafc; border-radius: 10px; font-size: 14px; transition: background .2s, transform .2s; }
.sm-info-row:hover { background: #f1f5f9; transform: translateX(4px); }
.sm-info-lbl { font-weight: 600; color: #475569; display: flex; align-items: center; gap: 8px; }
.sm-link { color: #3b82f6; text-decoration: none; font-weight: 500; }
.sm-link:hover { text-decoration: underline; }
.sm-url-count { color: #1e293b; font-weight: 700; }

/* ── Action row ────────────────────────────────── */
.sm-action-row { display: flex; gap: 12px; flex-wrap: wrap; }

/* ── Badges ────────────────────────────────────── */
.sm-badge { display: inline-block; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; }
.sm-badge-green  { background: #d1fae5; color: #065f46; }
.sm-badge-red    { background: #fee2e2; color: #991b1b; }
.sm-badge-blue   { background: #dbeafe; color: #1e40af; }
.sm-badge-purple { background: #ede9fe; color: #6b21a8; }

/* ── Included cards ────────────────────────────── */
.sm-inc-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 18px; }
.sm-inc-card { border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; transition: all .25s; }
.sm-inc-card:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(0,0,0,.09); }
.sm-inc-top { padding: 20px 18px 14px; text-align: center; }
.sic-blue   { background: linear-gradient(135deg,rgba(59,130,246,.07),rgba(37,99,235,.04)); border-bottom: 3px solid #3b82f6; }
.sic-green  { background: linear-gradient(135deg,rgba(16,185,129,.07),rgba(5,150,105,.04)); border-bottom: 3px solid #10b981; }
.sic-purple { background: linear-gradient(135deg,rgba(139,92,246,.07),rgba(124,58,237,.04)); border-bottom: 3px solid #8b5cf6; }
.sm-inc-icon { width: 48px; height: 48px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; color: white; margin-bottom: 10px; }
.sic-blue .sm-inc-icon   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sic-green .sm-inc-icon  { background: linear-gradient(135deg,#10b981,#059669); }
.sic-purple .sm-inc-icon { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.sm-inc-top h4 { font-size: 14px; font-weight: 700; margin: 0; }
.sic-blue h4 { color: #2563eb; } .sic-green h4 { color: #059669; } .sic-purple h4 { color: #7c3aed; }
.sm-inc-body { padding: 16px 16px 18px; }
.sm-inc-body p { font-size: 12.5px; color: #64748b; margin: 0 0 12px; line-height: 1.6; }

/* ── Progress ──────────────────────────────────── */
.sm-prog-wrap { padding: 8px 0; }
.sm-prog-bar  { background: #e2e8f0; height: 22px; border-radius: 11px; overflow: hidden; margin-bottom: 12px; }
.sm-prog-fill { height: 100%; background: linear-gradient(90deg,#3b82f6,#10b981); width: 0%; transition: width .4s; border-radius: 11px; }
.sm-prog-text { color: #475569; font-size: 14px; font-weight: 600; margin: 0; }

/* ── Tips ──────────────────────────────────────── */
.sm-tips-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.sm-tip { display: flex; align-items: flex-start; gap: 12px; padding: 16px; background: #f0f9ff; border-radius: 10px; border-left: 4px solid #3b82f6; transition: all .2s; }
.sm-tip:hover { background: #e0f2fe; transform: translateX(4px); }
.sm-tip-dot { width: 26px; height: 26px; border-radius: 50%; background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
.sm-tip p { margin: 0; font-size: 13.5px; color: #475569; line-height: 1.5; }

@media(max-width:768px) {
    .sm-stats { grid-template-columns: repeat(2,1fr) !important; overflow: visible; border-radius: 14px; }
    .sm-stat + .sm-stat::before { display: none; }
    .sm-stat { border: 1px solid #f1f5f9 !important; border-radius: 12px !important; padding: 18px 14px; }
    .sm-inc-grid, .sm-tips-grid { grid-template-columns: 1fr; }
    .sm-hero { flex-direction: column; align-items: flex-start; padding: 20px 16px; gap: 14px; }
    .sm-hero-right { flex-wrap: wrap; gap: 8px; }
    .sm-section-head { padding: 14px 16px; }
    .sm-section-body, .sm-section > div:not(.sm-section-head) { padding: 14px 16px; }
    .sm-url, .sm-section a { word-break: break-all; overflow-wrap: break-word; }
}
@media(max-width:480px) {
    .sm-stats { grid-template-columns: repeat(2,1fr) !important; gap: 0; }
    .sm-stat { padding: 14px 10px; }
    .sm-stat-num { font-size: 22px !important; }
    .sm-stat-lbl { font-size: 9px; }
    .sm-btn, .sm-btn-blue { padding: 10px 14px; font-size: 13px; width: 100%; justify-content: center; }
}

/* ── Toggle Switch ─────────────────────────────── */
.sm-toggle { position:relative; display:inline-block; width:48px; height:26px; flex-shrink:0; }
.sm-toggle input { opacity:0; width:0; height:0; }
.sm-toggle-slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background:#cbd5e1; border-radius:13px; transition:.3s; }
.sm-toggle-slider:before { content:''; position:absolute; width:20px; height:20px; left:3px; bottom:3px; background:white; border-radius:50%; transition:.3s; }
.sm-toggle input:checked + .sm-toggle-slider { background:#10b981; }
.sm-toggle input:checked + .sm-toggle-slider:before { transform:translateX(22px); }
</style>

<script>
function generateSitemap() {
    if (!confirm('Generate sitemap.xml now?')) return;
    
    const progress = document.getElementById('generationProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    progress.style.display = 'block';
    progressBar.style.width = '0%';
    progressText.textContent = 'Generating sitemap...';
    
    fetch('../api/generate-sitemap.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            progressBar.style.width = '100%';
            progressText.textContent = `✓ Sitemap generated successfully! ${data.total_urls} URLs added.`;
            setTimeout(() => location.reload(), 2000);
        } else {
            progressText.textContent = '✗ Error: ' + data.message;
        }
    })
    .catch(error => {
        progressText.textContent = '✗ Failed to generate sitemap';
        console.error(error);
    });
}

function submitToGoogle() {
    const sitemapUrl = '<?php echo SITE_URL; ?>/sitemap.xml';
    // Ping both Google and Bing
    fetch('../api/generate-sitemap.php', { method:'POST', body: new URLSearchParams({ping:'1'}) })
        .then(r => r.json())
        .then(d => alert(d.pinged ? '✓ Sitemap submitted to Google & Bing!' : 'Sitemap generated. Open Search Console to submit manually.'))
        .catch(() => window.open(`https://www.google.com/ping?sitemap=${encodeURIComponent(sitemapUrl)}`, '_blank'));
}

function saveSitemapSettings() {
    const autoRefresh = document.getElementById('toggleAutoRefresh').checked;
    const autoPing    = document.getElementById('toggleAutoPing').checked;
    fetch('../api/save-sitemap-settings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ auto_refresh: autoRefresh, auto_ping: autoPing })
    })
    .then(r => r.json())
    .then(d => {
        const el = document.getElementById('smSettingsStatus');
        if (d.success) {
            el.innerHTML = '<span style="color:#059669;">✓ Settings saved — auto-refresh is now <strong>' + (autoRefresh ? 'ON' : 'OFF') + '</strong>' + (autoRefresh && autoPing ? ' with auto-ping enabled' : '') + '</strong>.</span>';
        } else {
            el.innerHTML = '<span style="color:#dc2626;">✗ Failed to save settings.</span>';
        }
    })
    .catch(() => document.getElementById('smSettingsStatus').innerHTML = '<span style="color:#dc2626;">✗ Error saving settings.</span>');
}
</script>
<?php
// Auto-generate sitemap after page generation
if (isset($_GET['auto_generate']) && $_GET['auto_generate'] == 'true') {
    echo '<script>generateSitemap();</script>';
}
?>

<?php include '../includes/footer.php'; ?>
