<?php
/**
 * Scale GCM Netting Solutions Database: Import 600 Keywords & 633 Areas
 */
$pdo = new PDO("mysql:host=localhost;dbname=gcmsafetynets_db;charset=utf8mb4", "gcmsafetynets_user", "t856zxMjLey8bpU5");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Import Keywords
$kws_file = __DIR__ . "/data/new_keywords.json";
if (file_exists($kws_file)) {
    $kws = json_decode(file_get_contents($kws_file), true);
    $pdo->beginTransaction();
    $kw_stmt = $pdo->prepare("
        INSERT INTO seo_service_keywords (service_id, keyword_name, keyword_slug, category, search_volume, is_active, display_order)
        VALUES (:sid, :name, :slug, :cat, 1000, 1, :order)
        ON DUPLICATE KEY UPDATE is_active = 1, category = VALUES(category), service_id = VALUES(service_id)
    ");
    $order = 1;
    foreach ($kws as $k) {
        $kw_stmt->execute([
            ":sid"   => $k["service_id"],
            ":name"  => $k["name"],
            ":slug"  => $k["slug"],
            ":cat"   => $k["category"],
            ":order" => $order++
        ]);
    }
    $pdo->commit();
    echo "Keywords imported successfully!\n";
}

// 2. Import Areas
$areas_file = __DIR__ . "/data/new_areas.json";
if (file_exists($areas_file)) {
    $areas = json_decode(file_get_contents($areas_file), true);
    $pdo->beginTransaction();
    $area_stmt = $pdo->prepare("
        INSERT INTO service_areas (area_name, area_slug, zone, pincode, is_active)
        VALUES (:name, :slug, :zone, :pin, 1)
        ON DUPLICATE KEY UPDATE zone = VALUES(zone), pincode = VALUES(pincode), is_active = 1
    ");
    foreach ($areas as $a) {
        $area_stmt->execute([
            ":name" => $a["area_name"],
            ":slug" => $a["area_slug"],
            ":zone" => !empty($a["zone"]) ? $a["zone"] : "Central Chennai",
            ":pin"  => !empty($a["pincode"]) ? $a["pincode"] : "600002"
        ]);
    }
    $pdo->commit();
    echo "Areas imported successfully!\n";
}

// 3. Stats & Calculation
$total_kws = $pdo->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active = 1")->fetchColumn();
$total_areas = $pdo->query("SELECT COUNT(*) FROM service_areas WHERE is_active = 1")->fetchColumn();
$total_scale = $total_kws * $total_areas;

echo "========================================\n";
echo "Total Active Keywords: {$total_kws}\n";
echo "Total Active Localities: {$total_areas}\n";
echo "Total Dynamic Pages Potential: " . number_format($total_scale) . " Pages\n";
echo "========================================\n";
