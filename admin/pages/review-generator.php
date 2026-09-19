<?php
/**
 * Review Generator - Admin Panel
 * Generate customer reviews with AI
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// ── Settings stored in DB (survive redeployment) ────────
$reviews_dir = dirname(dirname(__DIR__)) . '/data/reviews';
$_db = Database::getInstance();

// Ensure review_settings table exists
try {
    $_db->execute("
        CREATE TABLE IF NOT EXISTS review_settings (
            setting_key   VARCHAR(100) NOT NULL PRIMARY KEY,
            setting_value TEXT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
} catch (Exception $e) { /* already exists */ }

// Helper: read all review_settings into array
function loadReviewSettings(Database $db): array {
    $rows = $db->fetchAll("SELECT setting_key, setting_value FROM review_settings") ?: [];
    $s = [];
    foreach ($rows as $r) $s[$r['setting_key']] = $r['setting_value'];
    return $s;
}
// Helper: upsert one setting
function saveReviewSetting(Database $db, string $key, string $value): void {
    $db->execute(
        "INSERT INTO review_settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
        [$key, $value]
    );
}

$auto_settings = loadReviewSettings($_db);

// Generate cron_token if missing (first run)
if (empty($auto_settings['cron_token'])) {
    // Try to migrate token from old JSON file
    $json_file = $reviews_dir . '/auto-settings.json';
    $json_data = file_exists($json_file) ? json_decode(file_get_contents($json_file), true) : [];
    $token = !empty($json_data['cron_token']) ? $json_data['cron_token'] : bin2hex(random_bytes(20));
    saveReviewSetting($_db, 'cron_token',     $token);
    saveReviewSetting($_db, 'auto_enabled',   '0');
    saveReviewSetting($_db, 'daily_count',    '3');
    saveReviewSetting($_db, 'run_once_today', '1');
    saveReviewSetting($_db, 'last_run_date',  '');
    $auto_settings = loadReviewSettings($_db);
}

// Handle settings save BEFORE header output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_auto_settings'])) {
    saveReviewSetting($_db, 'auto_enabled',   isset($_POST['auto_enabled']) ? '1' : '0');
    saveReviewSetting($_db, 'daily_count',    (string)max(1, min(50, (int)($_POST['daily_count'] ?? 3))));
    saveReviewSetting($_db, 'run_once_today', isset($_POST['run_once_today']) ? '1' : '0');
    if (isset($_POST['regenerate_token'])) {
        saveReviewSetting($_db, 'cron_token', bin2hex(random_bytes(20)));
        saveReviewSetting($_db, 'today_schedule', ''); // reset random schedule
    }
    // Reset random schedule when saving so new settings take effect today
    saveReviewSetting($_db, 'today_schedule', '');
    $auto_settings = loadReviewSettings($_db);
    $_SESSION['success_message'] = 'Auto-generation settings saved!';
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$page_title = 'Auto Review Generator';
include '../includes/header.php';

// Load stats from JSON
$stats_file = $reviews_dir . '/stats.json';
$stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];

// Count reviews
$total_reviews = 0;
if (is_dir($reviews_dir)) {
    $files = glob($reviews_dir . '/*.json') ?: [];
    $total_reviews = count(array_filter($files, function($f) {
        return basename($f) !== 'stats.json' && basename($f) !== 'auto-settings.json';
    }));
}

// Count pending reviews
$pending_count = 0;
$pending_dir = $reviews_dir . '/pending';
if (is_dir($pending_dir)) {
    $pending_count = count(glob($pending_dir . '/*.json') ?: []);
}

$cron_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
            . '/admin/api/auto-review-generator.php?generate=1&token=' . urlencode($auto_settings['cron_token']);
?>

<div class="seo-page">

<!-- Hero -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-star"></i> Auto Review Generator</h1>
        <p>Auto-generate customer reviews with AI &mdash; 80% Chennai names, 85% positive ratings</p>
    </div>
    <div class="hero-actions">
        <button class="btn-action blue sm" onclick="scrollToBulkGenerator()">
            <i class="fas fa-layer-group"></i> Bulk Generate
        </button>
        <button class="btn-action gray sm" onclick="document.getElementById('autoCard').scrollIntoView({behavior:'smooth'})">
            <i class="fas fa-robot"></i> Auto Daily
        </button>
        <button class="btn-action green sm" onclick="generateSingleReview()">
            <i class="fas fa-magic"></i> Generate 1 Now
        </button>
    </div>
</div>

<!-- Flash messages -->
<?php if (!empty($_SESSION['success_message'])): ?>
<div class="rg-alert success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['error_message'])): ?>
<div class="rg-alert error"><i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?></div>
<?php endif; ?>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-star"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Reviews</h3>
            <div class="value"><?php echo number_format($total_reviews); ?></div>
            <div class="sub"><span class="stat-badge green"><i class="fas fa-check"></i> AI Generated</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-clock"></i></div>
        <div class="stat-text-wrap">
            <h3>Pending Approval</h3>
            <div class="value"><?php echo $pending_count; ?></div>
            <div class="sub">
                <?php if ($pending_count > 0): ?>
                <a href="manage-reviews.php?tab=pending"><i class="fas fa-arrow-right"></i> Review Now</a>
                <?php else: ?><span class="stat-badge blue">None pending</span><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap purple"><i class="fas fa-chart-line"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Generated</h3>
            <div class="value"><?php echo $stats['total_generated'] ?? 0; ?></div>
            <div class="sub"><span class="stat-badge purple">Lifetime count</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-thumbs-up"></i></div>
        <div class="stat-text-wrap">
            <h3>Positive Reviews</h3>
            <div class="value">85%</div>
            <div class="sub"><span class="stat-badge green">4-5 Stars</span></div>
        </div>
    </div>
</div>

<!-- Section 1: Where Are My Reviews -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num orange">1</div>
        <h2><i class="fas fa-map-marker-alt" style="color:#f59e0b;margin-right:8px;"></i>Where Are My Generated Reviews?</h2>
        <span class="sec-badge" style="margin-left:auto;background:linear-gradient(135deg,#f59e0b,#d97706);">3 Locations</span>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;margin:0 0 18px;font-size:14px;">Reviews are automatically saved and available in 3 places:</p>
        <div class="where-links">
            <a href="manage-reviews.php" class="where-link purple">
                <span class="wl-icon"><i class="fas fa-list-ul"></i></span>
                <span class="wl-label">Manage Reviews (Admin Panel)</span>
                <i class="fas fa-chevron-right wl-arrow"></i>
            </a>
            <a href="manage-reviews.php?tab=pending" class="where-link orange">
                <span class="wl-icon"><i class="fas fa-clock"></i></span>
                <span class="wl-label">Pending Approvals (<?php echo $pending_count; ?>)</span>
                <i class="fas fa-chevron-right wl-arrow"></i>
            </a>
            <a href="<?php echo SITE_URL; ?>/reviews.php" class="where-link green" target="_blank">
                <span class="wl-icon"><i class="fas fa-globe"></i></span>
                <span class="wl-label">View Reviews on Website (Public Page)</span>
                <i class="fas fa-chevron-right wl-arrow"></i>
            </a>
        </div>
        <p class="storage-note"><i class="fas fa-folder-open"></i> <strong>Storage:</strong> Reviews saved as JSON files in <code>/data/reviews/</code></p>
    </div>
</div>

<!-- Section 2: Review Generation Details -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-cogs" style="color:#3b82f6;margin-right:8px;"></i>Review Generation Details</h2>
        <span class="sec-badge" style="margin-left:auto;">AI Powered</span>
    </div>
    <div class="seo-section-body">
        <div class="info-grid">
            <div class="info-box ib-purple">
                <div class="ib-icon"><i class="fas fa-user-friends"></i></div>
                <h4>Name Distribution</h4>
                <ul>
                    <li><strong>80%</strong> Chennai names (Rajesh, Srinivas, Madhavi, etc.)</li>
                    <li><strong>20%</strong> Other states (Amit, Priya, Vikram, etc.)</li>
                    <li>45+ unique Chennai names</li>
                    <li>14+ unique other states names</li>
                </ul>
            </div>
            <div class="info-box ib-gold">
                <div class="ib-icon"><i class="fas fa-star"></i></div>
                <h4>Rating Distribution</h4>
                <ul>
                    <li><strong>85%</strong> Positive (4-5 stars)</li>
                    <li><strong>15%</strong> Negative (1-2 stars)</li>
                    <li>AI generates genuine-sounding reviews</li>
                    <li>Different content for each rating</li>
                </ul>
            </div>
            <div class="info-box ib-blue">
                <div class="ib-icon"><i class="fas fa-th-list"></i></div>
                <h4>10 Categories</h4>
                <ul>
                    <li>Installation Service</li>
                    <li>Product Quality</li>
                    <li>Warranty Coverage</li>
                    <li>Customer Service</li>
                    <li>Technician Professionalism</li>
                    <li>And 5 more...</li>
                </ul>
            </div>
            <div class="info-box ib-green">
                <div class="ib-icon"><i class="fas fa-calendar-alt"></i></div>
                <h4>Random Dates</h4>
                <ul>
                    <li><strong>2010-2025</strong> (15 years)</li>
                    <li>Makes reviews look established</li>
                    <li>Random locations in Chennai</li>
                    <li>Verified badge on all</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Section 3: Bulk Review Generation -->
<div class="seo-section" id="bulkGeneratorCard">
    <div class="seo-section-head">
        <div class="sec-num purple">3</div>
        <h2><i class="fas fa-layer-group" style="color:#667eea;margin-right:8px;"></i>Bulk Review Generation</h2>
        <span class="sec-badge" style="margin-left:auto;"><i class="fas fa-robot" style="margin-right:5px;"></i>AI Powered</span>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;font-size:14px;margin:0 0 24px;">Generate multiple reviews at once with AI — fast &amp; realistic</p>
        <form id="bulkGenerateForm">
            <div class="count-selector">
                <label class="count-label">
                    <i class="fas fa-hashtag"></i> NUMBER OF REVIEWS
                    <span class="count-tip">You can change this number</span>
                </label>
                <input type="number" id="review_count" name="review_count" value="10" min="1" max="50" class="count-input">
                <p class="count-hint"><i class="fas fa-info-circle"></i> Generate 1–50 reviews at once (approx. 30 seconds per review)</p>
            </div>

            <div class="gen-info-banner">
                <div class="gib-item">
                    <div class="gib-dot clock"><i class="fas fa-clock"></i></div>
                    <span>Estimated time: <strong id="estimatedTime">5 minutes</strong></span>
                </div>
                <div class="gib-item">
                    <div class="gib-dot check"><i class="fas fa-check-circle"></i></div>
                    <span>All reviews will have unique names and content</span>
                </div>
                <div class="gib-item">
                    <div class="gib-dot star"><i class="fas fa-star"></i></div>
                    <span>85% positive (4-5 stars), 15% negative (1-2 stars)</span>
                </div>
            </div>

            <div class="bulk-action">
                <button type="submit" class="btn-generate">
                    <i class="fas fa-magic"></i> Generate Reviews with AI
                </button>
            </div>
        </form>

        <!-- Progress -->
        <div id="bulkProgress" style="display:none;margin-top:32px;">
            <div class="progress-header">
                <span><i class="fas fa-spinner fa-spin"></i> Generating Reviews…</span>
                <span id="progressCount" class="progress-count">0 / 10</span>
            </div>
            <div class="progress">
                <div id="progressBar" class="progress-bar" style="width:0%"></div>
            </div>
            <p class="progress-note">Please wait — this may take several minutes</p>
        </div>
    </div>
</div>

<!-- Section 4: Daily Auto-Generation -->
<div class="seo-section" id="autoCard">
    <div class="seo-section-head">
        <div class="sec-num green">4</div>
        <h2><i class="fas fa-robot" style="color:#10b981;margin-right:8px;"></i>Daily Auto-Generation</h2>
        <span class="auto-status-pill <?php echo $auto_settings['auto_enabled'] ? 'on' : 'off'; ?>" style="margin-left:auto;">
            <i class="fas fa-circle" style="font-size:9px;"></i>
            <?php echo $auto_settings['auto_enabled'] ? 'AUTO ON' : 'AUTO OFF'; ?>
        </span>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;font-size:14px;margin:0 0 22px;">Set up automatic daily reviews via Hostinger Cron Jobs</p>

        <!-- How It Works -->
        <div class="how-it-works">
            <h3><i class="fas fa-info-circle"></i> How to Set Up Daily Reviews</h3>
            <div class="steps-grid">
                <div class="step"><span class="step-num">1</span><div><strong>Configure below</strong> — set how many reviews per day &amp; enable auto mode</div></div>
                <div class="step"><span class="step-num">2</span><div><strong>Copy the Cron URL</strong> — shown below after saving settings</div></div>
                <div class="step"><span class="step-num">3</span><div><strong>Add to Hostinger Cron</strong> — hPanel → Advanced → Cron Jobs → paste URL</div></div>
                <div class="step"><span class="step-num">4</span><div><strong>Set schedule</strong> — e.g. every day at 9:00 AM (0 9 * * *)</div></div>
            </div>
        </div>

        <!-- Settings Form -->
        <form method="POST" class="auto-form">
            <div class="auto-form-row">
                <div class="auto-form-group">
                    <label><i class="fas fa-toggle-on"></i> Enable Auto-Generation</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="auto_enabled" <?php echo $auto_settings['auto_enabled'] ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <small>Turn ON to allow cron URL to generate reviews</small>
                </div>
                <div class="auto-form-group">
                    <label><i class="fas fa-hashtag"></i> Reviews Per Day</label>
                    <input type="number" name="daily_count" value="<?php echo (int)($auto_settings['daily_count'] ?? 3); ?>" min="1" max="20" class="auto-input">
                    <small>1–20 reviews generated each day (recommended: 3–5)</small>
                </div>
                <div class="auto-form-group">
                    <label><i class="fas fa-calendar-check"></i> Run Once Per Day</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="run_once_today" <?php echo ($auto_settings['run_once_today'] ?? true) ? 'checked' : ''; ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <small>Skip if already ran today (prevents duplicates)</small>
                </div>
            </div>
            <div class="auto-form-actions">
                <button type="submit" name="save_auto_settings" class="btn-action green sm">
                    <i class="fas fa-save"></i> Save Settings
                </button>
                <button type="submit" name="save_auto_settings" value="1" onclick="document.querySelector('[name=regenerate_token]').disabled=false" class="btn-action gray sm">
                    <i class="fas fa-sync"></i> Save &amp; Regenerate Token
                </button>
                <input type="hidden" name="regenerate_token" disabled>
            </div>
        </form>

        <!-- Cron URL Box -->
        <div class="cron-url-box">
            <div class="cron-url-header">
                <span><i class="fas fa-link"></i> Your Cron URL (keep this secret!)</span>
                <button type="button" onclick="copyCronUrl()" class="btn-copy"><i class="fas fa-copy"></i> Copy</button>
            </div>
            <div class="cron-url-text" id="cronUrl"><?php echo htmlspecialchars($cron_url); ?></div>
            <div class="cron-schedule-examples">
                <strong><i class="fas fa-clock"></i> Paste this URL in Hostinger → hPanel → Advanced → Cron Jobs</strong>
                <div class="schedule-chips">
                    <span class="chip">Every day 9 AM → <code>0 9 * * *</code></span>
                    <span class="chip">Every day 8 PM → <code>0 20 * * *</code></span>
                    <span class="chip">Twice daily → <code>0 9,20 * * *</code></span>
                </div>
            </div>
        </div>

        <!-- Last run + Test button -->
        <div class="auto-footer">
            <div class="last-run-info">
                <i class="fas fa-history"></i>
                Last auto-run:
                <strong><?php echo !empty($auto_settings['last_run_date']) ? $auto_settings['last_run_date'] : 'Never'; ?></strong>
            </div>
            <button type="button" class="btn-action green sm" onclick="testAutoGenerate()">
                <i class="fas fa-play-circle"></i> Test: Run Now (1 Review)
            </button>
        </div>
        <div id="autoRunResult" style="display:none;" class="auto-run-result"></div>
    </div>
</div>

<!-- Bottom Navigation -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="manage-reviews.php" class="btn-action purple">
        <i class="fas fa-list-ul"></i> Manage Reviews
    </a>
</div>

</div>

<style>
/* ═══ Auto Review Generator — Complete SEO System Theme ═ */
@keyframes shimmer   { 0%{background-position:-400px 0} 100%{background-position:400px 0} }
@keyframes pulse-ring{ 0%{box-shadow:0 0 0 0 rgba(102,126,234,.4)} 70%{box-shadow:0 0 0 10px rgba(102,126,234,0)} 100%{box-shadow:0 0 0 0 rgba(102,126,234,0)} }

/* ── Page wrapper ─────────────────────────────── */
.seo-page { padding: 0; }

/* ── Hero ─────────────────────────────────────── */
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
.stat-icon-wrap.purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f97316,#dc2626); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-card h3 { font-size: 10px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 5px !important; }
.stat-card .value { font-size: 2rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 0 6px !important; }
.stat-card .sub { font-size: 12px; color: #64748b; }
.stat-card .sub a { color: #667eea; font-weight: 600; text-decoration: none; }
.stat-card .sub a:hover { text-decoration: underline; }
.stat-badge { display: inline-block; padding: 3px 9px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.stat-badge.green  { background: #d1fae5; color: #065f46; }
.stat-badge.blue   { background: #dbeafe; color: #1e40af; }
.stat-badge.purple { background: #ede9fe; color: #5b21b6; }

/* ── Section card ─────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.sec-num.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.seo-section-head h2 { font-size: 20px; font-weight: 700; color: #1e293b; margin: 0; }
.sec-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 5px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.seo-section-body { padding: 28px 32px; }

/* ── Action buttons ───────────────────────────── */
.action-row { display: flex; gap: 16px; flex-wrap: wrap; }
.btn-action { display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; border: none; border-radius: 12px; font-size: 15px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .25s; }
.btn-action:hover { transform: translateY(-3px); text-decoration: none; }
.btn-action.green  { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 6px 20px rgba(40,167,69,.35); }
.btn-action.green:hover  { box-shadow: 0 10px 30px rgba(40,167,69,.45); }
.btn-action.purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 6px 20px rgba(102,126,234,.35); }
.btn-action.purple:hover { box-shadow: 0 10px 30px rgba(102,126,234,.45); }
.btn-action.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; box-shadow: 0 6px 20px rgba(59,130,246,.35); }
.btn-action.blue:hover   { box-shadow: 0 10px 30px rgba(59,130,246,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; box-shadow: none; transform: none; }
.btn-action.sm { padding: 9px 18px; font-size: 13px; gap: 7px; }

/* ── Alert messages ───────────────────────────── */
.rg-alert { padding: 14px 20px; border-radius: 12px; margin-bottom: 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; }
.rg-alert.success { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
.rg-alert.error   { background: #fee2e2; color: #991b1b; border: 1.5px solid #fca5a5; }

/* ── Where links ──────────────────────────────── */
.where-links { display: grid; gap: 12px; margin-bottom: 20px; }
.where-link { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-radius: 14px; text-decoration: none; font-weight: 600; font-size: 14px; transition: transform .2s, box-shadow .2s; color: white; }
.where-link:hover { transform: translateX(4px); box-shadow: 0 8px 24px rgba(0,0,0,.15); }
.where-link.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.where-link.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.where-link.green  { background: linear-gradient(135deg,#10b981,#059669); }
.wl-icon  { width: 36px; height: 36px; background: rgba(255,255,255,.2); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; flex-shrink: 0; }
.wl-label { flex: 1; }
.wl-arrow { font-size: 12px; opacity: .7; transition: transform .2s; }
.where-link:hover .wl-arrow { transform: translateX(4px); }
.storage-note { margin: 0; padding: 14px 0 0; color: #64748b; font-size: 13px; border-top: 1px solid #f1f5f9; }
.storage-note code { background: #f1f5f9; padding: 2px 6px; border-radius: 4px; font-size: 12px; }

/* ── Info Grid ────────────────────────────────── */
.info-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 18px; }
.info-box { padding: 22px; border-radius: 16px; transition: transform .25s, box-shadow .25s; position: relative; overflow: hidden; }
.info-box::before { content: ''; position: absolute; top: -20px; right: -20px; width: 80px; height: 80px; border-radius: 50%; background: rgba(255,255,255,.18); }
.info-box:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,.14); }
.ib-purple { background: linear-gradient(135deg,#8b5cf6,#7c3aed); color: white; }
.ib-gold   { background: linear-gradient(135deg,#f59e0b,#d97706); color: white; }
.ib-blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; }
.ib-green  { background: linear-gradient(135deg,#10b981,#059669); color: white; }
.ib-icon { width: 44px; height: 44px; background: rgba(255,255,255,.22); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; margin-bottom: 14px; }
.info-box h4 { margin: 0 0 14px; font-size: 15px; font-weight: 700; color: white; }
.info-box ul { list-style: none; padding: 0; margin: 0; }
.info-box li { padding: 7px 0; font-size: 13px; border-bottom: 1px solid rgba(255,255,255,.18); line-height: 1.5; color: rgba(255,255,255,.9); }
.info-box li:last-child { border-bottom: none; }
.info-box li strong { color: white; }

/* ── Bulk form ────────────────────────────────── */
.count-selector { margin-bottom: 24px; }
.count-label { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-bottom: 12px; font-weight: 700; color: #1e293b; font-size: 13px; text-transform: uppercase; letter-spacing: .6px; }
.count-tip { background: #ede9fe; color: #7c3aed; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: none; letter-spacing: 0; }
.count-input { width: 100%; padding: 16px 20px; text-align: center; font-size: 36px; font-weight: 800; color: #3730a3; border: 3px solid #e0e7ff; border-radius: 16px; background: linear-gradient(135deg,#f8faff,#eef2ff); transition: all .2s; box-sizing: border-box; outline: none; }
.count-input:focus { border-color: #667eea; box-shadow: 0 0 0 4px rgba(102,126,234,.15); }
.count-hint { margin: 10px 0 0; color: #64748b; font-size: 13px; }
.count-hint i { color: #667eea; margin-right: 4px; }
.gen-info-banner { background: linear-gradient(135deg,#f0f9ff,#e0f2fe); border: 2px solid #7dd3fc; border-radius: 16px; padding: 6px 20px; margin-bottom: 24px; }
.gib-item { display: flex; align-items: center; gap: 14px; padding: 14px 0; font-size: 14px; color: #0c4a6e; }
.gib-item:not(:last-child) { border-bottom: 1px solid rgba(125,211,252,.4); }
.gib-dot { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; color: white; flex-shrink: 0; }
.gib-dot.clock { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.gib-dot.check { background: linear-gradient(135deg,#10b981,#059669); }
.gib-dot.star  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.bulk-action { text-align: center; }
.btn-generate { padding: 18px 56px; font-size: 18px; font-weight: 700; background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; border-radius: 16px; cursor: pointer; transition: all .3s; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 8px 28px rgba(102,126,234,.45); animation: pulse-ring 2.5s infinite; }
.btn-generate:hover { transform: translateY(-3px); box-shadow: 0 14px 36px rgba(102,126,234,.55); animation: none; }
.progress-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; font-size: 15px; font-weight: 600; color: #1e293b; }
.progress-count { color: #667eea; font-size: 14px; }
.progress { background: #e2e8f0; height: 16px; border-radius: 12px; overflow: hidden; }
.progress-bar { background: linear-gradient(90deg,#667eea,#f093fb); height: 100%; transition: width .5s ease; border-radius: 12px; background-size: 200% 100%; animation: shimmer 2s linear infinite; }
.progress-note { text-align: center; margin: 10px 0 0; color: #64748b; font-size: 13px; }

/* ── Auto-generation form ─────────────────────── */
.auto-form { margin-bottom: 24px; }
.auto-form-row { display: grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr)); gap: 20px; margin-bottom: 20px; }
.auto-form-group { display: flex; flex-direction: column; gap: 8px; }
.auto-form-group label:first-child { font-size: 13px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px; }
.auto-form-group label:first-child i { color: #667eea; }
.auto-form-group small { color: #64748b; font-size: 12px; }
.auto-input { padding: 10px 14px; border: 2px solid #e0e7ff; border-radius: 10px; font-size: 18px; font-weight: 700; text-align: center; color: #3730a3; width: 100%; box-sizing: border-box; outline: none; transition: border-color .2s; }
.auto-input:focus { border-color: #667eea; box-shadow: 0 0 0 3px rgba(102,126,234,.15); }
.auto-form-actions { display: flex; gap: 12px; flex-wrap: wrap; }

/* ── Toggle switch ────────────────────────────── */
.toggle-switch { display: flex; align-items: center; cursor: pointer; width: fit-content; }
.toggle-switch input { display: none; }
.toggle-slider { width: 52px; height: 28px; background: #e2e8f0; border-radius: 14px; position: relative; transition: background .2s; }
.toggle-slider::after { content: ''; width: 22px; height: 22px; background: white; border-radius: 50%; position: absolute; top: 3px; left: 3px; transition: left .2s; box-shadow: 0 2px 6px rgba(0,0,0,.18); }
.toggle-switch input:checked + .toggle-slider { background: linear-gradient(135deg,#667eea,#764ba2); }
.toggle-switch input:checked + .toggle-slider::after { left: 27px; }

/* ── How-it-works ─────────────────────────────── */
.how-it-works { background: linear-gradient(135deg,#f8faff,#eef2ff); border-radius: 14px; padding: 20px 24px; margin-bottom: 24px; border: 2px solid #e0e7ff; }
.how-it-works h3 { font-size: 15px; font-weight: 700; color: #3730a3; margin: 0 0 16px; }
.how-it-works h3 i { color: #667eea; margin-right: 6px; }
.steps-grid { display: grid; grid-template-columns: repeat(auto-fit,minmax(200px,1fr)); gap: 14px; }
.step { display: flex; align-items: flex-start; gap: 12px; background: white; padding: 14px 16px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); }
.step-num { width: 28px; height: 28px; background: linear-gradient(135deg,#667eea,#764ba2); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 800; flex-shrink: 0; }
.step div { font-size: 13px; color: #475569; line-height: 1.5; }
.step strong { color: #1e293b; display: block; margin-bottom: 2px; }

/* ── Cron URL Box ─────────────────────────────── */
.cron-url-box { background: #0f172a; border-radius: 16px; overflow: hidden; margin-bottom: 24px; }
.cron-url-header { padding: 14px 20px; background: #1e293b; display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 600; color: #94a3b8; }
.cron-url-text { padding: 16px 20px; font-family: monospace; font-size: 13px; color: #7dd3fc; word-break: break-all; line-height: 1.6; }
.btn-copy { background: linear-gradient(135deg,#667eea,#764ba2); color: white; border: none; padding: 7px 16px; border-radius: 8px; cursor: pointer; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 6px; transition: all .2s; }
.btn-copy:hover { transform: scale(1.05); }
.cron-schedule-examples { padding: 14px 20px; border-top: 1px solid #1e293b; font-size: 13px; color: #94a3b8; }
.cron-schedule-examples strong { display: block; margin-bottom: 10px; color: #cbd5e1; }
.schedule-chips { display: flex; gap: 10px; flex-wrap: wrap; }
.chip { background: #1e293b; padding: 6px 14px; border-radius: 8px; font-size: 12px; color: #94a3b8; border: 1px solid #334155; }
.chip code { color: #7dd3fc; font-size: 11px; }

/* ── Auto footer ──────────────────────────────── */
.auto-footer { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
.last-run-info { font-size: 13px; color: #64748b; display: flex; align-items: center; gap: 8px; }
.last-run-info i { color: #667eea; }
.last-run-info strong { color: #1e293b; }
.auto-run-result { margin-top: 16px; padding: 16px 20px; border-radius: 12px; font-size: 14px; font-weight: 600; }
.auto-run-result.ok   { background: #d1fae5; color: #065f46; border: 1.5px solid #6ee7b7; }
.auto-run-result.fail { background: #fee2e2; color: #991b1b; border: 1.5px solid #fca5a5; }
.auto-status-pill { padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
.auto-status-pill.on  { background: #d1fae5; color: #065f46; }
.auto-status-pill.off { background: #fee2e2; color: #991b1b; }

/* ── Toast ────────────────────────────────────── */
.rg-toast { position: fixed; top: 24px; right: 24px; padding: 14px 22px; border-radius: 14px; font-size: 14px; font-weight: 600; box-shadow: 0 8px 28px rgba(0,0,0,.18); z-index: 10000; display: flex; align-items: center; gap: 10px; min-width: 260px; max-width: 380px; }
.rg-toast.success { background: linear-gradient(135deg,#10b981,#059669); color: white; }
.rg-toast.error   { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; }
.rg-toast.info    { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; }

@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:640px) { .count-input{font-size:28px;} .auto-form-row{grid-template-columns:1fr;} }
@media(max-width:560px) { .seo-hero{flex-direction:column;} .stats-grid{grid-template-columns:1fr;} }
</style>

<script>
// Update time estimate
document.getElementById('review_count').addEventListener('input', function() {
    const count = parseInt(this.value) || 10;
    const minutes = Math.ceil(count * 0.5);
    document.getElementById('estimatedTime').textContent = minutes + ' minute' + (minutes > 1 ? 's' : '');
});

// Scroll to bulk generator
function scrollToBulkGenerator() {
    const card = document.getElementById('bulkGeneratorCard');
    card.scrollIntoView({ behavior: 'smooth', block: 'start' });
    card.style.boxShadow = '0 0 0 4px #3B82F6';
    setTimeout(() => {
        card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
    }, 2000);
}

// Generate Single Review
function generateSingleReview() {
    if (!confirm('Generate 1 review now? This takes about 10-15 seconds.')) return;
    
    showToast('Generating review with AI...', 'info');
    
    fetch('../api/generate-single-review-simple.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast(`Review generated! ${data.rating}⭐ from ${data.name}`, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error generating review', 'error');
    });
}

// Bulk Generate Reviews
document.getElementById('bulkGenerateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const count = formData.get('review_count');
    
    if (!confirm(`Generate ${count} reviews with AI? This takes about ${Math.ceil(count * 0.5)} minutes.`)) return;
    
    document.getElementById('bulkProgress').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressCount').textContent = `0 / ${count}`;
    
    fetch('../api/generate-bulk-reviews-simple.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('progressCount').textContent = `${data.generated} / ${count}`;
            showToast(`Generated ${data.generated} reviews successfully!`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showToast('Error generating reviews', 'error');
        document.getElementById('bulkProgress').style.display = 'none';
    });
});

function copyCronUrl() {
    const url = document.getElementById('cronUrl').textContent.trim();
    navigator.clipboard.writeText(url).then(() => {
        showToast('Cron URL copied to clipboard!', 'success');
    }).catch(() => {
        prompt('Copy this URL:', url);
    });
}

function testAutoGenerate() {
    const btn = document.querySelector('.btn-test-now');
    const result = document.getElementById('autoRunResult');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating 1 review…';
    result.style.display = 'none';

    fetch('../api/auto-review-generator.php?generate=1&count=1', { method: 'GET' })
        .then(r => r.json())
        .then(data => {
            result.style.display = 'block';
            if (data.success) {
                const r0 = data.reviews[0] || {};
                result.className = 'auto-run-result ok';
                result.innerHTML = '<i class="fas fa-check-circle"></i> Generated 1 review! '
                    + (r0.name ? r0.name + ' — ' + r0.rating + '★ — ' + r0.category : '')
                    + ' <a href="manage-reviews.php" style="margin-left:10px;color:#065f46;font-weight:700;">View Reviews →</a>';
                setTimeout(() => location.reload(), 3000);
            } else {
                result.className = 'auto-run-result fail';
                result.innerHTML = '<i class="fas fa-times-circle"></i> ' + (data.message || 'Error generating review');
            }
        })
        .catch(() => {
            result.style.display = 'block';
            result.className = 'auto-run-result fail';
            result.innerHTML = '<i class="fas fa-times-circle"></i> Request failed — check server logs';
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-play-circle"></i> Test: Run Now (1 Review)';
        });
}

function showToast(message, type) {
    const icons = { success: 'fa-check-circle', error: 'fa-times-circle', info: 'fa-info-circle' };
    const toast = document.createElement('div');
    toast.className = 'rg-toast ' + (type || 'info');
    toast.innerHTML = '<i class="fas ' + (icons[type] || icons.info) + '"></i> ' + message;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0'; toast.style.transform = 'translateY(-12px)';
        toast.style.transition = '.3s';
        setTimeout(() => toast.remove(), 320);
    }, 3200);
}
</script>

<?php include '../includes/footer.php'; ?>
