<?php
/**
 * GCM Netting Solutions — Unique Content Migration Engine
 * Migrates 12,000+ static generated page texts into MariaDB `generated_pages` table
 * Preserves 100% of unique copy while stripping malformed nested HTML tags.
 */

define('GCM_INIT', true);
require_once __DIR__ . '/config/config.php';

$pages_dir = __DIR__ . '/generated-pages';

if (!is_dir($pages_dir)) {
    die("ERROR: {$pages_dir} directory not found.\n");
}

echo "Connecting to MariaDB (" . DB_NAME . ")...\n";
try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

$start_time = microtime(true);
$files = glob($pages_dir . '/*.php');

// Filter out .bak files
$valid_files = [];
foreach ($files as $f) {
    $base = basename($f);
    if (strpos($base, '.bak') === false) {
        $valid_files[] = $f;
    }
}

$total_files = count($valid_files);
echo "Found {$total_files} active page files to migrate...\n";

$sql = "INSERT INTO generated_pages 
    (slug, keyword_slug, area_slug, page_title, meta_description, meta_keywords, content, word_count, is_published, status, generated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 'completed', NOW())
    ON DUPLICATE KEY UPDATE
    page_title = VALUES(page_title),
    meta_description = VALUES(meta_description),
    meta_keywords = VALUES(meta_keywords),
    content = VALUES(content),
    word_count = VALUES(word_count),
    updated_at = NOW()";

$stmt = $pdo->prepare($sql);

$pdo->beginTransaction();
$migrated = 0;

foreach ($valid_files as $idx => $file_path) {
    $filename = basename($file_path);
    $slug = substr($filename, 0, -4); // remove .php
    
    $html = @file_get_contents($file_path);
    if (!$html) continue;

    // 1. Title
    $title = '';
    if (preg_match('/\$page_title\s*=\s*[\'"]([^\'"]+)[\'"];/', $html, $m)) {
        $title = trim($m[1]);
    }

    // 2. Meta description
    $desc = '';
    if (preg_match('/\$meta_description\s*=\s*[\'"]([^\'"]+)[\'"];/', $html, $m)) {
        $desc = trim($m[1]);
    }

    // 3. Meta keywords
    $keywords = '';
    if (preg_match('/\$meta_keywords\s*=\s*[\'"]([^\'"]+)[\'"];/', $html, $m)) {
        $keywords = trim($m[1]);
    }

    // 4. Extract content
    $content = '';
    $start_tag = '<div class="gcm-content">';
    $s_idx = strpos($html, $start_tag);
    if ($s_idx === false) {
        $start_tag = "<div class='gcm-content'>";
        $s_idx = strpos($html, $start_tag);
    }

    if ($s_idx !== false) {
        $raw = substr($html, $s_idx + strlen($start_tag));
        $end_marker = '</div><!-- /main -->';
        $e_idx = strpos($raw, $end_marker);
        if ($e_idx !== false) {
            $raw = substr($raw, 0, $e_idx);
            $raw = preg_replace('/\s*<\/div>\s*<\/div>\s*$/', '', $raw);
        }

        // Clean nested tags
        $raw = preg_replace('/<!DOCTYPE[^>]*>/i', '', $raw);
        $raw = preg_replace('/<\/?(html|head|body)[^>]*>/i', '', $raw);
        $raw = preg_replace('/<title>[^<]*<\/title>/i', '', $raw);
        $raw = preg_replace('/<meta[^>]*>/i', '', $raw);
        $raw = preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $raw);
        $raw = preg_replace('/<h1\b[^>]*>.*?<\/h1>/is', '', $raw, 1);
        $content = trim($raw);
    }

    // Slugs
    $parts = explode('-in-', $slug, 2);
    $keyword_slug = $parts[0] ?? '';
    $area_slug    = $parts[1] ?? '';

    $word_count = $content ? str_word_count(strip_tags($content)) : 0;

    $stmt->execute([
        $slug,
        $keyword_slug,
        $area_slug,
        mb_substr($title, 0, 255),
        $desc,
        $keywords,
        $content,
        $word_count
    ]);

    $migrated++;

    if ($migrated % 500 === 0) {
        $pdo->commit();
        $pdo->beginTransaction();
        $percent = round(($migrated / $total_files) * 100, 1);
        echo "Progress: {$migrated} / {$total_files} pages migrated ({$percent}%)...\n";
    }
}

if ($pdo->inTransaction()) {
    $pdo->commit();
}

$duration = round(microtime(true) - $start_time, 2);
echo "SUCCESS! Migrated {$migrated} pages into MariaDB table `generated_pages` in {$duration} seconds.\n";
