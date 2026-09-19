<?php

define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$actions = $input['actions'] ?? ['sitemap', 'health_sample'];

$root = dirname(dirname(dirname(__FILE__)));

$result = [
    'success' => true,
    'actions_requested' => $actions,
    'actions' => [],
];

// 1) Generate sitemap
if (in_array('sitemap', $actions, true)) {
    try {
        require_once __DIR__ . '/sitemap-functions.php';
        if (function_exists('gcm_generate_sitemap')) {
            $sm = gcm_generate_sitemap();
            $result['actions']['sitemap'] = [
                'success' => (bool)($sm['success'] ?? true),
                'url_count' => (int)($sm['url_count'] ?? 0),
                'file' => $sm['file'] ?? 'sitemap.xml',
                'message' => $sm['message'] ?? 'Sitemap generated',
            ];
            if (!($result['actions']['sitemap']['success'])) {
                $result['success'] = false;
            }
        } else {
            $result['success'] = false;
            $result['actions']['sitemap'] = ['success' => false, 'error' => 'Sitemap generator function not found'];
        }
    } catch (\Throwable $e) {
        $result['success'] = false;
        $result['actions']['sitemap'] = ['success' => false, 'error' => $e->getMessage()];
    }
}

// 2) Quick URL health check (sample batches)
if (in_array('health_sample', $actions, true)) {
    $batchSize = (int)($input['batch_size'] ?? 200);
    if ($batchSize < 50) $batchSize = 50;
    if ($batchSize > 800) $batchSize = 800;

    $maxBatches = (int)($input['max_batches'] ?? 3);
    if ($maxBatches < 1) $maxBatches = 1;
    if ($maxBatches > 10) $maxBatches = 10;

    $mode = strtolower(trim((string)($input['mode'] ?? 'clean')));
    if (!in_array($mode, ['clean', 'php', 'both'], true)) $mode = 'clean';

    $includeStatic = (int)($input['include_static'] ?? 1) === 1;

    $summaryTotal = [
        'scanned' => 0,
        'http_200' => 0,
        'http_3xx' => 0,
        'http_4xx' => 0,
        'http_5xx' => 0,
        'other' => 0,
    ];

    $samples = [];

    try {
        $urls = [];

        if ($includeStatic) {
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

        $gen_dir = $root . '/generated-pages/';
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
        $offset = 0;
        for ($b = 0; $b < $maxBatches; $b++) {
            $batch = array_slice($urls, $offset, $batchSize);
            if (!$batch) break;

            foreach ($batch as $item) {
                $r = gcm_check_url_head($item['url']);
                $status = (int)($r['status'] ?? 0);

                $bucket = 'other';
                if ($status >= 200 && $status < 300) $bucket = 'http_200';
                elseif ($status >= 300 && $status < 400) $bucket = 'http_3xx';
                elseif ($status >= 400 && $status < 500) $bucket = 'http_4xx';
                elseif ($status >= 500 && $status < 600) $bucket = 'http_5xx';

                $summaryTotal['scanned']++;
                $summaryTotal[$bucket]++;

                $row = [
                    'source'        => $item['source'],
                    'path'          => $item['path'],
                    'url'           => $item['url'],
                    'status'        => $status,
                    'final_url'     => $r['final_url'] ?? '',
                    'redirect_hops' => $r['redirect_hops'] ?? 0,
                    'error'         => $r['error'] ?? '',
                ];

                if ($status >= 400 || ($status >= 300 && ($row['redirect_hops'] ?? 0) > 3)) {
                    $samples[] = $row;
                }
            }

            $offset += count($batch);
            if ($offset >= $total) break;
            if (count($samples) >= 50) break;
        }

        $samples = array_slice($samples, 0, 50);
        $result['actions']['health_sample'] = [
            'success' => true,
            'summary' => $summaryTotal,
            'checked_total' => $summaryTotal['scanned'],
            'notable' => $samples,
        ];
    } catch (\Throwable $e) {
        $result['success'] = false;
        $result['actions']['health_sample'] = ['success' => false, 'error' => $e->getMessage()];
    }
}

echo json_encode($result);

function gcm_check_url_head(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_NOBODY         => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 6,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT      => 'GCM-Technical-SEO-Fix/1.0 (+https://www.gcmsafetynets.in)',
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
