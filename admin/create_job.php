<?php
/**
 * Create Job — ปฏิทินงาน / สร้างงานภายใน (สำหรับ manager)
 */
session_start();
require_once '../config/database.php';

require_manager_or_admin();

// Build $user array for topbar (same pattern as other admin pages)
$user = [
    'username'   => $_SESSION['username']   ?? 'User',
    'email'      => $_SESSION['email']      ?? '',
    'full_name'  => $_SESSION['full_name']  ?? $_SESSION['username'] ?? 'User',
    'first_name' => $_SESSION['first_name'] ?? 'User',
];

// Fetch departments
$depts_q = $conn->query("SELECT department_id, department_name FROM departments WHERE status='active' ORDER BY department_name");
$depts   = $depts_q ? $depts_q->fetch_all(MYSQLI_ASSOC) : [];

// Fetch staff (line_user_id excluded — may not exist on production yet; fetched per-user via API)
$staff_q = $conn->query("
    SELECT u.user_id,
           CONCAT(IFNULL(p.prefix_name,''), u.first_name, ' ', u.last_name) AS full_name,
           u.position, d.department_name, u.profile_image, u.line_user_id
    FROM users u
    LEFT JOIN prefixes    p ON u.prefix_id     = p.prefix_id
    LEFT JOIN departments d ON u.department_id  = d.department_id
    WHERE u.role IN ('admin','staff') AND u.status = 'active'
    ORDER BY u.first_name
");
$staff = $staff_q ? $staff_q->fetch_all(MYSQLI_ASSOC) : [];

// Stats — use $job_stats (not $st — topbar.php reuses $st for request status string)
$today = date('Y-m-d');
$job_stats = ['total' => 0, 'today' => 0, 'week' => 0, 'done' => 0];
$tbl_check = $conn->query("SHOW TABLES LIKE 'internal_jobs'");
if ($tbl_check && $tbl_check->num_rows > 0) {
    $r = $conn->query("SELECT COUNT(*) c FROM internal_jobs");
    $job_stats['total'] = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $conn->query("SELECT COUNT(*) c FROM internal_jobs WHERE scheduled_date = '$today'");
    $job_stats['today'] = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $conn->query("SELECT COUNT(*) c FROM internal_jobs WHERE scheduled_date BETWEEN '$today' AND DATE_ADD('$today',INTERVAL 7 DAY)");
    $job_stats['week'] = $r ? (int)$r->fetch_assoc()['c'] : 0;
    $r = $conn->query("SELECT COUNT(*) c FROM internal_jobs WHERE status='completed'");
    $job_stats['done'] = $r ? (int)$r->fetch_assoc()['c'] : 0;
}

$page_title   = 'ปฏิทินงาน';
$current_page = 'create_job';
$breadcrumb   = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'ปฏิทินงาน']
];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* ── Flatpickr Thai overrides ── */
    .flatpickr-calendar { font-family: 'Sarabun', sans-serif; }
    .flatpickr-input[readonly] { background: white; cursor: pointer; }
    /* ── Stat Cards (same as user-manager) ── */
    .stat-card {
        background: white;
        border-radius: .75rem;
        border: 1px solid #e5e7eb;
        padding: 1.25rem;
        transition: box-shadow .15s ease;
    }
    .stat-card:hover { box-shadow: 0 4px 6px -1px rgba(0,0,0,.1); }
    .stat-icon {
        width: 2.5rem; height: 2.5rem;
        border-radius: .5rem;
        display: flex; align-items: center; justify-content: center;
    }

    /* ── Buttons (same as user-manager) ── */
    .btn {
        padding: .625rem 1.25rem;
        border: none; border-radius: .5rem;
        font-size: .875rem; font-weight: 500;
        cursor: pointer; transition: all .15s ease;
        display: inline-flex; align-items: center; justify-content: center; gap: .5rem;
    }
    .btn-primary  { background-color: #009933; color: white; }
    .btn-primary:hover  { background-color: #007a29; }
    .btn-secondary { background-color: #f3f4f6; color: #374151; }
    .btn-secondary:hover { background-color: #e5e7eb; }
    .btn-sm { padding: .375rem .75rem; font-size: .8rem; }
    .btn-teal { background-color: #0f766e; color: white; }
    .btn-teal:hover { background-color: #115e59; }

    /* ── Modal (same pattern as user-manager) ── */
    .modal {
        display: none; position: fixed; z-index: 1000;
        inset: 0; background-color: rgba(0,0,0,0);
        transition: background-color .2s ease;
        padding: 1rem;
    }
    .modal.active {
        display: flex; align-items: center; justify-content: center;
        background-color: rgba(0,0,0,.45);
    }
    .modal-content {
        background: white; border-radius: .75rem;
        box-shadow: 0 25px 50px -12px rgba(0,0,0,.25);
        width: 100%; max-width: 640px;
        max-height: 90vh; overflow-y: auto;
        padding: 1.5rem;
        animation: modalSlide .2s ease;
    }
    .modal-content.modal-sm { max-width: 480px; }
    @keyframes modalSlide {
        from { opacity:0; transform:translateY(-16px); }
        to   { opacity:1; transform:translateY(0); }
    }

    /* ── Form Groups (same as user-manager) ── */
    .form-group { margin-bottom: 0; }
    .form-group label {
        display: block; margin-bottom: .375rem;
        font-weight: 500; color: #374151; font-size: .875rem;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%; padding: .625rem .875rem;
        border: 1px solid #e5e7eb; border-radius: .5rem;
        font-size: .875rem; transition: all .15s ease; background: white;
    }
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none; border-color: #009933;
        box-shadow: 0 0 0 3px rgba(0,153,51,.1);
    }
    .form-group input::placeholder,
    .form-group textarea::placeholder { color: #d1d5db; }

    /* ── Calendar ── */
    .cal-grid { display: grid; grid-template-columns: repeat(7,1fr); }
    .cal-day-hdr {
        padding: .4rem 0; text-align: center;
        font-size: .7rem; font-weight: 700; color: #6b7280;
        text-transform: uppercase; letter-spacing: .05em;
    }
    .cal-cell {
        min-height: 80px; border: 1px solid #f3f4f6;
        padding: 4px; cursor: pointer;
        transition: background .15s; position: relative; overflow: hidden;
    }
    .cal-cell:hover { background: #f0fdf4; }
    .cal-cell.today  { background: #f0fdf9; border-color: #0d9488; }
    .cal-cell.selected { background: #ccfbf1; border-color: #0f766e; }
    .cal-cell.other-month { background: #fafafa; }
    .cal-cell.other-month .cal-date { color: #d1d5db; }
    .cal-date {
        font-size: .78rem; font-weight: 600; color: #374151;
        width: 22px; height: 22px;
        display: flex; align-items: center; justify-content: center;
        border-radius: 50%; margin-bottom: 2px;
    }
    .cal-cell.today    .cal-date { background: #0f766e; color: white; }
    .cal-cell.selected .cal-date { background: #0d9488; color: white; }
    .cal-dot {
        font-size: .6rem; padding: 1px 4px;
        border-radius: 3px; margin-bottom: 1px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        cursor: pointer; display: block;
    }

    /* Priority colours */
    .p-low     { background:#dbeafe; color:#1d4ed8; }
    .p-normal  { background:#d1fae5; color:#065f46; }
    .p-high    { background:#fef9c3; color:#854d0e; }
    .p-urgent  { background:#fee2e2; color:#991b1b; }

    /* Status colours */
    .s-scheduled   { background:#dbeafe; color:#1e40af; }
    .s-in_progress { background:#fef9c3; color:#92400e; }
    .s-completed   { background:#dcfce7; color:#14532d; }
    .s-cancelled   { background:#f3f4f6; color:#6b7280; }

    /* Job card (day list) */
    .job-card {
        border-left: 4px solid; padding: .625rem .875rem;
        border-radius: 0 .5rem .5rem 0; background: white;
        border-top: 1px solid #f3f4f6; border-right: 1px solid #f3f4f6;
        border-bottom: 1px solid #f3f4f6;
        transition: box-shadow .15s; cursor: pointer; margin-bottom: .375rem;
    }
    .job-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,.08); }
    .job-card.p-low    { border-left-color: #3b82f6; }
    .job-card.p-normal { border-left-color: #10b981; }
    .job-card.p-high   { border-left-color: #f59e0b; }
    .job-card.p-urgent { border-left-color: #ef4444; }

    /* Responsive calendar */
    @media(max-width:639px) {
        .cal-cell { min-height: 46px; padding: 2px; }
        .cal-date { font-size: .65rem; width: 17px; height: 17px; margin-bottom: 1px; }
        .cal-dot-text { display: none; }
        .cal-dot { padding: 1px 2px; font-size: .55rem; }
        .cal-day-hdr { font-size: .6rem; padding: .25rem 0; }
    }
    @media(min-width:640px) and (max-width:1023px) {
        .cal-cell { min-height: 70px; }
        .cal-dot-text { display: none; }
    }
    @media(max-width:639px) {
        .modal-content { border-radius: .75rem; max-height: 92vh; padding: 1rem; }
    }
</style>

<div>
    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">ปฏิทินงาน</h1>
        <p class="text-gray-500 text-sm mt-1">สร้างและจัดการงานประจำ/งานรูทีนของทีม</p>
    </div>

    <!-- Stat Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <?php foreach ([
            ['งานทั้งหมด', $job_stats['total'], 'fa-calendar-alt',  'text-blue-500',   'bg-blue-50'],
            ['วันนี้',      $job_stats['today'], 'fa-calendar-day',  'text-teal-600',   'bg-teal-50'],
            ['สัปดาห์นี้', $job_stats['week'],  'fa-calendar-week', 'text-amber-600',  'bg-amber-50'],
            ['เสร็จสิ้น',  $job_stats['done'],  'fa-check-circle',  'text-green-600',  'bg-green-50'],
        ] as [$lbl,$val,$ico,$tc,$bg]): ?>
        <div class="stat-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide"><?= $lbl ?></p>
                    <p class="text-2xl font-semibold text-gray-800 mt-1"><?= $val ?></p>
                </div>
                <div class="stat-icon <?= $bg ?>">
                    <i class="fas <?= $ico ?> <?= $tc ?>"></i>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Main Grid: Calendar + Side Panel -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-5">

        <!-- ── Calendar Card (3/5) ── -->
        <div class="md:col-span-3 bg-white rounded-xl border border-gray-200 overflow-hidden">

            <!-- Toolbar -->
            <div class="p-4 border-b border-gray-100">
                <div class="flex flex-wrap items-center gap-3">
                    <!-- Month nav -->
                    <div class="flex items-center gap-1">
                        <button onclick="changeMonth(-1)" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition text-gray-600">
                            <i class="fas fa-chevron-left text-xs"></i>
                        </button>
                        <h2 id="calTitle" class="text-base font-bold text-gray-800 min-w-[140px] text-center"></h2>
                        <button onclick="changeMonth(1)" class="w-8 h-8 flex items-center justify-center rounded-lg border border-gray-200 hover:bg-gray-50 transition text-gray-600">
                            <i class="fas fa-chevron-right text-xs"></i>
                        </button>
                    </div>
                    <button onclick="goToday()" class="btn btn-secondary btn-sm">
                        <i class="fas fa-dot-circle text-xs"></i> วันนี้
                    </button>
                    <div class="ml-auto">
                        <button onclick="openCreateModal()" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i>
                            <span class="hidden sm:inline">สร้างงานใหม่</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Day headers -->
            <div class="cal-grid px-1 pt-2">
                <?php foreach(['อา','จ','อ','พ','พฤ','ศ','ส'] as $d): ?>
                <div class="cal-day-hdr"><?= $d ?></div>
                <?php endforeach; ?>
            </div>

            <!-- Calendar body -->
            <div id="calBody" class="cal-grid px-1 pb-2"></div>

            <!-- Legend -->
            <div class="px-4 pb-3 pt-2 border-t border-gray-50 flex flex-wrap gap-2 items-center">
                <span class="text-xs text-gray-400">ลำดับ:</span>
                <span class="text-xs px-2 py-0.5 rounded p-low">ต่ำ</span>
                <span class="text-xs px-2 py-0.5 rounded p-normal">ปกติ</span>
                <span class="text-xs px-2 py-0.5 rounded p-high">สูง</span>
                <span class="text-xs px-2 py-0.5 rounded p-urgent">เร่งด่วน</span>
            </div>
        </div>

        <!-- ── Right Panel (2/5) ── -->
        <div class="md:col-span-2 flex flex-col gap-4">

            <!-- Day Jobs -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                    <h3 id="listTitle" class="text-sm font-bold text-gray-800">งานวันนี้</h3>
                    <button onclick="openCreateModal(calState.selectedDate)" class="btn btn-teal btn-sm">
                        <i class="fas fa-plus text-xs"></i> เพิ่มงาน
                    </button>
                </div>
                <div id="dayJobList" class="p-3 min-h-[160px]">
                    <p class="text-center text-gray-400 text-sm pt-8">กำลังโหลด...</p>
                </div>
            </div>

            <!-- Upcoming -->
            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="p-4 border-b border-gray-100">
                    <h3 class="text-sm font-bold text-gray-800">
                        กำลังจะมาถึง
                        <span class="text-gray-400 font-normal text-xs ml-1">(14 วัน)</span>
                    </h3>
                </div>
                <div id="upcomingList" class="p-3 max-h-72 overflow-y-auto">
                    <p class="text-center text-gray-400 text-sm py-4">กำลังโหลด...</p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- ════ CREATE / EDIT MODAL ════ -->
<div id="jobModal" class="modal">
  <div class="modal-content">
    <div class="flex justify-between items-center mb-5 pb-4 border-b border-gray-100">
        <h2 id="modalTitle" class="text-lg font-semibold text-gray-800">สร้างงานใหม่</h2>
        <button onclick="closeJobModal()" class="text-gray-400 hover:text-gray-600 transition p-1">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <form id="jobForm" class="space-y-4">
        <input type="hidden" id="fJobId" name="job_id">
        <input type="hidden" id="fAction" name="action" value="create">

        <!-- Title + Type + Service Type -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="form-group sm:col-span-2">
                <label>ชื่องาน <span class="text-red-500">*</span></label>
                <input type="text" id="fTitle" name="title" required placeholder="ชื่องาน/กิจกรรม">
            </div>
            <div class="form-group">
                <label>ประเภทงาน</label>
                <select id="fJobType" name="job_type">
                    <option value="routine">🔄 รูทีน</option>
                    <option value="event">🎪 อีเวนต์</option>
                    <option value="project">📁 โปรเจกต์</option>
                    <option value="maintenance">🔧 ซ่อมบำรุง</option>
                    <option value="meeting">👥 ประชุม</option>
                    <option value="other">📌 อื่นๆ</option>
                </select>
            </div>
        </div>

        <!-- Service Type -->
        <div class="form-group">
            <label>บริการที่เกี่ยวข้อง</label>
            <select id="fServiceType" name="service_type">
                <option value="">— ไม่ระบุ —</option>
                <option value="photography">📷 ช่างภาพ</option>
                <option value="mc">🎤 MC / พิธีกร</option>
                <option value="led">📺 จอ LED</option>
                <option value="it_support">💻 IT Support</option>
                <option value="qr_code">🔷 QR Code</option>
                <option value="printer">🖨️ เครื่องพิมพ์</option>
                <option value="web_design">🌐 เว็บไซต์</option>
                <option value="internet">📡 อินเทอร์เน็ต</option>
                <option value="email">📧 อีเมล</option>
                <option value="nas">💾 NAS / Storage</option>
            </select>
        </div>

        <!-- Date + Times -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            <div class="form-group col-span-2">
                <label>วันที่กำหนด</label>
                <input type="text" id="fSchedDate" name="scheduled_date" placeholder="เลือกวันที่" readonly>
            </div>
            <div class="form-group">
                <label>เริ่ม</label>
                <input type="text" id="fStartTime" name="start_time" placeholder="--:--" readonly>
            </div>
            <div class="form-group">
                <label>สิ้นสุด</label>
                <input type="text" id="fEndTime" name="end_time" placeholder="--:--" readonly>
            </div>
        </div>

        <!-- Location (2col) + Priority (1col) -->
        <div class="grid grid-cols-3 gap-3">
            <div class="form-group col-span-2">
                <label>สถานที่</label>
                <input type="text" id="fLocation" name="location" placeholder="ห้อง/อาคาร">
            </div>
            <div class="form-group">
                <label>ลำดับความสำคัญ</label>
                <select id="fPriority" name="priority">
                    <option value="low">🔵 ต่ำ</option>
                    <option value="normal" selected>🟢 ปกติ</option>
                    <option value="high">🟡 สูง</option>
                    <option value="urgent">🔴 เร่งด่วน</option>
                </select>
            </div>
        </div>

        <!-- Dept cascading -->
        <div class="form-group">
            <label>หน่วยงาน</label>
            <input type="hidden" id="fDept" name="department_id">
            <div id="deptCascade" class="space-y-1.5"></div>
        </div>

        <!-- Assign (multi) -->
        <div class="form-group">
            <label>
                มอบหมายให้
                <span class="text-gray-400 font-normal text-xs ml-1">(เพิ่มได้หลายคน)</span>
            </label>
            <div id="assigneeChips" class="flex flex-wrap gap-1.5 mb-1.5"></div>
            <div id="assigneeInputs"></div>
            <select id="fAssignPicker">
                <option value="">+ เพิ่มผู้รับผิดชอบ...</option>
                <?php foreach ($staff as $s): ?>
                <option value="<?= $s['user_id'] ?>"
                        data-name="<?= htmlspecialchars($s['full_name']) ?>"
                        data-line="<?= !empty($s['line_user_id']) ? '1' : '0' ?>">
                    <?= htmlspecialchars($s['full_name']) ?><?= $s['department_name'] ? ' — '.htmlspecialchars($s['department_name']) : '' ?>
                </option>
                <?php endforeach; ?>
            </select>
            <p id="lineHint" class="mt-1 text-xs text-green-600 hidden">
                <i class="fab fa-line mr-1"></i>มีผู้รับผิดชอบที่ใช้ LINE — จะรับแจ้งเตือนอัตโนมัติ
            </p>
        </div>

        <!-- Description -->
        <div class="form-group">
            <label>รายละเอียด</label>
            <textarea id="fDesc" name="description" rows="3" placeholder="รายละเอียด ขั้นตอน หมายเหตุ..."></textarea>
        </div>

        <!-- Attachments -->
        <div class="form-group">
            <label>ไฟล์แนบ / ลิงก์แชร์</label>
            <!-- File upload -->
            <label class="flex items-center gap-2 cursor-pointer px-3 py-2.5 border-2 border-dashed border-gray-300 rounded-lg hover:border-green-400 hover:bg-green-50 transition">
                <i class="fas fa-paperclip text-gray-400"></i>
                <span class="text-sm text-gray-400">คลิกเพื่อเลือกไฟล์ (JPG, PNG, PDF, DOC, XLS) สูงสุด 20 MB/ไฟล์</span>
                <input type="file" name="attachments[]" id="fAttachFiles" multiple
                       accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.xls,.xlsx"
                       class="hidden" onchange="previewFiles(this)">
            </label>
            <div id="filePreviewList" class="mt-1.5 space-y-1"></div>
            <!-- URL links -->
            <div id="urlLinkContainer" class="mt-2 space-y-1.5"></div>
            <button type="button" onclick="addUrlRow()"
                    class="mt-1.5 text-xs text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <i class="fas fa-link"></i> เพิ่ม URL / Google Drive Link
            </button>
            <!-- Existing attachments (edit mode) -->
            <div id="existingAttachments" class="hidden mt-2 border-t border-gray-100 pt-2"></div>
        </div>

        <!-- Footer -->
        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-3 border-t border-gray-100">
            <button type="button" onclick="closeJobModal()" class="btn btn-secondary">ยกเลิก</button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <span id="saveBtnTxt">บันทึก</span>
            </button>
        </div>
    </form>
  </div>
</div>

<!-- ════ DETAIL MODAL ════ -->
<div id="detailModal" class="modal" style="z-index:1001;">
  <div class="modal-content modal-sm">
    <div class="flex justify-between items-start mb-4 pb-3 border-b border-gray-100">
        <div>
            <h2 id="dtTitle" class="text-base font-bold text-gray-800"></h2>
            <p id="dtCode" class="text-xs text-gray-400 font-mono mt-0.5"></p>
        </div>
        <button onclick="closeDetailModal()" class="text-gray-400 hover:text-gray-600 p-1 flex-shrink-0">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div id="dtBody" class="space-y-3 text-sm text-gray-700 mb-4"></div>

    <!-- Action buttons -->
    <div class="flex flex-wrap gap-2 items-center pt-3 border-t border-gray-100">
        <button id="dtEditBtn" class="btn btn-sm" style="background:#f0fdf4;color:#15803d;">
            <i class="fas fa-pen text-xs"></i> แก้ไข
        </button>
        <button id="dtAssignBtn" class="btn btn-sm" style="background:#eff6ff;color:#1d4ed8;">
            <i class="fas fa-user-plus text-xs"></i> มอบหมาย
        </button>
        <div id="dtStatusBtns" class="flex gap-2 flex-wrap"></div>
        <button id="dtDeleteBtn" class="btn btn-sm ml-auto" style="background:#fef2f2;color:#dc2626;">
            <i class="fas fa-trash text-xs"></i> ลบ
        </button>
    </div>

    <!-- Assign sub-form -->
    <div id="assignSub" class="hidden mt-3">
        <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
            <p class="text-xs font-semibold text-gray-600 mb-2">มอบหมายเจ้าหน้าที่ <span class="font-normal text-gray-400">(เพิ่มได้หลายคน)</span></p>
            <div id="dtAssignChips" class="flex flex-wrap gap-1.5 mb-2 min-h-[1.5rem]"></div>
            <div class="form-group mb-2">
                <select id="dtAssignSel">
                    <option value="">+ เพิ่มผู้รับผิดชอบ...</option>
                    <?php foreach ($staff as $s): ?>
                    <option value="<?= $s['user_id'] ?>" data-name="<?= htmlspecialchars($s['full_name']) ?>">
                        <?= htmlspecialchars($s['full_name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button onclick="submitAssign()" class="btn btn-teal flex-1 text-xs py-2">บันทึกการมอบหมาย</button>
                <button type="button" onclick="clearDtAssignees()" class="btn text-xs py-2 px-3" style="background:#fee2e2;color:#dc2626;" title="ล้างทั้งหมด">ล้าง</button>
            </div>
        </div>
    </div>
  </div>
</div>

<script>
const TH_MONTHS   = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
const P_LABELS    = {low:'ต่ำ',normal:'ปกติ',high:'สูง',urgent:'เร่งด่วน'};
const S_LABELS    = {scheduled:'กำหนดการ',in_progress:'กำลังทำ',completed:'เสร็จสิ้น',cancelled:'ยกเลิก'};
const TYPE_ICON   = {routine:'🔄',event:'🎪',project:'📁',maintenance:'🔧',meeting:'👥',other:'📌'};
const SVC_LABELS  = {photography:'📷 ช่างภาพ',mc:'🎤 MC / พิธีกร',led:'📺 จอ LED',it_support:'💻 IT Support',qr_code:'🔷 QR Code',printer:'🖨️ เครื่องพิมพ์',web_design:'🌐 เว็บไซต์',internet:'📡 อินเทอร์เน็ต',email:'📧 อีเมล',nas:'💾 NAS / Storage'};

const calState = {
    year: <?= date('Y') ?>, month: <?= (int)date('n') ?>,
    selectedDate: '<?= $today ?>', jobs: []
};

async function loadMonth() {
    try {
        const m   = String(calState.month).padStart(2,'0');
        const res = await fetch(`api/internal_jobs_api.php?action=list&month=${calState.year}-${m}`);
        const d   = await res.json();
        calState.jobs = d.jobs ?? [];
    } catch(e) {
        calState.jobs = [];
    }
    renderCalendar();
    renderDayList(calState.selectedDate);
}

function renderCalendar() {
    const {year:y, month:m} = calState;
    document.getElementById('calTitle').textContent = `${TH_MONTHS[m]} ${y+543}`;
    const firstDay    = new Date(y, m-1, 1).getDay();
    const daysInMonth = new Date(y, m,   0).getDate();
    const daysInPrev  = new Date(y, m-1, 0).getDate();
    const today       = new Date().toISOString().slice(0,10);

    const byDate = {};
    calState.jobs.forEach(j => { if(!j.scheduled_date) return; (byDate[j.scheduled_date]??=[]).push(j); });

    const cells   = Math.ceil((firstDay + daysInMonth)/7)*7;
    const maxDots = window.innerWidth < 640 ? 2 : 3;
    let html = '';

    for (let i=0; i<cells; i++) {
        let dn, ds, isOther=false;
        if (i < firstDay) {
            dn=daysInPrev-firstDay+i+1; isOther=true;
            const pm=m===1?12:m-1, py=m===1?y-1:y;
            ds=`${py}-${String(pm).padStart(2,'0')}-${String(dn).padStart(2,'0')}`;
        } else if (i >= firstDay+daysInMonth) {
            dn=i-firstDay-daysInMonth+1; isOther=true;
            const nm=m===12?1:m+1, ny=m===12?y+1:y;
            ds=`${ny}-${String(nm).padStart(2,'0')}-${String(dn).padStart(2,'0')}`;
        } else {
            dn=i-firstDay+1;
            ds=`${y}-${String(m).padStart(2,'0')}-${String(dn).padStart(2,'0')}`;
        }
        const jobs=byDate[ds]??[];
        const cls='cal-cell'+(isOther?' other-month':'')+(ds===today?' today':'')+(ds===calState.selectedDate?' selected':'');
        let dots=jobs.slice(0,maxDots).map(j=>`<span class="cal-dot p-${j.priority}" onclick="event.stopPropagation();showDetail(${j.job_id})" title="${j.title}">${TYPE_ICON[j.job_type]||'📌'}<span class="cal-dot-text"> ${j.title}</span></span>`).join('');
        if(jobs.length>maxDots) dots+=`<span class="cal-dot" style="background:#f3f4f6;color:#6b7280">+${jobs.length-maxDots}</span>`;
        html+=`<div class="${cls}" onclick="selectDate('${ds}')"><div class="cal-date">${dn}</div>${dots}</div>`;
    }
    document.getElementById('calBody').innerHTML = html;
}

function selectDate(ds) { calState.selectedDate=ds; renderCalendar(); renderDayList(ds); }

function renderDayList(ds) {
    const [y,m,d]=ds.split('-');
    document.getElementById('listTitle').textContent=`งานวันที่ ${+d} ${TH_MONTHS[+m]} ${+y+543}`;
    const jobs=calState.jobs.filter(j=>j.scheduled_date===ds);
    const el=document.getElementById('dayJobList');
    if(!jobs.length){
        el.innerHTML=`<div class="text-center py-8 text-gray-400">
            <i class="fas fa-calendar-day text-3xl mb-2 block opacity-30"></i>
            <p class="text-sm">ไม่มีงานในวันนี้</p>
            <button onclick="openCreateModal('${ds}')" class="mt-3 text-xs text-green-600 hover:underline">+ เพิ่มงานในวันนี้</button>
        </div>`;
        return;
    }
    el.innerHTML=jobs.map(j=>`<div class="job-card p-${j.priority}" onclick="showDetail(${j.job_id})">
        <div class="flex items-start justify-between gap-2">
            <div class="flex-1 min-w-0">
                <p class="font-semibold text-gray-800 text-sm truncate">${TYPE_ICON[j.job_type]||''} ${j.title}</p>
                <div class="flex items-center gap-2 mt-1 flex-wrap">
                    <span class="text-xs s-${j.status} px-1.5 py-0.5 rounded">${S_LABELS[j.status]||j.status}</span>
                    ${j.start_time?`<span class="text-xs text-gray-400"><i class="fas fa-clock mr-1"></i>${j.start_time.slice(0,5)}${j.end_time?' – '+j.end_time.slice(0,5):''}</span>`:''}
                    ${j.location?`<span class="text-xs text-gray-400"><i class="fas fa-map-marker-alt mr-1"></i>${j.location}</span>`:''}
                </div>
            </div>
            <div class="text-right flex-shrink-0 max-w-[120px]">
                ${(j.assignees_names||j.assigned_to_name)
                    ?`<div class="text-xs text-gray-500 leading-snug">${j.assignees_names||j.assigned_to_name}</div>`
                    :`<div class="text-xs text-amber-500 italic">ยังไม่มอบหมาย</div>`}
            </div>
        </div>
    </div>`).join('');
}

async function loadUpcoming() {
    let jobs = [];
    try {
        const res=await fetch('api/internal_jobs_api.php?action=upcoming&days=14&limit=20');
        const d=await res.json();
        jobs=d.jobs??[];
    } catch(e) { jobs=[]; }
    const el=document.getElementById('upcomingList');
    if(!jobs.length){ el.innerHTML='<p class="text-center text-gray-400 text-sm py-4">ไม่มีงานใน 14 วันข้างหน้า</p>'; return; }
    el.innerHTML=jobs.map(j=>{
        const [yy,mm,dd]=j.scheduled_date.split('-');
        return `<div class="flex items-start gap-3 py-2.5 border-b border-gray-50 cursor-pointer hover:bg-gray-50 px-1 rounded" onclick="showDetail(${j.job_id})">
            <div class="text-center w-9 flex-shrink-0">
                <p class="text-xs text-gray-400 leading-none">${TH_MONTHS[+mm].slice(0,3)}</p>
                <p class="text-lg font-bold text-teal-700 leading-tight">${+dd}</p>
            </div>
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-800 truncate">${TYPE_ICON[j.job_type]||''} ${j.title}</p>
                <p class="text-xs text-gray-400">${j.start_time?j.start_time.slice(0,5)+' น. ':''}${(j.assignees_names||j.assigned_to_name)||'<span class="text-amber-500">ยังไม่มอบหมาย</span>'}</p>
            </div>
            <span class="flex-shrink-0 text-xs px-1.5 py-0.5 rounded p-${j.priority}">${P_LABELS[j.priority]}</span>
        </div>`;
    }).join('');
}

function changeMonth(d){ calState.month+=d; if(calState.month>12){calState.month=1;calState.year++;} if(calState.month<1){calState.month=12;calState.year--;} loadMonth(); }
function goToday(){ const n=new Date(); calState.year=n.getFullYear(); calState.month=n.getMonth()+1; calState.selectedDate=n.toISOString().slice(0,10); loadMonth(); }

// ── Create / Edit Modal ──────────────────────────────────
function openCreateModal(prefill='') {
    document.getElementById('jobForm').reset();
    document.getElementById('fAction').value='create'; document.getElementById('fJobId').value='';
    document.getElementById('modalTitle').textContent='สร้างงานใหม่'; document.getElementById('saveBtnTxt').textContent='บันทึก';
    document.getElementById('lineHint').classList.add('hidden');
    if(prefill) document.getElementById('fSchedDate').value=prefill;
    document.getElementById('jobModal').classList.add('active');
}

function openEditModal(job) {
    document.getElementById('fAction').value='update'; document.getElementById('fJobId').value=job.job_id;
    document.getElementById('fTitle').value=job.title; document.getElementById('fJobType').value=job.job_type;
    document.getElementById('fServiceType').value=job.service_type??'';
    document.getElementById('fPriority').value=job.priority; document.getElementById('fSchedDate').value=job.scheduled_date??'';
    document.getElementById('fStartTime').value=(job.start_time??'').slice(0,5); document.getElementById('fEndTime').value=(job.end_time??'').slice(0,5);
    document.getElementById('fLocation').value=job.location??''; document.getElementById('fDept').value=job.department_id??'';
    document.getElementById('fAssignTo').value=job.assigned_to??''; document.getElementById('fDesc').value=job.description??'';
    document.getElementById('modalTitle').textContent='แก้ไขงาน'; document.getElementById('saveBtnTxt').textContent='บันทึกการแก้ไข';
    document.getElementById('jobModal').classList.add('active'); closeDetailModal();
}

function closeJobModal(){ document.getElementById('jobModal').classList.remove('active'); }

// ── Multi-Assignee (create/edit form) ────────────────────────
let _selectedAssignees = [];
function _renderAssigneeChips() {
    const chips = document.getElementById('assigneeChips');
    const inputs = document.getElementById('assigneeInputs');
    chips.innerHTML = '';
    inputs.innerHTML = '';
    _selectedAssignees.forEach(a => {
        const chip = document.createElement('span');
        chip.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-teal-100 text-teal-800';
        chip.innerHTML = `<i class="fas fa-user text-[9px]"></i>${a.name} <button type="button" onclick="removeAssignee(${a.id})" class="ml-0.5 text-teal-600 hover:text-red-600 font-bold leading-none">&times;</button>`;
        chips.appendChild(chip);
        const inp = document.createElement('input');
        inp.type = 'hidden'; inp.name = 'assignees[]'; inp.value = a.id;
        inputs.appendChild(inp);
    });
    document.getElementById('lineHint').classList.toggle('hidden', !_selectedAssignees.some(a => a.line));
}
function removeAssignee(id) { _selectedAssignees = _selectedAssignees.filter(a => a.id !== id); _renderAssigneeChips(); }
function clearAssignees() { _selectedAssignees = []; _renderAssigneeChips(); }
function setAssignees(list) {
    _selectedAssignees = (list||[]).map(a => ({id: parseInt(a.user_id), name: a.full_name, line: !!(a.line_user_id)}));
    _renderAssigneeChips();
}
document.getElementById('fAssignPicker').addEventListener('change', function() {
    const val = this.value; if (!val) return;
    const opt = this.options[this.selectedIndex];
    if (_selectedAssignees.some(a => a.id == val)) { this.value = ''; return; }
    _selectedAssignees.push({ id: parseInt(val), name: opt.dataset.name, line: opt.dataset.line === '1' });
    _renderAssigneeChips();
    this.value = '';
});

// ── Multi-Assignee (detail modal) ────────────────────────────
let _dtAssignees = [];
function _renderDtChips() {
    const chips = document.getElementById('dtAssignChips');
    chips.innerHTML = '';
    _dtAssignees.forEach(a => {
        const c = document.createElement('span');
        c.className = 'inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800';
        c.innerHTML = `${a.name} <button type="button" onclick="removeDtAssignee(${a.id})" class="ml-0.5 hover:text-red-600 font-bold leading-none">&times;</button>`;
        chips.appendChild(c);
    });
}
function removeDtAssignee(id) { _dtAssignees = _dtAssignees.filter(a => a.id !== id); _renderDtChips(); }
function clearDtAssignees() { _dtAssignees = []; _renderDtChips(); }
document.getElementById('dtAssignSel').addEventListener('change', function() {
    const val = this.value; if (!val) return;
    const opt = this.options[this.selectedIndex];
    if (_dtAssignees.some(a => a.id == val)) { this.value = ''; return; }
    _dtAssignees.push({ id: parseInt(val), name: opt.dataset.name });
    _renderDtChips();
    this.value = '';
});

document.getElementById('jobForm').addEventListener('submit', async function(e){
    e.preventDefault();
    const btn=this.querySelector('button[type=submit]'); btn.disabled=true;
    try {
        const res=await fetch('api/internal_jobs_api.php',{method:'POST',body:new FormData(this)});
        const d=await res.json();
        if(d.success){
            closeJobModal();
            Swal.fire({icon:'success',title:'สำเร็จ',text:d.message,timer:1500,showConfirmButton:false});
            await loadMonth(); loadUpcoming();
        } else {
            Swal.fire({icon:'error',title:'ผิดพลาด',text:d.message});
        }
    } finally { btn.disabled=false; }
});

// ── Detail Modal ─────────────────────────────────────────
let currentJobId=null;
async function showDetail(id){
    currentJobId=id;
    const res=await fetch(`api/internal_jobs_api.php?action=get&job_id=${id}`);
    const d=await res.json(); if(!d.success) return;
    const j=d.job;
    document.getElementById('dtTitle').textContent=`${TYPE_ICON[j.job_type]||''} ${j.title}`;
    document.getElementById('dtCode').textContent=j.job_code;
    const [yy,mm,dd]=(j.scheduled_date??'----').split('-');
    const dateStr=j.scheduled_date
        ?`${+dd} ${TH_MONTHS[+mm]} ${+yy+543}`+(j.start_time?' เวลา '+j.start_time.slice(0,5)+(j.end_time?' – '+j.end_time.slice(0,5):''):'')
        :'ยังไม่กำหนด';
    document.getElementById('dtBody').innerHTML=`
        <div class="flex flex-wrap gap-2 mb-1">
            <span class="text-xs px-2 py-1 rounded s-${j.status}">${S_LABELS[j.status]||j.status}</span>
            <span class="text-xs px-2 py-1 rounded p-${j.priority}">${P_LABELS[j.priority]}</span>
            ${j.service_type?`<span class="text-xs px-2 py-1 rounded bg-indigo-50 text-indigo-700">${SVC_LABELS[j.service_type]||j.service_type}</span>`:''}
        </div>
        <div class="grid grid-cols-2 gap-x-4 gap-y-1.5 text-sm">
            <div><span class="text-gray-500">วันที่:</span> ${dateStr}</div>
            <div><span class="text-gray-500">สถานที่:</span> ${j.location||'—'}</div>
            <div><span class="text-gray-500">หน่วยงาน:</span> ${j.department_name||'—'}</div>
            <div><span class="text-gray-500">สร้างโดย:</span> ${j.assigned_by_name||'—'}</div>
            <div class="col-span-2"><span class="text-gray-500">มอบหมายให้:</span> ${(j.assignees&&j.assignees.length)?j.assignees.map(a=>`<strong>${a.full_name}</strong>`).join(', '):'<em class="text-amber-500">ยังไม่มอบหมาย</em>'}</div>
        </div>
        ${j.description?`<div class="bg-gray-50 rounded-lg p-3 text-sm mt-1">${j.description.replace(/\n/g,'<br>')}</div>`:''}
        ${(j.attachments&&j.attachments.length)?`<div class="mt-2 pt-2 border-t border-gray-100">
            <p class="text-xs font-medium text-gray-500 mb-1"><i class="fas fa-paperclip mr-1"></i>ไฟล์แนบ (${j.attachments.length})</p>
            <div class="space-y-1">${j.attachments.map(a=>a.attach_type==='url'
                ?`<a href="${a.url}" target="_blank" class="flex items-center gap-1.5 text-xs text-blue-600 hover:underline"><i class="fas fa-link"></i>${a.label||a.url}</a>`
                :`<a href="/iservice/${a.file_path}" target="_blank" class="flex items-center gap-1.5 text-xs text-gray-700 hover:underline"><i class="fas fa-paperclip text-gray-400"></i>${a.label}</a>`
            ).join('')}</div>
        </div>`:''}
    `;
    const sbtnColors={scheduled:'background:#eff6ff;color:#1d4ed8',in_progress:'background:#fffbeb;color:#92400e',completed:'background:#f0fdf4;color:#15803d',cancelled:'background:#fef2f2;color:#dc2626'};
    const sbtnLabels={scheduled:'📅 กำหนดการ',in_progress:'⚙️ กำลังทำ',completed:'✅ เสร็จสิ้น',cancelled:'❌ ยกเลิก'};
    document.getElementById('dtStatusBtns').innerHTML=['scheduled','in_progress','completed','cancelled']
        .filter(s=>s!==j.status)
        .map(s=>`<button onclick="updateStatus(${j.job_id},'${s}')" class="btn btn-sm" style="${sbtnColors[s]}">${sbtnLabels[s]}</button>`)
        .join('');
    document.getElementById('dtEditBtn').onclick=()=>openEditModal(j);
    document.getElementById('dtDeleteBtn').onclick=()=>deleteJob(j.job_id);
    document.getElementById('assignSub').classList.add('hidden');
    _dtAssignees = (j.assignees||[]).map(a=>({id:parseInt(a.user_id),name:a.full_name}));
    _renderDtChips();
    document.getElementById('detailModal').classList.add('active');
}

function closeDetailModal(){ document.getElementById('detailModal').classList.remove('active'); document.getElementById('assignSub').classList.add('hidden'); }
document.getElementById('dtAssignBtn').addEventListener('click',()=>document.getElementById('assignSub').classList.toggle('hidden'));

async function submitAssign(){
    const fd=new FormData(); fd.append('action','assign'); fd.append('job_id',currentJobId);
    _dtAssignees.forEach(a=>fd.append('assignees[]',a.id));
    const res=await fetch('api/internal_jobs_api.php',{method:'POST',body:fd}); const d=await res.json();
    Swal.fire({icon:d.success?'success':'error',title:d.success?'สำเร็จ':'ผิดพลาด',text:d.message,timer:1500,showConfirmButton:false});
    if(d.success){ closeDetailModal(); await loadMonth(); loadUpcoming(); }
}

async function updateStatus(id,status){
    const fd=new FormData(); fd.append('action','update_status'); fd.append('job_id',id); fd.append('status',status);
    const res=await fetch('api/internal_jobs_api.php',{method:'POST',body:fd}); const d=await res.json();
    if(d.success){ Swal.fire({icon:'success',title:'สำเร็จ',text:d.message,timer:1200,showConfirmButton:false}); closeDetailModal(); await loadMonth(); loadUpcoming(); }
    else Swal.fire({icon:'error',title:'ผิดพลาด',text:d.message});
}

async function deleteJob(id){
    const c=await Swal.fire({title:'ยืนยันการลบ',text:'ต้องการลบงานนี้ใช่หรือไม่?',icon:'warning',showCancelButton:true,confirmButtonColor:'#ef4444',cancelButtonColor:'#6b7280',confirmButtonText:'ลบ',cancelButtonText:'ยกเลิก'});
    if(!c.isConfirmed) return;
    const fd=new FormData(); fd.append('action','delete'); fd.append('job_id',id);
    const res=await fetch('api/internal_jobs_api.php',{method:'POST',body:fd}); const d=await res.json();
    if(d.success){ Swal.fire({icon:'success',title:'ลบแล้ว',timer:1200,showConfirmButton:false}); closeDetailModal(); await loadMonth(); loadUpcoming(); }
    else Swal.fire({icon:'error',title:'ผิดพลาด',text:d.message});
}

const _jobModal    = document.getElementById('jobModal');
const _detailModal = document.getElementById('detailModal');
if (_jobModal)    _jobModal.addEventListener('click',   e=>{ if(e.target.id==='jobModal')    closeJobModal(); });
if (_detailModal) _detailModal.addEventListener('click', e=>{ if(e.target.id==='detailModal') closeDetailModal(); });

loadMonth();
loadUpcoming();
</script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
// ── Flatpickr: Thai Buddhist Era + 24hr time ───────────────
const FP_LOCALE = {
    months: {
        shorthand: ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'],
        longhand:  ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                    'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม']
    },
    weekdays: {
        shorthand: ['อา','จ','อ','พ','พฤ','ศ','ส'],
        longhand:  ['อาทิตย์','จันทร์','อังคาร','พุธ','พฤหัสบดี','ศุกร์','เสาร์']
    },
    firstDayOfWeek: 0
};

function setThaiAlt(fp) {
    if (!fp.selectedDates.length || !fp.altInput) return;
    const d = fp.selectedDates[0];
    fp.altInput.value = `${d.getDate()} ${FP_LOCALE.months.longhand[d.getMonth()]} ${d.getFullYear()+543}`;
}
function setThaiAltDT(fp) {
    if (!fp.selectedDates.length || !fp.altInput) return;
    const d = fp.selectedDates[0];
    const h = String(d.getHours()).padStart(2,'0');
    const m = String(d.getMinutes()).padStart(2,'0');
    fp.altInput.value = `${d.getDate()} ${FP_LOCALE.months.longhand[d.getMonth()]} ${d.getFullYear()+543}  ${h}:${m}`;
}

const fpSchedDate = flatpickr('#fSchedDate', {
    dateFormat: 'Y-m-d', altInput: true, altFormat: 'j F Y',
    locale: FP_LOCALE,
    onReady(_,__,fp){ setThaiAlt(fp); },
    onChange(_,__,fp){ setThaiAlt(fp); }
});
const fpStartTime = flatpickr('#fStartTime', {
    enableTime: true, noCalendar: true, dateFormat: 'H:i', time_24hr: true
});
const fpEndTime = flatpickr('#fEndTime', {
    enableTime: true, noCalendar: true, dateFormat: 'H:i', time_24hr: true
});
// ── Attachments ───────────────────────────────────────────
function previewFiles(input) {
    const list = document.getElementById('filePreviewList');
    list.innerHTML = '';
    Array.from(input.files).forEach(f => {
        const icon = f.type.startsWith('image/') ? 'fa-image text-green-500' :
                     f.type === 'application/pdf' ? 'fa-file-pdf text-red-500' :
                     f.name.match(/\.xlsx?$/i) ? 'fa-file-excel text-emerald-600' :
                     f.name.match(/\.docx?$/i) ? 'fa-file-word text-blue-600' : 'fa-file text-gray-400';
        const size = f.size > 1024*1024 ? (f.size/1024/1024).toFixed(1)+' MB' : Math.round(f.size/1024)+' KB';
        list.innerHTML += `<div class="flex items-center gap-2 text-sm bg-gray-50 px-2.5 py-1.5 rounded-lg border border-gray-100">
            <i class="fas ${icon}"></i>
            <span class="flex-1 truncate text-gray-700">${f.name}</span>
            <span class="text-xs text-gray-400 flex-shrink-0">${size}</span>
        </div>`;
    });
}

let _urlCount = 0;
function addUrlRow() {
    _urlCount++;
    const id = 'urlRow'+_urlCount;
    const div = document.createElement('div');
    div.id = id; div.className = 'flex gap-1.5 items-center';
    div.innerHTML = `
        <i class="fas fa-link text-gray-300 text-xs flex-shrink-0"></i>
        <input type="text" name="url_labels[]" placeholder="ชื่อลิงก์ (ไม่บังคับ)"
               class="w-1/3 px-2.5 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-green-400">
        <input type="text" name="url_links[]" placeholder="https://drive.google.com/..."
               class="flex-1 px-2.5 py-1.5 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-green-400">
        <button type="button" onclick="document.getElementById('${id}').remove()"
                class="text-gray-300 hover:text-red-400 text-sm flex-shrink-0">
            <i class="fas fa-times-circle"></i>
        </button>`;
    document.getElementById('urlLinkContainer').appendChild(div);
}

function clearAttachmentUI() {
    document.getElementById('filePreviewList').innerHTML = '';
    document.getElementById('urlLinkContainer').innerHTML = '';
    const ea = document.getElementById('existingAttachments');
    ea.innerHTML = ''; ea.classList.add('hidden');
    document.getElementById('fAttachFiles').value = '';
}

function showExistingAttachments(attachments) {
    const div = document.getElementById('existingAttachments');
    if (!attachments || !attachments.length) { div.classList.add('hidden'); return; }
    div.classList.remove('hidden');
    div.innerHTML = '<p class="text-xs font-medium text-gray-500 mb-1.5">ไฟล์แนบที่มีอยู่:</p>' +
        attachments.map(a => {
            if (a.attach_type === 'url') {
                return `<div class="flex items-center gap-2 text-sm py-1 border-b border-gray-50" id="att-${a.id}">
                    <i class="fas fa-link text-blue-400 flex-shrink-0"></i>
                    <a href="${a.url}" target="_blank" class="flex-1 text-blue-600 hover:underline truncate text-xs">${a.label||a.url}</a>
                    <button type="button" onclick="deleteAttachment(${a.id})" class="text-gray-300 hover:text-red-400 text-xs flex-shrink-0"><i class="fas fa-trash"></i></button>
                </div>`;
            }
            const icon = a.mime_type && a.mime_type.startsWith('image/') ? 'fa-image text-green-500' :
                         a.label && /\.pdf$/i.test(a.label) ? 'fa-file-pdf text-red-500' :
                         a.label && /\.xlsx?$/i.test(a.label) ? 'fa-file-excel text-emerald-600' :
                         a.label && /\.docx?$/i.test(a.label) ? 'fa-file-word text-blue-600' : 'fa-file text-gray-400';
            return `<div class="flex items-center gap-2 text-sm py-1 border-b border-gray-50" id="att-${a.id}">
                <i class="fas ${icon} flex-shrink-0"></i>
                <a href="/iservice/${a.file_path}" target="_blank" class="flex-1 text-gray-700 hover:underline truncate text-xs">${a.label}</a>
                <button type="button" onclick="deleteAttachment(${a.id})" class="text-gray-300 hover:text-red-400 text-xs flex-shrink-0"><i class="fas fa-trash"></i></button>
            </div>`;
        }).join('');
}

async function deleteAttachment(id) {
    const fd = new FormData();
    fd.append('action','delete_attachment'); fd.append('att_id', id);
    const res = await fetch('api/internal_jobs_api.php',{method:'POST',body:fd});
    const d = await res.json();
    if (d.success) { const el = document.getElementById('att-'+id); if(el) el.remove(); }
    else Swal.fire({icon:'error',text:d.message,timer:1500,showConfirmButton:false});
}

// ── Cascading Department Selector ────────────────────────
let _allDepts = [], _deptTree = {};

async function _loadAllDepts() {
    if (_allDepts.length) return;
    try {
        const res = await fetch('../api/get_departments.php?level=0');
        const d = await res.json();
        _allDepts = d.data ?? [];
        _deptTree = {};
        _allDepts.forEach(dept => {
            const pid = dept.parent_department_id ?? 0;
            (_deptTree[pid] = _deptTree[pid] || []).push(dept);
        });
    } catch(e) { _allDepts = []; }
}

function _findDeptPath(deptId) {
    const id = parseInt(deptId);
    const dept = _allDepts.find(d => parseInt(d.department_id) === id);
    if (!dept) return [];
    const path = [id];
    let cur = dept;
    while (cur.parent_department_id) {
        const pid = parseInt(cur.parent_department_id);
        path.unshift(pid);
        cur = _allDepts.find(d => parseInt(d.department_id) === pid);
        if (!cur) break;
    }
    return path;
}

function _renderDeptLevel(parentId, levelNum, selectedId = '') {
    const children = _deptTree[parentId] ?? [];
    const container = document.getElementById('deptCascade');
    container.querySelectorAll('[data-dlevel]').forEach(el => {
        if (parseInt(el.dataset.dlevel) >= levelNum) el.remove();
    });
    if (!children.length) return;

    const levelLabels = {1:'สำนัก/กอง', 2:'ส่วน', 3:'ฝ่าย/กลุ่มงาน', 4:'งาน'};
    const sel = document.createElement('select');
    sel.dataset.dlevel = levelNum;
    sel.className = 'w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-green-400 bg-white';
    sel.innerHTML = `<option value="">— เลือก${levelLabels[levelNum] || 'หน่วยงาน'} —</option>`;
    children.forEach(dept => {
        const opt = document.createElement('option');
        opt.value = dept.department_id;
        opt.textContent = dept.department_name;
        if (String(dept.department_id) === String(selectedId)) opt.selected = true;
        sel.appendChild(opt);
    });
    sel.addEventListener('change', function() {
        const val = this.value;
        document.getElementById('fDept').value = val;
        container.querySelectorAll('[data-dlevel]').forEach(el => {
            if (parseInt(el.dataset.dlevel) > levelNum) el.remove();
        });
        if (val) _renderDeptLevel(parseInt(val), levelNum + 1);
    });
    container.appendChild(sel);
}

async function initDeptCascade(selectedDeptId = '') {
    await _loadAllDepts();
    const container = document.getElementById('deptCascade');
    container.innerHTML = '';
    document.getElementById('fDept').value = '';

    if (!selectedDeptId) {
        _renderDeptLevel(0, 1);
        return;
    }
    const path = _findDeptPath(selectedDeptId);
    let parentId = 0;
    for (let i = 0; i < path.length; i++) {
        _renderDeptLevel(parentId, i + 1, path[i]);
        parentId = path[i];
    }
    if (_deptTree[parseInt(path[path.length - 1])]) {
        _renderDeptLevel(parseInt(path[path.length - 1]), path.length + 1);
    }
    document.getElementById('fDept').value = selectedDeptId;
}

// Preload on page ready
_loadAllDepts();

// ── Override openCreateModal to use Flatpickr ─────────────
openCreateModal = async function(prefill='') {
    document.getElementById('jobForm').reset();
    document.getElementById('fAction').value='create';
    document.getElementById('fJobId').value='';
    document.getElementById('modalTitle').textContent='สร้างงานใหม่';
    document.getElementById('saveBtnTxt').textContent='บันทึก';
    document.getElementById('lineHint').classList.add('hidden');
    fpSchedDate.clear(); fpStartTime.clear(); fpEndTime.clear();
    clearAttachmentUI();
    clearAssignees();
    await initDeptCascade('');
    if (prefill) fpSchedDate.setDate(prefill, true);
    document.getElementById('jobModal').classList.add('active');
};

// ── Override openEditModal to use Flatpickr ───────────────
openEditModal = async function(job) {
    document.getElementById('fAction').value='update';
    document.getElementById('fJobId').value=job.job_id;
    document.getElementById('fTitle').value=job.title;
    document.getElementById('fJobType').value=job.job_type;
    document.getElementById('fServiceType').value=job.service_type??'';
    document.getElementById('fPriority').value=job.priority;
    document.getElementById('fLocation').value=job.location??'';
    document.getElementById('fDept').value=job.department_id??'';
    document.getElementById('fDesc').value=job.description??'';
    document.getElementById('modalTitle').textContent='แก้ไขงาน';
    document.getElementById('saveBtnTxt').textContent='บันทึกการแก้ไข';
    fpSchedDate.setDate(job.scheduled_date??'', true);
    fpStartTime.setDate((job.start_time??'').slice(0,5), true);
    fpEndTime.setDate((job.end_time??'').slice(0,5), true);
    await initDeptCascade(job.department_id ?? '');
    setAssignees(job.assignees??[]);
    clearAttachmentUI();
    showExistingAttachments(job.attachments??[]);
    document.getElementById('jobModal').classList.add('active');
    closeDetailModal();
};
</script>

<?php include 'admin-layout/footer.php'; ?>
