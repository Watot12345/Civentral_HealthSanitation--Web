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
                            <select id="reportType" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200" onchange="refreshUI()">
                                <?php foreach ($availableReportTypes as $val => $label): ?>
                                    <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>



                        <!-- Facility -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                <?= $isStaff ? 'Assigned Facility' : 'Facility Location' ?>
                            </label>
                            <?php if ($isStaff): ?>
                                <select id="facility" class="w-full rounded-xl px-4 py-2.5 text-sm bg-slate-100/80 border border-slate-200 cursor-not-allowed pointer-events-none" readonly>
                                    <option value="<?= htmlspecialchars($assignedFacility) ?>" selected><?= htmlspecialchars($assignedFacility) ?> (Assigned)</option>
                                </select>
                            <?php elseif ($isDirector): ?>
                                <select id="facility" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200" onchange="refreshUI()">
                                    <option value="<?= htmlspecialchars($assignedDept) ?>">All <?= htmlspecialchars($assignedDept) ?> Facilities</option>
                                    <option value="<?= htmlspecialchars($assignedFacility) ?>"><?= htmlspecialchars($assignedFacility) ?></option>
                                </select>
                            <?php else: ?>
                                <select id="facility" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200" onchange="refreshUI()">
                                    <option value="all">All Facilities</option>
                                    <optgroup label="Facilities &amp; Clinics">
                                        <option value="Central Health Center">Central Health Center</option>
                                        <option value="Eastside Clinic">Eastside Clinic</option>
                                        <option value="West District Hospital">West District Hospital</option>
                                        <option value="North Community Hub">North Community Hub</option>
                                        <option value="South Sanitation Depot">South Sanitation Depot</option>
                                    </optgroup>
                                </select>
                            <?php endif; ?>
                        </div>

                        <!-- Inspector / Personnel -->
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                <?= $isStaff ? 'Assigned Personnel' : 'Personnel / Officer' ?>
                            </label>
                            <?php if ($isStaff): ?>
                                <select id="inspector" class="w-full rounded-xl px-4 py-2.5 text-sm bg-slate-100/80 border border-slate-200 cursor-not-allowed pointer-events-none" readonly>
                                    <option value="<?= htmlspecialchars($userName) ?>" selected><?= htmlspecialchars($userName) ?> (You)</option>
                                </select>
                            <?php else: ?>
                                <select id="inspector" class="w-full rounded-xl px-4 py-2.5 text-sm border border-slate-200" onchange="refreshUI()">
                                    <option value="all">Loading Personnel...</option>
                                </select>
                            <?php endif; ?>
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
            <h2>Custom Compliance Report</h2>
        </div>

        <!-- ─── REPORT PREVIEW CARD ─── -->
        <div id="reportPreview" class="report-card rounded-3xl overflow-hidden mb-8">
            <div class="card-shape card-shape-4"></div>
            <div class="dot-pattern absolute inset-0"></div>

            <div class="relative z-10">
                <!-- actions -->
                <div id="reportTabsBar" class="flex flex-wrap items-center justify-between px-5 sm:px-7 pt-4 pb-0 border-b border-[#B4D4FF]/30 gap-2">
                    <div class="flex gap-1" id="reportTabs">
                        <button onclick="switchTab('chart')" class="report-tab active px-4 py-2 text-sm font-semibold text-[#176B87] border-b-2 border-[#176B87] hover:bg-slate-50 transition" data-tab="chart">Chart View</button>
                        <button onclick="switchTab('table')" class="report-tab px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:bg-slate-50 transition" data-tab="table">Table View</button>
                        <button onclick="switchTab('summary')" class="report-tab px-4 py-2 text-sm font-medium text-slate-500 border-b-2 border-transparent hover:bg-slate-50 transition" data-tab="summary">AI Summary</button>
                    </div>
                    <div id="reportExportActions" class="flex items-center gap-2 pb-2">
                        <?php if ($canExport): ?>
                        <button onclick="exportPDF()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export PDF"><i class="fa-solid fa-file-pdf"></i></button>
                        <button onclick="exportExcel()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export Excel"><i class="fa-solid fa-file-excel"></i></button>
                        <button onclick="exportWord()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Export Word"><i class="fa-solid fa-file-word"></i></button>
                        <?php endif; ?>
                        <button onclick="window.print()" class="w-8 h-8 rounded-lg hover:bg-[#B4D4FF]/30 text-slate-400 hover:text-[#176B87] transition text-sm inline-flex items-center justify-center" title="Print"><i class="fa-solid fa-print"></i></button>
                        <button onclick="openScheduleModal()" class="ml-1 h-8 px-3 rounded-lg bg-[#B4D4FF]/30 text-[#176B87] text-xs font-medium hover:bg-[#86B6F6]/40 transition inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-clock"></i> Schedule
                        </button>
                    </div>
                </div>

                <!-- status & date filters -->
                <div class="flex flex-wrap items-center justify-between px-5 sm:px-7 py-3 border-b border-[#B4D4FF]/20 bg-slate-50/50 gap-4">
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
                    <div id="tabSummary" class="tab-content hidden mb-8">
                        <div class="flex items-center justify-between mb-4 pb-3 border-b border-slate-200/80">
                            <div class="flex items-center gap-2">
                                <span class="px-2.5 py-1 bg-indigo-50 text-indigo-700 rounded-lg text-xs font-bold flex items-center gap-1.5 border border-indigo-100">
                                    <i class="fas fa-brain text-indigo-600"></i> AI Department Intelligence
                                </span>
                                <span id="aiRiskBadge" class="px-2.5 py-1 bg-emerald-100 text-emerald-700 rounded-lg text-xs font-bold">Optimal</span>
                            </div>
                            <button type="button" onclick="fetchAiReportSummary(true)" id="btnGenerateAiSummary" class="px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition flex items-center gap-1.5 shadow-sm cursor-pointer">
                                <i class="fas fa-wand-magic-sparkles text-xs"></i> Generate AI Summary
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                    <i class="fas fa-file-contract text-indigo-600"></i> Executive Summary Narrative
                                </h4>
                                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/80 text-xs text-slate-700 leading-relaxed shadow-xs" id="summaryText">
                                    <p>Loading dynamic report executive summary...</p>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-2" id="summaryTags"></div>

                                <div class="mt-4 pt-3 border-t border-slate-200/80">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                        <i class="fas fa-lightbulb text-amber-500"></i> Actionable Recommendations
                                    </h4>
                                    <ul class="space-y-1.5 text-xs text-slate-600" id="aiRecommendationsList">
                                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-500 text-xs"></i> Reallocate response staff to high-density zones.</li>
                                        <li class="flex items-center gap-2"><i class="fas fa-check-circle text-emerald-500 text-xs"></i> Conduct weekly supervisory audit reviews.</li>
                                    </ul>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                    <i class="fas fa-chart-line text-[#176B87]"></i> Department Performance Metrics
                                </h4>
                                <div class="space-y-3 p-4 bg-slate-50 rounded-2xl border border-slate-200/80 mb-4" id="summaryMetrics">
                                    <div>
                                        <div class="flex justify-between text-xs font-bold text-slate-700"><span>Compliance Rate</span><span class="text-[#176B87]" id="metricCompliance">0%</span></div>
                                        <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-[#176B87] rounded-full transition-all duration-500" style="width:0%" id="metricComplianceBar"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs font-bold text-slate-700"><span>Inspection / Encounter Coverage</span><span class="text-[#176B87]" id="metricCoverage">0%</span></div>
                                        <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-sky-500 rounded-full transition-all duration-500" style="width:0%" id="metricCoverageBar"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs font-bold text-slate-700"><span>Issue Resolution Rate</span><span class="text-[#176B87]" id="metricResolution">0%</span></div>
                                        <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-indigo-500 rounded-full transition-all duration-500" style="width:0%" id="metricResolutionBar"></div></div>
                                    </div>
                                    <div>
                                        <div class="flex justify-between text-xs font-bold text-slate-700"><span>Department Operational Index</span><span class="text-[#176B87]" id="metricParticipation">0%</span></div>
                                        <div class="w-full h-2 bg-slate-200 rounded-full mt-1"><div class="h-2 bg-emerald-500 rounded-full transition-all duration-500" style="width:0%" id="metricParticipationBar"></div></div>
                                    </div>
                                </div>

                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2 flex items-center gap-1.5">
                                    <i class="fas fa-magnifying-glass-chart text-indigo-600"></i> Key AI Findings
                                </h4>
                                <div class="space-y-2 text-xs" id="aiKeyFindings">
                                    <div class="p-2.5 bg-indigo-50/60 rounded-xl border border-indigo-100 text-indigo-900 font-semibold">
                                        Department compliance efficiency evaluated across all active records.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Chart View -->
                    <div id="tabChart" class="tab-content mb-8">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            <div class="lg:col-span-2 bg-white/40 backdrop-blur-sm rounded-xl p-4 border border-[#B4D4FF]/20 relative overflow-hidden">
                                <div class="relative z-10">
                                    <div class="flex items-center justify-between mb-3">
                                        <h4 class="text-sm font-semibold text-[#176B87]">Sanitation Compliance by Facility</h4>
                                        <span class="text-[10px] text-slate-400 bg-white/50 px-2 py-0.5 rounded-full border border-[#B4D4FF]/20">Jul 2026</span>
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
                    <div id="tabTable" class="tab-content hidden mb-8">
                        <div class="table-wrap overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="text-left text-xs font-semibold text-[#176B87] uppercase tracking-wider border-b border-[#B4D4FF]/30">
                                        <th class="pb-3 pr-4">Facility</th>
                                        <th class="pb-3 pr-4">Inspector</th>
                                        <th class="pb-3 pr-4">Date</th>
                                        <th class="pb-3 pr-4">Score</th>
                                        <th class="pb-3 pr-4">Status</th>
                                        <th class="pb-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[#B4D4FF]/20" id="tableViewBody"></tbody>
                            </table>
                        </div>
                        <div class="mt-4 flex flex-wrap items-center justify-between text-xs text-slate-400 border-t border-[#B4D4FF]/30 pt-3 gap-2">
                            <span id="tableViewSummary">Showing 0 of 0 entries</span>
                            <div class="flex gap-2" id="tablePagination">
                                <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" onclick="goToPage(currentPage - 1)">Prev</button>
                                <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" data-page="1" onclick="goToPage(1)">1</button>
                                <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" data-page="2" onclick="goToPage(2)">2</button>
                                <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" data-page="3" onclick="goToPage(3)">3</button>
                                <button class="px-3 py-1 rounded-lg border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/20 transition" onclick="goToPage(currentPage + 1)">Next</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

