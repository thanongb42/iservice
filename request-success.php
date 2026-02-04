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
    <title>ส่งคำขอสำเร็จ - เทศบาลนครรังสิต</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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
        
        /* Fixed green header */
        header.bg-teal-700 {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
        }
        
        body {
            padding-top: 80px; /* Adjust based on header height */
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
    <!-- Header -->
    <header class="bg-teal-700 text-white shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-4">
                    <img src="public/assets/images/logo/rangsit-small-logo.png" alt="Rangsit Logo" class="h-14 w-auto">
                    <div>
                        <h1 class="text-lg font-bold">เทศบาลนครรังสิต</h1>
                        <p class="text-xs text-teal-100">ระบบบริการสาธารณะออนไลน์</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow container mx-auto px-4 py-8 flex items-center justify-center">
        <div class="max-w-3xl w-full bg-white rounded-2xl shadow-xl overflow-hidden">
            <!-- Success Header -->
            <div class="bg-teal-600 text-white text-center py-10 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full opacity-10">
                    <i class="fas fa-check-circle text-[15rem] absolute -top-10 -right-10 transform rotate-45"></i>
                </div>
                
                <div class="relative z-10">
                    <div class="w-24 h-24 bg-white rounded-full mx-auto mb-6 flex items-center justify-center shadow-lg checkmark">
                        <i class="fas fa-check text-teal-600 text-5xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold mb-2">ส่งคำขอสำเร็จ!</h1>
                    <p class="text-teal-100 text-lg">ระบบได้รับแจ้งเรื่องของท่านเรียบร้อยแล้ว</p>
                </div>
            </div>

            <div class="p-8">
                <div class="flex flex-col md:flex-row gap-8 items-center justify-center mb-10">
                    <!-- Request Code Box -->
                    <div class="flex-1 w-full">
                        <div class="bg-orange-50 border-2 border-orange-200 rounded-xl p-6 text-center h-full flex flex-col justify-center relative shadow-sm">
                            <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-orange-500 text-white px-3 py-1 rounded-full text-xs font-bold shadow-sm">
                                เลขที่อ้างอิง
                            </div>
                            
                            <p class="text-gray-500 text-sm mb-2 pt-2">รหัสติดตามเรื่องของคุณคือ</p>
                            <div class="group relative inline-block">
                                <span class="text-4xl font-bold text-orange-600 font-mono tracking-wider select-all cursor-pointer" onclick="copyCode(this)" title="คลิกเพื่อคัดลอก">
                                    <?php echo $request_code; ?>
                                </span>
                            </div>
                             <p class="text-xs text-gray-400 mt-2"><i class="fas fa-info-circle mr-1"></i>ใช้รหัสนี้สำหรับติดตามสถานะการดำเนินการ</p>
                        </div>
                    </div>
                    
                    <!-- QR Code Box -->
                    <div class="flex-shrink-0">
                        <div class="bg-white p-4 rounded-xl border border-gray-200 shadow-md text-center">
                            <div id="qrcode" class="flex justify-center mb-2"></div>
                            <p class="text-xs text-gray-500 font-medium">สแกนเพื่อติดตามสถานะ</p>
                        </div>
                    </div>
                </div>

                <!-- Timeline Steps -->
                <div class="border-t border-gray-100 pt-8 pb-4 mb-6">
                    <h3 class="font-bold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-tasks text-teal-600 mr-2"></i>ขันตอนการดำเนินการต่อไป
                    </h3>
                    
                    <div class="relative">
                        <!-- Connecting Line -->
                        <div class="hidden md:block absolute top-1/2 left-0 w-full h-1 bg-gray-100 -translate-y-1/2 z-0"></div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 relative z-10">
                            <!-- Step 1 -->
                            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm text-center md:text-left md:border-none md:shadow-none md:bg-transparent">
                                <div class="w-10 h-10 bg-teal-100 text-teal-700 rounded-full flex items-center justify-center text-lg font-bold mb-3 mx-auto md:mx-0 border-2 border-white shadow-sm ring-2 ring-teal-50">1</div>
                                <h4 class="font-bold text-gray-800 text-sm">รับเรื่องและตรวจสอบ</h4>
                                <p class="text-xs text-gray-500 mt-1">เจ้าหน้าที่จะตรวจสอบข้อมูลความถูกต้องเบื้องต้น</p>
                            </div>
                            
                            <!-- Step 2 -->
                            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm text-center md:text-left md:border-none md:shadow-none md:bg-transparent">
                                <div class="w-10 h-10 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center text-lg font-bold mb-3 mx-auto md:mx-0 border-2 border-white shadow-sm ring-2 ring-blue-50">2</div>
                                <h4 class="font-bold text-gray-800 text-sm">ดำเนินการแก้ไข/บริการ</h4>
                                <p class="text-xs text-gray-500 mt-1">ส่งต่อหน่วยงานที่เกี่ยวข้องเพื่อดำเนินการ</p>
                            </div>
                            
                            <!-- Step 3 -->
                            <div class="bg-white p-4 rounded-lg border border-gray-100 shadow-sm text-center md:text-left md:border-none md:shadow-none md:bg-transparent">
                                <div class="w-10 h-10 bg-green-100 text-green-700 rounded-full flex items-center justify-center text-lg font-bold mb-3 mx-auto md:mx-0 border-2 border-white shadow-sm ring-2 ring-green-50">3</div>
                                <h4 class="font-bold text-gray-800 text-sm">แจ้งผลการดำเนินการ</h4>
                                <p class="text-xs text-gray-500 mt-1">แจ้งผลให้ท่านทราบเมื่อดำเนินการเสร็จสิ้น</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8">
                    <a href="index.php" class="flex items-center justify-center px-6 py-4 rounded-xl border border-gray-300 text-gray-600 hover:bg-gray-50 hover:text-teal-700 font-semibold transition bg-white shadow-sm group">
                        <i class="fas fa-home mr-2 group-hover:scale-110 transition-transform"></i> กลับหน้าหลัก
                    </a>
                    <a href="tracking.php?req=<?= $request_code ?>" class="flex items-center justify-center px-6 py-4 rounded-xl bg-teal-600 text-white hover:bg-teal-700 shadow-lg shadow-teal-200 font-bold transition transform hover:-translate-y-0.5 group">
                        <i class="fas fa-search mr-2 group-hover:scale-110 transition-transform"></i> ติดตามสถานะ
                    </a>
                </div>
            </div>
            
            <!-- Footer Note -->
            <div class="bg-gray-50 px-8 py-4 text-center border-t border-gray-100">
                <p class="text-xs text-gray-500">
                    หากมีข้อสงสัยเพิ่มเติม โปรดติดต่อ 02-567-xxxx ต่อ 1234 หรือ Line Official
                </p>
            </div>
        </div>
    </main>

    <script>
        // Generate QR Code
        var trackingUrl = window.location.origin + window.location.pathname.replace('request-success.php', 'tracking.php') + '?req=<?= $request_code ?>';
        
        new QRCode(document.getElementById("qrcode"), {
            text: trackingUrl,
            width: 100,
            height: 100,
            colorDark : "#0f766e", // Teal-700
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
        
        // Copy Code Function
        function copyCode(element) {
            const code = '<?= $request_code ?>';
            navigator.clipboard.writeText(code).then(() => {
                // Visual feedback
                const originalText = element.innerText;
                const originalColor = element.className;
                
                element.innerText = 'Copied!';
                element.classList.remove('text-orange-600');
                element.classList.add('text-green-600');
                
                setTimeout(() => {
                    element.innerText = originalText;
                    element.classList.remove('text-green-600');
                    element.classList.add('text-orange-600');
                }, 1000);
            });
        }
    </script>
</body>
</html>
