<?php
/**
 * Related Agencies Management
 * หน้าจัดการหน่วยงานที่เกี่ยวข้อง
 */

require_once '../config/database.php';
session_start();

$page_title = 'จัดการหน่วยงานที่เกี่ยวข้อง';
$current_page = 'related_agencies';

// --- Handle Form Submissions ---
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'update') {
        $name = trim($_POST['name'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $display_order = intval($_POST['display_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        // Handle Image Upload
        $image_path = $_POST['current_image'] ?? ''; // Keep old image if not replaced
        
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['logo_image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($ext, $allowed)) {
                $upload_dir = '../storage/agency_logos/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $new_filename = 'agency_' . time() . '_' . uniqid() . '.' . $ext;
                $target_file = $upload_dir . $new_filename;
                
                if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $target_file)) {
                    $image_path = 'storage/agency_logos/' . $new_filename;
                } else {
                    $error = 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์';
                }
            } else {
                $error = 'รองรับเฉพาะไฟล์รูปภาพ (JPG, PNG, GIF, WEBP)';
            }
        }
        
        if (!$error) {
            if ($action === 'add') {
                $stmt = $conn->prepare("INSERT INTO related_agencies (name, link, image, display_order, is_active) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssii", $name, $link, $image_path, $display_order, $is_active);
                if ($stmt->execute()) {
                    $message = 'เพิ่มข้อมูลสำเร็จ';
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            } else {
                $id = intval($_POST['id']);
                $stmt = $conn->prepare("UPDATE related_agencies SET name=?, link=?, image=?, display_order=?, is_active=? WHERE id=?");
                $stmt->bind_param("sssiii", $name, $link, $image_path, $display_order, $is_active, $id);
                if ($stmt->execute()) {
                    $message = 'แก้ไขข้อมูลสำเร็จ';
                } else {
                    $error = 'Error: ' . $stmt->error;
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("DELETE FROM related_agencies WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = 'ลบข้อมูลสำเร็จ';
        } else {
            $error = 'Error: ' . $stmt->error;
        }
    }

    if ($action === 'toggle_active') {
        $id = intval($_POST['id']);
        $stmt = $conn->prepare("UPDATE related_agencies SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        exit(json_encode(['success' => true])); // AJAX response
    }
}

// --- Fetch Data ---
$agencies = [];
$result = $conn->query("SELECT * FROM related_agencies ORDER BY display_order ASC, created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $agencies[] = $row;
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
                <i class="fas fa-building text-teal-600"></i> จัดการหน่วยงานที่เกี่ยวข้อง
            </h1>
            <p class="mt-2 text-gray-600">จัดการรายชื่อหน่วยงานและลิงก์เว็บไซต์ที่เกี่ยวข้อง</p>
        </div>

        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error; ?></span>
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
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Logo</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ชื่อหน่วยงาน</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ลิงก์</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">สถานะ</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($agencies as $agency): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($agency['image']): ?>
                                            <img src="../<?php echo htmlspecialchars($agency['image']); ?>" alt="logo" class="h-10 w-auto object-contain">
                                        <?php else: ?>
                                            <span class="text-gray-400"><i class="fas fa-image fa-2x"></i></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($agency['name']); ?></div>
                                        <div class="text-xs text-gray-500">ลำดับ: <?php echo $agency['display_order']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="<?php echo htmlspecialchars($agency['link']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900 text-sm truncate max-w-xs block">
                                            <?php echo htmlspecialchars($agency['link']); ?>
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <button onclick="toggleActive(<?php echo $agency['id']; ?>)" class="focus:outline-none">
                                            <?php if ($agency['is_active']): ?>
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
                                        <button onclick='editAgency(<?php echo json_encode($agency); ?>)' class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirmDelete(<?php echo $agency['id']; ?>)" class="text-red-600 hover:text-red-900">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($agencies)): ?>
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

                    <form id="agencyForm" method="POST" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="agencyId" value="">
                        <input type="hidden" name="current_image" id="currentImage" value="">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อหน่วยงาน *</label>
                            <input type="text" name="name" id="name" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ลิงก์เว็บไซต์ *</label>
                            <input type="url" name="link" id="link" required placeholder="https://..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับการแสดงผล</label>
                            <input type="number" name="display_order" id="display_order" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">โลโก้หน่วยงาน</label>
                            <input type="file" name="logo_image" id="logo_image" accept="image/*" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            <div id="imagePreview" class="mt-2 hidden">
                                <p class="text-xs text-gray-500">รูปภาพปัจจุบัน:</p>
                                <img src="" alt="preview" class="h-16 w-auto object-contain mt-1 border p-1 rounded">
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
function editAgency(data) {
    document.getElementById('formTitle').innerText = 'แก้ไขข้อมูล';
    document.getElementById('formAction').value = 'update';
    document.getElementById('agencyId').value = data.id;
    document.getElementById('name').value = data.name;
    document.getElementById('link').value = data.link;
    document.getElementById('display_order').value = data.display_order;
    document.getElementById('is_active').checked = data.is_active == 1;
    document.getElementById('currentImage').value = data.image;
    
    document.getElementById('cancelBtn').style.display = 'block';
    
    // Show current image
    const preview = document.getElementById('imagePreview');
    const img = preview.querySelector('img');
    if (data.image) {
        img.src = '../' + data.image;
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

function resetForm() {
    document.getElementById('formTitle').innerText = 'เพิ่มข้อมูลใหม่';
    document.getElementById('formAction').value = 'add';
    document.getElementById('agencyId').value = '';
    document.getElementById('agencyForm').reset();
    document.getElementById('cancelBtn').style.display = 'none';
    document.getElementById('imagePreview').classList.add('hidden');
    document.getElementById('currentImage').value = '';
}

function confirmDelete(id) {
    if(confirm('คุณแน่ใจหรือไม่ที่จะลบรายการนี้?')) {
        document.getElementById('deleteId').value = id;
        document.getElementById('deleteForm').submit();
    }
}

function toggleActive(id) {
    fetch('related_agencies.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_active&id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        }
    });
}
</script>

<?php
include 'admin-layout/footer.php';
$conn->close();
?>
