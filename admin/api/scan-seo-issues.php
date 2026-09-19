<?php
/**
 * Scan Generated Pages for SEO Issues
 * Returns grouped issue list without modifying any files
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

@set_time_limit(120);

$root    = dirname(dirname(dirname(__FILE__)));
$gen_dir = $root . '/generated-pages/';
$files   = is_dir($gen_dir) ? glob($gen_dir . '*.php') : [];

$issues = [
    'missing_meta_desc'  => [],
    'short_meta_desc'    => [],
    'missing_title'      => [],
    'missing_canonical'  => [],
    'thin_content'       => [],
];

$scanned = 0;

foreach ($files as $file) {
    $base = basename($file);
    if ($base === 'index.php') continue;

    $src = @file_get_contents($file);
    if ($src === false) continue;
    $scanned++;

    $slug  = pathinfo($base, PATHINFO_FILENAME);
    $entry = ['file' => $base, 'slug' => $slug, 'url' => SITE_URL . '/' . $slug];

    // Issue 1: missing or empty meta description
    if (!preg_match('/\$meta_description\s*=\s*[\'"](.+?)[\'"]/', $src, $m)) {
        $issues['missing_meta_desc'][] = $entry;
    } elseif (strlen($m[1]) < 50) {
        $issues['short_meta_desc'][] = $entry;
    }

    // Issue 2: missing or empty page title
    if (!preg_match('/\$(?:page_title|meta_title)\s*=\s*[\'"](.+?)[\'"]/', $src)) {
        $issues['missing_title'][] = $entry;
    }

    // Issue 3: missing canonical link
    if (stripos($src, 'rel="canonical"') === false && stripos($src, "rel='canonical'") === false) {
        $issues['missing_canonical'][] = $entry;
    }

    // Issue 4: thin content (fewer than 300 visible text words)
    $text_content = preg_replace('/<[^>]+>/', ' ', $src);
    $word_count   = str_word_count(strip_tags($text_content));
    if ($word_count < 300) {
        $issues['thin_content'][] = $entry;
    }
}

$summary = [
    'missing_meta_desc'  => ['label' => 'Missing Meta Description',  'count' => count($issues['missing_meta_desc']),  'severity' => 'high',   'icon' => 'fas fa-align-left'],
    'short_meta_desc'    => ['label' => 'Short Meta Description',     'count' => count($issues['short_meta_desc']),    'severity' => 'medium', 'icon' => 'fas fa-minus-circle'],
    'missing_title'      => ['label' => 'Missing Page Title',         'count' => count($issues['missing_title']),      'severity' => 'high',   'icon' => 'fas fa-heading'],
    'missing_canonical'  => ['label' => 'Missing Canonical URL',      'count' => count($issues['missing_canonical']),  'severity' => 'medium', 'icon' => 'fas fa-link'],
    'thin_content'       => ['label' => 'Thin Content (< 300 words)', 'count' => count($issues['thin_content']),       'severity' => 'low',    'icon' => 'fas fa-file-alt'],
];

echo json_encode([
    'success'  => true,
    'scanned'  => $scanned,
    'summary'  => $summary,
    'issues'   => $issues,
    'total_issues' => array_sum(array_column($summary, 'count')),
]);
