<?php
/**
 * Department Management Page
 * หน้าจัดการโครงสร้างหน่วยงาน (CRUD) - AJAX Version
 */

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

// Fetch all departments
$departments = [];
$result = $conn->query("SELECT * FROM departments ORDER BY level ASC, department_name ASC");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Build tree structure
function buildDepartmentTree($departments, $parent_id = null) {
    $tree = [];
    foreach ($departments as $dept) {
        if ($dept['parent_department_id'] == $parent_id) {
            $dept['children'] = buildDepartmentTree($departments, $dept['department_id']);
            $tree[] = $dept;
        }
    }
    return $tree;
}

$departmentTree = buildDepartmentTree($departments);

// Level types
$level_types = [
    1 => ['สำนัก', 'กอง', 'พิเศษ'],
    2 => ['ส่วน', 'ฝ่าย'],
    3 => ['ฝ่าย', 'กลุ่มงาน'],
    4 => ['งาน']
];

// Page configuration
$page_title = 'จัดการหน่วยงาน';
$current_page = 'departments';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการหน่วยงาน']
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
    <title>จัดการโครงสร้างหน่วยงาน - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .tree-item { margin-left: 20px; }
        .level-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        .level-1 { background: #3b82f6; color: white; }
        .level-2 { background: #10b981; color: white; }
        .level-3 { background: #f59e0b; color: white; }
        .level-4 { background: #8b5cf6; color: white; }
    </style>
</head>
<body class="bg-gray-50">

<!-- Main Content -->
<main class="main-content-transition lg:ml-0">
    <div class="p-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-sitemap text-teal-600"></i> จัดการโครงสร้างหน่วยงาน
            </h1>
            <p class="text-gray-600">เพิ่ม แก้ไข ลบ และจัดการโครงสร้างหน่วยงาน 4 ระดับ</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Department Tree -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-800">
                            <i class="fas fa-list text-teal-600"></i> โครงสร้างหน่วยงาน (<?= count($departments) ?>)
                        </h2>
                        <button onclick="resetForm()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm transition">
                            <i class="fas fa-plus mr-2"></i>เพิ่มหน่วยงานใหม่
                        </button>
                    </div>

                    <!-- Department Tree -->
                    <div class="overflow-y-auto" style="max-height: 70vh;">
                        <?php
                        function renderDepartmentTree($tree, $level = 0) {
                            foreach ($tree as $dept):
                                $indent = $level * 20;
                                $levelClass = "level-{$dept['level']}";
                                $statusColor = $dept['status'] == 'active' ? 'text-green-500' : 'text-red-500';
                        ?>
                            <div class="border-b border-gray-200 py-3 hover:bg-gray-50" style="margin-left: <?= $indent ?>px;">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <span class="level-badge <?= $levelClass ?>">
                                                Lv<?= $dept['level'] ?>
                                            </span>
                                            <?php if ($dept['level_type']): ?>
                                                <span class="text-xs bg-gray-200 px-2 py-1 rounded">
                                                    <?= htmlspecialchars($dept['level_type']) ?>
                                                </span>
                                            <?php endif; ?>
                                            <i class="fas fa-<?= $dept['status'] == 'active' ? 'check-circle' : 'times-circle' ?> <?= $statusColor ?>"></i>
                                        </div>
                                        <h3 class="font-bold text-gray-800">
                                            <?= htmlspecialchars($dept['department_name']) ?>
                                            <?php if ($dept['short_name']): ?>
                                                <span class="text-sm text-gray-500">(<?= htmlspecialchars($dept['short_name']) ?>)</span>
                                            <?php endif; ?>
                                        </h3>
                                        <p class="text-xs text-gray-500">รหัส: <?= htmlspecialchars($dept['department_code']) ?></p>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="toggleStatus(<?= $dept['department_id'] ?>, '<?= $dept['status'] == 'active' ? 'inactive' : 'active' ?>')"
                                                class="text-gray-400 hover:text-gray-600" title="เปิด/ปิด">
                                            <i class="fas fa-toggle-<?= $dept['status'] == 'active' ? 'on' : 'off' ?> text-xl <?= $statusColor ?>"></i>
                                        </button>
                                        <button onclick="editDepartment(<?= $dept['department_id'] ?>)" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteDepartment(<?= $dept['department_id'] ?>)" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <?php
                                if (!empty($dept['children'])) {
                                    renderDepartmentTree($dept['children'], $level + 1);
                                }
                            endforeach;
                        }

                        if (empty($departmentTree)) {
                            echo '<div class="text-center py-12 text-gray-500"><i class="fas fa-inbox text-6xl mb-4"></i><p>ยังไม่มีหน่วยงานในระบบ</p></div>';
                        } else {
                            renderDepartmentTree($departmentTree);
                        }
                        ?>
                    </div>
                </div>
            </div>

            <!-- Right: Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">
                        <i class="fas fa-plus text-teal-600"></i> เพิ่มหน่วยงานใหม่
                    </h2>

                    <form id="departmentForm" class="space-y-4">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="departmentId">
                        <input type="hidden" name="manager_user_id" id="manager_user_id" value="">

                        <!-- Level (First Field) -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                ระดับ <span class="text-red-500">*</span>
                            </label>
                            <select name="level" id="level" required onchange="loadParentDepartments(); updateLevelTypeOptions()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">-- เลือกระดับ --</option>
                                <option value="1">1 - สำนัก/กอง (ระดับบนสุด)</option>
                                <option value="2">2 - ส่วน</option>
                                <option value="3">3 - ฝ่าย/กลุ่มงาน</option>
                                <option value="4">4 - งาน</option>
                            </select>
                        </div>

                        <!-- Parent Department (Dynamic based on level) -->
                        <div id="parentDeptContainer" style="display:none;">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                หน่วยงานแม่ <span class="text-red-500">*</span>
                            </label>
                            <select name="parent_department_id" id="parent_department_id" required onchange="updateLevelTypeOptions()"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">-- เลือกหน่วยงานแม่ --</option>
                            </select>
                            <p class="text-xs text-gray-500 mt-1" id="parentHint"></p>
                        </div>

                        <!-- Department Code with Real-time Check -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                รหัสหน่วยงาน <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="department_code" id="department_code" required
                                       placeholder="D001, 53603.1"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent uppercase pr-10">
                                <div class="absolute right-3 top-2.5" id="codeCheckIcon"></div>
                            </div>
                            <p class="text-xs mt-1" id="codeCheckMessage"></p>
                            <p class="text-xs text-gray-500 mt-1">ภาษาอังกฤษตัวพิมพ์ใหญ่ ตัวเลข . - _</p>
                        </div>

                        <!-- Department Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                ชื่อหน่วยงาน <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="department_name" id="department_name" required
                                   placeholder="สำนักปลัดเทศบาล"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <!-- Short Name -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                ชื่อย่อ (สูงสุด 5 ตัวอักษร)
                            </label>
                            <input type="text" name="short_name" id="short_name" maxlength="5"
                                   placeholder="สป"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent uppercase">
                        </div>

                        <!-- Level Type -->
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภท</label>
                            <select name="level_type" id="level_type"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                <option value="">-- เลือกประเภท --</option>
                            </select>
                        </div>

                        <!-- Contact Info -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">อาคาร</label>
                                <input type="text" name="building" id="building"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ชั้น</label>
                                <input type="text" name="floor" id="floor"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">โทรศัพท์</label>
                            <input type="tel" name="phone" id="phone"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">อีเมล</label>
                            <input type="email" name="email" id="email"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">รหัสงบประมาณ</label>
                            <input type="text" name="budget_code" id="budget_code"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <!-- Status -->
                        <div class="flex items-center">
                            <input type="checkbox" name="status" id="status" value="active" checked
                                   class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <label for="status" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex space-x-3 pt-4">
                            <button type="submit" id="submitBtn"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-plus mr-2"></i> เพิ่มหน่วยงาน
                            </button>

                            <button type="button" id="cancelBtn" onclick="resetForm()" style="display:none;"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-times mr-2"></i>ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        const levelTypes = <?= json_encode($level_types) ?>;
        const allDepartments = <?= json_encode($departments) ?>;
        let codeCheckTimeout = null;
        let isCodeAvailable = false;

        // Debounce function
        function debounce(func, wait) {
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(codeCheckTimeout);
                    func(...args);
                };
                clearTimeout(codeCheckTimeout);
                codeCheckTimeout = setTimeout(later, wait);
            };
        }

        // Load parent departments based on selected level
        function loadParentDepartments() {
            const level = parseInt(document.getElementById('level').value);
            const parentSelect = document.getElementById('parent_department_id');
            const parentContainer = document.getElementById('parentDeptContainer');
            const parentHint = document.getElementById('parentHint');

            if (level === 1) {
                // Level 1 - No parent needed
                parentContainer.style.display = 'none';
                parentSelect.removeAttribute('required');
                parentSelect.value = '';
            } else {
                // Level 2-4 - Show parent selector
                parentContainer.style.display = 'block';
                parentSelect.setAttribute('required', 'required');

                // Filter departments that are lower level
                const eligibleParents = allDepartments.filter(dept => dept.level < level);

                parentSelect.innerHTML = '<option value="">-- เลือกหน่วยงานแม่ --</option>';

                eligibleParents.forEach(dept => {
                    const option = document.createElement('option');
                    option.value = dept.department_id;
                    const indent = '　'.repeat(dept.level - 1);
                    option.textContent = indent + dept.department_name + (dept.short_name ? ` (${dept.short_name})` : '');
                    option.dataset.level = dept.level;
                    parentSelect.appendChild(option);
                });

                // Update hint
                const levelNames = {
                    2: 'สำนัก/กอง (ระดับ 1)',
                    3: 'สำนัก/กอง หรือ ส่วน (ระดับ 1-2)',
                    4: 'สำนัก/กอง, ส่วน หรือ ฝ่าย (ระดับ 1-3)'
                };
                parentHint.textContent = `เลือกได้จาก: ${levelNames[level]}`;
            }
        }

        // Define valid child types based on parent's type
        const parentChildTypeMap = {
            'สำนัก': ['ส่วน', 'ฝ่าย'],
            'กอง': ['ฝ่าย'],
            'พิเศษ': ['ฝ่าย'],
            'ส่วน': ['ฝ่าย'],
            'ฝ่าย': ['งาน'],
            'กลุ่มงาน': ['งาน']
        };

        // Update level type options based on parent department
        async function updateLevelTypeOptions() {
            const level = document.getElementById('level').value;
            const parentId = document.getElementById('parent_department_id').value;
            const levelTypeSelect = document.getElementById('level_type');

            levelTypeSelect.innerHTML = '<option value="">-- เลือกประเภท --</option>';

            // If parent is selected, get parent's type to determine valid child types
            if (parentId) {
                try {
                    const response = await fetch(`api/get_department.php?id=${parentId}`);
                    const parent = await response.json();

                    if (parent && parent.level_type) {
                        const validTypes = parentChildTypeMap[parent.level_type];
                        if (validTypes) {
                            validTypes.forEach(type => {
                                const option = document.createElement('option');
                                option.value = type;
                                option.textContent = type;
                                levelTypeSelect.appendChild(option);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error fetching parent department:', error);
                    // Fallback to level-based types if API fails
                    if (levelTypes[level]) {
                        levelTypes[level].forEach(type => {
                            const option = document.createElement('option');
                            option.value = type;
                            option.textContent = type;
                            levelTypeSelect.appendChild(option);
                        });
                    }
                }
            } else {
                // No parent selected, use level-based types as fallback
                if (levelTypes[level]) {
                    levelTypes[level].forEach(type => {
                        const option = document.createElement('option');
                        option.value = type;
                        option.textContent = type;
                        levelTypeSelect.appendChild(option);
                    });
                }
            }
        }

        // Real-time department code checking
        async function checkDepartmentCode() {
            const codeInput = document.getElementById('department_code');
            const code = codeInput.value.trim().toUpperCase();
            const departmentId = document.getElementById('departmentId').value;
            const icon = document.getElementById('codeCheckIcon');
            const message = document.getElementById('codeCheckMessage');

            if (code.length === 0) {
                icon.innerHTML = '';
                message.textContent = '';
                message.className = 'text-xs mt-1';
                isCodeAvailable = false;
                return;
            }

            // Show loading
            icon.innerHTML = '<i class="fas fa-spinner fa-spin text-gray-400"></i>';
            message.textContent = 'กำลังตรวจสอบ...';
            message.className = 'text-xs text-gray-500 mt-1';

            try {
                const url = `api/check_department_code.php?code=${encodeURIComponent(code)}${departmentId ? '&exclude_id=' + departmentId : ''}`;
                const response = await fetch(url);
                const result = await response.json();

                if (result.available) {
                    icon.innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
                    message.textContent = result.message;
                    message.className = 'text-xs text-green-600 mt-1';
                    isCodeAvailable = true;
                } else {
                    icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
                    message.textContent = result.message;
                    message.className = 'text-xs text-red-600 mt-1';
                    isCodeAvailable = false;
                }
            } catch (error) {
                icon.innerHTML = '<i class="fas fa-exclamation-circle text-yellow-500"></i>';
                message.textContent = 'ไม่สามารถตรวจสอบได้';
                message.className = 'text-xs text-yellow-600 mt-1';
                isCodeAvailable = false;
            }
        }

        // Debounced code check
        const debouncedCodeCheck = debounce(checkDepartmentCode, 500);

        // Attach event listener to department code input
        document.getElementById('department_code').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            debouncedCodeCheck();
        });

        // Form submit handler
        document.getElementById('departmentForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Validate department code availability (only for new records)
            const isEdit = document.getElementById('formAction').value === 'update';
            if (!isEdit && !isCodeAvailable) {
                Swal.fire({
                    icon: 'error',
                    title: 'ไม่สามารถบันทึกได้',
                    text: 'รหัสหน่วยงานนี้ถูกใช้งานแล้ว กรุณาใช้รหัสอื่น',
                    confirmButtonColor: '#4f46e5'
                });
                document.getElementById('department_code').focus();
                return;
            }

            const formData = new FormData(this);

            // Handle status checkbox
            if (!document.getElementById('status').checked) {
                formData.set('status', 'inactive');
            }

            try {
                const response = await fetch('api/departments_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonColor: '#4f46e5'
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message,
                        confirmButtonColor: '#4f46e5'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#4f46e5'
                });
            }
        });

        // Edit department
        async function editDepartment(id) {
            try {
                const response = await fetch(`api/get_department.php?id=${id}`);
                const dept = await response.json();

                if (dept) {
                    // Populate form
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('departmentId').value = dept.department_id;

                    // Set level first
                    document.getElementById('level').value = dept.level;

                    // Load parent departments based on level
                    loadParentDepartments();

                    // Then set parent value after a short delay to ensure options are loaded
                    setTimeout(() => {
                        document.getElementById('parent_department_id').value = dept.parent_department_id || '';
                    }, 100);

                    document.getElementById('department_code').value = dept.department_code;
                    document.getElementById('department_name').value = dept.department_name;
                    document.getElementById('short_name').value = dept.short_name || '';

                    // Update level type options (now parent-aware) and set the value
                    setTimeout(async () => {
                        await updateLevelTypeOptions();
                        document.getElementById('level_type').value = dept.level_type || '';
                    }, 150);

                    document.getElementById('building').value = dept.building || '';
                    document.getElementById('floor').value = dept.floor || '';
                    document.getElementById('phone').value = dept.phone || '';
                    document.getElementById('email').value = dept.email || '';
                    document.getElementById('budget_code').value = dept.budget_code || '';
                    document.getElementById('status').checked = dept.status == 'active';

                    // Clear code check status for edit mode
                    document.getElementById('codeCheckIcon').innerHTML = '';
                    document.getElementById('codeCheckMessage').textContent = '';
                    isCodeAvailable = true; // Allow saving in edit mode

                    // Update form title and button
                    document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-teal-600"></i> แก้ไขหน่วยงาน';
                    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i> บันทึกการแก้ไข';
                    document.getElementById('cancelBtn').style.display = 'block';

                    // Scroll to form
                    document.getElementById('departmentForm').scrollIntoView({ behavior: 'smooth' });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถโหลดข้อมูลได้',
                    confirmButtonColor: '#4f46e5'
                });
            }
        }

        // Delete department
        async function deleteDepartment(id) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: 'ต้องการลบหน่วยงานนี้หรือไม่? (ต้องลบหน่วยงานลูกก่อน)',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    const response = await fetch('api/departments_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: data.message,
                            confirmButtonColor: '#4f46e5'
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.message,
                            confirmButtonColor: '#4f46e5'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                        confirmButtonColor: '#4f46e5'
                    });
                }
            }
        }

        // Toggle status
        async function toggleStatus(id, status) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_status');
                formData.append('id', id);
                formData.append('status', status);

                const response = await fetch('api/departments_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonColor: '#4f46e5',
                        timer: 1500,
                        showConfirmButton: false
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message,
                        confirmButtonColor: '#4f46e5'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#4f46e5'
                });
            }
        }

        // Reset form
        function resetForm() {
            document.getElementById('departmentForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('departmentId').value = '';

            // Reset level and hide parent container
            document.getElementById('level').value = '';
            document.getElementById('parentDeptContainer').style.display = 'none';
            document.getElementById('parent_department_id').removeAttribute('required');

            // Clear level type options
            document.getElementById('level_type').innerHTML = '<option value="">-- เลือกประเภท --</option>';

            // Clear code check
            document.getElementById('codeCheckIcon').innerHTML = '';
            document.getElementById('codeCheckMessage').textContent = '';
            isCodeAvailable = false;

            document.getElementById('status').checked = true;
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus text-teal-600"></i> เพิ่มหน่วยงานใหม่';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus mr-2"></i> เพิ่มหน่วยงาน';
            document.getElementById('cancelBtn').style.display = 'none';
        }
    </script>

    <?php include 'admin-layout/footer.php'; ?>
    </div>
</main>

</body>
</html>
