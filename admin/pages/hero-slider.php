<?php
/**
 * Hero Slider Management
 * Manages the `services` table — exactly what index.php uses for the homepage slider.
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$page_title = 'Hero Slider Management';

// ── The 6 main category slides (single source of truth) ──────────────────────
$_main_cats = [
    ['order'=>1,'slug'=>'pigeon-nets',     'name'=>'Pigeon Nets',     'icon'=>'fas fa-feather-alt', 'desc'=>'Expert pigeon netting to protect your balcony, terrace & AC units — fully mess-free'],
    ['order'=>2,'slug'=>'bird-nets',        'name'=>'Bird Nets',       'icon'=>'fas fa-dove',         'desc'=>'Humane bird-proofing solutions for homes, offices & commercial buildings across Chennai'],
    ['order'=>3,'slug'=>'safety-nets',      'name'=>'Safety Nets',     'icon'=>'fas fa-shield-alt',   'desc'=>'ISI-certified balcony & staircase safety nets — trusted by 10,000+ families in Chennai'],
    ['order'=>4,'slug'=>'cricket-nets',     'name'=>'Cricket Nets',    'icon'=>'fas fa-baseball-ball','desc'=>'High-tension practice nets for schools, clubs & private backyards — custom sizes available'],
    ['order'=>5,'slug'=>'invisible-grills', 'name'=>'Invisible Grills','icon'=>'fas fa-border-none',  'desc'=>'Sleek stainless steel invisible grills — maximum safety with zero obstruction to your view'],
    ['order'=>6,'slug'=>'cloth-hangers',    'name'=>'Cloth Hangers',   'icon'=>'fas fa-tshirt',       'desc'=>'Space-saving ceiling-mounted cloth drying systems — strong, rust-proof & built to last'],
];

// ── One-time migration: add show_in_slider column ─────────────────────────
try {
    $db->execute("ALTER TABLE services ADD COLUMN show_in_slider TINYINT(1) NOT NULL DEFAULT 0");
} catch (Exception $e) { /* column already exists — ignore */ }

// ── Helper: find a service row by slug or service_slug ───────────────────
function findServiceBySlug($db, $slug) {
    // slug is the original unique column; service_slug is dynamically added
    $row = $db->fetchOne("SELECT id FROM services WHERE slug=? LIMIT 1", [$slug]);
    if ($row) return $row;
    return $db->fetchOne("SELECT id FROM services WHERE service_slug=? LIMIT 1", [$slug]);
}

// ── Core helper: activate a slide using raw PDO (UPDATE-first, INSERT if missing) ───
function sliderActivate($pdo, $slug, $name, $icon, $order, $desc = '') {
    // 1. Ensure dynamically-added slider columns exist
    try { $pdo->exec("ALTER TABLE services ADD COLUMN show_in_slider TINYINT(1) NOT NULL DEFAULT 0"); } catch (\Exception $e) {}
    try { $pdo->exec("ALTER TABLE services ADD COLUMN service_slug VARCHAR(100) DEFAULT NULL");       } catch (\Exception $e) {}
    try { $pdo->exec("ALTER TABLE services ADD COLUMN icon_class VARCHAR(100) DEFAULT 'fas fa-shield-alt'"); } catch (\Exception $e) {}
    try { $pdo->exec("ALTER TABLE services ADD COLUMN description TEXT");                             } catch (\Exception $e) {}
    try { $pdo->exec("ALTER TABLE services ADD COLUMN display_order INT DEFAULT 0");                 } catch (\Exception $e) {}

    // 2. UPDATE existing row — try slug (original unique col) then service_slug (added col)
    try {
        $st = $pdo->prepare(
            "UPDATE services
             SET show_in_slider=1, is_active=1, display_order=?,
                 service_name=?, service_slug=?, icon_class=?, description=?
             WHERE slug=? OR service_slug=?"
        );
        $st->execute([$order, $name, $slug, $icon, $desc, $slug, $slug]);
        if ($st->rowCount() > 0) return [true, null];
    } catch (\Exception $e) {
        try {
            $st = $pdo->prepare("UPDATE services SET show_in_slider=1, is_active=1, display_order=? WHERE slug=? OR service_slug=?");
            $st->execute([$order, $slug, $slug]);
            if ($st->rowCount() > 0) return [true, null];
        } catch (\Exception $e2) { return [false, $e2->getMessage()]; }
    }

    // 3. No existing row — INSERT
    //    Must satisfy all unique constraints: slug (idx_slug), service_id (idx_service_id)
    //    Also include category if required (NOT NULL on original schema)
    $cat = strtoupper(str_replace('-', ' ', $slug)); // e.g. CLOTH HANGERS
    $lastErr = '';
    $attempts = [
        // Full: all known unique + slider columns
        ["INSERT INTO services (slug, service_id, category, service_name, service_slug, icon_class, description, is_active, display_order, show_in_slider)
          VALUES (?,?,?,?,?,?,?,1,?,1)",
         [$slug, $slug, $cat, $name, $slug, $icon, $desc, $order]],
        // Without optional text columns
        ["INSERT INTO services (slug, service_id, category, service_name, service_slug, is_active, display_order, show_in_slider)
          VALUES (?,?,?,?,?,1,?,1)",
         [$slug, $slug, $cat, $name, $slug, $order]],
        // Minimal: just the NOT NULL + unique columns
        ["INSERT INTO services (slug, service_id, category, service_name, is_active, display_order, show_in_slider)
          VALUES (?,?,?,?,1,?,1)",
         [$slug, $slug, $cat, $name, $order]],
    ];
    foreach ($attempts as [$q, $p]) {
        try { $pdo->prepare($q)->execute($p); return [true, null]; }
        catch (\Exception $e) { $lastErr = $e->getMessage(); }
    }
    return [false, $lastErr];
}

// ── Helper: upsert a service row into the slider ──────────────────────────
function upsertService($db, $name, $slug, $icon, $order) {
    [$ok] = sliderActivate($db->getConnection(), $slug, $name, $icon, $order);
    return $ok;
}

// Check how many slides are currently active in the slider
$_slider_count = (int)(($db->fetchOne("SELECT COUNT(*) AS cnt FROM services WHERE show_in_slider=1") ?: ['cnt'=>0])['cnt']);

// ── Seed: ensure every main category exists & descriptions are always up-to-date ────────────────
foreach ($_main_cats as $_mc) {
    $_ex = findServiceBySlug($db, $_mc['slug']);
    if (!$_ex) {
        // Row missing — insert it
        sliderActivate($db->getConnection(), $_mc['slug'], $_mc['name'], $_mc['icon'], $_mc['order'], $_mc['desc'] ?? '', $_mc['subtitle'] ?? '');
    } else {
        // Row exists — always sync the description so it's never blank/stale
        try {
            $db->execute(
                "UPDATE services SET description=? WHERE (service_slug=? OR slug=?)",
                [$_mc['desc'], $_mc['slug'], $_mc['slug']]
            );
        } catch (\Exception $_ue) {}
        if ($_slider_count === 0 && !(int)$_ex['show_in_slider']) {
            $db->execute("UPDATE services SET show_in_slider=1 WHERE id=?", [$_ex['id']]);
        }
    }
}

// Absolute path to services image folder
$img_dir_abs = dirname(__DIR__, 2) . '/assets/img/services/';

// ── The 6 main category slides (single source of truth) ──────────────────────
$_main_cats = [
    ['order'=>1,'slug'=>'pigeon-nets',     'name'=>'Pigeon Nets',     'icon'=>'fas fa-feather-alt', 'desc'=>'Expert pigeon netting to protect your balcony, terrace & AC units — fully mess-free'],
    ['order'=>2,'slug'=>'bird-nets',        'name'=>'Bird Nets',       'icon'=>'fas fa-dove',         'desc'=>'Humane bird-proofing solutions for homes, offices & commercial buildings across Chennai'],
    ['order'=>3,'slug'=>'safety-nets',      'name'=>'Safety Nets',     'icon'=>'fas fa-shield-alt',   'desc'=>'ISI-certified balcony & staircase safety nets — trusted by 10,000+ families in Chennai'],
    ['order'=>4,'slug'=>'cricket-nets',     'name'=>'Cricket Nets',    'icon'=>'fas fa-baseball-ball','desc'=>'High-tension practice nets for schools, clubs & private backyards — custom sizes available'],
    ['order'=>5,'slug'=>'invisible-grills', 'name'=>'Invisible Grills','icon'=>'fas fa-border-none',  'desc'=>'Sleek stainless steel invisible grills — maximum safety with zero obstruction to your view'],
    ['order'=>6,'slug'=>'cloth-hangers',    'name'=>'Cloth Hangers',   'icon'=>'fas fa-tshirt',       'desc'=>'Space-saving ceiling-mounted cloth drying systems — strong, rust-proof & built to last'],
];

// ── Helper: save uploaded image (move first, then optionally GD-resize) ────────
function saveSlideImage(string $tmp_path, string $dest_path): bool {
    $dir = dirname($dest_path);
    if (!is_dir($dir)) @mkdir($dir, 0755, true);
    @chmod($dir, 0755);

    // Step 1: move the uploaded file to destination (most reliable method)
    $moved = false;
    if (is_uploaded_file($tmp_path)) {
        $moved = @move_uploaded_file($tmp_path, $dest_path);
    }
    if (!$moved) {
        $moved = @copy($tmp_path, $dest_path);
    }
    if (!$moved) return false;
    @chmod($dest_path, 0644);

    // Step 2: optionally GD-resize to 1920×800 in-place (non-fatal if it fails)
    if (!function_exists('imagecreatefromjpeg')) return true;
    $info = @getimagesize($dest_path);
    if (!$info) return true;
    switch ($info[2]) {
        case IMAGETYPE_JPEG: $src = @imagecreatefromjpeg($dest_path); break;
        case IMAGETYPE_PNG:  $src = @imagecreatefrompng($dest_path);  break;
        case IMAGETYPE_WEBP: $src = @imagecreatefromwebp($dest_path); break;
        default: return true;
    }
    if (!$src) return true;
    $tw = 1920; $th = 800;
    $sw = imagesx($src); $sh = imagesy($src);
    $src_ratio = $sw / $sh; $dst_ratio = $tw / $th;
    if ($src_ratio > $dst_ratio) {
        $crop_h = $sh; $crop_w = (int)($sh * $dst_ratio);
        $crop_x = (int)(($sw - $crop_w) / 2); $crop_y = 0;
    } else {
        $crop_w = $sw; $crop_h = (int)($sw / $dst_ratio);
        $crop_x = 0; $crop_y = (int)(($sh - $crop_h) / 2);
    }
    $out = imagecreatetruecolor($tw, $th);
    imagecopyresampled($out, $src, 0, 0, $crop_x, $crop_y, $tw, $th, $crop_w, $crop_h);
    imagejpeg($out, $dest_path, 90);
    imagedestroy($src); imagedestroy($out);
    return true;
}

// ── POST actions ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── ADD new slide ──────────────────────────────────────────────────────
    if ($action === 'add_slide') {
        $name  = trim($_POST['service_name']   ?? '');
        $slug  = trim($_POST['service_slug']   ?? '');
        $icon  = trim($_POST['icon_class']     ?? 'fas fa-shield-alt');
        $desc  = trim($_POST['description']    ?? '');
        $order = (int)($_POST['display_order'] ?? 99);
        $slug  = preg_replace('/[^a-z0-9-]/', '-', strtolower($slug));
        $slug  = trim(preg_replace('/-+/', '-', $slug), '-');

        if ($name && $slug) {
            // Upload image if provided
            if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === 0) {
                if (!is_dir($img_dir_abs)) { @mkdir($img_dir_abs, 0777, true); @chmod($img_dir_abs, 0777); }
                $f = $_FILES['slide_image'];
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp']) && $f['size'] <= 10*1024*1024) {
                    if (!saveSlideImage($f['tmp_name'], $img_dir_abs . $slug . '.jpg')) {
                        $_SESSION['error_message'] = 'Image upload failed. Dest: ' . $img_dir_abs . $slug . '.jpg — check that assets/img/services/ is writable (chmod 755).';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid image: use JPG/PNG/WebP under 10MB.';
                }
            }
            [$ok, $dbErr] = sliderActivate($db->getConnection(), $slug, $name, $icon, $order, $desc);
            if ($ok) {
                if (!isset($_SESSION['error_message'])) {
                    $_SESSION['success_message'] = "Slide \"$name\" added successfully!";
                }
            } else {
                $_SESSION['error_message'] = 'Failed to add slide \"' . $slug . '\": ' . ($dbErr ?: 'unknown DB error — check Hostinger error log.');
            }
        } else {
            $_SESSION['error_message'] = 'Slide title and URL slug are required.';
        }
        header('Location: ?page=hero-slider'); exit;
    }

    // ── UPDATE existing slide ──────────────────────────────────────────────
    if ($action === 'update_slide') {
        $id   = (int)($_POST['slide_id']       ?? 0);
        $name = trim($_POST['service_name']    ?? '');
        $icon = trim($_POST['icon_class']      ?? '');
        $desc = trim($_POST['description']     ?? '');
        $ord  = (int)($_POST['display_order']  ?? 99);
        $row  = $db->fetchOne("SELECT service_slug, slug FROM services WHERE id = ?", [$id]);
        if ($row && $name) {
            // Resolve effective slug: prefer service_slug, fall back to legacy slug column
            $slug = trim($row['service_slug'] ?: ($row['slug'] ?? ''));
            // Self-heal: if service_slug was NULL, write it back so future loads work
            if (!$row['service_slug'] && $slug) {
                $db->execute("UPDATE services SET service_slug=? WHERE id=?", [$slug, $id]);
            }
            if (!$slug) {
                $_SESSION['error_message'] = 'Cannot save image: service slug is empty. Try re-adding this slide.';
                header('Location: ?page=hero-slider'); exit;
            }
            // Optional new image
            if (isset($_FILES['slide_image']) && $_FILES['slide_image']['error'] === 0) {
                $f = $_FILES['slide_image'];
                $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp']) && $f['size'] <= 10*1024*1024) {
                    if (!saveSlideImage($f['tmp_name'], $img_dir_abs . $slug . '.jpg')) {
                        $_SESSION['error_message'] = 'Image upload failed. Path: ' . $img_dir_abs . $slug . '.jpg — ensure assets/img/services/ is writable (chmod 755).';
                    }
                } else {
                    $_SESSION['error_message'] = 'Invalid image: use JPG/PNG/WebP under 10MB.';
                }
            }
            $db->execute(
                "UPDATE services SET service_name=?, icon_class=?, description=?, display_order=? WHERE id=?",
                [$name, $icon, $desc, $ord, $id]
            );
            if (!isset($_SESSION['error_message'])) {
                $_SESSION['success_message'] = "Slide \"$name\" updated!";
            }
        }
        header('Location: ?page=hero-slider'); exit;
    }

    // ── TOGGLE show in slider ──────────────────────────────────────────────
    if ($action === 'toggle_slider') {
        $id = (int)($_POST['slide_id'] ?? 0);
        $db->execute("UPDATE services SET show_in_slider = 1 - show_in_slider WHERE id = ?", [$id]);
        $_SESSION['success_message'] = 'Slider visibility updated.';
        header('Location: ?page=hero-slider'); exit;
    }

    // ── TOGGLE active/inactive ────────────────────────────────────────────
    if ($action === 'toggle_status') {
        $id = (int)($_POST['slide_id'] ?? 0);
        $db->execute("UPDATE services SET is_active = 1 - is_active WHERE id = ?", [$id]);
        $_SESSION['success_message'] = 'Slide visibility toggled.';
        header('Location: ?page=hero-slider'); exit;
    }

    // ── DELETE slide ──────────────────────────────────────────────────────
    if ($action === 'delete_slide') {
        $id  = (int)($_POST['slide_id'] ?? 0);
        $row = $db->fetchOne("SELECT service_name, service_slug FROM services WHERE id = ?", [$id]);
        if ($row) {
            @unlink($img_dir_abs . $row['service_slug'] . '.jpg');
            $db->execute("DELETE FROM services WHERE id = ?", [$id]);
            $_SESSION['success_message'] = "Slide \"{$row['service_name']}\" deleted.";
        }
        header('Location: ?page=hero-slider'); exit;
    }

    // ── RESET to default 6 category slides ───────────────────────────────
    if ($action === 'reset_to_defaults') {
        $pdo = $db->getConnection();
        // Turn off show_in_slider for ALL slides first
        try { $pdo->exec("UPDATE services SET show_in_slider=0"); } catch (\Exception $e) {}
        $resetOk = 0; $resetErrors = [];
        foreach ($_main_cats as $cat) {
            [$done, $err] = sliderActivate($pdo, $cat['slug'], $cat['name'], $cat['icon'], $cat['order'], $cat['desc'] ?? '');
            if ($done) $resetOk++;
            elseif ($err) $resetErrors[] = $cat['name'] . ': ' . $err;
        }
        if ($resetOk === 6) {
            $_SESSION['success_message'] = 'Slider reset — all 6 category slides activated!';
        } else {
            $_SESSION['success_message'] = "Slider reset — {$resetOk}/6 slides activated.";
            if ($resetErrors) $_SESSION['error_message'] = implode(' | ', $resetErrors);
        }
        header('Location: ?page=hero-slider'); exit;
    }
}

// Fetch only slider slides (show_in_slider = 1)
$slides = $db->fetchAll("SELECT * FROM services WHERE show_in_slider = 1 ORDER BY display_order ASC, id ASC");

// Pre-compute stats
$total_slides    = count($slides);
$visible_slides  = count(array_filter($slides, fn($s) => !empty($s['is_active'])));
$hidden_slides   = $total_slides - $visible_slides;
$slides_with_img = 0;
foreach ($slides as $_s) {
    $_sv = $_s['service_slug'] ?: ($_s['slug'] ?? '');
    if ($_sv && file_exists($img_dir_abs . $_sv . '.jpg')) $slides_with_img++;
}
$default_active = 0;
foreach ($_main_cats as $_mc2) {
    $_in2 = $db->fetchOne("SELECT show_in_slider FROM services WHERE service_slug=? OR slug=? LIMIT 1", [$_mc2['slug'], $_mc2['slug']]);
    if ($_in2 && !empty($_in2['show_in_slider'])) $default_active++;
}

include '../includes/header.php';
?>

<style>
/* ── Page wrapper ───────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero header card ───────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; }

/* ── Solution banner ────────────────────────────── */
.solution-banner { background: linear-gradient(135deg,rgba(102,126,234,.08),rgba(118,75,162,.05)); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #3730a3; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #4338ca; font-size: 13.5px; margin: 0; line-height: 1.6; }

/* ── Stats grid ─────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; text-align: left !important; border-left: none !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; }
.stat-card .sub { font-size: 11px !important; color: #94a3b8 !important; margin: 3px 0 0 !important; }

/* ── Section card ───────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }
.count-badge { margin-left: auto; background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; }

/* ── Alerts ─────────────────────────────────────── */
.hs-alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px; animation: fadeIn .3s ease; }
@keyframes fadeIn { from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none} }
.hs-alert-success { background: linear-gradient(135deg,#f0fdf4,#ecfdf5); border: 1.5px solid #86efac; color: #166534; }
.hs-alert-error   { background: linear-gradient(135deg,#fff1f2,#fff5f5); border: 1.5px solid #fca5a5; color: #991b1b; }

/* ── Slide cards grid ───────────────────────────── */
.hs-slides-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(320px,1fr)); gap: 20px; }
.hs-slide { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 18px rgba(0,0,0,.08); transition: transform .3s, box-shadow .3s; position: relative; border: 1px solid #f1f5f9; }
.hs-slide:hover { transform: translateY(-4px); box-shadow: 0 10px 28px rgba(102,126,234,.15); }
.hs-slide-thumb { width: 100%; height: 190px; object-fit: cover; display: block; background: #f1f5f9; }
.hs-slide-thumb-placeholder { width: 100%; height: 190px; background: linear-gradient(135deg,#e2e8f0,#f1f5f9); display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px; color: #94a3b8; }
.hs-slide-thumb-placeholder i { font-size: 40px; }
.hs-slide-thumb-placeholder span { font-size: 13px; }
.hs-badge-order  { position: absolute; top: 12px; left: 12px; background: rgba(0,0,0,.7); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.hs-badge-status { position: absolute; top: 12px; right: 12px; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.hs-badge-status.on  { background: #10b981; color: #fff; }
.hs-badge-status.off { background: #ef4444; color: #fff; }
.hs-slide-body { padding: 16px 18px; }
.hs-slide-icon { display: flex; align-items: center; gap: 8px; color: #6366f1; font-size: 14px; margin-bottom: 5px; }
.hs-slide-icon i { font-size: 17px; }
.hs-slide-title { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 5px; }
.hs-slide-desc  { font-size: 13px; color: #64748b; margin: 0 0 12px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.hs-slide-actions { display: flex; gap: 7px; border-top: 1px solid #f1f5f9; padding-top: 12px; }
.hs-slide-actions .hs-btn { flex: 1; justify-content: center; min-width: 0; }

/* ── Slide action buttons ───────────────────────── */
.hs-btn { padding: 7px 13px; border: none; border-radius: 8px; font-size: 12.5px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; transition: all .2s; white-space: nowrap; }
.hs-btn-primary   { background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; }
.hs-btn-primary:hover { opacity: .88; transform: translateY(-1px); }
.hs-btn-success   { background: linear-gradient(135deg,#10b981,#059669); color: #fff; }
.hs-btn-success:hover { opacity: .88; }
.hs-btn-warning   { background: linear-gradient(135deg,#f59e0b,#d97706); color: #fff; }
.hs-btn-warning:hover { opacity: .88; }
.hs-btn-danger    { background: linear-gradient(135deg,#ef4444,#dc2626); color: #fff; }
.hs-btn-danger:hover { opacity: .88; }
.hs-btn-secondary { background: linear-gradient(135deg,#64748b,#475569); color: #fff; }
.hs-btn-secondary:hover { opacity: .88; }
.hs-btn-lg { padding: 12px 26px; font-size: 15px; border-radius: 12px; }

/* ── Main action buttons ────────────────────────── */
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 13px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#10b981,#059669); color: white; box-shadow: 0 6px 20px rgba(16,185,129,.3); }
.btn-action.green:hover  { box-shadow: 0 10px 30px rgba(16,185,129,.4); }
.btn-action.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); color: white; box-shadow: 0 6px 20px rgba(245,158,11,.3); }
.btn-action.amber:hover  { box-shadow: 0 10px 30px rgba(245,158,11,.4); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
.btn-action.gray:hover   { background: #e2e8f0; transform: none; }

/* ── Form fields ─────────────────────────────────── */
.hs-form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.hs-form-grid .full { grid-column: 1/-1; }
.hs-fgroup label { display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 7px; text-transform: uppercase; letter-spacing: .4px; }
.hs-fgroup input, .hs-fgroup textarea, .hs-fgroup select { width: 100%; padding: 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; color: #1e293b; background: #f8fafc; transition: border-color .2s, box-shadow .2s; box-sizing: border-box; font-family: inherit; }
.hs-fgroup input:focus, .hs-fgroup textarea:focus, .hs-fgroup select:focus { outline: none; border-color: #667eea; background: #fff; box-shadow: 0 0 0 3px rgba(102,126,234,.12); }
.hs-fgroup textarea { resize: vertical; min-height: 80px; }
.hs-upload-box { border: 3px dashed #c7d2fe; border-radius: 14px; background: linear-gradient(135deg,#f8faff,#f0f4ff); padding: 32px 20px; text-align: center; cursor: pointer; transition: all .3s; position: relative; }
.hs-upload-box:hover { border-color: #667eea; background: linear-gradient(135deg,#ede9fe,#eef2ff); }
.hs-upload-box input[type=file] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; }
.hs-upload-box i { font-size: 36px; color: #667eea; }
.hs-upload-box p { margin: 8px 0 0; font-size: 13px; color: #64748b; }
.hs-upload-box strong { color: #1e293b; }
.hs-preview-img { max-height: 120px; max-width: 100%; border-radius: 10px; display: none; margin: 12px auto 0; box-shadow: 0 4px 12px rgba(0,0,0,.12); }

/* ── Category status cards ──────────────────────── */
.cat-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(160px,1fr)); gap: 12px; }
.cat-card { padding: 14px 16px; border-radius: 12px; font-size: 13px; font-weight: 600; color: #1e293b; transition: transform .2s; }
.cat-card:hover { transform: translateY(-2px); }
.cat-card.active   { background: linear-gradient(135deg,#d1fae5,#a7f3d0); border: 1px solid #6ee7b7; }
.cat-card.inactive { background: linear-gradient(135deg,#fef3c7,#fde68a); border: 1px solid #fcd34d; }
.cat-card i { color: #6366f1; margin-right: 6px; }
.cat-card .cat-status { display: block; font-size: 11px; font-weight: 500; margin-top: 5px; }
.cat-card.active   .cat-status { color: #059669; }
.cat-card.inactive .cat-status { color: #d97706; }

/* ── Info banner ────────────────────────────────── */
.info-banner { background: linear-gradient(135deg,#eff6ff,#dbeafe); border: 1px solid #bfdbfe; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px; }
.info-banner p { margin: 0; font-size: 13px; color: #1e40af; line-height: 1.6; }

/* ── Tip bar ────────────────────────────────────── */
.tip-bar { background: linear-gradient(135deg,rgba(245,158,11,.08),rgba(251,191,36,.05)); border: 1px solid rgba(245,158,11,.25); border-left: 5px solid #f59e0b; border-radius: 12px; padding: 16px 20px; display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; }
.tip-bar .tip-icon { width: 36px; height: 36px; background: linear-gradient(135deg,#f59e0b,#d97706); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; flex-shrink: 0; margin-top: 2px; }
.tip-bar strong { color: #92400e; font-size: 13.5px; font-weight: 700; display: block; margin-bottom: 3px; }
.tip-list { list-style: none; padding: 0; margin: 6px 0 0; }
.tip-list li { font-size: 13px; color: #78350f; padding: 3px 0; display: flex; align-items: flex-start; gap: 7px; line-height: 1.5; }
.tip-list li::before { content: '\2713'; color: #d97706; font-weight: 800; flex-shrink: 0; }
.tip-list code { background: rgba(245,158,11,.15); color: #92400e; padding: 1px 6px; border-radius: 4px; font-size: 12px; font-weight: 600; }

/* ── Empty state ────────────────────────────────── */
.empty-state { text-align: center; padding: 56px 20px; }
.empty-state i { font-size: 56px; color: #cbd5e1; margin-bottom: 14px; display: block; }
.empty-state h3 { color: #64748b; margin: 0 0 6px; font-size: 17px; }
.empty-state p  { color: #94a3b8; margin: 0; font-size: 13.5px; }

/* ── Edit modal ─────────────────────────────────── */
.hs-modal-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 9999; align-items: center; justify-content: center; }
.hs-modal-overlay.open { display: flex; }
.hs-modal { background: #fff; border-radius: 18px; width: 700px; max-width: 96vw; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,.25); }
.hs-modal-header { padding: 22px 28px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 18px 18px 0 0; }
.hs-modal-header h3 { margin: 0; font-size: 18px; font-weight: 700; color: white; display: flex; align-items: center; gap: 10px; }
.hs-modal-close { background: rgba(255,255,255,.2); border: none; width: 32px; height: 32px; border-radius: 50%; font-size: 18px; cursor: pointer; color: white; display: flex; align-items: center; justify-content: center; transition: background .2s; }
.hs-modal-close:hover { background: rgba(255,255,255,.35); }
.hs-modal-body  { padding: 28px; }

@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } .hs-form-grid { grid-template-columns: 1fr; } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } .seo-hero { flex-direction: column; align-items: flex-start; } .seo-section-body { padding: 20px 18px; } }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-sliders-h"></i> Hero Slider Management</h1>
        <p>Control the homepage hero slider &mdash; images, titles, and visibility for each slide</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-film" style="margin-right:6px;"></i>Slider Editor</span>
</div>

<!-- Solution Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-sliders-h"></i></div>
    <div>
        <strong>This page controls both the Hero Slider AND the &ldquo;Our Services&rdquo; cards on the homepage</strong>
        <p>Every slide here appears in <strong>two places</strong>: (1) the full-screen hero slider at the top, and (2) the &ldquo;Our Services&rdquo; grid section below it. Click <strong>Edit</strong> on any slide to update its title, description (shown under the service card), icon, background image, and display order. Use <strong>Reset to 6 Categories</strong> to restore all defaults.</p>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-film"></i></div>
        <div class="stat-text-wrap">
            <h3>Active Slides</h3>
            <div class="value"><?php echo $total_slides; ?></div>
            <p class="sub"><?php echo $total_slides === 1 ? 'slide in slider' : 'slides in slider'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-eye"></i></div>
        <div class="stat-text-wrap">
            <h3>Visible</h3>
            <div class="value"><?php echo $visible_slides; ?></div>
            <p class="sub"><?php echo $visible_slides === 1 ? 'shown on homepage' : 'shown on homepage'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-image"></i></div>
        <div class="stat-text-wrap">
            <h3>With Images</h3>
            <div class="value"><?php echo $slides_with_img; ?></div>
            <p class="sub"><?php echo $slides_with_img === $total_slides ? 'all slides have images' : 'missing ' . ($total_slides - $slides_with_img); ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-check-double"></i></div>
        <div class="stat-text-wrap">
            <h3>Defaults Active</h3>
            <div class="value"><?php echo $default_active; ?>/6</div>
            <p class="sub"><?php echo $default_active === 6 ? 'all 6 defaults on' : $default_active . ' of 6 active'; ?></p>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="hs-alert hs-alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
<div class="hs-alert hs-alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<!-- Section 1: Current Slides -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-images" style="color:#667eea;margin-right:8px;"></i>Current Slides on Homepage</h2>
        <span class="count-badge"><?php echo $total_slides; ?> Slide<?php echo $total_slides !== 1 ? 's' : ''; ?></span>
    </div>
    <div class="seo-section-body">
        <?php if (empty($slides)): ?>
        <div class="empty-state">
            <i class="fas fa-film"></i>
            <h3>No slides yet</h3>
            <p>Add your first slide using the form below</p>
        </div>
        <?php else: ?>
        <div class="hs-slides-grid">
            <?php foreach ($slides as $slide):
                $svc_slug = $slide['service_slug'] ?: ($slide['slug'] ?? '');
                $img_abs  = $img_dir_abs . $svc_slug . '.jpg';
                $img_web  = SITE_URL . '/assets/img/services/' . $svc_slug . '.jpg';
                $has_img  = ($svc_slug !== '') && file_exists($img_abs);
            ?>
            <div class="hs-slide">
                <div class="hs-badge-order">Slide <?php echo (int)$slide['display_order']; ?></div>
                <div class="hs-badge-status <?php echo $slide['is_active'] ? 'on' : 'off'; ?>">
                    <?php echo $slide['is_active'] ? 'VISIBLE' : 'HIDDEN'; ?>
                </div>
                <?php if ($has_img): ?>
                    <img src="<?php echo $img_web; ?>?v=<?php echo filemtime($img_abs); ?>" alt="<?php echo htmlspecialchars($slide['service_name']); ?>" class="hs-slide-thumb">
                <?php else: ?>
                    <div class="hs-slide-thumb-placeholder">
                        <i class="fas fa-image"></i>
                        <span>No image yet</span>
                    </div>
                <?php endif; ?>
                <div class="hs-slide-body">
                    <div class="hs-slide-icon">
                        <i class="<?php echo htmlspecialchars($slide['icon_class']); ?>"></i>
                        <span style="font-size:11px;color:#94a3b8;"><?php echo htmlspecialchars($slide['icon_class']); ?></span>
                    </div>
                    <h3 class="hs-slide-title"><?php echo htmlspecialchars($slide['service_name']); ?></h3>
                    <p class="hs-slide-desc"><?php echo htmlspecialchars($slide['description'] ?: 'No description set'); ?></p>
                    <div class="hs-slide-actions">
                        <button class="hs-btn hs-btn-primary" onclick="openEditModal(<?php echo $slide['id']; ?>, <?php echo htmlspecialchars(json_encode($slide)); ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <button type="submit" class="hs-btn <?php echo $slide['is_active'] ? 'hs-btn-warning' : 'hs-btn-success'; ?>">
                                <i class="fas fa-<?php echo $slide['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                <?php echo $slide['is_active'] ? 'Hide' : 'Show'; ?>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" id="removeForm-<?php echo $slide['id']; ?>">
                            <input type="hidden" name="action" value="toggle_slider">
                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <button type="button" class="hs-btn hs-btn-secondary" title="Remove from slider" onclick="confirmRemove(<?php echo $slide['id']; ?>)">
                                <i class="fas fa-minus-circle"></i>
                            </button>
                        </form>
                        <form method="POST" style="display:inline;" id="deleteSlideForm-<?php echo $slide['id']; ?>">
                            <input type="hidden" name="action" value="delete_slide">
                            <input type="hidden" name="slide_id" value="<?php echo $slide['id']; ?>">
                            <button type="button" class="hs-btn hs-btn-danger" onclick="confirmDeleteSlide(<?php echo $slide['id']; ?>, '<?php echo addslashes(htmlspecialchars($slide['service_name'])); ?>')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Section 2: Default Categories -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-th-large" style="color:#3b82f6;margin-right:8px;"></i>6 Default Category Slides</h2>
        <span style="margin-left:auto;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;"><?php echo $default_active; ?>/6 Active</span>
    </div>
    <div class="seo-section-body">
        <div class="info-banner">
            <p><i class="fas fa-info-circle" style="margin-right:6px;"></i>The homepage slider is designed for exactly <strong>6 category slides</strong>. Use <strong>Reset to 6 Categories</strong> to restore the full default set instantly.</p>
        </div>
        <div class="cat-grid">
            <?php foreach ($_main_cats as $mc):
                $in = $db->fetchOne("SELECT show_in_slider FROM services WHERE service_slug=? OR slug=? LIMIT 1", [$mc['slug'], $mc['slug']]);
                $is_active = $in && !empty($in['show_in_slider']);
            ?>
            <div class="cat-card <?php echo $is_active ? 'active' : 'inactive'; ?>">
                <i class="<?php echo $mc['icon']; ?>"></i><?php echo $mc['name']; ?>
                <span class="cat-status"><?php echo $is_active ? '✓ In slider' : '✗ Not in slider'; ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:22px;display:flex;gap:14px;flex-wrap:wrap;align-items:center;">
            <form method="POST" id="resetDefaultsForm">
                <input type="hidden" name="action" value="reset_to_defaults">
                <button type="button" class="btn-action amber" onclick="confirmReset()">
                    <i class="fas fa-undo"></i> Reset to 6 Categories
                </button>
            </form>
            <a href="<?php echo SITE_URL; ?>" target="_blank" class="btn-action gray">
                <i class="fas fa-external-link-alt"></i> View Live Site
            </a>
        </div>
    </div>
</div>

<!-- Section 3: Add New Slide -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">3</div>
        <h2><i class="fas fa-plus-circle" style="color:#10b981;margin-right:8px;"></i>Add New Slide</h2>
    </div>
    <div class="seo-section-body">
        <div class="info-banner">
            <p><i class="fas fa-info-circle" style="margin-right:6px;"></i>The slide title appears as the big heading on the homepage. The URL slug names the background image file: <code style="background:rgba(59,130,246,.1);color:#1e40af;padding:1px 6px;border-radius:4px;font-size:12px;">assets/img/services/{slug}.jpg</code></p>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_slide">
            <div class="hs-form-grid">
                <div class="hs-fgroup">
                    <label>Slide Title *</label>
                    <input type="text" name="service_name" placeholder="e.g., Pigeon Safety Nets" required>
                </div>
                <div class="hs-fgroup">
                    <label>URL Slug * <small style="color:#94a3b8;text-transform:none;font-weight:400;">(auto-fills from title)</small></label>
                    <input type="text" name="service_slug" id="newSlug" placeholder="e.g., pigeon-nets" required>
                </div>
                <div class="hs-fgroup">
                    <label>Icon Class <small style="color:#94a3b8;text-transform:none;font-weight:400;">(Font Awesome)</small></label>
                    <input type="text" name="icon_class" placeholder="fas fa-shield-alt" value="fas fa-shield-alt">
                </div>
                <div class="hs-fgroup">
                    <label>Display Order</label>
                    <input type="number" name="display_order" value="<?php echo $total_slides + 1; ?>" min="1">
                </div>
                <div class="hs-fgroup full">
                    <label>Description</label>
                    <textarea name="description" placeholder="Short description shown below the title on the homepage (optional)"></textarea>
                </div>
                <div class="hs-fgroup full">
                    <label>Background Image &mdash; 1920&times;800 px recommended &nbsp;&middot;&nbsp; Max 10MB &nbsp;&middot;&nbsp; JPG / PNG / WebP</label>
                    <div class="hs-upload-box" id="newUploadBox">
                        <input type="file" name="slide_image" accept="image/jpeg,image/png,image/webp" onchange="previewImg(this,'newPreview','newUploadBox')">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p><strong>Click to upload</strong> or drag &amp; drop</p>
                        <p>Saved as <code style="background:rgba(102,126,234,.1);color:#4338ca;padding:1px 5px;border-radius:4px;">{slug}.jpg</code></p>
                        <img id="newPreview" class="hs-preview-img" alt="Preview">
                    </div>
                </div>
            </div>
            <div style="margin-top:24px;">
                <button type="submit" class="btn-action green">
                    <i class="fas fa-plus-circle"></i> Add Slide to Slider
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tip Bar -->
<div class="tip-bar">
    <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
    <div>
        <strong>Slider Tips</strong>
        <ul class="tip-list">
            <li>Background images should be <code>1920 &times; 800 px</code> &mdash; they are center-cropped to fit</li>
            <li>The slider is designed for <strong>exactly 6 slides</strong> &mdash; more or fewer may look unbalanced</li>
            <li>Use <strong>Hide/Show</strong> to temporarily remove a slide without deleting it</li>
            <li>Images are stored at <code>assets/img/services/{slug}.jpg</code></li>
            <li>Slide changes go live immediately on the homepage &mdash; no cache to clear</li>
        </ul>
    </div>
</div>

</div>

<!-- ── Edit Modal ─────────────────────────────────────────────────────────── -->
<div class="hs-modal-overlay" id="editModal">
    <div class="hs-modal">
        <div class="hs-modal-header">
            <h3><i class="fas fa-edit"></i> Edit Slide</h3>
            <button class="hs-modal-close" onclick="closeEditModal()">&times;</button>
        </div>
        <div class="hs-modal-body">
            <form method="POST" enctype="multipart/form-data" id="editForm">
                <input type="hidden" name="action" value="update_slide">
                <input type="hidden" name="slide_id" id="editSlideId">
                <div class="hs-form-grid">
                    <div class="hs-fgroup">
                        <label>Slide Title *</label>
                        <input type="text" name="service_name" id="editName" required>
                    </div>
                    <div class="hs-fgroup">
                        <label>Icon Class</label>
                        <input type="text" name="icon_class" id="editIcon">
                    </div>
                    <div class="hs-fgroup">
                        <label>Display Order</label>
                        <input type="number" name="display_order" id="editOrder" min="1">
                    </div>
                    <div class="hs-fgroup">
                        <label>URL Slug <small style="color:#94a3b8;">(read-only — image filename)</small></label>
                        <input type="text" id="editSlug" readonly style="background:#f8fafc;color:#94a3b8;">
                    </div>
                    <div class="hs-fgroup full">
                        <label>Description</label>
                        <textarea name="description" id="editDesc"></textarea>
                    </div>
                    <div class="hs-fgroup full">
                        <label>Replace Background Image <small style="color:#94a3b8;">(leave empty to keep current)</small></label>
                        <div class="hs-upload-box" id="editUploadBox">
                            <input type="file" name="slide_image" accept="image/jpeg,image/png,image/webp"
                                   onchange="previewImg(this,'editPreview','editUploadBox')">
                            <i class="fas fa-image"></i>
                            <p><strong>Click to replace</strong> current image</p>
                            <p>JPG, PNG or WebP — max 10MB</p>
                            <img id="editPreview" class="hs-preview-img" alt="Preview">
                        </div>
                        <div id="editCurrentImg" style="margin-top:10px;"></div>
                    </div>
                </div>
                <div style="margin-top:18px;display:flex;gap:12px;">
                    <button type="submit" class="hs-btn hs-btn-primary hs-btn-lg">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <button type="button" class="hs-btn hs-btn-secondary hs-btn-lg" onclick="closeEditModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ── GCMAlert confirm helpers ───────────────────────────────────────────────
function confirmReset() {
    GCMAlert.confirm(
        'Reset slider to the <strong>6 default category slides</strong>? Current custom slides will be hidden (not deleted).',
        'Reset to Defaults',
        function() { document.getElementById('resetDefaultsForm').submit(); }
    );
}
function confirmRemove(id) {
    GCMAlert.confirm(
        'Remove this slide from the homepage slider? The service entry is kept and can be re-added.',
        'Remove from Slider',
        function() { document.getElementById('removeForm-' + id).submit(); }
    );
}
function confirmDeleteSlide(id, name) {
    GCMAlert.confirm(
        'Permanently delete slide <strong>' + name + '</strong>? This cannot be undone.',
        'Delete Slide',
        function() { document.getElementById('deleteSlideForm-' + id).submit(); }
    );
}

// ── Image preview ─────────────────────────────────────────────────────────
function previewImg(input, previewId, boxId) {
    const preview = document.getElementById(previewId);
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(input.files[0]);
    }
}

// ── Auto-fill slug from title ─────────────────────────────────────────────
const nameInput = document.querySelector('input[name="service_name"]');
const slugInput = document.getElementById('newSlug');
if (nameInput && slugInput) {
    nameInput.addEventListener('input', function() {
        if (!slugInput.dataset.touched) {
            slugInput.value = this.value.toLowerCase()
                .replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        }
    });
    slugInput.addEventListener('input', () => slugInput.dataset.touched = '1');
}

// ── Edit modal ────────────────────────────────────────────────────────────
const SITE_URL = <?php echo json_encode(SITE_URL); ?>;

function openEditModal(id, slide) {
    document.getElementById('editSlideId').value = id;
    document.getElementById('editName').value    = slide.service_name || '';
    document.getElementById('editIcon').value    = slide.icon_class   || '';
    document.getElementById('editOrder').value   = slide.display_order || 99;
    const svcSlug = slide.service_slug || slide.slug || '';
    document.getElementById('editSlug').value    = svcSlug;
    document.getElementById('editDesc').value    = slide.description   || '';

    // Show current image thumbnail
    const imgUrl  = SITE_URL + '/assets/img/services/' + svcSlug + '.jpg';
    const imgBox  = document.getElementById('editCurrentImg');
    imgBox.innerHTML = '<p style="font-size:12px;color:#64748b;margin:0 0 6px;">Current image:</p>'
        + '<img src="' + imgUrl + '" onerror="this.parentNode.innerHTML=\'<em style=\"color:#94a3b8;font-size:13px;\">No image on disk</em>\'" '
        + 'style="max-height:100px;border-radius:8px;border:1px solid #e2e8f0;">';

    // Reset file input preview
    document.getElementById('editPreview').style.display = 'none';

    document.getElementById('editModal').classList.add('open');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.remove('open');
    document.body.style.overflow = '';
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php include '../includes/footer.php'; ?>
