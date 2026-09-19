<?php
/**
 * Track Visitor API
 * Records visitor information with IP geolocation
 */

// Allow API access
define('GCM_INIT', true);

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/database.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Config error: ' . $e->getMessage()]);
    exit;
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);

$session_id = $input['session_id'] ?? null;
$page_url = $input['page_url'] ?? '';
$page_title = $input['page_title'] ?? '';
$referrer = $input['referrer'] ?? '';
$browser = $input['browser'] ?? 'Unknown';
$browser_version = $input['browser_version'] ?? '';
$os = $input['os'] ?? 'Unknown';
$device_type = $input['device_type'] ?? 'Desktop';

if (!$session_id) {
    echo json_encode(['success' => false, 'message' => 'Session ID required']);
    exit;
}

try {
    $db = Database::getInstance();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// ── Schema migration: add any missing columns ───────────────────
function migrateVisitorSchema($pdo) {
    // visitor_tracking columns
    $existing = [];
    foreach ($pdo->query("SHOW COLUMNS FROM visitor_tracking")->fetchAll(PDO::FETCH_ASSOC) as $c) $existing[] = $c['Field'];
    $add_vt = [];
    if (!in_array('session_id',       $existing)) $add_vt[] = "ADD COLUMN session_id VARCHAR(64) NOT NULL DEFAULT ''";
    if (!in_array('search_keyword',   $existing)) $add_vt[] = "ADD COLUMN search_keyword VARCHAR(255) DEFAULT NULL";
    if (!in_array('exit_page',        $existing)) $add_vt[] = "ADD COLUMN exit_page VARCHAR(500) DEFAULT NULL";
    if (!in_array('ip_address',       $existing)) $add_vt[] = "ADD COLUMN ip_address VARCHAR(45) DEFAULT NULL";
    if (!in_array('browser',          $existing)) $add_vt[] = "ADD COLUMN browser VARCHAR(100) DEFAULT NULL";
    if (!in_array('device_type',      $existing)) $add_vt[] = "ADD COLUMN device_type VARCHAR(20) DEFAULT 'desktop'";
    if (!in_array('city',             $existing)) $add_vt[] = "ADD COLUMN city VARCHAR(100) DEFAULT NULL";
    if (!in_array('country',          $existing)) $add_vt[] = "ADD COLUMN country VARCHAR(100) DEFAULT NULL";
    if (!in_array('country_code',     $existing)) $add_vt[] = "ADD COLUMN country_code VARCHAR(10) DEFAULT NULL";
    if (!in_array('region',           $existing)) $add_vt[] = "ADD COLUMN region VARCHAR(100) DEFAULT NULL";
    if (!in_array('latitude',         $existing)) $add_vt[] = "ADD COLUMN latitude DECIMAL(10,8) DEFAULT NULL";
    if (!in_array('longitude',        $existing)) $add_vt[] = "ADD COLUMN longitude DECIMAL(11,8) DEFAULT NULL";
    if (!in_array('timezone',         $existing)) $add_vt[] = "ADD COLUMN timezone VARCHAR(50) DEFAULT NULL";
    if (!in_array('isp',              $existing)) $add_vt[] = "ADD COLUMN isp VARCHAR(255) DEFAULT NULL";
    if (!in_array('current_page',     $existing)) $add_vt[] = "ADD COLUMN current_page VARCHAR(500) DEFAULT NULL";
    if (!in_array('entry_page',       $existing)) $add_vt[] = "ADD COLUMN entry_page VARCHAR(500) DEFAULT NULL";
    if (!in_array('referrer',         $existing)) $add_vt[] = "ADD COLUMN referrer VARCHAR(500) DEFAULT NULL";
    if (!in_array('page_views',       $existing)) $add_vt[] = "ADD COLUMN page_views INT DEFAULT 1";
    if (!in_array('total_time_spent', $existing)) $add_vt[] = "ADD COLUMN total_time_spent INT DEFAULT 0";
    if (!in_array('is_online',        $existing)) $add_vt[] = "ADD COLUMN is_online TINYINT(1) DEFAULT 0";
    if (!in_array('user_agent',       $existing)) $add_vt[] = "ADD COLUMN user_agent TEXT";
    if (!in_array('os',               $existing)) $add_vt[] = "ADD COLUMN os VARCHAR(100) DEFAULT NULL";
    if (!in_array('browser_version',  $existing)) $add_vt[] = "ADD COLUMN browser_version VARCHAR(50) DEFAULT NULL";
    if (!in_array('first_visit',      $existing)) $add_vt[] = "ADD COLUMN first_visit DATETIME DEFAULT CURRENT_TIMESTAMP";
    if (!in_array('last_activity',    $existing)) $add_vt[] = "ADD COLUMN last_activity DATETIME DEFAULT CURRENT_TIMESTAMP";
    if (!empty($add_vt)) {
        try { $pdo->exec("ALTER TABLE visitor_tracking " . implode(', ', $add_vt)); } catch (\Exception $e) {}
    }
    // Add unique index on session_id if missing
    try {
        $idx = $pdo->query("SHOW INDEX FROM visitor_tracking WHERE Key_name='idx_session'")->fetch();
        if (!$idx) $pdo->exec("ALTER TABLE visitor_tracking ADD UNIQUE KEY idx_session (session_id)");
    } catch (\Exception $e) {}

    // page_views columns
    $existing2 = [];
    try { foreach ($pdo->query("SHOW COLUMNS FROM page_views")->fetchAll(PDO::FETCH_ASSOC) as $c) $existing2[] = $c['Field']; } catch (\Exception $e) {}
    $add_pv = [];
    if (!in_array('time_spent',   $existing2)) $add_pv[] = "ADD COLUMN time_spent INT DEFAULT 0";
    if (!in_array('scroll_depth', $existing2)) $add_pv[] = "ADD COLUMN scroll_depth INT DEFAULT 0";
    if (!empty($add_pv)) {
        try { $pdo->exec("ALTER TABLE page_views " . implode(', ', $add_pv)); } catch (\Exception $e) {}
    }
}

// ── Extract search keyword from referrer URL ─────────────────────
function extractSearchKeyword($referrer) {
    if (empty($referrer)) return '';
    $parsed = parse_url($referrer);
    $host   = strtolower($parsed['host'] ?? '');
    $search_engines = ['google', 'bing', 'yahoo', 'duckduckgo', 'yandex', 'baidu', 'ask.com'];
    foreach ($search_engines as $se) {
        if (strpos($host, $se) !== false) {
            parse_str($parsed['query'] ?? '', $params);
            $kw = trim($params['q'] ?? $params['query'] ?? $params['p'] ?? $params['text'] ?? '');
            return $kw;
        }
    }
    return '';
}

// Get visitor IP address
function getVisitorIP() {
    $ip = '';
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Get geolocation data from IP
function getGeolocation($ip) {
    // Using ip-api.com (free, no API key needed)
    $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,city,lat,lon,timezone,isp";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $data = json_decode($response, true);
        if ($data && $data['status'] === 'success') {
            return [
                'country' => $data['country'] ?? 'Unknown',
                'country_code' => $data['countryCode'] ?? 'XX',
                'region' => $data['region'] ?? '',
                'city' => $data['city'] ?? 'Unknown',
                'latitude' => $data['lat'] ?? 0,
                'longitude' => $data['lon'] ?? 0,
                'timezone' => $data['timezone'] ?? '',
                'isp' => $data['isp'] ?? ''
            ];
        }
    }
    
    // Fallback to default location
    return [
        'country' => 'Unknown',
        'country_code' => 'XX',
        'region' => '',
        'city' => 'Unknown',
        'latitude' => 0,
        'longitude' => 0,
        'timezone' => '',
        'isp' => ''
    ];
}

try {
    // ── Auto-create tables ────────────────────────────────
    $pdo = $db->getConnection();
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitor_tracking (
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
        is_online TINYINT(1) DEFAULT 1, page_views INT DEFAULT 1,
        total_time_spent INT DEFAULT 0,
        first_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY idx_session (session_id), KEY idx_first_visit (first_visit), KEY idx_is_online (is_online)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS page_views (
        id INT AUTO_INCREMENT PRIMARY KEY, session_id VARCHAR(64),
        page_url VARCHAR(500), `timestamp` DATETIME DEFAULT CURRENT_TIMESTAMP,
        KEY idx_session (session_id), KEY idx_timestamp (`timestamp`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS visitor_heatmap (
        id INT AUTO_INCREMENT PRIMARY KEY,
        country_code VARCHAR(10), country_name VARCHAR(100),
        visitor_count INT DEFAULT 0, last_visit DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY idx_country (country_code)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ip_address = getVisitorIP();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    // Get geolocation
    $geo = getGeolocation($ip_address);
    
    // Run schema migration to fix any missing columns
    migrateVisitorSchema($pdo);

    // Extract search keyword from referrer
    $search_keyword = extractSearchKeyword($referrer);

    // Check if visitor exists
    $existing = $db->fetchOne(
        "SELECT id FROM visitor_tracking WHERE session_id = ?",
        [$session_id],
        's'
    );
    
    // Always record the page view in page_views table
    $db->execute(
        "INSERT INTO page_views (session_id, page_url, `timestamp`) VALUES (?, ?, NOW())",
        [$session_id, $page_url]
    );

    if ($existing) {
        // Update existing visitor — update exit page tracking
        $db->execute(
            "UPDATE visitor_tracking SET 
             current_page = ?,
             exit_page = ?,
             last_activity = NOW(),
             page_views = page_views + 1,
             is_online = 1
             WHERE session_id = ?",
            [$page_url, $page_url, $session_id],
            'sss'
        );
    } else {
        // Insert new visitor
        $db->execute(
            "INSERT INTO visitor_tracking (
                session_id, ip_address, user_agent, browser, browser_version,
                device_type, os, country, country_code, region, city,
                latitude, longitude, timezone, isp, current_page, entry_page,
                exit_page, referrer, search_keyword, is_online
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [
                $session_id, $ip_address, $user_agent, $browser, $browser_version,
                $device_type, $os, $geo['country'], $geo['country_code'], $geo['region'],
                $geo['city'], $geo['latitude'], $geo['longitude'], $geo['timezone'],
                $geo['isp'], $page_url, $page_url, $page_url, $referrer, $search_keyword
            ],
            'sssssssssssddsssssss'
        );
        
        // Update heatmap
        $heatmap = $db->fetchOne(
            "SELECT id, visitor_count FROM visitor_heatmap WHERE country_code = ?",
            [$geo['country_code']],
            's'
        );
        
        if ($heatmap) {
            $db->execute(
                "UPDATE visitor_heatmap SET visitor_count = visitor_count + 1, last_visit = NOW() WHERE country_code = ?",
                [$geo['country_code']],
                's'
            );
        } else {
            $db->execute(
                "INSERT INTO visitor_heatmap (country_code, country_name, visitor_count) VALUES (?, ?, 1)",
                [$geo['country_code'], $geo['country']],
                'ss'
            );
        }
    }
    
    echo json_encode([
        'success' => true,
        'session_id' => $session_id,
        'ip' => $ip_address,
        'location' => [
            'country' => $geo['country'],
            'city' => $geo['city'],
            'latitude' => $geo['latitude'],
            'longitude' => $geo['longitude']
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

/* ── Pseudo-cron: ~1 in 5 visitors trigger the daily content schedule ───
 * fastcgi_finish_request() sends the response to the browser first,
 * so this has ZERO impact on page load speed for the visitor.         */
if (rand(1, 5) === 1) {
    if (function_exists('fastcgi_finish_request')) fastcgi_finish_request();
    define('PCRON_INCLUDED', 1);
    @include_once __DIR__ . '/pseudo-cron.php';
    if (function_exists('_pcron_check')) _pcron_check();
}
