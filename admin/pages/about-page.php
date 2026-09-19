<?php
/**
 * About Page Content Management
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

$success = '';
$error = '';

// ── Auto-create table if missing ──────────────────────────────────────────
try {
    $db->execute(
        "CREATE TABLE IF NOT EXISTS about_page_content (
            id                  INT AUTO_INCREMENT PRIMARY KEY,
            company_description TEXT,
            years_of_experience INT DEFAULT 10,
            team_image          VARCHAR(500) DEFAULT NULL,
            created_at          DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at          DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
} catch (Exception $e) { /* table may already exist */ }

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_about'])) {
        $company_description = trim($_POST['company_description'] ?? '');
        $years_of_experience = (int)($_POST['years_of_experience'] ?? 10);
        $team_image          = $_POST['team_image_current'] ?? '';

        // Handle image upload
        if (isset($_FILES['team_image']) && $_FILES['team_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../../uploads/about/';
            if (!is_dir($upload_dir)) {
                @mkdir($upload_dir, 0755, true);
            }
            if (!is_dir($upload_dir)) {
                $error = 'Upload folder could not be created. Set uploads/about/ permissions to 755 in Hostinger File Manager.';
            } else {
                $file_extension = strtolower(pathinfo($_FILES['team_image']['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];

                if (!in_array($file_extension, $allowed_extensions)) {
                    $error = 'Only JPG, PNG, and WebP images are allowed.';
                } elseif ($_FILES['team_image']['size'] > 10 * 1024 * 1024) {
                    $error = 'Image must be under 10MB.';
                } else {
                    $new_filename = 'team_' . time() . '.' . $file_extension;
                    $upload_path  = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['team_image']['tmp_name'], $upload_path)) {
                        // Delete old image if exists
                        if (!empty($_POST['team_image_current'])) {
                            $old = '../../' . $_POST['team_image_current'];
                            if (file_exists($old)) @unlink($old);
                        }
                        $team_image = 'uploads/about/' . $new_filename;
                    } else {
                        $error = 'Failed to save image. Check folder write permissions on uploads/about/';
                    }
                }
            }
        } elseif (isset($_FILES['team_image']) && $_FILES['team_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $error = 'Upload error code ' . $_FILES['team_image']['error'] . '. Check PHP upload_max_filesize.';
        }

        if (!$error) {
            try {
                $check = $db->fetchOne("SELECT id FROM about_page_content LIMIT 1");
                if ($check) {
                    $db->execute(
                        "UPDATE about_page_content SET company_description=?, years_of_experience=?, team_image=?, updated_at=CURRENT_TIMESTAMP WHERE id=?",
                        [$company_description, $years_of_experience, $team_image, $check['id']]
                    );
                } else {
                    $db->execute(
                        "INSERT INTO about_page_content (company_description, years_of_experience, team_image) VALUES (?, ?, ?)",
                        [$company_description, $years_of_experience, $team_image]
                    );
                }
                $success = 'About page content updated successfully!';
            } catch (Exception $e) {
                $error = 'Database error: ' . $e->getMessage();
            }
        }
    }
}

// Fetch current content
try {
    $content = $db->fetchOne("SELECT * FROM about_page_content ORDER BY id DESC LIMIT 1");
} catch (Exception $e) {
    $content = null;
}
if (!$content) {
    $content = [
        'company_description' => 'GCM Netting Solutions has been serving Chennai for over a decade, providing top-quality safety net installation services for residential, commercial, and industrial properties...',
        'years_of_experience' => 10,
        'team_image' => ''
    ];
}

$page_title = 'About Page Management';
require_once '../includes/header.php';
?>

<style>
/* ── Page wrapper ───────────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero header card ───────────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; }

/* ── Solution banner ────────────────────────────────── */
.solution-banner { background: linear-gradient(135deg, rgba(102,126,234,.08) 0%, rgba(118,75,162,.05) 100%); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #3730a3; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #4338ca; font-size: 13.5px; margin: 0; line-height: 1.6; }

/* ── Stat cards ─────────────────────────────────────── */
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

/* ── Section card ───────────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sec-num.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

/* ── Form fields ────────────────────────────────────── */
.ap-label { display: flex; align-items: center; gap: 7px; font-size: 11px; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 8px; }
.ap-label i { color: #667eea; font-size: 12px; }
.ap-textarea, .ap-number { width: 100%; padding: 13px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 14px; color: #1e293b; background: #f8fafc; transition: border-color .25s, box-shadow .25s, background .25s; box-sizing: border-box; font-family: inherit; resize: vertical; }
.ap-textarea::placeholder, .ap-number::placeholder { color: #b0bec5; }
.ap-textarea:focus, .ap-number:focus { outline: none; border-color: #667eea; background: #fff; box-shadow: 0 0 0 3px rgba(102,126,234,.15); }
.ap-hint { margin-top: 7px; font-size: 12.5px; color: #94a3b8; display: flex; align-items: center; gap: 5px; }

/* ── Upload zone ─────────────────────────────────────── */
.ap-upload-zone { border: 2px dashed #c7d2fe; border-radius: 14px; padding: 28px 20px; text-align: center; background: linear-gradient(135deg,#f8faff,#f0f4ff); cursor: pointer; transition: border-color .25s, background .25s; position: relative; }
.ap-upload-zone:hover { border-color: #818cf8; background: linear-gradient(135deg,#f0f4ff,#ede9fe); }
.ap-upload-zone input[type="file"] { position: absolute; inset: 0; opacity: 0; cursor: pointer; width: 100%; height: 100%; }
.ap-upload-icon { font-size: 36px; color: #a5b4fc; margin-bottom: 10px; }
.ap-upload-text { font-size: 14px; font-weight: 700; color: #6366f1; margin: 0 0 4px; }
.ap-upload-sub  { font-size: 12px; color: #94a3b8; margin: 0; }

/* ── Image preview panels ──────────────────────────────── */
.ap-img-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 18px; }
.ap-img-panel { background: #f8fafc; border: 1px solid #e8eef8; border-radius: 14px; overflow: hidden; transition: box-shadow .25s; }
.ap-img-panel:hover { box-shadow: 0 4px 16px rgba(102,126,234,.15); }
.ap-img-panel-label { padding: 9px 14px; font-size: 11px; font-weight: 700; color: #667eea; text-transform: uppercase; letter-spacing: .4px; background: linear-gradient(135deg,#f0f4ff,#f8faff); border-bottom: 1px solid #e8eef8; display: flex; align-items: center; gap: 6px; }
.ap-img-panel img { width: 100%; height: 160px; object-fit: cover; display: block; }

/* ── Action buttons ──────────────────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 13px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 10px 30px rgba(102,126,234,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; box-shadow: none; transform: none; }

/* ── Alert ───────────────────────────────────────────── */
.ap-alert { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 600; animation: apFadeIn .3s ease; }
@keyframes apFadeIn { from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:none} }
.ap-alert-success { background: linear-gradient(135deg,#f0fdf4,#ecfdf5); border: 1.5px solid #86efac; color: #166534; }
.ap-alert-error   { background: linear-gradient(135deg,#fff1f2,#fff5f5); border: 1.5px solid #fca5a5; color: #991b1b; }

/* ── Tip bar ─────────────────────────────────────────── */
.tip-bar { background: linear-gradient(135deg,rgba(245,158,11,.08),rgba(251,191,36,.05)); border: 1px solid rgba(245,158,11,.25); border-left: 5px solid #f59e0b; border-radius: 12px; padding: 16px 20px; display: flex; align-items: flex-start; gap: 12px; margin-bottom: 24px; }
.tip-bar .tip-icon { width: 36px; height: 36px; background: linear-gradient(135deg,#f59e0b,#d97706); border-radius: 9px; display: flex; align-items: center; justify-content: center; color: white; font-size: 16px; flex-shrink: 0; margin-top: 2px; }
.tip-bar strong { color: #92400e; font-size: 13.5px; font-weight: 700; display: block; margin-bottom: 3px; }
.tip-bar p { margin: 0; color: #78350f; font-size: 13px; line-height: 1.6; }
.tip-list { list-style: none; padding: 0; margin: 6px 0 0; }
.tip-list li { font-size: 13px; color: #78350f; padding: 3px 0; display: flex; align-items: flex-start; gap: 7px; line-height: 1.5; }
.tip-list li::before { content: '✓'; color: #d97706; font-weight: 800; flex-shrink: 0; }
.tip-list code { background: rgba(245,158,11,.15); color: #92400e; padding: 1px 6px; border-radius: 4px; font-size: 12px; font-weight: 600; }

@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } .ap-img-panels { grid-template-columns: 1fr; } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } .seo-hero { flex-direction: column; } .seo-section-body { padding: 20px 18px; } .action-row { flex-direction: column; } }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-id-card"></i> About Page Management</h1>
        <p>Update company description, years of experience, and team photo &mdash; changes reflect live on the website</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-pencil-alt" style="margin-right:6px;"></i>Content Editor</span>
</div>

<!-- Solution Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-id-card"></i></div>
    <div>
        <strong>About Page Content Control</strong>
        <p>Edit the company description, years of experience counter, and team photo shown on the public About page. All changes are saved to the database and go live immediately.</p>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-align-left"></i></div>
        <div class="stat-text-wrap">
            <h3>Description</h3>
            <div class="value"><?php echo !empty($content['company_description']) ? '✓' : '✗'; ?></div>
            <p class="sub"><?php echo !empty($content['company_description']) ? number_format(str_word_count($content['company_description'])) . ' words' : 'Not set'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-trophy"></i></div>
        <div class="stat-text-wrap">
            <h3>Experience</h3>
            <div class="value"><?php echo (int)$content['years_of_experience']; ?></div>
            <p class="sub">years on website</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-image"></i></div>
        <div class="stat-text-wrap">
            <h3>Team Image</h3>
            <div class="value"><?php echo !empty($content['team_image']) ? '✓' : '✗'; ?></div>
            <p class="sub"><?php echo !empty($content['team_image']) ? 'Image uploaded' : 'Not uploaded'; ?></p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Status</h3>
            <div class="value"><?php echo (!empty($content['company_description']) && !empty($content['team_image'])) ? '✓' : '!'; ?></div>
            <p class="sub"><?php echo (!empty($content['company_description']) && !empty($content['team_image'])) ? 'Fully configured' : 'Needs attention'; ?></p>
        </div>
    </div>
</div>

<!-- Alerts -->
<?php if ($success): ?>
<div class="ap-alert ap-alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="ap-alert ap-alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<!-- Section 1: Company Description -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-align-left" style="color:#667eea;margin-right:8px;"></i>Company Description</h2>
    </div>
    <div class="seo-section-body">
        <div class="ap-label"><i class="fas fa-align-left"></i> Description Text</div>
        <textarea name="company_description" class="ap-textarea" rows="7" required><?php echo htmlspecialchars($content['company_description']); ?></textarea>
        <div class="ap-hint"><i class="fas fa-info-circle"></i> Main text shown on the About page &mdash; cover your company's history, mission, and values</div>
    </div>
</div>

<!-- Section 2: Years of Experience -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">2</div>
        <h2><i class="fas fa-trophy" style="color:#f59e0b;margin-right:8px;"></i>Years of Experience</h2>
    </div>
    <div class="seo-section-body">
        <div class="ap-label"><i class="fas fa-trophy"></i> Number of Years</div>
        <input type="number" name="years_of_experience" class="ap-number" value="<?php echo (int)$content['years_of_experience']; ?>" required min="1" max="100" style="max-width:160px;">
        <div class="ap-hint"><i class="fas fa-info-circle"></i> Displayed as <strong>&ldquo;<?php echo (int)$content['years_of_experience']; ?>+ Years of Excellence&rdquo;</strong> on the About page</div>
    </div>
</div>

<!-- Section 3: Team Image -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num teal">3</div>
        <h2><i class="fas fa-image" style="color:#06b6d4;margin-right:8px;"></i>Team / Company Image</h2>
    </div>
    <div class="seo-section-body">
        <div class="ap-upload-zone">
            <input type="file" name="team_image" accept="image/*" onchange="apPreview(event)">
            <div class="ap-upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
            <p class="ap-upload-text">Click or drag &amp; drop to upload</p>
            <p class="ap-upload-sub">JPG, PNG, WebP &nbsp;&middot;&nbsp; Max 10MB &nbsp;&middot;&nbsp; Recommended 600&times;400 px</p>
        </div>
        <input type="hidden" name="team_image_current" value="<?php echo htmlspecialchars($content['team_image']); ?>">
        <div class="ap-img-panels">
            <?php if (!empty($content['team_image'])): ?>
            <div class="ap-img-panel" id="currentPanel">
                <div class="ap-img-panel-label"><i class="fas fa-check-circle"></i> Current Image</div>
                <img src="<?php echo SITE_URL . '/' . htmlspecialchars($content['team_image']); ?>?v=<?php echo time(); ?>" id="currentImage" alt="Current team image">
            </div>
            <?php endif; ?>
            <div class="ap-img-panel" id="newPanel" style="display:none;">
                <div class="ap-img-panel-label"><i class="fas fa-eye"></i> New Preview</div>
                <img src="" id="imagePreview" alt="New image preview">
            </div>
        </div>
    </div>
</div>

<!-- Section 4: Save Actions -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">4</div>
        <h2><i class="fas fa-save" style="color:#10b981;margin-right:8px;"></i>Save Changes</h2>
    </div>
    <div class="seo-section-body">
        <div class="action-row">
            <button type="submit" name="update_about" class="btn-action purple">
                <i class="fas fa-save"></i> Update About Page
            </button>
            <a href="../dashboard.php" class="btn-action gray">
                <i class="fas fa-times"></i> Cancel
            </a>
        </div>
    </div>
</div>

</form>

<!-- Quick Guide tip bar -->
<div class="tip-bar">
    <div class="tip-icon"><i class="fas fa-lightbulb"></i></div>
    <div>
        <strong>Quick Guide</strong>
        <ul class="tip-list">
            <li><strong>Company Description</strong> &mdash; Main text covering your company history, mission, and values</li>
            <li><strong>Years of Experience</strong> &mdash; Shown with a &ldquo;+&rdquo; suffix (e.g. <code>10+ Years of Excellence</code>)</li>
            <li><strong>Team Image</strong> &mdash; Professional photo of your team or facility &mdash; auto-saved to <code>/uploads/about/</code></li>
            <li>Recommended image size: <code>600 &times; 400 px</code></li>
        </ul>
    </div>
</div>

</div>

<script>
function apPreview(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').src = e.target.result;
            document.getElementById('newPanel').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
