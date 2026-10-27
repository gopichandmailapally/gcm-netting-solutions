<script src="/assets/js/geo_resolver.js"></script>
<?php
/**
 * GCM Netting Solutions - Unified Floating Action Buttons
 * Renders exactly 3 floating contact buttons:
 * 1. Phone Call Button (Instant dial)
 * 2. WhatsApp Button (Direct smart inquiry chat)
 * 3. Scroll to Top Button (Smooth scroll, appears on scroll > 250px)
 */

if (defined('GCM_FLOATING_BUTTONS_RENDERED')) {
    return;
}
define('GCM_FLOATING_BUTTONS_RENDERED', true);

require_once __DIR__ . '/whatsapp_helper.php';

// Safe normalization without character mask stripping bugs
$raw_phone = defined('COMPANY_PHONE') ? (string)COMPANY_PHONE : '9912399224';
$clean_digits = preg_replace('/[^0-9]/', '', $raw_phone);
if (strlen($clean_digits) === 12 && substr($clean_digits, 0, 2) === '91') {
    $clean_digits = substr($clean_digits, 2);
}
if (strlen($clean_digits) !== 10) {
    $clean_digits = '9912399224';
}

$phone_call_href = 'tel:+91' . $clean_digits;
$phone_display   = '+91 99123 99224';
$whatsapp_num    = defined('COMPANY_WHATSAPP') ? COMPANY_WHATSAPP : '919912399224';
$smart_wa_url    = getGcmWhatsAppUrl();
?>

<!-- GCM Netting Solutions - Exactly 3 Floating Action Buttons -->
<div class="gcm-floating-actions" id="gcmFloatingActions" aria-label="Quick Contact Options">
    <!-- 1. Phone Call Button -->
    <a href="<?php echo $phone_call_href; ?>" 
       class="gcm-fab gcm-fab-phone" 
       title="Call Us (<?php echo $phone_display; ?>)"
       aria-label="Call Us (<?php echo $phone_display; ?>)">
        <i class="fas fa-phone-alt"></i>
        <span class="gcm-fab-tooltip">Call Us</span>
    </a>

    <!-- 2. WhatsApp Button (Smart Dynamic Lead System) -->
    <a href="<?php echo htmlspecialchars($smart_wa_url); ?>" 
       class="gcm-fab gcm-fab-whatsapp" 
       target="_blank" 
       rel="noopener noreferrer"
       title="Chat on WhatsApp"
       aria-label="Chat on WhatsApp">
        <i class="fab fa-whatsapp"></i>
        <span class="gcm-fab-tooltip">WhatsApp</span>
    </a>

    <!-- 3. Cost Estimation Button -->
    <a href="<?php echo defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://www.gcmnettingsolutions.com'; ?>/estimation" 
       class="gcm-fab gcm-fab-estimate" 
       title="Get Instant Price Estimation"
       aria-label="Cost Estimation">
        <i class="fas fa-calculator"></i>
        <span class="gcm-fab-tooltip">Cost Estimation</span>
    </a>

    <!-- 4. Scroll to Top Button -->
    <button type="button" 
            class="gcm-fab gcm-fab-scrolltop" 
            id="gcmScrollTopBtn" 
            onclick="scrollToTop()" 
            title="Scroll to Top"
            aria-label="Scroll to Top">
        <i class="fas fa-arrow-up"></i>
        <span class="gcm-fab-tooltip">Top</span>
    </button>
</div>

<style>
/* Reset and hide any duplicate legacy floating elements */
.floating-actions,
.scroll-to-top,
.floating-buttons:not(.gcm-floating-actions) {
    display: none !important;
    visibility: hidden !important;
    pointer-events: none !important;
}

/* Unified 3 Floating Buttons Container */
.gcm-floating-actions {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 99999;
    pointer-events: none;
}

.gcm-fab {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ffffff !important;
    text-decoration: none !important;
    border: none;
    cursor: pointer;
    font-size: 22px;
    position: relative;
    pointer-events: auto;
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.28);
    transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s ease, opacity 0.25s ease, visibility 0.25s ease;
    user-select: none;
    -webkit-tap-highlight-color: transparent;
}

.gcm-fab:hover {
    transform: translateY(-3px) scale(1.06);
    box-shadow: 0 10px 26px rgba(0, 0, 0, 0.38);
    color: #ffffff !important;
}

.gcm-fab:active {
    transform: translateY(0) scale(0.96);
}

/* 1. Phone Button - Emerald Green */
.gcm-fab-phone {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    animation: gcm-fab-pulse 3s ease-in-out infinite;
}

/* 2. WhatsApp Button - Official WhatsApp Green */
.gcm-fab-whatsapp {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    animation: gcm-fab-pulse 3s ease-in-out infinite 1.5s;
}

/* 2.5 Cost Estimation Button - Golden Orange */
.gcm-fab-estimate {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    animation: gcm-fab-pulse 3s ease-in-out infinite 0.75s;
}

/* 3. Scroll to Top Button - Warm Orange */
.gcm-fab-scrolltop {
    background: linear-gradient(135deg, #FF6600 0%, #E55A00 100%);
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    transform: translateY(12px) scale(0.85);
}

.gcm-fab-scrolltop.visible {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
    transform: translateY(0) scale(1);
}

/* Desktop Tooltips */
.gcm-fab-tooltip {
    position: absolute;
    right: 68px;
    background: #0f172a;
    color: #ffffff;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: 600;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.25);
    transition: opacity 0.2s ease, transform 0.2s ease, visibility 0.2s ease;
    transform: translateX(6px);
}

.gcm-fab-tooltip::after {
    content: '';
    position: absolute;
    right: -5px;
    top: 50%;
    transform: translateY(-50%);
    border-width: 5px 0 5px 6px;
    border-style: solid;
    border-color: transparent transparent transparent #0f172a;
}

.gcm-fab:hover .gcm-fab-tooltip {
    opacity: 1;
    visibility: visible;
    transform: translateX(0);
}

/* Pulse animation */
@keyframes gcm-fab-pulse {
    0%, 100% {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.28);
    }
    50% {
        box-shadow: 0 4px 22px rgba(16, 185, 129, 0.55), 0 0 0 8px rgba(16, 185, 129, 0.15);
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .gcm-floating-actions {
        bottom: 16px;
        right: 16px;
        gap: 10px;
    }
    .gcm-fab {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    .gcm-fab-tooltip {
        display: none !important;
    }
}
</style>

<script>
// Smart Dynamic WhatsApp Click Handler for GCM Netting Solutions (Chennai)
document.addEventListener("click", function(e) {
    var waLink = e.target.closest('a[href*="wa.me"], a[href*="whatsapp.com"], .gcm-fab-whatsapp, .btn-whatsapp, .whatsapp-link, .fab-whatsapp');
    if (!waLink) return;
    
    e.preventDefault();
    
    var currentUrl = window.location.href.split("#")[0];
    var path = window.location.pathname;
    var title = document.title || "";
    var h1Elem = document.querySelector("h1");
    var h1 = h1Elem ? h1Elem.innerText.trim() : "";
    var checkStr = (h1 + " " + title + " " + path).toLowerCase();
    
    // 1. Detect Service
    var service = "";
    if (checkStr.includes("cloth-hanger") || checkStr.includes("cloth hanger") || checkStr.includes("drying-hanger") || checkStr.includes("drying stand") || checkStr.includes("ceiling cloth")) {
        service = "Balcony & Ceiling Cloth Drying Hangers";
    } else if (checkStr.includes("invisible-grill") || checkStr.includes("invisible grill")) {
        service = "SS 316 Marine Grade Invisible Grills";
    } else if (checkStr.includes("box-cricket") || checkStr.includes("box cricket")) {
        service = "Box Cricket Ground Setup & Netting";
    } else if (checkStr.includes("cricket")) {
        service = "Cricket Practice Pitch Nets Installation";
    } else if (checkStr.includes("sports")) {
        service = "Sports & Ground Perimeter Netting";
    } else if (checkStr.includes("spike")) {
        service = "Anti-Bird & Pigeon Spikes Installation";
    } else if (checkStr.includes("pigeon")) {
        service = "Pigeon Safety Nets Installation";
    } else if (checkStr.includes("child")) {
        service = "Children & Pet Balcony Safety Nets";
    } else if (checkStr.includes("balcony")) {
        service = "Balcony Safety Nets Installation";
    } else if (checkStr.includes("duct")) {
        service = "Duct Area Safety Nets";
    } else if (checkStr.includes("coconut")) {
        service = "Coconut Tree Safety Nets";
    } else if (h1) {
        service = h1.replace(/[–—|\-].*$/, "").trim();
    } else {
        service = "Safety Nets & Netting Solutions";
    }
    
    // 2. Detect Locality
    var location = "";
    var match = path.match(/-in-([a-z0-9-]+)/i);
    if (match && match[1]) {
        var areaSlug = match[1];
        if (typeof resolveSmartGeoLocation === "function") {
            location = resolveSmartGeoLocation(areaSlug);
        } else {
            var areaFormatted = areaSlug.split("-").map(function(w) { return w.charAt(0).toUpperCase() + w.slice(1); }).join(" ");
            location = areaFormatted + ", Chennai, Tamil Nadu";
        }
    } else {
        location = "Chennai, Tamil Nadu (All Areas & Pan-India Supply)";
    }
    
    // 3. Pricing Line
    var pricingText = "Please share pricing per sq.ft, technical specifications, and installation availability.";
    if (checkStr.includes("hanger") || checkStr.includes("drying")) {
        pricingText = "Please share pricing per piece / set, model options, technical specifications, and installation availability.";
    } else if (checkStr.includes("spike")) {
        pricingText = "Please share pricing per piece / per strip, technical specifications, and installation availability.";
    } else if (checkStr.includes("box cricket") || checkStr.includes("turf")) {
        pricingText = "Please share turnkey court setup cost / pricing per sq.ft, technical specifications, and installation availability.";
    }
    
    var msg = "Hi GCM Netting Solutions, I would like to inquire about *" + service + "* in *" + location + "*.

" +
              "• Service: " + service + "
" +
              "• Location: " + location + "
" +
              "• Page: " + currentUrl + "
" +
              "• Website: www.gcmnettingsolutions.com

" +
              pricingText;
              
    var targetPhone = "<?php echo preg_replace('/[^0-9]/', '', $whatsapp_num); ?>";
    window.open("https://wa.me/" + targetPhone + "?text=" + encodeURIComponent(msg), "_blank");
});
</script>
