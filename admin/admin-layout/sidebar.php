<?php
/**
 * Admin Layout - Sidebar
 * เมนูด้านซ้ายแบบ responsive - Clean Minimal Style
 * Role-based menu visibility
 */

$current_page = $current_page ?? 'dashboard';
$pending_requests = $pending_requests ?? 0;

// Check if user is admin/manager (has 'manager' or 'all' role)
$is_manager = false;
if (isset($_SESSION['user_id']) && isset($conn)) {
    $check_role = $conn->prepare("
        SELECT COUNT(*) as cnt FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND r.role_code IN ('manager', 'all')
        AND ur.is_active = 1 AND r.is_active = 1
    ");
    $check_role->bind_param('i', $_SESSION['user_id']);
    $check_role->execute();
    $role_result = $check_role->get_result()->fetch_assoc();
    $is_manager = $role_result['cnt'] > 0;
}

// Fetch system settings for sidebar
$system_settings = [];
if (isset($conn)) {
    $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($settings_query) {
        while ($row = $settings_query->fetch_assoc()) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

$sidebar_app_name = !empty($system_settings['app_name']) ? $system_settings['app_name'] : 'iService';
$sidebar_org_name = !empty($system_settings['organization_name']) ? $system_settings['organization_name'] : 'ระบบบริการดิจิทัล';
$sidebar_logo = !empty($system_settings['logo_image']) && file_exists('../' . $system_settings['logo_image']) ? $system_settings['logo_image'] : null;

// Menu items configuration - grouped
// Show admin menu only for managers/all roles
// Show my_tasks for all staff
$menu_groups = [];

// For managers - show all menus
if ($is_manager) {
    $menu_groups = [
        'main' => [
            'label' => '',
            'items' => [
                ['id' => 'dashboard', 'icon' => 'fa-home', 'label' => 'แดชบอร์ด', 'url' => 'admin_dashboard.php'],
                ['id' => 'user-manager', 'icon' => 'fa-users', 'label' => 'จัดการผู้ใช้งาน', 'url' => 'user-manager.php'],
                ['id' => 'departments', 'icon' => 'fa-sitemap', 'label' => 'จัดการหน่วยงาน', 'url' => 'departments.php'],
                ['id' => 'roles_manager', 'icon' => 'fa-user-tag', 'label' => 'จัดการบทบาท', 'url' => 'roles_manager.php'],
                ['id' => 'user_roles', 'icon' => 'fa-id-badge', 'label' => 'กำหนดบทบาทผู้ใช้', 'url' => 'user_roles.php'],
            ]
        ],
        'services' => [
            'label' => 'บริการ',
            'items' => [
                ['id' => 'service_requests', 'icon' => 'fa-clipboard-list', 'label' => 'คำขอบริการ', 'url' => 'service_requests.php', 'badge' => $pending_requests > 0 ? $pending_requests : null],
                ['id' => 'my_service', 'icon' => 'fa-concierge-bell', 'label' => 'บริการของเรา', 'url' => 'my_service.php'],
            ]
        ],
        'content' => [
            'label' => 'เนื้อหา',
            'items' => [
                ['id' => 'learning_resources', 'icon' => 'fa-book-open', 'label' => 'ศูนย์การเรียนรู้', 'url' => 'learning_resources.php'],
                ['id' => 'tech_news', 'icon' => 'fa-newspaper', 'label' => 'ข่าวสารเทคโนโลยี', 'url' => 'tech_news.php'],
                ['id' => 'nav_menu', 'icon' => 'fa-bars', 'label' => 'จัดการเมนู', 'url' => 'nav_menu.php'],
                ['id' => 'related_agencies', 'icon' => 'fa-building', 'label' => 'หน่วยงานที่เกี่ยวข้อง', 'url' => 'related_agencies.php'],
            ]
        ],
        'system' => [
            'label' => 'ระบบ',
            'items' => [
                ['id' => 'reports', 'icon' => 'fa-chart-bar', 'label' => 'รายงาน', 'url' => 'admin_report.php'],
                ['id' => 'system_setting', 'icon' => 'fa-cog', 'label' => 'ตั้งค่าระบบ', 'url' => 'system_setting.php'],
            ]
        ]
    ];
} else {
    // For non-managers - show only my_tasks
    $menu_groups = [
        'main' => [
            'label' => '',
            'items' => [
                ['id' => 'my_tasks', 'icon' => 'fa-tasks', 'label' => 'งานของฉัน', 'url' => 'my_tasks.php'],
            ]
        ]
    ];
}
?>

<style>
/* Clean Minimal Sidebar Theme */
:root {
    --sidebar-bg: #ffffff;
    --sidebar-border: #e5e7eb;
    --sidebar-text: #1f2937;
    --sidebar-text-muted: #6b7280;
    --sidebar-hover: #f3f4f6;
    --sidebar-active-bg: #ecfdf5;
    --sidebar-active-text: #009933;
    --sidebar-active-border: #009933;
    --sidebar-section-text: #9ca3af;
}

#sidebar {
    background: var(--sidebar-bg);
    border-right: 1px solid var(--sidebar-border);
}

/* Menu item styles */
.sidebar-menu-item {
    display: flex;
    align-items: center;
    padding: 0.625rem 1rem;
    margin: 0.125rem 0.5rem;
    border-radius: 0.5rem;
    color: var(--sidebar-text);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s ease;
    border-left: 3px solid transparent;
}

.sidebar-menu-item:hover {
    background: var(--sidebar-hover);
    color: var(--sidebar-text);
}

.sidebar-menu-item.active {
    background: var(--sidebar-active-bg);
    color: var(--sidebar-active-text);
    border-left-color: var(--sidebar-active-border);
}

.sidebar-menu-item i {
    width: 1.25rem;
    text-align: center;
    font-size: 1rem;
    color: var(--sidebar-text-muted);
    transition: color 0.15s ease;
}

.sidebar-menu-item:hover i,
.sidebar-menu-item.active i {
    color: var(--sidebar-active-text);
}

/* Section label */
.sidebar-section-label {
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: var(--sidebar-section-text);
    padding: 1rem 1rem 0.5rem 1.25rem;
}

/* Badge */
.sidebar-badge {
    background: #ef4444;
    color: white;
    font-size: 0.65rem;
    font-weight: 600;
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    margin-left: auto;
}

/* Logo area */
.sidebar-logo-area {
    padding: 1.25rem 1rem;
    border-bottom: 1px solid var(--sidebar-border);
}

/* Collapsed state */
#sidebar.sidebar-collapsed .sidebar-menu-item {
    justify-content: center;
    padding: 0.75rem;
    margin: 0.25rem;
}

#sidebar.sidebar-collapsed .sidebar-menu-item span:not(.sidebar-badge):not(.sidebar-tooltip) {
    display: none;
}

#sidebar.sidebar-collapsed .sidebar-section-label {
    display: none;
}

#sidebar.sidebar-collapsed .sidebar-logo-text {
    display: none;
}

#sidebar.sidebar-collapsed .sidebar-badge {
    position: absolute;
    top: 0.25rem;
    right: 0.25rem;
    padding: 0.1rem 0.35rem;
    font-size: 0.6rem;
}

#sidebar.sidebar-collapsed .sidebar-menu-item {
    position: relative;
}
</style>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-screen sidebar-expanded sidebar-transition z-50 shadow-sm sidebar-mobile">
    <div class="flex flex-col h-full">
        <!-- Logo Area -->
        <div class="sidebar-logo-area flex items-center">
            <div class="flex items-center space-x-3">
                <?php if ($sidebar_logo): ?>
                    <img src="../<?php echo htmlspecialchars($sidebar_logo); ?>" alt="Logo" class="w-9 h-9 object-contain rounded-lg flex-shrink-0">
                <?php else: ?>
                    <div class="w-9 h-9 bg-gradient-to-br from-green-500 to-green-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-headset text-white text-sm"></i>
                    </div>
                <?php endif; ?>
                <div class="sidebar-logo-text sidebar-transition">
                    <h1 class="text-base font-bold text-gray-800 truncate" style="max-width: 160px;"><?php echo htmlspecialchars($sidebar_app_name); ?></h1>
                    <p class="text-xs text-gray-400 truncate" style="max-width: 160px;"><?php echo htmlspecialchars($sidebar_org_name); ?></p>
                </div>
            </div>
            <button id="sidebarToggle" onclick="toggleMobileSidebar()" class="lg:hidden ml-auto text-gray-400 hover:text-gray-600 p-2 rounded-lg hover:bg-gray-100">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Navigation Menu -->
        <nav class="flex-1 overflow-y-auto py-4">
            <?php foreach ($menu_groups as $groupKey => $group): ?>
                <?php if (!empty($group['label'])): ?>
                    <div class="sidebar-section-label"><?php echo $group['label']; ?></div>
                <?php endif; ?>

                <?php foreach ($group['items'] as $item): ?>
                    <a href="<?php echo $item['url']; ?>"
                       class="sidebar-menu-item sidebar-item <?php echo $current_page === $item['id'] ? 'active' : ''; ?>">
                        <i class="fas <?php echo $item['icon']; ?> mr-3"></i>
                        <span class="menu-text sidebar-transition"><?php echo $item['label']; ?></span>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="sidebar-badge"><?php echo $item['badge']; ?></span>
                        <?php endif; ?>
                        <span class="sidebar-tooltip"><?php echo $item['label']; ?></span>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>

            <!-- Divider -->
            <div class="my-4 mx-4 border-t border-gray-200"></div>

            <!-- Back to Site -->
            <a href="../index.php" class="sidebar-menu-item sidebar-item">
                <i class="fas fa-external-link-alt mr-3"></i>
                <span class="menu-text sidebar-transition">กลับหน้าเว็บไซต์</span>
                <span class="sidebar-tooltip">กลับหน้าเว็บไซต์</span>
            </a>

            <!-- Logout -->
            <a href="../logout.php" class="sidebar-menu-item sidebar-item hover:!bg-red-50 hover:!text-red-600 group">
                <i class="fas fa-sign-out-alt mr-3 group-hover:!text-red-500"></i>
                <span class="menu-text sidebar-transition">ออกจากระบบ</span>
                <span class="sidebar-tooltip">ออกจากระบบ</span>
            </a>
        </nav>

        <!-- Sidebar Footer - Collapse Button -->
        <div class="p-3 border-t border-gray-200">
            <button id="collapseBtn" onclick="toggleSidebar()" class="w-full flex items-center justify-center space-x-2 p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition">
                <i class="fas fa-chevron-left text-xs" id="collapseIcon"></i>
                <span id="collapseText" class="sidebar-transition text-xs font-medium">ย่อเมนู</span>
            </button>
        </div>
    </div>
</aside>
