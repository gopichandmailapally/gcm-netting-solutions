<?php
/**
 * GCM Netting Solutions - Homepage
 * Professional netting solutions in Chennai
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

$page_title = 'Safety Nets in Chennai | Pigeon Nets & Balcony Safety Nets Installation Near Me';
$meta_description = 'Professional safety nets, pigeon nets, bird nets, invisible grills, sports nets, and cloth hangers installation in Chennai. Quality materials, expert installation. Call 9912399224.';
$meta_keywords = 'safety nets chennai, safety nets near me, pigeon nets chennai, pigeon nets for balcony near me, balcony safety nets in chennai, invisible grills chennai, cricket practice nets chennai, ceiling cloth drying hangers chennai, bird net installation chennai';

// Fetch services for slider
$db = Database::getInstance();
$services = $db->fetchAll("
    SELECT * FROM services 
    WHERE is_active = 1 AND show_in_slider = 1
    ORDER BY display_order ASC 
    LIMIT 6
");

// Fetch recent reviews from JSON files
$reviews_dir = __DIR__ . '/data/reviews';
$reviews = [];

if (is_dir($reviews_dir)) {
    $files = glob($reviews_dir . '/*.json');
    foreach ($files as $file) {
        if (basename($file) === 'stats.json') continue;
        $review = json_decode(file_get_contents($file), true);
        if ($review && $review['status'] === 'approved') {
            $reviews[] = $review;
        }
    }
    // Sort by date (newest first)
    usort($reviews, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    // Limit to 6
    $reviews = array_slice($reviews, 0, 6);
}

// Fetch recent blog posts
try {
    $blogs = $db->fetchAll("
        SELECT * FROM blog_posts 
        WHERE is_published = 1 
        ORDER BY published_at DESC 
        LIMIT 3
    ");
} catch (Exception $e) {
    $blogs = [];
}

$current_page = 'home';
include 'includes/modern-header.php';
?>

<!-- Improved Homepage CSS -->
<link rel="stylesheet" href="assets/css/homepage-improved.css">
<link rel="stylesheet" href="assets/css/service-highlights.css">
<link rel="stylesheet" href="assets/css/gcm-lux-theme.css?v=2">

<?php
// Unique subtitle for each hero slide.
// DB value is used when admin has customised it; otherwise this array is the source of truth.
$slide_descriptions = [
    'pigeon-nets'      => 'Expert pigeon netting to protect your balcony, terrace & AC units — fully mess-free',
    'bird-nets'        => 'Humane bird-proofing solutions for homes, offices & commercial buildings across Chennai',
    'safety-nets'      => 'ISI-certified balcony & staircase safety nets — trusted by 10,000+ families in Chennai',
    'cricket-nets'     => 'High-tension practice nets for schools, clubs & private backyards — custom sizes available',
    'invisible-grills' => 'Sleek stainless steel invisible grills — maximum safety with zero obstruction to your view',
    'cloth-hangers'    => 'Space-saving ceiling-mounted cloth drying systems — strong, rust-proof & built to last',
];
$_generic = 'Professional installation services across Chennai';
?>
<!-- Hero Slider Section -->
<section class="hero-slider-section">
    <div class="slider-container">
        <div class="hero-slider" id="heroSlider">
            <?php foreach ($services as $index => $service):
                $svc_slug     = $service['service_slug'] ?: ($service['slug'] ?? '');
                $svc_img_path = 'assets/img/services/' . $svc_slug . '.jpg';
                $svc_img_abs  = __DIR__ . '/' . $svc_img_path;
                $svc_img_v    = file_exists($svc_img_abs) ? filemtime($svc_img_abs) : time();
                $db_desc      = trim($service['description'] ?? '');
                $slide_desc   = ($db_desc !== '' && $db_desc !== $_generic)
                                ? $db_desc
                                : ($slide_descriptions[$svc_slug] ?? $_generic);
            ?>
                <div class="slide <?php echo $index === 0 ? 'active' : ''; ?>" data-slide="<?php echo $index; ?>">
                    <div class="slide-background" style="background-image: url('<?php echo $svc_img_path; ?>?v=<?php echo $svc_img_v; ?>');">
                        <div class="slide-overlay"></div>
                    </div>
                    <div class="container">
                        <div class="slide-content">
                            <div class="slide-icon">
                                <i class="<?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                            </div>
                            <h1 class="slide-title"><?php echo htmlspecialchars($service['service_name']); ?></h1>
                            <p class="slide-description"><?php echo htmlspecialchars($slide_desc); ?></p>
                            <div class="slide-actions">
                                <a href="contact?service=<?php echo $service['service_slug']; ?>" class="btn btn-primary">
                                    <i class="fas fa-phone"></i> Get Free Quote
                                </a>
                                <a href="#services" class="btn btn-secondary">
                                    <i class="fas fa-info-circle"></i> Learn More
                                </a>
                            </div>
                            <div class="slide-features">
                                <div class="feature-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Quality Materials</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Expert Installation</span>
                                </div>
                                <div class="feature-item">
                                    <i class="fas fa-check-circle"></i>
                                    <span>Affordable Prices</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Slider Controls -->
        <div class="slider-controls">
            <button class="slider-btn prev" onclick="prevSlide()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-btn next" onclick="nextSlide()">
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <!-- Slider Dots -->
        <div class="slider-dots">
            <?php foreach ($services as $index => $service): ?>
                <button class="dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></button>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Quick Contact & Offers Section -->
<section class="quick-contact-section">
    <div class="container">
        <div class="contact-offers-grid">
            <!-- Contact Form -->
            <div class="contact-form-box">
                <h3><i class="fas fa-envelope"></i> Get Free Quotation</h3>
                <p>Fill the form and we'll contact you within 24 hours</p>
                
                <form action="api/contact-handler.php" method="POST" class="contact-form" id="quickContactForm">
                    <input type="hidden" name="form_type" value="quick_quote">
                    
                    <div class="form-group">
                        <input type="text" name="name" class="form-control" placeholder="Your Name *" required>
                    </div>
                    
                    <div class="form-group">
                        <input type="tel" name="phone" class="form-control" placeholder="Phone Number *" required pattern="[6-9][0-9]{9}" title="Please enter a valid Indian mobile number starting with 6, 7, 8, or 9">
                    </div>
                    
                    <div class="form-group">
                        <input type="email" name="email" class="form-control" placeholder="Email Address">
                    </div>
                    
                    <div class="form-group">
                        <select name="service" class="form-control" required>
                            <option value="">Select Service *</option>
                            <option value="balcony-safety-nets">Balcony Safety Nets</option>
                            <option value="pigeon-nets">Pigeon Nets</option>
                            <option value="children-safety-nets">Children Safety Nets</option>
                            <option value="invisible-grills">Invisible Grills</option>
                            <option value="sports-nets">Sports Nets</option>
                            <option value="cloth-hangers">Cloth Hangers</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <textarea name="message" class="form-control" rows="3" placeholder="Your Message"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-paper-plane"></i> Send Request
                    </button>
                    
                    <div class="form-footer">
                        <p><i class="fas fa-lock"></i> Your information is safe with us</p>
                    </div>
                </form>
            </div>
            
            <!-- Service Highlights from Database -->
            <?php
            // Fetch active service highlights
            $highlights = $db->fetchAll("SELECT * FROM service_highlights WHERE is_active = 1 ORDER BY display_order ASC LIMIT 6");
            
            if ($highlights && count($highlights) > 0):
            ?>
            <div class="service-highlights-box">
                <h3 style="text-align: center; margin-bottom: 30px; font-size: 24px; color: #1e293b;">
                    <i class="fas fa-star" style="color: #667eea;"></i> Why Choose GCM Netting Solutions
                </h3>
                <div class="highlights-grid">
                    <?php foreach ($highlights as $highlight): ?>
                    <div class="highlight-card">
                        <div class="highlight-icon" style="background: <?php echo htmlspecialchars($highlight['highlight_color']); ?>;">
                            <i class="<?php echo htmlspecialchars($highlight['icon_class']); ?>"></i>
                        </div>
                        <h4 class="highlight-title"><?php echo htmlspecialchars($highlight['title']); ?></h4>
                        <?php if (!empty($highlight['description'])): ?>
                        <p class="highlight-description"><?php echo htmlspecialchars($highlight['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else: ?>
            <!-- Fallback if no highlights configured -->
            <div class="service-highlights-box">
                <h3 style="text-align: center; margin-bottom: 30px; font-size: 24px; color: #1e293b;">
                    <i class="fas fa-star" style="color: #667eea;"></i> Why Choose GCM Netting Solutions
                </h3>
                <div class="highlights-grid">
                    <div class="highlight-card">
                        <div class="highlight-icon" style="background: #667eea;"><i class="fas fa-award"></i></div>
                        <h4 class="highlight-title">15+ Years Experience</h4>
                        <p class="highlight-description">Trusted by 10,000+ customers</p>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon" style="background: #10b981;"><i class="fas fa-certificate"></i></div>
                        <h4 class="highlight-title">Russea™ Branded Nets</h4>
                        <p class="highlight-description">Authorised dealers in <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">Russea™</a> with 5-yr warranty</p>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon" style="background: #f59e0b;"><i class="fas fa-user-check"></i></div>
                        <h4 class="highlight-title">Expert Installation</h4>
                        <p class="highlight-description">Trained professionals</p>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon" style="background: #ef4444;"><i class="fas fa-clock"></i></div>
                        <h4 class="highlight-title">Same Day Service</h4>
                        <p class="highlight-description">Quick installation within 24 hours</p>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon" style="background: #3b82f6;"><i class="fas fa-search"></i></div>
                        <h4 class="highlight-title">Free Inspection</h4>
                        <p class="highlight-description">Complimentary site visit</p>
                    </div>
                    <div class="highlight-card">
                        <div class="highlight-icon" style="background: #8b5cf6;"><i class="fas fa-rupee-sign"></i></div>
                        <h4 class="highlight-title">Affordable Pricing</h4>
                        <p class="highlight-description">Competitive transparent rates</p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Marquee Features -->
<div class="marquee-container">
    <div class="marquee-content">
        <span><i class="fas fa-star"></i> 10+ Years Experience</span>
        <span><i class="fas fa-users"></i> 10,000+ Happy Customers</span>
        <span><i class="fas fa-shield-alt"></i> Quality Guaranteed</span>
        <span><i class="fas fa-tools"></i> Professional Installation</span>
        <span><i class="fas fa-headset"></i> 24/7 Customer Support</span>
        <span><i class="fas fa-certificate"></i> Authorised <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">Russea™</a> Dealer</span>
        <span><i class="fas fa-rupee-sign"></i> Affordable Pricing</span>
        <span><i class="fas fa-map-marker-alt"></i> All Over Chennai</span>
    </div>
</div>

<!-- Company Highlights -->
<section class="highlights-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Why Choose GCM Netting Solutions?</h2>
            <p>Chennai's Most Trusted Safety Net Installation Service</p>
        </div>
        
        <div class="highlights-grid">
            <div class="highlight-card">
                <div class="highlight-icon">
                    <i class="fas fa-award"></i>
                </div>
                <h3>10+ Years Experience</h3>
                <p>Decade of excellence in providing safety solutions across Chennai</p>
            </div>
            
            <div class="highlight-card">
                <div class="highlight-icon">
                    <i class="fas fa-certificate"></i>
                </div>
                <h3>Authorised Russea™ Nets</h3>
                <p>Authentic 100% Virgin <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">Russea™ Branded Nets</a> with UV protection & 5-year warranty</p>
            </div>
            
            <div class="highlight-card">
                <div class="highlight-icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <h3>Expert Team</h3>
                <p>Trained professionals ensuring perfect installation every time</p>
            </div>
            
            <div class="highlight-card">
                <div class="highlight-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>5 Year Warranty</h3>
                <p>Comprehensive warranty on all our products and services</p>
            </div>
            
            <div class="highlight-card">
                <div class="highlight-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <h3>Best Prices</h3>
                <p>Competitive pricing with no hidden charges or extra costs</p>
            </div>
            
            <div class="highlight-card">
                <div class="highlight-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Quick Service</h3>
                <p>Same-day installation available across all Chennai locations</p>
            </div>
        </div>
    </div>
</section>

<!-- Services Section -->
<section class="services-section" id="services">
    <div class="container">
        <div class="section-header text-center">
            <h2>Our Services</h2>
            <p>Comprehensive Safety Solutions for Residential & Commercial Properties</p>
        </div>

        <?php
        $card_descriptions = [
            'pigeon-nets'      => 'Keep pigeons away from your balcony, terrace & AC units with durable, mess-free netting — certified installation across Chennai.',
            'bird-nets'        => 'Humane bird-proofing for homes, offices & commercial buildings — safe, long-lasting & professionally installed.',
            'safety-nets'      => 'ISI-certified safety nets for balconies & staircases — protecting children, pets & families since 2009.',
            'cricket-nets'     => 'High-tension cricket practice nets for schools, clubs & backyards — custom sizes, quick professional installation.',
            'invisible-grills' => 'Sleek SS invisible grills for balconies — maximum safety with zero view obstruction, elegantly installed.',
            'cloth-hangers'    => 'Space-saving ceiling-mounted cloth drying systems — rust-proof, easy to use & built to last for years.',
        ];
        $_su = rtrim(SITE_URL, '/');
        $learn_more_links = [
            'pigeon-nets'      => $_su . '/services#pigeon-nets',
            'bird-nets'        => $_su . '/services#bird-nets',
            'safety-nets'      => $_su . '/services#safety-nets',
            'cricket-nets'     => $_su . '/services#cricket-nets',
            'invisible-grills' => $_su . '/services#invisible-grills',
            'cloth-hangers'    => $_su . '/services#ceiling-cloth-hangers',
        ];
        ?>

        <div class="services-grid">
            <?php foreach ($services as $service):
                $svc_slug  = $service['service_slug'] ?: ($service['slug'] ?? '');
                $card_desc = $card_descriptions[$svc_slug] ?? ($service['description'] ?: 'Professional installation services across Chennai.');
                $learn_url = $learn_more_links[$svc_slug] ?? 'services';
            ?>
                <div class="service-card">
                    <div class="service-image">
                        <img src="assets/img/services/<?php echo htmlspecialchars($svc_slug); ?>.jpg" alt="<?php echo htmlspecialchars($service['service_name']); ?>" loading="lazy">
                        <div class="service-overlay">
                            <a href="<?php echo $learn_url; ?>" class="btn btn-white">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                    <div class="service-content">
                        <div class="service-icon">
                            <i class="<?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($service['service_name']); ?></h3>
                        <p><?php echo htmlspecialchars($card_desc); ?></p>
                        <a href="<?php echo $learn_url; ?>" class="service-link">
                            Learn More <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center" style="margin-top: 40px;">
            <a href="estimation" class="btn btn-primary btn-large">
                <i class="fas fa-calculator"></i> Get Price Estimation
            </a>
        </div>
    </div>
</section>

<!-- About Preview Section -->
<section class="about-preview-section">
    <div class="container">
        <div class="about-grid">
            <div class="about-image">
                <?php
                try {
                    $about_image = $db->fetchOne("SELECT * FROM homepage_images WHERE section_name = 'about_section' AND is_active = 1");
                } catch (Exception $e) { $about_image = null; }
                $about_img_path = ($about_image && !empty($about_image['image_path'])) ? $about_image['image_path'] : 'assets/img/about-gcm.jpg';
                $about_img_alt = ($about_image && !empty($about_image['image_alt'])) ? $about_image['image_alt'] : 'About GCM Netting Solutions';
                ?>
                <img src="<?php echo htmlspecialchars($about_img_path); ?>" alt="<?php echo htmlspecialchars($about_img_alt); ?>" loading="lazy">
                <div class="about-badge">
                    <span class="badge-number">10+</span>
                    <span class="badge-text">Years of Excellence</span>
                </div>
            </div>
            
            <div class="about-content">
                <div class="section-header">
                    <h2>About GCM Netting Solutions</h2>
                    <p>Chennai's Largest Netting Solutions Provider</p>
                </div>
                
                <p class="about-text">
                    GCM Netting Solutions has been serving Chennai for over a decade, providing top-quality safety net 
                    installation services for residential and commercial properties. We specialize in pigeon nets, 
                    bird nets, balcony safety nets, invisible grills, sports nets, and cloth hangers.
                </p>
                
                <div class="about-stats">
                    <div class="stat-item">
                        <h3>10,000+</h3>
                        <p>Happy Customers</p>
                    </div>
                    <div class="stat-item">
                        <h3>188+</h3>
                        <p>Areas Covered</p>
                    </div>
                    <div class="stat-item">
                        <h3>100%</h3>
                        <p>Satisfaction Rate</p>
                    </div>
                </div>
                
                <div class="about-features">
                    <div class="feature"><i class="fas fa-check"></i> Premium Quality Materials</div>
                    <div class="feature"><i class="fas fa-check"></i> Experienced Installation Team</div>
                    <div class="feature"><i class="fas fa-check"></i> 5 Year Comprehensive Warranty</div>
                    <div class="feature"><i class="fas fa-check"></i> Affordable & Transparent Pricing</div>
                </div>
                
                <a href="about" class="btn btn-primary">
                    <i class="fas fa-info-circle"></i> Read More About Us
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Reviews Section -->
<section class="reviews-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>What Our Customers Say</h2>
            <p>Real Reviews from Real Customers</p>
        </div>
        
        <div class="reviews-slider">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <div class="review-card">
                        <div class="review-header">
                            <div class="customer-avatar">
                                <?php echo strtoupper(substr($review['customer_name'], 0, 1)); ?>
                            </div>
                            <div class="customer-info">
                                <h4><?php echo htmlspecialchars($review['customer_name']); ?></h4>
                                <?php if (!empty($review['location'])): ?>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($review['location']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="review-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        <?php if (!empty($review['category'])): ?>
                            <div class="review-service">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars($review['category']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default placeholder reviews -->
                <div class="review-card">
                    <div class="review-header">
                        <div class="customer-avatar">R</div>
                        <div class="customer-info">
                            <h4>Rajesh Kumar</h4>
                            <p><i class="fas fa-map-marker-alt"></i> Anna Nagar, Chennai</p>
                        </div>
                        <div class="review-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="review-text">Excellent service! Professional installation and quality materials. Highly recommended for safety nets in Chennai.</p>
                    <div class="review-service">
                        <i class="fas fa-tag"></i> Pigeon Nets
                    </div>
                </div>
                <div class="review-card">
                    <div class="review-header">
                        <div class="customer-avatar">P</div>
                        <div class="customer-info">
                            <h4>Priya Sharma</h4>
                            <p><i class="fas fa-map-marker-alt"></i> T Nagar, Chennai</p>
                        </div>
                        <div class="review-rating">
                            <i class="fas fa-star"></i><i class="fas fa-star"></i><i class="fas fa-star"></i>
                            <i class="fas fa-star"></i><i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="review-text">Very satisfied with the balcony safety nets. Great quality and the team was very professional. Worth every penny!</p>
                    <div class="review-service">
                        <i class="fas fa-tag"></i> Safety Nets
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center" style="margin-top: 40px;">
            <a href="reviews" class="btn btn-secondary">
                <i class="fas fa-star"></i> View All Reviews
            </a>
            <a href="reviews#submit" class="btn btn-primary">
                <i class="fas fa-edit"></i> Write a Review
            </a>
        </div>
    </div>
</section>

<!-- Blogs Preview -->
<section class="blogs-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Latest from Our Blog</h2>
            <p>Tips, Guides & Industry Updates</p>
        </div>
        
        <div class="blogs-grid">
            <?php if (count($blogs) > 0): ?>
                <?php foreach ($blogs as $blog): ?>
                    <div class="blog-card">
                        <?php if (!empty($blog['featured_image'])): ?>
                            <div class="blog-image">
                                <img src="<?php echo htmlspecialchars($blog['featured_image']); ?>" alt="<?php echo htmlspecialchars($blog['title']); ?>" loading="lazy">
                            </div>
                        <?php endif; ?>
                        <div class="blog-content">
                            <div class="blog-meta">
                                <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime($blog['published_at'])); ?></span>
                                <?php if (!empty($blog['category'])): ?>
                                    <span><i class="fas fa-folder"></i> <?php echo htmlspecialchars($blog['category']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h3><?php echo htmlspecialchars($blog['title']); ?></h3>
                            <p><?php echo htmlspecialchars(substr($blog['excerpt'], 0, 150)); ?>...</p>
                            <a href="blog.php?slug=<?php echo $blog['slug']; ?>" class="blog-link">
                                Read More <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Default placeholder blogs -->
                <div class="blog-card">
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y'); ?></span>
                            <span><i class="fas fa-folder"></i> Safety Tips</span>
                        </div>
                        <h3>How to Choose the Right Safety Net for Your Balcony</h3>
                        <p>Learn about different types of safety nets, materials, and factors to consider when selecting the perfect protection for your home...</p>
                        <a href="blogs" class="blog-link">
                            Read More <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="blog-card">
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime('-5 days')); ?></span>
                            <span><i class="fas fa-folder"></i> Installation Guide</span>
                        </div>
                        <h3>Invisible Grills vs Traditional Grills: Which is Better?</h3>
                        <p>Compare the benefits of invisible grills and traditional grills to make an informed decision for your property's safety and aesthetics...</p>
                        <a href="blogs" class="blog-link">
                            Read More <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="blog-card">
                    <div class="blog-content">
                        <div class="blog-meta">
                            <span><i class="fas fa-calendar"></i> <?php echo date('M j, Y', strtotime('-10 days')); ?></span>
                            <span><i class="fas fa-folder"></i> Maintenance</span>
                        </div>
                        <h3>Pigeon Control: Why Professional Nets Are Essential</h3>
                        <p>Discover why pigeon nets are the most effective solution for bird control and how professional installation ensures long-lasting protection...</p>
                        <a href="blogs" class="blog-link">
                            Read More <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center" style="margin-top: 40px;">
            <a href="blogs" class="btn btn-secondary">
                <i class="fas fa-blog"></i> View All Blogs
            </a>
        </div>
    </div>
</section>

<!-- Gallery Section -->
<section class="gallery-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Our Work Gallery</h2>
            <p>See Our Recent Projects & Installations Across Chennai</p>
        </div>
        
        <div class="gallery-grid">
            <?php
            // Fetch gallery images
            $gallery_images = $db->fetchAll("SELECT * FROM gallery_images WHERE is_active = 1 ORDER BY display_order ASC, id DESC LIMIT 8");
            
            if (count($gallery_images) > 0):
                foreach ($gallery_images as $image):
                    $img_src = !empty($image['image_url']) ? $image['image_url'] : ($image['image_path'] ?? '');
            ?>
                <div class="gallery-item">
                    <?php if (!empty($img_src)): ?>
                        <img src="<?php echo htmlspecialchars($img_src); ?>" 
                             alt="<?php echo htmlspecialchars($image['title'] ?? ''); ?>" 
                             loading="lazy">
                    <?php else: ?>
                        <div class="gallery-placeholder">
                            <i class="fas fa-image"></i>
                        </div>
                    <?php endif; ?>
                    <div class="gallery-overlay">
                        <h3><?php echo htmlspecialchars($image['title'] ?? ''); ?></h3>
                        <?php if (!empty($image['alt_text'])): ?>
                            <p><?php echo htmlspecialchars(substr($image['alt_text'], 0, 60)); ?>...</p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <!-- Default placeholder gallery items -->
                <div class="gallery-item">
                    <div class="gallery-placeholder">
                        <i class="fas fa-hard-hat"></i>
                        <p>Pigeon Net Installation</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-placeholder">
                        <i class="fas fa-shield-alt"></i>
                        <p>Safety Net Installation</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-placeholder">
                        <i class="fas fa-border-all"></i>
                        <p>Invisible Grills Setup</p>
                    </div>
                </div>
                <div class="gallery-item">
                    <div class="gallery-placeholder">
                        <i class="fas fa-building"></i>
                        <p>Commercial Projects</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center" style="margin-top: 40px;">
            <a href="gallery" class="btn btn-secondary">
                <i class="fas fa-th"></i> View Full Gallery
            </a>
        </div>
    </div>
</section>


<!-- City-Wide Coverage & Near Me Localities Section (SEO / GEO / AEO) -->
<section class="coverage-section" style="padding: 70px 0; background: #f8fafc; border-top: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
    <div class="container">
        <div class="section-header text-center" style="max-width: 860px; margin: 0 auto 40px;">
            <div style="display:inline-block; background: #e0e7ff; color: #4338ca; font-size: 13px; font-weight: 700; padding: 6px 16px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 12px;">
                <i class="fas fa-map-marked-alt"></i> Complete Chennai City Coverage
            </div>
            <h2 style="font-size: 34px; font-weight: 800; color: #1e293b; margin-bottom: 14px;">
                Looking for Safety Nets Near Me in Chennai?
            </h2>
            <p style="font-size: 17px; color: #64748b; line-height: 1.6;">
                GCM Netting Solutions provides fast, doorstep inspection and same-day installation across <strong>all 600+ localities</strong> in Chennai &amp; Outskirts. Whether you reside in a high-rise apartment in Anna Nagar, a coastal villa in Besant Nagar, or an IT corridor flat in Velachery/OMR, our mobile teams reach you in under 60 minutes.
            </p>
        </div>

        <!-- Top Chennai Hubs Grid -->
        <div style="display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; margin-bottom: 35px;">
            <?php
            $featured_localities = [
                ['name' => 'Anna Nagar', 'slug' => 'anna-nagar'],
                ['name' => 'T Nagar', 'slug' => 't-nagar'],
                ['name' => 'Velachery', 'slug' => 'velachery'],
                ['name' => 'Adyar', 'slug' => 'adyar'],
                ['name' => 'Tambaram', 'slug' => 'tambaram'],
                ['name' => 'Porur', 'slug' => 'porur'],
                ['name' => 'Mylapore', 'slug' => 'mylapore'],
                ['name' => 'Nungambakkam', 'slug' => 'nungambakkam'],
                ['name' => 'Guindy', 'slug' => 'guindy'],
                ['name' => 'Besant Nagar', 'slug' => 'besant-nagar'],
                ['name' => 'Sholinganallur', 'slug' => 'sholinganallur'],
                ['name' => 'Perungudi', 'slug' => 'perungudi'],
                ['name' => 'Thoraipakkam', 'slug' => 'thoraipakkam'],
                ['name' => 'Medavakkam', 'slug' => 'medavakkam'],
                ['name' => 'Chromepet', 'slug' => 'chromepet'],
                ['name' => 'Pallavaram', 'slug' => 'pallavaram'],
                ['name' => 'Ambattur', 'slug' => 'ambattur'],
                ['name' => 'Avadi', 'slug' => 'avadi'],
                ['name' => 'Poonamallee', 'slug' => 'poonamallee'],
                ['name' => 'Kilpauk', 'slug' => 'kilpauk'],
                ['name' => 'Kodambakkam', 'slug' => 'kodambakkam'],
                ['name' => 'Alwarpet', 'slug' => 'alwarpet'],
                ['name' => 'Thiruvanmiyur', 'slug' => 'thiruvanmiyur'],
                ['name' => 'Madipakkam', 'slug' => 'madipakkam'],
                ['name' => 'Saidapet', 'slug' => 'saidapet'],
                ['name' => 'Royapettah', 'slug' => 'royapettah'],
                ['name' => 'Kotturpuram', 'slug' => 'kotturpuram'],
                ['name' => 'Perambur', 'slug' => 'perambur'],
                ['name' => 'Mogappair', 'slug' => 'mogappair'],
                ['name' => 'Navalur (OMR)', 'slug' => 'navalur']
            ];
            foreach ($featured_localities as $loc):
            ?>
            <a href="<?php echo SITE_URL; ?>/balcony-safety-nets-in-<?php echo $loc['slug']; ?>" 
               style="background: white; border: 1px solid #cbd5e1; color: #334155; padding: 10px 18px; border-radius: 25px; text-decoration: none; font-size: 14px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.05);"
               onmouseover="this.style.borderColor='#3b82f6';this.style.color='#2563eb';this.style.transform='translateY(-2px)'"
               onmouseout="this.style.borderColor='#cbd5e1';this.style.color='#334155';this.style.transform='translateY(0)'">
                <i class="fas fa-map-pin" style="color: #ef4444; font-size: 12px;"></i>
                <?php echo $loc['name']; ?>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- Master Directory Call-to-Action Card -->
        <div style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); border-radius: 16px; padding: 35px 30px; text-align: center; color: white; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.15);">
            <div style="max-width: 750px; margin: 0 auto;">
                <h3 style="font-size: 24px; font-weight: 700; margin-bottom: 12px; color: #f8fafc;">
                    Don't see your specific area above?
                </h3>
                <p style="font-size: 16px; color: #94a3b8; margin-bottom: 24px;">
                    We operate fully equipped technician vans in every single colony, road, and ward across Chennai & Outskirts.
                </p>
                <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo SITE_URL; ?>/all-areas" 
                       style="background: linear-gradient(135deg, #2563eb, #1d4ed8); color: white; padding: 14px 28px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 16px; display: inline-flex; align-items: center; gap: 10px; box-shadow: 0 4px 14px rgba(37,99,235,0.4);">
                        <i class="fas fa-list-ul"></i> Browse All 600+ Chennai Service Areas
                    </a>
                    <a href="tel:+919912399224" 
                       style="background: #10b981; color: white; padding: 14px 28px; border-radius: 10px; font-weight: 700; text-decoration: none; font-size: 16px; display: inline-flex; align-items: center; gap: 10px;">
                        <i class="fas fa-phone-alt"></i> Call: +91 99123 99224
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQs Section -->
<section class="faqs-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Frequently Asked Questions</h2>
            <p>Quick Answers to Common Questions About Our Services</p>
        </div>
        
        <div class="faqs-grid">
            <?php
            // Fetch latest published FAQs from database
            $faqs_query = "SELECT * FROM faqs WHERE is_published = 1 ORDER BY display_order ASC, id DESC LIMIT 6";
            try {
                $homepage_faqs = $db->fetchAll($faqs_query);
            } catch (Exception $e) {
                $homepage_faqs = [];
            }
            
            if (count($homepage_faqs) > 0):
                foreach ($homepage_faqs as $index => $faq):
            ?>
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span><?php echo htmlspecialchars($faq['question']); ?></span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p><?php echo nl2br(htmlspecialchars($faq['answer'])); ?></p>
                    </div>
                </div>
            <?php
                endforeach;
            else:
            ?>
                <!-- Default FAQs if none in database -->
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span>What types of safety nets do you install?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We install all types of safety nets including pigeon nets, bird nets, balcony safety nets, invisible grills, sports nets, staircase safety nets, and cloth drying hangers.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span>How long does installation take?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Most installations are completed within 2-4 hours, depending on the size and complexity of the project. We offer same-day installation for urgent requirements.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span>What is the warranty period?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We provide a comprehensive 5-year warranty on all our products and installation services. The warranty covers material defects and installation issues.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span>Do you service all areas of Chennai?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Yes! We provide full coverage across all 600+ localities in Chennai and suburbs, including Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, Porur, Mylapore, Nungambakkam, Sholinganallur, and surrounding areas.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span>What materials do you use?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>We are authorized dealers in genuine <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color: var(--primary-color, #10b981); font-weight: 600; text-decoration: underline;">Russea™ Branded Nets</a>, utilizing 100% Virgin HDPE (High-Density Polyethylene) and high-tensile translucent nylon with UV-stabilization and ISO certification for maximum durability.</p>
                    </div>
                </div>
                
                <div class="faq-item">
                    <button class="faq-question" onclick="toggleFAQ(this)">
                        <span>How much does installation cost?</span>
                        <i class="fas fa-chevron-down"></i>
                    </button>
                    <div class="faq-answer">
                        <p>Costs vary based on the type of net, area size, and installation complexity. Contact us for a free inspection and accurate quotation with no hidden charges.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center" style="margin-top: 40px;">
            <a href="faqs" class="btn btn-secondary">
                <i class="fas fa-question-circle"></i> View All FAQs
            </a>
        </div>
    </div>
</section>

<script>
function toggleFAQ(button) {
    const faqItem = button.parentElement;
    const isActive = faqItem.classList.contains('active');
    
    // Close all FAQs
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
    });
    
    // Open clicked FAQ if it wasn't active
    if (!isActive) {
        faqItem.classList.add('active');
    }
}
</script>

<!-- CTA Section -->
<div class="contact-cta">
    <div class="container">
        <div class="cta-card">
            <div class="cta-content">
                <h2>Ready to Secure Your Space?</h2>
                <p>Get professional installation services across Chennai. Free consultation & quotation!</p>
                <div class="cta-buttons">
                    <a href="tel:+91<?php echo COMPANY_PHONE; ?>" class="btn btn-white btn-large">
                        <i class="fas fa-phone"></i> Call +91 99123 99224
                    </a>
                    <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>" class="btn btn-success btn-large" target="_blank">
                        <i class="fab fa-whatsapp"></i> WhatsApp Us
                    </a>
                    <a href="contact" class="btn btn-primary btn-large">
                        <i class="fas fa-envelope"></i> Contact Form
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/modern-footer.php'; ?>
