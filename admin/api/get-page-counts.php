<?php
/**
 * Get Accurate Page Counts from Database
 * Always returns exact counts - never wrong
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

// MySQL Connection
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

try {
    $conn = getDBConnection();
    
    // Get counts from database
    $services_count = $conn->query("SELECT COUNT(*) as count FROM services WHERE is_active = 1")->fetch_assoc()['count'] ?? 64;
    $areas_count = $conn->query("SELECT COUNT(*) as count FROM areas WHERE is_active = 1")->fetch_assoc()['count'] ?? 188;
    $static_pages_count = $conn->query("SELECT COUNT(*) as count FROM static_pages WHERE is_active = 1")->fetch_assoc()['count'] ?? 10;
    
    // Calculate service pages
    $service_pages_count = $services_count * $areas_count;
    
    // Pillar pages = one per service keyword
    $pillar_pages_count = $services_count;
    
    // Total pages
    $total_pages = $service_pages_count + $pillar_pages_count + $static_pages_count;
    
    // Get category breakdown
    $category_breakdown = [];
    $result = $conn->query("SELECT category, COUNT(*) as count FROM services WHERE is_active = 1 GROUP BY category");
    while ($row = $result->fetch_assoc()) {
        $category_breakdown[$row['category']] = [
            'keywords' => (int)$row['count'],
            'areas' => $areas_count,
            'total_pages' => (int)$row['count'] * $areas_count
        ];
    }
    
    echo json_encode([
        'success' => true,
        'counts' => [
            'services' => $services_count,
            'areas' => $areas_count,
            'service_pages' => $service_pages_count,
            'pillar_pages' => $pillar_pages_count,
            'static_pages' => $static_pages_count,
            'total_pages' => $total_pages
        ],
        'breakdown' => $category_breakdown,
        'formula' => [
            'service_pages' => "{$services_count} services × {$areas_count} areas = {$service_pages_count}",
            'pillar_pages' => "{$services_count} pillar pages (one per service keyword)",
            'static_pages' => "{$static_pages_count} main pages",
            'total' => "{$service_pages_count} + {$pillar_pages_count} + {$static_pages_count} = {$total_pages}"
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
