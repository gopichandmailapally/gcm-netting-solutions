<?php
/**
 * One-time migration: strips the old inline <style> block from every
 * generated service page and replaces it with the minimal version that
 * delegates all layout / font rules to gcm-service-pages.css (external).
 *
 * Run ONCE from browser after uploading the ZIP:
 *   https://gcmsafetynets.in/admin/api/update-page-styles.php
 * (admin session required)
 */
define('GCM_INIT', true);
require_once dirname(__DIR__, 2) . '/config/config.php';
require_once dirname(__DIR__, 2) . '/config/database.php';

// Require admin login
session_start();
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    exit('Unauthorized. Please log in to the admin panel first.');
}

$dir   = dirname(__DIR__, 2) . '/generated-pages/';
$files = glob($dir . '*.php');

if ($files === false) {
    exit('ERROR: Cannot read generated-pages/ directory.');
}

/* ── Minimal replacement <style> block ─────────────────────────────── */
$newStyle = '<style>
/* Layout & fonts handled by gcm-service-pages.css (external).
   Only the entry animation is defined inline. */
*{box-sizing:border-box;}body{overflow-x:hidden;margin:0;padding:0;}
@keyframes gcm-fi{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.gcm-content{animation:gcm-fi .6s ease-out;}
</style>';

$updated = 0;
$skipped = 0;
$errors  = [];

foreach ($files as $file) {
    $base = basename($file);
    if ($base === 'index.php') { $skipped++; continue; }

    $content = file_get_contents($file);
    if ($content === false) { $errors[] = $base . ': cannot read'; continue; }

    // Match any <style> block that contains .gcm-wrap or .gcm-grid
    // (these are specific to generated pages and not present in header/footer)
    if (strpos($content, '.gcm-wrap') === false && strpos($content, '.gcm-grid') === false) {
        // Already on new format (no old layout CSS) — skip
        $skipped++;
        continue;
    }

    // Replace the FIRST <style>...</style> block (the generated page's own block)
    $newContent = preg_replace('/<style>.*?<\/style>/s', $newStyle, $content, 1);

    if ($newContent === null) {
        $errors[] = $base . ': regex error';
        continue;
    }

    if ($newContent === $content) {
        $skipped++;
        continue;
    }

    if (file_put_contents($file, $newContent) === false) {
        $errors[] = $base . ': cannot write';
        continue;
    }

    $updated++;
}

$total = count($files);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Page Style Migration</title>
<style>
body{font-family:Arial,sans-serif;max-width:700px;margin:40px auto;padding:20px;background:#f8fafc;}
h1{color:#1e293b;}
.ok{color:#059669;font-weight:700;}
.skip{color:#6366f1;}
.err{color:#dc2626;font-weight:700;}
.box{background:#fff;border-radius:10px;padding:20px;margin-top:20px;box-shadow:0 2px 8px rgba(0,0,0,.08);}
</style>
</head>
<body>
<h1>Page Style Migration</h1>
<div class="box">
  <p>Total files scanned: <strong><?php echo $total; ?></strong></p>
  <p class="ok">&#10003; Updated: <strong><?php echo $updated; ?></strong> pages</p>
  <p class="skip">&#8212; Skipped (already updated or index): <strong><?php echo $skipped; ?></strong></p>
  <?php if (!empty($errors)): ?>
  <p class="err">&#9888; Errors: <?php echo count($errors); ?></p>
  <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
  <?php endif; ?>
  <?php if ($updated > 0): ?>
  <p style="margin-top:16px;padding:12px;background:#f0fdf4;border-left:4px solid #059669;border-radius:6px;">
    &#10003; Done! All <?php echo $updated; ?> pages now use <code>gcm-service-pages.css</code> for layout.
    Hard-refresh any service page to see the full-width layout.
  </p>
  <?php else: ?>
  <p style="margin-top:16px;padding:12px;background:#eff6ff;border-left:4px solid #6366f1;border-radius:6px;">
    All pages were already on the new format. No changes needed.
  </p>
  <?php endif; ?>
</div>
</body>
</html>
