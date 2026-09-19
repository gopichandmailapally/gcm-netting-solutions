<?php
/**
 * FAQ Generator - Admin Panel
 * Auto-generate and bulk-generate FAQs with AI
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../config/faq-categories.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$db = Database::getInstance();
$page_title = 'Auto FAQ Generator';
include '../includes/header.php';

// Load settings from DB (survives redeployment)
try {
    $db_faq = $db->fetchOne("SELECT * FROM faq_settings WHERE id = 1");
} catch (Exception $e) {
    $db_faq = null;
}
$faq_config = [
    'enabled'         => (bool)(($db_faq['auto_generate_enabled'] ?? 1)),
    'daily_count'     => (int)(($db_faq['daily_faq_count']      ?? 1)),
    'generation_time' => $db_faq['generation_time']  ?? 'random',
    'last_run'        => $db_faq['last_run_date']     ?? null,
    'total_generated' => (int)(($db_faq['total_generated']      ?? 0)),
];

// Get statistics (database not required - using default values)
$total_faqs = ['count' => 0];
$ai_generated = ['count' => 0];

// Get ALL 30 categories from config
$all_faq_categories = get_faq_categories();

// Icon and description map for each category
$cat_meta = [
    'General Information'            => ['icon' => 'fa-info-circle',      'desc' => 'Common questions about our company and services'],
    'Safety Nets (Balcony/Children)' => ['icon' => 'fa-shield-alt',       'desc' => 'FAQs about balcony and children safety nets'],
    'Pigeon Nets & Bird Control'     => ['icon' => 'fa-dove',             'desc' => 'Questions about pigeon and bird control nets'],
    'Bird Netting Solutions'         => ['icon' => 'fa-kiwi-bird',        'desc' => 'FAQs on bird netting products and installation'],
    'Sports Nets'                    => ['icon' => 'fa-futbol',           'desc' => 'Questions about sports and activity nets'],
    'Cricket Practice Nets'          => ['icon' => 'fa-baseball-ball',    'desc' => 'FAQs about cricket practice net installation'],
    'Invisible Grills'               => ['icon' => 'fa-th',               'desc' => 'Questions about invisible grill products'],
    'Balcony Safety Nets'            => ['icon' => 'fa-building',         'desc' => 'FAQs specific to balcony safety net installation'],
    'Children Safety Nets'           => ['icon' => 'fa-child',            'desc' => 'Safety net questions for homes with children'],
    'Staircase Safety Nets'          => ['icon' => 'fa-level-up-alt',     'desc' => 'FAQs about staircase and void area nets'],
    'Window Safety Nets'             => ['icon' => 'fa-window-maximize',  'desc' => 'Questions about window safety net fitting'],
    'Terrace Safety Nets'            => ['icon' => 'fa-home',             'desc' => 'FAQs about terrace and rooftop safety nets'],
    'Cloth Hangers & Drying'         => ['icon' => 'fa-tshirt',           'desc' => 'Questions about ceiling cloth hanger systems'],
    'Construction Safety Nets'       => ['icon' => 'fa-hard-hat',         'desc' => 'FAQs for construction site safety netting'],
    'Industrial Safety Nets'         => ['icon' => 'fa-industry',         'desc' => 'Questions about industrial safety net solutions'],
    'Monkey Protection Nets'         => ['icon' => 'fa-paw',              'desc' => 'FAQs about monkey and wildlife protection nets'],
    'Cat Protection Nets'            => ['icon' => 'fa-cat',              'desc' => 'Questions about cat and pet safety nets'],
    'Installation & Service'         => ['icon' => 'fa-tools',            'desc' => 'FAQs about our installation process and team'],
    'Pricing & Quotation'            => ['icon' => 'fa-rupee-sign',       'desc' => 'Questions about pricing, quotes and payment'],
    'Warranty & Maintenance'         => ['icon' => 'fa-certificate',      'desc' => 'FAQs about warranty terms and maintenance'],
    'Materials & Quality'            => ['icon' => 'fa-cubes',            'desc' => 'Questions about net materials and quality standards'],
    'Maintenance & Care'             => ['icon' => 'fa-wrench',           'desc' => 'FAQs on how to maintain and care for nets'],
    'Net Colors & Types'             => ['icon' => 'fa-palette',          'desc' => 'Questions about available net colors and types'],
    'Net Thickness & Mesh Size'      => ['icon' => 'fa-ruler',            'desc' => 'FAQs about mesh size and thickness options'],
    'UV Protection & Durability'     => ['icon' => 'fa-sun',              'desc' => 'Questions about UV resistance and net lifespan'],
    'Service Areas & Coverage'       => ['icon' => 'fa-map-marker-alt',   'desc' => 'FAQs about areas we serve in Chennai'],
    'Emergency Services'             => ['icon' => 'fa-phone-alt',        'desc' => 'Questions about emergency and urgent net repairs'],
    'Commercial Projects'            => ['icon' => 'fa-briefcase',        'desc' => 'FAQs for commercial and business net projects'],
    'Residential Projects'           => ['icon' => 'fa-house-user',       'desc' => 'Questions about residential net installations'],
    'Custom Solutions'               => ['icon' => 'fa-cogs',             'desc' => 'FAQs about custom and special net requirements'],
];

$categories = [];
foreach ($all_faq_categories as $cat_name) {
    $meta = $cat_meta[$cat_name] ?? ['icon' => 'fa-question-circle', 'desc' => ''];
    $categories[] = [
        'name'        => $cat_name,
        'slug'        => strtolower(str_replace([' ', '&', '(', ')', '/'], ['-', 'and', '', '', '-'], $cat_name)),
        'icon'        => $meta['icon'],
        'description' => $meta['desc'],
    ];
}
$settings = [
    'daily_faq_count' => $faq_config['daily_count'] ?? 1,
    'auto_generate_enabled' => $faq_config['enabled'] ?? true,
    'last_generation_time' => $faq_config['last_run'] ?? null,
    'total_generated' => $faq_config['total_generated'] ?? 0,
    'random_time' => true
];
$recent_faqs = [];
?>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-question-circle"></i> Auto FAQ Generator</h1>
        <p>Auto-generate FAQs with AI &mdash; 30 categories, daily automation available</p>
    </div>
    <div class="hero-actions">
        <button class="btn-action blue sm" onclick="scrollToBulkGenerator()">
            <i class="fas fa-layer-group"></i> Bulk Generate
        </button>
        <button class="btn-action green sm" onclick="generateSingleFAQ()">
            <i class="fas fa-magic"></i> Generate 1 FAQ Now
        </button>
    </div>
</div>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-question-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Total FAQs</h3>
            <div class="value"><?php echo number_format($total_faqs['count']); ?></div>
            <div class="sub"><span class="stat-badge green"><?php echo $ai_generated['count']; ?> AI Generated</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-robot"></i></div>
        <div class="stat-text-wrap">
            <h3>Daily Auto-Generate</h3>
            <div class="value"><?php echo $settings['daily_faq_count'] ?? 1; ?></div>
            <div class="sub"><span class="stat-badge <?php echo $settings['auto_generate_enabled'] ? 'green' : 'red'; ?>"><?php echo $settings['auto_generate_enabled'] ? 'Enabled' : 'Disabled'; ?></span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><i class="fas fa-calendar-check"></i></div>
        <div class="stat-text-wrap">
            <h3>Last Generated</h3>
            <div class="value" style="font-size:1.1rem!important;"><?php echo $settings['last_generation_time'] ? date('M j, g:i A', strtotime($settings['last_generation_time'])) : 'Never'; ?></div>
            <div class="sub"><span class="stat-badge purple">Auto-runs daily</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-chart-line"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Generated</h3>
            <div class="value"><?php echo number_format($settings['total_generated'] ?? 0); ?></div>
            <div class="sub"><span class="stat-badge blue">Lifetime count</span></div>
        </div>
    </div>
</div>

<!-- Section 1: Auto-Generation Settings -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">1</div>
        <h2><i class="fas fa-cog" style="color:#3b82f6;margin-right:8px;"></i>Auto-Generation Settings</h2>
        <div class="toggle-container" style="margin-left:auto;">
            <label class="toggle-switch">
                <input type="checkbox" id="autoGenerateToggle"
                       <?php echo $settings['auto_generate_enabled'] ? 'checked' : ''; ?>
                       onchange="toggleAutoGeneration(this.checked)">
                <span class="toggle-slider"></span>
            </label>
            <span id="toggleStatus" class="toggle-status <?php echo $settings['auto_generate_enabled'] ? 'active' : ''; ?>">
                <?php echo $settings['auto_generate_enabled'] ? 'Auto-Gen ON' : 'Auto-Gen OFF'; ?>
            </span>
        </div>
    </div>
    <div class="seo-section-body">
        <form id="faqSettingsForm" class="settings-form">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-calendar-day"></i> Daily FAQ Count</label>
                    <input type="number" name="daily_count"
                           value="<?php echo $settings['daily_faq_count'] ?? 1; ?>"
                           min="1" max="10" class="form-control">
                    <small>How many FAQs to generate daily (1-10)</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-random"></i> Generation Time</label>
                    <select name="generation_time" class="form-control">
                        <option value="random" selected>Random (Any time in 24hrs)</option>
                        <option value="00:00">12:00 AM (Midnight)</option>
                        <option value="02:00">2:00 AM (Early Morning)</option>
                        <option value="06:00">6:00 AM (Morning)</option>
                        <option value="12:00">12:00 PM (Noon)</option>
                        <option value="18:00">6:00 PM (Evening)</option>
                        <option value="22:00">10:00 PM (Night)</option>
                    </select>
                    <small>⭐ Recommended: Random time for natural posting pattern</small>
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn-action green sm">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <button type="button" class="btn-action gray sm" onclick="testGeneration()">
                    <i class="fas fa-flask"></i> Test Generation Now
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Section 2: Bulk FAQ Generation -->
<div class="seo-section" id="bulkCard">
    <div class="seo-section-head">
        <div class="sec-num purple">2</div>
        <h2><i class="fas fa-layer-group" style="color:#667eea;margin-right:8px;"></i>Bulk FAQ Generation</h2>
        <span class="sec-badge" style="margin-left:auto;"><i class="fas fa-robot" style="margin-right:5px;"></i>AI Powered</span>
    </div>
    <div class="seo-section-body">
        <form id="bulkGenerateForm" class="bulk-form" method="POST" onsubmit="return handleBulkGeneration(event);">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Number of FAQs</label>
                    <input type="number" name="faq_count" id="faqCountInput"
                           value="5" min="1" max="50" class="form-control"
                           style="font-size: 18px; font-weight: 600; padding: 15px;">
                    <small><strong>⚠️ You can change this number!</strong> Enter 1-50 FAQs to generate</small>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-folder"></i> Category</label>
                    <select name="category" class="form-control" style="font-size: 14px;">
                        <option value="random" style="font-weight: bold; background: #E0F2FE; color: #0369A1;">🎲 Random Categories (All 30 Categories)</option>
                        <option disabled>──────────────────────────</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat['name']); ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small><strong>📌 Random:</strong> Distributes FAQs evenly across ALL 30 categories | <strong>Specific:</strong> All FAQs in one category</small>
                </div>
            </div>
            <div class="form-group">
                <label><i class="fas fa-lightbulb"></i> Focus Topics (Optional)</label>
                <input type="text" name="topics" class="form-control"
                       placeholder="e.g., installation, pricing, warranty, maintenance">
                <small>Comma-separated topics for focused FAQs</small>
            </div>
            <div class="bulk-actions">
                <button type="submit" class="btn-action green">
                    <i class="fas fa-magic"></i> Generate FAQs with AI
                </button>
            </div>
        </form>
        <!-- Progress Bar -->
        <div id="bulkProgress" class="progress-section" style="display: none;">
            <div class="progress-header">
                <h3><i class="fas fa-spinner fa-spin"></i> Generating FAQs...</h3>
                <span id="progressCount">0 / 0</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" id="progressBar"></div>
            </div>
            <div class="progress-details" id="progressDetails"></div>
        </div>
    </div>
</div>

<!-- Section 3: Where Are My FAQs -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num orange">3</div>
        <h2><i class="fas fa-info-circle" style="color:#f59e0b;margin-right:8px;"></i>Where Are My Generated FAQs?</h2>
        <span style="margin-left:auto;background:linear-gradient(135deg,#f59e0b,#d97706);color:white;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;">3 Locations</span>
    </div>
    <div class="seo-section-body">
        <p class="info-lead">FAQs are automatically saved and available in 3 places:</p>
        <div class="quick-links">
            <a href="manage-faqs.php" class="quick-link purple">
                <i class="fas fa-list-ul"></i> View All FAQs (Admin Panel)
            </a>
            <a href="<?php echo SITE_URL; ?>/faqs.php" class="quick-link green" target="_blank">
                <i class="fas fa-globe"></i> View FAQs on Website (Public Page)
            </a>
            <a href="<?php echo SITE_URL; ?>/check-faqs.php" class="quick-link blue" target="_blank">
                <i class="fas fa-check-circle"></i> Verify FAQs (Technical Check)
            </a>
        </div>
        <p class="storage-note"><i class="fas fa-folder"></i> <strong>Storage:</strong> FAQs are saved as JSON files in <code>/data/faqs/</code> directory</p>
    </div>
</div>

<!-- Section 4: Recent FAQs -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">4</div>
        <h2><i class="fas fa-history" style="color:#10b981;margin-right:8px;"></i>Recent FAQs</h2>
        <a href="manage-faqs.php" class="btn-action gray sm" style="margin-left:auto;">
            <i class="fas fa-list"></i> Manage All FAQs
        </a>
    </div>
    <div class="seo-section-body">
        <div class="faq-list">
            <?php if (empty($recent_faqs)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No FAQs Yet</h3>
                <p>Generate some to get started!</p>
                <button class="btn-action green" style="margin-top:12px;" onclick="generateSingleFAQ()">
                    <i class="fas fa-magic"></i> Generate First FAQ
                </button>
            </div>
            <?php else: ?>
            <?php foreach ($recent_faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-header">
                    <div class="faq-badge">
                        <?php if ($faq['is_ai_generated']): ?>
                        <span class="badge-ai"><i class="fas fa-robot"></i> AI</span>
                        <?php endif; ?>
                        <span class="badge-category"><?php echo $faq['category_name']; ?></span>
                    </div>
                    <div class="faq-stats">
                        <span><i class="fas fa-eye"></i> <?php echo $faq['views']; ?></span>
                        <span><i class="fas fa-thumbs-up"></i> <?php echo $faq['helpful_count']; ?></span>
                    </div>
                </div>
                <div class="faq-question">
                    <i class="fas fa-question-circle"></i>
                    <?php echo htmlspecialchars($faq['question']); ?>
                </div>
                <div class="faq-answer"><?php echo substr(strip_tags($faq['answer']), 0, 150) . '...'; ?></div>
                <div class="faq-footer">
                    <span class="faq-date"><i class="fas fa-clock"></i> <?php echo date('M j, Y g:i A', strtotime($faq['created_at'])); ?></span>
                    <div class="faq-actions">
                        <button class="btn-icon" onclick="editFAQ(<?php echo $faq['id']; ?>)"><i class="fas fa-edit"></i></button>
                        <button class="btn-icon" onclick="viewFAQ(<?php echo $faq['id']; ?>)"><i class="fas fa-eye"></i></button>
                        <button class="btn-icon danger" onclick="deleteFAQ(<?php echo $faq['id']; ?>)"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Section 5: FAQ Categories -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">5</div>
        <h2><i class="fas fa-folder-open" style="color:#667eea;margin-right:8px;"></i>FAQ Categories</h2>
        <span class="sec-badge" style="margin-left:auto;">30 Total</span>
    </div>
    <div class="seo-section-body">
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
            <?php $cat_count = $db->fetchOne("SELECT COUNT(*) as count FROM faqs WHERE category = ? AND is_active = 1", [$cat['slug']], 's'); ?>
            <div class="category-card">
                <div class="category-icon"><i class="fas <?php echo $cat['icon']; ?>"></i></div>
                <h4><?php echo $cat['name']; ?></h4>
                <p><?php echo $cat['description']; ?></p>
                <div class="category-count"><?php echo $cat_count['count'] ?? 0; ?> FAQs</div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Bottom Navigation -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="manage-faqs.php" class="btn-action purple">
        <i class="fas fa-list-ul"></i> Manage FAQs
    </a>
    <a href="<?php echo SITE_URL; ?>/faqs.php" class="btn-action blue" target="_blank">
        <i class="fas fa-globe"></i> View on Website
    </a>
</div>

</div>

<style>
/* ═══ Auto FAQ Generator — Complete SEO System Theme ═══ */
@keyframes fadeInUp { from{opacity:0;transform:translateY(20px)} to{opacity:1;transform:translateY(0)} }
@keyframes float    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
@keyframes shimmer  { 0%{background-position:-400px 0} 100%{background-position:400px 0} }

.seo-page { padding: 0; }
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 32px; font-weight: 800; color: #1e293b; margin: 0 0 6px; }
.seo-hero-left h1 i { color: #667eea; margin-right: 10px; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.hero-actions { display: flex; gap: 12px; flex-shrink: 0; flex-wrap: wrap; }

/* ── Stat cards ─────────────────────────── */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 26px 22px; display: flex !important; align-items: center !important; gap: 16px !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 16%; bottom: 16%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f97316,#dc2626); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 0 6px !important; }
.stat-card .sub { font-size: 12px; }
.stat-badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.stat-badge.green  { background: #d1fae5; color: #065f46; }
.stat-badge.red    { background: #fee2e2; color: #991b1b; }
.stat-badge.blue   { background: #dbeafe; color: #1e40af; }
.stat-badge.purple { background: #ede9fe; color: #5b21b6; }

/* ── Section cards ─────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.sec-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.seo-section-body { padding: 28px 32px; }

/* ── Action buttons ────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 8px; padding: 12px 22px; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; white-space: nowrap; }
.btn-action:hover { transform: translateY(-2px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 4px 14px rgba(40,167,69,.35); }
.btn-action.green:hover  { box-shadow: 0 8px 24px rgba(40,167,69,.45); }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 4px 14px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 8px 24px rgba(102,126,234,.45); }
.btn-action.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; box-shadow: 0 4px 14px rgba(59,130,246,.35); }
.btn-action.blue:hover   { box-shadow: 0 8px 24px rgba(59,130,246,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; transform: none; }
.btn-action.sm { padding: 9px 16px; font-size: 13px; }

/* ── Toggle switch ─────────────────────── */
.toggle-container { display: flex; align-items: center; gap: 12px; }
.toggle-switch { position: relative; width: 60px; height: 30px; flex-shrink: 0; }
.toggle-switch input { opacity: 0; width: 0; height: 0; }
.toggle-slider { position: absolute; cursor: pointer; inset: 0; background: #cbd5e1; border-radius: 30px; transition: .4s; }
.toggle-slider:before { position: absolute; content: ""; height: 22px; width: 22px; left: 4px; bottom: 4px; background: white; border-radius: 50%; transition: .4s; box-shadow: 0 2px 6px rgba(0,0,0,.2); }
.toggle-switch input:checked + .toggle-slider { background: linear-gradient(135deg,#10b981,#059669); }
.toggle-switch input:checked + .toggle-slider:before { transform: translateX(30px); }
.toggle-status { font-weight: 700; color: #64748b; font-size: 14px; transition: color .3s; }
.toggle-status.active { color: #10b981; }

/* ── Form controls ─────────────────────── */
.settings-form .form-row, .bulk-form .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { display: flex; flex-direction: column; gap: 6px; margin-bottom: 16px; }
.form-group label { font-size: 13px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px; text-transform: uppercase; letter-spacing: .4px; }
.form-group label i { color: #667eea; }
.form-group small { color: #64748b; font-size: 12px; }
.form-control { padding: 11px 14px; border: 2px solid #e0e7ff; border-radius: 10px; font-size: 14px; color: #1e293b; background: white; outline: none; transition: border-color .2s, box-shadow .2s; width: 100%; box-sizing: border-box; }
.form-control:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.15); }
.form-actions { display: flex; gap: 12px; flex-wrap: wrap; padding-top: 4px; }
.bulk-actions { margin-top: 8px; }

/* ── Quick links ───────────────────────── */
.info-lead { font-size: 14px; color: #64748b; margin-bottom: 18px; }
.quick-links { display: grid; gap: 12px; margin-bottom: 18px; }
.quick-link { padding: 16px 20px; border-radius: 14px; font-weight: 700; font-size: 14px; color: white; display: flex; align-items: center; gap: 10px; text-decoration: none; transition: all .25s; }
.quick-link.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.quick-link.green  { background: linear-gradient(135deg,#10b981,#059669); }
.quick-link.blue   { background: linear-gradient(135deg,#06b6d4,#0284c7); }
.quick-link:hover  { transform: translateX(4px); box-shadow: 0 8px 24px rgba(0,0,0,.15); }
.storage-note { font-size: 13px; color: #64748b; margin: 0; border-top: 1px solid #f1f5f9; padding-top: 14px; }
.storage-note code { background: #eef2ff; color: #3730a3; padding: 2px 7px; border-radius: 5px; font-size: 12px; }

/* ── Progress ──────────────────────────── */
.progress-section { margin-top: 24px; padding: 24px; background: linear-gradient(135deg,#f0f9ff,#e0f2fe); border-radius: 16px; border: 2px solid #bfdbfe; }
.progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
.progress-header h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px; }
#progressCount { font-size: 14px; font-weight: 700; color: #3b82f6; }
.progress-bar { background: #dbeafe; border-radius: 20px; height: 10px; overflow: hidden; }
.progress-fill { height: 100%; background: linear-gradient(90deg,#667eea,#06b6d4); border-radius: 20px; transition: width .4s ease; }

/* ── FAQ list ──────────────────────────── */
.faq-list { display: flex; flex-direction: column; gap: 14px; }
.faq-item { background: #f8fafc; padding: 20px; border-radius: 14px; border: 2px solid #e2e8f0; transition: all .3s; border-left: 4px solid #667eea; }
.faq-item:hover { border-color: #667eea; transform: translateX(4px); box-shadow: 0 6px 20px rgba(102,126,234,.15); }
.faq-header { display: flex; justify-content: space-between; margin-bottom: 12px; }
.faq-badge { display: flex; gap: 8px; }
.badge-ai { background: linear-gradient(135deg,#8b5cf6,#7c3aed); color: white; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.badge-category { background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; }
.faq-stats { display: flex; gap: 12px; font-size: 13px; color: #64748b; }
.faq-question { font-size: 15px; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: flex; align-items: flex-start; gap: 8px; }
.faq-question i { color: #667eea; margin-top: 2px; }
.faq-answer { font-size: 13px; color: #64748b; line-height: 1.7; margin-bottom: 12px; }
.faq-footer { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #e2e8f0; }
.faq-date { font-size: 12px; color: #94a3b8; }
.faq-actions { display: flex; gap: 8px; }
.btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; background: #e2e8f0; color: #475569; cursor: pointer; transition: all .25s; display: inline-flex; align-items: center; justify-content: center; }
.btn-icon:hover { background: #667eea; color: white; transform: scale(1.1); }
.btn-icon.danger:hover { background: #ef4444; }

/* ── Category grid ─────────────────────── */
.category-grid { display: grid; grid-template-columns: repeat(auto-fill,minmax(175px,1fr)); gap: 16px; }
.category-card { background: white; padding: 20px 16px; border-radius: 16px; border: 2px solid #e8ecf5; text-align: center; transition: all .3s; cursor: default; position: relative; overflow: hidden; }
.category-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg,#667eea,#764ba2); transform: scaleX(0); transition: transform .3s; transform-origin: left; }
.category-card:hover::before { transform: scaleX(1); }
.category-card:hover { transform: translateY(-5px); box-shadow: 0 12px 30px rgba(102,126,234,.2); border-color: #c4b5fd; }
.category-icon {
    width: 52px; height: 52px; margin: 0 auto 12px;
    border-radius: 14px;
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: white; display: flex; align-items: center;
    justify-content: center; font-size: 22px;
    box-shadow: 0 4px 14px rgba(102,126,234,.3);
    animation: float 4s ease-in-out infinite;
}
.category-card:nth-child(3n)   .category-icon { background: linear-gradient(135deg,#10b981,#059669); box-shadow: 0 4px 14px rgba(16,185,129,.3); }
.category-card:nth-child(3n+1) .category-icon { background: linear-gradient(135deg,#f59e0b,#d97706); box-shadow: 0 4px 14px rgba(245,158,11,.3); }
.category-card:nth-child(3n+2) .category-icon { background: linear-gradient(135deg,#06b6d4,#0284c7); box-shadow: 0 4px 14px rgba(6,182,212,.3); }
.category-card h4 { font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 5px; line-height: 1.4; }
.category-card p  { font-size: 11px; color: #64748b; margin-bottom: 10px; line-height: 1.5; }
.category-count { font-size: 13px; font-weight: 700; background: linear-gradient(135deg,#eef2ff,#e0e7ff); color: #3730a3; padding: 4px 12px; border-radius: 20px; display: inline-block; }

/* ── Empty state ───────────────────────── */
.empty-state { text-align: center; padding: 60px 30px; }
.empty-state i { font-size: 64px; color: #c4b5fd; margin-bottom: 16px; display: block; animation: float 3s ease-in-out infinite; }
.empty-state h3 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0 0 8px; }
.empty-state p { font-size: 14px; color: #64748b; margin: 0; }

/* ── Toast ─────────────────────────────── */
.fq-toast { position: fixed; bottom: 28px; right: 28px; padding: 14px 22px; border-radius: 14px; font-size: 14px; font-weight: 600; box-shadow: 0 8px 28px rgba(0,0,0,.18); z-index: 10000; display: flex; align-items: center; gap: 10px; animation: fadeInUp .35s ease; min-width: 260px; max-width: 380px; }
.fq-toast.success { background: linear-gradient(135deg,#10b981,#059669); color: white; }
.fq-toast.error   { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; }
.fq-toast.info    { background: linear-gradient(135deg,#667eea,#764ba2); color: white; }

@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:768px){ .settings-form .form-row,.bulk-form .form-row{grid-template-columns:1fr;} .category-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px){ .seo-hero{flex-direction:column;} .stats-grid{grid-template-columns:1fr;} }
</style>

<script>
// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    
    // Save Settings Form
    const settingsForm = document.getElementById('faqSettingsForm');
    if (settingsForm) {
        settingsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('../api/save-faq-settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Settings saved successfully!', 'success');
                } else {
                    showToast('Error: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error saving settings', 'error');
            });
        });
    }

    
});

// Handle Bulk Generation (called from form onsubmit)
function handleBulkGeneration(event) {
    event.preventDefault();
    event.stopPropagation();
    
    console.log('handleBulkGeneration called');
    
    const form = document.getElementById('bulkGenerateForm');
    const formData = new FormData(form);
    const count = formData.get('faq_count');
    
    console.log('Form data:', {
        count: count,
        category: formData.get('category'),
        topics: formData.get('topics')
    });
    
    if (!confirm(`Generate ${count} FAQs with AI? This will take about ${count * 5} seconds.`)) {
        return false;
    }
    
    console.log('User confirmed, starting generation...');
    
    // Show progress
    document.getElementById('bulkProgress').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressCount').textContent = `0 / ${count}`;
    
    // Disable form
    const submitBtn = form.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    }
    
    fetch('../api/generate-bulk-faqs-simple.php', {
        method: 'POST',
        body: formData,
        redirect: 'follow', // Follow redirects
        credentials: 'same-origin' // Include cookies
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response URL:', response.url);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.text(); // Get as text first
    })
    .then(text => {
        console.log('Raw response:', text.substring(0, 200));
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            throw new Error('API returned invalid JSON. Response: ' + text.substring(0, 100));
        }
    })
    .then(data => {
        console.log('Parsed data:', data);
        if (data.success) {
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('progressCount').textContent = `${data.generated} / ${count}`;
            alert(`✅ Success! Generated ${data.generated} FAQs successfully!\n\nReloading page to show new FAQs...`);
            setTimeout(() => location.reload(), 1000);
        } else {
            console.error('Generation failed:', data.message);
            alert('❌ Error: ' + (data.message || 'Unknown error'));
            document.getElementById('bulkProgress').style.display = 'none';
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-magic"></i> Generate FAQs with AI';
            }
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('❌ Network Error: ' + error.message + '\n\nPlease check:\n1. Admin session active\n2. API file exists\n3. Server connection');
        document.getElementById('bulkProgress').style.display = 'none';
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-magic"></i> Generate FAQs with AI';
        }
    });
    
    return false; // Prevent form submission
}

// Toggle Auto-Generation
function toggleAutoGeneration(enabled) {
    fetch('../api/toggle-auto-generation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            enabled: enabled,
            type: 'faq'  // ✅ Specify FAQ generator
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('toggleStatus').textContent = enabled ? 'Auto-Gen ON' : 'Auto-Gen OFF';
            document.getElementById('toggleStatus').classList.toggle('active', enabled);
            
            // Show notification
            alert(data.message || 'FAQ auto-generation ' + (enabled ? 'enabled' : 'disabled'));
            location.reload();  // Reload to show updated status
        } else {
            alert('Error: ' + (data.message || 'Failed to toggle'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error toggling auto-generation');
    });
}

// Generate Single FAQ
function generateSingleFAQ() {
    console.log('generateSingleFAQ called'); // Debug log
    
    if (!confirm('Generate 1 FAQ now? This will use AI to create one FAQ.')) return;
    
    console.log('User confirmed, starting generation...'); // Debug log
    
    // Disable button to prevent double-clicks
    const buttons = document.querySelectorAll('.btn-success');
    buttons.forEach(btn => {
        if (btn.textContent.includes('Generate 1 FAQ')) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        }
    });
    
    showToast('🤖 AI is generating your FAQ... Please wait 20-30 seconds.', 'info');
    
    fetch('../api/generate-single-faq-simple.php', {
        method: 'POST',
        redirect: 'follow',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(response => {
        console.log('Response received:', response);
        console.log('Response URL:', response.url);
        if (!response.ok) {
            throw new Error('HTTP error! status: ' + response.status);
        }
        return response.text();
    })
    .then(text => {
        console.log('Raw response:', text.substring(0, 200));
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            throw new Error('Invalid JSON: ' + text.substring(0, 100));
        }
    })
    .then(data => {
        console.log('Data received:', data); // Debug log
        if (data.success) {
            alert('✅ FAQ Generated Successfully!\\n\\nQuestion: ' + data.question + '\\n\\nCategory: ' + data.category + '\\n\\nReloading page to show new FAQ...');
            setTimeout(() => location.reload(), 500);
        } else {
            console.error('Generation failed:', data.message);
            alert('❌ Error: ' + (data.message || 'Unknown error'));
            // Re-enable button
            buttons.forEach(btn => {
                if (btn.textContent.includes('Generating')) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class=\"fas fa-magic\"></i> Generate 1 FAQ Now';
                }
            });
        }
    })
    .catch(error => {
        console.error('Fetch error:', error); // Debug log
        alert('❌ Network Error: ' + error.message + '\\n\\nPlease check:\\n1. Admin login session\\n2. API file exists\\n3. Server connection');
        // Re-enable button
        buttons.forEach(btn => {
            if (btn.textContent.includes('Generating')) {
                btn.disabled = false;
                btn.innerHTML = '<i class=\"fas fa-magic\"></i> Generate 1 FAQ Now';
            }
        });
    });
}

function showToast(message, type) {
    const icons = { success:'fa-check-circle', error:'fa-times-circle', info:'fa-info-circle' };
    const toast = document.createElement('div');
    toast.className = 'fq-toast ' + (type || 'info');
    toast.innerHTML = '<i class="fas ' + (icons[type] || icons.info) + '"></i> ' + message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0'; toast.style.transform = 'translateY(12px)';
        toast.style.transition = '.3s';
        setTimeout(() => toast.remove(), 320);
    }, 3500);
}

// Scroll to Bulk Generator
function scrollToBulkGenerator() {
    document.getElementById('bulkGenerateForm').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
    // Highlight the form briefly
    const form = document.getElementById('bulkGenerateForm').closest('.seo-section');
    form.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.5)';
    setTimeout(() => {
        form.style.boxShadow = '';
    }, 2000);
}

// Test Generation
function testGeneration() {
    if (!confirm('Test FAQ generation now? This will generate 1 FAQ to test the system.')) return;
    
    showToast('Testing FAQ generation...', 'info');
    
    fetch('../api/generate-single-faq-simple.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Test successful! FAQ generated: ' + data.question, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Test failed: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Test error: ' + error.message, 'error');
    });
}
</script>

<?php include '../includes/footer.php'; ?>
