<?php
/**
 * toast.php - Reusable Production Toast Notification System
 *
 * Include this file in any page where you want toast notifications.
 * Place the include AFTER your PHP logic but BEFORE the closing </body> tag.
 *
 * USAGE (in any page):
 *   <?php include 'includes/toast.php'; ?>
 *
 * Then in JavaScript anywhere on the page:
 *
 *   toast.success('Profile updated!', { title: 'Saved', duration: 4000 });
 *   toast.error('Connection lost.', { title: 'Error', duration: 6000 });
 *   toast.info('New message received.', { duration: 5000 });
 *   toast.warning('Session expires soon.', { title: 'Warning', duration: 8000 });
 *
 *   // Dismiss programmatically:
 *   const id = toast.success('Operation complete.');
 *   toast.dismiss(id);
 *
 *   // Dismiss all:
 *   toast.dismissAll();
 */
?>
<!-- Toast Container - fixed overlay for notifications (WCAG 4.1.3 Live Region) -->
<div id="toastContainer" role="region" aria-label="Notifications" aria-live="polite"></div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<style>
/* ------------------------------------------------------------
   Toast System – Modern UI/UX with Glassmorphism & Dark Mode
   ------------------------------------------------------------ */
:root {
  --toast-bg-light: rgba(255,255,255,0.85);
  --toast-bg-dark: rgba(0,0,0,0.65);
  --toast-fg-light: #111827;
  --toast-fg-dark: #F3F4F6;
  --toast-success: #10B981;
  --toast-error: #EF4444;
  --toast-info: #3B82F6;
  --toast-warning: #F59E0B;
  --toast-border-width: 4px;
}

/* Container */
#toastContainer {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 12px;
    pointer-events: none;
    max-width: 400px;
    width: 100%;
}

/* Base Toast */
.toast {
    pointer-events: auto;
    background: var(--toast-bg-light);
    color: var(--toast-fg-light);
    border-radius: 14px;
    padding: 16px 18px;
    display: flex;
    align-items: flex-start;
    gap: 14px;
    box-shadow:
        0 4px 6px -1px rgba(0,0,0,0.05),
        0 10px 40px -4px rgba(0,0,0,0.1),
        0 0 0 1px rgba(0,0,0,0.02);
    transform: translateX(calc(100% + 40px));
    opacity: 0;
    position: relative;
    overflow: hidden;
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: transform 0.45s cubic-bezier(0.34, 1.56, 0.64, 1), opacity 0.45s;
}

/* Dark mode */
@media (prefers-color-scheme: dark) {
    .toast {
        background: var(--toast-bg-dark);
        color: var(--toast-fg-dark);
    }
}

/* Variant borders */
.toast.success   { border-left: var(--toast-border-width) solid var(--toast-success); }
.toast.error     { border-left: var(--toast-border-width) solid var(--toast-error); }
.toast.info      { border-left: var(--toast-border-width) solid var(--toast-info); }
.toast.warning   { border-left: var(--toast-border-width) solid var(--toast-warning); }

/* Show / Hide animations */
.toast.show { animation: toastSlideIn 0.6s cubic-bezier(.34,1.56,.64,1) forwards; }
.toast.hiding { animation: toastSlideOut 0.35s ease forwards; }

@keyframes toastSlideIn {
    0%   { transform: translateX(calc(100% + 40px)); opacity:0; }
    60%  { transform: translateX(-10%); }
    100% { transform: translateX(0); opacity:1; }
}
@keyframes toastSlideOut {
    to { transform: translateX(calc(100% + 40px)); opacity:0; }
}

/* Icon */
.toast-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: 12px;
    color: white;
    position: relative;
}
.toast.success .toast-icon   { background: linear-gradient(135deg, #10B981, #059669); }
.toast.error .toast-icon     { background: linear-gradient(135deg, #EF4444, #DC2626); }
.toast.info .toast-icon      { background: linear-gradient(135deg, #3B82F6, #2563EB); }
.toast.warning .toast-icon   { background: linear-gradient(135deg, #F59E0B, #D97706); }

/* Icon pop animation */
.toast.show .toast-icon i { animation: toastIconPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1); }
@keyframes toastIconPop {
    0%   { transform: scale(0) rotate(-90deg); opacity: 0; }
    60%  { transform: scale(1.2) rotate(5deg); }
    100% { transform: scale(1) rotate(0deg); opacity: 1; }
}

/* Body */
.toast-body { flex: 1; min-width: 0; }
.toast-title { font-size: 0.85rem; font-weight: 700; margin: 0 0 2px 0; line-height: 1.3; }
.toast-message { font-size: 0.78rem; margin: 0; line-height: 1.4; }

/* Close button */
.toast-close-btn {
    width: 24px; height: 24px; border-radius: 50%; border: none; background: transparent;
    color: #9CA3AF; cursor: pointer; display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; transition: all 0.2s ease; font-size: 11px;
}
.toast-close-btn:hover { background: rgba(0,0,0,0.06); color: #4B5563; transform: rotate(90deg); }

/* Progress bar */
.toast-progress-track { position: absolute; bottom: 0; left: 0; right: 0; height: 3px; background: rgba(0,0,0,0.04); border-radius: 0 0 14px 14px; overflow: hidden; }
.toast-progress-fill { height: 100%; width: 100%; background: linear-gradient(90deg, var(--toast-success), var(--toast-success)); transform: translateX(0); transition: transform linear; border-radius: 0 0 0 14px; }
.toast.error .toast-progress-fill   { background: linear-gradient(90deg, var(--toast-error), var(--toast-error)); }
.toast.info .toast-progress-fill    { background: linear-gradient(90deg, var(--toast-info), var(--toast-info)); }
.toast.warning .toast-progress-fill { background: linear-gradient(90deg, var(--toast-warning), var(--toast-warning)); }

/* Shake for error */
.toast.error.show { animation: toastShake 0.5s ease-in-out; }
@keyframes toastShake { 0%,100%{transform:translateX(0)} 20%{transform:translateX(-8px)} 40%{transform:translateX(8px)} 60%{transform:translateX(-5px)} 80%{transform:translateX(5px)} }

/* Action button */
.toast-action { margin-left: 12px; background: transparent; border: none; color: inherit; cursor: pointer; font-weight: 600; }
.toast-action:hover { text-decoration: underline; }

/* Responsive mobile */
@media (max-width: 480px) {
    #toastContainer { top: 16px; right: 12px; left: 12px; max-width: none; }
}
</style>

<script>
/**
 * toast - Production Notification System
 *
 * REAL-WORLD USAGE EXAMPLES:
 *
 *   toast.success('Profile updated successfully!', { duration: 4000 });
 *   toast.error('Connection lost. Retrying...', { duration: 8000 });
 *   toast.info('Your report is ready for download.', { duration: 5000 });
 *   toast.warning('Session expires in 5 minutes.', { duration: 10000 });
 *
 *   // With title:
 *   toast.success('Payment received!', {
 *       title: 'Transaction Complete',
 *       duration: 5000
 *   });
 *
 *   // Silent (no animation, for background events):
 *   toast.info('Background sync complete.', { silent: true });
 *
 * @param {string} message  - Main toast message text
 * @param {object} options  - { title, type, duration, silent }
 */
const toast = (function() {
    const container = document.getElementById('toastContainer');

    // Ensure container exists
    if (!container) {
        console.warn('toast: #toastContainer not found in DOM');
        return;
    }

    // Toast counter for unique IDs
    let counter = 0;

    /**
     * Internal method to create and show a toast
     */
    function createToast(message, options = {}) {
        const {
            title = '',
            type = 'info',
            duration = 5000,
            silent = false
        } = options;

        const id = 'toast-' + (++counter);

        // Map type to icon
        const icons = {
            success: 'fa-check',
            error:   'fa-exclamation',
            info:    'fa-info',
            warning: 'fa-exclamation-triangle'
        };

        // Build the toast element with accessibility semantics
        const toastEl = document.createElement('div');
        toastEl.className = 'toast ' + type;
        toastEl.id = id;
        toastEl.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toastEl.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        toastEl.setAttribute('aria-atomic', 'true');
        toastEl.innerHTML =
            '<div class="toast-icon">' +
                '<i class="fas ' + (icons[type] || icons.info) + '" aria-hidden="true"></i>' +
            '</div>' +
            '<div class="toast-body">' +
                (title ? '<p class="toast-title">' + escapeHtml(title) + '</p>' : '') +
                '<p class="toast-message">' + escapeHtml(message) + '</p>' +
            '</div>' +
            '<button class="toast-close-btn" onclick="toast.dismiss(\'' + id + '\')" aria-label="Close notification">' +
                '<i class="fas fa-times" aria-hidden="true"></i>' +
            '</button>' +
            '<div class="toast-progress-track">' +
                '<div class="toast-progress-fill" id="' + id + '-progress"></div>' +
            '</div>';

        // Append to container
        container.appendChild(toastEl);

        // Store timeouts on the element for cleanup
        toastEl._timeouts = [];

        // Trigger show animation on next frame
        if (silent) {
            toastEl.classList.add('show');
        } else {
            requestAnimationFrame(function() {
                toastEl.classList.add('show');
            });
        }

        // Start progress bar
        var progressBar = toastEl.querySelector('.toast-progress-fill');
        if (progressBar) {
            requestAnimationFrame(function() {
                progressBar.style.transition = 'transform ' + duration + 'ms linear';
                progressBar.style.transform = 'translateX(-100%)';
            });
        }

        // Auto-dismiss after duration
        if (duration > 0) {
            var autoDismiss = setTimeout(function() {
                dismissToast(id);
            }, duration);
            toastEl._timeouts.push(autoDismiss);
        }

        return id;
    }

    /**
     * Dismiss a toast by ID with animation
     */
    function dismissToast(id) {
        var toastEl = document.getElementById(id);
        if (!toastEl) return;

        // Clear all timeouts associated with this toast
        if (toastEl._timeouts) {
            for (var i = 0; i < toastEl._timeouts.length; i++) {
                clearTimeout(toastEl._timeouts[i]);
            }
            toastEl._timeouts = [];
        }

        // Stop progress bar
        var progressBar = toastEl.querySelector('.toast-progress-fill');
        if (progressBar) {
            progressBar.style.transition = 'none';
        }

        // Add hiding class for exit animation
        toastEl.classList.remove('show');
        toastEl.classList.add('hiding');

        // Remove from DOM after animation completes
        setTimeout(function() {
            if (toastEl.parentNode) {
                toastEl.parentNode.removeChild(toastEl);
            }
        }, 400);
    }

    /**
     * Dismiss all visible toasts
     */
    function dismissAll() {
        var toasts = container.querySelectorAll('.toast');
        for (var i = 0; i < toasts.length; i++) {
            var id = toasts[i].id;
            if (id) dismissToast(id);
        }
    }

    /**
     * Simple HTML escaping to prevent XSS
     */
    function escapeHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Public API
    return {
        success: function(msg, opts) {
            opts = opts || {};
            opts.type = 'success';
            return createToast(msg, opts);
        },
        error: function(msg, opts) {
            opts = opts || {};
            opts.type = 'error';
            return createToast(msg, opts);
        },
        info: function(msg, opts) {
            opts = opts || {};
            opts.type = 'info';
            return createToast(msg, opts);
        },
        warning: function(msg, opts) {
            opts = opts || {};
            opts.type = 'warning';
            return createToast(msg, opts);
        },
        dismiss: dismissToast,
        dismissAll: dismissAll
    };
})();
</script>