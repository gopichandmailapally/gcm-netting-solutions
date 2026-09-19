<?php
/**
 * Restore All AI Content from MySQL Database
 * Run this after any deployment to recreate JSON files and PHP service pages
 * from the MySQL database (which is NEVER affected by file deletions).
 *
 * ?type=all          — restore everything
 * ?type=service_pages — recreate PHP files from generated_pages DB
 * ?type=blogs        — recreate JSON files from ai_blogs DB
 * ?type=reviews      — recreate JSON files from ai_reviews DB
 * ?type=faqs         — recreate JSON files from faqs DB
 * ?type=status       — just return counts (no file writes)
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();

$db      = Database::getInstance();
$root    = dirname(__DIR__, 2);
$type    = $_GET['type'] ?? 'status';
$results = [];

/* ── Auth ─────────────────────────────────────────────────────────────── */
$from_session = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$from_token   = isset($_GET['token']) && defined('PSEUDO_CRON_TOKEN') && hash_equals(PSEUDO_CRON_TOKEN, (string)$_GET['token']);

$token_ok_types = ['server_backup', 'server_status'];
if (!$from_session) {
    if (!$from_token || !in_array($type, $token_ok_types, true)) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
}

// ── Helper: ensure directory exists ────────────────────────────────────────
function ensure_dir(string $path): bool {
    if (!is_dir($path)) {
        return @mkdir($path, 0755, true);
    }
    return is_writable($path);
}

function gcm_backup_dir(string $root): string {
    // Store outside public_html by default: parent folder of site root
    $parent = dirname($root);
    return rtrim($parent, '/\\') . '/gcm-backups';
}

function gcm_latest_backup_file(string $backup_dir): string {
    $files = glob(rtrim($backup_dir, '/\\') . '/gcm-content-backup-*.zip') ?: [];
    if (!$files) return '';
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    return $files[0] ?? '';
}

function gcm_zip_add_dir(ZipArchive $zip, string $dir, string $zipBase): int {
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

function gcm_restore_zip_selective(string $zip_file, string $root): array {
    if (!class_exists('ZipArchive')) return ['success' => false, 'error' => 'ZipArchive not available'];
    if (!file_exists($zip_file)) return ['success' => false, 'error' => 'Backup file not found'];

    $zip = new ZipArchive();
    if ($zip->open($zip_file) !== true) return ['success' => false, 'error' => 'Cannot open backup zip'];

    $allowed_prefixes = [
        'generated-pages/',
        'data/blogs/',
        'data/faqs/',
        'data/reviews/',
        'uploads/',
    ];
    $written = 0;
    $skipped = 0;

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!$name) continue;

        $ok = false;
        foreach ($allowed_prefixes as $p) {
            if (strpos($name, $p) === 0) { $ok = true; break; }
        }
        if (!$ok) { $skipped++; continue; }

        // Directory entry
        if (substr($name, -1) === '/') {
            @mkdir($root . '/' . rtrim($name, '/'), 0755, true);
            continue;
        }

        $dest = $root . '/' . $name;
        $dest_dir = dirname($dest);
        if (!is_dir($dest_dir)) @mkdir($dest_dir, 0755, true);

        $stream = $zip->getStream($name);
        if (!$stream) continue;
        $out = @fopen($dest, 'w');
        if (!$out) { fclose($stream); continue; }
        while (!feof($stream)) {
            fwrite($out, fread($stream, 8192));
        }
        fclose($out);
        fclose($stream);
        $written++;
    }

    $zip->close();
    return ['success' => true, 'written' => $written, 'skipped' => $skipped];
}

// ── STATUS: return counts from MySQL + JSON files (whichever is higher) ─────
function get_status(Database $db, string $root = ''): array {
    $out = ['service_pages' => 0, 'blogs' => 0, 'reviews' => 0, 'faqs' => 0];

    // MySQL counts
    try { $out['service_pages'] = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM generated_pages WHERE keyword_id>0 AND area_id>0 AND page_slug IS NOT NULL AND page_slug!=''")['c'] ?? 0); } catch(\Exception $e){}
    try { $out['blogs']   = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM ai_blogs WHERE is_published=1")['c'] ?? 0); } catch(\Exception $e){}
    try { $out['reviews'] = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM ai_reviews WHERE status='approved'")['c'] ?? 0); } catch(\Exception $e){}
    try { $out['faqs']    = (int)($db->fetchOne("SELECT COUNT(*) AS c FROM faqs WHERE is_active=1")['c'] ?? 0); } catch(\Exception $e){}

    // JSON file counts (fallback: use whichever is HIGHER — covers old JSON-only data)
    if ($root) {
        $skip = ['index.json', 'stats.json'];

        $blog_files = glob($root . '/data/blogs/*.json') ?: [];
        $fc_blogs = count(array_filter($blog_files, fn($f) => !in_array(basename($f), $skip)));
        if ($fc_blogs > $out['blogs']) $out['blogs'] = $fc_blogs;

        $rev_files = glob($root . '/data/reviews/*.json') ?: [];
        $fc_revs = count(array_filter($rev_files, fn($f) => !in_array(basename($f), $skip)));
        if ($fc_revs > $out['reviews']) $out['reviews'] = $fc_revs;

        $faq_files = glob($root . '/data/faqs/*.json') ?: [];
        $fc_faqs = count(array_filter($faq_files, fn($f) => !in_array(basename($f), $skip)));
        if ($fc_faqs > $out['faqs']) $out['faqs'] = $fc_faqs;

        // Service pages: also count PHP files in generated-pages/
        $gen_files = glob($root . '/generated-pages/*.php') ?: [];
        $fc_pages  = count(array_filter($gen_files, fn($f) => basename($f) !== 'index.php'));
        if ($fc_pages > $out['service_pages']) $out['service_pages'] = $fc_pages;
    }

    return $out;
}

if ($type === 'status') {
    echo json_encode(['success' => true, 'counts' => get_status($db, $root)]);
    exit;
}

// ── SERVER BACKUP STATUS (outside public_html) ─────────────────────────────
if ($type === 'server_status') {
    $bdir = gcm_backup_dir($root);
    $latest = gcm_latest_backup_file($bdir);
    echo json_encode([
        'success' => true,
        'backup_dir' => $bdir,
        'latest_backup' => $latest ? basename($latest) : null,
        'latest_mtime' => $latest ? date('Y-m-d H:i:s', filemtime($latest)) : null,
        'latest_size' => $latest ? filesize($latest) : null,
    ]);
    exit;
}

// ── SERVER BACKUP NOW (outside public_html) ───────────────────────────────
if ($type === 'server_backup') {
    if (!class_exists('ZipArchive')) {
        echo json_encode(['success' => false, 'error' => 'ZipArchive not available']);
        exit;
    }

    $bdir = gcm_backup_dir($root);
    if (!ensure_dir($bdir)) {
        echo json_encode(['success' => false, 'error' => 'Cannot create/write backup folder: ' . $bdir]);
        exit;
    }

    $date = date('Y-m-d_H-i-s');
    $zip_file = rtrim($bdir, '/\\') . '/gcm-content-backup-' . $date . '.zip';

    $zip = new ZipArchive();
    if ($zip->open($zip_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo json_encode(['success' => false, 'error' => 'Cannot create backup zip']);
        exit;
    }

    $total = 0;
    $gen_dir = $root . '/generated-pages';
    $data_dir = $root . '/data';
    $uploads_dir = $root . '/uploads';

    // Only generated content folders
    $total += gcm_zip_add_dir($zip, $gen_dir, 'generated-pages');
    $total += gcm_zip_add_dir($zip, $data_dir . '/blogs',   'data/blogs');
    $total += gcm_zip_add_dir($zip, $data_dir . '/faqs',    'data/faqs');
    $total += gcm_zip_add_dir($zip, $data_dir . '/reviews', 'data/reviews');
    $total += gcm_zip_add_dir($zip, $uploads_dir,           'uploads');

    $zip->addFromString('BACKUP-INFO.txt',
        "GCM Content Backup\n" .
        "Created: " . date('Y-m-d H:i:s') . "\n" .
        "Includes: generated-pages/, data/blogs/, data/faqs/, data/reviews/, uploads/\n"
    );
    $zip->close();

    // Keep only latest 14 backups
    $files = glob(rtrim($bdir, '/\\') . '/gcm-content-backup-*.zip') ?: [];
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    $old = array_slice($files, 14);
    foreach ($old as $f) { @unlink($f); }

    echo json_encode([
        'success' => true,
        'backup_dir' => $bdir,
        'backup_file' => basename($zip_file),
        'size' => filesize($zip_file),
        'items_added' => $total,
    ]);
    exit;
}

// ── SERVER RESTORE (from latest backup zip) ───────────────────────────────
if ($type === 'server_restore') {
    $bdir = gcm_backup_dir($root);
    $latest = gcm_latest_backup_file($bdir);
    if (!$latest) {
        echo json_encode(['success' => false, 'error' => 'No server backups found in ' . $bdir]);
        exit;
    }
    $r = gcm_restore_zip_selective($latest, $root);
    echo json_encode([
        'success' => (bool)($r['success'] ?? false),
        'backup_dir' => $bdir,
        'backup_file' => basename($latest),
        'restore' => $r,
    ]);
    exit;
}

// ── RESTORE SERVICE PAGES (PHP files from generated_pages DB) ──────────────
function restore_service_pages(Database $db, string $root): array {
    // We need the gcm_build_page function from generate-page-single.php
    $builder_file = __DIR__ . '/generate-page-single.php';
    if (!function_exists('gcm_build_page') && file_exists($builder_file)) {
        // Extract only the gcm_build_page function without running the full script
        // We include the function definition portion only
    }
    // Define gcm_build_page inline (mirrors the function in generate-page-single.php)
    if (!function_exists('gcm_build_page_restore')) {
        function gcm_build_page_restore($title, $h1, $metaDesc, $metaKw, $content, $svcName, $svcSlug, $areaName, $areaSlug) {
            $t      = addslashes($title);
            $md     = addslashes($metaDesc);
            $mk     = addslashes($metaKw);
            $h1safe = htmlspecialchars($h1);
            $anSafe = htmlspecialchars($areaName);
            $snSafe = htmlspecialchars($svcName);

            return <<<PHP
<?php
define('GCM_INIT', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
\$page_title       = '{$t}';
\$meta_description = '{$md}';
\$meta_keywords    = '{$mk}';
\$current_page     = 'services';
\$_db = Database::getInstance();
\$_cat_kws = \$_db->fetchAll("SELECT keyword_name, keyword_slug FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order");
\$_all_areas = \$_db->fetchAll("SELECT area_name, area_slug FROM service_areas WHERE is_active = 1 ORDER BY area_name");
include dirname(__DIR__) . '/includes/modern-header.php';
?>
<style>
*{box-sizing:border-box;}body{overflow-x:hidden;margin:0;padding:0;}
@keyframes gcm-fi{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.gcm-content{animation:gcm-fi .6s ease-out;}
</style>
<section style="position:relative;width:100%;min-height:480px;background:#1E293B;display:flex;align-items:center;overflow:hidden;">
  <div style="position:absolute;inset:0;background-image:url('<?php echo SITE_URL; ?>/assets/img/services/{$svcSlug}.jpg');background-size:cover;background-position:center;"></div>
  <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(71,85,105,.55) 0%,rgba(30,41,59,.80) 100%);"></div>
  <div style="max-width:1200px;margin:0 auto;padding:60px 20px;position:relative;z-index:3;width:100%;">
    <div style="font-size:13px;color:rgba(255,255,255,.7);margin-bottom:20px;">
      <a href="<?php echo SITE_URL; ?>" style="color:rgba(255,255,255,.8);text-decoration:none;">Home</a>
      <span style="margin:0 8px;opacity:.5;">&rsaquo;</span>
      <a href="<?php echo SITE_URL; ?>/services.php" style="color:rgba(255,255,255,.8);text-decoration:none;">Services</a>
      <span style="margin:0 8px;opacity:.5;">&rsaquo;</span>
      <span style="color:#fff;">{$h1safe}</span>
    </div>
    <h1 style="font-size:46px;font-weight:800;color:#fff;margin:0 0 16px;line-height:1.2;text-shadow:0 4px 20px rgba(0,0,0,.35);max-width:780px;font-family:'Times New Roman',Times,serif;">{$h1safe}</h1>
    <p style="font-size:20px;color:rgba(255,255,255,.88);margin:0 0 32px;max-width:560px;">Trusted {$snSafe} services for {$anSafe} homes &amp; businesses</p>
    <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:36px;">
      <a href="tel:+919912399224" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#10B981,#059669);color:white;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:700;font-size:16px;">&#128222; Call: 9912399224</a>
      <a href="#contact-form" style="display:inline-flex;align-items:center;gap:8px;background:linear-gradient(135deg,#F59E0B,#D97706);color:white;padding:14px 28px;border-radius:12px;text-decoration:none;font-weight:700;font-size:16px;">&#x1F4CB; Get Free Quote</a>
    </div>
    <div style="display:flex;gap:24px;flex-wrap:wrap;">
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> Quality Materials</span>
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> Expert Installation</span>
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> 5 Year Warranty</span>
      <span style="color:rgba(255,255,255,.9);font-size:14px;font-weight:600;display:flex;align-items:center;gap:6px;"><span style="color:#10B981;font-size:17px;">&#10003;</span> Free Inspection</span>
    </div>
  </div>
</section>
<div class="gcm-wrap">
  <div class="gcm-grid">
    <div class="gcm-main">
      <div class="gcm-card">
        <div class="gcm-content">
{$content}
        </div>
        <div style="background:linear-gradient(135deg,#3B82F6,#8B5CF6);padding:36px;border-radius:16px;text-align:center;margin-top:36px;">
          <h3 style="color:white;font-size:22px;margin:0 0 10px;">&#x1F680; Ready to Get Started?</h3>
          <p style="color:rgba(255,255,255,.9);margin:0 0 18px;">Contact us today for a free consultation and site visit</p>
          <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
            <a href="tel:+919912399224" style="background:white;color:#3B82F6;padding:11px 22px;border-radius:8px;text-decoration:none;font-weight:700;">&#128222; Call Now</a>
            <a href="https://wa.me/919912399224" style="background:#25D366;color:white;padding:11px 22px;border-radius:8px;text-decoration:none;font-weight:700;" target="_blank">WhatsApp</a>
            <a href="<?php echo SITE_URL; ?>/contact.php" style="background:rgba(255,255,255,.2);color:white;border:2px solid white;padding:11px 22px;border-radius:8px;text-decoration:none;font-weight:700;">Contact Form</a>
          </div>
        </div>
      </div>
    </div>
    <?php include dirname(__DIR__) . '/includes/modern-sidebar.php'; ?>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/modern-footer.php'; ?>

PHP;
        }
    }

    $gen_dir  = $root . '/generated-pages/';
    ensure_dir($gen_dir);

    $rows = [];
    try {
        $rows = $db->fetchAll(
            "SELECT gp.*, kw.keyword_name, kw.keyword_slug, kw.category,
                    sa.area_name, sa.area_slug
             FROM generated_pages gp
             LEFT JOIN seo_service_keywords kw ON gp.keyword_id = kw.id
             LEFT JOIN service_areas sa ON gp.area_id = sa.id
             WHERE gp.keyword_id > 0 AND gp.area_id > 0
               AND gp.page_slug IS NOT NULL AND gp.page_slug != ''
               AND gp.content IS NOT NULL AND gp.content != ''
             ORDER BY gp.id"
        ) ?: [];
    } catch (\Exception $e) {
        return ['restored' => 0, 'skipped' => 0, 'error' => $e->getMessage()];
    }

    $restored = 0;
    $skipped  = 0;
    foreach ($rows as $r) {
        $slug    = $r['page_slug'];
        $file    = $gen_dir . $slug . '.php';
        if (file_exists($file)) { $skipped++; continue; } // already exists

        $php = gcm_build_page_restore(
            $r['page_title']       ?? 'Service Page',
            $r['h1_heading']       ?? ($r['page_title'] ?? ''),
            $r['meta_description'] ?? '',
            $r['meta_keywords']    ?? '',
            $r['content']          ?? '',
            $r['keyword_name']     ?? '',
            $r['keyword_slug']     ?? '',
            $r['area_name']        ?? '',
            $r['area_slug']        ?? ''
        );
        if (@file_put_contents($file, $php) !== false) $restored++;
    }

    return ['restored' => $restored, 'skipped' => $skipped, 'total_db' => count($rows)];
}

// ── RESTORE BLOGS (JSON files from ai_blogs DB) ─────────────────────────────
function restore_blogs(Database $db, string $root): array {
    $dir = $root . '/data/blogs/';
    ensure_dir($dir);

    $rows = [];
    try {
        $rows = $db->fetchAll("SELECT * FROM ai_blogs WHERE is_published = 1 ORDER BY created_at DESC") ?: [];
    } catch (\Exception $e) {
        return ['restored' => 0, 'skipped' => 0, 'error' => $e->getMessage()];
    }

    $restored = 0; $skipped = 0;
    foreach ($rows as $r) {
        $file = $dir . $r['slug'] . '.json';
        if (file_exists($file)) { $skipped++; continue; }
        $data = [
            'id'             => $r['id'],
            'title'          => $r['title'],
            'content'        => $r['content'],
            'excerpt'        => $r['excerpt'],
            'slug'           => $r['slug'],
            'author'         => $r['author'] ?? 'GCM Netting Solutions',
            'created_at'     => $r['created_at'],
            'is_published'   => true,
            'views'          => (int)($r['views'] ?? 0),
            'featured_image' => $r['featured_image'] ?? (SITE_URL . '/uploads/default-blog.jpg'),
        ];
        if (@file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false) $restored++;
    }
    return ['restored' => $restored, 'skipped' => $skipped, 'total_db' => count($rows)];
}

// ── RESTORE REVIEWS (JSON files from ai_reviews DB) ─────────────────────────
function restore_reviews(Database $db, string $root): array {
    $dir = $root . '/data/reviews/';
    ensure_dir($dir);

    $rows = [];
    try {
        $rows = $db->fetchAll("SELECT * FROM ai_reviews WHERE status = 'approved' ORDER BY created_at DESC") ?: [];
    } catch (\Exception $e) {
        return ['restored' => 0, 'skipped' => 0, 'error' => $e->getMessage()];
    }

    $restored = 0; $skipped = 0;
    foreach ($rows as $r) {
        $slug = $r['slug'] ?? ('review-' . $r['id']);
        $file = $dir . $slug . '.json';
        if (file_exists($file)) { $skipped++; continue; }
        $data = [
            'id'            => $r['id'],
            'customer_name' => $r['customer_name'],
            'rating'        => (int)$r['rating'],
            'category'      => $r['category'] ?? '',
            'review_text'   => $r['review_text'],
            'location'      => $r['location'] ?? '',
            'created_at'    => $r['created_at'],
            'status'        => 'approved',
            'source'        => 'ai_generated',
            'verified'      => true,
            'helpful_count' => (int)($r['helpful_count'] ?? 0),
        ];
        if (@file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false) $restored++;
    }
    return ['restored' => $restored, 'skipped' => $skipped, 'total_db' => count($rows)];
}

// ── RESTORE FAQs (JSON files from faqs DB) ──────────────────────────────────
function restore_faqs(Database $db, string $root): array {
    $dir = $root . '/data/faqs/';
    ensure_dir($dir);

    $rows = [];
    try {
        $rows = $db->fetchAll("SELECT * FROM faqs WHERE is_active = 1 ORDER BY id") ?: [];
    } catch (\Exception $e) {
        return ['restored' => 0, 'skipped' => 0, 'error' => $e->getMessage()];
    }

    $restored = 0; $skipped = 0;
    foreach ($rows as $r) {
        $slug = $r['slug'] ?? ('faq-' . $r['id']);
        $file = $dir . $slug . '.json';
        if (file_exists($file)) { $skipped++; continue; }
        $data = [
            'id'            => $r['id'],
            'question'      => $r['question'],
            'answer'        => $r['answer'],
            'category'      => $r['category'] ?? '',
            'category_name' => $r['category'] ?? '',
            'slug'          => $slug,
            'created_at'    => $r['created_at'] ?? date('Y-m-d H:i:s'),
            'is_active'     => true,
            'views'         => 0,
            'helpful_count' => 0,
        ];
        if (@file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT)) !== false) $restored++;
    }
    return ['restored' => $restored, 'skipped' => $skipped, 'total_db' => count($rows)];
}

// ── SYNC PHP FILES → DB (rebuild generated_pages records from slug filenames) ─
function sync_filesystem_to_db(Database $db, string $root): array {
    $gen_dir = $root . '/generated-pages/';
    if (!is_dir($gen_dir)) {
        return ['synced' => 0, 'skipped' => 0, 'error' => 'generated-pages/ directory not found'];
    }

    $files = glob($gen_dir . '*.php') ?: [];
    $synced  = 0;
    $skipped = 0;
    $errors  = [];

    // Load existing slugs from DB to avoid redundant INSERTs
    $existing = [];
    try {
        $rows = $db->fetchAll("SELECT page_slug FROM generated_pages WHERE page_slug IS NOT NULL AND page_slug != ''") ?: [];
        foreach ($rows as $r) $existing[$r['page_slug']] = true;
    } catch (\Exception $e) {}

    // Load keyword and area lookup tables
    $kw_map   = []; // keyword_slug => ['id', 'keyword_name', 'category']
    $area_map = []; // area_slug => ['id', 'area_name']
    try {
        $kws = $db->fetchAll("SELECT id, keyword_slug, keyword_name, category FROM seo_service_keywords WHERE is_active = 1") ?: [];
        foreach ($kws as $k) $kw_map[$k['keyword_slug']] = $k;
        $areas = $db->fetchAll("SELECT id, area_slug, area_name FROM service_areas WHERE is_active = 1") ?: [];
        foreach ($areas as $a) $area_map[$a['area_slug']] = $a;
    } catch (\Exception $e) {
        return ['synced' => 0, 'skipped' => 0, 'error' => 'Failed to load keywords/areas: ' . $e->getMessage()];
    }

    // Category → service slug map (for service_id lookup)
    $cat_to_svc = [
        'PIGEON NETS'      => 'pigeon-nets',
        'BIRD NETS'        => 'bird-nets',
        'SAFETY NETS'      => 'balcony-safety-nets',
        'INVISIBLE GRILLS' => 'invisible-grills',
        'SPORTS NETS'      => 'sports-nets',
        'CLOTH HANGERS'    => 'cloth-hangers',
    ];
    $svc_id_cache = [];

    foreach ($files as $file) {
        $fname = basename($file, '.php');
        if ($fname === 'index') { $skipped++; continue; }

        // Slug format: keyword-slug-in-area-slug
        if (!preg_match('/^(.+)-in-(.+)$/', $fname, $m)) { $skipped++; continue; }
        $kw_part   = $m[1];
        $area_part = $m[2];

        // Already in DB
        if (isset($existing[$fname])) { $skipped++; continue; }

        // Look up keyword — try exact match first, then progressively shorter
        $kw_row = null;
        if (isset($kw_map[$kw_part])) {
            $kw_row = $kw_map[$kw_part];
        } else {
            // Try matching by removing trailing segments (area slug may contain hyphens)
            $parts = explode('-', $fname);
            $in_pos = array_search('in', $parts);
            if ($in_pos !== false) {
                for ($len = $in_pos; $len >= 1; $len--) {
                    $try_kw = implode('-', array_slice($parts, 0, $len));
                    $try_area = implode('-', array_slice($parts, $len + 1));
                    if (isset($kw_map[$try_kw]) && isset($area_map[$try_area])) {
                        $kw_row   = $kw_map[$try_kw];
                        $area_part = $try_area;
                        break;
                    }
                }
            }
        }

        if (!$kw_row || !isset($area_map[$area_part])) { $skipped++; continue; }

        $area_row = $area_map[$area_part];
        $kw_id    = (int)$kw_row['id'];
        $area_id  = (int)$area_row['id'];
        $kw_name  = $kw_row['keyword_name'];
        $ar_name  = $area_row['area_name'];
        $category = $kw_row['category'];

        // Get service_id
        if (!isset($svc_id_cache[$category])) {
            $svc_slug = $cat_to_svc[$category] ?? '';
            $svc_id_cache[$category] = 0;
            if ($svc_slug) {
                try {
                    $svc = $db->fetchOne("SELECT id FROM services WHERE service_slug = ?", [$svc_slug]);
                    $svc_id_cache[$category] = (int)($svc['id'] ?? 0);
                } catch (\Exception $e) {}
            }
        }
        $svc_id = $svc_id_cache[$category];

        $page_title = ucwords($kw_name) . " in {$ar_name}, Chennai | GCM Netting Solutions";
        $h1         = "Professional " . ucwords($kw_name) . " in {$ar_name}, Chennai";
        $meta_desc  = "Professional {$kw_name} installation in {$ar_name}, Chennai. Quality materials, expert installation. Call 9912399224.";
        $mtime      = @filemtime($file);
        $created_at = $mtime ? date('Y-m-d H:i:s', $mtime) : date('Y-m-d H:i:s');

        try {
            $db->execute(
                "INSERT IGNORE INTO generated_pages
                 (service_id, keyword_id, area_id, page_slug, page_title, meta_description, h1_heading, is_published, generated_by, generated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, 'restored_from_file', ?)",
                [$svc_id, $kw_id, $area_id, $fname, $page_title, $meta_desc, $h1, $created_at]
            );
            $existing[$fname] = true;
            $synced++;
        } catch (\Exception $e) {
            $errors[] = $fname . ': ' . $e->getMessage();
        }
    }

    // Detect & remove orphaned DB records (slug in DB but no PHP file on server)
    $_file_slugs = [];
    foreach ($files as $_f) {
        $_fn = basename($_f, '.php');
        if ($_fn !== 'index') $_file_slugs[$_fn] = true;
    }
    $_orphaned = 0;
    try {
        $_all_db = $db->fetchAll("SELECT id, page_slug FROM generated_pages WHERE page_slug IS NOT NULL AND page_slug != ''");
        foreach ($_all_db as $_row) {
            if (!isset($_file_slugs[$_row['page_slug']])) {
                $db->execute("DELETE FROM generated_pages WHERE id = ?", [(int)$_row['id']]);
                $_orphaned++;
            }
        }
    } catch (\Exception $e) {
        $errors[] = 'Orphan cleanup: ' . $e->getMessage();
    }

    return ['synced' => $synced, 'skipped' => $skipped, 'total_files' => count($files), 'orphaned_removed' => $_orphaned, 'errors' => array_slice($errors, 0, 5)];
}

// ── DISPATCH ────────────────────────────────────────────────────────────────
$response = ['success' => true, 'type' => $type, 'results' => []];

if ($type === 'sync') {
    $response['results']['sync'] = sync_filesystem_to_db($db, $root);
    $response['counts_after'] = get_status($db, $root);
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit;
}

if ($type === 'all' || $type === 'service_pages') {
    $response['results']['service_pages'] = restore_service_pages($db, $root);
}
if ($type === 'all' || $type === 'blogs') {
    $response['results']['blogs'] = restore_blogs($db, $root);
}
if ($type === 'all' || $type === 'reviews') {
    $response['results']['reviews'] = restore_reviews($db, $root);
}
if ($type === 'all' || $type === 'faqs') {
    $response['results']['faqs'] = restore_faqs($db, $root);
}

// Append fresh counts after restore
$response['counts_after'] = get_status($db, $root);

echo json_encode($response, JSON_PRETTY_PRINT);
