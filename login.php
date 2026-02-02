<?php
/**
 * Login Page
 * User authentication
 */

session_start();

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

require_once 'config/database.php';

// Generate CSRF Token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch system settings
$system_settings = [];
if (isset($conn)) {
    $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($settings_query) {
        while ($row = $settings_query->fetch_assoc()) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

$app_name = !empty($system_settings['app_name']) ? $system_settings['app_name'] : 'ระบบบริการดิจิทัล';
$org_name = !empty($system_settings['organization_name']) ? $system_settings['organization_name'] : 'เทศบาลนครรังสิต';
$logo_path = !empty($system_settings['logo_image']) && file_exists($system_settings['logo_image']) ? $system_settings['logo_image'] : 'images/logo/rangsit-big-logo.png';

// If already logged in, redirect to index
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Handle login submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    header('Content-Type: application/json');

    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Security validation failed (CSRF)']);
        exit;
    }

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
            sleep(1); // Brute-force delay
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
        // Verify passwordfy($password, $user['password'])) {
        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
            exit;
        }

        // Set session variables to prevent fixation
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['prefix_name'] = $user['prefix_name'] ?? '';
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['email'] = $user['email'];d'];
        $_SESSION['role'] = $user['role'];prefix_name'] ?? '';
        $_SESSION['full_name'] = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);
        $_SESSION['last_name'] = $user['last_name'];
        // Update last login $user['email'];
        $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $update_stmt->bind_param("i", $user['user_id']);e'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);
        $update_stmt->execute();
        // Update last login
        echo json_encode([nn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            'success' => true,am("i", $user['user_id']);
            'message' => 'เข้าสู่ระบบสำเร็จ',
            'redirect' => $user['role'] === 'admin' ? 'admin/index.php' : 'index.php'
        ]);o json_encode([
        exit;success' => true,
            'message' => 'เข้าสู่ระบบสำเร็จ',
    } catch (Exception $e) {ser['role'] === 'admin' ? 'admin/index.php' : 'index.php'
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        exit;
    }
}   } catch (Exception $e) {
?>      echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        exit;
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
    <style> src="https://cdn.tailwindcss.com"></script>
        body {"stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
            font-family: 'Sarabun', sans-serif;s2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
            min-height: 100vh;
        }ody {
            font-family: 'Sarabun', sans-serif;
        .login-container {near-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .login-container {
        .input-group {ilter: blur(10px);
            position: relative;5, 255, 255, 0.95);
        }

        .input-group > i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #0f766e;ute;
            z-index: 10;
        }   top: 50%;
            transform: translateY(-50%);
        .input-group input {
            padding-left: 3rem;
        }

        .btn-login { input {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            transition: all 0.3s ease;
        }
        .btn-login {
        .btn-login:hover {near-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(15, 118, 110, 0.3);
        }
        .btn-login:hover {
        .logo-container {anslateY(-2px);
            animation: fadeInDown 0.8s ease; 118, 110, 0.3);
        }

        .form-container {
            animation: fadeInUp 0.8s ease;e;
        }

        @keyframes fadeInDown {
            from {ion: fadeInUp 0.8s ease;
                opacity: 0;
                transform: translateY(-20px);
            }rames fadeInDown {
            to { {
                opacity: 1;
                transform: translateY(0);px);
            }
        }   to {
                opacity: 1;
        @keyframes fadeInUp {anslateY(0);
            from {
                opacity: 0;
                transform: translateY(20px);
            }rames fadeInUp {
            to { {
                opacity: 1;
                transform: translateY(0);x);
            }
        }   to {
                opacity: 1;
        .wave { transform: translateY(0);
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;lute;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%230f766e' fill-opacity='0.1' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
        }   width: 100%;
    </style>height: 100px;
</head>     background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1440 320'%3E%3Cpath fill='%230f766e' fill-opacity='0.1' d='M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z'%3E%3C/path%3E%3C/svg%3E");
<body>      background-size: cover;
    <div class="min-h-screen flex items-center justify-center px-4 py-12 relative">
        <!-- Background Wave -->
        <div class="wave"></div>
<body>
        <div class="login-container max-w-md w-full rounded-2xl shadow-2xl p-8 relative z-10">
            <!-- Logo Section -->
            <div class="logo-container text-center mb-8">
                <div class="inline-block mb-4">
                    <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="Logo" class="w-32 h-32 object-contain">
                </div>Section -->
                <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($app_name); ?></h1>
                <p class="text-gray-600"><?php echo htmlspecialchars($org_name); ?></p>
                <div class="h-1 w-20 bg-gradient-to-r from-teal-700 to-teal-400 mx-auto mt-3 rounded-full"></div>tain">
            </div>div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($app_name); ?></h1>
            <form id="loginForm" class="space-y-6">gray-600"><?php echo htmlspecialchars($org_name); ?></p>
                <!-- CSRF Token -->gradient-to-r from-teal-700 to-teal-400 mx-auto mt-3 rounded-full"></div>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <input type="hidden" name="login" value="1">
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">ชื่อผู้ใช้</label>                <h2 class="text-2xl font-semibold text-gray-800 mb-6 text-center">เข้าสู่ระบบ</h2>
                    <div class="input-group">
                        <i class="fas fa-user"></i>>
                        <input type="text"n" value="1">
                               name="username"
                               id="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"mb-4">
                               placeholder="ชื่อผู้ใช้"
                               required>
                    </div>rname"
                </div>     id="username"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                <div>"ชื่อผู้ใช้"
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">รหัสผ่าน</label>
                    <div class="input-group">
                        <i class="fas fa-lock"></i>
                        <input type="password"
                               name="password"mb-6">
                               id="password"
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                               placeholder="รหัสผ่าน"sword"
                               required>
                        <button type="button"der border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                                onclick="togglePassword()"
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-teal-700">
                            <i class="fas fa-eye" id="toggleIcon"></i>ype="button"
                        </button>      onclick="togglePassword()"
                    </div>                                class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-teal-700">
                </div>leIcon"></i>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between mb-6">
                    <label class="flex items-center text-sm text-gray-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="mr-2 rounded text-teal-600 focus:ring-teal-500">flex items-center justify-between mb-6">
                        <span>จดจำการเข้าสู่ระบบ</span>
                    </label>  <input type="checkbox" name="remember" class="mr-2 rounded text-teal-600 focus:ring-teal-500">
                    <a href="forgot_password.php" class="text-sm text-teal-700 hover:text-teal-900 font-medium">ลืมรหัสผ่าน?</a>                            <span>จดจำการเข้าสู่ระบบ</span>
                </div>
assword.php" class="text-sm text-teal-700 hover:text-teal-900 font-medium">ลืมรหัสผ่าน?</a>
                <!-- Login Button -->
                <button type="submit"
                        class="btn-login w-full text-white font-semibold py-3 rounded-lg shadow-lg">on -->
                    <i class="fas fa-sign-in-alt mr-2"></i>ype="submit"
                    เข้าสู่ระบบ     class="btn-login w-full text-white font-semibold py-3 rounded-lg shadow-lg">
                </button>                        <i class="fas fa-sign-in-alt mr-2"></i>
            </form>ะบบ

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">er -->
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">iv class="w-full border-t border-gray-300"></div>
                    <span class="px-4 bg-white text-gray-500">หรือ</span>div>
                </div>                    <div class="relative flex justify-center text-sm">
            </div>x-4 bg-white text-gray-500">หรือ</span>

            <!-- Register Link -->
            <div class="text-center">
                <p class="text-gray-600">
                    ยังไม่มีบัญชี?">
                    <a href="register.php" class="text-teal-700 hover:text-teal-900 font-semibold">="text-gray-600">
                        สมัครสมาชิกยังไม่มีบัญชี?
                    </a>  <a href="register.php" class="text-teal-700 hover:text-teal-900 font-semibold">
                </p>                            สมัครสมาชิก
            </div>

            <!-- Back to Home -->
            <div class="text-center mt-6">
                <a href="index.php" class="text-gray-500 hover:text-teal-700 transition text-sm">>
                    <i class="fas fa-arrow-left mr-2"></i>ss="text-center mt-6">
                    กลับหน้าหลัก href="index.php" class="text-gray-500 hover:text-teal-700 transition text-sm">
                </a>      <i class="fas fa-arrow-left mr-2"></i>
            </div>          กลับหน้าหลัก
        </div>          </a>
    </div>                </div>
</div>
    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');        // Toggle password visibility

            if (passwordInput.type === 'password') {etElementById('password');
                passwordInput.type = 'text';('toggleIcon');
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');wordInput.type === 'password') {
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');ash');
                toggleIcon.classList.add('fa-eye'); else {
            }       passwordInput.type = 'password';
        }                toggleIcon.classList.remove('fa-eye-slash');
ist.add('fa-eye');
        // Handle form submission
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);        document.getElementById('loginForm').addEventListener('submit', async function(e) {
ventDefault();
            try {
                const response = await fetch('login.php', {rmData(this);
                    method: 'POST',
                    body: formData
                });                const response = await fetch('login.php', {

                const result = await response.json();                    body: formData

                if (result.success) {
                    Swal.fire({ponse.json();
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        showConfirmButton: false,ess',
                        timer: 1500ร็จ!',
                    }).then(() => {
                        window.location.href = result.redirect; showConfirmButton: false,
                    });timer: 1500
                } else {=> {
                    Swal.fire({n.href = result.redirect;
                        icon: 'error',
                        title: 'ผิดพลาด!',
                        text: result.messagel.fire({
                    });       icon: 'error',
                }: 'ผิดพลาด!',
            } catch (error) {t: result.message
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด!',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'l.fire({
                });       icon: 'error',
            }         title: 'เกิดข้อผิดพลาด!',
        });                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'

        // Enter key to submit
        document.getElementById('password').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('loginForm').dispatchEvent(new Event('submit'));ter key to submit
            }ument.getElementById('password').addEventListener('keypress', function(e) {
        });f (e.key === 'Enter') {
    </script>         document.getElementById('loginForm').dispatchEvent(new Event('submit'));
</body>     }
</html>        });

    </script>
</body>
</html>
