<?php
/**
 * Edit User Page
 * Form to edit user details.
 */

require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($user_id)) {
    header('Location: user-manager.php');
    exit;
}

// Get user detail from view
$stmt = $conn->prepare("SELECT * FROM v_users_full WHERE user_id = ?");
if (!$stmt) {
    header('Location: user-manager.php?error=db');
    exit;
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

if (!$user) {
    header('Location: user-manager.php?error=notfound');
    exit;
}

// Fetch departments for dropdown
$departments = [];
$dept_query = $conn->query("SELECT * FROM departments ORDER BY department_name");
if ($dept_query) {
    $departments = $dept_query->fetch_all(MYSQLI_ASSOC);
}

// Fetch prefixes for dropdown
$prefixes = [];
$prefix_query = $conn->query("SELECT * FROM prefixes ORDER BY prefix_name");
if ($prefix_query) {
    $prefixes = $prefix_query->fetch_all(MYSQLI_ASSOC);
}

$page_title = 'แก้ไขข้อมูลผู้ใช้ - ' . htmlspecialchars($user['full_name']);
$current_page = 'user-manager';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'url' => 'index.php', 'icon' => 'fa-home'],
    ['label' => 'จัดการผู้ใช้งาน', 'url' => 'user-manager.php'],
    ['label' => htmlspecialchars($user['full_name']), 'url' => 'user_detail.php?id=' . $user_id],
    ['label' => 'แก้ไข']
];

include __DIR__ . '/admin-layout/header.php';
include __DIR__ . '/admin-layout/sidebar.php';
include __DIR__ . '/admin-layout/topbar.php';
?>
<div class="p-4 md:p-6">
    <form id="editUserForm" class="bg-white p-6 rounded-lg shadow-md">
        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
        <h2 class="text-2xl font-semibold mb-6">แก้ไขข้อมูลผู้ใช้</h2>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้</label>
                <input type="text" id="username" name="username" class="w-full border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($user['username']) ?>">
            </div>
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">อีเมล</label>
                <input type="email" id="email" name="email" class="w-full border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($user['email']) ?>">
            </div>
            <div>
                <label for="prefix_id" class="block text-sm font-medium text-gray-700 mb-1">คำนำหน้า</label>
                <select id="prefix_id" name="prefix_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <?php foreach ($prefixes as $prefix): ?>
                        <option value="<?= $prefix['prefix_id'] ?>" <?= ($user['prefix_id'] == $prefix['prefix_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($prefix['prefix_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">ชื่อ</label>
                <input type="text" id="first_name" name="first_name" class="w-full border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($user['first_name']) ?>">
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">นามสกุล</label>
                <input type="text" id="last_name" name="last_name" class="w-full border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($user['last_name']) ?>">
            </div>
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">โทรศัพท์</label>
                <input type="text" id="phone" name="phone" class="w-full border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div>
                <label for="department_id" class="block text-sm font-medium text-gray-700 mb-1">หน่วยงาน</label>
                <select id="department_id" name="department_id" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">-- เลือกหน่วยงาน --</option>
                    <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['department_id'] ?>" <?= ($user['department_id'] == $department['department_id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="position" class="block text-sm font-medium text-gray-700 mb-1">ตำแหน่ง</label>
                <input type="text" id="position" name="position" class="w-full border-gray-300 rounded-md shadow-sm" value="<?= htmlspecialchars($user['position'] ?? '') ?>">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">บทบาท</label>
                <select id="role" name="role" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="user" <?= ($user['role'] == 'user') ? 'selected' : '' ?>>ผู้ใช้ทั่วไป</option>
                    <option value="staff" <?= ($user['role'] == 'staff') ? 'selected' : '' ?>>เจ้าหน้าที่</option>
                    <option value="admin" <?= ($user['role'] == 'admin') ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
                </select>
            </div>
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-1">สถานะ</label>
                <select id="status" name="status" class="w-full border-gray-300 rounded-md shadow-sm">
                    <option value="active" <?= ($user['status'] == 'active') ? 'selected' : '' ?>>ใช้งาน</option>
                    <option value="inactive" <?= ($user['status'] == 'inactive') ? 'selected' : '' ?>>ไม่ใช้งาน</option>
                    <option value="suspended" <?= ($user['status'] == 'suspended') ? 'selected' : '' ?>>ระงับ</option>
                </select>
            </div>
        </div>

        <div class="mt-8 flex justify-end gap-3">
            <a href="user_detail.php?id=<?= $user['user_id'] ?>" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-300">ยกเลิก</a>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
            </button>
        </div>
    </form>
</div>

<script>
document.getElementById('editUserForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    formData.append('action', 'edit');

    const result = await Swal.fire({
        title: 'ยืนยันการแก้ไข',
        text: "คุณต้องการบันทึกการเปลี่ยนแปลงข้อมูลผู้ใช้ใช่หรือไม่?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ใช่, บันทึก',
        cancelButtonText: 'ยกเลิก'
    });

    if (result.isConfirmed) {
        try {
            const response = await fetch('api/user_manager_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                await Swal.fire({
                    title: 'สำเร็จ!',
                    text: 'ข้อมูลผู้ใช้ได้รับการอัปเดตเรียบร้อยแล้ว',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                window.location.href = 'user_detail.php?id=<?= $user['user_id'] ?>';
            } else {
                Swal.fire('ผิดพลาด', data.message || 'ไม่สามารถอัปเดตข้อมูลได้', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาดบางอย่างขึ้น', 'error');
        }
    }
});
</script>

<?php include __DIR__ . '/admin-layout/footer.php'; ?>
