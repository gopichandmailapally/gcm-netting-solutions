<?php
/**
 * Blog Page - Display Single Blog or All Blog Posts
 */
define('GCM_INIT', true);
require_once 'config/config.php';

// Check if viewing single blog
$slug = $_GET['slug'] ?? '';
$single_blog = null;

// Load blogs from JSON files
$blogs_dir = __DIR__ . '/data/blogs';
$blogs = [];

if (file_exists($blogs_dir) && is_dir($blogs_dir)) {
    $files = glob($blogs_dir . '/*.json');
    foreach ($files as $file) {
        if (basename($file) === 'index.json' || basename($file) === 'stats.json') continue;
        
        $blog_data = json_decode(file_get_contents($file), true);
        if ($blog_data && isset($blog_data['title'])) {
            // Generate slug from filename (may be the old broken slug)
            $blog_data['slug'] = str_replace('.json', '', basename($file));
            $blogs[] = $blog_data;
            
            // Match requested blog by filename slug OR by clean title-derived slug
            if (!empty($slug) && $single_blog === null) {
                $title_slug = strtolower($blog_data['title']);
                $title_slug = substr(trim(preg_replace('/[^a-z0-9]+/', '-', $title_slug), '-'), 0, 100);
                if ($blog_data['slug'] === $slug || $title_slug === $slug) {
                    $blog_data['slug'] = !empty($title_slug) ? $title_slug : $blog_data['slug'];
                    $single_blog = $blog_data;
                }
            }
        }
    }
}

// If no slug given, redirect to the canonical blog listing page
if (empty($slug)) {
    header('Location: blogs.php', true, 301);
    exit;
}

// Redirect legacy query URL to clean URL
$req_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
if (preg_match('~^/blog\.php$~i', $req_path)) {
    $clean = SITE_URL . '/blog/' . rawurlencode($slug);
    header('Location: ' . $clean, true, 301);
    exit;
}

// If slug given but no matching blog found, 404
if (!$single_blog) {
    http_response_code(404);
    $page_title = 'Blog Post Not Found | GCM Netting Solutions';
    $meta_description = 'The blog post you are looking for could not be found.';
    $meta_keywords = 'safety nets blog';
} else {
    $page_title = htmlspecialchars($single_blog['title']) . ' | GCM Netting Solutions Blog';
    $meta_description = htmlspecialchars(substr($single_blog['excerpt'] ?? '', 0, 160));
    $meta_keywords = 'safety nets blog, ' . htmlspecialchars($single_blog['category'] ?? '');
}

$current_page = 'blog';

// Sort by newest first
usort($blogs, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

include 'includes/modern-header.php';
?>

<style>
.blog-page {
    padding: 60px 0;
    background: #F8FAFC;
}

.blog-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 20px;
    text-align: center;
    border-radius: 20px;
    margin-bottom: 50px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.blog-hero h1 {
    font-size: 48px;
    font-weight: 800;
    margin-bottom: 15px;
}

.blog-hero p {
    font-size: 20px;
    opacity: 0.9;
}

.blog-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 30px;
    margin-bottom: 50px;
}

.blog-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    transition: all 0.3s;
}

.blog-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

.blog-image {
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
}

.blog-image i {
    font-size: 64px;
    color: white;
    opacity: 0.8;
}

.blog-content {
    padding: 25px;
}

.blog-title {
    font-size: 22px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 12px;
    line-height: 1.4;
}

.blog-meta {
    display: flex;
    gap: 15px;
    font-size: 14px;
    color: #64748B;
    margin-bottom: 15px;
}

.blog-excerpt {
    color: #475569;
    font-size: 15px;
    line-height: 1.6;
    margin-bottom: 20px;
}

.blog-read-more {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    background: linear-gradient(135deg, #3B82F6, #2563EB);
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.blog-read-more:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
}

.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: white;
    border-radius: 16px;
}

.empty-state i {
    font-size: 80px;
    color: #CBD5E1;
    margin-bottom: 20px;
}

@media (max-width: 768px) {
    .blog-hero h1 {
        font-size: 32px;
    }
    
    .blog-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="blog-page">
    <div class="container">
        <?php if ($single_blog): ?>
            <!-- Single Blog View -->
            <div style="max-width: 900px; margin: 0 auto;">
                <div style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 40px rgba(0,0,0,0.1);">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 50px 40px;">
                        <a href="blogs.php" style="color: white; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; margin-bottom: 20px; opacity: 0.9; font-size: 14px;">
                            <i class="fas fa-arrow-left"></i> Back to All Blogs
                        </a>
                        <h1 style="font-size: 36px; margin-bottom: 20px; line-height: 1.3;"><?php echo htmlspecialchars($single_blog['title']); ?></h1>
                        <div style="font-size: 14px; opacity: 0.9;">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($single_blog['author']); ?> | 
                            <i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($single_blog['created_at'])); ?>
                            <?php if (!empty($single_blog['category'])): ?>
                                | <i class="fas fa-tag"></i> <?php echo htmlspecialchars($single_blog['category']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div style="padding: 50px 40px; color: #1E293B; line-height: 1.8; font-size: 17px;">
                        <?php echo $single_blog['content']; ?>
                    </div>
                    <div style="padding: 40px; background: #F8FAFC; border-top: 1px solid #E2E8F0;">
                        <h3 style="text-align: center; color: #2D3748; margin-bottom: 20px;">Need Professional Safety Net Installation?</h3>
                        <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                            <a href="tel:+919912399224" style="padding: 15px 30px; background: linear-gradient(135deg, #3B82F6, #2563EB); color: white; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fas fa-phone"></i> Call: +91 99123 99224
                            </a>
                            <a href="https://wa.me/919912399224" style="padding: 15px 30px; background: #25D366; color: white; border-radius: 12px; text-decoration: none; font-weight: 600; display: inline-flex; align-items: center; gap: 8px;">
                                <i class="fab fa-whatsapp"></i> WhatsApp Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Blog Listing View -->
            <div class="blog-hero">
                <h1><i class="fas fa-blog"></i> Safety Nets Blog</h1>
                <p>Expert tips, guides, and articles about safety nets</p>
            </div>

            <!-- Blog Grid -->
            <?php if (!empty($blogs)): ?>
                <div class="blog-grid">
                    <?php foreach ($blogs as $blog): ?>
                        <article class="blog-card">
                            <div class="blog-image">
                                <i class="fas fa-newspaper"></i>
                            </div>
                            <div class="blog-content">
                                <h2 class="blog-title"><?php echo htmlspecialchars($blog['title']); ?></h2>
                                <div class="blog-meta">
                                    <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($blog['author']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($blog['created_at'])); ?></span>
                                </div>
                                <p class="blog-excerpt">
                                    <?php echo htmlspecialchars(substr($blog['excerpt'], 0, 150)); ?>...
                                </p>
                                <a href="blog.php?slug=<?php echo urlencode($blog['slug']); ?>" class="blog-read-more">
                                    Read More <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h2 style="color: #64748B; margin-bottom: 10px;">No Blog Posts Yet</h2>
                    <p style="color: #94A3B8;">Check back soon for helpful articles and guides!</p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Contact CTA -->
        <div style="text-align: center; margin-top: 60px; padding: 40px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 20px; color: white;">
            <h2 style="margin-bottom: 15px;">Need Safety Net Installation?</h2>
            <p style="margin-bottom: 25px; opacity: 0.9;">Contact our expert team for professional service</p>
            <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                <a href="tel:+919912399224" style="padding: 15px 30px; background: white; color: #667eea; border-radius: 12px; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-phone"></i> Call: +91 99123 99224
                </a>
                <a href="https://wa.me/919912399224" style="padding: 15px 30px; background: #25D366; color: white; border-radius: 12px; text-decoration: none; font-weight: 600;">
                    <i class="fab fa-whatsapp"></i> WhatsApp Us
                </a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/modern-footer.php'; ?>
