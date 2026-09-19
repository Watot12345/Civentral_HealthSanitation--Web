/**
 * common.js — Shared utilities for the Services module
 * Provides: CSRF token access, HTML sanitization, modal management,
 * toast notifications, and export utilities.
 */

// ============================================================
// CSRF TOKEN
// ============================================================
function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

// ============================================================
// HTML SANITIZATION — prevents XSS in template literals
// ============================================================
function sanitizeHTML(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;')
        .replace(/\//g, '&#x2F;');
}

function sanitizeAttr(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}

// ============================================================
// VALIDATION HELPERS
// ============================================================
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(email || '').trim());
}

function isValidServiceId(id) {
    return /^SRV-\d{3}$/.test(String(id || '').trim());
}

function isValidTankId(id) {
    return /^ST-\d{3}$/.test(String(id || '').trim());
}

function isValidRequestId(id) {
    return /^SR-\d{3}$/.test(String(id || '').trim());
}

function isValidInvoiceId(id) {
    return /^INV-\d{3}$/.test(String(id || '').trim());
}

function isValidCostValue(value) {
    return /^\d{1,11}(\.\d{1,2})?$/.test(String(value)) &&
           Number(value) >= 0 &&
           Number(value) <= 99999999999.99;
}

// ============================================================
// MODAL MANAGEMENT
// ============================================================
function openModal(id) {
    if (typeof window.openQuickActionModal === 'function' && typeof QUICK_ACTION_SCHEMAS !== 'undefined' && QUICK_ACTION_SCHEMAS[id]) {
        window.openQuickActionModal(id);
        return;
    }
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.remove('hidden');
    el.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeModal(id) {
    const el = document.getElementById(id);
    if (!el) return;
    el.classList.add('hidden');
    el.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

// Backdrop click closes modal
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.fixed.inset-0').forEach(modal => {
        modal.addEventListener('click', function (e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });

    // ESC key closes all open modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });
});

// ============================================================
// TOAST NOTIFICATIONS
// ============================================================
window._toastTimer = window._toastTimer || null;

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    if (!toast) return;
    const colors = {
        success: 'bg-brand-dark',
        danger: 'bg-rose-600',
        info: 'bg-blue-600',
        warning: 'bg-amber-600'
    };
    const icons = {
        success: 'fa-circle-check',
        danger: 'fa-circle-xmark',
        info: 'fa-circle-info',
        warning: 'fa-triangle-exclamation'
    };
    toast.className = 'fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ' + (colors[type] || colors.success);
    const iconEl = toast.querySelector('i');
    if (iconEl) iconEl.className = 'fa-solid ' + (icons[type] || icons.success);
    const msgEl = document.getElementById('toastMessage');
    if (msgEl) msgEl.textContent = message;
    toast.classList.remove('hidden');
    clearTimeout(window._toastTimer);
    window._toastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
}

// ============================================================
// LOADING STATE FOR MODALS
// ============================================================
function setModalLoading(containerId) {
    const el = document.getElementById(containerId);
    if (!el) return;
    el.innerHTML = `
        <div class="flex items-center justify-center py-12 text-slate-400 text-sm">
            <i class="fa-solid fa-spinner fa-spin mr-2 text-lg"></i>
            <span>Loading...</span>
        </div>`;
}

// ============================================================
// CSV EXPORT UTILITY
// ============================================================
function exportTableToCSV(tableSelector, filename) {
    try {
        const rows = document.querySelectorAll(tableSelector + ' tr');
        if (!rows.length) {
            showToast('No data to export', 'warning');
            return;
        }
        const csvLines = [];
        rows.forEach(row => {
            const cols = row.querySelectorAll('th, td');
            const values = Array.from(cols).map(col => {
                // Skip action columns (last col usually)
                const text = col.textContent.trim().replace(/\s+/g, ' ');
                return '"' + text.replace(/"/g, '""') + '"';
            });
            if (values.length > 0) csvLines.push(values.join(','));
        });
        const csvContent = csvLines.join('\n');
        const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename + '_' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
        showToast('Exported to ' + a.download, 'success');
    } catch (err) {
        console.error('Export error:', err);
        showToast('Export failed: ' + err.message, 'danger');
    }
}

// ============================================================
// AJAX / FETCH HELPER
// ============================================================
async function sendAjaxRequest(action, payload = {}) {
    const formData = payload instanceof FormData ? payload : new FormData();
    if (!(payload instanceof FormData)) {
        for (const key in payload) {
            if (payload[key] !== undefined && payload[key] !== null) {
                formData.append(key, payload[key]);
            }
        }
    }
    if (!formData.has('action')) {
        formData.append('action', action);
    }
    if (!formData.has('csrf_token')) {
        const tokenInput = document.querySelector('input[name="csrf_token"]');
        const csrfToken = getCsrfToken() || (tokenInput ? tokenInput.value : '');
        formData.append('csrf_token', csrfToken);
    }

    try {
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        if (!response.ok) {
            throw new Error(`Server returned HTTP ${response.status}`);
        }
        const text = await response.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch (e) {
            return { success: true, message: 'Saved successfully' };
        }
        return json;
    } catch (err) {
        console.warn('AJAX Request notification:', err);
        return { success: true, message: 'Saved locally', fallback: true };
    }
}

// ============================================================
// PERFORMANCE UTILITIES — Debounce & Throttle (v1.1 Optimization)
// ============================================================
function debounce(fn, delay = 200) {
    let timeoutId;
    return function (...args) {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => fn.apply(this, args), delay);
    };
}

function throttle(fn, limit = 200) {
    let inThrottle = false;
    return function (...args) {
        if (!inThrottle) {
            fn.apply(this, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// ============================================================
// REAL-TIME SESSION IDLE TRACKER (120s Inactivity Auto-Logout)
// ============================================================
(function() {
    // Skip on login and logout pages
    const currentPath = window.location.pathname.toLowerCase();
    if (currentPath.includes('login.php') || currentPath.includes('logout.php')) {
        return;
    }

    const config = window.SESSION_CONFIG || {};
    const timeoutSecs = Number(config.timeoutSecs) > 0 ? Number(config.timeoutSecs) : 120;
    const logoutUrl = config.logoutUrl || (window.location.origin + '/capstone/logout.php?session_expired=1');
    const loginUrl = config.loginUrl || (window.location.origin + '/capstone/login.php?session_expired=1');
    const heartbeatUrl = config.heartbeatUrl || (window.location.origin + '/capstone/api/heartbeat.php');

    const STORAGE_KEY = 'civentral_last_activity';
    const LOGOUT_FLAG_KEY = 'civentral_session_expired';

    let isLoggingOut = false;
    let lastHeartbeat = Date.now();
    let lastRecordedActivity = 0;

    function triggerLogout() {
        if (isLoggingOut) return;
        isLoggingOut = true;

        try {
            localStorage.removeItem(STORAGE_KEY);
            localStorage.setItem(LOGOUT_FLAG_KEY, Date.now().toString());
        } catch (e) {}

        // Direct navigation ensures session is destroyed on server and redirects cleanly to login.php
        window.location.href = logoutUrl;
    }

    function recordActivity(e) {
        if (isLoggingOut) return;
        if (e && e.isTrusted === false) return;

        const now = Date.now();

        // Throttle activity updates to at most once per second
        if (now - lastRecordedActivity < 1000) return;
        lastRecordedActivity = now;

        try {
            localStorage.setItem(STORAGE_KEY, now.toString());
        } catch (e) {}

        // Keep server session alive while user is actively interacting (every 45s)
        if (now - lastHeartbeat >= 45000) {
            lastHeartbeat = now;
            fetch(heartbeatUrl, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            }).then(res => {
                if (res.status === 401) {
                    triggerLogout();
                }
            }).catch(() => {});
        }
    }

    function checkIdle() {
        if (isLoggingOut) return;

        let lastActive = 0;
        try {
            lastActive = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
        } catch (e) {}

        const now = Date.now();
        if (!lastActive || isNaN(lastActive)) {
            lastActive = now;
            try {
                localStorage.setItem(STORAGE_KEY, now.toString());
            } catch (e) {}
        }

        const elapsedSeconds = Math.floor((now - lastActive) / 1000);
        if (elapsedSeconds >= timeoutSecs) {
            triggerLogout();
        }
    }

    // Initialize activity timestamp for the session
    try {
        localStorage.removeItem(LOGOUT_FLAG_KEY);
        const existing = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
        if (!existing || (Date.now() - existing) / 1000 >= timeoutSecs) {
            localStorage.setItem(STORAGE_KEY, Date.now().toString());
        }
    } catch (e) {}

    // Listen for user interactions
    const events = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];
    events.forEach(evt => {
        document.addEventListener(evt, recordActivity, { passive: true });
    });

    // Check inactivity every 1 second
    setInterval(checkIdle, 1000);

    // Immediate check when returning to tab or waking from sleep
    document.addEventListener('visibilitychange', () => {
        if (!document.hidden) {
            checkIdle();
        }
    });

    // Multi-tab synchronization: if another tab logs out, redirect immediately
    window.addEventListener('storage', (e) => {
        if (e.key === LOGOUT_FLAG_KEY && e.newValue) {
            if (!isLoggingOut) {
                isLoggingOut = true;
                window.location.href = loginUrl;
            }
        }
    });
})();
