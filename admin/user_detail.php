<?php
/**
 * User Detail Page
 * Beautiful profile view for admin to see user details
 */

require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($user_id)) {
    header('Location: user-manager.php');
    exit;
}

// Get user detail from view
$stmt = $conn->prepare("SELECT * FROM v_users_full WHERE user_id = ?");
if (!$stmt) {
    header('Location: user-manager.php?error=db');
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: user-manager.php?error=notfound');
    exit;
}

// Get user roles (defensive - table might not exist)
$user_roles = [];
$roles_query = $conn->prepare("
    SELECT ur.*, r.role_name, r.role_code, r.role_icon, r.role_color
    FROM user_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.user_id = ? AND ur.is_active = 1
    ORDER BY ur.is_primary DESC, r.display_order ASC
");
if ($roles_query) {
    $roles_query->bind_param('i', $user_id);
    $roles_query->execute();
    $roles_result = $roles_query->get_result();
    while ($row = $roles_result->fetch_assoc()) {
        $user_roles[] = $row;
    }
}

// Get service requests by this user (use view for compatibility)
$service_requests = [];
$requests_query = $conn->prepare("
    SELECT request_id, request_code, service_code, service_name,
           status, priority, created_at
    FROM v_service_requests_full
    WHERE user_id = ?
    ORDER BY created_at DESC
    LIMIT 10
");
if ($requests_query) {
    $requests_query->bind_param('i', $user_id);
    $requests_query->execute();
    $req_result = $requests_query->get_result();
    while ($row = $req_result->fetch_assoc()) {
        $service_requests[] = $row;
    }
}

// Get task assignments for this user (use view for compatibility)
$task_assignments = [];
$tasks_query = $conn->prepare("
    SELECT ta.*, sr.request_code, sr.service_name,
           u_by.first_name as by_first, u_by.last_name as by_last,
           r.role_name
    FROM task_assignments ta
    LEFT JOIN v_service_requests_full sr ON ta.request_id = sr.request_id
    LEFT JOIN users u_by ON ta.assigned_by = u_by.user_id
    LEFT JOIN roles r ON ta.assigned_as_role = r.role_id
    WHERE ta.assigned_to = ?
    ORDER BY ta.created_at DESC
    LIMIT 10
");
if ($tasks_query) {
    $tasks_query->bind_param('i', $user_id);
    $tasks_query->execute();
    $tasks_result = $tasks_query->get_result();
    while ($row = $tasks_result->fetch_assoc()) {
        $task_assignments[] = $row;
    }
}

// Thai labels
$role_labels = ['admin' => 'ผู้ดูแลระบบ', 'staff' => 'เจ้าหน้าที่', 'user' => 'ผู้ใช้ทั่วไป'];
$status_labels = ['active' => 'ใช้งาน', 'inactive' => 'ไม่ใช้งาน', 'suspended' => 'ระงับ'];
$req_status_labels = ['pending' => 'รอดำเนินการ', 'in_progress' => 'กำลังดำเนินการ', 'completed' => 'เสร็จสิ้น', 'rejected' => 'ปฏิเสธ', 'cancelled' => 'ยกเลิก'];
$task_status_labels = ['pending' => 'รอรับงาน', 'accepted' => 'รับงานแล้ว', 'in_progress' => 'กำลังดำเนินการ', 'completed' => 'เสร็จสิ้น', 'cancelled' => 'ยกเลิก'];
$priority_labels = ['low' => 'ต่ำ', 'medium' => 'ปานกลาง', 'normal' => 'ปกติ', 'high' => 'สูง', 'urgent' => 'เร่งด่วน'];

// Format Thai date
function formatThaiDateUser($datetime, $showTime = true) {
    if (empty($datetime)) return '-';
    $timestamp = strtotime($datetime);
    $thai_months = [1 => 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    $day = date('j', $timestamp);
    $month = $thai_months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    $time = date('H:i', $timestamp);
    return $showTime ? "{$day} {$month} {$year} {$time} น." : "{$day} {$month} {$year}";
}

// Calculate days since joined
$joined_days = floor((time() - strtotime($user['created_at'])) / 86400);

$page_title = 'รายละเอียดผู้ใช้ - ' . htmlspecialchars($user['full_name']);
$current_page = 'user-manager';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'url' => 'index.php', 'icon' => 'fa-home'],
    ['label' => 'จัดการผู้ใช้งาน', 'url' => 'user-manager.php'],
    ['label' => htmlspecialchars($user['full_name'])]
];

include __DIR__ . '/admin-layout/header.php';
include __DIR__ . '/admin-layout/sidebar.php';
include __DIR__ . '/admin-layout/topbar.php';
?>

<style>
    .profile-header {
        background: linear-gradient(135deg, #059669 0%, #10b981 50%, #34d399 100%);
        border-radius: 16px;
        position: relative;
        overflow: hidden;
    }
    .profile-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 400px;
        height: 400px;
        background: rgba(255,255,255,0.05);
        border-radius: 50%;
    }
    .profile-header::after {
        content: '';
        position: absolute;
        bottom: -30%;
        left: -10%;
        width: 300px;
        height: 300px;
        background: rgba(255,255,255,0.03);
        border-radius: 50%;
    }
    .profile-avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        border: 4px solid rgba(255,255,255,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.5rem;
        font-weight: 700;
        background: rgba(255,255,255,0.2);
        color: white;
        backdrop-filter: blur(10px);
        overflow: hidden;
    }
    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .info-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        transition: box-shadow 0.2s;
    }
    .info-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .info-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f3f4f6;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .info-card-body {
        padding: 20px;
    }
    .info-row {
        display: flex;
        align-items: flex-start;
        padding: 10px 0;
        border-bottom: 1px solid #f9fafb;
    }
    .info-row:last-child {
        border-bottom: none;
    }
    .info-label {
        width: 140px;
        flex-shrink: 0;
        font-size: 0.8rem;
        font-weight: 500;
        color: #6b7280;
        padding-top: 2px;
    }
    .info-value {
        flex: 1;
        font-size: 0.875rem;
        color: #1f2937;
        font-weight: 500;
    }
    .stat-mini {
        text-align: center;
        padding: 16px;
        background: #f9fafb;
        border-radius: 10px;
        border: 1px solid #f3f4f6;
    }
    .stat-mini-value {
        font-size: 1.5rem;
        font-weight: 700;
        line-height: 1;
    }
    .stat-mini-label {
        font-size: 0.7rem;
        color: #6b7280;
        margin-top: 4px;
        font-weight: 500;
    }
    .role-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 14px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        font-size: 0.8rem;
        font-weight: 500;
    }
    .timeline-item {
        position: relative;
        padding-left: 28px;
        padding-bottom: 16px;
    }
    .timeline-item::before {
        content: '';
        position: absolute;
        left: 7px;
        top: 24px;
        bottom: 0;
        width: 2px;
        background: #e5e7eb;
    }
    .timeline-item:last-child::before {
        display: none;
    }
    .timeline-dot {
        position: absolute;
        left: 0;
        top: 4px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        border: 3px solid;
        background: white;
    }
    .badge-status {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 6px;
        font-size: 0.7rem;
        font-weight: 600;
    }
</style>

<div class="p-4 md:p-6">
    <!-- Breadcrumb -->
    <nav class="text-gray-500 text-sm mb-5 flex items-center gap-2">
        <a href="index.php" class="hover:text-green-600 transition"><i class="fas fa-home"></i></a>
        <i class="fas fa-chevron-right text-xs text-gray-300"></i>
        <a href="user-manager.php" class="hover:text-green-600 transition">จัดการผู้ใช้งาน</a>
        <i class="fas fa-chevron-right text-xs text-gray-300"></i>
        <span class="text-gray-800 font-medium"><?= htmlspecialchars($user['full_name']) ?></span>
    </nav>

    <!-- Profile Header -->
    <div class="profile-header p-6 md:p-8 mb-6">
        <div class="relative z-10 flex flex-col md:flex-row items-center md:items-start gap-6">
            <!-- Avatar -->
            <div class="profile-avatar">
                <?php if (!empty($user['profile_image']) && file_exists('../' . $user['profile_image'])): ?>
                    <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="<?= htmlspecialchars($user['username']) ?>">
                <?php else: ?>
                    <?= strtoupper(mb_substr($user['first_name'], 0, 1, 'UTF-8')) ?>
                <?php endif; ?>
            </div>

            <!-- Info -->
            <div class="flex-1 text-center md:text-left">
                <h1 class="text-2xl md:text-3xl font-bold text-white mb-1">
                    <?= htmlspecialchars($user['full_name']) ?>
                </h1>
                <p class="text-green-100 text-sm mb-3">
                    <i class="fas fa-at mr-1"></i><?= htmlspecialchars($user['username']) ?>
                    <?php if ($user['position']): ?>
                        <span class="mx-2 opacity-50">|</span>
                        <i class="fas fa-briefcase mr-1"></i><?= htmlspecialchars($user['position']) ?>
                    <?php endif; ?>
                </p>

                <div class="flex flex-wrap items-center justify-center md:justify-start gap-2">
                    <!-- Role Badge -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
                        <?php echo match($user['role']) {
                            'admin' => 'bg-white/20 text-white',
                            'staff' => 'bg-white/15 text-green-50',
                            default => 'bg-white/10 text-green-100'
                        }; ?>">
                        <i class="fas <?php echo match($user['role']) {
                            'admin' => 'fa-shield-alt',
                            'staff' => 'fa-user-tie',
                            default => 'fa-user'
                        }; ?>"></i>
                        <?= $role_labels[$user['role']] ?? $user['role'] ?>
                    </span>

                    <!-- Status Badge -->
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold
                        <?php echo match($user['status']) {
                            'active' => 'bg-green-300/30 text-white',
                            'inactive' => 'bg-red-400/30 text-red-100',
                            'suspended' => 'bg-yellow-400/30 text-yellow-100',
                            default => 'bg-white/10 text-white'
                        }; ?>">
                        <span class="w-1.5 h-1.5 rounded-full <?php echo match($user['status']) {
                            'active' => 'bg-green-300',
                            'inactive' => 'bg-red-300',
                            'suspended' => 'bg-yellow-300',
                            default => 'bg-gray-300'
                        }; ?>"></span>
                        <?= $status_labels[$user['status']] ?? $user['status'] ?>
                    </span>

                    <!-- Department -->
                    <?php if ($user['department_name']): ?>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-white/10 text-green-50">
                        <i class="fas fa-building"></i>
                        <?= htmlspecialchars($user['department_name']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="flex gap-2 relative z-10">
                <a href="user-manager.php" class="inline-flex items-center gap-2 px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-lg text-sm font-medium transition backdrop-blur-sm">
                    <i class="fas fa-arrow-left"></i> กลับ
                </a>
                <button onclick="editUserFromDetail(<?= $user_id ?>)" class="inline-flex items-center gap-2 px-4 py-2 bg-white/15 hover:bg-white/25 text-white rounded-lg text-sm font-medium transition backdrop-blur-sm">
                    <i class="fas fa-pen"></i> แก้ไข
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="stat-mini">
            <div class="stat-mini-value text-green-600"><?= count($service_requests) ?></div>
            <div class="stat-mini-label">คำขอบริการ</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-value text-blue-600"><?= count($task_assignments) ?></div>
            <div class="stat-mini-label">งานที่ได้รับ</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-value text-purple-600"><?= count($user_roles) ?></div>
            <div class="stat-mini-label">บทบาท/หน้าที่</div>
        </div>
        <div class="stat-mini">
            <div class="stat-mini-value text-gray-600"><?= number_format($joined_days) ?></div>
            <div class="stat-mini-label">วันที่ใช้งาน</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column (2/3) -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Personal Info -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="w-8 h-8 rounded-lg bg-green-50 flex items-center justify-center">
                        <i class="fas fa-id-card text-green-600 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800">ข้อมูลส่วนตัว</h3>
                </div>
                <div class="info-card-body">
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-hashtag mr-2 text-gray-400"></i>รหัสผู้ใช้</div>
                        <div class="info-value">#<?= $user['user_id'] ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-user mr-2 text-gray-400"></i>ชื่อ-นามสกุล</div>
                        <div class="info-value"><?= htmlspecialchars($user['full_name']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-at mr-2 text-gray-400"></i>ชื่อผู้ใช้</div>
                        <div class="info-value"><code class="bg-gray-100 px-2 py-0.5 rounded text-sm"><?= htmlspecialchars($user['username']) ?></code></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-envelope mr-2 text-gray-400"></i>อีเมล</div>
                        <div class="info-value"><?= htmlspecialchars($user['email']) ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-phone mr-2 text-gray-400"></i>โทรศัพท์</div>
                        <div class="info-value"><?= htmlspecialchars($user['phone'] ?? '-') ?: '-' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-building mr-2 text-gray-400"></i>หน่วยงาน</div>
                        <div class="info-value"><?= htmlspecialchars($user['department_name'] ?? '-') ?: '-' ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label"><i class="fas fa-briefcase mr-2 text-gray-400"></i>ตำแหน่ง</div>
                        <div class="info-value"><?= htmlspecialchars($user['position'] ?? '-') ?: '-' ?></div>
                    </div>
                </div>
            </div>

            <!-- Service Requests -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center">
                        <i class="fas fa-clipboard-list text-blue-600 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800">คำขอบริการล่าสุด</h3>
                    <span class="ml-auto text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium"><?= count($service_requests) ?> รายการ</span>
                </div>
                <div class="info-card-body p-0">
                    <?php if (!empty($service_requests)): ?>
                        <div class="divide-y divide-gray-50">
                            <?php foreach ($service_requests as $req):
                                $req_status_config = [
                                    'pending'     => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700'],
                                    'in_progress' => ['bg' => 'bg-blue-50',   'text' => 'text-blue-700'],
                                    'completed'   => ['bg' => 'bg-green-50',  'text' => 'text-green-700'],
                                    'rejected'    => ['bg' => 'bg-red-50',    'text' => 'text-red-700'],
                                    'cancelled'   => ['bg' => 'bg-gray-50',   'text' => 'text-gray-500'],
                                ];
                                $rsc = $req_status_config[$req['status']] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-500'];
                            ?>
                            <a href="request_detail.php?id=<?= $req['request_id'] ?>" class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center text-gray-500">
                                        <i class="fas fa-file-alt text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">
                                            <?= htmlspecialchars($req['request_code']) ?>
                                            <span class="text-gray-400 font-normal mx-1">-</span>
                                            <span class="text-gray-600 font-normal"><?= htmlspecialchars($req['service_name'] ?? $req['service_code']) ?></span>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5"><?= formatThaiDateUser($req['created_at']) ?></p>
                                    </div>
                                </div>
                                <span class="badge-status <?= $rsc['bg'] ?> <?= $rsc['text'] ?>">
                                    <?= $req_status_labels[$req['status']] ?? $req['status'] ?>
                                </span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fas fa-inbox text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">ยังไม่มีคำขอบริการ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Task Assignments -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center">
                        <i class="fas fa-tasks text-purple-600 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800">งานที่ได้รับมอบหมาย</h3>
                    <span class="ml-auto text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded-full font-medium"><?= count($task_assignments) ?> รายการ</span>
                </div>
                <div class="info-card-body p-0">
                    <?php if (!empty($task_assignments)): ?>
                        <div class="divide-y divide-gray-50">
                            <?php foreach ($task_assignments as $ta):
                                $ta_status_config = [
                                    'pending'     => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'icon' => 'fa-clock'],
                                    'accepted'    => ['bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'icon' => 'fa-check'],
                                    'in_progress' => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'icon' => 'fa-spinner'],
                                    'completed'   => ['bg' => 'bg-green-50',  'text' => 'text-green-700',  'icon' => 'fa-check-circle'],
                                    'cancelled'   => ['bg' => 'bg-red-50',    'text' => 'text-red-500',    'icon' => 'fa-times'],
                                ];
                                $tsc = $ta_status_config[$ta['status']] ?? ['bg' => 'bg-gray-50', 'text' => 'text-gray-500', 'icon' => 'fa-circle'];
                            ?>
                            <div class="flex items-center justify-between px-5 py-3.5 hover:bg-gray-50 transition">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center text-purple-500">
                                        <i class="fas fa-user-tag text-xs"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">
                                            <?= htmlspecialchars($ta['request_code']) ?>
                                            <?php if ($ta['role_name']): ?>
                                                <span class="text-xs text-gray-400 font-normal ml-1">(<?= htmlspecialchars($ta['role_name']) ?>)</span>
                                            <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            มอบหมายโดย <?= htmlspecialchars($ta['by_first'] . ' ' . $ta['by_last']) ?>
                                            &middot; <?= formatThaiDateUser($ta['created_at']) ?>
                                        </p>
                                    </div>
                                </div>
                                <span class="badge-status <?= $tsc['bg'] ?> <?= $tsc['text'] ?>">
                                    <?= $task_status_labels[$ta['status']] ?? $ta['status'] ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-10 text-gray-400">
                            <i class="fas fa-tasks text-3xl mb-2 opacity-30"></i>
                            <p class="text-sm">ยังไม่มีงานที่ได้รับมอบหมาย</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Right Column (1/3) -->
        <div class="space-y-6">
            <!-- Account Info -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center">
                        <i class="fas fa-cog text-indigo-600 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800">ข้อมูลบัญชี</h3>
                </div>
                <div class="info-card-body space-y-4">
                    <div>
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-user-shield mr-1"></i> บทบาทระบบ</p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold
                            <?php echo match($user['role']) {
                                'admin' => 'bg-blue-50 text-blue-700',
                                'staff' => 'bg-purple-50 text-purple-700',
                                default => 'bg-gray-50 text-gray-600'
                            }; ?>">
                            <i class="fas <?php echo match($user['role']) {
                                'admin' => 'fa-shield-alt',
                                'staff' => 'fa-user-tie',
                                default => 'fa-user'
                            }; ?>"></i>
                            <?= $role_labels[$user['role']] ?? $user['role'] ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-toggle-on mr-1"></i> สถานะบัญชี</p>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-sm font-semibold
                            <?php echo match($user['status']) {
                                'active' => 'bg-green-50 text-green-700',
                                'inactive' => 'bg-red-50 text-red-600',
                                'suspended' => 'bg-yellow-50 text-yellow-700',
                                default => 'bg-gray-50 text-gray-600'
                            }; ?>">
                            <span class="w-2 h-2 rounded-full <?php echo match($user['status']) {
                                'active' => 'bg-green-500',
                                'inactive' => 'bg-red-500',
                                'suspended' => 'bg-yellow-500',
                                default => 'bg-gray-500'
                            }; ?>"></span>
                            <?= $status_labels[$user['status']] ?? $user['status'] ?>
                        </span>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-sign-in-alt mr-1"></i> เข้าสู่ระบบล่าสุด</p>
                        <p class="text-sm font-medium text-gray-800">
                            <?= $user['last_login'] ? formatThaiDateUser($user['last_login']) : '<span class="text-gray-400">ยังไม่เคยเข้าสู่ระบบ</span>' ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-calendar-plus mr-1"></i> วันที่สร้างบัญชี</p>
                        <p class="text-sm font-medium text-gray-800"><?= formatThaiDateUser($user['created_at']) ?></p>
                    </div>
                    <?php if (!empty($user['updated_at'])): ?>
                    <div>
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-edit mr-1"></i> อัปเดตล่าสุด</p>
                        <p class="text-sm font-medium text-gray-800"><?= formatThaiDateUser($user['updated_at']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Roles -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="w-8 h-8 rounded-lg bg-orange-50 flex items-center justify-center">
                        <i class="fas fa-user-tag text-orange-600 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800">บทบาท/หน้าที่</h3>
                </div>
                <div class="info-card-body">
                    <?php if (!empty($user_roles)): ?>
                        <div class="space-y-2">
                            <?php foreach ($user_roles as $role): ?>
                            <div class="role-chip w-full" style="border-color: <?= htmlspecialchars($role['role_color'] ?? '#e5e7eb') ?>20; background: <?= htmlspecialchars($role['role_color'] ?? '#6b7280') ?>08;">
                                <div class="w-7 h-7 rounded-lg flex items-center justify-center" style="background: <?= htmlspecialchars($role['role_color'] ?? '#6b7280') ?>15;">
                                    <i class="fas <?= htmlspecialchars($role['role_icon'] ?? 'fa-user-tag') ?>" style="color: <?= htmlspecialchars($role['role_color'] ?? '#6b7280') ?>; font-size: 0.75rem;"></i>
                                </div>
                                <div class="flex-1">
                                    <span class="text-gray-800"><?= htmlspecialchars($role['role_name']) ?></span>
                                    <?php if ($role['is_primary']): ?>
                                        <span class="ml-1 text-xs bg-yellow-100 text-yellow-700 px-1.5 py-0.5 rounded">หลัก</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-6 text-gray-400">
                            <i class="fas fa-user-tag text-2xl mb-2 opacity-30"></i>
                            <p class="text-sm">ยังไม่มีบทบาทที่ได้รับ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="info-card">
                <div class="info-card-header">
                    <div class="w-8 h-8 rounded-lg bg-gray-100 flex items-center justify-center">
                        <i class="fas fa-bolt text-gray-600 text-sm"></i>
                    </div>
                    <h3 class="font-semibold text-gray-800">การดำเนินการ</h3>
                </div>
                <div class="info-card-body space-y-2">
                    <button onclick="editUserFromDetail(<?= $user_id ?>)" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-left text-sm font-medium text-gray-700 hover:bg-green-50 hover:text-green-700 transition">
                        <i class="fas fa-pen text-gray-400 w-4"></i> แก้ไขข้อมูล
                    </button>
                    <button onclick="window.location.href='user-manager.php'" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-left text-sm font-medium text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition">
                        <i class="fas fa-users text-gray-400 w-4"></i> กลับรายการผู้ใช้
                    </button>
                    <?php if ($user_id != $_SESSION['user_id']): ?>
                    <button onclick="toggleUserStatus(<?= $user_id ?>, '<?= $user['status'] ?>')" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg text-left text-sm font-medium text-gray-700 hover:bg-yellow-50 hover:text-yellow-700 transition">
                        <i class="fas fa-toggle-<?= $user['status'] === 'active' ? 'on' : 'off' ?> text-gray-400 w-4"></i>
                        <?= $user['status'] === 'active' ? 'ระงับบัญชี' : 'เปิดใช้งานบัญชี' ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function editUserFromDetail(userId) {
    window.location.href = `edit_user.php?id=${userId}`;
}

async function toggleUserStatus(userId, currentStatus) {
    const newStatus = currentStatus === 'active' ? 'suspended' : 'active';
    const actionText = currentStatus === 'active' ? 'ระงับ' : 'เปิดใช้งาน';

    const result = await Swal.fire({
        title: '',
        html: `
            <div style="text-align:center;margin-bottom:16px;">
                <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,${currentStatus === 'active' ? '#f59e0b,#d97706' : '#10b981,#059669'});
                            display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                    <i class="fas fa-toggle-${currentStatus === 'active' ? 'off' : 'on'}" style="color:#fff;font-size:22px;"></i>
                </div>
                <h2 style="margin:0;font-size:1.3rem;font-weight:700;color:#1f2937;">${actionText}บัญชีผู้ใช้?</h2>
                <p style="margin:6px 0 0;color:#6b7280;font-size:0.875rem;"><?= htmlspecialchars($user['full_name']) ?></p>
            </div>`,
        width: 400,
        padding: '24px',
        showCancelButton: true,
        confirmButtonText: `<i class="fas fa-check" style="margin-right:6px;"></i> ยืนยัน${actionText}`,
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: currentStatus === 'active' ? '#d97706' : '#10b981',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'swal-status-popup'
        }
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'edit');
            formData.append('user_id', userId);
            formData.append('username', '<?= htmlspecialchars($user['username']) ?>');
            formData.append('email', '<?= htmlspecialchars($user['email']) ?>');
            formData.append('prefix_id', '<?= $user['prefix_id'] ?? '' ?>');
            formData.append('first_name', '<?= htmlspecialchars($user['first_name']) ?>');
            formData.append('last_name', '<?= htmlspecialchars($user['last_name']) ?>');
            formData.append('phone', '<?= htmlspecialchars($user['phone'] ?? '') ?>');
            formData.append('role', '<?= $user['role'] ?>');
            formData.append('status', newStatus);
            formData.append('department_id', '<?= $user['department_id'] ?? '' ?>');
            formData.append('position', '<?= htmlspecialchars($user['position'] ?? '') ?>');

            const response = await fetch('api/user_manager_api.php', { method: 'POST', body: formData });
            const data = await response.json();

            if (data.success) {
                Swal.fire('สำเร็จ', `${actionText}บัญชีเรียบร้อยแล้ว`, 'success').then(() => location.reload());
            } else {
                Swal.fire('ผิดพลาด', data.message, 'error');
            }
        } catch (error) {
            Swal.fire('ผิดพลาด', 'ไม่สามารถดำเนินการได้', 'error');
        }
    }
}
</script>

<style>
    .swal-status-popup { border-radius: 16px !important; }
</style>

<?php include __DIR__ . '/admin-layout/footer.php'; ?>
