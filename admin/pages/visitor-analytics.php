<?php
/**
 * Real Visitor Analytics Dashboard
 * Shows actual visitor data with geolocation
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check admin authentication
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Visitor Analytics';
include '../includes/header.php';

// MySQL Connection + auto-create tables
$error_message = null;
$today_stats = [];
$realtime_visitors = [];
$top_pages = [];
$top_cities = [];
$visitor_locations = [];
$vl_opts   = [10, 25, 50, 100, 250, 500, 1000, 5000];
$vl_pp     = 25;
$vl_pg     = 1;
$vl_total  = 0;
$vl_pages  = 1;
$vl_offset = 0;
$visitor_log = [];
$at_unique_visitors = 0;
$at_total_sessions  = 0;
$at_total_views     = 0;
$at_unique_ips      = 0;
$at_since           = null;
$monthly_breakdown  = [];

try {
    mysqli_report(MYSQLI_REPORT_OFF);
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception($conn->connect_error);
    $conn->set_charset('utf8mb4');

    // ── Schema migration: add missing columns before querying ──
    $cols_r = $conn->query("SHOW COLUMNS FROM visitor_tracking");
    if ($cols_r) {
        $cols = []; while ($row = $cols_r->fetch_assoc()) $cols[] = $row['Field'];
        $add = [];
        if (!in_array('session_id',     $cols)) $add[] = "ADD COLUMN session_id VARCHAR(64) NOT NULL DEFAULT ''";
        if (!in_array('search_keyword', $cols)) $add[] = "ADD COLUMN search_keyword VARCHAR(255) DEFAULT NULL";
        if (!in_array('exit_page',      $cols)) $add[] = "ADD COLUMN exit_page VARCHAR(500) DEFAULT NULL";
        if (!in_array('ip_address',     $cols)) $add[] = "ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL";
        if (!in_array('city',           $cols)) $add[] = "ADD COLUMN city VARCHAR(100) DEFAULT NULL";
        if (!in_array('country',        $cols)) $add[] = "ADD COLUMN country VARCHAR(100) DEFAULT NULL";
        if (!in_array('country_code',   $cols)) $add[] = "ADD COLUMN country_code VARCHAR(10) DEFAULT NULL";
        if (!in_array('browser',        $cols)) $add[] = "ADD COLUMN browser VARCHAR(100) DEFAULT NULL";
        if (!in_array('device_type',    $cols)) $add[] = "ADD COLUMN device_type VARCHAR(20) DEFAULT 'desktop'";
        if (!in_array('page_views',     $cols)) $add[] = "ADD COLUMN page_views INT DEFAULT 1";
        if (!in_array('total_time_spent',$cols))$add[] = "ADD COLUMN total_time_spent INT DEFAULT 0";
        if (!in_array('first_visit',    $cols)) $add[] = "ADD COLUMN first_visit DATETIME DEFAULT CURRENT_TIMESTAMP";
        if (!in_array('last_activity',  $cols)) $add[] = "ADD COLUMN last_activity DATETIME DEFAULT CURRENT_TIMESTAMP";
        if (!in_array('entry_page',     $cols)) $add[] = "ADD COLUMN entry_page VARCHAR(500) DEFAULT NULL";
        if (!in_array('referrer',       $cols)) $add[] = "ADD COLUMN referrer VARCHAR(500) DEFAULT NULL";
        if (!empty($add)) $conn->query("ALTER TABLE visitor_tracking " . implode(', ', $add));
        // unique index on session_id
        $idx = $conn->query("SHOW INDEX FROM visitor_tracking WHERE Key_name='idx_session'");
        if ($idx && $idx->num_rows === 0) $conn->query("ALTER TABLE visitor_tracking ADD UNIQUE KEY idx_session (session_id)");
    }
    // page_views extra columns
    $cols2_r = $conn->query("SHOW COLUMNS FROM page_views");
    if ($cols2_r) {
        $cols2 = []; while ($row = $cols2_r->fetch_assoc()) $cols2[] = $row['Field'];
        $add2 = [];
        if (!in_array('time_spent',   $cols2)) $add2[] = "ADD COLUMN time_spent INT DEFAULT 0";
        if (!in_array('scroll_depth', $cols2)) $add2[] = "ADD COLUMN scroll_depth INT DEFAULT 0";
        if (!empty($add2)) $conn->query("ALTER TABLE page_views " . implode(', ', $add2));
    }

    // ── Auto-create tracking tables (if they don't exist yet) ──
    $conn->query("CREATE TABLE IF NOT EXISTS visitor_tracking (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        browser VARCHAR(100),
        browser_version VARCHAR(50),
        device_type VARCHAR(20) DEFAULT 'desktop',
        os VARCHAR(100),
        country VARCHAR(100),
        country_code VARCHAR(10),
        region VARCHAR(100),
        city VARCHAR(100),
        latitude DECIMAL(10,8),
        longitude DECIMAL(11,8),
        timezone VARCHAR(50),
        isp VARCHAR(255),
        current_page VARCHAR(500),
        entry_page VARCHAR(500),
        referrer VARCHAR(500),
        is_online TINYINT(1) DEFAULT 0,
        page_views INT DEFAULT 1,
        total_time_spent INT DEFAULT 0,
        first_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_session (session_id),
        KEY idx_first_visit (first_visit),
        KEY idx_city (city),
        KEY idx_is_online (is_online)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64),
        page_url VARCHAR(500),
        `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_session (session_id),
        KEY idx_timestamp (`timestamp`),
        KEY idx_page (page_url(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $conn->query("CREATE TABLE IF NOT EXISTS visitor_heatmap (
        id INT AUTO_INCREMENT PRIMARY KEY,
        country_code VARCHAR(10),
        country_name VARCHAR(100),
        visitor_count INT DEFAULT 0,
        last_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_country (country_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Today's statistics ────────────────────────────────
    $r = $conn->query("
        SELECT
            COUNT(DISTINCT session_id)                                      AS total_visitors,
            COALESCE(SUM(page_views), 0)                                    AS total_page_views,
            COALESCE(ROUND(AVG(total_time_spent), 0), 0)                    AS avg_time_on_site,
            SUM(CASE WHEN device_type='mobile'  THEN 1 ELSE 0 END)          AS mobile_visitors,
            SUM(CASE WHEN device_type='desktop' THEN 1 ELSE 0 END)          AS desktop_visitors
        FROM visitor_tracking
        WHERE DATE(first_visit) = CURDATE()
    ");
    $today_stats = $r ? $r->fetch_assoc() : [];

    // ── Real-time visitors (active last 30 min) with full data ──
    $r2 = $conn->query("
        SELECT session_id, ip_address, city, region, country, country_code,
               device_type, browser, os, isp,
               last_activity, page_views AS total_page_views,
               total_time_spent,
               TIMESTAMPDIFF(MINUTE, last_activity, NOW()) AS minutes_since_last_activity,
               current_page, entry_page, exit_page, referrer, search_keyword,
               first_visit
        FROM visitor_tracking
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
        ORDER BY last_activity DESC
        LIMIT 50
    ");
    $realtime_visitors = $r2 ? $r2->fetch_all(MYSQLI_ASSOC) : [];

    // ── Full visitor log today — paginated ────────────────
    $vl_opts   = [10, 25, 50, 100, 250, 500, 1000, 5000];
    $vl_pp     = in_array((int)($_GET['per_page'] ?? 25), $vl_opts) ? (int)($_GET['per_page'] ?? 25) : 25;
    $vl_pg     = max(1, (int)($_GET['page'] ?? 1));
    $vl_total_r = $conn->query("SELECT COUNT(*) AS c FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()");
    $vl_total   = $vl_total_r ? (int)$vl_total_r->fetch_assoc()['c'] : 0;
    $vl_pages   = max(1, (int)ceil($vl_total / $vl_pp));
    $vl_pg      = min($vl_pg, $vl_pages);
    $vl_offset  = ($vl_pg - 1) * $vl_pp;
    $r_log = $conn->query("
        SELECT id, ip_address, city, region, country, country_code,
               device_type, browser, os,
               entry_page, exit_page, current_page, referrer, search_keyword,
               first_visit, last_activity, total_time_spent, page_views
        FROM visitor_tracking
        WHERE DATE(first_visit) = CURDATE()
        ORDER BY first_visit DESC
        LIMIT {$vl_pp} OFFSET {$vl_offset}
    ");
    $visitor_log = $r_log ? $r_log->fetch_all(MYSQLI_ASSOC) : [];

    // ── Top pages today ───────────────────────────────────
    $r3 = $conn->query("
        SELECT page_url,
               COUNT(*) AS view_count,
               COUNT(DISTINCT session_id) AS unique_visitors,
               0 AS avg_time_on_page
        FROM page_views
        WHERE DATE(`timestamp`) = CURDATE()
        GROUP BY page_url
        ORDER BY view_count DESC
        LIMIT 10
    ");
    $top_pages = $r3 ? $r3->fetch_all(MYSQLI_ASSOC) : [];

    // ── Top cities ────────────────────────────────────────
    $r4 = $conn->query("
        SELECT city, region, country,
               COUNT(DISTINCT session_id) AS visitor_count,
               COALESCE(SUM(page_views), 0) AS total_page_views
        FROM visitor_tracking
        WHERE DATE(first_visit) = CURDATE()
          AND city IS NOT NULL AND city != '' AND city != 'Unknown'
        GROUP BY city, region, country
        ORDER BY visitor_count DESC
        LIMIT 10
    ");
    $top_cities = $r4 ? $r4->fetch_all(MYSQLI_ASSOC) : [];

    // ── Visitor locations for map ─────────────────────────
    $r5 = $conn->query("
        SELECT city, region, country, latitude, longitude, COUNT(*) AS visitor_count
        FROM visitor_tracking
        WHERE DATE(first_visit) = CURDATE()
          AND latitude IS NOT NULL AND latitude != 0
        GROUP BY city, region, country, latitude, longitude
        ORDER BY visitor_count DESC
        LIMIT 100
    ");
    $visitor_locations = $r5 ? $r5->fetch_all(MYSQLI_ASSOC) : [];

    // ── ALL-TIME stats ────────────────────────────────────
    $at_unique_visitors = (int)(($conn->query("SELECT COUNT(DISTINCT session_id) AS c FROM visitor_tracking") ?: null)?->fetch_assoc()['c'] ?? 0);
    $at_total_sessions  = (int)(($conn->query("SELECT COUNT(*) AS c FROM visitor_tracking") ?: null)?->fetch_assoc()['c'] ?? 0);
    $at_total_views     = (int)(($conn->query("SELECT COUNT(*) AS c FROM page_views") ?: null)?->fetch_assoc()['c'] ?? 0);
    if ($at_total_views === 0) {
        $at_total_views = (int)(($conn->query("SELECT COALESCE(SUM(page_views),0) AS c FROM visitor_tracking") ?: null)?->fetch_assoc()['c'] ?? 0);
    }
    $at_unique_ips    = (int)(($conn->query("SELECT COUNT(DISTINCT ip_address) AS c FROM visitor_tracking WHERE ip_address IS NOT NULL AND ip_address != ''") ?: null)?->fetch_assoc()['c'] ?? 0);
    $at_since_row     = ($conn->query("SELECT MIN(first_visit) AS d FROM visitor_tracking") ?: null)?->fetch_assoc();
    $at_since         = $at_since_row['d'] ?? null;

    // ── Monthly breakdown — last 12 months ───────────────
    $r_monthly = $conn->query("
        SELECT DATE_FORMAT(first_visit, '%Y-%m') AS month,
               DATE_FORMAT(MIN(first_visit), '%b %Y') AS month_label,
               COUNT(DISTINCT session_id) AS unique_visitors,
               COUNT(*) AS total_sessions,
               COALESCE(SUM(page_views), 0) AS total_page_views,
               COUNT(DISTINCT ip_address) AS unique_ips
        FROM visitor_tracking
        WHERE first_visit >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(first_visit, '%Y-%m')
        ORDER BY month DESC
    ");
    $monthly_breakdown = $r_monthly ? $r_monthly->fetch_all(MYSQLI_ASSOC) : [];

} catch (\Throwable $e) {
    $error_message = $e->getMessage();
    $at_unique_visitors = $at_unique_visitors ?? 0;
    $at_total_sessions  = $at_total_sessions  ?? 0;
    $at_total_views     = $at_total_views     ?? 0;
    $at_unique_ips      = $at_unique_ips      ?? 0;
    $at_since           = $at_since           ?? null;
    $monthly_breakdown  = $monthly_breakdown  ?? [];
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
<style>
/* ── Visitor Analytics — same lv- theme ─────────────────── */
@keyframes lv-blink{0%,100%{opacity:1;}50%{opacity:.25;}}
.lv-hero{background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:28px 32px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;gap:16px;border-left:6px solid #8b5cf6;}
.lv-hero h1{font-size:26px;font-weight:800;color:#1e293b;margin:0 0 4px;}
.lv-hero p{color:#64748b;font-size:14px;margin:0;}
.lv-badge{background:#f5f3ff;border:1.5px solid #ddd6fe;border-radius:12px;padding:12px 18px;text-align:center;flex-shrink:0;}
.lv-badge-num{font-size:28px;font-weight:800;color:#6d28d9;}
.lv-badge-lbl{font-size:11px;color:#7c3aed;font-weight:600;}
.lv-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:0;background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);overflow:hidden;margin-bottom:20px;}
.lv-stat{padding:22px 20px;text-align:center;position:relative;}
.lv-stat+.lv-stat::before{content:'';position:absolute;left:0;top:20%;bottom:20%;width:1px;background:#e2e8f0;}
.lv-stat-val{font-size:34px;font-weight:800;color:#1e293b;}
.lv-stat-lbl{font-size:12px;color:#64748b;font-weight:600;margin-top:4px;display:flex;align-items:center;justify-content:center;gap:5px;}
.lv-stat.green .lv-stat-val{color:#10b981;}.lv-stat.blue .lv-stat-val{color:#3b82f6;}.lv-stat.amber .lv-stat-val{color:#f59e0b;}.lv-stat.indigo .lv-stat-val{color:#6366f1;}
.lv-card{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:20px;overflow:hidden;}
.lv-card-hdr{padding:16px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;}
.lv-card-hdr h2{font-size:16px;font-weight:800;color:#1e293b;margin:0;}
.lv-card-hdr h2 i{color:#8b5cf6;margin-right:8px;}
.lv-card-body{padding:16px 22px;}
.lv-online-dot{width:8px;height:8px;border-radius:50%;background:#10b981;animation:lv-blink 1.4s infinite;display:inline-block;}
.lv-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:20px;margin-bottom:20px;}
@media(max-width:900px){.lv-grid{grid-template-columns:1fr;}}
/* Visitor rows */
.lv-visitor-row{padding:12px 14px;background:#f8fafc;border-radius:10px;margin-bottom:8px;border-left:3px solid #8b5cf6;transition:all .2s;}
.lv-visitor-row:hover{background:#f1f5f9;transform:translateX(3px);}
.lv-vr-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;}
.lv-vr-loc{font-weight:700;font-size:14px;color:#1e293b;}
.lv-vr-time{font-size:11px;color:#94a3b8;}
.lv-vr-details{display:flex;flex-wrap:wrap;gap:12px;}
.lv-vr-det{font-size:12px;color:#64748b;display:flex;align-items:center;gap:4px;}
.lv-vr-det i{color:#8b5cf6;width:13px;}
.lv-vr-page{margin-top:6px;font-size:12px;color:#475569;background:#fff;padding:5px 10px;border-radius:6px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
/* Table */
.lv-table{width:100%;border-collapse:collapse;}
.lv-table th{padding:10px 14px;text-align:left;font-size:12px;font-weight:700;color:#64748b;background:#f8fafc;border-bottom:2px solid #e2e8f0;}
.lv-table td{padding:10px 14px;font-size:13px;color:#1e293b;border-bottom:1px solid #f1f5f9;}
.lv-table tr:last-child td{border-bottom:none;}
.lv-table tr:hover td{background:#f8fafc;}
.lv-rank{width:24px;height:24px;border-radius:50%;background:#ede9fe;color:#6d28d9;font-size:11px;font-weight:800;display:flex;align-items:center;justify-content:center;}
/* Map */
#va-map{height:420px;border-radius:10px;}
/* Scroll */
.lv-scroll{max-height:420px;overflow-y:auto;}
.lv-scroll::-webkit-scrollbar{width:4px;}
.lv-scroll::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:2px;}
.lv-empty{text-align:center;padding:40px 20px;color:#94a3b8;}
.lv-empty i{font-size:32px;display:block;margin-bottom:10px;}
.lv-empty p{font-size:13px;margin:0;}
.lv-actions{background:#fff;border-radius:14px;box-shadow:0 4px 20px rgba(0,0,0,.07);padding:18px 24px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;}
.lv-btn{padding:10px 20px;border:none;border-radius:10px;font-weight:700;font-size:13px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:opacity .2s;text-decoration:none;}
.lv-btn:hover{opacity:.85;}
.lv-btn-refresh{background:#8b5cf6;color:#fff;}
.lv-auto-lbl{font-size:12px;color:#94a3b8;display:flex;align-items:center;gap:6px;}
.lv-auto-dot{width:7px;height:7px;border-radius:50%;background:#10b981;animation:lv-blink 1.4s infinite;}
/* Pagination */
.lv-pagination{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;border-top:1px solid #f1f5f9;flex-wrap:wrap;gap:10px;background:#fafafa;}
.lv-pg-left{font-size:13px;color:#64748b;}
.lv-pg-right{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
.lv-pg-right label{font-size:13px;color:#64748b;display:flex;align-items:center;gap:6px;}
.lv-pg-right select{padding:5px 10px;border:1px solid #e2e8f0;border-radius:7px;font-size:13px;background:#fff;cursor:pointer;outline:none;color:#1e293b;}
.lv-pg-btns{display:flex;gap:3px;align-items:center;flex-wrap:wrap;}
.lv-pg-btn{padding:6px 11px;border:1px solid #e2e8f0;border-radius:7px;font-size:13px;font-weight:600;color:#475569;background:#fff;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;transition:all .15s;min-width:34px;line-height:1;}
.lv-pg-btn:hover:not(.disabled):not(.active){background:#eff6ff;border-color:#bfdbfe;color:#1d4ed8;}
.lv-pg-btn.active{background:#8b5cf6;color:#fff;border-color:#8b5cf6;pointer-events:none;}
.lv-pg-btn.disabled{opacity:.4;pointer-events:none;}
.lv-pg-ellipsis{padding:0 4px;color:#94a3b8;font-size:14px;}
</style>

<!-- Hero -->
<div class="lv-hero">
    <div>
        <h1><i class="fas fa-chart-line" style="color:#8b5cf6;margin-right:8px;"></i>Visitor Analytics</h1>
        <p>Today's real visitor data — all figures read directly from the tracking database, zero fake numbers</p>
    </div>
    <div class="lv-badge">
        <div class="lv-badge-num"><?php echo number_format($today_stats['total_visitors'] ?? 0); ?></div>
        <div class="lv-badge-lbl">VISITORS TODAY</div>
    </div>
</div>

<?php if ($error_message): ?>
<div style="background:#fff3cd;border:1px solid #ffc107;border-left:5px solid #f59e0b;padding:14px 18px;border-radius:10px;margin-bottom:20px;color:#856404;font-size:13px;">
    <strong>⚠ DB Notice:</strong> <?php echo htmlspecialchars($error_message); ?> — tables will be created automatically on first visit.
</div>
<?php endif; ?>

<!-- Stats -->
<div class="lv-stats">
    <div class="lv-stat blue">
        <div class="lv-stat-val"><?php echo number_format($today_stats['total_visitors'] ?? 0); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-users" style="color:#3b82f6;"></i> Visitors Today</div>
    </div>
    <div class="lv-stat amber">
        <div class="lv-stat-val"><?php echo number_format($today_stats['total_page_views'] ?? 0); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-eye" style="color:#f59e0b;"></i> Page Views</div>
    </div>
    <div class="lv-stat indigo">
        <div class="lv-stat-val"><?php echo gmdate('i:s', $today_stats['avg_time_on_site'] ?? 0); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-clock" style="color:#6366f1;"></i> Avg Time on Site</div>
    </div>
    <div class="lv-stat green">
        <div class="lv-stat-val"><?php echo number_format($today_stats['mobile_visitors'] ?? 0); ?></div>
        <div class="lv-stat-lbl"><i class="fas fa-mobile-alt" style="color:#10b981;"></i> Mobile Visitors</div>
    </div>
</div>

<!-- All-Time Statistics ─────────────────────────────────────── -->
<div class="lv-card" style="margin-bottom:20px;border-left:6px solid #10b981;">
    <div class="lv-card-hdr" style="background:linear-gradient(135deg,#ecfdf5 0%,#f0fdf4 100%);">
        <h2><i class="fas fa-infinity" style="color:#10b981;"></i> All-Time Statistics <span style="font-size:12px;font-weight:400;color:#94a3b8;margin-left:8px;">Every visitor since tracking began<?php if ($at_since): ?> · <strong style="color:#059669;">Since <?php echo date('M j, Y', strtotime($at_since)); ?></strong><?php endif; ?></span></h2>
        <span style="font-size:12px;color:#6b7280;background:#fff;padding:5px 12px;border-radius:8px;border:1px solid #d1fae5;"><i class="fas fa-database" style="color:#10b981;"></i> Live from DB</span>
    </div>
    <div class="lv-card-body" style="padding:0;">
        <!-- 4 Big Counters -->
        <div style="display:grid;grid-template-columns:repeat(4,1fr);">
            <div style="padding:22px 18px;text-align:center;border-right:1px solid #f0fdf4;">
                <div style="font-size:36px;font-weight:900;color:#059669;"><?php echo number_format($at_unique_visitors ?? 0); ?></div>
                <div style="font-size:12px;font-weight:700;color:#065f46;margin-top:4px;"><i class="fas fa-user-check"></i> Unique Visitors</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:3px;">Each person counted once</div>
            </div>
            <div style="padding:22px 18px;text-align:center;border-right:1px solid #f0fdf4;">
                <div style="font-size:36px;font-weight:900;color:#0284c7;"><?php echo number_format($at_total_sessions ?? 0); ?></div>
                <div style="font-size:12px;font-weight:700;color:#0c4a6e;margin-top:4px;"><i class="fas fa-users"></i> Total Visits</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:3px;">All sessions including returns</div>
            </div>
            <div style="padding:22px 18px;text-align:center;border-right:1px solid #f0fdf4;">
                <div style="font-size:36px;font-weight:900;color:#7c3aed;"><?php echo number_format($at_total_views ?? 0); ?></div>
                <div style="font-size:12px;font-weight:700;color:#4c1d95;margin-top:4px;"><i class="fas fa-eye"></i> Total Page Views</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:3px;">Every page opened ever</div>
            </div>
            <div style="padding:22px 18px;text-align:center;">
                <div style="font-size:36px;font-weight:900;color:#d97706;"><?php echo number_format($at_unique_ips ?? 0); ?></div>
                <div style="font-size:12px;font-weight:700;color:#92400e;margin-top:4px;"><i class="fas fa-network-wired"></i> Unique IP Addresses</div>
                <div style="font-size:11px;color:#94a3b8;margin-top:3px;">Distinct real-world devices</div>
            </div>
        </div>

        <!-- Monthly Breakdown -->
        <?php if (!empty($monthly_breakdown)): ?>
        <div style="border-top:1px solid #f1f5f9;padding:16px 22px;">
            <div style="font-size:13px;font-weight:800;color:#1e293b;margin-bottom:12px;"><i class="fas fa-calendar-alt" style="color:#10b981;"></i> Monthly Breakdown <small style="font-weight:400;color:#94a3b8;">(last 12 months)</small></div>
            <div style="overflow-x:auto;">
            <table class="lv-table">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th style="text-align:right;">Unique Visitors</th>
                        <th style="text-align:right;">Total Visits</th>
                        <th style="text-align:right;">Page Views</th>
                        <th style="text-align:right;">Unique IPs</th>
                        <th>Traffic Bar</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $max_uniq = max(1, max(array_column($monthly_breakdown, 'unique_visitors')));
                foreach ($monthly_breakdown as $mrow):
                    $bar_pct = round(($mrow['unique_visitors'] / $max_uniq) * 100);
                    $is_this_month = ($mrow['month'] === date('Y-m'));
                ?>
                <tr<?php if ($is_this_month): ?> style="background:#f0fdf4;font-weight:700;"<?php endif; ?>>
                    <td><?php if ($is_this_month): ?><span style="background:#10b981;color:#fff;font-size:10px;padding:2px 7px;border-radius:5px;margin-right:6px;">NOW</span><?php endif; ?><?php echo htmlspecialchars($mrow['month_label']); ?></td>
                    <td style="text-align:right;color:#059669;font-weight:700;"><?php echo number_format($mrow['unique_visitors']); ?></td>
                    <td style="text-align:right;color:#0284c7;font-weight:700;"><?php echo number_format($mrow['total_sessions']); ?></td>
                    <td style="text-align:right;color:#7c3aed;"><?php echo number_format($mrow['total_page_views']); ?></td>
                    <td style="text-align:right;color:#d97706;"><?php echo number_format($mrow['unique_ips']); ?></td>
                    <td style="width:160px;">
                        <div style="background:#e2e8f0;border-radius:4px;height:8px;overflow:hidden;">
                            <div style="background:#10b981;height:100%;width:<?php echo $bar_pct; ?>%;border-radius:4px;transition:width .8s;"></div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Actions -->
<div class="lv-actions">
    <button class="lv-btn lv-btn-refresh" onclick="location.reload()">
        <i class="fas fa-sync-alt"></i> Refresh Now
    </button>
    <div class="lv-auto-lbl">
        <span class="lv-auto-dot"></span>
        Auto-refresh every 60 s &nbsp;|&nbsp; All data from MySQL — no estimates
    </div>
</div>

<!-- Map + Recent Visitors -->
<div class="lv-grid">
    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-map-marked-alt"></i> Visitor Locations (Today)</h2>
        </div>
        <div class="lv-card-body" style="padding:12px;">
            <div id="va-map"></div>
        </div>
    </div>

    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-satellite-dish"></i> Recent Visitors <small style="font-size:11px;color:#94a3b8;font-weight:400;">(last 30 min)</small></h2>
        </div>
        <div class="lv-card-body">
            <div class="lv-scroll">
                <?php if (empty($realtime_visitors)): ?>
                <div class="lv-empty"><i class="fas fa-moon"></i><p>No active visitors in the last 30 minutes</p></div>
                <?php else: foreach ($realtime_visitors as $v):
                    $mins = (int)($v['minutes_since_last_activity'] ?? 0);
                    $ts   = (int)($v['total_time_spent'] ?? 0);
                    $tstr = $ts < 60 ? $ts.'s' : (floor($ts/60).'m '.($ts%60).'s');
                ?>
                <div class="lv-visitor-row">
                    <div class="lv-vr-top">
                        <div class="lv-vr-loc">
                            <?php echo htmlspecialchars(trim(($v['city'] ?? '') . ', ' . ($v['country'] ?? ''), ', ')) ?: 'Unknown'; ?>
                        </div>
                        <div class="lv-vr-time">
                            <?php if ($mins == 0): ?><span class="lv-online-dot"></span> Active now
                            <?php else: echo $mins . ' min ago'; endif; ?>
                        </div>
                    </div>
                    <div class="lv-vr-details">
                        <span class="lv-vr-det"><i class="fas fa-network-wired"></i><?php echo htmlspecialchars($v['ip_address'] ?? '–'); ?></span>
                        <span class="lv-vr-det"><i class="fas fa-<?php echo $v['device_type']=='mobile'?'mobile-alt':'laptop'; ?>"></i><?php echo htmlspecialchars($v['device_type'] ?? 'desktop'); ?></span>
                        <span class="lv-vr-det"><i class="fas fa-globe"></i><?php echo htmlspecialchars($v['browser'] ?? '–'); ?></span>
                        <span class="lv-vr-det"><i class="fas fa-clock"></i><?php echo $tstr; ?></span>
                        <span class="lv-vr-det"><i class="fas fa-file-alt"></i><?php echo (int)$v['total_page_views']; ?> pg</span>
                    </div>
                    <?php if (!empty($v['search_keyword'])): ?>
                    <div style="margin-top:5px;font-size:12px;background:#fef3c7;padding:4px 10px;border-radius:6px;color:#92400e;"><i class="fas fa-search" style="margin-right:4px;color:#d97706;"></i>Keyword: <strong><?php echo htmlspecialchars($v['search_keyword']); ?></strong></div>
                    <?php endif; ?>
                    <div class="lv-vr-page"><i class="fas fa-sign-in-alt" style="color:#10b981;margin-right:4px;"></i>Entry: <?php echo htmlspecialchars($v['entry_page'] ?? '/'); ?></div>
                    <div class="lv-vr-page"><i class="fas fa-link" style="color:#8b5cf6;margin-right:4px;"></i>Now: <?php echo htmlspecialchars($v['current_page'] ?? '/'); ?></div>
                    <div style="font-size:11px;color:#94a3b8;margin-top:4px;padding:0 4px;"><i class="fas fa-calendar-alt" style="margin-right:3px;"></i><?php echo date('H:i:s', strtotime($v['first_visit'])); ?> — <?php echo date('H:i:s', strtotime($v['last_activity'])); ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Top Pages + Top Cities -->
<div class="lv-grid">
    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-fire"></i> Top Pages Today</h2>
        </div>
        <div class="lv-card-body">
            <?php if (empty($top_pages)): ?>
            <div class="lv-empty"><i class="fas fa-chart-bar"></i><p>No page data yet today</p></div>
            <?php else: ?>
            <table class="lv-table">
                <thead><tr><th>#</th><th>Page URL</th><th>Views</th><th>Unique</th></tr></thead>
                <tbody>
                <?php $rank=1; foreach ($top_pages as $pg): ?>
                <tr>
                    <td><div class="lv-rank"><?php echo $rank++; ?></div></td>
                    <td style="max-width:220px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;"><?php echo htmlspecialchars($pg['page_url']); ?></td>
                    <td><strong><?php echo number_format($pg['view_count']); ?></strong></td>
                    <td><?php echo number_format($pg['unique_visitors']); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="lv-card">
        <div class="lv-card-hdr">
            <h2><i class="fas fa-city"></i> Top Cities Today</h2>
        </div>
        <div class="lv-card-body">
            <?php if (empty($top_cities)): ?>
            <div class="lv-empty"><i class="fas fa-map-pin"></i><p>No city data yet today</p></div>
            <?php else: ?>
            <table class="lv-table">
                <thead><tr><th>#</th><th>City</th><th>Country</th><th>Visitors</th></tr></thead>
                <tbody>
                <?php $rank=1; foreach ($top_cities as $c): ?>
                <tr>
                    <td><div class="lv-rank"><?php echo $rank++; ?></div></td>
                    <td><strong><?php echo htmlspecialchars($c['city']); ?></strong></td>
                    <td><?php echo htmlspecialchars($c['country']); ?></td>
                    <td><?php echo number_format($c['visitor_count']); ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detailed Visitor Log -->
<?php
$vl_base = array_filter($_GET, fn($k) => !in_array($k, ['page','per_page']), ARRAY_FILTER_USE_KEY);
$vl_url  = fn($p,$pp) => '?' . http_build_query(array_merge($vl_base, ['page'=>$p,'per_page'=>$pp]));
$vl_start = $vl_total > 0 ? $vl_offset + 1 : 0;
$vl_end   = min($vl_offset + $vl_pp, $vl_total);
?>
<div class="lv-card">
    <div class="lv-card-hdr" style="flex-wrap:wrap;gap:8px;">
        <h2><i class="fas fa-list-alt"></i> Full Visitor Log — Today <small style="font-size:11px;color:#94a3b8;font-weight:400;">(<?php echo number_format($vl_total); ?> total)</small></h2>
        <a href="../api/export-visitors.php" style="padding:7px 14px;background:#8b5cf6;color:#fff;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;"><i class="fas fa-download"></i> Export CSV</a>
    </div>
    <div class="lv-card-body" style="padding:0;overflow-x:auto;">
        <?php if (empty($visitor_log)): ?>
        <div class="lv-empty"><i class="fas fa-inbox"></i><p>No visitors recorded today yet</p></div>
        <?php else: ?>
        <table class="lv-table" style="min-width:900px;font-size:12px;">
            <thead><tr>
                <th>#</th>
                <th><i class="fas fa-network-wired" style="color:#8b5cf6;"></i> IP Address</th>
                <th><i class="fas fa-city" style="color:#3b82f6;"></i> City / Country</th>
                <th><i class="fas fa-sign-in-alt" style="color:#10b981;"></i> Entry Time</th>
                <th><i class="fas fa-sign-out-alt" style="color:#ef4444;"></i> Exit Time</th>
                <th><i class="fas fa-clock" style="color:#f59e0b;"></i> Time Spent</th>
                <th><i class="fas fa-search" style="color:#d97706;"></i> Keyword</th>
                <th><i class="fas fa-door-open" style="color:#10b981;"></i> Entry Page</th>
                <th><i class="fas fa-door-closed" style="color:#ef4444;"></i> Exit Page</th>
                <th><i class="fas fa-file-alt" style="color:#64748b;"></i> Pages</th>
                <th><i class="fas fa-laptop" style="color:#64748b;"></i> Device</th>
            </tr></thead>
            <tbody>
            <?php $n=1; foreach ($visitor_log as $vl):
                $ts2 = (int)($vl['total_time_spent'] ?? 0);
                $ts2s = $ts2 < 60 ? $ts2.'s' : (floor($ts2/60).'m '.($ts2%60).'s');
                $ep  = $vl['entry_page'] ?? '/';
                $xp  = $vl['exit_page']  ?? ($vl['current_page'] ?? '/');
            ?>
            <tr>
                <td style="color:#94a3b8;"><?php echo $n++; ?></td>
                <td><code style="background:#f1f5f9;padding:2px 7px;border-radius:5px;font-size:11px;"><?php echo htmlspecialchars($vl['ip_address'] ?? '–'); ?></code></td>
                <td><strong><?php echo htmlspecialchars($vl['city'] ?? '–'); ?></strong><br><span style="color:#94a3b8;font-size:11px;"><?php echo htmlspecialchars($vl['country'] ?? ''); ?></span></td>
                <td style="white-space:nowrap;"><?php echo date('H:i:s', strtotime($vl['first_visit'])); ?><br><span style="font-size:10px;color:#94a3b8;"><?php echo date('d M', strtotime($vl['first_visit'])); ?></span></td>
                <td style="white-space:nowrap;"><?php echo date('H:i:s', strtotime($vl['last_activity'])); ?><br><span style="font-size:10px;color:#94a3b8;">last seen</span></td>
                <td><span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:20px;font-weight:700;"><?php echo $ts2s; ?></span></td>
                <td><?php if (!empty($vl['search_keyword'])): ?><span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:6px;font-weight:600;"><?php echo htmlspecialchars($vl['search_keyword']); ?></span><?php else: ?><span style="color:#94a3b8;">Direct</span><?php endif; ?></td>
                <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($ep); ?>"><?php echo htmlspecialchars(substr($ep, 0, 30)).(strlen($ep)>30?'...':''); ?></td>
                <td style="max-width:150px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?php echo htmlspecialchars($xp); ?>"><?php echo htmlspecialchars(substr($xp, 0, 30)).(strlen($xp)>30?'...':''); ?></td>
                <td style="text-align:center;"><span style="background:#ede9fe;color:#6d28d9;padding:2px 8px;border-radius:20px;font-weight:700;"><?php echo (int)($vl['page_views'] ?? 1); ?></span></td>
                <td><?php
                    $dev = strtolower($vl['device_type'] ?? 'desktop');
                    $ic  = $dev==='mobile' ? 'fa-mobile-alt' : ($dev==='tablet' ? 'fa-tablet-alt' : 'fa-laptop');
                ?><i class="fas <?php echo $ic; ?>" style="margin-right:4px;"></i><?php echo htmlspecialchars($vl['browser'] ?? $vl['device_type'] ?? ''); ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php if ($vl_total > 0): ?>
        <div class="lv-pagination">
            <div class="lv-pg-left">Showing <strong><?php echo $vl_start; ?></strong>–<strong><?php echo $vl_end; ?></strong> of <strong><?php echo number_format($vl_total); ?></strong> entries</div>
            <div class="lv-pg-right">
                <label>Show
                    <select onchange="window.location='<?php echo htmlspecialchars($vl_url(1, 0)); ?>'.replace('per_page=0','per_page='+this.value)">
                        <?php foreach ($vl_opts as $o): ?><option value="<?php echo $o; ?>"<?php echo $o===$vl_pp?' selected':''; ?>><?php echo $o; ?></option><?php endforeach; ?>
                    </select>
                    entries
                </label>
                <div class="lv-pg-btns">
                    <a href="<?php echo htmlspecialchars($vl_url(1,$vl_pp)); ?>" class="lv-pg-btn <?php echo $vl_pg<=1?'disabled':''; ?>" title="First">&laquo;</a>
                    <a href="<?php echo htmlspecialchars($vl_url(max(1,$vl_pg-1),$vl_pp)); ?>" class="lv-pg-btn <?php echo $vl_pg<=1?'disabled':''; ?>" title="Prev">&lsaquo;</a>
                    <?php
                    $r2 = 2; $sp = max(1,$vl_pg-$r2); $ep = min($vl_pages,$vl_pg+$r2);
                    if ($sp>1) echo '<span class="lv-pg-ellipsis">…</span>';
                    for ($p=$sp;$p<=$ep;$p++) echo '<a href="'.htmlspecialchars($vl_url($p,$vl_pp)).'" class="lv-pg-btn'.($p===$vl_pg?' active':'').'">'.$p.'</a>';
                    if ($ep<$vl_pages) echo '<span class="lv-pg-ellipsis">…</span>';
                    ?>
                    <a href="<?php echo htmlspecialchars($vl_url(min($vl_pages,$vl_pg+1),$vl_pp)); ?>" class="lv-pg-btn <?php echo $vl_pg>=$vl_pages?'disabled':''; ?>" title="Next">&rsaquo;</a>
                    <a href="<?php echo htmlspecialchars($vl_url($vl_pages,$vl_pp)); ?>" class="lv-pg-btn <?php echo $vl_pg>=$vl_pages?'disabled':''; ?>" title="Last">&raquo;</a>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
    const vaMap = L.map('va-map').setView([17.385, 78.487], 11);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap'}).addTo(vaMap);
    const vaLocs = <?php echo json_encode($visitor_locations ?? []); ?>;
    vaLocs.forEach(function(loc){
        if (loc.latitude && loc.longitude) {
            L.circleMarker([loc.latitude, loc.longitude], {
                radius: Math.min(loc.visitor_count * 2 + 5, 20),
                fillColor:'#8b5cf6', color:'#fff', weight:2, opacity:1, fillOpacity:0.6
            }).addTo(vaMap).bindPopup('<strong>'+loc.city+', '+loc.region+'</strong><br>'+loc.country+'<br><strong>'+loc.visitor_count+'</strong> visitor(s)');
        }
    });
    setTimeout(() => location.reload(), 60000);
</script>

<?php include '../includes/footer.php'; ?>
