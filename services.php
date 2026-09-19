<?php
/**
 * GCM Netting Solutions - Our Services Page
 * Overview of all services offered
 */

define('GCM_INIT', true);
require_once 'config/config.php';

$page_title = 'Our Services - Safety Nets, Pigeon Nets & More in Chennai';
$meta_description = 'GCM Netting Solutions offers pigeon nets, bird nets, safety nets, sports nets, invisible grills and cloth hangers installation services across Chennai. 15+ years experience.';
$meta_keywords = 'safety nets chennai, pigeon nets, bird nets, invisible grills, sports nets, cloth hangers installation';

$current_page = 'services';

include 'includes/modern-header.php';
?>

<style>
/* ── Services Page ───────────────────────────────────────── */
.services-page {
    background: #f8f9fa;
    min-height: 100vh;
    padding-bottom: 70px;
}

.page-banner {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 80px 0;
    margin-bottom: 50px;
    color: white;
    text-align: center;
}
.page-banner h1 { font-size: 48px; font-weight: 700; margin-bottom: 15px; color: white; }
.page-banner p  { font-size: 20px; opacity: 0.9; }

.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

/* ── Why Choose Us bar ───────────────────────────────────── */
.trust-bar {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    justify-content: center;
    margin-bottom: 50px;
}
.trust-item {
    display: flex;
    align-items: center;
    gap: 10px;
    background: white;
    padding: 16px 24px;
    border-radius: 50px;
    box-shadow: 0 4px 15px rgba(0,0,0,.08);
    font-weight: 600;
    color: #2d3748;
    font-size: 14px;
}
.trust-item i { color: #667eea; font-size: 18px; }

/* ── Category grid ───────────────────────────────────────── */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 28px;
    margin-bottom: 60px;
}

.cat-card {
    background: white;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,.07);
    transition: transform .3s, box-shadow .3s;
}
.cat-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 35px rgba(0,0,0,.13);
}

.cat-header {
    padding: 28px 26px 20px;
    display: flex;
    align-items: flex-start;
    gap: 16px;
}
.cat-icon {
    width: 56px; height: 56px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    font-size: 24px;
    color: white;
    flex-shrink: 0;
}
.cat-icon.c0 { background: linear-gradient(135deg,#667eea,#764ba2); }
.cat-icon.c1 { background: linear-gradient(135deg,#11998e,#38ef7d); }
.cat-icon.c2 { background: linear-gradient(135deg,#f7971e,#ffd200); }
.cat-icon.c3 { background: linear-gradient(135deg,#ee0979,#ff6a00); }
.cat-icon.c4 { background: linear-gradient(135deg,#2193b0,#6dd5ed); }
.cat-icon.c5 { background: linear-gradient(135deg,#834d9b,#d04ed6); }

.cat-title { font-size: 20px; font-weight: 700; color: #1a202c; margin-bottom: 6px; }
.cat-desc  { font-size: 13px; color: #718096; line-height: 1.5; }

.service-list {
    padding: 0 26px 26px;
    display: flex;
    flex-direction: column;
    gap: 6px;
}
.service-link {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 14px;
    border-radius: 9px;
    text-decoration: none;
    color: #4a5568;
    font-size: 14px;
    font-weight: 500;
    transition: all .2s;
    background: #f7fafc;
}
.service-link:hover {
    background: linear-gradient(135deg,rgba(102,126,234,.12),rgba(118,75,162,.12));
    color: #667eea;
    transform: translateX(4px);
}
.service-link i { color: #a0aec0; font-size: 11px; transition: color .2s; }
.service-link:hover i { color: #667eea; }

.cat-footer {
    padding: 0 26px 24px;
}
.btn-viewall {
    display: block;
    text-align: center;
    padding: 12px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    font-size: 13px;
    font-weight: 700;
    letter-spacing: .3px;
    transition: all .2s;
}
.btn-viewall:hover { transform: translateY(-2px); box-shadow: 0 6px 18px rgba(102,126,234,.4); color: white; }

/* ── CTA section ─────────────────────────────────────────── */
.cta-section {
    background: linear-gradient(135deg,#667eea,#764ba2);
    border-radius: 20px;
    padding: 50px 40px;
    text-align: center;
    color: white;
}
.cta-section h2 { font-size: 32px; font-weight: 700; margin-bottom: 14px; }
.cta-section p  { font-size: 17px; opacity: .9; margin-bottom: 28px; }
.cta-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.btn-white {
    padding: 14px 32px;
    background: white;
    color: #667eea;
    text-decoration: none;
    border-radius: 50px;
    font-weight: 700;
    font-size: 15px;
    transition: all .2s;
}
.btn-white:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,.2); color: #667eea; }
.btn-outline-white {
    padding: 14px 32px;
    background: transparent;
    color: white;
    text-decoration: none;
    border-radius: 50px;
    border: 2px solid rgba(255,255,255,.7);
    font-weight: 700;
    font-size: 15px;
    transition: all .2s;
}
.btn-outline-white:hover { background: rgba(255,255,255,.15); color: white; }

/* ── Responsive ──────────────────────────────────────────── */
@media (max-width: 1024px) { .categories-grid { grid-template-columns: repeat(2,1fr); } }
@media (max-width: 640px)  {
    .page-banner h1 { font-size: 32px; }
    .categories-grid { grid-template-columns: 1fr; }
    .cta-section { padding: 36px 22px; }
}
</style>

<div class="services-page">

    <!-- Banner -->
    <div class="page-banner">
        <div class="container">
            <h1><i class="fas fa-tools"></i> Our Services</h1>
            <p>Professional safety net &amp; grill installation across Chennai</p>
        </div>
    </div>

    <div class="container">

        <!-- Trust badges -->
        <div class="trust-bar">
            <div class="trust-item"><i class="fas fa-certificate" style="color:#10b981;"></i> <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:none;">Authorised <span style="color:#10b981;font-weight:700;text-decoration:underline;">Russea™</span> Dealer</a></div>
            <div class="trust-item"><i class="fas fa-star"></i> 15+ Years Experience</div>
            <div class="trust-item"><i class="fas fa-users"></i> 10,000+ Happy Customers</div>
            <div class="trust-item"><i class="fas fa-map-marker-alt"></i> 188+ Locations Covered</div>
            <div class="trust-item"><i class="fas fa-shield-alt"></i> 5-Year Net Warranty</div>
            <div class="trust-item"><i class="fas fa-clock"></i> Same-Day Service Available</div>
        </div>

        <!-- Service categories -->
        <?php
        $categories = [
            [
                'name'  => 'Pigeon Nets',
                'icon'  => 'fas fa-dove',
                'ci'    => 'c0',
                'desc'  => 'Keep pigeons and birds away from your balcony, terrace, and open spaces with our durable netting solutions.',
                'pillar'=> 'pigeon-nets',
                'services' => [
                    ['name'=>'Pigeon Nets',                    'slug'=>'pigeon-nets'],
                    ['name'=>'Balcony Netting',                'slug'=>'balcony-netting'],
                    ['name'=>'Pigeon Net For Balcony',         'slug'=>'pigeon-net-for-balcony'],
                    ['name'=>'Pigeon Nets Installation',       'slug'=>'pigeon-nets-installation'],
                    ['name'=>'Pigeon Bird Netting',            'slug'=>'pigeon-bird-netting'],
                    ['name'=>'Pigeon Net Near Me',             'slug'=>'pigeon-net-near-me'],
                    ['name'=>'Pigeon Safety Nets',             'slug'=>'pigeon-safety-nets'],
                    ['name'=>'Pigeon Net Price',               'slug'=>'pigeon-net-price'],
                    ['name'=>'Kabutar Jali Near Me',           'slug'=>'kabutar-jali-near-me'],
                ],
            ],
            [
                'name'  => 'Bird Nets',
                'icon'  => 'fas fa-feather-alt',
                'ci'    => 'c1',
                'desc'  => 'Industrial-grade bird netting for warehouses, factories, commercial buildings and residential properties.',
                'pillar'=> 'bird-nets',
                'services' => [
                    ['name'=>'Bird Nets',                 'slug'=>'bird-nets'],
                    ['name'=>'Bird Net For Balcony',      'slug'=>'bird-net-for-balcony'],
                    ['name'=>'Bird Net Near Me',          'slug'=>'bird-net-near-me'],
                    ['name'=>'Nets For Birds',            'slug'=>'nets-for-birds'],
                    ['name'=>'Industrial Bird Netting',   'slug'=>'industrial-bird-netting'],
                    ['name'=>'Bird Netting',              'slug'=>'bird-netting'],
                    ['name'=>'Anti Bird Netting',         'slug'=>'anti-bird-netting'],
                ],
            ],
            [
                'name'  => 'Safety Nets',
                'icon'  => 'fas fa-shield-alt',
                'ci'    => 'c2',
                'desc'  => 'High-strength safety nets for balconies, duct areas, children, pets, construction and industrial use.',
                'pillar'=> 'safety-nets',
                'services' => [
                    ['name'=>'Safety Nets',                   'slug'=>'safety-nets'],
                    ['name'=>'Balcony Safety Nets',           'slug'=>'balcony-safety-nets'],
                    ['name'=>'Safety Nets For Balconies',     'slug'=>'safety-nets-for-balconies'],
                    ['name'=>'Duct Area Safety Nets',         'slug'=>'duct-area-safety-nets'],
                    ['name'=>'Monkey Safety Nets',            'slug'=>'monkey-safety-nets'],
                    ['name'=>'Construction Safety Nets',      'slug'=>'construction-safety-nets'],
                    ['name'=>'Children Safety Nets',          'slug'=>'children-safety-nets'],
                    ['name'=>'Pet Safety Nets',               'slug'=>'pet-safety-nets'],
                    ['name'=>'Fall Protection Nets',          'slug'=>'fall-protection-nets'],
                ],
            ],
            [
                'name'  => 'Sports Nets',
                'icon'  => 'fas fa-futbol',
                'ci'    => 'c3',
                'desc'  => 'Professional cricket nets, sports netting for practice facilities, box cricket, indoor and outdoor venues.',
                'pillar'=> 'cricket-nets',
                'services' => [
                    ['name'=>'Cricket Nets',                  'slug'=>'cricket-nets'],
                    ['name'=>'Cricket Nets Price',            'slug'=>'cricket-nets-price'],
                    ['name'=>'Cricket Nets Near Me',          'slug'=>'cricket-nets-near-me'],
                    ['name'=>'Cricket Practice Nets',         'slug'=>'cricket-practice-nets'],
                    ['name'=>'Cricket Net Price',             'slug'=>'cricket-net-price'],
                    ['name'=>'Indoor Cricket Nets Near Me',   'slug'=>'indoor-cricket-nets-near-me'],
                    ['name'=>'Sports Nets',                   'slug'=>'sports-nets'],
                    ['name'=>'Box Cricket Net',               'slug'=>'box-cricket-net'],
                    ['name'=>'Cricket Net Installation',      'slug'=>'cricket-net-installation'],
                ],
            ],
            [
                'name'  => 'Invisible Grills',
                'icon'  => 'fas fa-border-all',
                'ci'    => 'c4',
                'desc'  => 'Stainless steel invisible grills for balconies and windows — unobstructed view with maximum safety.',
                'pillar'=> 'invisible-grills',
                'services' => [
                    ['name'=>'Invisible Grills',                    'slug'=>'invisible-grills'],
                    ['name'=>'Invisible Grill Near Me',             'slug'=>'invisible-grill-near-me'],
                    ['name'=>'SS Invisible Grills',                 'slug'=>'ss-invisible-grills'],
                    ['name'=>'Invisible Grill For Balcony',         'slug'=>'invisible-grill-for-balcony'],
                    ['name'=>'Balcony Invisible Grill',             'slug'=>'balcony-invisible-grill'],
                    ['name'=>'Invisible Safety Grill',              'slug'=>'invisible-safety-grill'],
                    ['name'=>'Invisible Grill For Safety',          'slug'=>'invisible-grill-for-safety'],
                    ['name'=>'Invisible Grill For Pigeons',         'slug'=>'invisible-grill-for-pigeons'],
                ],
            ],
            [
                'name'  => 'Cloth Hangers',
                'icon'  => 'fas fa-tshirt',
                'ci'    => 'c5',
                'desc'  => 'Space-saving ceiling and pulley cloth drying hangers for balconies, terraces and indoor spaces.',
                'pillar'=> 'ceiling-cloth-hangers',
                'services' => [
                    ['name'=>'Ceiling Cloth Hangers',          'slug'=>'ceiling-cloth-hangers'],
                    ['name'=>'Dry Cloth Hangers',              'slug'=>'dry-cloth-hangers'],
                    ['name'=>'Cloth Drying Hangers',           'slug'=>'cloth-drying-hangers'],
                    ['name'=>'Cloth Hanger For Balcony',       'slug'=>'cloth-hanger-for-balcony'],
                    ['name'=>'Pulley Cloth Drying Hanger',     'slug'=>'pulley-cloth-drying-hanger'],
                    ['name'=>'Pulley Cloth Hanger',            'slug'=>'pulley-cloth-hanger'],
                    ['name'=>'Laundry Hanger Dryer',           'slug'=>'laundry-hanger-dryer'],
                    ['name'=>'Clothes Hanger To Dry Clothes',  'slug'=>'clothes-hanger-to-dry-clothes'],
                ],
            ],
        ];
        ?>

        <div class="categories-grid">
            <?php foreach ($categories as $cat): ?>
            <div class="cat-card" id="<?php echo htmlspecialchars($cat['pillar']); ?>">
                <div class="cat-header">
                    <div class="cat-icon <?php echo $cat['ci']; ?>">
                        <i class="<?php echo $cat['icon']; ?>"></i>
                    </div>
                    <div>
                        <div class="cat-title"><?php echo $cat['name']; ?></div>
                        <div class="cat-desc"><?php echo $cat['desc']; ?></div>
                    </div>
                </div>
                <div class="service-list">
                    <?php foreach ($cat['services'] as $svc): ?>
                    <a href="<?php echo SITE_URL; ?>/<?php echo $svc['slug']; ?>.php" class="service-link">
                        <i class="fas fa-angle-right"></i>
                        <?php echo $svc['name']; ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <div class="cat-footer">
                    <a href="<?php echo SITE_URL; ?>/<?php echo $cat['pillar']; ?>.php" class="btn-viewall">
                        <i class="fas fa-arrow-right"></i> View All <?php echo $cat['name']; ?> Services
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- CTA -->
        <div class="cta-section">
            <h2><i class="fas fa-phone-alt"></i> Need a Free Site Visit?</h2>
            <p>Our experts visit your location, take measurements, and provide a no-obligation quote — completely free.</p>
            <div class="cta-buttons">
                <a href="tel:+919912399224" class="btn-white">
                    <i class="fas fa-phone"></i> Call Now: +91 99123 99224
                </a>
                <a href="<?php echo SITE_URL; ?>/contact" class="btn-outline-white">
                    <i class="fas fa-envelope"></i> Get a Free Quote
                </a>
            </div>
        </div>

    </div><!-- /.container -->
</div><!-- /.services-page -->

<?php include 'includes/modern-footer.php'; ?>
