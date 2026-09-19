<?php
/**
 * Verify Real Areas from Database
 * Check what areas are actually being used in generated pages
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    $conn->set_charset('utf8mb4');
    
    // Get unique areas from generated_pages table
    $result = $conn->query("SELECT DISTINCT area_name FROM generated_pages ORDER BY area_name");
    
    $database_areas = [];
    while ($row = $result->fetch_assoc()) {
        $database_areas[] = $row['area_name'];
    }
    
    // Get areas from config file
    $config_areas_data = require '../../config/all-areas.php';
    $config_areas = array_map(function($area) {
        return $area['area'];
    }, $config_areas_data);
    
    // Compare
    $in_db_not_config = array_diff($database_areas, $config_areas);
    $in_config_not_db = array_diff($config_areas, $database_areas);
    
    echo json_encode([
        'success' => true,
        'database_area_count' => count($database_areas),
        'config_area_count' => count($config_areas),
        'database_areas' => $database_areas,
        'config_areas' => $config_areas,
        'in_database_but_not_config' => array_values($in_db_not_config),
        'in_config_but_not_database' => array_values($in_config_not_db),
        'match' => (count($in_db_not_config) == 0 && count($in_config_not_db) == 0)
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
