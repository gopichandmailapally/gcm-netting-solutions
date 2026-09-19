/**
 * Real-Time Visitor Tracking System
 * Tracks visitor location, IP, page views, and activity
 */

(function() {
    'use strict';
    
    // Generate or retrieve session ID
    function getSessionId() {
        let sessionId = sessionStorage.getItem('visitor_session_id');
        if (!sessionId) {
            sessionId = 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            sessionStorage.setItem('visitor_session_id', sessionId);
        }
        return sessionId;
    }
    
    // Get browser information
    function getBrowserInfo() {
        const ua = navigator.userAgent;
        let browser = 'Unknown';
        let version = 'Unknown';
        let os = 'Unknown';
        let device = 'Desktop';
        
        // Detect browser
        if (ua.indexOf('Firefox') > -1) {
            browser = 'Firefox';
            version = ua.match(/Firefox\/([0-9.]+)/)?.[1] || 'Unknown';
        } else if (ua.indexOf('Chrome') > -1) {
            browser = 'Chrome';
            version = ua.match(/Chrome\/([0-9.]+)/)?.[1] || 'Unknown';
        } else if (ua.indexOf('Safari') > -1) {
            browser = 'Safari';
            version = ua.match(/Version\/([0-9.]+)/)?.[1] || 'Unknown';
        } else if (ua.indexOf('Edge') > -1) {
            browser = 'Edge';
            version = ua.match(/Edge\/([0-9.]+)/)?.[1] || 'Unknown';
        }
        
        // Detect OS
        if (ua.indexOf('Windows') > -1) os = 'Windows';
        else if (ua.indexOf('Mac') > -1) os = 'MacOS';
        else if (ua.indexOf('Linux') > -1) os = 'Linux';
        else if (ua.indexOf('Android') > -1) os = 'Android';
        else if (ua.indexOf('iOS') > -1 || ua.indexOf('iPhone') > -1 || ua.indexOf('iPad') > -1) os = 'iOS';
        
        // Detect device
        if (/Mobile|Android|iPhone|iPad|iPod/i.test(ua)) {
            device = 'Mobile';
        } else if (/Tablet|iPad/i.test(ua)) {
            device = 'Tablet';
        }
        
        return { browser, version, os, device };
    }
    
    // Track visitor data
    const VisitorTracker = {
        sessionId: getSessionId(),
        startTime: Date.now(),
        currentPage: window.location.pathname,
        scrollDepth: 0,
        heartbeatInterval: null,
        
        // Initialize tracking
        init: function() {
            this.trackVisit();
            this.setupEventListeners();
            this.startHeartbeat();
            this.trackPageView();
        },
        
        // Track initial visit
        trackVisit: function() {
            const browserInfo = getBrowserInfo();
            
            fetch('/api/track-visitor-direct.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    page_url: window.location.href,
                    page_title: document.title,
                    referrer: document.referrer,
                    browser: browserInfo.browser,
                    browser_version: browserInfo.version,
                    os: browserInfo.os,
                    device_type: browserInfo.device,
                    screen_width: window.screen.width,
                    screen_height: window.screen.height,
                    language: navigator.language
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Visitor tracked successfully');
                }
            })
            .catch(error => console.error('Tracking error:', error));
        },
        
        // Track page view
        trackPageView: function() {
            fetch('/api/track-page-view.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    page_url: window.location.href,
                    page_title: document.title,
                    referrer: document.referrer
                })
            });
        },
        
        // Send heartbeat to show visitor is still active
        startHeartbeat: function() {
            this.heartbeatInterval = setInterval(() => {
                const timeSpent = Math.floor((Date.now() - this.startTime) / 1000);
                
                fetch('/api/visitor-heartbeat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: this.sessionId,
                        page_url: window.location.href,
                        time_spent: timeSpent,
                        scroll_depth: this.scrollDepth
                    })
                });
            }, 10000); // Every 10 seconds
        },
        
        // Setup event listeners
        setupEventListeners: function() {
            // Track scroll depth
            let maxScroll = 0;
            window.addEventListener('scroll', () => {
                const scrollPercent = Math.round(
                    (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100
                );
                if (scrollPercent > maxScroll) {
                    maxScroll = scrollPercent;
                    this.scrollDepth = maxScroll;
                }
            });
            
            // Track clicks
            document.addEventListener('click', (e) => {
                const target = e.target.closest('a, button');
                if (target) {
                    this.trackAction('click', {
                        element: target.tagName,
                        text: target.textContent.substring(0, 100),
                        href: target.href || null
                    });
                }
            });
            
            // Track form submissions
            document.addEventListener('submit', (e) => {
                if (e.target.tagName === 'FORM') {
                    this.trackAction('form_submit', {
                        form_id: e.target.id || 'unknown',
                        form_action: e.target.action
                    });
                }
            });
            
            // Track page exit
            window.addEventListener('beforeunload', () => {
                const timeSpent = Math.floor((Date.now() - this.startTime) / 1000);
                
                navigator.sendBeacon('/api/visitor-exit.php', JSON.stringify({
                    session_id: this.sessionId,
                    time_spent: timeSpent,
                    scroll_depth: this.scrollDepth
                }));
            });
            
            // Track page visibility changes
            document.addEventListener('visibilitychange', () => {
                if (document.hidden) {
                    this.trackAction('tab_hidden', { page: window.location.href });
                } else {
                    this.trackAction('tab_visible', { page: window.location.href });
                }
            });
        },
        
        // Track custom actions
        trackAction: function(actionType, actionData) {
            fetch('/api/track-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: this.sessionId,
                    action_type: actionType,
                    action_data: JSON.stringify(actionData),
                    page_url: window.location.href
                })
            });
        }
    };
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => VisitorTracker.init());
    } else {
        VisitorTracker.init();
    }
    
    // Expose tracker for custom tracking
    window.VisitorTracker = VisitorTracker;
})();
