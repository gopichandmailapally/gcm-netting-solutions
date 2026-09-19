<?php
/**
 * GCM Netting Solutions - Blogs Page
 * Blog posts about safety nets, tips, and industry updates
 */

define('GCM_INIT', true);
require_once 'config/config.php';

$page_title = 'Blog - Tips, Guides & Updates';
$meta_description = 'Read our blog for safety net installation tips, maintenance guides, industry updates, and expert advice for Chennai property owners.';
$meta_keywords = 'safety nets blog, installation tips, maintenance guide, chennai';

// Get selected category from URL
$selected_category = isset($_GET['category']) ? $_GET['category'] : 'all';

// Load blogs from JSON files (each blog is a separate file)
$all_blogs = [];
$blogs_dir = __DIR__ . '/data/blogs/';

if (is_dir($blogs_dir)) {
    $files = glob($blogs_dir . '*.json');
    foreach ($files as $file) {
        // Skip index and stats files
        $basename = basename($file);
        if ($basename === 'index.json' || $basename === 'stats.json') {
            continue;
        }
        
        $json_content = file_get_contents($file);
        $blog = json_decode($json_content, true);
        
        if ($blog && isset($blog['title']) && isset($blog['content'])) {
            $all_blogs[] = $blog;
        }
    }
}

// Sort by created_at (newest first)
usort($all_blogs, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Store total count before filtering
$total_blogs_count = count($all_blogs);

// Filter by category if selected (currently blogs don't have categories, so this is future-proof)
if ($selected_category !== 'all') {
    $all_blogs = array_filter($all_blogs, function($blog) use ($selected_category) {
        return isset($blog['category']) && $blog['category'] === $selected_category;
    });
}

// For now, don't show category filter since blogs don't have categories yet
$category_counts = [];

// Pagination
$per_page_bl  = 20;
$current_pg   = max(1, (int)($_GET['pg'] ?? 1));
$filtered_bl  = count($all_blogs);
$total_pgs_bl = max(1, (int)ceil($filtered_bl / $per_page_bl));
$current_pg   = min($current_pg, $total_pgs_bl);
$offset_bl    = ($current_pg - 1) * $per_page_bl;
$paged_blogs  = array_slice(array_values($all_blogs), $offset_bl, $per_page_bl);

$current_page = 'blogs';

include 'includes/modern-header.php';
?>

<style>
/* Blogs Page Styles - Matching FAQs Design */
.blogs-page {
    background: #f8f9fa;
    min-height: 100vh;
    padding-bottom: 60px;
}

.page-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    margin-bottom: 50px;
    color: white;
    text-align: center;
}

.page-banner h1 {
    font-size: 48px;
    font-weight: 700;
    margin-bottom: 15px;
}

.page-banner p {
    font-size: 20px;
    opacity: 0.95;
}

.blog-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 40px;
    margin-top: 30px;
}

/* Sidebar - Matching FAQs Sidebar */
.blog-sidebar {
    position: sticky;
    top: 100px;
    height: fit-content;
}

.sidebar-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
}

.sidebar-card h3 {
    font-size: 18px;
    margin-bottom: 20px;
    color: #2d3748;
    display: flex;
    align-items: center;
    gap: 10px;
}

.category-list {
    list-style: none;
}

.category-list li {
    margin-bottom: 10px;
}

.category-list a {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 15px;
    background: #f7fafc;
    border-radius: 10px;
    color: #4a5568;
    text-decoration: none;
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
    font-weight: 600;
}

.category-count {
    background: rgba(255,255,255,0.3);
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.category-list a.active .category-count {
    background: rgba(255,255,255,0.3);
}

/* Blog Content */
.blog-content {
    min-height: 400px;
}

.blog-grid {
    display: grid;
    gap: 20px;
}

.blog-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
}

.blog-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15);
}

.blog-header {
    padding: 25px 30px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 20px;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
    transition: all 0.3s ease;
}

.blog-header:hover {
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.1) 0%, rgba(118, 75, 162, 0.1) 100%);
}

.blog-header-content h3 {
    font-size: 20px;
    color: #2d3748;
    font-weight: 600;
    margin-bottom: 10px;
}

.blog-category-badge {
    display: inline-block;
    padding: 4px 12px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 10px;
}

.blog-meta {
    font-size: 13px;
    color: #718096;
    display: flex;
    gap: 15px;
    margin-top: 10px;
}

.blog-icon {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 18px;
    transition: transform 0.3s ease;
    flex-shrink: 0;
}

.blog-card.active .blog-icon {
    transform: rotate(180deg);
}

.blog-excerpt {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.blog-excerpt-content {
    padding: 25px 30px;
    color: #4a5568;
    font-size: 15px;
    line-height: 1.8;
    border-top: 2px solid rgba(102, 126, 234, 0.1);
}

.blog-card.active .blog-excerpt {
    max-height: 1000px;
}

.blog-read-more {
    display: inline-block;
    margin-top: 15px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.blog-read-more:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 15px;
}

.empty-state i {
    font-size: 80px;
    color: #cbd5e0;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 24px;
    color: #2d3748;
    margin-bottom: 10px;
}

.empty-state p {
    color: #718096;
    font-size: 16px;
}

/* Pagination */
.pagination-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 30px;
    padding: 20px 0 10px;
    border-top: 2px solid #e2e8f0;
}
.pag-info { color: #718096; font-size: 14px; }
.pag-links { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
.pag-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 40px;
    height: 40px;
    padding: 0 12px;
    border-radius: 10px;
    background: white;
    color: #4a5568;
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    border: 2px solid #e2e8f0;
    transition: all 0.2s ease;
}
.pag-btn:hover { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: white; border-color: transparent; }
.pag-btn.active { background: linear-gradient(135deg,#667eea 0%,#764ba2 100%); color: white; border-color: transparent; pointer-events: none; }
.pag-dots { color: #a0aec0; font-size: 14px; padding: 0 4px; }

/* Responsive */
@media (max-width: 992px) {
    .blog-layout {
        grid-template-columns: 1fr;
    }

    .blog-sidebar {
        position: static;
    }
}

@media (max-width: 768px) {
    .page-banner h1 {
        font-size: 32px;
    }

    .blog-header-content h3 {
        font-size: 16px;
    }
}
</style>

<div class="blogs-page">
    <!-- Page Header -->
    <div class="page-banner">
        <div class="container">
            <h1><i class="fas fa-blog"></i> Our Blog</h1>
            <p>Tips, Guides & Industry Updates</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Blog Layout: Sidebar + Content -->
        <div class="blog-layout">
            <!-- Sidebar -->
            <aside class="blog-sidebar">
                <div class="sidebar-card">
                    <h3><i class="fas fa-newspaper"></i> Blog Stats</h3>
                    <div style="text-align: center; padding: 20px 0;">
                        <div style="font-size: 48px; font-weight: 700; color: #667eea; line-height: 1;"><?php echo $total_blogs_count; ?></div>
                        <div style="color: #718096; font-size: 14px; margin-top: 10px;">Total Blog Posts</div>
                    </div>
                </div>

                <div class="sidebar-card">
                    <h3><i class="fas fa-phone"></i> Need Help?</h3>
                    <p style="font-size: 14px; color: #718096; margin-bottom: 15px;">
                        Can't find what you're looking for? Contact us directly!
                    </p>
                    <a href="tel:+919121399234" style="display: block; padding: 12px; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600; margin-bottom: 10px;">
                        <i class="fas fa-phone-alt"></i> +91 91213 99234
                    </a>
                    <a href="contact.php" style="display: block; padding: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-align: center; border-radius: 10px; text-decoration: none; font-weight: 600;">
                        <i class="fas fa-envelope"></i> Contact Form
                    </a>
                </div>
            </aside>

            <!-- Blog Content -->
            <div class="blog-content">
                <?php if (empty($all_blogs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h3>No Blogs Found</h3>
                        <p>
                            <?php if ($selected_category !== 'all'): ?>
                                No blog posts available in this category yet. Try viewing all categories.
                            <?php else: ?>
                                Blog posts will appear here once they are published.
                            <?php endif; ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="blog-grid">
                        <?php foreach ($paged_blogs as $blog): ?>
                            <div class="blog-card">
                                <div class="blog-header" onclick="toggleBlog(this)">
                                    <div class="blog-header-content">
                                        <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
                                        <div class="blog-meta">
                                            <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($blog['author'] ?? 'GCM Netting Solutions'); ?></span>
                                            <span><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($blog['created_at'])); ?></span>
                                            <span><i class="fas fa-eye"></i> <?php echo $blog['views'] ?? 0; ?> views</span>
                                        </div>
                                    </div>
                                    <div class="blog-icon">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </div>
                                <div class="blog-excerpt">
                                    <div class="blog-excerpt-content">
                                        <?php 
                                        $excerpt = strip_tags($blog['content']);
                                        echo htmlspecialchars(substr($excerpt, 0, 300)) . '...'; 
                                        ?>
                                        <br>
                                        <a href="<?php echo SITE_URL; ?>/blog/<?php echo urlencode($blog['slug']); ?>" class="blog-read-more">
                                            Read Full Article <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($total_pgs_bl > 1): ?>
                    <div class="pagination-bar">
                        <span class="pag-info">Showing <?php echo $offset_bl + 1; ?>–<?php echo min($offset_bl + $per_page_bl, $filtered_bl); ?> of <?php echo $filtered_bl; ?> articles</span>
                        <div class="pag-links">
                            <?php if ($current_pg > 1): ?>
                                <a href="?category=<?php echo urlencode($selected_category); ?>&pg=<?php echo $current_pg - 1; ?>" class="pag-btn">&#8249; Prev</a>
                            <?php endif;
                            $sp = max(1, $current_pg - 3);
                            $ep = min($total_pgs_bl, $current_pg + 3);
                            if ($sp > 1) echo '<span class="pag-dots">…</span>';
                            for ($pp = $sp; $pp <= $ep; $pp++): ?>
                                <a href="?category=<?php echo urlencode($selected_category); ?>&pg=<?php echo $pp; ?>" class="pag-btn <?php echo $pp == $current_pg ? 'active' : ''; ?>"><?php echo $pp; ?></a>
                            <?php endfor;
                            if ($ep < $total_pgs_bl) echo '<span class="pag-dots">…</span>';
                            if ($current_pg < $total_pgs_bl): ?>
                                <a href="?category=<?php echo urlencode($selected_category); ?>&pg=<?php echo $current_pg + 1; ?>" class="pag-btn">Next &#8250;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleBlog(element) {
    const card = element.closest('.blog-card');
    const wasActive = card.classList.contains('active');
    
    // Close all blogs
    document.querySelectorAll('.blog-card').forEach(c => {
        c.classList.remove('active');
    });
    
    // Open clicked blog if it wasn't active
    if (!wasActive) {
        card.classList.add('active');
    }
}

// Open first blog by default
document.addEventListener('DOMContentLoaded', function() {
    const firstBlog = document.querySelector('.blog-card');
    if (firstBlog) {
        firstBlog.classList.add('active');
    }
});
</script>

<?php include 'includes/modern-footer.php'; ?>
