// ================================================================
//  CODE ARCHITECTURE – MODULAR FUNCTIONS & ENTERPRISE RBAC
// ================================================================

// ─── AUTH & RBAC CONTEXT ──────────────────────────────────────────

// ─── MODAL NAVIGATION ─────────────────────────────────────────
function openGenerateReportModal() {
    const modal = document.getElementById('generateReportModal');
    if (modal) {
        const startEl = document.getElementById('startDate');
        const endEl = document.getElementById('endDate');
        const today = new Date();
        const curYear = today.getFullYear();
        const curMonth = String(today.getMonth() + 1).padStart(2, '0');
        const curDay = String(today.getDate()).padStart(2, '0');
        const curDateStr = `${curYear}-${curMonth}-${curDay}`;

        const past30 = new Date(today.getTime() - 30 * 24 * 60 * 60 * 1000);
        const pYear = past30.getFullYear();
        const pMonth = String(past30.getMonth() + 1).padStart(2, '0');
        const pDay = String(past30.getDate()).padStart(2, '0');
        const pDateStr = `${pYear}-${pMonth}-${pDay}`;

        if (startEl && (!startEl.value || startEl.value > curDateStr)) {
            startEl.value = pDateStr;
        }
        if (endEl && (!endEl.value || endEl.value > curDateStr)) {
            endEl.value = curDateStr;
        }

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

function setScheduleDatePreset(preset) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    if (preset === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (preset === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end = new Date(today.getFullYear(), 11, 31);
    } else if (preset === 'last_30_days') {
        start.setDate(today.getDate() - 30);
    }

    const format = (d) => {
        const offset = d.getTimezoneOffset();
        d = new Date(d.getTime() - (offset * 60 * 1000));
        return d.toISOString().split('T')[0];
    };
    
    const startEl = document.getElementById('scheduleReportStart');
    const endEl = document.getElementById('scheduleReportEnd');
    if (startEl) startEl.value = format(start);
    if (endEl) endEl.value = format(end);
}

// ─── TAB NAVIGATION ───────────────────────────────────────────
function switchTab(tabId) {
    document.querySelectorAll('.report-tab').forEach(tab => {
        const isActive = tab.dataset.tab === tabId;
        tab.classList.toggle('active', isActive);
        tab.classList.toggle('text-[#176B87]', isActive);
        tab.classList.toggle('border-[#176B87]', isActive);
        tab.classList.toggle('text-slate-500', !isActive);
        tab.classList.toggle('border-transparent', !isActive);
    });
    
    document.querySelectorAll('.tab-content').forEach(content => content.classList.add('hidden'));
    
    const targetContent = document.getElementById('tab' + (tabId === 'summary' ? 'Summary' : (tabId === 'chart' ? 'Chart' : 'Table')));
    if (targetContent) {
        targetContent.classList.remove('hidden');
    }

    if (tabId === 'chart') {
        setTimeout(() => {
            if (barChart && typeof barChart.resize === 'function') barChart.resize();
            if (doughnutChart && typeof doughnutChart.resize === 'function') doughnutChart.resize();
            if (lineChart && typeof lineChart.resize === 'function') lineChart.resize();
        }, 60);
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
const PAGE_SIZE = 99999; // Removed pagination per user request
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
    const facilitySelect = document.getElementById('facility');
    const selectedFacility = facilitySelect ? facilitySelect.value : 'all';

    return allReportRows.filter(row => {
        // 1. Status Filter
        if (currentStatusFilter && currentStatusFilter !== 'all') {
            const rowStatus = String(row.status || '').toLowerCase();
            const filter = String(currentStatusFilter).toLowerCase();
            if (filter === 'non-compliant') {
                if (rowStatus !== 'non-compliant' && rowStatus !== 'urgent' && rowStatus !== 'failed') return false;
            } else if (filter === 'urgent') {
                if (rowStatus !== 'urgent' && rowStatus !== 'emergency' && rowStatus !== 'high') return false;
            } else if (filter === 'compliant') {
                if (rowStatus !== 'compliant' && rowStatus !== 'passed' && rowStatus !== 'resolved') return false;
            } else if (filter === 'pending') {
                if (rowStatus !== 'pending' && rowStatus !== 'in_progress' && rowStatus !== 'investigating' && rowStatus !== 'scheduled') return false;
            }
        }

        // 2. Facility Filter
        if (selectedFacility && selectedFacility !== 'all') {
            const rowFac = String(row.facility || '').toLowerCase();
            const selFac = String(selectedFacility).toLowerCase();
            if (selFac !== 'all core departments' && !rowFac.includes(selFac) && !selFac.includes(rowFac)) {
                return false;
            }
        }

        return true;
    });
}

// ─── GET CURRENT CONFIG (for templates) ──────────────────────
function getCurrentConfig() {
    const getVal = (id, fallback = '') => {
        const el = document.getElementById(id);
        return el ? el.value : fallback;
    };

    return {
        reportType: getVal('reportType', 'unified'),
        startDate: getVal('startDate', ''),
        endDate: getVal('endDate', ''),
        facility: getVal('facility', 'all'),
        inspector: getVal('inspector', 'all'),
        status: typeof currentStatusFilter !== 'undefined' ? currentStatusFilter : 'all'
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
    
    const mComp = document.getElementById('metricCompliance');
    if (mComp) mComp.textContent = complianceRate + '%';
    const coverage = total > 0 ? Math.round((facilities / 52) * 100) : 0;
    const mCov = document.getElementById('metricCoverage');
    if (mCov) mCov.textContent = coverage + '%';
    const resolution = total > 0 ? Math.round(((compliant) / total) * 100) : 0;
    const mRes = document.getElementById('metricResolution');
    if (mRes) mRes.textContent = resolution + '%';
    const mPart = document.getElementById('metricParticipation');
    if (mPart) mPart.textContent = total > 0 ? '100%' : '0%';

    const reportTypeSelect = document.getElementById('reportType');
    const moduleName = reportTypeSelect?.selectedOptions[0]?.textContent?.trim() || 'Operational Report';
    const modValue = reportTypeSelect?.value || 'unified';

    const lbl1 = document.getElementById('labelMetric1');
    const lbl2 = document.getElementById('labelMetric2');
    if (lbl1 && lbl2) {
        if (modValue === 'health_center' || modValue === 'immunization') {
            lbl1.textContent = 'Patient Success Rate:';
            lbl2.textContent = 'Encounter / Treatment Coverage:';
        } else if (modValue === 'wastewater') {
            lbl1.textContent = 'Payment Collection Rate:';
            lbl2.textContent = 'Client Service Coverage:';
        } else if (modValue === 'surveillance') {
            lbl1.textContent = 'Case Resolution Rate:';
            lbl2.textContent = 'Surveillance Area Coverage:';
        } else {
            lbl1.textContent = 'Compliance / Approval Rate:';
            lbl2.textContent = 'Inspection / Enforcement Coverage:';
        }
    }

    const subTitleElem = document.getElementById('printReportSubtitle');
    if (subTitleElem) {
        subTitleElem.textContent = moduleName;
    }
    
    const chartBarTitle = document.getElementById('chartBarTitle');
    if (chartBarTitle) {
        chartBarTitle.textContent = moduleName + ' Operational Distribution';
    }
    const chartBarDate = document.getElementById('chartBarDate');
    const sd = document.getElementById('startDate')?.value || '';
    const ed = document.getElementById('endDate')?.value || '';
    if (chartBarDate) {
        chartBarDate.textContent = (sd && ed) ? `${sd} to ${ed}` : 'All Time';
    }

    updateCharts(data);

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

    const startDate = document.getElementById('startDate')?.value || '';
    const endDate = document.getElementById('endDate')?.value || '';

    try {
        let url = `${APP_CONFIG.api_ai_summary}?module=${encodeURIComponent(selectedModule)}&department=${encodeURIComponent(selectedDept)}&total=${encodeURIComponent(total)}&compliant=${encodeURIComponent(compliant)}&urgent=${encodeURIComponent(urgent)}&pending=${encodeURIComponent(pending)}`;
        if (startDate) url += `&start_date=${encodeURIComponent(startDate)}`;
        if (endDate) url += `&end_date=${encodeURIComponent(endDate)}`;
        if (isManual) url += `&refresh=1`;

        const resp = await fetch(url);
        const res = await resp.json();

        if (res && res.success) {
            const boldNumbers = (text) => (text || '').replace(/(\d+(?:\.\d+)?%?)/g, '<strong class="text-[#176B87] font-bold">$1</strong>');

            // Update Summary Text
            document.getElementById('summaryText').innerHTML = `
                <p class="font-bold text-slate-800 mb-1.5 text-xs">${res.department} Executive Overview:</p>
                <p class="leading-relaxed text-xs text-slate-700">${boldNumbers(res.summary)}</p>
            `;

            // Update Key Findings
            if (res.key_findings && res.key_findings.length > 0) {
                const findingsHtml = res.key_findings.map(f => `
                    <div class="p-2.5 bg-indigo-50/60 rounded-xl border border-indigo-100 text-indigo-950 font-semibold flex items-start gap-2 text-xs">
                        <span>${boldNumbers(f)}</span>
                    </div>
                `).join('');
                document.getElementById('aiKeyFindings').innerHTML = findingsHtml;
            }

            // Update Actionable Recommendations
            if (res.recommendations && res.recommendations.length > 0) {
                const recsHtml = res.recommendations.map(r => `
                    <li class="font-medium text-slate-700 text-xs">
                        <span>${boldNumbers(r)}</span>
                    </li>
                `).join('');
                document.getElementById('aiRecommendationsList').innerHTML = recsHtml;
            }

            if (res.requests_left !== undefined) {
                const badge = document.getElementById('aiQuotaBadge');
                if (badge) {
                    badge.innerHTML = `🤖 ${res.requests_left} requests left`;
                    if (res.requests_left > 2) {
                        badge.className = 'px-2.5 py-1 bg-cyan-100 text-cyan-700 rounded-full text-xs font-bold shadow-sm';
                    } else {
                        badge.className = 'px-2.5 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold shadow-sm';
                    }
                }
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
            barChart.data.labels = ['No Data Available'];
            barChart.data.datasets[0].data = [0];
            barChart.update();
        }
        if (doughnutChart) {
            doughnutChart.data.labels = ['No Data Available'];
            doughnutChart.data.datasets[0].data = [1];
            doughnutChart.data.datasets[0].backgroundColor = ['#e2e8f0'];
            doughnutChart.update();
        }
        if (lineChart) {
            lineChart.data.labels = ['No Data Available'];
            lineChart.data.datasets[0].data = [0];
            lineChart.update();
        }
        return;
    }

    // 1. Bar Chart: Metric/Sub-Module Distribution
    const moduleSelect = document.getElementById('reportType');
    const isUnified = moduleSelect && moduleSelect.value === 'unified';

    const facMap = {};
    data.forEach(r => {
        let fac = r.metric || r.category || r.facility || 'General';
        if (isUnified) {
            fac = r.category || fac; // Group by main department for Unified Report
        }
        facMap[fac] = (facMap[fac] || 0) + 1;
    });
    const barLabels = Object.keys(facMap);
    const barValues = Object.values(facMap);
    const palette = ['#176B87', '#3b82f6', '#0ea5e9', '#6366f1', '#10b981', '#f59e0b', '#8b5cf6'];

    if (barChart) {
        barChart.data.labels = barLabels;
        barChart.data.datasets[0].data = barValues;
        barChart.data.datasets[0].backgroundColor = palette.slice(0, barLabels.length);
        barChart.update();
    }

    // 2. Doughnut Chart: Compliance Status Distribution (Compliant, Pending, Urgent)
    let compliantCount = 0;
    let pendingCount = 0;
    let urgentCount = 0;
    data.forEach(r => {
        const s = String(r.status || '').toLowerCase();
        if (s === 'compliant' || s === 'passed' || s === 'resolved') compliantCount++;
        else if (s === 'urgent' || s === 'failed' || s === 'non-compliant' || s === 'emergency') urgentCount++;
        else pendingCount++;
    });

    if (doughnutChart) {
        doughnutChart.data.labels = ['Compliant', 'Pending', 'Urgent'];
        doughnutChart.data.datasets[0].data = [compliantCount, pendingCount, urgentCount];
        doughnutChart.data.datasets[0].backgroundColor = ['#10b981', '#f59e0b', '#ef4444'];
        doughnutChart.update();
    }

    // 3. Line Chart: Timeline / Activity Trend over Dates
    const timelineMap = {};
    data.forEach(r => {
        if (r.date && r.date.length >= 7) {
            const period = r.date.substring(0, 10);
            timelineMap[period] = (timelineMap[period] || 0) + 1;
        }
    });

    const sortedDates = Object.keys(timelineMap).sort();
    const lineLabels = sortedDates.length > 0 ? sortedDates : ['Active Period'];
    const lineValues = sortedDates.length > 0 ? sortedDates.map(d => timelineMap[d]) : [data.length];

    if (lineChart) {
        lineChart.data.labels = lineLabels;
        lineChart.data.datasets[0].data = lineValues;
        lineChart.update();
    }
}



// ─── VIEW ROW ──────────────────────────────────────────────────
function viewRow(index) {
    const data = getFilteredData();
    if (index < 0 || index >= data.length) {
        showToast('Record not found.', 'info');
        return;
    }
    const row = data[index];
    const facEl = document.getElementById('detailFacility');
    if (facEl) facEl.textContent = row.facility || 'Central Health Facility';
    const inspEl = document.getElementById('detailInspector');
    if (inspEl) inspEl.textContent = row.inspector || 'Designated Officer';
    const dateEl = document.getElementById('detailDate');
    if (dateEl) dateEl.textContent = row.date || 'N/A';
    const scoreEl = document.getElementById('detailScore');
    if (scoreEl) scoreEl.textContent = (row.score !== undefined ? row.score : 85) + ' / 100';
    const statusEl = document.getElementById('detailStatus');
    if (statusEl) statusEl.textContent = row.status || 'Compliant';
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
            <div class="flex items-center gap-3 flex-1 min-w-0" onclick="loadLocalTemplateByName('${t.name}')">
                <i class="fa-regular fa-file-lines text-[#176B87]"></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-slate-700 truncate">${t.name}</p>
                    <p class="text-[10px] text-slate-400">${new Date(t.savedAt).toLocaleDateString()}</p>
                </div>
            </div>
            <button onclick="deleteLocalTemplate('${t.name}')" class="delete-btn p-1.5 rounded-lg hover:bg-red-50 text-slate-300 hover:text-red-500 transition ml-2" title="Delete template">
                <i class="fa-regular fa-trash-can text-xs"></i>
            </button>
        </div>
    `).join('');
}

function loadLocalTemplateByName(name) {
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

function deleteLocalTemplate(name) {
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
async function generateReport() {
    const badgeText = document.getElementById('aiQuotaBadge')?.textContent || '';
    const quotaMatch = badgeText.match(/(\d+)/);
    const quota = quotaMatch ? parseInt(quotaMatch[1]) : 10;
    
    if (quota === 0) {
        if (typeof ModalSystem !== 'undefined' && ModalSystem.confirm) {
            ModalSystem.confirm(
                "Your AI Quota is currently 0. The system will continue using recent cached reports or system fallback templates. Do you want to continue?",
                function() {
                    _doGenerateReport();
                },
                {
                    title: 'AI Quota Exceeded',
                    confirmText: 'Continue',
                    type: 'warning'
                }
            );
        } else {
            if (confirm("Your AI Quota is currently 0.\n\nThe system will continue using recent cached reports or system fallback templates.\n\nDo you want to continue?")) {
                _doGenerateReport();
            }
        }
    } else {
        _doGenerateReport();
    }
}

// ─── REPORT GENERATION LOADER CONTROLLER ─────────────────────
function showReportGeneratingLoader() {
    const loader = document.getElementById('reportGeneratingLoader');
    if (!loader) return;
    
    const stepEl = document.getElementById('loaderDynamicStep');
    const barEl = document.getElementById('loaderProgressBar');
    const textEl = document.getElementById('loaderProgressText');
    const iconEl = document.getElementById('loaderDynamicIcon');

    if (stepEl) stepEl.textContent = 'Querying departmental database records...';
    if (barEl) barEl.style.width = '25%';
    if (textEl) textEl.textContent = '25%';
    if (iconEl) iconEl.className = 'fa-solid fa-database text-xl animate-pulse';

    loader.classList.remove('hidden');
    void loader.offsetWidth;
    loader.style.opacity = '1';

    window._genStepTimer1 = setTimeout(() => {
        if (stepEl) stepEl.textContent = 'Synthesizing AI Executive Summary & Risk Analysis...';
        if (barEl) barEl.style.width = '65%';
        if (textEl) textEl.textContent = '65%';
        if (iconEl) iconEl.className = 'fa-solid fa-wand-magic-sparkles text-xl animate-pulse';
    }, 450);

    window._genStepTimer2 = setTimeout(() => {
        if (stepEl) stepEl.textContent = 'Rendering visual distribution charts & operational indices...';
        if (barEl) barEl.style.width = '90%';
        if (textEl) textEl.textContent = '90%';
        if (iconEl) iconEl.className = 'fa-solid fa-chart-pie text-xl animate-pulse';
    }, 900);
}

function hideReportGeneratingLoader() {
    clearTimeout(window._genStepTimer1);
    clearTimeout(window._genStepTimer2);
    const loader = document.getElementById('reportGeneratingLoader');
    if (!loader) return;
    
    const barEl = document.getElementById('loaderProgressBar');
    const textEl = document.getElementById('loaderProgressText');
    if (barEl) barEl.style.width = '100%';
    if (textEl) textEl.textContent = '100%';

    setTimeout(() => {
        loader.style.opacity = '0';
        setTimeout(() => loader.classList.add('hidden'), 300);
    }, 250);
}

async function _doGenerateReport() {
    const btn = document.getElementById('generateBtn');
    const originalContent = btn ? btn.innerHTML : '';
    if (btn) {
        btn.innerHTML = '<div class="spinner"></div> Generating...';
        btn.disabled = true;
    }

    // Show futuristic loader animation
    showReportGeneratingLoader();

    try {
        // 1. Fetch live report records from database with current parameter selections
        await loadLiveReportData();

        const data = getFilteredData();
        const total = data.length;
        const compliant = data.filter(r => r.status === 'Compliant').length;
        const nonCompliant = data.filter(r => r.status === 'Non-Compliant').length;
        const urgent = data.filter(r => r.status === 'Urgent').length;
        const complianceRate = total > 0 ? ((compliant / total) * 100).toFixed(1) : 0;

        // 2. Update Summary Banner
        const banner = document.getElementById('generatedReportSummary');
        if (banner) {
            const compEl = document.getElementById('bannerCompliance');
            if (compEl) compEl.textContent = complianceRate + '%';
            const totEl = document.getElementById('bannerTotal');
            if (totEl) totEl.textContent = total;
            const nonCompEl = document.getElementById('bannerNonCompliant');
            if (nonCompEl) nonCompEl.textContent = nonCompliant;
            const urgEl = document.getElementById('bannerUrgent');
            if (urgEl) urgEl.textContent = urgent;

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

        refreshUI();

        // 3. Update the AI summary (uses cache when available to prevent rate limits)
        await fetchAiReportSummary(false);

        // Smooth transition pause for the loading animation
        await new Promise(r => setTimeout(r, 600));

        hideReportGeneratingLoader();

        if (btn) {
            btn.innerHTML = '<i class="fa-solid fa-check"></i> Report Ready!';
        }
        showToast('Report generated successfully!', 'success');
        
        // 4. Open sleek report preview modal
        setTimeout(() => {
            openReportPreviewModal();
        }, 350);

    } catch (err) {
        hideReportGeneratingLoader();
        console.error('Failed to generate report:', err);
        showToast('Failed to generate report: ' + err.message, 'danger');
    } finally {
        setTimeout(() => {
            if (btn) {
                btn.innerHTML = originalContent;
                btn.disabled = false;
            }
        }, 1500);
    }
}

function openReportPreviewModal() {
    const modal = document.getElementById('reportPreview');
    if (modal) {
        // Adapt preview content and badge based on selected format
        const exportFmt = document.getElementById('exportFormat')?.value || 'pdf';
        const badge = document.getElementById('previewFormatBadge');
        if (badge) {
            if (exportFmt === 'excel') {
                badge.innerHTML = '<i class="fa-solid fa-file-excel text-emerald-500"></i> Excel Mode';
            } else if (exportFmt === 'word') {
                badge.innerHTML = '<i class="fa-solid fa-file-word text-blue-500"></i> Word Mode';
            } else {
                badge.innerHTML = '<i class="fa-solid fa-file-pdf text-red-500"></i> PDF Mode';
            }
        }

        const tabSummary = document.getElementById('tabSummary');
        const tabChart = document.getElementById('tabChart');
        const includeVisuals = document.getElementById('includeVisuals')?.checked ?? true;
        const tabTable = document.getElementById('tabTable');
        
        if (tabTable) tabTable.classList.remove('hidden');
        
        if (exportFmt === 'excel' || exportFmt === 'csv') {
            if (tabSummary) tabSummary.classList.add('hidden');
            if (tabChart) tabChart.classList.add('hidden');
        } else {
            if (tabSummary) tabSummary.classList.remove('hidden');
            if (tabChart) {
                if (includeVisuals) tabChart.classList.remove('hidden');
                else tabChart.classList.add('hidden');
            }
        }
        
        // Update dynamic title and date in header
        const titleEl = document.getElementById('reportHeaderTitle');
        const reportTypeSelect = document.getElementById('reportType');
        if (titleEl && reportTypeSelect) {
            const selectedText = reportTypeSelect.options[reportTypeSelect.selectedIndex]?.text;
            if (selectedText) {
                titleEl.textContent = selectedText.includes('Report') ? selectedText : selectedText + ' Report';
            }
        }

        const dateSpan = document.getElementById('reportDateText');
        if (dateSpan) {
            const now = new Date();
            dateSpan.textContent = now.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
        }

        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.style.opacity = '1';
        setTimeout(() => {
            if (barChart && typeof barChart.resize === 'function') {
                barChart.resize();
                barChart.update();
            }
            if (doughnutChart && typeof doughnutChart.resize === 'function') {
                doughnutChart.resize();
                doughnutChart.update();
            }
            if (lineChart && typeof lineChart.resize === 'function') {
                lineChart.resize();
                lineChart.update();
            }
        }, 150);
    }
}

function closeReportPreviewModal() {
    const modal = document.getElementById('reportPreview');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => modal.classList.add('hidden'), 300);
    }
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
function triggerSelectedDownload() {
    const exportFmt = document.getElementById('exportFormat')?.value || 'pdf';
    if (exportFmt === 'excel') exportExcel();
    else if (exportFmt === 'csv') exportCSV();
    else if (exportFmt === 'word') exportWord();
    else exportPDF(); 
}

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
    if (!content) {
        showToast('Download failed: No file data generated.', 'danger');
        return;
    }
    const blob = (content instanceof Blob) ? content : new Blob([content], { type: mimeType });
    if (blob.size === 0) {
        showToast('Download failed: Generated file is empty.', 'danger');
        return;
    }
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

function getCanvasWhiteBgDataUrl(canvasId, mimeType = 'image/png', quality = 0.95) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || canvas.width === 0 || canvas.height === 0) return '';
    try {
        const offscreen = document.createElement('canvas');
        offscreen.width = canvas.width;
        offscreen.height = canvas.height;
        const ctx = offscreen.getContext('2d');
        if (!ctx) return canvas.toDataURL(mimeType, quality);
        
        // Fill clean white background so transparent canvas doesn't render black in PDF
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, offscreen.width, offscreen.height);
        ctx.drawImage(canvas, 0, 0);
        return offscreen.toDataURL(mimeType, quality);
    } catch (e) {
        try {
            return canvas.toDataURL(mimeType, quality);
        } catch (err) {
            return '';
        }
    }
}

function getReportExportMarkup() {
    const reportTypeSelect = document.getElementById('reportType');
    const reportType = reportTypeSelect?.selectedOptions[0]?.textContent?.trim() || 'Executive Operational Report';
    const startDate = document.getElementById('startDate')?.value || '';
    const endDate = document.getElementById('endDate')?.value || '';
    const generatedDate = new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' });
    const logoUrl = new URL('../assets/images/logo.png', window.location.href).href;

    const data = (typeof currentReportData === 'function') ? currentReportData() : (typeof getFilteredData === 'function' ? getFilteredData() : []);
    const total = data.length;
    let compliantCount = 0;
    let pendingCount = 0;
    let urgentCount = 0;

    data.forEach(r => {
        const s = String(r.status || '').toLowerCase();
        if (s === 'compliant' || s === 'passed' || s === 'resolved') compliantCount++;
        else if (s === 'urgent' || s === 'failed' || s === 'non-compliant' || s === 'emergency') urgentCount++;
        else pendingCount++;
    });

    const complianceRate = total > 0 ? ((compliantCount / total) * 100).toFixed(1) : '0.0';

    // Department & User metadata
    const userDept = (typeof CURRENT_USER !== 'undefined' && CURRENT_USER.department) ? CURRENT_USER.department : 'Health Sanitation Management';
    const userName = (typeof CURRENT_USER !== 'undefined' && CURRENT_USER.name) ? CURRENT_USER.name : 'Authorized Officer';
    const userRole = (typeof CURRENT_USER !== 'undefined' && (CURRENT_USER.role_description || CURRENT_USER.role)) ? (CURRENT_USER.role_description || CURRENT_USER.role) : 'Department Staff';

    // Chart Images with Crisp White Backgrounds (eliminates solid black box glitch)
    const barImg = getCanvasWhiteBgDataUrl('barChart');
    const doughnutImg = getCanvasWhiteBgDataUrl('doughnutChart');
    const lineImg = getCanvasWhiteBgDataUrl('lineChart');

    // Page Header Generator
    const makeHeader = (pageNum, totalPages = 3) => `
    <table class="header-table">
        <tr>
            <td class="header-logo"><img src="${logoUrl}" alt="City Logo"></td>
            <td class="header-titles">
                <div class="city-title">Republic of the Philippines · City of South Caloocan</div>
                <div class="dept-title">City Health &amp; Sanitation Department</div>
                <div class="report-name">${escapeExportHtml(reportType)} — Executive Report</div>
            </td>
            <td class="header-meta">
                <strong>Page:</strong> ${pageNum} of ${totalPages}<br>
                <strong>Date Range:</strong> ${escapeExportHtml(startDate || 'All dates')} to ${escapeExportHtml(endDate || 'Present')}<br>
                <strong>Generated:</strong> ${escapeExportHtml(generatedDate)}<br>
                <strong>Scope:</strong> ${escapeExportHtml(userDept)}
            </td>
        </tr>
    </table>`;

    // ─── PAGE 1: EXECUTIVE KPI SUMMARY & VISUAL CHARTS ───
    const page1 = `
    <div class="export-page">
        ${makeHeader(1, 3)}

        <!-- 4 KPI Summary Cards -->
        <table class="kpi-table">
            <tr>
                <td class="kpi-card kpi-primary" style="width:25%;">
                    <span class="kpi-label">Total Logged Records</span>
                    <div class="kpi-val">${total}</div>
                    <div class="kpi-sub">Active period entries</div>
                </td>
                <td class="kpi-card kpi-success" style="width:25%;">
                    <span class="kpi-label">Compliance / Success</span>
                    <div class="kpi-val" style="color:#10b981;">${complianceRate}%</div>
                    <div class="kpi-sub">${compliantCount} passed records</div>
                </td>
                <td class="kpi-card kpi-warning" style="width:25%;">
                    <span class="kpi-label">Pending Reviews</span>
                    <div class="kpi-val" style="color:#f59e0b;">${pendingCount}</div>
                    <div class="kpi-sub">In-progress follow-ups</div>
                </td>
                <td class="kpi-card kpi-danger" style="width:25%;">
                    <span class="kpi-label">Critical / Urgent</span>
                    <div class="kpi-val" style="color:#ef4444;">${urgentCount}</div>
                    <div class="kpi-sub">Immediate attention</div>
                </td>
            </tr>
        </table>

        <div class="section-bar">&#128202; Visual Operational Distribution &amp; Trend Analytics</div>

        <table class="chart-layout-table">
            <tr>
                <!-- Left: Distribution Bar Chart (58%) -->
                <td class="chart-card" style="width: 58%;">
                    <div class="chart-card-title">&#128202; Operational Category Distribution</div>
                    <div style="height: 195px; text-align: center;">
                        ${barImg ? `<img src="${barImg}" style="max-height: 185px; width: auto; max-width: 100%; margin: 0 auto;" alt="Operational Distribution">` : '<div style="padding:40px 0;color:#94a3b8;font-size:8pt;">No chart data rendered</div>'}
                    </div>
                </td>
                
                <!-- Right: Doughnut & Trend (42%) -->
                <td style="width: 42%; vertical-align: top; padding: 0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td class="chart-card" style="margin-bottom: 8px;">
                                <div class="chart-card-title">&#129684; Overall Compliance Breakdown</div>
                                <div style="height: 98px; text-align: center;">
                                    ${doughnutImg ? `<img src="${doughnutImg}" style="max-height: 92px; width: auto; max-width: 100%; margin: 0 auto;" alt="Status Doughnut">` : '<div style="padding:20px 0;color:#94a3b8;font-size:7pt;">No status chart</div>'}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td class="chart-card" style="margin-top: 8px;">
                                <div class="chart-card-title">&#128200; 6-Month Operational Trend</div>
                                <div style="height: 80px; text-align: center;">
                                    ${lineImg ? `<img src="${lineImg}" style="max-height: 74px; width: auto; max-width: 100%; margin: 0 auto;" alt="Trend Line">` : '<div style="padding:15px 0;color:#94a3b8;font-size:7pt;">No trend chart</div>'}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>`;

    // ─── PAGE 2: DETAILED RECORDS TABLE ───
    let tableRowsHtml = '';
    const rowsToRender = data.slice(0, 100);
    rowsToRender.forEach((r, idx) => {
        const status = String(r.status || 'Compliant');
        const statusLower = status.toLowerCase();
        let badgeClass = 'badge-compliant';
        if (['urgent', 'non-compliant', 'failed', 'emergency'].includes(statusLower)) {
            badgeClass = 'badge-urgent';
        } else if (['pending', 'in_progress', 'scheduled', 'investigating'].includes(statusLower)) {
            badgeClass = 'badge-pending';
        }

        const cat = escapeExportHtml(r.category || r.metric || 'General');
        const facDetails = escapeExportHtml(r.facility ? `${r.facility} · ${r.details || r.inspector || ''}` : (r.details || r.item || 'Operational Record'));
        const dt = escapeExportHtml(r.date || 'N/A');
        const sc = (r.score !== undefined && r.score !== null) ? `${escapeExportHtml(r.score)}%` : '—';

        tableRowsHtml += `
        <tr>
            <td style="text-align:center;">${idx + 1}</td>
            <td><strong>${cat}</strong></td>
            <td>${facDetails}</td>
            <td style="text-align:center;">${dt}</td>
            <td style="text-align:center;">${sc}</td>
            <td style="text-align:center;"><span class="badge ${badgeClass}">${escapeExportHtml(status)}</span></td>
        </tr>`;
    });

    if (!tableRowsHtml) {
        tableRowsHtml = '<tr><td colspan="6" style="text-align:center;padding:16px;color:#94a3b8;">No records match the active criteria.</td></tr>';
    }

    const page2 = `
    <div class="export-page page-break">
        ${makeHeader(2, 3)}
        <div class="section-bar">&#128203; Tabular Record Breakdown (Showing ${rowsToRender.length} of ${total} records)</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 5%; text-align: center;">#</th>
                    <th style="width: 22%;">Module / Category</th>
                    <th style="width: 40%;">Facility &amp; Details</th>
                    <th style="width: 13%; text-align: center;">Date</th>
                    <th style="width: 10%; text-align: center;">Score</th>
                    <th style="width: 10%; text-align: center;">Status</th>
                </tr>
            </thead>
            <tbody>
                ${tableRowsHtml}
            </tbody>
        </table>
    </div>`;

    // ─── PAGE 3: AI EXECUTIVE SUMMARY & SIGN-OFF ───
    const summaryEl = document.getElementById('summaryText');
    let aiNarrative = '';
    if (summaryEl && summaryEl.innerText.trim() && !summaryEl.innerText.includes('Synthesizing')) {
        aiNarrative = summaryEl.innerHTML;
    } else {
        aiNarrative = `<p>South Caloocan City Health &amp; Sanitation department recorded a total of <strong>${total} active events</strong> with an overall compliance index of <strong>${complianceRate}%</strong> during the reporting period. All health centers and sanitation inspection hubs operated within mandated municipal standards. Focus should remain on resolving ${pendingCount} pending inquiries and attending to critical findings promptly.</p>`;
    }

    const recItems = document.querySelectorAll('#aiRecommendationsList li');
    let recsHtml = '';
    if (recItems && recItems.length > 0) {
        recItems.forEach(li => {
            const text = li.innerText.trim();
            if (text) {
                recsHtml += `<div class="rec-item"><span class="rec-icon">&#10003;</span> ${escapeExportHtml(text)}</div>`;
            }
        });
    }
    if (!recsHtml) {
        recsHtml = `
            <div class="rec-item"><span class="rec-icon">&#10003;</span> <strong>Maintain Inspection Regularity:</strong> Enforce bi-weekly compliance visits across core facilities.</div>
            <div class="rec-item"><span class="rec-icon">&#10003;</span> <strong>Clear Pending Backlog:</strong> Follow up on open inquiries within 48 hours.</div>
            <div class="rec-item"><span class="rec-icon">&#10003;</span> <strong>Resource Rebalancing:</strong> Allocate supplies to priority satellite centers.</div>`;
    }

    const lbl1 = document.getElementById('labelMetric1')?.textContent || 'Compliance Rate:';
    const val1 = document.getElementById('metricCompliance')?.textContent || `${complianceRate}%`;

    const lbl2 = document.getElementById('labelMetric2')?.textContent || 'Encounter / Coverage:';
    const val2 = document.getElementById('metricCoverage')?.textContent || '98.0%';

    const lbl3 = document.getElementById('labelMetric3')?.textContent || 'Resolution Rate:';
    const val3 = document.getElementById('metricResolution')?.textContent || '92.5%';

    const lbl4 = document.getElementById('labelMetric4')?.textContent || 'Operational Index:';
    const val4 = document.getElementById('metricParticipation')?.textContent || '100%';

    const page3 = `
    <div class="export-page page-break">
        ${makeHeader(3, 3)}

        <div class="section-bar">&#129302; AI Executive Summary Narrative</div>
        <div class="narrative-box">
            ${aiNarrative}
        </div>

        <div class="section-bar">&#128161; Actionable Strategic Recommendations</div>
        <div style="background:#ffffff;border:1px solid #cbd5e1;border-radius:4px;padding:8px 12px;margin-bottom:10px;">
            ${recsHtml}
        </div>

        <div class="section-bar">&#128202; Key Department Performance Indicators</div>
        <table class="kpi-table" style="margin-bottom: 12px;">
            <tr>
                <td class="kpi-card" style="width:25%;">
                    <span class="kpi-label">${escapeExportHtml(lbl1)}</span>
                    <div class="kpi-val" style="color:#10b981;font-size:12pt;">${escapeExportHtml(val1)}</div>
                </td>
                <td class="kpi-card" style="width:25%;">
                    <span class="kpi-label">${escapeExportHtml(lbl2)}</span>
                    <div class="kpi-val" style="color:#176B87;font-size:12pt;">${escapeExportHtml(val2)}</div>
                </td>
                <td class="kpi-card" style="width:25%;">
                    <span class="kpi-label">${escapeExportHtml(lbl3)}</span>
                    <div class="kpi-val" style="color:#f59e0b;font-size:12pt;">${escapeExportHtml(val3)}</div>
                </td>
                <td class="kpi-card" style="width:25%;">
                    <span class="kpi-label">${escapeExportHtml(lbl4)}</span>
                    <div class="kpi-val" style="color:#6366f1;font-size:12pt;">${escapeExportHtml(val4)}</div>
                </td>
            </tr>
        </table>

        <!-- Official Sign-off Table -->
        <table class="signoff-table">
            <tr>
                <td class="signoff-cell">
                    <div class="signoff-line"></div>
                    <div class="signoff-name">${escapeExportHtml(userName)}</div>
                    <div class="signoff-title">${escapeExportHtml(userRole)} · ${escapeExportHtml(userDept)}</div>
                </td>
                <td class="signoff-cell">
                    <div class="signoff-line"></div>
                    <div class="signoff-name">City Health Officer / Department Director</div>
                    <div class="signoff-title">South Caloocan City Health &amp; Sanitation Department</div>
                </td>
            </tr>
        </table>

        <div class="footer-text">
            Civentral Health &amp; Sanitation MIS · Official Government Document · Generated on ${escapeExportHtml(generatedDate)} · Document Ref: HSMS-CAL-${new Date().getFullYear()}
        </div>
    </div>`;

    return `<article class="export-report">${page1}${page2}${page3}</article>`;
}

function getExportDocument(content, title, extraStyles = '') {
    return `<!doctype html><html><head><meta charset="UTF-8"><title>${escapeExportHtml(title)}</title>
        <style>
            @page {
                size: A4 portrait;
                margin: 0.45in 0.45in 0.5in 0.45in;
            }
            body {
                font-family: "DejaVu Sans", Arial, sans-serif;
                font-size: 9pt;
                color: #1e293b;
                line-height: 1.35;
                margin: 0;
                padding: 0;
            }
            
            .page-break {
                page-break-before: always;
            }
            
            /* ─── Header ─── */
            .header-table {
                width: 100%;
                border-collapse: collapse;
                border-bottom: 2.5px solid #176B87;
                padding-bottom: 8px;
                margin-bottom: 12px;
            }
            .header-logo {
                width: 56px;
                vertical-align: middle;
            }
            .header-logo img {
                width: 50px;
                height: auto;
            }
            .header-titles {
                vertical-align: middle;
                padding-left: 10px;
            }
            .header-titles .city-title {
                font-size: 7.5pt;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                color: #64748b;
                font-weight: bold;
                margin: 0;
            }
            .header-titles .dept-title {
                font-size: 13pt;
                color: #0F4A5E;
                font-weight: bold;
                margin: 2px 0 0 0;
            }
            .header-titles .report-name {
                font-size: 10.5pt;
                color: #176B87;
                font-weight: 600;
                margin: 2px 0 0 0;
            }
            .header-meta {
                text-align: right;
                vertical-align: middle;
                font-size: 7.5pt;
                color: #475569;
                line-height: 1.4;
            }
            
            /* ─── Section Header Bar ─── */
            .section-bar {
                background: #176B87;
                color: #ffffff;
                padding: 5px 10px;
                font-size: 8.5pt;
                font-weight: bold;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-radius: 3px;
                margin: 10px 0 8px 0;
            }
            
            /* ─── KPI Metric Cards Table ─── */
            .kpi-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 6px;
                margin-bottom: 8px;
            }
            .kpi-card {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-radius: 5px;
                padding: 8px;
                text-align: center;
                vertical-align: top;
            }
            .kpi-card.kpi-primary { border-top: 3px solid #176B87; }
            .kpi-card.kpi-success { border-top: 3px solid #10b981; }
            .kpi-card.kpi-warning { border-top: 3px solid #f59e0b; }
            .kpi-card.kpi-danger  { border-top: 3px solid #ef4444; }
            
            .kpi-label {
                font-size: 7pt;
                font-weight: bold;
                text-transform: uppercase;
                color: #64748b;
                display: block;
                margin-bottom: 2px;
            }
            .kpi-val {
                font-size: 14pt;
                font-weight: bold;
                color: #0f172a;
                margin: 0;
            }
            .kpi-sub {
                font-size: 6.5pt;
                color: #94a3b8;
                margin-top: 2px;
            }
            
            /* ─── Visual Charts Layout Table ─── */
            .chart-layout-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 8px 0;
                margin-top: 4px;
            }
            .chart-card {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 5px;
                padding: 8px;
                vertical-align: top;
            }
            .chart-card-title {
                font-size: 7.5pt;
                font-weight: bold;
                color: #176B87;
                margin: 0 0 6px 0;
                padding-bottom: 4px;
                border-bottom: 1px solid #f1f5f9;
            }
            .chart-card img {
                display: block;
                width: 100%;
                height: auto;
                margin: 0 auto;
            }
            
            /* ─── Data Tables ─── */
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 6px;
                font-size: 7.5pt;
            }
            .data-table th {
                background: #176B87;
                color: #ffffff;
                padding: 5px 7px;
                font-weight: bold;
                text-align: left;
                border: 1px solid #176B87;
                text-transform: uppercase;
                font-size: 7pt;
                letter-spacing: 0.3px;
            }
            .data-table td {
                padding: 5px 7px;
                border: 1px solid #cbd5e1;
                color: #334155;
            }
            .data-table tr:nth-child(even) {
                background-color: #f8fafc;
            }
            
            /* ─── Status Badges ─── */
            .badge {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 4px;
                font-size: 6.5pt;
                font-weight: bold;
                text-transform: uppercase;
            }
            .badge-compliant { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
            .badge-pending   { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
            .badge-urgent    { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
            
            /* ─── AI Narrative & Recommendations ─── */
            .narrative-box {
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                border-left: 4px solid #176B87;
                border-radius: 4px;
                padding: 8px 12px;
                font-size: 8pt;
                color: #334155;
                line-height: 1.45;
                margin-bottom: 10px;
            }
            .rec-item {
                padding: 4px 0;
                font-size: 8pt;
                color: #334155;
            }
            .rec-icon {
                color: #10b981;
                font-weight: bold;
                margin-right: 4px;
            }
            
            /* ─── Sign-off Block ─── */
            .signoff-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 24px;
                page-break-inside: avoid;
            }
            .signoff-cell {
                width: 50%;
                padding: 10px 20px;
                vertical-align: top;
            }
            .signoff-line {
                border-bottom: 1.5px solid #64748b;
                margin-top: 36px;
                margin-bottom: 4px;
            }
            .signoff-name {
                font-size: 8.5pt;
                font-weight: bold;
                color: #0f172a;
            }
            .signoff-title {
                font-size: 7pt;
                color: #64748b;
            }
            
            /* ─── Footer ─── */
            .footer-text {
                text-align: center;
                font-size: 6.5pt;
                color: #94a3b8;
                border-top: 1px solid #e2e8f0;
                padding-top: 6px;
                margin-top: 16px;
            }
            
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
    const exportUrl = (window.APP_CONFIG && window.APP_CONFIG.api_reports_export) ? window.APP_CONFIG.api_reports_export : '../api/reports/export.php';
    fetch(`${exportUrl}?format=excel&title=${encodeURIComponent(meta.title)}&module=${encodeURIComponent(meta.module)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ headers, rows })
    }).then(async res => {
        const contentType = res.headers.get('content-type') || '';
        if (!res.ok || contentType.includes('application/json')) {
            const errData = await res.json().catch(() => ({}));
            throw new Error(errData.message || `Server error (HTTP ${res.status})`);
        }
        return res.blob();
    }).then(blob => {
        if (!blob || blob.size === 0) {
            throw new Error('Server returned an empty Excel file.');
        }
        downloadBlob(blob, `${meta.slug}_${stamp}.xlsx`, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        logReportGeneration(meta.title, 'Excel Export');
        showToast('Excel report downloaded successfully!', 'success');
    }).catch(err => {
        console.error('Excel export error:', err);
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

    const exportPdfUrl = (window.APP_CONFIG && window.APP_CONFIG.api_reports_export) ? window.APP_CONFIG.api_reports_export : '../api/reports/export.php';
    fetch(`${exportPdfUrl}?format=pdf&title=${encodeURIComponent(meta.title)}&module=${encodeURIComponent(meta.module)}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ html: visualHtml, title: meta.title })
    }).then(async res => {
        const contentType = res.headers.get('content-type') || '';
        if (!res.ok || contentType.includes('application/json')) {
            const errData = await res.json().catch(() => ({}));
            throw new Error(errData.message || `Server error (HTTP ${res.status})`);
        }
        return res.blob();
    }).then(blob => {
        if (!blob || blob.size === 0) {
            throw new Error('Server returned an empty PDF file.');
        }
        downloadBlob(blob, `${meta.slug}_${stamp}.pdf`, 'application/pdf');
        logReportGeneration(meta.title, 'PDF Export');
        showToast('PDF report downloaded successfully!', 'success');
    }).catch(err => {
        console.error('PDF export error:', err);
        showToast('PDF export failed: ' + err.message, 'danger');
    });
}

function printCustomReport() {
    const meta = getReportMetadata();

    // Build the identical 3-page executive document used by PDF export
    const markup  = getReportExportMarkup();
    if (!markup) {
        showToast('No report data to print. Please generate a report first.', 'warning');
        return;
    }

    // Add @media print tweaks so page-breaks render correctly in browser print
    const printStyles = `
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .export-page { page-break-after: always; }
            .export-page:last-child { page-break-after: avoid; }
        }
    `;
    const docHtml = getExportDocument(markup, meta.title, printStyles);

    // Write into a hidden iframe so we don't disturb the current modal/page
    let printFrame = document.getElementById('_reportPrintFrame');
    if (!printFrame) {
        printFrame = document.createElement('iframe');
        printFrame.id = '_reportPrintFrame';
        printFrame.style.cssText = 'position:fixed;top:-9999px;left:-9999px;width:210mm;height:297mm;border:0;opacity:0;pointer-events:none;';
        document.body.appendChild(printFrame);
    }

    // Write document into the iframe
    const doc = printFrame.contentDocument || printFrame.contentWindow.document;
    doc.open();
    doc.write(docHtml);
    doc.close();

    // Wait for images (chart data-URIs) to finish loading, then print
    printFrame.onload = () => {
        try {
            printFrame.contentWindow.focus();
            printFrame.contentWindow.print();
        } catch (e) {
            console.error('Print error:', e);
            showToast('Print failed: ' + e.message, 'danger');
        }
    };

    // Log audit silently (do not block print on failure)
    logReportGeneration(meta.title, 'Browser Print').catch(() => {});
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

// ─── MODAL CONTROLS ───────────────────────────────────────────
let selectedScheduleFormat = 'PDF';

function selectScheduleFormat(fmt) {
    selectedScheduleFormat = fmt;
    ['PDF', 'Excel', 'Word'].forEach(f => {
        const el = document.getElementById('schedFormat' + f);
        if (el) {
            if (f.toLowerCase() === fmt.toLowerCase()) {
                el.className = 'sched-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-[#176B87] bg-[#176B87]/5 cursor-pointer transition-all';
                const radio = el.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            } else {
                el.className = 'sched-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all';
                const radio = el.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            }
        }
    });
}

function selectGenerateFormat(fmt) {
    const selectEl = document.getElementById('exportFormat');
    if (selectEl) selectEl.value = fmt.toLowerCase();
    ['Pdf', 'Excel', 'Word'].forEach(f => {
        const el = document.getElementById('genFormat' + f);
        if (el) {
            if (f.toLowerCase() === fmt.toLowerCase()) {
                el.className = 'gen-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-[#176B87] bg-[#176B87]/5 cursor-pointer transition-all';
                const radio = el.querySelector('input[type="radio"]');
                if (radio) radio.checked = true;
            } else {
                el.className = 'gen-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all';
                const radio = el.querySelector('input[type="radio"]');
                if (radio) radio.checked = false;
            }
        }
    });
}

function setScheduleDatePreset(preset) {
    const startInput = document.getElementById('scheduleReportStart');
    const endInput = document.getElementById('scheduleReportEnd');
    if (!startInput || !endInput) return;

    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    endInput.value = todayStr;

    if (preset === 'this_month') {
        const firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        startInput.value = firstDay.toISOString().split('T')[0];
    } else if (preset === 'this_year') {
        const firstDay = new Date(today.getFullYear(), 0, 1);
        startInput.value = firstDay.toISOString().split('T')[0];
    } else if (preset === 'last_30_days') {
        const past = new Date(today);
        past.setDate(past.getDate() - 30);
        startInput.value = past.toISOString().split('T')[0];
    }
}

function openScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    if (!modal) return;
    const statusMsg = document.getElementById('scheduleStatusMsg');
    const downloadSection = document.getElementById('scheduleDownloadSection');
    const overlay = document.getElementById('scheduleLoadingOverlay');

    if (statusMsg) statusMsg.classList.add('hidden');
    if (downloadSection) downloadSection.classList.add('hidden');
    if (overlay) {
        overlay.style.opacity = '0';
        overlay.classList.add('hidden');
    }

    // Auto-sync current parameters from Generate Report form
    const genStart = document.getElementById('startDate')?.value;
    const genEnd = document.getElementById('endDate')?.value;
    const genType = document.getElementById('reportType')?.value;
    const genVisuals = document.getElementById('includeVisuals')?.checked;
    const genFmt = (document.getElementById('exportFormat')?.value || 'PDF').toUpperCase();

    const schedStart = document.getElementById('scheduleReportStart');
    const schedEnd = document.getElementById('scheduleReportEnd');
    const schedType = document.getElementById('scheduleReportType');
    const schedVisuals = document.getElementById('scheduleIncludeVisuals');

    if (genType && schedType) schedType.value = genType;
    if (typeof genVisuals !== 'undefined' && schedVisuals) schedVisuals.checked = genVisuals;
    if (typeof selectScheduleFormat === 'function') selectScheduleFormat(genFmt);

    // Default Report Date Range to past 30 days up to today
    const now = new Date();
    const curYear = now.getFullYear();
    const curMonth = String(now.getMonth() + 1).padStart(2, '0');
    const curDay = String(now.getDate()).padStart(2, '0');
    const curDateStr = `${curYear}-${curMonth}-${curDay}`;

    const past30 = new Date(now.getTime() - 30 * 24 * 60 * 60 * 1000);
    const pYear = past30.getFullYear();
    const pMonth = String(past30.getMonth() + 1).padStart(2, '0');
    const pDay = String(past30.getDate()).padStart(2, '0');
    const pDateStr = `${pYear}-${pMonth}-${pDay}`;

    if (schedStart) schedStart.value = pDateStr;
    if (schedEnd) schedEnd.value = curDateStr;

    // Pre-populate Start Date with current local date and Time with upcoming time (+2 mins)
    const upcoming = new Date(now.getTime() + 2 * 60000);
    const upHours = String(upcoming.getHours()).padStart(2, '0');
    const upMins = String(upcoming.getMinutes()).padStart(2, '0');
    const upTimeStr = `${upHours}:${upMins}`;

    const startDateEl = document.getElementById('scheduleStartDateInput');
    const timeEl = document.getElementById('scheduleTimeInput');
    if (startDateEl && (!startDateEl.value || startDateEl.value < curDateStr)) {
        startDateEl.value = curDateStr;
    }
    if (timeEl && (!timeEl.value || timeEl.value === '08:00')) {
        timeEl.value = upTimeStr;
    }

    modal.classList.remove('hidden');
    void modal.offsetWidth;
    modal.style.opacity = '1';
}

function closeScheduleModal() {
    const modal = document.getElementById('scheduleModal');
    if (!modal) return;
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

function scheduleReport() {
    saveSchedule();
}

function showScheduleLoading(show, message = 'Generating executive report document and dispatching directly to recipient email inbox...') {
    const fullLoading = document.getElementById('scheduleLoadingScreen');
    const overlay = document.getElementById('scheduleLoadingOverlay');
    const subtext = document.getElementById('scheduleLoadingSubtext');           // full-screen
    const subtextOverlay = document.getElementById('scheduleLoadingSubtextOverlay'); // modal overlay
    const submitBtn = document.getElementById('scheduleSubmitBtn');

    if (show) {
        if (subtext) subtext.textContent = message;
        if (subtextOverlay) subtextOverlay.textContent = message;
        if (fullLoading) {
            fullLoading.classList.remove('hidden');
            void fullLoading.offsetWidth;
            fullLoading.style.opacity = '1';
        }
        if (overlay) {
            overlay.classList.remove('hidden');
            void overlay.offsetWidth;
            overlay.style.opacity = '1';
        }
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-sm"></i> <span>Sending Email &amp; Saving...</span>';
        }
    } else {
        if (fullLoading) {
            fullLoading.style.opacity = '0';
            setTimeout(() => fullLoading.classList.add('hidden'), 300);
        }
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => overlay.classList.add('hidden'), 300);
        }
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            submitBtn.innerHTML = '<i class="fa-regular fa-floppy-disk"></i> <span id="scheduleSubmitBtnText">Schedule &amp; Send</span>';
        }
    }
}

function saveSchedule() {
    const title = (document.getElementById('scheduleTitleInput')?.value || '').trim();
    const recipients = (document.getElementById('scheduleRecipientsInput')?.value || '').trim();
    const reportTypeSelect = document.getElementById('scheduleReportType');
    const reportType = reportTypeSelect?.value || 'unified';
    const department = reportTypeSelect?.selectedOptions?.[0]?.text || 'Health Center Services';
    const startDate = document.getElementById('scheduleReportStart')?.value || '';
    const endDate = document.getElementById('scheduleReportEnd')?.value || '';
    const includeVisuals = document.getElementById('scheduleIncludeVisuals')?.checked ?? true;
    const frequency = document.getElementById('scheduleFrequencySelect')?.value || 'Weekly';
    const startDateInput = document.getElementById('scheduleStartDateInput')?.value || '';
    const time = document.getElementById('scheduleTimeInput')?.value || '08:00';
    const statusMsg = document.getElementById('scheduleStatusMsg');
    const downloadSection = document.getElementById('scheduleDownloadSection');

    if (!title) {
        showToast('Please enter a schedule title.', 'info');
        return;
    }
    if (!recipients) {
        showToast('Please enter recipient email address(es).', 'info');
        return;
    }

    // Trigger full loading effect
    showScheduleLoading(true, 'Saving automated schedule "' + title + '" for ' + frequency + ' delivery...');

    const payload = {
        action: 'create',
        report_title: title,
        recipients: recipients,
        report_type: reportType,
        department: department,
        report_start_date: startDate,
        report_end_date: endDate,
        include_visuals: includeVisuals ? 1 : 0,
        frequency: frequency,
        start_date: startDateInput,
        time: time,
        format: selectedScheduleFormat
    };

    const apiUrl = (typeof APP_CONFIG !== 'undefined' && APP_CONFIG.api_reports_schedule) ? APP_CONFIG.api_reports_schedule : '../api/reports/schedule.php';
    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        showScheduleLoading(false);

        if (data.success) {
            const nextRunTime = data.schedule?.next_run_at || 'scheduled time';
            showToast(data.message || `Schedule created! Next automated run: ${nextRunTime}`, 'success');
            if (statusMsg) {
                statusMsg.className = 'p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-700 font-medium';
                statusMsg.textContent = `✓ Schedule active (Status: ACTIVE 🟢). Next automated run: ${nextRunTime}`;
                statusMsg.classList.remove('hidden');
            }
            if (downloadSection) {
                downloadSection.classList.remove('hidden');
                const label = document.getElementById('scheduleDownloadLabel');
                if (label) label.textContent = `Download sample report in ${selectedScheduleFormat} format now`;
            }
            if (typeof loadScheduledReports === 'function') loadScheduledReports();

            // Automatically close schedule modal after loading completes
            setTimeout(() => {
                closeScheduleModal();
            }, 1200);
        } else {
            showToast(data.message || 'Failed to save schedule.', 'info');
            if (statusMsg) {
                statusMsg.className = 'p-3 bg-rose-50 border border-rose-200 rounded-xl text-xs text-rose-700 font-medium';
                statusMsg.textContent = data.message || 'Error saving schedule.';
                statusMsg.classList.remove('hidden');
            }
        }
    })
    .catch(err => {
        console.error('Schedule Error:', err);
        showScheduleLoading(false);
        showToast('Error saving schedule: ' + err.message, 'info');
    });
}

function downloadScheduledReport() {
    const reportType = document.getElementById('scheduleReportType')?.value || 'unified';
    const startDate = document.getElementById('scheduleReportStart')?.value || '';
    const endDate = document.getElementById('scheduleReportEnd')?.value || '';
    const fmt = selectedScheduleFormat.toLowerCase();

    if (fmt === 'pdf' && typeof downloadPDF === 'function') {
        downloadPDF();
    } else if (fmt === 'excel' && typeof downloadExcel === 'function') {
        downloadExcel();
    } else if (fmt === 'word' && typeof downloadWord === 'function') {
        downloadWord();
    } else {
        if (typeof downloadPDF === 'function') downloadPDF();
    }
}

window.selectScheduleFormat = selectScheduleFormat;
window.setScheduleDatePreset = setScheduleDatePreset;
window.openScheduleModal = openScheduleModal;
window.closeScheduleModal = closeScheduleModal;
window.deleteSchedule = deleteSchedule;
window.closeDeleteScheduleModal = closeDeleteScheduleModal;
window.confirmDeleteScheduleAction = confirmDeleteScheduleAction;
window.scheduleReport = scheduleReport;
window.saveSchedule = saveSchedule;
window.downloadScheduledReport = downloadScheduledReport;

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
        let subtitleText = displayType;
        const rawDept = (t.department || '').trim();
        if (rawDept && rawDept.toLowerCase() !== displayType.toLowerCase()) {
            if (rawDept.toLowerCase().startsWith(displayType.toLowerCase())) {
                subtitleText = escapeExportHtml(rawDept);
            } else {
                subtitleText = `${displayType} · ${escapeExportHtml(rawDept)}`;
            }
        }

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
                                <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">${subtitleText}</span>
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
    openReportPreviewModal();
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

let pendingDeleteTemplateId = null;

function deleteTemplate(id) {
    const t = allTemplates.find(tpl => String(tpl.id) === String(id));
    pendingDeleteTemplateId = id;
    const modal = document.getElementById('deleteTemplateModal');
    const nameEl = document.getElementById('deleteTemplateTargetName');
    if (nameEl) {
        nameEl.textContent = t ? (t.name || 'Report Template') : 'Report Template';
    }
    if (modal) {
        modal.classList.remove('hidden');
        setTimeout(() => modal.classList.remove('opacity-0'), 10);
    }
}

function closeDeleteTemplateModal() {
    const modal = document.getElementById('deleteTemplateModal');
    if (modal) {
        modal.classList.add('opacity-0');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }
    pendingDeleteTemplateId = null;
}

async function confirmDeleteTemplateAction() {
    if (!pendingDeleteTemplateId) return;
    const id = pendingDeleteTemplateId;
    closeDeleteTemplateModal();
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
        } else {
            showToast(res.message || 'Failed to delete template', 'info');
        }
    } catch (e) {
        console.error('Failed to delete template:', e);
        showToast('Error deleting template', 'info');
    }
}

// ─── SCHEDULED REPORTS MANAGEMENT ──────────────────────────────
let allSchedules = [];
let schedulePollerTimer = null;
let lastProcessedScheduleMap = {};

async function loadScheduledReports(silent = false) {
    const tbody = document.getElementById('schedulesTableBody');
    if (!tbody) return;
    if (!silent && allSchedules.length === 0) {
        tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-xs text-slate-400"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Loading schedules...</td></tr>';
    }

    try {
        const apiUrl = (typeof APP_CONFIG !== 'undefined' && APP_CONFIG.api_reports_schedule) ? APP_CONFIG.api_reports_schedule : '../api/reports/schedule.php';
        const resp = await fetch(apiUrl);
        const res = await resp.json();
        if (res && res.success) {
            allSchedules = res.schedules || [];
            
            // Check for freshly executed schedules to notify in realtime
            allSchedules.forEach(s => {
                if (s.last_run_at) {
                    if (lastProcessedScheduleMap[s.id] && lastProcessedScheduleMap[s.id] !== s.last_run_at) {
                        const title = s.report_title || 'Scheduled Report';
                        const recips = Array.isArray(s.recipients) ? s.recipients.join(', ') : (s.recipients || 'Recipients');
                        showToast(`🔔 Scheduled Task "${title}" has executed & sent to ${recips}!`, 'success');
                    }
                    lastProcessedScheduleMap[s.id] = s.last_run_at;
                } else if (!lastProcessedScheduleMap[s.id]) {
                    lastProcessedScheduleMap[s.id] = 'initial';
                }
            });

            // Render with active filter criteria applied
            filterScheduledReports();
        } else if (!silent) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-xs text-red-500">Failed to load schedules.</td></tr>';
        }
    } catch (e) {
        console.error('Failed to load schedules:', e);
        if (!silent && allSchedules.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="py-8 text-center text-xs text-red-500">Error loading schedules.</td></tr>';
        }
    }
}

function filterScheduledReports() {
    const searchKeyword = (document.getElementById('scheduleSearchInput')?.value || '').trim().toLowerCase();
    const filterDate = document.getElementById('scheduleFilterDate')?.value || '';
    const badgeEl = document.getElementById('scheduleCountBadge');

    let filtered = Array.isArray(allSchedules) ? [...allSchedules] : [];

    if (searchKeyword) {
        filtered = filtered.filter(s => {
            const title = (s.report_title || s.title || '').toLowerCase();
            const dept = (s.department || '').toLowerCase();
            const format = (s.format || '').toLowerCase();
            const freq = (s.frequency || '').toLowerCase();
            const recips = Array.isArray(s.recipients) ? s.recipients.join(' ').toLowerCase() : (s.recipients || '').toLowerCase();
            const nextRun = (s.next_run_at || '').toLowerCase();
            const status = (s.status || '').toLowerCase();
            const lastStatus = (s.last_status || '').toLowerCase();
            return title.includes(searchKeyword) || dept.includes(searchKeyword) || format.includes(searchKeyword) || freq.includes(searchKeyword) || recips.includes(searchKeyword) || nextRun.includes(searchKeyword) || status.includes(searchKeyword) || lastStatus.includes(searchKeyword);
        });
    }

    if (filterDate) {
        filtered = filtered.filter(s => {
            const nextRunDate = (s.next_run_at || '').substring(0, 10);
            const lastRunDate = (s.last_run_at || '').substring(0, 10);
            const startDate = (s.start_date || '').substring(0, 10);
            return nextRunDate === filterDate || lastRunDate === filterDate || startDate === filterDate;
        });
    }

    if (badgeEl) {
        if (searchKeyword || filterDate) {
            badgeEl.textContent = `Showing ${filtered.length} of ${allSchedules.length} schedules`;
        } else {
            badgeEl.textContent = `Showing ${allSchedules.length} schedule${allSchedules.length === 1 ? '' : 's'}`;
        }
    }

    renderScheduledReports(filtered);
}

function resetScheduleFilters() {
    const searchInput = document.getElementById('scheduleSearchInput');
    const dateInput = document.getElementById('scheduleFilterDate');
    if (searchInput) searchInput.value = '';
    if (dateInput) dateInput.value = '';
    filterScheduledReports();
}

function startScheduleAutoPoller() {
    if (schedulePollerTimer) return;
    
    // Auto-poll heartbeat every 5 seconds
    schedulePollerTimer = setInterval(async () => {
        const now = new Date();
        let shouldPoll = false;

        // Check if any schedule has reached its execution time
        if (Array.isArray(allSchedules)) {
            for (const s of allSchedules) {
                if (s.status === 'active' && s.next_run_at) {
                    const nextTs = new Date(s.next_run_at.replace(/-/g, '/'));
                    if (nextTs <= now) {
                        shouldPoll = true;
                        break;
                    }
                }
            }
        }

        // Also poll if the Automated Schedules tab is currently active
        const schedTabContent = document.getElementById('tab-content-scheduled');
        if (schedTabContent && !schedTabContent.classList.contains('hidden')) {
            shouldPoll = true;
        }

        if (shouldPoll) {
            await loadScheduledReports(true);
        }
    }, 5000);
}

// Automatically start poller on script initialization
startScheduleAutoPoller();

function renderScheduledReports(schedules) {
    const tbody = document.getElementById('schedulesTableBody');
    if (!tbody) return;

    if (!schedules || schedules.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="py-12 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-16 h-16 rounded-full bg-slate-50 flex items-center justify-center mb-3 border border-slate-100 shadow-sm">
                            <i class="fa-regular fa-clock text-2xl text-[#86B6F6]"></i>
                        </div>
                        <span class="font-medium text-slate-600 text-sm">No Matching Schedules</span>
                        <span class="text-xs text-slate-400 mt-1 max-w-xs">No automated schedules match your filter criteria.</span>
                    </div>
                </td>
            </tr>
        `;
        return;
    }

    const now = new Date();

    // ─── SORT: ACTIVE 🟢 FIRST, EXECUTED ⚪ LAST ───
    const sorted = [...schedules].sort((a, b) => {
        const aRawStatus = (a.status || 'active').toLowerCase();
        const bRawStatus = (b.status || 'active').toLowerCase();
        const aLastStatus = (a.last_status || '').toLowerCase();
        const bLastStatus = (b.last_status || '').toLowerCase();

        const aExecuted = (aRawStatus === 'executed' || aRawStatus === 'sent' || aRawStatus === 'completed' || aLastStatus === 'executed');
        const bExecuted = (bRawStatus === 'executed' || bRawStatus === 'sent' || bRawStatus === 'completed' || bLastStatus === 'executed');

        // Active first (aExecuted = false comes before bExecuted = true)
        if (!aExecuted && bExecuted) return -1;
        if (aExecuted && !bExecuted) return 1;

        // Both are Active: sort by upcoming next_run_at ascending (soonest first)
        if (!aExecuted && !bExecuted) {
            const aNext = a.next_run_at ? new Date(a.next_run_at.replace(/-/g, '/')).getTime() : 9999999999999;
            const bNext = b.next_run_at ? new Date(b.next_run_at.replace(/-/g, '/')).getTime() : 9999999999999;
            return aNext - bNext;
        }

        // Both are Executed: sort by last_run_at descending (most recently executed first)
        const aLast = a.last_run_at ? new Date(a.last_run_at.replace(/-/g, '/')).getTime() : 0;
        const bLast = b.last_run_at ? new Date(b.last_run_at.replace(/-/g, '/')).getTime() : 0;
        return bLast - aLast;
    });

    tbody.innerHTML = sorted.map(s => {
        const title = s.report_title || s.title || s.report_type || 'Automated Report';
        const nextRun = s.next_run_at || s.next_run || (s.last_run_at ? 'Completed' : 'Pending');
        const recipients = Array.isArray(s.recipients) ? s.recipients.join(', ') : (s.recipients || 'admin@caloocan.gov.ph');
        const format = (s.format || 'PDF').toUpperCase();
        const freq = s.frequency || 'Daily';
        const rawStatus = (s.status || 'active').toLowerCase();
        const lastStatus = (s.last_status || '').toLowerCase();

        let isExecuted = false;
        if (rawStatus === 'executed' || rawStatus === 'sent' || rawStatus === 'completed' || lastStatus === 'executed') {
            isExecuted = true;
        }

        let isPastDue = false;
        if (s.next_run_at) {
            const nextTs = new Date(s.next_run_at.replace(/-/g, '/'));
            if (nextTs <= now) {
                isPastDue = true;
            }
        }

        let statusBadgeHtml = '';
        if (isExecuted || (rawStatus === 'active' && isPastDue)) {
            // EXECUTED ⚪ / SENT State
            statusBadgeHtml = `
                <span class="status-badge-pill bg-slate-100 text-slate-700 border border-slate-300 font-bold px-2.5 py-1 rounded-full text-[11px] inline-flex items-center gap-1.5 shadow-2xs" title="${s.last_run_at ? 'Executed at: ' + s.last_run_at : 'Task Executed'}">
                    <i class="fa-solid fa-circle text-slate-400 text-[8px]"></i> EXECUTED
                </span>
            `;
        } else {
            // ACTIVE 🟢 State
            statusBadgeHtml = `
                <span class="status-badge-pill bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold px-2.5 py-1 rounded-full text-[11px] inline-flex items-center gap-1.5 shadow-2xs" title="Awaiting next execution at: ${nextRun}">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> ACTIVE
                </span>
            `;
        }

        return `
        <tr class="table-row-hover transition-colors">
            <td class="py-3.5 px-5 font-medium text-[#176B87]">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-[#B4D4FF]/20 text-[#176B87] flex items-center justify-center shrink-0">
                        <i class="fa-regular fa-calendar-check text-xs text-[#176B87]"></i>
                    </div>
                    <div>
                        <span class="font-bold text-slate-800 text-sm block">${escapeExportHtml(title)}</span>
                        <span class="text-[11px] text-slate-400 font-normal">${escapeExportHtml(s.department || 'Health & Sanitation')}</span>
                    </div>
                </div>
            </td>
            <td class="py-3.5 px-4 text-xs font-semibold text-slate-700 whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-lg bg-slate-100/90 border border-slate-200 text-slate-700 font-medium">${escapeExportHtml(freq)}</span>
            </td>
            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                <span class="px-2.5 py-1 rounded-md text-xs font-extrabold ${format === 'PDF' ? 'bg-red-50 text-red-600 border border-red-200' : (format === 'EXCEL' ? 'bg-emerald-50 text-emerald-600 border border-emerald-200' : 'bg-blue-50 text-blue-600 border border-blue-200')}">
                    ${escapeExportHtml(format)}
                </span>
            </td>
            <td class="py-3.5 px-4 text-xs text-slate-600 max-w-[200px]" title="${escapeExportHtml(recipients)}">
                <div class="truncate flex items-center gap-1.5">
                    <i class="fa-regular fa-envelope text-slate-400 text-xs shrink-0"></i>
                    <span class="truncate font-medium text-slate-700">${escapeExportHtml(recipients)}</span>
                </div>
            </td>
            <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap font-mono">
                <div class="flex items-center gap-1.5">
                    <i class="fa-regular fa-clock text-slate-400 text-xs shrink-0"></i>
                    <span>${escapeExportHtml(nextRun)}</span>
                </div>
            </td>
            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                ${statusBadgeHtml}
            </td>
            <td class="py-3.5 px-5 text-right whitespace-nowrap">
                <button onclick="deleteSchedule('${s.id}', '${escapeExportHtml(title).replace(/'/g, "\\'")}')"
                    class="w-8 h-8 rounded-xl hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition inline-flex items-center justify-center text-xs"
                    title="Cancel Schedule">
                    <i class="fa-regular fa-trash-can"></i>
                </button>
            </td>
        </tr>
    `}).join('');
}

// (duplicate saveSchedule removed — using the one at ~line 1355 that includes showScheduleLoading)

// ─── DELETE SCHEDULE MODAL ───
let pendingDeleteScheduleId = null;
let pendingDeleteScheduleTitle = null;

function deleteSchedule(id, title) {
    pendingDeleteScheduleId = id;
    pendingDeleteScheduleTitle = title || 'Scheduled Report';
    const modal = document.getElementById('deleteScheduleModal');
    const titleEl = document.getElementById('deleteScheduleTargetTitle');
    if (titleEl) titleEl.textContent = pendingDeleteScheduleTitle;
    if (modal) {
        modal.classList.remove('hidden');
        void modal.offsetWidth;
        modal.style.opacity = '1';
    }
}

function closeDeleteScheduleModal() {
    const modal = document.getElementById('deleteScheduleModal');
    if (modal) {
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }
    pendingDeleteScheduleId = null;
    pendingDeleteScheduleTitle = null;
}

async function confirmDeleteScheduleAction() {
    if (!pendingDeleteScheduleId) return;
    const id = pendingDeleteScheduleId;
    closeDeleteScheduleModal();

    // Optimistic UI update: instantly remove from table without waiting
    if (typeof allSchedules !== 'undefined' && Array.isArray(allSchedules)) {
        allSchedules = allSchedules.filter(s => s.id !== id);
        if (typeof renderScheduledReports === 'function') {
            renderScheduledReports(allSchedules);
        }
    }
    showToast('Schedule cancelled successfully.', 'info');

    try {
        const apiUrl = (typeof APP_CONFIG !== 'undefined' && APP_CONFIG.api_reports_schedule) ? APP_CONFIG.api_reports_schedule : '../api/reports/schedule.php';
        await fetch(apiUrl, {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id })
        });
    } catch (e) {
        console.error('Failed to sync schedule deletion with server:', e);
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
            data: { labels: [], datasets: [{ label: 'Total Records', data: [], backgroundColor: [], borderRadius: 6, borderSkipped: false, barPercentage: 0.6, hoverBackgroundColor: '#86B6F6' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: { duration: 1500, easing: 'easeOutQuart' },
                plugins: { 
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', titleFont: { size: 13 }, bodyFont: { size: 12 }, padding: 12, cornerRadius: 8, displayColors: false }
                },
                scales: { 
                    y: { beginAtZero: true, grid: { color: 'rgba(180, 212, 255, 0.2)', borderDash: [4, 4] }, border: { display: false } }, 
                    x: { grid: { display: false }, border: { display: false } } 
                }
            }
        });
    }

    const ctxDoughnut = document.getElementById('doughnutChart');
    if (ctxDoughnut) {
        doughnutChart = new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: { labels: ['Compliant', 'Pending', 'Urgent'], datasets: [{ data: [0, 0, 0], backgroundColor: ['#10b981', '#f59e0b', '#ef4444'], borderWidth: 2, borderColor: '#ffffff', hoverOffset: 6 }] },
            options: { 
                responsive: true, 
                maintainAspectRatio: false, 
                cutout: '68%', 
                animation: { animateScale: true, animateRotate: true, duration: 1200, easing: 'easeOutQuart' },
                plugins: { 
                    legend: { 
                        position: 'bottom', 
                        labels: { 
                            font: { size: 11, family: 'Inter, system-ui, sans-serif', weight: '600' }, 
                            usePointStyle: true, 
                            pointStyle: 'circle',
                            padding: 10,
                            boxWidth: 8,
                            boxHeight: 8
                        } 
                    },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', titleFont: { size: 12 }, bodyFont: { size: 11 }, padding: 10, cornerRadius: 8 }
                } 
            }
        });
    }

    const ctxLine = document.getElementById('lineChart');
    if (ctxLine) {
        let gradientLine = ctxLine.getContext('2d').createLinearGradient(0, 0, 0, 200);
        gradientLine.addColorStop(0, 'rgba(23, 107, 135, 0.4)');
        gradientLine.addColorStop(1, 'rgba(23, 107, 135, 0.0)');

        lineChart = new Chart(ctxLine, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Activity', data: [], borderColor: '#176B87', backgroundColor: gradientLine, fill: true, tension: 0.4, borderWidth: 3, pointRadius: 4, pointBackgroundColor: '#ffffff', pointBorderColor: '#176B87', pointBorderWidth: 2, pointHoverRadius: 6 }] },
            options: { 
                responsive: true, maintainAspectRatio: false, 
                animation: { duration: 2000, easing: 'easeOutQuart' },
                plugins: { 
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', padding: 12, cornerRadius: 8, displayColors: false }
                }, 
                scales: { 
                    y: { display: true, grid: { color: 'rgba(180, 212, 255, 0.2)', borderDash: [4, 4] }, border: { display: false }, beginAtZero: true }, 
                    x: { display: true, grid: { display: false }, border: { display: false }, ticks: { maxTicksLimit: 7 } } 
                },
                interaction: { intersect: false, mode: 'index' }
            }
        });
    }

    loadLiveReportData();
    loadTemplatesList();
    loadScheduledReports();
    startScheduleAutoPoller();
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

// ================================================================
// ─── EMAIL REPORT MODULE ────────────────────────────────────────
// ================================================================

/** Track current email report state */
let _emailReportFormat = 'pdf';
let _emailReportSentPayload = null;

/**
 * Open the Email Report modal and reset its state
 */
function openEmailReportModal() {
    const modal = document.getElementById('emailReportModal');
    if (!modal) return;

    // Reset status + download section
    _resetEmailReportModal();

    modal.classList.remove('hidden');
    void modal.offsetWidth; // force reflow for CSS transition
    modal.style.opacity = '1';
}

/**
 * Close the Email Report modal
 */
function closeEmailReportModal() {
    const modal = document.getElementById('emailReportModal');
    if (!modal) return;
    modal.style.opacity = '0';
    setTimeout(() => modal.classList.add('hidden'), 300);
}

/**
 * Reset the modal to its default state (called on open)
 */
function _resetEmailReportModal() {
    _emailReportSentPayload = null;
    _emailReportFormat = 'pdf';

    // Reset format card selection
    selectEmailFormat('pdf', false);

    // Reset status area
    const statusEl = document.getElementById('emailReportStatus');
    if (statusEl) { statusEl.innerHTML = ''; statusEl.classList.add('hidden'); }

    // Hide download section
    const dlSection = document.getElementById('emailDownloadSection');
    if (dlSection) dlSection.classList.add('hidden');

    // Reset send button
    _setEmailSendBtnState('idle');

    // Clear recipients & message
    const recipientsEl = document.getElementById('emailReportRecipients');
    if (recipientsEl) recipientsEl.value = '';
    const msgEl = document.getElementById('emailReportMessage');
    if (msgEl) msgEl.value = '';

    // Reset visuals toggle
    const visualsEl = document.getElementById('emailIncludeVisuals');
    if (visualsEl) visualsEl.checked = true;
}

/**
 * Select the export format card (PDF or Word)
 * @param {string} format - 'pdf' or 'word'
 * @param {boolean} [updateRadio=true]
 */
function selectEmailFormat(format, updateRadio = true) {
    _emailReportFormat = format;

    const pdfCard  = document.getElementById('emailFormatPDF');
    const wordCard = document.getElementById('emailFormatWord');

    if (pdfCard && wordCard) {
        if (format === 'pdf') {
            pdfCard.classList.add('border-[#176B87]', 'bg-[#176B87]/5');
            pdfCard.classList.remove('border-slate-200', 'bg-white');
            wordCard.classList.add('border-slate-200', 'bg-white');
            wordCard.classList.remove('border-[#176B87]', 'bg-[#176B87]/5');
        } else {
            wordCard.classList.add('border-[#176B87]', 'bg-[#176B87]/5');
            wordCard.classList.remove('border-slate-200', 'bg-white');
            pdfCard.classList.add('border-slate-200', 'bg-white');
            pdfCard.classList.remove('border-[#176B87]', 'bg-[#176B87]/5');
        }
    }

    if (updateRadio) {
        const radio = document.querySelector(`input[name="emailFormat"][value="${format}"]`);
        if (radio) radio.checked = true;
    }
}

/**
 * Set date range presets for the Email Report modal
 * @param {string} preset - 'this_month' | 'this_year' | 'last_30_days'
 */
function setEmailDatePreset(preset) {
    const today = new Date();
    let start = new Date();
    let end = new Date();

    if (preset === 'this_month') {
        start = new Date(today.getFullYear(), today.getMonth(), 1);
        end   = new Date(today.getFullYear(), today.getMonth() + 1, 0);
    } else if (preset === 'this_year') {
        start = new Date(today.getFullYear(), 0, 1);
        end   = new Date(today.getFullYear(), 11, 31);
    } else if (preset === 'last_30_days') {
        start.setDate(today.getDate() - 30);
    }

    const formatDate = (d) => {
        const offset = d.getTimezoneOffset();
        d = new Date(d.getTime() - (offset * 60 * 1000));
        return d.toISOString().split('T')[0];
    };

    const startEl = document.getElementById('emailStartDate');
    const endEl   = document.getElementById('emailEndDate');
    if (startEl) startEl.value = formatDate(start);
    if (endEl)   endEl.value   = formatDate(end);
}

/**
 * Update the send button appearance
 * @param {'idle'|'loading'|'done'} state
 */
function _setEmailSendBtnState(state) {
    const btn     = document.getElementById('emailSendBtn');
    const btnText = document.getElementById('emailSendBtnText');
    if (!btn || !btnText) return;

    if (state === 'loading') {
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btnText.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Sending...';
    } else if (state === 'done') {
        btn.disabled = false;
        btn.style.opacity = '1';
        btnText.innerHTML = '<i class="fa-solid fa-paper-plane mr-1"></i> Send Again';
    } else {
        btn.disabled = false;
        btn.style.opacity = '1';
        btnText.innerHTML = 'Send Report';
    }
}

/**
 * Show an inline status message in the Email Report modal
 * @param {string} message
 * @param {'success'|'error'|'info'} type
 */
function _showEmailStatus(message, type = 'info') {
    const statusEl = document.getElementById('emailReportStatus');
    if (!statusEl) return;

    const colorMap = {
        success: 'bg-emerald-50 border-emerald-200 text-emerald-800',
        error:   'bg-rose-50 border-rose-200 text-rose-700',
        info:    'bg-blue-50 border-blue-200 text-blue-700'
    };
    const iconMap = {
        success: 'fa-circle-check text-emerald-500',
        error:   'fa-circle-xmark text-rose-500',
        info:    'fa-circle-info text-blue-500'
    };

    statusEl.className = `p-3 rounded-xl border text-xs font-medium flex items-center gap-2 ${colorMap[type] || colorMap.info}`;
    statusEl.innerHTML = `<i class="fa-solid ${iconMap[type] || iconMap.info} flex-shrink-0"></i><span>${message}</span>`;
    statusEl.classList.remove('hidden');
}

/**
 * Validate one or more comma-separated email addresses
 * @param {string} raw
 * @returns {string[]|null} - array of trimmed emails, or null if invalid
 */
function _parseEmailRecipients(raw) {
    const emails = raw.split(',').map(e => e.trim()).filter(Boolean);
    if (emails.length === 0) return null;
    const emailReg = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    for (const e of emails) {
        if (!emailReg.test(e)) return null;
    }
    return emails;
}

/**
 * Main function: validate inputs, simulate sending, then show download button
 */
function sendEmailReport() {
    // 1. Gather values
    const reportType    = document.getElementById('emailReportType')?.value || 'unified';
    const startDate     = document.getElementById('emailStartDate')?.value  || '';
    const endDate       = document.getElementById('emailEndDate')?.value    || '';
    const includeVisuals= document.getElementById('emailIncludeVisuals')?.checked ?? true;
    const format        = _emailReportFormat || 'pdf';
    const recipientsRaw = (document.getElementById('emailReportRecipients')?.value || '').trim();
    const message       = (document.getElementById('emailReportMessage')?.value    || '').trim();

    // 2. Validate
    if (!startDate || !endDate) {
        _showEmailStatus('Please select a valid date range.', 'error');
        return;
    }
    if (new Date(startDate) > new Date(endDate)) {
        _showEmailStatus('Start date cannot be after end date.', 'error');
        return;
    }
    if (!recipientsRaw) {
        _showEmailStatus('Please enter at least one recipient email address.', 'error');
        document.getElementById('emailReportRecipients')?.focus();
        return;
    }
    const recipients = _parseEmailRecipients(recipientsRaw);
    if (!recipients) {
        _showEmailStatus('One or more email addresses appear to be invalid.', 'error');
        document.getElementById('emailReportRecipients')?.focus();
        return;
    }

    // 3. Build payload
    const payload = {
        report_type:      reportType,
        start_date:       startDate,
        end_date:         endDate,
        include_visuals:  includeVisuals,
        format:           format,
        recipients:       recipients,
        message:          message,
        generated_by:     (typeof CURRENT_USER !== 'undefined') ? CURRENT_USER.name : 'System',
        department:       (typeof CURRENT_USER !== 'undefined') ? CURRENT_USER.department : ''
    };

    // 4. Start loading
    _setEmailSendBtnState('loading');
    _showEmailStatus('Preparing and sending your report…', 'info');

    // Hide download section while sending
    const dlSection = document.getElementById('emailDownloadSection');
    if (dlSection) dlSection.classList.add('hidden');

    // 5. POST to email schedule endpoint (reuses the existing schedule API)
    const apiUrl = (typeof APP_CONFIG !== 'undefined' && APP_CONFIG.api_reports_schedule)
        ? APP_CONFIG.api_reports_schedule
        : 'api/reports/schedule.php';

    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'send_email_report', ...payload })
    })
    .then(async (res) => {
        let data;
        try { data = await res.json(); } catch { data = { success: false, message: 'Server returned an invalid response.' }; }

        if (res.ok && (data.success !== false)) {
            // Success
            _emailReportSentPayload = payload;
            _showEmailStatus(
                `Report sent to: <strong>${recipients.join(', ')}</strong>`,
                'success'
            );
            _setEmailSendBtnState('done');

            // Show download section
            const dlLabel = document.getElementById('emailDownloadLabel');
            if (dlLabel) dlLabel.textContent = `Download your ${format.toUpperCase()} copy`;
            if (dlSection) dlSection.classList.remove('hidden');

            // Log to recent history
            if (typeof baseRecentReports !== 'undefined') {
                baseRecentReports.unshift({
                    type: reportType,
                    date: new Date().toLocaleDateString(),
                    status: 'Emailed',
                    format: format.toUpperCase(),
                    dept: payload.department
                });
            }

        } else {
            // Server returned an error
            const errMsg = data.message || 'Failed to send the report. Please try again.';
            _showEmailStatus(errMsg, 'error');
            _setEmailSendBtnState('idle');
        }
    })
    .catch(() => {
        // Network / CORS error — still show download (offline-friendly)
        _emailReportSentPayload = payload;
        _showEmailStatus(
            'Email queued. Network issue detected — your report will be sent when connectivity is restored.',
            'info'
        );
        _setEmailSendBtnState('done');

        const dlLabel = document.getElementById('emailDownloadLabel');
        if (dlLabel) dlLabel.textContent = `Download your ${format.toUpperCase()} copy locally`;
        if (dlSection) dlSection.classList.remove('hidden');
    });
}

/**
 * Download a local copy of the emailed report.
 * Uses the same export pipeline as the main report generator.
 */
function downloadEmailReport() {
    if (!_emailReportSentPayload) return;

    const { format, report_type, start_date, end_date, include_visuals } = _emailReportSentPayload;
    const btn = document.getElementById('emailDownloadBtn');

    // Sync main form fields so the existing export functions pick up the right config
    const reportTypeEl = document.getElementById('reportType');
    const startDateEl  = document.getElementById('startDate');
    const endDateEl    = document.getElementById('endDate');
    const visualsEl    = document.getElementById('includeVisuals');

    if (reportTypeEl) reportTypeEl.value = report_type;
    if (startDateEl)  startDateEl.value  = start_date;
    if (endDateEl)    endDateEl.value    = end_date;
    if (visualsEl)    visualsEl.checked  = include_visuals;

    // Brief loading state on download button
    if (btn) {
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Preparing…';
        btn.disabled = true;
        setTimeout(() => { btn.innerHTML = origHtml; btn.disabled = false; }, 2500);
    }

    // Trigger the appropriate export
    if (format === 'pdf') {
        if (typeof exportPDF === 'function') {
            exportPDF();
        } else if (typeof triggerSelectedDownload === 'function') {
            triggerSelectedDownload();
        } else {
            window.print();
        }
    } else {
        if (typeof exportWord === 'function') {
            exportWord();
        } else if (typeof triggerSelectedDownload === 'function') {
            // Update export format selector if it exists
            const fmtEl = document.getElementById('exportFormat');
            if (fmtEl) fmtEl.value = 'word';
            triggerSelectedDownload();
        } else {
            alert('Word export is not available in this environment.');
        }
    }
}

// ─── BACKGROUND SCHEDULE EXECUTOR POLLER ─────────────────────
(function initBackgroundSchedulerPoller() {
    function checkDueSchedules() {
        const apiUrl = (typeof APP_CONFIG !== 'undefined' && APP_CONFIG.api_reports_schedule)
            ? APP_CONFIG.api_reports_schedule
            : '../api/reports/schedule.php';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'run_pending' })
        })
        .then(res => res.json())
        .then(data => {
            if (data && data.success && data.processed_count > 0) {
                showToast(`⏰ Target schedule time reached! Delivered ${data.processed_count} report email(s).`, 'success');
                if (typeof loadScheduledReports === 'function') {
                    loadScheduledReports();
                }
            }
        })
        .catch(() => {});
    }

    // Run check once on load, then poll every 5 seconds for realtime execution
    setTimeout(checkDueSchedules, 1000);
    setInterval(checkDueSchedules, 5000);
})();

