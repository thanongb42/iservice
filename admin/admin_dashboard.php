<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

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
            <?php if ($recent_requests && $recent_requests->num_rows > 0): ?>
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
                        <?php echo date('d/m H:i', strtotime($request['created_at'])); ?>
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
                <?php if ($recent_users && $recent_users->num_rows > 0): ?>
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
                            <?php echo date('d/m/Y H:i', strtotime($u['created_at'])); ?>
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
