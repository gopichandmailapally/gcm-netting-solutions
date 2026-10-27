<?php
/**
 * GCM Netting Solutions - Master Service Areas Directory
 * Complete coverage of Chennai & Tambaram Localities
 * Engineered for maximum SEO, GEO, and AEO discoverability
 */
define('GCM_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title = "Chennai Safety Nets Service Areas Directory | GCM Netting Solutions";
$meta_description = "Browse all localities served by GCM Netting Solutions. Same-day balcony safety nets, pigeon nets, bird nets, invisible grills, and sports nets with 5-year warranty across Chennai & Tambaram.";
$meta_keywords = "safety nets chennai areas, pigeon nets chennai localities, balcony safety nets chennai directory, gcm netting solutions locations";
$current_page = "areas";

$db = Database::getInstance();
$areas = $db->fetchAll("SELECT * FROM service_areas WHERE is_active = 1 ORDER BY zone ASC, area_name ASC") ?: [];
$total_count = count($areas);

// Extract unique zones
$zones = [];
foreach ($areas as $a) {
    $z = !empty($a['zone']) ? trim($a['zone']) : 'Chennai';
    if (!in_array($z, $zones)) {
        $zones[] = $z;
    }
}
sort($zones);

include __DIR__ . '/includes/modern-header.php';
?>

<!-- Schema.org Breadcrumb & WebPage -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "CollectionPage",
  "name": "<?php echo htmlspecialchars($page_title); ?>",
  "description": "<?php echo htmlspecialchars($meta_description); ?>",
  "url": "<?php echo SITE_URL; ?>/all-areas",
  "breadcrumb": {
    "@type": "BreadcrumbList",
    "itemListElement": [
      { "@type": "ListItem", "position": 1, "name": "Home", "item": "<?php echo SITE_URL; ?>/" },
      { "@type": "ListItem", "position": 2, "name": "Service Areas", "item": "<?php echo SITE_URL; ?>/all-areas" }
    ]
  }
}
</script>

<style>
.area-search-wrap {
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
    color: white;
    padding: 60px 20px;
    text-align: center;
    border-radius: 0 0 24px 24px;
    margin-bottom: 35px;
}
.area-search-input-box {
    max-width: 650px;
    margin: 25px auto 0;
    position: relative;
}
.area-search-input-box input {
    width: 100%;
    padding: 16px 24px 16px 54px;
    border-radius: 30px;
    border: 2px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.98);
    font-size: 16px;
    color: #0f172a;
    outline: none;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    transition: all 0.3s;
}
.area-search-input-box input:focus {
    border-color: #38bdf8;
    box-shadow: 0 12px 30px rgba(56,189,248,0.3);
}
.area-search-input-box i {
    position: absolute;
    left: 22px;
    top: 18px;
    color: #64748b;
    font-size: 18px;
}
.zone-pill {
    padding: 7px 16px;
    border-radius: 20px;
    background: #f1f5f9;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    user-select: none;
}
.zone-pill:hover, .zone-pill.active {
    background: #2563eb;
    border-color: #2563eb;
    color: white;
}
.area-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 18px 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    transition: all 0.25s ease;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.area-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(37,99,235,0.12);
    border-color: #93c5fd;
}
.service-link-tag {
    display: inline-block;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    color: #2563eb;
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 600;
    text-decoration: none;
    margin: 3px 4px 3px 0;
    transition: all 0.15s;
}
.service-link-tag:hover {
    background: #2563eb;
    color: #ffffff;
    border-color: #2563eb;
}
</style>

<!-- Hero & Search Header -->
<div class="area-search-wrap">
    <div style="max-width: 900px; margin: 0 auto;">
        <span style="display:inline-block; background: rgba(56,189,248,0.15); color: #38bdf8; font-size: 13px; font-weight: 700; padding: 6px 16px; border-radius: 20px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; border: 1px solid rgba(56,189,248,0.3);">
            <i class="fas fa-map-marked-alt"></i> Complete Chennai & Tambaram Network
        </span>
        <h1 style="font-size: 36px; font-weight: 800; margin-bottom: 14px; line-height: 1.2;">
            All <?php echo $total_count; ?>+ Service Localities in Chennai
        </h1>
        <p style="font-size: 17px; color: #cbd5e1; max-width: 760px; margin: 0 auto; line-height: 1.6;">
            GCM Netting Solutions provides same-day installation of genuine <strong>Russea™ Branded Nets</strong>, pigeon nets, balcony safety nets, and invisible grills across all <?php echo $total_count; ?>+ localities with 5-year replacement warranty.
        </p>

        <!-- Live Instant Search Bar -->
        <div class="area-search-input-box">
            <i class="fas fa-search"></i>
            <input type="text" id="areaSearchInput" onkeyup="filterAreas()" placeholder="Type your area (e.g. T Nagar, Anna Nagar, Velachery)..." autofocus>
        </div>
        
        <div style="margin-top: 15px; font-size: 14px; color: #94a3b8;">
            Showing <strong id="visibleCount" style="color: #38bdf8;"><?php echo $total_count; ?></strong> of <?php echo $total_count; ?> localities
        </div>
    </div>
</div>

<div class="container" style="max-width: 1380px; margin: 0 auto; padding: 0 20px 60px;">
    <!-- Zone Filter Pills -->
    <div style="margin-bottom: 25px;">
        <div style="font-size: 13px; font-weight: 700; color: #64748b; text-transform: uppercase; margin-bottom: 10px;">
            Filter by Chennai Zone:
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 8px;">
            <button class="zone-pill active" onclick="filterByZone('all', this)">All Chennai (<?php echo $total_count; ?>)</button>
            <?php foreach ($zones as $zone): 
                $z_count = count(array_filter($areas, function($item) use ($zone) { return ($item['zone'] ?? '') === $zone; }));
            ?>
            <button class="zone-pill" onclick="filterByZone('<?php echo htmlspecialchars(strtolower($zone)); ?>', this)">
                <?php echo htmlspecialchars($zone); ?> (<?php echo $z_count; ?>)
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Areas Grid -->
    <div id="areasGrid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px;">
        <?php foreach ($areas as $area): 
            $a_name = htmlspecialchars($area['area_name']);
            $a_slug = htmlspecialchars($area['area_slug']);
            $a_zone = htmlspecialchars($area['zone'] ?? 'Chennai');
            $a_pincode = htmlspecialchars(!empty($area['pincode']) ? $area['pincode'] : '500001');
        ?>
        <div class="area-card" data-name="<?php echo strtolower($a_name); ?>" data-zone="<?php echo strtolower($a_zone); ?>">
            <div>
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 6px;">
                    <h3 style="font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; line-height: 1.3;">
                        <i class="fas fa-map-marker-alt" style="color: #ef4444; font-size: 14px; margin-right: 5px;"></i>
                        <?php echo $a_name; ?>
                    </h3>
                </div>
                <div style="font-size: 12px; color: #64748b; margin-bottom: 12px; display: flex; gap: 8px;">
                    <span style="background: #eff6ff; color: #2563eb; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                        <?php echo $a_zone; ?>
                    </span>
                    <span style="color: #94a3b8;">PIN: <?php echo $a_pincode; ?></span>
                </div>

                <div style="margin-bottom: 14px;">
                    <div style="font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; margin-bottom: 4px;">Top Services in <?php echo $a_name; ?>:</div>
                    <a href="<?php echo SITE_URL . '/pigeon-nets-in-' . $a_slug; ?>" class="service-link-tag">Pigeon Nets</a>
                    <a href="<?php echo SITE_URL . '/balcony-safety-nets-in-' . $a_slug; ?>" class="service-link-tag">Balcony Nets</a>
                    <a href="<?php echo SITE_URL . '/anti-bird-nets-in-' . $a_slug; ?>" class="service-link-tag">Bird Nets</a>
                    <a href="<?php echo SITE_URL . '/cricket-nets-in-' . $a_slug; ?>" class="service-link-tag">Cricket Nets</a>
                    <a href="<?php echo SITE_URL . '/invisible-grills-in-' . $a_slug; ?>" class="service-link-tag">Invisible Grills</a>
                </div>
            </div>

            <div style="display: flex; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: 8px;">
                <a href="tel:+919912399224" style="flex: 1; text-align: center; background: #f0fdf4; border: 1px solid #bbf7d0; color: #15803d; padding: 6px 0; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <i class="fas fa-phone-alt"></i> Call
                </a>
                <a href="https://wa.me/919912399224?text=Hi%20GCM%20Netting%20Solutions,%20I%20need%20safety%20net%20installation%20in%20<?php echo urlencode($a_name); ?>" target="_blank" rel="noopener" style="flex: 1; text-align: center; background: #25D366; color: white; padding: 6px 0; border-radius: 6px; font-size: 12px; font-weight: 700; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 4px;">
                    <i class="fab fa-whatsapp"></i> Chat
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- No results state -->
    <div id="noResults" style="display: none; text-align: center; padding: 60px 20px; background: white; border: 1px solid #e2e8f0; border-radius: 12px; margin-top: 20px;">
        <i class="fas fa-search-location" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
        <h3 style="font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 8px;">No Localities Found</h3>
        <p style="font-size: 15px; color: #64748b; max-width: 500px; margin: 0 auto 20px;">
            We serve every street in Chennai. Call our direct hotline to book a free doorstep inspection in your location.
        </p>
        <a href="tel:+919912399224" style="background: #2563eb; color: white; padding: 10px 24px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fas fa-phone-alt"></i> Call +91 9912399224
        </a>
    </div>
</div>

<!-- Client-side filtering script -->
<script>
let currentZone = 'all';

function filterAreas() {
    const input = document.getElementById('areaSearchInput').value.toLowerCase().trim();
    const cards = document.querySelectorAll('.area-card');
    let visible = 0;

    cards.forEach(card => {
        const name = card.getAttribute('data-name') || '';
        const zone = card.getAttribute('data-zone') || '';
        
        const matchesQuery = input === '' || name.includes(input);
        const matchesZone = currentZone === 'all' || zone.includes(currentZone);

        if (matchesQuery && matchesZone) {
            card.style.display = 'flex';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    document.getElementById('visibleCount').innerText = visible;
    document.getElementById('noResults').style.display = visible === 0 ? 'block' : 'none';
}

function filterByZone(zone, el) {
    currentZone = zone;
    document.querySelectorAll('.zone-pill').forEach(btn => btn.classList.remove('active'));
    if (el) el.classList.add('active');
    filterAreas();
}
</script>

<?php include __DIR__ . '/includes/modern-footer.php'; ?>
