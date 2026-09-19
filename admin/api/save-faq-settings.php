<?php
/**
 * Save FAQ Settings API
 * Persists to DB (faq_settings table) — survives redeployment
 */

define('ADMIN_ACCESS', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$daily_count     = max(1, min(10, (int)($_POST['daily_count']     ?? 1)));
$generation_time = trim($_POST['generation_time'] ?? 'random');

try {
    $db = Database::getInstance();

    // Ensure faq_settings table exists with all needed columns
    $db->execute("
        CREATE TABLE IF NOT EXISTS faq_settings (
            id                    INT NOT NULL DEFAULT 1,
            auto_generate_enabled TINYINT(1) NOT NULL DEFAULT 1,
            daily_faq_count       INT NOT NULL DEFAULT 1,
            generation_time       VARCHAR(50) NOT NULL DEFAULT 'random',
            today_schedule        VARCHAR(20) NOT NULL DEFAULT '',
            last_run_date         DATE NULL,
            total_generated       INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    // Add columns if upgrading from older schema
    foreach (['generation_time VARCHAR(50) NOT NULL DEFAULT \'random\'',
               'today_schedule VARCHAR(20) NOT NULL DEFAULT \'\'',
               'last_run_date DATE NULL'] as $col_def) {
        try { $db->execute("ALTER TABLE faq_settings ADD COLUMN IF NOT EXISTS $col_def"); }
        catch (Exception $e) { /* column already exists */ }
    }

    // UPSERT row id = 1
    $db->execute(
        "INSERT INTO faq_settings (id, auto_generate_enabled, daily_faq_count, generation_time, today_schedule)
         VALUES (1, 1, ?, ?, '')
         ON DUPLICATE KEY UPDATE
             daily_faq_count  = VALUES(daily_faq_count),
             generation_time  = VALUES(generation_time),
             today_schedule   = ''",
        [$daily_count, $generation_time]
    );

    echo json_encode([
        'success'         => true,
        'daily_count'     => $daily_count,
        'generation_time' => $generation_time,
        'message'         => 'Settings saved successfully!'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'DB error: ' . $e->getMessage()
    ]);
}
