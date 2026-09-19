/**
 * GCM Netting Solutions - Admin Panel JavaScript
 * Handles sidebar, dropdowns, and interactions
 */

(function() {
    'use strict';
    
    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        initSidebar();
        initUserMenu();
        initSubmenuToggles();
        initMobileSidebar();
        initFormValidation();
        initTooltips();
        setActiveMenuItem();
    });
    
    /**
     * Initialize sidebar collapse/expand
     */
    function initSidebar() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                
                // Save state to localStorage
                const isCollapsed = sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebar_collapsed', isCollapsed);
            });
            
            // Restore state from localStorage
            const savedState = localStorage.getItem('sidebar_collapsed');
            if (savedState === 'true') {
                sidebar.classList.add('collapsed');
            }
        }
    }
    
    /**
     * Initialize user menu dropdown
     */
    function initUserMenu() {
        const userMenuTrigger = document.getElementById('userMenuTrigger');
        const userMenu = document.getElementById('userMenu');
        
        if (userMenuTrigger && userMenu) {
            const dropdown = userMenuTrigger.closest('.dropdown');
            
            userMenuTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('active');
            });
            
            // Close on outside click
            document.addEventListener('click', function(e) {
                if (!dropdown.contains(e.target)) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Close on menu item clicks (but not forms)
            userMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    dropdown.classList.remove('active');
                });
            });
        }
    }
    
    /**
     * Initialize submenu toggles
     */
    function initSubmenuToggles() {
        const submenuTriggers = document.querySelectorAll('.submenu-trigger');
        
        submenuTriggers.forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                
                const parentLi = this.closest('li');
                const submenu = parentLi.querySelector('.submenu');
                
                if (submenu) {
                    // Toggle active state
                    parentLi.classList.toggle('active');
                    
                    // Close other submenus (optional)
                    const siblings = Array.from(parentLi.parentElement.children);
                    siblings.forEach(sibling => {
                        if (sibling !== parentLi && sibling.classList.contains('has-submenu')) {
                            sibling.classList.remove('active');
                        }
                    });
                    
                    // Save state to localStorage
                    const menuId = this.textContent.trim().replace(/\s+/g, '-').toLowerCase();
                    const isActive = parentLi.classList.contains('active');
                    localStorage.setItem(`submenu_${menuId}`, isActive);
                }
            });
            
            // Restore state from localStorage
            const menuId = trigger.textContent.trim().replace(/\s+/g, '-').toLowerCase();
            const savedState = localStorage.getItem(`submenu_${menuId}`);
            if (savedState === 'true') {
                trigger.closest('li').classList.add('active');
            }
        });
    }
    
    /**
     * Initialize mobile sidebar
     */
    function initMobileSidebar() {
        const mobileTrigger = document.getElementById('mobileSidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (mobileTrigger && sidebar) {
            // Create overlay
            const overlay = document.createElement('div');
            overlay.className = 'sidebar-overlay';
            document.body.appendChild(overlay);
            
            // Toggle sidebar
            mobileTrigger.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                document.body.style.overflow = sidebar.classList.contains('active') ? 'hidden' : '';
            });
            
            // Close on overlay click
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
            });
            
            // Close on window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        }
    }
    
    /**
     * Set active menu item based on current URL
     */
    function setActiveMenuItem() {
        const currentPath = window.location.pathname;
        const menuLinks = document.querySelectorAll('.sidebar-nav a');
        
        menuLinks.forEach(link => {
            const linkPath = new URL(link.href, window.location.origin).pathname;
            
            if (linkPath === currentPath) {
                link.closest('li').classList.add('active');
                
                // Expand parent submenu if exists
                const parentSubmenu = link.closest('.submenu');
                if (parentSubmenu) {
                    const parentLi = parentSubmenu.closest('li');
                    if (parentLi) {
                        parentLi.classList.add('active');
                    }
                }
            }
        });
    }
    
    /**
     * Initialize form validation
     */
    function initFormValidation() {
        const forms = document.querySelectorAll('form[data-validate]');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                let isValid = true;
                const requiredFields = form.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('error');
                        showFieldError(field, 'This field is required');
                    } else {
                        field.classList.remove('error');
                        hideFieldError(field);
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    showNotification('Please fill in all required fields', 'error');
                }
            });
        });
    }
    
    /**
     * Show field error
     */
    function showFieldError(field, message) {
        let errorEl = field.nextElementSibling;
        if (!errorEl || !errorEl.classList.contains('field-error')) {
            errorEl = document.createElement('span');
            errorEl.className = 'field-error';
            field.parentNode.insertBefore(errorEl, field.nextSibling);
        }
        errorEl.textContent = message;
    }
    
    /**
     * Hide field error
     */
    function hideFieldError(field) {
        const errorEl = field.nextElementSibling;
        if (errorEl && errorEl.classList.contains('field-error')) {
            errorEl.remove();
        }
    }
    
    /**
     * Show notification
     */
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
            <button class="notification-close">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        // Show animation
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', function() {
            closeNotification(notification);
        });
        
        // Auto close after 5 seconds
        setTimeout(() => closeNotification(notification), 5000);
    }
    
    /**
     * Get notification icon
     */
    function getNotificationIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-circle',
            'warning': 'exclamation-triangle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
    
    /**
     * Close notification
     */
    function closeNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => notification.remove(), 300);
    }
    
    /**
     * Initialize tooltips
     */
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        
        tooltipElements.forEach(el => {
            el.addEventListener('mouseenter', function() {
                const text = this.getAttribute('data-tooltip');
                const tooltip = document.createElement('div');
                tooltip.className = 'tooltip';
                tooltip.textContent = text;
                document.body.appendChild(tooltip);
                
                // Position tooltip
                const rect = this.getBoundingClientRect();
                tooltip.style.top = `${rect.top - tooltip.offsetHeight - 10}px`;
                tooltip.style.left = `${rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2)}px`;
                
                setTimeout(() => tooltip.classList.add('show'), 10);
                
                this._tooltip = tooltip;
            });
            
            el.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.classList.remove('show');
                    setTimeout(() => {
                        if (this._tooltip) {
                            this._tooltip.remove();
                            delete this._tooltip;
                        }
                    }, 200);
                }
            });
        });
    }
    
    /**
     * Confirm dialog
     */
    window.confirmAction = function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    };
    
    /**
     * Copy to clipboard
     */
    window.copyToClipboard = function(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showNotification('Copied to clipboard!', 'success');
            });
        } else {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showNotification('Copied to clipboard!', 'success');
        }
    };
    
    /**
     * Format number with commas
     */
    window.formatNumber = function(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    };
    
    /**
     * Debounce function
     */
    window.debounce = function(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    };
    
    /**
     * Auto-save form
     */
    window.initAutoSave = function(formId, saveCallback, interval = 30000) {
        const form = document.getElementById(formId);
        if (!form) return;
        
        let autoSaveInterval;
        let hasChanges = false;
        
        // Detect changes
        form.addEventListener('input', function() {
            hasChanges = true;
        });
        
        // Auto-save
        autoSaveInterval = setInterval(() => {
            if (hasChanges) {
                saveCallback(new FormData(form));
                hasChanges = false;
                showNotification('Draft saved', 'success');
            }
        }, interval);
        
        // Clear interval on page unload
        window.addEventListener('beforeunload', function() {
            clearInterval(autoSaveInterval);
        });
    };
    
    /**
     * AJAX helper
     */
    window.ajax = function(url, options = {}) {
        const defaults = {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const config = { ...defaults, ...options };
        
        return fetch(url, config)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .catch(error => {
                console.error('AJAX Error:', error);
                showNotification('An error occurred. Please try again.', 'error');
                throw error;
            });
    };
    
    /**
     * Initialize data tables (if using)
     */
    window.initDataTable = function(tableId, options = {}) {
        const table = document.getElementById(tableId);
        if (!table) return;
        
        // Add search functionality
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search...';
        searchInput.className = 'table-search';
        table.parentNode.insertBefore(searchInput, table);
        
        searchInput.addEventListener('input', debounce(function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }, 300));
    };
    
    /**
     * Export global utility object
     */
    window.AdminUtils = {
        showNotification,
        confirmAction,
        copyToClipboard,
        formatNumber,
        debounce,
        ajax
    };
    
})();

// Add CSS for notifications dynamically
(function() {
    const style = document.createElement('style');
    style.textContent = `
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #FFFFFF;
            padding: 16px 20px;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 300px;
            max-width: 500px;
            z-index: 9999;
            transform: translateX(120%);
            transition: transform 0.3s ease;
        }
        
        .notification.show {
            transform: translateX(0);
        }
        
        .notification i {
            font-size: 20px;
        }
        
        .notification-success {
            border-left: 4px solid #00CC66;
        }
        
        .notification-success i {
            color: #00CC66;
        }
        
        .notification-error {
            border-left: 4px solid #FF4444;
        }
        
        .notification-error i {
            color: #FF4444;
        }
        
        .notification-warning {
            border-left: 4px solid #FFA500;
        }
        
        .notification-warning i {
            color: #FFA500;
        }
        
        .notification-info {
            border-left: 4px solid #00BFFF;
        }
        
        .notification-info i {
            color: #00BFFF;
        }
        
        .notification-close {
            background: none;
            border: none;
            font-size: 24px;
            color: #64748B;
            cursor: pointer;
            margin-left: auto;
            padding: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .notification-close:hover {
            color: #1E293B;
        }
        
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        
        .sidebar-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        .tooltip {
            position: fixed;
            background: #1E293B;
            color: #FFFFFF;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            z-index: 10000;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
        }
        
        .tooltip.show {
            opacity: 1;
        }
        
        .field-error {
            display: block;
            color: #FF4444;
            font-size: 13px;
            margin-top: 4px;
        }
        
        .form-control.error {
            border-color: #FF4444;
        }
    `;
    document.head.appendChild(style);
})();
