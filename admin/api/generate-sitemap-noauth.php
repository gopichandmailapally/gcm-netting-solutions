<?php
/**
 * TEMPORARY SITEMAP GENERATOR - NO AUTH
 * Use this to test if sitemap generation works
 * DELETE THIS FILE after testing!
 */

header('Content-Type: application/json');

$base_url = 'https://www.gcmsafetynets.in';
$base_dir = dirname(dirname(__DIR__));

// Start XML
$xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;

$total_urls = 0;
$details = [
    'static_pages' => 0,
    'blog_posts' => 0,
    'pillar_pages' => 0,
    'service_pages' => 0
];

// 1. Static pages
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

// 2. Blog posts
$blogs_dir = $base_dir . '/data/blogs';
if (file_exists($blogs_dir) && is_dir($blogs_dir)) {
    $blog_files = glob($blogs_dir . '/*.json');
    foreach ($blog_files as $blog_file) {
        $basename = basename($blog_file);
        if ($basename === 'index.json' || $basename === 'stats.json') continue;
        
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

// 3. Service and Pillar pages
// Load the actual 64 service keywords from config
$all_services = require $base_dir . '/config/all-service-keywords.php';

// Extract just the slugs for pillar pages (these are the 64 pillar pages)
$pillar_slugs = [];
foreach ($all_services as $service) {
    $pillar_slugs[] = $service['slug'];
}

$php_files = glob($base_dir . '/*.php');
$skip_patterns = [
    'admin', 'test-', 'check-', 'cleanup-', 'find-', 'fix-',
    'regenerate-', 'show-', 'create-', 'prepare-', 'setup-',
    'update-', 'generate-', '-old.', 'redirect', 'config',
    'index.php', 'blog.php', 'blogs.php'
];

foreach ($php_files as $file) {
    $filename = basename($file);
    
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
    
    $lastmod = date('Y-m-d', filemtime($file));
    $priority = $is_pillar ? '0.85' : '0.7';
    $changefreq = $is_pillar ? 'weekly' : 'monthly';
    
    $xml .= '  <url>' . PHP_EOL;
    $xml .= '    <loc>' . $base_url . '/' . $filename . '</loc>' . PHP_EOL;
    $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
    $xml .= '    <changefreq>' . $changefreq . '</changefreq>' . PHP_EOL;
    $xml .= '    <priority>' . $priority . '</priority>' . PHP_EOL;
    $xml .= '  </url>' . PHP_EOL;
    
    $total_urls++;
    if ($is_pillar) {
        $details['pillar_pages']++;
    } elseif ($is_service_area) {
        $details['service_pages']++;
    }
}

$xml .= '</urlset>';

// Save sitemap
$sitemap_path = $base_dir . '/sitemap.xml';
file_put_contents($sitemap_path, $xml);
$file_size = filesize($sitemap_path);

echo json_encode([
    'success' => true,
    'total_urls' => $total_urls,
    'file_size' => $file_size,
    'details' => $details,
    'note' => 'Sitemap generated successfully (NO AUTH VERSION - DELETE THIS FILE AFTER TESTING)',
    'warning' => 'This is a temporary test file. Delete generate-sitemap-noauth.php after confirming it works!'
]);
