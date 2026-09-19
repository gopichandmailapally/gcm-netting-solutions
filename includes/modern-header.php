<?php
/**
 * Modern Attractive Header with Mobile Menu
 * Responsive design with hamburger menu for mobile
 */
if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}
require_once __DIR__ . '/seo-engine.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google Search Console Verification -->
    <meta name="google-site-verification" content="z3sEDC2o67dDvLFf6Gv8c0oQd9sepeHfXF-rpNw_ZNA" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $meta_description ?? SITE_DESCRIPTION; ?>">
    <meta name="keywords" content="<?php echo $meta_keywords ?? 'safety nets chennai, pigeon nets, bird nets'; ?>">
    <meta name="author" content="<?php echo COMPANY_NAME; ?>">
    
    <!-- Title -->
    <title><?php $rt = $page_title ?? 'Home'; echo (strpos($rt, SITE_NAME) !== false) ? htmlspecialchars($rt) : htmlspecialchars($rt . ' | ' . SITE_NAME); ?></title>
    
    <!-- Favicon -->
    <?php $favicon_version = file_exists(__DIR__ . '/../uploads/favicon.png') ? '?v=' . filemtime(__DIR__ . '/../uploads/favicon.png') : ''; ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo SITE_URL; ?>/uploads/favicon.png<?php echo $favicon_version; ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo SITE_URL; ?>/uploads/favicon.png<?php echo $favicon_version; ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo SITE_URL; ?>/uploads/favicon.png<?php echo $favicon_version; ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- CSS Files (v= uses file mtime for automatic cache-busting on every upload) -->
    <?php $cssDir = __DIR__ . '/../assets/css/'; ?>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/pages.css?v=<?php echo filemtime($cssDir.'pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/slider.css?v=<?php echo filemtime($cssDir.'slider.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/homepage.css?v=<?php echo filemtime($cssDir.'homepage.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/modern-header.css?v=<?php echo filemtime($cssDir.'modern-header.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/modern-footer.css?v=<?php echo filemtime($cssDir.'modern-footer.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/enhanced-effects.css?v=<?php echo filemtime($cssDir.'enhanced-effects.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/service-pages.css?v=<?php echo filemtime($cssDir.'service-pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/responsive-mobile.css?v=<?php echo filemtime($cssDir.'responsive-mobile.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/mobile-fix-service-pages.css?v=<?php echo filemtime($cssDir.'mobile-fix-service-pages.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/custom-alerts.css?v=<?php echo filemtime($cssDir.'custom-alerts.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/success-popup.css?v=<?php echo filemtime($cssDir.'success-popup.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/gcm-fonts.css?v=<?php echo filemtime($cssDir.'gcm-fonts.css'); ?>">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/gcm-service-pages.css?v=<?php echo filemtime($cssDir.'gcm-service-pages.css'); ?>">
    
    <?php
    // Include additional CSS if specified
    if (isset($additional_css) && is_array($additional_css)) {
        foreach ($additional_css as $css_file) {
            echo '<link rel="stylesheet" href="' . SITE_URL . '/' . $css_file . '">' . "\n    ";
        }
    }
    ?>
    <?php gcm_render_seo_head(); ?>
</head>
<body style="margin:0;padding:0;">

<!-- Announcement/Marquee Bar -->
<div class="announcement-bar">
    <div class="announcement-container">
        <div class="announcement-content">
            <div class="marquee">
                <span class="highlight">✨ #1 Safety Nets in Chennai</span>
                <span>🏆 15+ Years Experience</span>
                <span>⭐ 10,000+ Happy Customers</span>
                <span>🔧 Professional Installation</span>
                <span>💯 100% Quality Guarantee</span>
                <span>📞 24/7 Customer Support</span>
                <span class="highlight">🎯 Free Inspection & Quote</span>
            </div>
        </div>
    </div>
</div>

<!-- Modern Header -->
<header class="modern-header">
    <div class="header-container">
        <!-- Logo -->
        <div class="header-logo">
            <a href="<?php echo SITE_URL; ?>">
                <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="<?php echo COMPANY_NAME; ?>" class="logo-img">
            </a>
        </div>

        <!-- Desktop Navigation -->
        <nav class="desktop-nav">
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>" class="nav-link <?php echo ($current_page == 'home') ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/about" class="nav-link <?php echo ($current_page == 'about') ? 'active' : ''; ?>">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                </li>
                
                <li class="nav-item has-dropdown has-3level">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-th-large"></i> Services <i class="fas fa-chevron-down"></i>
                    </a>
                    <ul class="dropdown-level1">
                        <?php
                        // EXACT 64 Service Keywords from Project
                        $service_categories = [
                            'PIGEON NETS' => [
                                'icon' => 'fas fa-dove',
                                'services' => [
                                    'Pigeon Nets' => 'pigeon-nets',
                                    'Pigeon Net' => 'pigeon-net',
                                    'Balcony Netting' => 'balcony-netting',
                                    'Pigeon Net For Balcony' => 'pigeon-net-for-balcony',
                                    'Pigeon Nets Installation' => 'pigeon-nets-installation',
                                    'Pigeon Bird Netting' => 'pigeon-bird-netting',
                                    'Pigeon Net Installation' => 'pigeon-net-installation',
                                    'Pigeon Net Near Me' => 'pigeon-net-near-me',
                                    'Pigeon Net For Balcony Near Me' => 'pigeon-net-for-balcony-near-me',
                                    'Pigeon Net Installation Near Me' => 'pigeon-net-installation-near-me',
                                    'Pigeon Safety Nets' => 'pigeon-safety-nets',
                                    'Pigeon Net Price' => 'pigeon-net-price',
                                    'Kabutar Jali Near Me' => 'kabutar-jali-near-me'
                                ]
                            ],
                            'BIRD NETS' => [
                                'icon' => 'fas fa-kiwi-bird',
                                'services' => [
                                    'Bird Nets' => 'bird-nets',
                                    'Bird Net' => 'bird-net',
                                    'Bird Net For Balcony' => 'bird-net-for-balcony',
                                    'Bird Net Near Me' => 'bird-net-near-me',
                                    'Nets For Birds' => 'nets-for-birds',
                                    'Net For Birds' => 'net-for-birds',
                                    'Industrial Bird Netting' => 'industrial-bird-netting',
                                    'Bird Netting' => 'bird-netting',
                                    'Anti Bird Netting' => 'anti-bird-netting'
                                ]
                            ],
                            'SAFETY NETS' => [
                                'icon' => 'fas fa-shield-alt',
                                'services' => [
                                    'Safety Nets' => 'safety-nets',
                                    'Balcony Safety Nets' => 'balcony-safety-nets',
                                    'Safety Nets For Balconies' => 'safety-nets-for-balconies',
                                    'Duct Area Safety Nets' => 'duct-area-safety-nets',
                                    'Monkey Safety Nets' => 'monkey-safety-nets',
                                    'Construction Safety Nets' => 'construction-safety-nets',
                                    'Industrial Safety Nets' => 'industrial-safety-nets',
                                    'Fall Safety Nets' => 'fall-safety-nets',
                                    'Fall Protection Nets' => 'fall-protection-nets',
                                    'Children Safety Nets' => 'children-safety-nets',
                                    'Pet Safety Nets' => 'pet-safety-nets'
                                ]
                            ],
                            'SPORTS NETS' => [
                                'icon' => 'fas fa-baseball-ball',
                                'services' => [
                                    'Cricket Nets' => 'cricket-nets',
                                    'Cricket Nets Price' => 'cricket-nets-price',
                                    'Cricket Nets Near Me' => 'cricket-nets-near-me',
                                    'Cricket Practice Net' => 'cricket-practice-net',
                                    'Cricket Practice Nets' => 'cricket-practice-nets',
                                    'Cricket Net Price' => 'cricket-net-price',
                                    'Cricket Indoor Nets Near Me' => 'cricket-indoor-nets-near-me',
                                    'Indoor Cricket Nets Near Me' => 'indoor-cricket-nets-near-me',
                                    'Sports Nets' => 'sports-nets',
                                    'Sports Netting' => 'sports-netting',
                                    'Cricket Netting' => 'cricket-netting',
                                    'Box Cricket Net' => 'box-cricket-net',
                                    'Cricket Net Installation' => 'cricket-net-installation'
                                ]
                            ],
                            'INVISIBLE GRILLS' => [
                                'icon' => 'fas fa-grip-lines-vertical',
                                'services' => [
                                    'Invisible Grills' => 'invisible-grills',
                                    'Invisible Grill Near Me' => 'invisible-grill-near-me',
                                    'SS Invisible Grills' => 'ss-invisible-grills',
                                    'Invisible Grill For Balcony' => 'invisible-grill-for-balcony',
                                    'Balcony Invisible Grill' => 'balcony-invisible-grill',
                                    'Invisible Grill For Balcony Near Me' => 'invisible-grill-for-balcony-near-me',
                                    'Invisible Safety Grill' => 'invisible-safety-grill',
                                    'Invisible Grill For Safety' => 'invisible-grill-for-safety',
                                    'Invisible Grill For Pigeons' => 'invisible-grill-for-pigeons'
                                ]
                            ],
                            'CLOTH HANGERS' => [
                                'icon' => 'fas fa-tshirt',
                                'services' => [
                                    'Ceiling Cloth Hangers' => 'ceiling-cloth-hangers',
                                    'Dry Cloth Hangers' => 'dry-cloth-hangers',
                                    'Cloth Drying Hangers' => 'cloth-drying-hangers',
                                    'Cloth Hanger For Balcony' => 'cloth-hanger-for-balcony',
                                    'Pulley Cloth Drying Hanger' => 'pulley-cloth-drying-hanger',
                                    'Pulley Cloth Hanger' => 'pulley-cloth-hanger',
                                    'Laundry Hanger Dryer' => 'laundry-hanger-dryer',
                                    'Clothes Hanger To Dry Clothes' => 'clothes-hanger-to-dry-clothes',
                                    'Clothes Hanger Drier' => 'clothes-hanger-drier'
                                ]
                            ]
                        ];
                        
                        // Top 10 Major Chennai Hubs for Menu
                        $top_key_areas = [
                            'T Nagar' => 't-nagar',
                            'Anna Nagar' => 'anna-nagar',
                            'Velachery' => 'velachery',
                            'Adyar' => 'adyar',
                            'OMR' => 'omr',
                            'Tambaram' => 'tambaram',
                            'Porur' => 'porur',
                            'Mylapore' => 'mylapore',
                            'Guindy' => 'guindy',
                            'Sholinganallur' => 'sholinganallur'
                        ];
                        
                        foreach ($service_categories as $category => $cat_data):
                        ?>
                        <li class="has-sublevel">
                            <a href="javascript:void(0)">
                                <i class="<?php echo $cat_data['icon']; ?>"></i> <?php echo $category; ?>
                                <i class="fas fa-chevron-right arrow-right"></i>
                            </a>
                            <ul class="dropdown-level2">
                                <?php foreach ($cat_data['services'] as $service_name => $service_slug): ?>
                                <li class="has-areas">
                                    <a href="<?php echo SITE_URL; ?>/<?php echo $service_slug; ?>">
                                        <?php echo $service_name; ?>
                                        <i class="fas fa-chevron-right arrow-right"></i>
                                    </a>
                                    <ul class="dropdown-level3">
                                        <?php foreach ($top_key_areas as $area_title => $area_slug_part): ?>
                                        <li><a href="<?php echo SITE_URL; ?>/<?php echo $service_slug; ?>-in-<?php echo $area_slug_part; ?>"><?php echo $area_title; ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/estimation" class="nav-link <?php echo ($current_page == 'estimation') ? 'active' : ''; ?>">
                        <i class="fas fa-calculator"></i> Estimation
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/gallery" class="nav-link <?php echo ($current_page == 'gallery') ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> Gallery
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/videos" class="nav-link <?php echo ($current_page == 'videos') ? 'active' : ''; ?>">
                        <i class="fas fa-play-circle"></i> Videos
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/reviews" class="nav-link <?php echo ($current_page == 'reviews') ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/blogs" class="nav-link <?php echo ($current_page == 'blogs') ? 'active' : ''; ?>">
                        <i class="fas fa-blog"></i> Blogs
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/faqs" class="nav-link <?php echo ($current_page == 'faqs') ? 'active' : ''; ?>">
                        <i class="fas fa-question-circle"></i> FAQ's
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/contact" class="nav-link <?php echo ($current_page == 'contact') ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">
            <a href="tel:+919912399224" class="btn-call">
                <i class="fas fa-phone-alt"></i>
                <span class="call-text">
                    <small>Call Us</small>
                    <strong>+91 99123 99224</strong>
                </span>
            </a>
            <a href="<?php echo SITE_URL; ?>/estimation" class="btn-quote">
                <i class="fas fa-calculator"></i> Get Estimate Here
            </a>
        </div>

        <!-- Mobile Menu Toggle -->
        <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
            <span class="hamburger"></span>
            <span class="hamburger"></span>
            <span class="hamburger"></span>
        </button>
    </div>
</header>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-menu-header">
        <div class="mobile-logo">
            <img src="<?php echo SITE_URL; ?>/uploads/logo.png" alt="<?php echo COMPANY_NAME; ?>">
        </div>
        <button class="mobile-menu-close" onclick="closeMobileMenu()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <nav class="mobile-nav">
        <ul class="mobile-nav-menu">
            <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="<?php echo SITE_URL; ?>/about"><i class="fas fa-info-circle"></i> About</a></li>
            
            <li class="has-submenu">
                <a href="#" onclick="toggleSubmenu(event, this)">
                    <i class="fas fa-th-large"></i> Services 
                    <i class="fas fa-chevron-right submenu-icon"></i>
                </a>
                <ul class="submenu level-1">
                    <?php foreach ($service_categories as $category => $cat_data): ?>
                        <li class="has-submenu-l2">
                            <a href="#" onclick="toggleSubmenu(event, this)">
                                <i class="<?php echo $cat_data['icon']; ?>"></i> <?php echo $category; ?>
                                <i class="fas fa-chevron-right submenu-icon"></i>
                            </a>
                            <ul class="submenu level-2">
                                <?php foreach ($cat_data['services'] as $service_name => $service_slug): ?>
                                    <li class="has-submenu-l3">
                                        <a href="#" onclick="toggleSubmenu(event, this)">
                                            <?php echo $service_name; ?>
                                            <i class="fas fa-chevron-right submenu-icon"></i>
                                        </a>
                                        <ul class="submenu level-3">
                                            <li><a href="<?php echo SITE_URL; ?>/<?php echo $service_slug; ?>" style="font-weight:700;color:#10B981;">&rarr; View All Chennai</a></li>
                                            <?php foreach ($top_key_areas as $area_title => $area_slug_part): ?>
                                                <li><a href="<?php echo SITE_URL; ?>/<?php echo $service_slug; ?>-in-<?php echo $area_slug_part; ?>"><?php echo $area_title; ?></a></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </li>
            
            <li><a href="<?php echo SITE_URL; ?>/estimation"><i class="fas fa-calculator"></i> Estimation</a></li>
            <li><a href="<?php echo SITE_URL; ?>/gallery"><i class="fas fa-images"></i> Gallery</a></li>
            <li><a href="<?php echo SITE_URL; ?>/videos"><i class="fas fa-play-circle"></i> Videos</a></li>
            <li><a href="<?php echo SITE_URL; ?>/reviews"><i class="fas fa-star"></i> Reviews</a></li>
            <li><a href="<?php echo SITE_URL; ?>/blogs"><i class="fas fa-blog"></i> Blogs</a></li>
            <li><a href="<?php echo SITE_URL; ?>/faqs"><i class="fas fa-question-circle"></i> FAQ's</a></li>
            <li><a href="<?php echo SITE_URL; ?>/contact"><i class="fas fa-envelope"></i> Contact</a></li>
        </ul>
    </nav>
    
    <div class="mobile-menu-footer">
        <a href="tel:+919912399224" class="mobile-call-btn">
            <i class="fas fa-phone-alt"></i> Call: +91 99123 99224
        </a>
        <a href="<?php echo SITE_URL; ?>/estimation" class="mobile-quote-btn">
            <i class="fas fa-calculator"></i> Get Estimate Here
        </a>
    </div>
</div>

<script>
function toggleMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const body = document.body;
    
    menu.classList.toggle('active');
    overlay.classList.toggle('active');
    body.classList.toggle('menu-open');
}

function closeMobileMenu() {
    const menu = document.getElementById('mobileMenu');
    const overlay = document.getElementById('mobileMenuOverlay');
    const body = document.body;
    
    menu.classList.remove('active');
    overlay.classList.remove('active');
    body.classList.remove('menu-open');
}

function toggleSubmenu(event, element) {
    event.preventDefault();
    event.stopPropagation();
    
    const parent = element.parentElement;
    const wasActive = parent.classList.contains('active');
    
    // Close all sibling submenus at the same level
    const siblings = parent.parentElement.querySelectorAll(':scope > li.has-submenu, :scope > li.has-submenu-l2, :scope > li.has-submenu-l3');
    siblings.forEach(item => {
        if (item !== parent) {
            item.classList.remove('active');
        }
    });
    
    // Toggle this submenu
    if (!wasActive) {
        parent.classList.add('active');
    }
}

function openQuoteModal() {
    // Implement quote modal
    GCMAlert.info('Quote form will open here', 'Get Quote');
}
</script>

<!-- Custom Alerts JS -->
<script src="<?php echo SITE_URL; ?>/assets/js/custom-alerts.js"></script>
