<?php
/**
 * Automated Link Building System
 * Helps build quality backlinks for SEO
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

header('Content-Type: application/json');

// Check admin authentication
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// MySQL Connection
function getDBConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed: ' . $conn->connect_error);
        }
        $conn->set_charset('utf8mb4');
    }
    return $conn;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get_directory_list':
        getDirectoryList();
        break;
    
    case 'track_backlink':
        trackBacklink();
        break;
    
    case 'get_backlinks':
        getBacklinks();
        break;
    
    case 'check_backlink_status':
        checkBacklinkStatus();
        break;
    
    case 'get_link_building_opportunities':
        getLinkBuildingOpportunities();
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

/**
 * Get list of directories to submit to
 */
function getDirectoryList() {
    $directories = [
        // Indian Business Directories
        [
            'name' => 'JustDial',
            'url' => 'https://www.justdial.com/Add-Your-Business',
            'category' => 'Business Directory',
            'priority' => 'High',
            'da' => 85,
            'instructions' => '1. Visit JustDial\n2. Click "Add Your Business"\n3. Fill business details\n4. Verify phone number\n5. Add photos and services',
            'benefits' => 'High DA, Local SEO, Customer reviews'
        ],
        [
            'name' => 'Sulekha',
            'url' => 'https://www.sulekha.com/add-listing',
            'category' => 'Business Directory',
            'priority' => 'High',
            'da' => 75,
            'instructions' => '1. Register on Sulekha\n2. Add business listing\n3. Add services and pricing\n4. Upload photos\n5. Get verified',
            'benefits' => 'Local visibility, Lead generation'
        ],
        [
            'name' => 'IndiaMART',
            'url' => 'https://seller.indiamart.com/',
            'category' => 'B2B Directory',
            'priority' => 'High',
            'da' => 90,
            'instructions' => '1. Create seller account\n2. List products/services\n3. Add company profile\n4. Upload catalog\n5. Get verified',
            'benefits' => 'B2B leads, High authority backlink'
        ],
        [
            'name' => 'TradeIndia',
            'url' => 'https://www.tradeindia.com/seller-registration.html',
            'category' => 'B2B Directory',
            'priority' => 'Medium',
            'da' => 70,
            'instructions' => '1. Register as seller\n2. Add company details\n3. List products\n4. Verify business',
            'benefits' => 'B2B visibility, Quality backlink'
        ],
        
        // Google Properties
        [
            'name' => 'Google My Business',
            'url' => 'https://www.google.com/business/',
            'category' => 'Google',
            'priority' => 'Critical',
            'da' => 100,
            'instructions' => '1. Claim/create GMB listing\n2. Verify business (postcard/phone)\n3. Complete 100% of profile\n4. Add photos (minimum 10)\n5. Add services and areas\n6. Post weekly updates\n7. Respond to all reviews',
            'benefits' => 'Local SEO #1 factor, Google Maps visibility'
        ],
        
        // Social Media
        [
            'name' => 'Facebook Business Page',
            'url' => 'https://www.facebook.com/pages/create',
            'category' => 'Social Media',
            'priority' => 'High',
            'da' => 96,
            'instructions' => '1. Create business page\n2. Complete all information\n3. Add cover and profile photos\n4. Post regularly (3-5 times/week)\n5. Respond to messages\n6. Get reviews',
            'benefits' => 'Social signals, Customer engagement'
        ],
        [
            'name' => 'Instagram Business',
            'url' => 'https://business.instagram.com/',
            'category' => 'Social Media',
            'priority' => 'Medium',
            'da' => 94,
            'instructions' => '1. Convert to business account\n2. Complete bio with website link\n3. Post photos/videos regularly\n4. Use relevant hashtags\n5. Engage with followers',
            'benefits' => 'Visual marketing, Brand awareness'
        ],
        [
            'name' => 'LinkedIn Company Page',
            'url' => 'https://www.linkedin.com/company/setup/new/',
            'category' => 'Social Media',
            'priority' => 'Medium',
            'da' => 98,
            'instructions' => '1. Create company page\n2. Add company details\n3. Post industry content\n4. Connect with professionals',
            'benefits' => 'Professional network, B2B leads'
        ],
        
        // Local Directories
        [
            'name' => 'Bing Places',
            'url' => 'https://www.bingplaces.com/',
            'category' => 'Search Engine',
            'priority' => 'High',
            'da' => 95,
            'instructions' => '1. Claim business on Bing\n2. Verify ownership\n3. Complete profile\n4. Add photos',
            'benefits' => 'Bing search visibility'
        ],
        [
            'name' => 'Yellow Pages India',
            'url' => 'https://www.yellowpages.co.in/add-listing',
            'category' => 'Business Directory',
            'priority' => 'Medium',
            'da' => 65,
            'instructions' => '1. Register business\n2. Add complete details\n3. Verify listing',
            'benefits' => 'Local directory presence'
        ],
        
        // Industry Specific
        [
            'name' => 'Houzz (Home Services)',
            'url' => 'https://www.houzz.in/professionals',
            'category' => 'Industry Directory',
            'priority' => 'Medium',
            'da' => 92,
            'instructions' => '1. Create professional profile\n2. Add portfolio photos\n3. Get reviews from customers',
            'benefits' => 'Home improvement leads'
        ],
        [
            'name' => 'UrbanClap/Urban Company',
            'url' => 'https://www.urbancompany.com/partner',
            'category' => 'Service Marketplace',
            'priority' => 'Medium',
            'da' => 70,
            'instructions' => '1. Register as service partner\n2. Complete profile\n3. Get verified',
            'benefits' => 'Direct customer leads'
        ],
        
        // Review Sites
        [
            'name' => 'Trustpilot',
            'url' => 'https://business.trustpilot.com/',
            'category' => 'Review Site',
            'priority' => 'Medium',
            'da' => 92,
            'instructions' => '1. Claim business profile\n2. Invite customers for reviews\n3. Respond to all reviews',
            'benefits' => 'Trust signals, Reviews'
        ],
        
        // Local Chennai Directories
        [
            'name' => 'Chennai Yellow Pages',
            'url' => 'https://chennai.yellowpages.co.in/',
            'category' => 'Local Directory',
            'priority' => 'Low',
            'da' => 45,
            'instructions' => '1. Add business listing\n2. Complete profile',
            'benefits' => 'Local visibility'
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'directories' => $directories,
        'total' => count($directories)
    ]);
}

/**
 * Track a backlink
 */
function trackBacklink() {
    $source = $_POST['source'] ?? '';
    $url = $_POST['url'] ?? '';
    $type = $_POST['type'] ?? 'directory';
    $status = $_POST['status'] ?? 'pending';
    
    if (empty($source) || empty($url)) {
        echo json_encode(['success' => false, 'message' => 'Source and URL required']);
        return;
    }
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("INSERT INTO backlinks (source, url, type, status, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param('ssss', $source, $url, $type, $status);
        $stmt->execute();
        $backlink_id = $conn->insert_id;
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'message' => 'Backlink tracked successfully',
            'backlink_id' => $backlink_id
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}

/**
 * Get all tracked backlinks
 */
function getBacklinks() {
    try {
        $conn = getDBConnection();
        $result = $conn->query("SELECT * FROM backlinks ORDER BY created_at DESC LIMIT 100");
        
        $backlinks = [];
        while ($row = $result->fetch_assoc()) {
            $backlinks[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'backlinks' => $backlinks,
            'total' => count($backlinks)
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Check backlink status (verify if link is live)
 */
function checkBacklinkStatus() {
    $backlink_id = $_POST['backlink_id'] ?? 0;
    
    if ($backlink_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid backlink ID']);
        return;
    }
    
    try {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT url FROM backlinks WHERE id = ?");
        $stmt->bind_param('i', $backlink_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $backlink = $result->fetch_assoc();
        $stmt->close();
        
        if (!$backlink) {
            throw new Exception('Backlink not found');
        }
        
        // Check if URL is accessible
        $ch = curl_init($backlink['url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        $is_live = ($http_code >= 200 && $http_code < 400);
        $status = $is_live ? 'active' : 'broken';
        
        // Update status
        $stmt = $conn->prepare("UPDATE backlinks SET status = ?, last_checked = NOW() WHERE id = ?");
        $stmt->bind_param('si', $status, $backlink_id);
        $stmt->execute();
        $stmt->close();
        
        echo json_encode([
            'success' => true,
            'status' => $status,
            'http_code' => $http_code,
            'is_live' => $is_live
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}

/**
 * Get link building opportunities
 */
function getLinkBuildingOpportunities() {
    $opportunities = [
        [
            'type' => 'Guest Posting',
            'description' => 'Write articles for home improvement blogs',
            'effort' => 'High',
            'value' => 'Very High',
            'steps' => [
                '1. Find home improvement blogs in India',
                '2. Check if they accept guest posts',
                '3. Pitch article ideas related to safety',
                '4. Write 1500+ word quality article',
                '5. Include 1-2 contextual links to your site',
                '6. Follow up on publication'
            ],
            'examples' => [
                'Home improvement blogs',
                'Construction industry blogs',
                'Safety equipment blogs',
                'Real estate blogs'
            ]
        ],
        [
            'type' => 'Local Citations',
            'description' => 'List business in local directories',
            'effort' => 'Low',
            'value' => 'High',
            'steps' => [
                '1. Create consistent NAP (Name, Address, Phone)',
                '2. Submit to all local directories',
                '3. Verify listings',
                '4. Keep information updated'
            ],
            'examples' => [
                'Google My Business',
                'Bing Places',
                'Yellow Pages',
                'Local chamber of commerce'
            ]
        ],
        [
            'type' => 'Broken Link Building',
            'description' => 'Find broken links and offer your content as replacement',
            'effort' => 'Medium',
            'value' => 'High',
            'steps' => [
                '1. Find pages with broken links (use tools)',
                '2. Create similar/better content',
                '3. Email webmaster about broken link',
                '4. Suggest your content as replacement'
            ]
        ],
        [
            'type' => 'Resource Page Links',
            'description' => 'Get listed on resource pages',
            'effort' => 'Medium',
            'value' => 'Medium',
            'steps' => [
                '1. Find resource pages in your industry',
                '2. Check if your business fits',
                '3. Email requesting inclusion',
                '4. Provide value proposition'
            ]
        ],
        [
            'type' => 'Competitor Backlinks',
            'description' => 'Get links from same sources as competitors',
            'effort' => 'Medium',
            'value' => 'High',
            'steps' => [
                '1. Identify top competitors',
                '2. Use backlink checker tools',
                '3. Find their backlink sources',
                '4. Reach out to same sources',
                '5. Offer better content/service'
            ]
        ],
        [
            'type' => 'Social Media Profiles',
            'description' => 'Create profiles on all major platforms',
            'effort' => 'Low',
            'value' => 'Medium',
            'steps' => [
                '1. Create business profiles',
                '2. Complete all information',
                '3. Add website link',
                '4. Post regularly',
                '5. Engage with followers'
            ]
        ],
        [
            'type' => 'Customer Reviews',
            'description' => 'Get reviews with backlinks',
            'effort' => 'Low',
            'value' => 'High',
            'steps' => [
                '1. Ask satisfied customers for reviews',
                '2. Guide them to review platforms',
                '3. Respond to all reviews',
                '4. Share positive reviews'
            ]
        ],
        [
            'type' => 'Local Partnerships',
            'description' => 'Partner with local businesses',
            'effort' => 'Medium',
            'value' => 'High',
            'steps' => [
                '1. Identify complementary businesses',
                '2. Propose partnership',
                '3. Exchange website links',
                '4. Cross-promote services'
            ],
            'examples' => [
                'Construction companies',
                'Interior designers',
                'Real estate agents',
                'Property management companies'
            ]
        ]
    ];
    
    echo json_encode([
        'success' => true,
        'opportunities' => $opportunities,
        'total' => count($opportunities)
    ]);
}
