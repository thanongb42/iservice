<?php
/**
 * Admin Layout - Top Navbar
 * แถบนำทางด้านบน
 *
 * ตัวแปรที่ต้องกำหนดก่อน include:
 * - $page_title: ชื่อหน้าปัจจุบัน (สำหรับ breadcrumb)
 * - $user: ข้อมูลผู้ใช้ ['username']
 * - $pending_requests: จำนวนคำขอที่รอดำเนินการ (optional, default: 0)
 * - $breadcrumb: array ของ breadcrumb items (optional)
 *   เช่น [['label' => 'หน้าหลัก', 'url' => '#', 'icon' => 'fa-home'], ['label' => 'แดชบอร์ด']]
 */

$pending_requests = $pending_requests ?? 0;
$breadcrumb = $breadcrumb ?? [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => $page_title ?? 'แดชบอร์ด']
];

// Get current user's profile image from session or database
$current_user_profile_image = $_SESSION['profile_image'] ?? null;
if (!$current_user_profile_image && isset($conn) && isset($_SESSION['user_id'])) {
    $profile_query = $conn->query("SELECT profile_image FROM users WHERE user_id = " . intval($_SESSION['user_id']));
    if ($profile_query && $profile_row = $profile_query->fetch_assoc()) {
        $current_user_profile_image = $profile_row['profile_image'];
        $_SESSION['profile_image'] = $current_user_profile_image; // Cache in session
    }
}
?>

<!-- Main Content Wrapper Start -->
<div id="mainContent" class="main-content-transition ml-0 lg:ml-[280px] min-h-screen">
    <!-- Top Navbar -->
    <nav class="bg-white shadow-md sticky top-0 z-30">
        <div class="flex items-center justify-between px-4 py-3">
            <!-- Mobile Menu Button -->
            <button onclick="toggleMobileSidebar()" class="lg:hidden text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Desktop Collapse Button -->
            <button onclick="toggleSidebar()" class="hidden lg:flex items-center space-x-2 text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100">
                <i class="fas fa-bars"></i>
            </button>

            <!-- Breadcrumb -->
            <div class="flex-1 hidden md:block">
                <nav class="text-gray-600 text-sm">
                    <?php foreach ($breadcrumb as $index => $item): ?>
                        <?php if ($index > 0): ?>
                            <span class="mx-2">/</span>
                        <?php endif; ?>

                        <?php if ($index === count($breadcrumb) - 1): ?>
                            <span class="font-medium text-gray-800">
                                <?php if (isset($item['icon'])): ?>
                                    <i class="fas <?php echo $item['icon']; ?> mr-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($item['label']); ?>
                            </span>
                        <?php else: ?>
                            <?php if (isset($item['url'])): ?>
                                <a href="<?php echo $item['url']; ?>" class="text-green-600 hover:text-teal-700">
                                    <?php if (isset($item['icon'])): ?>
                                        <i class="fas <?php echo $item['icon']; ?> mr-1"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </a>
                            <?php else: ?>
                                <span class="text-green-600">
                                    <?php if (isset($item['icon'])): ?>
                                        <i class="fas <?php echo $item['icon']; ?> mr-1"></i>
                                    <?php endif; ?>
                                    <?php echo htmlspecialchars($item['label']); ?>
                                </span>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
            </div>

            <!-- Right Menu -->
            <div class="flex items-center space-x-4">
                <!-- Notifications -->
                <button class="relative text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100" title="การแจ้งเตือน">
                    <i class="fas fa-bell text-xl"></i>
                    <?php if ($pending_requests > 0): ?>
                    <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center"><?php echo $pending_requests; ?></span>
                    <?php endif; ?>
                </button>

                <!-- User Dropdown -->
                <div class="relative">
                    <button onclick="toggleUserDropdown()" class="flex items-center space-x-2 text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100">
                        <?php if (!empty($current_user_profile_image) && file_exists('../' . $current_user_profile_image)): ?>
                            <img src="../<?php echo htmlspecialchars($current_user_profile_image); ?>"
                                 alt="<?php echo htmlspecialchars($user['username'] ?? 'Admin'); ?>"
                                 class="w-8 h-8 rounded-full object-cover">
                        <?php else: ?>
                            <div class="w-8 h-8 bg-gray-200 rounded-full flex items-center justify-center text-gray-600 font-medium text-sm">
                                <?php echo strtoupper(substr($user['username'] ?? 'A', 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                        <span class="hidden md:block font-medium"><?php echo htmlspecialchars($user['username'] ?? 'Admin'); ?></span>
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <div id="userDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-2 z-50">
                        <a href="user_profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-user mr-2"></i>โปรไฟล์
                        </a>
                        <a href="system_setting.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            <i class="fas fa-cog mr-2"></i>ตั้งค่า
                        </a>
                        <hr class="my-2">
                        <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content Area Start -->
    <main class="p-4 md:p-6 lg:p-8">
