<?php
/**
 * GCM Netting Solutions - Terms & Conditions
 */
define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

// Fetch page content from database
$db = Database::getInstance();
$page_content = $db->fetchOne("SELECT * FROM page_content WHERE page_slug = 'terms-conditions'");

if (!$page_content) {
    // Default content if not in database
    $page_content = [
        'page_title' => 'Terms & Conditions',
        'content' => '<p>Terms and conditions content will be added soon.</p>',
        'meta_description' => 'Read our terms and conditions for safety net installation services by GCM Netting Solutions in Chennai.'
    ];
}

$page_title = $page_content['page_title'] . ' - GCM Netting Solutions';
$meta_description = $page_content['meta_description'] ?: 'Read our terms and conditions for safety net installation services by GCM Netting Solutions in Chennai.';
$meta_keywords = 'terms and conditions, service terms, GCM safety nets';

$current_page = 'terms';
include 'includes/modern-header.php';
?>

<div class="page-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header" style="background: linear-gradient(135deg, #0066CC 0%, #0052A3 100%); color: white; padding: 60px 0; text-align: center; margin-bottom: 50px; border-radius: 16px;">
            <h1 style="font-size: 48px; font-weight: 800; margin-bottom: 12px;">
                <i class="fas fa-file-contract"></i> Terms & Conditions
            </h1>
            <p style="font-size: 18px; opacity: 0.95;">Service Agreement for Safety Net Installations</p>
        </div>

        <!-- Content -->
        <div class="terms-content" style="max-width: 900px; margin: 0 auto; background: white; padding: 50px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
            <p style="font-size: 16px; line-height: 1.8; color: #64748B; margin-bottom: 30px;">
                <strong>Last Updated:</strong> <?php echo $page_content['updated_at'] ? date('F d, Y', strtotime($page_content['updated_at'])) : date('F d, Y'); ?>
            </p>
            
            <!-- Dynamic Content from Database -->
            <div style="font-size: 16px; line-height: 1.8; color: #475569;">
                <?php echo $page_content['content']; ?>
            </div>
            
            <!-- Static Content (shown if database content is minimal) -->
            <?php if (strlen($page_content['content']) < 100): ?>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-info-circle"></i> Agreement Overview
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    These Terms and Conditions ("Terms") govern your use of services provided by GCM Netting Solutions ("Company," "we," "our," or "us") for safety net installations in Chennai. By engaging our services, you ("Customer," "you," or "your") agree to be bound by these Terms.
                </p>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-tools"></i> Services Offered
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    GCM Netting Solutions provides professional installation services for:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Pigeon Nets:</strong> Anti-bird netting for balconies, windows, and open areas</li>
                    <li><strong>Bird Nets:</strong> Industrial and commercial bird control solutions</li>
                    <li><strong>Safety Nets:</strong> Balcony safety nets, construction site safety nets, staircase nets</li>
                    <li><strong>Sports Nets:</strong> Cricket nets, football nets, volleyball nets, and practice nets</li>
                    <li><strong>Invisible Grills:</strong> Stainless steel cable grills for windows and balconies</li>
                    <li><strong>Cloth Hangers:</strong> Ceiling-mounted and pulley-based cloth drying systems</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-clipboard-check"></i> Service Process
                </h2>
                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">1. Quotation & Booking</h3>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Free site inspection and measurement</li>
                    <li>Detailed quotation with material specifications</li>
                    <li>Booking confirmation upon advance payment</li>
                    <li>Scheduled installation date and time</li>
                </ul>

                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">2. Installation</h3>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Professional installation by trained technicians</li>
                    <li>Quality materials and equipment used</li>
                    <li>Installation completed within agreed timeframe</li>
                    <li>Site cleanup after installation</li>
                </ul>

                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">3. Completion</h3>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Final inspection and quality check</li>
                    <li>Customer acceptance and sign-off</li>
                    <li>Warranty documentation provided</li>
                    <li>Final payment collection</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-rupee-sign"></i> Pricing & Payment
                </h2>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Quotations:</strong> Valid for 15 days from date of issue</li>
                    <li><strong>Advance Payment:</strong> 30-50% advance required to confirm booking</li>
                    <li><strong>Balance Payment:</strong> Due upon installation completion</li>
                    <li><strong>Payment Methods:</strong> Cash, Bank Transfer, UPI, Card payments accepted</li>
                    <li><strong>Price Changes:</strong> Prices subject to change for material cost variations</li>
                    <li><strong>Additional Charges:</strong> Extra work or material changes billed separately</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-shield-alt"></i> Warranty & Guarantee
                </h2>
                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Product Warranty</h3>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Safety Nets & Pigeon Nets:</strong> 2-5 years warranty based on material quality</li>
                    <li><strong>Invisible Grills:</strong> 5-10 years warranty on stainless steel cables</li>
                    <li><strong>Cloth Hangers:</strong> 1-2 years warranty on pulley mechanisms</li>
                    <li><strong>Sports Nets:</strong> 1-3 years warranty based on usage intensity</li>
                </ul>

                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Warranty Coverage</h3>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Manufacturing defects in materials</li>
                    <li>Installation workmanship issues</li>
                    <li>Free repairs or replacement during warranty period</li>
                </ul>

                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Warranty Exclusions</h3>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Normal wear and tear</li>
                    <li>Damage from extreme weather conditions</li>
                    <li>Unauthorized modifications or repairs</li>
                    <li>Misuse or negligence by customer</li>
                    <li>Acts of God (earthquakes, floods, etc.)</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-undo"></i> Cancellation & Refund Policy
                </h2>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Before Installation:</strong> Cancellation allowed with 50% advance forfeiture</li>
                    <li><strong>During Installation:</strong> No cancellation allowed once work begins</li>
                    <li><strong>Rescheduling:</strong> Free rescheduling up to 24 hours before appointment</li>
                    <li><strong>Refunds:</strong> Processed within 7-10 business days</li>
                    <li><strong>Quality Issues:</strong> Free rectification or replacement within warranty</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-user-shield"></i> Customer Responsibilities
                </h2>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Provide accurate measurements and site information</li>
                    <li>Ensure site accessibility for installation team</li>
                    <li>Obtain necessary building permissions if required</li>
                    <li>Clear the installation area of obstacles</li>
                    <li>Ensure power and water availability if needed</li>
                    <li>Supervise children and pets during installation</li>
                    <li>Inspect and approve work upon completion</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-exclamation-triangle"></i> Liability & Limitations
                </h2>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Property Damage:</strong> We are not liable for pre-existing structural damage</li>
                    <li><strong>Third-Party Damage:</strong> Not responsible for damage by unauthorized persons</li>
                    <li><strong>Delay Claims:</strong> Not liable for delays due to weather or unforeseen circumstances</li>
                    <li><strong>Consequential Damages:</strong> Liability limited to service cost only</li>
                    <li><strong>Safety:</strong> Customers responsible for using products as intended</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-wrench"></i> Maintenance & Care
                </h2>
                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Regular Maintenance</h3>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Periodic inspection recommended every 6 months</li>
                    <li>Clean nets with water to remove dust and debris</li>
                    <li>Check for loose fittings and tighten if needed</li>
                    <li>Report any damage immediately for timely repair</li>
                </ul>

                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Do's and Don'ts</h3>
                <p style="font-size: 16px; line-height: 1.8; color: #475569; margin-bottom: 10px;"><strong>Do:</strong></p>
                <ul style="margin: 10px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Regular visual inspections</li>
                    <li>Clean with mild soap and water</li>
                    <li>Contact us for professional maintenance</li>
                </ul>
                <p style="font-size: 16px; line-height: 1.8; color: #475569; margin-bottom: 10px; margin-top: 20px;"><strong>Don't:</strong></p>
                <ul style="margin: 10px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Use harsh chemicals or bleach</li>
                    <li>Hang heavy objects on safety nets</li>
                    <li>Attempt DIY repairs</li>
                    <li>Allow children to play with installations</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-gavel"></i> Dispute Resolution
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    In case of disputes:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Contact our customer service team first</li>
                    <li>Attempt amicable resolution through discussion</li>
                    <li>Mediation through mutually agreed third party if needed</li>
                    <li>Jurisdiction: Courts of Chennai, Tamil Nadu</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-sync-alt"></i> Terms Modification
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    We reserve the right to modify these Terms at any time. Changes will be effective immediately upon posting on our website. Continued use of our services constitutes acceptance of modified Terms.
                </p>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-phone"></i> Contact Information
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    For questions about these Terms or our services:
                </p>
                <div style="background: #F8FAFC; padding: 30px; border-radius: 12px; margin-top: 20px;">
                    <p style="font-size: 16px; color: #1E293B; margin-bottom: 10px;"><strong><?php echo COMPANY_NAME; ?></strong></p>
                    <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-map-marker-alt" style="color: #0066CC; width: 20px;"></i> <?php echo COMPANY_ADDRESS; ?></p>
                    <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-phone" style="color: #0066CC; width: 20px;"></i> +91-<?php echo COMPANY_PHONE; ?></p>
                    <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-envelope" style="color: #0066CC; width: 20px;"></i> <?php echo COMPANY_EMAIL; ?></p>
                    <p style="font-size: 15px; color: #475569; margin-bottom: 0;"><i class="fas fa-clock" style="color: #0066CC; width: 20px;"></i> Mon - Sun: 8:00 AM - 8:00 PM</p>
                </div>
            </section>

            <div style="background: #FEF3C7; padding: 20px; border-radius: 12px; border-left: 4px solid #F59E0B; margin-top: 40px;">
                <p style="font-size: 14px; color: #78350F; margin: 0;">
                    <i class="fas fa-check-circle"></i> <strong>Service Areas:</strong> We provide professional safety net installation services across all 188+ areas in Chennai including Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, and all major localities.
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.page-content {
    padding: 60px 0;
    background: #F8FAFC;
    min-height: 100vh;
}
</style>

<?php include 'includes/modern-footer.php'; ?>
