/* ============================================
   GCM NETTING SOLUTIONS - VANILLA JAVASCRIPT
   Mobile Menu Toggle & Performance Optimizations
   No jQuery | Pure JavaScript
   ============================================ */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
  
  // ==========================================
  // 1. MOBILE MENU TOGGLE
  // ==========================================
  
  const menuToggle = document.querySelector('.menu-toggle');
  const mainNav = document.querySelector('.main-nav');
  
  if (menuToggle && mainNav) {
    menuToggle.addEventListener('click', function() {
      mainNav.classList.toggle('active');
      
      // Update ARIA attribute for accessibility
      const isExpanded = mainNav.classList.contains('active');
      menuToggle.setAttribute('aria-expanded', isExpanded);
    });
  }
  
  // ==========================================
  // 2. SMOOTH SCROLL FOR ANCHOR LINKS
  // ==========================================
  
  const smoothScrollLinks = document.querySelectorAll('a[href^="#"]');
  
  smoothScrollLinks.forEach(link => {
    link.addEventListener('click', function(e) {
      const targetId = this.getAttribute('href');
      
      // Only apply smooth scroll if it's a valid target (not just "#")
      if (targetId && targetId !== '#') {
        const targetElement = document.querySelector(targetId);
        
        if (targetElement) {
          e.preventDefault();
          targetElement.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
          
          // Close mobile menu if open
          if (mainNav && mainNav.classList.contains('active')) {
            mainNav.classList.remove('active');
          }
        }
      }
    });
  });
  
  // ==========================================
  // 3. LAZY LOADING IMAGE OPTIMIZATION
  // ==========================================
  
  // Add 'loaded' class to lazy images once they're loaded
  const lazyImages = document.querySelectorAll('img[loading="lazy"]');
  
  lazyImages.forEach(img => {
    if (img.complete) {
      img.classList.add('loaded');
    } else {
      img.addEventListener('load', function() {
        this.classList.add('loaded');
      });
    }
  });
  
  // ==========================================
  // 4. ACTIVE NAV LINK HIGHLIGHTING
  // ==========================================
  
  // Get current page URL
  const currentLocation = location.pathname.split('/').pop() || 'index.html';
  const navLinks = document.querySelectorAll('.nav-list li a');
  
  navLinks.forEach(link => {
    const linkPath = link.getAttribute('href');
    
    if (linkPath === currentLocation || 
        (currentLocation === '' && linkPath === 'index.html')) {
      link.classList.add('active');
    }
  });
  
  // ==========================================
  // 5. CLICK-TO-CALL TRACKING (Optional)
  // ==========================================
  
  const phoneLinks = document.querySelectorAll('a[href^="tel:"]');
  
  phoneLinks.forEach(link => {
    link.addEventListener('click', function() {
      // Optional: Add analytics tracking here
      console.log('Phone call initiated: ' + this.getAttribute('href'));
    });
  });
  
  // ==========================================
  // 6. WHATSAPP CLICK TRACKING (Optional)
  // ==========================================
  
  const whatsappLinks = document.querySelectorAll('a[href*="whatsapp"]');
  
  whatsappLinks.forEach(link => {
    link.addEventListener('click', function() {
      // Optional: Add analytics tracking here
      console.log('WhatsApp link clicked');
    });
  });
  
});

// ==========================================
// 7. PERFORMANCE: DEBOUNCE UTILITY
// ==========================================

function debounce(func, wait = 100) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}

// ==========================================
// 8. WINDOW RESIZE HANDLER (Debounced)
// ==========================================

const handleResize = debounce(function() {
  // Close mobile menu on window resize to desktop size
  const mainNav = document.querySelector('.main-nav');
  
  if (window.innerWidth >= 768 && mainNav) {
    mainNav.classList.remove('active');
  }
}, 250);

window.addEventListener('resize', handleResize);
