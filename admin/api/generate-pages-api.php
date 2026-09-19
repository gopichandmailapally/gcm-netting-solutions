<?php
/**
 * Page Generation API Backend
 * Handles AI-powered page generation requests
 */

// Define GCM_INIT first to prevent config.php from dying
define('GCM_INIT', true);

// Start output buffering to catch any unwanted output
ob_start();

// Suppress all errors and warnings from being displayed
error_reporting(0);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set content type header
header('Content-Type: application/json');

// Set error handler to return JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_clean();
    echo json_encode(['error' => "PHP Error: $errstr"]);
    exit;
});

set_exception_handler(function($e) {
    ob_clean();
    echo json_encode(['error' => "Exception: " . $e->getMessage()]);
    exit;
});

// Load required files
try {
    require_once __DIR__ . '/../../config/config.php';
    require_once __DIR__ . '/../../includes/gemini-api.php';
    require_once __DIR__ . '/../../includes/ai-prompt-generator.php';
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['error' => 'Failed to load required files: ' . $e->getMessage()]);
    exit;
}

// Database connection (if not already connected)
if (!isset($conn)) {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            error_log("Database connection failed: " . $conn->connect_error);
            $conn = null; // Set to null so we can check later
        } else {
            $conn->set_charset(DB_CHARSET);
        }
    } catch (Exception $e) {
        error_log("Database exception: " . $e->getMessage());
        $conn = null;
    }
}

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

// Load configuration
$titleFormats = include __DIR__ . '/../../config/title-formats.php';

switch ($action) {
    case 'generate_single':
        generateSinglePage($input);
        break;
    
    case 'test_api':
        testAPIConnection($input);
        break;
    
    case 'get_progress':
        getGenerationProgress();
        break;
    
    default:
        ob_clean();
        echo json_encode(['error' => 'Invalid action']);
}

/**
 * Generate a single page
 */
function generateSinglePage($input) {
    global $titleFormats;
    
    $apiKey = $input['api_key'] ?? '';
    $serviceSlug = $input['service_slug'] ?? '';
    $serviceName = $input['service_name'] ?? '';
    $areaSlug = $input['area_slug'] ?? '';
    $areaName = $input['area_name'] ?? '';
    $wordCount = $input['word_count'] ?? 700;
    
    if (!$apiKey || !$serviceSlug || !$areaSlug) {
        echo json_encode(['error' => 'Missing required parameters']);
        return;
    }
    
    try {
        // Initialize AI components
        $gemini = new GeminiAPI($apiKey);
        $promptGen = new AIPromptGenerator();
        
        // Generate AI prompt
        $prompt = $promptGen->generatePagePrompt($serviceName, $serviceSlug, $areaName, $areaSlug, $wordCount);
        
        // Call Gemini API
        $startTime = microtime(true);
        $aiContent = $gemini->generateContent($prompt);
        $generationTime = microtime(true) - $startTime;
        
        // Clean markdown artifacts from AI output
        $aiContent = cleanMarkdownArtifacts($aiContent);
        
        // Select random title format
        $randomTitleFormat = $titleFormats[array_rand($titleFormats)];
        $pageTitle = str_replace(
            ['{service}', '{area}', '{company}'],
            [$serviceName, $areaName, 'GCM Netting Solutions'],
            $randomTitleFormat
        );
        
        // Create H1 (fixed format)
        $h1Heading = "{$serviceName} in {$areaName}, Chennai";
        
        // Generate meta description
        $metaDescription = generateMetaDescription($serviceName, $areaName);
        
        // Create the PHP file in protected 'generated-pages' folder
        $fileName = "{$serviceSlug}-in-{$areaSlug}.php";
        $protectedDir = __DIR__ . "/../../generated-pages/";
        
        // Ensure directory exists
        if (!is_dir($protectedDir)) {
            mkdir($protectedDir, 0755, true);
        }
        
        $filePath = $protectedDir . $fileName;
        
        $pageContent = createPageFile($pageTitle, $h1Heading, $metaDescription, $aiContent, $serviceName, $serviceSlug, $areaName, $areaSlug);
        
        // Write file with protection
        file_put_contents($filePath, $pageContent);
        chmod($filePath, 0644); // Read-only for security
        
        // Prepare page data
        $pageData = [
            'service_slug' => $serviceSlug,
            'service_name' => $serviceName,
            'area_slug' => $areaSlug,
            'area_name' => $areaName,
            'page_title' => $pageTitle,
            'meta_description' => $metaDescription,
            'h1_heading' => $h1Heading,
            'file_path' => $fileName,
            'generation_time' => $generationTime,
            'ai_tokens_used' => estimateTokens($prompt . $aiContent),
            'word_count' => str_word_count($aiContent),
            'generated_at' => date('Y-m-d H:i:s')
        ];
        
        // Save to database
        savePageToDatabase($pageData);
        
        // Log to JSON file (backup)
        logPageGeneration($pageData);
        
        // Clean any output buffer and send JSON response
        ob_clean();
        echo json_encode([
            'success' => true,
            'message' => 'Page generated successfully',
            'file_name' => $fileName,
            'generation_time' => round($generationTime, 2),
            'word_count' => str_word_count($aiContent)
        ]);
        
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Test API connection
 */
function testAPIConnection($input) {
    $apiKey = $input['api_key'] ?? '';
    
    if (!$apiKey) {
        ob_clean();
        echo json_encode(['error' => 'API key required']);
        return;
    }
    
    try {
        $gemini = new GeminiAPI($apiKey);
        $result = $gemini->testConnection();
        ob_clean();
        echo json_encode($result);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['error' => $e->getMessage()]);
    }
}

/**
 * Get generation progress
 */
function getGenerationProgress() {
    $statsFile = __DIR__ . '/../../config/generation-stats.json';
    
    ob_clean();
    if (file_exists($statsFile)) {
        $stats = json_decode(file_get_contents($statsFile), true);
        echo json_encode($stats);
    } else {
        echo json_encode([
            'total_generated' => 0,
            'last_generated' => 'Never'
        ]);
    }
}

/**
 * Clean markdown artifacts from AI-generated content
 */
function cleanMarkdownArtifacts($content) {
    // Remove markdown code fences
    $content = preg_replace('/```html\s*/i', '', $content);
    $content = preg_replace('/```\s*$/s', '', $content);
    $content = preg_replace('/```/', '', $content);
    
    // Remove any stray backticks at start/end
    $content = trim($content, '`');
    
    // Remove "html" label if it appears at the start
    $content = preg_replace('/^html\s+/i', '', $content);
    
    return trim($content);
}

/**
 * Generate meta description
 */
function generateMetaDescription($serviceName, $areaName) {
    $templates = [
        "Professional {service} installation in {area}, Chennai. Quality materials, expert installation, 5-year warranty. Call +91 99123 99224 for free quote.",
        "Expert {service} services in {area}, Chennai. Serving residential & commercial clients with premium solutions. Free consultation available.",
        "Get quality {service} installation in {area}, Chennai. Certified installers, weather-resistant materials, affordable prices. Call now!",
        "Trusted {service} providers in {area}, Chennai. Fast installation, premium materials, lifetime support. Contact +91 99123 99224.",
        "Best {service} services in {area}, Chennai. Professional installation, competitive pricing, 5-year warranty. Get your free quote today!"
    ];
    
    $template = $templates[array_rand($templates)];
    return str_replace(['{service}', '{area}'], [$serviceName, $areaName], $template);
}

/**
 * Create complete PHP page file
 */
function createPageFile($title, $h1, $metaDescription, $content, $serviceName, $serviceSlug, $areaName, $areaSlug) {
    $heroPrefixes = ['Professional','Trusted','Expert','Premium','Quality','Certified','Reliable',
                     'Superior','Elite','Top-Rated','Best','No.1','Branded','Verified','Specialized',
                     'Dependable','Proven','Award-Winning','5-Star','High-Quality'];
    $heroSubtitlePrefix = $heroPrefixes[array_rand($heroPrefixes)];
    
    $pageContent = <<<PHP
<?php
/**
 * {$title}
 * AI-Generated Content - SEO Optimized
 */
define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

// Create database connection for footer
\$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if (\$conn->connect_error) {
    \$conn = null;
} else {
    \$conn->set_charset(DB_CHARSET);
}

\$page_title = '{$title}';
\$meta_description = '{$metaDescription}';
\$meta_keywords = '{$serviceSlug}, {$serviceSlug} chennai, {$serviceSlug} installation';
\$current_page = 'services';
\$active_service = '{$serviceSlug}';

include 'includes/modern-header.php';
?>

<!-- Hero Section -->
<div class="service-hero-section">
    <div class="hero-container">
        <div class="breadcrumb">
            <a href="<?php echo SITE_URL; ?>">Home</a> » 
            <a href="<?php echo SITE_URL; ?>/services.php">Services</a> » 
            <span>{$serviceName}</span>
        </div>
        <h1 class="hero-title">{$h1}</h1>
        <p class="hero-subtitle">{$heroSubtitlePrefix} {$serviceName} Installation Services in Chennai</p>
        <div class="hero-buttons">
            <a href="tel:+919912399224" class="hero-btn hero-btn-primary">
                <i class="fas fa-phone-alt"></i> Call Now: 9912399224
            </a>
            <a href="#contact-form" class="hero-btn hero-btn-secondary">
                <i class="fas fa-envelope"></i> Get Free Estimate
            </a>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<div class="service-area-page">
    <div class="page-container">
        <div class="two-column-layout">
            <!-- Left Content Section (75%) -->
            <div class="content-section">
                <div class="content-card">
                    {$content}
                    
                    <div class="cta-gradient-box">
                        <h3 class="cta-title">🚀 Ready to Get Started?</h3>
                        <p class="cta-subtitle">Contact us today for a free consultation and quote</p>
                        <div class="cta-buttons">
                            <a href="tel:+919912399224" class="cta-btn cta-btn-primary">
                                <i class="fas fa-phone-alt"></i> Call: +91 99123 99224
                            </a>
                            <a href="https://wa.me/919912399224" class="cta-btn cta-btn-success" target="_blank">
                                <i class="fab fa-whatsapp"></i> WhatsApp Us
                            </a>
                            <a href="<?php echo SITE_URL; ?>/contact.php" class="cta-btn cta-btn-secondary">
                                <i class="fas fa-envelope"></i> Get Free Quote
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Sidebar Section (25%) -->
            <div class="sidebar-section">
                <!-- Contact Form Card -->
                <div class="sidebar-card contact-form-card" id="contact-form">
                    <h3 class="form-title">Get Free Quote</h3>
                    <form class="quick-contact-form" action="<?php echo SITE_URL; ?>/process-contact.php" method="POST">
                        <input type="hidden" name="service" value="{$serviceName}">
                        <input type="text" name="name" placeholder="Your Name" required class="form-input">
                        <input type="tel" name="phone" placeholder="Phone Number" required pattern="[6-9][0-9]{9}" title="Please enter a valid Indian mobile number starting with 6, 7, 8, or 9" class="form-input">
                        <input type="email" name="email" placeholder="Email Address" class="form-input">
                        <textarea name="message" placeholder="Your Requirements" rows="4" class="form-input"></textarea>
                        <button type="submit" class="form-submit-btn">
                            <i class="fas fa-paper-plane"></i> Send Inquiry
                        </button>
                    </form>
                </div>
                
                <!-- Service Highlights -->
                <div class="sidebar-card highlights-card">
                    <h3 class="sidebar-title">Service Highlights</h3>
                    <ul class="highlights-list">
                        <li><i class="fas fa-check-circle"></i> Free Home Inspection</li>
                        <li><i class="fas fa-check-circle"></i> Same Day Installation</li>
                        <li><i class="fas fa-check-circle"></i> 5 Year Warranty</li>
                        <li><i class="fas fa-check-circle"></i> Premium Quality</li>
                        <li><i class="fas fa-check-circle"></i> Expert Technicians</li>
                        <li><i class="fas fa-check-circle"></i> Best Price Guarantee</li>
                    </ul>
                </div>
                
                <!-- Contact Info Card -->
                <div class="sidebar-card contact-info-card">
                    <h3 class="sidebar-title">Contact Us</h3>
                    <div class="info-list">
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>Phone:</strong>
                                <a href="tel:+919912399224">9912399224</a>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>Email:</strong>
                                <a href="mailto:gcmsafetynets@gmail.com">gcmsafetynets@gmail.com</a>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>Service Area:</strong>
                                <p>Chennai & All Chennai</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>Working Hours:</strong>
                                <p>Mon-Sun: 8:00 AM - 8:00 PM</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- All Available Services Card -->
                <div class="sidebar-card services-card">
                    <h3 class="sidebar-title">All available services in {$areaName}</h3>
                    <div class="services-list">
                        <?php
                        // All 64 keywords
                        \$allKeywords = [
                            ['name' => 'Pigeon Nets', 'slug' => 'pigeon-nets'], ['name' => 'Pigeon Net', 'slug' => 'pigeon-net'],
                            ['name' => 'Balcony Netting', 'slug' => 'balcony-netting'], ['name' => 'Pigeon Net For Balcony', 'slug' => 'pigeon-net-for-balcony'],
                            ['name' => 'Pigeon Nets Installation', 'slug' => 'pigeon-nets-installation'], ['name' => 'Pigeon Bird Netting', 'slug' => 'pigeon-bird-netting'],
                            ['name' => 'Pigeon Net Installation', 'slug' => 'pigeon-net-installation'], ['name' => 'Pigeon Net Near Me', 'slug' => 'pigeon-net-near-me'],
                            ['name' => 'Pigeon Net For Balcony Near Me', 'slug' => 'pigeon-net-for-balcony-near-me'], ['name' => 'Pigeon Net Installation Near Me', 'slug' => 'pigeon-net-installation-near-me'],
                            ['name' => 'Pigeon Safety Nets', 'slug' => 'pigeon-safety-nets'], ['name' => 'Pigeon Net Price', 'slug' => 'pigeon-net-price'],
                            ['name' => 'Kabutar Jali Near Me', 'slug' => 'kabutar-jali-near-me'], ['name' => 'Bird Nets', 'slug' => 'bird-nets'],
                            ['name' => 'Bird Net', 'slug' => 'bird-net'], ['name' => 'Bird Net For Balcony', 'slug' => 'bird-net-for-balcony'],
                            ['name' => 'Bird Net Near Me', 'slug' => 'bird-net-near-me'], ['name' => 'Nets For Birds', 'slug' => 'nets-for-birds'],
                            ['name' => 'Net For Birds', 'slug' => 'net-for-birds'], ['name' => 'Industrial Bird Netting', 'slug' => 'industrial-bird-netting'],
                            ['name' => 'Bird Netting', 'slug' => 'bird-netting'], ['name' => 'Anti Bird Netting', 'slug' => 'anti-bird-netting'],
                            ['name' => 'Safety Nets', 'slug' => 'safety-nets'], ['name' => 'Balcony Safety Nets', 'slug' => 'balcony-safety-nets'],
                            ['name' => 'Safety Nets For Balconies', 'slug' => 'safety-nets-for-balconies'], ['name' => 'Duct Area Safety Nets', 'slug' => 'duct-area-safety-nets'],
                            ['name' => 'Monkey Safety Nets', 'slug' => 'monkey-safety-nets'], ['name' => 'Construction Safety Nets', 'slug' => 'construction-safety-nets'],
                            ['name' => 'Industrial Safety Nets', 'slug' => 'industrial-safety-nets'], ['name' => 'Fall Safety Nets', 'slug' => 'fall-safety-nets'],
                            ['name' => 'Fall Protection Nets', 'slug' => 'fall-protection-nets'], ['name' => 'Children Safety Nets', 'slug' => 'children-safety-nets'],
                            ['name' => 'Pet Safety Nets', 'slug' => 'pet-safety-nets'], ['name' => 'Cricket Nets', 'slug' => 'cricket-nets'],
                            ['name' => 'Cricket Nets Price', 'slug' => 'cricket-nets-price'], ['name' => 'Cricket Nets Near Me', 'slug' => 'cricket-nets-near-me'],
                            ['name' => 'Cricket Practice Net', 'slug' => 'cricket-practice-net'], ['name' => 'Cricket Practice Nets', 'slug' => 'cricket-practice-nets'],
                            ['name' => 'Cricket Net Price', 'slug' => 'cricket-net-price'], ['name' => 'Cricket Indoor Nets Near Me', 'slug' => 'cricket-indoor-nets-near-me'],
                            ['name' => 'Indoor Cricket Nets Near Me', 'slug' => 'indoor-cricket-nets-near-me'], ['name' => 'Sports Nets', 'slug' => 'sports-nets'],
                            ['name' => 'Sports Netting', 'slug' => 'sports-netting'], ['name' => 'Cricket Netting', 'slug' => 'cricket-netting'],
                            ['name' => 'Box Cricket Net', 'slug' => 'box-cricket-net'], ['name' => 'Cricket Net Installation', 'slug' => 'cricket-net-installation'],
                            ['name' => 'Invisible Grills', 'slug' => 'invisible-grills'], ['name' => 'Invisible Grill Near Me', 'slug' => 'invisible-grill-near-me'],
                            ['name' => 'SS Invisible Grills', 'slug' => 'ss-invisible-grills'], ['name' => 'Invisible Grill For Balcony', 'slug' => 'invisible-grill-for-balcony'],
                            ['name' => 'Balcony Invisible Grill', 'slug' => 'balcony-invisible-grill'], ['name' => 'Invisible Grill For Balcony Near Me', 'slug' => 'invisible-grill-for-balcony-near-me'],
                            ['name' => 'Invisible Safety Grill', 'slug' => 'invisible-safety-grill'], ['name' => 'Invisible Grill For Safety', 'slug' => 'invisible-grill-for-safety'],
                            ['name' => 'Invisible Grill For Pigeons', 'slug' => 'invisible-grill-for-pigeons'], ['name' => 'Ceiling Cloth Hangers', 'slug' => 'ceiling-cloth-hangers'],
                            ['name' => 'Dry Cloth Hangers', 'slug' => 'dry-cloth-hangers'], ['name' => 'Cloth Drying Hangers', 'slug' => 'cloth-drying-hangers'],
                            ['name' => 'Cloth Hanger For Balcony', 'slug' => 'cloth-hanger-for-balcony'], ['name' => 'Pulley Cloth Drying Hanger', 'slug' => 'pulley-cloth-drying-hanger'],
                            ['name' => 'Pulley Cloth Hanger', 'slug' => 'pulley-cloth-hanger'], ['name' => 'Laundry Hanger Dryer', 'slug' => 'laundry-hanger-dryer'],
                            ['name' => 'Clothes Hanger To Dry Clothes', 'slug' => 'clothes-hanger-to-dry-clothes'], ['name' => 'Clothes Hanger Drier', 'slug' => 'clothes-hanger-drier']
                        ];
                        
                        foreach (\$allKeywords as \$keyword) {
                            \$isActive = (\$keyword['slug'] == '{$serviceSlug}') ? 'active' : '';
                            \$serviceUrl = SITE_URL . '/' . \$keyword['slug'] . '-in-{$areaSlug}.php';
                        ?>
                            <a href="<?php echo \$serviceUrl; ?>" class="service-item <?php echo \$isActive; ?>">
                                <?php if (\$isActive): ?>
                                    <i class="fas fa-check-circle"></i> 
                                <?php endif; ?>
                                <?php echo \$keyword['name']; ?> in {$areaName}
                            </a>
                        <?php } ?>
                    </div>
                </div>
                
                <!-- Why Choose Us Card -->
                <div class="sidebar-card why-choose-card">
                    <h3 class="sidebar-title">
                        <i class="fas fa-star"></i> Why Choose Us?
                    </h3>
                    <ul class="why-choose-list">
                        <li><i class="fas fa-check"></i> 15+ Years Experience</li>
                        <li><i class="fas fa-check"></i> 10,000+ Happy Customers</li>
                        <li><i class="fas fa-check"></i> 5-Year Warranty</li>
                        <li><i class="fas fa-check"></i> Same Day Service</li>
                        <li><i class="fas fa-check"></i> Expert Installation</li>
                        <li><i class="fas fa-check"></i> Free Site Visit</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* ============================================
   HERO SECTION - PURPLE GRADIENT
   ============================================ */

.service-hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 60px 20px;
    position: relative;
    overflow: hidden;
}

.service-hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg width="100" height="100" xmlns="http://www.w3.org/2000/svg"><circle cx="50" cy="50" r="40" fill="rgba(255,255,255,0.03)"/></svg>');
    opacity: 0.4;
}

.hero-container {
    max-width: 1200px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
}

.breadcrumb {
    color: rgba(255,255,255,0.8);
    font-size: 14px;
    margin-bottom: 20px;
}

.breadcrumb a {
    color: white;
    text-decoration: none;
    transition: opacity 0.3s ease;
}

.breadcrumb a:hover {
    opacity: 0.7;
}

.breadcrumb span {
    color: rgba(255,255,255,0.9);
}

.hero-title {
    font-size: 48px;
    font-weight: 700;
    color: white;
    margin-bottom: 15px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.2);
}

.hero-subtitle {
    font-size: 20px;
    color: rgba(255,255,255,0.9);
    margin-bottom: 30px;
}

.hero-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.hero-btn {
    padding: 14px 30px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    transition: all 0.3s ease;
}

.hero-btn-primary {
    background: #10B981;
    color: white;
    box-shadow: 0 4px 15px rgba(16,185,129,0.4);
}

.hero-btn-primary:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(16,185,129,0.5);
}

.hero-btn-secondary {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 2px solid white;
    backdrop-filter: blur(10px);
}

.hero-btn-secondary:hover {
    background: white;
    color: #667eea;
}

/* ============================================
   MAIN CONTENT AREA - WHITE BACKGROUND
   ============================================ */

.service-area-page {
    background: #f8f9fa;
    padding: 60px 0;
}

.page-container {
    max-width: 1440px; /* Expanded from 1200px */
    margin: 0 auto;
    padding: 0 40px; /* Increased side padding */
}

/* ============================================
   TWO-COLUMN LAYOUT
   ============================================ */

.two-column-layout {
    display: grid;
    grid-template-columns: 75% 25%;
    gap: 30px;
    align-items: start;
}

/* ============================================
   LEFT CONTENT SECTION (75%)
   ============================================ */

.content-section {
    animation: fadeInLeft 0.8s ease;
}

.content-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 40px; /* Reduced from 50px for more content space */
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
    transition: all 0.4s ease;
}

.content-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 30px 80px rgba(0,0,0,0.3);
}

/* Content Styling - Clean & Professional */
.content-card h2 {
    color: #1E293B;
    font-size: 32px;
    font-weight: 700;
    margin: 35px 0 18px 0;
    color: #5b6cef;
}

.content-card h3 {
    color: #5b6cef;
    font-size: 24px;
    font-weight: 600;
    margin: 28px 0 14px 0;
}

.content-card p {
    color: #4a5568;
    line-height: 1.8;
    font-size: 16px;
    margin: 16px 0;
}

.content-card strong, 
.content-card b {
    color: #2d3748;
    font-weight: 700;
}

.content-card ul {
    margin: 20px 0;
    padding-left: 0;
    list-style: none;
}

.content-card li {
    color: #4a5568;
    line-height: 1.8;
    margin: 12px 0;
    padding-left: 30px;
    position: relative;
}

.content-card li::before {
    content: '✓';
    position: absolute;
    left: 0;
    top: 2px;
    width: 20px;
    height: 20px;
    background: #10B981;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

/* CTA Gradient Box */
.cta-gradient-box {
    background: linear-gradient(135deg, #3B82F6 0%, #8B5CF6 100%);
    padding: 50px;
    border-radius: 20px;
    text-align: center;
    margin: 50px 0 0 0;
    box-shadow: 0 15px 40px rgba(59, 130, 246, 0.3);
    transition: all 0.4s ease;
    position: relative;
    overflow: hidden;
}

.cta-gradient-box::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
    animation: rotate 10s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.cta-gradient-box:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 60px rgba(59, 130, 246, 0.5);
}

.cta-title {
    color: #ffffff !important;
    font-size: 32px;
    font-weight: 700;
    margin-bottom: 15px;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.cta-subtitle {
    color: #ffffff !important;
    font-size: 18px;
    margin-bottom: 30px;
    position: relative;
    z-index: 1;
    text-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.cta-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
    position: relative;
    z-index: 1;
}

.cta-btn {
    padding: 15px 35px;
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    font-size: 16px;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.cta-btn-primary {
    background: white;
    color: #3B82F6;
}

.cta-btn-primary:hover {
    background: #3B82F6;
    color: white;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.4);
}

.cta-btn-success {
    background: #10B981;
    color: white;
}

.cta-btn-success:hover {
    background: #059669;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
}

.cta-btn-secondary {
    background: #F59E0B;
    color: white;
}

.cta-btn-secondary:hover {
    background: #D97706;
    transform: translateY(-3px);
    box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
}

/* ============================================
   RIGHT SIDEBAR SECTION (25%)
   ============================================ */

.sidebar-section {
    display: flex;
    flex-direction: column;
    gap: 25px;
    animation: fadeInRight 0.8s ease;
    position: sticky;
    top: 20px;
}

.sidebar-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    transition: all 0.4s ease;
}

.sidebar-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.25);
}

.sidebar-title {
    font-size: 20px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-title i {
    color: #3B82F6;
    font-size: 22px;
}

/* Contact Form Card - Purple Gradient */
.contact-form-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white;
    padding: 30px !important;
}

.form-title {
    color: #ffffff !important;
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 25px;
    text-align: center;
    text-shadow: 0 2px 8px rgba(0,0,0,0.2);
}

.quick-contact-form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

.form-input {
    width: 100%;
    padding: 14px 16px;
    border: none;
    border-radius: 8px;
    font-size: 15px;
    background: rgba(255,255,255,0.95);
    transition: all 0.3s ease;
}

.form-input:focus {
    outline: none;
    background: white;
    box-shadow: 0 0 0 3px rgba(255,255,255,0.3);
}

.form-input::placeholder {
    color: #999;
}

.form-submit-btn {
    width: 100%;
    padding: 14px 20px;
    background: white;
    color: #667eea;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.form-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

/* Service Highlights & Contact Info Cards */
.highlights-card, 
.contact-info-card {
    background: white;
}

.highlights-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.highlights-list li {
    padding: 12px 0;
    color: #4a5568;
    font-size: 15px;
    display: flex;
    align-items: center;
    gap: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.highlights-list li:last-child {
    border-bottom: none;
}

.highlights-list li i {
    color: #10B981;
    font-size: 18px;
}

/* Contact Info */
.info-list {
    display: flex;
    flex-direction: column;
    gap: 18px;
}

.info-item {
    display: flex;
    align-items: start;
    gap: 12px;
}

.info-item i {
    color: #667eea;
    font-size: 18px;
    margin-top: 2px;
}

.info-item strong {
    display: block;
    color: #2d3748;
    margin-bottom: 4px;
    font-size: 14px;
}

.info-item a,
.info-item p {
    color: #4a5568;
    text-decoration: none;
    font-size: 14px;
    margin: 0;
}

.info-item a:hover {
    color: #667eea;
}

/* Services List - Scrollable after 10 items */
.services-list {
    display: flex;
    flex-direction: column;
    gap: 0;
    max-height: 480px; /* Height for ~10 items, then scrollbar */
    overflow-y: auto;
    padding-right: 10px;
}

.services-list::-webkit-scrollbar {
    width: 8px;
}

.services-list::-webkit-scrollbar-track {
    background: #f0f2f5;
    border-radius: 10px;
}

.services-list::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 10px;
}

.services-list::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #5568d3 0%, #6a4091 100%);
}

.service-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 10px;
    color: #5b6cef;
    text-decoration: none;
    font-size: 14px;
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

.service-item:last-child {
    border-bottom: none;
}

.service-item:hover {
    background: #f8f9ff;
    color: #4c5fd6;
    padding-left: 15px;
}

.service-item.active {
    background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
    color: #2d3748;
    font-weight: 700;
    border-left: 4px solid #667eea;
    padding-left: 15px;
}

.service-item.active i {
    color: #10B981;
    font-size: 16px;
    animation: checkPulse 2s infinite;
}

@keyframes checkPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.2); }
}

/* Why Choose Us */
.why-choose-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.why-choose-list li {
    padding: 12px 15px;
    background: rgba(16, 185, 129, 0.05);
    border-radius: 8px;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 12px;
    color: #475569;
    font-size: 14px;
    transition: all 0.3s ease;
}

.why-choose-list li:hover {
    background: rgba(16, 185, 129, 0.15);
    transform: translateX(5px);
}

.why-choose-list li i {
    color: #10B981;
    font-size: 16px;
}

/* ============================================
   ANIMATIONS
   ============================================ */

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes fadeInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* ============================================
   MOBILE RESPONSIVE
   ============================================ */

@media (max-width: 1024px) {
    .two-column-layout {
        grid-template-columns: 100%;
    }
    
    .sidebar-section {
        position: static;
        order: 2;
    }
    
    .content-section {
        order: 1;
    }
    
    .hero-title {
        font-size: 36px;
    }
    
    .content-card {
        padding: 30px;
    }
}

@media (max-width: 768px) {
    /* MOBILE PADDING FIX - Reduce Side Padding */
    .service-area-page {
        padding: 0 10px !important;
    }
    
    .page-container {
        padding: 0 !important;
        max-width: 100% !important;
    }
    
    .service-hero-section {
        padding: 40px 15px !important;
        margin: 0 !important;
    }
    
    .hero-container {
        padding: 0 5px !important;
    }
    
    .hero-title {
        font-size: 28px;
    }
    
    .hero-subtitle {
        font-size: 16px;
    }
    
    .hero-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .hero-btn {
        width: 100%;
        justify-content: center;
        padding: 12px 16px !important;
    }
    
    .content-section {
        padding: 0 !important;
    }
    
    .content-card {
        padding: 15px !important;
        margin: 10px 0 !important;
    }
    
    .content-card h2 {
        font-size: 24px !important;
        padding: 0 5px !important;
    }
    
    .content-card h3 {
        font-size: 20px !important;
        padding: 0 5px !important;
    }
    
    .content-card p {
        font-size: 16px !important;
        line-height: 1.7 !important;
        padding: 0 5px !important;
    }
    
    .content-card ul,
    .content-card ol {
        padding-left: 20px !important;
        margin: 15px 5px !important;
    }
    
    .content-card li {
        margin-bottom: 10px !important;
        font-size: 15px !important;
    }
    
    .cta-gradient-box {
        padding: 25px 15px !important;
        margin: 20px 0 !important;
    }
    
    .cta-title {
        font-size: 22px !important;
    }
    
    .cta-buttons {
        flex-direction: column;
        gap: 10px;
    }
    
    .cta-btn {
        width: 100%;
        padding: 12px 16px !important;
    }
    
    .sidebar-section {
        padding: 0 !important;
        margin-top: 20px !important;
    }
    
    .sidebar-card {
        padding: 20px 15px !important;
        margin-bottom: 15px !important;
    }
    
    .contact-form-card {
        padding: 25px 15px !important;
    }
    
    .form-title {
        font-size: 20px !important;
    }
    
    .two-column-layout {
        padding: 0 !important;
        gap: 15px !important;
    }
}

/* Extra Small Devices - Ultra Compact */
@media (max-width: 480px) {
    .service-area-page {
        padding: 0 8px !important;
    }
    
    .service-hero-section {
        padding: 30px 12px !important;
    }
    
    .hero-title {
        font-size: 24px !important;
    }
    
    .hero-subtitle {
        font-size: 14px !important;
    }
    
    .content-card {
        padding: 12px !important;
    }
    
    .content-card h2 {
        font-size: 22px !important;
    }
    
    .content-card h3 {
        font-size: 18px !important;
    }
    
    .cta-gradient-box {
        padding: 20px 12px !important;
    }
    
    .sidebar-card {
        padding: 15px 12px !important;
    }
}
</style>

<?php 
include 'includes/modern-footer.php'; 

// Close database connection
if (\$conn) {
    \$conn->close();
}
?>
PHP;
    
    return $pageContent;
}

/**
 * Save generated page to database
 */
function savePageToDatabase($data) {
    global $conn;
    
    // Check if database connection exists
    if (!isset($conn) || !$conn) {
        error_log("Database connection not available");
        return false;
    }
    
    try {
        // Determine service category based on service name
        $serviceCategory = determineServiceCategory($data['service_name']);
        
        // Create slug from service and area
        $slug = $data['service_slug'];
        
        $stmt = $conn->prepare("
            INSERT INTO generated_pages (
                service_category,
                service_name,
                area,
                slug,
                title,
                meta_description,
                content,
                is_active,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
        ");
        
        if (!$stmt) {
            error_log("Database prepare failed: " . $conn->error);
            return false;
        }
        
        // Content summary for now (will be full content later)
        $content = "Generated content for " . $data['service_name'] . " in " . $data['area_name'];
        
        $stmt->bind_param(
            "sssssss",
            $serviceCategory,
            $data['service_name'],
            $data['area_name'],
            $slug,
            $data['page_title'],
            $data['meta_description'],
            $content
        );
        
        $result = $stmt->execute();
        
        if (!$result) {
            error_log("Database execute failed: " . $stmt->error);
            $stmt->close();
            return false;
        }
        
        $stmt->close();
        return true;
        
    } catch (Exception $e) {
        error_log("Database save exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Determine service category from service name
 */
function determineServiceCategory($serviceName) {
    $categories = [
        'pigeon' => 'PIGEON NETS',
        'bird' => 'BIRD NETS',
        'safety' => 'SAFETY NETS',
        'cricket' => 'SPORTS NETS',
        'sports' => 'SPORTS NETS',
        'invisible' => 'INVISIBLE GRILLS',
        'grill' => 'INVISIBLE GRILLS',
        'cloth' => 'CLOTH HANGERS',
        'hanger' => 'CLOTH HANGERS',
        'balcony' => 'SAFETY NETS'
    ];
    
    $serviceLower = strtolower($serviceName);
    foreach ($categories as $keyword => $category) {
        if (strpos($serviceLower, $keyword) !== false) {
            return $category;
        }
    }
    
    return 'GENERAL';
}

/**
 * Log page generation to JSON file
 */
function logPageGeneration($data) {
    $logFile = __DIR__ . '/../../config/generated-pages-log.json';
    
    // Load existing log
    $log = [];
    if (file_exists($logFile)) {
        $log = json_decode(file_get_contents($logFile), true) ?? [];
    }
    
    // Add new entry
    $log[] = $data;
    
    // Save log
    file_put_contents($logFile, json_encode($log, JSON_PRETTY_PRINT));
    
    // Update stats
    $statsFile = __DIR__ . '/../../config/generation-stats.json';
    $stats = [
        'total_generated' => count($log),
        'last_generated' => date('Y-m-d H:i:s'),
        'total_tokens_used' => array_sum(array_column($log, 'ai_tokens_used')),
        'total_time_seconds' => array_sum(array_column($log, 'generation_time'))
    ];
    file_put_contents($statsFile, json_encode($stats, JSON_PRETTY_PRINT));
}

/**
 * Estimate token count
 */
function estimateTokens($text) {
    // Rough estimate: 1 token ≈ 4 characters
    return (int)(strlen($text) / 4);
}
