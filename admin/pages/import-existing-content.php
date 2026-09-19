<?php
/**
 * Import Existing Page Content to Database
 * Included by page-content-editor.php - do not run directly
 */

// This file is included by page-content-editor.php which already has session check
// $db variable is already available from parent file

// Privacy Policy Content (extracted from privacy-policy.php)
$privacy_content = '
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
        <i class="fas fa-user-shield"></i> Your Rights
    </h2>
    <p style="font-size: 16px; line-height: 1.8; color: #475569;">You have the right to:</p>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li>Access your personal information we hold</li>
        <li>Request correction of inaccurate data</li>
        <li>Request deletion of your personal information</li>
        <li>Object to processing of your personal data</li>
        <li>Withdraw consent at any time</li>
        <li>Lodge a complaint with supervisory authorities</li>
    </ul>
</section>

<section style="margin-bottom: 40px;">
    <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-phone-alt"></i> Contact Us
    </h2>
    <p style="font-size: 16px; line-height: 1.8; color: #475569;">
        If you have any questions about this Privacy Policy or our data practices, please contact us:
    </p>
    <div style="background: #F8FAFC; padding: 25px; border-radius: 12px; margin-top: 20px; border-left: 4px solid #0066CC;">
        <p style="font-size: 16px; color: #1E293B; margin-bottom: 10px;"><strong>GCM Netting Solutions</strong></p>
        <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-map-marker-alt" style="color: #0066CC; width: 20px;"></i> Chennai, Tamil Nadu, India</p>
        <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-phone" style="color: #0066CC; width: 20px;"></i> +91-9912399234</p>
        <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-envelope" style="color: #0066CC; width: 20px;"></i> info@gcmsafetynets.com</p>
    </div>
</section>
';

// Terms & Conditions Content
$terms_content = '
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
        <li><strong>Pigeon Safety Nets:</strong> Anti-bird netting for balconies, windows, and open areas</li>
        <li><strong>Balcony Safety Nets:</strong> Child safety nets for residential buildings</li>
        <li><strong>Sports Nets:</strong> Cricket nets, practice nets, and sports facility netting</li>
        <li><strong>Construction Safety Nets:</strong> Safety solutions for construction sites</li>
        <li><strong>Invisible Grills:</strong> Modern stainless steel safety grills</li>
        <li><strong>Cloth Drying Hangers:</strong> Ceiling-mounted cloth hangers</li>
    </ul>
</section>

<section style="margin-bottom: 40px;">
    <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-rupee-sign"></i> Pricing and Payment
    </h2>
    <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Pricing</h3>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li>All prices are quoted in Indian Rupees (₹)</li>
        <li>Pricing is based on square feet area and net specifications</li>
        <li>Free site inspection and measurement provided</li>
        <li>Custom quotes for special requirements</li>
        <li>Prices include material, installation, and 5-year warranty</li>
    </ul>

    <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Payment Terms</h3>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li>50% advance payment required for booking</li>
        <li>Balance 50% payable after installation completion</li>
        <li>Payment methods: Cash, UPI, Bank Transfer, Online Payment</li>
        <li>GST invoices provided for all transactions</li>
    </ul>
</section>

<section style="margin-bottom: 40px;">
    <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-calendar-check"></i> Service Delivery
    </h2>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li><strong>Scheduling:</strong> Installation scheduled within 2-3 working days of booking</li>
        <li><strong>Duration:</strong> Most installations completed within 2-4 hours</li>
        <li><strong>Site Requirements:</strong> Customer must provide access to installation area</li>
        <li><strong>Weather Dependent:</strong> Outdoor installations may be rescheduled due to heavy rain</li>
        <li><strong>Safety Compliance:</strong> All safety protocols followed during installation</li>
    </ul>
</section>

<section style="margin-bottom: 40px;">
    <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-shield-alt"></i> Warranty and Guarantee
    </h2>
    <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">5-Year Comprehensive Warranty</h3>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li>Premium HDPE nets with UV protection</li>
        <li>Warranty covers material defects and installation issues</li>
        <li>Free repairs within warranty period</li>
        <li>24/7 customer support for emergency issues</li>
    </ul>

    <h3 style="font-size: 20px; margin-bottom: 15px; color: #1E293B; font-weight: 600;">Warranty Exclusions</h3>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li>Damage caused by customer negligence</li>
        <li>Natural disasters or extreme weather conditions</li>
        <li>Unauthorized modifications or repairs</li>
        <li>Normal wear and tear beyond specified period</li>
    </ul>
</section>

<section style="margin-bottom: 40px;">
    <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-undo"></i> Cancellation and Refund Policy
    </h2>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li><strong>Before Installation:</strong> Full refund if cancelled 48 hours before scheduled date</li>
        <li><strong>24-48 Hours Before:</strong> 50% of advance amount refunded</li>
        <li><strong>Less than 24 Hours:</strong> No refund (material procurement initiated)</li>
        <li><strong>After Installation:</strong> No cancellation or refund</li>
        <li><strong>Rescheduling:</strong> Free rescheduling allowed once with 24-hour notice</li>
    </ul>
</section>

<section style="margin-bottom: 40px;">
    <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-gavel"></i> Liability Limitations
    </h2>
    <p style="font-size: 16px; line-height: 1.8; color: #475569;">
        GCM Netting Solutions shall not be liable for:
    </p>
    <ul style="margin: 20px 0; padding-left: 30px; color: #475569; line-height: 2;">
        <li>Damage to property not caused by our installation work</li>
        <li>Loss or theft of customer belongings during installation</li>
        <li>Delays due to unforeseen circumstances or force majeure</li>
        <li>Injuries resulting from improper use of installed nets</li>
        <li>Consequential or indirect damages</li>
    </ul>
</section>

<section style="margin-bottom: 40px;">
    <h2 style="color: #0066CC; font-size: 28px; margin-bottom: 20px; font-weight: 700;">
        <i class="fas fa-phone-alt"></i> Contact Information
    </h2>
    <p style="font-size: 16px; line-height: 1.8; color: #475569;">
        For questions about these Terms & Conditions or our services:
    </p>
    <div style="background: #F8FAFC; padding: 25px; border-radius: 12px; margin-top: 20px; border-left: 4px solid #0066CC;">
        <p style="font-size: 16px; color: #1E293B; margin-bottom: 10px;"><strong>GCM Netting Solutions</strong></p>
        <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-map-marker-alt" style="color: #0066CC; width: 20px;"></i> Chennai, Tamil Nadu, India</p>
        <p style="font-size: 15px; color: #475569; margin-bottom: 8px;"><i class="fas fa-phone" style="color: #0066CC; width: 20px;"></i> +91-9912399234</p>
        <p style="font-size: 15px; color: #475569; margin-bottom: 0;"><i class="fas fa-envelope" style="color: #0066CC; width: 20px;"></i> info@gcmsafetynets.com</p>
        <p style="font-size: 15px; color: #475569; margin-bottom: 0;"><i class="fas fa-clock" style="color: #0066CC; width: 20px;"></i> Mon - Sun: 8:00 AM - 8:00 PM</p>
    </div>
</section>
';

// Insert/Update Privacy Policy
$db->execute(
    "INSERT INTO page_content (page_slug, page_title, content, meta_description) 
     VALUES ('privacy-policy', 'Privacy Policy', ?, 'Read our privacy policy to understand how GCM Netting Solutions collects, uses, and protects your personal information.')
     ON DUPLICATE KEY UPDATE content = ?, page_title = 'Privacy Policy'",
    [$privacy_content, $privacy_content],
    'ss'
);

// Insert/Update Terms & Conditions
$db->execute(
    "INSERT INTO page_content (page_slug, page_title, content, meta_description) 
     VALUES ('terms-conditions', 'Terms & Conditions', ?, 'Read our terms and conditions for safety net installation services by GCM Netting Solutions in Chennai.')
     ON DUPLICATE KEY UPDATE content = ?, page_title = 'Terms & Conditions'",
    [$terms_content, $terms_content],
    'ss'
);

// Content imported successfully - parent script will redirect
