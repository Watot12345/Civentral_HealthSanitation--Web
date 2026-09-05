    <!-- ================================================================ -->
    <!--  SECTION 4: SCHEDULED REPORTS                                    -->
    <!-- ================================================================ -->
    <div id="section-scheduled" class="report-section">
        <div class="report-card rounded-b-3xl rounded-tr-3xl p-5 sm:p-7 bg-white border border-[#B4D4FF]/30">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                <div>
                    <h3 class="text-base font-semibold text-[#176B87] flex items-center gap-2">
                        <i class="fa-regular fa-clock text-[#86B6F6]"></i>
                        Automated Report Schedules
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">Manage automated report generation and email distribution</p>
                </div>
                <button onclick="openScheduleModal()" class="btn-primary px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-plus"></i> Schedule New Report
                </button>
            </div>
            
            <div class="p-5 sm:p-7 bg-slate-50/50 rounded-2xl">
                <div class="table-wrap overflow-x-auto bg-white rounded-2xl border border-[#B4D4FF]/30">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs font-semibold text-[#176B87] uppercase tracking-wider border-b border-[#B4D4FF]/30">
                            <tr>
                                <th class="py-3 px-4">Schedule Title</th>
                                <th class="py-3 px-4">Frequency</th>
                                <th class="py-3 px-4">Format</th>
                                <th class="py-3 px-4">Recipients</th>
                                <th class="py-3 px-4">Next Execution</th>
                                <th class="py-3 px-4">Status</th>
                                <th class="py-3 px-4 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#B4D4FF]/20" id="schedulesTableBody">
                            <tr><td colspan="7" class="py-8 text-center text-xs text-slate-400">Loading schedules...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ================================================================ -->
    <!--  SECTION 6 (REMOVED)                                             -->
    <!-- ================================================================ -->

    <!-- ─── VIEW DETAIL MODAL ─── -->
    <div id="viewDetailModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeViewDetailModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-[#B4D4FF]/30 flex items-center justify-center text-[#176B87]">
                        <i class="fa-regular fa-file-lines"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Report Details</h3>
                        <p class="text-xs text-slate-400">Full inspection record</p>
                    </div>
                </div>
                <button onclick="closeViewDetailModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-5" id="viewDetailContent">
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Facility</span><span class="font-medium text-[#176B87]" id="detailFacility">—</span></div>
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Inspector</span><span class="font-medium text-[#176B87]" id="detailInspector">—</span></div>
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Date</span><span class="font-medium text-[#176B87]" id="detailDate">—</span></div>
                    <div class="flex justify-between border-b border-[#B4D4FF]/20 pb-2"><span class="text-slate-500">Score</span><span class="font-medium text-[#176B87]" id="detailScore">—</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Status</span><span class="font-medium" id="detailStatus">—</span></div>
                </div>
            </div>
            <div class="px-6 py-3 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end">
                <button onclick="closeViewDetailModal()" class="text-sm text-slate-400 hover:text-slate-600 transition">Close</button>
            </div>
        </div>
    </div>

    <!-- ─── REPORT RESULT MODAL (PDF / EXCEL / WORD) ─── -->
    <div id="reportResultModal" class="fixed inset-0 z-50 flex items-center justify-center modal-overlay hidden opacity-0" onclick="if(event.target===this) closeResultModal()">
        <div class="modal-content rounded-3xl max-w-md w-full mx-4 shadow-2xl overflow-hidden">
            <div class="px-6 py-5 border-b border-[#B4D4FF]/30 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-emerald-100 flex items-center justify-center text-emerald-600">
                        <i class="fa-regular fa-circle-check text-xl"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-[#176B87]">Report Ready!</h3>
                        <p class="text-xs text-slate-400">Choose format to download</p>
                    </div>
                </div>
                <button onclick="closeResultModal()" class="p-1.5 rounded-lg hover:bg-[#B4D4FF]/20 text-slate-400 transition">
                    <i class="fa-solid fa-xmark text-xl"></i>
                </button>
            </div>
            <div class="px-6 py-6 space-y-3">
                <p class="text-sm text-slate-500 mb-2">Your report has been generated. Select a format to save it locally:</p>
                <button onclick="downloadPDF()" class="w-full flex items-center gap-4 px-4 py-3 rounded-xl border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/10 transition group">
                    <span class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center text-red-500 group-hover:scale-110 transition"><i class="fa-solid fa-file-pdf text-lg"></i></span>
                    <div class="flex-1 text-left">
                        <span class="font-medium text-slate-700">PDF Document</span>
                        <p class="text-xs text-slate-400">Portable Document Format</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:translate-x-1 transition"></i>
                </button>
                <button onclick="downloadExcel()" class="w-full flex items-center gap-4 px-4 py-3 rounded-xl border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/10 transition group">
                    <span class="w-10 h-10 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-600 group-hover:scale-110 transition"><i class="fa-solid fa-file-excel text-lg"></i></span>
                    <div class="flex-1 text-left">
                        <span class="font-medium text-slate-700">Excel Spreadsheet</span>
                        <p class="text-xs text-slate-400">.xls format – compatible with Excel</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:translate-x-1 transition"></i>
                </button>
                <button onclick="downloadWord()" class="w-full flex items-center gap-4 px-4 py-3 rounded-xl border border-[#B4D4FF]/30 hover:bg-[#B4D4FF]/10 transition group">
                    <span class="w-10 h-10 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 group-hover:scale-110 transition"><i class="fa-solid fa-file-word text-lg"></i></span>
                    <div class="flex-1 text-left">
                        <span class="font-medium text-slate-700">Word Document</span>
                        <p class="text-xs text-slate-400">.doc format – compatible with Word</p>
                    </div>
                    <i class="fa-solid fa-chevron-right text-slate-300 group-hover:translate-x-1 transition"></i>
                </button>
            </div>
            <div class="px-6 py-3 border-t border-[#B4D4FF]/30 bg-white/30 flex justify-end">
                <button onclick="closeResultModal()" class="text-sm text-slate-400 hover:text-slate-600 transition">Close</button>
            </div>
        </div>
    </div>
