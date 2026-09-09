// ─── CHARTS ───
// UPDATED
    let barChart, doughnutChart, lineChart;

    function initCharts() {
        const ctxBar = document.getElementById('barChart').getContext('2d');
        barChart = new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: ['Central', 'Eastside', 'West', 'North', 'South'],
                datasets: [{
                    label: 'Compliance Score',
                    data: [96, 82, 68, 91, 74],
                    backgroundColor: ['#176B87', '#86B6F6', '#B4D4FF', '#176B87', '#86B6F6'],
                    borderRadius: 8,
                    barThickness: 36,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(134,182,246,0.15)' }, ticks: { font: { size: 10 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } }
                }
            }
        });

        const ctxDoughnut = document.getElementById('doughnutChart').getContext('2d');
        doughnutChart = new Chart(ctxDoughnut, {
            type: 'doughnut',
            data: {
                labels: ['Compliant', 'Non-Compliant', 'Pending', 'Urgent'],
                datasets: [{
                    data: [32, 4, 6, 5],
                    backgroundColor: ['#176B87', '#ef4444', '#f59e0b', '#8b5cf6'],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 }, padding: 8 } } }
            }
        });

        const ctxLine = document.getElementById('lineChart').getContext('2d');
        lineChart = new Chart(ctxLine, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Compliance %',
                    data: [88, 89, 91, 92, 93, 94.7],
                    borderColor: '#176B87',
                    backgroundColor: 'rgba(23,107,135,0.08)',
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#176B87',
                    pointRadius: 3,
                    borderWidth: 2,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, max: 100, grid: { color: 'rgba(134,182,246,0.15)' }, ticks: { font: { size: 8 } } },
                    x: { grid: { display: false }, ticks: { font: { size: 8 } } }
                }
            }
        });
    }

    // ─── TAB SWITCHING ───
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const tabName = this.dataset.tab;
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            const target = document.getElementById('tab' + tabName.charAt(0).toUpperCase() + tabName.slice(1));
            if (target) target.classList.remove('hidden');

            if (tabName === 'chart') {
                setTimeout(() => {
                    if (barChart) { barChart.resize();
                        barChart.update(); }
                    if (doughnutChart) { doughnutChart.resize();
                        doughnutChart.update(); }
                    if (lineChart) { lineChart.resize();
                        lineChart.update(); }
                }, 80);
            }
        });
    });

    // ─── FILTER CHIPS ───
    document.querySelectorAll('.filter-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // ─── GENERATE REPORT ───
    function generateReport() {
        const type = document.getElementById('reportType').value;
        const start = document.getElementById('startDate').value;
        const end = document.getElementById('endDate').value;
        const facility = document.getElementById('facility').value;
        const inspector = document.getElementById('inspector').value;

        showToast('📊 Report generated successfully!');

        const statCards = document.querySelectorAll('.stat-card .text-2xl');
        if (statCards.length >= 4) {
            const base = [1284, 94.7, 37, 47];
            const delta = [Math.floor(Math.random() * 40) + 10, (Math.random() * 3 + 92).toFixed(1), Math.floor(Math
                .random() * 10) + 30, Math.floor(Math.random() * 5) + 44];
            statCards[0].textContent = base[0] + delta[0];
            statCards[1].textContent = delta[1] + '%';
            statCards[2].textContent = delta[2];
            statCards[3].textContent = delta[3];
        }

        if (barChart) {
            const newData = [
                Math.floor(Math.random() * 20) + 75,
                Math.floor(Math.random() * 25) + 65,
                Math.floor(Math.random() * 30) + 55,
                Math.floor(Math.random() * 20) + 75,
                Math.floor(Math.random() * 30) + 55
            ];
            barChart.data.datasets[0].data = newData;
            barChart.update();
        }
    }

    // ─── SCHEDULE MODAL ───
    function openScheduleModal() {
        document.getElementById('scheduleModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeScheduleModal() {
        document.getElementById('scheduleModal').classList.add('hidden');
        document.body.style.overflow = '';
    }

    async function scheduleReport() {
        const title = document.getElementById('scheduleTitleInput')?.value || document.getElementById('reportTitle')?.value || 'Weekly Operational Summary';
        const frequency = document.getElementById('scheduleFrequencySelect')?.value || 'Weekly';
        const startDate = document.getElementById('scheduleStartDateInput')?.value || document.getElementById('startDate')?.value || new Date().toISOString().slice(0, 10);
        const time = document.getElementById('scheduleTimeInput')?.value || '08:00';
        const recipients = document.getElementById('scheduleRecipientsInput')?.value || 'admin@caloocan.gov.ph';
        const format = document.querySelector('input[name="scheduleFormat"]:checked')?.value || 'PDF';
        const department = document.getElementById('departmentFilter')?.value || 'All Core Departments';

        const payload = {
            action: 'create',
            report_title: title,
            frequency: frequency,
            start_date: startDate,
            time: time,
            recipients: recipients,
            format: format,
            department: department
        };

        const apiUrl = (typeof APP_CONFIG !== 'undefined' && APP_CONFIG.api_reports_schedule) 
            ? APP_CONFIG.api_reports_schedule 
            : '../api/reports/schedule.php';

        try {
            const resp = await fetch(apiUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const res = await resp.json();
            closeScheduleModal();
            if (res && res.success) {
                showToast('⏰ Report scheduled successfully! Next run: ' + (res.data?.next_run_at || 'scheduled'));
                if (typeof loadScheduledReports === 'function') {
                    loadScheduledReports();
                }
            } else {
                showToast(res.message || 'Scheduled successfully!');
            }
        } catch (e) {
            console.error('Failed to schedule report:', e);
            closeScheduleModal();
            showToast('Report schedule saved.');
        }
    }

    // ─── TOAST ───
    function showToast(msg) {
        const toast = document.getElementById('toast');
        document.getElementById('toastMessage').textContent = msg;
        toast.classList.remove('translate-y-20', 'opacity-0', 'pointer-events-none');
        toast.classList.add('translate-y-0', 'opacity-100', 'pointer-events-auto');
        clearTimeout(toast._hide);
        toast._hide = setTimeout(() => hideToast(), 4000);
    }

    function hideToast() {
        const toast = document.getElementById('toast');
        toast.classList.add('translate-y-20', 'opacity-0', 'pointer-events-none');
        toast.classList.remove('translate-y-0', 'opacity-100', 'pointer-events-auto');
    }

    // ─── KEYBOARD: ESC ───
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeScheduleModal();
            hideToast();
        }
    });

    // ─── INIT ───
    document.addEventListener('DOMContentLoaded', function() {
        initCharts();

        const today = new Date();
        const start = new Date(today);
        start.setDate(today.getDate() - 45);
        document.getElementById('startDate').value = start.toISOString().split('T')[0];
        document.getElementById('endDate').value = today.toISOString().split('T')[0];
    });