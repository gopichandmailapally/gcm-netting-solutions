<?php
/**
 * ONE-TIME FIX: Insert/activate missing "Anti Bird Netting" keyword
 * Upload this file to /admin/ on Hostinger, visit it once, then delete it.
 */
define('GCM_INIT', true);
require_once '../config/config.php';

// Direct MySQL connection using config constants
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    // Step 1: Add missing columns if they don't exist
    try { $pdo->exec("ALTER TABLE `seo_service_keywords` ADD COLUMN `difficulty` INT(11) DEFAULT 0"); } catch (Exception $e) {}
    try { $pdo->exec("ALTER TABLE `seo_service_keywords` ADD COLUMN `display_order` INT(11) DEFAULT 0"); } catch (Exception $e) {}

    // Check current state
    $before = $pdo->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active = 1")->fetchColumn();
    $existing = $pdo->query("SELECT * FROM seo_service_keywords WHERE keyword_slug = 'anti-bird-netting'")->fetch(PDO::FETCH_ASSOC);

    // Step 2: Get the service_id for Bird Nets (required by FK constraint)
    $svc = $pdo->query("SELECT id FROM `services` WHERE service_slug = 'bird-nets' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $service_id = $svc['id'] ?? null;

    // Step 3: Disable FK checks so insert works even if service_id is missing
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Step 4: Insert or activate the missing keyword
    if ($service_id) {
        $pdo->exec(
            "INSERT INTO seo_service_keywords
             (keyword_name, keyword_slug, category, search_volume, difficulty, is_active, display_order, service_id)
             VALUES ('Anti Bird Netting', 'anti-bird-netting', 'BIRD NETS', 540, 41, 1, 22, {$service_id})
             ON DUPLICATE KEY UPDATE is_active = 1, display_order = 22, service_id = {$service_id}"
        );
    } else {
        $pdo->exec(
            "INSERT INTO seo_service_keywords
             (keyword_name, keyword_slug, category, search_volume, difficulty, is_active, display_order)
             VALUES ('Anti Bird Netting', 'anti-bird-netting', 'BIRD NETS', 540, 41, 1, 22)
             ON DUPLICATE KEY UPDATE is_active = 1, display_order = 22"
        );
    }

    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Verify
    $after = $pdo->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active = 1")->fetchColumn();
    $bird_nets = $pdo->query("SELECT keyword_name, keyword_slug, is_active FROM seo_service_keywords WHERE category = 'BIRD NETS' ORDER BY display_order")->fetchAll(PDO::FETCH_ASSOC);

    echo '<h2 style="font-family:sans-serif;color:green;">✅ Fix Applied</h2>';
    echo '<pre style="font-family:monospace;background:#f0f0f0;padding:16px;">';
    echo "Before: {$before} active keywords\n";
    echo "After:  {$after} active keywords\n\n";
    if ($existing) {
        echo "Previous state of anti-bird-netting:\n";
        print_r($existing);
    } else {
        echo "anti-bird-netting was NOT in DB — inserted fresh.\n";
    }
    echo "\nBIRD NETS keywords now in DB:\n";
    foreach ($bird_nets as $kw) {
        echo "  [{$kw['is_active']}] {$kw['keyword_name']} ({$kw['keyword_slug']})\n";
    }
    echo '</pre>';
    echo '<p style="font-family:sans-serif;color:red;"><strong>Delete this file from your server now!</strong></p>';

} catch (Exception $e) {
    echo '<h2 style="font-family:sans-serif;color:red;">❌ Error</h2>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
}
