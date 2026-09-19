/**
 * Hero Slider JavaScript
 * Handles automatic sliding, manual controls, and touch gestures
 */

(function() {
    'use strict';
    
    let currentSlide = 0;
    let slideInterval;
    let touchStartX = 0;
    let touchEndX = 0;
    
    // Initialize slider when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initSlider();
        initTouchGestures();
        initAutoSlide();
    });
    
    /**
     * Initialize slider
     */
    function initSlider() {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        
        if (slides.length === 0) return;
        
        // Show first slide
        slides[0].classList.add('active');
        if (dots[0]) dots[0].classList.add('active');
        
        // Add keyboard navigation
        document.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowLeft') {
                prevSlide();
            } else if (e.key === 'ArrowRight') {
                nextSlide();
            }
        });
    }
    
    /**
     * Go to specific slide
     */
    window.goToSlide = function(index) {
        const slides = document.querySelectorAll('.slide');
        const dots = document.querySelectorAll('.dot');
        
        if (index < 0 || index >= slides.length) return;
        
        // Remove active class from all slides and dots
        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));
        
        // Add active class to target slide and dot
        slides[index].classList.add('active');
        if (dots[index]) dots[index].classList.add('active');
        
        currentSlide = index;
        
        // Reset auto-slide timer
        resetAutoSlide();
        
        // Track slide view
        trackSlideView(index);
    };
    
    /**
     * Go to next slide
     */
    window.nextSlide = function() {
        const slides = document.querySelectorAll('.slide');
        const nextIndex = (currentSlide + 1) % slides.length;
        goToSlide(nextIndex);
    };
    
    /**
     * Go to previous slide
     */
    window.prevSlide = function() {
        const slides = document.querySelectorAll('.slide');
        const prevIndex = (currentSlide - 1 + slides.length) % slides.length;
        goToSlide(prevIndex);
    };
    
    /**
     * Initialize auto-slide
     */
    function initAutoSlide() {
        // Auto-slide every 5 seconds
        slideInterval = setInterval(function() {
            nextSlide();
        }, 5000);
        
        // Pause on hover
        const sliderContainer = document.querySelector('.slider-container');
        if (sliderContainer) {
            sliderContainer.addEventListener('mouseenter', function() {
                clearInterval(slideInterval);
            });
            
            sliderContainer.addEventListener('mouseleave', function() {
                resetAutoSlide();
            });
        }
        
        // Pause when page is not visible
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                clearInterval(slideInterval);
            } else {
                resetAutoSlide();
            }
        });
    }
    
    /**
     * Reset auto-slide timer
     */
    function resetAutoSlide() {
        clearInterval(slideInterval);
        slideInterval = setInterval(function() {
            nextSlide();
        }, 5000);
    }
    
    /**
     * Initialize touch gestures for mobile
     */
    function initTouchGestures() {
        const sliderContainer = document.querySelector('.slider-container');
        if (!sliderContainer) return;
        
        sliderContainer.addEventListener('touchstart', function(e) {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        sliderContainer.addEventListener('touchend', function(e) {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
    }
    
    /**
     * Handle swipe gesture
     */
    function handleSwipe() {
        const swipeThreshold = 50; // Minimum swipe distance
        
        if (touchEndX < touchStartX - swipeThreshold) {
            // Swipe left (next slide)
            nextSlide();
        } else if (touchEndX > touchStartX + swipeThreshold) {
            // Swipe right (previous slide)
            prevSlide();
        }
    }
    
    /**
     * Track slide view (for analytics)
     */
    function trackSlideView(index) {
        const slides = document.querySelectorAll('.slide');
        if (!slides[index]) return;
        
        const slideTitle = slides[index].querySelector('.slide-title');
        const serviceName = slideTitle ? slideTitle.textContent : `Slide ${index + 1}`;
        
        // Track with Google Analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'view_slide', {
                'event_category': 'Hero Slider',
                'event_label': serviceName,
                'value': index
            });
        }
    }
    
    /**
     * Preload images for better performance
     */
    function preloadImages() {
        const slides = document.querySelectorAll('.slide-background');
        slides.forEach(slide => {
            const bgImage = slide.style.backgroundImage;
            if (bgImage) {
                const imageUrl = bgImage.match(/url\(['"]?(.+?)['"]?\)/);
                if (imageUrl && imageUrl[1]) {
                    const img = new Image();
                    img.src = imageUrl[1];
                }
            }
        });
    }
    
    // Preload images after initial load
    window.addEventListener('load', preloadImages);
    
    /**
     * Add smooth scroll behavior to action buttons
     */
    document.querySelectorAll('.slide-actions a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            e.preventDefault();
            const target = document.querySelector(href);
            
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
})();

/**
 * Quick Contact Form Handler
 */
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const quickForm = document.getElementById('quickContactForm');
        
        if (!quickForm) return;
        
        quickForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Disable button and show loading
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showNotification('Thank you! We will contact you soon.', 'success');
                    this.reset();
                    
                    // Track form submission
                    if (typeof gtag !== 'undefined') {
                        gtag('event', 'form_submit', {
                            'event_category': 'Contact Form',
                            'event_label': 'Quick Quote Form'
                        });
                    }
                } else {
                    showNotification(data.message || 'Something went wrong. Please try again.', 'error');
                }
            })
            .catch(error => {
                console.error('Form Error:', error);
                showNotification('Network error. Please try again later.', 'error');
            })
            .finally(() => {
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
        
        // Phone number validation
        const phoneInput = quickForm.querySelector('input[type="tel"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                this.value = this.value.replace(/[^0-9]/g, '').substring(0, 10);
            });
        }
    });
    
    /**
     * Show notification
     */
    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `form-notification form-notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
            <span>${message}</span>
        `;
        
        const form = document.getElementById('quickContactForm');
        form.insertBefore(notification, form.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
    
})();

// Add notification styles
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .form-notification {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
            animation: slideInDown 0.3s ease;
            transition: opacity 0.3s;
        }
        
        .form-notification-success {
            background: #D1FAE5;
            color: #065F46;
            border: 1px solid #6EE7B7;
        }
        
        .form-notification-error {
            background: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
        }
        
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    `;
    document.head.appendChild(style);
})();
