<?php
/**
 * Manage Blogs - View All Generated Blogs
 * VERSION: v2025-04-10-B
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

@set_time_limit(120);
@ini_set('memory_limit', '256M');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$blogs_dir = dirname(dirname(__DIR__)) . '/data/blogs';

// ── AJAX: return single blog content (before any HTML output) ──
if (isset($_GET['ajax_view'])) {
    header('Content-Type: application/json');
    $fname = basename($_GET['ajax_view']);
    $fpath = $blogs_dir . '/' . $fname;
    if (file_exists($fpath) && pathinfo($fpath, PATHINFO_EXTENSION) === 'json') {
        $data = json_decode(file_get_contents($fpath), true);
        echo json_encode(['success' => true, 'blog' => $data]);
    } else {
        echo json_encode(['success' => false, 'message' => 'File not found']);
    }
    exit;
}

// ── Load blogs – metadata only (strip content to save memory) ──
$blogs = [];
$total_files = 0;

if (file_exists($blogs_dir) && is_dir($blogs_dir)) {
    $files = glob($blogs_dir . '/*.json');
    if ($files === false) $files = [];
    foreach ($files as $file) {
        $bname = basename($file);
        if ($bname === 'index.json' || $bname === 'stats.json') continue;
        $total_files++;
        $raw = file_get_contents($file);
        if (!$raw) continue;
        $blog_data = json_decode($raw, true);
        if ($blog_data && isset($blog_data['title'])) {
            // Keep only list-display fields; content loaded via AJAX
            $blogs[] = [
                'file_name'  => $bname,
                'title'      => $blog_data['title'] ?? '',
                'author'     => $blog_data['author'] ?? 'GCM Netting Solutions',
                'excerpt'    => $blog_data['excerpt'] ?? '',
                'created_at' => $blog_data['created_at'] ?? '',
            ];
        }
    }
}

usort($blogs, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

$stats_file = $blogs_dir . '/stats.json';
$stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];

// ── Bulk delete (must be before any output) ────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $to_delete = $_POST['selected_files'] ?? [];
    $deleted = 0;
    foreach ($to_delete as $fname) {
        $fpath = $blogs_dir . '/' . basename($fname);
        if (file_exists($fpath) && pathinfo($fpath, PATHINFO_EXTENSION) === 'json') {
            unlink($fpath);
            $deleted++;
        }
    }
    $_SESSION['success_message'] = "Deleted {$deleted} blog(s) successfully!";
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// ── Pagination setup ───────────────────────────────────
$per_page_opts = [25, 50, 100, 250, 500];
$per_page     = in_array((int)($_GET['per_page'] ?? 25), $per_page_opts) ? (int)($_GET['per_page'] ?? 25) : 25;
$current_page = max(1, (int)($_GET['page'] ?? 1));
$total_blogs  = count($blogs);
$total_pages  = max(1, (int)ceil($total_blogs / $per_page));
$current_page = min($current_page, $total_pages);
$offset       = ($current_page - 1) * $per_page;
$paged_blogs  = array_slice($blogs, $offset, $per_page);

$page_title = 'Manage Blogs';
include '../includes/header.php';
?>

<style>
/* ═══ Manage Blogs — Complete SEO System Theme ═══════ */
@keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }

/* ── Page wrapper ───────────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero ───────────────────────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.hero-actions { display: flex; gap: 12px; flex-shrink: 0; flex-wrap: wrap; }

/* ── Stat cards ─────────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; position: relative; transition: background .2s; }
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
.stat-card .value.sm { font-size: 1.1rem !important; }

/* ── Section card ───────────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

/* ── Action buttons ─────────────────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 6px 20px rgba(40,167,69,.35); }
.btn-action.green:hover  { box-shadow: 0 10px 30px rgba(40,167,69,.45); }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 10px 30px rgba(102,126,234,.45); }
.btn-action.red    { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 6px 20px rgba(239,68,68,.35); }
.btn-action.red:hover    { box-shadow: 0 10px 30px rgba(239,68,68,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; box-shadow: none; transform: none; }
.btn-action.sm { padding: 9px 18px; font-size: 13px; gap: 7px; }

/* ── Info banner ────────────────────────────────────── */
.info-banner { background: linear-gradient(135deg,#ecfdf5,#d1fae5); border: 2px solid #6ee7b7; border-radius: 14px; padding: 16px 24px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; color: #065f46; font-size: 14px; font-weight: 500; }
.info-banner i { font-size: 18px; color: #10b981; flex-shrink: 0; }
.info-banner a { color: #059669; font-weight: 700; text-decoration: none; }
.info-banner a:hover { text-decoration: underline; }

/* ── Alert messages ─────────────────────────────────── */
.alert { padding:15px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:center; gap:12px; font-size:14px; }
.alert-success { background:linear-gradient(135deg,#ecfdf5,#d1fae5); border:2px solid #6ee7b7; color:#065f46; }
.alert-error   { background:linear-gradient(135deg,#fef2f2,#fee2e2); border:2px solid #fca5a5; color:#991b1b; }

/* ── Toolbar ────────────────────────────────────────── */
.toolbar { display:flex; justify-content:space-between; align-items:center; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
.toolbar-left  { display:flex; align-items:center; gap:12px; }
.toolbar-right { display:flex; align-items:center; gap:12px; }
.per-page-label { font-size:13px; color:#64748b; font-weight:600; white-space:nowrap; }
.per-page-select { padding:9px 12px; border:2px solid #e2e8f0; border-radius:9px; font-size:13px; font-weight:600; color:#1e293b; cursor:pointer; outline:none; transition:border-color .2s; }
.per-page-select:focus { border-color:#667eea; }
.page-info { font-size:13px; color:#64748b; white-space:nowrap; }
.page-info strong { color:#1e293b; }
.select-all-label { display:flex; align-items:center; gap:8px; font-size:13px; font-weight:600; color:#475569; cursor:pointer; user-select:none; padding:9px 16px; border-radius:9px; border:2px solid #e2e8f0; background:white; transition:all .2s; }
.select-all-label:hover { border-color:#667eea; color:#667eea; }
.select-all-label input { width:16px; height:16px; accent-color:#667eea; cursor:pointer; }
.btn-bulk-delete { background:linear-gradient(135deg,#EF4444,#DC2626); color:white; border:none; padding:9px 18px; border-radius:9px; font-size:13px; font-weight:700; cursor:pointer; display:none; align-items:center; gap:7px; transition:all .25s; }
.btn-bulk-delete:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(239,68,68,.4); }
.btn-bulk-delete.show { display:inline-flex; }

/* ── Blog Grid ──────────────────────────────────────── */
.blog-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(340px,1fr)); gap:20px; }
.blog-card { background:white; border-radius:16px; overflow:hidden; border:2px solid #f1f5f9; transition:all .3s; position:relative; }
.blog-card:hover { border-color:#c4b5fd; box-shadow:0 12px 36px rgba(102,126,234,.15); transform:translateY(-4px); }
.blog-card.selected { border-color:#667eea !important; box-shadow:0 0 0 3px rgba(102,126,234,.2) !important; transform:translateY(-2px); }
.blog-card-check { position:absolute; top:14px; left:14px; z-index:2; width:18px; height:18px; accent-color:#667eea; cursor:pointer; }
.blog-card-header { padding:20px 20px 16px 44px; background:linear-gradient(135deg,#f8faff 0%,#eef2ff 100%); border-bottom:1px solid #e2e8f0; }
.blog-card-header h3 { font-size:14px; font-weight:700; color:#1e293b; line-height:1.5; margin:0 0 10px; }
.blog-card-meta { font-size:12px; color:#64748b; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
.blog-card-meta i { color:#667eea; }
.blog-card-body { padding:18px 20px; }
.blog-excerpt { color:#64748b; font-size:13px; line-height:1.7; margin-bottom:16px; }
.blog-actions { display:flex; gap:8px; }

/* ── Pagination ─────────────────────────────────────── */
.pagination-wrap { display:flex; justify-content:center; align-items:center; gap:6px; margin-top:24px; flex-wrap:wrap; }
.page-btn { min-width:40px; height:40px; border:2px solid #e2e8f0; background:white; border-radius:10px; font-size:14px; font-weight:600; color:#475569; cursor:pointer; display:flex; align-items:center; justify-content:center; text-decoration:none; transition:all .2s; padding:0 10px; }
.page-btn:hover { border-color:#667eea; color:#667eea; background:#f8faff; }
.page-btn.active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:transparent; box-shadow:0 4px 14px rgba(102,126,234,.45); }
.page-btn.disabled { opacity:.4; pointer-events:none; }
.page-ellipsis { color:#94a3b8; font-weight:700; padding:0 4px; }

/* ── Empty State ────────────────────────────────────── */
.empty-state { text-align:center; padding:60px 30px; }
.empty-state i { font-size:64px; color:#c4b5fd; margin-bottom:20px; display:block; }
.empty-state h3 { color:#64748b; font-size:20px; margin-bottom:8px; }
.empty-state p  { color:#94a3b8; margin-bottom:24px; font-size:15px; }

/* ── Floating bulk bar ──────────────────────────────── */
.bulk-bar { position:fixed; bottom:32px; left:50%; transform:translateX(-50%) translateY(120px); background:linear-gradient(135deg,#1e293b,#334155); color:white; border-radius:16px; padding:14px 28px; display:flex; align-items:center; gap:16px; box-shadow:0 12px 40px rgba(0,0,0,.35); transition:transform .35s cubic-bezier(.175,.885,.32,1.275); z-index:999; white-space:nowrap; }
.bulk-bar.show { transform:translateX(-50%) translateY(0); }
.bulk-bar-count { font-weight:700; font-size:15px; }
.bulk-bar-count span { color:#a78bfa; }

@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:640px) { .blog-grid{grid-template-columns:1fr;} }
@media(max-width:560px) { .seo-hero{flex-direction:column;} .stats-grid{grid-template-columns:1fr;} }
</style>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-newspaper"></i> Manage Blog Posts</h1>
        <p>View, filter and manage all <?php echo $total_blogs; ?> generated blogs</p>
    </div>
    <div class="hero-actions">
        <a href="auto-blog-dashboard.php" class="btn-action purple sm">
            <i class="fas fa-robot"></i> Blog Generator
        </a>
        <a href="<?php echo SITE_URL; ?>/blogs.php" class="btn-action green sm" target="_blank">
            <i class="fas fa-external-link-alt"></i> View on Website
        </a>
    </div>
</div>

<!-- Flash messages -->
<?php if (isset($_SESSION['success_message'])): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    <span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span>
</div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
<div class="alert alert-error">
    <i class="fas fa-exclamation-circle"></i>
    <span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span>
</div>
<?php endif; ?>

<!-- Info Banner -->
<div class="info-banner">
    <i class="fas fa-globe"></i>
    <span><strong>Blogs are live on your website:</strong>&nbsp;
        <a href="<?php echo SITE_URL; ?>/blogs.php" target="_blank">
            <?php echo SITE_URL; ?>/blogs.php <i class="fas fa-external-link-alt"></i>
        </a>
    </span>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-newspaper"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Blog Posts</h3>
            <div class="value"><?php echo $total_blogs; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-file-code"></i></div>
        <div class="stat-text-wrap">
            <h3>JSON Files Stored</h3>
            <div class="value"><?php echo $total_files; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-text-wrap">
            <h3>All-Time Generated</h3>
            <div class="value"><?php echo $stats['total_generated'] ?? $total_blogs; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-clock"></i></div>
        <div class="stat-text-wrap">
            <h3>Last Generated</h3>
            <div class="value sm"><?php echo $stats['last_generation'] ?? 'Never'; ?></div>
        </div>
    </div>
</div>

<?php if (!empty($blogs)): ?>

<!-- Section 1: All Blog Posts -->
<?php
$showing_from = $offset + 1;
$showing_to   = min($offset + $per_page, $total_blogs);
$pu = function($p, $pp) { return '?page=' . $p . '&per_page=' . $pp; };
?>
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-newspaper" style="color:#667eea;margin-right:8px;"></i>All Blog Posts</h2>
        <span style="margin-left:auto;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;"><?php echo $total_blogs; ?> Posts</span>
    </div>
    <div class="seo-section-body">

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toolbar-left">
                <label class="select-all-label">
                    <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                    Select All on Page
                </label>
                <button class="btn-bulk-delete" id="bulkDeleteBtn" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i> Delete Selected (<span id="selCount">0</span>)
                </button>
            </div>
            <div class="toolbar-right">
                <span class="page-info">Showing <strong><?php echo $showing_from; ?>–<?php echo $showing_to; ?></strong> of <strong><?php echo $total_blogs; ?></strong></span>
                <span class="per-page-label">Per page:</span>
                <select class="per-page-select" onchange="changePerPage(this.value)" id="perPageSel">
                    <?php foreach ($per_page_opts as $opt): ?>
                    <option value="<?php echo $opt; ?>" <?php echo $per_page === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Blog Grid -->
        <form method="POST" id="blogsForm">
            <input type="hidden" name="bulk_delete" value="1">
            <div class="blog-grid" id="blogGrid">
                <?php foreach ($paged_blogs as $blog): ?>
                <div class="blog-card" id="card-<?php echo htmlspecialchars($blog['file_name']); ?>">
                    <input type="checkbox" class="blog-card-check" name="selected_files[]"
                        value="<?php echo htmlspecialchars($blog['file_name']); ?>"
                        onchange="updateSelection()" title="Select">
                    <div class="blog-card-header">
                        <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
                        <div class="blog-card-meta">
                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($blog['author'] ?? 'GCM Netting Solutions'); ?></span>
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($blog['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="blog-card-body">
                        <div class="blog-excerpt"><?php echo htmlspecialchars(substr($blog['excerpt'] ?? '', 0, 150)); ?>...</div>
                        <div class="blog-actions">
                            <button type="button" class="btn-action purple sm" onclick="viewBlog('<?php echo htmlspecialchars($blog['file_name']); ?>')">
                                <i class="fas fa-eye"></i> View
                            </button>
                            <button type="button" class="btn-action red sm" onclick="deleteBlog('<?php echo htmlspecialchars($blog['file_name']); ?>')">
                                <i class="fas fa-trash"></i> Delete
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </form>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination-wrap">
            <?php if ($current_page > 1): ?>
            <a href="<?php echo $pu($current_page - 1, $per_page); ?>" class="page-btn"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php
            $range = 2;
            for ($p = 1; $p <= $total_pages; $p++):
                if ($p === 1 || $p === $total_pages || abs($p - $current_page) <= $range):
            ?>
            <a href="<?php echo $pu($p, $per_page); ?>" class="page-btn <?php echo $p === $current_page ? 'active' : ''; ?>"><?php echo $p; ?></a>
            <?php
                elseif (abs($p - $current_page) === $range + 1):
            ?>
            <span class="page-ellipsis">…</span>
            <?php
                endif;
            endfor;
            ?>

            <?php if ($current_page < $total_pages): ?>
            <a href="<?php echo $pu($current_page + 1, $per_page); ?>" class="page-btn"><i class="fas fa-chevron-right"></i></a>
            <?php else: ?>
            <span class="page-btn disabled"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php else: ?>

<div class="seo-section">
    <div class="seo-section-body">
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Blogs Generated Yet</h3>
            <p>Start generating blog posts to see them here!</p>
            <a href="auto-blog-dashboard.php" class="btn-action purple" style="margin-top:8px;">
                <i class="fas fa-robot"></i> Go to Blog Generator
            </a>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Bottom Navigation -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="auto-blog-dashboard.php" class="btn-action purple">
        <i class="fas fa-robot"></i> Blog Generator
    </a>
</div>

<!-- Floating Bulk Action Bar -->
<div class="bulk-bar" id="bulkBar">
    <span class="bulk-bar-count"><span id="bulkCount">0</span> blogs selected</span>
    <button type="button" class="btn-action red sm" onclick="bulkDelete()">
        <i class="fas fa-trash"></i> Delete Selected
    </button>
    <button type="button" class="btn-action gray sm" onclick="clearSelection()">
        <i class="fas fa-times"></i> Clear
    </button>
</div>

</div>

<!-- View Blog Modal -->
<div id="viewModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.7);backdrop-filter:blur(4px);z-index:10000;align-items:center;justify-content:center;padding:20px;">
    <div style="background:white;max-width:900px;width:100%;max-height:88vh;overflow-y:auto;border-radius:20px;padding:36px;position:relative;box-shadow:0 25px 60px rgba(0,0,0,.3);">
        <button onclick="document.getElementById('viewModal').style.display='none'" style="position:absolute;top:16px;right:20px;background:none;border:none;font-size:26px;cursor:pointer;color:#94a3b8;">&times;</button>
        <div id="modalContent"></div>
    </div>
</div>

<script>
/* ── View Blog (AJAX – content loaded on demand) ───────── */
function viewBlog(filename) {
    const modal   = document.getElementById('viewModal');
    const content = document.getElementById('modalContent');
    content.innerHTML = '<div style="text-align:center;padding:40px 20px;color:#667eea;"><i class="fas fa-spinner fa-spin fa-2x"></i><p style="margin-top:12px;color:#64748b;">Loading blog…</p></div>';
    modal.style.display = 'flex';
    modal.onclick = function(e) { if (e.target === modal) modal.style.display = 'none'; };

    fetch('manage-blogs.php?ajax_view=' + encodeURIComponent(filename))
        .then(r => r.json())
        .then(data => {
            if (!data.success) { content.innerHTML = '<p style="color:red;">Error loading blog.</p>'; return; }
            const blog = data.blog;
            const d = new Date(blog.created_at);
            const dateStr = isNaN(d.getTime()) ? blog.created_at : d.toLocaleDateString('en-IN', {day:'numeric',month:'short',year:'numeric'});
            content.innerHTML = `
                <h2 style="color:#1e293b;margin:0 0 16px;font-size:22px;line-height:1.4;">${blog.title || ''}</h2>
                <div style="background:#f8faff;padding:14px 18px;border-radius:10px;margin-bottom:24px;
                            color:#64748b;font-size:13px;display:flex;gap:18px;flex-wrap:wrap;border:2px solid #e0e7ff;">
                    <span><i class="fas fa-user" style="color:#667eea;"></i> ${blog.author || 'GCM Netting Solutions'}</span>
                    <span><i class="fas fa-calendar" style="color:#667eea;"></i> ${dateStr}</span>
                    <span><i class="fas fa-file" style="color:#667eea;"></i> ${filename}</span>
                </div>
                <div style="color:#475569;line-height:1.85;font-size:15px;">${blog.content || blog.excerpt || ''}</div>
            `;
        })
        .catch(() => { content.innerHTML = '<p style="color:red;">Failed to load blog content.</p>'; });
}

/* ── AI Protection Modal ───────────────────────────────── */
let _gcmDelFile = null;
function deleteBlog(filename) {
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
        const resp = await fetch('../api/delete-blog.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({filename: _gcmDelFile, pin, reason})
        });
        const data = await resp.json();
        if (data.success) {
            gcmCloseProtModal(); location.reload();
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
document.addEventListener('keydown', e => { if (e.key==='Escape') gcmCloseProtModal(); });

/* ── Single Delete (legacy kept for non-protected) ─────── */
function deleteBlog_direct(filename) {
    const card = document.getElementById('card-' + filename);
    if (card) { card.style.opacity='0'; card.style.transform='scale(.9)'; card.style.transition='.3s'; setTimeout(()=>{card.remove();updateSelection();},300); }
}

/* ── Select All ────────────────────────────────────────── */
function toggleSelectAll(cb) {
    document.querySelectorAll('.blog-card-check').forEach(c => {
        c.checked = cb.checked;
        c.closest('.blog-card').classList.toggle('selected', cb.checked);
    });
    updateSelection();
}

/* ── Update selection state ────────────────────────────── */
function updateSelection() {
    const checked = document.querySelectorAll('.blog-card-check:checked');
    const n = checked.length;
    document.getElementById('selCount').textContent = n;
    document.getElementById('bulkCount').textContent = n;
    const btn = document.getElementById('bulkDeleteBtn');
    const bar = document.getElementById('bulkBar');
    btn.classList.toggle('show', n > 0);
    bar.classList.toggle('show', n > 0);

    // sync select-all checkbox state
    const total = document.querySelectorAll('.blog-card-check').length;
    const sa = document.getElementById('selectAll');
    sa.indeterminate = n > 0 && n < total;
    sa.checked = n === total && total > 0;

    // highlight cards
    document.querySelectorAll('.blog-card').forEach(card => {
        const cb = card.querySelector('.blog-card-check');
        card.classList.toggle('selected', cb && cb.checked);
    });
}

/* ── Bulk Delete ───────────────────────────────────────── */
function bulkDelete() {
    alert('⚠️ Bulk delete is disabled for AI-protected content.\n\nPlease delete items individually — each requires your Security PIN and admin email approval.');
}

/* ── Clear Selection ───────────────────────────────────── */
function clearSelection() {
    document.querySelectorAll('.blog-card-check').forEach(c => { c.checked = false; });
    document.getElementById('selectAll').checked = false;
    updateSelection();
}

/* ── Change Per Page ───────────────────────────────────── */
function changePerPage(val) {
    window.location.href = '?page=1&per_page=' + val;
}

/* ── ESC closes modal ──────────────────────────────────── */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { document.getElementById('viewModal').style.display = 'none'; gcmCloseProtModal(); }
});
</script>

<!-- AI Content Protection Modal -->
<div id="gcmProtModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.78);backdrop-filter:blur(6px);z-index:20000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:white;max-width:460px;width:100%;border-radius:20px;padding:32px;box-shadow:0 25px 60px rgba(0,0,0,.35);">
    <div style="text-align:center;margin-bottom:22px;">
      <div style="width:64px;height:64px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 4px 16px rgba(217,119,6,.25);">
        <i class="fas fa-shield-alt" style="font-size:28px;color:#d97706;"></i>
      </div>
      <h3 style="margin:0 0 8px;font-size:19px;font-weight:700;color:#1e293b;">AI Content Protected</h3>
      <p style="margin:0;color:#64748b;font-size:13px;line-height:1.65;">This blog is registered in <strong>AI Content Security</strong>. Enter your <strong>Security PIN</strong> to submit a deletion request. Nothing will be deleted until you approve it via the email link sent to the admin.</p>
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
