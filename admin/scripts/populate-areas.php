<?php
/**
 * Populate areas table from all-areas.php config
 */

define('GCM_INIT', true);
require_once __DIR__ . '/../../config/config.php';

// Get areas from config
$areas = require __DIR__ . '/../../config/all-areas.php';

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset(DB_CHARSET);

// Check if table is empty
$result = $conn->query("SELECT COUNT(*) as total FROM areas");
$row = $result->fetch_assoc();
$is_empty = ($row['total'] == 0);

// Prepare statement - use INSERT IGNORE to skip duplicates
$stmt = $conn->prepare("INSERT IGNORE INTO areas (area_slug, area_name, is_active) VALUES (?, ?, 1)");

$inserted = 0;
$errors = [];

foreach ($areas as $area) {
    $area_slug = $area['slug'];
    $area_name = $area['area'];
    
    // Clean up - remove any directional suffixes
    $area_name = preg_replace('/\s*\((north|south|east|west)\)\s*/i', '', $area_name);
    $area_slug = preg_replace('/-\((north|south|east|west)\)|-\(north\)|-\(south\)|-\(east\)|-\(west\)/i', '', $area_slug);
    
    $stmt->bind_param("ss", $area_slug, $area_name);
    
    if ($stmt->execute()) {
        $inserted++;
    } else {
        $errors[] = "Failed to insert {$area_name}: " . $stmt->error;
    }
}

$stmt->close();
$conn->close();

echo "✅ Successfully populated areas table!\n\n";
echo "Inserted: {$inserted} areas\n";
if (!empty($errors)) {
    echo "\nErrors:\n" . implode("\n", $errors) . "\n";
}
