<?php
if (ob_get_level() === 0) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
    @session_start();
}

require_once __DIR__ . '/../config/paths.php';

// Auto-restore session from active cookie (civentral_remember or civentral_session) if PHP session expired
if (empty($_SESSION['logged_in'])) {
    if (!empty($_COOKIE['civentral_remember'])) {
        require_once __DIR__ . '/../app/services/RememberMeService.php';
        \App\Services\RememberMeService::processAutoLogin();
    }
    if (empty($_SESSION['logged_in']) && !empty($_COOKIE['civentral_session'])) {
        require_once __DIR__ . '/../app/services/SessionAuthService.php';
        $authSvc = new SessionAuthService();
        $authSvc->validateActiveToken($_COOKIE['civentral_session']);
    }
}

// Global Authentication Guard: Ensure user is logged in for all pages including header.php
$allowAnonymous = $allowAnonymous ?? false;
if (!$allowAnonymous && empty($_SESSION['logged_in'])) {
    $_SESSION['flash_error'] = 'Access Denied: Please log in to access the system.';
    $loginUrl = site_url('login.php');
    if (!headers_sent()) {
        header('Location: ' . $loginUrl);
    } else {
        echo "<script>window.location.href = '" . htmlspecialchars($loginUrl, ENT_QUOTES) . "';</script>";
    }
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get user data from session with real-time employee lookup fallback
$currentUserId   = (int)($_SESSION['user_id'] ?? 0);
$fullName        = $_SESSION['full_name'] ?? 'User';
$employeeId      = $_SESSION['employee_id'] ?? '';
$email           = $_SESSION['email'] ?? '';
$contact         = $_SESSION['contact'] ?? ($_SESSION['phone'] ?? '');
$department      = $_SESSION['department'] ?? $_SESSION['user_department'] ?? 'City Health Department';
$role            = $_SESSION['role'] ?? 'Employee';
$roleDescription = $_SESSION['role_description'] ?? 'employee';
$displayRole     = !empty($_SESSION['role']) ? $_SESSION['role'] : (!empty($_SESSION['role_description']) ? $_SESSION['role_description'] : 'Employee');
$userStatus      = $_SESSION['status'] ?? 'Active';

if ($currentUserId > 0) {
    try {
        require_once __DIR__ . '/../app/Models/Employee.php';
        $empModel = new Employee();
        $liveUser = $empModel->find($currentUserId);
        if ($liveUser) {
            $fullName    = $liveUser['full_name'] ?? $fullName;
            $employeeId  = $liveUser['employee_id'] ?? ($liveUser['username'] ?? $employeeId);
            $email       = !empty($liveUser['email']) ? $liveUser['email'] : $email;
            $contact     = !empty($liveUser['contact']) ? $liveUser['contact'] : (!empty($liveUser['phone']) ? $liveUser['phone'] : $contact);
            $department  = !empty($liveUser['department']) ? $liveUser['department'] : $department;
            $userStatus  = !empty($liveUser['status']) ? $liveUser['status'] : $userStatus;
            $displayRole = !empty($liveUser['role_description']) ? $liveUser['role_description'] : (!empty($liveUser['role']) ? $liveUser['role'] : $displayRole);
        }
    } catch (\Throwable $e) {}
}

if (empty($email)) {
    $email = strtolower(preg_replace('/[^a-zA-Z0-9]/', '.', $fullName)) . '@caloocan.gov.ph';
}

// Generate initials from full name (e.g., "Joshua Sierra" -> "JS")
$initials = '';
$nameParts = explode(' ', trim($fullName));
foreach ($nameParts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper($part[0]);
    }
}
$initials = substr($initials, 0, 2); // Get first 2 initials

// Dynamic Assigned Station based on Department
$assignedStation = 'Main Health HQ (Caloocan)';
$dLower = strtolower($department);
if (str_contains($dLower, 'health center') || str_contains($dLower, 'medical')) $assignedStation = 'District 1 Health Center';
elseif (str_contains($dLower, 'sanitation')) $assignedStation = 'Sanitation & Environmental Inspection Unit';
elseif (str_contains($dLower, 'immunization') || str_contains($dLower, 'nutrition')) $assignedStation = 'Maternal & Child Health Center';
elseif (str_contains($dLower, 'waste')) $assignedStation = 'Wastewater Treatment & Septic Services';
elseif (str_contains($dLower, 'surveillance')) $assignedStation = 'Epidemiological Surveillance Unit (CESU)';
elseif (str_contains($dLower, 'admin')) $assignedStation = 'City Administration HQ';

// ------------------------------------------------------------
// Minimal header flag.
// Any page that sets $minimalHeader = true; BEFORE including this
// file (e.g. queue-display.php, since it's shown on a public TV/monitor)
// will hide the admin profile block, notification bell, and the
// data-mask toggle. Every other page is unaffected.
// ------------------------------------------------------------
$minimalHeader = $minimalHeader ?? false;

// Dynamic System Notifications from Database
require_once __DIR__ . '/../app/services/NotificationService.php';
$notificationService = new NotificationService();
$headerNotifications = $notificationService->getNotifications(10);
$initialTotalCount = count($headerNotifications);
$initialUnreadCount = count(array_filter($headerNotifications, fn($n) => empty($n['is_read'])));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
  <title>Civentral<?php if (!empty($pageTitle)) echo ' · ' . htmlspecialchars($pageTitle); ?></title>

  <link rel="icon" type="image/png" href="<?= site_url('assets/images/logo.png'); ?>" title="Civentral">
  <link rel="apple-touch-icon" href="<?= site_url('assets/images/logo.png'); ?>">
  
  <!-- Global App Base URL Configuration -->
  <script>
    window.SITE_URL = "<?= rtrim(site_url(''), '/'); ?>";
    window.API_BASE = "<?= site_url('api/'); ?>";
  </script>

  <!-- Dark Mode Instant Theme Initialization -->
  <script>
    (function() {
      const savedTheme = localStorage.getItem('portal_theme') || 'light';
      if (savedTheme === 'dark' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
      } else {
        document.documentElement.classList.remove('dark');
      }
    })();
  </script>

  <!-- Performance CDN Preconnects -->
  <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
  <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>

  <!-- Tailwind CSS & ApexCharts -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts" defer></script>
  
  <!-- Font Awesome 6 (Latest) - Loaded in head for priority -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  
  <style type="text/tailwindcss">
    @theme {
      --color-brand-light: #EEF5FF;
      --color-brand-border: #B4D4FF;
      --color-brand-medium: #86B6F6;
      --color-brand-dark: #176B87;
      --color-dash-bg: #F9FAFB;
      --color-c1: #B4D4FF;
      --color-c2: #86B6F6;
      --color-c3: #176B87;
      --color-c3d: #0d4f64;
    }
  </style>

  <style>
    /* Ultra-Sleek Matte Dark Mode UI/UX Design System */
    html.dark {
      color-scheme: dark;
    }
    
    /* Matte Base Canvas */
    html.dark body, 
    html.dark main, 
    html.dark div.flex-1 {
      background-color: #0b0f17 !important;
      color: #e2e8f0 !important;
    }

    /* Header & Navigation Bar */
    html.dark header, 
    html.dark aside, 
    html.dark nav {
      background-color: #111726 !important;
      border-color: #1c2638 !important;
    }

    /* Surfaces & Cards - Smooth Dark Matte Surface */
    html.dark .bg-white, 
    html.dark .bg-slate-50, 
    html.dark .bg-zinc-50, 
    html.dark .bg-gray-50,
    html.dark .bg-brand-light,
    html.dark [class*="bg-white"],
    html.dark [class*="bg-slate-50"],
    html.dark [class*="bg-zinc-50"] {
      background-color: #151d2a !important;
      color: #e2e8f0 !important;
      border-color: #212c40 !important;
      box-shadow: none !important;
    }

    /* Soft Subdued Light-Tinted Boxes (No Glare / No Bright Pastels) */
    html.dark .bg-indigo-50, html.dark .bg-indigo-50\/60 { background-color: rgba(99, 102, 241, 0.08) !important; color: #a5b4fc !important; border-color: rgba(99, 102, 241, 0.2) !important; }
    html.dark .bg-blue-50, html.dark .bg-blue-50\/60 { background-color: rgba(59, 130, 246, 0.08) !important; color: #93c5fd !important; border-color: rgba(59, 130, 246, 0.2) !important; }
    html.dark .bg-emerald-50, html.dark .bg-emerald-50\/60 { background-color: rgba(16, 185, 129, 0.08) !important; color: #6ee7b7 !important; border-color: rgba(16, 185, 129, 0.2) !important; }
    html.dark .bg-purple-50, html.dark .bg-purple-50\/60 { background-color: rgba(168, 85, 247, 0.08) !important; color: #c084fc !important; border-color: rgba(168, 85, 247, 0.2) !important; }
    html.dark .bg-amber-50, html.dark .bg-amber-50\/60 { background-color: rgba(245, 158, 11, 0.08) !important; color: #fcd34d !important; border-color: rgba(245, 158, 11, 0.2) !important; }
    html.dark .bg-teal-50, html.dark .bg-teal-50\/60 { background-color: rgba(20, 184, 166, 0.08) !important; color: #5eead4 !important; border-color: rgba(20, 184, 166, 0.2) !important; }
    html.dark .bg-rose-50, html.dark .bg-rose-50\/60 { background-color: rgba(244, 63, 94, 0.08) !important; color: #fda4af !important; border-color: rgba(244, 63, 94, 0.2) !important; }

    /* Soft Subtle Borders (Zero Glare) */
    html.dark .border-slate-100, 
    html.dark .border-slate-200, 
    html.dark .border-zinc-100, 
    html.dark .border-zinc-200,
    html.dark .border-brand-border,
    html.dark .border-gray-200,
    html.dark .border-indigo-100,
    html.dark .border-blue-100 {
      border-color: #212c40 !important;
    }

    /* Comfortable Eye-Care Typography */
    html.dark .text-slate-900, 
    html.dark .text-slate-800, 
    html.dark .text-zinc-900, 
    html.dark .text-zinc-800,
    html.dark .text-gray-900,
    html.dark .text-black {
      color: #f1f5f9 !important; /* Soft reading off-white */
    }
    html.dark .text-slate-700, 
    html.dark .text-zinc-700,
    html.dark .text-gray-700 {
      color: #cbd5e1 !important;
    }
    html.dark .text-slate-600, 
    html.dark .text-slate-500, 
    html.dark .text-zinc-600, 
    html.dark .text-zinc-500, 
    html.dark .text-zinc-400,
    html.dark .text-gray-600,
    html.dark .text-gray-500 {
      color: #94a3b8 !important;
    }

    /* Form Controls & Inputs */
    html.dark input, 
    html.dark select, 
    html.dark textarea {
      background-color: #0e1524 !important;
      color: #f1f5f9 !important;
      border-color: #26354b !important;
    }
    html.dark select option {
      background-color: #0e1524 !important;
      color: #f1f5f9 !important;
    }
    html.dark input::placeholder, html.dark textarea::placeholder {
      color: #64748b !important;
    }

    /* Interactive Hover States */
    html.dark .bg-slate-100, 
    html.dark .bg-slate-200\/60,
    html.dark .bg-zinc-100,
    html.dark .hover\:bg-slate-50:hover,
    html.dark .hover\:bg-slate-100:hover,
    html.dark .hover\:bg-white\/60:hover {
      background-color: #212d42 !important;
      color: #f1f5f9 !important;
    }

    /* Clean Dark Tables */
    html.dark table {
      border-color: #212c40 !important;
    }
    html.dark thead, html.dark th {
      background-color: #151d2a !important;
      color: #94a3b8 !important;
      border-color: #212c40 !important;
    }
    html.dark tbody tr, html.dark td {
      background-color: #0b0f17 !important;
      color: #cbd5e1 !important;
      border-color: #212c40 !important;
    }
    html.dark tbody tr:hover {
      background-color: #151d2a !important;
    }

    /* Modals & Dropdowns - Elevated Dark Layers */
    html.dark #profileDropdown,
    html.dark #notificationDropdown,
    html.dark #personalSettingsModal .bg-white,
    html.dark #userProfileModal .bg-white,
    html.dark .popup {
      background-color: #151d2a !important;
      border-color: #26354b !important;
      color: #f1f5f9 !important;
      box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.7) !important;
    }

    /* ApexCharts Dark Mode Tune */
    html.dark .apexcharts-canvas text {
      fill: #94a3b8 !important;
    }
    html.dark .apexcharts-title-text, html.dark .apexcharts-subtitle-text {
      fill: #f1f5f9 !important;
    }
    html.dark .apexcharts-gridline {
      stroke: #212c40 !important;
    }
    html.dark .apexcharts-tooltip {
      background: #151d2a !important;
      border-color: #26354b !important;
      color: #f1f5f9 !important;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.5) !important;
    }
    html.dark .apexcharts-tooltip-title {
      background: #0e1524 !important;
      border-color: #26354b !important;
      color: #f1f5f9 !important;
    }
  </style>
  
  <!-- Your custom styles -->
  <link rel="stylesheet" href="<?= site_url('assets/css/dashb-style.css'); ?>">
  <!-- Common JS Utilities -->
  <script src="<?= site_url('assets/js/common.js'); ?>"></script>
  <!-- Offline Transaction Queue & Auto-Sync -->
  <script src="<?= site_url('assets/js/offline-sync.js'); ?>"></script>
  <link rel="manifest" href="<?= site_url('manifest.json'); ?>">
</head>
<?php if (!$minimalHeader) include_once __DIR__ . '/data-mask.php'; ?>
<body class="bg-white font-sans antialiased text-slate-800 min-h-screen flex flex-col">

<!-- PWA Install Prompt -->
<div id="pwa-install-prompt" class="hidden fixed bottom-4 right-4 bg-white border border-slate-200 shadow-xl rounded-xl p-4 flex flex-col sm:flex-row items-center gap-4 z-50">
  <div class="flex items-center gap-3">
    <img src="<?= site_url('assets/images/logo.png'); ?>" alt="App Logo" class="h-10 w-10 object-contain">
    <div class="flex flex-col">
      <span class="text-sm font-bold text-slate-800">Install Civentral</span>
      <span class="text-xs text-slate-500">Add to home screen for offline access</span>
    </div>
  </div>
  <div class="flex items-center gap-2 w-full sm:w-auto">
    <button id="pwa-install-btn" class="flex-1 sm:flex-none bg-brand-medium text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-brand-dark transition-colors">Install App</button>
    <button id="pwa-dismiss-btn" class="px-3 py-2 text-slate-400 hover:text-slate-600 transition-colors"><i class="fa-solid fa-xmark"></i></button>
  </div>
</div>

  <header class="bg-white border-b border-slate-200 h-20 px-6 flex items-center justify-between sticky top-0 z-40 shadow-xs shrink-0">
    <div class="flex items-center space-x-4 text-brand-dark">
        <div class="shrink-0 flex items-center justify-center">
          <img src="<?= site_url('assets/images/logo.png'); ?>" alt="Logo" class="h-16 w-auto object-contain">
        </div>
    <div class="flex flex-col">
      <span class="text-base font-black tracking-[0.15em] uppercase leading-none">CIVENTRAL</span>
      <span class="text-[9px] font-bold text-brand-medium tracking-widest uppercase mt-1">Caloocan Portal</span>
    </div>
  </div>

    <?php if (!$minimalHeader): ?>
    <div class="flex items-center space-x-3">
      
      <div class="hidden md:flex items-center space-x-2 text-slate-500 font-mono text-xs font-semibold">
        <i class="fa-solid fa-calendar-day text-brand-medium"></i>
        <span id="headerClock">Loading System Time...</span>
      </div>
      
      <div class="hidden md:block h-6 w-px bg-slate-200"></div>

      <!-- Offline & Sync Status Indicator -->
      <div id="offlineSyncWidget" onclick="if(typeof CiventralOfflineSync!=='undefined')CiventralOfflineSync.syncNow()" title="Click to synchronize offline transactions" class="hidden items-center gap-2 px-3 py-1.5 rounded-xl border text-xs font-bold transition-all shadow-xs cursor-pointer bg-amber-50 text-amber-700 border-amber-200">
        <span id="offlineIndicatorDot" class="w-2 h-2 rounded-full bg-amber-500"></span>
        <i id="offlineSyncIcon" class="fa-solid fa-cloud-arrow-up text-xs"></i>
        <span id="offlineSyncText">Checking Sync...</span>
      </div>

      <!-- Notification Bell Icon & Dropdown Container -->
      <div class="relative inline-block">
        <button type="button" id="notifBellBtn" onclick="toggleNotificationDropdown(event)" class="relative p-2 text-slate-400 hover:text-brand-dark rounded-lg hover:bg-slate-50 transition focus:outline-none cursor-pointer" title="Alerts & Notifications">
          <i class="fa-solid fa-bell text-lg"></i>
          <span id="notifBadgeCount" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 bg-rose-500 text-white text-[10px] font-extrabold rounded-full flex items-center justify-center ring-2 ring-white shadow-xs leading-none z-10 pointer-events-none <?php echo $initialUnreadCount > 0 ? '' : 'hidden'; ?>"><?php echo $initialUnreadCount > 99 ? '99+' : $initialUnreadCount; ?></span>
        </button>

        <!-- Dropdown Popover Panel -->
        <div id="notificationDropdown" class="hidden absolute right-0 top-full mt-2 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden transition-all duration-200 origin-top-right max-h-[85vh] flex flex-col">
          <!-- Dropdown Header -->
          <div class="px-4 py-3 bg-slate-50/90 border-b border-slate-100 flex items-center justify-between flex-shrink-0">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800">
              <i class="fas fa-bell text-brand-medium text-xs"></i>
              <span>Notifications</span>
              <span id="dropdownNotifBadge" class="px-2 py-0.5 <?php echo $initialUnreadCount > 0 ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[9px] font-extrabold ml-1"><?php echo $initialUnreadCount; ?> New</span>
            </div>
            <button type="button" onclick="markAllNotificationsRead(event)" class="text-[10px] text-brand-medium hover:text-brand-dark font-semibold transition-colors flex items-center gap-1 cursor-pointer">
              <i class="fas fa-check-circle text-[10px]"></i> Mark read
            </button>
          </div>

          <!-- Notification Filter Tabs: All & Unread -->
          <div class="px-3 py-2 bg-slate-50/80 border-b border-slate-100 flex items-center justify-between text-xs font-semibold flex-shrink-0 gap-2">
            <div class="flex items-center gap-1 p-0.5 bg-slate-200/70 rounded-xl w-full">
              <button type="button" id="notifTabUnread" onclick="switchNotifTab('unread', event)"
                      class="flex-1 py-1 px-3 text-[11px] font-bold rounded-lg text-center transition-all cursor-pointer bg-white text-slate-800 shadow-xs">
                Unread <span id="notifTabUnreadCount" class="ml-1 px-1.5 py-0.2 bg-rose-100 text-rose-700 rounded-full text-[9px]"><?php echo $initialUnreadCount; ?></span>
              </button>
              <button type="button" id="notifTabAll" onclick="switchNotifTab('all', event)"
                      class="flex-1 py-1 px-3 text-[11px] font-bold rounded-lg text-center transition-all cursor-pointer text-slate-500 hover:text-slate-800">
                All
              </button>
            </div>
          </div>

          <!-- Dropdown Items Feed (Scrollable with optional No-Limit view) -->
          <div id="notifItemsContainer" class="overflow-y-auto divide-y divide-slate-100 custom-scroll flex-1 transition-all duration-300" style="max-height: 280px; overflow-y: auto; -webkit-overflow-scrolling: touch;">
            
            <!-- Empty State -->
            <div id="notifEmptyState" class="hidden p-6 text-center text-slate-400">
              <i class="fas fa-bell-slash text-2xl text-slate-300 mb-2"></i>
              <p class="text-xs font-semibold">No notifications found in this view</p>
            </div>

            <?php if (!empty($headerNotifications)): ?>
              <?php foreach ($headerNotifications as $n): ?>
                <?php $isRead = !empty($n['is_read']); ?>
                <a href="<?php echo htmlspecialchars($n['url']); ?>"
                   data-notif-id="<?php echo htmlspecialchars($n['id']); ?>"
                   data-notif-status="<?php echo $isRead ? 'read' : 'unread'; ?>"
                   class="notif-item p-3 hover:bg-slate-50/80 transition flex items-start gap-2.5 block relative group">
                  <?php if (!$isRead): ?>
                    <span class="unread-dot w-2 h-2 rounded-full bg-rose-500 absolute top-3.5 right-3"></span>
                  <?php endif; ?>
                  <div class="w-7 h-7 rounded-full <?php echo htmlspecialchars($n['icon_bg']); ?> flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="<?php echo htmlspecialchars($n['icon']); ?> <?php echo htmlspecialchars($n['icon_color']); ?> text-xs"></i>
                  </div>
                  <div class="flex-1 min-w-0 pr-3">
                    <div class="flex items-center justify-between">
                      <p class="text-xs font-bold <?php echo htmlspecialchars($n['title_color']); ?> truncate"><?php echo htmlspecialchars($n['title']); ?></p>
                      <span class="text-[9px] text-slate-400 flex-shrink-0 ml-1"><?php echo htmlspecialchars($n['time']); ?></span>
                    </div>
                    <p class="text-[10px] text-slate-600 mt-0.5 line-clamp-2"><?php echo htmlspecialchars($n['message']); ?></p>
                  </div>
                </a>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="p-6 text-center text-slate-400">
                <i class="fas fa-bell-slash text-2xl text-slate-300 mb-2"></i>
                <p class="text-xs font-semibold text-slate-600">No active notifications</p>
                <p class="text-[10px] text-slate-400 mt-0.5">You're all caught up! Real-time alerts will appear here.</p>
              </div>
            <?php endif; ?>

          </div>

          <!-- Dropdown Footer with "See previous notification" -->
          <div class="px-3 py-2 bg-slate-50 border-t border-slate-100 text-center flex-shrink-0">
            <button type="button" id="seePreviousNotifBtn" onclick="toggleSeePreviousNotifications(event)" class="w-full py-1.5 px-3 text-xs font-bold text-brand-dark hover:text-brand-medium hover:bg-slate-100 transition rounded-xl flex items-center justify-center gap-1.5 cursor-pointer">
              <i class="fas fa-history text-xs"></i>
              <span id="seePreviousNotifText">See previous notification</span>
            </button>
          </div>
        </div>
      </div>

      <!-- User Profile & Personal Settings Dropdown Container -->
      <div class="relative inline-block">
        <button type="button" id="userProfileBtn" onclick="toggleProfileDropdown(event)"
                class="flex items-center space-x-2 p-1.5 rounded-xl hover:bg-slate-100/80 transition-all duration-200 text-left select-none focus:outline-none cursor-pointer border border-transparent hover:border-slate-200">
          <div class="h-8 w-8 rounded-lg bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-extrabold text-xs shadow-xs">
            <?php echo htmlspecialchars($initials); ?>
          </div>
          <div class="hidden sm:flex flex-col">
            <span class="text-xs font-bold text-slate-700 leading-none flex items-center gap-1">
              <span id="headerUserFullName"><?php echo htmlspecialchars($fullName); ?></span>
              <i class="fas fa-chevron-down text-[9px] text-slate-400"></i>
            </span>
            <span class="text-[9px] font-bold text-slate-400 uppercase mt-0.5 tracking-wider"><?php echo htmlspecialchars($displayRole); ?></span>
          </div>
        </button>

        <!-- Profile & Settings Dropdown Menu -->
        <div id="profileDropdown" class="hidden absolute right-0 top-full mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-slate-100 z-50 overflow-hidden transition-all duration-200 origin-top-right">
          
          <!-- Profile Card Header -->
          <div class="p-3.5 bg-slate-50/80 border-b border-slate-100 flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl bg-c3 text-white flex items-center justify-center font-extrabold text-sm shadow-sm flex-shrink-0">
              <?php echo htmlspecialchars($initials); ?>
            </div>
            <div class="min-w-0 flex-1">
              <p id="dropdownUserFullName" class="text-xs font-bold text-slate-800 truncate"><?php echo htmlspecialchars($fullName); ?></p>
              <p class="text-[10px] font-medium text-slate-500 truncate"><?php echo htmlspecialchars($email); ?></p>
              <span class="inline-block mt-1 px-2 py-0.5 bg-c3/10 text-c3 rounded-md text-[8px] font-extrabold uppercase tracking-wide">
                <?php echo htmlspecialchars($displayRole); ?>
              </span>
            </div>
          </div>

          <!-- Menu Links -->
          <div class="py-1.5">
            <!-- View My Profile -->
            <button type="button" onclick="openUserProfileModal()" class="w-full px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-c3 flex items-center gap-2.5 transition text-left cursor-pointer">
              <div class="w-6 h-6 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0 text-xs">
                <i class="fas fa-user-circle"></i>
              </div>
              <span>View My Profile</span>
            </button>

            <!-- Personal Settings -->
            <button type="button" onclick="openPersonalSettingsModal()" class="w-full px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 hover:text-c3 flex items-center gap-2.5 transition text-left cursor-pointer">
              <div class="w-6 h-6 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0 text-xs">
                <i class="fas fa-sliders"></i>
              </div>
              <span>Personal Settings</span>
            </button>
          </div>

          <div class="border-t border-slate-100 my-0.5"></div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </header>

  <!-- ============================================================ -->
  <!-- VIEW MY PROFILE MODAL                                       -->
  <!-- ============================================================ -->
  <div id="userProfileModal" class="fixed inset-0 z-[999] hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-md overflow-hidden flex flex-col animate-scaleUp">
      <!-- Header -->
      <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
          <div class="w-8 h-8 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center">
            <i class="fas fa-id-badge text-sm"></i>
          </div>
          <span>Staff Profile Details</span>
        </div>
        <button type="button" onclick="closeUserProfileModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
          <i class="fas fa-times text-sm"></i>
        </button>
      </div>
      <!-- Profile Card Body -->
      <div class="p-5 space-y-4">
        <div class="flex items-center gap-4 bg-blue-50/60 p-3.5 rounded-2xl border border-blue-100/80">
          <div class="w-14 h-14 rounded-2xl bg-c3 text-white flex items-center justify-center font-extrabold text-xl shadow-md flex-shrink-0">
            <?php echo htmlspecialchars($initials); ?>
          </div>
          <div class="min-w-0 flex-1">
            <h3 class="text-sm font-bold text-slate-800 truncate"><?php echo htmlspecialchars($fullName); ?></h3>
            <p class="text-xs font-semibold text-c3"><?php echo htmlspecialchars($displayRole); ?></p>
            <p class="text-[10px] text-slate-500 mt-0.5">Emp ID: <span class="font-mono font-bold text-slate-700"><?php echo htmlspecialchars($employeeId ?: ('EMP-' . str_pad((string)$currentUserId, 4, '0', STR_PAD_LEFT))); ?></span></p>
          </div>
        </div>

        <!-- Detail Fields -->
        <div class="space-y-2.5 text-xs">
          <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
            <span class="text-slate-500 font-semibold flex items-center gap-2">
              <i class="fas fa-envelope text-slate-400"></i> Email Address
            </span>
            <span class="font-bold text-slate-800"><?php echo htmlspecialchars($email); ?></span>
          </div>

          <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
            <span class="text-slate-500 font-semibold flex items-center gap-2">
              <i class="fas fa-building text-slate-400"></i> Department / Division
            </span>
            <span class="font-bold text-slate-800"><?php echo htmlspecialchars(!empty($department) ? $department : 'City Health Department'); ?></span>
          </div>

          <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
            <span class="text-slate-500 font-semibold flex items-center gap-2">
              <i class="fas fa-location-dot text-slate-400"></i> Assigned Station
            </span>
            <span class="font-bold text-slate-800"><?php echo htmlspecialchars($assignedStation); ?></span>
          </div>

          <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
            <span class="text-slate-500 font-semibold flex items-center gap-2">
              <i class="fas fa-phone text-slate-400"></i> Contact Phone
            </span>
            <span class="font-bold text-slate-800"><?php echo htmlspecialchars(!empty($contact) ? $contact : 'Not specified'); ?></span>
          </div>

          <div class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl">
            <span class="text-slate-500 font-semibold flex items-center gap-2">
              <i class="fas fa-shield-check text-slate-400"></i> Security Clearance
            </span>
            <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-md text-[9px] font-extrabold"><?php echo htmlspecialchars($userStatus === 'Active' ? 'Active ' . $displayRole : $userStatus); ?></span>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="px-5 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs">
        <button type="button" onclick="closeUserProfileModal(); openPersonalSettingsModal();" class="text-c3 hover:underline font-bold flex items-center gap-1">
          <i class="fas fa-sliders text-[10px]"></i> Edit Personal Settings
        </button>
        <button type="button" onclick="closeUserProfileModal()" class="px-4 py-1.5 bg-slate-200 text-slate-700 font-bold rounded-xl hover:bg-slate-300 transition cursor-pointer">
          Close
        </button>
      </div>
    </div>
  </div>

  <!-- ============================================================ -->
  <!-- PERSONAL SETTINGS MODAL                                      -->
  <!-- ============================================================ -->
  <div id="personalSettingsModal" class="fixed inset-0 z-[999] hidden overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4 transition-all duration-300">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-100 w-full max-w-lg overflow-hidden flex flex-col animate-scaleUp">
      <!-- Header -->
      <div class="px-5 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
        <div class="flex items-center gap-2 text-sm font-bold text-slate-800">
          <div class="w-8 h-8 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center">
            <i class="fas fa-sliders text-sm"></i>
          </div>
          <span>Personal Preferences &amp; Settings</span>
        </div>
        <button type="button" onclick="closePersonalSettingsModal()" class="w-7 h-7 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-200/60 flex items-center justify-center transition cursor-pointer">
          <i class="fas fa-times text-sm"></i>
        </button>
      </div>

      <!-- Body Form -->
      <form id="personalSettingsForm" onsubmit="handlePersonalSettingsSave(event)" class="p-5 space-y-4 text-xs">
        
        <!-- Display & Account Preferences -->
        <div class="space-y-2">
          <h4 class="font-extrabold text-slate-800 uppercase text-[10px] tracking-wider text-slate-400 flex items-center gap-1.5">
            <i class="fas fa-user-gear"></i> Account Display Info
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="settingsDisplayName" class="block font-bold text-slate-700 mb-1">Display Name</label>
              <input type="text" id="settingsDisplayName" value="<?php echo htmlspecialchars($fullName); ?>"
                     class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-c3 outline-none transition" />
            </div>
            <div>
              <label for="settingsContact" class="block font-bold text-slate-700 mb-1">Contact Phone</label>
              <input type="text" id="settingsContact" value="<?php echo htmlspecialchars($contact ?: '+63 917 000 0000'); ?>"
                     class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-c3 outline-none transition" />
            </div>
          </div>
          <div class="flex items-center gap-2 pt-1">
            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-bold">
              ID: <?php echo htmlspecialchars($employeeId ?: ('EMP-' . $currentUserId)); ?>
            </span>
            <span class="px-2.5 py-1 bg-c3/10 text-c3 rounded-lg text-[10px] font-bold">
              Role: <?php echo htmlspecialchars($displayRole); ?>
            </span>
            <span class="px-2.5 py-1 bg-slate-100 text-slate-600 rounded-lg text-[10px] font-bold">
              Dept: <?php echo htmlspecialchars($department); ?>
            </span>
          </div>
        </div>

        <!-- Appearance & Dark Mode Settings -->
        <div class="space-y-2 pt-3 border-t border-slate-100">
          <h4 class="font-extrabold text-slate-800 uppercase text-[10px] tracking-wider text-slate-400 flex items-center gap-1.5">
            <i class="fas fa-moon"></i> Appearance &amp; Dark Mode
          </h4>
          
          <div class="grid grid-cols-3 gap-2">
            <button type="button" onclick="applyPortalTheme('light')" id="themeBtnLight"
                    class="p-2.5 rounded-xl border border-slate-200 text-center hover:bg-slate-50 transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700">
              <i class="fas fa-sun text-amber-500 text-base"></i>
              <span>Light Mode</span>
            </button>
            <button type="button" onclick="applyPortalTheme('dark')" id="themeBtnDark"
                    class="p-2.5 rounded-xl border border-slate-200 text-center hover:bg-slate-50 transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700">
              <i class="fas fa-moon text-indigo-600 text-base"></i>
              <span>Dark Mode</span>
            </button>
            <button type="button" onclick="applyPortalTheme('system')" id="themeBtnSystem"
                    class="p-2.5 rounded-xl border border-slate-200 text-center hover:bg-slate-50 transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700">
              <i class="fas fa-desktop text-slate-500 text-base"></i>
              <span>System</span>
            </button>
          </div>

          <label class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100/80 transition mt-2">
            <div class="flex items-center gap-2 font-bold text-slate-700">
              <i class="fas fa-circle-half-stroke text-c3"></i>
              <span>Enable Dark Mode Portal Theme</span>
            </div>
            <input type="checkbox" id="settingsDarkModeToggle" onchange="applyPortalTheme(this.checked ? 'dark' : 'light')" class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3 cursor-pointer" />
          </label>
        </div>

        <!-- Data Privacy & Citizen Confidentiality (Data Masking) -->
        <div class="space-y-2 pt-3 border-t border-slate-100">
          <h4 class="font-extrabold text-slate-800 uppercase text-[10px] tracking-wider text-slate-400 flex items-center gap-1.5">
            <i class="fas fa-shield-halved"></i> Data Privacy &amp; Citizen Confidentiality
          </h4>
          
          <label class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100/80 transition">
            <div class="flex items-center gap-2 font-bold text-slate-700">
              <i id="settingsMaskIcon" class="fa-solid fa-eye-slash text-slate-500"></i>
              <div>
                <span>Mask Citizen Confidential Data</span>
                <p class="text-[10px] text-slate-400 font-normal">Hides names, contact info, and patient IDs across tables (Shortcut: Ctrl+Shift+D)</p>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <span id="settingsMaskBadge" class="px-2 py-0.5 bg-slate-200 text-slate-700 rounded-md text-[9px] font-extrabold uppercase">Hidden</span>
              <input type="checkbox" id="settingsDataMaskToggle" onchange="if(typeof toggleDataMask==='function')toggleDataMask()" class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3 cursor-pointer" />
            </div>
          </label>
        </div>

        <!-- Portal Display Preferences -->
        <div class="space-y-2 pt-3 border-t border-slate-100">
          <h4 class="font-extrabold text-slate-800 uppercase text-[10px] tracking-wider text-slate-400 flex items-center gap-1.5">
            <i class="fas fa-display"></i> Employee Portal Preferences
          </h4>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label for="settingsDefaultView" class="block font-bold text-slate-700 mb-1">Default Portal Start Page</label>
              <select id="settingsDefaultView" class="w-full border border-slate-200 rounded-xl px-3 py-2 focus:ring-2 focus:ring-c3 outline-none transition bg-white">
                <option value="dashboard.php">Main Dashboard</option>
                <option value="modules/healthservices/consultations.php">Health Center</option>
                <option value="pages/ai_insights.php">AI Analytics</option>
                <option value="management/user_management.php">User Management</option>
              </select>
            </div>
            <div class="flex items-end">
              <label class="w-full flex items-center justify-between p-2 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100 transition border border-slate-200">
                <span class="font-bold text-slate-700 text-[11px]">Compact Grid Layout</span>
                <input type="checkbox" id="settingsCompactView" class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3 cursor-pointer" />
              </label>
            </div>
          </div>
        </div>

        <!-- Staff Notification Preferences -->
        <div class="space-y-2 pt-3 border-t border-slate-100">
          <h4 class="font-extrabold text-slate-800 uppercase text-[10px] tracking-wider text-slate-400 flex items-center gap-1.5">
            <i class="fas fa-bell"></i> Staff Notification Preferences
          </h4>
          
          <div class="space-y-2">
            <label class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100/80 transition">
              <span class="font-bold text-slate-700">Urgent Outbreak Desktop Alerts</span>
              <input type="checkbox" id="settingsNotifOutbreak" checked class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3 cursor-pointer" />
            </label>

            <label class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100/80 transition">
              <span class="font-bold text-slate-700">Sanitation Permit Expiry Email Digest</span>
              <input type="checkbox" id="settingsNotifExpiry" checked class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3 cursor-pointer" />
            </label>

            <label class="flex items-center justify-between p-2.5 bg-slate-50 rounded-xl cursor-pointer hover:bg-slate-100/80 transition">
              <span class="font-bold text-slate-700">Realtime Notification Chime</span>
              <input type="checkbox" id="settingsNotifSound" class="w-4 h-4 text-c3 rounded border-slate-300 focus:ring-c3 cursor-pointer" />
            </label>
          </div>
        </div>

        <!-- Footer Actions -->
        <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
          <button type="button" onclick="closePersonalSettingsModal()"
                  class="px-4 py-2 bg-slate-100 text-slate-600 font-semibold rounded-xl hover:bg-slate-200 transition cursor-pointer">
            Cancel
          </button>
          <button type="submit"
                  class="px-4 py-2 bg-c3 hover:bg-c3d text-white font-bold rounded-xl shadow-md transition cursor-pointer flex items-center gap-1.5">
            <i class="fas fa-save"></i> Save Settings
          </button>
        </div>
      </form>
    </div>
  </div>

  <script>
  function applyPortalTheme(mode) {
    localStorage.setItem('portal_theme', mode);
    const isDark = (mode === 'dark') || (mode === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
    
    if (isDark) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }

    const toggleBtn = document.getElementById('settingsDarkModeToggle');
    if (toggleBtn) {
      toggleBtn.checked = isDark;
    }

    const btnLight = document.getElementById('themeBtnLight');
    const btnDark = document.getElementById('themeBtnDark');
    const btnSys = document.getElementById('themeBtnSystem');

    const activeStyle = 'border-c3 bg-c3/10 ring-2 ring-c3/30';
    const inactiveStyle = 'border-slate-200';

    if (btnLight) btnLight.className = mode === 'light' ? `p-2.5 rounded-xl border text-center transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700 ${activeStyle}` : `p-2.5 rounded-xl border text-center hover:bg-slate-50 transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700 ${inactiveStyle}`;
    if (btnDark) btnDark.className = mode === 'dark' ? `p-2.5 rounded-xl border text-center transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700 ${activeStyle}` : `p-2.5 rounded-xl border text-center hover:bg-slate-50 transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700 ${inactiveStyle}`;
    if (btnSys) btnSys.className = mode === 'system' ? `p-2.5 rounded-xl border text-center transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700 ${activeStyle}` : `p-2.5 rounded-xl border text-center hover:bg-slate-50 transition cursor-pointer flex flex-col items-center gap-1 font-bold text-slate-700 ${inactiveStyle}`;
  }

  function toggleNotificationDropdown(e) {
    if (e) e.stopPropagation();
    const profileDd = document.getElementById('profileDropdown');
    if (profileDd) profileDd.classList.add('hidden');

    const dd = document.getElementById('notificationDropdown');
    if (dd) {
      dd.classList.toggle('hidden');
    }
  }

  function toggleProfileDropdown(e) {
    if (e) e.stopPropagation();
    const notifDd = document.getElementById('notificationDropdown');
    if (notifDd) notifDd.classList.add('hidden');

    const profileDd = document.getElementById('profileDropdown');
    if (profileDd) {
      profileDd.classList.toggle('hidden');
    }
  }

  function openUserProfileModal() {
    const profileDd = document.getElementById('profileDropdown');
    if (profileDd) profileDd.classList.add('hidden');
    const modal = document.getElementById('userProfileModal');
    if (modal) {
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
  }

  function closeUserProfileModal() {
    const modal = document.getElementById('userProfileModal');
    if (modal) {
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }
  }

  function openPersonalSettingsModal() {
    const profileDd = document.getElementById('profileDropdown');
    if (profileDd) profileDd.classList.add('hidden');

    const currentTheme = localStorage.getItem('portal_theme') || 'light';
    applyPortalTheme(currentTheme);

    const savedName = localStorage.getItem('user_display_name');
    if (savedName) {
      const nameInput = document.getElementById('settingsDisplayName');
      if (nameInput) nameInput.value = savedName;
    }

    if (typeof updateMaskToggleButton === 'function') {
      updateMaskToggleButton();
    }

    const modal = document.getElementById('personalSettingsModal');
    if (modal) {
      modal.classList.remove('hidden');
      document.body.style.overflow = 'hidden';
    }
  }

  function closePersonalSettingsModal() {
    const modal = document.getElementById('personalSettingsModal');
    if (modal) {
      modal.classList.add('hidden');
      document.body.style.overflow = '';
    }
  }

  function handlePersonalSettingsSave(e) {
    e.preventDefault();

    const displayName = document.getElementById('settingsDisplayName').value.trim();
    if (displayName) {
      localStorage.setItem('user_display_name', displayName);
      const nameEls = document.querySelectorAll('#headerUserFullName, #dropdownUserFullName');
      nameEls.forEach(el => el.textContent = displayName);
    }

    closePersonalSettingsModal();
    if (typeof toast !== 'undefined') {
      toast.success('Personal preferences updated successfully!', { title: 'Settings' });
    }
  }

  let currentNotifFilter = 'unread';
  let isNoLimitNotifView = false;

  function switchNotifTab(filter, e) {
    if (e) e.stopPropagation();
    currentNotifFilter = filter;

    const btnAll = document.getElementById('notifTabAll');
    const btnUnread = document.getElementById('notifTabUnread');

    if (filter === 'all') {
      if (btnAll) btnAll.className = 'flex-1 py-1 px-3 text-[11px] font-bold rounded-lg text-center transition-all cursor-pointer bg-white text-slate-800 shadow-xs';
      if (btnUnread) btnUnread.className = 'flex-1 py-1 px-3 text-[11px] font-bold rounded-lg text-center transition-all cursor-pointer text-slate-500 hover:text-slate-800';
    } else {
      if (btnUnread) btnUnread.className = 'flex-1 py-1 px-3 text-[11px] font-bold rounded-lg text-center transition-all cursor-pointer bg-white text-slate-800 shadow-xs';
      if (btnAll) btnAll.className = 'flex-1 py-1 px-3 text-[11px] font-bold rounded-lg text-center transition-all cursor-pointer text-slate-500 hover:text-slate-800';
    }

    applyNotifFilter();
  }

  function applyNotifFilter() {
    const items = document.querySelectorAll('.notif-item');
    let visibleCount = 0;

    items.forEach(item => {
      const status = item.getAttribute('data-notif-status');
      if (currentNotifFilter === 'unread') {
        if (status === 'unread') {
          item.style.display = '';
          visibleCount++;
        } else {
          item.style.display = 'none';
        }
      } else {
        item.style.display = '';
        visibleCount++;
      }
    });

    const emptyState = document.getElementById('notifEmptyState');
    if (emptyState) {
      if (items.length > 0 && visibleCount === 0) {
        emptyState.innerHTML = `
          <i class="fas fa-check-circle text-2xl text-emerald-400 mb-2"></i>
          <p class="text-xs font-semibold text-slate-600">No unread notifications</p>
          <p class="text-[10px] text-slate-400 mt-0.5">All notifications have been marked as read.</p>
        `;
        emptyState.style.display = 'block';
      } else if (items.length === 0) {
        emptyState.style.display = 'none';
      } else {
        emptyState.style.display = 'none';
      }
    }
  }

  function toggleSeePreviousNotifications(e) {
    if (e) e.stopPropagation();
    const container = document.getElementById('notifItemsContainer');
    const btnText = document.getElementById('seePreviousNotifText');
    const btn = document.getElementById('seePreviousNotifBtn');

    isNoLimitNotifView = !isNoLimitNotifView;

    if (isNoLimitNotifView) {
      if (container) {
        container.style.maxHeight = 'none';
        container.style.overflowY = 'visible';
      }
      if (btnText) btnText.textContent = 'Showing all previous notifications (No limit)';
      if (btn) btn.classList.add('bg-brand-light', 'text-brand-dark');
    } else {
      if (container) {
        container.style.maxHeight = '280px';
        container.style.overflowY = 'auto';
      }
      if (btnText) btnText.textContent = 'See previous notification';
      if (btn) btn.classList.remove('bg-brand-light', 'text-brand-dark');
    }
  }

  function updateNotificationCounts() {
    const unreadItems = document.querySelectorAll('#notifItemsContainer .notif-item[data-notif-status="unread"]');
    const totalItems = document.querySelectorAll('#notifItemsContainer .notif-item');
    const unreadCount = unreadItems.length;
    const totalCount = totalItems.length;

    const badgeCount = document.getElementById('notifBadgeCount');
    const dot = document.getElementById('notifBadgeDot');
    const badge = document.getElementById('dropdownNotifBadge');
    const unreadTabCount = document.getElementById('notifTabUnreadCount');
    const allTabCount = document.getElementById('notifTabAllCount');

    if (badgeCount) {
      if (unreadCount > 0) {
        badgeCount.textContent = unreadCount > 99 ? '99+' : unreadCount;
        badgeCount.classList.remove('hidden');
        badgeCount.style.display = 'flex';
      } else {
        badgeCount.textContent = '0';
        badgeCount.classList.add('hidden');
        badgeCount.style.display = 'none';
      }
    }
    if (dot) {
      if (unreadCount > 0) {
        dot.classList.remove('hidden');
      } else {
        dot.classList.add('hidden');
      }
    }
    if (badge) {
      if (unreadCount > 0) {
        badge.textContent = `${unreadCount} New`;
        badge.className = 'px-2 py-0.5 bg-rose-100 text-rose-700 rounded-full text-[9px] font-extrabold ml-1';
      } else {
        badge.textContent = '0 New';
        badge.className = 'px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[9px] font-extrabold ml-1';
      }
    }
    if (unreadTabCount) {
      unreadTabCount.textContent = unreadCount;
    }
    if (allTabCount) {
      allTabCount.textContent = totalCount;
    }
  }

  function markAllNotificationsRead(e) {
    if (e) e.stopPropagation();
    if (typeof toast !== 'undefined') {
      toast.success('All notifications marked as read');
    }

    const items = document.querySelectorAll('#notifItemsContainer .notif-item');
    const unreadIds = [];

    items.forEach(item => {
      const id = item.getAttribute('data-notif-id');
      const status = item.getAttribute('data-notif-status');
      if (id && status !== 'read') {
        unreadIds.push(id);
      }
      item.setAttribute('data-notif-status', 'read');
      const unreadDot = item.querySelector('.unread-dot');
      if (unreadDot) unreadDot.remove();
    });

    updateNotificationCounts();
    applyNotifFilter();

    // Persist directly to database via API (Zero LocalStorage)
    if (unreadIds.length > 0) {
      fetch('<?php echo site_url('api/notifications.php?action=mark_read'); ?>', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ notification_ids: unreadIds })
      }).catch(err => console.error('Notif DB read sync error:', err));
    }
  }

  // Handle individual notification clicks and persist directly to Database
  document.addEventListener('DOMContentLoaded', function() {
    const notifContainer = document.getElementById('notifItemsContainer');
    if (notifContainer) {
      notifContainer.addEventListener('click', function(e) {
        const item = e.target.closest('.notif-item');
        if (item) {
          const id = item.getAttribute('data-notif-id');
          const currentStatus = item.getAttribute('data-notif-status');
          
          if (id && currentStatus !== 'read') {
            item.setAttribute('data-notif-status', 'read');
            const unreadDot = item.querySelector('.unread-dot');
            if (unreadDot) unreadDot.remove();
            updateNotificationCounts();

            // Persist directly to Database via API (Zero LocalStorage)
            fetch('<?php echo site_url('api/notifications.php?action=mark_read'); ?>', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({ notification_ids: [id] })
            }).catch(err => console.error('Notif DB read sync error:', err));
          }
        }
      });
    }

    updateNotificationCounts();
    applyNotifFilter();
  });

  // Close dropdowns on click outside or ESC key
  document.addEventListener('click', function(e) {
    const dd = document.getElementById('notificationDropdown');
    const btn = document.getElementById('notifBellBtn');
    if (dd && !dd.classList.contains('hidden')) {
      if (!dd.contains(e.target) && (!btn || !btn.contains(e.target))) {
        dd.classList.add('hidden');
      }
    }

    const pDd = document.getElementById('profileDropdown');
    const pBtn = document.getElementById('userProfileBtn');
    if (pDd && !pDd.classList.contains('hidden')) {
      if (!pDd.contains(e.target) && (!pBtn || !pBtn.contains(e.target))) {
        pDd.classList.add('hidden');
      }
    }
  });

  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const dd = document.getElementById('notificationDropdown');
      if (dd) dd.classList.add('hidden');

      const pDd = document.getElementById('profileDropdown');
      if (pDd) pDd.classList.add('hidden');

      if (typeof closeUserProfileModal === 'function') {
        closeUserProfileModal();
      }
      if (typeof closePersonalSettingsModal === 'function') {
        closePersonalSettingsModal();
      }
    }
  });
  </script>

  <div class="flex-1 flex relative">