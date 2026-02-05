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

// Check if user has manager/all role (redirect to admin dashboard)
$check_manager = $conn->prepare("
    SELECT COUNT(*) as cnt FROM user_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.user_id = ? AND r.role_code IN ('manager', 'all')
    AND ur.is_active = 1 AND r.is_active = 1
");
$check_manager->bind_param('i', $user_id);
$check_manager->execute();
$manager_result = $check_manager->get_result()->fetch_assoc();

if ($manager_result['cnt'] > 0) {
    header('Location: admin_dashboard.php');
    exit();
}

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

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-tasks text-blue-600"></i> งานของฉัน
        </h1>
        <p class="text-gray-600">
            บทบาท: 
            <?php foreach ($user_roles as $role): ?>
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 rounded text-sm ml-1">
                    <i class="fas <?= $role['role_icon'] ?>" style="color: <?= $role['role_color'] ?>"></i>
                    <?= htmlspecialchars($role['role_name']) ?>
                </span>
            <?php endforeach; ?>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="stat-card">
            <div class="count"><?= $stats['total'] ?? 0 ?></div>
            <div class="label">งานทั้งหมด</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['pending'] ?? 0 ?></div>
            <div class="label">รอรับงาน</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['accepted'] ?? 0 ?></div>
            <div class="label">รับงานแล้ว</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['in_progress'] ?? 0 ?></div>
            <div class="label">กำลังดำเนินการ</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['completed'] ?? 0 ?></div>
            <div class="label">เสร็จสิ้น</div>
        </div>
    </div>

    <!-- Tab Container -->
    <div class="tab-container">
        <div class="tab-buttons">
            <button class="tab-button active" onclick="switchTab('list-view', this)">
                <i class="fas fa-list mr-2"></i> รายการ
            </button>
            <button class="tab-button" onclick="switchTab('calendar-view', this)">
                <i class="fas fa-calendar mr-2"></i> ปฏิทิน
            </button>
        </div>

        <!-- List View Tab -->
        <div id="list-view" class="tab-content active">
            <?php if (!empty($all_tasks)): ?>
            <div class="tasks-table-container">
                <!-- Search Bar -->
                <div class="table-controls" style="padding: 1.5rem 1rem 1rem 1rem;">
                    <input type="text" id="tableSearch" class="table-search-box" placeholder="ค้นหารหัสคำขอ, บริการ, ผู้ขอ...">
                </div>

                <!-- Table -->
                <table id="tasksTable">
                    <thead>
                        <tr>
                            <th>รหัสคำขอ</th>
                            <th>บริการ</th>
                            <th>ผู้ขอ</th>
                            <th>สถานะ</th>
                            <th>กำหนดส่ง</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
                    <?php foreach ($all_tasks as $task): ?>
                        <tr>
                            <td>
                                <span style="font-family: 'Courier New', monospace; font-weight: 600;">
                                    <?= htmlspecialchars($task['request_code']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($task['service_name']) ?></td>
                            <td><?= htmlspecialchars($task['requester_name']) ?></td>
                            <td>
                                <span class="status-badge status-<?= $task['status'] ?>">
                                    <?php
                                    $status_labels = [
                                        'pending' => 'รอรับงาน',
                                        'accepted' => 'รับงานแล้ว',
                                        'in_progress' => 'กำลังดำเนินการ',
                                        'completed' => 'เสร็จสิ้น',
                                        'cancelled' => 'ยกเลิก'
                                    ];
                                    echo $status_labels[$task['status']] ?? $task['status'];
                                    ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($task['due_date']): ?>
                                    <?= date('d/m/Y', strtotime($task['due_date'])) ?>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="task_detail.php?assignment_id=<?= $task['assignment_id'] ?>" 
                                   class="btn-action-small" title="ดูรายละเอียด">
                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div style="padding: 1rem;">
                    <div class="table-pagination" id="tablePagination"></div>
                    <div class="table-info" id="tableInfo"></div>
                </div>
            </div>
            <?php else: ?>
                <div class="no-tasks">
                    <i class="fas fa-inbox"></i>
                    <p class="text-lg">ยังไม่มีงานที่ได้รับมอบหมาย</p>
                    <p class="text-sm">รอให้ผู้จัดการมอบหมายงานให้คุณ</p>
                </div>
            <?php endif; ?>

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
</div>

<script>
    // Get all tasks data from PHP
    const tasksData = <?= json_encode($all_tasks) ?>;
    console.log('Tasks data loaded:', tasksData.length, 'tasks');
    
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();
    console.log('Initial month/year:', currentMonth, '/', currentYear);

    // Tab switching - pass button element as parameter
    function switchTab(tabName, buttonElement) {
        console.log('switchTab called with:', tabName);
        
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));

        // Show selected tab
        document.getElementById(tabName).classList.add('active');
        if (buttonElement) {
            buttonElement.classList.add('active');
        }

        if (tabName === 'calendar-view') {
            console.log('Rendering calendar...');
            renderCalendar();
        } else if (tabName === 'list-view' && !window.tasksTableInitialized) {
            console.log('Initializing table...');
            initializeTable();
            window.tasksTableInitialized = true;
        }
    }

    // Table Pagination
    const ITEMS_PER_PAGE = 10;
    let allRows = [];
    let currentPage = 1;

    function initializeTable() {
        allRows = Array.from(document.querySelectorAll('#tableBody tr'));
        setupSearchFilter();
        renderTablePage(1);
    }

    function setupSearchFilter() {
        const searchBox = document.getElementById('tableSearch');
        if (!searchBox) return;

        searchBox.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const filteredRows = allRows.filter(row => {
                const text = row.textContent.toLowerCase();
                return text.includes(searchTerm);
            });
            currentPage = 1;
            renderPaginationAndRows(filteredRows);
        });
    }

    function renderPaginationAndRows(rows) {
        const totalPages = Math.ceil(rows.length / ITEMS_PER_PAGE);
        
        // Show/hide rows
        rows.forEach((row, index) => {
            const pageNumber = Math.floor(index / ITEMS_PER_PAGE) + 1;
            row.style.display = pageNumber === currentPage ? '' : 'none';
        });

        // Render pagination buttons
        let paginationHTML = '';
        if (totalPages > 1) {
            // Previous button
            paginationHTML += `<button class="pagination-btn" onclick="goToPage(${Math.max(1, currentPage - 1)})" ${currentPage === 1 ? 'disabled' : ''}>ก่อนหน้า</button>`;
            
            // Page numbers
            for (let i = 1; i <= Math.min(totalPages, 5); i++) {
                paginationHTML += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
            }
            if (totalPages > 5) {
                paginationHTML += '<span style="padding: 0 0.5rem;">...</span>';
                paginationHTML += `<button class="pagination-btn ${totalPages === currentPage ? 'active' : ''}" onclick="goToPage(${totalPages})">${totalPages}</button>`;
            }
            
            // Next button
            paginationHTML += `<button class="pagination-btn" onclick="goToPage(${Math.min(totalPages, currentPage + 1)})" ${currentPage === totalPages ? 'disabled' : ''}>ถัดไป</button>`;
        }
        document.getElementById('tablePagination').innerHTML = paginationHTML;

        // Render info text
        const startIndex = (currentPage - 1) * ITEMS_PER_PAGE + 1;
        const endIndex = Math.min(currentPage * ITEMS_PER_PAGE, rows.length);
        document.getElementById('tableInfo').textContent = `แสดง ${startIndex} ถึง ${endIndex} จากทั้งหมด ${rows.length} รายการ`;
    }

    function goToPage(pageNum) {
        currentPage = pageNum;
        const searchBox = document.getElementById('tableSearch');
        const searchTerm = searchBox ? searchBox.value.toLowerCase() : '';
        
        if (searchTerm) {
            const filteredRows = allRows.filter(row => row.textContent.toLowerCase().includes(searchTerm));
            renderPaginationAndRows(filteredRows);
        } else {
            renderPaginationAndRows(allRows);
        }
    }

    function renderTablePage(pageNum) {
        currentPage = pageNum;
        const searchBox = document.getElementById('tableSearch');
        const searchTerm = searchBox ? searchBox.value.toLowerCase() : '';
        
        if (searchTerm) {
            const filteredRows = allRows.filter(row => row.textContent.toLowerCase().includes(searchTerm));
            renderPaginationAndRows(filteredRows);
        } else {
            renderPaginationAndRows(allRows);
        }
    }

    // Calendar functions
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

</script>

<?php include 'admin-layout/footer.php'; ?>
