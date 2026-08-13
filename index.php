<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Civentral</title>
  <link rel="icon" type="image/png" href="assets/images/logo.png">
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <!--  Palette -->
  <style type="text/tailwindcss">
    @theme {
      --color-brand-light: #EEF5FF;
      --color-brand-border: #B4D4FF;
      --color-brand-medium: #86B6F6;
      --color-brand-dark: #176B87;
    }
  </style>
  <!-- fonts -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 font-sans antialiased text-slate-800">

  <!-- Real-time clock shit -->
  <div class="bg-slate-900 text-white text-xs px-4 sm:px-6 py-2.5 flex flex-col sm:flex-row items-center justify-between border-b border-slate-800 gap-2">
    <div class="flex items-center space-x-2 font-mono text-slate-300 text-center sm:text-left">
      <i class="fa-solid fa-calendar-day text-brand-medium"></i>
      <span id="liveClock">Loading Date & Time...</span>
    </div>
    <div class="flex items-center space-x-1 text-[11px] font-bold tracking-wider">
      <button onclick="changeLanguage('en')" id="lang-en" class="text-white hover:text-brand-medium transition cursor-pointer">ENGLISH</button>
      <span class="text-slate-600">|</span>
      <button onclick="changeLanguage('tl')" id="lang-tl" class="text-slate-400 hover:text-brand-medium transition cursor-pointer">TAGALOG</button>
    </div>
  </div>

  <!-- Header -->
  <nav class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-brand-border px-4 sm:px-6 py-4">
    <div class="max-w-7xl mx-auto flex items-center justify-between">
      <div class="flex items-center space-x-3">
        <img src="assets/images/logo.png" alt="Civentral Logo" class="h-10 w-auto object-contain">
        <div class="flex flex-col">
          <span class="text-xl font-black text-brand-dark tracking-wider uppercase leading-none">CIVENTRAL</span>
          <span class="text-[10px] font-bold text-brand-medium tracking-widest uppercase mt-0.5" id="navTagline">Caloocan City Portal</span>
        </div>
      </div>
      
      <!-- For desktop menu -->
      <div class="hidden lg:flex items-center space-x-6">
        <a href="#city-showcase" class="text-sm font-semibold text-slate-600 hover:text-brand-dark transition">Home</a>
        <a href="#leadership" class="text-sm font-semibold text-slate-600 hover:text-brand-dark transition">Officials</a>
        <a href="#features" class="text-sm font-semibold text-slate-600 hover:text-brand-dark transition">Services</a>
        <a href="#announcements" class="text-sm font-semibold text-slate-600 hover:text-brand-dark transition">News & Events</a>
        <a href="#download-app" class="text-sm font-semibold text-slate-600 hover:text-brand-dark transition">Mobile App</a>
      </div>

      <div class="flex items-center space-x-2">
        <a href="login.php" class="hidden lg:inline-flex items-center justify-center px-4 py-2 text-sm font-semibold text-brand-dark border border-brand-medium bg-brand-light hover:bg-brand-medium hover:text-white rounded-lg shadow-xs transition">
          Employee Portal <i class="fa-solid fa-arrow-right-to-bracket ml-2 text-xs"></i>
        </a>
        <!-- Mobile -->
        <button onclick="toggleMobileMenu()" class="lg:hidden text-slate-600 hover:text-brand-dark p-2 focus:outline-none cursor-pointer">
          <i id="menuIcon" class="fa-solid fa-bars text-xl"></i>
        </button>
      </div>
    </div>

    <!-- Dropdown Mobile Menu -->
    <div id="mobileMenu" class="hidden lg:hidden border-t border-slate-100 mt-4 pt-4 flex flex-col space-y-3 px-2">
      <a href="#city-showcase" onclick="toggleMobileMenu()" class="text-sm font-semibold text-slate-600 hover:text-brand-dark py-1">Home</a>
      <a href="#leadership" onclick="toggleMobileMenu()" class="text-sm font-semibold text-slate-600 hover:text-brand-dark py-1">Officials</a>
      <a href="#features" onclick="toggleMobileMenu()" class="text-sm font-semibold text-slate-600 hover:text-brand-dark py-1">Services</a>
      <a href="#announcements" onclick="toggleMobileMenu()" class="text-sm font-semibold text-slate-600 hover:text-brand-dark py-1">News & Events</a>
      <a href="#download-app" onclick="toggleMobileMenu()" class="text-sm font-semibold text-slate-600 hover:text-brand-dark py-1 mb-2">Mobile App</a>
      <a href="login.php" class="w-full text-center py-2.5 text-sm font-semibold text-brand-dark border border-brand-medium bg-brand-light rounded-lg">
        Employee Portal <i class="fa-solid fa-arrow-right-to-bracket ml-1 text-xs"></i>
      </a>
    </div>
  </nav>

  <!-- HERO SEC FOR  -->
  <header id="city-showcase" class="relative bg-slate-900 h-[400px] sm:h-[460px] md:h-[560px] overflow-hidden">
    <div class="absolute inset-0 flex transition-transform duration-1000 ease-in-out" id="carouselTrack">
      <div class="w-full h-full shrink-0 bg-cover bg-center relative bg-[url('assets/images/main-building.jpg')] bg-slate-800">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-transparent"></div>
      </div>
      <div class="w-full h-full shrink-0 bg-cover bg-center relative bg-[url('assets/images/park.jpg')] bg-slate-700">
        <div class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-900/40 to-transparent"></div>
      </div>
    </div>

    <div class="absolute inset-0 z-10 flex items-center justify-center px-6 text-center">
      <div class="max-w-4xl space-y-4 sm:space-y-6">
        <span class="text-[10px] sm:text-xs font-bold tracking-[0.2em] sm:tracking-[0.3em] uppercase text-brand-medium bg-white/10 backdrop-blur-xs px-3 sm:px-4 py-1.5 rounded-full border border-white/20" id="heroBadge">
          Welcome to the Historic City of Caloocan
        </span>
        <h1 class="text-3xl sm:text-5xl lg:text-6xl font-black text-white tracking-tight leading-tight sm:leading-none" id="heroTitle">
          Serving the Citizens with Excellence and Integrity
        </h1>
        <p class="text-xs sm:text-base text-slate-300 max-w-2xl mx-auto leading-relaxed" id="heroSubtitle">
          Discover convenient digital public pathways, community updates, and localized transaction structures managed completely online.
        </p>
        <div class="pt-2 sm:pt-4 flex flex-wrap justify-center gap-3">
          <a href="#features" class="px-4 sm:px-5 py-2.5 sm:py-3 bg-brand-medium text-white font-semibold rounded-lg text-xs sm:text-sm transition shadow-md hover:bg-opacity-90" id="heroBtnPrimary">Explore Services</a>
          <a href="#download-app" class="px-4 sm:px-5 py-2.5 sm:py-3 bg-white/10 hover:bg-white/20 text-white font-semibold border border-white/30 rounded-lg text-xs sm:text-sm transition" id="heroBtnSecondary">Get Mobile App</a>
        </div>
      </div>
    </div>

    <div class="absolute bottom-6 left-0 right-0 z-20 flex justify-center space-x-2">
      <button onclick="setSlide(0)" class="h-2 w-8 bg-white rounded-full transition-all duration-300 id-dot cursor-pointer"></button>
      <button onclick="setSlide(1)" class="h-2 w-2 bg-white/50 rounded-full transition-all duration-300 id-dot cursor-pointer"></button>
    </div>
  </header>

  <!-- Leader -->
  <section id="leadership" class="py-16 sm:py-24 px-4 sm:px-6 max-w-7xl mx-auto space-y-12">
    <div class="text-center space-y-3 max-w-2xl mx-auto">
      <span class="text-xs font-bold uppercase tracking-wider text-brand-medium" id="leaderBadge">City Leadership</span>
      <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" id="leaderTitle">The Local Government Executive Council</h2>
      <p class="text-xs sm:text-sm text-slate-500" id="leaderSubtitle">
        Guiding the sustainable development and growth of Caloocan through dedicated public service.
      </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 max-w-6xl mx-auto">
      <!-- Mayor -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center space-y-4 shadow-xs">
        <div class="h-28 w-28 rounded-full border-2 border-brand-medium mx-auto overflow-hidden bg-slate-100">
          <img src="<?= site_url('assets/images/mayor.jpg'); ?>" alt="Mayor Along Malapitan" class="w-full h-full object-cover object-top">
        </div>
        <div>
          <h4 class="font-extrabold text-slate-900 text-base">Hon. Dale Gonzalo "Along" Malapitan</h4>
          <p class="text-xs font-bold text-brand-dark uppercase tracking-widest mt-0.5" id="titleMayor">City Mayor</p>
        </div>
      </div>
      <!-- Vice Mayor -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center space-y-4 shadow-xs">
        <div class="h-28 w-28 rounded-full border-2 border-brand-medium mx-auto overflow-hidden bg-slate-100">
          <img src="<?= site_url('assets/images/Vice.jpg'); ?>" alt="Vice Mayor Karina Teh" class="w-full h-full object-cover object-top">
        </div>
        <div>
          <h4 class="font-extrabold text-slate-900 text-base">Hon. Karina Teh-Limsico</h4>
          <p class="text-xs font-bold text-brand-dark uppercase tracking-widest mt-0.5" id="titleViceMayor">City Vice Mayor</p>
        </div>
      </div>
      <!-- Sangguniang Panlungsod wala ako malagay -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 text-center space-y-4 shadow-xs">
        <div class="h-24 w-24 rounded-full bg-brand-light border-2 border-brand-medium mx-auto overflow-hidden flex items-center justify-center text-brand-dark">
          <i class="fa-solid fa-users text-3xl"></i>
        </div>
        <div>
          <h4 class="font-extrabold text-slate-900 text-sm sm:text-base" id="titleCouncil">Sangguniang Panlungsod</h4>
          <p class="text-[11px] font-bold text-brand-dark uppercase tracking-widest mt-0.5" id="subtitleCouncil">City Council Members</p>
        </div>
      </div>
    </div>

    <!-- All Officials -->
    <div class="text-center pt-2">
      <a href="officials.html" class="inline-flex items-center space-x-2 text-xs font-bold text-brand-dark bg-white border border-brand-border px-5 py-2.5 rounded-lg shadow-xs hover:bg-brand-light transition">
        <span>View Full Organizational Directory</span>
        <i class="fa-solid fa-chevron-right text-[10px]"></i>
      </a>
    </div>
  </section>

  <!-- Announcement -->
  <section id="announcements" class="py-16 sm:py-20 bg-slate-100 border-y border-slate-200 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto space-y-8 sm:space-y-12">
      <div>
        <span class="text-xs font-bold uppercase tracking-wider text-brand-medium" id="newsBadge">Bulletin Board</span>
        <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" id="newsTitle">Active Circulars & Public Memos</h2>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3 shadow-xs">
          <span class="text-[10px] font-bold bg-blue-100 text-blue-700 px-2 py-0.5 rounded uppercase">Taxation</span>
          <h4 class="font-bold text-slate-900 text-base leading-snug">Business Permit Renewal Automations</h4>
          <p class="text-xs text-slate-500">File clearance registrations and verify municipal updates securely inside your online profile track fields.</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3 shadow-xs">
          <span class="text-[10px] font-bold bg-green-100 text-green-700 px-2 py-0.5 rounded uppercase">Social Aid</span>
          <h4 class="font-bold text-slate-900 text-base leading-snug">AICS Financial Assistance Distributions</h4>
          <p class="text-xs text-slate-500">Verified educational distributions and medical subsidy allocations are open for tracking updates.</p>
        </div>
        <div class="bg-white border border-slate-200 rounded-xl p-5 space-y-3 shadow-xs">
          <span class="text-[10px] font-bold bg-amber-100 text-amber-800 px-2 py-0.5 rounded uppercase">Scholarships</span>
          <h4 class="font-bold text-slate-900 text-base leading-snug">City University Open Scholarship List</h4>
          <p class="text-xs text-slate-500">Application windows are open for new incoming academic terms via the central student tracking module.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Services -->
  <section id="features" class="py-16 sm:py-24 px-4 sm:px-6 max-w-7xl mx-auto space-y-12">
    <div class="text-center space-y-3 max-w-2xl mx-auto">
      <h2 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight" id="servicesTitle">Unified Department Public Services</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      <!-- 1 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-users text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Citizen Identity Registry</h4>
          <p class="text-xs text-slate-500">Population profiling setups and official civic information master databases.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 2 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-file-signature text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Permit Clearance Controls</h4>
          <p class="text-xs text-slate-500">Online assessment processing tracks for structural development and commercial business licensing.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 3 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-hand-holding-heart text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Social Welfare System</h4>
          <p class="text-xs text-slate-500">AICS assistance coordination paths alongside senior citizen support services management.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 4 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-heart-pulse text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Health & Sanitation Management</h4>
          <p class="text-xs text-slate-500">Municipal health center documentation networks and sanitation inspection registry systems.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 5 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-graduation-cap text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Education & Scholarships</h4>
          <p class="text-xs text-slate-500">Application verification portals for students and institutional support asset allocation metrics.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 6 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-triangle-exclamation text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Disaster Risk Reduction (DRRM)</h4>
          <p class="text-xs text-slate-500">Real-time emergency broadcast notification paths and center hazard layout registers.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 7 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-map-location-dot text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Urban Planning & Zoning</h4>
          <p class="text-xs text-slate-500">Zoning assessment structural matrices and infrastructure project development coordination.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 8 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-cash-register text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Treasury & Revenue Processing</h4>
          <p class="text-xs text-slate-500">Digital bookkeeping for tax collection loops, fee payments, and financial invoicing pipelines.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 9 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs sm:col-span-2 lg:col-span-1">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-bus text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Transport & Mobility Control</h4>
          <p class="text-xs text-slate-500">Franchise database registries and municipal traffic regulation tracking structures.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
      <!-- 10 -->
      <div class="bg-white border border-slate-200 rounded-xl p-5 flex flex-col justify-between space-y-4 shadow-xs sm:col-span-2 lg:col-span-2 xl:col-span-1">
        <div class="space-y-2">
          <div class="h-8 w-8 rounded bg-brand-light flex items-center justify-center text-brand-dark"><i class="fa-solid fa-warehouse text-xs"></i></div>
          <h4 class="font-bold text-slate-900 text-sm">Public Asset Management</h4>
          <p class="text-xs text-slate-500">Facility configuration reservation grids and infrastructure inventory tracker setups.</p>
        </div>
        <a href="#download-app" class="w-full text-center py-2 bg-brand-light hover:bg-brand-medium/20 text-brand-dark font-bold text-xs rounded-lg transition border border-brand-border/60">Download App to Apply</a>
      </div>
    </div>
  </section>

  <!-- Mobile app showcase -->
  <section id="download-app" class="py-12 bg-slate-50 px-4 sm:px-6">
    <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
      <div class="md:col-span-8 space-y-3 text-center md:text-left">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-dark bg-brand-light px-3 py-1 rounded-md" id="appBadge">Mobile Platform</span>
        <h3 class="text-2xl font-black text-slate-900 tracking-tight" id="appTitle">Download Citizen Mobile App</h3>
        <p class="text-xs sm:text-sm text-slate-500 leading-relaxed max-w-xl" id="appDesc">
          Gain direct mobile access to local permit tracking fields, emergency notices, and service scheduling pipelines. Scan the security barcode vector to safely pull down the native Android installation package.
        </p>
        <div class="pt-1 flex flex-wrap justify-center md:justify-start gap-2">
          <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5 bg-slate-200 text-slate-600 rounded">APK Build</span>
          <span class="text-[10px] font-mono font-bold uppercase px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded">Verified Secure</span>
        </div>
      </div>
      <div class="md:col-span-4 flex flex-col items-center justify-center space-y-2 shrink-0">
        <div class="p-3 bg-white rounded-2xl shadow-sm border border-slate-200">
          <img src="assets/images/qr.jpg" alt="QR Asset" class="h-28 w-28 object-contain">
        </div>
        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Scan to Install</span>
      </div>
    </div>
  </section>

  <!-- Contact -->
  <section id="contacts-registry" class="py-16 bg-slate-100 border-t border-slate-200 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto space-y-8">
      <div class="space-y-2">
        <span class="text-xs font-bold uppercase tracking-wider text-brand-medium">LGU Communications Desk</span>
        <h3 class="text-2xl font-black text-slate-900 tracking-tight">Direct Department Communications Matrix</h3>
      </div>
      
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <!-- Mayor -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Office of the City Mayor</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-7711</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> mayor@caloocancity.gov.ph</p>
        </div>
        <!-- Vice Mayor -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Office of the Vice Mayor</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-7712</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> vicemayor@caloocancity.gov.ph</p>
        </div>
        <!-- Sangguniang Panlungsod -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Sangguniang Panlungsod</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-8841</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> council@caloocancity.gov.ph</p>
        </div>
        <!-- Registry -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Civil Registry Department</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-1122</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> registry@caloocancity.gov.ph</p>
        </div>
        <!-- BPLO -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Business Permits & Licensing</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-4455</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> bplo@caloocancity.gov.ph</p>
        </div>
        <!-- Welfare -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Social Welfare & Devt (CSWDO)</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-6677</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> cswdo@caloocancity.gov.ph</p>
        </div>
        <!-- Health -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">City Health Department</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-1024</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> health@caloocancity.gov.ph</p>
        </div>
        <!-- Treasury -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">City Treasury Office</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-5512</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> treasury@caloocancity.gov.ph</p>
        </div>
        <!-- Planning -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Urban Planning & Zoning</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-9900</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> planning@caloocancity.gov.ph</p>
        </div>
        <!-- Traffic -->
        <div class="p-4 bg-white border border-slate-200 rounded-xl space-y-1 shadow-xs">
          <h5 class="text-xs font-black text-slate-900 uppercase tracking-wide">Transport & Traffic Management</h5>
          <p class="text-xs font-semibold text-slate-700"><i class="fa-solid fa-phone mr-1.5 opacity-60"></i> (02) 8332-3344</p>
          <p class="text-[11px] text-brand-dark font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> traffic@caloocancity.gov.ph</p>
        </div>
        <!-- DRRMO -->
        <div class="p-4 bg-brand-light/40 border border-brand-border/60 rounded-xl space-y-1 shadow-xs sm:col-span-2">
          <h5 class="text-xs font-black text-red-700 uppercase tracking-wide flex items-center gap-1.5"><i class="fa-solid fa-circle-exclamation text-[10px] animate-pulse"></i> DRRMO Emergency Command Center</h5>
          <p class="text-xs font-bold text-slate-800"><i class="fa-solid fa-phone mr-1.5 text-red-500"></i> Hotline 911 / 8288-2323</p>
          <p class="text-[11px] text-slate-600 font-medium"><i class="fa-solid fa-envelope mr-1.5 opacity-60"></i> drrmo@caloocancity.gov.ph</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Footer -->
  <footer class="bg-brand-dark text-white/80 border-t-4 border-brand-medium pt-16 pb-12 px-4 sm:px-6">
    <div class="max-w-7xl mx-auto space-y-12">
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 border-b border-white/10 pb-12">
        <div class="space-y-4">
          <div class="flex items-center space-x-2 text-white">
            <img src="assets/images/logo.png" alt="Civentral" class="h-8 w-auto object-contain brightness-200">
            <span class="text-lg font-black tracking-wide uppercase">Caloocan City</span>
          </div>
          <p class="text-xs leading-relaxed text-brand-light/70">
            Providing accessible, smart, and comprehensive administrative tracking environments for all municipal districts.
          </p>
        </div>
        <div class="space-y-3">
          <h5 class="text-xs font-bold uppercase text-white tracking-wider">Public Services</h5>
          <ul class="text-xs space-y-2 text-brand-light/70">
            <li><a href="#features" class="hover:text-white transition">Business Registrations</a></li>
            <li><a href="#features" class="hover:text-white transition">Real Property Tax Inquiries</a></li>
            <li><a href="#features" class="hover:text-white transition">Civil Registry Trackers</a></li>
          </ul>
        </div>
        <div class="space-y-3">
          <h5 class="text-xs font-bold uppercase text-white tracking-wider">Government Links</h5>
          <ul class="text-xs space-y-2 text-brand-light/70">
            <li><a href="#" class="hover:text-white transition">Transparency Board</a></li>
            <li><a href="#" class="hover:text-white transition">Municipal Code Regulations</a></li>
            <li><a href="login.php" class="hover:text-white text-brand-medium font-semibold transition">Administrative Login Portal</a></li>
          </ul>
        </div>
        <div class="space-y-3">
          <h5 class="text-xs font-bold uppercase text-white tracking-wider">City Hall Contact</h5>
          <p class="text-xs leading-relaxed text-brand-light/70">
             City Hall Complex, A. Mabini St., <br>
            Caloocan City, Metro Manila, <br>
            Philippines
          </p>
        </div>
      </div>

      <div class="flex flex-col md:flex-row items-center justify-between space-y-6 md:space-y-0 text-center md:text-left">
        <div class="space-y-1 text-xs">
          <p class="text-brand-light font-medium">&copy; 2026 City Government of Caloocan. All Rights Reserved.</p>
          <p class="text-brand-light/50">Regulated under data infrastructure protection parameters.</p>
        </div>
        <div class="text-[10px] font-bold text-slate-800 tracking-wider max-w-sm border border-brand-border/40 rounded-lg p-3 bg-brand-light">
          DEPT ACCESS ONLY - UNAUTHORIZED USE IS LOGGED & PROSECUTABLE UNDER RA 8792
        </div>
      </div>

    </div>
  </footer>

  <script src="assets/js/index.js"></script>
</body>
</html>