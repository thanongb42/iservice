<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ข้อมูลธุรกรรมเมือง ศีลคำเขียว รุกรม</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Responsive Typography */
        h1 { font-size: clamp(1.25rem, 5vw, 1.875rem); }
        h2 { font-size: clamp(1.875rem, 6vw, 2.25rem); }
        h3 { font-size: clamp(1.5rem, 5vw, 1.875rem); }
        h4 { font-size: clamp(1.125rem, 4vw, 1.5rem); }
        p { font-size: clamp(0.875rem, 2vw, 1rem); }

        .hero-gradient {
            background: linear-gradient(135deg, #1e3a5f 0%, #2d5a7b 50%, #7fb069 100%);
            background-attachment: fixed;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }

        /* Sticky navbar with scroll effect */
        nav.bg-white {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header.bg-teal-700 {
            position: relative;
            z-index: 999;
        }

        /* Mobile Menu Toggle */
        .mobile-menu-btn {
            display: none;
            flex-direction: column;
            cursor: pointer;
        }

        .mobile-menu-btn span {
            width: 25px;
            height: 3px;
            background-color: #1f2937;
            margin: 5px 0;
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .mobile-menu-btn.active span:nth-child(1) {
            transform: rotate(45deg) translate(10px, 10px);
        }

        .mobile-menu-btn.active span:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-btn.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -7px);
        }

        /* Navigation Responsive */
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
            }

            nav ul {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                background-color: white;
                box-shadow: 0 8px 16px rgba(0,0,0,0.1);
                z-index: 999;
            }

            nav ul.active {
                display: flex;
            }

            nav ul li {
                width: 100%;
                border-bottom: 1px solid #e5e7eb;
            }

            nav ul li a {
                display: block;
                padding: 1rem;
            }

        /* Submenu Mobile */
        .submenu-mobile {
            display: none !important;
            background-color: #f9fafb;
        }

        .submenu-mobile.active {
            display: block !important;
        }

        .submenu-mobile a {
            padding-left: 2rem;
            font-size: 0.875rem;
        }

        /* Dropdown Toggle Arrow */
        nav ul li .dropdown-toggle {
            position: relative;
        }

        nav ul li .dropdown-toggle::after {
            content: '▼';
            position: absolute;
            right: 1rem;
            font-size: 0.625rem;
            transition: transform 0.3s ease;
        }

        nav ul li .dropdown-toggle.active::after {
            transform: rotate(180deg);
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

        /* Header Responsive */
        @media (max-width: 768px) {
            header .flex {
                flex-direction: column;
                gap: 1rem;
            }

            header input {
                width: 100%;
            }

            header h1 {
                font-size: 1.125rem;
            }

            header p {
                font-size: 0.65rem;
            }
        }

        /* Image Responsive */
        img {
            max-width: 100%;
            height: auto;
            display: block;
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
            nav, header, .mobile-menu-btn {
                display: none;
            }

            body {
                background-color: white;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-teal-700 text-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-4">
                    <img src="https://via.placeholder.com/50" alt="Logo" class="h-12">
                    <div>
                        <h1 class="text-lg font-bold">คณะกรรมการกิจการกระจายเสียง</h1>
                        <p class="text-xs">กิจการโทรทัศน์ และกิจการโทรคมนาคมแห่งชาติ</p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <input type="search" placeholder="ค้นหา" class="px-3 py-1 rounded text-gray-800 text-sm">
                    <button class="text-sm">TH | EN</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <nav class="bg-white shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between py-3">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-2">
                    <img src="images/logo/rangsit-small-logo.png" alt="Logo" class="h-10 md:h-12 w-auto">
                    <span class="text-xs md:text-sm font-semibold text-gray-800 hidden sm:inline">เทศบาลนครรังสิต</span>
                </div>
                
                <!-- Mobile Menu Toggle Button -->
                <button class="mobile-menu-btn md:hidden" id="mobileMenuToggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
                
                <!-- Menu Items -->
                <ul class="hidden md:flex space-x-2 lg:space-x-8" id="navMenu">
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-teal-600 font-medium py-3 text-sm lg:text-base">หน้าแรก</a>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-teal-600 font-medium py-3 flex items-center text-sm lg:text-base dropdown-toggle" data-dropdown="dropdown1">
                            เกี่ยวกับ กสทช.
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </a>
                        <!-- Desktop Submenu - Hidden by default, shows on hover -->
                        <div class="absolute left-0 mt-0 w-48 bg-white rounded-b-lg shadow-lg z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ประวัติ กสทช.</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">วิสัยทัศน์ พันธกิจ</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">โครงสร้างองค์กร</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm border-t">คณะกรรมการ</a>
                        </div>
                        <!-- Mobile Submenu -->
                        <div class="submenu-mobile" id="dropdown1">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ประวัติ กสทช.</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">วิสัยทัศน์ พันธกิจ</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">โครงสร้างองค์กร</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm border-t">คณะกรรมการ</a>
                        </div>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-teal-600 font-medium py-3 flex items-center text-sm lg:text-base dropdown-toggle" data-dropdown="dropdown2">
                            ข่าวสาร
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </a>
                        <!-- Desktop Submenu -->
                        <div class="absolute left-0 mt-0 w-48 bg-white rounded-b-lg shadow-lg z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ข่าวประชาสัมพันธ์</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ข่าวกิจกรรม</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">Clipping News</a>
                        </div>
                        <!-- Mobile Submenu -->
                        <div class="submenu-mobile" id="dropdown2">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ข่าวประชาสัมพันธ์</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ข่าวกิจกรรม</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">Clipping News</a>
                        </div>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-teal-600 font-medium py-3 flex items-center text-sm lg:text-base dropdown-toggle" data-dropdown="dropdown3">
                            บริการ
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </a>
                        <!-- Desktop Submenu -->
                        <div class="absolute left-0 mt-0 w-48 bg-white rounded-b-lg shadow-lg z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">บริการประชาชน</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ใบอนุญาต</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">แบบฟอร์มต่างๆ</a>
                        </div>
                        <!-- Mobile Submenu -->
                        <div class="submenu-mobile" id="dropdown3">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">บริการประชาชน</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ใบอนุญาต</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">แบบฟอร์มต่างๆ</a>
                        </div>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-teal-600 font-medium py-3 flex items-center text-sm lg:text-base dropdown-toggle" data-dropdown="dropdown4">
                            ประชาสัมพันธ์
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                            </svg>
                        </a>
                        <!-- Desktop Submenu -->
                        <div class="absolute left-0 mt-0 w-48 bg-white rounded-b-lg shadow-lg z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ข้อมูลข่าวสาร</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ศูนย์ข้อมูลข่าวสาร</a>
                        </div>
                        <!-- Mobile Submenu -->
                        <div class="submenu-mobile" id="dropdown4">
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ข้อมูลข่าวสาร</a>
                            <a href="#" class="block px-4 py-2 text-gray-700 hover:bg-teal-50 hover:text-teal-600 text-sm">ศูนย์ข้อมูลข่าวสาร</a>
                        </div>
                    </li>
                    <li class="relative group">
                        <a href="#" class="text-gray-700 hover:text-teal-600 font-medium py-3 text-sm lg:text-base">ติดต่อเรา</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Banner -->
    <section class="hero-gradient text-white py-8 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8 hero-section">
                <div class="w-full md:w-1/2 w-1-2">
                    <img src="https://via.placeholder.com/200" alt="Mascot" class="w-24 md:w-32 mb-4">
                    <h2 class="text-2xl md:text-3xl lg:text-4xl font-bold mb-4">ข้อมูลธุรกรรมเมือง<br>ศีลคำเขียว <span class="text-green-300">รุกรม</span></h2>
                    <ul class="space-y-2 text-xs md:text-sm lg:text-base">
                        <li>• การประกันคำปฏิญาณปฏิญาณเพื่อพัฒนาทักษะด้านดิจิทัล</li>
                        <li>• การพัฒนาระบบการจัดเก็บข้อมูลและการให้บริการ</li>
                        <li>• ส่งเสริมการใช้เทคโนโลยีเพื่อความโปร่งใสและตรวจสอบได้</li>
                    </ul>
                    <button class="mt-6 bg-white text-teal-700 px-4 md:px-6 py-2 rounded-full font-medium hover:bg-gray-100 text-xs md:text-sm lg:text-base transition-colors">
                        อ่านเพิ่มเติม
                    </button>
                </div>
                <div class="w-full md:w-1/2 flex justify-center md:justify-end w-1-2">
                    <img src="https://via.placeholder.com/400x300" alt="Hero Image" class="rounded-lg shadow-xl w-full max-w-xs md:max-w-sm lg:max-w-md">
                </div>
            </div>
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
                <div class="text-2xl md:text-3xl mb-2">📄</div>
                <p class="text-xs md:text-sm font-medium">คำสั่ง กสทช.</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">⚖️</div>
                <p class="text-xs md:text-sm font-medium">กฎหมายและระเบียบ</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">📢</div>
                <p class="text-xs md:text-sm font-medium">ประกาศจัดซื้อจัดจ้าง</p>
            </div>
            <div class="bg-teal-600 text-white p-3 md:p-4 rounded-lg text-center card-hover transition-all cursor-pointer">
                <div class="text-2xl md:text-3xl mb-2">📋</div>
                <p class="text-xs md:text-sm font-medium">แบบฟอร์มต่างๆ</p>
            </div>
        </div>
    </section>

    <!-- Committee Members -->
    <section class="container mx-auto px-4 py-8 md:py-16 lg:py-20">
        <h3 class="text-xl md:text-2xl lg:text-3xl font-bold text-center mb-8">คณะกรรมการกิจการกระจายเสียง</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3 md:gap-4">
            <div class="text-center">
                <img src="https://via.placeholder.com/150" alt="Member" class="rounded-lg mb-2 w-full">
                <p class="font-medium text-xs md:text-sm">นายสมชาย</p>
                <p class="text-xs text-gray-600">ประธานกรรมการ</p>
            </div>
            <div class="text-center">
                <img src="https://via.placeholder.com/150" alt="Member" class="rounded-lg mb-2 w-full">
                <p class="font-medium text-xs md:text-sm">นายวิชัย</p>
                <p class="text-xs text-gray-600">กรรมการ</p>
            </div>
            <div class="text-center">
                <img src="https://via.placeholder.com/150" alt="Member" class="rounded-lg mb-2 w-full">
                <p class="font-medium text-xs md:text-sm">นายประสิทธิ์</p>
                <p class="text-xs text-gray-600">กรรมการ</p>
            </div>
            <div class="text-center">
                <img src="https://via.placeholder.com/150" alt="Member" class="rounded-lg mb-2 w-full">
                <p class="font-medium text-xs md:text-sm">นายสุรชัย</p>
                <p class="text-xs text-gray-600">กรรมการ</p>
            </div>
            <div class="text-center">
                <img src="https://via.placeholder.com/150" alt="Member" class="rounded-lg mb-2 w-full">
                <p class="font-medium text-xs md:text-sm">นายพิชัย</p>
                <p class="text-xs text-gray-600">กรรมการ</p>
            </div>
            <div class="text-center">
                <img src="https://via.placeholder.com/150" alt="Member" class="rounded-lg mb-2 w-full">
                <p class="font-medium text-xs md:text-sm">นายสมศักดิ์</p>
                <p class="text-xs text-gray-600">กรรมการ</p>
            </div>
            <div class="text-center">
                <img src="https://via.placeholder.com/150" alt="Member" class="rounded-lg mb-2 w-full">
                <p class="font-medium text-xs md:text-sm">นายอนุชา</p>
                <p class="text-xs text-gray-600">กรรมการ</p>
            </div>
        </div>
    </section>

    <!-- News Section -->
    <section class="bg-white py-8 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <h3 class="text-xl md:text-2xl lg:text-3xl font-bold mb-8">ข่าวเด่น กสท.</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6">
                <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover transition-all">
                    <img src="https://via.placeholder.com/400x250" alt="News" class="w-full">
                    <div class="p-3 md:p-4">
                        <span class="bg-teal-600 text-white text-xs px-2 py-1 rounded inline-block">ข่าวประชาสัมพันธ์</span>
                        <h4 class="font-bold mt-2 mb-2 text-sm md:text-base">กสทช. ประชุมคณะทำงานพิจารณา...</h4>
                        <p class="text-gray-600 text-xs md:text-sm">รายละเอียดข่าวประชาสัมพันธ์เกี่ยวกับการประชุมและการดำเนินงาน...</p>
                        <p class="text-teal-600 text-xs md:text-sm mt-2">29 ธ.ค. 2568</p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover transition-all">
                    <img src="https://via.placeholder.com/400x250" alt="News" class="w-full">
                    <div class="p-3 md:p-4">
                        <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded inline-block">ข่าวกิจกรรม</span>
                        <h4 class="font-bold mt-2 mb-2 text-sm md:text-base">กิจกรรมส่งเสริมการใช้งานดิจิทัล...</h4>
                        <p class="text-gray-600 text-xs md:text-sm">รายละเอียดกิจกรรมที่จัดขึ้นเพื่อส่งเสริมการใช้เทคโนโลยี...</p>
                        <p class="text-teal-600 text-xs md:text-sm mt-2">28 ธ.ค. 2568</p>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover transition-all">
                    <img src="https://via.placeholder.com/400x250" alt="News" class="w-full">
                    <div class="p-3 md:p-4">
                        <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded inline-block">ข่าวสาร</span>
                        <h4 class="font-bold mt-2 mb-2 text-sm md:text-base">การพัฒนาระบบโครงสร้างพื้นฐาน...</h4>
                        <p class="text-gray-600 text-xs md:text-sm">ข้อมูลเกี่ยวกับการพัฒนาโครงสร้างพื้นฐานด้านดิจิทัล...</p>
                        <p class="text-teal-600 text-xs md:text-sm mt-2">27 ธ.ค. 2568</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="container mx-auto px-4 py-8 md:py-16 lg:py-20">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
            <!-- Left Column -->
            <div>
                <h3 class="text-lg md:text-xl font-bold mb-4 flex items-center">
                    <span class="bg-teal-600 text-white px-3 py-1 rounded-md mr-2 text-sm md:text-base">ข่าวประกาศ กสท.</span>
                </h3>
                <div class="space-y-3">
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all flex items-start">
                        <span class="text-teal-600 mr-2 text-lg flex-shrink-0">📄</span>
                        <div>
                            <p class="text-xs md:text-sm font-medium">กสทช. ประกาศกำหนดราคาค่าบริการโทรคมนาคม...</p>
                            <p class="text-xs text-gray-500">25 ธ.ค. 2568</p>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all flex items-start">
                        <span class="text-teal-600 mr-2 text-lg flex-shrink-0">📄</span>
                        <div>
                            <p class="text-xs md:text-sm font-medium">ประกาศรับสมัครบุคคลเพื่อแต่งตั้งเป็นเจ้าหน้าที่...</p>
                            <p class="text-xs text-gray-500">24 ธ.ค. 2568</p>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all flex items-start">
                        <span class="text-teal-600 mr-2 text-lg flex-shrink-0">📄</span>
                        <div>
                            <p class="text-xs md:text-sm font-medium">ประกาศรายชื่อผู้ผ่านการคัดเลือกเข้าทำงาน...</p>
                            <p class="text-xs text-gray-500">23 ธ.ค. 2568</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Middle Column -->
            <div>
                <h3 class="text-lg md:text-xl font-bold mb-4 flex items-center">
                    <span class="bg-purple-600 text-white px-3 py-1 rounded-md mr-2 text-sm md:text-base">ครบวาระ-เข้า 2 ปี68</span>
                </h3>
                <div class="space-y-3">
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all flex items-start">
                        <span class="text-purple-600 mr-2 text-lg flex-shrink-0">📅</span>
                        <div>
                            <p class="text-xs md:text-sm font-medium">ใบอนุญาตประกอบกิจการโทรทัศน์...</p>
                            <p class="text-xs text-gray-500">วันที่ 5 ม.ค. 2568</p>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all flex items-start">
                        <span class="text-purple-600 mr-2 text-lg flex-shrink-0">📅</span>
                        <div>
                            <p class="text-xs md:text-sm font-medium">ใบอนุญาตประกอบกิจการวิทยุกระจายเสียง...</p>
                            <p class="text-xs text-gray-500">วันที่ 10 ม.ค. 2568</p>
                        </div>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all flex items-start">
                        <span class="text-purple-600 mr-2 text-lg flex-shrink-0">📅</span>
                        <div>
                            <p class="text-xs md:text-sm font-medium">ใบอนุญาตประกอบกิจการโทรคมนาคม...</p>
                            <p class="text-xs text-gray-500">วันที่ 15 ม.ค. 2568</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div>
                <h3 class="text-lg md:text-xl font-bold mb-4">กระดานข่าวความรับรับรับข่าว</h3>
                <div class="space-y-3">
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all">
                        <p class="text-xs md:text-sm font-medium">แผนการดำเนินงานประจำปี 2568...</p>
                        <p class="text-xs text-gray-500 mt-1">รายละเอียดแผนการดำเนินงาน</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all">
                        <p class="text-xs md:text-sm font-medium">ประกาศรับฟังความคิดเห็นร่างประกาศ...</p>
                        <p class="text-xs text-gray-500 mt-1">เปิดรับฟังความคิดเห็นจนถึงวันที่ 30 ม.ค. 2568</p>
                    </div>
                    <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all">
                        <p class="text-xs md:text-sm font-medium">คู่มือการขอรับใบอนุญาตประกอบกิจการ...</p>
                        <p class="text-xs text-gray-500 mt-1">ดาวน์โหลดคู่มือได้ที่นี่</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Mobile Apps Section -->
    <section class="bg-gradient-to-r from-teal-700 to-teal-500 py-8 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row items-center justify-between gap-8">
                <div class="w-full md:w-1/3">
                    <img src="https://via.placeholder.com/300" alt="Person" class="rounded-lg w-full">
                </div>
                <div class="w-full md:w-2/3 md:pl-12">
                    <h3 class="text-2xl md:text-3xl lg:text-4xl font-bold text-white mb-6 md:mb-8">ดาวน์โหลดแอปพลิเคชัน</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-6">
                        <div class="bg-white rounded-xl p-4 md:p-6 text-center card-hover transition-all">
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-blue-500 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl">
                                📱
                            </div>
                            <h4 class="font-bold mb-2 text-sm md:text-base">แอป กสทช.</h4>
                            <p class="text-xs text-gray-600">ตรวจสอบข้อมูลและบริการต่างๆ</p>
                        </div>
                        <div class="bg-white rounded-xl p-4 md:p-6 text-center card-hover transition-all">
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-yellow-500 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl">
                                💡
                            </div>
                            <h4 class="font-bold mb-2 text-sm md:text-base">ตรวจสอบสัญญาณ</h4>
                            <p class="text-xs text-gray-600">ตรวจสอบคุณภาพสัญญาณ</p>
                        </div>
                        <div class="bg-white rounded-xl p-4 md:p-6 text-center card-hover transition-all">
                            <div class="w-20 h-20 md:w-24 md:h-24 bg-red-500 rounded-2xl mx-auto mb-4 flex items-center justify-center text-white text-2xl md:text-3xl">
                                🗳️
                            </div>
                            <h4 class="font-bold mb-2 text-sm md:text-base">แอปโหวต</h4>
                            <p class="text-xs text-gray-600">ร่วมแสดงความคิดเห็น</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="container mx-auto px-4 py-8 md:py-16 lg:py-20">
        <h3 class="text-xl md:text-2xl lg:text-3xl font-bold text-center mb-8">บริการจากกระทรวง</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
            <div class="bg-gradient-to-br from-blue-500 to-blue-700 rounded-xl p-6 md:p-8 text-white card-hover transition-all">
                <h4 class="text-xl md:text-2xl font-bold mb-4">การประเมินผล<br>การใช้บริการ</h4>
                <p class="mb-4 text-sm md:text-base">ร่วมแสดงความคิดเห็นเพื่อพัฒนาการให้บริการ</p>
                <button class="bg-white text-blue-600 px-4 md:px-6 py-2 rounded-full font-medium hover:bg-gray-100 text-sm md:text-base transition-colors">
                    เข้าสู่ระบบ →
                </button>
            </div>
            <div class="bg-gradient-to-br from-pink-500 to-pink-700 rounded-xl p-6 md:p-8 text-white card-hover transition-all">
                <h4 class="text-xl md:text-2xl font-bold mb-4">การประเมินผล<br>การใช้บริการ</h4>
                <p class="mb-4 text-sm md:text-base">ตรวจสอบและติดตามสถานะการให้บริการ</p>
                <button class="bg-white text-pink-600 px-4 md:px-6 py-2 rounded-full font-medium hover:bg-gray-100 text-sm md:text-base transition-colors">
                    เข้าสู่ระบบ →
                </button>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="bg-gray-800 text-white py-8 md:py-16 lg:py-20">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 md:gap-8">
                <div class="text-center card-hover transition-all bg-gray-700 p-4 md:p-6 rounded-lg">
                    <div class="text-3xl md:text-4xl mb-4">📞</div>
                    <h4 class="font-bold mb-2 text-base md:text-lg">ติดต่อสอบถาม</h4>
                    <p class="text-xs md:text-sm">โทร. 02-123-4567</p>
                </div>
                <div class="text-center card-hover transition-all bg-gray-700 p-4 md:p-6 rounded-lg">
                    <div class="text-3xl md:text-4xl mb-4">💬</div>
                    <h4 class="font-bold mb-2 text-base md:text-lg">ศูนย์บริการประชาชน</h4>
                    <p class="text-xs md:text-sm">สายด่วน 1234</p>
                </div>
                <div class="text-center card-hover transition-all bg-gray-700 p-4 md:p-6 rounded-lg">
                    <div class="text-3xl md:text-4xl mb-4">📧</div>
                    <h4 class="font-bold mb-2 text-base md:text-lg">สายด่วน 1444</h4>
                    <p class="text-xs md:text-sm">รับเรื่องร้องเรียน 24 ชม.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-8 md:py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6 md:gap-8 mb-8">
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">เกี่ยวกับ กสทช.</h5>
                    <ul class="space-y-2 text-xs md:text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">ประวัติ กสทช.</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">วิสัยทัศน์ พันธกิจ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">โครงสร้างองค์กร</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">บริการ</h5>
                    <ul class="space-y-2 text-xs md:text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">ใบอนุญาต กสทช.</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">ระเบียบกฎหมาย</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">แบบฟอร์มต่างๆ</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">ข้อมูล</h5>
                    <ul class="space-y-2 text-xs md:text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">ศูนย์ข้อมูลข่าวสาร</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">คำถามที่พบบ่อย</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">ดาวน์โหลด</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">ติดต่อเรา</h5>
                    <p class="text-xs md:text-sm mb-2">87 ถนนพหลโยธิน แขวงสามเสนใน</p>
                    <p class="text-xs md:text-sm mb-2">เขตพญาไท กรุงเทพฯ 10400</p>
                    <p class="text-xs md:text-sm">โทร. 02-123-4567</p>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center text-xs md:text-sm">
                <p>&copy; 2568 คณะกรรมการกิจการกระจายเสียง กิจการโทรทัศน์ และกิจการโทรคมนาคมแห่งชาติ. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Mobile Menu Toggle
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuBtn = document.getElementById('mobileMenuToggle');
            const navMenu = document.getElementById('navMenu');
            
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenuBtn.classList.toggle('active');
                    navMenu.classList.toggle('active');
                });

                // Close menu when clicking on a link
                navMenu.querySelectorAll('a').forEach(link => {
                    link.addEventListener('click', function() {
                        mobileMenuBtn.classList.remove('active');
                        navMenu.classList.remove('active');
                    });
                });
            }

            // Mobile Dropdown Toggle
            document.querySelectorAll('.dropdown-toggle').forEach(toggle => {
                toggle.addEventListener('click', function(e) {
                    if (window.innerWidth <= 768) {
                        e.preventDefault();
                        const dropdownId = this.getAttribute('data-dropdown');
                        const submenu = document.getElementById(dropdownId);
                        
                        if (submenu) {
                            // Close other open submenus
                            document.querySelectorAll('.submenu-mobile.active').forEach(menu => {
                                if (menu !== submenu) {
                                    menu.classList.remove('active');
                                }
                            });

                            // Toggle current submenu
                            submenu.classList.toggle('active');
                            this.classList.toggle('active');
                        }
                    }
                });
            });

            // Close menu when clicking outside
            document.addEventListener('click', function(e) {
                const nav = document.querySelector('nav');
                const mobileBtn = document.getElementById('mobileMenuToggle');
                
                if (nav && !nav.contains(e.target) && mobileBtn) {
                    mobileBtn.classList.remove('active');
                    navMenu.classList.remove('active');
                }
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    mobileMenuBtn.classList.remove('active');
                    navMenu.classList.remove('active');
                }
            });
        });

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
</body>
</html>