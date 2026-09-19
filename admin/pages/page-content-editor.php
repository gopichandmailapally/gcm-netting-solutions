<?php
/**
 * Page Content Editor
 * Edit Privacy Policy and Terms & Conditions
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$success = '';
$error = '';

// Initialize pages if not exists
$pages = ['privacy-policy', 'terms-conditions'];
foreach ($pages as $slug) {
    $exists = $db->fetchOne("SELECT id FROM page_content WHERE page_slug = ?", [$slug], 's');
    if (!$exists) {
        $title = $slug === 'privacy-policy' ? 'Privacy Policy' : 'Terms & Conditions';
        $db->execute(
            "INSERT INTO page_content (page_slug, page_title, content) VALUES (?, ?, ?)",
            [$slug, $title, 'Content coming soon...'],
            'sss'
        );
    }
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['import_existing'])) {
        // Import existing static content
        require_once 'import-existing-content.php';
        header('Location: page-content-editor.php?imported=1');
        exit;
    }
    
    if (isset($_POST['update_content'])) {
        $slug = $_POST['page_slug'];
        $title = $_POST['page_title'];
        $content = $_POST['content'];
        $meta_description = $_POST['meta_description'];
        
        $db->execute(
            "UPDATE page_content SET page_title = ?, content = ?, meta_description = ?, last_updated_by = ? WHERE page_slug = ?",
            [$title, $content, $meta_description, $_SESSION['admin_id'], $slug],
            'sssss'
        );
        
        $success = 'Page content updated successfully!';
    }
}

// Check if import was successful
if (isset($_GET['imported'])) {
    $success = 'Existing content imported successfully! You can now edit it below.';
}

// Fetch page contents
$privacy = $db->fetchOne("SELECT * FROM page_content WHERE page_slug = 'privacy-policy'");
$terms = $db->fetchOne("SELECT * FROM page_content WHERE page_slug = 'terms-conditions'");

$page_title = 'Page Content Editor';
require_once '../includes/header.php';
?>

<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
/* Modern Page Content Editor Styling */
.content-wrapper {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
}

.page-header-section {
    background: white;
    padding: 25px 30px;
    border-radius: 16px;
    margin-bottom: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    position: relative;
    z-index: 15;
}

.page-header-section h2 {
    color: #667eea;
    font-weight: 700;
    margin-bottom: 8px;
}

.page-header-section .text-muted {
    color: #64748B !important;
    font-size: 15px;
}

.import-btn {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    border: none;
    padding: 12px 24px;
    border-radius: 12px;
    color: white;
    font-weight: 600;
    box-shadow: 0 4px 15px rgba(245, 87, 108, 0.3);
    transition: all 0.3s ease;
}

.import-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 87, 108, 0.4);
}

.alert-success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    border: none;
    color: white;
    border-radius: 12px;
    padding: 16px 20px;
    font-weight: 500;
    box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
    margin-bottom: 20px;
    position: relative;
    z-index: 10;
}

.nav-tabs {
    background: white;
    padding: 15px 15px 0 15px;
    border-radius: 16px 16px 0 0;
    border: none;
    box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    margin-top: 0;
    position: relative;
    z-index: 5;
    display: flex;
    flex-direction: row;
    list-style: none;
}

.nav-tabs .nav-link {
    border: none;
    color: #64748B;
    font-weight: 600;
    padding: 14px 28px;
    border-radius: 12px 12px 0 0;
    transition: all 0.3s ease;
    margin-right: 8px;
    background: #F1F5F9;
    position: relative;
    z-index: 8;
    text-decoration: none;
    display: inline-block;
}

.nav-tabs .nav-link:hover {
    background: #E2E8F0;
    color: #667eea;
    text-decoration: none;
}

.nav-tabs .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    z-index: 9;
}

.nav-tabs .nav-link i {
    margin-right: 8px;
}

.nav-tabs .nav-item {
    margin-bottom: 0;
    list-style: none;
    list-style-type: none;
    display: inline-block;
}

.nav-tabs .nav-item::before {
    content: none !important;
}

.tab-content {
    background: white;
    padding: 35px;
    border-radius: 0 0 16px 16px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.08);
    position: relative;
    z-index: 3;
    min-height: 600px;
}

.card {
    border: none;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    margin-bottom: 0;
}

.card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 20px 25px;
}

.card-header h5 {
    margin: 0;
    font-weight: 700;
    font-size: 18px;
}

.card-body {
    padding: 30px;
    position: relative;
}

.form-label {
    font-weight: 600;
    color: #1E293B;
    margin-bottom: 10px;
    font-size: 15px;
}

.form-control {
    border: 2px solid #E2E8F0;
    border-radius: 10px;
    padding: 12px 16px;
    font-size: 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* Quill Editor Styling */
.ql-container {
    border: 2px solid #E2E8F0 !important;
    border-radius: 0 0 12px 12px !important;
    font-size: 16px;
    background: white;
}

.ql-toolbar {
    border: 2px solid #E2E8F0 !important;
    border-radius: 12px 12px 0 0 !important;
    background: #F8FAFC;
    padding: 12px !important;
}

.ql-editor {
    min-height: 450px;
    font-size: 16px;
    line-height: 1.8;
    padding: 25px;
}

.ql-editor:focus {
    outline: none;
}

/* Toolbar buttons */
.ql-toolbar button {
    border-radius: 6px !important;
    transition: all 0.2s ease;
}

.ql-toolbar button:hover {
    background: #667eea !important;
    color: white !important;
}

.ql-toolbar button.ql-active {
    background: #667eea !important;
    color: white !important;
}

.ql-stroke {
    stroke: #64748B !important;
}

.ql-toolbar button:hover .ql-stroke {
    stroke: white !important;
}

/* Save Buttons */
.btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    border: none;
    padding: 14px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    box-shadow: 0 4px 15px rgba(56, 239, 125, 0.3);
    transition: all 0.3s ease;
    margin-bottom: 20px;
    position: relative;
    z-index: 5;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(56, 239, 125, 0.4);
}

.btn-secondary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    padding: 14px 32px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 16px;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    transition: all 0.3s ease;
    margin-bottom: 20px;
    position: relative;
    z-index: 5;
}

.btn-secondary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.d-flex.justify-content-between {
    margin-bottom: 30px;
}

/* Info Alert */
.alert-info {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    border: none;
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(250, 112, 154, 0.2);
    margin-top: 30px;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
    clear: both;
}

.alert-info h5 {
    color: #7c2d12;
    font-weight: 700;
    margin-bottom: 15px;
}

.alert-info ul {
    color: #7c2d12;
    margin: 0;
    padding-left: 20px;
}

.alert-info ul li {
    margin-bottom: 8px;
}

/* Responsive */
@media (max-width: 768px) {
    .content-wrapper {
        padding: 20px;
    }
    
    .page-header-section {
        padding: 20px;
    }
    
    .tab-content {
        padding: 20px;
    }
    
    .card-body {
        padding: 20px;
    }
    
    .ql-editor {
        min-height: 350px;
    }
}

/* Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.card {
    animation: fadeIn 0.5s ease;
}
</style>

<div class="content-wrapper">
    <div class="page-header-section">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><i class="fas fa-file-alt"></i> Page Content Editor</h2>
                <p class="text-muted">Edit Privacy Policy and Terms & Conditions content with rich text editor</p>
            </div>
            <?php if (empty($privacy['content']) || strlen($privacy['content']) < 100): ?>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="import_existing" class="btn import-btn">
                        <i class="fas fa-download"></i> Import Existing Content
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#privacy">
                <i class="fas fa-shield-alt"></i> Privacy Policy
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#terms">
                <i class="fas fa-file-contract"></i> Terms & Conditions
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Privacy Policy -->
        <div class="tab-pane fade show active" id="privacy">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5><i class="fas fa-shield-alt"></i> Privacy Policy Editor</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="privacyForm">
                        <input type="hidden" name="page_slug" value="privacy-policy">
                        
                        <div class="mb-3">
                            <label>Page Title</label>
                            <input type="text" name="page_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($privacy['page_title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label>Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($privacy['meta_description'] ?? ''); ?></textarea>
                            <small class="text-muted">For SEO (max 160 characters)</small>
                        </div>

                        <div class="mb-3">
                            <label>Content</label>
                            <div id="privacy-editor" style="background: white;"><?php echo $privacy['content'] ?? '<p>Start editing your privacy policy here...</p>'; ?></div>
                            <textarea name="content" id="privacy-content" style="display:none;"><?php echo htmlspecialchars($privacy['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" name="update_content" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Save Privacy Policy
                            </button>
                            <a href="../../privacy-policy.php" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Preview Page
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="tab-pane fade" id="terms">
            <div class="card">
                <div class="card-header bg-info text-white">
                    <h5><i class="fas fa-file-contract"></i> Terms & Conditions Editor</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="termsForm">
                        <input type="hidden" name="page_slug" value="terms-conditions">
                        
                        <div class="mb-3">
                            <label>Page Title</label>
                            <input type="text" name="page_title" class="form-control" 
                                   value="<?php echo htmlspecialchars($terms['page_title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label>Meta Description</label>
                            <textarea name="meta_description" class="form-control" rows="2"><?php echo htmlspecialchars($terms['meta_description'] ?? ''); ?></textarea>
                            <small class="text-muted">For SEO (max 160 characters)</small>
                        </div>

                        <div class="mb-3">
                            <label>Content</label>
                            <div id="terms-editor" style="background: white;"><?php echo $terms['content'] ?? '<p>Start editing your terms & conditions here...</p>'; ?></div>
                            <textarea name="content" id="terms-content" style="display:none;"><?php echo htmlspecialchars($terms['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="submit" name="update_content" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Save Terms & Conditions
                            </button>
                            <a href="../../terms-conditions.php" class="btn btn-secondary" target="_blank">
                                <i class="fas fa-external-link-alt"></i> Preview Page
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-4">
        <h5><i class="fas fa-info-circle"></i> Editor Tips:</h5>
        <ul>
            <li>Use headings (H2, H3) to structure your content</li>
            <li>Add bullet points and numbered lists for readability</li>
            <li>Include your company contact information</li>
            <li>Update the "Last Updated" date after major changes</li>
            <li>Keep language clear and easy to understand</li>
        </ul>
    </div>
</div>

<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
// Initialize Privacy Policy Editor
var privacyQuill = new Quill('#privacy-editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            ['link'],
            [{ 'align': [] }],
            ['clean']
        ]
    }
});

// Initialize Terms Editor
var termsQuill = new Quill('#terms-editor', {
    theme: 'snow',
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
            [{ 'indent': '-1'}, { 'indent': '+1' }],
            ['link'],
            [{ 'align': [] }],
            ['clean']
        ]
    }
});

// Update hidden textarea before form submission
document.getElementById('privacyForm').addEventListener('submit', function() {
    document.getElementById('privacy-content').value = privacyQuill.root.innerHTML;
});

document.getElementById('termsForm').addEventListener('submit', function() {
    document.getElementById('terms-content').value = termsQuill.root.innerHTML;
});
</script>

<?php require_once '../includes/footer.php'; ?>
