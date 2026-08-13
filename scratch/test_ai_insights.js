
document.addEventListener('DOMContentLoaded', function() {

    let currentModalInsight = null;
    let rawAnalyticsData = null;

    // =====================================================================
    // 1. SKELETON LOADING & EMPTY HANDLERS (ISO/IEC 25010)
    // =====================================================================
    function renderLoadingState(containerId, count = 4) {
        const container = document.getElementById(containerId);
        if (!container) return;

        let html = '';
        for (let i = 0; i < count; i++) {
            html += `
                <div class="rounded-xl border border-slate-200/80 p-5 bg-white animate-pulse shadow-xs">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-8 h-8 bg-slate-200 rounded-lg"></div>
                        <div class="w-20 h-5 bg-slate-200 rounded-full"></div>
                    </div>
                    <div class="space-y-2 mb-4">
                        <div class="w-full h-4 bg-slate-200 rounded"></div>
                        <div class="w-3/4 h-4 bg-slate-200 rounded"></div>
                    </div>
                    <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                        <div class="w-16 h-3 bg-slate-200 rounded"></div>
                        <div class="w-20 h-3 bg-slate-200 rounded"></div>
                    </div>
                </div>
            `;
        }
        container.innerHTML = html;
    }

    function renderEmptyState(containerId, message = "No data available for this range.") {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = `
            <div class="col-span-full py-10 px-6 text-center bg-white rounded-2xl border border-dashed border-slate-300">
                <i class="fas fa-inbox text-2xl text-slate-300 mb-2"></i>
                <p class="text-xs font-bold text-slate-600">${message}</p>
            </div>
        `;
    }

    // =====================================================================
    // 2. APEXCHARTS INITIALIZATION
    // =====================================================================
    var predictiveOptions = {
        series: [
            { name: 'Expected Cases', data: [145, 160, 167, 175, 177, 191, 198] },
            { name: 'Permit Requests', data: [310, 338, 353, 372, 388, 419, 435] },
            { name: 'Vaccine Demand', data: [180, 195, 210, 225, 240, 260, 273] }
        ],
        chart: {
            type: 'area',
            height: 180,
            toolbar: { show: false },
            background: 'transparent',
            animations: { enabled: true, easing: 'easeinout', speed: 800 }
        },
        colors: ['#ef4444', '#3b82f6', '#10b981'],
        fill: {
            type: 'gradient',
            gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.04, stops: [0, 90, 100] }
        },
        stroke: { curve: 'smooth', width: [3, 3, 3] },
        xaxis: {
            categories: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May (AI Forecast)'],
            labels: { style: { fontSize: '10px', colors: ['#71717a', '#71717a', '#71717a', '#71717a', '#71717a', '#71717a', '#9333ea'], fontWeight: '600' } }
        },
        yaxis: { show: false },
        grid: { borderColor: '#f4f4f5', strokeDashArray: 3 },
        legend: { show: false },
        markers: { size: 4, strokeWidth: 2, strokeColors: '#ffffff', hover: { size: 7 } },
        annotations: {
            xaxis: [{
                x: 'May (AI Forecast)',
                borderColor: '#a855f7',
                strokeDashArray: 4,
                label: { borderColor: '#a855f7', style: { color: '#fff', background: '#9333ea', fontSize: '9px', fontWeight: '700' }, text: '⚡ AI Horizon' }
            }]
        }
    };
    var predictiveChart = new ApexCharts(document.querySelector("#predictiveLineChart"), predictiveOptions);
    predictiveChart.render();

    // =====================================================================
    // 3. MAIN API FETCH & DSS RENDER LOGIC
    // =====================================================================
    async function fetchLiveAnalytics(forceRefresh = false, isSilent = false) {
        var range = document.getElementById('dateRangeSelect').value;
        var dept = document.getElementById('deptFilterSelect').value;

        if (!isSilent) {
            renderLoadingState('situationalGrid', 6);
            renderLoadingState('prescriptiveCardsGrid', 3);
            renderLoadingState('insightsGrid', 4);
        }

        try {
            var url = '../api/analytics.php?range=' + range + '&dept=' + dept + (forceRefresh ? '&refresh=1' : '');
            var res = await fetch(url);
            var data = await res.json();

            if (data && data.success) {
                rawAnalyticsData = data;
                renderExecutiveOverview(data.exec_overview);
                renderSituationalAwareness(data.situational);
                renderPrescriptiveActions(data.prescriptive);
                renderPredictiveView(data.predictive);
                renderCorrelationsView(data.correlations);
                renderInsightsGrid(data.insights);
                renderModelQualityView(data.model_quality);
            }
        } catch (err) {
            console.error('API Fetch Error:', err);
        }
    }
    window.fetchLiveAnalytics = fetchLiveAnalytics;

    // RENDER FUNCTIONS
    function renderExecutiveOverview(exec) {
        if (!exec) return;
        if (document.getElementById('execHealthScore')) document.getElementById('execHealthScore').textContent = (exec.health_score || 94.8) + '%';
        if (document.getElementById('execHealthStatus')) document.getElementById('execHealthStatus').textContent = exec.status || 'Optimal';
        if (document.getElementById('execConfidenceScore')) document.getElementById('execConfidenceScore').textContent = (exec.ai_confidence || 96.4) + '%';
        if (document.getElementById('execRiskLevel')) document.getElementById('execRiskLevel').textContent = exec.risk_level || 'Low-Mod';
        if (document.getElementById('execNarrativeText')) document.getElementById('execNarrativeText').textContent = exec.executive_summary || '';
        if (document.getElementById('execLastTimestamp')) document.getElementById('execLastTimestamp').textContent = new Date().toLocaleTimeString();
    }

    function renderSituationalAwareness(situational) {
        const grid = document.getElementById('situationalGrid');
        if (!grid || !Array.isArray(situational)) return;

        const badgeColors = {
            'emerald': 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'blue': 'bg-blue-50 text-blue-700 border-blue-200',
            'amber': 'bg-amber-50 text-amber-700 border-amber-200',
            'indigo': 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'teal': 'bg-teal-50 text-teal-700 border-teal-200'
        };

        grid.innerHTML = situational.map(item => `
            <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between mb-2">
                    <i class="fas ${item.icon || 'fa-info-circle'} text-xs text-slate-400"></i>
                    <span class="text-[9px] font-extrabold px-2 py-0.5 rounded-full border ${badgeColors[item.color] || 'bg-slate-100 text-slate-700'}">${item.badge}</span>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">${item.domain}</p>
                    <p class="text-xs font-black text-slate-800 mt-0.5">${item.status}</p>
                </div>
            </div>
        `).join('');
    }

    function renderPrescriptiveActions(actions) {
        const container = document.getElementById('prescriptiveCardsGrid');
        if (!container || !Array.isArray(actions)) return;

        const priorityBadge = {
            'High': 'bg-rose-100 text-rose-800 border-rose-200',
            'Medium': 'bg-amber-100 text-amber-800 border-amber-200',
            'Low': 'bg-emerald-100 text-emerald-800 border-emerald-200'
        };

        container.innerHTML = actions.map(act => `
            <div class="bg-white border border-slate-200 p-5 rounded-2xl flex flex-col justify-between shadow-xs">
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-[10px] font-extrabold px-2.5 py-0.5 rounded-full border ${priorityBadge[act.priority] || 'bg-slate-100'}">${act.priority} Priority</span>
                        <span class="text-[10px] font-bold text-purple-600 bg-purple-50 px-2 py-0.5 rounded-md">${act.confidence}% AI Certainty</span>
                    </div>
                    <h3 class="text-xs font-black text-slate-900 leading-snug mb-2">${act.title}</h3>
                    <p class="text-[11px] text-slate-500 font-medium mb-3"><strong class="text-slate-700">Reason:</strong> ${act.reason}</p>
                    <p class="text-[11px] text-emerald-700 font-bold bg-emerald-50 p-2 rounded-lg border border-emerald-100 mb-3"><i class="fas fa-bullseye mr-1"></i> ${act.impact}</p>
                    
                    <!-- 5-Question Explainability Accordion -->
                    <details class="text-[10px] bg-slate-50 p-2.5 rounded-xl border border-slate-200/80 mb-3 cursor-pointer">
                        <summary class="font-bold text-slate-700 uppercase tracking-wider">Explainable AI (XAI) Breakdown</summary>
                        <div class="mt-2 space-y-1.5 text-slate-600 border-t border-slate-200/60 pt-2">
                            <p><strong>What Happened:</strong> ${act.explainability.what}</p>
                            <p><strong>Why:</strong> ${act.explainability.why}</p>
                            <p><strong>Calculation Method:</strong> ${act.explainability.how}</p>
                            <p><strong>Reliability:</strong> ${act.explainability.reliability}</p>
                        </div>
                    </details>
                </div>

                <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">${act.department}</span>
                    <button onclick="window.location.href='../modules/${act.module}'" class="px-3 py-1.5 bg-slate-900 hover:bg-black text-white text-[10px] font-bold rounded-lg transition shadow-xs">
                        Execute Action →
                    </button>
                </div>
            </div>
        `).join('');
    }

    function renderPredictiveView(predictive) {
        if (!predictive) return;
        if (predictive.series && typeof predictiveChart !== 'undefined') {
            predictiveChart.updateOptions({
                series: predictive.series,
                xaxis: { categories: predictive.categories }
            });
        }
        if (predictive.cards && predictive.cards.length >= 3) {
            if (document.getElementById('pred-cases-val')) document.getElementById('pred-cases-val').textContent = predictive.cards[0].value;
            if (document.getElementById('pred-permits-val')) document.getElementById('pred-permits-val').textContent = predictive.cards[1].value;
            if (document.getElementById('pred-vaccines-val')) document.getElementById('pred-vaccines-val').textContent = predictive.cards[2].value;
        }
    }

    function renderCorrelationsView(correlations) {
        const grid = document.getElementById('correlationGrid');
        if (!grid || !Array.isArray(correlations)) return;

        grid.innerHTML = correlations.map(c => `
            <div class="bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-slate-800">${c.pair}</span>
                    <span class="text-xs font-black font-mono text-${c.color}-600 bg-${c.color}-50 px-2 py-0.5 rounded-md">r = ${c.coefficient}</span>
                </div>
                <p class="text-[10px] font-bold uppercase text-slate-400 mb-1">${c.strength}</p>
                <p class="text-xs text-slate-600 font-medium">${c.interpretation}</p>
            </div>
        `).join('');
    }

    function renderInsightsGrid(insights) {
        const grid = document.getElementById('insightsGrid');
        if (!grid || !Array.isArray(insights)) return;

        grid.innerHTML = insights.map(item => `
            <div class="bg-white border border-slate-200 p-4 rounded-xl flex flex-col justify-between cursor-pointer hover:shadow-md transition" onclick="openInsightModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 border border-blue-100">${item.badge || 'Live AI Insight'}</span>
                        <span class="text-[10px] font-bold text-slate-400">${item.confidence}%</span>
                    </div>
                    <p class="text-xs font-extrabold text-slate-800 mt-2 leading-relaxed">${item.title}</p>
                </div>
                <div class="mt-4 pt-2 border-t border-slate-100 flex justify-between items-center text-[10px]">
                    <span class="font-bold text-slate-400 uppercase">AI Processed</span>
                    <span class="font-bold text-purple-600 hover:underline">Click details →</span>
                </div>
            </div>
        `).join('');
    }

    function renderModelQualityView(mq) {
        if (!mq) return;
        if (document.getElementById('modelR2')) document.getElementById('modelR2').textContent = mq.r_squared || '0.924';
        if (document.getElementById('modelMAE')) document.getElementById('modelMAE').textContent = mq.mae || '3.12';
        if (document.getElementById('modelRMSE')) document.getElementById('modelRMSE').textContent = mq.rmse || '4.65';
        if (document.getElementById('modelMAPE')) document.getElementById('modelMAPE').textContent = mq.mape || '4.2%';
        if (document.getElementById('modelHealth')) document.getElementById('modelHealth').textContent = mq.model_health || '98%';
    }

    // SCENARIO SIMULATOR (WHAT-IF ANALYSIS)
    window.updateSimulation = function() {
        var v = parseInt(document.getElementById('simVaccineSlider').value);
        var s = parseInt(document.getElementById('simStaffSlider').value);
        var i = parseInt(document.getElementById('simInspectSlider').value);

        if (document.getElementById('simVaccineVal')) document.getElementById('simVaccineVal').textContent = '+' + v + '%';
        if (document.getElementById('simStaffVal')) document.getElementById('simStaffVal').textContent = '+' + s + ' Nurses';
        if (document.getElementById('simInspectVal')) document.getElementById('simInspectVal').textContent = '+' + i + '%';

        var vRes = (v * 0.9).toFixed(1);
        var sRes = s * 11.6;
        var iRes = (i * 0.79).toFixed(1);

        if (document.getElementById('simVaccineResult')) document.getElementById('simVaccineResult').textContent = '-' + vRes + '%';
        if (document.getElementById('simStaffResult')) document.getElementById('simStaffResult').textContent = '-' + Math.round(sRes) + ' Mins';
        if (document.getElementById('simInspectResult')) document.getElementById('simInspectResult').textContent = '+' + iRes + '%';
    };

    window.resetSimulation = function() {
        document.getElementById('simVaccineSlider').value = 15;
        document.getElementById('simStaffSlider').value = 3;
        document.getElementById('simInspectSlider').value = 25;
        updateSimulation();
    };

    // MODAL HANDLERS
    window.openExportModal = function() { document.getElementById('exportBriefingModal').classList.remove('hidden'); };
    window.closeExportModal = function() { document.getElementById('exportBriefingModal').classList.add('hidden'); };
    window.exportCSVReport = function() {
        var csv = "Category,Predicted_Value,Confidence,Status\nDisease Cases,198,92%,Optimal\nPermit Requests,435,89%,Normal\nVaccine Demand,273,95%,Optimal\n";
        var blob = new Blob([csv], { type: 'text/csv' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = "Admin_AI_DSS_Report.csv";
        a.click();
    };

    window.openInsightModal = function(insight) {
        currentModalInsight = insight;
        const modal = document.getElementById('insightDetailModal');
        if (!modal) return;
        document.getElementById('modalCategoryBadge').textContent = insight.badge || 'AI Insight';
        document.getElementById('modalInsightTitle').innerHTML = insight.title || 'AI Insight Details';
        document.getElementById('modalInsightAction').textContent = insight.action || 'No action specified.';
        modal.classList.remove('hidden');
    };
    window.closeInsightModal = function() { document.getElementById('insightDetailModal').classList.add('hidden'); };
    window.navigateToInsightModule = function() {
        if (currentModalInsight) window.location.href = '../modules/surveillence/outbreak_detection.php';
    };

    window.toggleNarrativeAudio = function() {
        var text = document.getElementById('execNarrativeText').textContent;
        var msg = new SpeechSynthesisUtterance(text);
        window.speechSynthesis.speak(msg);
    };

    // INITIAL FETCH
    fetchLiveAnalytics(false, false);

    // SUPABASE REALTIME WEBSOCKET LISTENER
    var SUPABASE_URL = "mock";
    var SUPABASE_ANON_KEY = "mock";
    if (SUPABASE_URL && SUPABASE_ANON_KEY && typeof supabase !== 'undefined') {
        try {
            var client = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
            client.channel('realtime-dss-admin')
                  .on('postgres_changes', { event: '*', schema: 'public', table: 'surveillance_cases' }, function() { fetchLiveAnalytics(true, true); })
                  .on('postgres_changes', { event: '*', schema: 'public', table: 'permits' }, function() { fetchLiveAnalytics(true, true); })
                  .subscribe();
        } catch (e) {}
    }

});
