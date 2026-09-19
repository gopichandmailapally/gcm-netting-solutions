<?php
/**
 * GCM Netting Solutions - Privacy Policy
 */
define('GCM_INIT', true);
require_once 'config/config.php';
require_once 'config/database.php';

// Fetch page content from database
$db = Database::getInstance();
$page_content = $db->fetchOne("SELECT * FROM page_content WHERE page_slug = 'privacy-policy'");

if (!$page_content) {
    // Default content if not in database
    $page_content = [
        'page_title' => 'Privacy Policy',
        'content' => '<p>Privacy policy content will be added soon.</p>',
        'meta_description' => 'Read our privacy policy to understand how GCM Netting Solutions collects, uses, and protects your personal information.'
    ];
}

$page_title = $page_content['page_title'] . ' - GCM Netting Solutions';
$meta_description = $page_content['meta_description'] ?: 'Read our privacy policy to understand how GCM Netting Solutions collects, uses, and protects your personal information.';
$meta_keywords = 'privacy policy, data protection, GCM safety nets';

$current_page = 'privacy';
include 'includes/modern-header.php';
?>

<div class="page-content">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header" style="background: linear-gradient(135deg, #0066CC 0%, #0052A3 100%); color: white; padding: 60px 0; text-align: center; margin-bottom: 50px; border-radius: 16px;">
            <h1 style="font-size: 48px; font-weight: 800; margin-bottom: 12px;">
                <i class="fas fa-shield-alt"></i> Privacy Policy
            </h1>
            <p style="font-size: 18px; opacity: 0.95;">How We Protect Your Personal Information</p>
        </div>

        <!-- Content -->
        <div class="privacy-content" style="max-width: 900px; margin: 0 auto; background: white; padding: 50px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08);">
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
                    <i class="fas fa-info-circle"></i> Introduction
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    GCM Netting Solutions ("we," "our," or "us") is committed to protecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information when you visit our website or use our safety net installation services in Chennai.
                </p>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-database"></i> Information We Collect
                </h2>
                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Personal Information</h3>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    When you contact us for safety net installation services, we may collect:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Name and contact details (phone number, email address)</li>
                    <li>Property address and location details</li>
                    <li>Service requirements (type of nets, area measurements)</li>
                    <li>Payment information for transactions</li>
                    <li>Communication preferences</li>
                </ul>

                <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Automatically Collected Information</h3>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    When you visit our website, we automatically collect:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>IP address and browser type</li>
                    <li>Device information and operating system</li>
                    <li>Pages visited and time spent on our site</li>
                    <li>Referring website addresses</li>
                    <li>Cookies and similar tracking technologies</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-cogs"></i> How We Use Your Information
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    We use the collected information for the following purposes:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Service Delivery:</strong> To provide safety net installation services, schedule appointments, and complete installations</li>
                    <li><strong>Communication:</strong> To respond to inquiries, send quotes, and provide customer support</li>
                    <li><strong>Payment Processing:</strong> To process payments and prevent fraudulent transactions</li>
                    <li><strong>Service Improvement:</strong> To analyze usage patterns and improve our website and services</li>
                    <li><strong>Marketing:</strong> To send promotional offers, service updates, and newsletters (with your consent)</li>
                    <li><strong>Legal Compliance:</strong> To comply with applicable laws and regulations</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-share-alt"></i> Information Sharing
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    We do not sell your personal information. We may share your information with:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Service Providers:</strong> Third-party contractors who assist in installation work</li>
                    <li><strong>Payment Processors:</strong> To facilitate secure payment transactions</li>
                    <li><strong>Legal Authorities:</strong> When required by law or to protect our legal rights</li>
                    <li><strong>Business Transfers:</strong> In case of merger, sale, or acquisition of our business</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-lock"></i> Data Security
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    We implement appropriate technical and organizational measures to protect your personal information, including:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li>Secure SSL encryption for data transmission</li>
                    <li>Regular security assessments and updates</li>
                    <li>Restricted access to personal information</li>
                    <li>Employee training on data protection</li>
                    <li>Secure storage of physical and digital records</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-user-check"></i> Your Rights
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    You have the following rights regarding your personal information:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Access:</strong> Request copies of your personal data</li>
                    <li><strong>Correction:</strong> Request correction of inaccurate information</li>
                    <li><strong>Deletion:</strong> Request deletion of your personal data</li>
                    <li><strong>Objection:</strong> Object to processing of your data</li>
                    <li><strong>Portability:</strong> Request transfer of your data to another service</li>
                    <li><strong>Withdraw Consent:</strong> Withdraw consent for marketing communications</li>
                </ul>
                <p style="font-size: 16px; line-height: 1.8; color: #475569; margin-top: 20px;">
                    To exercise these rights, contact us at <a href="mailto:<?php echo COMPANY_EMAIL; ?>" style="color: #0066CC; text-decoration: none; font-weight: 600;"><?php echo COMPANY_EMAIL; ?></a>
                </p>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-cookie-bite"></i> Cookies Policy
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    We use cookies to enhance your browsing experience. You can control cookie preferences through your browser settings. Types of cookies we use:
                </p>
                <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
                    <li><strong>Essential Cookies:</strong> Required for website functionality</li>
                    <li><strong>Analytics Cookies:</strong> Help us understand website usage</li>
                    <li><strong>Marketing Cookies:</strong> Used to deliver relevant advertisements</li>
                </ul>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-child"></i> Children's Privacy
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    Our services are not directed to children under 18. We do not knowingly collect personal information from children. If you believe we have collected information from a child, please contact us immediately.
                </p>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-sync-alt"></i> Policy Updates
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    We may update this Privacy Policy periodically. Changes will be posted on this page with an updated "Last Updated" date. Continued use of our services after changes constitutes acceptance of the updated policy.
                </p>
            </section>

            <section style="margin-bottom: 40px;">
                <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
                    <i class="fas fa-phone"></i> Contact Us
                </h2>
                <p style="font-size: 16px; line-height: 1.8; color: #475569;">
                    If you have questions about this Privacy Policy or our privacy practices, please contact us:
                </p>
                <div style="background: #F8FAFC; padding: 30px; border-radius: 12px; margin-top: 20px;">
                    <p style="font-size: 16px; color: #1E293B; margin-bottom: 10px;"><strong><?php echo COMPANY_NAME; ?></strong></p>
                    <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-map-marker-alt" style="color: #0066CC; width: 20px;"></i> <?php echo COMPANY_ADDRESS; ?></p>
                    <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-phone" style="color: #0066CC; width: 20px;"></i> +91 99123 99224</p>
                    <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-envelope" style="color: #0066CC; width: 20px;"></i> <?php echo COMPANY_EMAIL; ?></p>
                </div>
            </section>

            <div style="background: #FEF3C7; padding: 20px; border-radius: 12px; border-left: 4px solid #F59E0B; margin-top: 40px;">
                <p style="font-size: 14px; color: #78350F; margin: 0;">
                    <i class="fas fa-info-circle"></i> <strong>Note:</strong> This Privacy Policy applies to our safety net installation services including pigeon nets, bird nets, balcony safety nets, sports nets, invisible grills, and cloth hanger installations across all areas of Chennai.
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
