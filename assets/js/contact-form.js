/**
 * Contact Form JavaScript
 * Handles validation and AJAX submission
 */

(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        initContactForm();
    });
    
    /**
     * Initialize contact form
     */
    function initContactForm() {
        const form = document.getElementById('contactForm');
        if (!form) return;
        
        // Phone number formatting
        const phoneInput = form.querySelector('#phone');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
            });
        }
        
        // Form submission
        form.addEventListener('submit', handleFormSubmit);
    }
    
    /**
     * Handle form submission
     */
    function handleFormSubmit(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        
        // Check honeypot
        const honeypot = form.querySelector('input[name="website"]');
        if (honeypot && honeypot.value !== '') {
            // Spam detected - silently reject
            return false;
        }
        
        // Validate form
        if (!validateForm(form)) {
            return false;
        }
        
        // Show loading state
        submitBtn.disabled = true;
        submitBtn.classList.add('loading');
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        // Remove any existing messages
        const existingMessages = form.querySelectorAll('.form-message');
        existingMessages.forEach(msg => msg.remove());
        
        // Get form data
        const formData = new FormData(form);
        
        // Send AJAX request
        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                showMessage(form, 'success', data.message || 'Thank you! Your message has been sent successfully. We will contact you shortly.');
                
                // Reset form
                form.reset();
                
                // Track conversion
                if (typeof gtag !== 'undefined') {
                    gtag('event', 'form_submit', {
                        'event_category': 'Contact Form',
                        'event_label': 'Contact Page Form'
                    });
                }
                
                // Redirect to thank you page after 2 seconds
                setTimeout(() => {
                    window.location.href = 'thank-you.php?from=contact';
                }, 2000);
            } else {
                // Show error message
                showMessage(form, 'error', data.message || 'Something went wrong. Please try again or call us directly.');
            }
        })
        .catch(error => {
            console.error('Form Error:', error);
            showMessage(form, 'error', 'Network error. Please check your connection and try again.');
        })
        .finally(() => {
            // Reset button
            submitBtn.disabled = false;
            submitBtn.classList.remove('loading');
            submitBtn.innerHTML = originalText;
        });
    }
    
    /**
     * Validate form
     */
    function validateForm(form) {
        let isValid = true;
        
        // Name validation
        const name = form.querySelector('#name');
        if (!name.value.trim() || name.value.trim().length < 3) {
            showFieldError(name, 'Please enter your full name (minimum 3 characters)');
            isValid = false;
        } else {
            clearFieldError(name);
        }
        
        // Phone validation
        const phone = form.querySelector('#phone');
        const phoneRegex = /^[0-9]{10}$/;
        if (!phone.value.match(phoneRegex)) {
            showFieldError(phone, 'Please enter a valid 10-digit mobile number');
            isValid = false;
        } else {
            clearFieldError(phone);
        }
        
        // Email validation
        const email = form.querySelector('#email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!email.value.match(emailRegex)) {
            showFieldError(email, 'Please enter a valid email address');
            isValid = false;
        } else {
            clearFieldError(email);
        }
        
        // Service validation
        const service = form.querySelector('#service');
        if (!service.value) {
            showFieldError(service, 'Please select a service');
            isValid = false;
        } else {
            clearFieldError(service);
        }
        
        // Area validation
        const area = form.querySelector('#area');
        if (!area.value.trim() || area.value.trim().length < 3) {
            showFieldError(area, 'Please enter your location');
            isValid = false;
        } else {
            clearFieldError(area);
        }
        
        // Message validation
        const message = form.querySelector('#message');
        if (!message.value.trim() || message.value.trim().length < 10) {
            showFieldError(message, 'Please enter your message (minimum 10 characters)');
            isValid = false;
        } else {
            clearFieldError(message);
        }
        
        // Terms validation
        const terms = form.querySelector('#terms');
        if (!terms.checked) {
            showFieldError(terms, 'Please accept the terms to continue');
            isValid = false;
        } else {
            clearFieldError(terms);
        }
        
        return isValid;
    }
    
    /**
     * Show field error
     */
    function showFieldError(field, message) {
        clearFieldError(field);
        
        field.classList.add('error');
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.textContent = message;
        
        field.parentNode.appendChild(errorDiv);
    }
    
    /**
     * Clear field error
     */
    function clearFieldError(field) {
        field.classList.remove('error');
        
        const existingError = field.parentNode.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
    }
    
    /**
     * Show form message
     */
    function showMessage(form, type, message) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `form-message ${type}`;
        messageDiv.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        form.insertBefore(messageDiv, form.firstChild);
        
        // Scroll to message
        messageDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        
        // Auto remove success message after 5 seconds
        if (type === 'success') {
            setTimeout(() => {
                messageDiv.style.opacity = '0';
                setTimeout(() => messageDiv.remove(), 300);
            }, 5000);
        }
    }
    
})();

// Add error styling dynamically
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .form-control.error {
            border-color: #DC2626;
            background: #FEF2F2;
        }
        
        .field-error {
            display: block;
            color: #DC2626;
            font-size: 13px;
            margin-top: 6px;
            font-weight: 500;
        }
        
        .form-control.error:focus {
            border-color: #DC2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
    `;
    document.head.appendChild(style);
})();
