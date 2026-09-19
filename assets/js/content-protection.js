/**
 * Content Protection System
 * Prevents copying, right-click, inspect element, and content theft
 */

(function() {
    'use strict';
    
    // Disable right-click
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        showProtectionMessage('Right-click is disabled to protect content');
        return false;
    });
    
    // Disable text selection
    document.addEventListener('selectstart', function(e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });
    
    // Disable copy
    document.addEventListener('copy', function(e) {
        e.preventDefault();
        showProtectionMessage('Content copying is disabled');
        return false;
    });
    
    // Disable cut
    document.addEventListener('cut', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Disable paste (in case of reverse engineering)
    document.addEventListener('paste', function(e) {
        if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
            e.preventDefault();
            return false;
        }
    });
    
    // Disable keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Disable F12 (Developer Tools)
        if (e.keyCode === 123) {
            e.preventDefault();
            showProtectionMessage('Developer tools are disabled');
            return false;
        }
        
        // Disable Ctrl+Shift+I (Inspect Element)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 73) {
            e.preventDefault();
            showProtectionMessage('Inspect element is disabled');
            return false;
        }
        
        // Disable Ctrl+Shift+J (Console)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 74) {
            e.preventDefault();
            showProtectionMessage('Console is disabled');
            return false;
        }
        
        // Disable Ctrl+Shift+C (Inspect Element)
        if (e.ctrlKey && e.shiftKey && e.keyCode === 67) {
            e.preventDefault();
            showProtectionMessage('Inspect element is disabled');
            return false;
        }
        
        // Disable Ctrl+U (View Source)
        if (e.ctrlKey && e.keyCode === 85) {
            e.preventDefault();
            showProtectionMessage('View source is disabled');
            return false;
        }
        
        // Disable Ctrl+S (Save Page)
        if (e.ctrlKey && e.keyCode === 83) {
            e.preventDefault();
            showProtectionMessage('Saving page is disabled');
            return false;
        }
        
        // Disable Ctrl+C (Copy)
        if (e.ctrlKey && e.keyCode === 67) {
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                showProtectionMessage('Content copying is disabled');
                return false;
            }
        }
        
        // Disable Ctrl+A (Select All)
        if (e.ctrlKey && e.keyCode === 65) {
            if (e.target.tagName !== 'INPUT' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
                return false;
            }
        }
        
        // Disable Ctrl+P (Print)
        if (e.ctrlKey && e.keyCode === 80) {
            e.preventDefault();
            showProtectionMessage('Printing is disabled');
            return false;
        }
    });
    
    // Detect DevTools
    var devtools = {
        isOpen: false,
        orientation: null
    };
    
    var threshold = 160;
    var emitEvent = function(isOpen, orientation) {
        if (devtools.isOpen !== isOpen || devtools.orientation !== orientation) {
            devtools.isOpen = isOpen;
            devtools.orientation = orientation;
            
            if (isOpen) {
                // Redirect or show warning
                document.body.innerHTML = '<div style="display: flex; align-items: center; justify-content: center; height: 100vh; font-family: Arial; text-align: center; background: #f8d7da; color: #721c24;"><div><h1>⚠️ Access Denied</h1><p>Developer tools detected. Please close developer tools to continue.</p></div></div>';
            }
        }
    };
    
    setInterval(function() {
        var widthThreshold = window.outerWidth - window.innerWidth > threshold;
        var heightThreshold = window.outerHeight - window.innerHeight > threshold;
        var orientation = widthThreshold ? 'vertical' : 'horizontal';
        
        if (!(heightThreshold && widthThreshold) && ((window.Firebug && window.Firebug.chrome && window.Firebug.chrome.isInitialized) || widthThreshold || heightThreshold)) {
            emitEvent(true, orientation);
        } else {
            emitEvent(false, null);
        }
    }, 500);
    
    // Disable drag and drop
    document.addEventListener('dragstart', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Disable image dragging
    var images = document.getElementsByTagName('img');
    for (var i = 0; i < images.length; i++) {
        images[i].addEventListener('dragstart', function(e) {
            e.preventDefault();
            return false;
        });
        
        // Disable image context menu
        images[i].addEventListener('contextmenu', function(e) {
            e.preventDefault();
            return false;
        });
    }
    
    // Add watermark to images (optional)
    function addWatermark() {
        var images = document.querySelectorAll('img');
        images.forEach(function(img) {
            if (!img.classList.contains('watermarked')) {
                img.style.position = 'relative';
                img.classList.add('watermarked');
            }
        });
    }
    
    // Apply CSS to prevent selection
    var style = document.createElement('style');
    style.innerHTML = `
        * {
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        
        input, textarea {
            -webkit-user-select: text !important;
            -moz-user-select: text !important;
            -ms-user-select: text !important;
            user-select: text !important;
        }
        
        img {
            pointer-events: none;
            -webkit-user-drag: none;
            -khtml-user-drag: none;
            -moz-user-drag: none;
            -o-user-drag: none;
            user-drag: none;
        }
        
        .protection-message {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 16px 24px;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(239, 68, 68, 0.4);
            z-index: 999999;
            font-family: Arial, sans-serif;
            font-size: 14px;
            font-weight: 600;
            animation: slideInRight 0.3s ease;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(400px);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
    
    // Show protection message
    function showProtectionMessage(message) {
        var existing = document.querySelector('.protection-message');
        if (existing) {
            existing.remove();
        }
        
        var messageDiv = document.createElement('div');
        messageDiv.className = 'protection-message';
        messageDiv.innerHTML = '<i class="fas fa-shield-alt"></i> ' + message;
        document.body.appendChild(messageDiv);
        
        setTimeout(function() {
            messageDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(function() {
                messageDiv.remove();
            }, 300);
        }, 3000);
    }
    
    // Detect screenshot attempts (limited detection)
    document.addEventListener('keyup', function(e) {
        // Print Screen key
        if (e.keyCode === 44) {
            showProtectionMessage('Screenshot detected - content is protected');
        }
    });
    
    // Blur content when window loses focus (prevents screenshot tools)
    var blurTimeout;
    window.addEventListener('blur', function() {
        blurTimeout = setTimeout(function() {
            document.body.style.filter = 'blur(10px)';
        }, 100);
    });
    
    window.addEventListener('focus', function() {
        clearTimeout(blurTimeout);
        document.body.style.filter = 'none';
    });
    
    // Prevent iframe embedding
    if (window.top !== window.self) {
        window.top.location = window.self.location;
    }
    
    // Console warning
    console.log('%c⚠️ WARNING', 'color: red; font-size: 40px; font-weight: bold;');
    console.log('%cThis is a browser feature intended for developers.', 'font-size: 16px;');
    console.log('%cIf someone told you to copy-paste something here, it is a scam.', 'font-size: 16px;');
    console.log('%cPasting anything here can give attackers access to your account.', 'font-size: 16px; color: red;');
    console.log('%c© GCM Netting Solutions - All content is protected', 'font-size: 14px; color: #667eea;');
    
    // Initialize
    addWatermark();
    
    // Re-apply protection on dynamic content
    var observer = new MutationObserver(function(mutations) {
        addWatermark();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
})();
