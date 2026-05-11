<?php
session_start();
// Load navigation menu from database
require_once __DIR__ . '/includes/nav_menu_loader.php';
$nav_menus = get_menu_structure();
$nav_html = render_nav_menu($nav_menus);

// Load services from database
require_once __DIR__ . '/includes/service_loader.php';
$services = get_services();
$service_cards_html = render_service_cards($services);

// Load learning resources from database
require_once __DIR__ . '/includes/learning_resources_loader.php';
$learning_resources = get_learning_resources(9); // ดึง 9 รายการล่าสุด
$learning_header_html = render_learning_center_header();
$learning_cards_html = render_learning_resources($learning_resources);

// Load tech news from database
require_once __DIR__ . '/includes/tech_news_loader.php';
$all_news = get_all_tech_news(8); // ดึงข่าว 8 รายการ เรียงจากปักหมุด (1-4) แล้วต่อด้วยข่าวล่าสุด
$latest_news = get_latest_tech_news(3); // ดึงข่าวล่าสุด 3 ข่าว (สำหรับ sidebar)
$tech_news_html = render_tech_news_cards($all_news);
$tech_updates_html = render_tech_updates($latest_news);

$page_title = "ระบบบริการออนไลน์ | ฝ่ายบริการและเผยแพร่วิชาการ เทศบาลนครรังสิต";
$meta_description = "ยื่นคำร้องออนไลน์ ติดตามสถานะงาน และรับบริการดิจิทัลจากฝ่ายบริการและเผยแพร่วิชาการ กองยุทธศาสตร์และงบประมาณ เทศบาลนครรังสิต ปทุมธานี";
$extra_styles = '
        .hero-gradient {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a7b 50%, #7fb069 100%);
            background-attachment: fixed;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        /* Hero Section Responsive */
        @media (max-width: 768px) {
            .hero-section {
                flex-direction: column !important;
            }

            .hero-section .w-1-2 {
                width: 100% !important;
            }

            .hero-section .w-1-2 img {
                max-width: 100%;
                height: auto;
            }
        }
        
        /* Grid Responsive */
        @media (max-width: 1440px) {
            .grid-cols-7 {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .grid-cols-6 {
                grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .grid-cols-3 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-cols-4 {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .grid-cols-2 {
                grid-template-columns: 1fr;
            }

            .grid-cols-3 {
                grid-template-columns: 1fr;
            }

            .grid-cols-4 {
                grid-template-columns: 1fr;
            }

            .grid-cols-6 {
                grid-template-columns: repeat(2, 1fr);
            }

            .grid-cols-7 {
                grid-template-columns: repeat(2, 1fr);
            }

            section {
                padding: 1rem !important;
            }
        }

        /* Container Responsive */
        @media (max-width: 1280px) {
            .container {
                max-width: 1024px;
            }
        }

        @media (max-width: 1024px) {
            .container {
                max-width: 768px;
            }
        }

        @media (max-width: 768px) {
            .container {
                max-width: 100%;
                padding: 0 1rem;
            }
        }

        /* High Resolution Support */
        @media (min-width: 1920px) {
            html {
                font-size: 18px;
            }

            .container {
                max-width: 1400px;
            }
        }

        /* Tablet Optimizations */
        @media (min-width: 768px) and (max-width: 1024px) {
            nav ul {
                space-x-4 !important;
            }

            nav ul li a {
                font-size: 0.95rem;
            }
        }

        /* Landscape Mobile */
        @media (max-height: 500px) and (orientation: landscape) {
            .hero-gradient {
                padding: 0.5rem 0 !important;
            }

            section {
                padding: 1rem 0 !important;
            }
        }

        /* Print Styles */
        @media print {
            nav, header {
                display: none;
            }

            body {
                background-color: white;
            }
        }

        /* Prevent container size overrides from squeezing the sticky navbar */
        header .container { max-width: unset !important; }
';

include __DIR__ . '/includes/header_public.php';
?>
    <!-- Hero Banner -->
    <section class="relative bg-gradient-to-br from-teal-600 via-teal-700 to-blue-800 text-white py-16 md:py-20 lg:py-28 overflow-hidden">
        <!-- Background Pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute top-0 left-0 w-96 h-96 bg-white rounded-full mix-blend-overlay filter blur-3xl animate-pulse"></div>
            <div class="absolute bottom-0 right-0 w-96 h-96 bg-teal-300 rounded-full mix-blend-overlay filter blur-3xl animate-pulse" style="animation-delay: 2s;"></div>
        </div>

        <!-- Decorative Elements -->
        <div class="absolute top-20 right-10 opacity-20">
            <i class="fas fa-network-wired text-9xl"></i>
        </div>
        <div class="absolute bottom-20 left-10 opacity-10">
            <i class="fas fa-server text-8xl"></i>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <!-- Left Content -->
                <div class="space-y-6">
                    <!-- Badge -->
                    <div class="inline-flex items-center bg-white/20 backdrop-blur-sm px-4 py-2 rounded-full text-sm font-semibold">
                        <i class="fas fa-bolt text-yellow-300 mr-2"></i>
                        ระบบบริการอิเล็กทรอนิกส์ภายใน
                    </div>

                    <!-- Main Heading -->
                    <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                        ศูนย์บริการ
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-yellow-200 to-green-300">
                            เทคโนโลยีสารสนเทศ
                        </span>
                    </h1>

                    <p class="text-lg md:text-xl text-teal-50 leading-relaxed">
                        บริการด้าน IT ครบวงจร สำหรับเจ้าหน้าที่และประชาชน
                        <br class="hidden md:block">
                        ด้วยระบบที่ทันสมัย รวดเร็ว และปลอดภัย
                    </p>

                    <!-- Features List -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-4">
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check text-green-300 text-lg"></i>
                            </div>
                            <span class="text-sm md:text-base">บริการออนไลน์ 24/7</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check text-green-300 text-lg"></i>
                            </div>
                            <span class="text-sm md:text-base">ติดตามสถานะแบบ Real-time</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check text-green-300 text-lg"></i>
                            </div>
                            <span class="text-sm md:text-base">ทีมงานมืออาชีพ</span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-check text-green-300 text-lg"></i>
                            </div>
                            <span class="text-sm md:text-base">ระบบปลอดภัยสูง</span>
                        </div>
                    </div>

                    <!-- CTA Buttons -->
                    <div class="flex flex-wrap gap-4 pt-4">
                        <a href="#services" class="inline-flex items-center bg-white text-teal-700 px-8 py-4 rounded-xl font-bold hover:bg-teal-50 transition-all transform hover:scale-105 shadow-2xl">
                            <i class="fas fa-rocket mr-2"></i>
                            เริ่มใช้บริการ
                        </a>
                        <a href="speaker_map.php" class="inline-flex items-center bg-gradient-to-r from-purple-500 to-indigo-600 text-white px-8 py-4 rounded-xl font-bold hover:from-purple-600 hover:to-indigo-700 transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-broadcast-tower mr-2"></i>
                            จุดติดตั้งระบบเสียงไร้สาย
                        </a>
                        <a href="water_map.php" class="inline-flex items-center bg-gradient-to-r from-cyan-400 to-blue-500 text-white px-8 py-4 rounded-xl font-bold hover:from-cyan-500 hover:to-blue-600 transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-droplet mr-2"></i>
                            จุดติดตั้งตู้น้ำดื่ม
                        </a>
                        <a href="track.php" class="inline-flex items-center bg-gradient-to-r from-green-400 to-emerald-500 text-white px-8 py-4 rounded-xl font-bold hover:from-green-500 hover:to-emerald-600 transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-tracking mr-2"></i>
                            ติดตามงาน
                        </a>
                        <a href="pm25_dashboard.php" class="inline-flex items-center bg-gradient-to-r from-emerald-400 to-teal-500 text-white px-8 py-4 rounded-xl font-bold hover:from-emerald-500 hover:to-teal-600 transition-all transform hover:scale-105 shadow-lg">
                            <i class="fas fa-wind mr-2"></i>
                            คุณภาพอากาศ PM2.5
                        </a>
                        <a href="help.php" class="inline-flex items-center bg-transparent border-2 border-white text-white px-8 py-4 rounded-xl font-bold hover:bg-white/10 transition-all">
                            <i class="fas fa-book-open mr-2"></i>
                            คู่มือการใช้งาน
                        </a>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-3 gap-6 pt-8 border-t border-white/20">
                        <div class="text-center">
                            <div class="text-3xl md:text-4xl font-bold text-yellow-300">1,250+</div>
                            <div class="text-xs md:text-sm text-teal-100 mt-1">คำขอที่ดำเนินการ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl md:text-4xl font-bold text-yellow-300">98%</div>
                            <div class="text-xs md:text-sm text-teal-100 mt-1">ความพึงพอใจ</div>
                        </div>
                        <div class="text-center">
                            <div class="text-3xl md:text-4xl font-bold text-yellow-300">24/7</div>
                            <div class="text-xs md:text-sm text-teal-100 mt-1">ระบบออนไลน์</div>
                        </div>
                    </div>
                </div>

                <!-- Right Content - Illustration -->
                <div class="hidden md:block relative">
                    <div class="relative">
                        <!-- Main Card -->
                        <div class="bg-white/10 backdrop-blur-lg rounded-2xl p-8 shadow-2xl border border-white/20">
                            <div class="space-y-6">
                                <!-- Header -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-12 h-12 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <div class="text-sm text-teal-100">ยินดีต้อนรับ</div>
                                            <div class="font-bold">เจ้าหน้าที่เทศบาล</div>
                                        </div>
                                    </div>
                                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center animate-pulse">
                                        <i class="fas fa-check text-white"></i>
                                    </div>
                                </div>

                                <!-- Service Icons -->
                                <div class="grid grid-cols-3 gap-4">
                                    <div class="bg-white/10 rounded-xl p-4 text-center hover:bg-white/20 transition-all cursor-pointer transform hover:scale-105">
                                        <i class="fas fa-envelope text-3xl mb-2 text-blue-300"></i>
                                        <div class="text-xs">Email</div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-4 text-center hover:bg-white/20 transition-all cursor-pointer transform hover:scale-105">
                                        <i class="fas fa-wifi text-3xl mb-2 text-purple-300"></i>
                                        <div class="text-xs">Internet</div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-4 text-center hover:bg-white/20 transition-all cursor-pointer transform hover:scale-105">
                                        <i class="fas fa-tools text-3xl mb-2 text-red-300"></i>
                                        <div class="text-xs">IT Support</div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-4 text-center hover:bg-white/20 transition-all cursor-pointer transform hover:scale-105">
                                        <i class="fas fa-hdd text-3xl mb-2 text-orange-300"></i>
                                        <div class="text-xs">NAS</div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-4 text-center hover:bg-white/20 transition-all cursor-pointer transform hover:scale-105">
                                        <i class="fas fa-qrcode text-3xl mb-2 text-pink-300"></i>
                                        <div class="text-xs">QR Code</div>
                                    </div>
                                    <div class="bg-white/10 rounded-xl p-4 text-center hover:bg-white/20 transition-all cursor-pointer transform hover:scale-105">
                                        <i class="fas fa-camera text-3xl mb-2 text-green-300"></i>
                                        <div class="text-xs">Photo</div>
                                    </div>
                                </div>

                                <!-- Recent Activity -->
                                <div class="space-y-3">
                                    <div class="text-sm font-semibold text-teal-100">กิจกรรมล่าสุด</div>
                                    <div class="flex items-center space-x-3 bg-white/5 rounded-lg p-3">
                                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-xs">
                                            <i class="fas fa-envelope"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">ขอเปิดอีเมลใหม่</div>
                                            <div class="text-xs text-teal-100">5 นาทีที่แล้ว</div>
                                        </div>
                                        <div class="px-3 py-1 bg-green-500/20 text-green-300 text-xs rounded-full">
                                            สำเร็จ
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-3 bg-white/5 rounded-lg p-3">
                                        <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center text-xs">
                                            <i class="fas fa-hdd"></i>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-sm font-medium">ขอพื้นที่ NAS</div>
                                            <div class="text-xs text-teal-100">15 นาทีที่แล้ว</div>
                                        </div>
                                        <div class="px-3 py-1 bg-yellow-500/20 text-yellow-300 text-xs rounded-full">
                                            กำลังดำเนินการ
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Floating Elements -->
                        <div class="absolute -top-4 -right-4 w-20 h-20 bg-gradient-to-br from-yellow-400 to-orange-500 rounded-2xl flex items-center justify-center shadow-2xl animate-bounce">
                            <i class="fas fa-bell text-white text-2xl"></i>
                            <div class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 rounded-full flex items-center justify-center text-xs font-bold">3</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Wave Bottom -->
        <div class="absolute bottom-0 left-0 w-full overflow-hidden leading-none">
            <svg class="relative block w-full h-12 md:h-16" viewBox="0 0 1200 120" preserveAspectRatio="none">
                <path d="M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z" fill="#f9fafb"></path>
            </svg>
        </div>
    </section>

    <!-- Quick Links -->
    <section class="container mx-auto px-4 -mt-8 relative z-10">
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3 md:gap-4">
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">📱</div>
                <p class="text-xs md:text-sm font-medium">บริการประชาชน</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">📊</div>
                <p class="text-xs md:text-sm font-medium">ข้อมูลสถิติ</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">💻</div>
                <p class="text-xs md:text-sm font-medium">เทคโนโลยี IT</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">🔐</div>
                <p class="text-xs md:text-sm font-medium">ความปลอดภัย</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">☁️</div>
                <p class="text-xs md:text-sm font-medium">Cloud Services</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">📋</div>
                <p class="text-xs md:text-sm font-medium">แบบฟอร์มต่างๆ</p>
            </div>
        </div>
    </section>

    <!-- My Services Section -->
    <section id="services" class="container mx-auto px-4 py-12 md:py-16 lg:py-20">
        <div class="text-center mb-12">
            <span class="text-teal-600 font-bold tracking-wider uppercase text-sm">Our Services</span>
            <h3 class="text-3xl md:text-4xl font-bold text-gray-800 mt-2">เลือกประเภทบริการ</h3>
            <div class="w-20 h-1 bg-teal-500 mx-auto mt-4 rounded-full"></div>
            <p class="text-gray-600 mt-4 max-w-2xl mx-auto">บริการด้านเทคโนโลยีสารสนเทศและการสื่อสารที่หลากหลาย เพื่อรองรับความต้องการของเจ้าหน้าที่และประชาชน</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8">
            <?php echo $service_cards_html; ?>
        </div>


    </section>

    <!-- Learning Resources Center -->
    <section class="bg-gradient-to-br from-gray-50 to-blue-50 py-12 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <!-- Header -->
            <?php echo $learning_header_html; ?>

            <!-- Resource Cards Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
                <?php echo $learning_cards_html; ?>
            </div>

            <!-- View All Button -->
            <div class="text-center mt-12">
                <a href="learning-resources.php" class="inline-flex items-center bg-gradient-to-r from-teal-600 to-blue-600 hover:from-teal-700 hover:to-blue-700 text-white font-bold px-8 py-4 rounded-xl transition-all transform hover:scale-105 shadow-lg">
                    <i class="fas fa-book-reader mr-2"></i>
                    ดูทรัพยากรทั้งหมด
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>


        </div>
    </section>

    <!-- Technology News Section -->
    <section class="bg-white py-8 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h3 class="text-xl md:text-2xl lg:text-3xl font-bold mb-3">
                    <i class="fas fa-newspaper text-teal-600 mr-2"></i>ข่าวเทคโนโลยี
                </h3>
                <p class="text-gray-600 text-sm md:text-base">ติดตามข่าวสารและแนวโน้มเทคโนโลยีล่าสุด</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 md:gap-6">
                <?php echo $tech_news_html; ?>
            </div>


        </div>
    </section>

    <!-- Related Organizations Section -->
    <section class="bg-gradient-to-br from-gray-50 to-gray-100 py-8 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h3 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-link text-teal-600 mr-3"></i>เว็บไซต์หน่วยงานที่เกี่ยวข้อง
                </h3>
                <p class="text-gray-600 text-sm md:text-base">ลิงก์ไปยังหน่วยงานภาครัฐ องค์กรด้านเทคโนโลยี และแหล่งข้อมูลที่เป็นประโยชน์</p>
            </div>

            <!-- Government Agencies -->
            <div class="mb-12">
                <h4 class="text-xl md:text-2xl font-bold text-gray-700 mb-6 flex items-center">
                    <i class="fas fa-landmark text-blue-600 mr-3"></i>หน่วยงานภาครัฐ
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <a href="https://www.thaigov.go.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-building text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-blue-600 transition">ราชกิจจานุเบกษา</h5>
                            <p class="text-xs text-gray-500 mt-1">thaigov.go.th</p>
                        </div>
                    </a>

                    <a href="https://www.mdes.go.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-laptop-code text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-green-600 transition">ดิจิทัลเพื่อเศรษฐกิจและสังคม</h5>
                            <p class="text-xs text-gray-500 mt-1">mdes.go.th</p>
                        </div>
                    </a>

                    <a href="https://www.dga.or.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-purple-500 to-purple-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-server text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-purple-600 transition">สำนักงานพัฒนารัฐบาลดิจิทัล</h5>
                            <p class="text-xs text-gray-500 mt-1">dga.or.th</p>
                        </div>
                    </a>

                    <a href="https://www.nbtc.go.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-broadcast-tower text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-red-600 transition">กสทช.</h5>
                            <p class="text-xs text-gray-500 mt-1">nbtc.go.th</p>
                        </div>
                    </a>

                    <a href="https://www.etda.or.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-orange-500 to-orange-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-chart-line text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-orange-600 transition">สำนักงานพัฒนาธุรกรรมทางอิเล็กทรอนิกส์</h5>
                            <p class="text-xs text-gray-500 mt-1">etda.or.th</p>
                        </div>
                    </a>

                    <a href="https://www.nstda.or.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-teal-500 to-teal-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-flask text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-teal-600 transition">สวทช.</h5>
                            <p class="text-xs text-gray-500 mt-1">nstda.or.th</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Tech Organizations -->
            <div class="mb-12">
                <h4 class="text-xl md:text-2xl font-bold text-gray-700 mb-6 flex items-center">
                    <i class="fas fa-microchip text-purple-600 mr-3"></i>องค์กรด้านเทคโนโลยี
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <a href="https://www.sipa.or.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-indigo-500 to-indigo-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-shield-alt text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-indigo-600 transition">สมาคมอุตสาหกรรมซอฟต์แวร์ไทย</h5>
                            <p class="text-xs text-gray-500 mt-1">sipa.or.th</p>
                        </div>
                    </a>

                    <a href="https://www.thaicert.or.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-lock text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-red-600 transition">ThaiCERT</h5>
                            <p class="text-xs text-gray-500 mt-1">thaicert.or.th</p>
                        </div>
                    </a>

                    <a href="https://www.tcas.or.th" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-pink-500 to-pink-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-graduation-cap text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-pink-600 transition">TCAS</h5>
                            <p class="text-xs text-gray-500 mt-1">tcas.or.th</p>
                        </div>
                    </a>

                    <a href="https://www.thaiware.com" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-download text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-blue-600 transition">ThaiWare</h5>
                            <p class="text-xs text-gray-500 mt-1">thaiware.com</p>
                        </div>
                    </a>

                    <a href="https://www.itcity.com" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-shopping-cart text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-green-600 transition">IT City</h5>
                            <p class="text-xs text-gray-500 mt-1">itcity.com</p>
                        </div>
                    </a>

                    <a href="https://www.github.com" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-gray-700 to-gray-900 rounded-full flex items-center justify-center">
                                <i class="fab fa-github text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-gray-700 transition">GitHub</h5>
                            <p class="text-xs text-gray-500 mt-1">github.com</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Learning Resources -->
            <div>
                <h4 class="text-xl md:text-2xl font-bold text-gray-700 mb-6 flex items-center">
                    <i class="fas fa-book-reader text-green-600 mr-3"></i>แหล่งเรียนรู้ออนไลน์
                </h4>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                    <a href="https://www.codecademy.com" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-blue-500 to-blue-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-code text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-blue-600 transition">Codecademy</h5>
                            <p class="text-xs text-gray-500 mt-1">codecademy.com</p>
                        </div>
                    </a>

                    <a href="https://www.udemy.com" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-purple-500 to-purple-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-chalkboard-teacher text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-purple-600 transition">Udemy</h5>
                            <p class="text-xs text-gray-500 mt-1">udemy.com</p>
                        </div>
                    </a>

                    <a href="https://www.coursera.org" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-blue-600 to-blue-800 rounded-full flex items-center justify-center">
                                <i class="fas fa-university text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-blue-600 transition">Coursera</h5>
                            <p class="text-xs text-gray-500 mt-1">coursera.org</p>
                        </div>
                    </a>

                    <a href="https://www.w3schools.com" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-green-500 to-green-700 rounded-full flex items-center justify-center">
                                <i class="fas fa-laptop-code text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-green-600 transition">W3Schools</h5>
                            <p class="text-xs text-gray-500 mt-1">w3schools.com</p>
                        </div>
                    </a>

                    <a href="https://www.stackoverflow.com" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-orange-500 to-orange-700 rounded-full flex items-center justify-center">
                                <i class="fab fa-stack-overflow text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-orange-600 transition">Stack Overflow</h5>
                            <p class="text-xs text-gray-500 mt-1">stackoverflow.com</p>
                        </div>
                    </a>

                    <a href="https://www.youtube.com/education" target="_blank"
                       class="bg-white rounded-xl p-6 shadow-md hover:shadow-2xl transition-all transform hover:-translate-y-2 group">
                        <div class="text-center">
                            <div class="w-16 h-16 mx-auto mb-3 bg-gradient-to-br from-red-500 to-red-700 rounded-full flex items-center justify-center">
                                <i class="fab fa-youtube text-white text-2xl"></i>
                            </div>
                            <h5 class="font-bold text-sm text-gray-800 group-hover:text-red-600 transition">YouTube Edu</h5>
                            <p class="text-xs text-gray-500 mt-1">youtube.com/education</p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Tech Tools Section -->
    <section class="bg-gradient-to-br from-slate-900 via-purple-900 to-slate-900 py-12 md:py-20 lg:py-24 relative overflow-hidden">
        <!-- Animated Background -->
        <div class="absolute inset-0 opacity-20">
            <div class="absolute top-0 left-0 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob"></div>
            <div class="absolute top-0 right-0 w-96 h-96 bg-teal-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-2000"></div>
            <div class="absolute bottom-0 left-1/2 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl animate-blob animation-delay-4000"></div>
        </div>

        <div class="container mx-auto px-4 relative z-10">
            <!-- Header -->
            <div class="text-center mb-12 md:mb-16">
                <h3 class="text-3xl md:text-4xl lg:text-5xl font-bold text-white mb-4">
                    <i class="fas fa-rocket mr-3 text-teal-400"></i>เครื่องมือเทคโนโลยีสมัยใหม่
                </h3>
                <p class="text-gray-300 text-base md:text-lg max-w-3xl mx-auto">
                    เครื่องมือและแพลตฟอร์มที่ทันสมัย สำหรับการพัฒนา บริหารจัดการ และยกระดับองค์กรดิจิทัล
                </p>
            </div>

            <!-- Tools Grid -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4 md:gap-6">
                <!-- AI & Machine Learning -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-purple-400 to-purple-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-brain"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">AI & ML</h4>
                    <p class="text-xs text-gray-300">ปัญญาประดิษฐ์</p>
                </div>

                <!-- Cloud Computing -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-blue-400 to-blue-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-cloud"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Cloud Platform</h4>
                    <p class="text-xs text-gray-300">AWS, Azure, GCP</p>
                </div>

                <!-- DevOps -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-orange-400 to-orange-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-infinity"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">DevOps</h4>
                    <p class="text-xs text-gray-300">CI/CD, Docker</p>
                </div>

                <!-- API Development -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-green-400 to-green-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-plug"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">API Platform</h4>
                    <p class="text-xs text-gray-300">RESTful, GraphQL</p>
                </div>

                <!-- Version Control -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-gray-700 to-gray-900 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fab fa-git-alt"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Version Control</h4>
                    <p class="text-xs text-gray-300">Git, GitHub</p>
                </div>

                <!-- Database -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-teal-400 to-teal-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-database"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Database</h4>
                    <p class="text-xs text-gray-300">SQL, NoSQL, Redis</p>
                </div>

                <!-- Cybersecurity -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-red-400 to-red-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Cybersecurity</h4>
                    <p class="text-xs text-gray-300">Firewall, Encryption</p>
                </div>

                <!-- Analytics -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-pink-400 to-pink-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Analytics</h4>
                    <p class="text-xs text-gray-300">Big Data, BI</p>
                </div>

                <!-- Blockchain -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-yellow-400 to-yellow-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-link"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Blockchain</h4>
                    <p class="text-xs text-gray-300">Web3, Smart Contract</p>
                </div>

                <!-- IoT -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-indigo-400 to-indigo-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">IoT</h4>
                    <p class="text-xs text-gray-300">Internet of Things</p>
                </div>

                <!-- Automation -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-cyan-400 to-cyan-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-robot"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Automation</h4>
                    <p class="text-xs text-gray-300">RPA, Workflow</p>
                </div>

                <!-- Mobile Dev -->
                <div class="group bg-white/10 backdrop-blur-lg rounded-2xl p-6 text-center hover:bg-white/20 transition-all duration-300 transform hover:-translate-y-2 hover:shadow-2xl border border-white/20">
                    <div class="w-16 h-16 md:w-20 md:h-20 bg-gradient-to-br from-lime-400 to-lime-600 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4 class="font-bold mb-2 text-sm md:text-base text-white">Mobile Dev</h4>
                    <p class="text-xs text-gray-300">iOS, Android, Flutter</p>
                </div>
            </div>
        </div>

        <!-- Add Animation Keyframes -->
        <style>
            @keyframes blob {
                0% { transform: translate(0px, 0px) scale(1); }
                33% { transform: translate(30px, -50px) scale(1.1); }
                66% { transform: translate(-20px, 20px) scale(0.9); }
                100% { transform: translate(0px, 0px) scale(1); }
            }
            .animate-blob {
                animation: blob 7s infinite;
            }
            .animation-delay-2000 {
                animation-delay: 2s;
            }
            .animation-delay-4000 {
                animation-delay: 4s;
            }
        </style>
    </section>

 

    <!-- Contact Section -->
    <section class="bg-gray-800 text-white py-8 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-8">
                <div class="text-center card-hover transition-all bg-gray-700 p-4 md:p-6 rounded-lg">
                    <div class="text-3xl md:text-4xl mb-4">📞</div>
                    <h4 class="font-bold mb-2 text-base md:text-lg">ติดต่อสอบถาม</h4>
                    <p class="text-xs md:text-sm">โทร. 02-567-6000 ต่อ 151</p>
                </div>
                <div class="text-center card-hover transition-all bg-gray-700 p-4 md:p-6 rounded-lg">
                    <div class="text-3xl md:text-4xl mb-4">💬</div>
                    <h4 class="font-bold mb-2 text-base md:text-lg">ศูนย์บริการ Rangsit Smart City</h4>
                    <p class="text-xs md:text-sm">โทร. 02-567-6000 ต่อ 151</p>
                </div>
                <div class="text-center card-hover transition-all bg-gray-700 p-4 md:p-6 rounded-lg">
                    <div class="text-3xl md:text-4xl mb-4"><i class="fas fa-envelope text-blue-400"></i></div>
                    <h4 class="font-bold mb-2 text-base md:text-lg">Email</h4>
                    <p class="text-xs md:text-sm">rssc@rangsitcity.go.th</p>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                if (this.getAttribute('href') !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                }
            });
        });

        // Add animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card-hover').forEach(el => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = 'all 0.6s ease-out';
            observer.observe(el);
        });

        // Responsive Image Handling
        window.addEventListener('resize', debounce(function() {
            adjustResponsiveElements();
        }, 250));

        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }

        function adjustResponsiveElements() {
            const screenWidth = window.innerWidth;
            
            // Adjust font sizes for very small screens
            if (screenWidth < 480) {
                document.documentElement.style.fontSize = '14px';
            } else if (screenWidth < 768) {
                document.documentElement.style.fontSize = '15px';
            } else if (screenWidth >= 1920) {
                document.documentElement.style.fontSize = '18px';
            } else {
                document.documentElement.style.fontSize = '16px';
            }
        }

        // Initialize responsive elements on page load
        window.addEventListener('load', adjustResponsiveElements);

        // Lazy load images
        if ('IntersectionObserver' in window) {
            let imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        let img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    </script>
?>
<?php include __DIR__ . '/includes/footer_public.php'; ?>