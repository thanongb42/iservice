<?php
/**
 * Tracking System
 * หน้าติดตามสถานะคำร้อง
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/nav_menu_loader.php';

$request_code = isset($_GET['req']) ? clean_input($_GET['req']) : (isset($_POST['request_code']) ? clean_input($_POST['request_code']) : '');
$request = null;
$timeline = [];

if (!empty($request_code)) {
    // Determine the user's view table (using the view v_service_requests_full if it exists, or join manually)
    // Checking previous logs, v_service_requests_full exists in green_theme_db but maybe not fully migrated/created in iservice_db yet?
    // Let's use direct query to be safe with the new structure.
    
    $query = "SELECT r.*, m.service_name as master_service_name, m.icon, m.color_code 
              FROM service_requests r 
              LEFT JOIN my_service m ON r.service_code = m.service_code 
              WHERE r.request_code = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $request_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();

    if ($request) {
        // Get task assignments for this request
        $ta_stmt = $conn->prepare("
            SELECT ta.status, ta.assigned_to, ta.created_at, ta.accepted_at, ta.started_at, ta.completed_at,
                   CONCAT(u.first_name, ' ', u.last_name) as assignee_name
            FROM task_assignments ta
            JOIN users u ON ta.assigned_to = u.user_id
            WHERE ta.request_id = ? AND ta.status != 'cancelled'
            ORDER BY ta.created_at DESC
            LIMIT 1
        ");
        $ta_stmt->bind_param('i', $request['request_id']);
        $ta_stmt->execute();
        $task = $ta_stmt->get_result()->fetch_assoc();

        // Determine actual progress from task assignment data
        $task_status = $task['status'] ?? null;
        $is_assigned = !empty($task);
        $is_accepted = in_array($task_status, ['accepted', 'in_progress', 'completed']);
        $is_working = in_array($task_status, ['in_progress', 'completed']);
        $is_completed = ($request['status'] == 'completed') || ($task_status == 'completed');
        $is_rejected = ($request['status'] == 'rejected');
        $is_cancelled = ($request['status'] == 'cancelled');

        // Step 1: รับเรื่อง
        $timeline[] = [
            'status' => 'pending',
            'label' => 'รับเรื่อง',
            'desc' => 'ระบบได้รับคำขอแล้ว',
            'time' => $request['created_at'],
            'completed' => true
        ];

        // Step 2: มอบหมายงาน
        $timeline[] = [
            'status' => 'assigned',
            'label' => 'มอบหมายงาน',
            'desc' => $is_assigned ? 'มอบหมายให้ ' . htmlspecialchars($task['assignee_name']) : 'รอมอบหมาย',
            'time' => $is_assigned ? $task['created_at'] : null,
            'completed' => $is_assigned
        ];

        // Step 3: รับงาน
        $timeline[] = [
            'status' => 'accepted',
            'label' => 'รับงาน',
            'desc' => $is_accepted ? 'เจ้าหน้าที่รับงานแล้ว' : 'รอเจ้าหน้าที่รับงาน',
            'time' => $is_accepted ? $task['accepted_at'] : null,
            'completed' => $is_accepted
        ];

        // Step 4: กำลังดำเนินการ
        $timeline[] = [
            'status' => 'in_progress',
            'label' => 'กำลังดำเนินการ',
            'desc' => $is_working ? 'เจ้าหน้าที่กำลังดำเนินการ' : 'รอดำเนินการ',
            'time' => $is_working ? $task['started_at'] : null,
            'completed' => $is_working
        ];

        if ($is_rejected) {
            $timeline[] = [
                'status' => 'rejected',
                'label' => 'ถูกปฏิเสธ',
                'desc' => $request['rejection_reason'],
                'time' => $request['updated_at'],
                'completed' => true
            ];
        } elseif ($is_cancelled) {
            $timeline[] = [
                'status' => 'cancelled',
                'label' => 'ยกเลิก',
                'desc' => 'ผู้ใช้ยกเลิกคำขอ',
                'time' => $request['cancelled_at'],
                'completed' => true
            ];
        } else {
            // Step 5: เสร็จสิ้น
            $timeline[] = [
                'status' => 'completed',
                'label' => 'เสร็จสิ้น',
                'desc' => $is_completed ? ($request['completion_notes'] ?? 'ดำเนินการเรียบร้อย') : 'รอดำเนินการให้เสร็จ',
                'time' => $is_completed ? ($request['completed_at'] ?? $task['completed_at']) : null,
                'completed' => $is_completed
            ];
        }
    }
}

// Nav Menu
$nav_menus = get_menu_structure();
$nav_html = render_nav_menu($nav_menus);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ติดตามสถานะคำร้อง - iService</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <?php include 'includes/header_public.php'; ?>

    <main class="flex-grow container mx-auto px-4 py-8">
        
        <!-- Search Section -->
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-sm p-8 mb-8 text-center">
            <h1 class="text-2xl font-bold text-gray-800 mb-2">ติดตามสถานะคำร้อง</h1>
            <p class="text-gray-500 mb-6">กรอกเลขที่คำร้อง (Request Code) เพื่อตรวจสอบสถานะ</p>
            
            <form action="" method="GET" class="flex gap-2">
                <input type="text" name="req" value="<?= htmlspecialchars($request_code) ?>" 
                       class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-lg text-center uppercase"
                       placeholder="REQ-202X-XXXX" required>
                <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-bold transition">
                    <i class="fas fa-search"></i> ค้นหา
                </button>
            </form>
        </div>

        <?php if ($request): ?>
        <!-- Result Section -->
        <div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-teal-700 p-6 text-white flex justify-between items-start">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-white/20 px-3 py-1 rounded text-sm font-mono"><?= $request['request_code'] ?></span>
                        <?php 
                            $status_color = 'bg-yellow-500';
                            $status_text = 'รอดำเนินการ';
                            switch($request['status']) {
                                case 'in_progress': $status_color = 'bg-blue-500'; $status_text = 'กำลังดำเนินการ'; break;
                                case 'completed': $status_color = 'bg-green-500'; $status_text = 'เสร็จสิ้น'; break;
                                case 'rejected': $status_color = 'bg-red-500'; $status_text = 'ถูกปฏิเสธ'; break;
                                case 'cancelled': $status_color = 'bg-gray-500'; $status_text = 'ยกเลิก'; break;
                            }
                        ?>
                        <span class="<?= $status_color ?> px-3 py-1 rounded text-sm font-bold shadow-sm">
                            <?= $status_text ?>
                        </span>
                    </div>
                    <h2 class="text-2xl font-bold mb-1"><?= htmlspecialchars($request['subject']) ?></h2>
                    <p class="text-teal-100 text-sm">
                        <i class="<?= $request['icon'] ?> mr-1"></i> <?= htmlspecialchars($request['master_service_name']) ?> | 
                        <i class="fas fa-clock mr-1"></i> ส่งเมื่อ <?= date('d/m/Y H:i', strtotime($request['created_at'])) ?>
                    </p>
                </div>
                <!-- QR Code for this page -->
                <div class="hidden md:block bg-white p-2 rounded-lg" id="qrcode"></div>
            </div>

            <div class="p-6 md:p-8">
                <!-- Timeline -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-800 mb-4 border-b pb-2">ความคืบหน้า</h3>
                    <div class="relative">
                        <!-- Connecting Line -->
                        <div class="absolute left-4 top-0 bottom-0 w-0.5 bg-gray-200"></div>

                        <?php foreach($timeline as $step): ?>
                        <div class="relative pl-12 pb-8 last:pb-0">
                            <!-- Bullet -->
                            <div class="absolute left-0 w-8 h-8 rounded-full flex items-center justify-center border-4 
                                <?= $step['completed'] ? 'bg-teal-600 border-teal-100 text-white' : 'bg-white border-gray-300 text-gray-400' ?> 
                                z-10 transition-colors">
                                <i class="fas fa-check text-xs"></i>
                            </div>
                            
                            <!-- Content -->
                            <div class="<?= $step['completed'] ? 'opacity-100' : 'opacity-60' ?>">
                                <h4 class="font-bold text-gray-800 text-lg"><?= $step['label'] ?></h4>
                                <p class="text-gray-600 text-sm mb-1"><?= $step['desc'] ?></p>
                                <?php if($step['time']): ?>
                                    <p class="text-xs text-gray-400">
                                        <i class="far fa-calendar-alt mr-1"></i> <?= date('d/m/Y H:i', strtotime($step['time'])) ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Details -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-gray-50 p-6 rounded-xl border border-gray-200">
                    <div>
                        <h3 class="font-bold text-gray-700 mb-3">ข้อมูลผู้ขอ</h3>
                        <ul class="space-y-2 text-sm text-gray-600">
                            <li><span class="font-semibold block text-gray-500 text-xs">ชื่อ-สกุล:</span> <?= htmlspecialchars($request['requester_name']) ?></li>
                            <li><span class="font-semibold block text-gray-500 text-xs">หน่วยงาน:</span> <?= htmlspecialchars($request['department_name']) ?></li>
                            <li><span class="font-semibold block text-gray-500 text-xs">อีเมล:</span> <?= htmlspecialchars($request['requester_email']) ?></li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-700 mb-3">รายละเอียดเพิ่มเติม</h3>
                        <p class="text-sm text-gray-600 whitespace-pre-line"><?= htmlspecialchars($request['description']) ?></p>
                        <?php if(!empty($request['rejection_reason'])): ?>
                            <div class="mt-4 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm">
                                <span class="font-bold">สาเหตุที่ปฏิเสธ:</span> <?= htmlspecialchars($request['rejection_reason']) ?>
                            </div>
                        <?php endif; ?>

                        <?php 
                        // Display Attachments
                        if (!empty($request['attachments'])) {
                            $attachments = json_decode($request['attachments'], true);
                            if ($attachments && is_array($attachments)) {
                                echo '<div class="mt-4 border-t pt-3">';
                                echo '<h4 class="font-bold text-gray-700 mb-2 text-sm"><i class="fas fa-paperclip mr-1"></i> เอกสารแนบ:</h4>';
                                echo '<ul class="space-y-1">';
                                foreach ($attachments as $att) {
                                    $filename = basename($att);
                                    echo '<li><a href="'.htmlspecialchars($att).'" target="_blank" class="text-teal-600 hover:underline text-sm"><i class="fas fa-file-alt mr-1"></i> '.htmlspecialchars($filename).'</a></li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                            }
                        }
                        ?>
                    </div>
                </div>

            </div>
            
            <div class="bg-gray-50 px-6 py-4 flex justify-between items-center border-t">
                <a href="index.php" class="text-gray-500 hover:text-teal-600 text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> กลับหน้าหลัก
                </a>
                <button onclick="window.print()" class="text-teal-600 hover:text-teal-800 text-sm font-semibold">
                    <i class="fas fa-print mr-1"></i> พิมพ์ใบคำขอ
                </button>
            </div>
        </div>
        
        <script>
            // Generate QR Code linking to current page
            new QRCode(document.getElementById("qrcode"), {
                text: window.location.href,
                width: 80,
                height: 80,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });
        </script>

        <?php elseif(!empty($request_code)): ?>
            <!-- Not Found -->
            <div class="max-w-2xl mx-auto bg-white rounded-xl shadow p-8 text-center">
                <div class="inline-block p-4 rounded-full bg-red-100 text-red-500 mb-4">
                    <i class="fas fa-search text-3xl"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">ไม่พบข้อมูลคำร้อง</h2>
                <p class="text-gray-600">รหัสคำร้อง <strong><?= htmlspecialchars($request_code) ?></strong> ไม่ถูกต้อง หรือไม่มีในระบบ</p>
                <div class="mt-6">
                    <a href="index.php" class="text-teal-600 hover:underline">กลับหน้าหลัก</a>
                </div>
            </div>
        <?php endif; ?>

    </main>

</body>
</html>
