<?php
require_once __DIR__ . '/../config/paths.php';

// Get current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['PHP_SELF'];

// Detect which module is active
$activeModule = '';
if (strpos($currentPath, 'modules/healthservices') !== false) {
    $activeModule = 'healthCenter';
} elseif (strpos($currentPath, 'modules/sanitation') !== false) {
    $activeModule = 'sanitation';
} elseif (strpos($currentPath, 'modules/immunization') !== false) {
    $activeModule = 'immunization';
} elseif (strpos($currentPath, 'modules/services') !== false) {
    $activeModule = 'wastewater';
} elseif (strpos($currentPath, 'modules/surveillence') !== false) {
    $activeModule = 'surveillance';
} elseif (strpos($currentPath, 'management/') !== false) {
    $activeModule = 'management';
}

$_sidebarRole = strtolower(trim($_SESSION['role_description'] ?? $_SESSION['role'] ?? ''));
$isDoctorSidebar = (str_contains($_sidebarRole, 'doctor') || str_contains($_sidebarRole, 'physician') || str_contains($_sidebarRole, 'dentist') || str_contains($_sidebarRole, 'medical practitioner'));
?>
<!-- admin/includes/sidebar.php -->
<aside id="sidebar" class="bg-brand-light text-slate-600 w-72 min-h-[calc(100vh-5rem)] flex flex-col justify-between transition-all duration-300 border-r border-brand-border/60 sticky top-20 h-[calc(100vh-5rem)] z-30 shrink-0 shadow-sm">
  
  <div class="flex flex-col h-full overflow-hidden">
    <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto custom-scrollbar">
      
      <!-- Collapse Button -->
      <div class="px-1 pb-3 mb-2 border-b border-brand-border/30">
        <button onclick="toggleSidebar()" class="w-full text-brand-dark/60 hover:text-brand-dark py-2 bg-white/60 hover:bg-white rounded-xl border border-brand-border/30 flex items-center justify-center focus:outline-none transition cursor-pointer" title="Collapse Menu Panel">
          <i id="toggleArrow" class="fa-solid fa-chevron-left text-xs"></i>
        </button>
      </div>

      <!-- ============================================================ -->
      <!-- SECTION 1: MAIN CONTROLS - DIRECT LINKS (NO DROPDOWNS)     -->
      <!-- ============================================================ -->
      
      <span class="sidebar-text text-[9px] font-bold tracking-widest text-slate-400 uppercase block px-3 mb-2">Main Controls</span>
      
      <!-- 1. SYSTEM OVERVIEW / DASHBOARD - Direct Link -->
      <?php if (hasPermission('dashboard.view')): ?>
      <div class="space-y-1">
        <a href="<?= site_url('pages/dashboard.php') ?>"
           class="w-full flex items-center px-3 py-2.5 hover:bg-white/60 hover:text-brand-dark rounded-xl text-xs font-semibold tracking-wide transition group 
           <?php echo (strpos($currentPath, 'dashboard.php') !== false || strpos($currentPath, 'module_activity.php') !== false || strpos($currentPath, 'alerts.php') !== false || strpos($currentPath, 'system_health.php') !== false) ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?>">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-table-columns text-sm <?php echo (strpos($currentPath, 'dashboard.php') !== false || strpos($currentPath, 'module_activity.php') !== false || strpos($currentPath, 'alerts.php') !== false || strpos($currentPath, 'system_health.php') !== false) ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">
              <?= htmlspecialchars(getNavigationService()->getDashboardTitle()) ?>
            </span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <!-- 2. ANALYTICS - Direct Link -->
      <?php if (hasPermission('analytics.view')): ?>
      <div class="space-y-1">
        <a href="<?= site_url('pages/ai_insights.php') ?>"
           class="w-full flex items-center px-3 py-2.5 hover:bg-white/60 hover:text-brand-dark rounded-xl text-xs font-semibold tracking-wide transition group 
           <?php echo (strpos($currentPath, 'ai_insights.php') !== false || strpos($currentPath, 'trend_analysis.php') !== false || strpos($currentPath, 'predictive.php') !== false || strpos($currentPath, 'performance.php') !== false) ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?>">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-chart-line text-sm <?php echo (strpos($currentPath, 'ai_insights.php') !== false || strpos($currentPath, 'trend_analysis.php') !== false || strpos($currentPath, 'predictive.php') !== false || strpos($currentPath, 'performance.php') !== false) ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Analytics</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <!-- 3. REPORTS - Direct Link -->
      <?php if (hasPermission('reports.view')): ?>
      <div class="space-y-1">
        <a href="<?= site_url('pages/custom_report.php') ?>"
           class="w-full flex items-center px-3 py-2.5 hover:bg-white/60 hover:text-brand-dark rounded-xl text-xs font-semibold tracking-wide transition group 
           <?php echo (strpos($currentPath, 'custom_report.php') !== false || strpos($currentPath, 'export.php') !== false || strpos($currentPath, 'report_templates.php') !== false) ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?>">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-file-pen text-sm <?php echo (strpos($currentPath, 'custom_report.php') !== false || strpos($currentPath, 'export.php') !== false || strpos($currentPath, 'report_templates.php') !== false) ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Reports</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <!-- 4. COMPLIANCE & VIOLATIONS - Direct Link -->
      <?php if (hasPermission('compliance.view')): ?>
      <div class="space-y-1">
        <a href="<?= site_url('pages/compliance_monitoring.php') ?>"
           class="w-full flex items-center px-3 py-2.5 hover:bg-white/60 hover:text-brand-dark rounded-xl text-xs font-semibold tracking-wide transition group 
           <?php echo (strpos($currentPath, 'compliance_monitoring.php') !== false || strpos($currentPath, 'violation_tracking.php') !== false || strpos($currentPath, 'corrective_actions.php') !== false || strpos($currentPath, 'regulatory_compliance.php') !== false) ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?>">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-gavel text-sm <?php echo (strpos($currentPath, 'compliance_monitoring.php') !== false || strpos($currentPath, 'violation_tracking.php') !== false || strpos($currentPath, 'corrective_actions.php') !== false || strpos($currentPath, 'regulatory_compliance.php') !== false) ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Compliance & Violations</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <!-- ============================================================ -->
      <!-- SECTION 2: OPERATIONAL MODULES                               -->
      <!-- ============================================================ -->

      <span class="sidebar-text text-[9px] font-bold tracking-widest text-slate-400 uppercase block px-3 mt-6 mb-2">Operational Modules</span>

      <!-- MODULE 1: HEALTH CENTER SERVICES -->
      <?php if (canAccessDepartment('health center services') && (hasPermission('patients.view') || hasPermission('consultations.view') || hasPermission('triage.view') || hasPermission('prescriptions.view'))): ?>
      <div class="space-y-1">
        <button onclick="toggleDropdown('healthCenterDropdown', 'healthCenterChevron')" 
                class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
                <?php echo ($activeModule === 'healthCenter') ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?> cursor-pointer">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-hospital text-sm <?php echo ($activeModule === 'healthCenter') ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Health Center Services</span>
          </div>
          <div class="dropdown-right">
            <i id="healthCenterChevron" class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200"></i>
          </div>
        </button>
        <div id="healthCenterDropdown" class="hidden pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
          
          <?php if (hasPermission('patients.view')): ?>
          <a href="<?= site_url('modules/healthservices/patients.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'patients.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-users text-[10px] opacity-50"></i> 
            <span>Patient Management</span>
          </a>
          <?php endif; ?>

          <?php if (hasPermission('consultations.view')): ?>
          <a href="<?= site_url('modules/healthservices/consultations.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'consultations.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-stethoscope text-[10px] opacity-50"></i> 
            <span><?= $isDoctorSidebar ? 'My Consultations' : 'Consultations' ?></span>
          </a>
          <?php endif; ?>

          <?php if (hasPermission('patients.view')): ?>
          <a href="<?= site_url('modules/healthservices/medical_records.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'medical_records.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-folder text-[10px] opacity-50"></i> 
            <span>Medical Records</span>
          </a>
          <?php endif; ?>

          <?php if (hasPermission('patients.view')): ?>
          <a href="<?= site_url('modules/healthservices/appointments.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'appointments.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-calendar-check text-[10px] opacity-50"></i> 
            <span><?= $isDoctorSidebar ? 'My Appointments' : 'Appointments' ?></span>
          </a>
          <?php endif; ?>

          <?php if (hasPermission('triage.view')): ?>
          <a href="<?= site_url('modules/healthservices/triage.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'triage.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-heart-pulse text-[10px] opacity-50"></i> 
            <span>Patient Assessment</span>
          </a>
          <?php endif; ?>

          <?php if (hasPermission('prescriptions.view')): ?>
          <a href="<?= site_url('modules/healthservices/prescriptions.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'prescriptions.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-prescription-bottle text-[10px] opacity-50"></i> 
            <span>Prescriptions</span>
          </a>
          <?php endif; ?>

          <?php if (hasPermission('patients.view')): ?>
          <a href="<?= site_url('modules/healthservices/referrals.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'referrals.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-arrow-right-arrow-left text-[10px] opacity-50"></i> 
            <span>Referrals</span>
          </a>
          <?php endif; ?>

        </div>
      </div>
      <?php endif; ?>

      <!-- MODULE 2: SANITATION PERMITS -->
      <?php if (canAccessDepartment('sanitation permits') && (hasPermission('permits.view') || hasPermission('inspections.view') || hasPermission('inspections.conduct'))): ?>
      <div class="space-y-1">
        <button onclick="toggleDropdown('sanitationDropdown', 'sanitationChevron')" 
                class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
                <?php echo ($activeModule === 'sanitation') ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?> cursor-pointer">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-clipboard-check text-sm <?php echo ($activeModule === 'sanitation') ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Sanitation Permits</span>
          </div>
          <div class="dropdown-right">
            <i id="sanitationChevron" class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200"></i>
          </div>
        </button>
        <div id="sanitationDropdown" class="hidden pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
          
          <?php if (hasPermission('permits.view')): ?>
          <a href="<?= site_url('modules/sanitation/permit_applications.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'permit_applications.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-file-pen text-[10px] opacity-50"></i> 
            <span>Permit Applications</span>
          </a>
          <?php endif; ?>
          
          <?php if (hasPermission('inspections.view') || hasPermission('inspections.conduct')): ?>
          <a href="<?= site_url('modules/sanitation/inspections.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'inspections.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-search text-[10px] opacity-50"></i> 
            <span>Inspections</span>
          </a>
          <?php endif; ?>
          
          <?php if (hasPermission('permits.view')): ?>
          <a href="<?= site_url('modules/sanitation/permit_records.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'permit_records.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-folder text-[10px] opacity-50"></i> 
            <span>Permit Records</span>
          </a>
          
          <a href="<?= site_url('modules/sanitation/payments.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'payments.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-money-bill-wave text-[10px] opacity-50"></i> 
            <span>Payments</span>
          </a>
          
          <a href="<?= site_url('modules/sanitation/documents.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'documents.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-file text-[10px] opacity-50"></i> 
            <span>Documents</span>
          </a>
          
          <a href="<?= site_url('modules/sanitation/renewals.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'renewals.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-rotate text-[10px] opacity-50"></i> 
            <span>Renewals</span>
          </a>
          <?php endif; ?>

        </div>
      </div>
      <?php endif; ?>

      <!-- MODULE 3: IMMUNIZATION & NUTRITION -->
      <?php if (canAccessDepartment('immunization & nutrition') && (hasPermission('immunization.view') || hasPermission('patients.view') || hasPermission('dashboard.view'))): ?>
      <div class="space-y-1">
        <button onclick="toggleDropdown('immunizationDropdown', 'immunizationChevron')" 
                class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
                <?php echo ($activeModule === 'immunization') ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?> cursor-pointer">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-syringe text-sm <?php echo ($activeModule === 'immunization') ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Immunization & Nutrition</span>
          </div>
          <div class="dropdown-right">
            <i id="immunizationChevron" class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200"></i>
          </div>
        </button>
        <div id="immunizationDropdown" class="hidden pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
          
          <a href="<?= site_url('modules/immunization/child_records.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'child_records.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-child text-[10px] opacity-50"></i> 
            <span>Child Records</span>
          </a>
          
          <a href="<?= site_url('modules/immunization/vaccination_tracking.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'vaccination_tracking.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-vial text-[10px] opacity-50"></i> 
            <span>Vaccination Tracking</span>
          </a>
          
          <a href="<?= site_url('modules/immunization/growth_charts.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'growth_charts.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-chart-line text-[10px] opacity-50"></i> 
            <span>Growth Charts</span>
          </a>
          
          <a href="<?= site_url('modules/immunization/vaccine_inventory.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'vaccine_inventory.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-boxes text-[10px] opacity-50"></i> 
            <span>Vaccine Inventory</span>
          </a>
          
          <a href="<?= site_url('modules/immunization/nutrition_assessment.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'nutrition_assessment.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-apple-alt text-[10px] opacity-50"></i> 
            <span>Nutrition Assessment</span>
          </a>

        </div>
      </div>
      <?php endif; ?>

      <!-- MODULE 4: WASTEWATER SERVICES -->
      <?php if (canAccessDepartment('wastewater services') && (hasPermission('permits.view') || hasPermission('inspections.view'))): ?>
      <div class="space-y-1">
        <button onclick="toggleDropdown('wastewaterDropdown', 'wastewaterChevron')" 
                class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
                <?php echo ($activeModule === 'wastewater') ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?> cursor-pointer">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-droplet text-sm <?php echo ($activeModule === 'wastewater') ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Wastewater Services</span>
          </div>
          <div class="dropdown-right">
            <i id="wastewaterChevron" class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200"></i>
          </div>
        </button>
        <div id="wastewaterDropdown" class="hidden pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
          
          <a href="<?= site_url('modules/services/septic_tanks.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'septic_tanks.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-water text-[10px] opacity-50"></i> 
            <span>Septic Tank Registry</span>
          </a>
          
          <a href="<?= site_url('modules/services/maintenance.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'maintenance.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-wrench text-[10px] opacity-50"></i> 
            <span>Maintenance & Desludging</span>
          </a>
          
          <a href="<?= site_url('modules/services/service_requests.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'service_requests.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-tools text-[10px] opacity-50"></i> 
            <span>Service Requests</span>
          </a>
          
          <a href="<?= site_url('modules/services/providers.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'providers.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-user-cog text-[10px] opacity-50"></i> 
            <span>Service Providers</span>
          </a>
          
          <a href="<?= site_url('modules/services/wastewater_billing.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'wastewater_billing.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-file-invoice text-[10px] opacity-50"></i> 
            <span>Billing</span>
          </a>

        </div>
      </div>
      <?php endif; ?>

      <!-- MODULE 5: HEALTH SURVEILLANCE -->
      <?php if (canAccessDepartment('health surveillance') && (hasPermission('dashboard.view') || hasPermission('reports.view'))): ?>
      <div class="space-y-1">
        <button onclick="toggleDropdown('surveillanceDropdown', 'surveillanceChevron')" 
                class="dropdown-btn w-full flex items-center justify-between px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
                <?php echo ($activeModule === 'surveillance') ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?> cursor-pointer">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-binoculars text-sm <?php echo ($activeModule === 'surveillance') ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Health Surveillance</span>
          </div>
          <div class="dropdown-right">
            <i id="surveillanceChevron" class="fa-solid fa-chevron-down text-[10px] opacity-60 dropdown-chevron transition-transform duration-200"></i>
          </div>
        </button>
        <div id="surveillanceDropdown" class="hidden pl-8 pr-2 space-y-0.5 font-medium sidebar-text">
          
          <a href="<?= site_url('modules/surveillence/case_reports.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'case_reports.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-file-medical text-[10px] opacity-50"></i> 
            <span>Case Reports</span>
          </a>
          
          <a href="<?= site_url('modules/surveillence/mapping.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'mapping.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-map text-[10px] opacity-50"></i> 
            <span>Mapping & Clustering</span>
          </a>
          
          <a href="<?= site_url('modules/surveillence/outbreak_command.php') ?>" class="flex items-center space-x-2 px-3 py-2 text-[11px] rounded-md transition <?php echo (strpos($currentPath, 'outbreak_command.php') !== false || strpos($currentPath, 'outbreak_detection.php') !== false || strpos($currentPath, 'alerts.php') !== false) ? 'bg-brand-light text-brand-dark' : 'text-slate-500 hover:bg-brand-light hover:text-brand-dark'; ?>">
            <i class="fa-solid fa-shield-virus text-[10px] opacity-50"></i> 
            <span>Outbreak Surveillance</span>
          </a>

        </div>
      </div>
      <?php endif; ?>

      <!-- ============================================================ -->
      <!-- SECTION 3: SYSTEM MANAGEMENT                                 -->
      <!-- ============================================================ -->

      <?php if (hasPermission('users.view') || hasPermission('roles.manage') || hasPermission('logs.view') || hasPermission('settings.manage')): ?>
      <span class="sidebar-text text-[9px] font-bold tracking-widest text-slate-400 uppercase block px-3 mt-6 mb-2">System Management</span>

      <!-- User Management -->
      <?php if (hasPermission('users.view') || hasPermission('roles.manage')): ?>
      <div class="space-y-1">
        <a href="<?= site_url('management/user_management.php') ?>"
           class="w-full flex items-center px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
           <?php echo (strpos($currentPath, 'user_management.php') !== false) ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?>">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-users-gear text-sm <?php echo (strpos($currentPath, 'user_management.php') !== false) ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">User Management</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <!-- System Logs -->
      <?php if (hasPermission('logs.view')): ?>
      <div class="space-y-1">
        <a href="<?= site_url('management/system_logs.php') ?>"
           class="w-full flex items-center px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
           <?php echo (strpos($currentPath, 'system_logs.php') !== false) ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?>">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-clock-rotate-left text-sm <?php echo (strpos($currentPath, 'system_logs.php') !== false) ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">System Logs</span>
          </div>
        </a>
      </div>
      <?php endif; ?>

      <!-- Settings -->
      <?php if (hasPermission('settings.manage')): ?>
      <div class="space-y-1">
        <a href="<?= site_url('management/settings.php') ?>"
           class="w-full flex items-center px-3 py-2.5 rounded-xl text-xs font-semibold tracking-wide transition group 
           <?php echo (strpos($currentPath, 'settings.php') !== false) ? 'bg-white/60 text-brand-dark' : 'text-slate-600 hover:bg-white/60 hover:text-brand-dark'; ?>">
          <div class="flex items-center space-x-3">
            <i class="fa-solid fa-gear text-sm <?php echo (strpos($currentPath, 'settings.php') !== false) ? 'text-brand-medium' : 'text-slate-400 group-hover:text-brand-medium'; ?> transition"></i>
            <span class="sidebar-text truncate">Settings</span>
          </div>
        </a>
      </div>
      <?php endif; ?>
      <?php endif; ?>

    </nav>
    
    <!-- Logout -->
    <div class="p-4 border-t border-brand-border/40 shrink-0 bg-white/40">
      <button onclick="confirmLogout('<?= site_url('logout.php') ?>')" class="w-full flex items-center space-x-3 px-3 py-2.5 hover:bg-red-50 hover:text-red-600 text-slate-500 rounded-xl text-xs font-bold tracking-wide transition group cursor-pointer text-left border-0 bg-transparent outline-none">
        <i class="fa-solid fa-arrow-right-from-bracket text-sm"></i>
        <span class="sidebar-text truncate">Logout</span>
      </button>
    </div>
  </div>
</aside>