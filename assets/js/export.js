// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// DATA – with date fields for filtering
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// UPDATED
const reportData = {
  executive: {
    icon: '📊',
    label: 'AI Executive Reports',
    items: [
      { name: 'AI Executive Summary', formats: ['PDF'], date: '2026-07-10' },
      { name: 'Daily Patient Report', formats: ['PDF', 'Excel'], date: '2026-07-12' },
      { name: 'Weekly Consultation Summary', formats: ['PDF', 'Excel'], date: '2026-07-14' },
      { name: 'Monthly Health Statistics', formats: ['PDF', 'Excel'], date: '2026-07-16' },
    ]
  },
  sanitation: {
    icon: '🧹',
    label: 'Sanitation Reports',
    items: [
      { name: 'Monthly Permit Statistics', formats: ['PDF'], date: '2026-07-09' },
      { name: 'Inspection Success Rate', formats: ['PDF', 'Excel'], date: '2026-07-11' },
      { name: 'Processing Time Average', formats: ['PDF', 'Excel'], date: '2026-07-13' },
    ]
  },
  wastewater: {
    icon: '💧',
    label: 'Wastewater Reports',
    items: [
      { name: 'Monthly Sanitation Report', formats: ['PDF'], date: '2026-07-08' },
      { name: 'Serviced vs Unserviced Ratio', formats: ['PDF', 'Excel'], date: '2026-07-10' },
      { name: 'Overdue Maintenance List', formats: ['PDF', 'Excel'], date: '2026-07-15' },
      { name: 'Compliance Rate', formats: ['PDF'], date: '2026-07-17' },
    ]
  },
  healthcenter: {
    icon: '🏥',
    label: 'Health Center Reports',
    items: [
      { name: 'Daily Patient Report', formats: ['PDF', 'Excel'], date: '2026-07-12' },
      { name: 'Weekly Consultation Summary', formats: ['PDF', 'Excel'], date: '2026-07-14' },
      { name: 'Monthly Health Statistics', formats: ['PDF', 'Excel'], date: '2026-07-16' },
    ]
  },
  immunization: {
    icon: '💉',
    label: 'Immunization Reports',
    items: [
      { name: 'Monthly Immunization Report', formats: ['PDF'], date: '2026-07-11' },
    ]
  },
  nutrition: {
    icon: '🥗',
    label: 'Nutrition Status Report',
    items: [
      { name: 'Nutrition Status Report', formats: ['PDF', 'Excel'], date: '2026-07-13' },
    ]
  },
  vaccination: {
    icon: '🛡️',
    label: 'Vaccination Coverage Rate',
    items: [
      { name: 'Vaccination Coverage Rate', formats: ['PDF', 'Excel'], date: '2026-07-15' },
    ]
  },
  surveillance: {
    icon: '🔬',
    label: 'Surveillance Reports',
    items: [
      { name: 'Weekly Disease Report', formats: ['PDF'], date: '2026-07-09' },
      { name: 'Outbreak Reports', formats: ['PDF', 'Excel'], date: '2026-07-12' },
      { name: 'Barangay Health Risk Report', formats: ['PDF'], date: '2026-07-14' },
    ]
  }
};

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// STATE
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
let currentCategory = 'executive';
let selectedFormats = {};
let searchTerm = '';
let dateFrom = '';
let dateTo = '';

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// DOM REFS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
const categoryTabs = document.getElementById('categoryTabs');
const categoryIcon = document.getElementById('categoryIcon');
const categoryLabel = document.getElementById('categoryLabel');
const reportList = document.getElementById('reportList');
const reportCount = document.getElementById('reportCount');
const totalFormats = document.getElementById('totalFormats');
const lastUpdated = document.getElementById('lastUpdated');
const selectAllBtn = document.getElementById('selectAllBtn');
const searchInput = document.getElementById('searchInput');
const dateFromInput = document.getElementById('dateFrom');
const dateToInput = document.getElementById('dateTo');
const clearFiltersBtn = document.getElementById('clearFiltersBtn');

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// RENDER FUNCTIONS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
function renderTabs() {
  let html = '';
  for (const [key, data] of Object.entries(reportData)) {
    const active = key === currentCategory ? 'active' : '';
    html += `
                    <button class="category-tab ${active}" data-category="${key}">
                        <span class="icon">${data.icon}</span>
                        ${data.label}
                    </button>
                `;
  }
  categoryTabs.innerHTML = html;
  document.querySelectorAll('.category-tab').forEach(tab => {
    tab.addEventListener('click', function () {
      const cat = this.dataset.category;
      if (cat && cat !== currentCategory) {
        currentCategory = cat;
        renderAll();
      }
    });
  });
}

function renderReports() {
  const data = reportData[currentCategory];
  if (!data) return;

  // Update header
  categoryIcon.textContent = data.icon;
  categoryLabel.textContent = data.label;

  // ── Filter items ──
  let items = data.items;

  if (searchTerm.trim() !== '') {
    const term = searchTerm.trim().toLowerCase();
    items = items.filter(item => item.name.toLowerCase().includes(term));
  }

  if (dateFrom) {
    items = items.filter(item => item.date >= dateFrom);
  }
  if (dateTo) {
    items = items.filter(item => item.date <= dateTo);
  }

  reportCount.textContent = items.length;

  if (items.length === 0) {
    reportList.innerHTML = `
                    <div class="text-center py-8 text-slate-400 text-sm bg-white/30 backdrop-blur-sm rounded-xl border border-dashed border-slate-200">
                        No reports match your filters.
                    </div>
                `;
    totalFormats.textContent = '0';
    return;
  }

  let html = '';
  let selectedCount = 0;

  items.forEach((item, idx) => {
    const originalIndex = data.items.indexOf(item);
    const key = `${currentCategory}-${originalIndex}`;

    if (!selectedFormats[key]) {
      selectedFormats[key] = item.formats[0] || 'PDF';
    }
    if (!item.formats.includes(selectedFormats[key])) {
      selectedFormats[key] = item.formats[0] || 'PDF';
    }

    const selected = selectedFormats[key];
    const hasPDF = item.formats.includes('PDF');
    const hasExcel = item.formats.includes('Excel');

    if (selected) selectedCount++;

    const pdfBtn = hasPDF
      ? `<button class="format-btn pdf ${selected === 'PDF' ? 'active' : ''}" data-key="${key}" data-format="PDF"><span class="badge-dot"></span>PDF</button>`
      : `<button class="format-btn pdf" disabled><span class="badge-dot"></span>PDF</button>`;

    const excelBtn = hasExcel
      ? `<button class="format-btn excel ${selected === 'Excel' ? 'active' : ''}" data-key="${key}" data-format="Excel"><span class="badge-dot"></span>Excel</button>`
      : `<button class="format-btn excel" disabled><span class="badge-dot"></span>Excel</button>`;

    const downloadEnabled = selected ? '' : 'disabled';

    html += `
                    <div class="report-card" data-key="${key}">
                        <div class="report-name">
                            <span class="doc-icon">📄</span>
                            ${item.name}
                            <span class="report-meta">📅 ${item.date}</span>
                        </div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <div class="format-group">
                                ${pdfBtn}
                                ${excelBtn}
                            </div>
                            <button class="download-btn" data-key="${key}" ${downloadEnabled}>
                                <span>⬇</span> Download
                            </button>
                        </div>
                    </div>
                `;
  });

  reportList.innerHTML = html;
  totalFormats.textContent = selectedCount;

  // ── Attach format button events ──
  document.querySelectorAll('.format-btn:not([disabled])').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const key = this.dataset.key;
      const format = this.dataset.format;
      if (key && format) {
        selectedFormats[key] = format;
        renderReports();
      }
    });
  });

  // ── Download button events — calls api/export.php for real file download ──
  document.querySelectorAll('.download-btn:not([disabled])').forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      const key = this.dataset.key;
      const fmt = (selectedFormats[key] || 'PDF').toLowerCase();
      if (!key || !fmt) return;

      // Map JS category key → API category parameter
      const categoryMap = {
        executive:   'patients',
        sanitation:  'permits',
        wastewater:  'wastewater',
        healthcenter:'patients',
        immunization:'patients',
        nutrition:   'patients',
        vaccination: 'patients',
        surveillance:'surveillance'
      };
      const category = categoryMap[currentCategory] || currentCategory;

      // Build query string
      const params = new URLSearchParams({ category, format: fmt });
      if (dateFrom) params.set('date_from', dateFrom);
      if (dateTo)   params.set('date_to',   dateTo);

      // Trigger browser download via navigation — browser will save the file
      window.location.href = `../api/export.php?${params.toString()}`;
    });
  });
}

function renderAll() {
  renderTabs();
  renderReports();
  const now = new Date();
  lastUpdated.textContent = `Updated ${now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
}

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// FILTER EVENT LISTENERS
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
searchInput.addEventListener('input', function () {
  searchTerm = this.value;
  renderReports();
});

dateFromInput.addEventListener('change', function () {
  dateFrom = this.value;
  renderReports();
});

dateToInput.addEventListener('change', function () {
  dateTo = this.value;
  renderReports();
});

clearFiltersBtn.addEventListener('click', function () {
  searchInput.value = '';
  dateFromInput.value = '';
  dateToInput.value = '';
  searchTerm = '';
  dateFrom = '';
  dateTo = '';
  renderReports();
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// SELECT ALL
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
selectAllBtn.addEventListener('click', function () {
  const data = reportData[currentCategory];
  if (!data) return;
  data.items.forEach((item, idx) => {
    const key = `${currentCategory}-${idx}`;
    selectedFormats[key] = item.formats[0] || 'PDF';
  });
  renderReports();
});

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// INIT
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
renderAll();