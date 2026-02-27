<?php
/**
 * Learning Resources API
 * AJAX endpoint for CRUD operations with file upload support
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

// File Upload Handler
function handle_file_upload($file, $upload_dir, $allowed_types = []) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'];
    }

    // Check file type
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Block dangerous file types
    $blocked_extensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'pht', 'exe', 'bat', 'cmd', 'sh', 'cgi'];
    if (in_array($file_extension, $blocked_extensions)) {
        return ['error' => 'ไม่อนุญาตให้อัปโหลดไฟล์ประเภทนี้ด้วยเหตุผลด้านความปลอดภัย'];
    }

    if (!empty($allowed_types) && !in_array($file_extension, $allowed_types)) {
        return ['error' => 'ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะ: ' . implode(', ', $allowed_types)];
    }

    // Check file size (max 50MB)
    if ($file['size'] > 50 * 1024 * 1024) {
        return ['error' => 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 50MB)'];
    }

    // Create upload directory if not exists
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Generate unique filename
    $unique_name = time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_name;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => $upload_path];
    } else {
        return ['error' => 'ไม่สามารถบันทึกไฟล์ได้'];
    }
}

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
        case 'update':
            // Get form data
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $resource_type = trim($_POST['resource_type'] ?? '');
            $resource_url = trim($_POST['resource_url'] ?? '');
            $cover_image = trim($_POST['cover_image'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $author = trim($_POST['author'] ?? '');
            $duration = trim($_POST['duration'] ?? '');
            $file_size = trim($_POST['file_size'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $display_order = intval($_POST['display_order'] ?? 0);

            // Validation
            if (empty($title) || empty($resource_type)) {
                throw new Exception('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            }

            // Handle resource file upload
            if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_resource_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'mp4', 'mp3', 'zip', 'rar'];
                $result = handle_file_upload($_FILES['resource_file'], '../../uploads/resources/', $allowed_resource_types);

                if (isset($result['error'])) {
                    throw new Exception($result['error']);
                } else {
                    // Remove ../../ prefix for storage in database
                    $resource_url = str_replace('../../', '', $result['success']);
                }
            }

            // Handle cover image upload
            if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $result = handle_file_upload($_FILES['cover_image_file'], '../../uploads/covers/', $allowed_image_types);

                if (isset($result['error'])) {
                    throw new Exception($result['error']);
                } else {
                    // Remove ../../ prefix for storage in database
                    $cover_image = str_replace('../../', '', $result['success']);
                }
            }

            if ($action == 'add') {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO learning_resources (title, description, resource_type, resource_url, cover_image, category, author, duration, file_size, tags, is_featured, is_active, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssssii", $title, $description, $resource_type, $resource_url, $cover_image, $category, $author, $duration, $file_size, $tags, $is_featured, $is_active, $display_order);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'เพิ่มทรัพยากรสำเร็จ';
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการเพิ่มทรัพยากร: ' . $stmt->error);
                }
            } else {
                // Update existing record
                $id = intval($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new Exception('ID ไม่ถูกต้อง');
                }

                $stmt = $conn->prepare("UPDATE learning_resources SET title=?, description=?, resource_type=?, resource_url=?, cover_image=?, category=?, author=?, duration=?, file_size=?, tags=?, is_featured=?, is_active=?, display_order=? WHERE id=?");
                $stmt->bind_param("ssssssssssiiii", $title, $description, $resource_type, $resource_url, $cover_image, $category, $author, $duration, $file_size, $tags, $is_featured, $is_active, $display_order, $id);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'อัพเดททรัพยากรสำเร็จ';
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการอัพเดททรัพยากร: ' . $stmt->error);
                }
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("DELETE FROM learning_resources WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'ลบทรัพยากรสำเร็จ';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการลบทรัพยากร');
            }
            break;

        case 'toggle_active':
            $id = intval($_POST['id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 1);

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("UPDATE learning_resources SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $is_active, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $is_active ? 'เปิดใช้งานทรัพยากรแล้ว' : 'ปิดใช้งานทรัพยากรแล้ว';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
            }
            break;

        case 'toggle_featured':
            $id = intval($_POST['id'] ?? 0);
            $is_featured = intval($_POST['is_featured'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("UPDATE learning_resources SET is_featured = ? WHERE id = ?");
            $stmt->bind_param("ii", $is_featured, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $is_featured ? 'ทำเครื่องหมายแนะนำแล้ว' : 'ยกเลิกเครื่องหมายแนะนำแล้ว';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
            }
            break;

        default:
            throw new Exception('Action ไม่ถูกต้อง');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
