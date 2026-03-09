<?php
session_start();
require_once '../config/database.php';
require_manager_or_admin();

$page_title   = 'สถิติผู้เข้าชมเว็บไซต์';
$current_page = 'visitor_stats';
$breadcrumb   = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'สถิติผู้เข้าชม'],
];

$visitor_enabled = function_exists('table_exists') && table_exists('visitor_stats');

// Summary totals for top cards
$summary = ['total' => 0, 'today' => 0, 'this_month' => 0, 'yesterday' => 0];
if ($visitor_enabled) {
    $res = $conn->query("SELECT SUM(visit_count) AS t FROM visitor_stats");
    if ($res) $summary['total'] = (int)($res->fetch_assoc()['t'] ?? 0);

    $res = $conn->query("SELECT SUM(visit_count) AS t FROM visitor_stats WHERE visit_date = CURDATE()");
    if ($res) $summary['today'] = (int)($res->fetch_assoc()['t'] ?? 0);

    $res = $conn->query("SELECT SUM(visit_count) AS t FROM visitor_stats WHERE visit_date = CURDATE() - INTERVAL 1 DAY");
    if ($res) $summary['yesterday'] = (int)($res->fetch_assoc()['t'] ?? 0);

    $res = $conn->query("SELECT SUM(visit_count) AS t FROM visitor_stats WHERE YEAR(visit_date)=YEAR(CURDATE()) AND MONTH(visit_date)=MONTH(CURDATE())");
    if ($res) $summary['this_month'] = (int)($res->fetch_assoc()['t'] ?? 0);
}

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-1 flex items-center gap-3">
        <i class="fas fa-chart-line text-indigo-600"></i>
        สถิติผู้เข้าชมเว็บไซต์
    </h1>
    <p class="text-gray-500 text-sm">ติดตามจำนวนผู้เข้าชมหน้าแรกของเว็บไซต์ iService</p>
</div>

<?php if (!$visitor_enabled): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center text-yellow-700">
    <i class="fas fa-exclamation-triangle text-3xl mb-2"></i>
    <p class="font-medium">ไม่พบตาราง <code>visitor_stats</code> ในฐานข้อมูล</p>
    <p class="text-sm mt-1">กรุณาตรวจสอบว่า <code>includes/header_public.php</code> มีการบันทึกข้อมูลการเข้าชมอยู่</p>
</div>
<?php else: ?>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-1">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">วันนี้</p>
        <p class="text-3xl font-bold text-indigo-700"><?php echo number_format($summary['today']); ?></p>
        <p class="text-xs text-gray-400">ครั้ง</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-1">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">เมื่อวาน</p>
        <p class="text-3xl font-bold text-blue-700"><?php echo number_format($summary['yesterday']); ?></p>
        <p class="text-xs text-gray-400">ครั้ง</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-1">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">เดือนนี้</p>
        <p class="text-3xl font-bold text-green-700"><?php echo number_format($summary['this_month']); ?></p>
        <p class="text-xs text-gray-400">ครั้ง</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex flex-col gap-1">
        <p class="text-xs text-gray-500 font-medium uppercase tracking-wide">ทั้งหมด</p>
        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($summary['total']); ?></p>
        <p class="text-xs text-gray-400">ครั้ง (ตั้งแต่เริ่มต้น)</p>
    </div>
</div>

<!-- Main Chart Card -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <!-- Toolbar -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-chart-bar text-indigo-500"></i> กราฟจำนวนผู้เข้าชมรายวัน
        </h2>
        <button id="vs-btn-refresh" class="px-3 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition flex items-center gap-1 self-start sm:self-auto">
            <i class="fas fa-sync-alt text-xs"></i> รีเฟรช
        </button>
    </div>

    <!-- Quick Presets -->
    <div class="flex flex-wrap gap-2 mb-4">
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-700 transition" data-preset="yesterday">เมื่อวาน</button>
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-700 transition" data-preset="today">วันนี้</button>
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-700 transition" data-preset="this_week">สัปดาห์นี้</button>
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-indigo-500 bg-indigo-50 text-indigo-700 font-medium" data-preset="this_month">เดือนนี้</button>
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-700 transition" data-preset="last_month">เดือนที่แล้ว</button>
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-700 transition" data-preset="this_quarter">ไตรมาสนี้</button>
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-700 transition" data-preset="this_year">ปีนี้</button>
        <button class="vs-preset px-3 py-1.5 text-sm rounded-lg border border-gray-300 hover:bg-indigo-50 hover:border-indigo-400 hover:text-indigo-700 transition" data-preset="last_year">ปีที่แล้ว</button>
    </div>

    <!-- Custom Date Range -->
    <div class="flex flex-wrap items-center gap-3 mb-5 p-3 bg-gray-50 rounded-lg">
        <span class="text-sm text-gray-600 font-medium">ช่วงวันที่:</span>
        <div class="flex items-center gap-2">
            <input type="date" id="vs-start-date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <span class="text-gray-400 text-sm">ถึง</span>
            <input type="date" id="vs-end-date" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
        </div>
        <button id="vs-btn-search" class="px-4 py-1.5 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700 transition">
            <i class="fas fa-search mr-1"></i>ค้นหา
        </button>
    </div>

    <!-- Period Summary -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        <div class="bg-indigo-50 rounded-lg p-3 text-center">
            <p class="text-xs text-indigo-600 font-medium mb-1">รวมในช่วง</p>
            <p class="text-2xl font-bold text-indigo-700" id="vs-total">—</p>
            <p class="text-xs text-indigo-500">ครั้ง</p>
        </div>
        <div class="bg-blue-50 rounded-lg p-3 text-center">
            <p class="text-xs text-blue-600 font-medium mb-1">สูงสุด/วัน</p>
            <p class="text-2xl font-bold text-blue-700" id="vs-max">—</p>
            <p class="text-xs text-blue-500">ครั้ง</p>
        </div>
        <div class="bg-green-50 rounded-lg p-3 text-center">
            <p class="text-xs text-green-600 font-medium mb-1">เฉลี่ย/วัน</p>
            <p class="text-2xl font-bold text-green-700" id="vs-avg">—</p>
            <p class="text-xs text-green-500">ครั้ง</p>
        </div>
        <div class="bg-gray-50 rounded-lg p-3 text-center border border-gray-200">
            <p class="text-xs text-gray-600 font-medium mb-1">จำนวนวัน</p>
            <p class="text-2xl font-bold text-gray-700" id="vs-days">—</p>
            <p class="text-xs text-gray-500">วัน</p>
        </div>
    </div>

    <!-- Chart -->
    <div class="relative" style="height: 340px;">
        <canvas id="visitorChart"></canvas>
        <div id="vs-no-data" class="hidden absolute inset-0 flex flex-col items-center justify-center text-gray-400">
            <i class="fas fa-chart-bar text-5xl mb-3"></i>
            <p>ไม่มีข้อมูลในช่วงเวลาที่เลือก</p>
        </div>
        <div id="vs-loading" class="absolute inset-0 flex items-center justify-center bg-white bg-opacity-80">
            <i class="fas fa-circle-notch fa-spin text-indigo-500 text-3xl"></i>
        </div>
    </div>
</div>

<!-- Daily Data Table -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fas fa-table text-indigo-500"></i> ข้อมูลรายวัน
    </h2>
    <div class="overflow-x-auto max-h-96 overflow-y-auto">
        <table class="min-w-full text-sm" id="vs-table">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">วันที่</th>
                    <th class="px-4 py-2.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">จำนวนเข้าชม</th>
                    <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">กราฟ</th>
                </tr>
            </thead>
            <tbody id="vs-tbody" class="divide-y divide-gray-100">
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">กำลังโหลด...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    function fmt(d) {
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const dd = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${dd}`;
    }

    function fmtLabel(iso) {
        const [y, m, d] = iso.split('-');
        return `${d}/${m}/${y}`;
    }

    function startOfWeek(d) {
        const tmp = new Date(d);
        const day = tmp.getDay();
        tmp.setDate(tmp.getDate() + (day === 0 ? -6 : 1 - day));
        return tmp;
    }

    function presetRange(preset) {
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        switch (preset) {
            case 'today':      return [fmt(today), fmt(today)];
            case 'yesterday':  { const y = new Date(today); y.setDate(y.getDate()-1); return [fmt(y), fmt(y)]; }
            case 'this_week':  { const s = startOfWeek(today); const e = new Date(s); e.setDate(e.getDate()+6); return [fmt(s), fmt(e > today ? today : e)]; }
            case 'this_month': { const s = new Date(today.getFullYear(), today.getMonth(), 1); return [fmt(s), fmt(today)]; }
            case 'last_month': { const s = new Date(today.getFullYear(), today.getMonth()-1, 1); const e = new Date(today.getFullYear(), today.getMonth(), 0); return [fmt(s), fmt(e)]; }
            case 'this_quarter': { const q = Math.floor(today.getMonth()/3); const s = new Date(today.getFullYear(), q*3, 1); return [fmt(s), fmt(today)]; }
            case 'this_year':  { return [fmt(new Date(today.getFullYear(), 0, 1)), fmt(today)]; }
            case 'last_year':  { const s = new Date(today.getFullYear()-1, 0, 1); const e = new Date(today.getFullYear()-1, 11, 31); return [fmt(s), fmt(e)]; }
            default:           return [fmt(today), fmt(today)];
        }
    }

    // Chart
    const ctx = document.getElementById('visitorChart').getContext('2d');
    let chart = new Chart(ctx, {
        type: 'bar',
        data: { labels: [], datasets: [{ label: 'ผู้เข้าชม', data: [], backgroundColor: 'rgba(99,102,241,0.75)', borderColor: 'rgba(99,102,241,1)', borderWidth: 1.5, borderRadius: 4, hoverBackgroundColor: 'rgba(79,70,229,0.9)' }] },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => ' ' + c.parsed.y.toLocaleString('th-TH') + ' ครั้ง' } }
            },
            scales: {
                x: { ticks: { maxRotation: 45, font: { size: 11 } }, grid: { display: false } },
                y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,0.05)' } }
            }
        }
    });

    let lastData = null;

    function loadData(startDate, endDate) {
        document.getElementById('vs-loading').classList.remove('hidden');
        document.getElementById('vs-no-data').classList.add('hidden');

        fetch(`api/visitor_stats_api.php?start_date=${startDate}&end_date=${endDate}`)
            .then(r => r.json())
            .then(res => {
                document.getElementById('vs-loading').classList.add('hidden');

                if (!res.success || !res.labels.length) {
                    document.getElementById('vs-no-data').classList.remove('hidden');
                    chart.data.labels = [];
                    chart.data.datasets[0].data = [];
                    chart.update();
                    ['vs-total','vs-max','vs-avg','vs-days'].forEach(id => document.getElementById(id).textContent = '0');
                    document.getElementById('vs-tbody').innerHTML = '<tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">ไม่มีข้อมูลในช่วงเวลาที่เลือก</td></tr>';
                    return;
                }

                lastData = res;

                // Auto chart type
                const shouldLine = res.labels.length > 60;
                if (chart.config.type !== (shouldLine ? 'line' : 'bar')) {
                    chart.destroy();
                    chart = new Chart(ctx, {
                        type: shouldLine ? 'line' : 'bar',
                        data: { labels: [], datasets: [{ label: 'ผู้เข้าชม', data: [], backgroundColor: shouldLine ? 'rgba(99,102,241,0.15)' : 'rgba(99,102,241,0.75)', borderColor: 'rgba(99,102,241,1)', borderWidth: shouldLine ? 2 : 1.5, fill: shouldLine, tension: 0.35, pointRadius: res.labels.length > 90 ? 0 : 3, borderRadius: shouldLine ? 0 : 4 }] },
                        options: chart.options
                    });
                }

                chart.data.labels = res.labels.map(fmtLabel);
                chart.data.datasets[0].data = res.data;
                chart.update();

                document.getElementById('vs-total').textContent = res.total.toLocaleString('th-TH');
                document.getElementById('vs-max').textContent   = res.max.toLocaleString('th-TH');
                document.getElementById('vs-avg').textContent   = res.avg.toLocaleString('th-TH');
                document.getElementById('vs-days').textContent  = res.days.toLocaleString('th-TH');

                // Table
                const maxVal = res.max || 1;
                const tbody = document.getElementById('vs-tbody');
                tbody.innerHTML = res.labels.map((lbl, i) => {
                    const val = res.data[i];
                    const pct = Math.round((val / maxVal) * 100);
                    return `<tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 text-gray-400 text-xs">${i+1}</td>
                        <td class="px-4 py-2 text-gray-800">${fmtLabel(lbl)}</td>
                        <td class="px-4 py-2 text-right font-semibold text-gray-900">${val.toLocaleString('th-TH')}</td>
                        <td class="px-4 py-2 w-40">
                            <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-2 bg-indigo-500 rounded-full" style="width:${pct}%"></div>
                            </div>
                        </td>
                    </tr>`;
                }).join('');
            })
            .catch(() => {
                document.getElementById('vs-loading').classList.add('hidden');
            });
    }

    function setActivePreset(el) {
        document.querySelectorAll('.vs-preset').forEach(b => {
            b.classList.remove('border-indigo-500','bg-indigo-50','text-indigo-700','font-medium');
            b.classList.add('border-gray-300');
        });
        if (el) {
            el.classList.add('border-indigo-500','bg-indigo-50','text-indigo-700','font-medium');
            el.classList.remove('border-gray-300');
        }
    }

    document.querySelectorAll('.vs-preset').forEach(btn => {
        btn.addEventListener('click', function () {
            const [s, e] = presetRange(this.dataset.preset);
            document.getElementById('vs-start-date').value = s;
            document.getElementById('vs-end-date').value   = e;
            setActivePreset(this);
            loadData(s, e);
        });
    });

    document.getElementById('vs-btn-search').addEventListener('click', function () {
        const s = document.getElementById('vs-start-date').value;
        const e = document.getElementById('vs-end-date').value;
        if (!s || !e) return;
        setActivePreset(null);
        loadData(s, e);
    });

    document.getElementById('vs-btn-refresh').addEventListener('click', function () {
        const s = document.getElementById('vs-start-date').value;
        const e = document.getElementById('vs-end-date').value;
        if (s && e) loadData(s, e);
    });

    // Init
    const [initS, initE] = presetRange('this_month');
    document.getElementById('vs-start-date').value = initS;
    document.getElementById('vs-end-date').value   = initE;
    loadData(initS, initE);
})();
</script>

<?php endif; ?>
<?php include 'admin-layout/footer.php'; ?>
