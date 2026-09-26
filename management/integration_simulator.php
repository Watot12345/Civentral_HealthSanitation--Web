<?php
// management/integration_simulator.php
// Microservices Integration Plan Interactive Simulator (Tier 1 Core Integrations) - Admin & Management Access

require_once '../includes/header.php';
require_once '../includes/sidebar.php';

// Authorization Guard: Admin or Management Role Access
$userScope = getUserScope();
$isSystemAdmin = ($userScope['is_admin'] ?? false)
    || (function_exists('hasPermission') && (hasPermission('settings.manage') || hasPermission('logs.view') || hasPermission(\App\Constants\Permissions::ROLES_MANAGE)))
    || (function_exists('getPermissionService') && (getPermissionService()->isAdminRole($_SESSION['role'] ?? '') || getPermissionService()->isAdminRole($_SESSION['role_description'] ?? '')));

if (!$isSystemAdmin) {
    http_response_code(403);
    $_SESSION['flash_error'] = 'Access Denied: Administrative privileges required to access the Integration Simulator.';
    if (!headers_sent()) {
        header('Location: ' . site_url('pages/dashboard.php'));
    } else {
        echo "<script>window.location.href = '" . site_url('pages/dashboard.php') . "';</script>";
    }
    exit;
}
?>

<div class="p-4 sm:p-6 lg:p-8 bg-slate-50 dark:bg-slate-900 min-h-screen">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="flex items-center space-x-3">
                    <span class="px-3 py-1 text-xs font-semibold uppercase tracking-wider text-teal-700 bg-teal-100 dark:bg-teal-900/40 dark:text-teal-300 rounded-full border border-teal-300 dark:border-teal-800">
                        Admin Capstone Utility
                    </span>
                    <span class="text-xs text-slate-500 dark:text-slate-400">GSMS Architecture v2.0</span>
                </div>
                <h1 class="mt-2 text-2xl sm:text-3xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-network-wired text-teal-600 dark:text-teal-400"></i>
                    Microservices Integration Simulator
                </h1>
                <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                    Live simulation harness for Tier 1 Core Microservice Integrations with Health & Sanitation Management.
                </p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="<?php echo site_url('docs/planning/GSMS_Microservices_Integration_Plan.md'); ?>" target="_blank" 
                   class="inline-flex items-center px-4 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-sm font-medium text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 shadow-sm transition-colors">
                    <i class="fas fa-file-alt mr-2 text-teal-600 dark:text-teal-400"></i>
                    View Integration Plan Doc
                </a>
            </div>
        </div>

        <!-- Universal Master Keys Banner -->
        <div class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center space-x-4">
                <div class="w-10 h-10 rounded-lg bg-teal-100 dark:bg-teal-900/50 flex items-center justify-center text-teal-600 dark:text-teal-400 font-bold text-sm shrink-0">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Citizen Key</p>
                    <p class="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200 truncate">citizen_id</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">e.g. CTZN-PH-2026-008912</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center space-x-4">
                <div class="w-10 h-10 rounded-lg bg-emerald-100 dark:bg-emerald-900/50 flex items-center justify-center text-emerald-600 dark:text-emerald-400 font-bold text-sm shrink-0">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Payment Key</p>
                    <p class="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200 truncate">transaction_id / invoice_number</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">e.g. INV-2026-00412</p>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-800 p-4 rounded-xl border border-slate-200 dark:border-slate-700/80 shadow-sm flex items-center space-x-4">
                <div class="w-10 h-10 rounded-lg bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center text-blue-600 dark:text-blue-400 font-bold text-sm shrink-0">
                    <i class="fas fa-store"></i>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-medium text-slate-500 dark:text-slate-400 uppercase tracking-wider">Business Key</p>
                    <p class="text-sm font-mono font-semibold text-slate-800 dark:text-slate-200 truncate">business_id</p>
                    <p class="text-xs text-slate-500 dark:text-slate-400 truncate">e.g. BIZ-NCR-2026-00452</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="border-b border-slate-200 dark:border-slate-700 mb-6">
        <nav class="flex space-x-4 overflow-x-auto pb-1" aria-label="Tabs">
            <button onclick="switchTab('citizen-tab')" id="tab-btn-citizen-tab"
                    class="sim-tab-btn active px-4 py-2 text-sm font-medium border-b-2 border-teal-600 text-teal-600 dark:text-teal-400 dark:border-teal-400 whitespace-nowrap flex items-center gap-2">
                <i class="fas fa-user-check"></i>
                1. Citizen Master Registry
            </button>
            <button onclick="switchTab('treasury-tab')" id="tab-btn-treasury-tab"
                    class="sim-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 whitespace-nowrap flex items-center gap-2">
                <i class="fas fa-cash-register"></i>
                2. Treasury & Central Payments
            </button>
            <button onclick="switchTab('permits-tab')" id="tab-btn-permits-tab"
                    class="sim-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 whitespace-nowrap flex items-center gap-2">
                <i class="fas fa-certificate"></i>
                3. Business Permits Clearance Check
            </button>
            <button onclick="switchTab('architecture-tab')" id="tab-btn-architecture-tab"
                    class="sim-tab-btn px-4 py-2 text-sm font-medium border-b-2 border-transparent text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 whitespace-nowrap flex items-center gap-2">
                <i class="fas fa-layer-group"></i>
                Scope Matrix & Plan Summary
            </button>
        </nav>
    </div>

    <!-- TAB 1: CITIZEN MASTER REGISTRY -->
    <div id="citizen-tab" class="tab-content">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left Controls -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-database text-teal-600 dark:text-teal-400"></i>
                        Master Citizen Registry Fetch
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Emulates calling <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded text-teal-600 dark:text-teal-300">GET /api/v1/citizens.php?citizen_id=...</code> to auto-populate patient/child registration forms.
                    </p>
                </div>

                <!-- Sample Identifiers -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Preset Test Citizen IDs</label>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="setCitizenId('CTZN-PH-2026-008912')" 
                                class="px-3 py-1.5 text-xs font-mono font-medium rounded-lg bg-teal-50 text-teal-700 dark:bg-teal-900/40 dark:text-teal-300 border border-teal-200 dark:border-teal-800 hover:bg-teal-100 transition-colors">
                            CTZN-PH-2026-008912 (Althea Cruz)
                        </button>
                        <button onclick="setCitizenId('CTZN-PH-2026-001234')" 
                                class="px-3 py-1.5 text-xs font-mono font-medium rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 hover:bg-slate-200 transition-colors">
                            CTZN-PH-2026-001234 (Juan Dela Cruz)
                        </button>
                        <button onclick="setCitizenId('CTZN-PH-2026-005678')" 
                                class="px-3 py-1.5 text-xs font-mono font-medium rounded-lg bg-slate-100 text-slate-700 dark:bg-slate-700 dark:text-slate-300 hover:bg-slate-200 transition-colors">
                            CTZN-PH-2026-005678 (Maria Clara Reyes)
                        </button>
                    </div>
                </div>

                <!-- Input Form -->
                <form id="citizen-sim-form" onsubmit="executeCitizenSim(event)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Citizen ID or Resident QR Code</label>
                        <div class="relative">
                            <input type="text" id="sim_citizen_id" value="CTZN-PH-2026-008912" required
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                            <i class="fas fa-qrcode absolute left-3.5 top-3.5 text-slate-400"></i>
                        </div>
                    </div>

                    <button type="submit" id="btn-fetch-citizen"
                            class="w-full py-3 px-4 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl shadow-md hover:shadow-lg transition-all flex items-center justify-center gap-2 text-sm">
                        <i class="fas fa-cloud-download-alt"></i>
                        Execute Integration Query
                    </button>
                </form>

                <!-- Integration Spec Note -->
                <div class="bg-slate-50 dark:bg-slate-900/60 p-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs text-slate-600 dark:text-slate-400 space-y-2">
                    <div class="font-semibold text-slate-800 dark:text-slate-200 flex items-center gap-1.5">
                        <i class="fas fa-shield-alt text-teal-600"></i> Security & Authentication
                    </div>
                    <p>Production integration uses HTTP Bearer Token (<code class="bg-white dark:bg-slate-800 px-1 py-0.5 rounded border border-slate-300 dark:border-slate-600">Authorization: Bearer &lt;service_token&gt;</code>) over HTTPS TLS 1.3 encryption.</p>
                </div>
            </div>

            <!-- Right Results & Form Autofill Preview -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Interactive Form Autofill Simulation -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-magic text-teal-600 dark:text-teal-400"></i>
                            Health Center Clinic Form Autofill Preview
                        </h3>
                        <span id="autofill-status-badge" class="px-2.5 py-1 text-xs font-semibold rounded-full bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                            Waiting for Query
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">First Name</label>
                            <input type="text" id="preview_first_name" readonly placeholder="Auto-populated"
                                   class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm font-medium transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Middle Name</label>
                            <input type="text" id="preview_middle_name" readonly placeholder="Auto-populated"
                                   class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm font-medium transition-all">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Last Name</label>
                            <input type="text" id="preview_last_name" readonly placeholder="Auto-populated"
                                   class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm font-medium transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mt-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Birth Date</label>
                            <input type="text" id="preview_birth_date" readonly placeholder="YYYY-MM-DD"
                                   class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Gender</label>
                            <input type="text" id="preview_gender" readonly placeholder="Female / Male"
                                   class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Blood Type</label>
                            <input type="text" id="preview_blood_type" readonly placeholder="O+"
                                   class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Contact No.</label>
                            <input type="text" id="preview_contact" readonly placeholder="639XXXXXXXXX"
                                   class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm font-mono">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">Registered Address</label>
                        <input type="text" id="preview_address" readonly placeholder="Complete Street Address & Barangay"
                               class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900/60 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-800 dark:text-slate-200 text-sm">
                    </div>
                </div>

                <!-- API Response Inspector -->
                <div class="bg-slate-900 rounded-2xl p-5 border border-slate-800 shadow-xl font-mono text-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 text-slate-400">
                        <div class="flex items-center space-x-2">
                            <span class="w-3 h-3 rounded-full bg-emerald-500"></span>
                            <span class="w-3 h-3 rounded-full bg-amber-500"></span>
                            <span class="w-3 h-3 rounded-full bg-rose-500"></span>
                            <span class="ml-2 font-semibold text-slate-200">HTTP Response payload (JSON)</span>
                        </div>
                        <span id="citizen-http-status" class="text-emerald-400 font-bold">200 OK</span>
                    </div>
                    <pre id="citizen-json-output" class="pt-4 text-emerald-300 overflow-x-auto max-h-72">Press "Execute Integration Query" to make a real-time request to api/v1/citizens.php</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 2: TREASURY & CENTRAL PAYMENTS -->
    <div id="treasury-tab" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Left: Bill Creation Form -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-file-invoice text-emerald-600 dark:text-emerald-400"></i>
                        Step 1: Outgoing Bill Registration
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Emulates sending payable bills to LGU Treasury via <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded text-emerald-600 dark:text-emerald-300">POST /api/v1/treasury_bills.php</code>.
                    </p>
                </div>

                <form id="treasury-bill-form" onsubmit="executeCreateBill(event)" class="space-y-4">
                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Service Module</label>
                        <select id="bill_service_module" class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm">
                            <option value="SANITATION_PERMITS">Sanitation Permit Fee</option>
                            <option value="SEPTIC_DESLUDGING">Septic Desludging Quotation</option>
                            <option value="INSPECTION_VIOLATION">Sanitary Inspection Fine</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Invoice Number</label>
                            <input type="text" id="bill_invoice_number" value="INV-2026-00412" required
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm font-mono">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Amount (PHP ₱)</label>
                            <input type="number" step="0.01" id="bill_amount" value="1500.00" required
                                   class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm font-mono">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Payer Full Name</label>
                        <input type="text" id="bill_payer_name" value="Mateo Reyes" required
                               class="w-full px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-700 dark:text-slate-300 mb-1">Callback Webhook Endpoint</label>
                        <input type="text" readonly value="<?php echo site_url('api/treasury-webhook.php'); ?>"
                               class="w-full px-3 py-2 bg-slate-100 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-500 text-xs font-mono">
                    </div>

                    <button type="submit"
                            class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl shadow-md transition-all text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-paper-plane"></i>
                        1. Register Bill in Treasury
                    </button>
                </form>
            </div>

            <!-- Right: Webhook Trigger & Invoice Lifecycle -->
            <div class="lg:col-span-7 space-y-6">
                <!-- Step 2: Webhook Simulation -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-sm space-y-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-bolt text-amber-500"></i>
                            Step 2: Simulate Payment & Webhook Arrival
                        </h3>
                        <span id="bill-status-badge" class="px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300">
                            STATUS: UNPAID
                        </span>
                    </div>

                    <p class="text-xs text-slate-600 dark:text-slate-400">
                        When a citizen completes payment via GCash or City Treasury OTC, Central Treasury sends an HTTP POST Webhook payload to <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded text-emerald-600">/api/treasury-webhook.php</code>.
                    </p>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <button onclick="triggerPaymentWebhook('GCASH')" 
                                class="py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow transition-all text-xs flex items-center justify-center gap-2">
                            <i class="fas fa-mobile-alt"></i>
                            Simulate GCash Online Payment Success
                        </button>
                        <button onclick="triggerPaymentWebhook('OTC')" 
                                class="py-2.5 px-4 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl shadow transition-all text-xs flex items-center justify-center gap-2">
                            <i class="fas fa-building"></i>
                            Simulate Treasury Over-The-Counter Cash Success
                        </button>
                    </div>
                </div>

                <!-- Webhook Console Output -->
                <div class="bg-slate-900 rounded-2xl p-5 border border-slate-800 shadow-xl font-mono text-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 text-slate-400">
                        <span class="font-semibold text-slate-200">Treasury Webhook Event Stream Log</span>
                        <span id="webhook-http-status" class="text-slate-400 font-bold">Idle</span>
                    </div>
                    <pre id="treasury-json-output" class="pt-4 text-emerald-300 overflow-x-auto max-h-64">Register a bill or click "Simulate Payment" to view live Webhook event payload...</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 3: BUSINESS PERMITS CLEARANCE CHECK -->
    <div id="permits-tab" class="tab-content hidden">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!-- Controls -->
            <div class="lg:col-span-5 bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-sm space-y-6">
                <div>
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <i class="fas fa-shield-virus text-blue-600 dark:text-blue-400"></i>
                        Sanitation Clearance Gatekeeper API
                    </h2>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        External Business Permit Office (BPLO) calls <code class="bg-slate-100 dark:bg-slate-700 px-1 py-0.5 rounded text-blue-600 dark:text-blue-300">GET /api/v1/clearance_status.php?business_id=...</code> to verify clearance before renewing business permits.
                    </p>
                </div>

                <!-- Presets -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 uppercase tracking-wider mb-2">Preset Test Business IDs</label>
                    <div class="flex flex-wrap gap-2">
                        <button onclick="setBusinessId('BIZ-NCR-2026-00452')" 
                                class="px-3 py-1.5 text-xs font-mono font-medium rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 hover:bg-emerald-100 transition-colors">
                            BIZ-NCR-2026-00452 (Approved)
                        </button>
                        <button onclick="setBusinessId('BIZ-NCR-2026-00891')" 
                                class="px-3 py-1.5 text-xs font-mono font-medium rounded-lg bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 border border-amber-200 dark:border-amber-800 hover:bg-amber-100 transition-colors">
                            BIZ-NCR-2026-00891 (Pending)
                        </button>
                        <button onclick="setBusinessId('BIZ-NCR-2026-00999')" 
                                class="px-3 py-1.5 text-xs font-mono font-medium rounded-lg bg-rose-50 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 transition-colors">
                            BIZ-NCR-2026-00999 (Revoked/Expired)
                        </button>
                    </div>
                </div>

                <!-- Input -->
                <form id="permits-sim-form" onsubmit="executePermitCheck(event)" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">Target Business Identification ID</label>
                        <div class="relative">
                            <input type="text" id="sim_business_id" value="BIZ-NCR-2026-00452" required
                                   class="w-full pl-10 pr-4 py-2.5 bg-slate-50 dark:bg-slate-900 border border-slate-300 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white font-mono text-sm focus:ring-2 focus:ring-blue-500">
                            <i class="fas fa-store absolute left-3.5 top-3.5 text-slate-400"></i>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl shadow-md transition-all text-sm flex items-center justify-center gap-2">
                        <i class="fas fa-search"></i>
                        Query Sanitation Clearance Status
                    </button>
                </form>
            </div>

            <!-- Results -->
            <div class="lg:col-span-7 space-y-6">
                <!-- BPLO Business Permit Portal Decision Card -->
                <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <i class="fas fa-gavel text-purple-600 dark:text-purple-400"></i>
                            Business Permit Office Gatekeeper Decision
                        </h3>
                        <span id="bplo-decision-badge" class="px-3 py-1 text-xs font-bold rounded-full bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                            Awaiting Query
                        </span>
                    </div>

                    <div id="bplo-decision-box" class="p-4 rounded-xl bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 space-y-2">
                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-200" id="bplo-business-name">Reyes Food Manufacturing Corp.</p>
                        <div class="grid grid-cols-2 gap-4 text-xs">
                            <div>
                                <span class="text-slate-500">Sanitation Permit No:</span>
                                <span class="font-mono font-medium text-slate-800 dark:text-slate-200 block" id="bplo-permit-no">SAN-2026-00109</span>
                            </div>
                            <div>
                                <span class="text-slate-500">Sanitation Expiry Date:</span>
                                <span class="font-mono font-medium text-slate-800 dark:text-slate-200 block" id="bplo-expiry">2027-01-31</span>
                            </div>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-400 pt-2 border-t border-slate-200 dark:border-slate-700" id="bplo-explanation">
                            Click "Query Sanitation Clearance Status" to evaluate permit eligibility.
                        </p>
                    </div>
                </div>

                <!-- API Response -->
                <div class="bg-slate-900 rounded-2xl p-5 border border-slate-800 shadow-xl font-mono text-xs">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-800 text-slate-400">
                        <span class="font-semibold text-slate-200">GET /api/v1/clearance_status.php Response</span>
                        <span id="permits-http-status" class="text-emerald-400 font-bold">200 OK</span>
                    </div>
                    <pre id="permits-json-output" class="pt-4 text-emerald-300 overflow-x-auto max-h-64">Execute query to view JSON response payload...</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- TAB 4: ARCHITECTURE & MATRIX -->
    <div id="architecture-tab" class="tab-content hidden">
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-6 border border-slate-200 dark:border-slate-700/80 shadow-sm space-y-6">
            <div>
                <h2 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <i class="fas fa-sitemap text-teal-600 dark:text-teal-400"></i>
                    Capstone Microservices Scope & Integration Matrix
                </h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Defensible tiered architecture ensuring domain isolation and eliminating unnecessary inter-department coupling.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 dark:bg-slate-700/60 uppercase font-semibold text-slate-700 dark:text-slate-300 border-b border-slate-200 dark:border-slate-700">
                        <tr>
                            <th class="px-4 py-3">Tier Level</th>
                            <th class="px-4 py-3">Microservice Partner</th>
                            <th class="px-4 py-3">Integration Purpose</th>
                            <th class="px-4 py-3">Endpoint / Mechanism</th>
                            <th class="px-4 py-3">Simulation Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-slate-700 text-slate-700 dark:text-slate-300">
                        <tr class="bg-emerald-50/40 dark:bg-emerald-950/20">
                            <td class="px-4 py-3 font-bold text-emerald-700 dark:text-emerald-400">Tier 1 (Core)</td>
                            <td class="px-4 py-3 font-medium">Master Citizen Registry</td>
                            <td class="px-4 py-3">Autofill Verified Resident Demographics</td>
                            <td class="px-4 py-3 font-mono">GET /api/v1/citizens.php?id={id}</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 text-xs font-semibold">Active Simulation</span></td>
                        </tr>
                        <tr class="bg-emerald-50/40 dark:bg-emerald-950/20">
                            <td class="px-4 py-3 font-bold text-emerald-700 dark:text-emerald-400">Tier 1 (Core)</td>
                            <td class="px-4 py-3 font-medium">Revenue Collection & Treasury</td>
                            <td class="px-4 py-3">Central Payment Gateway & Webhook Receipts</td>
                            <td class="px-4 py-3 font-mono">POST /api/v1/treasury_bills.php & Webhook</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 text-xs font-semibold">Active Simulation</span></td>
                        </tr>
                        <tr class="bg-emerald-50/40 dark:bg-emerald-950/20">
                            <td class="px-4 py-3 font-bold text-emerald-700 dark:text-emerald-400">Tier 1 (Core)</td>
                            <td class="px-4 py-3 font-medium">Permits & Licensing (BPLO)</td>
                            <td class="px-4 py-3">Sanitation Clearance Permit Gatekeeping</td>
                            <td class="px-4 py-3 font-mono">GET /api/v1/clearance_status.php</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-200 text-xs font-semibold">Active Simulation</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-bold text-amber-600 dark:text-amber-400">Tier 2 (Roadmap)</td>
                            <td class="px-4 py-3 font-medium">DRRM Disaster Management</td>
                            <td class="px-4 py-3">Disease Epidemic Cluster Alerts</td>
                            <td class="px-4 py-3 font-mono">POST /api/v1/drrm/epidemic-alert</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 text-xs">Documented Spec</span></td>
                        </tr>
                        <tr>
                            <td class="px-4 py-3 font-bold text-amber-600 dark:text-amber-400">Tier 2 (Roadmap)</td>
                            <td class="px-4 py-3 font-medium">Social Services (AICS)</td>
                            <td class="px-4 py-3">Child Malnutrition Program Referral</td>
                            <td class="px-4 py-3 font-mono">POST /api/v1/social-services/aid-enrollment</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-400 text-xs">Documented Spec</span></td>
                        </tr>
                        <tr class="bg-slate-100/60 dark:bg-slate-900/40">
                            <td class="px-4 py-3 font-bold text-slate-500">Tier 3 (Excluded)</td>
                            <td class="px-4 py-3 font-medium">Transport, Zoning, Scholarships, Parks</td>
                            <td class="px-4 py-3">Unrelated LGU Domains</td>
                            <td class="px-4 py-3 font-mono text-slate-400">N/A (Decoupled by Design)</td>
                            <td class="px-4 py-3"><span class="px-2 py-0.5 rounded-full bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-400 text-xs">Disconnected</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
    document.querySelectorAll('.sim-tab-btn').forEach(el => {
        el.classList.remove('active', 'border-teal-600', 'text-teal-600', 'dark:text-teal-400', 'dark:border-teal-400');
        el.classList.add('border-transparent', 'text-slate-500', 'dark:text-slate-400');
    });

    const activeTab = document.getElementById(tabId);
    const activeBtn = document.getElementById('tab-btn-' + tabId);

    if (activeTab) activeTab.classList.remove('hidden');
    if (activeBtn) {
        activeBtn.classList.add('active', 'border-teal-600', 'text-teal-600', 'dark:text-teal-400', 'dark:border-teal-400');
        activeBtn.classList.remove('border-transparent', 'text-slate-500', 'dark:text-slate-400');
    }
}

// -------------------------------------------------------------
// CITIZEN SIMULATION
// -------------------------------------------------------------
function setCitizenId(id) {
    document.getElementById('sim_citizen_id').value = id;
    executeCitizenSim();
}

async function executeCitizenSim(e) {
    if (e) e.preventDefault();
    const citizenId = document.getElementById('sim_citizen_id').value.trim();
    const outputEl = document.getElementById('citizen-json-output');
    const badgeEl = document.getElementById('autofill-status-badge');

    outputEl.textContent = 'Fetching from api/v1/citizens.php...';
    badgeEl.textContent = 'Querying...';
    badgeEl.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';

    try {
        const res = await fetch(`<?php echo site_url('api/v1/citizens.php'); ?>?citizen_id=${encodeURIComponent(citizenId)}`);
        const data = await res.json();

        outputEl.textContent = JSON.stringify(data, null, 2);

        if (data.status === 'success' && data.data) {
            const d = data.data;
            document.getElementById('preview_first_name').value  = d.first_name || '';
            document.getElementById('preview_middle_name').value = d.middle_name || '';
            document.getElementById('preview_last_name').value   = d.last_name || '';
            document.getElementById('preview_birth_date').value  = d.birth_date || '';
            document.getElementById('preview_gender').value      = d.gender || '';
            document.getElementById('preview_blood_type').value  = d.blood_type || '';
            document.getElementById('preview_contact').value     = d.contact_number || '';
            document.getElementById('preview_address').value     = d.address || '';

            badgeEl.textContent = 'Autofill Successful ✓';
            badgeEl.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
        }
    } catch (err) {
        outputEl.textContent = 'Error executing request: ' + err.message;
        badgeEl.textContent = 'Query Failed';
        badgeEl.className = 'px-2.5 py-1 text-xs font-semibold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300';
    }
}

// -------------------------------------------------------------
// TREASURY SIMULATION
// -------------------------------------------------------------
async function executeCreateBill(e) {
    if (e) e.preventDefault();
    const outputEl = document.getElementById('treasury-json-output');
    const badgeEl = document.getElementById('bill-status-badge');

    const payload = {
        service_module: document.getElementById('bill_service_module').value,
        invoice_number: document.getElementById('bill_invoice_number').value,
        amount: parseFloat(document.getElementById('bill_amount').value),
        payer_name: document.getElementById('bill_payer_name').value
    };

    outputEl.textContent = 'Sending POST /api/v1/treasury_bills.php...';

    try {
        const res = await fetch(`<?php echo site_url('api/v1/treasury_bills.php'); ?>`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        outputEl.textContent = '--- STEP 1: BILL REGISTERED IN TREASURY ---\n' + JSON.stringify(data, null, 2);
        badgeEl.textContent = 'STATUS: PENDING PAYMENT';
        badgeEl.className = 'px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300';
    } catch (err) {
        outputEl.textContent = 'Error: ' + err.message;
    }
}

async function triggerPaymentWebhook(channel) {
    const outputEl = document.getElementById('treasury-json-output');
    const badgeEl = document.getElementById('bill-status-badge');
    const statusEl = document.getElementById('webhook-http-status');
    const invNo = document.getElementById('bill_invoice_number').value || 'INV-2026-00412';
    const amount = parseFloat(document.getElementById('bill_amount').value) || 1500.00;

    const webhookPayload = {
        event: 'payment.succeeded',
        invoice_number: invNo,
        official_receipt_no: 'OR-2026-' + Math.floor(100000 + Math.random() * 900000),
        amount_paid: amount,
        payment_channel: channel,
        paid_at: new Date().toISOString()
    };

    statusEl.textContent = 'POSTing Webhook...';

    try {
        const res = await fetch(`<?php echo site_url('api/treasury-webhook.php'); ?>`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(webhookPayload)
        });
        const data = await res.json();

        statusEl.textContent = '200 OK (Webhook Received)';
        outputEl.textContent = `--- INCOMING WEBHOOK PAYLOAD FROM TREASURY (${channel}) ---\n` +
                               JSON.stringify(webhookPayload, null, 2) +
                               '\n\n--- HEALTH & SANITATION SYSTEM RESPONSE ---\n' +
                               JSON.stringify(data, null, 2);

        badgeEl.textContent = 'STATUS: PAID (PERMIT ISSUED)';
        badgeEl.className = 'px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
    } catch (err) {
        outputEl.textContent = 'Webhook Error: ' + err.message;
        statusEl.textContent = '500 Error';
    }
}

// -------------------------------------------------------------
// PERMITS CLEARANCE SIMULATION
// -------------------------------------------------------------
function setBusinessId(id) {
    document.getElementById('sim_business_id').value = id;
    executePermitCheck();
}

async function executePermitCheck(e) {
    if (e) e.preventDefault();
    const businessId = document.getElementById('sim_business_id').value.trim();
    const outputEl = document.getElementById('permits-json-output');
    const badgeEl = document.getElementById('bplo-decision-badge');
    const expEl = document.getElementById('bplo-explanation');

    outputEl.textContent = 'Fetching from api/v1/clearance_status.php...';

    try {
        const res = await fetch(`<?php echo site_url('api/v1/clearance_status.php'); ?>?business_id=${encodeURIComponent(businessId)}`);
        const data = await res.json();

        outputEl.textContent = JSON.stringify(data, null, 2);

        document.getElementById('bplo-business-name').textContent = data.business_name || ('Business #' + businessId);
        document.getElementById('bplo-permit-no').textContent     = data.sanitation_permit_no || 'N/A';
        document.getElementById('bplo-expiry').textContent        = data.expiry_date || 'N/A';

        if (data.clearance_valid === true) {
            badgeEl.textContent = 'BPLO DECISION: GRANT BUSINESS PERMIT ✓';
            badgeEl.className = 'px-3 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300';
            expEl.innerHTML = '<span class="text-emerald-600 font-semibold">Sanitation Clearance Verified Valid:</span> Sanitation permit is active and non-expired. The BPLO automated engine allows application approval.';
        } else {
            badgeEl.textContent = 'BPLO DECISION: BLOCK PERMIT RENEWAL ✕';
            badgeEl.className = 'px-3 py-1 text-xs font-bold rounded-full bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300';
            expEl.innerHTML = '<span class="text-rose-600 font-semibold">Sanitation Clearance Missing or Invalid:</span> Business establishment must schedule and pass a Sanitation Inspection before BPLO permit can be approved.';
        }
    } catch (err) {
        outputEl.textContent = 'Error: ' + err.message;
    }
}

// Auto-run first simulation on page load
document.addEventListener('DOMContentLoaded', () => {
    executeCitizenSim();
});
</script>

<?php include_once '../includes/footer.php'; ?>

