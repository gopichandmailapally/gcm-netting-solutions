<?php
/**
 * Google Search Console API Integration
 * Fetches real ranking data, clicks, impressions, and CTR from GSC
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'sync':
        syncSearchConsoleData();
        break;
    
    case 'get_rankings':
        getRankings();
        break;
    
    case 'get_performance':
        getPerformanceData();
        break;

    case 'get_sitemap_status':
        getSitemapStatus();
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Sync data from Google Search Console
 */
function syncSearchConsoleData() {
    $db           = Database::getInstance();
    $root         = dirname(dirname(dirname(__FILE__)));
    $settings_file= $root . '/data/gsc-settings.json';
    $tokens_file  = $root . '/data/gsc-tokens.json';

    $settings = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
    $tokens   = file_exists($tokens_file)   ? (json_decode(file_get_contents($tokens_file),   true) ?: []) : [];

    if (empty($tokens['access_token'])) {
        echo json_encode(['success' => false, 'message' => 'Google Search Console API not connected. Go to Search Console page and authorize with Google.', 'need_auth' => true]);
        return;
    }

    // Auto-refresh token if expired
    $obtained_at = $tokens['obtained_at'] ?? 0;
    $expires_in  = $tokens['expires_in']  ?? 3600;
    if (time() > ($obtained_at + $expires_in - 60) && !empty($tokens['refresh_token'])) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'refresh_token' => $tokens['refresh_token'],
                'client_id'     => $settings['oauth_client_id']     ?? '',
                'client_secret' => $settings['oauth_client_secret'] ?? '',
                'grant_type'    => 'refresh_token',
            ]),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $ref = json_decode(curl_exec($ch), true) ?: [];
        curl_close($ch);
        if (!empty($ref['access_token'])) {
            $tokens['access_token'] = $ref['access_token'];
            $tokens['obtained_at']  = time();
            $tokens['expires_in']   = $ref['expires_in'] ?? 3600;
            file_put_contents($tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
        }
    }

    try {
        $site_url   = $settings['property_url'] ?? SITE_URL . '/';
        $end_date   = date('Y-m-d');
        $start_date = date('Y-m-d', strtotime('-28 days'));

        $search_analytics = fetchSearchAnalytics($tokens['access_token'], $site_url, $start_date, $end_date);

        $synced_count = 0;
        foreach ($search_analytics as $row) {
            $db->execute(
                "INSERT INTO seo_rankings (page_url, keyword, position, clicks, impressions, ctr, last_updated, is_real_data)
                 VALUES (?, ?, ?, ?, ?, ?, NOW(), 1)
                 ON DUPLICATE KEY UPDATE
                   position=VALUES(position), clicks=VALUES(clicks),
                   impressions=VALUES(impressions), ctr=VALUES(ctr),
                   last_updated=NOW(), is_real_data=1",
                [$row['page'], $row['query'], $row['position'], $row['clicks'], $row['impressions'], $row['ctr']]
            );
            $synced_count++;
        }

        // Save sync timestamp
        $settings['last_sync'] = date('Y-m-d H:i:s');
        file_put_contents($settings_file, json_encode($settings, JSON_PRETTY_PRINT));

        echo json_encode(['success' => true, 'message' => "Synced {$synced_count} ranking records from Google Search Console ({$start_date} to {$end_date})", 'synced_count' => $synced_count]);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
    }
}


/**
 * Fetch Search Analytics data from Google Search Console
 */
function fetchSearchAnalytics($access_token, $site_url, $start_date, $end_date) {
    $api_url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/searchAnalytics/query';
    
    $request_body = [
        'startDate' => $start_date,
        'endDate' => $end_date,
        'dimensions' => ['page', 'query'],
        'rowLimit' => 1000
    ];
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($request_body));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    $results = [];
    if (isset($data['rows'])) {
        foreach ($data['rows'] as $row) {
            $results[] = [
                'page' => $row['keys'][0],
                'query' => $row['keys'][1],
                'clicks' => $row['clicks'],
                'impressions' => $row['impressions'],
                'ctr' => $row['ctr'] * 100,
                'position' => round($row['position'])
            ];
        }
    }
    
    return $results;
}

/**
 * Get sitemap submission status from Google Search Console Sitemaps API
 */
function getSitemapStatus() {
    $root          = dirname(dirname(dirname(__FILE__)));
    $settings_file = $root . '/data/gsc-settings.json';
    $tokens_file   = $root . '/data/gsc-tokens.json';

    $settings = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];
    $tokens   = file_exists($tokens_file)   ? (json_decode(file_get_contents($tokens_file),   true) ?: []) : [];

    if (empty($tokens['access_token'])) {
        echo json_encode(['success' => false, 'message' => 'GSC not connected. Connect via OAuth first.', 'need_auth' => true]);
        return;
    }

    // Auto-refresh token if expired
    $obtained_at = $tokens['obtained_at'] ?? 0;
    $expires_in  = $tokens['expires_in']  ?? 3600;
    if (time() > ($obtained_at + $expires_in - 60) && !empty($tokens['refresh_token'])) {
        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query([
                'refresh_token' => $tokens['refresh_token'],
                'client_id'     => $settings['oauth_client_id']     ?? '',
                'client_secret' => $settings['oauth_client_secret'] ?? '',
                'grant_type'    => 'refresh_token',
            ]),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $ref = json_decode(curl_exec($ch), true) ?: [];
        curl_close($ch);
        if (!empty($ref['access_token'])) {
            $tokens['access_token'] = $ref['access_token'];
            $tokens['obtained_at']  = time();
            $tokens['expires_in']   = $ref['expires_in'] ?? 3600;
            file_put_contents($tokens_file, json_encode($tokens, JSON_PRETTY_PRINT));
        }
    }

    $site_url     = rtrim($settings['property_url'] ?? SITE_URL . '/', '/');
    $encoded_site = urlencode($site_url . '/');
    $api_base     = 'https://www.googleapis.com/webmasters/v3/sites/' . $encoded_site;

    $headers = [
        'Authorization: Bearer ' . $tokens['access_token'],
        'Accept: application/json',
    ];

    // Fetch list of all submitted sitemaps
    $ch = curl_init($api_base . '/sitemaps');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT        => 20,
    ]);
    $raw  = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http === 401 || $http === 403) {
        echo json_encode(['success' => false, 'message' => 'Access denied (HTTP ' . $http . '). Token may be expired — try reconnecting GSC.']);
        return;
    }

    $data = json_decode($raw, true);

    if (isset($data['error'])) {
        echo json_encode(['success' => false, 'message' => $data['error']['message'] ?? 'GSC API error']);
        return;
    }

    $sitemaps_raw = $data['sitemap'] ?? [];
    $sitemaps     = [];

    foreach ($sitemaps_raw as $sm) {
        $submitted = 0;
        $indexed   = 0;
        foreach ($sm['contents'] ?? [] as $c) {
            $submitted += (int)($c['submitted'] ?? 0);
            $indexed   += (int)($c['indexed']   ?? 0);
        }

        $last_submitted  = $sm['lastSubmitted']  ?? '';
        $last_downloaded = $sm['lastDownloaded'] ?? '';

        // Convert ISO 8601 to human-readable IST
        $fmt_sub  = $last_submitted  ? date('d M Y, h:i A', strtotime($last_submitted))  : 'Never';
        $fmt_dl   = $last_downloaded ? date('d M Y, h:i A', strtotime($last_downloaded)) : 'Not yet downloaded';

        // How long ago was last download?
        $dl_ago   = '';
        if ($last_downloaded) {
            $diff = time() - strtotime($last_downloaded);
            if ($diff < 3600)      $dl_ago = round($diff / 60)   . ' min ago';
            elseif ($diff < 86400) $dl_ago = round($diff / 3600) . ' hours ago';
            else                   $dl_ago = round($diff / 86400) . ' days ago';
        }

        $warnings = (int)($sm['warnings'] ?? 0);
        $errors   = (int)($sm['errors']   ?? 0);
        $pending  = !empty($sm['isPending']);
        $index_pct = $submitted > 0 ? round(($indexed / $submitted) * 100) : 0;

        $sitemaps[] = [
            'path'            => $sm['path'] ?? '',
            'type'            => $sm['type'] ?? 'web',
            'is_index'        => !empty($sm['isSitemapsIndex']),
            'is_pending'      => $pending,
            'last_submitted'  => $fmt_sub,
            'last_downloaded' => $fmt_dl,
            'downloaded_ago'  => $dl_ago,
            'submitted'       => $submitted,
            'indexed'         => $indexed,
            'index_pct'       => $index_pct,
            'warnings'        => $warnings,
            'errors'          => $errors,
            'status'          => $errors > 0 ? 'error' : ($pending ? 'pending' : ($warnings > 0 ? 'warning' : 'ok')),
        ];
    }

    echo json_encode([
        'success'     => true,
        'site_url'    => $site_url,
        'sitemaps'    => $sitemaps,
        'total'       => count($sitemaps),
        'checked_at'  => date('d M Y, h:i A'),
    ]);
}

/**
 * Get current rankings from database
 */
function getRankings() {
    $db = Database::getInstance();
    
    $limit = $_GET['limit'] ?? 50;
    $position_filter = $_GET['position'] ?? '';
    
    $where = '';
    if ($position_filter === '1') {
        $where = 'WHERE position = 1';
    } elseif ($position_filter === '1-3') {
        $where = 'WHERE position BETWEEN 1 AND 3';
    } elseif ($position_filter === '1-10') {
        $where = 'WHERE position BETWEEN 1 AND 10';
    } elseif ($position_filter === '11-20') {
        $where = 'WHERE position BETWEEN 11 AND 20';
    }
    
    $rankings = $db->fetchAll(
        "SELECT * FROM seo_rankings {$where} ORDER BY clicks DESC LIMIT ?",
        [$limit]
    );
    
    echo json_encode([
        'success' => true,
        'rankings' => $rankings ?? []
    ]);
}

/**
 * Get performance metrics
 */
function getPerformanceData() {
    $db = Database::getInstance();
    
    $stats = [
        'total_clicks' => $db->fetchOne("SELECT SUM(clicks) as total FROM seo_rankings")['total'] ?? 0,
        'total_impressions' => $db->fetchOne("SELECT SUM(impressions) as total FROM seo_rankings")['total'] ?? 0,
        'avg_position' => $db->fetchOne("SELECT AVG(position) as avg FROM seo_rankings")['avg'] ?? 0,
        'avg_ctr' => $db->fetchOne("SELECT AVG(ctr) as avg FROM seo_rankings")['avg'] ?? 0,
        'rank_1_pages' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position = 1")['count'] ?? 0,
        'top_3_pages' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position BETWEEN 1 AND 3")['count'] ?? 0,
        'page_1_pages' => $db->fetchOne("SELECT COUNT(*) as count FROM seo_rankings WHERE position BETWEEN 1 AND 10")['count'] ?? 0
    ];
    
    echo json_encode([
        'success' => true,
        'performance' => $stats
    ]);
}
