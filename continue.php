<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ฝ่ายบริการและเผยแพร่วิชาการ - เทศบาลนครรังสิต</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .nav-submenu { display: none; }
        .nav-item:hover .nav-submenu { display: block; }
    </style>
</head>
<body class="bg-gray-50">

    <div class="bg-green-800 text-white py-2">
        <div class="container mx-auto px-4 flex justify-between text-sm">
            <div><i class="fas fa-phone mr-2"></i> ติดต่อสอบถาม: 02-567-6000 ต่อ 151</div>
            <div class="space-x-4">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fas fa-envelope"></i></a>
            </div>
        </div>
    </div>

    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex items-center">
            <img src="images/logo/rangsit-big-logo.png" alt="Logo" class="h-20 mr-4">
            <div>
                <h1 class="text-xl font-bold text-green-900">เทศบาลนครรังสิต</h1>
                <p class="text-green-700">ฝ่ายบริการและเผยแพร่วิชาการ กองยุทธศาสตร์และงบประมาณ</p>
            </div>
        </div>
    </header>

    <nav class="bg-green-700 text-white sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <ul class="flex">
                <li class="nav-item relative group">
                    <a href="#" class="block py-4 px-6 hover:bg-green-600 font-semibold">หน้าแรก</a>
                </li>
                <li class="nav-item relative group">
                    <a href="#" class="block py-4 px-6 hover:bg-green-600 font-semibold">
                        บริการออนไลน์ <i class="fas fa-chevron-down ml-1 text-xs"></i>
                    </a>
                    <ul class="nav-submenu absolute left-0 w-56 bg-white text-gray-800 shadow-xl border-t-2 border-green-500">
                        <li><a href="#" class="block px-4 py-2 hover:bg-green-50 hover:text-green-700 border-b">แจ้งซ่อมคอมพิวเตอร์</a></li>
                        <li><a href="#" class="block px-4 py-2 hover:bg-green-50 hover:text-green-700 border-b">ขอใช้พื้นที่เก็บข้อมูล</a></li>
                        <li><a href="#" class="block px-4 py-2 hover:bg-green-50 hover:text-green-700">ขอใช้อินเทอร์เน็ต</a></li>
                    </ul>
                </li>
                <li class="nav-item relative group">
                    <a href="#" class="block py-4 px-6 hover:bg-green-600 font-semibold">คลังความรู้</a>
                </li>
                <li class="nav-item relative group">
                    <a href="#" class="block py-4 px-6 hover:bg-green-600 font-semibold">ติดต่อเรา</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="bg-gradient-to-r from-green-900 to-green-600 text-white py-16">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-4">E-Service System</h2>
            <p class="text-xl opacity-90">ระบบให้บริการดิจิทัล เพื่อความสะดวกและรวดเร็วสำหรับเจ้าหน้าที่และประชาชน</p>
        </div>
    </div>

    <main class="container mx-auto px-4 py-12">
        <div class="flex items-center mb-8 border-l-8 border-green-600 pl-4">
            <h3 class="text-2xl font-bold text-gray-800">เลือกประเภทบริการ (Request Types)</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="service-grid">
            <div class="bg-white p-6 rounded-xl shadow-sm border hover:shadow-lg transition cursor-pointer">
                <div class="w-14 h-14 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-2xl mb-4">
                    <i class="fas fa-envelope"></i>
                </div>
                <h4 class="text-xl font-bold mb-2">บริการอีเมล (EMAIL)</h4>
                <p class="text-gray-600 text-sm mb-4">ยื่นคำขอใช้งานอีเมลใหม่ หรือแก้ไขรหัสผ่านสำหรับเจ้าหน้าที่</p>
                <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">แจ้งเรื่อง</button>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border hover:shadow-lg transition cursor-pointer">
                <div class="w-14 h-14 bg-green-100 text-green-600 rounded-full flex items-center justify-center text-2xl mb-4">
                    <i class="fas fa-wifi"></i>
                </div>
                <h4 class="text-xl font-bold mb-2">อินเทอร์เน็ต (INTERNET)</h4>
                <p class="text-gray-600 text-sm mb-4">ขอรับสิทธิ์เข้าใช้งานระบบเครือข่ายอินเทอร์เน็ตภายในอาคาร</p>
                <button class="bg-green-600 text-white px-4 py-2 rounded text-sm hover:bg-green-700">แจ้งเรื่อง</button>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-sm border hover:shadow-lg transition cursor-pointer">
                <div class="w-14 h-14 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-2xl mb-4">
                    <i class="fas fa-tools"></i>
                </div>
                <h4 class="text-xl font-bold mb-2">ฝ่ายไอที (IT_SUPPORT)</h4>
                <p class="text-gray-600 text-sm mb-4">แจ้งซ่อมคอมพิวเตอร์ อุปกรณ์ต่อพ่วง หรือปัญหาการใช้งานทั่วไป</p>
                <button class="bg-red-600 text-white px-4 py-2 rounded text-sm hover:bg-red-700">แจ้งเรื่อง</button>
            </div>
        </div>
    </main>

    <footer class="bg-gray-900 text-white py-12 mt-12">
        <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
            <div>
                <h5 class="text-lg font-bold mb-4">ที่อยู่หน่วยงาน</h5>
                <p class="text-gray-400 text-sm">เลขที่ 449 ถ.พหลโยธิน ต.ประชาธิปัตย์ อ.ธัญบุรี จ.ปทุมธานี 12130</p>
            </div>
            <div>
                <h5 class="text-lg font-bold mb-4">ลิงก์ที่เกี่ยวข้อง</h5>
                <ul class="text-gray-400 text-sm space-y-2">
                    <li><a href="#" class="hover:text-white">เทศบาลนครรังสิต</a></li>
                    <li><a href="#" class="hover:text-white">กองยุทธศาสตร์และงบประมาณ</a></li>
                </ul>
            </div>
            <div>
                <h5 class="text-lg font-bold mb-4">แผนที่</h5>
                <div class="bg-gray-700 h-32 rounded"></div>
            </div>
        </div>
    </footer>

    <script>
        // สามารถเพิ่ม JavaScript สำหรับการดึงข้อมูลจาก SQL API มาแสดงผลในหน้าเว็บได้ที่นี่
        console.log("ฝ่ายบริการและเผยแพร่วิชาการ System Ready");
    </script>
</body>
</html>