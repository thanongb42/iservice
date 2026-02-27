<?php
/**
 * Admin System Settings
 * หน้าจัดการตั้งค่าระบบ
 */

require_once '../config/database.php';
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title = 'ตั้งค่าระบบ';
$current_page = 'system_setting';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'ตั้งค่าระบบ']
];

// Fetch all settings
$settings = [];
$result = $conn->query("SELECT * FROM system_settings ORDER BY setting_key ASC");
while ($row = $result->fetch_assoc()) {
    $settings[$row['setting_key']] = [
        'id' => $row['id'],
        'value' => $row['setting_value'],
        'type' => $row['setting_type'],
        'description' => $row['description']
    ];
}

// Helper function to get setting
function getSetting($key, $default = '') {
    global $settings;
    return $settings[$key]['value'] ?? $default;
}

// Fetch services for email notification tab
$all_services = [];
$svc_result = $conn->query("SELECT id, service_code, service_name, icon, color_code FROM my_service ORDER BY display_order ASC");
if ($svc_result) {
    while ($row = $svc_result->fetch_assoc()) {
        $all_services[] = $row;
    }
}

// Auto-create service_notification_emails table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS `service_notification_emails` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `service_id` int(11) NOT NULL,
      `email` varchar(255) NOT NULL,
      `name` varchar(100) DEFAULT NULL,
      `is_active` tinyint(1) NOT NULL DEFAULT 1,
      `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `unique_service_email` (`service_id`, `email`),
      KEY `service_id` (`service_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

// Fetch notification emails grouped by service
$notification_emails = [];
$ne_result = $conn->query("
    SELECT sne.*, ms.service_name, ms.service_code
    FROM service_notification_emails sne
    JOIN my_service ms ON sne.service_id = ms.id
    ORDER BY ms.display_order ASC, sne.name ASC
");
if ($ne_result) {
    while ($row = $ne_result->fetch_assoc()) {
        $notification_emails[$row['service_id']][] = $row;
    }
}

?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<main class="main-content-transition lg:ml-0">

    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .nav-tabs {
            display: flex;
            gap: 0.5rem;
            border-bottom: 2px solid #e5e7eb;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .nav-tabs button {
            padding: 0.75rem 1.5rem;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            cursor: pointer;
            font-weight: 500;
            color: #6b7280;
            transition: all 0.3s ease;
        }
        .nav-tabs button.active {
            color: #009933;
            border-bottom-color: #009933;
        }
        .nav-tabs button:hover {
            color: #007a29;
        }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .settings-group {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .settings-group h3 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #111827;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-weight: 500;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.95rem;
        }
        .form-group small {
            display: block;
            color: #9ca3af;
            margin-top: 0.25rem;
            font-size: 0.875rem;
        }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="number"],
        .form-group input[type="password"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
            transition: all 0.2s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #009933;
            box-shadow: 0 0 0 3px rgba(0, 153, 51, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .btn-save {
            background: linear-gradient(135deg, #009933 0%, #007a29 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 0.375rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px rgba(0, 153, 51, 0.3);
        }
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border-left: 4px solid #10b981;
        }
        .alert-error {
            background: #fee2e2;
            color: #7f1d1d;
            border-left: 4px solid #ef4444;
        }
        .logo-preview {
            max-width: 200px;
            max-height: 200px;
            border-radius: 0.375rem;
            margin-top: 1rem;
        }
        .test-email-btn, .backup-btn {
            background: #3b82f6;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 0.375rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        .test-email-btn:hover, .backup-btn:hover {
            background: #2563eb;
        }
    </style>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-cog text-green-600"></i> ตั้งค่าระบบ
            </h1>
            <p class="mt-2 text-gray-600">จัดการตั้งค่าองค์กร email server และ backup database</p>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span>บันทึกการเปลี่ยนแปลงสำเร็จแล้ว</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span>เกิดข้อผิดพลาด: <?= htmlspecialchars($_GET['error']) ?></span>
            </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <div class="nav-tabs">
            <button class="tab-btn active" onclick="showTab(event, 'organization')">
                <i class="fas fa-building"></i> ข้อมูลองค์กร
            </button>
            <button class="tab-btn" onclick="showTab(event, 'email')">
                <i class="fas fa-envelope"></i> ตั้งค่า Email
            </button>
            <button class="tab-btn" onclick="showTab(event, 'email_list')">
                <i class="fas fa-mail-bulk"></i> Email แจ้งเตือนบริการ
            </button>
            <button class="tab-btn" onclick="showTab(event, 'backup')">
                <i class="fas fa-database"></i> Backup Database
            </button>
            <button class="tab-btn" onclick="showTab(event, 'db_manage'); loadBackupList(); loadTableCounts();">
                <i class="fas fa-trash-alt text-red-500"></i> จัดการข้อมูล
            </button>
        </div>

        <!-- ORGANIZATION SETTINGS TAB -->
        <div id="organization" class="tab-content active">
            <form action="api/system_settings_api.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="tab" value="organization">

                <div class="settings-group">
                    <h3><i class="fas fa-info-circle text-green-600"></i> ข้อมูลองค์กร</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>ชื่อองค์กร *</label>
                            <input type="text" name="organization_name" value="<?= htmlspecialchars(getSetting('organization_name')) ?>" required>
                            <small>ชื่อหลักขององค์กร</small>
                        </div>
                        <div class="form-group">
                            <label>ชื่อแอปพลิเคชัน</label>
                            <input type="text" name="app_name" value="<?= htmlspecialchars(getSetting('app_name')) ?>">
                            <small>ชื่อที่แสดงในระบบ</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>เบอร์โทรศัพท์</label>
                            <input type="text" name="organization_phone" value="<?= htmlspecialchars(getSetting('organization_phone')) ?>" placeholder="+66-2-XXX-XXXX">
                            <small>หมายเลขติดต่อ</small>
                        </div>
                        <div class="form-group">
                            <label>ที่อยู่</label>
                            <input type="text" name="organization_address" value="<?= htmlspecialchars(getSetting('organization_address')) ?>" placeholder="ที่อยู่ขององค์กร">
                            <small>ที่ตั้งองค์กร</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>คำอธิบายแอปพลิเคชัน</label>
                        <textarea name="app_description" rows="3" placeholder="คำอธิบายสั้นๆ เกี่ยวกับระบบ"><?= htmlspecialchars(getSetting('app_description')) ?></textarea>
                        <small>คำอธิบายที่แสดงในหน้า login หรือ footer</small>
                    </div>
                </div>

                <div class="settings-group">
                    <h3><i class="fas fa-image text-green-600"></i> โลโก้องค์กร</h3>
                    
                    <div class="form-group">
                        <label>อัปโหลดโลโก้</label>
                        <input type="file" name="logo_image" accept="image/*">
                        <small>รองรับ JPG, PNG, GIF (แนะนำ: 200x100px หรือเล็กกว่า)</small>
                        
                        <?php if (!empty(getSetting('logo_image'))): ?>
                            <div class="mt-3">
                                <p class="text-sm font-semibold mb-2">โลโก้ปัจจุบัน:</p>
                                <img src="../<?= htmlspecialchars(getSetting('logo_image')) ?>" alt="Logo" class="logo-preview">
                                <label class="block mt-2">
                                    <input type="checkbox" name="delete_logo"> ลบโลโก้
                                </label>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
            </form>
        </div>

        <!-- EMAIL SETTINGS TAB -->
        <div id="email" class="tab-content">
            <form action="api/system_settings_api.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="tab" value="email">

                <div class="settings-group">
                    <h3><i class="fas fa-envelope text-green-600"></i> ตั้งค่า SMTP Server</h3>
                    <p class="text-sm text-gray-600 mb-4">กำหนดเซิร์ฟเวอร์อีเมลสำหรับส่งข้อความจากระบบ</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Host *</label>
                            <input type="text" name="smtp_host" value="<?= htmlspecialchars(getSetting('smtp_host')) ?>" required placeholder="smtp.gmail.com">
                            <small>เช่น smtp.gmail.com, mail.company.com</small>
                        </div>
                        <div class="form-group">
                            <label>SMTP Port *</label>
                            <input type="number" name="smtp_port" value="<?= htmlspecialchars(getSetting('smtp_port')) ?>" required placeholder="587">
                            <small>ปกติ 587 (TLS) หรือ 465 (SSL)</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Encryption</label>
                            <select name="smtp_encryption">
                                <option value="tls" <?= getSetting('smtp_encryption') === 'tls' ? 'selected' : '' ?>>TLS</option>
                                <option value="ssl" <?= getSetting('smtp_encryption') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                                <option value="none" <?= getSetting('smtp_encryption') === 'none' ? 'selected' : '' ?>>ไม่มี</option>
                            </select>
                            <small>ประเภทการเข้ารหัส</small>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>SMTP Username</label>
                            <input type="text" name="smtp_username" value="<?= htmlspecialchars(getSetting('smtp_username')) ?>" placeholder="username@example.com">
                            <small>ชื่อผู้ใช้สำหรับเข้า SMTP</small>
                        </div>
                        <div class="form-group">
                            <label>SMTP Password</label>
                            <input type="password" name="smtp_password" value="" placeholder="<?= getSetting('smtp_password') ? '••••••••' : 'ยังไม่ได้ตั้งค่า' ?>" autocomplete="new-password">
                            <small>รหัสผ่าน SMTP (เว้นว่างไว้หากไม่ต้องการเปลี่ยน)</small>
                        </div>
                    </div>
                </div>

                <div class="settings-group">
                    <h3><i class="fas fa-paper-plane text-green-600"></i> ตั้งค่าข้อความอีเมล</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>ชื่อผู้ส่ง *</label>
                            <input type="text" name="email_from_name" value="<?= htmlspecialchars(getSetting('email_from_name')) ?>" required placeholder="iService">
                            <small>ชื่อที่แสดงในอีเมล</small>
                        </div>
                        <div class="form-group">
                            <label>อีเมลผู้ส่ง *</label>
                            <input type="email" name="email_from_address" value="<?= htmlspecialchars(getSetting('email_from_address')) ?>" required placeholder="noreply@example.com">
                            <small>อีเมลที่ใช้ส่งข้อความ</small>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom: 1.5rem;">
                    <button type="submit" class="btn-save" style="margin-right: 1rem;">
                        <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                    </button>
                    <button type="button" class="test-email-btn" onclick="testEmailConnection()">
                        <i class="fas fa-paper-plane"></i> ทดสอบส่งอีเมล
                    </button>
                </div>
            </form>
        </div>

        <!-- EMAIL NOTIFICATION LIST TAB -->
        <div id="email_list" class="tab-content">
            <div class="settings-group">
                <h3><i class="fas fa-mail-bulk text-green-600"></i> จัดการอีเมลแจ้งเตือนตามบริการ</h3>
                <p class="text-sm text-gray-600 mb-4">กำหนดอีเมลที่ต้องการให้ระบบแจ้งเตือนเมื่อมีคำขอบริการแต่ละประเภทเข้ามา</p>

                <?php if (empty($all_services)): ?>
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-inbox text-4xl mb-3"></i>
                        <p>ยังไม่มีบริการในระบบ กรุณาเพิ่มบริการก่อน</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($all_services as $svc):
                        $svc_emails = $notification_emails[$svc['id']] ?? [];
                    ?>
                    <div class="border border-gray-200 rounded-lg mb-4 overflow-hidden" id="svc-block-<?= $svc['id'] ?>">
                        <!-- Service Header -->
                        <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg flex items-center justify-center text-white text-sm bg-<?= htmlspecialchars($svc['color_code'] ?? 'blue') ?>-500">
                                    <i class="<?= htmlspecialchars($svc['icon'] ?? 'fas fa-star') ?>"></i>
                                </div>
                                <div>
                                    <span class="font-semibold text-gray-800"><?= htmlspecialchars($svc['service_name']) ?></span>
                                    <span class="text-xs text-gray-400 ml-2"><?= htmlspecialchars($svc['service_code']) ?></span>
                                </div>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-0.5 rounded-full font-semibold" id="email-count-<?= $svc['id'] ?>"><?= count($svc_emails) ?> อีเมล</span>
                            </div>
                            <button onclick="addEmailToService(<?= $svc['id'] ?>, '<?= htmlspecialchars(addslashes($svc['service_name'])) ?>')"
                                    class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition">
                                <i class="fas fa-plus mr-1"></i> เพิ่มอีเมล
                            </button>
                        </div>

                        <!-- Email List -->
                        <div class="px-4 py-2" id="email-list-<?= $svc['id'] ?>">
                            <?php if (empty($svc_emails)): ?>
                                <p class="text-sm text-gray-400 py-3 text-center empty-msg">ยังไม่มีอีเมลแจ้งเตือนสำหรับบริการนี้</p>
                            <?php else: ?>
                                <?php foreach ($svc_emails as $em): ?>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0 email-row" data-id="<?= $em['id'] ?>">
                                    <div class="flex items-center gap-3">
                                        <i class="fas fa-envelope text-gray-400"></i>
                                        <div>
                                            <span class="text-sm font-medium text-gray-700"><?= htmlspecialchars($em['email']) ?></span>
                                            <?php if (!empty($em['name'])): ?>
                                                <span class="text-xs text-gray-400 ml-1">(<?= htmlspecialchars($em['name']) ?>)</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!$em['is_active']): ?>
                                            <span class="text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full">ปิดใช้งาน</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button onclick="editNotifEmail(<?= $em['id'] ?>, '<?= htmlspecialchars(addslashes($em['email'])) ?>', '<?= htmlspecialchars(addslashes($em['name'] ?? '')) ?>', <?= $svc['id'] ?>)"
                                                class="text-blue-400 hover:text-blue-600" title="แก้ไข">
                                            <i class="fas fa-edit text-sm"></i>
                                        </button>
                                        <button onclick="toggleEmailActive(<?= $em['id'] ?>, <?= $em['is_active'] ? 0 : 1 ?>, <?= $svc['id'] ?>)"
                                                class="text-gray-400 hover:text-gray-600" title="เปิด/ปิดใช้งาน">
                                            <i class="fas fa-toggle-<?= $em['is_active'] ? 'on text-green-500' : 'off text-red-400' ?> text-lg"></i>
                                        </button>
                                        <button onclick="deleteNotifEmail(<?= $em['id'] ?>, <?= $svc['id'] ?>)"
                                                class="text-red-400 hover:text-red-600" title="ลบ">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- DATABASE MANAGE TAB -->
        <div id="db_manage" class="tab-content">

            <!-- Danger zone banner -->
            <div class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-5 py-4 mb-5">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl mt-0.5 flex-shrink-0"></i>
                <div>
                    <p class="font-semibold text-red-700">เขตอันตราย — ล้างข้อมูลคำขอทั้งหมด</p>
                    <p class="text-sm text-red-600 mt-0.5">ใช้สำหรับลบข้อมูลทดสอบออกเพื่อเริ่มใช้งานจริง ระบบจะสำรองข้อมูลอัตโนมัติก่อนล้างทุกครั้ง</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-5 gap-5">

                <!-- LEFT: Backup list -->
                <div class="lg:col-span-3 settings-group">
                    <h3 class="flex items-center gap-2">
                        <i class="fas fa-archive text-blue-500"></i>
                        ไฟล์สำรองข้อมูล
                        <button onclick="loadBackupList()" class="ml-auto text-xs bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 px-2 py-1 rounded-lg transition">
                            <i class="fas fa-sync-alt"></i> รีเฟรช
                        </button>
                    </h3>

                    <div class="mb-4 flex gap-2">
                        <button onclick="createBackupOnly()"
                            class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                            <i class="fas fa-download"></i> สำรองข้อมูลตอนนี้
                        </button>
                    </div>

                    <div id="backupListContainer">
                        <div class="text-center py-6 text-gray-400 text-sm">
                            <i class="fas fa-spinner fa-spin mr-2"></i> กำลังโหลด...
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Table counts + Truncate -->
                <div class="lg:col-span-2 space-y-4">

                    <!-- Row counts -->
                    <div class="settings-group">
                        <h3 class="flex items-center gap-2">
                            <i class="fas fa-table text-gray-500"></i>
                            จำนวนข้อมูลในระบบ
                            <button onclick="loadTableCounts()" class="ml-auto text-xs bg-gray-50 hover:bg-gray-100 text-gray-600 border border-gray-200 px-2 py-1 rounded-lg transition">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </h3>
                        <div id="tableCountsContainer" class="text-sm space-y-1 text-gray-600">
                            <div class="text-center py-3 text-gray-400">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Truncate action -->
                    <div class="settings-group border-2 border-red-200">
                        <h3 class="flex items-center gap-2 text-red-700">
                            <i class="fas fa-fire text-red-500"></i>
                            ล้างข้อมูลคำขอทั้งหมด
                        </h3>
                        <p class="text-xs text-gray-500 mb-4">ระบบจะสำรองข้อมูลอัตโนมัติก่อน แล้วจึงล้าง<br>สามารถ restore กลับได้จากรายการ backup ด้านซ้าย</p>
                        <button onclick="confirmTruncate()"
                            id="truncateBtn"
                            class="w-full flex items-center justify-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm px-4 py-2.5 rounded-lg transition">
                            <i class="fas fa-trash-alt"></i> สำรอง แล้วล้างข้อมูล
                        </button>
                    </div>

                </div>
            </div>
        </div>

        <!-- BACKUP SETTINGS TAB -->
        <div id="backup" class="tab-content">
            <form action="api/system_settings_api.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="tab" value="backup">

                <div class="settings-group">
                    <h3><i class="fas fa-database text-green-600"></i> ตั้งค่า Backup</h3>
                    <p class="text-sm text-gray-600 mb-4">จัดการการสำรองข้อมูลอัตโนมัติของฐานข้อมูล</p>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>เปิดใช้งาน Backup อัตโนมัติ</label>
                            <select name="backup_enable">
                                <option value="1" <?= getSetting('backup_enable') == 1 ? 'selected' : '' ?>>เปิด</option>
                                <option value="0" <?= getSetting('backup_enable') == 0 ? 'selected' : '' ?>>ปิด</option>
                            </select>
                            <small>เปิดปิดการสำรองข้อมูลอัตโนมัติ</small>
                        </div>
                        <div class="form-group">
                            <label>ตารางเวลา Backup</label>
                            <select name="backup_schedule">
                                <option value="daily" <?= getSetting('backup_schedule') === 'daily' ? 'selected' : '' ?>>ทุกวัน (Daily)</option>
                                <option value="weekly" <?= getSetting('backup_schedule') === 'weekly' ? 'selected' : '' ?>>ทุกสัปดาห์ (Weekly)</option>
                                <option value="monthly" <?= getSetting('backup_schedule') === 'monthly' ? 'selected' : '' ?>>ทุกเดือน (Monthly)</option>
                            </select>
                            <small>ความถี่ของการ backup</small>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Path สำหรับเก็บ Backup</label>
                        <input type="text" name="backup_path" value="<?= htmlspecialchars(getSetting('backup_path')) ?>" placeholder="/backups/">
                        <small>ต้องสิ้นสุดด้วย / เช่น /backups/ หรือ C:\backups\</small>
                    </div>
                </div>

                <div class="settings-group">
                    <h3><i class="fas fa-clock text-green-600"></i> สถานะ Backup</h3>
                    
                    <div class="form-group" style="background: #f3f4f6; padding: 1rem; border-radius: 0.375rem;">
                        <p class="text-sm mb-2">
                            <strong>ครั้งล่าสุด:</strong> 
                            <span id="lastBackup">ไม่มีข้อมูล</span>
                        </p>
                        <p class="text-sm">
                            <strong>ครั้งถัดไป:</strong> 
                            <span id="nextBackup">ไม่มีข้อมูล</span>
                        </p>
                    </div>
                </div>

                <button type="submit" class="btn-save" style="margin-right: 1rem;">
                    <i class="fas fa-save"></i> บันทึกการเปลี่ยนแปลง
                </button>
                <button type="button" class="backup-btn" onclick="createManualBackup()">
                    <i class="fas fa-plus"></i> Backup ตอนนี้
                </button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle form submissions via AJAX
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังบันทึก...';
                
                const formData = new FormData(this);
                
                fetch(this.getAttribute('action'), {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ',
                            text: data.message,
                            showConfirmButton: false,
                            timer: 1500
                        }).then(() => {
                            if (data.reload) {
                                location.reload();
                            }
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'ผิดพลาด',
                            text: data.message
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด',
                        text: 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' + error.message
                    });
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
            });
        });
    });

    function showTab(event, tabName) {
        event.preventDefault();

        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });

        // Remove active from all buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        // Show selected tab
        document.getElementById(tabName).classList.add('active');
        // Handle click on <i> inside button
        const btn = event.target.closest('.tab-btn');
        if (btn) btn.classList.add('active');
    }
    
function testEmailConnection() {
    if (!confirm('ทำการทดสอบส่งอีเมล? กรุณาตรวจสอบที่อยู่อีเมลผู้ส่งก่อน')) return;
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังทดสอบ...';
    
    fetch('api/system_settings_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=test_email'
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> ทดสอบส่งอีเมล';
        
        if (data.success) {
            alert('✓ ทดสอบสำเร็จ: ' + data.message);
        } else {
            alert('✗ ทดสอบล้มเหลว: ' + data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-paper-plane"></i> ทดสอบส่งอีเมล';
        alert('✗ เกิดข้อผิดพลาด: ' + err.message);
    });
}

function createManualBackup() {
    if (!confirm('สร้าง Backup ตอนนี้? ระบบอาจทำงานช้าขณะที่กำลังสำรองข้อมูล')) return;
    
    const btn = event.target;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสำรองข้อมูล...';
    
    fetch('api/system_settings_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=backup_now'
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Backup ตอนนี้';
        
        if (data.success) {
            alert('✓ สำรองข้อมูลสำเร็จ: ' + data.message);
            location.reload();
        } else {
            alert('✗ สำรองข้อมูลล้มเหลว: ' + data.message);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-plus"></i> Backup ตอนนี้';
        alert('✗ เกิดข้อผิดพลาด: ' + err.message);
    });
}

// ===== Email Notification List Functions =====
function addEmailToService(serviceId, serviceName) {
    Swal.fire({
        title: '<i class="fas fa-plus text-green-600"></i> เพิ่มอีเมลแจ้งเตือน',
        html: `
            <p class="text-sm text-gray-600 mb-3">บริการ: <strong>${serviceName}</strong></p>
            <div style="text-align:left;">
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">อีเมล <span class="text-red-500">*</span></label>
                <input type="email" id="swal-notif-email" placeholder="user@example.com" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem; margin-bottom:12px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">ชื่อผู้รับ</label>
                <input type="text" id="swal-notif-name" placeholder="ชื่อ-นามสกุล (ไม่บังคับ)" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem;">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-plus mr-1"></i> เพิ่ม',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#009933',
        cancelButtonColor: '#6b7280',
        preConfirm: () => {
            const email = document.getElementById('swal-notif-email').value.trim();
            const name = document.getElementById('swal-notif-name').value.trim();
            if (!email) {
                Swal.showValidationMessage('กรุณากรอกอีเมล');
                return false;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.showValidationMessage('รูปแบบอีเมลไม่ถูกต้อง');
                return false;
            }
            return { email, name };
        }
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            try {
                const formData = new FormData();
                formData.append('action', 'add');
                formData.append('service_id', serviceId);
                formData.append('email', result.value.email);
                formData.append('name', result.value.name);

                const res = await fetch('api/service_emails_api.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    await Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, timer: 1500, showConfirmButton: false });
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
            }
        }
    });
}

function editNotifEmail(id, currentEmail, currentName, serviceId) {
    Swal.fire({
        title: '<i class="fas fa-edit text-blue-600"></i> แก้ไขอีเมลแจ้งเตือน',
        html: `
            <div style="text-align:left;">
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">อีเมล <span class="text-red-500">*</span></label>
                <input type="email" id="swal-edit-email" value="${currentEmail}" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem; margin-bottom:12px;">
                <label style="display:block; font-size:0.85rem; font-weight:600; color:#374151; margin-bottom:4px;">ชื่อผู้รับ</label>
                <input type="text" id="swal-edit-name" value="${currentName}" placeholder="ชื่อ-นามสกุล (ไม่บังคับ)" style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; font-size:0.9rem;">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#6b7280',
        preConfirm: () => {
            const email = document.getElementById('swal-edit-email').value.trim();
            const name = document.getElementById('swal-edit-name').value.trim();
            if (!email) {
                Swal.showValidationMessage('กรุณากรอกอีเมล');
                return false;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                Swal.showValidationMessage('รูปแบบอีเมลไม่ถูกต้อง');
                return false;
            }
            return { email, name };
        }
    }).then(async (result) => {
        if (result.isConfirmed && result.value) {
            try {
                const formData = new FormData();
                formData.append('action', 'edit');
                formData.append('id', id);
                formData.append('email', result.value.email);
                formData.append('name', result.value.name);

                const res = await fetch('api/service_emails_api.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    // Update DOM in-place
                    const row = document.querySelector(`.email-row[data-id="${id}"]`);
                    if (row) {
                        const emailSpan = row.querySelector('.text-sm.font-medium.text-gray-700');
                        const nameSpan = row.querySelector('.text-xs.text-gray-400.ml-1');
                        const editBtn = row.querySelector('button[onclick^="editNotifEmail"]');

                        if (emailSpan) emailSpan.textContent = result.value.email;
                        if (result.value.name) {
                            if (nameSpan) {
                                nameSpan.textContent = '(' + result.value.name + ')';
                            } else {
                                const newName = document.createElement('span');
                                newName.className = 'text-xs text-gray-400 ml-1';
                                newName.textContent = '(' + result.value.name + ')';
                                emailSpan.parentElement.appendChild(newName);
                            }
                        } else {
                            if (nameSpan) nameSpan.remove();
                        }

                        // Update onclick with new values
                        const escapedEmail = result.value.email.replace(/'/g, "\\'");
                        const escapedName = result.value.name.replace(/'/g, "\\'");
                        editBtn.setAttribute('onclick', `editNotifEmail(${id}, '${escapedEmail}', '${escapedName}', ${serviceId})`);
                    }
                    Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, timer: 1200, showConfirmButton: false });
                } else {
                    Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
            }
        }
    });
}

async function deleteNotifEmail(id, serviceId) {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ',
        text: 'ต้องการลบอีเมลแจ้งเตือนนี้หรือไม่?',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });

    if (result.isConfirmed) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            const res = await fetch('api/service_emails_api.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                // Remove row from DOM
                const row = document.querySelector(`.email-row[data-id="${id}"]`);
                if (row) row.remove();
                // Update count
                const list = document.getElementById('email-list-' + serviceId);
                const remaining = list.querySelectorAll('.email-row').length;
                document.getElementById('email-count-' + serviceId).textContent = remaining + ' อีเมล';
                if (remaining === 0) {
                    list.innerHTML = '<p class="text-sm text-gray-400 py-3 text-center empty-msg">ยังไม่มีอีเมลแจ้งเตือนสำหรับบริการนี้</p>';
                }
                Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, timer: 1200, showConfirmButton: false });
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
        }
    }
}

// ===== DB Manage Tab =====

const DB_API = 'api/db_manage_api.php';

async function loadBackupList() {
    const container = document.getElementById('backupListContainer');
    container.innerHTML = '<div class="text-center py-6 text-gray-400 text-sm"><i class="fas fa-spinner fa-spin mr-2"></i> กำลังโหลด...</div>';
    const res  = await fetch(DB_API + '?action=list_backups');
    const data = await res.json();
    if (!data.success) { container.innerHTML = '<p class="text-red-500 text-sm">' + data.message + '</p>'; return; }

    if (data.backups.length === 0) {
        container.innerHTML = '<p class="text-center text-gray-400 text-sm py-4">ยังไม่มีไฟล์ backup</p>';
        return;
    }

    let html = '<div class="space-y-2">';
    data.backups.forEach(b => {
        html += `
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg border border-gray-200 hover:bg-gray-100 transition">
            <div>
                <p class="text-xs font-mono font-semibold text-gray-700">${b.filename}</p>
                <p class="text-xs text-gray-400 mt-0.5">${b.created} &bull; ${b.size_fmt}</p>
            </div>
            <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                <a href="${DB_API}?action=download_backup&filename=${encodeURIComponent(b.filename)}"
                   class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 px-2 py-1 rounded-lg transition" title="Download">
                    <i class="fas fa-download"></i>
                </a>
                <button onclick="restoreBackup('${b.filename}')"
                    class="text-xs bg-green-50 hover:bg-green-100 text-green-600 border border-green-200 px-2 py-1 rounded-lg transition" title="Restore">
                    <i class="fas fa-undo"></i> Restore
                </button>
                <button onclick="deleteBackup('${b.filename}')"
                    class="text-xs bg-red-50 hover:bg-red-100 text-red-500 border border-red-200 px-2 py-1 rounded-lg transition" title="ลบ">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
    });
    html += '</div>';
    container.innerHTML = html;
}

async function loadTableCounts() {
    const container = document.getElementById('tableCountsContainer');
    container.innerHTML = '<div class="text-center py-3 text-gray-400"><i class="fas fa-spinner fa-spin"></i></div>';
    const res  = await fetch(DB_API + '?action=table_counts');
    const data = await res.json();
    if (!data.success) { container.innerHTML = '<p class="text-red-500 text-sm">' + data.message + '</p>'; return; }

    let html = '';
    const tableLabels = {
        'service_requests'         : 'คำขอบริการ',
        'task_assignments'         : 'งานที่มอบหมาย',
        'task_history'             : 'ประวัติงาน',
        'request_email_details'    : 'รายละเอียด Email',
        'request_internet_details' : 'รายละเอียด Internet',
        'request_it_support_details':'รายละเอียด IT Support',
        'request_led_details'      : 'รายละเอียด LED',
        'request_mc_details'       : 'รายละเอียด MC',
        'request_nas_details'      : 'รายละเอียด NAS',
        'request_photography_details':'รายละเอียด ถ่ายภาพ',
        'request_printer_details'  : 'รายละเอียด Printer',
        'request_qrcode_details'   : 'รายละเอียด QRCode',
        'request_webdesign_details': 'รายละเอียด WebDesign',
    };
    let total = 0;
    for (const [tbl, count] of Object.entries(data.counts)) {
        const label = tableLabels[tbl] || tbl;
        const color = count > 0 ? 'text-gray-800 font-semibold' : 'text-gray-400';
        if (count > 0) total += count;
        html += `<div class="flex justify-between items-center py-1 border-b border-gray-100 last:border-0">
            <span class="text-xs text-gray-500 truncate max-w-[160px]" title="${tbl}">${label}</span>
            <span class="text-xs ${color} ml-2 flex-shrink-0">${count < 0 ? '—' : count + ' rows'}</span>
        </div>`;
    }
    html += `<div class="flex justify-between items-center pt-2 mt-1 border-t-2 border-gray-300">
        <span class="text-xs font-bold text-gray-700">รวมทั้งหมด</span>
        <span class="text-xs font-bold text-red-600">${total} rows</span>
    </div>`;
    container.innerHTML = html;
}

async function createBackupOnly() {
    const btn = event.currentTarget;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสำรอง...';
    try {
        const fd = new FormData();
        fd.append('action', 'create_backup');
        const res  = await fetch(DB_API, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'สำรองข้อมูลสำเร็จ', html: `ไฟล์: <code>${data.filename}</code><br>${data.rows} rows &bull; ${data.size_fmt}`, confirmButtonColor: '#009933' });
            loadBackupList();
        } else {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: e.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-download"></i> สำรองข้อมูลตอนนี้';
    }
}

async function confirmTruncate() {
    // Step 1: warn
    const warn = await Swal.fire({
        icon: 'warning',
        title: 'ล้างข้อมูลคำขอทั้งหมด?',
        html: `ระบบจะ<strong>สำรองข้อมูลอัตโนมัติก่อน</strong> แล้วจึงลบ<br>
               คำขอ, งานมอบหมาย และรายละเอียดทั้งหมด<br><br>
               <span class="text-red-600 font-semibold">ข้อมูลที่ไม่ backup จะหายถาวร</span>`,
        showCancelButton: true,
        confirmButtonText: 'ต่อไป — ยืนยัน',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    });
    if (!warn.isConfirmed) return;

    // Step 2: type confirmation
    const typed = await Swal.fire({
        title: 'พิมพ์ยืนยัน',
        html: `พิมพ์ <code class="bg-gray-100 px-1 rounded">DELETE_ALL_REQUESTS</code> เพื่อยืนยัน`,
        input: 'text',
        inputPlaceholder: 'DELETE_ALL_REQUESTS',
        showCancelButton: true,
        confirmButtonText: 'ล้างข้อมูล',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        inputValidator: (v) => v !== 'DELETE_ALL_REQUESTS' ? 'พิมพ์ไม่ถูกต้อง' : null,
    });
    if (!typed.isConfirmed) return;

    // Step 3: execute
    const btn = document.getElementById('truncateBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังสำรองและล้างข้อมูล...';
    try {
        const fd = new FormData();
        fd.append('action', 'truncate_requests');
        fd.append('confirm', 'DELETE_ALL_REQUESTS');
        const res  = await fetch(DB_API, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'ล้างข้อมูลสำเร็จ',
                html: `สำรองข้อมูลไว้แล้ว: <code>${data.backup}</code><br>(${data.backup_rows} rows)<br><br>ข้อมูลทั้งหมดถูกลบออกแล้ว`,
                confirmButtonColor: '#009933',
            });
            loadBackupList();
            loadTableCounts();
        } else {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
        }
    } catch(e) {
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: e.message });
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash-alt"></i> สำรอง แล้วล้างข้อมูล';
    }
}

async function restoreBackup(filename) {
    const confirm = await Swal.fire({
        icon: 'question',
        title: 'Restore ข้อมูล?',
        html: `จะ restore จากไฟล์:<br><code class="text-xs">${filename}</code><br><br>
               <span class="text-orange-600 font-semibold">ข้อมูลปัจจุบันทั้งหมดจะถูกแทนที่</span>`,
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-undo mr-1"></i> Restore',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6b7280',
    });
    if (!confirm.isConfirmed) return;

    await Swal.fire({
        title: 'กำลัง Restore...',
        html: 'กรุณารอสักครู่',
        allowOutsideClick: false,
        didOpen: () => Swal.showLoading(),
    });

    try {
        const fd = new FormData();
        fd.append('action', 'restore_backup');
        fd.append('filename', filename);
        const res  = await fetch(DB_API, { method: 'POST', body: fd });
        const data = await res.json();
        Swal.close();
        if (data.success) {
            await Swal.fire({ icon: 'success', title: 'Restore สำเร็จ', text: data.message, confirmButtonColor: '#009933' });
            loadTableCounts();
        } else {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
        }
    } catch(e) {
        Swal.close();
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: e.message });
    }
}

async function deleteBackup(filename) {
    const confirm = await Swal.fire({
        icon: 'warning',
        title: 'ลบไฟล์ backup?',
        text: filename,
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
    });
    if (!confirm.isConfirmed) return;
    const fd = new FormData();
    fd.append('action', 'delete_backup');
    fd.append('filename', filename);
    const res  = await fetch(DB_API, { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
        Swal.fire({ icon: 'success', title: 'ลบแล้ว', timer: 1200, showConfirmButton: false });
        loadBackupList();
    } else {
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
    }
}

// Auto-load when tab opens (called inline from the tab button onclick)

async function toggleEmailActive(id, isActive, serviceId) {
    const actionText = isActive ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
    const confirm = await Swal.fire({
        icon: 'question',
        title: 'ยืนยัน' + actionText,
        text: 'ต้องการ' + actionText + 'อีเมลแจ้งเตือนนี้หรือไม่?',
        showCancelButton: true,
        confirmButtonText: actionText,
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: isActive ? '#009933' : '#ef4444',
        cancelButtonColor: '#6b7280'
    });

    if (!confirm.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('action', 'toggle_active');
        formData.append('id', id);
        formData.append('is_active', isActive);

        const res = await fetch('api/service_emails_api.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            // Update DOM in-place
            const row = document.querySelector(`.email-row[data-id="${id}"]`);
            if (row) {
                const toggleBtn = row.querySelector('button[onclick^="toggleEmailActive"]');
                const toggleIcon = toggleBtn.querySelector('i');
                const statusBadge = row.querySelector('.bg-red-100.text-red-600');

                if (isActive) {
                    // Now active
                    toggleBtn.setAttribute('onclick', `toggleEmailActive(${id}, 0, ${serviceId})`);
                    toggleIcon.className = 'fas fa-toggle-on text-green-500 text-lg';
                    if (statusBadge) statusBadge.remove();
                } else {
                    // Now inactive
                    toggleBtn.setAttribute('onclick', `toggleEmailActive(${id}, 1, ${serviceId})`);
                    toggleIcon.className = 'fas fa-toggle-off text-red-400 text-lg';
                    if (!statusBadge) {
                        const badge = document.createElement('span');
                        badge.className = 'text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded-full';
                        badge.textContent = 'ปิดใช้งาน';
                        row.querySelector('.flex.items-center.gap-3').appendChild(badge);
                    }
                }
            }
            Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, timer: 1200, showConfirmButton: false });
        } else {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
        }
    } catch (e) {
        Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
    }
}
</script>
</main>
<?php include 'admin-layout/footer.php'; ?>
