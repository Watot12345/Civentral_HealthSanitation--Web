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
requireDepartmentAccess('health surveillance');

require_once __DIR__ . '/../../app/Models/SurveillanceAlert.php';

try {
    $alertModel = new SurveillanceAlert();
    $rawAlerts = $alertModel->all();

    $alerts = array_map(function($a) {
        $actionsRaw = $a['response_actions'] ?? '';
        $actionsArr = is_array($actionsRaw) ? $actionsRaw : array_map('trim', explode(',', (string)$actionsRaw));
        if (empty($actionsArr) || (count($actionsArr) === 1 && $actionsArr[0] === '')) {
            $actionsArr = ['Monitoring', 'Field Investigation'];
        }
        return [
            'id' => $a['alert_code'] ?? ('ALT-' . $a['id']),
            'db_id' => (int) $a['id'],
            'disease' => $a['disease'] ?? 'Dengue',
            'barangay' => $a['barangay'] ?? 'San Jose',
            'cases' => (int) ($a['cases'] ?? 0),
            'threshold' => (int) ($a['threshold'] ?? 10),
            'severity' => $a['severity'] ?? 'Warning',
            'status' => $a['status'] ?? 'Active',
            'timestamp' => $a['timestamp'] ?? date('Y-m-d H:i:s'),
            'escalation_level' => (int) ($a['escalation_level'] ?? 1),
            'assigned_to' => $a['assigned_to'] ?? 'Dr. Reyes',
            'response_actions' => $actionsArr,
            'message' => $a['message'] ?? 'Alert triggered for ' . ($a['disease'] ?? 'Disease')
        ];
    }, $rawAlerts);

} catch (Throwable $e) {
    error_log("Alerts fetch error: " . $e->getMessage());
    $alerts = [];
}


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

// Emergency response teams
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

// Statistics
$activeAlerts = count(array_filter($alerts, function($a) { return $a['status'] == 'Active'; }));
$criticalAlerts = count(array_filter($alerts, function($a) { return $a['severity'] == 'Critical'; }));
$warningAlerts = count(array_filter($alerts, function($a) { return $a['severity'] == 'Warning'; }));

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
                <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-xs font-bold flex items-center gap-1 animate-pulse">
                    <i class="fa-solid fa-circle text-[6px]"></i> <?php echo $activeAlerts; ?> Active Alerts
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
                        <p class="text-2xl font-black <?php echo $activeAlerts > 0 ? 'text-red-600' : 'text-emerald-600'; ?>"><?php echo $activeAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Active Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 <?php echo $activeAlerts > 0 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700'; ?> rounded-full text-[10px] font-bold">
                        <?php echo $activeAlerts > 0 ? '⚠️ Needs Action' : '✅ All Clear'; ?>
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
                        <p class="text-2xl font-black text-red-600"><?php echo $criticalAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Critical Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-red-100 text-red-700 rounded-full text-[10px] font-bold">🚨 Urgent</span>
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
                        <p class="text-2xl font-black text-amber-600"><?php echo $warningAlerts; ?></p>
                        <p class="text-xs font-medium text-slate-500">Warning Alerts</p>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-700 rounded-full text-[10px] font-bold">⚠️ Monitor</span>
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
                        <?php echo count(array_filter($responseTeams, function($t) { return $t['status'] == 'Available'; })); ?> Available
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
                <span class="text-xs font-normal text-slate-400">(<?php echo $activeAlerts; ?> active)</span>
            </h3>
            <div class="flex items-center gap-3">
                <button onclick="filterAlerts('all')" class="filter-btn active px-3 py-1 text-xs font-semibold rounded-full bg-brand-dark text-white hover:bg-brand-medium transition" id="filter-all">All</button>
                <button onclick="filterAlerts('Critical')" class="filter-btn px-3 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-700 hover:bg-red-200 transition" id="filter-critical">Critical</button>
                <button onclick="filterAlerts('Warning')" class="filter-btn px-3 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-700 hover:bg-amber-200 transition" id="filter-warning">Warning</button>
                <button onclick="markAllRead()" class="text-xs text-brand-dark hover:text-brand-medium font-medium">
                    Mark all read
                </button>
            </div>
        </div>
        <div class="p-4 max-h-[500px] overflow-y-auto" id="alertFeed">
            <?php foreach ($alerts as $alert): 
                $severityColors = [
                    'Critical' => 'bg-red-50 border-l-4 border-red-500 text-red-700',
                    'Warning' => 'bg-amber-50 border-l-4 border-amber-500 text-amber-700'
                ];
                $severityBadges = [
                    'Critical' => 'bg-red-500 text-white',
                    'Warning' => 'bg-amber-500 text-white'
                ];
            ?>
            <div class="flex items-start gap-3 p-3 <?php echo $severityColors[$alert['severity']] ?? 'bg-slate-50'; ?> rounded-lg mb-2 hover:shadow-sm transition alert-item" data-severity="<?php echo $alert['severity']; ?>">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="w-2 h-2 <?php echo $alert['severity'] == 'Critical' ? 'bg-red-500' : 'bg-amber-500'; ?> rounded-full inline-block animate-pulse"></span>
                        <span class="text-sm font-bold <?php echo $alert['severity'] == 'Critical' ? 'text-red-700' : 'text-amber-700'; ?>">
                            <?php echo $alert['message']; ?>
                        </span>
                        <span class="px-2 py-0.5 <?php echo $severityBadges[$alert['severity']] ?? 'bg-slate-500 text-white'; ?> rounded-full text-[10px] font-bold">
                            <?php echo $alert['severity']; ?>
                        </span>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px] font-bold">
                            <?php echo $alert['id']; ?>
                        </span>
                    </div>
                    <div class="mt-1 flex flex-wrap items-center gap-4 text-xs text-slate-600">
                        <span>📍 <?php echo $alert['barangay']; ?></span>
                        <span>🦟 <?php echo $alert['disease']; ?></span>
                        <span>📊 <?php echo $alert['cases']; ?> cases (Threshold: <?php echo $alert['threshold']; ?>)</span>
                        <span class="text-slate-400"><?php echo date('h:i A', strtotime($alert['timestamp'])); ?></span>
                        <span class="text-slate-400">| Escalation: Level <?php echo $alert['escalation_level']; ?></span>
                    </div>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span class="text-xs text-slate-500">👨‍⚕️ Assigned: <?php echo $alert['assigned_to']; ?></span>
                        <?php foreach ($alert['response_actions'] as $action): ?>
                        <span class="px-2 py-0.5 bg-slate-100 text-slate-600 rounded-full text-[10px]">
                            <i class="fa-solid fa-check-circle text-[8px]"></i> <?php echo $action; ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="flex flex-col gap-1">
                    <button onclick="resolveAlert('<?php echo $alert['id']; ?>')" class="px-2 py-1 text-emerald-600 hover:text-emerald-800 hover:bg-emerald-50 rounded text-xs font-medium transition">
                        <i class="fa-solid fa-check"></i> Resolve
                    </button>
                    <button onclick="escalateAlert('<?php echo $alert['id']; ?>')" class="px-2 py-1 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded text-xs font-medium transition">
                        <i class="fa-solid fa-arrow-up"></i> Escalate
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
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
            <button onclick="deployTeam()" class="px-3 py-1.5 bg-brand-dark text-white rounded-lg hover:bg-brand-medium transition text-xs font-semibold flex items-center gap-1.5">
                <i class="fa-solid fa-rocket"></i> Deploy Team
            </button>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <?php foreach ($responseTeams as $team): ?>
                <div class="border border-slate-200 rounded-lg p-4 hover:shadow-md transition">
                    <div class="flex items-start justify-between">
                        <div>
                            <h4 class="font-bold text-slate-800 text-sm"><?php echo $team['name']; ?></h4>
                            <p class="text-xs text-slate-500"><?php echo $team['specialization']; ?></p>
                        </div>
                        <span class="px-2 py-0.5 <?php echo $team['status'] == 'Available' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'; ?> rounded-full text-[10px] font-bold">
                            <?php echo $team['status']; ?>
                        </span>
                    </div>
                    <div class="mt-2">
                        <p class="text-xs text-slate-600">👨‍⚕️ Leader: <span class="font-medium"><?php echo $team['leader']; ?></span></p>
                        <p class="text-xs text-slate-600 mt-1">👥 Members: <?php echo implode(', ', $team['members']); ?></p>
                    </div>
                    <?php if ($team['status'] == 'Available'): ?>
                    <button onclick="assignTeam('<?php echo $team['name']; ?>')" class="mt-3 w-full px-3 py-1.5 bg-brand-light text-brand-dark rounded-lg hover:bg-brand-dark hover:text-white transition text-xs font-semibold">
                        <i class="fa-solid fa-paper-plane"></i> Assign to Alert
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================ -->
    <!-- EMERGENCY RESPONSE MODAL                                   -->
    <!-- ============================================================ -->
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
                    <p class="text-sm text-red-700 font-semibold">🚨 Emergency Response Protocol Activated</p>
                    <p class="text-xs text-red-600 mt-1">Immediate action required for <?php echo $criticalAlerts; ?> critical alerts</p>
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
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark" checked>
                                Deploy response team to barangay
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark">
                                Activate contact tracing
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark">
                                Issue public health advisory
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark">
                                Mobilize medical supplies
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-700">
                                <input type="checkbox" class="rounded border-slate-300 text-brand-dark">
                                Activate emergency hotline
                            </label>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">Additional Instructions</label>
                        <textarea id="emergencyInstructions" rows="3" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 focus:ring-brand-medium/40 focus:border-brand-medium outline-none" placeholder="Enter specific instructions for the response team..."></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end gap-2 pt-4 border-t border-slate-100 mt-4">
                    <button onclick="closeModal('emergencyModal')" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg hover:bg-slate-50 transition text-sm font-semibold">
                        Cancel
                    </button>
                    <button onclick="activateEmergencyResponse()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm font-semibold flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Activate Emergency Response
                    </button>
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

<script>
    // ============================================================
    // FILTER ALERTS
    // ============================================================
    function filterAlerts(severity) {
        // Update active filter button
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.classList.remove('active', 'bg-brand-dark', 'text-white');
            btn.classList.add('bg-white', 'text-slate-700');
        });
        
        if (severity === 'all') {
            document.getElementById('filter-all').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (severity === 'Critical') {
            document.getElementById('filter-critical').classList.add('active', 'bg-brand-dark', 'text-white');
        } else if (severity === 'Warning') {
            document.getElementById('filter-warning').classList.add('active', 'bg-brand-dark', 'text-white');
        }
        
        // Filter alerts
        const alerts = document.querySelectorAll('.alert-item');
        alerts.forEach(alert => {
            if (severity === 'all' || alert.dataset.severity === severity) {
                alert.style.display = 'flex';
            } else {
                alert.style.display = 'none';
            }
        });
    }

    // ============================================================
    // RESOLVE ALERT
    // ============================================================
    function resolveAlert(alertId) {
        if (confirm('Resolve alert ' + alertId + '?')) {
            showToast('✅ Alert ' + alertId + ' resolved!', 'success');
            // In production, this would send an AJAX request
        }
    }

    // ============================================================
    // ESCALATE ALERT
    // ============================================================
    function escalateAlert(alertId) {
        if (confirm('Escalate alert ' + alertId + ' to next level?')) {
            showToast('⬆️ Alert ' + alertId + ' escalated!', 'warning');
            // In production, this would send an AJAX request
        }
    }

    // ============================================================
    // MARK ALL READ
    // ============================================================
    function markAllRead() {
        showToast('✅ All alerts marked as read', 'success');
    }

    // ============================================================
    // DEPLOY TEAM
    // ============================================================
    function deployTeam() {
        showToast('🚀 Response team deployed!', 'success');
    }

    // ============================================================
    // ASSIGN TEAM
    // ============================================================
    function assignTeam(teamName) {
        showToast('👨‍⚕️ ' + teamName + ' assigned to alert!', 'success');
    }

    // ============================================================
    // EMERGENCY RESPONSE
    // ============================================================
    function showResponseModal() {
        document.getElementById('emergencyModal').classList.remove('hidden');
        document.getElementById('emergencyModal').classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.getElementById(id).classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }

    function activateEmergencyResponse() {
        const alertId = document.getElementById('emergencyAlertSelect').value;
        const team = document.getElementById('emergencyTeamSelect').value;
        const instructions = document.getElementById('emergencyInstructions').value;
        
        if (confirm('🚨 Activate emergency response for ' + alertId + '?')) {
            showToast('🚨 Emergency response activated for ' + alertId + '!', 'danger');
            closeModal('emergencyModal');
        }
    }

    // ============================================================
    // REFRESH ALERTS
    // ============================================================
    function refreshAlerts() {
        showToast('🔄 Refreshing alerts...', 'info');
        setTimeout(() => {
            showToast('✅ Alerts refreshed!', 'success');
        }, 1500);
    }

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
    // CLOSE MODAL ON BACKDROP CLICK
    // ============================================================
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
    // ESC KEY TO CLOSE MODAL
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
</style>

<?php include_once '../../includes/footer.php'; ?>