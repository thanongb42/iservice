<?php
/**
 * Admin Reports
 * หน้าแสดงรายงานต่างๆของระบบ
 */

require_once '../config/database.php';
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'รายงาน';
$current_page = 'reports';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'รายงาน']
];

// Get filter parameters
$report_type = $_GET['type'] ?? 'overview';
$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to = $_GET['to'] ?? date('Y-m-t');
$department_id = $_GET['dept'] ?? null;

// ======= DATA FETCHING =======

// Overview Statistics
$stats = [
    'total_users' => $conn->query("SELECT COUNT(*) as cnt FROM users")->fetch_assoc()['cnt'],
    'total_departments' => $conn->query("SELECT COUNT(*) as cnt FROM departments WHERE parent_department_id IS NULL")->fetch_assoc()['cnt'],
    'total_services' => $conn->query("SELECT COUNT(*) as cnt FROM my_service")->fetch_assoc()['cnt'],
    'active_requests' => $conn->query("SELECT COUNT(*) as cnt FROM service_requests WHERE status != 'completed'")->fetch_assoc()['cnt'],
    'total_tech_news' => $conn->query("SELECT COUNT(*) as cnt FROM tech_news WHERE is_active = 1")->fetch_assoc()['cnt'],
];

// Service Requests by Status
$request_status = [];
$status_query = $conn->query("SELECT status, COUNT(*) as count FROM service_requests GROUP BY status");
while ($row = $status_query->fetch_assoc()) {
    $request_status[$row['status']] = $row['count'];
}

// Users by Department
$users_by_dept = [];
$dept_query = $conn->query("SELECT d.department_name as name, COUNT(u.user_id) as count FROM departments d LEFT JOIN users u ON d.department_id = u.department_id WHERE d.parent_department_id IS NULL GROUP BY d.department_id ORDER BY d.department_name");
while ($row = $dept_query->fetch_assoc()) {
    $users_by_dept[] = $row;
}

// Services Stats
$services_stats = [];
$services_query = $conn->query("SELECT id, service_name as name, is_active FROM my_service ORDER BY service_name");
while ($row = $services_query->fetch_assoc()) {
    $services_stats[] = $row;
}

// Service Requests by Type (for charts)
$requests_by_type = [];
$type_query = $conn->query("SELECT service_code, service_name, COUNT(*) as count FROM service_requests GROUP BY service_code ORDER BY count DESC");
while ($row = $type_query->fetch_assoc()) {
    $requests_by_type[] = $row;
}

// Service Requests by Day (past 30 days)
$requests_by_day = [];
$day_query = $conn->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM service_requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
while ($row = $day_query->fetch_assoc()) {
    $requests_by_day[] = $row;
}

// Service Requests by Month (past 12 months)
$requests_by_month = [];
$month_query = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM service_requests 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
while ($row = $month_query->fetch_assoc()) {
    $requests_by_month[] = $row;
}

// Service Requests by Year
$requests_by_year = [];
$year_query = $conn->query("
    SELECT YEAR(created_at) as year, COUNT(*) as count 
    FROM service_requests 
    GROUP BY YEAR(created_at)
    ORDER BY year ASC
");
while ($row = $year_query->fetch_assoc()) {
    $requests_by_year[] = $row;
}

?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<main class="main-content-transition lg:ml-0">

    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .report-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            border-left: 4px solid #0d9488;
        }
        .stat-card h3 {
            font-size: 0.875rem;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #111827;
        }
        .stat-card.secondary { border-left-color: #3b82f6; }
        .stat-card.success { border-left-color: #10b981; }
        .stat-card.warning { border-left-color: #f59e0b; }
        .stat-card.danger { border-left-color: #ef4444; }

        .chart-container {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        .chart-container h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #111827;
        }

        .table-report {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }
        .table-report th {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: white;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
        }
        .table-report td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        .table-report tbody tr:hover {
            background: #f9fafb;
        }

        .filter-panel {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .filter-panel h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #111827;
        }
        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        .filter-group label {
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
        }
        .filter-group input,
        .filter-group select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-family: 'Sarabun', sans-serif;
        }
        .filter-group input:focus,
        .filter-group select:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        .btn-filter {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
            align-self: flex-end;
        }
        .btn-filter:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }
        .btn-export {
            background: #3b82f6;
            color: white;
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-export:hover {
            background: #2563eb;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-completed { background: #d1fae5; color: #065f46; }
        .status-pending { background: #fef3c7; color: #92400e; }
        .status-in-progress { background: #dbeafe; color: #0c4a6e; }
        .status-active { background: #d1fae5; color: #065f46; }
        .status-inactive { background: #f3f4f6; color: #374151; }
    </style>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-chart-bar text-teal-600"></i> รายงาน
            </h1>
            <p class="mt-2 text-gray-600">ดูรายงานเบื้องต้นเกี่ยวกับสถิติและประสิทธิภาพระบบ</p>
        </div>

        <!-- Filter Panel -->
        <div class="filter-panel">
            <h3><i class="fas fa-filter"></i> ตัวกรอง</h3>
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>ประเภทรายงาน</label>
                    <select name="type" onchange="this.form.submit()">
                        <option value="overview" <?= $report_type === 'overview' ? 'selected' : '' ?>>ภาพรวม</option>
                        <option value="users" <?= $report_type === 'users' ? 'selected' : '' ?>>ผู้ใช้งาน</option>
                        <option value="services" <?= $report_type === 'services' ? 'selected' : '' ?>>บริการ</option>
                        <option value="requests" <?= $report_type === 'requests' ? 'selected' : '' ?>>คำขอบริการ</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>วันที่เริ่มต้น</label>
                    <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>">
                </div>

                <div class="filter-group">
                    <label>วันที่สิ้นสุด</label>
                    <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>">
                </div>

                <button type="submit" class="btn-filter" style="margin-top: 1.5rem;">
                    <i class="fas fa-search"></i> ค้นหา
                </button>

                <button type="button" class="btn-export" style="margin-top: 1.5rem;">
                    <i class="fas fa-download"></i> ส่งออก PDF
                </button>
            </form>
        </div>

        <!-- OVERVIEW REPORT -->
        <?php if ($report_type === 'overview'): ?>
            <!-- Statistics Cards -->
            <div class="report-container">
                <div class="stat-card">
                    <h3><i class="fas fa-users"></i> จำนวนผู้ใช้</h3>
                    <div class="value"><?= number_format($stats['total_users']) ?></div>
                </div>

                <div class="stat-card secondary">
                    <h3><i class="fas fa-sitemap"></i> แผนก</h3>
                    <div class="value"><?= number_format($stats['total_departments']) ?></div>
                </div>

                <div class="stat-card success">
                    <h3><i class="fas fa-concierge-bell"></i> บริการ</h3>
                    <div class="value"><?= number_format($stats['total_services']) ?></div>
                </div>

                <div class="stat-card warning">
                    <h3><i class="fas fa-tasks"></i> คำขอค้างอยู่</h3>
                    <div class="value"><?= number_format($stats['active_requests']) ?></div>
                </div>

                <div class="stat-card danger">
                    <h3><i class="fas fa-newspaper"></i> ข่าวเทค</h3>
                    <div class="value"><?= number_format($stats['total_tech_news']) ?></div>
                </div>
            </div>

            <!-- Charts Section -->
            <div style="margin-bottom: 2rem;">
                <!-- 2-column grid for charts -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(500px, 1fr)); gap: 1.5rem;">
                    <!-- Service Requests by Type - Bar Chart -->
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-bar"></i> คำขอตามประเภทบริการ</h3>
                        <div id="chartTypeBar" style="width: 100%; height: 350px;"></div>
                    </div>

                    <!-- Service Requests by Type - Pie Chart -->
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-pie"></i> สัดส่วนคำขอบริการ</h3>
                        <div id="chartTypePie" style="width: 100%; height: 350px;"></div>
                    </div>

                    <!-- Service Requests by Day - Line Chart -->
                    <div class="chart-container" style="grid-column: 1 / -1;">
                        <h3><i class="fas fa-chart-line"></i> คำขอรายวัน (30 วันล่าสุด)</h3>
                        <div id="chartDay" style="width: 100%; height: 350px;"></div>
                    </div>

                    <!-- Service Requests by Month - Line Chart -->
                    <div class="chart-container" style="grid-column: 1 / -1;">
                        <h3><i class="fas fa-chart-line"></i> คำขอรายเดือน (12 เดือนล่าสุด)</h3>
                        <div id="chartMonth" style="width: 100%; height: 350px;"></div>
                    </div>

                    <!-- Service Requests by Year - Bar Chart -->
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-bar"></i> คำขอรายปี</h3>
                        <div id="chartYear" style="width: 100%; height: 350px;"></div>
                    </div>

                    <!-- Service Requests by Status - Pie Chart -->
                    <div class="chart-container">
                        <h3><i class="fas fa-chart-pie"></i> สถานะคำขอบริการ</h3>
                        <div id="chartStatus" style="width: 100%; height: 350px;"></div>
                    </div>
                </div>
            </div>

            <script>
            // Vibrant contrasting colors
            const vibrantColors = [
                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                '#FF9F40', '#E74C3C', '#2ECC71', '#3498DB', '#F39C12',
                '#8E44AD', '#1ABC9C', '#E91E63', '#00BCD4', '#FF5722'
            ];
            const statusColorMap = {
                'pending': '#FFCE56',
                'in_progress': '#36A2EB',
                'completed': '#2ECC71',
                'cancelled': '#E74C3C'
            };
            const statusLabelMap = {
                'pending': 'รอดำเนินการ',
                'in_progress': 'กำลังดำเนินการ',
                'completed': 'เสร็จสิ้น',
                'cancelled': 'ยกเลิก'
            };

            // PHP data
            const typeData = <?= json_encode($requests_by_type) ?>;
            const dayData = <?= json_encode($requests_by_day) ?>;
            const monthData = <?= json_encode($requests_by_month) ?>;
            const yearData = <?= json_encode($requests_by_year) ?>;
            const statusData = <?= json_encode($request_status) ?>;

            // Load Google Charts
            google.charts.load('current', { packages: ['corechart', 'bar'] });
            google.charts.setOnLoadCallback(drawAllCharts);

            function drawAllCharts() {
                drawTypeBar();
                drawTypePie();
                drawDayLine();
                drawMonthLine();
                drawYearBar();
                drawStatusPie();
            }

            // Chart 1: คำขอตามประเภทบริการ (Column)
            function drawTypeBar() {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'บริการ');
                data.addColumn('number', 'จำนวนคำขอ');
                data.addColumn({ type: 'number', role: 'annotation' });
                data.addColumn({ type: 'string', role: 'style' });

                typeData.forEach((d, i) => {
                    data.addRow([
                        d.service_name || d.service_code,
                        parseInt(d.count),
                        parseInt(d.count),
                        vibrantColors[i % vibrantColors.length]
                    ]);
                });

                const options = {
                    legend: 'none',
                    chartArea: { width: '80%', height: '70%' },
                    annotations: {
                        alwaysOutside: true,
                        textStyle: { fontSize: 13, fontName: 'Sarabun', bold: true, color: '#333' }
                    },
                    vAxis: { minValue: 0, format: '#,###', textStyle: { fontSize: 12 } },
                    hAxis: { textStyle: { fontSize: 11 }, slantedText: true, slantedTextAngle: 30 },
                    bar: { groupWidth: '60%' },
                    animation: { startup: true, duration: 800, easing: 'out' }
                };

                const chart = new google.visualization.ColumnChart(document.getElementById('chartTypeBar'));
                chart.draw(data, options);
            }

            // Chart 2: สัดส่วนคำขอบริการ (Pie)
            function drawTypePie() {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'บริการ');
                data.addColumn('number', 'จำนวน');

                typeData.forEach(d => {
                    data.addRow([d.service_name || d.service_code, parseInt(d.count)]);
                });

                const options = {
                    colors: vibrantColors.slice(0, typeData.length),
                    pieSliceText: 'value',
                    pieSliceTextStyle: { fontSize: 13, bold: true },
                    legend: { position: 'labeled', textStyle: { fontSize: 12, fontName: 'Sarabun' } },
                    chartArea: { width: '90%', height: '85%' },
                    pieHole: 0,
                    sliceVisibilityThreshold: 0,
                    animation: { startup: true, duration: 800, easing: 'out' }
                };

                const chart = new google.visualization.PieChart(document.getElementById('chartTypePie'));
                chart.draw(data, options);
            }

            // Chart 3: คำขอรายวัน (Line)
            function drawDayLine() {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'วันที่');
                data.addColumn('number', 'จำนวนคำขอ');
                data.addColumn({ type: 'number', role: 'annotation' });

                dayData.forEach(d => {
                    const date = new Date(d.date);
                    const label = date.toLocaleDateString('th-TH', { day: 'numeric', month: 'short' });
                    const count = parseInt(d.count);
                    data.addRow([label, count, count]);
                });

                const options = {
                    colors: ['#E74C3C'],
                    curveType: 'function',
                    legend: { position: 'none' },
                    chartArea: { width: '88%', height: '70%' },
                    pointSize: 6,
                    pointShape: 'circle',
                    lineWidth: 3,
                    annotations: {
                        textStyle: { fontSize: 10, fontName: 'Sarabun', color: '#E74C3C', bold: true },
                        stem: { length: 8, color: 'transparent' }
                    },
                    vAxis: { minValue: 0, format: '#,###', textStyle: { fontSize: 11 } },
                    hAxis: { textStyle: { fontSize: 10 }, slantedText: true, slantedTextAngle: 45, showTextEvery: Math.ceil(dayData.length / 10) },
                    animation: { startup: true, duration: 800, easing: 'out' }
                };

                const chart = new google.visualization.LineChart(document.getElementById('chartDay'));
                chart.draw(data, options);
            }

            // Chart 4: คำขอรายเดือน (Line)
            function drawMonthLine() {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'เดือน');
                data.addColumn('number', 'จำนวนคำขอ');
                data.addColumn({ type: 'number', role: 'annotation' });

                monthData.forEach(d => {
                    const [year, month] = d.month.split('-');
                    const date = new Date(year, parseInt(month) - 1);
                    const thaiYear = parseInt(year) + 543;
                    const label = date.toLocaleDateString('th-TH', { month: 'short' }) + ' ' + thaiYear;
                    const count = parseInt(d.count);
                    data.addRow([label, count, count]);
                });

                const options = {
                    colors: ['#9966FF'],
                    curveType: 'function',
                    legend: { position: 'none' },
                    chartArea: { width: '88%', height: '70%' },
                    pointSize: 8,
                    pointShape: 'diamond',
                    lineWidth: 3,
                    annotations: {
                        textStyle: { fontSize: 12, fontName: 'Sarabun', color: '#7B42D6', bold: true },
                        stem: { length: 10, color: 'transparent' }
                    },
                    vAxis: { minValue: 0, format: '#,###', textStyle: { fontSize: 11 } },
                    hAxis: { textStyle: { fontSize: 11 }, slantedText: true, slantedTextAngle: 30 },
                    animation: { startup: true, duration: 800, easing: 'out' }
                };

                const chart = new google.visualization.LineChart(document.getElementById('chartMonth'));
                chart.draw(data, options);
            }

            // Chart 5: คำขอรายปี (Column)
            function drawYearBar() {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'ปี');
                data.addColumn('number', 'จำนวนคำขอ');
                data.addColumn({ type: 'number', role: 'annotation' });
                data.addColumn({ type: 'string', role: 'style' });

                yearData.forEach((d, i) => {
                    const thaiYear = 'ปี ' + (parseInt(d.year) + 543);
                    const count = parseInt(d.count);
                    data.addRow([thaiYear, count, count, vibrantColors[i % vibrantColors.length]]);
                });

                const options = {
                    legend: 'none',
                    chartArea: { width: '80%', height: '70%' },
                    annotations: {
                        alwaysOutside: true,
                        textStyle: { fontSize: 14, fontName: 'Sarabun', bold: true, color: '#333' }
                    },
                    vAxis: { minValue: 0, format: '#,###', textStyle: { fontSize: 12 } },
                    hAxis: { textStyle: { fontSize: 13, fontName: 'Sarabun', bold: true } },
                    bar: { groupWidth: '50%' },
                    animation: { startup: true, duration: 800, easing: 'out' }
                };

                const chart = new google.visualization.ColumnChart(document.getElementById('chartYear'));
                chart.draw(data, options);
            }

            // Chart 6: สถานะคำขอบริการ (Pie)
            function drawStatusPie() {
                const data = new google.visualization.DataTable();
                data.addColumn('string', 'สถานะ');
                data.addColumn('number', 'จำนวน');

                const statusKeys = Object.keys(statusData);
                const sliceColors = [];

                statusKeys.forEach(s => {
                    data.addRow([statusLabelMap[s] || s, parseInt(statusData[s])]);
                    sliceColors.push(statusColorMap[s] || '#9ca3af');
                });

                const options = {
                    colors: sliceColors,
                    pieSliceText: 'percentage',
                    pieSliceTextStyle: { fontSize: 14, bold: true, color: '#fff' },
                    legend: { position: 'labeled', textStyle: { fontSize: 13, fontName: 'Sarabun' } },
                    chartArea: { width: '90%', height: '85%' },
                    sliceVisibilityThreshold: 0,
                    animation: { startup: true, duration: 800, easing: 'out' }
                };

                const chart = new google.visualization.PieChart(document.getElementById('chartStatus'));
                chart.draw(data, options);
            }

            // Responsive: redraw on window resize
            window.addEventListener('resize', function() {
                clearTimeout(window._chartResizeTimer);
                window._chartResizeTimer = setTimeout(drawAllCharts, 200);
            });
            </script>

        <?php endif; ?>

        <!-- USERS REPORT -->
        <?php if ($report_type === 'users'): ?>
            <div class="chart-container">
                <h3><i class="fas fa-users"></i> รายงานผู้ใช้งาน</h3>
                <table class="table-report">
                    <thead>
                        <tr>
                            <th>ชื่อผู้ใช้</th>
                            <th>อีเมล</th>
                            <th>แผนก</th>
                            <th>บทบาท</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $users_query = $conn->query("
                            SELECT u.user_id, u.username, u.email, u.role, d.department_name as dept_name 
                            FROM users u 
                            LEFT JOIN departments d ON u.department_id = d.department_id 
                            ORDER BY u.username
                        ");
                        while ($user = $users_query->fetch_assoc()):
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['dept_name'] ?? '-') ?></td>
                                <td><span class="status-badge"><?= htmlspecialchars($user['role']) ?></span></td>
                                <td><span class="status-badge status-active">Active</span></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- SERVICES REPORT -->
        <?php if ($report_type === 'services'): ?>
            <div class="chart-container">
                <h3><i class="fas fa-concierge-bell"></i> รายงานบริการ</h3>
                <table class="table-report">
                    <thead>
                        <tr>
                            <th>ชื่อบริการ</th>
                            <th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services_stats as $service): ?>
                            <tr>
                                <td><?= htmlspecialchars($service['name']) ?></td>
                                <td>
                                    <span class="status-badge <?= $service['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $service['is_active'] ? 'เปิด' : 'ปิด' ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- SERVICE REQUESTS REPORT -->
        <?php if ($report_type === 'requests'): ?>
            <div class="chart-container">
                <h3><i class="fas fa-tasks"></i> รายงานคำขอบริการ</h3>
                <table class="table-report">
                    <thead>
                        <tr>
                            <th>เลขที่</th>
                            <th>บริการ</th>
                            <th>ผู้ขอ</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $requests_query = $conn->query("
                            SELECT sr.request_id, sr.service_name, sr.user_id, sr.status, sr.created_at, u.username
                            FROM service_requests sr
                            LEFT JOIN users u ON sr.user_id = u.user_id
                            WHERE DATE(sr.created_at) BETWEEN '$date_from' AND '$date_to'
                            ORDER BY sr.created_at DESC
                        ");
                        while ($req = $requests_query->fetch_assoc()):
                            $badge_class = 'status-' . strtolower(str_replace(' ', '-', $req['status']));
                        ?>
                            <tr>
                                <td>#<?= htmlspecialchars($req['request_id']) ?></td>
                                <td><?= htmlspecialchars($req['service_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($req['username'] ?? '-') ?></td>
                                <td><span class="status-badge <?= $badge_class ?>"><?= htmlspecialchars($req['status']) ?></span></td>
                                <td><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <script>
    // PDF Export (Basic)
    document.querySelector('.btn-export')?.addEventListener('click', function() {
        alert('ฟีเจอร์นี้จะเพิ่มในรุ่นถัดไป');
        // TODO: Implement PDF export using library like jsPDF or html2pdf
    });
    </script>
</main>

<?php include 'admin-layout/footer.php'; ?>
