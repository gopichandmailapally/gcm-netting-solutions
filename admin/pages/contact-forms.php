<?php
/**
 * Contact Forms Management
 * View and manage all contact form submissions
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

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_status'])) {
        $id = (int)$_POST['submission_id'];
        $status = $_POST['status'];
        $admin_notes = $_POST['admin_notes'];
        
        $db->execute(
            "UPDATE contact_submissions SET status = ?, admin_notes = ? WHERE id = ?",
            [$status, $admin_notes, $id],
            'ssi'
        );
        
        $success = 'Submission status updated successfully!';
    }
    
    if (isset($_POST['delete_submission'])) {
        $id = (int)$_POST['submission_id'];
        $db->execute("DELETE FROM contact_submissions WHERE id = ?", [$id], 'i');
        $success = 'Submission deleted successfully!';
    }
}

// Fetch all submissions
$submissions = $db->fetchAll("SELECT * FROM contact_submissions ORDER BY submitted_at DESC");

$page_title = 'Contact Forms';
require_once '../includes/header.php';
?>

<style>
/* ═══ Contact Forms — Complete SEO System Theme ═══ */
@keyframes fadeInUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes float    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-6px)} }

.seo-page { padding: 0; }
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#3b82f6,#06b6d4) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #3b82f6; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.hero-actions { display: flex; gap: 12px; flex-shrink: 0; flex-wrap: wrap; }

/* ── Stat cards ───────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 0 6px !important; }
.stat-card .sub { font-size: 12px; }
.stat-badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.stat-badge.blue   { background: #dbeafe; color: #1e40af; }
.stat-badge.orange { background: #fef3c7; color: #92400e; }
.stat-badge.purple { background: #ede9fe; color: #5b21b6; }
.stat-badge.green  { background: #d1fae5; color: #065f46; }

/* ── Alert ─────────────────────────── */
.cf-alert { padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 10px; }
.cf-alert.success { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }

/* ── Section ───────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.blue { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.sec-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.seo-section-body { padding: 28px 32px; }

/* ── Buttons ───────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; white-space: nowrap; }
.btn-action:hover { transform: translateY(-2px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#10b981,#059669); color: white; box-shadow: 0 4px 14px rgba(16,185,129,.35); }
.btn-action.green:hover  { box-shadow: 0 8px 24px rgba(16,185,129,.45); }
.btn-action.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.35); }
.btn-action.blue:hover   { box-shadow: 0 8px 24px rgba(59,130,246,.45); }
.btn-action.red    { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 4px 14px rgba(239,68,68,.3); }
.btn-action.red:hover    { box-shadow: 0 8px 24px rgba(239,68,68,.4); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; transform: none; }
.btn-action.sm { padding: 9px 16px; font-size: 13px; }

/* ── Submission cards ──────────────── */
.submissions-list { display: flex; flex-direction: column; gap: 18px; }
.submission-card { background: #f8fafc; border-radius: 16px; border: 2px solid #e2e8f0; padding: 22px; transition: all .3s; border-left: 4px solid #3b82f6; animation: fadeInUp .4s ease both; }
.submission-card:hover { box-shadow: 0 8px 24px rgba(59,130,246,.1); border-color: #bfdbfe; }
.submission-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 18px; }
.sub-left { display: flex; align-items: center; gap: 14px; }
.sub-avatar { width: 48px; height: 48px; border-radius: 14px; background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 800; flex-shrink: 0; }
.sub-info h4 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.sub-date { font-size: 12px; color: #94a3b8; display: flex; align-items: center; gap: 5px; }

/* Status badges */
.status-badge { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; letter-spacing: .5px; }
.status-new        { background: #fef3c7; color: #92400e; border: 1.5px solid #fcd34d; }
.status-read       { background: #dbeafe; color: #1e40af; border: 1.5px solid #93c5fd; }
.status-responded  { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }

/* Detail grid */
.sub-details { display: grid; grid-template-columns: repeat(3,1fr); gap: 12px; margin-bottom: 16px; }
.sub-detail-item { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; background: white; border-radius: 10px; border: 1px solid #e2e8f0; }
.sub-detail-item > i { color: #667eea; margin-top: 2px; font-size: 14px; flex-shrink: 0; }
.detail-label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 3px; }
.detail-value { font-size: 13px; font-weight: 600; color: #1e293b; text-decoration: none; word-break: break-all; display: block; }
a.detail-value:hover { color: #3b82f6; }

/* Message / notes */
.sub-message, .admin-notes { margin-bottom: 16px; background: white; border-radius: 10px; padding: 14px 18px; border: 1px solid #e2e8f0; }
.admin-notes { background: #fffbeb; border-color: #fde68a; }
.msg-label { font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: .4px; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.msg-label i { color: #667eea; }
.admin-notes .msg-label i { color: #f59e0b; }
.sub-message p, .admin-notes p { font-size: 13px; color: #374151; line-height: 1.7; margin: 0; }

/* Sub actions */
.sub-actions { display: flex; gap: 10px; flex-wrap: wrap; }

/* Update form */
.update-form { margin-top: 16px; padding-top: 16px; border-top: 1px dashed #e2e8f0; }
.update-grid { display: grid; grid-template-columns: 1fr 2fr; gap: 16px; }
.form-group { display: flex; flex-direction: column; gap: 6px; }
.form-group label { font-size: 12px; font-weight: 700; color: #1e293b; text-transform: uppercase; letter-spacing: .4px; display: flex; align-items: center; gap: 6px; }
.form-group label i { color: #667eea; }
.form-control { padding: 10px 14px; border: 2px solid #e0e7ff; border-radius: 10px; font-size: 14px; color: #1e293b; background: white; outline: none; transition: border-color .2s, box-shadow .2s; width: 100%; box-sizing: border-box; }
.form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.12); }

/* Empty state */
.empty-state { text-align: center; padding: 60px 30px; }
.empty-state i { font-size: 64px; color: #bfdbfe; margin-bottom: 16px; display: block; animation: float 3s ease-in-out infinite; }
.empty-state h3 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
.empty-state p { font-size: 14px; color: #64748b; margin: 0; }

@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:768px){ .sub-details{grid-template-columns:1fr;} .update-grid{grid-template-columns:1fr;} }
@media(max-width:560px){ .seo-hero{flex-direction:column;} .stats-grid{grid-template-columns:1fr 1fr;} }
</style>

<?php
$total     = count($submissions);
$new_count = count(array_filter($submissions, fn($s) => $s['status'] === 'new'));
$read_count= count(array_filter($submissions, fn($s) => $s['status'] === 'read'));
$resp_count= count(array_filter($submissions, fn($s) => $s['status'] === 'responded'));
?>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-envelope"></i> Contact Form Submissions</h1>
        <p>All customer enquiries and their current response status</p>
    </div>
    <div class="hero-actions">
        <a href="../dashboard.php" class="btn-action gray sm">
            <i class="fas fa-arrow-left"></i> Dashboard
        </a>
    </div>
</div>

<!-- Stats bar -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-inbox"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Submissions</h3>
            <div class="value"><?php echo $total; ?></div>
            <div class="sub"><span class="stat-badge blue">All Time</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-envelope"></i></div>
        <div class="stat-text-wrap">
            <h3>New</h3>
            <div class="value"><?php echo $new_count; ?></div>
            <div class="sub"><span class="stat-badge orange">Awaiting Response</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><i class="fas fa-eye"></i></div>
        <div class="stat-text-wrap">
            <h3>Read</h3>
            <div class="value"><?php echo $read_count; ?></div>
            <div class="sub"><span class="stat-badge purple">In Progress</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Responded</h3>
            <div class="value"><?php echo $resp_count; ?></div>
            <div class="sub"><span class="stat-badge green">Completed</span></div>
        </div>
    </div>
</div>

<?php if ($success): ?>
<div class="cf-alert success">
    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
</div>
<?php endif; ?>

<!-- Section 1: All Submissions -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">1</div>
        <h2><i class="fas fa-list-alt" style="color:#3b82f6;margin-right:8px;"></i>All Submissions</h2>
        <?php if ($new_count > 0): ?>
        <span class="sec-badge" style="margin-left:auto;background:linear-gradient(135deg,#f59e0b,#d97706);">
            <i class="fas fa-bell" style="margin-right:5px;"></i><?php echo $new_count; ?> New
        </span>
        <?php else: ?>
        <span class="sec-badge" style="margin-left:auto;"><?php echo $total; ?> Total</span>
        <?php endif; ?>
    </div>
    <div class="seo-section-body">
        <?php if (empty($submissions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Submissions Yet</h3>
            <p>Customer contact form submissions will appear here.</p>
        </div>
        <?php else: ?>
        <div class="submissions-list">
            <?php foreach ($submissions as $submission): ?>
            <div class="submission-card">
                <div class="submission-header">
                    <div class="sub-left">
                        <div class="sub-avatar"><?php echo strtoupper(substr($submission['name'], 0, 1)); ?></div>
                        <div class="sub-info">
                            <h4><?php echo htmlspecialchars($submission['name']); ?></h4>
                            <span class="sub-date"><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></span>
                        </div>
                    </div>
                    <span class="status-badge status-<?php echo $submission['status']; ?>">
                        <?php echo strtoupper($submission['status']); ?>
                    </span>
                </div>

                <div class="sub-details">
                    <div class="sub-detail-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <span class="detail-label">Email</span>
                            <a href="mailto:<?php echo htmlspecialchars($submission['email']); ?>" class="detail-value"><?php echo htmlspecialchars($submission['email']); ?></a>
                        </div>
                    </div>
                    <div class="sub-detail-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <span class="detail-label">Phone</span>
                            <a href="tel:<?php echo htmlspecialchars($submission['phone']); ?>" class="detail-value"><?php echo htmlspecialchars($submission['phone']); ?></a>
                        </div>
                    </div>
                    <div class="sub-detail-item">
                        <i class="fas fa-cogs"></i>
                        <div>
                            <span class="detail-label">Service</span>
                            <span class="detail-value"><?php echo htmlspecialchars($submission['service']); ?></span>
                        </div>
                    </div>
                </div>

                <div class="sub-message">
                    <div class="msg-label"><i class="fas fa-comment-alt"></i> Message</div>
                    <p><?php echo nl2br(htmlspecialchars($submission['message'])); ?></p>
                </div>

                <?php if (!empty($submission['admin_notes'])): ?>
                <div class="admin-notes">
                    <div class="msg-label"><i class="fas fa-sticky-note"></i> Admin Notes</div>
                    <p><?php echo nl2br(htmlspecialchars($submission['admin_notes'])); ?></p>
                </div>
                <?php endif; ?>

                <div class="sub-actions">
                    <button class="btn-action blue sm" onclick="toggleUpdate(<?php echo $submission['id']; ?>)">
                        <i class="fas fa-edit"></i> Update Status
                    </button>
                    <button class="btn-action red sm" onclick="deleteSubmission(<?php echo $submission['id']; ?>)">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>

                <div class="update-form" id="update-<?php echo $submission['id']; ?>" style="display:none;">
                    <form method="POST">
                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                        <div class="update-grid">
                            <div class="form-group">
                                <label><i class="fas fa-tag"></i> Status</label>
                                <select name="status" class="form-control" required>
                                    <option value="new" <?php echo $submission['status'] === 'new' ? 'selected' : ''; ?>>New</option>
                                    <option value="read" <?php echo $submission['status'] === 'read' ? 'selected' : ''; ?>>Read</option>
                                    <option value="responded" <?php echo $submission['status'] === 'responded' ? 'selected' : ''; ?>>Responded</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-sticky-note"></i> Admin Notes</label>
                                <textarea name="admin_notes" class="form-control" rows="3"><?php echo htmlspecialchars($submission['admin_notes']); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="update_status" class="btn-action green sm" style="margin-top:10px;">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bottom nav -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
</div>

</div>

<script>
function deleteSubmission(id) {
    if (confirm('Are you sure you want to delete this submission?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="submission_id" value="${id}">
            <input type="hidden" name="delete_submission" value="1">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleUpdate(id) {
    const el = document.getElementById('update-' + id);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
}
</script>

<?php require_once '../includes/footer.php'; ?>
