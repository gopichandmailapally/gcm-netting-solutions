<?php
/**
 * Gallery Management
 * Upload and manage gallery images
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Session already started in config.php

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$page_title = 'Gallery Management';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_image' && isset($_FILES['gallery_image'])) {
        $title         = trim($_POST['title']         ?? '');
        $alt_text      = trim($_POST['alt_text']      ?? '');
        $category      = trim($_POST['category']      ?? 'general');
        $service_id    = !empty($_POST['service_id']) ? (int)$_POST['service_id'] : null;
        $display_order = (int)($_POST['display_order'] ?? 99);

        $upload_dir = '../../assets/img/gallery/';
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }
        if (!is_dir($upload_dir)) {
            $_SESSION['error_message'] = 'Upload folder could not be created. Set assets/img/gallery/ permissions to 755 in Hostinger File Manager.';
            header('Location: ?page=gallery-management'); exit;
        }

        $file = $_FILES['gallery_image'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error_message'] = 'Upload error code ' . $file['error'] . '. Check PHP upload_max_filesize setting.';
            header('Location: ?page=gallery-management'); exit;
        }

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error_message'] = 'Only JPG, PNG, and WebP images are allowed';
        } elseif ($file['size'] > 10 * 1024 * 1024) {
            $_SESSION['error_message'] = 'Image size must be less than 10MB';
        } else {
            // Always save as .jpg for consistency
            $filename = 'gallery-' . time() . '-' . rand(1000, 9999) . '.jpg';
            $filepath = $upload_dir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                resizeGalleryImage($filepath);
                $image_url = 'assets/img/gallery/' . $filename;

                $db->execute(
                    "INSERT INTO gallery_images (image_url, title, alt_text, category, service_id, display_order) VALUES (?, ?, ?, ?, ?, ?)",
                    [$image_url, $title, $alt_text, $category, $service_id, $display_order]
                );
                $_SESSION['success_message'] = 'Gallery image uploaded successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to move uploaded file. Check folder write permissions on assets/img/gallery/';
            }
        }

        header('Location: ?page=gallery-management');
        exit;
    }
    
    if ($action === 'delete_image') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $image = $db->fetchOne("SELECT image_url FROM gallery_images WHERE id = ?", [$image_id]);

        if ($image) {
            // Delete file
            $filepath = '../../' . $image['image_url'];
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Delete from database
            $db->execute("DELETE FROM gallery_images WHERE id = ?", [$image_id]);
            
            $_SESSION['success_message'] = 'Image deleted successfully!';
        }
        
        header('Location: ?page=gallery-management');
        exit;
    }
    
    if ($action === 'toggle_status') {
        $image_id = (int)($_POST['image_id'] ?? 0);
        $db->execute("UPDATE gallery_images SET is_active = 1 - is_active WHERE id = ?", [$image_id]);
        
        $_SESSION['success_message'] = 'Image status updated!';
        header('Location: ?page=gallery-management');
        exit;
    }

    if ($action === 'edit_image') {
        $image_id      = (int)($_POST['image_id']      ?? 0);
        $title         = trim($_POST['title']          ?? '');
        $alt_text      = trim($_POST['alt_text']       ?? '');
        $category      = trim($_POST['category']       ?? 'general');
        $service_id    = !empty($_POST['service_id'])  ? (int)$_POST['service_id'] : null;
        $display_order = (int)($_POST['display_order'] ?? 99);

        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['gallery_image'];
            $allowed_types = ['image/jpeg','image/jpg','image/png','image/webp'];
            if (!in_array($file['type'], $allowed_types)) {
                $_SESSION['error_message'] = 'Only JPG, PNG, and WebP images are allowed';
            } elseif ($file['size'] > 10 * 1024 * 1024) {
                $_SESSION['error_message'] = 'Image size must be less than 10MB';
            } else {
                $old        = $db->fetchOne("SELECT image_url FROM gallery_images WHERE id = ?", [$image_id]);
                $upload_dir = '../../assets/img/gallery/';
                if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
                $filename   = 'gallery-' . time() . '-' . rand(1000,9999) . '.jpg';
                $filepath   = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    resizeGalleryImage($filepath);
                    $new_url = 'assets/img/gallery/' . $filename;
                    if ($old && !empty($old['image_url'])) {
                        $oldpath = '../../' . $old['image_url'];
                        if (file_exists($oldpath)) @unlink($oldpath);
                    }
                    $db->execute(
                        "UPDATE gallery_images SET title=?, alt_text=?, category=?, service_id=?, display_order=?, image_url=? WHERE id=?",
                        [$title, $alt_text, $category, $service_id, $display_order, $new_url, $image_id]
                    );
                    $_SESSION['success_message'] = 'Image updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to upload new image';
                }
            }
        } else {
            $db->execute(
                "UPDATE gallery_images SET title=?, alt_text=?, category=?, service_id=?, display_order=? WHERE id=?",
                [$title, $alt_text, $category, $service_id, $display_order, $image_id]
            );
            $_SESSION['success_message'] = 'Image updated successfully!';
        }
        header('Location: ?page=gallery-management');
        exit;
    }
}

// Fetch all gallery images
$gallery_images = $db->fetchAll("SELECT * FROM gallery_images ORDER BY display_order ASC, id DESC");

// Get categories
$categories = [
    'general' => 'General',
    'residential' => 'Residential Projects',
    'commercial' => 'Commercial Projects',
    'pigeon-nets' => 'Pigeon Nets',
    'bird-nets' => 'Bird Nets',
    'safety-nets' => 'Safety Nets',
    'invisible-grills' => 'Invisible Grills',
    'sports-nets' => 'Sports Nets',
    'cloth-hangers' => 'Cloth Hangers'
];

// Pre-compute stats for the stats grid
$total_images  = count($gallery_images);
$active_images = count(array_filter($gallery_images, fn($i) => !empty($i['is_active'])));
$inactive_images = $total_images - $active_images;
$unique_cats   = count(array_unique(array_filter(array_column($gallery_images, 'category'))));

include '../includes/header.php';
?>

<style>
/* ── Page wrapper ──────────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero header card ──────────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; }

/* ── Solution banner ───────────────────────────────── */
.solution-banner { background: linear-gradient(135deg,rgba(102,126,234,.08),rgba(118,75,162,.05)); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #3730a3; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #4338ca; font-size: 13.5px; margin: 0; line-height: 1.6; }

/* ── Stats grid ────────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; text-align: left !important; border-left: none !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.red    { background: linear-gradient(135deg,#ef4444,#dc2626); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; }
.stat-card .sub { font-size: 11px !important; color: #94a3b8 !important; margin: 3px 0 0 !important; }

/* ── Section card ──────────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-num.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

/* ── Form elements ─────────────────────────────────── */
.form-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 20px; }
.form-group { margin-bottom: 0; }
.form-group.full-width { grid-column: 1/-1; }
.form-group label { display: block; font-weight: 700; color: #475569; margin-bottom: 8px; font-size: 11px; text-transform: uppercase; letter-spacing: .5px; }
.form-control { width: 100%; padding: 12px 15px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 14px; color: #1e293b; background: #f8fafc; transition: border-color .2s, box-shadow .2s; box-sizing: border-box; font-family: inherit; }
.form-control:focus { outline: none; border-color: #667eea; background: #fff; box-shadow: 0 0 0 3px rgba(102,126,234,.12); }

/* ── File upload ───────────────────────────────────── */
.file-upload-wrapper { position: relative; width: 100%; }
.file-upload-input { position: absolute; left: -9999px; }
.file-upload-label { display: flex; align-items: center; justify-content: center; gap: 10px; padding: 40px 20px; border: 3px dashed #c7d2fe; border-radius: 14px; background: linear-gradient(135deg,#f8faff,#f0f4ff); cursor: pointer; transition: all .3s; }
.file-upload-label:hover { border-color: #667eea; background: linear-gradient(135deg,#ede9fe,#eef2ff); }
.file-upload-label i { font-size: 40px; color: #667eea; }

/* ── Action buttons ────────────────────────────────── */
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 13px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 10px 30px rgba(102,126,234,.45); }

/* ── Gallery item buttons ──────────────────────────── */
.btn { padding: 9px 16px; border: none; border-radius: 9px; font-weight: 700; font-size: 12px; cursor: pointer; transition: all .2s; text-decoration: none; display: inline-flex; align-items: center; gap: 7px; white-space: nowrap; }
.btn-secondary { background: linear-gradient(135deg,#64748b,#475569); color: white; flex: 1; justify-content: center; }
.btn-secondary:hover { transform: translateY(-1px); opacity: .9; color: white; }
.btn-danger { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; flex: 1; justify-content: center; }
.btn-danger:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(239,68,68,.4); color: white; }
.btn-edit { background: linear-gradient(135deg,#10b981,#059669); color: white; flex: 1; justify-content: center; }
.btn-edit:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16,185,129,.4); color: white; }

/* ── Gallery grid ──────────────────────────────────── */
.gallery-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(280px,1fr)); gap: 20px; }
.gallery-item { background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 18px rgba(0,0,0,.07); transition: all .3s; position: relative; border: 1px solid #f1f5f9; }
.gallery-item:hover { transform: translateY(-4px); box-shadow: 0 12px 30px rgba(102,126,234,.15); }
.gallery-image { width: 100%; height: 200px; object-fit: cover; display: block; }
.gallery-item-status { position: absolute; top: 10px; right: 10px; padding: 5px 12px; border-radius: 20px; font-weight: 700; font-size: 11px; }
.gallery-item-status.active   { background: #10b981; color: white; }
.gallery-item-status.inactive { background: #ef4444; color: white; }
.gallery-content { padding: 16px 18px; }
.gallery-content h3 { font-size: 14px; font-weight: 700; color: #1e293b; margin: 0 0 5px; line-height: 1.4; }
.gallery-content p  { font-size: 12.5px; color: #64748b; margin: 0 0 10px; line-height: 1.6; }
.gallery-meta { display: flex; gap: 7px; flex-wrap: wrap; margin-bottom: 12px; }
.gallery-tag { display: inline-flex; align-items: center; gap: 5px; padding: 4px 10px; background: #eef2ff; border-radius: 20px; font-size: 11px; color: #667eea; font-weight: 700; }
.gallery-actions { display: flex; gap: 8px; padding-top: 12px; border-top: 1px solid #f1f5f9; }
.gallery-actions form { flex: 1; }

/* ── Alerts ────────────────────────────────────────── */
.alert { padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 600; font-size: 14px; animation: fadeIn .3s ease; }
@keyframes fadeIn { from{opacity:0;transform:translateY(-6px)}to{opacity:1;transform:none} }
.alert-success { background: linear-gradient(135deg,#f0fdf4,#ecfdf5); border: 1.5px solid #86efac; color: #166534; }
.alert-error   { background: linear-gradient(135deg,#fff1f2,#fff5f5); border: 1.5px solid #fca5a5; color: #991b1b; }

/* ── Count badge in section head ───────────────────── */
.count-badge { margin-left: auto; background: linear-gradient(135deg,#10b981,#059669); color: white; padding: 5px 14px; border-radius: 20px; font-size: 13px; font-weight: 700; }

/* ── Tip bar ───────────────────────────────────────── */
.tip-bar { background: linear-gradient(135deg,rgba(245,158,11,.08),rgba(251,191,36,.05)); border: 1px solid rgba(245,158,11,.25); border-left: 5px solid #f59e0b; border-radius: 12px; padding: 16px 20px; display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; }
.tip-bar .tip-icon { width: 36px; height: 36px; background: linear-gradient(135deg,#f59e0b,#d97706); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; flex-shrink: 0; margin-top: 2px; }
.tip-bar strong { color: #92400e; font-size: 13.5px; font-weight: 700; display: block; margin-bottom: 3px; }
.tip-list { list-style: none; padding: 0; margin: 6px 0 0; }
.tip-list li { font-size: 13px; color: #78350f; padding: 3px 0; display: flex; align-items: flex-start; gap: 7px; line-height: 1.5; }
.tip-list li::before { content: '\2713'; color: #d97706; font-weight: 800; flex-shrink: 0; }
.tip-list code { background: rgba(245,158,11,.15); color: #92400e; padding: 1px 6px; border-radius: 4px; font-size: 12px; font-weight: 600; }

/* ── Empty state ───────────────────────────────────── */
.empty-state { text-align: center; padding: 64px 20px; }
.empty-state i { font-size: 64px; color: #cbd5e1; margin-bottom: 16px; display: block; }
.empty-state h3 { color: #64748b; margin: 0 0 8px; font-size: 18px; }
.empty-state p  { color: #94a3b8; margin: 0; font-size: 14px; }

@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } .form-grid { grid-template-columns: 1fr; } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } .seo-hero { flex-direction: column; } .seo-section-body { padding: 20px 18px; } }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-images"></i> Gallery Management</h1>
        <p>Upload and manage project gallery images &mdash; displayed on the public Gallery page</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-camera" style="margin-right:6px;"></i>Photo Gallery</span>
</div>

<!-- Solution Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-images"></i></div>
    <div>
        <strong>Gallery Image Control</strong>
        <p>Upload JPG, PNG, or WebP images (auto-resized to 800&times;600 px). Assign categories, link to services, and toggle visibility. Changes go live on the public gallery immediately.</p>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-images"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Images</h3>
            <div class="value"><?php echo $total_images; ?></div>
            <p class="sub"><?php echo $total_images === 1 ? '1 image stored' : $total_images . ' images stored'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-eye"></i></div>
        <div class="stat-text-wrap">
            <h3>Active</h3>
            <div class="value"><?php echo $active_images; ?></div>
            <p class="sub"><?php echo $active_images === 1 ? 'visible on site' : 'visible on site'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap red"><i class="fas fa-eye-slash"></i></div>
        <div class="stat-text-wrap">
            <h3>Hidden</h3>
            <div class="value"><?php echo $inactive_images; ?></div>
            <p class="sub"><?php echo $inactive_images > 0 ? 'not shown on site' : 'all images visible'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-tags"></i></div>
        <div class="stat-text-wrap">
            <h3>Categories</h3>
            <div class="value"><?php echo $unique_cats; ?></div>
            <p class="sub"><?php echo $unique_cats === 1 ? 'category in use' : 'categories in use'; ?></p>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<!-- Section 1: Upload New Image -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-upload" style="color:#667eea;margin-right:8px;"></i>Upload New Image</h2>
    </div>
    <div class="seo-section-body">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload_image">
            <div class="form-grid">
                <div class="form-group">
                    <label>Image Title *</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g., Balcony Safety Net Installation" required>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select name="category" class="form-control">
                        <?php foreach ($categories as $key => $label): ?>
                            <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Alt Text (SEO)</label>
                    <input type="text" name="alt_text" class="form-control" placeholder="e.g., Safety net installation in Chennai">
                </div>
                <div class="form-group">
                    <label>Link to Service (Optional)</label>
                    <select name="service_id" class="form-control">
                        <option value="">None</option>
                        <?php
                        $services = $db->fetchAll("SELECT id, service_name FROM services WHERE is_active = 1");
                        foreach ($services as $service):
                        ?>
                            <option value="<?php echo $service['id']; ?>"><?php echo htmlspecialchars($service['service_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Display Order</label>
                    <input type="number" name="display_order" class="form-control" value="<?php echo $total_images + 1; ?>" min="1">
                </div>
                <div class="form-group full-width">
                    <label>Upload Image * &mdash; JPG, PNG, WebP &nbsp;&middot;&nbsp; Max 10MB &nbsp;&middot;&nbsp; Auto-resized to 800&times;600 px</label>
                    <div class="file-upload-wrapper">
                        <input type="file" name="gallery_image" id="gallery_image" class="file-upload-input" accept="image/*" required onchange="gPreview(this)">
                        <label for="gallery_image" class="file-upload-label" id="uploadLabel">
                            <div style="text-align:center;">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <div style="margin-top:12px;font-weight:700;color:#475569;">Click to upload or drag &amp; drop</div>
                                <div style="margin-top:6px;font-size:13px;color:#94a3b8;">JPG, PNG, WebP &nbsp;&middot;&nbsp; Max 10MB</div>
                            </div>
                        </label>
                    </div>
                    <div id="imgPreviewWrap" style="display:none;margin-top:14px;">
                        <img id="imgPreview" style="max-height:180px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.1);">
                        <div id="imgPreviewName" style="margin-top:8px;font-size:12px;color:#64748b;"></div>
                    </div>
                </div>
            </div>
            <div style="margin-top:24px;">
                <button type="submit" class="btn-action purple">
                    <i class="fas fa-cloud-upload-alt"></i> Upload to Gallery
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Section 2: Gallery Grid -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">2</div>
        <h2><i class="fas fa-th" style="color:#10b981;margin-right:8px;"></i>Gallery Images</h2>
        <span class="count-badge"><?php echo $total_images; ?> Image<?php echo $total_images !== 1 ? 's' : ''; ?></span>
    </div>
    <div class="seo-section-body">
    <?php if (empty($gallery_images)): ?>
        <div class="empty-state">
            <i class="fas fa-images"></i>
            <h3>No images yet</h3>
            <p>Upload your first gallery image using the form above</p>
        </div>
    <?php else: ?>
        <div class="gallery-grid">
            <?php foreach ($gallery_images as $image): ?>
            <div class="gallery-item">
                <div class="gallery-item-status <?php echo $image['is_active'] ? 'active' : 'inactive'; ?>">
                    <?php echo $image['is_active'] ? 'Active' : 'Inactive'; ?>
                </div>
                <?php if (!empty($image['image_url']) && file_exists('../../' . $image['image_url'])): ?>
                    <img src="<?php echo SITE_URL . '/' . htmlspecialchars($image['image_url']); ?>?v=<?php echo filemtime('../../' . $image['image_url']); ?>" alt="<?php echo htmlspecialchars($image['alt_text'] ?? $image['title']); ?>" class="gallery-image">
                <?php else: ?>
                    <div class="gallery-image" style="background:#f1f5f9;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-image" style="font-size:48px;color:#cbd5e1;"></i>
                    </div>
                <?php endif; ?>
                <div class="gallery-content">
                    <h3><?php echo htmlspecialchars($image['title']); ?></h3>
                    <?php if (!empty($image['alt_text'])): ?>
                        <p><?php echo htmlspecialchars(substr($image['alt_text'], 0, 80)); ?><?php echo strlen($image['alt_text']) > 80 ? '...' : ''; ?></p>
                    <?php endif; ?>
                    <div class="gallery-meta">
                        <span class="gallery-tag"><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($image['created_at'] ?? 'now')); ?></span>
                        <?php if (!empty($image['category'])): ?>
                        <span class="gallery-tag"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($categories[$image['category']] ?? $image['category']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="gallery-actions">
                        <?php $editData = json_encode(['id'=>(int)$image['id'],'title'=>$image['title']??'','alt_text'=>$image['alt_text']??'','category'=>$image['category']??'general','service_id'=>(int)($image['service_id']??0),'display_order'=>(int)($image['display_order']??99),'image_url'=>$image['image_url']??''],JSON_HEX_QUOT|JSON_HEX_TAG); ?>
                        <button type="button" class="btn btn-edit" onclick='openEditModal(<?php echo $editData; ?>)'>
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <form method="POST" style="flex:1;">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-<?php echo $image['is_active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                <?php echo $image['is_active'] ? 'Hide' : 'Show'; ?>
                            </button>
                        </form>
                        <form method="POST" style="flex:1;" id="delForm-<?php echo $image['id']; ?>">
                            <input type="hidden" name="action" value="delete_image">
                            <input type="hidden" name="image_id" value="<?php echo $image['id']; ?>">
                            <button type="button" class="btn btn-danger" onclick="confirmDelete(<?php echo $image['id']; ?>, '<?php echo addslashes(htmlspecialchars($image['title'])); ?>')"> 
                                <i class="fas fa-trash"></i> Delete
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

<!-- Tip bar -->
<div class="tip-bar">
    <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
    <div>
        <strong>Gallery Tips</strong>
        <ul class="tip-list">
            <li>Images are auto-resized to <code>800 &times; 600 px</code> on upload &mdash; original aspect ratio is NOT preserved (cropped)</li>
            <li>Use descriptive <strong>Alt Text</strong> for every image &mdash; this directly improves image SEO rankings</li>
            <li>Assign a <strong>Category</strong> to enable category filtering on the public gallery page</li>
            <li>Use <strong>Display Order</strong> (lower = first) to control image sequence</li>
            <li>Images are stored at <code>assets/img/gallery/</code></li>
        </ul>
    </div>
</div>

</div>

<!-- Edit Gallery Image Modal -->
<div id="editModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:9999;align-items:center;justify-content:center;padding:20px;">
    <div style="background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(0,0,0,.25);width:100%;max-width:640px;max-height:90vh;overflow-y:auto;">
        <div style="padding:22px 28px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:#fff;z-index:1;border-radius:18px 18px 0 0;">
            <h2 style="margin:0;font-size:20px;font-weight:800;color:#1e293b;"><i class="fas fa-edit" style="color:#10b981;margin-right:8px;"></i>Edit Gallery Image</h2>
            <button onclick="closeEditModal()" style="background:none;border:none;font-size:24px;cursor:pointer;color:#94a3b8;line-height:1;padding:0 4px;">&times;</button>
        </div>
        <div style="padding:24px 28px;">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_image">
                <input type="hidden" name="image_id" id="editImageId">

                <!-- Current image preview -->
                <div id="editCurrentImgWrap" style="margin-bottom:18px;text-align:center;">
                    <img id="editCurrentImg" src="" alt="Current image" style="max-height:160px;border-radius:10px;box-shadow:0 4px 16px rgba(0,0,0,.12);max-width:100%;">
                    <div style="margin-top:6px;font-size:12px;color:#94a3b8;">Current image</div>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Image Title *</label>
                        <input type="text" name="title" id="editTitle" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" id="editCategory" class="form-control">
                            <?php foreach ($categories as $eKey => $eLabel): ?>
                                <option value="<?php echo $eKey; ?>"><?php echo $eLabel; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Alt Text (SEO)</label>
                        <input type="text" name="alt_text" id="editAltText" class="form-control">
                    </div>
                    <div class="form-group">
                        <label>Link to Service (Optional)</label>
                        <select name="service_id" id="editServiceId" class="form-control">
                            <option value="">None</option>
                            <?php foreach (($services ?? []) as $svc): ?>
                                <option value="<?php echo $svc['id']; ?>"><?php echo htmlspecialchars($svc['service_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Display Order</label>
                        <input type="number" name="display_order" id="editDisplayOrder" class="form-control" min="1">
                    </div>
                    <div class="form-group full-width">
                        <label>Replace Image (Optional) &mdash; JPG, PNG, WebP &middot; Max 10MB</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="gallery_image" id="editGalleryImage" class="file-upload-input" accept="image/*" onchange="gPreviewEdit(this)">
                            <label for="editGalleryImage" class="file-upload-label" id="editUploadLabel" style="padding:24px 20px;">
                                <div style="text-align:center;">
                                    <i class="fas fa-cloud-upload-alt" style="font-size:28px;color:#667eea;"></i>
                                    <div style="margin-top:8px;font-weight:700;color:#475569;font-size:13px;">Click to replace image (optional)</div>
                                    <div style="margin-top:4px;font-size:12px;color:#94a3b8;">Leave empty to keep current image</div>
                                </div>
                            </label>
                        </div>
                        <div id="editImgPreviewWrap" style="display:none;margin-top:10px;">
                            <img id="editImgPreview" style="max-height:120px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,.1);">
                            <div id="editImgPreviewName" style="margin-top:6px;font-size:12px;color:#64748b;"></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:22px;display:flex;gap:10px;justify-content:flex-end;">
                    <button type="button" onclick="closeEditModal()" style="padding:11px 22px;border:1.5px solid #e2e8f0;border-radius:10px;background:#fff;color:#64748b;font-weight:700;font-size:14px;cursor:pointer;">Cancel</button>
                    <button type="submit" style="padding:11px 26px;border:none;border-radius:10px;background:linear-gradient(135deg,#10b981,#059669);color:#fff;font-weight:700;font-size:14px;cursor:pointer;box-shadow:0 4px 12px rgba(16,185,129,.3);"><i class="fas fa-save" style="margin-right:6px;"></i>Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function gPreview(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('imgPreview').src = e.target.result;
        document.getElementById('imgPreviewName').textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('imgPreviewWrap').style.display = 'block';
        document.getElementById('uploadLabel').innerHTML = '<div style="text-align:center;"><i class="fas fa-check-circle" style="font-size:36px;color:#10b981;"></i><div style="margin-top:10px;font-weight:700;color:#10b981;">File selected &mdash; ready to upload</div></div>';
    };
    reader.readAsDataURL(file);
}

function confirmDelete(id, title) {
    GCMAlert.confirm(
        'Are you sure you want to permanently delete <strong>' + title + '</strong>? This cannot be undone.',
        'Delete Image',
        function() { document.getElementById('delForm-' + id).submit(); }
    );
}

function openEditModal(data) {
    document.getElementById('editImageId').value       = data.id;
    document.getElementById('editTitle').value         = data.title;
    document.getElementById('editAltText').value       = data.alt_text;
    document.getElementById('editCategory').value      = data.category;
    document.getElementById('editServiceId').value     = data.service_id || '';
    document.getElementById('editDisplayOrder').value  = data.display_order;

    var imgWrap = document.getElementById('editCurrentImgWrap');
    var img     = document.getElementById('editCurrentImg');
    if (data.image_url) {
        img.src = '<?php echo SITE_URL; ?>/' + data.image_url;
        imgWrap.style.display = 'block';
    } else {
        imgWrap.style.display = 'none';
    }

    document.getElementById('editUploadLabel').innerHTML = '<div style="text-align:center;"><i class="fas fa-cloud-upload-alt" style="font-size:28px;color:#667eea;"></i><div style="margin-top:8px;font-weight:700;color:#475569;font-size:13px;">Click to replace image (optional)</div><div style="margin-top:4px;font-size:12px;color:#94a3b8;">Leave empty to keep current image</div></div>';
    document.getElementById('editImgPreviewWrap').style.display = 'none';
    document.getElementById('editGalleryImage').value = '';

    document.getElementById('editModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    document.body.style.overflow = '';
}

function gPreviewEdit(input) {
    const file = input.files[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = function(e) {
        document.getElementById('editImgPreview').src = e.target.result;
        document.getElementById('editImgPreviewName').textContent = file.name + ' (' + (file.size/1024).toFixed(1) + ' KB)';
        document.getElementById('editImgPreviewWrap').style.display = 'block';
        document.getElementById('editUploadLabel').innerHTML = '<div style="text-align:center;"><i class="fas fa-check-circle" style="font-size:28px;color:#10b981;"></i><div style="margin-top:8px;font-weight:700;color:#10b981;font-size:13px;">New image selected — will replace current</div></div>';
    };
    reader.readAsDataURL(file);
}

document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>

<?php
/**
 * Resize gallery image to 800x600px
 */
function resizeGalleryImage($filepath) {
    $target_width = 800;
    $target_height = 600;
    
    $image_info = getimagesize($filepath);
    if (!$image_info) return;
    
    list($width, $height, $type) = $image_info;
    
    // Create image resource
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            return;
    }
    
    // Create new image
    $new_image = imagecreatetruecolor($target_width, $target_height);
    
    // Preserve transparency
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    // Resize
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $target_width, $target_height, $width, $height);
    
    // Save as JPEG
    imagejpeg($new_image, $filepath, 90);
    
    imagedestroy($source);
    imagedestroy($new_image);
}

include '../includes/footer.php';
?>
