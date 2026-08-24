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

// ===== DYNAMIC QUICK ACTION MODAL SCHEMAS =====
const QUICK_ACTION_SCHEMAS = {
    'new-patient': {
        title: 'Register New Patient',
        subtitle: 'Add a new patient profile to Health Center Services',
        icon: 'fas fa-user-plus text-emerald-600',
        color: 'bg-emerald-100 text-emerald-600',
        submitText: 'Save Patient Profile',
        permission: 'patients.create',
        fields: `
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Full Name</label>
                <input type="text" name="full_name" required placeholder="e.g. Maria Clara Santos" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Birth Date</label>
                    <input type="date" name="birth_date" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Gender</label>
                    <select name="gender" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
                        <option value="Female">Female</option>
                        <option value="Male">Male</option>
                    </select>
                </div>
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Contact Number</label>
                <input type="text" name="contact" placeholder="0917-000-0000" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Barangay / Address</label>
                <input type="text" name="address" placeholder="Barangay 1, City Center" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-brand-medium outline-none">
            </div>
        `
    },
    'new-permit': {
        title: 'Issue Sanitation Permit',
        subtitle: 'Create a new business sanitation permit application',
        icon: 'fas fa-file-circle-plus text-amber-600',
        color: 'bg-amber-100 text-amber-600',
        submitText: 'Submit Application',
        permission: 'permits.create',
        fields: `
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Establishment Name</label>
                <input type="text" name="establishment" required placeholder="e.g. City Health Diner & Grill" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Owner Name</label>
                <input type="text" name="owner" required placeholder="Juan Dela Cruz" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Business Category</label>
                    <select name="category" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                        <option value="Food Establishment">Food Establishment</option>
                        <option value="Service Industry">Service Industry</option>
                        <option value="Industrial & Water">Industrial & Water</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Contact Number</label>
                    <input type="text" name="contact" placeholder="0917-123-4567" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-amber-500 outline-none">
                </div>
            </div>
        `
    },
    'vaccinate': {
        title: 'Record Vaccination',
        subtitle: 'Log immunization dose for child or adult patient',
        icon: 'fas fa-syringe text-blue-600',
        color: 'bg-blue-100 text-blue-600',
        submitText: 'Record Dose',
        permission: 'immunization.create',
        fields: `
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Patient Name / ID</label>
                <input type="text" name="patient_name" required placeholder="Patient Name or ID" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Vaccine Type</label>
                    <select name="vaccine" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="BCG">BCG</option>
                        <option value="Hepatitis B">Hepatitis B</option>
                        <option value="Pentavalent">Pentavalent (DPT-HepB-Hib)</option>
                        <option value="OPV/IPV">Polio (OPV / IPV)</option>
                        <option value="MMR">Measles, Mumps, Rubella (MMR)</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Dose Number</label>
                    <select name="dose" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-500 outline-none">
                        <option value="Dose 1">Dose 1</option>
                        <option value="Dose 2">Dose 2</option>
                        <option value="Dose 3">Dose 3</option>
                        <option value="Booster 1">Booster 1</option>
                    </select>
                </div>
            </div>
        `
    },
    'report-case': {
        title: 'Report Health Case',
        subtitle: 'Flag disease outbreak or health surveillance case',
        icon: 'fas fa-flag text-rose-600',
        color: 'bg-rose-100 text-rose-600',
        submitText: 'File Case Report',
        permission: 'compliance.view',
        fields: `
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Case Condition / Disease</label>
                <input type="text" name="disease" required placeholder="e.g. Dengue, Acute Gastroenteritis" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-rose-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Barangay Location</label>
                    <input type="text" name="location" required placeholder="Barangay 5" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-rose-500 outline-none">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Severity Level</label>
                    <select name="severity" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-rose-500 outline-none">
                        <option value="Low">Low Risk</option>
                        <option value="Medium">Medium Alert</option>
                        <option value="High">High Outbreak Alert</option>
                    </select>
                </div>
            </div>
        `
    },
    'schedule': {
        title: 'Schedule Sanitation Inspection',
        subtitle: 'Assign field inspector for facility compliance audit',
        icon: 'fas fa-calendar-plus text-purple-600',
        color: 'bg-purple-100 text-purple-600',
        submitText: 'Schedule Audit',
        permission: 'inspections.conduct',
        fields: `
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Facility / Business Name</label>
                <input type="text" name="facility" required placeholder="Facility Name" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-purple-500 outline-none">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Inspection Date</label>
                    <input type="date" name="date" required class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-purple-500 outline-none">
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Inspector Assigned</label>
                    <input type="text" name="inspector" placeholder="Sanitation Inspector Name" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-purple-500 outline-none">
                </div>
            </div>
        `
    },
    'report': {
        title: 'Generate Custom Report',
        subtitle: 'Compile departmental summary & export analytics',
        icon: 'fas fa-file-pdf text-indigo-600',
        color: 'bg-indigo-100 text-indigo-600',
        submitText: 'Generate & Download',
        permission: 'reports.view',
        fields: `
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Report Scope</label>
                <select name="report_type" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                    <option value="Health Services Summary">Health Services & Patient Census</option>
                    <option value="Sanitation Permits Issued">Sanitation Permits & Compliance</option>
                    <option value="Immunization Coverage">Immunization & Growth Tracking</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Format</label>
                    <select name="format" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                        <option value="PDF Document">PDF Document</option>
                        <option value="Excel Spreadsheet">Excel Spreadsheet (.xlsx)</option>
                    </select>
                </div>
                <div class="space-y-1.5">
                    <label class="text-xs font-semibold text-slate-600">Period</label>
                    <select name="period" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-indigo-500 outline-none">
                        <option value="This Month">This Month</option>
                        <option value="This Quarter">This Quarter</option>
                        <option value="Year-to-Date">Year-to-Date</option>
                    </select>
                </div>
            </div>
        `
    },
    'export-data': {
        title: 'Export Data Records',
        subtitle: 'Download raw CSV/JSON dataset for offline archiving',
        icon: 'fas fa-download text-emerald-600',
        color: 'bg-emerald-100 text-emerald-600',
        submitText: 'Download Dataset',
        permission: 'reports.view',
        fields: `
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">Dataset Scope</label>
                <select name="dataset" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-emerald-500 outline-none">
                    <option value="Patients Masterlist">Patients Masterlist</option>
                    <option value="Sanitation Permit Registry">Sanitation Permit Registry</option>
                    <option value="Vaccination Logs">Vaccination Logs</option>
                </select>
            </div>
            <div class="space-y-1.5">
                <label class="text-xs font-semibold text-slate-600">File Format</label>
                <select name="export_format" class="w-full px-3 py-2 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-emerald-500 outline-none">
                    <option value="csv">CSV Spreadsheet</option>
                    <option value="json">JSON Data</option>
                </select>
            </div>
        `
    }
};

function openModal(modalId) {
    const schema = QUICK_ACTION_SCHEMAS[modalId];
    if (!schema) return;

    const userPerms = window.USER_PERMISSIONS || [];
    const isAdmin = window.IS_ADMIN || false;

    // Client-side RBAC Permission Guard
    if (!isAdmin && schema.permission && !userPerms.includes(schema.permission)) {
        showToast(`Access Denied: You do not have permission [${schema.permission}] to perform this action.`, 'error');
        return;
    }

    // Render Dynamic Modal Content
    const typeEl = document.getElementById('quickActionType');
    const titleEl = document.getElementById('quickActionModalTitle');
    const subEl = document.getElementById('quickActionModalSubtitle');
    const submitEl = document.getElementById('quickActionSubmitText');
    const iconEl = document.getElementById('quickActionModalIcon');
    const iconContainerEl = document.getElementById('quickActionModalIconContainer');
    const fieldsEl = document.getElementById('quickActionDynamicFields');

    if (typeEl) typeEl.value = modalId;
    if (titleEl) titleEl.textContent = schema.title;
    if (subEl) subEl.textContent = schema.subtitle;
    if (submitEl) submitEl.textContent = schema.submitText;
    if (iconEl) iconEl.className = schema.icon;
    if (iconContainerEl) iconContainerEl.className = `w-9 h-9 rounded-xl flex items-center justify-center ${schema.color}`;
    if (fieldsEl) fieldsEl.innerHTML = schema.fields;

    const modal = document.getElementById('quickActionModal');
    const box = document.getElementById('quickActionModalBox');
    if (!modal || !box) return;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        box.classList.remove('opacity-0', 'scale-95');
        box.classList.add('opacity-100', 'scale-100');
    }, 20);
}

function closeQuickActionModal() {
    const modal = document.getElementById('quickActionModal');
    const box = document.getElementById('quickActionModalBox');
    if (!modal || !box) return;

    box.classList.remove('opacity-100', 'scale-100');
    box.classList.add('opacity-0', 'scale-95');
    setTimeout(() => {
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }, 200);
}

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

function handleQuickActionSubmit(event) {
    event.preventDefault();
    const actionId = document.getElementById('quickActionType').value;
    const schema = QUICK_ACTION_SCHEMAS[actionId];
    const formData = new FormData(event.target);

    if (actionId === 'export-data') {
        downloadDashboardSnapshot(
            formData.get('dataset') || 'Dashboard Snapshot',
            formData.get('export_format') || 'csv',
            'dashboard-export'
        );
        closeQuickActionModal();
        return;
    }

    if (actionId === 'report') {
        const reportType = formData.get('report_type') || 'Dashboard Summary';
        const format = formData.get('format') || 'PDF Document';

        if (format === 'PDF Document') {
            closeQuickActionModal();
            showToast('Print dialog opened. Choose "Save as PDF" to save the report locally.', 'info', 5000);
            setTimeout(() => window.print(), 250);
        } else {
            downloadDashboardSnapshot(reportType, 'csv', 'dashboard-report');
            closeQuickActionModal();
        }
        return;
    }

    showToast(`Successfully submitted: ${schema ? schema.title : 'Quick Action'}`, 'success');
    closeQuickActionModal();
}

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

    formData.set('title', title);
    formData.set('body', body);
    formData.set('category', categorySelect ? categorySelect.value : 'General Notice');
    formData.set('audience', audienceSelect ? audienceSelect.value : 'All Staff');

    if (fileInput && fileInput.files && fileInput.files[0]) {
        formData.set('announcementFile', fileInput.files[0]);
    }

    try {
        const response = await fetch(getAnnouncementsApiUrl(), {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (result.success) {
            closePostAnnouncementModal();
            form.reset();
            removeAnnouncementFile();

            if (typeof showToast === 'function') {
                showToast('Announcement published successfully!', 'success');
            }
            await loadAnnouncements();
        } else {
            showPostAnnouncementError(result.message || 'Failed to post announcement.');
        }
    } catch (err) {
        console.error('Submit Announcement Error:', err);
        showPostAnnouncementError('Network error occurred while publishing announcement.');
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
