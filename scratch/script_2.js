
    document.addEventListener('DOMContentLoaded', function() {

        // =====================================================================
        // DATA
        // =====================================================================
        const InsightsData = [{
                id: 'dengue',
                title: 'Dengue cases increased <span class="text-red-600 font-bold">18%</span> compared to last month.',
                priority: 'High Priority',
                priorityColor: 'red',
                icon: 'alert',
                detail: 'Dengue Cases – Barangay Breakdown',
                subtitle: '18% increase vs last month',
                rows: [{
                        label: 'Barangay 172',
                        value: '42 cases'
                    },
                    {
                        label: 'Barangay 176',
                        value: '31 cases'
                    },
                    {
                        label: 'Barangay 168',
                        value: '19 cases'
                    },
                    {
                        label: 'Barangay 174',
                        value: '12 cases'
                    },
                    {
                        label: 'Total this month',
                        value: '104 cases'
                    }
                ]
            },
            {
                id: 'barangay',
                title: 'Barangay 172 has the <span class="text-amber-600 font-bold">highest patient volume</span>.',
                priority: 'Medium',
                priorityColor: 'amber',
                icon: 'users',
                detail: 'Barangay 172 – Patient Volume',
                subtitle: 'Highest volume this month',
                rows: [{
                        label: 'Health Center visits',
                        value: '268'
                    },
                    {
                        label: 'Immunization visits',
                        value: '94'
                    },
                    {
                        label: 'Sanitation requests',
                        value: '37'
                    },
                    {
                        label: 'Staff on duty',
                        value: '6'
                    }
                ]
            },
            {
                id: 'permits',
                title: 'Permit processing time improved by <span class="text-emerald-600 font-bold">21%</span>.',
                priority: 'Positive',
                priorityColor: 'emerald',
                icon: 'check',
                detail: 'Permit Processing – Efficiency Gain',
                subtitle: '21% faster vs last month',
                rows: [{
                        label: 'Avg. time (this month)',
                        value: '2.3 days'
                    },
                    {
                        label: 'Avg. time (last month)',
                        value: '2.9 days'
                    },
                    {
                        label: 'Permits processed',
                        value: '356'
                    },
                    {
                        label: 'Backlog reduced by',
                        value: '44 permits'
                    }
                ]
            },
            {
                id: 'vaccination',
                title: 'Recommend increasing vaccination staff next week based on trends.',
                priority: 'AI Suggestion',
                priorityColor: 'blue',
                icon: 'ai',
                detail: 'Vaccination Staffing Recommendation',
                subtitle: 'Predictive recommendation',
                rows: [{
                        label: 'Projected demand',
                        value: '2,150 doses'
                    },
                    {
                        label: 'Current staff capacity',
                        value: '1,680 doses'
                    },
                    {
                        label: 'Suggested additional staff',
                        value: '3 nurses'
                    },
                    {
                        label: 'Confidence level',
                        value: '58%'
                    }
                ]
            }
        ];

        const PredictiveData = [{
                title: 'Expected Disease Cases',
                value: '185',
                confidence: '92%',
                trend: '↑ 8.3% vs current month',
                color: 'indigo',
                icon: 'alert',
                detail: 'Disease Cases Forecast',
                subtitle: 'Next month projection',
                rows: [{
                        label: 'Expected cases',
                        value: '185'
                    },
                    {
                        label: 'Confidence interval',
                        value: '±5%'
                    },
                    {
                        label: 'Trend direction',
                        value: '↑ 8.3%'
                    },
                    {
                        label: 'Risk level',
                        value: 'Moderate'
                    }
                ],
                pieData: [{
                        label: 'Low Risk',
                        value: 25,
                        color: '#10b981'
                    },
                    {
                        label: 'Moderate Risk',
                        value: 55,
                        color: '#f59e0b'
                    },
                    {
                        label: 'High Risk',
                        value: 20,
                        color: '#ef4444'
                    }
                ]
            },
            {
                title: 'Estimated Permit Requests',
                value: '420',
                confidence: '89%',
                trend: '↑ 12.1% vs current month',
                color: 'blue',
                icon: 'document',
                detail: 'Permit Requests Forecast',
                subtitle: 'Next month projection',
                rows: [{
                        label: 'Estimated requests',
                        value: '420'
                    },
                    {
                        label: 'Confidence interval',
                        value: '±4%'
                    },
                    {
                        label: 'Trend direction',
                        value: '↑ 12.1%'
                    },
                    {
                        label: 'Staff required',
                        value: '8 officers'
                    }
                ],
                pieData: [{
                        label: 'Residential',
                        value: 60,
                        color: '#3b82f6'
                    },
                    {
                        label: 'Commercial',
                        value: 25,
                        color: '#8b5cf6'
                    },
                    {
                        label: 'Industrial',
                        value: 15,
                        color: '#f59e0b'
                    }
                ]
            },
            {
                title: 'Vaccination Demand',
                value: '2,150',
                unit: 'doses',
                confidence: '58%',
                trend: '↑ 15.7% vs current month',
                color: 'amber',
                icon: 'health',
                detail: 'Vaccination Demand Forecast',
                subtitle: 'Next month projection',
                rows: [{
                        label: 'Doses needed',
                        value: '2,150'
                    },
                    {
                        label: 'Confidence interval',
                        value: '±8%'
                    },
                    {
                        label: 'Trend direction',
                        value: '↑ 15.7%'
                    },
                    {
                        label: 'Stock status',
                        value: 'Sufficient'
                    }
                ],
                pieData: [{
                        label: 'Children',
                        value: 40,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Adults',
                        value: 35,
                        color: '#10b981'
                    },
                    {
                        label: 'Seniors',
                        value: 25,
                        color: '#8b5cf6'
                    }
                ]
            }
        ];

        const ModuleData = [{
                label: 'Health Center Services',
                share: 32,
                color: '#3b82f6',
                trend: '▲ 3.2%',
                status: 'On track'
            },
            {
                label: 'Sanitation Permits',
                share: 24,
                color: '#10b981',
                trend: '▼ 1.1%',
                status: 'On track'
            },
            {
                label: 'Immunization & Nutrition',
                share: 20,
                color: '#f59e0b',
                trend: '▲ 2.4%',
                status: 'On track'
            },
            {
                label: 'Health Surveillance',
                share: 16,
                color: '#f43f5e',
                trend: '▼ 0.8%',
                status: 'On track'
            },
            {
                label: 'Wastewater Services',
                share: 8,
                color: '#a855f7',
                trend: '▲ 4.2%',
                status: 'Needs attention'
            }
        ];

        const MetricsData = [{
                label: 'Permit Processing',
                value: 2.3,
                unit: 'Days',
                change: '↓ 21%',
                changeColor: 'emerald',
                progress: 78,
                glow: 'glow-green',
                watermark: 'fa-file-signature',
                details: [{
                        label: 'Current Average',
                        value: '2.3 Days'
                    },
                    {
                        label: 'Previous Month',
                        value: '2.9 Days'
                    },
                    {
                        label: 'Improvement',
                        value: '21%'
                    },
                    {
                        label: 'Target',
                        value: '< 2.5 Days'
                    },
                    {
                        label: 'Backlog',
                        value: '12 permits'
                    }
                ],
                pieData: [{
                        label: 'Completed',
                        value: 78,
                        color: '#10b981'
                    },
                    {
                        label: 'In Progress',
                        value: 12,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Pending',
                        value: 10,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'AI Report Accuracy',
                value: 96,
                unit: '%',
                change: '↑ 5%',
                changeColor: 'blue',
                progress: 96,
                glow: 'glow-blue',
                watermark: 'fa-robot',
                details: [{
                        label: 'Current Accuracy',
                        value: '96%'
                    },
                    {
                        label: 'Previous Month',
                        value: '91%'
                    },
                    {
                        label: 'Improvement',
                        value: '+5%'
                    },
                    {
                        label: 'Target',
                        value: '> 95%'
                    },
                    {
                        label: 'Total Reports',
                        value: '1,247'
                    }
                ],
                pieData: [{
                        label: 'Accurate',
                        value: 96,
                        color: '#3b82f6'
                    },
                    {
                        label: 'Needs Review',
                        value: 3,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Inaccurate',
                        value: 1,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'System Response',
                value: 0.4,
                unit: 'sec',
                change: '↓ 0.2s',
                changeColor: 'teal',
                progress: 92,
                glow: 'glow-teal',
                watermark: 'fa-server',
                details: [{
                        label: 'Current Avg.',
                        value: '0.4 sec'
                    },
                    {
                        label: 'Previous Month',
                        value: '0.6 sec'
                    },
                    {
                        label: 'Improvement',
                        value: '-33%'
                    },
                    {
                        label: 'Target',
                        value: '< 0.5 sec'
                    },
                    {
                        label: 'Peak Load',
                        value: '1.2 sec'
                    }
                ],
                pieData: [{
                        label: 'Under 0.5s',
                        value: 92,
                        color: '#14b8a6'
                    },
                    {
                        label: '0.5-1.0s',
                        value: 6,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Above 1.0s',
                        value: 2,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'Monthly Active Users',
                value: 1248,
                unit: '',
                change: '↑ 14%',
                changeColor: 'purple',
                progress: 85,
                glow: 'glow-purple',
                watermark: 'fa-users',
                details: [{
                        label: 'Current Users',
                        value: '1,248'
                    },
                    {
                        label: 'Previous Month',
                        value: '1,094'
                    },
                    {
                        label: 'Growth',
                        value: '+14%'
                    },
                    {
                        label: 'Target',
                        value: '> 1,200'
                    },
                    {
                        label: 'New Users',
                        value: '156'
                    }
                ],
                pieData: [{
                        label: 'Active',
                        value: 85,
                        color: '#8b5cf6'
                    },
                    {
                        label: 'Semi-Active',
                        value: 10,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Inactive',
                        value: 5,
                        color: '#ef4444'
                    }
                ]
            },
            {
                label: 'User Satisfaction',
                value: 94,
                unit: '%',
                change: '↑ 3%',
                changeColor: 'amber',
                progress: 94,
                glow: 'glow-amber',
                watermark: 'fa-face-smile',
                details: [{
                        label: 'Current Satisfaction',
                        value: '94%'
                    },
                    {
                        label: 'Previous Month',
                        value: '91%'
                    },
                    {
                        label: 'Improvement',
                        value: '+3%'
                    },
                    {
                        label: 'Target',
                        value: '> 92%'
                    },
                    {
                        label: 'Survey Responses',
                        value: '892'
                    }
                ],
                pieData: [{
                        label: 'Satisfied',
                        value: 94,
                        color: '#f59e0b'
                    },
                    {
                        label: 'Neutral',
                        value: 4,
                        color: '#a1a1aa'
                    },
                    {
                        label: 'Unsatisfied',
                        value: 2,
                        color: '#ef4444'
                    }
                ]
            }
        ];

        const StaffData = [{
                name: 'Juan Dela Cruz',
                score: 94,
                cases: 112,
                response: 4.2
            },
            {
                name: 'Ana Reyes',
                score: 91,
                cases: 98,
                response: 4.8
            },
            {
                name: 'Carlos Tan',
                score: 88,
                cases: 85,
                response: 5.1
            },
            {
                name: 'Elena Santos',
                score: 85,
                cases: 76,
                response: 5.6
            },
            {
                name: 'Roberto Silva',
                score: 82,
                cases: 68,
                response: 6.2
            },
            {
                name: 'Jose Mendoza',
                score: 78,
                cases: 59,
                response: 6.8
            }
        ];

        // =====================================================================
        // AI GLOW CURSOR TRACKING
        // =====================================================================
        const aiPanel = document.getElementById('aiInsightPanel');
        aiPanel.addEventListener('mousemove', (e) => {
            const rect = aiPanel.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;
            aiPanel.style.setProperty('--mouse-x', `${x}px`);
            aiPanel.style.setProperty('--mouse-y', `${y}px`);
        });

        // =====================================================================
        // TOOLTIP SYSTEM
        // =====================================================================
        const tooltip = document.getElementById('hoverTooltip');
        let tooltipTimeout = null;
        let tooltipHideTimeout = null;
        let isTooltipVisible = false;
        let currentTarget = null;

        function showTooltip(event, title, content, isPieChart = false) {
            if (!tooltip) return;

            if (tooltipHideTimeout) {
                clearTimeout(tooltipHideTimeout);
                tooltipHideTimeout = null;
            }

            const rect = event.currentTarget.getBoundingClientRect();
            currentTarget = event.currentTarget;

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

            if (isPieChart && content.pieData) {
                contentHtml = `
                <div class="mini-chart" id="miniPieChart"></div>
                <div class="tooltip-pie-legend">
                    ${content.pieData.map(d => `<span><span class="dot" style="background: ${d.color};"></span>${d.label} (${d.value}%)</span>`).join('')}
                </div>
                <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid #f4f4f5;">
                    ${content.details.map(d => `<div class="tooltip-row"><span class="label">${d.label}</span><span class="value">${d.value}</span></div>`).join('')}
                </div>
            `;
            } else {
                contentHtml = content.map(d => `<div class="tooltip-row"><span class="label">${d.label}</span><span class="value">${d.value}</span></div>`).join('');
            }

            const contentEl = document.getElementById('tooltipContent');
            if (contentEl) contentEl.innerHTML = contentHtml;

            tooltip.classList.add('active');
            isTooltipVisible = true;

            if (isPieChart && content.pieData) {
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
                if (tooltip) {
                    tooltip.classList.remove('active');
                    isTooltipVisible = false;
                    currentTarget = null;
                }
                if (window._miniChart) {
                    window._miniChart.destroy();
                    window._miniChart = null;
                }
                tooltipHideTimeout = null;
            }, 200);
        }

        function renderMiniPieChart(data) {
            const container = document.getElementById('miniPieChart');
            if (!container) return;
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
                    toolbar: {
                        show: false
                    },
                    animations: {
                        enabled: true,
                        speed: 500
                    }
                },
                colors: data.map(d => d.color),
                labels: data.map(d => d.label),
                legend: {
                    show: false
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: 2,
                    colors: ['#ffffff']
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '60%'
                        }
                    }
                },
                tooltip: {
                    enabled: true,
                    style: {
                        fontSize: '10px'
                    }
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
            if (event) {
                event.stopPropagation();
            }
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
        // DYNAMIC LOADING, EMPTY & ERROR STATE HANDLERS (ISO/IEC 25010 Usability & Reliability)
        // =====================================================================

        // 1. Render Loading State (Modular Shimmer Skeletons for Lazy Loading)
        function renderLoadingState(containerId, count = 4) {
            const container = document.getElementById(containerId);
            if (!container) return;

            let html = '';
            for (let i = 0; i < count; i++) {
                if (containerId === 'predictiveCards') {
                    html += `
                        <div class="rounded-xl border border-zinc-200/80 p-4 bg-white/90 animate-pulse shadow-xs">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-zinc-200/80 rounded-lg shrink-0"></div>
                                <div class="flex-1 space-y-2 min-w-0">
                                    <div class="w-24 h-3 bg-zinc-200/80 rounded"></div>
                                    <div class="w-16 h-5 bg-zinc-200/80 rounded"></div>
                                </div>
                                <div class="w-12 h-5 bg-zinc-200/80 rounded-full shrink-0"></div>
                            </div>
                            <div class="h-1.5 w-full bg-zinc-200/80 rounded-full mt-3"></div>
                            <div class="mt-2 flex justify-between items-center">
                                <div class="w-20 h-3 bg-zinc-200/80 rounded"></div>
                                <div class="w-16 h-3 bg-zinc-200/80 rounded"></div>
                            </div>
                        </div>
                    `;
                } else if (containerId === 'metricsGrid') {
                    html += `
                        <div class="rounded-2xl border border-slate-100 bg-white p-3 animate-pulse shadow-xs">
                            <div class="flex items-start justify-between gap-2">
                                <div class="space-y-1.5">
                                    <div class="w-20 h-3 bg-zinc-200/80 rounded"></div>
                                    <div class="w-14 h-6 bg-zinc-200/80 rounded mt-1"></div>
                                </div>
                                <div class="w-10 h-10 rounded-full bg-zinc-200/80 shrink-0"></div>
                            </div>
                            <div class="mt-3 pt-2 border-t border-slate-100 flex justify-between items-center">
                                <div class="w-12 h-3 bg-zinc-200/80 rounded"></div>
                                <div class="w-16 h-3 bg-zinc-200/80 rounded"></div>
                            </div>
                        </div>
                    `;
                } else {
                    html += `
                        <div class="rounded-xl border border-zinc-200/80 p-5 bg-white/90 animate-pulse shadow-sm">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-8 h-8 bg-zinc-200/80 rounded-lg"></div>
                                <div class="w-20 h-5 bg-zinc-200/80 rounded-full"></div>
                            </div>
                            <div class="space-y-2 mb-4">
                                <div class="w-full h-4 bg-zinc-200/80 rounded"></div>
                                <div class="w-3/4 h-4 bg-zinc-200/80 rounded"></div>
                            </div>
                            <div class="pt-3 border-t border-zinc-100 flex justify-between items-center">
                                <div class="w-16 h-3 bg-zinc-200/80 rounded"></div>
                                <div class="w-20 h-3 bg-zinc-200/80 rounded"></div>
                            </div>
                        </div>
                    `;
                }
            }
            container.innerHTML = html;
        }

        // 2. Render Empty State
        function renderEmptyState(containerId, message = "No AI insights available for this date range.") {
            const container = document.getElementById(containerId);
            if (!container) return;

            container.innerHTML = `
                <div class="col-span-full py-10 px-6 text-center bg-white/70 backdrop-blur-md rounded-2xl border border-dashed border-zinc-300 shadow-xs">
                    <div class="w-12 h-12 rounded-2xl bg-zinc-100 text-zinc-400 mx-auto mb-3 flex items-center justify-center">
                        <i class="fas fa-inbox text-xl"></i>
                    </div>
                    <p class="text-sm font-bold text-zinc-700">${message}</p>
                    <p class="text-xs text-zinc-400 mt-1 max-w-md mx-auto">Try selecting a different date range or sync your database records from the operational modules.</p>
                    <button onclick="fetchLiveAnalytics(true, false)" class="mt-4 px-4 py-2 bg-c3 hover:bg-c3d text-white rounded-xl text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fas fa-rotate text-xs"></i> Refresh Analytics
                    </button>
                </div>
            `;
        }

        // 3. Render Error State (Reliability & Fault Tolerance)
        function renderErrorState(containerId, errorMessage = "Error connecting to analytics service. Please check your connection.") {
            const container = document.getElementById(containerId);
            if (!container) return;

            container.innerHTML = `
                <div class="col-span-full py-10 px-6 text-center bg-rose-50/80 backdrop-blur-md rounded-2xl border border-rose-200 shadow-xs">
                    <div class="w-12 h-12 rounded-2xl bg-rose-100 text-rose-600 mx-auto mb-3 flex items-center justify-center">
                        <i class="fas fa-triangle-exclamation text-xl"></i>
                    </div>
                    <h4 class="text-sm font-bold text-rose-800">${errorMessage}</h4>
                    <p class="text-xs text-rose-600/80 mt-1 max-w-md mx-auto font-medium">The system encountered a temporary connection issue. Retrying will attempt to re-establish API sync.</p>
                    <button onclick="fetchLiveAnalytics(true, false)" class="mt-4 px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-bold transition-all shadow-sm cursor-pointer inline-flex items-center gap-1.5">
                        <i class="fas fa-arrows-rotate text-xs"></i> Retry Connection
                    </button>
                </div>
            `;
        }

        // =====================================================================
        // RENDER FUNCTIONS
        // =====================================================================
        function renderInsights(liveInsights) {
            const grid = document.getElementById('insightsGrid') || document.getElementById('aiInsightsGrid');
            if (!grid) return;

            if (!liveInsights || !Array.isArray(liveInsights) || liveInsights.length === 0) {
                renderEmptyState('insightsGrid', "No AI analytical insights available for this date range.");
                return;
            }

            const items = liveInsights;

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

            grid.innerHTML = items.map(function(insight, idx) {
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
        renderInsights(); // Start Insights render

        // =====================================================================
        // PREDICTIVE WITH PIE CHART HOVER
        // =====================================================================
        function renderPredictive(apiPredictiveData) {
            const container = document.getElementById('predictiveCards');
            if (!container) return;

            const items = (apiPredictiveData && Array.isArray(apiPredictiveData.cards) && apiPredictiveData.cards.length > 0)
                ? apiPredictiveData.cards
                : PredictiveData;

            if (!items || items.length === 0) {
                renderEmptyState('predictiveCards', "No predictive forecast data available.");
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
                const indicatorColor = parseInt(item.confidence) >= 80 ? 'emerald' : 'amber';
                const badgeBg = badgeMap[item.color] || 'bg-zinc-50 border-zinc-200 text-zinc-700';

                return '<div class="predictive-card rounded-xl border border-zinc-200/80 p-4 transition-all duration-200 cursor-pointer" ' +
                    'onmouseenter="showPredictiveTooltip(event, \'' + item.detail + '\', ' + JSON.stringify(item.rows).replace(/"/g, '&quot;') + ', ' + JSON.stringify(item.pieData).replace(/"/g, '&quot;') + ')" ' +
                    'onmouseleave="hidePredictiveTooltip()">' +
                    '<div class="flex items-center gap-3">' +
                    '<div class="p-2 ' + badgeBg + ' border rounded-lg shrink-0">' +
                    '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + (iconMap[item.icon] || iconMap['alert']) + '</svg>' +
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
                    '<span>' + item.trend + '</span>' +
                    '<span class="text-blue-500 font-semibold uppercase tracking-wider no-print">Hover for details</span>' +
                    '</p>' +
                    '</div>';
            }).join('');
        }
        renderPredictive(); // Start Predictive render

        function showLoadingState() {
            if (document.getElementById('headerTimestamp')) {
                document.getElementById('headerTimestamp').innerHTML = '<span class="inline-block animate-spin mr-1">⚡</span> Syncing...';
            }
            ['#trendChart', '#predictiveLineChart', '#insightsGrid', '#modulesChart'].forEach(function(sel) {
                var el = document.querySelector(sel);
                if (el) {
                    el.style.pointerEvents = 'none';
                    el.style.transition = 'all 0.2s ease';
                    el.style.opacity = '0.3';
                    el.style.filter = 'blur(1px)';
                }
            });
        }

        function hideLoadingState() {
            ['#trendChart', '#predictiveLineChart', '#insightsGrid', '#modulesChart'].forEach(function(sel) {
                var el = document.querySelector(sel);
                if (el) {
                    el.style.pointerEvents = 'auto';
                    el.style.opacity = '1';
                    el.style.filter = 'none';
                }
            });
        }

        var isAutoRefreshEnabled = true;

        async function fetchLiveAnalytics(forceRefresh = false, isSilent = false) {
            var range = document.getElementById('dateRangeSelect').value;
            var filter = document.getElementById('trendFilter').value;
            var yoy = document.getElementById('yoyToggle').checked;

            if (!isSilent) {
                showLoadingState();
                renderLoadingState('insightsGrid', 4);
                renderLoadingState('predictiveCards', 3);
            }

            try {
                var url = '../api/analytics.php?range=' + range + '&filter=' + filter + '&yoy=' + yoy + (forceRefresh ? '&refresh=1' : '');
                var res = await fetch(url);
                
                if (!res.ok) {
                    throw new Error('HTTP status ' + res.status);
                }

                var data = await res.json();
                if (data && data.success) {
                    var now = new Date();
                    var syncText = 'Live (' + now.toLocaleTimeString() + ')';
                    if (document.getElementById('headerTimestamp')) document.getElementById('headerTimestamp').textContent = syncText;
                    if (document.getElementById('footerTimestamp')) document.getElementById('footerTimestamp').textContent = now.toLocaleString();

                    // Update Trend Chart
                    if (data.trend && typeof trendChart !== 'undefined') {
                        trendChart.updateOptions({
                            series: data.trend.series,
                            colors: data.trend.colors,
                            xaxis: { categories: data.trend.categories }
                        });
                        renderLegend(data.trend.legend);
                    }
                    // Update Predictive Chart & Badges
                    if (data.predictive && typeof predictiveChart !== 'undefined') {
                        predictiveChart.updateOptions({
                            series: data.predictive.series,
                            xaxis: { categories: data.predictive.categories }
                        });
                        renderPredictive(data.predictive);
                    } else {
                        renderPredictive();
                    }
                    // Update AI Insights Grid
                    if (data.insights && Array.isArray(data.insights) && data.insights.length > 0) {
                        renderInsights(data.insights);
                    } else {
                        renderEmptyState('insightsGrid', "No AI analytical insights generated for the selected period.");
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
                } else {
                    renderEmptyState('insightsGrid', "No AI analytical insights generated for the selected period.");
                }
            } catch (err) {
                console.error('API Fetch Error:', err);
                renderErrorState('insightsGrid', "Error connecting to analytics service. Please check your connection.");
            } finally {
                if (!isSilent) {
                    hideLoadingState();
                }
            }
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

        function renderModuleLegend() {
            const container = document.getElementById('moduleLegend');
            if (!container) return;
            container.innerHTML = ModuleData.map(function(m) {
                return '<div class="module-item flex items-center justify-between" ' +
                    'data-label="' + m.label + '" data-share="' + m.share + '%" data-trend="' + m.trend + '" data-status="' + m.status + '" data-color="' + m.color + '">' +
                    '<span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full" style="background:' + m.color + '"></span> ' + m.label + '</span>' +
                    '<span class="font-bold text-zinc-800">' + m.share + '%</span>' +
                    '</div>';
            }).join('');
            bindModuleLegendEvents();
        }
        renderModuleLegend(); // Start module legend render

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

            const list = (liveMetrics && Array.isArray(liveMetrics) && liveMetrics.length > 0) ? liveMetrics : MetricsData;

            // Map the colors to Tailwind text- classes for the icons
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

            // Calculating stroke-dashoffset for SVG Ring
            // 100 - progress = offset
            grid.innerHTML = list.map(function(m) {
                const isPositive = m.change.includes('↑');
                const changeClass = isPositive ? 'positive' : 'negative';
                const changeIcon = isPositive ? '↑' : '↓';
                const iconColors = iconColorMap[m.changeColor] || 'text-zinc-600 bg-zinc-50 border-zinc-200';
                const ringColor = ringColorMap[m.changeColor] || '#3b82f6';
                const ringOffset = 100 - m.progress;

                return '<div class="kpi-card rounded-2xl border border-slate-100 bg-white p-3 cursor-pointer group ' + m.glow + '" ' +
                    'onmouseenter="showMetricTooltip(event, \'' + m.label + '\', ' + JSON.stringify(m.details).replace(/"/g, '&quot;') + ', ' + JSON.stringify(m.pieData).replace(/"/g, '&quot;') + ')" ' +
                    'onmouseleave="hideMetricTooltip()">' +
                    '<div class="kpi-shine no-print"></div>' +
                    '<div class="absolute left-0 top-0 h-full w-[3px] bg-gradient-to-b from-' + m.changeColor + '-400 to-' + m.changeColor + '-600"></div>' +
                    '<i class="fas ' + m.watermark + ' kpi-watermark absolute -bottom-3 -right-2 text-[58px] text-' + m.changeColor + '-500/10 rotate-[-8deg] pointer-events-none no-print"></i>' +
                    '<div class="relative p-1">' +
                    '<div class="flex items-start justify-between gap-2">' +
                    '<div>' +
                    '<p class="text-[8px] font-bold uppercase tracking-wider text-' + m.changeColor + '-600">' + m.label + '</p>' +
                    '<p class="kpi-value text-xl font-black mt-1 leading-none" data-target="' + m.value + '">0' + (m.unit ? '<span class="text-xs font-semibold text-slate-400"> ' + m.unit + '</span>' : '') + '</p>' +
                    '</div>' +
                    '<svg viewBox="0 0 36 36" class="kpi-ring w-10 h-10 flex-shrink-0">' +
                    '<circle cx="18" cy="18" r="15.5" fill="none" stroke="#e2e8f0" stroke-width="3"/>' +
                    '<circle cx="18" cy="18" r="15.5" fill="none" stroke="' + ringColor + '" stroke-width="3" stroke-linecap="round" pathLength="100" class="kpi-ring-progress" style="--offset:' + ringOffset + '" transform="rotate(-90 18 18)"/>' +
                    '<text x="18" y="20.5" text-anchor="middle" font-size="8" font-weight="700" fill="' + ringColor + '">' + m.progress + '%</text>' +
                    '</svg>' +
                    '</div>' +
                    '<div class="mt-2 pt-2 border-t border-slate-100 flex items-center justify-between gap-2">' +
                    '<span class="kpi-change ' + changeClass + '">' + changeIcon + ' ' + m.change.replace(/[↑↓]\s*/, '') + '</span>' +
                    '<span class="text-[8px] text-slate-400">vs last month</span>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }).join('');

            // Animate Counters
            document.querySelectorAll('.kpi-value').forEach(el => {
                const target = parseFloat(el.getAttribute('data-target'));
                const duration = 1500;
                const start = 0;
                const startTime = performance.now();

                function updateNumber(currentTime) {
                    const elapsed = currentTime - startTime;
                    const progress = Math.min(elapsed / duration, 1);
                    const current = Math.floor(progress * target);
                    // Preserve the unit span tag during count up
                    const unitHtml = el.innerHTML.includes('<span') ? el.innerHTML.substring(el.innerHTML.indexOf('<span')) : '';
                    el.innerHTML = current.toLocaleString() + unitHtml;
                    if (progress < 1) requestAnimationFrame(updateNumber);
                    else el.innerHTML = target.toLocaleString() + unitHtml;
                }
                requestAnimationFrame(updateNumber);
            });
        }
        renderMetrics(); // Start Metric render

        window.showMetricTooltip = function(event, title, details, pieData) {
            showTooltip(event, title, {
                details,
                pieData
            }, true);
        };
        window.hideMetricTooltip = function() {
            hideTooltip();
        };

        // Staff tooltip
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

        // =====================================================================
        // TREND CHART
        // =====================================================================
        var trendDatasets = {
            disease: {
                subtitle: 'Disease Cases Trend',
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                colors: ['#ef4444', '#f59e0b', '#10b981', '#a855f7'],
                series: [{
                        name: 'Dengue',
                        data: [2, 3, 5, 4, 7, 8]
                    },
                    {
                        name: 'Influenza',
                        data: [1, 2, 3, 2, 4, 5]
                    },
                    {
                        name: 'Food Poisoning',
                        data: [0, 1, 2, 1, 3, 2]
                    },
                    {
                        name: 'Leptospirosis',
                        data: [0, 0, 1, 1, 2, 1]
                    }
                ],
                legend: [{
                        label: 'Dengue',
                        color: 'bg-red-500'
                    },
                    {
                        label: 'Influenza',
                        color: 'bg-amber-500'
                    },
                    {
                        label: 'Food Poisoning',
                        color: 'bg-emerald-500'
                    },
                    {
                        label: 'Leptospirosis',
                        color: 'bg-purple-500'
                    }
                ]
            },
            service: {
                subtitle: 'Service Requests Trend',
                categories: ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                colors: ['#3b82f6', '#10b981', '#f59e0b'],
                series: [{
                        name: 'Patients',
                        data: [4, 6, 8, 7, 9, 12]
                    },
                    {
                        name: 'Vaccination',
                        data: [3, 5, 7, 6, 8, 10]
                    },
                    {
                        name: 'Requests',
                        data: [2, 4, 6, 5, 7, 9]
                    }
                ],
                legend: [{
                        label: 'Patients',
                        color: 'bg-blue-500'
                    },
                    {
                        label: 'Vaccination',
                        color: 'bg-emerald-500'
                    },
                    {
                        label: 'Requests',
                        color: 'bg-amber-500'
                    }
                ]
            },
            combined: {
                subtitle: 'Combined View',
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                colors: ['#ef4444', '#3b82f6', '#10b981'],
                series: [{
                        name: 'Disease Cases',
                        data: [3, 5, 8, 7, 11, 13]
                    },
                    {
                        name: 'Service Requests',
                        data: [9, 15, 21, 18, 24, 31]
                    },
                    {
                        name: 'Permits Issued',
                        data: [4, 6, 8, 7, 9, 12]
                    }
                ],
                legend: [{
                        label: 'Disease Cases',
                        color: 'bg-red-500'
                    },
                    {
                        label: 'Service Requests',
                        color: 'bg-blue-500'
                    },
                    {
                        label: 'Permits Issued',
                        color: 'bg-emerald-500'
                    }
                ]
            }
        };

        var rangeLabels = {
            'today': 'Today',
            '7d': 'Last 7 Days',
            '30d': 'Last 30 Days',
            '90d': 'Last 90 Days',
            '6m': 'Last 6 Months',
            '12m': 'Last 12 Months',
            'custom': 'Custom Range'
        };

        function interpolateSeries(values, targetLen) {
            var result = [];
            for (var i = 0; i < targetLen; i++) {
                var t = targetLen === 1 ? 0 : i / (targetLen - 1) * (values.length - 1);
                var i0 = Math.floor(t),
                    i1 = Math.min(i0 + 1, values.length - 1);
                var frac = t - i0;
                var v = values[i0] * (1 - frac) + values[i1] * frac;
                var noise = Math.round(Math.sin(i * 12.9898 + values[0]) * 4) / 10;
                result.push(Math.max(0, Math.round((v + noise) * 10) / 10));
            }
            return result;
        }

        function rangeLength(rangeKey) {
            if (rangeKey === 'today') return 1;
            if (rangeKey === '7d') return 7;
            if (rangeKey === '30d') return 30;
            if (rangeKey === '90d') return 13;
            if (rangeKey === '12m') return 12;
            if (rangeKey === 'custom') {
                var from = document.getElementById('dateFrom').value;
                var to = document.getElementById('dateTo').value;
                if (from && to) {
                    var days = Math.max(2, Math.round((new Date(to) - new Date(from)) / 86400000) + 1);
                    return Math.min(days, 60);
                }
                return 14;
            }
            return 6;
        }

        function makeCategories(rangeKey, len) {
            if (rangeKey === 'today') return ['Today'];
            if (rangeKey === '7d') return Array.from({
                length: len
            }, function(_, i) {
                var d = new Date();
                d.setDate(d.getDate() - (len - 1 - i));
                return d.toLocaleDateString('en-US', {
                    month: 'short',
                    day: 'numeric'
                });
            });
            if (rangeKey === '12m') return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            if (rangeKey === '90d') return Array.from({
                length: len
            }, function(_, i) {
                return 'Wk ' + (i + 1);
            });
            return Array.from({
                length: len
            }, function(_, i) {
                return 'Day ' + (i + 1);
            });
        }

        function buildTrendSeries(typeKey, rangeKey, yoy) {
            var base = trendDatasets[typeKey] || trendDatasets.disease;
            var len = rangeLength(rangeKey);
            var categories = makeCategories(rangeKey, len);
            var series = base.series.map(function(s) {
                return {
                    name: s.name,
                    data: interpolateSeries(s.data, len)
                };
            });
            var colors = base.colors.slice();
            var legend = base.legend.slice();
            if (yoy && series.length > 0) {
                var primary = series[0];
                var prevYear = primary.data.map(function(v, i) {
                    return Math.max(0, Math.round((v * 0.82 + Math.sin(i * 7.13) * 0.6) * 10) / 10);
                });
                series.push({
                    name: primary.name + ' (YoY)',
                    data: prevYear
                });
                colors.push('#a1a1aa');
                legend.push({
                    label: primary.name + ' (YoY)',
                    color: 'bg-zinc-400'
                });
            }
            return {
                categories: categories,
                series: series,
                colors: colors,
                subtitle: base.subtitle,
                legend: legend,
                yoy: yoy
            };
        }

        var trendOptions = {
            series: [],
            chart: {
                type: 'line',
                height: 224,
                toolbar: {
                    show: false
                },
                background: 'transparent',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            stroke: {
                curve: 'smooth',
                width: 3,
                lineCap: 'round'
            },
            grid: {
                borderColor: '#f4f4f5',
                strokeDashArray: 3,
                padding: {
                    top: 0,
                    right: 0,
                    bottom: 0,
                    left: 10
                }
            },
            xaxis: {
                categories: [],
                labels: {
                    style: {
                        colors: '#a1a1aa',
                        fontSize: '10px',
                        fontWeight: '500'
                    }
                },
                axisBorder: {
                    show: false
                },
                axisTicks: {
                    show: false
                }
            },
            yaxis: {
                labels: {
                    style: {
                        colors: '#a1a1aa',
                        fontSize: '10px',
                        fontWeight: '500'
                    }
                },
                min: 0
            },
            legend: {
                show: false
            },
            tooltip: {
                theme: 'light',
                style: {
                    fontSize: '11px'
                },
                marker: {
                    show: true
                }
            },
            markers: {
                size: 4,
                hover: {
                    size: 6,
                    sizeOffset: 3
                }
            }
        };
        var trendChart = new ApexCharts(document.querySelector("#trendChart"), trendOptions);
        trendChart.render();

        var predictiveOptions = {
            series: [
                { name: 'Expected Cases', data: [145, 160, 167, 175, 177, 191, 198] },
                { name: 'Permit Requests', data: [310, 338, 353, 372, 388, 419, 435] },
                { name: 'Vaccine Demand', data: [180, 195, 210, 225, 240, 260, 273] }
            ],
            chart: {
                type: 'line',
                height: 180,
                toolbar: { show: false },
                background: 'transparent',
                animations: { enabled: true, easing: 'easeinout', speed: 800 }
            },
            colors: ['#ef4444', '#3b82f6', '#10b981'],
            stroke: {
                curve: 'smooth',
                width: 3,
                dashArray: [0, 0, 0]
            },
            xaxis: {
                categories: ['Nov', 'Dec', 'Jan', 'Feb', 'Mar', 'Apr', 'May (AI Forecast)'],
                labels: { style: { fontSize: '10px', colors: '#a1a1aa' } }
            },
            yaxis: { show: false },
            grid: { borderColor: '#f4f4f5' },
            legend: { show: false },
            tooltip: { theme: 'light', style: { fontSize: '11px' } },
            markers: { size: 3, hover: { size: 5 } }
        };
        var predictiveChart = new ApexCharts(document.querySelector("#predictiveLineChart"), predictiveOptions);
        predictiveChart.render();

        function renderLegend(items) {
            const legendEl = document.getElementById('trendLegend');
            if (!legendEl) return;
            legendEl.innerHTML = items.map(function(item) {
                return '<span class="flex items-center gap-1.5"><span class="inline-block h-2 w-2 rounded-full ' + item.color + '"></span> ' + item.label + '</span>';
            }).join('');
        }

        function updateTrendChart() {
            var typeKey = document.getElementById('trendFilter').value;
            var rangeKey = document.getElementById('dateRangeSelect').value;
            var yoy = document.getElementById('yoyToggle').checked;
            var built = buildTrendSeries(typeKey, rangeKey, yoy);
            document.getElementById('trendSubtitle').textContent = built.subtitle + ' · ' + rangeLabels[rangeKey];
            var dashArray = built.series.map(function(s, i) {
                return (yoy && i === built.series.length - 1) ? 6 : 0;
            });
            trendChart.updateOptions({
                series: built.series,
                colors: built.colors,
                xaxis: {
                    categories: built.categories
                },
                stroke: {
                    curve: 'smooth',
                    width: 3,
                    dashArray: dashArray,
                    lineCap: 'round'
                }
            });
            renderLegend(built.legend);

            var now = new Date();
            if (document.getElementById('headerTimestamp')) document.getElementById('headerTimestamp').textContent = now.toLocaleTimeString();
            if (document.getElementById('footerTimestamp')) document.getElementById('footerTimestamp').textContent = now.toLocaleString();
        }
        updateTrendChart(); // Initialize Trend Chart

        var moduleMode = 'current'; // 'current' or 'projected'
        var moduleRawData = [];

        function renderModulesView(data) {
            if (data && Array.isArray(data)) moduleRawData = data;
            var legendEl = document.getElementById('moduleLegend');
            if (!legendEl) return;

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

            if (!moduleRawData || moduleRawData.length === 0) return;

            if (typeof modulesChart !== 'undefined') {
                modulesChart.updateOptions({
                    series: moduleRawData.map(function(m) { return isProjected ? m.projected_share : m.share; })
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
                    '<span class="text-[10px] text-zinc-400 font-medium">(' + (m.delta || '↑ 2.1pts') + ')</span>' +
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

        // Initialize live analytics immediately on page load
        fetchLiveAnalytics(false, false);

        // Supabase Realtime WebSocket Connection (Instant Push on DB Insert/Update/Delete)
        var SUPABASE_URL = "mock";
        var SUPABASE_ANON_KEY = "mock";

        if (SUPABASE_URL && SUPABASE_ANON_KEY && typeof supabase !== 'undefined') {
            try {
                var supabaseClient = supabase.createClient(SUPABASE_URL, SUPABASE_ANON_KEY);
                supabaseClient
                    .channel('realtime-ai-analytics')
                    .on('postgres_changes', { event: '*', schema: 'public', table: 'surveillance_cases' }, function(payload) {
                        console.log('⚡ Supabase Realtime Push [surveillance_cases]:', payload);
                        fetchLiveAnalytics(true, true);
                    })
                    .on('postgres_changes', { event: '*', schema: 'public', table: 'patients' }, function(payload) {
                        console.log('⚡ Supabase Realtime Push [patients]:', payload);
                        fetchLiveAnalytics(true, true);
                    })
                    .on('postgres_changes', { event: '*', schema: 'public', table: 'permits' }, function(payload) {
                        console.log('⚡ Supabase Realtime Push [permits]:', payload);
                        fetchLiveAnalytics(true, true);
                    })
                    .on('postgres_changes', { event: '*', schema: 'public', table: 'surveillance_alerts' }, function(payload) {
                        console.log('⚡ Supabase Realtime Push [surveillance_alerts]:', payload);
                        fetchLiveAnalytics(true, true);
                    })
                    .subscribe();
            } catch (err) {
                console.log('Supabase Realtime subscription fallback:', err);
            }
        }

        document.getElementById('trendFilter').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });
        document.getElementById('yoyToggle').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });
        document.getElementById('dateRangeSelect').addEventListener('change', function(e) {
            document.getElementById('customDateWrap').classList.toggle('hidden', e.target.value !== 'custom');
            document.getElementById('customDateWrap').classList.toggle('flex', e.target.value === 'custom');
            if (e.target.value !== 'custom') { updateTrendChart(); fetchLiveAnalytics(); }
        });
        document.getElementById('dateFrom').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });
        document.getElementById('dateTo').addEventListener('change', function() { updateTrendChart(); fetchLiveAnalytics(); });

        // =====================================================================
        // OPERATIONAL MODULES PIE CHART
        // =====================================================================
        var modulesOptions = {
            series: ModuleData.map(function(m) {
                return m.share;
            }),
            chart: {
                type: 'pie',
                height: 224,
                toolbar: {
                    show: false
                },
                background: 'transparent',
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                },
                events: {
                    dataPointMouseEnter: function(event, chartContext, config) {
                        const m = ModuleData[config.dataPointIndex];
                        if (m) showModuleTooltip(event, m.label, m.share + '%', m.trend, m.status, m.color);
                    },
                    dataPointMouseLeave: function() {
                        hideModuleTooltip();
                    }
                }
            },
            labels: ModuleData.map(function(m) {
                return m.label;
            }),
            colors: ModuleData.map(function(m) {
                return m.color;
            }),
            stroke: {
                width: 3,
                colors: ['#ffffff']
            },
            legend: {
                show: false
            },
            dataLabels: {
                enabled: true,
                style: {
                    fontSize: '11px',
                    fontWeight: 'bold'
                },
                dropShadow: {
                    enabled: false
                }
            },
            tooltip: {
                enabled: false
            }
        };
        var modulesChart = new ApexCharts(document.querySelector("#modulesChart"), modulesOptions);
        modulesChart.render();

        function bindModuleLegendEvents() {
            const items = document.querySelectorAll('#moduleLegend .module-item');
            items.forEach(function(item) {
                item.addEventListener('mouseenter', function(event) {
                    showModuleTooltip(event, item.getAttribute('data-label'), item.getAttribute('data-share'), item.getAttribute('data-trend'), item.getAttribute('data-status'), item.getAttribute('data-color'));
                });
                item.addEventListener('mouseleave', function() {
                    hideModuleTooltip();
                });
            });
        }

        var staffData = StaffData.map(function(s) {
            return {
                ...s
            };
        });

        window.updateStaffData = function(liveStaff) {
            if (liveStaff && liveStaff.length > 0) {
                staffData = liveStaff;
                if (typeof staffChart !== 'undefined' && staffChart) {
                    staffChart.updateOptions(buildStaffOptions(sortedStaff()));
                }
            }
        };

        function sortedStaff() {
            var dir = document.getElementById('staffSort').value;
            return staffData.slice().sort(function(a, b) {
                return dir === 'asc' ? a.score - b.score : b.score - a.score;
            });
        }

        function buildStaffOptions(data) {
            return {
                series: [{
                    name: 'Performance',
                    data: data.map(function(d) {
                        return d.score;
                    })
                }],
                chart: {
                    type: 'bar',
                    height: 320,
                    toolbar: {
                        show: false
                    },
                    background: 'transparent',
                    animations: {
                        enabled: true,
                        easing: 'easeinout',
                        speed: 800
                    },
                    events: {
                        dataPointMouseEnter: function(event, chartContext, config) {
                            clearTimeout(window.__staffHideTimer);
                            const d = data[config.dataPointIndex];
                            showStaffTooltip(event, d.name, d.score, d.cases, d.response);
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
                    bar: {
                        borderRadius: 6,
                        horizontal: true,
                        barHeight: '50%'
                    }
                },
                grid: {
                    borderColor: '#f4f4f5',
                    strokeDashArray: 3
                },
                xaxis: {
                    categories: data.map(function(d) {
                        return d.name;
                    }),
                    labels: {
                        style: {
                            colors: '#a1a1aa',
                            fontSize: '11px',
                            fontWeight: '500'
                        }
                    },
                    max: 100,
                    axisBorder: {
                        show: false
                    },
                    axisTicks: {
                        show: false
                    }
                },
                yaxis: {
                    labels: {
                        style: {
                            colors: '#27272a',
                            fontSize: '11px',
                            fontWeight: '600'
                        }
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: function(val) {
                        return val + '%';
                    },
                    style: {
                        fontSize: '10px',
                        fontWeight: 'bold',
                        colors: ['#4338ca']
                    },
                    offsetX: 20
                },
                tooltip: {
                    enabled: false
                },
                annotations: {
                    xaxis: [{
                        x: 80,
                        borderColor: '#f59e0b',
                        label: {
                            borderColor: '#f59e0b',
                            style: {
                                color: '#fff',
                                background: '#f59e0b',
                                fontSize: '9px',
                                fontWeight: '700'
                            },
                            text: 'Target: 80%'
                        }
                    }]
                }
            };
        }

        var staffChart = new ApexCharts(document.querySelector("#staffChart"), buildStaffOptions(sortedStaff()));
        staffChart.render();

        document.getElementById('staffSort').addEventListener('change', function() {
            staffChart.updateOptions(buildStaffOptions(sortedStaff()), true, true);
        });

        // =====================================================================
        // SERVICE / DISEASE TREND + SERVICE DISTRIBUTION
        // =====================================================================
        const serviceMonths = ['Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'];
        var serviceData = {
            appointments: [6, 8, 10, 11, 9, 7],
            emails: [4, 5, 6, 7, 5, 3],
            requests: [3, 4, 5, 6, 4, 2]
        };
        const serviceColors = {
            appointments: '#3b82f6',
            emails: '#10b981',
            requests: '#f59e0b'
        };
        var serviceVisibility = {
            appointments: true,
            emails: true,
            requests: true
        };

        function drawServiceChart() {
            const group = document.getElementById('serviceLineGroup');
            const dots = document.getElementById('serviceDotsGroup');
            if (!group || !dots) return;
            group.innerHTML = '';
            dots.innerHTML = '';

            const width = 500,
                height = 200;
            const margin = {
                top: 20,
                bottom: 30,
                left: 40,
                right: 20
            };
            const chartWidth = width - margin.left - margin.right;
            const chartHeight = height - margin.top - margin.bottom;
            const maxVal = 12;

            function getX(idx) {
                return margin.left + (idx / (serviceMonths.length - 1)) * chartWidth;
            }

            function getY(val) {
                return margin.top + chartHeight - (val / maxVal) * chartHeight;
            }

            Object.keys(serviceData).forEach(series => {
                if (!serviceVisibility[series]) return;
                const values = serviceData[series];
                const color = serviceColors[series];
                let pathD = '';
                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    pathD += (idx === 0 ? `M ${x} ${y}` : ` L ${x} ${y}`);
                });
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathD);
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-linejoin', 'round');
                group.appendChild(path);

                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', x);
                    circle.setAttribute('cy', y);
                    circle.setAttribute('r', '4');
                    circle.setAttribute('fill', color);
                    circle.setAttribute('stroke', '#fff');
                    circle.setAttribute('stroke-width', '1');
                    dots.appendChild(circle);
                });
            });
        }
        window.drawServiceChart = drawServiceChart;
        drawServiceChart();

        document.querySelectorAll('.service-legend').forEach(item => {
            item.addEventListener('click', function() {
                const series = this.dataset.series;
                serviceVisibility[series] = !serviceVisibility[series];
                const dot = this.querySelector('.inline-block');
                dot.style.opacity = serviceVisibility[series] ? '1' : '0.3';
                drawServiceChart();
            });
        });

        const diseaseMonths = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        var diseaseData = {
            dengue: [2, 4, 3, 5, 7, 4],
            influenza: [5, 7, 6, 8, 6, 3],
            foodPoisoning: [1, 2, 4, 3, 5, 2],
            leptospirosis: [0, 1, 2, 1, 3, 1]
        };
        const diseaseColors = {
            dengue: '#ef4444',
            influenza: '#eab308',
            foodPoisoning: '#22c55e',
            leptospirosis: '#a855f7'
        };
        var diseaseVisibility = {
            dengue: true,
            influenza: true,
            foodPoisoning: true,
            leptospirosis: true
        };

        function drawDiseaseChart() {
            const group = document.getElementById('diseaseLineGroup');
            const dots = document.getElementById('diseaseDotsGroup');
            if (!group || !dots) return;
            group.innerHTML = '';
            dots.innerHTML = '';

            const width = 500,
                height = 200;
            const margin = {
                top: 20,
                bottom: 30,
                left: 40,
                right: 20
            };
            const chartWidth = width - margin.left - margin.right;
            const chartHeight = height - margin.top - margin.bottom;
            const maxVal = 10;

            function getX(idx) {
                return margin.left + (idx / (diseaseMonths.length - 1)) * chartWidth;
            }

            function getY(val) {
                return margin.top + chartHeight - (val / maxVal) * chartHeight;
            }

            Object.keys(diseaseData).forEach(series => {
                if (!diseaseVisibility[series]) return;
                const values = diseaseData[series];
                const color = diseaseColors[series];
                let pathD = '';
                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    pathD += (idx === 0 ? `M ${x} ${y}` : ` L ${x} ${y}`);
                });
                const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', pathD);
                path.setAttribute('stroke', color);
                path.setAttribute('stroke-width', '2.5');
                path.setAttribute('fill', 'none');
                path.setAttribute('stroke-linecap', 'round');
                path.setAttribute('stroke-linejoin', 'round');
                group.appendChild(path);

                values.forEach((val, idx) => {
                    const x = getX(idx),
                        y = getY(val);
                    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                    circle.setAttribute('cx', x);
                    circle.setAttribute('cy', y);
                    circle.setAttribute('r', '4');
                    circle.setAttribute('fill', color);
                    circle.setAttribute('stroke', '#fff');
                    circle.setAttribute('stroke-width', '1');
                    dots.appendChild(circle);
                });
            });
        }
        window.drawDiseaseChart = drawDiseaseChart;
        drawDiseaseChart();

        document.querySelectorAll('.disease-legend').forEach(item => {
            item.addEventListener('click', function() {
                const series = this.dataset.series;
                diseaseVisibility[series] = !diseaseVisibility[series];
                const dot = this.querySelector('.inline-block');
                dot.style.opacity = diseaseVisibility[series] ? '1' : '0.3';
                drawDiseaseChart();
            });
        });

        var donutData = [{
                label: 'Health Center',
                percentage: 35.7,
                color: '#3b82f6'
            },
            {
                label: 'Sanitation',
                percentage: 42.9,
                color: '#10b981'
            },
            {
                label: 'Immunization',
                percentage: 21.4,
                color: '#f59e0b'
            },
            {
                label: 'Wastewater',
                percentage: 0,
                color: '#8b5cf6'
            }
        ];

        function drawDonut() {
            const container = document.getElementById('donutSegments');
            if (!container) return;
            container.innerHTML = '';

            const radius = 80;
            const circumference = 2 * Math.PI * radius;
            let cumulativeOffset = 0;

            donutData.forEach(seg => {
                const percent = seg.percentage;
                if (percent === 0) return;

                const dashLength = (percent / 100) * circumference;
                const dashArray = dashLength + ' ' + (circumference - dashLength);
                const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', '100');
                circle.setAttribute('cy', '100');
                circle.setAttribute('r', radius);
                circle.setAttribute('fill', 'none');
                circle.setAttribute('stroke', seg.color);
                circle.setAttribute('stroke-width', '30');
                circle.setAttribute('stroke-dasharray', dashArray);
                circle.setAttribute('stroke-dashoffset', -cumulativeOffset);
                circle.setAttribute('stroke-linecap', 'round');
                circle.style.cursor = 'pointer';

                circle.dataset.label = seg.label;
                circle.dataset.percentage = seg.percentage;

                const startAngleFrom3 = (-cumulativeOffset / circumference) * 360;
                const midAngleFrom3 = startAngleFrom3 + (dashLength / 2 / circumference) * 360;
                let midAngleFromTop = midAngleFrom3 + 90;
                midAngleFromTop = ((midAngleFromTop % 360) + 360) % 360;
                circle.dataset.midpointAngle = midAngleFromTop;

                container.appendChild(circle);
                cumulativeOffset += dashLength;
            });
        }
        window.drawDonut = drawDonut;
        drawDonut();
    });
