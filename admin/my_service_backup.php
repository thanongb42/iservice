<?php
/**
 * My Service Management Page
 * หน้าจัดการบริการต่างๆ (CRUD)
 */

// Include database config
require_once '../config/database.php';

// Start session
session_start();

// Handle CRUD operations
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// CREATE - Add new service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    $service_code = strtoupper(clean_input($_POST['service_code']));
    $service_name = clean_input($_POST['service_name']);
    $service_name_en = clean_input($_POST['service_name_en']);
    $description = clean_input($_POST['description']);
    $icon = clean_input($_POST['icon']);
    $color_code = clean_input($_POST['color_code']);
    $service_url = clean_input($_POST['service_url']);
    $display_order = (int)$_POST['display_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "INSERT INTO my_service (service_code, service_name, service_name_en, description, icon, color_code, service_url, display_order, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssssii", $service_code, $service_name, $service_name_en, $description, $icon, $color_code, $service_url, $display_order, $is_active);

    if ($stmt->execute()) {
        $message = "เพิ่มบริการสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// UPDATE - Edit service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_service'])) {
    $id = (int)$_POST['id'];
    $service_code = strtoupper(clean_input($_POST['service_code']));
    $service_name = clean_input($_POST['service_name']);
    $service_name_en = clean_input($_POST['service_name_en']);
    $description = clean_input($_POST['description']);
    $icon = clean_input($_POST['icon']);
    $color_code = clean_input($_POST['color_code']);
    $service_url = clean_input($_POST['service_url']);
    $display_order = (int)$_POST['display_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $sql = "UPDATE my_service SET service_code=?, service_name=?, service_name_en=?, description=?, icon=?,
            color_code=?, service_url=?, display_order=?, is_active=? WHERE id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssii", $service_code, $service_name, $service_name_en, $description, $icon, $color_code, $service_url, $display_order, $is_active, $id);

    if ($stmt->execute()) {
        $message = "แก้ไขบริการสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// DELETE - Remove service
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    if ($conn->query("DELETE FROM my_service WHERE id = $id")) {
        $message = "ลบบริการสำเร็จ!";
    } else {
        $error = "เกิดข้อผิดพลาด: " . $conn->error;
    }
}

// TOGGLE ACTIVE - Toggle service status
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE my_service SET is_active = NOT is_active WHERE id = $id");
    $message = "เปลี่ยนสถานะสำเร็จ!";
}

// Fetch all services
$services = [];
$result = $conn->query("SELECT * FROM my_service ORDER BY display_order ASC");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Get edit data
$edit_data = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $result = $conn->query("SELECT * FROM my_service WHERE id = $id");
    $edit_data = $result->fetch_assoc();
}

// Color definitions for preview
$color_map = [
    'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600', 'border' => 'border-blue-200'],
    'indigo' => ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-600', 'border' => 'border-indigo-200'],
    'red' => ['bg' => 'bg-red-500', 'text' => 'text-red-600', 'border' => 'border-red-200'],
    'orange' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-600', 'border' => 'border-orange-200'],
    'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-600', 'border' => 'border-purple-200'],
    'pink' => ['bg' => 'bg-pink-500', 'text' => 'text-pink-600', 'border' => 'border-pink-200'],
    'teal' => ['bg' => 'bg-teal-500', 'text' => 'text-teal-600', 'border' => 'border-teal-200'],
    'green' => ['bg' => 'bg-green-500', 'text' => 'text-green-600', 'border' => 'border-green-200'],
    'gray' => ['bg' => 'bg-gray-500', 'text' => 'text-gray-600', 'border' => 'border-gray-200'],
    'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-600', 'border' => 'border-yellow-200'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการบริการ - Admin</title>
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
                        <i class="fas fa-briefcase text-teal-600"></i> จัดการบริการ (My Service)
                    </h1>
                    <p class="text-gray-600 mt-2">เพิ่ม แก้ไข ลบ และจัดการบริการต่างๆ ของระบบ</p>
                </div>
                <div class="flex space-x-3">
                    <a href="nav_menu.php" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-bars mr-2"></i>จัดการเมนู
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
            <!-- Left: Service Cards -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-th-large text-teal-600"></i> รายการบริการทั้งหมด (<?= count($services) ?>)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($services as $service):
                            $colors = $color_map[$service['color_code']] ?? $color_map['blue'];
                        ?>
                            <div class="border-2 <?= $colors['border'] ?> rounded-xl p-4 hover:shadow-lg transition <?= !$service['is_active'] ? 'opacity-50' : '' ?>">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="w-12 h-12 <?= $colors['bg'] ?> rounded-lg flex items-center justify-center text-white text-2xl">
                                        <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
                                    </div>
                                    <div class="flex space-x-2">
                                        <a href="?action=toggle&id=<?= $service['id'] ?>" class="text-gray-400 hover:text-gray-600" title="เปิด/ปิด">
                                            <?php if ($service['is_active']): ?>
                                                <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                            <?php else: ?>
                                                <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                            <?php endif; ?>
                                        </a>
                                        <a href="?action=edit&id=<?= $service['id'] ?>" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=delete&id=<?= $service['id'] ?>" class="text-red-600 hover:text-red-800"
                                           onclick="return confirm('ต้องการลบบริการนี้หรือไม่?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-800 mb-1"><?= htmlspecialchars($service['service_name']) ?></h3>
                                <p class="text-xs text-gray-500 mb-2 uppercase font-semibold"><?= htmlspecialchars($service['service_name_en']) ?></p>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($service['description']) ?></p>
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span class="<?= $colors['bg'] ?> text-white px-2 py-1 rounded"><?= $service['color_code'] ?></span>
                                    <span>ลำดับ: <?= $service['display_order'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($services)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-6xl mb-4"></i>
                            <p>ยังไม่มีบริการในระบบ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-<?= $edit_data ? 'edit' : 'plus' ?> text-teal-600"></i>
                        <?= $edit_data ? 'แก้ไขบริการ' : 'เพิ่มบริการใหม่' ?>
                    </h2>

                    <form method="POST" class="space-y-4">
                        <?php if ($edit_data): ?>
                            <input type="hidden" name="id" value="<?= $edit_data['id'] ?>">
                        <?php endif; ?>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">รหัสบริการ (CODE) *</label>
                            <input type="text" name="service_code" required
                                   value="<?= $edit_data['service_code'] ?? '' ?>"
                                   placeholder="EMAIL, INTERNET, IT_SUPPORT"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 uppercase focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">ภาษาอังกฤษเท่านั้น ไม่มีช่องว่าง (ใช้ _ แทน)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อบริการ (TH) *</label>
                            <input type="text" name="service_name" required
                                   value="<?= $edit_data['service_name'] ?? '' ?>"
                                   placeholder="อีเมลเทศบาล"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อบริการ (EN) *</label>
                            <input type="text" name="service_name_en" required
                                   value="<?= $edit_data['service_name_en'] ?? '' ?>"
                                   placeholder="Email Service"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                            <textarea name="description" rows="3"
                                      placeholder="รายละเอียดบริการ..."
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"><?= $edit_data['description'] ?? '' ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Icon (Font Awesome) *
                                <a href="https://fontawesome.com/icons" target="_blank" class="text-xs text-blue-600 ml-2">
                                    <i class="fas fa-external-link-alt"></i> ค้นหา Icon
                                </a>
                            </label>
                            <div class="relative">
                                <input type="text" name="icon" required id="iconInput"
                                       value="<?= $edit_data['icon'] ?? 'fas fa-star' ?>"
                                       placeholder="fas fa-envelope"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-12 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <div class="absolute right-3 top-2 text-2xl" id="iconPreview">
                                    <i class="<?= $edit_data['icon'] ?? 'fas fa-star' ?>"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">สี (Color Code) *</label>
                            <select name="color_code" id="colorSelect" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <?php foreach ($color_map as $color_name => $color_classes): ?>
                                    <option value="<?= $color_name ?>" <?= ($edit_data && $edit_data['color_code'] == $color_name) ? 'selected' : '' ?>>
                                        <?= ucfirst($color_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2 p-3 border-2 rounded-lg text-center" id="colorPreview">
                                <div class="w-12 h-12 mx-auto rounded-lg flex items-center justify-center text-white text-2xl <?= $color_map[$edit_data['color_code'] ?? 'blue']['bg'] ?>">
                                    <i class="fas fa-palette"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">URL</label>
                            <input type="text" name="service_url"
                                   value="<?= $edit_data['service_url'] ?? '#' ?>"
                                   placeholder="service-email.php, #, https://..."
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับการแสดงผล *</label>
                            <input type="number" name="display_order" required
                                   value="<?= $edit_data['display_order'] ?? 0 ?>"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active"
                                   <?= (!$edit_data || $edit_data['is_active']) ? 'checked' : '' ?>
                                   class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit" name="<?= $edit_data ? 'edit_service' : 'add_service' ?>"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-<?= $edit_data ? 'save' : 'plus' ?> mr-2"></i>
                                <?= $edit_data ? 'บันทึกการแก้ไข' : 'เพิ่มบริการ' ?>
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
        // Icon preview
        document.getElementById('iconInput').addEventListener('input', function() {
            const iconClass = this.value || 'fas fa-star';
            document.getElementById('iconPreview').innerHTML = '<i class="' + iconClass + '"></i>';
        });

        // Color preview
        document.getElementById('colorSelect').addEventListener('change', function() {
            const colors = <?= json_encode($color_map) ?>;
            const selectedColor = this.value;
            const colorClass = colors[selectedColor]?.bg || 'bg-blue-500';

            document.getElementById('colorPreview').innerHTML =
                '<div class="w-12 h-12 mx-auto rounded-lg flex items-center justify-center text-white text-2xl ' + colorClass + '">' +
                '<i class="fas fa-palette"></i></div>';
        });
    </script>
</body>
</html>
