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
    // 1. Validate CAPTCHA
    $captcha_input = isset($_POST['captcha']) ? $_POST['captcha'] : '';
    $captcha_session = isset($_SESSION['captcha_code']) ? $_SESSION['captcha_code'] : '';

    if (empty($captcha_input) || empty($captcha_session) || $captcha_input !== $captcha_session) {
        throw new Exception("รหัส Captcha ไม่ถูกต้อง (Invalid Captcha)");
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

    $name = clean_input($_POST['requester_name'] ?? '');
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
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                
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
    // Columns updated to match service_requests table schema
    $stmt = $conn->prepare("INSERT INTO service_requests (
        user_id, request_code, service_code, service_name, 
        requester_name, requester_email, requester_phone, requester_position, 
        department_id, department_name, subject, description, 
        priority, expected_completion_date, attachments
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("isssssssissssss", 
        $user_id, $request_code, $service_code, $service_name,
        $name, $email, $phone, $position,
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
            $stmt->bind_param("sisssi", $request_id, $folder, $size, $permission, $shared, $purpose, $backup);
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
            $stmt = $conn->prepare("INSERT INTO request_internet_details (request_id, request_type, location, building, room_number, current_issue) VALUES (?, ?, ?, ?, ?, ?)");
            $req_type = clean_input($_POST['request_type']);
            $location = clean_input($_POST['location']);
            $building = clean_input($_POST['building']);
            $room = clean_input($_POST['room_number']);
            $issue = clean_input($_POST['current_issue'] ?? '');
            $stmt->bind_param("isssss", $request_id, $req_type, $location, $building, $room, $issue);
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
            $time_start = clean_input($_POST['event_time_start']);
            $time_end = clean_input($_POST['event_time_end']);
            $event_loc = clean_input($_POST['event_location']);
            $photographers = intval($_POST['number_of_photographers']);
            $video = intval($_POST['video_required']);
            $drone = intval($_POST['drone_required']);
            $delivery = clean_input($_POST['delivery_format']);
            $special = clean_input($_POST['special_requirements'] ?? '');
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
            $start = clean_input($_POST['event_time_start']);
            $end = clean_input($_POST['event_time_end']);
            $loc = clean_input($_POST['location']);
            $count = intval($_POST['mc_count']);
            $lang = clean_input($_POST['language']);
            $script = clean_input($_POST['script_status']);
            $dress = clean_input($_POST['dress_code'] ?? '');
            $note = clean_input($_POST['note'] ?? '');
            $stmt->bind_param("issssssissss", $request_id, $event_name, $event_type, $event_date, $start, $end, $loc, $count, $lang, $script, $dress, $note);
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