<?php
/**
 * Register Page
 * New user registration with prefix support
 */

session_start();
require_once 'config/database.php';

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Get all active prefixes grouped by type
$prefixes_query = "SELECT prefix_id, prefix_name, prefix_type FROM prefixes WHERE is_active = 1 ORDER BY display_order";
$prefixes_result = $conn->query($prefixes_query);
$prefixes = [];
while ($row = $prefixes_result->fetch_assoc()) {
    $prefixes[$row['prefix_type']][] = $row;
}

// Handle registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    header('Content-Type: application/json');

    $prefix_id = intval($_POST['prefix_id'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation
    if ($prefix_id <= 0 || empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน']);
        exit;
    }

    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือ _ ความยาว 4-20 ตัวอักษร']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'รูปแบบอีเมลไม่ถูกต้อง']);
        exit;
    }

    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']);
        exit;
    }

    if ($password !== $confirm_password) {
        echo json_encode(['success' => false, 'message' => 'รหัสผ่านไม่ตรงกัน']);
        exit;
    }

    try {
        // Check if username already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้ถูกใช้งานแล้ว']);
            exit;
        }

        // Check if email already exists
        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            echo json_encode(['success' => false, 'message' => 'อีเมลนี้ถูกใช้งานแล้ว']);
            exit;
        }

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
        $stmt = $conn->prepare("INSERT INTO users (prefix_id, username, first_name, last_name, email, phone, password, role, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'user', 'active', NOW())");
        $stmt->bind_param("issssss", $prefix_id, $username, $first_name, $last_name, $email, $phone, $hashed_password);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'สมัครสมาชิกสำเร็จ กรุณาเข้าสู่ระบบ'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการสมัครสมาชิก']);
        }
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก - ระบบบริการดิจิทัลเทศบาล</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
            min-height: 100vh;
        }

        .register-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }

        .input-group {
            position: relative;
        }

        .input-group i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #0f766e;
            z-index: 10;
        }

        .input-group input,
        .input-group select {
            padding-left: 3rem;
        }

        .btn-register {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            transition: all 0.3s ease;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3);
        }

        .logo-container {
            animation: fadeInDown 0.8s ease;
        }

        .form-container {
            animation: fadeInUp 0.8s ease;
        }

        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%230f766e' fill-opacity='0.1' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center px-4 py-12 relative">
        <!-- Background Wave -->
        <div class="wave"></div>

        <div class="register-container max-w-2xl w-full rounded-2xl shadow-2xl p-8 relative z-10">
            <!-- Logo Section -->
            <div class="logo-container text-center mb-8">
                <div class="inline-block mb-4">
                    <img src="images/logo/rangsit-big-logo.png" alt="Logo" class="w-32 h-32 object-contain">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">สมัครสมาชิก</h1>
                <p class="text-gray-600">เทศบาลนครรังสิต</p>
                <div class="h-1 w-20 bg-gradient-to-r from-teal-700 to-teal-400 mx-auto mt-3 rounded-full"></div>
            </div>

            <!-- Register Form -->
            <div class="form-container">
                <form id="registerForm" method="POST">
                    <input type="hidden" name="register" value="1">

                    <!-- Username -->
                    <div class="input-group mb-4">
                        <i class="fas fa-user-circle"></i>
                        <input type="text"
                               name="username"
                               id="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                               placeholder="ชื่อผู้ใช้ (4-20 ตัวอักษร ภาษาอังกฤษ ตัวเลข หรือ _) *"
                               pattern="[a-zA-Z0-9_]{4,20}"
                               minlength="4"
                               maxlength="20"
                               required>
                    </div>

                    <!-- Prefix (คำนำหน้า) -->
                    <div class="input-group mb-4">
                        <i class="fas fa-id-card"></i>
                        <select name="prefix_id"
                                id="prefix_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                                required>
                            <option value="">-- เลือกคำนำหน้า *</option>

                            <?php if (!empty($prefixes['general'])): ?>
                            <optgroup label="คำนำหน้าทั่วไป">
                                <?php foreach ($prefixes['general'] as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>"><?= htmlspecialchars($prefix['prefix_name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($prefixes['military_army'])): ?>
                            <optgroup label="ยศทหารบก">
                                <?php foreach ($prefixes['military_army'] as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>"><?= htmlspecialchars($prefix['prefix_name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($prefixes['military_navy'])): ?>
                            <optgroup label="ยศทหารเรือ">
                                <?php foreach ($prefixes['military_navy'] as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>"><?= htmlspecialchars($prefix['prefix_name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($prefixes['military_air'])): ?>
                            <optgroup label="ยศทหารอากาศ">
                                <?php foreach ($prefixes['military_air'] as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>"><?= htmlspecialchars($prefix['prefix_name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($prefixes['police'])): ?>
                            <optgroup label="ยศตำรวจ">
                                <?php foreach ($prefixes['police'] as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>"><?= htmlspecialchars($prefix['prefix_name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>

                            <?php if (!empty($prefixes['academic'])): ?>
                            <optgroup label="คำนำหน้าทางวิชาการ">
                                <?php foreach ($prefixes['academic'] as $prefix): ?>
                                <option value="<?= $prefix['prefix_id'] ?>"><?= htmlspecialchars($prefix['prefix_name']) ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <!-- First Name -->
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text"
                                   name="first_name"
                                   id="first_name"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                                   placeholder="ชื่อ *"
                                   required>
                        </div>

                        <!-- Last Name -->
                        <div class="input-group">
                            <i class="fas fa-user"></i>
                            <input type="text"
                                   name="last_name"
                                   id="last_name"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                                   placeholder="นามสกุล *"
                                   required>
                        </div>
                    </div>

                    <!-- Email -->
                    <div class="input-group mb-4">
                        <i class="fas fa-envelope"></i>
                        <input type="email"
                               name="email"
                               id="email"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                               placeholder="อีเมล *"
                               required>
                    </div>

                    <!-- Phone -->
                    <div class="input-group mb-4">
                        <i class="fas fa-phone"></i>
                        <input type="tel"
                               name="phone"
                               id="phone"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                               placeholder="เบอร์โทรศัพท์ (ไม่บังคับ)"
                               pattern="[0-9]{10}"
                               maxlength="10">
                    </div>

                    <!-- Password -->
                    <div class="input-group mb-4">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               name="password"
                               id="password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent pr-12"
                               placeholder="รหัสผ่าน (อย่างน้อย 6 ตัวอักษร) *"
                               required
                               minlength="6">
                        <button type="button"
                                onclick="togglePassword('password')"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-teal-700">
                            <i class="fas fa-eye" id="toggleIcon1"></i>
                        </button>
                    </div>

                    <!-- Confirm Password -->
                    <div class="input-group mb-6">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               name="confirm_password"
                               id="confirm_password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent pr-12"
                               placeholder="ยืนยันรหัสผ่าน *"
                               required
                               minlength="6">
                        <button type="button"
                                onclick="togglePassword('confirm_password')"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-teal-700">
                            <i class="fas fa-eye" id="toggleIcon2"></i>
                        </button>
                    </div>

                    <!-- Terms & Conditions -->
                    <div class="mb-6">
                        <label class="flex items-start text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" name="terms" class="mt-1 mr-2 rounded text-teal-600 focus:ring-teal-500" required>
                            <span>ฉันยอมรับ <a href="#" class="text-teal-700 hover:text-teal-900 font-medium">ข้อกำหนดและเงื่อนไข</a> และ <a href="#" class="text-teal-700 hover:text-teal-900 font-medium">นโยบายความเป็นส่วนตัว</a></span>
                        </label>
                    </div>

                    <!-- Register Button -->
                    <button type="submit"
                            class="btn-register w-full text-white font-semibold py-3 rounded-lg shadow-lg">
                        <i class="fas fa-user-plus mr-2"></i>
                        สมัครสมาชิก
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-6">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-300"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white text-gray-500">หรือ</span>
                    </div>
                </div>

                <!-- Login Link -->
                <div class="text-center">
                    <p class="text-gray-600">
                        มีบัญชีอยู่แล้ว?
                        <a href="login.php" class="text-teal-700 hover:text-teal-900 font-semibold">
                            เข้าสู่ระบบ
                        </a>
                    </p>
                </div>

                <!-- Back to Home -->
                <div class="text-center mt-6">
                    <a href="index.php" class="text-gray-500 hover:text-teal-700 transition text-sm">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับหน้าหลัก
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(inputId) {
            const passwordInput = document.getElementById(inputId);
            const toggleIcon = inputId === 'password' ? document.getElementById('toggleIcon1') : document.getElementById('toggleIcon2');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }

        // Handle form submission
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Client-side validation
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                Swal.fire({
                    icon: 'error',
                    title: 'ผิดพลาด!',
                    text: 'รหัสผ่านไม่ตรงกัน'
                });
                return;
            }

            const formData = new FormData(this);

            try {
                const response = await fetch('register.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonText: 'ไปยังหน้าเข้าสู่ระบบ'
                    }).then(() => {
                        window.location.href = 'login.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด!',
                        text: result.message
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
                });
            }
        });
    </script>
</body>
</html>
