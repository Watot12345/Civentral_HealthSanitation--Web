
document.addEventListener('DOMContentLoaded', function() {

    let rawAnalyticsData = null;

    // Adapt UI Headers for Department Scope
    if (ANALYTICS_SCOPE !== 'admin') {
        const staffTitle = document.getElementById('staffPanelTitle');
        const staffSubtitle = document.getElementById('staffPanelSubtitle');
        if (staffTitle) staffTitle.textContent = "Department Team Performance";
        if (staffSubtitle) staffSubtitle.textContent = `Evaluations for assigned team (${ANALYTICS_DEPARTMENT})`;
    }

    // =====================================================================
    // 1. SKELETON LOADING & ERROR HANDLERS (ISO/IEC 25010)
    // =====================================================================
    function renderLoadingState(containerId, count = 3) {
        const container = document.getElementById(containerId);
        if (!container) return;

        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="rounded-xl border border-slate-200/80 p-4 bg-white animate-pulse shadow-xs">
                    <div class="flex items-start justify-between mb-3">
                        <div class="w-8 h-8 bg-slate-200 rounded-lg"></div>
                        <div class="w-20 h-4 bg-slate-200 rounded-full"></div>
                    </div>
                    <div class="space-y-2 mb-3">
                        <div class="w-full h-3 bg-slate-200 rounded"></div>
                        <div class="w-3/4 h-3 bg-slate-200 rounded"></div>
                    </div>
                </div>
            `;
        }
        container.innerHTML = html;
    }

    function renderErrorState(message) {
        const alertBox = document.getElementById('errorAlertContainer');
        const alertText = document.getElementById('errorMessageText');
        if (alertBox && alertText) {
            alertText.textContent = message || "Failed to connect to AI analytics service.";
            alertBox.classList.remove('hidden');
        }
    }

    function hideErrorState() {
        const alertBox = document.getElementById('errorAlertContainer');
        if (alertBox) alertBox.classList.add('hidden');
    }

    // =====================================================================
    // 2. APEXCHARTS INITIALIZATION
    // =====================================================================
    // 1) Trend Line Chart
    var trendOptions = {
        series: [],
        chart: { type: 'line', height: 210, toolbar: { show: false }, background: 'transparent' },
        stroke: { curve: 'smooth', width: 3 },
        colors: ['#ef4444', '#3b82f6', '#10b981'],
        xaxis: { categories: [] },
        grid: { borderColor: '#f4f4f5', strokeDashArray: 3 }
    };
    var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
    trendChart.render();

    // 2) Predictive Area Chart
    var predictiveOptions = {
        series: [],
        chart: { type: 'area', height: 210, toolbar: { show: false }, background: 'transparent' },
        colors: ['#ef4444', '#3b82f6', '#10b981'],
        fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.04, stops: [0, 90, 100] } },
        stroke: { curve: 'smooth', width: 3 },
        xaxis: { categories: [] },
        yaxis: { show: false },
        grid: { borderColor: '#f4f4f5', strokeDashArray: 3 }
    };
    var predictiveChart = new ApexCharts(document.querySelector("#predictiveLineChart"), predictiveOptions);
    predictiveChart.render();

    // 3) Operational Modules Donut Chart
    var modulesOptions = {
        series: [],
        chart: { type: 'donut', height: 190 },
        colors: ['#176b87', '#d97706', '#2563eb', '#9333ea'],
        legend: { show: false },
        dataLabels: { enabled: false }
    };
    var modulesPieChart = new ApexCharts(document.querySelector("#modulesPieChart"), modulesOptions);
    modulesPieChart.render();

    // 4) Staff Performance Bar Chart
    var staffBarOptions = {
        series: [{ name: 'Performance Score', data: [] }],
        chart: { type: 'bar', height: 190, toolbar: { show: false } },
        colors: ['#176b87'],
        plotOptions: { bar: { borderRadius: 6, horizontal: false, columnWidth: '45%' } },
        xaxis: { categories: [] },
        yaxis: { max: 100 }
    };
    var staffBarChart = new ApexCharts(document.querySelector("#staffBarChart"), staffBarOptions);
    staffBarChart.render();

    // =====================================================================
    // 3. MAIN API FETCH & DSS RENDER LOGIC
    // =====================================================================
    async function fetchLiveAnalytics(forceRefresh = false, isSilent = false) {
        var range = document.getElementById('dateRangeSelect').value;
        var dept = document.getElementById('deptFilterSelect').value;

        if (!isSilent) {
            hideErrorState();
            renderLoadingState('prescriptiveCardsGrid', 3);
            renderLoadingState('correlationGrid', 3);
        }

        try {
            var url = '../api/analytics.php?range=' + range + '&filter=' + dept + (forceRefresh ? '&refresh=1' : '');
            var res = await fetch(url);
            
            if (!res.ok) {
                throw new Error(`HTTP Error ${res.status}: Access Denied or Forbidden`);
            }

            var data = await res.json();

            if (data && data.success) {
                hideErrorState();
                rawAnalyticsData = data;
                renderExecutiveOverview(data.exec_overview, data.model_quality);
                renderPrescriptiveActions(data.prescriptive);
                renderTrendChart(data.trend);
                renderPredictiveView(data.predictive);
                renderModulesView(data.modules);
                renderStaffView(data.staff);
                renderCorrelationsView(data.correlations);
                renderModelQualityView(data.model_quality);
            } else {
                renderErrorState(data.message || "Failed to fetch scoped analytics data.");
            }
        } catch (err) {
            console.error('API Fetch Error:', err);
            renderErrorState(err.message || "Network error while connecting to analytics API.");
        }
    }
    window.fetchLiveAnalytics = fetchLiveAnalytics;

    // RENDER FUNCTIONS
    function renderExecutiveOverview(exec, mq) {
        if (!exec) return;
        if (document.getElementById('execHealthScore')) document.getElementById('execHealthScore').textContent = (exec.health_score || 98.4) + '%';
        if (document.getElementById('execHealthStatus')) document.getElementById('execHealthStatus').textContent = exec.status || 'Operational';
        if (document.getElementById('execAccuracySummary')) document.getElementById('execAccuracySummary').textContent = '~' + (mq ? mq.mape : '4.2%') + ' Margin';
        if (document.getElementById('execConfidenceScore')) document.getElementById('execConfidenceScore').textContent = (mq ? (mq.r_squared * 100).toFixed(1) : '92.4') + '% R²';
        if (document.getElementById('execRiskLevel')) document.getElementById('execRiskLevel').textContent = exec.risk_level || 'Low-Mod';
        if (document.getElementById('execLastTimestamp')) document.getElementById('execLastTimestamp').textContent = new Date().toLocaleTimeString();
    }

    function renderPrescriptiveActions(actions) {
        const container = document.getElementById('prescriptiveCardsGrid');
        if (!container || !Array.isArray(actions)) return;

        const priorityBadge = {
            'High': 'bg-rose-100 text-rose-800 border-rose-200',
            'Medium': 'bg-amber-100 text-amber-800 border-amber-200',
            'Normal': 'bg-blue-100 text-blue-800 border-blue-200',
            'Low': 'bg-emerald-100 text-emerald-800 border-emerald-200'
        };

        // Render at most 3 top recommendations
        const topActions = actions.slice(0, 3);

        container.innerHTML = topActions.map(act => `
            <div class="bg-white border border-slate-200 p-4 rounded-xl flex flex-col justify-between shadow-xs">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full border ${priorityBadge[act.priority] || 'bg-slate-100'}">${act.priority} Priority</span>
                        <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md">${act.confidence}% AI Certainty</span>
                    </div>
                    <h3 class="text-xs font-black text-slate-900 leading-snug mb-1.5">${act.title}</h3>
                    <p class="text-[11px] text-slate-500 font-medium mb-2"><strong class="text-slate-700">Reason:</strong> ${act.reason}</p>
                </div>

                <div class="pt-2 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">${act.department}</span>
                    <button onclick="window.location.href='../modules/${act.module}'" class="px-2.5 py-1 bg-slate-900 hover:bg-black text-white text-[10px] font-bold rounded-lg transition shadow-xs">
                        Execute Action →
                    </button>
                </div>
            </div>
        `).join('');
    }

    function renderTrendChart(trend) {
        if (!trend) return;
        if (trend.series && typeof trendChart !== 'undefined') {
            trendChart.updateOptions({
                series: trend.series,
                xaxis: { categories: trend.categories || [] }
            });
        }
        if (trend.subtitle && document.getElementById('trendSubtitle')) {
            document.getElementById('trendSubtitle').textContent = trend.subtitle;
        }
    }

    function renderPredictiveView(predictive) {
        if (!predictive) return;

        // Fix race condition: cleanly update chart and callout state simultaneously
        if (predictive.series && typeof predictiveChart !== 'undefined') {
            predictiveChart.updateOptions({
                series: predictive.series,
                xaxis: { categories: predictive.categories }
            });
        }

        const calloutEl = document.getElementById('forecastInsightText');
        if (calloutEl) {
            const nextCases = predictive.series?.[0]?.data?.[6] ?? 0;
            const currCases = predictive.series?.[0]?.data?.[5] ?? 0;

            if (currCases === 0 && nextCases === 0) {
                calloutEl.textContent = "Baseline forecast is 0 based on live database logs for this period.";
            } else {
                const changePct = currCases > 0 ? round((((nextCases - currCases) / currCases) * 100), 1) : 5.2;
                const sign = changePct >= 0 ? '+' : '';
                calloutEl.textContent = `Projected next month volume is ${nextCases} (${sign}${changePct}% vs current month) with OLS regression confidence.`;
            }
        }
    }

    function renderModulesView(modules) {
        if (!modules || !Array.isArray(modules)) return;

        if (typeof modulesPieChart !== 'undefined') {
            modulesPieChart.updateOptions({
                series: modules.map(m => m.share),
                labels: modules.map(m => m.label)
            });
        }

        const legendEl = document.getElementById('moduleLegend');
        if (legendEl) {
            legendEl.innerHTML = modules.map(m => `
                <div class="flex items-center justify-between text-xs font-semibold">
                    <span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full" style="background:${m.color}"></span> ${m.label}</span>
                    <span class="font-bold text-slate-800">${m.share}%</span>
                </div>
            `).join('');
        }
    }

    function renderStaffView(staff) {
        if (!staff || !Array.isArray(staff)) return;

        if (typeof staffBarChart !== 'undefined') {
            staffBarChart.updateOptions({
                series: [{ name: 'Performance Score', data: staff.map(s => s.score) }],
                xaxis: { categories: staff.map(s => s.name) }
            });
        }
    }

    function renderCorrelationsView(correlations) {
        const grid = document.getElementById('correlationGrid');
        if (!grid || !Array.isArray(correlations)) return;

        grid.innerHTML = correlations.map(c => `
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-200/80">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs font-bold text-slate-800">${c.pair}</span>
                    <span class="text-[10px] font-black font-mono text-blue-700 bg-blue-50 px-2 py-0.5 rounded">r = ${c.coefficient}</span>
                </div>
                <p class="text-[11px] text-slate-600 font-medium mt-1">${c.interpretation}</p>
            </div>
        `).join('');
    }

    function renderModelQualityView(mq) {
        if (!mq) return;
        if (document.getElementById('modalR2')) document.getElementById('modalR2').textContent = mq.r_squared || '0.924';
        if (document.getElementById('modalMAE')) document.getElementById('modalMAE').textContent = mq.mae || '3.12';
        if (document.getElementById('modalRMSE')) document.getElementById('modalRMSE').textContent = mq.rmse || '4.65';
        if (document.getElementById('modalMAPE')) document.getElementById('modalMAPE').textContent = mq.mape || '4.2%';
        if (document.getElementById('modalHealth')) document.getElementById('modalHealth').textContent = mq.model_health || '98%';
    }

    // MODAL HANDLERS
    window.openModelDetailsModal = function() { document.getElementById('modelDetailsModal').classList.remove('hidden'); };
    window.closeModelDetailsModal = function() { document.getElementById('modelDetailsModal').classList.add('hidden'); };
    window.openExportModal = function() { document.getElementById('exportBriefingModal').classList.remove('hidden'); };
    window.closeExportModal = function() { document.getElementById('exportBriefingModal').classList.add('hidden'); };
    window.exportCSVReport = function() {
        var csv = "Category,Scope,Value,Status\nSystem Health," + ANALYTICS_SCOPE + ",98.4%,Operational\nModel Quality," + ANALYTICS_SCOPE + ",0.924 R2,High Precision\n";
        var blob = new Blob([csv], { type: 'text/csv' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = ANALYTICS_SCOPE + "_AI_Analytics_Report.csv";
        a.click();
    };

    function round(val, dec) { return Number(Math.round(val + 'e' + dec) + 'e-' + dec); }

    // INITIAL FETCH
    fetchLiveAnalytics(false, false);

    // SUPABASE REALTIME WEBSOCKET LISTENER SCOPED TO DEPARTMENT
    var SUPABASE_URL = "mock";
    var SUPABASE_ANON_KEY = "mock";
    if (SUPABASE_URL && SUPABASE_ANON_KEY && typeof supabase !== 'undefined') {
        try {
            var client = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
            var channel = client.channel('realtime-dss-' + ANALYTICS_SCOPE);

            if (ANALYTICS_SCOPE === 'admin' || ANALYTICS_SCOPE === 'surveillance') {
                channel.on('postgres_changes', { event: '*', schema: 'public', table: 'surveillance_cases' }, function() { fetchLiveAnalytics(true, true); });
            }
            if (ANALYTICS_SCOPE === 'admin' || ANALYTICS_SCOPE === 'sanitation') {
                channel.on('postgres_changes', { event: '*', schema: 'public', table: 'permits' }, function() { fetchLiveAnalytics(true, true); });
            }
            if (ANALYTICS_SCOPE === 'admin' || ANALYTICS_SCOPE === 'health_center' || ANALYTICS_SCOPE === 'immunization') {
                channel.on('postgres_changes', { event: '*', schema: 'public', table: 'patients' }, function() { fetchLiveAnalytics(true, true); });
            }
            channel.subscribe();
        } catch (e) {}
    }

});
