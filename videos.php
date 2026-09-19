<?php
/**
 * GCM Netting Solutions - Videos Page
 * Video gallery of installations and testimonials
 */

define('GCM_INIT', true);
require_once 'config/config.php';

$page_title = 'Videos - Installation & Customer Testimonials';
$meta_description = 'Watch our installation process videos, customer testimonials, and safety net demonstrations. See how GCM Netting Solutions provides quality service across Chennai.';
$meta_keywords = 'safety nets videos, installation videos, customer testimonials, chennai';

// Get selected category from URL
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Load videos from JSON files
$all_videos = [];
$videos_dir = __DIR__ . '/data/videos/';

if (is_dir($videos_dir)) {
    $files = glob($videos_dir . '*.json');
    foreach ($files as $file) {
        // Skip stats file
        $basename = basename($file);
        if ($basename === 'stats.json') {
            continue;
        }
        
        $json_content = file_get_contents($file);
        $video = json_decode($json_content, true);
        
        if ($video && isset($video['title']) && isset($video['youtube_id'])) {
            $all_videos[] = $video;
        }
    }
}

// Sort by created_at (newest first)
usort($all_videos, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Store total count before filtering
$total_videos_count = count($all_videos);

// Get all unique categories
$categories = [];
foreach ($all_videos as $video) {
    if (!empty($video['category']) && !in_array($video['category'], $categories)) {
        $categories[] = $video['category'];
    }
}
sort($categories);

// Count videos per category
$category_counts = [];
foreach ($categories as $category) {
    $category_counts[$category] = 0;
}
foreach ($all_videos as $video) {
    if (isset($video['category']) && isset($category_counts[$video['category']])) {
        $category_counts[$video['category']]++;
    }
}

// Filter by category if selected
if ($selected_category !== 'all') {
    $all_videos = array_filter($all_videos, function($video) use ($selected_category) {
        return isset($video['category']) && $video['category'] === $selected_category;
    });
}

$current_page = 'videos';

include 'includes/modern-header.php';
?>

<style>
/* Videos Page Styles - Matching FAQs Design */
.videos-page {
    background: #f8f9fa;
    min-height: 100vh;
}

.video-page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 0 80px;
    text-align: center;
    color: white;
    position: relative;
    overflow: hidden;
}

.video-page-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') no-repeat bottom center;
    background-size: cover;
    opacity: 0.3;
}

.video-page-header h1 {
    font-size: 48px;
    margin: 0 0 15px 0;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.video-page-header p {
    font-size: 18px;
    opacity: 0.95;
    margin: 0;
    position: relative;
    z-index: 1;
}

.container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

.video-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 30px;
    margin-top: -40px;
    padding-bottom: 60px;
}

.video-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.sidebar-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.sidebar-card h3 {
    font-size: 18px;
    margin: 0 0 20px 0;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.category-list li {
    margin-bottom: 8px;
}

.category-list a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f8f9fa;
    border-radius: 10px;
    text-decoration: none;
    color: #4a5568;
    transition: all 0.3s ease;
    font-size: 14px;
}

.category-list a:hover {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateX(5px);
}

.category-list a.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.category-count {
    background: rgba(0,0,0,0.1);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.video-content {
    background: transparent;
}

.video-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 25px;
}

.video-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
}

.video-thumbnail {
    position: relative;
    padding-bottom: 56.25%;
    background: #000;
    cursor: pointer;
    overflow: hidden;
}

.video-thumbnail img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.video-card:hover .video-thumbnail img {
    transform: scale(1.05);
}

.play-button {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 70px;
    height: 70px;
    background: rgba(102, 126, 234, 0.9);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.video-card:hover .play-button {
    background: rgba(102, 126, 234, 1);
    transform: translate(-50%, -50%) scale(1.1);
}

.play-button i {
    color: white;
    font-size: 28px;
    margin-left: 4px;
}

.video-duration {
    position: absolute;
    bottom: 10px;
    right: 10px;
    background: rgba(0,0,0,0.8);
    color: white;
    padding: 4px 8px;
    border-radius: 5px;
    font-size: 12px;
    font-weight: 600;
}

.video-info {
    padding: 20px;
}

.video-info h3 {
    margin: 0 0 10px 0;
    font-size: 18px;
    color: #2d3748;
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-info p {
    color: #718096;
    font-size: 14px;
    margin: 0 0 15px 0;
    line-height: 1.6;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.video-meta {
    display: flex;
    gap: 15px;
    font-size: 13px;
    color: #718096;
}

.video-meta span {
    display: flex;
    align-items: center;
    gap: 5px;
}

.video-category-badge {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 10px;
}

.empty-state {
    background: white;
    border-radius: 15px;
    padding: 60px 40px;
    text-align: center;
}

.empty-state i {
    font-size: 64px;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #2d3748;
    margin: 0 0 15px 0;
}

.empty-state p {
    color: #718096;
    font-size: 16px;
    max-width: 500px;
    margin: 0 auto 30px;
}

.video-modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.9);
    align-items: center;
    justify-content: center;
}

.close-modal {
    position: absolute;
    top: 20px;
    right: 35px;
    color: white;
    font-size: 40px;
    font-weight: bold;
    cursor: pointer;
    z-index: 10001;
}

.close-modal:hover {
    color: #667eea;
}

.modal-content {
    width: 90%;
    max-width: 1200px;
}

.video-container {
    position: relative;
    padding-bottom: 56.25%;
    height: 0;
    overflow: hidden;
}

.video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}

@media (max-width: 968px) {
    .video-layout {
        grid-template-columns: 1fr;
    }
    
    .video-sidebar {
        position: static;
    }
    
    .video-grid {
        grid-template-columns: 1fr;
    }
    
    .video-page-header h1 {
        font-size: 32px;
    }
}
</style>

<div class="videos-page">
    <!-- Page Header -->
    <div class="video-page-header">
        <div class="container">
            <h1><i class="fas fa-play-circle"></i> Our Videos</h1>
            <p>Installation Guides, Tips & Customer Testimonials</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Video Layout: Sidebar + Content -->
        <div class="video-layout">
            <!-- Sidebar -->
            <aside class="video-sidebar">
                <div class="sidebar-card">
                    <h3><i class="fas fa-video"></i> Video Stats</h3>
                    <div style="text-align: center; padding: 20px 0;">
                        <div style="font-size: 48px; font-weight: 700; color: #667eea; line-height: 1;"><?php echo $total_videos_count; ?></div>
                        <div style="color: #718096; font-size: 14px; margin-top: 10px;">Total Videos</div>
                    </div>
                </div>

                <?php if (count($categories) > 0): ?>
                <div class="sidebar-card">
                    <h3><i class="fas fa-filter"></i> Categories</h3>
                    <ul class="category-list">
                        <li>
                            <a href="?category=all" class="<?php echo $selected_category === 'all' ? 'active' : ''; ?>">
                                <span><i class="fas fa-th"></i> All Videos</span>
                                <span class="category-count"><?php echo $total_videos_count; ?></span>
                            </a>
                        </li>
                        <?php foreach ($categories as $category): 
                            $count = $category_counts[$category] ?? 0;
                            if ($count > 0): ?>
                            <li>
                                <a href="?category=<?php echo urlencode($category); ?>" 
                                   class="<?php echo $selected_category === $category ? 'active' : ''; ?>">
                                    <span><?php echo htmlspecialchars($category); ?></span>
                                    <span class="category-count"><?php echo $count; ?></span>
                                </a>
                            </li>
                        <?php endif; endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <div class="sidebar-card">
                    <h3><i class="fab fa-youtube"></i> YouTube</h3>
                    <p style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                        Subscribe to our channel for more videos!
                    </p>
                    <a href="https://youtube.com/@gcmsafetynets" target="_blank" style="display: block; padding: 12px; background: #ff0000; color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">
                        <i class="fab fa-youtube"></i> Subscribe Now
                    </a>
                </div>

                <div class="sidebar-card">
                    <h3><i class="fas fa-phone"></i> Need Help?</h3>
                    <p style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                        Have questions? Contact us directly!
                    </p>
                    <a href="tel:+919121399234" style="display: block; padding: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">
                        <i class="fas fa-phone-alt"></i> +91 91213 99234
                    </a>
                    <a href="contact.php" style="display: block; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-envelope"></i> Contact Form
                    </a>
                </div>
            </aside>

            <!-- Video Content -->
            <div class="video-content">
                <?php if (empty($all_videos)): ?>
                    <div class="empty-state">
                        <i class="fas fa-video-slash"></i>
                        <h3>No Videos Found</h3>
                        <p>
                            <?php if ($selected_category !== 'all'): ?>
                                No videos available in this category yet. Try viewing all categories.
                            <?php else: ?>
                                Videos will appear here once they are added to the website.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="video-grid">
                        <?php foreach ($all_videos as $video): ?>
                            <div class="video-card">
                                <div class="video-thumbnail" onclick="playVideo('<?php echo htmlspecialchars($video['youtube_id']); ?>')">
                                    <img src="https://img.youtube.com/vi/<?php echo htmlspecialchars($video['youtube_id']); ?>/mqdefault.jpg" 
                                         alt="<?php echo htmlspecialchars($video['title']); ?>" 
                                         loading="lazy">
                                    <div class="play-button">
                                        <i class="fas fa-play"></i>
                                    </div>
                                    <?php if (!empty($video['duration'])): ?>
                                        <div class="video-duration"><?php echo htmlspecialchars($video['duration']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="video-info">
                                    <?php if (!empty($video['category'])): ?>
                                        <div class="video-category-badge"><?php echo htmlspecialchars($video['category']); ?></div>
                                    <?php endif; ?>
                                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                                    <?php if (!empty($video['description'])): ?>
                                        <p><?php echo htmlspecialchars(substr($video['description'], 0, 120)); ?><?php echo strlen($video['description']) > 120 ? '...' : ''; ?></p>
                                    <?php endif; ?>
                                    <div class="video-meta">
                                        <?php if (!empty($video['views'])): ?>
                                            <span><i class="fas fa-eye"></i> <?php echo number_format($video['views']); ?> views</span>
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
    </div>
</div>

<!-- Video Modal -->
<div id="videoModal" class="video-modal" onclick="closeVideoModal()">
    <span class="close-modal">&times;</span>
    <div class="modal-content" onclick="event.stopPropagation()">
        <div class="video-container">
            <iframe id="videoFrame" src="" frameborder="0" 
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                    allowfullscreen>
            </iframe>
        </div>
    </div>
</div>

<script>
function playVideo(videoId) {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('videoFrame');
    
    iframe.src = `https://www.youtube.com/embed/${videoId}?autoplay=1`;
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
    
    // Track video view
    if (typeof gtag !== 'undefined') {
        gtag('event', 'video_play', {
            'event_category': 'Video',
            'event_label': videoId
        });
    }
}

function closeVideoModal() {
    const modal = document.getElementById('videoModal');
    const iframe = document.getElementById('videoFrame');
    
    iframe.src = '';
    modal.style.display = 'none';
    document.body.style.overflow = '';
}

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('videoModal').style.display === 'flex') {
        closeVideoModal();
    }
});
</script>

<?php include 'includes/modern-footer.php'; ?>
