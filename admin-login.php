<?php
session_start();

// Security Headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

// If already logged in as admin, redirect to admin panel
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
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
        exit();on_encode(['success' => false, 'message' => 'รหัสผ่านไม่ถูกต้อง']);
    }   exit();
    }
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['first_name'] = $user['first_name'];
    $_SESSION['last_name'] = $user['last_name'];
    $_SESSION['prefix_name'] = $user['prefix_name'] ?? '';
    $_SESSION['full_name'] = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);
    $_SESSION['email'] = $user['email'];st_name'];
    $_SESSION['role'] = $user['role'];st_name'];
    $_SESSION['prefix_name'] = $user['prefix_name'] ?? '';
    // Update last login'] = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);
    $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $update_stmt->bind_param("i", $user['user_id']);
    $update_stmt->execute();
    // Update last login
    echo json_encode([nn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        'success' => true,am("i", $user['user_id']);
        'message' => 'เข้าสู่ระบบสำเร็จ',
        'redirect' => 'admin/index.php'
    ]);o json_encode([
    exit();ccess' => true,
}       'message' => 'เข้าสู่ระบบสำเร็จ',
?>      'redirect' => 'admin/index.php'
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($app_name); ?> - ระบบบริการดิจิทัล</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>0">
    <style><?php echo htmlspecialchars($app_name); ?> - ระบบบริการดิจิทัล</title>
        body {c="https://cdn.tailwindcss.com"></script>
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);4.0/css/all.min.css">
            min-height: 100vh;sdelivr.net/npm/sweetalert2@11"></script>
            display: flex;
            align-items: center;
            justify-content: center;ent(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
        }   min-height: 100vh;
            display: flex;
        .login-card {ms: center;
            background: white;enter;
            border-radius: 1rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            max-width: 450px;;
            width: 100%;s: 1rem;
            margin: 1rem; 25px 50px -12px rgba(0, 0, 0, 0.25);
        }   overflow: hidden;
            max-width: 450px;
        .gradient-header {
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            padding: 2rem;
            text-align: center;
            color: white;{
        }   background: linear-gradient(135deg, #0f766e 0%, #14b8a6 100%);
            padding: 2rem;
        .input-group {: center;
            position: relative;
            margin-bottom: 1.5rem;
        }
        .input-group {
        .input-group i:not(.password-toggle) {
            position: absolute;em;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);gle) {
            color: #6b7280;ute;
        }   left: 1rem;
            top: 50%;
        .input-group input {lateY(-50%);
            width: 100%;80;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.3s; 0.75rem 3rem;
        }   border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
        .input-group input:focus {
            outline: none;l 0.3s;
            border-color: #14b8a6;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
        }input-group input:focus {
            outline: none;
        .btn-login {olor: #14b8a6;
            background: linear-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;r-gradient(135deg, #14b8a6 0%, #0d9488 100%);
            width: 100%;;
            border: none;rem 1.5rem;
            cursor: pointer;.5rem;
            transition: all 0.3s;
        }   width: 100%;
            border: none;
        .btn-login:hover {r;
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(20, 184, 166, 0.3);
        }btn-login:hover {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        .btn-login:disabled {ateY(-2px);
            opacity: 0.6; 10px 20px rgba(20, 184, 166, 0.3);
            cursor: not-allowed;
            transform: none;
        }btn-login:disabled {
            opacity: 0.6;
        .password-toggle {lowed;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;te;
            color: #6b7280;
        }   top: 50%;
            transform: translateY(-50%);
        .password-toggle:hover {
            color: #14b8a6;
        }

        .back-link {ggle:hover {
            display: inline-flex;
            align-items: center;
            color: #0d9488;
            text-decoration: none;
            font-size: 0.875rem;;
            margin-top: 1rem;er;
            transition: color 0.3s;
        }   text-decoration: none;
            font-size: 0.875rem;
        .back-link:hover {em;
            color: #0f766e;or 0.3s;
        }

        .shield-icon {er {
            font-size: 4rem;
            margin-bottom: 1rem;
            color: #fbbf24;
            text-shadow: 0 0 20px rgba(251, 191, 36, 0.5);
        }   font-size: 4rem;
            margin-bottom: 1rem;
        .info-box {#fbbf24;
            background: #f0fdfa;x rgba(251, 191, 36, 0.5);
            border: 1px solid #14b8a6;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }   border: 1px solid #14b8a6;
            border-radius: 0.5rem;
        .info-box p {1rem;
            color: #0f766e;1.5rem;
            font-size: 0.875rem;
            margin: 0;
        }info-box p {
            color: #0f766e;
        .info-box strong {75rem;
            display: block;
            margin-bottom: 0.5rem;
            color: #0d9488;
        }info-box strong {
    </style>display: block;
</head>     margin-bottom: 0.5rem;
<body>      color: #0d9488;
    <div class="login-card">
        <div class="gradient-header">
            <?php if ($logo_path): ?>
                <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="h-20 w-auto mx-auto mb-4 bg-white rounded-lg p-1">
            <?php else: ?>">
                <i class="fas fa-shield-alt shield-icon"></i>
            <?php endif; ?>_path): ?>
            <h1 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($app_name); ?></h1>-auto mb-4 bg-white rounded-lg p-1">
            <p class="text-teal-100 text-sm"><?php echo htmlspecialchars($org_name); ?></p>
        </div>  <i class="fas fa-shield-alt shield-icon"></i>
            <?php endif; ?>
        <div class="p-8">xt-2xl font-bold mb-2"><?php echo htmlspecialchars($app_name); ?></h1>
            <div class="text-center mb-8">m"><?php echo htmlspecialchars($org_name); ?></p>
                <h2 class="text-xl font-bold text-gray-800 mb-2">เข้าสู่ระบบผู้ดูแล</h2>
                <p class="text-gray-500 mt-2">ผู้ดูแลระบบ</p>
            </div>="p-8">
            <div class="mb-6 text-center">
            <form id="adminLoginForm" class="space-y-6">old text-gray-800 mb-2">เข้าสู่ระบบผู้ดูแล</h2>
                <!-- CSRF Token -->y-600 text-sm">สำหรับผู้ดูแลระบบเท่านั้น</p>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="bg-gray-50 p-1 rounded-lg border border-gray-200 flex items-center">
                    <i class="fas fa-user text-gray-400 mr-3"></i>lass="info-box">
                    <input                <strong><i class="fas fa-info-circle mr-1"></i>ข้อมูลสำหรับทดสอบ:</strong>
                        type="text"strong> admin</p>
                        name="username"ong> admin123</p>
                        id="username"
                        placeholder="ชื่อผู้ใช้หรืออีเมล"
                        requiredm">
                        autocomplete="username"">
                        class="bg-transparent border-0 focus:ring-0 focus:outline-none flex-1"user"></i>
                    >
                </div>xt"

                <div class="bg-gray-50 p-1 rounded-lg border border-gray-200 flex items-center">   id="username"
                    <i class="fas fa-lock text-gray-400 mr-3"></i>  placeholder="ชื่อผู้ใช้หรืออีเมล"
                    <input                        required
                        type="password"rname"
                        name="password"
                        id="password"
                        placeholder="รหัสผ่าน"
                        required">
                        autocomplete="current-password"lock"></i>
                        class="bg-transparent border-0 focus:ring-0 focus:outline-none flex-1"
                    >ssword"
                    <i class="fas fa-eye password-toggle" id="togglePassword"></i>
                </div>   id="password"

                <div class="mb-6">  required
                    <div class="flex items-center justify-between text-sm">                        autocomplete="current-password"
                        <label class="flex items-center text-gray-700 cursor-pointer">
                            <input type="checkbox" class="mr-2 rounded border-gray-300 text-teal-600 focus:ring-teal-500">d"></i>
                            <span>จดจำฉัน</span>
                        </label>
                        <a href="login.php" class="text-teal-600 hover:text-teal-700">Login ผู้ใช้ทั่วไป</a>
                    </div>flex items-center justify-between text-sm">
                </div>
  <input type="checkbox" class="mr-2 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                <button type="submit" class="btn-login" id="loginBtn">      <span>จดจำฉัน</span>
                    <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ                        </label>
                </button>:text-teal-700">Login ผู้ใช้ทั่วไป</a>
            </form>

            <div class="mt-6 text-center">
                <a href="index.php" class="back-link">                <button type="submit" class="btn-login" id="loginBtn">
                    <i class="fas fa-arrow-left mr-2"></i>in-alt mr-2"></i>เข้าสู่ระบบ
                    กลับสู่หน้าหลัก
                </a>
            </div>
        </div>ss="mt-6 text-center">
 href="index.php" class="back-link">
        <div class="bg-gray-50 px-8 py-4 border-t">      <i class="fas fa-arrow-left mr-2"></i>
            <div class="flex items-center justify-between text-xs text-gray-600">                    กลับสู่หน้าหลัก
                <span><i class="fas fa-shield-alt mr-1"></i>ปลอดภัย</span>
                <span><i class="fas fa-lock mr-1"></i>เข้ารหัส</span>
                <span><i class="fas fa-check-circle mr-1"></i>Admin Only</span>
            </div>
        </div>
    </div>lass="flex items-center justify-between text-xs text-gray-600">
  <span><i class="fas fa-shield-alt mr-1"></i>ปลอดภัย</span>
    <script>      <span><i class="fas fa-lock mr-1"></i>เข้ารหัส</span>
        // Toggle password visibility                <span><i class="fas fa-check-circle mr-1"></i>Admin Only</span>
        const togglePassword = document.getElementById('togglePassword');</div>
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function() {
            const type = passwordInput.type === 'password' ? 'text' : 'password';    <script>
            passwordInput.type = type;
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');.getElementById('password');
        });
unction() {
        // Handle form submission const type = passwordInput.type === 'password' ? 'text' : 'password';
        document.getElementById('adminLoginForm').addEventListener('submit', async (e) => {            passwordInput.type = type;
            e.preventDefault();('fa-eye');

            const loginBtn = document.getElementById('loginBtn');
            const formData = new FormData(e.target);

            // Disable button during requestdEventListener('submit', async (e) => {
            loginBtn.disabled = true;            e.preventDefault();
            loginBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังเข้าสู่ระบบ...';
.getElementById('loginBtn');
            try {
                const response = await fetch('admin-login.php', {
                    method: 'POST',sable button during request
                    body: formData
                });i class="fas fa-spinner fa-spin mr-2"></i>กำลังเข้าสู่ระบบ...';

                const data = await response.json();
                const response = await fetch('admin-login.php', {
                if (data.success) {
                    Swal.fire({                    body: formData
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: data.message,nse.json();
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        window.location.href = data.redirect;ess',
                    });ร็จ!',
                } else {
                    Swal.fire({ showConfirmButton: false,
                        icon: 'error',timer: 1500
                        title: 'ผิดพลาด',=> {
                        text: data.message,n.href = data.redirect;
                        confirmButtonColor: '#14b8a6'
                    });

                    // Re-enable button icon: 'error',
                    loginBtn.disabled = false;                        title: 'ผิดพลาด',
                    loginBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ';age,
                }14b8a6'
            } catch (error) {
                console.error('Error:', error);
                Swal.fire({ble button
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',n.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ';
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#14b8a6'
                });

                // Re-enable button icon: 'error',
                loginBtn.disabled = false;                    title: 'เกิดข้อผิดพลาด',
                loginBtn.innerHTML = '<i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ';ถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
            }14b8a6'
        });

        // Auto-focus username field     // Re-enable button
        document.getElementById('username').focus();                loginBtn.disabled = false;
 '<i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ';
        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {        });
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>event form resubmission on page refresh
    </script>
</body>
</html>
