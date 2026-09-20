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
    <link rel="stylesheet" href="<?php echo $adminPath; ?>assets/css/admin-lux-executive.css?v=2">
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

                <!-- Inquiries & Leads -->
                <li class="nav-dashboard-item <?php echo $currentPage == 'contact-forms.php' ? 'active' : ''; ?>">
                    <a href="<?php echo $pagesPath; ?>contact-forms.php">
                        <i class="fas fa-envelope-open-text"></i>
                        <span>Customer Leads</span>
                    </a>
                </li>

                <!-- Content & Media -->
                <li class="nav-group <?php echo navGroupOpen(['manage-blogs.php','manage-reviews.php','manage-faqs.php','gallery-management.php','hero-slider.php','about-page.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-layer-group"></i><span>Content &amp; Media</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='manage-blogs.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>manage-blogs.php"><i class="fas fa-blog"></i><span>Manage Blogs</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='manage-reviews.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>manage-reviews.php"><i class="fas fa-star"></i><span>Customer Reviews</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='manage-faqs.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>manage-faqs.php"><i class="fas fa-question-circle"></i><span>Manage FAQs</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='gallery-management.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>gallery-management.php"><i class="fas fa-images"></i><span>Photo Gallery</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='hero-slider.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>hero-slider.php"><i class="fas fa-sliders-h"></i><span>Hero Slider</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='about-page.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>about-page.php"><i class="fas fa-info-circle"></i><span>About Page</span></a>
                        </li>
                    </ul>
                </li>

                <!-- SEO & Search -->
                <li class="nav-group <?php echo navGroupOpen(['seo-dashboard.php','sitemap-generator.php','search-console.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-rocket"></i><span>SEO &amp; Search</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='seo-dashboard.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>seo-dashboard.php"><i class="fas fa-search"></i><span>SEO Dashboard</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='sitemap-generator.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>sitemap-generator.php"><i class="fas fa-sitemap"></i><span>Sitemap Generator</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='search-console.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>search-console.php"><i class="fab fa-google"></i><span>Search Console</span></a>
                        </li>
                    </ul>
                </li>

                <!-- Analytics & Tracking -->
                <li class="nav-group <?php echo navGroupOpen(['visitor-analytics.php','live-visitors.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-chart-line"></i><span>Analytics &amp; Traffic</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='visitor-analytics.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>visitor-analytics.php"><i class="fas fa-chart-line"></i><span>Visitor Analytics</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='live-visitors.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>live-visitors.php"><i class="fas fa-users"></i><span>Live Visitors</span></a>
                        </li>
                    </ul>
                </li>

                <!-- Security & System -->
                <li class="nav-group <?php echo navGroupOpen(['security-status.php','account-manager.php','security-2fa.php','api-settings.php']); ?>">
                    <div class="nav-group-header">
                        <div class="nav-group-label"><i class="fas fa-shield-alt"></i><span>Security &amp; System</span></div>
                        <i class="fas fa-chevron-right nav-group-arrow"></i>
                    </div>
                    <ul class="nav-group-items">
                        <li class="<?php echo $currentPage=='security-status.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>security-status.php"><i class="fas fa-shield-alt"></i><span>Security Status</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='account-manager.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>account-manager.php"><i class="fas fa-user-shield"></i><span>Account Manager</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='security-2fa.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>security-2fa.php"><i class="fas fa-mobile-alt"></i><span>2FA Authentication</span></a>
                        </li>
                        <li class="<?php echo $currentPage=='api-settings.php'?'active':''; ?>">
                            <a href="<?php echo $pagesPath; ?>api-settings.php"><i class="fas fa-cog"></i><span>API &amp; System Settings</span></a>
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
