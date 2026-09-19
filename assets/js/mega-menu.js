/**
 * 4-Level Mega Menu JavaScript
 * Handles mobile menu, dropdown interactions, and smooth transitions
 */

(function() {
    'use strict';
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initMegaMenu();
        initMobileMenu();
        initStickyHeader();
    });
    
    /**
     * Initialize desktop mega menu
     */
    function initMegaMenu() {
        const megaMenuItems = document.querySelectorAll('.has-megamenu');
        
        megaMenuItems.forEach(item => {
            const trigger = item.querySelector('.dropdown-trigger');
            const panel = item.querySelector('.megamenu-panel');
            
            if (!trigger || !panel) return;
            
            // Desktop hover behavior
            if (window.innerWidth > 768) {
                item.addEventListener('mouseenter', function() {
                    closeAllMegaMenus();
                    panel.style.opacity = '1';
                    panel.style.visibility = 'visible';
                    panel.style.marginTop = '0';
                });
                
                item.addEventListener('mouseleave', function() {
                    panel.style.opacity = '0';
                    panel.style.visibility = 'hidden';
                    panel.style.marginTop = '10px';
                });
            }
        });
        
        // Initialize submenu interactions
        initSubmenuInteractions();
    }
    
    /**
     * Initialize submenu (Level 3 -> Level 4) interactions
     */
    function initSubmenuInteractions() {
        const keywordLinks = document.querySelectorAll('.has-submenu');
        
        keywordLinks.forEach(item => {
            const link = item.querySelector('.keyword-link');
            const submenu = item.querySelector('.area-submenu');
            
            if (!link || !submenu) return;
            
            if (window.innerWidth > 768) {
                // Desktop: Show on hover
                item.addEventListener('mouseenter', function() {
                    closeAllAreaSubmenus(item.closest('.megamenu-column'));
                    submenu.style.opacity = '1';
                    submenu.style.visibility = 'visible';
                    submenu.style.marginLeft = '5px';
                });
                
                item.addEventListener('mouseleave', function() {
                    submenu.style.opacity = '0';
                    submenu.style.visibility = 'hidden';
                    submenu.style.marginLeft = '10px';
                });
            } else {
                // Mobile: Click to toggle
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const isActive = item.classList.contains('active');
                    
                    // Close other submenus
                    document.querySelectorAll('.has-submenu.active').forEach(el => {
                        if (el !== item) {
                            el.classList.remove('active');
                        }
                    });
                    
                    // Toggle current submenu
                    item.classList.toggle('active');
                });
            }
        });
    }
    
    /**
     * Close all open mega menus
     */
    function closeAllMegaMenus() {
        document.querySelectorAll('.megamenu-panel').forEach(panel => {
            panel.style.opacity = '0';
            panel.style.visibility = 'hidden';
            panel.style.marginTop = '10px';
        });
    }
    
    /**
     * Close all area submenus in a column
     */
    function closeAllAreaSubmenus(column) {
        if (!column) return;
        
        column.querySelectorAll('.area-submenu').forEach(submenu => {
            submenu.style.opacity = '0';
            submenu.style.visibility = 'hidden';
            submenu.style.marginLeft = '10px';
        });
    }
    
    /**
     * Initialize mobile menu
     */
    function initMobileMenu() {
        const menuToggle = document.querySelector('.mobile-menu-toggle');
        const mainMenu = document.querySelector('.main-menu');
        const body = document.body;
        
        if (!menuToggle || !mainMenu) return;
        
        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'menu-overlay';
        document.body.appendChild(overlay);
        
        // Toggle menu on button click
        menuToggle.addEventListener('click', function() {
            const isActive = mainMenu.classList.contains('active');
            
            if (isActive) {
                closeMenu();
            } else {
                openMenu();
            }
        });
        
        // Close menu on overlay click
        overlay.addEventListener('click', function() {
            closeMenu();
        });
        
        // Mobile mega menu toggle
        const megaMenuTriggers = document.querySelectorAll('.has-megamenu > .dropdown-trigger');
        
        megaMenuTriggers.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                if (window.innerWidth <= 768) {
                    e.preventDefault();
                    
                    const parent = this.closest('.has-megamenu');
                    const isActive = parent.classList.contains('active');
                    
                    // Close other mega menus
                    document.querySelectorAll('.has-megamenu.active').forEach(el => {
                        if (el !== parent) {
                            el.classList.remove('active');
                        }
                    });
                    
                    // Toggle current mega menu
                    parent.classList.toggle('active');
                }
            });
        });
        
        function openMenu() {
            mainMenu.classList.add('active');
            menuToggle.classList.add('active');
            overlay.classList.add('active');
            body.style.overflow = 'hidden';
        }
        
        function closeMenu() {
            mainMenu.classList.remove('active');
            menuToggle.classList.remove('active');
            overlay.classList.remove('active');
            body.style.overflow = '';
            
            // Close all submenus
            document.querySelectorAll('.has-megamenu.active').forEach(el => {
                el.classList.remove('active');
            });
            document.querySelectorAll('.has-submenu.active').forEach(el => {
                el.classList.remove('active');
            });
        }
        
        // Close menu on window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768 && mainMenu.classList.contains('active')) {
                closeMenu();
            }
        });
    }
    
    /**
     * Initialize sticky header
     */
    function initStickyHeader() {
        const header = document.querySelector('.site-header');
        if (!header) return;
        
        let lastScroll = 0;
        
        window.addEventListener('scroll', function() {
            const currentScroll = window.pageYOffset;
            
            if (currentScroll > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
            
            lastScroll = currentScroll;
        });
    }
    
    /**
     * Close menus on ESC key
     */
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const mainMenu = document.querySelector('.main-menu');
            const overlay = document.querySelector('.menu-overlay');
            
            if (mainMenu && mainMenu.classList.contains('active')) {
                mainMenu.classList.remove('active');
                document.querySelector('.mobile-menu-toggle')?.classList.remove('active');
                overlay?.classList.remove('active');
                document.body.style.overflow = '';
            }
        }
    });
    
    /**
     * Smooth scroll for anchor links
     */
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const href = this.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    /**
     * Track active page in menu
     */
    function setActiveMenuItem() {
        const currentPath = window.location.pathname;
        const menuLinks = document.querySelectorAll('.main-menu a');
        
        menuLinks.forEach(link => {
            const linkPath = new URL(link.href).pathname;
            
            if (linkPath === currentPath) {
                link.classList.add('active');
            }
        });
    }
    
    setActiveMenuItem();
    
    /**
     * Accessibility improvements
     */
    function initAccessibility() {
        // Add ARIA labels
        document.querySelectorAll('.has-megamenu').forEach((item, index) => {
            const trigger = item.querySelector('.dropdown-trigger');
            const panel = item.querySelector('.megamenu-panel');
            
            if (trigger && panel) {
                trigger.setAttribute('aria-haspopup', 'true');
                trigger.setAttribute('aria-expanded', 'false');
                panel.setAttribute('role', 'menu');
                panel.setAttribute('id', `megamenu-${index}`);
                trigger.setAttribute('aria-controls', `megamenu-${index}`);
            }
        });
        
        // Keyboard navigation
        document.querySelectorAll('.has-megamenu > .dropdown-trigger').forEach(trigger => {
            trigger.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        });
    }
    
    initAccessibility();
    
})();

/**
 * Utility: Debounce function
 */
function debounce(func, wait) {
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

/**
 * Reinitialize menu on dynamic content load
 */
window.reinitMegaMenu = function() {
    initMegaMenu();
    initSubmenuInteractions();
};
