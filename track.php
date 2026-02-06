<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

$page_title = 'ติดตามงาน - เทศบาลนครรังสิต';
$tracking_data = null;
$error_message = '';
$search_ticket = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ticket'])) {
    $search_ticket = trim($_GET['ticket']);
    
    if (!empty($search_ticket)) {
        // Search via API
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'http://localhost/iservice/api/track_task.php?ticket=' . urlencode($search_ticket),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
        ]);
        
        $response = curl_exec($curl);
        curl_close($curl);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data['status'] === 'success' && $data['data']) {
                $tracking_data = $data['data'];
            } else {
                $error_message = $data['message'] ?? 'ไม่สามารถค้นหาข้อมูลได้';
            }
        }
    }
}

// Get service name helper
function get_service_name($service_code) {
    $services = [
        'EMAIL' => 'บริการอีเมล',
        'NAS' => 'บริการ NAS',
        'INTERNET' => 'บริการอินเทอร์เน็ต',
        'IT_SUPPORT' => 'บริการ IT Support',
        'WEB_DESIGN' => 'บริการออกแบบเว็บไซต์',
        'PRINTER' => 'บริการเครื่องพิมพ์',
        'QR_CODE' => 'บริการ QR Code',
        'PHOTOGRAPHY' => 'บริการถ่ายรูป'
    ];
    return $services[$service_code] ?? $service_code;
}

// Get status badge
function get_status_badge($status) {
    $badges = [
        'pending' => ['color' => 'bg-yellow-100 text-yellow-800', 'label' => 'รอการรับงาน'],
        'accepted' => ['color' => 'bg-blue-100 text-blue-800', 'label' => 'เจ้าหน้าที่รับงานแล้ว'],
        'in_progress' => ['color' => 'bg-purple-100 text-purple-800', 'label' => 'กำลังดำเนินการ'],
        'completed' => ['color' => 'bg-green-100 text-green-800', 'label' => 'เสร็จสิ้น'],
        'cancelled' => ['color' => 'bg-red-100 text-red-800', 'label' => 'ยกเลิก'],
        'open' => ['color' => 'bg-green-100 text-green-800', 'label' => 'เปิด'],
        'closed' => ['color' => 'bg-gray-100 text-gray-800', 'label' => 'ปิด']
    ];
    return $badges[$status] ?? ['color' => 'bg-gray-100 text-gray-800', 'label' => $status];
}

// Get priority color
function get_priority_color($priority) {
    $colors = [
        'high' => 'bg-red-100 text-red-800',
        'medium' => 'bg-orange-100 text-orange-800',
        'low' => 'bg-green-100 text-green-800'
    ];
    return $colors[strtolower($priority)] ?? 'bg-gray-100 text-gray-800';
}

include __DIR__ . '/includes/header_public.php';
?>

<main class="container mx-auto px-4 py-12">
    <!-- Page Title -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 mb-2">ติดตามงาน</h1>
        <p class="text-gray-600">ค้นหาและติดตามสถานะการบริการของคุณ</p>
    </div>

    <!-- Search Section -->
    <div class="max-w-2xl mx-auto mb-12">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <form method="GET" action="" class="space-y-4">
                <div>
                    <label for="ticket" class="block text-lg font-semibold text-gray-700 mb-2">
                        <i class="fas fa-search text-teal-600 mr-2"></i>ค้นหาเรื่องของคุณ
                    </label>
                    <div class="flex gap-3">
                        <input 
                            type="text" 
                            id="ticket" 
                            name="ticket" 
                            placeholder="ระบุรหัสเรื่อง เช่น REQ-2025-0005" 
                            value="<?php echo htmlspecialchars($search_ticket); ?>"
                            class="flex-1 px-4 py-3 border-2 border-gray-300 rounded-lg focus:outline-none focus:border-teal-500 text-lg"
                            required
                        >
                        <button 
                            type="submit" 
                            class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-bold transition-colors flex items-center gap-2 whitespace-nowrap"
                        >
                            <i class="fas fa-search"></i>
                            <span>ค้นหา</span>
                        </button>
                    </div>
                </div>

                <!-- QR Code Scanner Option -->
                <div class="border-t pt-4">
                    <button 
                        type="button" 
                        id="qrScannerBtn"
                        class="flex items-center gap-2 text-teal-600 hover:text-teal-700 font-semibold"
                    >
                        <i class="fas fa-qrcode"></i>
                        <span>หรือสแกน QR Code</span>
                    </button>
                </div>
            </form>

            <!-- QR Scanner (Hidden by default) -->
            <div id="qrScannerContainer" class="hidden mt-6 p-4 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <p class="text-sm text-gray-600 mb-4">โปรแกรมจะเริ่มใช้กล้องของคุณเพื่อสแกน QR Code</p>
                <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                <button 
                    type="button" 
                    id="closeScannerBtn"
                    class="mt-4 text-red-600 hover:text-red-700 font-semibold flex items-center gap-2 mx-auto"
                >
                    <i class="fas fa-times"></i>
                    <span>ปิดสแกนเนอร์</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <?php if ($error_message): ?>
        <div class="max-w-4xl mx-auto mb-8">
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-circle text-red-500 mt-1 mr-3 text-xl"></i>
                    <div>
                        <p class="text-red-800 font-semibold">ไม่พบข้อมูล</p>
                        <p class="text-red-700"><?php echo htmlspecialchars($error_message); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($tracking_data): ?>
        <div class="max-w-4xl mx-auto">
            <!-- Task Summary Card -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <!-- Header -->
                <div class="flex justify-between items-start mb-6 pb-6 border-b">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-900 mb-2"><?php echo htmlspecialchars($tracking_data['request_code']); ?></h2>
                        <p class="text-gray-600 text-lg"><?php echo htmlspecialchars($tracking_data['subject']); ?></p>
                    </div>
                    <div class="flex gap-2">
                        <span class="<?php echo get_status_badge($tracking_data['task_status'] ?? 'pending')['color']; ?> px-4 py-2 rounded-full font-semibold">
                            <?php echo get_status_badge($tracking_data['task_status'] ?? 'pending')['label']; ?>
                        </span>
                        <span class="<?php echo get_priority_color($tracking_data['priority']); ?> px-4 py-2 rounded-full font-semibold">
                            <?php 
                            $priority_labels = ['high' => 'สูง', 'medium' => 'กลาง', 'low' => 'ต่ำ'];
                            echo $priority_labels[strtolower($tracking_data['priority'])] ?? $tracking_data['priority'];
                            ?>
                        </span>
                    </div>
                </div>

                <!-- Key Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-1">ประเภทบริการ</p>
                        <p class="text-gray-900 text-lg"><?php echo get_service_name($tracking_data['service_code']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-1">วันที่ขอบริการ</p>
                        <p class="text-gray-900 text-lg"><?php echo date('d/m/Y H:i', strtotime($tracking_data['created_at'])); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-1">ชื่อผู้ขอบริการ</p>
                        <p class="text-gray-900 text-lg"><?php echo htmlspecialchars($tracking_data['requester_name']); ?></p>
                    </div>
                    <div>
                        <p class="text-gray-600 text-sm font-semibold mb-1">บุคลากรที่รับผิดชอบ</p>
                        <p class="text-gray-900 text-lg">
                            <?php 
                            if ($tracking_data['assigned_staff_name']) {
                                echo htmlspecialchars($tracking_data['assigned_staff_name']);
                                if ($tracking_data['role_name']) {
                                    echo ' (' . htmlspecialchars($tracking_data['role_name']) . ')';
                                }
                            } else {
                                echo '<span class="text-gray-500">รอการมอบหมาย</span>';
                            }
                            ?>
                        </p>
                    </div>
                </div>

                <!-- Description -->
                <?php if (!empty($tracking_data['description'])): ?>
                <div class="mb-6 pb-6 border-b">
                    <p class="text-gray-600 text-sm font-semibold mb-2">รายละเอียด</p>
                    <p class="text-gray-900 text-base leading-relaxed"><?php echo nl2br(htmlspecialchars($tracking_data['description'])); ?></p>
                </div>
                <?php endif; ?>

                <!-- Contact Information -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <p class="text-gray-600 text-sm font-semibold mb-3">ติดต่อ</p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <?php if (!empty($tracking_data['requester_phone'])): ?>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-phone text-teal-600 text-lg"></i>
                            <a href="tel:<?php echo htmlspecialchars($tracking_data['requester_phone']); ?>" class="text-teal-600 hover:text-teal-700 font-medium">
                                <?php echo htmlspecialchars($tracking_data['requester_phone']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($tracking_data['requester_email'])): ?>
                        <div class="flex items-center gap-3">
                            <i class="fas fa-envelope text-teal-600 text-lg"></i>
                            <a href="mailto:<?php echo htmlspecialchars($tracking_data['requester_email']); ?>" class="text-teal-600 hover:text-teal-700 font-medium">
                                <?php echo htmlspecialchars($tracking_data['requester_email']); ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Timeline -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">ความคืบหน้า</h3>
                <div class="space-y-4">
                    <!-- Created -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check text-green-600"></i>
                            </div>
                            <div class="w-1 bg-green-300 flex-1 my-2" style="min-height: 40px;"></div>
                        </div>
                        <div class="pt-1">
                            <p class="font-semibold text-gray-900">ส่งเรื่องแล้ว</p>
                            <p class="text-sm text-gray-600"><?php echo date('d/m/Y H:i', strtotime($tracking_data['created_at'])); ?></p>
                        </div>
                    </div>

                    <!-- Assigned -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <?php if ($tracking_data['assigned_at']): ?>
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-check text-green-600"></i>
                            </div>
                            <div class="w-1 bg-green-300 flex-1 my-2" style="min-height: 40px;"></div>
                            <?php else: ?>
                            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                <i class="fas fa-user-check text-gray-600"></i>
                            </div>
                            <div class="w-1 bg-gray-300 flex-1 my-2" style="min-height: 40px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="pt-1">
                            <p class="font-semibold text-gray-900">มอบหมายงาน</p>
                            <p class="text-sm text-gray-600">
                                <?php echo $tracking_data['assigned_at'] ? date('d/m/Y H:i', strtotime($tracking_data['assigned_at'])) : 'รอการมอบหมาย'; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Accepted -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <?php if ($tracking_data['accepted_at']): ?>
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-thumbs-up text-green-600"></i>
                            </div>
                            <div class="w-1 bg-green-300 flex-1 my-2" style="min-height: 40px;"></div>
                            <?php else: ?>
                            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                <i class="fas fa-thumbs-up text-gray-600"></i>
                            </div>
                            <div class="w-1 bg-gray-300 flex-1 my-2" style="min-height: 40px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="pt-1">
                            <p class="font-semibold text-gray-900">เจ้าหน้าที่รับงานแล้ว</p>
                            <p class="text-sm text-gray-600">
                                <?php echo $tracking_data['accepted_at'] ? date('d/m/Y H:i', strtotime($tracking_data['accepted_at'])) : 'รอเจ้าหน้าที่รับงาน'; ?>
                            </p>
                        </div>
                    </div>

                    <!-- In Progress -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <?php if ($tracking_data['started_at']): ?>
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-spinner text-green-600"></i>
                            </div>
                            <div class="w-1 bg-green-300 flex-1 my-2" style="min-height: 40px;"></div>
                            <?php else: ?>
                            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                <i class="fas fa-spinner text-gray-600"></i>
                            </div>
                            <div class="w-1 bg-gray-300 flex-1 my-2" style="min-height: 40px;"></div>
                            <?php endif; ?>
                        </div>
                        <div class="pt-1">
                            <p class="font-semibold text-gray-900">กำลังดำเนินการ</p>
                            <p class="text-sm text-gray-600">
                                <?php echo $tracking_data['started_at'] ? date('d/m/Y H:i', strtotime($tracking_data['started_at'])) : 'รอเริ่มดำเนินการ'; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Completed -->
                    <div class="flex gap-4">
                        <div class="flex flex-col items-center">
                            <?php if ($tracking_data['completed_at']): ?>
                            <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-double text-green-600"></i>
                            </div>
                            <?php else: ?>
                            <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                                <i class="fas fa-check-double text-gray-600"></i>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="pt-1">
                            <p class="font-semibold text-gray-900">เสร็จสิ้น</p>
                            <p class="text-sm text-gray-600">
                                <?php echo $tracking_data['completed_at'] ? date('d/m/Y H:i', strtotime($tracking_data['completed_at'])) : 'รอการเสร็จสิ้น'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Service Details (if any) -->
            <?php if (!empty($tracking_data['details'])): ?>
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h3 class="text-2xl font-bold text-gray-900 mb-6">ข้อมูลเพิ่มเติม</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <?php foreach ($tracking_data['details'] as $key => $value): ?>
                        <?php if ($key !== 'id' && $key !== 'request_id' && !is_null($value) && $value !== ''): ?>
                        <div class="border-b pb-3">
                            <p class="text-gray-600 text-sm font-semibold mb-1"><?php echo ucfirst(str_replace('_', ' ', $key)); ?></p>
                            <p class="text-gray-900"><?php echo htmlspecialchars($value); ?></p>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Info Box -->
    <?php if (!$tracking_data): ?>
    <div class="max-w-2xl mx-auto">
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-500 mt-1 mr-4 text-2xl"></i>
                <div>
                    <h3 class="text-blue-900 font-semibold text-lg mb-2">วิธีการค้นหา</h3>
                    <ul class="text-blue-800 space-y-1">
                        <li>• ค้นหาโดยใช้รหัสเรื่อง เช่น <strong>REQ-2025-0005</strong></li>
                        <li>• สแกน QR Code จากใบยืนยันการขอบริการ</li>
                        <li>• ระบบจะแสดงสถานะและความคืบหน้าของงาน</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</main>

<!-- Load QR Scanner Library -->
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
    let html5QrcodeScanner = null;
    const qrScannerBtn = document.getElementById('qrScannerBtn');
    const qrScannerContainer = document.getElementById('qrScannerContainer');
    const closeScannerBtn = document.getElementById('closeScannerBtn');

    qrScannerBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (qrScannerContainer.classList.contains('hidden')) {
            qrScannerContainer.classList.remove('hidden');
            initializeQRScanner();
        }
    });

    closeScannerBtn.addEventListener('click', function(e) {
        e.preventDefault();
        if (html5QrcodeScanner) {
            html5QrcodeScanner.clear();
        }
        qrScannerContainer.classList.add('hidden');
    });

    function initializeQRScanner() {
        if (html5QrcodeScanner) return;

        html5QrcodeScanner = new Html5Qrcode('reader');
        
        html5QrcodeScanner.start(
            { facingMode: 'environment' },
            {
                fps: 10,
                qrbox: 250
            },
            (decodedText, decodedResult) => {
                // Extract ticket number from QR code
                let ticketNumber = decodedText.trim();
                
                // If it's a URL, extract the ticket parameter
                if (ticketNumber.includes('?')) {
                    const params = new URLSearchParams(new URL(ticketNumber).search);
                    ticketNumber = params.get('ticket') || ticketNumber;
                }
                
                // Stop scanner and submit
                html5QrcodeScanner.pause(true);
                document.getElementById('ticket').value = ticketNumber;
                document.querySelector('form').submit();
            },
            (error) => {
                // Suppress error messages during scanning
            }
        ).catch(err => {
            console.error('Failed to initialize QR scanner:', err);
            alert('ไม่สามารถเปิดกล้องได้ โปรดตรวจสอบสิทธิ์การเข้าถึงกล้องของเบราว์เซอร์');
            qrScannerContainer.classList.add('hidden');
        });
    }
</script>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
