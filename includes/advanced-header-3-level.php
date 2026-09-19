<?php
/**
 * Advanced 3-Level Header with Mega Dropdown
 * Level 1: Categories (no links)
 * Level 2: Services (no links)
 * Level 3: Areas (actual page links)
 */
if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

// Service Categories Structure
$service_structure = [
    'PIGEON NETS' => [
        'icon' => 'fas fa-dove',
        'services' => [
            'Pigeon Nets' => ['pigeon-nets', 'pigeon-net'],
            'Balcony Netting' => ['balcony-netting'],
            'Pigeon Net For Balcony' => ['pigeon-net-for-balcony', 'pigeon-net-for-balcony-near-me'],
            'Pigeon Nets Installation' => ['pigeon-nets-installation', 'pigeon-net-installation', 'pigeon-net-installation-near-me'],
            'Pigeon Bird Netting' => ['pigeon-bird-netting'],
            'Pigeon Safety Nets' => ['pigeon-safety-nets'],
            'Pigeon Net Price' => ['pigeon-net-price'],
            'Kabutar Jali Near Me' => ['kabutar-jali-near-me']
        ]
    ],
    'BIRD NETS' => [
        'icon' => 'fas fa-kiwi-bird',
        'services' => [
            'Bird Nets' => ['bird-nets', 'bird-net'],
            'Bird Net For Balcony' => ['bird-net-for-balcony'],
            'Bird Net Near Me' => ['bird-net-near-me'],
            'Bird Netting' => ['bird-netting', 'nets-for-birds', 'net-for-birds'],
            'Industrial Bird Netting' => ['industrial-bird-netting'],
            'Anti Bird Netting' => ['anti-bird-netting']
        ]
    ],
    'SAFETY NETS' => [
        'icon' => 'fas fa-shield-alt',
        'services' => [
            'Safety Nets' => ['safety-nets'],
            'Balcony Safety Nets' => ['balcony-safety-nets', 'safety-nets-for-balconies'],
            'Construction Safety Nets' => ['construction-safety-nets'],
            'Industrial Safety Nets' => ['industrial-safety-nets'],
            'Children Safety Nets' => ['children-safety-nets'],
            'Pet Safety Nets' => ['pet-safety-nets'],
            'Duct Area Safety Nets' => ['duct-area-safety-nets'],
            'Monkey Safety Nets' => ['monkey-safety-nets'],
            'Fall Safety Nets' => ['fall-safety-nets', 'fall-protection-nets']
        ]
    ],
    'SPORTS NETS' => [
        'icon' => 'fas fa-baseball-ball',
        'services' => [
            'Cricket Nets' => ['cricket-nets', 'cricket-nets-near-me'],
            'Cricket Practice Nets' => ['cricket-practice-nets', 'cricket-practice-net'],
            'Cricket Net Installation' => ['cricket-net-installation'],
            'Cricket Nets Price' => ['cricket-nets-price', 'cricket-net-price'],
            'Indoor Cricket Nets' => ['cricket-indoor-nets-near-me', 'indoor-cricket-nets-near-me'],
            'Sports Nets' => ['sports-nets', 'sports-netting'],
            'Cricket Netting' => ['cricket-netting'],
            'Box Cricket Net' => ['box-cricket-net']
        ]
    ],
    'INVISIBLE GRILLS' => [
        'icon' => 'fas fa-grip-lines-vertical',
        'services' => [
            'Invisible Grills' => ['invisible-grills', 'ss-invisible-grills'],
            'Invisible Grill Near Me' => ['invisible-grill-near-me'],
            'Balcony Invisible Grill' => ['invisible-grill-for-balcony', 'balcony-invisible-grill', 'invisible-grill-for-balcony-near-me'],
            'Invisible Safety Grill' => ['invisible-safety-grill', 'invisible-grill-for-safety'],
            'Invisible Grill For Pigeons' => ['invisible-grill-for-pigeons']
        ]
    ],
    'CLOTH HANGERS' => [
        'icon' => 'fas fa-tshirt',
        'services' => [
            'Ceiling Cloth Hangers' => ['ceiling-cloth-hangers'],
            'Cloth Drying Hangers' => ['dry-cloth-hangers', 'cloth-drying-hangers'],
            'Balcony Cloth Hanger' => ['cloth-hanger-for-balcony'],
            'Pulley Cloth Hanger' => ['pulley-cloth-drying-hanger', 'pulley-cloth-hanger'],
            'Laundry Hanger Dryer' => ['laundry-hanger-dryer', 'clothes-hanger-to-dry-clothes', 'clothes-hanger-drier']
        ]
    ]
];

// Top 150 Service Areas in Chennai (for Level 3)
$service_areas = [
    'Abids', 'Adikmet', 'Afzalgunj', 'Aliwal', 'Amberpet', 'Ameerpet', 'Ananthagiri Hills', 'Asif Nagar',
    'Asifabad', 'Attapur', 'Attapur Metro', 'Bagh Lingampally', 'Bagh Amberpet', 'Bahadurpura', 'Balkampet',
    'Balnagar', 'Bandlaguda', 'Banjara Hills', 'Barkas', 'Basheerbagh', 'Begum Bazar', 'Begumpet', 'Borabanda',
    'Champapet', 'Chandanagar', 'Charminar', 'Chikkadpally', 'Chintal', 'Chintalkunta', 'Dabeerpura', 
    'Dilsukhnagar', 'Domalguda', 'Ecil', 'Erragadda', 'Falaknuma', 'Gachibowli', 'Gaddiannaram', 'Gandhi Nagar',
    'Golconda', 'Goshamahal', 'Gudimelakunta', 'Habsiguda', 'Hafeezpet', 'Hayathnagar', 'Himayatnagar', 
    'Hussainialam', 'Hyderguda', 'Jeedimetla', 'Jubilee Hills', 'Kachiguda', 'Kailash Nagar', 'Kalimandir',
    'Kamala Nagar', 'Kapra', 'Karkhana', 'Karwan', 'Kattedan', 'Khairtabad', 'Khajaguda', 'Kishanbagh',
    'Kismatkhan Gudda', 'Kompally', 'Kondapur', 'Kothapet', 'Kukatpally', 'LB Nagar', 'Langar Houz',
    'Lingampally', 'Madhapur', 'Madinaguda', 'Mahdipatnam', 'Malakpet', 'Mallapur', 'Marredpally', 
    'Masab Tank', 'Manikonda', 'Mehdipatnam', 'Mettuguda', 'Miyapur', 'Moghalpura', 'Moosarambagh', 
    'Moti Nagar', 'Moula Ali', 'Musheerabad', 'Nacharam', 'Nagaram', 'Nagole', 'Nallakunta', 'Nanakramguda',
    'Narayanguda', 'Nizampet', 'Old City', 'Osmania University', 'Padmarao Nagar', 'Panjagutta', 'Paradise',
    'Patelguda', 'Patny', 'Peerzadiguda', 'PJR Nagar', 'Pragathi Nagar', 'Puppalaguda', 'Quthbullapur',
    'Rajendranagar', 'Ramanthapur', 'Ramgopalpet', 'Ramnagar', 'Ramnagar Extension', 'Red Hills', 
    'Safilguda', 'Saidabad', 'Sainikpuri', 'Sanath Nagar', 'Sanghi Nagar', 'Santosh Nagar', 'Saroor Nagar',
    'Tambaram', 'Serilingampally', 'Shadnagar', 'Shah Ali Banda', 'Shaikpet', 'Shamshabad', 
    'Shivam Road', 'Somajiguda', 'SR Nagar', 'Sultan Bazar', 'Suncity', 'Tarnaka', 'Toli Chowki',
    'Tolichowki', 'Trimulgherry', 'Tukkuguda', 'Turkayamjal', 'Uppal', 'Vanasthalipuram', 'Vidyanagar',
    'Vikrampuri', 'Vijayanagar Colony', 'West Marredpally', 'Yapral', 'Yousufguda', 'Zaheerabad'
];
?>
<!-- Favicon -->
<link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/uploads/favicon.png">
<link rel="shortcut icon" type="image/png" href="<?php echo SITE_URL; ?>/uploads/favicon.png">

<!-- Announcement/Marquee Bar -->
<div class="announcement-bar">
    <div class="announcement-container">
        <div class="announcement-content">
            <span class="announcement-icon"><i class="fas fa-star"></i></span>
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

<!-- Advanced 3-Level Header -->
<header class="advanced-header">
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
                
                <li class="nav-item has-mega-dropdown">
                    <a href="javascript:void(0)" class="nav-link">
                        <i class="fas fa-th-large"></i> Services <i class="fas fa-chevron-down"></i>
                    </a>
                    <div class="mega-dropdown-3level">
                        <div class="mega-grid">
                            <?php foreach ($service_structure as $category => $data): ?>
                            <div class="mega-column">
                                <div class="category-header">
                                    <i class="<?php echo $data['icon']; ?>"></i>
                                    <h4><?php echo $category; ?></h4>
                                </div>
                                <ul class="service-list">
                                    <?php foreach ($data['services'] as $service_name => $slugs): ?>
                                    <li class="has-area-dropdown">
                                        <span class="service-item">
                                            <?php echo $service_name; ?>
                                            <i class="fas fa-chevron-right"></i>
                                        </span>
                                        <div class="area-dropdown">
                                            <div class="area-header">
                                                <strong><?php echo $service_name; ?></strong>
                                                <span class="area-count"><?php echo count($service_areas); ?> Areas</span>
                                            </div>
                                            <div class="area-list">
                                                <?php foreach ($service_areas as $area): ?>
                                                <a href="<?php echo SITE_URL; ?>/<?php echo $slugs[0]; ?>-in-<?php echo strtolower(str_replace(' ', '-', $area)); ?>.php" class="area-link">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <?php echo $area; ?>
                                                </a>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/gallery.php" class="nav-link <?php echo ($current_page == 'gallery') ? 'active' : ''; ?>">
                        <i class="fas fa-images"></i> Gallery
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/reviews.php" class="nav-link <?php echo ($current_page == 'reviews') ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/blog.php" class="nav-link <?php echo ($current_page == 'blog') ? 'active' : ''; ?>">
                        <i class="fas fa-blog"></i> Blog
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/about.php" class="nav-link <?php echo ($current_page == 'about') ? 'active' : ''; ?>">
                        <i class="fas fa-info-circle"></i> About
                    </a>
                </li>
                
                <li class="nav-item">
                    <a href="<?php echo SITE_URL; ?>/contact.php" class="nav-link <?php echo ($current_page == 'contact') ? 'active' : ''; ?>">
                        <i class="fas fa-envelope"></i> Contact
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Header Actions -->
        <div class="header-actions">
            <a href="tel:+919912399224" class="btn btn-call">
                <i class="fas fa-phone-alt"></i> Call Now
            </a>
            <a href="<?php echo SITE_URL; ?>/estimation.php" class="btn btn-quote">
                <i class="fas fa-calculator"></i> Get Estimate Here
            </a>
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="openMobileMenu()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Menu Overlay -->
<div class="mobile-menu-overlay" id="mobileMenuOverlay" onclick="closeMobileMenu()"></div>

<!-- Advanced Mobile Menu (3-Level Accordion) -->
<div class="advanced-mobile-menu" id="advancedMobileMenu">
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
            
            <!-- Services Accordion -->
            <li class="mobile-has-children">
                <button class="mobile-accordion-btn" onclick="toggleMobileAccordion(this, 'services')">
                    <span><i class="fas fa-th-large"></i> Services</span>
                    <i class="fas fa-chevron-down"></i>
                </button>
                <div class="mobile-accordion-content" id="services-content">
                    <?php foreach ($service_structure as $category => $data): ?>
                    <div class="mobile-category">
                        <button class="mobile-category-btn" onclick="toggleMobileAccordion(this, 'category-<?php echo sanitize_slug($category); ?>')">
                            <span><i class="<?php echo $data['icon']; ?>"></i> <?php echo $category; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="mobile-category-content" id="category-<?php echo sanitize_slug($category); ?>-content">
                            <?php foreach ($data['services'] as $service_name => $slugs): ?>
                            <div class="mobile-service">
                                <button class="mobile-service-btn" onclick="toggleMobileAccordion(this, 'service-<?php echo sanitize_slug($service_name); ?>')">
                                    <span><?php echo $service_name; ?></span>
                                    <i class="fas fa-chevron-down"></i>
                                </button>
                                <div class="mobile-service-content" id="service-<?php echo sanitize_slug($service_name); ?>-content">
                                    <?php foreach ($service_areas as $area): ?>
                                    <a href="<?php echo SITE_URL; ?>/<?php echo $slugs[0]; ?>-in-<?php echo strtolower(str_replace(' ', '-', $area)); ?>.php" class="mobile-area-link">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo $area; ?>
                                    </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </li>
            
            <li><a href="<?php echo SITE_URL; ?>/gallery.php"><i class="fas fa-images"></i> Gallery</a></li>
            <li><a href="<?php echo SITE_URL; ?>/reviews.php"><i class="fas fa-star"></i> Reviews</a></li>
            <li><a href="<?php echo SITE_URL; ?>/blog.php"><i class="fas fa-blog"></i> Blog</a></li>
            <li><a href="<?php echo SITE_URL; ?>/about.php"><i class="fas fa-info-circle"></i> About</a></li>
            <li><a href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-envelope"></i> Contact</a></li>
        </ul>
        
        <div class="mobile-menu-footer">
            <a href="tel:+919912399224" class="btn btn-mobile-call">
                <i class="fas fa-phone-alt"></i> Call: +91 99123 99224
            </a>
            <a href="<?php echo SITE_URL; ?>/quote.php" class="btn btn-mobile-quote">
                <i class="fas fa-file-invoice"></i> Get Free Quote
            </a>
        </div>
    </nav>
</div>

<?php
// Helper function
function sanitize_slug($text) {
    return strtolower(str_replace([' ', '&'], ['-', 'and'], $text));
}
?>

<script>
// Open Mobile Menu
function openMobileMenu() {
    document.getElementById('advancedMobileMenu').classList.add('active');
    document.getElementById('mobileMenuOverlay').classList.add('active');
    document.body.classList.add('menu-open');
}

// Close Mobile Menu
function closeMobileMenu() {
    document.getElementById('advancedMobileMenu').classList.remove('active');
    document.getElementById('mobileMenuOverlay').classList.remove('active');
    document.body.classList.remove('menu-open');
}

// Toggle Mobile Accordion
function toggleMobileAccordion(button, contentId) {
    const content = document.getElementById(contentId + '-content');
    const parent = button.parentElement;
    const allAccordions = document.querySelectorAll('.mobile-accordion-content, .mobile-category-content, .mobile-service-content');
    const allButtons = document.querySelectorAll('.mobile-accordion-btn, .mobile-category-btn, .mobile-service-btn');
    
    // Close all other accordions at the same level
    const parentLevel = parent.parentElement;
    parentLevel.querySelectorAll(':scope > .mobile-has-children, :scope > .mobile-category, :scope > .mobile-service').forEach(item => {
        if (item !== parent) {
            item.classList.remove('active');
            const itemContent = item.querySelector('.mobile-accordion-content, .mobile-category-content, .mobile-service-content');
            if (itemContent) {
                itemContent.style.maxHeight = '0';
            }
        }
    });
    
    // Toggle current
    parent.classList.toggle('active');
    
    if (parent.classList.contains('active')) {
        content.style.maxHeight = content.scrollHeight + 'px';
        
        // Expand parent accordions if nested
        let parentAccordion = parent.closest('.mobile-accordion-content, .mobile-category-content, .mobile-service-content');
        while (parentAccordion) {
            parentAccordion.style.maxHeight = parentAccordion.scrollHeight + content.scrollHeight + 'px';
            parentAccordion = parentAccordion.closest('.mobile-accordion-content, .mobile-category-content, .mobile-service-content');
        }
    } else {
        content.style.maxHeight = '0';
    }
}

// Close menu on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMobileMenu();
    }
});
</script>
