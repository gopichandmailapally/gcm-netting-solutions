<?php
if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

// Detect current location and set paths accordingly
$inPagesFolder = (strpos($_SERVER['PHP_SELF'], '/admin/pages/') !== false);
$inBillingFolder = (strpos($_SERVER['PHP_SELF'], '/admin/billing/') !== false);
$inSubfolder = $inPagesFolder || $inBillingFolder;

$adminPath = $inSubfolder ? '../' : '';
$pagesPath = $inSubfolder ? '../pages/' : 'pages/';
$billingPath = $inSubfolder ? ($inBillingFolder ? '' : '../billing/') : 'billing/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Admin Panel'; ?> | GCM Netting Solutions</title>
    
    <!-- Favicon -->
    <?php 
    $favicon_path = dirname(dirname(__DIR__)) . '/uploads/favicon.png';
    $favicon_version = file_exists($favicon_path) ? '?v=' : '';
    $baseUrl = $inSubfolder ? '../../' : '../';
    ?>
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $baseUrl; ?>uploads/favicon.png<?php echo $favicon_version; ?>">
    <link rel="shortcut icon" type="image/png" href="<?php echo $baseUrl; ?>uploads/favicon.png<?php echo $favicon_version; ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $baseUrl; ?>uploads/favicon.png<?php echo $favicon_version; ?>">
    
    <!-- Stylesheets - NEW PROFESSIONAL THEME -->
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin-professional-theme.css">
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin-mobile-responsive.css">
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>assets/css/custom-alerts.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom Alerts JavaScript -->
    <script src="<?php echo $baseUrl; ?>assets/js/custom-alerts.js"></script>
    
    <!-- Additional CSS if needed -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body class="admin-body">
    <!-- Sidebar with Collapsible Categories -->
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>GCM Admin</span>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        
        <nav class="sidebar-nav">
            <ul>
                <!-- Dashboard -->
                <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $adminPath; ?>dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                
                <!-- AI CONTENT GENERATION -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-robot"></i> AI Content Generation</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'pillar-page-generator.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $pagesPath; ?>pillar-page-generator.php">
                                <i class="fas fa-magic"></i>
                                <span>AI Page Generator</span>
                            </a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'generate-pages.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $pagesPath; ?>generate-pages.php">
                                <i class="fas fa-cogs"></i>
                                <span>Service Page Generator</span>
                            </a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'view-generated-pages.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $pagesPath; ?>view-generated-pages.php">
                                <i class="fas fa-file-alt"></i>
                                <span>View Generated Pages</span>
                            </a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'content-generator.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $pagesPath; ?>content-generator.php">
                                <i class="fas fa-pen-fancy"></i>
                                <span>Blog Generator</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>review-generator.php">
                                <i class="fas fa-star"></i>
                                <span>Review Generator</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>faq-generator.php">
                                <i class="fas fa-question-circle"></i>
                                <span>FAQ Generator</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- CONTENT MANAGEMENT -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-folder-open"></i> Content Management</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $pagesPath; ?>manage-blogs.php">
                                <i class="fas fa-blog"></i>
                                <span>Manage Blogs</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>manage-reviews.php">
                                <i class="fas fa-tasks"></i>
                                <span>Manage Reviews</span>
                            </a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'manage-faqs.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $pagesPath; ?>manage-faqs.php">
                                <i class="fas fa-question-circle"></i>
                                <span>Manage FAQs</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>auto-blog-dashboard.php">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Auto Blog Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- WEBSITE MANAGEMENT -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-globe"></i> Website Management</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $pagesPath; ?>hero-slider.php">
                                <i class="fas fa-sliders-h"></i>
                                <span>Hero Slider</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>gallery-management.php">
                                <i class="fas fa-th"></i>
                                <span>Gallery</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>about-page.php">
                                <i class="fas fa-info-circle"></i>
                                <span>About Page</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>logo-management.php">
                                <i class="fas fa-image"></i>
                                <span>Logo Management</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>rate-management.php">
                                <i class="fas fa-rupee-sign"></i>
                                <span>Rate Management</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>service-highlights.php">
                                <i class="fas fa-star"></i>
                                <span>Service Highlights</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>homepage-images.php">
                                <i class="fas fa-images"></i>
                                <span>Homepage Images</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- SEO & MARKETING -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-chart-line"></i> SEO & Marketing</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $pagesPath; ?>complete-seo-system.php">
                                <i class="fas fa-rocket"></i>
                                <span>Complete SEO System</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>auto-seo-optimizer-dashboard.php">
                                <i class="fas fa-magic"></i>
                                <span>Auto SEO Optimizer</span>
                            </a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'seo-dashboard.php' ? 'active' : ''; ?>">
                            <a href="<?php echo $pagesPath; ?>seo-dashboard.php">
                                <i class="fas fa-search"></i>
                                <span>SEO Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $adminPath; ?>dashboard.php#sitemap">
                                <i class="fas fa-sitemap"></i>
                                <span>Generate Sitemap</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- ANALYTICS & TRACKING -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-chart-bar"></i> Analytics & Tracking</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $pagesPath; ?>visitor-analytics.php">
                                <i class="fas fa-chart-line"></i>
                                <span>Visitor Analytics</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>live-visitors.php">
                                <i class="fas fa-users"></i>
                                <span>Live Visitors</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>advanced-analytics.php">
                                <i class="fas fa-chart-pie"></i>
                                <span>Advanced Analytics</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- BILLING SYSTEM -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-file-invoice-dollar"></i> Billing System</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $billingPath; ?>index.php">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Billing Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>create-invoice-gst.php">
                                <i class="fas fa-file-invoice"></i>
                                <span>Create Invoice (GST)</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>create-invoice-no-gst.php">
                                <i class="fas fa-file-alt"></i>
                                <span>Create Invoice (No GST)</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>create-estimation-gst.php">
                                <i class="fas fa-calculator"></i>
                                <span>Create Estimation (GST)</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>create-estimation-no-gst.php">
                                <i class="fas fa-receipt"></i>
                                <span>Create Estimation (No GST)</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>create-warranty.php">
                                <i class="fas fa-certificate"></i>
                                <span>Create Warranty Card</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>create-cash-bill.php">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>Create Cash Bill</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>invoices-list.php">
                                <i class="fas fa-list-alt"></i>
                                <span>All Documents</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>products.php">
                                <i class="fas fa-box"></i>
                                <span>Products & GST</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>bank-details.php">
                                <i class="fas fa-university"></i>
                                <span>Bank Details</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $billingPath; ?>company-settings.php">
                                <i class="fas fa-building"></i>
                                <span>Company Settings</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- CUSTOMER MANAGEMENT -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-users"></i> Customer Management</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $pagesPath; ?>contact-forms.php">
                                <i class="fas fa-envelope"></i>
                                <span>Contact Forms</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>offers-management.php">
                                <i class="fas fa-tags"></i>
                                <span>Offers Management</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- SECURITY -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-shield-alt"></i> Security</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $pagesPath; ?>security-status.php">
                                <i class="fas fa-shield-alt"></i>
                                <span>Security Status</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>api-key-settings.php">
                                <i class="fas fa-key"></i>
                                <span>API Key Management</span>
                            </a>
                        </li>
                    </ul>
                </li>
                
                <!-- SETTINGS -->
                <li class="nav-category collapsed">
                    <div class="nav-category-header" onclick="toggleCategory(this)">
                        <span><i class="fas fa-cog"></i> Settings</span>
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                    <ul class="nav-category-items">
                        <li>
                            <a href="<?php echo $pagesPath; ?>api-settings.php">
                                <i class="fas fa-cog"></i>
                                <span>API Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo $pagesPath; ?>change-password.php">
                                <i class="fas fa-lock"></i>
                                <span>Change Password</span>
                            </a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        
        <div class="sidebar-footer">
            <a href="<?php echo SITE_URL; ?>/" target="_blank" class="btn-view-site">
                <i class="fas fa-external-link-alt"></i> View Site
            </a>
        </div>
    </aside>

    <!-- Sidebar overlay backdrop (mobile) -->
    <div id="sidebarOverlay"></div>
    
    <!-- Main Content Area -->
    <div class="admin-main">
        <!-- Top Bar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h2><?php echo $page_title ?? 'Admin Panel'; ?></h2>
            </div>
            
            <div class="topbar-right">
                <div class="topbar-item">
                    <a href="<?php echo SITE_URL; ?>/" target="_blank" class="btn-icon" title="View Website">
                        <i class="fas fa-globe"></i>
                    </a>
                </div>
                
                <div class="topbar-item dropdown">
                    <button class="user-menu-trigger" id="userMenuTrigger">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="user-info">
                            <span class="user-name"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? $_SESSION['admin_username'] ?? 'Admin'); ?></span>
                            <span class="user-role"><?php echo ucfirst($_SESSION['admin_role'] ?? 'administrator'); ?></span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    
                    <div class="dropdown-menu" id="userMenu">
                        <a href="<?php echo $pagesPath; ?>change-password.php">
                            <i class="fas fa-lock"></i> Change Password
                        </a>
                        <a href="<?php echo $pagesPath; ?>api-settings.php">
                            <i class="fas fa-cog"></i> API Settings
                        </a>
                        <hr>
                        <form method="POST" action="<?php echo $adminPath; ?>logout.php" style="margin:0;padding:0;">
                            <button type="submit" class="logout-button" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;width:100%;text-align:left;padding:12px 20px;color:#ef4444;font-size:14px;transition:all 0.3s;">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="admin-content">

<script>
// Collapsible Category Toggle with Accordion Behavior
function toggleCategory(header) {
    const category = header.parentElement;
    const isCurrentlyCollapsed = category.classList.contains('collapsed');
    
    // Close all other categories (accordion behavior)
    const allCategories = document.querySelectorAll('.nav-category');
    allCategories.forEach(cat => {
        if (cat !== category) {
            cat.classList.add('collapsed');
        }
    });
    
    // Toggle current category
    if (isCurrentlyCollapsed) {
        category.classList.remove('collapsed');
    } else {
        category.classList.add('collapsed');
    }
}

// User Menu Toggle
document.addEventListener('DOMContentLoaded', function() {
    const userMenuTrigger = document.getElementById('userMenuTrigger');
    const userMenu = document.getElementById('userMenu');
    const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    
    if (userMenuTrigger && userMenu) {
        userMenuTrigger.addEventListener('click', function(e) {
            e.stopPropagation();
            userMenu.classList.toggle('show');
        });
        
        document.addEventListener('click', function(e) {
            if (!userMenuTrigger.contains(e.target) && !userMenu.contains(e.target)) {
                userMenu.classList.remove('show');
            }
        });
    }
    
    // Mobile Sidebar Toggle
    const sidebarOverlay = document.getElementById('sidebarOverlay');
    function openSidebar() {
        adminSidebar.classList.add('show');
        if (sidebarOverlay) sidebarOverlay.classList.add('show');
        document.body.classList.add('sidebar-open');
    }
    function closeSidebar() {
        adminSidebar.classList.remove('show');
        if (sidebarOverlay) sidebarOverlay.classList.remove('show');
        document.body.classList.remove('sidebar-open');
    }
    if (mobileSidebarToggle && adminSidebar) {
        mobileSidebarToggle.addEventListener('click', function() {
            if (adminSidebar.classList.contains('show')) { closeSidebar(); } else { openSidebar(); }
        });
    }
    if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', closeSidebar);
    }
    // Close sidebar on nav link click (mobile)
    document.querySelectorAll('.sidebar-nav a').forEach(function(link) {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 1024) closeSidebar();
        });
    });
});
</script>
