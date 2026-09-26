    <!-- ================================================================ -->
    <!--  SECTION 2: GENERATE REPORT                                       -->
    <!-- ================================================================ -->
    <div id="section-generate">
        <!-- ─── DATE RANGE MODAL ─── -->
        <!-- ─── CONFIGURATION MODAL ─── -->
        <div id="generateReportModal" class="fixed inset-0 z-[100] flex items-center justify-center hidden" style="background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.3s ease;">
            <div class="bg-white rounded-3xl w-full max-w-lg mx-4 shadow-2xl overflow-hidden relative flex flex-col max-h-[90vh]">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87] flex items-center gap-2">
                            <i class="fa-solid fa-sliders text-[#86B6F6] text-sm"></i>
                            <?= $isStaff ? 'Assigned Work Report Parameters' : ($isDirector ? htmlspecialchars($assignedDept) . ' Report Parameters' : 'System Report Configuration') ?>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">
                            <?= $isStaff ? 'Scoped strictly to your assigned department and designated facility work' : ($isDirector ? 'Scoped to ' . htmlspecialchars($assignedDept) . ' departmental operations and personnel' : 'Global administrative configuration with cross-department access') ?>
                        </p>
                    </div>
                    <button onclick="closeGenerateReportModal()" class="w-8 h-8 flex items-center justify-center rounded-xl bg-slate-200/50 text-slate-500 hover:bg-rose-100 hover:text-rose-600 transition">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
                
                <?php
                    require_once __DIR__ . '/../../app/services/GroqAiService.php';
                    $groqService = new GroqAiService();
                    $requestsLeft = $groqService->getRemainingRequests();
                    $badgeColor = $requestsLeft > 2 ? 'bg-cyan-100 text-cyan-700' : 'bg-red-100 text-red-700';
                ?>
                <div class="px-6 py-2 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                    <span class="text-xs font-medium text-slate-500">AI Report Generation Quota:</span>
                    <span id="aiQuotaBadge" class="px-2.5 py-1 <?= $badgeColor ?> rounded-full text-xs font-bold shadow-sm">
                        🤖 <?= $requestsLeft ?> requests left
                    </span>
                </div>

                <div class="p-6 overflow-y-auto">
                    <!-- vertical form layout -->
                    <div class="space-y-5">
                        <!-- Date Range -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-sm font-semibold text-slate-700">
                                    Date Range
                                </label>
                                <div class="flex items-center gap-1.5">
                                    <button onclick="setDatePreset('this_month')" class="px-2 py-1 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition cursor-pointer">This Month</button>
                                    <button onclick="setDatePreset('this_year')" class="px-2 py-1 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition cursor-pointer">This Year</button>
                                    <button onclick="setDatePreset('last_30_days')" class="px-2 py-1 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition cursor-pointer">Last 30 Days</button>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="date" id="startDate" value="<?= date('Y-m-d', strtotime('-90 days')) ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200" onchange="refreshUI()" />
                                <span class="text-slate-400 text-sm">to</span>
                                <input type="date" id="endDate" value="<?= date('Y-m-d', strtotime('+30 days')) ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200" onchange="refreshUI()" />
                            </div>
                        </div>

                        
                        <!-- Visual Graphs Option -->
                        <div class="mt-4">
                            <label class="flex items-center gap-2 text-sm font-semibold text-slate-700 cursor-pointer">
                                <input type="checkbox" id="includeVisuals" checked class="w-4 h-4 text-[#176B87] rounded border-slate-300 focus:ring-[#176B87]">
                                Include Visual Graphs
                            </label>
                        </div>

                        <!-- Export Format -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Export Format
                            </label>
                            <select id="exportFormat" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200">
                                <?php foreach ($exportFormats as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Department Module -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Department Module
                            </label>
                            <select id="reportType" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200" onchange="loadLiveReportData()">
                                <?php foreach ($availableReportTypes as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>



                        </div>
                    </div>
                    <!-- Actions -->
                    <div class="mt-5 flex flex-wrap items-center justify-end gap-4 pt-4 border-t border-slate-100">
                        <div class="flex items-center gap-2">
                            <button id="resetBtn" onclick="resetFilters()" class="px-4 py-2.5 rounded-xl text-sm font-medium border border-slate-200 text-slate-600 hover:bg-slate-50 transition flex items-center gap-2">
                                <i class="fa-regular fa-circle-xmark"></i> Reset
                            </button>
                            <button id="generateBtn" onclick="generateReport(); closeGenerateReportModal();" class="btn-primary px-6 py-2.5 rounded-xl text-sm font-semibold text-white flex items-center gap-2 shadow-sm">
                                <i class="fa-solid fa-play"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        <!-- PRINT HEADER -->
        <div id="printReportHeader">
            <img src="../assets/images/logo.png" alt="Logo">
            <h1>Health Sanitation Management Caloocan</h1>
            <h2 id="printReportSubtitle">Custom Compliance Report</h2>
        </div>

        <!-- ─── REPORT PREVIEW CARD ─── -->
        <!-- ─── REPORT PREVIEW MODAL ─── -->
        <div id="reportPreview" class="fixed inset-0 z-[110] flex items-center justify-center hidden" style="background: rgba(15,23,42,0.6); backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.3s ease;">
            <div class="modal-content w-[95%] max-w-6xl max-h-[90vh] overflow-y-auto bg-white rounded-3xl shadow-2xl relative p-4 sm:p-6">
                <div class="sticky top-0 right-0 z-[120] flex justify-end gap-2 mb-4 bg-white/80 backdrop-blur-md p-2 rounded-2xl">
                    <button onclick="triggerSelectedDownload()" class="h-10 px-6 rounded-xl bg-gradient-to-r from-[#176B87] to-[#0F4A5E] text-white text-sm font-bold hover:opacity-90 transition shadow-md inline-flex items-center gap-2">
                        <i class="fa-solid fa-download"></i> Continue Download
                    </button>
                    <button onclick="closeReportPreviewModal()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-100 text-slate-500 hover:bg-rose-100 hover:text-rose-600 transition">
                        <i class="fa-solid fa-times text-lg"></i>
                    </button>
                </div>
                <div class="report-card rounded-3xl overflow-hidden relative">

                <div class="mb-6 pb-4 border-b border-slate-200 flex items-center justify-between" id="previewHeader">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center">
                            <i class="fa-solid fa-file-invoice text-[#176B87] text-xl"></i>
                        </div>
                        <div>
                            <h2 id="reportHeaderTitle" class="text-xl font-bold text-slate-800">Reports</h2>
                            <p class="text-xs text-slate-500">Generated on: <span id="reportDateText"></span></p>
                        </div>
                    </div>
                </div>

            <div class="card-shape card-shape-4"></div>
            <div class="dot-pattern absolute inset-0"></div>

            <div class="relative z-10">
                <!-- actions -->
                <div id="reportTabsBar" class="hidden">
                    <div class="flex gap-1" id="reportTabs">
                        <button onclick="switchTab('chart')" class="report-tab active px-4 py-2 text-sm font-semibold text-[#176B87] border-b-2 border-[#176B87] hover:bg-slate-50 transition" data-tab="chart">Chart View</button>
                        <button onclick="switchTab('summary')" class="report-tab px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:bg-slate-50 transition" data-tab="summary">AI Summary</button>
                    </div>
                    <div id="reportExportActions" class="flex items-center gap-2 pb-2">
                        <?php if ($canExport): ?>
                        <button onclick="exportPDF()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export PDF"><i class="fa-solid fa-file-pdf"></i></button>
                        <button onclick="exportWord()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export Word"><i class="fa-solid fa-file-word"></i></button>
                        <?php endif; ?>
                        <button onclick="printCustomReport()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Print"><i class="fa-solid fa-print"></i></button>
                        <button onclick="openScheduleModal()" class="ml-1 h-8 px-3 rounded-lg bg-[#B4D4FF]/30 text-[#176B87] text-xs font-medium hover:bg-[#86B6F6]/40 transition inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-clock"></i> Schedule
                        </button>
                        <button onclick="triggerSelectedDownload()" class="ml-2 h-8 px-4 rounded-lg bg-gradient-to-r from-[#176B87] to-[#0F4A5E] text-white text-xs font-bold hover:opacity-90 transition shadow-md inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-download"></i> Download Report
                        </button>
                    </div>
                </div>

                <!-- status & date filters -->
                <div class="hidden">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="text-xs font-medium text-slate-500">Filter Status:</span>
                        <div class="flex flex-wrap gap-1.5" id="statusChips">
                            <span class="filter-chip active px-3 py-1 rounded-full text-xs font-medium border border-slate-200 cursor-pointer hover:bg-slate-100 transition" data-status="all">All</span>
                            <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium border border-slate-200 cursor-pointer hover:bg-slate-100 transition" data-status="Compliant">Compliant</span>
                            <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium border border-slate-200 cursor-pointer hover:bg-slate-100 transition" data-status="Non-Compliant">Non-Compliant</span>
                            <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium border border-slate-200 cursor-pointer hover:bg-slate-100 transition" data-status="Pending">Pending</span>
                            <span class="filter-chip px-3 py-1 rounded-full text-xs font-medium border border-slate-200 cursor-pointer hover:bg-slate-100 transition" data-status="Urgent">Urgent</span>
                        </div>
                    </div>
                </div>

                <!-- tab content -->
                <div class="p-5 sm:p-7">
                    <!-- Summary View -->
                    <div id="tabChart" class="tab-content mb-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2 bg-white/40 backdrop-blur-sm rounded-xl p-4 border border-[#B4D4FF]/20 relative overflow-hidden">
                                <div class="relative z-10">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 id="chartBarTitle" class="text-sm font-semibold text-[#176B87]">Sanitation Compliance by Facility</h4>
                                        <span id="chartBarDate" class="text-[10px] text-slate-400 bg-white/50 px-2 py-0.5 rounded-full border border-[#B4D4FF]/20"></span>
                                    </div>
                                    <div class="chart-container h-64">
                                        <canvas id="barChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            <div class="space-y-5">
                                <div class="bg-white/40 backdrop-blur-sm rounded-xl p-4 border border-[#B4D4FF]/20 relative overflow-hidden">
                                    <div class="relative z-10">
                                        <h4 class="text-sm font-semibold text-[#176B87] mb-2">Overall Status</h4>
                                        <div class="chart-container h-36">
                                            <canvas id="doughnutChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-white/40 backdrop-blur-sm rounded-xl p-4 border border-[#B4D4FF]/20 relative overflow-hidden">
                                    <div class="relative z-10">
                                        <h4 class="text-sm font-semibold text-[#176B87] mb-1">Trend (last 6 mo)</h4>
                                        <div class="chart-container h-20">
                                            <canvas id="lineChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Table View -->
                    <div id="tabSummary" class="tab-content mb-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                                    Executive Summary Narrative
                                </h4>
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 text-xs text-slate-700 leading-relaxed shadow-xs" id="summaryText">
                                    <p>Loading dynamic report executive summary...</p>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2" id="summaryTags"></div>

                                <div class="mt-4 pt-3 border-t border-slate-200/80">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                                        Actionable Recommendations
                                    </h4>
                                    <ul class="space-y-1.5 text-xs text-slate-600 list-disc list-inside" id="aiRecommendationsList">
                                        <li>Reallocate response staff to high-density zones.</li>
                                        <li>Conduct weekly supervisory audit reviews.</li>
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                                    Department Performance Metrics
                                </h4>
                                <div class="space-y-2 p-4 bg-slate-50 rounded-2xl border border-slate-200/80 mb-4 text-xs text-slate-700" id="summaryMetrics">
                                    <p><strong id="labelMetric1">Success/Compliance Rate:</strong> <span id="metricCompliance">0%</span></p>
                                    <p><strong id="labelMetric2">Encounter/Inspection Coverage:</strong> <span id="metricCoverage">0%</span></p>
                                    <p><strong id="labelMetric3">Issue Resolution Rate:</strong> <span id="metricResolution">0%</span></p>
                                    <p><strong id="labelMetric4">Department Operational Index:</strong> <span id="metricParticipation">0%</span></p>
                                </div>

                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">
                                    Key AI Findings
                                </h4>
                                <div class="space-y-2 text-xs" id="aiKeyFindings">
                                    <div class="p-2.5 bg-indigo-50/60 rounded-xl border border-indigo-100 text-indigo-900 font-semibold">
                                        Department compliance efficiency evaluated across all active records.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- End Summary View -->
                </div> <!-- end relative z-10 -->
            </div> <!-- end report-card -->
        </div> <!-- end modal-content -->
    </div> <!-- end reportPreview -->
</div> <!-- end section-generate -->
