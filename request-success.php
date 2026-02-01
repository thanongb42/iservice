<?php
/**
 * Request Success Page
 * หน้าแสดงผลเมื่อส่งคำขอสำเร็จ
 */

$request_code = isset($_GET['code']) ? htmlspecialchars($_GET['code']) : '';

if (empty($request_code)) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ส่งคำขอสำเร็จ</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .checkmark {
            animation: checkmark 0.6s ease-in-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-teal-50 to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Success Header -->
        <div class="bg-gradient-to-r from-teal-600 to-blue-600 text-white text-center py-12">
            <div class="w-24 h-24 bg-white rounded-full mx-auto mb-6 flex items-center justify-center checkmark">
                <i class="fas fa-check text-teal-600 text-5xl"></i>
            </div>
            <h1 class="text-3xl font-bold mb-2">ส่งคำขอสำเร็จ!</h1>
            <p class="text-teal-100 text-lg">คำขอของคุณถูกส่งเข้าระบบเรียบร้อยแล้ว</p>
        </div>

        <!-- Content -->
        <div class="p-8">
            <!-- Request Code -->
            <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-orange-200 rounded-xl p-6 mb-8 text-center">
                <p class="text-gray-600 text-sm mb-2">รหัสคำขอของคุณ</p>
                <div class="flex items-center justify-center gap-2">
                    <p class="text-3xl font-mono font-bold text-orange-700"><?= $request_code ?></p>
                    <button onclick="copyCode()" class="bg-orange-200 hover:bg-orange-300 text-orange-700 px-3 py-1 rounded-lg transition text-sm" title="คัดลอก">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    <i class="fas fa-info-circle"></i> กรุณาเก็บรหัสนี้ไว้เพื่อติดตามสถานะคำขอ
                </p>
            </div>

            <!-- Next Steps -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center">
                    <i class="fas fa-list-check text-teal-600 mr-2"></i>
                    ขั้นตอนต่อไป
                </h2>

                <div class="space-y-4">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <span class="text-blue-600 font-bold">1</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">ตรวจสอบคำขอ</h3>
                            <p class="text-sm text-gray-600">ทีมงานจะตรวจสอบคำขอของคุณภายใน 24 ชั่วโมง</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                            <span class="text-orange-600 font-bold">2</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">ดำเนินการ</h3>
                            <p class="text-sm text-gray-600">เจ้าหน้าที่จะดำเนินการตามคำขอของคุณ</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <span class="text-green-600 font-bold">3</span>
                        </div>
                        <div class="flex-1">
                            <h3 class="font-semibold text-gray-900">แจ้งผลลัพธ์</h3>
                            <p class="text-sm text-gray-600">คุณจะได้รับการแจ้งผลลัพธ์ผ่านอีเมลหรือโทรศัพท์</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="bg-gray-50 rounded-xl p-6 mb-8">
                <h3 class="font-bold text-gray-900 mb-3 flex items-center">
                    <i class="fas fa-headset text-teal-600 mr-2"></i>
                    ต้องการความช่วยเหลือ?
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex items-center gap-2">
                        <i class="fas fa-phone text-gray-500"></i>
                        <span class="text-gray-700">โทร: 02-123-4567</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-envelope text-gray-500"></i>
                        <span class="text-gray-700">it.support@rangsit.go.th</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-clock text-gray-500"></i>
                        <span class="text-gray-700">จันทร์-ศุกร์ 8:30-16:30</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <i class="fas fa-building text-gray-500"></i>
                        <span class="text-gray-700">อาคาร IT ชั้น 2</span>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="index.php" class="block bg-gradient-to-r from-teal-600 to-blue-600 hover:from-teal-700 hover:to-blue-700 text-white text-center py-4 px-6 rounded-xl font-semibold transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-home mr-2"></i>กลับหน้าแรก
                </a>
                <a href="request-form.php?service=<?= $_GET['service'] ?? '' ?>" class="block bg-gray-200 hover:bg-gray-300 text-gray-700 text-center py-4 px-6 rounded-xl font-semibold transition">
                    <i class="fas fa-plus mr-2"></i>ส่งคำขออื่น
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="bg-gray-50 border-t px-8 py-6 text-center">
            <p class="text-sm text-gray-600">
                <i class="fas fa-shield-alt text-teal-600 mr-1"></i>
                ข้อมูลของคุณได้รับการปกป้องและรักษาความปลอดภัยตามมาตรฐาน
            </p>
        </div>
    </div>

    <script>
        function copyCode() {
            const code = '<?= $request_code ?>';
            navigator.clipboard.writeText(code).then(() => {
                alert('คัดลอกรหัสคำขอแล้ว: ' + code);
            });
        }

        // Auto redirect after 30 seconds (optional)
        // setTimeout(() => {
        //     window.location.href = 'index.php';
        // }, 30000);
    </script>
</body>
</html>
