<?php
/**
 * GCM Netting Solutions - Thank You Page
 * Displayed after successful form submission
 */

define('GCM_INIT', true);
require_once 'config/config.php';

$page_title = 'Thank You - Message Received';
$meta_description = 'Thank you for contacting GCM Netting Solutions. We have received your message and will get back to you shortly.';
$meta_keywords = 'thank you, gcm safety nets, contact confirmation';

// Get the source of submission
$from = isset($_GET['from']) ? sanitize_input($_GET['from']) : 'contact';

$current_page = 'thank-you';

include 'includes/modern-header.php';
?>

<div class="thank-you-page">
    <div class="container">
        <div class="thank-you-content">
            <!-- Success Icon Animation -->
            <div class="success-animation">
                <div class="checkmark-circle">
                    <div class="checkmark-icon">
                        <i class="fas fa-check"></i>
                    </div>
                </div>
            </div>
            
            <!-- Thank You Message -->
            <div class="thank-you-message">
                <h1>Thank You!</h1>
                <p class="lead">Your message has been received successfully</p>
                <p class="description">
                    We appreciate you taking the time to contact us. Our team will review your inquiry 
                    and get back to you within <strong>1-2 hours</strong> during business hours.
                </p>
            </div>
            
            <!-- What Happens Next -->
            <div class="next-steps-section">
                <h2><i class="fas fa-route"></i> What Happens Next?</h2>
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-icon">
                            <i class="fas fa-envelope-open-text"></i>
                        </div>
                        <h3>We Review Your Request</h3>
                        <p>Our team carefully reviews your requirements and prepares a personalized response</p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-icon">
                            <i class="fas fa-phone-volume"></i>
                        </div>
                        <h3>We Contact You</h3>
                        <p>We'll call you to discuss your requirements and schedule a free site visit</p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3>Free Site Visit</h3>
                        <p>Our expert visits your location for accurate measurements and quotation</p>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <div class="step-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3>Professional Installation</h3>
                        <p>We complete the installation with quality materials and expert workmanship</p>
                    </div>
                </div>
            </div>
            
            <!-- Contact Options -->
            <div class="urgent-contact-section">
                <div class="urgent-box">
                    <div class="urgent-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="urgent-content">
                        <h3>Need Immediate Assistance?</h3>
                        <p>If you have an urgent requirement, feel free to contact us directly</p>
                        <div class="urgent-buttons">
                            <a href="tel:<?php echo COMPANY_PHONE; ?>" class="btn btn-primary">
                                <i class="fas fa-phone"></i> Call Now
                            </a>
                            <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>" class="btn btn-success" target="_blank">
                                <i class="fab fa-whatsapp"></i> WhatsApp Us
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Additional Resources -->
            <div class="resources-section">
                <h2><i class="fas fa-compass"></i> Explore More</h2>
                <div class="resources-grid">
                    <a href="<?php echo SITE_URL; ?>/estimation" class="resource-card">
                        <div class="resource-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <h4>Price Calculator</h4>
                        <p>Get instant price estimates for your project</p>
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/gallery" class="resource-card">
                        <div class="resource-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <h4>Our Gallery</h4>
                        <p>View our completed projects and installations</p>
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/reviews" class="resource-card">
                        <div class="resource-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <h4>Customer Reviews</h4>
                        <p>Read what our customers say about us</p>
                    </a>
                    
                    <a href="<?php echo SITE_URL; ?>/faqs" class="resource-card">
                        <div class="resource-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                        <h4>FAQ's</h4>
                        <p>Find answers to common questions</p>
                    </a>
                </div>
            </div>
            
            <!-- Back to Home -->
            <div class="back-home-section">
                <a href="<?php echo SITE_URL; ?>/" class="btn btn-secondary btn-large">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>
        </div>
    </div>
</div>

<style>
.thank-you-page {
    background: linear-gradient(135deg, #F8FAFC 0%, #E2E8F0 100%);
    padding: 80px 0;
    min-height: 100vh;
}

.thank-you-content {
    max-width: 1000px;
    margin: 0 auto;
}

/* Success Animation */
.success-animation {
    display: flex;
    justify-content: center;
    margin-bottom: 40px;
}

.checkmark-circle {
    width: 120px;
    height: 120px;
    background: linear-gradient(135deg, #00CC66 0%, #059669 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: scaleIn 0.5s ease;
    box-shadow: 0 10px 40px rgba(0, 204, 102, 0.3);
}

.checkmark-icon {
    font-size: 60px;
    color: #FFFFFF;
    animation: checkmark 0.6s ease 0.3s both;
}

@keyframes scaleIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

@keyframes checkmark {
    from {
        transform: scale(0) rotate(-45deg);
        opacity: 0;
    }
    to {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }
}

/* Thank You Message */
.thank-you-message {
    text-align: center;
    margin-bottom: 60px;
    animation: fadeInUp 0.6s ease 0.4s both;
}

.thank-you-message h1 {
    font-size: 48px;
    font-weight: 800;
    color: #1E293B;
    margin-bottom: 16px;
}

.thank-you-message .lead {
    font-size: 24px;
    color: #00CC66;
    font-weight: 600;
    margin-bottom: 20px;
}

.thank-you-message .description {
    font-size: 16px;
    color: #64748B;
    line-height: 1.8;
    max-width: 700px;
    margin: 0 auto;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Next Steps */
.next-steps-section {
    background: #FFFFFF;
    padding: 50px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 40px;
}

.next-steps-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1E293B;
    text-align: center;
    margin-bottom: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.next-steps-section h2 i {
    color: #0066CC;
}

.steps-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.step-card {
    background: #F8FAFC;
    padding: 32px 24px;
    border-radius: 12px;
    text-align: center;
    position: relative;
    transition: all 0.3s;
}

.step-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.step-number {
    position: absolute;
    top: -15px;
    left: 50%;
    transform: translateX(-50%);
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #0066CC 0%, #0052A3 100%);
    color: #FFFFFF;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 18px;
    box-shadow: 0 4px 12px rgba(0, 102, 204, 0.3);
}

.step-icon {
    font-size: 48px;
    color: #0066CC;
    margin: 20px 0;
}

.step-card h3 {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 12px;
}

.step-card p {
    font-size: 14px;
    color: #64748B;
    line-height: 1.6;
}

/* Urgent Contact */
.urgent-contact-section {
    margin-bottom: 40px;
}

.urgent-box {
    background: linear-gradient(135deg, #FFF7ED 0%, #FFEDD5 100%);
    border: 2px solid #FFA500;
    border-radius: 16px;
    padding: 40px;
    display: flex;
    align-items: center;
    gap: 32px;
}

.urgent-icon {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #FFA500 0%, #FF6600 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 40px;
    color: #FFFFFF;
    flex-shrink: 0;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
        box-shadow: 0 0 0 0 rgba(255, 165, 0, 0.7);
    }
    50% {
        transform: scale(1.05);
        box-shadow: 0 0 0 15px rgba(255, 165, 0, 0);
    }
}

.urgent-content h3 {
    font-size: 24px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 8px;
}

.urgent-content p {
    font-size: 16px;
    color: #64748B;
    margin-bottom: 20px;
}

.urgent-buttons {
    display: flex;
    gap: 16px;
}

/* Resources */
.resources-section {
    background: #FFFFFF;
    padding: 50px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 40px;
}

.resources-section h2 {
    font-size: 28px;
    font-weight: 700;
    color: #1E293B;
    text-align: center;
    margin-bottom: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.resources-section h2 i {
    color: #0066CC;
}

.resources-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 24px;
}

.resource-card {
    background: #F8FAFC;
    padding: 32px 24px;
    border-radius: 12px;
    text-align: center;
    text-decoration: none;
    transition: all 0.3s;
}

.resource-card:hover {
    background: #0066CC;
    transform: translateY(-8px);
    box-shadow: 0 8px 24px rgba(0, 102, 204, 0.3);
}

.resource-icon {
    font-size: 48px;
    color: #0066CC;
    margin-bottom: 16px;
    transition: all 0.3s;
}

.resource-card:hover .resource-icon {
    color: #FFFFFF;
}

.resource-card h4 {
    font-size: 18px;
    font-weight: 700;
    color: #1E293B;
    margin-bottom: 8px;
    transition: all 0.3s;
}

.resource-card:hover h4 {
    color: #FFFFFF;
}

.resource-card p {
    font-size: 14px;
    color: #64748B;
    transition: all 0.3s;
}

.resource-card:hover p {
    color: rgba(255, 255, 255, 0.9);
}

/* Back Home */
.back-home-section {
    text-align: center;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 14px 32px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    font-size: 16px;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, #0066CC 0%, #0052A3 100%);
    color: #FFFFFF;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(0, 102, 204, 0.3);
}

.btn-success {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    color: #FFFFFF;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 211, 102, 0.3);
}

.btn-secondary {
    background: #F1F5F9;
    color: #1E293B;
}

.btn-secondary:hover {
    background: #E2E8F0;
}

.btn-large {
    padding: 16px 40px;
    font-size: 18px;
}

/* Responsive */
@media (max-width: 1024px) {
    .steps-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .resources-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .thank-you-page {
        padding: 60px 0;
    }
    
    .thank-you-message h1 {
        font-size: 36px;
    }
    
    .thank-you-message .lead {
        font-size: 20px;
    }
    
    .next-steps-section {
        padding: 32px 20px;
    }
    
    .steps-grid {
        grid-template-columns: 1fr;
    }
    
    .urgent-box {
        flex-direction: column;
        text-align: center;
        padding: 32px 24px;
    }
    
    .urgent-buttons {
        flex-direction: column;
        width: 100%;
    }
    
    .urgent-buttons .btn {
        width: 100%;
        justify-content: center;
    }
    
    .resources-section {
        padding: 32px 20px;
    }
    
    .resources-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php include 'includes/modern-footer.php'; ?>
