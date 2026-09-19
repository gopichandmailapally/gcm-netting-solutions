<?php
/**
 * Standalone keyword fix — run once via browser:
 *   https://gcmsafetynets.in/admin/fix-keywords.php
 * Activates all 64 known keywords and inserts any missing ones.
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (Exception $e) {
    die("DB connect failed: " . $e->getMessage());
}

$kw_file = __DIR__ . '/../config/all-service-keywords.php';
if (!file_exists($kw_file)) { die("all-service-keywords.php not found"); }
$keywords = require $kw_file;

$before = (int)$pdo->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active=1")->fetchColumn();
echo "Before: {$before} active keywords\n\n";

/* ── Ensure difficulty + display_order columns exist ── */
foreach (['difficulty INT(11) DEFAULT 0', 'display_order INT(11) DEFAULT 0'] as $col_def) {
    try { $pdo->exec("ALTER TABLE `seo_service_keywords` ADD COLUMN $col_def"); echo "Added column: $col_def\n"; }
    catch (Exception $e) { /* already exists */ }
}

/* ── Ensure unique index on keyword_slug ── */
try {
    $idx = $pdo->query("SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='seo_service_keywords' AND INDEX_NAME='uq_kw_slug'")->fetchColumn();
    if ((int)$idx === 0) {
        $pdo->exec("ALTER TABLE `seo_service_keywords` ADD UNIQUE KEY `uq_kw_slug` (`keyword_slug`(191))");
        echo "Added unique index on keyword_slug\n";
    }
} catch (Exception $e) { echo "Index check: " . $e->getMessage() . "\n"; }

/* ── Step 1: Activate any inactive keywords from the known 64 ── */
$slugs = array_column($keywords, 'slug');
$ph    = implode(',', array_fill(0, count($slugs), '?'));
$stmt  = $pdo->prepare("UPDATE `seo_service_keywords` SET `is_active`=1 WHERE `keyword_slug` IN ($ph) AND `is_active`=0");
$stmt->execute($slugs);
$activated = $stmt->rowCount();
echo "Step 1 — Activated {$activated} inactive keywords\n";

/* ── Step 2: Insert any keywords that are still missing ── */
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
$volumes = [
    'PIGEON NETS'     => [1400, 1200, 900, 1100, 850, 780, 800, 700, 650, 600, 1200, 500, 420],
    'BIRD NETS'       => [890, 750, 650, 600, 540, 480, 320, 450, 540],
    'SAFETY NETS'     => [1800, 1100, 900, 420, 380, 780, 650, 420, 380, 620, 520],
    'SPORTS NETS'     => [1500, 480, 620, 580, 890, 420, 380, 350, 750, 320, 420, 280, 520],
    'INVISIBLE GRILLS'=> [1350, 780, 680, 920, 840, 750, 580, 620, 480],
    'CLOTH HANGERS'   => [980, 720, 820, 680, 580, 640, 420, 380, 350],
];
$cat_idx = [];
$ins = $pdo->prepare(
    "INSERT IGNORE INTO `seo_service_keywords`
     (`keyword_slug`,`keyword_name`,`category`,`search_volume`,`difficulty`,`is_active`,`display_order`)
     VALUES (?,?,?,?,?,1,?)"
);
$inserted = 0;
$order = 1;
foreach ($keywords as $kw) {
    $cat  = $kw['category'];
    $idx  = $cat_idx[$cat] ?? 0;
    $vol  = $volumes[$cat][$idx] ?? 300;
    $diff = min(100, 30 + (int)($vol / 50));
    $ins->execute([$kw['slug'], $kw['keyword'], $cat, $vol, $diff, $order++]);
    $inserted += $ins->rowCount();
    $cat_idx[$cat] = $idx + 1;
}
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
echo "Step 2 — Inserted {$inserted} missing keywords\n\n";

/* ── Final count by category ── */
$after = (int)$pdo->query("SELECT COUNT(*) FROM seo_service_keywords WHERE is_active=1")->fetchColumn();
echo "After: {$after} active keywords\n\n";

$rows = $pdo->query(
    "SELECT category, COUNT(*) AS cnt FROM seo_service_keywords WHERE is_active=1 GROUP BY category ORDER BY category"
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo "  {$r['category']}: {$r['cnt']} keywords\n";
}
echo "\n" . ($after >= 64 ? "✓ All 64 keywords active!" : "✗ Still missing " . (64 - $after) . " keywords — check Hostinger error log.") . "\n";
