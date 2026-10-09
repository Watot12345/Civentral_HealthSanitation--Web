<?php
    // ============================================================
    // 1. PHP BACKEND - Fetch ALL Data for Client-side Pagination
    // ============================================================
    require_once '../../includes/header.php';
    require_once '../../includes/sidebar.php';
requireDepartmentAccess('health center services');

    require_once __DIR__ . '/../../app/Models/Prescription.php';
    require_once __DIR__ . '/../../app/Models/Patient.php';
    require_once __DIR__ . '/../../app/Models/Employee.php';

    $title = 'Prescriptions';

    // Fetch ALL prescriptions (no limit)
    $prescriptionModel = new Prescription();
    $patientModel = new Patient();
    $employeeModel = new Employee();

    // Get all prescriptions
    $rawPrescriptions = [];
    try {
        $rawPrescriptions = $prescriptionModel->all(['order' => 'created_at.desc']);
    } catch (Throwable $e) {
        error_log('Error fetching prescriptions: ' . $e->getMessage());
    }

    // Get patients and employees for enrichment
    $patients = [];
    $patientsJsMap = [];
    try {
        $patientsRaw = $patientModel->all();
        foreach ($patientsRaw as $p) {
            $patients[$p['id']] = $p;
            $age = 0;
            if (!empty($p['birth_date'])) {
                try {
                    $dob = new DateTime($p['birth_date']);
                    $now = new DateTime();
                    $age = $now->diff($dob)->y;
                } catch (Throwable $ex) {}
            }
            $conditions = 'None';
            if (!empty($p['medical_history'])) {
                $history = is_string($p['medical_history']) 
                    ? json_decode($p['medical_history'], true) 
                    : $p['medical_history'];
                $conditions = $history['conditions'] ?? 'None';
            }
            $patientsJsMap[$p['id']] = [
                'id' => (int)($p['id'] ?? 0),
                'patient_id' => $p['patient_id'] ?? "P-{$p['id']}",
                'first_name' => $p['first_name'] ?? '',
                'last_name' => $p['last_name'] ?? '',
                'gender' => $p['gender'] ?? 'Unspecified',
                'age' => $age,
                'blood_type' => $p['blood_type'] ?? 'N/A',
                'contact' => $p['contact'] ?? 'N/A',
                'email' => $p['email'] ?? 'N/A',
                'address' => $p['address'] ?? 'N/A',
                'barangay' => $p['barangay'] ?? 'N/A',
                'emergency_contact' => $p['emergency_contact'] ?? 'N/A',
                'registration_date' => $p['registration_date'] ?? 'N/A',
                'status' => $p['status'] ?? 'active',
                'allergies' => $p['allergies'] ?? 'None',
                'conditions' => $conditions
            ];
        }
    } catch (Throwable $e) {
        error_log('Error fetching patients: ' . $e->getMessage());
    }

    $employees = [];
    try {
        $employeesRaw = $employeeModel->all();
        foreach ($employeesRaw as $e) {
            $employees[$e['id']] = $e;
        }
    } catch (Throwable $e) {
        error_log('Error fetching employees: ' . $e->getMessage());
    }

    // Resolve logged in doctor / employee ID & Name based on role / session
    $sessionUserId = $_SESSION['user_id'] ?? null;
    $sessionEmployeeId = $_SESSION['employee_id'] ?? null;
    $sessionFullName = trim($_SESSION['full_name'] ?? ($_SESSION['name'] ?? ($_SESSION['username'] ?? '')));

    $loggedInDoctorId = null;
    $loggedInDoctorName = null;

    foreach ($employees as $e) {
        $eId = (string)($e['id'] ?? '');
        $uId = (string)($e['user_id'] ?? '');
        $eName = trim($e['full_name'] ?? (($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')));
        $eUser = trim($e['username'] ?? '');

        if (
            ($sessionEmployeeId && (string)$sessionEmployeeId === $eId) ||
            ($sessionUserId && (string)$sessionUserId === $uId) ||
            (!empty($sessionFullName) && (stripos($eName, $sessionFullName) !== false || stripos($sessionFullName, $eName) !== false || stripos($sessionFullName, $eUser) !== false))
        ) {
            $loggedInDoctorId = (int)$e['id'];
            $loggedInDoctorName = $e['full_name'] ?? $eName;
            break;
        }
    }

    // Enrich prescriptions
    $allPrescriptions = [];
    foreach ($rawPrescriptions as $p) {
        // Get patient name
        $patient = $patients[$p['patient_id']] ?? null;
        if ($patient) {
            $p['patient_name'] = trim(($patient['first_name'] ?? '') . ' ' . ($patient['last_name'] ?? ''));
            $p['patient_avatar'] = strtoupper(substr($patient['first_name'] ?? '', 0, 1) . substr($patient['last_name'] ?? '', 0, 1));
        } else {
            $p['patient_name'] = 'Unknown';
            $p['patient_avatar'] = '??';
        }
        
        // Get doctor name
    // Get doctor name
    $employee = $employees[$p['employee_id']] ?? null;
    if ($employee) {
        // Use full_name instead of first_name + last_name
        $p['doctor_name'] = $employee['full_name'] ?? 'Unknown';
    } else {
        $p['doctor_name'] = 'Unknown';
    }
        
        // Decode medications
        if (isset($p['medications']) && is_string($p['medications'])) {
            $p['medications'] = json_decode($p['medications'], true) ?: [];
        }
        
        // Format date
        if (isset($p['date'])) {
            $p['date_formatted'] = date('M d, Y', strtotime($p['date']));
        }
        
        $allPrescriptions[] = $p;
    }

    // Stats
    $totalDispensed = count(array_filter($allPrescriptions, fn($p) => ($p['status'] ?? '') === 'dispensed'));
    $totalPending = count(array_filter($allPrescriptions, fn($p) => ($p['status'] ?? '') === 'pending'));
    $totalMedications = array_sum(array_map(fn($p) => count($p['medications'] ?? []), $allPrescriptions));

    // Get current user ID & check permissions for Inventory additions (Doctor, Director, Dentist, Admin)
    $currentUserId = $_SESSION['user_id'] ?? 1;
    $userRoleStr = strtolower(trim($_SESSION['role_description'] ?? $_SESSION['role_name'] ?? $_SESSION['role'] ?? ''));
    $canAddMedicine = (
        empty($_SESSION['role']) ||
        str_contains($userRoleStr, 'doctor') ||
        str_contains($userRoleStr, 'director') ||
        str_contains($userRoleStr, 'dentist') ||
        str_contains($userRoleStr, 'admin') ||
        str_contains($userRoleStr, 'physician')
    );
    ?>

    <!-- ============================================================ -->
    <!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
    <!-- ============================================================ -->

    <div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight" id="pageTitle">Prescriptions</h2>
                <p class="text-sm text-slate-500 mt-0.5" id="pageSubtitle">Electronic prescriptions with drug selection & dosage management</p>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap sm:flex-nowrap">
                <!-- Inventory View Button (Positioned to the left of New Prescription) -->
                <button type="button" id="btnInventoryToggle" onclick="toggleMainView('inventory')"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors text-sm font-semibold flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-boxes-stacked text-xs text-brand-medium"></i> Inventory
                </button>

                <!-- Back to Prescriptions View Button -->
                <button type="button" id="btnPrescriptionsToggle" onclick="toggleMainView('prescriptions')"
                        class="hidden px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition-colors text-sm font-semibold flex items-center gap-2 shadow-xs">
                    <i class="fa-solid fa-prescription text-xs text-brand-medium"></i> Prescriptions
                </button>

                <!-- New Prescription Button -->
                <button id="btnNewPrescription" onclick="ModalSystem.open('newPrescriptionModal')"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-prescription-bottle text-xs"></i> New Prescription
                </button>

                <!-- Add Medicine Button (Restricted to Doctor, Director, Dentist) -->
                <?php if ($canAddMedicine): ?>
                <button id="btnAddMedicine" onclick="ModalSystem.open('addMedicineModal')"
                        class="hidden px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-plus text-xs"></i> Add Medicine
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Dynamically updates for Rx & Inventory   -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Prescriptions / Total Medicines -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div id="kpiCard1Bg" class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div id="kpiIcon1" class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200 shrink-0">
                        <i class="fa-solid fa-prescription text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900" id="totalPrescriptions">-</p>
                        <p class="text-xs font-medium text-slate-500" id="kpiLabel1">Total Prescriptions</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span id="kpiBadge1" class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">💊 All prescriptions</span>
                    <span class="text-[10px] text-slate-400" id="totalDispensed">- dispensed</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Dispensed / In Stock -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div id="kpiCard2Bg" class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div id="kpiIcon2" class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200 shrink-0">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600" id="dispensedCount">-</p>
                        <p class="text-xs font-medium text-slate-500" id="kpiLabel2">Dispensed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span id="kpiBadge2" class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Filled</span>
                    <span class="text-[10px] text-slate-400" id="kpiSubtext2">Successfully dispensed</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Pending / Low Stock -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div id="kpiCard3Bg" class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div id="kpiIcon3" class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200 shrink-0">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600" id="pendingCount">-</p>
                        <p class="text-xs font-medium text-slate-500" id="kpiLabel3">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span id="kpiBadge3" class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400" id="kpiSubtext3">Ready for dispensing</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Medications / Expiring -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div id="kpiCard4Bg" class="absolute -top-12 -right-12 w-24 h-24 bg-violet-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div id="kpiIcon4" class="w-11 h-11 bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-violet-200 shrink-0">
                        <i class="fa-solid fa-capsules text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-violet-600" id="totalMedications">-</p>
                        <p class="text-xs font-medium text-slate-500" id="kpiLabel4">Total Medications</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span id="kpiBadge4" class="px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-[10px] font-bold">🧪 Items</span>
                    <span class="text-[10px] text-slate-400" id="kpiSubtext4">Across all prescriptions</span>
                </div>
            </div>
        </div>
    </div>

        <!-- Loading Status Bar -->
        <div id="loadingInfo" class="hidden mb-4 px-4 py-2 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-700 font-medium">
            <i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading prescriptions...
        </div>

        <!-- Search & Filter -->
        <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="text"
                        id="searchPrescription"
                        placeholder="Search by patient name, ID, or medication..."
                        class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
                </div>
                
                <!-- Prescriptions Filters -->
                <div id="prescriptionFilterGroup" class="flex gap-2 flex-wrap">
                    <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                        <option value="">All Status</option>
                        <option value="dispensed">Dispensed</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <label class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span>From</span>
                        <input type="date" id="filterDateFrom" class="px-2.5 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                    </label>
                    <label class="flex items-center gap-1.5 text-xs text-slate-500">
                        <span>To</span>
                        <input type="date" id="filterDateTo" class="px-2.5 py-2 border border-slate-200 rounded-lg text-sm bg-white">
                    </label>
                    <button onclick="resetFilters()" title="Reset filters"
                            class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>

                <!-- Inventory Filters (Types of Medicine & Grams / Dosage) -->
                <div id="inventoryFilterGroup" class="hidden flex gap-2 flex-wrap">
                    <!-- Dropdown Selection: Types of Medicine -->
                    <select id="filterMedicineType" onchange="filterInventory()" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                        <option value="">All Medicine Types</option>
                    </select>

                    <!-- Dropdown Selection: Grams / Dosage -->
                    <select id="filterMedicineGrams" onchange="filterInventory()" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                        <option value="">All Grams / Strengths</option>
                    </select>

                    <!-- Stock & Expiration Status Filter -->
                    <select id="filterMedicineStock" onchange="filterInventory()" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                        <option value="">All Stock & Expiration</option>
                        <option value="in_stock">In Stock (> 50)</option>
                        <option value="low_stock">Low Stock (≤ 50)</option>
                        <option value="out_of_stock">Out of Stock (0)</option>
                        <option value="expiring_soon">Expiring Soon (≤ 60 days)</option>
                        <option value="expired">Expired</option>
                    </select>

                    <button onclick="resetInventoryFilters()" title="Reset inventory filters"
                            class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                        <i class="fa-solid fa-rotate-right"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Prescriptions Table -->
        <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200" id="tableHeaderRow">
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">RX ID</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Medications</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="prescriptionTableBody">
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-spinner fa-spin text-2xl mb-2"></i>
                                <p class="text-sm">Loading prescriptions...</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Empty state -->
            <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-prescription text-slate-400"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">No prescriptions match your filters</p>
                <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
                <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
            </div>

            <!-- Pagination -->
            <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
                <p class="text-xs text-slate-500">
                    Showing <span class="font-semibold text-slate-700" id="showingStart">0</span> to
                    <span class="font-semibold text-slate-700" id="showingEnd">0</span> of
                    <span class="font-semibold text-slate-700" id="showingTotal">0</span> <span id="showingItemType">prescriptions</span>
                </p>
                <div class="flex gap-1" id="paginationControls">
                    <!-- Pagination buttons will be generated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- PATIENT PROFILE MODAL IN PRESCRIPTIONS                        -->
    <!-- ============================================================ -->
    <div id="prescriptionPatientProfileModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-address-card text-brand-medium"></i> Patient Profile Overview
                </h3>
                <button onclick="ModalSystem.close('prescriptionPatientProfileModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="prescriptionPatientProfileContent" class="p-6">
                <div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading profile...</div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- NEW PRESCRIPTION MODAL                                       -->
    <!-- ============================================================ -->
    <div id="newPrescriptionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-prescription-bottle text-brand-medium"></i>
                    New Electronic Prescription
                </h3>
                <button onclick="ModalSystem.close('newPrescriptionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="newPrescriptionForm" class="p-6 space-y-4" onsubmit="savePrescription(event)">
                <!-- Patient & Doctor -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label>
                        <select id="rx_patient" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="">Select Patient</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Doctor</label>
                        <select id="rx_doctor" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="">Select Doctor</option>
                        </select>
                    </div>
                </div>
                
                <!-- Date -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Prescription Date</label>
                    <input type="date" id="rx_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                            <!-- Drug Selection with Search -->
    <div class="relative">
        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1.5">Add Medication</label>
        <div class="flex gap-2">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" 
                    id="rx_drug_search" 
                    placeholder="Search medication by name or category..."
                    class="w-full pl-8 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"
                    oninput="searchDrugs(this.value)"
                    onfocus="showDrugDropdown()"
                    onblur="hideDrugDropdown()"
                    autocomplete="off">
            </div>
            <button type="button" onclick="addSelectedDrug()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold whitespace-nowrap">
                <i class="fa-solid fa-plus mr-1"></i> Add
            </button>
        </div>
        <!-- Dropdown results -->
        <div id="drugDropdown" class="hidden absolute z-20 mt-1 left-0 right-0 bg-white border border-slate-200 rounded-lg shadow-lg max-h-52 overflow-y-auto">
            <div id="drugDropdownList" class="p-1">
                <!-- Dynamic results will be populated here -->
            </div>
        </div>
        <!-- Selected drug display -->
        <div id="selectedDrugDisplay" class="hidden mt-2">
            <div class="flex items-center gap-2 p-2 bg-brand-light/40 rounded-lg border border-brand-border">
                <i class="fa-solid fa-capsules text-brand-medium text-xs"></i>
                <span class="text-xs font-medium text-slate-700" id="selectedDrugName">-</span>
                <span class="text-xs text-slate-400" id="selectedDrugStrength">-</span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs text-slate-400" id="selectedDrugCategory">-</span>
                <button onclick="clearSelectedDrug()" class="ml-auto text-slate-400 hover:text-rose-500 text-xs">
                    <i class="fa-solid fa-times"></i>
                </button>
            </div>
        </div>
    </div>

                <!-- Medication List -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Medications</label>
                    <div id="rxMedicationList" class="space-y-2 max-h-48 overflow-y-auto">
                        <!-- Medication items will be added here -->
                        <div class="text-center py-4 text-slate-400 text-sm" id="rxEmptyMedication">
                            <i class="fa-solid fa-capsules text-2xl block mb-2"></i>
                            No medications added yet
                        </div>
                    </div>
                </div>

                <!-- Dosage Management - Per Medication (hidden until medication is added) -->
                <div id="dosageSection" class="hidden bg-slate-50 rounded-xl p-4 border border-slate-200">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wide mb-3">Dosage Management</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Dosage</label>
                            <input type="text" id="rx_dosage" placeholder="e.g. 5mg" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Frequency</label>
                            <select id="rx_frequency" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <option value="Once daily">Once daily</option>
                                <option value="Twice daily">Twice daily</option>
                                <option value="Three times daily">Three times daily</option>
                                <option value="Four times daily">Four times daily</option>
                                <option value="Every 4 hours">Every 4 hours</option>
                                <option value="Every 6 hours">Every 6 hours</option>
                                <option value="Every 8 hours">Every 8 hours</option>
                                <option value="As needed">As needed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Duration</label>
                            <select id="rx_duration" class="w-full px-3 py-1.5 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                                <option value="3 days">3 days</option>
                                <option value="5 days">5 days</option>
                                <option value="7 days">7 days</option>
                                <option value="10 days">10 days</option>
                                <option value="14 days">14 days</option>
                                <option value="30 days" selected>30 days</option>
                                <option value="60 days">60 days</option>
                                <option value="90 days">90 days</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Notes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes / Instructions</label>
                    <textarea id="rx_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Additional instructions for the patient..."></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="ModalSystem.close('newPrescriptionModal')"
                            class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-prescription mr-1.5"></i> Create Prescription
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- VIEW PRESCRIPTION MODAL                                      -->
    <!-- ============================================================ -->
    <div id="viewPrescriptionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
                <h3 class="font-bold text-slate-900">Prescription Details</h3>
                <button onclick="ModalSystem.close('viewPrescriptionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div id="prescriptionDetailsContent" class="p-6">
                <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                    <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- EDIT PRESCRIPTION MODAL                                      -->
    <!-- ============================================================ -->
    <div id="editPrescriptionModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
                <h3 class="font-bold text-slate-900">Edit Prescription</h3>
                <button onclick="ModalSystem.close('editPrescriptionModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="editPrescriptionForm" class="p-6 space-y-4" onsubmit="saveEditedPrescription(event)">
                <input type="hidden" id="edit_rx_id">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Patient</label>
                        <input type="text" id="edit_rx_patient" readonly class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-600 outline-none cursor-not-allowed">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Doctor</label>
                        <select id="edit_rx_doctor" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="">Select Doctor</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date</label>
                    <input type="date" id="edit_rx_date" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Status</label>
                    <select id="edit_rx_status" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <option value="pending">Pending</option>
                        <option value="dispensed">Dispensed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Notes</label>
                    <textarea id="edit_rx_notes" rows="2" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="ModalSystem.close('editPrescriptionModal')"
                            class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                        <i class="fa-solid fa-check mr-1.5"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ADD MEDICINE MODAL (Doctor, Director, Dentist)              -->
    <!-- ============================================================ -->
    <div id="addMedicineModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl z-10">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-pills text-brand-medium"></i> Add New Medicine to Inventory
                </h3>
                <button onclick="ModalSystem.close('addMedicineModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="addMedicineForm" class="p-6 space-y-4" onsubmit="saveNewMedicine(event)">
                <!-- Medicine Name -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Medicine Name <span class="text-rose-500">*</span></label>
                    <input type="text" id="med_name" required placeholder="e.g. Amoxicillin, Paracetamol, Cefalexin"
                           class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                </div>

                <!-- Type / Category & Grams / Dosage -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Type / Category <span class="text-rose-500">*</span></label>
                        <input type="text" id="med_category" required list="medCategoryList" placeholder="e.g. Antibiotic, Analgesic, NSAID"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <datalist id="medCategoryList">
                            <option value="Analgesic">
                            <option value="Antibiotic">
                            <option value="Antihypertensive">
                            <option value="Antidiabetic">
                            <option value="Antihistamine">
                            <option value="Vitamin/Supplement">
                            <option value="NSAID">
                            <option value="Corticosteroid">
                            <option value="Bronchodilator">
                            <option value="Antiemetic">
                            <option value="Rehydration">
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Dosage / Grams / Weight <span class="text-rose-500">*</span></label>
                        <input type="text" id="med_strength" required list="medStrengthList" placeholder="e.g. 500mg, 250mg, 1g, 5mg"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <datalist id="medStrengthList">
                            <option value="100mg">
                            <option value="250mg">
                            <option value="500mg">
                            <option value="1000mg (1g)">
                            <option value="5mg">
                            <option value="10mg">
                            <option value="20mg">
                            <option value="50mg">
                            <option value="15mg/kg">
                            <option value="100mcg">
                        </datalist>
                    </div>
                </div>

                <!-- Dosage Form & Initial Stock -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Form</label>
                        <select id="med_form" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <option value="Tablet">Tablet</option>
                            <option value="Capsule">Capsule</option>
                            <option value="Syrup">Syrup</option>
                            <option value="Injection">Injection</option>
                            <option value="Inhaler">Inhaler</option>
                            <option value="Cream">Cream</option>
                            <option value="Powder">Powder</option>
                            <option value="Drops">Drops</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Stock Quantity <span class="text-rose-500">*</span></label>
                        <input type="number" id="med_stock" required min="0" value="100"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>

                <!-- Expiration Date & Batch/Lot Number -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Expiration Date <span class="text-rose-500">*</span></label>
                        <input type="date" id="med_expiration_date" required
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Batch / Lot #</label>
                        <input type="text" id="med_batch_number" placeholder="e.g. LOT-2026-088"
                               class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    </div>
                </div>

                <!-- Description / Notes -->
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Description / Notes</label>
                    <textarea id="med_description" rows="2" placeholder="Indications, precautions, or storage requirements..."
                              class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none"></textarea>
                </div>

                <!-- Footer Buttons -->
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" onclick="ModalSystem.close('addMedicineModal')"
                            class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button type="submit" id="btnSubmitAddMedicine"
                            class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-1.5">
                        <i class="fa-solid fa-plus"></i> Add to Inventory
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- DELETE MEDICINE CONFIRMATION MODAL                            -->
    <!-- ============================================================ -->
    <div id="deleteMedicineModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md overflow-hidden">
            <!-- Modal Header -->
            <div class="px-6 pt-6 pb-4 text-center">
                <div class="w-14 h-14 rounded-full bg-rose-100 border border-rose-200 text-rose-600 flex items-center justify-center text-2xl mx-auto mb-3 shadow-xs">
                    <i class="fa-solid fa-trash-can"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Delete Medicine from Inventory?</h3>
                <p class="text-xs text-slate-500 mt-1">This action cannot be undone. The selected medicine item will be permanently removed from the health center inventory.</p>
            </div>

            <!-- Medicine Target Summary Box -->
            <div class="mx-6 p-3.5 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-medium">Medicine:</span>
                    <span class="font-bold text-slate-800" id="delete_med_name">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-medium">Type / Category:</span>
                    <span class="font-semibold text-slate-700" id="delete_med_category">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-medium">Dosage / Grams:</span>
                    <span class="font-semibold text-brand-dark" id="delete_med_strength">-</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-slate-400 font-medium">Batch / Lot #:</span>
                    <span class="font-mono text-slate-600" id="delete_med_batch">-</span>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="p-6 flex gap-3">
                <button type="button" onclick="ModalSystem.close('deleteMedicineModal')"
                        class="flex-1 py-2.5 bg-white border border-slate-200 text-slate-600 rounded-xl hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="button" id="btnConfirmDeleteMedicine" onclick="confirmDeleteMedicine()"
                        class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-xl transition text-sm font-semibold flex items-center justify-center gap-2 shadow-sm">
                    <i class="fa-solid fa-trash-can text-xs"></i> Delete Medicine
                </button>
            </div>
        </div>
    </div>

<style>#emptyState.hidden {
    display: none !important;
}

#prescriptionTableBody:empty {
    display: none;
}</style>
    <!-- ============================================================ -->
    <!-- JAVASCRIPT - Client-side pagination (NO PAGE RELOAD!)       -->
    <!-- ============================================================ -->
    <script>
        const API_URL = '<?php echo site_url('api/prescriptions.php'); ?>';
        const CURRENT_USER_ID = <?php echo $currentUserId; ?>;
        
        // Data loaded from PHP - ALL DATA available client-side
        let allPrescriptions = <?php echo json_encode($allPrescriptions); ?>;
        let filteredPrescriptions = [...allPrescriptions];
        let prescriptionMedications = [];
        let patients = <?php echo json_encode(array_values($patients)); ?>;
        let doctors = <?php echo json_encode(array_values($employees)); ?>;
        let drugs = [];

        let currentPage = 1;
        const ITEMS_PER_PAGE = 5;

        // Inventory view state
        let activeMainView = 'prescriptions'; // 'prescriptions' | 'inventory'
        let inventoryItems = [];
        let filteredInventory = [];
        let inventoryCurrentPage = 1;
        const CAN_ADD_MEDICINE = <?php echo $canAddMedicine ? 'true' : 'false'; ?>;

        // ============================================================
        // DRUG SELECTION - Searchable with Categories
        // ============================================================
        let selectedDrug = null;
        let filteredDrugs = [];

        // ============================================================
        // INITIALIZATION
        // ============================================================
       document.addEventListener('DOMContentLoaded', function() {
    // Hide empty state initially
    const emptyState = document.getElementById('emptyState');
    if (emptyState) {
        emptyState.classList.add('hidden');
        emptyState.style.display = 'none';
    }

    // Re-enrich the PHP-embedded data with local patients/doctors lookups
    // This guarantees correct names even before any API call
    allPrescriptions = allPrescriptions.map(p => enrichLocally(p));
    filteredPrescriptions = [...allPrescriptions];
    
    updateStats();
    renderTable();
    populatePatientSelects();
    populateDoctorSelects();
    loadDrugs();
    
    const dateInput = document.getElementById('rx_date');
    if (dateInput) {
        dateInput.value = new Date().toISOString().split('T')[0];
    }

    // Centralized Workflow: Auto-open & prefill New Prescription modal if redirected from Consultation/Appointment
    const urlParams = new URLSearchParams(window.location.search);
    const targetPatientId = urlParams.get('patient_id') || urlParams.get('patient');
    const targetDoctorId = urlParams.get('doctor_id') || urlParams.get('employee_id');
    const autoOpenNew = urlParams.get('from_consultation') !== null || urlParams.get('action') === 'new' || urlParams.get('new') === 'true';

    if (autoOpenNew || targetPatientId) {
        setTimeout(() => {
            ModalSystem.open('newPrescriptionModal');
            
            const patientSelect = document.getElementById('rx_patient');
            if (patientSelect && targetPatientId) {
                patientSelect.value = targetPatientId;
            }

            const doctorSelect = document.getElementById('rx_doctor');
            if (doctorSelect && targetDoctorId) {
                doctorSelect.value = targetDoctorId;
            }

            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.info('New prescription form pre-filled for patient.', { title: '💊 Electronic Prescription' });
            }
        }, 350);
    }
});

        // ============================================================
        // LOAD DRUGS FROM API
        // ============================================================
        const PATIENTS_MAP = <?php echo json_encode($patientsJsMap, JSON_UNESCAPED_UNICODE); ?>;

        // ============================================================
        // PATIENT PROFILE MODAL IN PRESCRIPTIONS
        // ============================================================
        function openPatientProfile(patientId) {
            ModalSystem.open('prescriptionPatientProfileModal');
            const content = document.getElementById('prescriptionPatientProfileContent');
            content.innerHTML = '<div class="flex items-center justify-center py-10 text-slate-400 text-sm"><i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading profile...</div>';
            
            setTimeout(() => {
                const p = PATIENTS_MAP[patientId];
                if (!p) {
                    content.innerHTML = '<p class="text-sm text-rose-500 text-center py-10">Patient profile details not found.</p>';
                    return;
                }
                const fName = p.first_name || '';
                const lName = p.last_name || '';
                const initials = (((fName[0] || 'P')) + ((lName[0] || 'T'))).toUpperCase();
                const fullName = `${fName} ${lName}`.trim() || ('Patient #' + p.id);
                const statusBadge = p.status === 'active' 
                    ? '<span class="inline-block px-2.5 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-xs font-semibold mt-1">Active</span>' 
                    : '<span class="inline-block px-2.5 py-0.5 bg-slate-100 text-slate-500 rounded-full text-xs font-semibold mt-1">Inactive</span>';
                
                content.innerHTML = `
                    <div class="space-y-6">
                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between pb-4 border-b border-slate-200 gap-3">
                            <div class="flex items-center gap-4">
                                <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xl flex-shrink-0">${initials}</div>
                                <div>
                                    <h4 class="text-lg font-bold text-slate-900 maskable" data-real="${fullName}" data-masked="${maskName(fullName)}">${maskName(fullName)}</h4>
                                    <p class="text-xs text-slate-500 font-mono">${p.patient_id} &bull; ${p.gender} &bull; ${p.age} yrs old</p>
                                    ${statusBadge}
                                </div>
                            </div>
                            <a href="patients.php?patient=${p.id}&autoView=true" class="px-3.5 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5 shrink-0 shadow-xs">
                                <i class="fa-solid fa-arrow-up-right-from-square text-[10px]"></i> View in Patients
                            </a>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Contact</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.contact}" data-masked="${maskName(p.contact)}">${maskName(p.contact)}</p></div>
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Email</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.email}" data-masked="${maskName(p.email)}">${maskName(p.email)}</p></div>
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Blood Type</p><p class="text-slate-800 font-bold text-rose-600 mt-0.5">${p.blood_type}</p></div>
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Barangay</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.barangay}" data-masked="${maskName(p.barangay)}">${maskName(p.barangay)}</p></div>
                            <div class="md:col-span-2"><p class="text-slate-400 font-semibold uppercase text-[10px]">Address</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.address}" data-masked="${maskName(p.address)}">${maskName(p.address)}</p></div>
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Emergency Contact</p><p class="text-slate-800 font-medium mt-0.5 maskable" data-real="${p.emergency_contact}" data-masked="${maskName(p.emergency_contact)}">${maskName(p.emergency_contact)}</p></div>
                            <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Registration Date</p><p class="text-slate-800 font-medium mt-0.5">${p.registration_date}</p></div>
                        </div>
                        <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border text-xs">
                            <h5 class="font-bold text-slate-700 mb-2 flex items-center gap-1.5"><i class="fa-solid fa-notes-medical text-brand-medium"></i> Medical Information</h5>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Allergies</p><p class="text-slate-800 font-medium mt-0.5">${p.allergies}</p></div>
                                <div><p class="text-slate-400 font-semibold uppercase text-[10px]">Existing Conditions</p><p class="text-slate-800 font-medium mt-0.5">${p.conditions}</p></div>
                            </div>
                        </div>
                        <div class="flex justify-between items-center pt-3 border-t border-slate-100">
                            <button type="button" onclick="ModalSystem.close('prescriptionPatientProfileModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-xs font-semibold">Close</button>
                            <a href="patients.php?patient=${p.id}&autoView=true" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
                                <i class="fa-solid fa-users text-xs"></i> Open Full Profile in Patients Page
                            </a>
                        </div>
                    </div>
                `;
                if (typeof ModalSystem !== 'undefined' && ModalSystem.refreshMasking) {
                    ModalSystem.refreshMasking('prescriptionPatientProfileModal');
                }
            }, 150);
        }

        async function loadDrugs() {
            try {
                const response = await fetch('<?php echo site_url('api/drugs.php'); ?>');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data && data.data.length > 0) {
                        drugs = data.data;
                        console.log(`Loaded ${drugs.length} drugs from API`);
                        populateDrugSelect();
                        return;
                    }
                }
                // Fallback to sample drugs
                useSampleDrugs();
            } catch (e) {
                console.warn('Failed to load drugs from API, using sample data:', e);
                useSampleDrugs();
            }
        }

        function useSampleDrugs() {
            // Fallback sample data (most common health center drugs)
            drugs = [
                {id: 1, name: 'Paracetamol', category: 'Analgesic', strength: '500mg', form: 'Tablet'},
                {id: 2, name: 'Amoxicillin', category: 'Antibiotic', strength: '500mg', form: 'Capsule'},
                {id: 3, name: 'Metformin', category: 'Antidiabetic', strength: '500mg', form: 'Tablet'},
                {id: 4, name: 'Amlodipine', category: 'Antihypertensive', strength: '5mg', form: 'Tablet'},
                {id: 5, name: 'Losartan', category: 'Antihypertensive', strength: '50mg', form: 'Tablet'},
                {id: 6, name: 'Salbutamol', category: 'Bronchodilator', strength: '100mcg', form: 'Inhaler'},
                {id: 7, name: 'Ibuprofen', category: 'NSAID', strength: '400mg', form: 'Tablet'},
                {id: 8, name: 'Cetirizine', category: 'Antihistamine', strength: '10mg', form: 'Tablet'},
                {id: 9, name: 'Omeprazole', category: 'PPI', strength: '20mg', form: 'Capsule'},
                {id: 10, name: 'Multivitamins', category: 'Supplement', strength: 'Once daily', form: 'Tablet'},
                {id: 11, name: 'Folic Acid', category: 'Supplement', strength: '1mg', form: 'Tablet'},
                {id: 12, name: 'Ferrous Sulfate', category: 'Supplement', strength: '325mg', form: 'Tablet'},
            ];
            populateDrugSelect();
        }

        // ============================================================
        // SEARCH DRUGS FUNCTION
        // ============================================================
        function searchDrugs(query) {
            const dropdown = document.getElementById('drugDropdown');
            const list = document.getElementById('drugDropdownList');
            
            if (!dropdown || !list) return;
            
            // If query is empty or too short, show all drugs
            if (!query || query.length < 1) {
                filteredDrugs = drugs;
            } else {
                const lowerQuery = query.toLowerCase();
                filteredDrugs = drugs.filter(d => 
                    d.name.toLowerCase().includes(lowerQuery) ||
                    d.category.toLowerCase().includes(lowerQuery) ||
                    (d.strength && d.strength.toLowerCase().includes(lowerQuery))
                );
            }
            
            if (filteredDrugs.length === 0) {
                list.innerHTML = `<div class="px-3 py-2 text-xs text-slate-400 text-center">No medications found</div>`;
                dropdown.classList.remove('hidden');
                return;
            }
            
            // Show limited results for better UX (max 15)
            const displayDrugs = filteredDrugs.slice(0, 15);
            
            list.innerHTML = displayDrugs.map(d => `
                <div onclick="selectDrug(${d.id})" 
                    class="px-3 py-2 hover:bg-brand-light/40 rounded-lg cursor-pointer transition flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-slate-700">${d.name}</span>
                        <span class="text-xs text-slate-400 ml-2">${d.strength || ''}</span>
                        <span class="text-xs text-slate-400 ml-2">${d.form || ''}</span>
                    </div>
                    <span class="text-[10px] px-2 py-0.5 bg-slate-100 rounded-full text-slate-500">${d.category}</span>
                </div>
            `).join('');
            
            // Show count if there are more results
            if (filteredDrugs.length > 15) {
                list.innerHTML += `<div class="px-3 py-1 text-[10px] text-slate-400 text-center border-t border-slate-100">+ ${filteredDrugs.length - 15} more results</div>`;
            }
            
            dropdown.classList.remove('hidden');
        }

        function selectDrug(id) {
            const drug = drugs.find(d => d.id === id);
            if (!drug) return;
            
            selectedDrug = drug;
            
            // Update display
            document.getElementById('selectedDrugName').textContent = drug.name;
            document.getElementById('selectedDrugStrength').textContent = drug.strength || '';
            document.getElementById('selectedDrugCategory').textContent = drug.category;
            document.getElementById('selectedDrugDisplay').classList.remove('hidden');
            
            // Update search input
            document.getElementById('rx_drug_search').value = drug.name;
            
            // Hide dropdown
            document.getElementById('drugDropdown').classList.add('hidden');
        }

        function addSelectedDrug() {
            const searchInput = document.getElementById('rx_drug_search');
            const customName = searchInput ? searchInput.value.trim() : '';

            if (!selectedDrug && !customName) {
                if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                    ModalSystem.toast.warning('Please search and select a medication first');
                }
                return;
            }
            
            const drugName = selectedDrug ? selectedDrug.name : customName;
            const drugStrength = selectedDrug ? (selectedDrug.strength || '') : '';
            const drugId = selectedDrug ? selectedDrug.id : null;
            
            // Check if already added
            if (prescriptionMedications.some(m => m.name.toLowerCase() === drugName.toLowerCase())) {
                if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                    ModalSystem.toast.warning('Medication already added');
                }
                return;
            }
            
            const freqEl = document.getElementById('rx_frequency');
            const durEl = document.getElementById('rx_duration');
            const frequency = freqEl ? freqEl.value : '1 tablet daily';
            const duration = durEl ? durEl.value : '7 days';

            // Add to prescription
            const medication = {
                id: drugId,
                name: drugName,
                dosage: drugStrength,
                frequency: frequency,
                duration: duration,
                quantity: calculateQuantity(frequency, duration)
            };
            
            prescriptionMedications.push(medication);
            renderMedicationList();
            clearSelectedDrug();
            if (typeof ModalSystem !== 'undefined' && ModalSystem.toast) {
                ModalSystem.toast.success(drugName + ' added to prescription');
            }
        }

        function clearSelectedDrug() {
            selectedDrug = null;
            const searchInput = document.getElementById('rx_drug_search');
            if (searchInput) searchInput.value = '';
            const display = document.getElementById('selectedDrugDisplay');
            if (display) display.classList.add('hidden');
            const dropdown = document.getElementById('drugDropdown');
            if (dropdown) dropdown.classList.add('hidden');
        }

        function showDrugDropdown() {
            const searchValue = document.getElementById('rx_drug_search').value;
            if (searchValue.length >= 0 || drugs.length > 0) {
                searchDrugs(searchValue);
            }
        }

        function hideDrugDropdown() {
            setTimeout(() => {
                document.getElementById('drugDropdown').classList.add('hidden');
            }, 200);
        }

        // ============================================================
        // POPULATE DRUG SELECT (Fallback dropdown)
        // ============================================================
        function populateDrugSelect() {
            const select = document.getElementById('rx_drug_select');
            if (!select) return;
            
            // Group drugs by category
            const grouped = {};
            drugs.forEach(d => {
                const cat = d.category || 'Other';
                if (!grouped[cat]) grouped[cat] = [];
                grouped[cat].push(d);
            });
            
            select.innerHTML = '<option value="">Search or select drug...</option>';
            
            // Add categorized options
            Object.keys(grouped).sort().forEach(category => {
                const optgroup = document.createElement('optgroup');
                optgroup.label = category;
                
                grouped[category].forEach(d => {
                    const option = document.createElement('option');
                    option.value = d.id;
                    const strength = d.strength ? ' ' + d.strength : '';
                    const form = d.form ? ' (' + d.form + ')' : '';
                    option.textContent = d.name + strength + form;
                    optgroup.appendChild(option);
                });
                
                select.appendChild(optgroup);
            });
        }

        // ============================================================
        // POPULATE PATIENT & DOCTOR SELECTS
        // ============================================================
        function populatePatientSelects() {
            const select = document.getElementById('rx_patient');
            if (!select) return;
            select.innerHTML = '<option value="">Select Patient</option>';
            patients.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = `${p.first_name} ${p.last_name} (${p.patient_id || p.id})`;
                select.appendChild(option);
            });
        }

        const LOGGED_IN_DOCTOR_ID = <?php echo json_encode($loggedInDoctorId); ?>;

        function populateDoctorSelects() {
            const select = document.getElementById('rx_doctor');
            const editSelect = document.getElementById('edit_rx_doctor');
            
            if (select) {
                select.innerHTML = '<option value="">Select Doctor</option>';
                doctors.forEach(d => {
                    const option = document.createElement('option');
                    option.value = d.id;
                    option.textContent = d.full_name || `${d.first_name || ''} ${d.last_name || ''}`.trim() || `Employee #${d.id}`;
                    if (LOGGED_IN_DOCTOR_ID && parseInt(d.id) === parseInt(LOGGED_IN_DOCTOR_ID)) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
                if (LOGGED_IN_DOCTOR_ID && !select.value) {
                    select.value = LOGGED_IN_DOCTOR_ID;
                }
            }

            if (editSelect) {
                editSelect.innerHTML = '<option value="">Select Doctor</option>';
                doctors.forEach(d => {
                    const option = document.createElement('option');
                    const name = d.full_name || `${d.first_name || ''} ${d.last_name || ''}`.trim() || `Employee #${d.id}`;
                    option.value = name;
                    option.textContent = name;
                    editSelect.appendChild(option);
                });
            }
        }
        // ============================================================
        // MEDICATION MANAGEMENT
        // ============================================================
        function addMedicationToPrescription() {
            const select = document.getElementById('rx_drug_select');
            const drugId = select.value;
            if (!drugId) {
                ModalSystem.toast.warning('Please select a medication');
                return;
            }

            const drug = drugs.find(d => d.id == drugId);
            if (!drug) return;

            const dosage = document.getElementById('rx_dosage').value.trim() || drug.strength;
            const frequency = document.getElementById('rx_frequency').value;
            const duration = document.getElementById('rx_duration').value;

            if (prescriptionMedications.some(m => m.name === drug.name && m.dosage === dosage)) {
                ModalSystem.toast.warning('Medication already added');
                return;
            }

            const medication = {
                id: drug.id,
                name: drug.name,
                dosage: dosage,
                frequency: frequency,
                duration: duration,
                quantity: calculateQuantity(frequency, duration)
            };

            prescriptionMedications.push(medication);
            renderMedicationList();
            
            document.getElementById('rx_dosage').value = '';
            select.value = '';
            
            ModalSystem.toast.success(drug.name + ' added to prescription');
        }

        function calculateQuantity(frequency, duration) {
            const freqMap = {
                'Once daily': 1,
                'Twice daily': 2,
                'Three times daily': 3,
                'Four times daily': 4,
                'Every 4 hours': 6,
                'Every 6 hours': 4,
                'Every 8 hours': 3,
                'As needed': 1
            };
            const days = parseInt(duration) || 30;
            return (freqMap[frequency] || 1) * days;
        }

        function renderMedicationList() {
            const container = document.getElementById('rxMedicationList');
            
            if (prescriptionMedications.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-4 text-slate-400 text-sm" id="rxEmptyMedication">
                        <i class="fa-solid fa-capsules text-2xl block mb-2"></i>
                        No medications added yet
                    </div>
                `;
                return;
            }

            container.innerHTML = prescriptionMedications.map((med, index) => `
                <div class="flex items-center justify-between p-3 bg-brand-light/40 rounded-lg border border-brand-border">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <span class="font-semibold text-slate-800 text-sm">${med.name}</span>
                            <span class="text-xs text-slate-500">${med.dosage}</span>
                            <span class="text-xs text-slate-400">•</span>
                            <span class="text-xs text-slate-500">${med.frequency}</span>
                            <span class="text-xs text-slate-400">•</span>
                            <span class="text-xs text-slate-500">${med.duration}</span>
                            <span class="text-xs text-brand-dark font-semibold">Qty: ${med.quantity}</span>
                        </div>
                    </div>
                    <button onclick="removeMedication(${index})" class="text-rose-500 hover:text-rose-700 transition text-sm px-2">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            `).join('');
        }

        function removeMedication(index) {
            prescriptionMedications.splice(index, 1);
            renderMedicationList();
            ModalSystem.toast.info('Medication removed');
        }

        // ============================================================
        // TABLE RENDERING (Client-side pagination - NO PAGE RELOAD!)
        // ============================================================
        function renderTable() {
    const tbody = document.getElementById('prescriptionTableBody');
    const emptyState = document.getElementById('emptyState');
    const tableElement = tbody.closest('table');
    const paginationDiv = document.querySelector('.px-4.py-3.border-t'); // Pagination section
    
    const totalItems = filteredPrescriptions.length;
    const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE) || 1;
    
    if (currentPage < 1) currentPage = 1;
    if (currentPage > totalPages) currentPage = totalPages;
    
    const startIndex = (currentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = Math.min(startIndex + ITEMS_PER_PAGE, totalItems);
    const pageData = filteredPrescriptions.slice(startIndex, endIndex);
    
    // Hide both states first
    if (emptyState) emptyState.classList.add('hidden');
    if (tableElement) tableElement.classList.remove('hidden');
    if (paginationDiv) paginationDiv.classList.remove('hidden');
    
    if (totalItems === 0) {
        // Show empty state, hide table
        if (emptyState) {
            emptyState.classList.remove('hidden');
            emptyState.style.display = 'flex';
        }
        if (tableElement) tableElement.classList.add('hidden');
        if (paginationDiv) paginationDiv.classList.add('hidden');
        
        // Clear tbody
        tbody.innerHTML = '';
        
        // Update empty state message based on whether filters are active
        const searchValue = document.getElementById('searchPrescription')?.value || '';
        const statusValue = document.getElementById('filterStatus')?.value || '';
        const dateFromValue = document.getElementById('filterDateFrom')?.value || '';
        const dateToValue = document.getElementById('filterDateTo')?.value || '';
        
        if (searchValue || statusValue || dateFromValue || dateToValue) {
            emptyState.innerHTML = `
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-filter-circle-xmark text-slate-400"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">No prescriptions match your filters</p>
                <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
                <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
            `;
        } else {
            emptyState.innerHTML = `
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                    <i class="fa-solid fa-prescription text-slate-400"></i>
                </div>
                <p class="text-sm font-semibold text-slate-600">No prescriptions found</p>
                <p class="text-xs text-slate-400 mt-1">Click "New Prescription" to create one</p>
            `;
        }
        
    } else {
        // Show table, hide empty state
        if (emptyState) {
            emptyState.classList.add('hidden');
            emptyState.style.display = 'none';
        }
        if (tableElement) tableElement.classList.remove('hidden');
        if (paginationDiv) paginationDiv.classList.remove('hidden');
        
        // Render table rows
        tbody.innerHTML = pageData.map(p => {
            let medications = Array.isArray(p.medications) ? p.medications : [];
            const rxId = p.prescription_id || 'N/A';
            const patientName = p.patient_name || 'Unknown';
            const patientAvatar = p.patient_avatar || '??';
            const doctorName = p.doctor_name || 'Unknown';
            
            return `
            <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors">
                <td class="px-4 py-3 font-mono text-xs text-brand-dark font-semibold">
                    <span class="maskable" data-real="${rxId}" data-masked="${maskId(rxId)}">${rxId}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs flex-shrink-0">
                            <span class="maskable" data-real="${patientAvatar}" data-masked="??">${patientAvatar}</span>
                        </div>
                        <div>
                            <button type="button" onclick="openPatientProfile(${p.patient_id})" class="text-left group block">
                                <span class="font-semibold text-slate-800 text-sm group-hover:text-brand-medium transition maskable" data-real="${patientName}" data-masked="${maskName(patientName)}">${patientName}</span>
                            </button>
                            <p class="text-xs text-slate-400">${medications.length} medication${medications.length !== 1 ? 's' : ''}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">
                    <span>${doctorName}</span>
                </td>
                <td class="px-4 py-3">
                    <div class="space-y-0.5">
                        ${medications.slice(0, 2).map(med => `
                            <span class="inline-block px-2 py-0.5 bg-slate-100 rounded text-[10px] text-slate-700">
                                ${med.name || 'Unknown'} ${med.dosage || ''}
                            </span>
                        `).join('')}
                        ${medications.length > 2 ? `<span class="text-[10px] text-slate-400">+${medications.length - 2} more</span>` : ''}
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">${formatDate(p.date)}</td>
                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded-full text-xs font-semibold ${getStatusClasses(p.status)}">
                        ${(p.status || 'pending').charAt(0).toUpperCase() + (p.status || 'pending').slice(1)}
                    </span>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center justify-center gap-1">
                        <button onclick="openPatientProfile(${p.patient_id})" title="View Patient Profile" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg transition"><i class="fa-solid fa-address-card text-sm"></i></button>
                        <button onclick="viewPrescription(${p.id})" title="View" class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition"><i class="fa-solid fa-eye text-sm"></i></button>
                        ${p.status === 'pending' ? `<button onclick="dispensePrescription(${p.id})" title="Dispense" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition"><i class="fa-solid fa-check text-sm"></i></button>` : ''}
                        <button onclick="editPrescription(${p.id})" title="Edit" class="p-1.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 rounded-lg transition"><i class="fa-solid fa-pen text-sm"></i></button>
                        <button onclick="deletePrescription(${p.id})" title="Cancel" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition"><i class="fa-solid fa-trash-can text-sm"></i></button>
                    </div>
                </td>
            </tr>
        `}).join('');

        // Re-apply data masking to newly rendered rows
        if (typeof window.applyMaskingToNewContent === 'function') {
            window.applyMaskingToNewContent();
        } else if (typeof ModalSystem !== 'undefined') {
            // Trigger masking on the tbody
            setTimeout(() => {
                const tableContainer = document.querySelector('.bg-white.rounded-xl.shadow-xs.border');
                if (tableContainer) ModalSystem.applyMaskingToModal(tableContainer);
            }, 50);
        }
    }

    // Update pagination info
    if (totalItems === 0) {
        document.getElementById('showingStart').textContent = 0;
        document.getElementById('showingEnd').textContent = 0;
        document.getElementById('showingTotal').textContent = 0;
    } else {
        document.getElementById('showingStart').textContent = startIndex + 1;
        document.getElementById('showingEnd').textContent = endIndex;
        document.getElementById('showingTotal').textContent = totalItems;
    }
    const itemTypeEl = document.getElementById('showingItemType');
    if (itemTypeEl) itemTypeEl.textContent = 'prescriptions';

    renderPagination(totalItems, totalPages);
}

        // ============================================================
        // CHANGE PAGE - NO PAGE RELOAD!
        // ============================================================
        function changePage(page) {
            const totalItems = filteredPrescriptions.length;
            const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE) || 1;
            
            if (page < 1 || page > totalPages) return;
            currentPage = page;
            renderTable();
        }

        // ============================================================
        // RENDER PAGINATION - Generates page buttons
        // ============================================================
        function renderPagination(totalItems, totalPages) {
            const container = document.getElementById('paginationControls');
            if (!container) return;

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            const btnBase = 'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors';
            const btnActive = `${btnBase} bg-brand-dark text-white shadow-sm`;
            const btnInactive = `${btnBase} bg-white border border-slate-200 text-slate-600 hover:bg-slate-50`;
            const btnDisabled = `${btnBase} bg-white border border-slate-200 text-slate-300 cursor-not-allowed`;

            let html = '';

            // Prev button
            html += `<button onclick="changePage(${currentPage - 1})"
                        class="${currentPage === 1 ? btnDisabled : btnInactive}"
                        ${currentPage === 1 ? 'disabled' : ''}>
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>`;

            // Page number buttons — show at most 5 pages around current
            const delta = 2;
            const rangeStart = Math.max(1, currentPage - delta);
            const rangeEnd   = Math.min(totalPages, currentPage + delta);

            if (rangeStart > 1) {
                html += `<button onclick="changePage(1)" class="${btnInactive}">1</button>`;
                if (rangeStart > 2) {
                    html += `<span class="px-1 py-1.5 text-xs text-slate-400">…</span>`;
                }
            }

            for (let p = rangeStart; p <= rangeEnd; p++) {
                html += `<button onclick="changePage(${p})"
                            class="${p === currentPage ? btnActive : btnInactive}">
                            ${p}
                         </button>`;
            }

            if (rangeEnd < totalPages) {
                if (rangeEnd < totalPages - 1) {
                    html += `<span class="px-1 py-1.5 text-xs text-slate-400">…</span>`;
                }
                html += `<button onclick="changePage(${totalPages})" class="${btnInactive}">${totalPages}</button>`;
            }

            // Next button
            html += `<button onclick="changePage(${currentPage + 1})"
                        class="${currentPage === totalPages ? btnDisabled : btnInactive}"
                        ${currentPage === totalPages ? 'disabled' : ''}>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>`;

            container.innerHTML = html;
        }

        // ============================================================
        // STATS UPDATE (Prescriptions Mode)
        // ============================================================
        function updateStats() {
            const total = allPrescriptions.length;
            const dispensed = allPrescriptions.filter(p => p.status === 'dispensed').length;
            const pending = allPrescriptions.filter(p => p.status === 'pending').length;
            const totalMeds = allPrescriptions.reduce((sum, p) => sum + (p.medications || []).length, 0);

            // Card 1: Total Prescriptions
            const totalPrescriptionsEl = document.getElementById('totalPrescriptions');
            const kpiLabel1El = document.getElementById('kpiLabel1');
            const kpiBadge1El = document.getElementById('kpiBadge1');
            const totalDispensedEl = document.getElementById('totalDispensed');
            const kpiIcon1El = document.getElementById('kpiIcon1');

            if (totalPrescriptionsEl) totalPrescriptionsEl.textContent = total;
            if (kpiLabel1El) kpiLabel1El.textContent = 'Total Prescriptions';
            if (kpiBadge1El) {
                kpiBadge1El.textContent = '💊 All prescriptions';
                kpiBadge1El.className = 'px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold';
            }
            if (totalDispensedEl) totalDispensedEl.textContent = `${dispensed} dispensed`;
            if (kpiIcon1El) kpiIcon1El.innerHTML = '<i class="fa-solid fa-prescription text-lg"></i>';

            // Card 2: Dispensed
            const dispensedCountEl = document.getElementById('dispensedCount');
            const kpiLabel2El = document.getElementById('kpiLabel2');
            const kpiBadge2El = document.getElementById('kpiBadge2');
            const kpiSubtext2El = document.getElementById('kpiSubtext2');
            const kpiIcon2El = document.getElementById('kpiIcon2');

            if (dispensedCountEl) dispensedCountEl.textContent = dispensed;
            if (kpiLabel2El) kpiLabel2El.textContent = 'Dispensed';
            if (kpiBadge2El) {
                kpiBadge2El.textContent = '✅ Filled';
                kpiBadge2El.className = 'px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold';
            }
            if (kpiSubtext2El) kpiSubtext2El.textContent = 'Successfully dispensed';
            if (kpiIcon2El) kpiIcon2El.innerHTML = '<i class="fa-solid fa-check-circle text-lg"></i>';

            // Card 3: Pending
            const pendingCountEl = document.getElementById('pendingCount');
            const kpiLabel3El = document.getElementById('kpiLabel3');
            const kpiBadge3El = document.getElementById('kpiBadge3');
            const kpiSubtext3El = document.getElementById('kpiSubtext3');
            const kpiIcon3El = document.getElementById('kpiIcon3');

            if (pendingCountEl) pendingCountEl.textContent = pending;
            if (kpiLabel3El) kpiLabel3El.textContent = 'Pending';
            if (kpiBadge3El) {
                kpiBadge3El.textContent = '⏳ Awaiting';
                kpiBadge3El.className = 'px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold';
            }
            if (kpiSubtext3El) kpiSubtext3El.textContent = 'Ready for dispensing';
            if (kpiIcon3El) kpiIcon3El.innerHTML = '<i class="fa-solid fa-clock text-lg"></i>';

            // Card 4: Total Medications
            const totalMedicationsEl = document.getElementById('totalMedications');
            const kpiLabel4El = document.getElementById('kpiLabel4');
            const kpiBadge4El = document.getElementById('kpiBadge4');
            const kpiSubtext4El = document.getElementById('kpiSubtext4');
            const kpiIcon4El = document.getElementById('kpiIcon4');

            if (totalMedicationsEl) totalMedicationsEl.textContent = totalMeds;
            if (kpiLabel4El) kpiLabel4El.textContent = 'Total Medications';
            if (kpiBadge4El) {
                kpiBadge4El.textContent = '🧪 Items';
                kpiBadge4El.className = 'px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-[10px] font-bold';
            }
            if (kpiSubtext4El) kpiSubtext4El.textContent = 'Across all prescriptions';
            if (kpiIcon4El) kpiIcon4El.innerHTML = '<i class="fa-solid fa-capsules text-lg"></i>';
        }

        // ============================================================
        // SEARCH & FILTER (Client-side - NO PAGE RELOAD!)
        // ============================================================
        // ============================================================
        // SEARCH & FILTER (Client-side - NO PAGE RELOAD!)
        // ============================================================
        document.getElementById('searchPrescription').addEventListener('input', function() {
            if (activeMainView === 'inventory') {
                filterInventory();
            } else {
                filterPrescriptions();
            }
        });
        document.getElementById('filterStatus').addEventListener('change', filterPrescriptions);
        document.getElementById('filterDateFrom').addEventListener('change', filterPrescriptions);
        document.getElementById('filterDateTo').addEventListener('change', filterPrescriptions);

        function filterPrescriptions() {
            const search = document.getElementById('searchPrescription').value.toLowerCase().trim();
            const status = document.getElementById('filterStatus').value;
            const dateFrom = document.getElementById('filterDateFrom').value;
            const dateTo = document.getElementById('filterDateTo').value;

            if (dateFrom && dateTo && dateFrom > dateTo) {
                ModalSystem.toast.error('The start date cannot be after the end date');
                return;
            }
            
            filteredPrescriptions = allPrescriptions.filter(p => {
                const matchesSearch = !search || 
                    (p.patient_name || '').toLowerCase().includes(search) ||
                    (p.prescription_id || '').toLowerCase().includes(search) ||
                    String(p.id || '').includes(search) ||
                    (p.doctor_name || '').toLowerCase().includes(search) ||
                    (p.medications || []).some(m => (m.name || '').toLowerCase().includes(search));
                
                const matchesStatus = !status || (p.status || '') === status;
                const prescriptionDate = String(p.date || '').slice(0, 10);
                const matchesDateFrom = !dateFrom || prescriptionDate >= dateFrom;
                const matchesDateTo = !dateTo || prescriptionDate <= dateTo;
                
                return matchesSearch && matchesStatus && matchesDateFrom && matchesDateTo;
            });
            
            currentPage = 1;
            renderTable();
        }

        function resetFilters() {
            document.getElementById('searchPrescription').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterDateFrom').value = '';
            document.getElementById('filterDateTo').value = '';
            filteredPrescriptions = [...allPrescriptions];
            currentPage = 1;
            renderTable();
        }

        // ============================================================
        // INVENTORY MANAGEMENT & VIEW TOGGLE
        // ============================================================
        function toggleMainView(view) {
            activeMainView = view;
            
            const pageTitle = document.getElementById('pageTitle');
            const pageSubtitle = document.getElementById('pageSubtitle');
            const btnInventoryToggle = document.getElementById('btnInventoryToggle');
            const btnPrescriptionsToggle = document.getElementById('btnPrescriptionsToggle');
            const btnNewPrescription = document.getElementById('btnNewPrescription');
            const btnAddMedicine = document.getElementById('btnAddMedicine');
            
            const searchInput = document.getElementById('searchPrescription');
            const rxFilters = document.getElementById('prescriptionFilterGroup');
            const invFilters = document.getElementById('inventoryFilterGroup');
            
            if (view === 'inventory') {
                if (pageTitle) pageTitle.textContent = 'Medicine Inventory';
                if (pageSubtitle) pageSubtitle.textContent = 'Health center formulary, stock levels, dosage, and expiration tracking';
                
                if (btnInventoryToggle) btnInventoryToggle.classList.add('hidden');
                if (btnPrescriptionsToggle) btnPrescriptionsToggle.classList.remove('hidden');
                if (btnNewPrescription) btnNewPrescription.classList.add('hidden');
                if (btnAddMedicine) btnAddMedicine.classList.remove('hidden');
                
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.placeholder = 'Search medicine by name, type, grams, lot #...';
                }
                if (rxFilters) rxFilters.classList.add('hidden');
                if (invFilters) invFilters.classList.remove('hidden');
                
                if (inventoryItems.length === 0) {
                    fetchInventoryData();
                } else {
                    populateInventoryFilters();
                    filterInventory();
                    updateInventoryStats();
                }
            } else {
                if (pageTitle) pageTitle.textContent = 'Prescriptions';
                if (pageSubtitle) pageSubtitle.textContent = 'Electronic prescriptions with drug selection & dosage management';
                
                if (btnInventoryToggle) btnInventoryToggle.classList.remove('hidden');
                if (btnPrescriptionsToggle) btnPrescriptionsToggle.classList.add('hidden');
                if (btnNewPrescription) btnNewPrescription.classList.remove('hidden');
                if (btnAddMedicine) btnAddMedicine.classList.add('hidden');
                
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.placeholder = 'Search by patient name, ID, or medication...';
                }
                if (rxFilters) rxFilters.classList.remove('hidden');
                if (invFilters) invFilters.classList.add('hidden');
                
                // Reset table header to Prescriptions columns
                const headerRow = document.getElementById('tableHeaderRow');
                if (headerRow) {
                    headerRow.innerHTML = `
                        <tr>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">RX ID</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Patient</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Doctor</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Medications</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date</th>
                            <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    `;
                }
                
                updateStats();
                renderTable();
            }
        }

        async function fetchInventoryData() {
            try {
                const response = await fetch('<?php echo site_url('api/drugs.php'); ?>');
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        inventoryItems = data.data;
                        drugs = data.data;
                        populateDrugSelect();
                        populateInventoryFilters();
                        filterInventory();
                        updateInventoryStats();
                    }
                }
            } catch (e) {
                console.error('Failed to load inventory data:', e);
            }
        }

        function populateInventoryFilters() {
            const typeSelect = document.getElementById('filterMedicineType');
            const gramsSelect = document.getElementById('filterMedicineGrams');
            
            if (!typeSelect || !gramsSelect) return;
            
            const selectedType = typeSelect.value;
            const selectedGrams = gramsSelect.value;
            
            const types = Array.from(new Set(inventoryItems.map(i => i.category || 'General').filter(Boolean))).sort();
            const gramsList = Array.from(new Set(inventoryItems.map(i => i.strength || 'Standard').filter(Boolean))).sort();
            
            typeSelect.innerHTML = '<option value="">All Medicine Types (' + types.length + ')</option>';
            types.forEach(t => {
                const opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;
                if (t === selectedType) opt.selected = true;
                typeSelect.appendChild(opt);
            });
            
            gramsSelect.innerHTML = '<option value="">All Grams / Strengths (' + gramsList.length + ')</option>';
            gramsList.forEach(g => {
                const opt = document.createElement('option');
                opt.value = g;
                opt.textContent = g;
                if (g === selectedGrams) opt.selected = true;
                gramsSelect.appendChild(opt);
            });
        }

        function filterInventory() {
            const search = (document.getElementById('searchPrescription')?.value || '').toLowerCase().trim();
            const typeFilter = document.getElementById('filterMedicineType')?.value || '';
            const gramsFilter = document.getElementById('filterMedicineGrams')?.value || '';
            const stockFilter = document.getElementById('filterMedicineStock')?.value || '';
            
            const today = new Date().toISOString().split('T')[0];
            const in60Days = new Date(Date.now() + 60 * 86400000).toISOString().split('T')[0];
            
            filteredInventory = inventoryItems.filter(item => {
                const name = (item.name || '').toLowerCase();
                const cat = (item.category || '').toLowerCase();
                const str = (item.strength || '').toLowerCase();
                const batch = (item.batch_number || '').toLowerCase();
                const desc = (item.description || '').toLowerCase();
                
                const matchesSearch = !search || name.includes(search) || cat.includes(search) || str.includes(search) || batch.includes(search) || desc.includes(search);
                const matchesType = !typeFilter || item.category === typeFilter;
                const matchesGrams = !gramsFilter || item.strength === gramsFilter;
                
                let matchesStock = true;
                if (stockFilter === 'in_stock') {
                    matchesStock = (parseInt(item.stock) || 0) > 50;
                } else if (stockFilter === 'low_stock') {
                    const st = parseInt(item.stock) || 0;
                    matchesStock = st > 0 && st <= 50;
                } else if (stockFilter === 'out_of_stock') {
                    matchesStock = (parseInt(item.stock) || 0) <= 0;
                } else if (stockFilter === 'expiring_soon') {
                    const exp = item.expiration_date || '';
                    matchesStock = exp && exp >= today && exp <= in60Days;
                } else if (stockFilter === 'expired') {
                    const exp = item.expiration_date || '';
                    matchesStock = exp && exp < today;
                }
                
                return matchesSearch && matchesType && matchesGrams && matchesStock;
            });
            
            inventoryCurrentPage = 1;
            renderInventoryTable();
        }

        function resetInventoryFilters() {
            const searchInput = document.getElementById('searchPrescription');
            const typeSelect = document.getElementById('filterMedicineType');
            const gramsSelect = document.getElementById('filterMedicineGrams');
            const stockSelect = document.getElementById('filterMedicineStock');
            
            if (searchInput) searchInput.value = '';
            if (typeSelect) typeSelect.value = '';
            if (gramsSelect) gramsSelect.value = '';
            if (stockSelect) stockSelect.value = '';
            
            filteredInventory = [...inventoryItems];
            inventoryCurrentPage = 1;
            renderInventoryTable();
        }

        function renderInventoryTable() {
            const tbody = document.getElementById('prescriptionTableBody');
            const headerRow = document.getElementById('tableHeaderRow');
            const emptyState = document.getElementById('emptyState');
            const tableElement = tbody ? tbody.closest('table') : null;
            const paginationDiv = document.querySelector('.px-4.py-3.border-t');
            
            if (headerRow) {
                headerRow.innerHTML = `
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Medicine Name</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Type / Category</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Grams / Dosage</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Stock Qty</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Expiration Date</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Batch / Lot #</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                `;
            }
            
            const totalItems = filteredInventory.length;
            const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE) || 1;
            
            if (inventoryCurrentPage < 1) inventoryCurrentPage = 1;
            if (inventoryCurrentPage > totalPages) inventoryCurrentPage = totalPages;
            
            const startIndex = (inventoryCurrentPage - 1) * ITEMS_PER_PAGE;
            const endIndex = Math.min(startIndex + ITEMS_PER_PAGE, totalItems);
            const pageData = filteredInventory.slice(startIndex, endIndex);
            
            if (totalItems === 0) {
                if (emptyState) {
                    emptyState.classList.remove('hidden');
                    emptyState.style.display = 'flex';
                    emptyState.innerHTML = `
                        <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                            <i class="fa-solid fa-boxes-stacked text-slate-400"></i>
                        </div>
                        <p class="text-sm font-semibold text-slate-600">No medicine items match your filter</p>
                        <p class="text-xs text-slate-400 mt-1">Try selecting different types or clearing your search</p>
                        <button onclick="resetInventoryFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear filters</button>
                    `;
                }
                if (tableElement) tableElement.classList.add('hidden');
                if (paginationDiv) paginationDiv.classList.add('hidden');
                if (tbody) tbody.innerHTML = '';
                return;
            }
            
            if (emptyState) {
                emptyState.classList.add('hidden');
                emptyState.style.display = 'none';
            }
            if (tableElement) tableElement.classList.remove('hidden');
            if (paginationDiv) paginationDiv.classList.remove('hidden');
            
            const today = new Date().toISOString().split('T')[0];
            const in60Days = new Date(Date.now() + 60 * 86400000).toISOString().split('T')[0];
            
            tbody.innerHTML = pageData.map(item => {
                const exp = item.expiration_date || 'N/A';
                let expBadge = '';
                if (exp !== 'N/A') {
                    if (exp < today) {
                        expBadge = `<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 flex items-center gap-1 w-fit"><i class="fa-solid fa-triangle-exclamation"></i> Expired (${formatDate(exp)})</span>`;
                    } else if (exp <= in60Days) {
                        expBadge = `<span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 flex items-center gap-1 w-fit"><i class="fa-solid fa-clock"></i> Soon (${formatDate(exp)})</span>`;
                    } else {
                        expBadge = `<span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center gap-1 w-fit"><i class="fa-solid fa-calendar-check"></i> ${formatDate(exp)}</span>`;
                    }
                } else {
                    expBadge = '<span class="text-slate-400 text-xs">No Expiry Date</span>';
                }
                
                const stock = parseInt(item.stock) || 0;
                let stockBadge = '';
                if (stock <= 0) {
                    stockBadge = `<span class="px-2.5 py-1 bg-rose-100 text-rose-700 rounded-full text-xs font-bold">Out of Stock (0)</span>`;
                } else if (stock <= 50) {
                    stockBadge = `<span class="px-2.5 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">Low Stock (${stock})</span>`;
                } else {
                    stockBadge = `<span class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">${stock} units</span>`;
                }
                
                return `
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-xs shrink-0">
                                    <i class="fa-solid fa-capsules"></i>
                                </div>
                                <div>
                                    <span class="font-bold text-slate-800 text-sm block">${escapeHtml(item.name)}</span>
                                    <span class="text-xs text-slate-400">${escapeHtml(item.form || 'Tablet')}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                ${escapeHtml(item.category || 'General')}
                            </span>
                        </td>
                        <td class="px-4 py-3 font-semibold text-xs text-brand-dark">
                            ${escapeHtml(item.strength || 'Standard')}
                        </td>
                        <td class="px-4 py-3">
                            ${stockBadge}
                        </td>
                        <td class="px-4 py-3">
                            ${expBadge}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-slate-500">
                            ${escapeHtml(item.batch_number || 'LOT-2026-GEN')}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <div class="flex items-center justify-center gap-1">
                                ${CAN_ADD_MEDICINE ? `
                                <button onclick="deleteMedicine('${item.id}')" title="Delete Medicine" class="p-1.5 text-rose-500 hover:bg-rose-50 rounded-lg transition">
                                    <i class="fa-solid fa-trash-can text-sm"></i>
                                </button>
                                ` : '<span class="text-[10px] text-slate-400">View Only</span>'}
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
            
            document.getElementById('showingStart').textContent = startIndex + 1;
            document.getElementById('showingEnd').textContent = endIndex;
            document.getElementById('showingTotal').textContent = totalItems;
            const itemTypeEl = document.getElementById('showingItemType');
            if (itemTypeEl) itemTypeEl.textContent = 'medicines';
            
            renderInventoryPagination(totalItems, totalPages);
        }

        function changeInventoryPage(page) {
            const totalItems = filteredInventory.length;
            const totalPages = Math.ceil(totalItems / ITEMS_PER_PAGE) || 1;
            if (page < 1 || page > totalPages) return;
            inventoryCurrentPage = page;
            renderInventoryTable();
        }

        function renderInventoryPagination(totalItems, totalPages) {
            const container = document.getElementById('paginationControls');
            if (!container) return;

            if (totalPages <= 1) {
                container.innerHTML = '';
                return;
            }

            const btnBase = 'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors';
            const btnActive = `${btnBase} bg-brand-dark text-white shadow-sm`;
            const btnInactive = `${btnBase} bg-white border border-slate-200 text-slate-600 hover:bg-slate-50`;
            const btnDisabled = `${btnBase} bg-white border border-slate-200 text-slate-300 cursor-not-allowed`;

            let html = '';

            html += `<button onclick="changeInventoryPage(${inventoryCurrentPage - 1})"
                        class="${inventoryCurrentPage === 1 ? btnDisabled : btnInactive}"
                        ${inventoryCurrentPage === 1 ? 'disabled' : ''}>
                        <i class="fa-solid fa-chevron-left text-[10px]"></i>
                    </button>`;

            const delta = 2;
            const rangeStart = Math.max(1, inventoryCurrentPage - delta);
            const rangeEnd   = Math.min(totalPages, inventoryCurrentPage + delta);

            if (rangeStart > 1) {
                html += `<button onclick="changeInventoryPage(1)" class="${btnInactive}">1</button>`;
                if (rangeStart > 2) {
                    html += `<span class="px-1 py-1.5 text-xs text-slate-400">…</span>`;
                }
            }

            for (let p = rangeStart; p <= rangeEnd; p++) {
                html += `<button onclick="changeInventoryPage(${p})"
                            class="${p === inventoryCurrentPage ? btnActive : btnInactive}">
                            ${p}
                         </button>`;
            }

            if (rangeEnd < totalPages) {
                if (rangeEnd < totalPages - 1) {
                    html += `<span class="px-1 py-1.5 text-xs text-slate-400">…</span>`;
                }
                html += `<button onclick="changeInventoryPage(${totalPages})" class="${btnInactive}">${totalPages}</button>`;
            }

            html += `<button onclick="changeInventoryPage(${inventoryCurrentPage + 1})"
                        class="${inventoryCurrentPage === totalPages ? btnDisabled : btnInactive}"
                        ${inventoryCurrentPage === totalPages ? 'disabled' : ''}>
                        <i class="fa-solid fa-chevron-right text-[10px]"></i>
                    </button>`;

            container.innerHTML = html;
        }

        // ============================================================
        // STATS UPDATE (Inventory Mode)
        // ============================================================
        function updateInventoryStats() {
            const total = inventoryItems.length;
            const inStock = inventoryItems.filter(i => (parseInt(i.stock) || 0) > 50).length;
            const lowStock = inventoryItems.filter(i => {
                const s = parseInt(i.stock) || 0;
                return s > 0 && s <= 50;
            }).length;
            const outOfStock = inventoryItems.filter(i => (parseInt(i.stock) || 0) <= 0).length;
            
            const today = new Date().toISOString().split('T')[0];
            const in60Days = new Date(Date.now() + 60 * 86400000).toISOString().split('T')[0];
            const expiringSoon = inventoryItems.filter(i => i.expiration_date && i.expiration_date >= today && i.expiration_date <= in60Days).length;
            const expired = inventoryItems.filter(i => i.expiration_date && i.expiration_date < today).length;
            const totalExpiring = expiringSoon + expired;

            // Card 1: Total Medicines
            const totalPrescriptionsEl = document.getElementById('totalPrescriptions');
            const kpiLabel1El = document.getElementById('kpiLabel1');
            const kpiBadge1El = document.getElementById('kpiBadge1');
            const totalDispensedEl = document.getElementById('totalDispensed');
            const kpiIcon1El = document.getElementById('kpiIcon1');

            if (totalPrescriptionsEl) totalPrescriptionsEl.textContent = total;
            if (kpiLabel1El) kpiLabel1El.textContent = 'Total Medicines';
            if (kpiBadge1El) {
                kpiBadge1El.textContent = '📦 Formulary';
                kpiBadge1El.className = 'px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold';
            }
            if (totalDispensedEl) totalDispensedEl.textContent = `${inStock} in stock`;
            if (kpiIcon1El) kpiIcon1El.innerHTML = '<i class="fa-solid fa-boxes-stacked text-lg"></i>';

            // Card 2: In Stock
            const dispensedCountEl = document.getElementById('dispensedCount');
            const kpiLabel2El = document.getElementById('kpiLabel2');
            const kpiBadge2El = document.getElementById('kpiBadge2');
            const kpiSubtext2El = document.getElementById('kpiSubtext2');
            const kpiIcon2El = document.getElementById('kpiIcon2');

            if (dispensedCountEl) dispensedCountEl.textContent = inStock;
            if (kpiLabel2El) kpiLabel2El.textContent = 'In Stock (>50)';
            if (kpiBadge2El) {
                kpiBadge2El.textContent = '✅ Available';
                kpiBadge2El.className = 'px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold';
            }
            if (kpiSubtext2El) kpiSubtext2El.textContent = 'Adequate inventory';
            if (kpiIcon2El) kpiIcon2El.innerHTML = '<i class="fa-solid fa-circle-check text-lg"></i>';

            // Card 3: Low Stock
            const pendingCountEl = document.getElementById('pendingCount');
            const kpiLabel3El = document.getElementById('kpiLabel3');
            const kpiBadge3El = document.getElementById('kpiBadge3');
            const kpiSubtext3El = document.getElementById('kpiSubtext3');
            const kpiIcon3El = document.getElementById('kpiIcon3');

            if (pendingCountEl) pendingCountEl.textContent = lowStock + (outOfStock > 0 ? ` (${outOfStock} out)` : '');
            if (kpiLabel3El) kpiLabel3El.textContent = 'Low Stock (≤50)';
            if (kpiBadge3El) {
                kpiBadge3El.textContent = '⚠️ Reorder';
                kpiBadge3El.className = 'px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold';
            }
            if (kpiSubtext3El) kpiSubtext3El.textContent = `${lowStock} low, ${outOfStock} out of stock`;
            if (kpiIcon3El) kpiIcon3El.innerHTML = '<i class="fa-solid fa-triangle-exclamation text-lg"></i>';

            // Card 4: Expiring / Expired
            const totalMedicationsEl = document.getElementById('totalMedications');
            const kpiLabel4El = document.getElementById('kpiLabel4');
            const kpiBadge4El = document.getElementById('kpiBadge4');
            const kpiSubtext4El = document.getElementById('kpiSubtext4');
            const kpiIcon4El = document.getElementById('kpiIcon4');

            if (totalMedicationsEl) totalMedicationsEl.textContent = totalExpiring;
            if (kpiLabel4El) kpiLabel4El.textContent = 'Expiring / Expired';
            if (kpiBadge4El) {
                kpiBadge4El.textContent = '📅 Expiry Alert';
                kpiBadge4El.className = 'px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold';
            }
            if (kpiSubtext4El) kpiSubtext4El.textContent = `${expired} expired, ${expiringSoon} soon`;
            if (kpiIcon4El) kpiIcon4El.innerHTML = '<i class="fa-solid fa-calendar-xmark text-lg"></i>';
        }

        async function saveNewMedicine(e) {
            e.preventDefault();
            if (!CAN_ADD_MEDICINE) {
                ModalSystem.toast.error('Permission denied: Only Doctor, Director, and Dentist can add medicines.');
                return;
            }
            
            const name = document.getElementById('med_name').value.trim();
            const category = document.getElementById('med_category').value.trim();
            const strength = document.getElementById('med_strength').value.trim();
            const form = document.getElementById('med_form').value;
            const stock = document.getElementById('med_stock').value;
            const expiration_date = document.getElementById('med_expiration_date').value;
            const batch_number = document.getElementById('med_batch_number').value.trim();
            const description = document.getElementById('med_description').value.trim();
            
            if (!name || !category || !strength || !expiration_date) {
                ModalSystem.toast.warning('Please fill in all required fields (*)');
                return;
            }
            
            const submitBtn = document.getElementById('btnSubmitAddMedicine');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Saving...';
            }
            
            try {
                const response = await fetch('<?php echo site_url('api/drugs.php'); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name, category, strength, form, stock, expiration_date, batch_number, description
                    })
                });
                
                const data = await response.json();
                if (response.ok && data.success) {
                    ModalSystem.toast.success(data.message || 'Medicine added to inventory successfully!');
                    ModalSystem.close('addMedicineModal');
                    document.getElementById('addMedicineForm').reset();
                    
                    if (data.data) {
                        inventoryItems.unshift(data.data);
                        drugs.unshift(data.data);
                    } else {
                        fetchInventoryData();
                    }
                    populateDrugSelect();
                    populateInventoryFilters();
                    filterInventory();
                    updateInventoryStats();
                } else {
                    ModalSystem.toast.error(data.message || 'Failed to add medicine');
                }
            } catch (err) {
                console.error('Error adding medicine:', err);
                ModalSystem.toast.error('An error occurred while adding medicine.');
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fa-solid fa-plus mr-1"></i> Add to Inventory';
                }
            }
        }

        let medicineToDeleteId = null;

        function deleteMedicine(id) {
            openDeleteMedicineModal(id);
        }

        function openDeleteMedicineModal(id) {
            if (!CAN_ADD_MEDICINE) {
                ModalSystem.toast.error('Permission denied: Only Doctor, Director, and Dentist can delete medicines.');
                return;
            }
            
            const item = inventoryItems.find(i => parseInt(i.id) === parseInt(id));
            if (!item) {
                ModalSystem.toast.error('Medicine record not found');
                return;
            }
            
            medicineToDeleteId = id;
            document.getElementById('delete_med_name').textContent = item.name || '-';
            document.getElementById('delete_med_category').textContent = item.category || 'General';
            document.getElementById('delete_med_strength').textContent = item.strength || 'Standard';
            document.getElementById('delete_med_batch').textContent = item.batch_number || 'LOT-2026-GEN';
            
            ModalSystem.open('deleteMedicineModal');
        }

        async function confirmDeleteMedicine() {
            if (!medicineToDeleteId) return;
            
            const btn = document.getElementById('btnConfirmDeleteMedicine');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-xs"></i> Deleting...';
            }
            
            try {
                const response = await fetch('<?php echo site_url('api/drugs.php'); ?>?action=delete&id=' + medicineToDeleteId, {
                    method: 'DELETE'
                });
                const data = await response.json();
                if (data.success) {
                    ModalSystem.toast.success(data.message || 'Medicine removed from inventory successfully.');
                    ModalSystem.close('deleteMedicineModal');
                    
                    inventoryItems = inventoryItems.filter(i => parseInt(i.id) !== parseInt(medicineToDeleteId));
                    drugs = drugs.filter(i => parseInt(i.id) !== parseInt(medicineToDeleteId));
                    
                    populateDrugSelect();
                    populateInventoryFilters();
                    filterInventory();
                    updateInventoryStats();
                } else {
                    ModalSystem.toast.error(data.message || 'Failed to delete medicine');
                }
            } catch (err) {
                console.error('Delete error:', err);
                ModalSystem.toast.error('Failed to delete medicine from inventory.');
            } finally {
                medicineToDeleteId = null;
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fa-solid fa-trash-can text-xs"></i> Delete Medicine';
                }
            }
        }

        // ============================================================
        // VIEW PRESCRIPTION
        // ============================================================
        async function viewPrescription(id) {
            ModalSystem.open('viewPrescriptionModal');
            
            // Use local data immediately for instant render (no Unknown flash)
            const localP = allPrescriptions.find(p => p.id === id);
            if (localP) {
                renderViewModal(localP);
            }
            
            // Then fetch fresh from API to get dispensed_by_name / dispensed_at
            try {
                const response = await fetch(`${API_URL}/${id}`);
                const data = await response.json();
                
                if (data.success && data.data) {
                    // Merge API data with local enriched data — keep local names if API returns Unknown
                    const apiP = data.data;
                    const merged = Object.assign({}, localP || {}, apiP);
                    // Prefer local enriched names if API returned Unknown/empty
                    if (localP) {
                        if (!apiP.patient_name || apiP.patient_name === 'Unknown') merged.patient_name = localP.patient_name;
                        if (!apiP.patient_avatar || apiP.patient_avatar === '??') merged.patient_avatar = localP.patient_avatar;
                        if (!apiP.doctor_name || apiP.doctor_name === 'Unknown') merged.doctor_name = localP.doctor_name;
                    }
                    renderViewModal(merged);
                }
            } catch (error) {
                // Local data already rendered above — just log
                console.warn('Could not refresh prescription from API:', error);
            }
        }

        function renderViewModal(p) {
            const statusColors = {
                dispensed: 'bg-emerald-100 text-emerald-700',
                pending: 'bg-amber-100 text-amber-700',
                cancelled: 'bg-slate-100 text-slate-500'
            };

            const medications = Array.isArray(p.medications)
                ? p.medications
                : (typeof p.medications === 'string' ? JSON.parse(p.medications || '[]') : []);

            const medsHtml = medications.map(m => `
                <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${escapeHtml(m.name || 'Unknown')}</p>
                        <p class="text-xs text-slate-500">${escapeHtml(m.dosage || '')} • ${escapeHtml(m.frequency || '')} • ${escapeHtml(m.duration || '')}</p>
                    </div>
                    <span class="text-xs font-semibold text-brand-dark">Qty: ${m.quantity || '-'}</span>
                </div>
            `).join('') || '<p class="text-xs text-slate-400">No medications listed</p>';

            const patientName = p.patient_name || 'Unknown';
            const patientAvatar = p.patient_avatar || '??';
            const doctorName = p.doctor_name || 'Unknown';
            const rxId = p.prescription_id || 'N/A';

            document.getElementById('prescriptionDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-lg flex-shrink-0">
                            <span class="maskable" data-real="${patientAvatar}" data-masked="??">${patientAvatar}</span>
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">
                                <span class="maskable" data-real="${patientName}" data-masked="${maskName(patientName)}">${patientName}</span>
                            </h4>
                            <p class="text-sm text-slate-500">
                                <span class="maskable font-mono" data-real="${rxId}" data-masked="${maskId(rxId)}">${rxId}</span>
                                &nbsp;•&nbsp;
                                <span>${doctorName}</span>
                            </p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${statusColors[p.status] || statusColors.pending}">
                                ${(p.status || 'pending').toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 font-semibold">Date</p>
                            <p class="text-sm text-slate-800">${formatDate(p.date)}</p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 font-semibold">Doctor</p>
                            <p class="text-sm text-slate-800">
                                <span>${doctorName}</span>
                            </p>
                        </div>
                        ${p.dispensed_by_name ? `
                        <div>
                            <p class="text-xs text-slate-400 font-semibold">Dispensed By</p>
                            <p class="text-sm text-slate-800">
                                <span>${escapeHtml(p.dispensed_by_name)}</span>
                            </p>
                        </div>` : ''}
                        ${p.dispensed_at_formatted ? `
                        <div>
                            <p class="text-xs text-slate-400 font-semibold">Dispensed At</p>
                            <p class="text-sm text-slate-800">${escapeHtml(p.dispensed_at_formatted)}</p>
                        </div>` : ''}
                    </div>
                    <div class="bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">💊 Medications (${medications.length})</h5>
                        <div class="space-y-2">${medsHtml}</div>
                    </div>
                    ${p.notes ? `
                    <div class="bg-brand-light/40 rounded-xl p-4 border border-brand-border">
                        <h5 class="text-sm font-bold text-slate-700 mb-2">📋 Notes</h5>
                        <p class="text-sm text-slate-800">${escapeHtml(p.notes)}</p>
                    </div>` : ''}
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="ModalSystem.close('viewPrescriptionModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                        ${p.status === 'pending' ? `<button onclick="ModalSystem.close('viewPrescriptionModal'); dispensePrescription(${p.id})" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm font-semibold"><i class="fa-solid fa-check mr-1.5"></i> Dispense</button>` : ''}
                    </div>
                </div>
            `;

            // Apply data masking to the newly rendered modal content
            ModalSystem.applyMaskingToModal('viewPrescriptionModal');
        }

        // ============================================================
        // DISPENSE PRESCRIPTION - NO PAGE RELOAD!
        // ============================================================
        async function dispensePrescription(id) {
    ModalSystem.confirm(
        'This will mark the prescription as dispensed.',
        async () => {
            try {
                const csrfToken = CrudAjax.getCsrfToken();
                const response = await fetch(`${API_URL}/${id}`, {
                    method: 'PUT',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        status: 'dispensed',
                        dispensed_by: CURRENT_USER_ID,
                        dispensed_at: new Date().toISOString(),
                        csrf_token: csrfToken
                    })
                });
                const data = await response.json();
                if (data.success) {
                    ModalSystem.toast.success('Prescription dispensed successfully!');
                    const prescription = allPrescriptions.find(p => p.id === id);
                    if (prescription) prescription.status = 'dispensed';
                    filteredPrescriptions = [...allPrescriptions];
                    currentPage = 1;
                    renderTable();
                    updateStats();
                } else {
                    ModalSystem.toast.error(data.message || 'Failed to dispense prescription');
                }
            } catch (error) {
                console.error('Error dispensing prescription:', error);
                ModalSystem.toast.error('Failed to dispense prescription');
            }
        },
        { title: 'Dispense Prescription', confirmText: 'Dispense', type: 'info' }
    );
}

        // ============================================================
        // SAVE PRESCRIPTION - NO PAGE RELOAD!
        // ============================================================
        async function savePrescription(event) {
            event.preventDefault();
            
            if (prescriptionMedications.length === 0) {
                ModalSystem.toast.warning('Please add at least one medication');
                return;
            }

            const data = {
                patient_id: document.getElementById('rx_patient').value,
                employee_id: document.getElementById('rx_doctor').value,
                date: document.getElementById('rx_date').value,
                notes: document.getElementById('rx_notes').value,
                medications: prescriptionMedications
            };

            try {
                const csrfToken = CrudAjax.getCsrfToken();
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ ...data, csrf_token: csrfToken })
                });

                const result = await response.json();
                
                if (result.success) {
                    ModalSystem.toast.success('Prescription created successfully!');
                    prescriptionMedications = [];
                    renderMedicationList();
                    ModalSystem.close('newPrescriptionModal');
                    document.getElementById('newPrescriptionForm').reset();
                    // Restore today's date after reset
                    const dateInput = document.getElementById('rx_date');
                    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
                    // Reload data from API - NO PAGE RELOAD!
                    await loadInitialData();
                } else {
                    ModalSystem.toast.error(result.message || 'Failed to create prescription');
                }
            } catch (error) {
                console.error('Error saving prescription:', error);
                ModalSystem.toast.error('Failed to create prescription');
            }
        }

        // ============================================================
        // EDIT PRESCRIPTION
        // ============================================================
        async function editPrescription(id) {
            // Use local data first for instant, correct render
            const localP = allPrescriptions.find(p => p.id === id);
            
            if (localP) {
                populateEditForm(localP);
                ModalSystem.open('editPrescriptionModal');
                return;
            }
            
            // Fallback to API if not in local cache
            try {
                const response = await fetch(`${API_URL}/${id}`);
                const data = await response.json();
                
                if (!data.success) {
                    ModalSystem.toast.error(data.message || 'Failed to load prescription');
                    return;
                }
                populateEditForm(data.data);
                ModalSystem.open('editPrescriptionModal');
            } catch (error) {
                console.error('Error loading prescription for edit:', error);
                ModalSystem.toast.error('Failed to load prescription');
            }
        }

        function populateEditForm(p) {
            document.getElementById('edit_rx_id').value = p.id;
            // Use enriched patient name from local data — never rely on API for this
            document.getElementById('edit_rx_patient').value = p.patient_name && p.patient_name !== 'Unknown'
                ? p.patient_name
                : 'Unknown Patient';

            // Set doctor select — match by full_name
            const doctorSelect = document.getElementById('edit_rx_doctor');
            const doctorName = p.doctor_name || '';
            // Try to find matching option
            let matched = false;
            Array.from(doctorSelect.options).forEach(opt => {
                if (opt.value === doctorName || opt.textContent.trim() === doctorName) {
                    doctorSelect.value = opt.value;
                    matched = true;
                }
            });
            if (!matched) doctorSelect.value = '';

            document.getElementById('edit_rx_date').value = p.date || '';
            document.getElementById('edit_rx_status').value = p.status || 'pending';
            document.getElementById('edit_rx_notes').value = p.notes || '';
        }

        async function saveEditedPrescription(event) {
            event.preventDefault();
            const id = document.getElementById('edit_rx_id').value;
            
            const data = {
                employee_id: doctors.find(d => {
                    const name = d.full_name || `${d.first_name || ''} ${d.last_name || ''}`.trim();
                    return name === document.getElementById('edit_rx_doctor').value;
                })?.id,
                date: document.getElementById('edit_rx_date').value,
                status: document.getElementById('edit_rx_status').value,
                notes: document.getElementById('edit_rx_notes').value
            };

            try {
                const csrfToken = CrudAjax.getCsrfToken();
                const response = await fetch(`${API_URL}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ ...data, csrf_token: csrfToken })
                });

                const result = await response.json();
                
                if (result.success) {
                    ModalSystem.toast.success('Prescription updated successfully!');
                    ModalSystem.close('editPrescriptionModal');
                    // Reload data from API - NO PAGE RELOAD!
                    await loadInitialData();
                } else {
                    ModalSystem.toast.error(result.message || 'Failed to update prescription');
                }
            } catch (error) {
                console.error('Error updating prescription:', error);
                ModalSystem.toast.error('Failed to update prescription');
            }
        }

        // ============================================================
        // DELETE PRESCRIPTION - NO PAGE RELOAD!
        // ============================================================
        async function deletePrescription(id) {
    ModalSystem.confirm(
        'This prescription will be cancelled.',
        async () => {
            try {
                const csrfToken = CrudAjax.getCsrfToken();
                const response = await fetch(`${API_URL}/${id}`, { 
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ csrf_token: csrfToken })
                });
                const data = await response.json();
                if (data.success) {
                    ModalSystem.toast.success('Prescription cancelled successfully');
                    const prescription = allPrescriptions.find(p => p.id === id);
                    if (prescription) prescription.status = 'cancelled';
                    filteredPrescriptions = [...allPrescriptions];
                    currentPage = 1;
                    renderTable();
                    updateStats();
                } else {
                    ModalSystem.toast.error(data.message || 'Failed to cancel prescription');
                }
            } catch (error) {
                console.error('Error cancelling prescription:', error);
                ModalSystem.toast.error('Failed to cancel prescription');
            }
        },
        { title: 'Cancel Prescription', confirmText: 'Cancel', type: 'danger' }
    );
}

        // ============================================================
        // UTILITY FUNCTIONS
        // ============================================================
        function formatDate(dateStr) {
            if (!dateStr) return 'N/A';
            const date = new Date(dateStr);
            if (isNaN(date.getTime())) return dateStr;
            return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }

        function getStatusClasses(status) {
            switch(status) {
                case 'dispensed': return 'bg-emerald-100 text-emerald-700';
                case 'pending': return 'bg-amber-100 text-amber-700';
                case 'cancelled': return 'bg-slate-100 text-slate-500';
                default: return 'bg-slate-100 text-slate-500';
            }
        }

        // Data masking helpers — mirrors PHP maskName / maskId
        function maskName(name) {
            if (!name) return '';
            return name.split(' ').map(part => {
                if (!part) return '';
                return part.charAt(0).toUpperCase() + '*'.repeat(Math.max(0, part.length - 1));
            }).join(' ');
        }

        function maskId(id) {
            if (!id) return '';
            const s = String(id);
            if (s.length <= 2) return s;
            return s.substring(0, 2) + '*'.repeat(s.length - 2);
        }

        function escapeHtml(str) {
            if (str === null || str === undefined) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        // ============================================================
        // LOAD INITIAL DATA - NO PAGE RELOAD!
        // ============================================================
        async function loadInitialData() {
            try {
                showSkeletonLoading();
                const startTime = performance.now();

                const response = await fetch(`${API_URL}?limit=1000`);
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.success) {
                        // Re-enrich from local patients/doctors data
                        // in case the API enrichment returns Unknown
                        const rawPrescriptions = data.data || [];
                        allPrescriptions = rawPrescriptions.map(p => enrichLocally(p));
                        filteredPrescriptions = [...allPrescriptions];
                        currentPage = 1;
                        
                        updateStats();
                        renderTable();
                        
                        const loadTime = performance.now() - startTime;
                        const loadingInfo = document.getElementById('loadingInfo');
                        if (loadingInfo) {
                            loadingInfo.innerHTML = `<i class="fa-solid fa-check-circle text-emerald-600 mr-2"></i>Updated ${allPrescriptions.length} prescriptions in ${loadTime.toFixed(0)}ms`;
                            setTimeout(() => loadingInfo.classList.add('hidden'), 2000);
                        }
                    } else {
                        ModalSystem.toast.error(data.message || 'Failed to load prescriptions');
                    }
                } else {
                    ModalSystem.toast.error('Failed to load prescriptions');
                }
            } catch (error) {
                console.error('Error loading initial data:', error);
                ModalSystem.toast.error('Failed to load data');
            }
        }

        // Enrich a prescription using the local patients/doctors arrays
        function enrichLocally(p) {
            // Patient name
            if (!p.patient_name || p.patient_name === 'Unknown') {
                const pat = patients.find(pt => pt.id == p.patient_id);
                if (pat) {
                    p.patient_name = `${pat.first_name || ''} ${pat.last_name || ''}`.trim() || 'Unknown';
                    const f = (pat.first_name || '').charAt(0).toUpperCase();
                    const l = (pat.last_name || '').charAt(0).toUpperCase();
                    p.patient_avatar = (f + l) || '??';
                }
            }
            // Doctor name
            if (!p.doctor_name || p.doctor_name === 'Unknown') {
                const doc = doctors.find(d => d.id == p.employee_id);
                if (doc) {
                    p.doctor_name = doc.full_name || `${doc.first_name || ''} ${doc.last_name || ''}`.trim() || 'Unknown';
                }
            }
            // Medications
            if (typeof p.medications === 'string') {
                try { p.medications = JSON.parse(p.medications); } catch(e) { p.medications = []; }
            }
            if (!Array.isArray(p.medications)) p.medications = [];
            return p;
        }

        // ============================================================
        // SHOW SKELETON LOADING
        // ============================================================
        function showSkeletonLoading() {
            const tbody = document.getElementById('prescriptionTableBody');
            let skeletonRows = '';
            for (let i = 0; i < 5; i++) {
                skeletonRows += `
                    <tr class="border-b border-slate-100">
                        <td class="px-4 py-3">
                            <div class="h-4 bg-slate-200 rounded animate-pulse w-16"></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 bg-slate-200 rounded-full animate-pulse flex-shrink-0"></div>
                                <div class="space-y-2">
                                    <div class="h-4 bg-slate-200 rounded animate-pulse w-32"></div>
                                    <div class="h-3 bg-slate-200 rounded animate-pulse w-20"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="h-4 bg-slate-200 rounded animate-pulse w-24"></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="space-y-2">
                                <div class="h-4 bg-slate-200 rounded animate-pulse w-20"></div>
                                <div class="h-4 bg-slate-200 rounded animate-pulse w-24"></div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="h-4 bg-slate-200 rounded animate-pulse w-20"></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="h-6 bg-slate-200 rounded-full animate-pulse w-16"></div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-center gap-1">
                                <div class="w-8 h-8 bg-slate-200 rounded animate-pulse"></div>
                                <div class="w-8 h-8 bg-slate-200 rounded animate-pulse"></div>
                                <div class="w-8 h-8 bg-slate-200 rounded animate-pulse"></div>
                            </div>
                        </td>
                    </tr>
                `;
            }
            tbody.innerHTML = skeletonRows;
            
            const loadingInfo = document.getElementById('loadingInfo');
            if (loadingInfo) {
                loadingInfo.classList.remove('hidden');
                loadingInfo.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-2"></i>Loading prescriptions...';
            }
        }
    </script>
    <?php include_once '../../includes/footer.php'; ?>