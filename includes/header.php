<?php
if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Google Search Console Verification -->
    <meta name="google-site-verification" content="LM3-QQv_gwEmLfFxVrcOfu3mtxPhG2CUQm4ncsYksG4" />
    <meta name="google-site-verification" content="z3sEDC2o67dDvLFf6Gv8c0oQd9sepeHfXF-rpNw_ZNA" />
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo $meta_description ?? SITE_DESCRIPTION; ?>">
    <meta name="keywords" content="<?php echo $meta_keywords ?? DEFAULT_META_KEYWORDS; ?>">
    <meta name="author" content="<?php echo COMPANY_NAME; ?>">
    
    <!-- Title -->
    <title><?php echo ($page_title ?? 'Home') . DEFAULT_META_TITLE_SUFFIX; ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo SITE_URL; ?>/assets/img/favicon.png">
    
    <!-- Open Graph Meta Tags -->
    <meta property="og:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
    <meta property="og:description" content="<?php echo $meta_description ?? SITE_DESCRIPTION; ?>">
    <meta property="og:image" content="<?php echo SITE_URL; ?>/assets/img/og-image.jpg">
    <meta property="og:url" content="<?php echo SITE_URL . $_SERVER['REQUEST_URI']; ?>">
    <meta property="og:type" content="website">
    
    <!-- Twitter Card Meta Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $page_title ?? SITE_NAME; ?>">
    <meta name="twitter:description" content="<?php echo $meta_description ?? SITE_DESCRIPTION; ?>">
    <meta name="twitter:image" content="<?php echo SITE_URL; ?>/assets/img/og-image.jpg">
    
    <!-- Stylesheets -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/mega-menu.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/slider.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/success-popup.css">
    
    <!-- Additional CSS if specified -->
    <?php if (isset($additional_css) && is_array($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?php echo SITE_URL; ?>/<?php echo $css; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Schema.org Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "LocalBusiness",
        "name": "<?php echo COMPANY_NAME; ?>",
        "image": "<?php echo SITE_URL; ?>/assets/img/logo.png",
        "description": "<?php echo SITE_DESCRIPTION; ?>",
        "address": {
            "@type": "PostalAddress",
            "streetAddress": "<?php echo COMPANY_ADDRESS; ?>",
            "addressLocality": "Chennai",
            "addressRegion": "Tamil Nadu",
            "postalCode": "600002",
            "addressCountry": "IN"
        },
        "telephone": "+91-<?php echo COMPANY_PHONE; ?>",
        "email": "<?php echo COMPANY_EMAIL; ?>",
        "url": "<?php echo SITE_URL; ?>",
        "priceRange": "₹₹",
        "areaServed": {
            "@type": "City",
            "name": "Chennai"
        },
        "openingHours": "Mo-Su 08:00-20:00",
        "sameAs": [
            "https://www.facebook.com/gcmsafetynets",
            "https://www.instagram.com/gcmsafetynets"
        ]
    }
    </script>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="top-bar-content">
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <a href="tel:+91<?php echo COMPANY_PHONE; ?>">
                        +91 99123 99224
                    </a>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <a href="mailto:<?php echo COMPANY_EMAIL; ?>">
                        <?php echo COMPANY_EMAIL; ?>
                    </a>
                </div>
            </div>
            <div class="top-bar-right">
                <div class="social-links">
                    <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Main Header -->
    <header class="site-header" id="siteHeader">
        <div class="header-container">
            <div class="header-content">
                <!-- Logo -->
                <div class="site-logo">
                    <a href="<?php echo SITE_URL; ?>/">
                        <img src="<?php echo SITE_URL; ?>/assets/img/logo.png" alt="<?php echo COMPANY_NAME; ?> Logo">
                        <div class="logo-text">
                            <span class="logo-name"><?php echo COMPANY_NAME; ?></span>
                            <span class="logo-tagline"><?php echo SITE_TAGLINE; ?></span>
                        </div>
                    </a>
                </div>
                
                <!-- Main Navigation -->
                <?php include 'mega-menu.php'; generate_mega_menu(); ?>
            </div>
        </div>
    </header>
    
    <!-- Main Content -->
    <main class="site-content">
