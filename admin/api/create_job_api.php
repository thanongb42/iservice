<?php
/**
 * Create Job API
 * Admin creates a service ticket on behalf of a user
 */

require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$admin_user_id = intval($_SESSION['user_id']);

$conn->begin_transaction();

try {
    // 1. Validate required fields
    $service_code = isset($_POST['service_code']) ? clean_input($_POST['service_code']) : '';
    $requester_name = clean_input($_POST['requester_name'] ?? '');
    $dept_id = intval($_POST['department_id'] ?? 0);

    if (empty($service_code) || empty($requester_name) || $dept_id <= 0) {
        throw new Exception('กรุณากรอกข้อมูลที่จำเป็น: ประเภทบริการ, ชื่อผู้ขอรับบริการ, หน่วยงาน');
    }

    // Validate service_code against my_service table
    $svc_stmt = $conn->prepare("SELECT service_name FROM my_service WHERE service_code = ? AND is_active = 1");
    $svc_stmt->bind_param('s', $service_code);
    $svc_stmt->execute();
    $svc_row = $svc_stmt->get_result()->fetch_assoc();
    if (!$svc_row) {
        throw new Exception('ประเภทบริการไม่ถูกต้องหรือไม่เปิดใช้งาน');
    }
    $service_name = $svc_row['service_name'];

    // 2. Generate request_code (same algorithm as process_request.php)
    $year = date('Y');
    $last_req = $conn->query("SELECT request_code FROM service_requests WHERE request_code LIKE 'REQ-$year-%' ORDER BY request_id DESC LIMIT 1")->fetch_assoc();
    if ($last_req) {
        $last_num = intval(substr($last_req['request_code'], -4));
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }
    $request_code = "REQ-$year-$new_num";

    // 3. Get department name
    $dept_stmt = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
    $dept_stmt->bind_param('i', $dept_id);
    $dept_stmt->execute();
    $dept_row = $dept_stmt->get_result()->fetch_assoc();
    $dept_name = $dept_row ? $dept_row['department_name'] : '';

    // 4. Collect other fields
    $requester_email    = clean_input($_POST['requester_email'] ?? '');
    $requester_phone    = clean_input($_POST['requester_phone'] ?? '');
    $requester_position = clean_input($_POST['requester_position'] ?? '');
    $description        = clean_input($_POST['description'] ?? '');
    $priority           = in_array($_POST['priority'] ?? '', ['low','medium','high','urgent']) ? $_POST['priority'] : 'medium';
    $expected_date      = !empty($_POST['expected_completion_date']) ? clean_input($_POST['expected_completion_date']) : null;

    // Build subject
    $subject = $service_name . ' - ' . $requester_name;

    // 5. File upload handling (same pattern as process_request.php)
    $attachments_json = null;
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $uploaded_files = [];
        $base_upload_path = __DIR__ . '/../../storage/uploads/requests/' . date('Y') . '/' . date('m') . '/';
        if (!file_exists($base_upload_path)) {
            mkdir($base_upload_path, 0777, true);
        }
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $total = count($_FILES['attachments']['name']);
        for ($i = 0; $i < $total; $i++) {
            if ($_FILES['attachments']['error'][$i] === 0) {
                $base_name = basename(str_replace('\\', '/', $_FILES['attachments']['name'][$i]));
                $file_ext = strtolower(pathinfo($base_name, PATHINFO_EXTENSION));
                if (in_array($file_ext, $allowed_types) && $_FILES['attachments']['size'][$i] <= 5242880) {
                    $new_file_name = uniqid('REQ_', true) . '.' . $file_ext;
                    $dest = $base_upload_path . $new_file_name;
                    if (move_uploaded_file($_FILES['attachments']['tmp_name'][$i], $dest)) {
                        $uploaded_files[] = 'storage/uploads/requests/' . date('Y') . '/' . date('m') . '/' . $new_file_name;
                    }
                }
            }
        }
        if (!empty($uploaded_files)) {
            $attachments_json = json_encode($uploaded_files, JSON_UNESCAPED_UNICODE);
        }
    }

    // 6. Insert main service_requests record
    // admin_notes: mark as admin-created
    $admin_note = '[สร้างโดยแอดมิน: ' . ($_SESSION['username'] ?? 'admin') . ']';
    if (!empty($_POST['admin_notes'])) {
        $admin_note .= ' ' . clean_input($_POST['admin_notes']);
    }

    $stmt = $conn->prepare("INSERT INTO service_requests (
        user_id, request_code, service_code, service_name,
        requester_name, requester_email, requester_phone, requester_position,
        department_id, department_name, subject, description,
        priority, expected_completion_date, attachments, admin_notes
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param('isssssssissssssss',
        $admin_user_id, $request_code, $service_code, $service_name,
        $requester_name, $requester_email, $requester_phone, $requester_position,
        $dept_id, $dept_name, $subject, $description,
        $priority, $expected_date, $attachments_json, $admin_note
    );

    if (!$stmt->execute()) {
        throw new Exception('Database insert failed: ' . $stmt->error);
    }
    $request_id = $conn->insert_id;

    // 7. Insert service-specific detail record (same switch-case as process_request.php)
    $detail_stmt = null;
    switch ($service_code) {
        case 'EMAIL':
            $detail_stmt = $conn->prepare("INSERT INTO request_email_details (request_id, requested_username, email_format, quota_mb, purpose, is_new_account, existing_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $firstname_en = clean_input($_POST['firstname_en'] ?? '');
            $lastname_en  = clean_input($_POST['lastname_en'] ?? '');
            $username_val = trim($firstname_en . ' ' . $lastname_en);
            $email_format = clean_input($_POST['email_format'] ?? '');
            $quota        = 2048;
            $purpose      = clean_input($_POST['purpose'] ?? '');
            $is_new       = intval($_POST['is_new_account'] ?? 1);
            $existing     = clean_input($_POST['existing_email'] ?? '');
            $detail_stmt->bind_param('issisis', $request_id, $username_val, $email_format, $quota, $purpose, $is_new, $existing);
            break;

        case 'NAS':
            $detail_stmt = $conn->prepare("INSERT INTO request_nas_details (request_id, folder_name, storage_size_gb, permission_type, shared_with, purpose, backup_required) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $folder     = clean_input($_POST['folder_name'] ?? '');
            $size       = intval($_POST['storage_size_gb'] ?? 0);
            $permission = clean_input($_POST['permission_type'] ?? '');
            $shared     = clean_input($_POST['shared_with'] ?? '');
            $purpose    = clean_input($_POST['purpose'] ?? '');
            $backup     = intval($_POST['backup_required'] ?? 0);
            $detail_stmt->bind_param('isisssi', $request_id, $folder, $size, $permission, $shared, $purpose, $backup);
            break;

        case 'IT_SUPPORT':
            $detail_stmt = $conn->prepare("INSERT INTO request_it_support_details (request_id, issue_type, device_type, device_brand, symptoms, location, urgency_level, error_message, when_occurred) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $issue_type  = clean_input($_POST['issue_type'] ?? '');
            $device_type = clean_input($_POST['device_type'] ?? '');
            $device_brand= clean_input($_POST['device_brand'] ?? '');
            $symptoms    = clean_input($_POST['symptoms'] ?? '');
            $location    = clean_input($_POST['location'] ?? '');
            $urgency     = clean_input($_POST['urgency_level'] ?? '');
            $error_msg   = clean_input($_POST['error_message'] ?? '');
            $when        = clean_input($_POST['when_occurred'] ?? '');
            $detail_stmt->bind_param('issssssss', $request_id, $issue_type, $device_type, $device_brand, $symptoms, $location, $urgency, $error_msg, $when);
            break;

        case 'INTERNET':
            $detail_stmt = $conn->prepare("INSERT INTO request_internet_details (request_id, request_type, location, building, room_number, current_issue) VALUES (?, ?, ?, ?, ?, ?)");
            $req_type = clean_input($_POST['request_type'] ?? '');
            $location = clean_input($_POST['location'] ?? '');
            $building = clean_input($_POST['building'] ?? '');
            $room     = clean_input($_POST['room_number'] ?? '');
            $issue    = clean_input($_POST['current_issue'] ?? '');
            $detail_stmt->bind_param('isssss', $request_id, $req_type, $location, $building, $room, $issue);
            break;

        case 'QR_CODE':
            $detail_stmt = $conn->prepare("INSERT INTO request_qrcode_details (request_id, qr_type, qr_content, qr_size, color_primary, color_background, logo_url, output_format, quantity, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $qr_type  = clean_input($_POST['qr_type'] ?? '');
            $content  = clean_input($_POST['qr_content'] ?? '');
            $qr_size  = clean_input($_POST['qr_size'] ?? '');
            $color1   = clean_input($_POST['color_primary'] ?? '#000000');
            $color2   = clean_input($_POST['color_background'] ?? '#ffffff');
            $logo     = clean_input($_POST['logo_url'] ?? '');
            $format   = clean_input($_POST['output_format'] ?? '');
            $qty      = intval($_POST['quantity'] ?? 1);
            $purpose  = clean_input($_POST['purpose'] ?? '');
            $detail_stmt->bind_param('issssssis', $request_id, $qr_type, $content, $qr_size, $color1, $color2, $logo, $format, $qty, $purpose);
            break;

        case 'PHOTOGRAPHY':
            $detail_stmt = $conn->prepare("INSERT INTO request_photography_details (request_id, event_name, event_type, event_date, event_time_start, event_time_end, event_location, number_of_photographers, video_required, drone_required, delivery_format, special_requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $event_name    = clean_input($_POST['event_name'] ?? '');
            $event_type    = clean_input($_POST['event_type'] ?? '');
            $event_date    = clean_input($_POST['event_date'] ?? '');
            $time_start_h  = clean_input($_POST['event_time_start_h'] ?? '00');
            $time_start_m  = clean_input($_POST['event_time_start_m'] ?? '00');
            $time_start    = $time_start_h . ':' . $time_start_m;
            $time_end_h    = clean_input($_POST['event_time_end_h'] ?? '');
            $time_end_m    = clean_input($_POST['event_time_end_m'] ?? '');
            $time_end      = ($time_end_h !== '') ? $time_end_h . ':' . $time_end_m : '';
            $event_loc     = clean_input($_POST['event_location'] ?? '');
            $photographers = intval($_POST['number_of_photographers'] ?? 1);
            $photo_types   = $_POST['photo_type'] ?? [];
            $video         = in_array('video', $photo_types) ? 1 : 0;
            $drone         = 0;
            $delivery      = clean_input($_POST['delivery_format'] ?? '');
            $special       = clean_input($_POST['notes'] ?? '');
            $detail_stmt->bind_param('isssssiiiiss', $request_id, $event_name, $event_type, $event_date, $time_start, $time_end, $event_loc, $photographers, $video, $drone, $delivery, $special);
            break;

        case 'WEB_DESIGN':
            $detail_stmt = $conn->prepare("INSERT INTO request_webdesign_details (request_id, website_type, project_name, purpose, target_audience, number_of_pages, features_required, has_existing_site, existing_url, domain_name, hosting_required, reference_sites, color_preferences, budget) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $web_type    = clean_input($_POST['website_type'] ?? '');
            $proj_name   = clean_input($_POST['project_name'] ?? '');
            $purpose     = clean_input($_POST['purpose'] ?? '');
            $audience    = clean_input($_POST['target_audience'] ?? '');
            $pages       = intval($_POST['number_of_pages'] ?? 1);
            $features    = clean_input($_POST['features_required'] ?? '');
            $has_site    = intval($_POST['has_existing_site'] ?? 0);
            $existing_url= clean_input($_POST['existing_url'] ?? '');
            $domain      = clean_input($_POST['domain_name'] ?? '');
            $hosting     = intval($_POST['hosting_required'] ?? 0);
            $references  = clean_input($_POST['reference_sites'] ?? '');
            $colors      = clean_input($_POST['color_preferences'] ?? '');
            $budget      = clean_input($_POST['budget'] ?? '');
            $detail_stmt->bind_param('issssisisssss', $request_id, $web_type, $proj_name, $purpose, $audience, $pages, $features, $has_site, $existing_url, $domain, $hosting, $references, $colors, $budget);
            break;

        case 'PRINTER':
            $detail_stmt = $conn->prepare("INSERT INTO request_printer_details (request_id, issue_type, printer_type, printer_brand, printer_model, serial_number, location, problem_description, error_code, toner_color, supplies_needed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $issue        = clean_input($_POST['issue_type'] ?? '');
            $printer_type = clean_input($_POST['printer_type'] ?? '');
            $brand        = clean_input($_POST['printer_brand'] ?? '');
            $model        = clean_input($_POST['printer_model'] ?? '');
            $serial       = clean_input($_POST['serial_number'] ?? '');
            $location     = clean_input($_POST['location'] ?? '');
            $problem      = clean_input($_POST['problem_description'] ?? '');
            $error_code   = clean_input($_POST['error_code'] ?? '');
            $toner        = clean_input($_POST['toner_color'] ?? '');
            $supplies     = clean_input($_POST['supplies_needed'] ?? '');
            $detail_stmt->bind_param('issssssssss', $request_id, $issue, $printer_type, $brand, $model, $serial, $location, $problem, $error_code, $toner, $supplies);
            break;

        case 'MC':
            $detail_stmt = $conn->prepare("INSERT INTO request_mc_details (request_id, event_name, event_type, event_date, event_time_start, event_time_end, location, mc_count, language, script_status, dress_code, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $event_name  = clean_input($_POST['event_name'] ?? '');
            $event_type  = clean_input($_POST['event_type'] ?? '');
            $event_date  = clean_input($_POST['event_date'] ?? '');
            $start_h     = clean_input($_POST['event_time_start_h'] ?? '00');
            $start_m     = clean_input($_POST['event_time_start_m'] ?? '00');
            $start       = $start_h . ':' . $start_m;
            $end_h       = clean_input($_POST['event_time_end_h'] ?? '');
            $end_m       = clean_input($_POST['event_time_end_m'] ?? '');
            $end         = ($end_h !== '') ? $end_h . ':' . $end_m : '';
            $loc         = clean_input($_POST['location'] ?? '');
            $count       = intval($_POST['mc_count'] ?? 1);
            $lang        = clean_input($_POST['language'] ?? '');
            $script      = clean_input($_POST['script_status'] ?? '');
            $dress       = clean_input($_POST['dress_code'] ?? '');
            $note        = '';
            $detail_stmt->bind_param('issssssissss', $request_id, $event_name, $event_type, $event_date, $start, $end, $loc, $count, $lang, $script, $dress, $note);
            break;

        case 'LED':
            $media_title      = clean_input($_POST['media_title'] ?? '');
            $media_type       = clean_input($_POST['media_type'] ?? '');
            $display_location = clean_input($_POST['display_location'] ?? '');
            $date_start       = clean_input($_POST['display_date_start'] ?? '');
            $date_end         = !empty($_POST['display_date_end']) ? clean_input($_POST['display_date_end']) : null;
            $time_start_led   = !empty($_POST['display_time_start']) ? clean_input($_POST['display_time_start']) : null;
            $time_end_led     = !empty($_POST['display_time_end']) ? clean_input($_POST['display_time_end']) : null;
            $duration         = intval($_POST['duration_seconds'] ?? 15);
            $resolution       = clean_input($_POST['resolution'] ?? '');
            $purpose          = clean_input($_POST['purpose'] ?? '');
            $special          = clean_input($_POST['special_requirements'] ?? '');

            $media_file_path = null;
            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
                $led_file = $_FILES['media_file'];
                $max_size = 200 * 1024 * 1024;
                if ($led_file['size'] > $max_size) {
                    throw new Exception('ไฟล์สื่อมีขนาดเกิน 200 MB');
                }
                $allowed_ext = ['mp4','avi','mov','wmv','mkv','jpg','jpeg','png','gif','webm','webp'];
                $ext = strtolower(pathinfo($led_file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_ext)) {
                    throw new Exception('ไฟล์ประเภท .' . $ext . ' ไม่รองรับ');
                }
                $led_path = __DIR__ . '/../../storage/uploads/led/' . date('Y') . '/' . date('m') . '/';
                if (!file_exists($led_path)) mkdir($led_path, 0777, true);
                $new_name = 'LED_' . $request_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($led_file['tmp_name'], $led_path . $new_name)) {
                    $media_file_path = 'storage/uploads/led/' . date('Y') . '/' . date('m') . '/' . $new_name;
                }
            }
            $media_url = !empty($_POST['media_url']) ? clean_input($_POST['media_url']) : null;

            $detail_stmt = $conn->prepare("INSERT INTO request_led_details (request_id, media_title, media_type, display_location, display_date_start, display_date_end, display_time_start, display_time_end, duration_seconds, resolution, media_file, media_url, purpose, special_requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $detail_stmt->bind_param('isssssssissssss', $request_id, $media_title, $media_type, $display_location, $date_start, $date_end, $time_start_led, $time_end_led, $duration, $resolution, $media_file_path, $media_url, $purpose, $special);
            break;
    }

    if (isset($detail_stmt)) {
        if (!$detail_stmt->execute()) {
            throw new Exception('Detail insert failed: ' . $detail_stmt->error);
        }
    }

    // 8. Optional immediate assignment
    $assign_immediately = isset($_POST['assign_immediately']) && $_POST['assign_immediately'] === '1';
    $assign_to = intval($_POST['assign_to'] ?? 0);

    if ($assign_immediately && $assign_to > 0) {
        $assign_priority = in_array($_POST['assign_priority'] ?? '', ['low','normal','high','urgent']) ? $_POST['assign_priority'] : 'normal';
        $assign_notes    = clean_input($_POST['assign_notes'] ?? '');
        $assign_due_date = !empty($_POST['assign_due_date']) ? clean_input($_POST['assign_due_date']) : null;

        $asgn_stmt = $conn->prepare("INSERT INTO task_assignments (request_id, assigned_to, assigned_by, priority, notes, due_date, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $asgn_stmt->bind_param('iiisss', $request_id, $assign_to, $admin_user_id, $assign_priority, $assign_notes, $assign_due_date);
        if (!$asgn_stmt->execute()) {
            throw new Exception('Assignment insert failed: ' . $asgn_stmt->error);
        }
        $assignment_id = $conn->insert_id;

        // Log assignment history (inline — same logic as logTaskHistory in task_assignments_api.php)
        $hist_stmt = $conn->prepare("INSERT INTO task_history (assignment_id, action, old_status, new_status, performed_by, notes) VALUES (?, 'assigned', NULL, 'pending', ?, ?)");
        $hist_notes = 'สร้างงานโดยแอดมินและมอบหมายทันที';
        $hist_stmt->bind_param('iis', $assignment_id, $admin_user_id, $hist_notes);
        $hist_stmt->execute();
    }

    $conn->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'สร้างงานเรียบร้อยแล้ว รหัสคำขอ: ' . $request_code,
        'request_id'   => $request_id,
        'request_code' => $request_code,
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]);
}

$conn->close();
