<?php
/**
 * Request Print / PDF View
 * Standalone A4 print page — no sidebar, no navbar
 */
require_once '../config/database.php';
session_start();
require_manager_or_admin();

$request_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$request_id) { header('Location: service_requests.php'); exit; }

// ── Main request ────────────────────────────────────────────
$stmt = $conn->prepare("SELECT * FROM service_requests WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
$request = $stmt->get_result()->fetch_assoc();
if (!$request) { header('Location: service_requests.php?error=notfound'); exit; }

// ── Service-specific details ────────────────────────────────
$service_detail_tables = [
    'EMAIL'       => 'request_email_details',
    'NAS'         => 'request_nas_details',
    'IT_SUPPORT'  => 'request_it_support_details',
    'PHOTOGRAPHY' => 'request_photography_details',
    'MC'          => 'request_mc_details',
    'QR_CODE'     => 'request_qrcode_details',
    'PRINTER'     => 'request_printer_details',
    'WEB_DESIGN'  => 'request_webdesign_details',
    'LED'         => 'request_led_details',
    'INTERNET'    => 'request_internet_details',
];
$service_details = [];
if (isset($service_detail_tables[$request['service_code']])) {
    $tbl = $service_detail_tables[$request['service_code']];
    $ds  = $conn->prepare("SELECT * FROM `{$tbl}` WHERE request_id = ?");
    if ($ds) { $ds->bind_param("i", $request_id); $ds->execute(); $service_details = $ds->get_result()->fetch_assoc() ?: []; }
}

// ── Task assignments ────────────────────────────────────────
$task_assignments = [];
$ta_q = $conn->prepare("
    SELECT ta.*, u_to.first_name as to_first, u_to.last_name as to_last,
           u_to.username as to_username, r.role_name
    FROM task_assignments ta
    JOIN users u_to ON ta.assigned_to = u_to.user_id
    LEFT JOIN roles r ON ta.assigned_as_role = r.role_id
    WHERE ta.request_id = ? AND ta.status != 'cancelled'
    ORDER BY ta.created_at ASC
");
if ($ta_q) { $ta_q->bind_param('i', $request_id); $ta_q->execute(); $r2 = $ta_q->get_result(); while ($row = $r2->fetch_assoc()) $task_assignments[] = $row; }

// ── Labels & maps ───────────────────────────────────────────
$status_labels = [
    'pending' => 'รอการอนุมัติ', 'in_progress' => 'กำลังดำเนินการ',
    'completed' => 'เสร็จสิ้น', 'rejected' => 'ปฏิเสธ', 'cancelled' => 'ยกเลิก',
];
$priority_labels = ['urgent' => 'เร่งด่วน', 'high' => 'สูง', 'medium' => 'ปานกลาง', 'low' => 'ต่ำ'];
$service_map = [
    'PHOTOGRAPHY' => ['label' => 'บริการช่างภาพ',        'icon' => '📷'],
    'MC'          => ['label' => 'บริการพิธีกร',          'icon' => '🎤'],
    'IT_SUPPORT'  => ['label' => 'แจ้งซ่อมคอมพิวเตอร์',  'icon' => '🖥'],
    'EMAIL'       => ['label' => 'ขอใช้อีเมล',            'icon' => '✉️'],
    'NAS'         => ['label' => 'ขอใช้พื้นที่ NAS',      'icon' => '💾'],
    'WEB_DESIGN'  => ['label' => 'ออกแบบเว็บไซต์',        'icon' => '🌐'],
    'PRINTER'     => ['label' => 'แจ้งซ่อมปริ้นเตอร์',    'icon' => '🖨'],
    'QR_CODE'     => ['label' => 'สร้าง QR Code',         'icon' => '⬛'],
    'INTERNET'    => ['label' => 'แจ้งปัญหาอินเทอร์เน็ต', 'icon' => '📡'],
    'LED'         => ['label' => 'ขอใช้จอ LED',           'icon' => '📺'],
];
$svc = $service_map[$request['service_code']] ?? ['label' => $request['service_name'] ?? $request['service_code'], 'icon' => '⚙'];

$field_labels = [
    'event_name'=>'ชื่องาน/โครงการ','event_type'=>'ประเภทงาน',
    'event_date'=>'วันที่จัดงาน','event_time_start'=>'เวลาเริ่ม','event_time_end'=>'เวลาสิ้นสุด',
    'event_location'=>'สถานที่จัดงาน','location'=>'สถานที่','purpose'=>'วัตถุประสงค์',
    'number_of_photographers'=>'จำนวนช่างภาพ','video_required'=>'ต้องการวิดีโอ',
    'drone_required'=>'ต้องการโดรน','delivery_format'=>'รูปแบบส่งมอบ',
    'special_requirements'=>'ข้อมูลเพิ่มเติม','mc_count'=>'จำนวนพิธีกร',
    'language'=>'ภาษา','script_status'=>'สถานะบทพูด','dress_code'=>'การแต่งกาย',
    'requested_username'=>'ชื่อผู้ใช้','email_format'=>'รูปแบบอีเมล',
    'quota_mb'=>'พื้นที่ (MB)','is_new_account'=>'ประเภทคำขอ','existing_email'=>'อีเมลเดิม',
    'firstname_en'=>'ชื่อ (อังกฤษ)','lastname_en'=>'นามสกุล (อังกฤษ)',
    'folder_name'=>'ชื่อโฟลเดอร์','storage_size_gb'=>'ขนาดที่ขอ',
    'permission_type'=>'สิทธิ์การเข้าถึง','shared_with'=>'ผู้ใช้งานร่วม',
    'backup_required'=>'ต้องการ Backup','issue_type'=>'ประเภทปัญหา',
    'urgency_level'=>'ระดับความเร่งด่วน','device_type'=>'ประเภทอุปกรณ์',
    'device_brand'=>'ยี่ห้ออุปกรณ์','symptoms'=>'อาการ/ปัญหา',
    'error_message'=>'ข้อความแสดงข้อผิดพลาด','when_occurred'=>'เกิดเหตุเมื่อ',
    'printer_type'=>'ประเภทเครื่องพิมพ์','printer_brand'=>'ยี่ห้อ','printer_model'=>'รุ่น',
    'serial_number'=>'Serial Number','problem_description'=>'รายละเอียดปัญหา',
    'error_code'=>'รหัส Error','toner_color'=>'สีหมึก','supplies_needed'=>'วัสดุที่ต้องการ',
    'qr_type'=>'ประเภท QR','qr_size'=>'ขนาด QR','qr_content'=>'เนื้อหา',
    'output_format'=>'รูปแบบไฟล์','media_title'=>'ชื่อสื่อ/หัวข้อ',
    'media_type'=>'ประเภทสื่อ','display_date_start'=>'วันที่เริ่มแสดง',
    'display_date_end'=>'วันที่สิ้นสุด','project_name'=>'ชื่อโปรเจค',
    'website_type'=>'ประเภทเว็บไซต์','number_of_pages'=>'จำนวนหน้า',
    'request_type'=>'ประเภทคำขอ','current_issue'=>'ปัญหาที่พบ',
    'note'=>'หมายเหตุ',
];
$value_maps = [
    'is_new_account' => ['1'=>'สร้างบัญชีใหม่','0'=>'ขอเพิ่ม Quota / Reset Password'],
    'video_required' => ['1'=>'ต้องการ','0'=>'ไม่ต้องการ'],
    'drone_required' => ['1'=>'ต้องการ','0'=>'ไม่ต้องการ'],
    'backup_required'=> ['1'=>'ต้องการ Backup','0'=>'ไม่ต้องการ'],
    'event_type'     => ['formal'=>'พิธีการ/ทางการ','entertainment'=>'สันทนาการ','seminar'=>'อบรม/สัมมนา','press'=>'แถลงข่าว','other'=>'อื่นๆ'],
    'language'       => ['TH'=>'ไทย','EN'=>'อังกฤษ','BOTH'=>'ไทย + อังกฤษ'],
    'script_status'  => ['not_ready'=>'ยังไม่มีบท','draft'=>'มีร่างให้','ready'=>'มีบทสมบูรณ์'],
    'permission_type'=> ['read_only'=>'อ่านอย่างเดียว','read_write'=>'อ่าน-เขียน','full_control'=>'ควบคุมเต็ม'],
    'issue_type'     => ['hardware'=>'ฮาร์ดแวร์','software'=>'ซอฟต์แวร์','network'=>'เครือข่าย','other'=>'อื่นๆ','repair'=>'ซ่อมแซม','toner_replacement'=>'เติมหมึก','paper_jam'=>'กระดาษติด','driver_install'=>'ติดตั้ง Driver','new_installation'=>'ติดตั้งเครื่องใหม่'],
    'urgency_level'  => ['low'=>'ต่ำ','medium'=>'ปานกลาง','high'=>'สูง','critical'=>'วิกฤต'],
    'printer_type'   => ['inkjet'=>'Inkjet','laser'=>'Laser','multifunction'=>'Multifunction','scanner'=>'Scanner','plotter'=>'Plotter'],
    'qr_type'        => ['url'=>'URL/เว็บไซต์','text'=>'ข้อความ','vcard'=>'นามบัตร','wifi'=>'WiFi','payment'=>'QR Payment'],
    'media_type'     => ['image'=>'ภาพนิ่ง','video'=>'วิดีโอ','animation'=>'แอนิเมชัน'],
    'website_type'   => ['landing_page'=>'Landing Page','corporate'=>'Corporate','blog'=>'Blog','ecommerce'=>'E-Commerce','portal'=>'Portal','other'=>'อื่นๆ'],
];
$hidden_fields = ['id','request_id','created_at','updated_at'];

$status_color = match($request['status']) {
    'pending'     => '#d97706',
    'in_progress' => '#2563eb',
    'completed'   => '#16a34a',
    'rejected'    => '#dc2626',
    'cancelled'   => '#6b7280',
    default       => '#6b7280',
};
$priority_color = match($request['priority']) {
    'urgent' => '#dc2626',
    'high'   => '#ea580c',
    'medium' => '#2563eb',
    'low'    => '#16a34a',
    default  => '#6b7280',
};

// ── task status labels ──────────────────────────────────────
$ta_status_labels = ['pending'=>'รอรับงาน','accepted'=>'รับงานแล้ว','in_progress'=>'กำลังดำเนินการ','completed'=>'เสร็จสิ้น'];
$ta_status_colors = ['pending'=>'#d97706','accepted'=>'#2563eb','in_progress'=>'#7c3aed','completed'=>'#16a34a'];

// ── Department name ─────────────────────────────────────────
$dept_name = $request['department_name'] ?? '';
if (empty($dept_name) && $request['department_id']) {
    $dq = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
    $dq->bind_param('i', $request['department_id']); $dq->execute();
    $dept_name = $dq->get_result()->fetch_assoc()['department_name'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>พิมพ์คำขอ <?= htmlspecialchars($request['request_code']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Sarabun', sans-serif;
    font-size: 13px;
    color: #1f2937;
    background: #e5e7eb;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
  }

  /* ── Screen wrapper ── */
  .page-wrapper {
    width: 210mm;
    min-height: 297mm;
    margin: 20px auto;
    background: #fff;
    box-shadow: 0 4px 32px rgba(0,0,0,.18);
    padding: 14mm 14mm 16mm;
  }

  /* ── Print ── */
  @media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .page-wrapper { width: 100%; margin: 0; padding: 10mm 12mm 12mm; box-shadow: none; }
    @page { size: A4 portrait; margin: 0; }
  }

  /* ── Header ── */
  .doc-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 3px solid #0f766e;
    padding-bottom: 10px;
    margin-bottom: 14px;
  }
  .doc-header-left { display: flex; align-items: center; gap: 12px; }
  .org-logo {
    width: 52px; height: 52px; flex-shrink: 0;
    object-fit: contain;
  }
  .org-name { font-size: 11px; color: #6b7280; }
  .req-code { font-size: 20px; font-weight: 800; color: #065f46; line-height: 1.2; }
  .doc-header-right { text-align: right; font-size: 10.5px; color: #6b7280; line-height: 1.6; }

  /* ── Badges ── */
  .badges { display: flex; gap: 6px; margin-bottom: 12px; flex-wrap: wrap; align-items: center; }
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px;
    font-size: 11.5px; font-weight: 700; color: #fff;
  }
  .badge-outline {
    background: transparent !important; border: 1.5px solid currentColor;
    color: inherit !important; padding: 2px 9px;
  }
  .svc-badge {
    font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 999px;
    background: #f0fdf4; color: #065f46; border: 1.5px solid #6ee7b7;
  }

  /* ── Section card ── */
  .card {
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 14px;
    margin-bottom: 10px;
  }
  .card-title {
    font-size: 11px; font-weight: 700; color: #6b7280;
    text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 6px; margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
  }
  .card-title i { color: #0d9488; }

  /* ── Info grid ── */
  .info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px 16px;
  }
  .info-grid.col2 { grid-template-columns: repeat(2, 1fr); }
  .info-item {}
  .info-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }
  .info-value { font-size: 13px; font-weight: 600; color: #111827; margin-top: 1px; word-break: break-word; }
  .info-value.muted { color: #9ca3af; font-weight: 400; }

  /* ── Description block ── */
  .desc-block { background: #f9fafb; border-radius: 6px; padding: 8px 10px; font-size: 12.5px; line-height: 1.7; white-space: pre-wrap; }

  /* ── Timeline ── */
  .timeline { display: flex; gap: 0; margin: 6px 0 2px; }
  .tl-step { flex: 1; display: flex; flex-direction: column; align-items: center; position: relative; }
  .tl-step:not(:last-child)::after {
    content: '';
    position: absolute;
    top: 14px; left: 50%; width: 100%;
    height: 2px; background: #d1d5db; z-index: 0;
  }
  .tl-step.done:not(:last-child)::after { background: #0d9488; }
  .tl-dot {
    width: 28px; height: 28px; border-radius: 50%;
    border: 2px solid #d1d5db; background: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px; color: #d1d5db; z-index: 1; position: relative;
  }
  .tl-step.done .tl-dot { background: #0d9488; border-color: #0d9488; color: #fff; }
  .tl-step.rejected .tl-dot { background: #dc2626; border-color: #dc2626; color: #fff; }
  .tl-label { font-size: 10px; font-weight: 600; color: #9ca3af; margin-top: 4px; text-align: center; }
  .tl-step.done .tl-label { color: #065f46; }
  .tl-step.rejected .tl-label { color: #dc2626; }
  .tl-sub { font-size: 9.5px; color: #6b7280; text-align: center; }
  .tl-time { font-size: 9px; color: #9ca3af; text-align: center; }

  /* ── Task assignments ── */
  .ta-row {
    display: flex; align-items: center; justify-content: space-between;
    padding: 6px 10px; border-radius: 6px; background: #f9fafb;
    border: 1px solid #e5e7eb; margin-bottom: 5px;
    font-size: 12px;
  }
  .ta-name { font-weight: 700; }
  .ta-role { color: #6b7280; font-size: 11px; }
  .ta-status-dot {
    display: inline-block; width: 8px; height: 8px;
    border-radius: 50%; margin-right: 4px;
  }

  /* ── Dates row ── */
  .dates-row { display: flex; gap: 16px; flex-wrap: wrap; }
  .date-item { display: flex; align-items: flex-start; gap: 6px; }
  .date-icon { font-size: 13px; margin-top: 1px; }
  .date-label { font-size: 10px; color: #9ca3af; }
  .date-val { font-size: 12.5px; font-weight: 700; }

  /* ── Admin notes ── */
  .notes-box { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 8px 10px; font-size: 12.5px; }

  /* ── Print toolbar ── */
  .print-toolbar {
    width: 210mm; margin: 0 auto 10px;
    display: flex; gap: 8px; justify-content: flex-end;
  }
  .btn-print {
    background: #0f766e; color: #fff; border: none; border-radius: 8px;
    padding: 8px 20px; font-family: 'Sarabun', sans-serif;
    font-size: 13px; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
  }
  .btn-print:hover { background: #065f46; }
  .btn-close {
    background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 8px;
    padding: 8px 16px; font-family: 'Sarabun', sans-serif;
    font-size: 13px; font-weight: 600; cursor: pointer;
  }
  .btn-close:hover { background: #f3f4f6; }

  /* ── Divider ── */
  .divider { border: none; border-top: 1px solid #f3f4f6; margin: 8px 0; }

  /* ── Two col layout ── */
  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
  .full-col { grid-column: 1 / -1; }
</style>
</head>
<body>

<!-- Toolbar (screen only) -->
<div class="print-toolbar no-print">
  <button class="btn-close" onclick="window.close()"><i class="fas fa-times"></i> ปิด</button>
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> พิมพ์ / บันทึก PDF</button>
</div>

<div class="page-wrapper">

  <!-- ── HEADER ─────────────────────────────────────── -->
  <div class="doc-header">
    <div class="doc-header-left">
      <img src="../public/assets/images/logo/rangsit-small-logo.png" class="org-logo" alt="เทศบาลนครรังสิต">
      <div>
        <div class="org-name">เทศบาลนครรังสิต &nbsp;·&nbsp; iService ระบบแจ้งบริการดิจิทัล</div>
        <div class="req-code"><?= htmlspecialchars($request['request_code']) ?></div>
      </div>
    </div>
    <div class="doc-header-right">
      <div><strong>พิมพ์เมื่อ:</strong> <?= thdate('d/m/Y H:i', time()) ?> น.</div>
      <div style="color:#0d9488;font-weight:600;">iservice.rangsitcity.go.th</div>
    </div>
  </div>

  <!-- ── BADGES ─────────────────────────────────────── -->
  <div class="badges">
    <span class="svc-badge"><?= $svc['icon'] ?> <?= htmlspecialchars($svc['label']) ?></span>
    <span class="badge" style="background:<?= $status_color ?>">
      <?= htmlspecialchars($status_labels[$request['status']] ?? $request['status']) ?>
    </span>
    <span class="badge badge-outline" style="color:<?= $priority_color ?>;border-color:<?= $priority_color ?>">
      ⚑ <?= htmlspecialchars($priority_labels[$request['priority']] ?? $request['priority']) ?>
    </span>
  </div>

  <!-- ── TRACKING TIMELINE ──────────────────────────── -->
  <?php
  $track_task = null;
  foreach ($task_assignments as $ta) {
      if (in_array($ta['status'], ['in_progress','completed'])) { $track_task = $ta; break; }
  }
  if (!$track_task) foreach ($task_assignments as $ta) {
      if ($ta['status'] === 'accepted') { $track_task = $ta; break; }
  }
  if (!$track_task && !empty($task_assignments)) $track_task = $task_assignments[0];

  $tr_assigned  = !empty($track_task);
  $tr_accepted  = $tr_assigned && in_array($track_task['status'], ['accepted','in_progress','completed']);
  $tr_working   = $tr_assigned && in_array($track_task['status'], ['in_progress','completed']);
  $tr_completed = $request['status'] === 'completed' || ($track_task && $track_task['status'] === 'completed');
  $tr_rejected  = $request['status'] === 'rejected';
  $tr_cancelled = $request['status'] === 'cancelled';
  $assignee     = $tr_assigned ? trim($track_task['to_first'].' '.$track_task['to_last']) : '';

  $steps = [
    ['icon'=>'fa-file-alt',   'label'=>'รับเรื่อง',        'done'=>true,          'special'=>null, 'sub'=>'', 'time'=>$request['created_at']],
    ['icon'=>'fa-user-tag',   'label'=>'มอบหมายงาน',        'done'=>$tr_assigned,  'special'=>null, 'sub'=>$assignee, 'time'=>$tr_assigned?$track_task['created_at']:null],
    ['icon'=>'fa-hand-paper', 'label'=>'รับงาน',            'done'=>$tr_accepted,  'special'=>null, 'sub'=>'', 'time'=>$tr_accepted?($track_task['accepted_at']??null):null],
    ['icon'=>'fa-cog',        'label'=>'ดำเนินการ',         'done'=>$tr_working,   'special'=>null, 'sub'=>'', 'time'=>$tr_working?($track_task['started_at']??null):null],
    ['icon'=>$tr_rejected?'fa-times-circle':($tr_cancelled?'fa-ban':'fa-check-circle'),
                              'label'=>$tr_rejected?'ปฏิเสธ':($tr_cancelled?'ยกเลิก':'เสร็จสิ้น'),
                              'done'=>$tr_completed||$tr_rejected||$tr_cancelled,
                              'special'=>$tr_rejected?'rejected':($tr_cancelled?'cancelled':null),
                              'sub'=>'', 'time'=>$tr_completed?($request['completed_at']??null):null],
  ];
  ?>
  <div class="card">
    <div class="card-title"><i class="fas fa-route"></i> ความคืบหน้าคำขอ</div>
    <div class="timeline">
      <?php foreach ($steps as $i => $step):
        $cls = 'tl-step';
        if ($step['done']) $cls .= ' done';
        if ($step['special'] === 'rejected') $cls .= ' rejected';
      ?>
      <div class="<?= $cls ?>">
        <div class="tl-dot"><i class="fas <?= $step['icon'] ?>"></i></div>
        <div class="tl-label"><?= $step['label'] ?></div>
        <?php if ($step['sub']): ?><div class="tl-sub"><?= htmlspecialchars($step['sub']) ?></div><?php endif; ?>
        <?php if ($step['time']): ?><div class="tl-time"><?= thdate('d/m H:i', strtotime($step['time'])) ?></div><?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ── REQUESTER INFO ─────────────────────────────── -->
  <div class="card">
    <div class="card-title"><i class="fas fa-user"></i> ข้อมูลผู้ร้องขอ</div>
    <div class="info-grid">
      <?php
      $rname = trim($request['requester_name'] ?? '');
      if (!$rname || $rname === '0') $rname = '-';
      ?>
      <div class="info-item">
        <div class="info-label">ชื่อ-นามสกุล</div>
        <div class="info-value"><?= htmlspecialchars($rname) ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">อีเมล</div>
        <div class="info-value"><?= htmlspecialchars($request['requester_email'] ?? '-') ?: '-' ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">เบอร์โทร</div>
        <div class="info-value"><?= htmlspecialchars($request['requester_phone'] ?? '-') ?: '-' ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">ตำแหน่ง</div>
        <div class="info-value"><?= htmlspecialchars($request['requester_position'] ?? '-') ?: '-' ?></div>
      </div>
      <div class="info-item">
        <div class="info-label">แผนก/หน่วยงาน</div>
        <div class="info-value"><?= htmlspecialchars($dept_name ?: '-') ?></div>
      </div>
      <?php if (!empty($request['requester_line_user_id'])): ?>
      <div class="info-item">
        <div class="info-label">LINE</div>
        <div class="info-value" style="color:#16a34a;">✓ เชื่อมต่อแล้ว</div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── DESCRIPTION ───────────────────────────────── -->
  <?php if (!empty($request['description'])): ?>
  <div class="card">
    <div class="card-title"><i class="fas fa-align-left"></i> รายละเอียดคำขอ</div>
    <div class="desc-block"><?= htmlspecialchars($request['description']) ?></div>
  </div>
  <?php endif; ?>

  <!-- ── SERVICE DETAILS ───────────────────────────── -->
  <?php if (!empty($service_details)): ?>
  <div class="card">
    <div class="card-title"><i class="fas fa-list-alt"></i> รายละเอียดบริการ — <?= htmlspecialchars($svc['label']) ?></div>
    <div class="info-grid col2">
      <?php foreach ($service_details as $key => $val):
        if (in_array($key, $hidden_fields)) continue;
        $label = $field_labels[$key] ?? ucfirst(str_replace('_','',$key));
        $display = (string)$val;
        if (isset($value_maps[$key][$display]))        $display = $value_maps[$key][$display];
        elseif (preg_match('/_date$/', $key) && $val)  $display = thdate('d/m/Y', strtotime($val));
        elseif (preg_match('/_time(_start|_end)?$/', $key) && $val) {
            $ts = strtotime($val); $display = $ts ? date('H:i', $ts) . ' น.' : $val;
        }
        elseif ($key === 'storage_size_gb')   $display = (intval($val)>=1000 ? number_format(intval($val)/1000).' TB' : intval($val).' GB');
        elseif (in_array($key, ['number_of_photographers','mc_count'])) $display = intval($val).' คน';
        if ($display === '' || $display === null) $display = '-';
        $isWide = in_array($key, ['special_requirements','problem_description','note','symptoms','error_message','qr_content','features_required','reference_sites']);
      ?>
      <div class="info-item<?= $isWide ? ' full-col' : '' ?>">
        <div class="info-label"><?= htmlspecialchars($label) ?></div>
        <div class="info-value<?= ($display==='-'?' muted':'') ?>"><?= htmlspecialchars($display) ?></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- ── TASK ASSIGNMENTS ──────────────────────────── -->
  <?php if (!empty($task_assignments)): ?>
  <div class="card">
    <div class="card-title"><i class="fas fa-user-tag"></i> การมอบหมายงาน</div>
    <?php foreach ($task_assignments as $ta):
      $tcolor = $ta_status_colors[$ta['status']] ?? '#6b7280';
      $tlabel = $ta_status_labels[$ta['status']] ?? $ta['status'];
    ?>
    <div class="ta-row">
      <div>
        <span class="ta-name"><?= htmlspecialchars($ta['to_first'].' '.$ta['to_last']) ?></span>
        <span style="color:#9ca3af;margin:0 4px;">@<?= htmlspecialchars($ta['to_username']) ?></span>
        <?php if (!empty($ta['role_name'])): ?><span class="ta-role">(<?= htmlspecialchars($ta['role_name']) ?>)</span><?php endif; ?>
        <?php if (!empty($ta['due_date'])): ?><span class="ta-role"> &nbsp;·&nbsp; กำหนด <?= thdate('d/m/Y', strtotime($ta['due_date'])) ?></span><?php endif; ?>
        <?php if (!empty($ta['task_notes'])): ?><div class="ta-role" style="margin-top:2px;">📝 <?= htmlspecialchars($ta['task_notes']) ?></div><?php endif; ?>
      </div>
      <div style="text-align:right;">
        <span style="font-size:11.5px;font-weight:700;color:<?= $tcolor ?>">
          <span class="ta-status-dot" style="background:<?= $tcolor ?>"></span><?= $tlabel ?>
        </span>
        <?php if (!empty($ta['completed_at'])): ?><div style="font-size:10px;color:#6b7280;">เสร็จ <?= thdate('d/m H:i', strtotime($ta['completed_at'])) ?></div><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- ── DATES & NOTES ─────────────────────────────── -->
  <div class="two-col">
    <div class="card">
      <div class="card-title"><i class="fas fa-clock"></i> วันเวลาสำคัญ</div>
      <div style="display:flex;flex-direction:column;gap:6px;">
        <div class="date-item">
          <div class="date-icon" style="color:#0d9488;">📅</div>
          <div><div class="date-label">ยื่นคำขอเมื่อ</div><div class="date-val"><?= thdate('d/m/Y H:i', strtotime($request['created_at'])) ?></div></div>
        </div>
        <?php if ($request['expected_completion_date']): ?>
        <div class="date-item">
          <div class="date-icon" style="color:#d97706;">⏳</div>
          <div><div class="date-label">วันที่ต้องทำงาน</div><div class="date-val"><?= thdate('d/m/Y', strtotime($request['expected_completion_date'])) ?></div></div>
        </div>
        <?php endif; ?>
        <?php if ($request['started_at']): ?>
        <div class="date-item">
          <div class="date-icon" style="color:#2563eb;">▶</div>
          <div><div class="date-label">เริ่มดำเนินการ</div><div class="date-val"><?= thdate('d/m/Y H:i', strtotime($request['started_at'])) ?></div></div>
        </div>
        <?php endif; ?>
        <?php if ($request['completed_at']): ?>
        <div class="date-item">
          <div class="date-icon" style="color:#16a34a;">✔</div>
          <div><div class="date-label">เสร็จสิ้นเมื่อ</div><div class="date-val"><?= thdate('d/m/Y H:i', strtotime($request['completed_at'])) ?></div></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div>
      <?php if (!empty($request['admin_notes'])): ?>
      <div class="card" style="margin-bottom:10px;">
        <div class="card-title"><i class="fas fa-sticky-note"></i> หมายเหตุเจ้าหน้าที่</div>
        <div class="notes-box"><?= htmlspecialchars($request['admin_notes']) ?></div>
      </div>
      <?php endif; ?>
      <?php if (!empty($request['rejection_reason'])): ?>
      <div class="card">
        <div class="card-title" style="color:#dc2626;"><i class="fas fa-times-circle"></i> เหตุผลปฏิเสธ</div>
        <div class="desc-block" style="background:#fef2f2;border:1px solid #fecaca;"><?= htmlspecialchars($request['rejection_reason']) ?></div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── FOOTER ─────────────────────────────────────── -->
  <div style="border-top:1px solid #e5e7eb;margin-top:12px;padding-top:8px;display:flex;justify-content:space-between;align-items:center;font-size:10px;color:#9ca3af;">
    <span>เทศบาลนครรังสิต — iService ระบบแจ้งบริการดิจิทัล</span>
    <span><?= htmlspecialchars($request['request_code']) ?> &nbsp;·&nbsp; พิมพ์ <?= thdate('d/m/Y H:i', time()) ?> น.</span>
  </div>

</div><!-- end page-wrapper -->

<script>
// Auto print if ?autoprint=1
if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
}
</script>
</body>
</html>
