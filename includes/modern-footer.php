<?php
/**
 * Modern Attractive Footer
 * Responsive and feature-rich footer for all pages
 */
if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}
?>
<style>
/* ── FULL-WIDTH RESET + SERVICE PAGE OVERRIDE ────────────────
   Injected via modern-footer.php — appears AFTER every
   generated page's own <style> block in document order.
   Later position wins for equal specificity. !important
   guarantees override of any old cached CSS.
   ──────────────────────────────────────────────────────────── */
html,body{margin:0!important;padding:0!important;overflow-x:hidden!important;}
.gcm-wrap{background:#fff!important;border-radius:0!important;margin-top:0!important;padding:32px 0 48px!important;width:100%!important;max-width:100%!important;box-sizing:border-box!important;}
.gcm-grid{max-width:1380px!important;margin:0 auto!important;padding:0 24px!important;display:grid!important;grid-template-columns:1fr 300px!important;gap:28px!important;width:100%!important;box-sizing:border-box!important;align-items:start!important;}
.gcm-main{min-width:0!important;}.gcm-sidebar{min-width:0!important;}
.gcm-main>.gcm-card{background:#fff!important;border-radius:10px!important;box-shadow:0 2px 12px rgba(0,0,0,.06)!important;border:1px solid #e8eef4!important;padding:28px!important;margin-bottom:24px!important;}
.gcm-main>.gcm-card:last-child{margin-bottom:0!important;}
.gcm-sidebar .gcm-card{background:#fff!important;border-radius:14px!important;padding:22px!important;box-shadow:0 4px 20px rgba(102,126,234,.12)!important;border:1px solid #e0e7ff!important;margin-bottom:18px!important;}
.gcm-content,.gcm-content h2,.gcm-content h3,.gcm-content h4,.gcm-content p,.gcm-content li,.gcm-content strong,.gcm-content em{font-family:'Times New Roman',Times,serif!important;}
.gcm-sb-title,.gcm-sb-links a,.gcm-highlights li{font-family:'Times New Roman',Times,serif!important;}
@media(max-width:900px){.gcm-grid{grid-template-columns:1fr!important;padding:0 12px!important;}.gcm-main{order:1!important;}.gcm-sidebar{order:2!important;}}
/* ── CONTENT-CARD ACCORDION SUPPORT ─────────────────────────
   Pages from generate-pages-api.php use .content-card instead
   of .gcm-content. This block makes accordion work there too.
   ──────────────────────────────────────────────────────────── */
.content-card li.gcm-li-acc{padding:0!important;cursor:default!important;overflow:hidden!important;}
.content-card li.gcm-li-acc::before{content:none!important;display:none!important;}
.content-card ol .gcm-li-chev{background:rgba(99,102,241,.14)!important;}
.content-card ol .gcm-li-chev::before{border-right-color:#6366f1!important;border-bottom-color:#6366f1!important;}
.content-card ol .gcm-li-acc.gcm-li-open .gcm-li-chev{background:rgba(99,102,241,.25)!important;}
</style>

<!-- Modern Footer -->
<footer class="modern-footer">
    <!-- Main Footer -->
    <div class="footer-main">
        <div class="footer-container">
            <!-- Company Info -->
            <div class="footer-column footer-about">
                <div class="footer-logo">
                    <img src="<?php echo SITE_URL; ?>/uploads/logo-footer.png" alt="<?php echo COMPANY_NAME; ?>">
                </div>
                <p class="footer-description">
                    Leading provider of safety nets, pigeon nets, bird nets, cricket nets, and invisible grills in Chennai. 
                    15+ years of experience with 10,000+ satisfied customers.
                </p>
                <div class="footer-certifications">
                    <span class="cert-badge"><i class="fas fa-certificate"></i> ISO Certified</span>
                    <span class="cert-badge"><i class="fas fa-award"></i> Quality Assured</span>
                    <a href="https://www.russea.in" target="_blank" rel="noopener noreferrer" class="cert-badge" style="text-decoration:none;color:inherit;" title="Official Authorized Dealer in Russea™ Branded Nets"><i class="fas fa-shield-alt" style="color:#10B981;"></i> Authorised Russea™ Dealer</a>
                </div>
                <div class="footer-social">
                    <a href="#" class="social-link facebook" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="social-link instagram" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" class="social-link twitter" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="social-link youtube" aria-label="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <a href="#" class="social-link linkedin" aria-label="LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="footer-column">
                <h4 class="footer-title">Quick Links</h4>
                <ul class="footer-links">
                    <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/about"><i class="fas fa-chevron-right"></i> About Us</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/services"><i class="fas fa-chevron-right"></i> Our Services</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/gallery"><i class="fas fa-chevron-right"></i> Gallery</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/reviews"><i class="fas fa-chevron-right"></i> Customer Reviews</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/blogs"><i class="fas fa-chevron-right"></i> Blog</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/faqs"><i class="fas fa-chevron-right"></i> FAQ</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact"><i class="fas fa-chevron-right"></i> Contact Us</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="footer-column footer-contact">
                <h4 class="footer-title">Contact Us</h4>
                <ul class="contact-info">
                    <li>
                        <i class="fas fa-phone-alt"></i>
                        <div>
                            <strong>Phone:</strong>
                            <p><a href="tel:+919912399224">+91 99123 99224</a></p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <div>
                            <strong>Email:</strong>
                            <p><a href="mailto:gcmsafetynets@gmail.com">gcmsafetynets@gmail.com</a></p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <strong>Head Office:</strong>
                            <p>No. 42, Anna Salai, Mount Road,<br>Anna Salai, Chennai - 600002</p>
                        </div>
                    </li>
                    <li>
                        <i class="fas fa-clock"></i>
                        <div>
                            <strong>Working Hours:</strong>
                            <p>Mon - Sun: 8:00 AM - 8:00 PM</p>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- Branch Locations -->
            <div class="footer-column footer-branches">
                <h4 class="footer-title">Our Branches</h4>
                <ul class="branch-list">
                    <li><i class="fas fa-map-pin"></i> T Nagar - Usman Road</li>
                    <li><i class="fas fa-map-pin"></i> Anna Nagar - 2nd Avenue</li>
                    <li><i class="fas fa-map-pin"></i> Velachery - 100 Feet Bypass Road</li>
                    <li><i class="fas fa-map-pin"></i> Adyar - LB Road</li>
                    <li><i class="fas fa-map-pin"></i> Tambaram - GST Road</li>
                    <li><i class="fas fa-map-pin"></i> Porur - Mount Poonamallee Road</li>
                    <li><i class="fas fa-map-pin"></i> OMR - Sholinganallur Junction</li>
                </ul>
                <a href="<?php echo SITE_URL; ?>/contact" class="footer-branch-link">
                    <i class="fas fa-building"></i> View All Addresses
                </a>
            </div>
        </div>
    </div>

    <!-- All Services Section (64 Services with Pillar Page Links) -->
    <div class="footer-all-services">
        <div class="services-container">
            <h3 class="section-title">All Our Services in Chennai</h3>
            <p class="section-subtitle">Choose your service and select from 188+ locations</p>
            
            <div class="services-grid">
                <?php
                // 6 Categories with 64 Services
                $services_structure = [
                    'PIGEON NETS' => [
                        ['name' => 'Pigeon Nets', 'slug' => 'pigeon-nets'],
                        ['name' => 'Pigeon Net', 'slug' => 'pigeon-net'],
                        ['name' => 'Balcony Netting', 'slug' => 'balcony-netting'],
                        ['name' => 'Pigeon Net For Balcony', 'slug' => 'pigeon-net-for-balcony'],
                        ['name' => 'Pigeon Nets Installation', 'slug' => 'pigeon-nets-installation'],
                        ['name' => 'Pigeon Bird Netting', 'slug' => 'pigeon-bird-netting'],
                        ['name' => 'Pigeon Net Installation', 'slug' => 'pigeon-net-installation'],
                        ['name' => 'Pigeon Net Near Me', 'slug' => 'pigeon-net-near-me'],
                        ['name' => 'Pigeon Net For Balcony Near Me', 'slug' => 'pigeon-net-for-balcony-near-me'],
                        ['name' => 'Pigeon Net Installation Near Me', 'slug' => 'pigeon-net-installation-near-me'],
                        ['name' => 'Pigeon Safety Nets', 'slug' => 'pigeon-safety-nets'],
                        ['name' => 'Pigeon Net Price', 'slug' => 'pigeon-net-price'],
                        ['name' => 'Kabutar Jali Near Me', 'slug' => 'kabutar-jali-near-me']
                    ],
                    'BIRD NETS' => [
                        ['name' => 'Bird Nets', 'slug' => 'bird-nets'],
                        ['name' => 'Bird Net', 'slug' => 'bird-net'],
                        ['name' => 'Bird Net For Balcony', 'slug' => 'bird-net-for-balcony'],
                        ['name' => 'Bird Net Near Me', 'slug' => 'bird-net-near-me'],
                        ['name' => 'Nets For Birds', 'slug' => 'nets-for-birds'],
                        ['name' => 'Net For Birds', 'slug' => 'net-for-birds'],
                        ['name' => 'Industrial Bird Netting', 'slug' => 'industrial-bird-netting'],
                        ['name' => 'Bird Netting', 'slug' => 'bird-netting'],
                        ['name' => 'Anti Bird Netting', 'slug' => 'anti-bird-netting']
                    ],
                    'SAFETY NETS' => [
                        ['name' => 'Safety Nets', 'slug' => 'safety-nets'],
                        ['name' => 'Balcony Safety Nets', 'slug' => 'balcony-safety-nets'],
                        ['name' => 'Safety Nets For Balconies', 'slug' => 'safety-nets-for-balconies'],
                        ['name' => 'Duct Area Safety Nets', 'slug' => 'duct-area-safety-nets'],
                        ['name' => 'Monkey Safety Nets', 'slug' => 'monkey-safety-nets'],
                        ['name' => 'Construction Safety Nets', 'slug' => 'construction-safety-nets'],
                        ['name' => 'Industrial Safety Nets', 'slug' => 'industrial-safety-nets'],
                        ['name' => 'Fall Safety Nets', 'slug' => 'fall-safety-nets'],
                        ['name' => 'Fall Protection Nets', 'slug' => 'fall-protection-nets'],
                        ['name' => 'Children Safety Nets', 'slug' => 'children-safety-nets'],
                        ['name' => 'Pet Safety Nets', 'slug' => 'pet-safety-nets']
                    ],
                    'SPORTS NETS' => [
                        ['name' => 'Cricket Nets', 'slug' => 'cricket-nets'],
                        ['name' => 'Cricket Nets Price', 'slug' => 'cricket-nets-price'],
                        ['name' => 'Cricket Nets Near Me', 'slug' => 'cricket-nets-near-me'],
                        ['name' => 'Cricket Practice Net', 'slug' => 'cricket-practice-net'],
                        ['name' => 'Cricket Practice Nets', 'slug' => 'cricket-practice-nets'],
                        ['name' => 'Cricket Net Price', 'slug' => 'cricket-net-price'],
                        ['name' => 'Cricket Indoor Nets Near Me', 'slug' => 'cricket-indoor-nets-near-me'],
                        ['name' => 'Indoor Cricket Nets Near Me', 'slug' => 'indoor-cricket-nets-near-me'],
                        ['name' => 'Sports Nets', 'slug' => 'sports-nets'],
                        ['name' => 'Sports Netting', 'slug' => 'sports-netting'],
                        ['name' => 'Cricket Netting', 'slug' => 'cricket-netting'],
                        ['name' => 'Box Cricket Net', 'slug' => 'box-cricket-net'],
                        ['name' => 'Cricket Net Installation', 'slug' => 'cricket-net-installation']
                    ],
                    'INVISIBLE GRILLS' => [
                        ['name' => 'Invisible Grills', 'slug' => 'invisible-grills'],
                        ['name' => 'Invisible Grill Near Me', 'slug' => 'invisible-grill-near-me'],
                        ['name' => 'SS Invisible Grills', 'slug' => 'ss-invisible-grills'],
                        ['name' => 'Invisible Grill For Balcony', 'slug' => 'invisible-grill-for-balcony'],
                        ['name' => 'Balcony Invisible Grill', 'slug' => 'balcony-invisible-grill'],
                        ['name' => 'Invisible Grill For Balcony Near Me', 'slug' => 'invisible-grill-for-balcony-near-me'],
                        ['name' => 'Invisible Safety Grill', 'slug' => 'invisible-safety-grill'],
                        ['name' => 'Invisible Grill For Safety', 'slug' => 'invisible-grill-for-safety'],
                        ['name' => 'Invisible Grill For Pigeons', 'slug' => 'invisible-grill-for-pigeons']
                    ],
                    'CLOTH HANGERS' => [
                        ['name' => 'Ceiling Cloth Hangers', 'slug' => 'ceiling-cloth-hangers'],
                        ['name' => 'Dry Cloth Hangers', 'slug' => 'dry-cloth-hangers'],
                        ['name' => 'Cloth Drying Hangers', 'slug' => 'cloth-drying-hangers'],
                        ['name' => 'Cloth Hanger For Balcony', 'slug' => 'cloth-hanger-for-balcony'],
                        ['name' => 'Pulley Cloth Drying Hanger', 'slug' => 'pulley-cloth-drying-hanger'],
                        ['name' => 'Pulley Cloth Hanger', 'slug' => 'pulley-cloth-hanger'],
                        ['name' => 'Laundry Hanger Dryer', 'slug' => 'laundry-hanger-dryer'],
                        ['name' => 'Clothes Hanger To Dry Clothes', 'slug' => 'clothes-hanger-to-dry-clothes'],
                        ['name' => 'Clothes Hanger Drier', 'slug' => 'clothes-hanger-drier']
                    ]
                ];
                
                foreach ($services_structure as $category => $services):
                ?>
                <div class="service-category">
                    <h4 class="category-name"><?php echo $category; ?></h4>
                    <ul class="service-links">
                        <?php foreach ($services as $service): ?>
                        <li>
                            <a href="<?php echo SITE_URL; ?>/<?php echo $service['slug']; ?>">
                                <i class="fas fa-angle-right"></i> <?php echo $service['name']; ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Footer Bottom -->
    <div class="footer-bottom">
        <div class="footer-container">
            <div class="footer-bottom-content">
                <div class="copyright">
                    <p>&copy; <?php echo date('Y'); ?> <strong>GCM Netting Solutions</strong>. All Rights Reserved.</p>
                </div>
                <div class="footer-bottom-links">
                    <a href="<?php echo SITE_URL; ?>/privacy-policy">Privacy Policy</a>
                    <span>|</span>
                    <a href="<?php echo SITE_URL; ?>/terms-conditions">Terms & Conditions</a>
                    <span>|</span>
                    <a href="<?php echo SITE_URL; ?>/sitemap.xml">Sitemap</a>
                </div>
                <div class="footer-credits">
                    <p>Designed and Developed by <a href="https://www.ushaleads.in" target="_blank" style="color: #667eea; text-decoration: none; font-weight: 600;">Usha Leads</a></p>
                </div>
            </div>
        </div>
    </div>
</footer>


<script>
// Hero Slider functionality
let currentSlide = 0;
const slides = document.querySelectorAll('.slide');
const dots = document.querySelectorAll('.dot');

function showSlide(index) {
    slides.forEach((slide, i) => {
        slide.classList.remove('active');
        if (dots[i]) dots[i].classList.remove('active');
    });
    
    if (slides[index]) {
        slides[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');
    }
    currentSlide = index;
}

function nextSlide() {
    let next = (currentSlide + 1) % slides.length;
    showSlide(next);
}

function goToSlide(index) {
    showSlide(index);
}

// Auto-advance slider every 5 seconds
if (slides.length > 0) {
    setInterval(nextSlide, 5000);
}

// Scroll to Top functionality
window.addEventListener('scroll', function() {
    const scrollBtn = document.getElementById('scrollToTop');
    if (scrollBtn && window.pageYOffset > 300) {
        scrollBtn.classList.add('visible');
    } else if (scrollBtn) {
        scrollBtn.classList.remove('visible');
    }
});

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
</script>

<!-- Visitor Tracking Script - ADDED FOR LIVE VISITORS FEATURE -->
<script src="<?php echo SITE_URL; ?>/assets/js/visitor-tracking.js"></script>

<!-- Contact Form Handler with Success Popup - UNIVERSAL FOR ALL FORMS -->
<script src="<?php echo SITE_URL; ?>/assets/js/contact-form-handler.js"></script>

<?php
// Include additional JavaScript if specified
if (isset($additional_js) && is_array($additional_js)) {
    foreach ($additional_js as $js_file) {
        echo '<script src="' . SITE_URL . '/' . $js_file . '"></script>' . "\n";
    }
}
?>

<!-- Security Badge -->
<?php include __DIR__ . '/security-badge.php'; ?>

<!-- Content Protection -->
<script src="<?php echo SITE_URL; ?>/assets/js/content-protection.js"></script>

<!-- Visitor Tracking (silent, non-blocking) -->
<script>
(function(){
    try {
        /* Generate or reuse session ID */
        var sid = localStorage.getItem('gcm_sid');
        if (!sid) { sid = Math.random().toString(36).substr(2)+Date.now().toString(36); localStorage.setItem('gcm_sid', sid); }

        /* Device detection */
        var ua = navigator.userAgent;
        var device = /Mobi|Android/i.test(ua) ? 'mobile' : /Tablet|iPad/i.test(ua) ? 'tablet' : 'desktop';
        var browser = 'Unknown', bver = '';
        if (/Edg\//i.test(ua))       { browser='Edge';    bver=(ua.match(/Edg\/([\d.]+)/)||[])[1]||''; }
        else if (/OPR\//i.test(ua))  { browser='Opera';   bver=(ua.match(/OPR\/([\d.]+)/)||[])[1]||''; }
        else if (/Chrome\//i.test(ua)){ browser='Chrome';  bver=(ua.match(/Chrome\/([\d.]+)/)||[])[1]||''; }
        else if (/Firefox\//i.test(ua)){ browser='Firefox'; bver=(ua.match(/Firefox\/([\d.]+)/)||[])[1]||''; }
        else if (/Safari\//i.test(ua)){ browser='Safari';  bver=(ua.match(/Version\/([\d.]+)/)||[])[1]||''; }
        var os = /Windows/i.test(ua)?'Windows':/Mac OS/i.test(ua)?'Mac':/Android/i.test(ua)?'Android':/iOS|iPhone|iPad/i.test(ua)?'iOS':/Linux/i.test(ua)?'Linux':'Other';

        fetch('<?php echo SITE_URL; ?>/api/track-visitor.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                session_id: sid,
                page_url: location.pathname + location.search,
                page_title: document.title,
                referrer: document.referrer,
                browser: browser,
                browser_version: bver,
                os: os,
                device_type: device
            }),
            keepalive: true
        }).catch(function(){});

        /* Heartbeat every 25s — keeps visitor marked as online & tracks time spent */
        var gcm_hb_t0 = Date.now();
        var gcm_hb = setInterval(function(){
            try {
                fetch('<?php echo SITE_URL; ?>/api/visitor-heartbeat.php', {
                    method: 'POST',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({
                        session_id: sid,
                        page_url: location.pathname + location.search,
                        time_spent: Math.round((Date.now() - gcm_hb_t0) / 1000),
                        scroll_depth: document.body.scrollHeight > 0
                            ? Math.min(100, Math.round((window.scrollY + window.innerHeight) / document.body.scrollHeight * 100))
                            : 0
                    }),
                    keepalive: true
                }).catch(function(){});
            } catch(e) {}
        }, 25000);
        window.addEventListener('beforeunload', function(){ clearInterval(gcm_hb); });
    } catch(e) {}
})();
</script>

<!-- Service Page List-Item Accordion — only li items with <strong> titles become collapsible -->
<script>
(function(){
    var content = document.querySelector('.gcm-content') || document.querySelector('.content-card');
    if (!content) return;

    content.querySelectorAll('ul, ol').forEach(function(list){
        var items = Array.from(list.querySelectorAll('li'));
        var hasAcc = false;

        items.forEach(function(li, idx){
            /* Only accordion li items that contain a <strong> or <b> */
            var strong = li.querySelector('strong, b');
            if (!strong) return;

            /* Extract title from strong, body = everything else */
            var titleHTML = strong.innerHTML;

            var tmp = document.createElement('div');
            tmp.innerHTML = li.innerHTML;
            var tmpStrong = tmp.querySelector('strong, b');
            if (tmpStrong) tmpStrong.remove();
            /* Strip leading colon / whitespace / <br> left after removing strong */
            var bodyHTML = tmp.innerHTML
                .replace(/^[\s\u00a0:]+/, '')
                .replace(/^(<br\s*\/?>\s*)+/i, '')
                .trim();

            if (!bodyHTML || bodyHTML.length < 4) return; /* nothing to collapse */

            hasAcc = true;
            li.classList.add('gcm-li-acc');
            /* First item in each list starts open */
            if (idx === 0) li.classList.add('gcm-li-open');

            li.innerHTML =
                '<button class="gcm-li-header" type="button" aria-expanded="' + (idx === 0 ? 'true' : 'false') + '">' +
                    '<span class="gcm-li-title">' + titleHTML + '</span>' +
                    '<span class="gcm-li-chev" aria-hidden="true"></span>' +
                '</button>' +
                '<div class="gcm-li-body"><div class="gcm-li-inner">' + bodyHTML + '</div></div>';
        });

        if (!hasAcc) return;

        /* Click: one open at a time per list */
        list.addEventListener('click', function(e){
            var hdr = e.target.closest('.gcm-li-header');
            if (!hdr) return;
            var li     = hdr.closest('.gcm-li-acc');
            var isOpen = li.classList.contains('gcm-li-open');

            list.querySelectorAll('.gcm-li-acc').forEach(function(item){
                item.classList.remove('gcm-li-open');
                var h = item.querySelector('.gcm-li-header');
                if (h) h.setAttribute('aria-expanded', 'false');
            });

            if (!isOpen){
                li.classList.add('gcm-li-open');
                hdr.setAttribute('aria-expanded', 'true');
            }
        });

        /* Keyboard support */
        list.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ' '){
                var hdr = e.target.closest('.gcm-li-header');
                if (hdr){ e.preventDefault(); hdr.click(); }
            }
        });
    });
})();
</script>

<?php 
if (file_exists(__DIR__ . '/floating-buttons.php')) {
    include __DIR__ . '/floating-buttons.php';
}
?>

</body>
</html>
