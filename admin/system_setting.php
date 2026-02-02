<?php
/**
 * Admin System Settings
 * หน้าจัดการตั้งค่าระบบ
 */

require_once '../config/database.php';
session_start();

// Check admin access
if (!isset($_SESSION['admin_id'])) {
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
            color: #0d9488;
            border-bottom-color: #0d9488;
        }
        .nav-tabs button:hover {
            color: #0f766e;
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
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .btn-save {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
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
            box-shadow: 0 10px 15px rgba(13, 148, 136, 0.3);
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
                <i class="fas fa-cog text-teal-600"></i> ตั้งค่าระบบ
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
            <button class="tab-btn" onclick="showTab(event, 'backup')">
                <i class="fas fa-database"></i> Backup Database
            </button>
        </div>

        <!-- ORGANIZATION SETTINGS TAB -->
        <div id="organization" class="tab-content active">
            <form action="api/system_settings_api.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="tab" value="organization">

                <div class="settings-group">
                    <h3><i class="fas fa-info-circle text-teal-600"></i> ข้อมูลองค์กร</h3>
                    
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
                    <h3><i class="fas fa-image text-teal-600"></i> โลโก้องค์กร</h3>
                    
                    <div class="form-group">
                        <label>อัปโหลดโลโก้</label>
                        <input type="file" name="logo_image" accept="image/*">
                        <small>รองรับ JPG, PNG, GIF (แนะนำ: 200x100px หรือเล็กกว่า)</small>
                        
                        <?php if (!empty(getSetting('logo_image'))): ?>
                            <div class="mt-3">
                                <p class="text-sm font-semibold mb-2">โลโก้ปัจจุบัน:</p>
                                <img src="<?= htmlspecialchars(getSetting('logo_image')) ?>" alt="Logo" class="logo-preview">
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
                    <h3><i class="fas fa-envelope text-teal-600"></i> ตั้งค่า SMTP Server</h3>
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
                            <input type="password" name="smtp_password" value="<?= htmlspecialchars(getSetting('smtp_password')) ?>" placeholder="••••••••">
                            <small>รหัสผ่าน SMTP</small>
                        </div>
                    </div>
                </div>

                <div class="settings-group">
                    <h3><i class="fas fa-paper-plane text-teal-600"></i> ตั้งค่าข้อความอีเมล</h3>
                    
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

        <!-- BACKUP SETTINGS TAB -->
        <div id="backup" class="tab-content">
            <form action="api/system_settings_api.php" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="tab" value="backup">

                <div class="settings-group">
                    <h3><i class="fas fa-database text-teal-600"></i> ตั้งค่า Backup</h3>
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
                    <h3><i class="fas fa-clock text-teal-600"></i> สถานะ Backup</h3>
                    
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

</main>

<?php include 'admin-layout/footer.php'; ?>

<script>
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
    event.target.classList.add('active');
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
</script>
