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

// Build $user from session if not already defined by the parent page
if (!isset($user) || !is_array($user)) {
    $user = [
        'username' => $_SESSION['username'] ?? 'User',
        'email' => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User',
        'first_name' => $_SESSION['first_name'] ?? 'User'
    ];
}

$pending_requests = $pending_requests ?? 0;

// Fetch recent service requests for notification dropdown (latest 10)
$recent_requests = [];
if (isset($conn)) {
    $notif_query = $conn->query("
        SELECT sr.request_id, sr.service_name, sr.status, sr.created_at,
               COALESCE(u.first_name, u.username) as requester_name
        FROM service_requests sr
        LEFT JOIN users u ON sr.user_id = u.user_id
        ORDER BY sr.created_at DESC
        LIMIT 10
    ");
    if ($notif_query) {
        while ($row = $notif_query->fetch_assoc()) {
            $recent_requests[] = $row;
        }
    }
}

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
                <div class="relative">
                    <button onclick="toggleNotifDropdown()" class="relative text-gray-600 hover:text-gray-900 p-2 rounded-lg hover:bg-gray-100" title="การแจ้งเตือน">
                        <i class="fas fa-bell text-xl"></i>
                        <?php if ($pending_requests > 0): ?>
                        <span class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center animate-pulse"><?php echo $pending_requests; ?></span>
                        <?php endif; ?>
                    </button>

                    <!-- Notification Dropdown -->
                    <div id="notifDropdown" class="hidden absolute right-0 mt-2 w-96 bg-white rounded-xl shadow-2xl border border-gray-200 z-50 overflow-hidden">
                        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
                            <h4 class="font-semibold text-gray-800 text-sm"><i class="fas fa-bell mr-1"></i> การแจ้งเตือน</h4>
                            <?php if ($pending_requests > 0): ?>
                            <span class="bg-red-500 text-white text-xs font-bold px-2 py-0.5 rounded-full"><?php echo $pending_requests; ?> รอดำเนินการ</span>
                            <?php endif; ?>
                        </div>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (empty($recent_requests)): ?>
                                <div class="px-4 py-8 text-center text-gray-400">
                                    <i class="fas fa-inbox text-3xl mb-2"></i>
                                    <p class="text-sm">ยังไม่มีการแจ้งเตือน</p>
                                </div>
                            <?php else: ?>
                                <?php
                                $status_config = [
                                    'pending'     => ['label' => 'รอดำเนินการ',    'color' => '#EF4444', 'bg' => '#FEF2F2', 'icon' => 'fa-clock'],
                                    'accepted'    => ['label' => 'รับงานแล้ว',     'color' => '#F59E0B', 'bg' => '#FFFBEB', 'icon' => 'fa-hand-paper'],
                                    'in_progress' => ['label' => 'กำลังดำเนินการ', 'color' => '#F59E0B', 'bg' => '#FFFBEB', 'icon' => 'fa-spinner'],
                                    'completed'   => ['label' => 'เสร็จสิ้น',      'color' => '#10B981', 'bg' => '#ECFDF5', 'icon' => 'fa-check-circle'],
                                    'cancelled'   => ['label' => 'ยกเลิก',         'color' => '#6B7280', 'bg' => '#F3F4F6', 'icon' => 'fa-times-circle'],
                                ];
                                foreach ($recent_requests as $req):
                                    $st = $req['status'];
                                    $cfg = $status_config[$st] ?? ['label' => $st, 'color' => '#6B7280', 'bg' => '#F3F4F6', 'icon' => 'fa-question-circle'];
                                    $time_ago = '';
                                    $diff = time() - strtotime($req['created_at']);
                                    if ($diff < 60) $time_ago = 'เมื่อสักครู่';
                                    elseif ($diff < 3600) $time_ago = floor($diff / 60) . ' นาทีที่แล้ว';
                                    elseif ($diff < 86400) $time_ago = floor($diff / 3600) . ' ชั่วโมงที่แล้ว';
                                    elseif ($diff < 604800) $time_ago = floor($diff / 86400) . ' วันที่แล้ว';
                                    else $time_ago = date('d/m/Y', strtotime($req['created_at']));
                                ?>
                                <a href="request_detail.php?id=<?php echo $req['request_id']; ?>" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-100 transition-colors" style="text-decoration:none;">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center mt-0.5" style="background: <?php echo $cfg['bg']; ?>;">
                                            <i class="fas <?php echo $cfg['icon']; ?>" style="color: <?php echo $cfg['color']; ?>; font-size: 0.85rem;"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm font-medium text-gray-800 truncate">#<?php echo $req['request_id']; ?> <?php echo htmlspecialchars($req['service_name'] ?? ''); ?></p>
                                            <p class="text-xs text-gray-500 mt-0.5">โดย <?php echo htmlspecialchars($req['requester_name'] ?? '-'); ?></p>
                                            <div class="flex items-center justify-between mt-1">
                                                <span class="inline-flex items-center text-xs font-semibold px-2 py-0.5 rounded-full" style="background: <?php echo $cfg['bg']; ?>; color: <?php echo $cfg['color']; ?>;">
                                                    <?php echo $cfg['label']; ?>
                                                </span>
                                                <span class="text-xs text-gray-400"><?php echo $time_ago; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <a href="service_requests.php" class="block px-4 py-3 text-center text-sm font-semibold text-green-600 hover:bg-green-50 border-t border-gray-200 transition-colors" style="text-decoration:none;">
                            ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>

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

    <script>
    // Toggle notification dropdown
    function toggleNotifDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown) userDropdown.classList.add('hidden');
        if (dropdown) dropdown.classList.toggle('hidden');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(event) {
        const notifDropdown = document.getElementById('notifDropdown');
        const notifBtn = event.target.closest('button[onclick="toggleNotifDropdown()"]');
        if (notifDropdown && !notifBtn && !notifDropdown.contains(event.target)) {
            notifDropdown.classList.add('hidden');
        }
    });
    </script>

    <!-- Main Content Area Start -->
    <main class="p-4 md:p-6 lg:p-8">
