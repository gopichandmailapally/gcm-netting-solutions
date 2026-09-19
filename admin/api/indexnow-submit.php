<?php
/**
 * IndexNow Submission API
 * Handles: generate_key, submit_sitemap, submit_all, submit_all_engines
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once __DIR__ . '/sitemap-functions.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

$root          = dirname(dirname(dirname(__FILE__)));
$settings_file = $root . '/data/gsc-settings.json';
$settings      = file_exists($settings_file) ? (json_decode(file_get_contents($settings_file), true) ?: []) : [];

$input  = json_decode(file_get_contents('php://input'), true) ?: [];
$action = $input['action'] ?? '';

$host        = parse_url(SITE_URL, PHP_URL_HOST);
$sitemap_url = SITE_URL . '/sitemap.xml';

// ── Helper: save settings ─────────────────────────────────────────
function save_gsc($settings, $file) {
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));
}

// ── Helper: ping URL (fire & forget) ─────────────────────────────
function ping_url($url) {
    $ctx = stream_context_create(['http' => ['timeout' => 8, 'ignore_errors' => true, 'method' => 'GET']]);
    $resp = @file_get_contents($url, false, $ctx);
    $code = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $m);
        $code = (int)($m[1] ?? 0);
    }
    return ['body' => $resp, 'code' => $code];
}

// ── Action: generate_key ──────────────────────────────────────────
if ($action === 'generate_key') {
    $key = bin2hex(random_bytes(16)); // 32-char hex
    $key_file = $root . '/' . $key . '.txt';

    if (!file_put_contents($key_file, $key)) {
        echo json_encode(['success' => false, 'message' => 'Cannot create key file — check server permissions']);
        exit;
    }

    // Remove old key file if exists
    if (!empty($settings['indexnow_key'])) {
        $old = $root . '/' . $settings['indexnow_key'] . '.txt';
        if (file_exists($old)) @unlink($old);
    }

    $settings['indexnow_key'] = $key;
    save_gsc($settings, $settings_file);

    echo json_encode(['success' => true, 'key' => $key, 'message' => "Key generated and file created: /{$key}.txt"]);
    exit;
}

// ── Shared: get IndexNow key ──────────────────────────────────────
$key = $settings['indexnow_key'] ?? '';
if (empty($key) && in_array($action, ['submit_sitemap','submit_all','submit_all_engines'])) {
    echo json_encode(['success' => false, 'message' => 'IndexNow key not generated. Please generate a key first.']);
    exit;
}

$key_location = SITE_URL . '/' . $key . '.txt';

// ── Helper: post to IndexNow API ──────────────────────────────────
function indexnow_post($host, $key, $key_location, $urls) {
    $body = json_encode([
        'host'        => $host,
        'key'         => $key,
        'keyLocation' => $key_location,
        'urlList'     => array_values($urls),
    ]);
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => "Content-Type: application/json\r\nContent-Length: " . strlen($body) . "\r\n",
        'content'       => $body,
        'timeout'       => 15,
        'ignore_errors' => true,
    ]]);
    $resp = @file_get_contents('https://api.indexnow.org/IndexNow', false, $ctx);
    $code = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        preg_match('/HTTP\/\d\.\d\s+(\d+)/', $http_response_header[0] ?? '', $m);
        $code = (int)($m[1] ?? 0);
    }
    return $code;
}

// ── Action: submit_sitemap ────────────────────────────────────────
if ($action === 'submit_sitemap') {
    $code = indexnow_post($host, $key, $key_location, [$sitemap_url, SITE_URL . '/']);
    $ok   = ($code >= 200 && $code < 300) || $code === 0;

    $settings['last_submit']      = date('Y-m-d H:i:s');
    $settings['last_submit_urls'] = 1;
    save_gsc($settings, $settings_file);

    echo json_encode(['success' => true, 'code' => $code, 'message' => "Sitemap submitted via IndexNow (code: {$code})"]);
    exit;
}

// ── Action: submit_all ────────────────────────────────────────────
if ($action === 'submit_all') {
    // Collect up to 10,000 URLs from sitemap.xml
    $urls  = [];
    $sm_path = $root . '/sitemap.xml';
    if (file_exists($sm_path)) {
        preg_match_all('/<loc>(.*?)<\/loc>/s', file_get_contents($sm_path), $m);
        $urls = array_map('html_entity_decode', $m[1] ?? []);
    }
    if (empty($urls)) $urls = [SITE_URL . '/'];

    // IndexNow max 10,000 per request
    $batches = array_chunk($urls, 10000);
    $submitted = 0;
    foreach ($batches as $batch) {
        $code = indexnow_post($host, $key, $key_location, $batch);
        $submitted += count($batch);
    }

    $settings['last_submit']      = date('Y-m-d H:i:s');
    $settings['last_submit_urls'] = $submitted;
    save_gsc($settings, $settings_file);

    echo json_encode(['success' => true, 'submitted' => $submitted, 'message' => "Submitted {$submitted} URLs to IndexNow"]);
    exit;
}

// ── Action: submit_all_engines ────────────────────────────────────
if ($action === 'submit_all_engines') {
    $results = [];

    // 1. Google ping
    $gPing   = ping_url('https://www.google.com/ping?sitemap=' . urlencode($sitemap_url));
    $results['google'] = $gPing['code'];

    // 2. Bing ping
    $bPing   = ping_url('https://www.bing.com/webmaster/ping.aspx?siteMap=' . urlencode($sitemap_url));
    $results['bing'] = $bPing['code'];

    // 3. IndexNow (if key available)
    if (!empty($key)) {
        $iCode = indexnow_post($host, $key, $key_location, [$sitemap_url, SITE_URL . '/']);
        $results['indexnow'] = $iCode;
    }

    $settings['last_submit']      = date('Y-m-d H:i:s');
    $settings['last_submit_urls'] = file_exists($root . '/sitemap.xml')
        ? substr_count(file_get_contents($root . '/sitemap.xml'), '<url>') : 0;
    save_gsc($settings, $settings_file);

    echo json_encode([
        'success' => true,
        'results' => $results,
        'message' => 'Sitemap submitted to Google, Bing' . (!empty($key) ? ', and IndexNow' : '') . '!',
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action: ' . $action]);
