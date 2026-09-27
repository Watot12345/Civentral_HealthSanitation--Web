    <!-- ================================================================ -->
    <!--  SECTION 2: GENERATE REPORT                                       -->
    <!-- ================================================================ -->
    <div id="section-generate">
        <!-- ─── DATE RANGE MODAL ─── -->
        <!-- ─── CONFIGURATION MODAL ─── -->
        <div id="generateReportModal" class="fixed inset-0 z-[100] flex items-center justify-center modal-overlay hidden opacity-0 transition-all duration-300" onclick="if(event.target===this) closeGenerateReportModal()">
            <div class="modal-content rounded-3xl max-w-lg w-full mx-4 shadow-2xl overflow-hidden flex flex-col max-h-[90vh] relative bg-white">
                <!-- Header -->
                <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between flex-shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                            <i class="fa-solid fa-sliders"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-[#176B87]">
                                <?= $isStaff ? 'Assigned Work Report Parameters' : ($isDirector ? htmlspecialchars($assignedDept) . ' Report Parameters' : 'Generate Report') ?>
                            </h3>
                            <p class="text-xs text-slate-400">Configure parameters &amp; format settings</p>
                        </div>
                    </div>
                    <button onclick="closeGenerateReportModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                        <i class="fa-solid fa-xmark text-xl"></i>
                    </button>
                </div>
                
                <?php
                    require_once __DIR__ . '/../../app/services/GroqAiService.php';
                    $groqService = new GroqAiService();
                    $requestsLeft = $groqService->getRemainingRequests();
                    $badgeColor = $requestsLeft > 2 ? 'bg-cyan-100 text-cyan-700 border-cyan-200' : 'bg-red-100 text-red-700 border-red-200';
                ?>
                <div class="px-6 py-2 bg-[#B4D4FF]/10 border-b border-[#B4D4FF]/30 flex justify-between items-center">
                    <span class="text-xs font-medium text-[#176B87] flex items-center gap-1.5">
                        <i class="fa-solid fa-robot"></i> AI Quota Status:
                    </span>
                    <span id="aiQuotaBadge" class="px-2.5 py-1 <?= $badgeColor ?> border rounded-full text-xs font-bold shadow-sm">
                        🤖 <?= $requestsLeft ?> requests left
                    </span>
                </div>

                <!-- Scrollable Body -->
                <div class="px-6 py-5 space-y-4 overflow-y-auto">
                    <!-- Date Range -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider">Report Date Range</label>
                            <div class="flex items-center gap-1">
                                <button type="button" onclick="setDatePreset('this_month')" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition">This Month</button>
                                <button type="button" onclick="setDatePreset('this_year')"  class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition">This Year</button>
                                <button type="button" onclick="setDatePreset('last_30_days')" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition">Last 30 Days</button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" onchange="refreshUI()" />
                            <span class="text-slate-400 text-sm flex-shrink-0">to</span>
                            <input type="date" id="endDate" value="<?= date('Y-m-d') ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" onchange="refreshUI()" />
                        </div>
                    </div>

                    <!-- Visual Graphs Option -->
                    <div class="flex items-center justify-between p-3.5 bg-[#B4D4FF]/10 rounded-xl border border-[#B4D4FF]/30">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-[#176B87]/10 flex items-center justify-center">
                                <i class="fa-solid fa-chart-bar text-[#176B87] text-xs"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Include Visual Graphs</p>
                                <p class="text-[10px] text-slate-400">Attach charts &amp; trend visualizations</p>
                            </div>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="includeVisuals" checked class="sr-only peer">
                            <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#176B87]"></div>
                        </label>
                    </div>

                    <!-- Export Format Cards -->
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-2">Export Format</label>
                        <select id="exportFormat" class="sr-only">
                            <?php foreach ($exportFormats as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="grid grid-cols-3 gap-2">
                            <label id="genFormatPdf" onclick="selectGenerateFormat('pdf')" class="gen-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-[#176B87] bg-[#176B87]/5 cursor-pointer transition-all">
                                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                    <i class="fa-solid fa-file-pdf text-red-600"></i>
                                </div>
                                <span class="text-xs font-semibold text-slate-700">PDF</span>
                                <input type="radio" name="genExportFormat" value="pdf" checked class="sr-only">
                            </label>
                            <label id="genFormatExcel" onclick="selectGenerateFormat('excel')" class="gen-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all">
                                <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                    <i class="fa-solid fa-file-excel text-emerald-600"></i>
                                </div>
                                <span class="text-xs font-semibold text-slate-700">Excel</span>
                                <input type="radio" name="genExportFormat" value="excel" class="sr-only">
                            </label>
                            <label id="genFormatWord" onclick="selectGenerateFormat('word')" class="gen-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all">
                                <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                    <i class="fa-solid fa-file-word text-blue-600"></i>
                                </div>
                                <span class="text-xs font-semibold text-slate-700">Word</span>
                                <input type="radio" name="genExportFormat" value="word" class="sr-only">
                            </label>
                        </div>
                    </div>

                    <!-- Department Module -->
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Department Module</label>
                        <select id="reportType" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87] transition" onchange="loadLiveReportData()">
                            <?php foreach ($availableReportTypes as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Footer Actions -->
                <div class="px-6 py-4 border-t border-[#B4D4FF]/30 bg-white flex items-center justify-between flex-shrink-0">
                    <button id="resetBtn" onclick="resetFilters()" class="px-5 py-2.5 rounded-xl text-xs font-semibold border border-slate-200 text-slate-600 hover:bg-slate-50 transition flex items-center gap-2">
                        <i class="fa-regular fa-circle-xmark"></i> Reset
                    </button>
                    <button id="generateBtn" onclick="generateReport(); closeGenerateReportModal();" class="btn-primary px-6 py-2.5 rounded-xl text-xs font-bold text-white flex items-center gap-2 shadow-sm">
                        <i class="fa-solid fa-play"></i> Generate Report
                    </button>
                </div>
            </div>
        </div>



        <!-- PRINT HEADER -->
        <div id="printReportHeader">
            <img src="../assets/images/logo.png" alt="Logo">
            <h1>Health Sanitation Management Caloocan</h1>
            <h2 id="printReportSubtitle">Custom Executive Report</h2>
        </div>

        <!-- ─── REPORT GENERATING LOADING OVERLAY ─── -->
        <div id="reportGeneratingLoader" class="fixed inset-0 z-[150] flex items-center justify-center hidden opacity-0 transition-all duration-300" style="background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px);">
            <div class="bg-white rounded-3xl p-8 max-w-md w-[90%] mx-4 shadow-2xl border border-[#B4D4FF]/40 text-center relative overflow-hidden flex flex-col items-center">
                <div class="absolute -top-12 -right-12 w-36 h-36 rounded-full bg-[#B4D4FF]/30 blur-2xl pointer-events-none"></div>
                <div class="absolute -bottom-12 -left-12 w-36 h-36 rounded-full bg-[#86B6F6]/20 blur-2xl pointer-events-none"></div>

                <div class="relative w-20 h-20 mb-5 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-4 border-[#B4D4FF]/30 border-t-[#176B87] animate-spin"></div>
                    <div class="w-14 h-14 rounded-full bg-gradient-to-tr from-[#176B87] to-[#0F4A5E] text-white flex items-center justify-center shadow-lg shadow-[#176B87]/30">
                        <i id="loaderDynamicIcon" class="fa-solid fa-wand-magic-sparkles text-xl animate-pulse"></i>
                    </div>
                </div>

                <h3 class="text-lg font-bold text-slate-800 mb-1">Generating Live Report</h3>
                <p id="loaderDynamicStep" class="text-xs text-slate-500 mb-5 min-h-[32px] flex items-center justify-center px-2 leading-relaxed">
                    Querying departmental records and live transactions...
                </p>

                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden p-0.5 border border-slate-200/60 mb-2">
                    <div id="loaderProgressBar" class="h-full bg-gradient-to-r from-[#176B87] via-[#86B6F6] to-[#176B87] rounded-full transition-all duration-500 ease-out" style="width: 25%;"></div>
                </div>
                <div class="w-full flex justify-between items-center text-[10px] font-semibold text-slate-400">
                    <span>Compiling Analytics</span>
                    <span id="loaderProgressText">25%</span>
                </div>
            </div>
        </div>

        <!-- ─── REPORT PREVIEW MODAL ─── -->
        <div id="reportPreview" class="fixed inset-0 z-[110] flex items-center justify-center modal-overlay hidden opacity-0 transition-all duration-300" onclick="if(event.target===this) closeReportPreviewModal()">
            <div class="modal-content w-[96%] max-w-6xl max-h-[92vh] overflow-hidden flex flex-col bg-slate-50 rounded-3xl shadow-2xl relative border border-[#B4D4FF]/40">
                
                <!-- Unified Sleek Header Bar -->
                <div class="px-6 py-4 bg-white border-b border-[#B4D4FF]/30 flex flex-wrap items-center justify-between gap-3 flex-shrink-0 z-20" id="previewHeader">
                    <div class="flex items-center gap-3.5">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-[#176B87]/15 to-[#86B6F6]/30 flex items-center justify-center text-[#176B87] shadow-xs flex-shrink-0">
                            <i class="fa-solid fa-file-waveform text-xl"></i>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 id="reportHeaderTitle" class="text-lg sm:text-xl font-bold text-slate-800 tracking-tight">Executive Report Preview</h2>
                                <span id="previewFormatBadge" class="hidden sm:inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-[#176B87]/10 text-[#176B87] border border-[#176B87]/20">
                                    <i class="fa-solid fa-file-pdf text-red-500"></i> PDF Mode
                                </span>
                            </div>
                            <div class="flex items-center gap-2 mt-0.5 text-xs text-slate-400">
                                <span>Generated: <strong id="reportDateText" class="text-slate-600 font-medium"></strong></span>
                                <span>&bull;</span>
                                <span class="inline-flex items-center gap-1 text-emerald-600 font-medium text-[11px]">
                                    <i class="fa-solid fa-circle-check text-[9px]"></i> Live Data Synced
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Header Action Buttons -->
                    <div class="flex items-center gap-2">
                        <button onclick="printCustomReport()" class="h-10 px-3.5 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 text-xs font-semibold transition inline-flex items-center gap-1.5" title="Print Report">
                            <i class="fa-solid fa-print text-sm"></i> <span class="hidden md:inline">Print</span>
                        </button>
                        <button onclick="triggerSelectedDownload()" class="btn-primary h-10 px-5 rounded-xl text-white text-xs font-bold shadow-md hover:shadow-lg transition inline-flex items-center gap-2">
                            <i class="fa-solid fa-download"></i> Continue Download
                        </button>
                        <button onclick="closeReportPreviewModal()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition ml-1" title="Close Preview">
                            <i class="fa-solid fa-xmark text-lg"></i>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Body Content -->
                <div class="p-5 sm:p-6 space-y-6 overflow-y-auto flex-1 custom-scrollbar">
                    
                    <!-- 1. Visual Graphs Grid -->
                    <div id="tabChart" class="tab-content">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            
                            <!-- Left: Operational Distribution (2 cols) -->
                            <div class="lg:col-span-2 bg-white rounded-2xl p-5 border border-[#B4D4FF]/30 shadow-xs relative overflow-hidden flex flex-col">
                                <div class="flex items-center justify-between mb-3 flex-shrink-0">
                                    <div class="flex items-center gap-2">
                                        <div class="w-2 h-4 rounded-full bg-[#176B87]"></div>
                                        <h4 id="chartBarTitle" class="text-sm font-bold text-slate-800">Operational Distribution</h4>
                                    </div>
                                    <span id="chartBarDate" class="text-[11px] font-semibold text-slate-500 bg-slate-100 px-3 py-1 rounded-full border border-slate-200"></span>
                                </div>
                                <div class="chart-container h-64 sm:h-72 w-full relative">
                                    <canvas id="barChart"></canvas>
                                </div>
                            </div>

                            <!-- Right: Doughnut & Trend (1 col) -->
                            <div class="space-y-4 flex flex-col">
                                <!-- Overall Status Doughnut -->
                                <div class="bg-white rounded-2xl p-4 sm:p-5 border border-[#B4D4FF]/30 shadow-xs relative overflow-hidden flex-1 flex flex-col">
                                    <div class="flex items-center gap-2 mb-2 flex-shrink-0">
                                        <div class="w-2 h-4 rounded-full bg-emerald-500"></div>
                                        <h4 class="text-sm font-bold text-slate-800">Overall Status</h4>
                                    </div>
                                    <div class="chart-container h-48 sm:h-52 w-full relative flex items-center justify-center">
                                        <canvas id="doughnutChart"></canvas>
                                    </div>
                                </div>

                                <!-- Trend Line -->
                                <div class="bg-white rounded-2xl p-4 border border-[#B4D4FF]/30 shadow-xs relative overflow-hidden flex-shrink-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <div class="w-2 h-4 rounded-full bg-blue-500"></div>
                                        <h4 class="text-xs font-bold text-slate-700">Trend (last 6 mo)</h4>
                                    </div>
                                    <div class="chart-container h-24 w-full relative">
                                        <canvas id="lineChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. AI Executive Summary & Performance Metrics Grid -->
                    <div id="tabSummary" class="tab-content">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                            
                            <!-- Left: Executive Summary Narrative & Actionable Recommendations -->
                            <div class="bg-white rounded-2xl p-5 border border-[#B4D4FF]/30 shadow-xs flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-[#176B87] flex items-center gap-1.5">
                                            <i class="fa-solid fa-brain text-sm"></i> Executive Summary Narrative
                                        </h4>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-cyan-100 text-cyan-800 border border-cyan-200">
                                            AI Analyzed
                                        </span>
                                    </div>
                                    
                                    <div class="p-4 bg-slate-50/90 rounded-2xl border-l-4 border-l-[#176B87] border border-slate-200/80 text-xs text-slate-700 leading-relaxed" id="summaryText">
                                        <p class="text-slate-400 italic">Synthesizing executive overview...</p>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-1.5" id="summaryTags"></div>
                                </div>

                                <div class="mt-5 pt-4 border-t border-slate-100">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#176B87] flex items-center gap-1.5 mb-2.5">
                                        <i class="fa-solid fa-list-check text-sm"></i> Actionable Recommendations
                                    </h4>
                                    <ul class="space-y-2 text-xs text-slate-600" id="aiRecommendationsList">
                                        <li class="flex items-start gap-2">
                                            <i class="fa-solid fa-circle-check text-emerald-500 mt-0.5 text-xs flex-shrink-0"></i>
                                            <span>Reallocate response staff to high-density zones.</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Right: Department Performance Metrics & Key AI Findings -->
                            <div class="bg-white rounded-2xl p-5 border border-[#B4D4FF]/30 shadow-xs flex flex-col justify-between">
                                <div>
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#176B87] flex items-center gap-1.5 mb-3">
                                        <i class="fa-solid fa-chart-pie text-sm"></i> Department Performance Metrics
                                    </h4>
                                    <div class="grid grid-cols-2 gap-3 mb-5" id="summaryMetrics">
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                            <span class="text-[10px] font-semibold text-slate-500 uppercase block mb-1" id="labelMetric1">Compliance / Approval Rate</span>
                                            <span id="metricCompliance" class="text-base font-bold text-emerald-600">0%</span>
                                        </div>
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                            <span class="text-[10px] font-semibold text-slate-500 uppercase block mb-1" id="labelMetric2">Encounter / Coverage</span>
                                            <span id="metricCoverage" class="text-base font-bold text-[#176B87]">0%</span>
                                        </div>
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                            <span class="text-[10px] font-semibold text-slate-500 uppercase block mb-1" id="labelMetric3">Resolution Rate</span>
                                            <span id="metricResolution" class="text-base font-bold text-amber-600">0%</span>
                                        </div>
                                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/70">
                                            <span class="text-[10px] font-semibold text-slate-500 uppercase block mb-1" id="labelMetric4">Operational Index</span>
                                            <span id="metricParticipation" class="text-base font-bold text-indigo-600">0%</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="pt-4 border-t border-slate-100">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-[#176B87] flex items-center gap-1.5 mb-2.5">
                                        <i class="fa-solid fa-wand-magic-sparkles text-amber-500 text-sm"></i> Key AI Findings
                                    </h4>
                                    <div class="space-y-2 text-xs" id="aiKeyFindings">
                                        <div class="p-2.5 bg-indigo-50/70 rounded-xl border border-indigo-100 text-indigo-950 font-semibold text-xs">
                                            Department compliance efficiency evaluated across all active records.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div> <!-- end scrollable body -->
            </div> <!-- end modal-content -->
        </div> <!-- end reportPreview -->
</div> <!-- end section-generate -->
