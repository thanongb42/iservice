<?php
session_start();
require_once '../../config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

header('Content-Type: application/json');

// Get action
$action = $_REQUEST['action'] ?? '';

try {
    switch ($action) {
        case 'get':
            getUser($conn);
            break;

        case 'add':
            addUser($conn);
            break;

        case 'edit':
            editUser($conn);
            break;

        case 'delete':
            deleteUser($conn);
            break;

        case 'check_username':
            checkUsername($conn);
            break;

        case 'check_email':
            checkEmail($conn);
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

function getUser($conn) {
    $id = intval($_GET['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    $stmt = $conn->prepare("SELECT * FROM v_users_full WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
}

function addUser($conn) {
    // Validate required fields
    $required_fields = ['username', 'email', 'prefix_id', 'first_name', 'last_name', 'password', 'role', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "กรุณากรอก {$field}"]);
            return;
        }
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $prefix_id = intval($_POST['prefix_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'];
    $role = $_POST['role'];
    $status = $_POST['status'];
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $position = trim($_POST['position'] ?? '');

    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือ _ ความยาว 4-20 ตัวอักษร']);
        return;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'รูปแบบ Email ไม่ถูกต้อง']);
        return;
    }

    // Validate password length
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']);
        return;
    }

    // Check username uniqueness
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว']);
        return;
    }

    // Check email uniqueness
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email นี้มีในระบบแล้ว']);
        return;
    }

    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Insert user
    $stmt = $conn->prepare("INSERT INTO users
        (prefix_id, username, first_name, last_name, email, phone, password, role, status, department_id, position, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");

    $stmt->bind_param("issssssssss",
        $prefix_id, $username, $first_name, $last_name, $email,
        $phone, $hashed_password, $role, $status, $department_id, $position
    );

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'เพิ่มผู้ใช้เรียบร้อยแล้ว',
            'user_id' => $conn->insert_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถเพิ่มผู้ใช้ได้: ' . $stmt->error]);
    }
}

function editUser($conn) {
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    // Validate required fields
    $required_fields = ['username', 'email', 'prefix_id', 'first_name', 'last_name', 'role', 'status'];
    foreach ($required_fields as $field) {
        if (empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => "กรุณากรอก {$field}"]);
            return;
        }
    }

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $prefix_id = intval($_POST['prefix_id']);
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'];
    $status = $_POST['status'];
    $department_id = !empty($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $position = trim($_POST['position'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validate username format
    if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $username)) {
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษ ตัวเลข หรือ _ ความยาว 4-20 ตัวอักษร']);
        return;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'รูปแบบ Email ไม่ถูกต้อง']);
        return;
    }

    // Check username uniqueness (excluding current user)
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว']);
        return;
    }

    // Check email uniqueness (excluding current user)
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email นี้มีในระบบแล้ว']);
        return;
    }

    // Prevent deleting the last admin
    if ($role !== 'admin') {
        $stmt = $conn->prepare("SELECT COUNT(*) as admin_count FROM users WHERE role = 'admin' AND user_id != ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $current_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
        $current_stmt->bind_param("i", $user_id);
        $current_stmt->execute();
        $current_role = $current_stmt->get_result()->fetch_assoc()['role'];

        if ($current_role === 'admin' && $result['admin_count'] === 0) {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถเปลี่ยนบทบาทของ Admin คนสุดท้ายได้']);
            return;
        }
    }

    // Build update query
    if (!empty($password)) {
        // Validate password length
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร']);
            return;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET
            prefix_id = ?, username = ?, first_name = ?, last_name = ?,
            email = ?, phone = ?, password = ?, role = ?, status = ?,
            department_id = ?, position = ?, updated_at = NOW()
            WHERE user_id = ?");
        $stmt->bind_param("issssssssssi",
            $prefix_id, $username, $first_name, $last_name, $email,
            $phone, $hashed_password, $role, $status, $department_id, $position, $user_id
        );
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET
            prefix_id = ?, username = ?, first_name = ?, last_name = ?,
            email = ?, phone = ?, role = ?, status = ?,
            department_id = ?, position = ?, updated_at = NOW()
            WHERE user_id = ?");
        $stmt->bind_param("isssssssssi",
            $prefix_id, $username, $first_name, $last_name, $email,
            $phone, $role, $status, $department_id, $position, $user_id
        );
    }

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'อัปเดตข้อมูลเรียบร้อยแล้ว'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตข้อมูลได้: ' . $stmt->error]);
    }
}

function deleteUser($conn) {
    $id = intval($_POST['id'] ?? 0);

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
        return;
    }

    // Prevent deleting yourself
    if ($id == $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบบัญชีของตัวเองได้']);
        return;
    }

    // Check if this is the last admin (trigger will handle this too, but good to check first)
    $stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if ($user['role'] === 'admin') {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['count'] <= 1) {
            echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบ Admin คนสุดท้ายได้']);
            return;
        }
    }

    // Delete user
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'ลบผู้ใช้เรียบร้อยแล้ว']);
        } else {
            echo json_encode(['success' => false, 'message' => 'ไม่พบผู้ใช้ที่ต้องการลบ']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถลบผู้ใช้ได้: ' . $stmt->error]);
    }
}

function checkUsername($conn) {
    $username = trim($_GET['username'] ?? '');
    $user_id = intval($_GET['user_id'] ?? 0);

    if (empty($username)) {
        echo json_encode(['available' => false, 'message' => 'กรุณาระบุชื่อผู้ใช้']);
        return;
    }

    if ($user_id > 0) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
        $stmt->bind_param("si", $username, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['available' => false, 'message' => 'ชื่อผู้ใช้นี้มีในระบบแล้ว']);
    } else {
        echo json_encode(['available' => true, 'message' => 'ชื่อผู้ใช้นี้ว่าง']);
    }
}

function checkEmail($conn) {
    $email = trim($_GET['email'] ?? '');
    $user_id = intval($_GET['user_id'] ?? 0);

    if (empty($email)) {
        echo json_encode(['available' => false, 'message' => 'กรุณาระบุ Email']);
        return;
    }

    if ($user_id > 0) {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $stmt->bind_param("si", $email, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo json_encode(['available' => false, 'message' => 'Email นี้มีในระบบแล้ว']);
    } else {
        echo json_encode(['available' => true, 'message' => 'Email นี้ว่าง']);
    }
}
?>
