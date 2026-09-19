<?php
/**
 * AI Content Full Backup Export
 *
 * ?type=full    → ZIP of ALL AI content (service pages PHP + blogs + reviews + FAQs + DB records)
 * ?type=files   → ZIP of generated-pages PHP files only
 * ?type=db      → JSON of generated_pages DB records only
 * ?type=stats   → JSON summary of counts (used by content-export.php)
 */
ob_start();
define('GCM_INIT', true);
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$type     = $_GET['type'] ?? 'full';
$root_dir = dirname(__DIR__, 2);
$date     = date('Y-m-d_H-i');

// ─────────────────────────────────────────────────────────────
// Helper: recursively add directory to zip
// ─────────────────────────────────────────────────────────────
function zipAddDir(ZipArchive $zip, string $dir, string $zipBase): int {
    $count = 0;
    if (!is_dir($dir)) return 0;
    $iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
    foreach ($iter as $file) {
        if ($file->isFile()) {
            $rel = $zipBase . '/' . ltrim(str_replace($dir, '', $file->getPathname()), '/\\');
            $zip->addFile($file->getPathname(), $rel);
            $count++;
        }
    }
    return $count;
}

// ─────────────────────────────────────────────────────────────
// STATS only (JSON, no download)
// ─────────────────────────────────────────────────────────────
if ($type === 'stats') {
    $gen_dir  = $root_dir . '/generated-pages/';
    $data_dir = $root_dir . '/data/';

    $php_files  = is_dir($gen_dir) ? count(array_filter(glob($gen_dir . '*.php') ?: [], fn($f) => basename($f) !== 'index.php')) : 0;
    $blog_files = is_dir($data_dir . 'blogs')   ? count(glob($data_dir . 'blogs/*.json')   ?: []) : 0;
    $rev_files  = is_dir($data_dir . 'reviews') ? count(glob($data_dir . 'reviews/*.json') ?: []) : 0;
    $faq_files  = is_dir($data_dir . 'faqs')    ? count(glob($data_dir . 'faqs/*.json')    ?: []) : 0;

    // Pillar pages — match root PHP files against keyword slugs in DB (accurate count)
    $pillar_count = 0;
    try {
        $kw_rows = $db->fetchAll("SELECT keyword_slug FROM seo_service_keywords WHERE is_active = 1");
        $keyword_slugs = array_column($kw_rows, 'keyword_slug');
        $all_root = glob($root_dir . '/*.php') ?: [];
        $pillar_count = count(array_filter($all_root, fn($f) =>
            in_array(basename($f, '.php'), $keyword_slugs)
        ));
    } catch (\Exception $e) {
        $known = ['index.php','about.php','blog.php','blogs.php','contact.php','estimation.php','faqs.php',
                  'gallery.php','privacy-policy.php','reviews.php','terms-conditions.php','videos.php',
                  'thank-you.php','save-generated-page.php'];
        $all_root = glob($root_dir . '/*.php') ?: [];
        $pillar_count = count(array_filter($all_root, fn($f) => !in_array(basename($f), $known)
            && !str_starts_with(basename($f), 'test-') && !str_starts_with(basename($f), 'CHECK')
            && !str_starts_with(basename($f), 'EMERGENCY') && !str_contains(basename($f), 'generate')));
    }

    try {
        $db = Database::getInstance();
        $db_count = (int)($db->fetchOne("SELECT COUNT(*) as c FROM generated_pages WHERE keyword_id>0 AND area_id>0")['c'] ?? 0);
    } catch (\Exception $e) { $db_count = 0; }

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'service_pages_php' => $php_files,
        'service_pages_db'  => $db_count,
        'blogs'             => max(0, $blog_files - 2), // subtract stats.json + index.json
        'reviews'           => max(0, $rev_files - 2),
        'faqs'              => max(0, $faq_files - 2),
        'pillar_pages'      => $pillar_count,
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────
// FULL BACKUP ZIP (all AI content)
// ─────────────────────────────────────────────────────────────
if ($type === 'full') {
    if (!class_exists('ZipArchive')) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['error' => 'ZipArchive not available on this server.']); exit;
    }

    $tmp_zip = sys_get_temp_dir() . '/gcm-full-backup-' . time() . '.zip';
    $zip     = new ZipArchive();
    if ($zip->open($tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['error' => 'Cannot create backup ZIP.']); exit;
    }

    $total = 0;

    // 1. Service page PHP files
    $gen_dir = $root_dir . '/generated-pages/';
    $php_files = is_dir($gen_dir) ? array_filter(glob($gen_dir . '*.php') ?: [], fn($f) => basename($f) !== 'index.php') : [];
    foreach ($php_files as $f) { $zip->addFile($f, 'generated-pages/' . basename($f)); $total++; }

    // 2. Blogs JSON
    $total += zipAddDir($zip, $root_dir . '/data/blogs',   'data/blogs');

    // 3. Reviews JSON
    $total += zipAddDir($zip, $root_dir . '/data/reviews', 'data/reviews');

    // 4. FAQs JSON
    $total += zipAddDir($zip, $root_dir . '/data/faqs',    'data/faqs');

    // 5. DB export as JSON inside the zip
    try {
        $db = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT gp.*, sa.area_name, sa.area_slug, kw.keyword_name, kw.keyword_slug, kw.category
             FROM generated_pages gp
             LEFT JOIN service_areas sa        ON gp.area_id    = sa.id
             LEFT JOIN seo_service_keywords kw ON gp.keyword_id = kw.id
             ORDER BY gp.created_at ASC"
        );
        $db_json = json_encode([
            'exported_at'   => date('Y-m-d H:i:s'),
            'total_records' => count($rows),
            'records'       => $rows,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $zip->addFromString('db-records/generated_pages.json', $db_json);
        $total++;
    } catch (\Exception $e) {}

    // 6. README for restore
    $readme = "GCM NETTING SOLUTIONS - AI Content Backup\n";
    $readme .= "====================================\n";
    $readme .= "Exported: " . date('Y-m-d H:i:s') . "\n\n";
    $readme .= "CONTENTS OF THIS BACKUP:\n";
    $readme .= "  generated-pages/    → " . count($php_files) . " AI service page PHP files\n";
    $readme .= "  data/blogs/         → Blog posts (JSON)\n";
    $readme .= "  data/reviews/       → Reviews (JSON)\n";
    $readme .= "  data/faqs/          → FAQs (JSON)\n";
    $readme .= "  db-records/         → MySQL DB records (JSON)\n\n";
    $readme .= "HOW TO RESTORE AFTER ACCIDENTAL DELETION:\n";
    $readme .= "  1. Upload generated-pages/ folder to your server root\n";
    $readme .= "  2. Upload data/blogs/ to your server data/ folder\n";
    $readme .= "  3. Upload data/reviews/ to your server data/ folder\n";
    $readme .= "  4. Upload data/faqs/ to your server data/ folder\n";
    $readme .= "  5. DB records: use the restore feature in Admin → Content Protection\n\n";
    $readme .= "SAFE DEPLOY RULES:\n";
    $readme .= "  ✓ Upload gcmsafetynets-deploy.zip and Extract — content is NOT touched\n";
    $readme .= "  ✗ NEVER delete all server files before uploading — this WILL wipe content\n";
    $zip->addFromString('RESTORE-README.txt', $readme);

    $zip->close();

    ob_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="gcm-full-backup-' . $date . '.zip"');
    header('Content-Length: ' . filesize($tmp_zip));
    header('Cache-Control: no-cache, no-store');
    readfile($tmp_zip);
    @unlink($tmp_zip);
    exit;
}

// ─────────────────────────────────────────────────────────────
// ZIP of generated PHP files only (legacy)
// ─────────────────────────────────────────────────────────────
if ($type === 'files') {
    if (!class_exists('ZipArchive')) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['error' => 'ZipArchive not available.']); exit;
    }
    $gen_dir = $root_dir . '/generated-pages/';
    $files   = is_dir($gen_dir) ? array_filter(glob($gen_dir . '*.php') ?: [], fn($f) => basename($f) !== 'index.php') : [];
    if (empty($files)) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['error' => 'No generated pages found.']); exit;
    }
    $tmp_zip = sys_get_temp_dir() . '/gcm-pages-' . time() . '.zip';
    $zip     = new ZipArchive();
    $zip->open($tmp_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    foreach ($files as $f) $zip->addFile($f, 'generated-pages/' . basename($f));
    $zip->close();
    ob_clean();
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="gcm-service-pages-' . $date . '.zip"');
    header('Content-Length: ' . filesize($tmp_zip));
    header('Cache-Control: no-cache, no-store');
    readfile($tmp_zip);
    @unlink($tmp_zip);
    exit;
}

// ─────────────────────────────────────────────────────────────
// JSON export of DB records only (legacy)
// ─────────────────────────────────────────────────────────────
if ($type === 'db') {
    try {
        $db   = Database::getInstance();
        $rows = $db->fetchAll(
            "SELECT gp.*, sa.area_name, sa.area_slug, kw.keyword_name, kw.keyword_slug, kw.category
             FROM generated_pages gp
             LEFT JOIN service_areas sa        ON gp.area_id    = sa.id
             LEFT JOIN seo_service_keywords kw ON gp.keyword_id = kw.id
             ORDER BY gp.created_at ASC"
        );
        ob_clean();
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="gcm-db-export-' . $date . '.json"');
        header('Cache-Control: no-cache, no-store');
        echo json_encode(['exported_at'=>date('Y-m-d H:i:s'),'total_records'=>count($rows),'records'=>$rows],
                         JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    } catch (\Exception $e) {
        ob_clean(); header('Content-Type: application/json');
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

ob_clean();
http_response_code(400);
echo json_encode(['error' => 'Unknown type. Use: full, files, db, stats']);
