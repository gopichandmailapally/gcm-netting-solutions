/**
 * Universal Contact Form Handler
 * Handles ALL contact forms on the website with AJAX
 * Shows beautiful success popup modal
 */

(function() {
    'use strict';

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initContactForms);
    } else {
        initContactForms();
    }

    function initContactForms() {
        // Create success modal HTML (only once)
        createSuccessModal();

        // Find ALL contact forms on the page EXCEPT #contactForm
        // (#contactForm on contact.php has its own dedicated handler: contact-form.js)
        const allForms = document.querySelectorAll(
            'form.quick-contact-form, ' +
            'form.contact-form, ' +
            'form[action*="contact"], ' +
            'form[action*="process-contact"]'
        );
        const contactForms = Array.from(allForms).filter(f => f.id !== 'contactForm');

        // Attach AJAX handler to each form
        contactForms.forEach(form => {
            form.addEventListener('submit', handleFormSubmit);
        });

        console.log(`✅ Contact Form Handler: ${contactForms.length} form(s) initialized`);
    }

    /**
     * Handle form submission via AJAX
     */
    async function handleFormSubmit(e) {
        e.preventDefault();

        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalBtnText = submitBtn.innerHTML;

        // Disable submit button
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

        try {
            // Prepare form data
            const formData = new FormData(form);

            // Ensure we have the service field
            if (!formData.has('service')) {
                // Try to get from page title or meta
                const pageTitle = document.title;
                if (pageTitle.includes('Pigeon')) formData.set('service', 'pigeon-nets');
                else if (pageTitle.includes('Balcony')) formData.set('service', 'balcony-safety-nets');
                else if (pageTitle.includes('Children')) formData.set('service', 'children-safety-nets');
                else if (pageTitle.includes('Invisible')) formData.set('service', 'invisible-grills');
                else formData.set('service', 'general-inquiry');
            }

            // Ensure we have form_type
            if (!formData.has('form_type')) {
                formData.set('form_type', 'service_page');
            }

            // Add area if not present
            if (!formData.has('area')) {
                formData.set('area', 'Chennai');
            }

            // Submit to API
            const response = await fetch('/api/contact-handler.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Show success popup
                showSuccessModal(
                    formData.get('name'),
                    formData.get('phone'),
                    formData.get('email')
                );

                // Reset form
                form.reset();

                // Track conversion (Google Analytics, if available)
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submission', {
                        'event_category': 'Contact',
                        'event_label': formData.get('service') || 'contact'
                    });
                }
            } else {
                // Show error message
                alert('❌ ' + result.message);
            }
        } catch (error) {
            console.error('Form submission error:', error);
            alert('❌ An error occurred. Please try again or call us directly at 9912399224.');
        } finally {
            // Re-enable submit button
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        }
    }

    /**
     * Create success modal HTML
     */
    function createSuccessModal() {
        // Check if modal already exists
        if (document.getElementById('successModalOverlay')) {
            return;
        }

        const modalHTML = `
            <div class="success-modal-overlay" id="successModalOverlay">
                <div class="success-modal">
                    <!-- Close Button -->
                    <button class="success-modal-close" onclick="closeSuccessModal()">
                        <i class="fas fa-times"></i>
                    </button>

                    <!-- Success Icon with Confetti -->
                    <div class="success-icon-container">
                        <div class="confetti-bg">
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                            <div class="confetti"></div>
                        </div>
                        <div class="success-checkmark">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>

                    <!-- Modal Content -->
                    <div class="success-modal-content">
                        <h2>🎉 Message Sent Successfully!</h2>
                        <p class="success-message">
                            Thank you for contacting GCM Netting Solutions! We've received your inquiry and will get back to you shortly.
                        </p>

                        <div class="success-details" id="successDetails">
                            <p><i class="fas fa-user"></i> <strong>Name:</strong> <span id="successName">-</span></p>
                            <p><i class="fas fa-phone"></i> <strong>Phone:</strong> <span id="successPhone">-</span></p>
                            <p><i class="fas fa-envelope"></i> <strong>Email:</strong> <span id="successEmail">-</span></p>
                            <p><i class="fas fa-clock"></i> <strong>Response Time:</strong> Within 1-2 hours</p>
                        </div>

                        <div class="success-actions">
                            <a href="tel:+919912399224" class="success-btn success-btn-primary">
                                <i class="fas fa-phone-alt"></i> Call Now
                            </a>
                            <button onclick="closeSuccessModal()" class="success-btn success-btn-secondary">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        document.body.insertAdjacentHTML('beforeend', modalHTML);

        // Close on overlay click
        document.getElementById('successModalOverlay').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSuccessModal();
            }
        });

        // Close on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeSuccessModal();
            }
        });
    }

    /**
     * Show success modal with customer details
     */
    function showSuccessModal(name, phone, email) {
        const modal = document.getElementById('successModalOverlay');
        if (!modal) return;

        // Fill in customer details
        document.getElementById('successName').textContent = name || '-';
        document.getElementById('successPhone').textContent = phone || '-';
        document.getElementById('successEmail').textContent = email || '-';

        // Show modal
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';

        // Auto-close after 10 seconds
        setTimeout(() => {
            closeSuccessModal();
        }, 10000);
    }

    /**
     * Close success modal
     */
    window.closeSuccessModal = function() {
        const modal = document.getElementById('successModalOverlay');
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    };

})();
