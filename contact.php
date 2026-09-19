<?php
/**
 * GCM Netting Solutions - Contact Page
 * Contact form with email delivery
 */

define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

// Prevent caching so CSRF token is always fresh per user session
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

$db = Database::getInstance();

$page_title = 'Contact Us';
$meta_description = 'Contact GCM Netting Solutions for professional installation services in Chennai. Get free quotation, site visit, and expert consultation. Call 9912399224 or WhatsApp us.';
$meta_keywords = 'contact GCM safety nets, safety nets Chennai contact, pigeon nets contact, free quotation, site visit';

// Get service from URL parameter (if coming from specific service page)
$selected_service = isset($_GET['service']) ? sanitize_input($_GET['service']) : '';

$current_page = 'contact';
$additional_css = ['assets/css/contact.css', 'assets/css/branches.css'];

include 'includes/modern-header.php';
?>

<div class="contact-page">
    <!-- Page Header -->
    <div class="contact-header">
        <div class="container">
            <h1><i class="fas fa-envelope"></i> Contact Us</h1>
            <p>Get in touch for free quotation and expert consultation</p>
        </div>
    </div>
    
    <div class="container">
        <div class="contact-content">
            <!-- Contact Info Cards -->
            <div class="contact-info-section">
                <div class="info-cards-grid">
                    <!-- Phone Card -->
                    <div class="info-card">
                        <div class="info-icon phone">
                            <i class="fas fa-phone"></i>
                        </div>
                        <h3>Call Us</h3>
                        <a href="tel:<?php echo COMPANY_PHONE; ?>" class="info-link">
                            +91-<?php echo COMPANY_PHONE; ?>
                        </a>
                        <p>Mon - Sun: 8:00 AM - 8:00 PM</p>
                    </div>
                    
                    <!-- WhatsApp Card -->
                    <div class="info-card">
                        <div class="info-icon whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </div>
                        <h3>WhatsApp</h3>
                        <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>?text=Hi, I'm interested in your safety net services" class="info-link" target="_blank">
                            +91-<?php echo COMPANY_PHONE; ?>
                        </a>
                        <p>Quick Response Guaranteed</p>
                    </div>
                    
                    <!-- Email Card -->
                    <div class="info-card">
                        <div class="info-icon email">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <h3>Email Us</h3>
                        <a href="mailto:<?php echo COMPANY_EMAIL; ?>" class="info-link">
                            <?php echo COMPANY_EMAIL; ?>
                        </a>
                        <p>We'll respond within 24 hours</p>
                    </div>
                    
                    <!-- Location Card -->
                    <div class="info-card">
                        <div class="info-icon location">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3>Visit Us</h3>
                        <p class="info-address"><?php echo COMPANY_ADDRESS; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Main Contact Section -->
            <div class="contact-main-section">
                <div class="contact-grid">
                    <!-- Contact Form -->
                    <div class="contact-form-section">
                        <div class="form-card">
                            <div class="form-header">
                                <h2><i class="fas fa-paper-plane"></i> Send Us a Message</h2>
                                <p>Fill out the form below and we'll get back to you shortly</p>
                            </div>
                            
                            <form class="contact-form" id="contactForm" novalidate>
                                <input type="hidden" name="form_type" value="contact_page">
                                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                
                                <!-- Honeypot for spam protection -->
                                <input type="text" name="website" class="honeypot" tabindex="-1" autocomplete="off">
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="name">
                                            <i class="fas fa-user"></i> Full Name *
                                        </label>
                                        <input type="text" id="name" name="name" class="form-control" placeholder="Enter your full name" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="phone">
                                            <i class="fas fa-phone"></i> Phone Number *
                                        </label>
                                        <input type="tel" id="phone" name="phone" class="form-control" placeholder="10-digit mobile number" required pattern="[6-9][0-9]{9}" title="Please enter a valid Indian mobile number starting with 6, 7, 8, or 9">
                                    </div>
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="email">
                                            <i class="fas fa-envelope"></i> Email Address *
                                        </label>
                                        <input type="email" id="email" name="email" class="form-control" placeholder="your@email.com" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="service">
                                            <i class="fas fa-cog"></i> Service Required *
                                        </label>
                                        <select id="service" name="service" class="form-control" required>
                                            <option value="">Select Service</option>
                                            <?php
                                            $mainServices = [
                                                'balcony-safety-nets'  => 'Balcony Safety Nets',
                                                'pigeon-nets'          => 'Pigeon Nets',
                                                'children-safety-nets' => 'Children Safety Nets',
                                                'invisible-grills'     => 'Invisible Grills',
                                                'sports-nets'          => 'Sports Nets',
                                                'cloth-hangers'        => 'Cloth Hangers',
                                            ];
                                            foreach ($mainServices as $slug => $label):
                                            ?>
                                                <option value="<?php echo $slug; ?>" <?php echo ($selected_service === $slug) ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="area">
                                        <i class="fas fa-map-marker-alt"></i> Your Location (Area in Chennai) *
                                    </label>
                                    <input type="text" id="area" name="area" class="form-control" placeholder="e.g., Anna Nagar, T Nagar, Velachery" required>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">
                                        <i class="fas fa-comment"></i> Your Message *
                                    </label>
                                    <textarea id="message" name="message" class="form-control" rows="5" placeholder="Tell us about your requirements, measurements, or any questions..." required></textarea>
                                </div>
                                
                                <div class="form-checkbox">
                                    <input type="checkbox" id="terms" name="terms" required>
                                    <label for="terms">
                                        I agree to receive updates and quotations via email/SMS
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary btn-large btn-block">
                                    <i class="fas fa-paper-plane"></i> Send Message
                                </button>
                                
                                <div class="form-note">
                                    <i class="fas fa-lock"></i>
                                    <span>Your information is safe and will never be shared with third parties</span>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Quick Response Guarantee -->
                        <div class="response-guarantee">
                            <div class="guarantee-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="guarantee-content">
                                <h4>Quick Response Guaranteed</h4>
                                <p>We typically respond within 1-2 hours during business hours</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Why Choose Us Section -->
                    <div class="why-choose-section">
                        <div class="choose-card">
                            <h3><i class="fas fa-star"></i> Why Choose GCM Netting Solutions?</h3>
                            
                            <div class="feature-list">
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Free Site Visit</h4>
                                        <p>Get accurate measurements and quotation at no cost</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Same Day Installation</h4>
                                        <p>Quick and professional installation service</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>5 Year Warranty</h4>
                                        <p>Comprehensive warranty on all products</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Quality Materials</h4>
                                        <p>Premium HDPE nets with UV protection</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Expert Team</h4>
                                        <p>Trained professionals with 10+ years experience</p>
                                    </div>
                                </div>
                                
                                <div class="feature-item">
                                    <div class="feature-icon">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                    <div class="feature-content">
                                        <h4>Best Prices</h4>
                                        <p>Competitive pricing with no hidden charges</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Service Areas -->
                        <div class="service-areas-card">
                            <h3><i class="fas fa-map-marked-alt"></i> We Serve All Chennai</h3>
                            <p class="areas-intro">Professional installation services across 150+ areas in Chennai</p>
                            <div class="areas-tags">
                                <?php
                                $popular_areas = $db->fetchAll("SELECT area_name FROM service_areas WHERE is_active = 1 ORDER BY area_name LIMIT 20");
                                if ($popular_areas && count($popular_areas) > 0) {
                                    foreach ($popular_areas as $area):
                                ?>
                                    <span class="area-tag"><?php echo htmlspecialchars($area['area_name']); ?></span>
                                <?php 
                                    endforeach;
                                } else {
                                    // Fallback to popular areas if database is empty
                                    $fallback_areas = ['Anna Nagar', 'T Nagar', 'Velachery', 'Adyar', 'Tambaram', 'Porur', 'Mylapore', 'Nungambakkam', 'Guindy', 'Besant Nagar', 'Sholinganallur', 'Perungudi', 'Thoraipakkam', 'Medavakkam', 'Chromepet', 'Pallavaram', 'Ambattur', 'Avadi', 'Poonamallee', 'Kilpauk', 'Kodambakkam', 'Alwarpet'];
                                    foreach ($fallback_areas as $area):
                                ?>
                                    <span class="area-tag"><?php echo $area; ?></span>
                                <?php 
                                    endforeach;
                                }
                                ?>
                                <span class="area-tag more">+600 More Areas</span>
                            </div>
                            <a href="all-areas.php" class="btn btn-secondary btn-sm">
                                <i class="fas fa-map"></i> View All Areas
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Branch Locations Section -->
            <div class="branch-locations-section">
                <h2><i class="fas fa-building"></i> Our Branch Locations Across Chennai</h2>
                <p class="section-description">Visit any of our conveniently located branches for consultation, quotation, and quality service across Chennai</p>
                
                <div class="branches-grid">
                    <!-- Head Office -->
                    <div class="branch-card head-office">
                        <div class="branch-badge">HEAD OFFICE</div>
                        <div class="branch-icon">
                            <i class="fas fa-landmark"></i>
                        </div>
                        <h4>Anna Salai Head Office</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>No. 42, Anna Salai,<br>Mount Road, Anna Salai,<br>Chennai - 600002,<br>Tamil Nadu, India</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>

                    <!-- Branch 1: T Nagar -->
                    <div class="branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4>T Nagar Branch</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>No. 15, Usman Road,<br>Near Panagal Park,<br>T Nagar, Chennai - 600017,<br>Tamil Nadu</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>

                    <!-- Branch 2: Anna Nagar -->
                    <div class="branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4>Anna Nagar Branch</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>2nd Avenue, Near Roundtana,<br>Anna Nagar West,<br>Chennai - 600040,<br>Tamil Nadu</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>

                    <!-- Branch 3: Velachery -->
                    <div class="branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4>Velachery Branch</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>100 Feet Bypass Road,<br>Near Phoenix Marketcity,<br>Velachery, Chennai - 600042,<br>Tamil Nadu</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>

                    <!-- Branch 4: Adyar -->
                    <div class="branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4>Adyar Branch</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Lattice Bridge (LB) Road,<br>Near Adyar Signal,<br>Adyar, Chennai - 600020,<br>Tamil Nadu</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>

                    <!-- Branch 5: Tambaram -->
                    <div class="branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4>Tambaram Branch</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>GST Road, Near Railway Station,<br>West Tambaram,<br>Tambaram, Chennai - 600045,<br>Tamil Nadu</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>

                    <!-- Branch 6: Porur -->
                    <div class="branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4>Porur Branch</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Mount Poonamallee Road,<br>Near Porur Junction,<br>Porur, Chennai - 600116,<br>Tamil Nadu</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>

                    <!-- Branch 7: OMR -->
                    <div class="branch-card">
                        <div class="branch-icon">
                            <i class="fas fa-store"></i>
                        </div>
                        <h4>OMR Branch</h4>
                        <div class="branch-address">
                            <i class="fas fa-map-marker-alt"></i>
                            <p>Rajiv Gandhi Salai (OMR),<br>Near Sholinganallur Junction,<br>Chennai - 600119,<br>Tamil Nadu</p>
                        </div>
                        <div class="branch-contact">
                            <a href="tel:+919912399224" class="branch-phone">
                                <i class="fas fa-phone"></i> +91 99123 99224
                            </a>
                        </div>
                    </div>
                </div>

                <div class="branch-note">
                    <i class="fas fa-info-circle"></i>
                    <p><strong>Note:</strong> All branches are open Monday to Sunday, 8:00 AM - 8:00 PM. Free site visit and consultation available from any branch.</p>
                </div>
            </div>
            
            <!-- Google Map Section -->
            <div class="map-section">
                <h2><i class="fas fa-map-marker-alt"></i> Find Us on Map - All Branch Locations</h2>
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d243647.3160410283!2d80.06893089453125!3d13.047508700000012!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bcb99daeaebd2c7%3A0xae93b78392bafbc2!2sChennai%2C%20Tamil Nadu!5e0!3m2!1sen!2sin!4v1699000000000!5m2!1sen!2sin" 
                        width="100%" 
                        height="550" 
                        style="border:0; border-radius: 12px;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                <p class="map-note">
                    <i class="fas fa-info-circle"></i> 
                    Use the quick direction links below to navigate to any of our 8 branch locations across Chennai.
                </p>
                
                <!-- Branch Quick Links -->
                <div class="branch-quick-links">
                    <h3>Quick Direction Links:</h3>
                    <div class="quick-links-grid">
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.3569,78.5378" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Head Office - Saroornagar
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.4947,78.3985" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Porur Branch
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.4290,78.4490" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Panjagutta Branch
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.4010,78.3845" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Mylapore Branch
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.5378,78.4903" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Madipakkam Branch
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.4421,78.5494" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Nacharam Branch
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.3669,78.4159" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Saidapet Branch
                        </a>
                        <a href="https://www.google.com/maps/dir/?api=1&destination=17.4062,78.5591" target="_blank" class="direction-link">
                            <i class="fas fa-map-marker-alt"></i> Uppal Branch
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- FAQ Section -->
            <div class="contact-faq-section">
                <h2><i class="fas fa-question-circle"></i> Frequently Asked Questions</h2>
                <div class="faq-grid">
                    <div class="faq-item">
                        <h4><i class="fas fa-question"></i> How long does installation take?</h4>
                        <p>Most installations are completed within 2-4 hours, depending on the area and complexity.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4><i class="fas fa-question"></i> Do you provide warranty?</h4>
                        <p>Yes, we provide a comprehensive 5-year warranty on all our products and installation.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4><i class="fas fa-question"></i> Is site visit really free?</h4>
                        <p>Absolutely! We offer free site visits for accurate measurement and quotation across Chennai.</p>
                    </div>
                    
                    <div class="faq-item">
                        <h4><i class="fas fa-question"></i> What payment methods do you accept?</h4>
                        <p>We accept cash, UPI, bank transfer, and all major credit/debit cards.</p>
                    </div>
                </div>
                <div class="text-center" style="margin-top: 30px;">
                    <a href="faqs.php" class="btn btn-secondary">
                        <i class="fas fa-question-circle"></i> View All FAQs
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Contact Success Popup ─────────────────────────────────── -->
<div id="contactSuccessOverlay" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.65); backdrop-filter:blur(6px); z-index:99999; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:22px; padding:48px 40px; max-width:480px; width:90%; text-align:center; box-shadow:0 24px 80px rgba(0,0,0,0.3); animation:csPopIn .35s cubic-bezier(.34,1.56,.64,1) both;">
        <div style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#10b981,#059669); display:flex; align-items:center; justify-content:center; margin:0 auto 22px; box-shadow:0 8px 28px rgba(16,185,129,.4);">
            <i class="fas fa-check" style="font-size:38px; color:#fff;"></i>
        </div>
        <h2 style="font-size:26px; color:#1e293b; margin:0 0 12px; font-weight:800;">Message Sent!</h2>
        <p style="color:#64748b; font-size:16px; margin:0 0 8px;">Thank you! Your message has been sent successfully.</p>
        <p style="color:#64748b; font-size:15px; margin:0 0 28px;">We will contact you within <strong style="color:#10b981;">1–2 hours</strong>.</p>
        <div style="display:flex; gap:12px; justify-content:center; flex-wrap:wrap;">
            <a href="tel:+919912399224" style="display:inline-flex; align-items:center; gap:8px; padding:12px 22px; background:linear-gradient(135deg,#10b981,#059669); color:#fff; border-radius:10px; text-decoration:none; font-weight:700; font-size:15px; box-shadow:0 4px 14px rgba(16,185,129,.4);">
                <i class="fas fa-phone"></i> Call Us Now
            </a>
            <button onclick="document.getElementById('contactSuccessOverlay').style.display='none'" style="padding:12px 22px; background:#f1f5f9; color:#475569; border:none; border-radius:10px; font-weight:700; font-size:15px; cursor:pointer;">
                Close
            </button>
        </div>
    </div>
</div>

<style>
@keyframes csPopIn { from{opacity:0;transform:scale(.85)} to{opacity:1;transform:scale(1)} }
</style>

<script>
(function() {
    var form   = document.getElementById('contactForm');
    var overlay = document.getElementById('contactSuccessOverlay');
    if (!form) return;

    // Remove any existing inline error banner
    function clearError() {
        var old = document.getElementById('contactErrorBanner');
        if (old) old.remove();
    }

    function showError(msg) {
        clearError();
        var div = document.createElement('div');
        div.id = 'contactErrorBanner';
        div.style.cssText = 'background:#fee2e2;color:#991b1b;padding:14px 18px;border-radius:10px;margin-bottom:16px;font-size:14px;font-weight:600;';
        div.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + msg;
        form.insertBefore(div, form.firstChild);
        div.scrollIntoView({behavior:'smooth', block:'center'});
    }

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearError();

        var btn = form.querySelector('button[type="submit"]');
        var originalHTML = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...'; }

        var data = new FormData(form);

        fetch('api/contact-handler.php', {
            method: 'POST',
            body: data,
            credentials: 'same-origin'
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            if (res.success) {
                form.reset();
                overlay.style.display = 'flex';
            } else {
                showError(res.message || 'Something went wrong. Please try again.');
            }
        })
        .catch(function() {
            showError('Network error. Please check your connection and try again.');
        })
        .finally(function() {
            if (btn) { btn.disabled = false; btn.innerHTML = originalHTML; }
        });
    });

    // Close overlay on background click
    overlay.addEventListener('click', function(e) {
        if (e.target === overlay) overlay.style.display = 'none';
    });
})();
</script>

<?php include 'includes/modern-footer.php'; ?>
