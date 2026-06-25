<?php
/**
 * Admin Layout - Sidebar
 * เมนูด้านซ้ายแบบ responsive - Clean Minimal Style
 * Role-based menu visibility
 */

$current_page = $current_page ?? 'dashboard';

// Auto-fetch pending requests count for badge
$pending_requests = 0;
if (isset($conn)) {
    $pr_query = $conn->query("SELECT COUNT(*) as cnt FROM service_requests WHERE status = 'pending'");
    if ($pr_query) {
        $pending_requests = intval($pr_query->fetch_assoc()['cnt']);
    }
}

// Count my active tasks (task_assignments + internal_jobs) for current user
$my_tasks_count = 0;
if (isset($conn) && isset($_SESSION['user_id'])) {
    // task_assignments: งานตามคำร้อง
    $mt_stmt = $conn->prepare("
        SELECT COUNT(*) as cnt FROM task_assignments
        WHERE assigned_to = ? AND status IN ('pending', 'accepted', 'in_progress')
    ");
    if ($mt_stmt) {
        $mt_stmt->bind_param('i', $_SESSION['user_id']);
        $mt_stmt->execute();
        $my_tasks_count += intval($mt_stmt->get_result()->fetch_assoc()['cnt']);
    }
    // internal_jobs: งานตามแผนงาน (via job_assignments for multi-assignee support)
    $ij_tbl = $conn->query("SHOW TABLES LIKE 'job_assignments'");
    if ($ij_tbl && $ij_tbl->num_rows > 0) {
        $ij_stmt = $conn->prepare("
            SELECT COUNT(DISTINCT ja.job_id) as cnt
            FROM job_assignments ja
            JOIN internal_jobs ij ON ja.job_id = ij.job_id
            WHERE ja.user_id = ? AND ij.status IN ('scheduled', 'in_progress')
        ");
        if ($ij_stmt) {
            $ij_stmt->bind_param('i', $_SESSION['user_id']);
            $ij_stmt->execute();
            $my_tasks_count += intval($ij_stmt->get_result()->fetch_assoc()['cnt']);
        }
    } else {
        // Fallback: use legacy assigned_to if job_assignments not yet created
        $ij_tbl2 = $conn->query("SHOW TABLES LIKE 'internal_jobs'");
        if ($ij_tbl2 && $ij_tbl2->num_rows > 0) {
            $ij_stmt2 = $conn->prepare("
                SELECT COUNT(*) as cnt FROM internal_jobs
                WHERE assigned_to = ? AND status IN ('scheduled', 'in_progress')
            ");
            if ($ij_stmt2) {
                $ij_stmt2->bind_param('i', $_SESSION['user_id']);
                $ij_stmt2->execute();
                $my_tasks_count += intval($ij_stmt2->get_result()->fetch_assoc()['cnt']);
            }
        }
    }
}

// Tier detection
$is_admin   = (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
$is_manager = $is_admin; // admin also counts as manager
if (!$is_manager && isset($_SESSION['user_id']) && isset($conn)) {
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

// Menu items configuration - 3-tier role-based
// Tier 1 (admin): full menu
// Tier 2 (manager role_code): dashboard + my_tasks + service_requests
// Tier 3 (staff): my_tasks only
$menu_groups = [];

if ($is_admin) {
    // ── Admin: full menu ────────────────────────────────────────────
    $menu_groups = [
        'main' => [
            'label' => '',
            'items' => [
                ['id' => 'dashboard', 'icon' => 'fa-home', 'label' => 'แดชบอร์ด', 'url' => 'admin_dashboard.php'],
            ]
        ],
        'operation' => [
            'label' => 'ปฏิบัติงาน',
            'items' => [
                ['id' => 'my_tasks', 'icon' => 'fa-tasks', 'label' => 'งานของฉัน', 'url' => 'my_tasks.php', 'badge' => $my_tasks_count > 0 ? $my_tasks_count : null],
                ['id' => 'staff_guide_group', 'icon' => 'fa-book-open', 'label' => 'คู่มือการใช้งาน', 'submenu' => [
                    ['id' => 'staff_guide',         'icon' => 'fa-list-ul',        'label' => 'งานตามคำร้อง',  'url' => 'staff_guide.php'],
                    ['id' => 'staff_guide_planned', 'icon' => 'fa-calendar-check', 'label' => 'งานตามแผนงาน', 'url' => 'staff_guide_planned.php'],
                ]],
            ]
        ],
        'manage' => [
            'label' => 'จัดการระบบ',
            'items' => [
                ['id' => 'user-manager', 'icon' => 'fa-users', 'label' => 'จัดการผู้ใช้งาน', 'url' => 'user-manager.php'],
                ['id' => 'departments', 'icon' => 'fa-sitemap', 'label' => 'จัดการหน่วยงาน', 'url' => 'departments.php'],
                ['id' => 'roles_manager', 'icon' => 'fa-user-tag', 'label' => 'จัดการบทบาท', 'url' => 'roles_manager.php'],
                ['id' => 'user_roles', 'icon' => 'fa-id-badge', 'label' => 'กำหนดบทบาทผู้ใช้', 'url' => 'user_roles.php'],
            ]
        ],
        'services' => [
            'label' => 'บริการ',
            'items' => [
                ['id' => 'create_job', 'icon' => 'fa-plus-circle', 'label' => 'สร้างงาน', 'url' => 'create_job.php'],
                ['id' => 'service_requests', 'icon' => 'fa-clipboard-list', 'label' => 'คำขอบริการ', 'url' => 'service_requests.php', 'badge' => $pending_requests > 0 ? $pending_requests : null],
                ['id' => 'my_service', 'icon' => 'fa-concierge-bell', 'label' => 'บริการของเรา', 'url' => 'my_service.php'],
                ['id' => 'internet_request_types', 'icon' => 'fa-wifi', 'label' => 'ประเภทคำขอ Internet', 'url' => 'internet_request_types.php'],
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
        'cdp' => [
            'label' => 'CDP',
            'items' => [
                ['id' => 'cdp_speaker_system', 'icon' => 'fa-broadcast-tower', 'label' => 'ระบบเสียงไร้สาย',  'url' => 'cdp_speaker_system.php'],
                ['id' => 'pm25_dashboard',      'icon' => 'fa-wind',            'label' => 'PM2.5 Dashboard',  'url' => 'pm25_dashboard.php'],
                ['id' => 'pm25_sensors',        'icon' => 'fa-satellite-dish',  'label' => 'จัดการสถานี PM2.5','url' => 'pm25_sensors.php'],
                ['id' => 'pm25_api_keys',       'icon' => 'fa-key',             'label' => 'API Keys PM2.5',   'url' => 'pm25_api_keys.php'],
                ...( ($_SESSION['username'] ?? '') === 'thanong' ? [
                    ['id' => 'pm25_postman_guide', 'icon' => 'fa-book-open', 'label' => 'คู่มือ Postman API', 'url' => 'pm25_postman_guide.php'],
                ] : []),
            ]
        ],
        'system' => [
            'label' => 'ระบบ',
            'items' => [
                ['id' => 'reports', 'icon' => 'fa-chart-bar', 'label' => 'รายงาน', 'url' => 'admin_report.php'],
                ['id' => 'visitor_stats', 'icon' => 'fa-chart-line', 'label' => 'สถิติผู้เข้าชม', 'url' => 'visitor_stats.php'],
                ['id' => 'form_test_runner', 'icon' => 'fa-flask', 'label' => 'ทดสอบฟอร์ม', 'url' => 'form_test_runner.php'],
                ['id' => 'system_setting', 'icon' => 'fa-cog', 'label' => 'ตั้งค่าระบบ', 'url' => 'system_setting.php'],
                ['id' => 'cron_schedules', 'icon' => 'fa-clock', 'label' => 'Cron Jobs', 'url' => 'cron_schedules.php'],
            ]
        ]
    ];
} elseif ($is_manager) {
    // ── Manager: dashboard + my_tasks + service_requests ───────────
    $menu_groups = [
        'main' => [
            'label' => '',
            'items' => [
                ['id' => 'dashboard', 'icon' => 'fa-home', 'label' => 'แดชบอร์ด', 'url' => 'admin_dashboard.php'],
            ]
        ],
        'operation' => [
            'label' => 'ปฏิบัติงาน',
            'items' => [
                ['id' => 'my_tasks',        'icon' => 'fa-tasks',          'label' => 'งานของฉัน',   'url' => 'my_tasks.php', 'badge' => $my_tasks_count > 0 ? $my_tasks_count : null],
                ['id' => 'create_job',      'icon' => 'fa-calendar-plus',  'label' => 'ปฏิทินงาน',  'url' => 'create_job.php'],
                ['id' => 'service_requests','icon' => 'fa-clipboard-list', 'label' => 'คำขอบริการ', 'url' => 'service_requests.php', 'badge' => $pending_requests > 0 ? $pending_requests : null],
                ['id' => 'staff_guide_group', 'icon' => 'fa-book-open', 'label' => 'คู่มือการใช้งาน', 'submenu' => [
                    ['id' => 'staff_guide',         'icon' => 'fa-list-ul',        'label' => 'งานตามคำร้อง',  'url' => 'staff_guide.php'],
                    ['id' => 'staff_guide_planned', 'icon' => 'fa-calendar-check', 'label' => 'งานตามแผนงาน', 'url' => 'staff_guide_planned.php'],
                ]],
            ]
        ],
    ];
} else {
    // ── Staff: my_tasks + guide ─────────────────────────────────────
    $menu_groups = [
        'operation' => [
            'label' => 'ปฏิบัติงาน',
            'items' => [
                ['id' => 'my_tasks', 'icon' => 'fa-tasks', 'label' => 'งานของฉัน', 'url' => 'my_tasks.php', 'badge' => $my_tasks_count > 0 ? $my_tasks_count : null],
                ['id' => 'staff_guide_group', 'icon' => 'fa-book-open', 'label' => 'คู่มือการใช้งาน', 'submenu' => [
                    ['id' => 'staff_guide',         'icon' => 'fa-list-ul',        'label' => 'งานตามคำร้อง',  'url' => 'staff_guide.php'],
                    ['id' => 'staff_guide_planned', 'icon' => 'fa-calendar-check', 'label' => 'งานตามแผนงาน', 'url' => 'staff_guide_planned.php'],
                ]],
            ]
        ]
    ];
}
?>

<style>
/* ── Green Teal Sidebar Theme ── */
#sidebar {
    background: linear-gradient(180deg, #064e3b 0%, #065f46 45%, #0f766e 100%);
    border-right: none;
    box-shadow: 2px 0 12px rgba(6,78,59,.35);
    transition: width 0.3s ease-in-out;
}

/* Menu item */
.sidebar-menu-item {
    display: flex;
    align-items: center;
    padding: 0.625rem 1rem;
    margin: 0.125rem 0.5rem;
    border-radius: 0.5rem;
    color: rgba(255,255,255,.78);
    font-size: 0.875rem;
    font-weight: 500;
    transition: all 0.15s ease;
    border-left: 3px solid transparent;
    text-decoration: none;
}

.sidebar-menu-item:hover {
    background: rgba(255,255,255,.12);
    color: #ffffff;
}

.sidebar-menu-item.active {
    background: rgba(255,255,255,.18);
    color: #ffffff;
    border-left-color: #2dd4bf;
    font-weight: 600;
}

.sidebar-menu-item i {
    width: 1.25rem;
    text-align: center;
    font-size: 1rem;
    color: rgba(255,255,255,.55);
    transition: color 0.15s ease;
}

.sidebar-menu-item:hover i,
.sidebar-menu-item.active i {
    color: #5eead4;
}

/* Section label */
.sidebar-section-label {
    font-size: 0.68rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.07em;
    color: rgba(255,255,255,.38);
    padding: 1rem 1rem 0.4rem 1.25rem;
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
    border-bottom: 1px solid rgba(255,255,255,.1);
}
.sidebar-logo-area h1 { color: #ffffff !important; }
.sidebar-logo-area p  { color: rgba(255,255,255,.55) !important; }

/* Divider */
#sidebar .border-t { border-color: rgba(255,255,255,.12) !important; }

/* Footer collapse button */
#sidebar .p-3 {
    border-top: 1px solid rgba(255,255,255,.1) !important;
}
#sidebar #collapseBtn {
    color: rgba(255,255,255,.5) !important;
}
#sidebar #collapseBtn:hover {
    background: rgba(255,255,255,.1) !important;
    color: #fff !important;
}
#sidebar #collapseText { color: rgba(255,255,255,.5) !important; }

/* Logout hover */
.sidebar-menu-item.hover\:hover\:bg-red-50:hover {
    background: rgba(239,68,68,.2) !important;
    color: #fca5a5 !important;
}

/* Submenu */
.sidebar-submenu-toggle {
    width: 100%;
    text-align: left;
    background: none;
    border: none;
    cursor: pointer;
}
.sidebar-submenu {
    display: none;
    overflow: hidden;
}
.sidebar-submenu.open {
    display: block;
}
.sidebar-subitem {
    padding-left: 2.75rem !important;
    font-size: 0.825rem !important;
}
.sidebar-submenu-arrow {
    font-size: 0.7rem;
    color: rgba(255,255,255,.4);
    transition: transform 0.2s ease;
    flex-shrink: 0;
    margin-left: auto;
}
.sidebar-submenu-arrow.open {
    transform: rotate(90deg);
}
#sidebar.sidebar-collapsed .sidebar-submenu { display: none !important; }
#sidebar.sidebar-collapsed .sidebar-submenu-arrow { display: none; }

/* Collapsed state */
#sidebar.sidebar-collapsed .sidebar-menu-item {
    justify-content: center;
    padding: 0.75rem;
    margin: 0.25rem;
}
#sidebar.sidebar-collapsed .sidebar-menu-item span:not(.sidebar-badge):not(.sidebar-tooltip) { display: none; }
#sidebar.sidebar-collapsed .sidebar-section-label { display: none; }
#sidebar.sidebar-collapsed .sidebar-logo-text     { display: none; }
#sidebar.sidebar-collapsed .sidebar-badge {
    position: absolute; top: 0.25rem; right: 0.25rem;
    padding: 0.1rem 0.35rem; font-size: 0.6rem;
}
#sidebar.sidebar-collapsed .sidebar-menu-item { position: relative; }
</style>

<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 h-screen sidebar-expanded sidebar-transition z-50 shadow-sm sidebar-mobile">
    <div class="flex flex-col h-full">
        <!-- Logo Area -->
        <div class="sidebar-logo-area flex items-center">
            <a href="admin_dashboard.php" class="flex items-center space-x-3 hover:opacity-80 transition-opacity" style="text-decoration:none;">
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
            </a>
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
                    <?php if (!empty($item['submenu'])): ?>
                        <?php
                            $submenu_active = false;
                            foreach ($item['submenu'] as $sub) {
                                if ($current_page === $sub['id']) { $submenu_active = true; break; }
                            }
                        ?>
                        <div>
                            <button onclick="toggleSubmenu('<?php echo $item['id']; ?>')"
                                    class="sidebar-menu-item sidebar-submenu-toggle <?php echo $submenu_active ? 'active' : ''; ?>">
                                <i class="fas <?php echo $item['icon']; ?> mr-3"></i>
                                <span class="menu-text sidebar-transition"><?php echo $item['label']; ?></span>
                                <i class="fas fa-chevron-right sidebar-submenu-arrow <?php echo $submenu_active ? 'open' : ''; ?>"
                                   id="arrow-<?php echo $item['id']; ?>"></i>
                            </button>
                            <div class="sidebar-submenu <?php echo $submenu_active ? 'open' : ''; ?>"
                                 id="submenu-<?php echo $item['id']; ?>">
                                <?php foreach ($item['submenu'] as $sub): ?>
                                    <a href="<?php echo $sub['url']; ?>"
                                       class="sidebar-menu-item sidebar-subitem <?php echo $current_page === $sub['id'] ? 'active' : ''; ?>">
                                        <i class="fas <?php echo $sub['icon']; ?> mr-3"></i>
                                        <span class="menu-text sidebar-transition"><?php echo $sub['label']; ?></span>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="<?php echo $item['url']; ?>"
                           class="sidebar-menu-item sidebar-item <?php echo $current_page === $item['id'] ? 'active' : ''; ?>">
                            <i class="fas <?php echo $item['icon']; ?> mr-3"></i>
                            <span class="menu-text sidebar-transition"><?php echo $item['label']; ?></span>
                            <?php if (!empty($item['badge'])): ?>
                                <span class="sidebar-badge"><?php echo $item['badge']; ?></span>
                            <?php endif; ?>
                            <span class="sidebar-tooltip"><?php echo $item['label']; ?></span>
                        </a>
                    <?php endif; ?>
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

<script>
function toggleSubmenu(id) {
    const submenu = document.getElementById('submenu-' + id);
    const arrow   = document.getElementById('arrow-' + id);
    if (submenu) {
        submenu.classList.toggle('open');
        if (arrow) arrow.classList.toggle('open');
    }
}
</script>
