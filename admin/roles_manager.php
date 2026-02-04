<?php
/**
 * Admin Roles Manager
 * หน้าจัดการบทบาท/หน้าที่
 */

require_once '../config/database.php';
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'จัดการบทบาท';
$current_page = 'roles_manager';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'จัดการบทบาท']
];

// Get current user info
$user = [
    'username' => $_SESSION['username'] ?? 'Admin',
    'role' => $_SESSION['role'] ?? 'admin'
];

// Fetch all roles
$roles_query = $conn->query("SELECT * FROM roles ORDER BY display_order ASC, role_name ASC");
$roles = [];
if ($roles_query) {
    while ($row = $roles_query->fetch_assoc()) {
        $roles[] = $row;
    }
}

// Count users per role
$user_counts = [];
$counts_query = $conn->query("SELECT role_id, COUNT(*) as cnt FROM user_roles WHERE is_active = 1 GROUP BY role_id");
if ($counts_query) {
    while ($row = $counts_query->fetch_assoc()) {
        $user_counts[$row['role_id']] = $row['cnt'];
    }
}

// Include layout
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
    .role-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.25rem;
        transition: all 0.2s ease;
    }
    .role-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        border-color: #d1d5db;
    }
    .role-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }
    .role-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.7rem;
        font-weight: 500;
        gap: 0.25rem;
    }
    .badge-assign {
        background: #dbeafe;
        color: #2563eb;
    }
    .badge-receive {
        background: #dcfce7;
        color: #16a34a;
    }
    .badge-inactive {
        background: #f3f4f6;
        color: #9ca3af;
    }
    .action-btn {
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: none;
        background: transparent;
        color: #9ca3af;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .action-btn:hover {
        background: #f3f4f6;
    }
    .action-btn-edit:hover { color: #009933; }
    .action-btn-delete:hover { color: #ef4444; background: #fef2f2; }

    /* Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4);
        align-items: center;
        justify-content: center;
    }
    .modal.active {
        display: flex;
    }
    .modal-content {
        background: white;
        border-radius: 0.75rem;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        width: 90%;
        max-width: 500px;
        max-height: 85vh;
        overflow-y: auto;
        padding: 1.5rem;
        animation: modalSlide 0.2s ease;
    }
    @keyframes modalSlide {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .form-group {
        margin-bottom: 1rem;
    }
    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
        font-size: 0.875rem;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.15s ease;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #009933;
        box-shadow: 0 0 0 3px rgba(0,153,51,0.1);
    }
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
    .color-picker-wrapper {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    .color-picker-wrapper input[type="color"] {
        width: 3rem;
        height: 2.5rem;
        padding: 0.25rem;
        cursor: pointer;
    }
    .icon-preview {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f3f4f6;
    }
    .checkbox-group {
        display: flex;
        gap: 1.5rem;
    }
    .checkbox-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        cursor: pointer;
        font-weight: normal;
    }
    .checkbox-group input[type="checkbox"] {
        width: 1rem;
        height: 1rem;
    }
</style>

<div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">จัดการบทบาท</h1>
            <p class="text-gray-500 text-sm mt-1">กำหนดบทบาทและหน้าที่สำหรับการมอบหมายงาน</p>
        </div>
        <button onclick="openAddModal()" class="btn btn-primary">
            <i class="fas fa-plus"></i>
            เพิ่มบทบาท
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">บทบาททั้งหมด</p>
                    <p class="text-2xl font-semibold text-gray-800 mt-1"><?= count($roles) ?></p>
                </div>
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-user-tag text-blue-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">มอบหมายได้</p>
                    <p class="text-2xl font-semibold text-blue-600 mt-1"><?= count(array_filter($roles, fn($r) => $r['can_assign'])) ?></p>
                </div>
                <div class="w-10 h-10 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-hand-point-right text-green-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">รับงานได้</p>
                    <p class="text-2xl font-semibold text-green-600 mt-1"><?= count(array_filter($roles, fn($r) => $r['can_be_assigned'])) ?></p>
                </div>
                <div class="w-10 h-10 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-tasks text-purple-500"></i>
                </div>
            </div>
        </div>
        <div class="bg-white border border-gray-200 rounded-xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase">ผู้ใช้ที่มีบทบาท</p>
                    <p class="text-2xl font-semibold text-orange-600 mt-1"><?= array_sum($user_counts) ?></p>
                </div>
                <div class="w-10 h-10 bg-orange-50 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-orange-500"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Roles Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($roles as $role): ?>
        <div class="role-card" data-role-id="<?= $role['role_id'] ?>">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-3">
                    <div class="role-icon" style="background: <?= $role['role_color'] ?>20; color: <?= $role['role_color'] ?>;">
                        <i class="fas <?= htmlspecialchars($role['role_icon']) ?>"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($role['role_name']) ?></h3>
                        <p class="text-xs text-gray-400"><?= htmlspecialchars($role['role_code']) ?></p>
                    </div>
                </div>
                <div class="flex gap-1">
                    <button class="action-btn action-btn-edit" onclick="editRole(<?= $role['role_id'] ?>)" title="แก้ไข">
                        <i class="fas fa-pen text-sm"></i>
                    </button>
                    <?php if ($role['role_code'] !== 'all'): ?>
                    <button class="action-btn action-btn-delete" onclick="deleteRole(<?= $role['role_id'] ?>, '<?= htmlspecialchars($role['role_name']) ?>')" title="ลบ">
                        <i class="fas fa-trash text-sm"></i>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($role['description'] ?? '-') ?></p>

            <div class="flex flex-wrap gap-2 mb-3">
                <?php if ($role['can_assign']): ?>
                <span class="role-badge badge-assign">
                    <i class="fas fa-hand-point-right"></i> มอบหมายได้
                </span>
                <?php endif; ?>
                <?php if ($role['can_be_assigned']): ?>
                <span class="role-badge badge-receive">
                    <i class="fas fa-inbox"></i> รับงานได้
                </span>
                <?php endif; ?>
                <?php if (!$role['is_active']): ?>
                <span class="role-badge badge-inactive">
                    <i class="fas fa-pause"></i> ไม่ใช้งาน
                </span>
                <?php endif; ?>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                <span class="text-sm text-gray-500">
                    <i class="fas fa-users mr-1"></i>
                    <?= $user_counts[$role['role_id']] ?? 0 ?> คน
                </span>
                <a href="user_roles.php?role=<?= $role['role_id'] ?>" class="text-sm text-green-600 hover:text-green-700">
                    ดูผู้ใช้ <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($roles)): ?>
        <div class="col-span-full text-center py-12 text-gray-400">
            <i class="fas fa-user-tag text-4xl mb-3 opacity-30"></i>
            <p>ยังไม่มีบทบาทในระบบ</p>
            <p class="text-sm mt-2">กรุณาเรียกใช้ <a href="../setup_roles.php" class="text-green-600 hover:underline">setup_roles.php</a> เพื่อสร้างข้อมูลเริ่มต้น</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Role Modal -->
<div id="roleModal" class="modal">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800" id="modalTitle">เพิ่มบทบาทใหม่</h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="roleForm">
            <input type="hidden" id="roleId" name="role_id">
            <input type="hidden" id="formAction" name="action" value="add">

            <div class="form-group">
                <label>รหัสบทบาท <span class="text-red-500">*</span></label>
                <input type="text" id="roleCode" name="role_code" required
                       pattern="[a-z0-9_]+" placeholder="เช่น photographer, mc, it_support"
                       class="lowercase">
                <p class="text-xs text-gray-400 mt-1">ใช้ตัวอักษรเล็ก ตัวเลข และ _ เท่านั้น</p>
            </div>

            <div class="form-group">
                <label>ชื่อบทบาท <span class="text-red-500">*</span></label>
                <input type="text" id="roleName" name="role_name" required placeholder="เช่น ช่างภาพ, พิธีกร">
            </div>

            <div class="form-group">
                <label>คำอธิบาย</label>
                <textarea id="roleDescription" name="description" rows="2" placeholder="อธิบายหน้าที่ของบทบาทนี้"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="form-group">
                    <label>ไอคอน</label>
                    <div class="flex items-center gap-2">
                        <input type="text" id="roleIcon" name="role_icon" value="fa-user-tag" placeholder="fa-camera">
                        <div class="icon-preview" id="iconPreview">
                            <i class="fas fa-user-tag text-gray-600"></i>
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-1"><a href="https://fontawesome.com/icons" target="_blank" class="text-green-600">ดูไอคอน</a></p>
                </div>

                <div class="form-group">
                    <label>สี</label>
                    <div class="color-picker-wrapper">
                        <input type="color" id="roleColor" name="role_color" value="#6b7280">
                        <input type="text" id="roleColorText" value="#6b7280" class="flex-1" pattern="^#[0-9A-Fa-f]{6}$">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>ลำดับการแสดง</label>
                <input type="number" id="displayOrder" name="display_order" value="0" min="0">
            </div>

            <div class="form-group">
                <label class="mb-2">สิทธิ์</label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="canAssign" name="can_assign" value="1">
                        <span>มอบหมายงานให้ผู้อื่นได้</span>
                    </label>
                    <label>
                        <input type="checkbox" id="canBeAssigned" name="can_be_assigned" value="1" checked>
                        <span>รับมอบหมายงานได้</span>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="mb-2">สถานะ</label>
                <div class="checkbox-group">
                    <label>
                        <input type="checkbox" id="isActive" name="is_active" value="1" checked>
                        <span>เปิดใช้งาน</span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
// Roles data for editing
const rolesData = <?= json_encode($roles) ?>;

// Icon preview
document.getElementById('roleIcon').addEventListener('input', function() {
    const icon = this.value || 'fa-user-tag';
    document.getElementById('iconPreview').innerHTML = `<i class="fas ${icon} text-gray-600"></i>`;
});

// Color sync
document.getElementById('roleColor').addEventListener('input', function() {
    document.getElementById('roleColorText').value = this.value;
});
document.getElementById('roleColorText').addEventListener('input', function() {
    if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) {
        document.getElementById('roleColor').value = this.value;
    }
});

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'เพิ่มบทบาทใหม่';
    document.getElementById('formAction').value = 'add';
    document.getElementById('roleForm').reset();
    document.getElementById('roleColor').value = '#6b7280';
    document.getElementById('roleColorText').value = '#6b7280';
    document.getElementById('iconPreview').innerHTML = '<i class="fas fa-user-tag text-gray-600"></i>';
    document.getElementById('canBeAssigned').checked = true;
    document.getElementById('isActive').checked = true;
    document.getElementById('roleModal').classList.add('active');
}

function editRole(roleId) {
    const role = rolesData.find(r => r.role_id == roleId);
    if (!role) return;

    document.getElementById('modalTitle').textContent = 'แก้ไขบทบาท';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('roleId').value = role.role_id;
    document.getElementById('roleCode').value = role.role_code;
    document.getElementById('roleName').value = role.role_name;
    document.getElementById('roleDescription').value = role.description || '';
    document.getElementById('roleIcon').value = role.role_icon || 'fa-user-tag';
    document.getElementById('roleColor').value = role.role_color || '#6b7280';
    document.getElementById('roleColorText').value = role.role_color || '#6b7280';
    document.getElementById('displayOrder').value = role.display_order || 0;
    document.getElementById('canAssign').checked = role.can_assign == 1;
    document.getElementById('canBeAssigned').checked = role.can_be_assigned == 1;
    document.getElementById('isActive').checked = role.is_active == 1;

    document.getElementById('iconPreview').innerHTML = `<i class="fas ${role.role_icon || 'fa-user-tag'} text-gray-600"></i>`;
    document.getElementById('roleModal').classList.add('active');
}

function closeModal() {
    document.getElementById('roleModal').classList.remove('active');
}

function deleteRole(roleId, roleName) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        html: `ต้องการลบบทบาท <strong>${roleName}</strong> หรือไม่?<br><small class="text-gray-500">ผู้ใช้ที่มีบทบาทนี้จะถูกยกเลิกออก</small>`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('role_id', roleId);

            fetch('api/roles_api.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'ลบสำเร็จ',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => location.reload());
                } else {
                    Swal.fire('ผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

// Form submit
document.getElementById('roleForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    // Handle unchecked checkboxes
    if (!document.getElementById('canAssign').checked) formData.set('can_assign', '0');
    if (!document.getElementById('canBeAssigned').checked) formData.set('can_be_assigned', '0');
    if (!document.getElementById('isActive').checked) formData.set('is_active', '0');

    fetch('api/roles_api.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'บันทึกสำเร็จ',
                showConfirmButton: false,
                timer: 1500
            }).then(() => location.reload());
        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
    });
});

// Close modal on outside click
document.getElementById('roleModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include 'admin-layout/footer.php'; ?>
