<?php
/**
 * View Generated Pages
 * View, search, filter, and manage all generated pages
 */

define('GCM_INIT', true);
require_once '../../config/config.php';

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'View Generated Pages';

// Database connection using PDO
require_once '../../config/database.php';
$db = Database::getInstance();

include '../includes/header.php';

// Pagination
$page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$_allowed_per_page = [50, 100, 250, 500];
$per_page = in_array((int)($_GET['per_page'] ?? 50), $_allowed_per_page) ? (int)($_GET['per_page'] ?? 50) : 50;
$offset = ($page - 1) * $per_page;

// Filters
$filter_category = isset($_GET['category']) ? sanitize_input($_GET['category']) : '';
$filter_area = isset($_GET['area']) ? sanitize_input($_GET['area']) : '';
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

// Build query
$where = ["is_active IN (0, 1)"];
$params = [];
$types = '';

if ($filter_category) {
    $where[] = "service_category = ?";
    $params[] = $filter_category;
    $types .= 's';
}

if ($filter_area) {
    $where[] = "area = ?";
    $params[] = $filter_area;
    $types .= 's';
}

if ($search) {
    $where[] = "(title LIKE ? OR area LIKE ? OR service_name LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= 'sss';
}

$where_clause = implode(' AND ', $where);

// Get pages from both database AND file system
$pages = [];
$db_pages = [];
$file_pages = [];
$root_dir = dirname(dirname(dirname(__FILE__)));

try {
    // 1. Get pages from DATABASE (service pages with keyword + area)
    $sql = "SELECT
        gp.id,
        gp.page_slug     AS slug,
        gp.page_title    AS title,
        COALESCE(sa.area_name, '')   AS area,
        COALESCE(kw.category, 'AI Generated Page') AS service_category,
        COALESCE(kw.keyword_name, '') AS service_name,
        COALESCE(gp.is_published, 1) AS is_active,
        0 AS view_count,
        gp.created_at,
        gp.page_slug AS file_name
    FROM generated_pages gp
    LEFT JOIN service_areas sa        ON gp.area_id    = sa.id
    LEFT JOIN seo_service_keywords kw ON gp.keyword_id  = kw.id
    WHERE gp.keyword_id > 0 AND gp.area_id > 0
      AND gp.page_slug IS NOT NULL AND gp.page_slug != ''
    ORDER BY gp.created_at DESC";

    $db_pages = $db->fetchAll($sql) ?: [];

    // NOTE: Do NOT delete DB records here — only hide pages where file is missing from display.
    // Deleting records causes irreversible data loss on every page refresh.
    $gen_dir_check = dirname(dirname(dirname(__FILE__))) . '/generated-pages/';
    foreach ($db_pages as $key => $pg) {
        $s = $pg['slug'] ?? '';
        if (empty($s)) {
            unset($db_pages[$key]); // Remove blank-slug rows from display only
        }
        // Pages with a missing file are kept in DB; still shown (file may exist on server)
    }
    $db_pages = array_values($db_pages);

    // Add source tag
    foreach ($db_pages as &$_pg) {
        $_pg['source']     = 'ai-generator';
        $_pg['page_views'] = $_pg['view_count'] ?? 0;
        $_pg['file_path']  = $_pg['file_name'] . '.php';
    }
    unset($_pg);
    
    // 2. Get pages from FILE SYSTEM (area pages inside generated-pages/ folder)
    $generated_dir = $root_dir . '/generated-pages';
    $php_files = is_dir($generated_dir) ? glob($generated_dir . '/*.php') : [];
    if (!$php_files) $php_files = [];

    $exclude_files = ['index.php', 'about.php', 'contact.php', 'services.php',
                      'blogs.php', 'gallery.php', 'all-areas.php'];

    foreach ($php_files as $file) {
        $filename = basename($file);
        if (in_array($filename, $exclude_files)) continue;

        $pageType = 'Generated Page';
        $service  = '';
        $area     = '';

        if (preg_match('/^(.+)-in-(.+)\.php$/', $filename, $matches)) {
            $pageType = 'Area Page';
            $service  = $matches[1];
            $area     = $matches[2];
        } elseif (preg_match('/^(.+)\.php$/', $filename, $matches)) {
            $pageType = 'Pillar Page';
            $service  = $matches[1];
        }

        $file_pages[] = [
            'id'               => 0,
            'slug'             => str_replace('.php', '', $filename),
            'title'            => ucwords(str_replace(['-', '.php'], [' ', ''], $filename)),
            'file_name'        => $filename,
            'file_path'        => $file,
            'created_at'       => date('Y-m-d H:i:s', @filemtime($file) ?: time()),
            'is_active'        => 1,
            'source'           => strtolower(str_replace(' ', '-', $pageType)),
            'service_category' => $pageType,
            'service_name'     => ucwords(str_replace('-', ' ', $service)),
            'area'             => ucwords(str_replace('-', ' ', $area)),
            'view_count'       => 0,
            'page_views'       => 0,
        ];
    }

    // 3. Load all 64 pillar keywords from config (slug + keyword name + category)
    $_kw_cfg = [];
    try { $_kw_cfg = require $root_dir . '/config/all-service-keywords.php'; } catch (\Throwable $_e) {}
    foreach ($_kw_cfg as $_kw) {
        $pslug  = $_kw['slug'];
        $pname  = $_kw['keyword'];
        $pcat   = $_kw['category'];
        $pfile  = $root_dir . '/' . $pslug . '.php';
        $exists = file_exists($pfile);
        $file_pages[] = [
            'id'               => 0,
            'slug'             => $pslug,
            'title'            => $pname,
            'file_name'        => $pslug . '.php',
            'file_path'        => $pfile,
            'created_at'       => $exists ? date('Y-m-d H:i:s', @filemtime($pfile) ?: time()) : null,
            'is_active'        => $exists ? 1 : 0,
            'source'           => 'pillar-page',
            'service_category' => 'Pillar Page',
            'service_name'     => $pname,
            'area'             => $pcat,
            'view_count'       => 0,
            'page_views'       => 0,
        ];
    }
    
} catch (\Throwable $e) {
    error_log('View pages error: ' . $e->getMessage());
}

// Deduplicate: exclude filesystem entries that already have a DB record
$_db_slug_set = array_flip(array_column($db_pages, 'slug'));
$file_pages   = array_values(array_filter($file_pages, function($p) use ($_db_slug_set) {
    return !isset($_db_slug_set[$p['slug']]);
}));
// Combine both sources
$pages = array_merge($db_pages, $file_pages);

// Save UNFILTERED total before applying type filter
$total_pages_count_all = count($pages);

// Apply type filter if specified
$type_filter = $_GET['filter'] ?? 'all';
if ($type_filter != 'all') {
    $pages = array_filter($pages, function($page) use ($type_filter) {
        $source = $page['source'] ?? '';
        switch ($type_filter) {
            case 'pillar':
                return $source === 'pillar-page';
            case 'area':
                // Area/service pages exist as both filesystem 'area-page' and DB 'ai-generator'
                return $source === 'area-page' || $source === 'ai-generator';
            case 'ai':
                // ALL pages are AI-generated (Gemini) — show everything
                return true;
            default:
                return true;
        }
    });
}

// Debug output
error_log("DEBUG: DB Pages count: " . count($db_pages));
error_log("DEBUG: File Pages count: " . count($file_pages));
error_log("DEBUG: Total Pages count before filter: " . (count($db_pages) + count($file_pages)));
error_log("DEBUG: Total Pages count after filter: " . count($pages));

// Apply category / area / search filters (PHP-side, works for both DB and filesystem pages)
if ($filter_category) {
    $pages = array_values(array_filter($pages, function($pg) use ($filter_category) {
        $cat = ($pg['source'] === 'pillar-page') ? ($pg['area'] ?? '') : ($pg['service_category'] ?? '');
        return strcasecmp(trim($cat), trim($filter_category)) === 0;
    }));
}
if ($filter_area) {
    $pages = array_values(array_filter($pages, function($pg) use ($filter_area) {
        return strcasecmp(trim($pg['area'] ?? ''), trim($filter_area)) === 0;
    }));
}
if ($search) {
    $pages = array_values(array_filter($pages, function($pg) use ($search) {
        $s = strtolower($search);
        return strpos(strtolower($pg['title'] ?? ''), $s) !== false
            || strpos(strtolower($pg['area'] ?? ''), $s) !== false
            || strpos(strtolower($pg['service_category'] ?? ''), $s) !== false
            || strpos(strtolower($pg['service_name'] ?? ''), $s) !== false;
    }));
}

// Sort by creation date (null-safe for PHP 8)
if (!empty($pages)) {
    usort($pages, function($a, $b) {
        $ta = @strtotime($a['created_at'] ?? '') ?: 0;
        $tb = @strtotime($b['created_at'] ?? '') ?: 0;
        return $tb - $ta; // newest first
    });
}

$total_pages_count = count($pages);

// Get actual counts from database for delete confirmation
$pillar_count    = 64;
$areas_count     = 188;
$total_area_pages = 12032;
$total_all_pages  = 12096;
try {
    $pillar_count     = (int)($db->fetchOne("SELECT COUNT(*) as cnt FROM seo_service_keywords WHERE is_active = 1")['cnt'] ?? 64);
    $areas_count      = (int)($db->fetchOne("SELECT COUNT(*) as cnt FROM service_areas WHERE is_active = 1")['cnt'] ?? 188);
    $total_area_pages = $pillar_count * $areas_count;
    $total_all_pages  = $total_area_pages + $pillar_count;
} catch (\Exception $e) {}

// Paginate
if ($per_page < 1) $per_page = 50; // safety: prevent DivisionByZeroError
$pages = array_slice($pages, $offset, $per_page);
$total_pages_pagination = $total_pages_count > 0 ? (int)ceil($total_pages_count / $per_page) : 0;

// Get all categories and areas from source tables directly (not via generated_pages join)
// This ensures all 64 keywords and 188 areas are always shown in dropdowns
try {
    $categories = $db->fetchAll(
        "SELECT DISTINCT category AS service_category
         FROM seo_service_keywords
         WHERE is_active = 1 AND category IS NOT NULL AND category != ''
         ORDER BY category"
    ) ?: [];
    $areas = $db->fetchAll(
        "SELECT area_name AS area
         FROM service_areas
         WHERE is_active = 1 AND area_name IS NOT NULL AND area_name != ''
         ORDER BY area_name"
    ) ?: [];
} catch (\Exception $e) {
    $categories = [];
    $areas = [];
}

// Per-category progress stats for breakdown section
$cat_icons = [
    'PIGEON NETS'      => 'fas fa-dove',
    'BIRD NETS'        => 'fas fa-crow',
    'SAFETY NETS'      => 'fas fa-shield-alt',
    'SPORTS NETS'      => 'fas fa-futbol',
    'INVISIBLE GRILLS' => 'fas fa-grip-lines',
    'CLOTH HANGERS'    => 'fas fa-tshirt',
];
$cat_stats     = [];
$kw_count_map  = [];
$total_areas_count = $areas_count; // reuse already-fetched count
try {
    $rows = $db->fetchAll(
        "SELECT kw.category, COUNT(*) as generated
         FROM generated_pages gp
         LEFT JOIN seo_service_keywords kw ON gp.keyword_id = kw.id
         WHERE kw.category IS NOT NULL AND gp.keyword_id > 0 AND gp.area_id > 0
         GROUP BY kw.category"
    ) ?: [];
    foreach ($rows as $r) {
        $cat_stats[$r['category']] = (int)$r['generated'];
    }
    $kw_rows = $db->fetchAll(
        "SELECT kw.category, COUNT(DISTINCT kw.id) as kw_count
         FROM seo_service_keywords kw WHERE kw.is_active=1 GROUP BY kw.category"
    ) ?: [];
    foreach ($kw_rows as $kc) $kw_count_map[$kc['category']] = (int)$kc['kw_count'];
} catch (\Exception $e) {}

// Filesystem fallback: when DB count < filesystem count (DB out of sync)
$_vgp_gen_dir   = dirname(dirname(dirname(__FILE__))) . '/generated-pages/';
$_vgp_fs_files  = glob($_vgp_gen_dir . '*.php') ?: [];
$_vgp_fs_count  = count(array_filter($_vgp_fs_files, fn($f) => basename($f) !== 'index.php'));
$_vgp_db_total  = array_sum($cat_stats);
if ($_vgp_fs_count > $_vgp_db_total) {
    // Build keyword_slug → category mapping
    $_vgp_kw_map = [];
    try {
        foreach (($db->fetchAll("SELECT keyword_slug, category FROM seo_service_keywords WHERE is_active = 1") ?: []) as $_k) {
            if (!empty($_k['keyword_slug'])) $_vgp_kw_map[$_k['keyword_slug']] = $_k['category'];
        }
    } catch (\Exception $e) {}
    // Count files per category by parsing filename
    $_vgp_fs_cat = [];
    foreach ($_vgp_fs_files as $_vgp_f) {
        $_vgp_fn = basename($_vgp_f, '.php');
        if ($_vgp_fn === 'index') continue;
        $_vgp_parts  = explode('-', $_vgp_fn);
        $_vgp_in_pos = array_search('in', $_vgp_parts);
        if ($_vgp_in_pos === false || $_vgp_in_pos < 1) continue;
        for ($_vgp_klen = $_vgp_in_pos; $_vgp_klen >= 1; $_vgp_klen--) {
            $_vgp_kslug = implode('-', array_slice($_vgp_parts, 0, $_vgp_klen));
            if (isset($_vgp_kw_map[$_vgp_kslug])) {
                $_vgp_cat = $_vgp_kw_map[$_vgp_kslug];
                $_vgp_fs_cat[$_vgp_cat] = ($_vgp_fs_cat[$_vgp_cat] ?? 0) + 1;
                break;
            }
        }
    }
    // Use max(DB, filesystem) per category
    foreach ($_vgp_fs_cat as $_c => $_cnt) {
        if ($_cnt > ($cat_stats[$_c] ?? 0)) $cat_stats[$_c] = $_cnt;
    }
}
?>

<div class="seo-page">

<!-- Hero Header -->
<div class="seo-hero">
    <div class="seo-hero-left">
        <h1><i class="fas fa-list"></i> View Generated Pages</h1>
        <p><?php echo number_format($total_area_pages); ?> service + <?php echo number_format($pillar_count); ?> pillar = <?php echo number_format($total_all_pages); ?> total pages</p>
    </div>
    <span class="seo-hero-badge"><i class="fas fa-file-code" style="margin-right:6px;"></i><?php echo number_format($total_all_pages); ?> Total Pages</span>
</div>

<!-- Info Banner -->
<div class="solution-banner">
    <div class="sol-icon"><i class="fas fa-database"></i></div>
    <div>
        <strong>AI-Generated Service Pages</strong>
        <p><?php echo number_format($pillar_count); ?> keywords &times; <?php echo number_format($areas_count); ?> areas = <?php echo number_format($total_area_pages); ?> service pages + <?php echo number_format($pillar_count); ?> pillar pages = <strong><?php echo number_format($total_all_pages); ?> total pages.</strong></p>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon-wrap blue"><i class="fas fa-file-alt"></i></div>
        <div class="stat-text-wrap"><h3>Total Pages</h3><div class="value"><?php echo number_format($total_all_pages); ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap green"><i class="fas fa-tags"></i></div>
        <div class="stat-text-wrap"><h3>Categories</h3><div class="value"><?php echo count($cat_stats) ?: 6; ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap orange"><i class="fas fa-map-marker-alt"></i></div>
        <div class="stat-text-wrap"><h3>Service Areas</h3><div class="value"><?php echo number_format($areas_count); ?></div></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon-wrap teal"><i class="fas fa-layer-group"></i></div>
        <div class="stat-text-wrap"><h3>Target Total</h3><div class="value"><?php echo number_format($total_all_pages); ?></div></div>
    </div>
</div>

<!-- Action Buttons -->
<div class="vgp-actions">
    <button class="vgp-btn vgp-green" onclick="window.location.href='generate-pages.php'">
        <i class="fas fa-plus"></i> Generate More
    </button>
    <button class="vgp-btn vgp-red" onclick="deleteAllGeneratedPages()">
        <i class="fas fa-trash-alt"></i> Delete ALL Generated Pages
    </button>
    <button class="vgp-btn vgp-gray" onclick="bulkDeleteSelected()">
        <i class="fas fa-trash"></i> Delete Selected
    </button>
</div>


<!-- Section 1: Category Progress -->
<?php if (!empty($cat_stats)): ?>
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num purple">1</div>
        <h2><i class="fas fa-chart-bar" style="color:#667eea;margin-right:8px;"></i>Generation Progress by Category</h2>
    </div>
    <div class="seo-section-body">
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px;">
            <?php
            $all_cats = ['PIGEON NETS','BIRD NETS','SAFETY NETS','SPORTS NETS','INVISIBLE GRILLS','CLOTH HANGERS'];
            foreach ($all_cats as $cat):
                $generated = $cat_stats[$cat] ?? 0;
                $total_kws = $kw_count_map[$cat] ?? 1;
                $total_possible = $total_kws * $total_areas_count;
                $pct = $total_possible > 0 ? min(100, round($generated / $total_possible * 100)) : 0;
                $icon = $cat_icons[$cat] ?? 'fas fa-file';
                $color = $pct >= 80 ? '#10b981' : ($pct >= 40 ? '#f59e0b' : '#6366f1');
            ?>
            <div style="background:#f8faff;border-radius:12px;padding:14px 16px;border:1.5px solid #e2e8f0;">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                    <i class="<?php echo $icon; ?>" style="color:<?php echo $color; ?>;font-size:18px;"></i>
                    <strong style="font-size:13px;color:#1e293b;"><?php echo $cat; ?></strong>
                    <span style="margin-left:auto;font-size:12px;font-weight:700;color:<?php echo $color; ?>"><?php echo $pct; ?>%</span>
                </div>
                <div style="background:#e2e8f0;border-radius:4px;height:6px;">
                    <div style="background:<?php echo $color; ?>;width:<?php echo $pct; ?>%;height:6px;border-radius:4px;transition:width .5s;"></div>
                </div>
                <div style="font-size:11px;color:#64748b;margin-top:6px;">
                    <?php echo number_format($generated); ?> pages generated &nbsp;&middot;&nbsp; <?php echo $total_kws; ?> keywords &times; <?php echo $total_areas_count; ?> areas
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Section 2: Filter by Type -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num blue">2</div>
        <h2><i class="fas fa-filter" style="color:#3b82f6;margin-right:8px;"></i>Filter by Type</h2>
    </div>
    <div class="seo-section-body">
        <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:20px;">
            <a href="?filter=all" class="vgp-filter-card <?php echo (!isset($_GET['filter']) || $_GET['filter'] == 'all') ? 'active' : ''; ?>">
                <i class="fas fa-globe"></i><h3>All Pages</h3><p><?php echo number_format($total_all_pages); ?> total pages</p>
            </a>
            <a href="?filter=pillar" class="vgp-filter-card <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'pillar') ? 'active' : ''; ?>">
                <i class="fas fa-layer-group"></i><h3>Pillar Pages</h3><p><?php echo number_format($pillar_count); ?> keyword hubs</p>
            </a>
            <a href="?filter=area" class="vgp-filter-card <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'area') ? 'active' : ''; ?>">
                <i class="fas fa-map-marker-alt"></i><h3>Service Pages</h3><p><?php echo number_format($total_area_pages); ?> location pages</p>
            </a>
            <a href="?filter=ai" class="vgp-filter-card <?php echo (isset($_GET['filter']) && $_GET['filter'] == 'ai') ? 'active' : ''; ?>">
                <i class="fas fa-robot"></i><h3>AI Generated</h3><p>All <?php echo number_format($total_all_pages); ?> pages</p>
            </a>
        </div>
    </div>
</div>

<!-- Section 3: Search & Filter -->
<div class="seo-section">
    <div class="seo-section-head">
        <div class="sec-num amber">3</div>
        <h2><i class="fas fa-search" style="color:#f59e0b;margin-right:8px;"></i>Search &amp; Filter</h2>
    </div>
    <div class="seo-section-body">
        <form method="GET" class="vgp-filters-form">
            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($type_filter); ?>">
            <div class="filter-group">
                <label><i class="fas fa-tag"></i> Category</label>
                <select name="category" class="form-control">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo $cat['service_category']; ?>" <?php echo $filter_category == $cat['service_category'] ? 'selected' : ''; ?>>
                            <?php echo $cat['service_category']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-map-marker-alt"></i> Area</label>
                <select name="area" class="form-control">
                    <option value="">All Areas</option>
                    <?php foreach ($areas as $area): ?>
                        <option value="<?php echo $area['area']; ?>" <?php echo $filter_area == $area['area'] ? 'selected' : ''; ?>>
                            <?php echo $area['area']; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Search</label>
                <input type="text" name="search" class="form-control" placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-list-ol"></i> Per Page</label>
                <select name="per_page" class="form-control" onchange="this.form.submit()">
                    <?php foreach ([50, 100, 250, 500] as $_pp): ?>
                        <option value="<?php echo $_pp; ?>" <?php echo $per_page == $_pp ? 'selected' : ''; ?>><?php echo $_pp; ?> per page</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-actions">
                <button type="submit" class="vgp-btn vgp-purple"><i class="fas fa-filter"></i> Apply</button>
                <button type="button" class="vgp-btn vgp-gray" onclick="window.location.href='view-generated-pages.php'"><i class="fas fa-times"></i> Clear</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Actions Bar -->
<div class="bulk-actions-bar" id="bulkActionsBar" style="display:none;">
    <div class="selected-count"><span id="selectedCount">0</span> pages selected</div>
    <div class="bulk-actions">
        <button class="btn btn-sm btn-success" onclick="bulkActivate()"><i class="fas fa-check"></i> Activate</button>
        <button class="btn btn-sm btn-warning" onclick="bulkDeactivate()"><i class="fas fa-ban"></i> Deactivate</button>
        <button class="btn btn-sm btn-danger" onclick="bulkDeleteSelected()"><i class="fas fa-trash"></i> Delete</button>
    </div>
</div>

<!-- Section 4: Pages Table -->
<div class="seo-section" style="margin-bottom:32px;">
    <div class="seo-section-head">
        <div class="sec-num green">4</div>
        <h2><i class="fas fa-table" style="color:#10b981;margin-right:8px;"></i>Generated Pages</h2>
        <div style="margin-left:auto;display:flex;align-items:center;gap:8px;font-size:13px;color:#64748b;">
            <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
            <label for="selectAll" style="margin:0;cursor:pointer;">Select All</label>
        </div>
    </div>
    <div class="seo-section-body" style="padding:0 0 4px;">
        <?php if (empty($pages)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <h3>No pages found</h3>
                <p>Try adjusting your filters or generate new pages</p>
                <button class="vgp-btn vgp-green" onclick="window.location.href='generate-pages.php'"><i class="fas fa-plus"></i> Generate Pages</button>
            </div>
        <?php else: ?>
            <div class="pages-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th width="40"><input type="checkbox" id="selectAllHeader" onclick="toggleSelectAll(this)"></th>
                            <th>Title</th>
                            <th>Area</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Created</th>
                            <th width="120">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $pg): ?>
                            <tr class="page-row" data-id="<?php echo $pg['id']; ?>">
                                <td><input type="checkbox" class="page-checkbox" value="<?php echo $pg['id']; ?>" onchange="updateBulkActions()"></td>
                                <td>
                                    <div class="page-title">
                                        <i class="fas fa-file-alt"></i>
                                        <strong><?php echo htmlspecialchars($pg['title']); ?></strong>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($pg['area']); ?></td>
                                <td><span class="category-badge"><?php echo htmlspecialchars($pg['service_category']); ?></span></td>
                                <td>
                                    <?php if ($pg['is_active']): ?>
                                        <span class="status-badge active"><i class="fas fa-check-circle"></i> Active</span>
                                    <?php else: ?>
                                        <span class="status-badge inactive"><i class="fas fa-times-circle"></i> Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo number_format($pg['view_count']); ?></td>
                                <td><?php echo $pg['created_at'] ? date('M j, Y', strtotime($pg['created_at'])) : '<span style="color:#94a3b8;">—</span>'; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php
                                        if ($pg['source'] === 'pillar-page') {
                                            $view_url = rtrim(SITE_URL, '/') . '/' . htmlspecialchars($pg['slug']);
                                        } else {
                                            $view_url = rtrim(SITE_URL, '/') . '/generated-pages/' . htmlspecialchars($pg['slug']) . '.php';
                                        }
                                        ?>
                                        <a href="<?php echo $view_url; ?>" class="btn-action view" target="_blank" title="View"><i class="fas fa-eye"></i></a>
                                        <button class="btn-action edit" onclick="editPage(<?php echo $pg['id']; ?>)" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn-action delete" onclick="deletePage(<?php echo $pg['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            $pg_base = '?filter=' . urlencode($type_filter)
                     . '&category=' . urlencode($filter_category)
                     . '&area='     . urlencode($filter_area)
                     . '&search='   . urlencode($search)
                     . '&per_page=' . $per_page;
            ?>
            <div class="pagination" style="padding:16px 28px;">
                <div class="pagination-info">
                    Showing <?php echo number_format($offset + 1); ?>&ndash;<?php echo number_format(min($offset + $per_page, $total_pages_count)); ?> of <?php echo number_format($total_pages_count); ?> &nbsp;|&nbsp; Page <?php echo $page; ?> of <?php echo $total_pages_pagination; ?>
                </div>
                <div class="pagination-controls">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo $pg_base; ?>&p=1" class="page-btn"><i class="fas fa-angle-double-left"></i></a>
                        <a href="<?php echo $pg_base; ?>&p=<?php echo $page - 1; ?>" class="page-btn"><i class="fas fa-chevron-left"></i> Prev</a>
                    <?php endif; ?>
                    <?php $win = 4; $start = max(1,$page-$win); $end = min($total_pages_pagination,$page+$win); ?>
                    <?php if ($start > 1): ?><a href="<?php echo $pg_base; ?>&p=1" class="page-num">1</a><?php if ($start > 2): ?><span class="page-dots">&hellip;</span><?php endif; ?><?php endif; ?>
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a href="<?php echo $pg_base; ?>&p=<?php echo $i; ?>" class="page-num <?php echo $i==$page?'active':''; ?>"><?php echo $i; ?></a>
                    <?php endfor; ?>
                    <?php if ($end < $total_pages_pagination): ?><?php if ($end < $total_pages_pagination-1): ?><span class="page-dots">&hellip;</span><?php endif; ?><a href="<?php echo $pg_base; ?>&p=<?php echo $total_pages_pagination; ?>" class="page-num"><?php echo $total_pages_pagination; ?></a><?php endif; ?>
                    <?php if ($page < $total_pages_pagination): ?>
                        <a href="<?php echo $pg_base; ?>&p=<?php echo $page + 1; ?>" class="page-btn">Next <i class="fas fa-chevron-right"></i></a>
                        <a href="<?php echo $pg_base; ?>&p=<?php echo $total_pages_pagination; ?>" class="page-btn"><i class="fas fa-angle-double-right"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

</div>

<style>
/* ── Page Container ── */
.view-generated-pages {
    padding: 20px;
    width: 100%;
    box-sizing: border-box;
}

/* ── Page Header ── */
.page-header {
    background: white;
    border-radius: 18px;
    padding: 24px 28px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    margin-bottom: 24px;
    box-shadow: 0 4px 24px rgba(0,0,0,.07);
    border-left: 6px solid transparent;
    border-image: linear-gradient(180deg,#667eea,#764ba2) 1;
}

.header-left { flex: 1; min-width: 0; }

.header-left h1 {
    font-size: 1.8rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 6px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.header-left h1 i { color: #667eea; font-size: 1.5rem; }

.header-left p {
    margin: 0;
    color: #64748b;
    font-size: 14px;
}

.header-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: center;
    flex-shrink: 0;
}

/* ── Buttons (local overrides) ── */
.btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 9px 18px;
    border-radius: 8px;
    border: none;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}
.btn-primary  { background: linear-gradient(135deg, #667eea, #764ba2); color: #fff; }
.btn-danger   { background: #EF4444; color: #fff; }
.btn-secondary{ background: #64748b; color: #fff; }
.btn-sm { padding: 6px 12px; font-size: 12px; }
.btn:hover { opacity: 0.88; transform: translateY(-1px); }

/* ── Filters form ── */
.filters-form {
    display: grid;
    grid-template-columns: repeat(4, 1fr) auto;
    gap: 16px;
    align-items: end;
}

.filter-group { display: flex; flex-direction: column; }

.filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    margin-bottom: 6px;
}

.form-control {
    width: 100%;
    padding: 9px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    background: #fff;
    color: #1e293b;
    box-sizing: border-box;
    transition: border-color 0.2s;
}
.form-control:focus { outline: none; border-color: #667eea; }

.filter-actions {
    display: flex;
    gap: 8px;
    align-self: flex-end;
}

/* ── Card header layout ── */
.card-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: nowrap !important;
}

.card-header h2 {
    flex: 1;
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.table-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    font-weight: 600;
    color: #64748b;
    white-space: nowrap;
    flex-shrink: 0;
    margin-left: 12px;
}

/* ── Pages table: scrollable on narrow screens ── */
.pages-table {
    overflow-x: auto;
    border-radius: 8px;
}

.data-table {
    width: 100%;
    min-width: 800px;
    border-collapse: collapse;
}

/* ── Empty state ── */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: #94a3b8;
}
.empty-state i   { font-size: 48px; margin-bottom: 16px; display: block; }
.empty-state h3  { font-size: 20px; font-weight: 600; color: #475569; margin-bottom: 8px; }
.empty-state p   { font-size: 14px; margin-bottom: 20px; }

.generation-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 24px;
    background: linear-gradient(135deg, #ffffff, #f8f9fa);
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-decoration: none;
    color: inherit;
    transition: all 0.3s ease;
    cursor: pointer;
}

.generation-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.15);
    border-color: #3B82F6;
}

.generation-card.active {
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    border-color: #3B82F6;
    color: white;
    box-shadow: 0 8px 16px rgba(59, 130, 246, 0.3);
}

.generation-card.active h3 {
    color: white;
}

.generation-card.active p {
    color: rgba(255, 255, 255, 0.9);
}

.generation-card.active i {
    color: white !important;
}

.generation-card i {
    margin-bottom: 12px;
}

.generation-card h3 {
    margin: 8px 0;
    font-size: 16px;
    font-weight: 600;
    color: #1e293b;
}

.generation-card p {
    margin: 0;
    font-size: 13px;
    color: #64748b;
}

.filters-card {
    margin-bottom: 20px;
}

/* filters-card has no .card-body wrapper — add padding directly */
.filters-card .filters-form {
    padding: 20px 24px;
}

.filters-form {
    display: grid;
    grid-template-columns: repeat(4, 1fr) auto;
    gap: 16px;
    align-items: end;
}

.filter-group label {
    display: block;
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    margin-bottom: 6px;
}

.bulk-actions-bar {
    padding: 16px 20px;
    background: linear-gradient(135deg, #3B82F6, #8B5CF6);
    border-radius: 12px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: #FFFFFF;
}

.selected-count {
    font-size: 16px;
    font-weight: 600;
}

.bulk-actions {
    display: flex;
    gap: 10px;
}

.data-table thead {
    background: #F8FAFC;
}

.data-table th {
    padding: 14px;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #64748B;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 2px solid #E2E8F0;
}

.data-table td {
    padding: 14px;
    border-bottom: 1px solid #E2E8F0;
}

.page-row:hover {
    background: #F8FAFC;
}

.page-title {
    display: flex;
    align-items: center;
    gap: 8px;
}

.page-title i {
    color: #3B82F6;
}

.category-badge {
    display: inline-block;
    padding: 4px 10px;
    background: #DBEAFE;
    color: #1E40AF;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 600;
}

.status-badge.active {
    background: #D1FAE5;
    color: #065F46;
}

.status-badge.inactive {
    background: #FEE2E2;
    color: #991B1B;
}

.action-buttons {
    display: flex;
    gap: 8px;
}

.btn-action {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    border: none;
    background: #E2E8F0;
    color: #475569;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.btn-action.view:hover {
    background: #3B82F6;
    color: #FFFFFF;
}

.btn-action.edit:hover {
    background: #F59E0B;
    color: #FFFFFF;
}

.btn-action.delete:hover {
    background: #EF4444;
    color: #FFFFFF;
}

.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 2px solid #E2E8F0;
}

.pagination-controls {
    display: flex;
    gap: 8px;
}

.page-btn, .page-num {
    padding: 8px 14px;
    border-radius: 8px;
    border: 2px solid #E2E8F0;
    background: #FFFFFF;
    color: #64748B;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s;
}

.page-num:hover, .page-btn:hover {
    border-color: #3B82F6;
    background: #EFF6FF;
    color: #3B82F6;
}

.page-num.active {
    border-color: #3B82F6;
    background: #3B82F6;
    color: #FFFFFF;
}

@media (max-width: 1024px) {
    .filters-form { grid-template-columns: 1fr; }
}
</style>

<style>
/* ── New theme overrides ───────────────────────────────── */
.seo-page { padding: 0; }
.seo-hero { background: white; border-radius: 18px; box-shadow: 0 4px 24px rgba(0,0,0,.07); padding: 32px 36px; margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; gap: 20px; border-left: 6px solid transparent; border-image: linear-gradient(180deg,#667eea,#764ba2) 1; }
.seo-hero-left h1 { font-size: 30px; font-weight: 800; color: #1e293b; margin: 0 0 6px; display: flex; align-items: center; gap: 12px; }
.seo-hero-left h1 i { color: #667eea; }
.seo-hero-left p { color: #64748b; font-size: 15px; margin: 0; }
.seo-hero-badge { background: linear-gradient(135deg,#667eea,#764ba2); color: white; padding: 8px 20px; border-radius: 30px; font-size: 13px; font-weight: 700; white-space: nowrap; flex-shrink: 0; }
.solution-banner { background: linear-gradient(135deg,rgba(102,126,234,.08),rgba(118,75,162,.05)); border: 1px solid rgba(102,126,234,.2); border-left: 5px solid #667eea; border-radius: 12px; padding: 18px 22px; margin-bottom: 24px; display: flex; align-items: flex-start; gap: 14px; }
.solution-banner .sol-icon { width: 40px; height: 40px; background: linear-gradient(135deg,#667eea,#764ba2); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; flex-shrink: 0; margin-top: 2px; }
.solution-banner strong { color: #3730a3; font-size: 14px; font-weight: 700; display: block; margin-bottom: 4px; }
.solution-banner p { color: #4338ca; font-size: 13.5px; margin: 0; line-height: 1.6; }
/* Stats */
.stats-grid { display: grid; grid-template-columns: repeat(4,1fr); gap: 0; background: white; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,.08); overflow: hidden; margin-bottom: 24px; }
.stat-card { background: white; padding: 22px 18px !important; display: flex !important; align-items: center !important; gap: 14px !important; text-align: left !important; border: none !important; border-radius: 0 !important; box-shadow: none !important; position: relative; transition: background .2s; }
.stat-card:hover { background: #f8faff !important; transform: none !important; }
.stat-card + .stat-card::before { content: ''; position: absolute; left: 0; top: 15%; bottom: 15%; width: 1px; background: #e2e8f0; }
.stat-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white; flex-shrink: 0; }
.stat-icon-wrap.blue   { background: linear-gradient(135deg,#667eea,#764ba2); }
.stat-icon-wrap.green  { background: linear-gradient(135deg,#10b981,#059669); }
.stat-icon-wrap.orange { background: linear-gradient(135deg,#f59e0b,#d97706); }
.stat-icon-wrap.teal   { background: linear-gradient(135deg,#06b6d4,#0891b2); }
.stat-text-wrap { flex: 1; min-width: 0; }
.stat-text-wrap h3 { font-size: 9px !important; color: #94a3b8 !important; text-transform: uppercase !important; letter-spacing: .7px !important; font-weight: 700 !important; margin: 0 0 3px !important; background: none !important; -webkit-text-fill-color: unset !important; }
.stat-text-wrap .value { font-size: 1.55rem !important; font-weight: 800 !important; color: #1e293b !important; line-height: 1.1 !important; margin: 0 !important; background: none !important; -webkit-text-fill-color: unset !important; }
/* Section cards */
.seo-section { background: white; border-radius: 18px; box-shadow: 0 4px 20px rgba(0,0,0,.07); margin-bottom: 24px; overflow: hidden; }
.seo-section-head { padding: 20px 28px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; gap: 12px; }
.seo-section-head .sec-num { width: 34px; height: 34px; border-radius: 9px; display: flex; align-items: center; justify-content: center; font-size: 15px; font-weight: 800; color: white; flex-shrink: 0; }
.sec-num.purple { background: linear-gradient(135deg,#667eea,#764ba2); }
.sec-num.blue   { background: linear-gradient(135deg,#3b82f6,#2563eb); }
.sec-num.amber  { background: linear-gradient(135deg,#f59e0b,#d97706); }
.sec-num.green  { background: linear-gradient(135deg,#10b981,#059669); }
.seo-section-head h2 { font-size: 19px; font-weight: 700; color: #1e293b; margin: 0; background: none !important; -webkit-text-fill-color: unset !important; }
.seo-section-body { padding: 24px 28px; }
/* VGP Buttons */
.vgp-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 24px; }
.vgp-btn { display: inline-flex; align-items: center; gap: 8px; padding: 11px 22px; border: none; border-radius: 10px; font-size: 14px; font-weight: 700; cursor: pointer; text-decoration: none; transition: all .22s; }
.vgp-btn:hover { transform: translateY(-2px); text-decoration: none; }
.vgp-green  { background: linear-gradient(135deg,#28a745,#20c997); color: white; box-shadow: 0 4px 14px rgba(40,167,69,.3); }
.vgp-red    { background: linear-gradient(135deg,#ef4444,#dc2626); color: white; box-shadow: 0 4px 14px rgba(239,68,68,.3); }
.vgp-gray   { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; box-shadow: none; }
.vgp-gray:hover { background: #e2e8f0; transform: none; }
.vgp-purple { background: linear-gradient(135deg,#667eea,#764ba2); color: white; box-shadow: 0 4px 14px rgba(102,126,234,.3); }
/* Filter cards */
.vgp-filter-card { display: flex; flex-direction: column; align-items: center; text-align: center; padding: 22px 16px; background: #f8faff; border: 1.5px solid #e2e8f0; border-radius: 12px; text-decoration: none; color: inherit; transition: all .2s; cursor: pointer; }
.vgp-filter-card:hover { border-color: #667eea; box-shadow: 0 6px 20px rgba(102,126,234,.15); transform: translateY(-3px); text-decoration: none; }
.vgp-filter-card.active { background: linear-gradient(135deg,#667eea,#764ba2); border-color: #667eea; color: white; box-shadow: 0 8px 20px rgba(102,126,234,.35); }
.vgp-filter-card.active h3, .vgp-filter-card.active p, .vgp-filter-card.active i { color: white !important; }
.vgp-filter-card i { font-size: 28px; color: #667eea; margin-bottom: 10px; }
.vgp-filter-card h3 { font-size: 15px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
.vgp-filter-card p { font-size: 12px; color: #64748b; margin: 0; }
/* Filters form */
.vgp-filters-form { display: grid; grid-template-columns: repeat(4,1fr) auto; gap: 16px; align-items: end; }
/* Override card-header h2 for seo-section-head */
.seo-section-head h2 { white-space: normal !important; overflow: visible !important; }
@media(max-width:900px){ .stats-grid { grid-template-columns: repeat(2,1fr); } .vgp-filters-form { grid-template-columns: 1fr 1fr; } }
@media(max-width:600px){ .stats-grid { grid-template-columns: 1fr 1fr; } .seo-hero { flex-direction: column; align-items: flex-start; } }
</style>

<script>
function toggleSelectAll(checkbox) {
    const checkboxes = document.querySelectorAll('.page-checkbox');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    updateBulkActions();
}

function updateBulkActions() {
    const checkboxes = document.querySelectorAll('.page-checkbox:checked');
    const count = checkboxes.length;
    document.getElementById('selectedCount').textContent = count;
    document.getElementById('bulkActionsBar').style.display = count > 0 ? 'flex' : 'none';
}

function bulkDeleteSelected() {
    alert('⚠️ Bulk delete is disabled for AI-protected service pages.\n\nAll 12,032+ service pages are registered in AI Content Security.\n\nPlease delete pages one at a time — each requires your Security PIN and admin email approval.');
}

// ── AI Protection Modal ──────────────────────────────────
let _gcmDelPageId = null;
function deletePage(id) {
    _gcmDelPageId = id;
    document.getElementById('gcmProtFile').textContent = 'Service Page ID: ' + id;
    document.getElementById('gcmProtPin').value = '';
    document.getElementById('gcmProtReason').value = '';
    document.getElementById('gcmProtMsg').style.display = 'none';
    const btn = document.getElementById('gcmProtSubmit');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
    document.getElementById('gcmProtModal').style.display = 'flex';
    setTimeout(() => document.getElementById('gcmProtPin').focus(), 100);
}
function gcmCloseProtModal() {
    document.getElementById('gcmProtModal').style.display = 'none';
    _gcmDelPageId = null;
}
function gcmShowProtMsg(text, type) {
    const el = document.getElementById('gcmProtMsg');
    el.style.cssText = 'display:block;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:10px;' +
        (type==='success' ? 'background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;'
                         : 'background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;');
    el.textContent = text;
}
async function gcmSubmitProtDel() {
    const pin    = document.getElementById('gcmProtPin').value.trim();
    const reason = document.getElementById('gcmProtReason').value.trim();
    const btn    = document.getElementById('gcmProtSubmit');
    if (!pin) { gcmShowProtMsg('Please enter your Security PIN.', 'error'); return; }
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
    document.getElementById('gcmProtMsg').style.display = 'none';
    try {
        const resp = await fetch('../api/delete-pages.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ids: [_gcmDelPageId], pin, reason})
        });
        const data = await resp.json();
        if (data.success) {
            gcmCloseProtModal(); location.reload();
        } else if (data.pending) {
            gcmShowProtMsg('✅ ' + data.message, 'success');
            btn.innerHTML = 'Request Submitted';
            setTimeout(gcmCloseProtModal, 6000);
        } else {
            gcmShowProtMsg('❌ ' + (data.message || 'Error'), 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
        }
    } catch(e) {
        gcmShowProtMsg('Network error: ' + e.message, 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> Submit Request';
    }
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') gcmCloseProtModal(); });

function bulkActivate() {
    const checkboxes = document.querySelectorAll('.page-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    fetch('../api/update-page-status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ids: ids, status: 1})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Pages activated!');
            location.reload();
        }
    });
}

function bulkDeactivate() {
    const checkboxes = document.querySelectorAll('.page-checkbox:checked');
    const ids = Array.from(checkboxes).map(cb => cb.value);
    
    if (ids.length === 0) return;
    
    fetch('../api/update-page-status.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ids: ids, status: 0})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Pages deactivated!');
            location.reload();
        }
    });
}

function deleteAllGeneratedPages() {
    alert('🛡️ BLOCKED by AI Content Security.\n\nAll 12,032+ service pages are AI-protected. Deleting all pages at once is permanently disabled.\n\nTo delete a page, use the individual delete button on each page — it requires your Security PIN and admin email approval.');
}
</script>

<!-- AI Content Protection Modal -->
<div id="gcmProtModal" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.78);backdrop-filter:blur(6px);z-index:20000;align-items:center;justify-content:center;padding:20px;">
  <div style="background:white;max-width:460px;width:100%;border-radius:20px;padding:32px;box-shadow:0 25px 60px rgba(0,0,0,.35);">
    <div style="text-align:center;margin-bottom:22px;">
      <div style="width:64px;height:64px;background:linear-gradient(135deg,#fef3c7,#fde68a);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 14px;box-shadow:0 4px 16px rgba(217,119,6,.25);">
        <i class="fas fa-shield-alt" style="font-size:28px;color:#d97706;"></i>
      </div>
      <h3 style="margin:0 0 8px;font-size:19px;font-weight:700;color:#1e293b;">AI Content Protected</h3>
      <p style="margin:0;color:#64748b;font-size:13px;line-height:1.65;">This service page is registered in <strong>AI Content Security</strong>. Enter your <strong>Security PIN</strong> to submit a deletion request. Nothing will be deleted until you approve it via the email link sent to the admin.</p>
    </div>
    <div style="background:#f8faff;border:1.5px solid #e0e7ff;border-radius:9px;padding:9px 13px;margin-bottom:18px;font-size:12px;color:#64748b;">
      <i class="fas fa-file-code" style="color:#667eea;margin-right:5px;"></i><span id="gcmProtFile"></span>
    </div>
    <div style="margin-bottom:14px;">
      <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Security PIN <span style="color:#ef4444;">*</span></label>
      <input id="gcmProtPin" type="password" maxlength="6" placeholder="••••••"
             style="width:100%;padding:12px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:22px;letter-spacing:8px;text-align:center;outline:none;box-sizing:border-box;"
             onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"
             onkeydown="if(event.key==='Enter')gcmSubmitProtDel()">
    </div>
    <div style="margin-bottom:18px;">
      <label style="font-size:13px;font-weight:600;color:#374151;display:block;margin-bottom:6px;">Reason <span style="color:#94a3b8;font-weight:400;">(optional)</span></label>
      <textarea id="gcmProtReason" rows="2" placeholder="Why do you need to delete this page?"
                style="width:100%;padding:10px 14px;border:2px solid #e2e8f0;border-radius:10px;font-size:13px;resize:none;outline:none;box-sizing:border-box;"
                onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e2e8f0'"></textarea>
    </div>
    <div id="gcmProtMsg" style="display:none;"></div>
    <div style="display:flex;gap:10px;">
      <button onclick="gcmCloseProtModal()" style="flex:1;padding:12px;background:#f1f5f9;border:none;border-radius:10px;font-size:14px;font-weight:600;color:#64748b;cursor:pointer;">Cancel</button>
      <button id="gcmProtSubmit" onclick="gcmSubmitProtDel()" style="flex:2;padding:12px;background:linear-gradient(135deg,#ef4444,#dc2626);border:none;border-radius:10px;font-size:14px;font-weight:700;color:white;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;">
        <i class="fas fa-paper-plane"></i> Submit Request
      </button>
    </div>
  </div>
</div>

<?php include '../includes/footer.php'; ?>
