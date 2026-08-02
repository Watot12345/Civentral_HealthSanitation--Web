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
// 1. PHP BACKEND - Fetch Data & API Endpoints
// ============================================================
require_once '../../includes/header.php';
require_once '../../includes/sidebar.php';
requireDepartmentAccess('health surveillance');

// Start session for persistence across AJAX calls
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Try to include the model, but don't fail if missing
$modelAvailable = false;
try {
    require_once __DIR__ . '/../../app/Models/SurveillanceAlert.php';
    if (class_exists('SurveillanceAlert')) {
        $modelAvailable = true;
        $alertModel = new SurveillanceAlert();
    }
} catch (Throwable $e) {
    error_log("SurveillanceAlert model not loaded: " . $e->getMessage());
    $modelAvailable = false;
}

// Initialize session data if empty
if (!isset($_SESSION['alerts_data']) || empty($_SESSION['alerts_data'])) {
    // Try to fetch from model, otherwise create dummy data
    $rawAlerts = [];
    if ($modelAvailable && method_exists($alertModel, 'all')) {
        try {
            $rawAlerts = $alertModel->all();
        } catch (Throwable $e) {
            error_log("Failed to fetch alerts from model: " . $e->getMessage());
            $rawAlerts = [];
        }
    }
    // If no data, create default sample alerts
    if (empty($rawAlerts)) {
        $rawAlerts = [
            [
                'id' => 1,
                'alert_code' => 'ALT-001',
                'disease' => 'Dengue',
                'barangay' => 'San Jose',
                'cases' => 12,
                'threshold' => 10,
                'severity' => 'Critical',
                'status' => 'Active',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'escalation_level' => 2,
                'assigned_to' => 'Dr. Reyes',
                'response_actions' => ['Monitoring', 'Contact Tracing'],
                'message' => 'Critical outbreak detected in San Jose!'
            ],
            [
                'id' => 2,
                'alert_code' => 'ALT-002',
                'disease' => 'Influenza',
                'barangay' => 'Poblacion',
                'cases' => 15,
                'threshold' => 14,
                'severity' => 'Critical',
                'status' => 'Active',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-5 hours')),
                'escalation_level' => 1,
                'assigned_to' => 'Dr. Garcia',
                'response_actions' => ['Data verification'],
                'message' => 'Critical outbreak detected in Poblacion!'
            ],
            [
                'id' => 3,
                'alert_code' => 'ALT-003',
                'disease' => 'Leptospirosis',
                'barangay' => 'Riverside',
                'cases' => 5,
                'threshold' => 4,
                'severity' => 'Warning',
                'status' => 'Active',
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day')),
                'escalation_level' => 1,
                'assigned_to' => 'Dr. Santos',
                'response_actions' => ['Monitoring'],
                'message' => 'Alert: Riverside has exceeded threshold for Leptospirosis'
            ]
        ];
    }
    $_SESSION['alerts_data'] = $rawAlerts;
} else {
    $rawAlerts = $_SESSION['alerts_data'];
}

// Helper function to format alerts (consistent structure)
function formatAlerts($raw) {
    return array_map(function($a) {
        $actionsRaw = $a['response_actions'] ?? '';
        $actionsArr = is_array($actionsRaw) ? $actionsRaw : array_map('trim', explode(',', (string)$actionsRaw));
        if (empty($actionsArr) || (count($actionsArr) === 1 && $actionsArr[0] === '')) {
            $actionsArr = ['Monitoring', 'Field Investigation'];
        }
        return [
            'id' => $a['alert_code'] ?? ('ALT-' . ($a['id'] ?? rand(100,999))),
            'db_id' => (int) ($a['id'] ?? 0),
            'disease' => $a['disease'] ?? 'Unknown',
            'barangay' => $a['barangay'] ?? 'Unknown',
            'cases' => (int) ($a['cases'] ?? 0),
            'threshold' => (int) ($a['threshold'] ?? 10),
            'severity' => $a['severity'] ?? 'Warning',
            'status' => $a['status'] ?? 'Active',
            'timestamp' => $a['timestamp'] ?? date('Y-m-d H:i:s'),
            'escalation_level' => (int) ($a['escalation_level'] ?? 1),
            'assigned_to' => $a['assigned_to'] ?? 'Unassigned',
            'response_actions' => $actionsArr,
            'message' => $a['message'] ?? 'Alert triggered'
        ];
    }, $raw);
}

// ============================================================
// AJAX HANDLER - all actions are processed here
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'Invalid action'];

    try {
        $action = $_POST['action'];

        switch ($action) {
            case 'refresh':
                // Try to reload from model if available, else keep session
                if ($modelAvailable && method_exists($alertModel, 'all')) {
                    $fresh = $alertModel->all();
                    if (!empty($fresh)) {
                        $_SESSION['alerts_data'] = $fresh;
                        $rawAlerts = $fresh;
                    }
                }
                $alerts = formatAlerts($_SESSION['alerts_data']);
                $response = [
                    'success' => true,
                    'alerts' => $alerts,
                    'stats' => [
                        'active' => count(array_filter($alerts, fn($a) => $a['status'] == 'Active')),
                        'critical' => count(array_filter($alerts, fn($a) => $a['severity'] == 'Critical')),
                        'warning' => count(array_filter($alerts, fn($a) => $a['severity'] == 'Warning')),
                        'total' => count($alerts)
                    ]
                ];
                break;

            case 'resolve':
                $id = $_POST['alert_id'] ?? null;
                if (!$id) throw new Exception('Missing alert ID');
                $found = false;
                foreach ($_SESSION['alerts_data'] as &$a) {
                    $code = $a['alert_code'] ?? ('ALT-' . ($a['id'] ?? ''));
                    if ($code == $id) {
                        $a['status'] = 'Resolved';
                        $found = true;
                        break;
                    }
                }
                if (!$found) throw new Exception("Alert $id not found");
                // If model available, update there as well
                if ($modelAvailable && method_exists($alertModel, 'updateStatus')) {
                    $alertModel->updateStatus($id, 'Resolved');
                }
                $response = ['success' => true, 'message' => "Alert $id resolved"];
                break;

            case 'escalate':
                $id = $_POST['alert_id'] ?? null;
                if (!$id) throw new Exception('Missing alert ID');
                $found = false;
                foreach ($_SESSION['alerts_data'] as &$a) {
                    $code = $a['alert_code'] ?? ('ALT-' . ($a['id'] ?? ''));
                    if ($code == $id) {
                        $a['escalation_level'] = min(3, ($a['escalation_level'] ?? 1) + 1);
                        $found = true;
                        break;
                    }
                }
                if (!$found) throw new Exception("Alert $id not found");
                if ($modelAvailable && method_exists($alertModel, 'escalateAlert')) {
                    $alertModel->escalateAlert($id);
                }
                $response = ['success' => true, 'message' => "Alert $id escalated"];
                break;

            case 'assign_team':
                $id = $_POST['alert_id'] ?? null;
                $team = $_POST['team_name'] ?? null;
                if (!$id || !$team) throw new Exception('Missing alert ID or team name');
                $found = false;
                foreach ($_SESSION['alerts_data'] as &$a) {
                    $code = $a['alert_code'] ?? ('ALT-' . ($a['id'] ?? ''));
                    if ($code == $id) {
                        $a['assigned_to'] = $team;
                        $found = true;
                        break;
                    }
                }
                if (!$found) throw new Exception("Alert $id not found");
                if ($modelAvailable && method_exists($alertModel, 'assignTeam')) {
                    $alertModel->assignTeam($id, $team);
                }
                $response = ['success' => true, 'message' => "Team $team assigned to alert $id"];
                break;

            case 'deploy_team':
                $team = $_POST['team_name'] ?? null;
                $alertId = $_POST['alert_id'] ?? null;
                if (!$team || !$alertId) throw new Exception('Missing team or alert ID');
                $found = false;
                foreach ($_SESSION['alerts_data'] as &$a) {
                    $code = $a['alert_code'] ?? ('ALT-' . ($a['id'] ?? ''));
                    if ($code == $alertId) {
                        $a['assigned_to'] = $team;
                        $found = true;
                        break;
                    }
                }
                if (!$found) throw new Exception("Alert $alertId not found");
                if ($modelAvailable && method_exists($alertModel, 'assignTeam')) {
                    $alertModel->assignTeam($alertId, $team);
                }
                $response = ['success' => true, 'message' => "Team $team deployed to alert $alertId"];
                break;

            default:
                throw new Exception('Unknown action: ' . $action);
        }
    } catch (Throwable $e) {
        $response = ['success' => false, 'message' => 'Server error: ' . $e->getMessage()];
        error_log("Alerts AJAX error: " . $e->getMessage());
    }

    echo json_encode($response);
    exit;
}

// Format alerts for display
$alerts = formatAlerts($_SESSION['alerts_data']);

// Stats
$activeAlerts = count(array_filter($alerts, fn($a) => $a['status'] == 'Active'));
$criticalAlerts = count(array_filter($alerts, fn($a) => $a['severity'] == 'Critical'));
$warningAlerts = count(array_filter($alerts, fn($a) => $a['severity'] == 'Warning'));

// Escalation protocol levels
$escalationLevels = [
    1 => [
        'level' => 'Level 1 - Monitoring',
        'color' => 'bg-emerald-100 text-emerald-700',
        'icon' => 'fa-eye',
        'actions' => ['Continuous monitoring', 'Data verification', 'Community awareness']
    ],
    2 => [
        'level' => 'Level 2 - Response',
        'color' => 'bg-amber-100 text-amber-700',
        'icon' => 'fa-hand',
        'actions' => ['Team deployment', 'Contact tracing', 'Public advisory']
    ],
    3 => [
        'level' => 'Level 3 - Emergency',
        'color' => 'bg-red-100 text-red-700',
        'icon' => 'fa-triangle-exclamation',
        'actions' => ['Emergency response', 'Resource mobilization', 'Mass testing', 'Isolation protocols']
    ],
];

// Response teams (static)
$responseTeams = [
    [
        'name' => 'Rapid Response Team - A',
        'leader' => 'Dr. Reyes',
        'members' => ['Nurse Santos', 'Med Tech Cruz', 'Sanitation Officer Chen'],
        'status' => 'Available',
        'specialization' => 'Outbreak Control'
    ],
    [
        'name' => 'Rapid Response Team - B',
        'leader' => 'Dr. Garcia',
        'members' => ['Nurse Lopez', 'Med Tech Ramos', 'Sanitation Officer Tan'],
        'status' => 'Deployed',
        'specialization' => 'Emergency Response'
    ],
    [
        'name' => 'Surveillance Team',
        'leader' => 'Dr. Santos',
        'members' => ['Epidemiologist Lim', 'Data Analyst Cruz', 'Field Worker Gomez'],
        'status' => 'Available',
        'specialization' => 'Data Collection'
    ],
];

$title = 'Real-time Alerts';
?>
<!-- ============================================================ -->
<!-- 2. HTML + PHP EMBEDDED + Tailwind CSS                       -->
<!-- ============================================================ -->

<div class="flex-1 px-6 pt-[26px] pb-20 mb-10 flex flex-col min-h-0 overflow-hidden">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <h2 class="text-2xl font-black text-slate-900 tracking-tight">Real-time Alerts</h2>
                <span class="px-3 py-1 bg-brand-light text-brand-dark rounded-full text-xs font-bold flex items-center gap-1">
                    <i class="fa-solid fa-location-dot"></i> Caloocan City
                </span>
                <?php if ($activeAlerts > 0): ?>
                <span id="activeAlertBadge" class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold flex items-center gap-1 animate-pulse">
                    <i class="fa-solid fa-circle text-[6px]"></i> <span id="activeAlertCount"><?php echo $activeAlerts; ?></span> Active Alerts
                </span>
                <?php endif; ?>
            </div>
            <p class="text-sm text-slate-500 mt-0.5">Automated alerts, escalation protocol & emergency response management</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <button onclick="refreshAlerts()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold flex items-center gap-2 shadow-sm">
                <i class="fa-solid fa-rotate text-xs"></i> Refresh Alerts
            </button>
            <button onclick="showResponseModal()" class="px-4 py-2 bg-white border border-slate-200 text-slate-700 rounded-lg hover:bg-slate-50 transition text-sm font-semibold flex items-center gap-2">
                <i class="fa-solid fa-phone text-xs"></i> Emergency Response
            </button>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- KPI CARDS - Alert Overview                                 -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <!-- Card 1: Total Active Alerts -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 <?php echo $activeAlerts > 0 ? 'bg-gradient-to-br from-red-500 to-red-600' : 'bg-gradient-to-br from-emerald-500 to-emerald-600'; ?> rounded-xl flex items-center justify-center text-white shadow-lg <?php echo $activeAlerts > 0 ? 'shadow-red-200' : 'shadow-emerald-200'; ?>">
                        <i class="fa-solid fa-bell text-lg"></i>
                    </div>
                    <div>
                        <p id="kpiActive" class="text-2xl font-black <?php echo $activeAlerts > 0 ? 'text-red-600' : 'text-emerald-600'; ?>"><?php echo $activeAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span id="statusLabel" class="px-2 py-0.5 <?php echo $activeAlerts > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $activeAlerts > 0 ? '<i class="fa-solid fa-triangle-exclamation"></i> Needs Action' : '<i class="fa-solid fa-check-circle"></i> All Clear'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 2: Critical Alerts -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-red-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center text-white shadow-lg shadow-red-200">
                        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p id="kpiCritical" class="text-2xl font-black text-red-600"><?php echo $criticalAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Critical Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-bolt"></i> Urgent</span>
                    <span class="text-[10px] text-slate-400">Needs immediate action</span>
                </div>
            </div>
        </div>

        <!-- Card 3: Warning Alerts -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-amber-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-amber-500 to-amber-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200">
                        <i class="fa-solid fa-exclamation text-lg"></i>
                    </div>
                    <div>
                        <p id="kpiWarning" class="text-2xl font-black text-amber-600"><?php echo $warningAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Warning Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-eye"></i> Monitor</span>
                    <span class="text-[10px] text-slate-400">Watch closely</span>
                </div>
            </div>
        </div>

        <!-- Card 4: Response Teams -->
        <div class="relative overflow-hidden bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-lg transition group">
            <div class="absolute -top-12 -right-12 w-24 h-24 bg-blue-100 rounded-full opacity-50 group-hover:scale-110 transition"></div>
            <div class="relative">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl flex items-center justify-center text-white shadow-lg shadow-blue-200">
                        <i class="fa-solid fa-user-group text-lg"></i>
                    </div>
                    <div>
                        <p class="text-2xl font-black text-slate-900"><?php echo count($responseTeams); ?></p>
                        <p class="text-xs font-medium text-slate-500">Response Teams</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold">
                        <i class="fa-solid fa-circle-check"></i> <?php echo count(array_filter($responseTeams, fn($t) => $t['status'] == 'Available')); ?> Available
                    </span>
                    <span class="text-[10px] text-slate-400">Ready</span>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ALERT LIST - Real-time Alerts Feed                         -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-list text-brand-medium"></i>
                Alert Feed
                <span id="alertCountLabel" class="text-xs font-normal text-slate-400">(<?php echo $activeAlerts; ?> active)</span>
            </h3>
            <div class="flex items-center gap-3">
                <button onclick="filterAlerts('all')" class="filter-btn active px-3 py-1 text-xs font-semibold rounded-full bg-brand-dark text-white hover:bg-brand-medium transition" id="filter-all">All</button>
                <button onclick="filterAlerts('Critical')" class="filter-btn px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 hover:bg-red-200 transition" id="filter-critical">Critical</button>
                <button onclick="filterAlerts('Warning')" class="filter-btn px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 transition" id="filter-warning">Warning</button>
                <button onclick="markAllRead()" class="text-xs text-brand-dark hover:text-brand-medium font-medium">
                    <i class="fa-regular fa-circle-check"></i> Mark all read
                </button>
            </div>
        </div>
        <div class="p-4 max-h-[500px] overflow-y-auto" id="alertFeed">
            <!-- Dynamic content -->
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- ESCALATION PROTOCOL SECTION                                -->
    <!-- ============================================================ -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <?php foreach ($escalationLevels as $level => $data): ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-lg transition">
            <div class="px-4 py-3 <?php echo $level == 3 ? 'bg-red-50 border-b border-red-200' : ($level == 2 ? 'bg-amber-50 border-b border-amber-200' : 'bg-emerald-50 border-b border-emerald-200'); ?>">
                <div class="flex items-center gap-2">
                    <span class="w-8 h-8 <?php echo $level == 3 ? 'bg-red-500' : ($level == 2 ? 'bg-amber-500' : 'bg-emerald-500'); ?> rounded-full flex items-center justify-center text-white text-xs font-bold">
                        <?php echo $level; ?>
                    </span>
                    <div>
                        <h4 class="font-bold text-slate-800 text-sm"><?php echo $data['level']; ?></h4>
                        <span class="text-xs text-slate-500">Escalation Level</span>
                    </div>
                </div>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <?php foreach ($data['actions'] as $action): ?>
                    <div class="flex items-center gap-2 text-sm text-slate-600">
                        <i class="fa-solid fa-chevron-right text-[10px] <?php echo $level == 3 ? 'text-red-500' : ($level == 2 ? 'text-amber-500' : 'text-emerald-500'); ?>"></i>
                        <?php echo $action; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <span class="text-xs text-slate-400">
                        <i class="fa-regular fa-clock"></i> 
                        <?php echo $level == 3 ? 'Immediate response required' : ($level == 2 ? 'Response within 24 hours' : 'Continuous monitoring'); ?>
                    </span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ============================================================ -->
    <!-- RESPONSE TEAMS SECTION                                     -->
    <!-- ============================================================ -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-2">
            <h3 class="font-semibold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-user-group text-brand-medium"></i>
                Emergency Response Teams
                <span class="text-xs font-normal text-slate-400">(<?php echo count($responseTeams); ?> teams)</span>
            </h3>
            <button onclick="showDeployModal()" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-rocket"></i> Deploy Team
            </button>
        </div>
        <div class="p-4" id="teamsContainer">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($responseTeams as $idx => $team): ?>
                <div class="border border-slate-200 rounded-lg p-4 hover:shadow-md transition team-card" data-team-name="<?php echo $team['name']; ?>" data-team-idx="<?php echo $idx; ?>">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm"><?php echo $team['name']; ?></h4>
                            <p class="text-xs text-slate-500"><?php echo $team['specialization']; ?></p>
                        </div>
                        <span class="px-2 py-0.5 <?php echo $team['status'] == 'Available' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?> rounded-full text-[10px] font-bold team-status">
                            <?php echo $team['status']; ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-slate-600"><i class="fa-solid fa-user-doctor"></i> Leader: <span class="font-medium"><?php echo $team['leader']; ?></span></p>
                        <p class="text-xs text-slate-600 mt-1"><i class="fa-solid fa-users"></i> Members: <?php echo implode(', ', $team['members']); ?></p>
                    </div>
                    <?php if ($team['status'] == 'Available'): ?>
                    <button onclick="openAssignModal('<?php echo $team['name']; ?>')" class="mt-3 w-full px-3 py-1.5 bg-brand-light text-brand-dark rounded-lg hover:bg-brand-dark hover:text-white transition text-xs font-semibold">
                        <i class="fa-solid fa-paper-plane"></i> Assign to Alert
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- MODALS                                                      -->
    <!-- ============================================================ -->

    <!-- Emergency Response Modal -->
    <div id="emergencyModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 sticky top-0 bg-white rounded-t-2xl">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-phone text-red-500"></i>
                    Emergency Response
                </h3>
                <button onclick="closeModal('emergencyModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <p class="text-sm text-red-700 font-semibold"><i class="fa-solid fa-triangle-exclamation"></i> Emergency Response Protocol Activated</p>
                    <p id="emergencyCriticalCount" class="text-xs text-red-600 mt-1">Immediate action required for <?php echo $criticalAlerts; ?> critical alerts</p>
                </div>
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Alert to Respond</label>
                        <select id="emergencyAlertSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <?php foreach ($alerts as $alert): ?>
                            <option value="<?php echo $alert['id']; ?>">
                                <?php echo $alert['id']; ?> - <?php echo $alert['barangay']; ?> (<?php echo $alert['severity']; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Response Team</label>
                        <select id="emergencyTeamSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                            <?php foreach ($responseTeams as $team): ?>
                            <option value="<?php echo $team['name']; ?>"><?php echo $team['name']; ?> (<?php echo $team['status']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Response Actions</label>
                        <div class="space-y-2">
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark" checked> Deploy response team to barangay
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Activate contact tracing
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Issue public health advisory
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Mobilize medical supplies
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark"> Activate emergency hotline
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Additional Instructions</label>
                        <textarea id="emergencyInstructions" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Enter specific instructions..."></textarea>
                    </div>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button onclick="closeModal('emergencyModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                    <button onclick="activateEmergencyResponse()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i> Activate
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Team Modal -->
    <div id="assignModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-user-check text-brand-medium"></i> Assign Team to Alert
                </h3>
                <button onclick="closeModal('assignModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4"><p class="text-sm text-slate-600">Team: <span id="assignTeamName" class="font-bold text-slate-900"></span></p></div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Alert</label>
                    <select id="assignAlertSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <?php foreach ($alerts as $alert): ?>
                        <option value="<?php echo $alert['id']; ?>"><?php echo $alert['id']; ?> - <?php echo $alert['barangay']; ?> (<?php echo $alert['severity']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button onclick="closeModal('assignModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                    <button onclick="confirmAssign()" class="px-4 py-2 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-sm font-semibold">Assign Team</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Deploy Team Modal -->
    <div id="deployModal" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-50 items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-rocket text-brand-medium"></i> Deploy Response Team
                </h3>
                <button onclick="closeModal('deployModal')" class="w-8 h-8 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-600 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <div class="p-6">
                <div class="mb-4">
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Team</label>
                    <select id="deployTeamSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <?php foreach ($responseTeams as $team): ?>
                        <option value="<?php echo $team['name']; ?>"><?php echo $team['name']; ?> (<?php echo $team['status']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Select Alert to Deploy to</label>
                    <select id="deployAlertSelect" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none">
                        <?php foreach ($alerts as $alert): ?>
                        <option value="<?php echo $alert['id']; ?>"><?php echo $alert['id']; ?> - <?php echo $alert['barangay']; ?> (<?php echo $alert['severity']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button onclick="closeModal('deployModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">Cancel</button>
                    <button onclick="confirmDeploy()" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition text-sm font-semibold">Deploy Team</button>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Toast -->
<div id="toast" class="hidden fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2">
    <i class="fa-solid fa-circle-check"></i>
    <span id="toastMessage"></span>
</div>

<!-- ============================================================ -->
<!-- JAVASCRIPT                                                   -->
<!-- ============================================================ -->
<script>
    // ============================================================
    // RENDER ALERTS
    // ============================================================
    function renderAlerts(alerts, stats) {
        const feed = document.getElementById('alertFeed');
        if (alerts.length === 0) {
            feed.innerHTML = `
                <div class="text-center py-8 text-slate-400">
                    <i class="fa-solid fa-check-circle text-3xl block mb-2 text-emerald-500"></i>
                    <p class="text-sm font-medium">No alerts</p>
                    <p class="text-xs">All clear</p>
                </div>
            `;
        } else {
            let html = '';
            alerts.forEach(alert => {
                const severityColors = {
                    'Critical': 'bg-red-50 border-l-4 border-red-500 text-red-700',
                    'Warning': 'bg-amber-50 border-l-4 border-amber-500 text-amber-700'
                };
                const severityBadges = {
                    'Critical': 'bg-red-500 text-white',
                    'Warning': 'bg-amber-500 text-white'
                };
                const cls = severityColors[alert.severity] || 'bg-slate-50';
                const badge = severityBadges[alert.severity] || 'bg-slate-500 text-white';
                const dot = alert.severity == 'Critical' ? 'bg-red-500' : 'bg-amber-500';
                const statusClass = alert.status == 'Resolved' ? 'opacity-50 line-through' : '';
                html += `
                <div class="flex items-start gap-3 p-3 ${cls} rounded-lg mb-2 hover:shadow-sm transition alert-item ${statusClass}" data-severity="${alert.severity}" data-alert-id="${alert.id}">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="w-2 h-2 ${dot} rounded-full inline-block animate-pulse"></span>
                            <span class="text-sm font-bold ${alert.severity == 'Critical' ? 'text-red-700' : 'text-amber-700'}">${alert.message}</span>
                            <span class="px-2 py-0.5 ${badge} rounded-full text-[10px] font-bold">${alert.severity}</span>
                            <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold">${alert.id}</span>
                            ${alert.status == 'Resolved' ? '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold"><i class="fa-solid fa-check"></i> Resolved</span>' : ''}
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                            <span><i class="fa-solid fa-location-dot"></i> ${alert.barangay}</span>
                            <span><i class="fa-solid fa-bug"></i> ${alert.disease}</span>
                            <span><i class="fa-solid fa-chart-bar"></i> ${alert.cases} cases (Threshold: ${alert.threshold})</span>
                            <span class="text-slate-400"><i class="fa-regular fa-clock"></i> ${new Date(alert.timestamp).toLocaleTimeString()}</span>
                            <span class="text-slate-400"><i class="fa-solid fa-arrow-up"></i> Escalation: Level ${alert.escalation_level}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span class="text-xs text-slate-500"><i class="fa-solid fa-user-doctor"></i> Assigned: ${alert.assigned_to}</span>
                            ${alert.response_actions.map(action => `<span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px]"><i class="fa-solid fa-check-circle text-[8px]"></i> ${action}</span>`).join('')}
                        </div>
                    </div>
                    ${alert.status != 'Resolved' ? `
                    <div class="flex flex-col gap-1">
                        <button onclick="resolveAlert('${alert.id}')" class="px-2 py-1 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded text-xs font-medium transition">
                            <i class="fa-solid fa-check"></i> Resolve
                        </button>
                        <button onclick="escalateAlert('${alert.id}')" class="px-2 py-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded text-xs font-medium transition">
                            <i class="fa-solid fa-arrow-up"></i> Escalate
                        </button>
                    </div>
                    ` : ''}
                </div>
                `;
            });
            feed.innerHTML = html;
        }

        // Update KPIs
        document.getElementById('kpiActive').textContent = stats.active;
        document.getElementById('kpiCritical').textContent = stats.critical;
        document.getElementById('kpiWarning').textContent = stats.warning;
        document.getElementById('activeAlertCount').textContent = stats.active;
        document.getElementById('alertCountLabel').textContent = `(${stats.active} active)`;
        document.getElementById('emergencyCriticalCount').textContent = `Immediate action required for ${stats.critical} critical alerts`;

        const statusLabel = document.getElementById('statusLabel');
        if (stats.active > 0) {
            statusLabel.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Needs Action';
            statusLabel.className = 'px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold';
        } else {
            statusLabel.innerHTML = '<i class="fa-solid fa-check-circle"></i> All Clear';
            statusLabel.className = 'px-2 py-0.5 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-bold';
        }

        const badge = document.getElementById('activeAlertBadge');
        if (badge) {
            if (stats.active > 0) badge.classList.remove('hidden');
            else badge.classList.add('hidden');
        }

        // Reapply filter
        const activeFilter = document.querySelector('.filter-btn.active');
        if (activeFilter) {
            const filter = activeFilter.id === 'filter-all' ? 'all' : (activeFilter.id === 'filter-critical' ? 'Critical' : 'Warning');
            filterAlerts(filter);
        }
    }

    // ============================================================
    // REFRESH ALERTS (AJAX)
    // ============================================================
    function refreshAlerts() {
        showToast('🔄 Refreshing alerts...', 'info');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'refresh' })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderAlerts(data.alerts, data.stats);
                showToast('✅ Alerts refreshed!', 'success');
            } else {
                showToast('❌ ' + (data.message || 'Failed to refresh alerts'), 'danger');
            }
        })
        .catch(err => {
            showToast('❌ Network error: ' + err.message, 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // RESOLVE ALERT
    // ============================================================
    function resolveAlert(alertId) {
        if (!confirm(`Resolve alert ${alertId}?`)) return;
        showToast(`⏳ Resolving ${alertId}...`, 'info');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'resolve', alert_id: alertId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                refreshAlerts();
                showToast(data.message, 'success');
            } else {
                showToast('❌ ' + data.message, 'danger');
            }
        })
        .catch(err => {
            showToast('❌ Error resolving alert', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // ESCALATE ALERT
    // ============================================================
    function escalateAlert(alertId) {
        if (!confirm(`Escalate alert ${alertId} to next level?`)) return;
        showToast(`⏳ Escalating ${alertId}...`, 'info');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'escalate', alert_id: alertId })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                refreshAlerts();
                showToast(data.message, 'warning');
            } else {
                showToast('❌ ' + data.message, 'danger');
            }
        })
        .catch(err => {
            showToast('❌ Error escalating alert', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // FILTER ALERTS
    // ============================================================
    function filterAlerts(severity) {
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-dark', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700');
        });
        const targetBtn = severity === 'all' ? 'filter-all' : (severity === 'Critical' ? 'filter-critical' : 'filter-warning');
        const btn = document.getElementById(targetBtn);
        if (btn) {
            btn.classList.add('active', 'bg-brand-dark', 'text-white');
            btn.classList.remove('bg-white', 'text-slate-700');
        }

        document.querySelectorAll('.alert-item').forEach(item => {
            item.style.display = (severity === 'all' || item.dataset.severity === severity) ? 'flex' : 'none';
        });
    }

    // ============================================================
    // MARK ALL READ (dummy)
    // ============================================================
    function markAllRead() {
        showToast('✅ All alerts marked as read', 'success');
    }

    // ============================================================
    // ASSIGN TEAM
    // ============================================================
    let assignTeamName = '';

    function openAssignModal(teamName) {
        assignTeamName = teamName;
        document.getElementById('assignTeamName').textContent = teamName;
        document.getElementById('assignModal').classList.remove('hidden');
        document.getElementById('assignModal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function confirmAssign() {
        const alertId = document.getElementById('assignAlertSelect').value;
        if (!alertId) {
            showToast('Please select an alert', 'warning');
            return;
        }
        showToast(`⏳ Assigning ${assignTeamName}...`, 'info');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'assign_team', alert_id: alertId, team_name: assignTeamName })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModal('assignModal');
                refreshAlerts();
                showToast(data.message, 'success');
            } else {
                showToast('❌ ' + data.message, 'danger');
            }
        })
        .catch(err => {
            showToast('❌ Error assigning team', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // DEPLOY TEAM
    // ============================================================
    function showDeployModal() {
        document.getElementById('deployModal').classList.remove('hidden');
        document.getElementById('deployModal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function confirmDeploy() {
        const team = document.getElementById('deployTeamSelect').value;
        const alertId = document.getElementById('deployAlertSelect').value;
        if (!team || !alertId) {
            showToast('Please select both team and alert', 'warning');
            return;
        }
        showToast(`⏳ Deploying ${team}...`, 'info');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'deploy_team', alert_id: alertId, team_name: team })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModal('deployModal');
                refreshAlerts();
                showToast(data.message, 'success');
            } else {
                showToast('❌ ' + data.message, 'danger');
            }
        })
        .catch(err => {
            showToast('❌ Error deploying team', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // EMERGENCY RESPONSE
    // ============================================================
    function showResponseModal() {
        document.getElementById('emergencyModal').classList.remove('hidden');
        document.getElementById('emergencyModal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function activateEmergencyResponse() {
        const alertId = document.getElementById('emergencyAlertSelect').value;
        const team = document.getElementById('emergencyTeamSelect').value;
        if (!alertId) {
            showToast('Please select an alert', 'warning');
            return;
        }
        if (!confirm(`🚨 Activate emergency response for ${alertId}?`)) return;
        showToast(`⏳ Activating emergency response...`, 'info');
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'deploy_team', alert_id: alertId, team_name: team })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                closeModal('emergencyModal');
                refreshAlerts();
                showToast('🚨 Emergency response activated for ' + alertId, 'danger');
            } else {
                showToast('❌ ' + data.message, 'danger');
            }
        })
        .catch(err => {
            showToast('❌ Error activating emergency response', 'danger');
            console.error(err);
        });
    }

    // ============================================================
    // MODAL HELPERS
    // ============================================================
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    document.querySelectorAll('.fixed.inset-0').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) closeModal(this.id);
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.fixed.inset-0:not(.hidden)').forEach(modal => closeModal(modal.id));
        }
    });

    // ============================================================
    // TOAST
    // ============================================================
    let toastTimer = null;

    function showToast(msg, type = 'success') {
        const t = document.getElementById('toast');
        const colors = {
            success: 'bg-brand-dark',
            danger: 'bg-red-600',
            info: 'bg-blue-600',
            warning: 'bg-amber-600'
        };
        t.className = `fixed bottom-6 right-6 z-[60] px-4 py-3 rounded-lg shadow-lg text-sm font-semibold text-white flex items-center gap-2 ${colors[type] || colors.success}`;
        t.querySelector('i').className = 'fa-solid fa-circle-check';
        document.getElementById('toastMessage').textContent = msg;
        t.classList.remove('hidden');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.add('hidden'), 3000);
    }

    // ============================================================
    // INITIAL LOAD
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        refreshAlerts();
    });
</script>

<style>
    .animate-pulse {
        animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
    }
    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.5; }
    }
    
    .filter-btn.active {
        background: #0B4F4A !important;
        color: white !important;
    }
    .filter-btn:not(.active):hover {
        opacity: 0.8;
    }
    
    .alert-item {
        transition: all 0.3s ease;
    }
    .alert-item:hover {
        transform: translateX(4px);
    }
    .alert-item.opacity-50 {
        opacity: 0.5;
    }
    .alert-item.line-through .text-sm {
        text-decoration: line-through;
    }
</style>

<?php include_once '../../includes/footer.php'; ?>