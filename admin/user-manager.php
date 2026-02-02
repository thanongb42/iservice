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

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการผู้ใช้งาน - ระบบบริการดิจิทัล</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-gradient-to-r from-teal-700 to-teal-500 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-xl font-bold">
                        <i class="fas fa-home mr-2"></i>Admin Panel
                    </a>
                    <span class="text-teal-200">|</span>
                    <span class="text-white">จัดการผู้ใช้งาน</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm">
                        <i class="fas fa-user-circle mr-2"></i>
                        <?php echo htmlspecialchars($user['full_name']); ?>
                    </span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>ออกจากระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="main-content-transition lg:ml-0">
        <div class="p-6">
            <!-- Page Title -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">จัดการผู้ใช้งาน</h1>
                <p class="text-gray-600">ระบบจัดการข้อมูลผู้ใช้งาน เพิ่ม แก้ไข ลบ และจัดการสิทธิ์</p>
            </div>
        <!-- Header -->
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
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table id="usersTable">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อผู้ใช้</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>Email</th>
                            <th>หน่วยงาน</th>
                            <th>บทบาท</th>
                            <th>สถานะ</th>
                            <th>วันที่สร้าง</th>
                            <th>การจัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users_result->fetch_assoc()): ?>
                        <tr data-role="<?php echo $user['role']; ?>" data-status="<?php echo $user['status']; ?>">
                            <td class="font-mono text-sm"><?php echo $user['user_id']; ?></td>
                            <td>
                                <div class="flex items-center">
                                    <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center mr-2">
                                        <span class="text-teal-600 font-bold text-sm">
                                            <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                                        </span>
                                    </div>
                                    <span class="font-medium"><?php echo htmlspecialchars($user['username']); ?></span>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <?php if ($user['department_name']): ?>
                                    <span class="text-sm text-gray-600">
                                        <?php echo htmlspecialchars($user['department_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-gray-400 text-sm">ไม่ระบุ</span>
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
                            <td class="text-sm text-gray-600">
                                <?php echo date('d/m/Y', strtotime($user['created_at'])); ?>
                            </td>
                            <td>
                                <div class="flex space-x-2">
                                    <button onclick="viewUser(<?php echo $user['user_id']; ?>)"
                                            class="text-blue-600 hover:text-blue-800" title="ดูรายละเอียด">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="editUser(<?php echo $user['user_id']; ?>)"
                                            class="text-green-600 hover:text-green-800" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <button onclick="deleteUser(<?php echo $user['user_id']; ?>)"
                                            class="text-red-600 hover:text-red-800" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="userModal" class="modal">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-user-plus text-teal-600 mr-2"></i>
                    <span id="modalTitle">เพิ่มผู้ใช้ใหม่</span>
                </h2>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-2xl"></i>
                </button>
            </div>

            <form id="userForm">
                <input type="hidden" id="userId" name="user_id">
                <input type="hidden" id="formAction" name="action" value="add">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="form-group">
                        <label for="username">ชื่อผู้ใช้ *</label>
                        <input type="text" id="username" name="username" required
                               pattern="[a-zA-Z0-9_]{4,20}"
                               placeholder="4-20 ตัวอักษร (a-z, 0-9, _)">
                    </div>

                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group md:col-span-2">
                        <label for="prefix_id">คำนำหน้า *</label>
                        <select id="prefix_id" name="prefix_id" required>
                            <option value="">-- เลือกคำนำหน้า *</option>
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
                        <label for="first_name">ชื่อ *</label>
                        <input type="text" id="first_name" name="first_name" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">นามสกุล *</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">เบอร์โทรศัพท์</label>
                        <input type="tel" id="phone" name="phone" pattern="[0-9]{10}">
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
                        <input type="text" id="position" name="position">
                    </div>

                    <div class="form-group">
                        <label for="role">บทบาท *</label>
                        <select id="role" name="role" required>
                            <option value="user">ผู้ใช้ทั่วไป</option>
                            <option value="staff">เจ้าหน้าที่</option>
                            <option value="admin">ผู้ดูแลระบบ</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="status">สถานะ *</label>
                        <select id="status" name="status" required>
                            <option value="active">ใช้งาน</option>
                            <option value="inactive">ไม่ใช้งาน</option>
                            <option value="suspended">ระงับการใช้งาน</option>
                        </select>
                    </div>

                    <div class="form-group md:col-span-2" id="passwordGroup">
                        <label for="password">รหัสผ่าน <span id="passwordRequired">*</span></label>
                        <input type="password" id="password" name="password">
                        <p class="text-sm text-gray-500 mt-1">
                            <span id="passwordHint">ต้องมีความยาวอย่างน้อย 6 ตัวอักษร</span>
                        </p>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">
                        <i class="fas fa-times mr-2"></i>ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save mr-2"></i>บันทึก
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

    <?php include 'admin-layout/footer.php'; ?>
</main>

</body>
</html>
