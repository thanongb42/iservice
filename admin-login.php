<?php
session_start();

// If already logged in as admin, redirect to admin panel
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/index.php');
    exit();
}

require_once 'config/database.php';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
        exit();
    }

    // Get user data (username or email)
    $stmt = $conn->prepare("SELECT u.user_id, u.username, p.prefix_name, u.first_name, u.last_name,
                            u.email, u.password, u.role, u.status
                            FROM users u
                            LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                            WHERE u.username = ? OR u.email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้นี้ในระบบ']);
        exit();
    }

    $user = $result->fetch_assoc();

    // Check if user is admin
    if ($user['role'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์เข้าถึงระบบผู้ดูแล']);
        exit();
    }

    // Check if account is active
    if ($user['status'] !== 'active') {
        echo json_encode(['success' => false, 'message' => 'บัญชีของคุณถูกระงับการใช้งาน']);
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        echo json_encode(['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง']);
        exit();
    }

    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['prefix_name'] = $user['prefix_name'] ?? '';
    $_SESSION['full_name'] = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];

    // Update last login
    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $update_stmt->bind_param("i", $user['user_id']);
    $update_stmt->execute();

    echo json_encode([
        'success' => true,
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'redirect' => 'admin/index.php'
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - ระบบบริการดิจิทัล</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            background: white;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
            margin: 1rem;
        }

        .gradient-header {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            padding: 2rem;
            text-align: center;
            color: white;
        }

        .input-group {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .input-group i:not(.password-toggle) {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }

        .input-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #14b8a6;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
        }

        .btn-login {
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            width: 100%;
            border: none;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(20, 184, 166, 0.3);
        }

        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6b7280;
        }

        .password-toggle:hover {
            color: #14b8a6;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            color: #0d9488;
            text-decoration: none;
            font-size: 0.875rem;
            margin-top: 1rem;
            transition: color 0.3s;
        }

        .back-link:hover {
            color: #0f766e;
        }

        .shield-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #fbbf24;
            text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
        }

        .info-box {
            background: #f0fdfa;
            border: 1px solid #14b8a6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }

        .info-box p {
            color: #0f766e;
            font-size: 0.875rem;
            margin: 0;
        }

        .info-box strong {
            display: block;
            margin-bottom: 0.5rem;
            color: #0d9488;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="gradient-header">
            <i class="fas fa-shield-alt shield-icon"></i>
            <h1 class="text-2xl font-bold mb-2">Admin Login</h1>
            <p class="text-teal-100 text-sm">ระบบจัดการสำหรับผู้ดูแล</p>
        </div>

        <div class="p-8">
            <div class="mb-6 text-center">
                <h2 class="text-xl font-bold text-gray-800 mb-2">เข้าสู่ระบบผู้ดูแล</h2>
                <p class="text-gray-600 text-sm">สำหรับผู้ดูแลระบบเท่านั้น</p>
            </div>

            <!-- Info Box for Testing -->
            <div class="info-box">
                <strong><i class="fas fa-info-circle mr-1"></i>ข้อมูลสำหรับทดสอบ:</strong>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
            </div>

            <form id="adminLoginForm">
                <div class="input-group">
                    <i class="fas fa-user"></i>
                    <input
                        type="text"
                        name="username"
                        id="username"
                        placeholder="ชื่อผู้ใช้หรืออีเมล"
                        required
                        autocomplete="username"
                    >
                </div>

                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        placeholder="รหัสผ่าน"
                        required
                        autocomplete="current-password"
                    >
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>

                <div class="mb-6">
                    <div class="flex items-center justify-between text-sm">
                        <label class="flex items-center text-gray-700 cursor-pointer">
                            <input type="checkbox" class="mr-2 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span>จดจำฉัน</span>
                        </label>
                        <a href="login.php" class="text-teal-600 hover:text-teal-700">Login ผู้ใช้ทั่วไป</a>
                    </div>
                </div>

                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
                </button>
            </form>

            <div class="mt-6 text-center">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับสู่หน้าหลัก
                </a>
            </div>
        </div>

        <div class="bg-gray-50 px-8 py-4 border-t">
            <div class="flex items-center justify-between text-xs text-gray-600">
                <span><i class="fas fa-shield-alt mr-1"></i>ปลอดภัย</span>
                <span><i class="fas fa-lock mr-1"></i>เข้ารหัส</span>
                <span><i class="fas fa-check-circle mr-1"></i>Admin Only</span>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Handle form submission
        document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {
            e.preventDefault();

            const loginBtn = document.getElementById('loginBtn');
            const formData = new FormData(e.target);

            // Disable button during request
            loginBtn.disabled = true;
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังเข้าสู่ระบบ...';

            try {
                const response = await fetch('admin-login.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'ผิดพลาด',
                        text: data.message,
                        confirmButtonColor: '#14b8a6'
                    });

                    // Re-enable button
                    loginBtn.disabled = false;
                    loginBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ';
                }
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#14b8a6'
                });

                // Re-enable button
                loginBtn.disabled = false;
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ';
            }
        });

        // Auto-focus username field
        document.getElementById('username').focus();

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>
