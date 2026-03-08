<?php
/**
 * คู่มือการใช้งานระบบ iService สำหรับเจ้าหน้าที่
 * Staff User Guide — ขั้นตอนการรับงานและอัปเดตสถานะ
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch services for the service-specific section
$services = [];
$res = $conn->query("SELECT service_code, service_name, icon FROM my_service WHERE is_active=1 ORDER BY service_code");
if ($res) while ($r = $res->fetch_assoc()) $services[$r['service_code']] = $r;

$page_title   = 'คู่มือการใช้งานระบบ';
$current_page = 'staff_guide';
$breadcrumb   = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'คู่มือการใช้งาน'],
];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<style>
.guide-step {
    counter-increment: step-counter;
    position: relative;
    padding-left: 3.5rem;
    margin-bottom: 2rem;
}
.guide-step::before {
    content: counter(step-counter);
    position: absolute;
    left: 0; top: 0;
    width: 2.25rem; height: 2.25rem;
    background: #4f46e5;
    color: white;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; font-weight: 700;
    line-height: 1;
}
.guide-steps { counter-reset: step-counter; }
.step-img { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; margin-top: 0.75rem; }
.service-pill { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.75rem;
    background: #ede9fe; color: #5b21b6; border-radius: 9999px; font-size: 0.78rem; font-weight: 600; }
.tip-box { background: #fffbeb; border: 1px solid #fbbf24; border-radius: 0.5rem; padding: 0.75rem 1rem;
    font-size: 0.875rem; color: #92400e; }
.warn-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; padding: 0.75rem 1rem;
    font-size: 0.875rem; color: #991b1b; }
.info-box { background: #eff6ff; border: 1px solid #93c5fd; border-radius: 0.5rem; padding: 0.75rem 1rem;
    font-size: 0.875rem; color: #1e40af; }
.flow-arrow { color: #6b7280; font-size: 1.25rem; margin: 0 0.25rem; }
.status-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.25rem 0.75rem; border-radius:9999px; font-size:0.8rem; font-weight:600; }
</style>

<!-- Page Header -->
<div class="mb-6">
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-3">
        <i class="fas fa-book-open text-indigo-600"></i>
        คู่มือการใช้งานระบบ iService
    </h1>
    <p class="text-gray-500 mt-1">สำหรับเจ้าหน้าที่ผู้ปฏิบัติงาน — ขั้นตอนการรับงานและอัปเดตสถานะ</p>
</div>

<!-- Quick Jump Menu -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">ไปยังหัวข้อ</p>
    <div class="flex flex-wrap gap-2">
        <a href="#overview"        class="px-3 py-1.5 text-sm bg-indigo-50 text-indigo-700 rounded-lg hover:bg-indigo-100 transition">ภาพรวมระบบ</a>
        <a href="#login"           class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">เข้าสู่ระบบ</a>
        <a href="#my-tasks"        class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">ดูงานของฉัน</a>
        <a href="#accept"          class="px-3 py-1.5 text-sm bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition">รับงาน</a>
        <a href="#in-progress"     class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">เริ่มดำเนินการ</a>
        <a href="#complete"        class="px-3 py-1.5 text-sm bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition">ส่งงาน</a>
        <a href="#services"        class="px-3 py-1.5 text-sm bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition">งานแต่ละประเภท</a>
        <a href="#status-flow"     class="px-3 py-1.5 text-sm bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition">Flow สถานะ</a>
        <a href="#planned-work"    class="px-3 py-1.5 text-sm bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition font-semibold">งานตามแผน</a>
    </div>
</div>

<!-- ── SECTION 1: Overview ─────────────────────────────────────────── -->
<div id="overview" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-sitemap text-indigo-500"></i> ภาพรวมระบบและบทบาทของเจ้าหน้าที่
    </h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-100">
            <p class="font-semibold text-yellow-800 mb-1"><i class="fas fa-user mr-1"></i> ประชาชน (ผู้ยื่น)</p>
            <ul class="text-sm text-yellow-700 space-y-1 list-disc ml-4">
                <li>กรอกแบบฟอร์มขอบริการ</li>
                <li>ติดตามสถานะผ่าน LINE / เว็บ</li>
                <li>ได้รับการแจ้งเตือนทุกขั้นตอน</li>
            </ul>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <p class="font-semibold text-blue-800 mb-1"><i class="fas fa-user-shield mr-1"></i> Admin / Manager</p>
            <ul class="text-sm text-blue-700 space-y-1 list-disc ml-4">
                <li>รับคำขอและพิจารณา</li>
                <li><strong>มอบหมายงาน</strong>ให้เจ้าหน้าที่</li>
                <li>ติดตาม ยกเลิก หรือเปลี่ยนผู้รับผิดชอบ</li>
            </ul>
        </div>
        <div class="bg-green-50 rounded-lg p-4 border border-green-100">
            <p class="font-semibold text-green-800 mb-1"><i class="fas fa-hard-hat mr-1"></i> เจ้าหน้าที่ (Staff) — คุณ</p>
            <ul class="text-sm text-green-700 space-y-1 list-disc ml-4">
                <li>ได้รับการแจ้งเตือนทาง <strong>LINE</strong></li>
                <li><strong>รับงาน</strong> → <strong>ดำเนินการ</strong> → <strong>ส่งงาน</strong></li>
                <li>อัปเดตสถานะผ่านหน้า "งานของฉัน"</li>
            </ul>
        </div>
    </div>
    <div class="info-box">
        <i class="fas fa-info-circle mr-1"></i>
        เมื่อ Admin มอบหมายงาน ระบบจะ <strong>แจ้งเตือนทาง LINE อัตโนมัติ</strong> พร้อมลิงก์เข้าดูรายละเอียดงาน
    </div>
</div>

<!-- ── SECTION 2: Login ───────────────────────────────────────────── -->
<div id="login" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-sign-in-alt text-indigo-500"></i> เข้าสู่ระบบ
    </h2>
    <div class="guide-steps">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">เปิดเว็บไซต์และล็อกอิน</p>
            <p class="text-sm text-gray-600 mt-1">ไปที่
                <a href="../login.php" target="_blank" class="text-indigo-600 hover:underline font-mono">
                    <?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/') . '/login.php'; ?>
                </a>
            </p>
            <p class="text-sm text-gray-500 mt-1">กรอก <strong>ชื่อผู้ใช้</strong> และ <strong>รหัสผ่าน</strong> ที่ผู้ดูแลระบบกำหนดให้</p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">หลังล็อกอิน — ระบบจะพาไปหน้า "งานของฉัน" โดยอัตโนมัติ</p>
            <p class="text-sm text-gray-500 mt-1">หรือคลิก <span class="font-mono bg-gray-100 px-1 rounded">งานของฉัน</span> จาก sidebar ซ้ายมือ</p>
        </div>
    </div>
</div>

<!-- ── SECTION 3: My Tasks ────────────────────────────────────────── -->
<div id="my-tasks" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-tasks text-indigo-500"></i> หน้า "งานของฉัน" (My Tasks)
    </h2>
    <p class="text-sm text-gray-600 mb-4">
        <a href="my_tasks.php" class="text-indigo-600 hover:underline font-semibold">
            <i class="fas fa-external-link-alt text-xs mr-1"></i>คลิกเพื่อไปหน้างานของฉัน
        </a>
        — แสดงงานทั้งหมดที่ได้รับมอบหมาย จัดเรียงตามความเร่งด่วน
    </p>

    <!-- Status badges -->
    <div class="flex flex-wrap gap-2 mb-4">
        <span class="status-badge bg-yellow-100 text-yellow-800"><i class="fas fa-clock"></i> รอรับงาน (pending)</span>
        <span class="status-badge bg-blue-100 text-blue-800"><i class="fas fa-check-circle"></i> รับงานแล้ว (accepted)</span>
        <span class="status-badge bg-indigo-100 text-indigo-800"><i class="fas fa-spinner"></i> กำลังดำเนินการ (in_progress)</span>
        <span class="status-badge bg-green-100 text-green-800"><i class="fas fa-flag-checkered"></i> เสร็จสิ้น (completed)</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="step-img">
            <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-filter text-gray-400 mr-1"></i> กรองงาน</p>
            <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4">
                <li>แถบ <strong>สถานะ</strong> — กรองตาม pending / accepted / in_progress / completed</li>
                <li>งานที่ <strong>เร่งด่วน (urgent/high)</strong> จะขึ้นก่อน</li>
                <li>คลิก <strong>ชื่องาน</strong> เพื่อดูรายละเอียด</li>
            </ul>
        </div>
        <div class="step-img">
            <p class="text-sm font-semibold text-gray-700 mb-2"><i class="fas fa-bell text-yellow-500 mr-1"></i> การแจ้งเตือน</p>
            <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4">
                <li>ได้รับ <strong>LINE notification</strong> เมื่อมีงานใหม่</li>
                <li>กดลิงก์ใน LINE เพื่อเปิดรายละเอียดได้ทันที</li>
                <li>ระบบแจ้งซ้ำเมื่อ Admin เปลี่ยนผู้รับผิดชอบ</li>
            </ul>
        </div>
    </div>
</div>

<!-- ── SECTION 4: Accept Task ─────────────────────────────────────── -->
<div id="accept" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-hand-paper text-green-500"></i> ขั้นตอนที่ 1 — รับงาน (Accept)
    </h2>
    <div class="guide-steps">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">คลิกงานที่ต้องการรับใน "งานของฉัน"</p>
            <p class="text-sm text-gray-500 mt-1">หรือคลิกลิงก์จาก LINE notification ที่ได้รับ</p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">อ่านรายละเอียดงานให้ครบถ้วน</p>
            <div class="step-img mt-2">
                <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4">
                    <li><strong>ชื่อผู้ยื่น</strong> และ <strong>เบอร์โทร</strong> สำหรับติดต่อ</li>
                    <li><strong>วันที่ต้องการ</strong> (due date) และความเร่งด่วน</li>
                    <li><strong>ข้อมูลเพิ่มเติม</strong> ตามประเภทบริการ (สถานที่, ประเภทงาน ฯลฯ)</li>
                    <li>หมายเหตุจาก Admin</li>
                </ul>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กดปุ่ม <span class="px-2 py-0.5 bg-green-600 text-white text-xs rounded">✓ รับงาน</span></p>
            <p class="text-sm text-gray-500 mt-1">สถานะจะเปลี่ยนเป็น <span class="status-badge bg-blue-100 text-blue-800 text-xs">accepted</span> และระบบบันทึกเวลารับงานอัตโนมัติ</p>
            <div class="tip-box mt-2">
                <i class="fas fa-lightbulb mr-1"></i>
                <strong>เมื่อคุณรับงาน</strong> — คำขอของประชาชนจะเปลี่ยนเป็น "กำลังดำเนินการ" และประชาชนจะได้รับแจ้งทาง LINE
            </div>
        </div>
    </div>
</div>

<!-- ── SECTION 5: In Progress ─────────────────────────────────────── -->
<div id="in-progress" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-play-circle text-blue-500"></i> ขั้นตอนที่ 2 — เริ่มดำเนินการ (In Progress)
    </h2>
    <div class="guide-steps">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">เมื่อเริ่มปฏิบัติงานจริง กดปุ่ม <span class="px-2 py-0.5 bg-blue-600 text-white text-xs rounded">▶ เริ่มดำเนินการ</span></p>
            <p class="text-sm text-gray-500 mt-1">ระบบบันทึก <strong>เวลาเริ่มงาน</strong> ไว้โดยอัตโนมัติ</p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">ปฏิบัติงานตามที่ได้รับมอบหมาย</p>
            <div class="step-img mt-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">ช่างภาพ / Photography</p>
                    <ul class="text-sm text-gray-600 list-disc ml-4 space-y-0.5">
                        <li>ตรวจสอบสถานที่ วันเวลา จำนวนผู้เข้าร่วม</li>
                        <li>เตรียมอุปกรณ์ถ่ายภาพ/วีดีโอ</li>
                        <li>ถ่ายภาพงานตามที่แจ้งไว้</li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">พิธีกร / MC</p>
                    <ul class="text-sm text-gray-600 list-disc ml-4 space-y-0.5">
                        <li>ตรวจสอบกำหนดการงาน</li>
                        <li>เตรียมบทพิธีกร</li>
                        <li>ประสานงานผู้จัดงาน</li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">IT Support</p>
                    <ul class="text-sm text-gray-600 list-disc ml-4 space-y-0.5">
                        <li>ติดต่อผู้แจ้งเพื่อนัดเวลา</li>
                        <li>เข้าดูเครื่อง/แก้ปัญหา</li>
                        <li>ทดสอบก่อนส่งคืน</li>
                    </ul>
                </div>
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase mb-1">LED / เสียง</p>
                    <ul class="text-sm text-gray-600 list-disc ml-4 space-y-0.5">
                        <li>ตรวจสอบสถานที่ติดตั้ง</li>
                        <li>ติดตั้งและทดสอบอุปกรณ์</li>
                        <li>อยู่ประจำจนงานเสร็จ</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── SECTION 6: Complete ────────────────────────────────────────── -->
<div id="complete" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-flag-checkered text-purple-500"></i> ขั้นตอนที่ 3 — ส่งงาน / เสร็จสิ้น (Complete)
    </h2>
    <div class="guide-steps">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กลับมาที่หน้ารายละเอียดงาน</p>
            <p class="text-sm text-gray-500 mt-1">
                <a href="my_tasks.php" class="text-indigo-600 hover:underline">งานของฉัน</a>
                → คลิกงานที่ต้องการส่ง → กดปุ่ม
                <span class="px-2 py-0.5 bg-purple-600 text-white text-xs rounded">✓ เสร็จสิ้น</span>
            </p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กรอก <strong>หมายเหตุการส่งงาน</strong> (ถ้ามี)</p>
            <div class="step-img mt-1">
                <p class="text-sm text-gray-600">เช่น: "ถ่ายภาพเสร็จแล้ว รวม 120 ภาพ ส่งไฟล์ผ่าน Google Drive แล้ว"</p>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กดยืนยัน</p>
            <p class="text-sm text-gray-500 mt-1">ระบบบันทึก <strong>เวลาเสร็จสิ้น</strong> และถ้างานทั้งหมดในคำขอเสร็จแล้ว → คำขอจะเปลี่ยนเป็น <span class="status-badge bg-green-100 text-green-800 text-xs">completed</span> และประชาชนได้รับแจ้ง LINE</p>
            <div class="warn-box mt-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>ข้อสำคัญ:</strong> ถ้า 1 คำขอมีหลายคนรับผิดชอบ (เช่น ช่างภาพ + MC) คำขอจะเสร็จสิ้นก็ต่อเมื่อ <strong>ทุกคนกดเสร็จสิ้น</strong> แล้วเท่านั้น
            </div>
        </div>
    </div>
</div>

<!-- ── SECTION 7: Status Flow Diagram ────────────────────────────── -->
<div id="status-flow" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-project-diagram text-indigo-500"></i> Flow สถานะงาน
    </h2>
    <div class="overflow-x-auto">
        <div class="flex items-center flex-nowrap gap-2 min-w-max py-3">
            <!-- Admin side -->
            <div class="flex flex-col items-center gap-1 px-3">
                <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user-shield text-orange-600"></i>
                </div>
                <span class="text-xs text-gray-500">Admin</span>
            </div>
            <div class="text-center">
                <div class="px-3 py-2 bg-orange-100 text-orange-800 rounded-lg text-sm font-semibold">
                    <i class="fas fa-inbox mr-1"></i>รับคำขอ
                </div>
                <p class="text-xs text-gray-400 mt-1">มอบหมายงาน</p>
            </div>
            <span class="flow-arrow">→</span>

            <!-- pending -->
            <div class="px-3 py-2 bg-yellow-100 text-yellow-800 rounded-lg text-center">
                <div class="text-sm font-bold">🕐 รอรับงาน</div>
                <div class="text-xs mt-0.5">pending</div>
                <div class="text-xs text-yellow-600 mt-1">แจ้ง LINE เจ้าหน้าที่</div>
            </div>
            <span class="flow-arrow">→</span>

            <!-- Staff action: accept -->
            <div class="text-center">
                <div class="px-2 py-1 bg-green-600 text-white rounded text-xs font-semibold">กดรับงาน</div>
                <div class="flex items-center gap-1 mt-1">
                    <div class="w-6 h-6 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-hard-hat text-green-600 text-xs"></i>
                    </div>
                    <span class="text-xs text-gray-400">เจ้าหน้าที่</span>
                </div>
            </div>
            <span class="flow-arrow">→</span>

            <!-- accepted -->
            <div class="px-3 py-2 bg-blue-100 text-blue-800 rounded-lg text-center">
                <div class="text-sm font-bold">✅ รับงานแล้ว</div>
                <div class="text-xs mt-0.5">accepted</div>
                <div class="text-xs text-blue-600 mt-1">คำขอ → in_progress</div>
            </div>
            <span class="flow-arrow">→</span>

            <!-- Staff action: start -->
            <div class="text-center">
                <div class="px-2 py-1 bg-blue-600 text-white rounded text-xs font-semibold">กดเริ่มงาน</div>
            </div>
            <span class="flow-arrow">→</span>

            <!-- in_progress -->
            <div class="px-3 py-2 bg-indigo-100 text-indigo-800 rounded-lg text-center">
                <div class="text-sm font-bold">🔧 กำลังทำ</div>
                <div class="text-xs mt-0.5">in_progress</div>
            </div>
            <span class="flow-arrow">→</span>

            <!-- Staff action: complete -->
            <div class="text-center">
                <div class="px-2 py-1 bg-purple-600 text-white rounded text-xs font-semibold">กดเสร็จสิ้น</div>
            </div>
            <span class="flow-arrow">→</span>

            <!-- completed -->
            <div class="px-3 py-2 bg-green-100 text-green-800 rounded-lg text-center">
                <div class="text-sm font-bold">🎉 เสร็จสิ้น</div>
                <div class="text-xs mt-0.5">completed</div>
                <div class="text-xs text-green-600 mt-1">แจ้ง LINE ประชาชน</div>
            </div>
        </div>
    </div>

    <div class="mt-4 info-box">
        <i class="fas fa-info-circle mr-1"></i>
        ทุกการเปลี่ยนสถานะ ระบบจะ <strong>แจ้ง LINE อัตโนมัติ</strong> ทั้งผู้ยื่นคำขอและเจ้าหน้าที่
    </div>
</div>

<!-- ── SECTION 8: Service-Specific Guide ─────────────────────────── -->
<div id="services" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-concierge-bell text-orange-500"></i> ข้อมูลเฉพาะแต่ละประเภทบริการ
    </h2>

    <div class="space-y-4">

        <!-- PHOTOGRAPHY -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-camera text-purple-600"></i> ช่างภาพ / Photography
                    <span class="service-pill">PHOTOGRAPHY</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <p class="font-medium text-gray-800 mb-2">ข้อมูลที่ต้องตรวจสอบในรายละเอียดงาน:</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <ul class="list-disc ml-4 space-y-1">
                        <li><strong>ชื่องาน/กิจกรรม</strong> ที่ต้องถ่าย</li>
                        <li><strong>วันเวลา</strong> ที่ต้องการช่างภาพ</li>
                        <li><strong>สถานที่</strong> จัดงาน</li>
                        <li><strong>จำนวนผู้เข้าร่วม</strong></li>
                    </ul>
                    <ul class="list-disc ml-4 space-y-1">
                        <li>ประเภทการถ่าย: ภาพนิ่ง / วีดีโอ / ทั้งคู่</li>
                        <li>ความต้องการพิเศษ (ถ้ามีใน notes)</li>
                        <li>เบอร์ติดต่อผู้จัดงาน</li>
                    </ul>
                </div>
                <div class="tip-box">
                    <i class="fas fa-lightbulb mr-1"></i>
                    <strong>Checklist:</strong> ตรวจสอบ due_date ก่อนรับงาน / แจ้งผู้ยื่นล่วงหน้าอย่างน้อย 1 วัน / อัปโหลดรูปผ่าน Google Drive แล้วแจ้ง link ใน completion_notes
                </div>
            </div>
        </div>

        <!-- MC -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-microphone text-pink-600"></i> พิธีกร / MC
                    <span class="service-pill">MC</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <ul class="list-disc ml-4 space-y-1">
                        <li><strong>ชื่องาน</strong> และ <strong>รูปแบบงาน</strong></li>
                        <li><strong>วันเวลา</strong> และ <strong>ระยะเวลา</strong> งาน</li>
                        <li><strong>สถานที่</strong> จัดงาน</li>
                    </ul>
                    <ul class="list-disc ml-4 space-y-1">
                        <li>กำหนดการ / ลำดับพิธี</li>
                        <li>ภาษาที่ใช้ (ไทย/อังกฤษ)</li>
                        <li>จำนวนผู้เข้าร่วม</li>
                    </ul>
                </div>
                <div class="tip-box">
                    <i class="fas fa-lightbulb mr-1"></i>
                    ขอ <strong>กำหนดการงาน</strong> จากผู้จัดล่วงหน้า / เตรียมบทเปิด-ปิด / ประสานกับผู้จัด 1 วันก่อนงาน
                </div>
            </div>
        </div>

        <!-- LED -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-tv text-yellow-600"></i> จอ LED
                    <span class="service-pill">LED</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <ul class="list-disc ml-4 space-y-1 mb-3">
                    <li><strong>สถานที่ติดตั้ง</strong> และขนาดพื้นที่</li>
                    <li>วันเวลาติดตั้ง / วันเวลาถอด</li>
                    <li>ไฟล์ content ที่ต้องแสดง (รับจากผู้ยื่น)</li>
                    <li>แหล่งไฟฟ้าและจุดเชื่อมต่อ</li>
                </ul>
                <div class="tip-box">
                    <i class="fas fa-lightbulb mr-1"></i>
                    ทดสอบสัญญาณก่อนงาน / บันทึก completion_notes ว่าถอด/เก็บอุปกรณ์เรียบร้อยแล้ว
                </div>
            </div>
        </div>

        <!-- IT_SUPPORT -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-desktop text-blue-600"></i> ซ่อม/ติดตั้งระบบ IT Support
                    <span class="service-pill">IT_SUPPORT</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <ul class="list-disc ml-4 space-y-1 mb-3">
                    <li>ประเภทปัญหา: คอมพิวเตอร์ / เครือข่าย / ซอฟต์แวร์ / อื่นๆ</li>
                    <li>หน่วยงานและเบอร์ติดต่อผู้แจ้ง</li>
                    <li>อาการของปัญหา (อ่านจาก description)</li>
                    <li>ประเภทเครื่อง: desktop / laptop / printer / อื่นๆ</li>
                </ul>
                <div class="tip-box">
                    <i class="fas fa-lightbulb mr-1"></i>
                    โทรนัดเวลาก่อน / บันทึกสิ่งที่แก้ไขใน completion_notes เพื่อใช้ reference ในอนาคต
                </div>
            </div>
        </div>

        <!-- EMAIL -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-envelope text-red-600"></i> อีเมลราชการ
                    <span class="service-pill">EMAIL</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <ul class="list-disc ml-4 space-y-1 mb-3">
                    <li>ประเภท: สร้างใหม่ / ลืมรหัสผ่าน / ปิดบัญชี</li>
                    <li>Email ที่ต้องการ (email_requested)</li>
                    <li>แผนกและตำแหน่งผู้ขอ</li>
                </ul>
                <div class="tip-box">
                    <i class="fas fa-lightbulb mr-1"></i>
                    บันทึก email ที่สร้างสำเร็จและ username ใน completion_notes / แจ้ง password เริ่มต้นกับผู้ขอโดยตรง (ไม่บันทึกใน system)
                </div>
            </div>
        </div>

        <!-- INTERNET -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-wifi text-green-600"></i> อินเทอร์เน็ต
                    <span class="service-pill">INTERNET</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <ul class="list-disc ml-4 space-y-1 mb-3">
                    <li>ประเภท: ติดตั้งใหม่ / แก้ปัญหา / เพิ่มความเร็ว</li>
                    <li>สถานที่/ห้องที่ต้องการ</li>
                    <li>จำนวนจุดเชื่อมต่อ</li>
                </ul>
            </div>
        </div>

        <!-- NAS -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-hdd text-gray-600"></i> พื้นที่จัดเก็บ NAS
                    <span class="service-pill">NAS</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <ul class="list-disc ml-4 space-y-1 mb-3">
                    <li>ประเภท: สร้าง folder ใหม่ / เพิ่มสิทธิ์ / เพิ่มพื้นที่</li>
                    <li>ชื่อ folder และสิทธิ์การเข้าถึง</li>
                    <li>ขนาดพื้นที่ที่ต้องการ</li>
                </ul>
                <div class="tip-box">
                    <i class="fas fa-lightbulb mr-1"></i>
                    บันทึก path และสิทธิ์ที่ตั้งใน completion_notes
                </div>
            </div>
        </div>

        <!-- WEB_DESIGN -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <button class="w-full flex items-center justify-between px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-left"
                    onclick="this.nextElementSibling.classList.toggle('hidden')">
                <span class="font-semibold text-gray-800 flex items-center gap-2">
                    <i class="fas fa-paint-brush text-indigo-600"></i> ออกแบบเว็บไซต์
                    <span class="service-pill">WEB_DESIGN</span>
                </span>
                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
            </button>
            <div class="px-4 py-4 text-sm text-gray-700">
                <ul class="list-disc ml-4 space-y-1 mb-3">
                    <li>ประเภทงาน: สร้างใหม่ / แก้ไข / อัปเดตเนื้อหา</li>
                    <li>URL เว็บไซต์เป้าหมาย</li>
                    <li>ไฟล์ต้นฉบับ/รายละเอียดที่ต้องการ</li>
                </ul>
            </div>
        </div>

    </div>
</div>

<!-- ── SECTION 9: FAQ / Tips ──────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-question-circle text-indigo-500"></i> คำถามที่พบบ่อย
    </h2>
    <div class="space-y-3">
        <details class="border border-gray-100 rounded-lg">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                ไม่ได้รับ LINE notification แต่มีงานอยู่ใน My Tasks — ทำอย่างไร?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                ตรวจสอบว่าบัญชีของคุณผูกกับ LINE หรือยัง — ติดต่อ Admin เพื่อตั้งค่า LINE User ID ในโปรไฟล์
            </div>
        </details>
        <details class="border border-gray-100 rounded-lg">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                รับงานแล้วแต่ทำไม่ได้ — ต้องทำอย่างไร?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                แจ้ง Admin / Manager เพื่อ <strong>เปลี่ยนผู้รับผิดชอบ</strong> (reassign) หรือ <strong>ยกเลิกงาน</strong> — เจ้าหน้าที่ไม่สามารถยกเลิกงานเองได้จากระบบ
            </div>
        </details>
        <details class="border border-gray-100 rounded-lg">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                กดเสร็จสิ้นแล้ว แต่คำขอยังไม่เปลี่ยนเป็น completed?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                มีเจ้าหน้าที่คนอื่นที่ยังไม่กดเสร็จสิ้น — คำขอจะ completed เมื่อ <strong>ทุกคน</strong>ในการมอบหมายกดเสร็จสิ้นแล้ว
            </div>
        </details>
        <details class="border border-gray-100 rounded-lg">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                จะดูข้อมูลติดต่อของผู้ยื่นได้ที่ไหน?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                หน้า <strong>รายละเอียดงาน</strong> (task_detail.php) มีส่วน "ข้อมูลผู้ยื่น" แสดงชื่อ เบอร์โทร และอีเมล
            </div>
        </details>
    </div>
</div>

<!-- ── SECTION: งานตามแผน ────────────────────────────────────────── -->
<div id="planned-work" class="bg-white rounded-xl shadow-sm border border-orange-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2 border-b border-orange-100 pb-3">
        <i class="fas fa-calendar-check text-orange-500"></i> งานตามแผน (งานปฏิทิน / Internal Jobs)
    </h2>
    <p class="text-sm text-gray-500 mb-5">งานที่ Manager/Admin สร้างและมอบหมายล่วงหน้าตามปฏิทินงาน เช่น ถ่ายภาพงาน พิธีกร LED — ต่างจากงานตามคำร้องตรงที่ <strong>ไม่มีผู้ยื่นคำขอจากภายนอก</strong></p>

    <!-- ภาพรวมความแตกต่าง -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <p class="font-semibold text-blue-800 mb-2"><i class="fas fa-list-ul mr-1"></i> งานตามคำร้อง</p>
            <ul class="text-sm text-blue-700 space-y-1 list-disc ml-4">
                <li>ประชาชนกรอกแบบฟอร์มยื่นคำขอ</li>
                <li>Admin มอบหมายให้เจ้าหน้าที่</li>
                <li>ปรากฏในแถบ "งานตามคำร้อง"</li>
                <li>รหัส: <span class="font-mono">REQ-XXXX</span></li>
            </ul>
        </div>
        <div class="bg-orange-50 rounded-lg p-4 border border-orange-100">
            <p class="font-semibold text-orange-800 mb-2"><i class="fas fa-calendar-check mr-1"></i> งานตามแผน</p>
            <ul class="text-sm text-orange-700 space-y-1 list-disc ml-4">
                <li>Manager/Admin สร้างและกำหนดวันล่วงหน้า</li>
                <li>มีวันเวลา สถานที่ ประเภทงานชัดเจน</li>
                <li>ปรากฏในแถบ "งานตามแผน" และ <strong>ปฏิทิน</strong></li>
                <li>รหัส: <span class="font-mono">JOB-2026-XXXX</span></li>
            </ul>
        </div>
    </div>

    <!-- วิธีดูงานตามแผน -->
    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
        <span class="w-6 h-6 rounded-full bg-orange-500 text-white text-xs flex items-center justify-center font-bold">1</span>
        วิธีดูงานตามแผนที่ได้รับมอบหมาย
    </h3>
    <div class="guide-steps mb-5">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">ไปที่หน้า "งานของฉัน"</p>
            <p class="text-sm text-gray-500 mt-1">คลิก <span class="font-mono bg-gray-100 px-1 rounded">งานของฉัน</span> จาก sidebar หรือ <a href="my_tasks.php" class="text-indigo-600 hover:underline">คลิกที่นี่</a></p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">คลิก tab <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-xs font-bold"><i class="fas fa-calendar-check text-[10px]"></i> งานตามแผน</span></p>
            <div class="step-img mt-2">
                <p class="text-sm text-gray-600">แต่ละการ์ดแสดง:</p>
                <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4 mt-1">
                    <li><strong>ชื่องาน</strong> และรหัส JOB</li>
                    <li><strong>วันที่กำหนด</strong> และเวลาเริ่ม–สิ้นสุด</li>
                    <li><strong>สถานที่</strong> ปฏิบัติงาน</li>
                    <li><strong>สถานะ</strong> ปัจจุบันของงาน</li>
                </ul>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">หรือดูในแถบ <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-bold"><i class="fas fa-calendar-alt text-[10px]"></i> ปฏิทิน</span></p>
            <p class="text-sm text-gray-500 mt-1">งานตามแผนจะแสดงเป็น <span class="inline-block px-1.5 py-0.5 rounded text-xs font-semibold bg-orange-200 text-orange-900">สีส้ม</span> บนปฏิทิน คลิกวันที่เพื่อดูรายละเอียด</p>
        </div>
    </div>

    <!-- วิธีอัปเดตสถานะงานตามแผน -->
    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
        <span class="w-6 h-6 rounded-full bg-orange-500 text-white text-xs flex items-center justify-center font-bold">2</span>
        การอัปเดตสถานะงานตามแผน
    </h3>

    <!-- Flow -->
    <div class="flex flex-wrap items-center gap-2 mb-4 bg-gray-50 rounded-lg p-3 text-sm font-medium">
        <span class="status-badge bg-amber-100 text-amber-800"><i class="fas fa-calendar-alt"></i> กำหนดการแล้ว</span>
        <i class="fas fa-arrow-right text-gray-400"></i>
        <span class="status-badge bg-purple-100 text-purple-800"><i class="fas fa-spinner"></i> กำลังดำเนินการ</span>
        <i class="fas fa-arrow-right text-gray-400"></i>
        <span class="status-badge bg-green-100 text-green-800"><i class="fas fa-check-double"></i> เสร็จสิ้น</span>
    </div>

    <div class="guide-steps mb-5">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">เมื่อถึงวันงาน — กดปุ่ม <span class="px-2 py-0.5 bg-purple-600 text-white text-xs rounded">▶ เริ่มงาน</span></p>
            <p class="text-sm text-gray-500 mt-1">กดเมื่อเริ่มออกปฏิบัติงานจริง ระบบบันทึกเวลาเริ่มงานอัตโนมัติ</p>
            <div class="tip-box mt-2">
                <i class="fas fa-lightbulb mr-1"></i>
                ยืนยันใน popup ก่อนกด — ระบบจะถามว่า "ต้องการเริ่มดำเนินการงาน JOB-XXXX ใช่หรือไม่?"
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">ปฏิบัติงานตามที่ได้รับมอบหมาย</p>
            <div class="step-img mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1"><i class="fas fa-camera mr-1 text-orange-400"></i> ช่างภาพ</p>
                        <ul class="text-gray-600 list-disc ml-4 space-y-0.5">
                            <li>ตรวจสอบสถานที่และเวลา</li>
                            <li>เตรียมอุปกรณ์กล้อง</li>
                            <li>ถ่ายภาพ/วีดีโองาน</li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1"><i class="fas fa-microphone mr-1 text-blue-400"></i> พิธีกร (MC)</p>
                        <ul class="text-gray-600 list-disc ml-4 space-y-0.5">
                            <li>ศึกษากำหนดการงาน</li>
                            <li>เตรียมบทพูด/คำกล่าว</li>
                            <li>ประสานงานผู้จัดงาน</li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1"><i class="fas fa-tv mr-1 text-indigo-400"></i> LED / อุปกรณ์</p>
                        <ul class="text-gray-600 list-disc ml-4 space-y-0.5">
                            <li>ตรวจสอบอุปกรณ์ก่อนงาน</li>
                            <li>ติดตั้ง ณ สถานที่จัดงาน</li>
                            <li>ดูแลระหว่างงาน</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">เสร็จงานแล้ว — กดปุ่ม <span class="px-2 py-0.5 bg-green-600 text-white text-xs rounded">✓ เสร็จสิ้น</span></p>
            <p class="text-sm text-gray-500 mt-1">ระบบบันทึกเวลาเสร็จงานและย้ายออกจากรายการงานที่ยังค้างอยู่</p>
            <div class="warn-box mt-2">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                <strong>หมายเหตุ:</strong> งานที่ "เสร็จสิ้น" จะหายไปจาก tab งานตามแผน (เพื่อความสะอาด) แต่ Manager ยังดูได้ในหน้าจัดการงาน
            </div>
        </div>
    </div>

    <!-- วิธีสร้างงานตามแผน (สำหรับ Manager) -->
    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
        <span class="w-6 h-6 rounded-full bg-orange-500 text-white text-xs flex items-center justify-center font-bold">3</span>
        วิธีสร้างงานตามแผน <span class="text-xs text-gray-400 font-normal">(สำหรับ Manager / Admin)</span>
    </h3>
    <div class="guide-steps mb-4">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">ไปที่หน้า <a href="create_job.php" class="text-orange-600 hover:underline">สร้างงานตามแผน</a></p>
            <p class="text-sm text-gray-500 mt-1">คลิก <span class="font-mono bg-gray-100 px-1 rounded">สร้างงาน</span> จาก sidebar หรือจากหน้าปฏิทินงาน</p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กรอกข้อมูลงาน</p>
            <div class="step-img mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm">
                    <div>
                        <p class="font-semibold text-gray-700 mb-1">ข้อมูลหลัก</p>
                        <ul class="text-gray-600 list-disc ml-4 space-y-1">
                            <li><strong>ชื่องาน</strong> — ระบุชัดเจน เช่น "ถ่ายภาพงานวันเด็ก 2569"</li>
                            <li><strong>ประเภทงาน</strong> — ช่างภาพ / พิธีกร / LED ฯลฯ</li>
                            <li><strong>ความเร่งด่วน</strong> — ต่ำ / ปกติ / กลาง / สูง / ด่วนมาก</li>
                            <li><strong>ผู้รับผิดชอบ</strong> — เลือกเจ้าหน้าที่ที่จะมอบหมาย</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold text-gray-700 mb-1">วันเวลาและสถานที่</p>
                        <ul class="text-gray-600 list-disc ml-4 space-y-1">
                            <li><strong>วันที่กำหนด</strong> — วันที่จะปฏิบัติงาน</li>
                            <li><strong>เวลาเริ่ม / สิ้นสุด</strong> — ระยะเวลางาน</li>
                            <li><strong>สถานที่</strong> — ที่อยู่หรือชื่อสถานที่</li>
                            <li><strong>หมายเหตุ</strong> — คำแนะนำพิเศษให้เจ้าหน้าที่</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กดปุ่ม <span class="px-2 py-0.5 bg-blue-600 text-white text-xs rounded">บันทึกงาน</span></p>
            <p class="text-sm text-gray-500 mt-1">ระบบสร้างรหัส <span class="font-mono bg-gray-100 px-1 rounded">JOB-2026-XXXX</span> อัตโนมัติ และงานจะปรากฏในปฏิทินของเจ้าหน้าที่ที่ได้รับมอบหมาย</p>
            <div class="info-box mt-2">
                <i class="fas fa-info-circle mr-1"></i>
                เจ้าหน้าที่จะเห็นงานใน tab <strong>"งานตามแผน"</strong> และบนปฏิทิน (สีส้ม) ทันทีหลังบันทึก
            </div>
        </div>
    </div>

    <!-- ประเภทงาน -->
    <h3 class="font-bold text-gray-800 mb-3 flex items-center gap-2">
        <span class="w-6 h-6 rounded-full bg-orange-500 text-white text-xs flex items-center justify-center font-bold">4</span>
        ประเภทงานตามแผนที่รองรับ
    </h3>
    <div class="flex flex-wrap gap-2 mb-2">
        <span class="service-pill"><i class="fas fa-camera"></i> ช่างภาพ (PHOTOGRAPHY)</span>
        <span class="service-pill"><i class="fas fa-microphone"></i> พิธีกร (MC)</span>
        <span class="service-pill"><i class="fas fa-tv"></i> LED Screen</span>
        <span class="service-pill"><i class="fas fa-desktop"></i> IT Support</span>
        <span class="service-pill"><i class="fas fa-star"></i> Event ทั่วไป</span>
        <span class="service-pill"><i class="fas fa-tasks"></i> งานประจำ (Routine)</span>
    </div>
    <p class="text-xs text-gray-400 mt-1">* ประเภทงานกำหนดโดย Admin — เจ้าหน้าที่ไม่ต้องเลือกเอง</p>
</div>

<!-- CTA -->
<div class="bg-indigo-600 rounded-xl p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-4">
    <div>
        <p class="font-bold text-lg">พร้อมเริ่มต้นแล้ว?</p>
        <p class="text-indigo-200 text-sm">ไปที่หน้างานของฉันเพื่อดูงานที่ได้รับมอบหมาย</p>
    </div>
    <a href="my_tasks.php" class="px-6 py-2.5 bg-white text-indigo-700 font-semibold rounded-lg hover:bg-indigo-50 transition whitespace-nowrap flex items-center gap-2">
        <i class="fas fa-tasks"></i> ไปหน้างานของฉัน
    </a>
</div>

<?php include 'admin-layout/footer.php'; ?>
