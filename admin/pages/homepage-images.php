<?php
/**
 * Homepage Image Management
 * Upload and manage all homepage section images
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
$page_title = 'Homepage Images';

// Auto-create table if missing
try {
    $db->execute(
        "CREATE TABLE IF NOT EXISTS homepage_images (
            id            INT AUTO_INCREMENT PRIMARY KEY,
            section_name  VARCHAR(100) NOT NULL UNIQUE,
            image_path    VARCHAR(500) DEFAULT NULL,
            image_title   VARCHAR(255) DEFAULT NULL,
            image_alt     VARCHAR(255) DEFAULT NULL,
            is_active     TINYINT(1) NOT NULL DEFAULT 1,
            display_order INT NOT NULL DEFAULT 0,
            created_at    DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at    DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) { /* table already exists */ }

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'upload_section_image' && isset($_FILES['image'])) {
        $section_name = $_POST['section_name'] ?? '';
        $image_title = $_POST['image_title'] ?? '';
        $image_alt = $_POST['image_alt'] ?? '';
        
        $upload_dir = '../../assets/img/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $file = $_FILES['image'];
        
        // Validate image
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error_message'] = 'Only JPG, PNG, and WebP images are allowed';
        } elseif ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
            $_SESSION['error_message'] = 'Image size must be less than 5MB';
        } else {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $section_name . '-' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Resize image to proper dimensions
                resizeImage($filepath, $section_name);
                
                $relative_path = 'assets/img/' . $filename;
                
                // Update or insert database
                $existing = $db->fetchOne("SELECT id FROM homepage_images WHERE section_name = ?", [$section_name], 's');
                
                if ($existing) {
                    $db->execute(
                        "UPDATE homepage_images SET image_path = ?, image_title = ?, image_alt = ? WHERE section_name = ?",
                        [$relative_path, $image_title, $image_alt, $section_name]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO homepage_images (section_name, image_path, image_title, image_alt) VALUES (?, ?, ?, ?)",
                        [$section_name, $relative_path, $image_title, $image_alt]
                    );
                }
                
                $_SESSION['success_message'] = 'Image uploaded successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to upload image';
            }
        }
        
        header('Location: ?page=homepage-images');
        exit;
    }
}

// Fetch all homepage images
$homepage_images = $db->fetchAll("SELECT * FROM homepage_images ORDER BY display_order ASC");

// Image sections configuration
$image_sections = [
    'about_section' => [
        'title' => 'About Section Image',
        'description' => 'Main image for "About GCM Netting Solutions" section',
        'recommended_size' => '800x600px',
        'aspect_ratio' => '4:3'
    ],
    'services_banner' => [
        'title' => 'Services Section Banner',
        'description' => 'Background image for services section header',
        'recommended_size' => '1920x400px',
        'aspect_ratio' => '16:4'
    ],
    'why_choose_bg' => [
        'title' => 'Why Choose Us Background',
        'description' => 'Background image for highlights section',
        'recommended_size' => '1920x800px',
        'aspect_ratio' => '16:7'
    ]
];

include '../includes/header.php';
?>

<style>
/* ═══ Homepage Images — Complete SEO System Theme ═══ */
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
@keyframes float{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}

.seo-page{padding:0;}
.seo-hero{background:white;border-radius:18px;box-shadow:0 4px 24px rgba(0,0,0,.07);padding:32px 36px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:20px;border-left:6px solid transparent;border-image:linear-gradient(180deg,#8b5cf6,#3b82f6) 1;}
.seo-hero-left h1{font-size:32px;font-weight:800;color:#1e293b;margin:0 0 6px;}
.seo-hero-left h1 i{color:#8b5cf6;margin-right:10px;}
.seo-hero-left p{color:#64748b;font-size:15px;margin:0;}
.hero-actions{display:flex;gap:12px;flex-shrink:0;flex-wrap:wrap;}

.hi-notice{display:flex;align-items:center;gap:14px;border-radius:14px;border:1.5px solid;padding:14px 20px;margin-bottom:18px;font-size:14px;}
.hi-notice.success{background:#f0fdf4;border-color:#86efac;color:#065f46;}
.hi-notice.error{background:#fef2f2;border-color:#fca5a5;color:#991b1b;}
.hi-notice.info{background:#fffbeb;border-color:#fde68a;color:#92400e;}
.hi-notice-icon{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;color:white;font-size:16px;flex-shrink:0;}

.seo-section{background:white;border-radius:18px;box-shadow:0 4px 20px rgba(0,0,0,.07);margin-bottom:24px;overflow:hidden;}
.seo-section-head{padding:22px 32px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;gap:12px;}
.sec-num{width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:16px;font-weight:800;color:white;flex-shrink:0;}
.sec-num.purple{background:linear-gradient(135deg,#8b5cf6,#7c3aed);}
.seo-section-head h2{font-size:20px;font-weight:700;color:#1e293b;margin:0;}
.seo-section-body{padding:28px 32px;}
.action-row-bottom{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:32px;}

.img-cards-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(440px,1fr));gap:24px;}
.img-card{background:#fafbfc;border:2px solid #e2e8f0;border-radius:16px;padding:26px;transition:all .25s;}
.img-card:hover{border-color:#c4b5fd;box-shadow:0 8px 28px rgba(139,92,246,.1);transform:translateY(-3px);}
.img-card-head{display:flex;align-items:center;gap:14px;margin-bottom:18px;padding-bottom:18px;border-bottom:1px solid #f1f5f9;}
.img-card-icon{width:54px;height:54px;border-radius:12px;background:linear-gradient(135deg,#8b5cf6,#7c3aed);display:flex;align-items:center;justify-content:center;color:white;font-size:22px;flex-shrink:0;}
.img-card-info h3{margin:0 0 4px;font-size:18px;font-weight:700;color:#1e293b;}
.img-card-info p{margin:0;font-size:13px;color:#64748b;}

.current-image{margin:0 0 16px;border-radius:12px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,.1);position:relative;}
.current-image img{width:100%;height:220px;object-fit:cover;display:block;}
.image-overlay{position:absolute;inset:0;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .3s;}
.current-image:hover .image-overlay{opacity:1;}
.image-info-badge{background:white;color:#1e293b;padding:8px 18px;border-radius:8px;font-size:13px;font-weight:700;}

.rec-size{background:#fffbeb;border:1px solid #fde68a;padding:10px 14px;border-radius:8px;margin-bottom:14px;font-size:12px;color:#92400e;display:flex;align-items:center;gap:8px;}

.upload-form .form-group{margin-bottom:14px;}
.upload-form label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:6px;}
.form-control{width:100%;padding:10px 14px;border:2px solid #e2e8f0;border-radius:8px;font-size:14px;color:#1e293b;outline:none;transition:border-color .2s;box-sizing:border-box;}
.form-control:focus{border-color:#8b5cf6;}

.file-upload-wrapper{position:relative;display:block;width:100%;}
.file-upload-input{position:absolute;left:-9999px;}
.file-upload-label{display:flex;flex-direction:column;align-items:center;justify-content:center;gap:8px;padding:32px 20px;border:3px dashed #c4b5fd;border-radius:12px;background:#faf5ff;cursor:pointer;transition:all .25s;text-align:center;}
.file-upload-label:hover{border-color:#8b5cf6;background:#f5f3ff;}
.file-upload-label i{font-size:30px;color:#8b5cf6;}
.file-upload-text{font-weight:700;color:#4c1d95;font-size:14px;}
.file-upload-hint{font-size:12px;color:#94a3b8;}

.btn-hi{display:inline-flex;align-items:center;gap:8px;padding:11px 22px;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;text-decoration:none;transition:all .25s;white-space:nowrap;}
.btn-hi:hover{transform:translateY(-2px);text-decoration:none;}
.btn-hi.purple{background:linear-gradient(135deg,#8b5cf6,#7c3aed);color:white;box-shadow:0 4px 14px rgba(139,92,246,.35);}
.btn-hi.purple:hover{box-shadow:0 8px 24px rgba(139,92,246,.45);}
.btn-hi.gray{background:#f1f5f9;color:#475569;border:1px solid #e2e8f0;}
.btn-hi.gray:hover{background:#e2e8f0;transform:none;}
.btn-hi.sm{padding:9px 16px;font-size:13px;}

@media(max-width:768px){.img-cards-grid{grid-template-columns:1fr;}}
</style>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-images"></i> Homepage Images</h1>
        <p>Upload and manage images for homepage sections &mdash; auto-resized to perfect dimensions</p>
    </div>
    <div class="hero-actions">
        <a href="../dashboard.php" class="btn-hi gray sm"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
<div class="hi-notice success"><div class="hi-notice-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-check-circle"></i></div><span><?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
<div class="hi-notice error"><div class="hi-notice-icon" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-exclamation-circle"></i></div><span><?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<div class="hi-notice info">
    <div class="hi-notice-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-info-circle"></i></div>
    <span>Upload images for each homepage section below. Images are automatically resized to the recommended dimensions on upload.</span>
</div>

<!-- Section: Image Uploads -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-images" style="color:#8b5cf6;margin-right:8px;"></i>Homepage Section Images</h2>
    </div>
    <div class="seo-section-body">
        <div class="img-cards-grid">
            <?php foreach ($image_sections as $section_key => $section_config):
                $current_image = null;
                foreach ($homepage_images as $img) {
                    if ($img['section_name'] === $section_key) { $current_image = $img; break; }
                }
            ?>
            <div class="img-card">
                <div class="img-card-head">
                    <div class="img-card-icon"><i class="fas fa-image"></i></div>
                    <div class="img-card-info">
                        <h3><?php echo $section_config['title']; ?></h3>
                        <p><?php echo $section_config['description']; ?></p>
                    </div>
                </div>

                <?php if ($current_image && file_exists('../../' . $current_image['image_path'])): ?>
                <div class="current-image">
                    <img src="../../<?php echo htmlspecialchars($current_image['image_path']); ?>" alt="<?php echo htmlspecialchars($current_image['image_alt']); ?>">
                    <div class="image-overlay"><div class="image-info-badge">Current Image</div></div>
                </div>
                <?php endif; ?>

                <div class="rec-size">
                    <i class="fas fa-ruler-combined"></i>
                    <span><strong>Recommended:</strong> <?php echo $section_config['recommended_size']; ?> &nbsp;&bull;&nbsp; Ratio: <?php echo $section_config['aspect_ratio']; ?></span>
                </div>

                <form method="POST" enctype="multipart/form-data" class="upload-form">
                    <input type="hidden" name="action" value="upload_section_image">
                    <input type="hidden" name="section_name" value="<?php echo $section_key; ?>">
                    <div class="form-group">
                        <label>Image Title</label>
                        <input type="text" name="image_title" class="form-control" value="<?php echo htmlspecialchars($current_image['image_title'] ?? $section_config['title']); ?>" placeholder="Enter image title">
                    </div>
                    <div class="form-group">
                        <label>Image Alt Text <span style="color:#64748b;font-weight:400;">(SEO)</span></label>
                        <input type="text" name="image_alt" class="form-control" value="<?php echo htmlspecialchars($current_image['image_alt'] ?? ''); ?>" placeholder="Describe the image for SEO">
                    </div>
                    <div class="form-group">
                        <label>Upload Image</label>
                        <div class="file-upload-wrapper">
                            <input type="file" name="image" id="file_<?php echo $section_key; ?>" class="file-upload-input" accept="image/*" required>
                            <label for="file_<?php echo $section_key; ?>" class="file-upload-label">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span class="file-upload-text">Click to upload or drag &amp; drop</span>
                                <span class="file-upload-hint">JPG, PNG or WebP &mdash; Max 5MB</span>
                            </label>
                        </div>
                    </div>
                    <button type="submit" class="btn-hi purple"><i class="fas fa-upload"></i> Upload Image</button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Bottom nav -->
<div class="action-row-bottom">
    <a href="../dashboard.php" class="btn-hi gray"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
</div>

</div>

<?php
/**
 * Resize image based on section requirements
 */
function resizeImage($filepath, $section_name) {
    $size_map = [
        'about_section' => [800, 600],
        'services_banner' => [1920, 400],
        'why_choose_bg' => [1920, 800]
    ];
    
    if (!isset($size_map[$section_name])) {
        return;
    }
    
    list($target_width, $target_height) = $size_map[$section_name];
    
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
    
    // Preserve transparency for PNG
    if ($type == IMAGETYPE_PNG) {
        imagealphablending($new_image, false);
        imagesavealpha($new_image, true);
    }
    
    // Resize
    imagecopyresampled($new_image, $source, 0, 0, 0, 0, $target_width, $target_height, $width, $height);
    
    // Save
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($new_image, $filepath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($new_image, $filepath, 9);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($new_image, $filepath, 90);
            break;
    }
    
    imagedestroy($source);
    imagedestroy($new_image);
}

include '../includes/footer.php';
?>
