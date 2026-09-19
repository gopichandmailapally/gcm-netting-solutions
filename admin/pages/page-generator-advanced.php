<?php
/**
 * Advanced Page Generator
 * Generate pages with categories, multiple speed modes, and parallel processing
 */

if (!defined('ADMIN_ACCESS')) {
    header('Location: ../index.php');
    exit;
}

$db = Database::getInstance();

// Get statistics
$total_pages = $db->fetchOne("SELECT COUNT(*) as count FROM generated_pages");
$total_areas = $db->fetchOne("SELECT COUNT(DISTINCT area) as count FROM service_areas");

// ACTUAL Service categories with keywords from your website (6 categories)
$service_categories = [
    'SAFETY NETS' => [
        'safety nets',
        'balcony safety nets',
        'safety nets for balconies',
        'duct area safety nets',
        'monkey safety nets',
        'construction safety nets',
        'industrial safety nets',
        'fall safety nets',
        'fall protection nets',
        'children safety nets',
        'pet safety nets'
    ],
    'SPORTS NETS' => [
        'cricket nets',
        'cricket nets price',
        'cricket nets near me',
        'cricket practice net',
        'cricket practice nets',
        'cricket net price',
        'cricket indoor nets near me',
        'indoor cricket nets near me',
        'sports nets',
        'sports netting',
        'cricket netting',
        'box cricket net',
        'cricket net installation'
    ],
    'INVISIBLE GRILLS' => [
        'invisible grills',
        'invisible grill near me',
        'ss invisible grills',
        'invisible grill for balcony',
        'balcony invisible grill',
        'invisible grill for balcony near me',
        'invisible safety grill',
        'invisible grill for safety',
        'invisible grill for pigeons'
    ],
    'CLOTH HANGERS' => [
        'ceiling cloth hangers',
        'dry cloth hangers',
        'cloth drying hangers',
        'cloth hanger for balcony',
        'pulley cloth drying hanger',
        'pulley cloth hanger',
        'laundry hanger dryer',
        'clothes hanger to dry clothes',
        'clothes hanger drier'
    ],
    'PIGEON NETS' => [
        'pigeon net',
        'pigeon nets',
        'balcony netting',
        'pigeon net for balcony',
        'pigeon nets installation',
        'pigeon bird netting',
        'koilsuttur jail near me',
        'pigeon net installation',
        'pigeon net near me',
        'pigeon net for balcony near me',
        'pigeon net installation near me',
        'pigeon safety nets',
        'pigeon net price'
    ],
    'BIRD NETS' => [
        'bird nets',
        'bird net',
        'bird net for balcony',
        'bird net near me',
        'nets for birds',
        'net for birds',
        'industrial bird netting',
        'bird netting',
        'anti bird netting'
    ]
];
?>

<div class="page-generator-advanced">
    <!-- Header with actions -->
    <div class="page-header">
        <div class="header-left">
            <h1><i class="fas fa-layer-group"></i> Advanced Page Generator</h1>
            <p>Generate 188 area pages per keyword with 3 speed modes</p>
        </div>
        <div class="header-actions">
            <button class="btn btn-success" onclick="showGenerateAllModal()">
                <i class="fas fa-rocket"></i> Generate All Pages
            </button>
            <button class="btn btn-secondary" onclick="window.location.href='?page=view-generated-pages'">
                <i class="fas fa-list"></i> View Pages
            </button>
        </div>
    </div>
    
    <!-- Statistics -->
    <div class="stats-row">
        <div class="stat-box">
            <div class="stat-icon blue"><i class="fas fa-file-alt"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_pages['count']); ?></h3>
                <p>Total Pages Generated</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon green"><i class="fas fa-map-marker-alt"></i></div>
            <div class="stat-content">
                <h3><?php echo number_format($total_areas['count']); ?></h3>
                <p>Total Areas</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon purple"><i class="fas fa-tags"></i></div>
            <div class="stat-content">
                <h3><?php echo count($service_categories); ?></h3>
                <p>Service Categories</p>
            </div>
        </div>
        <div class="stat-box">
            <div class="stat-icon orange"><i class="fas fa-key"></i></div>
            <div class="stat-content">
                <h3><?php 
                    $total_keywords = 0;
                    foreach ($service_categories as $keywords) {
                        $total_keywords += count($keywords);
                    }
                    echo $total_keywords;
                ?></h3>
                <p>Service Keywords</p>
            </div>
        </div>
    </div>
    
    <!-- Service Categories (Collapsible) -->
    <div class="content-card">
        <div class="card-header">
            <h2><i class="fas fa-th-large"></i> Select Service & Keyword</h2>
            <button class="btn btn-sm btn-secondary" onclick="expandAllCategories()">
                <i class="fas fa-expand-alt"></i> Expand All
            </button>
        </div>
        <div class="card-body">
            <div class="categories-container">
                <?php foreach ($service_categories as $category => $keywords): ?>
                    <div class="category-section">
                        <div class="category-header" onclick="toggleCategory(this)">
                            <div class="category-title">
                                <i class="fas fa-chevron-right collapse-icon"></i>
                                <span><?php echo $category; ?></span>
                                <span class="keyword-count"><?php echo count($keywords); ?> keywords</span>
                            </div>
                            <button class="btn btn-sm btn-primary" onclick="event.stopPropagation(); generateCategoryPages('<?php echo addslashes($category); ?>')">
                                <i class="fas fa-magic"></i> Generate All
                            </button>
                        </div>
                        <div class="category-content">
                            <div class="keywords-grid">
                                <?php foreach ($keywords as $keyword): ?>
                                    <div class="keyword-card" onclick="openGenerationModal('<?php echo addslashes($keyword); ?>')">
                                        <div class="keyword-name">
                                            <i class="fas fa-tag"></i>
                                            <?php echo $keyword; ?>
                                        </div>
                                        <div class="keyword-actions">
                                            <span class="keyword-pages">188 pages</span>
                                            <button class="btn-generate">
                                                <i class="fas fa-play"></i> Generate
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Include modals and scripts from separate file -->
<?php include 'includes/page-generator-modals.php'; ?>
<?php include 'includes/page-generator-styles.php'; ?>
<?php include 'includes/page-generator-scripts.php'; ?>
