<?php
/**
 * Staff Dashboard - View Assigned Tasks
 * สำหรับผู้ที่ไม่ใช่ manager เพื่อดูงานที่ได้รับมอบหมาย
 */

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Build $user array for topbar.php
$user = [
    'username' => $_SESSION['username'] ?? 'User',
    'email' => $_SESSION['email'] ?? '',
    'full_name' => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User',
    'first_name' => $_SESSION['first_name'] ?? 'User'
];

// Manager/all users can also access my_tasks (they may be assigned tasks too)

// Get user roles
$roles_query = "SELECT r.role_code, r.role_name, r.role_icon, r.role_color
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.role_id
                WHERE ur.user_id = ? AND ur.is_active = 1 AND r.is_active = 1
                ORDER BY ur.is_primary DESC";
$roles_stmt = $conn->prepare($roles_query);
$roles_stmt->bind_param('i', $user_id);
$roles_stmt->execute();
$roles_result = $roles_stmt->get_result();

$user_roles = [];
$primary_role = null;
while ($row = $roles_result->fetch_assoc()) {
    $user_roles[] = $row;
    if (!$primary_role) {
        $primary_role = $row;
    }
}

// Get assigned tasks statistics
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM task_assignments
WHERE assigned_to = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param('i', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get assigned tasks
$tasks_query = "SELECT ta.*, sr.request_code, sr.service_name, sr.requester_name, sr.requester_phone, sr.requester_email,
                u_by.username as assigned_by_username,
                CONCAT(p_by.prefix_name, u_by.first_name, ' ', u_by.last_name) as assigned_by_name,
                r.role_name as assigned_role_name
                FROM task_assignments ta
                JOIN service_requests sr ON ta.request_id = sr.request_id
                JOIN users u_by ON ta.assigned_by = u_by.user_id
                LEFT JOIN prefixes p_by ON u_by.prefix_id = p_by.prefix_id
                LEFT JOIN roles r ON ta.assigned_as_role = r.role_id
                WHERE ta.assigned_to = ?
                ORDER BY 
                    CASE ta.status 
                        WHEN 'pending' THEN 1
                        WHEN 'accepted' THEN 2
                        WHEN 'in_progress' THEN 3
                        WHEN 'completed' THEN 4
                    END,
                    ta.due_date ASC";

$tasks_stmt = $conn->prepare($tasks_query);
$tasks_stmt->bind_param('i', $user_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();

// Fetch all tasks into array for use in both list and calendar views
$all_tasks = [];
while ($task = $tasks_result->fetch_assoc()) {
    $all_tasks[] = $task;
}

$page_title = 'งานของฉัน';
$current_page = 'my_tasks';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'งานของฉัน']
];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
/* ── Status badges ─────────────────────────── */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-accepted { background-color: #dbeafe; color: #1e40af; }
    .status-in_progress { background-color: #c7d2fe; color: #3730a3; }
    .status-completed { background-color: #dcfce7; color: #15803d; }

    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
    }

    .stat-card .count {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
    }

    .stat-card .label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.5rem;
    }

    .task-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.2s ease;
    }

    .task-card:hover {
        border-color: #d1d5db;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .task-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }

    .task-code {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #1f2937;
        font-size: 1.125rem;
    }

    .task-service {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .task-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin: 1rem 0;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .task-detail-item {
        font-size: 0.875rem;
    }

    .task-detail-label {
        color: #9ca3af;
        font-weight: 500;
    }

    .task-detail-value {
        color: #1f2937;
        margin-top: 0.25rem;
    }

    /* Simple List View Styles */
    .task-simple-list {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .task-simple-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .task-simple-item:hover {
        border-color: #d1d5db;
        box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        background-color: #f9fafb;
    }

    .task-simple-left {
        flex: 1;
    }

    .task-code-simple {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #1f2937;
        font-size: 0.95rem;
    }

    .task-service-simple {
        color: #6b7280;
        font-size: 0.8rem;
        margin-top: 0.25rem;
    }

    .task-requester-simple {
        color: #1f2937;
        font-size: 0.85rem;
        margin-top: 0.25rem;
    }

    .task-simple-right {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 0.5rem;
        margin-left: 1rem;
    }

    .task-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-status {
        flex: 1;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-accept {
        background-color: #dbeafe;
        color: #0c4a6e;
    }

    .btn-accept:hover {
        background-color: #bfdbfe;
    }

    .btn-start {
        background-color: #c7d2fe;
        color: #312e81;
    }

    .btn-start:hover {
        background-color: #a5b4fc;
    }

    .btn-complete {
        background-color: #dcfce7;
        color: #166534;
    }

    .btn-complete:hover {
        background-color: #bbf7d0;
    }

    .btn-disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .no-tasks {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }

    .no-tasks i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }

    /* Tab Styles */
    .tab-container {
        background: white;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .tab-buttons {
        display: flex;
        border-bottom: 2px solid #e5e7eb;
        background-color: #f9fafb;
    }

    .tab-button {
        flex: 1;
        padding: 1rem;
        text-align: center;
        font-weight: 500;
        color: #6b7280;
        cursor: pointer;
        border: none;
        background: none;
        transition: all 0.2s;
        font-size: 1rem;
    }

    .tab-button.active {
        color: #3b82f6;
        border-bottom: 3px solid #3b82f6;
        margin-bottom: -2px;
        background-color: white;
    }

    .tab-button:hover {
        color: #1f2937;
    }

    .tab-content {
        display: none;
        padding: 1.5rem;
    }

    .tab-content.active {
        display: block;
    }

    /* View Toggle */
    .view-toggle {
        display: flex;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .view-toggle button {
        padding: 0.5rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        background: white;
        cursor: pointer;
        transition: all 0.2s;
    }

    .view-toggle button.active {
        background: #3b82f6;
        color: white;
        border-color: #3b82f6;
    }

    /* Grid View */
    .task-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1rem;
    }

    .task-grid-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .task-grid-card:hover {
        border-color: #3b82f6;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    }

    .task-grid-header {
        margin-bottom: 1rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }

    /* Calendar Styles */
    .calendar-wrapper {
        max-width: 900px;
        margin: 0 auto;
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        padding: 1rem;
        background: #f9fafb;
        border-radius: 0.75rem;
    }

    .calendar-header button {
        padding: 0.5rem 1rem;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 0.375rem;
        cursor: pointer;
        font-weight: 500;
    }

    .calendar-header button:hover {
        background: #2563eb;
    }

    .calendar-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        min-height: 400px;
    }

    .calendar-table th {
        background: #f3f4f6;
        padding: 1rem;
        text-align: center;
        font-weight: 600;
        color: #374151;
    }

    .calendar-table td {
        padding: 0.5rem;
        border: 1px solid #e5e7eb;
        height: 120px;
        vertical-align: top;
        position: relative;
        cursor: pointer;
        transition: background 0.2s;
    }

    .calendar-table td:hover {
        background: #f9fafb;
    }

    .calendar-day-number {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 0.25rem;
    }

    .calendar-day-tasks {
        font-size: 0.75rem;
        margin-top: 0.25rem;
    }

    .calendar-task-item {
        background: #dbeafe;
        color: #0c4a6e;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        margin-bottom: 0.25rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .calendar-task-item.status-pending { background: #fef3c7; color: #92400e; }
    .calendar-task-item.status-accepted { background: #dbeafe; color: #0c4a6e; }
    .calendar-task-item.status-in_progress { background: #c7d2fe; color: #3730a3; }
    .calendar-task-item.status-completed { background: #dcfce7; color: #166534; }

    .calendar-table td.other-month {
        background: #f9fafb;
        color: #d1d5db;
    }

    .calendar-table td.today {
        background: #fef2f2;
    }

    /* Day Details */
    .day-details {
        margin-top: 1.5rem;
        padding: 1.5rem;
        background: #f9fafb;
        border-radius: 0.75rem;
    }

    .day-details-title {
        font-size: 1.5rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 1rem;
    }

    .day-details-empty {
        text-align: center;
        color: #9ca3af;
        padding: 2rem;
    }

    /* Table Styles - Custom minimal design */
    .tasks-table-container {
        background: white;
        border-radius: 0.5rem;
        overflow: hidden;
    }

    #tasksTable {
        width: 100%;
        border-collapse: collapse;
    }

    #tasksTable thead {
        background-color: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }

    #tasksTable th {
        padding: 0.875rem 1rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.75rem;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }

    #tasksTable tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: background-color 0.15s ease;
    }

    #tasksTable tbody tr:hover {
        background-color: #f9fafb;
    }

    #tasksTable td {
        padding: 1rem;
        font-size: 0.875rem;
        color: #374151;
    }

    .btn-action-small {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.15s ease;
        background-color: transparent;
        color: #9ca3af;
    }

    .btn-action-small:hover {
        background-color: #f3f4f6;
        color: #3b82f6;
    }

    /* Search and Filter Styles */
    .table-controls {
        margin-bottom: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .table-search-box {
        padding: 0.5rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        transition: border-color 0.15s;
        flex: 1;
        min-width: 200px;
    }

    .table-search-box:focus {
        outline: none;
        border-color: #3b82f6;
    }

    /* Pagination */
    .table-pagination {
        margin-top: 1.5rem;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .pagination-btn {
        padding: 0.5rem 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        background-color: white;
        color: #374151;
        cursor: pointer;
        font-size: 0.875rem;
        transition: all 0.15s;
        min-width: 2.5rem;
        text-align: center;
    }

    .pagination-btn:hover:not(:disabled) {
        background-color: #f3f4f6;
        border-color: #d1d5db;
    }

    .pagination-btn.active {
        background-color: #3b82f6;
        color: white;
        border-color: #3b82f6;
        font-weight: 600;
    }

    .pagination-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .table-info {
        margin-top: 1rem;
        color: #6b7280;
        font-size: 0.875rem;
    }
</style>

<!-- ── Page Header (mobile-compact) ──────────────────────────── -->
<div class="flex items-center justify-between mb-4 px-1">
    <div>
        <h1 class="text-2xl font-bold text-gray-800 leading-tight">
            <i class="fas fa-tasks text-teal-600 mr-1"></i> งานของฉัน
        </h1>
        <div class="flex flex-wrap gap-1 mt-1">
            <?php foreach ($user_roles as $role): ?>
            <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-gray-100 rounded-full text-xs text-gray-600">
                <i class="fas <?= $role['role_icon'] ?> text-xs" style="color:<?= $role['role_color'] ?>"></i>
                <?= htmlspecialchars($role['role_name']) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ── Stats strip (horizontal scroll on mobile) ──────────────── -->
<div class="flex gap-3 overflow-x-auto pb-1 mb-5 snap-x" style="scrollbar-width:none;">
    <?php
    $stat_items = [
        ['count'=>$stats['total']??0,       'label'=>'ทั้งหมด',          'bg'=>'bg-gray-50',    'text'=>'text-gray-700',   'icon'=>'fa-layer-group',   'ring'=>'ring-gray-200'],
        ['count'=>$stats['pending']??0,     'label'=>'รอรับงาน',         'bg'=>'bg-amber-50',   'text'=>'text-amber-700',  'icon'=>'fa-clock',         'ring'=>'ring-amber-200'],
        ['count'=>$stats['accepted']??0,    'label'=>'รับงานแล้ว',       'bg'=>'bg-blue-50',    'text'=>'text-blue-700',   'icon'=>'fa-check-circle',  'ring'=>'ring-blue-200'],
        ['count'=>$stats['in_progress']??0, 'label'=>'กำลังดำเนินการ',   'bg'=>'bg-purple-50',  'text'=>'text-purple-700', 'icon'=>'fa-spinner',       'ring'=>'ring-purple-200'],
        ['count'=>$stats['completed']??0,   'label'=>'เสร็จสิ้น',        'bg'=>'bg-green-50',   'text'=>'text-green-700',  'icon'=>'fa-check-double',  'ring'=>'ring-green-200'],
    ];
    foreach ($stat_items as $s):
    ?>
    <div class="flex-shrink-0 snap-start flex items-center gap-3 px-4 py-3 rounded-2xl ring-1 <?= $s['bg'].' '.$s['ring'] ?>" style="min-width:130px;">
        <div class="w-10 h-10 rounded-xl flex items-center justify-center <?= $s['text'] ?>" style="background:rgba(0,0,0,.05);">
            <i class="fas <?= $s['icon'] ?> text-lg"></i>
        </div>
        <div>
            <p class="text-2xl font-bold <?= $s['text'] ?> leading-none"><?= $s['count'] ?></p>
            <p class="text-xs <?= $s['text'] ?> opacity-70 mt-0.5"><?= $s['label'] ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- ── Tab Container ─────────────────────────────────────────── -->
<div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-200 overflow-hidden">
    <!-- Tab buttons -->
    <div class="flex border-b border-gray-100">
        <button class="tab-button active flex-1 flex items-center justify-center gap-2 py-3.5 text-sm font-semibold" onclick="switchTab('list-view', this)">
            <i class="fas fa-list-ul"></i> รายการ
        </button>
        <button class="tab-button flex-1 flex items-center justify-center gap-2 py-3.5 text-sm font-semibold" onclick="switchTab('calendar-view', this)">
            <i class="fas fa-calendar-alt"></i> ปฏิทิน
        </button>
    </div>

    <!-- ── LIST VIEW ──────────────────────────────────────────── -->
    <div id="list-view" class="tab-content active">
        <?php if (!empty($all_tasks)): ?>

        <!-- Search -->
        <div class="p-3 border-b border-gray-50">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="cardSearch"
                       class="w-full pl-9 pr-4 py-2.5 rounded-xl bg-gray-50 border border-gray-200 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 focus:bg-white transition"
                       placeholder="ค้นหารหัส, บริการ, ผู้ขอ...">
            </div>
        </div>

        <!-- Task Cards -->
        <div class="p-3 space-y-2.5" id="taskCardList">
        <?php
        $svc_icons = [
            'PHOTOGRAPHY'=>'fa-camera','MC'=>'fa-microphone','IT_SUPPORT'=>'fa-desktop',
            'EMAIL'=>'fa-envelope','NAS'=>'fa-database','WEB_DESIGN'=>'fa-globe',
            'PRINTER'=>'fa-print','QR_CODE'=>'fa-qrcode','INTERNET'=>'fa-wifi','LED'=>'fa-tv',
        ];
        $status_cfg = [
            'pending'    =>['color'=>'#f59e0b','bg'=>'#fffbeb','badge'=>'bg-amber-100 text-amber-800',  'label'=>'รอรับงาน',       'icon'=>'fa-clock'],
            'accepted'   =>['color'=>'#3b82f6','bg'=>'#eff6ff','badge'=>'bg-blue-100 text-blue-800',    'label'=>'รับงานแล้ว',     'icon'=>'fa-check-circle'],
            'in_progress'=>['color'=>'#8b5cf6','bg'=>'#f5f3ff','badge'=>'bg-purple-100 text-purple-800','label'=>'กำลังดำเนินการ','icon'=>'fa-spinner'],
            'completed'  =>['color'=>'#10b981','bg'=>'#f0fdf4','badge'=>'bg-green-100 text-green-800',  'label'=>'เสร็จสิ้น',      'icon'=>'fa-check-double'],
            'cancelled'  =>['color'=>'#6b7280','bg'=>'#f9fafb','badge'=>'bg-gray-100 text-gray-600',   'label'=>'ยกเลิก',         'icon'=>'fa-ban'],
        ];
        foreach ($all_tasks as $task):
            $sc  = $status_cfg[$task['status']] ?? $status_cfg['cancelled'];
            $sic = $svc_icons[$task['service_code'] ?? ''] ?? 'fa-concierge-bell';
            $search_str = strtolower($task['request_code'].' '.$task['service_name'].' '.$task['requester_name']);
        ?>
        <div class="task-card-item rounded-2xl overflow-hidden shadow-sm ring-1 ring-gray-100 bg-white"
             data-search="<?= htmlspecialchars($search_str) ?>">
            <div class="flex">
                <!-- Left color strip -->
                <div class="w-1.5 flex-shrink-0" style="background:<?= $sc['color'] ?>;"></div>

                <!-- Card body -->
                <div class="flex-1 p-4 min-w-0">

                    <!-- Row 1: service icon + name + status badge -->
                    <div class="flex items-start gap-2.5 mb-2">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 mt-0.5"
                             style="background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>;">
                            <i class="fas <?= $sic ?> text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-bold text-gray-900 text-[15px] leading-snug truncate">
                                <?= htmlspecialchars($task['service_name']) ?>
                            </p>
                            <p class="text-xs font-mono text-gray-400 mt-0.5"><?= htmlspecialchars($task['request_code']) ?></p>
                        </div>
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold flex-shrink-0 <?= $sc['badge'] ?>">
                            <i class="fas <?= $sc['icon'] ?> text-[10px]"></i>
                            <?= $sc['label'] ?>
                        </span>
                    </div>

                    <!-- Row 2: requester + due date -->
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span class="flex items-center gap-1 truncate">
                            <i class="fas fa-user-circle text-gray-300"></i>
                            <?= htmlspecialchars($task['requester_name']) ?>
                        </span>
                        <?php if ($task['due_date']): ?>
                        <span class="flex items-center gap-1 flex-shrink-0 ml-2 font-medium" style="color:<?= $sc['color'] ?>;">
                            <i class="fas fa-calendar-alt text-[10px]"></i>
                            <?= thdate('d/m/Y', strtotime($task['due_date'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Row 3: action buttons -->
                    <div class="flex gap-2">
                        <!-- VIEW DETAIL — big prominent button -->
                        <a href="task_detail.php?assignment_id=<?= $task['assignment_id'] ?>"
                           class="flex-1 flex items-center justify-center gap-2 py-2.5 rounded-xl font-bold text-sm transition-all active:scale-95"
                           style="background:<?= $sc['bg'] ?>; color:<?= $sc['color'] ?>; border:1.5px solid <?= $sc['color'] ?>40;">
                            <i class="fas fa-eye text-base"></i>
                            <span>ดูรายละเอียด</span>
                        </a>

                        <!-- STATUS ACTION button -->
                        <?php if ($task['status'] === 'pending'): ?>
                        <button onclick="updateTaskStatus(<?= $task['assignment_id'] ?>, 'accepted')"
                                class="flex items-center gap-1.5 py-2.5 px-4 rounded-xl font-bold text-sm text-white transition-all active:scale-95"
                                style="background:#3b82f6;">
                            <i class="fas fa-hand-paper"></i>
                            <span class="hidden sm:inline">รับงาน</span>
                        </button>
                        <?php elseif ($task['status'] === 'accepted'): ?>
                        <button onclick="updateTaskStatus(<?= $task['assignment_id'] ?>, 'in_progress')"
                                class="flex items-center gap-1.5 py-2.5 px-4 rounded-xl font-bold text-sm text-white transition-all active:scale-95"
                                style="background:#8b5cf6;">
                            <i class="fas fa-play"></i>
                            <span class="hidden sm:inline">เริ่มงาน</span>
                        </button>
                        <?php elseif ($task['status'] === 'in_progress'): ?>
                        <button onclick="updateTaskStatus(<?= $task['assignment_id'] ?>, 'completed')"
                                class="flex items-center gap-1.5 py-2.5 px-4 rounded-xl font-bold text-sm text-white transition-all active:scale-95"
                                style="background:#10b981;">
                            <i class="fas fa-check"></i>
                            <span class="hidden sm:inline">เสร็จสิ้น</span>
                        </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>

        <div class="text-center text-xs text-gray-400 py-3" id="cardInfo"></div>

        <?php else: ?>
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                <i class="fas fa-inbox text-4xl text-gray-300"></i>
            </div>
            <p class="font-semibold text-gray-500">ยังไม่มีงานที่ได้รับมอบหมาย</p>
            <p class="text-sm text-gray-400 mt-1">รอให้ผู้จัดการมอบหมายงานให้คุณ</p>
        </div>
        <?php endif; ?>
    </div>

        <!-- Calendar View Tab -->
        <div id="calendar-view" class="tab-content">
            <div class="calendar-wrapper">
                <div class="calendar-header">
                    <button onclick="previousMonth()"><i class="fas fa-chevron-left"></i> ก่อนหน้า</button>
                    <h2 id="calendar-month-year" style="font-size: 1.25rem; font-weight: 600; color: #1f2937;"></h2>
                    <button onclick="nextMonth()">ถัดไป <i class="fas fa-chevron-right"></i></button>
                </div>

                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>จันทร์</th>
                            <th>อังคาร</th>
                            <th>พุธ</th>
                            <th>พฤหัสบดี</th>
                            <th>ศุกร์</th>
                            <th>เสาร์</th>
                            <th>อาทิตย์</th>
                        </tr>
                    </thead>
                    <tbody id="calendar-body">
                    </tbody>
                </table>

                <div id="day-details" style="display: none;">
                    <!-- Will be filled by JavaScript -->
                </div>
            </div>
        </div>
    </div>

<script>
    const tasksData = <?= json_encode($all_tasks) ?>;
    let currentMonth = new Date().getMonth();
    let currentYear  = new Date().getFullYear();

    // ── Tab switching ──────────────────────────────────────────────
    function switchTab(tabName, buttonElement) {
        document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.tab-button').forEach(b => b.classList.remove('active'));
        document.getElementById(tabName).classList.add('active');
        if (buttonElement) buttonElement.classList.add('active');
        if (tabName === 'calendar-view') renderCalendar();
    }

    // ── Card search ────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.getElementById('cardSearch');
        const cards       = document.querySelectorAll('.task-card-item');
        const info        = document.getElementById('cardInfo');
        if (!searchInput) return;
        searchInput.addEventListener('input', function () {
            const term = this.value.toLowerCase();
            let visible = 0;
            cards.forEach(card => {
                const match = !term || (card.dataset.search || '').includes(term);
                card.style.display = match ? '' : 'none';
                if (match) visible++;
            });
            if (info) info.textContent = term ? `พบ ${visible} รายการ` : '';
        });
    });

    // ── Calendar functions ─────────────────────────────────────────
    function renderCalendar() {
        console.log('renderCalendar called, currentMonth:', currentMonth, 'currentYear:', currentYear);
        
        const firstDay = new Date(currentYear, currentMonth, 1);
        const lastDay = new Date(currentYear, currentMonth + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = (firstDay.getDay() + 6) % 7; // Monday = 0

        console.log('daysInMonth:', daysInMonth, 'startingDayOfWeek:', startingDayOfWeek);

        // Update month/year display
        const monthNames = ['มกราคม', 'กุมภาพันธ์', 'มีนาคม', 'เมษายน', 'พฤษภาคม', 'มิถุนายน',
                           'กรกฎาคม', 'สิงหาคม', 'กันยายน', 'ตุลาคม', 'พฤศจิกายน', 'ธันวาคม'];
        document.getElementById('calendar-month-year').textContent = `${monthNames[currentMonth]} ${currentYear + 543}`;

        const calendarBody = document.getElementById('calendar-body');
        calendarBody.innerHTML = '';

        let dayCount = 1;
        let weekRow = document.createElement('tr');

        // Previous month days
        for (let i = 0; i < startingDayOfWeek; i++) {
            const cell = document.createElement('td');
            cell.classList.add('other-month');
            weekRow.appendChild(cell);
        }

        // Current month days
        const today = new Date();
        for (let day = 1; day <= daysInMonth; day++) {
            if (!weekRow) weekRow = document.createElement('tr');
            if (weekRow.children.length === 7) {
                calendarBody.appendChild(weekRow);
                weekRow = document.createElement('tr');
            }

            const cell = document.createElement('td');
            const cellDate = new Date(currentYear, currentMonth, day);
            
            // Format date as YYYY-MM-DD without timezone conversion
            const cellDateStr = cellDate.getFullYear() + '-' + 
                               String(cellDate.getMonth() + 1).padStart(2, '0') + '-' + 
                               String(cellDate.getDate()).padStart(2, '0');

            // Check if today
            if (cellDate.toDateString() === today.toDateString()) {
                cell.classList.add('today');
            }

            cell.innerHTML = `<div class="calendar-day-number">${day}</div>`;
            cell.classList.add('calendar-day');
            cell.dataset.date = cellDateStr;

            // Add tasks for this day
            const dayTasks = getTasksForDay(cellDate);
            if (dayTasks.length > 0) {
                const tasksDiv = document.createElement('div');
                tasksDiv.classList.add('calendar-day-tasks');
                dayTasks.forEach(task => {
                    const taskEl = document.createElement('div');
                    taskEl.classList.add('calendar-task-item', `status-${task.status}`);
                    taskEl.title = task.request_code;
                    taskEl.textContent = task.request_code.substring(0, 10) + '...';
                    tasksDiv.appendChild(taskEl);
                });
                cell.appendChild(tasksDiv);
            }

            cell.onclick = () => showDayDetails(cellDate, dayTasks);
            weekRow.appendChild(cell);
        }

        // Next month days
        if (weekRow && weekRow.children.length < 7) {
            while (weekRow.children.length < 7) {
                const cell = document.createElement('td');
                cell.classList.add('other-month');
                weekRow.appendChild(cell);
            }
        }
        if (weekRow) calendarBody.appendChild(weekRow);
    }

    function getTasksForDay(date) {
        // Format date as YYYY-MM-DD without timezone conversion
        const dateStr = date.getFullYear() + '-' + 
                       String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                       String(date.getDate()).padStart(2, '0');
        const tasks = [];

        // Get tasks from tasksData array
        tasksData.forEach(task => {
            let matchesDate = false;
            
            // Only use start_time for calendar display (for PHOTOGRAPHY, MC events with actual event dates)
            if (task.start_time) {
                // Extract date part directly from string to avoid timezone issues
                // start_time format: "2026-02-07 08:31:00"
                const taskDateStr = task.start_time.split(' ')[0]; // Get "2026-02-07" part
                if (taskDateStr === dateStr) {
                    matchesDate = true;
                }
            }
            
            if (matchesDate) {
                tasks.push({
                    assignment_id: task.assignment_id,
                    request_code: task.request_code,
                    status: task.status,
                    start_time: task.start_time,
                    end_time: task.end_time
                });
            }
        });

        return tasks;
    }

    function showDayDetails(date, tasks) {
        const dayDetailsDiv = document.getElementById('day-details');
        const dateStr = date.toLocaleDateString('th-TH', { year: 'numeric', month: 'long', day: 'numeric' });

        if (tasks.length === 0) {
            dayDetailsDiv.innerHTML = `<div class="day-details"><div class="day-details-empty">ไม่มีงานในวันนี้</div></div>`;
        } else {
            let html = `<div class="day-details"><div class="day-details-title">งานในวันที่ ${dateStr}</div>`;
            tasks.forEach(task => {
                // Format time display if available
                let timeDisplay = '';
                if (task.start_time) {
                    // Parse time from string "2026-02-07 08:31:00" to extract just time
                    const timeParts = task.start_time.split(' ')[1]; // Get "08:31:00"
                    const timeStr = timeParts.substring(0, 5); // Get "08:31"
                    timeDisplay = `<div style="font-size: 0.75rem; color: #6b7280; margin-top: 0.25rem;">⏰ เวลา: ${timeStr}`;
                    if (task.end_time) {
                        const endTimeParts = task.end_time.split(' ')[1]; // Get time part
                        const endTimeStr = endTimeParts.substring(0, 5); // Get time "HH:MM"
                        timeDisplay += ` - ${endTimeStr}`;
                    }
                    timeDisplay += '</div>';
                }
                
                html += `
                    <div class="task-card" onclick="event.stopPropagation()" style="border-left: 4px solid #0d9488;">
                        <div class="task-header">
                            <div>
                                <div class="task-code">${task.request_code}</div>
                                ${timeDisplay}
                            </div>
                            <span class="status-badge status-${task.status}">
                                ${['pending', 'รอรับงาน', 'accepted', 'รับงานแล้ว', 'in_progress', 'กำลังดำเนินการ', 'completed', 'เสร็จสิ้น'][
                                    ['pending', 'accepted', 'in_progress', 'completed'].indexOf(task.status) * 2 + 1
                                ] || task.status}
                            </span>
                        </div>
                        <a href="task_detail.php?assignment_id=${task.assignment_id}" class="btn-status" style="background-color: #dbeafe; color: #0c4a6e; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; padding: 0.5rem 1rem; margin-top: 1rem;">
                            <i class="fas fa-eye"></i> ดูรายละเอียด
                        </a>
                    </div>
                `;
            });
            html += `</div>`;
            dayDetailsDiv.innerHTML = html;
        }
        
        // Always show the div and scroll it into view
        dayDetailsDiv.style.display = 'block';
        dayDetailsDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function previousMonth() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    }

    function nextMonth() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    }

    // Task status update
    async function updateTaskStatus(assignmentId, newStatus) {
        const statusLabels = {
            'accepted': 'รับงาน',
            'in_progress': 'เริ่มดำเนินการ',
            'completed': 'ดำเนินการเสร็จ'
        };

        const result = await Swal.fire({
            title: 'ยืนยันการเปลี่ยนสถานะ',
            text: `คุณต้องการ ${statusLabels[newStatus]} ใช่หรือไม่?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        });

        if (!result.isConfirmed) return;

        try {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('assignment_id', assignmentId);
            formData.append('new_status', newStatus);

            const response = await fetch('api/task_assignment_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('สำเร็จ', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('ผิดพลาด', data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถอัปเดตสถานะได้', 'error');
        }
    }

    // Initialize table on page load
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('tableBody')) {
            initializeTable();
            window.tasksTableInitialized = true;
        }
    });

</script>

<?php include 'admin-layout/footer.php'; ?>
