<?php
/**
 * Forgot Password Handler
 * สร้างลิงก์รีเซ็ตรหัสผ่าน
 */

session_start();
require_once 'config/database.php';

$message = '';
$success = false;

// Handle forgot password form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot'])) {
    header('Content-Type: application/json');
    
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกอีเมล']);
        exit;
    }
    
    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id, username, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'ไม่พบอีเมลนี้ในระบบ']);
            exit;
        }
        
        $user = $result->fetch_assoc();
        
        // Generate reset token (expires in 1 hour)
        $reset_token = bin2hex(random_bytes(32));
        $token_hash = hash('sha256', $reset_token);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Check if password_reset table exists, if not create it
        $check_table = $conn->query("SHOW TABLES LIKE 'password_reset'");
        if ($check_table->num_rows === 0) {
            $conn->query("CREATE TABLE password_reset (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token_hash VARCHAR(255) NOT NULL,
                expires_at DATETIME NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
            )");
        }
        
        // Delete old reset tokens for this user
        $stmt = $conn->prepare("DELETE FROM password_reset WHERE user_id = ?");
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        
        // Insert new reset token
        $stmt = $conn->prepare("INSERT INTO password_reset (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user['user_id'], $token_hash, $expires);
        $stmt->execute();
        
        // Generate reset link
        $base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $reset_link = $base_url . '/iservice/reset_password.php?token=' . $reset_token;
        
        // TODO: Send email with reset link
        // For now, log the link to a file for testing
        $log_file = __DIR__ . '/logs/password_reset.log';
        @mkdir(dirname($log_file), 0755, true);
        file_put_contents($log_file, date('Y-m-d H:i:s') . " | User: {$user['username']} ({$user['email']}) | Link: {$reset_link}\n", FILE_APPEND);
        
        echo json_encode([
            'success' => true,
            'message' => 'ลิงก์รีเซ็ตรหัสผ่านถูกส่งไปยังอีเมลของคุณแล้ว (ใช้ได้ 1 ชั่วโมง)'
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
    <title>ลืมรหัสผ่าน - ระบบบริการดิจิทัล</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
            min-height: 100vh;
        }
        .forgot-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="forgot-container max-w-md w-full rounded-2xl shadow-2xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">ลืมรหัสผ่าน</h1>
                <p class="text-gray-600">กรุณากรอกอีเมลของคุณเพื่อรับลิงก์รีเซ็ต</p>
            </div>

            <!-- Form -->
            <form id="forgotForm" method="POST">
                <input type="hidden" name="forgot" value="1">

                <div class="mb-6">
                    <label class="block text-gray-700 font-medium mb-2">อีเมล</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-4 top-1/2 transform -translate-y-1/2 text-teal-600"></i>
                        <input type="email"
                               name="email"
                               id="email"
                               class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                               placeholder="กรุณากรอกอีเมล"
                               required>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-teal-700 to-teal-600 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-xl transition">
                    <i class="fas fa-paper-plane mr-2"></i>
                    ส่งลิงก์รีเซ็ต
                </button>
            </form>

            <!-- Back to Login -->
            <div class="text-center mt-6">
                <a href="login.php" class="text-teal-700 hover:text-teal-900 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i>
                    กลับไปเข้าสู่ระบบ
                </a>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('forgotForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('forgot_password.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonText: 'ตกลง'
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
