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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

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
                <!-- Service Requests by Type - Bar Chart -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar"></i> คำขอตามประเภทบริการ</h3>
                    <canvas id="chartTypeBar" style="max-height: 300px;"></canvas>
                </div>

                <!-- Service Requests by Type - Pie Chart -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> สัดส่วนคำขอบริการ</h3>
                    <canvas id="chartTypePie" style="max-height: 300px;"></canvas>
                </div>

                <!-- Service Requests by Day - Line Chart -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line"></i> คำขอรายวัน (30 วันล่าสุด)</h3>
                    <canvas id="chartDay" style="max-height: 300px;"></canvas>
                </div>

                <!-- Service Requests by Month - Line Chart -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-line"></i> คำขอรายเดือน (12 เดือนล่าสุด)</h3>
                    <canvas id="chartMonth" style="max-height: 300px;"></canvas>
                </div>

                <!-- Service Requests by Year - Bar Chart -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-bar"></i> คำขอรายปี</h3>
                    <canvas id="chartYear" style="max-height: 300px;"></canvas>
                </div>

                <!-- Service Requests by Status - Pie Chart -->
                <div class="chart-container">
                    <h3><i class="fas fa-chart-pie"></i> สถานะคำขอบริการ</h3>
                    <canvas id="chartStatus" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <script>
            // Color palettes
            const colors = {
                bar: ['#0d9488', '#0f766e', '#06b6d4', '#0891b2', '#06a7d2', '#0e7490'],
                status: {
                    'pending': '#fbbf24',
                    'in_progress': '#60a5fa',
                    'completed': '#34d399',
                    'cancelled': '#f87171'
                }
            };

            // Chart 1: Service Requests by Type (Bar)
            const typeData = <?= json_encode($requests_by_type) ?>;
            const ctxTypeBar = document.getElementById('chartTypeBar').getContext('2d');
            new Chart(ctxTypeBar, {
                type: 'bar',
                data: {
                    labels: typeData.map(d => d.service_name || d.service_code),
                    datasets: [{
                        label: 'จำนวนคำขอ',
                        data: typeData.map(d => d.count),
                        backgroundColor: typeData.map((_, i) => colors.bar[i % colors.bar.length]),
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Chart 2: Service Requests by Type (Pie)
            const ctxTypePie = document.getElementById('chartTypePie').getContext('2d');
            new Chart(ctxTypePie, {
                type: 'pie',
                data: {
                    labels: typeData.map(d => d.service_name || d.service_code),
                    datasets: [{
                        data: typeData.map(d => d.count),
                        backgroundColor: typeData.map((_, i) => colors.bar[i % colors.bar.length])
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // Chart 3: Service Requests by Day (Line)
            const dayData = <?= json_encode($requests_by_day) ?>;
            const ctxDay = document.getElementById('chartDay').getContext('2d');
            new Chart(ctxDay, {
                type: 'line',
                data: {
                    labels: dayData.map(d => {
                        const date = new Date(d.date);
                        return date.toLocaleDateString('th-TH');
                    }),
                    datasets: [{
                        label: 'จำนวนคำขอ',
                        data: dayData.map(d => d.count),
                        borderColor: '#0d9488',
                        backgroundColor: 'rgba(13, 148, 136, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: '#0d9488'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: true } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Chart 4: Service Requests by Month (Line)
            const monthData = <?= json_encode($requests_by_month) ?>;
            const ctxMonth = document.getElementById('chartMonth').getContext('2d');
            new Chart(ctxMonth, {
                type: 'line',
                data: {
                    labels: monthData.map(d => {
                        const [year, month] = d.month.split('-');
                        const date = new Date(year, parseInt(month) - 1);
                        return date.toLocaleDateString('th-TH', { year: 'numeric', month: 'short' });
                    }),
                    datasets: [{
                        label: 'จำนวนคำขอ',
                        data: monthData.map(d => d.count),
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 5,
                        pointBackgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: true } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Chart 5: Service Requests by Year (Bar)
            const yearData = <?= json_encode($requests_by_year) ?>;
            const ctxYear = document.getElementById('chartYear').getContext('2d');
            new Chart(ctxYear, {
                type: 'bar',
                data: {
                    labels: yearData.map(d => 'ปี ' + (d.year + 543)),
                    datasets: [{
                        label: 'จำนวนคำขอ',
                        data: yearData.map(d => d.count),
                        backgroundColor: '#10b981',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true } }
                }
            });

            // Chart 6: Service Requests by Status (Pie)
            const statusData = <?= json_encode($request_status) ?>;
            const ctxStatus = document.getElementById('chartStatus').getContext('2d');
            const statusLabels = Object.keys(statusData);
            const statusColors = statusLabels.map(s => colors.status[s] || '#9ca3af');
            new Chart(ctxStatus, {
                type: 'pie',
                data: {
                    labels: statusLabels.map(s => {
                        const labels = { 'pending': 'รอดำเนินการ', 'in_progress': 'กำลังดำเนินการ', 'completed': 'เสร็จสิ้น', 'cancelled': 'ยกเลิก' };
                        return labels[s] || s;
                    }),
                    datasets: [{
                        data: statusLabels.map(s => statusData[s]),
                        backgroundColor: statusColors
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: { legend: { position: 'bottom' } }
                }
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
