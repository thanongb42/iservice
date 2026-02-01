<?php
/**
 * Nav Menu Management Page
 * หน้าจัดการเมนูนำทาง (CRUD)
 */

// Include database config
require_once '../config/database.php';

// Start session
session_start();

// Handle CRUD operations
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// CREATE - Add new menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_menu'])) {
    $parent_id = $_POST['parent_id'] ? (int)$_POST['parent_id'] : NULL;
    $menu_name = clean_input($_POST['menu_name']);
    $menu_name_en = clean_input($_POST['menu_name_en']);
    $menu_url = clean_input($_POST['menu_url']);
    $menu_icon = clean_input($_POST['menu_icon']);
    $menu_order = (int)$_POST['menu_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $target = clean_input($_POST['target']);
    $description = clean_input($_POST['description']);

    $sql = "INSERT INTO nav_menu (parent_id, menu_name, menu_name_en, menu_url, menu_icon, menu_order, is_active, target, description)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssiiis", $parent_id, $menu_name, $menu_name_en, $menu_url, $menu_icon, $menu_order, $is_active, $target, $description);

    if ($stmt->execute()) {
        $message = "เพิ่มเมนูสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// UPDATE - Edit menu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_menu'])) {
    $id = (int)$_POST['id'];
    $parent_id = $_POST['parent_id'] ? (int)$_POST['parent_id'] : NULL;
    $menu_name = clean_input($_POST['menu_name']);
    $menu_name_en = clean_input($_POST['menu_name_en']);
    $menu_url = clean_input($_POST['menu_url']);
    $menu_icon = clean_input($_POST['menu_icon']);
    $menu_order = (int)$_POST['menu_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $target = clean_input($_POST['target']);
    $description = clean_input($_POST['description']);

    $sql = "UPDATE nav_menu SET parent_id=?, menu_name=?, menu_name_en=?, menu_url=?, menu_icon=?,
            menu_order=?, is_active=?, target=?, description=? WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issssiiisi", $parent_id, $menu_name, $menu_name_en, $menu_url, $menu_icon, $menu_order, $is_active, $target, $description, $id);

    if ($stmt->execute()) {
        $message = "แก้ไขเมนูสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// DELETE - Remove menu
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Delete children first
    $conn->query("DELETE FROM nav_menu WHERE parent_id = $id");

    // Delete parent
    if ($conn->query("DELETE FROM nav_menu WHERE id = $id")) {
        $message = "ลบเมนูสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// TOGGLE ACTIVE - Toggle menu status
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE nav_menu SET is_active = NOT is_active WHERE id = $id");
    $message = "เปลี่ยนสถานะสำเร็จ!";
}

// Fetch all menus
$menus = [];
$result = $conn->query("SELECT * FROM nav_menu WHERE parent_id IS NULL ORDER BY menu_order ASC");
while ($row = $result->fetch_assoc()) {
    $menu_id = $row['id'];
    $row['children'] = [];

    // Fetch children
    $child_result = $conn->query("SELECT * FROM nav_menu WHERE parent_id = $menu_id ORDER BY menu_order ASC");
    while ($child = $child_result->fetch_assoc()) {
        $row['children'][] = $child;
    }

    $menus[] = $row;
}

// Get parent menus for dropdown
$parent_menus = $conn->query("SELECT id, menu_name FROM nav_menu WHERE parent_id IS NULL ORDER BY menu_name ASC");

// Get edit data
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM nav_menu WHERE id = $id");
    $edit_data = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเมนูนำทาง - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-bars text-teal-600"></i> จัดการเมนูนำทาง
                    </h1>
                    <p class="text-gray-600 mt-2">เพิ่ม แก้ไข ลบ และจัดการลำดับเมนู</p>
                </div>
                <a href="../index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-arrow-left mr-2"></i>กลับหน้าแรก
                </a>
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
            <!-- Left: Menu List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-teal-600"></i> รายการเมนูทั้งหมด
                    </h2>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ลำดับ</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">ชื่อเมนู</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">URL</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">สถานะ</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">จัดการ</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <?php foreach ($menus as $menu): ?>
                                    <!-- Parent Menu -->
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= $menu['menu_order'] ?></td>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-gray-800"><?= $menu['menu_name'] ?></div>
                                            <?php if ($menu['menu_name_en']): ?>
                                                <div class="text-xs text-gray-500"><?= $menu['menu_name_en'] ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= $menu['menu_url'] ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="?action=toggle&id=<?= $menu['id'] ?>" class="inline-block">
                                                <?php if ($menu['is_active']): ?>
                                                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                                        <i class="fas fa-check"></i> เปิด
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                                                        <i class="fas fa-times"></i> ปิด
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <a href="?action=edit&id=<?= $menu['id'] ?>" class="text-blue-600 hover:text-blue-800 mr-3">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?action=delete&id=<?= $menu['id'] ?>" class="text-red-600 hover:text-red-800"
                                               onclick="return confirm('ต้องการลบเมนูนี้และเมนูย่อยทั้งหมดหรือไม่?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>

                                    <!-- Child Menus -->
                                    <?php foreach ($menu['children'] as $child): ?>
                                        <tr class="bg-gray-50 hover:bg-gray-100">
                                            <td class="px-4 py-2 text-sm pl-12 text-gray-500"><?= $child['menu_order'] ?></td>
                                            <td class="px-4 py-2 pl-12">
                                                <i class="fas fa-level-up-alt fa-rotate-90 text-gray-400 mr-2"></i>
                                                <span class="text-gray-700"><?= $child['menu_name'] ?></span>
                                                <?php if ($child['menu_icon']): ?>
                                                    <i class="<?= $child['menu_icon'] ?> text-gray-400 ml-2"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-600"><?= $child['menu_url'] ?></td>
                                            <td class="px-4 py-2 text-center">
                                                <a href="?action=toggle&id=<?= $child['id'] ?>" class="inline-block">
                                                    <?php if ($child['is_active']): ?>
                                                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                                                            <i class="fas fa-check"></i>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full">
                                                            <i class="fas fa-times"></i>
                                                        </span>
                                                    <?php endif; ?>
                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <a href="?action=edit&id=<?= $child['id'] ?>" class="text-blue-600 hover:text-blue-800 mr-3">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="?action=delete&id=<?= $child['id'] ?>" class="text-red-600 hover:text-red-800"
                                                   onclick="return confirm('ต้องการลบเมนูนี้หรือไม่?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right: Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-<?= $edit_data ? 'edit' : 'plus' ?> text-teal-600"></i>
                        <?= $edit_data ? 'แก้ไขเมนู' : 'เพิ่มเมนูใหม่' ?>
                    </h2>

                    <form method="POST" class="space-y-4">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Parent Menu (ถ้าเป็น submenu)
                            </label>
                            <select name="parent_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <option value="">-- ไม่มี (Parent Menu) --</option>
                                <?php
                                $parent_menus = $conn->query("SELECT id, menu_name FROM nav_menu WHERE parent_id IS NULL ORDER BY menu_name ASC");
                                while ($parent = $parent_menus->fetch_assoc()):
                                ?>
                                    <option value="<?= $parent['id'] ?>" <?= ($edit_data && $edit_data['parent_id'] == $parent['id']) ? 'selected' : '' ?>>
                                        <?= $parent['menu_name'] ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อเมนู (TH) *</label>
                            <input type="text" name="menu_name" required
                                   value="<?= $edit_data['menu_name'] ?? '' ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อเมนู (EN)</label>
                            <input type="text" name="menu_name_en"
                                   value="<?= $edit_data['menu_name_en'] ?? '' ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">URL *</label>
                            <input type="text" name="menu_url" required
                                   value="<?= $edit_data['menu_url'] ?? '#' ?>"
                                   placeholder="#, page.php, https://..."
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Icon (Font Awesome)
                                <a href="https://fontawesome.com/icons" target="_blank" class="text-xs text-blue-600 ml-2">
                                    <i class="fas fa-external-link-alt"></i> ค้นหา Icon
                                </a>
                            </label>
                            <input type="text" name="menu_icon"
                                   value="<?= $edit_data['menu_icon'] ?? '' ?>"
                                   placeholder="fas fa-home"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับ *</label>
                            <input type="number" name="menu_order" required
                                   value="<?= $edit_data['menu_order'] ?? 0 ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Target</label>
                            <select name="target" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <option value="_self" <?= ($edit_data && $edit_data['target'] == '_self') ? 'selected' : '' ?>>_self (หน้าเดิม)</option>
                                <option value="_blank" <?= ($edit_data && $edit_data['target'] == '_blank') ? 'selected' : '' ?>>_blank (หน้าใหม่)</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                            <textarea name="description" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"><?= $edit_data['description'] ?? '' ?></textarea>
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active"
                                   <?= (!$edit_data || $edit_data['is_active']) ? 'checked' : '' ?>
                                   class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit" name="<?= $edit_data ? 'edit_menu' : 'add_menu' ?>"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-<?= $edit_data ? 'save' : 'plus' ?> mr-2"></i>
                                <?= $edit_data ? 'บันทึกการแก้ไข' : 'เพิ่มเมนู' ?>
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
</body>
</html>
