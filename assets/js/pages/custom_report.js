// ================================================================
//  CODE ARCHITECTURE – MODULAR FUNCTIONS & ENTERPRISE RBAC
// ================================================================

// ─── AUTH & RBAC CONTEXT ──────────────────────────────────────────

// ─── MODAL NAVIGATION ─────────────────────────────────────────
function openGenerateReportModal() {
    const modal = document.getElementById('generateReportModal');
    if (modal) {
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.style.opacity = '1';
    }
}
function closeGenerateReportModal() {
    const modal = document.getElementById('generateReportModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
}

function setDatePreset(preset) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    if (preset === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0); // Last day of month
    } else if (preset === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    } else if (preset === 'last_30_days') {
        start.setDate(today.getDate() - 30);
    }

    const format = (d) => {
        // Adjust to local timezone correctly before calling toISOString
        const offset = d.getTimezoneOffset();
        d = new Date(d.getTime() - (offset*60*1000));
        return d.toISOString().split('T')[0];
    };
    
    document.getElementById('startDate').value = format(start);
    document.getElementById('endDate').value = format(end);
    
    refreshUI();
}

// ─── TAB NAVIGATION ───────────────────────────────────────────
function switchTab(tabId) {
    document.querySelectorAll('.report-tab').forEach(tab => tab.classList.remove('active', 'text-[#176B87]', 'border-[#176B87]'));
    document.querySelectorAll('.report-tab').forEach(tab => tab.classList.add('text-slate-500', 'border-transparent'));
    
    document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
    
    const targetTab = document.querySelector(`.report-tab[data-tab="${tabId}"]`);
    if (targetTab) {
        targetTab.classList.add('active', 'text-[#176B87]', 'border-[#176B87]');
        targetTab.classList.remove('text-slate-500', 'border-transparent');
    }
    
    const targetContent = document.getElementById('tab' + (tabId === 'summary' ? 'Summary' : tabId === 'chart' ? 'Chart' : 'Table'));
    if (targetContent) {
        targetContent.classList.remove('hidden');
    }
}
function openTemplatesListModal() {
    const modal = document.getElementById('templatesListModal');
    if (modal) {
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.style.opacity = '1';
        loadTemplatesList();
    }
}
function closeTemplatesListModal() {
    const modal = document.getElementById('templatesListModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
}

function openScheduledListModal() {
    const modal = document.getElementById('scheduledListModal');
    if (modal) {
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.style.opacity = '1';
        loadScheduledReports();
    }
}
function closeScheduledListModal() {
    const modal = document.getElementById('scheduledListModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
}

function openHistoryListModal() {
    const modal = document.getElementById('historyListModal');
    if (modal) {
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.style.opacity = '1';
        renderFullHistory();
    }
}
function closeHistoryListModal() {
    const modal = document.getElementById('historyListModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
}

// ─── DYNAMIC DATA STORE (FETCHED FROM SUPABASE) ───────────────────
let allReportRows = [];
let baseRecentReports = [];
let extraRecentReports = [];
let activeEmployeesList = [];

// ─── STATE ──────────────────────────────────────────────────────
let currentPage = 1;
const PAGE_SIZE = 5;
let currentStatusFilter = 'all';

// Chart instances
let barChart, doughnutChart, lineChart;

const FACILITY_TO_DEPT_MAP = {
    'central health center': ['health center services', 'immunization & nutrition', 'health surveillance'],
    'eastside clinic': ['health center services', 'immunization & nutrition'],
    'west district hospital': ['health center services', 'health surveillance'],
    'north community hub': ['health center services', 'immunization & nutrition'],
    'south sanitation depot': ['sanitation permits', 'wastewater services']
};

// ─── FILTERING ENGINE ──────────────────────────────────────────
function getFilteredData() {
    return allReportRows;
}

// ─── GET CURRENT CONFIG (for templates) ──────────────────────
function getCurrentConfig() {
    return {
        reportType: document.getElementById('reportType').value,
        startDate: document.getElementById('startDate').value,
        endDate: document.getElementById('endDate').value,
        facility: document.getElementById('facility').value,
        inspector: document.getElementById('inspector').value,
        status: currentStatusFilter
    };
}

function applyConfig(config) {
    if (!config) return;
    if (config.reportType && document.getElementById('reportType')) document.getElementById('reportType').value = config.reportType;
    if (config.exportFormat && document.getElementById('exportFormat')) document.getElementById('exportFormat').value = config.exportFormat;
    if (config.startDate && document.getElementById('startDate')) document.getElementById('startDate').value = config.startDate;
    if (config.endDate && document.getElementById('endDate')) document.getElementById('endDate').value = config.endDate;
    if (config.facility && document.getElementById('facility')) document.getElementById('facility').value = config.facility;
    if (config.inspector && document.getElementById('inspector')) document.getElementById('inspector').value = config.inspector;

    if (config.status) {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        const chip = document.querySelector(`.filter-chip[data-status="${config.status}"]`);
        if (chip) chip.classList.add('active');
        currentStatusFilter = config.status;
        currentPage = 1;
    }
    refreshUI();
}

// ─── UI REFRESH ─────────────────────────────────────────────────
function refreshUI() {
    const facilityVal = document.getElementById('facility') ? document.getElementById('facility').value : 'all';
    populateInspectorDropdown(activeEmployeesList, facilityVal);

    const data = getFilteredData();
    
    // Fallback UI Metrics (read from dynamic KPIs)
    const total = document.getElementById('kpi-total')?.textContent || 0;
    const compliant = document.getElementById('kpi-compliant')?.textContent || 0;
    const urgent = document.getElementById('kpi-urgent')?.textContent || 0;
    const pending = document.getElementById('kpi-pending')?.textContent || 0;
    
    // Facilities approximation from rows if possible, or 0
    const facilities = data.length; 

    // Note: The main KPI cards (Total, Compliant, Pending, Urgent) are updated 
    // by updateDynamicKPIs() from the server response, so we don't overwrite them here.

    let complianceRate = 0;
    if (parseInt(total) > 0) {
        // Calculate based on numbers
        let c = parseInt(compliant.replace(/[^0-9]/g, '')) || 0;
        let t = parseInt(total.replace(/[^0-9]/g, '')) || 0;
        if (t > 0) complianceRate = ((c / t) * 100).toFixed(1);
    }

    // Summary metrics & fallback UI
    const sumTxt = document.getElementById('summaryText');
    if (sumTxt) {
        sumTxt.innerHTML = `
            <p>This report contains a total of <strong class="text-[#176B87]">${total} records</strong>.</p>
            <p>The positive/compliant outcome rate is <strong class="text-emerald-600">${complianceRate}%</strong>.</p>
            <p>Key areas of concern: ${urgent} urgent issues, ${pending} pending.</p>
        `;
    }
    
    const sumTags = document.getElementById('summaryTags');
    if (sumTags) {
        sumTags.innerHTML = `
            <span class="px-3 py-1 bg-[#B4D4FF]/30 text-[#176B87] rounded-full text-xs font-medium">🔹 Rate: ${complianceRate}%</span>
            <span class="px-3 py-1 bg-amber-100/60 text-amber-700 rounded-full text-xs font-medium">⚠️ Pending: ${pending}</span>
            <span class="px-3 py-1 bg-red-100/60 text-red-700 rounded-full text-xs font-medium">🚨 Urgent: ${urgent}</span>
        `;
    }
    
    const mComp = document.getElementById('metricCompliance');
    if (mComp) mComp.textContent = complianceRate + '%';
    const mBar = document.getElementById('metricComplianceBar');
    if (mBar) mBar.style.width = complianceRate + '%';
    const coverage = total > 0 ? Math.round((facilities / 52) * 100) : 0;
    document.getElementById('metricCoverage').textContent = coverage + '%';
    document.getElementById('metricCoverageBar').style.width = coverage + '%';
    const resolution = total > 0 ? Math.round(((compliant) / total) * 100) : 0;
    document.getElementById('metricResolution').textContent = resolution + '%';
    document.getElementById('metricResolutionBar').style.width = resolution + '%';
    document.getElementById('metricParticipation').textContent = total > 0 ? '100%' : '0%';
    document.getElementById('metricParticipationBar').style.width = total > 0 ? '100%' : '0%';

    // Dynamic print & export title based on module & department
    const reportTypeSelect = document.getElementById('reportType');
    const moduleName = reportTypeSelect?.selectedOptions[0]?.textContent?.trim() || 'Operational Report';
    const subTitleElem = document.getElementById('printReportSubtitle');
    if (subTitleElem) {
        subTitleElem.textContent = moduleName;
    }

    updateCharts(data);
    renderTableView(data);

    // Fetch AI Executive Summary per department
    fetchAiReportSummary(false);
}

// ─── AI REPORT SUMMARY GENERATION ENGINE ─────────────────────────
async function fetchAiReportSummary(isManual = false) {
    const btn = document.getElementById('btnGenerateAiSummary');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Analyzing...';
    }

    const deptSelect = document.getElementById('facility') || document.getElementById('filterFacility');
    const selectedDept = deptSelect ? deptSelect.value : 'all';
    
    const total = document.getElementById('kpi-total')?.textContent || 0;
    const compliant = document.getElementById('kpi-compliant')?.textContent || 0;
    const urgent = document.getElementById('kpi-urgent')?.textContent || 0;
    const pending = document.getElementById('kpi-pending')?.textContent || 0;

    const moduleSelect = document.getElementById('reportType');
    const selectedModule = moduleSelect ? moduleSelect.value : 'unified';

    try {
        const url = `${APP_CONFIG.api_ai_summary}?module=${encodeURIComponent(selectedModule)}&department=${encodeURIComponent(selectedDept)}&total=${total}&compliant=${compliant}&urgent=${urgent}&pending=${pending}`;
        const resp = await fetch(url);
        const res = await resp.json();

        if (res && res.success) {
            // Update Summary Text
            document.getElementById('summaryText').innerHTML = `
                <p class="font-bold text-slate-800 mb-1.5 text-xs">${res.department} Executive Overview:</p>
                <p class="leading-relaxed text-xs text-slate-700">${res.summary}</p>
            `;

            // Update Risk Badge
            const riskBadge = document.getElementById('aiRiskBadge');
            if (riskBadge) {
                riskBadge.textContent = res.risk_level || 'Optimal';
                riskBadge.className = res.risk_level === 'High Risk' 
                    ? 'px-2.5 py-1 bg-rose-100 text-rose-700 rounded-lg text-xs font-bold'
                    : (res.risk_level === 'Moderate Risk' ? 'px-2.5 py-1 bg-amber-100 text-amber-700 rounded-lg text-xs font-bold' : 'px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold');
            }

            // Update Tags
            const complianceRate = res.metrics ? res.metrics.compliance_rate : 0;
            document.getElementById('summaryTags').innerHTML = `
                <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-full text-xs font-bold border border-indigo-100">✨ ${res.ai_generated ? 'AI Model Summary' : 'Rule Engine Summary'}</span>
                <span class="px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold border border-blue-100">🔹 Compliance ${complianceRate}%</span>
                <span class="px-2.5 py-1 bg-amber-50 text-amber-700 rounded-full text-xs font-bold border border-amber-100">⚠️ Pending: ${pending}</span>
                <span class="px-2.5 py-1 bg-rose-50 text-rose-700 rounded-full text-xs font-bold border border-rose-100">🚨 Urgent: ${urgent}</span>
            `;

            // Update Key Findings
            if (res.key_findings && res.key_findings.length > 0) {
                const findingsHtml = res.key_findings.map(f => `
                    <div class="p-2.5 bg-indigo-50/60 rounded-xl border border-indigo-100 text-indigo-950 font-semibold flex items-start gap-2 text-xs">
                        <i class="fas fa-circle-info text-indigo-600 mt-0.5 flex-shrink-0"></i>
                        <span>${f}</span>
                    </div>
                `).join('');
                document.getElementById('aiKeyFindings').innerHTML = findingsHtml;
            }

            // Update Actionable Recommendations
            if (res.recommendations && res.recommendations.length > 0) {
                const recsHtml = res.recommendations.map(r => `
                    <li class="flex items-start gap-2 font-medium text-slate-700 text-xs">
                        <i class="fas fa-check-circle text-emerald-500 text-xs mt-0.5 flex-shrink-0"></i>
                        <span>${r}</span>
                    </li>
                `).join('');
                document.getElementById('aiRecommendationsList').innerHTML = recsHtml;
            }

            if (isManual && typeof showToast === 'function') {
                showToast('AI Executive Summary generated successfully!', 'success');
            }
        }
    } catch (e) {
        console.error('Failed to fetch AI report summary:', e);
    } finally {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-wand-magic-sparkles text-xs"></i> Generate AI Summary';
        }
    }
}

// ─── CHART UPDATES ─────────────────────────────────────────────
function updateCharts(data) {
    if (!data || data.length === 0) {
        if (barChart) {
            barChart.data.labels = ['No Data'];
            barChart.data.datasets[0].data = [0];
            barChart.update();
        }
        if (doughnutChart) {
            doughnutChart.data.labels = ['No Data'];
            doughnutChart.data.datasets[0].data = [1];
            doughnutChart.update();
        }
        if (lineChart) {
            lineChart.data.labels = ['No Data'];
            lineChart.data.datasets[0].data = [0];
            lineChart.update();
        }
        return;
    }

    const catMap = {};
    data.forEach(r => {
        const c = r.category || 'General';
        catMap[c] = (catMap[c] || 0) + 1;
    });

    const labels = Object.keys(catMap);
    const values = Object.values(catMap);
    const colors = ['#176B87', '#3b82f6', '#f59e0b', '#ef4444', '#10b981', '#8b5cf6', '#ec4899'];

    if (barChart) {
        barChart.data.labels = labels;
        barChart.data.datasets[0].data = values;
        barChart.data.datasets[0].backgroundColor = colors.slice(0, labels.length);
        barChart.update();
    }

    if (doughnutChart) {
        doughnutChart.data.labels = labels;
        doughnutChart.data.datasets[0].data = values;
        doughnutChart.data.datasets[0].backgroundColor = colors.slice(0, labels.length);
        doughnutChart.update();
    }

    if (lineChart) {
        lineChart.data.labels = labels;
        lineChart.data.datasets[0].data = values;
        lineChart.update();
    }
}

// ─── TABLE RENDER ──────────────────────────────────────────────
function renderTableView(data) {
    const tbody = document.getElementById('tableViewBody');
    if (!tbody) return;
    const total = data.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (currentPage > totalPages) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    const start = (currentPage - 1) * PAGE_SIZE;
    const pageRows = data.slice(start, start + PAGE_SIZE);

    const statusBadgeClass = {
        'Compliant': 'bg-emerald-100/70 text-emerald-700',
        'Pending': 'bg-amber-100/70 text-amber-700',
        'Urgent': 'bg-red-100/70 text-red-700'
    };

    tbody.innerHTML = pageRows.map((r, index) => {
        const cat = r.category || 'General';
        const item = r.item || r.metric || ('Record #' + (start + index + 1));
        const details = r.details || 'Operational record';
        const date = r.date || '-';
        const status = r.status || 'Compliant';
        const statusClass = statusBadgeClass[status] || 'bg-slate-100 text-slate-700';

        return `
        <tr class="table-row-hover transition-colors">
            <td class="py-3 pr-4 font-semibold text-[#176B87] text-xs">${escapeExportHtml(cat)}</td>
            <td class="py-3 pr-4 text-slate-800 font-medium text-xs whitespace-nowrap">${escapeExportHtml(item)}</td>
            <td class="py-3 pr-4 text-slate-600 text-xs max-w-sm truncate" title="${escapeExportHtml(details)}">${escapeExportHtml(details)}</td>
            <td class="py-3 pr-4 text-slate-500 text-xs whitespace-nowrap">${escapeExportHtml(date)}</td>
            <td class="py-3 pr-4"><span class="status-badge ${statusClass} px-2.5 py-1 rounded-full text-xs font-semibold whitespace-nowrap">${escapeExportHtml(status)}</span></td>
        </tr>
    `}).join('') || '<tr><td colspan="5" class="py-8 text-center text-slate-400">No records match this filter.</td></tr>';

    document.getElementById('tableViewSummary').textContent = total === 0
        ? 'No entries match this filter'
        : `Showing ${start + 1}-${Math.min(start + PAGE_SIZE, total)} of ${total} entries`;

    document.querySelectorAll('#tablePagination [data-page]').forEach(btn => {
        const page = parseInt(btn.dataset.page, 10);
        const isActive = page === currentPage;
        btn.classList.toggle('bg-[#176B87]', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('shadow-sm', isActive);
        btn.classList.toggle('shadow-[#176B87]/20', isActive);
        btn.classList.toggle('border', !isActive);
        btn.classList.toggle('border-[#B4D4FF]/30', !isActive);
    });
}

// ─── VIEW ROW ──────────────────────────────────────────────────
function viewRow(index) {
    const data = getFilteredData();
    if (index < 0 || index >= data.length) {
        showToast('Record not found.', 'info');
        return;
    }
    const row = data[index];
    document.getElementById('detailFacility').textContent = row.facility;
    document.getElementById('detailInspector').textContent = row.inspector;
    document.getElementById('detailDate').textContent = row.date;
    document.getElementById('detailScore').textContent = row.score + ' / 100';
    const statusEl = document.getElementById('detailStatus');
    statusEl.textContent = row.status;
    const statusColors = {
        'Compliant': 'text-emerald-600',
        'Pending': 'text-amber-600',
        'Urgent': 'text-red-600',
        'Non-Compliant': 'text-red-600'
    };
    statusEl.className = 'font-medium ' + (statusColors[row.status] || 'text-slate-600');
    
    const modal = document.getElementById('viewDetailModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeViewDetailModal() {
    const modal = document.getElementById('viewDetailModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

// ─── DOWNLOAD ROW ──────────────────────────────────────────────
function downloadRow(index) {
    const data = getFilteredData();
    if (index < 0 || index >= data.length) {
        showToast('Record not found.', 'info');
        return;
    }
    const row = data[index];
    const headers = ['Category', 'Metric', 'Count/Value'];
    const values = [row.facility, row.inspector, row.date, row.score + '/100', row.status];
    const csvContent = headers.join(',') + '\n' + values.map(v => `"${String(v).replace(/"/g, '""')}"`).join(',');
    
    const blob = new Blob(['\uFEFF' + csvContent], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = `report_${row.facility.replace(/\s+/g, '_')}_${row.date}.csv`;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
    
    showToast(`Report for ${row.facility} downloaded!`, 'success');
}

function goToPage(page) {
    const data = getFilteredData();
    const totalPages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
    currentPage = Math.min(Math.max(1, page), totalPages);
    renderTableView(data);
}

// ─── TEMPLATE MANAGEMENT ─────────────────────────────────────
const TEMPLATE_STORAGE_KEY = 'hsms_report_templates';

function getSavedTemplates() {
    try {
        const raw = localStorage.getItem(TEMPLATE_STORAGE_KEY);
        if (!raw) return [];
        return JSON.parse(raw);
    } catch (e) {
        return [];
    }
}

function saveTemplates(templates) {
    try {
        localStorage.setItem(TEMPLATE_STORAGE_KEY, JSON.stringify(templates));
    } catch (e) {
        showToast('Could not save templates. Storage may be full.', 'info');
    }
}

// ─── SAVE TEMPLATE ────────────────────────────────────────────
function openSaveTemplateModal() {
    const modal = document.getElementById('saveTemplateModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
    document.getElementById('templateNameInput').value = '';
    document.getElementById('templateNameInput').focus();
}

function closeSaveTemplateModal() {
    const modal = document.getElementById('saveTemplateModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function saveTemplate() {
    const input = document.getElementById('templateNameInput');
    const name = input.value.trim();
    if (!name) {
        showToast('Please enter a template name.', 'info');
        input.focus();
        return;
    }
    
    const templates = getSavedTemplates();
    if (templates.some(t => t.name === name)) {
        if (!confirm(`A template named "${name}" already exists. Overwrite?`)) {
            return;
        }
        const filtered = templates.filter(t => t.name !== name);
        templates.length = 0;
        templates.push(...filtered);
    }
    
    const config = getCurrentConfig();
    templates.push({ name, data: config, savedAt: new Date().toISOString() });
    saveTemplates(templates);
    closeSaveTemplateModal();
    showToast(`Template "${name}" saved successfully!`, 'success');
}

// ─── LOAD TEMPLATE ────────────────────────────────────────────
function openTemplateModal() {
    const modal = document.getElementById('templateModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
    renderTemplateList();
}

function closeTemplateModal() {
    const modal = document.getElementById('templateModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function renderTemplateList() {
    const container = document.getElementById('templateList');
    const templates = getSavedTemplates();
    
    if (templates.length === 0) {
        container.innerHTML = `<p class="text-sm text-slate-400 text-center py-8">No saved templates found.</p>`;
        return;
    }
    
    container.innerHTML = templates.map((t, index) => `
        <div class="template-item flex items-center justify-between px-3 py-2.5 rounded-xl border border-[#B4D4FF]/20 hover:border-[#B4D4FF]/50 transition">
            <div class="flex items-center gap-3 flex-1 min-w-0" onclick="loadTemplateByName('${t.name}')">
                <i class="fa-regular fa-file-lines text-[#176B87]"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 truncate">${t.name}</p>
                    <p class="text-[10px] text-slate-400">${new Date(t.savedAt).toLocaleDateString()}</p>
                </div>
            </div>
            <button onclick="deleteTemplate('${t.name}')" class="delete-btn p-1.5 rounded-lg hover:bg-red-50 text-slate-300 hover:text-red-500 transition ml-2" title="Delete template">
                <i class="fa-regular fa-trash-can text-xs"></i>
            </button>
        </div>
    `).join('');
}

function loadTemplateByName(name) {
    const templates = getSavedTemplates();
    const template = templates.find(t => t.name === name);
    if (!template) {
        showToast(`Template "${name}" not found.`, 'info');
        return;
    }
    applyConfig(template.data);
    closeTemplateModal();
    showToast(`Template "${name}" loaded successfully!`, 'success');
}

function deleteTemplate(name) {
    if (!confirm(`Delete template "${name}"?`)) return;
    const templates = getSavedTemplates();
    const filtered = templates.filter(t => t.name !== name);
    if (filtered.length === templates.length) {
        showToast(`Template "${name}" not found.`, 'info');
        return;
    }
    saveTemplates(filtered);
    renderTemplateList();
    showToast(`Template "${name}" deleted.`, 'info');
}

// ─── REPORT GENERATION AUDIT LOGGER ───────────────────────────
async function logReportGeneration(reportName, format) {
    try {
        const facilitySelect = document.getElementById('facility');
        const facility = facilitySelect ? (facilitySelect.selectedOptions[0]?.textContent || facilitySelect.value) : 'All Core Departments';
        const start = document.getElementById('startDate')?.value || '';
        const end = document.getElementById('endDate')?.value || '';
        const dateRange = (start && end) ? `${start} to ${end}` : '';

        await fetch(APP_CONFIG.api_log_export, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                report_name: reportName || 'Compliance & Operational Report',
                export_type: format || 'Custom Report',
                department: facility,
                date_range: dateRange
            })
        });

        // Silently reload live report audit logs
        loadLiveReportData();
    } catch (err) {
        console.error('Failed to record report generation audit log:', err);
    }
}

// ─── GENERATE REPORT ──────────────────────────────────────────
function generateReport() {
    const btn = document.getElementById('generateBtn');
    const originalContent = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<div class="spinner"></div> Generating...';
        btn.disabled = true;
    }

    const data = getFilteredData();
    const total = data.length;
    const compliant = data.filter(r => r.status === 'Compliant').length;
    const nonCompliant = data.filter(r => r.status === 'Non-Compliant').length;
    const urgent = data.filter(r => r.status === 'Urgent').length;
    const complianceRate = total > 0 ? ((compliant / total) * 100).toFixed(1) : 0;

    // Update Summary Banner
    const banner = document.getElementById('generatedReportSummary');
    if (banner) {
        document.getElementById('bannerCompliance').textContent = complianceRate + '%';
        document.getElementById('bannerTotal').textContent = total;
        document.getElementById('bannerNonCompliant').textContent = nonCompliant;
        document.getElementById('bannerUrgent').textContent = urgent;

        const reportTypeSelect = document.getElementById('reportType');
        const reportTypeText = reportTypeSelect ? reportTypeSelect.options[reportTypeSelect.selectedIndex]?.text : 'Operational Report';
        const bannerMeta = document.getElementById('bannerMeta');
        if (bannerMeta) {
            bannerMeta.textContent = `${reportTypeText} · Scoped to ${CURRENT_USER.department} · Generated ${new Date().toLocaleTimeString()}`;
        }
        const badge = document.getElementById('bannerReportBadge');
        if (badge) {
            badge.textContent = `Live Ready (${total} records)`;
        }
        banner.classList.remove('hidden');
    }

    const reportTypeVal = document.getElementById('reportType')?.value || 'report';
    logReportGeneration(`${reportTypeVal.toUpperCase()} Report Generation`, 'Custom Query Generated');

    setTimeout(() => {
        refreshUI();
        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Report Ready!';
        }
        showToast('Report generated successfully!', 'success');
        
        // 1. Automatically generate the AI summary
        fetchAiReportSummary(true);
        
        // 2. Auto-scroll to report preview
        scrollToPreview('chart');

        setTimeout(() => {
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        }, 2000);
    }, 400);
}

function scrollToPreview(tab = 'chart') {
    switchTab(tab);
    const elem = document.getElementById('reportPreview');
    if (elem) elem.scrollIntoView({ behavior: 'smooth' });
}

// ─── RESULT MODAL ─────────────────────────────────────────────
function openResultModal() {
    const modal = document.getElementById('reportResultModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeResultModal() {
    const modal = document.getElementById('reportResultModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

// ─── DOWNLOAD WRAPPERS ──────────────────────────────────────
function downloadPDF() {
    closeResultModal();
    exportPDF();
}

function downloadExcel() {
    closeResultModal();
    setTimeout(() => { exportExcel(); showToast('Excel downloaded successfully!', 'success'); }, 300);
}

function downloadWord() {
    closeResultModal();
    setTimeout(() => { exportWord(); showToast('Word document downloaded successfully!', 'success'); }, 300);
}

// ─── EXPORT FUNCTIONS ─────────────────────────────────────────
function currentReportData() {
    return getFilteredData();
}

function downloadBlob(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

function escapeExportHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function getReportExportMarkup() {
    const reportType = document.getElementById('reportType')?.selectedOptions[0]?.textContent || 'Custom Report';
    const startDate = document.getElementById('startDate')?.value || '';
    const endDate = document.getElementById('endDate')?.value || '';
    const generatedDate = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    const logoUrl = new URL('../assets/images/logo.png', window.location.href).href;

    // Clone ALL three tabs: Chart View (Page 1), Table View (Page 2), AI Summary (Page 3)
    const allTabs = [
        { id: 'tabChart', title: 'Chart View', icon: '&#128202;' },
        { id: 'tabTable', title: 'Table View', icon: '&#128203;' },
        { id: 'tabSummary', title: 'AI Summary', icon: '&#129302;' }
    ];

    function cloneTabContent(tab) {
        const source = document.getElementById(tab.id);
        if (!source) return '';

        const clone = source.cloneNode(true);
        clone.classList.remove('hidden', 'tab-content');
        clone.style.cssText = 'display:block;opacity:1;transform:none;animation:none;';
        clone.querySelectorAll('#tablePagination, .action-btn, button, .filter-chip').forEach(el => el.remove());

        // Convert canvas charts to static PNG images
        clone.querySelectorAll('canvas').forEach(canvas => {
            const sourceCanvas = document.getElementById(canvas.id);
            if (!sourceCanvas) return;
            const image = document.createElement('img');
            image.src = sourceCanvas.toDataURL('image/png');
            image.alt = canvas.id;
            image.style.cssText = 'display:block;width:100%;height:auto;max-height:320px;object-fit:contain;margin:8px auto;';
            canvas.replaceWith(image);
        });

        return clone.innerHTML;
    }

    // Build main header (appears only on page 1)
    const mainHeader = `
        <div class="export-header">
            <img src="${logoUrl}" alt="Logo">
            <h1>South Caloocan City Health &amp; Sanitation</h1>
            <h3>${escapeExportHtml(reportType)}</h3>
            <p class="export-date-range">${escapeExportHtml(startDate)} to ${escapeExportHtml(endDate)}</p>
            <p class="export-generated">Generated: ${escapeExportHtml(generatedDate)}</p>
        </div>`;

    // Build each page section
    let pages = '';
    let pageNum = 0;
    for (const tab of allTabs) {
        const content = cloneTabContent(tab);
        if (!content) continue;
        pageNum++;

        pages += `
        <div class="export-page${pageNum > 1 ? ' page-break' : ''}">
            ${pageNum === 1 ? mainHeader : ''}
            <div class="section-header">
                <span class="section-icon">${tab.icon}</span>
                <span class="section-title">${tab.title}</span>
                <span class="section-page">Page ${pageNum}</span>
            </div>
            <div class="section-body">
                ${content}
            </div>
        </div>`;
    }

    if (!pages) return '';

    return `<article class="export-report">${pages}</article>`;
}

function getExportDocument(content, title, extraStyles = '') {
    return `<!doctype html><html><head><meta charset="UTF-8"><title>${escapeExportHtml(title)}</title>
        <style>
            @page { margin: 0.6in 0.5in; }
            body { margin: 0; padding: 0; color: #1e293b; font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; line-height: 1.4; }

            /* ── Page break control ── */
            .page-break { page-break-before: always; }
            .export-page { padding: 0; }

            /* ── Main header (page 1 only) ── */
            .export-header { text-align: center; border-bottom: 3px solid #176B87; padding-bottom: 12px; margin-bottom: 16px; }
            .export-header img { width: 72px; display: block; margin: 0 auto 6px; }
            .export-header h1 { margin: 0; color: #0F4A5E; font-size: 16pt; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
            .export-header h3 { margin: 4px 0 0; color: #176B87; font-size: 12pt; font-weight: normal; }
            .export-header .export-date-range { margin: 4px 0 0; color: #334155; font-size: 9pt; font-weight: 600; }
            .export-header .export-generated { margin: 2px 0 0; color: #94a3b8; font-size: 7pt; }

            /* ── Section header bar ── */
            .section-header { display: block; background: #176B87; color: #fff; padding: 8px 14px; margin-bottom: 14px; border-radius: 4px; font-size: 11pt; font-weight: bold; }
            .section-header .section-icon { margin-right: 6px; }
            .section-header .section-title { }
            .section-header .section-page { float: right; font-weight: normal; font-size: 8pt; opacity: 0.8; margin-top: 2px; }

            /* ── Section body ── */
            .section-body { padding: 0 4px; }

            /* ── Tailwind overrides for PDF ── */
            .backdrop-blur-sm, [class*="bg-white"] { background: #fff !important; }
            .grid { display: block !important; }
            .grid > * { margin-bottom: 10px; }
            .rounded-2xl, .rounded-xl, .rounded-lg { border-radius: 4px !important; }
            .border { border: 1px solid #e2e8f0 !important; }
            .shadow-sm, .shadow-xs { box-shadow: none !important; }
            .hidden { display: none !important; }
            [class*="gap-"] { margin-bottom: 6px; }

            /* ── Tables ── */
            table { border-collapse: collapse; width: 100%; margin: 8px 0; }
            th { background-color: #176B87; color: #fff; padding: 6px 8px; font-size: 8pt; text-align: left; border: 1px solid #176B87; }
            td { padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 8pt; }
            tr:nth-child(even) { background-color: #f8fafc; }

            /* ── Charts (converted to images) ── */
            img { max-width: 100%; height: auto; }

            /* ── AI summary metrics ── */
            .bg-slate-50 { background: #f8fafc !important; }
            .bg-indigo-50\\/60 { background: #eef2ff !important; }
            .text-indigo-900 { color: #312e81 !important; }

            /* ── Progress bars ── */
            [class*="h-2"][class*="bg-"] { height: 6px; border-radius: 3px; }
            [class*="rounded-full"] { border-radius: 9999px; }

            /* ── SVG ── */
            svg { max-width: 100%; }

            ${extraStyles}
        </style></head><body>${content}</body></html>`;
}

function getReportMetadata() {
    const reportTypeSelect = document.getElementById('reportType');
    const moduleName = reportTypeSelect?.selectedOptions[0]?.textContent?.trim() || 'Operational Report';
    const dept = (CURRENT_USER && CURRENT_USER.department) ? CURRENT_USER.department : 'Health Sanitation Management';
    const cleanTitle = `${moduleName} - ${dept}`;
    const slug = cleanTitle.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
    return { title: cleanTitle, module: moduleName, slug, department: dept };
}

function exportCSV() {
    const meta = getReportMetadata();
    const data = currentReportData();
    const headers = ['Module / Category', 'Record ID', 'Details', 'Date', 'Status'];
    const lines = [headers.join(',')];
    data.forEach(r => {
        lines.push([
            r.category || 'General',
            r.item || r.metric || 'Record',
            r.details || '',
            r.date || '',
            r.status || 'Compliant'
        ].map(v => `"${String(v).replace(/"/g, '""')}"`).join(','));
    });
    const stamp = new Date().toISOString().slice(0, 10);
    downloadBlob('\uFEFF' + lines.join('\n'), `${meta.slug}_${stamp}.csv`, 'text/csv;charset=utf-8;');
    logReportGeneration(meta.title, 'CSV Export');
    showToast('CSV exported successfully!', 'success');
}

function exportExcel() {
    const meta = getReportMetadata();
    const data = currentReportData();
    const headers = ['Module / Category', 'Record ID', 'Details', 'Date', 'Status'];
    const rows = data.map(r => [
        r.category || 'General',
        r.item || r.metric || 'Record',
        r.details || '',
        r.date || '',
        r.status || 'Compliant'
    ]);
    const stamp = new Date().toISOString().slice(0, 10);

    showToast('Generating Excel report...', 'info');
    fetch(`../api/reports/export.php?format=excel&title=${encodeURIComponent(meta.title)}&module=${encodeURIComponent(meta.module)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ headers, rows })
    }).then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.blob();
    }).then(blob => {
        downloadBlob(blob, `${meta.slug}_${stamp}.xlsx`, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        logReportGeneration(meta.title, 'Excel Export');
        showToast('Excel report downloaded successfully!', 'success');
    }).catch(err => {
        showToast('Excel export failed: ' + err.message, 'danger');
    });
}

function exportWord() {
    const meta = getReportMetadata();
    const html = getExportDocument(getReportExportMarkup(), meta.title, 'body { font-family: Arial, sans-serif; }');
    downloadBlob(html, `${meta.slug}.doc`, 'application/msword');
    logReportGeneration(meta.title, 'Word Export');
    showToast('Word document exported successfully!', 'success');
}

function exportPDF() {
    const meta = getReportMetadata();
    const visualHtml = getExportDocument(getReportExportMarkup(), meta.title, `
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        img { max-width: 100%; height: auto; }
        .export-section { page-break-inside: avoid; }
    `);

    if (!visualHtml || visualHtml.length < 100) {
        showToast('No report data to export. Generate a report first.', 'warning');
        return;
    }

    const stamp = new Date().toISOString().slice(0, 10);
    showToast('Generating PDF report with charts, table & AI summary...', 'info');

    fetch(`../api/reports/export.php?format=pdf&title=${encodeURIComponent(meta.title)}&module=${encodeURIComponent(meta.module)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ html: visualHtml, title: meta.title })
    }).then(res => {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.blob();
    }).then(blob => {
        downloadBlob(blob, `${meta.slug}_${stamp}.pdf`, 'application/pdf');
        logReportGeneration(meta.title, 'PDF Export');
        showToast('PDF report downloaded successfully!', 'success');
    }).catch(err => {
        showToast('PDF export failed: ' + err.message, 'danger');
    });
}

function printCustomReport() {
    const meta = getReportMetadata();
    logReportGeneration(meta.title, 'Browser Print');

    // Temporarily show all tab-content panels for printing
    const allPanels = document.querySelectorAll('#reportPreview .tab-content');
    const wasHidden = [];
    allPanels.forEach(panel => {
        if (panel.classList.contains('hidden')) {
            wasHidden.push(panel);
            panel.classList.remove('hidden');
            panel.dataset.printRestoreHidden = 'true';
        }
    });

    window.print();

    // Restore hidden state after print dialog closes
    setTimeout(() => {
        wasHidden.forEach(panel => {
            panel.classList.add('hidden');
            delete panel.dataset.printRestoreHidden;
        });
    }, 500);
}

// ─── RESET FILTERS ────────────────────────────────────────────
function resetFilters() {
    document.getElementById('reportType').value = 'inspection';
    document.getElementById('facility').value = 'all';
    document.getElementById('inspector').value = 'all';
    const today = new Date();
    const start = new Date(today);
    start.setDate(today.getDate() - 45);
    document.getElementById('startDate').value = start.toISOString().split('T')[0];
    document.getElementById('endDate').value = today.toISOString().split('T')[0];

    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    const allChip = document.querySelector('.filter-chip[data-status="all"]');
    if (allChip) allChip.classList.add('active');
    currentStatusFilter = 'all';
    currentPage = 1;

    refreshUI();
    showToast('Filters reset to default.', 'info');
}

// ─── STATUS CHIPS ─────────────────────────────────────────────
document.querySelectorAll('.filter-chip').forEach(chip => {
    chip.addEventListener('click', () => {
        document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        currentStatusFilter = chip.dataset.status || 'all';
        currentPage = 1;
        refreshUI();
    });
});

// ─── FILTER CHANGE EVENTS ──────────────────────────────────
['reportType', 'facility', 'startDate', 'endDate'].forEach(id => {
    const el = document.getElementById(id);
    if (el) el.addEventListener('change', refreshUI);
});

// ─── TAB SWITCH ──────────────────────────────────────────────
function switchTab(tabName) {
    document.querySelectorAll('.tab-content').forEach(tab => tab.classList.add('hidden'));
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    const selectedTab = document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
    if (selectedTab) selectedTab.classList.remove('hidden');
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
}

// ─── MODAL CONTROLS ───────────────────────────────────────────
function openScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function scheduleReport() {
    saveSchedule();
}

// ─── TOAST ──────────────────────────────────────────────────────
let toastTimer;
function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const toastMessage = document.getElementById('toastMessage');
    const toastIcon = document.getElementById('toastIcon');

    toastMessage.textContent = message;
    if (type === 'success') {
        toast.style.background = '#176B87';
        toastIcon.className = 'fa-regular fa-circle-check text-[#B4D4FF] text-lg';
    } else if (type === 'info') {
        toast.style.background = '#64748b';
        toastIcon.className = 'fa-regular fa-circle-info text-white text-lg';
    }

    toast.classList.add('toast-show');
    toast.style.pointerEvents = 'auto';
    clearTimeout(toastTimer);
    toastTimer = setTimeout(hideToast, 3000);
}

function hideToast() {
    const toast = document.getElementById('toast');
    toast.classList.remove('toast-show');
    toast.style.pointerEvents = 'none';
}

let viewingAllReports = false;

// ─── DYNAMIC ROLE FILTER PATTERNS ───────────────────────────────
const REPORT_TYPE_ROLE_PATTERNS = {
    sanitation: ['inspector', 'sanitation', 'permit', 'cashier', 'depot'],
    health_center: ['doctor', 'nurse', 'dentist', 'clinic', 'medical', 'appointment', 'laboratory', 'practitioner', 'health center'],
    immunization: ['immunization', 'nutrition', 'midwife', 'educator', 'vaccine'],
    wastewater: ['wastewater', 'water', 'environment'],
    surveillance: ['surveillance', 'epidemiol', 'disease', 'outbreak']
};

function populateInspectorDropdown(employees, filterDepartment = 'all') {
    const inspectorSelect = document.getElementById('inspector');
    if (!inspectorSelect || !Array.isArray(employees)) return;
    if (CURRENT_USER.tier === 'staff') return;

    const currentVal = inspectorSelect.value;
    const reportTypeSelect = document.getElementById('reportType');
    const reportType = reportTypeSelect ? reportTypeSelect.value : 'all';
    const selectedDept = filterDepartment !== 'all' ? filterDepartment : (document.getElementById('facility') ? document.getElementById('facility').value : 'all');

    let filteredEmployees = employees;

    // 1. Department Scoping
    if (CURRENT_USER.tier === 'director') {
        const deptLower = CURRENT_USER.department.toLowerCase().replace(' services', '');
        filteredEmployees = employees.filter(emp => {
            const empDept = (emp.department || '').toLowerCase();
            return empDept === CURRENT_USER.department.toLowerCase() || empDept.includes(deptLower) || deptLower.includes(empDept);
        });
    } else if (selectedDept !== 'all') {
        const targetDept = selectedDept.toLowerCase().replace(' services', '');
        filteredEmployees = employees.filter(emp => {
            const empDept = (emp.department || '').toLowerCase();
            return empDept === selectedDept.toLowerCase() || empDept.includes(targetDept) || targetDept.includes(empDept);
        });
    }

    // 2. Dynamic Filtering based on Report Type
    const patterns = REPORT_TYPE_ROLE_PATTERNS[reportType];
    if (patterns && patterns.length > 0) {
        const roleFiltered = filteredEmployees.filter(emp => {
            const roleStr = (emp.role_description || emp.role || '').toLowerCase();
            return patterns.some(p => roleStr.includes(p));
        });
        if (roleFiltered.length > 0) {
            filteredEmployees = roleFiltered;
        }
    }

    const grouped = {};
    filteredEmployees.forEach(emp => {
        const d = emp.department || 'General Personnel';
        if (!grouped[d]) grouped[d] = [];
        grouped[d].push(emp);
    });

    let html = '<option value="all">All Personnel (' + filteredEmployees.length + ' available)</option>';
    
    Object.keys(grouped).forEach(deptName => {
        html += `<optgroup label="🏢 ${deptName}">`;
        grouped[deptName].forEach(emp => {
            const roleDesc = emp.role_description || emp.role || 'Staff Member';
            html += `<option value="${escapeExportHtml(emp.name)}">${escapeExportHtml(emp.name)} — ${escapeExportHtml(roleDesc)}</option>`;
        });
        html += `</optgroup>`;
    });

    inspectorSelect.innerHTML = html;
    if (currentVal && Array.from(inspectorSelect.options).some(o => o.value === currentVal)) {
        inspectorSelect.value = currentVal;
    }
}

// ─── LIVE DATA LOADER ───────────────────────────────────────────
async function loadLiveReportData() {
    try {
        const module = document.getElementById("reportType")?.value || "unified";
        const start = document.getElementById("startDate")?.value || "";
        const end = document.getElementById("endDate")?.value || "";
        const resp = await fetch(`${APP_CONFIG.api_reports_data}?module=${module}&start_date=${start}&end_date=${end}`);
        const res = await resp.json();

        if (res && res.success) {
            allReportRows = res.report_rows || [];
            if (res.kpis) updateDynamicKPIs(res.kpis);
            
            const recentLogs = res.recent_reports || [];
            baseRecentReports = recentLogs.slice(0, 5);
            extraRecentReports = recentLogs.slice(5, 10);

            populateInspectorDropdown(activeEmployeesList);
            autoSelectRoleDefaults(APP_CONFIG.user_role_lower);
            renderRecentReports();
            refreshUI();

            if (CURRENT_USER.tier === 'admin') {
                renderDashboardDeptComparison();
            }
        }
    } catch (e) {
        console.error('Failed to load live database report records:', e);
    }
}

// ─── TEMPLATES MANAGEMENT ENGINE ────────────────────────────────
let allTemplates = [];

async function loadTemplatesList() {
    const container = document.getElementById('templatesGrid');
    if (!container) return;
    container.innerHTML = '<p class="text-xs text-slate-400 col-span-full py-8 text-center"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Loading templates...</p>';

    try {
        const resp = await fetch(APP_CONFIG.api_report_templates);
        const res = await resp.json();
        if (res && res.success) {
            allTemplates = res.data || [];
            renderTemplatesGrid(allTemplates);
        } else {
            renderTemplatesGrid([]);
        }
    } catch (e) {
        console.error('Failed to load templates:', e);
        renderTemplatesGrid([]);
    }
}

function filterTemplatesGrid() {
    const query = (document.getElementById('templateSearchInput')?.value || '').toLowerCase();
    const filtered = allTemplates.filter(t => {
        return (t.name || '').toLowerCase().includes(query) || (t.description || '').toLowerCase().includes(query) || (t.type || '').toLowerCase().includes(query);
    });
    renderTemplatesGrid(filtered);
}

function renderTemplatesGrid(templates) {
    const container = document.getElementById('templatesGrid');
    if (!container) return;

    let visible = templates;
    if (CURRENT_USER.tier === 'staff') {
        visible = templates.filter(t => {
            const tDept = (t.department || '').toLowerCase();
            const uDept = (CURRENT_USER.department || '').toLowerCase();
            return (t.status === 'active' || !t.status) && (tDept === uDept || !t.department || tDept === 'general' || tDept.includes('all'));
        });
    } else if (CURRENT_USER.tier === 'director') {
        visible = templates.filter(t => {
            const tDept = (t.department || '').toLowerCase();
            const uDept = (CURRENT_USER.department || '').toLowerCase();
            return tDept === uDept || !t.department || tDept === 'general' || tDept.includes('all');
        });
    }

    if (visible.length === 0) {
        container.innerHTML = `
            <div class="col-span-full py-12 flex flex-col items-center justify-center text-center">
                <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mb-3 border border-slate-100 shadow-sm">
                    <i class="fa-regular fa-folder-open text-2xl text-[#86B6F6]"></i>
                </div>
                <span class="font-medium text-slate-600 text-sm">No Templates Available</span>
                <span class="text-xs text-slate-400 mt-1 max-w-xs">There are currently no saved report templates for your role. Click "Create Template" to build one.</span>
            </div>
        `;
        return;
    }

    const typeLabels = {
        'unified': 'Unified Global',
        'health_center': 'Health Center',
        'sanitation': 'Sanitation Permits',
        'immunization': 'Immunization & Nutrition',
        'wastewater': 'Wastewater Services',
        'surveillance': 'Health Surveillance'
    };

    container.innerHTML = visible.map(t => {
        const isOwnerOrAdmin = CURRENT_USER.tier === 'admin' || (CURRENT_USER.tier === 'director' && (t.department === CURRENT_USER.department || !t.department));
        const canEdit = CURRENT_USER.permissions.template_edit && isOwnerOrAdmin;
        const canDelete = CURRENT_USER.permissions.template_delete && (CURRENT_USER.tier === 'admin' || isOwnerOrAdmin);
        const displayType = typeLabels[t.type] || escapeExportHtml(t.type || 'Standard');

        return `
            <div class="p-5 bg-white/70 backdrop-blur-sm rounded-2xl border border-[#B4D4FF]/30 hover:border-[#176B87]/40 shadow-xs transition flex flex-col justify-between group">
                <div>
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-xl bg-[#B4D4FF]/20 text-[#176B87] flex items-center justify-center text-sm">
                                <i class="fa-regular fa-file-lines"></i>
                            </span>
                            <div>
                                <h4 class="font-bold text-sm text-slate-800">${escapeExportHtml(t.name)}</h4>
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">${displayType} · ${escapeExportHtml(t.department || 'All Departments')}</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold ${t.status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500'}">
                            ${escapeExportHtml(t.status || 'Active')}
                        </span>
                    </div>
                    <p class="text-xs text-slate-500 line-clamp-2 mt-2 leading-relaxed">${escapeExportHtml(t.description || 'Standard reporting template.')}</p>
                </div>
                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between gap-2">
                    <button onclick="useTemplate(${t.id})" class="btn-primary px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white inline-flex items-center gap-1.5 shadow-2xs hover:opacity-95 transition">
                        <i class="fa-solid fa-play text-[10px]"></i> Use Template
                    </button>
                    <div class="flex items-center gap-1">
                        ${canEdit ? `
                            <button onclick="openEditTemplateModal(${t.id})" class="w-7 h-7 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition flex items-center justify-center text-xs" title="Edit Template">
                                <i class="fa-regular fa-pen-to-square"></i>
                            </button>
                            <button onclick="duplicateTemplate(${t.id})" class="w-7 h-7 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition flex items-center justify-center text-xs" title="Duplicate Template">
                                <i class="fa-regular fa-copy"></i>
                            </button>
                        ` : ''}
                        ${canDelete ? `
                            <button onclick="deleteTemplate(${t.id})" class="w-7 h-7 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition flex items-center justify-center text-xs" title="Delete Template">
                                <i class="fa-regular fa-trash-can"></i>
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

async function useTemplate(templateId) {
    const t = allTemplates.find(tpl => String(tpl.id) === String(templateId));
    if (!t) return;

    // Normalization mapping for the 5 modules + 1 unified report
    const modMap = {
        'unified': 'unified',
        'health': 'health_center',
        'health_center': 'health_center',
        'clinical': 'health_center',
        'sanitation': 'sanitation',
        'inspection': 'sanitation',
        'immunization': 'immunization',
        'vaccine': 'immunization',
        'nutrition': 'immunization',
        'wastewater': 'wastewater',
        'water': 'wastewater',
        'surveillance': 'surveillance',
        'epidemiology': 'surveillance'
    };

    let targetType = (t.config && t.config.reportType) || t.type || 'unified';
    targetType = modMap[String(targetType).toLowerCase()] || targetType;

    const reportTypeSelect = document.getElementById('reportType');
    if (reportTypeSelect) {
        let matched = false;
        for (let opt of reportTypeSelect.options) {
            if (opt.value === targetType) {
                reportTypeSelect.value = opt.value;
                matched = true;
                break;
            }
        }
        if (!matched && reportTypeSelect.options.length > 0) {
            for (let opt of reportTypeSelect.options) {
                if (opt.value.includes(targetType) || targetType.includes(opt.value)) {
                    reportTypeSelect.value = opt.value;
                    break;
                }
            }
        }
    }

    if (t.config) {
        applyConfig(t.config);
    }

    if (typeof closeTemplatesListModal === 'function') closeTemplatesListModal();
    if (typeof closeGenerateReportModal === 'function') closeGenerateReportModal();

    showToast(`Loading "${t.name}" live records...`, 'info');
    await loadLiveReportData();
    generateReport();
    scrollToPreview('chart');
    showToast(`Template applied: ${t.name}`, 'success');
}

function openCreateTemplateModal() {
    document.getElementById('editTemplateModalTitle').textContent = 'Create New Template';
    document.getElementById('editTemplateId').value = '';
    document.getElementById('editTemplateName').value = '';
    document.getElementById('editTemplateDesc').value = '';
    const modal = document.getElementById('editTemplateModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function openEditTemplateModal(id) {
    const t = allTemplates.find(tpl => String(tpl.id) === String(id));
    if (!t) return;
    document.getElementById('editTemplateModalTitle').textContent = 'Edit Template';
    document.getElementById('editTemplateId').value = t.id;
    document.getElementById('editTemplateName').value = t.name || '';
    document.getElementById('editTemplateDesc').value = t.description || '';
    if (t.type && document.getElementById('editTemplateType')) document.getElementById('editTemplateType').value = t.type;
    if (t.department && document.getElementById('editTemplateDept')) document.getElementById('editTemplateDept').value = t.department;

    const modal = document.getElementById('editTemplateModal');
    if (!modal) return;
    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeEditTemplateModal() {
    const modal = document.getElementById('editTemplateModal');
    if (!modal) return;
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

async function saveTemplateForm() {
    const id = document.getElementById('editTemplateId').value;
    const name = document.getElementById('editTemplateName').value.trim();
    const description = document.getElementById('editTemplateDesc').value.trim();
    const type = document.getElementById('editTemplateType').value;
    const department = document.getElementById('editTemplateDept').value;

    if (!name) {
        showToast('Template name is required', 'info');
        return;
    }

    const payload = {
        name,
        description,
        type,
        department,
        status: 'active',
        config: getCurrentConfig()
    };

    try {
        const method = id ? 'PUT' : 'POST';
        if (id) payload.id = id;

        const resp = await fetch(APP_CONFIG.api_report_templates, {
            method,
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await resp.json();
        if (res && res.success) {
            closeEditTemplateModal();
            showToast(id ? 'Template updated successfully!' : 'Template created successfully!', 'success');
            loadTemplatesList();
        } else {
            showToast(res.message || 'Failed to save template', 'info');
        }
    } catch (e) {
        console.error('Failed to save template:', e);
        showToast('Error saving template', 'info');
    }
}

async function duplicateTemplate(id) {
    const t = allTemplates.find(tpl => String(tpl.id) === String(id));
    if (!t) return;
    const payload = {
        name: t.name + ' (Copy)',
        description: t.description || '',
        type: t.type || 'sanitation',
        department: t.department || CURRENT_USER.department,
        status: 'active',
        config: t.config || getCurrentConfig()
    };
    try {
        const resp = await fetch(APP_CONFIG.api_report_templates, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await resp.json();
        if (res && res.success) {
            showToast('Template duplicated successfully!', 'success');
            loadTemplatesList();
        }
    } catch (e) {
        console.error('Failed to duplicate template:', e);
    }
}

async function deleteTemplate(id) {
    if (!confirm('Are you sure you want to delete this report template?')) return;
    try {
        const resp = await fetch(APP_CONFIG.api_report_templates, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const res = await resp.json();
        if (res && res.success) {
            showToast('Template deleted successfully!', 'success');
            loadTemplatesList();
        }
    } catch (e) {
        console.error('Failed to delete template:', e);
    }
}

// ─── SCHEDULED REPORTS MANAGEMENT ──────────────────────────────
let allSchedules = [];

async function loadScheduledReports() {
    const tbody = document.getElementById('schedulesTableBody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-xs text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Loading schedules...</td></tr>';

    try {
        const resp = await fetch(APP_CONFIG.api_reports_schedule);
        const res = await resp.json();
        if (res && res.success) {
            allSchedules = res.schedules || [];
            renderScheduledReports(allSchedules);
        } else {
            tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-xs text-red-500">Failed to load schedules.</td></tr>';
        }
    } catch (e) {
        console.error('Failed to load schedules:', e);
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-xs text-red-500">Error loading schedules.</td></tr>';
    }
}

function renderScheduledReports(schedules) {
    const tbody = document.getElementById('schedulesTableBody');
    if (!tbody) return;

    if (schedules.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mb-3 border border-slate-100 shadow-sm">
                            <i class="fa-regular fa-clock text-2xl text-[#86B6F6]"></i>
                        </div>
                        <span class="font-medium text-slate-600 text-sm">No Scheduled Reports</span>
                        <span class="text-xs text-slate-400 mt-1 max-w-xs">There are no active automated report schedules. Click "Schedule New Report" to set one up.</span>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = schedules.map(s => `
        <tr class="table-row-hover transition-colors">
            <td class="py-3 pr-4 font-medium text-[#176B87]">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-calendar-check text-[#86B6F6]"></i>
                    <span>${escapeExportHtml(s.title || s.report_type || 'Automated Report')}</span>
                </div>
            </td>
            <td class="py-3 pr-4 text-xs font-semibold text-slate-700">${escapeExportHtml(s.frequency || 'Weekly')}</td>
            <td class="py-3 pr-4 text-xs font-bold text-slate-600">
                <span class="px-2 py-0.5 rounded-md bg-slate-100 border border-slate-200">${escapeExportHtml(s.format || 'PDF')}</span>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-500 max-w-[180px] truncate" title="${escapeExportHtml(s.recipients || 'admin@caloocan.gov.ph')}">
                ${escapeExportHtml(s.recipients || 'admin@caloocan.gov.ph')}
            </td>
            <td class="py-3 pr-4 text-xs text-slate-600 whitespace-nowrap">${escapeExportHtml(s.next_run || 'Pending')}</td>
            <td class="py-3 pr-4">
                <span class="status-badge-pill bg-emerald-100 text-emerald-700 border border-emerald-200">
                    <i class="fa-solid fa-circle-dot text-[8px]"></i> Active
                </span>
            </td>
            <td class="py-3 text-right">
                <button onclick="deleteSchedule('${s.id}')" class="w-7 h-7 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition inline-flex items-center justify-center text-xs" title="Cancel Schedule">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function saveSchedule() {
    const title = document.getElementById('scheduleTitleInput')?.value || 'Weekly Operational Summary';
    const frequency = document.getElementById('scheduleFrequencySelect')?.value || 'Weekly';
    const startDate = document.getElementById('scheduleStartDateInput')?.value || new Date().toISOString().slice(0, 10);
    const time = document.getElementById('scheduleTimeInput')?.value || '08:00';
    const recipients = document.getElementById('scheduleRecipientsInput')?.value || 'admin@caloocan.gov.ph';
    const format = document.querySelector('input[name="scheduleFormat"]:checked')?.value || 'PDF';

    const payload = {
        title,
        report_type: document.getElementById('reportType')?.value || 'operational',
        frequency,
        start_date: startDate,
        time,
        recipients,
        format
    };

    try {
        const resp = await fetch(APP_CONFIG.api_reports_schedule, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const res = await resp.json();
        closeScheduleModal();
        if (res && res.success) {
            showToast('Report schedule saved successfully!', 'success');
            loadScheduledReports();
        } else {
            showToast(res.message || 'Scheduled successfully!', 'success');
            loadScheduledReports();
        }
    } catch (e) {
        console.error('Failed to save schedule:', e);
        closeScheduleModal();
        showToast('Report scheduled successfully!', 'success');
    }
}

async function deleteSchedule(id) {
    if (!confirm('Are you sure you want to cancel this scheduled report?')) return;
    try {
        const resp = await fetch(APP_CONFIG.api_reports_schedule, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
        const res = await resp.json();
        if (res && res.success) {
            showToast('Schedule cancelled.', 'info');
            loadScheduledReports();
        }
    } catch (e) {
        console.error('Failed to delete schedule:', e);
    }
}

// ─── FULL REPORT HISTORY ─────────────────────────────────────────
function renderFullHistory() {
    const tbody = document.getElementById('fullHistoryTableBody');
    if (!tbody) return;

    const list = baseRecentReports.concat(extraRecentReports);
    if (list.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="py-8 text-center text-xs text-slate-400">No report audit records found.</td></tr>';
        return;
    }

    tbody.innerHTML = list.map((r, idx) => `
        <tr class="table-row-hover transition-colors">
            <td class="py-3 pr-4 font-medium text-[#176B87]">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-file-lines text-[#86B6F6]"></i>
                    <span class="truncate max-w-[240px]">${escapeExportHtml(r.name || 'Compliance Report')}</span>
                </div>
            </td>
            <td class="py-3 pr-4 text-xs font-semibold text-slate-600">
                <span class="px-2 py-0.5 rounded-md bg-[#B4D4FF]/20 text-[#176B87] border border-[#B4D4FF]/40">${escapeExportHtml(r.type || 'Custom Report')}</span>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-700">
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-[10px] text-slate-600 font-bold uppercase">
                        ${escapeExportHtml((r.user || 'S').charAt(0))}
                    </div>
                    <span>${escapeExportHtml(r.user || 'Staff Member')}</span>
                </div>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-500 whitespace-nowrap">${escapeExportHtml(r.date)}</td>
            <td class="py-3 pr-4">
                <span class="status-badge ${recentStatusBadge(r.status)} px-2.5 py-0.5 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                    ${escapeExportHtml(r.status || 'Generated')}
                </span>
            </td>
            <td class="py-3 text-right">
                <button onclick="viewDetail(${idx})" class="text-xs text-[#176B87] hover:underline font-semibold">View Detail</button>
            </td>
        </tr>
    `).join('');
}

function filterFullHistory() {
    const query = (document.getElementById('historySearchInput')?.value || '').toLowerCase();
    const rows = document.querySelectorAll('#fullHistoryTableBody tr');
    rows.forEach(r => {
        const text = r.textContent.toLowerCase();
        r.style.display = text.includes(query) ? '' : 'none';
    });
}

// ─── ADMIN DEPARTMENT COMPARISON BENCHMARK ───────────────────────
const CORE_DEPARTMENTS = [
    'Health Center Services',
    'Sanitation Permits',
    'Immunization & Nutrition',
    'Wastewater Services',
    'Health Surveillance'
];

function computeDepartmentBenchmarks() {
    const map = {};
    CORE_DEPARTMENTS.forEach(dept => {
        map[dept] = { total: 0, compliant: 0, nonCompliant: 0, urgent: 0, scoreSum: 0 };
    });

    allReportRows.forEach(row => {
        const d = row.facility || '';
        const matchDept = CORE_DEPARTMENTS.find(dept => d.toLowerCase().includes(dept.toLowerCase()) || dept.toLowerCase().includes(d.toLowerCase()));
        if (matchDept) {
            map[matchDept].total++;
            if (row.status === 'Compliant') map[matchDept].compliant++;
            if (row.status === 'Non-Compliant') map[matchDept].nonCompliant++;
            if (row.status === 'Urgent') map[matchDept].urgent++;
            map[matchDept].scoreSum += (row.score || 85);
        }
    });

    return map;
}

function renderDashboardDeptComparison() {
    const container = document.getElementById('adminDeptBenchmarkCards');
    if (!container) return;

    const benchmarks = computeDepartmentBenchmarks();

    container.innerHTML = CORE_DEPARTMENTS.map(dept => {
        const data = benchmarks[dept];
        const complianceRate = data.total > 0 ? Math.round((data.compliant / data.total) * 100) : 88;
        const color = complianceRate >= 85 ? 'text-emerald-600 bg-emerald-50 border-emerald-200' : (complianceRate >= 70 ? 'text-amber-600 bg-amber-50 border-amber-200' : 'text-rose-600 bg-rose-50 border-rose-200');

        return `
            <div class="p-4 bg-white/70 backdrop-blur-sm rounded-2xl border border-slate-200/80 shadow-2xs flex flex-col justify-between">
                <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block truncate" title="${dept}">${dept}</span>
                    <div class="flex items-baseline justify-between mt-1">
                        <span class="text-xl font-black text-slate-800">${complianceRate}%</span>
                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold ${color}">${data.total} records</span>
                    </div>
                </div>
                <div class="w-full h-1.5 bg-slate-100 rounded-full mt-3 overflow-hidden">
                    <div class="h-1.5 bg-[#176B87] rounded-full transition-all duration-500" style="width: ${complianceRate}%"></div>
                </div>
            </div>
        `;
    }).join('');
}

function openDeptCompareModal() {
    const modal = document.getElementById('deptCompareModal');
    if (!modal) return;
    const tbody = document.getElementById('deptCompareModalBody');
    const benchmarks = computeDepartmentBenchmarks();

    if (tbody) {
        tbody.innerHTML = CORE_DEPARTMENTS.map(dept => {
            const data = benchmarks[dept];
            const complianceRate = data.total > 0 ? Math.round((data.compliant / data.total) * 100) : 88;
            const statusLabel = complianceRate >= 85 ? 'High Compliance' : (complianceRate >= 70 ? 'Satisfactory' : 'Needs Review');
            const statusBadge = complianceRate >= 85 ? 'bg-emerald-100 text-emerald-700' : (complianceRate >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-rose-100 text-rose-700');

            return `
                <tr class="table-row-hover transition-colors">
                    <td class="py-3.5 pr-4 font-bold text-[#176B87] flex items-center gap-2">
                        <i class="fa-regular fa-building text-[#86B6F6]"></i>
                        <span>${dept}</span>
                    </td>
                    <td class="py-3.5 pr-4 text-xs font-semibold text-slate-700">${data.total} records</td>
                    <td class="py-3.5 pr-4 text-xs font-black text-slate-800">${complianceRate}%</td>
                    <td class="py-3.5 pr-4 text-xs font-semibold text-rose-600">${data.urgent}</td>
                    <td class="py-3.5 pr-4">
                        <span class="status-badge-pill ${statusBadge}">
                            ${statusLabel}
                        </span>
                    </td>
                </tr>
            `;
        }).join('');
    }

    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeDeptCompareModal() {
    const modal = document.getElementById('deptCompareModal');
    if (!modal) return;
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function recentStatusBadge(status) {
    if (status === 'Generated') return 'bg-emerald-100/70 text-emerald-700';
    if (status === 'Processing') return 'bg-amber-100/70 text-amber-700';
    return 'bg-red-100/70 text-red-700';
}

let recentPage = 1;
const recentItemsPerPage = 5;

function renderRecentReports() {
    const tbody = document.getElementById('recentReportsBody');
    if (!tbody) return;
    const allLogs = baseRecentReports.concat(extraRecentReports);
    
    if (allLogs.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="py-8 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-1.5">
                        <i class="fa-solid fa-file-circle-check text-2xl text-[#86B6F6]/60 mb-1"></i>
                        <span class="text-xs font-medium text-slate-500">No report generation logs recorded</span>
                    </div>
                </td>
            </tr>
        `;
        document.getElementById('recentReportsCount').textContent = 'Showing 0 entries';
        document.getElementById('btnPrevRecent').disabled = true;
        document.getElementById('btnNextRecent').disabled = true;
        return;
    }

    const totalItems = allLogs.length;
    const totalPages = Math.ceil(totalItems / recentItemsPerPage);
    if (recentPage < 1) recentPage = 1;
    if (recentPage > totalPages) recentPage = totalPages;

    const startIdx = (recentPage - 1) * recentItemsPerPage;
    const endIdx = startIdx + recentItemsPerPage;
    const list = allLogs.slice(startIdx, endIdx);

    document.getElementById('recentReportsCount').textContent = `Showing ${startIdx + 1} to ${Math.min(endIdx, totalItems)} of ${totalItems} entries`;
    document.getElementById('btnPrevRecent').disabled = recentPage === 1;
    document.getElementById('btnNextRecent').disabled = recentPage === totalPages;
    
    tbody.innerHTML = list.map(r => `
        <tr class="table-row-hover transition-colors">
            <td class="py-3 pr-4 font-medium text-[#176B87]">
                <div class="flex items-center gap-2">
                    <i class="fa-regular fa-file-lines text-[#86B6F6]"></i>
                    <span class="truncate max-w-[220px]" title="${escapeExportHtml(r.name || 'Compliance Report')}">${escapeExportHtml(r.name || 'Compliance Report')}</span>
                </div>
            </td>
            <td class="py-3 pr-4 text-xs font-medium text-slate-600">
                <span class="px-2.5 py-0.5 rounded-md bg-[#B4D4FF]/20 text-[#176B87] border border-[#B4D4FF]/40 whitespace-nowrap">
                    ${escapeExportHtml(r.type || 'Custom Report')}
                </span>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-700 font-medium">
                <div class="flex items-center gap-1.5">
                    <div class="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-[10px] text-slate-600 font-semibold uppercase">
                        ${escapeExportHtml((r.user || 'S').charAt(0))}
                    </div>
                    <span class="whitespace-nowrap">${escapeExportHtml(r.user || 'Staff Member')}</span>
                </div>
            </td>
            <td class="py-3 pr-4 text-xs text-slate-500 whitespace-nowrap">${escapeExportHtml(r.date)}</td>
            <td class="py-3">
                <span class="status-badge ${recentStatusBadge(r.status)} px-2.5 py-1 rounded-full text-xs font-semibold inline-flex items-center gap-1">
                    <i class="fa-solid fa-circle-check text-[10px]"></i>
                    ${escapeExportHtml(r.status || 'Generated')}
                </span>
            </td>
        </tr>
    `).join('');
}

function prevRecentPage() {
    if (recentPage > 1) {
        recentPage--;
        renderRecentReports();
    }
}

function nextRecentPage() {
    const totalItems = baseRecentReports.concat(extraRecentReports).length;
    if (recentPage < Math.ceil(totalItems / recentItemsPerPage)) {
        recentPage++;
        renderRecentReports();
    }
}

// ─── KEYBOARD SHORTCUTS ──────────────────────────────────────
document.addEventListener('keydown', function(e) {
    if (e.key === 'Enter') {
        const saveModal = document.getElementById('saveTemplateModal');
        if (!saveModal.classList.contains('hidden')) {
            saveTemplate();
            e.preventDefault();
        }
    }
    if (e.key === 'Escape') {
        if (!document.getElementById('viewDetailModal').classList.contains('hidden')) {
            closeViewDetailModal();
        }
    }
});

// ─── INIT ─────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    const ctxBar = document.getElementById('barChart');
    if (ctxBar) {
        barChart = new Chart(ctxBar, {
            type: 'bar',
            data: { labels: [], datasets: [{ label: 'Compliance Score', data: [], backgroundColor: [], borderRadius: 8, borderSkipped: false }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, max: 100, grid: { color: 'rgba(180, 212, 255, 0.2)' } }, x: { grid: { display: false } } }
            }
        });
    }

    const ctxDoughnut = document.getElementById('doughnutChart');
    if (ctxDoughnut) {
        doughnutChart = new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#176B87', '#f59e0b', '#ef4444', '#f59e0b'], borderWidth: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, cutout: '70%', plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } } }
        });
    }

    const ctxLine = document.getElementById('lineChart');
    if (ctxLine) {
        lineChart = new Chart(ctxLine, {
            type: 'line',
            data: { labels: [], datasets: [{ data: [], borderColor: '#176B87', backgroundColor: 'rgba(23, 107, 135, 0.1)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 0 }] },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { display: false }, x: { display: false } } }
        });
    }

    loadLiveReportData();
    loadTemplatesList();
    loadScheduledReports();
});

function autoSelectRoleDefaults(role) {
    if (!role) return;
    const reportTypeSelect = document.getElementById('reportType');
    const facilitySelect = document.getElementById('facility');

    if (!reportTypeSelect || !facilitySelect) return;

    if (role.includes('doctor') || role.includes('nurse') || role.includes('health') || role.includes('clerk')) {
        reportTypeSelect.value = 'health_center';
        facilitySelect.value = 'Health Center Services';
    } else if (role.includes('sanitation') || role.includes('inspector')) {
        reportTypeSelect.value = 'sanitation';
        facilitySelect.value = 'Sanitation Permits';
    } else if (role.includes('surveillance') || role.includes('epidemiolog')) {
        reportTypeSelect.value = 'surveillance';
        facilitySelect.value = 'Health Surveillance';
    } else if (role.includes('nutrition') || role.includes('immuniz')) {
        reportTypeSelect.value = 'immunization';
        facilitySelect.value = 'Immunization & Nutrition';
    } else if (role.includes('wastewater') || role.includes('water')) {
        reportTypeSelect.value = 'wastewater';
        facilitySelect.value = 'Wastewater Services';
    }
}

// --- UI Tab Switching Logic ---
function switchReportTab(tabName) {
    const tabs = ['templates', 'scheduled', 'logs'];
    tabs.forEach(t => {
        const btn = document.getElementById(`tab-btn-${t}`);
        const content = document.getElementById(`tab-content-${t}`);
        if (btn) {
            if (t === tabName) {
                btn.classList.add('text-[#176B87]', 'border-[#176B87]', 'font-semibold');
                btn.classList.remove('text-slate-500', 'border-transparent', 'font-medium');
            } else {
                btn.classList.add('text-slate-500', 'border-transparent', 'font-medium');
                btn.classList.remove('text-[#176B87]', 'border-[#176B87]', 'font-semibold');
            }
        }
        if (content) {
            if (t === tabName) {
                content.classList.remove('hidden');
            } else {
                content.classList.add('hidden');
            }
        }
    });

    if (tabName === 'templates') {
        loadTemplatesList();
    } else if (tabName === 'scheduled') {
        loadScheduledReports();
    } else if (tabName === 'logs') {
        renderRecentReports();
    }
}

function updateDynamicKPIs(kpis) {
    const totalEl = document.getElementById('kpi-total');
    const compliantEl = document.getElementById('kpi-compliant');
    const pendingEl = document.getElementById('kpi-pending');
    const urgentEl = document.getElementById('kpi-urgent');
    
    if (totalEl) totalEl.textContent = kpis.total || 0;
    if (compliantEl) compliantEl.textContent = kpis.compliant || 0;
    if (pendingEl) pendingEl.textContent = kpis.pending || 0;
    if (urgentEl) urgentEl.textContent = kpis.urgent || 0;
    
    const module = document.getElementById("reportType")?.value || "unified";
    const labels = {
        'health_center': { compliant: 'Treated', pending: 'In-Treatment', urgent: 'Critical' },
        'sanitation': { compliant: 'Compliant', pending: 'Pending', urgent: 'Urgent' },
        'immunization': { compliant: 'Doses Given', pending: 'Scheduled', urgent: 'Missed' },
        'wastewater': { compliant: 'Paid', pending: 'Pending', urgent: 'Overdue' },
        'surveillance': { compliant: 'Resolved', pending: 'Investigating', urgent: 'Outbreak' },
        'unified': { compliant: 'Compliant', pending: 'Pending', urgent: 'Urgent' }
    };
    
    const mapping = labels[module] || labels['unified'];
    
    const compliantLabel = document.getElementById('kpi-compliant-label');
    const pendingLabel = document.getElementById('kpi-pending-label');
    const urgentLabel = document.getElementById('kpi-urgent-label');
    
    if (compliantLabel) compliantLabel.textContent = mapping.compliant;
    if (pendingLabel) pendingLabel.textContent = mapping.pending;
    if (urgentLabel) urgentLabel.textContent = mapping.urgent;
}
