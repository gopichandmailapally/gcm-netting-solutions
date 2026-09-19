        </main>
        
        <!-- Admin Footer -->
        <footer class="admin-footer">
            <div class="footer-content">
                <p>&copy; <?php echo date('Y'); ?> GCM Netting Solutions. All rights reserved.</p>
                <p>Version 1.0.0 | Last Updated: <?php echo date('M j, Y'); ?></p>
            </div>
        </footer>
    </div>
    
    <!-- Scripts -->
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/admin.js"></script>
    <script src="<?php echo SITE_URL; ?>/admin/assets/js/ai-content-protection.js"></script>
    
    <!-- Modern Admin Panel JavaScript -->
    <script>
    // User Menu Dropdown
    document.addEventListener('DOMContentLoaded', function() {
        const userMenuTrigger = document.getElementById('userMenuTrigger');
        const userMenu = document.getElementById('userMenu');
        
        if (userMenuTrigger && userMenu) {
            userMenuTrigger.addEventListener('click', function(e) {
                e.stopPropagation();
                userMenu.classList.toggle('show');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!userMenuTrigger.contains(e.target) && !userMenu.contains(e.target)) {
                    userMenu.classList.remove('show');
                }
            });
        }
        
        // Sidebar Toggle for Desktop
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.querySelector('.admin-sidebar');
        const adminMain = document.querySelector('.admin-main');
        
        if (sidebarToggle && sidebar) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('collapsed');
                if (adminMain) {
                    adminMain.classList.toggle('sidebar-collapsed');
                }
            });
        }
        
        // Mobile Sidebar Toggle
        const mobileSidebarToggle = document.getElementById('mobileSidebarToggle');
        
        if (mobileSidebarToggle && sidebar) {
            mobileSidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('show');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(e.target) && !mobileSidebarToggle.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                const href = this.getAttribute('href');
                if (href !== '#' && href !== '#sitemap') {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
        
        // Add animation to cards on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);
        
        // Observe all cards
        document.querySelectorAll('.card, .stat-card, .action-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            observer.observe(card);
        });
    });
    </script>
    
    <!-- Additional JS if needed -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?php echo $js; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- ═══ GCM Admin Modal & Toast System ═══ -->
    <style>
    /* ── Overlay ───────────────────────────────────────────────── */
    #gcm-overlay{display:none;position:fixed;inset:0;z-index:99999;background:rgba(15,23,42,.72);backdrop-filter:blur(5px);align-items:center;justify-content:center;animation:gcmFadeIn .18s ease;}
    #gcm-overlay.on{display:flex;}
    #gcm-box{background:#fff;border-radius:20px;padding:36px 40px 28px;max-width:460px;width:92%;box-shadow:0 28px 80px rgba(0,0,0,.28);transform:scale(.96);transition:transform .18s cubic-bezier(.34,1.56,.64,1);}
    #gcm-overlay.on #gcm-box{transform:scale(1);}
    #gcm-icon{font-size:42px;text-align:center;margin-bottom:14px;line-height:1;}
    #gcm-title{font-size:18px;font-weight:800;color:#1e293b;text-align:center;margin:0 0 8px;letter-spacing:-.2px;}
    #gcm-msg{font-size:14px;color:#64748b;text-align:center;line-height:1.65;margin:0 0 28px;}
    #gcm-btns{display:flex;gap:12px;justify-content:center;}
    .gcm-btn-cancel{padding:11px 30px;border:2px solid #e2e8f0;border-radius:10px;background:#f8fafc;color:#475569;font-weight:700;font-size:14px;cursor:pointer;transition:all .15s;font-family:inherit;}
    .gcm-btn-cancel:hover{background:#f1f5f9;border-color:#cbd5e1;color:#1e293b;}
    .gcm-btn-ok{padding:11px 30px;border:none;border-radius:10px;background:linear-gradient(135deg,#8b5cf6,#6d28d9);color:#fff;font-weight:700;font-size:14px;cursor:pointer;transition:all .15s;box-shadow:0 4px 14px rgba(109,40,217,.35);font-family:inherit;}
    .gcm-btn-ok:hover{opacity:.9;transform:translateY(-1px);box-shadow:0 6px 18px rgba(109,40,217,.4);}
    .gcm-btn-ok.gcm-danger{background:linear-gradient(135deg,#ef4444,#dc2626);box-shadow:0 4px 14px rgba(220,38,38,.35);}
    .gcm-btn-ok.gcm-success{background:linear-gradient(135deg,#10b981,#059669);box-shadow:0 4px 14px rgba(5,150,105,.35);}
    .gcm-btn-ok.gcm-info{background:linear-gradient(135deg,#3b82f6,#1d4ed8);box-shadow:0 4px 14px rgba(29,78,216,.35);}
    /* ── Toasts ─────────────────────────────────────────────────── */
    #gcm-toasts{position:fixed;top:22px;right:22px;z-index:99998;display:flex;flex-direction:column;gap:10px;pointer-events:none;}
    .gcm-toast{background:#fff;border-radius:14px;padding:14px 18px 14px 16px;box-shadow:0 8px 32px rgba(0,0,0,.13);max-width:400px;min-width:260px;display:flex;align-items:flex-start;gap:12px;pointer-events:all;animation:gcmSlideIn .3s ease;border-left:4px solid #8b5cf6;font-family:inherit;}
    .gcm-toast.gcm-ts-success{border-color:#10b981;}.gcm-toast.gcm-ts-error{border-color:#ef4444;}.gcm-toast.gcm-ts-warning{border-color:#f59e0b;}.gcm-toast.gcm-ts-info{border-color:#3b82f6;}
    .gcm-toast-ico{font-size:20px;flex-shrink:0;margin-top:1px;}
    .gcm-toast-body{flex:1;min-width:0;}
    .gcm-toast-ttl{font-weight:800;font-size:13px;color:#1e293b;margin-bottom:2px;}
    .gcm-toast-txt{font-size:12px;color:#64748b;line-height:1.55;word-break:break-word;}
    .gcm-toast-x{cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0;flex-shrink:0;background:none;border:none;}
    .gcm-toast-x:hover{color:#475569;}
    .gcm-toast.gcm-out{animation:gcmSlideOut .3s ease forwards;}
    @keyframes gcmFadeIn{from{opacity:0}to{opacity:1}}
    @keyframes gcmSlideIn{from{opacity:0;transform:translateX(30px)}to{opacity:1;transform:translateX(0)}}
    @keyframes gcmSlideOut{from{opacity:1;transform:translateX(0)}to{opacity:0;transform:translateX(30px)}}
    </style>

    <!-- Modal DOM -->
    <div id="gcm-overlay">
      <div id="gcm-box">
        <div id="gcm-icon"></div>
        <div id="gcm-title"></div>
        <div id="gcm-msg"></div>
        <div id="gcm-btns">
          <button class="gcm-btn-cancel" id="gcm-cancel">Cancel</button>
          <button class="gcm-btn-ok"    id="gcm-ok">OK</button>
        </div>
      </div>
    </div>
    <div id="gcm-toasts"></div>

    <script>
    (function(){
    'use strict';
    /* ── Toast ───────────────────────────────────────────────────── */
    var ICONS = {success:'✅',error:'❌',warning:'⚠️',info:'ℹ️',confirm:'❓'};
    var TITLES = {success:'Success',error:'Error',warning:'Warning',info:'Notice',confirm:'Confirm'};

    window.gcmToast = function(msg, type, ttl) {
        type = type || 'info';
        var box = document.createElement('div');
        box.className = 'gcm-toast gcm-ts-' + type;
        var rawMsg = msg.replace(/^[✅❌⚠️ℹ️\s]+/, '');
        box.innerHTML = '<span class="gcm-toast-ico">' + (ICONS[type]||'ℹ️') + '</span>'
            + '<div class="gcm-toast-body"><div class="gcm-toast-ttl">' + (ttl || TITLES[type]) + '</div>'
            + '<div class="gcm-toast-txt">' + rawMsg + '</div></div>'
            + '<button class="gcm-toast-x" onclick="gcmDismiss(this)">&times;</button>';
        document.getElementById('gcm-toasts').appendChild(box);
        setTimeout(function(){ gcmDismiss(box.querySelector('.gcm-toast-x')); },
            type === 'error' ? 8000 : 5000);
    };
    window.gcmDismiss = function(btn) {
        var t = btn.closest ? btn.closest('.gcm-toast') : btn.parentNode.parentNode;
        if (!t || t.classList.contains('gcm-out')) return;
        t.classList.add('gcm-out');
        setTimeout(function(){ if (t.parentNode) t.parentNode.removeChild(t); }, 320);
    };

    /* ── Override window.alert ────────────────────────────────── */
    window.alert = function(msg) {
        msg = String(msg || '');
        var type = 'info';
        if (/^✅|success|fixed|complete|saved|done|created|updated/i.test(msg)) type = 'success';
        else if (/^❌|error|fail|invalid|cannot|unable/i.test(msg)) type = 'error';
        else if (/^⚠|warning|caution|careful|deprecated/i.test(msg)) type = 'warning';
        gcmToast(msg, type);
    };

    /* ── Modal ───────────────────────────────────────────────────── */
    var _overlay  = document.getElementById('gcm-overlay');
    var _icon     = document.getElementById('gcm-icon');
    var _title    = document.getElementById('gcm-title');
    var _msg      = document.getElementById('gcm-msg');
    var _okBtn    = document.getElementById('gcm-ok');
    var _cancelBtn= document.getElementById('gcm-cancel');
    var _okFn     = null;
    var _cancelFn = null;

    function _closeModal() {
        _overlay.classList.remove('on');
        _okFn = _cancelFn = null;
    }
    _overlay.addEventListener('click', function(e){ if (e.target === _overlay) { if (_cancelFn) _cancelFn(); _closeModal(); } });
    _cancelBtn.addEventListener('click', function(){ if (_cancelFn) _cancelFn(); _closeModal(); });
    _okBtn.addEventListener('click', function(){ var fn = _okFn; _closeModal(); if (fn) fn(); });
    document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && _overlay.classList.contains('on')){ if (_cancelFn) _cancelFn(); _closeModal(); } });

    window.gcmConfirm = function(msg, okFn, cancelFn, opts) {
        opts = opts || {};
        var isDanger = opts.danger || /delete|remove|reset|clear|destroy|permanen|irreversib|cannot be undone/i.test(msg);
        var isSuccess = opts.success || /success|confirm|apply|proceed/i.test(msg);
        _icon.textContent   = isDanger ? '🗑️' : opts.icon || '❓';
        _title.textContent  = opts.title || (isDanger ? 'Are you sure?' : 'Confirm Action');
        _msg.innerHTML      = msg.replace(/^[✅❌⚠️ℹ️❓\s]+/, '');
        _okBtn.className    = 'gcm-btn-ok' + (isDanger ? ' gcm-danger' : isSuccess ? ' gcm-success' : '');
        _okBtn.textContent  = opts.okLabel || (isDanger ? 'Yes, proceed' : 'OK');
        _cancelBtn.style.display = opts.alertOnly ? 'none' : '';
        _cancelBtn.textContent = opts.cancelLabel || 'Cancel';
        _okFn = okFn || null;
        _cancelFn = cancelFn || null;
        _overlay.classList.add('on');
        setTimeout(function(){ _okBtn.focus(); }, 50);
    };

    window.gcmAlert = function(msg, type, title) {
        gcmToast(msg, type || 'info', title);
    };

    /* ── Override window.confirm with re-click magic ─────────── */
    var _lastTarget  = null;
    var _confirming  = false;
    document.addEventListener('mousedown', function(e) {
        var t = e.target;
        _lastTarget = (t && (t.tagName === 'BUTTON' || t.tagName === 'A' ||
            t.getAttribute('onclick') ||
            t.closest('button, a[href], [onclick], input[type=button], input[type=submit]')))
            ? (t.closest('button, a, [onclick], input') || t) : t;
    }, true);

    window.confirm = function(msg) {
        if (_confirming) { _confirming = false; return true; }
        var target = _lastTarget;
        var isDanger = /delete|remove|reset|clear|destroy|permanen|irreversib/i.test(msg);
        gcmConfirm(msg,
            function() {
                _confirming = true;
                if (target && target.click) {
                    setTimeout(function(){ target.click(); }, 30);
                }
            },
            null,
            { danger: isDanger }
        );
        return false;
    };

    })();
    </script>
</body>
</html>
