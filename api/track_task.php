<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

$response = ['status' => 'error', 'message' => 'Invalid request', 'data' => null];

try {
    // Get search parameter from GET or POST
    $ticket = isset($_GET['ticket']) ? trim($_GET['ticket']) : '';
    $assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

    if (empty($ticket) && empty($assignment_id)) {
        $response['message'] = 'Please provide either ticket number or assignment ID';
        echo json_encode($response);
        exit;
    }

    // Search by ticket number (REQ-2025-0005 format)
    if (!empty($ticket)) {
        $stmt = $conn->prepare("
            SELECT 
                sr.request_id,
                sr.request_code,
                sr.service_code,
                sr.subject,
                sr.description,
                sr.priority,
                sr.status AS request_status,
                sr.created_at,
                sr.requester_name,
                sr.requester_phone,
                sr.requester_email,
                ta.assignment_id,
                ta.assigned_to,
                ta.created_at AS assigned_at,
                ta.status AS task_status,
                ta.accepted_at,
                ta.started_at,
                ta.completed_at,
                CONCAT(u.first_name, ' ', u.last_name) as assigned_staff_name,
                r.role_name
            FROM service_requests sr
            LEFT JOIN task_assignments ta ON sr.request_id = ta.request_id
            LEFT JOIN users u ON ta.assigned_to = u.user_id
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id AND ur.is_primary = 1
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE sr.request_code = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param('s', $ticket);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = 'ไม่พบข้อมูลเรื่องที่ค้นหา';
            echo json_encode($response);
            exit;
        }

        $task_data = $result->fetch_assoc();
        $stmt->close();

        // Get any additional details based on service code
        $service_code = $task_data['service_code'];
        $details_table = '';

        $service_details_map = [
            'EMAIL' => 'request_email_details',
            'NAS' => 'request_nas_details',
            'INTERNET' => 'request_internet_details',
            'IT_SUPPORT' => 'request_it_support_details',
            'WEB_DESIGN' => 'request_webdesign_details',
            'PRINTER' => 'request_printer_details',
            'QR_CODE' => 'request_qr_code_details',
            'PHOTOGRAPHY' => 'request_photography_details'
        ];

        if (isset($service_details_map[$service_code])) {
            $details_table = $service_details_map[$service_code];
            
            $details_stmt = $conn->prepare("SELECT * FROM " . $details_table . " WHERE request_id = ?");
            if ($details_stmt) {
                $details_stmt->bind_param('i', $task_data['request_id']);
                $details_stmt->execute();
                $details_result = $details_stmt->get_result();
                if ($details_result && $details_result->num_rows > 0) {
                    $task_data['details'] = $details_result->fetch_assoc();
                }
                $details_stmt->close();
            }
        }

        $response['status'] = 'success';
        $response['message'] = 'พบข้อมูลเรื่องที่ค้นหา';
        $response['data'] = $task_data;
        echo json_encode($response);
        exit;
    }

    // Search by assignment ID
    if (!empty($assignment_id)) {
        $stmt = $conn->prepare("
            SELECT 
                sr.request_id,
                sr.request_code,
                sr.service_code,
                sr.subject,
                sr.description,
                sr.priority,
                sr.status AS request_status,
                sr.created_at,
                sr.requester_name,
                sr.requester_phone,
                sr.requester_email,
                ta.assignment_id,
                ta.assigned_to,
                ta.created_at AS assigned_at,
                ta.status AS task_status,
                ta.accepted_at,
                ta.started_at,
                ta.completed_at,
                CONCAT(u.first_name, ' ', u.last_name) as assigned_staff_name,
                r.role_name
            FROM task_assignments ta
            LEFT JOIN service_requests sr ON ta.request_id = sr.request_id
            LEFT JOIN users u ON ta.assigned_to = u.user_id
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id AND ur.is_primary = 1
            LEFT JOIN roles r ON ur.role_id = r.role_id
            WHERE ta.assignment_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }

        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $response['message'] = 'ไม่พบข้อมูลการมอบหมายงาน';
            echo json_encode($response);
            exit;
        }

        $task_data = $result->fetch_assoc();
        $stmt->close();

        // Get service details
        $service_code = $task_data['service_code'];
        $service_details_map = [
            'EMAIL' => 'request_email_details',
            'NAS' => 'request_nas_details',
            'INTERNET' => 'request_internet_details',
            'IT_SUPPORT' => 'request_it_support_details',
            'WEB_DESIGN' => 'request_webdesign_details',
            'PRINTER' => 'request_printer_details',
            'QR_CODE' => 'request_qr_code_details',
            'PHOTOGRAPHY' => 'request_photography_details'
        ];

        if (isset($service_details_map[$service_code])) {
            $details_stmt = $conn->prepare("SELECT * FROM " . $service_details_map[$service_code] . " WHERE request_id = ?");
            if ($details_stmt) {
                $details_stmt->bind_param('i', $task_data['request_id']);
                $details_stmt->execute();
                $details_result = $details_stmt->get_result();
                if ($details_result && $details_result->num_rows > 0) {
                    $task_data['details'] = $details_result->fetch_assoc();
                }
                $details_stmt->close();
            }
        }

        $response['status'] = 'success';
        $response['message'] = 'พบข้อมูลการมอบหมายงาน';
        $response['data'] = $task_data;
        echo json_encode($response);
        exit;
    }

} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    echo json_encode($response);
}
?>
