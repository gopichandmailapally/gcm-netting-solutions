<?php
/**
 * URL Health Checker API
 * Batch-checks site URLs (200/3xx/4xx/5xx) using HEAD requests.
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'scan';
if ($action !== 'scan') {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit;
}

$batchSize = (int)($_POST['batch_size'] ?? $_GET['batch_size'] ?? 200);
if ($batchSize < 10) $batchSize = 10;
if ($batchSize > 800) $batchSize = 800;

$offset = (int)($_POST['offset'] ?? $_GET['offset'] ?? 0);
if ($offset < 0) $offset = 0;

$mode = strtolower(trim((string)($_POST['mode'] ?? $_GET['mode'] ?? 'clean')));
if (!in_array($mode, ['clean', 'php', 'both'], true)) $mode = 'clean';

$include_static = (int)($_POST['include_static'] ?? $_GET['include_static'] ?? 1) === 1;

$root_dir = dirname(dirname(__DIR__));

$urls = [];

// Static pages
if ($include_static) {
    $static = [
        '',
        'about.php',
        'contact.php',
        'faqs.php',
        'gallery.php',
        'reviews.php',
        'blogs.php',
        'all-areas.php',
        'estimation.php',
        'privacy-policy.php',
        'terms-conditions.php',
        'videos.php',
    ];
    foreach ($static as $path) {
        $urls[] = [
            'source' => 'static',
            'path'   => $path === '' ? '/' : '/' . $path,
            'url'    => rtrim(SITE_URL, '/') . '/' . $path,
        ];
    }
}

// Generated pages from filesystem
$gen_dir = $root_dir . '/generated-pages/';
$gen_files = is_dir($gen_dir) ? (glob($gen_dir . '*.php') ?: []) : [];
foreach ($gen_files as $file) {
    $base = basename($file);
    if ($base === 'index.php') continue;

    $slug = pathinfo($base, PATHINFO_FILENAME);

    if ($mode === 'clean' || $mode === 'both') {
        $urls[] = [
            'source' => 'generated_clean',
            'path'   => '/' . $slug,
            'url'    => rtrim(SITE_URL, '/') . '/' . $slug,
        ];
    }
    if ($mode === 'php' || $mode === 'both') {
        $urls[] = [
            'source' => 'generated_php',
            'path'   => '/' . $slug . '.php',
            'url'    => rtrim(SITE_URL, '/') . '/' . $slug . '.php',
        ];
    }
}

$total = count($urls);
if ($offset >= $total) {
    echo json_encode([
        'success' => true,
        'message' => 'No more URLs to scan',
        'total'   => $total,
        'offset'  => $offset,
        'next_offset' => $offset,
        'done'    => true,
        'summary' => [
            'scanned' => 0,
            'http_200' => 0,
            'http_3xx' => 0,
            'http_4xx' => 0,
            'http_5xx' => 0,
            'other'    => 0,
        ],
        'results' => [],
    ]);
    exit;
}

$batch = array_slice($urls, $offset, $batchSize);

$results = [];
$summary = [
    'scanned'   => 0,
    'http_200'  => 0,
    'http_3xx'  => 0,
    'http_4xx'  => 0,
    'http_5xx'  => 0,
    'other'     => 0,
];

foreach ($batch as $item) {
    $r = gcm_check_url_head($item['url']);
    $status = (int)($r['status'] ?? 0);

    $bucket = 'other';
    if ($status >= 200 && $status < 300) $bucket = 'http_200';
    elseif ($status >= 300 && $status < 400) $bucket = 'http_3xx';
    elseif ($status >= 400 && $status < 500) $bucket = 'http_4xx';
    elseif ($status >= 500 && $status < 600) $bucket = 'http_5xx';

    $summary['scanned']++;
    $summary[$bucket]++;

    $results[] = [
        'source'        => $item['source'],
        'path'          => $item['path'],
        'url'           => $item['url'],
        'status'        => $status,
        'final_url'     => $r['final_url'] ?? '',
        'redirect_hops' => $r['redirect_hops'] ?? 0,
        'error'         => $r['error'] ?? '',
    ];
}

$nextOffset = $offset + count($batch);

echo json_encode([
    'success' => true,
    'total'   => $total,
    'offset'  => $offset,
    'batch_size' => $batchSize,
    'next_offset' => $nextOffset,
    'done'    => $nextOffset >= $total,
    'mode'    => $mode,
    'include_static' => $include_static,
    'summary' => $summary,
    'results' => $results,
]);

function gcm_check_url_head(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 6,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'GCM-URL-Health-Checker/1.0 (+https://www.gcmsafetynets.in)',
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    curl_exec($ch);

    $errNo = curl_errno($ch);
    $err   = $errNo ? (curl_error($ch) ?: ('cURL error ' . $errNo)) : '';

    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $final  = (string)curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $hops   = (int)curl_getinfo($ch, CURLINFO_REDIRECT_COUNT);

    curl_close($ch);

    return [
        'status'        => $status,
        'final_url'     => $final,
        'redirect_hops' => $hops,
        'error'         => $err,
    ];
}
