    <!-- ================================================================ -->
    <!--  SECTION 1: DASHBOARD                                            -->
    <!-- ================================================================ -->
    <div id="section-dashboard">
        <!-- ─── QUICK STATS ─── -->
        <div id="quickStats" class="kpi-grid grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <!-- KPI 1: Total Inspections -->
            <a href="javascript:void(0)" onclick="openGenerateReportModal()" class="kpi-card glow-blue relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-clipboard kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-blue-500/10 rotate-[-8deg] pointer-events-none"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-blue-400 to-blue-600"></div>
                <div class="relative p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-blue-600">Total Inspections</p>
                            <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiTotal">0</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5"><?= $isStaff ? 'Your Assigned Records' : 'Conducted' ?></p>
                        </div>
                    </div>
                </div>
            </a>

            <!-- KPI 2: Compliance Rate -->
            <a href="javascript:void(0)" onclick="openGenerateReportModal()" class="kpi-card glow-emerald relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-emerald-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-circle-check kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-emerald-500/10 rotate-[-8deg] pointer-events-none"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-emerald-400 to-emerald-600"></div>
                <div class="relative p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-emerald-600">Compliance Rate</p>
                            <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiCompliance">0%</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Overall Status</p>
                        </div>
                    </div>
                </div>
            </a>

            <!-- KPI 3: Urgent Issues -->
            <a href="javascript:void(0)" onclick="openGenerateReportModal()" class="kpi-card glow-red relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-rose-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-circle-exclamation kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-rose-500/10 rotate-[-8deg] pointer-events-none"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-rose-400 to-rose-600"></div>
                <div class="relative p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-rose-600">Urgent Issues</p>
                            <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiUrgent">0</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Need Attention</p>
                        </div>
                    </div>
                </div>
            </a>

            <!-- KPI 4: Facilities Covered -->
            <a href="javascript:void(0)" onclick="openGenerateReportModal()" class="kpi-card glow-purple relative overflow-hidden rounded-2xl shadow-sm cursor-pointer group block">
                <div class="kpi-shine"></div>
                <div class="absolute inset-0 bg-gradient-to-br from-purple-50 via-transparent to-transparent pointer-events-none"></div>
                <i class="fas fa-hospital kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-purple-500/10 rotate-[-8deg] pointer-events-none"></i>
                <div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-purple-400 to-purple-600"></div>
                <div class="relative p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-[8px] font-bold uppercase tracking-wider text-purple-600">Facilities Covered</p>
                            <p class="kpi-value text-xl font-black text-slate-900 mt-1 leading-none" id="kpiFacilities">0</p>
                            <p class="text-[8px] font-medium text-slate-400 mt-0.5">Operational Reach</p>
                        </div>
                    </div>
                </div>
            </a>
        </div>

        <!-- ─── DASHBOARD ACTION BAR ─── -->
        <div class="flex flex-wrap items-center justify-between gap-3 p-4 bg-white/90 backdrop-blur-md rounded-2xl border border-[#B4D4FF]/40 mb-6 shadow-xs sticky top-0 z-50">
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-semibold text-[#176B87] uppercase tracking-wider">Quick Actions:</span>
                <button onclick="openGenerateReportModal()" class="btn-primary px-3.5 py-1.5 rounded-xl text-xs font-semibold text-white inline-flex items-center gap-1.5 shadow-xs">
                    <i class="fa-solid fa-plus"></i> Generate New Report
                </button>
                <button onclick="switchReportTab('templates'); document.getElementById('report-management-container').scrollIntoView({behavior: 'smooth', block: 'start'})" class="btn-outline-primary px-3.5 py-1.5 rounded-xl text-xs font-medium inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-folder-open"></i> Browse Templates
                </button>
                <button onclick="switchReportTab('scheduled'); document.getElementById('report-management-container').scrollIntoView({behavior: 'smooth', block: 'start'})" class="px-3.5 py-1.5 rounded-xl text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition inline-flex items-center gap-1.5">
                    <i class="fa-regular fa-clock"></i> View Schedule
                </button>
            </div>
        </div>


    </div>
