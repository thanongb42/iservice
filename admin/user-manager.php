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
$stats_query = "SELECT
    COUNT(*) as total_users,
    SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as admin_count,
    SUM(CASE WHEN role = 'staff' THEN 1 ELSE 0 END) as staff_count,
    SUM(CASE WHEN role = 'user' THEN 1 ELSE 0 END) as user_count,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_count,
    SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_count
FROM users";
$stats_result = $conn->query($stats_query);
$stats = $stats_result->fetch_assoc();

// Get all users
$users_query = "SELECT * FROM v_users_full ORDER BY created_at DESC";
$users_result = $conn->query($users_query);

// Pagination setup
$items_per_page = 10;
$total_users = $users_result->num_rows;
$total_pages = ceil($total_users / $items_per_page);
$current_page_num = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page_num - 1) * $items_per_page;

// Re-query with LIMIT and OFFSET
$users_query = "SELECT * FROM v_users_full ORDER BY created_at DESC LIMIT $offset, $items_per_page";
$users_result = $conn->query($users_query);

// Get prefixes for dropdown
$prefixes_query = "SELECT prefix_id, prefix_name, prefix_type FROM prefixes WHERE is_active = 1 ORDER BY display_order";
$prefixes_result = $conn->query($prefixes_query);
$prefixes = [];
while ($row = $prefixes_result->fetch_assoc()) {
    $prefixes[$row['prefix_type']][] = $row;
}

// Get departments for dropdown
$departments_query = "SELECT department_id, department_name, department_code FROM departments WHERE status = 'active' ORDER BY department_name";
$departments_result = $conn->query($departments_query);

// Page configuration
$page_title = 'จัดการผู้ใช้งาน';
$current_page = 'user-manager';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการผู้ใช้งาน']
];

// Include layout components
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
        /* Clean Minimal Table Styles */

        /* Status Badge - Minimal */
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .status-active { background-color: #ecfdf5; color: #059669; }
        .status-inactive { background-color: #fef2f2; color: #dc2626; }
        .status-suspended { background-color: #fffbeb; color: #d97706; }

        /* Role Badge - Minimal */
        .role-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.625rem;
            border-radius: 0.375rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .role-admin { background-color: #eff6ff; color: #2563eb; }
        .role-staff { background-color: #f5f3ff; color: #7c3aed; }
        .role-user { background-color: #f9fafb; color: #6b7280; }

        /* Sortable column headers */
        #usersTable thead th.sortable {
            cursor: pointer;
            user-select: none;
            white-space: nowrap;
        }
        #usersTable thead th.sortable:hover {
            background-color: #f0fdf4;
            color: #16a34a;
        }
        #usersTable thead th.sortable.sorted {
            background-color: #f0fdf4;
            color: #16a34a;
        }
        .sort-icon {
            display: inline-block;
            margin-left: 4px;
            font-size: 0.7rem;
            opacity: 0.5;
        }
        #usersTable thead th.sorted .sort-icon {
            opacity: 1;
            color: #16a34a;
        }

        /* Clean Table */
        #usersTable {
            width: 100%;
            border-collapse: collapse;
        }

        #usersTable thead {
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        #usersTable th {
            padding: 0.875rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        #usersTable th i {
            display: none;
        }

        #usersTable tbody tr {
            border-bottom: 1px solid #f3f4f6;
            transition: background-color 0.15s ease;
        }

        #usersTable tbody tr:hover {
            background-color: #f9fafb;
        }

        #usersTable td {
            padding: 1rem;
            font-size: 0.875rem;
            color: #374151;
        }

        /* User avatar - simple circle */
        .user-avatar {
            width: 2.25rem;
            height: 2.25rem;
            min-width: 2.25rem;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
            overflow: hidden;
        }

        img.user-avatar {
            object-fit: cover;
        }

        /* Action buttons - Minimal */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.375rem;
            transition: all 0.15s ease;
            cursor: pointer;
            border: none;
            background: transparent;
            color: #9ca3af;
        }

        .action-btn:hover {
            background-color: #f3f4f6;
        }

        .action-btn-view:hover { color: #3b82f6; }
        .action-btn-edit:hover { color: #009933; }
        .action-btn-delete:hover { color: #ef4444; background-color: #fef2f2; }

        /* Pagination - Clean */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.25rem;
            margin-top: 1.5rem;
        }

        .pagination a, .pagination span {
            padding: 0.5rem 0.875rem;
            border-radius: 0.375rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.15s ease;
        }

        .pagination a {
            background-color: transparent;
            color: #6b7280;
            text-decoration: none;
            border: 1px solid #e5e7eb;
        }

        .pagination a:hover {
            background-color: #f9fafb;
            border-color: #d1d5db;
            color: #374151;
        }

        .pagination span.current {
            background-color: #009933;
            color: white;
            border: 1px solid #009933;
        }

        .pagination span.info {
            color: #9ca3af;
            border: none;
        }

        /* Table Info - Simple */
        .table-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background-color: #f9fafb;
            border-bottom: 1px solid #e5e7eb;
        }

        .table-info-text {
            color: #6b7280;
            font-size: 0.875rem;
        }

        /* Modal - Clean */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0);
            transition: background-color 0.2s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.4);
        }

        .modal-content {
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            width: 90%;
            max-width: 560px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 1.5rem;
            animation: modalSlide 0.2s ease;
        }

        @keyframes modalSlide {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Roles Modal Specific Styles */
        #rolesModal .modal-content {
            width: 100%;
            max-width: 600px;
        }

        .role-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem;
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
            transition: all 0.15s ease;
        }

        .role-item:hover {
            background-color: #f3f4f6;
            border-color: #d1d5db;
        }

        /* Form - Clean */
        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-group label i {
            color: #9ca3af;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.625rem 0.875rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.15s ease;
            background-color: #fff;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #009933;
            box-shadow: 0 0 0 3px rgba(0, 153, 51, 0.1);
        }

        .form-group input::placeholder {
            color: #d1d5db;
        }

        .form-group p {
            margin-top: 0.375rem;
            color: #9ca3af;
            font-size: 0.75rem;
        }

        /* Buttons - Clean */
        .btn {
            padding: 0.625rem 1.25rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.15s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background-color: #009933;
            color: white;
        }

        .btn-primary:hover {
            background-color: #007a29;
        }

        .btn-secondary {
            background-color: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
        }

        /* Stats Cards - Clean */
        .stat-card {
            background: white;
            border-radius: 0.75rem;
            border: 1px solid #e5e7eb;
            padding: 1.25rem;
            transition: box-shadow 0.15s ease;
        }

        .stat-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .w-full {
            width: 100%;
        }
    </style>

    <!-- Content -->
    <div>
        <!-- Page Header - Clean -->
        <div class="mb-6">
            <h1 class="text-2xl font-semibold text-gray-800">จัดการผู้ใช้งาน</h1>
            <p class="text-gray-500 text-sm mt-1">จัดการข้อมูลผู้ใช้งานทั้งหมดในระบบ</p>
        </div>

        <!-- Statistics Cards - Clean Minimal -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ทั้งหมด</p>
                        <p class="text-2xl font-semibold text-gray-800 mt-1"><?php echo number_format($stats['total_users']); ?></p>
                    </div>
                    <div class="stat-icon bg-blue-50">
                        <i class="fas fa-users text-blue-500"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ใช้งานอยู่</p>
                        <p class="text-2xl font-semibold text-green-600 mt-1"><?php echo number_format($stats['active_count']); ?></p>
                    </div>
                    <div class="stat-icon bg-green-50">
                        <i class="fas fa-user-check text-green-500"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ผู้ดูแล</p>
                        <p class="text-2xl font-semibold text-blue-600 mt-1"><?php echo number_format($stats['admin_count']); ?></p>
                    </div>
                    <div class="stat-icon bg-purple-50">
                        <i class="fas fa-user-shield text-purple-500"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">เจ้าหน้าที่</p>
                        <p class="text-2xl font-semibold text-orange-600 mt-1"><?php echo number_format($stats['staff_count']); ?></p>
                    </div>
                    <div class="stat-icon bg-orange-50">
                        <i class="fas fa-user-tie text-orange-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
            <!-- Table Header with Search & Filters -->
            <div class="p-4 border-b border-gray-100">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <!-- Search -->
                    <div class="relative flex-1 max-w-md">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" id="searchInput" placeholder="ค้นหาผู้ใช้..."
                               class="w-full pl-10 pr-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500">
                    </div>
                    <!-- Filters & Action -->
                    <div class="flex items-center gap-3">
                        <select id="filterRole" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500">
                            <option value="">ทุกบทบาท</option>
                            <option value="admin">ผู้ดูแล</option>
                            <option value="staff">เจ้าหน้าที่</option>
                            <option value="user">ผู้ใช้</option>
                        </select>
                        <select id="filterStatus" class="text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-green-500/20 focus:border-green-500">
                            <option value="">ทุกสถานะ</option>
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                            <option value="suspended">ระงับ</option>
                        </select>
                        <button onclick="openAddUserModal()" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            <span class="hidden sm:inline">เพิ่มผู้ใช้</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Table Info -->
            <div class="table-info">
                <span class="table-info-text">แสดง <?= $users_result->num_rows ?> จาก <?= $total_users ?> รายการ</span>
                <span class="table-info-text">หน้า <?= $current_page_num ?>/<?= $total_pages ?></span>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th class="sortable" data-col="0" onclick="sortTable(0)">ID <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="1" onclick="sortTable(1)">ผู้ใช้ <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="2" onclick="sortTable(2)">ชื่อ-นามสกุล <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="3" onclick="sortTable(3)">Email <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="4" onclick="sortTable(4)">หน่วยงาน <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="5" onclick="sortTable(5)">บทบาท <span class="sort-icon">↕</span></th>
                            <th class="sortable" data-col="6" onclick="sortTable(6)">สถานะ <span class="sort-icon">↕</span></th>
                            <th class="text-center">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>" data-user-id="<?php echo $user['user_id']; ?>">
                                <td>
                                    <span class="text-gray-500 text-sm">#<?php echo $user['user_id']; ?></span>
                                </td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <?php if (!empty($user['profile_image']) && file_exists('../' . $user['profile_image'])): ?>
                                            <img src="../<?php echo htmlspecialchars($user['profile_image']); ?>"
                                                 alt="<?php echo htmlspecialchars($user['username']); ?>"
                                                 class="user-avatar object-cover">
                                        <?php else: ?>
                                            <div class="user-avatar bg-gray-100 text-gray-600">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td>
                                    <span class="text-gray-500 text-sm"><?php echo htmlspecialchars($user['email']); ?></span>
                                </td>
                                <td>
                                    <?php if ($user['department_name']): ?>
                                        <span class="text-sm text-gray-600"><?php echo htmlspecialchars($user['department_name']); ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="role-badge role-<?php echo $user['role']; ?>">
                                        <?php
                                        $role_text = ['admin' => 'ผู้ดูแล', 'staff' => 'เจ้าหน้าที่', 'user' => 'ผู้ใช้'];
                                        echo $role_text[$user['role']];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $user['status']; ?>">
                                        <?php
                                        $status_text = ['active' => 'ใช้งาน', 'inactive' => 'ไม่ใช้งาน', 'suspended' => 'ระงับ'];
                                        echo $status_text[$user['status']];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-1">
                                        <button class="action-btn action-btn-view" onclick="viewUser(<?php echo $user['user_id']; ?>)" title="ดู">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn action-btn-edit" onclick="editUser(<?php echo $user['user_id']; ?>)" title="แก้ไข">
                                            <i class="fas fa-pen"></i>
                                        </button>
                                        <button class="action-btn" onclick="setUserRoles(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" title="จัดการบทบาท" style="color: #7c3aed;">
                                            <i class="fas fa-user-tag"></i>
                                        </button>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button class="action-btn action-btn-delete" onclick="deleteUser(<?php echo $user['user_id']; ?>)" title="ลบ">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-12 text-gray-400">
                                    <i class="fas fa-users text-4xl mb-3 block opacity-30"></i>
                                    <p>ไม่พบข้อมูลผู้ใช้งาน</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <span class="info">
                <i class="fas fa-list mr-1"></i>
                หน้า:
            </span>
            
            <?php if ($current_page_num > 1): ?>
                <a href="?page=1" title="หน้าแรก">
                    <i class="fas fa-chevron-double-left"></i>
                </a>
                <a href="?page=<?php echo $current_page_num - 1; ?>" title="หน้าก่อนหน้า">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>

            <?php 
            $start_page = max(1, $current_page_num - 2);
            $end_page = min($total_pages, $current_page_num + 2);
            
            if ($start_page > 1): ?>
                <a href="?page=1">1</a>
                <?php if ($start_page > 2): ?>
                    <span class="info">...</span>
                <?php endif; ?>
            <?php endif; ?>

            <?php for ($page = $start_page; $page <= $end_page; $page++): ?>
                <?php if ($page == $current_page_num): ?>
                    <span class="current"><?php echo $page; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $page; ?>"><?php echo $page; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($end_page < $total_pages): ?>
                <?php if ($end_page < $total_pages - 1): ?>
                    <span class="info">...</span>
                <?php endif; ?>
                <a href="?page=<?php echo $total_pages; ?>"><?php echo $total_pages; ?></a>
            <?php endif; ?>

            <?php if ($current_page_num < $total_pages): ?>
                <a href="?page=<?php echo $current_page_num + 1; ?>" title="หน้าถัดไป">
                    <i class="fas fa-chevron-right"></i>
                </a>
                <a href="?page=<?php echo $total_pages; ?>" title="หน้าสุดท้าย">
                    <i class="fas fa-chevron-double-right"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <!-- Modal Header - Clean -->
            <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800" id="modalTitle">เพิ่มผู้ใช้ใหม่</h2>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition p-1">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="formAction" name="action" value="add">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้ <span class="text-red-500">*</span></label>
                        <input type="text" id="username" name="username" required
                               pattern="[a-zA-Z0-9_]{4,20}"
                               placeholder="4-20 ตัวอักษร">
                    </div>

                    <div class="form-group">
                        <label for="email">Email <span class="text-red-500">*</span></label>
                        <input type="email" id="email" name="email" required placeholder="email@example.com">
                    </div>

                    <div class="form-group md:col-span-2">
                        <label for="prefix_id">คำนำหน้า <span class="text-red-500">*</span></label>
                        <select id="prefix_id" name="prefix_id" required>
                            <option value="">-- เลือกคำนำหน้า --</option>
                            <?php
                            $prefix_labels = [
                                'general' => 'คำนำหน้าทั่วไป',
                                'military_army' => 'ยศทหารบก',
                                'military_navy' => 'ยศทหารเรือ',
                                'military_air' => 'ยศทหารอากาศ',
                                'police' => 'ยศตำรวจ',
                                'academic' => 'คำนำหน้าทางวิชาการ'
                            ];
                            foreach ($prefix_labels as $type => $label):
                                if (!empty($prefixes[$type])):
                            ?>
                            <optgroup label="<?php echo $label; ?>">
                                <?php foreach ($prefixes[$type] as $prefix): ?>
                                <option value="<?php echo $prefix['prefix_id']; ?>">
                                    <?php echo htmlspecialchars($prefix['prefix_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php
                                endif;
                            endforeach;
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="first_name">ชื่อ <span class="text-red-500">*</span></label>
                        <input type="text" id="first_name" name="first_name" required placeholder="ชื่อจริง">
                    </div>

                    <div class="form-group">
                        <label for="last_name">นามสกุล <span class="text-red-500">*</span></label>
                        <input type="text" id="last_name" name="last_name" required placeholder="นามสกุล">
                    </div>

                    <div class="form-group">
                        <label for="phone">เบอร์โทรศัพท์</label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" placeholder="0891234567">
                    </div>

                    <div class="form-group">
                        <label for="department_id">หน่วยงาน</label>
                        <select id="department_id" name="department_id">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php
                            $departments_result->data_seek(0);
                            while ($dept = $departments_result->fetch_assoc()):
                            ?>
                            <option value="<?php echo $dept['department_id']; ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="position">ตำแหน่ง</label>
                        <input type="text" id="position" name="position" placeholder="ตำแหน่งงาน">
                    </div>

                    <div class="form-group">
                        <label for="role">บทบาท <span class="text-red-500">*</span></label>
                        <select id="role" name="role" required>
                            <option value="user">ผู้ใช้ทั่วไป</option>
                            <option value="staff">เจ้าหน้าที่</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">สถานะ <span class="text-red-500">*</span></label>
                        <select id="status" name="status" required>
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                            <option value="suspended">ระงับ</option>
                        </select>
                    </div>

                    <div class="form-group md:col-span-2">
                        <label for="password">รหัสผ่าน <span id="passwordRequired" class="text-red-500">*</span></label>
                        <input type="password" id="password" name="password" placeholder="อย่างน้อย 6 ตัวอักษร">
                        <p id="passwordHint">ต้องมีความยาวอย่างน้อย 6 ตัวอักษร</p>
                    </div>
                </div>

                <!-- Modal Footer - Clean -->
                <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Manage User Roles Modal -->
    <div id="rolesModal" class="modal">
        <div class="modal-content" style="max-width: 600px;">
            <!-- Modal Header -->
            <div class="flex justify-between items-center pb-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">จัดการบทบาท/หน้าที่</h2>
                <button onclick="closeRolesModal()" class="text-gray-400 hover:text-gray-600 transition p-1">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="py-4">
                <p class="text-sm text-gray-600 mb-4">ผู้ใช้: <strong id="rolesModalUsername"></strong></p>

                <!-- Current Roles -->
                <div class="mb-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">บทบาท/หน้าที่ที่ได้รับมอบหมายแล้ว</h3>
                    <div id="currentRolesList" class="space-y-2">
                        <p class="text-sm text-gray-400">กำลังโหลด...</p>
                    </div>
                </div>

                <!-- Add New Role -->
                <div class="border-t pt-4">
                    <h3 class="text-sm font-semibold text-gray-700 mb-3">เพิ่มบทบาท/หน้าที่ใหม่</h3>
                    <div class="flex gap-2">
                        <select id="availableRolesSelect" class="flex-1 text-sm border border-gray-200 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-purple-500/20 focus:border-purple-500">
                            <option value="">-- เลือกบทบาท --</option>
                        </select>
                        <button onclick="addRoleToUser()" class="btn btn-primary px-4">
                            <i class="fas fa-plus mr-2"></i>เพิ่ม
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeRolesModal()" class="btn btn-secondary">ปิด</button>
            </div>
        </div>
    </div>

    <script>
        // Filter functionality
        const searchInput = document.getElementById('searchInput');
        const filterRole = document.getElementById('filterRole');
        const filterStatus = document.getElementById('filterStatus');
        const tableRows = document.querySelectorAll('#usersTable tbody tr');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const roleFilter = filterRole.value;
            const statusFilter = filterStatus.value;

            tableRows.forEach(row => {
                const text = row.textContent.toLowerCase();
                const role = row.dataset.role;
                const status = row.dataset.status;

                const matchesSearch = text.includes(searchTerm);
                const matchesRole = !roleFilter || role === roleFilter;
                const matchesStatus = !statusFilter || status === statusFilter;

                if (matchesSearch && matchesRole && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('input', filterTable);
        filterRole.addEventListener('change', filterTable);
        filterStatus.addEventListener('change', filterTable);

        // Sort functionality
        let sortState = { col: -1, asc: true };

        function sortTable(colIndex) {
            const tbody = document.querySelector('#usersTable tbody');
            const allRows = Array.from(tbody.querySelectorAll('tr'));

            if (sortState.col === colIndex) {
                sortState.asc = !sortState.asc;
            } else {
                sortState.col = colIndex;
                sortState.asc = true;
            }

            // Update header icons
            document.querySelectorAll('#usersTable thead th.sortable').forEach(th => {
                const icon = th.querySelector('.sort-icon');
                const col = parseInt(th.dataset.col);
                if (col === colIndex) {
                    th.classList.add('sorted');
                    icon.textContent = sortState.asc ? '↑' : '↓';
                } else {
                    th.classList.remove('sorted');
                    icon.textContent = '↕';
                }
            });

            function getCellValue(row, col) {
                const cell = row.cells[col];
                if (!cell) return '';
                if (col === 0) {
                    return parseInt(cell.textContent.replace(/\D/g, '')) || 0;
                }
                if (col === 1) {
                    const span = cell.querySelector('span.font-medium');
                    return (span ? span.textContent : cell.textContent).trim().toLowerCase();
                }
                return cell.textContent.trim().toLowerCase();
            }

            allRows.sort((a, b) => {
                const valA = getCellValue(a, colIndex);
                const valB = getCellValue(b, colIndex);
                if (colIndex === 0) {
                    return sortState.asc ? valA - valB : valB - valA;
                }
                if (valA < valB) return sortState.asc ? -1 : 1;
                if (valA > valB) return sortState.asc ? 1 : -1;
                return 0;
            });

            allRows.forEach(row => tbody.appendChild(row));
        }

        // Modal functions
        function openAddUserModal() {
            document.getElementById('modalTitle').textContent = 'เพิ่มผู้ใช้ใหม่';
            document.getElementById('formAction').value = 'add';
            document.getElementById('userForm').reset();
            document.getElementById('userId').value = '';
            document.getElementById('password').required = true;
            document.getElementById('passwordRequired').textContent = '*';
            document.getElementById('passwordHint').textContent = 'ต้องมีความยาวอย่างน้อย 6 ตัวอักษร';
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        // View user details - redirect to detail page
        function viewUser(userId) {
            window.location.href = `user_detail.php?id=${userId}`;
        }

        // Edit user
        async function editUser(userId) {
            try {
                const response = await fetch(`api/user_manager_api.php?action=get&id=${userId}`);
                const data = await response.json();

                if (data.success) {
                    const user = data.user;
                    document.getElementById('modalTitle').textContent = 'แก้ไขข้อมูลผู้ใช้';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('userId').value = user.user_id;
                    document.getElementById('username').value = user.username;
                    document.getElementById('email').value = user.email;
                    document.getElementById('prefix_id').value = user.prefix_id || '';
                    document.getElementById('first_name').value = user.first_name;
                    document.getElementById('last_name').value = user.last_name;
                    document.getElementById('phone').value = user.phone || '';
                    document.getElementById('department_id').value = user.department_id || '';
                    document.getElementById('position').value = user.position || '';
                    document.getElementById('role').value = user.role;
                    document.getElementById('status').value = user.status;
                    document.getElementById('password').required = false;
                    document.getElementById('password').value = '';
                    document.getElementById('passwordRequired').textContent = '';
                    document.getElementById('passwordHint').textContent = 'เว้นว่างไว้หากไม่ต้องการเปลี่ยนรหัสผ่าน';

                    document.getElementById('userModal').classList.add('active');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            }
        }

        // Delete user
        async function deleteUser(userId) {
            const result = await Swal.fire({
                title: 'ยืนยันการลบ',
                text: 'คุณต้องการลบผู้ใช้นี้ใช่หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', userId);

                    const response = await fetch('api/user_manager_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        Swal.fire('สำเร็จ', 'ลบผู้ใช้เรียบร้อยแล้ว', 'success');
                        setTimeout(() => location.reload(), 1500);
                    } else {
                        Swal.fire('ผิดพลาด', data.message, 'error');
                    }
                } catch (error) {
                    console.error('Error:', error);
                    Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบได้', 'error');
                }
            }
        }

        // Form submission
        document.getElementById('userForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const formData = new FormData(e.target);

            try {
                const response = await fetch('api/user_manager_api.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    closeModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    Swal.fire('ผิดพลาด', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถบันทึกข้อมูลได้', 'error');
            }
        });

        // Close modal on outside click
        document.getElementById('userModal').addEventListener('click', (e) => {
            if (e.target.id === 'userModal') {
                closeModal();
            }
        });

        // ========================================
        // Functions for Managing User Roles
        // ========================================
        let currentRolesUserId = null;

        async function setUserRoles(userId, username) {
            currentRolesUserId = userId;
            document.getElementById('rolesModalUsername').textContent = username;
            
            // Load current roles
            await loadCurrentRoles(userId);
            
            // Load available roles
            await loadAvailableRoles();
            
            document.getElementById('rolesModal').classList.add('active');
        }

        async function loadCurrentRoles(userId) {
            try {
                const response = await fetch(`api/user_roles_api.php?action=get_user_roles&user_id=${userId}`);
                const data = await response.json();
                
                if (data.success) {
                    const rolesList = document.getElementById('currentRolesList');
                    
                    if (data.roles.length === 0) {
                        rolesList.innerHTML = '<p class="text-sm text-gray-400">ยังไม่มีบทบาทที่ได้รับมอบหมาย</p>';
                        return;
                    }
                    
                    let html = '';
                    data.roles.forEach(role => {
                        const primaryBadge = role.is_primary ? '<span class="text-xs bg-yellow-100 text-yellow-700 px-2 py-1 rounded">หลัก</span>' : '';
                        html += `
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200">
                                <div class="flex items-center gap-2">
                                    <i class="fas ${role.role_icon || 'fa-user-tag'}" style="color: ${role.role_color}; font-size: 1.1em;"></i>
                                    <div>
                                        <div class="font-medium text-gray-800">${role.role_name}</div>
                                        <div class="text-xs text-gray-500">${role.role_code}</div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    ${!role.is_primary ? `<button onclick="setPrimaryRole(${userId}, ${role.role_id})" class="text-xs bg-blue-100 text-blue-700 hover:bg-blue-200 px-2 py-1 rounded transition" title="ตั้งเป็นบทบาทหลัก">ตั้งเป็นหลัก</button>` : ''}
                                    <button onclick="removeRole(${userId}, ${role.role_id})" class="text-xs bg-red-100 text-red-700 hover:bg-red-200 px-2 py-1 rounded transition" title="ลบบทบาท">ลบ</button>
                                </div>
                            </div>
                        `;
                    });
                    rolesList.innerHTML = html;
                } else {
                    console.error('Error loading roles:', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('currentRolesList').innerHTML = '<p class="text-sm text-red-500">ไม่สามารถโหลดข้อมูล</p>';
            }
        }

        async function loadAvailableRoles() {
            try {
                const response = await fetch('api/user_roles_api.php?action=get_available_roles');
                const data = await response.json();
                
                if (data.success) {
                    const select = document.getElementById('availableRolesSelect');
                    select.innerHTML = '<option value="">-- เลือกบทบาท --</option>';
                    
                    data.roles.forEach(role => {
                        const option = document.createElement('option');
                        option.value = role.role_id;
                        option.textContent = `${role.role_name} (${role.role_code})`;
                        select.appendChild(option);
                    });
                } else {
                    console.error('Error loading available roles:', data.message);
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function addRoleToUser() {
            const roleId = document.getElementById('availableRolesSelect').value;
            
            if (!roleId) {
                Swal.fire('แจ้งเตือน', 'กรุณาเลือกบทบาท', 'warning');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'add_role');
                formData.append('user_id', currentRolesUserId);
                formData.append('role_id', roleId);
                formData.append('is_primary', 0);
                
                const response = await fetch('api/user_roles_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    document.getElementById('availableRolesSelect').value = '';
                    await loadCurrentRoles(currentRolesUserId);
                    await loadAvailableRoles();
                } else {
                    Swal.fire('ผิดพลาด', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถเพิ่มบทบาทได้', 'error');
            }
        }

        async function removeRole(userId, roleId) {
            const result = await Swal.fire({
                title: 'ยืนยันการลบ',
                text: 'คุณต้องการลบบทบาทนี้หรือไม่?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก'
            });
            
            if (!result.isConfirmed) return;
            
            try {
                const formData = new FormData();
                formData.append('action', 'remove_role');
                formData.append('user_id', userId);
                formData.append('role_id', roleId);
                
                const response = await fetch('api/user_roles_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    await loadCurrentRoles(userId);
                    await loadAvailableRoles();
                } else {
                    Swal.fire('ผิดพลาด', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถลบบทบาทได้', 'error');
            }
        }

        async function setPrimaryRole(userId, roleId) {
            try {
                const formData = new FormData();
                formData.append('action', 'set_primary_role');
                formData.append('user_id', userId);
                formData.append('role_id', roleId);
                
                const response = await fetch('api/user_roles_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    Swal.fire('สำเร็จ', data.message, 'success');
                    await loadCurrentRoles(userId);
                } else {
                    Swal.fire('ผิดพลาด', data.message, 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถอัปเดตบทบาทหลักได้', 'error');
            }
        }

        function closeRolesModal() {
            document.getElementById('rolesModal').classList.remove('active');
            currentRolesUserId = null;
        }

        // Close roles modal on outside click
        document.getElementById('rolesModal').addEventListener('click', (e) => {
            if (e.target.id === 'rolesModal') {
                closeRolesModal();
            }
        });
    </script>
</div>
<?php include 'admin-layout/footer.php'; ?>