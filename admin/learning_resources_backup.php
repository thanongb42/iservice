<?php
/**
 * Learning Resources Management Page
 * หน้าจัดการศูนย์รวมการเรียนรู้ (CRUD)
 */

// Include database config
require_once '../config/database.php';

// Start session
session_start();

// Handle CRUD operations
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

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
        mkdir($upload_dir, 0777, true);
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

// CREATE - Add new resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $resource_type = clean_input($_POST['resource_type']);
    $resource_url = clean_input($_POST['resource_url']);
    $cover_image = clean_input($_POST['cover_image']);
    $category = clean_input($_POST['category']);
    $author = clean_input($_POST['author']);
    $duration = clean_input($_POST['duration']);
    $file_size = clean_input($_POST['file_size']);
    $tags = clean_input($_POST['tags']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];

    // Handle resource file upload
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_resource_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'mp4', 'mp3', 'zip', 'rar'];
        $result = handle_file_upload($_FILES['resource_file'], '../uploads/resources/', $allowed_resource_types);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Remove ../ prefix for storage in database
            $resource_url = str_replace('../', '', $result['success']);
        }
    }

    // Handle cover image upload
    if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $result = handle_file_upload($_FILES['cover_image_file'], '../uploads/covers/', $allowed_image_types);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Remove ../ prefix for storage in database
            $cover_image = str_replace('../', '', $result['success']);
        }
    }

    if (empty($error)) {
        $sql = "INSERT INTO learning_resources (title, description, resource_type, resource_url, cover_image, category, author, duration, file_size, tags, is_featured, is_active, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssii", $title, $description, $resource_type, $resource_url, $cover_image, $category, $author, $duration, $file_size, $tags, $is_featured, $is_active, $display_order);

        if ($stmt->execute()) {
            $message = "เพิ่มทรัพยากรสำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// UPDATE - Edit resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_resource'])) {
    $id = (int)$_POST['id'];
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $resource_type = clean_input($_POST['resource_type']);
    $resource_url = clean_input($_POST['resource_url']);
    $cover_image = clean_input($_POST['cover_image']);
    $category = clean_input($_POST['category']);
    $author = clean_input($_POST['author']);
    $duration = clean_input($_POST['duration']);
    $file_size = clean_input($_POST['file_size']);
    $tags = clean_input($_POST['tags']);
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];

    // Handle resource file upload (will override existing if uploaded)
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_resource_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'mp4', 'mp3', 'zip', 'rar'];
        $result = handle_file_upload($_FILES['resource_file'], '../uploads/resources/', $allowed_resource_types);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Remove ../ prefix for storage in database
            $resource_url = str_replace('../', '', $result['success']);
        }
    }

    // Handle cover image upload (will override existing if uploaded)
    if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $allowed_image_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $result = handle_file_upload($_FILES['cover_image_file'], '../uploads/covers/', $allowed_image_types);

        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            // Remove ../ prefix for storage in database
            $cover_image = str_replace('../', '', $result['success']);
        }
    }

    if (empty($error)) {
        $sql = "UPDATE learning_resources SET title=?, description=?, resource_type=?, resource_url=?, cover_image=?,
                category=?, author=?, duration=?, file_size=?, tags=?, is_featured=?, is_active=?, display_order=? WHERE id=?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssiiii", $title, $description, $resource_type, $resource_url, $cover_image, $category, $author, $duration, $file_size, $tags, $is_featured, $is_active, $display_order, $id);

        if ($stmt->execute()) {
            $message = "แก้ไขทรัพยากรสำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// DELETE - Remove resource
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($conn->query("DELETE FROM learning_resources WHERE id = $id")) {
        $message = "ลบทรัพยากรสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// TOGGLE ACTIVE
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE learning_resources SET is_active = NOT is_active WHERE id = $id");
    $message = "เปลี่ยนสถานะสำเร็จ!";
}

// TOGGLE FEATURED
if ($action === 'toggle_featured' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE learning_resources SET is_featured = NOT is_featured WHERE id = $id");
    $message = "เปลี่ยนสถานะแนะนำสำเร็จ!";
}

// Fetch all resources
$resources = [];
$result = $conn->query("SELECT * FROM learning_resources ORDER BY display_order ASC, created_at DESC");
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

// Get edit data
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM learning_resources WHERE id = $id");
    $edit_data = $result->fetch_assoc();
}

// Resource type icons
$type_icons = [
    'pdf' => 'fa-file-pdf text-red-500',
    'video' => 'fa-video text-blue-500',
    'podcast' => 'fa-podcast text-purple-500',
    'blog' => 'fa-blog text-green-500',
    'sourcecode' => 'fa-code text-gray-700',
    'youtube' => 'fa-youtube text-red-600',
    'flipbook' => 'fa-book-open text-teal-500'
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการศูนย์รวมการเรียนรู้ - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-graduation-cap text-teal-600"></i> จัดการศูนย์รวมการเรียนรู้
                    </h1>
                    <p class="text-gray-600 mt-2">เพิ่ม แก้ไข ลบ คู่มือ หลักสูตร Blog และทรัพยากรการเรียนรู้ต่างๆ</p>
                </div>
                <div class="flex space-x-3">
                    <a href="my_service.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-briefcase mr-2"></i>จัดการบริการ
                    </a>
                    <a href="../index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left mr-2"></i>กลับหน้าแรก
                    </a>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
                <i class="fas fa-check-circle mr-2"></i><?= $message ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <i class="fas fa-exclamation-circle mr-2"></i><?= $error ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Resource Cards -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-teal-600"></i> รายการทรัพยากรทั้งหมด (<?= count($resources) ?>)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($resources as $resource):
                            $icon_class = $type_icons[$resource['resource_type']] ?? 'fa-file text-gray-500';
                        ?>
                            <div class="border rounded-xl overflow-hidden hover:shadow-lg transition <?= !$resource['is_active'] ? 'opacity-50' : '' ?>">
                                <!-- Cover Image -->
                                <div class="relative h-40 bg-gray-200 overflow-hidden">
                                    <?php if ($resource['cover_image']):
                                        // Fix path for admin page - add ../ if not present and not a URL
                                        $cover_img = $resource['cover_image'];
                                        if (!preg_match('/^https?:\/\//', $cover_img) && !str_starts_with($cover_img, '../')) {
                                            $cover_img = '../' . $cover_img;
                                        }
                                    ?>
                                        <img src="<?= htmlspecialchars($cover_img) ?>"
                                             alt="<?= htmlspecialchars($resource['title']) ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gray-300\'><i class=\'fas fa-image-slash text-gray-500 text-4xl\'></i></div>'">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-teal-400 to-blue-500">
                                            <i class="fas fa-image text-white text-4xl opacity-50"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Type Badge -->
                                    <div class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold flex items-center space-x-1">
                                        <i class="fas <?= $icon_class ?>"></i>
                                        <span class="uppercase"><?= $resource['resource_type'] ?></span>
                                    </div>

                                    <!-- Featured Star -->
                                    <?php if ($resource['is_featured']): ?>
                                        <div class="absolute top-2 right-2 bg-yellow-400 p-2 rounded-full">
                                            <i class="fas fa-star text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Content -->
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-800 mb-2 line-clamp-2"><?= htmlspecialchars($resource['title']) ?></h3>

                                    <?php if ($resource['category']): ?>
                                        <span class="inline-block bg-teal-100 text-teal-700 text-xs px-2 py-1 rounded mb-2">
                                            <?= htmlspecialchars($resource['category']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($resource['description']) ?></p>

                                    <!-- Meta Info -->
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                        <?php if ($resource['author']): ?>
                                            <span><i class="fas fa-user mr-1"></i><?= htmlspecialchars($resource['author']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-eye mr-1"></i><?= $resource['view_count'] ?></span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center justify-between pt-3 border-t">
                                        <div class="flex space-x-2">
                                            <a href="?action=toggle&id=<?= $resource['id'] ?>" class="text-sm" title="เปิด/ปิด">
                                                <?php if ($resource['is_active']): ?>
                                                    <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                                <?php endif; ?>
                                            </a>
                                            <a href="?action=toggle_featured&id=<?= $resource['id'] ?>" class="text-sm" title="แนะนำ">
                                                <?php if ($resource['is_featured']): ?>
                                                    <i class="fas fa-star text-yellow-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-gray-400 text-xl"></i>
                                                <?php endif; ?>
                                            </a>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="?action=edit&id=<?= $resource['id'] ?>" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $resource['id'] ?>" class="text-red-600 hover:text-red-800"
                                               onclick="return confirm('ต้องการลบทรัพยากรนี้หรือไม่?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($resources)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-6xl mb-4"></i>
                            <p>ยังไม่มีทรัพยากรในระบบ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-<?= $edit_data ? 'edit' : 'plus' ?> text-teal-600"></i>
                        <?= $edit_data ? 'แก้ไขทรัพยากร' : 'เพิ่มทรัพยากรใหม่' ?>
                    </h2>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">หัวข้อ/ชื่อเรื่อง *</label>
                            <input type="text" name="title" required
                                   value="<?= $edit_data['title'] ?? '' ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                            <textarea name="description" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"><?= $edit_data['description'] ?? '' ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภท *</label>
                            <select name="resource_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                                <option value="pdf" <?= ($edit_data && $edit_data['resource_type'] == 'pdf') ? 'selected' : '' ?>>PDF Document</option>
                                <option value="video" <?= ($edit_data && $edit_data['resource_type'] == 'video') ? 'selected' : '' ?>>Video</option>
                                <option value="podcast" <?= ($edit_data && $edit_data['resource_type'] == 'podcast') ? 'selected' : '' ?>>Podcast</option>
                                <option value="blog" <?= ($edit_data && $edit_data['resource_type'] == 'blog') ? 'selected' : '' ?>>Blog</option>
                                <option value="sourcecode" <?= ($edit_data && $edit_data['resource_type'] == 'sourcecode') ? 'selected' : '' ?>>Source Code</option>
                                <option value="youtube" <?= ($edit_data && $edit_data['resource_type'] == 'youtube') ? 'selected' : '' ?>>YouTube</option>
                                <option value="flipbook" <?= ($edit_data && $edit_data['resource_type'] == 'flipbook') ? 'selected' : '' ?>>Flipbook</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-file-upload"></i> อัปโหลดไฟล์ทรัพยากร
                            </label>
                            <input type="file" name="resource_file" id="resource_file"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.mp4,.mp3,.zip,.rar"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> ไฟล์ที่รองรับ: PDF, DOC, XLS, PPT, MP4, MP3, ZIP (สูงสุด 50MB)
                            </p>

                            <!-- Optional URL Input -->
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">หรือใส่ URL/Link ภายนอก</label>
                                <input type="text" name="resource_url"
                                       value="<?= $edit_data['resource_url'] ?? '' ?>"
                                       placeholder="https://... (ใช้แทนการอัปโหลดไฟล์)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                            </div>

                            <?php if ($edit_data && $edit_data['resource_url']): ?>
                                <div class="mt-2 p-2 bg-blue-50 rounded text-xs text-blue-700">
                                    <i class="fas fa-link"></i> ไฟล์ปัจจุบัน:
                                    <a href="<?= htmlspecialchars($edit_data['resource_url']) ?>" target="_blank" class="underline">
                                        <?= htmlspecialchars(basename($edit_data['resource_url'])) ?>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-image"></i> อัปโหลดภาพหน้าปก
                            </label>
                            <input type="file" name="cover_image_file" id="cover_image_file"
                                   accept="image/*"
                                   onchange="previewCoverImage(event)"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> รูปภาพ: JPG, PNG, GIF, WEBP (สูงสุด 50MB)
                            </p>

                            <!-- Image Preview -->
                            <div id="coverImagePreview" class="mt-3 hidden">
                                <img id="previewImg" src="" alt="Preview" class="w-full h-32 object-cover rounded-lg border">
                            </div>

                            <!-- Optional URL Input -->
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">หรือใส่ URL รูปภาพภายนอก</label>
                                <input type="text" name="cover_image"
                                       value="<?= $edit_data['cover_image'] ?? '' ?>"
                                       placeholder="https://... (ใช้แทนการอัปโหลดไฟล์)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                            </div>

                            <?php if ($edit_data && $edit_data['cover_image']):
                                // Fix path for admin page
                                $edit_cover_img = $edit_data['cover_image'];
                                if (!preg_match('/^https?:\/\//', $edit_cover_img) && !str_starts_with($edit_cover_img, '../')) {
                                    $edit_cover_img = '../' . $edit_cover_img;
                                }
                            ?>
                                <div class="mt-2 p-2 bg-blue-50 rounded">
                                    <p class="text-xs text-blue-700 mb-2">
                                        <i class="fas fa-image"></i> รูปปัจจุบัน:
                                    </p>
                                    <img src="<?= htmlspecialchars($edit_cover_img) ?>"
                                         alt="Current cover"
                                         class="w-full h-24 object-cover rounded border"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'100\'%3E%3Crect fill=\'%23EEEEEE\' width=\'400\' height=\'100\'/%3E%3Ctext fill=\'%23999999\' font-family=\'Arial,sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EImage Not Found%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">หมวดหมู่</label>
                            <input type="text" name="category"
                                   value="<?= $edit_data['category'] ?? '' ?>"
                                   placeholder="คู่มือ, หลักสูตร, บทความ"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ผู้เขียน/ผู้สร้าง</label>
                            <input type="text" name="author"
                                   value="<?= $edit_data['author'] ?? '' ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ระยะเวลา</label>
                                <input type="text" name="duration"
                                       value="<?= $edit_data['duration'] ?? '' ?>"
                                       placeholder="15:30"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ขนาดไฟล์</label>
                                <input type="text" name="file_size"
                                       value="<?= $edit_data['file_size'] ?? '' ?>"
                                       placeholder="2.5 MB"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tags (คั่นด้วยคอมม่า)</label>
                            <input type="text" name="tags"
                                   value="<?= $edit_data['tags'] ?? '' ?>"
                                   placeholder="tutorial,video,การใช้งาน"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับการแสดงผล *</label>
                            <input type="number" name="display_order" required
                                   value="<?= $edit_data['display_order'] ?? 0 ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_featured" id="is_featured"
                                       <?= ($edit_data && $edit_data['is_featured']) ? 'checked' : '' ?>
                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                                <label for="is_featured" class="ml-2 text-sm font-medium text-gray-700">
                                    <i class="fas fa-star text-yellow-500"></i> แนะนำ
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active"
                                       <?= (!$edit_data || $edit_data['is_active']) ? 'checked' : '' ?>
                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                            </div>
                        </div>

                        <div class="flex space-x-3 pt-4">
                            <button type="submit" name="<?= $edit_data ? 'edit_resource' : 'add_resource' ?>"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-<?= $edit_data ? 'save' : 'plus' ?> mr-2"></i>
                                <?= $edit_data ? 'บันทึกการแก้ไข' : 'เพิ่มทรัพยากร' ?>
                            </button>

                            <?php if ($edit_data): ?>
                                <a href="?" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold text-center transition">
                                    <i class="fas fa-times mr-2"></i>ยกเลิก
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        function previewCoverImage(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('coverImagePreview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }
    </script>
</body>
</html>
