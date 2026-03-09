<?php
/**
 * คู่มือการใช้งานระบบ iService
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/nav_menu_loader.php';

$nav_menus = get_menu_structure();
$nav_html  = render_nav_menu($nav_menus);
$page_title = 'คู่มือการใช้งานระบบ iService';

$extra_styles = '
    .tab-btn { transition: all .25s; }
    .tab-btn.active { background:#0d9488; color:#fff; }
    .tab-panel { display:none; }
    .tab-panel.active { display:block; }
    .step-line::before {
        content:"";
        position:absolute;
        left:1.75rem;
        top:4rem;
        bottom:-1rem;
        width:2px;
        background:linear-gradient(180deg,#0d9488,#a7f3d0);
    }
    .step-line:last-child::before { display:none; }
    .faq-answer { max-height:0; overflow:hidden; transition:max-height .35s ease; }
    .faq-answer.open { max-height:500px; }
    .service-badge { animation: float 3s ease-in-out infinite; }
    @keyframes float {
        0%,100%{ transform:translateY(0) }
        50%{ transform:translateY(-6px) }
    }
    .hero-gradient { background:linear-gradient(135deg,#134e4a 0%,#0d9488 50%,#0891b2 100%); }
    .section-gradient-1 { background:linear-gradient(135deg,#eff6ff,#dbeafe); }
    .section-gradient-2 { background:linear-gradient(135deg,#f0fdf4,#dcfce7); }
    .section-gradient-3 { background:linear-gradient(135deg,#fdf4ff,#f3e8ff); }
    .section-gradient-4 { background:linear-gradient(135deg,#fff7ed,#ffedd5); }
';

include __DIR__ . '/includes/header_public.php';
?>

<!-- ░░░ HERO ░░░ -->
<section class="hero-gradient text-white py-16 px-4 relative overflow-hidden">
    <!-- Decorative blobs -->
    <div class="absolute top-0 right-0 w-96 h-96 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 left-0 w-64 h-64 bg-teal-300/10 rounded-full translate-y-1/2 -translate-x-1/3 blur-2xl pointer-events-none"></div>

    <div class="container mx-auto max-w-5xl relative z-10">
        <div class="flex flex-col md:flex-row items-center gap-8">
            <div class="flex-1">
                <div class="inline-flex items-center gap-2 bg-white/15 backdrop-blur text-white text-sm font-semibold px-4 py-1.5 rounded-full mb-4">
                    <i class="fas fa-book-open text-yellow-300"></i>
                    คู่มือการใช้งาน
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold leading-tight mb-4">
                    ระบบ iService<br>
                    <span class="text-yellow-300">เทศบาลนครรังสิต</span>
                </h1>
                <p class="text-teal-100 text-base leading-relaxed mb-6 max-w-lg">
                    คู่มือการใช้งานครบวงจร — ตั้งแต่การยื่นคำขอบริการ,
                    การติดตามสถานะ, ไปจนถึงการรับแจ้งเตือนผ่าน LINE
                </p>
                <div class="flex flex-wrap gap-3">
                    <a href="#submit-guide" class="bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-bold px-6 py-3 rounded-xl shadow-lg transition-all hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i>วิธียื่นคำขอ
                    </a>
                    <a href="#track-guide" class="bg-white/15 hover:bg-white/25 text-white font-semibold px-6 py-3 rounded-xl backdrop-blur transition-all">
                        <i class="fas fa-search mr-2"></i>วิธีติดตามสถานะ
                    </a>
                </div>
            </div>
            <!-- Decorative icons grid -->
            <div class="hidden md:grid grid-cols-3 gap-4 flex-shrink-0">
                <?php
                $deco = [
                    ['fas fa-envelope',     'bg-blue-400/30',   '1.5s'],
                    ['fas fa-server',       'bg-purple-400/30', '2s'],
                    ['fas fa-tools',        'bg-yellow-400/30', '2.5s'],
                    ['fas fa-wifi',         'bg-cyan-400/30',   '1.8s'],
                    ['fas fa-qrcode',       'bg-green-400/30',  '1s'],
                    ['fas fa-camera',       'bg-pink-400/30',   '2.2s'],
                    ['fas fa-globe',        'bg-indigo-400/30', '1.3s'],
                    ['fas fa-print',        'bg-orange-400/30', '1.7s'],
                    ['fas fa-tv',           'bg-red-400/30',    '2.4s'],
                ];
                foreach ($deco as $d): ?>
                <div class="w-16 h-16 <?= $d[1] ?> backdrop-blur rounded-2xl flex items-center justify-center service-badge" style="animation-delay:<?= $d[2] ?>">
                    <i class="<?= $d[0] ?> text-white text-2xl"></i>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- ░░░ QUICK TABS ░░░ -->
<nav class="bg-white shadow-sm sticky top-[5rem] z-40 border-b border-gray-100">
    <div class="container mx-auto max-w-5xl px-4">
        <div class="flex gap-1 overflow-x-auto py-2 scrollbar-hide" id="tabNav">
            <?php
            $tabs = [
                ['#submit-guide',   'fas fa-paper-plane',      'ยื่นคำขอบริการ',    'tab-submit'],
                ['#track-guide',    'fas fa-magnifying-glass',  'ติดตามสถานะ',        'tab-track'],
                ['#line-guide',     'fab fa-line',              'รับแจ้งเตือน LINE',  'tab-line'],
                ['#services-guide', 'fas fa-th-large',          'บริการทั้งหมด',      'tab-svc'],
                ['#faq-guide',      'fas fa-circle-question',   'คำถามที่พบบ่อย',     'tab-faq'],
            ];
            foreach ($tabs as $t): ?>
            <a href="<?= $t[0] ?>" class="tab-btn flex items-center gap-2 whitespace-nowrap px-4 py-2.5 rounded-lg font-semibold text-sm text-gray-600 hover:bg-teal-50 hover:text-teal-700">
                <i class="<?= $t[1] ?> text-base"></i>
                <?= $t[2] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>

<main class="container mx-auto max-w-5xl px-4 py-10 space-y-16">

<!-- ░░░ SECTION 1: ยื่นคำขอ ░░░ -->
<section id="submit-guide" class="scroll-mt-32">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-12 h-12 bg-teal-100 rounded-2xl flex items-center justify-center">
            <i class="fas fa-paper-plane text-teal-600 text-xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900">วิธีการยื่นคำขอบริการ</h2>
            <p class="text-gray-500 text-sm">ขั้นตอนการกรอกแบบฟอร์มและส่งคำขอ</p>
        </div>
    </div>

    <!-- Steps -->
    <div class="space-y-2">
        <?php
        $steps = [
            [
                'num'   => '1',
                'color' => 'teal',
                'icon'  => 'fas fa-th-large',
                'title' => 'เลือกบริการที่ต้องการ',
                'desc'  => 'จากหน้าหลัก เลือกประเภทบริการที่ต้องการจากเมนูบริการ เช่น อีเมล, NAS, IT Support, QR Code, ออกแบบเว็บ ฯลฯ',
                'tip'   => 'คลิกปุ่ม "ขอรับบริการ" หรือ "ยื่นคำขอ" ใต้การ์ดบริการที่ต้องการ',
                'img_icon' => 'fas fa-hand-pointer',
            ],
            [
                'num'   => '2',
                'color' => 'blue',
                'icon'  => 'fab fa-line',
                'title' => 'เชื่อมต่อ LINE (ไม่บังคับ)',
                'desc'  => 'เพื่อรับแจ้งเตือนสถานะคำขอผ่าน LINE โดยตรง กดปุ่ม "เชื่อมต่อ LINE เพื่อรับแจ้งเตือน" สีเขียวที่ด้านบนของฟอร์ม แล้วทำตามขั้นตอน LINE Login',
                'tip'   => 'หากไม่ต้องการรับแจ้งเตือน LINE สามารถข้ามขั้นตอนนี้ได้ และกรอกอีเมลเพื่อรับแจ้งเตือนทางอีเมลแทน',
                'img_icon' => 'fab fa-line',
            ],
            [
                'num'   => '3',
                'color' => 'purple',
                'icon'  => 'fas fa-user',
                'title' => 'กรอกข้อมูลผู้ขอ',
                'desc'  => 'กรอกข้อมูลส่วนตัว ได้แก่ คำนำหน้า, ชื่อ-นามสกุล, อีเมล, เบอร์โทร, หน่วยงาน (เลือกจากเมนู 4 ระดับ) และตำแหน่ง',
                'tip'   => 'ข้อมูลที่มี * (ดาวสีแดง) เป็นข้อมูลที่จำเป็นต้องกรอก',
                'img_icon' => 'fas fa-id-card',
            ],
            [
                'num'   => '4',
                'color' => 'orange',
                'icon'  => 'fas fa-list-alt',
                'title' => 'กรอกรายละเอียดบริการ',
                'desc'  => 'กรอกข้อมูลเฉพาะของบริการที่เลือก เช่น ชื่อโปรเจค, วันที่จัดงาน, รายละเอียดงาน, และข้อกำหนดพิเศษต่างๆ',
                'tip'   => 'แต่ละบริการมีฟอร์มรายละเอียดที่แตกต่างกัน อ่านคำอธิบายของแต่ละช่องให้เข้าใจก่อนกรอก',
                'img_icon' => 'fas fa-file-alt',
            ],
            [
                'num'   => '5',
                'color' => 'rose',
                'icon'  => 'fas fa-paperclip',
                'title' => 'แนบเอกสาร (ถ้ามี)',
                'desc'  => 'สามารถแนบไฟล์รูปภาพ (JPG, PNG) หรือเอกสาร (PDF, DOCX) ได้สูงสุด 5 ไฟล์ ขนาดรวมไม่เกิน 10MB',
                'tip'   => 'เอกสารที่ควรแนบ: โครงการ, หนังสือเชิญ, กำหนดการ, ภาพตัวอย่างงาน',
                'img_icon' => 'fas fa-file-upload',
            ],
            [
                'num'   => '6',
                'color' => 'green',
                'icon'  => 'fas fa-shield-alt',
                'title' => 'กรอกรหัส CAPTCHA',
                'desc'  => 'พิมพ์ตัวอักษรที่เห็นในภาพ CAPTCHA ลงในช่องที่กำหนด เพื่อยืนยันว่าไม่ใช่โปรแกรมอัตโนมัติ หากอ่านไม่ออก กดปุ่ม <i class="fas fa-sync-alt"></i> เพื่อเปลี่ยนภาพ',
                'tip'   => 'CAPTCHA เป็นตัวอักษรภาษาอังกฤษ/ตัวเลข และระบบพิจารณาตัวพิมพ์ใหญ่-เล็ก',
                'img_icon' => 'fas fa-lock',
            ],
            [
                'num'   => '7',
                'color' => 'teal',
                'icon'  => 'fas fa-check-circle',
                'title' => 'กดส่งคำขอและบันทึกรหัส',
                'desc'  => 'กดปุ่ม "ส่งคำขอ" สีเขียว ระบบจะแสดงรหัสคำขอ เช่น REQ-2026-0001 — บันทึกรหัสนี้ไว้เพื่อใช้ติดตามสถานะในภายหลัง',
                'tip'   => 'ระบบจะส่งอีเมลยืนยันไปยังอีเมลที่กรอกไว้ และส่ง LINE notification ถ้าเชื่อมต่อ LINE ไว้แล้ว',
                'img_icon' => 'fas fa-check-double',
            ],
        ];
        foreach ($steps as $i => $step):
            $colors = [
                'teal'   => ['bg-teal-100',   'text-teal-700',   'bg-teal-600',   'border-teal-200', 'bg-teal-50'],
                'blue'   => ['bg-blue-100',   'text-blue-700',   'bg-blue-600',   'border-blue-200', 'bg-blue-50'],
                'purple' => ['bg-purple-100', 'text-purple-700', 'bg-purple-600', 'border-purple-200','bg-purple-50'],
                'orange' => ['bg-orange-100', 'text-orange-700', 'bg-orange-600', 'border-orange-200','bg-orange-50'],
                'rose'   => ['bg-rose-100',   'text-rose-700',   'bg-rose-600',   'border-rose-200', 'bg-rose-50'],
                'green'  => ['bg-green-100',  'text-green-700',  'bg-green-600',  'border-green-200','bg-green-50'],
            ];
            [$bg, $txt, $badge_bg, $border, $tip_bg] = $colors[$step['color']];
        ?>
        <div class="relative step-line flex gap-4 pb-8">
            <!-- Step badge -->
            <div class="flex-shrink-0 flex flex-col items-center">
                <div class="w-14 h-14 <?= $badge_bg ?> rounded-2xl flex items-center justify-center shadow-md z-10">
                    <i class="<?= $step['icon'] ?> text-white text-xl"></i>
                </div>
            </div>
            <!-- Content card -->
            <div class="flex-1 <?= $bg ?> border <?= $border ?> rounded-2xl p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <span class="flex-shrink-0 inline-flex items-center justify-center w-7 h-7 <?= $badge_bg ?> text-white text-xs font-black rounded-full"><?= $step['num'] ?></span>
                    <div class="flex-1">
                        <h3 class="font-bold text-gray-900 text-base mb-1"><?= $step['title'] ?></h3>
                        <p class="text-gray-700 text-sm leading-relaxed mb-3"><?= $step['desc'] ?></p>
                        <div class="<?= $tip_bg ?> border <?= $border ?> rounded-lg px-4 py-2.5 flex gap-2">
                            <i class="fas fa-lightbulb <?= $txt ?> flex-shrink-0 mt-0.5"></i>
                            <p class="text-xs text-gray-700 leading-relaxed"><?= $step['tip'] ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Result card -->
    <div class="bg-gradient-to-r from-teal-600 to-cyan-600 rounded-2xl p-6 text-white mt-4 shadow-lg">
        <div class="flex items-center gap-3 mb-3">
            <i class="fas fa-trophy text-yellow-300 text-2xl"></i>
            <h3 class="font-bold text-lg">ส่งคำขอสำเร็จ!</h3>
        </div>
        <p class="text-teal-100 text-sm leading-relaxed mb-4">หลังจากส่งคำขอแล้ว ระบบจะดำเนินการต่อไปนี้โดยอัตโนมัติ:</p>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="bg-white/15 rounded-xl p-3 text-center">
                <i class="fas fa-envelope text-yellow-300 text-2xl mb-1"></i>
                <p class="text-xs font-semibold">ส่งอีเมลยืนยัน<br>ไปยังผู้ยื่นคำขอ</p>
            </div>
            <div class="bg-white/15 rounded-xl p-3 text-center">
                <i class="fab fa-line text-green-300 text-2xl mb-1"></i>
                <p class="text-xs font-semibold">แจ้งเตือน LINE<br>(ถ้าเชื่อมต่อไว้)</p>
            </div>
            <div class="bg-white/15 rounded-xl p-3 text-center">
                <i class="fas fa-users text-blue-300 text-2xl mb-1"></i>
                <p class="text-xs font-semibold">แจ้งเจ้าหน้าที่<br>รับเรื่องทันที</p>
            </div>
        </div>
    </div>
</section>

<!-- ░░░ SECTION 2: ติดตามสถานะ ░░░ -->
<section id="track-guide" class="scroll-mt-32">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-12 h-12 bg-blue-100 rounded-2xl flex items-center justify-center">
            <i class="fas fa-magnifying-glass text-blue-600 text-xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900">วิธีติดตามสถานะคำขอ</h2>
            <p class="text-gray-500 text-sm">ตรวจสอบความคืบหน้าได้ตลอด 24 ชั่วโมง</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Method 1: URL tracking -->
        <div class="section-gradient-1 border border-blue-200 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-blue-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-link text-white"></i>
                </div>
                <h3 class="font-bold text-gray-900">วิธีที่ 1 — เข้าหน้าติดตามสถานะ</h3>
            </div>
            <ol class="space-y-3 text-sm text-gray-700">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center">1</span>
                    ไปที่เมนู <strong>"ติดตามสถานะ"</strong> หรือเข้า URL: <code class="bg-blue-100 px-1.5 py-0.5 rounded text-blue-800 text-xs">tracking.php</code>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center">2</span>
                    กรอก <strong>รหัสคำขอ</strong> เช่น <code class="bg-blue-100 px-1.5 py-0.5 rounded text-blue-800 text-xs">REQ-2026-0001</code> ในช่องค้นหา
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-blue-600 text-white text-xs font-bold rounded-full flex items-center justify-center">3</span>
                    กดปุ่ม <strong>"ติดตาม"</strong> ระบบจะแสดงสถานะและรายละเอียดคำขอ
                </li>
            </ol>
        </div>

        <!-- Method 2: Email link -->
        <div class="section-gradient-2 border border-green-200 rounded-2xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center">
                    <i class="fas fa-envelope text-white"></i>
                </div>
                <h3 class="font-bold text-gray-900">วิธีที่ 2 — ผ่านลิงก์ในอีเมล</h3>
            </div>
            <ol class="space-y-3 text-sm text-gray-700">
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-600 text-white text-xs font-bold rounded-full flex items-center justify-center">1</span>
                    เปิดอีเมลยืนยันที่ได้รับหลังส่งคำขอ จาก <strong>noreply@rangsitcity.go.th</strong>
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-600 text-white text-xs font-bold rounded-full flex items-center justify-center">2</span>
                    คลิกปุ่ม <strong>"ติดตามสถานะคำขอ"</strong> สีเขียวในอีเมล
                </li>
                <li class="flex gap-3">
                    <span class="flex-shrink-0 w-6 h-6 bg-green-600 text-white text-xs font-bold rounded-full flex items-center justify-center">3</span>
                    ระบบจะพาไปหน้าสถานะของคำขอนั้นโดยตรง
                </li>
            </ol>
        </div>
    </div>

    <!-- Status badges explanation -->
    <h3 class="font-bold text-gray-900 text-lg mb-4">ความหมายของสถานะ</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 mb-6">
        <?php
        $statuses = [
            ['⏳', 'รอการดำเนินการ', 'Pending',     'bg-yellow-50 border-yellow-200', 'text-yellow-700', 'คำขอเข้าสู่ระบบแล้ว รอเจ้าหน้าที่รับเรื่อง'],
            ['🔄', 'กำลังดำเนินการ', 'In Progress', 'bg-blue-50 border-blue-200',     'text-blue-700',   'เจ้าหน้าที่รับเรื่องและกำลังดำเนินการ'],
            ['✅', 'เสร็จสิ้น',       'Completed',  'bg-green-50 border-green-200',   'text-green-700',  'งานเสร็จเรียบร้อยแล้ว'],
            ['❌', 'ถูกปฏิเสธ',       'Rejected',   'bg-red-50 border-red-200',       'text-red-700',    'คำขอถูกปฏิเสธ (ดูเหตุผลในรายละเอียด)'],
            ['🚫', 'ยกเลิก',          'Cancelled',  'bg-gray-50 border-gray-200',     'text-gray-600',   'คำขอถูกยกเลิก'],
        ];
        foreach ($statuses as $s): ?>
        <div class="<?= $s[3] ?> border rounded-xl p-4 text-center">
            <div class="text-2xl mb-2"><?= $s[0] ?></div>
            <div class="font-bold <?= $s[4] ?> text-sm"><?= $s[1] ?></div>
            <div class="text-xs text-gray-400 mb-2"><?= $s[2] ?></div>
            <div class="text-xs text-gray-600 leading-relaxed"><?= $s[5] ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 flex gap-3">
        <i class="fas fa-triangle-exclamation text-amber-500 flex-shrink-0 mt-0.5"></i>
        <p class="text-sm text-amber-800">
            <strong>ไม่พบรหัสคำขอ?</strong> — ตรวจสอบว่ากรอกรหัสถูกต้อง (ตัวพิมพ์ใหญ่) เช่น REQ-2026-0001
            หากยังไม่พบ ติดต่อเจ้าหน้าที่โดยตรง
        </p>
    </div>
</section>

<!-- ░░░ SECTION 3: LINE Guide ░░░ -->
<section id="line-guide" class="scroll-mt-32">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-12 h-12 bg-green-100 rounded-2xl flex items-center justify-center">
            <i class="fab fa-line text-[#06C755] text-2xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900">การรับแจ้งเตือนผ่าน LINE</h2>
            <p class="text-gray-500 text-sm">ติดตามสถานะคำขอผ่าน LINE Messaging</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- LINE connect steps -->
        <div class="bg-white border border-gray-200 rounded-2xl p-6 shadow-sm">
            <h3 class="font-bold text-gray-900 mb-5 flex items-center gap-2">
                <i class="fas fa-plug text-[#06C755]"></i>
                ขั้นตอนเชื่อมต่อ LINE
            </h3>
            <div class="space-y-4">
                <?php
                $line_steps = [
                    ['fas fa-file-alt',  '#06C755', 'เปิดฟอร์มยื่นคำขอ', 'ไปที่หน้ายื่นคำขอบริการที่ต้องการ'],
                    ['fab fa-line',      '#06C755', 'กดปุ่มเชื่อมต่อ LINE', 'คลิกปุ่มสีเขียว "เชื่อมต่อ LINE เพื่อรับแจ้งเตือน" ที่ด้านบนฟอร์ม'],
                    ['fas fa-user-check','#06C755', 'Login ด้วย LINE', 'ระบบจะพาไปหน้า LINE Login — กด "Allow" เพื่ออนุญาต'],
                    ['fas fa-user-plus', '#06C755', 'เพิ่มบอทเป็นเพื่อน', 'ระบบจะแสดง prompt ให้เพิ่ม RCM-iService Bot เป็นเพื่อน — กด "Add"'],
                    ['fas fa-check-circle','#06C755','เชื่อมต่อสำเร็จ!', 'ฟอร์มจะแสดงชื่อ LINE ของคุณ แสดงว่าพร้อมรับแจ้งเตือนแล้ว'],
                    ['fas fa-paper-plane','#06C755', 'กรอกฟอร์มและส่ง', 'กรอกรายละเอียดและส่งคำขอตามปกติ — ระบบจะส่ง LINE message ให้อัตโนมัติ'],
                ];
                foreach ($line_steps as $i => $ls): ?>
                <div class="flex gap-3 items-start">
                    <div class="w-8 h-8 rounded-full flex-shrink-0 flex items-center justify-center" style="background:<?= $ls[1] ?>20">
                        <i class="<?= $ls[0] ?> text-sm" style="color:<?= $ls[1] ?>"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-900 text-sm"><?= $ls[2] ?></p>
                        <p class="text-xs text-gray-500 leading-relaxed"><?= $ls[3] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- What notifications you get -->
        <div class="space-y-4">
            <div class="bg-[#06C755] rounded-2xl p-5 text-white shadow-lg">
                <h3 class="font-bold text-lg mb-4 flex items-center gap-2">
                    <i class="fas fa-bell"></i>
                    แจ้งเตือนที่คุณจะได้รับ
                </h3>
                <div class="space-y-3">
                    <?php
                    $notifs = [
                        ['fas fa-inbox',       'เมื่อส่งคำขอสำเร็จ', 'ระบบยืนยันการรับคำขอพร้อมรหัสคำขอ และลิงก์ติดตามสถานะ'],
                        ['fas fa-sync-alt',    'เมื่อสถานะเปลี่ยน',  'แจ้งทุกครั้งที่เจ้าหน้าที่อัปเดตสถานะ พร้อมหมายเหตุ (ถ้ามี)'],
                        ['fas fa-check-circle','เมื่องานเสร็จสิ้น',   'แจ้งเมื่อคำขอได้รับการดำเนินการเสร็จสมบูรณ์'],
                    ];
                    foreach ($notifs as $n): ?>
                    <div class="bg-white/15 rounded-xl p-3 flex gap-3 items-start">
                        <i class="<?= $n[0] ?> text-yellow-300 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <p class="font-semibold text-sm"><?= $n[1] ?></p>
                            <p class="text-green-100 text-xs leading-relaxed"><?= $n[2] ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Note box -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                <h4 class="font-bold text-blue-800 text-sm mb-2 flex items-center gap-2">
                    <i class="fas fa-info-circle"></i> ข้อควรทราบ
                </h4>
                <ul class="text-xs text-blue-700 space-y-1.5 leading-relaxed list-disc list-inside">
                    <li>ต้องเพิ่ม RCM-iService Bot เป็นเพื่อนก่อน จึงจะรับ message ได้</li>
                    <li>LINE session จะถูกล้างหลังส่งคำขอสำเร็จ (เพื่อความปลอดภัย)</li>
                    <li>ถ้าต้องการส่งคำขอใหม่ ต้องเชื่อมต่อ LINE ใหม่ทุกครั้ง</li>
                    <li>ไม่เชื่อมต่อ LINE ก็ยังรับแจ้งเตือนทางอีเมลได้ตามปกติ</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- ░░░ SECTION 4: บริการทั้งหมด ░░░ -->
<section id="services-guide" class="scroll-mt-32">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-12 h-12 bg-purple-100 rounded-2xl flex items-center justify-center">
            <i class="fas fa-th-large text-purple-600 text-xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900">บริการทั้งหมดในระบบ</h2>
            <p class="text-gray-500 text-sm">รายละเอียดบริการแต่ละประเภทที่สามารถขอได้</p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $services_info = [
            [
                'code' => 'EMAIL',
                'icon' => 'fas fa-envelope',
                'color_from' => '#3b82f6', 'color_to' => '#2563eb',
                'title' => 'ขอใช้บริการอีเมล',
                'short' => 'สร้าง/แก้ไขบัญชีอีเมลองค์กร',
                'details' => ['สร้างบัญชีอีเมลใหม่', 'กำหนด Format อีเมล', 'ตั้งค่า Quota พื้นที่', 'เชื่อมต่อกับ Domain องค์กร'],
                'time' => '1-3 วันทำการ',
            ],
            [
                'code' => 'NAS',
                'icon' => 'fas fa-server',
                'color_from' => '#8b5cf6', 'color_to' => '#7c3aed',
                'title' => 'ขอพื้นที่จัดเก็บ NAS',
                'short' => 'จัดการพื้นที่เก็บข้อมูลแชร์',
                'details' => ['กำหนดชื่อ Folder', 'ขนาด Storage ที่ต้องการ', 'สิทธิ์การเข้าถึง (Read/Write)', 'กำหนดผู้ใช้ร่วม'],
                'time' => '1-2 วันทำการ',
            ],
            [
                'code' => 'IT_SUPPORT',
                'icon' => 'fas fa-tools',
                'color_from' => '#f59e0b', 'color_to' => '#d97706',
                'title' => 'ขอรับบริการ IT Support',
                'short' => 'แก้ปัญหาคอมพิวเตอร์และอุปกรณ์',
                'details' => ['ระบุประเภทปัญหา', 'แจ้งรุ่นอุปกรณ์', 'บรรยายอาการ', 'ระบุความเร่งด่วน'],
                'time' => 'ขึ้นอยู่กับความเร่งด่วน',
            ],
            [
                'code' => 'INTERNET',
                'icon' => 'fas fa-wifi',
                'color_from' => '#06b6d4', 'color_to' => '#0891b2',
                'title' => 'ขอใช้บริการอินเทอร์เน็ต',
                'short' => 'ติดตั้ง/แก้ไขปัญหาอินเทอร์เน็ต',
                'details' => ['ระบุตำแหน่งที่ต้องการ', 'อาคาร/ห้อง', 'ประเภทคำขอ', 'ปัญหาที่พบ (ถ้ามี)'],
                'time' => '2-5 วันทำการ',
            ],
            [
                'code' => 'QR_CODE',
                'icon' => 'fas fa-qrcode',
                'color_from' => '#10b981', 'color_to' => '#059669',
                'title' => 'สร้าง QR Code ฟรี',
                'short' => 'สร้าง QR Code สำหรับลิงก์/ข้อมูล',
                'details' => ['ไม่ต้องมีบัญชี', 'กำหนดสีและขนาด', 'เพิ่มโลโก้ตรงกลาง', 'ดาวน์โหลดทันที'],
                'time' => 'ทันที (บริการฟรี)',
            ],
            [
                'code' => 'PHOTOGRAPHY',
                'icon' => 'fas fa-camera',
                'color_from' => '#ec4899', 'color_to' => '#db2777',
                'title' => 'ขอบริการถ่ายภาพ',
                'short' => 'ถ่ายภาพและวิดีโองานกิจกรรม',
                'details' => ['ชื่อและประเภทงาน', 'วัน-เวลา-สถานที่', 'จำนวนช่างภาพ', 'รูปแบบการส่งมอบไฟล์'],
                'time' => 'ขึ้นอยู่กับวันจัดงาน',
            ],
            [
                'code' => 'WEB_DESIGN',
                'icon' => 'fas fa-globe',
                'color_from' => '#6366f1', 'color_to' => '#4f46e5',
                'title' => 'ขอออกแบบเว็บไซต์',
                'short' => 'พัฒนา/ออกแบบเว็บไซต์องค์กร',
                'details' => ['ประเภทเว็บไซต์', 'วัตถุประสงค์และกลุ่มเป้าหมาย', 'จำนวนหน้า', 'ฟีเจอร์ที่ต้องการ'],
                'time' => '1-4 สัปดาห์',
            ],
            [
                'code' => 'PRINTER',
                'icon' => 'fas fa-print',
                'color_from' => '#f97316', 'color_to' => '#ea580c',
                'title' => 'ขอบริการซ่อมเครื่องพิมพ์',
                'short' => 'แจ้งปัญหาเครื่องพิมพ์/ขอวัสดุสิ้นเปลือง',
                'details' => ['ประเภทและยี่ห้อ', 'ปัญหาที่พบ', 'รหัส Error (ถ้ามี)', 'หมึก/โทนเนอร์ที่ต้องการ'],
                'time' => '1-3 วันทำการ',
            ],
            [
                'code' => 'LED',
                'icon' => 'fas fa-tv',
                'color_from' => '#ef4444', 'color_to' => '#dc2626',
                'title' => 'ขอใช้จอ LED',
                'short' => 'ลงสื่อประชาสัมพันธ์บนจอ LED',
                'details' => ['ชื่อสื่อและประเภทไฟล์', 'ช่วงวันที่แสดง', 'สถานที่แสดงผล', 'อัปโหลดไฟล์ (≤200MB)'],
                'time' => '1-2 วันทำการ',
            ],
            [
                'code' => 'MC',
                'icon' => 'fas fa-microphone',
                'color_from' => '#0ea5e9', 'color_to' => '#0284c7',
                'title' => 'ขอพิธีกร (MC)',
                'short' => 'ขอพิธีกรสำหรับงานกิจกรรม',
                'details' => ['ชื่อและประเภทงาน', 'วัน-เวลา-สถานที่', 'จำนวนพิธีกร', 'ภาษาที่ใช้'],
                'time' => 'ขึ้นอยู่กับวันจัดงาน',
            ],
        ];
        foreach ($services_info as $svc): ?>
        <div class="bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <!-- Color header -->
            <div class="h-2 w-full" style="background:linear-gradient(90deg,<?= $svc['color_from'] ?>,<?= $svc['color_to'] ?>)"></div>
            <div class="p-5">
                <div class="flex items-start gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0" style="background:<?= $svc['color_from'] ?>20">
                        <i class="<?= $svc['icon'] ?> text-lg" style="color:<?= $svc['color_from'] ?>"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900 text-sm"><?= $svc['title'] ?></h4>
                        <p class="text-xs text-gray-500"><?= $svc['short'] ?></p>
                    </div>
                </div>
                <ul class="space-y-1 mb-3">
                    <?php foreach ($svc['details'] as $d): ?>
                    <li class="text-xs text-gray-600 flex gap-2">
                        <i class="fas fa-check text-green-500 flex-shrink-0 mt-0.5"></i>
                        <?= $d ?>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="text-xs text-gray-400">
                        <i class="fas fa-clock mr-1"></i><?= $svc['time'] ?>
                    </span>
                    <a href="request-form.php?service=<?= $svc['code'] ?>"
                       class="text-xs font-bold px-3 py-1.5 rounded-lg text-white transition-all hover:opacity-90"
                       style="background:<?= $svc['color_from'] ?>">
                        ยื่นคำขอ
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ░░░ SECTION 5: FAQ ░░░ -->
<section id="faq-guide" class="scroll-mt-32 pb-8">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-12 h-12 bg-orange-100 rounded-2xl flex items-center justify-center">
            <i class="fas fa-circle-question text-orange-500 text-xl"></i>
        </div>
        <div>
            <h2 class="text-2xl font-extrabold text-gray-900">คำถามที่พบบ่อย (FAQ)</h2>
            <p class="text-gray-500 text-sm">คำตอบสำหรับคำถามที่ผู้ใช้งานมักสงสัย</p>
        </div>
    </div>

    <div class="space-y-3" id="faqList">
        <?php
        $faqs = [
            ['ต้องสร้างบัญชีก่อนยื่นคำขอหรือไม่?',
             'ไม่จำเป็น! ระบบ iService อนุญาตให้ประชาชนและบุคลากรยื่นคำขอได้โดยไม่ต้องล็อกอิน (Guest Mode) เพียงกรอกชื่อ-หน่วยงาน-อีเมล ก็สามารถส่งคำขอได้ทันที'],
            ['ลืมรหัสคำขอ ต้องทำอย่างไร?',
             'ตรวจสอบในอีเมลที่กรอกไว้ตอนยื่นคำขอ จะมีอีเมลยืนยันพร้อมรหัสคำขอ หรือถ้าเชื่อมต่อ LINE ไว้ จะมีข้อความแจ้งเตือนในแอป LINE ที่บันทึกรหัสไว้ หากยังหาไม่ได้ ติดต่อเจ้าหน้าที่โดยตรง'],
            ['ยื่นคำขอแล้ว ต้องรอนานแค่ไหน?',
             'ขึ้นอยู่กับประเภทบริการ โดยทั่วไปเจ้าหน้าที่จะรับเรื่องภายใน 1 วันทำการ สำหรับงานซับซ้อนเช่นออกแบบเว็บไซต์อาจใช้เวลา 1-4 สัปดาห์ ระบบจะแจ้งเตือนเมื่อสถานะเปลี่ยน'],
            ['สามารถแก้ไขคำขอหลังส่งแล้วได้หรือไม่?',
             'ระบบยังไม่รองรับการแก้ไขคำขอหลังส่ง หากต้องการแก้ไขข้อมูล กรุณายื่นคำขอใหม่ และแจ้งให้เจ้าหน้าที่ทราบในหมายเหตุ หรือติดต่อเจ้าหน้าที่โดยตรง'],
            ['ไฟล์แนบอัปโหลดไม่ได้ ต้องแก้อย่างไร?',
             'ตรวจสอบว่าไฟล์มีนามสกุล JPG, PNG, PDF, DOC, DOCX และขนาดไม่เกิน 5MB ต่อไฟล์ รวมทุกไฟล์ไม่เกิน 10MB หากไฟล์ใหญ่เกินไป ให้บีบอัดหรือแยกส่งเป็นหลายคำขอ (สำหรับไฟล์วิดีโอ LED รองรับถึง 200MB)'],
            ['LINE notification ไม่ได้รับ ทำอย่างไร?',
             'ตรวจสอบว่าได้เพิ่ม RCM-iService Bot เป็นเพื่อนใน LINE แล้ว และตรวจสอบการตั้งค่า Notification ในแอป LINE ให้เปิดอยู่ หากยังไม่ได้รับ ลองตรวจสอบผ่านอีเมลแทน'],
            ['CAPTCHA อ่านไม่ออก ต้องทำอย่างไร?',
             'กดปุ่มไอคอนรีเฟรช (↻) ข้างภาพ CAPTCHA เพื่อเปลี่ยนรูปใหม่ หากยังอ่านไม่ออก ลองกดซ้ำจนได้ภาพที่อ่านออก ตัวอักษร CAPTCHA จะเป็นภาษาอังกฤษและตัวเลขเท่านั้น'],
            ['ส่งคำขอหน่วยงานไหนได้บ้าง?',
             'สามารถส่งคำขอได้จากทุกสำนัก/กองของเทศบาลนครรังสิต ระบบมีโครงสร้างหน่วยงาน 4 ระดับ ได้แก่ สำนัก/กอง → ส่วน → ฝ่าย/กลุ่มงาน → งาน'],
        ];
        foreach ($faqs as $i => $faq): ?>
        <div class="bg-white border border-gray-200 rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
            <button onclick="toggleFaq(<?= $i ?>)"
                    class="w-full text-left flex items-center justify-between px-5 py-4 gap-4 group">
                <div class="flex items-center gap-3">
                    <div class="w-7 h-7 bg-orange-100 rounded-lg flex items-center justify-center flex-shrink-0 group-hover:bg-orange-200 transition-colors">
                        <i class="fas fa-question text-orange-500 text-xs"></i>
                    </div>
                    <span class="font-semibold text-gray-900 text-sm"><?= $faq[0] ?></span>
                </div>
                <i class="fas fa-chevron-down text-gray-400 transition-transform duration-300 flex-shrink-0" id="faq-arrow-<?= $i ?>"></i>
            </button>
            <div class="faq-answer" id="faq-answer-<?= $i ?>">
                <div class="px-5 pb-4 ml-10 text-sm text-gray-600 leading-relaxed border-t border-gray-100 pt-3">
                    <?= $faq[1] ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ░░░ CTA ░░░ -->
<section class="bg-gradient-to-r from-teal-600 to-cyan-600 rounded-3xl p-8 text-white text-center shadow-xl mb-8">
    <i class="fas fa-headset text-4xl text-yellow-300 mb-4"></i>
    <h2 class="text-2xl font-extrabold mb-2">ยังมีข้อสงสัย?</h2>
    <p class="text-teal-100 mb-6">ติดต่อเจ้าหน้าที่ฝ่ายบริการและเผยแพร่วิชาการ เทศบาลนครรังสิต</p>
    <div class="flex flex-wrap justify-center gap-3">
        <a href="index.php" class="bg-white text-teal-700 hover:bg-teal-50 font-bold px-6 py-3 rounded-xl transition-all hover:scale-105 shadow">
            <i class="fas fa-home mr-2"></i>กลับหน้าหลัก
        </a>
        <a href="tracking.php" class="bg-yellow-400 hover:bg-yellow-300 text-gray-900 font-bold px-6 py-3 rounded-xl transition-all hover:scale-105 shadow">
            <i class="fas fa-search mr-2"></i>ติดตามคำขอ
        </a>
        <a href="line-add-friend.php" class="bg-[#06C755] hover:bg-[#05b34c] text-white font-bold px-6 py-3 rounded-xl transition-all hover:scale-105 shadow">
            <i class="fab fa-line mr-2"></i>เพิ่มเพื่อน LINE Bot
        </a>
    </div>
</section>

</main>

<script>
function toggleFaq(idx) {
    const answer = document.getElementById('faq-answer-' + idx);
    const arrow  = document.getElementById('faq-arrow-' + idx);
    const isOpen = answer.classList.contains('open');

    // Close all
    document.querySelectorAll('.faq-answer').forEach(el => el.classList.remove('open'));
    document.querySelectorAll('[id^="faq-arrow-"]').forEach(el => el.style.transform = '');

    if (!isOpen) {
        answer.classList.add('open');
        arrow.style.transform = 'rotate(180deg)';
    }
}

// Highlight active tab on scroll
const tabLinks = document.querySelectorAll('nav a[href^="#"]');
const sections = document.querySelectorAll('section[id]');

function onScroll() {
    let current = '';
    sections.forEach(s => {
        if (window.scrollY >= s.offsetTop - 160) current = s.id;
    });
    tabLinks.forEach(a => {
        a.classList.toggle('active', a.getAttribute('href') === '#' + current);
    });
}
window.addEventListener('scroll', onScroll, { passive: true });
onScroll();
</script>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
