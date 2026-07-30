<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require_once __DIR__ . '/../config/paths.php';

// Get user data from session
$fullName = $_SESSION['full_name'] ?? 'Joshua Sierra';
$employeeId = $_SESSION['employee_id'] ?? 'SYS--ADMIN-2011';
$role = $_SESSION['role'] ?? 'System Admin';
$roleDescription = $_SESSION['role_description'] ?? 'admin';

// Display actual user role dynamically
$displayRole = !empty($_SESSION['role']) ? $_SESSION['role'] : (!empty($_SESSION['role_description']) ? $_SESSION['role_description'] : 'System Admin');

// Generate initials from full name (e.g., "Joshua Sierra" -> "JS")
$initials = '';
$nameParts = explode(' ', $fullName);
foreach ($nameParts as $part) {
    if (!empty($part)) {
        $initials .= strtoupper($part[0]);
    }
}
$initials = substr($initials, 0, 2); // Get first 2 initials

// ------------------------------------------------------------
// Minimal header flag.
// Any page that sets $minimalHeader = true; BEFORE including this
// file (e.g. queue-display.php, since it's shown on a public TV/monitor)
// will hide the admin profile block, notification bell, and the
// data-mask toggle. Every other page is unaffected.
// ------------------------------------------------------------
$minimalHeader = $minimalHeader ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES); ?>">
  <title>Civentral</title>

  <link rel="icon" type="image/png" href="<?= site_url('assets/images/logo.png'); ?>">
  
  <!-- Tailwind CSS -->
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  
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
  
  <!-- Your custom styles -->
  <link rel="stylesheet" href="<?= site_url('assets/css/dashb-style.css'); ?>">
</head>
<?php if (!$minimalHeader) include_once __DIR__ . '/data-mask.php'; ?>
<body class="bg-white font-sans antialiased text-slate-800 min-h-screen flex flex-col">

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

      <!-- Bell Icon -->
      <button class="relative p-2 text-slate-400 hover:text-brand-dark rounded-lg hover:bg-slate-50 transition focus:outline-none cursor-pointer">
        <i class="fa-solid fa-bell text-lg"></i>
        <span class="absolute top-1.5 right-1.5 h-2 w-2 rounded-full bg-red-500 ring-2 ring-white"></span>
      </button>

      <!-- Data Mask Toggle -->
      <button id="dataMaskToggle" onclick="toggleDataMask()"
              class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-all duration-300 cursor-pointer"
              title="Click to toggle data masking (Ctrl+Shift+M)">
        <i id="maskToggleIcon" class="fa-solid fa-eye-slash text-sm transition-all duration-300"></i>
        <span id="maskToggleLabel" class="hidden sm:inline">Hidden</span>
      </button>

      <div class="h-6 w-px bg-slate-200"></div>

      <div class="flex items-center space-x-2 p-1.5 rounded-lg text-left select-none">
        <div class="h-8 w-8 rounded-lg bg-brand-light border border-brand-border flex items-center justify-center text-brand-dark font-extrabold text-xs">
          <?php echo htmlspecialchars($initials); ?>
        </div>
        <div class="hidden sm:flex flex-col">
          <span class="text-xs font-bold text-slate-700 leading-none"><?php echo htmlspecialchars($fullName); ?></span>
          <span class="text-[9px] font-bold text-slate-400 uppercase mt-0.5 tracking-wider"><?php echo htmlspecialchars($displayRole); ?></span>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </header>

  <div class="flex-1 flex relative">