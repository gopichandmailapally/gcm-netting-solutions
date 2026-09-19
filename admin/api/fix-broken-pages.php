<?php
/**
 * Fix Broken Pages API
 * Scans generated_pages for issues and fixes them WITHOUT re-calling AI.
 * For empty-content pages only, flags them for re-generation.
 */
define('GCM_INIT', true);
ob_start();
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json');
set_time_limit(300);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name('GCM_ADMIN_SESSION');
    session_start();
}
if (empty($_SESSION['admin_logged_in'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}
session_write_close();

$db      = Database::getInstance();
$gen_dir = __DIR__ . '/../../generated-pages/';
$input   = json_decode(file_get_contents('php://input'), true) ?? [];
$action  = $input['action'] ?? $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'scan':            doScan($db, $gen_dir);                  break;
    case 'get_list':        getList($db, $gen_dir);                 break;
    case 'fix_batch':       doFixBatch($db, $gen_dir, $input);      break;
    case 'scan_files':      doScanFiles($gen_dir, $input);          break;
    case 'fix_files_batch': doFixFilesBatch($db, $gen_dir, $input); break;
    case 'fix_all_sync':    doFixAllSync($db, $gen_dir);            break;  // ONE request, all files
    default:
        ob_clean();
        echo json_encode(['error' => 'Invalid action']);
}

/* ═══════════════════════════════════════════════════════
   SCAN  – counts broken pages by issue type
═══════════════════════════════════════════════════════ */
function doScan($db, $gen_dir) {
    $stats = [];

    $r = $db->fetchOne("SELECT COUNT(*) as c FROM generated_pages WHERE content IS NULL OR TRIM(content)=''");
    $stats['empty'] = (int)($r['c'] ?? 0);

    $r = $db->fetchOne("SELECT COUNT(*) as c FROM generated_pages WHERE word_count < 80 AND content IS NOT NULL AND TRIM(content)!=''");
    $stats['thin'] = (int)($r['c'] ?? 0);

    $r = $db->fetchOne("SELECT COUNT(*) as c FROM generated_pages WHERE content LIKE '%Key improvements and explanations%' OR content LIKE '%In this improved version%' OR content LIKE '%Changes made:%'");
    $stats['ai_notes'] = (int)($r['c'] ?? 0);

    $r = $db->fetchOne("SELECT COUNT(*) as c FROM generated_pages WHERE content LIKE '%[Insert Phone%' OR content LIKE '%[Insert Website%' OR content LIKE '%[Phone Number%' OR content LIKE '%[Company Name%' OR content LIKE '%[Your Website%'");
    $stats['placeholders'] = (int)($r['c'] ?? 0);

    $r = $db->fetchOne("SELECT COUNT(*) as c FROM generated_pages WHERE content LIKE '%**HTML5%' OR content LIKE '%**Targeted Keywords%' OR content LIKE '%**Location-Specific%' OR content LIKE '%**GCM Netting Solutions Integration%' OR content LIKE '%**SEO Optimization%'");
    $stats['markdown'] = (int)($r['c'] ?? 0);

    // Files missing on disk (DB record exists but PHP file is gone)
    $missing_files = 0;
    $rows = $db->fetchAll("SELECT page_slug, keyword_slug, area_slug FROM generated_pages WHERE content IS NOT NULL AND TRIM(content)!='' LIMIT 2000");
    foreach ($rows as $row) {
        $slug = $row['page_slug'] ?? ($row['keyword_slug'] . '-in-' . $row['area_slug']);
        if ($slug && !file_exists($gen_dir . $slug . '.php')) {
            $missing_files++;
        }
    }
    $stats['missing_file'] = $missing_files;

    $stats['total_cleanable'] = $stats['ai_notes'] + $stats['placeholders'] + $stats['markdown'] + $stats['missing_file'];
    $stats['needs_regen']     = $stats['empty'] + $stats['thin'];

    ob_clean();
    echo json_encode(['success' => true, 'stats' => $stats]);
}

/* ═══════════════════════════════════════════════════════
   GET_LIST  – returns first 200 broken pages with issue type
═══════════════════════════════════════════════════════ */
function getList($db, $gen_dir) {
    $rows = $db->fetchAll("
        SELECT p.id, p.page_slug, p.page_title, p.word_count,
               k.keyword_name, a.area_name
        FROM generated_pages p
        LEFT JOIN seo_service_keywords k ON k.id = p.keyword_id
        LEFT JOIN service_areas a ON a.id = p.area_id
        WHERE
            p.content IS NULL OR TRIM(p.content) = ''
            OR p.word_count < 80
            OR p.content LIKE '%Key improvements and explanations%'
            OR p.content LIKE '%In this improved version%'
            OR p.content LIKE '%[Insert Phone%'
            OR p.content LIKE '%[Insert Website%'
            OR p.content LIKE '%[Phone Number%'
            OR p.content LIKE '%**HTML5%'
        ORDER BY p.id DESC
        LIMIT 200
    ");

    $result = [];
    foreach ($rows as $row) {
        $issue = 'unknown';
        if (empty($row['content']) || trim($row['content']) === '') {
            $issue = 'empty';
        } elseif ((int)$row['word_count'] < 80) {
            $issue = 'thin';
        } elseif (stripos($row['content'], 'Key improvements and explanations') !== false
               || stripos($row['content'], 'In this improved version') !== false) {
            $issue = 'ai_notes';
        } elseif (stripos($row['content'], '[Insert Phone') !== false
               || stripos($row['content'], '[Phone Number') !== false
               || stripos($row['content'], '[Insert Website') !== false) {
            $issue = 'placeholder';
        } elseif (stripos($row['content'], '**HTML5') !== false) {
            $issue = 'markdown';
        }
        $slug = $row['page_slug'] ?? '';
        $result[] = [
            'id'       => $row['id'],
            'slug'     => $slug,
            'title'    => $row['page_title'],
            'keyword'  => $row['keyword_name'],
            'area'     => $row['area_name'],
            'words'    => $row['word_count'],
            'issue'    => $issue,
        ];
    }
    ob_clean();
    echo json_encode(['success' => true, 'pages' => $result]);
}

/* ═══════════════════════════════════════════════════════
   FIX_BATCH  – cleans a batch of fixable pages (no AI)
═══════════════════════════════════════════════════════ */
function doFixBatch($db, $gen_dir, $input) {
    $offset    = (int)($input['offset'] ?? 0);
    $batchSize = (int)($input['batch_size'] ?? 30);
    $mode      = $input['mode'] ?? 'clean'; // 'clean' | 'rebuild_files'

    if ($mode === 'rebuild_files') {
        // Rebuild PHP files for pages that have DB content but no file on disk
        $rows = $db->fetchAll("
            SELECT p.*, k.keyword_name, k.keyword_slug, k.category, a.area_name, a.area_slug
            FROM generated_pages p
            JOIN seo_service_keywords k ON k.id = p.keyword_id
            JOIN service_areas a ON a.id = p.area_id
            WHERE p.content IS NOT NULL AND TRIM(p.content) != ''
            LIMIT ?, ?
        ", [$offset, $batchSize]);
    } else {
        // Clean AI notes / placeholders / markdown from DB content
        $rows = $db->fetchAll("
            SELECT p.*, k.keyword_name, k.keyword_slug, k.category, a.area_name, a.area_slug
            FROM generated_pages p
            JOIN seo_service_keywords k ON k.id = p.keyword_id
            JOIN service_areas a ON a.id = p.area_id
            WHERE p.content IS NOT NULL AND TRIM(p.content) != ''
              AND (
                p.content LIKE '%Key improvements and explanations%'
                OR p.content LIKE '%In this improved version%'
                OR p.content LIKE '%Changes made:%'
                OR p.content LIKE '%[Insert Phone%'
                OR p.content LIKE '%[Insert Website%'
                OR p.content LIKE '%[Phone Number%'
                OR p.content LIKE '%[Company Name%'
                OR p.content LIKE '%[Your Website%'
                OR p.content LIKE '%**HTML5%'
                OR p.content LIKE '%**Targeted Keywords%'
                OR p.content LIKE '%**Location-Specific%'
                OR p.content LIKE '%**SEO Optimization%'
              )
            LIMIT ?, ?
        ", [$offset, $batchSize]);
    }

    $fixed = 0; $errors = 0; $skipped = 0;

    foreach ($rows as $page) {
        try {
            $slug = $page['page_slug'] ?? ($page['keyword_slug'] . '-in-' . $page['area_slug']);
            if (!$slug) { $skipped++; continue; }

            // Clean content
            $cleanContent = cleanContent($page['content']);
            $newWordCount = str_word_count(strip_tags($cleanContent));

            // Build the PHP file
            $h1 = $page['h1_heading'] ?? ('Professional ' . $page['keyword_name'] . ' in ' . $page['area_name'] . ', Chennai');
            $fileContent = buildPage(
                $page['page_title'] ?? '',
                $h1,
                $page['meta_description'] ?? '',
                $page['meta_keywords'] ?? '',
                $cleanContent,
                $page['keyword_name'],
                $page['keyword_slug'],
                $page['area_name'],
                $page['area_slug'],
                $page['category'] ?? ''
            );

            // Write file
            if (!is_dir($gen_dir)) @mkdir($gen_dir, 0755, true);
            $written = file_put_contents($gen_dir . $slug . '.php', $fileContent);
            if ($written === false) { $errors++; continue; }

            // Update DB only if content was cleaned (mode=clean)
            if ($mode !== 'rebuild_files') {
                $db->execute(
                    "UPDATE generated_pages SET content=?, word_count=?, updated_at=NOW() WHERE id=?",
                    [$cleanContent, $newWordCount, $page['id']]
                );
            }

            $fixed++;
        } catch (Exception $e) {
            $errors++;
        }
    }

    ob_clean();
    echo json_encode([
        'success'   => true,
        'fixed'     => $fixed,
        'errors'    => $errors,
        'skipped'   => $skipped,
        'processed' => count($rows),
        'has_more'  => count($rows) === $batchSize,
    ]);
}

/* ═══════════════════════════════════════════════════════
   FIX_ALL_SYNC  – process ALL files in ONE request
   Avoids Hostinger WAF blocking from repeated batch calls.
   Typical runtime: 20-50 seconds for 12,032 files.
═══════════════════════════════════════════════════════ */
function doFixAllSync($db, $gen_dir) {
    set_time_limit(600);
    ignore_user_abort(true);

    $allFiles = glob($gen_dir . '*.php');
    if (!$allFiles) {
        ob_clean();
        echo json_encode(['success' => true, 'fixed' => 0, 'errors' => 0, 'skipped' => 0, 'total' => 0]);
        return;
    }
    $allFiles = array_values(array_filter($allFiles, fn($f) => basename($f) !== 'index.php'));
    $total   = count($allFiles);
    $fixed   = 0;
    $errors  = 0;
    $skipped = 0;

    // Pre-build list of placeholder needle strings for fast strpos scanning
    $phNeedles = [
        '[Insert Phone','[Phone Number]','[Phone]','[Mobile','[Contact Number]',
        '[Your Phone','[Call Us]','[Insert Website','[Website URL]','[Website]',
        '[Your Website','[URL]','[Company Name]','[Your Company','[Business Name]',
        '[Email','[City]','[Location]','[Your City]','[Area]',
    ];

    foreach ($allFiles as $file) {
        try {
            $raw = file_get_contents($file);
            if ($raw === false) { $errors++; continue; }

            // ── Detect issue types ──
            $hasAiNotes = stripos($raw, 'Key improvements and explanations') !== false
                       || stripos($raw, 'In this improved version')          !== false
                       || stripos($raw, 'Changes made:')                     !== false
                       || stripos($raw, "Here's a breakdown")                !== false
                       || stripos($raw, 'Improvements made:')                !== false
                       || stripos($raw, "I've made the following")           !== false
                       || stripos($raw, 'Key changes made:')                 !== false;

            $hasPlaceholder = false;
            foreach ($phNeedles as $needle) {
                if (stripos($raw, $needle) !== false) { $hasPlaceholder = true; break; }
            }

            $isOldTemplate = strpos($raw, 'gcm-wrap') === false
                          && strpos($raw, 'gcm-grid') === false;

            if (!$hasAiNotes && !$hasPlaceholder && !$isOldTemplate) {
                $skipped++;
                continue;
            }

            $newRaw  = $raw;
            $changed = false;

            // ── 1. Old template → rebuild entire file from DB ──
            if ($isOldTemplate) {
                $slug = basename($file, '.php');
                $page = $db->fetchOne("
                    SELECT p.*, k.keyword_name, k.keyword_slug, k.category, a.area_name, a.area_slug
                    FROM generated_pages p
                    JOIN seo_service_keywords k ON k.id = p.keyword_id
                    JOIN service_areas a ON a.id = p.area_id
                    WHERE p.page_slug = ?
                ", [$slug]);
                if ($page && !empty($page['content'])) {
                    $cleanC = cleanContent($page['content']);
                    $h1 = $page['h1_heading'] ?? ('Professional ' . $page['keyword_name'] . ' in ' . $page['area_name'] . ', Chennai');
                    $newRaw = buildPage(
                        $page['page_title'] ?? '', $h1,
                        $page['meta_description'] ?? '', $page['meta_keywords'] ?? '',
                        $cleanC, $page['keyword_name'], $page['keyword_slug'],
                        $page['area_name'], $page['area_slug'], $page['category'] ?? ''
                    );
                    $changed = true;
                    // After rebuild, check if remaining issues need treatment
                    $hasPlaceholder = false;
                    $hasAiNotes     = false;
                }
                // If no DB record fall through to in-place fixes below
            }

            // ── 2. Placeholder replacement — safe on full file ──
            //    Placeholders like [Insert Phone Number] only appear inside
            //    AI-generated content, never in PHP template boilerplate.
            if ($hasPlaceholder) {
                $cleaned = cleanPlaceholders($newRaw);
                if ($cleaned !== $newRaw) {
                    $newRaw  = $cleaned;
                    $changed = true;
                }
            }

            // ── 3. AI notes — ONLY strip from inside gcm-content div ──
            //    Never apply to full file (would corrupt PHP template code).
            //    Use '<!-- CTA bar -->' as end marker (reliable, whitespace-agnostic).
            if ($hasAiNotes) {
                $cOpen = '<div class="gcm-content">';
                $cEnd  = '<!-- CTA bar -->';
                $oPos  = strpos($newRaw, $cOpen);
                $ePos  = $oPos !== false ? strpos($newRaw, $cEnd, $oPos) : false;

                if ($oPos !== false && $ePos !== false) {
                    $head  = substr($newRaw, 0, $oPos + strlen($cOpen));
                    $inner = substr($newRaw, $oPos + strlen($cOpen), $ePos - $oPos - strlen($cOpen));
                    $tail  = substr($newRaw, $ePos);

                    $cleanedInner = cleanAiNotes($inner);
                    if ($cleanedInner !== $inner) {
                        $newRaw  = $head . $cleanedInner . $tail;
                        $changed = true;
                    }
                }
                // If markers not found: skip — never risk corrupting template code
            }

            if ($changed) {
                if (file_put_contents($file, $newRaw) !== false) {
                    $fixed++;
                } else {
                    $errors++;
                }
            } else {
                $skipped++;
            }

        } catch (Exception $e) {
            $errors++;
        }
    }

    ob_clean();
    echo json_encode([
        'success' => true,
        'fixed'   => $fixed,
        'errors'  => $errors,
        'skipped' => $skipped,
        'total'   => $total,
    ]);
}

/* ═══════════════════════════════════════════════════════
   CLEAN PLACEHOLDERS — broad patterns, safe on full file
═══════════════════════════════════════════════════════ */
function cleanPlaceholders($content) {
    // Phone / mobile / contact — match ANY variant like [Insert Phone], [Your Phone Number], etc.
    $content = preg_replace('/\[Insert\s+Phone[^\]]*\]/i',  '9912399224',              $content);
    $content = preg_replace('/\[Your\s+Phone[^\]]*\]/i',    '9912399224',              $content);
    $content = preg_replace('/\[Phone\s*(?:Number|No\.?)?\]/i', '9912399224',          $content);
    $content = preg_replace('/\[Mobile\s*(?:Number|No\.?)?\]/i','9912399224',          $content);
    $content = preg_replace('/\[Contact\s*(?:Number|No\.?|Us)?\]/i','9912399224',      $content);
    $content = preg_replace('/\[Call\s*(?:Us|Now)?\]/i',    '9912399224',              $content);

    // Website — match ANY variant
    $content = preg_replace('/\[Insert\s+Website[^\]]*\]/i','www.gcmsafetynets.in',    $content);
    $content = preg_replace('/\[Your\s+Website[^\]]*\]/i',  'www.gcmsafetynets.in',    $content);
    $content = preg_replace('/\[Website\s*(?:URL|Address|Link)?\]/i','gcmsafetynets.in',$content);
    $content = preg_replace('/\[URL\]/i',                   'https://gcmsafetynets.in',$content);
    $content = preg_replace('/\[Site\s*(?:URL|Link)?\]/i',  'gcmsafetynets.in',        $content);

    // Company / business name
    $content = preg_replace('/\[Company\s*Name\]/i',        'GCM Netting Solutions',         $content);
    $content = preg_replace('/\[Your\s+Company[^\]]*\]/i',  'GCM Netting Solutions',         $content);
    $content = preg_replace('/\[Business\s*Name\]/i',       'GCM Netting Solutions',         $content);
    $content = preg_replace('/\[Brand\s*Name\]/i',          'GCM Netting Solutions',         $content);

    // Email
    $content = preg_replace('/\[E-?mail[^\]]*\]/i',         'gcmsafetynets@gmail.com', $content);

    // City / location / area
    $content = preg_replace('/\[(?:Your\s+)?City\]/i',      'Chennai',               $content);
    $content = preg_replace('/\[Location\]/i',              'Chennai',               $content);
    $content = preg_replace('/\[Area\]/i',                  'Chennai',               $content);
    $content = preg_replace('/\[(?:Your\s+)?Location\]/i',  'Chennai',               $content);

    // Convert raw markdown (common in placeholder-heavy files)
    $content = preg_replace('/\*\*([^*\n]{1,120})\*\*/s', '<strong>$1</strong>', $content);
    $content = preg_replace('/(?<!\*)\*([^*\n]{1,120})\*(?!\*)/s', '<em>$1</em>', $content);
    $content = str_replace(['```html', '```', '`'], '', $content);

    return $content;
}

/* ═══════════════════════════════════════════════════════
   CLEAN AI NOTES — strip commentary from content section
   Applied ONLY to inner content, not full PHP file.
═══════════════════════════════════════════════════════ */
function cleanAiNotes($inner) {
    $markers = [
        'Key improvements and explanations',
        'In this improved version',
        'In this revised version',
        'Changes made:',
        'Improvements made:',
        'Summary of changes:',
        "Here's a breakdown of",
        "Here are the key improvements",
        "Key changes made:",
        "Note: I've",
        "I've made the following",
        "I have made the following",
        "The following improvements",
    ];
    foreach ($markers as $m) {
        $pos = stripos($inner, $m);
        // Only cut if marker appears in the last 60% of the section
        if ($pos !== false && $pos > strlen($inner) * 0.35) {
            $inner = rtrim(substr($inner, 0, $pos));
            break; // stop after first match
        }
    }
    return $inner;
}

/* ═══════════════════════════════════════════════════════
   SCAN_FILES  – scan actual PHP files on disk (not DB)
   More reliable: DB content may differ from file content
═══════════════════════════════════════════════════════ */
function doScanFiles($gen_dir, $input) {
    $offset = (int)($input['offset'] ?? 0);
    $limit  = (int)($input['limit']  ?? 500);

    $allFiles = glob($gen_dir . '*.php');
    if (!$allFiles) { ob_clean(); echo json_encode(['success' => true, 'total' => 0, 'ai_notes' => 0, 'placeholder' => 0, 'empty_content' => 0, 'old_template' => 0, 'done' => true]); return; }

    // Remove index.php
    $allFiles = array_filter($allFiles, fn($f) => basename($f) !== 'index.php');
    $allFiles = array_values($allFiles);
    $total    = count($allFiles);
    $batch    = array_slice($allFiles, $offset, $limit);

    $ai_notes = $placeholder = $empty_content = $old_template = 0;
    foreach ($batch as $file) {
        $raw = file_get_contents($file);
        if ($raw === false) continue;

        if (stripos($raw, 'Key improvements and explanations') !== false ||
            stripos($raw, 'In this improved version') !== false ||
            stripos($raw, 'Changes made:') !== false) {
            $ai_notes++;
        }
        if (strpos($raw, '[Insert Phone') !== false ||
            strpos($raw, '[Phone Number]') !== false ||
            strpos($raw, '[Insert Website') !== false) {
            $placeholder++;
        }
        // Old template: lacks gcm-wrap/gcm-grid classes
        if (strpos($raw, 'gcm-wrap') === false && strpos($raw, 'gcm-grid') === false) {
            $old_template++;
        } elseif (preg_match('/<div class="gcm-content">\s*<\/div>/s', $raw) ||
                  !preg_match('/<div class="gcm-content">[\s\S]{50,}/m', $raw)) {
            $empty_content++;
        }
    }

    $scanned = $offset + count($batch);
    ob_clean();
    echo json_encode([
        'success'       => true,
        'total'         => $total,
        'scanned'       => $scanned,
        'ai_notes'      => $ai_notes,
        'placeholder'   => $placeholder,
        'empty_content' => $empty_content,
        'old_template'  => $old_template,
        'done'          => ($scanned >= $total),
    ]);
}

/* ═══════════════════════════════════════════════════════
   FIX_FILES_BATCH – directly patch physical PHP files
   Works even when DB content doesn't match file content
═══════════════════════════════════════════════════════ */
function doFixFilesBatch($db, $gen_dir, $input) {
    $offset    = (int)($input['offset']     ?? 0);
    $batchSize = (int)($input['batch_size'] ?? 30);

    $allFiles = glob($gen_dir . '*.php');
    if (!$allFiles) { ob_clean(); echo json_encode(['success' => true, 'fixed' => 0, 'errors' => 0, 'processed' => 0, 'has_more' => false]); return; }
    $allFiles = array_values(array_filter($allFiles, fn($f) => basename($f) !== 'index.php'));

    $batch  = array_slice($allFiles, $offset, $batchSize);
    $fixed  = 0; $errors = 0; $skipped = 0;

    foreach ($batch as $file) {
        try {
            $raw = file_get_contents($file);
            if ($raw === false) { $errors++; continue; }

            $hasAiNotes   = stripos($raw, 'Key improvements and explanations') !== false
                         || stripos($raw, 'In this improved version') !== false
                         || stripos($raw, 'Changes made:') !== false;
            $hasPlaceholder = strpos($raw, '[Insert Phone') !== false
                           || strpos($raw, '[Phone Number]') !== false
                           || strpos($raw, '[Insert Website') !== false;
            $isOldTemplate  = strpos($raw, 'gcm-wrap') === false && strpos($raw, 'gcm-grid') === false;

            if (!$hasAiNotes && !$hasPlaceholder && !$isOldTemplate) {
                $skipped++;
                continue;
            }

            if ($isOldTemplate || $hasPlaceholder || !$hasAiNotes) {
                // Try to rebuild entire file from DB
                $slug = basename($file, '.php');
                $page = $db->fetchOne("
                    SELECT p.*, k.keyword_name, k.keyword_slug, k.category, a.area_name, a.area_slug
                    FROM generated_pages p
                    JOIN seo_service_keywords k ON k.id = p.keyword_id
                    JOIN service_areas a ON a.id = p.area_id
                    WHERE p.page_slug = ?
                ", [$slug]);

                if ($page && !empty($page['content'])) {
                    $cleanContent = cleanContent($page['content']);
                    $h1 = $page['h1_heading'] ?? ('Professional ' . $page['keyword_name'] . ' in ' . $page['area_name'] . ', Chennai');
                    $newFile = buildPage(
                        $page['page_title'] ?? '', $h1,
                        $page['meta_description'] ?? '', $page['meta_keywords'] ?? '',
                        $cleanContent, $page['keyword_name'], $page['keyword_slug'],
                        $page['area_name'], $page['area_slug'], $page['category'] ?? ''
                    );
                    if (file_put_contents($file, $newFile) !== false) {
                        // Sync clean content back to DB
                        $db->execute("UPDATE generated_pages SET content=?, word_count=?, updated_at=NOW() WHERE id=?",
                            [$cleanContent, str_word_count(strip_tags($cleanContent)), $page['id']]);
                        $fixed++;
                    } else { $errors++; }
                    continue;
                }
                // If no DB record, fall through to in-place patch below
            }

            if ($hasAiNotes || $hasPlaceholder) {
                // In-place patch: extract the gcm-content section, clean it, write back
                $startMarker = '<div class="gcm-content">';
                $endMarker   = "\n        </div>\n        <!-- CTA bar -->";
                $startPos = strpos($raw, $startMarker);
                $endPos   = strpos($raw, $endMarker, $startPos !== false ? $startPos : 0);

                if ($startPos !== false && $endPos !== false) {
                    $contentStart = $startPos + strlen($startMarker);
                    $innerContent = substr($raw, $contentStart, $endPos - $contentStart);
                    $cleaned      = cleanContent($innerContent);
                    if ($cleaned !== $innerContent) {
                        $newRaw = substr($raw, 0, $contentStart) . $cleaned . substr($raw, $endPos);
                        if (file_put_contents($file, $newRaw) !== false) {
                            $fixed++;
                        } else { $errors++; }
                    } else { $skipped++; }
                } else {
                    // Fallback: apply cleaning to entire file (safe for AI note patterns)
                    $newRaw = cleanContent($raw);
                    if ($newRaw !== $raw && file_put_contents($file, $newRaw) !== false) {
                        $fixed++;
                    } else { $skipped++; }
                }
            }
        } catch (Exception $e) {
            $errors++;
        }
    }

    ob_clean();
    echo json_encode([
        'success'   => true,
        'fixed'     => $fixed,
        'errors'    => $errors,
        'skipped'   => $skipped,
        'processed' => count($batch),
        'has_more'  => ($offset + count($batch)) < count($allFiles),
        'total'     => count($allFiles),
    ]);
}

/* ═══════════════════════════════════════════════════════
   CLEAN CONTENT  – strips AI notes, placeholders, markdown
═══════════════════════════════════════════════════════ */
function cleanContent($content) {
    if (empty($content)) return $content;

    // ── Cut AI commentary that AI appends after the actual content ──
    $cutAt = [
        'Key improvements and explanations:',
        'Key improvements and explanations.',
        'In this improved version,',
        'In this revised version,',
        'Changes made:',
        'Improvements made:',
        'Summary of changes:',
        'Note: This',
        'Here\'s a breakdown of the key improvements:',
        'Here are the key improvements:',
    ];
    foreach ($cutAt as $marker) {
        $pos = stripos($content, $marker);
        if ($pos !== false) {
            // Only cut if the marker is in the last 40% of the content
            // (avoids cutting content that happens to mention improvements)
            if ($pos > strlen($content) * 0.5) {
                $content = substr($content, 0, $pos);
            }
        }
    }

    // ── Replace unfilled placeholders ──
    $content = preg_replace('/\[Insert Phone Number\]/i',      '9912399224',               $content);
    $content = preg_replace('/\[Insert Website Address\]/i',   'www.gcmsafetynets.in',      $content);
    $content = preg_replace('/\[Phone Number\]/i',             '9912399224',               $content);
    $content = preg_replace('/\[Phone\]/i',                    '9912399224',               $content);
    $content = preg_replace('/\[Company Name\]/i',             'GCM Netting Solutions',          $content);
    $content = preg_replace('/\[Your Company\]/i',             'GCM Netting Solutions',          $content);
    $content = preg_replace('/\[Your Website\]/i',             'www.gcmsafetynets.in',      $content);
    $content = preg_replace('/\[Website URL\]/i',              'https://gcmsafetynets.in', $content);
    $content = preg_replace('/\[Website\]/i',                  'gcmsafetynets.in',         $content);
    $content = preg_replace('/\[Email\]/i',                    'gcmsafetynets@gmail.com',  $content);
    $content = preg_replace('/\[Contact Number\]/i',           '9912399224',               $content);
    $content = preg_replace('/\[City\]/i',                     'Chennai',                $content);
    $content = preg_replace('/\[Location\]/i',                 'Chennai',                $content);

    // ── Convert leftover markdown bold/italic to HTML ──
    $content = preg_replace('/\*\*([^*\n]+)\*\*/s', '<strong>$1</strong>', $content);
    $content = preg_replace('/\*([^*\n]+)\*/s',     '<em>$1</em>',         $content);

    // ── Remove raw markdown headings (## Heading → already wrapped in <h2> usually) ──
    $content = preg_replace('/^#{1,6}\s+(.+)$/m', '$1', $content);

    // ── Clean up stray backticks ──
    $content = str_replace(['```html', '```', '`'], '', $content);

    return trim($content);
}

/* ═══════════════════════════════════════════════════════
   BUILD PAGE  – generates PHP file content (same as
   gcm_build_page in generate-page-single.php)
═══════════════════════════════════════════════════════ */
function buildPage($title, $h1, $metaDesc, $metaKw, $content, $svcName, $svcSlug, $areaName, $areaSlug, $category = '') {
    $t      = addslashes($title);
    $md     = addslashes($metaDesc);
    $mk     = addslashes($metaKw);
    $sn     = addslashes($svcName);
    $h1safe = htmlspecialchars($h1, ENT_QUOTES);
    $anSafe = htmlspecialchars($areaName, ENT_QUOTES);
    $snSafe = htmlspecialchars($svcName, ENT_QUOTES);

    $part1 = <<<PHP
<?php
define('GCM_INIT', true);
require_once dirname(__DIR__) . '/config/config.php';
require_once dirname(__DIR__) . '/config/database.php';
\$page_title       = '{$t}';
\$meta_description = '{$md}';
\$meta_keywords    = '{$mk}';
\$current_page     = 'services';
\$_db = Database::getInstance();
\$_cat_kws = \$_db->fetchAll(
    "SELECT keyword_name, keyword_slug FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order"
);
\$_all_areas = \$_db->fetchAll(
    "SELECT area_name, area_slug FROM service_areas WHERE is_active = 1 ORDER BY area_name"
);
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
<style>
@keyframes gcm-kb{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}
</style>

<div class="gcm-wrap">
  <div class="gcm-grid">
    <div class="gcm-main">
      <div class="gcm-card">
        <div class="gcm-content">
PHP;

    $part2 = <<<PHP2

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

    <div class="gcm-sidebar">
      <div class="gcm-card" id="contact-form">
        <h3 class="gcm-sb-title">&#128221; Get Free Quote</h3>
        <div id="gcm-form-msg" style="display:none;padding:10px 14px;border-radius:8px;margin-bottom:12px;font-size:14px;font-weight:600;"></div>
        <form id="gcm-inquiry-form" onsubmit="gcmSubmitForm(event)">
          <input type="hidden" name="form_type" value="service_page">
          <input type="hidden" name="service"   value="{$sn}">
          <input type="hidden" name="area"      value="{$areaSlug}">
          <input type="text"  name="name"    placeholder="Your Name *"    required minlength="3" style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
          <input type="tel"   name="phone"   placeholder="Phone Number *" required pattern="[6-9][0-9]{9}" style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
          <input type="email" name="email"   placeholder="Email Address *" required style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'">
          <textarea name="message" placeholder="Your requirements *" rows="3" required minlength="10" style="width:100%;padding:11px 12px;border:1.5px solid #e2e8f0;border-radius:8px;margin-bottom:10px;font-size:14px;resize:vertical;outline:none;transition:border .2s;" onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
          <button type="submit" id="gcm-form-btn" style="width:100%;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;padding:13px;border-radius:8px;font-weight:700;cursor:pointer;font-size:15px;">Send Inquiry &#x27A4;</button>
        </form>
        <script>
        function gcmSubmitForm(e){e.preventDefault();var btn=document.getElementById('gcm-form-btn');var msg=document.getElementById('gcm-form-msg');btn.disabled=true;btn.textContent='Sending...';var fd=new FormData(document.getElementById('gcm-inquiry-form'));fetch('<?php echo SITE_URL; ?>/api/contact-handler.php',{method:'POST',body:fd}).then(function(r){return r.json();}).then(function(d){msg.style.display='block';if(d.success){msg.style.background='#d1fae5';msg.style.color='#065f46';msg.innerHTML='&#10003; '+d.message;document.getElementById('gcm-inquiry-form').reset();btn.textContent='Sent!';}else{msg.style.background='#fee2e2';msg.style.color='#991b1b';msg.innerHTML='&#9888; '+(d.message||'Please try again.');btn.disabled=false;btn.textContent='Send Inquiry \u27a4';}}).catch(function(){msg.style.display='block';msg.style.background='#fee2e2';msg.style.color='#991b1b';msg.innerHTML='&#9888; Network error. Please call us.';btn.disabled=false;btn.textContent='Send Inquiry \u27a4';});}
        </script>
      </div>

      <div class="gcm-card">
        <h3 class="gcm-sb-title">Service Highlights</h3>
        <ul class="gcm-highlights" style="padding:0;margin:0;">
          <li>&#10003; Free Home Inspection</li><li>&#10003; Same Day Installation</li>
          <li>&#10003; 5 Year Warranty</li><li>&#10003; Premium Quality Materials</li>
          <li>&#10003; Expert Certified Technicians</li><li>&#10003; Best Price Guarantee</li>
        </ul>
      </div>

      <div class="gcm-card">
        <h3 class="gcm-sb-title">Why Choose GCM?</h3>
        <ul class="gcm-highlights" style="padding:0;margin:0 0 14px;">
          <li>&#11088; 15+ Years Experience</li><li>&#11088; 10,000+ Happy Customers</li>
          <li>&#11088; Chennai's #1 Choice</li><li>&#11088; ISO Certified Company</li>
          <li>&#11088; 24/7 Customer Support</li>
        </ul>
        <a href="tel:+919912399224" style="display:block;background:linear-gradient(135deg,#10B981,#059669);color:white;padding:12px;border-radius:8px;text-decoration:none;font-weight:700;font-size:15px;text-align:center;">&#128222; 9912399224</a>
      </div>

      <?php if (!empty(\$_cat_kws)): ?>
      <div class="gcm-card">
        <h3 class="gcm-sb-title">All Services in {$anSafe}</h3>
        <div class="gcm-sb-links">
          <?php foreach(\$_cat_kws as \$_kw): ?>
            <?php \$_isActive = (\$_kw['keyword_slug'] === '{$svcSlug}'); ?>
            <a href="<?php echo SITE_URL . '/' . \$_kw['keyword_slug'] . '-in-{$areaSlug}'; ?>"
               class="<?php echo \$_isActive ? 'active' : ''; ?>">
              <?php if(\$_isActive): ?>&#128205; <?php endif; ?><?php echo htmlspecialchars(\$_kw['keyword_name']); ?> in {$anSafe}
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if (!empty(\$_all_areas)): ?>
      <div class="gcm-card">
        <h3 class="gcm-sb-title">{$snSafe} in All Areas</h3>
        <div class="gcm-sb-links">
          <?php foreach(\$_all_areas as \$_area): ?>
            <?php \$_isActive = (\$_area['area_slug'] === '{$areaSlug}'); ?>
            <a href="<?php echo SITE_URL . '/{$svcSlug}-in-' . \$_area['area_slug']; ?>"
               class="<?php echo \$_isActive ? 'active' : ''; ?>">
              <?php if(\$_isActive): ?>&#128205; <?php endif; ?>{$snSafe} in <?php echo htmlspecialchars(\$_area['area_name']); ?>
            </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
<?php include dirname(__DIR__) . '/includes/modern-footer.php'; ?>
PHP2;

    return $part1 . $content . $part2;
}
