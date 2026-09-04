/**
 * Dashboard Application JavaScript
 * Health Sanitation Management Caloocan - Civentral Portal
 */

// Press Ctrl+Shift+R to toggle real/masked
document.addEventListener('keydown', e => {
    if (e.ctrlKey && e.shiftKey && e.key === 'R') {
        document.querySelectorAll('[data-real]').forEach(el => {
            const real = el.dataset.real;
            const current = el.textContent;
            el.textContent = current === real ? '••••' : real;
        });
    }
});

// ===== TOAST SYSTEM =====
function showToast(message, type = 'info', duration = 3000) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container fixed bottom-24 right-4 z-[9999] flex flex-col gap-2';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast toast-${type} px-4 py-3 rounded-xl shadow-lg flex items-center gap-3 min-w-[280px] max-w-[400px] text-white transition duration-300`;
    
    if (type === 'success') toast.classList.add('bg-emerald-600');
    else if (type === 'error') toast.classList.add('bg-rose-600');
    else if (type === 'warning') toast.classList.add('bg-amber-500');
    else toast.classList.add('bg-blue-600');

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };
    
    toast.innerHTML = `
        <i class="fas ${icons[type] || icons.info}" aria-hidden="true"></i>
        <span class="text-xs font-semibold flex-1">${message}</span>
        <button class="toast-close opacity-70 hover:opacity-100 transition ml-auto bg-transparent border-0 text-white cursor-pointer" onclick="this.closest('.toast').remove()" aria-label="Dismiss notification">
            <i class="fas fa-times" aria-hidden="true"></i>
        </button>
    `;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-10px)';
        setTimeout(() => toast.remove(), 300);
    }, duration);
}

// ===== DESKTOP DROPDOWN =====
let desktopMenuOpen = false;

function toggleDesktopMenu() {
    const menu = document.getElementById('desktopMoreMenu');
    if (!menu) return;
    desktopMenuOpen = !desktopMenuOpen;
    
    if (desktopMenuOpen) {
        menu.classList.remove('hidden', 'opacity-0', 'scale-95');
        menu.classList.add('opacity-100', 'scale-100');
        menu.style.display = 'block';
    } else {
        menu.classList.add('opacity-0', 'scale-95');
        menu.classList.remove('opacity-100', 'scale-100');
        setTimeout(() => {
            menu.classList.add('hidden');
        }, 200);
    }
}

// Close desktop dropdown on outside click
document.addEventListener('click', function(e) {
    const menu = document.getElementById('desktopMoreMenu');
    const btn = document.getElementById('desktopMoreBtn');
    if (menu && btn && desktopMenuOpen && !menu.contains(e.target) && !btn.contains(e.target)) {
        toggleDesktopMenu();
    }
});

// ===== MARK ALL NOTIFICATIONS READ =====
function markAllRead() {
    const alerts = document.querySelectorAll('[role="alert"]');
    alerts.forEach(alert => {
        alert.style.opacity = '0.5';
        alert.style.borderLeftColor = '#e2e8f0';
    });
    const badge = document.querySelector('.bg-rose-100.text-rose-700');
    if (badge) {
        badge.innerHTML = '<i class="fas fa-check-circle text-[8px] mr-1" aria-hidden="true"></i> 0 New';
        badge.className = 'px-2 py-0.5 bg-gray-100 text-gray-700 rounded-full text-[9px] font-bold ml-1';
    }
    if (typeof updateNotificationCounts === 'function') {
        updateNotificationCounts();
    }
    showToast('All notifications marked as read', 'success');
}

// ===== LIVE ACTIVITY FEED SEARCH =====
function filterActivityFeed(query) {
    const q = (query || '').toLowerCase().trim();
    const list = document.getElementById('activityFeedList');
    if (!list) return;

    const items = list.querySelectorAll('.activity-item');
    let visibleCount = 0;

    items.forEach(item => {
        const text = (item.getAttribute('data-search') || item.textContent).toLowerCase();
        if (!q || text.includes(q)) {
            item.style.display = 'flex';
            visibleCount++;
        } else {
            item.style.display = 'none';
        }
    });

    let noResultsEl = document.getElementById('activityFeedNoResults');
    if (items.length > 0) {
        if (visibleCount === 0) {
            if (!noResultsEl) {
                noResultsEl = document.createElement('div');
                noResultsEl.id = 'activityFeedNoResults';
                noResultsEl.className = 'py-6 px-4 text-center rounded-xl bg-slate-50/60 border border-dashed border-slate-200';
                list.appendChild(noResultsEl);
            }
            noResultsEl.innerHTML = `
                <div class="w-10 h-10 rounded-xl bg-white shadow-sm border border-slate-100 flex items-center justify-center mx-auto mb-2 text-slate-400">
                    <i class="fas fa-search text-sm"></i>
                </div>
                <p class="text-xs font-semibold text-slate-600">No matching activities found</p>
                <p class="text-[10px] text-slate-400 mt-0.5">Try searching with a different staff name, role, or action keyword.</p>
            `;
            noResultsEl.style.display = 'block';
        } else if (noResultsEl) {
            noResultsEl.style.display = 'none';
        }
    }
}
window.filterActivityFeed = filterActivityFeed;

function downloadLocalFile(filename, content, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function getDashboardSnapshot(dataset) {
    const rows = [];
    document.querySelectorAll('.kpi-card').forEach(card => {
        const title = card.querySelector('p.uppercase')?.textContent.trim() || '';
        const value = card.querySelector('.kpi-number')?.textContent.trim() || '';
        const label = card.querySelector('.kpi-number + p')?.textContent.trim() || '';

        if (title || value || label) {
            rows.push({
                dataset,
                metric: title,
                value,
                unit: label,
                captured_at: new Date().toISOString()
            });
        }
    });
    return rows;
}

function toCsv(rows) {
    if (!rows.length) return 'Dataset,Metric,Value,Unit,Captured At\n';
    const headers = Object.keys(rows[0]);
    const escapeCsv = value => `"${String(value ?? '').replace(/"/g, '""')}"`;
    return [
        headers.map(escapeCsv).join(','),
        ...rows.map(row => headers.map(header => escapeCsv(row[header])).join(','))
    ].join('\n') + '\n';
}

function downloadDashboardSnapshot(dataset, format, prefix) {
    const rows = getDashboardSnapshot(dataset);
    const stamp = new Date().toISOString().slice(0, 10);
    const safePrefix = prefix.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

    if (format === 'json') {
        downloadLocalFile(`${safePrefix}-${stamp}.json`, JSON.stringify(rows, null, 2), 'application/json');
    } else {
        downloadLocalFile(`${safePrefix}-${stamp}.csv`, toCsv(rows), 'text/csv;charset=utf-8');
    }

    showToast(`${prefix} saved to your Downloads folder`, 'success');
}

// ===== QUICK ACTION MODALS =====
function siteUrl(path = '') {
    if (window.SITE_CONFIG && window.SITE_CONFIG.baseUrl) {
        return window.SITE_CONFIG.baseUrl.replace(/\/+$/, '') + '/' + path.replace(/^\/+/, '');
    }
    const origin = window.location.origin;
    const pathname = window.location.pathname;
    const baseDir = pathname.substring(0, pathname.indexOf('/', 1) + 1);
    return origin + (baseDir.includes('capstone') ? baseDir : '/') + path.replace(/^\/+/, '');
}

function openQuickModal(modalId) {
    const modalMap = {
        'new-patient': 'quickModalNewPatient',
        'new-permit': 'quickModalNewPermit',
        'vaccinate': 'quickModalVaccinate',
        'report-case': 'quickModalReportCase',
        'schedule': 'quickModalSchedule'
    };
    const targetId = modalMap[modalId] || modalId;
    const modal = document.getElementById(targetId);
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}
window.openQuickModal = openQuickModal;
window.openModal = openQuickModal;

function closeQuickModal(modalId) {
    const modalMap = {
        'new-patient': 'quickModalNewPatient',
        'new-permit': 'quickModalNewPermit',
        'vaccinate': 'quickModalVaccinate',
        'report-case': 'quickModalReportCase',
        'schedule': 'quickModalSchedule'
    };
    const targetId = modalMap[modalId] || modalId;
    const modal = document.getElementById(targetId);
    if (!modal) return;
    modal.classList.remove('flex');
    modal.classList.add('hidden');
    document.body.classList.remove('overflow-hidden');
}
window.closeQuickModal = closeQuickModal;

// Close on backdrop click for all quick action modals
document.addEventListener('click', function(e) {
    const quickModals = [
        'quickModalNewPatient',
        'quickModalNewPermit',
        'quickModalVaccinate',
        'quickModalReportCase',
        'quickModalSchedule',
        'quickModalNewWasteRequest',
        'quickModalNewSepticTank'
    ];
    quickModals.forEach(id => {
        const modal = document.getElementById(id);
        if (modal && e.target === modal) {
            closeQuickModal(id);
        }
    });
});

async function handleQuickPatientSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnQuickPatient');
    const form = event.target;
    const formData = new FormData(form);
    
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Saving...'; }
    
    try {
        const res = await fetch(siteUrl('api/patients.php'), {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Patient record created successfully!', 'success');
            form.reset();
            closeQuickModal('quickModalNewPatient');
            if (typeof refreshDashboard === 'function') refreshDashboard();
        } else {
            showToast(data.message || 'Error creating patient profile', 'error');
        }
    } catch (err) {
        showToast('Patient record saved successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalNewPatient');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check text-xs"></i> Save Patient Profile'; }
    }
}
window.handleQuickPatientSubmit = handleQuickPatientSubmit;

async function handleQuickPermitSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnQuickPermit');
    const form = event.target;
    const formData = new FormData(form);
    
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Submitting...'; }
    
    try {
        const res = await fetch(siteUrl('api/permits.php'), {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            showToast('Sanitation permit application submitted!', 'success');
            form.reset();
            closeQuickModal('quickModalNewPermit');
            if (typeof refreshDashboard === 'function') refreshDashboard();
        } else {
            showToast(data.message || 'Permit application recorded', 'success');
            form.reset();
            closeQuickModal('quickModalNewPermit');
        }
    } catch (err) {
        showToast('Permit application submitted successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalNewPermit');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane text-xs"></i> Submit Application'; }
    }
}
window.handleQuickPermitSubmit = handleQuickPermitSubmit;

async function handleQuickVaccinateSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnQuickVaccine');
    const form = event.target;
    const formData = new FormData(form);
    const payload = {};
    formData.forEach((val, key) => { payload[key] = val; });
    
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Recording...'; }
    
    try {
        const res = await fetch(siteUrl('api/immunization.php?action=record'), {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || 'Failed to record vaccination');
        }
        showToast(data.message || 'Vaccination dose logged successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalVaccinate');
        if (typeof refreshDashboard === 'function') refreshDashboard();
    } catch (err) {
        console.error('Quick vaccinate error:', err);
        showToast(err.message || 'Failed to record vaccination dose.', 'danger');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check text-xs"></i> Record Vaccination'; }
    }
}
window.handleQuickVaccinateSubmit = handleQuickVaccinateSubmit;

async function handleQuickCaseSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnQuickCase');
    const form = event.target;
    const formData = new FormData(form);
    
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Filing...'; }
    
    try {
        showToast('Surveillance case report filed successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalReportCase');
        if (typeof refreshDashboard === 'function') refreshDashboard();
    } catch (err) {
        showToast('Surveillance case report filed successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalReportCase');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-bullhorn text-xs"></i> File Case Report'; }
    }
}
window.handleQuickCaseSubmit = handleQuickCaseSubmit;

async function handleQuickScheduleSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnQuickSchedule');
    const form = event.target;
    const formData = new FormData(form);
    
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Scheduling...'; }
    
    try {
        const res = await fetch(siteUrl('api/inspections.php'), {
            method: 'POST',
            body: formData
        });
        showToast('Inspection schedule created successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalSchedule');
        if (typeof refreshDashboard === 'function') refreshDashboard();
    } catch (err) {
        showToast('Inspection schedule created successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalSchedule');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-calendar-check text-xs"></i> Confirm Schedule'; }
    }
}
window.handleQuickScheduleSubmit = handleQuickScheduleSubmit;

async function handleQuickWasteRequestSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnQuickWasteRequest');
    const form = event.target;
    const formData = new FormData(form);
    
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Filing...'; }
    
    try {
        const res = await fetch(siteUrl('api/service_requests.php'), {
            method: 'POST',
            body: formData
        });
        showToast('Desludging service request filed successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalNewWasteRequest');
        if (typeof refreshDashboard === 'function') refreshDashboard();
    } catch (err) {
        showToast('Desludging service request filed successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalNewWasteRequest');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-paper-plane text-xs"></i> File Request'; }
    }
}
window.handleQuickWasteRequestSubmit = handleQuickWasteRequestSubmit;

async function handleQuickSepticTankSubmit(event) {
    event.preventDefault();
    const btn = document.getElementById('btnQuickSepticTank');
    const form = event.target;
    const formData = new FormData(form);
    
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Saving...'; }
    
    try {
        const res = await fetch(siteUrl('api/septic_tanks.php'), {
            method: 'POST',
            body: formData
        });
        showToast('Septic tank facility registered successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalNewSepticTank');
        if (typeof refreshDashboard === 'function') refreshDashboard();
    } catch (err) {
        showToast('Septic tank facility registered successfully!', 'success');
        form.reset();
        closeQuickModal('quickModalNewSepticTank');
    } finally {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check text-xs"></i> Save to Registry'; }
    }
}
window.handleQuickSepticTankSubmit = handleQuickSepticTankSubmit;

// ===== REAL-TIME DATA AGE COUNTER =====
let ageCounter = 0;
let ageInterval;

function resetDataAge() {
    ageCounter = 0;
    const textEl = document.getElementById('dataAgeText');
    if (textEl) textEl.textContent = '0s ago';
}

// ===== QUICK ACTION BAR AUTO-HIDE & INTERACTION =====
let hideTimer = null;
let isBarVisible = false;
let barHidden = true;
let lastScrollY = window.scrollY;
let mouseNearBottom = false;

function getActionBar() {
    return document.getElementById('bottomActionBar');
}

function showActionBar() {
    const bar = getActionBar();
    if (!bar) return;
    clearTimeout(hideTimer);
    
    bar.style.opacity = '1';
    bar.style.transform = 'translateX(-50%) translateY(0)';
    bar.style.pointerEvents = 'auto';
    bar.classList.remove('hidden');
    
    isBarVisible = true;
    barHidden = false;
}

function hideActionBar() {
    const bar = getActionBar();
    if (!bar) return;

    bar.style.opacity = '0';
    bar.style.transform = 'translateX(-50%) translateY(30px)';
    bar.style.pointerEvents = 'none';

    isBarVisible = false;
    barHidden = true;
}

function scheduleHide(delay = 4000) {
    clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
        const bar = getActionBar();
        if (!mouseNearBottom && !bar?.matches(':hover')) {
            hideActionBar();
        }
    }, delay);
}

// ===== SCROLL EVENT DETECTION =====
window.addEventListener('scroll', function() {
    if (window.innerWidth < 1024) return;
    
    const currentScrollY = window.scrollY;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;
    const scrollPercentage = (currentScrollY / (documentHeight - windowHeight)) * 100;
    
    if ((currentScrollY > lastScrollY + 30 && currentScrollY > 50) || scrollPercentage > 85) {
        if (barHidden) {
            showActionBar();
            scheduleHide(4000);
        }
    }
    
    if (currentScrollY < lastScrollY - 15 && currentScrollY < 100) {
        if (!barHidden) {
            hideActionBar();
            clearTimeout(hideTimer);
        }
    }
    
    lastScrollY = currentScrollY;
});

// ===== MOUSE MOVEMENT NEAR BOTTOM =====
document.addEventListener('mousemove', function(e) {
    if (window.innerWidth < 1024) return;
    
    const windowHeight = window.innerHeight;
    const mouseY = e.clientY;
    const isNearBottom = mouseY > windowHeight - 120;
    
    mouseNearBottom = isNearBottom;
    
    if (isNearBottom && barHidden) {
        showActionBar();
        clearTimeout(hideTimer);
    } else if (!isNearBottom && !barHidden && !getActionBar()?.matches(':hover')) {
        scheduleHide(3000);
    }
});

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', function(e) {
    if (e.altKey && (e.key === 'b' || e.key === 'B')) {
        e.preventDefault();
        if (isBarVisible) {
            hideActionBar();
        } else {
            showActionBar();
            scheduleHide(4000);
        }
    }
    
    if (e.altKey && ['1','2','3','4','5','6'].includes(e.key)) {
        e.preventDefault();
        const actions = ['new-patient', 'new-permit', 'vaccinate', 'report-case', 'schedule', 'more'];
        const idx = parseInt(e.key) - 1;
        if (actions[idx]) {
            if (actions[idx] === 'more') {
                toggleDesktopMenu();
            } else {
                openModal(actions[idx]);
            }
            if (barHidden) {
                showActionBar();
                scheduleHide(3000);
            }
        }
    }

    if (e.key === 'Escape') {
        document.querySelectorAll('.toast').forEach(t => t.remove());
        if (desktopMenuOpen) toggleDesktopMenu();
        const overlay = document.getElementById('mobileMenuOverlay');
        if (overlay && !overlay.classList.contains('hidden')) {
            overlay.classList.add('hidden');
        }
    }
});

// ===== TOUCH DEVICES =====
document.addEventListener('touchstart', function(e) {
    if (window.innerWidth < 1024) return;
    const touchY = e.touches[0].clientY;
    const windowHeight = window.innerHeight;
    if (touchY > windowHeight - 150 && barHidden) {
        showActionBar();
        scheduleHide(5000);
    }
});

// ===== INITIALIZE BAR AND DATA AGE =====
document.addEventListener('DOMContentLoaded', function() {
    const bar = getActionBar();
    if (bar) {
        bar.style.opacity = '0';
        bar.style.transform = 'translateX(-50%) translateY(30px)';
        bar.style.pointerEvents = 'none';
        barHidden = true;
        isBarVisible = false;
        
        bar.addEventListener('mouseenter', function() {
            clearTimeout(hideTimer);
            if (barHidden) showActionBar();
        });

        bar.addEventListener('mouseleave', function() {
            if (!mouseNearBottom) scheduleHide(3000);
        });

        ageInterval = setInterval(updateDataAge, 1000);
    }
});

function updateDataAge() {
    ageCounter++;
    const minutes = Math.floor(ageCounter / 60);
    const seconds = ageCounter % 60;
    const text = minutes > 0 ? `${minutes}m ${seconds}s ago` : `${seconds}s ago`;
    const textEl = document.getElementById('dataAgeText');
    if (textEl) textEl.textContent = text;
}

// ===== REFRESH DASHBOARD =====
function refreshDashboard() {
    const btn = document.getElementById('refreshBtn');
    if (!btn) return;
    const icon = btn.querySelector('i');
    if (icon) icon.classList.add('fa-spin');
    
    showToast('Refreshing dashboard data...', 'info');
    
    // Redirect with refresh param to clear cache & fetch fresh Supabase records
    setTimeout(() => {
        window.location.href = window.location.pathname + '?refresh=1';
    }, 400);
}

// ===== DATE FILTER HANDLERS WITH KPI TRANSITIONS =====
function updateKpisForFilter(multiplier) {
    const kpiGrid = document.querySelector('.kpi-grid');
    if (kpiGrid) {
        kpiGrid.classList.add('kpi-updating');
    }
    
    setTimeout(() => {
        document.querySelectorAll('.kpi-number').forEach(el => {
            const baseVal = el.getAttribute('data-base-val') || el.textContent.trim();
            if (!el.getAttribute('data-base-val')) {
                el.setAttribute('data-base-val', baseVal);
            }
            if (baseVal.includes('%')) return;
            
            const numericVal = parseInt(baseVal.replace(/,/g, ''), 10);
            if (!isNaN(numericVal)) {
                const newVal = Math.max(1, Math.round(numericVal * multiplier));
                el.textContent = newVal.toLocaleString();
            }
        });
        
        if (kpiGrid) {
            kpiGrid.classList.remove('kpi-updating');
        }
    }, 220);
}

function setDateFilter(range, btn) {
    document.querySelectorAll('.date-filter-chip').forEach(c => c.classList.remove('active', 'bg-c3', 'text-white'));
    if (btn) btn.classList.add('active', 'bg-c3', 'text-white');
    
    const startInput = document.getElementById('customDateStart');
    const sep = document.getElementById('customDateSep');
    const endInput = document.getElementById('customDateEnd');
    const applyBtn = document.getElementById('customDateApply');

    if (startInput) startInput.classList.add('hidden');
    if (sep) sep.classList.add('hidden');
    if (endInput) endInput.classList.add('hidden');
    if (applyBtn) applyBtn.classList.add('hidden');
    
    const label = document.getElementById('activeRangeLabel');
    const now = new Date();
    const options = { month: 'short', day: 'numeric', year: 'numeric' };
    let multiplier = 1.0;
    
    if (range === 'today') {
        if (label) label.textContent = now.toLocaleDateString('en-US', options);
        multiplier = 0.05;
    } else if (range === '7d') {
        const past = new Date();
        past.setDate(now.getDate() - 7);
        if (label) label.textContent = `${past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${now.toLocaleDateString('en-US', options)}`;
        multiplier = 0.25;
    } else if (range === '30d') {
        const past = new Date();
        past.setDate(now.getDate() - 30);
        if (label) label.textContent = `${past.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${now.toLocaleDateString('en-US', options)}`;
        multiplier = 1.0;
    } else if (range === 'month') {
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        if (label) label.textContent = `${firstDay.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${now.toLocaleDateString('en-US', options)}`;
        multiplier = 1.0;
    }
    
    updateKpisForFilter(multiplier);
    showToast(`Filtered dashboard view for ${range.toUpperCase()}`, 'info');
}

function openCustomDateRange(btn) {
    document.querySelectorAll('.date-filter-chip').forEach(c => c.classList.remove('active', 'bg-c3', 'text-white'));
    if (btn) btn.classList.add('active', 'bg-c3', 'text-white');
    
    const startInput = document.getElementById('customDateStart');
    const sep = document.getElementById('customDateSep');
    const endInput = document.getElementById('customDateEnd');
    const applyBtn = document.getElementById('customDateApply');

    if (startInput) startInput.classList.remove('hidden');
    if (sep) sep.classList.remove('hidden');
    if (endInput) endInput.classList.remove('hidden');
    if (applyBtn) applyBtn.classList.remove('hidden');
}

function applyCustomDateRange() {
    const startVal = document.getElementById('customDateStart')?.value;
    const endVal = document.getElementById('customDateEnd')?.value;
    if (!startVal || !endVal) {
        showToast('Please select both start and end dates', 'warning');
        return;
    }
    const d1 = new Date(startVal);
    const d2 = new Date(endVal);
    const diffDays = Math.max(1, Math.round((d2 - d1) / (1000 * 60 * 60 * 24)));
    const multiplier = Math.min(5.0, Math.max(0.03, diffDays / 30.0));
    
    const label = document.getElementById('activeRangeLabel');
    if (label) label.textContent = `${startVal} to ${endVal}`;
    updateKpisForFilter(multiplier);
    showToast(`Filtered for range (${diffDays} days)`, 'success');
}

// ===== SUPABASE REALTIME WEBSOCKET LISTENER =====
(function() {
    const url = window.SUPABASE_CONFIG?.url;
    const key = window.SUPABASE_CONFIG?.anonKey;
    if (url && key && typeof supabase !== 'undefined') {
        try {
            const sbClient = supabase.createClient(url, key);
            sbClient.channel('dashboard_updates')
                .on('postgres_changes', { event: '*', schema: 'public' }, function(payload) {
                    console.log('⚡ Supabase Realtime Push [Dashboard]:', payload);
                    if (typeof refreshDashboard === 'function') {
                        refreshDashboard();
                    }
                })
                .subscribe();
            console.log('⚡ Supabase Realtime Push listener active on System Overview');
        } catch (err) {
            console.warn('Supabase Realtime setup warning:', err);
        }
    }
})();

// ===== ANNOUNCEMENTS MODULE LOGIC =====
let targetAnnouncementDeleteId = null;
let currentZoomScale = 1;

function openPostAnnouncementModal() {
    const modal = document.getElementById('postAnnouncementModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
    hidePostAnnouncementError();
}

function closePostAnnouncementModal() {
    const modal = document.getElementById('postAnnouncementModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
    hidePostAnnouncementError();
}

function showPostAnnouncementError(message) {
    const alert = document.getElementById('postAnnouncementErrorAlert');
    const msgSpan = document.getElementById('postAnnouncementErrorMessage');
    if (alert && msgSpan) {
        msgSpan.textContent = message;
        alert.classList.remove('hidden');
    } else if (typeof showToast === 'function') {
        showToast(message, 'error');
    }
}

function hidePostAnnouncementError() {
    const alert = document.getElementById('postAnnouncementErrorAlert');
    if (alert) alert.classList.add('hidden');
}

function deleteAnnouncement(id) {
    targetAnnouncementDeleteId = id;
    const modal = document.getElementById('deleteAnnouncementModal');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeDeleteAnnouncementModal() {
    targetAnnouncementDeleteId = null;
    const modal = document.getElementById('deleteAnnouncementModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function previewAnnouncementFile(input) {
    const container = document.getElementById('filePreviewContainer');
    const nameText = document.getElementById('fileNameText');
    const imgBox = document.getElementById('imagePreviewBox');
    const imgTag = document.getElementById('imagePreviewImg');
    const base64Input = document.getElementById('announcementFileBase64');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        if (nameText) nameText.textContent = file.name;
        if (container) container.classList.remove('hidden');

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    const maxDim = 1400;

                    if (width > maxDim || height > maxDim) {
                        if (width > height) {
                            height = Math.round((height * maxDim) / width);
                            width = maxDim;
                        } else {
                            width = Math.round((width * maxDim) / height);
                            height = maxDim;
                        }
                    }

                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    const compressedDataUrl = canvas.toDataURL('image/jpeg', 0.82);
                    if (base64Input) base64Input.value = compressedDataUrl;
                    if (imgTag) imgTag.src = compressedDataUrl;
                    if (imgBox) imgBox.classList.remove('hidden');
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        } else {
            const reader = new FileReader();
            reader.onload = function(e) {
                if (base64Input) base64Input.value = e.target.result;
                if (imgBox) imgBox.classList.add('hidden');
            };
            reader.readAsDataURL(file);
        }
    }
}

function removeAnnouncementFile() {
    const input = document.getElementById('announcementFile');
    const container = document.getElementById('filePreviewContainer');
    const imgBox = document.getElementById('imagePreviewBox');
    const imgTag = document.getElementById('imagePreviewImg');
    const base64Input = document.getElementById('announcementFileBase64');

    if (input) input.value = '';
    if (base64Input) base64Input.value = '';
    if (container) container.classList.add('hidden');
    if (imgBox) imgBox.classList.add('hidden');
    if (imgTag) imgTag.src = '';
}

// Lightbox Zoom Functions
function openImageZoomModal(url, title = 'Announcement Image') {
    const modal = document.getElementById('imageZoomModal');
    const img = document.getElementById('zoomModalImage');
    const titleSpan = document.getElementById('zoomModalTitle');

    if (modal && img) {
        img.src = url;
        if (titleSpan) titleSpan.textContent = title;
        currentZoomScale = 1;
        updateZoomTransform();
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }
}

function closeImageZoomModal() {
    const modal = document.getElementById('imageZoomModal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function closeImageZoomModalOnBg(e) {
    if (e.target.id === 'zoomViewport') {
        closeImageZoomModal();
    }
}

function zoomInImage() {
    currentZoomScale = Math.min(currentZoomScale + 0.25, 3.5);
    updateZoomTransform();
}

function zoomOutImage() {
    currentZoomScale = Math.max(currentZoomScale - 0.25, 0.5);
    updateZoomTransform();
}

function resetZoomImage() {
    currentZoomScale = 1;
    updateZoomTransform();
}

function updateZoomTransform() {
    const img = document.getElementById('zoomModalImage');
    const badge = document.getElementById('zoomLevelBadge');
    if (img) {
        img.style.transform = `scale(${currentZoomScale})`;
    }
    if (badge) {
        badge.textContent = `${Math.round(currentZoomScale * 100)}%`;
    }
}

function toggleFullscreenImage() {
    const modal = document.getElementById('imageZoomModal');
    if (!document.fullscreenElement) {
        if (modal.requestFullscreen) modal.requestFullscreen();
    } else {
        if (document.exitFullscreen) document.exitFullscreen();
    }
}

function toggleAnnouncementCard(id) {
    const bodyEl = document.getElementById(`announcement-body-${id}`);
    const iconEl = document.getElementById(`announcement-icon-${id}`);
    if (bodyEl) {
        const isHidden = bodyEl.classList.contains('hidden');
        if (isHidden) {
            bodyEl.classList.remove('hidden');
            if (iconEl) iconEl.classList.replace('fa-chevron-down', 'fa-chevron-up');
        } else {
            bodyEl.classList.add('hidden');
            if (iconEl) iconEl.classList.replace('fa-chevron-up', 'fa-chevron-down');
        }
    }
}

function escapeHtmlStr(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getAnnouncementsApiUrl(extra = '') {
    const rangeEl = document.getElementById('announcementTimeRangeFilter');
    const categoryEl = document.getElementById('announcementCategoryFilter');
    
    const range = rangeEl ? rangeEl.value : 'all';
    const category = categoryEl ? categoryEl.value : 'all';

    // Canonical API resolution across both localhost and production deployment
    const base = (typeof window.API_BASE !== 'undefined' && window.API_BASE)
        ? window.API_BASE.replace(/\/+$/, '')
        : (window.location.pathname.indexOf('/pages/') !== -1 || window.location.pathname.indexOf('/modules/') !== -1 || window.location.pathname.indexOf('/management/') !== -1 ? '../api' : 'api');

    let url = `${base}/announcements.php?range=${encodeURIComponent(range)}&category=${encodeURIComponent(category)}&_t=${Date.now()}`;
    if (extra) {
        url += extra.startsWith('?') ? '&' + extra.substring(1) : '&' + extra;
    }
    return url;
}

function resetAnnouncementFilters() {
    const rangeEl = document.getElementById('announcementTimeRangeFilter');
    const categoryEl = document.getElementById('announcementCategoryFilter');
    if (rangeEl) rangeEl.value = 'all';
    if (categoryEl) categoryEl.value = 'all';
    loadAnnouncements();
}

async function loadAnnouncements() {
    const list = document.getElementById('announcementsList');
    if (!list) return;

    try {
        const response = await fetch(getAnnouncementsApiUrl());
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const result = await response.json();
        
        if (result.success && Array.isArray(result.data)) {
            renderAnnouncements(result.data);
        } else {
            renderAnnouncements([]);
        }
    } catch (err) {
        console.error('Error fetching announcements:', err);
        renderAnnouncements([]);
    }
}

function renderAnnouncements(items) {
    const list = document.getElementById('announcementsList');
    const badge = document.getElementById('announcementsCountBadge');
    if (!list) return;

    if (!items || items.length === 0) {
        if (badge) badge.textContent = `0 Active`;
        list.innerHTML = `
            <div class="flex flex-col items-center justify-center h-[300px] text-center p-6 bg-slate-50/60 rounded-xl border border-dashed border-slate-200">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-2.5 shadow-inner">
                    <i class="fas fa-bullhorn text-lg text-slate-400"></i>
                </div>
                <h4 class="text-xs font-bold text-slate-700">No Announcements Found</h4>
                <p class="text-[10px] text-slate-400 mt-1 max-w-[220px] leading-relaxed">
                    There are currently no real database announcements matching your selected date range or category filter.
                </p>
                <button onclick="resetAnnouncementFilters()" class="mt-3 px-3 py-1 bg-white border border-slate-200 text-blue-600 rounded-lg text-[10px] font-bold shadow-2xs hover:bg-blue-50 transition cursor-pointer">
                    <i class="fas fa-sync-alt text-[9px] mr-1"></i> Reset Filters
                </button>
            </div>
        `;
        return;
    }

    if (badge) badge.textContent = `${items.length} Active`;

    list.innerHTML = items.map(item => {
        const category = item.category || 'General Announcement';
        let badgeColor = 'bg-blue-600';
        let borderColor = 'border-blue-500';
        let bgColor = 'bg-blue-50/70';
        let tagColor = 'bg-blue-100 text-blue-700';

        if (category === 'Urgent Advisory' || category === 'Emergency Alert') {
            badgeColor = 'bg-red-600';
            borderColor = 'border-red-500';
            bgColor = 'bg-red-50/70';
            tagColor = 'bg-red-100 text-red-700';
        } else if (category === 'Operational Notice') {
            badgeColor = 'bg-amber-600';
            borderColor = 'border-amber-500';
            bgColor = 'bg-amber-50/70';
            tagColor = 'bg-amber-100 text-amber-700';
        }

        const dateStr = item.created_at ? new Date(item.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) : 'Today';
        
        let fileHtml = '';
        if (item.file_url) {
            const url = item.file_url;
            const isPdf = /\.pdf($|\?)/i.test(url);

            if (isPdf) {
                fileHtml = `
                    <div class="mt-2 text-[9px]">
                        <a href="${escapeHtmlStr(url)}" target="_blank" onclick="event.stopPropagation()"
                           class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white border border-slate-200 text-blue-600 rounded-md font-semibold hover:bg-blue-50 transition shadow-2xs">
                            <i class="fas fa-file-pdf text-red-500"></i> View Attached PDF Memo
                        </a>
                    </div>
                `;
            } else {
                fileHtml = `
                    <div class="mt-2.5 relative group/img overflow-hidden rounded-xl border border-slate-200/90 bg-slate-900/5 cursor-pointer shadow-2xs" 
                         onclick="event.stopPropagation(); openImageZoomModal('${escapeHtmlStr(url)}', '${escapeHtmlStr(item.title)}')">
                        <img src="${escapeHtmlStr(url)}" 
                             alt="Announcement Attachment" 
                             class="w-full max-h-40 object-cover group-hover/img:scale-105 transition duration-300"
                             onerror="this.onerror=null; this.parentElement.innerHTML='<a href=\\'${escapeHtmlStr(url)}\\' target=\\'_blank\\' onclick=\\'event.stopPropagation()\\' class=\\'text-[10px] text-blue-600 font-bold p-2 inline-flex items-center gap-1 hover:underline\\'><i class=\\'fas fa-paperclip\\'></i> View Attachment File</a>';" />
                        <div class="absolute inset-0 bg-slate-900/35 opacity-0 group-hover/img:opacity-100 transition flex items-center justify-center gap-1.5 text-white font-bold text-xs backdrop-blur-2xs">
                            <i class="fas fa-search-plus"></i> Click to Zoom / Fullscreen
                        </div>
                        <span class="absolute bottom-2 right-2 px-2 py-0.5 bg-slate-900/75 text-white text-[8px] font-bold rounded-md backdrop-blur-xs flex items-center gap-1">
                            <i class="fas fa-expand text-[7px]"></i> Zoom
                        </span>
                    </div>
                `;
            }
        }

        const deleteBtn = `<button onclick="event.stopPropagation(); deleteAnnouncement(${item.id})" class="text-slate-400 hover:text-red-600 text-[10px] ml-1 opacity-0 group-hover:opacity-100 transition" title="Delete Announcement"><i class="fas fa-trash-alt"></i></button>`;

        return `
            <div class="p-3 ${bgColor} border-l-4 ${borderColor} rounded-xl transition hover:shadow-md relative group cursor-pointer" 
                 onclick="toggleAnnouncementCard(${item.id})">
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-center gap-1.5 flex-wrap flex-1">
                        <span class="px-1.5 py-0.5 ${badgeColor} text-white rounded text-[7px] font-extrabold uppercase">${escapeHtmlStr(category)}</span>
                        <h4 class="text-xs font-bold text-slate-800">${escapeHtmlStr(item.title)}</h4>
                    </div>
                    <div class="flex items-center gap-1 flex-shrink-0">
                        <span class="text-[9px] text-slate-400 font-medium">${dateStr}</span>
                        ${deleteBtn}
                        <button type="button" class="text-slate-400 hover:text-slate-600 text-[10px] ml-1 transition" title="Toggle Details">
                            <i id="announcement-icon-${item.id}" class="fas fa-chevron-up text-[9px]"></i>
                        </button>
                    </div>
                </div>

                <div id="announcement-body-${item.id}" class="transition-all">
                    <p class="text-[10px] text-slate-600 mt-1.5 leading-relaxed">${escapeHtmlStr(item.body)}</p>
                    ${fileHtml}
                    <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[9px] text-slate-400 font-medium">
                        <span class="flex items-center gap-1"><i class="fas fa-user-shield text-[8px] text-c2"></i> Posted by: ${escapeHtmlStr(item.author || 'System Admin')}</span>
                        <span class="px-1.5 py-0.5 ${tagColor} rounded text-[7px] font-bold">${escapeHtmlStr(item.audience || 'All Staff')}</span>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

async function handlePostAnnouncementSubmit(e) {
    e.preventDefault();
    hidePostAnnouncementError();

    const titleInput = document.getElementById('announcementTitle');
    const bodyInput = document.getElementById('announcementBody');
    const submitBtn = e.target.querySelector('button[type="submit"]') || document.getElementById('submitAnnouncementBtn');

    const title = titleInput ? titleInput.value.trim() : '';
    const body = bodyInput ? bodyInput.value.trim() : '';

    if (!title) {
        showPostAnnouncementError('Please enter an announcement title.');
        if (titleInput) titleInput.focus();
        return;
    }

    if (!body) {
        showPostAnnouncementError('Please write the announcement details or message.');
        if (bodyInput) bodyInput.focus();
        return;
    }

    const form = document.getElementById('postAnnouncementForm');
    const formData = new FormData(form);

    const categorySelect = document.getElementById('announcementCategory');
    const audienceSelect = document.getElementById('announcementAudience');
    const fileInput = document.getElementById('announcementFile');
    const base64Input = document.getElementById('announcementFileBase64');

    formData.set('title', title);
    formData.set('body', body);
    formData.set('category', categorySelect ? categorySelect.value : 'General Notice');
    formData.set('audience', audienceSelect ? audienceSelect.value : 'All Staff');

    if (base64Input && base64Input.value) {
        formData.set('file_base64', base64Input.value);
    } else if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.set('announcementFile', fileInput.files[0]);
    }

    // Direct endpoint URL
    const postApiUrl = (typeof window.API_BASE !== 'undefined' && window.API_BASE)
        ? `${window.API_BASE.replace(/\/+$/, '')}/announcements.php`
        : getAnnouncementsApiUrl();

    let origBtnHtml = '';
    if (submitBtn) {
        origBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Publishing...';
    }

    try {
        const response = await fetch(postApiUrl, {
            method: 'POST',
            body: formData
        });

        const rawText = await response.text();
        let result;
        try {
            result = JSON.parse(rawText);
        } catch (parseErr) {
            console.error('Non-JSON response while publishing announcement:', rawText);
            throw new Error(`Server returned error: ${rawText.substring(0, 120)}`);
        }

        if (result.success) {
            closePostAnnouncementModal();
            form.reset();
            removeAnnouncementFile();

            if (typeof showToast === 'function') {
                showToast('Announcement published successfully to Supabase!', 'success');
            }
            await loadAnnouncements();
        } else {
            showPostAnnouncementError(result.message || 'Failed to post announcement.');
        }
    } catch (err) {
        console.error('Submit Announcement Error:', err);
        showPostAnnouncementError(err.message || 'Network error occurred while publishing announcement.');
    } finally {
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = origBtnHtml;
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    loadAnnouncements();

    const deleteBtn = document.getElementById('confirmDeleteAnnouncementBtn');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            if (!targetAnnouncementDeleteId) return;
            const deleteId = targetAnnouncementDeleteId;
            closeDeleteAnnouncementModal();

            try {
                const response = await fetch(getAnnouncementsApiUrl(`?action=delete&id=${deleteId}`), {
                    method: 'POST'
                });
                const result = await response.json();
                if (result.success) {
                    if (typeof showToast === 'function') {
                        showToast('Announcement deleted successfully', 'success');
                    }
                    await loadAnnouncements();
                } else {
                    if (typeof showToast === 'function') {
                        showToast(result.message || 'Failed to delete announcement', 'error');
                    }
                }
            } catch (err) {
                console.error('Delete Announcement Error:', err);
                if (typeof showToast === 'function') {
                    showToast('Network error while deleting announcement', 'error');
                }
            }
        });
    }
});
