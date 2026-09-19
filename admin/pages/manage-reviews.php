<?php
/**
 * Manage Reviews - Admin Panel
 * View, approve, and delete reviews
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Manage Reviews';
include '../includes/header.php';

// Load reviews
$reviews_dir = dirname(dirname(__DIR__)) . '/data/reviews';
$pending_dir = $reviews_dir . '/pending';

$approved_reviews = [];
$pending_reviews = [];

// Load approved reviews
if (is_dir($reviews_dir)) {
    $files = glob($reviews_dir . '/*.json');
    $skip = ['stats.json', 'auto-settings.json', 'index.php'];
    foreach ($files as $file) {
        if (in_array(basename($file), $skip)) continue;
        $review = json_decode(file_get_contents($file), true);
        if ($review && isset($review['customer_name'])) {
            $review['filename'] = basename($file);
            $approved_reviews[] = $review;
        }
    }
}

// Load pending reviews
if (is_dir($pending_dir)) {
    $files = glob($pending_dir . '/*.json');
    foreach ($files as $file) {
        $review = json_decode(file_get_contents($file), true);
        if ($review) {
            $review['filename'] = basename($file);
            $pending_reviews[] = $review;
        }
    }
}

// Sort by date
usort($approved_reviews, function($a, $b) {
    return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
});
usort($pending_reviews, function($a, $b) {
    return strtotime($b['created_at'] ?? 0) - strtotime($a['created_at'] ?? 0);
});

// Pagination
$per_page         = 30;
$active_tab       = $_GET['tab'] ?? 'approved';

$page_approved    = max(1, (int)($_GET['page_approved'] ?? 1));
$total_approved   = count($approved_reviews);
$pages_approved   = max(1, (int)ceil($total_approved / $per_page));
$page_approved    = min($page_approved, $pages_approved);
$paged_approved   = array_slice($approved_reviews, ($page_approved - 1) * $per_page, $per_page);

$page_pending     = max(1, (int)($_GET['page_pending'] ?? 1));
$total_pending    = count($pending_reviews);
$pages_pending    = max(1, (int)ceil($total_pending / $per_page));
$page_pending     = min($page_pending, $pages_pending);
$paged_pending    = array_slice($pending_reviews, ($page_pending - 1) * $per_page, $per_page);

// Helper: build pagination URL
function pgUrl(string $tab, int $page, int $other_page, string $other_key): string {
    $params = ['tab' => $tab, ($tab === 'approved' ? 'page_approved' : 'page_pending') => $page, $other_key => $other_page];
    return '?' . http_build_query($params);
}
?>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-star"></i> Manage Reviews</h1>
        <p>View, approve, and manage customer reviews from all sources</p>
    </div>
    <div class="hero-actions">
        <a href="review-generator.php" class="btn-action green sm">
            <i class="fas fa-magic"></i> Generate Reviews
        </a>
        <a href="<?php echo SITE_URL; ?>/reviews.php" class="btn-action blue sm" target="_blank">
            <i class="fas fa-globe"></i> View on Website
        </a>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-star"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Reviews</h3>
            <div class="value"><?php echo $total_approved + $total_pending; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Approved</h3>
            <div class="value"><?php echo $total_approved; ?></div>
            <div class="sub"><span class="stat-badge green">Live on site</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-clock"></i></div>
        <div class="stat-text-wrap">
            <h3>Pending</h3>
            <div class="value"><?php echo $total_pending; ?></div>
            <div class="sub"><?php if ($total_pending > 0): ?><span class="stat-badge orange">Needs review</span><?php else: ?><span class="stat-badge green">All clear</span><?php endif; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><i class="fas fa-robot"></i></div>
        <div class="stat-text-wrap">
            <h3>AI Generated</h3>
            <div class="value"><?php echo count(array_filter($approved_reviews, function($r){ return in_array($r['source'] ?? '', ['ai_generated','ai_auto']); })); ?></div>
            <div class="sub"><span class="stat-badge purple">Auto AI</span></div>
        </div>
    </div>
</div>

<!-- Tab Navigation -->
<div class="tab-nav">
    <button class="tab-btn <?php echo $active_tab === 'approved' ? 'active' : ''; ?>" onclick="switchTab('approved')">
        <i class="fas fa-check-circle"></i> Approved Reviews
        <span class="tab-count"><?php echo $total_approved; ?></span>
    </button>
    <button class="tab-btn <?php echo $active_tab === 'pending' ? 'active' : ''; ?>" onclick="switchTab('pending')">
        <i class="fas fa-clock"></i> Pending Approval
        <span class="tab-count <?php echo $total_pending > 0 ? 'orange' : ''; ?>"><?php echo $total_pending; ?></span>
    </button>
</div>

<!-- Approved Reviews Tab -->
<div id="approved-tab" class="tab-content <?php echo $active_tab === 'approved' ? 'active' : ''; ?>">
    <div class="seo-section">
        <div class="seo-section-head">
            <div class="sec-num green"><i class="fas fa-check"></i></div>
            <h2><i class="fas fa-check-circle" style="color:#10b981;margin-right:8px;"></i>Approved Reviews</h2>
            <span style="margin-left:auto;background:linear-gradient(135deg,#10b981,#059669);color:white;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;"><?php echo $total_approved; ?> Reviews</span>
        </div>
        <div class="seo-section-body">
            <?php if (empty($approved_reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Approved Reviews Yet</h3>
                <p>Generate or approve reviews to see them here</p>
                <a href="review-generator.php" class="btn-action green" style="margin-top:12px;">
                    <i class="fas fa-magic"></i> Generate Reviews
                </a>
            </div>
            <?php else: ?>
            <p style="color:#64748b;font-size:13px;margin:0 0 20px;">Showing <?php echo min($per_page, $total_approved - ($page_approved-1)*$per_page); ?> of <?php echo $total_approved; ?> &mdash; page <?php echo $page_approved; ?> of <?php echo $pages_approved; ?></p>
            <div class="reviews-grid">
                <?php foreach ($paged_approved as $review): ?>
                <div class="review-card">
                    <div class="review-header">
                        <div class="review-info">
                            <h3><?php echo htmlspecialchars($review['customer_name']); ?></h3>
                            <div class="rating">
                                <?php for($i=1; $i<=5; $i++): ?><i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i><?php endfor; ?>
                                <span>(<?php echo $review['rating']; ?>/5)</span>
                            </div>
                        </div>
                        <div class="review-meta">
                            <span class="badge-success"><i class="fas fa-check"></i> Approved</span>
                            <?php if (in_array($review['source'] ?? '', ['ai_generated','ai_auto'])): ?>
                            <span class="badge-primary"><i class="fas fa-robot"></i> AI</span>
                            <?php else: ?>
                            <span class="badge-info"><i class="fas fa-user"></i> User</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="review-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($review['category']); ?></div>
                    <div class="review-text"><?php echo htmlspecialchars(substr($review['review_text'], 0, 200)) . (strlen($review['review_text']) > 200 ? '...' : ''); ?></div>
                    <div class="review-footer">
                        <div class="review-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($review['location']); ?></div>
                        <div class="review-date"><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                    <div class="review-actions">
                        <button class="btn-action btn-view" onclick='viewReview(<?php echo json_encode($review); ?>)'><i class="fas fa-eye"></i> View</button>
                        <button class="btn-action btn-delete" onclick="deleteReview('<?php echo $review['filename']; ?>')"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($pages_approved > 1): ?>
            <div class="pagination">
                <?php $pa=$page_approved; $pp=$page_pending;
                if ($pa > 1): ?>
                <a href="<?php echo pgUrl('approved',1,$pp,'page_pending'); ?>" class="pg-btn pg-edge"><i class="fas fa-angle-double-left"></i></a>
                <a href="<?php echo pgUrl('approved',$pa-1,$pp,'page_pending'); ?>" class="pg-btn"><i class="fas fa-angle-left"></i></a>
                <?php endif;
                $start=max(1,$pa-2); $end=min($pages_approved,$pa+2);
                if ($start>1) echo '<span class="pg-dots">…</span>';
                for ($p=$start;$p<=$end;$p++): ?>
                <a href="<?php echo pgUrl('approved',$p,$pp,'page_pending'); ?>" class="pg-btn <?php echo $p===$pa?'active':''; ?>"><?php echo $p; ?></a>
                <?php endfor;
                if ($end<$pages_approved) echo '<span class="pg-dots">…</span>';
                if ($pa<$pages_approved): ?>
                <a href="<?php echo pgUrl('approved',$pa+1,$pp,'page_pending'); ?>" class="pg-btn"><i class="fas fa-angle-right"></i></a>
                <a href="<?php echo pgUrl('approved',$pages_approved,$pp,'page_pending'); ?>" class="pg-btn pg-edge"><i class="fas fa-angle-double-right"></i></a>
                <?php endif; ?>
                <span class="pg-info"><?php echo (($pa-1)*$per_page+1); ?>–<?php echo min($pa*$per_page,$total_approved); ?> of <?php echo $total_approved; ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Pending Reviews Tab -->
<div id="pending-tab" class="tab-content <?php echo $active_tab === 'pending' ? 'active' : ''; ?>">
    <div class="seo-section">
        <div class="seo-section-head">
            <div class="sec-num orange"><i class="fas fa-clock"></i></div>
            <h2><i class="fas fa-clock" style="color:#f59e0b;margin-right:8px;"></i>Pending Approval</h2>
            <span style="margin-left:auto;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;"><?php echo $total_pending; ?> Pending</span>
        </div>
        <div class="seo-section-body">
            <?php if (empty($pending_reviews)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No Pending Reviews</h3>
                <p>Reviews submitted from the website will appear here</p>
            </div>
            <?php else: ?>
            <p style="color:#64748b;font-size:13px;margin:0 0 20px;">Showing <?php echo min($per_page, max(0,$total_pending-($page_pending-1)*$per_page)); ?> of <?php echo $total_pending; ?> &mdash; page <?php echo $page_pending; ?> of <?php echo $pages_pending; ?></p>
            <div class="reviews-grid">
                <?php foreach ($paged_pending as $review): ?>
                <div class="review-card pending">
                    <div class="review-header">
                        <div class="review-info">
                            <h3><?php echo htmlspecialchars($review['customer_name']); ?></h3>
                            <div class="rating">
                                <?php for($i=1; $i<=5; $i++): ?><i class="fas fa-star <?php echo $i <= $review['rating'] ? 'filled' : ''; ?>"></i><?php endfor; ?>
                                <span>(<?php echo $review['rating']; ?>/5)</span>
                            </div>
                        </div>
                        <div class="review-meta">
                            <span class="badge-warning"><i class="fas fa-clock"></i> Pending</span>
                        </div>
                    </div>
                    <div class="review-category"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($review['category']); ?></div>
                    <div class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></div>
                    <div class="review-footer">
                        <div class="review-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($review['location']); ?></div>
                        <div class="review-date"><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($review['created_at'])); ?></div>
                    </div>
                    <?php if (!empty($review['email']) || !empty($review['phone'])): ?>
                    <div class="review-contact">
                        <?php if (!empty($review['email'])): ?><span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($review['email']); ?></span><?php endif; ?>
                        <?php if (!empty($review['phone'])): ?><span><i class="fas fa-phone"></i> <?php echo htmlspecialchars($review['phone']); ?></span><?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <div class="review-actions">
                        <button class="btn-action btn-approve" onclick="approveReview('<?php echo $review['filename']; ?>')"><i class="fas fa-check"></i> Approve</button>
                        <button class="btn-action btn-reject" onclick="rejectReview('<?php echo $review['filename']; ?>')"><i class="fas fa-times"></i> Reject</button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if ($pages_pending > 1): ?>
            <div class="pagination">
                <?php $pa=$page_approved; $pp=$page_pending;
                if ($pp>1): ?>
                <a href="<?php echo pgUrl('pending',1,$pa,'page_approved'); ?>" class="pg-btn pg-edge"><i class="fas fa-angle-double-left"></i></a>
                <a href="<?php echo pgUrl('pending',$pp-1,$pa,'page_approved'); ?>" class="pg-btn"><i class="fas fa-angle-left"></i></a>
                <?php endif;
                $start=max(1,$pp-2); $end=min($pages_pending,$pp+2);
                if ($start>1) echo '<span class="pg-dots">…</span>';
                for ($p=$start;$p<=$end;$p++): ?>
                <a href="<?php echo pgUrl('pending',$p,$pa,'page_approved'); ?>" class="pg-btn <?php echo $p===$pp?'active':''; ?>"><?php echo $p; ?></a>
                <?php endfor;
                if ($end<$pages_pending) echo '<span class="pg-dots">…</span>';
                if ($pp<$pages_pending): ?>
                <a href="<?php echo pgUrl('pending',$pp+1,$pa,'page_approved'); ?>" class="pg-btn"><i class="fas fa-angle-right"></i></a>
                <a href="<?php echo pgUrl('pending',$pages_pending,$pa,'page_approved'); ?>" class="pg-btn pg-edge"><i class="fas fa-angle-double-right"></i></a>
                <?php endif; ?>
                <span class="pg-info"><?php echo (($pp-1)*$per_page+1); ?>–<?php echo min($pp*$per_page,$total_pending); ?> of <?php echo $total_pending; ?></span>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bottom Navigation -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="review-generator.php" class="btn-action green">
        <i class="fas fa-magic"></i> Generate More Reviews
    </a>
    <a href="<?php echo SITE_URL; ?>/reviews.php" class="btn-action blue" target="_blank">
        <i class="fas fa-globe"></i> View on Website
    </a>
</div>

</div>

<!-- View Review Modal -->
<div id="reviewModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <div id="modalBody"></div>
    </div>
</div>

<style>
/* ═══ Manage Reviews — Complete SEO System Theme ════════ */
@keyframes float   { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
@keyframes fadeIn  { from{opacity:0} to{opacity:1} }
@keyframes slideUp { from{transform:translateY(40px);opacity:0} to{transform:translateY(0);opacity:1} }
@keyframes fadeInUp{ from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }

/* ── Page & Hero ──────────────────────────────── */
.seo-page { padding: 0; }
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.hero-actions { display: flex; gap: 12px; flex-shrink: 0; flex-wrap: wrap; }

/* ── Stat cards ───────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f97316,#dc2626); }
.stat-icon-wrap.purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 0 6px !important; }
.stat-card .sub { font-size: 12px; }
.stat-badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.stat-badge.green  { background: #d1fae5; color: #065f46; }
.stat-badge.orange { background: #fef3c7; color: #92400e; }
.stat-badge.purple { background: #ede9fe; color: #5b21b6; }

/* ── Section cards ────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-num.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

/* ── Action buttons ───────────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border: none; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all .25s; white-space: nowrap; }
.btn-action:hover { transform: translateY(-2px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 4px 14px rgba(40,167,69,.35); }
.btn-action.green:hover  { box-shadow: 0 8px 24px rgba(40,167,69,.45); }
.btn-action.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.35); }
.btn-action.blue:hover   { box-shadow: 0 8px 24px rgba(59,130,246,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; transform: none; }
.btn-action.sm { padding: 9px 16px; font-size: 13px; }

/* Card-level action buttons (view/delete/approve/reject) */
.btn-view    { background: linear-gradient(135deg,#667eea,#764ba2) !important; color: white !important; flex: 1; justify-content: center; }
.btn-delete  { background: linear-gradient(135deg,#ef4444,#dc2626) !important; color: white !important; flex: 1; justify-content: center; }
.btn-approve { background: linear-gradient(135deg,#10b981,#059669) !important; color: white !important; flex: 1; justify-content: center; }
.btn-reject  { background: linear-gradient(135deg,#ef4444,#dc2626) !important; color: white !important; flex: 1; justify-content: center; }

/* ── Tab navigation ───────────────────────────── */
.tab-nav { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
.tab-btn { padding: 12px 22px; background: white; border: 2px solid #e2e8f0; border-radius: 12px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all .2s; color: #64748b; }
.tab-btn:hover { background: #f8faff; border-color: #c4b5fd; color: #667eea; }
.tab-btn.active { background: linear-gradient(135deg,#667eea,#764ba2); color: white; border-color: transparent; box-shadow: 0 4px 16px rgba(102,126,234,.35); }
.tab-count { background: rgba(255,255,255,.25); padding: 2px 9px; border-radius: 20px; font-size: 12px; font-weight: 700; }
.tab-btn:not(.active) .tab-count { background: #e0e7ff; color: #3730a3; }
.tab-btn:not(.active) .tab-count.orange { background: #fef3c7; color: #92400e; }
.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeInUp .3s ease; }

/* ── Reviews Grid ─────────────────────────────── */
.reviews-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(330px,1fr)); gap: 20px; }
.review-card { background: white; border: 2px solid #e2e8f0; border-radius: 16px; padding: 20px; transition: all .3s; position: relative; overflow: hidden; }
.review-card::before { content: ''; position: absolute; top: 0; left: 0; width: 4px; height: 100%; background: linear-gradient(180deg,#667eea,#764ba2); border-radius: 4px 0 0 4px; }
.review-card:hover { border-color: #c4b5fd; box-shadow: 0 8px 28px rgba(102,126,234,.18); transform: translateY(-4px); }
.review-card.pending { background: #fffbeb; border-color: #fcd34d; }
.review-card.pending::before { background: linear-gradient(180deg,#f59e0b,#d97706); }

.review-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 14px; }
.review-info h3 { margin: 0 0 8px; font-size: 16px; font-weight: 700; color: #1e293b; }
.rating { display: flex; align-items: center; gap: 3px; }
.rating i { color: #e2e8f0; font-size: 15px; }
.rating i.filled { color: #f59e0b; }
.rating span { margin-left: 6px; color: #64748b; font-size: 13px; }
.review-meta { display: flex; flex-direction: column; gap: 5px; align-items: flex-end; }
.review-category { background: linear-gradient(135deg,#eef2ff,#e0e7ff); color: #3730a3; padding: 5px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; margin-bottom: 12px; display: inline-block; }
.review-text { color: #475569; line-height: 1.7; margin-bottom: 14px; font-size: 14px; }
.review-footer { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; padding-top: 12px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; }
.review-contact { background: linear-gradient(135deg,#eff6ff,#dbeafe); padding: 10px 14px; border-radius: 10px; margin-bottom: 14px; font-size: 13px; color: #1e40af; display: flex; flex-direction: column; gap: 5px; }
.review-actions { display: flex; gap: 8px; }

/* ── Badges ───────────────────────────────────── */
.badge-success,.badge-primary,.badge-info,.badge-warning { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; white-space: nowrap; }
.badge-success { background: #d1fae5; color: #065f46; }
.badge-primary { background: #ede9fe; color: #5b21b6; }
.badge-info    { background: #dbeafe; color: #1e40af; }
.badge-warning { background: #fef3c7; color: #92400e; }

/* ── Empty State ──────────────────────────────── */
.empty-state { text-align: center; padding: 60px 30px; }
.empty-state i { font-size: 64px; color: #c4b5fd; margin-bottom: 18px; display: block; animation: float 3s ease-in-out infinite; }
.empty-state h3 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
.empty-state p { font-size: 14px; color: #64748b; margin: 0; }

/* ── Modal ────────────────────────────────────── */
.modal { display: none; position: fixed; z-index: 10000; inset: 0; background: rgba(15,23,42,.65); backdrop-filter: blur(4px); animation: fadeIn .3s; }
.modal-content { background: white; margin: 5% auto; padding: 32px; border-radius: 20px; width: 90%; max-width: 700px; max-height: 80vh; overflow-y: auto; animation: slideUp .3s; box-shadow: 0 25px 60px rgba(0,0,0,.25); }
.close { float: right; font-size: 28px; font-weight: 700; color: #94a3b8; cursor: pointer; line-height: 1; }
.close:hover { color: #1e293b; }

/* ── Pagination ───────────────────────────────── */
.pagination { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 24px 0 8px; flex-wrap: wrap; }
.pg-btn { min-width: 38px; height: 38px; padding: 0 12px; display: inline-flex; align-items: center; justify-content: center; border-radius: 10px; background: white; border: 2px solid #e2e8f0; color: #64748b; font-size: 14px; font-weight: 600; text-decoration: none; transition: all .2s; }
.pg-btn:hover { border-color: #a78bfa; color: #667eea; background: #f5f3ff; transform: translateY(-1px); }
.pg-btn.active { background: linear-gradient(135deg,#667eea,#764ba2); color: white; border-color: transparent; box-shadow: 0 4px 14px rgba(102,126,234,.45); transform: translateY(-2px); }
.pg-btn.pg-edge { font-size: 12px; }
.pg-dots { color: #94a3b8; font-size: 16px; padding: 0 4px; }
.pg-info { color: #64748b; font-size: 13px; font-weight: 600; background: #f1f5f9; padding: 8px 16px; border-radius: 20px; margin-left: 8px; white-space: nowrap; }

@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:768px){ .reviews-grid{grid-template-columns:1fr;} .pg-info{display:none;} }
@media(max-width:560px){ .seo-hero{flex-direction:column;} .stats-grid{grid-template-columns:1fr;} }
</style>

<script>
function switchTab(tab) {
    // Preserve current page numbers in URL
    const params = new URLSearchParams(window.location.search);
    params.set('tab', tab);
    window.history.pushState({}, '', '?' + params.toString());

    document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));

    document.getElementById(tab + '-tab').classList.add('active');
    document.querySelectorAll('.tab-btn').forEach(el => {
        if (el.textContent.toLowerCase().includes(tab === 'approved' ? 'approved' : 'pending')) {
            el.classList.add('active');
        }
    });
}

function viewReview(review) {
    const stars = '⭐'.repeat(review.rating);
    const modal = document.getElementById('reviewModal');
    const modalBody = document.getElementById('modalBody');
    
    modalBody.innerHTML = `
        <h2>${review.customer_name}</h2>
        <div style="margin: 15px 0;">
            <span style="font-size: 24px;">${stars}</span>
            <span style="color: #64748B;">${review.rating}/5</span>
        </div>
        <div style="background: #F0F9FF; padding: 10px 15px; border-radius: 6px; margin-bottom: 15px;">
            <strong>Category:</strong> ${review.category}
        </div>
        <div style="background: #FAFAFA; padding: 15px; border-radius: 6px; margin-bottom: 15px; line-height: 1.6;">
            ${review.review_text}
        </div>
        <div style="color: #64748B; font-size: 14px;">
            <div><i class="fas fa-map-marker-alt"></i> ${review.location}</div>
            <div><i class="fas fa-calendar"></i> ${new Date(review.created_at).toLocaleDateString()}</div>
            ${review.source === 'ai_generated' ? '<div><i class="fas fa-robot"></i> AI Generated</div>' : '<div><i class="fas fa-user"></i> User Submission</div>'}
        </div>
    `;
    
    modal.style.display = 'block';
}

function closeModal() {
    document.getElementById('reviewModal').style.display = 'none';
}

let _gcmDelFile = null;
function deleteReview(filename) {
    _gcmDelFile = filename;
    document.getElementById('gcmProtFile').textContent = filename;
    document.getElementById('gcmProtPin').value = '';
    document.getElementById('gcmProtReason').value = '';
    document.getElementById('gcmProtMsg').style.display = 'none';
    const btn = document.getElementById('gcmProtSubmit');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
    document.getElementById('gcmProtModal').style.display = 'flex';
    setTimeout(() => document.getElementById('gcmProtPin').focus(), 100);
}
function gcmCloseProtModal() {
    document.getElementById('gcmProtModal').style.display = 'none';
    _gcmDelFile = null;
}
function gcmShowProtMsg(text, type) {
    const el = document.getElementById('gcmProtMsg');
    el.style.cssText = 'display:block;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:10px;' +
        (type==='success' ? 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;'
                         : 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;');
    el.textContent = text;
}
async function gcmSubmitProtDel() {
    const pin    = document.getElementById('gcmProtPin').value.trim();
    const reason = document.getElementById('gcmProtReason').value.trim();
    const btn    = document.getElementById('gcmProtSubmit');
    if (!pin) { gcmShowProtMsg('Please enter your Security PIN.', 'error'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
    document.getElementById('gcmProtMsg').style.display = 'none';
    try {
        const resp = await fetch('../api/delete-review.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({filename: _gcmDelFile, pin, reason})
        });
        const data = await resp.json();
        if (data.success) {
            gcmCloseProtModal();
            showToast('Review deleted successfully', 'success');
            setTimeout(() => location.reload(), 1000);
        } else if (data.pending) {
            gcmShowProtMsg('✅ ' + data.message, 'success');
            btn.innerHTML = 'Request Submitted';
            setTimeout(gcmCloseProtModal, 6000);
        } else {
            gcmShowProtMsg('❌ ' + (data.message || 'Error'), 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        }
    } catch(e) {
        gcmShowProtMsg('Network error: ' + e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
    }
}

function approveReview(filename) {
    if (!confirm('Approve this review and publish it on the website?')) return;
    
    fetch('../api/approve-review.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({filename: filename, action: 'approve'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Review approved and published!', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    });
}

function rejectReview(filename) {
    if (!confirm('Reject and delete this review?')) return;
    
    fetch('../api/approve-review.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({filename: filename, action: 'reject'})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Review rejected', 'success');
            setTimeout(() => location.reload(), 1000);
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    });
}

function showToast(message, type) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10B981' : '#EF4444'};
        color: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10001;
    `;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 3000);
}

// Close modal on outside click
window.onclick = function(event) {
    const modal = document.getElementById('reviewModal');
    if (event.target == modal) closeModal();
    const pm = document.getElementById('gcmProtModal');
    if (event.target == pm) gcmCloseProtModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') gcmCloseProtModal(); });
</script>

<!-- AI Content Protection Modal -->
<div id="gcmProtModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.78);backdrop-filter:blur(6px);z-index:20000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:white;max-width:460px;width:100%;border-radius:20px;padding:32px;box-shadow:0 25px 60px rgba(0,0,0,.35);">
    <div style="text-align:center;margin-bottom:22px;">
      <div style="width:64px;height:64px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 4px 16px rgba(217,119,6,.25);">
        <i class="fas fa-shield-alt" style="font-size:28px;color:#d97706;"></i>
      </div>
      <h3 style="margin:0 0 8px;font-size:19px;font-weight:700;color:#1e293b;">AI Content Protected</h3>
      <p style="margin:0;color:#64748b;font-size:13px;line-height:1.65;">This review is registered in <strong>AI Content Security</strong>. Enter your <strong>Security PIN</strong> to submit a deletion request. Nothing will be deleted until you approve it via the email link sent to the admin.</p>
    </div>
    <div style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:9px;padding:9px 13px;margin-bottom:18px;font-size:12px;color:#64748b;word-break:break-all;">
      <i class="fas fa-file" style="color:#667eea;margin-right:5px;"></i><span id="gcmProtFile"></span>
    </div>
    <div style="margin-bottom:14px;">
      <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Security PIN <span style="color:#ef4444;">*</span></label>
      <input id="gcmProtPin" type="password" maxlength="6" placeholder="••••••"
             style="width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:22px;letter-spacing:8px;text-align:center;outline:none;box-sizing:border-box;"
             onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"
             onkeydown="if(event.key==='Enter')gcmSubmitProtDel()">
    </div>
    <div style="margin-bottom:18px;">
      <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Reason <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
      <textarea id="gcmProtReason" rows="2" placeholder="Why do you need to delete this content?"
                style="width:100%;padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:13px;resize:none;outline:none;box-sizing:border-box;"
                onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
    </div>
    <div id="gcmProtMsg" style="display:none;"></div>
    <div style="display:flex;gap:10px;">
      <button onclick="gcmCloseProtModal()" style="flex:1;padding:12px;background:#f1f5f9;border:none;border-radius:10px;font-size:14px;font-weight:600;color:#64748b;cursor:pointer;">Cancel</button>
      <button id="gcmProtSubmit" onclick="gcmSubmitProtDel()" style="flex:2;padding:12px;background:linear-gradient(135deg,#ef4444,#dc2626);border:none;border-radius:10px;font-size:14px;font-weight:700;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
        <i class="fas fa-paper-plane"></i> Submit Request
      </button>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
