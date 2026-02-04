<?php
/**
 * Staff Dashboard - View Assigned Tasks
 * สำหรับผู้ที่ไม่ใช่ manager เพื่อดูงานที่ได้รับมอบหมาย
 */

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// Check if user has manager/all role (redirect to admin dashboard)
$check_manager = $conn->prepare("
    SELECT COUNT(*) as cnt FROM user_roles ur
    JOIN roles r ON ur.role_id = r.role_id
    WHERE ur.user_id = ? AND r.role_code IN ('manager', 'all')
    AND ur.is_active = 1 AND r.is_active = 1
");
$check_manager->bind_param('i', $user_id);
$check_manager->execute();
$manager_result = $check_manager->get_result()->fetch_assoc();

if ($manager_result['cnt'] > 0) {
    header('Location: admin_dashboard.php');
    exit();
}

// Get user roles
$roles_query = "SELECT r.role_code, r.role_name, r.role_icon, r.role_color
                FROM user_roles ur
                JOIN roles r ON ur.role_id = r.role_id
                WHERE ur.user_id = ? AND ur.is_active = 1 AND r.is_active = 1
                ORDER BY ur.is_primary DESC";
$roles_stmt = $conn->prepare($roles_query);
$roles_stmt->bind_param('i', $user_id);
$roles_stmt->execute();
$roles_result = $roles_stmt->get_result();

$user_roles = [];
$primary_role = null;
while ($row = $roles_result->fetch_assoc()) {
    $user_roles[] = $row;
    if (!$primary_role) {
        $primary_role = $row;
    }
}

// Get assigned tasks statistics
$stats_query = "SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'accepted' THEN 1 ELSE 0 END) as accepted,
    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
FROM task_assignments
WHERE assigned_to = ?";

$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param('i', $user_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get assigned tasks
$tasks_query = "SELECT ta.*, sr.request_code, sr.service_name, sr.requester_name, sr.requester_phone, sr.requester_email,
                u_by.username as assigned_by_username,
                CONCAT(p_by.prefix_name, u_by.first_name, ' ', u_by.last_name) as assigned_by_name,
                r.role_name as assigned_role_name
                FROM task_assignments ta
                JOIN service_requests sr ON ta.request_id = sr.request_id
                JOIN users u_by ON ta.assigned_by = u_by.user_id
                LEFT JOIN prefixes p_by ON u_by.prefix_id = p_by.prefix_id
                LEFT JOIN roles r ON ta.assigned_as_role = r.role_id
                WHERE ta.assigned_to = ?
                ORDER BY 
                    CASE ta.status 
                        WHEN 'pending' THEN 1
                        WHEN 'accepted' THEN 2
                        WHEN 'in_progress' THEN 3
                        WHEN 'completed' THEN 4
                    END,
                    ta.due_date ASC";

$tasks_stmt = $conn->prepare($tasks_query);
$tasks_stmt->bind_param('i', $user_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();

$page_title = 'งานของฉัน';
$current_page = 'my_tasks';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'งานของฉัน']
];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.375rem 0.875rem;
        border-radius: 0.5rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-accepted { background-color: #dbeafe; color: #1e40af; }
    .status-in_progress { background-color: #c7d2fe; color: #3730a3; }
    .status-completed { background-color: #dcfce7; color: #15803d; }

    .stat-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        text-align: center;
    }

    .stat-card .count {
        font-size: 2rem;
        font-weight: 700;
        color: #1f2937;
    }

    .stat-card .label {
        font-size: 0.875rem;
        color: #6b7280;
        margin-top: 0.5rem;
    }

    .task-card {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 1.5rem;
        margin-bottom: 1rem;
        transition: all 0.2s ease;
    }

    .task-card:hover {
        border-color: #d1d5db;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .task-header {
        display: flex;
        justify-content: space-between;
        align-items: start;
        margin-bottom: 1rem;
    }

    .task-code {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        color: #1f2937;
        font-size: 1.125rem;
    }

    .task-service {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .task-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
        margin: 1rem 0;
        padding-bottom: 1rem;
        border-bottom: 1px solid #f3f4f6;
    }

    .task-detail-item {
        font-size: 0.875rem;
    }

    .task-detail-label {
        color: #9ca3af;
        font-weight: 500;
    }

    .task-detail-value {
        color: #1f2937;
        margin-top: 0.25rem;
    }

    .task-actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-status {
        flex: 1;
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s ease;
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

    .btn-disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .no-tasks {
        text-align: center;
        padding: 3rem 1rem;
        color: #9ca3af;
    }

    .no-tasks i {
        font-size: 3rem;
        color: #d1d5db;
        margin-bottom: 1rem;
    }
</style>

<div class="p-6">
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">
            <i class="fas fa-tasks text-blue-600"></i> งานของฉัน
        </h1>
        <p class="text-gray-600">
            บทบาท: 
            <?php foreach ($user_roles as $role): ?>
                <span class="inline-flex items-center gap-1 px-2 py-1 bg-gray-100 rounded text-sm ml-1">
                    <i class="fas <?= $role['role_icon'] ?>" style="color: <?= $role['role_color'] ?>"></i>
                    <?= htmlspecialchars($role['role_name']) ?>
                </span>
            <?php endforeach; ?>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="stat-card">
            <div class="count"><?= $stats['total'] ?? 0 ?></div>
            <div class="label">งานทั้งหมด</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['pending'] ?? 0 ?></div>
            <div class="label">รอรับงาน</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['accepted'] ?? 0 ?></div>
            <div class="label">รับงานแล้ว</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['in_progress'] ?? 0 ?></div>
            <div class="label">กำลังดำเนินการ</div>
        </div>
        <div class="stat-card">
            <div class="count"><?= $stats['completed'] ?? 0 ?></div>
            <div class="label">เสร็จสิ้น</div>
        </div>
    </div>

    <!-- Tasks List -->
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h2 class="font-semibold text-gray-800">
                <i class="fas fa-list-check text-blue-600"></i> รายการงานที่ได้รับมอบหมาย
            </h2>
        </div>

        <div class="p-6">
            <?php if ($tasks_result && $tasks_result->num_rows > 0): ?>
                <?php while ($task = $tasks_result->fetch_assoc()): ?>
                    <div class="task-card">
                        <div class="task-header">
                            <div>
                                <div class="task-code"><?= htmlspecialchars($task['request_code']) ?></div>
                                <div class="task-service"><?= htmlspecialchars($task['service_name']) ?></div>
                            </div>
                            <span class="status-badge status-<?= $task['status'] ?>">
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

                        <div class="task-details">
                            <div class="task-detail-item">
                                <div class="task-detail-label">ผู้ขอ</div>
                                <div class="task-detail-value"><?= htmlspecialchars($task['requester_name']) ?></div>
                            </div>
                            <div class="task-detail-item">
                                <div class="task-detail-label">ติดต่อ</div>
                                <div class="task-detail-value">
                                    <?php if ($task['requester_phone']): ?>
                                        <?= htmlspecialchars($task['requester_phone']) ?>
                                    <?php elseif ($task['requester_email']): ?>
                                        <?= htmlspecialchars($task['requester_email']) ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="task-detail-item">
                                <div class="task-detail-label">มอบหมายโดย</div>
                                <div class="task-detail-value"><?= htmlspecialchars($task['assigned_by_name']) ?></div>
                            </div>
                            <div class="task-detail-item">
                                <div class="task-detail-label">กำหนดส่ง</div>
                                <div class="task-detail-value">
                                    <?php if ($task['due_date']): ?>
                                        <?= date('d/m/Y H:i', strtotime($task['due_date'])) ?>
                                    <?php else: ?>
                                        ไม่มีกำหนด
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($task['notes']): ?>
                            <div style="margin: 1rem 0; padding: 1rem; background-color: #f9fafb; border-left: 4px solid #3b82f6; border-radius: 0.375rem;">
                                <p class="text-sm font-semibold text-gray-700 mb-1">หมายเหตุ:</p>
                                <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($task['notes'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <div class="task-actions">
                            <?php if ($task['status'] === 'pending'): ?>
                                <button class="btn-status btn-accept" onclick="updateTaskStatus(<?= $task['assignment_id'] ?>, 'accepted')">
                                    <i class="fas fa-check-circle"></i> รับงาน
                                </button>
                            <?php endif; ?>

                            <?php if ($task['status'] === 'accepted'): ?>
                                <button class="btn-status btn-start" onclick="updateTaskStatus(<?= $task['assignment_id'] ?>, 'in_progress')">
                                    <i class="fas fa-play-circle"></i> เริ่มดำเนินการ
                                </button>
                            <?php endif; ?>

                            <?php if ($task['status'] === 'in_progress'): ?>
                                <button class="btn-status btn-complete" onclick="updateTaskStatus(<?= $task['assignment_id'] ?>, 'completed')">
                                    <i class="fas fa-check-double"></i> ดำเนินการเสร็จ
                                </button>
                            <?php endif; ?>

                            <?php if (in_array($task['status'], ['pending', 'accepted', 'in_progress'])): ?>
                                <button class="btn-status" style="background-color: #fee2e2; color: #7f1d1d;" onclick="showTaskDetails(<?= $task['assignment_id'] ?>)">
                                    <i class="fas fa-eye"></i> ดูรายละเอียด
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-tasks">
                    <i class="fas fa-inbox"></i>
                    <p class="text-lg">ยังไม่มีงานที่ได้รับมอบหมาย</p>
                    <p class="text-sm">รอให้ผู้จัดการมอบหมายงานให้คุณ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    async function updateTaskStatus(assignmentId, newStatus) {
        const statusLabels = {
            'accepted': 'รับงาน',
            'in_progress': 'เริ่มดำเนินการ',
            'completed': 'ดำเนินการเสร็จ'
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
            formData.append('assignment_id', assignmentId);
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

    function showTaskDetails(assignmentId) {
        Swal.fire({
            title: 'รายละเอียดงาน',
            html: '<p>กำลังโหลดข้อมูล...</p>',
            icon: 'info'
        });
    }
</script>

<?php include 'admin-layout/footer.php'; ?>
