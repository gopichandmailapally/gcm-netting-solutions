/**
 * GCM AI Content Protection — Client-side Guard
 *
 * Usage: include this script on any admin page that has delete buttons.
 * Mark any delete button/link with:
 *   data-ai-delete="1"
 *   data-content-type="page|blog|review|faq"
 *   data-content-id="<id>"
 *   data-content-title="<title>"   (optional)
 *
 * The script intercepts the click, checks protection status via API,
 * and shows the security modal if the content is protected.
 * On approval the original click handler fires (or a custom callback).
 *
 * Custom callback usage:
 *   <button data-ai-delete="1" data-content-type="blog" data-content-id="12">Delete</button>
 *   When approved, the button receives a 'ai-delete-approved' event with {contentType, contentId}.
 */

(function () {
    'use strict';

    var API_URL = '/admin/api/ai-content-protection-api.php';

    /* ── Detect path depth (pages/ sub-folder) ──────── */
    var pathParts = window.location.pathname.split('/');
    if (pathParts.includes('pages') || pathParts.includes('billing')) {
        API_URL = '../api/ai-content-protection-api.php';
    }

    /* ── Inject modal HTML once ─────────────────────── */
    function injectModal() {
        if (document.getElementById('aiProtectModal')) return;

        var html = [
            '<div id="aiProtectModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:99999;align-items:center;justify-content:center;">',
            '  <div style="background:#fff;border-radius:18px;padding:32px;width:460px;max-width:95%;box-shadow:0 24px 80px rgba(0,0,0,.25);animation:slideDownModal .3s ease;">',
            '    <div style="display:flex;align-items:center;gap:14px;margin-bottom:6px;">',
            '      <div style="width:44px;height:44px;border-radius:12px;background:rgba(255,102,0,.12);display:flex;align-items:center;justify-content:center;font-size:20px;color:#FF6600;">',
            '        <i class="fas fa-shield-alt"></i>',
            '      </div>',
            '      <div>',
            '        <h3 style="margin:0;font-size:16px;font-weight:700;color:#1E293B;">AI Content Protected</h3>',
            '        <p style="margin:0;font-size:12px;color:#94A3B8;">This content requires security verification to delete</p>',
            '      </div>',
            '    </div>',
            '    <div id="aiProtectInfo" style="background:#FFF7ED;border:1px solid #FED7AA;border-radius:10px;padding:12px 16px;margin:16px 0;font-size:13px;color:#92400E;display:none;"></div>',
            '    <div id="aiProtectError" style="background:#FEF2F2;border:1px solid #FCA5A5;border-radius:10px;padding:12px 16px;margin:10px 0;font-size:13px;color:#7F1D1D;display:none;"></div>',
            '    <div style="margin-top:14px;">',
            '      <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#374151;margin-bottom:6px;">Reason for Deletion <span style="color:#EF4444;">*</span></label>',
            '      <input id="aiProtectReason" type="text" placeholder="Why are you deleting this AI content?" style="width:100%;padding:10px 14px;border:1.5px solid #E2E8F0;border-radius:8px;font-size:13px;color:#1E293B;box-sizing:border-box;">',
            '    </div>',
            '    <div style="margin-top:12px;">',
            '      <label style="display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:#374151;margin-bottom:6px;">Security PIN <span style="color:#EF4444;">*</span></label>',
            '      <input id="aiProtectPin" type="password" placeholder="Enter your security PIN" style="width:100%;padding:10px 14px;border:1.5px solid #E2E8F0;border-radius:8px;font-size:13px;color:#1E293B;box-sizing:border-box;">',
            '    </div>',
            '    <div id="aiProtectCooldown" style="display:none;background:#F0FDF4;border:1px solid #BBF7D0;border-radius:10px;padding:12px 16px;margin-top:12px;font-size:13px;color:#166534;"></div>',
            '    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:20px;">',
            '      <button id="aiProtectCancel" style="background:#F1F5F9;color:#475569;border:none;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;">Cancel</button>',
            '      <button id="aiProtectConfirm" style="background:linear-gradient(135deg,#EF4444,#DC2626);color:#fff;border:none;padding:10px 22px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;"><i class="fas fa-trash-alt"></i> Confirm Delete</button>',
            '    </div>',
            '  </div>',
            '</div>',
            '<style>@keyframes slideDownModal{from{opacity:0;transform:translateY(-20px)}to{opacity:1;transform:translateY(0)}}</style>'
        ].join('');

        var div = document.createElement('div');
        div.innerHTML = html;
        document.body.appendChild(div.firstChild);
        document.body.appendChild(div.lastChild);

        document.getElementById('aiProtectCancel').addEventListener('click', closeModal);
        document.getElementById('aiProtectModal').addEventListener('click', function (e) {
            if (e.target === this) closeModal();
        });
    }

    var _pendingBtn = null;

    function openModal(btn, info) {
        _pendingBtn = btn;
        injectModal();

        var modal     = document.getElementById('aiProtectModal');
        var infoBox   = document.getElementById('aiProtectInfo');
        var errorBox  = document.getElementById('aiProtectError');
        var coolBox   = document.getElementById('aiProtectCooldown');

        document.getElementById('aiProtectReason').value = '';
        document.getElementById('aiProtectPin').value    = '';
        errorBox.style.display  = 'none';
        coolBox.style.display   = 'none';

        if (info) {
            infoBox.innerHTML    = '<i class="fas fa-robot" style="margin-right:6px;"></i><strong>Protected AI Content:</strong> ' + escHtml(btn.dataset.contentTitle || btn.dataset.contentId || 'this item');
            infoBox.style.display = 'block';
        }

        modal.style.display = 'flex';
        setTimeout(function () { document.getElementById('aiProtectReason').focus(); }, 100);
    }

    function closeModal() {
        var modal = document.getElementById('aiProtectModal');
        if (modal) modal.style.display = 'none';
        _pendingBtn = null;
    }

    function showError(msg) {
        var box = document.getElementById('aiProtectError');
        box.innerHTML     = '<i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>' + escHtml(msg);
        box.style.display = 'block';
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Confirm button logic ───────────────────────── */
    document.addEventListener('click', function (e) {
        if (e.target && e.target.id === 'aiProtectConfirm') {
            e.preventDefault();
            var btn    = _pendingBtn;
            if (!btn) return;

            var reason = document.getElementById('aiProtectReason').value.trim();
            var pin    = document.getElementById('aiProtectPin').value.trim();
            var type   = btn.dataset.contentType;
            var id     = btn.dataset.contentId;
            var title  = btn.dataset.contentTitle || id;
            var errBox = document.getElementById('aiProtectError');

            errBox.style.display = 'none';

            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=request_delete&content_type=' + encodeURIComponent(type)
                    + '&content_id='    + encodeURIComponent(id)
                    + '&content_title=' + encodeURIComponent(title)
                    + '&pin='           + encodeURIComponent(pin)
                    + '&reason='        + encodeURIComponent(reason)
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.allowed) {
                    closeModal();
                    /* Fire custom event so the page's own delete handler takes over */
                    var evt = new CustomEvent('ai-delete-approved', {
                        bubbles: true,
                        detail: { contentType: type, contentId: id, contentTitle: title }
                    });
                    btn.dispatchEvent(evt);
                    /* Also execute the button's original onclick if any */
                    if (btn._originalOnClick) {
                        btn._originalOnClick.call(btn, new MouseEvent('click'));
                    }
                } else {
                    showError(data.error || 'Deletion not allowed.');
                }
            })
            .catch(function () {
                showError('Network error. Please try again.');
            });
        }
    });

    /* ── Intercept data-ai-delete buttons ───────────── */
    function attachGuard(btn) {
        if (btn._aiGuardAttached) return;
        btn._aiGuardAttached = true;

        /* Save original onclick */
        if (btn.onclick) {
            btn._originalOnClick = btn.onclick;
            btn.onclick = null;
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();

            var type = btn.dataset.contentType;
            var id   = btn.dataset.contentId;

            if (!type || !id) {
                /* No protection data — allow immediately */
                if (btn._originalOnClick) btn._originalOnClick.call(btn, e);
                return;
            }

            /* Check protection status first */
            fetch(API_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=check_protected&content_type=' + encodeURIComponent(type)
                    + '&content_id=' + encodeURIComponent(id)
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.protected) {
                    openModal(btn, true);
                } else {
                    /* Not protected — proceed with original handler */
                    if (btn._originalOnClick) {
                        btn._originalOnClick.call(btn, new MouseEvent('click'));
                    } else {
                        btn.dispatchEvent(new CustomEvent('ai-delete-approved', {
                            bubbles: true,
                            detail: { contentType: type, contentId: id }
                        }));
                    }
                }
            })
            .catch(function () {
                /* On API error, allow deletion (fail-open so UI isn't broken) */
                if (btn._originalOnClick) btn._originalOnClick.call(btn, e);
            });
        }, true);
    }

    function scanButtons() {
        document.querySelectorAll('[data-ai-delete="1"]').forEach(attachGuard);
    }

    /* ── Auto-protect newly generated content ───────── */
    window.aiProtect = function (contentType, contentId, contentTitle, contentPath) {
        fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=protect'
                + '&content_type='  + encodeURIComponent(contentType  || '')
                + '&content_id='    + encodeURIComponent(contentId    || '')
                + '&content_title=' + encodeURIComponent(contentTitle || '')
                + '&content_path='  + encodeURIComponent(contentPath  || '')
        }).catch(function () {});
    };

    /* ── Init ───────────────────────────────────────── */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scanButtons);
    } else {
        scanButtons();
    }

    /* Re-scan after dynamic content changes (e.g. AJAX-loaded tables) */
    var observer = new MutationObserver(function () { scanButtons(); });
    observer.observe(document.body, { childList: true, subtree: true });

})();
