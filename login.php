<?php
/**
 * Login Page
 * User authentication
 */

session_start();
require_once 'config/database.php';

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    header('Content-Type: application/json');

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
        exit;
    }

    try {
        // Check user credentials (support both username and email)
        $stmt = $conn->prepare("SELECT u.user_id, u.first_name, u.last_name, u.email, u.password, u.role, u.status, p.prefix_name
                                FROM users u
                                LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                                WHERE u.email = ? OR u.username = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
            exit;
        }

        $user = $result->fetch_assoc();

        // Check if account is active
        if ($user['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ']);
            exit;
        }

        // Verify password
        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
            exit;
        }

        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['prefix_name'] = $user['prefix_name'] ?? '';
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['full_name'] = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);

        // Update last login
        $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $update_stmt->bind_param("i", $user['user_id']);
        $update_stmt->execute();

        echo json_encode([
            'success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'redirect' => $user['role'] === 'admin' ? 'admin/index.php' : 'index.php'
        ]);
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
    <title>เข้าสู่ระบบ - ระบบบริการดิจิทัลเทศบาล</title>
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

        .login-container {
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
        }

        .input-group input {
            padding-left: 3rem;
        }

        .btn-login {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            transition: all 0.3s ease;
        }

        .btn-login:hover {
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

        <div class="login-container max-w-md w-full rounded-2xl shadow-2xl p-8 relative z-10">
            <!-- Logo Section -->
            <div class="logo-container text-center mb-8">
                <div class="inline-block mb-4">
                    <img src="images/logo/rangsit-big-logo.png" alt="Logo" class="w-32 h-32 object-contain">
                </div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">ระบบบริการดิจิทัล</h1>
                <p class="text-gray-600">เทศบาลนครรังสิต</p>
                <div class="h-1 w-20 bg-gradient-to-r from-teal-700 to-teal-400 mx-auto mt-3 rounded-full"></div>
            </div>

            <!-- Login Form -->
            <div class="form-container">
                <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">เข้าสู่ระบบ</h2>

                <form id="loginForm" method="POST">
                    <input type="hidden" name="login" value="1">

                    <!-- Username Input -->
                    <div class="input-group mb-4">
                        <i class="fas fa-user"></i>
                        <input type="text"
                               name="username"
                               id="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                               placeholder="ชื่อผู้ใช้"
                               required>
                    </div>

                    <!-- Password Input -->
                    <div class="input-group mb-6">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               name="password"
                               id="password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                               placeholder="รหัสผ่าน"
                               required>
                        <button type="button"
                                onclick="togglePassword()"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-teal-700">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>

                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center text-sm text-gray-600 cursor-pointer">
                            <input type="checkbox" name="remember" class="mr-2 rounded text-teal-600 focus:ring-teal-500">
                            <span>จดจำการเข้าสู่ระบบ</span>
                        </label>
                        <a href="#" class="text-sm text-teal-700 hover:text-teal-900 font-medium">ลืมรหัสผ่าน?</a>
                    </div>

                    <!-- Login Button -->
                    <button type="submit"
                            class="btn-login w-full text-white font-semibold py-3 rounded-lg shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        เข้าสู่ระบบ
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

                <!-- Register Link -->
                <div class="text-center">
                    <p class="text-gray-600">
                        ยังไม่มีบัญชี?
                        <a href="register.php" class="text-teal-700 hover:text-teal-900 font-semibold">
                            สมัครสมาชิก
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
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');

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
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('login.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = result.redirect;
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

        // Enter key to submit
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));
            }
        });
    </script>
</body>
</html>
