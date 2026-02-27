<?php
/**
 * Process Request API
 * Processes the service request form submission via AJAX
 */


require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email_helper.php';

header('Content-Type: application/json; charset=utf-8');

// Enable error reporting for debugging (but catch errors to return JSON)
// error_reporting(E_ALL);
// ini_set('display_errors', 0); // Hide from output, handle via try key

$response = [
    'success' => false,
    'message' => 'Unknown Error',
    'redirect_url' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid Request Method']);
    exit;
}

// Start session if not started (required for Captcha)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn->begin_transaction();

try {
    // 1. Validate CAPTCHA (skip if not in session)
    if (isset($_SESSION['captcha_code']) && !empty($_SESSION['captcha_code'])) {
        $captcha_input = isset($_POST['captcha']) ? $_POST['captcha'] : '';
        $captcha_session = $_SESSION['captcha_code'];

        if ($captcha_input !== $captcha_session) {
            throw new Exception("รหัส Captcha ไม่ถูกต้อง (Invalid Captcha)");
        }
    }

    // 2. Generate request code
    $year = date('Y');
    // Fix key id -> request_id for order
    $last_request = $conn->query("SELECT request_code FROM service_requests WHERE request_code LIKE 'REQ-$year-%' ORDER BY request_id DESC LIMIT 1")->fetch_assoc();

    if ($last_request) {
        $last_num = intval(substr($last_request['request_code'], -4));
        $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
    } else {
        $new_num = '0001';
    }

    $request_code = "REQ-$year-$new_num";

    // 3. Get basic inputs
    $service_code = isset($_POST['service_code']) ? clean_input($_POST['service_code']) : '';
    if (empty($service_code)) {
        throw new Exception("ไม่ระบุ Service Code");
    }

    // Get Service Name
    $service_query = $conn->prepare("SELECT service_name FROM my_service WHERE service_code = ?");
    $service_query->bind_param("s", $service_code);
    $service_query->execute();
    $service_res = $service_query->get_result()->fetch_assoc();
    $service_name = $service_res ? $service_res['service_name'] : $service_code;

    $dept_id = isset($_POST['department']) ? intval($_POST['department']) : 0;
    $dept_name = '';
    if ($dept_id > 0) {
        $dept_query = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $dept_query->bind_param("i", $dept_id);
        $dept_query->execute();
        $dept_result = $dept_query->get_result()->fetch_assoc();
        $dept_name = $dept_result ? $dept_result['department_name'] : '';
    }

    // Build requester name from prefix + firstname + lastname
    $prefix_id = intval($_POST['requester_prefix_id'] ?? 0);
    $prefix_name = '';
    if ($prefix_id > 0) {
        $pfx_q = $conn->prepare("SELECT prefix_name FROM prefixes WHERE prefix_id = ?");
        $pfx_q->bind_param('i', $prefix_id);
        $pfx_q->execute();
        $pfx_row = $pfx_q->get_result()->fetch_assoc();
        $prefix_name = $pfx_row ? $pfx_row['prefix_name'] : '';
    }
    $firstname = clean_input($_POST['requester_firstname'] ?? '');
    $lastname  = clean_input($_POST['requester_lastname'] ?? '');
    // Fallback: ถ้าส่งมาเป็น requester_name เดิม (backward compat)
    if (empty($firstname) && !empty($_POST['requester_name'])) {
        $name = clean_input($_POST['requester_name']);
    } else {
        $name = trim($prefix_name . $firstname . ' ' . $lastname);
    }

    $email = clean_input($_POST['requester_email'] ?? '');
    $phone = clean_input($_POST['requester_phone'] ?? '');
    $position = clean_input($_POST['position'] ?? '');
    $priority = isset($_POST['priority']) ? clean_input($_POST['priority']) : 'medium';
    $target_date = !empty($_POST['target_date']) ? clean_input($_POST['target_date']) : NULL;
    $description = clean_input($_POST['notes'] ?? '');

    // Validate required fields (skip for QR_CODE - free public service)
    if ($service_code !== 'QR_CODE' && (empty($name) || empty($dept_id))) {
        throw new Exception("กรุณากรอกข้อมูลที่จำเป็น (ชื่อ, แผนก)");
    }

    // Create Subject
    $subject = !empty($name)
        ? $service_name . " Request from " . $name
        : $service_name . " Request #" . $request_code;
    
    // User ID (NULL for guest/public)
    $user_id = null;

    // 4. File Upload Handling
    $attachments_json = null;
    if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
        $uploaded_files = [];
        // Determine upload path properly relative to this file
        // API is in /api/, so root is ../
        // Storage is in /storage/uploads/requests/...
        
        $base_upload_path = __DIR__ . '/../storage/uploads/requests/' . date('Y') . '/' . date('m') . '/';
        
        // Create directory if not exists
        if (!file_exists($base_upload_path)) {
            mkdir($base_upload_path, 0777, true);
        }

        $total = count($_FILES['attachments']['name']);
        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        
        for ($i = 0; $i < $total; $i++) {
            $file_name = $_FILES['attachments']['name'][$i];
            $file_tmp = $_FILES['attachments']['tmp_name'][$i];
            $file_size = $_FILES['attachments']['size'][$i];
            $file_error = $_FILES['attachments']['error'][$i];

            if ($file_error === 0) {
                // Extract only filename (remove path from iPhone: 'images\S__144867343.jpg' -> 'S__144867343.jpg')
                $base_name = basename(str_replace('\\', '/', $file_name));
                $file_ext = strtolower(pathinfo($base_name, PATHINFO_EXTENSION));
                
                if (in_array($file_ext, $allowed_types)) {
                        if ($file_size <= 5242880) { // 5MB
                        $new_file_name = uniqid('REQ_', true) . '.' . $file_ext;
                        $destination = $base_upload_path . $new_file_name;
                        
                        // DB path should be relative to webroot e.g. 'storage/...'
                        if (move_uploaded_file($file_tmp, $destination)) {
                            // Store relative path
                            $uploaded_files[] = 'storage/uploads/requests/' . date('Y') . '/' . date('m') . '/' . $new_file_name;
                        }
                        }
                }
            }
        }

        if (!empty($uploaded_files)) {
            $attachments_json = json_encode($uploaded_files, JSON_UNESCAPED_UNICODE);
        }
    }

    // 5. Insert Main Request
    $stmt = $conn->prepare("INSERT INTO service_requests (
        user_id, request_code, service_code, service_name,
        requester_prefix_id, requester_name, requester_email, requester_phone, requester_position,
        department_id, department_name, subject, description,
        priority, expected_completion_date, attachments
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("issssisssissssss",
        $user_id, $request_code, $service_code, $service_name,
        $prefix_id, $name, $email, $phone, $position,
        $dept_id, $dept_name, $subject, $description,
        $priority, $target_date, $attachments_json
    );
    
    if (!$stmt->execute()) {
        throw new Exception("Database Insert Failed: " . $stmt->error);
    }

    $request_id = $conn->insert_id;

    // 6. Insert Service Specific Details
    switch ($service_code) {
        case 'EMAIL':
            $stmt = $conn->prepare("INSERT INTO request_email_details (request_id, requested_username, email_format, quota_mb, purpose, is_new_account, existing_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
            
            // Combine Firstname and Lastname for requested_username column
            $firstname_en = clean_input($_POST['firstname_en']);
            $lastname_en = clean_input($_POST['lastname_en']);
            $username = $firstname_en . " " . $lastname_en; // e.g. "Somchai Jaidee"
            
            $email_format = clean_input($_POST['email_format']);
            $quota = 2048; 
            $purpose = clean_input($_POST['purpose']);
            $is_new = intval($_POST['is_new_account']);
            $existing = clean_input($_POST['existing_email'] ?? '');
            $stmt->bind_param("issisis", $request_id, $username, $email_format, $quota, $purpose, $is_new, $existing);
            break;

        case 'NAS':
            $stmt = $conn->prepare("INSERT INTO request_nas_details (request_id, folder_name, storage_size_gb, permission_type, shared_with, purpose, backup_required) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $folder = clean_input($_POST['folder_name']);
            $size = intval($_POST['storage_size_gb']);
            $permission = clean_input($_POST['permission_type']);
            $shared = clean_input($_POST['shared_with']);
            $purpose = clean_input($_POST['purpose']);
            $backup = intval($_POST['backup_required']);
            $stmt->bind_param("isisssi", $request_id, $folder, $size, $permission, $shared, $purpose, $backup);
            break;

        case 'IT_SUPPORT':
            $stmt = $conn->prepare("INSERT INTO request_it_support_details (request_id, issue_type, device_type, device_brand, symptoms, location, urgency_level, error_message, when_occurred) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $issue_type = clean_input($_POST['issue_type']);
            $device_type = clean_input($_POST['device_type']);
            $device_brand = clean_input($_POST['device_brand']);
            $symptoms = clean_input($_POST['symptoms']);
            $location = clean_input($_POST['location']);
            $urgency = clean_input($_POST['urgency_level']);
            $error_msg = clean_input($_POST['error_message'] ?? '');
            $when = clean_input($_POST['when_occurred']);
            $stmt->bind_param("issssssss", $request_id, $issue_type, $device_type, $device_brand, $symptoms, $location, $urgency, $error_msg, $when);
            break;

        case 'INTERNET':
            $stmt = $conn->prepare("INSERT INTO request_internet_details (request_id, request_type, location, building, room_number, current_issue, citizen_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $req_type   = clean_input($_POST['request_type']);
            $location   = clean_input($_POST['location']);
            $building   = clean_input($_POST['building'] ?? '');
            $room       = clean_input($_POST['room_number'] ?? '');
            $issue      = clean_input($_POST['current_issue'] ?? '');
            $citizen_id = !empty($_POST['citizen_id']) ? clean_input($_POST['citizen_id']) : null;
            $stmt->bind_param("issssss", $request_id, $req_type, $location, $building, $room, $issue, $citizen_id);
            break;

        case 'QR_CODE':
            $stmt = $conn->prepare("INSERT INTO request_qrcode_details (request_id, qr_type, qr_content, qr_size, color_primary, color_background, logo_url, output_format, quantity, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $qr_type = clean_input($_POST['qr_type']);
            $content = clean_input($_POST['qr_content']);
            $size = clean_input($_POST['qr_size']);
            $color1 = clean_input($_POST['color_primary']);
            $color2 = clean_input($_POST['color_background']);
            $logo = clean_input($_POST['logo_url'] ?? '');
            $format = clean_input($_POST['output_format']);
            $qty = intval($_POST['quantity']);
            $purpose = clean_input($_POST['purpose'] ?? '');
            $stmt->bind_param("issssssis", $request_id, $qr_type, $content, $size, $color1, $color2, $logo, $format, $qty, $purpose);
            break;

        case 'PHOTOGRAPHY':
            $stmt = $conn->prepare("INSERT INTO request_photography_details (request_id, event_name, event_type, event_date, event_time_start, event_time_end, event_location, number_of_photographers, video_required, drone_required, delivery_format, special_requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $event_name = clean_input($_POST['event_name']);
            $event_type = clean_input($_POST['event_type']);
            $event_date = clean_input($_POST['event_date']);
            $time_start_h = clean_input($_POST['event_time_start_h'] ?? '00');
            $time_start_m = clean_input($_POST['event_time_start_m'] ?? '00');
            $time_start = $time_start_h . ':' . $time_start_m;
            $time_end_h = clean_input($_POST['event_time_end_h'] ?? '');
            $time_end_m = clean_input($_POST['event_time_end_m'] ?? '');
            $time_end = ($time_end_h !== '') ? $time_end_h . ':' . $time_end_m : '';
            $event_loc = clean_input($_POST['event_location']);
            $photographers = intval($_POST['number_of_photographers']);
            $photo_types = $_POST['photo_type'] ?? [];
            $video = in_array('video', $photo_types) ? 1 : 0;
            $drone = 0;
            $delivery = clean_input($_POST['delivery_format']);
            $special = clean_input($_POST['notes'] ?? '');
            $stmt->bind_param("isssssiiiiss", $request_id, $event_name, $event_type, $event_date, $time_start, $time_end, $event_loc, $photographers, $video, $drone, $delivery, $special);
            break;

        case 'WEB_DESIGN':
            $stmt = $conn->prepare("INSERT INTO request_webdesign_details (request_id, website_type, project_name, purpose, target_audience, number_of_pages, features_required, has_existing_site, existing_url, domain_name, hosting_required, reference_sites, color_preferences, budget) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $web_type = clean_input($_POST['website_type']);
            $proj_name = clean_input($_POST['project_name']);
            $purpose = clean_input($_POST['purpose']);
            $audience = clean_input($_POST['target_audience']);
            $pages = intval($_POST['number_of_pages']);
            $features = clean_input($_POST['features_required'] ?? '');
            $has_site = intval($_POST['has_existing_site']);
            $existing_url = clean_input($_POST['existing_url'] ?? '');
            $domain = clean_input($_POST['domain_name'] ?? '');
            $hosting = intval($_POST['hosting_required']);
            $references = clean_input($_POST['reference_sites'] ?? '');
            $colors = clean_input($_POST['color_preferences'] ?? '');
            $budget = clean_input($_POST['budget'] ?? '');
            $stmt->bind_param("issssisisssss", $request_id, $web_type, $proj_name, $purpose, $audience, $pages, $features, $has_site, $existing_url, $domain, $hosting, $references, $colors, $budget);
            break;

        case 'PRINTER':
            $stmt = $conn->prepare("INSERT INTO request_printer_details (request_id, issue_type, printer_type, printer_brand, printer_model, serial_number, location, problem_description, error_code, toner_color, supplies_needed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $issue = clean_input($_POST['issue_type']);
            $printer_type = clean_input($_POST['printer_type'] ?? '');
            $brand = clean_input($_POST['printer_brand'] ?? '');
            $model = clean_input($_POST['printer_model'] ?? '');
            $serial = clean_input($_POST['serial_number'] ?? '');
            $location = clean_input($_POST['location']);
            $problem = clean_input($_POST['problem_description']);
            $error_code = clean_input($_POST['error_code'] ?? '');
            $toner = clean_input($_POST['toner_color'] ?? '');
            $supplies = clean_input($_POST['supplies_needed'] ?? '');
            $stmt->bind_param("issssssssss", $request_id, $issue, $printer_type, $brand, $model, $serial, $location, $problem, $error_code, $toner, $supplies);
            break;

        case 'MC':
            $stmt = $conn->prepare("INSERT INTO request_mc_details (request_id, event_name, event_type, event_date, event_time_start, event_time_end, location, mc_count, language, script_status, dress_code, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $event_name = clean_input($_POST['event_name']);
            $event_type = clean_input($_POST['event_type']);
            $event_date = clean_input($_POST['event_date']);
            $start_h = clean_input($_POST['event_time_start_h'] ?? '00');
            $start_m = clean_input($_POST['event_time_start_m'] ?? '00');
            $start = $start_h . ':' . $start_m;
            $end_h = clean_input($_POST['event_time_end_h'] ?? '');
            $end_m = clean_input($_POST['event_time_end_m'] ?? '');
            $end = ($end_h !== '') ? $end_h . ':' . $end_m : '';
            $loc = clean_input($_POST['location']);
            $count = intval($_POST['mc_count']);
            $lang = clean_input($_POST['language']);
            $script = clean_input($_POST['script_status']);
            $dress = clean_input($_POST['dress_code'] ?? '');
            $note = '';
            $stmt->bind_param("issssssissss", $request_id, $event_name, $event_type, $event_date, $start, $end, $loc, $count, $lang, $script, $dress, $note);
            break;

        case 'LED':
            $media_title = clean_input($_POST['media_title']);
            $media_type = clean_input($_POST['media_type']);
            $display_location = clean_input($_POST['display_location']);
            $date_start = clean_input($_POST['display_date_start']);
            $date_end = !empty($_POST['display_date_end']) ? clean_input($_POST['display_date_end']) : null;
            $time_start = !empty($_POST['display_time_start']) ? clean_input($_POST['display_time_start']) : null;
            $time_end = !empty($_POST['display_time_end']) ? clean_input($_POST['display_time_end']) : null;
            $duration = intval($_POST['duration_seconds'] ?? 15);
            $resolution = clean_input($_POST['resolution'] ?? '');
            $purpose = clean_input($_POST['purpose']);
            $special = clean_input($_POST['special_requirements'] ?? '');

            // Handle LED media file upload (up to 200MB)
            $media_file_path = null;
            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
                $led_file = $_FILES['media_file'];
                $max_size = 200 * 1024 * 1024; // 200MB
                if ($led_file['size'] > $max_size) {
                    throw new Exception("ไฟล์สื่อมีขนาดเกิน 200 MB");
                }
                $allowed_ext = ['mp4', 'avi', 'mov', 'wmv', 'mkv', 'jpg', 'jpeg', 'png', 'gif', 'webm', 'webp'];
                $ext = strtolower(pathinfo($led_file['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed_ext)) {
                    throw new Exception("ไฟล์ประเภท .$ext ไม่รองรับ (รองรับ: " . implode(', ', $allowed_ext) . ")");
                }
                $led_upload_path = __DIR__ . '/../storage/uploads/led/' . date('Y') . '/' . date('m') . '/';
                if (!file_exists($led_upload_path)) {
                    mkdir($led_upload_path, 0777, true);
                }
                $new_name = 'LED_' . $request_id . '_' . time() . '.' . $ext;
                if (move_uploaded_file($led_file['tmp_name'], $led_upload_path . $new_name)) {
                    $media_file_path = 'storage/uploads/led/' . date('Y') . '/' . date('m') . '/' . $new_name;
                }
            }

            // Media URL (Google Drive, etc.)
            $media_url = !empty($_POST['media_url']) ? clean_input($_POST['media_url']) : null;

            $stmt = $conn->prepare("INSERT INTO request_led_details (request_id, media_title, media_type, display_location, display_date_start, display_date_end, display_time_start, display_time_end, duration_seconds, resolution, media_file, media_url, purpose, special_requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssissssss", $request_id, $media_title, $media_type, $display_location, $date_start, $date_end, $time_start, $time_end, $duration, $resolution, $media_file_path, $media_url, $purpose, $special);
            break;
    }

    if (isset($stmt)) {
        $stmt->execute();
    }

    // 7. Success
    $conn->commit();

    // Send Notification (fire and forget basically)
    send_request_notification($request_id, $conn);

    $response['success'] = true;
    $response['message'] = "ส่งคำขอเรียบร้อยแล้ว รหัสคำขอของคุณคือ: $request_code";
    $response['redirect_url'] = "request-success.php?code=$request_code";

} catch (Exception $e) {
    $conn->rollback();
    $response['success'] = false;
    $response['message'] = $e->getMessage();
}

$conn->close();
echo json_encode($response);
?>