<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบบริการอิเล็กทรอนิกส์ภายใน - เทศบาลนครรังสิต</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Sarabun', 'sans-serif'],
                        display: ['Prompt', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            500: '#22c55e',
                            700: '#15803d', // สีเขียวหลัก
                            800: '#166534',
                            900: '#14532d',
                        },
                        accent: {
                            400: '#facc15', // สีทอง
                            500: '#eab308',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #166534; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #14532d; }
        
        /* Hero Pattern Overlay */
        .hero-pattern {
            background-color: #14532d;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23196c39' fill-opacity='0.4'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
        
        /* Glassmorphism Effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
    </style>
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">

    <div class="bg-brand-900 text-white text-xs py-2 border-b border-brand-800">
        <div class="container mx-auto px-4 flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <span><i class="fas fa-phone-alt mr-2 text-accent-400"></i>02-567-6000 ต่อ 151</span>
                <span class="hidden sm:inline">|</span>
                <span class="hidden sm:inline"><i class="far fa-clock mr-2 text-accent-400"></i>จันทร์ - ศุกร์ : 08.30 - 16.30 น.</span>
            </div>
            <div class="flex space-x-3">
                <a href="#" class="hover:text-accent-400 transition"><i class="fab fa-facebook-f"></i></a>
                <a href="#" class="hover:text-accent-400 transition"><i class="fab fa-line"></i></a>
                <a href="#" class="hover:text-accent-400 transition">เข้าสู่ระบบเจ้าหน้าที่</a>
            </div>
        </div>
    </div>

    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-brand-500 shadow-md">
                        <img src="images/logo/rangsit-big-logo.png" alt="Rangsit Logo" class="w-full h-full object-cover p-1">
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-display font-bold text-brand-900 leading-tight">เทศบาลนครรังสิต</h1>
                        <p class="text-sm md:text-base text-gray-600 font-medium">ฝ่ายบริการและเผยแพร่วิชาการ <span class="text-brand-700">กองยุทธศาสตร์และงบประมาณ</span></p>
                    </div>
                </div>

                <nav class="hidden lg:flex space-x-1">
                    <a href="#" class="px-4 py-2 text-brand-900 font-semibold hover:bg-brand-50 rounded-lg transition">หน้าแรก</a>
                    
                    <div class="relative group">
                        <button class="px-4 py-2 text-brand-900 font-semibold hover:bg-brand-50 rounded-lg transition flex items-center">
                            บริการออนไลน์ <i class="fas fa-chevron-down ml-2 text-xs text-gray-400 group-hover:text-brand-700"></i>
                        </button>
                        <div class="absolute top-full right-0 w-64 bg-white shadow-xl rounded-xl mt-2 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top-right border border-gray-100">
                            <a href="#" class="block px-6 py-3 text-gray-700 hover:bg-brand-50 hover:text-brand-700 border-b border-gray-50">
                                <i class="fas fa-tools w-6 text-center mr-2 text-gray-400"></i> แจ้งซ่อมคอมพิวเตอร์
                            </a>
                            <a href="#" class="block px-6 py-3 text-gray-700 hover:bg-brand-50 hover:text-brand-700 border-b border-gray-50">
                                <i class="fas fa-wifi w-6 text-center mr-2 text-gray-400"></i> ขอใช้อินเทอร์เน็ต
                            </a>
                            <a href="#" class="block px-6 py-3 text-gray-700 hover:bg-brand-50 hover:text-brand-700">
                                <i class="fas fa-hdd w-6 text-center mr-2 text-gray-400"></i> ขอพื้นที่เก็บข้อมูล
                            </a>
                        </div>
                    </div>

                    <a href="#" class="px-4 py-2 text-brand-900 font-semibold hover:bg-brand-50 rounded-lg transition">คู่มือการใช้งาน</a>
                    <a href="#" class="px-4 py-2 text-brand-900 font-semibold hover:bg-brand-50 rounded-lg transition">ติดต่อเรา</a>
                </nav>

                <button class="lg:hidden text-brand-900 text-2xl focus:outline-none">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <section class="relative bg-brand-900 py-20 overflow-hidden">
        <div class="absolute inset-0 hero-pattern opacity-20"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-brand-900 via-brand-800 to-transparent opacity-90"></div>
        
        <div class="container mx-auto px-4 relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="text-white space-y-6">
                    <div class="inline-block bg-accent-500 text-brand-900 text-xs font-bold px-3 py-1 rounded-full mb-2">
                        E-SERVICE PORTAL 2025
                    </div>
                    <h2 class="text-4xl md:text-5xl font-display font-bold leading-tight">
                        ระบบบริการวิชาการ <br>
                        <span class="text-accent-400">และสารสนเทศอัจฉริยะ</span>
                    </h2>
                    <p class="text-gray-300 text-lg font-light max-w-lg">
                        อำนวยความสะดวกในการแจ้งปัญหาด้านไอที, ขอใช้อินเทอร์เน็ต และบริการข้อมูลข่าวสาร สำหรับเจ้าหน้าที่และประชาชน
                    </p>
                    <div class="flex space-x-4 pt-4">
                        <button class="bg-accent-500 hover:bg-accent-400 text-brand-900 font-bold py-3 px-8 rounded-full shadow-lg hover:shadow-accent-500/50 transition transform hover:-translate-y-1">
                            แจ้งเรื่องทันที
                        </button>
                        <button class="bg-transparent border-2 border-white hover:bg-white hover:text-brand-900 text-white font-semibold py-3 px-8 rounded-full transition">
                            ติดตามสถานะ
                        </button>
                    </div>
                </div>
                
                <div class="hidden md:block">
                   <div class="glass-card p-8 rounded-2xl shadow-2xl max-w-md ml-auto transform rotate-1 hover:rotate-0 transition duration-500">
                        <h3 class="text-brand-900 font-bold text-xl mb-4 flex items-center">
                            <i class="fas fa-search-location text-brand-600 mr-2"></i> ติดตามสถานะคำขอ
                        </h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-gray-600 text-sm mb-1">เลขที่คำขอ / Ticket ID</label>
                                <input type="text" placeholder="เช่น REQ-2025-XXXX" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-brand-500 text-brand-900">
                            </div>
                            <button class="w-full bg-brand-700 hover:bg-brand-800 text-white font-bold py-3 rounded-lg shadow-md transition">
                                ค้นหาข้อมูล
                            </button>
                        </div>
                   </div>
                </div>
            </div>
        </div>
    </section>

    <main class="container mx-auto px-4 py-16 -mt-10 relative z-20">
        <div class="text-center mb-12">
            <span class="text-brand-600 font-bold tracking-wider uppercase text-sm">Our Services</span>
            <h3 class="text-3xl font-display font-bold text-gray-800 mt-2">เลือกประเภทบริการ</h3>
            <div class="w-20 h-1 bg-accent-500 mx-auto mt-4 rounded-full"></div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8" id="service-container">
            </div>
    </main>

    <section class="bg-gray-100 py-16 border-t border-gray-200">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="p-6 bg-white rounded-xl shadow-sm">
                    <div class="text-4xl font-bold text-brand-600 mb-2">1,250+</div>
                    <div class="text-gray-500 text-sm">คำขอที่ดำเนินการแล้ว</div>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-sm">
                    <div class="text-4xl font-bold text-brand-600 mb-2">98%</div>
                    <div class="text-gray-500 text-sm">ความพึงพอใจ</div>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-sm">
                    <div class="text-4xl font-bold text-brand-600 mb-2">24/7</div>
                    <div class="text-gray-500 text-sm">ระบบออนไลน์</div>
                </div>
                <div class="p-6 bg-white rounded-xl shadow-sm">
                    <div class="text-4xl font-bold text-brand-600 mb-2">50+</div>
                    <div class="text-gray-500 text-sm">หน่วยงานที่ใช้งาน</div>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-brand-900 text-white pt-16 pb-8 mt-auto">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-12 mb-12">
                <div class="md:col-span-2">
                    <div class="flex items-center space-x-3 mb-6">
                         <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center">
                            <img src="https://upload.wikimedia.org/wikipedia/th/a/a8/Rangsit_City_Municipality_Seal.png" alt="Logo" class="w-8 h-8">
                        </div>
                        <h4 class="text-xl font-bold font-display">เทศบาลนครรังสิต</h4>
                    </div>
                    <p class="text-brand-100 text-sm leading-relaxed max-w-md">
                        ฝ่ายบริการและเผยแพร่วิชาการ กองยุทธศาสตร์และงบประมาณ <br>
                        มุ่งมั่นพัฒนาการให้บริการด้วยเทคโนโลยีที่ทันสมัย โปร่งใส และตรวจสอบได้
                    </p>
                </div>
                
                <div>
                    <h5 class="text-lg font-bold mb-6 border-b border-brand-700 pb-2 inline-block">เมนูลัด</h5>
                    <ul class="space-y-3 text-brand-100 text-sm">
                        <li><a href="#" class="hover:text-accent-400 transition"><i class="fas fa-angle-right mr-2"></i>หน้าแรก</a></li>
                        <li><a href="#" class="hover:text-accent-400 transition"><i class="fas fa-angle-right mr-2"></i>แจ้งซ่อม</a></li>
                        <li><a href="#" class="hover:text-accent-400 transition"><i class="fas fa-angle-right mr-2"></i>ตรวจสอบสถานะ</a></li>
                        <li><a href="#" class="hover:text-accent-400 transition"><i class="fas fa-angle-right mr-2"></i>ติดต่อเจ้าหน้าที่</a></li>
                    </ul>
                </div>

                <div>
                    <h5 class="text-lg font-bold mb-6 border-b border-brand-700 pb-2 inline-block">ติดต่อเรา</h5>
                    <ul class="space-y-4 text-brand-100 text-sm">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-accent-400"></i>
                            <span>เลขที่ 449 ถ.พหลโยธิน ต.ประชาธิปัตย์ <br>อ.ธัญบุรี จ.ปทุมธานี 12130</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mt-1 mr-3 text-accent-400"></i>
                            <span>02-567-6000 ต่อ 1234</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-brand-800 pt-8 flex flex-col md:flex-row justify-between items-center text-xs text-brand-200">
                <p>&copy; 2025 Service & Academic Dissemination Section. All rights reserved.</p>
                <div class="mt-4 md:mt-0 space-x-4">
                    <a href="#" class="hover:text-white">Privacy Policy</a>
                    <a href="#" class="hover:text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // จำลองข้อมูลจาก Database (tbl_request_type)
        const services = [
            {
                code: 'EMAIL',
                name: 'อีเมลองค์กร',
                name_en: 'Email Service',
                desc: 'ขอเปิดใช้งานอีเมลใหม่, รีเซ็ตรหัสผ่าน, เพิ่มขนาดพื้นที่กล่องจดหมาย',
                icon: 'fas fa-envelope',
                color: 'blue'
            },
            {
                code: 'INTERNET',
                name: 'อินเทอร์เน็ต / WiFi',
                name_en: 'Internet Access',
                desc: 'ขอรหัสผ่าน WiFi, แจ้งปัญหาเน็ตช้า, ติดตั้งจุดกระจายสัญญาณเพิ่ม',
                icon: 'fas fa-wifi',
                color: 'indigo'
            },
            {
                code: 'IT_SUPPORT',
                name: 'แจ้งซ่อมไอที',
                name_en: 'IT Support',
                desc: 'คอมพิวเตอร์เสีย, เครื่องพิมพ์มีปัญหา, ลงโปรแกรม, ไวรัส',
                icon: 'fas fa-laptop-medical', // เปลี่ยน icon ให้สื่อกว่าเดิม
                color: 'red'
            },
            {
                code: 'STORAGE',
                name: 'พื้นที่เก็บข้อมูล',
                name_en: 'Cloud Storage',
                desc: 'ขอพื้นที่แชร์ไฟล์ส่วนกลาง (File Server), กู้คืนข้อมูลหาย',
                icon: 'fas fa-hdd',
                color: 'orange'
            },
            {
                code: 'QR_CODE',
                name: 'สร้าง QR Code',
                name_en: 'QR Generator',
                desc: 'บริการสร้าง QR Code สำหรับประชาสัมพันธ์โครงการต่างๆ',
                icon: 'fas fa-qrcode',
                color: 'slate'
            },
            {
                code: 'PHOTO',
                name: 'บริการถ่ายภาพ',
                name_en: 'Photography',
                desc: 'จองคิวช่างภาพสำหรับงานพิธี, งานกิจกรรมโครงการเทศบาล',
                icon: 'fas fa-camera',
                color: 'pink'
            }
        ];

        const container = document.getElementById('service-container');

        // ฟังก์ชันเลือกสี Class ตามข้อมูล Database
        function getColorClasses(color) {
            const map = {
                'blue':   { bg: 'bg-blue-50', text: 'text-blue-600', hover: 'hover:border-blue-200', btn: 'bg-blue-600 hover:bg-blue-700' },
                'indigo': { bg: 'bg-indigo-50', text: 'text-indigo-600', hover: 'hover:border-indigo-200', btn: 'bg-indigo-600 hover:bg-indigo-700' },
                'red':    { bg: 'bg-red-50', text: 'text-red-600', hover: 'hover:border-red-200', btn: 'bg-red-600 hover:bg-red-700' },
                'orange': { bg: 'bg-orange-50', text: 'text-orange-600', hover: 'hover:border-orange-200', btn: 'bg-orange-600 hover:bg-orange-700' },
                'pink':   { bg: 'bg-pink-50', text: 'text-pink-600', hover: 'hover:border-pink-200', btn: 'bg-pink-600 hover:bg-pink-700' },
                'slate':  { bg: 'bg-slate-100', text: 'text-slate-600', hover: 'hover:border-slate-300', btn: 'bg-slate-700 hover:bg-slate-800' },
            };
            return map[color] || map['blue'];
        }

        // Render Loop
        services.forEach(service => {
            const styles = getColorClasses(service.color);
            
            const cardHTML = `
                <div class="group bg-white rounded-2xl p-8 shadow-md border border-transparent ${styles.hover} transition-all duration-300 hover:shadow-xl hover:-translate-y-2 relative overflow-hidden">
                    
                    <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full ${styles.bg} opacity-50 group-hover:scale-150 transition-transform duration-500"></div>

                    <div class="relative z-10">
                        <div class="w-16 h-16 rounded-2xl ${styles.bg} ${styles.text} flex items-center justify-center text-3xl mb-6 shadow-sm">
                            <i class="${service.icon}"></i>
                        </div>
                        
                        <h4 class="text-xl font-bold text-gray-800 mb-1 group-hover:${styles.text} transition-colors">${service.name}</h4>
                        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">${service.name_en}</span>
                        
                        <p class="text-gray-600 text-sm mt-4 mb-6 leading-relaxed h-10 overflow-hidden text-ellipsis line-clamp-2">
                            ${service.desc}
                        </p>
                        
                        <a href="#" class="inline-flex items-center justify-center w-full py-3 px-4 rounded-lg text-white font-medium text-sm transition shadow-md ${styles.btn}">
                            <i class="far fa-edit mr-2"></i> ยื่นคำขอ
                        </a>
                    </div>
                </div>
            `;
            container.innerHTML += cardHTML;
        });
    </script>
</body>
</html>