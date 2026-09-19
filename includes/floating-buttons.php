<!-- Floating Action Buttons -->
<div class="floating-buttons">
    <!-- WhatsApp Button -->
    <a href="https://wa.me/<?php echo COMPANY_WHATSAPP; ?>?text=Hi, I'm interested in your safety net services" 
       class="floating-btn whatsapp-btn" 
       target="_blank" 
       rel="noopener noreferrer"
       title="Chat on WhatsApp">
        <i class="fab fa-whatsapp"></i>
        <span class="floating-btn-text">WhatsApp</span>
    </a>
    
    <!-- Phone Button -->
    <a href="tel:<?php echo COMPANY_PHONE; ?>" 
       class="floating-btn phone-btn" 
       title="Call Us">
        <i class="fas fa-phone"></i>
        <span class="floating-btn-text">Call Us</span>
    </a>
    
    <!-- Scroll to Top Button -->
    <button class="floating-btn scroll-top-btn" 
            id="scrollTopBtn" 
            onclick="scrollToTop()" 
            title="Scroll to Top"
            style="display: none;">
        <i class="fas fa-arrow-up"></i>
    </button>
</div>

<style>
.floating-buttons {
    position: fixed;
    bottom: 24px;
    right: 24px;
    display: flex;
    flex-direction: column;
    gap: 12px;
    z-index: 9999;
}

.floating-btn {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #FFFFFF;
    text-decoration: none;
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.2);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    border: none;
    cursor: pointer;
    font-size: 24px;
}

.floating-btn:hover {
    transform: translateY(-4px) scale(1.05);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.3);
}

.floating-btn-text {
    position: absolute;
    right: 70px;
    background: rgba(0, 0, 0, 0.8);
    color: #FFFFFF;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 600;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    pointer-events: none;
}

.floating-btn-text::after {
    content: '';
    position: absolute;
    right: -6px;
    top: 50%;
    transform: translateY(-50%);
    width: 0;
    height: 0;
    border-left: 6px solid rgba(0, 0, 0, 0.8);
    border-top: 6px solid transparent;
    border-bottom: 6px solid transparent;
}

.floating-btn:hover .floating-btn-text {
    opacity: 1;
    visibility: visible;
    right: 75px;
}

.whatsapp-btn {
    background: linear-gradient(135deg, #25D366 0%, #128C7E 100%);
    animation: pulse-whatsapp 2s infinite;
}

.phone-btn {
    background: linear-gradient(135deg, #0066CC 0%, #0052A3 100%);
}

.scroll-top-btn {
    background: linear-gradient(135deg, #FF6600 0%, #E55A00 100%);
}

.scroll-top-btn.show {
    display: flex !important;
    animation: fadeInUp 0.3s ease;
}

@keyframes pulse-whatsapp {
    0%, 100% {
        box-shadow: 0 4px 16px rgba(37, 211, 102, 0.4);
    }
    50% {
        box-shadow: 0 4px 24px rgba(37, 211, 102, 0.6), 0 0 0 8px rgba(37, 211, 102, 0.2);
    }
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .floating-buttons {
        bottom: 16px;
        right: 16px;
        gap: 10px;
    }
    
    .floating-btn {
        width: 50px;
        height: 50px;
        font-size: 20px;
    }
    
    .floating-btn-text {
        display: none;
    }
}
</style>

<script>
// Scroll to top functionality
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide scroll to top button
window.addEventListener('scroll', function() {
    const scrollTopBtn = document.getElementById('scrollTopBtn');
    if (window.pageYOffset > 300) {
        scrollTopBtn.style.display = 'flex';
        scrollTopBtn.classList.add('show');
    } else {
        scrollTopBtn.style.display = 'none';
        scrollTopBtn.classList.remove('show');
    }
});

// Track clicks on floating buttons
document.querySelectorAll('.floating-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        const btnType = this.classList.contains('whatsapp-btn') ? 'WhatsApp' : 
                       this.classList.contains('phone-btn') ? 'Phone' : 'ScrollTop';
        
        // Track with Google Analytics if available
        if (typeof gtag !== 'undefined') {
            gtag('event', 'click', {
                'event_category': 'Floating Button',
                'event_label': btnType
            });
        }
    });
});
</script>
