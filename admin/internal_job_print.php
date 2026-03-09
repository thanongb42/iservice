<?php
/**
 * Internal Job Print / PDF View — Standalone A4, no sidebar/navbar
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$job_id) { header('Location: my_tasks.php'); exit; }

$stmt = $conn->prepare("
    SELECT ij.*,
           u_to.first_name as to_first, u_to.last_name as to_last, u_to.username as to_username,
           u_by.first_name as by_first, u_by.last_name as by_last, u_by.username as by_username,
           d.department_name
    FROM internal_jobs ij
    LEFT JOIN users u_to ON ij.assigned_to = u_to.user_id
    LEFT JOIN users u_by ON ij.assigned_by = u_by.user_id
    LEFT JOIN departments d ON ij.department_id = d.department_id
    WHERE ij.job_id = ?
");
$stmt->bind_param('i', $job_id);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();
if (!$job) { header('Location: my_tasks.php'); exit; }

// ── Labels ──────────────────────────────────────────────────
$status_labels = [
    'scheduled'   => 'กำหนดการแล้ว',
    'in_progress' => 'กำลังดำเนินการ',
    'completed'   => 'เสร็จสิ้น',
    'cancelled'   => 'ยกเลิก',
];
$status_color = match($job['status']) {
    'scheduled'   => '#d97706',
    'in_progress' => '#7c3aed',
    'completed'   => '#16a34a',
    'cancelled'   => '#6b7280',
    default       => '#6b7280',
};
$priority_labels = ['low'=>'ต่ำ','normal'=>'ปกติ','medium'=>'ปานกลาง','high'=>'สูง','urgent'=>'เร่งด่วน'];
$priority_color  = match($job['priority']) {
    'urgent' => '#dc2626', 'high' => '#ea580c',
    'medium' => '#2563eb', 'low'  => '#16a34a',
    default  => '#6b7280',
};
$job_type_labels = [
    'routine'=>'งานประจำ','event'=>'งานอีเวนต์/กิจกรรม',
    'project'=>'โปรเจค','maintenance'=>'งานซ่อมบำรุง',
    'meeting'=>'ประชุม','other'=>'อื่นๆ',
];
$service_type_labels = [
    'PHOTOGRAPHY'=>'บริการช่างภาพ','MC'=>'บริการพิธีกร',
    'LED'=>'จอ LED','IT_SUPPORT'=>'แจ้งซ่อม IT',
];
$svc_icons = [
    'PHOTOGRAPHY'=>'📷','MC'=>'🎤','LED'=>'📺','IT_SUPPORT'=>'🖥',
    'routine'=>'📋','event'=>'⭐','project'=>'🗂','maintenance'=>'🔧',
    'meeting'=>'👥','other'=>'💼',
];
$stype = $job['service_type'] ?? $job['job_type'];
$sic   = $svc_icons[$stype] ?? $svc_icons[$job['job_type']] ?? '⚙';
$type_label = $job['service_type']
    ? ($service_type_labels[$job['service_type']] ?? $job['service_type'])
    : ($job_type_labels[$job['job_type']] ?? $job['job_type']);

$assignee = trim($job['to_first'].' '.$job['to_last']) ?: ($job['to_username'] ?? '-');
$assigner = trim($job['by_first'].' '.$job['by_last']) ?: ($job['by_username'] ?? '-');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>พิมพ์งาน <?= htmlspecialchars($job['job_code']) ?></title>
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

  /* Screen wrapper */
  .page-wrapper {
    width: 210mm;
    min-height: 297mm;
    margin: 20px auto;
    background: #fff;
    box-shadow: 0 4px 32px rgba(0,0,0,.18);
    padding: 14mm 14mm 16mm;
  }

  /* Print */
  @media print {
    body { background: #fff; }
    .no-print { display: none !important; }
    .page-wrapper { width: 100%; margin: 0; padding: 10mm 12mm 12mm; box-shadow: none; }
    @page { size: A4 portrait; margin: 0; }
  }

  /* Toolbar */
  .print-toolbar {
    width: 210mm; margin: 0 auto 10px;
    display: flex; gap: 8px; justify-content: flex-end;
  }
  .btn-print {
    background: #7c3aed; color: #fff; border: none; border-radius: 8px;
    padding: 8px 20px; font-family: 'Sarabun', sans-serif;
    font-size: 13px; font-weight: 700; cursor: pointer;
    display: flex; align-items: center; gap: 6px;
  }
  .btn-print:hover { background: #6d28d9; }
  .btn-close {
    background: #fff; color: #374151; border: 1px solid #d1d5db; border-radius: 8px;
    padding: 8px 16px; font-family: 'Sarabun', sans-serif;
    font-size: 13px; font-weight: 600; cursor: pointer;
  }
  .btn-close:hover { background: #f3f4f6; }

  /* Header */
  .doc-header {
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 3px solid #7c3aed;
    padding-bottom: 10px; margin-bottom: 14px;
  }
  .doc-header-left { display: flex; align-items: center; gap: 12px; }
  .org-logo { width: 52px; height: 52px; flex-shrink: 0; object-fit: contain; }
  .org-name { font-size: 11px; color: #6b7280; }
  .job-code  { font-size: 20px; font-weight: 800; color: #5b21b6; line-height: 1.2; }
  .doc-header-right { text-align: right; font-size: 10.5px; color: #6b7280; line-height: 1.6; }

  /* Badges */
  .badges { display: flex; gap: 6px; margin-bottom: 14px; flex-wrap: wrap; align-items: center; }
  .badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 3px 10px; border-radius: 999px;
    font-size: 11.5px; font-weight: 700; color: #fff;
  }
  .badge-outline {
    background: transparent !important; border: 1.5px solid currentColor;
    color: inherit !important;
  }
  .type-badge {
    font-size: 12px; font-weight: 700; padding: 3px 12px; border-radius: 999px;
    background: #f5f3ff; color: #5b21b6; border: 1.5px solid #c4b5fd;
  }

  /* Card */
  .card {
    border: 1px solid #e5e7eb; border-radius: 8px;
    padding: 12px 14px; margin-bottom: 10px;
    break-inside: avoid;
  }
  .card-title {
    font-size: 11px; font-weight: 700; color: #6b7280;
    text-transform: uppercase; letter-spacing: .06em;
    border-bottom: 1px solid #f3f4f6;
    padding-bottom: 6px; margin-bottom: 10px;
    display: flex; align-items: center; gap: 6px;
  }
  .card-title i { color: #7c3aed; }

  /* Info grid */
  .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px 16px; }
  .info-grid.col2 { grid-template-columns: repeat(2, 1fr); }
  .info-label { font-size: 10px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }
  .info-value { font-size: 13px; font-weight: 600; color: #111827; margin-top: 1px; word-break: break-word; }
  .info-value.muted { color: #9ca3af; font-weight: 400; }
  .full-col { grid-column: 1 / -1; }

  /* Description */
  .desc-block { background: #f9fafb; border-radius: 6px; padding: 8px 10px; font-size: 12.5px; line-height: 1.7; white-space: pre-wrap; }
  .notes-block { background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; padding: 8px 10px; font-size: 12.5px; line-height: 1.7; white-space: pre-wrap; }
  .completion-block { background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; padding: 8px 10px; font-size: 12.5px; line-height: 1.7; white-space: pre-wrap; }

  /* Timeline */
  .tl-row { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 8px; }
  .tl-dot { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
  .tl-label { font-size: 10px; color: #9ca3af; }
  .tl-val   { font-size: 12.5px; font-weight: 700; color: #111827; }

  /* Two col layout */
  .two-col { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }

  /* Footer */
  .doc-footer {
    border-top: 1px solid #e5e7eb; margin-top: 12px; padding-top: 8px;
    display: flex; justify-content: space-between; align-items: center;
    font-size: 10px; color: #9ca3af;
  }

  /* Highlight color strip */
  .color-strip {
    height: 4px; border-radius: 4px 4px 0 0; margin: -12px -14px 10px;
  }
</style>
</head>
<body>

<!-- Toolbar (screen only) -->
<div class="print-toolbar no-print">
  <button class="btn-close" onclick="window.close()"><i class="fas fa-times"></i> ปิด</button>
  <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> พิมพ์ / บันทึก PDF</button>
</div>

<div class="page-wrapper">

  <!-- ── HEADER ──────────────────────────────────────────── -->
  <div class="doc-header">
    <div class="doc-header-left">
      <img src="../public/assets/images/logo/rangsit-small-logo.png" class="org-logo" alt="เทศบาลนครรังสิต">
      <div>
        <div class="org-name">เทศบาลนครรังสิต &nbsp;·&nbsp; iService — งานปฏิทิน</div>
        <div class="job-code"><?= htmlspecialchars($job['job_code']) ?></div>
      </div>
    </div>
    <div class="doc-header-right">
      <div><strong>พิมพ์เมื่อ:</strong> <?= thdate('d/m/Y H:i', time()) ?> น.</div>
      <div style="color:#7c3aed;font-weight:600;">iservice.rangsitcity.go.th</div>
    </div>
  </div>

  <!-- ── TITLE + BADGES ──────────────────────────────────── -->
  <div style="margin-bottom:14px;">
    <h1 style="font-size:18px;font-weight:800;color:#1f2937;margin-bottom:8px;">
      <?= $sic ?> <?= htmlspecialchars($job['title']) ?>
    </h1>
    <div class="badges">
      <span class="type-badge"><?= htmlspecialchars($type_label) ?></span>
      <span class="badge" style="background:<?= $status_color ?>">
        <?= htmlspecialchars($status_labels[$job['status']] ?? $job['status']) ?>
      </span>
      <span class="badge badge-outline" style="color:<?= $priority_color ?>;border-color:<?= $priority_color ?>;">
        ⚑ <?= htmlspecialchars($priority_labels[$job['priority']] ?? $job['priority']) ?>
      </span>
    </div>
  </div>

  <!-- ── JOB INFO ─────────────────────────────────────────── -->
  <div class="card">
    <div class="card-title"><i class="fas fa-calendar-alt"></i> ข้อมูลงาน</div>
    <div class="info-grid">

      <div>
        <div class="info-label">วันที่กำหนด</div>
        <div class="info-value"><?= $job['scheduled_date'] ? thdate('d M Y', strtotime($job['scheduled_date'])) : '-' ?></div>
      </div>

      <?php if ($job['start_time']): ?>
      <div>
        <div class="info-label">เวลา</div>
        <div class="info-value">
          <?= substr($job['start_time'], 0, 5) ?><?= $job['end_time'] ? ' – ' . substr($job['end_time'], 0, 5) : '' ?> น.
        </div>
      </div>
      <?php endif; ?>

      <?php if ($job['due_date']): ?>
      <div>
        <div class="info-label">กำหนดส่งงาน</div>
        <div class="info-value"><?= thdate('d M Y H:i', strtotime($job['due_date'])) ?> น.</div>
      </div>
      <?php endif; ?>

      <?php if ($job['location']): ?>
      <div class="full-col">
        <div class="info-label">สถานที่</div>
        <div class="info-value">📍 <?= htmlspecialchars($job['location']) ?></div>
      </div>
      <?php endif; ?>

      <div>
        <div class="info-label">มอบหมายโดย</div>
        <div class="info-value"><?= htmlspecialchars($assigner) ?></div>
      </div>

      <div>
        <div class="info-label">ผู้รับผิดชอบ</div>
        <div class="info-value"><?= htmlspecialchars($assignee) ?></div>
      </div>

      <?php if ($job['department_name']): ?>
      <div>
        <div class="info-label">หน่วยงาน</div>
        <div class="info-value"><?= htmlspecialchars($job['department_name']) ?></div>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- ── DESCRIPTION ──────────────────────────────────────── -->
  <?php if (!empty($job['description'])): ?>
  <div class="card">
    <div class="card-title"><i class="fas fa-align-left"></i> รายละเอียด</div>
    <div class="desc-block"><?= htmlspecialchars($job['description']) ?></div>
  </div>
  <?php endif; ?>

  <!-- ── NOTES ────────────────────────────────────────────── -->
  <?php if (!empty($job['notes'])): ?>
  <div class="card">
    <div class="card-title"><i class="fas fa-sticky-note"></i> หมายเหตุ</div>
    <div class="notes-block"><?= htmlspecialchars($job['notes']) ?></div>
  </div>
  <?php endif; ?>

  <!-- ── TIMELINE + COMPLETION NOTES ─────────────────────── -->
  <div class="two-col">
    <div class="card">
      <div class="card-title"><i class="fas fa-clock"></i> ประวัติเวลา</div>
      <div class="tl-row">
        <div class="tl-dot" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-file-alt"></i></div>
        <div><div class="tl-label">สร้างเมื่อ</div><div class="tl-val"><?= thdate('d/m/Y H:i', strtotime($job['created_at'])) ?> น.</div></div>
      </div>
      <?php if ($job['accepted_at']): ?>
      <div class="tl-row">
        <div class="tl-dot" style="background:#eff6ff;color:#2563eb;"><i class="fas fa-hand-paper"></i></div>
        <div><div class="tl-label">รับงาน</div><div class="tl-val"><?= thdate('d/m/Y H:i', strtotime($job['accepted_at'])) ?> น.</div></div>
      </div>
      <?php endif; ?>
      <?php if ($job['started_at']): ?>
      <div class="tl-row">
        <div class="tl-dot" style="background:#f5f3ff;color:#7c3aed;"><i class="fas fa-play"></i></div>
        <div><div class="tl-label">เริ่มดำเนินการ</div><div class="tl-val"><?= thdate('d/m/Y H:i', strtotime($job['started_at'])) ?> น.</div></div>
      </div>
      <?php endif; ?>
      <?php if ($job['completed_at']): ?>
      <div class="tl-row">
        <div class="tl-dot" style="background:#f0fdf4;color:#16a34a;"><i class="fas fa-check"></i></div>
        <div><div class="tl-label">เสร็จสิ้น</div><div class="tl-val"><?= thdate('d/m/Y H:i', strtotime($job['completed_at'])) ?> น.</div></div>
      </div>
      <?php endif; ?>
    </div>

    <?php if (!empty($job['completion_notes'])): ?>
    <div class="card">
      <div class="card-title"><i class="fas fa-check-circle"></i> บันทึกการเสร็จงาน</div>
      <div class="completion-block"><?= htmlspecialchars($job['completion_notes']) ?></div>
    </div>
    <?php else: ?>
    <div></div>
    <?php endif; ?>
  </div>

  <!-- ── FOOTER ───────────────────────────────────────────── -->
  <div class="doc-footer">
    <span>เทศบาลนครรังสิต — iService งานปฏิทิน</span>
    <span><?= htmlspecialchars($job['job_code']) ?> &nbsp;·&nbsp; พิมพ์ <?= thdate('d/m/Y H:i', time()) ?> น.</span>
  </div>

</div><!-- end page-wrapper -->

<script>
if (new URLSearchParams(window.location.search).get('autoprint') === '1') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
}
</script>
</body>
</html>
