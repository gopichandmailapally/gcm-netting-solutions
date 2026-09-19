<?php
define('GCM_INIT', true);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

$page_title = "Chennai Safety Nets Service Areas Directory | GCM Netting Solutions";
$meta_description = "Complete directory of 188+ Chennai localities served by GCM Netting Solutions. Same day balcony nets, pigeon nets, invisible grills installation across Chennai.";
$meta_keywords = "safety nets chennai areas, pigeon nets near me, balcony safety nets localities chennai, gcm safety nets locations";
$current_page = "services";

$db = Database::getInstance();
$areas = $db->fetchAll("SELECT * FROM service_areas WHERE is_active = 1 ORDER BY area_name ASC") ?: [];
$keywords = $db->fetchAll("SELECT * FROM seo_service_keywords WHERE is_active = 1 ORDER BY display_order ASC LIMIT 12") ?: [];

include __DIR__ . '/includes/modern-header.php';
?>

<div class="container" style="max-width:1200px;margin:40px auto;padding:0 20px;">
    <!-- Breadcrumb -->
    <nav style="font-size:14px;margin-bottom:20px;color:#64748b;">
        <a href="<?php echo SITE_URL; ?>/" style="color:#2563eb;text-decoration:none;">Home</a> &rsaquo; 
        <span>Service Areas Directory</span>
    </nav>

    <div style="text-align:center;margin-bottom:40px;">
        <h1 style="font-size:36px;font-weight:800;color:#1e293b;margin-bottom:12px;">GCM Netting Solutions Service Areas Across Chennai</h1>
        <p style="font-size:17px;color:#64748b;max-width:800px;margin:0 auto 20px;">
            We provide professional, same-day safety net installation, pigeon nets, bird nets, and invisible grills across all <strong>188+ localities in Chennai &amp; Tambaram</strong> with 5-year warranty and free doorstep inspection.
        </p>
        <div style="display:inline-flex;gap:15px;flex-wrap:wrap;justify-content:center;">
            <a href="tel:+919912399224" style="background:#10b981;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;">
                <i class="fas fa-phone"></i> Call: 9912399224
            </a>
            <a href="https://wa.me/919912399224" style="background:#25D366;color:white;padding:12px 24px;border-radius:8px;text-decoration:none;font-weight:700;display:inline-flex;align-items:center;gap:8px;" target="_blank">
                <i class="fab fa-whatsapp"></i> WhatsApp Inquiry
            </a>
        </div>
    </div>

    <!-- Quick Filter & Summary -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:25px;margin-bottom:40px;">
        <h2 style="font-size:20px;font-weight:700;color:#1e293b;margin-top:0;">Popular Services We Provide In Every Area</h2>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:15px;">
            <?php foreach ($keywords as $kw): ?>
                <a href="<?php echo SITE_URL . '/' . htmlspecialchars($kw['keyword_slug']); ?>" 
                   style="background:white;border:1px solid #cbd5e1;padding:8px 16px;border-radius:20px;font-size:14px;color:#334155;text-decoration:none;font-weight:600;transition:all 0.2s;"
                   onmouseover="this.style.borderColor='#2563eb';this.style.color='#2563eb';"
                   onmouseout="this.style.borderColor='#cbd5e1';this.style.color='#334155';">
                    <?php echo htmlspecialchars($kw['keyword_name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- All 188 Localities Grid -->
    <h2 style="font-size:24px;font-weight:800;color:#1e293b;margin-bottom:20px;">All 188+ Localities Served in Chennai</h2>
    <div style="display:grid;grid-template-columns:repeat(auto-fill, minmax(250px, 1fr));gap:15px;">
        <?php foreach ($areas as $area): ?>
            <div style="background:white;border:1px solid #e2e8f0;border-radius:8px;padding:14px;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                <h3 style="font-size:16px;font-weight:700;margin:0 0 6px;color:#0f172a;">
                    <i class="fas fa-map-marker-alt" style="color:#ef4444;margin-right:6px;"></i>
                    <?php echo htmlspecialchars($area['area_name']); ?>
                </h3>
                <div style="font-size:13px;">
                    <a href="<?php echo SITE_URL . '/pigeon-nets-for-balcony-near-me-in-' . htmlspecialchars($area['area_slug']); ?>" style="color:#2563eb;text-decoration:none;display:inline-block;margin-right:8px;">Pigeon Nets</a> &bull;
                    <a href="<?php echo SITE_URL . '/balcony-safety-nets-in-' . htmlspecialchars($area['area_slug']); ?>" style="color:#2563eb;text-decoration:none;display:inline-block;margin-right:8px;">Balcony Nets</a> &bull;
                    <a href="<?php echo SITE_URL . '/invisible-grill-for-balcony-near-me-in-' . htmlspecialchars($area['area_slug']); ?>" style="color:#2563eb;text-decoration:none;">Invisible Grills</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/modern-footer.php'; ?>
