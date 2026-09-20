<?php
/**
 * 404 Error Page - GCM Netting Solutions Chennai
 * Returns authentic HTTP 404 status code while guiding visitors to active services.
 */
http_response_code(404);
define('GCM_INIT', true);
require_once __DIR__ . '/config/config.php';

$page_title = "404 - Page Not Found | GCM Netting Solutions Chennai";
$meta_description = "The requested page was not found. Explore GCM Netting Solutions Chennai - Balcony Safety Nets, Pigeon Nets, Invisible Grills, and Sports Nets. Call 9912399224.";
$current_page = '404';

include __DIR__ . '/includes/header.php';
?>

<section style="padding: 80px 20px 100px; background: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%); min-height: 70vh; display: flex; align-items: center;">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
        <div style="font-size: 110px; font-weight: 900; line-height: 1; color: #0284c7; letter-spacing: -2px; margin-bottom: 12px; text-shadow: 0 4px 16px rgba(2,132,199,0.15);">
            404
        </div>
        <h1 style="font-size: 32px; font-weight: 800; color: #0f172a; margin-bottom: 14px;">
            Oops! Page Not Found
        </h1>
        <p style="font-size: 17px; color: #475569; max-width: 620px; margin: 0 auto 36px; line-height: 1.6;">
            The page you are looking for might have been moved, renamed, or is temporarily unavailable. Browse our popular safety net solutions in Chennai or speak with our installation experts right away.
        </p>

        <!-- Direct Contact CTAs -->
        <div style="display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; margin-bottom: 48px;">
            <a href="tel:+919912399224" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; background: linear-gradient(135deg, #059669, #10b981); color: #fff; border-radius: 12px; font-weight: 700; font-size: 16px; text-decoration: none; box-shadow: 0 8px 24px rgba(16,185,129,0.35);">
                <i class="fas fa-phone-alt"></i> Call +91 99123 99224
            </a>
            <a href="https://wa.me/919912399224?text=Hi%20GCM%20Netting%20Solutions,%20I%20need%20assistance%20with%20netting%20services%20in%20Chennai" target="_blank" rel="noopener" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; background: #25D366; color: #fff; border-radius: 12px; font-weight: 700; font-size: 16px; text-decoration: none; box-shadow: 0 8px 24px rgba(37,211,102,0.35);">
                <i class="fab fa-whatsapp"></i> Chat on WhatsApp
            </a>
            <a href="/" style="display: inline-flex; align-items: center; gap: 10px; padding: 14px 28px; background: #0f172a; color: #fff; border-radius: 12px; font-weight: 700; font-size: 16px; text-decoration: none;">
                <i class="fas fa-home"></i> Back to Homepage
            </a>
        </div>

        <!-- Quick Service Links -->
        <div style="background: #ffffff; border-radius: 20px; padding: 36px 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.06); text-align: left;">
            <h2 style="font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 20px; text-align: center;">
                Popular Safety Netting Solutions in Chennai
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <a href="/balcony-safety-nets-in-chennai" style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; font-weight: 600; transition: all 0.2s;">
                    <i class="fas fa-shield-alt" style="color: #0284c7; font-size: 18px;"></i> Balcony Safety Nets
                </a>
                <a href="/pigeon-nets-in-chennai" style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; font-weight: 600; transition: all 0.2s;">
                    <i class="fas fa-dove" style="color: #10b981; font-size: 18px;"></i> Pigeon / Bird Nets
                </a>
                <a href="/invisible-grills-in-chennai" style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; font-weight: 600; transition: all 0.2s;">
                    <i class="fas fa-grip-lines" style="color: #6366f1; font-size: 18px;"></i> SS 316 Invisible Grills
                </a>
                <a href="/children-safety-nets-in-chennai" style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; font-weight: 600; transition: all 0.2s;">
                    <i class="fas fa-child" style="color: #f59e0b; font-size: 18px;"></i> Children Safety Nets
                </a>
                <a href="/cricket-nets-in-chennai" style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; font-weight: 600; transition: all 0.2s;">
                    <i class="fas fa-baseball-ball" style="color: #ec4899; font-size: 18px;"></i> Cricket &amp; Sports Nets
                </a>
                <a href="/cloth-drying-hangers-in-chennai" style="display: flex; align-items: center; gap: 12px; padding: 14px 18px; border-radius: 10px; background: #f8fafc; border: 1px solid #e2e8f0; text-decoration: none; color: #1e293b; font-weight: 600; transition: all 0.2s;">
                    <i class="fas fa-tshirt" style="color: #8b5cf6; font-size: 18px;"></i> Ceiling Cloth Hangers
                </a>
            </div>
            <div style="margin-top: 24px; text-align: center; font-size: 14px; color: #64748b;">
                <i class="fas fa-certificate" style="color: #10b981; margin-right: 6px;"></i>
                Authorised Dealers in <a href="https://www.russea.in" target="_blank" rel="noopener" style="color: #0284c7; font-weight: 700; text-decoration: underline;">Russea™ Branded Nets</a> — Top Quality Netting Brand
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
