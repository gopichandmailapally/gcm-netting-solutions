<?php
/**
 * Safe SEO Batch Optimizer — zero content loss
 * Only touches: meta title, meta description, canonical, keywords, schema JSON-LD, image attrs.
 * NEVER modifies body content. Creates .bak backup before every write.
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

@set_time_limit(300);
@ini_set('memory_limit', '256M');

$root    = dirname(dirname(dirname(__FILE__)));
$gen_dir = $root . '/generated-pages/';

$batch_size = max(1, (int)($_POST['batch_size'] ?? 100));
$offset     = max(0, (int)($_POST['offset'] ?? 0));

// Sorted list of all generated PHP pages
$all_files = is_dir($gen_dir) ? (glob($gen_dir . '*.php') ?: []) : [];
$all_files = array_values(array_filter($all_files, fn($f) => basename($f) !== 'index.php'));
sort($all_files);

$total     = count($all_files);
$batch     = array_slice($all_files, $offset, $batch_size);
$optimized = 0;
$skipped   = 0;
$errors    = 0;
$details   = [];

foreach ($batch as $file) {
    $r = seo_safe_fix($file);
    if ($r === true)     { $optimized++; }
    elseif ($r === null) { $skipped++;   }
    else                 { $errors++;    $details[] = basename($file) . ': ' . $r; }
}

$processed = min($offset + $batch_size, $total);
echo json_encode([
    'success'   => true,
    'results'   => ['optimized' => $optimized, 'skipped' => $skipped, 'failed' => $errors],
    'processed' => $processed,
    'total'     => $total,
    'completed' => ($processed >= $total),
    'details'   => $details,
]);


// =============================================================
// Core safe fix — returns true | null (skipped) | string (error)
// =============================================================
function seo_safe_fix(string $file)
{
    $src = @file_get_contents($file);
    if ($src === false) return 'Cannot read file';

    $slug = pathinfo(basename($file), PATHINFO_FILENAME);
    $new  = $src;

    // ── Detect service + area ──────────────────────────────────
    if (preg_match('/^(.+?)-in-(.+)$/', $slug, $m)) {
        $service      = ucwords(str_replace('-', ' ', $m[1]));
        $area         = ucwords(str_replace('-', ' ', $m[2]));
        $is_area_page = true;
    } else {
        $service      = ucwords(str_replace('-', ' ', $slug));
        $area         = 'Chennai';
        $is_area_page = false;
    }

    $canonical = SITE_URL . '/' . $slug;

    $ideal_title = $is_area_page
        ? "{$service} in {$area} Chennai | GCM Netting Solutions"
        : "{$service} in Chennai | Expert Installation | GCM Netting Solutions";

    $ideal_desc = $is_area_page
        ? "Expert {$service} installation in {$area}, Chennai. HDPE quality nets, 3-5 year warranty, free site visit. Trusted by 5000+ customers. Call GCM Netting Solutions: +91 99123 99224."
        : "Professional {$service} services across Chennai & 188+ areas. HDPE quality nets, 3-5 year warranty. Free site visit. Call GCM Netting Solutions: +91 99123 99224.";

    $ideal_kws = "{$service}, {$service} {$area}, {$service} installation Chennai, {$service} near me, safety nets Chennai, GCM Netting Solutions";

    // ── 1. Meta Title ──────────────────────────────────────────
    if (preg_match('/\$(?:meta_title|page_title)\s*=\s*[\'"]([^\'"]*)[\'"]/', $new, $tm)) {
        if (strlen(trim($tm[1])) < 30 || strlen(trim($tm[1])) > 70) {
            $new = preg_replace(
                '/(\$(?:meta_title|page_title)\s*=\s*)[\'"][^\'"]*[\'"]/',
                '$1\'' . addslashes($ideal_title) . '\'',
                $new, 1
            );
        }
    } else {
        $new = preg_replace(
            '/(<\?php\b)/',
            '$1' . "\n" . '$page_title = \'' . addslashes($ideal_title) . '\';',
            $new, 1
        );
    }

    // ── 2. Meta Description ────────────────────────────────────
    if (preg_match('/\$meta_description\s*=\s*[\'"]([^\'"]*)[\'"]/', $new, $dm)) {
        if (strlen(trim($dm[1])) < 120 || strlen(trim($dm[1])) > 165) {
            $new = preg_replace(
                '/\$meta_description\s*=\s*[\'"][^\'"]*[\'"]/',
                '$meta_description = \'' . addslashes($ideal_desc) . '\'',
                $new, 1
            );
        }
    } else {
        $new = preg_replace(
            '/(\$(?:page_title|meta_title)\s*=\s*[\'"][^\'"]*[\'"];)/',
            '$1' . "\n" . '$meta_description = \'' . addslashes($ideal_desc) . '\';',
            $new, 1
        );
    }

    // ── 3. Canonical URL ───────────────────────────────────────
    if (!preg_match('/\$canonical_url\s*=/', $new)) {
        $new = preg_replace(
            '/(\$meta_description\s*=\s*[\'"][^\'"]*[\'"];)/',
            '$1' . "\n" . '$canonical_url = \'' . $canonical . '\';',
            $new, 1
        );
    }

    // ── 4. Meta Keywords ───────────────────────────────────────
    if (!preg_match('/\$meta_keywords\s*=/', $new)) {
        $new = preg_replace(
            '/(\$(?:canonical_url|meta_description)\s*=\s*[\'"][^\'"]*[\'"];)/',
            '$1' . "\n" . '$meta_keywords = \'' . addslashes($ideal_kws) . '\';',
            $new, 1
        );
    }

    // ── 5. Schema JSON-LD — inject as static HTML block ────────
    //    We close PHP, write the <script> tag, then reopen PHP.
    //    This way: no complex PHP string escaping, no parse risk.
    if (stripos($new, 'application/ld+json') === false) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type'    => 'Service',
            'name'     => $service . ' in ' . $area . ' Chennai',
            'provider' => [
                '@type'     => 'LocalBusiness',
                'name'      => 'GCM Netting Solutions',
                'telephone' => '+919912399224',
                'address'   => [
                    '@type'           => 'PostalAddress',
                    'addressLocality' => 'Chennai',
                    'addressRegion'   => 'Tamil Nadu',
                    'addressCountry'  => 'IN',
                ],
            ],
            'areaServed'       => $area,
            'serviceType'      => $service,
            'aggregateRating'  => [
                '@type'       => 'AggregateRating',
                'ratingValue' => '4.8',
                'reviewCount' => '500',
            ],
        ];
        $json_str = json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // Inject right after the header include (uses close/open PHP tag pattern)
        $close_open   = "\n" . '?>' . "\n";
        $reopen_php   = "\n" . '<?php' . "\n";
        $schema_block = $close_open
                      . '<script type="application/ld+json">' . "\n"
                      . $json_str . "\n"
                      . '</script>'
                      . $reopen_php;

        $new = preg_replace(
            '/(include\s*[\'"][^\'"]*modern-header\.php[\'"];\s*)/i',
            '$1' . $schema_block,
            $new, 1
        );
    }

    // ── 6. Image lazy-loading + alt text (safe — only adds missing attrs) ──
    $new = preg_replace_callback('/<img([^>]+)>/i', function ($m) use ($service, $area) {
        $tag = $m[0];
        if (stripos($tag, 'loading=') === false) {
            $tag = str_replace('<img', '<img loading="lazy"', $tag);
        }
        if (stripos($tag, 'alt=') === false) {
            $alt = htmlspecialchars($service . ' in ' . $area . ' - GCM Netting Solutions');
            $tag = str_replace('<img', '<img alt="' . $alt . '"', $tag);
        }
        return $tag;
    }, $new);

    // ── Write only if something changed ────────────────────────
    if ($new === $src) return null;

    @copy($file, $file . '.bak'); // backup

    if (file_put_contents($file, $new) === false) return 'Write failed';

    // Log to DB (non-fatal)
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if (!$conn->connect_error) {
            $stmt = $conn->prepare(
                "INSERT INTO seo_optimization_log (page_path, optimization_type, status, created_at)
                 VALUES (?, 'safe_meta_fix', 'completed', NOW())
                 ON DUPLICATE KEY UPDATE status='completed', created_at=NOW()"
            );
            if ($stmt) { $stmt->bind_param('s', $file); $stmt->execute(); $stmt->close(); }
            $conn->close();
        }
    } catch (Throwable $e) { /* non-fatal */ }

    return true;
}
