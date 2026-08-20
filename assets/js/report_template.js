// ===== DYNAMIC REPORT TEMPLATES APP =====

let templates = [];
const API_URL = '../api/report_templates.php';

// ===== DOM refs =====
const $ = id => document.getElementById(id);
const tbody = $('templatesBody');
const emptyState = $('emptyState');
const searchInput = $('searchInput');
const statusFilter = $('statusFilter');
const typeFilter = $('typeFilter');
const clearFiltersBtn = $('clearFiltersBtn');
const modalOverlay = $('modalOverlay');
const modalTitle = $('modalTitle');
const closeModalBtn = $('closeModalBtn');
const cancelModalBtn = $('cancelModalBtn');
const openAddBtn = $('openAddModal');
const templateForm = $('templateForm');
const editId = $('editId');
const templateName = $('templateName');
const templateType = $('templateType');
const templateStatus = $('templateStatus');
const templateDesc = $('templateDesc');
const saveBtnText = $('saveBtnText');
const totalTemplates = $('totalTemplates');
const activeTemplates = $('activeTemplates');
const draftTemplates = $('draftTemplates');
const toastContainer = $('toastContainer');

// ===== Fetch from API =====
async function fetchTemplates() {
  try {
    const res = await fetch(API_URL);
    const data = await res.json();
    if (data && data.success && Array.isArray(data.data)) {
      templates = data.data;
      render();
    } else {
      showToast(data.message || 'Failed to load templates', 'error');
    }
  } catch (err) {
    console.error('Error fetching templates:', err);
    showToast('Network error loading templates', 'error');
  }
}

// ===== Render =====
function render() {
  const search = searchInput.value.toLowerCase().trim();
  const statusVal = statusFilter.value;
  const typeVal = typeFilter.value;

  let filtered = templates.filter(t =>
    ((t.name || '').toLowerCase().includes(search) || (t.description || '').toLowerCase().includes(search)) &&
    (statusVal === 'all' || t.status === statusVal) &&
    (typeVal === 'all' || t.type === typeVal)
  );

  totalTemplates.textContent = templates.length;
  activeTemplates.textContent = templates.filter(t => t.status === 'active').length;
  draftTemplates.textContent = templates.filter(t => t.status === 'draft').length;

  if (filtered.length === 0) {
    tbody.innerHTML = '';
    emptyState.classList.remove('hidden');
    return;
  }
  emptyState.classList.add('hidden');

  const typeMap = { inspection: 'Inspection', audit: 'Audit', water: 'Water Quality', waste: 'Waste Management' };
  tbody.innerHTML = filtered.map(t => `
    <tr>
        <td class="px-5 py-3 font-medium text-gray-800">
            <div>${t.name}</div>
            ${t.description ? `<div class="text-xs text-gray-400 mt-0.5">${t.description}</div>` : ''}
        </td>
        <td class="px-5 py-3 text-gray-600">${typeMap[t.type] || t.type}</td>
        <td class="px-5 py-3">
            <span class="status-badge ${t.status}">
                <span class="dot"></span> ${(t.status || 'active').charAt(0).toUpperCase() + (t.status || 'active').slice(1)}
            </span>
        </td>
        <td class="px-5 py-3 text-right">
            <button onclick="editTemplate(${t.id})" class="text-gray-400 hover:text-[#176B87] px-1 transition"><i class="fas fa-edit"></i></button>
            <button onclick="deleteTemplate(${t.id})" class="text-gray-400 hover:text-red-500 px-1 transition"><i class="fas fa-trash"></i></button>
        </td>
    </tr>
  `).join('');
}

// ===== CRUD Operations via API =====
function editTemplate(id) {
  const t = templates.find(t => String(t.id) === String(id));
  if (!t) return;
  modalTitle.textContent = 'Edit Template';
  saveBtnText.textContent = 'Update';
  editId.value = id;
  templateName.value = t.name;
  templateType.value = t.type;
  templateStatus.value = t.status;
  templateDesc.value = t.description || '';
  modalOverlay.classList.remove('hidden');
}

async function deleteTemplate(id) {
  if (!confirm('Are you sure you want to delete this template?')) return;
  try {
    const res = await fetch(`${API_URL}?id=${id}`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await res.json();
    if (data.success) {
      templates = templates.filter(t => String(t.id) !== String(id));
      render();
      showToast('Template deleted successfully', 'success');
    } else {
      showToast(data.message || 'Failed to delete template', 'error');
    }
  } catch (err) {
    showToast('Failed to delete template', 'error');
  }
}

async function saveTemplate(e) {
  e.preventDefault();
  const name = templateName.value.trim();
  if (!name) return showToast('Template name is required', 'error');

  const payload = {
    name,
    type: templateType.value,
    status: templateStatus.value,
    description: templateDesc.value.trim()
  };

  const id = editId.value;
  const method = id ? 'PUT' : 'POST';
  if (id) payload.id = id;

  try {
    const res = await fetch(API_URL, {
      method: method,
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      showToast(data.message || (id ? 'Template updated' : 'Template created'), 'success');
      closeModal();
      await fetchTemplates();
    } else {
      showToast(data.message || 'Error saving template', 'error');
    }
  } catch (err) {
    showToast('Network error saving template', 'error');
  }
}

function closeModal() {
  modalOverlay.classList.add('hidden');
  templateForm.reset();
  editId.value = '';
  modalTitle.textContent = 'New Template';
  saveBtnText.textContent = 'Save';
}

function showToast(msg, type = 'success') {
  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}`;
  toastContainer.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transform = 'translateX(30px)';
    setTimeout(() => toast.remove(), 300);
  }, 3000);
}

// ===== Events =====
openAddBtn.addEventListener('click', () => { closeModal(); modalOverlay.classList.remove('hidden'); });
closeModalBtn.addEventListener('click', closeModal);
cancelModalBtn.addEventListener('click', closeModal);
modalOverlay.addEventListener('click', (e) => { if (e.target === modalOverlay) closeModal(); });
templateForm.addEventListener('submit', saveTemplate);

searchInput.addEventListener('input', render);
statusFilter.addEventListener('change', render);
typeFilter.addEventListener('change', render);
clearFiltersBtn.addEventListener('click', () => {
  searchInput.value = '';
  statusFilter.value = 'all';
  typeFilter.value = 'all';
  render();
  showToast('Filters reset');
});

// ===== Init =====
fetchTemplates();