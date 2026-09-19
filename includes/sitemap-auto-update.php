<?php
/**
 * SITEMAP AUTO-UPDATE HELPER
 * Call this function after creating/updating blogs or pages
 * to automatically regenerate the sitemap
 */

function auto_update_sitemap() {
    $base_url = 'https://www.gcmsafetynets.in';
    $base_dir = dirname(__DIR__);
    
    // Start XML
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . PHP_EOL;
    $xml .= '        xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . PHP_EOL;
    
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
    }
    
    // 2. Blog posts
    $blogs_dir = $base_dir . '/data/blogs';
    if (file_exists($blogs_dir) && is_dir($blogs_dir)) {
        $blog_files = glob($blogs_dir . '/*.json');
        foreach ($blog_files as $blog_file) {
            $basename = basename($blog_file);
            if ($basename === 'index.json' || $basename === 'stats.json') continue;
            
            $blog_data = json_decode(file_get_contents($blog_file), true);
            if ($blog_data && isset($blog_data['title'])) {
                $slug = str_replace('.json', '', $basename);
                $lastmod = isset($blog_data['created_at']) ? 
                           date('Y-m-d', strtotime($blog_data['created_at'])) : 
                           date('Y-m-d', filemtime($blog_file));
                
                $xml .= '  <url>' . PHP_EOL;
                $xml .= '    <loc>' . $base_url . '/blog/' . urlencode($slug) . '</loc>' . PHP_EOL;
                $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
                $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
                $xml .= '    <priority>0.7</priority>' . PHP_EOL;
                $xml .= '  </url>' . PHP_EOL;
            }
        }
    }
    
    // 3. Pillar pages and service pages
    $pillar_keywords = [
        'balcony-netting', 'balcony-safety-nets', 'children-safety-nets',
        'pigeon-nets', 'cricket-practice-nets', 'fall-safety-nets',
        'invisible-grill', 'monkey-safety-nets', 'ceiling-cloth-hangers',
        'pulley-cloth-drying-hanger', 'sports-netting'
    ];
    
    $root_files = glob($base_dir . '/*.php');
    $pillar_pages = [];
    $service_pages = [];
    
    $skip_patterns = [
        'admin', 'test-', 'check-', 'cleanup-', 'find-', 'fix-',
        'regenerate-', 'show-', 'create-', 'prepare-', 'setup-',
        'update-', 'generate-', '-old.', 'redirect', 'config',
        'index.php', 'blog.php', 'blogs.php'
    ];
    
    foreach ($root_files as $file) {
        $filename = basename($file);
        
        $should_skip = false;
        foreach ($skip_patterns as $pattern) {
            if (strpos($filename, $pattern) !== false || isset($static_pages[$filename])) {
                $should_skip = true;
                break;
            }
        }
        if ($should_skip) continue;
        
        $is_pillar = false;
        foreach ($pillar_keywords as $keyword) {
            if (strpos($filename, $keyword) !== false && strpos($filename, '-in-') === false) {
                $is_pillar = true;
                break;
            }
        }
        
        if ($is_pillar) {
            $pillar_pages[] = $filename;
        } elseif (strpos($filename, '-in-') !== false) {
            $service_pages[] = $filename;
        }
    }
    
    // Add pillar pages
    foreach ($pillar_pages as $filename) {
        $lastmod = date('Y-m-d', filemtime($base_dir . '/' . $filename));
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $base_url . '/' . $filename . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.85</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
    
    // Add service pages
    foreach ($service_pages as $filename) {
        $lastmod = date('Y-m-d', filemtime($base_dir . '/' . $filename));
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $base_url . '/' . $filename . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>monthly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.7</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }
    
    // 4. Video Sitemap (Google Video Sitemap extension)
    $videos_dir = $base_dir . '/data/videos';
    $video_entries = [];
    if (is_dir($videos_dir)) {
        $vfiles = array_filter(glob($videos_dir . '/*.json') ?: [], fn($f) => basename($f) !== 'stats.json');
        foreach ($vfiles as $vf) {
            $vd = @json_decode(@file_get_contents($vf), true);
            if (!empty($vd['youtube_id']) && !empty($vd['title'])) {
                $video_entries[] = $vd;
            }
        }
    }
    $videos_loc = htmlspecialchars($base_url . '/videos.php', ENT_XML1);
    if (!empty($video_entries)) {
        usort($video_entries, fn($a, $b) => strtotime($b['created_at'] ?? '0') - strtotime($a['created_at'] ?? '0'));
        $v_lastmod = date('Y-m-d', strtotime($video_entries[0]['created_at'] ?? 'now'));
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $videos_loc . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . $v_lastmod . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.7</priority>' . PHP_EOL;
        foreach ($video_entries as $vd) {
            $yt_id    = htmlspecialchars($vd['youtube_id'], ENT_XML1);
            $vtitle   = htmlspecialchars($vd['title'], ENT_XML1);
            $vdesc    = htmlspecialchars(substr($vd['description'] ?? $vd['title'], 0, 2048), ENT_XML1);
            $thumb    = 'https://img.youtube.com/vi/' . $yt_id . '/hqdefault.jpg';
            $pub_date = !empty($vd['created_at']) ? date('Y-m-d', strtotime($vd['created_at'])) : date('Y-m-d');
            $xml .= '    <video:video>' . PHP_EOL;
            $xml .= '      <video:thumbnail_loc>' . $thumb . '</video:thumbnail_loc>' . PHP_EOL;
            $xml .= '      <video:title>' . $vtitle . '</video:title>' . PHP_EOL;
            $xml .= '      <video:description>' . $vdesc . '</video:description>' . PHP_EOL;
            $xml .= '      <video:player_loc>https://www.youtube.com/embed/' . $yt_id . '</video:player_loc>' . PHP_EOL;
            $xml .= '      <video:publication_date>' . $pub_date . '</video:publication_date>' . PHP_EOL;
            $xml .= '    </video:video>' . PHP_EOL;
        }
        $xml .= '  </url>' . PHP_EOL;
    } else {
        $xml .= '  <url>' . PHP_EOL;
        $xml .= '    <loc>' . $videos_loc . '</loc>' . PHP_EOL;
        $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
        $xml .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
        $xml .= '    <priority>0.7</priority>' . PHP_EOL;
        $xml .= '  </url>' . PHP_EOL;
    }

    $xml .= '</urlset>';
    
    // Save sitemap
    $sitemap_path = $base_dir . '/sitemap.xml';
    return file_put_contents($sitemap_path, $xml) !== false;
}
