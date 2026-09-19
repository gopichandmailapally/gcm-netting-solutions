<?php
/**
 * Batch-update hero H1 prefix & subtitle prefix on generated pages.
 * Only touches the first word of the H1 and the subtitle — body content is untouched.
 * Creates .title.bak backup before every write.
 */
define('GCM_INIT', true);
require_once dirname(__DIR__, 2) . '/config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

@set_time_limit(120);
@ini_set('memory_limit', '256M');
header('Content-Type: application/json');

$root    = dirname(__DIR__, 2);
$gen_dir = $root . '/generated-pages/';

// ── Load 55-word prefix list ──────────────────────────────────────────────
$h1_prefixes = require $root . '/config/hero-title-prefixes.php';

// ── Category → keyword-slug mapping (for prefix exclusion rules) ──────────
// NET categories (polymer/HDPE — no rust-related or ISO words)
$nets_slugs = [
    // Pigeon Nets
    'pigeon-nets','pigeon-net','balcony-netting','pigeon-net-for-balcony',
    'pigeon-nets-installation','pigeon-bird-netting','pigeon-net-installation',
    'pigeon-net-near-me','pigeon-net-for-balcony-near-me','pigeon-net-installation-near-me',
    'pigeon-safety-nets','pigeon-net-price','kabutar-jali-near-me',
    // Bird Nets
    'bird-nets','bird-net','bird-net-for-balcony','bird-net-near-me',
    'nets-for-birds','net-for-birds','industrial-bird-netting','bird-netting','anti-bird-netting',
    // Safety Nets
    'safety-nets','balcony-safety-nets','safety-nets-for-balconies','duct-area-safety-nets',
    'monkey-safety-nets','construction-safety-nets','industrial-safety-nets',
    'fall-safety-nets','fall-protection-nets','children-safety-nets','pet-safety-nets',
    // Sports Nets
    'cricket-nets','cricket-nets-price','cricket-nets-near-me','cricket-practice-net',
    'cricket-practice-nets','cricket-net-price','cricket-indoor-nets-near-me',
    'indoor-cricket-nets-near-me','sports-nets','sports-netting','cricket-netting',
    'box-cricket-net','cricket-net-installation',
];
// METAL categories (stainless steel — no UV/eco/polymer words)
$metal_slugs = [
    // Invisible Grills
    'invisible-grills','invisible-grill-near-me','ss-invisible-grills',
    'invisible-grill-for-balcony','balcony-invisible-grill',
    'invisible-grill-for-balcony-near-me','invisible-safety-grill',
    'invisible-grill-for-safety','invisible-grill-for-pigeons',
    // Cloth Hangers
    'ceiling-cloth-hangers','dry-cloth-hangers','cloth-drying-hangers',
    'cloth-hanger-for-balcony','pulley-cloth-drying-hanger','pulley-cloth-hanger',
    'laundry-hanger-dryer','clothes-hanger-to-dry-clothes','clothes-hanger-drier',
];
// Words to EXCLUDE per category type
$nets_excluded  = ['Anti-Rust','Ultra-Strong','ISO-Certified'];
$metal_excluded = ['UV-Protected','Virgin-Quality','Safety-Tested','Eco-Friendly'];

/**
 * Detect category type ('nets', 'metal', or '') from the page filename.
 * Filename format: {keyword-slug}-{area-slug}.php
 */
function detect_category_type(string $file, array $nets_slugs, array $metal_slugs): string {
    $base = basename($file, '.php');
    // Sort slugs longest-first to avoid partial prefix matches
    $all_nets = $nets_slugs;
    $all_metal = $metal_slugs;
    usort($all_nets,  fn($a,$b) => strlen($b)-strlen($a));
    usort($all_metal, fn($a,$b) => strlen($b)-strlen($a));
    foreach ($all_nets  as $s) { if (strncmp($base, $s . '-', strlen($s)+1) === 0 || $base === $s) return 'nets'; }
    foreach ($all_metal as $s) { if (strncmp($base, $s . '-', strlen($s)+1) === 0 || $base === $s) return 'metal'; }
    return '';
}

// All known prefixes that might already be in existing pages
$known_prefixes = array_unique(array_merge($h1_prefixes, [
    'Professional','Trusted','Expert','Premium','Quality','Certified','Reliable',
    'Superior','Elite','Top-Rated','Best','No.1','Branded','Verified','Specialized',
    'Dependable','Proven','Award-Winning','5-Star','High-Quality',
]));
// Build regex alternation, longest first to avoid partial matches
usort($known_prefixes, fn($a,$b) => strlen($b) - strlen($a));
$ptn = implode('|', array_map('preg_quote', $known_prefixes));

// Subtitle word pool (shorter list — subtitle is less critical for SEO)
$sub_prefixes = ['Trusted','Reliable','Expert','Premium','Professional','Quality','Certified',
                 'Superior','Elite','Dependable','Genuine','Verified','Specialized','Top-Rated',
                 'Affordable','Experienced','Proven','Best','No.1','Branded'];

$action = trim($_POST['action'] ?? $_GET['action'] ?? 'scan');
$offset = (int)($_POST['offset'] ?? $_GET['offset'] ?? 0);
$limit  = (int)($_POST['limit']  ?? $_GET['limit']  ?? 20);

// ── Helpers ───────────────────────────────────────────────────────────────

function get_pages(string $dir): array {
    $files = glob($dir . '*.php');
    if (!$files) return [];
    return array_values(array_filter($files, fn($f) => basename($f) !== 'index.php'));
}

/**
 * Returns true if the page has a detectable hero H1 with a known prefix.
 */
function has_replaceable_title(string $file, string $ptn): bool {
    $html = file_get_contents($file);
    if ($html === false) return false;
    // Match H1 from our template (has the large inline style)
    return (bool) preg_match('/<h1[^>]*style="font-size:46px[^"]*">(' . $ptn . ')\s+/', $html)
        || (bool) preg_match('/<h1[^>]*>(' . $ptn . ')\s+[^<]+ in [^<]+, Chennai<\/h1>/i', $html);
}

/**
 * Update H1 prefix and subtitle prefix in a single page file.
 * Returns 'updated', 'skipped', or 'error:...'
 */
function update_title_prefix(string $file, string $ptn, array $h1_prefixes, array $sub_prefixes,
                              array $nets_slugs, array $metal_slugs,
                              array $nets_excluded, array $metal_excluded): string {
    $html = file_get_contents($file);
    if ($html === false) return 'error:read_failed';

    // ── Filter prefix pool based on product category ──────────────────────
    $cat = detect_category_type($file, $nets_slugs, $metal_slugs);
    if ($cat === 'nets') {
        $filtered_h1  = array_values(array_filter($h1_prefixes,  fn($w) => !in_array($w, $nets_excluded, true)));
        $filtered_sub = array_values(array_filter($sub_prefixes, fn($w) => !in_array($w, $nets_excluded, true)));
    } elseif ($cat === 'metal') {
        $filtered_h1  = array_values(array_filter($h1_prefixes,  fn($w) => !in_array($w, $metal_excluded, true)));
        $filtered_sub = array_values(array_filter($sub_prefixes, fn($w) => !in_array($w, $metal_excluded, true)));
    } else {
        $filtered_h1  = $h1_prefixes;
        $filtered_sub = $sub_prefixes;
    }
    if (empty($filtered_h1))  $filtered_h1  = $h1_prefixes;
    if (empty($filtered_sub)) $filtered_sub = $sub_prefixes;

    $new_h1  = $filtered_h1[array_rand($filtered_h1)];
    $new_sub = $filtered_sub[array_rand($filtered_sub)];

    // Replace H1 prefix — matches our template's large inline h1 style
    $h1_pattern_full = '/<h1([^>]*style="font-size:46px[^"]*")>(' . $ptn . ')(\s+)/';
    $h1_pattern_alt  = '/<h1([^>]*)>(' . $ptn . ')(\s+)([^<]+ in [^<]+, Chennai<\/h1>)/i';

    $updated_h1  = false;
    $updated_sub = false;

    $new_html = preg_replace_callback(
        '/<h1([^>]*style="font-size:46px[^"]*")>(' . $ptn . ')(\s+)/U',
        function($m) use ($new_h1, &$updated_h1) {
            $updated_h1 = true;
            return '<h1' . $m[1] . '>' . $new_h1 . $m[3];
        },
        $html
    );

    if (!$updated_h1) {
        // Fallback: generic h1 with "in X, Chennai" pattern
        $new_html = preg_replace_callback(
            '/<h1([^>]*)>(' . $ptn . ')(\s+)([^<]+ in [^<]+, Chennai)<\/h1>/iU',
            function($m) use ($new_h1, &$updated_h1) {
                $updated_h1 = true;
                return '<h1' . $m[1] . '>' . $new_h1 . $m[3] . $m[4] . '</h1>';
            },
            $html
        );
    }

    if (!$updated_h1) return 'skipped';

    // Replace subtitle prefix — targets our specific subtitle paragraph style
    $new_html2 = preg_replace_callback(
        '/<p([^>]*style="font-size:20px[^"]*")>(' . $ptn . ')(\s+)/U',
        function($m) use ($new_sub, &$updated_sub) {
            $updated_sub = true;
            return '<p' . $m[1] . '>' . $new_sub . $m[3];
        },
        $new_html
    );

    $final = $updated_sub ? $new_html2 : $new_html;

    if ($final === $html) return 'skipped';

    // Backup
    file_put_contents($file . '.title.bak', $html);

    if (file_put_contents($file, $final) === false) return 'error:write_failed';
    return 'updated';
}

// ── Actions ───────────────────────────────────────────────────────────────

if ($action === 'scan') {
    $files       = get_pages($gen_dir);
    $total       = count($files);
    $replaceable = 0;
    foreach ($files as $f) {
        if (has_replaceable_title($f, $ptn)) $replaceable++;
    }
    echo json_encode([
        'success'      => true,
        'total'        => $total,
        'replaceable'  => $replaceable,
        'already_varied' => $total - $replaceable,
    ]);
    exit;
}

if ($action === 'update') {
    $files   = get_pages($gen_dir);
    $total   = count($files);
    $slice   = array_slice($files, $offset, $limit);
    $updated = 0; $skipped = 0; $errors = [];

    foreach ($slice as $f) {
        $result = update_title_prefix($f, $ptn, $h1_prefixes, $sub_prefixes, $nets_slugs, $metal_slugs, $nets_excluded, $metal_excluded);
        if ($result === 'updated')       $updated++;
        elseif ($result === 'skipped')   $skipped++;
        else                             $errors[] = basename($f) . ': ' . $result;
    }

    $processed = $offset + count($slice);
    echo json_encode([
        'success'   => true,
        'total'     => $total,
        'processed' => $processed,
        'updated'   => $updated,
        'skipped'   => $skipped,
        'errors'    => $errors,
        'completed' => ($processed >= $total),
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
