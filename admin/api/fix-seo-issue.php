<?php
/**
 * Fix SEO Issues on Generated Pages
 * Targeted patches only — does NOT touch page body content
 * Actions: fix_meta_desc, fix_canonical, fix_title (single file or all)
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit;
}

@set_time_limit(180);

$root    = dirname(dirname(dirname(__FILE__)));
$gen_dir = $root . '/generated-pages/';
$input   = json_decode(file_get_contents('php://input'), true) ?: [];

$issue_type = $input['issue_type'] ?? '';   // missing_meta_desc | short_meta_desc | missing_canonical | missing_title
$target     = $input['target'] ?? 'all';    // 'all' or specific slug
$files_list = !empty($input['files']) ? (array)$input['files'] : [];

// ── Resolve which files to fix ────────────────────────────────────
if ($target !== 'all' && !empty($files_list)) {
    $files = array_map(fn($f) => $gen_dir . basename($f), $files_list);
    $files = array_filter($files, 'file_exists');
} else {
    $files = is_dir($gen_dir) ? (glob($gen_dir . '*.php') ?: []) : [];
    $files = array_filter($files, fn($f) => basename($f) !== 'index.php');
}

$fixed = 0; $skipped = 0; $errors = [];

// ── Helper: generate meta description from slug ───────────────────
function make_meta_desc($slug) {
    // Turn slug into readable text
    $text = ucwords(str_replace('-', ' ', $slug));
    // Try to detect service + area pattern (service-in-area)
    if (preg_match('/^(.+?)-in-(.+)$/', $slug, $m)) {
        $service = ucwords(str_replace('-', ' ', $m[1]));
        $area    = ucwords(str_replace('-', ' ', $m[2]));
        return "Expert {$service} installation in {$area}, Chennai. Professional service with 3-5 year warranty, HDPE quality nets. Free site visit. Call GCM Netting Solutions: +91 99123 99224";
    }
    // Pillar page
    $service = ucwords(str_replace('-', ' ', $slug));
    return "Premium {$service} services across Chennai. 150+ areas covered. Expert installation team, UV-stabilized HDPE nets, 3-5 year warranty. Call GCM Netting Solutions: +91 99123 99224";
}

// ── Helper: generate page title from slug ────────────────────────
function make_page_title($slug) {
    if (preg_match('/^(.+?)-in-(.+)$/', $slug, $m)) {
        $service = ucwords(str_replace('-', ' ', $m[1]));
        $area    = ucwords(str_replace('-', ' ', $m[2]));
        return "{$service} in {$area} Chennai | GCM Netting Solutions";
    }
    $service = ucwords(str_replace('-', ' ', $slug));
    return "{$service} in Chennai | Professional Installation | GCM Netting Solutions";
}

// ── Fix: missing/short meta description ──────────────────────────
function fix_meta_desc($file) {
    $slug = pathinfo(basename($file), PATHINFO_FILENAME);
    $desc = make_meta_desc($slug);
    $src  = @file_get_contents($file);
    if ($src === false) return false;

    // Replace existing empty/short meta_description
    if (preg_match('/\$meta_description\s*=\s*[\'"]([^\'"]*)[\'"]/', $src)) {
        $new_src = preg_replace(
            '/\$meta_description\s*=\s*[\'"][^\'"]*[\'"]/',
            '$meta_description = ' . "'" . addslashes($desc) . "'",
            $src
        );
    } else {
        // Inject after $page_title or at start of PHP block
        $new_src = preg_replace(
            '/(\$(?:page_title|meta_title)\s*=\s*[\'"][^\'"]*[\'"];)/',
            '$1' . "\n" . '$meta_description = \'' . addslashes($desc) . '\';',
            $src, 1
        );
        if ($new_src === $src) {
            // Fallback: inject after <?php
            $new_src = preg_replace('/(<\?php)/', '$1' . "\n" . '$meta_description = \'' . addslashes($desc) . '\';', $src, 1);
        }
    }

    return $new_src !== null && $new_src !== $src && file_put_contents($file, $new_src) !== false;
}

// ── Fix: missing canonical ────────────────────────────────────────
function fix_canonical($file) {
    $slug = pathinfo(basename($file), PATHINFO_FILENAME);
    $url  = SITE_URL . '/' . $slug;
    $src  = @file_get_contents($file);
    if ($src === false) return false;

    // Already has canonical?
    if (stripos($src, 'rel="canonical"') !== false || stripos($src, "rel='canonical'") !== false) return null; // skip

    // Inject canonical right before </head> or before the first include of modern-header
    $canonical_tag = "\n" . '<link rel="canonical" href="' . htmlspecialchars($url) . '">';
    if (stripos($src, '</head>') !== false) {
        $new_src = str_ireplace('</head>', $canonical_tag . "\n</head>", $src);
    } elseif (preg_match('/include.*?modern-header\.php.*?;/', $src)) {
        // Inject a $canonical_url variable before the include
        $new_src = preg_replace(
            '/(include.*?modern-header\.php.*?;)/',
            '$canonical_url = \'' . $url . '\';' . "\n" . '$1',
            $src, 1
        );
    } else {
        return false; // can't safely inject
    }

    return $new_src !== $src && file_put_contents($file, $new_src) !== false;
}

// ── Fix: missing page title ───────────────────────────────────────
function fix_page_title($file) {
    $slug  = pathinfo(basename($file), PATHINFO_FILENAME);
    $title = make_page_title($slug);
    $src   = @file_get_contents($file);
    if ($src === false) return false;

    if (preg_match('/\$(?:page_title|meta_title)\s*=\s*[\'"][^\'"]*[\'"]/', $src)) return null; // already has one

    $new_src = preg_replace(
        '/(<\?php\s*\/\*.*?\*\/\s*|<\?php\s+)/s',
        '$0' . '$page_title = \'' . addslashes($title) . '\';' . "\n",
        $src, 1
    );
    if ($new_src === null || $new_src === $src) {
        $new_src = preg_replace('/(<\?php)/', '$1' . "\n" . '$page_title = \'' . addslashes($title) . '\';', $src, 1);
    }
    return $new_src !== null && $new_src !== $src && file_put_contents($file, $new_src) !== false;
}

// ── Process files ─────────────────────────────────────────────────
foreach ($files as $file) {
    $result = null;
    switch ($issue_type) {
        case 'missing_meta_desc':
        case 'short_meta_desc':
            $result = fix_meta_desc($file); break;
        case 'missing_canonical':
            $result = fix_canonical($file); break;
        case 'missing_title':
            $result = fix_page_title($file); break;
        default:
            echo json_encode(['success' => false, 'message' => 'Unknown issue type: ' . $issue_type]); exit;
    }
    if ($result === true) $fixed++;
    elseif ($result === null) $skipped++;
    elseif ($result === false) $errors[] = basename($file);
}

echo json_encode([
    'success'  => true,
    'fixed'    => $fixed,
    'skipped'  => $skipped,
    'errors'   => count($errors),
    'message'  => "Fixed {$fixed} pages, skipped {$skipped} (already OK)" . (count($errors) ? ", errors on " . count($errors) . " files" : ""),
]);
