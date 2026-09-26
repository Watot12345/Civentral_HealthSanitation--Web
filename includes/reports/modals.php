    <!-- ─── SAVE TEMPLATE MODAL ─── -->
    <div id="saveTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeSaveTemplateModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-floppy-disk"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Save Template</h3>
                        <p class="text-xs text-slate-400">Enter a name for your template</p>
                    </div>
                </div>
                <button onclick="closeSaveTemplateModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5">
                <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Template Name</label>
                <input type="text" id="templateNameInput" placeholder="e.g. Weekly Compliance Report" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 focus:border-[#176B87] focus:ring-2 focus:ring-[#176B87]/20 outline-none transition" />
                <p class="text-xs text-slate-400 mt-1.5">This will save all current filter settings.</p>
            </div>
            <div class="px-6 py-4 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end gap-3">
                <button onclick="closeSaveTemplateModal()" class="px-5 py-2 rounded-xl text-sm font-medium border border-[#B4D4FF]/40 bg-white/50 text-slate-600 hover:bg-[#B4D4FF]/20 transition">Cancel</button>
                <button onclick="saveTemplate()" class="btn-primary px-6 py-2 rounded-xl text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk"></i> Save
                </button>
            </div>
        </div>
    </div>

    <!-- ─── LOAD TEMPLATE MODAL ─── -->
    <div id="templateModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeTemplateModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Load Template</h3>
                        <p class="text-xs text-slate-400">Select a saved template to apply</p>
                    </div>
                </div>
                <button onclick="closeTemplateModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5">
                <div id="templateList" class="space-y-2 max-h-72 overflow-y-auto">
                    <!-- Templates will be rendered here -->
                    <p class="text-sm text-slate-400 text-center py-8">No saved templates found.</p>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end">
                <button onclick="closeTemplateModal()" class="text-sm text-slate-400 hover:text-slate-600 transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ─── CREATE / EDIT TEMPLATE MODAL ─── -->
    <div id="editTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeEditTemplateModal()">
        <div class="modal-content rounded-3xl max-w-lg w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]" id="editTemplateModalTitle">Configure Template</h3>
                        <p class="text-xs text-slate-400">Save standardized report definitions</p>
                    </div>
                </div>
                <button onclick="closeEditTemplateModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5 space-y-4">
                <input type="hidden" id="editTemplateId" value="" />
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Template Name</label>
                    <input type="text" id="editTemplateName" placeholder="e.g. Monthly Compliance Audit" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Description</label>
                    <textarea id="editTemplateDesc" rows="2" placeholder="Brief explanation of this template scope..." class="w-full rounded-xl px-4 py-2 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]"></textarea>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Report Type</label>
                        <select id="editTemplateType" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none">
                            <option value="unified">Unified Global Report (All Modules)</option>
                            <option value="health_center">Health Center Services</option>
                            <option value="sanitation">Sanitation Permits</option>
                            <option value="immunization">Immunization &amp; Nutrition</option>
                            <option value="wastewater">Wastewater Services</option>
                            <option value="surveillance">Health Surveillance</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Department</label>
                        <input type="text" id="editTemplateDept" value="<?= htmlspecialchars($assignedDept) ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none <?= $isAdmin ? '' : 'bg-slate-100 cursor-not-allowed pointer-events-none' ?>" />
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end gap-3">
                <button onclick="closeEditTemplateModal()" class="px-5 py-2 rounded-xl text-sm font-medium border border-[#B4D4FF]/40 bg-white/50 text-slate-600 hover:bg-[#B4D4FF]/20 transition">Cancel</button>
                <button onclick="saveTemplateForm()" class="btn-primary px-6 py-2 rounded-xl text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk"></i> Save Template
                </button>
            </div>
        </div>
    </div>

    <!-- ─── FULL-SCREEN SCHEDULE & EMAIL DISPATCH LOADING SCREEN ─── -->
    <div id="scheduleLoadingScreen" class="fixed inset-0 flex items-center justify-center bg-slate-900/70 backdrop-blur-md hidden opacity-0 transition-all duration-300" style="z-index: 9999;">
        <div class="bg-white rounded-3xl p-8 max-w-sm w-full mx-4 shadow-2xl border border-[#B4D4FF]/40 text-center flex flex-col items-center">
            <div class="relative w-20 h-20 mb-5 flex items-center justify-center">
                <div class="absolute inset-0 rounded-full border-4 border-[#B4D4FF]/30 border-t-[#176B87] animate-spin"></div>
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-[#176B87] to-[#0F4A5E] flex items-center justify-center text-white shadow-lg">
                    <i class="fa-solid fa-paper-plane text-xl animate-pulse"></i>
                </div>
            </div>
            <h3 class="text-lg font-bold text-[#176B87]" id="scheduleLoadingTitle">Sending Email &amp; Saving Schedule</h3>
            <p class="text-xs text-slate-500 mt-2 leading-relaxed max-w-xs" id="scheduleLoadingSubtext">Generating executive report document and dispatching directly to recipient email inbox...</p>
            
            <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden mt-6 relative">
                <div class="h-full bg-gradient-to-r from-[#86B6F6] via-[#176B87] to-[#0F4A5E] rounded-full animate-pulse w-full"></div>
            </div>
            <span class="text-[10px] text-slate-400 mt-2.5 font-medium">Please wait while the server processes SMTP delivery...</span>
        </div>
    </div>

    <!-- ─── SCHEDULE MODAL ─── -->
    <div id="scheduleModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeScheduleModal()">
        <div class="modal-content rounded-3xl max-w-lg w-full mx-4 shadow-2xl overflow-hidden flex flex-col max-h-[95vh] relative">

            <!-- Loading Overlay -->
            <div id="scheduleLoadingOverlay" class="absolute inset-0 z-20 bg-white/90 backdrop-blur-sm flex flex-col items-center justify-center p-6 text-center hidden opacity-0 transition-all duration-300">
                <div class="relative w-16 h-16 mb-4 flex items-center justify-center">
                    <div class="absolute inset-0 rounded-full border-4 border-[#B4D4FF]/30 border-t-[#176B87] animate-spin"></div>
                    <div class="w-10 h-10 rounded-full bg-[#176B87]/10 flex items-center justify-center text-[#176B87]">
                        <i class="fa-solid fa-paper-plane text-lg animate-bounce"></i>
                    </div>
                </div>
                <h4 class="text-base font-bold text-[#176B87]">Scheduling & Dispatching Email...</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-xs" id="scheduleLoadingSubtext">Generating report package and dispatching notification emails to recipients...</p>
                <div class="w-48 h-1.5 bg-slate-100 rounded-full overflow-hidden mt-4">
                    <div class="h-full bg-gradient-to-r from-[#86B6F6] to-[#176B87] rounded-full animate-pulse w-full"></div>
                </div>
            </div>

            <!-- Header -->
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Schedule Report</h3>
                        <p class="text-xs text-slate-400">Automate report generation &amp; delivery</p>
                    </div>
                </div>
                <button onclick="closeScheduleModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>

            <!-- Scrollable Body -->
            <div class="px-6 py-5 space-y-4 overflow-y-auto">

                <!-- Report Module -->
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Report Module</label>
                    <select id="scheduleReportType" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87] transition">
                        <?php foreach ($availableReportTypes as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider">Report Date Range</label>
                        <div class="flex items-center gap-1">
                            <button type="button" onclick="setScheduleDatePreset('this_month')" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition">This Month</button>
                            <button type="button" onclick="setScheduleDatePreset('this_year')"  class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition">This Year</button>
                            <button type="button" onclick="setScheduleDatePreset('last_30_days')" class="px-2 py-0.5 text-[10px] font-medium bg-slate-100 text-slate-600 rounded-md hover:bg-[#176B87] hover:text-white transition">Last 30 Days</button>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <input type="date" id="scheduleReportStart" value="<?= date('Y-m-d', strtotime('-30 days')) ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" />
                        <span class="text-slate-400 text-sm flex-shrink-0">to</span>
                        <input type="date" id="scheduleReportEnd" value="<?= date('Y-m-d') ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" />
                    </div>
                </div>

                <!-- Include Visual Graphs -->
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
                        <input type="checkbox" id="scheduleIncludeVisuals" checked class="sr-only peer">
                        <div class="w-10 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-[#176B87]"></div>
                    </label>
                </div>

                <!-- Schedule Title -->
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Schedule Title</label>
                    <input type="text" id="scheduleTitleInput" placeholder="e.g. Weekly Health Center Summary" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" />
                </div>

                <!-- Frequency + Start Date + Time -->
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Frequency</label>
                        <select id="scheduleFrequencySelect" class="w-full rounded-xl px-3 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]">
                            <option value="Daily">Daily</option>
                            <option value="Weekly" selected>Weekly</option>
                            <option value="Monthly">Monthly</option>
                            <option value="Quarterly">Quarterly</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Start Date</label>
                        <input type="date" id="scheduleStartDateInput" value="<?= date('Y-m-d') ?>" class="w-full rounded-xl px-3 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Time</label>
                        <input type="time" id="scheduleTimeInput" value="08:00" class="w-full rounded-xl px-3 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" />
                    </div>
                </div>

                <!-- Recipients -->
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Recipients (email)</label>
                    <div class="relative">
                        <i class="fa-solid fa-at absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                        <input type="text" id="scheduleRecipientsInput" placeholder="admin@caloocan.gov.ph, team@caloocan.gov.ph" class="w-full rounded-xl pl-8 pr-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none focus:border-[#176B87]" />
                    </div>
                    <p class="text-[10px] text-slate-400 mt-1">Separate multiple emails with commas.</p>
                </div>

                <!-- Format Cards -->
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-2">Export Format</label>
                    <div class="grid grid-cols-3 gap-2">
                        <label id="schedFormatPDF" onclick="selectScheduleFormat('PDF')" class="sched-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-[#176B87] bg-[#176B87]/5 cursor-pointer transition-all">
                            <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center">
                                <i class="fa-solid fa-file-pdf text-red-600"></i>
                            </div>
                            <span class="text-xs font-semibold text-slate-700">PDF</span>
                            <input type="radio" name="scheduleFormat" value="PDF" checked class="sr-only">
                        </label>
                        <label id="schedFormatExcel" onclick="selectScheduleFormat('Excel')" class="sched-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all">
                            <div class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <i class="fa-solid fa-file-excel text-emerald-600"></i>
                            </div>
                            <span class="text-xs font-semibold text-slate-700">Excel</span>
                            <input type="radio" name="scheduleFormat" value="Excel" class="sr-only">
                        </label>
                        <label id="schedFormatWord" onclick="selectScheduleFormat('Word')" class="sched-fmt-card flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 border-slate-200 bg-white cursor-pointer transition-all">
                            <div class="w-8 h-8 rounded-lg bg-blue-100 flex items-center justify-center">
                                <i class="fa-solid fa-file-word text-blue-600"></i>
                            </div>
                            <span class="text-xs font-semibold text-slate-700">Word</span>
                            <input type="radio" name="scheduleFormat" value="Word" class="sr-only">
                        </label>
                    </div>
                </div>

                <!-- Status message -->
                <div id="scheduleStatusMsg" class="hidden"></div>

                <!-- Download section (shown after scheduling) -->
                <div id="scheduleDownloadSection" class="hidden">
                    <div class="flex items-center justify-between p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <div class="flex items-center gap-2.5">
                            <div class="w-7 h-7 rounded-lg bg-emerald-100 flex items-center justify-center">
                                <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-emerald-800">Schedule saved!</p>
                                <p class="text-[10px] text-emerald-600" id="scheduleDownloadLabel">Download a copy now</p>
                            </div>
                        </div>
                        <button onclick="downloadScheduledReport()" id="scheduleDownloadBtn" class="flex-shrink-0 px-4 py-2 rounded-xl text-xs font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition flex items-center gap-1.5 shadow-sm">
                            <i class="fa-solid fa-download"></i> Download
                        </button>
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-between items-center gap-3 flex-shrink-0">
                <button onclick="closeScheduleModal()" class="px-5 py-2 rounded-xl text-sm font-medium border border-[#B4D4FF]/40 bg-white/50 text-slate-600 hover:bg-[#B4D4FF]/20 transition">Cancel</button>
                <button onclick="saveSchedule()" id="scheduleSubmitBtn" class="btn-primary px-6 py-2 rounded-xl text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk"></i>
                    <span id="scheduleSubmitBtnText">Schedule &amp; Send</span>
                </button>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- ─── ADMIN DEPARTMENT COMPARISON MODAL ─── -->
    <div id="deptCompareModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeDeptCompareModal()">
        <div class="modal-content rounded-3xl max-w-3xl w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-purple-100 flex items-center justify-center text-purple-700 font-bold text-lg">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-[#176B87]">Citywide Department Compliance Benchmark</h3>
                        <p class="text-xs text-slate-400">Comparative metrics across all Caloocan municipal health &amp; sanitation departments</p>
                    </div>
                </div>
                <button onclick="closeDeptCompareModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5 max-h-[70vh] overflow-y-auto">
                <div class="table-wrap overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs font-semibold text-[#176B87] uppercase tracking-wider border-b border-[#B4D4FF]/30">
                            <tr>
                                <th class="pb-3 pr-4">Department</th>
                                <th class="pb-3 pr-4">Total Records</th>
                                <th class="pb-3 pr-4">Compliance %</th>
                                <th class="pb-3 pr-4">Urgent Issues</th>
                                <th class="pb-3 pr-4">Operational Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#B4D4FF]/20" id="deptCompareModalBody">
                            <!-- Populated dynamically -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end">
                <button onclick="closeDeptCompareModal()" class="px-5 py-2 rounded-xl text-sm font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition">Close</button>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <!-- ─── DELETE TEMPLATE CONFIRMATION MODAL ─── -->
    <div id="deleteTemplateModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeDeleteTemplateModal()">
        <div class="modal-content rounded-3xl max-w-sm w-full mx-4 shadow-2xl overflow-hidden bg-white/95 backdrop-blur-md border border-rose-100">
            <div class="px-6 py-5 border-b border-rose-100 flex items-center justify-between bg-rose-50/50">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-rose-100 flex items-center justify-center text-rose-600">
                        <i class="fa-regular fa-trash-can"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Delete Template</h3>
                        <p class="text-xs text-rose-500 font-medium">Confirm Permanent Deletion</p>
                    </div>
                </div>
                <button onclick="closeDeleteTemplateModal()" class="p-1.5 rounded-lg hover:bg-rose-100 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5 text-center">
                <p class="text-sm font-medium text-slate-600">Are you sure you want to delete this report template?</p>
                <p id="deleteTemplateTargetName" class="text-xs font-bold text-slate-800 mt-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl truncate"></p>
                <p class="text-[11px] text-slate-400 mt-2">This action cannot be undone.</p>
            </div>
            <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end gap-3">
                <button onclick="closeDeleteTemplateModal()" class="px-4 py-2 rounded-xl text-xs font-semibold border border-slate-200 bg-white text-slate-600 hover:bg-slate-100 transition">Cancel</button>
                <button onclick="confirmDeleteTemplateAction()" class="btn-primary px-5 py-2 rounded-xl text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white transition flex items-center gap-1.5 shadow-sm" style="background:#e11d48;">
                    <i class="fa-regular fa-trash-can"></i> Delete
                </button>
            </div>
        </div>
    </div>

    <!-- ─── TOAST ─── -->
    <div id="toast" class="fixed bottom-6 right-6 z-[60] text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 translate-y-20 opacity-0 transition-all duration-500 pointer-events-none" style="background: #176B87;">
        <i id="toastIcon" class="fa-regular fa-circle-check text-[#B4D4FF] text-lg"></i>
        <span class="text-sm font-medium" id="toastMessage">Report generated successfully!</span>
        <button onclick="hideToast()" class="ml-2 text-white/60 hover:text-white transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
