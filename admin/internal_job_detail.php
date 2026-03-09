<?php
/**
 * Internal Job Detail — Read-Only View
 */
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); exit;
}

$job_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$job_id) { header('Location: my_tasks.php'); exit; }

// Fetch job + assigned_by name
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

// Only the assigned user or admin/manager can view
$is_admin = ($_SESSION['role'] ?? '') === 'admin';
if (!$is_admin && $job['assigned_to'] != $_SESSION['user_id']) {
    header('Location: my_tasks.php'); exit;
}

// ── Labels ──────────────────────────────────────────────────
$status_labels = [
    'scheduled'   => 'กำหนดการแล้ว',
    'in_progress' => 'กำลังดำเนินการ',
    'completed'   => 'เสร็จสิ้น',
    'cancelled'   => 'ยกเลิก',
];
$status_colors = [
    'scheduled'   => ['bg'=>'#fffbeb','color'=>'#d97706','badge'=>'bg-amber-100 text-amber-800'],
    'in_progress' => ['bg'=>'#f5f3ff','color'=>'#7c3aed','badge'=>'bg-purple-100 text-purple-800'],
    'completed'   => ['bg'=>'#f0fdf4','color'=>'#16a34a','badge'=>'bg-green-100 text-green-800'],
    'cancelled'   => ['bg'=>'#f9fafb','color'=>'#6b7280','badge'=>'bg-gray-100 text-gray-500'],
];
$priority_labels = ['low'=>'ต่ำ','normal'=>'ปกติ','medium'=>'ปานกลาง','high'=>'สูง','urgent'=>'เร่งด่วน'];
$priority_colors = ['low'=>'#16a34a','normal'=>'#6b7280','medium'=>'#2563eb','high'=>'#ea580c','urgent'=>'#dc2626'];
$job_type_labels = [
    'routine'     => 'งานประจำ',
    'event'       => 'งานอีเวนต์/กิจกรรม',
    'project'     => 'โปรเจค',
    'maintenance' => 'งานซ่อมบำรุง',
    'meeting'     => 'ประชุม',
    'other'       => 'อื่นๆ',
];
$service_type_labels = [
    'PHOTOGRAPHY' => 'บริการช่างภาพ',
    'MC'          => 'บริการพิธีกร',
    'LED'         => 'จอ LED',
    'IT_SUPPORT'  => 'แจ้งซ่อม IT',
];
$svc_icons = [
    'PHOTOGRAPHY'=>'fa-camera','MC'=>'fa-microphone','LED'=>'fa-tv',
    'IT_SUPPORT'=>'fa-desktop','routine'=>'fa-tasks','event'=>'fa-star',
    'project'=>'fa-project-diagram','maintenance'=>'fa-tools',
    'meeting'=>'fa-users','other'=>'fa-briefcase',
];

$sc = $status_colors[$job['status']] ?? $status_colors['cancelled'];
$stype = $job['service_type'] ?? $job['job_type'];
$sic = $svc_icons[$stype] ?? $svc_icons[$job['job_type']] ?? 'fa-briefcase';

$page_title = 'รายละเอียดงาน — ' . htmlspecialchars($job['job_code']);
$current_page = 'my_tasks';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'งานของฉัน', 'url' => 'my_tasks.php'],
    ['label' => htmlspecialchars($job['job_code'])],
];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<div class="p-4 max-w-3xl mx-auto">

  <!-- Breadcrumb -->
  <nav class="text-gray-500 text-sm mb-4 flex items-center gap-1 flex-wrap">
    <a href="index.php" class="hover:text-teal-600"><i class="fas fa-home"></i></a>
    <span>/</span>
    <a href="my_tasks.php" class="hover:text-teal-600">งานของฉัน</a>
    <span>/</span>
    <span class="text-gray-900 font-semibold"><?= htmlspecialchars($job['job_code']) ?></span>
  </nav>

  <!-- ── Header card ──────────────────────────────────────── -->
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 overflow-hidden mb-4">
    <!-- color strip top -->
    <div class="h-1.5 w-full" style="background:<?= $sc['color'] ?>;"></div>
    <div class="p-6">
      <div class="flex items-start justify-between gap-3 flex-wrap">
        <div class="flex items-start gap-3">
          <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
               style="background:<?= $sc['bg'] ?>;color:<?= $sc['color'] ?>;">
            <i class="fas <?= $sic ?> text-xl"></i>
          </div>
          <div>
            <h1 class="text-xl font-bold text-gray-900 leading-snug"><?= htmlspecialchars($job['title']) ?></h1>
            <p class="text-sm font-mono text-gray-400 mt-0.5"><?= htmlspecialchars($job['job_code']) ?></p>
          </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <!-- Status badge -->
          <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-bold <?= $sc['badge'] ?>">
            <i class="fas <?= match($job['status']) {
              'scheduled'=>'fa-calendar-alt','in_progress'=>'fa-spinner',
              'completed'=>'fa-check-double','cancelled'=>'fa-ban', default=>'fa-circle'
            } ?> text-xs"></i>
            <?= $status_labels[$job['status']] ?? $job['status'] ?>
          </span>
          <!-- Priority badge -->
          <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full text-sm font-bold bg-gray-100"
                style="color:<?= $priority_colors[$job['priority']] ?? '#6b7280' ?>;">
            ⚑ <?= $priority_labels[$job['priority']] ?? $job['priority'] ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Info grid ─────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6 mb-4">
    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
      <i class="fas fa-info-circle text-teal-500"></i> รายละเอียดงาน
    </h2>
    <div class="grid grid-cols-2 gap-x-8 gap-y-4">

      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">ประเภทงาน</p>
        <p class="font-semibold text-gray-800">
          <?= htmlspecialchars($job['service_type'] ? ($service_type_labels[$job['service_type']] ?? $job['service_type']) : ($job_type_labels[$job['job_type']] ?? $job['job_type'])) ?>
        </p>
      </div>

      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">วันที่กำหนด</p>
        <p class="font-semibold text-gray-800">
          <?= $job['scheduled_date'] ? thdate('d M Y', strtotime($job['scheduled_date'])) : '-' ?>
        </p>
      </div>

      <?php if ($job['start_time']): ?>
      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">เวลาเริ่ม</p>
        <p class="font-semibold text-gray-800">
          <?= substr($job['start_time'], 0, 5) ?><?= $job['end_time'] ? ' – ' . substr($job['end_time'], 0, 5) : '' ?> น.
        </p>
      </div>
      <?php endif; ?>

      <?php if ($job['due_date']): ?>
      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">กำหนดส่งงาน</p>
        <p class="font-semibold text-gray-800"><?= thdate('d M Y H:i', strtotime($job['due_date'])) ?> น.</p>
      </div>
      <?php endif; ?>

      <?php if ($job['location']): ?>
      <div class="col-span-2">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">สถานที่</p>
        <p class="font-semibold text-gray-800">📍 <?= htmlspecialchars($job['location']) ?></p>
      </div>
      <?php endif; ?>

      <?php if ($job['department_name']): ?>
      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">หน่วยงาน</p>
        <p class="font-semibold text-gray-800"><?= htmlspecialchars($job['department_name']) ?></p>
      </div>
      <?php endif; ?>

      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">มอบหมายโดย</p>
        <p class="font-semibold text-gray-800">
          <?= htmlspecialchars(trim($job['by_first'].' '.$job['by_last']) ?: $job['by_username']) ?>
        </p>
      </div>

      <div>
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-0.5">ผู้รับผิดชอบ</p>
        <p class="font-semibold text-gray-800">
          <?= htmlspecialchars(trim($job['to_first'].' '.$job['to_last']) ?: ($job['to_username'] ?? '-')) ?>
        </p>
      </div>

    </div>
  </div>

  <!-- ── Description ──────────────────────────────────────── -->
  <?php if (!empty($job['description'])): ?>
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6 mb-4">
    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3 flex items-center gap-2">
      <i class="fas fa-align-left text-teal-500"></i> รายละเอียด
    </h2>
    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($job['description']) ?></p>
  </div>
  <?php endif; ?>

  <!-- ── Notes ────────────────────────────────────────────── -->
  <?php if (!empty($job['notes'])): ?>
  <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 mb-4">
    <h2 class="text-xs font-bold text-amber-600 uppercase tracking-widest mb-3 flex items-center gap-2">
      <i class="fas fa-sticky-note"></i> หมายเหตุ
    </h2>
    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($job['notes']) ?></p>
  </div>
  <?php endif; ?>

  <!-- ── Timeline ──────────────────────────────────────────── -->
  <div class="bg-white rounded-2xl shadow-sm ring-1 ring-gray-100 p-6 mb-4">
    <h2 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-4 flex items-center gap-2">
      <i class="fas fa-clock text-teal-500"></i> ประวัติเวลา
    </h2>
    <div class="space-y-3">
      <div class="flex items-start gap-3">
        <div class="w-7 h-7 rounded-full bg-teal-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-file-alt text-teal-600 text-xs"></i>
        </div>
        <div>
          <p class="text-xs text-gray-400">สร้างเมื่อ</p>
          <p class="font-semibold text-gray-800"><?= thdate('d/m/Y H:i', strtotime($job['created_at'])) ?> น.</p>
        </div>
      </div>
      <?php if ($job['accepted_at']): ?>
      <div class="flex items-start gap-3">
        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-hand-paper text-blue-600 text-xs"></i>
        </div>
        <div>
          <p class="text-xs text-gray-400">รับงานเมื่อ</p>
          <p class="font-semibold text-gray-800"><?= thdate('d/m/Y H:i', strtotime($job['accepted_at'])) ?> น.</p>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($job['started_at']): ?>
      <div class="flex items-start gap-3">
        <div class="w-7 h-7 rounded-full bg-purple-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-play text-purple-600 text-xs"></i>
        </div>
        <div>
          <p class="text-xs text-gray-400">เริ่มดำเนินการ</p>
          <p class="font-semibold text-gray-800"><?= thdate('d/m/Y H:i', strtotime($job['started_at'])) ?> น.</p>
        </div>
      </div>
      <?php endif; ?>
      <?php if ($job['completed_at']): ?>
      <div class="flex items-start gap-3">
        <div class="w-7 h-7 rounded-full bg-green-100 flex items-center justify-center flex-shrink-0 mt-0.5">
          <i class="fas fa-check text-green-600 text-xs"></i>
        </div>
        <div>
          <p class="text-xs text-gray-400">เสร็จสิ้นเมื่อ</p>
          <p class="font-semibold text-gray-800"><?= thdate('d/m/Y H:i', strtotime($job['completed_at'])) ?> น.</p>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <?php if (!empty($job['completion_notes'])): ?>
  <div class="bg-green-50 border border-green-200 rounded-2xl p-6 mb-4">
    <h2 class="text-xs font-bold text-green-700 uppercase tracking-widest mb-3 flex items-center gap-2">
      <i class="fas fa-check-circle"></i> บันทึกการเสร็จงาน
    </h2>
    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap"><?= htmlspecialchars($job['completion_notes']) ?></p>
  </div>
  <?php endif; ?>

  <!-- ── Action buttons ────────────────────────────────────── -->
  <?php if ($job['assigned_to'] == $_SESSION['user_id']): ?>
  <div class="flex gap-3 flex-wrap">
    <?php if ($job['status'] === 'scheduled'): ?>
    <button onclick="updateStatus('in_progress')"
            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-white text-sm transition active:scale-95"
            style="background:#7c3aed;">
      <i class="fas fa-play"></i> เริ่มดำเนินการ
    </button>
    <?php elseif ($job['status'] === 'in_progress'): ?>
    <button onclick="updateStatus('completed')"
            class="flex-1 flex items-center justify-center gap-2 py-3 rounded-xl font-bold text-white text-sm transition active:scale-95"
            style="background:#16a34a;">
      <i class="fas fa-check"></i> เสร็จสิ้น
    </button>
    <?php endif; ?>
    <button onclick="window.open('internal_job_print.php?id=<?= $job_id ?>', '_blank')"
            class="flex items-center gap-2 py-3 px-5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 text-sm transition">
      <i class="fas fa-print"></i> พิมพ์/PDF
    </button>
    <a href="my_tasks.php" class="flex items-center gap-2 py-3 px-5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 text-sm transition">
      <i class="fas fa-arrow-left"></i> กลับ
    </a>
  </div>
  <?php else: ?>
  <div class="flex gap-3">
    <button onclick="window.open('internal_job_print.php?id=<?= $job_id ?>', '_blank')"
            class="flex items-center gap-2 py-3 px-5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 text-sm transition">
      <i class="fas fa-print"></i> พิมพ์/PDF
    </button>
    <a href="my_tasks.php" class="inline-flex items-center gap-2 py-3 px-5 rounded-xl font-bold text-gray-600 bg-gray-100 hover:bg-gray-200 text-sm transition">
      <i class="fas fa-arrow-left"></i> กลับ
    </a>
  </div>
  <?php endif; ?>

</div><!-- end p-4 -->

<script>
async function updateStatus(newStatus) {
    const labels = { in_progress: 'เริ่มดำเนินการ', completed: 'เสร็จสิ้น' };
    const result = await Swal.fire({
        title: 'ยืนยันการอัปเดต',
        text: `เปลี่ยนสถานะเป็น "${labels[newStatus]}" ใช่หรือไม่?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'ยืนยัน',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: newStatus === 'completed' ? '#16a34a' : '#7c3aed',
    });
    if (!result.isConfirmed) return;

    const fd = new FormData();
    fd.append('action', 'update_status');
    fd.append('job_id', <?= $job_id ?>);
    fd.append('status', newStatus);
    try {
        const resp = await fetch('api/internal_jobs_api.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            await Swal.fire({ icon: 'success', title: 'อัปเดตสำเร็จ', timer: 1200, showConfirmButton: false });
            location.reload();
        } else {
            Swal.fire('ผิดพลาด', data.message || 'ไม่สามารถอัปเดตได้', 'error');
        }
    } catch(e) {
        Swal.fire('ผิดพลาด', 'เกิดข้อผิดพลาด', 'error');
    }
}
</script>
<?php
// Close mainContent opened by topbar.php
echo '</div>';
include 'admin-layout/footer.php';
?>
