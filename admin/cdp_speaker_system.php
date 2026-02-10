<?php
/**
 * CDP Speaker System Management Page
 * จัดการระบบเสียงไร้สายของ CDP
 * CRUD with Image Upload (max 10 images per speaker location)
 */

require_once '../config/database.php';
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'จัดการระบบเสียง CDP';
$current_page = 'cdp_speaker_system';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'CDP', 'icon' => 'fa-folder'],
    ['label' => 'ระบบเสียงไร้สาย']
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // GET all speaker locations
        if ($action === 'list') {
            $result = $conn->query("SELECT * FROM speaker_locations ORDER BY point_number ASC");
            $locations = [];
            while ($row = $result->fetch_assoc()) {
                // Count uploaded images for this location
                $img_result = $conn->query("SELECT COUNT(*) as total FROM speaker_images WHERE location_id = " . (int)$row['id']);
                $img_row = $img_result->fetch_assoc();
                $row['image_count'] = $img_row['total'];
                $locations[] = $row;
            }
            echo json_encode(['success' => true, 'locations' => $locations]);
            exit;
        }
        
        // GET single location for edit
        if ($action === 'get') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("SELECT * FROM speaker_locations WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $location = $result->fetch_assoc();
            
            // Get images for this location
            $img_result = $conn->query("SELECT * FROM speaker_images WHERE location_id = $id ORDER BY created_at DESC");
            $images = [];
            while ($img_row = $img_result->fetch_assoc()) {
                $images[] = $img_row;
            }
            $location['images'] = $images;
            
            echo json_encode($location);
            exit;
        }
        
        // SAVE (add/update)
        if ($action === 'save') {
            $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $point_number = (int)($_POST['point_number'] ?? 0);
            $description = clean_input($_POST['description'] ?? '');
            $latitude = clean_input($_POST['latitude'] ?? '');
            $longitude = clean_input($_POST['longitude'] ?? '');
            $zone_group = clean_input($_POST['zone_group'] ?? '');
            $device_count = (int)($_POST['device_count'] ?? 0);
            $status = clean_input($_POST['status'] ?? 'active');
            
            if (empty($point_number) || empty($latitude) || empty($longitude)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลที่จำเป็น']);
                exit;
            }
            
            if ($id) {
                // UPDATE
                $sql = "UPDATE speaker_locations SET point_number=?, description=?, latitude=?, longitude=?, zone_group=?, device_count=?, status=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssii", $point_number, $description, $latitude, $longitude, $zone_group, $device_count, $status, $id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'แก้ไขจุดติดตั้งสำเร็จ!', 'id' => $id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            } else {
                // INSERT
                $sql = "INSERT INTO speaker_locations (point_number, description, latitude, longitude, zone_group, device_count, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssi", $point_number, $description, $latitude, $longitude, $zone_group, $device_count, $status);
                
                if ($stmt->execute()) {
                    $new_id = $conn->insert_id;
                    echo json_encode(['success' => true, 'message' => 'เพิ่มจุดติดตั้งสำเร็จ!', 'id' => $new_id]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            }
            exit;
        }
        
        // DELETE
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // Delete images first
            $conn->query("DELETE FROM speaker_images WHERE location_id = $id");
            
            // Delete location
            $stmt = $conn->prepare("DELETE FROM speaker_locations WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'ลบจุดติดตั้งสำเร็จ!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
            exit;
        }
        
        // UPLOAD IMAGE
        if ($action === 'upload_image') {
            $location_id = (int)$_POST['location_id'];
            
            // Check image count for this location
            $count_result = $conn->query("SELECT COUNT(*) as total FROM speaker_images WHERE location_id = $location_id");
            $count_row = $count_result->fetch_assoc();
            
            if ($count_row['total'] >= 10) {
                echo json_encode(['success' => false, 'message' => 'จำนวนรูปภาพเกินขีดจำกัด (สูงสุด 10 รูป)']);
                exit;
            }
            
            if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'การอัปโหลดล้มเหลว']);
                exit;
            }
            
            $file = $_FILES['image'];
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            
            if (!in_array($file['type'], $allowed_types)) {
                echo json_encode(['success' => false, 'message' => 'ประเภทไฟล์ไม่ได้รับอนุญาต (JPEG, PNG, GIF, WebP เท่านั้น)']);
                exit;
            }
            
            if ($file['size'] > 5 * 1024 * 1024) { // 5MB max
                echo json_encode(['success' => false, 'message' => 'ไฟล์ใหญ่เกินไป (สูงสุด 5MB)']);
                exit;
            }
            
            // Create upload directory
            $upload_dir = '../public/uploads/speaker_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Generate unique filename
            $filename = uniqid('speaker_' . $location_id . '_') . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Save to database
                $rel_path = 'uploads/speaker_images/' . $filename;
                $stmt = $conn->prepare("INSERT INTO speaker_images (location_id, image_path) VALUES (?, ?)");
                $stmt->bind_param("is", $location_id, $rel_path);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'อัปโหลดรูปภาพสำเร็จ!', 'image_path' => $rel_path]);
                } else {
                    unlink($filepath);
                    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการบันทึก']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่สามารถบันทึกไฟล์']);
            }
            exit;
        }
        
        // DELETE IMAGE
        if ($action === 'delete_image') {
            $image_id = (int)$_POST['image_id'];
            
            // Get image info
            $result = $conn->query("SELECT image_path FROM speaker_images WHERE id = $image_id");
            $row = $result->fetch_assoc();
            
            if ($row) {
                $filepath = '../public/' . $row['image_path'];
                if (file_exists($filepath)) {
                    unlink($filepath);
                }
                
                // Delete from database
                $stmt = $conn->prepare("DELETE FROM speaker_images WHERE id = ?");
                $stmt->bind_param("i", $image_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'ลบรูปภาพสำเร็จ!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'ไม่พบรูปภาพ']);
            }
            exit;
        }
    }
}

// Fetch all locations
$locations = [];
$result = $conn->query("SELECT * FROM speaker_locations ORDER BY point_number ASC");
while ($row = $result->fetch_assoc()) {
    $img_result = $conn->query("SELECT COUNT(*) as total FROM speaker_images WHERE location_id = " . (int)$row['id']);
    $img_row = $img_result->fetch_assoc();
    $row['image_count'] = $img_row['total'];
    $locations[] = $row;
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
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-broadcast-tower text-blue-600"></i> จัดการระบบเสียง CDP
                </h1>
                <p class="mt-2 text-gray-600">จัดการจุดติดตั้งและรูปภาพระบบเสียงไร้สาย</p>
            </div>
            <button onclick="openAddModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>เพิ่มจุดติดตั้ง</span>
            </button>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">จุดที่</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">คำอธิบาย</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">พื้นที่</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">พิกัด</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">อุปกรณ์</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">รูปภาพ</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">สถานะ</th>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">การดำเนินการ</th>
                    </tr>
                </thead>
                <tbody id="locationsTable" class="divide-y">
                    <!-- Loaded via AJAX -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- SweetAlert Modal & Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Load locations on page load
document.addEventListener('DOMContentLoaded', function() {
    loadLocations();
});

function loadLocations() {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderLocations(data.locations);
        }
    })
    .catch(err => console.error('Error:', err));
}

function renderLocations(locations) {
    const table = document.getElementById('locationsTable');
    table.innerHTML = '';
    
    locations.forEach(loc => {
        const row = `
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4 text-sm font-medium text-gray-900">#${loc.point_number}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${loc.description || '-'}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${loc.zone_group || '-'}</td>
                <td class="px-6 py-4 text-sm font-mono text-gray-600">${loc.latitude}, ${loc.longitude}</td>
                <td class="px-6 py-4 text-sm text-gray-600">${loc.device_count || 0} ตัว</td>
                <td class="px-6 py-4">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${loc.image_count >= 10 ? 'bg-red-100 text-red-800' : 'bg-blue-100 text-blue-800'}">
                        <i class="fas fa-image mr-1"></i>
                        ${loc.image_count}/10
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-medium ${loc.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}">
                        ${loc.status === 'active' ? 'ใช้งาน' : 'ปิด'}
                    </span>
                </td>
                <td class="px-6 py-4 text-sm space-x-2">
                    <button onclick="editLocation(${loc.id})" class="text-blue-600 hover:text-blue-800 transition">
                        <i class="fas fa-edit"></i> แก้ไข
                    </button>
                    <button onclick="manageImages(${loc.id})" class="text-purple-600 hover:text-purple-800 transition">
                        <i class="fas fa-images"></i> รูปภาพ
                    </button>
                    <button onclick="deleteLocation(${loc.id})" class="text-red-600 hover:text-red-800 transition">
                        <i class="fas fa-trash"></i> ลบ
                    </button>
                </td>
            </tr>
        `;
        table.innerHTML += row;
    });
}

function openAddModal() {
    showLocationModal();
}

function editLocation(id) {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get&id=${id}`
    })
    .then(r => r.json())
    .then(location => showLocationModal(location))
    .catch(err => console.error('Error:', err));
}

function deleteLocation(id) {
    Swal.fire({
        title: 'ลบจุดติดตั้ง?',
        text: 'คุณแน่ใจหรือว่าต้องการลบ? (จะลบรูปภาพทั้งหมดด้วย)',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('ลบสำเร็จ!', '', 'success');
                    loadLocations();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

function showLocationModal(location = null) {
    const isEdit = !!location;
    const title = isEdit ? 'แก้ไขจุดติดตั้ง' : 'เพิ่มจุดติดตั้งใหม่';
    
    const html = `
        <div style="max-width: 600px; margin: 0 auto;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">จุดที่ *</label>
                    <input type="number" id="modal_point_number" placeholder="1" 
                           value="${isEdit ? location.point_number : ''}"
                           style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">จำนวนอุปกรณ์</label>
                    <input type="number" id="modal_device_count" placeholder="0" 
                           value="${isEdit ? location.device_count : ''}"
                           style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 13px;">
                </div>
            </div>
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">คำอธิบาย</label>
                <textarea id="modal_description" placeholder="รายละเอียดตำแหน่ง" rows="2"
                          style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 13px; font-family: inherit; resize: vertical;">${isEdit ? location.description : ''}</textarea>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Latitude *</label>
                    <input type="text" id="modal_latitude" placeholder="13.5" 
                           value="${isEdit ? location.latitude : ''}"
                           style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 13px;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">Longitude *</label>
                    <input type="text" id="modal_longitude" placeholder="100.5" 
                           value="${isEdit ? location.longitude : ''}"
                           style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 13px;">
                </div>
            </div>
            
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 6px;">กลุ่มพื้นที่</label>
                <input type="text" id="modal_zone_group" placeholder="เช่น ตัวเมือง" 
                       value="${isEdit ? location.zone_group : ''}"
                       style="width: 100%; border: 1px solid #d1d5db; border-radius: 6px; padding: 8px 10px; font-size: 13px;">
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding-top: 8px; border-top: 1px solid #e5e7eb;">
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="radio" name="modal_status" value="active" ${!isEdit || location.status === 'active' ? 'checked' : ''} style="cursor: pointer;">
                    <span style="font-size: 13px; color: #374151;">ใช้งาน</span>
                </label>
                <label style="display: flex; align-items: center; gap: 6px; cursor: pointer;">
                    <input type="radio" name="modal_status" value="inactive" ${isEdit && location.status === 'inactive' ? 'checked' : ''} style="cursor: pointer;">
                    <span style="font-size: 13px; color: #374151;">ปิดใช้งาน</span>
                </label>
            </div>
        </div>
    `;
    
    Swal.fire({
        title: title,
        html: html,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: isEdit ? 'บันทึก' : 'เพิ่ม',
        cancelButtonText: 'ยกเลิก',
        width: '520px',
        padding: '20px',
        didOpen: () => {
            document.getElementById('modal_point_number').focus();
        }
    }).then(result => {
        if (result.isConfirmed) {
            saveLocation(isEdit ? location.id : null);
        }
    });
}

function saveLocation(id = null) {
    const formData = new FormData();
    formData.append('action', 'save');
    if (id) formData.append('id', id);
    formData.append('point_number', document.getElementById('modal_point_number').value);
    formData.append('description', document.getElementById('modal_description').value);
    formData.append('latitude', document.getElementById('modal_latitude').value);
    formData.append('longitude', document.getElementById('modal_longitude').value);
    formData.append('zone_group', document.getElementById('modal_zone_group').value);
    formData.append('device_count', document.getElementById('modal_device_count').value);
    formData.append('status', document.querySelector('input[name="modal_status"]:checked').value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire('สำเร็จ!', data.message, 'success');
            loadLocations();
        } else {
            Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
        }
    })
    .catch(err => Swal.fire('เกิดข้อผิดพลาด', err.message, 'error'));
}

function manageImages(id) {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get&id=${id}`
    })
    .then(r => r.json())
    .then(location => showImageModal(location))
    .catch(err => console.error('Error:', err));
}

function showImageModal(location) {
    const imagesHtml = location.images && location.images.length > 0 
        ? location.images.map(img => `
            <div style="display: inline-block; position: relative; margin: 8px; width: 100px;">
                <img src="../${img.image_path}" style="width: 100px; height: 100px; border-radius: 6px; object-fit: cover;" alt="">
                <button type="button" onclick="deleteImage(${img.id})" 
                        style="position: absolute; top: 2px; right: 2px; background: #dc2626; color: white; border: none; width: 24px; height: 24px; border-radius: 50%; cursor: pointer; font-size: 12px; display: flex; align-items: center; justify-content: center;">
                    ×
                </button>
            </div>
        `).join('')
        : '<p style="color: #999; font-size: 13px;">ไม่มีรูปภาพ</p>';
    
    const html = `
        <div style="max-width: 500px; margin: 0 auto;">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                    อัปโหลดรูปภาพ (${location.images ? location.images.length : 0}/10)
                </label>
                <input type="file" id="image_upload" accept="image/*" 
                       style="width: 100%; padding: 8px; border: 2px dashed #d1d5db; border-radius: 6px; cursor: pointer;">
                <p style="font-size: 11px; color: #999; margin-top: 4px;">รองรับ JPEG, PNG, GIF, WebP (สูงสุด 5MB)</p>
            </div>
            
            <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid #e5e7eb;">
                <p style="font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">รูปภาพของจุดที่ ${location.point_number}</p>
                <div style="display: flex; flex-wrap: wrap;">
                    ${imagesHtml}
                </div>
            </div>
        </div>
    `;
    
    Swal.fire({
        title: 'จัดการรูปภาพ - จุดที่ ' + location.point_number,
        html: html,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'ปิด',
        cancelButtonText: 'ยกเลิก',
        width: '520px',
        padding: '20px',
        didOpen: () => {
            document.getElementById('image_upload').addEventListener('change', function() {
                if (this.files.length > 0) {
                    uploadImage(location.id, this.files[0]);
                }
            });
        }
    });
}

function uploadImage(locationId, file) {
    const formData = new FormData();
    formData.append('action', 'upload_image');
    formData.append('location_id', locationId);
    formData.append('image', file);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire('สำเร็จ!', data.message, 'success');
            // Reload image modal
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=get&id=${locationId}`
            })
            .then(r => r.json())
            .then(location => showImageModal(location));
            loadLocations();
        } else {
            Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
        }
    });
}

function deleteImage(imageId) {
    if (confirm('ลบรูปภาพนี้?')) {
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=delete_image&image_id=${imageId}`
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire('ลบสำเร็จ!', '', 'success');
                loadLocations();
                // Refresh image modal
                const modalContent = document.querySelector('.swal2-html-container');
                if (modalContent) {
                    location.reload();
                }
            }
        });
    }
}
</script>

<?php
include 'admin-layout/footer.php';
?>
