<?php
/**
 * Reset Password Page
 * ให้ผู้ใช้ตั้งรหัสผ่านใหม่
 */

session_start();
require_once 'config/database.php';

$token = $_GET['token'] ?? '';
$error = '';
$token_valid = false;
$user_id = null;

// Verify token
if (!empty($token)) {
    try {
        $token_hash = hash('sha256', $token);
        
        // Check if password_reset table exists
        $check_table = $conn->query("SHOW TABLES LIKE 'password_reset'");
        if ($check_table->num_rows > 0) {
            $stmt = $conn->prepare("SELECT user_id FROM password_reset WHERE token_hash = ? AND expires_at > NOW()");
            $stmt->bind_param("s", $token_hash);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $user_id = $row['user_id'];
                $token_valid = true;
            } else {
                $error = 'ลิงก์รีเซ็ตหมดอายุหรือไม่ถูกต้อง';
            }
        } else {
            $error = 'ลิงก์รีเซ็ตไม่ถูกต้อง';
        }
    } catch (Exception $e) {
        $error = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
    }
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset']) && $token_valid) {
    header('Content-Type: application/json');
    
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    
    if (empty($password) || empty($password_confirm)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกรหัสผ่าน']);
        exit;
    }
    
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร']);
        exit;
    }
    
    if ($password !== $password_confirm) {
        echo json_encode(['success' => false, 'message' => 'รหัสผ่านไม่ตรงกัน']);
        exit;
    }
    
    try {
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Update password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $password_hash, $user_id);
        $stmt->execute();
        
        // Delete reset token
        $stmt = $conn->prepare("DELETE FROM password_reset WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'รีเซ็ตรหัสผ่านสำเร็จ'
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
    <title>รีเซ็ตรหัสผ่าน - ระบบบริการดิจิทัล</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            background: linear-gradient(135deg, #0f766e 0%, #14b8a6 50%, #2dd4bf 100%);
            min-height: 100vh;
        }
        .reset-container {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body>
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="reset-container max-w-md w-full rounded-2xl shadow-2xl p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">รีเซ็ตรหัสผ่าน</h1>
            </div>

            <!-- Error Message -->
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
                <div class="text-center">
                    <a href="login.php" class="text-teal-700 hover:text-teal-900 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับไปเข้าสู่ระบบ
                    </a>
                </div>
            <?php elseif ($token_valid): ?>
                <!-- Form -->
                <form id="resetForm" method="POST">
                    <input type="hidden" name="reset" value="1">

                    <!-- New Password -->
                    <div class="mb-4">
                        <label class="block text-gray-700 font-medium mb-2">รหัสผ่านใหม่</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-teal-600"></i>
                            <input type="password"
                                   name="password"
                                   id="password"
                                   class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                                   placeholder="รหัสผ่านใหม่"
                                   required>
                        </div>
                        <small class="text-gray-500 block mt-1">อย่างน้อย 6 ตัวอักษร</small>
                    </div>

                    <!-- Confirm Password -->
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">ยืนยันรหัสผ่าน</label>
                        <div class="relative">
                            <i class="fas fa-lock absolute left-4 top-1/2 transform -translate-y-1/2 text-teal-600"></i>
                            <input type="password"
                                   name="password_confirm"
                                   id="password_confirm"
                                   class="w-full px-4 py-3 pl-12 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-600 focus:border-transparent"
                                   placeholder="ยืนยันรหัสผ่าน"
                                   required>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit"
                            class="w-full bg-gradient-to-r from-teal-700 to-teal-600 text-white font-semibold py-3 rounded-lg shadow-lg hover:shadow-xl transition">
                        <i class="fas fa-check mr-2"></i>
                        รีเซ็ตรหัสผ่าน
                    </button>
                </form>
            <?php else: ?>
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg">
                    <i class="fas fa-warning mr-2"></i>
                    ไม่พบลิงก์รีเซ็ตรหัสผ่าน
                </div>
                <div class="text-center mt-6">
                    <a href="login.php" class="text-teal-700 hover:text-teal-900 font-medium">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับไปเข้าสู่ระบบ
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($token_valid): ?>
    <script>
        document.getElementById('resetForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('reset_password.php?token=<?php echo urlencode($token); ?>', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonText: 'เข้าสู่ระบบ'
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
    <?php endif; ?>
</body>
</html>
