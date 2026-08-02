<?php
// ============================================================
// COLOR PALETTE USED ON THIS PAGE
// ============================================================
//   'brand-dark':   '#0B4F4A',
//   'brand-medium': '#14807A',
//   'brand-light':  '#E6F5F3',
//   'brand-border': '#B8E0DC',
// ============================================================

// ============================================================
// 1. PHP BACKEND - Fetch Data
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('immunization & nutrition');

// Sample Children Data
$children = [
    ['id' => 1, 'child_id' => 'CH-001', 'name' => 'Sofia Garcia', 'age' => '2 yrs 4 mos', 'gender' => 'Female', 'mother' => 'Rosa Mendoza'],
    ['id' => 2, 'child_id' => 'CH-002', 'name' => 'Luis Mendoza', 'age' => '1 yr 3 mos', 'gender' => 'Male', 'mother' => 'Rosa Mendoza'],
    ['id' => 3, 'child_id' => 'CH-003', 'name' => 'Emma Lim', 'age' => '3 yrs 1 mo', 'gender' => 'Female', 'mother' => 'Elena Tan'],
    ['id' => 4, 'child_id' => 'CH-004', 'name' => 'Noah Torres', 'age' => '9 mos', 'gender' => 'Male', 'mother' => 'Elena Torres'],
    ['id' => 5, 'child_id' => 'CH-005', 'name' => 'Isabella Cruz', 'age' => '1 yr 11 mos', 'gender' => 'Female', 'mother' => 'Ana Cruz'],
];

// Standard Vaccine Schedule (Recommended by DOH)
$vaccineSchedule = [
    ['vaccine' => 'BCG', 'dose' => 1, 'due_age' => 'At Birth', 'due_months' => 0, 'description' => 'At birth or as soon as possible'],
    ['vaccine' => 'Hepatitis B', 'dose' => 1, 'due_age' => 'At Birth', 'due_months' => 0, 'description' => 'Within 24 hours of birth'],
    ['vaccine' => 'Hepatitis B', 'dose' => 2, 'due_age' => '1-2 months', 'due_months' => 1.5, 'description' => 'At least 4 weeks after 1st dose'],
    ['vaccine' => 'DPT-Hib-HepB', 'dose' => 1, 'due_age' => '6 weeks', 'due_months' => 1.5, 'description' => 'Pentavalent vaccine'],
    ['vaccine' => 'OPV', 'dose' => 1, 'due_age' => '6 weeks', 'due_months' => 1.5, 'description' => 'Oral Polio Vaccine'],
    ['vaccine' => 'Pneumococcal', 'dose' => 1, 'due_age' => '6 weeks', 'due_months' => 1.5, 'description' => 'PCV'],
    ['vaccine' => 'DPT-Hib-HepB', 'dose' => 2, 'due_age' => '10 weeks', 'due_months' => 2.5, 'description' => 'Pentavalent vaccine'],
    ['vaccine' => 'OPV', 'dose' => 2, 'due_age' => '10 weeks', 'due_months' => 2.5, 'description' => 'Oral Polio Vaccine'],
    ['vaccine' => 'Pneumococcal', 'dose' => 2, 'due_age' => '10 weeks', 'due_months' => 2.5, 'description' => 'PCV'],
    ['vaccine' => 'DPT-Hib-HepB', 'dose' => 3, 'due_age' => '14 weeks', 'due_months' => 3.5, 'description' => 'Pentavalent vaccine'],
    ['vaccine' => 'OPV', 'dose' => 3, 'due_age' => '14 weeks', 'due_months' => 3.5, 'description' => 'Oral Polio Vaccine'],
    ['vaccine' => 'Pneumococcal', 'dose' => 3, 'due_age' => '14 weeks', 'due_months' => 3.5, 'description' => 'PCV'],
    ['vaccine' => 'MMR', 'dose' => 1, 'due_age' => '9 months', 'due_months' => 9, 'description' => 'Measles, Mumps, Rubella'],
    ['vaccine' => 'JE', 'dose' => 1, 'due_age' => '9 months', 'due_months' => 9, 'description' => 'Japanese Encephalitis'],
    ['vaccine' => 'MMR', 'dose' => 2, 'due_age' => '12-15 months', 'due_months' => 13, 'description' => 'Measles, Mumps, Rubella'],
    ['vaccine' => 'Hepatitis A', 'dose' => 1, 'due_age' => '12-15 months', 'due_months' => 13, 'description' => 'Hepatitis A vaccine'],
    ['vaccine' => 'VZV', 'dose' => 1, 'due_age' => '12-15 months', 'due_months' => 13, 'description' => 'Varicella (Chickenpox)'],
    ['vaccine' => 'DPT', 'dose' => 1, 'due_age' => '18 months', 'due_months' => 18, 'description' => 'DPT Booster'],
    ['vaccine' => 'OPV', 'dose' => 4, 'due_age' => '18 months', 'due_months' => 18, 'description' => 'Oral Polio Booster'],
    ['vaccine' => 'MMR', 'dose' => 3, 'due_age' => '4-6 years', 'due_months' => 54, 'description' => 'Measles, Mumps, Rubella'],
];

// Sample Immunization Records
$immunizations = [
    [
        'id' => 1,
        'child_id' => 1,
        'child_name' => 'Sofia Garcia',
        'vaccine' => 'BCG',
        'dose' => 1,
        'date' => '2024-03-15',
        'next_due' => '2024-04-15',
        'administered_by' => 'Nurse Maria Cruz',
        'health_center' => 'Health Center 1',
        'batch_number' => 'BCG-2024-01',
        'status' => 'completed'
    ],
    [
        'id' => 2,
        'child_id' => 1,
        'child_name' => 'Sofia Garcia',
        'vaccine' => 'Hepatitis B',
        'dose' => 1,
        'date' => '2024-03-15',
        'next_due' => '2024-04-15',
        'administered_by' => 'Nurse Maria Cruz',
        'health_center' => 'Health Center 1',
        'batch_number' => 'HB-2024-01',
        'status' => 'completed'
    ],
    [
        'id' => 3,
        'child_id' => 1,
        'child_name' => 'Sofia Garcia',
        'vaccine' => 'DPT-Hib-HepB',
        'dose' => 1,
        'date' => '2024-04-20',
        'next_due' => '2024-05-20',
        'administered_by' => 'Nurse Anna Reyes',
        'health_center' => 'Health Center 1',
        'batch_number' => 'PENTA-2024-01',
        'status' => 'completed'
    ],
    [
        'id' => 4,
        'child_id' => 1,
        'child_name' => 'Sofia Garcia',
        'vaccine' => 'OPV',
        'dose' => 1,
        'date' => '2024-04-20',
        'next_due' => '2024-05-20',
        'administered_by' => 'Nurse Anna Reyes',
        'health_center' => 'Health Center 1',
        'batch_number' => 'OPV-2024-01',
        'status' => 'completed'
    ],
    [
        'id' => 5,
        'child_id' => 2,
        'child_name' => 'Luis Mendoza',
        'vaccine' => 'BCG',
        'dose' => 1,
        'date' => '2025-04-20',
        'next_due' => '2025-05-20',
        'administered_by' => 'Nurse Maria Cruz',
        'health_center' => 'Health Center 1',
        'batch_number' => 'BCG-2025-01',
        'status' => 'completed'
    ],
    [
        'id' => 6,
        'child_id' => 2,
        'child_name' => 'Luis Mendoza',
        'vaccine' => 'Hepatitis B',
        'dose' => 1,
        'date' => '2025-04-20',
        'next_due' => '2025-05-20',
        'administered_by' => 'Nurse Maria Cruz',
        'health_center' => 'Health Center 1',
        'batch_number' => 'HB-2025-01',
        'status' => 'completed'
    ],
    [
        'id' => 7,
        'child_id' => 2,
        'child_name' => 'Luis Mendoza',
        'vaccine' => 'DPT-Hib-HepB',
        'dose' => 1,
        'due_date' => '2025-06-01',
        'administered_by' => null,
        'health_center' => 'Health Center 1',
        'batch_number' => null,
        'status' => 'missed'
    ],
    [
        'id' => 8,
        'child_id' => 3,
        'child_name' => 'Emma Lim',
        'vaccine' => 'MMR',
        'dose' => 1,
        'date' => '2024-03-01',
        'next_due' => '2025-03-01',
        'administered_by' => 'Dr. Elena Santos',
        'health_center' => 'Health Center 2',
        'batch_number' => 'MMR-2024-01',
        'status' => 'completed'
    ],
    [
        'id' => 9,
        'child_id' => 4,
        'child_name' => 'Noah Torres',
        'vaccine' => 'BCG',
        'dose' => 1,
        'date' => '2025-10-10',
        'next_due' => '2025-11-10',
        'administered_by' => 'Nurse Anna Reyes',
        'health_center' => 'Health Center 1',
        'batch_number' => 'BCG-2025-02',
        'status' => 'completed'
    ],
    [
        'id' => 10,
        'child_id' => 4,
        'child_name' => 'Noah Torres',
        'vaccine' => 'Hepatitis B',
        'dose' => 1,
        'due_date' => '2025-10-11',
        'administered_by' => null,
        'health_center' => 'Health Center 1',
        'batch_number' => null,
        'status' => 'missed'
    ],
    [
        'id' => 11,
        'child_id' => 5,
        'child_name' => 'Isabella Cruz',
        'vaccine' => 'BCG',
        'dose' => 1,
        'date' => '2024-08-25',
        'next_due' => '2024-09-25',
        'administered_by' => 'Nurse Maria Cruz',
        'health_center' => 'Health Center 2',
        'batch_number' => 'BCG-2024-02',
        'status' => 'completed'
    ],
    [
        'id' => 12,
        'child_id' => 5,
        'child_name' => 'Isabella Cruz',
        'vaccine' => 'Hepatitis B',
        'dose' => 1,
        'date' => '2024-08-25',
        'next_due' => '2024-09-25',
        'administered_by' => 'Nurse Maria Cruz',
        'health_center' => 'Health Center 2',
        'batch_number' => 'HB-2024-02',
        'status' => 'completed'
    ],
];

// Missed vaccines based on schedule vs actual
$missedVaccines = [
    ['child' => 'Luis Mendoza', 'vaccine' => 'DPT-Hib-HepB', 'dose' => 1, 'due_date' => '2025-06-01', 'days_overdue' => 45],
    ['child' => 'Noah Torres', 'vaccine' => 'Hepatitis B', 'dose' => 1, 'due_date' => '2025-10-11', 'days_overdue' => 280],
    ['child' => 'Sofia Garcia', 'vaccine' => 'MMR', 'dose' => 1, 'due_date' => '2024-12-15', 'days_overdue' => 214],
];

// Due date alerts (next vaccines due within 30 days)
$dueAlerts = [
    ['child' => 'Emma Lim', 'vaccine' => 'DPT', 'dose' => 1, 'due_date' => '2026-08-01', 'days_left' => 15, 'priority' => 'high'],
    ['child' => 'Sofia Garcia', 'vaccine' => 'MMR', 'dose' => 2, 'due_date' => '2026-07-28', 'days_left' => 11, 'priority' => 'high'],
    ['child' => 'Isabella Cruz', 'vaccine' => 'DPT-Hib-HepB', 'dose' => 1, 'due_date' => '2026-08-10', 'days_left' => 24, 'priority' => 'medium'],
];

// Stats
$totalImmunizations = count($immunizations);
$completedImmunizations = count(array_filter($immunizations, fn($i) => $i['status'] === 'completed'));
$missedImmunizations = count(array_filter($immunizations, fn($i) => $i['status'] === 'missed'));
$pendingImmunizations = count(array_filter($immunizations, fn($i) => $i['status'] === 'pending'));

$title = 'Vaccination Tracking';
?>

<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight">Vaccination Tracking</h2>
            <p class="text-sm text-slate-500 mt-0.5">Track immunizations, schedules, and due dates</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openModal('recordVaccinationModal')"
                    class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition-colors text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-syringe text-xs"></i> Record Vaccination
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODERN KPI CARDS - Updated to match design               -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Records -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-syringe text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo $totalImmunizations; ?></p>
                        <p class="text-xs font-medium text-slate-500">Total Records</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 rounded-full text-[10px] font-bold">💉 All vaccinations</span>
                    <span class="text-[10px] text-slate-400"><?php echo $completedImmunizations; ?> completed</span>
                </div>
            </div>
        </div>

        <!-- Card 2: Completed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-emerald-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200">
                        <i class="fa-solid fa-check-circle text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-emerald-600"><?php echo $completedImmunizations; ?></p>
                        <p class="text-xs font-medium text-slate-500">Completed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">✅ Done</span>
                    <span class="text-[10px] text-slate-400">Successfully administered</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Missed -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-rose-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-rose-500 to-rose-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-rose-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-rose-600"><?php echo $missedImmunizations; ?></p>
                        <p class="text-xs font-medium text-slate-500">Missed</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[10px] font-bold">⚠️ Overdue</span>
                    <span class="text-[10px] text-slate-400">Requires attention</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Due Soon -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-clock text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-amber-600"><?php echo count($dueAlerts); ?></p>
                        <p class="text-xs font-medium text-slate-500">Due Soon</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⏰ Upcoming</span>
                    <span class="text-[10px] text-slate-400">Within 30 days</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Due Date Alerts -->
    <?php if (count($dueAlerts) > 0): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-bell text-amber-500 text-lg"></i>
            <span class="text-sm text-amber-700">
                <span class="font-bold"><?php echo count($dueAlerts); ?></span> vaccine(s) due within 30 days
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='due_soon'; filterVaccinations();" 
                class="text-xs font-semibold text-amber-700 hover:text-amber-900 underline">
            View due
        </button>
    </div>
    <?php endif; ?>

    <!-- Missed Vaccines Alert -->
    <?php if (count($missedVaccines) > 0): ?>
    <div class="bg-rose-50 border border-rose-200 rounded-xl p-3 mb-4 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <i class="fa-solid fa-triangle-exclamation text-rose-500 text-lg"></i>
            <span class="text-sm text-rose-700">
                <span class="font-bold"><?php echo count($missedVaccines); ?></span> missed vaccine(s) require attention
            </span>
        </div>
        <button onclick="document.getElementById('filterStatus').value='missed'; filterVaccinations();" 
                class="text-xs font-semibold text-rose-700 hover:text-rose-900 underline">
            View missed
        </button>
    </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <div class="bg-white rounded-xl shadow-xs p-4 border border-slate-200 mb-6">
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1 relative">
                <i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                <input type="text"
                       id="searchVaccination"
                       placeholder="Search by child name, vaccine, or batch number..."
                       class="w-full pl-9 pr-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm transition">
            </div>
            <div class="flex gap-2 flex-wrap">
                <select id="filterStatus" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Status</option>
                    <option value="completed">Completed</option>
                    <option value="pending">Pending</option>
                    <option value="missed">Missed</option>
                    <option value="due_soon">Due Soon</option>
                </select>
                <select id="filterVaccine" class="px-4 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                    <option value="">All Vaccines</option>
                    <?php 
                        $vaccines = array_unique(array_column($vaccineSchedule, 'vaccine'));
                        foreach ($vaccines as $v): 
                    ?>
                        <option value="<?php echo $v; ?>"><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
                      <input type="date" id="filterDateFrom" aria-label="Administered date from"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                      <input type="date" id="filterDateTo" aria-label="Administered date to"
                          class="px-3 py-2 border border-slate-200 rounded-lg focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none text-sm bg-white">
                <button onclick="resetFilters()" title="Reset filters"
                        class="px-3 py-2 bg-slate-100 text-slate-500 rounded-lg hover:bg-slate-200 hover:text-slate-700 transition-colors text-sm">
                    <i class="fa-solid fa-rotate-right"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Immunization Table -->
    <div class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Child</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vaccine</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dose</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Date Administered</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Next Due</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Batch #</th>
                        <th class="px-4 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 py-3 text-center text-[10px] font-bold text-slate-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="vaccinationTableBody">
                    <?php foreach (array_slice($immunizations, 0, 10) as $immunization): ?>
                    <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors vaccination-row <?php echo $immunization['status'] === 'missed' ? 'bg-rose-50/50' : ''; ?>"
                        data-child="<?php echo strtolower($immunization['child_name']); ?>"
                        data-vaccine="<?php echo strtolower($immunization['vaccine']); ?>"
                        data-status="<?php echo $immunization['status']; ?>"
                        data-date="<?php echo htmlspecialchars($immunization['date'] ?? ''); ?>"
                        data-next-due="<?php echo htmlspecialchars($immunization['next_due'] ?? ($immunization['due_date'] ?? '')); ?>"
                        data-batch="<?php echo htmlspecialchars(strtolower($immunization['batch_number'] ?? '')); ?>"
                        data-dose="<?php echo (int)$immunization['dose']; ?>">
                        <td class="px-4 py-3">
                            <div>
                                <p class="font-semibold text-slate-800 text-sm"><?php echo $immunization['child_name']; ?></p>
                                <p class="text-xs text-slate-400"><?php echo $immunization['child_id'] ?? ''; ?></p>
                            </div>
                        </td>
                        <td class="px-4 py-3 font-medium text-slate-700 text-xs"><?php echo $immunization['vaccine']; ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs">Dose <?php echo $immunization['dose']; ?></td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <?php echo isset($immunization['date']) ? date('M d, Y', strtotime($immunization['date'])) : '—'; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-600 text-xs">
                            <?php 
                                $nextDue = isset($immunization['next_due']) ? $immunization['next_due'] : ($immunization['due_date'] ?? null);
                                if ($nextDue): 
                                    $daysLeft = (strtotime($nextDue) - time()) / 86400;
                                    $daysLeft = round($daysLeft);
                            ?>
                                <span class="<?php echo $daysLeft <= 30 && $daysLeft > 0 ? 'text-rose-600 font-bold' : 'text-slate-500'; ?>">
                                    <?php echo date('M d, Y', strtotime($nextDue)); ?>
                                </span>
                                <?php if ($daysLeft > 0 && $daysLeft <= 30): ?>
                                    <span class="block text-[10px] text-rose-500"><?php echo $daysLeft; ?> days left</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-slate-400">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-3 text-slate-500 text-xs font-mono"><?php echo $immunization['batch_number'] ?? '—'; ?></td>
                        <td class="px-4 py-3">
                            <?php
                                $statusColors = [
                                    'completed' => 'bg-emerald-100 text-emerald-700',
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'missed' => 'bg-rose-100 text-rose-700'
                                ];
                            ?>
                            <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $statusColors[$immunization['status']] ?? $statusColors['pending']; ?>">
                                <?php echo ucfirst($immunization['status']); ?>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <button onclick="viewImmunization(<?php echo $immunization['id']; ?>)"
                                        class="p-1.5 text-brand-medium hover:bg-brand-light rounded-lg transition" title="View">
                                    <i class="fa-solid fa-eye text-sm"></i>
                                </button>
                                <?php if ($immunization['status'] === 'missed' || $immunization['status'] === 'pending'): ?>
                                    <button onclick="recordVaccination(<?php echo $immunization['id']; ?>)"
                                            class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="Record">
                                        <i class="fa-solid fa-syringe text-sm"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Empty state -->
        <div id="emptyState" class="hidden flex-col items-center justify-center py-14 text-center">
            <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center mb-3">
                <i class="fa-solid fa-syringe text-slate-400"></i>
            </div>
            <p class="text-sm font-semibold text-slate-600">No vaccinations match your filters</p>
            <p class="text-xs text-slate-400 mt-1">Try adjusting your search or clearing filters</p>
            <button onclick="resetFilters()" class="mt-3 text-xs font-semibold text-brand-medium hover:text-brand-dark">Clear all filters</button>
        </div>

        <!-- Pagination -->
        <div class="px-4 py-3 border-t border-slate-200 flex flex-col sm:flex-row justify-between items-center gap-3 bg-slate-50">
            <p class="text-xs text-slate-500">
                Showing <span class="font-semibold text-slate-700">1</span> to
                <span class="font-semibold text-slate-700"><?php echo min(10, $totalImmunizations); ?></span> of
                <span class="font-semibold text-slate-700"><?php echo $totalImmunizations; ?></span> records
            </p>
            <div class="flex gap-1">
                <button class="px-3 py-1.5 rounded-lg text-sm bg-slate-100 text-slate-300 cursor-not-allowed" disabled>
                    <i class="fa-solid fa-chevron-left text-xs"></i>
                </button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-brand-dark text-white">1</button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">2</button>
                <button class="px-3 py-1.5 rounded-lg text-sm font-medium bg-white border border-slate-200 text-slate-600 hover:bg-slate-100">
                    <i class="fa-solid fa-chevron-right text-xs"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Vaccine Schedule Section -->
    <div class="mt-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-table-list text-brand-medium"></i> Recommended Vaccine Schedule
            </h3>
            <button onclick="document.getElementById('vaccineSchedule').classList.toggle('hidden')" 
                    class="text-xs font-semibold text-brand-medium hover:text-brand-dark transition">
                <i class="fa-solid fa-chevron-down"></i> Toggle
            </button>
        </div>
        <div id="vaccineSchedule" class="bg-white rounded-xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Vaccine</th>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Dose</th>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Due Age</th>
                            <th class="px-4 py-2 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($vaccineSchedule, 0, 15) as $schedule): ?>
                        <tr class="border-b border-slate-100 hover:bg-brand-light/40 transition-colors">
                            <td class="px-4 py-2 font-medium text-slate-700 text-xs"><?php echo $schedule['vaccine']; ?></td>
                            <td class="px-4 py-2 text-slate-600 text-xs"><?php echo $schedule['dose']; ?></td>
                            <td class="px-4 py-2 text-slate-600 text-xs"><?php echo $schedule['due_age']; ?></td>
                            <td class="px-4 py-2 text-slate-500 text-xs"><?php echo $schedule['description']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-2 text-center text-xs text-slate-400 border-t border-slate-200">
                <i class="fa-solid fa-info-circle mr-1"></i>
                Based on DOH National Immunization Program schedule
            </div>
        </div>
    </div>
</div>

<!-- ============================================================ -->
<!-- RECORD VACCINATION MODAL                                     -->
<!-- ============================================================ -->
<div id="recordVaccinationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-syringe text-brand-medium"></i>
                Record Vaccination
            </h3>
            <button onclick="closeModal('recordVaccinationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="recordVaccinationForm" class="p-6 space-y-4" onsubmit="saveVaccinationRecord(event)">
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Child</label>
                <select id="vacc_child" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Child</option>
                    <?php foreach ($children as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?> (<?php echo $c['child_id']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Vaccine</label>
                <select id="vacc_vaccine" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="">Select Vaccine</option>
                    <?php 
                        $vaccines = array_unique(array_column($vaccineSchedule, 'vaccine'));
                        foreach ($vaccines as $v): 
                    ?>
                        <option value="<?php echo $v; ?>"><?php echo $v; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Dose Number</label>
                <input type="number" id="vacc_dose" min="1" max="9999" step="1" required inputmode="numeric" oninput="limitDoseInput(this)" title="Dose number must be 1 to 9999" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Date Administered</label>
                <input type="date" id="vacc_date" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Next Due Date</label>
                <input type="date" id="vacc_next_due" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Administered By</label>
                <select id="vacc_admin" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Nurse Maria Cruz">Nurse Maria Cruz</option>
                    <option value="Nurse Anna Reyes">Nurse Anna Reyes</option>
                    <option value="Dr. Elena Santos">Dr. Elena Santos</option>
                    <option value="Dr. Ana Cruz">Dr. Ana Cruz</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Health Center</label>
                <select id="vacc_center" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                    <option value="Health Center 1">Health Center 1</option>
                    <option value="Health Center 2">Health Center 2</option>
                    <option value="Health Center 3">Health Center 3</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Batch Number</label>
                <input type="text" id="vacc_batch" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="e.g. BCG-2026-01">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeModal('recordVaccinationModal')"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">
                    <i class="fa-solid fa-syringe mr-1.5"></i> Record Vaccination
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================ -->
<!-- VIEW IMMUNIZATION MODAL                                      -->
<!-- ============================================================ -->
<div id="viewImmunizationModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-md max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
            <h3 class="font-bold text-slate-900">Immunization Details</h3>
            <button onclick="closeModal('viewImmunizationModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div id="immunizationDetailsContent" class="p-6">
            <div class="flex items-center justify-center py-10 text-slate-400 text-sm">
                <i class="fa-solid fa-spinner fa-spin mr-2"></i> Loading...
            </div>
        </div>
    </div>
</div>

<!-- Toast notification -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    const IMMUNIZATIONS = <?php echo json_encode(array_column($immunizations, null, 'id'), JSON_PRETTY_PRINT); ?>;

    // ============================================================
    // MODAL FUNCTIONS
    // ============================================================
    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.getElementById(id).classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    // Close modal on backdrop click
    document.querySelectorAll('.fixed.inset-0').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
                this.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            }
        });
    });

    // ============================================================
    // VIEW IMMUNIZATION
    // ============================================================
    function viewImmunization(id) {
        openModal('viewImmunizationModal');
        const i = IMMUNIZATIONS[id];
        if (!i) return;

        setTimeout(() => {
            document.getElementById('immunizationDetailsContent').innerHTML = `
                <div class="space-y-4">
                    <div class="flex items-center gap-4 pb-4 border-b border-slate-200">
                        <div class="w-14 h-14 rounded-full bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-bold text-2xl flex-shrink-0">
                            ${i.child_name.charAt(0)}
                        </div>
                        <div>
                            <h4 class="text-lg font-bold text-slate-900">${i.child_name}</h4>
                            <p class="text-sm text-slate-500">${i.vaccine} • Dose ${i.dose}</p>
                            <span class="inline-block px-2 py-0.5 rounded-full text-xs font-semibold mt-1 ${i.status === 'completed' ? 'bg-emerald-100 text-emerald-700' : i.status === 'missed' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700'}">
                                ${i.status.toUpperCase()}
                            </span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div><p class="text-xs text-slate-400">Date Administered</p><p class="text-sm text-slate-800">${i.date ? new Date(i.date).toLocaleDateString() : '—'}</p></div>
                        <div><p class="text-xs text-slate-400">Next Due</p><p class="text-sm text-slate-800">${i.next_due ? new Date(i.next_due).toLocaleDateString() : '—'}</p></div>
                        <div><p class="text-xs text-slate-400">Administered By</p><p class="text-sm text-slate-800">${i.administered_by || '—'}</p></div>
                        <div><p class="text-xs text-slate-400">Health Center</p><p class="text-sm text-slate-800">${i.health_center}</p></div>
                        <div class="col-span-2"><p class="text-xs text-slate-400">Batch Number</p><p class="text-sm text-slate-800 font-mono">${i.batch_number || '—'}</p></div>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t border-slate-200">
                        <button onclick="closeModal('viewImmunizationModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Close</button>
                    </div>
                </div>
            `;
        }, 300);
    }

    // ============================================================
    // RECORD VACCINATION
    // ============================================================
    function recordVaccination(id) {
        const i = IMMUNIZATIONS[id];
        if (!i) return;
        
        // Pre-fill form with data
        document.getElementById('vacc_child').value = i.child_id || '';
        document.getElementById('vacc_vaccine').value = i.vaccine;
        document.getElementById('vacc_dose').value = i.dose;
        document.getElementById('vacc_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('vacc_next_due').value = i.next_due || '';
        
        openModal('recordVaccinationModal');
    }

    function limitDoseInput(input) {
        input.value = String(input.value || '').replace(/\D/g, '').slice(0, 4);
    }

    function saveVaccinationRecord(event) {
        event.preventDefault();
        const dose = document.getElementById('vacc_dose').value;
        if (!/^\d{1,4}$/.test(dose) || Number(dose) < 1 || Number(dose) > 9999) {
            showToast('Dose number must be between 1 and 9999.', 'warning');
            document.getElementById('vacc_dose').focus();
            return;
        }
        showToast('Vaccination recorded successfully!', 'success');
        closeModal('recordVaccinationModal');
    }

    // ============================================================
    // TOAST NOTIFICATIONS
    // ============================================================
    let toastTimer = null;

    function showToast(message, type = 'success') {
        const toast = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-rose-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        toast.className = 'fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ' + (colors[type] || colors.success);
        toast.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = message;
        toast.classList.remove('hidden');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.classList.add('hidden'), 4000);
    }

    // ============================================================
    // SEARCH & FILTER
    // ============================================================
    document.getElementById('searchVaccination').addEventListener('input', filterVaccinations);
    document.getElementById('filterStatus').addEventListener('change', filterVaccinations);
    document.getElementById('filterVaccine').addEventListener('change', filterVaccinations);
    document.getElementById('filterDateFrom').addEventListener('change', filterVaccinations);
    document.getElementById('filterDateTo').addEventListener('change', filterVaccinations);

    function filterVaccinations() {
        const search = document.getElementById('searchVaccination').value.trim().toLowerCase();
        const status = document.getElementById('filterStatus').value;
        const vaccine = document.getElementById('filterVaccine').value.toLowerCase();
        const dateFrom = document.getElementById('filterDateFrom').value;
        const dateTo = document.getElementById('filterDateTo').value;
        let visibleCount = 0;

        document.querySelectorAll('.vaccination-row').forEach(row => {
            const child = row.dataset.child;
            const rowVaccine = row.dataset.vaccine;
            const rowStatus = row.dataset.status;
            const rowDate = row.dataset.date || '';
            const rowBatch = row.dataset.batch || '';
            const rowDose = row.dataset.dose || '';

            const matchesSearch = !search || [child, rowVaccine, rowBatch, rowDose]
                .some(value => String(value || '').toLowerCase().includes(search));
            const matchesStatus = !status || 
                (status === 'due_soon' ? isDueSoon(row) : rowStatus === status);
            const matchesVaccine = !vaccine || rowVaccine === vaccine;
            const matchesDateFrom = !dateFrom || (rowDate && rowDate >= dateFrom);
            const matchesDateTo = !dateTo || (rowDate && rowDate <= dateTo);
            const isVisible = matchesSearch && matchesStatus && matchesVaccine && matchesDateFrom && matchesDateTo;

            row.style.display = isVisible ? '' : 'none';
            if (isVisible) visibleCount++;
        });

        document.getElementById('emptyState').style.display = visibleCount === 0 ? 'flex' : 'none';
    }

    function isDueSoon(row) {
        if (!row.dataset.nextDue) return false;
        const dueDate = new Date(row.dataset.nextDue + 'T00:00:00');
        const daysLeft = (dueDate - new Date()) / 86400000;
        return daysLeft >= 0 && daysLeft <= 30;
    }

    function resetFilters() {
        document.getElementById('searchVaccination').value = '';
        document.getElementById('filterStatus').value = '';
        document.getElementById('filterVaccine').value = '';
        document.getElementById('filterDateFrom').value = '';
        document.getElementById('filterDateTo').value = '';
        document.querySelectorAll('.vaccination-row').forEach(row => row.style.display = '');
        document.getElementById('emptyState').style.display = 'none';
    }

    function changePage(page) {
        if (page < 1 || page > <?php echo ceil($totalImmunizations / 10); ?>) return;
        window.location.href = '?page=' + page;
    }

    // ESC to close modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.classList.remove('overflow-hidden');
            });
        }
    });

    // ============================================================
    // SET DEFAULT DATE
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        const dateInput = document.getElementById('vacc_date');
        if (dateInput) {
            dateInput.value = new Date().toISOString().split('T')[0];
        }
        // Set next due default to 1 month later
        const nextDue = document.getElementById('vacc_next_due');
        if (nextDue) {
            const date = new Date();
            date.setMonth(date.getMonth() + 1);
            nextDue.value = date.toISOString().split('T')[0];
        }
    });
</script>

<?php include_once '../../includes/footer.php'; ?>