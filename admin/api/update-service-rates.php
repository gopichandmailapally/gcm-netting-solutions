<?php
/**
 * Update Service Rates
 * Bulk update min-max pricing for services
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$rates = $input['rates'] ?? [];

if (empty($rates)) {
    echo json_encode(['success' => false, 'message' => 'No rates provided']);
    exit;
}

$db = Database::getInstance();
$updated = 0;

try {
    foreach ($rates as $rate) {
        $id = (int)$rate['id'];
        $min_rate = (float)$rate['min_rate'];
        $max_rate = (float)$rate['max_rate'];
        $unit = sanitize_input($rate['unit']);
        $is_active = (int)($rate['is_active'] ?? 1);
        
        // Validate
        if ($min_rate < 0 || $max_rate < 0) {
            continue; // Skip invalid rates
        }
        
        if ($min_rate > $max_rate) {
            // Swap if min is greater than max
            $temp = $min_rate;
            $min_rate = $max_rate;
            $max_rate = $temp;
        }
        
        $result = $db->execute(
            "UPDATE service_rates SET 
             min_rate = ?,
             max_rate = ?,
             unit = ?,
             is_active = ?,
             updated_by = ?,
             updated_at = NOW()
             WHERE id = ?",
            [$min_rate, $max_rate, $unit, $is_active, $_SESSION['admin_id'], $id],
            'ddsiii'
        );
        
        if ($result) {
            $updated++;
        }
    }
    
    // Log activity
    log_admin_activity(
        $_SESSION['admin_id'], 
        'service_rates_updated', 
        "Updated $updated service rates"
    );
    
    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'message' => "$updated service rates updated successfully"
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
