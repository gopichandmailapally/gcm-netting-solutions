<?php
/**
 * TEMPORARY FIX SCRIPT - DELETE AFTER USE
 * Visit: gcmsafetynets.in/admin/fix-pages.php
 * This directly patches the forgot pages on the server disk
 * and clears OPcache to force PHP to use the new versions.
 */
header('Content-Type: text/plain; charset=utf-8');

$results = [];

// ── Step 1: Ensure ob_start() is at the top of both forgot pages ────
$targets = [
    __DIR__ . '/forgot-password.php',
    __DIR__ . '/forgot-username.php',
];

foreach ($targets as $path) {
    $name = basename($path);

    if (!file_exists($path)) {
        $results[] = "ERROR: $name not found on disk!";
        continue;
    }

    $content = file_get_contents($path);
    if ($content === false) {
        $results[] = "ERROR: Cannot read $name";
        continue;
    }

    $modified = date('Y-m-d H:i:s', filemtime($path));
    $lines    = substr_count($content, "\n");
    $hasOb    = strpos($content, 'ob_start') !== false;

    if ($hasOb) {
        $results[] = "OK: $name already has ob_start() [modified=$modified, lines=$lines]";
    } else {
        // Insert ob_start() right after the opening <?php tag
        $new = preg_replace('/^<\?php\s*\n/', "<?php\nob_start();\n", $content, 1);
        if ($new !== null && $new !== $content) {
            if (file_put_contents($path, $new) !== false) {
                $results[] = "FIXED: $name - ob_start() inserted [was lines=$lines]";
            } else {
                $results[] = "ERROR: Cannot write to $name - check permissions!";
            }
        } else {
            $results[] = "WARN: $name - Could not insert ob_start() via regex";
        }
    }
}

// ── Step 2: Clear OPcache ────────────────────────────────────────────
if (function_exists('opcache_reset')) {
    opcache_reset();
    $results[] = "OK: OPcache reset - all cached PHP scripts cleared";
} else {
    $results[] = "INFO: opcache_reset() not available - using invalidate only";
}

if (function_exists('opcache_invalidate')) {
    foreach ($targets as $path) {
        opcache_invalidate($path, true);
    }
    $results[] = "OK: opcache_invalidate() called for both files";
}

// ── Step 3: Output results ───────────────────────────────────────────
echo "=== FIX RESULTS ===\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}

echo "\n=== NEXT STEPS ===\n";
echo "1. Visit: gcmsafetynets.in/admin/forgot-password.php\n";
echo "2. Visit: gcmsafetynets.in/admin/forgot-username.php\n";
echo "3. Delete this file (fix-pages.php) after confirming they work\n";
