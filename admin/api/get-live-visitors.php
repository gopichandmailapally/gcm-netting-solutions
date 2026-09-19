<?php
/**
 * Get Live Visitors API
 * Returns real-time visitor data for admin dashboard
 */

define('GCM_INIT', true);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = Database::getInstance();

try {
    // ── Auto-create tables if not yet present ────────────
    $pdo = $db->getConnection();
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitor_tracking (
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
        KEY idx_is_online (is_online)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY,
        session_id VARCHAR(64),
        page_url VARCHAR(500),
        `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_session (session_id),
        KEY idx_timestamp (`timestamp`),
        KEY idx_page (page_url(255))
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS visitor_heatmap (
        id INT AUTO_INCREMENT PRIMARY KEY,
        country_code VARCHAR(10),
        country_name VARCHAR(100),
        visitor_count INT DEFAULT 0,
        last_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_country (country_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // ── Schema migration: add each column individually (safe) ──
    $cols = [];
    $cols_r = $pdo->query("SHOW COLUMNS FROM visitor_tracking");
    if ($cols_r) foreach ($cols_r->fetchAll(PDO::FETCH_ASSOC) as $c) $cols[] = $c['Field'];

    // Add ONE column at a time so a single failure doesn't block others
    $needed = [
        'session_id'      => "ALTER TABLE visitor_tracking ADD COLUMN session_id VARCHAR(64) NOT NULL DEFAULT ''",
        'search_keyword'  => 'ALTER TABLE visitor_tracking ADD COLUMN search_keyword VARCHAR(255) DEFAULT NULL',
        'exit_page'       => 'ALTER TABLE visitor_tracking ADD COLUMN exit_page VARCHAR(500) DEFAULT NULL',
        'entry_page'      => 'ALTER TABLE visitor_tracking ADD COLUMN entry_page VARCHAR(500) DEFAULT NULL',
        'referrer'        => 'ALTER TABLE visitor_tracking ADD COLUMN referrer VARCHAR(500) DEFAULT NULL',
        'region'          => 'ALTER TABLE visitor_tracking ADD COLUMN region VARCHAR(100) DEFAULT NULL',
        'country_code'    => 'ALTER TABLE visitor_tracking ADD COLUMN country_code VARCHAR(10) DEFAULT NULL',
        'city'            => 'ALTER TABLE visitor_tracking ADD COLUMN city VARCHAR(100) DEFAULT NULL',
        'country'         => 'ALTER TABLE visitor_tracking ADD COLUMN country VARCHAR(100) DEFAULT NULL',
        'ip_address'      => 'ALTER TABLE visitor_tracking ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL',
        'browser'         => 'ALTER TABLE visitor_tracking ADD COLUMN browser VARCHAR(100) DEFAULT NULL',
        'os'              => 'ALTER TABLE visitor_tracking ADD COLUMN os VARCHAR(100) DEFAULT NULL',
        'isp'             => 'ALTER TABLE visitor_tracking ADD COLUMN isp VARCHAR(255) DEFAULT NULL',
        'timezone'        => 'ALTER TABLE visitor_tracking ADD COLUMN timezone VARCHAR(50) DEFAULT NULL',
        'latitude'        => 'ALTER TABLE visitor_tracking ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL',
        'longitude'       => 'ALTER TABLE visitor_tracking ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL',
        'device_type'     => "ALTER TABLE visitor_tracking ADD COLUMN device_type VARCHAR(20) DEFAULT 'desktop'",
        'page_views'      => 'ALTER TABLE visitor_tracking ADD COLUMN page_views INT DEFAULT 1',
        'total_time_spent'=> 'ALTER TABLE visitor_tracking ADD COLUMN total_time_spent INT DEFAULT 0',
        'first_visit'     => 'ALTER TABLE visitor_tracking ADD COLUMN first_visit DATETIME DEFAULT CURRENT_TIMESTAMP',
        'last_activity'   => 'ALTER TABLE visitor_tracking ADD COLUMN last_activity DATETIME DEFAULT CURRENT_TIMESTAMP',
    ];
    foreach ($needed as $col => $sql) {
        if (!in_array($col, $cols)) {
            try { $pdo->exec($sql); $cols[] = $col; } catch (\Exception $e) {}
        }
    }
    // session_id unique index
    try {
        $idx_r = $pdo->query("SHOW INDEX FROM visitor_tracking WHERE Key_name='idx_session'");
        if ($idx_r && !$idx_r->fetch()) $pdo->exec("ALTER TABLE visitor_tracking ADD UNIQUE KEY idx_session (session_id)");
    } catch (\Exception $e) {}
    // page_views extra columns
    $cols2 = [];
    try { $pv_r = $pdo->query("SHOW COLUMNS FROM page_views"); if ($pv_r) foreach ($pv_r->fetchAll(PDO::FETCH_ASSOC) as $c) $cols2[] = $c['Field']; } catch (\Exception $e) {}
    foreach (['time_spent' => 'ADD COLUMN time_spent INT DEFAULT 0', 'scroll_depth' => 'ADD COLUMN scroll_depth INT DEFAULT 0'] as $c2 => $s2) {
        if (!in_array($c2, $cols2)) { try { $pdo->exec("ALTER TABLE page_views $s2"); } catch (\Exception $e) {} }
    }

    // ── Re-read columns after migration, build SELECT with fallbacks ──
    $cols_now = [];
    $cr = $pdo->query("SHOW COLUMNS FROM visitor_tracking");
    if ($cr) foreach ($cr->fetchAll(PDO::FETCH_ASSOC) as $c) $cols_now[] = $c['Field'];

    $s_exit   = in_array('exit_page',      $cols_now) ? 'exit_page'      : 'current_page AS exit_page';
    $s_kw     = in_array('search_keyword', $cols_now) ? 'search_keyword' : "'' AS search_keyword";
    $s_os     = in_array('os',             $cols_now) ? 'os'             : "'' AS os";
    $s_lat    = in_array('latitude',       $cols_now) ? 'latitude'       : 'NULL AS latitude';
    $s_lng    = in_array('longitude',      $cols_now) ? 'longitude'      : 'NULL AS longitude';
    $s_tz     = in_array('timezone',       $cols_now) ? 'timezone'       : "'' AS timezone";
    $s_isp    = in_array('isp',            $cols_now) ? 'isp'            : "'' AS isp";
    $s_entry  = in_array('entry_page',     $cols_now) ? 'entry_page'     : 'current_page AS entry_page';
    $s_ref    = in_array('referrer',       $cols_now) ? 'referrer'       : "'' AS referrer";
    $s_region = in_array('region',         $cols_now) ? 'region'         : "'' AS region";
    $s_cc     = in_array('country_code',   $cols_now) ? 'country_code'   : "'' AS country_code";

    // Online = last_activity within 5 minutes
    $visitors = $db->fetchAll("
        SELECT
            id, session_id, ip_address, browser, device_type, {$s_os},
            country, {$s_cc}, {$s_region}, city, {$s_lat}, {$s_lng},
            {$s_tz}, {$s_isp}, current_page, {$s_entry}, {$s_exit}, {$s_ref},
            {$s_kw}, page_views, total_time_spent, last_activity, first_visit
        FROM visitor_tracking
        WHERE last_activity >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY last_activity DESC
    ");

    // Statistics
    $stats = [
        'online_now'             => count($visitors),
        'today'                  => (int)($db->fetchOne("SELECT COUNT(DISTINCT session_id) AS c FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()")['c'] ?? 0),
        'total_page_views_today' => (int)($db->fetchOne("SELECT COUNT(*) AS c FROM page_views WHERE DATE(`timestamp`) = CURDATE()")['c'] ?? 0),
        'avg_time_today'         => (float)($db->fetchOne("SELECT AVG(total_time_spent) AS avg FROM visitor_tracking WHERE DATE(first_visit) = CURDATE() AND total_time_spent > 0")['avg'] ?? 0),
    ];

    // If page_views table is empty, fall back to summing page_views column
    if ($stats['total_page_views_today'] === 0) {
        $stats['total_page_views_today'] = (int)($db->fetchOne("SELECT COALESCE(SUM(page_views),0) AS c FROM visitor_tracking WHERE DATE(first_visit) = CURDATE()")['c'] ?? 0);
    }

    // Top pages today
    $top_pages = $db->fetchAll("
        SELECT page_url, COUNT(*) as views, COUNT(DISTINCT session_id) as unique_visitors
        FROM page_views 
        WHERE DATE(`timestamp`) = CURDATE()
        GROUP BY page_url 
        ORDER BY views DESC 
        LIMIT 10
    ");

    // Country distribution today
    $countries = $db->fetchAll("
        SELECT country, country_code, COUNT(DISTINCT session_id) as visitor_count
        FROM visitor_tracking 
        WHERE DATE(first_visit) = CURDATE() AND country IS NOT NULL AND country != ''
        GROUP BY country, country_code
        ORDER BY visitor_count DESC
        LIMIT 15
    ");
    
    // Format visitors data
    foreach ($visitors as &$visitor) {
        $visitor['time_on_site'] = formatTime($visitor['total_time_spent']);
        $visitor['time_since_visit'] = timeAgo($visitor['first_visit']);
        $visitor['last_seen'] = timeAgo($visitor['last_activity']);
        $visitor['page_title'] = getPageTitle($visitor['current_page']);
    }
    
    echo json_encode([
        'success' => true,
        'visitors' => $visitors,
        'stats' => $stats,
        'top_pages' => $top_pages,
        'countries' => $countries,
        'timestamp' => time()
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function formatTime($seconds) {
    if ($seconds < 60) return $seconds . 's';
    if ($seconds < 3600) return floor($seconds / 60) . 'm ' . ($seconds % 60) . 's';
    return floor($seconds / 3600) . 'h ' . floor(($seconds % 3600) / 60) . 'm';
}

function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return $diff . ' seconds ago';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return floor($diff / 86400) . ' days ago';
}

function getPageTitle($url) {
    $titles = [
        '/' => 'Home Page',
        '/about.php' => 'About Us',
        '/contact.php' => 'Contact',
        '/services.php' => 'Services',
        '/blog.php' => 'Blog',
    ];
    
    foreach ($titles as $path => $title) {
        if (strpos($url, $path) !== false) return $title;
    }
    
    if (strpos($url, '/service-pages/') !== false) return 'Service Page';
    if (strpos($url, '/blog/') !== false) return 'Blog Post';
    
    return 'Unknown Page';
}
