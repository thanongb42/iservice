<?php
/**
 * Tech News API
 * AJAX endpoint for CRUD operations
 */

header('Content-Type: application/json');
require_once '../../config/database.php';

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
        case 'update':
            // Get form data
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $content = $_POST['content'] ?? '';
            $category = trim($_POST['category'] ?? '');
            $category_color = $_POST['category_color'] ?? 'blue';
            $author = trim($_POST['author'] ?? '');
            $tags = trim($_POST['tags'] ?? '');
            $published_date = $_POST['published_date'] ?? date('Y-m-d');
            $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
            $display_order = intval($_POST['display_order'] ?? 0);

            // Validation
            if (empty($title) || empty($description) || empty($category)) {
                throw new Exception('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            }

            // Check pin limit (only if pinning)
            if ($is_pinned) {
                $id = intval($_POST['id'] ?? 0);
                if ($action == 'update') {
                    $pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1 AND id != $id")->fetch_assoc()['count'];
                } else {
                    $pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1")->fetch_assoc()['count'];
                }

                if ($pinned_count >= 4) {
                    throw new Exception('ไม่สามารถปักหมุดได้เกิน 4 ข่าว กรุณายกเลิกการปักหมุดข่าวอื่นก่อน');
                }
            }

            // Handle file upload
            $cover_image = '';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $filename = $_FILES['cover_image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed)) {
                    throw new Exception('รองรับเฉพาะไฟล์ภาพ (JPG, PNG, GIF, WEBP)');
                }

                if ($_FILES['cover_image']['size'] > 5 * 1024 * 1024) {
                    throw new Exception('ขนาดไฟล์ต้องไม่เกิน 5MB');
                }

                $upload_dir = '../../uploads/tech_news/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_filename = 'tech_' . time() . '_' . uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $upload_path)) {
                    $cover_image = 'uploads/tech_news/' . $new_filename;
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการอัพโหลดไฟล์');
                }
            } elseif (!empty($_POST['cover_image_url'])) {
                $cover_image = trim($_POST['cover_image_url']);
            }

            if ($action == 'add') {
                // Insert new record
                $stmt = $conn->prepare("INSERT INTO tech_news (title, description, content, category, category_color, cover_image, author, tags, published_date, is_pinned, display_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssii", $title, $description, $content, $category, $category_color, $cover_image, $author, $tags, $published_date, $is_pinned, $display_order);

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'เพิ่มข่าวสำเร็จ';
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการเพิ่มข่าว: ' . $stmt->error);
                }
            } else {
                // Update existing record
                $id = intval($_POST['id']);

                if (!empty($cover_image)) {
                    $stmt = $conn->prepare("UPDATE tech_news SET title=?, description=?, content=?, category=?, category_color=?, cover_image=?, author=?, tags=?, published_date=?, is_pinned=?, display_order=? WHERE id=?");
                    $stmt->bind_param("ssssssssssii", $title, $description, $content, $category, $category_color, $cover_image, $author, $tags, $published_date, $is_pinned, $display_order, $id);
                } else {
                    $stmt = $conn->prepare("UPDATE tech_news SET title=?, description=?, content=?, category=?, category_color=?, author=?, tags=?, published_date=?, is_pinned=?, display_order=? WHERE id=?");
                    $stmt->bind_param("sssssssssii", $title, $description, $content, $category, $category_color, $author, $tags, $published_date, $is_pinned, $display_order, $id);
                }

                if ($stmt->execute()) {
                    $response['success'] = true;
                    $response['message'] = 'อัพเดทข่าวสำเร็จ';
                } else {
                    throw new Exception('เกิดข้อผิดพลาดในการอัพเดทข่าว: ' . $stmt->error);
                }
            }
            break;

        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("DELETE FROM tech_news WHERE id = ?");
            $stmt->bind_param("i", $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'ลบข่าวสำเร็จ';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการลบข่าว');
            }
            break;

        case 'toggle_active':
            $id = intval($_POST['id'] ?? 0);
            $is_active = intval($_POST['is_active'] ?? 1);

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            $stmt = $conn->prepare("UPDATE tech_news SET is_active = ? WHERE id = ?");
            $stmt->bind_param("ii", $is_active, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $is_active ? 'เปิดใช้งานข่าวแล้ว' : 'ปิดใช้งานข่าวแล้ว';
            } else {
                throw new Exception('เกิดข้อผิดพลาดในการเปลี่ยนสถานะ');
            }
            break;

        case 'toggle_pin':
            $id = intval($_POST['id'] ?? 0);
            $is_pinned = intval($_POST['is_pinned'] ?? 0);

            if ($id <= 0) {
                throw new Exception('ID ไม่ถูกต้อง');
            }

            // Check pin limit if pinning
            if ($is_pinned == 1) {
                $pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1 AND id != $id")->fetch_assoc()['count'];
                if ($pinned_count >= 4) {
                    throw new Exception('ไม่สามารถปักหมุดได้เกิน 4 ข่าว');
                }
            }

            $stmt = $conn->prepare("UPDATE tech_news SET is_pinned = ? WHERE id = ?");
            $stmt->bind_param("ii", $is_pinned, $id);

            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = $is_pinned ? 'ปักหมุดข่าวแล้ว' : 'ยกเลิกการปักหมุดแล้ว';
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
