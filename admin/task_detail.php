<?php
/**
 * Task Detail Page - View full task information
 * สำหรับผู้ที่ได้รับมอบหมายงานเพื่อดูรายละเอียดเต็มของงาน
 */

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$assignment_id = intval($_GET['assignment_id'] ?? 0);

if (!$assignment_id) {
    header('Location: my_tasks.php');
    exit();
}

// Get task assignment with verification
$task_query = "SELECT ta.assignment_id, ta.request_id, ta.assigned_to, ta.assigned_as_role, ta.assigned_by, ta.priority, ta.due_date, ta.notes, ta.accepted_at, ta.started_at, ta.completed_at, ta.completion_notes, ta.created_at, ta.updated_at, ta.start_time, ta.end_time,
                sr.*,
                ta.status, ta.priority,
                u_by.username as assigned_by_username,
                CONCAT(p_by.prefix_name, u_by.first_name, ' ', u_by.last_name) as assigned_by_name,
                r.role_name as assigned_role_name, r.role_icon, r.role_color
                FROM task_assignments ta
                JOIN service_requests sr ON ta.request_id = sr.request_id
                JOIN users u_by ON ta.assigned_by = u_by.user_id
                LEFT JOIN prefixes p_by ON u_by.prefix_id = p_by.prefix_id
                LEFT JOIN roles r ON ta.assigned_as_role = r.role_id
                WHERE ta.assignment_id = ? AND ta.assigned_to = ?";

$task_stmt = $conn->prepare($task_query);
$task_stmt->bind_param('ii', $assignment_id, $user_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();

if (!$task_result || $task_result->num_rows === 0) {
    header('Location: my_tasks.php');
    exit();
}

$task = $task_result->fetch_assoc();

// Check if user is manager
$is_manager = false;
$manager_check = $conn->prepare("
    SELECT COUNT(*) as cnt FROM user_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.user_id = ? AND r.role_code IN ('manager', 'all')
    AND ur.is_active = 1 AND r.is_active = 1
");
$manager_check->bind_param('i', $user_id);
$manager_check->execute();
$manager_result = $manager_check->get_result()->fetch_assoc();
$is_manager = $manager_result['cnt'] > 0;

// Get service-specific details
$service_details = null;
$service_code = $task['service_code'];

$detail_tables = [
    'EMAIL' => 'request_email_details',
    'NAS' => 'request_nas_details',
    'IT_SUPPORT' => 'request_it_support_details',
    'INTERNET' => 'request_internet_details',
    'PHOTOGRAPHY' => 'request_photography_details',
    'MC' => 'request_mc_details',
    'WEB_DESIGN' => 'request_webdesign_details',
    'PRINTER' => 'request_printer_details',
    'QR_CODE' => 'request_qrcode_details'
];

if (isset($detail_tables[$service_code])) {
    $table = $detail_tables[$service_code];
    $detail_query = "SELECT * FROM $table WHERE request_id = ? LIMIT 1";
    $detail_stmt = $conn->prepare($detail_query);
    $detail_stmt->bind_param('i', $task['request_id']);
    $detail_stmt->execute();
    $service_details = $detail_stmt->get_result()->fetch_assoc();
}

$page_title = 'รายละเอียดงาน - ' . htmlspecialchars($task['request_code']);
$current_page = 'my_tasks';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'งานของฉัน', 'url' => 'my_tasks.php'],
    ['label' => 'รายละเอียดงาน']
];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
    .detail-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .detail-card h3 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 1rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f3f4f6;
    }

    .detail-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1.5rem;
        margin-bottom: 1rem;
    }

    .detail-row.full {
        grid-template-columns: 1fr;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
    }

    .detail-label {
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        color: #9ca3af;
        margin-bottom: 0.375rem;
        letter-spacing: 0.05em;
    }

    .detail-value {
        font-size: 1rem;
        color: #1f2937;
        font-weight: 500;
    }

    .detail-value.text {
        font-weight: 400;
        line-height: 1.6;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 0.5rem;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-accepted { background-color: #dbeafe; color: #0c4a6e; }
    .status-in_progress { background-color: #c7d2fe; color: #312e81; }
    .status-completed { background-color: #dcfce7; color: #166534; }

    .timeline {
        position: relative;
        padding: 1rem 0;
    }

    .timeline-item {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
        position: relative;
    }

    .timeline-item:not(:last-child)::before {
        content: '';
        position: absolute;
        left: 1.25rem;
        top: 2.5rem;
        width: 2px;
        height: calc(100% + 0.5rem);
        background-color: #e5e7eb;
    }

    .timeline-dot {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 50%;
        background-color: #f3f4f6;
        border: 2px solid #e5e7eb;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-weight: 600;
        color: #6b7280;
    }

    .timeline-dot.completed {
        background-color: #dcfce7;
        border-color: #22c55e;
        color: #166534;
    }

    .timeline-dot.in_progress {
        background-color: #c7d2fe;
        border-color: #6366f1;
        color: #312e81;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    .timeline-content {
        flex: 1;
        padding-top: 0.375rem;
    }

    .timeline-label {
        font-weight: 600;
        color: #1f2937;
        font-size: 0.875rem;
    }

    .timeline-time {
        font-size: 0.75rem;
        color: #9ca3af;
        margin-top: 0.25rem;
    }

    .attachment-item {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem;
        background-color: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 0.375rem;
        margin-bottom: 0.5rem;
    }

    .attachment-icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.375rem;
        background-color: #eff6ff;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #0284c7;
        flex-shrink: 0;
    }

    .attachment-info {
        flex: 1;
    }

    .attachment-name {
        font-weight: 500;
        color: #1f2937;
        font-size: 0.875rem;
    }

    .attachment-size {
        font-size: 0.75rem;
        color: #9ca3af;
    }

    .action-buttons {
        display: flex;
        gap: 0.75rem;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 1px solid #e5e7eb;
    }

    .btn-action {
        flex: 1;
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-back {
        background-color: #f3f4f6;
        color: #1f2937;
    }

    .btn-back:hover {
        background-color: #e5e7eb;
    }

    .btn-accept {
        background-color: #dbeafe;
        color: #0c4a6e;
    }

    .btn-accept:hover {
        background-color: #bfdbfe;
    }

    .btn-start {
        background-color: #c7d2fe;
        color: #312e81;
    }

    .btn-start:hover {
        background-color: #a5b4fc;
    }

    .btn-complete {
        background-color: #dcfce7;
        color: #166534;
    }

    .btn-complete:hover {
        background-color: #bbf7d0;
    }

    .note-box {
        background-color: #fef3c7;
        border-left: 4px solid #fcd34d;
        padding: 1rem;
        border-radius: 0.375rem;
        margin-bottom: 1rem;
    }

    .note-box p {
        color: #92400e;
        margin: 0;
        font-size: 0.875rem;
        line-height: 1.5;
    }
</style>

<div class="p-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-tasks text-blue-600"></i>
                    <?= htmlspecialchars($task['request_code']) ?>
                </h1>
                <p class="text-gray-600">
                    <?= htmlspecialchars($task['service_name']) ?>
                </p>
            </div>
            <span class="status-badge status-<?= $task['status'] ?>">
                <i class="fas fa-circle"></i>
                <?php
                $status_labels = [
                    'pending' => 'รอรับงาน',
                    'accepted' => 'รับงานแล้ว',
                    'in_progress' => 'กำลังดำเนินการ',
                    'completed' => 'เสร็จสิ้น',
                    'cancelled' => 'ยกเลิก'
                ];
                echo $status_labels[$task['status']] ?? $task['status'];
                ?>
            </span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Details -->
        <div class="lg:col-span-2">
            <!-- เรื่องที่ขอ -->
            <div class="detail-card">
                <h3><i class="fas fa-file-alt text-green-600"></i> เรื่องที่ขอ</h3>
                <div class="detail-row full">
                    <div class="detail-item">
                        <span class="detail-label">หัวเรื่อง</span>
                        <span class="detail-value"><?= htmlspecialchars($task['subject']) ?></span>
                    </div>
                </div>
                <div class="detail-row full">
                    <div class="detail-item">
                        <span class="detail-label">รายละเอียด</span>
                        <span class="detail-value text"><?= htmlspecialchars($task['description']) ?></span>
                    </div>
                </div>
            </div>

            <!-- ผู้ขอบริการ -->
            <div class="detail-card">
                <h3><i class="fas fa-user text-blue-600"></i> ผู้ขอบริการ</h3>
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="detail-label">ชื่อ-นามสกุล</span>
                        <span class="detail-value"><?= htmlspecialchars($task['requester_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">ตำแหน่ง</span>
                        <span class="detail-value"><?= htmlspecialchars($task['requester_position'] ?? '-') ?></span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="detail-label">เบอร์โทรศัพท์</span>
                        <span class="detail-value"><?= htmlspecialchars($task['requester_phone'] ?? '-') ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">อีเมล</span>
                        <span class="detail-value"><?= htmlspecialchars($task['requester_email'] ?? '-') ?></span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="detail-label">หน่วยงาน</span>
                        <span class="detail-value"><?= htmlspecialchars($task['department_name'] ?? '-') ?></span>
                    </div>
                </div>
            </div>

            <!-- รายละเอียดการมอบหมาย -->
            <div class="detail-card">
                <h3><i class="fas fa-tasks text-purple-600"></i> รายละเอียดการมอบหมาย</h3>
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="detail-label">มอบหมายโดย</span>
                        <span class="detail-value"><?= htmlspecialchars($task['assigned_by_name']) ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">บทบาท</span>
                        <span class="detail-value">
                            <?php if ($task['assigned_role_name']): ?>
                                <span style="display: inline-flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas <?= $task['role_icon'] ?>" style="color: <?= $task['role_color'] ?>"></i>
                                    <?= htmlspecialchars($task['assigned_role_name']) ?>
                                </span>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                <div class="detail-row">
                    <div class="detail-item">
                        <span class="detail-label">ความสำคัญ</span>
                        <span class="detail-value">
                            <?php
                            $priority_colors = [
                                'low' => '#10b981',
                                'normal' => '#3b82f6',
                                'high' => '#f59e0b',
                                'urgent' => '#ef4444',
                                'medium' => '#8b5cf6'
                            ];
                            $priority_labels = [
                                'low' => 'ต่ำ',
                                'normal' => 'ปกติ',
                                'high' => 'สูง',
                                'urgent' => 'เร่งด่วน',
                                'medium' => 'ปานกลาง'
                            ];
                            $color = $priority_colors[$task['priority']] ?? '#6b7280';
                            $label = $priority_labels[$task['priority']] ?? $task['priority'];
                            ?>
                            <span style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.25rem 0.75rem; background-color: <?= $color ?>20; color: <?= $color ?>; border-radius: 0.25rem; font-weight: 500;">
                                <?= $label ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">กำหนดส่ง</span>
                        <span class="detail-value">
                            <?php if ($task['due_date']): ?>
                                <?= date('d/m/Y H:i', strtotime($task['due_date'])) ?>
                            <?php else: ?>
                                ไม่มีกำหนด
                            <?php endif; ?>
                        </span>
                    </div>
                </div>

                <div class="detail-row">
                    <div class="detail-item">
                        <span class="detail-label">เวลาเริ่มต้น</span>
                        <span class="detail-value">
                            <?php if ($task['start_time']): ?>
                                <?= date('d/m/Y H:i', strtotime($task['start_time'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">เวลาสิ้นสุด</span>
                        <span class="detail-value">
                            <?php if ($task['end_time']): ?>
                                <?= date('d/m/Y H:i', strtotime($task['end_time'])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- หมายเหตุการมอบหมาย -->
            <?php if ($task['notes']): ?>
            <div class="detail-card">
                <h3><i class="fas fa-sticky-note text-yellow-600"></i> หมายเหตุ</h3>
                <div class="note-box">
                    <p><?= nl2br(htmlspecialchars($task['notes'])) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- ข้อมูลเพิ่มเติม (Service-specific) -->
            <?php if ($service_details): ?>
            <div class="detail-card">
                <h3><i class="fas fa-info-circle text-blue-600"></i> ข้อมูลเพิ่มเติม</h3>
                <div class="detail-row full">
                    <?php foreach ($service_details as $key => $value): ?>
                        <?php if ($key !== 'request_id' && !is_null($value) && $value !== ''): ?>
                        <div class="detail-item" style="margin-bottom: 1rem;">
                            <span class="detail-label"><?= htmlspecialchars(str_replace('_', ' ', ucfirst($key))) ?></span>
                            <span class="detail-value text"><?= htmlspecialchars($value) ?></span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right Column - Timeline & Actions -->
        <div class="lg:col-span-1">
            <!-- Timeline -->
            <div class="detail-card">
                <h3><i class="fas fa-clock text-orange-600"></i> ประวัติการดำเนินการ</h3>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-dot">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">สร้างงาน</div>
                            <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($task['created_at'])) ?></div>
                        </div>
                    </div>

                    <?php if ($task['accepted_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot completed">
                            <i class="fas fa-check"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">รับงาน</div>
                            <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($task['accepted_at'])) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($task['started_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot completed">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">เริ่มดำเนินการ</div>
                            <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($task['started_at'])) ?></div>
                        </div>
                    </div>
                    
                    <?php if ($task['status'] === 'in_progress'): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot in_progress">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">กำลังดำเนินการ</div>
                            <div class="timeline-time text-orange-600 font-semibold">⏳ กำลังดำเนินการ...</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php elseif ($task['status'] === 'in_progress'): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot in_progress">
                            <i class="fas fa-spinner"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">กำลังดำเนินการ</div>
                            <div class="timeline-time text-orange-600 font-semibold">⏳ กำลังดำเนินการ...</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($task['completed_at']): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot completed">
                            <i class="fas fa-check-double"></i>
                        </div>
                        <div class="timeline-content">
                            <div class="timeline-label">เสร็จสิ้น</div>
                            <div class="timeline-time"><?= date('d/m/Y H:i', strtotime($task['completed_at'])) ?></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="detail-card">
                <h3>การดำเนินการ</h3>
                <div class="action-buttons">
                    <button class="btn-action btn-back" onclick="history.back()">
                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                    </button>
                </div>

                <!-- Time Input Section - Manager Only -->
                <div class="mt-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-clock text-green-600 mr-1"></i> เวลาทำงาน
                        <?php if (!$is_manager): ?>
                            <span class="ml-2 text-xs bg-gray-200 text-gray-700 px-2 py-1 rounded">📖 อ่านอย่างเดียว</span>
                        <?php endif; ?>
                    </label>
                    
                    <?php if ($is_manager): ?>
                        <!-- Manager: Editable Time Inputs -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">เวลาเริ่มต้น</label>
                                <input type="datetime-local" id="startTime" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-500 font-medium" 
                                    value="<?php echo $task['start_time'] ? date('Y-m-d\TH:i', strtotime($task['start_time'])) : ''; ?>">
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">เวลาสิ้นสุด</label>
                                <input type="datetime-local" id="endTime" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-green-500 font-medium"
                                    value="<?php echo $task['end_time'] ? date('Y-m-d\TH:i', strtotime($task['end_time'])) : ''; ?>">
                            </div>
                        </div>
                        <button class="w-full btn-action bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center justify-center gap-2" onclick="updateTaskTimes()">
                            <i class="fas fa-save"></i> บันทึกเวลา
                        </button>
                    <?php else: ?>
                        <!-- Staff: Read-only Time Display -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">เวลาเริ่มต้น</label>
                                <div class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 font-medium text-gray-700">
                                    <?php echo $task['start_time'] ? date('d/m/Y H:i', strtotime($task['start_time'])) : '—'; ?>
                                </div>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-600 mb-1 block">เวลาสิ้นสุด</label>
                                <div class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg bg-gray-100 font-medium text-gray-700">
                                    <?php echo $task['end_time'] ? date('d/m/Y H:i', strtotime($task['end_time'])) : '—'; ?>
                                </div>
                            </div>
                        </div>
                        <p class="text-sm text-gray-600 italic">
                            <i class="fas fa-lock text-gray-500 mr-1"></i> เฉพาะผู้จัดการเท่านั้นที่สามารถแก้ไขเวลาได้
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Status Change Dropdown -->
                <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                <div class="mt-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        <i class="fas fa-exchange-alt text-blue-600 mr-1"></i> เปลี่ยนสถานะงาน
                    </label>
                    <div class="flex gap-2">
                        <select id="statusSelect" class="flex-1 px-4 py-2 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-blue-500 font-medium">
                            <option value="">-- เลือกสถานะ --</option>
                            <?php if ($task['status'] === 'pending'): ?>
                                <option value="accepted">✓ รับงาน</option>
                            <?php endif; ?>
                            <?php if ($task['status'] === 'pending' || $task['status'] === 'accepted'): ?>
                                <option value="in_progress">▶ เริ่มดำเนินการ</option>
                            <?php endif; ?>
                            <option value="completed">✓✓ เสร็จสิ้น</option>
                            <option value="cancelled">✕ ยกเลิก</option>
                        </select>
                        <button class="btn-action bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-semibold transition-colors flex items-center gap-2" onclick="changeStatus()">
                            <i class="fas fa-save"></i> บันทึก
                        </button>
                    </div>
                    <p class="text-xs text-gray-500 mt-2">
                        สถานะปัจจุบัน: 
                        <span class="font-semibold text-gray-800">
                            <?php 
                            $status_labels = [
                                'pending' => 'รอการรับงาน',
                                'accepted' => 'รับงานแล้ว',
                                'in_progress' => 'กำลังดำเนินการ',
                                'completed' => 'เสร็จสิ้น',
                                'cancelled' => 'ยกเลิก'
                            ];
                            echo $status_labels[$task['status']] ?? $task['status'];
                            ?>
                        </span>
                    </p>
                </div>
                <?php else: ?>
                <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                    <p class="text-green-800 font-semibold flex items-center gap-2">
                        <i class="fas fa-check-circle"></i>
                        งานนี้<?php echo $task['status'] === 'completed' ? 'เสร็จสิ้นแล้ว' : 'ถูกยกเลิกแล้ว'; ?>
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    async function updateTaskTimes() {
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;

        if (!startTime && !endTime) {
            Swal.fire('ข้อมูลไม่ครบ', 'กรุณาใส่เวลาเริ่มต้นหรือเวลาสิ้นสุด', 'warning');
            return;
        }

        try {
            const formData = new FormData();
            formData.append('action', 'update_task_times');
            formData.append('assignment_id', <?= $assignment_id ?>);
            formData.append('start_time', startTime ? new Date(startTime).toISOString().slice(0, 19).replace('T', ' ') : '');
            formData.append('end_time', endTime ? new Date(endTime).toISOString().slice(0, 19).replace('T', ' ') : '');

            const response = await fetch('api/task_assignment_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('สำเร็จ', 'บันทึกเวลาทำงานแล้ว', 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('ผิดพลาด', data.message || 'ไม่สามารถบันทึกเวลาได้', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('ข้อผิดพลาด', 'เกิดข้อผิดพลาดในการบันทึกเวลา', 'error');
        }
    }

    async function changeStatus() {
        const select = document.getElementById('statusSelect');
        const newStatus = select.value;

        if (!newStatus) {
            Swal.fire('กรุณาเลือกสถานะ', 'โปรดเลือกสถานะที่ต้องการเปลี่ยน', 'warning');
            return;
        }

        const statusLabels = {
            'accepted': 'รับงาน',
            'in_progress': 'เริ่มดำเนินการ',
            'completed': 'เสร็จสิ้น',
            'cancelled': 'ยกเลิกงาน'
        };

        const result = await Swal.fire({
            title: 'ยืนยันการเปลี่ยนสถานะ',
            text: `คุณต้องการ ${statusLabels[newStatus]} ใช่หรือไม่?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'ยืนยัน',
            cancelButtonText: 'ยกเลิก'
        });

        if (!result.isConfirmed) return;

        try {
            const formData = new FormData();
            formData.append('action', 'update_status');
            formData.append('assignment_id', <?= $assignment_id ?>);
            formData.append('new_status', newStatus);

            const response = await fetch('api/task_assignment_api.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire('สำเร็จ', data.message, 'success').then(() => {
                    location.reload();
                });
            } else {
                Swal.fire('ผิดพลาด', data.message, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            Swal.fire('ข้อผิดพลาด', 'ไม่สามารถอัปเดตสถานะได้', 'error');
        }
    }
</script>

<?php include 'admin-layout/footer.php'; ?>
