    <?php
    // ============================================================
    // 1. PHP BACKEND - Fetch ALL Data for Client-side Pagination
    // ============================================================
    require_once '../../includes/header.php';
    require_once '../../includes/sidebar.php';

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
    try {
        $patientsRaw = $patientModel->all();
        foreach ($patientsRaw as $p) {
            $patients[$p['id']] = $p;
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

    // Get current user ID
    $currentUserId = $_SESSION['user_id'] ?? 1;
    ?>

    <!-- ============================================================ -->
    <!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
    <!-- ============================================================ -->

    <div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Prescriptions</h2>
                <p class="text-sm text-slate-500 mt-0.5">Electronic prescriptions with drug selection & dosage management</p>
            </div>
            <div class="flex gap-3">
                <button onclick="ModalSystem.open('newPrescriptionModal')"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-prescription-bottle text-xs"></i> New Prescription
                </button>
            </div>
        </div>

        <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Prescriptions -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-prescription text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900" id="totalPrescriptions">-</p>
                        <p class="text-xs font-medium text-slate-500">Total Prescriptions</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">💊 All prescriptions</span>
                    <span class="text-[10px] text-slate-400" id="totalDispensed">- dispensed</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Dispensed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600" id="dispensedCount">-</p>
                        <p class="text-xs font-medium text-slate-500">Dispensed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Filled</span>
                    <span class="text-[10px] text-slate-400">Successfully dispensed</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Pending -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600" id="pendingCount">-</p>
                        <p class="text-xs font-medium text-slate-500">Pending</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏳ Awaiting</span>
                    <span class="text-[10px] text-slate-400">Ready for dispensing</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Total Medications -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-violet-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-violet-500 to-violet-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-violet-200">
                        <i class="fa-solid fa-capsules text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-violet-600" id="totalMedications">-</p>
                        <p class="text-xs font-medium text-slate-500">Total Medications</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-violet-100 text-violet-700 rounded-full text-[10px] font-bold">🧪 Items</span>
                    <span class="text-[10px] text-slate-400">Across all prescriptions</span>
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
                <div class="flex gap-2 flex-wrap">
                    <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                        <option value="">All Status</option>
                        <option value="dispensed">Dispensed</option>
                        <option value="pending">Pending</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                    <button onclick="resetFilters()" title="Reset filters"
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
                    <thead class="bg-slate-50 border-b border-slate-200">
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
                    <span class="font-semibold text-slate-700" id="showingTotal">0</span> prescriptions
                </p>
                <div class="flex gap-1" id="paginationControls">
                    <!-- Pagination buttons will be generated here -->
                </div>
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
        const API_URL = '/capstone/api/prescriptions.php';
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
});

        // ============================================================
        // LOAD DRUGS FROM API
        // ============================================================
        async function loadDrugs() {
            try {
                const response = await fetch('/capstone/api/drugs.php');
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
            if (!selectedDrug) {
                ModalSystem.toast.warning('Please search and select a medication first');
                return;
            }
            
            // Check if already added
            if (prescriptionMedications.some(m => m.name === selectedDrug.name)) {
                ModalSystem.toast.warning('Medication already added');
                return;
            }
            
            // Add to prescription
            const medication = {
                id: selectedDrug.id,
                name: selectedDrug.name,
                dosage: selectedDrug.strength || '',
                frequency: document.getElementById('rx_frequency').value,
                duration: document.getElementById('rx_duration').value,
                quantity: calculateQuantity(
                    document.getElementById('rx_frequency').value, 
                    document.getElementById('rx_duration').value
                )
            };
            
            prescriptionMedications.push(medication);
            renderMedicationList();
            clearSelectedDrug();
            ModalSystem.toast.success(selectedDrug.name + ' added to prescription');
        }

        function clearSelectedDrug() {
            selectedDrug = null;
            document.getElementById('selectedDrugDisplay').classList.add('hidden');
            document.getElementById('rx_drug_search').value = '';
            document.getElementById('drugDropdown').classList.add('hidden');
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

        function populateDoctorSelects() {
            const select = document.getElementById('rx_doctor');
            const editSelect = document.getElementById('edit_rx_doctor');
            
            if (select) {
                select.innerHTML = '<option value="">Select Doctor</option>';
                doctors.forEach(d => {
                    const option = document.createElement('option');
                    option.value = d.id;
                    option.textContent = d.full_name || `${d.first_name || ''} ${d.last_name || ''}`.trim() || `Employee #${d.id}`;
                    select.appendChild(option);
                });
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
        
        if (searchValue || statusValue) {
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
                            <p class="font-semibold text-slate-800 text-sm">
                                <span class="maskable" data-real="${patientName}" data-masked="${maskName(patientName)}">${patientName}</span>
                            </p>
                            <p class="text-xs text-slate-400">${medications.length} medication${medications.length !== 1 ? 's' : ''}</p>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3 text-slate-600 text-xs">
                    <span class="maskable" data-real="${doctorName}" data-masked="${maskName(doctorName)}">${doctorName}</span>
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
        // STATS UPDATE
        // ============================================================
        function updateStats() {
            const total = allPrescriptions.length;
            const dispensed = allPrescriptions.filter(p => p.status === 'dispensed').length;
            const pending = allPrescriptions.filter(p => p.status === 'pending').length;
            const totalMeds = allPrescriptions.reduce((sum, p) => sum + (p.medications || []).length, 0);

            document.getElementById('totalPrescriptions').textContent = total;
            document.getElementById('totalDispensed').textContent = `${dispensed} dispensed`;
            document.getElementById('dispensedCount').textContent = dispensed;
            document.getElementById('pendingCount').textContent = pending;
            document.getElementById('totalMedications').textContent = totalMeds;
        }

        // ============================================================
        // SEARCH & FILTER (Client-side - NO PAGE RELOAD!)
        // ============================================================
        document.getElementById('searchPrescription').addEventListener('input', filterPrescriptions);
        document.getElementById('filterStatus').addEventListener('change', filterPrescriptions);

        function filterPrescriptions() {
            const search = document.getElementById('searchPrescription').value.toLowerCase().trim();
            const status = document.getElementById('filterStatus').value;
            
            filteredPrescriptions = allPrescriptions.filter(p => {
                const matchesSearch = !search || 
                    (p.patient_name || '').toLowerCase().includes(search) ||
                    (p.prescription_id || '').toLowerCase().includes(search) ||
                    String(p.id || '').includes(search) ||
                    (p.doctor_name || '').toLowerCase().includes(search) ||
                    (p.medications || []).some(m => (m.name || '').toLowerCase().includes(search));
                
                const matchesStatus = !status || (p.status || '') === status;
                
                return matchesSearch && matchesStatus;
            });
            
            currentPage = 1;
            renderTable();
        }

        function resetFilters() {
            document.getElementById('searchPrescription').value = '';
            document.getElementById('filterStatus').value = '';
            filteredPrescriptions = [...allPrescriptions];
            currentPage = 1;
            renderTable();
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
                                <span class="maskable" data-real="${doctorName}" data-masked="${maskName(doctorName)}">${doctorName}</span>
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
                                <span class="maskable" data-real="${doctorName}" data-masked="${maskName(doctorName)}">${doctorName}</span>
                            </p>
                        </div>
                        ${p.dispensed_by_name ? `
                        <div>
                            <p class="text-xs text-slate-400 font-semibold">Dispensed By</p>
                            <p class="text-sm text-slate-800">
                                <span class="maskable" data-real="${escapeHtml(p.dispensed_by_name)}" data-masked="${maskName(p.dispensed_by_name)}">${escapeHtml(p.dispensed_by_name)}</span>
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
                const response = await fetch(`${API_URL}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        status: 'dispensed',
                        dispensed_by: CURRENT_USER_ID,
                        dispensed_at: new Date().toISOString()
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
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
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
                const response = await fetch(`${API_URL}/${id}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
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
                const response = await fetch(`${API_URL}/${id}`, { method: 'DELETE' });
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