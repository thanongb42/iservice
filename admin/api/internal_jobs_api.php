<?php
/**
 * Internal Jobs API
 * CRUD สำหรับงานภายใน (ไม่ผ่านคำขอบริการ)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/line_helper.php';

header('Content-Type: application/json; charset=utf-8');

require_manager_or_admin();

$action   = $_REQUEST['action'] ?? '';
$response = ['success' => false, 'message' => 'Unknown action'];

try {
    switch ($action) {

        // ── GET: List jobs for calendar month ──────────────────────────────
        case 'list':
            $month = $_GET['month'] ?? date('Y-m');  // format: 2026-03
            if (!preg_match('/^\d{4}-\d{2}$/', $month)) $month = date('Y-m');
            [$y, $m] = explode('-', $month);
            $date_from = "$y-$m-01";
            $date_to   = date('Y-m-t', strtotime($date_from));

            $stmt = $conn->prepare("
                SELECT ij.*,
                       CONCAT(IFNULL(p.prefix_name,''), u.first_name, ' ', u.last_name) AS assigned_to_name,
                       u.profile_image AS assigned_to_image,
                       CONCAT(IFNULL(pb.prefix_name,''), ub.first_name, ' ', ub.last_name) AS assigned_by_name,
                       d.department_name
                FROM internal_jobs ij
                LEFT JOIN users u       ON ij.assigned_to   = u.user_id
                LEFT JOIN prefixes p    ON u.prefix_id       = p.prefix_id
                LEFT JOIN users ub      ON ij.assigned_by    = ub.user_id
                LEFT JOIN prefixes pb   ON ub.prefix_id      = pb.prefix_id
                LEFT JOIN departments d ON ij.department_id  = d.department_id
                WHERE ij.scheduled_date BETWEEN ? AND ?
                ORDER BY ij.scheduled_date, ij.start_time, ij.priority DESC
            ");
            $stmt->bind_param('ss', $date_from, $date_to);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $response = ['success' => true, 'jobs' => $rows, 'month' => $month];
            break;

        // ── GET: List upcoming jobs (for sidebar panel) ────────────────────
        case 'upcoming':
            $days  = intval($_GET['days'] ?? 14);
            $limit = intval($_GET['limit'] ?? 20);
            $from  = date('Y-m-d');
            $to    = date('Y-m-d', strtotime("+$days days"));

            $stmt = $conn->prepare("
                SELECT ij.job_id, ij.job_code, ij.title, ij.job_type, ij.priority,
                       ij.status, ij.scheduled_date, ij.start_time, ij.end_time, ij.location,
                       ij.assigned_to,
                       CONCAT(IFNULL(p.prefix_name,''), u.first_name, ' ', u.last_name) AS assigned_to_name,
                       u.profile_image AS assigned_to_image
                FROM internal_jobs ij
                LEFT JOIN users u    ON ij.assigned_to = u.user_id
                LEFT JOIN prefixes p ON u.prefix_id    = p.prefix_id
                WHERE ij.scheduled_date BETWEEN ? AND ?
                  AND ij.status NOT IN ('cancelled','completed')
                ORDER BY ij.scheduled_date, ij.start_time
                LIMIT ?
            ");
            $stmt->bind_param('ssi', $from, $to, $limit);
            $stmt->execute();
            $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $response = ['success' => true, 'jobs' => $rows];
            break;

        // ── GET: Single job ────────────────────────────────────────────────
        case 'get':
            $job_id = intval($_GET['job_id'] ?? 0);
            $stmt   = $conn->prepare("
                SELECT ij.*,
                       CONCAT(IFNULL(p.prefix_name,''), u.first_name, ' ', u.last_name) AS assigned_to_name,
                       u.profile_image  AS assigned_to_image,
                       u.line_user_id   AS assigned_line_id,
                       CONCAT(IFNULL(pb.prefix_name,''), ub.first_name, ' ', ub.last_name) AS assigned_by_name,
                       d.department_name
                FROM internal_jobs ij
                LEFT JOIN users u       ON ij.assigned_to   = u.user_id
                LEFT JOIN prefixes p    ON u.prefix_id       = p.prefix_id
                LEFT JOIN users ub      ON ij.assigned_by    = ub.user_id
                LEFT JOIN prefixes pb   ON ub.prefix_id      = pb.prefix_id
                LEFT JOIN departments d ON ij.department_id  = d.department_id
                WHERE ij.job_id = ?
            ");
            $stmt->bind_param('i', $job_id);
            $stmt->execute();
            $job = $stmt->get_result()->fetch_assoc();
            if ($job) {
                $response = ['success' => true, 'job' => $job];
            } else {
                $response = ['success' => false, 'message' => 'ไม่พบงาน'];
            }
            break;

        // ── GET: List assignable staff ─────────────────────────────────────
        case 'list_staff':
            $stmt = $conn->query("
                SELECT u.user_id,
                       CONCAT(IFNULL(p.prefix_name,''), u.first_name, ' ', u.last_name) AS full_name,
                       u.position, d.department_name, u.profile_image, u.line_user_id
                FROM users u
                LEFT JOIN prefixes    p ON u.prefix_id    = p.prefix_id
                LEFT JOIN departments d ON u.department_id = d.department_id
                WHERE u.role IN ('admin','staff') AND u.status = 'active'
                ORDER BY u.first_name, u.last_name
            ");
            $staff = $stmt->fetch_all(MYSQLI_ASSOC);
            $response = ['success' => true, 'staff' => $staff];
            break;

        // ── POST: Create job ────────────────────────────────────────────────
        case 'create':
            $title        = trim($_POST['title']          ?? '');
            $description  = trim($_POST['description']    ?? '');
            $job_type     = trim($_POST['job_type']        ?? 'routine');
            $service_type = trim($_POST['service_type']   ?? '') ?: null;
            $priority     = trim($_POST['priority']        ?? 'normal');
            $sched_date   = trim($_POST['scheduled_date'] ?? '') ?: null;
            $start_time   = trim($_POST['start_time']     ?? '') ?: null;
            $end_time     = trim($_POST['end_time']       ?? '') ?: null;
            $due_date     = trim($_POST['due_date']       ?? '') ?: null;
            $location     = trim($_POST['location']       ?? '');
            $dept_id      = intval($_POST['department_id'] ?? 0) ?: null;
            $assigned_to  = intval($_POST['assigned_to']  ?? 0) ?: null;
            $notes        = trim($_POST['notes']           ?? '');
            $assigned_by  = $_SESSION['user_id'];

            if (empty($title)) throw new Exception('กรุณาระบุชื่องาน');

            // Generate job_code: JOB-2026-0001
            $year     = date('Y');
            $last_row = $conn->query("SELECT job_code FROM internal_jobs WHERE job_code LIKE 'JOB-$year-%' ORDER BY job_id DESC LIMIT 1")->fetch_assoc();
            $seq      = $last_row ? intval(substr($last_row['job_code'], -4)) + 1 : 1;
            $job_code = 'JOB-' . $year . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $stmt = $conn->prepare("
                INSERT INTO internal_jobs
                    (job_code, title, description, job_type, service_type, priority,
                     assigned_to, assigned_by, department_id,
                     scheduled_date, start_time, end_time, due_date,
                     location, notes)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");
            $stmt->bind_param('sssssiiiissssss',
                $job_code, $title, $description, $job_type, $service_type, $priority,
                $assigned_to, $assigned_by, $dept_id,
                $sched_date, $start_time, $end_time, $due_date,
                $location, $notes
            );
            if (!$stmt->execute()) throw new Exception('บันทึกไม่สำเร็จ: ' . $stmt->error);
            $job_id = $conn->insert_id;

            // LINE notification if assigned
            if ($assigned_to) {
                _sendJobLineNotify($job_id, 'assigned', $conn);
            }

            $response = ['success' => true, 'message' => 'สร้างงานสำเร็จ', 'job_id' => $job_id, 'job_code' => $job_code];
            break;

        // ── POST: Update job ────────────────────────────────────────────────
        case 'update':
            $job_id       = intval($_POST['job_id']         ?? 0);
            $title        = trim($_POST['title']            ?? '');
            $description  = trim($_POST['description']      ?? '');
            $job_type     = trim($_POST['job_type']          ?? 'routine');
            $service_type = trim($_POST['service_type']     ?? '') ?: null;
            $priority     = trim($_POST['priority']          ?? 'normal');
            $sched_date   = trim($_POST['scheduled_date']   ?? '') ?: null;
            $start_time   = trim($_POST['start_time']       ?? '') ?: null;
            $end_time     = trim($_POST['end_time']         ?? '') ?: null;
            $due_date     = trim($_POST['due_date']         ?? '') ?: null;
            $location     = trim($_POST['location']         ?? '');
            $dept_id      = intval($_POST['department_id']  ?? 0) ?: null;
            $notes        = trim($_POST['notes']             ?? '');

            if (empty($title)) throw new Exception('กรุณาระบุชื่องาน');

            // Check old assigned_to before update
            $old = $conn->query("SELECT assigned_to FROM internal_jobs WHERE job_id = $job_id")->fetch_assoc();

            $stmt = $conn->prepare("
                UPDATE internal_jobs
                SET title=?, description=?, job_type=?, service_type=?, priority=?,
                    scheduled_date=?, start_time=?, end_time=?, due_date=?,
                    location=?, notes=?, department_id=?
                WHERE job_id=?
            ");
            $stmt->bind_param('sssssssssssii',
                $title, $description, $job_type, $service_type, $priority,
                $sched_date, $start_time, $end_time, $due_date,
                $location, $notes, $dept_id, $job_id
            );
            if (!$stmt->execute()) throw new Exception('บันทึกไม่สำเร็จ');

            $response = ['success' => true, 'message' => 'อัปเดตงานสำเร็จ'];
            break;

        // ── POST: Assign staff ──────────────────────────────────────────────
        case 'assign':
            $job_id     = intval($_POST['job_id']     ?? 0);
            $assigned_to = intval($_POST['assigned_to'] ?? 0) ?: null;

            // Check old
            $old = $conn->query("SELECT assigned_to FROM internal_jobs WHERE job_id = $job_id")->fetch_assoc();

            $stmt = $conn->prepare("UPDATE internal_jobs SET assigned_to=? WHERE job_id=?");
            $stmt->bind_param('ii', $assigned_to, $job_id);
            if (!$stmt->execute()) throw new Exception('บันทึกไม่สำเร็จ');

            if ($assigned_to && $assigned_to !== ($old['assigned_to'] ?? null)) {
                _sendJobLineNotify($job_id, 'assigned', $conn);
            }

            $response = ['success' => true, 'message' => $assigned_to ? 'มอบหมายงานสำเร็จ' : 'ยกเลิกการมอบหมายสำเร็จ'];
            break;

        // ── POST: Update status ─────────────────────────────────────────────
        case 'update_status':
            $job_id = intval($_POST['job_id'] ?? 0);
            $status = trim($_POST['status']   ?? '');
            $notes  = trim($_POST['notes']    ?? '');
            $allowed = ['scheduled','in_progress','completed','cancelled'];
            if (!in_array($status, $allowed)) throw new Exception('สถานะไม่ถูกต้อง');

            $extra = '';
            if ($status === 'in_progress') $extra = ', started_at = NOW()';
            if ($status === 'completed')   $extra = ', completed_at = NOW()';

            $stmt = $conn->prepare("UPDATE internal_jobs SET status=?, completion_notes=? $extra WHERE job_id=?");
            $stmt->bind_param('ssi', $status, $notes, $job_id);
            if (!$stmt->execute()) throw new Exception('บันทึกไม่สำเร็จ');

            $response = ['success' => true, 'message' => 'อัปเดตสถานะสำเร็จ'];
            break;

        // ── POST: Delete ────────────────────────────────────────────────────
        case 'delete':
            $job_id = intval($_POST['job_id'] ?? 0);
            $stmt   = $conn->prepare("DELETE FROM internal_jobs WHERE job_id=?");
            $stmt->bind_param('i', $job_id);
            if (!$stmt->execute()) throw new Exception('ลบไม่สำเร็จ');
            $response = ['success' => true, 'message' => 'ลบงานสำเร็จ'];
            break;

        default:
            $response = ['success' => false, 'message' => "Unknown action: $action"];
    }

} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);

// ── Helper: LINE push notification for internal jobs ──────────────────────────
function _sendJobLineNotify(int $job_id, string $event, $conn): void {
    try {
        $stmt = $conn->prepare("
            SELECT ij.job_code, ij.title, ij.scheduled_date, ij.start_time, ij.location,
                   u.line_user_id
            FROM internal_jobs ij
            JOIN users u ON ij.assigned_to = u.user_id
            WHERE ij.job_id = ?
        ");
        $stmt->bind_param('i', $job_id);
        $stmt->execute();
        $data = $stmt->get_result()->fetch_assoc();
        if (!$data || empty($data['line_user_id'])) return;

        $date_str = $data['scheduled_date']
            ? thdate('d M Y', strtotime($data['scheduled_date']))
              . ($data['start_time'] ? ' เวลา ' . substr($data['start_time'], 0, 5) . ' น.' : '')
            : 'ยังไม่กำหนด';

        if ($event === 'assigned') {
            $msg  = "📋 มอบหมายงานใหม่\n";
        } else {
            $msg  = "⚠️ งานถูกยกเลิก\n";
        }
        $msg .= "────────────────\n";
        $msg .= "รหัส: {$data['job_code']}\n";
        $msg .= "งาน: {$data['title']}\n";
        $msg .= "วัน: $date_str\n";
        if ($data['location']) $msg .= "สถานที่: {$data['location']}\n";
        $msg .= "────────────────\n";
        $msg .= "ดูรายละเอียด: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
            . '://' . $_SERVER['HTTP_HOST'] . '/iservice/admin/create_job.php';

        send_line_push_to_user($data['line_user_id'], $msg, $conn);
    } catch (Exception $e) {
        error_log('_sendJobLineNotify error: ' . $e->getMessage());
    }
}
