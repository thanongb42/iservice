<?php
/**
 * Service Requests Management
 * Admin interface for managing all service requests
 */

require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'จัดการคำขอบริการ';
$current_page = 'service_requests';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการคำขอบริการ']
];

// Get statistics
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
FROM service_requests";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Set pending_requests for sidebar badge
$pending_requests = intval($stats['pending'] ?? 0);

// Pagination setup
$items_per_page = 20;
$page_num = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page_num - 1) * $items_per_page;

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM v_service_requests_full";
$count_result = $conn->query($count_sql);
$count_row = $count_result->fetch_assoc();
$total_items = $count_row['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get paginated service requests
$sql = "SELECT * FROM v_service_requests_full ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $items_per_page, $offset);
$stmt->execute();
$result = $stmt->get_result();
$requests = [];
$stmt->close();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
}

// Get service types - define standard service types
$service_types = [
    ['service_code' => 'EMAIL', 'service_name' => 'ขอใช้บริการ Email'],
    ['service_code' => 'NAS', 'service_name' => 'ขอใช้พื้นที่ NAS'],
    ['service_code' => 'IT_SUPPORT', 'service_name' => 'ขอรับการสนับสนุนด้าน IT'],
    ['service_code' => 'INTERNET', 'service_name' => 'ขอใช้บริการ Internet'],
    ['service_code' => 'QR_CODE', 'service_name' => 'ขอทำ QR Code'],
    ['service_code' => 'PHOTOGRAPHY', 'service_name' => 'ขอถ่ายภาพกิจกรรม'],
    ['service_code' => 'WEB_DESIGN', 'service_name' => 'ขอออกแบบเว็บไซต์'],
    ['service_code' => 'PRINTER', 'service_name' => 'ขอใช้เครื่องพิมพ์']
];

// Try to get actual service codes from database if table has service_code column
$check_query = "SHOW COLUMNS FROM service_requests LIKE 'service_code'";
$check_result = $conn->query($check_query);
if ($check_result && $check_result->num_rows > 0) {
    $db_services_query = "SELECT DISTINCT service_code FROM service_requests WHERE service_code IS NOT NULL";
    $db_services_result = $conn->query($db_services_query);
    if ($db_services_result && $db_services_result->num_rows > 0) {
        // Use only service codes that actually exist in database
        $existing_codes = [];
        while ($row = $db_services_result->fetch_assoc()) {
            $existing_codes[] = $row['service_code'];
        }
        $service_types = array_filter($service_types, function($service) use ($existing_codes) {
            return in_array($service['service_code'], $existing_codes);
        });
    }
}

// Get users for assignment (legacy - keep for backwards compatibility)
$users_query = "SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'staff') ORDER BY first_name";
$users_result = $conn->query($users_query);
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Get roles for task assignment
$roles_query = "SELECT * FROM roles WHERE is_active = 1 AND can_be_assigned = 1 ORDER BY display_order ASC";
$roles_result = $conn->query($roles_query);
$roles = [];
if ($roles_result) {
    while ($row = $roles_result->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Check if current user can assign tasks
$can_assign_tasks = false;
if ($_SESSION['role'] === 'admin') {
    $can_assign_tasks = true;
} else {
    $check_assign = $conn->prepare("
        SELECT COUNT(*) as cnt FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = ? AND ur.is_active = 1 AND r.is_active = 1 AND r.can_assign = 1
    ");
    $check_assign->bind_param('i', $_SESSION['user_id']);
    $check_assign->execute();
    $assign_result = $check_assign->get_result()->fetch_assoc();
    $can_assign_tasks = $assign_result['cnt'] > 0;
}

// Get task assignments count per request
$task_counts_query = "
    SELECT request_id,
           COUNT(*) as total_assignments,
           SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
           SUM(CASE WHEN status IN ('pending', 'accepted', 'in_progress') THEN 1 ELSE 0 END) as active_count
    FROM task_assignments
    GROUP BY request_id
";
$task_counts_result = $conn->query($task_counts_query);
$task_counts = [];
if ($task_counts_result) {
    while ($row = $task_counts_result->fetch_assoc()) {
        $task_counts[$row['request_id']] = $row;
    }
}

// Helper function to get service name from code
function getServiceName($code) {
    $service_names = [
        'EMAIL' => 'ขอใช้บริการ Email',
        'NAS' => 'ขอใช้พื้นที่ NAS',
        'IT_SUPPORT' => 'ขอรับการสนับสนุนด้าน IT',
        'INTERNET' => 'ขอใช้บริการ Internet',
        'QR_CODE' => 'ขอทำ QR Code',
        'PHOTOGRAPHY' => 'ขอถ่ายภาพกิจกรรม',
        'WEB_DESIGN' => 'ขอออกแบบเว็บไซต์',
        'PRINTER' => 'ขอใช้เครื่องพิมพ์'
    ];
    return $service_names[$code] ?? $code;
}

// Helper function to get Thai status label
function getThaiStatus($status) {
    $labels = [
        'pending' => 'รอดำเนินการ',
        'in_progress' => 'กำลังดำเนินการ',
        'completed' => 'เสร็จสิ้น',
        'cancelled' => 'ยกเลิก'
    ];
    return $labels[$status] ?? $status;
}

// Helper function to get Thai priority label
function getThaiPriority($priority) {
    $labels = [
        'low' => 'ต่ำ',
        'medium' => 'ปานกลาง',
        'normal' => 'ปกติ',
        'high' => 'สูง',
        'urgent' => 'เร่งด่วน'
    ];
    return $labels[$priority] ?? $priority;
}

// Helper function to format date in Thai
function formatThaiDate($datetime, $showTime = true) {
    if (empty($datetime)) return '-';

    $timestamp = strtotime($datetime);

    $thai_months = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];

    $day = date('j', $timestamp);
    $month = $thai_months[(int)date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543; // พ.ศ.
    $time = date('H:i', $timestamp); // 24-hour format

    if ($showTime) {
        return "{$day} {$month} {$year} {$time} น.";
    }
    return "{$day} {$month} {$year}";
}
?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<main class="main-content-transition lg:ml-0">
    <!-- Flatpickr CSS for Thai Date/Time Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/material_blue.css">

    <style>
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending { background-color: #fef3c7; color: #92400e; }
        .status-in_progress { background-color: #dbeafe; color: #1e40af; }
        .status-completed { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }

        .priority-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .priority-low { background-color: #f3f4f6; color: #374151; }
        .priority-medium { background-color: #fef3c7; color: #92400e; }
        .priority-high { background-color: #fed7aa; color: #9a3412; }
        .priority-urgent { background-color: #fecaca; color: #991b1b; }

        .stat-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .action-btn {
            padding: 0.5rem;
            margin: 0 0.25rem;
            border-radius: 0.25rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .action-btn:hover {
            transform: scale(1.1);
        }

        .table-responsive {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background-color: #f9fafb;
            padding: 0.75rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }

        td {
            padding: 0.75rem;
            border-bottom: 1px solid #e5e7eb;
        }

        tr:hover {
            background-color: #f9fafb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 2rem;
            border-radius: 0.5rem;
            width: 90%;
            max-width: 800px;
            max-height: 85vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        .filter-container {
            background: white;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        /* SweetAlert Status Update Modal */
        .swal-status-popup {
            border-radius: 16px !important;
        }
        .swal-status-confirm-btn {
            border-radius: 10px !important;
            padding: 10px 28px !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
        }
        .swal-status-cancel-btn {
            border-radius: 10px !important;
            padding: 10px 28px !important;
            font-weight: 600 !important;
            font-size: 0.95rem !important;
        }
    </style>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-tasks text-green-600"></i> จัดการคำขอบริการ
            </h1>
            <p class="mt-2 text-gray-600">ดูแลและจัดการคำขอบริการต่างๆ จากผู้ใช้งาน</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">คำขอทั้งหมด</p>
                        <p class="text-2xl font-bold text-gray-800"><?= $stats['total'] ?></p>
                    </div>
                    <i class="fas fa-clipboard-list text-3xl text-gray-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">รอดำเนินการ</p>
                        <p class="text-2xl font-bold text-yellow-600"><?= $stats['pending'] ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">กำลังดำเนินการ</p>
                        <p class="text-2xl font-bold text-blue-600"><?= $stats['in_progress'] ?></p>
                    </div>
                    <i class="fas fa-spinner text-3xl text-blue-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">เสร็จสิ้น</p>
                        <p class="text-2xl font-bold text-green-600"><?= $stats['completed'] ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-400"></i>
                </div>
            </div>
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">ยกเลิก</p>
                        <p class="text-2xl font-bold text-red-600"><?= $stats['cancelled'] ?></p>
                    </div>
                    <i class="fas fa-times-circle text-3xl text-red-400"></i>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-container">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ค้นหา</label>
                    <input type="text" id="searchInput" placeholder="รหัสคำขอ, ชื่อ, หน่วยงาน..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                    <select id="filterStatus" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">ทุกสถานะ</option>
                        <option value="pending">รอดำเนินการ</option>
                        <option value="in_progress">กำลังดำเนินการ</option>
                        <option value="completed">เสร็จสิ้น</option>
                        <option value="cancelled">ยกเลิก</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ประเภทบริการ</label>
                    <select id="filterService" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">ทุกบริการ</option>
                        <?php foreach ($service_types as $service): ?>
                            <option value="<?= htmlspecialchars($service['service_code']) ?>">
                                <?= htmlspecialchars($service['service_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ความสำคัญ</label>
                    <select id="filterPriority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">ทุกระดับ</option>
                        <option value="low">ต่ำ</option>
                        <option value="medium">ปานกลาง</option>
                        <option value="high">สูง</option>
                        <option value="urgent">เร่งด่วน</option>
                    </select>
                </div>
            </div>
            <div class="flex justify-between items-center">
                <button onclick="clearFilters()" class="text-sm text-blue-600 hover:text-blue-800">
                    <i class="fas fa-redo"></i> ล้างตัวกรอง
                </button>
                <div id="bulkActions" style="display: none;">
                    <select id="bulkActionSelect" class="px-3 py-2 border border-gray-300 rounded-md mr-2">
                        <option value="">-- เลือกการดำเนินการ --</option>
                        <option value="update_status">อัปเดตสถานะ</option>
                        <option value="delete">ลบที่เลือก</option>
                    </select>
                    <button onclick="executeBulkAction()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded">
                        ดำเนินการ
                    </button>
                </div>
            </div>
        </div>

        <!-- Requests Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="table-responsive">
                <table id="requestsTable">
                    <thead>
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>รหัสคำขอ</th>
                            <th>บริการ</th>
                            <th>ผู้ขอ</th>
                            <th>หน่วยงาน</th>
                            <th>สถานะ</th>
                            <th>ความสำคัญ</th>
                            <th>มอบหมายให้</th>
                            <th>วันที่สร้าง</th>
                            <th style="width: 180px;">การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requests as $req): ?>
                            <tr data-id="<?= $req['request_id'] ?>"
                                data-status="<?= $req['status'] ?? '' ?>"
                                data-service="<?= $req['service_code'] ?? '' ?>"
                                data-priority="<?= $req['priority'] ?? '' ?>"
                                data-search="<?= strtolower($req['request_id'] . ' ' . ($req['user_full_name'] ?? '') . ' ' . ($req['department_name'] ?? '')) ?>">
                                <td>
                                    <input type="checkbox" class="request-checkbox" value="<?= $req['request_id'] ?>">
                                </td>
                                <td class="font-mono text-sm">#<?= str_pad($req['request_id'], 4, '0', STR_PAD_LEFT) ?></td>
                                <td><?= htmlspecialchars(isset($req['service_name']) ? $req['service_name'] : getServiceName($req['service_code'] ?? 'N/A')) ?></td>
                                <td>
                                    <?= htmlspecialchars($req['user_full_name'] ?? 'N/A') ?>
                                    <br><small class="text-gray-500"><?= htmlspecialchars($req['user_email'] ?? '-') ?></small>
                                </td>
                                <td><?= htmlspecialchars($req['department_name'] ?? '-') ?></td>
                                <td>
                                    <span class="status-badge status-<?= $req['status'] ?>">
                                        <?= getThaiStatus($req['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="priority-badge priority-<?= $req['priority'] ?>">
                                        <?= getThaiPriority($req['priority']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $req_task_count = $task_counts[$req['request_id']] ?? null;
                                    if ($req_task_count && $req_task_count['total_assignments'] > 0):
                                    ?>
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-users mr-1"></i>
                                                <?= $req_task_count['total_assignments'] ?> งาน
                                            </span>
                                            <?php if ($req_task_count['completed_count'] > 0): ?>
                                                <span class="text-xs text-gray-500">
                                                    (เสร็จ <?= $req_task_count['completed_count'] ?>)
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($req['assigned_to']): ?>
                                        <?= htmlspecialchars($req['assigned_full_name'] ?? $req['assigned_to']) ?>
                                    <?php else: ?>
                                        <span class="text-gray-400">ยังไม่มอบหมาย</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm"><?= formatThaiDate($req['created_at']) ?></td>
                                <td>
                                    <button onclick="window.location.href='request_detail.php?id=<?= $req['request_id'] ?>'" class="action-btn text-blue-600 hover:bg-blue-50" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="updateStatus(<?= $req['request_id'] ?>)" class="action-btn text-green-600 hover:bg-green-50" title="อัปเดตสถานะ">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="assignRequest(<?= $req['request_id'] ?>)" class="action-btn text-purple-600 hover:bg-purple-50" title="มอบหมายงาน">
                                        <i class="fas fa-user-tag"></i>
                                    </button>
                                    <button onclick="updatePriority(<?= $req['request_id'] ?>)" class="action-btn text-orange-600 hover:bg-orange-50" title="อัปเดตความสำคัญ">
                                        <i class="fas fa-flag"></i>
                                    </button>
                                    <button onclick="deleteRequest(<?= $req['request_id'] ?>)" class="action-btn text-red-600 hover:bg-red-50" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div style="padding: 1.5rem; border-top: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 1rem;">
                <div style="font-size: 0.875rem; color: #6b7280;">
                    แสดง <strong><?= ($offset + 1) ?></strong> ถึง <strong><?= min($offset + $items_per_page, $total_items) ?></strong> จาก <strong><?= $total_items ?></strong> รายการ
                </div>
                
                <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                    <!-- First Page -->
                    <a href="?page=1<?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; text-decoration: none; color: #374151; font-size: 0.875rem; <?= $page_num === 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' ?>"
                       <?= $page_num === 1 ? 'onclick="return false;"' : '' ?>>
                        <i class="fas fa-chevron-left mr-1"></i>First
                    </a>

                    <!-- Previous Page -->
                    <a href="?page=<?= max(1, $page_num - 1) ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; text-decoration: none; color: #374151; font-size: 0.875rem; <?= $page_num === 1 ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' ?>"
                       <?= $page_num === 1 ? 'onclick="return false;"' : '' ?>>
                        <i class="fas fa-chevron-left mr-1"></i>ก่อนหน้า
                    </a>

                    <!-- Page Numbers -->
                    <?php
                    $start_page = max(1, $page_num - 2);
                    $end_page = min($total_pages, $page_num + 2);

                    if ($start_page > 1) {
                        echo '<span style="padding: 0 0.25rem; color: #9ca3af;">...</span>';
                    }

                    for ($p = $start_page; $p <= $end_page; $p++):
                    ?>
                        <a href="?page=<?= $p ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                           style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; text-decoration: none; font-size: 0.875rem; <?= $p === $page_num ? 'background: #009933; color: white; border-color: #009933;' : 'color: #374151; cursor: pointer;' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($end_page < $total_pages) {
                        echo '<span style="padding: 0 0.25rem; color: #9ca3af;">...</span>';
                    } ?>

                    <!-- Next Page -->
                    <a href="?page=<?= min($total_pages, $page_num + 1) ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; text-decoration: none; color: #374151; font-size: 0.875rem; <?= $page_num === $total_pages ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' ?>"
                       <?= $page_num === $total_pages ? 'onclick="return false;"' : '' ?>>
                        ถัดไป<i class="fas fa-chevron-right ml-1"></i>
                    </a>

                    <!-- Last Page -->
                    <a href="?page=<?= $total_pages ?><?= isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?><?= isset($_GET['search']) ? '&search=' . urlencode($_GET['search']) : '' ?>"
                       style="padding: 0.5rem 0.75rem; border: 1px solid #d1d5db; border-radius: 0.375rem; text-decoration: none; color: #374151; font-size: 0.875rem; <?= $page_num === $total_pages ? 'opacity: 0.5; cursor: not-allowed;' : 'cursor: pointer;' ?>"
                       <?= $page_num === $total_pages ? 'onclick="return false;"' : '' ?>>
                        Last<i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDetailsModal()">&times;</span>
            <h2 class="text-2xl font-bold mb-4">รายละเอียดคำขอ</h2>
            <div id="detailsContent"></div>
        </div>
    </div>

    <!-- Task Assignment Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content" style="max-width: 900px;">
            <span class="close" onclick="closeAssignModal()">&times;</span>
            <h2 class="text-2xl font-bold mb-4">
                <i class="fas fa-user-tag text-green-600"></i> มอบหมายงาน
                <span id="assignRequestCode" class="text-gray-500 text-lg"></span>
            </h2>

            <!-- Current Assignments -->
            <div id="currentAssignments" class="mb-6">
                <h3 class="font-semibold text-gray-700 mb-3">
                    <i class="fas fa-tasks"></i> งานที่มอบหมายแล้ว
                </h3>
                <div id="assignmentsList" class="space-y-2">
                    <!-- Assignments will be loaded here -->
                </div>
            </div>

            <!-- New Assignment Form -->
            <div class="bg-gray-50 rounded-lg p-4">
                <h3 class="font-semibold text-gray-700 mb-3">
                    <i class="fas fa-plus-circle"></i> มอบหมายงานใหม่
                </h3>
                <input type="hidden" id="assignRequestId">
                <input type="hidden" id="assignServiceCode">

                <!-- Service Type and Required Roles Info -->
                <div id="serviceRoleInfo" class="mb-4 p-3 bg-blue-50 border border-blue-200 rounded-lg hidden">
                    <p class="text-sm text-blue-800">
                        <i class="fas fa-info-circle"></i>
                        <strong>บทบาท/หน้าที่ที่สามารถรับมอบหมายได้:</strong>
                        <span id="requiredRolesText"></span>
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ผู้รับผิดชอบ <span class="text-red-500">*</span></label>
                        <select id="assignUser" onchange="updateSelectedUserRoles()" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">-- เลือกผู้รับผิดชอบ --</option>
                        </select>
                        <p id="userRolesInfo" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">บทบาท</label>
                        <select id="assignRole" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="">-- เลือกบทบาท (ไม่บังคับ) --</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['role_id'] ?>" data-icon="<?= $role['role_icon'] ?>" data-color="<?= $role['role_color'] ?>">
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">ความสำคัญ</label>
                        <select id="assignPriority" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500">
                            <option value="normal">ปกติ</option>
                            <option value="low">ต่ำ</option>
                            <option value="high">สูง</option>
                            <option value="urgent">เร่งด่วน</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">กำหนดส่ง</label>
                        <input type="text" id="assignDueDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="เลือกวันที่และเวลา">
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">หมายเหตุ</label>
                    <textarea id="assignNotes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500" placeholder="ระบุรายละเอียดเพิ่มเติม..."></textarea>
                </div>

                <div class="flex justify-end gap-2">
                    <button onclick="closeAssignModal()" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-lg hover:bg-gray-300">
                        ยกเลิก
                    </button>
                    <button onclick="submitAssignment()" class="px-4 py-2 text-white bg-green-600 rounded-lg hover:bg-green-700">
                        <i class="fas fa-check mr-1"></i> มอบหมายงาน
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Flatpickr JS -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>

    <script>
        const users = <?= json_encode($users) ?>;
        const roles = <?= json_encode($roles) ?>;
        const canAssignTasks = <?= $can_assign_tasks ? 'true' : 'false' ?>;

        // Thai month names for display
        const thaiMonths = ['ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
        const thaiMonthsFull = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน', 'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        const thaiDays = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];

        // Status labels in Thai
        const statusLabelsThai = {
            'pending': 'รอดำเนินการ',
            'in_progress': 'กำลังดำเนินการ',
            'completed': 'เสร็จสิ้น',
            'cancelled': 'ยกเลิก'
        };

        // Priority labels in Thai
        const priorityLabelsThai = {
            'low': 'ต่ำ',
            'medium': 'ปานกลาง',
            'normal': 'ปกติ',
            'high': 'สูง',
            'urgent': 'เร่งด่วน'
        };

        // Function to format date in Thai
        function formatThaiDate(dateStr, showTime = true) {
            if (!dateStr) return '-';
            const date = new Date(dateStr);
            const day = date.getDate();
            const month = thaiMonths[date.getMonth()];
            const year = date.getFullYear() + 543;
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');

            if (showTime) {
                return `${day} ${month} ${year} ${hours}:${minutes} น.`;
            }
            return `${day} ${month} ${year}`;
        }

        // Initialize Flatpickr with Thai locale and 24-hour format
        document.addEventListener('DOMContentLoaded', function() {
            // Due date picker with Thai locale
            flatpickr("#assignDueDate", {
                locale: "th",
                enableTime: true,
                time_24hr: true,
                dateFormat: "Y-m-d H:i",
                altInput: true,
                altFormat: "j M Y H:i น.",
                minDate: "today",
                monthSelectorType: "static",
                formatDate: function(date, format, locale) {
                    if (format === "j M Y H:i น.") {
                        const day = date.getDate();
                        const month = thaiMonths[date.getMonth()];
                        const year = date.getFullYear() + 543;
                        const hours = String(date.getHours()).padStart(2, '0');
                        const minutes = String(date.getMinutes()).padStart(2, '0');
                        return `${day} ${month} ${year} ${hours}:${minutes} น.`;
                    }
                    return flatpickr.formatDate(date, format, locale);
                }
            });
        });

        // Service name mapping
        const serviceNames = {
            'EMAIL': 'ขอใช้บริการ Email',
            'NAS': 'ขอใช้พื้นที่ NAS',
            'IT_SUPPORT': 'ขอรับการสนับสนุนด้าน IT',
            'INTERNET': 'ขอใช้บริการ Internet',
            'QR_CODE': 'ขอทำ QR Code',
            'PHOTOGRAPHY': 'ขอถ่ายภาพกิจกรรม',
            'WEB_DESIGN': 'ขอออกแบบเว็บไซต์',
            'PRINTER': 'ขอใช้เครื่องพิมพ์'
        };

        function getServiceName(code) {
            return serviceNames[code] || code;
        }

        // Filter functionality
        function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('filterStatus').value;
            const serviceFilter = document.getElementById('filterService').value;
            const priorityFilter = document.getElementById('filterPriority').value;

            const rows = document.querySelectorAll('#requestsTable tbody tr');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                const status = row.getAttribute('data-status');
                const service = row.getAttribute('data-service');
                const priority = row.getAttribute('data-priority');

                const matchSearch = searchData.includes(searchTerm);
                const matchStatus = !statusFilter || status === statusFilter;
                const matchService = !serviceFilter || service === serviceFilter;
                const matchPriority = !priorityFilter || priority === priorityFilter;

                if (matchSearch && matchStatus && matchService && matchPriority) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function clearFilters() {
            document.getElementById('searchInput').value = '';
            document.getElementById('filterStatus').value = '';
            document.getElementById('filterService').value = '';
            document.getElementById('filterPriority').value = '';
            applyFilters();
        }

        // Attach filter listeners
        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);
        document.getElementById('filterService').addEventListener('change', applyFilters);
        document.getElementById('filterPriority').addEventListener('change', applyFilters);

        // Checkbox selection
        function toggleSelectAll() {
            const selectAll = document.getElementById('selectAll');
            const checkboxes = document.querySelectorAll('.request-checkbox');
            checkboxes.forEach(cb => {
                if (cb.closest('tr').style.display !== 'none') {
                    cb.checked = selectAll.checked;
                }
            });
            updateBulkActionsVisibility();
        }

        function updateBulkActionsVisibility() {
            const checkedBoxes = document.querySelectorAll('.request-checkbox:checked');
            document.getElementById('bulkActions').style.display = checkedBoxes.length > 0 ? 'block' : 'none';
        }

        document.querySelectorAll('.request-checkbox').forEach(cb => {
            cb.addEventListener('change', updateBulkActionsVisibility);
        });

        // View Details
        async function viewDetails(id) {
            try {
                const response = await fetch(`../get_request_status.php?request_id=${id}`);
                const data = await response.json();

                if (data.success) {
                    const req = data.request;
                    let html = `
                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-500">รหัสคำขอ</p>
                                    <p class="font-bold">#${String(req.request_id).padStart(4, '0')}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">บริการ</p>
                                    <p class="font-bold">${req.service_name || getServiceName(req.service_code)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">สถานะ</p>
                                    <p><span class="status-badge status-${req.status}">${statusLabelsThai[req.status] || req.status}</span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">ความสำคัญ</p>
                                    <p><span class="priority-badge priority-${req.priority}">${priorityLabelsThai[req.priority] || req.priority}</span></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">ผู้ขอ</p>
                                    <p>${req.user_full_name}</p>
                                    <p class="text-sm text-gray-600">${req.user_email}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">หน่วยงาน</p>
                                    <p>${req.department_name || '-'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">วันที่สร้าง</p>
                                    <p>${formatThaiDate(req.created_at)}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">อัปเดตล่าสุด</p>
                                    <p>${formatThaiDate(req.updated_at)}</p>
                                </div>
                            </div>

                            ${req.description ? `
                            <div>
                                <p class="text-sm text-gray-500">รายละเอียด</p>
                                <p class="mt-1">${req.description}</p>
                            </div>
                            ` : ''}

                            ${req.admin_notes ? `
                            <div>
                                <p class="text-sm text-gray-500">หมายเหตุผู้ดูแล</p>
                                <p class="mt-1 bg-yellow-50 p-3 rounded">${req.admin_notes}</p>
                            </div>
                            ` : ''}

                            ${req.completed_date ? `
                            <div>
                                <p class="text-sm text-gray-500">วันที่เสร็จสิ้น</p>
                                <p>${formatThaiDate(req.completed_date)}</p>
                            </div>
                            ` : ''}
                        </div>
                    `;

                    document.getElementById('detailsContent').innerHTML = html;
                    document.getElementById('detailsModal').style.display = 'block';
                } else {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดรายละเอียดได้', 'error');
                }
            } catch (error) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถโหลดรายละเอียดได้', 'error');
            }
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }

        // Select status card in the update status modal
        function selectStatusCard(card) {
            const statusColors = {
                'pending':     { bg: '#fef3c7', border: '#fcd34d', color: '#f59e0b' },
                'in_progress': { bg: '#dbeafe', border: '#93c5fd', color: '#3b82f6' },
                'completed':   { bg: '#d1fae5', border: '#6ee7b7', color: '#10b981' },
                'cancelled':   { bg: '#fee2e2', border: '#fca5a5', color: '#ef4444' }
            };

            // Deselect all cards
            document.querySelectorAll('.swal-status-card').forEach(c => {
                c.classList.remove('active');
                c.style.borderColor = '#e5e7eb';
                c.style.background = '#fff';
                c.style.boxShadow = 'none';
                c.querySelector('.swal-status-check').style.display = 'none';
            });

            // Select clicked card
            const value = card.dataset.value;
            const colors = statusColors[value];
            card.classList.add('active');
            card.style.borderColor = colors.border;
            card.style.background = colors.bg;
            card.querySelector('.swal-status-check').style.display = 'flex';
            card.querySelector('.swal-status-check').style.background = colors.color;

            // Update hidden input
            document.getElementById('swal-status').value = value;
        }

        // Update Status
        async function updateStatus(id) {
            // Get current status from the table row
            const row = document.querySelector(`tr[data-id="${id}"]`);
            const currentStatus = row ? row.dataset.status : '';
            const requestCode = '#' + String(id).padStart(4, '0');

            const statusOptions = [
                { value: 'pending', label: 'รอดำเนินการ', icon: 'fa-clock', color: '#f59e0b', bg: '#fef3c7', border: '#fcd34d' },
                { value: 'in_progress', label: 'กำลังดำเนินการ', icon: 'fa-spinner', color: '#3b82f6', bg: '#dbeafe', border: '#93c5fd' },
                { value: 'completed', label: 'เสร็จสิ้น', icon: 'fa-check-circle', color: '#10b981', bg: '#d1fae5', border: '#6ee7b7' },
                { value: 'cancelled', label: 'ยกเลิก', icon: 'fa-times-circle', color: '#ef4444', bg: '#fee2e2', border: '#fca5a5' }
            ];

            let statusCardsHtml = statusOptions.map(opt => {
                const isActive = opt.value === currentStatus;
                return `
                    <div class="swal-status-card ${isActive ? 'active' : ''}" data-value="${opt.value}"
                         onclick="selectStatusCard(this)"
                         style="display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:10px;cursor:pointer;
                                border:2px solid ${isActive ? opt.border : '#e5e7eb'};
                                background:${isActive ? opt.bg : '#fff'};
                                transition:all 0.2s ease;margin-bottom:8px;position:relative;">
                        <div style="width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;
                                    background:${opt.bg};flex-shrink:0;">
                            <i class="fas ${opt.icon}" style="color:${opt.color};font-size:18px;"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-weight:600;color:#1f2937;font-size:0.95rem;">${opt.label}</div>
                        </div>
                        <div class="swal-status-check" style="display:${isActive ? 'flex' : 'none'};width:24px;height:24px;border-radius:50%;
                                    background:${opt.color};align-items:center;justify-content:center;flex-shrink:0;">
                            <i class="fas fa-check" style="color:#fff;font-size:12px;"></i>
                        </div>
                    </div>`;
            }).join('');

            const { value: formValues } = await Swal.fire({
                title: '',
                html: `
                    <div style="text-align:left;">
                        <div style="text-align:center;margin-bottom:20px;">
                            <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#10b981,#059669);
                                        display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                                <i class="fas fa-exchange-alt" style="color:#fff;font-size:22px;"></i>
                            </div>
                            <h2 style="margin:0;font-size:1.35rem;font-weight:700;color:#1f2937;">อัปเดตสถานะคำขอ</h2>
                            <p style="margin:4px 0 0;color:#6b7280;font-size:0.875rem;">คำขอ ${requestCode}</p>
                        </div>
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:#374151;margin-bottom:8px;text-transform:uppercase;letter-spacing:0.05em;">
                            <i class="fas fa-tag" style="margin-right:4px;color:#9ca3af;"></i> เลือกสถานะ
                        </label>
                        <input type="hidden" id="swal-status" value="${currentStatus}">
                        ${statusCardsHtml}
                        <label style="display:block;font-size:0.8rem;font-weight:600;color:#374151;margin:16px 0 8px;text-transform:uppercase;letter-spacing:0.05em;">
                            <i class="fas fa-sticky-note" style="margin-right:4px;color:#9ca3af;"></i> หมายเหตุ
                        </label>
                        <textarea id="swal-notes" rows="3" placeholder="ระบุหมายเหตุเพิ่มเติม (ไม่บังคับ)..."
                                  style="width:100%;padding:10px 14px;border:2px solid #e5e7eb;border-radius:10px;font-size:0.9rem;
                                         resize:vertical;transition:border-color 0.2s;outline:none;font-family:inherit;box-sizing:border-box;"
                                  onfocus="this.style.borderColor='#10b981'" onblur="this.style.borderColor='#e5e7eb'"></textarea>
                    </div>`,
                width: 440,
                padding: '24px',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save" style="margin-right:6px;"></i> บันทึกสถานะ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                customClass: {
                    popup: 'swal-status-popup',
                    confirmButton: 'swal-status-confirm-btn',
                    cancelButton: 'swal-status-cancel-btn'
                },
                focusConfirm: false,
                didOpen: () => {
                    // Add hover effects
                    document.querySelectorAll('.swal-status-card').forEach(card => {
                        card.addEventListener('mouseenter', function() {
                            if (!this.classList.contains('active')) {
                                this.style.borderColor = '#d1d5db';
                                this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
                            }
                        });
                        card.addEventListener('mouseleave', function() {
                            if (!this.classList.contains('active')) {
                                this.style.borderColor = '#e5e7eb';
                                this.style.boxShadow = 'none';
                            }
                        });
                    });
                },
                preConfirm: () => {
                    const status = document.getElementById('swal-status').value;
                    if (!status) {
                        Swal.showValidationMessage('กรุณาเลือกสถานะ');
                        return false;
                    }
                    return {
                        status: status,
                        admin_notes: document.getElementById('swal-notes').value
                    };
                }
            });

            if (formValues) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_status');
                    formData.append('id', id);
                    formData.append('status', formValues.status);
                    formData.append('admin_notes', formValues.admin_notes);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('สำเร็จ', result.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถอัปเดตสถานะได้', 'error');
                }
            }
        }

        // Assign Request - Open Modal with Role-Based Filtering
        async function assignRequest(id) {
            if (!canAssignTasks) {
                Swal.fire('ไม่มีสิทธิ์', 'คุณไม่มีสิทธิ์ในการมอบหมายงาน', 'warning');
                return;
            }

            document.getElementById('assignRequestId').value = id;
            document.getElementById('assignRequestCode').textContent = '#' + String(id).padStart(4, '0');
            document.getElementById('assignRole').value = '';
            document.getElementById('assignPriority').value = 'normal';
            document.getElementById('assignDueDate').value = '';
            document.getElementById('assignNotes').value = '';

            // Get service code and load available users
            try {
                // Find the row with this request ID
                const row = document.querySelector(`tr[data-id="${id}"]`);
                if (!row) {
                    Swal.fire('ข้อผิดพลาด', 'ไม่พบข้อมูลคำขอ', 'error');
                    return;
                }

                const serviceCode = row.dataset.service;
                document.getElementById('assignServiceCode').value = serviceCode;

                // Load available users based on service code
                await loadAvailableUsers(id, serviceCode);

                // Load current assignments
                await loadCurrentAssignments(id);

                document.getElementById('assignModal').style.display = 'block';
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลได้', 'error');
            }
        }

        // Load available users based on service code and required roles
        async function loadAvailableUsers(requestId, serviceCode) {
            const userSelect = document.getElementById('assignUser');
            const serviceRoleInfo = document.getElementById('serviceRoleInfo');
            const requiredRolesText = document.getElementById('requiredRolesText');

            userSelect.innerHTML = '<option value="">-- กำลังโหลด --</option>';

            try {
                const response = await fetch(`api/task_assignment_api.php?action=get_available_users&service_code=${serviceCode}&request_id=${requestId}`);
                const data = await response.json();

                if (data.success) {
                    // Show required roles info
                    requiredRolesText.textContent = data.required_roles.join(', ');
                    serviceRoleInfo.classList.remove('hidden');

                    // Populate user select
                    userSelect.innerHTML = '<option value="">-- เลือกผู้รับผิดชอบ --</option>';
                    
                    if (data.users.length === 0) {
                        userSelect.innerHTML += '<option disabled>ไม่มีผู้ใช้ที่เหมาะสม</option>';
                        Swal.fire('แจ้งเตือน', data.message || 'ไม่มีผู้ใช้ที่มีบทบาท: ' + data.required_roles.join(', '), 'warning');
                        return;
                    }

                    data.users.forEach(user => {
                        const option = document.createElement('option');
                        option.value = user.user_id;
                        option.textContent = `${user.first_name} ${user.last_name} [@${user.username}] (${user.roles})`;
                        option.dataset.roles = user.roles;
                        userSelect.appendChild(option);
                    });
                } else {
                    userSelect.innerHTML += `<option disabled>${data.message}</option>`;
                    Swal.fire('แจ้งเตือน', data.message, 'warning');
                }
            } catch (error) {
                console.error('Error:', error);
                userSelect.innerHTML += '<option disabled>เกิดข้อผิดพลาด</option>';
                Swal.fire('ข้อผิดพลาด', 'ไม่สามารถโหลดข้อมูลผู้ใช้ได้', 'error');
            }
        }

        // Update user roles display when user is selected
        function updateSelectedUserRoles() {
            const userSelect = document.getElementById('assignUser');
            const userRolesInfo = document.getElementById('userRolesInfo');
            
            const selectedOption = userSelect.options[userSelect.selectedIndex];
            if (selectedOption && selectedOption.dataset.roles) {
                userRolesInfo.textContent = 'บทบาท: ' + selectedOption.dataset.roles;
            } else {
                userRolesInfo.textContent = '';
            }
        }

        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }

        // Load current assignments for a request
        async function loadCurrentAssignments(requestId) {
            const container = document.getElementById('assignmentsList');
            container.innerHTML = '<p class="text-gray-500 text-sm"><i class="fas fa-spinner fa-spin"></i> กำลังโหลด...</p>';

            // Status labels in Thai for assignments
            const assignStatusThai = {
                'pending': 'รอรับงาน',
                'accepted': 'รับงานแล้ว',
                'in_progress': 'กำลังดำเนินการ',
                'completed': 'เสร็จสิ้น',
                'cancelled': 'ยกเลิก'
            };

            try {
                const response = await fetch(`api/task_assignments_api.php?action=list_by_request&request_id=${requestId}`);
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    let html = '';
                    result.data.forEach(task => {
                        const statusClass = {
                            'pending': 'bg-yellow-100 text-yellow-800',
                            'accepted': 'bg-blue-100 text-blue-800',
                            'in_progress': 'bg-purple-100 text-purple-800',
                            'completed': 'bg-green-100 text-green-800',
                            'cancelled': 'bg-red-100 text-red-800'
                        }[task.status] || 'bg-gray-100 text-gray-800';

                        const priorityClass = {
                            'low': 'bg-gray-100 text-gray-700',
                            'normal': 'bg-blue-100 text-blue-700',
                            'high': 'bg-orange-100 text-orange-700',
                            'urgent': 'bg-red-100 text-red-700'
                        }[task.priority] || 'bg-gray-100 text-gray-700';

                        const canModify = !['completed', 'cancelled'].includes(task.status);
                        html += `
                            <div class="flex items-center justify-between p-3 bg-white border rounded-lg shadow-sm">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-gray-200 flex items-center justify-center">
                                        <i class="fas ${task.role_icon || 'fa-user'}" style="color: ${task.role_color || '#6b7280'}"></i>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">
                                            ${task.assigned_to_name || task.assigned_to_username}
                                            <span class="text-gray-400 font-normal text-xs">@${task.assigned_to_username}</span>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            ${task.assigned_role_name || 'ไม่ระบุบทบาท'}
                                            <span class="mx-1">•</span>
                                            มอบหมายโดย ${task.assigned_by_name || task.assigned_by_username}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs rounded-full ${priorityClass}">${priorityLabelsThai[task.priority] || task.priority}</span>
                                    <span class="px-2 py-1 text-xs rounded-full ${statusClass}">${assignStatusThai[task.status] || task.status}</span>
                                    ${canModify ? `
                                    <button onclick="reassignAssignment(${task.assignment_id}, '${(task.assigned_to_name || task.assigned_to_username).replace(/'/g, "\\'")}')" class="p-1 text-blue-500 hover:bg-blue-50 rounded" title="เปลี่ยนผู้รับ">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <button onclick="cancelAssignment(${task.assignment_id})" class="p-1 text-red-500 hover:bg-red-50 rounded" title="ยกเลิก">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    });
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<p class="text-gray-500 text-sm py-2">ยังไม่มีการมอบหมายงาน</p>';
                }
            } catch (error) {
                container.innerHTML = '<p class="text-red-500 text-sm">ไม่สามารถโหลดข้อมูลได้</p>';
            }
        }

        // Load assignable users by role
        async function loadAssignableUsers() {
            const roleId = document.getElementById('assignRole').value;
            const userSelect = document.getElementById('assignUser');

            if (!roleId) {
                userSelect.innerHTML = '<option value="">-- เลือกผู้รับผิดชอบ --</option>';
                return;
            }

            userSelect.innerHTML = '<option value="">-- กำลังโหลด --</option>';

            try {
                const response = await fetch(`api/task_assignments_api.php?action=get_assignable_users&role_id=${roleId}`);
                const result = await response.json();

                if (result.success) {
                    let options = '<option value="">-- เลือกผู้รับผิดชอบ --</option>';
                    result.data.forEach(user => {
                        const name = user.prefix_name ?
                            `${user.prefix_name}${user.first_name} ${user.last_name}` :
                            `${user.first_name} ${user.last_name}`;
                        const dept = user.department_name ? ` (${user.department_name})` : '';
                        options += `<option value="${user.user_id}">${name}${dept}</option>`;
                    });
                    userSelect.innerHTML = options;
                } else {
                    userSelect.innerHTML = '<option value="">ไม่พบผู้ใช้</option>';
                }
            } catch (error) {
                userSelect.innerHTML = '<option value="">เกิดข้อผิดพลาด</option>';
            }
        }

        // Submit new assignment
        async function submitAssignment() {
            const requestId = document.getElementById('assignRequestId').value;
            const roleId = document.getElementById('assignRole').value;
            const userId = document.getElementById('assignUser').value;
            const priority = document.getElementById('assignPriority').value;
            const dueDate = document.getElementById('assignDueDate').value;
            const notes = document.getElementById('assignNotes').value;
            const serviceCode = document.getElementById('assignServiceCode').value;

            if (!userId) {
                Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกผู้รับผิดชอบ', 'warning');
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'assign_task');
                formData.append('request_id', requestId);
                formData.append('assigned_to', userId);
                formData.append('assigned_as_role', roleId);
                formData.append('priority', priority);
                formData.append('due_date', dueDate);
                formData.append('notes', notes);

                const response = await fetch('api/task_assignment_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire('สำเร็จ', result.message, 'success');
                    // Reload assignments
                    await loadCurrentAssignments(requestId);
                    // Clear form
                    document.getElementById('assignRole').value = '';
                    document.getElementById('assignUser').value = '';
                    document.getElementById('userRolesInfo').textContent = '';
                    document.getElementById('assignNotes').value = '';
                } else {
                    Swal.fire('ผิดพลาด', result.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('ผิดพลาด', 'ไม่สามารถมอบหมายงานได้', 'error');
            }
        }

        // Cancel assignment
        async function cancelAssignment(assignmentId) {
            const result = await Swal.fire({
                title: 'ยืนยันการยกเลิก',
                text: 'ต้องการยกเลิกการมอบหมายงานนี้?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ยกเลิกงาน',
                cancelButtonText: 'ไม่ใช่'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'cancel');
                    formData.append('assignment_id', assignmentId);

                    const response = await fetch('api/task_assignments_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('ยกเลิกสำเร็จ', data.message, 'success');
                        const requestId = document.getElementById('assignRequestId').value;
                        await loadCurrentAssignments(requestId);
                    } else {
                        Swal.fire('ผิดพลาด', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถยกเลิกได้', 'error');
                }
            }
        }

        // Reassign assignment to different user
        async function reassignAssignment(assignmentId, currentName) {
            const requestId = document.getElementById('assignRequestId').value;
            const serviceCode = document.getElementById('assignServiceCode').value;

            // Load available users
            let usersOptions = '';
            try {
                const response = await fetch(`api/task_assignment_api.php?action=get_available_users&service_code=${serviceCode}&request_id=${requestId}`);
                const data = await response.json();
                if (data.success) {
                    data.users.forEach(user => {
                        usersOptions += `<option value="${user.user_id}">${user.first_name} ${user.last_name} [@${user.username}] (${user.roles})</option>`;
                    });
                }
            } catch (e) { /* ignore */ }

            const { value: formValues } = await Swal.fire({
                title: 'เปลี่ยนผู้รับผิดชอบ',
                width: 420,
                html:
                    `<p style="font-size:0.85rem;color:#6b7280;margin-bottom:0.75rem;">ปัจจุบัน: <strong>${currentName}</strong></p>` +
                    '<label style="display:block;font-size:0.8rem;font-weight:500;text-align:left;margin-bottom:4px;">ผู้รับผิดชอบใหม่</label>' +
                    '<select id="swal-reassign-user" style="width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:0.8rem;margin:0;">' +
                    '<option value="">-- เลือกผู้รับผิดชอบ --</option>' +
                    usersOptions +
                    '</select>' +
                    '<label style="display:block;font-size:0.8rem;font-weight:500;text-align:left;margin:10px 0 4px;">หมายเหตุ</label>' +
                    '<textarea id="swal-reassign-notes" rows="2" style="width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:0.8rem;resize:vertical;margin:0;" placeholder="เหตุผลในการเปลี่ยน..."></textarea>',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonColor: '#0d9488',
                confirmButtonText: '<i class="fas fa-exchange-alt mr-1"></i> เปลี่ยน',
                cancelButtonText: 'ยกเลิก',
                preConfirm: () => {
                    const userId = document.getElementById('swal-reassign-user').value;
                    if (!userId) {
                        Swal.showValidationMessage('กรุณาเลือกผู้รับผิดชอบใหม่');
                        return false;
                    }
                    return {
                        assigned_to: userId,
                        notes: document.getElementById('swal-reassign-notes').value
                    };
                }
            });

            if (!formValues) return;

            try {
                const formData = new FormData();
                formData.append('action', 'reassign');
                formData.append('assignment_id', assignmentId);
                formData.append('assigned_to', formValues.assigned_to);
                formData.append('notes', formValues.notes);

                const response = await fetch('api/task_assignments_api.php', { method: 'POST', body: formData });
                const result = await response.json();

                if (result.success) {
                    Swal.fire('สำเร็จ', result.message, 'success');
                    await loadCurrentAssignments(requestId);
                } else {
                    Swal.fire('ผิดพลาด', result.message, 'error');
                }
            } catch (error) {
                Swal.fire('ผิดพลาด', 'ไม่สามารถเปลี่ยนผู้รับผิดชอบได้', 'error');
            }
        }

        // Legacy Assign Request (simple assignment to service_requests.assigned_to)
        async function legacyAssignRequest(id) {
            const usersOptions = users.map(u =>
                `<option value="${u.user_id}">${u.first_name} ${u.last_name}</option>`
            ).join('');

            const { value: userId } = await Swal.fire({
                title: 'มอบหมายคำขอ',
                html:
                    '<select id="swal-user" class="swal2-input">' +
                    '<option value="">-- เลือกผู้รับผิดชอบ --</option>' +
                    usersOptions +
                    '</select>',
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'มอบหมาย',
                cancelButtonText: 'ยกเลิก',
                preConfirm: () => {
                    return document.getElementById('swal-user').value;
                }
            });

            if (userId !== undefined) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'assign');
                    formData.append('id', id);
                    formData.append('assigned_to', userId);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('สำเร็จ', result.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถมอบหมายคำขอได้', 'error');
                }
            }
        }

        // Update Priority
        async function updatePriority(id) {
            const { value: priority } = await Swal.fire({
                title: 'อัปเดตความสำคัญ',
                input: 'select',
                inputOptions: {
                    'low': 'ต่ำ',
                    'medium': 'ปานกลาง',
                    'high': 'สูง',
                    'urgent': 'เร่งด่วน'
                },
                inputPlaceholder: 'เลือกความสำคัญ',
                showCancelButton: true,
                confirmButtonText: 'อัปเดต',
                cancelButtonText: 'ยกเลิก'
            });

            if (priority) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'update_priority');
                    formData.append('id', id);
                    formData.append('priority', priority);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const result = await response.json();

                    if (result.success) {
                        Swal.fire('สำเร็จ', result.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', result.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถอัปเดตความสำคัญได้', 'error');
                }
            }
        }

        // Delete Request
        async function deleteRequest(id) {
            const result = await Swal.fire({
                title: 'ยืนยันการลบ?',
                text: "การดำเนินการนี้จะลบคำขอและข้อมูลที่เกี่ยวข้องทั้งหมดอย่างถาวร!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'ใช่ ลบเลย!',
                cancelButtonText: 'ยกเลิก'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    const response = await fetch('api/service_requests_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('ลบแล้ว!', data.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('ผิดพลาด', data.message, 'error');
                    }
                } catch (error) {
                    Swal.fire('ผิดพลาด', 'ไม่สามารถลบคำขอได้', 'error');
                }
            }
        }

        // Bulk Actions
        async function executeBulkAction() {
            const action = document.getElementById('bulkActionSelect').value;
            const checkedBoxes = document.querySelectorAll('.request-checkbox:checked');
            const ids = Array.from(checkedBoxes).map(cb => cb.value);

            if (!action) {
                Swal.fire('ผิดพลาด', 'กรุณาเลือกการดำเนินการ', 'error');
                return;
            }

            if (ids.length === 0) {
                Swal.fire('ผิดพลาด', 'กรุณาเลือกอย่างน้อย 1 คำขอ', 'error');
                return;
            }

            if (action === 'update_status') {
                const { value: status } = await Swal.fire({
                    title: `อัปเดตสถานะ ${ids.length} คำขอ`,
                    input: 'select',
                    inputOptions: {
                        'pending': 'รอดำเนินการ',
                        'in_progress': 'กำลังดำเนินการ',
                        'completed': 'เสร็จสิ้น',
                        'cancelled': 'ยกเลิก'
                    },
                    inputPlaceholder: 'เลือกสถานะ',
                    showCancelButton: true,
                    confirmButtonText: 'อัปเดตทั้งหมด',
                    cancelButtonText: 'ยกเลิก'
                });

                if (status) {
                    try {
                        const formData = new FormData();
                        formData.append('action', 'bulk_update_status');
                        formData.append('ids', JSON.stringify(ids));
                        formData.append('status', status);

                        const response = await fetch('api/service_requests_api.php', {
                            method: 'POST',
                            body: formData
                        });

                        const result = await response.json();

                        if (result.success) {
                            Swal.fire('สำเร็จ', result.message, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('ผิดพลาด', result.message, 'error');
                        }
                    } catch (error) {
                        Swal.fire('ผิดพลาด', 'ไม่สามารถอัปเดตสถานะได้', 'error');
                    }
                }
            } else if (action === 'delete') {
                const result = await Swal.fire({
                    title: 'ยืนยันการลบ?',
                    text: `การดำเนินการนี้จะลบ ${ids.length} คำขอ และข้อมูลที่เกี่ยวข้องทั้งหมดอย่างถาวร!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'ใช่ ลบทั้งหมด!',
                    cancelButtonText: 'ยกเลิก'
                });

                if (result.isConfirmed) {
                    try {
                        let successCount = 0;
                        let errorCount = 0;

                        for (const id of ids) {
                            const formData = new FormData();
                            formData.append('action', 'delete');
                            formData.append('id', id);

                            const response = await fetch('api/service_requests_api.php', {
                                method: 'POST',
                                body: formData
                            });

                            const data = await response.json();
                            if (data.success) {
                                successCount++;
                            } else {
                                errorCount++;
                            }
                        }

                        if (errorCount === 0) {
                            Swal.fire('สำเร็จ', `ลบ ${successCount} คำขอเรียบร้อยแล้ว`, 'success').then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire('บางส่วนสำเร็จ', `ลบได้ ${successCount} คำขอ, ล้มเหลว ${errorCount} คำขอ`, 'warning').then(() => {
                                location.reload();
                            });
                        }
                    } catch (error) {
                        Swal.fire('ผิดพลาด', 'ไม่สามารถลบคำขอได้', 'error');
                    }
                }
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const detailsModal = document.getElementById('detailsModal');
            const assignModal = document.getElementById('assignModal');
            if (event.target == detailsModal) {
                detailsModal.style.display = 'none';
            }
            if (event.target == assignModal) {
                assignModal.style.display = 'none';
            }
        }
    </script>
</main>

<?php
include 'admin-layout/footer.php';
$conn->close();
?>
