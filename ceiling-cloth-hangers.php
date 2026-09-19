<?php
/**
 * Ceiling Cloth Hangers - Service Pillar Page
 * Shows all 188 Chennai areas for this service
 */
define('GCM_INIT', true);
require_once 'config/config.php';

$page_title = "Ceiling Cloth Hangers in Chennai | GCM Netting Solutions";
$meta_description = "Professional Ceiling Cloth Hangers installation services across Chennai. Quality materials, expert installation, 5-year warranty. Call +91 99123 99224 for free quote.";
$meta_keywords = "ceiling-cloth-hangers, ceiling-cloth-hangers chennai, ceiling-cloth-hangers installation, safety nets chennai";
$current_page = 'services';
$active_service = 'ceiling-cloth-hangers';

include 'includes/modern-header.php';
?>

<div class="pillar-page">
    <div class="container">
        <!-- Hero Section -->
        <div class="pillar-hero">
            <h1>Ceiling Cloth Hangers in Chennai</h1>
            <p class="lead">
                Professional Ceiling Cloth Hangers installation services across all areas of Chennai. 
                Quality materials, expert installation, and 5-year warranty.
            </p>
            <div class="hero-actions">
                <a href="tel:+919912399224" class="btn btn-primary btn-lg">
                    <i class="fas fa-phone-alt"></i> Call: +91 99123 99224
                </a>
                <a href="<?php echo SITE_URL; ?>/contact" class="btn btn-secondary btn-lg">
                    <i class="fas fa-envelope"></i> Get Free Quote
                </a>
            </div>
        </div>

        <!-- Service Information (AI Generated) -->
        <div class="service-info-section">
            <div class="row">
                <div class="col-md-8">
                    <h2>Ceiling Cloth Hangers in Chennai: Dry Your Clothes Efficiently with GCM Netting Solutions</h2>

<p>Tired of struggling with limited space and unpredictable Chennai weather when drying your clothes? GCM Netting Solutions offers premium <strong>Ceiling Cloth Hangers</strong>, the perfect solution for maximizing space and ensuring efficient drying in your Chennai home or building. Our ceiling cloth hangers are designed to utilize unused vertical space, allowing you to dry your laundry indoors, protected from the dust, pollution, and sudden rain showers that are common in Chennai.</p>

<p>Ceiling cloth hangers provide a convenient and organized way to dry your clothes. They are particularly beneficial in apartments, balconies, and homes with limited outdoor drying space. In Chennai's humid climate, proper ventilation is crucial for preventing mold and mildew. Our ceiling cloth hangers promote air circulation around your clothes, ensuring faster and more hygienic drying. Say goodbye to bulky drying racks and hello to a clean, clutter-free drying solution!</p>

<h3>Why Choose GCM Netting Solutions for Ceiling Cloth Hangers in Chennai?</h3>

<ul>
    <li><i class="fas fa-check-circle"></i> <strong>15+ Years of Experience:</strong> Trusted provider of safety net and home solutions in Chennai.</li>
    <li><i class="fas fa-check-circle"></i> <strong>Premium Quality Materials:</strong> Durable and rust-resistant materials built to withstand Chennai's climate. Our hangers are also UV protected to prevent degradation from sunlight.</li>
    <li><i class="fas fa-check-circle"></i> <strong>Professional Installation Team:</strong> Expert technicians ensure secure and reliable installation, tailored to your specific ceiling type and space.</li>
    <li><i class="fas fa-check-circle"></i> <strong>5-Year Warranty:</strong> We stand behind our work with a comprehensive 5-year warranty on all ceiling cloth hanger installations.</li>
    <li><i class="fas fa-check-circle"></i> <strong>Same-Day Service:</strong> Quick and efficient service available across all of Chennai, minimizing disruption to your daily routine.</li>
    <li><i class="fas fa-check-circle"></i> <strong>Competitive Pricing:</strong> Transparent pricing with no hidden costs. Get the best value for your investment in a high-quality drying solution.</li>
</ul>

<p><strong>Service Coverage:</strong> We proudly serve all 188+ areas across Chennai, from Anna Nagar to Tambaram and beyond. Our services cater to residential apartments, commercial buildings, and industrial properties. We guarantee a quick response time in all zones of Chennai and offer free consultation and site visits to assess your specific needs.</p>

<h3>Why Ceiling Cloth Hangers Matter in Chennai</h3>

<p>Chennai's climate presents unique challenges for drying clothes. The high humidity levels can significantly prolong drying times, leading to musty odors and potential mold growth. Traditional drying methods, such as clotheslines or floor-standing racks, are often impractical due to limited space and exposure to dust and pollution. Ceiling cloth hangers offer a practical and hygienic alternative, utilizing vertical space and promoting better air circulation, crucial for effectively drying clothes in Chennai's environment.</p>

<p>Many homes and apartments in Chennai face space constraints, making it difficult to dedicate an area solely for drying clothes. Our ceiling cloth hangers address this issue by providing a space-saving solution that integrates seamlessly into your existing living space. Furthermore, they protect your clothes from the elements, ensuring they dry clean and fresh, regardless of the weather conditions outside. Invest in a GCM Netting Solutions ceiling cloth hanger and experience the convenience and efficiency of a modern drying solution tailored for Chennai homes.</p>
                </div>
                <div class="col-md-4">
                    <div class="contact-card">
                        <h3>Quick Contact</h3>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Phone</strong>
                                <a href="tel:+919912399224">+91 99123 99224</a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email</strong>
                                <a href="mailto:<?php echo COMPANY_EMAIL; ?>"><?php echo COMPANY_EMAIL; ?></a>
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Address</strong>
                                <p><?php echo COMPANY_ADDRESS; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Areas Section -->
        <div class="areas-section">
            <h2>Select Your Area</h2>
            <p class="section-subtitle">
                We provide Ceiling Cloth Hangers installation services in all areas of Chennai. 
                Click on your area to view specific information and pricing.
            </p>
            
            <div class="areas-grid">
                <?php
                $areas_data = include 'config/all-areas';
                foreach ($areas_data as $area):
                    $area_slug = $area['slug'];
                    $area_name = $area['area'];
                    $zone = $area['zone'];
                    $area_url = SITE_URL . '/ceiling-cloth-hangers-in-' . $area_slug;
                ?>
                <a href="<?php echo $area_url; ?>" class="area-button">
                    <div class="area-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="area-name"><?php echo $area_name; ?></div>
                    <div class="area-zone"><?php echo $zone; ?></div>
                    <div class="area-arrow">
                        <i class="fas fa-chevron-right"></i>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Related Services -->
        <div class="related-services-section">
            <h2>Other Popular Services</h2>
            <div class="services-grid">
                <div class="service-card">
                    <i class="fas fa-dove"></i>
                    <h3>Pigeon Nets</h3>
                    <p>Professional pigeon control solutions</p>
                    <a href="<?php echo SITE_URL; ?>/pigeon-nets" class="service-link">View Service →</a>
                </div>
                <div class="service-card">
                    <i class="fas fa-feather"></i>
                    <h3>Bird Nets</h3>
                    <p>Complete bird protection solutions</p>
                    <a href="<?php echo SITE_URL; ?>/bird-nets" class="service-link">View Service →</a>
                </div>
                <div class="service-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Safety Nets</h3>
                    <p>Balcony & construction safety solutions</p>
                    <a href="<?php echo SITE_URL; ?>/safety-nets" class="service-link">View Service →</a>
                </div>
                <div class="service-card">
                    <i class="fas fa-futbol"></i>
                    <h3>Sports Nets</h3>
                    <p>Cricket, football & practice nets</p>
                    <a href="<?php echo SITE_URL; ?>/sports-nets" class="service-link">View Service →</a>
                </div>
                <div class="service-card">
                    <i class="fas fa-border-none"></i>
                    <h3>Invisible Grills</h3>
                    <p>Modern window & balcony protection</p>
                    <a href="<?php echo SITE_URL; ?>/invisible-grills" class="service-link">View Service →</a>
                </div>
                <div class="service-card">
                    <i class="fas fa-tshirt"></i>
                    <h3>Cloth Hangers</h3>
                    <p>Space-saving ceiling cloth drying solutions</p>
                    <a href="<?php echo SITE_URL; ?>/cloth-hangers.php" class="service-link">View Service →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Pillar Page Styles - Enhanced with Modern Hover Effects */
.pillar-page { 
    padding: 40px 0; 
    background: linear-gradient(180deg, #F8FAFC 0%, #FFFFFF 100%);
}

.pillar-hero { 
    text-align: center; 
    padding: 80px 40px; 
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
    color: #FFF; 
    border-radius: 24px; 
    margin-bottom: 60px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
    animation: fadeInUp 0.6s ease-out;
}

.pillar-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: radial-gradient(circle at top right, rgba(255,255,255,0.1) 0%, transparent 50%);
    pointer-events: none;
}

.pillar-hero h1 { 
    font-size: 56px; 
    margin-bottom: 24px; 
    font-weight: 800;
    text-shadow: 0 2px 20px rgba(0,0,0,0.2);
    animation: fadeInUp 0.8s ease-out 0.2s both;
}

.pillar-hero .lead { 
    font-size: 22px; 
    margin-bottom: 40px; 
    color: #FFFFFF !important; /* Force white color */
    opacity: 1; /* Changed from 0.95 for better visibility */
    max-width: 800px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
    animation: fadeInUp 0.8s ease-out 0.4s both;
    background: transparent; /* Remove any background */
    text-shadow: 0 2px 4px rgba(0,0,0,0.2); /* Add subtle shadow for readability */
}

.hero-actions { 
    display: flex; 
    gap: 20px; 
    justify-content: center;
    flex-wrap: wrap;
    animation: fadeInUp 0.8s ease-out 0.6s both;
}

.btn-lg {
    padding: 16px 32px;
    font-size: 18px;
    font-weight: 600;
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

.btn-primary {
    background: #10B981;
    color: white;
    border: 2px solid #10B981;
}

.btn-primary:hover {
    background: #059669;
    border-color: #059669;
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
}

.btn-secondary {
    background: white;
    color: #667eea;
    border: 2px solid white;
}

.btn-secondary:hover {
    background: #F3F4F6;
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(255,255,255,0.4);
}

.service-info-section { 
    margin-bottom: 80px;
    animation: fadeInUp 1s ease-out;
}

.service-info-section h2 {
    font-size: 36px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 20px;
    position: relative;
    padding-bottom: 16px;
}

.service-info-section h2::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: 0;
    width: 80px;
    height: 4px;
    background: linear-gradient(90deg, #10B981 0%, #059669 100%);
    border-radius: 2px;
}

.service-info-section h3 {
    font-size: 28px;
    font-weight: 600;
    color: #334155;
    margin-top: 32px;
    margin-bottom: 16px;
    padding-left: 16px;
    border-left: 4px solid #3B82F6;
    transition: all 0.3s ease;
}

.service-info-section h3:hover {
    color: #3B82F6;
    padding-left: 24px;
}

.service-info-section p {
    font-size: 17px;
    line-height: 1.8;
    color: #475569;
    margin-bottom: 20px;
}

/* Remove bullet points from AI-generated lists */
.service-info-section ul {
    list-style: none !important;
    padding-left: 0 !important;
    margin: 24px 0;
}

.service-info-section li {
    padding: 18px 20px;
    margin-bottom: 14px;
    background: linear-gradient(135deg, #F0FDF4 0%, #FFFFFF 100%);
    border-left: 4px solid #10B981;
    border-radius: 12px;
    font-size: 16px;
    color: #334155;
    line-height: 1.7;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
}

.service-info-section li::before {
    content: '✓';
    position: absolute;
    left: -40px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #10B981;
    font-weight: bold;
    opacity: 0;
    transition: all 0.3s ease;
}

.service-info-section li:hover {
    background: linear-gradient(135deg, #D1FAE5 0%, #F0FDF4 100%);
    border-left-width: 6px;
    padding-left: 24px;
    transform: translateX(6px);
    box-shadow: 0 6px 20px rgba(16, 185, 129, 0.15);
}

.service-info-section li:hover::before {
    left: 8px;
    opacity: 1;
}

/* Style for strong tags */
.service-info-section strong {
    color: #1E293B;
    font-weight: 700;
}

.feature-list { 
    list-style: none; 
    padding: 0;
    margin-top: 24px;
}

.feature-list li { 
    padding: 16px;
    display: flex; 
    align-items: center; 
    gap: 16px;
    background: white;
    border-radius: 12px;
    margin-bottom: 12px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.feature-list li:hover {
    background: linear-gradient(135deg, #F0FDF4 0%, #ECFDF5 100%);
    border-color: #10B981;
    transform: translateX(8px);
    box-shadow: 0 4px 16px rgba(16, 185, 129, 0.1);
}

.feature-list i { 
    color: #10B981; 
    font-size: 24px;
    transition: all 0.3s ease;
}

.feature-list li:hover i {
    transform: scale(1.2) rotate(5deg);
}

.contact-card { 
    background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%);
    padding: 32px; 
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 2px solid #E0E7FF;
    transition: all 0.3s ease;
}

.contact-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(59, 130, 246, 0.15);
    border-color: #3B82F6;
}

.contact-card h3 {
    font-size: 24px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 24px;
}

.contact-item { 
    display: flex; 
    gap: 16px; 
    margin-bottom: 24px;
    padding: 12px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.contact-item:hover {
    background: rgba(59, 130, 246, 0.05);
    padding-left: 16px;
}

.contact-item i { 
    font-size: 28px; 
    color: #3B82F6;
    transition: all 0.3s ease;
}

.contact-item:hover i {
    transform: scale(1.15) rotate(-5deg);
}

.contact-item a {
    color: #3B82F6;
    text-decoration: none;
    transition: all 0.3s ease;
}

.contact-item a:hover {
    color: #2563EB;
    text-decoration: underline;
}

.areas-section { 
    margin-bottom: 80px;
    animation: fadeInUp 1.2s ease-out;
}

.areas-section h2 {
    font-size: 42px;
    font-weight: 800;
    text-align: center;
    margin-bottom: 16px;
    color: #1E293B;
}

.section-subtitle { 
    text-align: center; 
    color: #64748B; 
    margin-bottom: 48px;
    font-size: 18px;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

.areas-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); 
    gap: 20px;
}

.area-button { 
    display: flex; 
    align-items: center; 
    gap: 14px; 
    padding: 20px; 
    background: white;
    border: 2px solid #E2E8F0; 
    border-radius: 16px; 
    text-decoration: none; 
    color: #1E293B; 
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.area-button::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(59, 130, 246, 0.1), transparent);
    transition: left 0.5s ease;
}

.area-button:hover::before {
    left: 100%;
}

.area-button:hover { 
    border-color: #3B82F6; 
    background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%);
    transform: translateY(-6px) scale(1.02);
    box-shadow: 0 12px 32px rgba(59, 130, 246, 0.25);
}

.area-icon { 
    width: 48px; 
    height: 48px; 
    background: linear-gradient(135deg, #3B82F6 0%, #2563EB 100%); 
    border-radius: 12px; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    color: #FFF;
    font-size: 20px;
    transition: all 0.4s ease;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
}

.area-button:hover .area-icon {
    transform: rotate(360deg) scale(1.1);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
}

.area-name { 
    flex: 1; 
    font-weight: 600; 
    font-size: 16px;
    transition: all 0.3s ease;
}

.area-button:hover .area-name {
    color: #3B82F6;
}

.area-zone { 
    font-size: 12px; 
    color: #64748B; 
    padding: 6px 12px; 
    background: #F1F5F9; 
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
}

.area-button:hover .area-zone {
    background: #DBEAFE;
    color: #1E40AF;
}

.area-arrow { 
    color: #94A3B8;
    transition: all 0.3s ease;
}

.area-button:hover .area-arrow {
    color: #3B82F6;
    transform: translateX(6px);
}

/* Animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Related Services Section */
.related-services-section {
    margin-top: 80px;
    padding: 60px 20px; /* Reduced from 40px to 20px */
    background: linear-gradient(135deg, #F8FAFC 0%, #EFF6FF 100%);
    border-radius: 24px;
}

.related-services-section h2 {
    font-size: 42px;
    font-weight: 800;
    text-align: center;
    margin-bottom: 40px;
    color: #1E293B;
}

.services-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr); /* Fixed 5 columns */
    gap: 16px; /* Reduced gap from 24px */
}

.service-card {
    background: white;
    padding: 32px 24px;
    border-radius: 16px;
    text-align: center;
    border: 2px solid #E2E8F0;
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    overflow: hidden;
}

.service-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(59, 130, 246, 0.1) 0%, transparent 70%);
    transform: scale(0);
    transition: transform 0.6s ease;
}

.service-card:hover::before {
    transform: scale(1);
}

.service-card:hover {
    transform: translateY(-8px);
    border-color: #3B82F6;
    box-shadow: 0 16px 48px rgba(59, 130, 246, 0.2);
}

.service-card i {
    font-size: 48px;
    color: #3B82F6;
    margin-bottom: 20px;
    display: block;
    transition: all 0.4s ease;
}

.service-card:hover i {
    transform: scale(1.2) rotate(10deg);
    color: #2563EB;
}

.service-card h3 {
    font-size: 22px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 12px;
    transition: color 0.3s ease;
}

.service-card:hover h3 {
    color: #3B82F6;
}

.service-card p {
    font-size: 14px;
    color: #64748B;
    margin-bottom: 20px;
    line-height: 1.6;
}

.service-link {
    display: inline-block;
    color: #3B82F6;
    text-decoration: none;
    font-weight: 600;
    font-size: 14px;
    transition: all 0.3s ease;
    position: relative;
}

.service-link::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 0;
    height: 2px;
    background: #3B82F6;
    transition: width 0.3s ease;
}

.service-card:hover .service-link::after {
    width: 100%;
}

.service-link:hover {
    color: #2563EB;
    transform: translateX(4px);
}

/* Responsive Design */
@media (max-width: 1200px) {
    .services-grid { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .pillar-hero h1 { font-size: 36px; }
    .pillar-hero .lead { font-size: 18px; }
    .areas-grid { grid-template-columns: 1fr; }
    .hero-actions { flex-direction: column; }
    .btn-lg { width: 100%; justify-content: center; }
    .services-grid { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 480px) {
    .services-grid { grid-template-columns: 1fr; }
}
</style>

<?php include 'includes/modern-footer.php'; ?>