<?php
/**
 * Advanced Analytics Dashboard
 * Comprehensive analytics with charts and insights
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

$page_title = 'Advanced Analytics';
include '../includes/header.php';

// ── Real analytics data from visitor_tracking ─────────
// Helper functions for direct mysqli queries (mirrors visitor-analytics.php approach)
if (!function_exists('_aa_one')) {
    function _aa_one($c, $sql) { $r = @$c->query($sql); return ($r && $r !== true) ? $r->fetch_assoc() : null; }
    function _aa_all($c, $sql) { $r = @$c->query($sql); return ($r && $r !== true) ? $r->fetch_all(MYSQLI_ASSOC) : []; }
}
$adv_opts  = [10, 25, 50, 100, 250, 500, 1000, 5000];
$adv_pp    = 25;
$adv_pg    = 1;
$adv_total = 0;
$adv_pages = 1;
$adv_off   = 0;
$visitor_log_adv = [];
$today_visitors = 0; $yesterday_visitors = 0; $today_page_views = 0;
$yest_page_views = 0; $avg_session = '0:00'; $bounce_rate = 0;
$real_top_pages = []; $p_search = 0; $p_social = 0; $p_direct = 0; $p_referral = 0;
$daily_labels = []; $daily_visitors_chart = []; $daily_views_chart = [];
$dev_mobile = 0; $dev_desktop = 0; $dev_tablet = 0; $dev_total = 1;
$hourly_data = array_fill(0, 24, 0);
$hourly_labels = []; for ($h=0;$h<24;$h++) $hourly_labels[] = str_pad($h,2,'0',STR_PAD_LEFT).':00';
try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn_adv = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn_adv->connect_error) throw new \Exception($conn_adv->connect_error);
    $conn_adv->set_charset('utf8mb4');
    // Auto-create tables silently
    @$conn_adv->query("CREATE TABLE IF NOT EXISTS visitor_tracking (
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
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_session (session_id), KEY idx_first_visit (first_visit), KEY idx_is_online (is_online)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    @$conn_adv->query("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY, session_id VARCHAR(64),
        page_url VARCHAR(500), `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_session (session_id), KEY idx_timestamp (`timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Today / yesterday visitors
    $today_visitors     = (int)(_aa_one($conn_adv, "SELECT COUNT(DISTINCT session_id) AS c FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()")['c'] ?? 0);
    $yesterday_visitors = (int)(_aa_one($conn_adv, "SELECT COUNT(DISTINCT session_id) AS c FROM visitor_tracking WHERE DATE(first_visit) = DATE_SUB(CURDATE(),INTERVAL 1 DAY)")['c'] ?? 0);

    // Today's page views — prefer page_views table, fall back to sum column
    $today_page_views = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM page_views WHERE DATE(`timestamp`) = CURDATE()")['c'] ?? 0);
    if ($today_page_views === 0) {
        $today_page_views = (int)(_aa_one($conn_adv, "SELECT COALESCE(SUM(page_views),0) AS c FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()")['c'] ?? 0);
    }
    $yest_page_views = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM page_views WHERE DATE(`timestamp`) = DATE_SUB(CURDATE(),INTERVAL 1 DAY)")['c'] ?? 0);

    // Avg session (all visitors with time data)
    $avg_sec      = (int)(_aa_one($conn_adv, "SELECT COALESCE(AVG(total_time_spent),0) AS a FROM visitor_tracking WHERE total_time_spent > 0")['a'] ?? 0);
    $avg_session  = $avg_sec >= 60 ? floor($avg_sec/60).':'.str_pad($avg_sec%60,2,'0',STR_PAD_LEFT) : '0:'.str_pad($avg_sec,2,'0',STR_PAD_LEFT);

    // Bounce rate (sessions with only 1 page view today)
    $single_pg   = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE DATE(first_visit)=CURDATE() AND page_views=1")['c'] ?? 0);
    $bounce_rate = $today_visitors > 0 ? min(100, round($single_pg / max($today_visitors,1) * 100)) : 0;

    // Real top pages (last 30 days from page_views table)
    $real_top_pages = _aa_all($conn_adv, "
        SELECT page_url, COUNT(*) AS views, COUNT(DISTINCT session_id) AS uniq
        FROM page_views
        WHERE `timestamp` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY page_url ORDER BY views DESC LIMIT 5
    ");
    if (empty($real_top_pages)) {
        $real_top_pages = _aa_all($conn_adv, "
            SELECT current_page AS page_url, SUM(page_views) AS views, COUNT(DISTINCT session_id) AS uniq
            FROM visitor_tracking
            WHERE first_visit >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND current_page IS NOT NULL AND current_page != ''
            GROUP BY current_page ORDER BY views DESC LIMIT 5
        ");
    }

    // Real traffic sources from referrer (last 30 days)
    $total_30d   = max(1, (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY)")['c'] ?? 1));
    $src_search  = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND (referrer LIKE '%google%' OR referrer LIKE '%bing%' OR referrer LIKE '%yahoo%' OR referrer LIKE '%duckduckgo%')")['c'] ?? 0);
    $src_social  = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND (referrer LIKE '%facebook%' OR referrer LIKE '%twitter%' OR referrer LIKE '%instagram%' OR referrer LIKE '%whatsapp%' OR referrer LIKE '%youtube%')")['c'] ?? 0);
    $src_direct  = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND (referrer IS NULL OR referrer='')")['c'] ?? 0);
    $src_referral = max(0, $total_30d - $src_search - $src_social - $src_direct);
    $p_search    = round($src_search   / $total_30d * 100, 1);
    $p_social    = round($src_social   / $total_30d * 100, 1);
    $p_direct    = round($src_direct   / $total_30d * 100, 1);
    $p_referral  = max(0, round(100 - $p_search - $p_social - $p_direct, 1));

    // Real 30-day daily data for chart (2 single queries, not 60)
    $chart_v_rows = _aa_all($conn_adv, "SELECT DATE(first_visit) AS d, COUNT(DISTINCT session_id) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY DATE(first_visit)");
    $chart_p_rows = _aa_all($conn_adv, "SELECT DATE(`timestamp`) AS d, COUNT(*) AS c FROM page_views WHERE `timestamp` >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) GROUP BY DATE(`timestamp`)");
    $cv = []; foreach ($chart_v_rows as $r) $cv[$r['d']] = $r['c'];
    $cp = []; foreach ($chart_p_rows as $r) $cp[$r['d']] = $r['c'];
    $daily_labels = []; $daily_visitors_chart = []; $daily_views_chart = [];
    for ($i = 29; $i >= 0; $i--) {
        $d = date('Y-m-d', strtotime("-$i days"));
        $daily_labels[]          = date('M j', strtotime($d));
        $daily_visitors_chart[]  = $cv[$d] ?? 0;
        $daily_views_chart[]     = $cp[$d] ?? 0;
    }

    // Recent visitor log (last 48 hours) — paginated
    $adv_pp    = in_array((int)($_GET['per_page'] ?? 25), $adv_opts) ? (int)($_GET['per_page'] ?? 25) : 25;
    $adv_pg    = max(1, (int)($_GET['page'] ?? 1));
    $adv_total = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(NOW(), INTERVAL 48 HOUR)")['c'] ?? 0);
    $adv_pages = max(1, (int)ceil($adv_total / $adv_pp));
    $adv_pg    = min($adv_pg, $adv_pages);
    $adv_off   = ($adv_pg - 1) * $adv_pp;
    $visitor_log_adv = _aa_all($conn_adv, "
        SELECT ip_address, city, region, country, device_type, browser,
               entry_page, exit_page, current_page, search_keyword,
               first_visit, last_activity, total_time_spent, page_views
        FROM visitor_tracking
        WHERE first_visit >= DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ORDER BY first_visit DESC LIMIT {$adv_pp} OFFSET {$adv_off}
    ") ?: [];

    // Hourly traffic today
    $hourly_rows = _aa_all($conn_adv, "
        SELECT HOUR(first_visit) AS hr, COUNT(DISTINCT session_id) AS visitors, COUNT(*) AS sessions
        FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()
        GROUP BY HOUR(first_visit) ORDER BY hr
    ") ?: [];
    $hourly_data = array_fill(0, 24, 0); $hourly_labels = [];
    for ($h=0;$h<24;$h++) $hourly_labels[] = str_pad($h,2,'0',STR_PAD_LEFT).':00';
    foreach ($hourly_rows as $hr) $hourly_data[(int)$hr['hr']] = (int)$hr['visitors'];

    // Device breakdown (last 30 days)
    $dev_mobile  = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND device_type='mobile'")['c'] ?? 0);
    $dev_desktop = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND device_type='desktop'")['c'] ?? 0);
    $dev_tablet  = (int)(_aa_one($conn_adv, "SELECT COUNT(*) AS c FROM visitor_tracking WHERE first_visit >= DATE_SUB(CURDATE(),INTERVAL 30 DAY) AND device_type='tablet'")['c'] ?? 0);
    $dev_total   = max(1, $dev_mobile + $dev_desktop + $dev_tablet);

} catch (\Throwable $e) {
    $today_visitors = 0; $yesterday_visitors = 0; $today_page_views = 0;
    $yest_page_views = 0; $avg_session = '0:00'; $bounce_rate = 0;
    $real_top_pages = []; $p_search = 0; $p_social = 0; $p_direct = 0; $p_referral = 0;
    $daily_labels = []; $daily_visitors_chart = []; $daily_views_chart = [];
    $dev_mobile = 0; $dev_desktop = 0; $dev_tablet = 0; $dev_total = 1;
    $visitor_log_adv = []; $hourly_data = array_fill(0, 24, 0);
    $hourly_labels = []; for ($h=0;$h<24;$h++) $hourly_labels[] = str_pad($h,2,'0',STR_PAD_LEFT).':00';
}
$pct_change      = $yesterday_visitors > 0 ? round(($today_visitors - $yesterday_visitors) / $yesterday_visitors * 100, 1) : 0;
$views_pct_change = $yest_page_views   > 0 ? round(($today_page_views - $yest_page_views) / $yest_page_views * 100, 1) : 0;
?>

<style>
/* ── Advanced Analytics — lv- theme ────────────────────── */
@keyframes lv-blink{0%,100%{opacity:1;}50%{opacity:.25;}}
.lv-hero{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:28px 32px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-left:6px solid #3b82f6;}
.lv-hero h1{font-size:26px;font-weight:800;color:#1e293b;margin:0 0 4px;}
.lv-hero p{color:#64748b;font-size:14px;margin:0;}
.lv-badge{background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:12px;padding:12px 18px;text-align:center;flex-shrink:0;}
.lv-badge-num{font-size:28px;font-weight:800;color:#1d4ed8;}
.lv-badge-lbl{font-size:11px;color:#2563eb;font-weight:600;}
.lv-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;margin-bottom:20px;}
.lv-stat{padding:22px 20px;text-align:center;position:relative;}
.lv-stat+.lv-stat::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:1px;background:#e2e8f0;}
.lv-stat-val{font-size:34px;font-weight:800;color:#1e293b;}
.lv-stat-lbl{font-size:12px;color:#64748b;font-weight:600;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:5px;}
.lv-stat-chg{font-size:11px;font-weight:700;margin-top:3px;}
.lv-stat-chg.up{color:#10b981;}.lv-stat-chg.down{color:#ef4444;}.lv-stat-chg.neutral{color:#94a3b8;}
.lv-stat.blue .lv-stat-val{color:#3b82f6;}.lv-stat.green .lv-stat-val{color:#10b981;}.lv-stat.amber .lv-stat-val{color:#f59e0b;}.lv-stat.indigo .lv-stat-val{color:#6366f1;}
.lv-card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;}
.lv-card-hdr{padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;}
.lv-card-hdr h2{font-size:16px;font-weight:800;color:#1e293b;margin:0;}
.lv-card-hdr h2 i{color:#3b82f6;margin-right:8px;}
.lv-card-body{padding:16px 22px;}
.lv-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px;}
@media(max-width:900px){.lv-grid{grid-template-columns:1fr;}.lv-stats{grid-template-columns:repeat(2,1fr);}}
/* Source rows */
.lv-src-row{padding:12px 14px;background:#f8fafc;border-radius:10px;margin-bottom:8px;transition:all .2s;}
.lv-src-row:hover{background:#f1f5f9;transform:translateX(3px);}
.lv-src-top{display:flex;align-items:center;gap:10px;margin-bottom:6px;}
.lv-src-icon{width:34px;height:34px;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:15px;flex-shrink:0;}
.lv-src-name{font-weight:700;font-size:13px;color:#1e293b;flex:1;}
.lv-src-pct{font-size:13px;font-weight:800;color:#1e293b;}
.lv-src-bar{height:5px;background:#e2e8f0;border-radius:3px;overflow:hidden;}
.lv-src-fill{height:100%;border-radius:3px;transition:width 1s ease;}
/* Top pages */
.lv-page-row{display:flex;align-items:center;gap:10px;padding:10px 12px;background:#f8fafc;border-radius:8px;margin-bottom:6px;transition:all .2s;}
.lv-page-row:hover{background:#f1f5f9;}
.lv-page-num{width:24px;height:24px;border-radius:50%;background:#dbeafe;color:#1d4ed8;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.lv-page-url{flex:1;font-size:13px;font-weight:600;color:#1e293b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.lv-page-cnt{font-size:12px;color:#64748b;flex-shrink:0;}
/* Device bars */
.lv-dev-row{margin-bottom:14px;}
.lv-dev-top{display:flex;justify-content:space-between;font-size:13px;margin-bottom:5px;}
.lv-dev-name{font-weight:700;color:#1e293b;}
.lv-dev-cnt{color:#64748b;}
.lv-dev-bar{height:8px;background:#e2e8f0;border-radius:4px;overflow:hidden;}
.lv-dev-fill{height:100%;border-radius:4px;}
.lv-actions{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.lv-btn{padding:10px 20px;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:opacity .2s;text-decoration:none;}
.lv-btn:hover{opacity:.85;}
.lv-btn-refresh{background:#3b82f6;color:#fff;}
.lv-empty{text-align:center;padding:40px 20px;color:#94a3b8;}
.lv-empty i{font-size:32px;display:block;margin-bottom:10px;}
.lv-legend{display:flex;gap:16px;align-items:center;}
.lv-legend span{display:flex;align-items:center;gap:5px;font-size:12px;color:#64748b;}
.lv-dot{width:10px;height:10px;border-radius:50%;display:inline-block;}
.lv-explain{background:#eff6ff;border:1px solid #bfdbfe;border-left:5px solid #3b82f6;border-radius:12px;padding:14px 18px;margin-bottom:20px;font-size:13px;color:#1e40af;line-height:1.6;}
.lv-explain strong{display:block;margin-bottom:4px;font-size:14px;}
/* Pagination */
.lv-pagination{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid #f1f5f9;flex-wrap:wrap;gap:10px;background:#fafafa;}
.lv-pg-left{font-size:13px;color:#64748b;}
.lv-pg-right{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.lv-pg-right label{font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px;}
.lv-pg-right select{padding:5px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:13px;background:#fff;cursor:pointer;outline:none;color:#1e293b;}
.lv-pg-btns{display:flex;gap:3px;align-items:center;flex-wrap:wrap;}
.lv-pg-btn{padding:6px 11px;border:1px solid #e2e8f0;border-radius:7px;font-size:13px;font-weight:600;color:#475569;background:#fff;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;transition:all .15s;min-width:34px;line-height:1;}
.lv-pg-btn:hover:not(.disabled):not(.active){background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;}
.lv-pg-btn.active{background:#3b82f6;color:#fff;border-color:#3b82f6;pointer-events:none;}
.lv-pg-btn.disabled{opacity:.4;pointer-events:none;}
.lv-pg-ellipsis{padding:0 4px;color:#94a3b8;font-size:14px;}
/* Table */
.lv-table{width:100%;border-collapse:collapse;}
.lv-table th{padding:10px 14px;text-align:left;font-size:12px;font-weight:700;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;}
.lv-table td{padding:10px 14px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;}
.lv-table tr:last-child td{border-bottom:none;}
.lv-table tr:hover td{background:#f8fafc;}
</style>

<!-- Hero -->
<div class="lv-hero">
    <div>
        <h1><i class="fas fa-chart-area" style="color:#3b82f6;margin-right:8px;"></i>Advanced Analytics</h1>
        <p>Comprehensive website performance — 100% real data from MySQL, last 30 days</p>
    </div>
    <div class="lv-badge">
        <div class="lv-badge-num"><?php echo number_format($today_visitors); ?></div>
        <div class="lv-badge-lbl">VISITORS TODAY</div>
    </div>
</div>

<!-- Explanation -->
<div class="lv-explain">
    <strong><i class="fas fa-info-circle"></i> All numbers are real — zero fake data</strong>
    Traffic sources are calculated from the <code>referrer</code> column stored per visit. Top pages come from the <code>page_views</code> table. The traffic chart plots actual daily unique sessions for the last 30 days. Bounce rate = single-page sessions ÷ total sessions today.
</div>

<!-- Stats -->
<div class="lv-stats">
    <div class="lv-stat blue">
        <div class="lv-stat-val"><?php echo number_format($today_visitors); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-users" style="color:#3b82f6;"></i> Visitors Today</div>
        <?php if ($yesterday_visitors > 0): ?>
        <div class="lv-stat-chg <?php echo $pct_change >= 0 ? 'up' : 'down'; ?>">
            <i class="fas fa-arrow-<?php echo $pct_change >= 0 ? 'up' : 'down'; ?>"></i>
            <?php echo abs($pct_change); ?>% vs yesterday
        </div>
        <?php endif; ?>
    </div>
    <div class="lv-stat green">
        <div class="lv-stat-val"><?php echo number_format($today_page_views); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-eye" style="color:#10b981;"></i> Page Views Today</div>
        <?php if ($yest_page_views > 0): ?>
        <div class="lv-stat-chg <?php echo $views_pct_change >= 0 ? 'up' : 'down'; ?>">
            <i class="fas fa-arrow-<?php echo $views_pct_change >= 0 ? 'up' : 'down'; ?>"></i>
            <?php echo abs($views_pct_change); ?>% vs yesterday
        </div>
        <?php endif; ?>
    </div>
    <div class="lv-stat amber">
        <div class="lv-stat-val"><?php echo $avg_session; ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-clock" style="color:#f59e0b;"></i> Avg. Session</div>
        <div class="lv-stat-chg neutral">all-time average</div>
    </div>
    <div class="lv-stat indigo">
        <div class="lv-stat-val"><?php echo $bounce_rate; ?>%</div>
        <div class="lv-stat-lbl"><i class="fas fa-percentage" style="color:#6366f1;"></i> Bounce Rate Today</div>
        <div class="lv-stat-chg neutral">single-page sessions</div>
    </div>
</div>

<!-- Actions -->
<div class="lv-actions">
    <button class="lv-btn lv-btn-refresh" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i> Refresh
    </button>
    <div style="font-size:12px;color:#94a3b8;display:flex;align-items:center;gap:6px;">
        <span style="width:7px;height:7px;border-radius:50%;background:#10b981;display:inline-block;animation:lv-blink 1.4s infinite;"></span>
        Real data — no hardcoded numbers &nbsp;|&nbsp; Chart covers last 30 days
    </div>
</div>

<!-- Hourly Traffic Chart -->
<div class="lv-card" style="margin-bottom:20px;">
    <div class="lv-card-hdr">
        <h2><i class="fas fa-chart-bar"></i> Hourly Traffic — Today</h2>
        <span style="font-size:12px;color:#94a3b8;">Unique visitors by hour of day</span>
    </div>
    <div class="lv-card-body">
        <canvas id="hourlyChart" height="60"></canvas>
    </div>
</div>

<!-- Traffic Chart -->
<div class="lv-card">
    <div class="lv-card-hdr">
        <h2><i class="fas fa-chart-area"></i> Traffic Overview (Last 30 Days)</h2>
        <div class="lv-legend">
            <span><span class="lv-dot" style="background:#3b82f6;"></span> Visitors</span>
            <span><span class="lv-dot" style="background:#10b981;"></span> Page Views</span>
        </div>
    </div>
    <div class="lv-card-body">
        <canvas id="trafficChart" height="80"></canvas>
    </div>
</div>

<!-- Traffic Sources + Top Pages -->
<div class="lv-grid">
    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-share-alt"></i> Traffic Sources <small style="font-size:11px;color:#94a3b8;font-weight:400;">(last 30 days, from referrer)</small></h2>
        </div>
        <div class="lv-card-body">
            <div class="lv-src-row">
                <div class="lv-src-top">
                    <span class="lv-src-icon" style="background:linear-gradient(135deg,#4285F4,#34A853);"><i class="fab fa-google"></i></span>
                    <span class="lv-src-name">Organic Search</span>
                    <span class="lv-src-pct"><?php echo $p_search; ?>%</span>
                </div>
                <div class="lv-src-bar"><div class="lv-src-fill" style="width:<?php echo $p_search; ?>%;background:linear-gradient(90deg,#4285F4,#34A853);"></div></div>
            </div>
            <div class="lv-src-row">
                <div class="lv-src-top">
                    <span class="lv-src-icon" style="background:linear-gradient(135deg,#6366f1,#4f46e5);"><i class="fas fa-link"></i></span>
                    <span class="lv-src-name">Direct</span>
                    <span class="lv-src-pct"><?php echo $p_direct; ?>%</span>
                </div>
                <div class="lv-src-bar"><div class="lv-src-fill" style="width:<?php echo $p_direct; ?>%;background:linear-gradient(90deg,#6366f1,#4f46e5);"></div></div>
            </div>
            <div class="lv-src-row">
                <div class="lv-src-top">
                    <span class="lv-src-icon" style="background:linear-gradient(135deg,#ec4899,#db2777);"><i class="fas fa-share-alt"></i></span>
                    <span class="lv-src-name">Social Media</span>
                    <span class="lv-src-pct"><?php echo $p_social; ?>%</span>
                </div>
                <div class="lv-src-bar"><div class="lv-src-fill" style="width:<?php echo $p_social; ?>%;background:linear-gradient(90deg,#ec4899,#db2777);"></div></div>
            </div>
            <div class="lv-src-row">
                <div class="lv-src-top">
                    <span class="lv-src-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-external-link-alt"></i></span>
                    <span class="lv-src-name">Referral</span>
                    <span class="lv-src-pct"><?php echo $p_referral; ?>%</span>
                </div>
                <div class="lv-src-bar"><div class="lv-src-fill" style="width:<?php echo $p_referral; ?>%;background:linear-gradient(90deg,#f59e0b,#d97706);"></div></div>
            </div>
        </div>
    </div>

    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-fire"></i> Top Pages <small style="font-size:11px;color:#94a3b8;font-weight:400;">(last 30 days)</small></h2>
        </div>
        <div class="lv-card-body">
            <?php if (empty($real_top_pages)): ?>
            <div class="lv-empty"><i class="fas fa-chart-bar"></i><p>No page data yet</p></div>
            <?php else: $rank=1; foreach ($real_top_pages as $pg): ?>
            <div class="lv-page-row">
                <div class="lv-page-num"><?php echo $rank++; ?></div>
                <div class="lv-page-url" title="<?php echo htmlspecialchars($pg['page_url'] ?? $pg['current_page'] ?? ''); ?>"><?php echo htmlspecialchars($pg['page_url'] ?? $pg['current_page'] ?? '–'); ?></div>
                <div class="lv-page-cnt"><i class="fas fa-eye"></i> <?php echo number_format($pg['views']); ?></div>
            </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>

<!-- Device Breakdown -->
<div class="lv-card">
    <div class="lv-card-hdr">
        <h2><i class="fas fa-devices"></i> Device Breakdown <small style="font-size:11px;color:#94a3b8;font-weight:400;">(last 30 days — real data)</small></h2>
    </div>
    <div class="lv-card-body" style="max-width:500px;">
        <div class="lv-dev-row">
            <div class="lv-dev-top">
                <span class="lv-dev-name"><i class="fas fa-laptop" style="color:#3b82f6;margin-right:6px;"></i>Desktop</span>
                <span class="lv-dev-cnt"><?php echo number_format($dev_desktop); ?> (<?php echo round($dev_desktop/$dev_total*100); ?>%)</span>
            </div>
            <div class="lv-dev-bar"><div class="lv-dev-fill" style="width:<?php echo round($dev_desktop/$dev_total*100); ?>%;background:#3b82f6;"></div></div>
        </div>
        <div class="lv-dev-row">
            <div class="lv-dev-top">
                <span class="lv-dev-name"><i class="fas fa-mobile-alt" style="color:#10b981;margin-right:6px;"></i>Mobile</span>
                <span class="lv-dev-cnt"><?php echo number_format($dev_mobile); ?> (<?php echo round($dev_mobile/$dev_total*100); ?>%)</span>
            </div>
            <div class="lv-dev-bar"><div class="lv-dev-fill" style="width:<?php echo round($dev_mobile/$dev_total*100); ?>%;background:#10b981;"></div></div>
        </div>
        <div class="lv-dev-row">
            <div class="lv-dev-top">
                <span class="lv-dev-name"><i class="fas fa-tablet-alt" style="color:#f59e0b;margin-right:6px;"></i>Tablet</span>
                <span class="lv-dev-cnt"><?php echo number_format($dev_tablet); ?> (<?php echo round($dev_tablet/$dev_total*100); ?>%)</span>
            </div>
            <div class="lv-dev-bar"><div class="lv-dev-fill" style="width:<?php echo round($dev_tablet/$dev_total*100); ?>%;background:#f59e0b;"></div></div>
        </div>
    </div>
</div>

<!-- Recent Visitor Log -->
<?php
$adv_base  = array_filter($_GET, fn($k) => !in_array($k, ['page','per_page']), ARRAY_FILTER_USE_KEY);
$adv_url   = fn($p,$pp) => '?' . http_build_query(array_merge($adv_base, ['page'=>$p,'per_page'=>$pp]));
$adv_start = $adv_total > 0 ? $adv_off + 1 : 0;
$adv_end   = min($adv_off + $adv_pp, $adv_total);
?>
<div class="lv-card" style="margin-bottom:20px;">
    <div class="lv-card-hdr" style="flex-wrap:wrap;gap:8px;">
        <h2><i class="fas fa-list-alt"></i> Visitor Log <small style="font-size:11px;color:#94a3b8;font-weight:400;">(last 48 hrs &mdash; <?php echo number_format($adv_total); ?> total)</small></h2>
        <a href="../api/export-visitors.php" style="padding:7px 14px;background:#3b82f6;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;"><i class="fas fa-download"></i> Export CSV</a>
    </div>
    <div class="lv-card-body" style="padding:0;overflow-x:auto;">
        <?php if (empty($visitor_log_adv)): ?>
        <div class="lv-empty"><i class="fas fa-inbox"></i><p>No visitor data yet</p></div>
        <?php else: ?>
        <table class="lv-table" style="min-width:900px;font-size:12px;">
            <thead><tr>
                <th>#</th>
                <th>IP Address</th>
                <th>City / Country</th>
                <th>Entry Time</th>
                <th>Exit Time</th>
                <th>Time Spent</th>
                <th>Keyword</th>
                <th>Entry Page</th>
                <th>Exit Page</th>
                <th>Pgs</th>
                <th>Device</th>
            </tr></thead>
            <tbody>
            <?php $n=1; foreach ($visitor_log_adv as $vla):
                $ts3 = (int)($vla['total_time_spent'] ?? 0);
                $ts3s = $ts3 < 60 ? $ts3.'s' : (floor($ts3/60).'m '.($ts3%60).'s');
                $ep3  = $vla['entry_page']   ?? '/';
                $xp3  = $vla['exit_page']    ?? ($vla['current_page'] ?? '/');
            ?>
            <tr>
                <td style="color:#94a3b8;"><?php echo $n++; ?></td>
                <td><code style="background:#eff6ff;padding:2px 7px;border-radius:5px;font-size:11px;"><?php echo htmlspecialchars($vla['ip_address'] ?? '–'); ?></code></td>
                <td><strong><?php echo htmlspecialchars($vla['city'] ?? '–'); ?></strong> <span style="color:#94a3b8;font-size:11px;"><?php echo htmlspecialchars($vla['country'] ?? ''); ?></span></td>
                <td style="white-space:nowrap;font-size:11px;"><?php echo date('d M H:i:s', strtotime($vla['first_visit'])); ?></td>
                <td style="white-space:nowrap;font-size:11px;"><?php echo date('H:i:s', strtotime($vla['last_activity'])); ?></td>
                <td><span style="background:#dbeafe;color:#1d4ed8;padding:2px 7px;border-radius:20px;font-size:11px;font-weight:700;"><?php echo $ts3s; ?></span></td>
                <td><?php if (!empty($vla['search_keyword'])): ?><span style="background:#fef3c7;color:#92400e;padding:2px 7px;border-radius:6px;font-size:11px;font-weight:600;"><?php echo htmlspecialchars($vla['search_keyword']); ?></span><?php else: ?><span style="color:#94a3b8;font-size:11px;">Direct</span><?php endif; ?></td>
                <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;" title="<?php echo htmlspecialchars($ep3); ?>"><?php echo htmlspecialchars(substr($ep3,0,25)).(strlen($ep3)>25?'...':''); ?></td>
                <td style="max-width:130px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11px;" title="<?php echo htmlspecialchars($xp3); ?>"><?php echo htmlspecialchars(substr($xp3,0,25)).(strlen($xp3)>25?'...':''); ?></td>
                <td style="text-align:center;"><span style="background:#ede9fe;color:#6d28d9;padding:2px 7px;border-radius:20px;font-size:11px;font-weight:700;"><?php echo (int)($vla['page_views']??1); ?></span></td>
                <td style="font-size:11px;"><?php
                    $dv = strtolower($vla['device_type']??'desktop');
                    echo '<i class="fas fa-'.($dv==='mobile'?'mobile-alt':($dv==='tablet'?'tablet-alt':'laptop')).'" style="margin-right:3px;"></i>'.htmlspecialchars($vla['browser']??$vla['device_type']??'');
                ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($adv_total > 0): ?>
        <div class="lv-pagination">
            <div class="lv-pg-left">Showing <strong><?php echo $adv_start; ?></strong>&ndash;<strong><?php echo $adv_end; ?></strong> of <strong><?php echo number_format($adv_total); ?></strong> entries</div>
            <div class="lv-pg-right">
                <label>Show
                    <select onchange="window.location='<?php echo htmlspecialchars($adv_url(1,0)); ?>'.replace('per_page=0','per_page='+this.value)">
                        <?php foreach ($adv_opts as $o): ?><option value="<?php echo $o; ?>"<?php echo $o===$adv_pp?' selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?>
                    </select>
                    entries
                </label>
                <div class="lv-pg-btns">
                    <a href="<?php echo htmlspecialchars($adv_url(1,$adv_pp)); ?>" class="lv-pg-btn <?php echo $adv_pg<=1?'disabled':''; ?>" title="First">&laquo;</a>
                    <a href="<?php echo htmlspecialchars($adv_url(max(1,$adv_pg-1),$adv_pp)); ?>" class="lv-pg-btn <?php echo $adv_pg<=1?'disabled':''; ?>" title="Prev">&lsaquo;</a>
                    <?php
                    $ar = 2; $asp = max(1,$adv_pg-$ar); $aep = min($adv_pages,$adv_pg+$ar);
                    if ($asp>1) echo '<span class="lv-pg-ellipsis">&hellip;</span>';
                    for ($ap=$asp;$ap<=$aep;$ap++) echo '<a href="'.htmlspecialchars($adv_url($ap,$adv_pp)).'" class="lv-pg-btn'.($ap===$adv_pg?' active':'').'">'. $ap .'</a>';
                    if ($aep<$adv_pages) echo '<span class="lv-pg-ellipsis">&hellip;</span>';
                    ?>
                    <a href="<?php echo htmlspecialchars($adv_url(min($adv_pages,$adv_pg+1),$adv_pp)); ?>" class="lv-pg-btn <?php echo $adv_pg>=$adv_pages?'disabled':''; ?>" title="Next">&rsaquo;</a>
                    <a href="<?php echo htmlspecialchars($adv_url($adv_pages,$adv_pp)); ?>" class="lv-pg-btn <?php echo $adv_pg>=$adv_pages?'disabled':''; ?>" title="Last">&raquo;</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
new Chart(document.getElementById('trafficChart').getContext('2d'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [{
            label: 'Visitors',
            data: <?php echo json_encode($daily_visitors_chart); ?>,
            borderColor: '#3b82f6',
            backgroundColor: 'rgba(59,130,246,0.08)',
            tension: 0.4, fill: true, pointRadius: 2
        }, {
            label: 'Page Views',
            data: <?php echo json_encode($daily_views_chart); ?>,
            borderColor: '#10b981',
            backgroundColor: 'rgba(16,185,129,0.06)',
            tension: 0.4, fill: true, pointRadius: 2
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: 'rgba(0,0,0,.04)' }, ticks: { font: { size: 11 } } },
            x: { grid: { display: false }, ticks: { font: { size: 10 }, maxTicksLimit: 10 } }
        }
    }
});

new Chart(document.getElementById('hourlyChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($hourly_labels); ?>,
        datasets: [{
            label: 'Visitors',
            data: <?php echo json_encode(array_values($hourly_data)); ?>,
            backgroundColor: 'rgba(59,130,246,0.7)',
            borderColor: '#3b82f6',
            borderWidth: 1, borderRadius: 4
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color:'rgba(0,0,0,.04)' }, ticks:{ font:{size:10}, stepSize:1 } },
            x: { grid: { display:false }, ticks:{ font:{size:9}, maxTicksLimit:24 } }
        }
    }
});
</script>

<?php include '../includes/footer.php'; ?>
