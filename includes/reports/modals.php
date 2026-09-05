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
                            <option value="sanitation">Sanitation Inspections</option>
                            <option value="health_center">Health Center Consultations</option>
                            <option value="immunization">Immunization &amp; Nutrition</option>
                            <option value="wastewater">Wastewater Analysis</option>
                            <option value="surveillance">Disease Surveillance</option>
                            <option value="compliance">Compliance Summary</option>
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

    <!-- ─── SCHEDULE MODAL ─── -->
    <div id="scheduleModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeScheduleModal()">
        <div class="modal-content rounded-3xl max-w-lg w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
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
            <div class="px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Schedule Title</label>
                    <input type="text" id="scheduleTitleInput" placeholder="e.g. Weekly Health Center Summary" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Frequency</label>
                    <select id="scheduleFrequencySelect" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none">
                        <option value="Daily">Daily</option>
                        <option value="Weekly" selected>Weekly</option>
                        <option value="Monthly">Monthly</option>
                        <option value="Quarterly">Quarterly</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Start Date</label>
                        <input type="date" id="scheduleStartDateInput" value="<?= date('Y-m-d') ?>" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none" />
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Time</label>
                        <input type="time" id="scheduleTimeInput" value="08:00" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none" />
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Recipients (email)</label>
                    <input type="text" id="scheduleRecipientsInput" placeholder="admin@caloocan.gov.ph, team@caloocan.gov.ph" class="w-full rounded-xl px-4 py-2.5 text-sm border border-[#B4D4FF]/50 outline-none" />
                </div>
                <div>
                    <label class="block text-xs font-semibold text-[#176B87] uppercase tracking-wider mb-1.5">Format</label>
                    <div class="flex gap-4 text-sm">
                        <label class="flex items-center gap-2 text-slate-600"><input type="radio" name="scheduleFormat" value="PDF" checked class="accent-[#176B87]" /> PDF</label>
                        <label class="flex items-center gap-2 text-slate-600"><input type="radio" name="scheduleFormat" value="Excel" class="accent-[#176B87]" /> Excel</label>
                        <label class="flex items-center gap-2 text-slate-600"><input type="radio" name="scheduleFormat" value="Word" class="accent-[#176B87]" /> Word</label>
                    </div>
                </div>
            </div>
            <div class="px-6 py-4 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end gap-3">
                <button onclick="closeScheduleModal()" class="px-5 py-2 rounded-xl text-sm font-medium border border-[#B4D4FF]/40 bg-white/50 text-slate-600 hover:bg-[#B4D4FF]/20 transition">Cancel</button>
                <button onclick="saveSchedule()" class="btn-primary px-6 py-2 rounded-xl text-sm font-semibold text-white flex items-center gap-2">
                    <i class="fa-regular fa-floppy-disk"></i> Schedule
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

    <!-- ─── TOAST ─── -->
    <div id="toast" class="fixed bottom-6 right-6 z-[60] text-white px-5 py-3.5 rounded-xl shadow-2xl flex items-center gap-3 translate-y-20 opacity-0 transition-all duration-500 pointer-events-none" style="background: #176B87;">
        <i id="toastIcon" class="fa-regular fa-circle-check text-[#B4D4FF] text-lg"></i>
        <span class="text-sm font-medium" id="toastMessage">Report generated successfully!</span>
        <button onclick="hideToast()" class="ml-2 text-white/60 hover:text-white transition">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
