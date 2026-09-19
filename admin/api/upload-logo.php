<?php
/**
 * Upload Logo API
 * Handle logo/favicon file uploads with optional GD resizing
 */

ob_start(); // buffer any stray output so JSON is never corrupted

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();

header('Content-Type: application/json');

function jsonOut(array $data): void {
    ob_clean();
    echo json_encode($data);
    exit;
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    jsonOut(['success' => false, 'message' => 'Unauthorized']);
}

if (!isset($_FILES['logo']) || !isset($_POST['type'])) {
    jsonOut(['success' => false, 'message' => 'Missing file or type parameter']);
}

$type = $_POST['type']; // 'header', 'footer', or 'favicon'
$file = $_FILES['logo'];

// PHP upload error check
if ($file['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize in php.ini',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE in form',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
    ];
    jsonOut(['success' => false, 'message' => $upload_errors[$file['error']] ?? 'Upload error code ' . $file['error']]);
}

// Determine MIME type (allow PNG, JPG, WebP)
$allowed_mimes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp'];
$mime_type = 'image/png';
if (function_exists('finfo_open')) {
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
} elseif (function_exists('mime_content_type')) {
    $mime_type = mime_content_type($file['tmp_name']);
}
if (!in_array($mime_type, $allowed_mimes)) {
    jsonOut(['success' => false, 'message' => 'Only PNG, JPG, or WebP images are allowed (got: ' . $mime_type . ')']);
}

// File size check (5MB max — logos are usually tiny)
if ($file['size'] > 5 * 1024 * 1024) {
    jsonOut(['success' => false, 'message' => 'File size must be less than 5MB']);
}

try {
    $upload_dir = dirname(dirname(dirname(__FILE__))) . '/uploads/';
    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0755, true);
    if (!is_dir($upload_dir)) {
        jsonOut(['success' => false, 'message' => 'Uploads folder missing. Set /uploads/ permissions to 755 in Hostinger File Manager.']);
    }

    // Always save as .png so header/footer references keep working
    if ($type === 'header')  $filename = 'logo.png';
    elseif ($type === 'footer') $filename = 'logo-footer.png';
    else                        $filename = 'favicon.png';

    $destination = $upload_dir . $filename;

    // ── Try GD resize first; fall back to plain copy ──────────────────────────
    $gd_ok = false;
    if (function_exists('imagecreatefrompng')) {
        // Load source regardless of format
        $src = null;
        switch ($mime_type) {
            case 'image/png':  $src = @imagecreatefrompng($file['tmp_name']); break;
            case 'image/jpeg':
            case 'image/jpg':  $src = @imagecreatefromjpeg($file['tmp_name']); break;
            case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : null; break;
        }

        if ($src) {
            $ow = imagesx($src);
            $oh = imagesy($src);

            // Max dims per type
            $max_w = ($type === 'favicon') ? 512 : 400;
            $max_h = ($type === 'favicon') ? 512 : 120;

            $ratio = min($max_w / $ow, $max_h / $oh);
            if ($ratio < 1) {
                $nw = (int)($ow * $ratio);
                $nh = (int)($oh * $ratio);
            } else {
                $nw = $ow;
                $nh = $oh;
            }

            $out = imagecreatetruecolor($nw, $nh);
            imagealphablending($out, false);
            imagesavealpha($out, true);
            $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
            imagefill($out, 0, 0, $transparent);
            imagecopyresampled($out, $src, 0, 0, 0, 0, $nw, $nh, $ow, $oh);
            $gd_ok = imagepng($out, $destination, 6); // compression 6 = good balance
            imagedestroy($src);
            imagedestroy($out);
        }
    }

    if (!$gd_ok) {
        // GD unavailable or failed — just copy the raw file
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            jsonOut(['success' => false, 'message' => 'Failed to save file. Check /uploads/ folder write permissions.']);
        }
    }

    @chmod($destination, 0644);

    jsonOut([
        'success'  => true,
        'message'  => 'Logo uploaded successfully',
        'filename' => $filename,
        'path'     => '/uploads/' . $filename
    ]);

} catch (Exception $e) {
    error_log('[upload-logo] ' . $e->getMessage());
    jsonOut(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
