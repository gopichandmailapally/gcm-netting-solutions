<?php
/**
 * diagnose-layout.php — Layout Diagnostic Tool
 * Reads a specific generated page and reports its actual structure
 * so we can find the REAL cause of the sidebar layout issue.
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

// Read-only diagnostic — no auth required (delete this file after use)

$root    = dirname(dirname(dirname(__FILE__)));
$gen_dir = $root . '/generated-pages/';

$file = trim($_GET['file'] ?? $_POST['file'] ?? '');
if ($file === '') {
    // No file specified — list all pages and show div net for each, plus first broken one
    $all = glob($gen_dir . '*.php') ?: [];
    $all = array_values(array_filter($all, fn($f) => basename($f) !== 'index.php'));

    $broken_samples = [];
    $structure_types = [];

    foreach ($all as $f) {
        $src = @file_get_contents($f);
        if ($src === false) continue;

        // Check for different possible layout patterns
        $has_gcm_grid    = (strpos($src, '<div class="gcm-grid">') !== false);
        $has_gcm_sidebar = (strpos($src, '<div class="gcm-sidebar">') !== false);
        $has_gcm_main    = (strpos($src, '<div class="gcm-main">') !== false);
        $has_svc_content = (strpos($src, '<div class="service-content">') !== false);

        // Also check for grid with extra classes / attributes
        $has_gcm_grid_alt = preg_match('/<div[^>]+class=["\'][^"\']*gcm-grid/', $src);

        $type = '';
        if ($has_gcm_grid && $has_gcm_sidebar) $type = 'new_template';
        elseif ($has_svc_content) $type = 'old_template';
        else $type = 'unknown';

        $structure_types[$type] = ($structure_types[$type] ?? 0) + 1;

        // For new_template pages, check div net
        if ($has_gcm_grid && $has_gcm_sidebar) {
            $grid_pos    = strpos($src, '<div class="gcm-grid">');
            $sidebar_pos = strpos($src, '<div class="gcm-sidebar">');
            if ($sidebar_pos > $grid_pos) {
                $between = substr($src, $grid_pos, $sidebar_pos - $grid_pos);
                $opens   = preg_match_all('/<div\b/', $between);
                $closes  = substr_count($between, '</div>');
                $net     = $opens - $closes;
                if ($net < 1 && count($broken_samples) < 5) {
                    $broken_samples[] = [
                        'file' => basename($f),
                        'net'  => $net,
                        'opens' => $opens,
                        'closes' => $closes,
                    ];
                }
            }
        }
    }

    echo json_encode([
        'success'         => true,
        'total'           => count($all),
        'structure_types' => $structure_types,
        'broken_samples'  => $broken_samples,
    ]);
    exit;
}

// Single file diagnostic
$target = $gen_dir . basename($file);
if (!file_exists($target)) {
    echo json_encode(['success' => false, 'message' => 'File not found: ' . $file]);
    exit;
}

$src = file_get_contents($target);

// Find all key markers
$grid_pos     = strpos($src, '<div class="gcm-grid">');
$sidebar_pos  = strpos($src, '<div class="gcm-sidebar">');
$main_pos     = strpos($src, '<div class="gcm-main">');
$content_pos  = strpos($src, '<div class="gcm-content">');
$svc_pos      = strpos($src, '<div class="service-content">');

// Look for grid variants
preg_match('/<div[^>]+class=["\'][^"\']*gcm-grid[^"\']*["\']/', $src, $grid_match);

$result = [
    'success'       => true,
    'file'          => $file,
    'file_size'     => strlen($src),
    'markers' => [
        'gcm-grid_exact'    => $grid_pos !== false ? $grid_pos : 'NOT FOUND',
        'gcm-grid_alt'      => !empty($grid_match) ? $grid_match[0] : 'NO ALT FOUND',
        'gcm-main'          => $main_pos !== false ? $main_pos : 'NOT FOUND',
        'gcm-content'       => $content_pos !== false ? $content_pos : 'NOT FOUND',
        'gcm-sidebar'       => $sidebar_pos !== false ? $sidebar_pos : 'NOT FOUND',
        'service-content'   => $svc_pos !== false ? $svc_pos : 'NOT FOUND',
    ],
];

// If we have grid + sidebar, do detailed analysis
if ($grid_pos !== false && $sidebar_pos !== false && $sidebar_pos > $grid_pos) {
    $between = substr($src, $grid_pos, $sidebar_pos - $grid_pos);
    $opens   = preg_match_all('/<div\b/', $between);
    $closes  = substr_count($between, '</div>');
    $net     = $opens - $closes;

    // Show last 300 chars before sidebar (where excess closes would be)
    $before_sidebar = substr($src, max(0, $sidebar_pos - 300), 300);

    $result['div_analysis'] = [
        'opens_in_between'  => $opens,
        'closes_in_between' => $closes,
        'net'               => $net,
        'expected_net'      => 1,
        'is_broken'         => $net < 1,
        'excess_closes'     => max(0, 1 - $net),
    ];
    $result['before_sidebar_300chars'] = $before_sidebar;
} else {
    $result['div_analysis'] = 'Cannot analyze — gcm-grid or gcm-sidebar not found at expected positions';

    // Show what IS around position 400 bytes in (where body content starts)
    $result['sample_around_body'] = substr($src, 400, 400);
}

// Also check for any inline style that might override grid
preg_match_all('/style\s*=\s*["\'][^"\']*display\s*:\s*block[^"\']*["\']/', $src, $inline_blocks);
$result['inline_display_block'] = count($inline_blocks[0]);

// Show first 200 chars of the file for PHP structure context  
$result['file_start_200'] = substr($src, 0, 200);

echo json_encode($result, JSON_PRETTY_PRINT);
