<?php
/**
 * Service Images Management
 * Upload images for each service card
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
$page_title = 'Service Images';

// Handle image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'upload_service_image' && isset($_FILES['service_image'])) {
        $service_id = $_POST['service_id'] ?? 0;
        
        $upload_dir = '../../assets/img/services/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file = $_FILES['service_image'];
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $_SESSION['error_message'] = 'Only JPG, PNG, and WebP images are allowed';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $_SESSION['error_message'] = 'Image size must be less than 5MB';
        } else {
            $service = $db->fetchOne("SELECT service_slug FROM services WHERE id = ?", [$service_id], 'i');
            
            if ($service) {
                $filename = $service['service_slug'] . '.jpg';
                $filepath = $upload_dir . $filename;
                
                // Delete old image if exists
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Resize to 600x400px
                    resizeServiceImage($filepath);
                    
                    $relative_path = 'assets/img/services/' . $filename;
                    
                    // Check if service image record exists
                    $existing = $db->fetchOne("SELECT id FROM service_images WHERE service_id = ? AND image_type = 'card'", [$service_id], 'i');
                    
                    if ($existing) {
                        $db->execute(
                            "UPDATE service_images SET image_path = ? WHERE id = ?",
                            [$relative_path, $existing['id']]
                        );
                    } else {
                        $db->execute(
                            "INSERT INTO service_images (service_id, image_path, image_type) VALUES (?, ?, 'card')",
                            [$service_id, $relative_path]
                        );
                    }
                    
                    $_SESSION['success_message'] = 'Service image uploaded successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to upload image';
                }
            }
        }
        
        header('Location: ?page=service-images');
        exit;
    }
}

// Fetch all services
$services = $db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY display_order ASC");

include '../includes/header.php';
?>

<style>
.service-images-page {
    padding: 30px;
    animation: fadeIn 0.5s ease;
}

.page-header {
    color: #1e293b !important;
    padding: 40px;
    border-radius: 16px;
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 700;
    color: #1e293b !important;
}

.page-header p {
    color: #64748b !important;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 24px;
}

.service-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: all 0.3s ease;
}

.service-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.service-image-preview {
    width: 100%;
    height: 250px;
    object-fit: cover;
    display: block;
    background: #F1F5F9;
}

.no-image-placeholder {
    width: 100%;
    height: 250px;
    background: linear-gradient(135deg, #F1F5F9 0%, #E2E8F0 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-direction: column;
    color: #94A3B8;
}

.no-image-placeholder i {
    font-size: 64px;
    margin-bottom: 16px;
}

.service-content {
    padding: 24px;
}

.service-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.service-icon {
    width: 50px;
    height: 50px;
    border-radius: 10px;
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
}

.service-title {
    flex: 1;
}

.service-title h3 {
    margin: 0;
    font-size: 18px;
    color: #1E293B;
}

.service-title p {
    margin: 4px 0 0 0;
    font-size: 13px;
    color: #64748B;
}

.upload-form {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #F1F5F9;
}

.file-upload-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
    margin-bottom: 16px;
}

.file-upload-input {
    position: absolute;
    left: -9999px;
}

.file-upload-label {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 30px;
    border: 2px dashed #CBD5E1;
    border-radius: 10px;
    background: #F8FAFC;
    cursor: pointer;
    transition: all 0.3s ease;
}

.file-upload-label:hover {
    border-color: #10B981;
    background: #ECFDF5;
}

.file-upload-label i {
    font-size: 28px;
    color: #10B981;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    width: 100%;
    justify-content: center;
}

.btn-primary {
    background: linear-gradient(135deg, #10B981 0%, #059669 100%);
    color: white;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
}

.image-specs {
    background: #DBEAFE;
    border: 1px solid #93C5FD;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 16px;
    font-size: 13px;
    color: #1E40AF;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="service-images-page">
    <div class="page-header">
        <h1><i class="fas fa-images"></i> Service Images Management</h1>
        <p>Upload images for each service. Images will be displayed on homepage service cards.</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
        </div>
    <?php endif; ?>

    <div class="services-grid">
        <?php foreach ($services as $service): ?>
            <?php
            $image_path = 'assets/img/services/' . $service['service_slug'] . '.jpg';
            $image_exists = file_exists('../../' . $image_path);
            ?>
            <div class="service-card">
                <?php if ($image_exists): ?>
                    <img src="../../<?php echo $image_path; ?>?v=<?php echo time(); ?>" 
                         alt="<?php echo htmlspecialchars($service['service_name']); ?>" 
                         class="service-image-preview">
                <?php else: ?>
                    <div class="no-image-placeholder">
                        <i class="fas fa-image"></i>
                        <span>No Image Uploaded</span>
                    </div>
                <?php endif; ?>
                
                <div class="service-content">
                    <div class="service-header">
                        <div class="service-icon">
                            <i class="<?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                        </div>
                        <div class="service-title">
                            <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                            <p><?php echo htmlspecialchars($service['service_slug']); ?>.jpg</p>
                        </div>
                    </div>

                    <div class="image-specs">
                        <i class="fas fa-info-circle"></i>
                        <strong>Required:</strong> 600x400px (3:2 ratio) | Max 5MB
                    </div>

                    <form method="POST" enctype="multipart/form-data" class="upload-form">
                        <input type="hidden" name="action" value="upload_service_image">
                        <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                        
                        <div class="file-upload-wrapper">
                            <input type="file" name="service_image" id="file_<?php echo $service['id']; ?>" 
                                   class="file-upload-input" accept="image/*" required>
                            <label for="file_<?php echo $service['id']; ?>" class="file-upload-label">
                                <div style="text-align: center;">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <div style="margin-top: 8px; font-weight: 600; color: #475569;">
                                        <?php echo $image_exists ? 'Replace Image' : 'Upload Image'; ?>
                                    </div>
                                    <div style="margin-top: 4px; font-size: 12px; color: #94A3B8;">
                                        Click or drag & drop
                                    </div>
                                </div>
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> 
                            <?php echo $image_exists ? 'Update Image' : 'Upload Image'; ?>
                        </button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php
/**
 * Resize service image to 600x400px
 */
function resizeServiceImage($filepath) {
    $target_width = 600;
    $target_height = 400;
    
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
