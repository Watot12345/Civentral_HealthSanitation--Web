
    function getRoleBadgeColor(roleName) {
        const r = (roleName || '').trim();
        const map = {
            'System Admin': 'bg-purple-100 text-purple-700 border border-purple-200',
            'System Administrator': 'bg-purple-100 text-purple-700 border border-purple-200',
            'Admin': 'bg-purple-100 text-purple-700 border border-purple-200',
            'Doctor': 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            'Physician': 'bg-emerald-100 text-emerald-700 border border-emerald-200',
            'Dentist': 'bg-cyan-100 text-cyan-700 border border-cyan-200',
            'Nurse': 'bg-teal-100 text-teal-700 border border-teal-200',
            'Midwife': 'bg-teal-100 text-teal-700 border border-teal-200',
            'Health Officer': 'bg-amber-100 text-amber-700 border border-amber-200',
            'Department Head': 'bg-indigo-100 text-indigo-700 border border-indigo-200',
            'Nutritionist': 'bg-lime-100 text-lime-700 border border-lime-200',
            'Pharmacist': 'bg-rose-100 text-rose-700 border border-rose-200',
            'Medical Staff': 'bg-sky-100 text-sky-700 border border-sky-200',
            'Staff': 'bg-sky-100 text-sky-700 border border-sky-200',
            'Citizen': 'bg-blue-100 text-blue-700 border border-blue-200',
            'Patient': 'bg-slate-100 text-slate-700 border border-slate-200'
        };
        return map[r] || 'bg-slate-100 text-slate-700 border border-slate-200';
    }

    // ============================================================
    // FILTER USERS (Search + Role + Status)
    // ============================================================
    function filterUsers() {
        const searchQuery = (document.getElementById('userSearchInput')?.value || '').toLowerCase().trim();
        const roleFilter = (document.getElementById('roleFilter')?.value || '').toLowerCase().trim();
        const statusFilter = (document.getElementById('statusFilter')?.value || '').trim();
        
        const rows = document.querySelectorAll('.user-row');
        let visibleCount = 0;

        rows.forEach(row => {
            const employeeId = (row.dataset.employeeid || '').toLowerCase().trim();
            const fullName = (row.dataset.fullname || '').toLowerCase().trim();
            const username = (row.dataset.username || '').toLowerCase().trim();
            const email = (row.dataset.email || '').toLowerCase().trim();
            const department = (row.dataset.department || '').toLowerCase().trim();
            const role = (row.dataset.role || '').toLowerCase().trim();
            const roleDesc = (row.dataset.roledescription || '').toLowerCase().trim();
            const status = (row.dataset.status || '').trim();
            
            let show = true;

            // 1. Live Search Filter (matches Employee ID, Name, Username, Email, Department)
            if (searchQuery !== '') {
                const matchEmpId = employeeId.includes(searchQuery);
                const matchName  = fullName.includes(searchQuery);
                const matchUser  = username.includes(searchQuery);
                const matchEmail = email.includes(searchQuery);
                const matchDept  = department.includes(searchQuery);

                if (!matchEmpId && !matchName && !matchUser && !matchEmail && !matchDept) {
                    show = false;
                }
            }

            // 2. Role Filter
            if (roleFilter !== 'all') {
                const matchRole = role === roleFilter || role.includes(roleFilter);
                const matchDesc = roleDesc === roleFilter || roleDesc.includes(roleFilter);
                if (!matchRole && !matchDesc) {
                    show = false;
                }
            }

            // 3. Status Filter
            if (statusFilter !== 'all' && status !== statusFilter) {
                show = false;
            }
            
            row.style.display = show ? 'table-row' : 'none';
            if (show) visibleCount++;
        });

        // Update count display badge
        const registeredCountEl = document.getElementById('registeredCount');
        if (registeredCountEl) {
            registeredCountEl.textContent = visibleCount;
        }
    }

    // ============================================================
    // FILTER ACTIVITY
    // ============================================================
    function filterActivity(status) {
        document.querySelectorAll('.filter-btn-activity').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-dark', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700');
        });
        
        if (status === 'all') {
            document.getElementById('act-all').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Success') {
            document.getElementById('act-success').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (status === 'Failed') {
            document.getElementById('act-failed').classList.add('active', 'bg-brand-dark', 'text-white');
        }
        
        const rows = document.querySelectorAll('.activity-row');
        rows.forEach(row => {
            if (status === 'all' || row.dataset.status === status) {
                row.style.display = 'table-row';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Cascading Dropdown Mappings for Department -> Primary Role -> Position (Head to Lower Level)
    const PRIMARY_ROLES = [
        'Health Center Director',
        'Medical Practitioner',
        'Health Center Staff',
        'Sanitation Director',
        'Sanitation Officer',
        'Immunization Lead',
        'Nutrition Staff',
        'Wastewater Lead',
        'Surveillance Lead',
        'Surveillance Staff'
    ];

    const DEPT_TO_ROLES = {
        'Health Center Services': ['Health Center Director', 'Medical Practitioner', 'Health Center Staff'],
        'Health Center': ['Health Center Director', 'Medical Practitioner', 'Health Center Staff'],

        'Sanitation Permits': ['Sanitation Director', 'Sanitation Officer'],
        'Sanitation': ['Sanitation Director', 'Sanitation Officer'],

        'Immunization & Nutrition': ['Immunization Lead', 'Nutrition Staff'],
        'Immunization': ['Immunization Lead'],
        'Nutrition': ['Nutrition Staff'],

        'Wastewater Services': ['Wastewater Lead'],
        'Wastewater': ['Wastewater Lead'],

        'Health Surveillance': ['Surveillance Lead', 'Surveillance Staff'],
        'Administration': ['System Admin']
    };

    const ROLE_TO_DESCRIPTIONS = {
        'System Admin': ['Admin'],
        'Health Center Director': ['Health Center Director'],
        'Medical Practitioner': ['Doctor', 'Nurse', 'Dentist', 'Laboratory Technician'],
        'Health Center Staff': ['Medical Records Clerk', 'Appointment Clerk'],
        'Sanitation Director': ['Sanitation Director', 'Sanitation Officer'],
        'Sanitation Officer': ['Inspector', 'Permit Clerk', 'Cashier'],
        'Immunization Lead': ['Immunization Coordinator', 'Midwife'],
        'Nutrition Staff': ['Nutritionist', 'Nutrition Educator'],
        'Wastewater Lead': ['Wastewater Officer'],
        'Surveillance Lead': ['Surveillance Officer', 'Surveillance Coordinator']
    };

    function updateRoleCardCountersJS() {
        const rows = document.querySelectorAll('.user-row');
        const counts = {};

        rows.forEach(row => {
            const role = (row.dataset.role || '').trim().toLowerCase();
            const desc = (row.dataset.roledescription || '').trim().toLowerCase();

            if (role) counts[role] = (counts[role] || 0) + 1;
            if (desc) counts[desc] = (counts[desc] || 0) + 1;
        });

        const roleCards = document.querySelectorAll('.role-item-card');
        roleCards.forEach(card => {
            const rName = (card.dataset.rolename || '').trim().toLowerCase();
            const countSpan = card.querySelector('.role-user-count');
            if (countSpan && rName) {
                const count = counts[rName] || 0;
                countSpan.textContent = `${count} users`;
            }
        });
    }

    const CURRENT_USER_DEPT = "PHP_EXPR";
    const IS_SYSTEM_ADMIN   = "PHP_EXPR";

    function onDepartmentChange(dept, targetRole = '') {
        if (!IS_SYSTEM_ADMIN && CURRENT_USER_DEPT) {
            dept = CURRENT_USER_DEPT;
        }
        const roleSelect = document.getElementById('roleId');
        if (!roleSelect) return;
        roleSelect.innerHTML = '<option value="">Select Primary Role</option>';
        
        let roles = dept && DEPT_TO_ROLES[dept] ? DEPT_TO_ROLES[dept] : PRIMARY_ROLES;
        roles.forEach(r => {
            if (r === 'System Admin') return; // Cannot select Admin
            if (!IS_SYSTEM_ADMIN && (r.includes('Director') || r.includes('System Admin'))) {
                return; // Non-admin Department Heads cannot register Director roles
            }
            const opt = document.createElement('option');
            opt.value = r;
            opt.textContent = r;
            if (r === targetRole) opt.selected = true;
            roleSelect.appendChild(opt);
        });
        
        if (targetRole) {
            onRoleChange(targetRole);
        } else {
            onRoleChange(roleSelect.value);
        }
    }


    function generateNextEmployeeIdJS(dept, role) {
        const deptRolePrefixes = {
            'Health Center Services_Health Center Director': { prefix: 'HCD-', pad: 4 },
            'Health Center Services_Medical Practitioner':   { prefix: 'HMP-', pad: 4 },
            'Health Center Services_Health Center Staff':     { prefix: 'HCS-', pad: 4 },
            'Health Center_Health Center Director':          { prefix: 'HCD-', pad: 4 },
            'Health Center_Medical Practitioner':            { prefix: 'HMP-', pad: 4 },
            'Health Center_Health Center Staff':              { prefix: 'HCS-', pad: 4 },

            'Sanitation Permits_Sanitation Director':        { prefix: 'SD-',  pad: 4 },
            'Sanitation Permits_Sanitation Officer':         { prefix: 'SO-',  pad: 4 },
            'Sanitation_Sanitation Director':                { prefix: 'SD-',  pad: 4 },
            'Sanitation_Sanitation Officer':                 { prefix: 'SO-',  pad: 4 },

            'Immunization & Nutrition_Immunization Lead':    { prefix: 'IL-',  pad: 4 },
            'Immunization & Nutrition_Nutrition Staff':      { prefix: 'NS-',  pad: 4 },
            'Immunization_Immunization Lead':                { prefix: 'IL-',  pad: 4 },
            'Nutrition_Nutrition Staff':                     { prefix: 'NS-',  pad: 4 },

            'Wastewater Services_Wastewater Lead':           { prefix: 'WL-',  pad: 4 },
            'Wastewater_Wastewater Lead':                    { prefix: 'WL-',  pad: 4 },

            'Health Surveillance_Surveillance Lead':         { prefix: 'SL-',  pad: 4 },
            'Administration_System Admin':                   { prefix: 'HSA-ADMIN-', pad: 2 }
        };

        const deptPrefixes = {
            'Health Center Services':   { prefix: 'HCD-', pad: 4 },
            'Health Center':            { prefix: 'HCD-', pad: 4 },
            'Sanitation Permits':       { prefix: 'SD-',  pad: 4 },
            'Sanitation':               { prefix: 'SD-',  pad: 4 },
            'Immunization & Nutrition': { prefix: 'IL-',  pad: 4 },
            'Immunization':             { prefix: 'IL-',  pad: 4 },
            'Nutrition':                { prefix: 'NS-',  pad: 4 },
            'Wastewater Services':      { prefix: 'WL-',  pad: 4 },
            'Wastewater':               { prefix: 'WL-',  pad: 4 },
            'Health Surveillance':      { prefix: 'SL-',  pad: 4 },
            'Administration':           { prefix: 'HSA-ADMIN-', pad: 2 }
        };

        const key = `${dept}_${role}`;
        const config = deptRolePrefixes[key] || deptPrefixes[dept] || { prefix: 'EMP-', pad: 4 };
        const prefix = config.prefix;
        const pad = config.pad;

        const rows = document.querySelectorAll('.user-row');
        let maxNum = 0;

        rows.forEach(row => {
            const empId = row.dataset.employeeid || row.dataset.username || '';
            if (empId.startsWith(prefix)) {
                const numPart = empId.substring(prefix.length);
                const num = parseInt(numPart, 10);
                if (!isNaN(num) && num > maxNum) {
                    maxNum = num;
                }
            }
        });

        const nextNum = (maxNum + 1).toString().padStart(pad, '0');
        return prefix + nextNum;
    }

    function onRoleChange(role, targetDesc = '') {
        const descSelect = document.getElementById('roleDescription');
        if (descSelect) {
            descSelect.innerHTML = '<option value="">Select Position</option>';
            let descs = role && ROLE_TO_DESCRIPTIONS[role] ? ROLE_TO_DESCRIPTIONS[role] : [];
            descs.forEach(d => {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                if (d === targetDesc) opt.selected = true;
                descSelect.appendChild(opt);
            });
        }

        // Auto-generate Employee ID when adding a new user
        const userId = document.getElementById('userId')?.value;
        const dept = document.getElementById('department')?.value || '';
        const usernameInput = document.getElementById('username');
        if (!userId && usernameInput && (dept || role)) {
            usernameInput.value = generateNextEmployeeIdJS(dept, role);
        }
    }

    // ============================================================
    // OPEN ADD USER MODAL (RESET FORM)
    // ============================================================
    function openAddUserModal() {
        const form = document.getElementById('userForm');
        if (form) form.reset();
        document.getElementById('userId').value = '';
        const initialDept = (!IS_SYSTEM_ADMIN && CURRENT_USER_DEPT) ? CURRENT_USER_DEPT : 'Health Center Services';
        const deptSelect = document.getElementById('department');
        if (deptSelect) deptSelect.value = initialDept;

        onDepartmentChange(initialDept);
        document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-plus text-brand-medium"></i> Register New User';
        document.getElementById('userFormSubmit').innerHTML = '<i class="fa-solid fa-save mr-1.5"></i> Register User';
        const err = document.getElementById('userFormError');
        if (err) err.classList.add('hidden');
        
        const initialRole = (DEPT_TO_ROLES[initialDept] && DEPT_TO_ROLES[initialDept][0]) ? DEPT_TO_ROLES[initialDept][0] : 'Health Center Director';
        document.getElementById('username').value = generateNextEmployeeIdJS(initialDept, initialRole);
        
        openModal('addUserModal');
    }


    // Override openModal for addUserModal to ensure clean state
    const _origOpenModal = openModal;
    openModal = function(id) {
        if (id === 'addUserModal' && !document.getElementById('userId').value) {
            document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-plus text-brand-medium"></i> Register New User';
            document.getElementById('userFormSubmit').innerHTML = '<i class="fa-solid fa-save mr-1.5"></i> Register User';
        }
        _origOpenModal(id);
    };

    // ============================================================
    // REAL-TIME REACTIVE DOM UPDATES
    // ============================================================
    function updateKPISummariesJS() {
        const rows = document.querySelectorAll('.user-row');
        const total = rows.length;
        let active = 0;
        let inactive = 0;

        rows.forEach(r => {
            const st = (r.dataset.status || 'Active').trim();
            if (st === 'Active') active++;
            else if (st === 'Inactive') inactive++;
        });

        const totalEl = document.getElementById('kpiTotalUsers');
        if (totalEl) totalEl.textContent = total;

        const activeEl = document.getElementById('kpiActiveUsers');
        if (activeEl) activeEl.textContent = active;

        const subActive = document.getElementById('kpiSubActive');
        if (subActive) subActive.textContent = `${active} Active`;

        const subInactive = document.getElementById('kpiSubInactive');
        if (subInactive) subInactive.textContent = `${inactive} Inactive`;

        const activePct = document.getElementById('kpiActivePercent');
        if (activePct) activePct.textContent = `${total > 0 ? Math.round((active / total) * 100) : 0}% of total`;

        const headerBadge = document.getElementById('kpiHeaderUserBadge');
        if (headerBadge) headerBadge.innerHTML = `<i class="fa-solid fa-users-cog"></i> ${total} Users`;

        const regCount = document.getElementById('registeredCount');
        if (regCount) regCount.textContent = total;
    }

    function updateRoleCardCountersJS() {
        const counts = {};
        document.querySelectorAll('.user-row').forEach(row => {
            const rName = row.dataset.role || '';
            const rDesc = row.dataset.roledescription || '';
            if (rName) counts[rName] = (counts[rName] || 0) + 1;
            if (rDesc && rDesc !== rName) counts[rDesc] = (counts[rDesc] || 0) + 1;
        });

        document.querySelectorAll('.role-item-card').forEach(card => {
            const rName = card.dataset.rolename || '';
            const countSpan = card.querySelector('.role-user-count');
            if (countSpan) {
                const count = counts[rName] || 0;
                countSpan.textContent = `${count} users`;
            }
        });
    }

    const CURRENT_USER_NAME = "PHP_EXPR";
    const CURRENT_USER_ROLE = "PHP_EXPR";
    const CURRENT_CLIENT_IP = "PHP_EXPR";

    function getJSClientDevice() {
        const ua = navigator.userAgent;
        let os = "Linux";
        if (ua.indexOf("Win") !== -1) os = "Windows 11";
        else if (ua.indexOf("Mac") !== -1) os = "macOS";
        else if (ua.indexOf("Linux") !== -1 || ua.indexOf("X11") !== -1) os = "Linux";
        else if (ua.indexOf("Android") !== -1) os = "Android 14";
        else if (ua.indexOf("iPhone") !== -1 || ua.indexOf("iPad") !== -1) os = "iOS 17";

        let browser = "Chrome";
        if (ua.indexOf("Firefox") !== -1) browser = "Firefox";
        else if (ua.indexOf("Chrome") !== -1 && ua.indexOf("Edg") === -1) browser = "Chrome";
        else if (ua.indexOf("Safari") !== -1 && ua.indexOf("Chrome") === -1) browser = "Safari";
        else if (ua.indexOf("Edg") !== -1) browser = "Edge";

        const isMobile = /Mobi|Android|iPhone/i.test(ua);
        return `${isMobile ? 'Mobile' : 'Desktop'} • ${browser} (${os})`;
    }

    function addActivityLogJS(actionText, status = 'Success', role = CURRENT_USER_ROLE, userName = CURRENT_USER_NAME) {
        const tbody = document.getElementById('activityTableBody');
        if (!tbody) return;

        const emptyTd = tbody.querySelector('tr td[colspan]');
        if (emptyTd) emptyTd.closest('tr').remove();

        const dateStr = new Date().toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' });
        const deviceStr = getJSClientDevice();
        const tr = document.createElement('tr');
        tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition activity-row';
        tr.dataset.status = status;
        tr.innerHTML = `
            <td class="px-4 py-3 font-medium text-slate-700">${userName}</td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-xs font-semibold">
                    ${role}
                </span>
            </td>
            <td class="px-4 py-3 text-slate-600 text-sm font-medium">${actionText}</td>
            <td class="px-4 py-3 text-slate-500 text-xs">${dateStr}</td>
            <td class="px-4 py-3 text-xs">
                <span class="font-mono font-semibold text-slate-700 block">${CURRENT_CLIENT_IP}</span>
                <span class="text-[10px] text-slate-400 block mt-0.5"><i class="fas fa-desktop text-[8px] mr-1"></i>${deviceStr}</span>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-1 ${status === 'Success' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'} rounded-full text-xs font-semibold">
                    ${status}
                </span>
            </td>
        `;
        tbody.insertBefore(tr, tbody.firstChild);

        const actTotal = document.getElementById('kpiTotalActivities');
        if (actTotal) {
            actTotal.textContent = tbody.querySelectorAll('tr.activity-row').length;
        }
    }

    // ============================================================
    // SUBMIT USER FORM (WITH CONFIRMATION MODAL & ZERO RELOAD)
    // ============================================================
    let pendingUserFormData = null;

    function submitUserForm(e) {
        e.preventDefault();
        const form = document.getElementById('userForm');
        const errDiv = document.getElementById('userFormError');
        if (errDiv) errDiv.classList.add('hidden');

        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const userId = document.getElementById('userId').value;
        const action = userId ? 'update' : 'create';

        const fullName = (document.getElementById('fullName')?.value || '').trim();
        const username = (document.getElementById('username')?.value || '').trim();
        const dept = document.getElementById('department')?.value || '';
        const role = document.getElementById('roleId')?.value || '';
        const desc = document.getElementById('roleDescription')?.value || '';
        const status = document.getElementById('status')?.value || 'Active';
        const password = document.getElementById('password')?.value || '';

        if (!fullName) {
            if (errDiv) {
                errDiv.textContent = 'Full Name is required.';
                errDiv.classList.remove('hidden');
            }
            return;
        }

        if (!action || (action === 'create' && !password)) {
            if (errDiv) {
                errDiv.textContent = 'Password is required when registering a new user.';
                errDiv.classList.remove('hidden');
            }
            return;
        }

        const formData = new FormData(form);
        formData.append('action', action);
        if (userId) {
            formData.append('user_id', userId);
        }
        pendingUserFormData = formData;

        // Populate Confirmation Modal
        const modalTitle = document.getElementById('confirmUserModalTitle');
        const btnText = document.getElementById('confirmUserActionBtnText');
        const promptText = document.getElementById('confirmUserModalPrompt');

        if (action === 'create') {
            if (modalTitle) modalTitle.innerHTML = '<i class="fa-solid fa-user-plus text-brand-dark"></i> Confirm User Registration';
            if (btnText) btnText.textContent = 'Confirm & Register';
            if (promptText) promptText.textContent = 'Please review the user details below before registering to the system:';
        } else {
            if (modalTitle) modalTitle.innerHTML = '<i class="fa-solid fa-user-pen text-brand-dark"></i> Confirm User Update';
            if (btnText) btnText.textContent = 'Confirm & Save Changes';
            if (promptText) promptText.textContent = 'Please review the modified user details before saving changes:';
        }

        const sumName = document.getElementById('confirmSummaryName');
        const sumUser = document.getElementById('confirmSummaryUsername');
        const sumDept = document.getElementById('confirmSummaryDept');
        const sumRole = document.getElementById('confirmSummaryRole');
        const sumDesc = document.getElementById('confirmSummaryDesc');
        const sumStat = document.getElementById('confirmSummaryStatus');

        if (sumName) sumName.textContent = fullName;
        if (sumUser) sumUser.textContent = username || 'Auto-generated';
        if (sumDept) sumDept.textContent = dept || '—';
        if (sumRole) sumRole.textContent = role || '—';
        if (sumDesc) sumDesc.textContent = desc || '—';
        if (sumStat) {
            sumStat.textContent = status;
            sumStat.className = `px-2 py-0.5 rounded-full font-bold text-[10px] ${status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'}`;
        }

        openModal('confirmUserActionModal');
    }

    function executeUserFormSubmit() {
        if (!pendingUserFormData) {
            closeModal('confirmUserActionModal');
            return;
        }

        const confirmBtn = document.getElementById('confirmUserActionBtn');
        const origConfirmHtml = confirmBtn.innerHTML;
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Processing...';

        const submitBtn = document.getElementById('userFormSubmit');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1.5"></i> Saving...';
        }

        const formData = pendingUserFormData;
        const action = formData.get('action');
        const userId = formData.get('user_id');

        fetch('user_management_api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = origConfirmHtml;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = action === 'create' ? '<i class="fa-solid fa-save mr-1.5"></i> Register User' : '<i class="fa-solid fa-save mr-1.5"></i> Save Changes';
            }

            closeModal('confirmUserActionModal');

            if (data.success) {
                const u = data.data || {};
                const id = userId || u.id || Date.now();
                const fullName = formData.get('full_name');
                const username = formData.get('username') || u.username || '';
                const email = formData.get('email');
                const dept = formData.get('department');
                const role = formData.get('role');
                const desc = formData.get('role_description');
                const status = formData.get('status') || 'Active';
                const initials = fullName ? fullName.split(' ').map(n=>n[0]).join('').substring(0,2).toUpperCase() : '?';

                const tbody = document.getElementById('usersTableBody');
                const emptyTd = tbody?.querySelector('tr td[colspan]');
                if (emptyTd) emptyTd.closest('tr').remove();

                triggerTableSkeletonRefresh(() => {
                    if (action === 'create') {
                        const tr = document.createElement('tr');
                        tr.className = 'border-b border-slate-100 hover:bg-slate-50 transition user-row';
                        tr.dataset.id = id;
                        tr.dataset.employeeid = username;
                        tr.dataset.role = role;
                        tr.dataset.status = status;
                        tr.dataset.fullname = fullName;
                        tr.dataset.username = username;
                        tr.dataset.email = email;
                        tr.dataset.department = dept;
                        tr.dataset.roledescription = desc;

                        tr.innerHTML = `
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-brand-light flex items-center justify-center text-brand-dark font-bold text-xs">
                                        ${initials}
                                    </div>
                                    <div>
                                        <span class="font-medium text-slate-800">${fullName}</span>
                                        <span class="text-xs text-slate-400 block">${email}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-sm font-semibold font-mono text-xs">${username}</td>
                            <td class="px-4 py-3 text-slate-600 text-sm font-medium">${dept}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 ${getRoleBadgeColor(role)} rounded-full text-xs font-semibold">
                                    ${role}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-600 text-sm font-medium">${desc || '—'}</td>
                            <td class="px-4 py-3">
                                <span class="status-badge px-2 py-1 ${status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'} rounded-full text-xs font-semibold">
                                    ${status}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-500 text-xs">Never</td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1">
                                    <button onclick="editUser(${id})" class="text-brand-dark hover:text-brand-medium text-xs font-medium transition px-2 py-1 hover:bg-brand-light rounded" title="Edit User">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button onclick="managePermissions(${id})" class="text-purple-600 hover:text-purple-800 text-xs font-medium transition px-2 py-1 hover:bg-purple-50 rounded" title="Permissions">
                                        <i class="fa-solid fa-key"></i>
                                    </button>
                                    <button onclick="setUserStatus(${id})" class="text-amber-600 hover:text-amber-800 text-xs font-medium transition px-2 py-1 hover:bg-amber-50 rounded" title="Set Status">
                                        <i class="fa-solid fa-sliders"></i>
                                    </button>
                                    <button onclick="deleteUser(${id})" class="text-red-500 hover:text-red-700 text-xs font-medium transition px-2 py-1 hover:bg-red-50 rounded" title="${(status || '').toLowerCase() === 'active' ? 'Cannot delete active user (Must be set to Inactive first)' : 'Delete User'}">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        `;
                        tbody.insertBefore(tr, tbody.firstChild);
                        showToast(`User '${fullName}' registered successfully!`, 'success', 'Realtime Sync');
                        addActivityLogJS(`Registered user: ${fullName}`);
                    } else {
                        const row = document.querySelector(`.user-row[data-id="${id}"]`);
                        if (row) {
                            row.dataset.fullname = fullName;
                            row.dataset.username = username;
                            row.dataset.email = email;
                            row.dataset.department = dept;
                            row.dataset.role = role;
                            row.dataset.roledescription = desc;
                            row.dataset.status = status;
                            const editDelBtn = row.querySelector('button[onclick^="deleteUser"]');
                            if (editDelBtn) {
                                editDelBtn.title = (status || '').toLowerCase() === 'active' ? 'Cannot delete active user (Must be set to Inactive first)' : 'Delete User';
                            }

                            row.children[0].querySelector('span.font-medium').textContent = fullName;
                            row.children[0].querySelector('span.text-xs').textContent = email;
                            row.children[0].querySelector('div.w-8').textContent = initials;
                            row.children[1].textContent = username;
                            row.children[2].textContent = dept;
                            row.children[3].querySelector('span').textContent = role;
                            row.children[4].textContent = desc || '—';
                            const statusBadge = row.children[5].querySelector('span');
                            statusBadge.textContent = status;
                            statusBadge.className = `status-badge px-2 py-1 ${status === 'Active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'} rounded-full text-xs font-semibold`;
                        }
                        showToast(`User '${fullName}' updated successfully!`, 'success', 'Realtime Sync');
                        addActivityLogJS(`Updated user: ${fullName}`);
                    }

                    closeModal('addUserModal');
                    updateKPISummariesJS();
                    updateRoleCardCountersJS();
                });
            } else {
                const errDiv = document.getElementById('userFormError');
                if (errDiv) {
                    errDiv.textContent = data.message;
                    errDiv.classList.remove('hidden');
                }
                showToast(data.message, 'danger', 'Error');
            }
        })
        .catch(err => {
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = origConfirmHtml;
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = action === 'create' ? '<i class="fa-solid fa-save mr-1.5"></i> Register User' : '<i class="fa-solid fa-save mr-1.5"></i> Save Changes';
            }
            closeModal('confirmUserActionModal');
            showToast('Error connecting to server', 'danger', 'Error');
            console.error(err);
        });
    }

    // ============================================================
    // EDIT USER (POPULATE FORM)
    // ============================================================
    function editUser(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        if (!row) {
            showToast('User data not found', 'danger');
            return;
        }

        document.getElementById('userId').value = userId;
        document.getElementById('fullName').value = row.dataset.fullname || '';
        document.getElementById('username').value = row.dataset.username || '';
        document.getElementById('email').value = row.dataset.email || '';
        document.getElementById('status').value = row.dataset.status || 'Active';
        document.getElementById('password').value = '';

        const dept = row.dataset.department || '';
        const role = row.dataset.role || '';
        const desc = row.dataset.roledescription || '';

        document.getElementById('department').value = dept;
        onDepartmentChange(dept, role);
        if (desc) {
            onRoleChange(role, desc);
        }

        document.getElementById('userModalTitle').innerHTML = '<i class="fa-solid fa-user-pen text-brand-medium"></i> Edit User';
        document.getElementById('userFormSubmit').innerHTML = '<i class="fa-solid fa-save mr-1.5"></i> Update User';

        const err = document.getElementById('userFormError');
        if (err) err.classList.add('hidden');

        openModal('addUserModal');
    }

    // ============================================================
    // MANAGE PERMISSIONS MODAL (LOAD & SAVE IN DEDICATED POPUP)
    // ============================================================
    let currentModalRoleId = null;
    let currentModalUserId = null;
    let currentModalRoleName = '';
    let currentModalUserName = '';

    function managePermissions(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        if (!row) {
            showToast('User record not found', 'danger', 'Error');
            return;
        }

        const fullName = row.dataset.fullname || 'User';
        const roleName = row.dataset.role || 'Unassigned';
        const roleDesc = row.dataset.roledescription || '';
        const empId = row.dataset.employeeid || row.dataset.username || '';
        const targetRole = (roleDesc || roleName).trim();

        // 1. Role Authorization Guard (Non-Admin Restrictions)
        const isCurrentUser = (row.dataset.username && row.dataset.username === CURRENT_USER_NAME) || (fullName.toLowerCase() === CURRENT_USER_NAME.toLowerCase());
        const isTargetDirector = /director|coordinator|lead|system admin|^admin$/i.test(targetRole);

        if (!IS_SYSTEM_ADMIN) {
            if (isCurrentUser) {
                showToast('Access Denied: You cannot modify permissions for your own active account.', 'warning', 'Permission Guard');
                return;
            }
            if (isTargetDirector) {
                showToast('Access Denied: You cannot modify permissions for Department Heads, Leads, or Administrators.', 'warning', 'Permission Guard');
                return;
            }
        }

        const roleTitleEl = document.getElementById('permModalUserRoleTitle') || document.getElementById('permModalTitle');
        if (roleTitleEl) roleTitleEl.textContent = `${fullName} (${empId})`;

        const subEl = document.getElementById('permModalUserRoleSub') || document.getElementById('permModalSub');
        if (subEl) {
            if (!IS_SYSTEM_ADMIN) {
                subEl.textContent = `Role: ${targetRole} • Scope: ${CURRENT_USER_DEPT || 'Department'} & Main Controls`;
            } else {
                subEl.textContent = `Role: ${targetRole} • Employee ID: ${empId}`;
            }
        }

        // Resolve matching role_id from user row data or permissionRoleSelect
        let matchedRoleId = null;
        const rowRoleId = parseInt(row.dataset.roleid || '0', 10);
        if (rowRoleId > 0 && rowRoleId <= 19) {
            matchedRoleId = rowRoleId;
        }

        const roleSelect = document.getElementById('permissionRoleSelect');
        if (!matchedRoleId && roleSelect) {
            const normRole = (r) => {
                const s = (r || '').trim().toLowerCase();
                if (s === 'system admin' || s === 'admin') return 'system administrator';
                if (s === 'immunization lead') return 'immunization coordinator';
                if (s === 'wastewater lead') return 'wastewater officer';
                if (s === 'surveillance lead') return 'surveillance coordinator';
                return s;
            };

            const rNameLower = roleName.trim().toLowerCase();
            const rDescLower = roleDesc.trim().toLowerCase();
            const rNameNorm = normRole(roleName);
            const rDescNorm = normRole(roleDesc);

            for (let opt of roleSelect.options) {
                const optText = opt.text.trim().toLowerCase();
                const optNorm = normRole(opt.text);
                if (optText === rDescLower || optText === rNameLower
                    || optNorm === rDescNorm || optNorm === rNameNorm
                    || (rDescLower && optText.includes(rDescLower))
                    || (rDescNorm && optNorm.includes(rDescNorm))) {
                    matchedRoleId = opt.value;
                    break;
                }
            }
        }

        currentModalRoleId = matchedRoleId;
        currentModalUserId = userId;
        currentModalRoleName = targetRole;
        currentModalUserName = `${fullName}${empId ? ` (${empId})` : ''}`;

        if (matchedRoleId) {
            if (typeof loadRolePermissionsInModal === 'function') {
                loadRolePermissionsInModal(matchedRoleId);
            }
        }
        openModal('manageUserPermissionsModal');
    }

    function submitModalPermissions() {
        if (!currentModalRoleId) {
            showToast('No role matrix ID resolved for permission update', 'danger', 'Error');
            return;
        }

        const selectedIds = [];
        document.querySelectorAll('.modal-perm-checkbox:checked').forEach(cb => {
            selectedIds.push(cb.dataset.id);
        });

        const userEl = document.getElementById('confirmDangerousPermUser');
        const roleEl = document.getElementById('confirmDangerousPermRole');
        const countEl = document.getElementById('confirmDangerousPermCount');

        if (userEl) userEl.textContent = currentModalUserName || 'Selected User';
        if (roleEl) roleEl.textContent = currentModalRoleName || 'Unassigned Role';
        if (countEl) countEl.textContent = `${selectedIds.length} permission${selectedIds.length === 1 ? '' : 's'} selected`;

        // Open dangerous action confirmation modal
        openModal('confirmDangerousPermissionModal');
    }

    function executeModalPermissionsSubmit() {
        if (!currentModalRoleId) {
            showToast('No role matrix ID resolved for permission update', 'danger', 'Error');
            return;
        }

        const continueBtn = document.getElementById('confirmDangerousPermContinueBtn');
        const origContinueHtml = continueBtn ? continueBtn.innerHTML : '<i class="fa-solid fa-arrow-right text-xs"></i> <span>Continue</span>';
        if (continueBtn) {
            continueBtn.disabled = true;
            continueBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i> Saving...';
        }

        const saveBtn = document.getElementById('saveUserPermsModalBtn');
        const origSaveBtnHtml = saveBtn ? saveBtn.innerHTML : '<i class="fa-solid fa-check text-xs"></i> Save Permissions';
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs mr-1"></i> Saving...';
        }

        const selectedIds = [];
        document.querySelectorAll('.modal-perm-checkbox:checked').forEach(cb => {
            selectedIds.push(cb.dataset.id);
        });

        const formData = new FormData();
        formData.append('action', 'update_role_permissions');
        formData.append('role_id', currentModalRoleId);
        selectedIds.forEach(id => formData.append('permissions[]', id));

        fetch('user_management_api.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (continueBtn) {
                continueBtn.disabled = false;
                continueBtn.innerHTML = origContinueHtml;
            }
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = origSaveBtnHtml;
            }

            if (data.success) {
                closeModal('confirmDangerousPermissionModal');
                closeModal('manageUserPermissionsModal');
                showToast(`🔑 Permissions for ${currentModalRoleName || 'role'} saved successfully!`, 'success', 'Permissions Updated');
                closeModal('manageUserPermissionsModal');
                addActivityLogJS(`Updated permissions for role: ${currentModalRoleName || currentModalRoleId}`);
            } else {
                closeModal('confirmDangerousPermissionModal');
                const isWarning = (data.message || '').includes('Access Denied') || (data.message || '').includes('Restriction');
                showToast(data.message || 'Failed to save permissions', isWarning ? 'warning' : 'danger', isWarning ? 'Access Restriction' : 'Permission Error');
            }
        })
        .catch(err => {
            if (continueBtn) {
                continueBtn.disabled = false;
                continueBtn.innerHTML = origContinueHtml;
            }
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = origSaveBtnHtml;
            }
            closeModal('confirmDangerousPermissionModal');
            showToast('Error connecting to permission server', 'danger', 'Connection Error');
            console.error(err);
        });
    }

    // ============================================================
    // DYNAMIC ROLE PERMISSIONS (LOAD & SAVE)
    // ============================================================
    function showToast(message, type = 'info', title = '') {
        if (typeof toast !== 'undefined') {
            if (type === 'danger' || type === 'error') {
                toast.error(message, { title: title || 'Error' });
            } else if (type === 'success') {
                toast.success(message, { title: title || 'Success' });
            } else if (type === 'warning') {
                toast.warning(message, { title: title || 'Warning' });
            } else {
                toast.info(message, { title: title || 'Notification' });
            }
            return;
        }
        if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
            if (type === 'danger' || type === 'error') {
                ModalSystem.toast.error(message, { title: title || 'Error' });
            } else if (type === 'success') {
                ModalSystem.toast.success(message, { title: title || 'Success' });
            } else if (type === 'warning') {
                ModalSystem.toast.warning(message, { title: title || 'Warning' });
            } else {
                ModalSystem.toast.info(message, { title: title || 'Notification' });
            }
        }
    }

    function openModal(id) {
        if (typeof ModalSystem !== 'undefined' && ModalSystem.open) {
            ModalSystem.open(id);
        } else {
            const el = document.getElementById(id);
            if (el) {
                el.classList.remove('hidden');
                el.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        }
    }

    function closeModal(id) {
        if (typeof ModalSystem !== 'undefined' && ModalSystem.close) {
            ModalSystem.close(id);
        } else {
            const el = document.getElementById(id);
            if (el) {
                el.classList.add('hidden');
                el.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
                const anyOpen = document.querySelectorAll('.fixed.inset-0.flex:not(.hidden)');
                if (anyOpen.length === 0) {
                    document.body.classList.remove('overflow-hidden');
                }
            }
        }
    }

    // ============================================================
    // SKELETON LOADER HELPERS & SHIMMER REFRESH TRIGGERS
    // ============================================================
    function triggerTableSkeletonRefresh(callback) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) {
            if (typeof callback === 'function') callback();
            return;
        }
        tbody.classList.add('animate-pulse', 'opacity-50');
        setTimeout(() => {
            if (typeof callback === 'function') callback();
            tbody.classList.remove('animate-pulse', 'opacity-50');
        }, 250);
    }

    function triggerPermissionSkeletonRefresh(callback) {
        const grid = document.getElementById('permissionGrid');
        if (!grid) {
            if (typeof callback === 'function') callback();
            return;
        }
        grid.classList.add('animate-pulse', 'opacity-50');
        setTimeout(() => {
            if (typeof callback === 'function') callback();
            grid.classList.remove('animate-pulse', 'opacity-50');
        }, 250);
    }

    function renderPermissionSkeletonJS() {
        return `
            <div class="space-y-4 animate-pulse">
                <div class="border border-slate-100 rounded-lg p-3 space-y-3 bg-slate-50/50">
                    <div class="h-4 bg-slate-200 rounded w-1/3"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="h-3 bg-slate-200/70 rounded w-4/5"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-5/6"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-2/3"></div>
                    </div>
                </div>
                <div class="border border-slate-100 rounded-lg p-3 space-y-3 bg-slate-50/50">
                    <div class="h-4 bg-slate-200 rounded w-1/4"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="h-3 bg-slate-200/70 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-4/5"></div>
                    </div>
                </div>
                <div class="border border-slate-100 rounded-lg p-3 space-y-3 bg-slate-50/50">
                    <div class="h-4 bg-slate-200 rounded w-2/5"></div>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="h-3 bg-slate-200/70 rounded w-2/3"></div>
                        <div class="h-3 bg-slate-200/70 rounded w-5/6"></div>
                    </div>
                </div>
            </div>
        `;
    }

    function renderTableSkeletonJS() {
        let html = '';
        for (let i = 0; i < 4; i++) {
            html += `
                <tr class="animate-pulse border-b border-slate-100">
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-3/4 mb-1"></div><div class="h-3 bg-slate-100 rounded w-1/2"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-2/3"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/2"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/3"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/2"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/4"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/3"></div></td>
                    <td class="px-4 py-3"><div class="h-4 bg-slate-200 rounded w-1/4"></div></td>
                </tr>
            `;
        }
        return html;
    }

    function loadRolePermissions(roleId) {
        const grid = document.getElementById('permissionGrid');
        if (!grid) return;
        grid.innerHTML = renderPermissionSkeletonJS();

        fetch(`user_management_api.php?action=get_role_permissions&role_id=${roleId}`)
        .then(res => res.json())
        .then(res => {
            if (!res.success || !res.data) {
                if (grid) grid.innerHTML = '<p class="text-xs text-slate-400 text-center py-6">No permissions found for this role.</p>';
                return;
            }

            let html = '';
            for (const [module, perms] of Object.entries(res.data)) {
                html += `
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-4 mb-3">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">${module}</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-2">
                `;
                perms.forEach(p => {
                    const checked = p.granted ? 'checked' : '';
                    const isDisabled = p.is_system && !isSystemAdminUser;
                    const disabledAttr = isDisabled ? 'disabled' : '';
                    const labelClass = isDisabled ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer';
                    const lockBadge = isDisabled ? '<i class="fa-solid fa-lock text-[10px] text-amber-500 ml-1" title="System Management privileges reserved for System Administrator"></i>' : '';
                    const permName = p.name || p.slug;

                    html += `
                        <label class="flex items-center gap-2 text-xs ${labelClass}" title="${isDisabled ? 'Requires System Administrator Privileges' : ''}">
                            <input type="checkbox" value="${p.id}" ${checked} ${disabledAttr} class="rounded border-slate-300 text-brand-dark focus:ring-brand-medium ${isDisabled ? 'bg-slate-100 cursor-not-allowed' : ''}">
                            <span>${permName}</span>
                            ${lockBadge}
                        </label>
                    `;
                });

                html += `
                        </div>
                    </div>
                `;
            }
            if (grid) grid.innerHTML = html || '<p class="text-xs text-slate-400 text-center py-6">No permissions defined.</p>';
        })
        .catch(err => {
            if (grid) grid.innerHTML = '<p class="text-xs text-rose-500 text-center py-6">Failed to load permissions.</p>';
            showToast('Failed to load permissions', 'danger', 'Permission Error');
            console.error(err);
        });
    }

    function savePermissions() {
        const roleSelect = document.getElementById('permissionRoleSelect');
        const roleId = roleSelect ? roleSelect.value : 0;
        const roleName = roleSelect ? roleSelect.options[roleSelect.selectedIndex].text : '';
        const checkboxes = document.querySelectorAll('#permissionGrid input[type="checkbox"]:checked:not(:disabled)');

        const permIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

        const saveBtn = document.querySelector('button[onclick="savePermissions()"]');
        const origBtnText = saveBtn ? saveBtn.innerHTML : '<i class="fa-solid fa-save mr-1"></i> Save Permissions';

        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
        }

        const body = new URLSearchParams();
        body.append('action', 'save_permissions');
        body.append('role_id', roleId);
        body.append('permission_ids', JSON.stringify(permIds));

        fetch('user_management_api.php', {
            method: 'POST',
            body: body
        })
        .then(res => res.json())
        .then(data => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = origBtnText;
            }

            if (data.success) {
                showToast(`Permissions saved for ${roleName || 'role'}!`, 'success', 'Permission Management');

                // Real-time update permission count badge on target role card
                const roleCards = document.querySelectorAll('.role-item-card');
                roleCards.forEach(card => {
                    if (card.dataset.rolename && card.dataset.rolename.toLowerCase() === roleName.toLowerCase()) {
                        const permSpan = card.querySelector('.role-perm-count') || card.querySelector('p span');
                        if (permSpan) {
                            const userCountText = card.querySelector('.role-user-count') ? card.querySelector('.role-user-count').textContent : '';
                            permSpan.parentElement.innerHTML = `<span class="role-user-count">${userCountText}</span> • <span class="role-perm-count">${permIds.length} permissions</span>`;
                        }
                    }
                });

                addActivityLogJS(`Updated permissions for role: ${roleName}`);
            } else {
                showToast(data.message || 'Failed to save permissions.', 'danger', 'Save Failed');
            }
        })
        .catch(err => {
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = origBtnText;
            }
            showToast('Error saving permissions', 'danger', 'Save Error');
            console.error(err);
        });
    }

    // Load permissions for initial role on page load
    document.addEventListener('DOMContentLoaded', () => {
        const roleSelect = document.getElementById('permissionRoleSelect');
        const grid = document.getElementById('permissionGrid');
        if (grid && roleSelect && roleSelect.value) {
            loadRolePermissions(roleSelect.value);
        }
    });

    // ============================================================
    // SET USER STATUS via API (3-STATE PICKER + REALTIME DOM UPDATE)
    // ============================================================
    function setUserStatus(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        const userName = row ? (row.dataset.fullname || row.dataset.username || `ID ${userId}`) : `ID ${userId}`;
        document.getElementById('setStatusUserId').value = userId;
        document.getElementById('setStatusUserName').textContent = `User: ${userName}`;
        openModal('setStatusModal');
    }

    function applyStatus(newStatus) {
        const userId = document.getElementById('setStatusUserId').value;
        if (!userId) return;

        const body = new URLSearchParams();
        body.append('action', 'set_status');
        body.append('user_id', userId);
        body.append('new_status', newStatus);

        fetch('user_management_api.php', {
            method: 'POST',
            body: body
        })
        .then(res => res.json())
        .then(data => {
            closeModal('setStatusModal');
            if (data.success) {
                const row = document.querySelector(`.user-row[data-id="${userId}"]`);
                if (row) {
                    row.dataset.status = newStatus;
                    const badge = row.children[5]?.querySelector('span');
                    if (badge) {
                        badge.textContent = newStatus;
                        badge.className = `status-badge px-2 py-1 ${
                            newStatus === 'Active' ? 'bg-emerald-100 text-emerald-700' :
                            newStatus === 'Suspended' ? 'bg-red-100 text-red-700' :
                            'bg-slate-100 text-slate-700'
                        } rounded-full text-xs font-semibold`;
                    }
                    const statusDelBtn = row.querySelector('button[onclick^="deleteUser"]');
                    if (statusDelBtn) {
                        statusDelBtn.title = (newStatus || '').toLowerCase() === 'active' ? 'Cannot delete active user (Must be set to Inactive first)' : 'Delete User';
                    }
                }
            } else {
                showToast(data.message || 'Failed to set status', 'danger');
            }
        })
        .catch(err => {
            showToast('Error updating status', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // DELETE USER via API (REALTIME DOM UPDATE)
    // ============================================================
    function deleteUser(userId) {
        const row = document.querySelector(`.user-row[data-id="${userId}"]`);
        const userName = row ? (row.dataset.fullname || `ID ${userId}`) : `ID ${userId}`;
        const userStatus = row ? (row.dataset.status || 'Active').trim() : 'Active';

        if (userStatus.toLowerCase() === 'active') {
            const warningMsg = `You cannot delete active user '${userName}'. Active users cannot be deleted. Please set this user's status to Inactive before deleting.`;
            showToast(warningMsg, 'warning', 'Cannot Delete Active User');
            if (typeof ModalSystem !== 'undefined' && ModalSystem.confirm) {
                ModalSystem.confirm(
                    `Cannot delete user '${userName}' because the account is currently Active.\n\nActive users cannot be deleted. You must deactivate this user first before deletion.\n\nWould you like to open status settings to set this user to Inactive?`,
                    () => {
                        setUserStatus(userId);
                    },
                    { title: 'Active User Warning', confirmText: 'Change Status to Inactive', cancelText: 'Cancel', type: 'warning' }
                );
            } else {
                alert(warningMsg);
            }
            return;
        }

        if (userStatus.toLowerCase() !== 'inactive') {
            const warningMsg = `User '${userName}' must be Inactive before deletion. Current status: ${userStatus}.`;
            showToast(warningMsg, 'warning', 'Cannot Delete User');
            if (typeof ModalSystem !== 'undefined' && ModalSystem.confirm) {
                ModalSystem.confirm(
                    `Cannot delete user '${userName}' because the status is currently '${userStatus}'.\n\nUsers must be set to Inactive before they can be deleted.\n\nWould you like to open status settings now?`,
                    () => {
                        setUserStatus(userId);
                    },
                    { title: 'Status Warning', confirmText: 'Change Status', cancelText: 'Cancel', type: 'warning' }
                );
            } else {
                alert(warningMsg);
            }
            return;
        }

        const performDelete = () => {
            const body = new URLSearchParams();
            body.append('action', 'delete');
            body.append('user_id', userId);

            fetch('user_management_api.php', {
                method: 'POST',
                body: body
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (row) {
                        row.style.transition = 'all 0.3s ease';
                        row.style.opacity = '0';
                        row.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            row.remove();
                            updateKPISummariesJS();
                            updateRoleCardCountersJS();
                        }, 300);
                    } else {
                        updateKPISummariesJS();
                        updateRoleCardCountersJS();
                    }
                    addActivityLogJS(`Deleted user: ${userName}`);
                    showToast(data.message, 'success', 'User Deleted (Realtime)');
                } else {
                    showToast(data.message, 'danger', 'Delete Failed');
                }
            })
            .catch(err => {
                showToast('Error deleting user', 'danger', 'Error');
                console.error(err);
            });
        };

        if (typeof ModalSystem !== 'undefined' && ModalSystem.confirm) {
            ModalSystem.confirm(
                `Are you sure you want to delete user '${userName}'? This action cannot be undone.`,
                performDelete,
                { title: 'Delete User Confirmation', confirmText: 'Delete User', type: 'danger' }
            );
        } else if (confirm(`Are you sure you want to delete user '${userName}'? This action cannot be undone.`)) {
            performDelete();
        }
    }

    // ============================================================
    // EDIT ROLE
    // ============================================================
    function editRole(roleId) {
        const roleSelect = document.getElementById('permissionRoleSelect');
        if (roleSelect) {
            roleSelect.value = roleId;
            loadRolePermissions(roleId);
            showToast('✏️ Loaded permissions for role ID: ' + roleId, 'info');
            const grid = document.getElementById('permissionGrid');
            if (grid) grid.scrollIntoView({ behavior: 'smooth' });
        }
    }

    // ============================================================
    // CLEAR LOGS CONFIRMATION MODAL
    // ============================================================
    function openClearLogsModal() {
        const modal = document.getElementById('clearLogsModal');
        const card = document.getElementById('clearLogsModalCard');
        if (!modal || !card) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        setTimeout(() => {
            card.classList.remove('scale-95', 'opacity-0');
            card.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeClearLogsModal() {
        const modal = document.getElementById('clearLogsModal');
        const card = document.getElementById('clearLogsModalCard');
        if (!modal || !card) return;
        card.classList.remove('scale-100', 'opacity-100');
        card.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }, 200);
    }

    function clearLogs() {
        openClearLogsModal();
    }

    function executeClearLogs() {
        closeClearLogsModal();

        const body = new URLSearchParams();
        body.append('action', 'clear_logs');

        fetch('user_management_api.php', {
            method: 'POST',
            body: body
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const tbody = document.getElementById('logTableBody');
                if (tbody) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fa-solid fa-inbox text-lg text-slate-300"></i>
                                    <span>No log entries available.</span>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                showToast('🧹 ' + data.message, 'success');
            } else {
                showToast(data.message || 'Failed to clear logs', 'danger');
            }
        })
        .catch(err => {
            showToast('Error clearing logs', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // REFRESH DATA
    // ============================================================
    function refreshData() {
        showToast('🔄 Refreshing data...', 'info');
        const tbody = document.getElementById('usersTableBody');
        if (tbody) {
            tbody.querySelectorAll('tr').forEach(row => {
                row.classList.add('opacity-80');
            });
        }
        setTimeout(() => {
            showToast('✅ Data refreshed successfully!', 'success');
            window.location.reload();
        }, 400);
    }

    // ============================================================
    // ESC KEY TO CLOSE MODALS
    // ============================================================
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });
