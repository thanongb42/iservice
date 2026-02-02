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
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .status-active { background-color: #d1fae5; color: #065f46; }
        .status-inactive { background-color: #fee2e2; color: #991b1b; }
        .status-suspended { background-color: #fef3c7; color: #92400e; }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .role-admin { background-color: #dbeafe; color: #1e40af; }
        .role-staff { background-color: #e0e7ff; color: #4338ca; }
        .role-user { background-color: #f3f4f6; color: #374151; }

        /* Table Styling */
        #usersTable {
            width: 100%;
            border-collapse: collapse;
        }

        #usersTable thead {
            background: linear-gradient(135deg, #0d9488 0%, #14b8a6 100%);
            color: white;
        }

        #usersTable th {
            padding: 12px 16px;
            text-align: left;
            font-weight: 600;
            font-size: 0.875rem;
            letter-spacing: 0.5px;
        }

        #usersTable tbody tr {
            border-bottom: 1px solid #e5e7eb;
            transition: all 0.2s ease;
        }

        #usersTable tbody tr:hover {
            background-color: #f0fdfa;
            box-shadow: inset 0 1px 2px rgba(13, 148, 136, 0.1);
        }

        #usersTable td {
            padding: 14px 16px;
            font-size: 0.875rem;
        }

        #usersTable td:first-child {
            font-weight: 500;
            color: #0d9488;
        }

        /* Action buttons */
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 6px;
            margin-right: 4px;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background: none;
        }

        .action-btn-view {
            color: #0369a1;
        }

        .action-btn-view:hover {
            background-color: #ecf0ff;
            color: #0284c7;
        }

        .action-btn-edit {
            color: #059669;
        }

        .action-btn-edit:hover {
            background-color: #ecfdf5;
            color: #10b981;
        }

        .action-btn-delete {
            color: #dc2626;
        }

        .action-btn-delete:hover {
            background-color: #fef2f2;
            color: #ef4444;
        }

        /* Pagination Styling */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .pagination a, .pagination span {
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .pagination a {
            background-color: #f3f4f6;
            color: #0d9488;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid #e5e7eb;
        }

        .pagination a:hover {
            background-color: #0d9488;
            color: white;
            border-color: #0d9488;
        }

        .pagination span.current {
            background-color: #0d9488;
            color: white;
            border: 1px solid #0d9488;
            font-weight: 600;
        }

        .pagination span.info {
            color: #6b7280;
            padding: 0 12px;
        }

        .table-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding: 12px 16px;
            background-color: #f0fdfa;
            border-radius: 8px;
            border-left: 4px solid #0d9488;
        }

        .table-info-text {
            color: #065f46;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0);
            transition: background-color 0.3s ease;
        }

        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            width: 90%;
            max-width: 600px;
            max-height: 85vh;
            overflow-y: auto;
            padding: 32px;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #0d9488;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-thumb:hover {
            background: #0f766e;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 0.875rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 12px;
            border: 1.5px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
            background-color: #f0fdfa;
        }

        .form-group input::placeholder {
            color: #9ca3af;
        }

        .form-group p {
            margin-top: 6px;
            color: #6b7280;
            font-size: 0.75rem;
        }

        /* Button Styles */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-primary {
            background-color: #0d9488;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0f766e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(13, 148, 136, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .btn-secondary {
            background-color: #e5e7eb;
            color: #374151;
        }

        .btn-secondary:hover {
            background-color: #d1d5db;
        }

        .w-full {
            width: 100%;
        }
    </style>

    <!-- Content -->
    <div>
        <!-- Statistic Cards -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-users text-teal-600 mr-3"></i>จัดการผู้ใช้งาน
            </h1>
            <p class="text-gray-600">ระบบจัดการข้อมูลผู้ใช้งานทั้งหมด</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-blue-100 rounded-lg p-3">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">ผู้ใช้งานทั้งหมด</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['total_users']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                        <i class="fas fa-user-check text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">ใช้งานอยู่</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['active_count']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                        <i class="fas fa-user-shield text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">ผู้ดูแลระบบ</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['admin_count']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0 bg-orange-100 rounded-lg p-3">
                        <i class="fas fa-user-friends text-orange-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-600">เจ้าหน้าที่</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($stats['staff_count']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters and Actions -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                <div>
                    <input type="text" id="searchInput" placeholder="🔍 ค้นหา (ชื่อ, email, username...)"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                </div>
                <div>
                    <select id="filterRole" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">ทุกบทบาท</option>
                        <option value="admin">ผู้ดูแลระบบ</option>
                        <option value="staff">เจ้าหน้าที่</option>
                        <option value="user">ผู้ใช้ทั่วไป</option>
                    </select>
                </div>
                <div>
                    <select id="filterStatus" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500">
                        <option value="">ทุกสถานะ</option>
                        <option value="active">ใช้งานอยู่</option>
                        <option value="inactive">ไม่ใช้งาน</option>
                        <option value="suspended">ระงับการใช้งาน</option>
                    </select>
                </div>
                <div>
                    <button onclick="openAddUserModal()" class="w-full btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>เพิ่มผู้ใช้ใหม่
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
            <!-- Table Info Bar -->
            <div class="table-info">
                <div class="table-info-text">
                    <i class="fas fa-users mr-2"></i>
                    แสดง <strong><?= $users_result->num_rows ?></strong> จาก <strong><?= $total_users ?></strong> ผู้ใช้
                </div>
                <div class="text-sm text-gray-600">
                    หน้า <strong><?= $current_page_num ?></strong> จาก <strong><?= $total_pages ?></strong>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th style="width: 8%;">
                                <i class="fas fa-hashtag mr-1"></i>ID
                            </th>
                            <th style="width: 15%;">
                                <i class="fas fa-user mr-1"></i>ชื่อผู้ใช้
                            </th>
                            <th style="width: 20%;">
                                <i class="fas fa-id-card mr-1"></i>ชื่อ-นามสกุล
                            </th>
                            <th style="width: 18%;">
                                <i class="fas fa-envelope mr-1"></i>Email
                            </th>
                            <th style="width: 12%;">
                                <i class="fas fa-building mr-1"></i>หน่วยงาน
                            </th>
                            <th style="width: 8%;">
                                <i class="fas fa-shield-alt mr-1"></i>บทบาท
                            </th>
                            <th style="width: 8%;">
                                <i class="fas fa-check-circle mr-1"></i>สถานะ
                            </th>
                            <th style="width: 10%;">
                                <i class="fas fa-tools mr-1"></i>การจัดการ
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($users_result->num_rows > 0): ?>
                            <?php while ($user = $users_result->fetch_assoc()): ?>
                            <tr data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>" data-user-id="<?php echo $user['user_id']; ?>">
                                <td class="font-mono">
                                    <span class="inline-flex items-center justify-center w-7 h-7 bg-teal-100 rounded text-teal-700 font-bold text-xs">
                                        <?php echo $user['user_id']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-3">
                                        <div class="w-9 h-9 bg-gradient-to-br from-teal-400 to-teal-600 rounded-full flex items-center justify-center">
                                            <span class="text-white font-bold text-sm">
                                                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($user['username']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-gray-700">
                                        <?php echo htmlspecialchars($user['full_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-gray-600 text-sm"><?php echo htmlspecialchars($user['email']); ?></span>
                                </td>
                                <td>
                                    <?php if ($user['department_name']): ?>
                                        <span class="inline-block px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-medium">
                                            <?php echo htmlspecialchars($user['department_name']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm italic">ไม่ระบุ</span>
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
                                        <i class="fas fa-circle text-xs mr-1"></i>
                                        <?php
                                        $status_text = ['active' => 'ใช้งาน', 'inactive' => 'ไม่ใช้งาน', 'suspended' => 'ระงับ'];
                                        echo $status_text[$user['status']];
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center space-x-1">
                                        <button class="action-btn action-btn-view" onclick="viewUser(<?php echo $user['user_id']; ?>)" 
                                                title="ดูรายละเอียด" data-bs-toggle="tooltip">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn action-btn-edit" onclick="editUser(<?php echo $user['user_id']; ?>)"
                                                title="แก้ไข" data-bs-toggle="tooltip">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <button class="action-btn action-btn-delete" onclick="deleteUser(<?php echo $user['user_id']; ?>)"
                                                title="ลบ" data-bs-toggle="tooltip">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-8 text-gray-500">
                                    <i class="fas fa-inbox text-3xl mb-2 block opacity-50"></i>
                                    ไม่พบข้อมูลผู้ใช้งาน
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
            <!-- Modal Header -->
            <div class="flex justify-between items-start mb-8">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                        <div class="w-10 h-10 bg-gradient-to-br from-teal-400 to-teal-600 rounded-lg flex items-center justify-center">
                            <i class="fas fa-user-plus text-white"></i>
                        </div>
                        <span id="modalTitle">เพิ่มผู้ใช้ใหม่</span>
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">กรอกข้อมูลผู้ใช้งานใหม่ในระบบ</p>
                </div>
                <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 transition text-2xl">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Modal Form -->
            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="formAction" name="action" value="add">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="form-group">
                        <label for="username">
                            <i class="fas fa-user text-teal-600 mr-1"></i>ชื่อผู้ใช้ <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" id="username" name="username" required
                               pattern="[a-zA-Z0-9_]{4,20}"
                               placeholder="4-20 ตัวอักษร (a-z, 0-9, _)">
                    </div>

                    <div class="form-group">
                        <label for="email">
                            <i class="fas fa-envelope text-teal-600 mr-1"></i>Email <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="email" id="email" name="email" required placeholder="example@domain.com">
                    </div>

                    <div class="form-group md:col-span-2">
                        <label for="prefix_id">
                            <i class="fas fa-tag text-teal-600 mr-1"></i>คำนำหน้า <span style="color: #dc2626;">*</span>
                        </label>
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
                        <label for="first_name">
                            <i class="fas fa-user-tag text-teal-600 mr-1"></i>ชื่อ <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" id="first_name" name="first_name" required placeholder="ชื่อจริง">
                    </div>

                    <div class="form-group">
                        <label for="last_name">
                            <i class="fas fa-user-tag text-teal-600 mr-1"></i>นามสกุล <span style="color: #dc2626;">*</span>
                        </label>
                        <input type="text" id="last_name" name="last_name" required placeholder="นามสกุล">
                    </div>

                    <div class="form-group">
                        <label for="phone">
                            <i class="fas fa-phone text-teal-600 mr-1"></i>เบอร์โทรศัพท์
                        </label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{10}" placeholder="0891234567">
                    </div>

                    <div class="form-group">
                        <label for="department_id">
                            <i class="fas fa-building text-teal-600 mr-1"></i>หน่วยงาน
                        </label>
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
                        <label for="position">
                            <i class="fas fa-briefcase text-teal-600 mr-1"></i>ตำแหน่ง
                        </label>
                        <input type="text" id="position" name="position" placeholder="ตำแหน่งงาน">
                    </div>

                    <div class="form-group">
                        <label for="role">
                            <i class="fas fa-shield-alt text-teal-600 mr-1"></i>บทบาท <span style="color: #dc2626;">*</span>
                        </label>
                        <select id="role" name="role" required>
                            <option value="user">ผู้ใช้ทั่วไป</option>
                            <option value="staff">เจ้าหน้าที่</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">
                            <i class="fas fa-check-circle text-teal-600 mr-1"></i>สถานะ <span style="color: #dc2626;">*</span>
                        </label>
                        <select id="status" name="status" required>
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                            <option value="suspended">ระงับการใช้งาน</option>
                        </select>
                    </div>

                    <div class="form-group md:col-span-2">
                        <label for="password">
                            <i class="fas fa-lock text-teal-600 mr-1"></i>รหัสผ่าน <span id="passwordRequired" style="color: #dc2626;">*</span>
                        </label>
                        <input type="password" id="password" name="password" placeholder="กรอกรหัสผ่านเพื่อเพิ่มผู้ใช้">
                        <p>
                            <i class="fas fa-info-circle text-teal-600 mr-1"></i>
                            <span id="passwordHint">ต้องมีความยาวอย่างน้อย 6 ตัวอักษร</span>
                        </p>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end gap-3 mt-8 pt-6 border-t border-gray-200">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        <i class="fas fa-times"></i>ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>บันทึกข้อมูล
                    </button>
                </div>
            </form>
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

        // View user details
        async function viewUser(userId) {
            try {
                const response = await fetch(`api/user_manager_api.php?action=get&id=${userId}`);
                const data = await response.json();

                if (data.success) {
                    const user = data.user;
                    Swal.fire({
                        title: 'ข้อมูลผู้ใช้',
                        html: `
                            <div class="text-left space-y-2">
                                <p><strong>รหัส:</strong> ${user.user_id}</p>
                                <p><strong>ชื่อผู้ใช้:</strong> ${user.username}</p>
                                <p><strong>ชื่อ-นามสกุล:</strong> ${user.full_name}</p>
                                <p><strong>Email:</strong> ${user.email}</p>
                                <p><strong>โทรศัพท์:</strong> ${user.phone || '-'}</p>
                                <p><strong>หน่วยงาน:</strong> ${user.department_name || '-'}</p>
                                <p><strong>ตำแหน่ง:</strong> ${user.position || '-'}</p>
                                <p><strong>บทบาท:</strong> ${user.role}</p>
                                <p><strong>สถานะ:</strong> ${user.status}</p>
                                <p><strong>Login ล่าสุด:</strong> ${user.last_login || 'ยังไม่เคย'}</p>
                                <p><strong>วันที่สร้าง:</strong> ${user.created_at}</p>
                            </div>
                        `,
                        icon: 'info',
                        confirmButtonColor: '#14b8a6'
                    });
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire('เกิดข้อผิดพลาด', 'ไม่สามารถดึงข้อมูลได้', 'error');
            }
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
    </script>
</div>
<?php include 'admin-layout/footer.php'; ?>