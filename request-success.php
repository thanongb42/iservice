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
                            <p class="text-xs text-gray-500 font-medium mb-2">สแกนเพื่อติดตามสถานะ</p>
                            <button onclick="downloadQR()" class="w-full flex items-center justify-center gap-1 px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold rounded-lg transition">
                                <i class="fas fa-download"></i> บันทึก QR Code
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Timeline Steps -->
                <div class="border-t border-gray-100 pt-8 pb-4 mb-6">
                    <h3 class="font-bold text-gray-800 mb-8 flex items-center gap-2">
                        <span class="w-8 h-8 rounded-full bg-teal-600 flex items-center justify-center">
                            <i class="fas fa-list-check text-white text-sm"></i>
                        </span>
                        ขั้นตอนการดำเนินการต่อไป
                    </h3>

                    <div class="flex flex-col md:flex-row items-stretch gap-0">

                        <!-- Step 1 -->
                        <div class="flex md:flex-col items-start md:items-center flex-1 group">
                            <div class="flex md:flex-col items-center md:items-center w-full">
                                <!-- Circle -->
                                <div class="relative flex-shrink-0">
                                    <div class="w-14 h-14 rounded-full bg-teal-500 text-white flex items-center justify-center shadow-lg shadow-teal-200 ring-4 ring-teal-50 z-10 relative transition-transform group-hover:scale-110">
                                        <i class="fas fa-inbox text-xl"></i>
                                    </div>
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-orange-400 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow">1</span>
                                </div>
                                <!-- Connector (right on desktop, bottom on mobile) -->
                                <div class="flex-1 md:hidden w-0.5 h-8 bg-gradient-to-b from-teal-300 to-blue-300 mx-auto mt-1 mb-1 ml-7"></div>
                                <div class="hidden md:block h-0.5 flex-1 bg-gradient-to-r from-teal-300 to-blue-300 mt-7 -mx-1"></div>
                            </div>
                            <div class="md:text-center mt-0 md:mt-4 ml-4 md:ml-0 pb-6 md:pb-0 pl-0 md:pl-2 pr-0 md:pr-2">
                                <h4 class="font-bold text-gray-800 text-sm">รับเรื่องและตรวจสอบ</h4>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">เจ้าหน้าที่ตรวจสอบข้อมูล<br class="hidden md:block">ความถูกต้องเบื้องต้น</p>
                            </div>
                        </div>

                        <!-- Step 2 (inactive) -->
                        <div class="flex md:flex-col items-start md:items-center flex-1 opacity-40">
                            <div class="flex md:flex-col items-center md:items-center w-full">
                                <div class="relative flex-shrink-0">
                                    <div class="w-14 h-14 rounded-full bg-gray-300 text-gray-500 flex items-center justify-center ring-4 ring-gray-100 z-10 relative">
                                        <i class="fas fa-gears text-xl"></i>
                                    </div>
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-gray-400 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow">2</span>
                                </div>
                                <div class="flex-1 md:hidden w-0.5 h-8 bg-gray-200 mx-auto mt-1 mb-1 ml-7"></div>
                                <div class="hidden md:block h-0.5 flex-1 bg-gray-200 mt-7 -mx-1"></div>
                            </div>
                            <div class="md:text-center mt-0 md:mt-4 ml-4 md:ml-0 pb-6 md:pb-0 pl-0 md:pl-2 pr-0 md:pr-2">
                                <h4 class="font-semibold text-gray-500 text-sm">ดำเนินการแก้ไข/บริการ</h4>
                                <p class="text-xs text-gray-400 mt-1 leading-relaxed">ส่งต่อหน่วยงานที่เกี่ยวข้อง<br class="hidden md:block">เพื่อดำเนินการ</p>
                            </div>
                        </div>

                        <!-- Step 3 (inactive) -->
                        <div class="flex md:flex-col items-start md:items-center flex-1 opacity-40">
                            <div class="flex md:flex-col items-center md:items-center w-full">
                                <div class="relative flex-shrink-0">
                                    <div class="w-14 h-14 rounded-full bg-gray-300 text-gray-500 flex items-center justify-center ring-4 ring-gray-100 z-10 relative">
                                        <i class="fas fa-bell text-xl"></i>
                                    </div>
                                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-gray-400 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow">3</span>
                                </div>
                                <div class="hidden md:block h-0.5 flex-1 opacity-0 mt-7"></div>
                            </div>
                            <div class="md:text-center mt-0 md:mt-4 ml-4 md:ml-0 pb-0 pl-0 md:pl-2 pr-0 md:pr-2">
                                <h4 class="font-semibold text-gray-500 text-sm">แจ้งผลการดำเนินการ</h4>
                                <p class="text-xs text-gray-400 mt-1 leading-relaxed">แจ้งผลให้ท่านทราบ<br class="hidden md:block">เมื่อดำเนินการเสร็จสิ้น</p>
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
            width: 128,
            height: 128,
            colorDark : "#0f766e",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });

        function downloadQR() {
            setTimeout(() => {
                const canvas = document.querySelector('#qrcode canvas');
                const img    = document.querySelector('#qrcode img');
                if (canvas) {
                    const link = document.createElement('a');
                    link.download = 'QRCode-<?= $request_code ?>.png';
                    link.href = canvas.toDataURL('image/png');
                    link.click();
                } else if (img) {
                    const link = document.createElement('a');
                    link.download = 'QRCode-<?= $request_code ?>.png';
                    link.href = img.src;
                    link.click();
                }
            }, 200);
        }
        
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
