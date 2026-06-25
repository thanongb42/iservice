<?php
/**
 * Internet Request Types Management
 * หน้าจัดการประเภทคำขอสำหรับฟอร์ม Internet (request-form.php?service=INTERNET)
 */

require_once '../config/database.php';
session_start();

require_admin_role();

$page_title = 'จัดการประเภทคำขอ Internet';
$current_page = 'internet_request_types';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'จัดการประเภทคำขอ Internet']
];

// --- Handle Form Submissions ---
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $type_code    = trim($_POST['type_code'] ?? '');
        $type_name    = trim($_POST['type_name'] ?? '');
        $type_name_en = trim($_POST['type_name_en'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $icon         = trim($_POST['icon'] ?? '') ?: 'fa-wifi';
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active     = isset($_POST['is_active']) ? 1 : 0;

        if ($type_code === '' || $type_name === '') {
            $error = 'กรุณากรอกรหัสประเภทและชื่อประเภท';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $type_code)) {
            $error = 'รหัสประเภทใช้ได้เฉพาะ a-z, 0-9 และ _ เท่านั้น';
        } else {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO internet_request_types (type_code, type_name, type_name_en, description, icon, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssii", $type_code, $type_name, $type_name_en, $description, $icon, $display_order, $is_active);
                if ($stmt->execute()) {
                    $message = 'เพิ่มข้อมูลสำเร็จ';
                } else {
                    $error = ($conn->errno === 1062) ? 'รหัสประเภทนี้มีอยู่แล้ว กรุณาใช้รหัสอื่น' : ('Error: ' . $stmt->error);
                }
            } else {
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE internet_request_types SET type_code=?, type_name=?, type_name_en=?, description=?, icon=?, display_order=?, is_active=? WHERE id=?");
                $stmt->bind_param("sssssiii", $type_code, $type_name, $type_name_en, $description, $icon, $display_order, $is_active, $id);
                if ($stmt->execute()) {
                    $message = 'แก้ไขข้อมูลสำเร็จ';
                } else {
                    $error = ($conn->errno === 1062) ? 'รหัสประเภทนี้มีอยู่แล้ว กรุณาใช้รหัสอื่น' : ('Error: ' . $stmt->error);
                }
            }
        }
    }

    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM internet_request_types WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'ลบข้อมูลสำเร็จ';
        } else {
            $error = 'Error: ' . $stmt->error;
        }
    }

    if ($action === 'toggle_active') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE internet_request_types SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        header('Content-Type: application/json');
        exit(json_encode(['success' => true]));
    }
}

// --- Fetch Data ---
$types = [];
$result = $conn->query("SELECT * FROM internet_request_types ORDER BY display_order ASC, id ASC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $types[] = $row;
    }
}
?>

<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<main class="main-content-transition lg:ml-0">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-wifi text-teal-600"></i> จัดการประเภทคำขอ Internet
            </h1>
            <p class="mt-2 text-gray-600">จัดการตัวเลือก "ประเภทคำขอ" ที่แสดงในฟอร์มขอใช้บริการ Internet (request-form.php?service=INTERNET)</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($message); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-teal-600"></i> รายการทั้งหมด
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลำดับ</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อประเภท</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">รหัสประเภท</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($types as $type): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo (int)$type['display_order']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <i class="fas <?php echo htmlspecialchars($type['icon'] ?: 'fa-wifi'); ?> text-teal-500 mr-1"></i>
                                            <?php echo htmlspecialchars($type['type_name']); ?>
                                        </div>
                                        <?php if (!empty($type['type_name_en'])): ?>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($type['type_name_en']); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded"><?php echo htmlspecialchars($type['type_code']); ?></code>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button onclick="toggleActive(<?php echo (int)$type['id']; ?>)" class="focus:outline-none">
                                            <?php if ($type['is_active']): ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    ใช้งาน
                                                </span>
                                            <?php else: ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    ปิดใช้งาน
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button onclick='editType(<?php echo json_encode($type, JSON_UNESCAPED_UNICODE); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?php echo (int)$type['id']; ?>)" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($types)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                            ไม่พบข้อมูล
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-plus text-teal-600" id="formIcon"></i>
                        <span id="formTitle">เพิ่มข้อมูลใหม่</span>
                    </h2>

                    <form id="typeForm" method="POST" class="space-y-4">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="typeId" value="">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อประเภท (ไทย) *</label>
                            <input type="text" name="type_name" id="type_name" required placeholder="เช่น สร้าง username&amp;password Internet กลาง"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อประเภท (English)</label>
                            <input type="text" name="type_name_en" id="type_name_en" placeholder="Optional"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">รหัสประเภท * <span class="text-xs font-normal text-gray-400">(a-z, 0-9, _ เท่านั้น — ใช้เป็นค่าใน dropdown)</span></label>
                            <input type="text" name="type_code" id="type_code" required pattern="[a-z0-9_]+" placeholder="เช่น new_username_password"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 font-mono text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                            <textarea name="description" id="description" rows="2" placeholder="Optional"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"></textarea>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ไอคอน <span class="text-xs font-normal text-gray-400">(Font Awesome)</span></label>
                                <input type="text" name="icon" id="icon" value="fa-wifi" placeholder="fa-wifi"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 font-mono text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับการแสดงผล</label>
                                <input type="number" name="display_order" id="display_order" value="0"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                        </div>

                        <div class="flex space-x-3 pt-4">
                            <button type="submit" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-save mr-2"></i> บันทึก
                            </button>
                            <button type="button" id="cancelBtn" onclick="resetForm()" style="display:none" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                                ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<form id="deleteForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>

<script>
function editType(data) {
    document.getElementById('formTitle').innerText = 'แก้ไขข้อมูล';
    document.getElementById('formAction').value = 'update';
    document.getElementById('typeId').value = data.id;
    document.getElementById('type_name').value = data.type_name;
    document.getElementById('type_name_en').value = data.type_name_en || '';
    document.getElementById('type_code').value = data.type_code;
    document.getElementById('description').value = data.description || '';
    document.getElementById('icon').value = data.icon || 'fa-wifi';
    document.getElementById('display_order').value = data.display_order;
    document.getElementById('is_active').checked = data.is_active == 1;

    document.getElementById('cancelBtn').style.display = 'block';
    document.getElementById('typeForm').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function resetForm() {
    document.getElementById('formTitle').innerText = 'เพิ่มข้อมูลใหม่';
    document.getElementById('formAction').value = 'add';
    document.getElementById('typeId').value = '';
    document.getElementById('typeForm').reset();
    document.getElementById('icon').value = 'fa-wifi';
    document.getElementById('cancelBtn').style.display = 'none';
}

function confirmDelete(id) {
    if (confirm('คุณแน่ใจหรือไม่ที่จะลบรายการนี้? (ตัวเลือกนี้จะหายไปจากฟอร์มทันที)')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function toggleActive(id) {
    fetch('internet_request_types.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_active&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>

<?php
include 'admin-layout/footer.php';
$conn->close();
?>
