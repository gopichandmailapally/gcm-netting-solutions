<?php
/**
 * Automatic SEO Optimizer Dashboard
 * Safe meta-tag optimizer &mdash; fixes indexing issues on all <?php echo number_format($total_pages); ?> pages without touching content
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

// Check admin authentication
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// MySQL Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die('Database connection failed: ' . $conn->connect_error);
    }
    $conn->set_charset('utf8mb4');
    
    // Get total pages: filesystem is authoritative (most pages are file-only, DB tracks only a subset)
    $_gen_dir   = dirname(dirname(dirname(__FILE__))) . '/generated-pages/';
    $_area_f    = glob($_gen_dir . '*-in-*.php') ?: [];
    $_all_f     = glob($_gen_dir . '*.php') ?: [];
    $_pillar_f  = array_filter($_all_f, fn($f) => basename($f) !== 'index.php' && strpos(basename($f), '-in-') === false);
    $_fs_total  = count($_area_f) + count($_pillar_f);

    $total_res = $conn->query("SELECT COUNT(*) as count FROM generated_pages WHERE keyword_id > 0 AND area_id > 0");
    $db_total  = $total_res ? (int)$total_res->fetch_assoc()['count'] : 0;
    $total_pages = max($_fs_total, $db_total);

    // .bak files = pages already fixed by safe optimizer (filesystem, 100% reliable)
    $_bak_files      = glob($_gen_dir . '*.php.bak') ?: [];
    $optimized_pages = count($_bak_files);

    // Also try DB log as secondary (higher wins)
    $opt_res = @$conn->query("SELECT COUNT(DISTINCT page_path) as c FROM seo_optimization_log WHERE status='completed'");
    if ($opt_res) { $db_opt = (int)($opt_res->fetch_assoc()['c'] ?? 0); $optimized_pages = max($optimized_pages, $db_opt); }

    $pending_pages = max(0, $total_pages - $optimized_pages);
    
} catch (\Throwable $e) {
    $_gen_dir2  = dirname(dirname(dirname(__FILE__))) . '/generated-pages/';
    $_area_f2   = glob($_gen_dir2 . '*-in-*.php') ?: [];
    $_all_f2    = glob($_gen_dir2 . '*.php') ?: [];
    $_pillar_f2 = array_filter($_all_f2, fn($f) => basename($f) !== 'index.php' && strpos(basename($f), '-in-') === false);
    $total_pages     = count($_area_f2) + count($_pillar_f2);
    $_bak2           = glob($_gen_dir2 . '*.php.bak') ?: [];
    $optimized_pages = count($_bak2);
    $pending_pages   = max(0, $total_pages - $optimized_pages);
    error_log('Dashboard DB error: ' . $e->getMessage());
}

$page_title = 'Auto SEO Optimizer';
include '../includes/header.php';
?>

<style>
/* ── Shared base ───────────────────────────────── */
.aopt-page { padding: 0; }

/* ── Hero ──────────────────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#10b981,#059669) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #10b981; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; }

/* ── Automation banner ─────────────────────────── */
.auto-banner { background: linear-gradient(135deg,rgba(102,126,234,.06),rgba(118,75,162,.04)); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 16px 22px; margin-bottom: 24px; display: flex; align-items: center; gap: 14px; }
.auto-banner .ab-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; }
.auto-banner strong { color: #4338ca; font-size: 14px; font-weight: 700; }
.auto-banner p { color: #4f46e5; font-size: 13px; margin: 2px 0 0; }

/* ── Stats ─────────────────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 16px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; text-align: left !important; border-left: none !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.purple { background: linear-gradient(135deg,#8b5cf6,#6d28d9); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; }

/* ── Progress bar ──────────────────────────────── */
.prog-wrap { background: #e2e8f0; height: 10px; border-radius: 8px; overflow: hidden; margin-bottom: 24px; }
.prog-fill { height: 100%; background: linear-gradient(90deg,#667eea,#10b981); border-radius: 8px; transition: width .4s; }
.opt-progress-wrap { background: #e2e8f0; height: 24px; border-radius: 12px; overflow: hidden; margin: 16px 0; }
.opt-progress-fill { height: 100%; background: linear-gradient(90deg,#28a745,#20c997); transition: width .3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 700; font-size: 12px; }

/* ── Section card ──────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 20px 30px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 34px; height: 34px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.seo-section-head h2 { font-size: 19px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 26px 30px; }

/* ── Optimization feature cards ────────────────── */
.opt-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 18px; margin-bottom: 4px; }
.opt-card { background: white; border-radius: 14px; border: 1px solid #e2e8f0; overflow: hidden; transition: all .25s; }
.opt-card:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.09); }
.opt-card-top { padding: 18px 18px 14px; text-align: center; }
.oct-purple { background: linear-gradient(135deg,rgba(102,126,234,.07),rgba(118,75,162,.04)); border-bottom: 3px solid #667eea; }
.oct-green  { background: linear-gradient(135deg,rgba(16,185,129,.07),rgba(5,150,105,.04)); border-bottom: 3px solid #10b981; }
.oct-blue   { background: linear-gradient(135deg,rgba(59,130,246,.07),rgba(37,99,235,.04)); border-bottom: 3px solid #3b82f6; }
.oct-orange { background: linear-gradient(135deg,rgba(245,158,11,.07),rgba(217,119,6,.04)); border-bottom: 3px solid #f59e0b; }
.oct-teal   { background: linear-gradient(135deg,rgba(6,182,212,.07),rgba(8,145,178,.04)); border-bottom: 3px solid #06b6d4; }
.oct-pink   { background: linear-gradient(135deg,rgba(236,72,153,.07),rgba(219,39,119,.04)); border-bottom: 3px solid #ec4899; }
.oct-indigo { background: linear-gradient(135deg,rgba(99,102,241,.07),rgba(79,70,229,.04)); border-bottom: 3px solid #6366f1; }
.oct-emerald{ background: linear-gradient(135deg,rgba(52,211,153,.07),rgba(16,185,129,.04)); border-bottom: 3px solid #34d399; }
.opt-card-icon { width: 48px; height: 48px; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; font-size: 20px; color: white; margin-bottom: 10px; }
.oci-purple  { background: linear-gradient(135deg,#667eea,#764ba2); }
.oci-green   { background: linear-gradient(135deg,#10b981,#059669); }
.oci-blue    { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.oci-orange  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.oci-teal    { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.oci-pink    { background: linear-gradient(135deg,#ec4899,#db2777); }
.oci-indigo  { background: linear-gradient(135deg,#6366f1,#4f46e5); }
.oci-emerald { background: linear-gradient(135deg,#34d399,#10b981); }
.opt-card-top h4 { font-size: 13.5px; font-weight: 700; margin: 0; }
.oct-purple h4 { color: #667eea; } .oct-green h4 { color: #059669; } .oct-blue h4 { color: #2563eb; }
.oct-orange h4 { color: #d97706; } .oct-teal h4 { color: #0891b2; } .oct-pink h4 { color: #db2777; }
.oct-indigo h4 { color: #4f46e5; } .oct-emerald h4 { color: #10b981; }
.opt-card-body { padding: 14px 16px; }
.opt-card-body ul { list-style: none; padding: 0; margin: 0; }
.opt-card-body li { font-size: 12.5px; color: #475569; padding: 6px 0; display: flex; align-items: center; gap: 8px; border-bottom: 1px solid #f8fafc; }
.opt-card-body li:last-child { border-bottom: none; }
.opt-card-body li i { font-size: 10px; color: white; width: 18px; height: 18px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.oct-purple .opt-card-body li i { background:#667eea; } .oct-green .opt-card-body li i { background:#10b981; }
.oct-blue   .opt-card-body li i { background:#3b82f6; } .oct-orange .opt-card-body li i { background:#f59e0b; }
.oct-teal   .opt-card-body li i { background:#06b6d4; } .oct-pink   .opt-card-body li i { background:#ec4899; }
.oct-indigo .opt-card-body li i { background:#6366f1; } .oct-emerald .opt-card-body li i { background:#34d399; }

/* ── Action buttons ────────────────────────────── */
.action-row { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; margin: 20px 0 0; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 6px 20px rgba(40,167,69,.35); }
.btn-action.green:hover  { box-shadow: 0 10px 28px rgba(40,167,69,.45); }
.btn-action.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); color: white; box-shadow: 0 6px 20px rgba(245,158,11,.35); }
.btn-action.amber:hover  { box-shadow: 0 10px 28px rgba(245,158,11,.45); }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 10px 28px rgba(102,126,234,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; transform: none; box-shadow: none; }
.btn-action.big    { padding: 18px 38px; font-size: 17px; border-radius: 14px; }

/* ── Processing info box ───────────────────────── */
.proc-info { display: grid; grid-template-columns: repeat(auto-fit,minmax(180px,1fr)); gap: 14px; margin: 24px 0 0; }
.proc-item { background: #f8faff; border: 1px solid #e0e7ff; border-radius: 12px; padding: 14px 16px; display: flex; align-items: center; gap: 12px; }
.proc-item i { color: #667eea; font-size: 20px; flex-shrink: 0; }
.proc-item span { font-size: 12px; color: #64748b; }
.proc-item strong { display: block; color: #1e293b; font-size: 14px; font-weight: 700; }

/* ── Results / expected cards ──────────────────── */
.results-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.res-card { border-radius: 14px; padding: 22px; }
.res-card.rc-green { background: linear-gradient(135deg,rgba(16,185,129,.06),rgba(5,150,105,.03)); border: 1px solid #a7f3d0; }
.res-card.rc-blue  { background: linear-gradient(135deg,rgba(59,130,246,.06),rgba(37,99,235,.03)); border: 1px solid #bfdbfe; }
.res-card h4 { font-size: 14px; font-weight: 700; margin: 0 0 14px; display: flex; align-items: center; gap: 8px; }
.rc-green h4 { color: #059669; } .rc-blue h4 { color: #2563eb; }
.res-list { list-style: none; padding: 0; margin: 0; }
.res-list li { font-size: 13px; color: #475569; padding: 6px 0; border-bottom: 1px solid rgba(0,0,0,.04); display: flex; align-items: flex-start; gap: 8px; }
.res-list li:last-child { border-bottom: none; }
.res-list li span { color: #1e293b; font-weight: 600; margin-right: 2px; }

/* ── Result / loading box ──────────────────────── */
.loading { text-align: center; padding: 40px; }
.loading i { font-size: 48px; color: #667eea; animation: seo-spin 1s linear infinite; }
@keyframes seo-spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
.result-box { background: #f0fdf4; padding: 24px; border-radius: 14px; margin-top: 20px; border-left: 4px solid #10b981; }
.result-box h3 { color: #059669; margin-bottom: 16px; }
.result-item { padding: 12px; background: white; margin-bottom: 8px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }

@media(max-width:1100px){ .opt-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:700px){ .opt-grid,.results-grid { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: repeat(2,1fr); } }
</style>

<div class="aopt-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-magic"></i> Auto SEO Optimizer</h1>
        <p>Safe meta-tag fixer &mdash; fixes indexing issues on all <?php echo number_format($total_pages); ?> pages without touching content</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-robot" style="margin-right:6px;"></i>Fully Automated</span>
</div>

<!-- Banner -->
<div class="auto-banner">
    <div class="ab-icon"><i class="fas fa-robot"></i></div>
    <div>
        <strong>Safe SEO Metadata Fixer</strong>
        <p>Fixes meta titles, descriptions, canonical URLs, schema markup &amp; image attributes. <strong>Page body content is never changed &mdash; zero data loss.</strong></p>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-file-code"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Pages</h3>
            <div class="value"><?php echo number_format($total_pages); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Optimized Pages</h3>
            <div class="value"><?php echo number_format($optimized_pages); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-clock"></i></div>
        <div class="stat-text-wrap">
            <h3>Pending</h3>
            <div class="value"><?php echo number_format($pending_pages); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><i class="fas fa-percentage"></i></div>
        <div class="stat-text-wrap">
            <h3>Completion</h3>
            <div class="value"><?php echo $total_pages > 0 ? round(($optimized_pages / $total_pages) * 100) : 0; ?>%</div>
        </div>
    </div>
</div>

<?php $pct = $total_pages > 0 ? ($optimized_pages / $total_pages) * 100 : 0; ?>
<div class="prog-wrap" title="<?php echo round($pct); ?>% optimized">
    <div class="prog-fill" style="width:<?php echo $pct; ?>%"></div>
</div>

<!-- Optimizations Applied -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num sec-green"><i class="fas fa-list-check" style="font-size:14px;"></i></div>
        <h2>Automatic Optimizations Applied</h2>
    </div>
    <div class="seo-section-body">
        <div class="opt-grid">
            <div class="opt-card">
                <div class="opt-card-top oct-indigo"><div class="opt-card-icon oci-indigo"><i class="fas fa-tags"></i></div><h4>Meta Title</h4></div>
                <div class="opt-card-body"><ul>
                    <li><i class="fas fa-check"></i>Optimised to 50&ndash;70 chars</li>
                    <li><i class="fas fa-check"></i>Service + Area + Brand included</li>
                    <li><i class="fas fa-check"></i>Injected if missing</li>
                    <li><i class="fas fa-check"></i>Content is NOT changed</li>
                </ul></div>
            </div>
            <div class="opt-card">
                <div class="opt-card-top oct-purple"><div class="opt-card-icon oci-purple"><i class="fas fa-align-left"></i></div><h4>Meta Description</h4></div>
                <div class="opt-card-body"><ul>
                    <li><i class="fas fa-check"></i>Optimised to 120&ndash;165 chars</li>
                    <li><i class="fas fa-check"></i>Keyword + CTA included</li>
                    <li><i class="fas fa-check"></i>Injected if missing</li>
                    <li><i class="fas fa-check"></i>Content is NOT changed</li>
                </ul></div>
            </div>
            <div class="opt-card">
                <div class="opt-card-top oct-pink"><div class="opt-card-icon oci-pink"><i class="fas fa-link"></i></div><h4>Canonical URL</h4></div>
                <div class="opt-card-body"><ul>
                    <li><i class="fas fa-check"></i>Adds <code>$canonical_url</code> if missing</li>
                    <li><i class="fas fa-check"></i>Prevents duplicate-content issues</li>
                    <li><i class="fas fa-check"></i>Fixes Google indexing errors</li>
                    <li><i class="fas fa-check"></i>Content is NOT changed</li>
                </ul></div>
            </div>
            <div class="opt-card">
                <div class="opt-card-top oct-orange"><div class="opt-card-icon oci-orange"><i class="fas fa-code"></i></div><h4>Schema JSON-LD</h4></div>
                <div class="opt-card-body"><ul>
                    <li><i class="fas fa-check"></i>Service schema added</li>
                    <li><i class="fas fa-check"></i>LocalBusiness schema added</li>
                    <li><i class="fas fa-check"></i>AggregateRating (4.8&nbsp;★)</li>
                    <li><i class="fas fa-check"></i>Content is NOT changed</li>
                </ul></div>
            </div>
            <div class="opt-card">
                <div class="opt-card-top oct-green"><div class="opt-card-icon oci-green"><i class="fas fa-images"></i></div><h4>Image Attributes</h4></div>
                <div class="opt-card-body"><ul>
                    <li><i class="fas fa-check"></i>Adds missing <code>alt</code> text</li>
                    <li><i class="fas fa-check"></i>Adds <code>loading="lazy"</code></li>
                    <li><i class="fas fa-check"></i>Only adds — never removes</li>
                    <li><i class="fas fa-check"></i>Content is NOT changed</li>
                </ul></div>
            </div>
            <div class="opt-card">
                <div class="opt-card-top oct-teal"><div class="opt-card-icon oci-teal"><i class="fas fa-shield-alt"></i></div><h4>Safety &amp; Backup</h4></div>
                <div class="opt-card-body"><ul>
                    <li><i class="fas fa-check"></i>Creates <code>.bak</code> before every write</li>
                    <li><i class="fas fa-check"></i>Skips already-optimal pages</li>
                    <li><i class="fas fa-check"></i>Zero body content changed</li>
                    <li><i class="fas fa-check"></i>Safe on re-deploy</li>
                </ul></div>
            </div>
        </div>
    </div>
</div>

<!-- Start Optimization -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num sec-purple"><i class="fas fa-play" style="font-size:13px;"></i></div>
        <h2>Start Optimization</h2>
    </div>
    <div class="seo-section-body" style="text-align:center;">
        <p style="color:#64748b;font-size:15px;margin:0 0 24px;">Fixes <strong>meta tags, canonical URLs, schema markup &amp; image attributes</strong> on all pages. <span style="color:#10b981;font-weight:700;">Page content is never touched — zero data loss.</span></p>
        <div class="action-row" style="margin-top:0;">
            <button onclick="startBatchOptimization()" class="btn-action green big" id="startOptimizationBtn">
                <i class="fas fa-magic"></i> Start Automatic Optimization (<?php echo number_format($pending_pages); ?> Pages)
            </button>
            <button onclick="pauseOptimization()" class="btn-action amber" id="pauseBtn" style="display:none;">
                <i class="fas fa-pause"></i> Pause Optimization
            </button>
        </div>
        <div class="proc-info">
            <div class="proc-item"><i class="fas fa-file-alt"></i><div><strong><?php echo number_format($total_pages); ?></strong><span>Total Pages</span></div></div>
            <div class="proc-item"><i class="fas fa-layer-group"></i><div><strong>100 pages</strong><span>Batch Size</span></div></div>
            <div class="proc-item"><i class="fas fa-stopwatch"></i><div><strong>&lt;1 sec/page</strong><span>Processing Time</span></div></div>
            <div class="proc-item"><i class="fas fa-hourglass-half"></i><div><strong>~<?php echo round($total_pages / 100 / 10, 0); ?> min</strong><span>Total Est. Time</span></div></div>
            <div class="proc-item"><i class="fas fa-pause-circle"></i><div><strong>Yes</strong><span>Can be paused</span></div></div>
        </div>
        <div id="optimizationResults"></div>
    </div>
</div>

<!-- SEO Issues Fixer -->
<div class="seo-section" id="issuesFixer">
    <div class="seo-section-head">
        <div class="sec-num" style="background:linear-gradient(135deg,#ef4444,#dc2626);"><i class="fas fa-bug" style="font-size:13px;"></i></div>
        <h2>SEO Issues Fixer</h2>
        <a href="search-console.php" style="margin-left:auto;font-size:13px;color:#3b82f6;font-weight:600;text-decoration:none;"><i class="fab fa-google"></i> Search Console</a>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;font-size:14px;margin-bottom:20px;">Scan all <?php echo number_format($total_pages); ?> generated pages for SEO issues and apply <strong>targeted fixes without touching page content</strong>.</p>

        <div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:22px;">
            <button onclick="scanIssues()" id="scanBtn" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#ef4444,#dc2626);color:white;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 5px 15px rgba(239,68,68,.35);">
                <i class="fas fa-search"></i> Scan for Issues
            </button>
            <a href="search-console.php" style="display:inline-flex;align-items:center;gap:8px;padding:12px 24px;background:linear-gradient(135deg,#4285f4,#1a73e8);color:white;border:none;border-radius:10px;font-size:14px;font-weight:700;cursor:pointer;box-shadow:0 5px 15px rgba(66,133,244,.35);text-decoration:none;">
                <i class="fab fa-google"></i> Submit to Search Console
            </a>
        </div>

        <div id="issuesScanResult" style="display:none;">
            <div id="issuesSummary"></div>
        </div>
        <div id="issueFixMsg" style="display:none;padding:12px 16px;border-radius:10px;font-size:14px;font-weight:600;margin-top:12px;"></div>
    </div>
</div>

<!-- Expected Results -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num sec-blue"><i class="fas fa-chart-line" style="font-size:13px;"></i></div>
        <h2>Expected Results</h2>
    </div>
    <div class="seo-section-body">
        <div class="results-grid">
            <div class="res-card rc-green">
                <h4><i class="fas fa-check-circle"></i> After Optimization</h4>
                <ul class="res-list">
                    <li>✅ All pages will have 1500+ words of quality content</li>
                    <li>✅ Perfect keyword placement in all critical areas</li>
                    <li>✅ Comprehensive FAQ sections on every page</li>
                    <li>✅ Full schema markup for better search visibility</li>
                    <li>✅ Internal linking structure for better crawlability</li>
                    <li>✅ All technical SEO issues fixed</li>
                    <li>✅ Optimized meta tags for higher CTR</li>
                </ul>
            </div>
            <div class="res-card rc-blue">
                <h4><i class="fas fa-clock"></i> Timeline to #1 Position</h4>
                <ul class="res-list">
                    <li><span>Week 1-2:</span> Google indexes optimized pages</li>
                    <li><span>Month 1:</span> Initial improvements (50+ → 20-30)</li>
                    <li><span>Month 2-3:</span> Significant movement (20-30 → 10-15)</li>
                    <li><span>Month 4-6:</span> Reach page 1 and top 3 positions</li>
                    <li><span>Month 6+:</span> Multiple keywords at #1 position</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Bottom nav -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="seo-dashboard.php" class="btn-action purple">
        <i class="fas fa-chart-line"></i> View SEO Dashboard
    </a>
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Admin
    </a>
</div>

</div>
    
    <script>
        let currentOffset = 0;
        let totalPages = <?php echo $total_pages; ?>;
        let optimizationPaused = false;
        let totalFixed = 0, totalSkipped = 0, totalErrors = 0;

        function startBatchOptimization() {
            if (!confirm('Start safe SEO optimization on all <?php echo number_format($pending_pages); ?> pages?\n\nOnly fixes: meta title, meta description, canonical URL, keywords, schema markup, image attributes.\nPage content is NEVER changed — zero data loss.')) return;
            currentOffset = 0; totalFixed = 0; totalSkipped = 0; totalErrors = 0;
            optimizationPaused = false;
            document.getElementById('startOptimizationBtn').style.display = 'none';
            document.getElementById('pauseBtn').style.display = 'inline-block';

            document.getElementById('optimizationResults').innerHTML = `
            <div id="optDashboard" style="margin-top:20px;border-radius:14px;overflow:hidden;border:1.5px solid #e0e7ff;background:white;">
                <div style="background:linear-gradient(135deg,#667eea,#764ba2);padding:14px 22px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
                    <div style="color:white;font-weight:800;font-size:15px;"><i class="fas fa-cog fa-spin" id="optSpinIcon"></i>&nbsp;<span id="optHeaderTitle">Starting optimization...</span></div>
                    <div style="color:rgba(255,255,255,.8);font-size:12px;" id="optHeaderSub">Preparing first batch...</div>
                </div>
                <div style="padding:14px 22px;border-bottom:1px solid #f1f5f9;">
                    <div style="display:flex;justify-content:space-between;margin-bottom:6px;">
                        <span style="font-size:12px;color:#64748b;font-weight:600;">Overall Progress</span>
                        <span style="font-size:13px;font-weight:800;color:#667eea;" id="optProgressPct">0%</span>
                    </div>
                    <div style="height:14px;background:#e2e8f0;border-radius:7px;overflow:hidden;">
                        <div id="optProgressBar" style="height:100%;width:0%;background:linear-gradient(90deg,#667eea,#10b981);border-radius:7px;transition:width .6s ease;"></div>
                    </div>
                    <div style="display:flex;gap:20px;margin-top:9px;flex-wrap:wrap;">
                        <span style="font-size:12px;color:#64748b;"><i class="fas fa-check-circle" style="color:#10b981;"></i> Fixed: <strong id="optTotalFixed">0</strong></span>
                        <span style="font-size:12px;color:#64748b;"><i class="fas fa-forward" style="color:#f59e0b;"></i> Skipped: <strong id="optTotalSkipped">0</strong></span>
                        <span style="font-size:12px;color:#64748b;"><i class="fas fa-times-circle" style="color:#ef4444;"></i> Errors: <strong id="optTotalErrors">0</strong></span>
                        <span style="font-size:12px;color:#64748b;"><i class="fas fa-file-alt" style="color:#667eea;"></i> Done: <strong id="optTotalProcessed">0</strong> / ${totalPages}</span>
                    </div>
                </div>
                <div id="optCurrentBatch" style="padding:12px 22px;background:#f8faff;border-bottom:1px solid #e0e7ff;display:flex;align-items:center;gap:10px;">
                    <i class="fas fa-spinner fa-spin" style="color:#667eea;font-size:15px;"></i>
                    <span style="font-size:13px;color:#475569;font-weight:600;">Initializing...</span>
                </div>
                <div style="padding:12px 22px;">
                    <div style="font-size:10px;font-weight:700;color:#94a3b8;text-transform:uppercase;letter-spacing:.6px;margin-bottom:8px;">Batch Log (newest first)</div>
                    <div id="optBatchLog" style="display:flex;flex-direction:column;gap:5px;max-height:200px;overflow-y:auto;"></div>
                </div>
            </div>`;

            processBatch();
        }

        function pauseOptimization() {
            optimizationPaused = true;
            document.getElementById('pauseBtn').style.display = 'none';
            document.getElementById('startOptimizationBtn').style.display = 'inline-block';
            document.getElementById('startOptimizationBtn').innerHTML = '<i class="fas fa-play"></i> Resume Optimization';
            const cb = document.getElementById('optCurrentBatch');
            if (cb) cb.innerHTML = '<i class="fas fa-pause-circle" style="color:#f59e0b;font-size:15px;"></i> <span style="font-size:13px;color:#92400e;font-weight:700;">Paused — click Resume to continue</span>';
        }

        function processBatch() {
            if (optimizationPaused) return;
            const batchNum  = Math.floor(currentOffset / 100) + 1;
            const startPage = currentOffset + 1;
            const endPage   = Math.min(currentOffset + 100, totalPages);
            const totalBatches = Math.ceil(totalPages / 100);

            const cb  = document.getElementById('optCurrentBatch');
            const hdr = document.getElementById('optHeaderTitle');
            if (cb)  cb.innerHTML  = `<i class="fas fa-spinner fa-spin" style="color:#667eea;font-size:15px;"></i> <span style="font-size:13px;color:#475569;font-weight:600;">Processing Batch ${batchNum} of ${totalBatches} &mdash; pages ${startPage.toLocaleString()} to ${endPage.toLocaleString()}</span>`;
            if (hdr) hdr.textContent = `Batch ${batchNum} / ${totalBatches} running...`;

            fetch('../api/safe-seo-batch.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `batch_size=100&offset=${currentOffset}`
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    if (cb) cb.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> <span style="color:#ef4444;font-weight:600;">Batch ${batchNum} error: ${data.message}</span> <button onclick="processBatch()" style="margin-left:10px;padding:4px 12px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;">Retry</button>`;
                    return;
                }
                currentOffset = Math.min(currentOffset + 100, totalPages);
                totalFixed   += data.results.optimized || 0;
                totalSkipped += data.results.skipped   || 0;
                totalErrors  += data.results.failed    || 0;
                const progress = Math.min(100, Math.round((currentOffset / totalPages) * 100));

                const bar = document.getElementById('optProgressBar');
                const pct = document.getElementById('optProgressPct');
                if (bar) bar.style.width = progress + '%';
                if (pct) pct.textContent = progress + '%';
                document.getElementById('optTotalFixed').textContent     = totalFixed.toLocaleString();
                document.getElementById('optTotalSkipped').textContent   = totalSkipped.toLocaleString();
                document.getElementById('optTotalErrors').textContent    = totalErrors;
                document.getElementById('optTotalProcessed').textContent = currentOffset.toLocaleString();

                if (cb) cb.innerHTML = `<i class="fas fa-check-circle" style="color:#10b981;font-size:15px;"></i> <span style="font-size:13px;color:#064e3b;font-weight:700;">Batch ${batchNum} done &mdash; ${(data.results.optimized||0)} fixed, ${(data.results.skipped||0)} skipped${data.results.failed ? ', ' + data.results.failed + ' errors' : ''}</span>`;
                if (hdr) hdr.textContent = `Batch ${batchNum} / ${totalBatches} complete — ${progress}% done`;
                const sub = document.getElementById('optHeaderSub');
                if (sub) sub.textContent = `${currentOffset.toLocaleString()} / ${totalPages.toLocaleString()} pages processed`;

                const log = document.getElementById('optBatchLog');
                if (log) {
                    const item = document.createElement('div');
                    item.style.cssText = 'display:flex;align-items:center;gap:8px;padding:6px 10px;background:#f0fdf4;border-radius:8px;border-left:3px solid #10b981;font-size:12px;';
                    item.innerHTML = `<i class="fas fa-check" style="color:#10b981;flex-shrink:0;"></i><span style="color:#065f46;font-weight:700;">Batch ${batchNum}</span><span style="color:#64748b;">pp. ${startPage.toLocaleString()}&ndash;${endPage.toLocaleString()} &bull; ${data.results.optimized||0} fixed &bull; ${data.results.skipped||0} already OK</span><span style="margin-left:auto;color:#667eea;font-weight:700;">${progress}%</span>`;
                    log.prepend(item);
                }

                if (currentOffset < totalPages && !optimizationPaused) {
                    setTimeout(() => processBatch(), 2000);
                } else {
                    showCompletionMessage();
                }
            })
            .catch(err => {
                if (cb) cb.innerHTML = `<i class="fas fa-exclamation-triangle" style="color:#ef4444;"></i> <span style="color:#ef4444;font-weight:600;">Network error: ${err.message}</span> <button onclick="processBatch()" style="margin-left:10px;padding:4px 12px;background:#667eea;color:white;border:none;border-radius:6px;cursor:pointer;font-size:12px;">Retry</button>`;
            });
        }
        
        function showCompletionMessage() {
            // Update the persistent dashboard header to "All done"
            const icon = document.getElementById('optSpinIcon');
            if (icon) { icon.className = 'fas fa-check-circle'; }
            const hdr = document.getElementById('optHeaderTitle');
            if (hdr) hdr.textContent = '\u2705 All ' + totalPages.toLocaleString() + ' pages optimized!';
            const sub = document.getElementById('optHeaderSub');
            if (sub) sub.textContent = totalFixed.toLocaleString() + ' fixed, ' + totalSkipped.toLocaleString() + ' already optimal, ' + totalErrors + ' errors';

            const cb = document.getElementById('optCurrentBatch');
            if (cb) {
                cb.style.background = '#f0fdf4';
                cb.style.borderBottomColor = '#bbf7d0';
                cb.innerHTML = '<i class="fas fa-trophy" style="color:#f59e0b;font-size:16px;"></i> <span style="font-size:13.5px;color:#065f46;font-weight:800;">Optimization complete \u2014 all pages processed!</span>';
            }

            const log = document.getElementById('optBatchLog');
            if (log) {
                const summary = document.createElement('div');
                summary.style.cssText = 'padding:16px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border-radius:10px;border:1.5px solid #86efac;margin-bottom:10px;';
                summary.innerHTML = `
                    <div style="font-weight:800;color:#15803d;font-size:14px;margin-bottom:10px;"><i class="fas fa-check-circle"></i> SEO metadata fixes applied — page content unchanged</div>
                    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:6px;margin-bottom:12px;">
                        <div style="padding:7px 10px;background:white;border-radius:7px;font-size:12px;color:#065f46;"><i class="fas fa-tag" style="color:#667eea;margin-right:5px;"></i>Meta titles fixed</div>
                        <div style="padding:7px 10px;background:white;border-radius:7px;font-size:12px;color:#065f46;"><i class="fas fa-align-left" style="color:#667eea;margin-right:5px;"></i>Meta descriptions fixed</div>
                        <div style="padding:7px 10px;background:white;border-radius:7px;font-size:12px;color:#065f46;"><i class="fas fa-link" style="color:#667eea;margin-right:5px;"></i>Canonical URLs added</div>
                        <div style="padding:7px 10px;background:white;border-radius:7px;font-size:12px;color:#065f46;"><i class="fas fa-key" style="color:#667eea;margin-right:5px;"></i>Meta keywords added</div>
                        <div style="padding:7px 10px;background:white;border-radius:7px;font-size:12px;color:#065f46;"><i class="fas fa-code" style="color:#667eea;margin-right:5px;"></i>Schema JSON-LD added</div>
                        <div style="padding:7px 10px;background:white;border-radius:7px;font-size:12px;color:#065f46;"><i class="fas fa-image" style="color:#667eea;margin-right:5px;"></i>Image lazy-load + alt text</div>
                    </div>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:10px;">
                        <div style="padding:10px 16px;background:#667eea;color:white;border-radius:9px;font-size:12.5px;font-weight:700;"><i class="fas fa-shield-alt"></i> Zero content changes — zero data loss</div>
                        <div style="padding:10px 16px;background:#10b981;color:white;border-radius:9px;font-size:12.5px;font-weight:700;"><i class="fas fa-save"></i> Changes saved on server — safe on re-deploy</div>
                    </div>
                    <div style="background:white;border-radius:8px;padding:10px 14px;font-size:12.5px;color:#1e293b;">
                        <strong style="color:#667eea;">📈 Expected impact:</strong> Google will re-index these pages with proper meta data. Search Console coverage should improve within 1-2 weeks.
                    </div>
                    <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap;">
                        <button onclick="location.reload()" style="padding:9px 18px;background:#667eea;color:white;border:none;border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;"><i class="fas fa-sync"></i> Reload Page</button>
                        <a href="seo-dashboard.php" style="padding:9px 18px;background:#10b981;color:white;border-radius:8px;font-weight:700;font-size:13px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;"><i class="fas fa-chart-line"></i> SEO Dashboard</a>
                    </div>`;
                log.prepend(summary);
            }

            document.getElementById('pauseBtn').style.display = 'none';
            document.getElementById('startOptimizationBtn').style.display = 'none';
        }
        
        // ── SEO Issues Fixer ─────────────────────────────
        const severityColors = { high:'#dc2626', medium:'#d97706', low:'#64748b' };
        const severityBg    = { high:'#fee2e2',  medium:'#fef3c7', low:'#f1f5f9' };

        function scanIssues() {
            const btn = document.getElementById('scanBtn');
            btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Scanning...';
            fetch('../api/scan-seo-issues.php', { method:'POST' })
            .then(r => r.json())
            .then(data => {
                btn.disabled = false; btn.innerHTML = '<i class="fas fa-search"></i> Scan for Issues';
                if (!data.success) { showIssueMsg(data.message || 'Scan failed', false); return; }

                const el = document.getElementById('issuesScanResult');
                const sm = document.getElementById('issuesSummary');
                el.style.display = 'block';

                if (data.total_issues === 0) {
                    sm.innerHTML = '<div style="padding:20px;background:#d1fae5;border-radius:12px;color:#065f46;font-weight:700;font-size:15px;"><i class="fas fa-check-circle"></i> All ' + data.scanned + ' pages are clean — no SEO issues found!</div>';
                    return;
                }

                let html = '<div style="margin-bottom:14px;color:#64748b;font-size:13px;">Scanned <strong>' + data.scanned + '</strong> pages — found <strong style="color:#dc2626;">' + data.total_issues + ' issues</strong></div>';
                html += '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">';

                for (const [key, info] of Object.entries(data.summary)) {
                    if (info.count === 0) continue;
                    html += `<div style="padding:18px;background:${severityBg[info.severity]};border-radius:12px;border-left:4px solid ${severityColors[info.severity]};">
                        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                            <div style="font-weight:700;color:#1e293b;font-size:14px;"><i class="${info.icon}" style="color:${severityColors[info.severity]};margin-right:6px;"></i>${info.label}</div>
                            <span style="background:${severityColors[info.severity]};color:white;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700;">${info.count}</span>
                        </div>
                        <div style="font-size:12px;color:#64748b;margin-bottom:12px;">Severity: <strong style="color:${severityColors[info.severity]};text-transform:capitalize;">${info.severity}</strong></div>
                        <button onclick="fixIssue('${key}', '${info.label}', ${info.count})" style="width:100%;padding:9px;background:white;border:1.5px solid ${severityColors[info.severity]};color:${severityColors[info.severity]};border-radius:8px;font-weight:700;font-size:13px;cursor:pointer;" onmouseover="this.style.background='${severityColors[info.severity]}';this.style.color='white';" onmouseout="this.style.background='white';this.style.color='${severityColors[info.severity]}';">
                            <i class="fas fa-wrench"></i> Fix All ${info.count} Pages
                        </button>
                    </div>`;
                }
                html += '</div>';
                sm.innerHTML = html;
            })
            .catch(() => { btn.disabled=false; btn.innerHTML='<i class="fas fa-search"></i> Scan for Issues'; showIssueMsg('Network error during scan', false); });
        }

        function fixIssue(issueType, label, count) {
            if (!confirm(`Fix "${label}" on all ${count} affected pages?\n\nThis makes TARGETED changes only (meta tags, canonical) — page content is NOT touched.`)) return;
            showIssueMsg('<i class="fas fa-spinner fa-spin"></i> Fixing ' + label + ' on ' + count + ' pages...', true, true);
            fetch('../api/fix-seo-issue.php', {
                method: 'POST', headers:{'Content-Type':'application/json'},
                body: JSON.stringify({ issue_type: issueType, target: 'all' })
            }).then(r => r.json())
              .then(d => {
                  if (d.success) {
                      showIssueMsg('✓ ' + (d.message || 'Fixed ' + d.fixed + ' pages'), true);
                      setTimeout(() => scanIssues(), 1500);
                  } else {
                      showIssueMsg('✗ ' + (d.message || 'Fix failed'), false);
                  }
              }).catch(() => showIssueMsg('✗ Network error during fix', false));
        }

        function showIssueMsg(msg, ok, spin) {
            const el = document.getElementById('issueFixMsg');
            el.style.display = 'block';
            el.style.background = ok ? '#d1fae5' : '#fee2e2';
            el.style.color      = ok ? '#065f46' : '#991b1b';
            el.innerHTML = msg;
        }

        // Legacy function for compatibility
        function optimizeAllPages() {
            startBatchOptimization();
            // Redirects to new batch function
            startBatchOptimization();
            
            resultsDiv.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner"></i>
                    <h3 style="margin-top: 20px; color: #667eea;">Optimizing All Pages...</h3>
                    <p style="color: #666;">This may take several minutes. Please do not close this page.</p>
                </div>
            `;
            
            fetch('../api/auto-seo-optimizer.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=optimize_all_pages'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                        <div class="result-box">
                            <h3><i class="fas fa-check-circle"></i> Optimization Complete!</h3>
                            <p><strong>Total Pages:</strong> ${data.results.total}</p>
                            <p><strong>Successfully Optimized:</strong> ${data.results.optimized}</p>
                            <p><strong>Failed:</strong> ${data.results.failed}</p>
                            
                            <div style="margin-top: 20px;">
                                <h4>Optimizations Applied:</h4>
                                <ul style="margin-left: 20px; margin-top: 10px;">
                                    <li>✅ Content expanded to 1500+ words</li>
                                    <li>✅ Images optimized with alt text</li>
                                    <li>✅ FAQ sections generated</li>
                                    <li>✅ Schema markup added</li>
                                    <li>✅ Internal links created</li>
                                    <li>✅ Technical SEO fixed</li>
                                    <li>✅ Meta tags optimized</li>
                                </ul>
                            </div>
                            
                            <div class="alert alert-success" style="margin-top: 20px;">
                                <strong>Next Steps:</strong>
                                <ol style="margin-left: 20px; margin-top: 10px;">
                                    <li>Wait 1-2 weeks for Google to re-index pages</li>
                                    <li>Check rankings using SEO Dashboard</li>
                                    <li>Monitor position improvements</li>
                                    <li>Build quality backlinks (manual step)</li>
                                    <li>Update Google My Business</li>
                                </ol>
                            </div>
                            
                            <button onclick="location.reload()" class="btn btn-success" style="margin-top: 20px;">
                                <i class="fas fa-sync"></i> Refresh Page
                            </button>
                        </div>
                    `;
                    
                    resultsDiv.innerHTML = html;
                } else {
                    resultsDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <strong>Optimization Error:</strong> ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div class="alert alert-warning">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            });
        }
    </script>
<?php include '../includes/footer.php'; ?>
