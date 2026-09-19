<?php
/**
 * Complete SEO System Dashboard
 * Whole site optimization + Link building
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

$page_title = 'Complete SEO System';
include '../includes/header.php';

// ── Real data — filesystem is authoritative ──────────────────────
$root_dir = dirname(dirname(dirname(__FILE__)));
$gen_dir  = $root_dir . '/generated-pages/';

$area_files   = glob($gen_dir . '*-in-*.php') ?: [];
$all_gen      = glob($gen_dir . '*.php') ?: [];
$pillar_files = array_filter($all_gen, fn($f) => basename($f) !== 'index.php' && strpos(basename($f), '-in-') === false);
$fs_pages     = count($area_files);
$pillar_count = count($pillar_files);
$static_count = 12; // known static pages

$blog_dir   = $root_dir . '/data/blogs';
$blog_files = is_dir($blog_dir) ? array_filter(glob($blog_dir . '/*.json') ?: [], fn($f) => !in_array(basename($f), ['index.json','stats.json'])) : [];
$blog_count = count($blog_files);

$faq_dir   = $root_dir . '/data/faqs';
$faq_files = is_dir($faq_dir) ? array_filter(glob($faq_dir . '/*.json') ?: [], fn($f) => !in_array(basename($f), ['index.json','stats.json'])) : [];
$faq_count = count($faq_files);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) throw new Exception('no db');
    $conn->set_charset('utf8mb4');

    $r = @$conn->query("SELECT COUNT(*) as c FROM generated_pages");
    $db_pages = $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;
    $total_pages = max($fs_pages, $db_pages);

    $r = @$conn->query("SELECT COUNT(DISTINCT page_path) as c FROM seo_optimization_log WHERE status='completed'");
    $optimized_pages = $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;

    $r = @$conn->query("SELECT COUNT(*) as c FROM backlinks");
    $total_backlinks = $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;

    $r = @$conn->query("SELECT COUNT(*) as c FROM backlinks WHERE status='active'");
    $active_backlinks = $r ? (int)($r->fetch_assoc()['c'] ?? 0) : 0;

} catch (\Throwable $e) {
    $total_pages     = $fs_pages;
    $optimized_pages = 0;
    $total_backlinks = 0;
    $active_backlinks = 0;
}
$grand_total = $total_pages + $static_count + $blog_count + $pillar_count;
?>

<style>
/* ── Page wrapper ───────────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero header card ───────────────────────────────── */
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#28a745,#20c997); color: white; padding: 8px 18px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; }

/* ── Solution banner ────────────────────────────────── */
.solution-banner { background: linear-gradient(135deg, rgba(40,167,69,.08) 0%, rgba(32,201,151,.05) 100%); border: 1px solid rgba(40,167,69,.2); border-left: 5px solid #28a745; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#28a745,#20c997); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #166534; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #15803d; font-size: 13.5px; margin: 0; line-height: 1.6; }

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
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

/* ── "What gets optimized" grid ─────────────────────── */
.optimize-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px,1fr)); gap: 14px; margin-bottom: 28px; }
.optimize-item { background: #f8faff; border: 1px solid #e0e7ff; border-radius: 12px; padding: 16px 18px; display: flex; align-items: flex-start; gap: 12px; }
.opt-icon { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; color: white; flex-shrink: 0; background: linear-gradient(135deg,#667eea,#764ba2); }
.optimize-item strong { display: block; color: #1e293b; font-size: 13.5px; font-weight: 700; margin-bottom: 3px; }
.optimize-item span { font-size: 12px; color: #64748b; line-height: 1.4; }

/* ── Action button row ──────────────────────────────── */
.action-row { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-top: 24px; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 14px 30px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.green { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 6px 20px rgba(40,167,69,.35); }
.btn-action.green:hover { box-shadow: 0 10px 30px rgba(40,167,69,.45); }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 10px 30px rgba(102,126,234,.45); }
.btn-action.gray { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover { background: #e2e8f0; box-shadow: none; transform: none; }
.btn-action.big { padding: 18px 40px; font-size: 17px; border-radius: 14px; }

/* ── Link building info bar ─────────────────────────── */
.link-info-bar { background: linear-gradient(135deg,rgba(239,246,255,.9),rgba(219,234,254,.6)); border: 1px solid #bfdbfe; border-left: 5px solid #3b82f6; border-radius: 12px; padding: 16px 20px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; }
.link-info-bar i { color: #2563eb; font-size: 20px; }
.link-info-bar p { margin: 0; color: #1e40af; font-size: 13.5px; line-height: 1.6; }

/* ── Feature cards (link building) ─────────────────── */
.feature-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-bottom: 28px; }
.feature-card { background: white; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; transition: all .25s; }
.feature-card:hover { transform: translateY(-5px); box-shadow: 0 16px 40px rgba(0,0,0,.1); }
.fc-top { padding: 22px 22px 18px; text-align: center; }
.fc-top.fc-blue   { background: linear-gradient(135deg,#667eea12,#764ba208); border-bottom: 3px solid #667eea; }
.fc-top.fc-green  { background: linear-gradient(135deg,#10b98112,#05966908); border-bottom: 3px solid #10b981; }
.fc-top.fc-orange { background: linear-gradient(135deg,#f59e0b12,#d9770608); border-bottom: 3px solid #f59e0b; }
.fc-icon { width: 56px; height: 56px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 24px; color: white; margin-bottom: 12px; }
.fc-blue .fc-icon   { background: linear-gradient(135deg,#667eea,#764ba2); }
.fc-green .fc-icon  { background: linear-gradient(135deg,#10b981,#059669); }
.fc-orange .fc-icon { background: linear-gradient(135deg,#f59e0b,#d97706); }
.fc-top h4 { font-size: 16px; font-weight: 700; margin: 0; }
.fc-blue h4   { color: #667eea; }
.fc-green h4  { color: #059669; }
.fc-orange h4 { color: #d97706; }
.fc-body { padding: 18px 22px; }
.fc-body ul { list-style: none; padding: 0; margin: 0; }
.fc-body li { padding: 9px 0; color: #475569; font-size: 13.5px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #f8fafc; }
.fc-body li:last-child { border-bottom: none; }
.fc-body li i { font-size: 13px; color: white; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.fc-blue .fc-body li i   { background: #667eea; }
.fc-green .fc-body li i  { background: #10b981; }
.fc-orange .fc-body li i { background: #f59e0b; }

/* ── Timeline ────────────────────────────────────────── */
.timeline { display: flex; flex-direction: column; gap: 0; position: relative; padding-left: 56px; }
.timeline::before { content: ''; position: absolute; left: 17px; top: 20px; bottom: 20px; width: 3px; background: linear-gradient(180deg,#667eea,#10b981,#f59e0b); border-radius: 2px; }
.tl-item { position: relative; padding: 0 0 28px; }
.tl-badge { position: absolute; left: -56px; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 800; color: white; z-index: 1; }
.tl-badge.b1 { background: linear-gradient(135deg,#667eea,#764ba2); }
.tl-badge.b2 { background: linear-gradient(135deg,#10b981,#059669); }
.tl-badge.b3 { background: linear-gradient(135deg,#f59e0b,#d97706); }
.tl-card { background: #f8faff; border: 1px solid #e0e7ff; border-radius: 14px; padding: 20px 22px; }
.tl-card.tc2 { background: #f0fdf4; border-color: #bbf7d0; }
.tl-card.tc3 { background: #fffbeb; border-color: #fde68a; }
.tl-phase { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; margin-bottom: 8px; }
.tl-card .tl-phase { color: #667eea; }
.tl-card.tc2 .tl-phase { color: #059669; }
.tl-card.tc3 .tl-phase { color: #d97706; }
.tl-card h4 { font-size: 16px; font-weight: 700; color: #1e293b; margin: 0 0 12px; }
.tl-list { list-style: none; padding: 0; margin: 0 0 10px; }
.tl-list li { font-size: 13.5px; color: #475569; padding: 5px 0; display: flex; align-items: center; gap: 8px; }
.tl-list li::before { content: '✅'; font-size: 13px; }
.tl-expected { display: inline-flex; align-items: center; gap: 6px; background: white; border-radius: 8px; padding: 6px 12px; font-size: 12.5px; font-weight: 600; color: #1e293b; border: 1px solid #e2e8f0; }

/* ── Directory list ─────────────────────────────────── */
.directory-list { display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; margin: 20px 0; }
.directory-item { background: white; padding: 20px; border-radius: 12px; border: 2px solid #e2e8f0; transition: all 0.3s; }
.directory-item:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102,126,234,.2); }
.directory-item h4 { color: #667eea; margin-bottom: 10px; }
.priority { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 10px; }
.priority.high { background: #fee; color: #dc3545; }
.priority.medium { background: #fff3cd; color: #856404; }
.priority.critical { background: #d4edda; color: #155724; }
.directory-item .da { color: #666; font-size: 14px; margin-bottom: 10px; }
.directory-item .instructions { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-top: 10px; font-size: 13px; white-space: pre-line; }
.directory-item a { display: inline-block; margin-top: 10px; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-size: 14px; }
.directory-item a:hover { background: #5568d3; }

@media(max-width:900px){ .feature-grid { grid-template-columns: 1fr; } .stats-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } .seo-hero { flex-direction: column; } }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-rocket"></i> Complete SEO System</h1>
        <p>Whole Website Optimization + Link Building Strategy</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-check-circle" style="margin-right:6px;"></i>All-in-One Solution</span>
</div>

<!-- Solution Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-check-circle"></i></div>
    <div>
        <strong>Complete SEO Solution</strong>
        <p>This system optimizes your ENTIRE website (homepage, blogs, reviews, FAQs, service pages) AND provides a comprehensive link building strategy to reach #1 rankings.</p>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-globe"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Pages</h3>
            <div class="value"><?php echo number_format($grand_total); ?></div>
            <p class="sub"><?php echo number_format($total_pages); ?> service + <?php echo $static_count; ?> static + <?php echo number_format($blog_count); ?> blogs</p>
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
        <div class="stat-icon-wrap orange"><i class="fas fa-link"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Backlinks</h3>
            <div class="value"><?php echo number_format($total_backlinks); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-check-double"></i></div>
        <div class="stat-text-wrap">
            <h3>Active Backlinks</h3>
            <div class="value"><?php echo number_format($active_backlinks); ?></div>
        </div>
    </div>
</div>

<!-- Part 1: Whole Site Optimization -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-cog" style="color:#667eea;margin-right:8px;"></i>Whole Site SEO Optimization</h2>
    </div>
    <div class="seo-section-body">
        <div class="optimize-grid">
            <div class="optimize-item">
                <div class="opt-icon" style="background:linear-gradient(135deg,#667eea,#764ba2);"><i class="fas fa-home"></i></div>
                <div><strong>Homepage</strong><span>Schema markup, meta tags, images, internal links</span></div>
            </div>
            <div class="optimize-item">
                <div class="opt-icon" style="background:linear-gradient(135deg,#10b981,#059669);"><i class="fas fa-file-alt"></i></div>
                <div><strong>Service Pages</strong><span>All <?php echo number_format($total_pages); ?> pages — meta, schema, canonical, image optimisation</span></div>
            </div>
            <div class="optimize-item">
                <div class="opt-icon" style="background:linear-gradient(135deg,#3b82f6,#2563eb);"><i class="fas fa-blog"></i></div>
                <div><strong>Blog Posts</strong><span>Content optimization, internal linking, images</span></div>
            </div>
            <div class="optimize-item">
                <div class="opt-icon" style="background:linear-gradient(135deg,#f59e0b,#d97706);"><i class="fas fa-copy"></i></div>
                <div><strong>Static Pages</strong><span>About, Contact, Gallery, Reviews, FAQs, Videos, Privacy, Terms</span></div>
            </div>
            <div class="optimize-item">
                <div class="opt-icon" style="background:linear-gradient(135deg,#06b6d4,#0891b2);"><i class="fas fa-code"></i></div>
                <div><strong>Technical SEO</strong><span>Meta tags, Open Graph, canonical URLs, lazy loading</span></div>
            </div>
        </div>
        <div class="action-row">
            <button onclick="optimizeWholeSite()" class="btn-action green big">
                <i class="fas fa-magic"></i> Optimize Entire Website
            </button>
            <a href="auto-seo-optimizer-dashboard.php" class="btn-action purple">
                <i class="fas fa-cog"></i> Service Pages Only
            </a>
        </div>
        <div id="optimizationResults"></div>
    </div>
</div>

<!-- Part 2: Link Building -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-link" style="color:#3b82f6;margin-right:8px;"></i>Link Building Strategy</h2>
    </div>
    <div class="seo-section-body">
        <div class="link-info-bar">
            <i class="fas fa-info-circle"></i>
            <p><strong>Why Link Building is Critical:</strong> Backlinks are the #2 most important ranking factor (after content). You need 10+ quality backlinks per page to rank #1.</p>
        </div>
        <div class="feature-grid">
            <div class="feature-card">
                <div class="fc-top fc-blue">
                    <div class="fc-icon"><i class="fas fa-list"></i></div>
                    <h4>Directory Submissions</h4>
                </div>
                <div class="fc-body">
                    <ul>
                        <li><i class="fas fa-check"></i> 15+ high-authority directories</li>
                        <li><i class="fas fa-check"></i> Step-by-step instructions</li>
                        <li><i class="fas fa-check"></i> Priority ranking (Critical/High/Medium)</li>
                        <li><i class="fas fa-check"></i> Domain Authority scores</li>
                    </ul>
                </div>
            </div>
            <div class="feature-card">
                <div class="fc-top fc-green">
                    <div class="fc-icon"><i class="fas fa-handshake"></i></div>
                    <h4>Partnership Opportunities</h4>
                </div>
                <div class="fc-body">
                    <ul>
                        <li><i class="fas fa-check"></i> Local business partnerships</li>
                        <li><i class="fas fa-check"></i> Construction companies</li>
                        <li><i class="fas fa-check"></i> Interior designers</li>
                        <li><i class="fas fa-check"></i> Real estate agents</li>
                    </ul>
                </div>
            </div>
            <div class="feature-card">
                <div class="fc-top fc-orange">
                    <div class="fc-icon"><i class="fas fa-pen"></i></div>
                    <h4>Content Marketing</h4>
                </div>
                <div class="fc-body">
                    <ul>
                        <li><i class="fas fa-check"></i> Guest posting opportunities</li>
                        <li><i class="fas fa-check"></i> Industry blog outreach</li>
                        <li><i class="fas fa-check"></i> Resource page links</li>
                        <li><i class="fas fa-check"></i> Broken link building</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="action-row">
            <button onclick="showDirectories()" class="btn-action green">
                <i class="fas fa-list"></i> View Directory List (15+ Sites)
            </button>
            <button onclick="showLinkOpportunities()" class="btn-action purple">
                <i class="fas fa-lightbulb"></i> Link Building Opportunities
            </button>
        </div>
        <div id="linkBuildingResults"></div>
    </div>
</div>

<!-- Timeline -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">3</div>
        <h2><i class="fas fa-calendar-alt" style="color:#f59e0b;margin-right:8px;"></i>Complete Timeline to #1 Rankings</h2>
    </div>
    <div class="seo-section-body">
        <div class="timeline">
            <div class="tl-item">
                <div class="tl-badge b1">1</div>
                <div class="tl-card">
                    <div class="tl-phase">Month 1–2</div>
                    <h4>Foundation</h4>
                    <ul class="tl-list">
                        <li>Complete on-page SEO (automated)</li>
                        <li>Submit to all directories (manual – 2 hrs)</li>
                        <li>Set up Google My Business (manual – 1 hr)</li>
                        <li>Create social media profiles (manual – 2 hrs)</li>
                    </ul>
                    <span class="tl-expected"><i class="fas fa-bullseye" style="color:#667eea;"></i> Expected: Google indexes optimized pages</span>
                </div>
            </div>
            <div class="tl-item">
                <div class="tl-badge b2">2</div>
                <div class="tl-card tc2">
                    <div class="tl-phase">Month 3–4</div>
                    <h4>Growth</h4>
                    <ul class="tl-list">
                        <li>Build 5–10 backlinks per week</li>
                        <li>Get customer reviews (10+ per month)</li>
                        <li>Post on social media (3–5 times/week)</li>
                        <li>Update GMB weekly</li>
                    </ul>
                    <span class="tl-expected"><i class="fas fa-arrow-up" style="color:#059669;"></i> Expected: Position 50+ → 20–30</span>
                </div>
            </div>
            <div class="tl-item" style="padding-bottom:0;">
                <div class="tl-badge b3">3</div>
                <div class="tl-card tc3">
                    <div class="tl-phase">Month 5–6</div>
                    <h4>Results 🏆</h4>
                    <ul class="tl-list">
                        <li>Continue building backlinks</li>
                        <li>Update content monthly</li>
                        <li>Monitor rankings weekly</li>
                    </ul>
                    <span class="tl-expected"><i class="fas fa-trophy" style="color:#d97706;"></i> Expected: Position 20–30 → 1–10 (Page 1!)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Buttons -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="seo-dashboard.php" class="btn-action purple">
        <i class="fas fa-chart-line"></i> Check Rankings
    </a>
</div>

</div>
    
    <script>
        function optimizeWholeSite() {
            if (!confirm('This will optimize your ENTIRE website (homepage, blogs, reviews, FAQs, and all service pages). Continue?')) {
                return;
            }
            
            const resultsDiv = document.getElementById('optimizationResults');
            resultsDiv.innerHTML = `
                <div style="text-align: center; padding: 40px; background: #f8f9fa; border-radius: 12px; margin-top: 30px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 48px; color: #667eea;"></i>
                    <h3 style="color: #667eea;">Optimizing Entire Website...</h3>
                    <p style="color: #666;">Fixing meta tags, schema, canonical — no content changes. Please wait...</p>
                </div>
            `;
            
            // Redirect to the full batch optimizer for 12,000+ pages
            window.location.href = 'auto-seo-optimizer-dashboard.php';
            return;
            fetch('../api/safe-seo-batch.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'batch_size=100&offset=0'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultsDiv.innerHTML = `
                        <div style="background: #d4edda; padding: 30px; border-radius: 12px; margin-top: 30px; border-left: 4px solid #28a745;">
                            <h3 style="color: #28a745;"><i class="fas fa-check-circle"></i> Batch 1 Complete — Continuing on Optimizer Page</h3>
                            <div style="margin-top: 20px;">
                                <p><strong>✅ Fixed:</strong> ${data.results.optimized} pages</p>
                                <p><strong>⏭ Skipped (already OK):</strong> ${data.results.skipped} pages</p>
                                <p><strong>Total:</strong> ${data.processed} / ${data.total} processed</p>
                            </div>
                            <div style="margin-top: 16px;">
                                <a href="auto-seo-optimizer-dashboard.php" style="display:inline-block;padding:12px 24px;background:#667eea;color:white;border-radius:10px;font-weight:700;text-decoration:none;">
                                    <i class="fas fa-cog"></i> Continue Full Optimization (${data.total} pages)
                                </a>
                            </div>
                        </div>
                    `;
                } else {
                    resultsDiv.innerHTML = `
                        <div style="background: #f8d7da; padding: 20px; border-radius: 8px; margin-top: 20px;">
                            <strong>Error:</strong> ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                resultsDiv.innerHTML = `
                    <div style="background: #f8d7da; padding: 20px; border-radius: 8px; margin-top: 20px;">
                        <strong>Error:</strong> ${error.message}
                    </div>
                `;
            });
        }
        
        function showDirectories() {
            const resultsDiv = document.getElementById('linkBuildingResults');
            resultsDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading directories...</div>';
            
            fetch('../api/link-building-system.php?action=get_directory_list')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                        <div style="margin-top: 30px;">
                            <h3 style="color: #667eea; margin-bottom: 20px;">
                                <i class="fas fa-list"></i> ${data.total} High-Authority Directories
                            </h3>
                            <div class="directory-list">
                    `;
                    
                    data.directories.forEach(dir => {
                        html += `
                            <div class="directory-item">
                                <h4>${dir.name}</h4>
                                <span class="priority ${dir.priority.toLowerCase()}">${dir.priority} Priority</span>
                                <div class="da"><strong>DA:</strong> ${dir.da}/100</div>
                                <p><strong>Category:</strong> ${dir.category}</p>
                                <p><strong>Benefits:</strong> ${dir.benefits}</p>
                                <div class="instructions"><strong>How to Submit:</strong>\n${dir.instructions}</div>
                                <a href="${dir.url}" target="_blank"><i class="fas fa-external-link-alt"></i> Visit ${dir.name}</a>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                    resultsDiv.innerHTML = html;
                }
            });
        }
        
        function showLinkOpportunities() {
            const resultsDiv = document.getElementById('linkBuildingResults');
            resultsDiv.innerHTML = '<div style="text-align: center; padding: 20px;"><i class="fas fa-spinner fa-spin"></i> Loading opportunities...</div>';
            
            fetch('../api/link-building-system.php?action=get_link_building_opportunities')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                        <div style="margin-top: 30px;">
                            <h3 style="color: #667eea; margin-bottom: 20px;">
                                <i class="fas fa-lightbulb"></i> ${data.total} Link Building Strategies
                            </h3>
                            <div class="feature-grid">
                    `;
                    
                    data.opportunities.forEach(opp => {
                        html += `
                            <div class="feature-card">
                                <h4><i class="fas fa-arrow-right"></i> ${opp.type}</h4>
                                <p style="margin-bottom: 15px;">${opp.description}</p>
                                <p><strong>Effort:</strong> ${opp.effort} | <strong>Value:</strong> ${opp.value}</p>
                                <div style="margin-top: 15px; padding: 15px; background: white; border-radius: 6px;">
                                    <strong>Steps:</strong>
                                    <ol style="margin-left: 20px; margin-top: 10px; font-size: 13px;">
                                        ${opp.steps.map(step => `<li>${step}</li>`).join('')}
                                    </ol>
                                </div>
                            </div>
                        `;
                    });
                    
                    html += '</div></div>';
                    resultsDiv.innerHTML = html;
                }
            });
        }
    </script>
</div>

<?php include '../includes/footer.php'; ?>
