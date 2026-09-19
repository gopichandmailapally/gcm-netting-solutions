<?php
/**
 * PAGE GENERATOR SCRIPT
 * Generates all 12,096 physical PHP files for SEO
 * 
 * Run this script ONCE to generate:
 * - 64 pillar pages (pigeon-nets.php, bird-nets.php, etc.)
 * - 12,032 service+area pages (pigeon-nets-in-abids.php, etc.)
 */

define('GCM_INIT', true);
require_once '../config/config.php';

// Set execution time limit (this may take a few minutes)
set_time_limit(600); // 10 minutes
ini_set('memory_limit', '512M');

// ============================================
// SERVICE CATEGORIES & KEYWORDS (64 total)
// ============================================
$service_categories = [
    'PIGEON NETS' => [
        'icon' => 'fas fa-dove',
        'services' => [
            'Pigeon Nets' => 'pigeon-nets',
            'Pigeon Net' => 'pigeon-net',
            'Balcony Netting' => 'balcony-netting',
            'Pigeon Net For Balcony' => 'pigeon-net-for-balcony',
            'Pigeon Nets Installation' => 'pigeon-nets-installation',
            'Pigeon Bird Netting' => 'pigeon-bird-netting',
            'Pigeon Net Installation' => 'pigeon-net-installation',
            'Pigeon Net Near Me' => 'pigeon-net-near-me',
            'Pigeon Net For Balcony Near Me' => 'pigeon-net-for-balcony-near-me',
            'Pigeon Net Installation Near Me' => 'pigeon-net-installation-near-me',
            'Pigeon Safety Nets' => 'pigeon-safety-nets',
            'Pigeon Net Price' => 'pigeon-net-price',
            'Kabutar Jali Near Me' => 'kabutar-jali-near-me'
        ]
    ],
    'BIRD NETS' => [
        'icon' => 'fas fa-kiwi-bird',
        'services' => [
            'Bird Nets' => 'bird-nets',
            'Bird Net' => 'bird-net',
            'Bird Net For Balcony' => 'bird-net-for-balcony',
            'Bird Net Near Me' => 'bird-net-near-me',
            'Nets For Birds' => 'nets-for-birds',
            'Net For Birds' => 'net-for-birds',
            'Industrial Bird Netting' => 'industrial-bird-netting',
            'Bird Netting' => 'bird-netting',
            'Anti Bird Netting' => 'anti-bird-netting'
        ]
    ],
    'SAFETY NETS' => [
        'icon' => 'fas fa-shield-alt',
        'services' => [
            'Safety Nets' => 'safety-nets',
            'Balcony Safety Nets' => 'balcony-safety-nets',
            'Safety Nets For Balconies' => 'safety-nets-for-balconies',
            'Duct Area Safety Nets' => 'duct-area-safety-nets',
            'Monkey Safety Nets' => 'monkey-safety-nets',
            'Construction Safety Nets' => 'construction-safety-nets',
            'Industrial Safety Nets' => 'industrial-safety-nets',
            'Fall Safety Nets' => 'fall-safety-nets',
            'Fall Protection Nets' => 'fall-protection-nets',
            'Children Safety Nets' => 'children-safety-nets',
            'Pet Safety Nets' => 'pet-safety-nets'
        ]
    ],
    'SPORTS NETS' => [
        'icon' => 'fas fa-baseball-ball',
        'services' => [
            'Cricket Nets' => 'cricket-nets',
            'Cricket Nets Price' => 'cricket-nets-price',
            'Cricket Nets Near Me' => 'cricket-nets-near-me',
            'Cricket Practice Net' => 'cricket-practice-net',
            'Cricket Practice Nets' => 'cricket-practice-nets',
            'Cricket Net Price' => 'cricket-net-price',
            'Cricket Indoor Nets Near Me' => 'cricket-indoor-nets-near-me',
            'Indoor Cricket Nets Near Me' => 'indoor-cricket-nets-near-me',
            'Sports Nets' => 'sports-nets',
            'Sports Netting' => 'sports-netting',
            'Cricket Netting' => 'cricket-netting',
            'Box Cricket Net' => 'box-cricket-net',
            'Cricket Net Installation' => 'cricket-net-installation'
        ]
    ],
    'INVISIBLE GRILLS' => [
        'icon' => 'fas fa-grip-lines-vertical',
        'services' => [
            'Invisible Grills' => 'invisible-grills',
            'Invisible Grill Near Me' => 'invisible-grill-near-me',
            'SS Invisible Grills' => 'ss-invisible-grills',
            'Invisible Grill For Balcony' => 'invisible-grill-for-balcony',
            'Balcony Invisible Grill' => 'balcony-invisible-grill',
            'Invisible Grill For Balcony Near Me' => 'invisible-grill-for-balcony-near-me',
            'Invisible Safety Grill' => 'invisible-safety-grill',
            'Invisible Grill For Safety' => 'invisible-grill-for-safety',
            'Invisible Grill For Pigeons' => 'invisible-grill-for-pigeons'
        ]
    ],
    'CLOTH HANGERS' => [
        'icon' => 'fas fa-tshirt',
        'services' => [
            'Ceiling Cloth Hangers' => 'ceiling-cloth-hangers',
            'Dry Cloth Hangers' => 'dry-cloth-hangers',
            'Cloth Drying Hangers' => 'cloth-drying-hangers',
            'Cloth Hanger For Balcony' => 'cloth-hanger-for-balcony',
            'Pulley Cloth Drying Hanger' => 'pulley-cloth-drying-hanger',
            'Pulley Cloth Hanger' => 'pulley-cloth-hanger',
            'Laundry Hanger Dryer' => 'laundry-hanger-dryer',
            'Clothes Hanger To Dry Clothes' => 'clothes-hanger-to-dry-clothes',
            'Clothes Hanger Drier' => 'clothes-hanger-drier'
        ]
    ]
];

// ============================================
// 150 CHENNAI AREAS (Alphabetically Sorted)
// ============================================
$all_areas = [
    'Abids', 'Adikmet', 'Afzalgunj', 'Aliabad', 'Alwal', 'Amberpet', 'Kodambakkam', 'Ananthagiri-Hills', 'Asif-Nagar', 'Asifabad',
    'Saidapet', 'Saidapet-Metro', 'Ayyappa-Society', 'Royapettah', 'Badangpet', 'Bagh-Amberpet', 'Bagh-Lingampally', 'Bahadurpura', 'Balkampet', 'Balnagar',
    'Bandlaguda', 'Banjara-Hills', 'Barkas', 'Basheerbagh', 'Begum-Bazar', 'Nungambakkam', 'Boduppal', 'Borabanda', 'Bowenpally', 'Boysguda',
    'Champapet', 'Chanda-Nagar', 'Chromepet', 'Charminar', 'Chikkadpally', 'Chintal', 'Chintalkunta', 'Dabeerpura', 'Dammaiguda', 'Pallavaram',
    'Domalguda', 'ECIL', 'ECIL-Cross-Roads', 'Erragadda', 'Falaknuma', 'Film-Nagar', 'Financial-District', 'Anna Nagar', 'Gaddiannaram', 'Gajularamaram',
    'Gandhi-Nagar', 'Ghatkesar', 'Golconda', 'Goshamahal', 'Gowlidoddy', 'Gudimelakunta', 'Habsiguda', 'Hafeezpet', 'Hayathnagar', 'Himayatnagar',
    'Hitech-City', 'Hussainialam', 'Hyderguda', 'IS-Sadan', 'Jahanuma', 'Jeedimetla', 'Jubilee-Hills', 'Kachiguda', 'Kailash-Nagar', 'Kalimandir',
    'Kamala-Nagar', 'Kapra', 'Karkhana', 'Karwan', 'Kattedan', 'Khairtabad', 'Khajaguda', 'Kishanbagh', 'Kismatkhan-Gudda', 'Madipakkam',
    'Velachery', 'Kothapet', 'Thiruvanmiyur-Colony', 'Porur', 'LB-Nagar', 'Lakdikapul', 'Lalapet', 'Langer-Houz', 'Lingampally', 'Madannapet',
    'T Nagar', 'Madinaguda', 'Mahdipatnam', 'Malakpet', 'Mallapur', 'Mylapore', 'Marredpally', 'Masab-Tank', 'Meerpet', 'Mogappair',
    'Mettuguda', 'Ambattur', 'Moghalpura', 'Moosarambagh', 'Moti-Nagar', 'Moula-Ali', 'Musheerabad', 'Nacharam', 'Nagaram', 'Nagole',
    'Nallakunta', 'Nampally', 'Nanal-Nagar', 'Nanakramguda', 'Narayanguda', 'Neredmet', 'New-Bowenpally', 'Kilpauk', 'Old-Bowenpally', 'Old-City',
    'Osmangunj', 'Padmarao-Nagar', 'Panjagutta', 'Panjagutta-Circle', 'Paradise', 'Patancheru', 'Patel-Road', 'Peerzadiguda', 'Pragathi-Nagar', 'Purani-Haveli',
    'Quthbullapur', 'Rajendranagar', 'Ramakrishna-Puram', 'Ramanthapur', 'Ramnagar', 'RC-Puram', 'Red-Hills', 'Safilguda', 'Saidabad', 'Sainikpuri',
    'Sanath-Nagar', 'Sangareddy', 'Santosh-Nagar', 'Saroornagar', 'Tambaram', 'Serilingampally', 'Shaikpet', 'Shamirpet', 'Shamshabad', 'Shankarpally'
];

// ============================================
// COUNTERS
// ============================================
$stats = [
    'service_pages' => 0,
    'service_area_pages' => 0,
    'errors' => 0
];

echo "<!DOCTYPE html>\n";
echo "<html><head><title>Page Generator</title></head><body style='font-family: Arial; padding: 20px;'>\n";
echo "<h1>🚀 Generating All Pages...</h1>\n";
echo "<p>This may take 5-10 minutes. Please wait...</p>\n";
echo "<pre>\n";
flush();

// ============================================
// STEP 1: Generate Service Pages (64 files)
// ============================================
echo "\n📄 STEP 1: Generating 64 Service Pages...\n";
echo str_repeat('=', 60) . "\n";

foreach ($service_categories as $category => $cat_data) {
    foreach ($cat_data['services'] as $service_name => $service_slug) {
        $filename = "../{$service_slug}.php";
        
        $content = generateServicePageContent($service_name, $service_slug, $category);
        
        if (file_put_contents($filename, $content)) {
            $stats['service_pages']++;
            echo "✓ Created: {$service_slug}.php\n";
        } else {
            $stats['errors']++;
            echo "✗ FAILED: {$service_slug}.php\n";
        }
        
        flush();
    }
}

echo "\n✅ Service pages created: {$stats['service_pages']}\n\n";
flush();

// ============================================
// STEP 2: Generate Service+Area Pages (12,032 files)
// ============================================
echo "\n📍 STEP 2: Generating 12,032 Service+Area Pages...\n";
echo str_repeat('=', 60) . "\n";
echo "This will take several minutes...\n\n";
flush();

$batch_count = 0;
foreach ($service_categories as $category => $cat_data) {
    foreach ($cat_data['services'] as $service_name => $service_slug) {
        foreach ($all_areas as $area) {
            $area_slug = strtolower(str_replace(' ', '-', $area));
            $filename = "../{$service_slug}-in-{$area_slug}.php";
            
            $content = generateServiceAreaPageContent($service_name, $service_slug, $area, $area_slug, $category);
            
            if (file_put_contents($filename, $content)) {
                $stats['service_area_pages']++;
                $batch_count++;
                
                // Show progress every 100 files
                if ($batch_count % 100 == 0) {
                    echo "Progress: {$batch_count} pages created...\n";
                    flush();
                }
            } else {
                $stats['errors']++;
            }
        }
    }
}

echo "\n✅ Service+Area pages created: {$stats['service_area_pages']}\n\n";
flush();

// ============================================
// FINAL SUMMARY
// ============================================
echo "\n" . str_repeat('=', 60) . "\n";
echo "🎉 PAGE GENERATION COMPLETE!\n";
echo str_repeat('=', 60) . "\n";
echo "Service Pages: {$stats['service_pages']}\n";
echo "Service+Area Pages: {$stats['service_area_pages']}\n";
echo "Total Pages: " . ($stats['service_pages'] + $stats['service_area_pages']) . "\n";
echo "Errors: {$stats['errors']}\n";
echo str_repeat('=', 60) . "\n";
echo "\n✅ All pages generated successfully!\n";
echo "\n<a href='../index.php' style='padding: 10px 20px; background: #0066CC; color: white; text-decoration: none; border-radius: 5px;'>View Website</a>\n";
echo "</pre></body></html>\n";

// ============================================
// FUNCTION: Generate Service Page Content
// ============================================
function generateServicePageContent($service_name, $service_slug, $category) {
    $title = $service_name . " in Chennai | GCM Netting Solutions";
    $meta_desc = "Professional " . $service_name . " installation services in Chennai. Quality materials, expert installation, 5-year warranty. Call +91 99123 99224 for free quote.";
    
    $content = <<<PHP
<?php
/**
 * {$service_name} Page
 * Generated automatically for SEO
 */
define('GCM_INIT', true);
require_once 'config/config.php';

\$page_title = '{$title}';
\$meta_description = '{$meta_desc}';
\$meta_keywords = '{$service_slug}, {$service_slug} chennai, {$service_slug} installation';
\$current_page = 'services';

include 'includes/modern-header.php';
?>

<div class="service-page">
    <div class="container">
        <h1>{$service_name} in Chennai</h1>
        <p>Professional {$service_name} installation services across Chennai.</p>
        
        <div class="service-content">
            <h2>Why Choose Our {$service_name}?</h2>
            <ul>
                <li>✓ Quality Materials</li>
                <li>✓ Expert Installation</li>
                <li>✓ 5-Year Warranty</li>
                <li>✓ Affordable Prices</li>
                <li>✓ 24/7 Customer Support</li>
            </ul>
            
            <h3>Service Coverage</h3>
            <p>We provide {$service_name} services across all areas of Chennai including Anna Nagar, T Nagar, Porur, Pallavaram, and 150+ other locations.</p>
            
            <div class="cta-section">
                <a href="tel:+919912399224" class="btn btn-primary">Call Now: +91 99123 99224</a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-secondary">Get Free Quote</a>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/modern-footer.php'; ?>
PHP;
    
    return $content;
}

// ============================================
// FUNCTION: Generate Service+Area Page Content
// ============================================
function generateServiceAreaPageContent($service_name, $service_slug, $area, $area_slug, $category) {
    $title = $service_name . " in " . $area . " | GCM Netting Solutions Chennai";
    $meta_desc = "Professional " . $service_name . " installation in " . $area . ", Chennai. Quality materials, expert installation, 5-year warranty. Call +91 99123 99224.";
    
    $content = <<<PHP
<?php
/**
 * {$service_name} in {$area} Page
 * Generated automatically for SEO
 */
define('GCM_INIT', true);
require_once 'config/config.php';

\$page_title = '{$title}';
\$meta_description = '{$meta_desc}';
\$meta_keywords = '{$service_slug} {$area_slug}, {$service_slug} in {$area_slug}, {$service_slug} {$area_slug} chennai';
\$current_page = 'services';

include 'includes/modern-header.php';
?>

<div class="service-area-page">
    <div class="container">
        <h1>{$service_name} in {$area}</h1>
        <p>Professional {$service_name} installation services in {$area}, Chennai.</p>
        
        <div class="service-content">
            <h2>About {$service_name} in {$area}</h2>
            <p>We provide high-quality {$service_name} installation services in {$area}. Our experienced team ensures professional installation with premium materials and long-lasting results.</p>
            
            <h3>Why Choose Us in {$area}?</h3>
            <ul>
                <li>✓ Local service in {$area}</li>
                <li>✓ Quick response time</li>
                <li>✓ Quality materials</li>
                <li>✓ Expert installation</li>
                <li>✓ 5-year warranty</li>
                <li>✓ Competitive pricing</li>
                <li>✓ Free site visit & quote</li>
            </ul>
            
            <h3>Service Features</h3>
            <ul>
                <li>Premium quality materials</li>
                <li>Professional installation team</li>
                <li>Same-day service available</li>
                <li>Customized solutions</li>
                <li>Post-installation support</li>
            </ul>
            
            <h3>Coverage in {$area}</h3>
            <p>We serve all parts of {$area} including residential apartments, commercial buildings, villas, and independent houses. Our team is familiar with the local area and can provide quick service.</p>
            
            <div class="cta-section">
                <a href="tel:+919912399224" class="btn btn-primary">Call Now: +91 99123 99224</a>
                <a href="<?php echo SITE_URL; ?>/contact.php" class="btn btn-secondary">Get Free Quote</a>
            </div>
            
            <h3>Contact Us</h3>
            <p>For {$service_name} installation in {$area}, call us at +91 99123 99224 or fill out our online contact form for a free quote.</p>
        </div>
    </div>
</div>

<?php include 'includes/modern-footer.php'; ?>
PHP;
    
    return $content;
}
?>
