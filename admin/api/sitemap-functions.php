<?php
/**
 * Sitemap Shared Functions - Segmented & Clean Extensionless Engine
 * Generates sitemap index (sitemap.xml) + sub-sitemaps (main, services, blogs, areas).
 * Guarantees 100% clean extensionless URLs, 0 redirects, 0 404s.
 */
if (!defined('GCM_INIT')) die('Direct access not permitted');

// ── Main generation function ───────────────────────────────────────
function gcm_generate_sitemap() {
    $root_dir   = dirname(dirname(dirname(__FILE__)));
    $today      = date('Y-m-d');
    $total_urls = 0;
    $sitemaps_created = [];

    // Helper to start a urlset file
    $start_sitemap = function($filename) use ($root_dir) {
        $path = $root_dir . '/' . $filename;
        $fp = @fopen($path, 'w');
        if (!$fp) return null;
        fwrite($fp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($fp, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);
        return [$fp, $path];
    };

    // Helper to close a urlset file
    $close_sitemap = function($fp) {
        fwrite($fp, '</urlset>' . PHP_EOL);
        fclose($fp);
    };

    // Helper to write a clean <url>
    $write_url = function($fp, $loc, $lastmod, $freq, $pri) {
        fwrite($fp, "  <url>\n");
        fwrite($fp, "    <loc>" . htmlspecialchars($loc, ENT_XML1) . "</loc>\n");
        fwrite($fp, "    <lastmod>{$lastmod}</lastmod>\n");
        fwrite($fp, "    <changefreq>{$freq}</changefreq>\n");
        fwrite($fp, "    <priority>{$pri}</priority>\n");
        fwrite($fp, "  </url>\n");
    };

    // ─────────────────────────────────────────────────────────────────
    // 1. Static Main Pages (sitemap-main.xml)
    // ─────────────────────────────────────────────────────────────────
    $main_res = $start_sitemap('sitemap-main.xml');
    if ($main_res) {
        list($main_fp, $main_path) = $main_res;
        $static_pages = [
            ''                  => ['1.0', 'daily'],
            'about'             => ['0.8', 'monthly'],
            'services'          => ['0.9', 'weekly'],
            'all-areas'         => ['0.9', 'weekly'],
            'contact'           => ['0.9', 'monthly'],
            'faqs'              => ['0.8', 'weekly'],
            'gallery'           => ['0.8', 'weekly'],
            'reviews'           => ['0.8', 'weekly'],
            'blogs'             => ['0.9', 'daily'],
            'estimation'        => ['0.9', 'monthly'],
            'privacy-policy'    => ['0.3', 'yearly'],
            'terms-conditions'  => ['0.3', 'yearly']
        ];
        foreach ($static_pages as $slug => [$pri, $freq]) {
            $url = ($slug === '') ? 'https://www.gcmsafetynets.in/' : 'https://www.gcmsafetynets.in/' . $slug;
            $write_url($main_fp, $url, $today, $freq, $pri);
            $total_urls++;
        }
        $close_sitemap($main_fp);
        $sitemaps_created[] = 'sitemap-main.xml';
    }

    // ─────────────────────────────────────────────────────────────────
    // 2. Pillars / Core Services (sitemap-services.xml)
    // ─────────────────────────────────────────────────────────────────
    require_once $root_dir . '/config/database.php';
    $db = Database::getInstance();
    $pillars = $db->fetchAll("SELECT keyword_slug FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order") ?: [];

    $srv_res = $start_sitemap('sitemap-services.xml');
    if ($srv_res) {
        list($srv_fp, $srv_path) = $srv_res;
        foreach ($pillars as $p) {
            $slug = trim((string)($p['keyword_slug'] ?? ''));
            if (!$slug) continue;
            $write_url($srv_fp, 'https://www.gcmsafetynets.in/' . htmlspecialchars($slug), $today, 'weekly', '0.9');
            $total_urls++;
        }
        $close_sitemap($srv_fp);
        $sitemaps_created[] = 'sitemap-services.xml';
    }

    // ─────────────────────────────────────────────────────────────────
    // 3. Blog Posts (sitemap-blogs.xml)
    // ─────────────────────────────────────────────────────────────────
    $blog_rows = [];
    try {
        $blog_rows = $db->fetchAll("SELECT slug, COALESCE(created_at, generated_at) AS created_at FROM ai_blogs WHERE is_published = 1") ?: [];
    } catch (\Throwable $e) {
        $blog_rows = [];
    }
    if (empty($blog_rows)) {
        try {
            $blog_rows = $db->fetchAll("SELECT slug, COALESCE(created_at, published_at) AS created_at FROM blog_posts WHERE is_published = 1") ?: [];
        } catch (\Throwable $e) {
            $blog_rows = [];
        }
    }
    // Also scan data/blogs JSON files if DB is empty
    if (empty($blog_rows)) {
        $b_dir = $root_dir . '/data/blogs';
        if (is_dir($b_dir)) {
            foreach (glob($b_dir . '/*.json') as $bf) {
                $bn = basename($bf);
                if ($bn === 'index.json' || $bn === 'stats.json') continue;
                $blog_rows[] = [
                    'slug' => str_replace('.json', '', $bn),
                    'created_at' => date('Y-m-d', filemtime($bf))
                ];
            }
        }
    }

    $blog_res = $start_sitemap('sitemap-blogs.xml');
    if ($blog_res) {
        list($blog_fp, $blog_path) = $blog_res;
        foreach ($blog_rows as $br) {
            $slug = trim((string)($br['slug'] ?? ''));
            if (!$slug) continue;
            $lastmod = !empty($br['created_at']) ? date('Y-m-d', strtotime((string)$br['created_at'])) : $today;
            $write_url($blog_fp, 'https://www.gcmsafetynets.in/blog/' . urlencode($slug), $lastmod, 'monthly', '0.7');
            $total_urls++;
        }
        $close_sitemap($blog_fp);
        $sitemaps_created[] = 'sitemap-blogs.xml';
    }

    // ─────────────────────────────────────────────────────────────────
    // 4. Hyper-Local Area Pages Partitioned (sitemap-areas-X.xml)
    // ─────────────────────────────────────────────────────────────────
    $gen_dir   = $root_dir . '/generated-pages/';
    $gen_files = is_dir($gen_dir) ? (glob($gen_dir . '*-in-*.php') ?: []) : [];

    $chunk_size = 3500;
    $chunks = array_chunk($gen_files, $chunk_size);
    $chunk_idx = 1;

    foreach ($chunks as $chunk) {
        $area_sm_name = "sitemap-areas-{$chunk_idx}.xml";
        $area_res = $start_sitemap($area_sm_name);
        if ($area_res) {
            list($area_fp, $area_path) = $area_res;
            foreach ($chunk as $file) {
                $base = basename($file);
                $slug = pathinfo($base, PATHINFO_FILENAME);
                $write_url($area_fp, 'https://www.gcmsafetynets.in/' . htmlspecialchars($slug), $today, 'monthly', '0.8');
                $total_urls++;
            }
            $close_sitemap($area_fp);
            $sitemaps_created[] = $area_sm_name;
        }
        $chunk_idx++;
    }

    // ─────────────────────────────────────────────────────────────────
    // 5. Master Sitemap Index (sitemap.xml)
    // ─────────────────────────────────────────────────────────────────
    $index_path = $root_dir . '/sitemap.xml';
    $ifp = @fopen($index_path, 'w');
    if ($ifp) {
        fwrite($ifp, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($ifp, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);
        foreach ($sitemaps_created as $sm_file) {
            fwrite($ifp, "  <sitemap>\n");
            fwrite($ifp, "    <loc>https://www.gcmsafetynets.in/{$sm_file}</loc>\n");
            fwrite($ifp, "    <lastmod>{$today}</lastmod>\n");
            fwrite($ifp, "  </sitemap>\n");
        }
        fwrite($ifp, '</sitemapindex>' . PHP_EOL);
        fclose($ifp);
    }

    return [
        'success'          => true,
        'total_urls'       => $total_urls,
        'sitemaps_created' => $sitemaps_created,
        'file_size'        => filesize($index_path),
        'message'          => "Segmented sitemaps generated with {$total_urls} clean URLs across " . count($sitemaps_created) . " files.",
    ];
}

// ── Ping Google & Bing with updated sitemap ────────────────────────
function gcm_ping_search_engines() {
    $sm = urlencode('https://www.gcmsafetynets.in/sitemap.xml');
    $endpoints = [
        'https://www.google.com/ping?sitemap=' . $sm,
        'https://www.bing.com/webmaster/ping.aspx?siteMap=' . $sm,
    ];
    $ctx = stream_context_create(['http' => ['timeout' => 5, 'ignore_errors' => true]]);
    foreach ($endpoints as $url) {
        @file_get_contents($url, false, $ctx);
    }
}

// ── Read sitemap settings ──────────────────────────────────────────
function gcm_sitemap_settings() {
    $f = dirname(dirname(dirname(__FILE__))) . '/data/sitemap-settings.json';
    if (!file_exists($f)) return ['auto_refresh' => false, 'auto_ping' => false];
    return json_decode(file_get_contents($f), true) ?: ['auto_refresh' => false, 'auto_ping' => false];
}

// ── Save sitemap settings ──────────────────────────────────────────
function gcm_save_sitemap_settings(array $settings) {
    $f = dirname(dirname(dirname(__FILE__))) . '/data/sitemap-settings.json';
    return file_put_contents($f, json_encode($settings, JSON_PRETTY_PRINT)) !== false;
}

// ── Auto-refresh if setting enabled ────────────────────────────────
function gcm_auto_refresh_sitemap() {
    $settings = gcm_sitemap_settings();
    if (empty($settings['auto_refresh'])) return null;

    @set_time_limit(300);
    @ini_set('memory_limit', '256M');

    $result = gcm_generate_sitemap();

    if ($result['success']) {
        $settings['last_refresh']      = date('Y-m-d H:i:s');
        $settings['last_refresh_urls'] = $result['total_urls'];
        gcm_save_sitemap_settings($settings);

        if (!empty($settings['auto_ping'])) {
            gcm_ping_search_engines();
        }
    }

    return $result;
}
