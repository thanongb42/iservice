<?php
/**
 * Department Management Page
 * หน้าจัดการโครงสร้างหน่วยงาน (CRUD) - Modal CRUD with SweetAlert2
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
        /* SweetAlert2 custom styling for department modal */
        .swal2-popup.dept-modal {
            width: 42em !important;
            max-width: 95vw;
        }
        .dept-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            text-align: left;
        }
        .dept-form-grid .full-width {
            grid-column: 1 / -1;
        }
        .dept-form-grid label {
            display: block;
            font-size: 0.8rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 4px;
        }
        .dept-form-grid input,
        .dept-form-grid select {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: border-color 0.15s;
        }
        .dept-form-grid input:focus,
        .dept-form-grid select:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        .dept-form-grid .code-wrapper {
            position: relative;
        }
        .dept-form-grid .code-check-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
        }
        .dept-form-grid .code-msg {
            font-size: 0.7rem;
            margin-top: 2px;
        }
    </style>

<!-- Department Tree - Full Width -->
<div class="bg-white rounded-lg shadow p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-sitemap text-teal-600"></i> โครงสร้างหน่วยงาน (<?= count($departments) ?>)
        </h2>
        <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm transition">
            <i class="fas fa-plus mr-2"></i>เพิ่มหน่วยงานใหม่
        </button>
    </div>

    <!-- Department Tree -->
    <div class="overflow-y-auto" style="max-height: 75vh;">
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
                        <button onclick="editDepartment(<?= $dept['department_id'] ?>)" class="text-blue-600 hover:text-blue-800" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteDepartment(<?= $dept['department_id'] ?>)" class="text-red-600 hover:text-red-800" title="ลบ">
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

<script>
    const levelTypes = <?= json_encode($level_types) ?>;
    const allDepartments = <?= json_encode($departments) ?>;
    let codeCheckTimeout = null;
    let isCodeAvailable = false;

    // Define valid child types based on parent's type
    const parentChildTypeMap = {
        'สำนัก': ['ส่วน', 'ฝ่าย'],
        'กอง': ['ฝ่าย'],
        'พิเศษ': ['ฝ่าย'],
        'ส่วน': ['ฝ่าย'],
        'ฝ่าย': ['งาน'],
        'กลุ่มงาน': ['งาน']
    };

    // Build modal form HTML
    function buildFormHtml(data = {}) {
        const isEdit = !!data.department_id;
        return `
        <div class="dept-form-grid">
            <div>
                <label>ระดับ <span class="text-red-500">*</span></label>
                <select id="swal-level" onchange="onLevelChange()" required>
                    <option value="">-- เลือกระดับ --</option>
                    <option value="1" ${data.level == 1 ? 'selected' : ''}>1 - สำนัก/กอง</option>
                    <option value="2" ${data.level == 2 ? 'selected' : ''}>2 - ส่วน</option>
                    <option value="3" ${data.level == 3 ? 'selected' : ''}>3 - ฝ่าย/กลุ่มงาน</option>
                    <option value="4" ${data.level == 4 ? 'selected' : ''}>4 - งาน</option>
                </select>
            </div>
            <div id="swal-parent-container" style="display:${data.level > 1 ? 'block' : 'none'};">
                <label>หน่วยงานแม่ <span class="text-red-500">*</span></label>
                <select id="swal-parent-id" onchange="onParentChange()">
                    <option value="">-- เลือกหน่วยงานแม่ --</option>
                </select>
                <p class="code-msg text-gray-500" id="swal-parent-hint"></p>
            </div>
            <div>
                <label>รหัสหน่วยงาน <span class="text-red-500">*</span></label>
                <div class="code-wrapper">
                    <input type="text" id="swal-code" value="${data.department_code || ''}" placeholder="D001, 53603.1" style="text-transform:uppercase; padding-right:35px;" required>
                    <span class="code-check-icon" id="swal-code-icon"></span>
                </div>
                <p class="code-msg" id="swal-code-msg"></p>
            </div>
            <div>
                <label>ชื่อหน่วยงาน <span class="text-red-500">*</span></label>
                <input type="text" id="swal-name" value="${escapeHtml(data.department_name || '')}" placeholder="สำนักปลัดเทศบาล" required>
            </div>
            <div>
                <label>ชื่อย่อ (สูงสุด 5 ตัว)</label>
                <input type="text" id="swal-short" value="${escapeHtml(data.short_name || '')}" maxlength="5" placeholder="สป" style="text-transform:uppercase;">
            </div>
            <div>
                <label>ประเภท</label>
                <select id="swal-level-type">
                    <option value="">-- เลือกประเภท --</option>
                </select>
            </div>
            <div>
                <label>อาคาร</label>
                <input type="text" id="swal-building" value="${escapeHtml(data.building || '')}">
            </div>
            <div>
                <label>ชั้น</label>
                <input type="text" id="swal-floor" value="${escapeHtml(data.floor || '')}">
            </div>
            <div>
                <label>โทรศัพท์</label>
                <input type="tel" id="swal-phone" value="${escapeHtml(data.phone || '')}">
            </div>
            <div>
                <label>อีเมล</label>
                <input type="email" id="swal-email" value="${escapeHtml(data.email || '')}">
            </div>
            <div>
                <label>รหัสงบประมาณ</label>
                <input type="text" id="swal-budget" value="${escapeHtml(data.budget_code || '')}">
            </div>
            <div class="flex items-center" style="align-self:end; padding-bottom:8px;">
                <input type="checkbox" id="swal-status" ${(data.status || 'active') == 'active' ? 'checked' : ''} style="width:auto; margin-right:8px;">
                <label style="margin:0; cursor:pointer;" for="swal-status">เปิดใช้งาน</label>
            </div>
        </div>`;
    }

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Open Add Modal
    function openAddModal() {
        isCodeAvailable = false;
        Swal.fire({
            title: '<i class="fas fa-plus text-teal-600"></i> เพิ่มหน่วยงานใหม่',
            html: buildFormHtml(),
            customClass: { popup: 'dept-modal' },
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-plus mr-1"></i> เพิ่มหน่วยงาน',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#6b7280',
            didOpen: () => {
                setupCodeCheck(null);
            },
            preConfirm: () => validateAndCollect('add', null)
        }).then(result => {
            if (result.isConfirmed && result.value) {
                submitDepartment(result.value);
            }
        });
    }

    // Edit department - open modal with pre-filled data
    async function editDepartment(id) {
        try {
            const response = await fetch(`api/get_department.php?id=${id}`);
            const dept = await response.json();
            if (!dept) throw new Error('No data');

            isCodeAvailable = true; // Allow saving in edit mode

            Swal.fire({
                title: '<i class="fas fa-edit text-teal-600"></i> แก้ไขหน่วยงาน',
                html: buildFormHtml(dept),
                customClass: { popup: 'dept-modal' },
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึกการแก้ไข',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6b7280',
                didOpen: () => {
                    // Load parent options and set value
                    populateParentOptions(dept.level, dept.parent_department_id);
                    // Load level type options and set value
                    setTimeout(async () => {
                        await populateLevelTypeOptions(dept.parent_department_id, dept.level);
                        const ltSelect = document.getElementById('swal-level-type');
                        if (ltSelect && dept.level_type) ltSelect.value = dept.level_type;
                    }, 150);
                    setupCodeCheck(dept.department_id);
                },
                preConfirm: () => validateAndCollect('update', dept.department_id)
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    submitDepartment(result.value);
                }
            });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้', confirmButtonColor: '#0d9488' });
        }
    }

    // Populate parent department options
    function populateParentOptions(level, selectedParentId) {
        const parentSelect = document.getElementById('swal-parent-id');
        const parentContainer = document.getElementById('swal-parent-container');
        const parentHint = document.getElementById('swal-parent-hint');

        if (!level || parseInt(level) === 1) {
            parentContainer.style.display = 'none';
            return;
        }

        parentContainer.style.display = 'block';
        const eligibleParents = allDepartments.filter(d => d.level < parseInt(level));

        parentSelect.innerHTML = '<option value="">-- เลือกหน่วยงานแม่ --</option>';
        eligibleParents.forEach(d => {
            const indent = '　'.repeat(d.level - 1);
            const opt = new Option(indent + d.department_name + (d.short_name ? ` (${d.short_name})` : ''), d.department_id);
            opt.dataset.level = d.level;
            parentSelect.add(opt);
        });

        if (selectedParentId) parentSelect.value = selectedParentId;

        const levelNames = {
            2: 'สำนัก/กอง (ระดับ 1)',
            3: 'สำนัก/กอง หรือ ส่วน (ระดับ 1-2)',
            4: 'สำนัก/กอง, ส่วน หรือ ฝ่าย (ระดับ 1-3)'
        };
        if (parentHint) parentHint.textContent = `เลือกได้จาก: ${levelNames[level] || ''}`;
    }

    // Populate level type options based on parent
    async function populateLevelTypeOptions(parentId, level) {
        const ltSelect = document.getElementById('swal-level-type');
        if (!ltSelect) return;
        ltSelect.innerHTML = '<option value="">-- เลือกประเภท --</option>';

        if (parentId) {
            try {
                const res = await fetch(`api/get_department.php?id=${parentId}`);
                const parent = await res.json();
                if (parent && parent.level_type) {
                    const validTypes = parentChildTypeMap[parent.level_type];
                    if (validTypes) {
                        validTypes.forEach(t => ltSelect.add(new Option(t, t)));
                        return;
                    }
                }
            } catch (e) { /* fallback below */ }
        }
        // Fallback: level-based
        if (levelTypes[level]) {
            levelTypes[level].forEach(t => ltSelect.add(new Option(t, t)));
        }
    }

    // Called when level changes in modal
    function onLevelChange() {
        const level = document.getElementById('swal-level').value;
        populateParentOptions(level, null);
        populateLevelTypeOptions(null, level);
    }

    // Called when parent changes in modal
    async function onParentChange() {
        const parentId = document.getElementById('swal-parent-id').value;
        const level = document.getElementById('swal-level').value;
        await populateLevelTypeOptions(parentId, level);
    }

    // Setup real-time code checking inside modal
    function setupCodeCheck(excludeId) {
        const codeInput = document.getElementById('swal-code');
        if (!codeInput) return;

        codeInput.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
            clearTimeout(codeCheckTimeout);
            codeCheckTimeout = setTimeout(() => checkCode(excludeId), 500);
        });
    }

    async function checkCode(excludeId) {
        const codeInput = document.getElementById('swal-code');
        const code = (codeInput?.value || '').trim().toUpperCase();
        const icon = document.getElementById('swal-code-icon');
        const msg = document.getElementById('swal-code-msg');

        if (!code) {
            if (icon) icon.innerHTML = '';
            if (msg) { msg.textContent = ''; msg.className = 'code-msg'; }
            isCodeAvailable = false;
            return;
        }

        if (icon) icon.innerHTML = '<i class="fas fa-spinner fa-spin text-gray-400"></i>';
        if (msg) { msg.textContent = 'กำลังตรวจสอบ...'; msg.className = 'code-msg text-gray-500'; }

        try {
            const url = `api/check_department_code.php?code=${encodeURIComponent(code)}${excludeId ? '&exclude_id=' + excludeId : ''}`;
            const res = await fetch(url);
            const result = await res.json();

            if (result.available) {
                if (icon) icon.innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
                if (msg) { msg.textContent = result.message; msg.className = 'code-msg text-green-600'; }
                isCodeAvailable = true;
            } else {
                if (icon) icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
                if (msg) { msg.textContent = result.message; msg.className = 'code-msg text-red-600'; }
                isCodeAvailable = false;
            }
        } catch (e) {
            if (icon) icon.innerHTML = '<i class="fas fa-exclamation-circle text-yellow-500"></i>';
            if (msg) { msg.textContent = 'ไม่สามารถตรวจสอบได้'; msg.className = 'code-msg text-yellow-600'; }
            isCodeAvailable = false;
        }
    }

    // Validate and collect form data from modal
    function validateAndCollect(action, departmentId) {
        const level = document.getElementById('swal-level').value;
        const parentId = document.getElementById('swal-parent-id').value;
        const code = (document.getElementById('swal-code').value || '').trim().toUpperCase();
        const name = (document.getElementById('swal-name').value || '').trim();

        if (!level) {
            Swal.showValidationMessage('กรุณาเลือกระดับ');
            return false;
        }
        if (parseInt(level) > 1 && !parentId) {
            Swal.showValidationMessage('กรุณาเลือกหน่วยงานแม่');
            return false;
        }
        if (!code) {
            Swal.showValidationMessage('กรุณากรอกรหัสหน่วยงาน');
            return false;
        }
        if (!name) {
            Swal.showValidationMessage('กรุณากรอกชื่อหน่วยงาน');
            return false;
        }
        if (action === 'add' && !isCodeAvailable) {
            Swal.showValidationMessage('รหัสหน่วยงานนี้ถูกใช้งานแล้ว กรุณาใช้รหัสอื่น');
            return false;
        }

        return {
            action: action,
            id: departmentId || '',
            level: level,
            parent_department_id: parseInt(level) === 1 ? '' : parentId,
            department_code: code,
            department_name: name,
            short_name: (document.getElementById('swal-short').value || '').trim(),
            level_type: document.getElementById('swal-level-type').value,
            building: (document.getElementById('swal-building').value || '').trim(),
            floor: (document.getElementById('swal-floor').value || '').trim(),
            phone: (document.getElementById('swal-phone').value || '').trim(),
            email: (document.getElementById('swal-email').value || '').trim(),
            budget_code: (document.getElementById('swal-budget').value || '').trim(),
            status: document.getElementById('swal-status').checked ? 'active' : 'inactive',
            manager_user_id: ''
        };
    }

    // Submit department data via AJAX
    async function submitDepartment(data) {
        try {
            const formData = new FormData();
            for (const key in data) {
                formData.append(key, data[key]);
            }

            const response = await fetch('api/departments_api.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: result.message, confirmButtonColor: '#0d9488' });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message, confirmButtonColor: '#0d9488' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
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

                const response = await fetch('api/departments_api.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message, confirmButtonColor: '#0d9488' });
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message, confirmButtonColor: '#0d9488' });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
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

            const response = await fetch('api/departments_api.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: result.message, confirmButtonColor: '#0d9488', timer: 1500, showConfirmButton: false });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message, confirmButtonColor: '#0d9488' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
        }
    }
</script>

<?php include 'admin-layout/footer.php'; ?>
