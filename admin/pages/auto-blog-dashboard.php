<?php
/**
 * Automated Blog Generation Dashboard
 * Manage daily blog creation
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Handle settings update BEFORE any output (header.php sends HTML)
$db = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    try {
        $db->execute("UPDATE blog_settings SET setting_value = ? WHERE setting_key = 'daily_blog_count'", [$_POST['daily_blog_count']]);
        $db->execute("UPDATE blog_settings SET setting_value = ? WHERE setting_key = 'enable_auto_generation'", [$_POST['enable_auto_generation'] ?? '0']);
        $_SESSION['success_message'] = "Settings updated successfully!";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
    }
}

$page_title = 'Automated Blog Generator';
include '../includes/header.php';

try {
    // Get statistics
    $stats = $db->fetchOne("SELECT * FROM v_blog_statistics");
    
    // Get settings
    $settings_raw = $db->fetchAll("SELECT setting_key, setting_value FROM blog_settings");
    $settings = [];
    foreach ($settings_raw as $s) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
    
    // Get recent blogs
    $recent_blogs = $db->fetchAll("SELECT * FROM blog_posts ORDER BY created_at DESC LIMIT 10");
    
    // Get generation schedule
    $schedule = $db->fetchAll("SELECT * FROM blog_generation_schedule ORDER BY schedule_date DESC LIMIT 7");
    
    // Get topic statistics
    $topic_stats = $db->fetchAll("
        SELECT category, COUNT(*) as total, SUM(used_count) as times_used 
        FROM blog_topics 
        WHERE is_active = 1 
        GROUP BY category
    ");
    
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

$page_title = 'Auto Blog Generator';
?>

<style>
/* ═══ Auto Blog Dashboard — Complete SEO System Theme ═══ */
@keyframes fadeInUp { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
@keyframes float    { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-8px)} }
@keyframes spin     { to{transform:rotate(360deg)} }

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

/* ── Section card ───────────────────────────────────── */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 22px 32px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
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
.btn-action.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); color: white; box-shadow: 0 6px 20px rgba(59,130,246,.35); }
.btn-action.blue:hover   { box-shadow: 0 10px 30px rgba(59,130,246,.45); }
.btn-action.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); color: white; box-shadow: 0 6px 20px rgba(245,158,11,.35); }
.btn-action.amber:hover  { box-shadow: 0 10px 30px rgba(245,158,11,.45); }
.btn-action.gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.btn-action.gray:hover   { background: #e2e8f0; box-shadow: none; transform: none; }
.btn-action.sm { padding: 10px 20px; font-size: 14px; }

/* ── Alerts ─────────────────────────────────────────── */
.alert { padding:16px 20px; border-radius:12px; margin-bottom:20px; display:flex; align-items:flex-start; gap:12px; font-size:14px; line-height:1.6; }
.alert i { font-size:16px; margin-top:2px; flex-shrink:0; }
.alert-success { background:linear-gradient(135deg,#ecfdf5,#d1fae5); border:2px solid #6ee7b7; color:#065f46; }
.alert-error   { background:linear-gradient(135deg,#fef2f2,#fee2e2); border:2px solid #fca5a5; color:#991b1b; }
.alert-info    { background:linear-gradient(135deg,#eff6ff,#dbeafe); border:2px solid #93c5fd; color:#1e40af; }
.alert-warning { background:linear-gradient(135deg,#fffbeb,#fef3c7); border:2px solid #fcd34d; color:#92400e; }
.alert code    { background:rgba(0,0,0,.08); padding:2px 6px; border-radius:4px; font-size:12px; word-break:break-all; }

/* ── Form ───────────────────────────────────────────── */
.form-row  { display:flex; gap:20px; flex-wrap:wrap; margin-bottom:24px; }
.form-group { flex:1; min-width:200px; }
.form-group label { display:block; margin-bottom:8px; font-weight:700; color:#1e293b; font-size:12px; text-transform:uppercase; letter-spacing:.5px; }
.form-control { width:100%; padding:12px 16px; border:2px solid #e2e8f0; border-radius:10px; font-size:14px; transition:all .2s; background:white; box-sizing:border-box; }
.form-control:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.15); }

/* ── Tables ─────────────────────────────────────────── */
.data-table { width:100%; border-collapse:collapse; }
.data-table th { background:linear-gradient(135deg,#f8fafc,#eef2ff); padding:13px 32px; text-align:left; font-weight:700; font-size:11px; color:#475569; text-transform:uppercase; letter-spacing:.6px; border-bottom:2px solid #e2e8f0; }
.data-table td { padding:15px 32px; border-bottom:1px solid #f1f5f9; font-size:14px; vertical-align:middle; }
.data-table tbody tr:last-child td { border-bottom:none; }
.data-table tbody tr:hover { background:#f8faff; }
.data-table td strong { color:#1e293b; font-weight:600; }

/* ── Badges ─────────────────────────────────────────── */
.badge { padding:4px 12px; border-radius:20px; font-size:11px; font-weight:700; white-space:nowrap; display:inline-flex; align-items:center; gap:4px; }
.badge-success   { background:#d1fae5; color:#065f46; }
.badge-warning   { background:#fef3c7; color:#92400e; }
.badge-danger    { background:#fee2e2; color:#991b1b; }
.badge-secondary { background:#f1f5f9; color:#64748b; }

/* ── Empty state ─────────────────────────────────────── */
.empty-state { text-align:center; padding:60px 30px; }
.empty-state i { font-size:64px; color:#c4b5fd; margin-bottom:20px; display:block; }
.empty-state h3 { color:#64748b; font-size:20px; margin-bottom:8px; }
.empty-state p  { color:#94a3b8; margin-bottom:24px; font-size:15px; }

#generationStatus { margin-top:20px; }
.fa-spin { animation:spin 1s linear infinite; }

@media(max-width:900px){ .stats-grid{grid-template-columns:repeat(2,1fr);} }
@media(max-width:560px) { .seo-hero{flex-direction:column;} .stats-grid{grid-template-columns:1fr;} }
</style>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-robot"></i> Auto Blog Generator</h1>
        <p>AI-powered daily blog automation for GCM Netting Solutions</p>
    </div>
    <div class="hero-actions">
        <a href="manage-blogs.php" class="btn-action purple sm">
            <i class="fas fa-list"></i> Manage Blogs
        </a>
        <a href="<?php echo SITE_URL; ?>/blogs.php" class="btn-action green sm" target="_blank">
            <i class="fas fa-external-link-alt"></i> View on Website
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i><span><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></span></div>
<?php endif; ?>
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></span></div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i><span><strong>Database Error:</strong> <?php echo htmlspecialchars($error_message); ?></span></div>
    <div class="alert alert-info"><i class="fas fa-info-circle"></i><span>Run setup: <code>mysql -u u271370596_gcm -p u271370596_gcm &lt; sql/automated-blog-system.sql</code></span></div>
<?php else: ?>

<!-- Statistics -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-blog"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Blogs</h3>
            <div class="value"><?php echo number_format($stats['total_blogs'] ?? 0); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-check-circle"></i></div>
        <div class="stat-text-wrap">
            <h3>Published</h3>
            <div class="value"><?php echo number_format($stats['published_blogs'] ?? 0); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-eye"></i></div>
        <div class="stat-text-wrap">
            <h3>Total Views</h3>
            <div class="value"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-chart-line"></i></div>
        <div class="stat-text-wrap">
            <h3>Avg SEO Score</h3>
            <div class="value"><?php echo number_format($stats['avg_seo_score'] ?? 0); ?></div>
        </div>
    </div>
</div>

<!-- Section 1: Generate Blogs -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-magic" style="color:#667eea;margin-right:8px;"></i>Generate Blogs Now</h2>
    </div>
    <div class="seo-section-body">
        <p style="color:#64748b;margin:0 0 24px;font-size:15px;line-height:1.6;">
            Generate unique, SEO-optimized blogs instantly. Select how many blogs to create:
        </p>
        <div class="action-row">
            <button onclick="generateBlogs(1)" class="btn-action green">
                <i class="fas fa-plus"></i> Generate 1 Blog
            </button>
            <button onclick="generateBlogs(2)" class="btn-action purple">
                <i class="fas fa-plus"></i> Generate 2 Blogs
            </button>
            <button onclick="generateBlogs(3)" class="btn-action blue">
                <i class="fas fa-plus"></i> Generate 3 Blogs
            </button>
            <button onclick="generateBlogs(5)" class="btn-action amber">
                <i class="fas fa-plus"></i> Generate 5 Blogs
            </button>
        </div>
        <div id="generationStatus"></div>
    </div>
</div>

<!-- Section 2: Automation Settings -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-cog" style="color:#3b82f6;margin-right:8px;"></i>Automation Settings</h2>
    </div>
    <div class="seo-section-body">
        <form method="POST">
            <input type="hidden" name="update_settings" value="1">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-hashtag"></i> Daily Blog Count (1-5)</label>
                    <select name="daily_blog_count" class="form-control">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?php echo $i; ?>" <?php echo ($settings['daily_blog_count'] ?? 3) == $i ? 'selected' : ''; ?>>
                            <?php echo $i; ?> Blog<?php echo $i > 1 ? 's' : ''; ?> per day
                        </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-power-off"></i> Auto-Generation</label>
                    <select name="enable_auto_generation" class="form-control">
                        <option value="1" <?php echo ($settings['enable_auto_generation'] ?? 1) == 1 ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo ($settings['enable_auto_generation'] ?? 1) == 0 ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                </div>
            </div>
            <div class="alert alert-info" style="margin-bottom:24px;">
                <i class="fas fa-info-circle"></i>
                <span><strong>Auto-Generation:</strong> When enabled, blogs are generated automatically every day at 9:00 AM. Set up a cron job on Hostinger: <code>0 9 * * * curl https://gcmsafetynets.in/admin/api/auto-blog-generator.php?generate=1</code></span>
            </div>
            <div class="action-row">
                <button type="submit" class="btn-action green">
                    <i class="fas fa-save"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Section 3: Topic Categories -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">3</div>
        <h2><i class="fas fa-folder-open" style="color:#f59e0b;margin-right:8px;"></i>Topic Categories</h2>
        <span style="margin-left:auto;background:linear-gradient(135deg,#667eea,#764ba2);color:white;padding:5px 14px;border-radius:20px;font-size:13px;font-weight:700;"><?php echo count($topic_stats); ?> Categories</span>
    </div>
    <div class="seo-section-body" style="padding:0;">
        <?php if (!empty($topic_stats)): ?>
        <table class="data-table">
            <thead><tr><th>Category</th><th>Available Topics</th><th>Times Used</th></tr></thead>
            <tbody>
                <?php foreach ($topic_stats as $topic): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($topic['category']); ?></strong></td>
                    <td><?php echo number_format($topic['total']); ?></td>
                    <td><?php echo number_format($topic['times_used']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-folder-open"></i>
            <h3>No Categories Yet</h3>
            <p>Topic categories will appear here after setup</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Section 4: Recent Blogs -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num green">4</div>
        <h2><i class="fas fa-newspaper" style="color:#10b981;margin-right:8px;"></i>Recent Blogs</h2>
        <a href="manage-blogs.php" class="btn-action purple sm" style="margin-left:auto;">
            <i class="fas fa-list"></i> View All
        </a>
    </div>
    <div class="seo-section-body" style="padding:0;">
        <?php if (empty($recent_blogs)): ?>
            <div class="empty-state">
                <i class="fas fa-blog"></i>
                <h3>No Blogs Yet</h3>
                <p>Generate your first blog to get started</p>
                <button onclick="generateBlogs(1)" class="btn-action green" style="margin-top:8px;">
                    <i class="fas fa-magic"></i> Generate Now
                </button>
            </div>
        <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr><th>Title</th><th>Category</th><th>Words</th><th>SEO Score</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_blogs as $blog): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($blog['title']); ?></strong></td>
                        <td><?php echo htmlspecialchars($blog['category'] ?? 'General'); ?></td>
                        <td><?php echo number_format(str_word_count(strip_tags($blog['content'] ?? ''))); ?></td>
                        <td>
                            <?php $seo = $blog['seo_score'] ?? null; ?>
                            <?php if ($seo !== null): ?>
                            <span class="badge <?php echo $seo >= 80 ? 'badge-success' : ($seo >= 60 ? 'badge-warning' : 'badge-danger'); ?>"><?php echo $seo; ?>/100</span>
                            <?php else: ?><span class="badge badge-secondary">N/A</span><?php endif; ?>
                        </td>
                        <td>
                            <?php $pub = ($blog['status'] ?? $blog['is_published'] ?? 'draft'); $isPub = ($pub === 'published' || $pub === 1 || $pub === '1'); ?>
                            <span class="badge <?php echo $isPub ? 'badge-success' : 'badge-secondary'; ?>"><?php echo $isPub ? 'Published' : 'Draft'; ?></span>
                        </td>
                        <td style="color:#64748b;"><?php echo date('d M Y', strtotime($blog['created_at'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Section 5: Generation History -->
<?php if (!empty($schedule)): ?>
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">5</div>
        <h2><i class="fas fa-history" style="color:#3b82f6;margin-right:8px;"></i>Generation History <span style="font-size:14px;font-weight:500;color:#64748b;margin-left:6px;">(Last 7 Days)</span></h2>
    </div>
    <div class="seo-section-body" style="padding:0;">
        <table class="data-table">
            <thead><tr><th>Date</th><th>Planned</th><th>Generated</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($schedule as $s): ?>
                <tr>
                    <td style="color:#64748b;"><?php echo date('d M Y', strtotime($s['schedule_date'])); ?></td>
                    <td><?php echo $s['blogs_to_generate']; ?></td>
                    <td><strong><?php echo $s['blogs_generated']; ?></strong></td>
                    <td>
                        <?php $sc=['completed'=>'badge-success','in_progress'=>'badge-warning','failed'=>'badge-danger','pending'=>'badge-secondary']; $cls=$sc[$s['status']]??'badge-secondary'; ?>
                        <span class="badge <?php echo $cls; ?>"><?php echo ucfirst($s['status']); ?></span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Bottom Navigation -->
<div class="action-row" style="margin-bottom:32px;">
    <a href="../dashboard.php" class="btn-action gray">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    <a href="manage-blogs.php" class="btn-action purple">
        <i class="fas fa-list"></i> Manage All Blogs
    </a>
</div>

<?php endif; ?>
</div>

<script>
function generateBlogs(count) {
    const statusDiv = document.getElementById('generationStatus');
    statusDiv.innerHTML = `
        <div class="alert alert-info" style="margin-top:20px;">
            <i class="fas fa-spinner fa-spin"></i>
            <span>Generating ${count} blog(s)... This may take a few minutes. Please wait...</span>
        </div>
    `;

    fetch('../api/auto-blog-generator.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'count=' + count
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            let html = `
                <div class="alert alert-success" style="margin-top:20px;">
                    <i class="fas fa-check-circle"></i>
                    <span><strong>Success!</strong> Generated ${data.generated} blog(s). Reloading...</span>
                </div>
            `;
            if (data.blogs && data.blogs.length > 0) {
                html += '<ul style="margin:12px 0 0 20px;">';
                data.blogs.forEach(blog => {
                    html += `<li style="padding:4px 0;font-size:14px;">${blog.title} <span style="color:#667eea;">(${blog.word_count} words, SEO: ${blog.seo_score}/100)</span></li>`;
                });
                html += '</ul>';
            }
            if (data.errors && data.errors.length > 0) {
                html += '<div class="alert alert-warning" style="margin-top:16px;"><i class="fas fa-exclamation-triangle"></i><div><strong>Warnings:</strong><ul style="margin:6px 0 0 16px;">';
                data.errors.forEach(e => { html += `<li>${e}</li>`; });
                html += '</ul></div></div>';
            }
            statusDiv.innerHTML = html;
            setTimeout(() => location.reload(), 3000);
        } else {
            statusDiv.innerHTML = `
                <div class="alert alert-error" style="margin-top:20px;">
                    <i class="fas fa-exclamation-circle"></i>
                    <span><strong>Error:</strong> ${data.message || 'Failed to generate blogs'}</span>
                </div>
            `;
        }
    })
    .catch(error => {
        statusDiv.innerHTML = `
            <div class="alert alert-error" style="margin-top:20px;">
                <i class="fas fa-exclamation-circle"></i>
                <span><strong>Error:</strong> ${error.message}</span>
            </div>
        `;
    });
}
</script>

<?php include '../includes/footer.php'; ?>
