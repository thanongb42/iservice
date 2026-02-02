<?php
/**
 * Admin Layout - Sidebar
 * เมนูด้านซ้ายแบบ responsive
 *
 * ตัวแปรที่ต้องกำหนดก่อน include:
 * - $current_page: หน้าปัจจุบัน (dashboard, users, departments, requests, services, learning, news, menu)
 * - $user: ข้อมูลผู้ใช้ ['full_name', 'email', 'username']
 * - $pending_requests: จำนวนคำขอที่รอดำเนินการ (optional, default: 0)
 */

$current_page = $current_page ?? 'dashboard';
$pending_requests = $pending_requests ?? 0;

// Menu items configuration
$menu_items = [
    [
        'id' => 'dashboard',
        'icon' => 'fa-home',
        'label' => 'แดชบอร์ด',
        'url' => 'admin_dashboard.php',
        'badge' => null
    ],
    [
        'id' => 'users',
        'icon' => 'fa-users',
        'label' => 'จัดการผู้ใช้งาน',
        'url' => 'user-manager.php',
        'badge' => null
    ],
    [
        'id' => 'departments',
        'icon' => 'fa-sitemap',
        'label' => 'จัดการหน่วยงาน',
        'url' => 'departments.php',
        'badge' => null
    ],
    [
        'id' => 'requests',
        'icon' => 'fa-tasks',
        'label' => 'คำขอบริการ',
        'url' => 'service_requests.php',
        'badge' => $pending_requests > 0 ? $pending_requests : null
    ],
    [
        'id' => 'services',
        'icon' => 'fa-concierge-bell',
        'label' => 'บริการของเรา',
        'url' => 'my_service.php',
        'badge' => null
    ],
    [
        'id' => 'learning',
        'icon' => 'fa-book-open',
        'label' => 'ศูนย์การเรียนรู้',
        'url' => 'learning_resources.php',
        'badge' => null
    ],
    [
        'id' => 'news',
        'icon' => 'fa-newspaper',
        'label' => 'ข่าวสารเทคโนโลยี',
        'url' => 'tech_news.php',
        'badge' => null
    ],
    [
        'id' => 'menu',
        'icon' => 'fa-bars',
        'label' => 'จัดการเมนู',
        'url' => 'nav_menu.php',
        'badge' => null
    ],
    [
        'id' => 'reports',
        'icon' => 'fa-chart-bar',
        'label' => 'รายงาน',
        'url' => 'admin_report.php',
        'badge' => null
    ],
    [
        'id' => 'system_settings',
        'icon' => 'fa-cog',
        'label' => 'ตั้งค่าระบบ',
        'url' => 'system_setting.php',
        'badge' => null
    ]
];
?>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-screen bg-gradient-to-b from-teal-800 to-teal-700 text-white sidebar-expanded sidebar-transition z-50 shadow-2xl sidebar-mobile">
    <div class="flex flex-col h-full">
        <!-- Sidebar Header -->
        <div class="sidebar-header flex items-center justify-between p-3 border-b border-teal-600">
            <div class="flex items-center space-x-3">
                <div class="w-10 h-10 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-headset text-lg"></i>
                </div>
                <div id="sidebarLogo" class="sidebar-transition">
                    <h1 class="text-lg font-bold">iService</h1>
                    <p class="text-xs text-teal-200">ระบบบริการดิจิทัล</p>
                </div>
            </div>
            <button id="sidebarToggle" onclick="toggleMobileSidebar()" class="lg:hidden text-white hover:bg-teal-600 p-2 rounded">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- User Info -->
        <div class="sidebar-user p-3 border-b border-teal-600">
            <div class="flex items-center space-x-3">
                <div class="user-avatar w-10 h-10 bg-teal-600 rounded-full flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-user text-base"></i>
                </div>
                <div id="userInfo" class="sidebar-transition overflow-hidden">
                    <p class="font-semibold truncate text-sm"><?php echo htmlspecialchars($user['full_name'] ?? 'Admin'); ?></p>
                    <p class="text-xs text-teal-200 truncate"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                </div>
            </div>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto p-2">
            <div class="space-y-1">
                <?php foreach ($menu_items as $item): ?>
                <a href="<?php echo $item['url']; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-lg <?php echo $current_page === $item['id'] ? 'bg-teal-600' : 'hover:bg-teal-600'; ?> transition sidebar-item group">
                    <i class="fas <?php echo $item['icon']; ?> w-5 text-center text-lg"></i>
                    <span class="menu-text sidebar-transition text-sm"><?php echo $item['label']; ?></span>
                    <?php if ($item['badge']): ?>
                    <span class="bg-red-500 text-white text-xs px-2 py-0.5 rounded-full ml-auto"><?php echo $item['badge']; ?></span>
                    <?php endif; ?>
                    <span class="sidebar-tooltip"><?php echo $item['label']; ?></span>
                </a>
                <?php endforeach; ?>
            </div>

            <div class="nav-section pt-2 mt-2 border-t border-teal-600 space-y-1">
                <!-- Back to Site -->
                <a href="../index.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-teal-600 transition sidebar-item group">
                    <i class="fas fa-external-link-alt w-5 text-center text-lg"></i>
                    <span class="menu-text sidebar-transition text-sm">กลับหน้าเว็บไซต์</span>
                    <span class="sidebar-tooltip">กลับหน้าเว็บไซต์</span>
                </a>

                <!-- Logout -->
                <a href="../logout.php" class="flex items-center space-x-3 px-3 py-2 rounded-lg hover:bg-red-600 transition sidebar-item group">
                    <i class="fas fa-sign-out-alt w-5 text-center text-lg"></i>
                    <span class="menu-text sidebar-transition text-sm">ออกจากระบบ</span>
                    <span class="sidebar-tooltip">ออกจากระบบ</span>
                </a>
            </div>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer p-2 border-t border-teal-600">
            <button id="collapseBtn" onclick="toggleSidebar()" class="w-full flex items-center justify-center space-x-1 p-1 rounded-md hover:bg-teal-600 transition">
                <i class="fas fa-chevron-left text-xs" id="collapseIcon" style="line-height:1;"></i>
                <span id="collapseText" class="sidebar-transition text-xs leading-none">ย่อเมนู</span>
            </button>
        </div>
    </div>
</aside>
