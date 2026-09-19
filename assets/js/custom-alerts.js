/**
 * GCM Admin Alert System — Complete SEO System Theme
 * Matches /admin/pages/complete-seo-system.php design (purple gradient)
 *
 * API:
 *   GCMAlert.success(msg, title?, cb?)
 *   GCMAlert.error(msg, title?, cb?)
 *   GCMAlert.warning(msg, title?, cb?)
 *   GCMAlert.info(msg, title?, cb?)
 *   GCMAlert.confirm(msg, title?, onConfirm?, onCancel?)       — purple confirm
 *   GCMAlert.confirmDanger(msg, title?, onConfirm?, onCancel?) — red confirm (delete)
 *   GCMAlert.toast(msg, type?, title?, duration?)              — stacked toast
 *   GCMAlert.loading(msg?)                                     — show spinner modal
 *   GCMAlert.hideLoading()
 *   GCMAlert.updateLoading(msg)                                — update loading text
 *   GCMAlert.inline(type, title, msg, dismissible?)            — returns HTML string
 */

const GCMAlert = (function() {

    /* ── type map ─────────────────────────────── */
    const MAP = {
        success : { icon:'fa-check-circle',    btn:'gcm-btn-success',  title:'Success!'     },
        error   : { icon:'fa-times-circle',    btn:'gcm-btn-danger',   title:'Error!'       },
        warning : { icon:'fa-exclamation-triangle', btn:'gcm-btn-warning', title:'Warning!' },
        info    : { icon:'fa-info-circle',     btn:'gcm-btn-primary',  title:'Information'  },
        confirm : { icon:'fa-question-circle', btn:'gcm-btn-primary',  title:'Confirm'      },
        danger  : { icon:'fa-trash-alt',       btn:'gcm-btn-danger',   title:'Delete?'      },
        loading : { icon:null,                 btn:'gcm-btn-primary',  title:'Please Wait'  },
    };

    /* ── toast container (singleton) ─────────── */
    function getToastContainer() {
        let c = document.getElementById('gcm-toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'gcm-toast-container';
            c.className = 'gcm-toast-container';
            document.body.appendChild(c);
        }
        return c;
    }

    /* ── close a modal with animation ─────────── */
    function closeModal(overlay, modal, cb) {
        overlay.classList.add('closing');
        modal.classList.add('closing');
        setTimeout(function() {
            if (overlay.parentElement) overlay.parentElement.removeChild(overlay);
            if (cb) cb();
        }, 280);
    }

    /* ── build & show a modal ─────────────────── */
    function showModal(opts, onConfirm, onCancel) {
        var t = opts.type || 'info';
        var m = MAP[t] || MAP.info;

        var overlay = document.createElement('div');
        overlay.className = 'gcm-modal-overlay';

        var modal = document.createElement('div');
        modal.className = 'gcm-modal type-' + t;

        var cancelHtml = opts.showCancel
            ? '<button class="gcm-btn ' + (opts.cancelClass || 'gcm-btn-secondary') + '" data-action="cancel"><i class="fas fa-times"></i> ' + (opts.cancelText || 'Cancel') + '</button>'
            : '';

        var iconHtml = opts.loadingSpinner
            ? '<div class="gcm-loading"></div>'
            : '<i class="fas ' + (opts.icon || m.icon) + '"></i>';

        modal.innerHTML =
            '<div class="gcm-modal-header">' +
                '<div class="gcm-modal-icon">' + iconHtml + '</div>' +
                '<h3 class="gcm-modal-title">' + (opts.title || m.title) + '</h3>' +
            '</div>' +
            '<div class="gcm-modal-body">' +
                '<p class="gcm-modal-message" id="gcm-modal-msg">' + opts.message + '</p>' +
                (opts.hideButtons ? '' :
                '<div class="gcm-modal-buttons">' +
                    cancelHtml +
                    '<button class="gcm-btn ' + (opts.confirmClass || m.btn) + '" data-action="confirm">' +
                        '<i class="fas ' + (opts.confirmIcon || 'fa-check') + '"></i> ' +
                        (opts.confirmText || 'OK') +
                    '</button>' +
                '</div>') +
            '</div>';

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        var confirmBtn = modal.querySelector('[data-action="confirm"]');
        var cancelBtn  = modal.querySelector('[data-action="cancel"]');

        function close(action) {
            closeModal(overlay, modal, function() {
                if (action === 'confirm' && onConfirm) onConfirm();
                if (action === 'cancel'  && onCancel)  onCancel();
            });
        }

        if (confirmBtn) confirmBtn.addEventListener('click', function(){ close('confirm'); });
        if (cancelBtn)  cancelBtn.addEventListener('click',  function(){ close('cancel');  });

        overlay.addEventListener('click', function(e) {
            if (e.target === overlay && !opts.hideButtons) close('cancel');
        });

        var escH = function(e) {
            if (e.key === 'Escape' && !opts.hideButtons) {
                close('cancel');
                document.removeEventListener('keydown', escH);
            }
        };
        document.addEventListener('keydown', escH);

        if (confirmBtn) setTimeout(function(){ confirmBtn.focus(); }, 400);

        return { overlay: overlay, modal: modal };
    }

    /* ── public API ───────────────────────────── */
    return {

        success: function(msg, title, cb) {
            showModal({ type:'success', title:title||'Success!', message:msg,
                        icon:'fa-check-circle', confirmText:'OK', confirmClass:'gcm-btn-success', confirmIcon:'fa-check' }, cb);
        },

        error: function(msg, title, cb) {
            showModal({ type:'error', title:title||'Error!', message:msg,
                        icon:'fa-times-circle', confirmText:'OK', confirmClass:'gcm-btn-danger', confirmIcon:'fa-times' }, cb);
        },

        warning: function(msg, title, cb) {
            showModal({ type:'warning', title:title||'Warning!', message:msg,
                        icon:'fa-exclamation-triangle', confirmText:'OK', confirmClass:'gcm-btn-warning', confirmIcon:'fa-check' }, cb);
        },

        info: function(msg, title, cb) {
            showModal({ type:'info', title:title||'Information', message:msg,
                        icon:'fa-info-circle', confirmText:'OK', confirmClass:'gcm-btn-primary', confirmIcon:'fa-check' }, cb);
        },

        /* Purple confirm — for general confirmations */
        confirm: function(msg, title, onConfirm, onCancel) {
            showModal({
                type:'confirm', title:title||'Confirm Action', message:msg,
                icon:'fa-question-circle', showCancel:true,
                confirmText:'Confirm', confirmIcon:'fa-check', confirmClass:'gcm-btn-primary',
                cancelText:'Cancel',  cancelClass:'gcm-btn-secondary'
            }, onConfirm, onCancel);
        },

        /* Red confirm — for destructive delete actions */
        confirmDanger: function(msg, title, onConfirm, onCancel) {
            showModal({
                type:'danger', title:title||'Delete Permanently?', message:msg,
                icon:'fa-trash-alt', showCancel:true,
                confirmText:'Yes, Delete', confirmIcon:'fa-trash', confirmClass:'gcm-btn-danger',
                cancelText:'Cancel',       cancelClass:'gcm-btn-secondary'
            }, onConfirm, onCancel);
        },

        /* Stacked toast notification */
        toast: function(msg, type, title, duration) {
            type     = type     || 'success';
            duration = (duration !== undefined) ? duration : 4000;

            var icons  = { success:'fa-check-circle', error:'fa-times-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
            var titles = { success:'Success', error:'Error', warning:'Warning', info:'Info' };

            var toast = document.createElement('div');
            toast.className = 'gcm-toast toast-' + type;
            if (duration > 0) toast.style.setProperty('--td', (duration / 1000) + 's');

            toast.innerHTML =
                '<div class="gcm-toast-icon"><i class="fas ' + (icons[type]||icons.info) + '"></i></div>' +
                '<div class="gcm-toast-content">' +
                    '<div class="gcm-toast-title">' + (title || titles[type] || 'Notice') + '</div>' +
                    '<div class="gcm-toast-message">' + msg + '</div>' +
                '</div>' +
                '<button class="gcm-toast-close"><i class="fas fa-times"></i></button>' +
                (duration > 0 ? '<div class="gcm-toast-progress"></div>' : '');

            getToastContainer().appendChild(toast);

            var closed = false;
            function closeToast() {
                if (closed) return;
                closed = true;
                toast.classList.add('hiding');
                setTimeout(function() {
                    if (toast.parentElement) toast.parentElement.removeChild(toast);
                }, 320);
            }

            toast.querySelector('.gcm-toast-close').addEventListener('click', closeToast);
            if (duration > 0) setTimeout(closeToast, duration);

            return toast;
        },

        /* Loading spinner modal */
        loading: function(msg) {
            var handle = showModal({
                type:'loading', title:'Please Wait', message: msg || 'Processing, please wait&hellip;',
                icon:null, loadingSpinner:true, hideButtons:true
            });
            handle.overlay.id = 'gcm-loading-overlay';
            return handle.overlay;
        },

        /* Update loading message text */
        updateLoading: function(msg) {
            var el = document.getElementById('gcm-loading-overlay');
            if (el) {
                var m = el.querySelector('#gcm-modal-msg');
                if (m) m.innerHTML = msg;
            }
        },

        /* Hide loading modal */
        hideLoading: function() {
            var el = document.getElementById('gcm-loading-overlay');
            if (el) {
                var modal = el.querySelector('.gcm-modal');
                closeModal(el, modal || el);
            }
        },

        /**
         * Returns an HTML string for an inline alert banner.
         * Usage: el.innerHTML = GCMAlert.inline('success', 'Saved!', 'Changes saved.');
         *        el.innerHTML = GCMAlert.inline('error',   'Failed', err, true); // dismissible
         */
        inline: function(type, title, msg, dismissible) {
            var icons = { success:'fa-check-circle', error:'fa-times-circle', warning:'fa-exclamation-triangle', info:'fa-info-circle' };
            var icon  = icons[type] || icons.info;
            var closeBtn = dismissible
                ? '<button class="gcm-alert-close" onclick="this.closest(\'.gcm-alert\').remove()" title="Dismiss"><i class="fas fa-times"></i></button>'
                : '';
            return '<div class="gcm-alert gcm-alert-' + type + '">' +
                '<div class="gcm-alert-icon"><i class="fas ' + icon + '"></i></div>' +
                '<div class="gcm-alert-content">' +
                    (title ? '<span class="gcm-alert-title">' + title + '</span>' : '') +
                    '<p>' + msg + '</p>' +
                '</div>' +
                closeBtn +
            '</div>';
        }
    };
})();

/* ── Compatibility: override window.alert with styled info modal ── */
var _gcmOrigAlert = window.alert;
window.alert = function(message) { GCMAlert.info(String(message), 'Alert'); };
window.alertSync = _gcmOrigAlert;

/* ── Export ── */
if (typeof module !== 'undefined' && module.exports) { module.exports = GCMAlert; }
