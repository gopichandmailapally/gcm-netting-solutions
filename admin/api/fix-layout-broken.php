<?php
/**
 * fix-layout-broken.php — Safe Layout Repair API
 *
 * ROOT CAUSE: When the old AI optimizer expanded page content, it injected
 * extra </div> closing tags inside the .gcm-content area. These extra closes
 * prematurely close .gcm-card and .gcm-main, causing .gcm-sidebar to fall
 * OUTSIDE .gcm-grid — rendering the sidebar below main content instead of
 * on the right side.
 *
 * FIX: Counts the div open/close balance between <div class="gcm-grid"> and
 * <div class="gcm-sidebar">. If the balance < 1 (gcm-grid was closed too early),
 * removes exactly the right number of excess </div> tags from that region.
 *
 * SAFETY: Never changes page content text. Creates .layout.bak backup before
 * every write. Only touches div nesting structure.
 *
 * Actions: scan | fix
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

@set_time_limit(120);
@ini_set('memory_limit', '256M');

$root    = dirname(dirname(dirname(__FILE__)));
$gen_dir = $root . '/generated-pages/';

$action     = $_POST['action'] ?? 'scan';
$batch_size = max(1, min(200, (int)($_POST['batch_size'] ?? 100)));
$offset     = max(0, (int)($_POST['offset'] ?? 0));

$single_file = trim($_POST['file'] ?? '');

$all_files = glob($gen_dir . '*.php') ?: [];
$all_files = array_values(array_filter($all_files, fn($f) => basename($f) !== 'index.php'));
sort($all_files);
$total = count($all_files);

// Single-file mode: fix or scan just one filename
if ($single_file !== '' && $action === 'fix') {
    $target = $gen_dir . basename($single_file);
    if (!file_exists($target)) {
        echo json_encode(['success' => false, 'message' => 'File not found: ' . basename($single_file)]);
        exit;
    }
    $r = fix_layout($target);
    echo json_encode([
        'success' => true,
        'action'  => 'fix',
        'fixed'   => ($r === true) ? 1 : 0,
        'skipped' => ($r === null) ? 1 : 0,
        'errors'  => is_string($r) ? [$r] : [],
    ]);
    exit;
}

$batch = array_slice($all_files, $offset, $batch_size);

// ── SCAN ────────────────────────────────────────────────────────
if ($action === 'scan') {
    $broken = [];
    $clean  = 0;
    foreach ($batch as $file) {
        $excess = check_layout($file);
        if ($excess > 0) {
            $broken[] = [
                'file'   => basename($file),
                'excess' => $excess,
            ];
        } else {
            $clean++;
        }
    }
    $processed = min($offset + $batch_size, $total);
    echo json_encode([
        'success'      => true,
        'action'       => 'scan',
        'broken'       => $broken,
        'broken_count' => count($broken),
        'clean'        => $clean,
        'processed'    => $processed,
        'total'        => $total,
        'completed'    => ($processed >= $total),
    ]);
    exit;
}

// ── FIX ─────────────────────────────────────────────────────────
if ($action === 'fix') {
    $fixed  = 0;
    $skipped = 0;
    $errors  = [];
    foreach ($batch as $file) {
        $r = fix_layout($file);
        if ($r === true)    { $fixed++;   }
        elseif ($r === null){ $skipped++; }
        else                { $errors[] = basename($file) . ': ' . $r; }
    }
    $processed = min($offset + $batch_size, $total);
    echo json_encode([
        'success'   => true,
        'action'    => 'fix',
        'fixed'     => $fixed,
        'skipped'   => $skipped,
        'errors'    => $errors,
        'processed' => $processed,
        'total'     => $total,
        'completed' => ($processed >= $total),
    ]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action: ' . htmlspecialchars($action)]);

// ================================================================
// check_layout($file)
//
// ROOT CAUSE (confirmed by diagnostics):
//   The </div> that closes <div class="gcm-content"> is missing.
//   As a result, the CTA bar and sidebar both end up nested inside
//   gcm-content → gcm-main, so CSS grid cannot place the sidebar
//   as a second column — it stacks below the main content instead.
//
// DETECTION:
//   Look for "<!-- CTA bar -->" in the file. The </div> that closes
//   gcm-content MUST appear on the line(s) immediately before it
//   (with only whitespace in between). If it is absent, the page
//   is broken.
//
//   Also detects the older "excess closing div" variant (net < 1)
//   for backwards compatibility.
//
// Returns: 0 = OK, 1 = missing gcm-content close, 2 = excess closes
// ================================================================
function check_layout(string $file): int
{
    $src = @file_get_contents($file);
    if ($src === false) return 0;

    // Must have the two-column structure to be relevant
    if (strpos($src, '<div class="gcm-grid">') === false) return 0;
    if (strpos($src, '<div class="gcm-sidebar">') === false) return 0;

    // ── Primary check: missing </div> before CTA bar ──────────────
    $cta_pos = strpos($src, '<!-- CTA bar -->');
    if ($cta_pos !== false) {
        // Grab up to 80 chars before the CTA comment
        $pre_cta = substr($src, max(0, $cta_pos - 80), min(80, $cta_pos));
        // If there is NO </div> in that window, gcm-content was never closed
        if (strpos($pre_cta, '</div>') === false) {
            return 1; // missing gcm-content close
        }
    }

    // ── Secondary check: excess closing divs (old variant) ────────
    $grid_pos    = strpos($src, '<div class="gcm-grid">');
    $sidebar_pos = strpos($src, '<div class="gcm-sidebar">');
    if ($sidebar_pos > $grid_pos) {
        $between = substr($src, $grid_pos, $sidebar_pos - $grid_pos);
        $opens   = preg_match_all('/<div\b/', $between);
        $closes  = substr_count($between, '</div>');
        $net     = $opens - $closes;
        if ($net < 1) return 2; // excess closing divs
    }

    return 0; // layout is fine
}

// ================================================================
// fix_layout($file)
//
// Case 1 (return 1): inserts the missing </div> that closes
//   gcm-content — placed on the line just before "<!-- CTA bar -->".
//
// Case 2 (return 2): removes excess </div> tags between gcm-grid
//   and gcm-sidebar (legacy variant).
//
// Returns: true (fixed) | null (not broken) | string (error msg)
// ================================================================
function fix_layout(string $file)
{
    $src = @file_get_contents($file);
    if ($src === false) return 'Cannot read file';

    $type = check_layout($file);
    if ($type === 0) return null; // not broken

    if ($type === 1) {
        // ── Insert missing </div> before <!-- CTA bar --> ──────────
        // Replace the first occurrence of the CTA comment, adding
        // the closing div (with matching indentation) right before it.
        $cta_pos = strpos($src, '<!-- CTA bar -->');
        if ($cta_pos === false) return 'CTA bar marker not found';

        // Detect indentation used on the CTA comment line
        $line_start = strrpos(substr($src, 0, $cta_pos), "\n");
        $line_start = ($line_start === false) ? 0 : $line_start + 1;
        $indent     = '';
        for ($i = $line_start; $i < $cta_pos; $i++) {
            $ch = $src[$i];
            if ($ch === ' ' || $ch === "\t") { $indent .= $ch; } else { break; }
        }

        $insert = $indent . "</div>\n" . $indent;
        $new    = substr($src, 0, $cta_pos) . $insert . substr($src, $cta_pos);
    } else {
        // ── Remove excess </div> tags between gcm-grid and gcm-sidebar
        $grid_pos    = strpos($src, '<div class="gcm-grid">');
        $sidebar_pos = strpos($src, '<div class="gcm-sidebar">');

        $before  = substr($src, 0, $grid_pos);
        $between = substr($src, $grid_pos, $sidebar_pos - $grid_pos);
        $after   = substr($src, $sidebar_pos);

        $opens  = preg_match_all('/<div\b/', $between);
        $closes = substr_count($between, '</div>');
        $excess = max(0, ($closes - $opens) + 1); // excess = how many to remove

        $fixed_between = $between;
        for ($i = 0; $i < $excess; $i++) {
            $last_pos = strrpos($fixed_between, '</div>');
            if ($last_pos === false) break;
            $fixed_between = substr($fixed_between, 0, $last_pos)
                           . substr($fixed_between, $last_pos + 6);
        }
        $fixed_between = preg_replace('/\n[ \t]*\n[ \t]*\n/', "\n\n", $fixed_between);
        $new = $before . $fixed_between . $after;
    }

    if ($new === $src) return null;

    // Backup before writing
    @copy($file, $file . '.layout.bak');

    if (file_put_contents($file, $new) === false) return 'Write failed';

    return true;
}
