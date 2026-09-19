<?php
/**
 * GCM Netting Solutions - Gallery Page
 * Image gallery of completed projects
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

$page_title = 'Gallery - Our Work Showcase';
$meta_description = 'View our gallery of completed safety net, pigeon net, invisible grill, and sports net installation projects across Chennai. Quality work, satisfied customers.';
$meta_keywords = 'safety nets gallery, installation photos, completed projects, chennai';

// 6 gallery categories (maps slug → display label + keyword for LIKE matching)
$gallery_categories = [
    'pigeon-nets'      => ['label' => 'Pigeon Nets',     'keyword' => 'pigeon'],
    'bird-nets'        => ['label' => 'Bird Nets',       'keyword' => 'bird'],
    'safety-nets'      => ['label' => 'Safety Nets',     'keyword' => 'safety'],
    'cricket-nets'     => ['label' => 'Cricket Nets',    'keyword' => 'cricket'],
    'invisible-grills' => ['label' => 'Invisible Grills','keyword' => 'invisible'],
    'cloth-hangers'    => ['label' => 'Cloth Hangers',   'keyword' => 'cloth'],
];

// Get filter
$filter_service = isset($_GET['service']) ? sanitize_input($_GET['service']) : 'all';
if ($filter_service !== 'all' && !isset($gallery_categories[$filter_service])) {
    $filter_service = 'all'; // Sanitize unknown category slugs
}

// Fetch images
$db = Database::getInstance();
if ($filter_service === 'all') {
    $images = $db->fetchAll("
        SELECT g.*, s.service_name
        FROM gallery_images g
        LEFT JOIN services s ON g.service_id = s.id
        WHERE g.is_active = 1
        ORDER BY g.display_order ASC, g.created_at DESC
        LIMIT 50
    ");
} else {
    $kw = '%' . $gallery_categories[$filter_service]['keyword'] . '%';
    $images = $db->fetchAll("
        SELECT g.*, s.service_name
        FROM gallery_images g
        LEFT JOIN services s ON g.service_id = s.id
        WHERE g.is_active = 1 AND s.service_slug LIKE ?
        ORDER BY g.display_order ASC, g.created_at DESC
        LIMIT 50
    ", [$kw]);
}

$current_page = 'gallery';

include 'includes/modern-header.php';
?>

<div class="gallery-page">
    <!-- Page Header -->
    <div class="page-banner">
        <div class="container">
            <h1><i class="fas fa-images"></i> Our Gallery</h1>
            <p>Showcase of Our Completed Projects Across Chennai</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Filter Buttons -->
        <div class="gallery-filters">
            <button class="filter-btn <?php echo $filter_service === 'all' ? 'active' : ''; ?>" 
                    onclick="window.location.href='gallery.php?service=all'">
                <i class="fas fa-th"></i> All Projects
            </button>
            <?php foreach ($gallery_categories as $slug => $cat): ?>
                <button class="filter-btn <?php echo $filter_service === $slug ? 'active' : ''; ?>" 
                        onclick="window.location.href='gallery.php?service=<?php echo $slug; ?>'">
                    <?php echo htmlspecialchars($cat['label']); ?>
                </button>
            <?php endforeach; ?>
        </div>
        
        <!-- Gallery Grid -->
        <?php if (count($images) > 0): ?>
            <div class="gallery-grid">
                <?php foreach ($images as $image): ?>
                    <div class="gallery-item" onclick="openLightbox('<?php echo htmlspecialchars($image['image_url']); ?>', '<?php echo htmlspecialchars($image['title'] ?? 'Project Image'); ?>')">
                        <img src="<?php echo htmlspecialchars($image['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($image['alt_text'] ?? $image['title'] ?? 'Gallery Image'); ?>" 
                             loading="lazy">
                        <div class="gallery-overlay">
                            <div class="gallery-info">
                                <?php if ($image['title']): ?>
                                    <h4><?php echo htmlspecialchars($image['title']); ?></h4>
                                <?php endif; ?>
                                <?php if ($image['service_name']): ?>
                                    <p><i class="fas fa-tag"></i> <?php echo htmlspecialchars($image['service_name']); ?></p>
                                <?php endif; ?>
                                <?php if ($image['area_name']): ?>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($image['area_name']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="zoom-icon">
                                <i class="fas fa-search-plus"></i>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-images">
                <i class="fas fa-image"></i>
                <h3>No Images Available</h3>
                <p>We're currently updating our gallery. Please check back soon or view other service categories.</p>
                <a href="gallery.php?service=all" class="btn btn-primary">View All</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Lightbox Modal -->
<div id="lightbox" class="lightbox" onclick="closeLightbox()">
    <span class="close-lightbox">&times;</span>
    <div class="lightbox-content">
        <img id="lightbox-img" src="" alt="Gallery Image">
        <div id="lightbox-caption"></div>
    </div>
    <button class="lightbox-nav prev" onclick="event.stopPropagation(); navigateLightbox(-1)">
        <i class="fas fa-chevron-left"></i>
    </button>
    <button class="lightbox-nav next" onclick="event.stopPropagation(); navigateLightbox(1)">
        <i class="fas fa-chevron-right"></i>
    </button>
</div>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/pages.css">

<script>
let currentImageIndex = 0;
const images = <?php echo json_encode(array_map(function($img) {
    return [
        'src' => $img['image_path'],
        'title' => $img['title'] ?? 'Project Image'
    ];
}, $images)); ?>;

function openLightbox(src, title) {
    document.getElementById('lightbox').style.display = 'flex';
    document.getElementById('lightbox-img').src = src;
    document.getElementById('lightbox-caption').textContent = title;
    document.body.style.overflow = 'hidden';
    
    // Find current image index
    currentImageIndex = images.findIndex(img => img.src === src);
}

function closeLightbox() {
    document.getElementById('lightbox').style.display = 'none';
    document.body.style.overflow = '';
}

function navigateLightbox(direction) {
    currentImageIndex += direction;
    if (currentImageIndex < 0) currentImageIndex = images.length - 1;
    if (currentImageIndex >= images.length) currentImageIndex = 0;
    
    const img = images[currentImageIndex];
    document.getElementById('lightbox-img').src = img.src;
    document.getElementById('lightbox-caption').textContent = img.title;
}

// Keyboard navigation
document.addEventListener('keydown', function(e) {
    if (document.getElementById('lightbox').style.display === 'flex') {
        if (e.key === 'Escape') closeLightbox();
        if (e.key === 'ArrowLeft') navigateLightbox(-1);
        if (e.key === 'ArrowRight') navigateLightbox(1);
    }
});
</script>

<?php include 'includes/modern-footer.php'; ?>
