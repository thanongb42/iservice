<?php
/**
 * Admin User Roles Assignment
 * หน้ากำหนดบทบาทให้ผู้ใช้
 */

require_once '../config/database.php';
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'กำหนดบทบาทผู้ใช้';
$current_page = 'user_roles';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'กำหนดบทบาทผู้ใช้']
];

$user = [
    'username' => $_SESSION['username'] ?? 'Admin',
    'role' => $_SESSION['role'] ?? 'admin'
];

// Filter by role
$filter_role = isset($_GET['role']) ? intval($_GET['role']) : 0;

// Fetch all roles
$roles_query = $conn->query("SELECT * FROM roles WHERE is_active = 1 ORDER BY display_order ASC, role_name ASC");
$roles = [];
while ($row = $roles_query->fetch_assoc()) {
    $roles[] = $row;
}

// Fetch all active users with their roles
$users_sql = "
    SELECT u.user_id, u.username, u.first_name, u.last_name, u.email, u.profile_image, u.status,
           p.prefix_name, d.department_name,
           GROUP_CONCAT(DISTINCT r.role_name ORDER BY ur.is_primary DESC, r.display_order ASC SEPARATOR ', ') as role_names,
           GROUP_CONCAT(DISTINCT r.role_id ORDER BY ur.is_primary DESC SEPARATOR ',') as role_ids
    FROM users u
    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
    LEFT JOIN departments d ON u.department_id = d.department_id
    LEFT JOIN user_roles ur ON u.user_id = ur.user_id AND ur.is_active = 1
    LEFT JOIN roles r ON ur.role_id = r.role_id AND r.is_active = 1
    WHERE u.status = 'active'
";

if ($filter_role > 0) {
    $users_sql .= " AND ur.role_id = $filter_role";
}

$users_sql .= " GROUP BY u.user_id ORDER BY u.first_name ASC";

$users_query = $conn->query($users_sql);
$users = [];
while ($row = $users_query->fetch_assoc()) {
    $users[] = $row;
}

// Include layout
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
    .user-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1rem;
        transition: all 0.2s ease;
    }
    .user-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .user-avatar {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1rem;
        overflow: hidden;
    }
    .user-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .role-tag {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.5rem;
        border-radius: 0.375rem;
        font-size: 0.7rem;
        font-weight: 500;
        gap: 0.25rem;
        margin: 0.125rem;
    }
    .btn {
        padding: 0.5rem 1rem;
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
    .btn-sm {
        padding: 0.375rem 0.75rem;
        font-size: 0.75rem;
    }

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
        max-width: 600px;
        max-height: 85vh;
        overflow-y: auto;
        padding: 1.5rem;
    }
    .role-checkbox-list {
        display: grid;
        gap: 0.75rem;
        max-height: 300px;
        overflow-y: auto;
        padding: 0.5rem;
    }
    .role-checkbox-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .role-checkbox-item:hover {
        background: #f9fafb;
    }
    .role-checkbox-item.selected {
        background: #ecfdf5;
        border-color: #009933;
    }
    .role-checkbox-item input[type="checkbox"] {
        width: 1.25rem;
        height: 1.25rem;
    }
    .role-checkbox-item .role-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.375rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .filter-btn {
        padding: 0.5rem 1rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        background: white;
        color: #374151;
        font-size: 0.875rem;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .filter-btn:hover {
        background: #f9fafb;
    }
    .filter-btn.active {
        background: #009933;
        border-color: #009933;
        color: white;
    }
</style>

<div>
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-800">กำหนดบทบาทผู้ใช้</h1>
            <p class="text-gray-500 text-sm mt-1">กำหนดบทบาทและหน้าที่ให้กับผู้ใช้ในระบบ</p>
        </div>
        <a href="roles_manager.php" class="btn btn-secondary">
            <i class="fas fa-cog"></i>
            จัดการบทบาท
        </a>
    </div>

    <!-- Filter by Role -->
    <div class="bg-white border border-gray-200 rounded-xl p-4 mb-6">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm text-gray-500 mr-2">กรองตามบทบาท:</span>
            <a href="user_roles.php" class="filter-btn <?= $filter_role == 0 ? 'active' : '' ?>">
                ทั้งหมด
            </a>
            <?php foreach ($roles as $role): ?>
            <a href="user_roles.php?role=<?= $role['role_id'] ?>"
               class="filter-btn <?= $filter_role == $role['role_id'] ? 'active' : '' ?>">
                <i class="fas <?= $role['role_icon'] ?> mr-1" style="color: <?= $filter_role == $role['role_id'] ? 'white' : $role['role_color'] ?>"></i>
                <?= htmlspecialchars($role['role_name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Users Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($users as $u): ?>
        <div class="user-card" data-user-id="<?= $u['user_id'] ?>">
            <div class="flex items-start gap-3">
                <?php if (!empty($u['profile_image']) && file_exists('../' . $u['profile_image'])): ?>
                <div class="user-avatar">
                    <img src="../<?= htmlspecialchars($u['profile_image']) ?>" alt="">
                </div>
                <?php else: ?>
                <div class="user-avatar bg-gray-100 text-gray-600">
                    <?= strtoupper(substr($u['first_name'] ?? $u['username'], 0, 1)) ?>
                </div>
                <?php endif; ?>

                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold text-gray-800 truncate">
                        <?= htmlspecialchars(($u['prefix_name'] ?? '') . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?>
                    </h3>
                    <p class="text-sm text-gray-500 truncate"><?= htmlspecialchars($u['email']) ?></p>
                    <?php if ($u['department_name']): ?>
                    <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($u['department_name']) ?></p>
                    <?php endif; ?>
                </div>

                <button onclick="openAssignModal(<?= $u['user_id'] ?>, '<?= htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])) ?>', '<?= $u['role_ids'] ?? '' ?>')"
                        class="btn btn-sm btn-primary">
                    <i class="fas fa-user-tag"></i>
                </button>
            </div>

            <div class="mt-3 pt-3 border-t border-gray-100">
                <?php if ($u['role_names']): ?>
                    <?php
                    $role_ids = explode(',', $u['role_ids'] ?? '');
                    $role_names = explode(', ', $u['role_names']);
                    foreach ($role_ids as $idx => $rid):
                        $role = array_filter($roles, fn($r) => $r['role_id'] == $rid);
                        $role = reset($role);
                        if ($role):
                    ?>
                    <span class="role-tag" style="background: <?= $role['role_color'] ?>20; color: <?= $role['role_color'] ?>;">
                        <i class="fas <?= $role['role_icon'] ?>"></i>
                        <?= htmlspecialchars($role['role_name']) ?>
                    </span>
                    <?php
                        endif;
                    endforeach;
                    ?>
                <?php else: ?>
                    <span class="text-sm text-gray-400">ยังไม่มีบทบาท</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($users)): ?>
        <div class="col-span-full text-center py-12 text-gray-400">
            <i class="fas fa-users text-4xl mb-3 opacity-30"></i>
            <p>ไม่พบผู้ใช้</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Assign Role Modal -->
<div id="assignModal" class="modal">
    <div class="modal-content">
        <div class="flex justify-between items-center mb-6 pb-4 border-b border-gray-100">
            <h2 class="text-lg font-semibold text-gray-800">
                <i class="fas fa-user-tag mr-2 text-green-600"></i>
                กำหนดบทบาท
            </h2>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 p-1">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="mb-4">
            <p class="text-sm text-gray-500">กำหนดบทบาทให้:</p>
            <p class="text-lg font-semibold text-gray-800" id="selectedUserName"></p>
        </div>

        <form id="assignForm">
            <input type="hidden" id="assignUserId" name="user_id">

            <div class="role-checkbox-list">
                <?php foreach ($roles as $role): ?>
                <label class="role-checkbox-item" data-role-id="<?= $role['role_id'] ?>">
                    <input type="checkbox" name="roles[]" value="<?= $role['role_id'] ?>">
                    <div class="role-icon" style="background: <?= $role['role_color'] ?>20; color: <?= $role['role_color'] ?>;">
                        <i class="fas <?= $role['role_icon'] ?>"></i>
                    </div>
                    <div class="flex-1">
                        <p class="font-medium text-gray-800"><?= htmlspecialchars($role['role_name']) ?></p>
                        <p class="text-xs text-gray-500"><?= htmlspecialchars($role['description'] ?? '') ?></p>
                        <div class="flex gap-2 mt-1">
                            <?php if ($role['can_assign']): ?>
                            <span class="text-xs text-blue-600"><i class="fas fa-hand-point-right"></i> มอบหมายได้</span>
                            <?php endif; ?>
                            <?php if ($role['can_be_assigned']): ?>
                            <span class="text-xs text-green-600"><i class="fas fa-inbox"></i> รับงานได้</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                <button type="button" onclick="closeModal()" class="btn btn-secondary">ยกเลิก</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    บันทึก
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let currentUserId = null;

function openAssignModal(userId, userName, roleIds) {
    currentUserId = userId;
    document.getElementById('assignUserId').value = userId;
    document.getElementById('selectedUserName').textContent = userName;

    // Reset all checkboxes
    document.querySelectorAll('.role-checkbox-item').forEach(item => {
        item.classList.remove('selected');
        item.querySelector('input').checked = false;
    });

    // Check existing roles
    if (roleIds) {
        const ids = roleIds.split(',');
        ids.forEach(id => {
            const item = document.querySelector(`.role-checkbox-item[data-role-id="${id}"]`);
            if (item) {
                item.classList.add('selected');
                item.querySelector('input').checked = true;
            }
        });
    }

    document.getElementById('assignModal').classList.add('active');
}

function closeModal() {
    document.getElementById('assignModal').classList.remove('active');
}

// Toggle checkbox visual
document.querySelectorAll('.role-checkbox-item').forEach(item => {
    item.addEventListener('click', function(e) {
        if (e.target.type !== 'checkbox') {
            const checkbox = this.querySelector('input[type="checkbox"]');
            checkbox.checked = !checkbox.checked;
        }
        this.classList.toggle('selected', this.querySelector('input').checked);
    });
});

// Form submit
document.getElementById('assignForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const userId = document.getElementById('assignUserId').value;
    const selectedRoles = Array.from(document.querySelectorAll('input[name="roles[]"]:checked')).map(cb => cb.value);

    try {
        // First, remove all existing roles
        const currentRoles = document.querySelectorAll('.role-checkbox-item');
        for (const item of currentRoles) {
            const roleId = item.dataset.roleId;
            if (!selectedRoles.includes(roleId)) {
                const formData = new FormData();
                formData.append('action', 'remove_user_role');
                formData.append('user_id', userId);
                formData.append('role_id', roleId);
                await fetch('api/roles_api.php', { method: 'POST', body: formData });
            }
        }

        // Then, assign selected roles
        for (const roleId of selectedRoles) {
            const formData = new FormData();
            formData.append('action', 'assign_user');
            formData.append('user_id', userId);
            formData.append('role_id', roleId);
            await fetch('api/roles_api.php', { method: 'POST', body: formData });
        }

        Swal.fire({
            icon: 'success',
            title: 'บันทึกสำเร็จ',
            showConfirmButton: false,
            timer: 1500
        }).then(() => location.reload());

    } catch (err) {
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึก', 'error');
    }
});

// Close modal on outside click
document.getElementById('assignModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php include 'admin-layout/footer.php'; ?>
