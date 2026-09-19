<?php
/**
 * Email Helper Functions
 * Send notifications for various admin features
 */

if (!defined('GCM_INIT')) {
    die('Direct access not permitted');
}

// Email configuration
define('ADMIN_EMAIL', 'info@gcmsafetynets.com');
define('FROM_EMAIL', 'noreply@gcmsafetynets.com');
define('FROM_NAME', 'GCM Netting Solutions');

// Note: send_email() function is defined in config.php

/**
 * Get email template wrapper
 */
function get_email_template($content) {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #0066CC 0%, #0052A3 100%); color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 8px 8px; }
            .button { display: inline-block; padding: 12px 30px; background: #0066CC; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>GCM Netting Solutions</h1>
                <p>Chennai\'s Most Trusted Safety Net Installation Company</p>
            </div>
            <div class="content">
                ' . $content . '
            </div>
            <div class="footer">
                <p>© 2025 GCM Netting Solutions. All rights reserved.</p>
                <p>Phone: +91-9912399234 | Email: info@gcmsafetynets.com</p>
            </div>
        </div>
    </body>
    </html>
    ';
}

/**
 * Send contact form notification to admin
 */
function send_contact_form_notification($submission) {
    $subject = "New Contact Form Submission - " . $submission['name'];
    
    $content = "
        <h2>New Contact Form Submission</h2>
        <p><strong>Name:</strong> " . htmlspecialchars($submission['name']) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($submission['email']) . "</p>
        <p><strong>Phone:</strong> " . htmlspecialchars($submission['phone']) . "</p>
        <p><strong>Service:</strong> " . htmlspecialchars($submission['service']) . "</p>
        <p><strong>Message:</strong></p>
        <p>" . nl2br(htmlspecialchars($submission['message'])) . "</p>
        <p><strong>Submitted:</strong> " . date('F j, Y, g:i a') . "</p>
        <p><a href='". SITE_URL ."/admin/pages/contact-forms.php' class='button'>View in Admin Panel</a></p>
    ";
    
    $message = get_email_template($content);
    return send_email(ADMIN_EMAIL, $subject, $message);
}

/**
 * Send acknowledgment to user who submitted contact form
 */
function send_contact_form_acknowledgment($submission) {
    $subject = "Thank you for contacting GCM Netting Solutions";
    
    $content = "
        <h2>Thank You for Contacting Us!</h2>
        <p>Dear " . htmlspecialchars($submission['name']) . ",</p>
        <p>We have received your inquiry and one of our representatives will get back to you within 24 hours.</p>
        <p><strong>Your submission details:</strong></p>
        <p><strong>Service:</strong> " . htmlspecialchars($submission['service']) . "</p>
        <p><strong>Message:</strong> " . nl2br(htmlspecialchars($submission['message'])) . "</p>
        <br>
        <p>For urgent inquiries, please call us at <strong>+91-9912399234</strong></p>
        <p>Thank you for choosing GCM Netting Solutions!</p>
    ";
    
    $message = get_email_template($content);
    return send_email($submission['email'], $subject, $message);
}

/**
 * Send review notification to admin
 */
function send_review_notification_to_admin($review) {
    $subject = "New Review Submitted - " . $review['customer_name'];
    
    $stars = str_repeat('⭐', $review['rating']);
    
    $content = "
        <h2>New Review Pending Approval</h2>
        <p><strong>Customer:</strong> " . htmlspecialchars($review['customer_name']) . "</p>
        <p><strong>Rating:</strong> $stars (" . $review['rating'] . "/5)</p>
        <p><strong>Service:</strong> " . htmlspecialchars($review['service']) . "</p>
        <p><strong>Location:</strong> " . htmlspecialchars($review['location']) . "</p>
        <p><strong>Review:</strong></p>
        <p>" . nl2br(htmlspecialchars($review['review_text'])) . "</p>
        <p><a href='". SITE_URL ."/admin/pages/reviews-management.php' class='button'>Review & Approve</a></p>
    ";
    
    $message = get_email_template($content);
    return send_email(ADMIN_EMAIL, $subject, $message);
}

/**
 * Send review approval notification to user
 */
function send_review_approval_to_user($review) {
    if (empty($review['email'])) return false;
    
    $subject = "Your Review Has Been Approved - GCM Netting Solutions";
    
    $content = "
        <h2>Thank You for Your Review!</h2>
        <p>Dear " . htmlspecialchars($review['customer_name']) . ",</p>
        <p>Your review has been approved and is now live on our website. Thank you for taking the time to share your experience with GCM Netting Solutions!</p>
        <p>Your feedback helps us maintain our high standards and helps other customers make informed decisions.</p>
        <p><a href='". SITE_URL ."/reviews.php' class='button'>View Your Review</a></p>
    ";
    
    $message = get_email_template($content);
    return send_email($review['email'], $subject, $message);
}

/**
 * Send FAQ question notification to admin
 */
function send_faq_question_notification($question) {
    $subject = "New FAQ Question - " . substr($question['question'], 0, 50) . "...";
    
    $content = "
        <h2>New FAQ Question Submitted</h2>
        <p><strong>From:</strong> " . htmlspecialchars($question['user_name']) . " (" . htmlspecialchars($question['user_email']) . ")</p>
        <p><strong>Question:</strong></p>
        <p>" . nl2br(htmlspecialchars($question['question'])) . "</p>
        <p><a href='". SITE_URL ."/admin/pages/faq-management.php' class='button'>Answer Question</a></p>
    ";
    
    $message = get_email_template($content);
    return send_email(ADMIN_EMAIL, $subject, $message);
}

/**
 * Send FAQ answer notification to user
 */
function send_faq_answer_notification($question) {
    if (empty($question['user_email'])) return false;
    
    $subject = "Your Question Has Been Answered - GCM Netting Solutions";
    
    $content = "
        <h2>Your Question Has Been Answered!</h2>
        <p>Dear " . htmlspecialchars($question['user_name']) . ",</p>
        <p><strong>Your Question:</strong></p>
        <p>" . nl2br(htmlspecialchars($question['question'])) . "</p>
        <p><strong>Our Answer:</strong></p>
        <p>" . nl2br(htmlspecialchars($question['answer'])) . "</p>
        <p><a href='". SITE_URL ."/faqs.php' class='button'>View All FAQs</a></p>
    ";
    
    $message = get_email_template($content);
    return send_email($question['user_email'], $subject, $message);
}
