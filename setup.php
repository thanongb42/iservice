<?php
/**
 * Database Setup Script
 * สคริปต์สำหรับสร้างตารางอัตโนมัติ
 */

// Include database config
require_once __DIR__ . '/config/database.php';

$errors = [];
$success = [];

// Create nav_menu table
$nav_menu_sql = "CREATE TABLE IF NOT EXISTS `nav_menu` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT NULL COMMENT 'NULL = parent menu, มีค่า = submenu',
  `menu_name` varchar(100) NOT NULL COMMENT 'ชื่อเมนู (ภาษาไทย)',
  `menu_name_en` varchar(100) DEFAULT NULL COMMENT 'ชื่อเมนู (ภาษาอังกฤษ)',
  `menu_url` varchar(255) DEFAULT '#' COMMENT 'URL/Link ของเมนู',
  `menu_icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `menu_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `target` varchar(20) DEFAULT '_self' COMMENT '_self, _blank, _parent, _top',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายเพิ่มเติม',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `parent_id` (`parent_id`),
  KEY `is_active` (`is_active`),
  KEY `menu_order` (`menu_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($nav_menu_sql)) {
    $success[] = "✓ สร้างตาราง nav_menu สำเร็จ";
} else {
    $errors[] = "✗ สร้างตาราง nav_menu ล้มเหลว: " . $conn->error;
}

// Create my_service table
$my_service_sql = "CREATE TABLE IF NOT EXISTS `my_service` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `service_code` varchar(50) NOT NULL COMMENT 'รหัสบริการ',
  `service_name` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาไทย)',
  `service_name_en` varchar(100) NOT NULL COMMENT 'ชื่อบริการ (ภาษาอังกฤษ)',
  `description` text DEFAULT NULL COMMENT 'คำอธิบายบริการ',
  `icon` varchar(50) DEFAULT NULL COMMENT 'Font Awesome icon class',
  `color_code` varchar(20) DEFAULT 'blue' COMMENT 'สี',
  `service_url` varchar(255) DEFAULT '#' COMMENT 'URL สำหรับคลิก',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_code` (`service_code`),
  KEY `is_active` (`is_active`),
  KEY `display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($my_service_sql)) {
    $success[] = "✓ สร้างตาราง my_service สำเร็จ";
} else {
    $errors[] = "✗ สร้างตาราง my_service ล้มเหลว: " . $conn->error;
}

// Create learning_resources table
$learning_resources_sql = "CREATE TABLE IF NOT EXISTS `learning_resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL COMMENT 'หัวข้อ/ชื่อเรื่อง',
  `description` text DEFAULT NULL COMMENT 'คำอธิบาย/รายละเอียดย่อ',
  `resource_type` varchar(50) NOT NULL COMMENT 'ประเภท: pdf, video, podcast, blog, sourcecode, youtube, flipbook',
  `resource_url` varchar(500) NOT NULL COMMENT 'URL/Link ของทรัพยากร',
  `cover_image` varchar(255) DEFAULT NULL COMMENT 'URL ภาพหน้าปก',
  `category` varchar(100) DEFAULT NULL COMMENT 'หมวดหมู่: คู่มือ, หลักสูตร, บทความ, etc.',
  `author` varchar(100) DEFAULT NULL COMMENT 'ผู้เขียน/ผู้สร้าง',
  `duration` varchar(50) DEFAULT NULL COMMENT 'ระยะเวลา (สำหรับ Video/Podcast)',
  `file_size` varchar(50) DEFAULT NULL COMMENT 'ขนาดไฟล์ (สำหรับ PDF)',
  `tags` varchar(255) DEFAULT NULL COMMENT 'Tags/คำค้นหา (คั่นด้วยคอมม่า)',
  `view_count` int(11) DEFAULT 0 COMMENT 'จำนวนการเข้าชม',
  `is_featured` tinyint(1) DEFAULT 0 COMMENT '1 = แนะนำ, 0 = ปกติ',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = แสดง, 0 = ซ่อน',
  `display_order` int(11) NOT NULL DEFAULT 0 COMMENT 'ลำดับการแสดงผล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `resource_type` (`resource_type`),
  KEY `category` (`category`),
  KEY `is_active` (`is_active`),
  KEY `is_featured` (`is_featured`),
  KEY `display_order` (`display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($learning_resources_sql)) {
    $success[] = "✓ สร้างตาราง learning_resources สำเร็จ";
} else {
    $errors[] = "✗ สร้างตาราง learning_resources ล้มเหลว: " . $conn->error;
}

// Execute service_requests SQL file
$sql_file = __DIR__ . '/database/service_requests.sql';
if (file_exists($sql_file)) {
    $sql_content = file_get_contents($sql_file);
    // Remove comments and split into individual queries
    $sql_content = preg_replace('/--[^\n]*\n/', '', $sql_content);
    $queries = array_filter(array_map('trim', explode(';', $sql_content)));

    foreach ($queries as $query) {
        if (!empty($query) && !stripos($query, 'INSERT INTO')) {
            if ($conn->query($query)) {
                // Count successful table creations
                if (stripos($query, 'CREATE TABLE') !== false) {
                    preg_match('/CREATE TABLE.*?`([^`]+)`/', $query, $matches);
                    if (isset($matches[1])) {
                        $success[] = "✓ สร้างตาราง {$matches[1]} สำเร็จ";
                    }
                }
            }
        }
    }
} else {
    $errors[] = "✗ ไม่พบไฟล์ service_requests.sql";
}

// Check if nav_menu is empty and insert sample data
$check_nav = $conn->query("SELECT COUNT(*) as count FROM nav_menu");
$nav_count = $check_nav->fetch_assoc()['count'];

if ($nav_count == 0) {
    $nav_data = [
        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        (NULL, 'หน้าแรก', 'Home', 'index.php', NULL, 1, 1)",

        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        (NULL, 'บริการออนไลน์', 'Online Services', '#', 'fas fa-globe', 2, 1)",

        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        (NULL, 'คู่มือการใช้งาน', 'User Manual', '#', 'fas fa-book', 3, 1)",

        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        (NULL, 'ติดต่อเรา', 'Contact Us', 'contact.php', NULL, 4, 1)"
    ];

    foreach ($nav_data as $sql) {
        if ($conn->query($sql)) {
            $success[] = "✓ เพิ่มข้อมูล nav_menu สำเร็จ";
        }
    }

    // Add submenus
    $parent_service_id = $conn->insert_id - 2; // Get บริการออนไลน์ ID
    $parent_manual_id = $conn->insert_id - 1; // Get คู่มือการใช้งาน ID

    $submenu_service = [
        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        ($parent_service_id, 'แจ้งซ่อมคอมพิวเตอร์', 'IT Support', 'it-support.php', 'fas fa-tools', 1, 1)",

        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        ($parent_service_id, 'ขอใช้อินเทอร์เน็ต', 'Internet Request', 'internet-request.php', 'fas fa-wifi', 2, 1)",

        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        ($parent_service_id, 'ขอพื้นที่เก็บข้อมูล', 'Storage Request', 'storage-request.php', 'fas fa-hdd', 3, 1)"
    ];

    foreach ($submenu_service as $sql) {
        $conn->query($sql);
    }

    $submenu_manual = [
        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        ($parent_manual_id, 'Email เทศบาล', 'Email', 'manual-email.php', 'fas fa-envelope', 1, 1)",

        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        ($parent_manual_id, 'NAS', 'NAS', 'manual-nas.php', 'fas fa-server', 2, 1)",

        "INSERT INTO `nav_menu` (`parent_id`, `menu_name`, `menu_name_en`, `menu_url`, `menu_icon`, `menu_order`, `is_active`) VALUES
        ($parent_manual_id, 'Internet', 'Internet', 'manual-internet.php', 'fas fa-globe', 3, 1)"
    ];

    foreach ($submenu_manual as $sql) {
        $conn->query($sql);
    }
}

// Check if my_service is empty and insert sample data
$check_service = $conn->query("SELECT COUNT(*) as count FROM my_service");
$service_count = $check_service->fetch_assoc()['count'];

if ($service_count == 0) {
    $service_data = [
        ["EMAIL", "อีเมลเทศบาล", "Email Service", "ขอเปิดใช้งานอีเมลใหม่, รีเซ็ตรหัสผ่าน, เพิ่มขนาดพื้นที่กล่องจดหมาย", "fas fa-envelope", "blue", "service-email.php", 1],
        ["INTERNET", "อินเทอร์เน็ต / WiFi", "Internet Access", "ขอรหัสผ่าน WiFi, แจ้งปัญหาเน็ตช้า, ติดตั้งจุดกระจายสัญญาณเพิ่ม", "fas fa-wifi", "indigo", "service-internet.php", 2],
        ["IT_SUPPORT", "แจ้งซ่อมระบบ IT", "IT Support", "คอมพิวเตอร์เสีย, เครื่องพิมพ์มีปัญหา, ลงโปรแกรม, กำจัดไวรัส", "fas fa-tools", "red", "service-it-support.php", 3],
        ["NAS", "พื้นที่เก็บข้อมูล NAS", "NAS Storage", "ขอพื้นที่แชร์ไฟล์ส่วนกลาง (Network Attached Storage), กู้คืนข้อมูลหาย", "fas fa-hdd", "orange", "service-nas.php", 4],
        ["QR_CODE", "สร้าง QR Code", "QR Code Generator", "บริการสร้าง QR Code สำหรับประชาสัมพันธ์โครงการต่างๆ", "fas fa-qrcode", "purple", "service-qrcode.php", 5],
        ["PHOTOGRAPHY", "บริการถ่ายภาพ", "Photography Service", "จองคิวช่างภาพสำหรับงานพิธี, งานกิจกรรมโครงการเทศบาล", "fas fa-camera", "pink", "service-photo.php", 6],
        ["WEB_DESIGN", "ออกแบบเว็บไซต์", "Web Design", "ขอให้ออกแบบและพัฒนาเว็บไซต์สำหรับหน่วยงานต่างๆ", "fas fa-laptop-code", "teal", "service-webdesign.php", 7],
        ["PRINTER", "เครื่องพิมพ์และสแกนเนอร์", "Printer & Scanner", "แจ้งซ่อมเครื่องพิมพ์, เติมหมึก, ซื้อวัสดุสิ้นเปลือง", "fas fa-print", "green", "service-printer.php", 8]
    ];

    $stmt = $conn->prepare("INSERT INTO my_service (service_code, service_name, service_name_en, description, icon, color_code, service_url, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");

    foreach ($service_data as $data) {
        $stmt->bind_param("sssssssi", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7]);
        if ($stmt->execute()) {
            $success[] = "✓ เพิ่มบริการ: {$data[1]}";
        } else {
            $errors[] = "✗ เพิ่มบริการ {$data[1]} ล้มเหลว";
        }
    }
}

// Check if learning_resources is empty and insert sample data
$check_resources = $conn->query("SELECT COUNT(*) as count FROM learning_resources");
$resource_count = $check_resources->fetch_assoc()['count'];

if ($resource_count == 0) {
    $learning_data = [
        ["คู่มือการใช้งานอีเมลเทศบาล", "คู่มือฉบับสมบูรณ์สำหรับการใช้งานระบบอีเมลของเทศบาล รวมถึงการตั้งค่าบนมือถือและคอมพิวเตอร์", "pdf", "documents/email-manual.pdf", "", "คู่มือ", "ฝ่าย IT", NULL, "2.5 MB", "email,คู่มือ,การใช้งาน", 0, 1, 1],
        ["วิธีการใช้งาน NAS Storage", "สอนการใช้งานระบบ NAS เพื่อจัดเก็บและแชร์ไฟล์ภายในองค์กร", "video", "videos/nas-tutorial.mp4", "", "หลักสูตร", "ทีม IT Support", "15:30", NULL, "nas,storage,tutorial", 0, 1, 3],
        ["PHP Programming สำหรับมือใหม่", "หลักสูตร PHP เบื้องต้นสำหรับพัฒนาเว็บไซต์", "youtube", "https://www.youtube.com/watch?v=dQw4w9WgXcQ", "", "หลักสูตร", "Code Academy", "2:30:00", NULL, "php,programming,course", 0, 1, 5],
        ["IT Talk: เทคนิคการรักษาความปลอดภัยข้อมูล", "Podcast เกี่ยวกับการรักษาความปลอดภัยของข้อมูลในยุคดิจิทัล", "podcast", "podcasts/security-tips.mp3", "", "Podcast", "ฝ่าย IT Security", "25:15", "45 MB", "security,podcast,ความปลอดภัย", 0, 0, 6],
        ["10 เคล็ดลับการใช้คอมพิวเตอร์อย่างปลอดภัย", "บทความแนะนำวิธีการใช้งานคอมพิวเตอร์ให้ปลอดภัยจากมัลแวร์และแฮกเกอร์", "blog", "blog-detail.php?id=1", "", "บทความ", "Admin IT", NULL, NULL, "security,tips,คอมพิวเตอร์", 0, 0, 7],
        ["รายงานประจำปี IT 2567", "เอกสารรายงานผลการดำเนินงานด้าน IT ประจำปี 2567 แบบ Flipbook", "flipbook", "flipbook/annual-report-2024.html", "", "รายงาน", "ฝ่าย IT", NULL, NULL, "รายงาน,annual,2567", 0, 1, 8],
        ["Source Code: ระบบจองห้องประชุม", "โค้ดตัวอย่างระบบจองห้องประชุมออนไลน์ พร้อมเอกสารประกอบ", "sourcecode", "https://github.com/example/meeting-room", "", "Source Code", "Dev Team", NULL, NULL, "sourcecode,php,javascript", 0, 0, 9]
    ];

    $stmt_lr = $conn->prepare("INSERT INTO learning_resources (title, description, resource_type, resource_url, cover_image, category, author, duration, file_size, tags, view_count, is_featured, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, 1)");

    foreach ($learning_data as $data) {
        $stmt_lr->bind_param("ssssssssssii", $data[0], $data[1], $data[2], $data[3], $data[4], $data[5], $data[6], $data[7], $data[8], $data[9], $data[10], $data[11]);
        if ($stmt_lr->execute()) {
            $success[] = "✓ เพิ่มทรัพยากร: {$data[0]}";
        } else {
            $errors[] = "✗ เพิ่มทรัพยากร {$data[0]} ล้มเหลว";
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup - Green Theme</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-2xl p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-teal-400 to-blue-500 rounded-full mx-auto mb-4 flex items-center justify-center">
                <i class="fas fa-database text-white text-3xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Database Setup</h1>
            <p class="text-gray-600">ติดตั้งและกำหนดค่าฐานข้อมูลอัตโนมัติ</p>
        </div>

        <div class="space-y-4 mb-8">
            <?php if (!empty($success)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-check-circle text-green-500 text-2xl mr-3 mt-1"></i>
                        <div class="flex-1">
                            <h3 class="font-bold text-green-800 mb-2">ติดตั้งสำเร็จ!</h3>
                            <ul class="space-y-1">
                                <?php foreach ($success as $msg): ?>
                                    <li class="text-sm text-green-700"><?= $msg ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-circle text-red-500 text-2xl mr-3 mt-1"></i>
                        <div class="flex-1">
                            <h3 class="font-bold text-red-800 mb-2">เกิดข้อผิดพลาด</h3>
                            <ul class="space-y-1">
                                <?php foreach ($errors as $msg): ?>
                                    <li class="text-sm text-red-700"><?= $msg ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <a href="index.php" class="block bg-gradient-to-r from-teal-500 to-blue-600 hover:from-teal-600 hover:to-blue-700 text-white text-center px-6 py-4 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                <i class="fas fa-home mr-2"></i>ไปหน้าแรก
            </a>
            <a href="admin/my_service.php" class="block bg-gradient-to-r from-purple-500 to-pink-600 hover:from-purple-600 hover:to-pink-700 text-white text-center px-6 py-4 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                <i class="fas fa-cog mr-2"></i>จัดการบริการ
            </a>
            <a href="admin/nav_menu.php" class="block bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-center px-6 py-4 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                <i class="fas fa-bars mr-2"></i>จัดการเมนู
            </a>
            <a href="admin/learning_resources.php" class="block bg-gradient-to-r from-blue-500 to-indigo-600 hover:from-blue-600 hover:to-indigo-700 text-white text-center px-6 py-4 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                <i class="fas fa-graduation-cap mr-2"></i>จัดการทรัพยากร
            </a>
            <a href="admin/service_requests.php" class="block bg-gradient-to-r from-green-500 to-teal-600 hover:from-green-600 hover:to-teal-700 text-white text-center px-6 py-4 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                <i class="fas fa-clipboard-list mr-2"></i>จัดการคำขอ
            </a>
            <button onclick="location.reload()" class="block bg-gradient-to-r from-gray-500 to-gray-700 hover:from-gray-600 hover:to-gray-800 text-white text-center px-6 py-4 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                <i class="fas fa-redo mr-2"></i>รันอีกครั้ง
            </button>
        </div>

        <div class="mt-8 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-bold text-gray-800 mb-2 flex items-center">
                <i class="fas fa-info-circle text-blue-500 mr-2"></i>ข้อมูล Database
            </h3>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="text-gray-600">Database:</div>
                <div class="font-semibold text-gray-800"><?= DB_NAME ?></div>
                <div class="text-gray-600">Host:</div>
                <div class="font-semibold text-gray-800"><?= DB_HOST ?></div>
                <div class="text-gray-600">User:</div>
                <div class="font-semibold text-gray-800"><?= DB_USER ?></div>
            </div>
        </div>

        <div class="mt-6 text-center text-sm text-gray-500">
            <p>หากไม่มีปัญหา สามารถลบไฟล์ setup.php ได้</p>
        </div>
    </div>
</body>
</html>
