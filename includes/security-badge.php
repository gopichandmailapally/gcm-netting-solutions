<?php
/**
 * Security Badge - Shows security status on website
 * Displays active protection features
 */
?>
<div class="security-badge" id="securityBadge">
    <div class="security-badge-icon">
        <i class="fas fa-shield-alt"></i>
    </div>
</div>

<div class="security-badge-popup" id="securityPopup" style="display: none;">
    <div class="security-popup-header">
        <h3><i class="fas fa-shield-alt"></i> Security Protection Active</h3>
        <button class="security-popup-close" onclick="closeSecurityPopup()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    <div class="security-popup-body">
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>Content Copy Protection</span>
        </div>
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>SQL Injection Prevention</span>
        </div>
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>XSS Attack Prevention</span>
        </div>
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>CSRF Protection</span>
        </div>
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>Rate Limiting Active</span>
        </div>
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>Bot Detection Enabled</span>
        </div>
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>Secure Headers Set</span>
        </div>
        <div class="security-feature">
            <i class="fas fa-check-circle" style="color: #10b981;"></i>
            <span>Real-time Monitoring</span>
        </div>
    </div>
    <div class="security-popup-footer">
        <small>© <?php echo date('Y'); ?> GCM Netting Solutions - All Rights Reserved</small>
    </div>
</div>

<style>
.security-badge {
    position: fixed;
    bottom: 20px;
    left: 20px;
    background: linear-gradient(135deg, #10b981, #059669);
    color: white;
    padding: 12px;
    border-radius: 50%;
    width: 50px;
    height: 50px;
    box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 9998;
    transition: all 0.3s ease;
    animation: pulse 2s infinite;
}

.security-badge:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(16, 185, 129, 0.5);
}

@keyframes pulse {
    0%, 100% {
        box-shadow: 0 8px 24px rgba(16, 185, 129, 0.4);
    }
    50% {
        box-shadow: 0 8px 32px rgba(16, 185, 129, 0.6);
    }
}

.security-badge-icon {
    font-size: 24px;
    animation: shield-spin 3s linear infinite;
}

@keyframes shield-spin {
    0%, 90% {
        transform: rotate(0deg);
    }
    95% {
        transform: rotate(10deg);
    }
    100% {
        transform: rotate(0deg);
    }
}

.security-badge-content {
    display: flex;
    flex-direction: column;
}

.security-badge-title {
    font-weight: 700;
    font-size: 14px;
}

.security-badge-subtitle {
    font-size: 11px;
    opacity: 0.9;
}

.security-badge-popup {
    position: fixed;
    bottom: 90px;
    left: 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    width: 320px;
    z-index: 9999;
    animation: slideInUp 0.3s ease;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.security-popup-header {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    padding: 20px;
    border-radius: 16px 16px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.security-popup-header h3 {
    margin: 0;
    font-size: 16px;
    font-weight: 700;
}

.security-popup-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
}

.security-popup-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.security-popup-body {
    padding: 20px;
    max-height: 400px;
    overflow-y: auto;
}

.security-feature {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 14px;
    color: #1e293b;
}

.security-feature i {
    font-size: 18px;
}

.security-popup-footer {
    padding: 16px 20px;
    border-top: 1px solid #e5e7eb;
    text-align: center;
    color: #64748b;
}

@media (max-width: 768px) {
    .security-badge {
        bottom: 10px;
        left: 10px;
        width: 44px;
        height: 44px;
        padding: 10px;
    }
    
    .security-badge-popup {
        bottom: 65px;
        left: 10px;
        width: calc(100vw - 20px);
        max-width: 320px;
    }
}
</style>

<script>
document.getElementById('securityBadge').addEventListener('click', function() {
    var popup = document.getElementById('securityPopup');
    if (popup.style.display === 'none') {
        popup.style.display = 'block';
    } else {
        popup.style.display = 'none';
    }
});

function closeSecurityPopup() {
    document.getElementById('securityPopup').style.display = 'none';
}

// Close popup when clicking outside
document.addEventListener('click', function(e) {
    var badge = document.getElementById('securityBadge');
    var popup = document.getElementById('securityPopup');
    
    if (!badge.contains(e.target) && !popup.contains(e.target)) {
        popup.style.display = 'none';
    }
});
</script>
