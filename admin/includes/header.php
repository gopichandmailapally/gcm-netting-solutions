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
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin.css?v=2">
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin-modern.css?v=2">
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin-ultra-modern.css?v=2">
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin-enhanced-modern.css?v=2">
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin-accordion-sidebar.css?v=2">
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
    <!-- Sidebar -->
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
        
        <?php
        $currentPage = basename($_SERVER['PHP_SELF']);
        if (!function_exists('navGroupOpen')) {
            function navGroupOpen($pages) {
                return in_array(basename($_SERVER['PHP_SELF']), $pages) ? 'open' : '';
            }
        }
        ?>
        <nav class="sidebar-nav">
            <ul>
                <!-- Dashboard -->
                <li class="nav-dashboard-item <?php echo $currentPage == 'dashboard.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $adminPath; ?>dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- CONTENT -->
                <li class="nav-group <?php echo navGroupOpen(['service-highlights.php','homepage-images.php','generate-pages.php','pillar-page-generator.php','view-generated-pages.php','content-generator.php','content-export.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-edit"></i><span>Content</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='service-highlights.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>service-highlights.php"><i class="fas fa-star"></i><span>Service Highlights</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='homepage-images.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>homepage-images.php"><i class="fas fa-images"></i><span>Homepage Images</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='generate-pages.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>generate-pages.php"><i class="fas fa-cogs"></i><span>AI Page Generator</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='pillar-page-generator.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>pillar-page-generator.php"><i class="fas fa-layer-group"></i><span>64 Pillar Pages</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='view-generated-pages.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>view-generated-pages.php"><i class="fas fa-file-alt"></i><span>View Generated Pages</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='content-export.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>content-export.php"><i class="fas fa-shield-alt"></i><span>Content Protection</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='content-generator.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>content-generator.php"><i class="fas fa-robot"></i><span>Content Generator</span></a>
                        </li>
                    </ul>
                </li>

                <!-- WEBSITE -->
                <li class="nav-group <?php echo navGroupOpen(['hero-slider.php','gallery-management.php','about-page.php','logo-management.php','rate-management.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-globe"></i><span>Website</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='hero-slider.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>hero-slider.php"><i class="fas fa-sliders-h"></i><span>Hero Slider</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='gallery-management.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>gallery-management.php"><i class="fas fa-th"></i><span>Gallery</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='about-page.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>about-page.php"><i class="fas fa-info-circle"></i><span>About Page</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='logo-management.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>logo-management.php"><i class="fas fa-image"></i><span>Logo Management</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='rate-management.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>rate-management.php"><i class="fas fa-rupee-sign"></i><span>Rate Management</span></a>
                        </li>
                    </ul>
                </li>

                <!-- PAGE FIXES -->
                <li class="nav-group <?php echo navGroupOpen(['fix-broken-pages.php','fix-layout.php','fix-faq.php','update-hero-titles.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-tools"></i><span>Page Fixes</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='fix-broken-pages.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>fix-broken-pages.php"><i class="fas fa-wrench"></i><span>Fix Broken Pages</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='fix-layout.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>fix-layout.php"><i class="fas fa-columns"></i><span>Fix Layout</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='fix-faq.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>fix-faq.php"><i class="fas fa-question-circle"></i><span>Fix FAQ Format</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='update-hero-titles.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>update-hero-titles.php"><i class="fas fa-heading"></i><span>Update Hero Titles</span></a>
                        </li>
                    </ul>
                </li>

                <!-- SEO & MARKETING -->
                <li class="nav-group <?php echo navGroupOpen(['complete-seo-system.php','auto-seo-optimizer-dashboard.php','seo-dashboard.php','sitemap-generator.php','search-console.php','realistic-seo-dashboard.php','seo-improvement-guide.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-rocket"></i><span>SEO &amp; Marketing</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='complete-seo-system.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>complete-seo-system.php"><i class="fas fa-rocket"></i><span>Complete SEO System</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='auto-seo-optimizer-dashboard.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>auto-seo-optimizer-dashboard.php"><i class="fas fa-magic"></i><span>Auto SEO Optimizer</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='seo-dashboard.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>seo-dashboard.php"><i class="fas fa-search"></i><span>SEO Dashboard</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='realistic-seo-dashboard.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>realistic-seo-dashboard.php"><i class="fas fa-search-location"></i><span>Rank Checker</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='sitemap-generator.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>sitemap-generator.php"><i class="fas fa-sitemap"></i><span>Sitemap Generator</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='search-console.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>search-console.php"><i class="fab fa-google"></i><span>Search Console</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='seo-improvement-guide.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>seo-improvement-guide.php"><i class="fas fa-graduation-cap"></i><span>SEO Guide</span></a>
                        </li>
                    </ul>
                </li>

                <!-- AUTOMATED CONTENT -->
                <li class="nav-group <?php echo navGroupOpen(['auto-blog-dashboard.php','review-generator.php','faq-generator.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-robot"></i><span>Automated Content</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='auto-blog-dashboard.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>auto-blog-dashboard.php"><i class="fas fa-robot"></i><span>Auto Blog Generator</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='review-generator.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>review-generator.php"><i class="fas fa-star"></i><span>Auto Review Generator</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='faq-generator.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>faq-generator.php"><i class="fas fa-question-circle"></i><span>Auto FAQ Generator</span></a>
                        </li>
                    </ul>
                </li>

                <!-- CONTENT MANAGEMENT -->
                <li class="nav-group <?php echo navGroupOpen(['manage-blogs.php','manage-reviews.php','manage-faqs.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-layer-group"></i><span>Content Management</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='manage-blogs.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>manage-blogs.php"><i class="fas fa-blog"></i><span>Manage Blogs</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='manage-reviews.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>manage-reviews.php"><i class="fas fa-tasks"></i><span>Manage Reviews</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='manage-faqs.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>manage-faqs.php"><i class="fas fa-question-circle"></i><span>Manage FAQs</span></a>
                        </li>
                    </ul>
                </li>

                <!-- ANALYTICS -->
                <li class="nav-group <?php echo navGroupOpen(['visitor-analytics.php','live-visitors.php','advanced-analytics.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-chart-line"></i><span>Analytics &amp; Tracking</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='visitor-analytics.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>visitor-analytics.php"><i class="fas fa-chart-line"></i><span>Visitor Analytics</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='live-visitors.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>live-visitors.php"><i class="fas fa-users"></i><span>Live Visitors</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='advanced-analytics.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>advanced-analytics.php"><i class="fas fa-chart-bar"></i><span>Advanced Analytics</span></a>
                        </li>
                    </ul>
                </li>

                <!-- CUSTOMER MANAGEMENT -->
                <li class="nav-group <?php echo navGroupOpen(['contact-forms.php','offers-management.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-users"></i><span>Customer Management</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='contact-forms.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>contact-forms.php"><i class="fas fa-envelope"></i><span>Contact Forms</span></a>
                        </li>
                    </ul>
                </li>

                <!-- SECURITY -->
                <li class="nav-group <?php echo navGroupOpen(['security-status.php','ai-content-security.php','account-manager.php','security-2fa.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-shield-alt"></i><span>Security</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='security-status.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>security-status.php"><i class="fas fa-shield-alt"></i><span>Security Status</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='ai-content-security.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>ai-content-security.php"><i class="fas fa-lock"></i><span>AI Content Security</span><span class="nav-ai-badge"><i class="fas fa-robot"></i>AI</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='account-manager.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>account-manager.php"><i class="fas fa-user-shield"></i><span>Account Manager</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='security-2fa.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>security-2fa.php"><i class="fas fa-mobile-alt"></i><span>2FA &amp; Trusted Devices</span></a>
                        </li>
                    </ul>
                </li>

                <!-- SETTINGS -->
                <li class="nav-group <?php echo navGroupOpen(['api-settings.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-cog"></i><span>Settings</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='api-settings.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>api-settings.php"><i class="fas fa-cog"></i><span>API Settings</span></a>
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
                        <a href="<?php echo $pagesPath; ?>account-manager.php">
                            <i class="fas fa-user-shield"></i> Account Manager
                        </a>
                        <a href="<?php echo $pagesPath; ?>api-settings.php">
                            <i class="fas fa-cog"></i> API Settings
                        </a>
                        <hr>
                        <form method="POST" action="<?php echo $adminPath; ?>logout.php" style="margin:0;padding:0;">
                            <button type="submit" class="text-danger logout-button" style="background:none;border:none;cursor:pointer;display:flex;align-items:center;gap:8px;width:100%;text-align:left;padding:12px 20px;color:#EF4444;font-size:14px;transition:all 0.3s;">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                        <style>
                            .logout-button:hover {
                                background: #FEE2E2 !important;
                                padding-left: 24px !important;
                            }
                        </style>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <main class="admin-content">

<script>
(function() {
    function initAccordion() {
        var headers = document.querySelectorAll('.nav-group-header');
        headers.forEach(function(header) {
            header.addEventListener('click', function() {
                var group = this.closest('.nav-group');
                var isOpen = group.classList.contains('open');
                // Close ALL groups
                document.querySelectorAll('.nav-group.open').forEach(function(g) {
                    g.classList.remove('open');
                });
                // Open clicked group if it was closed
                if (!isOpen) {
                    group.classList.add('open');
                }
            });
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAccordion);
    } else {
        initAccordion();
    }

    // Mobile sidebar toggle with overlay
    document.addEventListener('DOMContentLoaded', function() {
        var adminSidebar = document.getElementById('adminSidebar');
        var mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        var sidebarOverlay = document.getElementById('sidebarOverlay');
        function openSidebar() {
            if (adminSidebar) adminSidebar.classList.add('show');
            if (sidebarOverlay) sidebarOverlay.classList.add('show');
            document.body.classList.add('sidebar-open');
        }
        function closeSidebar() {
            if (adminSidebar) adminSidebar.classList.remove('show');
            if (sidebarOverlay) sidebarOverlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
        if (mobileSidebarToggle) {
            mobileSidebarToggle.addEventListener('click', function() {
                if (adminSidebar && adminSidebar.classList.contains('show')) { closeSidebar(); } else { openSidebar(); }
            });
        }
        if (sidebarOverlay) { sidebarOverlay.addEventListener('click', closeSidebar); }
        document.querySelectorAll('.sidebar-nav a').forEach(function(link) {
            link.addEventListener('click', function() { if (window.innerWidth <= 1024) closeSidebar(); });
        });
    });
})();
</script>
