<?php
/**
 * COMPREHENSIVE SITEMAP GENERATOR
 * Includes: Static Pages, Service Pages, Pillar Pages, Blog Posts
 * Auto-updates for Google Search Console
 */

// Start session with proper settings (must match login.php)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_path', '/');
    session_name('GCM_ADMIN_SESSION');
    session_start();
}

// Check if logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$base_url = 'https://www.gcmsafetynets.in';
$base_dir = dirname(dirname(__DIR__));

// Start XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

$total_urls = 0;
$details = [
    'static_pages' => 0,
    'service_pages' => 0,
    'pillar_pages' => 0,
    'blog_posts' => 0
];

// ========================================
// 1. STATIC/MAIN PAGES
// ========================================
$static_pages = [
    '' => ['priority' => '1.0', 'changefreq' => 'daily'],
    'about.php' => ['priority' => '0.8', 'changefreq' => 'monthly'],
    'contact.php' => ['priority' => '0.9', 'changefreq' => 'monthly'],
    'estimation.php' => ['priority' => '0.9', 'changefreq' => 'monthly'],
    'all-areas.php' => ['priority' => '0.8', 'changefreq' => 'weekly'],
    'blog.php' => ['priority' => '0.9', 'changefreq' => 'daily'],
    'blogs.php' => ['priority' => '0.9', 'changefreq' => 'daily'],
    'faqs.php' => ['priority' => '0.8', 'changefreq' => 'weekly'],
    'reviews.php' => ['priority' => '0.8', 'changefreq' => 'daily'],
    'gallery.php' => ['priority' => '0.7', 'changefreq' => 'weekly'],
    'videos.php' => ['priority' => '0.7', 'changefreq' => 'weekly'],
    'privacy-policy.php' => ['priority' => '0.3', 'changefreq' => 'yearly'],
    'terms-conditions.php' => ['priority' => '0.3', 'changefreq' => 'yearly'],
    'thank-you.php' => ['priority' => '0.3', 'changefreq' => 'yearly']
];

foreach ($static_pages as $page => $meta) {
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $base_url . '/' . $page . '</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>' . $meta['changefreq'] . '</changefreq>' . PHP_EOL;
    $xml .= '    <priority>' . $meta['priority'] . '</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
    $total_urls++;
    $details['static_pages']++;
}

// ========================================
// 2. BLOG POSTS (from JSON files)
// ========================================
$blogs_dir = $base_dir . '/data/blogs';
if (file_exists($blogs_dir) && is_dir($blogs_dir)) {
    $blog_files = glob($blogs_dir . '/*.json');
    foreach ($blog_files as $blog_file) {
        $basename = basename($blog_file);
        
        // Skip index and stats files
        if ($basename === 'index.json' || $basename === 'stats.json') {
            continue;
        }
        
        // Read blog data - only include blogs with BOTH title AND content (matches blogs.php)
        $blog_data = json_decode(file_get_contents($blog_file), true);
        if ($blog_data && isset($blog_data['title']) && isset($blog_data['content'])) {
            $slug = str_replace('.json', '', $basename);
            $lastmod = isset($blog_data['created_at']) ? 
                       date('Y-m-d', strtotime($blog_data['created_at'])) : 
                       date('Y-m-d', filemtime($blog_file));
            
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . $base_url . '/blog.php?slug=' . urlencode($slug) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
            $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
            $xml .= '    <priority>0.7</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
            $total_urls++;
            $details['blog_posts']++;
        }
    }
}

// ========================================
// 3. PILLAR PAGES (Exactly 64 keywords from config)
// ========================================
// Load the actual 64 service keywords from config
$all_services = require dirname(__DIR__, 2) . '/config/all-service-keywords.php';

// Extract just the slugs for pillar pages (these are the 64 pillar pages)
$pillar_slugs = [];
foreach ($all_services as $service) {
    $pillar_slugs[] = $service['slug'];
}

$root_files = glob($base_dir . '/*.php');
$pillar_pages = [];
$service_area_pages = [];

foreach ($root_files as $file) {
    $filename = basename($file);
    
    // Skip files that are already in static pages or should be excluded
    $skip_patterns = [
        'admin', 'test-', 'check-', 'cleanup-', 'find-', 'fix-', 
        'regenerate-', 'show-', 'create-', 'prepare-', 'setup-', 
        'update-', 'generate-', '-old.', 'redirect', 'config',
        'index.php', 'blog.php', 'blogs.php'
    ];
    
    $should_skip = false;
    foreach ($skip_patterns as $pattern) {
        if (strpos($filename, $pattern) !== false || isset($static_pages[$filename])) {
            $should_skip = true;
            break;
        }
    }
    
    if ($should_skip) continue;
    
    // Get filename without .php extension
    $file_slug = str_replace('.php', '', $filename);
    
    // Check if this is one of the 64 pillar pages
    $is_pillar = in_array($file_slug, $pillar_slugs);
    
    // Check if it's a service-area page (has "-in-" in the filename)
    $is_service_area = strpos($filename, '-in-') !== false;
    
    if ($is_pillar) {
        $pillar_pages[] = $filename;
    } elseif ($is_service_area) {
        $service_area_pages[] = $filename;
    }
}

// Sort alphabetically
sort($pillar_pages);
sort($service_area_pages);

// Add pillar pages (HIGH priority - main service landing pages)
foreach ($pillar_pages as $filename) {
    $lastmod = date('Y-m-d', filemtime($base_dir . '/' . $filename));
    
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $base_url . '/' . $filename . '</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
    $xml .= '    <priority>0.85</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
    $total_urls++;
    $details['pillar_pages']++;
}

// ========================================
// 4. SERVICE-AREA PAGES (Location-specific)
// ========================================
// Add all service-area combination pages
foreach ($service_area_pages as $filename) {
    $lastmod = date('Y-m-d', filemtime($base_dir . '/' . $filename));
    
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $base_url . '/' . $filename . '</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
    $xml .= '    <priority>0.7</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
    $total_urls++;
    $details['service_pages']++;
}

// Close XML
$xml .= '</urlset>';

// Save sitemap
$sitemap_path = $base_dir . '/sitemap.xml';
$result = file_put_contents($sitemap_path, $xml);

if ($result !== false) {
    echo json_encode([
        'success' => true,
        'total_urls' => $total_urls,
        'file_size' => filesize($sitemap_path),
        'details' => $details,
        'message' => 'Comprehensive sitemap generated successfully',
        'note' => 'Google Search Console automatically checks sitemap every few hours. New blogs will be indexed within 24-48 hours.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to write sitemap file'
    ]);
}
