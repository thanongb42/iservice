<?php
/**
 * คู่มือการใช้งาน — งานตามแผนงาน (Internal Jobs)
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title   = 'คู่มือ — งานตามแผนงาน';
$current_page = 'staff_guide_planned';
$breadcrumb   = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'คู่มือการใช้งาน', 'url' => 'staff_guide.php'],
    ['label' => 'งานตามแผนงาน'],
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
    background: #ea580c;
    color: white;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.9rem; font-weight: 700;
    line-height: 1;
}
.guide-steps { counter-reset: step-counter; }
.step-img { background: #f8fafc; border: 1px solid #e5e7eb; border-radius: 0.75rem; padding: 1rem; margin-top: 0.75rem; }
.service-pill { display: inline-flex; align-items: center; gap: 0.4rem; padding: 0.25rem 0.75rem;
    background: #ffedd5; color: #9a3412; border-radius: 9999px; font-size: 0.78rem; font-weight: 600; }
.tip-box  { background: #fffbeb; border: 1px solid #fbbf24; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #92400e; }
.warn-box { background: #fef2f2; border: 1px solid #fca5a5; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #991b1b; }
.info-box { background: #eff6ff; border: 1px solid #93c5fd; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #1e40af; }
.orange-box { background: #fff7ed; border: 1px solid #fed7aa; border-radius: 0.5rem; padding: 0.75rem 1rem; font-size: 0.875rem; color: #9a3412; }
.status-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.25rem 0.75rem; border-radius:9999px; font-size:0.8rem; font-weight:600; }
</style>

<!-- Page Header -->
<div class="mb-6">
    <div class="flex items-center gap-2 text-sm text-gray-400 mb-2">
        <a href="staff_guide.php" class="hover:text-orange-600 transition"><i class="fas fa-book-open mr-1"></i>คู่มือการใช้งาน</a>
        <i class="fas fa-chevron-right text-xs"></i>
        <span class="text-orange-600 font-medium">งานตามแผนงาน</span>
    </div>
    <h1 class="text-2xl md:text-3xl font-bold text-gray-900 flex items-center gap-3">
        <i class="fas fa-calendar-check text-orange-500"></i>
        คู่มือ — งานตามแผนงาน
    </h1>
    <p class="text-gray-500 mt-1">งานที่ Manager/Admin สร้างและมอบหมายล่วงหน้าตามปฏิทิน เช่น ถ่ายภาพ พิธีกร LED Screen</p>
</div>

<!-- Quick Jump -->
<div class="bg-white rounded-xl shadow-sm border border-orange-100 p-4 mb-6">
    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">ไปยังหัวข้อ</p>
    <div class="flex flex-wrap gap-2">
        <a href="#what-is"      class="px-3 py-1.5 text-sm bg-orange-50 text-orange-700 rounded-lg hover:bg-orange-100 transition">งานตามแผนคืออะไร</a>
        <a href="#view-jobs"    class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">ดูงานที่ได้รับ</a>
        <a href="#update-status" class="px-3 py-1.5 text-sm bg-purple-50 text-purple-700 rounded-lg hover:bg-purple-100 transition">อัปเดตสถานะ</a>
        <a href="#create-job"   class="px-3 py-1.5 text-sm bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition">สร้างงาน (Manager)</a>
        <a href="#job-types"    class="px-3 py-1.5 text-sm bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition">ประเภทงาน</a>
        <a href="#faq"          class="px-3 py-1.5 text-sm bg-yellow-50 text-yellow-700 rounded-lg hover:bg-yellow-100 transition">คำถามที่พบบ่อย</a>
    </div>
</div>

<!-- ── SECTION 1: คืออะไร ── -->
<div id="what-is" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-info-circle text-orange-500"></i> งานตามแผนงานคืออะไร?
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
            <p class="font-semibold text-blue-800 mb-2"><i class="fas fa-list-ul mr-1"></i> งานตามคำร้อง</p>
            <ul class="text-sm text-blue-700 space-y-1 list-disc ml-4">
                <li>ประชาชนยื่นคำขอผ่านเว็บไซต์</li>
                <li>Admin มอบหมายให้เจ้าหน้าที่รับผิดชอบ</li>
                <li>รหัส <span class="font-mono bg-blue-100 px-1 rounded">REQ-XXXX</span></li>
                <li>ดูได้ที่แถบ "งานตามคำร้อง"</li>
            </ul>
        </div>
        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
            <p class="font-semibold text-orange-800 mb-2"><i class="fas fa-calendar-check mr-1"></i> งานตามแผนงาน <span class="text-xs font-normal">(หน้านี้)</span></p>
            <ul class="text-sm text-orange-700 space-y-1 list-disc ml-4">
                <li>Manager/Admin สร้างและกำหนดวันล่วงหน้า</li>
                <li>มีวันเวลา สถานที่ชัดเจน</li>
                <li>รหัส <span class="font-mono bg-orange-100 px-1 rounded">JOB-2026-XXXX</span></li>
                <li>ดูได้ที่แถบ "งานตามแผนงาน" <strong>และปฏิทิน</strong></li>
            </ul>
        </div>
    </div>
    <div class="orange-box">
        <i class="fas fa-calendar-alt mr-1"></i>
        งานตามแผนจะแสดง <strong>สีส้ม</strong> บนปฏิทิน (tab ปฏิทิน) ในหน้า "งานของฉัน" พร้อมชื่องานและเวลา
    </div>
</div>

<!-- ── SECTION 2: ดูงาน ── -->
<div id="view-jobs" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-eye text-orange-500"></i> วิธีดูงานตามแผนที่ได้รับมอบหมาย
    </h2>
    <div class="guide-steps">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">ไปที่หน้า <a href="my_tasks.php" class="text-orange-600 hover:underline">งานของฉัน</a></p>
            <p class="text-sm text-gray-500 mt-1">คลิก <span class="font-mono bg-gray-100 px-1 rounded">งานของฉัน</span> จาก sidebar ด้านซ้าย</p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">เลือก tab <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-orange-100 text-orange-800 text-xs font-bold"><i class="fas fa-calendar-check text-[10px]"></i> งานตามแผนงาน</span></p>
            <div class="step-img mt-2">
                <p class="text-sm text-gray-600 font-medium mb-2">แต่ละการ์ดแสดงข้อมูล:</p>
                <div class="grid grid-cols-2 gap-3 text-sm text-gray-600">
                    <div class="flex items-start gap-2"><i class="fas fa-heading text-orange-400 mt-0.5"></i><div><strong>ชื่องาน</strong><br><span class="text-gray-400 text-xs">และรหัส JOB-XXXX</span></div></div>
                    <div class="flex items-start gap-2"><i class="fas fa-calendar text-orange-400 mt-0.5"></i><div><strong>วันที่กำหนด</strong><br><span class="text-gray-400 text-xs">เวลาเริ่ม – สิ้นสุด</span></div></div>
                    <div class="flex items-start gap-2"><i class="fas fa-map-marker-alt text-orange-400 mt-0.5"></i><div><strong>สถานที่</strong><br><span class="text-gray-400 text-xs">ปฏิบัติงาน</span></div></div>
                    <div class="flex items-start gap-2"><i class="fas fa-tag text-orange-400 mt-0.5"></i><div><strong>สถานะ</strong><br><span class="text-gray-400 text-xs">ปัจจุบันของงาน</span></div></div>
                </div>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">หรือดูจาก tab <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-blue-100 text-blue-800 text-xs font-bold"><i class="fas fa-calendar-alt text-[10px]"></i> ปฏิทิน</span></p>
            <div class="step-img mt-2">
                <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4">
                    <li>งานตามแผนจะแสดงเป็น <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-orange-200 text-orange-900">JOB-XXXX สีส้ม</span> บนวันที่นั้น</li>
                    <li>คลิกที่วันเพื่อดูรายละเอียด — ชื่องาน เวลา สถานที่ สถานะ</li>
                    <li>งานตามคำร้อง (service request) จะแสดงเป็นสีอื่น (ตามสถานะ)</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- ── SECTION 3: อัปเดตสถานะ ── -->
<div id="update-status" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-sync-alt text-orange-500"></i> การอัปเดตสถานะงานตามแผน
    </h2>

    <!-- Status Flow -->
    <div class="flex flex-wrap items-center gap-2 mb-5 bg-orange-50 rounded-xl p-4">
        <div class="text-center">
            <span class="status-badge bg-amber-100 text-amber-800"><i class="fas fa-calendar-alt"></i> กำหนดการแล้ว</span>
            <p class="text-xs text-gray-400 mt-1">ก่อนวันงาน</p>
        </div>
        <i class="fas fa-long-arrow-alt-right text-orange-300 text-xl"></i>
        <div class="text-center">
            <span class="status-badge bg-purple-100 text-purple-800"><i class="fas fa-spinner"></i> กำลังดำเนินการ</span>
            <p class="text-xs text-gray-400 mt-1">วันงาน — กดเริ่มงาน</p>
        </div>
        <i class="fas fa-long-arrow-alt-right text-orange-300 text-xl"></i>
        <div class="text-center">
            <span class="status-badge bg-green-100 text-green-800"><i class="fas fa-check-double"></i> เสร็จสิ้น</span>
            <p class="text-xs text-gray-400 mt-1">งานเสร็จแล้ว</p>
        </div>
    </div>

    <div class="guide-steps">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">เมื่อถึงวันงาน — กดปุ่ม <span class="inline-flex items-center gap-1 px-3 py-1 bg-purple-600 text-white text-xs rounded-lg font-bold"><i class="fas fa-play"></i> เริ่มงาน</span></p>
            <div class="step-img mt-2">
                <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4">
                    <li>ระบบจะแสดง popup ยืนยัน — กด <strong>ยืนยัน</strong> เพื่อดำเนินการ</li>
                    <li>สถานะเปลี่ยนเป็น <span class="status-badge bg-purple-100 text-purple-800 text-xs"><i class="fas fa-spinner"></i> กำลังดำเนินการ</span></li>
                    <li>ระบบบันทึก <strong>เวลาเริ่มงาน</strong> อัตโนมัติ</li>
                </ul>
            </div>
            <div class="tip-box mt-3">
                <i class="fas fa-lightbulb mr-1"></i>
                กดปุ่ม "เริ่มงาน" เมื่อออกปฏิบัติงานจริง ไม่ใช่ก่อนวันงาน — เพื่อให้บันทึกเวลาถูกต้อง
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">ปฏิบัติงานตามที่ได้รับมอบหมาย</p>
            <div class="step-img mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                    <div class="bg-white rounded-lg p-3 border border-orange-100">
                        <p class="text-xs font-bold text-orange-700 uppercase mb-2"><i class="fas fa-camera mr-1"></i> ช่างภาพ</p>
                        <ul class="text-gray-600 list-disc ml-3 space-y-0.5 text-xs">
                            <li>ตรวจสอบสถานที่และเวลา</li>
                            <li>เตรียมอุปกรณ์กล้อง</li>
                            <li>ถ่ายภาพ/วีดีโองาน</li>
                            <li>ส่งไฟล์ภาพตามช่องทางที่กำหนด</li>
                        </ul>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-orange-100">
                        <p class="text-xs font-bold text-orange-700 uppercase mb-2"><i class="fas fa-microphone mr-1"></i> พิธีกร (MC)</p>
                        <ul class="text-gray-600 list-disc ml-3 space-y-0.5 text-xs">
                            <li>ศึกษากำหนดการงาน</li>
                            <li>เตรียมบทพูด/คำกล่าว</li>
                            <li>ประสานงานผู้จัดงาน</li>
                            <li>ดำเนินรายการงาน</li>
                        </ul>
                    </div>
                    <div class="bg-white rounded-lg p-3 border border-orange-100">
                        <p class="text-xs font-bold text-orange-700 uppercase mb-2"><i class="fas fa-tv mr-1"></i> LED / อุปกรณ์</p>
                        <ul class="text-gray-600 list-disc ml-3 space-y-0.5 text-xs">
                            <li>ตรวจสอบอุปกรณ์ก่อนงาน</li>
                            <li>ติดตั้ง ณ สถานที่จัดงาน</li>
                            <li>ทดสอบการทำงาน</li>
                            <li>ดูแลระหว่างงาน</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">งานเสร็จแล้ว — กดปุ่ม <span class="inline-flex items-center gap-1 px-3 py-1 bg-green-600 text-white text-xs rounded-lg font-bold"><i class="fas fa-check"></i> เสร็จสิ้น</span></p>
            <div class="step-img mt-2">
                <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4">
                    <li>ยืนยันใน popup อีกครั้ง</li>
                    <li>สถานะเปลี่ยนเป็น <span class="status-badge bg-green-100 text-green-800 text-xs"><i class="fas fa-check-double"></i> เสร็จสิ้น</span></li>
                    <li>ระบบบันทึกเวลาเสร็จงานอัตโนมัติ</li>
                </ul>
            </div>
            <div class="warn-box mt-3">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                งานที่ "เสร็จสิ้น" จะหายไปจาก tab งานตามแผนงาน (สถานะ completed/cancelled ถูกซ่อน) แต่ Manager ยังดูประวัติได้ในหน้าจัดการงาน
            </div>
        </div>
    </div>
</div>

<!-- ── SECTION 4: สร้างงาน (Manager) ── -->
<div id="create-job" class="bg-white rounded-xl shadow-sm border border-blue-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-1 flex items-center gap-2 border-b border-blue-100 pb-3">
        <i class="fas fa-plus-circle text-blue-500"></i> วิธีสร้างงานตามแผน
        <span class="text-xs font-normal text-gray-400 bg-blue-50 px-2 py-0.5 rounded-full">สำหรับ Manager / Admin</span>
    </h2>
    <p class="text-sm text-gray-500 mb-5">เจ้าหน้าที่ทั่วไปไม่สามารถสร้างงานได้ — ต้องเป็น Manager หรือ Admin</p>

    <div class="guide-steps">
        <div class="guide-step">
            <p class="font-semibold text-gray-800">ไปที่หน้า <a href="create_job.php" class="text-blue-600 hover:underline">ปฏิทินงาน / สร้างงาน</a></p>
            <p class="text-sm text-gray-500 mt-1">คลิก <span class="font-mono bg-gray-100 px-1 rounded">ปฏิทินงาน</span> จาก sidebar หรือคลิกลิงก์ด้านบน</p>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กรอกข้อมูลงานในฟอร์ม</p>
            <div class="step-img mt-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                    <div>
                        <p class="font-bold text-gray-700 mb-2 flex items-center gap-1"><i class="fas fa-file-alt text-blue-400"></i> ข้อมูลหลัก</p>
                        <table class="w-full text-xs">
                            <tr class="border-b"><td class="py-1.5 font-semibold text-gray-600 w-36">ชื่องาน</td><td class="text-gray-500">ระบุให้ชัดเจน เช่น "ถ่ายภาพงานวันเด็ก 2569"</td></tr>
                            <tr class="border-b"><td class="py-1.5 font-semibold text-gray-600">ประเภทงาน</td><td class="text-gray-500">ช่างภาพ / พิธีกร / LED ฯลฯ</td></tr>
                            <tr class="border-b"><td class="py-1.5 font-semibold text-gray-600">ความเร่งด่วน</td><td class="text-gray-500">ต่ำ / ปกติ / กลาง / สูง / ด่วนมาก</td></tr>
                            <tr><td class="py-1.5 font-semibold text-gray-600">ผู้รับผิดชอบ</td><td class="text-gray-500">เลือกเจ้าหน้าที่จากรายการ</td></tr>
                        </table>
                    </div>
                    <div>
                        <p class="font-bold text-gray-700 mb-2 flex items-center gap-1"><i class="fas fa-clock text-blue-400"></i> วันเวลาและสถานที่</p>
                        <table class="w-full text-xs">
                            <tr class="border-b"><td class="py-1.5 font-semibold text-gray-600 w-36">วันที่กำหนด</td><td class="text-gray-500">วันที่จะปฏิบัติงาน</td></tr>
                            <tr class="border-b"><td class="py-1.5 font-semibold text-gray-600">เวลาเริ่ม</td><td class="text-gray-500">เวลาเริ่มงาน (HH:MM)</td></tr>
                            <tr class="border-b"><td class="py-1.5 font-semibold text-gray-600">เวลาสิ้นสุด</td><td class="text-gray-500">เวลาเลิกงาน (HH:MM)</td></tr>
                            <tr class="border-b"><td class="py-1.5 font-semibold text-gray-600">สถานที่</td><td class="text-gray-500">ชื่อหรือที่อยู่สถานที่จัดงาน</td></tr>
                            <tr><td class="py-1.5 font-semibold text-gray-600">หมายเหตุ</td><td class="text-gray-500">คำแนะนำพิเศษให้เจ้าหน้าที่</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="guide-step">
            <p class="font-semibold text-gray-800">กดปุ่ม <span class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 text-white text-xs rounded-lg font-bold"><i class="fas fa-save"></i> บันทึกงาน</span></p>
            <div class="step-img mt-2">
                <ul class="text-sm text-gray-600 space-y-1 list-disc ml-4">
                    <li>ระบบสร้างรหัส <span class="font-mono bg-gray-100 px-1 rounded">JOB-2026-XXXX</span> อัตโนมัติ</li>
                    <li>งานปรากฏในปฏิทินของเจ้าหน้าที่ที่ได้รับมอบหมายทันที</li>
                    <li>เจ้าหน้าที่เห็นงานใน tab "งานตามแผนงาน" (สีส้ม)</li>
                </ul>
            </div>
            <div class="info-box mt-3">
                <i class="fas fa-info-circle mr-1"></i>
                เจ้าหน้าที่ที่ได้รับมอบหมายสามารถเห็นงานใน <strong>tab ปฏิทิน</strong> (สีส้ม) และ <strong>tab งานตามแผนงาน</strong> ทันทีหลังบันทึก
            </div>
        </div>
    </div>

    <a href="create_job.php" class="inline-flex items-center gap-2 mt-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-semibold text-sm hover:bg-blue-700 transition">
        <i class="fas fa-plus-circle"></i> ไปหน้าสร้างงาน
    </a>
</div>

<!-- ── SECTION 5: ประเภทงาน ── -->
<div id="job-types" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-tags text-orange-500"></i> ประเภทงานตามแผนที่รองรับ
    </h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php
        $job_types = [
            ['icon'=>'fa-camera',         'color'=>'orange', 'name'=>'ช่างภาพ (PHOTOGRAPHY)', 'desc'=>'ถ่ายภาพและวีดีโองานกิจกรรม'],
            ['icon'=>'fa-microphone',     'color'=>'blue',   'name'=>'พิธีกร (MC)',            'desc'=>'ดำเนินรายการและพิธีกรงาน'],
            ['icon'=>'fa-tv',             'color'=>'indigo', 'name'=>'LED Screen',              'desc'=>'ติดตั้งและดูแล LED Screen'],
            ['icon'=>'fa-desktop',        'color'=>'teal',   'name'=>'IT Support',              'desc'=>'สนับสนุนด้านเทคนิค/คอมพิวเตอร์'],
            ['icon'=>'fa-star',           'color'=>'yellow', 'name'=>'Event ทั่วไป',            'desc'=>'งานกิจกรรมพิเศษทั่วไป'],
            ['icon'=>'fa-tasks',          'color'=>'gray',   'name'=>'งานประจำ (Routine)',       'desc'=>'งานที่ทำเป็นประจำสม่ำเสมอ'],
        ];
        $colors = [
            'orange'=>['bg'=>'bg-orange-50','text'=>'text-orange-700','icon'=>'text-orange-500','border'=>'border-orange-100'],
            'blue'  =>['bg'=>'bg-blue-50',  'text'=>'text-blue-700',  'icon'=>'text-blue-500',  'border'=>'border-blue-100'],
            'indigo'=>['bg'=>'bg-indigo-50','text'=>'text-indigo-700','icon'=>'text-indigo-500','border'=>'border-indigo-100'],
            'teal'  =>['bg'=>'bg-teal-50',  'text'=>'text-teal-700',  'icon'=>'text-teal-500',  'border'=>'border-teal-100'],
            'yellow'=>['bg'=>'bg-yellow-50','text'=>'text-yellow-700','icon'=>'text-yellow-500','border'=>'border-yellow-100'],
            'gray'  =>['bg'=>'bg-gray-50',  'text'=>'text-gray-700',  'icon'=>'text-gray-500',  'border'=>'border-gray-100'],
        ];
        foreach ($job_types as $jt):
            $c = $colors[$jt['color']];
        ?>
        <div class="flex items-start gap-3 p-3 rounded-xl border <?= $c['border'] ?> <?= $c['bg'] ?>">
            <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0 bg-white shadow-sm">
                <i class="fas <?= $jt['icon'] ?> <?= $c['icon'] ?>"></i>
            </div>
            <div>
                <p class="font-semibold <?= $c['text'] ?> text-sm"><?= $jt['name'] ?></p>
                <p class="text-xs text-gray-500 mt-0.5"><?= $jt['desc'] ?></p>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <p class="text-xs text-gray-400 mt-4"><i class="fas fa-info-circle mr-1"></i> ประเภทงานกำหนดโดย Manager/Admin เมื่อสร้างงาน — เจ้าหน้าที่ไม่ต้องเลือกเอง</p>
</div>

<!-- ── SECTION 6: FAQ ── -->
<div id="faq" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-5">
    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center gap-2 border-b pb-3">
        <i class="fas fa-question-circle text-orange-500"></i> คำถามที่พบบ่อย
    </h2>
    <div class="space-y-2">
        <details class="border border-gray-100 rounded-lg overflow-hidden">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50 flex items-center justify-between">
                ทำไมกดปุ่ม "เริ่มงาน" แล้วไม่เจอ?
                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                งานที่มีสถานะ "กำหนดการแล้ว" เท่านั้นที่มีปุ่มเริ่มงาน — ถ้างานถูกยกเลิก (cancelled) หรือเสร็จสิ้น (completed) แล้ว จะไม่มีปุ่ม
            </div>
        </details>
        <details class="border border-gray-100 rounded-lg overflow-hidden">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                งานหายไปจาก tab งานตามแผนงาน ทำไม?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                ระบบซ่อนงานที่มีสถานะ <strong>เสร็จสิ้น</strong> และ <strong>ยกเลิก</strong> ออกจากรายการ เพื่อให้เห็นแค่งานที่ยังค้างอยู่ — Manager สามารถดูประวัติทั้งหมดได้ในหน้าปฏิทินงาน
            </div>
        </details>
        <details class="border border-gray-100 rounded-lg overflow-hidden">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                ปุ่ม "เสร็จสิ้น" กดแล้วย้อนกลับได้ไหม?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                ไม่ได้ — เจ้าหน้าที่ไม่สามารถย้อนสถานะได้ด้วยตัวเอง ต้องให้ Manager หรือ Admin แก้ไขในหน้าจัดการงาน
            </div>
        </details>
        <details class="border border-gray-100 rounded-lg overflow-hidden">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                เจ้าหน้าที่สร้างงานตามแผนเองได้ไหม?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                ไม่ได้ — เฉพาะ Manager และ Admin เท่านั้นที่สร้างและมอบหมายงานตามแผนได้ เจ้าหน้าที่รับและอัปเดตสถานะได้อย่างเดียว
            </div>
        </details>
        <details class="border border-gray-100 rounded-lg overflow-hidden">
            <summary class="px-4 py-3 cursor-pointer font-medium text-gray-800 hover:bg-gray-50">
                สีส้มบนปฏิทินหมายถึงอะไร?
            </summary>
            <div class="px-4 pb-4 text-sm text-gray-600">
                <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-orange-200 text-orange-900 mr-1">สีส้ม</span> = งานตามแผนงาน (internal job) <br>
                สีอื่น (เหลือง/น้ำเงิน/ม่วง/เขียว) = งานตามคำร้อง แบ่งตามสถานะ (รอรับงาน/รับแล้ว/ดำเนินการ/เสร็จ)
            </div>
        </details>
    </div>
</div>

<!-- CTA -->
<div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white flex flex-col sm:flex-row items-center justify-between gap-4">
    <div>
        <p class="font-bold text-lg">ไปดูงานที่ได้รับมอบหมาย</p>
        <p class="text-orange-100 text-sm">tab งานตามแผนงาน และ ปฏิทิน ในหน้างานของฉัน</p>
    </div>
    <a href="my_tasks.php" class="px-6 py-2.5 bg-white text-orange-700 font-semibold rounded-lg hover:bg-orange-50 transition whitespace-nowrap flex items-center gap-2">
        <i class="fas fa-tasks"></i> ไปหน้างานของฉัน
    </a>
</div>

<?php include 'admin-layout/footer.php'; ?>
