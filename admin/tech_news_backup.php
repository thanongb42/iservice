<?php
/**
 * Tech News Management (CRUD)
 * หน้าจัดการข่าวเทคโนโลยี
 */

require_once '../config/database.php';
session_start();

$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// File Upload Handler
function handle_news_image_upload($file) {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'เกิดข้อผิดพลาดในการอัปโหลดไฟล์'];
    }

    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_types)) {
        return ['error' => 'ประเภทไฟล์ไม่ถูกต้อง อนุญาตเฉพาะ: ' . implode(', ', $allowed_types)];
    }

    if ($file['size'] > 10 * 1024 * 1024) {
        return ['error' => 'ไฟล์มีขนาดใหญ่เกินไป (สูงสุด 10MB)'];
    }

    $upload_dir = '../uploads/tech_news/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $unique_name = time() . '_' . uniqid() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_name;

    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => str_replace('../', '', $upload_path)];
    }

    return ['error' => 'ไม่สามารถบันทึกไฟล์ได้'];
}

// CREATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $content = $_POST['content']; // Allow HTML
    $category = clean_input($_POST['category']);
    $category_color = clean_input($_POST['category_color']);
    $cover_image = clean_input($_POST['cover_image']);
    $author = clean_input($_POST['author']);
    $tags = clean_input($_POST['tags']);
    $published_date = $_POST['published_date'];
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];

    // Handle image upload
    if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = handle_news_image_upload($_FILES['cover_image_file']);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            $cover_image = $result['success'];
        }
    }

    // Check pinned limit (max 4)
    if ($is_pinned) {
        $pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1")->fetch_assoc()['count'];
        if ($pinned_count >= 4) {
            $error = "ไม่สามารถปักหมุดได้เกิน 4 ข่าว กรุณายกเลิกการปักหมุดข่าวอื่นก่อน";
        }
    }

    if (empty($error)) {
        $sql = "INSERT INTO tech_news (title, description, content, category, category_color, cover_image, author, tags, published_date, is_pinned, is_active, display_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssssii", $title, $description, $content, $category, $category_color, $cover_image, $author, $tags, $published_date, $is_pinned, $is_active, $display_order);

        if ($stmt->execute()) {
            $message = "เพิ่มข่าวสำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $id = (int)$_POST['id'];
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $content = $_POST['content'];
    $category = clean_input($_POST['category']);
    $category_color = clean_input($_POST['category_color']);
    $cover_image = clean_input($_POST['cover_image']);
    $author = clean_input($_POST['author']);
    $tags = clean_input($_POST['tags']);
    $published_date = $_POST['published_date'];
    $is_pinned = isset($_POST['is_pinned']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $display_order = (int)$_POST['display_order'];

    // Handle image upload
    if (isset($_FILES['cover_image_file']) && $_FILES['cover_image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
        $result = handle_news_image_upload($_FILES['cover_image_file']);
        if (isset($result['error'])) {
            $error = $result['error'];
        } else {
            $cover_image = $result['success'];
        }
    }

    // Check pinned limit
    if ($is_pinned) {
        $pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1 AND id != $id")->fetch_assoc()['count'];
        if ($pinned_count >= 4) {
            $error = "ไม่สามารถปักหมุดได้เกิน 4 ข่าว";
        }
    }

    if (empty($error)) {
        $sql = "UPDATE tech_news SET title=?, description=?, content=?, category=?, category_color=?, cover_image=?, author=?, tags=?, published_date=?, is_pinned=?, is_active=?, display_order=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssssii", $title, $description, $content, $category, $category_color, $cover_image, $author, $tags, $published_date, $is_pinned, $is_active, $display_order, $id);

        if ($stmt->execute()) {
            $message = "แก้ไขข่าวสำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}

// DELETE
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($conn->query("DELETE FROM tech_news WHERE id = $id")) {
        $message = "ลบข่าวสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// TOGGLE ACTIVE
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE tech_news SET is_active = NOT is_active WHERE id = $id");
    $message = "เปลี่ยนสถานะสำเร็จ!";
}

// TOGGLE PIN
if ($action === 'toggle_pin' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Check current pin status
    $current = $conn->query("SELECT is_pinned FROM tech_news WHERE id = $id")->fetch_assoc();

    if ($current['is_pinned'] == 0) {
        // Check pinned limit
        $pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1")->fetch_assoc()['count'];
        if ($pinned_count >= 4) {
            $error = "ไม่สามารถปักหมุดได้เกิน 4 ข่าว กรุณายกเลิกการปักหมุดข่าวอื่นก่อน";
        } else {
            $conn->query("UPDATE tech_news SET is_pinned = 1 WHERE id = $id");
            $message = "ปักหมุดข่าวสำเร็จ!";
        }
    } else {
        $conn->query("UPDATE tech_news SET is_pinned = 0 WHERE id = $id");
        $message = "ยกเลิกปักหมุดสำเร็จ!";
    }
}

// Fetch all news
$news_list = [];
$result = $conn->query("SELECT * FROM tech_news ORDER BY is_pinned DESC, display_order ASC, created_at DESC");
while ($row = $result->fetch_assoc()) {
    $news_list[] = $row;
}

// Get edit data
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM tech_news WHERE id = $id");
    $edit_data = $result->fetch_assoc();
}

// Category colors
$category_colors = [
    'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
    'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
    'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
    'teal' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-700'],
    'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข่าวเทคโนโลยี - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Quill Editor (Free, No API Key Required) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

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
                        <i class="fas fa-newspaper text-teal-600"></i> จัดการข่าวเทคโนโลยี
                    </h1>
                    <p class="text-gray-600 mt-2">เพิ่ม แก้ไข ลบ และปักหมุดข่าว (สูงสุด 4 ข่าว)</p>
                </div>
                <div class="flex space-x-3">
                    <a href="learning_resources.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-graduation-cap mr-2"></i>จัดการศูนย์เรียนรู้
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
            <!-- News List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-teal-600"></i> รายการข่าวทั้งหมด (<?= count($news_list) ?>)
                        <span class="text-sm text-gray-500 ml-2">
                            (ปักหมุด: <?= count(array_filter($news_list, fn($n) => $n['is_pinned'])) ?>/4)
                        </span>
                    </h2>

                    <div class="space-y-4">
                        <?php foreach ($news_list as $news):
                            $color = $category_colors[$news['category_color']] ?? $category_colors['blue'];
                        ?>
                            <div class="border rounded-xl overflow-hidden hover:shadow-lg transition <?= !$news['is_active'] ? 'opacity-50' : '' ?>">
                                <div class="flex gap-4 p-4">
                                    <!-- Image -->
                                    <div class="w-32 h-32 flex-shrink-0 rounded-lg overflow-hidden bg-gray-200">
                                        <?php if ($news['cover_image']):
                                            $img_src = $news['cover_image'];
                                            if (!preg_match('/^https?:\/\//', $img_src) && !str_starts_with($img_src, '../')) {
                                                $img_src = '../' . $img_src;
                                            }
                                        ?>
                                            <img src="<?= htmlspecialchars($img_src) ?>"
                                                 alt="<?= htmlspecialchars($news['title']) ?>"
                                                 class="w-full h-full object-cover"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'128\' height=\'128\'%3E%3Crect fill=\'%23E5E7EB\' width=\'128\' height=\'128\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'12\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
                                                <i class="fas fa-image text-gray-500 text-3xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="<?= $color['bg'] ?> <?= $color['text'] ?> text-xs px-2 py-1 rounded">
                                                    <?= htmlspecialchars($news['category']) ?>
                                                </span>
                                                <?php if ($news['is_pinned']): ?>
                                                    <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded flex items-center">
                                                        <i class="fas fa-thumbtack mr-1"></i>ปักหมุด
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-xs text-gray-500">
                                                    ลำดับ: <?= $news['display_order'] ?>
                                                </span>
                                            </div>
                                        </div>

                                        <h3 class="font-bold text-gray-900 line-clamp-2 mb-1">
                                            <?= htmlspecialchars($news['title']) ?>
                                        </h3>

                                        <p class="text-sm text-gray-600 line-clamp-2 mb-2">
                                            <?= htmlspecialchars($news['description']) ?>
                                        </p>

                                        <div class="flex items-center gap-4 text-xs text-gray-500">
                                            <span><i class="far fa-eye mr-1"></i><?= number_format($news['view_count']) ?></span>
                                            <span><i class="far fa-calendar mr-1"></i><?= date('d/m/Y', strtotime($news['published_date'])) ?></span>
                                            <?php if ($news['author']): ?>
                                                <span><i class="far fa-user mr-1"></i><?= htmlspecialchars($news['author']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 mt-3 pt-3 border-t">
                                            <a href="?action=toggle&id=<?= $news['id'] ?>"
                                               class="text-sm"
                                               title="เปิด/ปิด">
                                                <?php if ($news['is_active']): ?>
                                                    <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                                <?php endif; ?>
                                            </a>

                                            <a href="?action=toggle_pin&id=<?= $news['id'] ?>"
                                               class="text-sm ml-2"
                                               title="ปักหมุด/ยกเลิก">
                                                <?php if ($news['is_pinned']): ?>
                                                    <i class="fas fa-thumbtack text-yellow-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-thumbtack text-gray-400 text-xl"></i>
                                                <?php endif; ?>
                                            </a>

                                            <div class="flex-1"></div>

                                            <a href="?action=edit&id=<?= $news['id'] ?>"
                                               class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded transition">
                                                <i class="fas fa-edit mr-1"></i>แก้ไข
                                            </a>
                                            <a href="?action=delete&id=<?= $news['id'] ?>"
                                               class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded transition"
                                               onclick="return confirm('ต้องการลบข่าวนี้หรือไม่?')">
                                                <i class="fas fa-trash mr-1"></i>ลบ
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($news_list)): ?>
                            <div class="text-center py-12 text-gray-500">
                                <i class="fas fa-inbox text-6xl mb-4"></i>
                                <p>ยังไม่มีข่าวในระบบ</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-<?= $edit_data ? 'edit' : 'plus' ?> text-teal-600"></i>
                        <?= $edit_data ? 'แก้ไขข่าว' : 'เพิ่มข่าวใหม่' ?>
                    </h2>

                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">หัวข้อข่าว *</label>
                            <input type="text" name="title" required
                                   value="<?= $edit_data['title'] ?? '' ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบายสั้น *</label>
                            <textarea name="description" required rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"><?= $edit_data['description'] ?? '' ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">เนื้อหาข่าว</label>
                            <div id="quill-editor" class="bg-white border border-gray-300 rounded-lg" style="min-height: 200px;"></div>
                            <input type="hidden" name="content" id="content">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">หมวดหมู่ *</label>
                                <input type="text" name="category" required
                                       value="<?= $edit_data['category'] ?? '' ?>"
                                       placeholder="AI, Cloud, Security"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">สี</label>
                                <select name="category_color" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                                    <?php foreach ($category_colors as $color_key => $color_val): ?>
                                        <option value="<?= $color_key ?>" <?= ($edit_data && $edit_data['category_color'] == $color_key) ? 'selected' : '' ?>>
                                            <?= ucfirst($color_key) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-image"></i> อัปโหลดภาพหน้าปก
                            </label>
                            <input type="file" name="cover_image_file" accept="image/*"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF (สูงสุด 10MB)</p>

                            <div class="mt-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">หรือใส่ URL รูปภาพ</label>
                                <input type="text" name="cover_image"
                                       value="<?= $edit_data['cover_image'] ?? '' ?>"
                                       placeholder="https://..."
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                            </div>

                            <?php if ($edit_data && $edit_data['cover_image']):
                                $edit_img_src = $edit_data['cover_image'];
                                if (!preg_match('/^https?:\/\//', $edit_img_src) && !str_starts_with($edit_img_src, '../')) {
                                    $edit_img_src = '../' . $edit_img_src;
                                }
                            ?>
                                <div class="mt-2">
                                    <img src="<?= htmlspecialchars($edit_img_src) ?>"
                                         alt="Preview"
                                         class="w-full h-24 object-cover rounded border"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'100\'%3E%3Crect fill=\'%23E5E7EB\' width=\'400\' height=\'100\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EImage Not Found%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                                </div>
                            <?php endif; ?>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ผู้เขียน</label>
                            <input type="text" name="author"
                                   value="<?= $edit_data['author'] ?? 'ทีมข่าวเทคโนโลยี' ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tags</label>
                            <input type="text" name="tags"
                                   value="<?= $edit_data['tags'] ?? '' ?>"
                                   placeholder="AI,Cloud,Tech (คั่นด้วยคอมม่า)"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">วันที่เผยแพร่</label>
                                <input type="date" name="published_date"
                                       value="<?= $edit_data['published_date'] ?? date('Y-m-d') ?>"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับ *</label>
                                <input type="number" name="display_order" required
                                       value="<?= $edit_data['display_order'] ?? 0 ?>"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_pinned" id="is_pinned"
                                       <?= ($edit_data && $edit_data['is_pinned']) ? 'checked' : '' ?>
                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                                <label for="is_pinned" class="ml-2 text-sm font-medium text-gray-700">
                                    <i class="fas fa-thumbtack text-yellow-500"></i> ปักหมุด (สูงสุด 4 ข่าว)
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
                            <button type="submit" name="<?= $edit_data ? 'edit_news' : 'add_news' ?>"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-<?= $edit_data ? 'save' : 'plus' ?> mr-2"></i>
                                <?= $edit_data ? 'บันทึกการแก้ไข' : 'เพิ่มข่าว' ?>
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
        // Initialize Quill Editor (Free, No API Key Required)
        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            placeholder: 'เขียนเนื้อหาข่าวที่นี่...'
        });

        // Load existing content if editing
        <?php if (!empty($edit_data['content'])): ?>
        quill.root.innerHTML = <?= json_encode($edit_data['content']) ?>;
        <?php endif; ?>

        // Update hidden input before form submit
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('content').value = quill.root.innerHTML;
        });
    </script>
</body>
</html>
<?php $conn->close(); ?>
