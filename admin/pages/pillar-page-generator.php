<?php
/**
 * Pillar Page Generator
 * Generate 64 pillar pages for all services
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

// Start session with proper settings (must match login.php)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Pillar Page Generator';
include '../includes/header.php';

// 64 Services with metadata
$services_list = [
    // PIGEON NETS (13 services)
    ['name' => 'Pigeon Nets', 'slug' => 'pigeon-nets', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Net', 'slug' => 'pigeon-net', 'category' => 'PIGEON NETS'],
    ['name' => 'Balcony Netting', 'slug' => 'balcony-netting', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Net For Balcony', 'slug' => 'pigeon-net-for-balcony', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Nets Installation', 'slug' => 'pigeon-nets-installation', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Bird Netting', 'slug' => 'pigeon-bird-netting', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Net Installation', 'slug' => 'pigeon-net-installation', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Net Near Me', 'slug' => 'pigeon-net-near-me', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Net For Balcony Near Me', 'slug' => 'pigeon-net-for-balcony-near-me', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Net Installation Near Me', 'slug' => 'pigeon-net-installation-near-me', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Safety Nets', 'slug' => 'pigeon-safety-nets', 'category' => 'PIGEON NETS'],
    ['name' => 'Pigeon Net Price', 'slug' => 'pigeon-net-price', 'category' => 'PIGEON NETS'],
    ['name' => 'Kabutar Jali Near Me', 'slug' => 'kabutar-jali-near-me', 'category' => 'PIGEON NETS'],
    
    // BIRD NETS (9 services)
    ['name' => 'Bird Nets', 'slug' => 'bird-nets', 'category' => 'BIRD NETS'],
    ['name' => 'Bird Net', 'slug' => 'bird-net', 'category' => 'BIRD NETS'],
    ['name' => 'Bird Net For Balcony', 'slug' => 'bird-net-for-balcony', 'category' => 'BIRD NETS'],
    ['name' => 'Bird Net Near Me', 'slug' => 'bird-net-near-me', 'category' => 'BIRD NETS'],
    ['name' => 'Nets For Birds', 'slug' => 'nets-for-birds', 'category' => 'BIRD NETS'],
    ['name' => 'Net For Birds', 'slug' => 'net-for-birds', 'category' => 'BIRD NETS'],
    ['name' => 'Industrial Bird Netting', 'slug' => 'industrial-bird-netting', 'category' => 'BIRD NETS'],
    ['name' => 'Bird Netting', 'slug' => 'bird-netting', 'category' => 'BIRD NETS'],
    ['name' => 'Anti Bird Netting', 'slug' => 'anti-bird-netting', 'category' => 'BIRD NETS'],
    
    // SAFETY NETS (11 services)
    ['name' => 'Safety Nets', 'slug' => 'safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Balcony Safety Nets', 'slug' => 'balcony-safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Safety Nets For Balconies', 'slug' => 'safety-nets-for-balconies', 'category' => 'SAFETY NETS'],
    ['name' => 'Duct Area Safety Nets', 'slug' => 'duct-area-safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Monkey Safety Nets', 'slug' => 'monkey-safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Construction Safety Nets', 'slug' => 'construction-safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Industrial Safety Nets', 'slug' => 'industrial-safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Fall Safety Nets', 'slug' => 'fall-safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Fall Protection Nets', 'slug' => 'fall-protection-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Children Safety Nets', 'slug' => 'children-safety-nets', 'category' => 'SAFETY NETS'],
    ['name' => 'Pet Safety Nets', 'slug' => 'pet-safety-nets', 'category' => 'SAFETY NETS'],
    
    // SPORTS NETS (13 services)
    ['name' => 'Cricket Nets', 'slug' => 'cricket-nets', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Nets Price', 'slug' => 'cricket-nets-price', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Nets Near Me', 'slug' => 'cricket-nets-near-me', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Practice Net', 'slug' => 'cricket-practice-net', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Practice Nets', 'slug' => 'cricket-practice-nets', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Net Price', 'slug' => 'cricket-net-price', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Indoor Nets Near Me', 'slug' => 'cricket-indoor-nets-near-me', 'category' => 'SPORTS NETS'],
    ['name' => 'Indoor Cricket Nets Near Me', 'slug' => 'indoor-cricket-nets-near-me', 'category' => 'SPORTS NETS'],
    ['name' => 'Sports Nets', 'slug' => 'sports-nets', 'category' => 'SPORTS NETS'],
    ['name' => 'Sports Netting', 'slug' => 'sports-netting', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Netting', 'slug' => 'cricket-netting', 'category' => 'SPORTS NETS'],
    ['name' => 'Box Cricket Net', 'slug' => 'box-cricket-net', 'category' => 'SPORTS NETS'],
    ['name' => 'Cricket Net Installation', 'slug' => 'cricket-net-installation', 'category' => 'SPORTS NETS'],
    
    // INVISIBLE GRILLS (9 services)
    ['name' => 'Invisible Grills', 'slug' => 'invisible-grills', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'Invisible Grill Near Me', 'slug' => 'invisible-grill-near-me', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'SS Invisible Grills', 'slug' => 'ss-invisible-grills', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'Invisible Grill For Balcony', 'slug' => 'invisible-grill-for-balcony', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'Balcony Invisible Grill', 'slug' => 'balcony-invisible-grill', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'Invisible Grill For Balcony Near Me', 'slug' => 'invisible-grill-for-balcony-near-me', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'Invisible Safety Grill', 'slug' => 'invisible-safety-grill', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'Invisible Grill For Safety', 'slug' => 'invisible-grill-for-safety', 'category' => 'INVISIBLE GRILLS'],
    ['name' => 'Invisible Grill For Pigeons', 'slug' => 'invisible-grill-for-pigeons', 'category' => 'INVISIBLE GRILLS'],
    
    // CLOTH HANGERS (9 services)
    ['name' => 'Ceiling Cloth Hangers', 'slug' => 'ceiling-cloth-hangers', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Dry Cloth Hangers', 'slug' => 'dry-cloth-hangers', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Cloth Drying Hangers', 'slug' => 'cloth-drying-hangers', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Cloth Hanger For Balcony', 'slug' => 'cloth-hanger-for-balcony', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Pulley Cloth Drying Hanger', 'slug' => 'pulley-cloth-drying-hanger', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Pulley Cloth Hanger', 'slug' => 'pulley-cloth-hanger', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Laundry Hanger Dryer', 'slug' => 'laundry-hanger-dryer', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Clothes Hanger To Dry Clothes', 'slug' => 'clothes-hanger-to-dry-clothes', 'category' => 'CLOTH HANGERS'],
    ['name' => 'Clothes Hanger Drier', 'slug' => 'clothes-hanger-drier', 'category' => 'CLOTH HANGERS']
];

$total_services = count($services_list);
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
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.seo-section-body { padding: 28px 32px; }

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
.btn-action:disabled { opacity: .6; cursor: not-allowed; transform: none !important; }

/* ── Table ──────────────────────────────────────────── */
.services-table-container { overflow-x: auto; border-radius: 12px; }
.services-table { width: 100%; border-collapse: collapse; }
.services-table thead { background: linear-gradient(135deg,#667eea,#764ba2); }
.services-table th { padding: 14px 16px; text-align: left; font-weight: 700; color: #fff; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
.services-table tbody tr { background: #fff; transition: background .2s; }
.services-table tbody tr:hover { background: #f8faff; }
.services-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #475569; }
.services-table code { background: #f1f5f9; padding: 3px 7px; border-radius: 5px; font-family: monospace; font-size: 12px; color: #1e293b; font-weight: 600; }
.services-table a { color: #667eea; text-decoration: none; font-weight: 600; }
.services-table a:hover { color: #764ba2; text-decoration: underline; }
.category-badge { padding: 4px 10px; background: #eff6ff; color: #1e40af; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; display: inline-block; }
.status-badge { padding: 5px 12px; border-radius: 20px; font-size: 12px; font-weight: 700; display: inline-block; }
.status-exists { background: #d1fae5; color: #065f46; }
.status-missing { background: #fee2e2; color: #991b1b; }
.btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; background: linear-gradient(135deg,#667eea,#764ba2); color: #fff; cursor: pointer; transition: all .25s; display: inline-flex; align-items: center; justify-content: center; margin: 0 2px; font-size: 13px; }
.btn-icon:hover { transform: scale(1.1); box-shadow: 0 4px 12px rgba(102,126,234,.4); }

@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } }
@media(max-width:560px){ .stats-grid { grid-template-columns: 1fr; } .seo-hero { flex-direction: column; } }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-layer-group"></i> AI Pillar Page Generator</h1>
        <p>Generate <?php echo $total_services; ?> unique, AI-powered service pillar pages for SEO optimization</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-magic" style="margin-right:6px;"></i><?php echo $total_services; ?> Pillar Pages</span>
</div>

<!-- Info Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-robot"></i></div>
    <div>
        <strong>AI-Powered Pillar Pages</strong>
        <p>Each pillar page is uniquely generated by Google Gemini AI with 500–600 words of SEO-optimized content. Pages include service overview, benefits, and Chennai-specific context. Each page displays 188 area buttons linking to location-specific pages. <strong>Generation time: ~3–5 minutes for all <?php echo $total_services; ?> pages.</strong></p>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-layer-group"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Pillar Pages</h3>
            <div class="value"><?php echo $total_services; ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-map-marker-alt"></i></div>
        <div class="stat-text-wrap">
            <h3>Areas Per Page</h3>
            <div class="value">188</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-sitemap"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Area Pages</h3>
            <div class="value">12,032</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-chart-line"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Pages</h3>
            <div class="value">12,096</div>
        </div>
    </div>
</div>

<!-- Services Section -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-list" style="color:#667eea;margin-right:8px;"></i>All <?php echo $total_services; ?> Services</h2>
    </div>
    <div class="seo-section-body">
        <div class="services-table-container">
            <table class="services-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Service Name</th>
                        <th>Slug</th>
                        <th>Category</th>
                        <th>Pillar Page URL</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $root_dir = dirname(dirname(dirname(__FILE__)));
                    foreach ($services_list as $index => $service): 
                        $file_path = $root_dir . '/' . $service['slug'] . '.php';
                        $exists = file_exists($file_path);
                    ?>
                    <tr data-slug="<?php echo $service['slug']; ?>">
                        <td><?php echo $index + 1; ?></td>
                        <td><strong><?php echo htmlspecialchars($service['name']); ?></strong></td>
                        <td><code><?php echo $service['slug']; ?>.php</code></td>
                        <td><span class="category-badge"><?php echo $service['category']; ?></span></td>
                        <td><a href="<?php echo SITE_URL; ?>/<?php echo $service['slug']; ?>.php" target="_blank">/<?php echo $service['slug']; ?>.php</a></td>
                        <td>
                            <?php if ($exists): ?>
                                <span class="status-badge status-exists">✓ Exists</span>
                            <?php else: ?>
                                <span class="status-badge status-missing">✗ Missing</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn-icon" onclick="generateSinglePage('<?php echo $service['slug']; ?>')" title="Generate">
                                <i class="fas fa-magic"></i>
                            </button>
                            <?php if ($exists): ?>
                            <a href="<?php echo SITE_URL; ?>/<?php echo $service['slug']; ?>.php" target="_blank" class="btn-icon" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="action-row">
            <button id="generateAllBtn" onclick="generateAllPages()" class="btn-action green big">
                <i class="fas fa-magic"></i> Generate All <?php echo $total_services; ?> Pages
            </button>
        </div>
        <div id="pillarProgressContainer"></div>
    </div>
</div>

<!-- Bottom Buttons -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="view-generated-pages.php" class="btn-action purple">
        <i class="fas fa-eye"></i> View Generated Pages
    </a>
</div>

</div>

<script>
// All 64 slugs for sequential generation
const PILLAR_SLUGS = <?php echo json_encode(array_column($services_list, 'slug')); ?>;
const PILLAR_NAMES = <?php echo json_encode(array_column($services_list, 'name')); ?>;

async function generateAllPages() {
    const confirmed = await confirm('Generate all ' + PILLAR_SLUGS.length + ' pillar pages with AI?\n\nEach page is generated individually (no timeout). Progress shown below.');
    if (!confirmed) return false;

    const btn = document.getElementById('generateAllBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';

    // Build or show progress bar — insert as full-width block after the page header
    let progressDiv = document.getElementById('pillarProgress');
    if (!progressDiv) {
        progressDiv = document.createElement('div');
        progressDiv.id = 'pillarProgress';
        progressDiv.style.cssText = 'background:#fff;border-radius:16px;padding:24px;margin-top:20px;box-shadow:0 4px 20px rgba(0,0,0,.1);';
        document.getElementById('pillarProgressContainer').appendChild(progressDiv);
    }

    let done = 0, failed = 0, total = PILLAR_SLUGS.length;

    const updateProgress = (msg, type) => {
        progressDiv.innerHTML = `
            <div style="margin-bottom:12px;font-size:15px;font-weight:700;color:#1e293b;">
                Pillar Page Generation: <span style="color:#667eea">${done + failed}</span> / ${total}
                &nbsp;&nbsp;<span style="color:#10b981">✓ ${done}</span>
                &nbsp;&nbsp;<span style="color:#ef4444">✗ ${failed}</span>
            </div>
            <div style="background:#e2e8f0;border-radius:8px;height:10px;margin-bottom:12px;">
                <div style="background:linear-gradient(90deg,#667eea,#764ba2);height:10px;border-radius:8px;width:${Math.round(((done+failed)/total)*100)}%;transition:width .4s;"></div>
            </div>
            <div style="font-size:13px;color:${type==='ok'?'#059669':type==='err'?'#dc2626':'#64748b'};">${msg}</div>`;
    };

    for (let i = 0; i < PILLAR_SLUGS.length; i++) {
        const slug = PILLAR_SLUGS[i];
        const name = PILLAR_NAMES[i];
        updateProgress('⏳ Generating: ' + name + ' (' + slug + ')...', 'info');

        try {
            const res = await fetch('../api/generate-pillar-pages.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'generate_single', slug: slug})
            });
            const data = await res.json();
            if (data.success) {
                done++;
                // Update table row status
                const row = document.querySelector(`tr[data-slug="${slug}"]`);
                if (row) {
                    const badge = row.querySelector('.status-badge');
                    if (badge) { badge.className = 'status-badge status-exists'; badge.textContent = '✓ Exists'; }
                }
                updateProgress('✅ ' + name + ' done (' + done + '/' + total + ')', 'ok');
            } else {
                failed++;
                updateProgress('❌ ' + name + ': ' + (data.error || 'Failed'), 'err');
            }
        } catch (e) {
            failed++;
            updateProgress('❌ ' + name + ': Network error', 'err');
        }
        // Small delay between pages to avoid rate-limiting
        await new Promise(r => setTimeout(r, 800));
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-magic"></i> Generate All ' + total + ' Pages';
    btn.classList.remove('disabled');
    updateProgress('🎉 Complete! Generated: ' + done + ', Failed: ' + failed, done > failed ? 'ok' : 'err');
    if (done > 0) setTimeout(() => location.reload(), 3000);
    return false;
}

async function generateSinglePage(slug) {
    // Use beautiful custom confirm dialog
    const confirmed = await confirm(`Generate pillar page for ${slug}?`);
    
    if (!confirmed) {
        return;
    }
    
    // Show loading in the row
    const row = document.querySelector(`tr[data-slug="${slug}"]`);
    const statusCell = row.querySelector('.status-badge');
    const originalStatus = statusCell.innerHTML;
    statusCell.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    try {
        // Call backend API
        const response = await fetch('../api/generate-pillar-pages.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'generate_single', slug: slug })
        });
        
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        
        const data = await response.json();
        
        if (data.success) {
            statusCell.className = 'status-badge status-exists';
            statusCell.innerHTML = '✓ EXISTS';
            GCMAlert.success(`Successfully generated ${slug}.php`, 'Page Created! ✓');
            setTimeout(() => location.reload(), 1500);
        } else {
            statusCell.innerHTML = originalStatus;
            GCMAlert.error(data.error || 'Unknown error occurred', 'Generation Failed');
        }
    } catch (error) {
        statusCell.innerHTML = originalStatus;
        console.error('Generation error:', error);
        GCMAlert.error('Network error: ' + error.message, 'Connection Error');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
