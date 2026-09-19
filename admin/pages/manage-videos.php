<?php
/**
 * Manage Videos - Admin Panel
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

// Load videos from JSON files
$videos_dir = dirname(dirname(__DIR__)) . '/data/videos';
$all_videos = [];

if (is_dir($videos_dir)) {
    $files = glob($videos_dir . '/*.json');
    foreach ($files as $file) {
        $basename = basename($file);
        if ($basename === 'stats.json') {
            continue;
        }
        
        $content = file_get_contents($file);
        $video = json_decode($content, true);
        
        if ($video && isset($video['title'])) {
            $video['filename'] = $basename;
            $all_videos[] = $video;
        }
    }
}

// Sort by created_at (newest first)
usort($all_videos, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Get stats
$stats_file = $videos_dir . '/stats.json';
$stats = file_exists($stats_file) ? json_decode(file_get_contents($stats_file), true) : [];

$total_videos = count($all_videos);
$total_views = 0;
foreach ($all_videos as $video) {
    $total_views += $video['views'] ?? 0;
}

// Get unique categories
$categories = [];
foreach ($all_videos as $video) {
    if (!empty($video['category']) && !in_array($video['category'], $categories)) {
        $categories[] = $video['category'];
    }
}

$page_title = 'Manage Videos';
include '../includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="header-actions">
            <h1><i class="fas fa-video"></i> Manage Videos</h1>
            <div class="header-buttons">
                <button onclick="showAddVideoModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Video
                </button>
                <a href="<?php echo SITE_URL; ?>/videos.php" class="btn btn-success" target="_blank">
                    <i class="fas fa-external-link-alt"></i> View on Website
                </a>
            </div>
        </div>
    </div>

    <div class="website-link">
        <i class="fas fa-globe"></i> 
        <strong>Videos are live on your website:</strong>
        <a href="<?php echo SITE_URL; ?>/videos.php" target="_blank">
            <?php echo SITE_URL; ?>/videos.php <i class="fas fa-external-link-alt"></i>
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <i class="fas fa-video"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo $total_videos; ?></div>
                <div class="stat-label">Total Videos</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <i class="fas fa-eye"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo number_format($total_views); ?></div>
                <div class="stat-label">Total Views</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <i class="fas fa-folder"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo count($categories); ?></div>
                <div class="stat-label">Categories</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                <i class="fas fa-calendar"></i>
            </div>
            <div class="stat-info">
                <div class="stat-value"><?php echo isset($stats['last_added']) ? date('M d', strtotime($stats['last_added'])) : 'N/A'; ?></div>
                <div class="stat-label">Last Added</div>
            </div>
        </div>
    </div>

    <!-- Videos Grid -->
    <div class="videos-container">
        <?php if (empty($all_videos)): ?>
            <div class="empty-state">
                <i class="fas fa-video-slash"></i>
                <h3>No Videos Yet</h3>
                <p>Click "Add Video" to start adding YouTube videos to your website.</p>
                <button onclick="showAddVideoModal()" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Your First Video
                </button>
            </div>
        <?php else: ?>
            <div class="videos-grid">
                <?php foreach ($all_videos as $video): ?>
                    <div class="video-card">
                        <div class="video-thumbnail">
                            <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/mqdefault.jpg" 
                                 alt="<?php echo htmlspecialchars($video['title']); ?>">
                            <div class="video-play-btn"><i class="fas fa-play"></i></div>
                            <div class="video-overlay">
                                <a href="https://youtube.com/watch?v=<?php echo htmlspecialchars($video['youtube_id']); ?>" 
                                   target="_blank" class="btn-icon" title="Watch on YouTube">
                                    <i class="fab fa-youtube"></i>
                                </a>
                                <button onclick="deleteVideo('<?php echo htmlspecialchars($video['filename']); ?>')" 
                                        class="btn-icon btn-delete" title="Delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            <?php if (!empty($video['duration'])): ?>
                                <div class="video-duration"><?php echo htmlspecialchars($video['duration']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <?php if (!empty($video['category'])): ?>
                                <div class="video-category"><?php echo htmlspecialchars($video['category']); ?></div>
                            <?php endif; ?>
                            <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                            <?php if (!empty($video['description'])): ?>
                                <p><?php echo htmlspecialchars(substr($video['description'], 0, 100)); ?><?php echo strlen($video['description']) > 100 ? '...' : ''; ?></p>
                            <?php endif; ?>
                            <div class="video-meta">
                                <?php if (!empty($video['views'])): ?>
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($video['views']); ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($video['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Video Modal -->
<div id="addVideoModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-plus-circle"></i> Add New Video</h2>
            <span class="close-modal" onclick="closeAddVideoModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="addVideoForm">
                <div class="form-group">
                    <label for="youtube_url">YouTube URL <span class="required">*</span></label>
                    <input type="text" id="youtube_url" name="youtube_url" 
                           placeholder="https://www.youtube.com/watch?v=..." required>
                    <small>Paste the full YouTube video URL</small>
                </div>

                <div class="form-group">
                    <label for="category">Category <span class="required">*</span></label>
                    <select id="category" name="category" required>
                        <option value="">Select category...</option>
                        <option value="Installation Guides">Installation Guides</option>
                        <option value="Customer Testimonials">Customer Testimonials</option>
                        <option value="Product Demos">Product Demos</option>
                        <option value="Safety Tips">Safety Tips</option>
                        <option value="Before & After">Before & After</option>
                        <option value="Maintenance Tips">Maintenance Tips</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="ai-toggle-row">
                        <input type="checkbox" id="use_ai" name="use_ai" checked>
                        <span><i class="fas fa-robot"></i> &nbsp;Use AI to generate optimized title &amp; description</span>
                    </label>
                </div>

                <div id="manualFields" style="display: none;">
                    <div class="form-group">
                        <label for="manual_title">Title</label>
                        <input type="text" id="manual_title" name="manual_title" 
                               placeholder="Enter video title manually">
                    </div>

                    <div class="form-group">
                        <label for="manual_description">Description</label>
                        <textarea id="manual_description" name="manual_description" rows="4"
                                  placeholder="Enter video description manually"></textarea>
                    </div>
                </div>

                <div id="loadingMessage" style="display: none; text-align: center; padding: 20px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #667eea;"></i>
                    <p style="margin-top: 15px; color: #718096;">
                        <span id="loadingText">Processing video...</span>
                    </p>
                </div>

                <div class="form-actions">
                    <button type="button" onclick="closeAddVideoModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add Video
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* ── Page header ─────────────────────────────────────────── */
.content-header {
    margin-bottom: 0;
}
.content-header h1 {
    font-size: 28px;
    font-weight: 800;
    color: #1e293b;
    display: flex;
    align-items: center;
    gap: 10px;
}
.content-header h1 i {
    color: #667eea;
}

/* ── Website link strip ─────────────────────────────────── */
.website-link {
    display: flex;
    align-items: center;
    gap: 8px;
    background: linear-gradient(135deg, #eff6ff 0%, #f0f4ff 100%);
    border: 1px solid #c7d2fe;
    border-radius: 10px;
    padding: 12px 18px;
    margin: 16px 0 24px;
    font-size: 13.5px;
    color: #374151;
}
.website-link i { color: #6366f1; }
.website-link a {
    color: #4f46e5;
    font-weight: 600;
    text-decoration: none;
    transition: color .2s;
}
.website-link a:hover { color: #4338ca; text-decoration: underline; }

/* ── Stat cards ─────────────────────────────────────────── */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 18px;
    margin-bottom: 28px;
}

.stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 22px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    box-shadow: 0 4px 20px rgba(102,126,234,.10);
    border: 1px solid #e8eef8;
    transition: transform .25s, box-shadow .25s;
    position: relative;
    overflow: hidden;
}
.stat-card::after {
    content: '';
    position: absolute;
    top: 0; left: 0; bottom: 0;
    width: 4px;
    background: inherit;
    border-radius: 4px 0 0 4px;
}
.stat-card:nth-child(1)::after { background: linear-gradient(180deg,#667eea,#764ba2); }
.stat-card:nth-child(2)::after { background: linear-gradient(180deg,#f093fb,#f5576c); }
.stat-card:nth-child(3)::after { background: linear-gradient(180deg,#4facfe,#00f2fe); }
.stat-card:nth-child(4)::after { background: linear-gradient(180deg,#43e97b,#38f9d7); }
.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(102,126,234,.18);
}

.stat-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 22px;
    color: #fff;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(0,0,0,.15);
}

.stat-info .stat-value {
    font-size: 26px;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}
.stat-info .stat-label {
    font-size: 12px;
    color: #94a3b8;
    font-weight: 600;
    letter-spacing: .4px;
    text-transform: uppercase;
    margin-top: 4px;
}

/* ── Videos grid ────────────────────────────────────────── */
.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 22px;
    margin-top: 8px;
}

.video-card {
    background: #fff;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 18px rgba(0,0,0,.08);
    border: 1px solid #eef2ff;
    transition: transform .28s ease, box-shadow .28s ease;
}
.video-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 40px rgba(102,126,234,.2);
    border-color: #c7d2fe;
}

/* Thumbnail */
.video-thumbnail {
    position: relative;
    padding-bottom: 56.25%;
    background: #0f172a;
    overflow: hidden;
}
.video-thumbnail img {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    object-fit: cover;
    transition: transform .4s ease;
}
.video-card:hover .video-thumbnail img {
    transform: scale(1.05);
}

/* Play button in center on hover */
.video-play-btn {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%) scale(0.7);
    width: 56px; height: 56px;
    background: rgba(255,255,255,.92);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 22px;
    color: #ef4444;
    opacity: 0;
    transition: opacity .3s, transform .3s;
    pointer-events: none;
    z-index: 4;
}
.video-card:hover .video-play-btn {
    opacity: 1;
    transform: translate(-50%, -50%) scale(1);
}

.video-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(135deg, rgba(30,41,59,.75), rgba(102,126,234,.55));
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 14px;
    opacity: 0;
    transition: opacity .3s ease;
    z-index: 3;
}
.video-card:hover .video-overlay {
    opacity: 1;
}

.btn-icon {
    width: 46px; height: 46px;
    border-radius: 50%;
    background: rgba(255,255,255,.95);
    color: #667eea;
    border: none;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer;
    font-size: 18px;
    transition: transform .25s, background .25s, color .25s;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0,0,0,.2);
}
.btn-icon:hover {
    transform: scale(1.15);
    background: #667eea;
    color: #fff;
}
.btn-delete { background: #fee2e2; color: #ef4444; }
.btn-delete:hover { background: #ef4444; color: #fff; }

.video-duration {
    position: absolute;
    bottom: 10px; right: 10px;
    background: rgba(0,0,0,.82);
    color: #fff;
    padding: 3px 8px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    letter-spacing: .3px;
    backdrop-filter: blur(4px);
    z-index: 5;
}

/* Card body */
.video-info {
    padding: 18px 20px 20px;
}

/* Category badge — colour by category type */
.video-category {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: .3px;
    margin-bottom: 10px;
    background: linear-gradient(135deg,#667eea,#764ba2);
    color: #fff;
}

.video-info h3 {
    margin: 0 0 8px;
    font-size: 15.5px;
    font-weight: 700;
    color: #1e293b;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.45;
}
.video-info p {
    color: #64748b;
    font-size: 13px;
    margin: 0 0 14px;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-meta {
    display: flex;
    gap: 14px;
    font-size: 12px;
    color: #94a3b8;
    border-top: 1px solid #f1f5f9;
    padding-top: 12px;
    margin-top: 4px;
}
.video-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}
.video-meta .fa-eye   { color: #f093fb; }
.video-meta .fa-calendar { color: #4facfe; }

/* ── Empty state ────────────────────────────────────────── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: linear-gradient(135deg,#f8faff,#f0f4ff);
    border-radius: 16px;
    border: 2px dashed #c7d2fe;
    margin-top: 20px;
}
.empty-state i    { font-size: 56px; color: #c7d2fe; margin-bottom: 16px; display: block; }
.empty-state h3   { color: #1e293b; font-size: 22px; font-weight: 700; margin: 0 0 8px; }
.empty-state p    { color: #64748b; margin: 0 0 24px; }

/* ── Modal ──────────────────────────────────────────────── */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    inset: 0;
    background: rgba(15,23,42,.55);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
}
.modal-content {
    background: #fff;
    border-radius: 20px;
    width: 90%;
    max-width: 560px;
    max-height: 92vh;
    overflow-y: auto;
    box-shadow: 0 24px 64px rgba(0,0,0,.22);
    animation: modalIn .25s ease;
}
@keyframes modalIn {
    from { opacity:0; transform:translateY(24px) scale(.97); }
    to   { opacity:1; transform:translateY(0)    scale(1);  }
}

.modal-header {
    padding: 22px 26px 18px;
    background: linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 700;
    color: #fff;
    display: flex;
    align-items: center;
    gap: 10px;
}
.modal-header h2 i { font-size: 18px; }

.close-modal {
    font-size: 28px;
    color: rgba(255,255,255,.7);
    cursor: pointer;
    line-height: 1;
    transition: color .2s, transform .2s;
}
.close-modal:hover { color: #fff; transform: rotate(90deg); }

.modal-body { padding: 26px; }

/* ── Form ───────────────────────────────────────────────── */
.form-group { margin-bottom: 22px; }

.form-group label {
    display: block;
    margin-bottom: 7px;
    font-weight: 700;
    font-size: 13px;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: .5px;
}

.form-group input[type="text"],
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 14px;
    border: 1.5px solid #e2e8f0;
    border-radius: 10px;
    font-size: 14px;
    color: #1e293b;
    background: #f8fafc;
    transition: border-color .25s, box-shadow .25s, background .25s;
    box-sizing: border-box;
}
.form-group input::placeholder,
.form-group textarea::placeholder { color: #b0bec5; }
.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #667eea;
    background: #fff;
    box-shadow: 0 0 0 3px rgba(102,126,234,.15);
}
.form-group select { cursor: pointer; }

.form-group small {
    display: block;
    margin-top: 6px;
    color: #94a3b8;
    font-size: 12px;
}

/* AI toggle row */
.ai-toggle-row {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 16px;
    background: linear-gradient(135deg,#f0f4ff,#f8faff);
    border: 1.5px solid #c7d2fe;
    border-radius: 10px;
    cursor: pointer;
    transition: border-color .2s;
}
.ai-toggle-row:hover { border-color: #818cf8; }
.ai-toggle-row input[type="checkbox"] {
    width: 18px; height: 18px;
    accent-color: #667eea;
    cursor: pointer;
    flex-shrink: 0;
}
.ai-toggle-row span {
    font-size: 13px;
    font-weight: 600;
    color: #4338ca;
}

.required { color: #ef4444; font-weight: 800; }

/* Loading state */
#loadingMessage {
    text-align: center;
    padding: 24px;
    background: #f8faff;
    border-radius: 10px;
    border: 1px solid #e8eef8;
}
#loadingMessage i { color: #667eea; }
#loadingMessage p { color: #64748b; margin-top: 12px; font-size: 14px; }

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 20px;
    border-top: 1px solid #f1f5f9;
}
</style>

<script>
function showAddVideoModal() {
    document.getElementById('addVideoModal').style.display = 'flex';
}

function closeAddVideoModal() {
    document.getElementById('addVideoModal').style.display = 'none';
    document.getElementById('addVideoForm').reset();
    document.getElementById('loadingMessage').style.display = 'none';
}

// Toggle manual fields
document.getElementById('use_ai').addEventListener('change', function() {
    document.getElementById('manualFields').style.display = this.checked ? 'none' : 'block';
});

// Add Video Form Submit
document.getElementById('addVideoForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const loadingMsg = document.getElementById('loadingMessage');
    const loadingText = document.getElementById('loadingText');
    const submitBtn = this.querySelector('button[type="submit"]');
    
    submitBtn.disabled = true;
    loadingMsg.style.display = 'block';
    loadingText.textContent = 'Extracting video information...';
    
    try {
        const response = await fetch('../api/add-video.php', {
            method: 'POST',
            body: formData
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Video added successfully!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error adding video: ' + error.message);
    } finally {
        submitBtn.disabled = false;
        loadingMsg.style.display = 'none';
    }
});

// Delete Video
async function deleteVideo(filename) {
    if (!confirm('Are you sure you want to delete this video?')) {
        return;
    }
    
    try {
        const response = await fetch('../api/delete-video.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ filename })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Video deleted successfully!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        alert('Error deleting video: ' + error.message);
    }
}
</script>

<?php include '../includes/footer.php'; ?>
