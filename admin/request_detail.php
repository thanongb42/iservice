<?php
/**
 * Service Request Detail Page
 * Allows superadmin to view, edit, delete, approve, and reject requests
 */

require_once '../config/database.php';
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (empty($request_id)) {
    header('Location: service_requests.php');
    exit;
}

// Get request detail
$stmt = $conn->prepare("SELECT * FROM service_requests WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();

if (!$request) {
    header('Location: service_requests.php?error=notfound');
    exit;
}

$page_title = 'รายละเอียดคำขอบริการ - ' . htmlspecialchars($request['request_code']);
$current_page = 'service_requests';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'url' => 'index.php', 'icon' => 'fa-home'],
    ['label' => 'จัดการคำขอบริการ', 'url' => 'service_requests.php'],
    ['label' => htmlspecialchars($request['request_code'])]
];

// Get service-specific details if exists
$service_details = [];
switch ($request['service_code']) {
    case 'EMAIL':
        $detail_stmt = $conn->prepare("SELECT * FROM request_email_details WHERE request_id = ?");
        $detail_stmt->bind_param("i", $request_id);
        $detail_stmt->execute();
        $service_details = $detail_stmt->get_result()->fetch_assoc() ?: [];
        break;
    
    case 'NAS':
        $detail_stmt = $conn->prepare("SELECT * FROM request_nas_details WHERE request_id = ?");
        $detail_stmt->bind_param("i", $request_id);
        $detail_stmt->execute();
        $service_details = $detail_stmt->get_result()->fetch_assoc() ?: [];
        break;
    
    case 'IT_SUPPORT':
        $detail_stmt = $conn->prepare("SELECT * FROM request_it_support_details WHERE request_id = ?");
        $detail_stmt->bind_param("i", $request_id);
        $detail_stmt->execute();
        $service_details = $detail_stmt->get_result()->fetch_assoc() ?: [];
        break;
}

// Get users for assignment
$users_query = "SELECT user_id, first_name, last_name FROM users WHERE role IN ('admin', 'staff') ORDER BY first_name";
$users_result = $conn->query($users_query);
$users = [];
while ($row = $users_result->fetch_assoc()) {
    $users[] = $row;
}

// Handle form submissions
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update request
    $requester_name = clean_input($_POST['requester_name']);
    $requester_email = clean_input($_POST['requester_email']);
    $requester_phone = clean_input($_POST['requester_phone']);
    $department_id = intval($_POST['department_id']);
    $priority = clean_input($_POST['priority']);
    $status = clean_input($_POST['status']);
    $admin_notes = clean_input($_POST['admin_notes']);
    $description = clean_input($_POST['description']);
    
    $update_stmt = $conn->prepare("
        UPDATE service_requests 
        SET requester_name = ?, 
            requester_email = ?, 
            requester_phone = ?,
            department_id = ?,
            priority = ?,
            status = ?,
            admin_notes = ?,
            description = ?
        WHERE request_id = ?
    ");
    
    $update_stmt->bind_param("sssisissi", 
        $requester_name, $requester_email, $requester_phone, 
        $department_id, $priority, $status, $admin_notes, $description, $request_id
    );
    
    if ($update_stmt->execute()) {
        $_SESSION['success_msg'] = 'อัปเดตข้อมูลสำเร็จ';
        // Refresh request data
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
    } else {
        $_SESSION['error_msg'] = 'ไม่สามารถอัปเดตข้อมูลได้: ' . $conn->error;
    }
}

if ($action === 'approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Approve request
    $assigned_to = intval($_POST['assigned_to']);
    
    $approve_stmt = $conn->prepare("
        UPDATE service_requests 
        SET status = 'in_progress', assigned_to = ?
        WHERE request_id = ?
    ");
    
    $approve_stmt->bind_param("ii", $assigned_to, $request_id);
    
    if ($approve_stmt->execute()) {
        $_SESSION['success_msg'] = 'อนุมัติคำขอสำเร็จ';
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
    }
}

if ($action === 'reject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reject request
    $rejection_reason = clean_input($_POST['rejection_reason']);
    
    $reject_stmt = $conn->prepare("
        UPDATE service_requests 
        SET status = 'rejected', rejection_reason = ?
        WHERE request_id = ?
    ");
    
    $reject_stmt->bind_param("si", $rejection_reason, $request_id);
    
    if ($reject_stmt->execute()) {
        $_SESSION['success_msg'] = 'ปฏิเสธคำขอสำเร็จ';
        $stmt->execute();
        $request = $stmt->get_result()->fetch_assoc();
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete request
    $delete_stmt = $conn->prepare("DELETE FROM service_requests WHERE request_id = ?");
    $delete_stmt->bind_param("i", $request_id);
    
    if ($delete_stmt->execute()) {
        header('Location: service_requests.php?success=deleted');
        exit;
    } else {
        $_SESSION['error_msg'] = 'ไม่สามารถลบข้อมูลได้';
    }
}

// Get departments for select
$dept_query = "SELECT department_id, department_name FROM departments WHERE status = 'active' ORDER BY department_name";
$dept_result = $conn->query($dept_query);
$departments = [];
while ($row = $dept_result->fetch_assoc()) {
    $departments[] = $row;
}

// Get assigned staff
$assigned_staff = null;
if ($request['assigned_to']) {
    $staff_stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $staff_stmt->bind_param("i", $request['assigned_to']);
    $staff_stmt->execute();
    $assigned_staff = $staff_stmt->get_result()->fetch_assoc();
}

include '../admin-layout/header.php';
include '../admin-layout/sidebar.php';
?>

<main class="ml-64 bg-gray-50 min-h-screen">
    <div class="p-8">
        <!-- Breadcrumb -->
        <nav class="text-gray-600 text-sm mb-6">
            <a href="index.php" class="hover:text-teal-600"><i class="fas fa-home mr-1"></i>หน้าหลัก</a>
            <span class="mx-2">/</span>
            <a href="service_requests.php" class="hover:text-teal-600">จัดการคำขอบริการ</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-bold"><?= htmlspecialchars($request['request_code']) ?></span>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($request['request_code']) ?></h1>
                    <p class="text-gray-600">
                        <i class="fas fa-service mr-2"></i>
                        <?= htmlspecialchars($request['service_name']) ?>
                    </p>
                </div>
                
                <div class="flex items-center gap-2">
                    <!-- Status Badge -->
                    <span class="px-4 py-2 rounded-full font-bold text-white
                        <?php 
                        echo match($request['status']) {
                            'pending' => 'bg-yellow-500',
                            'in_progress' => 'bg-blue-500',
                            'completed' => 'bg-green-500',
                            'rejected' => 'bg-red-500',
                            'cancelled' => 'bg-gray-500',
                            default => 'bg-gray-500'
                        };
                        ?>
                    ">
                        <?php
                        $status_labels = [
                            'pending' => 'รอการอนุมัติ',
                            'in_progress' => 'กำลังดำเนินการ',
                            'completed' => 'เสร็จสิ้น',
                            'rejected' => 'ปฏิเสธ',
                            'cancelled' => 'ยกเลิก'
                        ];
                        echo $status_labels[$request['status']] ?? 'ไม่ทราบ';
                        ?>
                    </span>
                    
                    <!-- Priority Badge -->
                    <span class="px-4 py-2 rounded-full font-bold
                        <?php
                        echo match($request['priority']) {
                            'urgent' => 'bg-red-100 text-red-700',
                            'high' => 'bg-orange-100 text-orange-700',
                            'medium' => 'bg-blue-100 text-blue-700',
                            'low' => 'bg-green-100 text-green-700',
                            default => 'bg-gray-100 text-gray-700'
                        };
                        ?>
                    ">
                        <?php
                        $priority_labels = [
                            'urgent' => 'เร่งด่วน',
                            'high' => 'สูง',
                            'medium' => 'ปานกลาง',
                            'low' => 'ต่ำ'
                        ];
                        echo $priority_labels[$request['priority']] ?? 'ไม่ทราบ';
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Main Content -->
            <div class="lg:col-span-2">
                <!-- Request Information -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 pb-3 border-b">ข้อมูลผู้ร้องขอ</h2>
                    
                    <form method="POST" id="updateForm" class="space-y-4">
                        <input type="hidden" name="action" value="update">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ชื่อ-นามสกุล</label>
                                <input type="text" name="requester_name" value="<?= htmlspecialchars($request['requester_name']) ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                                <input type="email" name="requester_email" value="<?= htmlspecialchars($request['requester_email'] ?? '') ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทร</label>
                                <input type="tel" name="requester_phone" value="<?= htmlspecialchars($request['requester_phone'] ?? '') ?>" 
                                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">แผนก/หน่วยงาน</label>
                                <select name="department_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                                    <option value="">-- เลือกแผนก --</option>
                                    <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>" <?= $request['department_id'] == $dept['department_id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">ความสำคัญ</label>
                                <select name="priority" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                                    <option value="low" <?= $request['priority'] === 'low' ? 'selected' : '' ?>>ต่ำ</option>
                                    <option value="medium" <?= $request['priority'] === 'medium' ? 'selected' : '' ?>>ปานกลาง</option>
                                    <option value="high" <?= $request['priority'] === 'high' ? 'selected' : '' ?>>สูง</option>
                                    <option value="urgent" <?= $request['priority'] === 'urgent' ? 'selected' : '' ?>>เร่งด่วน</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">สถานะ</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500">
                                    <option value="pending" <?= $request['status'] === 'pending' ? 'selected' : '' ?>>รอการอนุมัติ</option>
                                    <option value="in_progress" <?= $request['status'] === 'in_progress' ? 'selected' : '' ?>>กำลังดำเนินการ</option>
                                    <option value="completed" <?= $request['status'] === 'completed' ? 'selected' : '' ?>>เสร็จสิ้น</option>
                                    <option value="rejected" <?= $request['status'] === 'rejected' ? 'selected' : '' ?>>ปฏิเสธ</option>
                                    <option value="cancelled" <?= $request['status'] === 'cancelled' ? 'selected' : '' ?>>ยกเลิก</option>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">รายละเอียด</label>
                            <textarea name="description" rows="4" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"><?= htmlspecialchars($request['description']) ?></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุของเจ้าหน้าที่</label>
                            <textarea name="admin_notes" rows="3" 
                                      class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500"><?= htmlspecialchars($request['admin_notes'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-bold">
                                <i class="fas fa-save mr-2"></i>บันทึกการเปลี่ยนแปลง
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Service Details -->
                <?php if (!empty($service_details)): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 pb-3 border-b">รายละเอียดบริการ</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($service_details as $key => $value): ?>
                            <?php if (!in_array($key, ['id', 'request_id'])): ?>
                            <div>
                                <p class="text-sm text-gray-600 font-medium"><?= ucfirst(str_replace('_', ' ', $key)) ?></p>
                                <p class="text-gray-900"><?= htmlspecialchars($value ?? '-') ?></p>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 pb-3 border-b">เวลาสำคัญ</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <i class="fas fa-calendar text-teal-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">เรียกร้องเมื่อ</p>
                                <p class="text-gray-900 font-bold"><?= date('d/m/Y H:i', strtotime($request['created_at'])) ?></p>
                            </div>
                        </div>
                        
                        <?php if ($request['started_at']): ?>
                        <div class="flex items-start">
                            <i class="fas fa-play text-blue-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">เริ่มดำเนินการเมื่อ</p>
                                <p class="text-gray-900 font-bold"><?= date('d/m/Y H:i', strtotime($request['started_at'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($request['completed_at']): ?>
                        <div class="flex items-start">
                            <i class="fas fa-check text-green-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">เสร็จสิ้นเมื่อ</p>
                                <p class="text-gray-900 font-bold"><?= date('d/m/Y H:i', strtotime($request['completed_at'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Actions -->
            <div class="lg:col-span-1">
                <!-- Assignment -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">มอบหมายงาน</h3>
                    
                    <?php if ($assigned_staff): ?>
                    <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-600 mb-1">มอบหมายให้</p>
                        <p class="text-gray-900 font-bold">
                            <?= htmlspecialchars($assigned_staff['first_name'] . ' ' . $assigned_staff['last_name']) ?>
                        </p>
                        <p class="text-xs text-gray-500 mt-2">
                            <?= date('d/m/Y H:i', strtotime($request['assigned_at'])) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($request['status'] === 'pending'): ?>
                    <form method="POST">
                        <input type="hidden" name="action" value="approve">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">เลือกผู้รับมอบหมาย</label>
                            <select name="assigned_to" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 text-sm">
                                <option value="">-- เลือกผู้รับมอบหมาย --</option>
                                <?php foreach ($users as $user): ?>
                                <option value="<?= $user['user_id'] ?>">
                                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold">
                            <i class="fas fa-check mr-2"></i>อนุมัติ
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

                <!-- Rejection -->
                <?php if ($request['status'] !== 'rejected' && $request['status'] !== 'completed'): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">ปฏิเสธคำขอ</h3>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="reject">
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">เหตุผลในการปฏิเสธ</label>
                            <textarea name="rejection_reason" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm"></textarea>
                        </div>
                        
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold" 
                                onclick="return confirm('คุณแน่ใจหรือว่าต้องการปฏิเสธคำขอนี้?')">
                            <i class="fas fa-times mr-2"></i>ปฏิเสธ
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Delete -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">ลบข้อมูล</h3>
                    
                    <form method="POST" onsubmit="return confirm('คุณแน่ใจหรือว่าต้องการลบข้อมูลนี้? การกระทำนี้ไม่สามารถยกเลิกได้');">
                        <input type="hidden" name="action" value="delete">
                        
                        <button type="submit" class="w-full px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 font-bold">
                            <i class="fas fa-trash mr-2"></i>ลบคำขอนี้
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../admin-layout/footer.php'; ?>
