<?php
/**
 * API: Get real-time page count
 * Returns current number of generated pages
 */

header('Content-Type: application/json');

// Count actual existing pages
$root_dir = __DIR__ . '/../../';
$existing_pages = glob($root_dir . '*-in-*.php');
$total_count = count($existing_pages);

// Calculate progress
$total_target = 12032; // 64 × 188
$progress_percentage = $total_count > 0 ? round(($total_count / $total_target) * 100, 1) : 0;

// Get last generated time
$stats_file = $root_dir . 'config/generation-stats.json';
$last_generated = 'Never';
if (file_exists($stats_file)) {
    $stats = json_decode(file_get_contents($stats_file), true);
    $last_generated = $stats['last_generated'] ?? 'Never';
}

echo json_encode([
    'success' => true,
    'total_generated' => $total_count,
    'progress_percentage' => $progress_percentage,
    'total_target' => $total_target,
    'last_generated' => $last_generated
]);
