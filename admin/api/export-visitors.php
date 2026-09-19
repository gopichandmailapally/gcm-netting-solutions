<?php
/**
 * Export Visitors as CSV
 */
define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(403); exit('Unauthorized');
}

$days  = max(1, min(90, (int)($_GET['days'] ?? 7)));
$limit = max(100, min(5000, (int)($_GET['limit'] ?? 1000)));

try {
    $db  = Database::getInstance();
    $pdo = $db->getConnection();

    $rows = $db->fetchAll("
        SELECT ip_address, city, region, country, country_code,
               device_type, browser, os, isp,
               entry_page, exit_page, current_page, referrer, search_keyword,
               first_visit, last_activity, total_time_spent, page_views
        FROM visitor_tracking
        WHERE first_visit >= DATE_SUB(NOW(), INTERVAL ? DAY)
        ORDER BY first_visit DESC
        LIMIT $limit
    ", [$days], 'i');
} catch (Exception $e) {
    http_response_code(500); exit('DB error: ' . $e->getMessage());
}

$filename = 'visitors-' . date('Y-m-d') . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$out = fopen('php://output', 'w');
fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel

fputcsv($out, [
    'IP Address', 'City', 'Region', 'Country', 'Country Code',
    'Device', 'Browser', 'OS', 'ISP',
    'Entry Page', 'Exit Page', 'Last Page', 'Referrer', 'Search Keyword',
    'Entry Time', 'Exit Time', 'Time Spent (s)', 'Pages Viewed'
]);

foreach ($rows as $r) {
    fputcsv($out, [
        $r['ip_address']     ?? '',
        $r['city']           ?? '',
        $r['region']         ?? '',
        $r['country']        ?? '',
        $r['country_code']   ?? '',
        $r['device_type']    ?? '',
        $r['browser']        ?? '',
        $r['os']             ?? '',
        $r['isp']            ?? '',
        $r['entry_page']     ?? '',
        $r['exit_page']      ?? '',
        $r['current_page']   ?? '',
        $r['referrer']       ?? '',
        $r['search_keyword'] ?? '',
        $r['first_visit']    ?? '',
        $r['last_activity']  ?? '',
        $r['total_time_spent'] ?? 0,
        $r['page_views']     ?? 1,
    ]);
}
fclose($out);
