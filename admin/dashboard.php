<?php
/**
 * Admin Panel - Main Dashboard
 */

define('GCM_INIT', true);
require_once '../config/config.php';

// Start session with proper settings (must match login.php)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// ── DB + file-system stats ────────────────────────────────────────
$base_dir = dirname(__DIR__);
$stats = [];

// Bootstrap DB connection (non-fatal if unavailable)
if (!class_exists('Database')) {
    @require_once $base_dir . '/config/database.php';
}
$db = null;
try { $db = Database::getInstance(); } catch (Exception $e) {}

// 1. Service Pages – filesystem is authoritative (bulk pages are file-only, DB only tracks a subset)
$_pg_area_f   = glob($base_dir . '/generated-pages/*-in-*.php') ?: [];
$_pg_all_f    = glob($base_dir . '/generated-pages/*.php') ?: [];
$_pg_pillar_f = array_filter($_pg_all_f, fn($f) => basename($f) !== 'index.php' && strpos(basename($f), '-in-') === false);
$_fs_pages    = count($_pg_area_f) + count($_pg_pillar_f);
try {
    if (!$db) throw new Exception('no db');
    $pg = $db->fetchOne("SELECT COUNT(*) as cnt FROM generated_pages");
    $db_count = (int)($pg['cnt'] ?? 0);
    $stats['total_pages']     = max($_fs_pages, $db_count);
    $stats['published_pages'] = $stats['total_pages'];
    $stats['draft_pages']     = 0;
} catch (Exception $e) {
    $stats['total_pages']     = $_fs_pages;
    $stats['published_pages'] = $_fs_pages;
    $stats['draft_pages']     = 0;
}

// 2. Reviews – approved JSON files + pending
$reviews_dir  = $base_dir . '/data/reviews';
$pending_dir  = $reviews_dir . '/pending';
$approved_files = [];
if (is_dir($reviews_dir)) {
    $approved_files = array_values(array_filter(
        glob($reviews_dir . '/*.json') ?: [],
        fn($f) => basename($f) !== 'stats.json'
    ));
}
$pending_files = is_dir($pending_dir) ? (glob($pending_dir . '/*.json') ?: []) : [];
$stats['published_reviews'] = count($approved_files);
$stats['pending_reviews']   = count($pending_files);
$stats['total_reviews']     = $stats['published_reviews'] + $stats['pending_reviews'];

// 3. Blogs – JSON files in data/blogs/
$blogs_dir  = $base_dir . '/data/blogs';
$blog_files = [];
if (is_dir($blogs_dir)) {
    $blog_files = array_values(array_filter(
        glob($blogs_dir . '/*.json') ?: [],
        fn($f) => !in_array(basename($f), ['index.json', 'stats.json'])
    ));
}
$stats['total_blogs']     = count($blog_files);
$stats['published_blogs'] = $stats['total_blogs'];

// 4. FAQs – JSON files in data/faqs/
$faqs_dir  = $base_dir . '/data/faqs';
$faq_files = [];
if (is_dir($faqs_dir)) {
    $faq_files = array_values(array_filter(
        glob($faqs_dir . '/*.json') ?: [],
        fn($f) => !in_array(basename($f), ['index.json', 'stats.json'])
    ));
}
$stats['total_faqs'] = count($faq_files);

// 5. Gallery images – assets/img/gallery/
$gallery_dir = $base_dir . '/assets/img/gallery';
$img_files   = is_dir($gallery_dir)
    ? (glob($gallery_dir . '/*.{jpg,jpeg,png,webp,JPG,PNG,WEBP}', GLOB_BRACE) ?: [])
    : [];
$stats['total_images'] = count($img_files);

// 6. Videos – JSON files in data/videos/
$videos_dir = $base_dir . '/data/videos';
$vid_files  = [];
if (is_dir($videos_dir)) {
    $vid_files = array_values(array_filter(
        glob($videos_dir . '/*.json') ?: [],
        fn($f) => basename($f) !== 'stats.json'
    ));
}
$stats['total_videos'] = count($vid_files);

// 7. Visitor Analytics – from visitor_tracking table
$stats['visitors_today']  = 0;
$stats['active_now']      = 0;
$stats['page_views_today']= 0;
try {
    if ($db) {
        // auto-create table silently
        $db->getConnection()->exec("CREATE TABLE IF NOT EXISTS visitor_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(64) NOT NULL,
            ip_address VARCHAR(45), user_agent TEXT,
            browser VARCHAR(100), browser_version VARCHAR(50),
            device_type VARCHAR(20) DEFAULT 'desktop', os VARCHAR(100),
            country VARCHAR(100), country_code VARCHAR(10),
            region VARCHAR(100), city VARCHAR(100),
            latitude DECIMAL(10,8), longitude DECIMAL(11,8),
            timezone VARCHAR(50), isp VARCHAR(255),
            current_page VARCHAR(500), entry_page VARCHAR(500), referrer VARCHAR(500),
            is_online TINYINT(1) DEFAULT 0, page_views INT DEFAULT 1,
            total_time_spent INT DEFAULT 0,
            first_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY idx_session (session_id), KEY idx_first_visit (first_visit)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $stats['visitors_today']   = (int)($db->fetchOne("SELECT COUNT(DISTINCT session_id) AS c FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()")['c'] ?? 0);
        $stats['active_now']       = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM visitor_tracking WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)")['c'] ?? 0);
        $stats['page_views_today'] = (int)($db->fetchOne("SELECT COALESCE(SUM(page_views),0) AS c FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()")['c'] ?? 0);
    }
} catch (Exception $e) { /* non-fatal */ }

// ── Recent pages – from DB (most accurate) ───────────────────────
$recent_pages = [];
try {
    if (!$db) throw new Exception('no db');
    $recent_pages = $db->fetchAll(
        "SELECT gp.page_slug, gp.page_title as keyword_text, gp.is_published, gp.generated_at,
                sa.area_name
         FROM generated_pages gp
         LEFT JOIN service_areas sa ON gp.area_id = sa.id
         ORDER BY gp.generated_at DESC LIMIT 5"
    );
} catch (Exception $e) {
    // Fallback: newest files in generated-pages/
    $pg_files = glob($base_dir . '/generated-pages/*-in-*.php') ?: [];
    if (!empty($pg_files)) {
        usort($pg_files, fn($a, $b) => filemtime($b) - filemtime($a));
        foreach (array_slice($pg_files, 0, 5) as $pgf) {
            $slug  = basename($pgf, '.php');
            $parts = explode('-in-', $slug, 2);
            $recent_pages[] = [
                'page_slug'    => $slug,
                'keyword_text' => ucwords(str_replace('-', ' ', $parts[0] ?? $slug)),
                'area_name'    => ucwords(str_replace('-', ' ', $parts[1] ?? '')),
                'is_published' => 1,
                'generated_at' => date('Y-m-d H:i:s', filemtime($pgf)),
            ];
        }
    }
}

// ── Pending reviews for bottom panel ─────────────────────────────
$pending_reviews = [];
foreach (array_slice($pending_files, 0, 5) as $pf) {
    $rv = json_decode(file_get_contents($pf), true);
    if ($rv) {
        $rv['filename']     = basename($pf);
        $rv['submitted_at'] = $rv['created_at'] ?? date('Y-m-d H:i:s', filemtime($pf));
        $rv['service_name'] = $rv['service'] ?? $rv['category'] ?? '';
        $rv['customer_name'] = $rv['customer_name'] ?? $rv['name'] ?? 'Anonymous';
        $rv['review_text']   = $rv['review_text'] ?? $rv['message'] ?? '';
        $pending_reviews[]   = $rv;
    }
}

// ── Sitemap recovery check (detects stale/missing sitemap after re-upload) ──
$sitemap_path      = $base_dir . '/sitemap.xml';
$sitemap_url_count = 0;
if (file_exists($sitemap_path)) {
    $sm_content        = @file_get_contents($sitemap_path);
    $sitemap_url_count = $sm_content ? substr_count($sm_content, '<url>') : 0;
}
$sitemap_needs_regen = ($sitemap_url_count < 1000);

$page_title = 'Dashboard';
include 'includes/header.php';
?>

<div class="seo-page">

<?php if ($sitemap_needs_regen): ?>
<!-- Sitemap Recovery Banner -->
<div id="sitemapRecoveryBanner" style="background:linear-gradient(135deg,#fef3c7,#fffbeb);border:2px solid #f59e0b;border-radius:16px;padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;box-shadow:0 4px 15px rgba(245,158,11,.2);">
    <div style="display:flex;align-items:center;gap:14px;">
        <div style="width:44px;height:44px;background:linear-gradient(135deg,#f59e0b,#d97706);border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
            <i class="fas fa-sitemap" style="color:white;font-size:18px;"></i>
        </div>
        <div>
            <div style="font-weight:800;color:#92400e;font-size:16px;">Sitemap needs regeneration</div>
            <div style="font-size:13px;color:#78350f;margin-top:2px;">
                Current sitemap has only <strong><?php echo $sitemap_url_count; ?> URLs</strong> — your site has <strong><?php echo number_format($stats['total_pages']); ?>+ pages</strong>. This happens after a fresh re-upload. Auto-regenerating now...
            </div>
        </div>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <span id="sitemapRegenStatus" style="font-size:13px;font-weight:600;color:#92400e;"><i class="fas fa-spinner fa-spin"></i> Regenerating...</span>
        <a href="pages/sitemap-generator.php" style="padding:9px 18px;background:#f59e0b;color:white;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;white-space:nowrap;">
            <i class="fas fa-sitemap"></i> Sitemap Generator
        </a>
    </div>
</div>
<script>
// Auto-regenerate sitemap silently on page load when it's missing/stale
(function() {
    fetch('api/generate-sitemap.php', { method: 'POST', credentials: 'same-origin' })
    .then(r => r.json())
    .then(d => {
        const st = document.getElementById('sitemapRegenStatus');
        if (d && d.success) {
            st.innerHTML = '<i class="fas fa-check-circle" style="color:#065f46;"></i> <span style="color:#065f46;">Done — ' + (d.total_urls || '') + ' URLs generated!</span>';
            setTimeout(() => {
                const b = document.getElementById('sitemapRecoveryBanner');
                if (b) b.style.background = 'linear-gradient(135deg,#d1fae5,#ecfdf5)';
                if (b) b.style.borderColor = '#10b981';
            }, 500);
        } else {
            st.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i> <span style="color:#dc2626;">Auto-regen failed — click Sitemap Generator to fix</span>';
        }
    })
    .catch(() => {
        const st = document.getElementById('sitemapRegenStatus');
        if (st) st.innerHTML = '<a href="pages/sitemap-generator.php?auto_generate=1" style="color:#92400e;font-weight:700;">Click here to regenerate manually</a>';
    });
})();
</script>
<?php endif; ?>

<!-- Hero -->
<div class="dash-hero">
    <div class="dash-hero-content">
        <?php $dh=(int)date('G'); $dg=$dh<12?'Good Morning':($dh<17?'Good Afternoon':'Good Evening'); $di=$dh<12?'fa-sun':($dh<17?'fa-cloud-sun':'fa-moon'); ?>
        <span class="dash-greet-chip"><i class="fas <?php echo $di; ?>"></i> <?php echo $dg; ?></span>
        <h1><i class="fas fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p>Welcome back, <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username']); ?></strong> &nbsp;&bull;&nbsp; <?php echo date('l, F j, Y'); ?></p>
    </div>
    <div class="dash-hero-right">
        <div class="dash-live-clock" id="dashClock">
            <div class="dc-time"><?php echo date('h:i'); ?></div>
            <div class="dc-ampm"><?php echo date('A'); ?></div>
        </div>
        <span class="dash-admin-badge"><i class="fas fa-shield-alt"></i> GCM Admin</span>
    </div>
</div>

<!-- Solution Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-tachometer-alt"></i></div>
    <div>
        <strong>Admin Control Centre</strong>
        <p>Manage all website content — service pages, blogs, reviews, gallery, SEO tools, billing &amp; visitor analytics. All changes go live immediately.</p>
    </div>
</div>
    
<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon-wrap blue"><i class="fas fa-file-alt"></i></div><div class="stat-text-wrap"><h3>Total Pages</h3><div class="value"><?php echo number_format($stats['total_pages']); ?></div><p class="sub"><?php echo number_format($stats['published_pages']); ?> Published</p></div></div>
    <div class="stat-card"><div class="stat-icon-wrap orange"><i class="fas fa-star"></i></div><div class="stat-text-wrap"><h3>Reviews</h3><div class="value"><?php echo number_format($stats['total_reviews']); ?></div><p class="sub"><?php echo $stats['pending_reviews']; ?> Pending<?php echo $stats['pending_reviews']>0?' ⚠':''; ?></p></div></div>
    <div class="stat-card" style="cursor:pointer;" onclick="location.href='pages/visitor-analytics.php'"><div class="stat-icon-wrap green"><i class="fas fa-users"></i></div><div class="stat-text-wrap"><h3>Visitors Today</h3><div class="value" id="dashVisitorsToday"><?php echo number_format($stats['visitors_today']); ?></div><p class="sub" id="dashPageViews"><?php echo number_format($stats['page_views_today']); ?> page views</p></div></div>
    <div class="stat-card" style="cursor:pointer;" onclick="location.href='pages/live-visitors.php'"><div class="stat-icon-wrap teal"><i class="fas fa-circle" style="animation:pulseDot 1.5s infinite;"></i></div><div class="stat-text-wrap"><h3>Active Now</h3><div class="value" id="dashActiveNow"><?php echo number_format($stats['active_now']); ?></div><p class="sub">Live visitors</p></div></div>
</div>
<!-- Secondary strip -->
<div class="dash-strip">
    <div class="strip-item"><i class="fas fa-blog" style="color:#667eea;"></i><span><strong><?php echo number_format($stats['total_blogs']); ?></strong> Blogs</span></div>
    <div class="strip-item"><i class="fas fa-question-circle" style="color:#3b82f6;"></i><span><strong><?php echo number_format($stats['total_faqs']); ?></strong> FAQs</span></div>
    <div class="strip-item"><i class="fas fa-images" style="color:#10b981;"></i><span><strong><?php echo number_format($stats['total_images']); ?></strong> Gallery</span></div>
    <div class="strip-item"><i class="fas fa-video" style="color:#f59e0b;"></i><span><strong><?php echo number_format($stats['total_videos']); ?></strong> Videos</span></div>
    <div class="strip-item"><i class="fas fa-code" style="color:#8b5cf6;"></i><span>PHP <strong><?php echo phpversion(); ?></strong></span></div>
    <div class="strip-item"><i class="fas fa-hdd" style="color:#06b6d4;"></i><span><strong><?php echo round(disk_free_space('/')/1073741824,1); ?> GB</strong> Free</span></div>
    <div class="strip-item"><i class="fas fa-memory" style="color:#ec4899;"></i><span>Memory <strong><?php echo ini_get('memory_limit'); ?></strong></span></div>
</div>
<style>@keyframes pulseDot{0%,100%{opacity:1}50%{opacity:.4}}</style>
<script>
(function() {
    function refreshLiveStats() {
        fetch('api/get-live-visitors.php', { credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(data) {
                if (!data || !data.success) return;
                var s = data.stats;
                var elOnline = document.getElementById('dashActiveNow');
                var elToday  = document.getElementById('dashVisitorsToday');
                var elPV     = document.getElementById('dashPageViews');
                if (elOnline) elOnline.textContent = s.online_now;
                if (elToday)  elToday.textContent  = s.today;
                if (elPV)     elPV.textContent     = s.total_page_views_today + ' page views';
            })
            .catch(function() {});
    }
    document.addEventListener('DOMContentLoaded', function() {
        refreshLiveStats();
        setInterval(refreshLiveStats, 30000);
    });
})();
</script>
    
<!-- Section 1: Quick Actions -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-bolt" style="color:#667eea;margin-right:8px;"></i>Quick Actions</h2>
        <span class="sec-badge" style="margin-left:auto;">12 Shortcuts</span>
    </div>
    <div class="seo-section-body">
        <div class="qa-grid">
            <a href="pages/generate-pages.php" class="qa-card qa-indigo">
                <div class="qa-icon"><i class="fas fa-magic"></i></div>
                <div class="qa-text"><span class="qa-title">Generate Pages</span><span class="qa-sub">Create service + area pages</span></div>
            </a>
            <a href="pages/manage-reviews.php" class="qa-card qa-amber">
                <div class="qa-icon"><i class="fas fa-star"></i></div>
                <div class="qa-text"><span class="qa-title">Approve Reviews</span><span class="qa-sub">Manage customer feedback</span></div>
                <?php if ($stats['pending_reviews']>0): ?><span class="qa-badge"><?php echo $stats['pending_reviews']; ?></span><?php endif; ?>
            </a>
            <a href="pages/manage-blogs.php" class="qa-card qa-green">
                <div class="qa-icon"><i class="fas fa-pen-alt"></i></div>
                <div class="qa-text"><span class="qa-title">Create Blog Post</span><span class="qa-sub">Write &amp; publish articles</span></div>
            </a>
            <a href="pages/gallery-management.php" class="qa-card qa-sky">
                <div class="qa-icon"><i class="fas fa-images"></i></div>
                <div class="qa-text"><span class="qa-title">Gallery &amp; Media</span><span class="qa-sub">Upload photos &amp; images</span></div>
            </a>
            <a href="pages/rate-management.php" class="qa-card qa-rose">
                <div class="qa-icon"><i class="fas fa-rupee-sign"></i></div>
                <div class="qa-text"><span class="qa-title">Update Pricing</span><span class="qa-sub">Service rates &amp; packages</span></div>
            </a>
            <a href="pages/seo-dashboard.php" class="qa-card qa-violet">
                <div class="qa-icon"><i class="fas fa-search"></i></div>
                <div class="qa-text"><span class="qa-title">SEO Tools</span><span class="qa-sub">Rankings &amp; optimization</span></div>
            </a>
            <a href="#" onclick="generateSitemap(); return false;" class="qa-card qa-blue">
                <div class="qa-icon"><i class="fas fa-sitemap"></i></div>
                <div class="qa-text"><span class="qa-title">Generate Sitemap</span><span class="qa-sub">Submit to search engines</span></div>
            </a>
            <a href="pages/visitor-analytics.php" class="qa-card qa-emerald">
                <div class="qa-icon"><i class="fas fa-chart-line"></i></div>
                <div class="qa-text"><span class="qa-title">Analytics</span><span class="qa-sub">Traffic &amp; visitor insights</span></div>
            </a>
            <a href="pages/hero-slider.php" class="qa-card qa-pink">
                <div class="qa-icon"><i class="fas fa-sliders-h"></i></div>
                <div class="qa-text"><span class="qa-title">Hero Slider</span><span class="qa-sub">Manage homepage slides</span></div>
            </a>
            <a href="pages/update-hero-titles.php" class="qa-card qa-orange">
                <div class="qa-icon"><i class="fas fa-font"></i></div>
                <div class="qa-text"><span class="qa-title">Hero Titles</span><span class="qa-sub">Update page headings</span></div>
            </a>
            <a href="pages/live-visitors.php" class="qa-card qa-teal">
                <div class="qa-icon"><i class="fas fa-wifi"></i></div>
                <div class="qa-text"><span class="qa-title">Live Visitors</span><span class="qa-sub"><?php echo number_format($stats['active_now']); ?> active right now</span></div>
            </a>
            <a href="pages/manage-faqs.php" class="qa-card qa-slate">
                <div class="qa-icon"><i class="fas fa-question-circle"></i></div>
                <div class="qa-text"><span class="qa-title">Manage FAQs</span><span class="qa-sub">Questions &amp; answers</span></div>
            </a>
        </div>
    </div>
</div>

<!-- Section 2: SEO & Marketing -->
<?php
    $seo_total_pages = $stats['total_pages'];
    $seo_optimized = 0;
    $seo_sitemap_path = $base_dir . '/sitemap.xml';
    $seo_sitemap_exists = file_exists($seo_sitemap_path);
    $seo_sitemap_urls = 0;
    try {
        if ($db) {
            $sr = $db->fetchOne("SELECT COUNT(DISTINCT page_path) as c FROM seo_optimization_log");
            $seo_optimized = (int)($sr['c'] ?? 0);
        }
    } catch(Exception $e){}
    if ($seo_sitemap_exists) {
        $xml = @simplexml_load_file($seo_sitemap_path);
        $seo_sitemap_urls = $xml ? count($xml->url) : 0;
    }
    $seo_pct = $seo_total_pages > 0 ? round(($seo_optimized / $seo_total_pages) * 100) : 0;
?>
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-search" style="color:#3b82f6;margin-right:8px;"></i>SEO &amp; Marketing</h2>
        <span class="sec-badge blue" style="margin-left:auto;">Live Stats</span>
    </div>
    <div class="seo-section-body">
        <div class="seo-tools-grid">
            <!-- Complete SEO System -->
            <a href="pages/complete-seo-system.php" class="seo-tool-card seo-purple">
                <div class="seo-tool-header">
                    <div class="seo-tool-icon"><i class="fas fa-rocket"></i></div>
                    <span class="seo-tool-badge">All-in-One</span>
                </div>
                <h3>Complete SEO System</h3>
                <p>Whole website optimization + link building strategy</p>
                <div class="seo-tool-stats">
                    <div class="seo-stat"><span class="seo-stat-val"><?php echo number_format($seo_total_pages); ?></span><span class="seo-stat-lbl">Total Pages</span></div>
                    <div class="seo-stat"><span class="seo-stat-val"><?php echo number_format($seo_optimized); ?></span><span class="seo-stat-lbl">Optimized</span></div>
                </div>
                <div class="seo-tool-action">Open Tool <i class="fas fa-arrow-right"></i></div>
            </a>
            <!-- Auto SEO Optimizer -->
            <a href="pages/auto-seo-optimizer-dashboard.php" class="seo-tool-card seo-green">
                <div class="seo-tool-header">
                    <div class="seo-tool-icon"><i class="fas fa-magic"></i></div>
                    <span class="seo-tool-badge">One Click</span>
                </div>
                <h3>Auto SEO Optimizer</h3>
                <p>Push all <?php echo number_format($seo_total_pages); ?> pages to #1 with one click</p>
                <div class="seo-tool-stats">
                    <div class="seo-stat"><span class="seo-stat-val"><?php echo $seo_pct; ?>%</span><span class="seo-stat-lbl">Completion</span></div>
                    <div class="seo-stat"><span class="seo-stat-val"><?php echo number_format(max(0,$seo_total_pages-$seo_optimized)); ?></span><span class="seo-stat-lbl">Pending</span></div>
                </div>
                <div class="seo-tool-action">Optimize Now <i class="fas fa-arrow-right"></i></div>
            </a>
            <!-- SEO Dashboard -->
            <a href="pages/seo-dashboard.php" class="seo-tool-card seo-blue">
                <div class="seo-tool-header">
                    <div class="seo-tool-icon"><i class="fas fa-chart-line"></i></div>
                    <span class="seo-tool-badge">Analytics</span>
                </div>
                <h3>SEO Dashboard</h3>
                <p>Google rankings, search console data &amp; AI recommendations</p>
                <div class="seo-tool-stats">
                    <div class="seo-stat"><span class="seo-stat-val"><?php echo number_format($seo_total_pages); ?></span><span class="seo-stat-lbl">Tracked Pages</span></div>
                    <div class="seo-stat"><span class="seo-stat-val">Live</span><span class="seo-stat-lbl">Rankings</span></div>
                </div>
                <div class="seo-tool-action">View Dashboard <i class="fas fa-arrow-right"></i></div>
            </a>
            <!-- Sitemap Generator -->
            <a href="pages/sitemap-generator.php" class="seo-tool-card seo-orange">
                <div class="seo-tool-header">
                    <div class="seo-tool-icon"><i class="fas fa-sitemap"></i></div>
                    <span class="seo-tool-badge"><?php echo $seo_sitemap_exists ? 'Active' : 'Not Generated'; ?></span>
                </div>
                <h3>Sitemap Generator</h3>
                <p>Generate &amp; submit XML sitemap to search engines</p>
                <div class="seo-tool-stats">
                    <div class="seo-stat"><span class="seo-stat-val"><?php echo number_format($seo_sitemap_urls ?: ($seo_total_pages + 10)); ?></span><span class="seo-stat-lbl">URLs</span></div>
                    <div class="seo-stat"><span class="seo-stat-val"><?php echo $seo_sitemap_exists ? date('d M', filemtime($seo_sitemap_path)) : 'Never'; ?></span><span class="seo-stat-lbl">Last Gen</span></div>
                </div>
                <div class="seo-tool-action">Generate Now <i class="fas fa-arrow-right"></i></div>
            </a>
        </div>
    </div>
</div>

    <!-- Sitemap Generation Modal -->
    <div id="sitemapModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sitemap"></i> Sitemap Generator</h3>
                <span class="close-modal" onclick="closeSitemapModal()">&times;</span>
            </div>
            <div class="modal-body" id="sitemapModalBody">
                <div class="loading-spinner">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Generating sitemap...</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function generateSitemap() {
        const modal = document.getElementById('sitemapModal');
        const modalBody = document.getElementById('sitemapModalBody');
        modal.style.display = 'flex';
        
        modalBody.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i><p>Generating sitemap...</p></div>';
        
        fetch('api/generate-sitemap-simple.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalBody.innerHTML = `
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <h3>Sitemap Generated Successfully!</h3>
                        <div class="sitemap-stats">
                            <p><strong>Total URLs:</strong> ${data.total_urls.toLocaleString()}</p>
                            <p><strong>File Size:</strong> ${(data.file_size / 1024).toFixed(2)} KB</p>
                            <p><strong>Location:</strong> /sitemap.xml</p>
                        </div>
                        <div class="sitemap-details">
                            <p><strong>Included Pages:</strong></p>
                            <ul>
                                <li>Static Pages: ${data.details.static_pages}</li>
                                <li>Blog Posts: ${data.details.blog_posts}</li>
                                <li>Pillar Pages: ${data.details.pillar_pages}</li>
                                <li>Service-Area Pages: ${data.details.service_pages.toLocaleString()}</li>
                            </ul>
                        </div>
                        <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; border-radius: 8px; margin-top: 20px;">
                            <p style="margin: 0; font-size: 14px; color: #92400e;">
                                <strong>📌 Auto-Update:</strong> ${data.note || 'Google Search Console will automatically detect sitemap changes within 24-48 hours.'}
                            </p>
                        </div>
                        <p class="mt-3"><a href="../sitemap.xml" target="_blank" class="btn btn-primary">View Sitemap</a></p>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <h3>Generation Failed</h3>
                        <p>${data.message}</p>
                        <button onclick="closeSitemapModal()" class="btn btn-secondary">Close</button>
                    </div>
                `;
            }
        })
        .catch(error => {
            modalBody.innerHTML = `
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i>
                    <h3>Error</h3>
                    <p>${error.message}</p>
                    <button onclick="closeSitemapModal()" class="btn btn-secondary">Close</button>
                </div>
            `;
        });
    }
    
    function closeSitemapModal() {
        document.getElementById('sitemapModal').style.display = 'none';
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('sitemapModal');
        if (event.target === modal) {
            closeSitemapModal();
        }
    }
    </script>
    
<!-- Section 3: Recent Activity -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">3</div>
        <h2><i class="fas fa-clock" style="color:#10b981;margin-right:8px;"></i>Recent Activity</h2>
    </div>
    <div class="seo-section-body">
        <div class="dash-two-col">
            <!-- Recent Pages -->
            <div>
                <div class="dash-panel-head"><i class="fas fa-file-alt"></i> Recently Generated Pages <a href="pages/manage-pages.php" class="view-all-lnk">View All <i class="fas fa-arrow-right"></i></a></div>
                <?php if (empty($recent_pages)): ?>
                <div class="empty-state"><i class="fas fa-file-alt"></i><p>No pages yet</p><a href="pages/generate-pages.php" class="btn-dash">Generate First Page</a></div>
                <?php else: ?>
                <table class="dash-table">
                    <thead><tr><th>Page</th><th>Area</th><th>Status</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent_pages as $page): ?>
                    <tr>
                        <td><a href="../service-pages/<?php echo $page['page_slug']; ?>.php" target="_blank"><?php echo htmlspecialchars($page['keyword_text']); ?></a></td>
                        <td><?php echo htmlspecialchars($page['area_name']); ?></td>
                        <td><span class="status-badge <?php echo $page['is_published']?'published':'draft'; ?>"><?php echo $page['is_published']?'Published':'Draft'; ?></span></td>
                        <td><?php echo date('M j',strtotime($page['generated_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php endif; ?>
            </div>
            <!-- Pending Reviews -->
            <div>
                <div class="dash-panel-head"><i class="fas fa-star"></i> Pending Reviews <?php if ($stats['pending_reviews']>0): ?><span class="count-badge"><?php echo $stats['pending_reviews']; ?></span><?php endif; ?> <a href="pages/manage-reviews.php" class="view-all-lnk">View All <i class="fas fa-arrow-right"></i></a></div>
                <?php if (empty($pending_reviews)): ?>
                <div class="empty-state"><i class="fas fa-check-circle"></i><p>No pending reviews</p></div>
                <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($pending_reviews as $review): ?>
                    <div class="review-item">
                        <div class="review-header"><strong><?php echo htmlspecialchars($review['customer_name']); ?></strong><div class="rating"><?php for($i=1;$i<=5;$i++) echo '<i class="fas fa-star'.($i<=$review['rating']?'':'-o').'"></i>'; ?></div></div>
                        <p class="review-text"><?php echo htmlspecialchars(substr($review['review_text'],0,100)); ?>...</p>
                        <div class="review-meta"><span><i class="fas fa-calendar"></i> <?php echo date('M j, Y',strtotime($review['submitted_at'])); ?></span><?php if ($review['service_name']): ?><span><i class="fas fa-tag"></i> <?php echo htmlspecialchars($review['service_name']); ?></span><?php endif; ?></div>
                        <a href="pages/manage-reviews.php?tab=pending" class="btn-dash btn-green"><i class="fas fa-check"></i> Review</a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Tip Bar -->
<div class="tip-bar">
    <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
    <div><strong>Dashboard Tips:</strong> Run <em>Auto SEO Optimizer</em> weekly &mdash; Check <em>Pending Reviews</em> daily &mdash; <em>Regenerate Sitemap</em> after adding new pages &mdash; Use <em>Live Visitors</em> to track real-time traffic.</div>
</div>

</div>
<script>
(function clockTick(){
    var n=new Date(),h=n.getHours(),m=n.getMinutes(),ap=h>=12?'PM':'AM';
    h=h%12||12;
    var t=document.querySelector('#dashClock .dc-time'),a=document.querySelector('#dashClock .dc-ampm');
    if(t)t.textContent=h+':'+String(m).padStart(2,'0');
    if(a)a.textContent=ap;
    setTimeout(clockTick,1000);
})();
</script>

<style>
/* ===== DASHBOARD — Complete SEO System Theme ===== */
@keyframes fadeInUp  { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes spinLoad  { from{transform:rotate(0)} to{transform:rotate(360deg)} }

/* ── Page Container ── */
.seo-page { padding: 0; }

/* ── Dashboard Hero ── */
.dash-hero { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); border-radius: 20px; padding: 30px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 24px; box-shadow: 0 8px 32px rgba(102,126,234,.35); animation: fadeInUp .4s ease both; overflow: hidden; position: relative; }
.dash-hero::before { content:''; position:absolute; top:-40px; right:-40px; width:200px; height:200px; background:rgba(255,255,255,.07); border-radius:50%; pointer-events:none; }
.dash-hero::after  { content:''; position:absolute; bottom:-50px; right:160px; width:130px; height:130px; background:rgba(255,255,255,.05); border-radius:50%; pointer-events:none; }
.dash-hero-content { position:relative; z-index:1; }
.dash-greet-chip { display:inline-flex; align-items:center; gap:6px; background:rgba(255,255,255,.2); color:#fff; padding:5px 14px; border-radius:20px; font-size:12px; font-weight:600; margin-bottom:10px; }
.dash-hero h1 { font-size:32px; font-weight:800; color:#fff; margin:0 0 6px; display:flex; align-items:center; gap:10px; }
.dash-hero p { color:rgba(255,255,255,.8); font-size:14px; margin:0; }
.dash-hero p strong { color:#fff; }
.dash-hero-right { display:flex; flex-direction:column; align-items:center; gap:12px; flex-shrink:0; position:relative; z-index:1; }
.dash-live-clock { text-align:center; background:rgba(255,255,255,.15); border-radius:16px; padding:12px 22px; }
.dc-time { font-size:30px; font-weight:800; color:#fff; line-height:1; letter-spacing:2px; }
.dc-ampm { font-size:11px; font-weight:700; color:rgba(255,255,255,.7); text-align:center; margin-top:3px; letter-spacing:2px; }
.dash-admin-badge { background:rgba(255,255,255,.2); color:#fff; padding:7px 16px; border-radius:20px; font-size:12px; font-weight:700; border:1px solid rgba(255,255,255,.3); display:flex; align-items:center; gap:6px; }

/* ── Solution banner ── */
.solution-banner { background: linear-gradient(135deg, rgba(40,167,69,.08) 0%, rgba(32,201,151,.05) 100%); border: 1px solid rgba(40,167,69,.2); border-left: 5px solid #28a745; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#28a745,#20c997); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #166534; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #15803d; font-size: 13.5px; margin: 0; line-height: 1.6; }

/* ── Stats Grid — unified block with dividers ── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white !important; padding: 26px 22px !important; display: flex !important; align-items: center !important; gap: 16px !important; text-align: left !important; border: none !important; box-shadow: none !important; border-radius: 0 !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; transform: none !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; min-width:52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; }
.stat-card .sub { font-size: 11px !important; color: #94a3b8 !important; margin: 3px 0 0 !important; }

/* ── Secondary Strip ── */
.dash-strip { background:#fff; border-radius:14px; padding:14px 22px; margin-bottom:24px; display:flex; gap:0; flex-wrap:wrap; box-shadow:0 4px 20px rgba(0,0,0,.07); }
.strip-item { display:flex; align-items:center; gap:8px; padding:6px 18px; font-size:13px; color:#475569; border-right:1px solid #f1f5f9; }
.strip-item:last-child { border-right:none; }
.strip-item strong { color:#1e293b; }

/* ── SEO Section Cards ── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; animation:fadeInUp .5s ease both; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; display:flex; align-items:center; }
.sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-badge { font-size:11px; font-weight:700; padding:4px 12px; border-radius:20px; background:#f0fdf4; color:#059669; border:1px solid #bbf7d0; }
.sec-badge.blue { background:#eff6ff; color:#2563eb; border-color:#bfdbfe; }
.seo-section-body { padding: 28px 32px; }

/* ── Quick Actions Grid ── */
.qa-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
.qa-card { background:#fff; border-radius:14px; padding:18px 20px; display:flex; align-items:center; gap:16px; text-decoration:none; border:1.5px solid #f1f5f9; transition:all .25s; position:relative; }
.qa-card:hover { transform:translateY(-3px); text-decoration:none; }
.qa-icon { width:52px; height:52px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; transition:all .25s; }
.qa-text { flex:1; min-width:0; }
.qa-title { display:block; font-size:13.5px; font-weight:700; color:#1e293b; margin-bottom:3px; }
.qa-sub { display:block; font-size:11.5px; color:#94a3b8; line-height:1.3; }
.qa-badge { position:absolute; top:8px; right:10px; background:#ef4444; color:#fff; font-size:10px; font-weight:700; padding:2px 7px; border-radius:10px; }
.qa-indigo { border-color:#e0e7ff; } .qa-indigo .qa-icon { background:#eef2ff; color:#6366f1; } .qa-indigo:hover { background:#eef2ff; border-color:#6366f1; box-shadow:0 8px 24px rgba(99,102,241,.15); } .qa-indigo:hover .qa-icon { background:#6366f1; color:#fff; }
.qa-amber  { border-color:#fef3c7; } .qa-amber .qa-icon  { background:#fffbeb; color:#f59e0b; } .qa-amber:hover  { background:#fffbeb; border-color:#f59e0b; box-shadow:0 8px 24px rgba(245,158,11,.15);  } .qa-amber:hover .qa-icon  { background:#f59e0b; color:#fff; }
.qa-green  { border-color:#dcfce7; } .qa-green .qa-icon  { background:#f0fdf4; color:#22c55e; } .qa-green:hover  { background:#f0fdf4; border-color:#22c55e; box-shadow:0 8px 24px rgba(34,197,94,.15);   } .qa-green:hover .qa-icon  { background:#22c55e; color:#fff; }
.qa-sky    { border-color:#e0f2fe; } .qa-sky .qa-icon    { background:#f0f9ff; color:#0ea5e9; } .qa-sky:hover    { background:#f0f9ff; border-color:#0ea5e9; box-shadow:0 8px 24px rgba(14,165,233,.15);  } .qa-sky:hover .qa-icon    { background:#0ea5e9; color:#fff; }
.qa-rose   { border-color:#ffe4e6; } .qa-rose .qa-icon   { background:#fff1f2; color:#f43f5e; } .qa-rose:hover   { background:#fff1f2; border-color:#f43f5e; box-shadow:0 8px 24px rgba(244,63,94,.15);   } .qa-rose:hover .qa-icon   { background:#f43f5e; color:#fff; }
.qa-violet { border-color:#ede9fe; } .qa-violet .qa-icon { background:#f5f3ff; color:#8b5cf6; } .qa-violet:hover { background:#f5f3ff; border-color:#8b5cf6; box-shadow:0 8px 24px rgba(139,92,246,.15);  } .qa-violet:hover .qa-icon { background:#8b5cf6; color:#fff; }
.qa-blue   { border-color:#dbeafe; } .qa-blue .qa-icon   { background:#eff6ff; color:#3b82f6; } .qa-blue:hover   { background:#eff6ff; border-color:#3b82f6; box-shadow:0 8px 24px rgba(59,130,246,.15);  } .qa-blue:hover .qa-icon   { background:#3b82f6; color:#fff; }
.qa-emerald{ border-color:#d1fae5; } .qa-emerald .qa-icon{ background:#ecfdf5; color:#10b981; } .qa-emerald:hover{ background:#ecfdf5; border-color:#10b981; box-shadow:0 8px 24px rgba(16,185,129,.15); } .qa-emerald:hover .qa-icon{ background:#10b981; color:#fff; }
.qa-pink   { border-color:#fce7f3; } .qa-pink .qa-icon   { background:#fdf2f8; color:#ec4899; } .qa-pink:hover   { background:#fdf2f8; border-color:#ec4899; box-shadow:0 8px 24px rgba(236,72,153,.15);  } .qa-pink:hover .qa-icon   { background:#ec4899; color:#fff; }
.qa-orange { border-color:#ffedd5; } .qa-orange .qa-icon { background:#fff7ed; color:#f97316; } .qa-orange:hover { background:#fff7ed; border-color:#f97316; box-shadow:0 8px 24px rgba(249,115,22,.15);  } .qa-orange:hover .qa-icon { background:#f97316; color:#fff; }
.qa-teal   { border-color:#ccfbf1; } .qa-teal .qa-icon   { background:#f0fdfa; color:#14b8a6; } .qa-teal:hover   { background:#f0fdfa; border-color:#14b8a6; box-shadow:0 8px 24px rgba(20,184,166,.15);  } .qa-teal:hover .qa-icon   { background:#14b8a6; color:#fff; }
.qa-slate  { border-color:#e2e8f0; } .qa-slate .qa-icon  { background:#f8fafc; color:#64748b; } .qa-slate:hover  { background:#f8fafc; border-color:#64748b; box-shadow:0 8px 24px rgba(100,116,139,.15); } .qa-slate:hover .qa-icon  { background:#64748b; color:#fff; }

/* ── SEO Tools Grid ── */
.seo-tools-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:20px; }
.seo-tool-card { display:flex; flex-direction:column; background:#fafbff; border-radius:16px; padding:22px; text-decoration:none; border:1px solid #e8edff; border-top:4px solid transparent; transition:all .25s; }
.seo-tool-card:hover { transform:translateY(-5px); box-shadow:0 14px 36px rgba(0,0,0,.1); text-decoration:none; background:#fff; }
.seo-purple { border-top-color:#764ba2; }
.seo-green  { border-top-color:#10b981; }
.seo-blue   { border-top-color:#3b82f6; }
.seo-orange { border-top-color:#f59e0b; }
.seo-tool-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
.seo-tool-icon { width:46px; height:46px; border-radius:13px; display:flex; align-items:center; justify-content:center; font-size:20px; color:#fff; }
.seo-purple .seo-tool-icon { background:linear-gradient(135deg,#667eea,#764ba2); }
.seo-green  .seo-tool-icon { background:linear-gradient(135deg,#10b981,#059669); }
.seo-blue   .seo-tool-icon { background:linear-gradient(135deg,#3b82f6,#2563eb); }
.seo-orange .seo-tool-icon { background:linear-gradient(135deg,#f59e0b,#d97706); }
.seo-tool-badge { font-size:10px; font-weight:700; padding:3px 8px; border-radius:20px; text-transform:uppercase; }
.seo-purple .seo-tool-badge { background:#f3f0ff; color:#764ba2; }
.seo-green  .seo-tool-badge { background:#d1fae5; color:#059669; }
.seo-blue   .seo-tool-badge { background:#dbeafe; color:#2563eb; }
.seo-orange .seo-tool-badge { background:#fef3c7; color:#d97706; }
.seo-tool-card h3 { font-size:15px; font-weight:700; color:#1e293b; margin:0 0 5px; }
.seo-tool-card p  { font-size:13px; color:#64748b; margin:0 0 16px; line-height:1.5; flex:1; }
.seo-tool-stats { display:flex; gap:18px; padding:12px 0; border-top:1px solid #f1f5f9; border-bottom:1px solid #f1f5f9; margin-bottom:12px; }
.seo-stat { display:flex; flex-direction:column; gap:2px; }
.seo-stat-val { font-size:20px; font-weight:800; color:#1e293b; }
.seo-stat-lbl { font-size:10px; font-weight:600; color:#94a3b8; text-transform:uppercase; }
.seo-tool-action { font-size:13px; font-weight:700; display:flex; align-items:center; gap:5px; }
.seo-purple .seo-tool-action { color:#764ba2; }
.seo-green  .seo-tool-action { color:#059669; }
.seo-blue   .seo-tool-action { color:#2563eb; }
.seo-orange .seo-tool-action { color:#d97706; }

/* ── Recent Activity ── */
.dash-two-col { display:grid; grid-template-columns:1fr 1fr; gap:24px; }
.dash-panel-head { font-size:14px; font-weight:700; color:#1e293b; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
.dash-panel-head i { color:#667eea; }
.view-all-lnk { margin-left:auto; font-size:12px; font-weight:600; color:#667eea; text-decoration:none; display:flex; align-items:center; gap:4px; }
.view-all-lnk:hover { color:#764ba2; text-decoration:none; }
.count-badge { background:#ef4444; color:#fff; font-size:10px; padding:2px 7px; border-radius:10px; font-weight:700; }
.dash-table { width:100%; border-collapse:collapse; font-size:13px; }
.dash-table th { background:#f8faff; color:#64748b; font-weight:700; font-size:11px; text-transform:uppercase; padding:8px 10px; text-align:left; border-bottom:1px solid #e8edff; }
.dash-table td { padding:9px 10px; border-bottom:1px solid #f8faff; color:#374151; vertical-align:middle; }
.dash-table tr:last-child td { border-bottom:none; }
.dash-table a { color:#667eea; text-decoration:none; font-weight:600; }
.dash-table a:hover { color:#764ba2; }
.status-badge { font-size:10px; font-weight:700; padding:3px 8px; border-radius:10px; }
.status-badge.published { background:#d1fae5; color:#059669; }
.status-badge.draft { background:#fef3c7; color:#d97706; }
.empty-state { text-align:center; padding:36px 20px; }
.empty-state i { font-size:40px; color:#c7d2fe; margin-bottom:10px; display:block; }
.empty-state p { color:#94a3b8; font-size:14px; margin:0 0 14px; }
.reviews-list { display:flex; flex-direction:column; gap:12px; }
.review-item { background:#f8faff; border-radius:12px; padding:14px; border:1px solid #e8edff; }
.review-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:6px; }
.review-header strong { font-size:13px; color:#1e293b; }
.rating i { color:#f59e0b; font-size:11px; }
.review-text { font-size:12px; color:#64748b; margin:0 0 8px; line-height:1.5; }
.review-meta { display:flex; gap:12px; font-size:11px; color:#94a3b8; margin-bottom:10px; }
.review-meta i { margin-right:3px; }
.btn-dash { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; border-radius:8px; font-size:12px; font-weight:700; text-decoration:none; transition:opacity .2s; }
.btn-dash:hover { opacity:.88; text-decoration:none; color:#fff; }
.btn-dash.btn-green { background:linear-gradient(135deg,#10b981,#059669); }

/* ── Tip Bar ── */
.tip-bar { background:linear-gradient(135deg,#fefce8,#fef9c3); border:1px solid #fde68a; border-radius:14px; padding:16px 22px; margin-bottom:22px; display:flex; align-items:flex-start; gap:14px; font-size:13px; color:#78350f; line-height:1.6; }
.tip-icon { width:36px; height:36px; background:linear-gradient(135deg,#f59e0b,#d97706); border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:16px; flex-shrink:0; }
.tip-bar strong { color:#92400e; }
.tip-bar em { font-style:normal; font-weight:700; color:#92400e; }

/* ── Buttons ── */
.btn { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border:none; border-radius:10px; font-size:14px; font-weight:700; cursor:pointer; transition:all .25s; text-decoration:none; }
.btn-primary { background:linear-gradient(135deg,#667eea,#764ba2); color:#fff; box-shadow:0 4px 14px rgba(102,126,234,.4); }
.btn-primary:hover { opacity:.9; transform:translateY(-2px); }
.btn-secondary { background:linear-gradient(135deg,#64748b,#475569); color:#fff; }

/* ── Modal ── */
.modal { display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; background:rgba(15,23,42,.7); backdrop-filter:blur(6px); align-items:center; justify-content:center; }
.modal-content { background:#fff; border-radius:20px; max-width:600px; width:90%; box-shadow:0 24px 80px rgba(0,0,0,.25); animation:fadeInUp .3s ease; }
.modal-header { background:linear-gradient(135deg,#667eea,#764ba2); padding:22px 26px; border-radius:20px 20px 0 0; display:flex; justify-content:space-between; align-items:center; }
.modal-header h3 { margin:0; color:#fff; font-size:18px; font-weight:700; display:flex; align-items:center; gap:10px; }
.close-modal { color:#fff; font-size:26px; cursor:pointer; transition:transform .3s; }
.close-modal:hover { transform:rotate(90deg); }
.modal-body { padding:28px; }
.loading-spinner { text-align:center; padding:40px; }
.loading-spinner i { font-size:44px; color:#667eea; animation:spinLoad 1s linear infinite; }
.loading-spinner p { margin-top:14px; color:#64748b; }
.success-message,.error-message { text-align:center; }
.success-message i { font-size:52px; color:#10b981; margin-bottom:12px; }
.error-message i   { font-size:52px; color:#ef4444; margin-bottom:12px; }
.success-message h3,.error-message h3 { font-size:20px; margin:0 0 12px; color:#1e293b; }
.sitemap-stats,.sitemap-details { background:#f8fafc; padding:16px; border-radius:10px; margin:16px 0; text-align:left; }
.sitemap-details ul { list-style:none; padding:0; margin:8px 0 0; }
.sitemap-details li { padding:6px 0; color:#64748b; border-bottom:1px solid #e2e8f0; }
.sitemap-details li:last-child { border-bottom:none; }

/* ── Responsive ── */
@media(max-width:1200px) {
    .qa-grid { grid-template-columns:repeat(3,1fr); }
}
@media(max-width:960px) {
    .stats-grid { grid-template-columns:repeat(2,1fr) !important; gap:12px !important; }
    .stat-card + .stat-card::before { display:none !important; }
    .stat-card { border:1px solid #f1f5f9 !important; border-radius:14px !important; }
    .seo-tools-grid { grid-template-columns:repeat(2,1fr); }
    .dash-two-col { grid-template-columns:1fr; }
    .qa-grid { grid-template-columns:repeat(2,1fr); }
}
@media(max-width:768px) {
    .dash-hero { flex-direction:column; gap:16px; align-items:flex-start; padding:22px 20px; }
    .dash-hero h1 { font-size:24px; }
    .dash-live-clock { display:none; }
    .dash-admin-badge { display:none; }
    .dash-strip { flex-wrap:wrap; gap:0; padding:10px 14px; }
    .strip-item { padding:5px 12px; font-size:12px; }
    .seo-section-head { padding:14px 16px; }
    .seo-section-body { padding:16px; }
    .dash-two-col { grid-template-columns:1fr; gap:16px; }
    .solution-banner { padding:14px 16px; }
    .tip-bar { padding:12px 14px; font-size:12px; }
}
@media(max-width:560px) {
    .qa-grid { grid-template-columns:1fr 1fr; gap:10px; }
    .qa-card { padding:14px 12px; gap:12px; }
    .qa-icon { width:44px; height:44px; font-size:18px; }
    .qa-title { font-size:12.5px; }
    .qa-sub { font-size:11px; }
    .seo-tools-grid { grid-template-columns:1fr; }
    .stats-grid { grid-template-columns:1fr 1fr !important; gap:10px !important; }
    .stat-card { padding:16px 12px !important; }
    .stat-icon-wrap { width:44px !important; height:44px !important; font-size:18px !important; }
    .stat-card .value { font-size:1.6rem !important; }
}
@media(max-width:480px) {
    .dash-hero { padding:18px 16px; border-radius:16px; margin-bottom:16px; }
    .dash-hero h1 { font-size:22px; gap:8px; }
    .dash-greet-chip { font-size:11px; padding:4px 10px; margin-bottom:8px; }
    .dash-hero p { font-size:13px; }
    .qa-grid { grid-template-columns:1fr 1fr; gap:8px; }
    .qa-card { padding:12px 10px; gap:10px; border-radius:12px; }
    .qa-icon { width:40px; height:40px; font-size:17px; border-radius:11px; }
    .qa-title { font-size:12px; }
    .stats-grid { grid-template-columns:1fr 1fr !important; gap:8px !important; }
    .stat-card { padding:14px 10px !important; }
    .stat-icon-wrap { width:40px !important; height:40px !important; font-size:17px !important; }
    .stat-card .value { font-size:1.4rem !important; }
    .stat-card h3 { font-size:9px !important; }
    .solution-banner { flex-direction:column; gap:10px; padding:14px; }
    .seo-section-head { padding:12px 14px; }
    .seo-section-body { padding:14px; }
    .seo-section-head h2 { font-size:15px; }
    .tip-bar { padding:10px 12px; font-size:12px; border-radius:12px; }
}
@media(max-width:360px) {
    .dash-hero { padding:14px 12px; }
    .dash-hero h1 { font-size:20px; }
    .qa-grid { grid-template-columns:1fr; gap:8px; }
    .stats-grid { grid-template-columns:1fr !important; }
    .stat-card + .stat-card::before { display:none !important; }
    .stat-card { padding:14px !important; flex-direction:row !important; border:1px solid #f1f5f9 !important; border-radius:14px !important; }
    .seo-tools-grid { grid-template-columns:1fr; gap:12px; }
}
</style>

<?php include 'includes/footer.php'; ?>
