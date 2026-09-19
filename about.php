<?php
/**
 * GCM Netting Solutions - About Us Page
 * Company information and history
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

// Fetch about page content from database (table may not exist yet)
$db = Database::getInstance();
try {
    $about_content = $db->fetchOne("SELECT * FROM about_page_content ORDER BY id DESC LIMIT 1");
} catch (Exception $e) {
    $about_content = null;
}

// Default values if no content in database
if (!$about_content) {
    $about_content = [
        'company_description' => 'GCM Netting Solutions has been serving Chennai for over a decade, providing top-quality safety net installation services for residential, commercial, and industrial properties. We specialize in pigeon nets, bird nets, balcony safety nets, invisible grills, sports nets, and cloth hangers.',
        'years_of_experience' => 10,
        'team_image' => 'assets/img/about-company.jpg'
    ];
}

$page_title = 'About Us - GCM Netting Solutions';
$meta_description = 'Learn about GCM Netting Solutions - Chennai\'s most trusted safety net installation company with ' . $about_content['years_of_experience'] . '+ years of experience.';
$meta_keywords = 'about gcm safety nets, safety nets company chennai, pigeon nets chennai, about us';

$current_page = 'about';
include 'includes/modern-header.php';
?>

<div class="about-page">
    <!-- Page Header -->
    <div class="page-banner">
        <div class="container">
            <h1>About GCM Netting Solutions</h1>
            <p>Chennai's Most Trusted Safety Net Installation Company</p>
        </div>
    </div>
    
    <div class="container">
        <!-- Company Overview -->
        <section class="company-overview">
            <div class="overview-grid">
                <div class="overview-image">
                    <?php if (!empty($about_content['team_image']) && file_exists($about_content['team_image'])): ?>
                        <img src="<?php echo htmlspecialchars($about_content['team_image']); ?>" alt="GCM Netting Solutions Team" loading="lazy">
                    <?php else: ?>
                        <img src="assets/img/about-company.jpg" alt="GCM Netting Solutions Team" loading="lazy">
                    <?php endif; ?>
                    <div class="experience-badge">
                        <div class="badge-number"><?php echo $about_content['years_of_experience']; ?>+</div>
                        <div class="badge-text">Years of Excellence</div>
                    </div>
                </div>
                
                <div class="overview-content">
                    <h2>Welcome to GCM Netting Solutions</h2>
                    <p class="lead">Your Trusted Partner for Safety Solutions in Chennai</p>
                    
                    <p><?php echo nl2br(htmlspecialchars($about_content['company_description'])); ?></p>
                    
                    <p>Our commitment to quality, customer satisfaction, and professional service has made us the preferred choice for thousands of customers across Chennai. With a team of experienced professionals and authentic <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color: #10B981; font-weight: 600; text-decoration: underline;">Russea™ Branded Nets</a>, we ensure the highest grade safety and security of your property.</p>
                    
                    <div class="key-points">
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>Authorised <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline;">Russea™</a> Virgin HDPE nets with UV protection</span>
                        </div>
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>5-year comprehensive warranty</span>
                        </div>
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>Trained and experienced installation team</span>
                        </div>
                        <div class="point">
                            <i class="fas fa-check-circle"></i>
                            <span>Competitive pricing with no hidden charges</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Statistics -->
        <section class="statistics-section">
            <div class="stats-container">
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-number">10+</div>
                    <div class="stat-label">Years Experience</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number">10,000+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <div class="stat-number">150+</div>
                    <div class="stat-label">Areas Covered</div>
                </div>
                
                <div class="stat-box">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Satisfaction Rate</div>
                </div>
            </div>
        </section>
        
        <!-- Our Mission & Vision -->
        <section class="mission-vision">
            <div class="mv-grid">
                <div class="mv-card mission">
                    <div class="mv-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Our Mission</h3>
                    <p>To provide the highest quality safety solutions that protect lives, property, and peace of mind. We are committed to delivering professional installation services with premium materials, ensuring the safety and satisfaction of every customer across Chennai.</p>
                </div>
                
                <div class="mv-card vision">
                    <div class="mv-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Our Vision</h3>
                    <p>To become Chennai's most trusted and reliable safety net installation company, known for innovation, quality, and customer service excellence. We aim to expand our services while maintaining the highest standards of professionalism and integrity.</p>
                </div>
            </div>
        </section>
        
        <!-- Why Choose Us -->
        <section class="why-choose-us">
            <h2 class="section-title">Why Choose GCM Netting Solutions?</h2>
            <p class="section-subtitle">What Sets Us Apart from the Competition</p>
            
            <div class="features-grid">
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-certificate"></i>
                    </div>
                    <h4>Authorised Russea™ Nets</h4>
                    <p>We are authorized dealers in genuine <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color: inherit; text-decoration: underline; font-weight: 600;">Russea™ Branded Nets</a>, using only premium UV-treated materials ensuring long-lasting durability.</p>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h4>Expert Team</h4>
                    <p>Our trained professionals have 10+ years of experience in safety net installation across all types of properties.</p>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>5 Year Warranty</h4>
                    <p>Comprehensive warranty on all products and installation, giving you complete peace of mind.</p>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h4>Free Site Visit</h4>
                    <p>Get accurate measurements and quotation at no cost. Our experts visit your location for assessment.</p>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <h4>Best Prices</h4>
                    <p>Competitive pricing with transparent quotations. No hidden charges, no surprises.</p>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <h4>Quick Service</h4>
                    <p>Same-day installation available. We value your time and complete projects efficiently.</p>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4>24/7 Support</h4>
                    <p>Customer support available round the clock. We're here whenever you need us.</p>
                </div>
                
                <div class="feature-box">
                    <div class="feature-icon">
                        <i class="fas fa-thumbs-up"></i>
                    </div>
                    <h4>100% Satisfaction</h4>
                    <p>We don't consider the job done until you're completely satisfied with our work.</p>
                </div>
            </div>
        </section>
        
        <!-- Our Services -->
        <section class="services-overview">
            <h2 class="section-title">Our Services</h2>
            <p class="section-subtitle">Comprehensive Safety Solutions for Every Need</p>
            
            <div class="services-list">
                <?php
                $services = $db->fetchAll("SELECT * FROM services WHERE is_active = 1 ORDER BY display_order ASC");
                foreach ($services as $service):
                ?>
                    <div class="service-item">
                        <div class="service-icon">
                            <i class="<?php echo htmlspecialchars($service['icon_class']); ?>"></i>
                        </div>
                        <div class="service-content">
                            <h4><?php echo htmlspecialchars($service['service_name']); ?></h4>
                            <p>Professional installation with quality materials and expert workmanship.</p>
                            <a href="service-pages/<?php echo $service['service_slug']; ?>.php" class="service-link">
                                View Details <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
        
        <!-- Testimonials Preview -->
        <section class="testimonials-preview">
            <h2 class="section-title">What Our Customers Say</h2>
            <p class="section-subtitle">Real Reviews from Real Customers</p>
            
            <div class="testimonials-grid">
                <?php
                // Load approved reviews from JSON files (same source as reviews.php)
                $reviews = [];
                $reviews_dir = __DIR__ . '/data/reviews/';
                if (is_dir($reviews_dir)) {
                    foreach (glob($reviews_dir . '*.json') as $file) {
                        if (basename($file) === 'stats.json') continue;
                        $r = json_decode(file_get_contents($file), true);
                        if ($r && isset($r['customer_name'], $r['review_text'])
                            && isset($r['status']) && $r['status'] === 'approved') {
                            $reviews[] = $r;
                        }
                    }
                    usort($reviews, function($a, $b) {
                        return strtotime($b['created_at']) - strtotime($a['created_at']);
                    });
                    $reviews = array_slice($reviews, 0, 3);
                }
                
                if (count($reviews) > 0):
                    foreach ($reviews as $review):
                ?>
                    <div class="testimonial-card">
                        <div class="quote-icon"><i class="fas fa-quote-left"></i></div>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star<?php echo $i <= $review['rating'] ? '' : '-o'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                        <p class="review-text"><?php echo htmlspecialchars($review['review_text']); ?></p>
                        <div class="reviewer">
                            <strong><?php echo htmlspecialchars($review['customer_name']); ?></strong>
                            <?php if (!empty($review['area'])): ?>
                                <span><?php echo htmlspecialchars($review['area']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php 
                    endforeach;
                else:
                ?>
                    <div class="no-reviews">
                        <i class="fas fa-comment-slash"></i>
                        <p>Be the first to review our services!</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="text-center" style="margin-top: 40px;">
                <a href="reviews.php" class="btn btn-primary">
                    <i class="fas fa-star"></i> View All Reviews
                </a>
            </div>
        </section>
        
        <!-- CTA Section -->
        <div class="contact-cta">
            <div class="cta-card">
                <div class="cta-content">
                    <h2>Ready to Secure Your Space?</h2>
                    <p>Get professional installation services across Chennai. Free consultation & quotation!</p>
                    <div class="cta-buttons">
                        <a href="tel:+91<?php echo COMPANY_PHONE; ?>" class="btn btn-white">
                            <i class="fas fa-phone"></i> Call +91 99123 99224
                        </a>
                        <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>" class="btn btn-success" target="_blank">
                            <i class="fab fa-whatsapp"></i> WhatsApp Us
                        </a>
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-envelope"></i> Contact Form
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/pages.css">

<?php include 'includes/modern-footer.php'; ?>
