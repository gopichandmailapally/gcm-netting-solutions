<?php
/**
 * 4-Level Mega Menu Generator
 * Dynamically builds menu from database
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

function generate_mega_menu() {
    $db = Database::getInstance();
    
    // Fetch all services (Level 2)
    $services = $db->fetchAll("
        SELECT id, service_slug, service_name, icon_class 
        FROM services 
        WHERE is_active = 1 
        ORDER BY display_order ASC
    ");
    
    // Service slug → keyword category mapping (matches actual DB services)
    $svc_cat_map = [
        'pigeon-nets'          => 'PIGEON NETS',
        'balcony-safety-nets'  => 'SAFETY NETS',
        'invisible-grills'     => 'INVISIBLE GRILLS',
        'sports-nets'          => 'SPORTS NETS',
        'children-safety-nets' => 'SAFETY NETS',
        'cloth-hangers'        => 'CLOTH HANGERS',
        'bird-nets'            => 'BIRD NETS',
        'safety-nets'          => 'SAFETY NETS',
    ];

    // Fetch all keywords grouped by service (Level 3)
    $keywords = [];
    foreach ($services as $service) {
        $cat = $svc_cat_map[$service['service_slug']] ?? strtoupper($service['service_name']);
        $keywords[$service['id']] = $db->fetchAll("
            SELECT id, keyword_slug, keyword_name 
            FROM seo_service_keywords 
            WHERE category = ? AND is_active = 1 
            ORDER BY display_order ASC
        ", [$cat]);
    }
    
    // Fetch first 20 areas for dropdown (Level 4)
    $areas = $db->fetchAll("
        SELECT area_slug, area_name 
        FROM service_areas 
        WHERE is_active = 1 
        ORDER BY area_name ASC 
        LIMIT 20
    ");
    
    ?>
    <nav class="mega-menu-container">
        <div class="menu-wrapper">
            <button class="mobile-menu-toggle" aria-label="Toggle Menu">
                <span class="hamburger"></span>
                <span class="hamburger"></span>
                <span class="hamburger"></span>
            </button>
            
            <ul class="main-menu">
                <li><a href="<?php echo SITE_URL; ?>/">Home</a></li>
                <li><a href="<?php echo SITE_URL; ?>/about.php">About</a></li>
                
                <!-- Level 1: Services Dropdown -->
                <li class="has-megamenu">
                    <a href="#" class="dropdown-trigger">
                        Services <i class="fas fa-chevron-down"></i>
                    </a>
                    
                    <div class="megamenu-panel">
                        <div class="megamenu-container">
                            <?php foreach ($services as $service): ?>
                                <!-- Level 2: Main Service -->
                                <div class="megamenu-column">
                                    <div class="service-header">
                                        <i class="<?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                                        <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                                    </div>
                                    
                                    <?php if (isset($keywords[$service['id']]) && count($keywords[$service['id']]) > 0): ?>
                                        <ul class="keyword-list">
                                            <?php foreach ($keywords[$service['id']] as $keyword): ?>
                                                <!-- Level 3: Keywords -->
                                                <li class="has-submenu">
                                                    <a href="#" class="keyword-link">
                                                        <?php echo htmlspecialchars($keyword['keyword_name']); ?>
                                                        <i class="fas fa-angle-right"></i>
                                                    </a>
                                                    
                                                    <!-- Level 4: Area Pages -->
                                                    <div class="area-submenu">
                                                        <div class="submenu-header">
                                                            <h4><?php echo htmlspecialchars($keyword['keyword_name']); ?> in:</h4>
                                                        </div>
                                                        <ul class="area-list">
                                                            <?php foreach ($areas as $area): ?>
                                                                <li>
                                                                    <a href="<?php echo SITE_URL; ?>/service-pages/<?php echo $keyword['keyword_slug']; ?>-in-<?php echo $area['area_slug']; ?>.php">
                                                                        <?php echo htmlspecialchars($area['area_name']); ?>
                                                                    </a>
                                                                </li>
                                                            <?php endforeach; ?>
                                                            <li class="view-all">
                                                                <a href="<?php echo SITE_URL; ?>/all-areas.php?keyword=<?php echo $keyword['keyword_slug']; ?>">
                                                                    <strong>View All 150 Areas →</strong>
                                                                </a>
                                                            </li>
                                                        </ul>
                                                    </div>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>
                
                <li><a href="<?php echo SITE_URL; ?>/gallery.php">Gallery</a></li>
                <li><a href="<?php echo SITE_URL; ?>/videos.php">Videos</a></li>
                <li><a href="<?php echo SITE_URL; ?>/reviews.php">Reviews</a></li>
                <li><a href="<?php echo SITE_URL; ?>/blogs.php">Blogs</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
            </ul>
        </div>
    </nav>
    <?php
}
