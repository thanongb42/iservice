<?php
/**
 * Tracking Print Page — ใบติดตามสถานะคำร้อง (PDF/Print layout)
 */
require_once __DIR__ . '/config/database.php';

$request_code = isset($_GET['req']) ? clean_input($_GET['req']) : '';
$request = null;
$timeline = [];

if (!empty($request_code)) {
    $stmt = $conn->prepare("
        SELECT r.*, m.service_name as master_service_name, m.icon, m.color_code
        FROM service_requests r
        LEFT JOIN my_service m ON r.service_code = m.service_code
        WHERE r.request_code = ?
    ");
    $stmt->bind_param("s", $request_code);
    $stmt->execute();
    $request = $stmt->get_result()->fetch_assoc();

    if ($request) {
        $ta_stmt = $conn->prepare("
            SELECT ta.status, ta.created_at, ta.accepted_at, ta.started_at, ta.completed_at,
                   CONCAT(u.first_name, ' ', u.last_name) as assignee_name
            FROM task_assignments ta
            JOIN users u ON ta.assigned_to = u.user_id
            WHERE ta.request_id = ? AND ta.status != 'cancelled'
            ORDER BY FIELD(ta.status,'completed','in_progress','accepted','assigned') ASC, ta.created_at DESC
        ");
        $ta_stmt->bind_param('i', $request['request_id']);
        $ta_stmt->execute();
        $all_tasks = $ta_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $task = !empty($all_tasks) ? $all_tasks[0] : null;
        $assignee_names = array_map(fn($t) => $t['assignee_name'], $all_tasks);

        $task_status  = $task['status'] ?? null;
        $is_assigned  = !empty($task);
        $is_accepted  = in_array($task_status, ['accepted', 'in_progress', 'completed']);
        $is_working   = in_array($task_status, ['in_progress', 'completed']);
        $is_completed = ($request['status'] == 'completed') || ($task_status == 'completed');
        $is_rejected  = ($request['status'] == 'rejected');
        $is_cancelled = ($request['status'] == 'cancelled');

        $timeline[] = ['label'=>'รับเรื่อง',         'icon'=>'fa-inbox',        'desc'=>'ระบบได้รับคำขอแล้ว',                                             'time'=>$request['created_at'],           'done'=>true,         'bad'=>false];
        $assignee_list_print = $is_assigned ? 'มอบหมายให้ '.implode(', ', array_map('htmlspecialchars', $assignee_names)) : 'รอมอบหมาย';
        $timeline[] = ['label'=>'มอบหมายงาน',        'icon'=>'fa-user-tag',     'desc'=>$assignee_list_print, 'time'=>$is_assigned?$task['created_at']:null, 'done'=>$is_assigned, 'bad'=>false];
        $timeline[] = ['label'=>'รับงาน',            'icon'=>'fa-hand-paper',   'desc'=>$is_accepted ? 'เจ้าหน้าที่รับงานแล้ว' : 'รอเจ้าหน้าที่รับงาน',   'time'=>$is_accepted?$task['accepted_at']:null,'done'=>$is_accepted,'bad'=>false];
        $timeline[] = ['label'=>'กำลังดำเนินการ',    'icon'=>'fa-cog',          'desc'=>$is_working  ? 'เจ้าหน้าที่กำลังดำเนินการ' : 'รอดำเนินการ',       'time'=>$is_working?$task['started_at']:null,  'done'=>$is_working, 'bad'=>false];

        if ($is_rejected) {
            $timeline[] = ['label'=>'ถูกปฏิเสธ', 'icon'=>'fa-times-circle', 'desc'=>htmlspecialchars($request['rejection_reason']??''), 'time'=>$request['updated_at'], 'done'=>true, 'bad'=>true];
        } elseif ($is_cancelled) {
            $timeline[] = ['label'=>'ยกเลิก',    'icon'=>'fa-ban',          'desc'=>'ผู้ใช้ยกเลิกคำขอ', 'time'=>$request['cancelled_at'], 'done'=>true, 'bad'=>true];
        } else {
            $timeline[] = ['label'=>'เสร็จสิ้น', 'icon'=>'fa-check-circle', 'desc'=>$is_completed?($request['completion_notes']??'ดำเนินการเรียบร้อย'):'รอดำเนินการให้เสร็จ', 'time'=>$is_completed?($request['completed_at']??$task['completed_at']):null, 'done'=>$is_completed, 'bad'=>false];
        }
    }
}

if (!$request) {
    header('Location: tracking.php' . ($request_code ? '?req='.urlencode($request_code) : ''));
    exit;
}

$status_map = [
    'pending'     => ['label'=>'รอดำเนินการ',    'bg'=>'#f59e0b'],
    'in_progress' => ['label'=>'กำลังดำเนินการ', 'bg'=>'#3b82f6'],
    'completed'   => ['label'=>'เสร็จสิ้น',      'bg'=>'#10b981'],
    'rejected'    => ['label'=>'ถูกปฏิเสธ',      'bg'=>'#ef4444'],
    'cancelled'   => ['label'=>'ยกเลิก',         'bg'=>'#6b7280'],
];
$sc = $status_map[$request['status']] ?? $status_map['pending'];

function fmt_date($dt) {
    if (!$dt) return '-';
    $ts = strtotime($dt);
    $months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    return date('j', $ts).' '.$months[(int)date('n',$ts)].' '.((int)date('Y',$ts)+543).' '.date('H:i',$ts).' น.';
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ใบติดตามสถานะ <?= htmlspecialchars($request_code) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Sarabun', sans-serif;
            background: #f3f4f6;
            color: #1f2937;
            font-size: 14px;
            line-height: 1.6;
        }

        /* ─── Screen wrapper ─────────────────────────────────────── */
        .page-wrapper {
            max-width: 794px; /* A4 width at 96dpi */
            margin: 24px auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 24px rgba(0,0,0,.12);
            overflow: hidden;
        }

        /* ─── Top bar ────────────────────────────────────────────── */
        .top-bar {
            background: linear-gradient(135deg, #0f766e 0%, #0d9488 60%, #14b8a6 100%);
            color: #fff;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
        }
        .top-bar .logo-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
        }
        .top-bar .logo-row img { width: 48px; height: 48px; border-radius: 50%; background: #fff; padding: 2px; }
        .top-bar .org-name { font-size: 13px; opacity: .85; line-height: 1.4; }
        .top-bar .org-name strong { font-size: 15px; display: block; }
        .top-bar h1 { font-size: 22px; font-weight: 800; letter-spacing: .3px; }
        .top-bar .meta { font-size: 12px; opacity: .8; margin-top: 4px; }

        .status-badge {
            display: inline-block;
            padding: 4px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-top: 8px;
            background: <?= $sc['bg'] ?>;
            color: #fff;
        }

        .qr-box { background: #fff; border-radius: 8px; padding: 6px; flex-shrink: 0; }

        /* ─── Content ────────────────────────────────────────────── */
        .content { padding: 28px 32px; }

        /* Request code pill */
        .req-code {
            display: inline-block;
            background: #f0fdf4;
            border: 1.5px solid #6ee7b7;
            color: #065f46;
            font-family: monospace;
            font-size: 15px;
            font-weight: 700;
            padding: 4px 14px;
            border-radius: 6px;
            margin-bottom: 4px;
        }

        /* ─── Info grid ──────────────────────────────────────────── */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px 24px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 24px;
        }
        .info-item .label { font-size: 11px; font-weight: 600; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
        .info-item .value { font-size: 13.5px; font-weight: 500; color: #111827; }

        /* ─── Section title ──────────────────────────────────────── */
        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0f766e;
            border-bottom: 2px solid #ccfbf1;
            padding-bottom: 6px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ─── Timeline ───────────────────────────────────────────── */
        .timeline { position: relative; padding-left: 36px; margin-bottom: 28px; }
        .timeline::before {
            content: '';
            position: absolute;
            left: 11px;
            top: 12px;
            bottom: 12px;
            width: 2px;
            background: #d1d5db;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .tl-step { position: relative; padding-bottom: 20px; }
        .tl-step:last-child { padding-bottom: 0; }
        .tl-dot {
            position: absolute;
            left: -36px;
            top: 2px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            border: 3px solid #d1d5db;
            background: #fff;
            color: #9ca3af;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .tl-dot.done     { background: #0d9488 !important; border-color: #0d9488 !important; color: #fff !important; }
        .tl-dot.done.bad { background: #ef4444 !important; border-color: #ef4444 !important; }
        .tl-dot.pending  { background: #f3f4f6 !important; border-color: #d1d5db !important; color: #9ca3af !important; }
        .tl-label { font-weight: 700; font-size: 14px; color: #111827; }
        .tl-label.pending { color: #9ca3af; }
        .tl-desc  { font-size: 12.5px; color: #6b7280; margin-top: 1px; }
        .tl-time  { font-size: 11px; color: #9ca3af; margin-top: 2px; }

        /* ─── Description box ────────────────────────────────────── */
        .desc-box {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 14px 18px;
            font-size: 13px;
            color: #374151;
            white-space: pre-line;
            margin-bottom: 24px;
        }

        /* ─── Rejection box ──────────────────────────────────────── */
        .reject-box {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 8px;
            padding: 12px 16px;
            color: #991b1b;
            font-size: 13px;
            margin-bottom: 24px;
        }

        /* ─── Footer ─────────────────────────────────────────────── */
        .doc-footer {
            border-top: 1px solid #e5e7eb;
            padding: 14px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
            color: #9ca3af;
        }

        /* ─── No-print action bar ─────────────────────────────────── */
        .action-bar {
            display: flex;
            gap: 10px;
            justify-content: center;
            padding: 20px;
            background: #f3f4f6;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 24px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            text-decoration: none;
        }
        .btn-print  { background: #0d9488; color: #fff; }
        .btn-print:hover { background: #0f766e; }
        .btn-close  { background: #e5e7eb; color: #374151; }
        .btn-close:hover { background: #d1d5db; }

        /* ─── Print overrides ────────────────────────────────────── */
        @page { size: A4; margin: 1.2cm 1.5cm; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { background: #fff !important; }
            .page-wrapper { margin: 0; box-shadow: none; border-radius: 0; }
            .action-bar { display: none !important; }
            .top-bar { background: linear-gradient(135deg, #0f766e 0%, #0d9488 60%, #14b8a6 100%) !important; }
            .status-badge { background: <?= $sc['bg'] ?> !important; }
            .tl-dot.done     { background: #0d9488 !important; border-color: #0d9488 !important; color: #fff !important; }
            .tl-dot.done.bad { background: #ef4444 !important; border-color: #ef4444 !important; }
            .tl-dot.pending  { background: #f3f4f6 !important; border-color: #d1d5db !important; }
            .timeline::before { background: #d1d5db !important; }
            a { color: inherit !important; text-decoration: none !important; }
        }
    </style>
</head>
<body>

<div class="action-bar no-print">
    <button class="btn btn-print" onclick="window.print()">
        <i class="fas fa-print"></i> พิมพ์ / บันทึก PDF
    </button>
    <a class="btn btn-close" href="tracking.php?req=<?= urlencode($request_code) ?>">
        <i class="fas fa-times"></i> ปิด
    </a>
</div>

<div class="page-wrapper">

    <!-- ── Top bar ─────────────────────────────────────────────────── -->
    <div class="top-bar">
        <div>
            <div class="logo-row">
                <img src="public/assets/images/logo/rangsit-small-logo.png" alt="Logo" onerror="this.style.display='none'">
                <div class="org-name">
                    <strong>เทศบาลนครรังสิต</strong>
                    ฝ่ายบริการและเผยแพร่วิชาการ · กองยุทธศาสตร์และงบประมาณ
                </div>
            </div>
            <h1><i class="fas fa-file-alt" style="font-size:18px;margin-right:8px;opacity:.8"></i>ใบติดตามสถานะคำร้อง</h1>
            <p class="meta">พิมพ์เมื่อ <?= fmt_date(date('Y-m-d H:i:s')) ?></p>
            <div class="status-badge"><?= $sc['label'] ?></div>
        </div>
        <div class="qr-box" id="qrcode-print"></div>
    </div>

    <!-- ── Content ─────────────────────────────────────────────────── -->
    <div class="content">

        <!-- Request info -->
        <div style="margin-bottom:20px;">
            <div class="req-code"><?= htmlspecialchars($request['request_code']) ?></div>
            <div style="font-size:22px;font-weight:800;color:#0f766e;margin:8px 0 4px;display:flex;align-items:center;gap:8px;">
                <i class="<?= htmlspecialchars($request['icon']??'fas fa-cog') ?>" style="font-size:20px;opacity:.85;"></i>
                <?= htmlspecialchars($request['master_service_name']) ?>
            </div>
            <div style="font-size:12.5px;color:#9ca3af;">
                <i class="fas fa-calendar-alt" style="margin-right:4px;"></i>
                ส่งเมื่อ <?= fmt_date($request['created_at']) ?>
            </div>
        </div>

        <!-- Info grid -->
        <div class="info-grid">
            <div class="info-item">
                <div class="label">ชื่อ-สกุลผู้ขอ</div>
                <div class="value"><?= htmlspecialchars($request['requester_name']) ?></div>
            </div>
            <div class="info-item">
                <div class="label">หน่วยงาน</div>
                <div class="value"><?= htmlspecialchars($request['department_name'] ?? '-') ?></div>
            </div>
            <div class="info-item">
                <div class="label">อีเมล</div>
                <div class="value"><?= htmlspecialchars($request['requester_email']) ?></div>
            </div>
            <div class="info-item">
                <div class="label">เบอร์โทร</div>
                <div class="value"><?= htmlspecialchars($request['requester_phone'] ?? '-') ?></div>
            </div>
        </div>

        <!-- Timeline -->
        <div class="section-title"><i class="fas fa-stream"></i> ความคืบหน้า</div>
        <div class="timeline">
            <?php foreach ($timeline as $step): ?>
            <div class="tl-step">
                <div class="tl-dot <?= $step['done'] ? 'done'.($step['bad']?' bad':'') : 'pending' ?>">
                    <i class="fas <?= $step['icon'] ?>"></i>
                </div>
                <div class="tl-label <?= $step['done'] ? '' : 'pending' ?>"><?= $step['label'] ?></div>
                <div class="tl-desc"><?= $step['desc'] ?></div>
                <?php if ($step['time']): ?>
                <div class="tl-time"><i class="far fa-clock" style="margin-right:3px;"></i><?= fmt_date($step['time']) ?></div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Description -->
        <?php if (!empty($request['description'])): ?>
        <div class="section-title"><i class="fas fa-align-left"></i> รายละเอียดคำขอ</div>
        <div class="desc-box"><?= htmlspecialchars($request['description']) ?></div>
        <?php endif; ?>

        <!-- Rejection reason -->
        <?php if (!empty($request['rejection_reason'])): ?>
        <div class="reject-box">
            <strong><i class="fas fa-exclamation-circle" style="margin-right:5px;"></i>สาเหตุที่ปฏิเสธ:</strong>
            <?= htmlspecialchars($request['rejection_reason']) ?>
        </div>
        <?php endif; ?>

        <!-- Attachments -->
        <?php
        $atts = [];
        if (!empty($request['attachments'])) $atts = json_decode($request['attachments'], true) ?: [];
        if ($atts): ?>
        <div class="section-title"><i class="fas fa-paperclip"></i> เอกสารแนบ</div>
        <ul style="margin-bottom:24px;padding-left:20px;font-size:13px;color:#0d9488;">
            <?php foreach ($atts as $att): ?>
            <li style="margin-bottom:4px;"><i class="fas fa-file-alt" style="margin-right:5px;"></i><?= htmlspecialchars(basename($att)) ?></li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>

    </div>

    <!-- ── Footer ──────────────────────────────────────────────────── -->
    <div class="doc-footer">
        <span><i class="fas fa-link" style="margin-right:4px;"></i>https://iservice.rangsitcity.go.th/tracking.php?req=<?= htmlspecialchars($request_code) ?></span>
        <span>ระบบ iService · เทศบาลนครรังสิต</span>
    </div>

</div><!-- /.page-wrapper -->

<script>
new QRCode(document.getElementById("qrcode-print"), {
    text: 'https://iservice.rangsitcity.go.th/tracking.php?req=<?= htmlspecialchars($request_code) ?>',
    width: 80,
    height: 80,
    colorDark: "#000000",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.H
});
</script>
</body>
</html>
