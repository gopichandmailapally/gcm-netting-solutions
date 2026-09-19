<?php
/**
 * FAQ Management
 * Answer user questions and manage FAQs
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/email-helper.php';
require_once '../../config/faq-categories.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$success = '';
$error = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['answer_question'])) {
        $id = (int)$_POST['question_id'];
        $answer = $_POST['answer'];
        $category = $_POST['category'];
        
        $question = $db->fetchOne("SELECT * FROM faq_user_questions WHERE id = ?", [$id], 'i');
        
        $db->execute(
            "UPDATE faq_user_questions SET answer = ?, category = ?, status = 'answered', answered_at = NOW(), answered_by = ? WHERE id = ?",
            [$answer, $category, $_SESSION['admin_id'], $id],
            'ssii'
        );
        
        // Send notification to user
        if (!empty($question['user_email'])) {
            $question['answer'] = $answer;
            send_faq_answer_notification($question);
        }
        
        $success = 'Question answered successfully!';
    }
    
    if (isset($_POST['publish_question'])) {
        $id = (int)$_POST['question_id'];
        $display_order = (int)$_POST['display_order'];
        
        $db->execute(
            "UPDATE faq_user_questions SET is_published = 1, status = 'published', display_order = ? WHERE id = ?",
            [$display_order, $id],
            'ii'
        );
        
        $success = 'Question published to FAQ page!';
    }
    
    if (isset($_POST['unpublish_question'])) {
        $id = (int)$_POST['question_id'];
        $db->execute("UPDATE faq_user_questions SET is_published = 0 WHERE id = ?", [$id], 'i');
        $success = 'Question unpublished from FAQ page.';
    }
    
    if (isset($_POST['delete_question'])) {
        $id = (int)$_POST['question_id'];
        $db->execute("DELETE FROM faq_user_questions WHERE id = ?", [$id], 'i');
        $success = 'Question deleted successfully!';
    }
}

// Fetch questions by status
$pending = $db->fetchAll("SELECT * FROM faq_user_questions WHERE status = 'pending' ORDER BY submitted_at DESC");
$answered = $db->fetchAll("SELECT * FROM faq_user_questions WHERE status = 'answered' ORDER BY answered_at DESC");
$published = $db->fetchAll("SELECT * FROM faq_user_questions WHERE is_published = 1 ORDER BY display_order ASC, submitted_at DESC");

$page_title = 'FAQ Management';
require_once '../includes/header.php';
?>

<style>
/* Modern FAQ Management Styles */
.content-wrapper {
    animation: fadeIn 0.5s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
    animation: slideDown 0.6s ease-out;
}

@keyframes slideDown {
    from { transform: translateY(-30px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.page-header h2 {
    margin: 0;
    font-weight: 700;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
}

.page-header p {
    margin: 10px 0 0 0;
    opacity: 0.95;
    font-size: 16px;
    color: rgba(255, 255, 255, 0.95) !important;
}

/* Remove ALL bullets globally */
ul, li {
    list-style: none !important;
    list-style-type: none !important;
}

ul {
    padding-left: 0 !important;
    margin-left: 0 !important;
}

/* Beautiful Tab Styles */
.nav-tabs {
    border-bottom: 3px solid #e9ecef;
    gap: 10px;
    list-style: none !important;
    padding-left: 0 !important;
    display: flex !important;
    flex-direction: row !important;
}

.nav-tabs .nav-item {
    margin-bottom: -3px;
    list-style: none !important;
    display: inline-block !important;
}

.nav-tabs .nav-link {
    border: none;
    border-radius: 12px 12px 0 0;
    padding: 15px 25px;
    font-weight: 600;
    color: #6c757d;
    background: #f8f9fa;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.nav-tabs .nav-link::before {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 100%;
    height: 3px;
    background: linear-gradient(90deg, #667eea, #764ba2);
    transform: translateX(-100%);
    transition: transform 0.3s ease;
}

.nav-tabs .nav-link:hover {
    background: #e9ecef;
    color: #495057;
    transform: translateY(-2px);
}

.nav-tabs .nav-link.active {
    background: white;
    color: #667eea;
    border-bottom: 3px solid #667eea;
    box-shadow: 0 -3px 10px rgba(102, 126, 234, 0.1);
}

.nav-tabs .nav-link.active::before {
    transform: translateX(0);
}

.nav-tabs .badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 20px;
    margin-left: 8px;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Beautiful FAQ Cards */
.faq-card {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px !important;
    background: white;
    transition: all 0.3s ease;
    position: relative;
    overflow: visible !important;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    clear: both;
    z-index: 1;
}

.faq-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 5px;
    height: 100%;
    transition: width 0.3s ease;
}

.faq-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.faq-card:hover::before {
    width: 10px;
}

.faq-pending::before {
    background: linear-gradient(180deg, #ffc107 0%, #ff9800 100%);
}

.faq-answered::before {
    background: linear-gradient(180deg, #17a2b8 0%, #0c7c8f 100%);
}

.faq-published::before {
    background: linear-gradient(180deg, #28a745 0%, #1e7e34 100%);
}

.faq-card h5 {
    color: #495057;
    font-weight: 700;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
    text-decoration: none !important;
}

.faq-card h5 i {
    color: #667eea;
    font-size: 20px;
}

.faq-card p {
    color: #6c757d;
    line-height: 1.8;
    font-size: 15px;
}

.faq-card .row {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin: 15px 0;
}

.faq-card .row strong {
    color: #495057;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.faq-card .row strong i {
    color: #667eea;
}

/* Button Container - Horizontal Layout */
.button-group {
    display: flex !important;
    gap: 10px !important;
    flex-wrap: wrap !important;
    align-items: center !important;
    margin-top: 15px !important;
}

.faq-card .mt-3,
.faq-card > div:last-child {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
}

/* Beautiful Buttons */
.btn {
    border-radius: 8px;
    padding: 10px 20px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: none;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    box-shadow: 0 4px 15px rgba(40, 167, 69, 0.4);
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.6);
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.4);
}

.btn-danger:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(220, 53, 69, 0.6);
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    box-shadow: 0 4px 15px rgba(255, 193, 7, 0.4);
    color: #fff;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(255, 193, 7, 0.6);
}

/* Alert Boxes */
.alert {
    border-radius: 12px;
    border: none;
    padding: 20px;
    margin-bottom: 25px;
    animation: slideIn 0.5s ease-out;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

@keyframes slideIn {
    from { transform: translateX(-20px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

.alert-info {
    background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
    color: #0d47a1;
    border-left: 5px solid #2196f3;
}

.alert-success {
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
    color: #1b5e20;
    border-left: 5px solid #4caf50;
}

/* Form Styling */
.form-control, .form-select {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.card {
    border-radius: 15px;
    border: 2px solid #e9ecef;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    margin-top: 15px !important;
    z-index: 100 !important;
    position: relative;
}

.card-body {
    padding: 25px;
    background: white;
}

/* Fix collapse overlapping */
.collapse {
    z-index: 100 !important;
    position: relative;
}

.collapse.show {
    margin-bottom: 20px !important;
}

/* Dropdown fixes */
.form-select {
    z-index: auto !important;
    position: relative;
}

/* Badge spacing */
.badge {
    margin-right: 8px;
    margin-bottom: 8px;
    display: inline-block;
}

/* Tab content spacing */
.tab-content {
    padding-top: 20px;
}

.tab-pane {
    min-height: 300px;
}

/* Loading Animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.btn i {
    transition: all 0.3s ease;
}

.btn:hover i {
    transform: scale(1.2);
}
</style>

<div class="content-wrapper">
    <div class="page-header">
        <h2><i class="fas fa-question-circle"></i> FAQ Management</h2>
        <p>Manage customer questions and publish helpful answers</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" data-bs-toggle="tab" href="#pending">
                Pending <span class="badge bg-warning"><?php echo count($pending); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#answered">
                Answered <span class="badge bg-info"><?php echo count($answered); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="tab" href="#published">
                Published <span class="badge bg-success"><?php echo count($published); ?></span>
            </a>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Pending Questions -->
        <div class="tab-pane fade show active" id="pending">
            <?php if (empty($pending)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No pending questions.
                </div>
            <?php else: ?>
                <?php foreach ($pending as $q): ?>
                    <div class="faq-card faq-pending">
                        <div class="mb-3">
                            <h5><i class="fas fa-question"></i> Question:</h5>
                            <p><?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <strong><i class="fas fa-user"></i> From:</strong><br>
                                <?php echo htmlspecialchars($q['user_name'] ?: 'Anonymous'); ?>
                            </div>
                            <div class="col-md-6">
                                <strong><i class="fas fa-calendar"></i> Submitted:</strong><br>
                                <?php echo date('F j, Y g:i A', strtotime($q['submitted_at'])); ?>
                            </div>
                        </div>

                        <div class="button-group">
                            <button class="btn btn-primary btn-sm" data-bs-toggle="collapse" data-bs-target="#answer-<?php echo $q['id']; ?>">
                                <i class="fas fa-reply"></i> Answer Question
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteQuestion(<?php echo $q['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>

                        <div class="collapse mt-3" id="answer-<?php echo $q['id']; ?>">
                            <div class="card card-body">
                                <form method="POST">
                                    <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                    <div class="mb-3">
                                        <label>Category *</label>
                                        <select name="category" class="form-select" required>
                                            <?php echo get_faq_category_options(); ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label>Your Answer</label>
                                        <textarea name="answer" class="form-control" rows="4" required 
                                                  placeholder="Provide a detailed answer..."></textarea>
                                    </div>
                                    <button type="submit" name="answer_question" class="btn btn-success">
                                        <i class="fas fa-paper-plane"></i> Submit Answer
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Answered Questions -->
        <div class="tab-pane fade" id="answered">
            <?php if (empty($answered)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No answered questions.
                </div>
            <?php else: ?>
                <?php foreach ($answered as $q): ?>
                    <div class="faq-card faq-answered">
                        <div class="mb-3">
                            <h5><i class="fas fa-question"></i> Question:</h5>
                            <p><?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="fas fa-comment"></i> Answer:</h5>
                            <p><?php echo nl2br(htmlspecialchars($q['answer'])); ?></p>
                        </div>

                        <div class="mb-3">
                            <span class="badge bg-info"><?php echo htmlspecialchars($q['category']); ?></span>
                        </div>

                        <div class="button-group">
                            <button class="btn btn-success btn-sm" data-bs-toggle="collapse" data-bs-target="#publish-<?php echo $q['id']; ?>">
                                <i class="fas fa-globe"></i> Publish to Website
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteQuestion(<?php echo $q['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>

                        <div class="collapse mt-3" id="publish-<?php echo $q['id']; ?>">
                            <div class="card card-body">
                                <form method="POST">
                                    <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                    <div class="mb-3">
                                        <label>Display Order (Lower numbers appear first)</label>
                                        <input type="number" name="display_order" class="form-control" 
                                               value="0" min="0" max="1000">
                                    </div>
                                    <button type="submit" name="publish_question" class="btn btn-success">
                                        <i class="fas fa-check"></i> Publish Now
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Published Questions -->
        <div class="tab-pane fade" id="published">
            <?php if (empty($published)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> No published FAQs.
                </div>
            <?php else: ?>
                <?php foreach ($published as $q): ?>
                    <div class="faq-card faq-published">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-info"><?php echo htmlspecialchars($q['category']); ?></span>
                                <span class="badge bg-secondary">Order: <?php echo $q['display_order']; ?></span>
                            </div>
                            <span class="badge bg-success"><i class="fas fa-globe"></i> LIVE ON WEBSITE</span>
                        </div>

                        <div class="mb-3">
                            <h5><i class="fas fa-question"></i> Question:</h5>
                            <p><?php echo nl2br(htmlspecialchars($q['question'])); ?></p>
                        </div>
                        
                        <div class="mb-3">
                            <h5><i class="fas fa-comment"></i> Answer:</h5>
                            <p><?php echo nl2br(htmlspecialchars($q['answer'])); ?></p>
                        </div>

                        <div class="button-group">
                            <button class="btn btn-warning btn-sm" onclick="unpublishQuestion(<?php echo $q['id']; ?>)">
                                <i class="fas fa-eye-slash"></i> Unpublish
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteQuestion(<?php echo $q['id']; ?>)">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function deleteQuestion(id) {
    if (confirm('Are you sure you want to delete this question?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="question_id" value="${id}">
            <input type="hidden" name="delete_question" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function unpublishQuestion(id) {
    if (confirm('Remove this FAQ from the website?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="question_id" value="${id}">
            <input type="hidden" name="unpublish_question" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
