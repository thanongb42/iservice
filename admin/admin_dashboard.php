<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin or manager
require_manager_or_admin();

// Get user info
$user = [
    'username' => $_SESSION['username'] ?? 'Admin',
    'email' => $_SESSION['email'] ?? '',
    'full_name' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin',
    'first_name' => $_SESSION['first_name'] ?? 'Admin'
];

// Get statistics
$stats = [];

// Total users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Active users
$result = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$stats['active_users'] = $result->fetch_assoc()['count'];

// Total departments
$result = $conn->query("SELECT COUNT(*) as count FROM departments");
$stats['total_departments'] = $result->fetch_assoc()['count'];

// Total service requests
$result = $conn->query("SHOW TABLES LIKE 'service_requests'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM service_requests");
    $stats['total_requests'] = $result->fetch_assoc()['count'];

    $result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'pending'");
    $stats['pending_requests'] = $result->fetch_assoc()['count'];

    $result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'in_progress'");
    $stats['in_progress_requests'] = $result->fetch_assoc()['count'];

    $result = $conn->query("SELECT COUNT(*) as count FROM service_requests WHERE status = 'completed'");
    $stats['completed_requests'] = $result->fetch_assoc()['count'];
} else {
    $stats['total_requests'] = 0;
    $stats['pending_requests'] = 0;
    $stats['in_progress_requests'] = 0;
    $stats['completed_requests'] = 0;
}

// Learning resources count
$result = $conn->query("SHOW TABLES LIKE 'learning_resources'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM learning_resources WHERE is_active = 1");
    $stats['learning_resources'] = $result->fetch_assoc()['count'];
} else {
    $stats['learning_resources'] = 0;
}

// Tech news count
$result = $conn->query("SHOW TABLES LIKE 'tech_news'");
if ($result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_active = 1");
    $stats['tech_news'] = $result->fetch_assoc()['count'];
} else {
    $stats['tech_news'] = 0;
}

// Visitor statistics (daily aggregated in visitor_stats)
$visitor_enabled = false;
$visitor_period  = isset($_GET['visitor_period']) ? $_GET['visitor_period'] : 'month';
$allowed_periods = ['day', 'week', 'month', 'quarter', 'year'];
if (!in_array($visitor_period, $allowed_periods, true)) {
    $visitor_period = 'month';
}

$visitor_stats      = [];
$visitor_total_hits = 0;
$visitor_min_year   = (int)date('Y');
$visitor_max_year   = (int)date('Y');
$visitor_year       = null;

if (function_exists('table_exists') && table_exists('visitor_stats')) {
    $visitor_enabled = true;

    // Find available year range from data
    $res_years = $conn->query("SELECT MIN(visit_date) AS min_d, MAX(visit_date) AS max_d FROM visitor_stats");
    if ($res_years) {
        $row_years = $res_years->fetch_assoc();
        if (!empty($row_years['min_d'])) {
            $visitor_min_year = (int)date('Y', strtotime($row_years['min_d']));
        }
        if (!empty($row_years['max_d'])) {
            $visitor_max_year = (int)date('Y', strtotime($row_years['max_d']));
        }
    }

    // Selected year (for week / month / quarter views)
    if (isset($_GET['visitor_year'])) {
        $tmp_year = (int)$_GET['visitor_year'];
        if ($tmp_year >= $visitor_min_year && $tmp_year <= $visitor_max_year) {
            $visitor_year = $tmp_year;
        }
    }
    if ($visitor_year === null) {
        $visitor_year = $visitor_max_year;
    }

    // Build statistics based on period
    switch ($visitor_period) {
        case 'day':
            // Last 30 days
            $sql = "
                SELECT visit_date, SUM(visit_count) AS total_hits
                FROM visitor_stats
                WHERE visit_date >= CURDATE() - INTERVAL 29 DAY
                GROUP BY visit_date
                ORDER BY visit_date ASC
            ";
            $res = $conn->query($sql);
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $visitor_stats[] = [
                        'label' => thdate('d/m/Y', $r['visit_date']),
                        'total' => (int)$r['total_hits'],
                    ];
                    $visitor_total_hits += (int)$r['total_hits'];
                }
            }
            break;

        case 'week':
            // Weekly summary for selected year (ISO week)
            $sql = "
                SELECT YEARWEEK(visit_date, 1) AS yw,
                       MIN(visit_date) AS start_d,
                       MAX(visit_date) AS end_d,
                       SUM(visit_count) AS total_hits
                FROM visitor_stats
                WHERE YEAR(visit_date) = {$visitor_year}
                GROUP BY yw
                ORDER BY yw ASC
            ";
            $res = $conn->query($sql);
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $label = thdate('d/m', $r['start_d']) . ' - ' . thdate('d/m', $r['end_d']);
                    $visitor_stats[] = [
                        'label' => $label,
                        'total' => (int)$r['total_hits'],
                    ];
                    $visitor_total_hits += (int)$r['total_hits'];
                }
            }
            break;

        case 'month':
            // Monthly summary for selected year
            $sql = "
                SELECT MONTH(visit_date) AS m,
                       SUM(visit_count) AS total_hits
                FROM visitor_stats
                WHERE YEAR(visit_date) = {$visitor_year}
                GROUP BY m
                ORDER BY m ASC
            ";
            $res = $conn->query($sql);
            if ($res) {
                $thai_months = [
                    1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม',
                    4 => 'เมษายน', 5 => 'พฤษภาคม', 6 => 'มิถุนายน',
                    7 => 'กรกฎาคม', 8 => 'สิงหาคม', 9 => 'กันยายน',
                    10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
                ];
                while ($r = $res->fetch_assoc()) {
                    $m = (int)$r['m'];
                    $label = $thai_months[$m] ?? "เดือนที่ {$m}";
                    $visitor_stats[] = [
                        'label' => $label,
                        'total' => (int)$r['total_hits'],
                    ];
                    $visitor_total_hits += (int)$r['total_hits'];
                }
            }
            break;

        case 'quarter':
            // Quarterly summary for selected year
            $sql = "
                SELECT QUARTER(visit_date) AS q,
                       SUM(visit_count) AS total_hits
                FROM visitor_stats
                WHERE YEAR(visit_date) = {$visitor_year}
                GROUP BY q
                ORDER BY q ASC
            ";
            $res = $conn->query($sql);
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $q = (int)$r['q'];
                    $label = 'ไตรมาส ' . $q;
                    $visitor_stats[] = [
                        'label' => $label,
                        'total' => (int)$r['total_hits'],
                    ];
                    $visitor_total_hits += (int)$r['total_hits'];
                }
            }
            break;

        case 'year':
            // Yearly summary for all years
            $sql = "
                SELECT YEAR(visit_date) AS y,
                       SUM(visit_count) AS total_hits
                FROM visitor_stats
                GROUP BY y
                ORDER BY y ASC
            ";
            $res = $conn->query($sql);
            if ($res) {
                while ($r = $res->fetch_assoc()) {
                    $y = (int)$r['y'];
                    $label = thdate('Y', "{$y}-01-01");
                    $visitor_stats[] = [
                        'label' => $label,
                        'total' => (int)$r['total_hits'],
                        'year'  => $y,
                    ];
                    $visitor_total_hits += (int)$r['total_hits'];
                }
            }
            break;
    }
}

// Recent users
$recent_users = $conn->query("SELECT * FROM v_users_full ORDER BY created_at DESC LIMIT 5");

// Recent service requests
$recent_requests_query = $conn->query("SHOW TABLES LIKE 'service_requests'");
$recent_requests = null;
if ($recent_requests_query->num_rows > 0) {
    $recent_requests = $conn->query("SELECT * FROM service_requests ORDER BY created_at DESC LIMIT 5");
}

// Page configuration
$page_title = 'แดชบอร์ด';
$current_page = 'dashboard';
$pending_requests = $stats['pending_requests'];
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'แดชบอร์ด']
];

// Include layout components
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<!-- Welcome Section -->
<div class="mb-8">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
        สวัสดี, <?php echo htmlspecialchars($user['first_name']); ?>!
    </h1>
    <p class="text-gray-600">ยินดีต้อนรับสู่ระบบจัดการ - เทศบาลนครรังสิต</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
    <!-- Total Users -->
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-l-4 border-blue-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">ผู้ใช้งานทั้งหมด</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_users']); ?></p>
                <p class="text-green-600 text-sm mt-2">
                    <i class="fas fa-user-check mr-1"></i><?php echo number_format($stats['active_users']); ?> ใช้งานอยู่
                </p>
            </div>
            <div class="w-14 h-14 bg-blue-100 rounded-full flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Departments -->
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-l-4 border-purple-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">หน่วยงาน</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_departments']); ?></p>
                <p class="text-purple-600 text-sm mt-2">
                    <i class="fas fa-sitemap mr-1"></i>โครงสร้าง 4 ระดับ
                </p>
            </div>
            <div class="w-14 h-14 bg-purple-100 rounded-full flex items-center justify-center">
                <i class="fas fa-building text-purple-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Service Requests -->
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-l-4 border-orange-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">คำขอบริการ</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_requests']); ?></p>
                <p class="text-orange-600 text-sm mt-2">
                    <i class="fas fa-clock mr-1"></i><?php echo number_format($stats['pending_requests']); ?> รอดำเนินการ
                </p>
            </div>
            <div class="w-14 h-14 bg-orange-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clipboard-list text-orange-600 text-2xl"></i>
            </div>
        </div>
    </div>

    <!-- Learning Resources -->
    <div class="bg-white rounded-xl shadow-md p-6 hover:shadow-lg transition border-l-4 border-teal-500">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-600 text-sm font-medium">ศูนย์การเรียนรู้</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['learning_resources']); ?></p>
                <p class="text-teal-600 text-sm mt-2">
                    <i class="fas fa-newspaper mr-1"></i><?php echo number_format($stats['tech_news']); ?> ข่าวสาร
                </p>
            </div>
            <div class="w-14 h-14 bg-teal-100 rounded-full flex items-center justify-center">
                <i class="fas fa-book-open text-teal-600 text-2xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Request Status Overview -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Pending -->
    <div class="bg-gradient-to-r from-yellow-400 to-yellow-500 rounded-xl shadow-md p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-yellow-100 text-sm font-medium">รอดำเนินการ</p>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($stats['pending_requests']); ?></p>
            </div>
            <i class="fas fa-hourglass-half text-4xl text-yellow-200"></i>
        </div>
    </div>

    <!-- In Progress -->
    <div class="bg-gradient-to-r from-blue-400 to-blue-500 rounded-xl shadow-md p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium">กำลังดำเนินการ</p>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($stats['in_progress_requests']); ?></p>
            </div>
            <i class="fas fa-spinner text-4xl text-blue-200"></i>
        </div>
    </div>

    <!-- Completed -->
    <div class="bg-gradient-to-r from-green-400 to-green-500 rounded-xl shadow-md p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-green-100 text-sm font-medium">เสร็จสิ้น</p>
                <p class="text-4xl font-bold mt-2"><?php echo number_format($stats['completed_requests']); ?></p>
            </div>
            <i class="fas fa-check-circle text-4xl text-green-200"></i>
        </div>
    </div>
</div>

<?php if ($visitor_enabled): ?>
<!-- Visitor Statistics -->
<div class="bg-white rounded-xl shadow-md p-6 mb-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
        <div>
            <h2 class="text-xl font-bold text-gray-900 flex items-center">
                <i class="fas fa-chart-line text-indigo-600 mr-2"></i>
                สถิติผู้เข้าชมเว็บไซต์
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                สรุปจำนวนการเข้าชมตามช่วงเวลา (รายวัน / สัปดาห์ / เดือน / ไตรมาส / ปี)
            </p>
        </div>
        <form method="get" class="flex flex-wrap items-center gap-2">
            <!-- Keep other dashboard query params (if any) -->
            <?php foreach ($_GET as $k => $v): ?>
                <?php if (in_array($k, ['visitor_period','visitor_year'], true)) continue; ?>
                <input type="hidden" name="<?php echo htmlspecialchars($k); ?>" value="<?php echo htmlspecialchars($v); ?>">
            <?php endforeach; ?>
            <label class="text-sm text-gray-700">
                ช่วงเวลา:
                <select name="visitor_period" class="ml-1 border-gray-300 rounded-md text-sm px-2 py-1">
                    <option value="day"     <?php echo $visitor_period === 'day' ? 'selected' : ''; ?>>รายวัน (30 วันที่ผ่านมา)</option>
                    <option value="week"    <?php echo $visitor_period === 'week' ? 'selected' : ''; ?>>รายสัปดาห์ (ตามปี)</option>
                    <option value="month"   <?php echo $visitor_period === 'month' ? 'selected' : ''; ?>>รายเดือน (ตามปี)</option>
                    <option value="quarter" <?php echo $visitor_period === 'quarter' ? 'selected' : ''; ?>>รายไตรมาส (ตามปี)</option>
                    <option value="year"    <?php echo $visitor_period === 'year' ? 'selected' : ''; ?>>รายปี</option>
                </select>
            </label>

            <?php if ($visitor_period !== 'year'): ?>
            <label class="text-sm text-gray-700">
                ปี:
                <select name="visitor_year" class="ml-1 border-gray-300 rounded-md text-sm px-2 py-1">
                    <?php for ($y = $visitor_max_year; $y >= $visitor_min_year; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($y === $visitor_year) ? 'selected' : ''; ?>>
                            <?php echo thdate('Y', "{$y}-01-01"); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </label>
            <?php endif; ?>

            <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition">
                แสดงผล
            </button>
        </form>
    </div>

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
        <div>
            <p class="text-sm text-gray-600">
                รวมทั้งหมด:
                <span class="font-semibold text-indigo-700">
                    <?php echo number_format($visitor_total_hits); ?>
                </span>
                ครั้ง
            </p>
            <?php if ($visitor_period !== 'day' && $visitor_period !== 'year'): ?>
                <p class="text-xs text-gray-500">
                    ปีที่เลือก: <?php echo thdate('Y', "{$visitor_year}-01-01"); ?>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($visitor_stats)): ?>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left font-semibold text-gray-600 uppercase tracking-wider">
                        ช่วงเวลา
                    </th>
                    <th class="px-4 py-2 text-right font-semibold text-gray-600 uppercase tracking-wider">
                        จำนวนเข้าชม (ครั้ง)
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($visitor_stats as $row): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 whitespace-nowrap text-gray-800">
                        <?php echo htmlspecialchars($row['label']); ?>
                    </td>
                    <td class="px-4 py-2 whitespace-nowrap text-right text-gray-900 font-medium">
                        <?php echo number_format($row['total']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="text-center py-8 text-gray-500">
        <i class="fas fa-info-circle text-3xl mb-2"></i>
        <p>ยังไม่มีข้อมูลสถิติการเข้าชมสำหรับช่วงเวลาที่เลือก</p>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Quick Actions & Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-bolt text-yellow-500 mr-2"></i>เมนูลัด
        </h2>
        <div class="grid grid-cols-2 gap-4">
            <a href="user-manager.php" class="flex flex-col items-center p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                <i class="fas fa-user-plus text-blue-600 text-2xl mb-2"></i>
                <span class="text-sm font-medium text-gray-700">เพิ่มผู้ใช้</span>
            </a>
            <a href="departments.php" class="flex flex-col items-center p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                <i class="fas fa-plus-circle text-purple-600 text-2xl mb-2"></i>
                <span class="text-sm font-medium text-gray-700">เพิ่มหน่วยงาน</span>
            </a>
            <a href="service_requests.php" class="flex flex-col items-center p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                <i class="fas fa-tasks text-orange-600 text-2xl mb-2"></i>
                <span class="text-sm font-medium text-gray-700">จัดการคำขอ</span>
            </a>
            <a href="learning_resources.php" class="flex flex-col items-center p-4 bg-teal-50 rounded-lg hover:bg-teal-100 transition">
                <i class="fas fa-book text-teal-600 text-2xl mb-2"></i>
                <span class="text-sm font-medium text-gray-700">เพิ่มบทความ</span>
            </a>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="bg-white rounded-xl shadow-md p-6">
        <h2 class="text-xl font-bold text-gray-900 mb-4">
            <i class="fas fa-history text-teal-600 mr-2"></i>กิจกรรมล่าสุด
        </h2>
        <div class="space-y-4">
            <?php if ($recent_requests instanceof mysqli_result && $recent_requests->num_rows > 0): ?>
                <?php while ($request = $recent_requests->fetch_assoc()): ?>
                <div class="flex items-center space-x-4 p-3 hover:bg-gray-50 rounded-lg">
                    <div class="w-10 h-10 <?php
                        echo match($request['status']) {
                            'pending' => 'bg-yellow-100',
                            'in_progress' => 'bg-blue-100',
                            'completed' => 'bg-green-100',
                            'cancelled' => 'bg-red-100',
                            default => 'bg-gray-100'
                        };
                    ?> rounded-full flex items-center justify-center">
                        <i class="fas <?php
                            echo match($request['status']) {
                                'pending' => 'fa-clock text-yellow-600',
                                'in_progress' => 'fa-spinner text-blue-600',
                                'completed' => 'fa-check text-green-600',
                                'cancelled' => 'fa-times text-red-600',
                                default => 'fa-question text-gray-600'
                            };
                        ?>"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-900 text-sm"><?php echo htmlspecialchars($request['service_name'] ?? $request['service_code']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($request['requester_name'] ?? 'ไม่ระบุ'); ?></p>
                    </div>
                    <span class="text-xs text-gray-400">
                        <?php echo thdate('d/m H:i', strtotime($request['created_at'])); ?>
                    </span>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-inbox text-4xl mb-2"></i>
                    <p>ยังไม่มีกิจกรรม</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Recent Users Table -->
<div class="bg-white rounded-xl shadow-md p-6">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900">
            <i class="fas fa-user-plus text-teal-600 mr-2"></i>ผู้ใช้งานล่าสุด
        </h2>
        <a href="user-manager.php" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 transition text-sm">
            <i class="fas fa-users mr-2"></i>ดูทั้งหมด
        </a>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">ผู้ใช้</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Email</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">บทบาท</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">สถานะ</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">วันที่สร้าง</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if ($recent_users instanceof mysqli_result && $recent_users->num_rows > 0): ?>
                    <?php while ($u = $recent_users->fetch_assoc()): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center">
                                    <span class="text-teal-600 font-bold text-sm">
                                        <?php echo strtoupper(substr($u['username'], 0, 1)); ?>
                                    </span>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($u['full_name']); ?></div>
                                    <div class="text-xs text-gray-500">@<?php echo htmlspecialchars($u['username']); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($u['email']); ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <?php
                            $role_colors = [
                                'admin' => 'bg-blue-100 text-blue-800',
                                'staff' => 'bg-purple-100 text-purple-800',
                                'user' => 'bg-gray-100 text-gray-800'
                            ];
                            $role_text = ['admin' => 'ผู้ดูแล', 'staff' => 'เจ้าหน้าที่', 'user' => 'ผู้ใช้'];
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $role_colors[$u['role']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo $role_text[$u['role']] ?? $u['role']; ?>
                            </span>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap">
                            <?php if ($u['status'] === 'active'): ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">ใช้งาน</span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">ปิดใช้งาน</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo thdate('d/m/Y H:i', strtotime($u['created_at'])); ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                            <i class="fas fa-users text-4xl mb-2"></i>
                            <p>ยังไม่มีข้อมูลผู้ใช้งาน</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'admin-layout/footer.php'; ?>
