/**
 * AI Insights Dashboard Client Application
 * 100% Dynamic - Powered directly by live Supabase data & API analytics.
 */

document.addEventListener('DOMContentLoaded', function() {

    // =====================================================================
    // AI GLOW CURSOR TRACKING
    // =====================================================================
    const aiPanel = document.getElementById('aiInsightPanel');
    if (aiPanel) {
        aiPanel.addEventListener('mousemove', (e) => {
            const rect = aiPanel.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            aiPanel.style.setProperty('--mouse-x', `${x}px`);
            aiPanel.style.setProperty('--mouse-y', `${y}px`);
        });
    }

    // =====================================================================
    // TOOLTIP SYSTEM
    // =====================================================================
    const tooltip = document.getElementById('hoverTooltip');
    let tooltipHideTimeout = null;

    function showTooltip(event, title, content, isPieChart = false) {
        if (!tooltip) return;

        if (tooltipHideTimeout) {
            clearTimeout(tooltipHideTimeout);
            tooltipHideTimeout = null;
        }

        const rect = event.currentTarget.getBoundingClientRect();
        let left = rect.left + rect.width / 2 - 160;
        let top = rect.top - 20;

        const tooltipWidth = 340;
        const tooltipHeight = 340;

        if (left + tooltipWidth > window.innerWidth - 20) left = window.innerWidth - tooltipWidth - 20;
        if (left < 20) left = 20;

        if (top - tooltipHeight < 20) {
            top = rect.bottom + 20;
            const arrow = tooltip.querySelector('.tooltip-arrow');
            if (arrow) arrow.className = 'tooltip-arrow bottom';
        } else {
            const arrow = tooltip.querySelector('.tooltip-arrow');
            if (arrow) arrow.className = 'tooltip-arrow top';
        }

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';

        const titleEl = document.getElementById('tooltipTitle');
        if (titleEl) titleEl.textContent = title;

        let contentHtml = '';

        if (isPieChart && content && content.pieData) {
            contentHtml = `
                <div class="mini-chart" id="miniPieChart"></div>
                <div class="tooltip-pie-legend">
                    ${content.pieData.map(d => `<span><span class="dot" style="background: ${d.color};"></span>${d.label} (${d.value}%)</span>`).join('')}
                </div>
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #f4f4f5;">
                    ${(content.details || []).map(d => `<div class="tooltip-row"><span class="label">${d.label}</span><span class="value">${d.value}</span></div>`).join('')}
                </div>
            `;
        } else if (Array.isArray(content)) {
            contentHtml = content.map(d => `<div class="tooltip-row"><span class="label">${d.label}</span><span class="value">${d.value}</span></div>`).join('');
        }

        const contentEl = document.getElementById('tooltipContent');
        if (contentEl) contentEl.innerHTML = contentHtml;

        tooltip.classList.add('active');

        if (isPieChart && content && content.pieData) {
            if (window._miniChart) {
                window._miniChart.destroy();
                window._miniChart = null;
            }
            setTimeout(() => {
                renderMiniPieChart(content.pieData);
            }, 50);
        }
    }

    function hideTooltip() {
        if (tooltipHideTimeout) clearTimeout(tooltipHideTimeout);
        tooltipHideTimeout = setTimeout(() => {
            if (tooltip) tooltip.classList.remove('active');
            if (window._miniChart) {
                window._miniChart.destroy();
                window._miniChart = null;
            }
            tooltipHideTimeout = null;
        }, 200);
    }

    function renderMiniPieChart(data) {
        const container = document.getElementById('miniPieChart');
        if (!container || !data || data.length === 0) return;
        if (window._miniChart) {
            window._miniChart.destroy();
            window._miniChart = null;
        }

        window._miniChart = new ApexCharts(container, {
            series: data.map(d => d.value),
            chart: {
                type: 'donut',
                height: 120,
                width: 120,
                toolbar: { show: false },
                animations: { enabled: true, speed: 500 }
            },
            colors: data.map(d => d.color),
            labels: data.map(d => d.label),
            legend: { show: false },
            dataLabels: { enabled: false },
            stroke: { width: 2, colors: ['#ffffff'] },
            plotOptions: {
                pie: {
                    donut: { size: '60%' }
                }
            },
            tooltip: {
                enabled: true,
                style: { fontSize: '10px' }
            }
        });
        window._miniChart.render();
    }

    let currentModalInsight = null;

    window.openInsightModal = function(insight) {
        currentModalInsight = insight;
        const modal = document.getElementById('insightDetailModal');
        if (!modal) return;

        document.getElementById('modalCategoryBadge').textContent = insight.category || insight.badge || 'AI Insight';
        document.getElementById('modalInsightTitle').innerHTML = insight.title || 'AI Insight Details';
        document.getElementById('modalInsightAction').textContent = insight.action || 'No action specified.';

        const metricsContainer = document.getElementById('modalInsightMetrics');
        if (metricsContainer && insight.metrics && Array.isArray(insight.metrics)) {
            metricsContainer.innerHTML = insight.metrics.map(m => `
                <div class="bg-white border border-zinc-200/80 p-2.5 rounded-xl text-center">
                    <div class="text-[9px] font-bold text-zinc-400 uppercase">${m.label}</div>
                    <div class="text-xs font-extrabold text-zinc-800 mt-0.5">${m.value}</div>
                </div>
            `).join('');
        }

        modal.classList.remove('hidden');
    };

    window.closeInsightModal = function() {
        const modal = document.getElementById('insightDetailModal');
        if (modal) modal.classList.add('hidden');
    };

    window.navigateToInsightModule = function() {
        if (currentModalInsight) {
            goToModule(currentModalInsight);
        }
    };

    window.goToModule = function(insight, event) {
        if (event) event.stopPropagation();
        let targetUrl = '../modules/surveillence/outbreak_detection.php';

        if (insight) {
            const cat = (insight.category || '').toLowerCase();
            const id = (insight.id || '').toLowerCase();

            if (cat.includes('disease') || id === 'ins_1') {
                targetUrl = '../modules/surveillence/outbreak_detection.php';
            } else if (cat.includes('patient') || cat.includes('health') || id === 'ins_2') {
                targetUrl = '../modules/healthservices/patients.php';
            } else if (cat.includes('permit') || cat.includes('sanitation') || id === 'ins_3') {
                targetUrl = '../modules/sanitation/permit_applications.php';
            } else if (cat.includes('resource') || cat.includes('staff') || id === 'ins_4') {
                targetUrl = '../modules/surveillence/alerts.php';
            }
        }

        window.location.href = targetUrl;
    };

    // =====================================================================
    // RENDER FUNCTIONS (WITH CLEAN EMPTY FALLBACK STATEMENTS)
    // =====================================================================
    function renderInsights(liveInsights) {
        const grid = document.getElementById('insightsGrid') || document.getElementById('aiInsightsGrid');
        if (!grid) return;

        const items = (liveInsights && Array.isArray(liveInsights)) ? liveInsights : [];

        if (items.length === 0) {
            grid.innerHTML = '<div class="col-span-full flex flex-col items-center justify-center p-8 bg-zinc-50/60 rounded-xl border border-dashed border-zinc-200 text-center">' +
                '<div class="w-10 h-10 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mb-2">' +
                '<i class="fa-solid fa-circle-check text-base"></i>' +
                '</div>' +
                '<h4 class="text-xs font-bold text-zinc-800">All Modules Operating Within Normal Parameters</h4>' +
                '<p class="text-[11px] text-zinc-500 max-w-sm mt-0.5">No critical anomalies or disease outbreak alerts recorded in Supabase for this period.</p>' +
                '</div>';
            return;
        }

        const iconMap = {
            'alert': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
            'users': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>',
            'check': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
            'ai': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>'
        };

        const bgClassMap = {
            'rose': 'bg-rose-50/70 text-rose-700 border-rose-100',
            'red': 'bg-red-50/70 text-red-700 border-red-100',
            'amber': 'bg-amber-50/70 text-amber-700 border-amber-100',
            'emerald': 'bg-emerald-50/70 text-emerald-700 border-emerald-100',
            'blue': 'bg-blue-50/70 text-blue-700 border-blue-100'
        };

        grid.innerHTML = items.map(function(insight) {
            const colorKey = insight.color || insight.priorityColor || 'blue';
            const wrapperBg = bgClassMap[colorKey] || 'bg-zinc-50 border-zinc-100';
            const badgeLabel = insight.badge || insight.priority || 'Live AI Insight';
            const cardTitle = insight.title || insight.ai_summary || 'AI Processing metrics...';

            return '<div class="insight-card text-left rounded-xl p-5 flex flex-col justify-between h-full border border-zinc-200/80 bg-white/90 hover:shadow-md transition-all cursor-pointer group" onclick="openInsightModal(' + JSON.stringify(insight).replace(/"/g, '&quot;') + ')">' +
                '<div>' +
                '<div class="flex items-start justify-between">' +
                '<div class="p-2 ' + wrapperBg + ' rounded-lg w-fit border">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + (iconMap[insight.icon] || iconMap['ai']) + '</svg>' +
                '</div>' +
                '<span class="text-[10px] font-bold px-2.5 py-0.5 rounded-full border ' + wrapperBg + '">' + badgeLabel + '</span>' +
                '</div>' +
                '<p class="text-sm font-semibold text-zinc-800 mt-4 leading-relaxed">' + cardTitle + '</p>' +
                '</div>' +
                '<div class="flex items-center justify-between mt-4 pt-3 border-t border-zinc-100">' +
                '<span class="text-[10px] font-bold text-zinc-400 uppercase tracking-wider">AI Processed</span>' +
                '<span class="text-[10px] font-semibold text-purple-600 hover:underline cursor-pointer" onclick="goToModule(' + JSON.stringify(insight).replace(/"/g, '&quot;') + ', event)">Click details →</span>' +
                '</div>' +
                '</div>';
        }).join('');
    }

    function renderPredictive(cards) {
        const container = document.getElementById('predictiveCards');
        if (!container) return;
        const items = (cards && Array.isArray(cards) && cards.length > 0) ? cards : [];
        if (items.length === 0) {
            container.innerHTML = '<div class="col-span-3 text-center py-6 text-xs text-zinc-400 font-semibold border border-dashed border-zinc-200 rounded-xl bg-zinc-50/50">No predictive forecast data recorded for this selection.</div>';
            return;
        }

        const iconMap = {
            'alert': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
            'document': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>',
            'health': '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 2a1 1 0 000 2h1v2.101a7.002 7.002 0 00-5.998 8.267l-1.06 1.06a1 1 0 001.415 1.415l.96-.96A6.99 6.99 0 0012 18a6.99 6.99 0 004.683-1.117l.96.96a1 1 0 001.415-1.415l-1.06-1.06A7.002 7.002 0 0014 6.101V4h1a1 1 0 100-2H9z"></path>'
        };

        const badgeMap = {
            'indigo': 'bg-indigo-50 border-indigo-100 text-indigo-700',
            'blue': 'bg-blue-50 border-blue-100 text-blue-700',
            'amber': 'bg-amber-50 border-amber-100 text-amber-700'
        };

        container.innerHTML = items.map(function(item) {
            var unitHtml = item.unit ? ' <span class="text-xs font-normal text-zinc-400">' + item.unit + '</span>' : '';
            const confVal = parseInt(item.confidence) || 85;
            const indicatorColor = confVal >= 80 ? 'emerald' : 'amber';
            const badgeBg = badgeMap[item.color] || 'bg-zinc-50 border-zinc-200 text-zinc-700';
            const iconSvg = iconMap[item.icon] || (item.key === 'permits' ? iconMap['document'] : (item.key === 'vaccines' ? iconMap['health'] : iconMap['alert']));
            const detailTitle = item.detail || (item.title + ' Analysis');
            const rowsData = item.rows || [
                { label: 'Forecasted Value', value: item.value + (item.unit ? ' ' + item.unit : '') },
                { label: 'Model Confidence', value: item.confidence || '90%' },
                { label: 'R² Correlation Fit', value: item.r_squared ? String(item.r_squared) : '0.92' }
            ];
            const pieData = item.pieData || [
                { label: 'Projected Target', value: Math.max(1, parseInt(item.value) || 10), color: '#10b981' },
                { label: 'Variance Margin', value: 5, color: '#f59e0b' }
            ];

            return '<div class="predictive-card rounded-xl border border-zinc-200/80 p-4 transition-all duration-200 cursor-pointer" ' +
                'onmouseenter="showPredictiveTooltip(event, \'' + detailTitle + '\', ' + JSON.stringify(rowsData).replace(/"/g, '&quot;') + ', ' + JSON.stringify(pieData).replace(/"/g, '&quot;') + ')" ' +
                'onmouseleave="hidePredictiveTooltip()">' +
                '<div class="flex items-center gap-3">' +
                '<div class="p-2 ' + badgeBg + ' border rounded-lg shrink-0">' +
                '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + iconSvg + '</svg>' +
                '</div>' +
                '<div class="flex-1 min-w-0">' +
                '<p class="text-xs font-semibold text-zinc-400 truncate uppercase tracking-wider">' + item.title + '</p>' +
                '<p class="text-xl font-extrabold text-zinc-900 mt-0.5">' + item.value + unitHtml + '</p>' +
                '</div>' +
                '<span class="text-xs font-bold text-' + indicatorColor + '-600 shrink-0 bg-' + indicatorColor + '-50/50 px-2 py-0.5 rounded-md border border-' + indicatorColor + '-100">' + item.confidence + '</span>' +
                '</div>' +
                '<div class="h-1.5 w-full bg-zinc-100 rounded-full overflow-hidden mt-3">' +
                '<div class="h-full bg-' + indicatorColor + '-500 rounded-full transition-all duration-1000" style="width:' + item.confidence + '"></div>' +
                '</div>' +
                '<p class="text-[10px] font-bold text-zinc-400 mt-2 flex items-center justify-between">' +
                '<span>' + (item.trend || 'Next month AI regression forecast') + '</span>' +
                '<span class="text-blue-500 font-semibold uppercase tracking-wider no-print">Hover for details</span>' +
                '</p>' +
                '</div>';
        }).join('');
    }

    window.showPredictiveTooltip = function(event, title, rows, pieData) {
        showTooltip(event, title, {
            details: rows,
            pieData
        }, true);
    };
    window.hidePredictiveTooltip = function() {
        hideTooltip();
    };

    window.showModuleTooltip = function(event, label, share, trend, status, color) {
        const tooltip = document.getElementById('moduleTooltip');
        if (!tooltip) return;
        const rect = event.currentTarget.getBoundingClientRect();

        const titleEl = document.getElementById('moduleTooltipTitle');
        if (titleEl) titleEl.textContent = label;

        const contentEl = document.getElementById('moduleTooltipContent');
        if (contentEl) {
            contentEl.innerHTML = `
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Share of Activity</span><span style="font-weight:700;color:#18181b;">${share}</span></div>
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Trend</span><span style="font-weight:700;color:#18181b;">${trend}</span></div>
            <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;"><span style="color:#71717a;">Status</span><span style="font-weight:700;color:#18181b;">${status}</span></div>
            <div style="margin-top:10px;height:5px;background:#f4f4f5;border-radius:9999px;overflow:hidden;"><div style="height:100%;width:${share};background:${color};border-radius:9999px;"></div></div>
        `;
        }

        let left = rect.left + rect.width / 2 - 100;
        let top = rect.bottom + 10;
        if (left + 200 > window.innerWidth - 20) left = window.innerWidth - 220;
        if (left < 20) left = 20;
        if (top + 180 > window.innerHeight - 20) top = rect.top - 180;

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        tooltip.classList.add('active');
    };

    window.hideModuleTooltip = function() {
        const tooltip = document.getElementById('moduleTooltip');
        if (tooltip) tooltip.classList.remove('active');
    };

    window.renderMetrics = function(liveMetrics) {
        const grid = document.getElementById('metricsGrid');
        if (!grid) return;

        const list = (liveMetrics && Array.isArray(liveMetrics) && liveMetrics.length > 0) ? liveMetrics : [];

        if (list.length === 0) {
            grid.innerHTML = '<div class="col-span-full py-6 text-center text-xs text-zinc-400 font-semibold border border-dashed border-zinc-200 rounded-xl bg-zinc-50/50">No performance metrics logged for this period.</div>';
            return;
        }

        const iconColorMap = {
            'emerald': 'text-emerald-600 bg-emerald-50 border-emerald-100',
            'blue': 'text-blue-600 bg-blue-50 border-blue-100',
            'teal': 'text-teal-600 bg-teal-50 border-teal-100',
            'purple': 'text-purple-600 bg-purple-50 border-purple-100',
            'amber': 'text-amber-600 bg-amber-50 border-amber-100'
        };

        const ringColorMap = {
            'emerald': '#10b981',
            'blue': '#3b82f6',
            'teal': '#14b8a6',
            'purple': '#8b5cf6',
            'amber': '#f59e0b'
        };

        grid.innerHTML = list.map(function(m) {
            const isPositive = (m.change || '').includes('↑');
            const changeClass = isPositive ? 'positive' : 'negative';
            const changeIcon = isPositive ? '↑' : '↓';
            const ringColor = ringColorMap[m.changeColor] || '#3b82f6';
            const ringOffset = 100 - (m.progress || 0);

            return '<div class="kpi-card rounded-2xl border border-slate-100 bg-white p-3 cursor-pointer group ' + (m.glow || '') + '" ' +
                'onmouseenter="showMetricTooltip(event, \'' + m.label + '\', ' + JSON.stringify(m.details || []).replace(/"/g, '&quot;') + ', ' + JSON.stringify(m.pieData || []).replace(/"/g, '&quot;') + ')" ' +
                'onmouseleave="hideMetricTooltip()">' +
                '<div class="kpi-shine no-print"></div>' +
                '<div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-' + (m.changeColor || 'blue') + '-400 to-' + (m.changeColor || 'blue') + '-600"></div>' +
                '<i class="fas ' + (m.watermark || 'fa-chart-line') + ' kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-' + (m.changeColor || 'blue') + '-500/10 rotate-[-8deg] pointer-events-none no-print"></i>' +
                '<div class="relative p-1">' +
                '<div class="flex items-start justify-between gap-2">' +
                '<div>' +
                '<p class="text-[8px] font-bold uppercase tracking-wider text-' + (m.changeColor || 'blue') + '-600">' + m.label + '</p>' +
                '<p class="kpi-value text-xl font-black mt-1 leading-none" data-target="' + m.value + '">0' + (m.unit ? '<span class="text-xs font-semibold text-slate-400"> ' + m.unit + '</span>' : '') + '</p>' +
                '</div>' +
                '<svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">' +
                '<circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>' +
                '<circle cx="18" cy="18" r="15.5" fill="none" stroke="' + ringColor + '" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:' + ringOffset + '" transform="rotate(-90 18 18)"/>' +
                '<text x="18" y="20.5" text-anchor="middle" font-size="8" font-weight="700" fill="' + ringColor + '">' + (m.progress || 0) + '%</text>' +
                '</svg>' +
                '</div>' +
                '<div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">' +
                '<span class="kpi-change ' + changeClass + '">' + changeIcon + ' ' + (m.change || '').replace(/[↑↓]\s*/, '') + '</span>' +
                '<span class="text-[8px] text-slate-400">vs last month</span>' +
                '</div>' +
                '</div>' +
                '</div>';
        }).join('');

        // Animate Counters
        document.querySelectorAll('.kpi-value').forEach(el => {
            const target = parseFloat(el.getAttribute('data-target')) || 0;
            const duration = 1200;
            const startTime = performance.now();

            function updateNumber(currentTime) {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const current = Math.floor(progress * target);
                const unitHtml = el.innerHTML.includes('<span') ? el.innerHTML.substring(el.innerHTML.indexOf('<span')) : '';
                el.innerHTML = current.toLocaleString() + unitHtml;
                if (progress < 1) requestAnimationFrame(updateNumber);
                else el.innerHTML = target.toLocaleString() + unitHtml;
            }
            requestAnimationFrame(updateNumber);
        });
    };

    window.showMetricTooltip = function(event, title, details, pieData) {
        showTooltip(event, title, { details, pieData }, true);
    };
    window.hideMetricTooltip = function() {
        hideTooltip();
    };

    window.showStaffTooltip = function(event, name, score, cases, response) {
        const tooltip = document.getElementById('staffTooltip') || createStaffTooltip();
        if (!tooltip) return;
        const rect = event.currentTarget.getBoundingClientRect();
        const status = score >= 85 ? '✅ Exceeds expectations' : score >= 80 ? '✅ Meets expectations' : '⚠️ Needs improvement';

        tooltip.innerHTML = `
        <div style="font-weight:700;font-size:13px;color:#18181b;margin-bottom:10px;letter-spacing:-0.01em;">${name}</div>
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Overall Score</span><span style="font-weight:700;color:#18181b;">${score}%</span></div>
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Cases Handled</span><span style="font-weight:700;color:#18181b;">${cases}</span></div>
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;border-bottom:1px solid #f4f4f5;"><span style="color:#71717a;">Avg. Response Time</span><span style="font-weight:700;color:#18181b;">${response} hrs</span></div>
        <div style="display:flex;justify-content:space-between;padding:5px 0;font-size:11px;"><span style="color:#71717a;">Status</span><span style="font-weight:700;color:#18181b;">${status}</span></div>
    `;

        let left = rect.left + rect.width / 2 - 125;
        let top = rect.bottom + 10;
        if (left + 250 > window.innerWidth - 20) left = window.innerWidth - 270;
        if (left < 20) left = 20;
        if (top + 200 > window.innerHeight - 20) top = rect.top - 200;

        tooltip.style.left = left + 'px';
        tooltip.style.top = top + 'px';
        tooltip.classList.add('active');
    };

    window.hideStaffTooltip = function() {
        const tooltip = document.getElementById('staffTooltip');
        if (tooltip) tooltip.classList.remove('active');
    };

    function createStaffTooltip() {
        const div = document.createElement('div');
        div.id = 'staffTooltip';
        div.className = 'module-tooltip';
        document.body.appendChild(div);
        return div;
    }

    function renderLegend(items) {
        const legendEl = document.getElementById('trendLegend');
        if (!legendEl) return;
        legendEl.innerHTML = (items || []).map(function(item) {
            return '<span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full ' + (item.color || 'bg-zinc-400') + '"></span> ' + item.label + '</span>';
        }).join('');
    }

    // =====================================================================
    // APEXCHARTS INITIALIZATIONS (EMPTY SKELETONS AWAITING LIVE DATA)
    // =====================================================================
    var trendOptions = {
        series: [],
        noData: { text: 'Loading live trends from Supabase...', style: { color: '#a1a1aa', fontSize: '11px' } },
        chart: {
            type: 'line',
            height: 224,
            toolbar: { show: false },
            background: 'transparent',
            animations: { enabled: true, easing: 'easeinout', speed: 600 }
        },
        stroke: { curve: 'smooth', width: 3, lineCap: 'round' },
        grid: { borderColor: '#f4f4f5', strokeDashArray: 3, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
        xaxis: {
            categories: [],
            labels: { style: { colors: '#a1a1aa', fontSize: '10px', fontWeight: '500' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { labels: { style: { colors: '#a1a1aa', fontSize: '10px', fontWeight: '500' } }, min: 0 },
        legend: { show: false },
        tooltip: {
            theme: 'light',
            shared: true,
            intersect: false,
            custom: function({series, seriesIndex, dataPointIndex, w}) {
                var category = (w.globals.categoryLabels && w.globals.categoryLabels[dataPointIndex]) || (w.globals.labels && w.globals.labels[dataPointIndex]) || 'Period';
                var html = '<div class="p-3 bg-white/95 backdrop-blur-md rounded-xl border border-zinc-200 shadow-xl min-w-[200px] text-xs font-sans">';
                html += '<div class="font-bold text-zinc-900 border-b border-zinc-100 pb-2 mb-2 flex items-center justify-between"><span>' + category + '</span><span class="px-2 py-0.5 bg-zinc-100 text-zinc-600 text-[10px] font-bold rounded-full border border-zinc-200">Historical Record</span></div>';
                
                var colors = w.config.colors || ['#ef4444', '#14b8a6', '#f59e0b', '#3b82f6', '#9333ea'];
                var hasNonZero = false;
                if (w.config.series && Array.isArray(w.config.series)) {
                    w.config.series.forEach(function(s, idx) {
                        var val = (s.data && s.data[dataPointIndex] !== undefined) ? s.data[dataPointIndex] : null;
                        if (val !== null && val !== undefined && Number(val) > 0) {
                            hasNonZero = true;
                            var color = colors[idx % colors.length];
                            html += '<div class="flex items-center justify-between py-1 border-b border-zinc-50 last:border-0">';
                            html += '<span class="flex items-center gap-1.5 text-zinc-600 font-medium"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:' + color + '"></span> ' + s.name + '</span>';
                            html += '<span class="font-extrabold text-zinc-900">' + val + '</span>';
                            html += '</div>';
                        }
                    });
                }
                if (!hasNonZero) {
                    html += '<div class="py-2 text-center text-zinc-400 font-medium italic text-[11px]">No records logged for this period</div>';
                }
                html += '</div>';
                return html;
            }
        },
        markers: { size: 4, hover: { size: 6, sizeOffset: 3 } }
    };
    var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
    trendChart.render();

    var predictiveOptions = {
        series: [],
        noData: { text: 'Calculating AI regression forecast...', style: { color: '#a1a1aa', fontSize: '11px' } },
        chart: {
            type: 'line',
            height: 224,
            toolbar: { show: false },
            background: 'transparent',
            animations: { enabled: true, easing: 'easeinout', speed: 600 }
        },
        colors: ['#ef4444', '#14b8a6', '#f59e0b', '#3b82f6', '#9333ea'],
        stroke: { curve: 'smooth', width: 3, dashArray: [0, 0, 0, 0, 0] },
        xaxis: {
            categories: [],
            labels: { style: { colors: '#a1a1aa', fontSize: '10px', fontWeight: '500' } },
            axisBorder: { show: false },
            axisTicks: { show: false }
        },
        yaxis: { labels: { style: { colors: '#a1a1aa', fontSize: '10px', fontWeight: '500' } }, min: 0 },
        grid: { borderColor: '#f4f4f5', strokeDashArray: 4, padding: { top: 0, right: 0, bottom: 0, left: 10 } },
        legend: { show: false },
        tooltip: {
            theme: 'light',
            shared: true,
            intersect: false,
            custom: function({series, seriesIndex, dataPointIndex, w}) {
                var category = (w.globals.categoryLabels && w.globals.categoryLabels[dataPointIndex]) || (w.globals.labels && w.globals.labels[dataPointIndex]) || 'Period';
                var isBaseline = (dataPointIndex === 0);
                var badgeTag = isBaseline 
                    ? '<span class="ml-1.5 px-2 py-0.5 bg-zinc-100 text-zinc-700 text-[10px] font-bold rounded-full border border-zinc-200">Current Baseline</span>' 
                    : '<span class="ml-1.5 px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[10px] font-extrabold rounded-full border border-emerald-200">★ AI PROJECTION</span>';
                
                var html = '<div class="p-3 bg-white/95 backdrop-blur-md rounded-xl border border-zinc-200 shadow-xl min-w-[220px] text-xs font-sans">';
                html += '<div class="font-bold text-zinc-900 border-b border-zinc-100 pb-2 mb-2 flex items-center justify-between"><span>' + category + '</span>' + badgeTag + '</div>';
                
                var colors = w.config.colors || ['#ef4444', '#14b8a6', '#f59e0b', '#3b82f6', '#9333ea'];
                var hasNonZero = false;
                if (w.config.series && Array.isArray(w.config.series)) {
                    w.config.series.forEach(function(s, idx) {
                        var val = (s.data && s.data[dataPointIndex] !== undefined) ? s.data[dataPointIndex] : null;
                        if (val !== null && val !== undefined && Number(val) > 0) {
                            hasNonZero = true;
                            var color = colors[idx % colors.length];
                            html += '<div class="flex items-center justify-between py-1 border-b border-zinc-50 last:border-0">';
                            html += '<span class="flex items-center gap-1.5 text-zinc-600 font-medium"><span class="inline-block w-2.5 h-2.5 rounded-full" style="background:' + color + '"></span> ' + s.name + '</span>';
                            html += '<span class="font-extrabold text-zinc-900">' + val + '</span>';
                            html += '</div>';
                        }
                    });
                }

                if (!hasNonZero) {
                    html += '<div class="py-2 text-center text-zinc-400 font-medium italic text-[11px]">No projected activity for this period</div>';
                }
                
                html += '<div class="mt-2 pt-1.5 border-t border-zinc-100 text-[10px] text-emerald-700 font-bold text-center">Ordinary Least Squares Forward Horizon</div>';
                html += '</div>';
                return html;
            }
        },
        markers: { size: 3.5, hover: { size: 6 } }
    };
    var predictiveChart = new ApexCharts(document.querySelector("#predictiveLineChart"), predictiveOptions);
    predictiveChart.render();

    var modulesOptions = {
        series: [],
        noData: { text: 'Loading module activity...', style: { color: '#a1a1aa', fontSize: '11px' } },
        chart: {
            type: 'pie',
            height: 224,
            toolbar: { show: false },
            background: 'transparent',
            animations: { enabled: true, easing: 'easeinout', speed: 800 },
            events: {
                dataPointMouseEnter: function(event, chartContext, config) {
                    if (moduleRawData && moduleRawData.length > 0) {
                        const m = moduleRawData[config.dataPointIndex];
                        if (m) {
                            var currentVal = (moduleMode === 'projected') ? m.projected_share : m.share;
                            showModuleTooltip(event, m.label, currentVal + '%', (m.delta || m.trend || 'Active'), m.status, m.color);
                        }
                    }
                },
                dataPointMouseLeave: function() {
                    hideModuleTooltip();
                }
            }
        },
        labels: [],
        colors: ['#3b82f6', '#10b981', '#f59e0b', '#f43f5e', '#a855f7'],
        stroke: { width: 3, colors: ['#ffffff'] },
        legend: { show: false },
        dataLabels: {
            enabled: true,
            style: { fontSize: '11px', fontWeight: 'bold' },
            dropShadow: { enabled: false }
        },
        tooltip: { enabled: false }
    };
    var modulesChart = new ApexCharts(document.querySelector("#modulesChart"), modulesOptions);
    modulesChart.render();

    function showLoadingState() {
        if (document.getElementById('headerTimestamp')) {
            document.getElementById('headerTimestamp').innerHTML = '<span class="inline-flex items-center gap-1.5 text-indigo-600 font-semibold"><i class="fa-solid fa-arrows-rotate fa-spin text-xs"></i> Syncing Live Supabase Data...</span>';
        }
        const refreshBtn = document.getElementById('btnManualRefresh');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            if (icon) icon.classList.add('fa-spin');
        }
        if (document.getElementById('trendSkeleton')) document.getElementById('trendSkeleton').classList.remove('hidden');
        if (document.getElementById('trendEmptyState')) document.getElementById('trendEmptyState').classList.add('hidden');
        if (document.getElementById('trendChart')) document.getElementById('trendChart').style.opacity = '0.2';

        if (document.getElementById('predictiveSkeleton')) document.getElementById('predictiveSkeleton').classList.remove('hidden');
        if (document.getElementById('predictiveEmptyState')) document.getElementById('predictiveEmptyState').classList.add('hidden');
        if (document.getElementById('predictiveLineChart')) document.getElementById('predictiveLineChart').style.opacity = '0.2';
    }

    function hideLoadingState() {
        const refreshBtn = document.getElementById('btnManualRefresh');
        if (refreshBtn) {
            const icon = refreshBtn.querySelector('i');
            if (icon) icon.classList.remove('fa-spin');
        }
        if (document.getElementById('trendSkeleton')) document.getElementById('trendSkeleton').classList.add('hidden');
        if (document.getElementById('predictiveSkeleton')) document.getElementById('predictiveSkeleton').classList.add('hidden');
    }

    var isAutoRefreshEnabled = true;

    async function fetchLiveAnalytics(forceRefresh = false, isSilent = false) {
        var range = document.getElementById('dateRangeSelect') ? document.getElementById('dateRangeSelect').value : '6m';
        var filterEl = document.getElementById('trendFilter');
        var filter = filterEl ? filterEl.value : 'combined';
        var yoy = document.getElementById('yoyToggle') ? document.getElementById('yoyToggle').checked : false;

        if (!isSilent) {
            showLoadingState();
        }

        try {
            var url = '../api/analytics.php?range=' + range + '&filter=' + filter + '&yoy=' + yoy + (forceRefresh ? '&refresh=1' : '');
            var res = await fetch(url);
            var data = await res.json();
            if (data && data.success) {
                var now = new Date();
                var syncText = 'Live (' + now.toLocaleTimeString() + ')';
                if (document.getElementById('headerTimestamp')) document.getElementById('headerTimestamp').textContent = syncText;
                if (document.getElementById('footerTimestamp')) document.getElementById('footerTimestamp').textContent = now.toLocaleString();

                // Update Subtitle
                if (data.trend && data.trend.subtitle && document.getElementById('trendSubtitle')) {
                    document.getElementById('trendSubtitle').textContent = data.trend.subtitle;
                }

                // Handle Trend Chart & Empty State
                if (data.trend) {
                    var totalTrendPoints = (data.trend.series || []).reduce(function(acc, s) {
                        return acc + (s.data || []).reduce(function(a, b) { return a + Number(b || 0); }, 0);
                    }, 0);

                    if (totalTrendPoints === 0 || !data.trend.series || data.trend.series.length === 0) {
                        if (document.getElementById('trendEmptyState')) document.getElementById('trendEmptyState').classList.remove('hidden');
                        if (document.getElementById('trendChart')) document.getElementById('trendChart').style.opacity = '0';
                    } else {
                        if (document.getElementById('trendEmptyState')) document.getElementById('trendEmptyState').classList.add('hidden');
                        if (document.getElementById('trendChart')) document.getElementById('trendChart').style.opacity = '1';
                        trendChart.updateOptions({
                            series: data.trend.series,
                            colors: data.trend.colors,
                            xaxis: { categories: data.trend.categories }
                        });
                    }
                    renderLegend(data.trend.legend);
                }

                // Handle Predictive Chart & Empty State
                if (data.predictive) {
                    var totalPredPoints = (data.predictive.series || []).reduce(function(acc, s) {
                        return acc + (s.data || []).reduce(function(a, b) { return a + Number(b || 0); }, 0);
                    }, 0);

                    if (totalPredPoints === 0 || !data.predictive.series || data.predictive.series.length === 0) {
                        if (document.getElementById('predictiveEmptyState')) document.getElementById('predictiveEmptyState').classList.remove('hidden');
                        if (document.getElementById('predictiveLineChart')) document.getElementById('predictiveLineChart').style.opacity = '0';
                    } else {
                        if (document.getElementById('predictiveEmptyState')) document.getElementById('predictiveEmptyState').classList.add('hidden');
                        if (document.getElementById('predictiveLineChart')) document.getElementById('predictiveLineChart').style.opacity = '1';
                        predictiveChart.updateOptions({
                            series: data.predictive.series,
                            xaxis: { categories: data.predictive.categories }
                        });
                    }

                    if (data.predictive.cards) {
                        renderPredictive(data.predictive.cards);
                    }
                }
                
                // Update AI Insights Grid
                if (data.insights) {
                    renderInsights(data.insights);
                }
                
                // Update Operational Modules & Callout Insights
                if (data.modules) {
                    renderModulesView(data.modules);
                }
                if (data.forecast_insight && document.getElementById('forecastInsightText')) {
                    document.getElementById('forecastInsightText').textContent = data.forecast_insight;
                }
                if (data.module_insight && document.getElementById('moduleInsightText')) {
                    document.getElementById('moduleInsightText').textContent = data.module_insight;
                }
                if (data.correlation_insight && document.getElementById('correlationInsightText')) {
                    document.getElementById('correlationInsightText').textContent = data.correlation_insight;
                }
                if (data.staff && typeof updateStaffData === 'function') {
                    updateStaffData(data.staff);
                }
                if (data.metrics && typeof renderMetrics === 'function') {
                    renderMetrics(data.metrics);
                }
                
                // Update KPI summary numbers if elements exist
                if (data.kpis && Array.isArray(data.kpis)) {
                    data.kpis.forEach(function(kpi) {
                        var el = document.getElementById('kpi-' + kpi.key);
                        if (el) el.textContent = kpi.value;
                    });
                }
            }
        } catch (err) {
            console.log('API Fetch Error:', err);
        } finally {
            if (!isSilent) {
                hideLoadingState();
            }
        }
    }

    var moduleMode = 'current'; // 'current' or 'projected'
    var moduleRawData = [];

    function renderModulesView(data) {
        if (data && Array.isArray(data)) moduleRawData = data;
        var legendEl = document.getElementById('moduleLegend');
        if (!legendEl) return;

        if (moduleRawData.length === 0) {
            legendEl.innerHTML = '<div class="text-xs text-zinc-400 py-3 text-center">No module distribution recorded.</div>';
            return;
        }

        var isProjected = (moduleMode === 'projected');
        
        var btnCurr = document.getElementById('btnModuleCurrent');
        var btnProj = document.getElementById('btnModuleProjected');
        if (btnCurr && btnProj) {
            if (isProjected) {
                btnCurr.className = "px-2.5 py-0.5 rounded-full text-zinc-500 hover:text-zinc-800 transition-all cursor-pointer font-medium";
                btnProj.className = "px-2.5 py-0.5 rounded-full bg-indigo-600 text-white shadow-xs transition-all cursor-pointer font-bold";
            } else {
                btnCurr.className = "px-2.5 py-0.5 rounded-full bg-white text-zinc-800 shadow-xs transition-all cursor-pointer font-bold";
                btnProj.className = "px-2.5 py-0.5 rounded-full text-zinc-500 hover:text-zinc-800 transition-all cursor-pointer font-medium";
            }
        }

        if (typeof modulesChart !== 'undefined' && moduleRawData.length > 0) {
            modulesChart.updateOptions({
                series: moduleRawData.map(function(m) { return isProjected ? m.projected_share : m.share; }),
                labels: moduleRawData.map(function(m) { return m.label; }),
                colors: moduleRawData.map(function(m) { return m.color; })
            });
        }

        legendEl.innerHTML = moduleRawData.map(function(m) {
            var val = isProjected ? m.projected_share : m.share;
            var isLow = (m.confidence === 'low');
            var cardBorder = (isProjected && isLow) ? 'border-dashed border-amber-300 bg-amber-50/40 p-1.5 rounded-lg' : '';
            var badgeHtml = (isProjected && isLow) ? '<span title="Limited historical data (<15 logs). Projection is estimated." class="ml-1 text-[9px] px-1.5 py-0.5 bg-amber-100 text-amber-800 border border-amber-200 rounded font-bold">⚠️ Low Data</span>' : '';
            
            return '<div class="flex items-center justify-between text-xs font-semibold ' + cardBorder + '">' +
                '<span class="flex items-center gap-2"><span class="inline-block h-2.5 w-2.5 rounded-full" style="background:' + m.color + '"></span> ' + m.label + badgeHtml + '</span>' +
                '<div class="flex items-center gap-2">' +
                '<span class="font-bold text-zinc-800">' + val + '%</span>' +
                '<span class="text-[10px] text-zinc-400 font-medium">(' + (m.delta || '↑ 1.0pts') + ')</span>' +
                '</div>' +
                '</div>';
        }).join('');
    }

    if (document.getElementById('btnModuleCurrent')) {
        document.getElementById('btnModuleCurrent').addEventListener('click', function() {
            moduleMode = 'current';
            renderModulesView();
        });
    }
    if (document.getElementById('btnModuleProjected')) {
        document.getElementById('btnModuleProjected').addEventListener('click', function() {
            moduleMode = 'projected';
            renderModulesView();
        });
    }

    // Toggle Auto Refresh Button Handler
    if (document.getElementById('toggleAutoRefresh')) {
        document.getElementById('toggleAutoRefresh').addEventListener('click', function() {
            isAutoRefreshEnabled = !isAutoRefreshEnabled;
            var btn = this;
            var statusText = document.getElementById('autoRefreshStatusText');
            if (isAutoRefreshEnabled) {
                btn.className = "flex items-center gap-1.5 px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200/60 rounded-lg text-xs font-bold transition hover:bg-emerald-100 cursor-pointer";
                if (statusText) statusText.textContent = "Auto-Refresh: ON";
            } else {
                btn.className = "flex items-center gap-1.5 px-2.5 py-1 bg-zinc-100 text-zinc-600 border border-zinc-200 rounded-lg text-xs font-bold transition hover:bg-zinc-200 cursor-pointer";
                if (statusText) statusText.textContent = "Auto-Refresh: OFF";
            }
        });
    }

    // Manual Refresh Button Handler
    if (document.getElementById('btnManualRefresh')) {
        document.getElementById('btnManualRefresh').addEventListener('click', function() {
            fetchLiveAnalytics(true, false);
        });
    }

    // Staff Performance Handling
    var staffData = [];

    window.updateStaffData = function(liveStaff) {
        staffData = Array.isArray(liveStaff) ? liveStaff : [];
        const countBadge = document.getElementById('staffCountBadge');
        if (countBadge) {
            countBadge.textContent = staffData.length > 0 ? (staffData.length + ' Personnel') : '0 Personnel';
        }
        if (typeof staffChart !== 'undefined' && staffChart && document.querySelector("#staffChart")) {
            if (staffData.length === 0) {
                document.querySelector("#staffChart").innerHTML = '<div class="flex items-center justify-center h-40 text-xs font-semibold text-zinc-400">No personnel records currently registered under this department.</div>';
            } else {
                staffChart.updateOptions(buildStaffOptions(sortedStaff()), true, true);
            }
        }
    };

    function sortedStaff() {
        var sortEl = document.getElementById('staffSort');
        var dir = sortEl ? sortEl.value : 'desc';
        return staffData.slice().sort(function(a, b) {
            return dir === 'asc' ? a.score - b.score : b.score - a.score;
        });
    }

    function buildStaffOptions(data) {
        const dynamicHeight = Math.max(260, (data.length || 1) * 44);
        return {
            series: [{
                name: 'Performance',
                data: data.map(function(d) { return d.score; })
            }],
            chart: {
                type: 'bar',
                height: dynamicHeight,
                toolbar: { show: false },
                background: 'transparent',
                animations: { enabled: true, easing: 'easeinout', speed: 800 },
                events: {
                    dataPointMouseEnter: function(event, chartContext, config) {
                        clearTimeout(window.__staffHideTimer);
                        const d = data[config.dataPointIndex];
                        if (d) {
                            showStaffTooltip(event, d.name, d.score, d.cases, d.response);
                        }
                    },
                    dataPointMouseLeave: function() {
                        window.__staffHideTimer = setTimeout(function() {
                            hideStaffTooltip();
                        }, 120);
                    }
                }
            },
            colors: ['#6366f1'],
            plotOptions: {
                bar: { borderRadius: 6, horizontal: true, barHeight: '52%' }
            },
            grid: { borderColor: '#f4f4f5', strokeDashArray: 3 },
            xaxis: {
                categories: data.map(function(d) { return d.name; }),
                labels: { style: { colors: '#a1a1aa', fontSize: '11px', fontWeight: '500' } },
                max: 100,
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: { style: { colors: '#27272a', fontSize: '11px', fontWeight: '600' } }
            },
            dataLabels: {
                enabled: true,
                formatter: function(val) { return val + '%'; },
                style: { fontSize: '10px', fontWeight: 'bold', colors: ['#4338ca'] },
                offsetX: 20
            },
            tooltip: { enabled: false },
            annotations: {
                xaxis: [{
                    x: 80,
                    borderColor: '#f59e0b',
                    label: {
                        borderColor: '#f59e0b',
                        style: { color: '#fff', background: '#f59e0b', fontSize: '9px', fontWeight: '700' },
                        text: 'Target: 80%'
                    }
                }]
            }
        };
    }

    var staffChartEl = document.querySelector("#staffChart");
    var staffChart = null;
    if (staffChartEl) {
        staffChart = new ApexCharts(staffChartEl, {
            series: [],
            noData: { text: 'Loading personnel records...', style: { color: '#a1a1aa', fontSize: '11px' } },
            chart: { type: 'bar', height: 260, toolbar: { show: false } }
        });
        staffChart.render();

        var staffSortEl = document.getElementById('staffSort');
        if (staffSortEl) {
            staffSortEl.addEventListener('change', function() {
                if (staffData.length > 0) {
                    staffChart.updateOptions(buildStaffOptions(sortedStaff()), true, true);
                }
            });
        }
    }

    // Filter change event listeners
    var trendFilterEl = document.getElementById('trendFilter');
    if (trendFilterEl) {
        trendFilterEl.addEventListener('change', function() { fetchLiveAnalytics(true, false); });
    }
    if (document.getElementById('yoyToggle')) {
        document.getElementById('yoyToggle').addEventListener('change', function() { fetchLiveAnalytics(true, false); });
    }
    if (document.getElementById('dateRangeSelect')) {
        document.getElementById('dateRangeSelect').addEventListener('change', function(e) {
            if (document.getElementById('customDateWrap')) {
                document.getElementById('customDateWrap').classList.toggle('hidden', e.target.value !== 'custom');
                document.getElementById('customDateWrap').classList.toggle('flex', e.target.value === 'custom');
            }
            if (e.target.value !== 'custom') { fetchLiveAnalytics(true, false); }
        });
    }
    if (document.getElementById('dateFrom')) {
        document.getElementById('dateFrom').addEventListener('change', function() { fetchLiveAnalytics(true, false); });
    }
    if (document.getElementById('dateTo')) {
        document.getElementById('dateTo').addEventListener('change', function() { fetchLiveAnalytics(true, false); });
    }

    // Supabase Realtime WebSocket Connection (Instant Push on DB changes)
    var supabaseConfig = window.SUPABASE_CONFIG || {};
    var SUPABASE_URL = supabaseConfig.url;
    var SUPABASE_ANON_KEY = supabaseConfig.anonKey;

    if (SUPABASE_URL && SUPABASE_ANON_KEY && typeof supabase !== 'undefined') {
        try {
            var supabaseClient = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
            supabaseClient
                .channel('realtime-ai-analytics')
                .on('postgres_changes', { event: '*', schema: 'public', table: 'surveillance_cases' }, function() {
                    fetchLiveAnalytics(true, true);
                })
                .on('postgres_changes', { event: '*', schema: 'public', table: 'patients' }, function() {
                    fetchLiveAnalytics(true, true);
                })
                .on('postgres_changes', { event: '*', schema: 'public', table: 'permits' }, function() {
                    fetchLiveAnalytics(true, true);
                })
                .on('postgres_changes', { event: '*', schema: 'public', table: 'surveillance_alerts' }, function() {
                    fetchLiveAnalytics(true, true);
                })
                .subscribe();
        } catch (err) {
            console.log('Supabase Realtime subscription fallback:', err);
        }
    }

    // Initialize live analytics immediately on page load
    fetchLiveAnalytics(false, false);
});
