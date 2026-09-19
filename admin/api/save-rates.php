<?php
/**
 * Save Service Rates API
 * Accepts full nested rates object and writes to config/service-rates.json
 */

ob_start();

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

function jOut(array $d): void { ob_clean(); echo json_encode($d); exit; }

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jOut(['success' => false, 'message' => 'Unauthorized']);
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['rates']) || !is_array($input['rates'])) {
    jOut(['success' => false, 'message' => 'Invalid data — expected {rates: {...}}']);
}

$new_rates = $input['rates'];

// Basic structure validation
$required = ['safety_nets', 'cricket_nets', 'invisible_grills', 'cloth_hangers'];
foreach ($required as $key) {
    if (!isset($new_rates[$key])) {
        jOut(['success' => false, 'message' => "Missing section: $key"]);
    }
}

try {
    $rates_file = dirname(dirname(__DIR__)) . '/config/service-rates.json';

    // Preserve the _note field if present
    $existing = file_exists($rates_file) ? json_decode(file_get_contents($rates_file), true) : [];
    if (isset($existing['_note'])) {
        $new_rates = array_merge(['_note' => $existing['_note']], $new_rates);
    }

    $written = file_put_contents($rates_file, json_encode($new_rates, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($written === false) {
        jOut(['success' => false, 'message' => 'Cannot write to config/service-rates.json — check file permissions (644)']);
    }

    jOut(['success' => true, 'message' => 'Rates saved successfully', 'bytes' => $written]);

} catch (Exception $e) {
    jOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
