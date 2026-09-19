<?php
/**
 * FAQ Generator - Admin Panel
 * Auto-generate and bulk-generate FAQs with AI
 */

define('GCM_INIT', true);
require_once '../../config/config.php';
require_once '../../config/blog-categories.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'Blog Generator';
include '../includes/header.php';

// Load settings from config file
$config_file = dirname(dirname(__DIR__)) . '/config/auto-generation-config.json';
if (file_exists($config_file)) {
    $config = json_decode(file_get_contents($config_file), true);
    $blog_config = $config['blog_generator'] ?? [];
} else {
    $blog_config = [
        'enabled' => true,
        'daily_count' => 1,
        'generation_time' => 'random',
        'last_run' => null,
        'total_generated' => 0
    ];
}

// Get blog categories from config
$blog_categories = get_blog_categories();

// Get statistics (database not required - using default values)
$total_blogs = ['count' => 0];
$ai_generated = ['count' => 0];
$categories = [];
foreach ($blog_categories as $cat_name) {
    $categories[] = [
        'name' => $cat_name,
        'icon' => get_blog_category_icon($cat_name),
        'description' => '',
        'count' => 0
    ];
}
$settings = [
    'daily_blog_count' => $blog_config['daily_count'] ?? 1,
    'auto_generate_enabled' => $blog_config['enabled'] ?? true,
    'last_generation_time' => $blog_config['last_run'] ?? null,
    'total_generated' => $blog_config['total_generated'] ?? 0,
    'random_time' => true
];
$recent_blogs = [];
?>

<div class="blog-generator">
    <!-- Page Header -->
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-pen-fancy"></i> Blog Generator</h1>
            <p>Auto-generate blog posts with AI - Daily automation available</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-primary" onclick="scrollToBulkGenerator()">
                <i class="fas fa-layer-group"></i> Bulk Generate
            </button>
            <button class="btn btn-success" onclick="generateSingleBlog()">
                <i class="fas fa-magic"></i> Generate 1 Blog Now
            </button>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card blue">
            <div class="stat-icon">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($total_blogs['count']); ?></h3>
                <p>Total Blogs</p>
                <div class="stat-footer">
                    <span class="badge-success">
                        <?php echo $ai_generated['count']; ?> AI Generated
                    </span>
                </div>
            </div>
        </div>
        
        <div class="stat-card green">
            <div class="stat-icon">
                <i class="fas fa-robot"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $settings['daily_blog_count'] ?? 1; ?></h3>
                <p>Daily Auto-Generate</p>
                <div class="stat-footer">
                    <span class="badge-<?php echo $settings['auto_generate_enabled'] ? 'success' : 'danger'; ?>">
                        <?php echo $settings['auto_generate_enabled'] ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
            </div>
        </div>
        
        <div class="stat-card purple">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo $settings['last_generation_time'] ? date('M j, g:i A', strtotime($settings['last_generation_time'])) : 'Never'; ?></h3>
                <p>Last Generated</p>
                <div class="stat-footer">
                    <span class="text-muted">Auto-runs daily at random time</span>
                </div>
            </div>
        </div>
        
        <div class="stat-card orange">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-content">
                <h3><?php echo number_format($settings['total_generated'] ?? 0); ?></h3>
                <p>Total Generated</p>
                <div class="stat-footer">
                    <span class="text-muted">Lifetime count</span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Auto-Generation Settings -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-cog"></i> Auto-Generation Settings</h2>
            <div class="toggle-container">
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
        <div class="card-body">
            <form id="blogSettingsForm" class="settings-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar-day"></i> Daily Blog Count</label>
                        <input type="number" name="daily_count" 
                               value="<?php echo $settings['daily_blog_count'] ?? 1; ?>" 
                               min="1" max="10" class="form-control">
                        <small>How many blogs to generate daily (1-10)</small>
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
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="testGeneration()">
                        <i class="fas fa-flask"></i> Test Generation Now
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Generation Panel -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-layer-group"></i> Bulk Blog Generation</h2>
        </div>
        <div class="card-body">
            <form id="bulkGenerateForm" class="bulk-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-hashtag"></i> Number of Blog Posts</label>
                        <input type="number" name="blog_count" id="blogCountInput"
                               value="3" min="1" max="20" class="form-control" 
                               style="font-size: 18px; font-weight: 600; padding: 15px;">
                        <small><strong>⚠️ You can change this!</strong> Enter 1-20 (blogs take 1 min each)</small>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-folder"></i> Category</label>
                        <select name="category" class="form-control">
                            <option value="random">Random Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>">
                                    <?php echo $cat['name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small>Choose specific category or random</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lightbulb"></i> Focus Topics (Optional)</label>
                    <input type="text" name="topics" class="form-control" 
                           placeholder="e.g., safety tips, installation guide, maintenance">
                    <small>Comma-separated topics for blog posts</small>
                </div>
                
                <div class="bulk-actions">
                    <button type="submit" class="btn btn-success btn-large">
                        <i class="fas fa-magic"></i> Generate Blogs with AI
                    </button>
                </div>
            </form>
            
            <!-- Progress Bar -->
            <div id="bulkProgress" class="progress-section" style="display: none;">
                <div class="progress-header">
                    <h3><i class="fas fa-spinner fa-spin"></i> Generating Blogs...</h3>
                    <span id="progressCount">0 / 0</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressBar"></div>
                </div>
                <div class="progress-details" id="progressDetails"></div>
            </div>
        </div>
    </div>
    
    <!-- Where Are Blogs -->
    <div class="content-card" style="background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%); border: 2px solid #3B82F6;">
        <div class="card-header" style="background: transparent;">
            <h2><i class="fas fa-info-circle"></i> Where Are My Generated Blogs?</h2>
        </div>
        <div class="card-body">
            <p style="font-size: 16px; margin-bottom: 20px; color: #1E293B;">
                <strong>Blogs are automatically saved and available in 2 places:</strong>
            </p>
            <div style="display: grid; gap: 15px;">
                <a href="manage-blogs.php" class="btn btn-primary" style="width: 100%; justify-content: center;">
                    <i class="fas fa-list-ul"></i> View All Blogs (Admin Panel)
                </a>
                <a href="<?php echo SITE_URL; ?>/blog.php" class="btn btn-success" style="width: 100%; justify-content: center;" target="_blank">
                    <i class="fas fa-globe"></i> View Blogs on Website (Public Page)
                </a>
            </div>
            <p style="margin-top: 15px; color: #64748B; font-size: 14px;">
                <i class="fas fa-folder"></i> <strong>Storage:</strong> Blogs saved as JSON files in <code>/data/blogs/</code>
            </p>
        </div>
    </div>

    <!-- Recent Blogs -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-history"></i> Recent Blogs</h2>
            <a href="manage-blogs.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-list"></i> View All Blogs
            </a>
        </div>
        <div class="card-body">
            <div class="blog-list">
                <?php if (empty($recent_blogs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No blog posts yet. Generate some to get started!</p>
                        <button class="btn btn-primary" onclick="generateSingleBlog()">
                            Generate First Blog Post
                        </button>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_blogs as $blog): ?>
                        <div class="blog-item">
                            <div class="blog-header">
                                <div class="blog-badge">
                                    <?php if ($blog['is_ai_generated']): ?>
                                        <span class="badge-ai"><i class="fas fa-robot"></i> AI</span>
                                    <?php endif; ?>
                                    <span class="badge-category"><?php echo $blog['category_name']; ?></span>
                                </div>
                                <div class="blog-stats">
                                    <span><i class="fas fa-eye"></i> <?php echo $blog['views']; ?></span>
                                    <span><i class="fas fa-thumbs-up"></i> <?php echo $blog['likes']; ?></span>
                                </div>
                            </div>
                            <div class="blog-title">
                                <i class="fas fa-file-alt"></i>
                                <?php echo htmlspecialchars($blog['title']); ?>
                            </div>
                            <div class="blog-excerpt">
                                <?php echo substr(strip_tags($blog['content']), 0, 150) . '...'; ?>
                            </div>
                            <div class="blog-footer">
                                <span class="blog-date">
                                    <i class="fas fa-clock"></i> 
                                    <?php echo date('M j, Y g:i A', strtotime($blog['created_at'])); ?>
                                </span>
                                <div class="blog-actions">
                                    <button class="btn-icon" onclick="editBlog(<?php echo $blog['id']; ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn-icon" onclick="viewBlog(<?php echo $blog['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="btn-icon danger" onclick="deleteBlog(<?php echo $blog['id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Blog Categories Overview -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-folder-tree"></i> Blog Categories</h2>
        </div>
        <div class="card-body">
            <div class="category-grid">
                <?php if (empty($categories)): ?>
                    <div class="empty-state">
                        <i class="fas fa-folder-open"></i>
                        <p>No blog categories configured yet</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                        <div class="category-card">
                            <div class="category-icon">
                                <i class="fas <?php echo $cat['icon']; ?>"></i>
                            </div>
                            <h4><?php echo $cat['name']; ?></h4>
                            <p><?php echo $cat['description']; ?></p>
                            <div class="category-count">
                                0 Blogs
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
/* Modern Blog Generator Styles */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes shimmer {
    0% {
        background-position: -1000px 0;
    }
    100% {
        background-position: 1000px 0;
    }
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

@keyframes glow {
    0%, 100% {
        box-shadow: 0 0 20px rgba(139, 92, 246, 0.3);
    }
    50% {
        box-shadow: 0 0 30px rgba(139, 92, 246, 0.6);
    }
}

.blog-generator { padding:28px 30px; background:#f0f2f8; min-height:100vh; animation:fadeInUp .6s ease; }

/* ── Page Header ─────────────────────────────────────── */
.page-header {
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    border-radius: 20px; padding: 28px 32px;
    display: flex; justify-content: space-between;
    align-items: center; gap: 20px; margin-bottom: 26px;
    box-shadow: 0 12px 40px rgba(102,126,234,.45);
    position: relative; overflow: hidden; color: white;
}
.page-header::before {
    content:''; position:absolute; top:-60px; right:-60px;
    width:200px; height:200px;
    background:rgba(255,255,255,.08); border-radius:50%;
}
.page-header::after {
    content:''; position:absolute; bottom:-40px; left:30%;
    width:140px; height:140px;
    background:rgba(255,255,255,.05); border-radius:50%;
}
.page-header .header-left h1 {
    font-size:28px; font-weight:700; color:white; margin:0 0 4px;
    display:flex; align-items:center; gap:12px;
}
.page-header .header-left p { color:rgba(255,255,255,.82); font-size:14px; margin:0; }
.header-actions { display:flex; gap:12px; flex-shrink:0; position:relative; z-index:1; flex-wrap:wrap; }

/* ── Buttons ─────────────────────────────────────────── */
.btn {
    padding:11px 22px; border-radius:10px; border:none;
    font-weight:600; font-size:14px; cursor:pointer;
    transition:all .25s; text-decoration:none;
    display:inline-flex; align-items:center; gap:8px; white-space:nowrap;
}
.btn-primary {
    background:rgba(255,255,255,.18); color:white;
    border:2px solid rgba(255,255,255,.45); backdrop-filter:blur(4px);
}
.btn-primary:hover { background:white; color:#667eea; border-color:white; transform:translateY(-2px); box-shadow:0 6px 20px rgba(0,0,0,.2); }
.btn-success  { background:linear-gradient(135deg,#10B981,#059669); color:white; border:none; }
.btn-success:hover  { transform:translateY(-2px); box-shadow:0 6px 20px rgba(16,185,129,.4); }
.btn-secondary { background:rgba(255,255,255,.15); color:white; border:2px solid rgba(255,255,255,.35); backdrop-filter:blur(4px); }
.btn-secondary:hover { background:rgba(255,255,255,.28); transform:translateY(-2px); }
.btn-lg { padding:15px 36px; font-size:16px; border-radius:12px; }
.btn-sm { padding:8px 16px; font-size:13px; }

/* ── Content Cards ───────────────────────────────────── */
.content-card {
    background:white; border-radius:18px;
    box-shadow:0 4px 20px rgba(0,0,0,.08);
    margin-bottom:24px; overflow:hidden;
    border:1px solid #f1f5f9; transition:box-shadow .2s;
}
.content-card:hover { box-shadow:0 8px 32px rgba(102,126,234,.12); }
.card-header {
    background:linear-gradient(135deg,#f8faff 0%,#eef2ff 100%);
    padding:22px 28px; border-bottom:2px solid #e0e7ff;
    display:flex; justify-content:space-between; align-items:center; gap:12px;
}
.card-header h2 {
    font-size:20px; font-weight:700; color:#1e293b; margin:0;
    display:flex; align-items:center; gap:10px;
}
.card-header h2 i { color:#667eea; }
.card-header p { margin:0; color:#64748b; font-size:13px; }
.card-body { padding:28px; }

/* ── Settings Form ───────────────────────────────────── */
.form-row { display:flex; gap:20px; flex-wrap:wrap; }
.form-group { flex:1; min-width:200px; margin-bottom:20px; }
.form-group label { display:flex; align-items:center; gap:8px; margin-bottom:8px; font-weight:700; color:#1e293b; font-size:13px; text-transform:uppercase; letter-spacing:.5px; }
.form-control { width:100%; padding:12px 16px; border:2px solid #e2e8f0; border-radius:10px; font-size:15px; transition:all .2s; box-sizing:border-box; }
.form-control:focus { outline:none; border-color:#667eea; box-shadow:0 0 0 3px rgba(102,126,234,.15); }
.form-text { display:block; margin-top:6px; color:#64748b; font-size:12px; }
.form-actions { text-align:center; padding-top:8px; }

/* ── Generation Info Banner ──────────────────────────── */
.generation-info {
    background:linear-gradient(135deg,#f0f9ff,#e0f2fe);
    border:2px solid #7dd3fc; border-radius:14px;
    padding:20px; margin-bottom:24px;
}
.info-item { display:flex; align-items:center; gap:10px; padding:8px 0; color:#0c4a6e; font-size:14px; }
.info-item:not(:last-child) { border-bottom:1px solid rgba(125,211,252,.4); }
.info-item i { color:#0284c7; font-size:16px; width:18px; }

/* Stats Grid */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.stat-card {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(248, 250, 252, 0.9) 100%);
    backdrop-filter: blur(10px);
    padding: 28px;
    border-radius: 20px;
    border: 2px solid transparent;
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    gap: 20px;
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s ease backwards;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transition: left 0.5s;
}

.stat-card:hover::before {
    left: 100%;
}

.stat-card:hover {
    transform: translateY(-12px) scale(1.02);
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
}

.stat-card.blue { 
    border-image: linear-gradient(135deg, #3B82F6, #8B5CF6) 1;
}
.stat-card.green { 
    border-image: linear-gradient(135deg, #10B981, #059669) 1;
}
.stat-card.purple { 
    border-image: linear-gradient(135deg, #8B5CF6, #EC4899) 1;
}
.stat-card.orange { 
    border-image: linear-gradient(135deg, #F59E0B, #EF4444) 1;
}

.stat-icon {
    width: 70px;
    height: 70px;
    border-radius: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 32px;
    color: #FFFFFF;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    animation: float 3s ease-in-out infinite;
}

.stat-card.blue .stat-icon { 
    background: linear-gradient(135deg, #3B82F6, #2563EB);
}
.stat-card.green .stat-icon { 
    background: linear-gradient(135deg, #10B981, #059669);
}
.stat-card.purple .stat-icon { 
    background: linear-gradient(135deg, #8B5CF6, #7C3AED);
    animation: glow 2s ease-in-out infinite;
}
.stat-card.orange .stat-icon { 
    background: linear-gradient(135deg, #F59E0B, #D97706);
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    font-size: 36px;
    font-weight: 800;
    background: linear-gradient(135deg, #1E293B, #475569);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin: 0 0 6px 0;
}

.stat-content p {
    font-size: 15px;
    color: #64748B;
    font-weight: 500;
    margin: 0 0 10px 0;
}

.stat-footer {
    margin-top: 10px;
}

/* Toggle Container */
.toggle-container {
    display: flex;
    align-items: center;
    gap: 15px;
}

.toggle-switch {
    position: relative;
    width: 64px;
    height: 34px;
}

.toggle-switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, #CBD5E1, #94A3B8);
    border-radius: 34px;
    transition: 0.4s;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.toggle-slider:before {
    position: absolute;
    content: "";
    height: 26px;
    width: 26px;
    left: 4px;
    bottom: 4px;
    background: linear-gradient(135deg, #FFFFFF, #F1F5F9);
    border-radius: 50%;
    transition: 0.4s;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
}

.toggle-switch input:checked + .toggle-slider {
    background: linear-gradient(135deg, #10B981, #059669);
    box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
}

.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(30px);
}

.toggle-status {
    font-weight: 700;
    font-size: 15px;
    color: #64748B;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.toggle-status.active {
    background: linear-gradient(135deg, #10B981, #059669);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Blog List */
.blog-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.blog-item {
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.95) 0%, rgba(248, 250, 252, 0.95) 100%);
    backdrop-filter: blur(10px);
    padding: 24px;
    border-radius: 16px;
    border: 2px solid #E2E8F0;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.blog-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(139, 92, 246, 0.1), transparent);
    transition: left 0.6s;
}

.blog-item:hover::before {
    left: 100%;
}

.blog-item:hover {
    border-color: #8B5CF6;
    transform: translateX(8px);
    box-shadow: 0 12px 40px rgba(139, 92, 246, 0.2);
}

.blog-header {
    display: flex;
    justify-content: space-between;
    margin-bottom: 14px;
}

.blog-badge {
    display: flex;
    gap: 10px;
}

.badge-ai {
    background: linear-gradient(135deg, #8B5CF6, #7C3AED);
    color: #FFFFFF;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(139, 92, 246, 0.3);
    animation: pulse 2s ease-in-out infinite;
}

.badge-category {
    background: linear-gradient(135deg, #DBEAFE, #BFDBFE);
    color: #1E40AF;
    padding: 6px 14px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 700;
}

.blog-stats {
    display: flex;
    gap: 16px;
    font-size: 14px;
    color: #64748B;
    font-weight: 600;
}

.blog-title {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 10px;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.blog-title i {
    color: #8B5CF6;
    margin-top: 3px;
}

.blog-excerpt {
    font-size: 15px;
    color: #64748B;
    line-height: 1.7;
    margin-bottom: 14px;
}

.blog-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 14px;
    border-top: 2px solid #E2E8F0;
}

.blog-date {
    font-size: 13px;
    color: #94A3B8;
    font-weight: 600;
}

.blog-actions {
    display: flex;
    gap: 10px;
}

.btn-icon {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    border: none;
    background: linear-gradient(135deg, #F1F5F9, #E2E8F0);
    color: #475569;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.btn-icon:hover {
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: #FFFFFF;
    transform: scale(1.15) rotate(5deg);
    box-shadow: 0 8px 20px rgba(59, 130, 246, 0.4);
}

.btn-icon.danger:hover {
    background: linear-gradient(135deg, #EF4444, #DC2626);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.4);
}

/* Category Grid */
.category-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 20px;
}

.category-card {
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.9) 0%, rgba(255, 255, 255, 0.9) 100%);
    backdrop-filter: blur(10px);
    padding: 28px;
    border-radius: 16px;
    border: 2px solid #E2E8F0;
    text-align: center;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    position: relative;
    overflow: hidden;
}

.category-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s;
}

.category-card:hover::before {
    left: 100%;
}

.category-card:hover {
    transform: translateY(-8px) scale(1.03);
    box-shadow: 0 16px 48px rgba(59, 130, 246, 0.2);
    border-color: #3B82F6;
}

.category-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 16px;
    border-radius: 16px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    color: #FFFFFF;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
    animation: float 3s ease-in-out infinite;
}

.category-card h4 {
    font-size: 17px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 8px;
}

.category-card p {
    font-size: 14px;
    color: #64748B;
    margin-bottom: 14px;
    line-height: 1.5;
}

.category-count {
    font-size: 15px;
    font-weight: 700;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

/* Progress Section */
.progress-section {
    margin-top: 28px;
    padding: 28px;
    background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%);
    border-radius: 16px;
    border: 2px solid #BFDBFE;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.1);
}

.progress-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.progress-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
}

.progress-bar {
    height: 12px;
    background: #E2E8F0;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3B82F6, #8B5CF6);
    border-radius: 12px;
    transition: width 0.5s ease;
    box-shadow: 0 0 20px rgba(59, 130, 246, 0.5);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
}

.empty-state i {
    font-size: 64px;
    color: #c4b5fd;
    margin-bottom: 20px;
    animation: float 3s ease-in-out infinite;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 700;
    color: #475569;
    margin-bottom: 10px;
}

.empty-state p {
    font-size: 15px;
    color: #94A3B8;
    margin-bottom: 24px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        flex-direction: row;
        text-align: left;
        padding: 24px;
    }
    
    .stat-icon {
        width: 60px;
        height: 60px;
        font-size: 28px;
    }
    
    .stat-content h3 {
        font-size: 28px;
    }
    
    .category-grid {
        grid-template-columns: 1fr;
    }
    
    .form-row {
        flex-direction: column;
    }
    
    .page-header {
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
    }
    
    .header-actions {
        width: 100%;
    }
    
    .header-actions .btn {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .card-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .toggle-container {
        width: 100%;
        justify-content: space-between;
    }
}

@media (max-width: 480px) {
    .stat-content h3 {
        font-size: 24px;
    }
    
    .stat-content p {
        font-size: 13px;
    }
    
    .stat-icon {
        width: 50px;
        height: 50px;
        font-size: 24px;
    }
}
</style>

<script>
// Toggle Auto-Generation
function toggleAutoGeneration(enabled) {
    fetch('../api/toggle-auto-generation.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            enabled: enabled,
            type: 'blog'  // ✅ Specify Blog generator
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('toggleStatus').textContent = enabled ? 'Auto-Gen ON' : 'Auto-Gen OFF';
            document.getElementById('toggleStatus').classList.toggle('active', enabled);
            
            // Show notification
            alert(data.message || 'Blog auto-generation ' + (enabled ? 'enabled' : 'disabled'));
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

// Save Settings
document.getElementById('blogSettingsForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('../api/save-blog-settings.php', {
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
    });
});

// Generate Single Blog
function generateSingleBlog() {
    if (!confirm('Generate 1 Blog Post now? This takes 10-20 seconds.')) return;
    
    showToast('Generating blog post with AI...', 'info');
    
    fetch('../api/generate-single-blog-simple.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('Blog generated successfully! Title: ' + data.title, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    });
}

// Bulk Generate Blogs
document.getElementById('bulkGenerateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    const count = formData.get('blog_count');
    
    if (!confirm(`Generate ${count} Blog Posts with AI? This takes about ${count} minutes.`)) return;
    
    document.getElementById('bulkProgress').style.display = 'block';
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressCount').textContent = `0 / ${count}`;
    
    fetch('../api/generate-bulk-blogs-simple.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('progressBar').style.width = '100%';
            document.getElementById('progressCount').textContent = `${data.generated} / ${count}`;
            showToast(`Generated ${data.generated} blog posts successfully!`, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('Error: ' + data.message, 'error');
        }
    });
});

function showToast(message, type) {
    // Simple toast notification
    alert(message);
}

// Scroll to Bulk Generator
function scrollToBulkGenerator() {
    document.getElementById('bulkGenerateForm').scrollIntoView({ 
        behavior: 'smooth', 
        block: 'start' 
    });
    const form = document.getElementById('bulkGenerateForm').closest('.content-card');
    form.style.boxShadow = '0 0 20px rgba(59, 130, 246, 0.5)';
    setTimeout(() => {
        form.style.boxShadow = '';
    }, 2000);
}
</script>

<?php include '../includes/footer.php'; ?>
