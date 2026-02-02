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

// Learning resources
$learning_result = $conn->query("SHOW TABLES LIKE 'learning_resources'");
if ($learning_result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM learning_resources");
    $stats['learning_resources'] = $result->fetch_assoc()['count'];
} else {
    $stats['learning_resources'] = 0;
}

// Tech news
$tech_news_result = $conn->query("SHOW TABLES LIKE 'tech_news'");
if ($tech_news_result->num_rows > 0) {
    $result = $conn->query("SELECT COUNT(*) as count FROM tech_news");
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

<!-- Main Content -->
<main class="main-content-transition lg:ml-0">
    <div class="p-6">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">
                สวัสดี, <?php echo htmlspecialchars($user['first_name']); ?>!
            </h1>
            <p class="text-gray-600">ยินดีต้อนรับสู่ระบบจัดการ - เทศบาลนครรังสิต</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-4">
                        <i class="fas fa-users text-blue-600 text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">ผู้ใช้งานทั้งหมด</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_users']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-4">
                        <i class="fas fa-user-check text-green-600 text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">ผู้ใช้ที่ Active</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['active_users']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-4">
                        <i class="fas fa-building text-purple-600 text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">หน่วยงาน</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['total_departments']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-100 rounded-lg p-4">
                        <i class="fas fa-clipboard-list text-orange-600 text-3xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600 font-medium">คำขอรอดำเนินการ</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo number_format($stats['pending_requests']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mb-8">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-bolt text-yellow-500 mr-2"></i>เมนูจัดการ
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- User Management -->
                <a href="user-manager.php" class="block bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="bg-blue-100 rounded-lg p-3">
                            <i class="fas fa-users-cog text-blue-600 text-2xl"></i>
                        </div>
                        <h4 class="ml-4 text-lg font-bold text-gray-800">จัดการผู้ใช้งาน</h4>
                    </div>
                    <p class="text-gray-600 text-sm">
                        เพิ่ม แก้ไข ลบ และจัดการข้อมูลผู้ใช้งานทั้งหมด
                    </p>
                    <div class="mt-4 flex items-center text-blue-600 font-medium">
                        <span>เข้าสู่หน้าจัดการ</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>

                <!-- Department Management -->
                <a href="departments.php" class="block bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="bg-purple-100 rounded-lg p-3">
                            <i class="fas fa-sitemap text-purple-600 text-2xl"></i>
                        </div>
                        <h4 class="ml-4 text-lg font-bold text-gray-800">จัดการหน่วยงาน</h4>
                    </div>
                    <p class="text-gray-600 text-sm">
                        จัดการโครงสร้างหน่วยงาน 4 ระดับ
                    </p>
                    <div class="mt-4 flex items-center text-purple-600 font-medium">
                        <span>เข้าสู่หน้าจัดการ</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>

                <!-- Service Requests Management -->
                <a href="service_requests.php" class="block bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1">
                    <div class="flex items-center mb-4">
                        <div class="bg-green-100 rounded-lg p-3">
                            <i class="fas fa-tasks text-green-600 text-2xl"></i>
                        </div>
                        <h4 class="ml-4 text-lg font-bold text-gray-800">จัดการคำขอบริการ</h4>
                    </div>
                    <p class="text-gray-600 text-sm">
                        อนุมัติ ปฏิเสธ และติดตามคำขอบริการ
                    </p>
                    <div class="mt-4 flex items-center text-green-600 font-medium">
                        <span>เข้าสู่หน้าจัดการ</span>
                        <i class="fas fa-arrow-right ml-2"></i>
                    </div>
                </a>

                <!-- Prefixes Management -->
                <a href="#" class="block bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1 opacity-75">
                    <div class="flex items-center mb-4">
                        <div class="bg-orange-100 rounded-lg p-3">
                            <i class="fas fa-id-badge text-orange-600 text-2xl"></i>
                        </div>
                        <h4 class="ml-4 text-lg font-bold text-gray-800">จัดการคำนำหน้า</h4>
                    </div>
                    <p class="text-gray-600 text-sm">
                        จัดการคำนำหน้าชื่อ ยศทหาร ตำรวจ และวิชาการ
                    </p>
                    <div class="mt-4 flex items-center text-gray-400 font-medium">
                        <span>กำลังพัฒนา</span>
                        <i class="fas fa-tools ml-2"></i>
                    </div>
                </a>

                <!-- Reports -->
                <a href="#" class="block bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1 opacity-75">
                    <div class="flex items-center mb-4">
                        <div class="bg-red-100 rounded-lg p-3">
                            <i class="fas fa-chart-bar text-red-600 text-2xl"></i>
                        </div>
                        <h4 class="ml-4 text-lg font-bold text-gray-800">รายงานและสถิติ</h4>
                    </div>
                    <p class="text-gray-600 text-sm">
                        ดูรายงานและสถิติการใช้งานระบบ
                    </p>
                    <div class="mt-4 flex items-center text-gray-400 font-medium">
                        <span>กำลังพัฒนา</span>
                        <i class="fas fa-tools ml-2"></i>
                    </div>
                </a>

                <!-- Settings -->
                <a href="#" class="block bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition transform hover:-translate-y-1 opacity-75">
                    <div class="flex items-center mb-4">
                        <div class="bg-gray-100 rounded-lg p-3">
                            <i class="fas fa-cog text-gray-600 text-2xl"></i>
                        </div>
                        <h4 class="ml-4 text-lg font-bold text-gray-800">ตั้งค่าระบบ</h4>
                    </div>
                    <p class="text-gray-600 text-sm">
                        ตั้งค่าและปรับแต่งการทำงานของระบบ
                    </p>
                    <div class="mt-4 flex items-center text-gray-400 font-medium">
                        <span>กำลังพัฒนา</span>
                        <i class="fas fa-tools ml-2"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="bg-white rounded-lg shadow-lg p-6">
            <h3 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-user-plus text-teal-600 mr-2"></i>ผู้ใช้งานล่าสุด
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b">
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">ชื่อผู้ใช้</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">ชื่อ-นามสกุล</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">บทบาท</th>
                            <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">วันที่สร้าง</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $recent_users->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center mr-2">
                                        <span class="text-teal-600 font-bold text-sm">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td class="px-4 py-3 text-sm text-gray-600"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="px-4 py-3">
                                <?php
                                $role_colors = [
                                    'admin' => 'bg-blue-100 text-blue-800',
                                    'staff' => 'bg-purple-100 text-purple-800',
                                    'user' => 'bg-gray-100 text-gray-800'
                                ];
                                $role_text = ['admin' => 'ผู้ดูแล', 'staff' => 'เจ้าหน้าที่', 'user' => 'ผู้ใช้'];
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $role_colors[$user['role']]; ?>">
                                    <?php echo $role_text[$user['role']]; ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="user-manager.php" class="text-teal-600 hover:text-teal-700 font-medium">
                    ดูผู้ใช้งานทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
    </div>

    <?php include 'admin-layout/footer.php'; ?>
</main>

</body>
</html>
