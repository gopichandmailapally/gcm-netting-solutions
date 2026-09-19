<?php
/**
 * fix-faq-format.php
 * Scans generated pages for plain-text paragraph FAQ sections (Q:/A: in <p> tags)
 * and converts them to <ol><li><strong>Q</strong> A</li></ol> structure so the
 * existing accordion JS in modern-footer.php picks them up correctly.
 */
define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

// Read-only auth check (same session as admin)
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

$action = trim($_POST['action'] ?? $_GET['action'] ?? 'scan');
$offset = (int)($_POST['offset'] ?? $_GET['offset'] ?? 0);
$limit  = (int)($_POST['limit']  ?? $_GET['limit']  ?? 50);
$target_file = trim($_POST['file'] ?? $_GET['file'] ?? '');

// ── Single file fix ────────────────────────────────────────────────────
if ($target_file !== '' && $action === 'fix') {
    $path = $gen_dir . basename($target_file);
    if (!file_exists($path)) {
        echo json_encode(['success' => false, 'message' => 'File not found']);
        exit;
    }
    $result = fix_faq_format($path);
    echo json_encode([
        'success' => $result === true,
        'file'    => $target_file,
        'result'  => is_string($result) ? $result : ($result === null ? 'already_ok' : 'fixed'),
    ]);
    exit;
}

// ── Batch scan / fix ───────────────────────────────────────────────────
$all   = glob($gen_dir . '*.php') ?: [];
$all   = array_values(array_filter($all, fn($f) => basename($f) !== 'index.php'));
$total = count($all);

if ($action === 'scan') {
    $broken = [];
    foreach ($all as $f) {
        if (check_faq_format($f)) {
            $broken[] = basename($f);
        }
    }
    echo json_encode([
        'success'      => true,
        'total'        => $total,
        'broken_count' => count($broken),
        'broken'       => $broken,
    ]);
    exit;
}

if ($action === 'fix') {
    $batch   = array_slice($all, $offset, $limit);
    $fixed   = 0;
    $skipped = 0;
    $errors  = [];

    foreach ($batch as $f) {
        $r = fix_faq_format($f);
        if ($r === true)      $fixed++;
        elseif ($r === null)  $skipped++;
        else                  $errors[] = basename($f) . ': ' . $r;
    }

    $processed = $offset + count($batch);
    echo json_encode([
        'success'   => true,
        'fixed'     => $fixed,
        'skipped'   => $skipped,
        'errors'    => $errors,
        'processed' => $processed,
        'total'     => $total,
        'completed' => ($processed >= $total),
    ]);
    exit;
}

if ($action === 'debug') {
    $results = [];
    $count = 0;
    foreach ($all as $f) {
        if (!check_faq_format($f)) continue;
        $src = @file_get_contents($f);
        if (!$src) continue;

        $outside_li = preg_replace('/<li[^>]*>[\s\S]*?<\/li>/i', '', $src);

        $snippet = '';
        $matched_pattern = '';
        // Find the position of Q: in the stripped content
        if (preg_match('/<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:/i', $outside_li, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1]; $matched_pattern = 'p-tag';
            $snippet = substr($outside_li, max(0, $pos - 50), 1000);
        } elseif (preg_match('/<h[2-6][^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:/i', $outside_li, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1]; $matched_pattern = 'h-tag';
            $snippet = substr($outside_li, max(0, $pos - 50), 1000);
        } elseif (preg_match('/<(?:strong|b)[^>]*>\s*Q\s*:/i', $outside_li, $m, PREG_OFFSET_CAPTURE)) {
            $pos = $m[0][1]; $matched_pattern = 'strong-tag';
            $snippet = substr($outside_li, max(0, $pos - 50), 1000);
        }

        // Also try fix and report result
        $fix_result = fix_faq_format($f);
        $results[] = [
            'file'           => basename($f),
            'check_pattern'  => $matched_pattern,
            'fix_result'     => is_bool($fix_result) ? ($fix_result ? 'fixed' : 'not-fixed') : (string)$fix_result,
            'snippet'        => htmlspecialchars($snippet),
        ];
        if (++$count >= 5) break;
    }
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);

// ══════════════════════════════════════════════════════════════════════
// check_faq_format($file)
// Returns true if the page has FAQ content in broken paragraph format
// ══════════════════════════════════════════════════════════════════════
function check_faq_format(string $file): bool
{
    $src = @file_get_contents($file);
    if (!$src) return false;

    // Strip all <li>...</li> content first — Q: inside <li> is already in the
    // correct accordion-ready format and must NOT be flagged as broken.
    $outside_li = preg_replace('/<li[^>]*>[\s\S]*?<\/li>/i', '', $src);
    if ($outside_li === null) $outside_li = $src; // fallback if regex fails

    // Pattern 1: <p> tags with Q: OUTSIDE any <li> element
    if (preg_match('/<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:/i', $outside_li)) return true;

    // Pattern 2: bare <strong>Q: or <b>Q: OUTSIDE any <li> element
    // (AI sometimes generates FAQ without <p> wrappers)
    if (preg_match('/<(?:strong|b)[^>]*>\s*Q\s*:/i', $outside_li)) return true;

    // Pattern 3: <h2>/<h3>/<h4>/<h5>/<h6> tags with Q: OUTSIDE any <li> element
    // (AIPromptGenerator bulk pages use <h3>Q:...</h3><p>A:</p> format)
    if (preg_match('/<h[2-6][^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:/i', $outside_li)) return true;

    return false;
}

// ══════════════════════════════════════════════════════════════════════
// fix_faq_format($file)
// Converts paragraph-style Q/A blocks to <ol><li> accordion-ready format.
// Handles the three most common AI output patterns.
// Returns true (fixed) | null (nothing to fix) | string (error)
// ══════════════════════════════════════════════════════════════════════
function fix_faq_format(string $file)
{
    $src = @file_get_contents($file);
    if ($src === false) return 'Cannot read file';

    if (!check_faq_format($file)) return null;

    $new = $src;

    // ─────────────────────────────────────────────────────────────────
    // PATTERN A  (most common from auto-seo-optimizer)
    // <p><strong>Q: question</strong></p>
    // <p>    A: answer</p>  OR  <p><strong>A: answer</strong></p>
    // ─────────────────────────────────────────────────────────────────
    $new = preg_replace_callback(
        '/((?:<p[^>]*>\s*<(?:strong|b)[^>]*>\s*Q\s*:\s*.+?<\/(?:strong|b)>\s*<\/p>[\s\S]{0,400}?<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*(?:&nbsp;|\s)*A\s*:\s*.+?(?:<\/(?:strong|b)>)?\s*<\/p>\s*)+)/si',
        function ($m) {
            $block = $m[0];
            preg_match_all(
                '/<p[^>]*>\s*<(?:strong|b)[^>]*>\s*Q\s*:\s*(.+?)<\/(?:strong|b)>\s*<\/p>[\s\S]{0,400}?<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*(?:&nbsp;|\s)*A\s*:\s*(.+?)(?:<\/(?:strong|b)>)?\s*<\/p>/si',
                $block, $pairs, PREG_SET_ORDER
            );
            if (empty($pairs)) return $block;
            $items = array_map(fn($p) =>
                '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                $pairs
            );
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        },
        $new
    );

    // ─────────────────────────────────────────────────────────────────
    // PATTERN B  (Q and A in same paragraph with <br> separator)
    // <p><strong>Q: question</strong><br>A: answer</p>
    // ─────────────────────────────────────────────────────────────────
    $new = preg_replace_callback(
        '/((?:<p[^>]*>\s*<(?:strong|b)[^>]*>\s*Q\s*:\s*.+?<\/(?:strong|b)>\s*(?:<br\s*\/?>\s*)A\s*:\s*.+?<\/p>\s*)+)/si',
        function ($m) {
            $block = $m[0];
            preg_match_all(
                '/<p[^>]*>\s*<(?:strong|b)[^>]*>\s*Q\s*:\s*(.+?)<\/(?:strong|b)>\s*(?:<br\s*\/?>\s*)A\s*:\s*(.+?)<\/p>/si',
                $block, $pairs, PREG_SET_ORDER
            );
            if (empty($pairs)) return $block;
            $items = array_map(fn($p) =>
                '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                $pairs
            );
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        },
        $new
    );

    // ─────────────────────────────────────────────────────────────────
    // PATTERN C  (plain-text Q and A as consecutive <p> tags)
    // <p>Q: question text</p>
    // <p>A: answer text</p>  OR  <p><strong>A: answer</strong></p>
    // ─────────────────────────────────────────────────────────────────
    $new = preg_replace_callback(
        '/((?:<p[^>]*>\s*Q\s*:\s*.+?<\/p>[\s\S]{0,400}?<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*(?:&nbsp;|\s)*A\s*:\s*.+?(?:<\/(?:strong|b)>)?\s*<\/p>\s*)+)/si',
        function ($m) {
            $block = $m[0];
            preg_match_all(
                '/<p[^>]*>\s*Q\s*:\s*(.+?)<\/p>[\s\S]{0,400}?<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*(?:&nbsp;|\s)*A\s*:\s*(.+?)(?:<\/(?:strong|b)>)?\s*<\/p>/si',
                $block, $pairs, PREG_SET_ORDER
            );
            if (empty($pairs)) return $block;
            $items = array_map(fn($p) =>
                '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                $pairs
            );
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        },
        $new
    );

    // ─────────────────────────────────────────────────────────────────
    // PATTERN E  (Q and A in same <p> separated by <br>, no <strong>)
    // <p>Q: question<br>A: answer</p>  OR  <p>Q: question<br/>A: answer</p>
    // ─────────────────────────────────────────────────────────────────
    $new = preg_replace_callback(
        '/((?:<p[^>]*>\s*Q\s*:\s*.+?(?:<br\s*\/?>\s*)(?:&nbsp;|\s)*A\s*:\s*.+?<\/p>\s*)+)/si',
        function ($m) {
            $block = $m[0];
            preg_match_all(
                '/<p[^>]*>\s*Q\s*:\s*(.+?)(?:<br\s*\/?>\s*)(?:&nbsp;|\s)*A\s*:\s*(.+?)<\/p>/si',
                $block, $pairs, PREG_SET_ORDER
            );
            if (empty($pairs)) return $block;
            $items = array_map(fn($p) =>
                '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                $pairs
            );
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        },
        $new
    );

    // ─────────────────────────────────────────────────────────────────
    // PATTERN F  (<h3>/<h2>/<h4> Q header + <p> A paragraph)
    // <h3>Q: question text</h3>  [or h2/h4/h5/h6]
    // <p>A: answer text</p>  OR  <p><strong>A: answer</strong></p>
    // Generated by AIPromptGenerator bulk page generator
    // ─────────────────────────────────────────────────────────────────
    $new = preg_replace_callback(
        '/((?:<h[2-6][^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:\s*.+?(?:<\/(?:strong|b)>)?\s*<\/h[2-6]>[\s\S]{0,400}?<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*(?:&nbsp;|\s)*A\s*:\s*.+?(?:<\/(?:strong|b)>)?\s*<\/p>\s*)+)/si',
        function ($m) {
            $block = $m[0];
            preg_match_all(
                '/<h[2-6][^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:\s*(.+?)(?:<\/(?:strong|b)>)?\s*<\/h[2-6]>[\s\S]{0,400}?<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*(?:&nbsp;|\s)*A\s*:\s*(.+?)(?:<\/(?:strong|b)>)?\s*<\/p>/si',
                $block, $pairs, PREG_SET_ORDER
            );
            if (empty($pairs)) return $block;
            $items = array_map(fn($p) =>
                '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                $pairs
            );
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        },
        $new
    );

    // ─────────────────────────────────────────────────────────────────
    // PATTERN H  (<dt><strong>Q:</strong></dt><dd>A:</dd> definition list)
    // AI sometimes generates FAQ as a definition list (<dl><dt><dd>).
    // Handles optional <dl> wrapper and missing </dt> closing tag.
    // ─────────────────────────────────────────────────────────────────
    $new = preg_replace_callback(
        '/(?:<dl[^>]*>\s*)?((?:<dt[^>]*>\s*<(?:strong|b)[^>]*>\s*Q\s*:\s*.+?<\/(?:strong|b)>\s*(?:<\/dt>|<dt[^>]*>)?\s*<dd[^>]*>[\s\S]{0,800}?<\/dd>\s*)+)(?:<\/dl>)?/si',
        function ($m) {
            $block = $m[1];
            preg_match_all(
                '/<dt[^>]*>\s*<(?:strong|b)[^>]*>\s*Q\s*:\s*(.+?)<\/(?:strong|b)>\s*(?:<\/dt>|<dt[^>]*>)?\s*<dd[^>]*>\s*(?:&nbsp;|\s)*A\s*:\s*([\s\S]+?)<\/dd>/si',
                $block, $pairs, PREG_SET_ORDER
            );
            if (empty($pairs)) return $m[0];
            $items = array_map(fn($p) =>
                '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                $pairs
            );
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        },
        $new
    );

    // ─────────────────────────────────────────────────────────────────
    // PATTERN D  (bare <strong> Q without <p> wrapper)
    // <strong>Q: question text</strong><br>A: answer text
    // OR <strong>Q: question text</strong>  followed by <p>A: answer</p>
    // ─────────────────────────────────────────────────────────────────
    $new = preg_replace_callback(
        '/((?:<(?:strong|b)[^>]*>\s*Q\s*:\s*.+?<\/(?:strong|b)>\s*(?:<br\s*\/?>\s*)?(?:&nbsp;|\s)*A\s*:\s*.+?(?:<br\s*\/?>|<\/p>|(?=\s*<(?:strong|b)[^>]*>\s*Q\s*:)))\s*)+/si',
        function ($m) {
            $block = $m[0];
            preg_match_all(
                '/<(?:strong|b)[^>]*>\s*Q\s*:\s*(.+?)<\/(?:strong|b)>\s*(?:<br\s*\/?>\s*)?(?:&nbsp;|\s)*A\s*:\s*(.+?)(?:<br\s*\/?>|(?=\s*<(?:strong|b)[^>]*>\s*Q\s*:)|$)/si',
                $block, $pairs, PREG_SET_ORDER
            );
            if (empty($pairs)) return $block;
            $items = array_map(fn($p) =>
                '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                $pairs
            );
            return "<ol>\n" . implode("\n", $items) . "\n</ol>\n";
        },
        $new
    );

    // ─────────────────────────────────────────────────────────────────
    // PATTERN G  (Universal FAQ section catch-all)
    // Finds the FAQ heading then replaces ALL Q/A pairs within that section.
    // Handles any combination of <p>, <h2>-<h6> for Q and A lines.
    // ─────────────────────────────────────────────────────────────────
    if ($new === $src) {
        $new = preg_replace_callback(
            '/(<h[2-6][^>]*>(?:[^<]|<(?!\/h[2-6]))*?(?:frequently\s+asked|FAQ|common\s+question)[^<]*<\/h[2-6]>)([\s\S]*?)(?=<h[12][^>]*>|\z)/si',
            function ($m) {
                $heading = $m[1];
                $section = $m[2];
                preg_match_all(
                    '/<(?:p|h[2-6]|dt)[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:\s*([\s\S]+?)(?:<\/(?:strong|b)>)?\s*(?:<\/(?:p|h[2-6]|dt)>|<(?:p|h[2-6]|dt)[^>]*>)[\s\S]{0,600}?<(?:p|h[2-6]|dd)[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*(?:&nbsp;|\s)*A\s*:\s*([\s\S]+?)(?:<\/(?:strong|b)>)?\s*<\/(?:p|h[2-6]|dd)>/si',
                    $section, $pairs, PREG_SET_ORDER
                );
                if (empty($pairs)) return $m[0];
                $items = array_map(fn($p) =>
                    '<li><strong>' . trim(strip_tags($p[1])) . '</strong> ' . trim(strip_tags($p[2])) . '</li>',
                    $pairs
                );
                return $heading . "\n<ol>\n" . implode("\n", $items) . "\n</ol>\n";
            },
            $new
        );
    }

    // Broken page detected but no pattern matched — report with diagnostic context
    if ($new === $src) {
        // Identify which format was detected to help diagnose
        $hints = [];
        if (preg_match('/<h[2-6][^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:/i', $src, $hm)) {
            $hints[] = 'h' . (preg_match('/<h([2-6])/i', $hm[0], $hn) ? $hn[1] : '?') . '-q-format';
        }
        if (preg_match('/<p[^>]*>\s*(?:<(?:strong|b)[^>]*>)?\s*Q\s*:/i', $src)) $hints[] = 'p-q-format';
        if (preg_match('/<(?:strong|b)[^>]*>\s*Q\s*:/i', $src)) $hints[] = 'bare-strong-q';
        return 'unmatched-format' . (count($hints) ? ':' . implode(',', $hints) : '');
    }

    // Backup before writing
    @copy($file, $file . '.faq.bak');

    if (file_put_contents($file, $new) === false) return 'Write failed';

    return true;
}
