<?php
/**
 * Admin Reports
 * หน้าแสดงรายงานต่างๆของระบบ
 */

require_once '../config/database.php';
session_start();

// Check admin access
if (!isset($_SESSION['admin_id'])) {
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
    'total_departments' => $conn->query("SELECT COUNT(*) as cnt FROM departments WHERE parent_id IS NULL")->fetch_assoc()['cnt'],
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
$dept_query = $conn->query("SELECT d.name, COUNT(u.id) as count FROM departments d LEFT JOIN users u ON d.id = u.department_id WHERE d.parent_id IS NULL GROUP BY d.id ORDER BY d.name");
while ($row = $dept_query->fetch_assoc()) {
    $users_by_dept[] = $row;
}

// Services Stats
$services_stats = [];
$services_query = $conn->query("SELECT id, name, is_active FROM my_service ORDER BY name");
while ($row = $services_query->fetch_assoc()) {
    $services_stats[] = $row;
}

?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

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
            <div class="report-container">
                <!-- Service Requests by Status -->
                <div class="chart-container">
                    <h3><i class="fas fa-tasks"></i> สถานะคำขอบริการ</h3>
                    <table class="table-report">
                        <thead>
                            <tr>
                                <th>สถานะ</th>
                                <th>จำนวน</th>
                                <th>เปอร์เซ็นต์</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $total_requests = array_sum($request_status);
                            foreach ($request_status as $status => $count):
                                $percent = $total_requests > 0 ? round($count / $total_requests * 100) : 0;
                                $badge_class = 'status-' . strtolower(str_replace(' ', '-', $status));
                            ?>
                                <tr>
                                    <td><span class="status-badge <?= $badge_class ?>"><?= htmlspecialchars($status) ?></span></td>
                                    <td><?= number_format($count) ?></td>
                                    <td>
                                        <div style="background: #e5e7eb; border-radius: 9999px; height: 6px; width: 100px; overflow: hidden;">
                                            <div style="background: #0d9488; height: 100%; width: <?= $percent ?>%;"></div>
                                        </div>
                                        <?= $percent ?>%
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Users by Department -->
                <div class="chart-container">
                    <h3><i class="fas fa-sitemap"></i> ผู้ใช้งานตามแผนก</h3>
                    <table class="table-report">
                        <thead>
                            <tr>
                                <th>แผนก</th>
                                <th>จำนวนผู้ใช้</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_by_dept as $dept): ?>
                                <tr>
                                    <td><?= htmlspecialchars($dept['name']) ?></td>
                                    <td><?= number_format($dept['count']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

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
                            SELECT u.id, u.username, u.email, u.role, d.name as dept_name 
                            FROM users u 
                            LEFT JOIN departments d ON u.department_id = d.id 
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
                            SELECT sr.id, sr.service_id, sr.user_id, sr.status, sr.created_at, ms.name, u.username
                            FROM service_requests sr
                            LEFT JOIN my_service ms ON sr.service_id = ms.id
                            LEFT JOIN users u ON sr.user_id = u.id
                            WHERE DATE(sr.created_at) BETWEEN '$date_from' AND '$date_to'
                            ORDER BY sr.created_at DESC
                        ");
                        while ($req = $requests_query->fetch_assoc()):
                            $badge_class = 'status-' . strtolower(str_replace(' ', '-', $req['status']));
                        ?>
                            <tr>
                                <td>#<?= htmlspecialchars($req['id']) ?></td>
                                <td><?= htmlspecialchars($req['name'] ?? '-') ?></td>
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

</main>

<?php include 'admin-layout/footer.php'; ?>

<script>
// PDF Export (Basic)
document.querySelector('.btn-export')?.addEventListener('click', function() {
    alert('ฟีเจอร์นี้จะเพิ่มในรุ่นถัดไป');
    // TODO: Implement PDF export using library like jsPDF or html2pdf
});
</script>
