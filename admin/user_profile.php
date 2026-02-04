<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $prefix_id = !empty($_POST['prefix_id']) ? intval($_POST['prefix_id']) : null;
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $position = trim($_POST['position']);
        $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;

        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email)) {
            $error = 'กรุณากรอกข้อมูลที่จำเป็น';
        } else {
            // Check if email already exists (excluding current user)
            $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
            $check_email->bind_param("si", $email, $user_id);
            $check_email->execute();
            if ($check_email->get_result()->num_rows > 0) {
                $error = 'อีเมลนี้ถูกใช้งานแล้ว';
            } else {
                $stmt = $conn->prepare("UPDATE users SET prefix_id = ?, first_name = ?, last_name = ?, email = ?, phone = ?, position = ?, department_id = ?, updated_at = NOW() WHERE user_id = ?");
                $stmt->bind_param("isssssii", $prefix_id, $first_name, $last_name, $email, $phone, $position, $department_id, $user_id);

                if ($stmt->execute()) {
                    $message = 'อัปเดตข้อมูลโปรไฟล์สำเร็จ';
                    // Update session
                    $_SESSION['first_name'] = $first_name;
                    $_SESSION['email'] = $email;
                } else {
                    $error = 'เกิดข้อผิดพลาด: ' . $conn->error;
                }
            }
        }
    }

    if ($action === 'update_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'กรุณากรอกรหัสผ่านให้ครบ';
        } elseif ($new_password !== $confirm_password) {
            $error = 'รหัสผ่านใหม่ไม่ตรงกัน';
        } elseif (strlen($new_password) < 6) {
            $error = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_data = $result->fetch_assoc();

            if (password_verify($current_password, $user_data['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $user_id);

                if ($update_stmt->execute()) {
                    $message = 'เปลี่ยนรหัสผ่านสำเร็จ';
                } else {
                    $error = 'เกิดข้อผิดพลาด: ' . $conn->error;
                }
            } else {
                $error = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
            }
        }
    }

    if ($action === 'upload_image') {
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $filename = $_FILES['profile_image']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (!in_array($filetype, $allowed)) {
                $error = 'อนุญาตเฉพาะไฟล์ JPG, PNG, GIF, WEBP';
            } elseif ($_FILES['profile_image']['size'] > 5 * 1024 * 1024) {
                $error = 'ขนาดไฟล์ต้องไม่เกิน 5MB';
            } else {
                $upload_dir = '../uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                // Delete old image if exists
                $old_image_query = $conn->query("SELECT profile_image FROM users WHERE user_id = $user_id");
                $old_image = $old_image_query->fetch_assoc()['profile_image'];
                if ($old_image && file_exists('../' . $old_image)) {
                    unlink('../' . $old_image);
                }

                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
                $upload_path = $upload_dir . $new_filename;
                $db_path = 'uploads/profiles/' . $new_filename;

                if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                    $stmt = $conn->prepare("UPDATE users SET profile_image = ?, updated_at = NOW() WHERE user_id = ?");
                    $stmt->bind_param("si", $db_path, $user_id);

                    if ($stmt->execute()) {
                        $message = 'อัปโหลดรูปโปรไฟล์สำเร็จ';
                        $_SESSION['profile_image'] = $db_path;
                    } else {
                        $error = 'เกิดข้อผิดพลาดในการบันทึก';
                    }
                } else {
                    $error = 'เกิดข้อผิดพลาดในการอัปโหลด';
                }
            }
        } else {
            $error = 'กรุณาเลือกไฟล์รูปภาพ';
        }
    }

    if ($action === 'remove_image') {
        $old_image_query = $conn->query("SELECT profile_image FROM users WHERE user_id = $user_id");
        $old_image = $old_image_query->fetch_assoc()['profile_image'];

        if ($old_image && file_exists('../' . $old_image)) {
            unlink('../' . $old_image);
        }

        $stmt = $conn->prepare("UPDATE users SET profile_image = NULL, updated_at = NOW() WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $message = 'ลบรูปโปรไฟล์สำเร็จ';
            unset($_SESSION['profile_image']);
        } else {
            $error = 'เกิดข้อผิดพลาด';
        }
    }
}

// Get current user data
$stmt = $conn->prepare("SELECT u.*, p.prefix_name, d.department_name
                        FROM users u
                        LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                        LEFT JOIN departments d ON u.department_id = d.department_id
                        WHERE u.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

// Get user info for layout
$user = [
    'username' => $profile['username'],
    'email' => $profile['email'],
    'full_name' => ($profile['prefix_name'] ?? '') . ' ' . $profile['first_name'] . ' ' . $profile['last_name'],
    'first_name' => $profile['first_name']
];

// Get prefixes
$prefixes_result = $conn->query("SELECT prefix_id, prefix_name, prefix_type FROM prefixes WHERE is_active = 1 ORDER BY display_order");
$prefixes = [];
while ($row = $prefixes_result->fetch_assoc()) {
    $prefixes[$row['prefix_type']][] = $row;
}

// Get departments
$departments_result = $conn->query("SELECT department_id, department_name FROM departments WHERE status = 'active' ORDER BY department_name");

// Page config
$page_title = 'โปรไฟล์ของฉัน';
$current_page = 'profile';

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
    .profile-card {
        background: white;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .profile-header {
        background: linear-gradient(135deg, #009933 0%, #007a29 100%);
        padding: 2rem;
        text-align: center;
        color: white;
    }

    .profile-avatar {
        width: 120px;
        height: 120px;
        border-radius: 9999px;
        border: 4px solid white;
        margin: 0 auto 1rem;
        overflow: hidden;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        position: relative;
    }

    .profile-avatar img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .profile-avatar-placeholder {
        font-size: 3rem;
        color: #9ca3af;
    }

    .avatar-overlay {
        position: absolute;
        inset: 0;
        background: rgba(0,0,0,0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.2s;
        cursor: pointer;
    }

    .profile-avatar:hover .avatar-overlay {
        opacity: 1;
    }

    .form-section {
        padding: 1.5rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .form-section:last-child {
        border-bottom: none;
    }

    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-title i {
        color: #009933;
    }

    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    @media (max-width: 640px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
    }

    .form-group {
        margin-bottom: 0;
    }

    .form-group label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        color: #374151;
        font-size: 0.875rem;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 0.625rem 0.875rem;
        border: 1px solid #e5e7eb;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        transition: all 0.15s ease;
    }

    .form-group input:focus,
    .form-group select:focus {
        outline: none;
        border-color: #009933;
        box-shadow: 0 0 0 3px rgba(0, 153, 51, 0.1);
    }

    .form-group input:disabled {
        background-color: #f9fafb;
        color: #6b7280;
    }

    .btn {
        padding: 0.625rem 1.25rem;
        border: none;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.15s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background-color: #009933;
        color: white;
    }

    .btn-primary:hover {
        background-color: #007a29;
    }

    .btn-secondary {
        background-color: #f3f4f6;
        color: #374151;
    }

    .btn-secondary:hover {
        background-color: #e5e7eb;
    }

    .btn-danger {
        background-color: #fef2f2;
        color: #dc2626;
    }

    .btn-danger:hover {
        background-color: #fee2e2;
    }

    .alert {
        padding: 1rem;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
        font-size: 0.875rem;
    }

    .alert-success {
        background-color: #ecfdf5;
        color: #059669;
        border: 1px solid #a7f3d0;
    }

    .alert-error {
        background-color: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    .info-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 0;
        border-bottom: 1px solid #f3f4f6;
    }

    .info-item:last-child {
        border-bottom: none;
    }

    .info-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 0.375rem;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
    }

    .info-label {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .info-value {
        font-size: 0.875rem;
        color: #374151;
        font-weight: 500;
    }

    .col-span-2 {
        grid-column: span 2;
    }
</style>

<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">โปรไฟล์ของฉัน</h1>
        <p class="text-gray-500 text-sm mt-1">จัดการข้อมูลส่วนตัวและการตั้งค่าบัญชี</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Profile Image & Info -->
        <div class="lg:col-span-1">
            <div class="profile-card">
                <div class="profile-header">
                    <form id="imageForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_image">
                        <input type="file" id="profileImageInput" name="profile_image" accept="image/*" class="hidden" onchange="document.getElementById('imageForm').submit()">

                        <div class="profile-avatar" onclick="document.getElementById('profileImageInput').click()">
                            <?php if (!empty($profile['profile_image']) && file_exists('../' . $profile['profile_image'])): ?>
                                <img src="../<?php echo htmlspecialchars($profile['profile_image']); ?>" alt="Profile">
                            <?php else: ?>
                                <i class="fas fa-user profile-avatar-placeholder"></i>
                            <?php endif; ?>
                            <div class="avatar-overlay">
                                <i class="fas fa-camera text-white text-xl"></i>
                            </div>
                        </div>
                    </form>

                    <h2 class="text-xl font-semibold"><?php echo htmlspecialchars($user['full_name']); ?></h2>
                    <p class="text-green-100 text-sm">@<?php echo htmlspecialchars($profile['username']); ?></p>

                    <?php if (!empty($profile['profile_image'])): ?>
                        <form method="POST" class="mt-3">
                            <input type="hidden" name="action" value="remove_image">
                            <button type="submit" class="text-xs text-green-200 hover:text-white underline" onclick="return confirm('ยืนยันลบรูปโปรไฟล์?')">
                                <i class="fas fa-trash mr-1"></i>ลบรูปโปรไฟล์
                            </button>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="p-4">
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-envelope text-sm"></i></div>
                        <div>
                            <div class="info-label">อีเมล</div>
                            <div class="info-value"><?php echo htmlspecialchars($profile['email']); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-phone text-sm"></i></div>
                        <div>
                            <div class="info-label">เบอร์โทร</div>
                            <div class="info-value"><?php echo htmlspecialchars($profile['phone'] ?: '-'); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-building text-sm"></i></div>
                        <div>
                            <div class="info-label">หน่วยงาน</div>
                            <div class="info-value"><?php echo htmlspecialchars($profile['department_name'] ?: '-'); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-briefcase text-sm"></i></div>
                        <div>
                            <div class="info-label">ตำแหน่ง</div>
                            <div class="info-value"><?php echo htmlspecialchars($profile['position'] ?: '-'); ?></div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-shield-alt text-sm"></i></div>
                        <div>
                            <div class="info-label">บทบาท</div>
                            <div class="info-value">
                                <?php
                                $role_text = ['admin' => 'ผู้ดูแลระบบ', 'staff' => 'เจ้าหน้าที่', 'user' => 'ผู้ใช้ทั่วไป'];
                                echo $role_text[$profile['role']] ?? $profile['role'];
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon"><i class="fas fa-calendar text-sm"></i></div>
                        <div>
                            <div class="info-label">สมาชิกเมื่อ</div>
                            <div class="info-value"><?php echo date('d/m/Y', strtotime($profile['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Edit Forms -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Edit Profile Form -->
            <div class="profile-card">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">

                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-user-edit"></i>แก้ไขข้อมูลส่วนตัว</h3>

                        <div class="form-grid">
                            <div class="form-group">
                                <label>ชื่อผู้ใช้</label>
                                <input type="text" value="<?php echo htmlspecialchars($profile['username']); ?>" disabled>
                            </div>

                            <div class="form-group">
                                <label>คำนำหน้า</label>
                                <select name="prefix_id">
                                    <option value="">-- เลือก --</option>
                                    <?php
                                    $prefix_labels = [
                                        'general' => 'คำนำหน้าทั่วไป',
                                        'military_army' => 'ยศทหารบก',
                                        'military_navy' => 'ยศทหารเรือ',
                                        'military_air' => 'ยศทหารอากาศ',
                                        'police' => 'ยศตำรวจ',
                                        'academic' => 'คำนำหน้าทางวิชาการ'
                                    ];
                                    foreach ($prefix_labels as $type => $label):
                                        if (!empty($prefixes[$type])):
                                    ?>
                                    <optgroup label="<?php echo $label; ?>">
                                        <?php foreach ($prefixes[$type] as $prefix): ?>
                                        <option value="<?php echo $prefix['prefix_id']; ?>" <?php echo ($profile['prefix_id'] == $prefix['prefix_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($prefix['prefix_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>ชื่อ <span class="text-red-500">*</span></label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($profile['first_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>นามสกุล <span class="text-red-500">*</span></label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($profile['last_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>อีเมล <span class="text-red-500">*</span></label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>เบอร์โทรศัพท์</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" pattern="[0-9]{10}" placeholder="0891234567">
                            </div>

                            <div class="form-group">
                                <label>หน่วยงาน</label>
                                <select name="department_id">
                                    <option value="">-- ไม่ระบุ --</option>
                                    <?php
                                    $departments_result->data_seek(0);
                                    while ($dept = $departments_result->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $dept['department_id']; ?>" <?php echo ($profile['department_id'] == $dept['department_id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['department_name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label>ตำแหน่ง</label>
                                <input type="text" name="position" value="<?php echo htmlspecialchars($profile['position']); ?>" placeholder="ตำแหน่งงาน">
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>บันทึกข้อมูล
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Change Password Form -->
            <div class="profile-card">
                <form method="POST">
                    <input type="hidden" name="action" value="update_password">

                    <div class="form-section">
                        <h3 class="section-title"><i class="fas fa-lock"></i>เปลี่ยนรหัสผ่าน</h3>

                        <div class="form-grid">
                            <div class="form-group col-span-2">
                                <label>รหัสผ่านปัจจุบัน <span class="text-red-500">*</span></label>
                                <input type="password" name="current_password" required placeholder="กรอกรหัสผ่านปัจจุบัน">
                            </div>

                            <div class="form-group">
                                <label>รหัสผ่านใหม่ <span class="text-red-500">*</span></label>
                                <input type="password" name="new_password" required minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร">
                            </div>

                            <div class="form-group">
                                <label>ยืนยันรหัสผ่านใหม่ <span class="text-red-500">*</span></label>
                                <input type="password" name="confirm_password" required minlength="6" placeholder="กรอกรหัสผ่านอีกครั้ง">
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i>เปลี่ยนรหัสผ่าน
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'admin-layout/footer.php'; ?>
