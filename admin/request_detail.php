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
    
    case 'PHOTOGRAPHY':
        $detail_stmt = $conn->prepare("SELECT * FROM request_photography_details WHERE request_id = ?");
        $detail_stmt->bind_param("i", $request_id);
        $detail_stmt->execute();
        $service_details = $detail_stmt->get_result()->fetch_assoc() ?: [];
        break;
    
    case 'MC':
        $detail_stmt = $conn->prepare("SELECT * FROM request_mc_details WHERE request_id = ?");
        $detail_stmt->bind_param("i", $request_id);
        $detail_stmt->execute();
        $service_details = $detail_stmt->get_result()->fetch_assoc() ?: [];
        break;
}

// Get task assignments for this request
$task_assignments = [];
$ta_query = $conn->prepare("
    SELECT ta.*,
           u_to.user_id as to_user_id, u_to.username as to_username,
           u_to.first_name as to_first, u_to.last_name as to_last,
           u_by.first_name as by_first, u_by.last_name as by_last,
           r.role_name
    FROM task_assignments ta
    JOIN users u_to ON ta.assigned_to = u_to.user_id
    JOIN users u_by ON ta.assigned_by = u_by.user_id
    LEFT JOIN roles r ON ta.assigned_as_role = r.role_id
    WHERE ta.request_id = ? AND ta.status != 'cancelled'
    ORDER BY ta.created_at DESC
");
$ta_query->bind_param('i', $request_id);
$ta_query->execute();
$ta_result = $ta_query->get_result();
while ($row = $ta_result->fetch_assoc()) {
    $task_assignments[] = $row;
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

$page_title = 'รายละเอียดคำขอ ' . $request['request_code'];
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'url' => 'index.php', 'icon' => 'fa-home'],
    ['label' => 'จัดการคำขอบริการ', 'url' => 'service_requests.php'],
    ['label' => $request['request_code']]
];

include __DIR__ . '/admin-layout/header.php';
include __DIR__ . '/admin-layout/sidebar.php';
include __DIR__ . '/admin-layout/topbar.php';
?>

<style>
    .swal-status-popup { border-radius: 16px !important; }
    .swal-status-confirm-btn { border-radius: 10px !important; padding: 10px 28px !important; font-weight: 600 !important; font-size: 0.95rem !important; }
    .swal-status-cancel-btn { border-radius: 10px !important; padding: 10px 28px !important; font-weight: 600 !important; font-size: 0.95rem !important; }
</style>

<!-- mainContent div is already opened in topbar.php -->
    <div class="p-4">
        <!-- Breadcrumb -->
        <nav class="text-gray-600 text-sm mb-4">
            <a href="index.php" class="hover:text-teal-600"><i class="fas fa-home mr-1"></i>หน้าหลัก</a>
            <span class="mx-2">/</span>
            <a href="service_requests.php" class="hover:text-teal-600">จัดการคำขอบริการ</a>
            <span class="mx-2">/</span>
            <span class="text-gray-900 font-bold"><?= htmlspecialchars($request['request_code']) ?></span>
        </nav>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_msg'])): ?>
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-green-700">
            <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['success_msg']; unset($_SESSION['success_msg']); ?>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_msg'])): ?>
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg text-red-700">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $_SESSION['error_msg']; unset($_SESSION['error_msg']); ?>
        </div>
        <?php endif; ?>

        <!-- Header -->
        <?php
        $service_map = [
            'PHOTOGRAPHY' => ['label' => 'บริการช่างภาพ',       'icon' => 'fa-camera',          'color' => 'bg-purple-100 text-purple-700 border-purple-200'],
            'MC'          => ['label' => 'บริการพิธีกร',         'icon' => 'fa-microphone',      'color' => 'bg-pink-100 text-pink-700 border-pink-200'],
            'IT_SUPPORT'  => ['label' => 'แจ้งซ่อมคอมพิวเตอร์', 'icon' => 'fa-computer',        'color' => 'bg-blue-100 text-blue-700 border-blue-200'],
            'EMAIL'       => ['label' => 'ขอใช้อีเมล',           'icon' => 'fa-envelope',        'color' => 'bg-sky-100 text-sky-700 border-sky-200'],
            'NAS'         => ['label' => 'ขอใช้พื้นที่ NAS',     'icon' => 'fa-hard-drive',      'color' => 'bg-yellow-100 text-yellow-700 border-yellow-200'],
            'WEB_DESIGN'  => ['label' => 'ออกแบบเว็บไซต์',       'icon' => 'fa-globe',           'color' => 'bg-teal-100 text-teal-700 border-teal-200'],
            'PRINTER'     => ['label' => 'แจ้งซ่อมปริ้นเตอร์',   'icon' => 'fa-print',           'color' => 'bg-orange-100 text-orange-700 border-orange-200'],
            'QR_CODE'     => ['label' => 'สร้าง QR Code',        'icon' => 'fa-qrcode',          'color' => 'bg-gray-100 text-gray-700 border-gray-200'],
            'INTERNET'    => ['label' => 'แจ้งปัญหาอินเทอร์เน็ต','icon' => 'fa-wifi',            'color' => 'bg-indigo-100 text-indigo-700 border-indigo-200'],
            'LED'         => ['label' => 'ขอใช้จอ LED',          'icon' => 'fa-tv',              'color' => 'bg-green-100 text-green-700 border-green-200'],
        ];
        $svc = $service_map[$request['service_code']] ?? ['label' => htmlspecialchars($request['service_name'] ?? $request['service_code']), 'icon' => 'fa-circle-info', 'color' => 'bg-gray-100 text-gray-700 border-gray-200'];
        ?>
        <div class="bg-white rounded-lg shadow-lg p-6 mb-4">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 mb-2"><?= htmlspecialchars($request['request_code']) ?></h1>
                    <span class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full border text-sm font-bold <?= $svc['color'] ?>">
                        <i class="fas <?= $svc['icon'] ?>"></i>
                        <?= $svc['label'] ?>
                    </span>
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
                <div class="bg-white rounded-lg shadow-lg p-6 mb-4">
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
                        <?php 
                        // Thai field labels mapping
                        $field_labels = [
                            'event_name' => 'ชื่องาน/โครงการ',
                            'event_type' => 'ประเภทงาน',
                            'event_date' => 'วันที่จัดงาน',
                            'event_time_start' => 'เวลาเริ่ม',
                            'event_time_end' => 'เวลาสิ้นสุด (โดยประมาณ)',
                            'event_location' => 'สถานที่จัดงาน',
                            'location' => 'สถานที่',
                            'number_of_photographers' => 'จำนวนช่างภาพ',
                            'video_required' => 'ต้องการวิดีโอ',
                            'drone_required' => 'ต้องการโดรน',
                            'delivery_format' => 'รูปแบบการส่งมอบ',
                            'special_requirements' => 'ข้อมูลเพิ่มเติม',
                            'mc_count' => 'จำนวนพิธีกร',
                            'language' => 'ภาษา',
                            'script_status' => 'สถานะบทพูด',
                            'dress_code' => 'การแต่งกาย',
                            'requested_username' => 'ชื่อผู้ใช้',
                            'email_format' => 'รูปแบบอีเมล',
                            'quota_mb' => 'พื้นที่ (MB)',
                            'purpose' => 'วัตถุประสงค์',
                            'is_new_account' => 'บัญชีใหม่',
                            'existing_email' => 'อีเมลเดิม',
                            'folder_name' => 'ชื่อโฟลเดอร์',
                            'storage_size_gb' => 'ขนาดที่ขอ (GB)',
                            'permission_type' => 'สิทธิ์',
                            'shared_with' => 'ผู้ใช้งานร่วม',
                            'backup_required' => 'ต้องการ Backup',
                        ];
                        ?>
                        <?php foreach ($service_details as $key => $value): ?>
                            <?php if (!in_array($key, ['id', 'request_id'])): ?>
                            <div>
                                <?php 
                                $label = isset($field_labels[$key]) ? $field_labels[$key] : ucfirst(str_replace('_', ' ', $key));
                                $display_value = $value;
                                
                                // Format dates and times
                                if (strpos($key, '_date') !== false && $value) {
                                    $display_value = date('d/m/Y', strtotime($value));
                                } else if (strpos($key, '_time') !== false && $value) {
                                    $display_value = date('H:i', strtotime($value));
                                } else if (in_array($key, ['is_new_account', 'video_required', 'drone_required', 'backup_required'])) {
                                    $display_value = $value ? 'ใช่' : 'ไม่ใช่';
                                }
                                ?>
                                <p class="text-sm text-gray-600 font-medium"><?= $label ?></p>
                                <p class="text-gray-900 font-bold"><?= htmlspecialchars($display_value ?? '-') ?></p>
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Timeline -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 pb-3 border-b">เวลาสำคัญ</h2>
                    
                    <div class="space-y-3">
                        <div class="flex items-start">
                            <i class="fas fa-calendar text-teal-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">เรียกร้องเมื่อ</p>
                                <p class="text-gray-900 font-bold"><?= date('d/m/Y H:i', strtotime($request['created_at'])) ?></p>
                            </div>
                        </div>
                        
                        <?php if ($request['expected_completion_date']): ?>
                        <div class="flex items-start">
                            <i class="fas fa-hourglass-end text-orange-600 mt-1 mr-3"></i>
                            <div>
                                <p class="text-sm text-gray-600">วันเวลาที่ต้องทำงาน</p>
                                <p class="text-gray-900 font-bold"><?= date('d/m/Y', strtotime($request['expected_completion_date'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
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

                <!-- Attachments -->
                <?php if ($request['attachments']): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4 pb-3 border-b">ไฟล์แนบ</h2>
                    
                    <?php 
                    $attachments = json_decode($request['attachments'], true);
                    if (is_array($attachments) && !empty($attachments)): 
                    ?>
                    <div class="space-y-2">
                        <?php foreach ($attachments as $file_path): ?>
                        <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-200 hover:border-teal-400 transition">
                            <div class="flex items-center flex-1">
                                <i class="fas fa-file text-teal-600 mr-3 text-lg"></i>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars(basename($file_path)) ?></p>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($file_path) ?></p>
                                </div>
                            </div>
                            <a href="<?= htmlspecialchars('../' . $file_path) ?>" class="px-3 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 text-xs font-bold transition" download>
                                <i class="fas fa-download mr-1"></i>ดาวน์โหลด
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <p class="text-gray-500 italic">ไม่มีไฟล์แนบ</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar Actions -->
            <div class="lg:col-span-1">
                <!-- Task Assignments -->
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="fas fa-user-tag text-teal-600 mr-1"></i> มอบหมายงาน
                    </h3>

                    <?php if (!empty($task_assignments)): ?>
                        <div class="space-y-3 mb-4">
                        <?php foreach ($task_assignments as $ta):
                            $status_config = [
                                'pending'     => ['label' => 'รอรับงาน',       'bg' => 'bg-yellow-50', 'border' => 'border-yellow-200', 'text' => 'text-yellow-800'],
                                'accepted'    => ['label' => 'รับงานแล้ว',     'bg' => 'bg-blue-50',   'border' => 'border-blue-200',   'text' => 'text-blue-800'],
                                'in_progress' => ['label' => 'กำลังดำเนินการ', 'bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-800'],
                                'completed'   => ['label' => 'เสร็จสิ้น',     'bg' => 'bg-green-50',  'border' => 'border-green-200',  'text' => 'text-green-800'],
                            ];
                            $sc = $status_config[$ta['status']] ?? ['label' => $ta['status'], 'bg' => 'bg-gray-50', 'border' => 'border-gray-200', 'text' => 'text-gray-800'];
                        ?>
                            <div class="<?= $sc['bg'] ?> border <?= $sc['border'] ?> rounded-lg p-4">
                                <div class="flex justify-between items-start mb-1">
                                    <p class="text-gray-900 font-bold text-sm">
                                        <?= htmlspecialchars($ta['to_first'] . ' ' . $ta['to_last']) ?>
                                        <span class="text-gray-400 font-normal">@<?= htmlspecialchars($ta['to_username']) ?></span>
                                    </p>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded <?= $sc['bg'] ?> <?= $sc['text'] ?>">
                                        <?= $sc['label'] ?>
                                    </span>
                                </div>
                                <?php if ($ta['role_name']): ?>
                                <p class="text-xs text-gray-500">บทบาท: <?= htmlspecialchars($ta['role_name']) ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-400 mt-1">
                                    มอบหมายโดย <?= htmlspecialchars($ta['by_first'] . ' ' . $ta['by_last']) ?>
                                    &middot; <?= date('d/m/Y H:i', strtotime($ta['created_at'])) ?>
                                </p>
                                <?php if (!in_array($ta['status'], ['completed', 'cancelled'])): ?>
                                <div class="flex gap-2 mt-3 pt-2 border-t <?= $sc['border'] ?>">
                                    <button onclick="reassignTask(<?= $ta['assignment_id'] ?>, '<?= htmlspecialchars($ta['to_first'] . ' ' . $ta['to_last'], ENT_QUOTES) ?>')"
                                            class="flex-1 text-xs px-2 py-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 font-medium">
                                        <i class="fas fa-exchange-alt mr-1"></i> เปลี่ยนผู้รับ
                                    </button>
                                    <button onclick="cancelAssignment(<?= $ta['assignment_id'] ?>)"
                                            class="flex-1 text-xs px-2 py-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 font-medium">
                                        <i class="fas fa-times mr-1"></i> ยกเลิก
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-400 mb-4">ยังไม่มีการมอบหมายงาน</p>
                    <?php endif; ?>

                    <!-- New Assignment Form -->
                    <div class="bg-gray-50 rounded-lg p-4 mt-4">
                        <h4 class="text-sm font-bold text-gray-700 mb-3">
                            <i class="fas fa-plus-circle text-teal-600 mr-1"></i> มอบหมายงานใหม่
                        </h4>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">ผู้รับผิดชอบ <span class="text-red-500">*</span></label>
                                <select id="newAssignUser" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                    <option value="">-- กำลังโหลด --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">ความสำคัญ</label>
                                <select id="newAssignPriority" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                                    <option value="normal">ปกติ</option>
                                    <option value="low">ต่ำ</option>
                                    <option value="high">สูง</option>
                                    <option value="urgent">เร่งด่วน</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">กำหนดส่ง</label>
                                <input type="datetime-local" id="newAssignDueDate" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">หมายเหตุ</label>
                                <textarea id="newAssignNotes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-teal-500 focus:outline-none" placeholder="ระบุรายละเอียดเพิ่มเติม..."></textarea>
                            </div>
                            <button onclick="submitNewAssignment()" class="w-full px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700 font-bold text-sm">
                                <i class="fas fa-user-plus mr-1"></i> มอบหมายงาน
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Rejection -->
                <?php if ($request['status'] !== 'rejected' && $request['status'] !== 'completed'): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">ปฏิเสธคำขอ</h3>

                    <form method="POST" id="rejectForm">
                        <input type="hidden" name="action" value="reject">

                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">เหตุผลในการปฏิเสธ</label>
                            <textarea name="rejection_reason" id="rejectionReason" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 text-sm"></textarea>
                        </div>

                        <button type="button" onclick="confirmReject()" class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-bold">
                            <i class="fas fa-times mr-2"></i>ปฏิเสธ
                        </button>
                    </form>
                </div>
                <?php endif; ?>

                <!-- Delete -->
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">ลบข้อมูล</h3>

                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="action" value="delete">

                        <button type="button" onclick="confirmDelete()" class="w-full px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 font-bold">
                            <i class="fas fa-trash mr-2"></i>ลบคำขอนี้
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div><!-- End mainContent from topbar.php -->

<script>
const REQUEST_ID = <?= $request_id ?>;
const SERVICE_CODE = '<?= htmlspecialchars($request['service_code']) ?>';

// Load available users on page load
document.addEventListener('DOMContentLoaded', loadAvailableUsers);

async function loadAvailableUsers() {
    const select = document.getElementById('newAssignUser');
    try {
        const response = await fetch(`api/task_assignment_api.php?action=get_available_users&service_code=${SERVICE_CODE}&request_id=${REQUEST_ID}`);
        const responseText = await response.text();
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseErr) {
            console.error('API Response (not JSON):', responseText);
            select.innerHTML = '<option value="">❌ API Error - ดู Console</option>';
            return;
        }
        
        select.innerHTML = '<option value="">-- เลือกผู้รับผิดชอบ --</option>';
        if (data.success && data.users.length > 0) {
            data.users.forEach(user => {
                const option = document.createElement('option');
                option.value = user.user_id;
                option.textContent = `${user.first_name} ${user.last_name} [@${user.username}] (${user.roles})`;
                select.appendChild(option);
            });
        } else {
            select.innerHTML += `<option disabled>${data.message || 'ไม่มีผู้ใช้ที่เหมาะสม'}</option>`;
        }
    } catch (error) {
        console.error('loadAvailableUsers error:', error);
        select.innerHTML = `<option value="">❌ ${error.message}</option>`;
    }
}

async function submitNewAssignment() {
    const userId = document.getElementById('newAssignUser').value;
    const priority = document.getElementById('newAssignPriority').value;
    const dueDate = document.getElementById('newAssignDueDate').value;
    const notes = document.getElementById('newAssignNotes').value;

    if (!userId) {
        Swal.fire('ข้อมูลไม่ครบ', 'กรุณาเลือกผู้รับผิดชอบ', 'warning');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'assign_task');
        formData.append('request_id', REQUEST_ID);
        formData.append('assigned_to', userId);
        formData.append('priority', priority);
        formData.append('due_date', dueDate);
        formData.append('notes', notes);

        const response = await fetch('api/task_assignment_api.php', { method: 'POST', body: formData });
        
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers.get('content-type'));
        
        const responseText = await response.text();
        console.log('Response body:', responseText);
        
        if (!responseText || responseText.trim() === '') {
            Swal.fire('ผิดพลาด', 'Server ตอบกลับว่างเปล่า (Empty Response)<br>Status: ' + response.status + '<br>URL: api/task_assignment_api.php', 'error');
            return;
        }
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseErr) {
            Swal.fire('ผิดพลาด', 'Server ตอบกลับไม่ใช่ JSON (Status ' + response.status + '):<br><code style="word-break:break-all;font-size:12px;">' + responseText.substring(0, 800) + '</code>', 'error');
            return;
        }

        if (result.success) {
            Swal.fire('สำเร็จ', result.message, 'success').then(() => location.reload());
        } else {
            let errMsg = result.message || 'Unknown error';
            if (result.debug) errMsg += '<br><small>(' + result.debug.file + ':' + result.debug.line + ')</small>';
            Swal.fire('ผิดพลาด', errMsg, 'error');
        }
    } catch (error) {
        console.error('Assign task error:', error);
        Swal.fire('ผิดพลาด', 'Network/Fetch Error:<br>' + error.name + ': ' + error.message, 'error');
    }
}

async function reassignTask(assignmentId, currentName) {
    // Load available users for the SweetAlert dropdown
    let usersOptions = '';
    try {
        const response = await fetch(`api/task_assignment_api.php?action=get_available_users&service_code=${SERVICE_CODE}&request_id=${REQUEST_ID}`);
        const data = await response.json();
        if (data.success) {
            data.users.forEach(user => {
                usersOptions += `<option value="${user.user_id}">${user.first_name} ${user.last_name} [@${user.username}] (${user.roles})</option>`;
            });
        }
    } catch (e) { /* ignore */ }

    const { value: formValues } = await Swal.fire({
        title: 'เปลี่ยนผู้รับผิดชอบ',
        width: 420,
        html:
            `<p style="font-size:0.85rem;color:#6b7280;margin-bottom:0.75rem;">ปัจจุบัน: <strong>${currentName}</strong></p>` +
            '<label style="display:block;font-size:0.8rem;font-weight:500;text-align:left;margin-bottom:4px;">ผู้รับผิดชอบใหม่</label>' +
            '<select id="swal-reassign-user" style="width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:0.8rem;margin:0;">' +
            '<option value="">-- เลือกผู้รับผิดชอบ --</option>' +
            usersOptions +
            '</select>' +
            '<label style="display:block;font-size:0.8rem;font-weight:500;text-align:left;margin:10px 0 4px;">หมายเหตุ</label>' +
            '<textarea id="swal-reassign-notes" rows="2" style="width:100%;padding:6px 8px;border:1px solid #d1d5db;border-radius:6px;font-size:0.8rem;resize:vertical;margin:0;" placeholder="เหตุผลในการเปลี่ยน..."></textarea>',
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonColor: '#0d9488',
        confirmButtonText: '<i class="fas fa-exchange-alt mr-1"></i> เปลี่ยน',
        cancelButtonText: 'ยกเลิก',
        preConfirm: () => {
            const userId = document.getElementById('swal-reassign-user').value;
            if (!userId) {
                Swal.showValidationMessage('กรุณาเลือกผู้รับผิดชอบใหม่');
                return false;
            }
            return {
                assigned_to: userId,
                notes: document.getElementById('swal-reassign-notes').value
            };
        }
    });

    if (!formValues) return;

    try {
        const formData = new FormData();
        formData.append('action', 'reassign');
        formData.append('assignment_id', assignmentId);
        formData.append('assigned_to', formValues.assigned_to);
        formData.append('notes', formValues.notes);

        const response = await fetch('api/task_assignments_api.php', { method: 'POST', body: formData });
        const result = await response.json();

        if (result.success) {
            Swal.fire('สำเร็จ', result.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('ผิดพลาด', result.message, 'error');
        }
    } catch (error) {
        Swal.fire('ผิดพลาด', 'ไม่สามารถเปลี่ยนผู้รับผิดชอบได้', 'error');
    }
}

// Confirm Reject Request
async function confirmReject() {
    const reason = document.getElementById('rejectionReason').value.trim();

    const result = await Swal.fire({
        title: '',
        html: `
            <div style="text-align:center;margin-bottom:16px;">
                <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#ef4444,#dc2626);
                            display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                    <i class="fas fa-ban" style="color:#fff;font-size:24px;"></i>
                </div>
                <h2 style="margin:0;font-size:1.3rem;font-weight:700;color:#1f2937;">ปฏิเสธคำขอนี้?</h2>
                <p style="margin:6px 0 0;color:#6b7280;font-size:0.875rem;">คำขอ <?= htmlspecialchars($request['request_code']) ?></p>
            </div>
            <div style="text-align:left;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-top:8px;">
                <p style="font-size:0.8rem;font-weight:600;color:#991b1b;margin:0 0 4px;">
                    <i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> คำเตือน
                </p>
                <p style="font-size:0.8rem;color:#7f1d1d;margin:0;">การปฏิเสธจะแจ้งผู้ร้องขอทราบ และเปลี่ยนสถานะเป็น "ปฏิเสธ"</p>
            </div>
            ${reason ? '<div style="text-align:left;margin-top:12px;"><p style="font-size:0.75rem;font-weight:600;color:#374151;margin:0 0 4px;">เหตุผล:</p><p style="font-size:0.85rem;color:#4b5563;margin:0;background:#f9fafb;padding:10px;border-radius:8px;border:1px solid #e5e7eb;">' + reason.replace(/</g,'&lt;').replace(/>/g,'&gt;') + '</p></div>' : ''}
        `,
        width: 420,
        padding: '24px',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-ban" style="margin-right:6px;"></i> ยืนยันปฏิเสธ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'swal-status-popup',
            confirmButton: 'swal-status-confirm-btn',
            cancelButton: 'swal-status-cancel-btn'
        },
        focusCancel: true
    });

    if (result.isConfirmed) {
        document.getElementById('rejectForm').submit();
    }
}

// Confirm Delete Request
async function confirmDelete() {
    const result = await Swal.fire({
        title: '',
        html: `
            <div style="text-align:center;margin-bottom:16px;">
                <div style="width:56px;height:56px;border-radius:50%;background:linear-gradient(135deg,#b91c1c,#7f1d1d);
                            display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                    <i class="fas fa-trash-alt" style="color:#fff;font-size:22px;"></i>
                </div>
                <h2 style="margin:0;font-size:1.3rem;font-weight:700;color:#1f2937;">ลบคำขอนี้?</h2>
                <p style="margin:6px 0 0;color:#6b7280;font-size:0.875rem;">คำขอ <?= htmlspecialchars($request['request_code']) ?></p>
            </div>
            <div style="text-align:left;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:14px;margin-top:8px;">
                <p style="font-size:0.8rem;font-weight:600;color:#991b1b;margin:0 0 4px;">
                    <i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i> คำเตือน
                </p>
                <p style="font-size:0.8rem;color:#7f1d1d;margin:0;">การลบจะไม่สามารถกู้คืนได้ ข้อมูลคำขอ, รายละเอียดบริการ และการมอบหมายงานทั้งหมดจะถูกลบถาวร</p>
            </div>
        `,
        width: 420,
        padding: '24px',
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-trash-alt" style="margin-right:6px;"></i> ยืนยันลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#b91c1c',
        cancelButtonColor: '#6b7280',
        customClass: {
            popup: 'swal-status-popup',
            confirmButton: 'swal-status-confirm-btn',
            cancelButton: 'swal-status-cancel-btn'
        },
        focusCancel: true
    });

    if (result.isConfirmed) {
        document.getElementById('deleteForm').submit();
    }
}

async function cancelAssignment(assignmentId) {
    const result = await Swal.fire({
        title: 'ยืนยันการยกเลิก',
        text: 'ต้องการยกเลิกการมอบหมายงานนี้?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ยกเลิกงาน',
        cancelButtonText: 'ไม่ใช่'
    });

    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('action', 'cancel');
        formData.append('assignment_id', assignmentId);

        const response = await fetch('api/task_assignments_api.php', { method: 'POST', body: formData });
        const data = await response.json();

        if (data.success) {
            Swal.fire('สำเร็จ', data.message, 'success').then(() => location.reload());
        } else {
            Swal.fire('ผิดพลาด', data.message, 'error');
        }
    } catch (error) {
        Swal.fire('ผิดพลาด', 'ไม่สามารถยกเลิกได้', 'error');
    }
}
</script>

<?php include __DIR__ . '/admin-layout/footer.php'; ?>
