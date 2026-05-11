<?php
/**
 * Internal Jobs API
 * CRUD สำหรับงานภายใน (ไม่ผ่านคำขอบริการ)
 */

session_start();
require_once '../../config/database.php';
require_once '../../includes/line_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Auth: manager/admin can do everything.
// Exception: assigned staff can update_status of their own job.
$action = $_REQUEST['action'] ?? '';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'กรุณาเข้าสู่ระบบ']);
    exit;
}
if ($action !== 'update_status') {
    require_manager_or_admin();
}

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
                       d.department_name,
                       (SELECT GROUP_CONCAT(CONCAT(IFNULL(p2.prefix_name,''), u2.first_name,' ',u2.last_name) ORDER BY ja2.assigned_at SEPARATOR ', ')
                        FROM job_assignments ja2
                        JOIN users u2 ON ja2.user_id = u2.user_id
                        LEFT JOIN prefixes p2 ON u2.prefix_id = p2.prefix_id
                        WHERE ja2.job_id = ij.job_id
                       ) AS assignees_names
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
                       u.profile_image AS assigned_to_image,
                       (SELECT GROUP_CONCAT(CONCAT(IFNULL(p2.prefix_name,''), u2.first_name,' ',u2.last_name) ORDER BY ja2.assigned_at SEPARATOR ', ')
                        FROM job_assignments ja2
                        JOIN users u2 ON ja2.user_id = u2.user_id
                        LEFT JOIN prefixes p2 ON u2.prefix_id = p2.prefix_id
                        WHERE ja2.job_id = ij.job_id
                       ) AS assignees_names
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
                // Load assignees from job_assignments
                $ass_s = $conn->prepare("
                    SELECT ja.user_id,
                           CONCAT(IFNULL(p.prefix_name,''), u.first_name,' ', u.last_name) AS full_name,
                           u.profile_image, u.line_user_id
                    FROM job_assignments ja
                    JOIN users u ON ja.user_id = u.user_id
                    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                    WHERE ja.job_id = ?
                    ORDER BY ja.assigned_at
                ");
                $ass_s->bind_param('i', $job_id);
                $ass_s->execute();
                $job['assignees'] = $ass_s->get_result()->fetch_all(MYSQLI_ASSOC);

                // Load attachments
                $att_s = $conn->prepare("SELECT * FROM job_attachments WHERE job_id=? ORDER BY created_at");
                $att_s->bind_param('i', $job_id);
                $att_s->execute();
                $job['attachments'] = $att_s->get_result()->fetch_all(MYSQLI_ASSOC);
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
            $notes        = trim($_POST['notes']           ?? '');
            $assigned_by  = $_SESSION['user_id'];

            // Multi-assignee: accept assignees[] array, fallback to legacy assigned_to
            $assignees = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['assignees'] ?? [])))));
            if (empty($assignees) && !empty($_POST['assigned_to'])) {
                $av = intval($_POST['assigned_to']);
                if ($av > 0) $assignees = [$av];
            }
            $assigned_to = $assignees[0] ?? null; // primary assignee for backward compat

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
            $stmt->bind_param('ssssssiiissssss',
                $job_code, $title, $description, $job_type, $service_type, $priority,
                $assigned_to, $assigned_by, $dept_id,
                $sched_date, $start_time, $end_time, $due_date,
                $location, $notes
            );
            if (!$stmt->execute()) throw new Exception('บันทึกไม่สำเร็จ: ' . $stmt->error);
            $job_id = $conn->insert_id;

            // Save multi-assignees to job_assignments
            if (!empty($assignees)) {
                _saveJobAssignments($job_id, $assignees, $assigned_by, $conn);
                _sendJobLineNotifyMulti($job_id, 'assigned', $conn);
            }

            // Save attachments (files + URLs)
            _saveAttachments($job_id, $conn);

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
            $editor_id    = $_SESSION['user_id'];

            // Multi-assignee
            $assignees = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['assignees'] ?? [])))));
            if (empty($assignees) && !empty($_POST['assigned_to'])) {
                $av = intval($_POST['assigned_to']);
                if ($av > 0) $assignees = [$av];
            }
            $assigned_to = $assignees[0] ?? null;

            if (empty($title)) throw new Exception('กรุณาระบุชื่องาน');

            // Fetch existing assignees to detect new ones
            $old_ass_r = $conn->query("SELECT user_id FROM job_assignments WHERE job_id = $job_id");
            $old_assignees = $old_ass_r ? array_column($old_ass_r->fetch_all(MYSQLI_ASSOC), 'user_id') : [];

            $stmt = $conn->prepare("
                UPDATE internal_jobs
                SET title=?, description=?, job_type=?, service_type=?, priority=?,
                    scheduled_date=?, start_time=?, end_time=?, due_date=?,
                    location=?, notes=?, department_id=?, assigned_to=?
                WHERE job_id=?
            ");
            $stmt->bind_param('sssssssssssiii',
                $title, $description, $job_type, $service_type, $priority,
                $sched_date, $start_time, $end_time, $due_date,
                $location, $notes, $dept_id, $assigned_to, $job_id
            );
            if (!$stmt->execute()) throw new Exception('บันทึกไม่สำเร็จ');

            // Replace job_assignments and notify newly added assignees
            $del = $conn->prepare("DELETE FROM job_assignments WHERE job_id=?");
            $del->bind_param('i', $job_id);
            $del->execute();
            if (!empty($assignees)) {
                _saveJobAssignments($job_id, $assignees, $editor_id, $conn);
                // Notify only new assignees
                $new_assignees = array_diff($assignees, $old_assignees);
                if (!empty($new_assignees)) {
                    _sendJobLineNotifyMulti($job_id, 'assigned', $conn, $new_assignees);
                }
            }

            // Save new attachments (files + URLs added during edit)
            _saveAttachments($job_id, $conn);

            $response = ['success' => true, 'message' => 'อัปเดตงานสำเร็จ'];
            break;

        // ── POST: Delete attachment ─────────────────────────────────────────
        case 'delete_attachment':
            $att_id = intval($_POST['att_id'] ?? 0);
            $att_s  = $conn->prepare("SELECT file_path, attach_type FROM job_attachments WHERE id=?");
            $att_s->bind_param('i', $att_id);
            $att_s->execute();
            $att = $att_s->get_result()->fetch_assoc();
            if ($att && $att['attach_type'] === 'file' && !empty($att['file_path'])) {
                $full = dirname(__DIR__, 2) . '/' . $att['file_path'];
                if (file_exists($full)) @unlink($full);
            }
            $del = $conn->prepare("DELETE FROM job_attachments WHERE id=?");
            $del->bind_param('i', $att_id);
            $del->execute();
            $response = ['success' => true, 'message' => 'ลบไฟล์แนบสำเร็จ'];
            break;

        // ── POST: Assign staff ──────────────────────────────────────────────
        case 'assign':
            $job_id    = intval($_POST['job_id'] ?? 0);
            $assigner  = $_SESSION['user_id'];

            // Accept assignees[] array; fallback to legacy assigned_to
            $assignees = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['assignees'] ?? [])))));
            if (empty($assignees) && !empty($_POST['assigned_to'])) {
                $av = intval($_POST['assigned_to']);
                if ($av > 0) $assignees = [$av];
            }
            $assigned_to = $assignees[0] ?? null;

            // Detect new assignees before replacing
            $old_r = $conn->query("SELECT user_id FROM job_assignments WHERE job_id = $job_id");
            $old_assignees = $old_r ? array_column($old_r->fetch_all(MYSQLI_ASSOC), 'user_id') : [];

            // Replace job_assignments
            $del = $conn->prepare("DELETE FROM job_assignments WHERE job_id=?");
            $del->bind_param('i', $job_id);
            $del->execute();

            // Update primary assigned_to
            $upd = $conn->prepare("UPDATE internal_jobs SET assigned_to=? WHERE job_id=?");
            $upd->bind_param('ii', $assigned_to, $job_id);
            if (!$upd->execute()) throw new Exception('บันทึกไม่สำเร็จ');

            if (!empty($assignees)) {
                _saveJobAssignments($job_id, $assignees, $assigner, $conn);
                $new_assignees = array_diff($assignees, $old_assignees);
                if (!empty($new_assignees)) {
                    _sendJobLineNotifyMulti($job_id, 'assigned', $conn, $new_assignees);
                }
            }

            $response = ['success' => true, 'message' => !empty($assignees) ? 'มอบหมายงานสำเร็จ' : 'ยกเลิกการมอบหมายสำเร็จ'];
            break;

        // ── POST: Update status ─────────────────────────────────────────────
        case 'update_status':
            $job_id = intval($_POST['job_id'] ?? 0);
            $status = trim($_POST['status']   ?? '');
            $notes  = trim($_POST['notes']    ?? '');
            $allowed = ['scheduled','in_progress','completed','cancelled'];
            if (!in_array($status, $allowed)) throw new Exception('สถานะไม่ถูกต้อง');

            // Permission: manager/admin OR assigned person
            $is_mgr = (($_SESSION['role'] ?? '') === 'admin');
            if (!$is_mgr) {
                $uid = intval($_SESSION['user_id']);
                $chk = $conn->prepare("SELECT COUNT(*) as cnt FROM user_roles ur JOIN roles r ON ur.role_id=r.role_id WHERE ur.user_id=? AND r.role_code IN ('manager','all') AND ur.is_active=1 AND r.is_active=1");
                $chk->bind_param('i', $uid);
                $chk->execute();
                $is_mgr = $chk->get_result()->fetch_assoc()['cnt'] > 0;
            }
            if (!$is_mgr) {
                $uid = intval($_SESSION['user_id']);
                // Allow if user is any assignee (job_assignments) or legacy assigned_to
                $own = $conn->prepare("
                    SELECT 1 FROM job_assignments WHERE job_id=? AND user_id=?
                    UNION
                    SELECT 1 FROM internal_jobs WHERE job_id=? AND assigned_to=?
                    LIMIT 1
                ");
                $own->bind_param('iiii', $job_id, $uid, $job_id, $uid);
                $own->execute();
                if (!$own->get_result()->fetch_assoc()) throw new Exception('ไม่มีสิทธิ์อัปเดตสถานะงานนี้');
            }

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

// ── Helper: save file uploads + URL links ─────────────────────────────────────
function _saveAttachments(int $job_id, $conn): void {
    $uploaded_by = $_SESSION['user_id'];
    $upload_dir  = dirname(__DIR__, 2) . '/storage/uploads/jobs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    // Files
    if (!empty($_FILES['attachments']['name'][0])) {
        $allowed = ['jpg','jpeg','png','gif','pdf','doc','docx','xls','xlsx'];
        foreach ($_FILES['attachments']['name'] as $i => $orig) {
            if ($_FILES['attachments']['error'][$i] !== UPLOAD_ERR_OK) continue;
            $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) continue;
            if ($_FILES['attachments']['size'][$i] > 20 * 1024 * 1024) continue;
            $fname = 'job_' . $job_id . '_' . uniqid() . '.' . $ext;
            if (!move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $upload_dir . $fname)) continue;
            $fpath = 'storage/uploads/jobs/' . $fname;
            $mime  = $_FILES['attachments']['type'][$i];
            $fsize = $_FILES['attachments']['size'][$i];
            $s = $conn->prepare("INSERT INTO job_attachments (job_id,attach_type,label,file_path,mime_type,file_size,uploaded_by) VALUES (?,'file',?,?,?,?,?)");
            $s->bind_param('isssii', $job_id, $orig, $fpath, $mime, $fsize, $uploaded_by);
            $s->execute();
        }
    }

    // URL links
    $url_links  = (array)($_POST['url_links']  ?? []);
    $url_labels = (array)($_POST['url_labels'] ?? []);
    foreach ($url_links as $i => $url) {
        $url = trim($url);
        if (empty($url)) continue;
        $label = trim($url_labels[$i] ?? '') ?: $url;
        $s = $conn->prepare("INSERT INTO job_attachments (job_id,attach_type,label,url,uploaded_by) VALUES (?,'url',?,?,?)");
        $s->bind_param('issi', $job_id, $label, $url, $uploaded_by);
        $s->execute();
    }
}

// ── Helper: save multiple assignees to job_assignments ─────────────────────────
function _saveJobAssignments(int $job_id, array $assignees, int $assigned_by, $conn): void {
    $ins = $conn->prepare("INSERT IGNORE INTO job_assignments (job_id, user_id, assigned_by) VALUES (?,?,?)");
    foreach ($assignees as $uid) {
        $ins->bind_param('iii', $job_id, $uid, $assigned_by);
        $ins->execute();
    }
}

// ── Helper: LINE push notification to all assignees ────────────────────────────
// $only_users: optional array of user_ids to restrict who gets notified
function _sendJobLineNotifyMulti(int $job_id, string $event, $conn, array $only_users = []): void {
    try {
        // Fetch job info
        $js = $conn->prepare("SELECT job_code, title, scheduled_date, start_time, location FROM internal_jobs WHERE job_id=?");
        $js->bind_param('i', $job_id);
        $js->execute();
        $job = $js->get_result()->fetch_assoc();
        if (!$job) return;

        // Fetch all assignees' LINE IDs from job_assignments
        $as = $conn->prepare("
            SELECT ja.user_id, u.line_user_id
            FROM job_assignments ja
            JOIN users u ON ja.user_id = u.user_id
            WHERE ja.job_id = ? AND u.line_user_id IS NOT NULL AND u.line_user_id != ''
        ");
        $as->bind_param('i', $job_id);
        $as->execute();
        $assignees = $as->get_result()->fetch_all(MYSQLI_ASSOC);

        if (empty($assignees)) return;

        $date_str = $job['scheduled_date']
            ? thdate('d M Y', strtotime($job['scheduled_date']))
              . ($job['start_time'] ? ' เวลา ' . substr($job['start_time'], 0, 5) . ' น.' : '')
            : 'ยังไม่กำหนด';

        $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $url    = "{$scheme}://{$_SERVER['HTTP_HOST']}/admin/internal_job_detail.php?id={$job_id}";

        $msg  = ($event === 'assigned' ? "📋 มอบหมายงานใหม่\n" : "⚠️ งานถูกยกเลิก\n");
        $msg .= "────────────────\n";
        $msg .= "รหัส: {$job['job_code']}\n";
        $msg .= "งาน: {$job['title']}\n";
        $msg .= "วัน: $date_str\n";
        if ($job['location']) $msg .= "สถานที่: {$job['location']}\n";
        $msg .= "────────────────\n";
        $msg .= "ดูรายละเอียด: {$url}";

        foreach ($assignees as $a) {
            if (!empty($only_users) && !in_array((int)$a['user_id'], $only_users)) continue;
            send_line_push_to_user($a['line_user_id'], $msg, $conn);
        }
    } catch (Exception $e) {
        error_log('_sendJobLineNotifyMulti error: ' . $e->getMessage());
    }
}
