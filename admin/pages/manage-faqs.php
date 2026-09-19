<?php
/**
 * Manage FAQs - View All Generated FAQs
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php'); exit;
}

$page_title = 'Manage FAQs';
include '../includes/header.php';

// ── Load FAQs ────────────────────────────────────────────
$faqs_dir   = dirname(dirname(__DIR__)) . '/data/faqs';
$faqs       = [];
$total_files = 0;

if (file_exists($faqs_dir) && is_dir($faqs_dir)) {
    foreach (glob($faqs_dir . '/*.json') ?: [] as $file) {
        if (in_array(basename($file), ['index.json','stats.json'])) continue;
        $total_files++;
        $d = json_decode(file_get_contents($file), true);
        if ($d && isset($d['question'])) {
            $d['file_path'] = $file;
            $d['file_name'] = basename($file);
            $faqs[] = $d;
        }
    }
}

usort($faqs, function($a, $b) { return strtotime($b['created_at']) - strtotime($a['created_at']); });

$stats_file = $faqs_dir . '/stats.json';
$stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];

// ── Build category list & colour map ─────────────────────
$all_cats = []; $cat_map = []; $cat_idx = 0;
foreach ($faqs as $f) {
    $cn = $f['category_name'] ?? $f['category'] ?? '';
    if ($cn && !in_array($cn, $all_cats)) $all_cats[] = $cn;
    if ($cn && !isset($cat_map[$cn])) $cat_map[$cn] = ($cat_idx++) % 8;
}
sort($all_cats);

// ── Filter ───────────────────────────────────────────────
$filter_cat = trim($_GET['cat'] ?? '');
$search_q   = trim($_GET['q']   ?? '');
$filtered   = $faqs;
if ($filter_cat !== '') {
    $fc = $filter_cat;
    $filtered = array_values(array_filter($filtered, function($f) use ($fc) {
        return ($f['category_name'] ?? $f['category'] ?? '') === $fc;
    }));
}
if ($search_q !== '') {
    $sq = $search_q;
    $filtered = array_values(array_filter($filtered, function($f) use ($sq) {
        return stripos($f['question'], $sq) !== false ||
               stripos(strip_tags($f['answer'] ?? ''), $sq) !== false;
    }));
}

// ── Pagination ───────────────────────────────────────────
$per_page_options = [25, 50, 100, 250, 500];
$per_page_raw = (int)(isset($_GET['per_page']) ? $_GET['per_page'] : 50);
$per_page     = in_array($per_page_raw, $per_page_options) ? $per_page_raw : 50;
$total_filt  = count($filtered);
$total_pages = max(1, (int)ceil($total_filt / $per_page));
$cur_page    = min(max(1, (int)($_GET['page'] ?? 1)), $total_pages);
$offset      = ($cur_page - 1) * $per_page;
$paged_faqs  = array_slice($filtered, $offset, $per_page);

// Helper to build URL preserving current params
function pager_url($override = array()) {
    $p = array_merge(array(
        'cat'      => isset($_GET['cat'])      ? $_GET['cat']      : '',
        'q'        => isset($_GET['q'])        ? $_GET['q']        : '',
        'per_page' => isset($_GET['per_page']) ? $_GET['per_page'] : 50,
        'page'     => 1
    ), $override);
    $p = array_filter($p, function($v) { return $v !== '' && $v !== null; });
    return '?' . http_build_query($p);
}
?>

<style>
/* ── Manage FAQs — Complete SEO System theme ─── */
.seo-page { padding: 0; }
.seo-hero { background:white; border-radius:18px; box-shadow:0 4px 24px rgba(0,0,0,.07); padding:32px 36px; margin-bottom:24px; display:flex; align-items:center; justify-content:space-between; gap:20px; border-left:6px solid transparent; border-image:linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size:30px; font-weight:800; color:#1e293b; margin:0 0 6px; display:flex; align-items:center; gap:12px; }
.seo-hero-left h1 i { color:#667eea; }
.seo-hero-left p { color:#64748b; font-size:15px; margin:0; }
.seo-hero-badge { background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:8px 20px; border-radius:30px; font-size:13px; font-weight:700; white-space:nowrap; flex-shrink:0; }
.hero-actions { display:flex; gap:12px; flex-shrink:0; flex-wrap:wrap; }
.solution-banner { background:linear-gradient(135deg,rgba(40,167,69,.08),rgba(32,201,151,.05)); border:1px solid rgba(40,167,69,.2); border-left:5px solid #28a745; border-radius:12px; padding:18px 22px; margin-bottom:24px; display:flex; align-items:flex-start; gap:14px; }
.solution-banner .sol-icon { width:40px; height:40px; background:linear-gradient(135deg,#28a745,#20c997); border-radius:10px; display:flex; align-items:center; justify-content:center; color:white; font-size:18px; flex-shrink:0; margin-top:2px; }
.solution-banner strong { color:#166534; font-size:14px; font-weight:700; display:block; margin-bottom:4px; }
.solution-banner p { color:#15803d; font-size:13.5px; margin:0; line-height:1.6; }
.solution-banner a { color:#059669; font-weight:700; }

/* ── old page-header (neutralised) ── */
.manage-faqs { padding: 0; }
.page-header { display:none; }
.header-actions { display:none; }

/* ── Stats grid ── */
.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:0; background:white; border-radius:16px; box-shadow:0 4px 20px rgba(0,0,0,.08); overflow:hidden; margin-bottom:24px; }
.stat-card { background:white; padding:24px 20px; display:flex; align-items:center; gap:14px; position:relative; transition:background .2s; border-radius:0; box-shadow:none; overflow:visible; }
.stat-card:hover { background:#f8faff; }
.stat-card + .stat-card::before { content:''; position:absolute; left:0; top:18%; bottom:18%; width:1px; background:#e2e8f0; }
.stat-card::after { display:none; }
.stat-icon { width:50px; height:50px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:20px; color:white; flex-shrink:0; }
.stat-card.blue .stat-icon   { background:linear-gradient(135deg,#667eea,#764ba2); }
.stat-card.green .stat-icon  { background:linear-gradient(135deg,#ec4899,#a855f7); }
.stat-card.orange .stat-icon { background:linear-gradient(135deg,#3b82f6,#06b6d4); }
.stat-card.teal .stat-icon   { background:linear-gradient(135deg,#10b981,#059669); }
.stat-text { flex:1; min-width:0; }
.stat-card h3 { font-size:2rem !important; font-weight:800 !important; color:#1e293b !important; line-height:1.1 !important; margin:0 0 5px !important; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.stat-card h3.last-gen { font-size:1.25rem !important; line-height:1.3 !important; white-space:normal; }
.stat-card p { font-size:11px !important; font-weight:700 !important; color:#94a3b8 !important; text-transform:uppercase !important; letter-spacing:.6px !important; margin:0 !important; }
/* ── Section cards ── */
.seo-section { background:white; border-radius:18px; box-shadow:0 4px 20px rgba(0,0,0,.07); margin-bottom:24px; overflow:hidden; }
.seo-section-head { padding:20px 28px; border-bottom:1px solid #f1f5f9; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
.seo-section-head .sec-num { width:34px; height:34px; border-radius:9px; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; color:white; flex-shrink:0; }
.sec-num.purple { background:linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background:linear-gradient(135deg,#3b82f6,#2563eb); }
.seo-section-head h2 { font-size:19px; font-weight:700; color:#1e293b; margin:0; }
.seo-section-body { padding:24px 28px; }
/* ── Buttons ── */
.btn { padding:10px 20px; border-radius:10px; border:none; font-weight:600; font-size:14px; cursor:pointer; transition:all .25s; text-decoration:none; display:inline-flex; align-items:center; gap:8px; white-space:nowrap; }
.btn-primary { background:linear-gradient(135deg,#667eea,#764ba2); color:white; }
.btn-primary:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(102,126,234,.4); color:white; text-decoration:none; }
.btn-success { background:linear-gradient(135deg,#10B981,#059669); color:white; }
.btn-success:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(16,185,129,.4); color:white; text-decoration:none; }
.btn-danger { background:linear-gradient(135deg,#EF4444,#DC2626); color:white; }
.btn-danger:hover { transform:translateY(-2px); box-shadow:0 6px 20px rgba(239,68,68,.4); color:white; text-decoration:none; }

/* ── Storage path ── */
.storage-path { background:#f1f5f9; border:1px solid #e2e8f0; padding:10px 14px; border-radius:8px; font-family:monospace; font-size:13px; color:#334155; margin:10px 0; word-break:break-all; }
.storage-meta { display:flex; gap:10px; flex-wrap:wrap; margin-top:10px; }
.storage-meta span { font-size:13px; color:#475569; background:#f8fafc; border:1px solid #e2e8f0; padding:5px 12px; border-radius:8px; }
/* ── FAQ table ── */
.faq-table-header { padding:16px 24px; background:#f8faff; border-bottom:2px solid #e0e7ff; display:flex; align-items:center; justify-content:space-between; gap:12px; }
.faq-table-header h2 { font-size:16px; font-weight:700; color:#1e293b; display:flex; align-items:center; gap:10px; margin:0; }
.faq-table-header h2 i { color:#667eea; }
.faq-count-badge { background:linear-gradient(135deg,#667eea,#764ba2); color:white; padding:5px 14px; border-radius:20px; font-size:13px; font-weight:700; }
.faq-table { width:100%; border-collapse:collapse; }
.faq-table th { background:linear-gradient(135deg,#f8fafc,#eef2ff); padding:13px 14px; text-align:left; font-weight:700; font-size:12px; color:#475569; text-transform:uppercase; letter-spacing:.6px; border-bottom:2px solid #e2e8f0; }
.faq-table td { padding:14px; border-bottom:1px solid #f1f5f9; vertical-align:top; }
.faq-table tbody tr:last-child td { border-bottom:none; }
.faq-table tbody tr:hover { background:linear-gradient(135deg,rgba(102,126,234,.04),rgba(118,75,162,.04)); }
.faq-table tbody tr:nth-child(even) { background:#fafbff; }
.faq-table tbody tr:nth-child(even):hover { background:linear-gradient(135deg,rgba(102,126,234,.06),rgba(118,75,162,.06)); }
.row-num { width:34px; height:34px; background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-size:12px; font-weight:700; }
.faq-question-cell { font-weight:600; color:#1e293b; font-size:14px; line-height:1.5; max-width:380px; }
.faq-answer-preview { color:#94a3b8; font-size:13px; margin-top:5px; line-height:1.5; }
.cat-badge { display:inline-block; padding:4px 11px; border-radius:20px; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.5px; white-space:nowrap; }
.cat-badge.c0 { background:#ede9fe; color:#5b21b6; }
.cat-badge.c1 { background:#d1fae5; color:#065f46; }
.cat-badge.c2 { background:#fef3c7; color:#92400e; }
.cat-badge.c3 { background:#fee2e2; color:#991b1b; }
.cat-badge.c4 { background:#e0f2fe; color:#075985; }
.cat-badge.c5 { background:#fce7f3; color:#9d174d; }
.cat-badge.c6 { background:#ecfdf5; color:#065f46; }
.cat-badge.c7 { background:#fff7ed; color:#9a3412; }
.date-cell { font-size:13px; color:#475569; white-space:nowrap; }
.date-cell small { color:#94a3b8; display:block; margin-top:2px; }
.file-cell { font-family:monospace; font-size:11px; color:#94a3b8; max-width:130px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.action-buttons { display:flex; gap:8px; }
.btn-icon { width:32px; height:32px; border-radius:9px; border:none; cursor:pointer; transition:all .2s; display:flex; align-items:center; justify-content:center; font-size:13px; }
.btn-icon.view   { background:#eef2ff; color:#667eea; }
.btn-icon.delete { background:#fee2e2; color:#ef4444; }
.btn-icon.view:hover   { background:linear-gradient(135deg,#667eea,#764ba2); color:white; transform:scale(1.1); }
.btn-icon.delete:hover { background:linear-gradient(135deg,#ef4444,#dc2626); color:white; transform:scale(1.1); }
/* ── Toolbar ── */
.table-toolbar { display:flex; align-items:center; gap:10px; flex-wrap:wrap; padding:14px 20px; background:#f8faff; border-bottom:1px solid #e0e7ff; }
.toolbar-input { flex:1; min-width:160px; max-width:260px; padding:9px 14px; border:1.5px solid #e2e8f0; border-radius:9px; font-size:13px; outline:none; transition:border-color .2s; }
.toolbar-input:focus { border-color:#667eea; }
.toolbar-select { padding:9px 12px; border:1.5px solid #e2e8f0; border-radius:9px; font-size:13px; outline:none; background:white; cursor:pointer; transition:border-color .2s; }
.toolbar-select:focus { border-color:#667eea; }
.toolbar-sep { width:1px; height:28px; background:#e2e8f0; margin:0 4px; }
.bulk-bar { display:none; align-items:center; gap:10px; padding:10px 20px; background:#fff3cd; border-bottom:1px solid #ffc107; font-size:14px; font-weight:600; color:#92400e; }
.bulk-bar.show { display:flex; }
.btn-bulk-delete { padding:7px 18px; background:linear-gradient(135deg,#ef4444,#dc2626); color:white; border:none; border-radius:8px; font-size:13px; font-weight:700; cursor:pointer; transition:all .2s; }
.btn-bulk-delete:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(239,68,68,.4); }
/* ── Pagination ── */
.pager { display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding:16px 20px; border-top:2px solid #f1f5f9; background:#fafbff; }
.pager-info { font-size:13px; color:#64748b; }
.pager-links { display:flex; gap:5px; flex-wrap:wrap; }
.pager-btn { display:inline-flex; align-items:center; justify-content:center; min-width:36px; height:36px; padding:0 10px; border-radius:8px; background:white; color:#475569; text-decoration:none; font-size:13px; font-weight:600; border:1.5px solid #e2e8f0; transition:all .2s; }
.pager-btn:hover { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:transparent; text-decoration:none; }
.pager-btn.active { background:linear-gradient(135deg,#667eea,#764ba2); color:white; border-color:transparent; pointer-events:none; }
.pager-btn.disabled { opacity:.4; pointer-events:none; }
/* ── Checkbox ── */
.cb-th, .cb-td { width:44px; text-align:center; }
input[type=checkbox].row-cb { width:16px; height:16px; accent-color:#667eea; cursor:pointer; }
/* ── Empty state ── */
.empty-state { text-align:center; padding:60px 30px; }
.empty-state i { font-size:64px; color:#cbd5e1; margin-bottom:16px; display:block; }
.empty-state h3 { color:#64748b; font-size:20px; margin-bottom:8px; }
.empty-state p { color:#94a3b8; }
/* ── Table scroll wrapper ── */
.faq-table-wrapper { overflow-x: auto; -webkit-overflow-scrolling: touch; }
.faq-table { min-width: 700px; }
/* ── Responsive ── */
@media (max-width:1024px) { .stats-grid { grid-template-columns:repeat(2,1fr); } }
@media (max-width:768px) {
    .seo-hero { flex-direction:column; align-items:flex-start; gap:12px; padding:18px 16px; }
    .hero-actions { flex-wrap:wrap; gap:8px; }
    .table-toolbar { flex-wrap:wrap; gap:8px; }
    .toolbar-input { min-width:100%; max-width:none; }
    .toolbar-select { width:100%; }
    .stats-grid { grid-template-columns:repeat(2,1fr) !important; }
    .stat-card + .stat-card::before { display:none !important; }
    .stat-card { border:1px solid #f1f5f9 !important; border-radius:14px !important; }
    .seo-section-head { flex-wrap:wrap; gap:8px; padding:14px 16px !important; }
}
@media (max-width:640px) { .stats-grid { grid-template-columns:1fr 1fr !important; } }
@media (max-width:480px) { .stats-grid { grid-template-columns:1fr 1fr !important; } .stat-card { padding:14px 10px !important; } }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-question-circle"></i> Manage FAQs</h1>
        <p>View, filter and manage all <?php echo number_format(count($faqs)); ?> AI-generated FAQs</p>
    </div>
    <div class="hero-actions">
        <a href="content-generator.php" class="btn btn-primary"><i class="fas fa-plus"></i> Generate New</a>
        <a href="<?php echo SITE_URL; ?>/faqs.php" class="btn btn-success" target="_blank"><i class="fas fa-external-link-alt"></i> View on Website</a>
    </div>
</div>

<!-- Info Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-globe"></i></div>
    <div>
        <strong>FAQs Are Live on Your Website</strong>
        <p>Publicly accessible at: <a href="<?php echo SITE_URL; ?>/faqs.php" target="_blank"><?php echo SITE_URL; ?>/faqs.php <i class="fas fa-external-link-alt"></i></a></p>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card blue">
        <div class="stat-icon"><i class="fas fa-question-circle"></i></div>
        <div class="stat-text"><h3><?php echo count($faqs); ?></h3><p>Total FAQs Generated</p></div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><i class="fas fa-file-code"></i></div>
        <div class="stat-text"><h3><?php echo $total_files; ?></h3><p>JSON Files Stored</p></div>
    </div>
    <div class="stat-card orange">
        <div class="stat-icon"><i class="fas fa-chart-line"></i></div>
        <div class="stat-text"><h3><?php echo $stats['total_generated'] ?? count($faqs); ?></h3><p>All-Time Generated</p></div>
    </div>
    <div class="stat-card teal">
        <div class="stat-icon"><i class="fas fa-clock"></i></div>
        <?php $lg = $stats['last_generation'] ?? null; ?>
        <div class="stat-text"><h3 class="last-gen"><?php echo $lg ? date('M j, Y', strtotime($lg)).'<br>'.date('g:i A', strtotime($lg)) : 'Never'; ?></h3><p>Last Generated</p></div>
    </div>
</div>

<!-- Section 1: Storage Info -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-folder-open" style="color:#667eea;margin-right:8px;"></i>FAQ Storage Location</h2>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;font-size:14px;margin:0 0 10px;">FAQs are stored as individual JSON files in the following directory:</p>
        <div class="storage-path">📁 <?php echo $faqs_dir; ?></div>
        <div class="storage-meta">
            <span><strong><?php echo $total_files; ?></strong> Total Files</span>
            <span><?php echo file_exists($faqs_dir) ? '✅ Directory Exists' : '❌ Directory Missing'; ?></span>
            <span><?php echo is_writable($faqs_dir) ? '✅ Writable' : '❌ Not Writable'; ?></span>
        </div>
    </div>
</div>

<!-- Section 2: FAQs Table -->
<?php if (!empty($faqs)): ?>
<div class="seo-section" style="margin-bottom:32px;">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-list-ul" style="color:#3b82f6;margin-right:8px;"></i>All FAQs
            <?php if ($filter_cat || $search_q): ?><span style="font-size:13px;font-weight:400;color:#94a3b8;margin-left:6px;">(filtered)</span><?php endif; ?>
        </h2>
        <span class="faq-count-badge" style="margin-left:auto;"><?php echo $total_filt; ?> of <?php echo count($faqs); ?> FAQs</span>
    </div>

    <form method="get" class="table-toolbar" id="filterForm">
        <input type="text" name="q" class="toolbar-input" placeholder="🔍  Search question or answer…" value="<?php echo htmlspecialchars($search_q); ?>">
        <select name="cat" class="toolbar-select" onchange="document.getElementById('filterForm').submit()">
            <option value="">All Categories</option>
            <?php foreach ($all_cats as $c): ?>
                <option value="<?php echo htmlspecialchars($c); ?>" <?php echo $filter_cat === $c ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
            <?php endforeach; ?>
        </select>
        <div class="toolbar-sep"></div>
        <label style="font-size:13px;color:#64748b;white-space:nowrap;">Show:</label>
        <select name="per_page" class="toolbar-select" onchange="document.getElementById('filterForm').submit()">
            <?php foreach ($per_page_options as $opt): ?>
                <option value="<?php echo $opt; ?>" <?php echo $per_page == $opt ? 'selected' : ''; ?>><?php echo $opt; ?> per page</option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-success" style="border:none;padding:9px 18px;font-size:13px;"><i class="fas fa-filter"></i> Filter</button>
        <?php if ($filter_cat || $search_q): ?>
        <a href="?per_page=<?php echo $per_page; ?>" class="btn btn-danger" style="padding:9px 16px;font-size:13px;"><i class="fas fa-times"></i> Clear</a>
        <?php endif; ?>
    </form>

    <div class="bulk-bar" id="bulkBar">
        <i class="fas fa-check-square"></i>
        <span id="bulkCount">0</span> FAQ(s) selected
        <button class="btn-bulk-delete" onclick="bulkDelete()"><i class="fas fa-trash"></i> Delete Selected</button>
        <button onclick="clearSelection()" style="background:none;border:none;color:#92400e;cursor:pointer;font-weight:600;font-size:13px;padding:4px 8px;">✕ Clear</button>
    </div>

    <div class="faq-table-wrapper">
    <table class="faq-table" id="faqTable">
        <thead>
            <tr>
                <th class="cb-th"><input type="checkbox" class="row-cb" id="selectAll" title="Select All"></th>
                <th>#</th>
                <th>Question &amp; Preview</th>
                <th>Category</th>
                <th>Created</th>
                <th>File</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($paged_faqs as $pi => $faq):
                $cn = $faq['category_name'] ?? $faq['category'] ?? '';
                $ci = $cat_map[$cn] ?? 0;
                $abs_num = $offset + $pi + 1;
                $faq_json = htmlspecialchars(json_encode($faq), ENT_QUOTES);
            ?>
                <tr>
                    <td class="cb-td"><input type="checkbox" class="row-cb item-cb" value="<?php echo htmlspecialchars($faq['file_name']); ?>" onchange="updateBulkBar()"></td>
                    <td><span class="row-num"><?php echo $abs_num; ?></span></td>
                    <td>
                        <div class="faq-question-cell"><?php echo htmlspecialchars($faq['question']); ?></div>
                        <div class="faq-answer-preview"><?php echo substr(strip_tags($faq['answer']), 0, 110); ?>…</div>
                    </td>
                    <td><span class="cat-badge c<?php echo $ci; ?>"><?php echo htmlspecialchars($cn); ?></span></td>
                    <td class="date-cell">
                        <?php echo date('M j, Y', strtotime($faq['created_at'])); ?>
                        <small><?php echo date('g:i A', strtotime($faq['created_at'])); ?></small>
                    </td>
                    <td class="file-cell" title="<?php echo htmlspecialchars($faq['file_name']); ?>"><?php echo htmlspecialchars($faq['file_name']); ?></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-icon view" onclick='viewFAQ(<?php echo $faq_json; ?>)' title="View FAQ"><i class="fas fa-eye"></i></button>
                            <button class="btn-icon delete" onclick="deleteFAQ('<?php echo htmlspecialchars($faq['file_name']); ?>')" title="Delete FAQ"><i class="fas fa-trash"></i></button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div><!-- /.faq-table-wrapper -->

    <?php if ($total_pages > 1 || $total_filt > 25): ?>
    <div class="pager">
        <span class="pager-info">Showing <?php echo $offset+1; ?>–<?php echo min($offset+$per_page,$total_filt); ?> of <?php echo $total_filt; ?> FAQs</span>
        <div class="pager-links">
            <a href="<?php echo pager_url(['page'=>$cur_page-1,'per_page'=>$per_page]); ?>" class="pager-btn <?php echo $cur_page<=1 ? 'disabled' : ''; ?>">&#8249; Prev</a>
            <?php
            $pstart = max(1, $cur_page-3); $pend = min($total_pages, $cur_page+3);
            if ($pstart > 1) echo '<span style="color:#a0aec0;padding:0 4px;">…</span>';
            for ($pp = $pstart; $pp <= $pend; $pp++): ?>
                <a href="<?php echo pager_url(['page'=>$pp,'per_page'=>$per_page]); ?>" class="pager-btn <?php echo $pp==$cur_page ? 'active' : ''; ?>"><?php echo $pp; ?></a>
            <?php endfor;
            if ($pend < $total_pages) echo '<span style="color:#a0aec0;padding:0 4px;">…</span>'; ?>
            <a href="<?php echo pager_url(['page'=>$cur_page+1,'per_page'=>$per_page]); ?>" class="pager-btn <?php echo $cur_page>=$total_pages ? 'disabled' : ''; ?>">Next &#8250;</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php else: ?>
<div class="seo-section" style="margin-bottom:32px;">
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3><?php echo ($filter_cat || $search_q) ? 'No FAQs Match Your Filter' : 'No FAQs Generated Yet'; ?></h3>
        <p><?php echo ($filter_cat || $search_q) ? 'Try clearing the filter.' : 'Start generating FAQs to see them here!'; ?></p>
        <?php if ($filter_cat || $search_q): ?>
            <a href="?per_page=<?php echo $per_page; ?>" class="btn btn-success" style="margin-top:20px;border:none;"><i class="fas fa-times"></i> Clear Filter</a>
        <?php else: ?>
            <a href="content-generator.php" class="btn btn-success" style="margin-top:20px;border:none;"><i class="fas fa-magic"></i> Generate Your First FAQ</a>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

</div>

<script>
// ── View FAQ (receives full faq object) ───────────────────
function viewFAQ(faq) {
    const existing = document.getElementById('faqModal');
    if (existing) existing.remove();
    const modal = `
        <div id="faqModal" style="position:fixed;inset:0;background:rgba(15,23,42,.7);backdrop-filter:blur(4px);z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;" onclick="document.getElementById('faqModal').remove()">
            <div style="background:white;max-width:720px;width:100%;max-height:90vh;overflow-y:auto;border-radius:20px;box-shadow:0 25px 60px rgba(0,0,0,.3);" onclick="event.stopPropagation()">
                <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:28px 32px;border-radius:20px 20px 0 0;">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:16px;">
                        <h2 style="color:white;font-size:18px;font-weight:700;line-height:1.5;flex:1;">${faq.question}</h2>
                        <button onclick="document.getElementById('faqModal').remove()" style="width:34px;height:34px;background:rgba(255,255,255,.2);border:none;border-radius:50%;color:white;cursor:pointer;font-size:18px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">&times;</button>
                    </div>
                </div>
                <div style="padding:24px 32px;">
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">
                        <span style="background:#ede9fe;color:#5b21b6;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;">${faq.category_name||faq.category||'—'}</span>
                        <span style="background:#e0f2fe;color:#075985;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:600;">${new Date(faq.created_at).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'})}</span>
                        <span style="background:#f1f5f9;color:#64748b;padding:5px 14px;border-radius:20px;font-size:11px;font-family:monospace;">${faq.file_name}</span>
                    </div>
                    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;color:#334155;font-size:15px;line-height:1.8;">
                        ${faq.answer}
                    </div>
                    <div style="margin-top:20px;text-align:right;">
                        <button onclick="document.getElementById('faqModal').remove()" style="padding:11px 28px;background:linear-gradient(135deg,#667eea,#764ba2);color:white;border:none;border-radius:10px;cursor:pointer;font-weight:700;font-size:14px;">Close</button>
                    </div>
                </div>
            </div>
        </div>`;
    document.body.insertAdjacentHTML('beforeend', modal);
}

// ── AI Protection Modal ──────────────────────────────────
let _gcmDelFile = null;
function deleteFAQ(filename) {
    _gcmDelFile = filename;
    const m = document.getElementById('gcmProtModal');
    document.getElementById('gcmProtFile').textContent = filename;
    document.getElementById('gcmProtPin').value = '';
    document.getElementById('gcmProtReason').value = '';
    document.getElementById('gcmProtMsg').style.display = 'none';
    const btn = document.getElementById('gcmProtSubmit');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
    m.style.display = 'flex';
    setTimeout(() => document.getElementById('gcmProtPin').focus(), 100);
}
function gcmCloseProtModal() {
    document.getElementById('gcmProtModal').style.display = 'none';
    _gcmDelFile = null;
}
function gcmShowProtMsg(text, type) {
    const el = document.getElementById('gcmProtMsg');
    el.style.cssText = 'display:block;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:10px;' +
        (type==='success'
            ? 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;'
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
        const resp = await fetch('../api/delete-faq.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
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

// ── Select-all checkbox ───────────────────────────────────
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.item-cb').forEach(cb => cb.checked = this.checked);
    updateBulkBar();
});

function updateBulkBar() {
    const checked = document.querySelectorAll('.item-cb:checked');
    const bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked.length;
    bar.classList.toggle('show', checked.length > 0);
}

function clearSelection() {
    document.querySelectorAll('.item-cb,.row-cb').forEach(cb => cb.checked = false);
    document.getElementById('selectAll').checked = false;
    updateBulkBar();
}

// ── Bulk delete ───────────────────────────────────────────
function bulkDelete() {
    alert('⚠️ Bulk delete is disabled for AI-protected content.\n\nPlease delete items individually — each requires your Security PIN and admin email approval.');
}
</script>

<!-- AI Content Protection Modal -->
<div id="gcmProtModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.78);backdrop-filter:blur(6px);z-index:20000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:white;max-width:460px;width:100%;border-radius:20px;padding:32px;box-shadow:0 25px 60px rgba(0,0,0,.35);position:relative;">
    <div style="text-align:center;margin-bottom:22px;">
      <div style="width:64px;height:64px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 4px 16px rgba(217,119,6,.25);">
        <i class="fas fa-shield-alt" style="font-size:28px;color:#d97706;"></i>
      </div>
      <h3 style="margin:0 0 8px;font-size:19px;font-weight:700;color:#1e293b;">AI Content Protected</h3>
      <p style="margin:0;color:#64748b;font-size:13px;line-height:1.65;">This content is registered in <strong>AI Content Security</strong>. Enter your <strong>Security PIN</strong> to submit a deletion request. Nothing will be deleted until you approve it via the email link sent to the admin.</p>
    </div>
    <div style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:9px;padding:9px 13px;margin-bottom:18px;font-size:12px;color:#64748b;word-break:break-all;">
      <i class="fas fa-file" style="color:#667eea;margin-right:5px;"></i><span id="gcmProtFile"></span>
    </div>
    <div style="margin-bottom:14px;">
      <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Security PIN <span style="color:#ef4444;">*</span></label>
      <input id="gcmProtPin" type="password" maxlength="6" placeholder="••••••"
             style="width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:22px;letter-spacing:8px;text-align:center;outline:none;box-sizing:border-box;transition:border-color .2s;"
             onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"
             onkeydown="if(event.key==='Enter')gcmSubmitProtDel()">
    </div>
    <div style="margin-bottom:18px;">
      <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Reason for deletion <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
      <textarea id="gcmProtReason" rows="2" placeholder="Why do you need to delete this content?"
                style="width:100%;padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:13px;resize:none;outline:none;box-sizing:border-box;transition:border-color .2s;"
                onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
    </div>
    <div id="gcmProtMsg" style="display:none;"></div>
    <div style="display:flex;gap:10px;">
      <button onclick="gcmCloseProtModal()" style="flex:1;padding:12px;background:#f1f5f9;border:none;border-radius:10px;font-size:14px;font-weight:600;color:#64748b;cursor:pointer;transition:background .2s;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">Cancel</button>
      <button id="gcmProtSubmit" onclick="gcmSubmitProtDel()" style="flex:2;padding:12px;background:linear-gradient(135deg,#ef4444,#dc2626);border:none;border-radius:10px;font-size:14px;font-weight:700;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:opacity .2s;">
        <i class="fas fa-paper-plane"></i> Submit Request
      </button>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
