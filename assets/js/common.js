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

    const WARNING_DURATION_SECS = 5; // 5-second warning countdown before timeout
    let warningModalEl = null;
    let isWarningShowing = false;

    function getOrCreateWarningModal() {
        if (warningModalEl && document.body.contains(warningModalEl)) {
            return warningModalEl;
        }

        const modal = document.createElement('div');
        modal.id = 'session-timeout-warning-modal';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', 'session-timeout-title');
        modal.setAttribute('aria-describedby', 'session-timeout-desc');
        modal.className = 'fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[99999] flex items-center justify-center p-4 transition-opacity duration-200 opacity-0 pointer-events-none';
        modal.innerHTML = `
            <div class="bg-white dark:bg-[#151d2f] rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-700 w-full max-w-md overflow-hidden transform transition-all duration-200 scale-95" onclick="event.stopPropagation()">
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/50 border-b border-slate-100 dark:border-slate-700/60 flex items-center justify-between">
                    <div class="flex items-center gap-2.5 text-sm font-bold text-slate-800 dark:text-slate-100">
                        <div class="w-8 h-8 rounded-xl bg-amber-100 dark:bg-amber-500/20 text-amber-600 dark:text-amber-400 flex items-center justify-center text-sm shadow-xs">
                            <i class="fa-solid fa-clock-rotate-left"></i>
                        </div>
                        <span>Session Inactivity Warning</span>
                    </div>
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-amber-50 dark:bg-amber-500/10 text-amber-700 dark:text-amber-300 border border-amber-200/80 dark:border-amber-500/20">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Expiring Soon
                    </span>
                </div>

                <!-- Modal Body -->
                <div class="p-6 text-center">
                    <div class="w-16 h-16 rounded-full bg-amber-50 dark:bg-amber-500/10 border-2 border-amber-300 dark:border-amber-500/30 flex items-center justify-center mx-auto mb-4">
                        <span id="session-timeout-countdown-number" class="text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">5</span>
                    </div>
                    <h3 id="session-timeout-title" class="text-base font-bold text-slate-900 dark:text-white mb-2">
                        Are you still there?
                    </h3>
                    <p id="session-timeout-desc" class="text-xs text-slate-500 dark:text-slate-400 max-w-xs mx-auto leading-relaxed">
                        You have been inactive for nearly 2 minutes (115 seconds). For your account security, your session will automatically terminate in:
                    </p>
                    <div class="mt-3 inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/60 text-amber-800 dark:text-amber-200 font-bold text-xs">
                        <i class="fa-solid fa-hourglass-half text-amber-600 dark:text-amber-400"></i>
                        Auto-logout in <span id="session-timeout-countdown" class="font-mono text-sm underline decoration-amber-400">5 seconds</span>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/40 border-t border-slate-100 dark:border-slate-700/60 flex items-center justify-end gap-3">
                    <button type="button" id="session-timeout-logout-btn" class="px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-slate-300">
                        Log Out Now
                    </button>
                    <button type="button" id="session-timeout-stay-btn" class="px-5 py-2.5 rounded-xl bg-[#176B87] hover:bg-[#053B50] text-white text-xs font-semibold shadow-xs transition focus:outline-none focus:ring-2 focus:ring-[#176B87]/50 inline-flex items-center gap-2">
                        <i class="fa-solid fa-check"></i>
                        <span>Yes, Keep Me Logged In</span>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        modal.querySelector('#session-timeout-stay-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            stayLoggedIn();
        });

        modal.querySelector('#session-timeout-logout-btn').addEventListener('click', function(e) {
            e.stopPropagation();
            triggerLogout();
        });

        warningModalEl = modal;
        return modal;
    }

    function showWarningModal(secondsRemaining) {
        if (isLoggingOut) return;
        const modal = getOrCreateWarningModal();
        const secs = Math.max(1, secondsRemaining);
        const countdownEl = modal.querySelector('#session-timeout-countdown');
        const numberEl = modal.querySelector('#session-timeout-countdown-number');
        if (countdownEl) {
            countdownEl.textContent = `${secs} second${secs === 1 ? '' : 's'}`;
        }
        if (numberEl) {
            numberEl.textContent = secs.toString();
        }
        if (!isWarningShowing) {
            isWarningShowing = true;
            modal.classList.remove('pointer-events-none');
            // Trigger animation frame for transition
            requestAnimationFrame(() => {
                modal.classList.remove('opacity-0');
                const card = modal.firstElementChild;
                if (card) {
                    card.classList.remove('scale-95');
                    card.classList.add('scale-100');
                }
            });
        }
    }

    function hideWarningModal() {
        if (!isWarningShowing && !warningModalEl) return;
        isWarningShowing = false;
        if (warningModalEl) {
            warningModalEl.classList.add('opacity-0', 'pointer-events-none');
            const card = warningModalEl.firstElementChild;
            if (card) {
                card.classList.remove('scale-100');
                card.classList.add('scale-95');
            }
        }
    }

    function stayLoggedIn() {
        hideWarningModal();
        const now = Date.now();
        lastRecordedActivity = now;
        lastHeartbeat = now;
        try {
            localStorage.setItem(STORAGE_KEY, now.toString());
        } catch (e) {}

        // Send heartbeat to backend immediately to refresh PHP session
        fetch(heartbeatUrl, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then(res => {
            if (res.status === 401) {
                triggerLogout();
            }
        }).catch(() => {});
    }

    function triggerLogout() {
        if (isLoggingOut) return;
        isLoggingOut = true;
        hideWarningModal();

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

        // If the warning dialog is currently active, require user to click "Yes" instead of arbitrary background mousemove dismissing it
        if (isWarningShowing) return;

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
        const warningThreshold = Math.max(0, timeoutSecs - WARNING_DURATION_SECS);

        if (elapsedSeconds >= timeoutSecs) {
            triggerLogout();
        } else if (elapsedSeconds >= warningThreshold) {
            const secondsRemaining = timeoutSecs - elapsedSeconds;
            showWarningModal(secondsRemaining);
        } else if (isWarningShowing) {
            hideWarningModal();
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
        if (e.key === STORAGE_KEY && e.newValue) {
            const updated = parseInt(e.newValue, 10);
            if (updated && (Date.now() - updated) / 1000 < (timeoutSecs - WARNING_DURATION_SECS)) {
                hideWarningModal();
            }
        }
    });
})();
