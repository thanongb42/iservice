<?php
session_start();

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// If already logged in as admin, redirect to admin panel
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/index.php');
    exit();
}

require_once 'config/database.php';

// Generate CSRF Token
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

$app_name = !empty($system_settings['app_name']) ? $system_settings['app_name'] : 'Admin Login';
$org_name = !empty($system_settings['organization_name']) ? $system_settings['organization_name'] : 'ระบบจัดการสำหรับผู้ดูแล';
$logo_path = !empty($system_settings['logo_image']) && file_exists($system_settings['logo_image']) ? $system_settings['logo_image'] : null;

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Verify CSRF Token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Security validation failed (CSRF)']);
        exit();
    }

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
        sleep(1); // Brute-force delay
        echo json_encode(['success' => false, 'message' => 'ไม่พบบัญชีผู้ใช้นี้ในระบบ']);
        exit();
    }

    $user = $result->fetch_assoc();

    // Check if user is admin
    if ($user['role'] !== 'admin') {
        sleep(1); 
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
    <title><?php echo htmlspecialchars($app_name); ?> - ระบบบริการดิจิทัล</title>
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
            <?php if ($logo_path): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="h-20 w-auto mx-auto mb-4 bg-white rounded-lg p-1">
            <?php else: ?>
                <i class="fas fa-shield-alt shield-icon"></i>
            <?php endif; ?>
            <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($app_name); ?></h1>
            <p class="text-teal-100 text-sm"><?php echo htmlspecialchars($org_name); ?></p>
        </div>
        
        <div class="p-8">
            <div class="text-center mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-2">เข้าสู่ระบบผู้ดูแล</h2>
                <p class="text-gray-500 text-sm">สำหรับผู้ดูแลระบบเท่านั้น</p>
            </div>

            <form id="adminLoginForm" class="space-y-6">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
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
                    <i class="fas fa-eye password-toggle" onclick="togglePassword()"></i>
                </div>

                <button type="submit" class="btn-login">
                    <span id="btnText">เข้าสู่ระบบ</span>
                    <i class="fas fa-spinner fa-spin ml-2 hidden" id="btnSpinner"></i>
                </button>
            </form>

            <div class="text-center mt-6">
                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left mr-2"></i> กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.password-toggle');
            
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

        document.getElementById('adminLoginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const btn = document.querySelector('.btn-login');
            const btnText = document.getElementById('btnText');
            const btnSpinner = document.getElementById('btnSpinner');
            
            // Disable button
            btn.disabled = true;
            btnText.textContent = 'กำลังตรวจสอบ...';
            btnSpinner.classList.remove('hidden');
            
            const formData = new FormData(this);
            
            fetch('admin-login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'เข้าสู่ระบบสำเร็จ',
                        text: 'กำลังพาท่านไปยังหน้าแรก...',
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = data.redirect;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เข้าสู่ระบบไม่สำเร็จ',
                        text: data.message,
                        confirmButtonColor: '#0d9488'
                    });
                    
                    // Reset button
                    btn.disabled = false;
                    btnText.textContent = 'เข้าสู่ระบบ';
                    btnSpinner.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#0d9488'
                });
                
                // Reset button
                btn.disabled = false;
                btnText.textContent = 'เข้าสู่ระบบ';
                btnSpinner.classList.add('hidden');
            });
        });
    </script>
</body>
</html>
