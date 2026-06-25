<?php
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

// ── Parameters ───────────────────────────────────────────────────────────────
$period     = in_array($_GET['period'] ?? '', ['daily','weekly','monthly','yearly'])
              ? $_GET['period'] : 'daily';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-d');
$doExport   = isset($_GET['export']) && $_GET['export'] === 'excel';

// sanitize
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))   $end_date   = date('Y-m-d');
if ($start_date > $end_date) [$start_date, $end_date] = [$end_date, $start_date];

$startTs = strtotime($start_date . ' 00:00:00');
$endTs   = strtotime($end_date   . ' 23:59:59');

// ── Sensors ──────────────────────────────────────────────────────────────────
$sensors = [];
try {
    $sensors = $pdo->query("SELECT * FROM pm25_sensors WHERE is_active=1 ORDER BY id")
                   ->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}
$cids = array_column($sensors, 'cid');

// ── Month names ───────────────────────────────────────────────────────────────
$monthsTH = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
$monthFull = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
              'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

// ── Query ─────────────────────────────────────────────────────────────────────
$columns = []; // [key => label]
$data    = []; // [cid][key] => avg_pm25

if (!empty($cids)) {
    $in     = implode(',', array_fill(0, count($cids), '?'));
    $params = array_merge($cids, [$startTs, $endTs]);
    try {
        switch ($period) {
            // ── รายวัน ────────────────────────────────────────────────────────
            case 'daily':
                $stmt = $pdo->prepare("
                    SELECT cid,
                           DATE(FROM_UNIXTIME(sensor_timestamp)) AS k,
                           AVG(pm25) AS v
                    FROM pm25_data
                    WHERE cid IN ($in) AND sensor_timestamp BETWEEN ? AND ? AND pm25 IS NOT NULL
                    GROUP BY cid, k ORDER BY k
                ");
                $stmt->execute($params);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $data[$r['cid']][$r['k']] = round((float)$r['v'], 1);
                    if (!isset($columns[$r['k']])) {
                        $t = strtotime($r['k']);
                        $columns[$r['k']] = date('j', $t) . ' ' . $monthsTH[(int)date('n', $t)];
                    }
                }
                break;

            // ── รายสัปดาห์ ────────────────────────────────────────────────────
            case 'weekly':
                $stmt = $pdo->prepare("
                    SELECT cid,
                           YEARWEEK(FROM_UNIXTIME(sensor_timestamp), 1) AS k,
                           MIN(DATE(FROM_UNIXTIME(sensor_timestamp))) AS ws,
                           MAX(DATE(FROM_UNIXTIME(sensor_timestamp))) AS we,
                           AVG(pm25) AS v
                    FROM pm25_data
                    WHERE cid IN ($in) AND sensor_timestamp BETWEEN ? AND ? AND pm25 IS NOT NULL
                    GROUP BY cid, k ORDER BY k
                ");
                $stmt->execute($params);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $k = (string)$r['k'];
                    $data[$r['cid']][$k] = round((float)$r['v'], 1);
                    if (!isset($columns[$k])) {
                        $s = strtotime($r['ws']); $e = strtotime($r['we']);
                        $columns[$k] = date('j', $s) . '–' . date('j', $e)
                                     . ' ' . $monthsTH[(int)date('n', $e)];
                    }
                }
                break;

            // ── รายเดือน ─────────────────────────────────────────────────────
            case 'monthly':
                $stmt = $pdo->prepare("
                    SELECT cid,
                           DATE_FORMAT(FROM_UNIXTIME(sensor_timestamp),'%Y-%m') AS k,
                           AVG(pm25) AS v
                    FROM pm25_data
                    WHERE cid IN ($in) AND sensor_timestamp BETWEEN ? AND ? AND pm25 IS NOT NULL
                    GROUP BY cid, k ORDER BY k
                ");
                $stmt->execute($params);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $data[$r['cid']][$r['k']] = round((float)$r['v'], 1);
                    if (!isset($columns[$r['k']])) {
                        [$y, $m] = explode('-', $r['k']);
                        $columns[$r['k']] = $monthsTH[(int)$m] . "\n" . ((int)$y + 543);
                    }
                }
                break;

            // ── รายปี ─────────────────────────────────────────────────────────
            case 'yearly':
                $stmt = $pdo->prepare("
                    SELECT cid,
                           YEAR(FROM_UNIXTIME(sensor_timestamp)) AS k,
                           AVG(pm25) AS v
                    FROM pm25_data
                    WHERE cid IN ($in) AND sensor_timestamp BETWEEN ? AND ? AND pm25 IS NOT NULL
                    GROUP BY cid, k ORDER BY k
                ");
                $stmt->execute($params);
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                    $k = (string)$r['k'];
                    $data[$r['cid']][$k] = round((float)$r['v'], 1);
                    if (!isset($columns[$k])) $columns[$k] = 'ปี ' . ((int)$k + 543);
                }
                break;
        }
    } catch (Exception $e) {}
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function pmLevel($v): array {
    if ($v === null) return ['hex'=>'#94a3b8','text'=>'#fff','label'=>'ไม่มีข้อมูล'];
    $v = (float)$v;
    if ($v <= 15.0) return ['hex'=>'#3BCCFF','text'=>'#0c4a6e','label'=>'ดีมาก'];
    if ($v <= 25.0) return ['hex'=>'#92D050','text'=>'#14532d','label'=>'ดี'];
    if ($v <= 37.5) return ['hex'=>'#FFFF00','text'=>'#713f12','label'=>'ปานกลาง'];
    if ($v <= 75.0) return ['hex'=>'#FFA200','text'=>'#ffffff','label'=>'เริ่มมีผลกระทบ'];
    return                 ['hex'=>'#F04646','text'=>'#ffffff','label'=>'มีผลกระทบต่อสุขภาพ'];
}

// ── Summary stats ─────────────────────────────────────────────────────────────
$allVals = [];
foreach ($data as $cid => $cols) foreach ($cols as $v) $allVals[] = $v;
$overallAvg = $allVals ? round(array_sum($allVals)/count($allVals),1) : null;
$overallMax = $allVals ? max($allVals) : null;
$overallMin = $allVals ? min($allVals) : null;
$exceedCnt  = count(array_filter($allVals, fn($v) => $v > 37.5));

// ── shared Excel styles & credit helper ──────────────────────────────────────
function xlHeader(): string {
    echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel">';
    echo '<head><meta charset="UTF-8"></head><body>';
    return '';
}
function xlCredit(int $cols): void {
    $s = 'font-family:Tahoma,sans-serif;font-size:9pt;font-weight:bold;background:#0f766e;color:white;padding:5px 10px;border:1px solid #0d9488;';
    echo '<tr><td colspan="'.$cols.'" style="'.$s.'">Rangsit Smart City (RSSC) - Open data &nbsp;·&nbsp; พัฒนาโดย : งานสถิติและข้อมูลสารสนเทศ &nbsp;ฝ่ายบริการและเผยแพร่วิชาการ &nbsp;กองยุทธศาสตร์และงบประมาณ</td></tr>';
}
function xlFooter(int $cols): void {
    $s = 'font-family:Tahoma,sans-serif;font-size:9pt;font-weight:bold;background:#0f766e;color:white;padding:5px 10px;border:1px solid #0d9488;text-align:center;';
    echo '<tr><td colspan="'.$cols.'" style="'.$s.'">ติดตั้งและตรวจสอบความถูกต้องโดย : กองสาธารณสุขและสิ่งแวดล้อม เทศบาลนครรังสิต</td></tr>';
}

// ── Excel Export: ตารางผลการค้นหา ────────────────────────────────────────────
if ($doExport) {
    $periodLabel = ['daily'=>'รายวัน','weekly'=>'รายสัปดาห์','monthly'=>'รายเดือน','yearly'=>'รายปี'][$period];
    $filename = 'PM25_' . $periodLabel . '_' . str_replace('-','',$start_date) . '_' . str_replace('-','',$end_date) . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF";

    $th = 'font-family:Tahoma,sans-serif;font-size:10pt;font-weight:bold;background:#1e293b;color:white;border:1px solid #475569;padding:4px 8px;text-align:center;';
    $td = 'font-family:Tahoma,sans-serif;font-size:10pt;border:1px solid #e2e8f0;padding:3px 6px;';
    $totalCols = count($columns) + 4;

    xlHeader();
    echo '<table>';
    // ── Credit header ─────────────────────────────────────────────────────────
    xlCredit($totalCols);
    // ── Title ─────────────────────────────────────────────────────────────────
    echo '<tr><td colspan="'.$totalCols.'" style="font-family:Tahoma;font-size:13pt;font-weight:bold;background:#134e4a;color:white;padding:7px 12px">';
    echo 'รายงาน PM2.5 '.$periodLabel.' ('.$start_date.' ถึง '.$end_date.')</td></tr>';
    echo '<tr><td colspan="'.$totalCols.'" style="font-family:Tahoma;font-size:9pt;background:#1e3a33;color:#99f6e4;padding:4px 12px">';
    echo 'ออกรายงาน: '.date('d/m/').(date('Y')+543).' '.date('H:i').' น.  ·  เทศบาลนครรังสิต  ·  '.count($sensors).' สถานี';
    echo '</td></tr>';
    echo '<tr><td colspan="'.$totalCols.'"></td></tr>';
    // ── Column headers ────────────────────────────────────────────────────────
    echo '<tr><th style="'.$th.'text-align:left;min-width:200px">สถานี</th>';
    foreach ($columns as $k => $label) {
        echo '<th style="'.$th.'min-width:55px">'.str_replace("\n",' ',$label).'</th>';
    }
    echo '<th style="'.$th.'background:#334155">เฉลี่ย</th>';
    echo '<th style="'.$th.'background:#334155">สูงสุด</th>';
    echo '<th style="'.$th.'background:#334155">ต่ำสุด</th>';
    echo '</tr>';
    // ── Data rows ─────────────────────────────────────────────────────────────
    foreach ($sensors as $idx => $s) {
        $cid  = $s['cid'];
        $row  = $data[$cid] ?? [];
        $vals = array_values($row);
        $rAvg = $vals ? round(array_sum($vals)/count($vals),1) : null;
        $rMax = $vals ? max($vals) : null;
        $rMin = $vals ? min($vals) : null;
        $bg   = $idx % 2 ? 'background:#f8fafc;' : '';
        echo '<tr>';
        echo '<td style="'.$td.$bg.'font-weight:bold">' . htmlspecialchars($s['location_name']) . '</td>';
        foreach ($columns as $k => $label) {
            $v  = $row[$k] ?? null;
            $lv = pmLevel($v);
            if ($v !== null)
                echo '<td style="'.$td.'background:'.$lv['hex'].';color:'.$lv['text'].';text-align:center;font-weight:bold">'.$v.'</td>';
            else
                echo '<td style="'.$td.$bg.'text-align:center;color:#cbd5e1">–</td>';
        }
        $la = pmLevel($rAvg); $lx = pmLevel($rMax); $ln = pmLevel($rMin);
        echo '<td style="'.$td.'text-align:center;font-weight:bold;color:'.$la['hex'].'">' . ($rAvg ?? '–') . '</td>';
        echo '<td style="'.$td.'text-align:center;font-weight:bold;color:'.$lx['hex'].'">' . ($rMax ?? '–') . '</td>';
        echo '<td style="'.$td.'text-align:center;font-weight:bold;color:'.$ln['hex'].'">' . ($rMin ?? '–') . '</td>';
        echo '</tr>';
    }
    // ── Credit footer ─────────────────────────────────────────────────────────
    echo '<tr><td colspan="'.$totalCols.'"></td></tr>';
    xlFooter($totalCols);
    echo '</table></body></html>';
    exit;
}

// ── Excel Export: ตารางรายปี ──────────────────────────────────────────────────
if (isset($_GET['export']) && $_GET['export'] === 'excel_yr') {
    $xyr_year = (int)($_GET['yr_year'] ?? date('Y'));
    $xyr_cid  = $_GET['yr_cid'] ?? 'all';
    $xyr_isAll = ($xyr_cid === 'all');

    // ดึงชื่อสถานี
    $xyr_name = 'ค่าเฉลี่ยทั้งหมด ' . count($sensors) . ' สถานี';
    if (!$xyr_isAll) {
        foreach ($sensors as $s) {
            if ($s['cid'] === $xyr_cid) { $xyr_name = $s['location_name']; break; }
        }
    }

    // ดึงข้อมูล daily summary
    $xyrData = [];
    try {
        if ($xyr_isAll) {
            $st = $pdo->prepare("
                SELECT MONTH(summary_date) AS m, DAY(summary_date) AS d,
                       ROUND(AVG(avg_pm25),1) AS v
                FROM pm25_daily_summary
                WHERE YEAR(summary_date) = ? AND avg_pm25 IS NOT NULL
                GROUP BY m, d ORDER BY m, d
            ");
            $st->execute([$xyr_year]);
        } else {
            $st = $pdo->prepare("
                SELECT MONTH(summary_date) AS m, DAY(summary_date) AS d,
                       ROUND(avg_pm25,1) AS v
                FROM pm25_daily_summary
                WHERE cid = ? AND YEAR(summary_date) = ? AND avg_pm25 IS NOT NULL
                ORDER BY summary_date
            ");
            $st->execute([$xyr_cid, $xyr_year]);
        }
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $xyrData[(int)$r['m']][(int)$r['d']] = (float)$r['v'];
        }
    } catch (Exception $e) {}

    $monthFullTHx = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                     'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

    $filename = 'PM25_รายปี_' . ($xyr_year+543) . '_' . ($xyr_isAll ? 'ทุกสถานี' : $xyr_cid) . '.xls';
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: max-age=0');
    echo "\xEF\xBB\xBF";

    $th  = 'font-family:Tahoma,sans-serif;font-size:9pt;font-weight:bold;background:#1e293b;color:white;border:1px solid #475569;padding:3px 5px;text-align:center;';
    $thM = 'font-family:Tahoma,sans-serif;font-size:9pt;font-weight:bold;background:#1e293b;color:white;border:1px solid #475569;padding:3px 10px;text-align:left;';
    $td  = 'font-family:Tahoma,sans-serif;font-size:9pt;border:1px solid #e2e8f0;padding:2px 4px;text-align:center;';
    $totalCols = 35; // month + 31 days + avg + max

    xlHeader();
    echo '<table>';
    xlCredit($totalCols);
    echo '<tr><td colspan="'.$totalCols.'" style="font-family:Tahoma;font-size:13pt;font-weight:bold;background:#134e4a;color:white;padding:7px 12px">';
    echo 'รายงานค่าเฉลี่ย PM2.5 รายวัน ปี ' . ($xyr_year+543) . ' — ' . $xyr_name;
    echo '</td></tr>';
    echo '<tr><td colspan="'.$totalCols.'" style="font-family:Tahoma;font-size:9pt;background:#1e3a33;color:#99f6e4;padding:4px 12px">';
    echo 'ออกรายงาน: '.date('d/m/').(date('Y')+543).' '.date('H:i').' น.  ·  เทศบาลนครรังสิต';
    echo '</td></tr>';
    echo '<tr><td colspan="'.$totalCols.'"></td></tr>';

    // Column headers
    echo '<tr><th style="'.$thM.'min-width:80px">เดือน</th>';
    for ($d = 1; $d <= 31; $d++) echo '<th style="'.$th.'min-width:30px">'.$d.'</th>';
    echo '<th style="'.$th.'background:#334155">เฉลี่ย</th>';
    echo '<th style="'.$th.'background:#334155">สูงสุด</th>';
    echo '</tr>';

    $yearVals = [];
    for ($m = 1; $m <= 12; $m++) {
        $dim = cal_days_in_month(CAL_GREGORIAN, $m, $xyr_year);
        $isFuture = ($xyr_year > (int)date('Y')) || ($xyr_year == (int)date('Y') && $m > (int)date('n'));
        if ($isFuture) continue;

        $monthVals = [];
        echo '<tr>';
        echo '<td style="'.$thM.'">' . $monthFullTHx[$m] . '</td>';
        for ($d = 1; $d <= 31; $d++) {
            if ($d > $dim) { echo '<td style="'.$td.'background:#f8fafc"></td>'; continue; }
            $cellDate = sprintf('%04d-%02d-%02d', $xyr_year, $m, $d);
            if ($cellDate > date('Y-m-d')) { echo '<td style="'.$td.'background:#f8fafc"></td>'; continue; }
            $v  = $xyrData[$m][$d] ?? null;
            $lv = pmLevel($v);
            if ($v !== null) {
                $monthVals[] = $v;
                $ul = $v > 37.5 ? 'text-decoration:underline;' : '';
                echo '<td style="'.$td.'background:'.$lv['hex'].';color:'.$lv['text'].';font-weight:bold;'.$ul.'">'.$v.'</td>';
            } else {
                echo '<td style="'.$td.'color:#cbd5e1">–</td>';
            }
        }
        $mAvg = $monthVals ? round(array_sum($monthVals)/count($monthVals),1) : null;
        $mMax = $monthVals ? max($monthVals) : null;
        if ($mAvg !== null) $yearVals[] = $mAvg;
        $lvA = pmLevel($mAvg); $lvX = pmLevel($mMax);
        echo '<td style="'.$td.'font-weight:bold;color:'.$lvA['hex'].'">' . ($mAvg ?? '–') . '</td>';
        echo '<td style="'.$td.'font-weight:bold;color:'.$lvX['hex'].'">' . ($mMax ?? '–') . '</td>';
        echo '</tr>';
    }
    // Year avg row
    $yAvg = $yearVals ? round(array_sum($yearVals)/count($yearVals),1) : null;
    $lvY  = pmLevel($yAvg);
    echo '<tr>';
    echo '<td style="'.$thM.'background:#334155">เฉลี่ยทั้งปี</td>';
    for ($d = 1; $d <= 33; $d++) echo '<td style="'.$td.'background:#f8fafc"></td>';
    echo '<td style="'.$td.'font-weight:bold;font-size:11pt;color:'.$lvY['hex'].'">' . ($yAvg ?? '–') . '</td>';
    echo '<td></td></tr>';

    echo '<tr><td colspan="'.$totalCols.'"></td></tr>';
    xlFooter($totalCols);
    echo '</table></body></html>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน PM2.5 — เทศบาลนครรังสิต</title>
    <link rel="icon" type="image/png" href="public/assets/images/logo/rangsit-small-logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Kanit', sans-serif; }
        input[type="date"] { font-family: 'Kanit', sans-serif; }
        .period-btn { transition: all .15s; }
        .period-btn.active { background:#0f172a; color:#fff; border-color:#0f172a; }
        .tbl-cell { min-width: 52px; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<!-- ── Header ── -->
<header class="bg-gradient-to-r from-teal-800 to-teal-600 text-white shadow-lg sticky top-0 z-20">
    <div class="max-w-screen-xl mx-auto px-4 py-3 flex items-center gap-4">
        <img src="public/assets/images/logo/rangsit-small-logo.png" alt="logo"
             class="h-12 w-auto rounded" onerror="this.style.display='none'">
        <div>
            <h1 class="text-lg font-bold leading-tight">รายงานคุณภาพอากาศ PM2.5</h1>
            <p class="text-teal-200 text-xs">เทศบาลนครรังสิต</p>
        </div>
        <div class="ml-auto flex items-center gap-2">
            <a href="pm25_dashboard.php"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white/15 hover:bg-white/25 transition-colors">
                <i class="fas fa-tachometer-alt"></i>
                <span class="hidden sm:inline">Dashboard</span>
            </a>
            <a href="index.php"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-teal-700 hover:bg-teal-50 transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline">หน้าหลัก</span>
            </a>
        </div>
    </div>
</header>

<div class="max-w-screen-xl mx-auto px-4 py-6">

    <!-- ── Search Panel ── -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
        <form method="GET" id="searchForm">
            <!-- Period Buttons -->
            <div class="flex flex-wrap gap-2 mb-5">
                <span class="text-sm font-semibold text-slate-600 self-center mr-1">ช่วงเวลา :</span>
                <?php foreach ([
                    ['daily',   'fa-calendar-day',   'รายวัน'],
                    ['weekly',  'fa-calendar-week',  'รายสัปดาห์'],
                    ['monthly', 'fa-calendar-alt',   'รายเดือน'],
                    ['yearly',  'fa-calendar',       'รายปี'],
                ] as [$p, $icon, $label]): ?>
                <button type="button"
                        onclick="setPeriod('<?= $p ?>')"
                        id="btn_<?= $p ?>"
                        class="period-btn flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium border border-slate-200 bg-white text-slate-600 hover:border-teal-400 hover:text-teal-700
                               <?= $period === $p ? 'active' : '' ?>">
                    <i class="fas <?= $icon ?> text-xs"></i><?= $label ?>
                </button>
                <?php endforeach; ?>
                <input type="hidden" name="period" id="periodInput" value="<?= htmlspecialchars($period) ?>">
            </div>

            <!-- Date Range -->
            <div class="flex flex-wrap items-end gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-slate-500 font-medium">วันที่เริ่มต้น</label>
                    <input type="date" name="start_date" id="start_date"
                           value="<?= htmlspecialchars($start_date) ?>"
                           class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-teal-400">
                </div>
                <div class="flex items-center pb-2 text-slate-400 font-bold text-lg">—</div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs text-slate-500 font-medium">วันที่สิ้นสุด</label>
                    <input type="date" name="end_date" id="end_date"
                           value="<?= htmlspecialchars($end_date) ?>"
                           class="border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 focus:border-teal-400">
                </div>
                <button type="submit"
                        class="flex items-center gap-2 px-6 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm">
                    <i class="fas fa-search"></i> ค้นหา
                </button>

                <!-- Quick presets -->
                <div class="flex flex-wrap gap-1.5 ml-2">
                    <span class="text-xs text-slate-400 self-center">เลือกด่วน:</span>
                    <button type="button" onclick="setPreset('thisMonth')"
                            class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 hover:bg-teal-50 hover:border-teal-300 hover:text-teal-700 transition-colors">
                        เดือนนี้
                    </button>
                    <button type="button" onclick="setPreset('lastMonth')"
                            class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 hover:bg-teal-50 hover:border-teal-300 hover:text-teal-700 transition-colors">
                        เดือนที่แล้ว
                    </button>
                    <button type="button" onclick="setPreset('thisYear')"
                            class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 hover:bg-teal-50 hover:border-teal-300 hover:text-teal-700 transition-colors">
                        ปีนี้
                    </button>
                    <button type="button" onclick="setPreset('last7')"
                            class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 hover:bg-teal-50 hover:border-teal-300 hover:text-teal-700 transition-colors">
                        7 วันล่าสุด
                    </button>
                    <button type="button" onclick="setPreset('last30')"
                            class="px-3 py-1.5 text-xs rounded-lg border border-slate-200 bg-slate-50 hover:bg-teal-50 hover:border-teal-300 hover:text-teal-700 transition-colors">
                        30 วันล่าสุด
                    </button>
                </div>
            </div>
        </form>
    </div>

    <?php if (!empty($columns)): ?>

    <!-- ── Overview Cards ── -->
    <?php
    $lvAvg = pmLevel($overallAvg); $lvMax = pmLevel($overallMax); $lvMin = pmLevel($overallMin);
    $excPct = $allVals ? round($exceedCnt / count($allVals) * 100) : 0;
    $periodLabelTH = ['daily'=>'รายวัน','weekly'=>'รายสัปดาห์','monthly'=>'รายเดือน','yearly'=>'รายปี'][$period];
    ?>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-teal-50 flex items-center justify-center shrink-0">
                <i class="fas fa-satellite-dish text-teal-600"></i>
            </div>
            <div>
                <div class="text-xs text-slate-400">ช่วงข้อมูล</div>
                <div class="text-lg font-bold text-slate-700"><?= count($columns) ?> <?= $periodLabelTH ?></div>
                <div class="text-xs text-slate-400"><?= count($sensors) ?> สถานี</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                 style="background:<?= $lvAvg['hex'] ?>20">
                <i class="fas fa-chart-line" style="color:<?= $lvAvg['hex'] ?>"></i>
            </div>
            <div>
                <div class="text-xs text-slate-400">ค่าเฉลี่ย PM2.5</div>
                <div class="text-2xl font-bold" style="color:<?= $lvAvg['hex'] ?>"><?= $overallAvg ?? '--' ?></div>
                <div class="text-xs" style="color:<?= $lvAvg['hex'] ?>"><?= $lvAvg['label'] ?></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                 style="background:<?= $lvMax['hex'] ?>20">
                <i class="fas fa-arrow-up" style="color:<?= $lvMax['hex'] ?>"></i>
            </div>
            <div>
                <div class="text-xs text-slate-400">ค่าสูงสุด</div>
                <div class="text-2xl font-bold" style="color:<?= $lvMax['hex'] ?>"><?= $overallMax ?? '--' ?></div>
                <div class="text-xs text-slate-400">µg/m³</div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-red-50 flex items-center justify-center shrink-0">
                <i class="fas fa-exclamation-triangle text-red-500"></i>
            </div>
            <div>
                <div class="text-xs text-slate-400">เกินมาตรฐาน (>37.5)</div>
                <div class="text-2xl font-bold text-red-600"><?= $exceedCnt ?></div>
                <div class="text-xs text-slate-400">จาก <?= count($allVals) ?> รายการ (<?= $excPct ?>%)</div>
            </div>
        </div>
    </div>

    <!-- ── Result Table ── -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-4">
        <!-- Table Header -->
        <div class="flex items-center gap-3 px-5 pt-4 pb-3 border-b border-slate-100">
            <i class="fas fa-table text-teal-600"></i>
            <div>
                <h2 class="font-semibold text-slate-700">
                    ผลการค้นหา — <?= $periodLabelTH ?> (<?= date('d/m/', $startTs).(date('Y',$startTs)+543) ?> – <?= date('d/m/', $endTs).(date('Y',$endTs)+543) ?>)
                </h2>
                <p class="text-xs text-slate-400">ค่าเฉลี่ย PM2.5 (µg/m³) · <?= count($columns) ?> คอลัมน์ · <?= count($sensors) ?> สถานี</p>
            </div>
            <a href="?period=<?= $period ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>&export=excel"
               class="ml-auto flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm whitespace-nowrap">
                <i class="fas fa-file-excel"></i> Export Excel
            </a>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="border-collapse text-xs" style="min-width:100%">
                <thead>
                    <tr>
                        <th colspan="50" class="bg-teal-800 text-white text-center py-2 px-4" style="font-size:11px;letter-spacing:.02em">
                            <span class="font-bold">Rangsit Smart City (RSSC) - Open data </span>
                            &nbsp;·&nbsp; พัฒนาโดย : งานสถิติและข้อมูลสารสนเทศ &nbsp;ฝ่ายบริการและเผยแพร่วิชาการ &nbsp;กองยุทธศาสตร์และงบประมาณ
                        </th>
                    </tr>
                    <tr>
                        <th class="bg-slate-800 text-white text-left px-4 py-3 font-semibold sticky left-0 z-10 min-w-[200px] border-r border-slate-700">
                            สถานี
                        </th>
                        <?php foreach ($columns as $k => $label): ?>
                        <th class="bg-slate-800 text-slate-200 text-center py-2 px-1 font-medium tbl-cell leading-tight whitespace-pre-line">
                            <?= htmlspecialchars($label) ?>
                        </th>
                        <?php endforeach; ?>
                        <th class="bg-slate-700 text-slate-300 text-center py-3 px-3 font-semibold min-w-[52px]">เฉลี่ย</th>
                        <th class="bg-slate-700 text-slate-300 text-center py-3 px-3 font-semibold min-w-[52px]">สูงสุด</th>
                        <th class="bg-slate-700 text-slate-300 text-center py-3 px-3 font-semibold min-w-[52px]">ต่ำสุด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sensors as $idx => $s):
                        $cid  = $s['cid'];
                        $row  = $data[$cid] ?? [];
                        $vals = array_values($row);
                        $rAvg = $vals ? round(array_sum($vals)/count($vals),1) : null;
                        $rMax = $vals ? max($vals) : null;
                        $rMin = $vals ? min($vals) : null;
                        $la = pmLevel($rAvg); $lx = pmLevel($rMax); $ln = pmLevel($rMin);
                        $rowBg = $idx % 2 ? 'bg-slate-50/60' : '';
                    ?>
                    <tr class="border-t border-slate-100 <?= $rowBg ?>">
                        <td class="px-4 py-2.5 font-semibold text-slate-700 whitespace-nowrap sticky left-0 <?= $rowBg ?: 'bg-white' ?> border-r border-slate-100 z-10">
                            <span class="text-slate-400 text-[10px] mr-1"><?= $idx+1 ?>.</span>
                            <?= htmlspecialchars($s['location_name']) ?>
                        </td>
                        <?php foreach ($columns as $k => $label):
                            $v  = $row[$k] ?? null;
                            $lv = pmLevel($v);
                        ?>
                        <td class="text-center py-2 px-0 font-bold leading-none tbl-cell"
                            title="<?= htmlspecialchars($label) ?>: <?= $v !== null ? $v.' µg/m³' : 'ไม่มีข้อมูล' ?>"
                            style="<?= $v !== null ? 'background:'.$lv['hex'].';color:'.$lv['text'] : '' ?>">
                            <?= $v !== null ? $v : '<span class="text-slate-200 font-normal">–</span>' ?>
                        </td>
                        <?php endforeach; ?>
                        <td class="text-center py-2 px-2 font-bold" style="color:<?= $la['hex'] ?>">
                            <?= $rAvg ?? '–' ?>
                        </td>
                        <td class="text-center py-2 px-2 font-bold" style="color:<?= $lx['hex'] ?>">
                            <?= $rMax ?? '–' ?>
                        </td>
                        <td class="text-center py-2 px-2 font-bold" style="color:<?= $ln['hex'] ?>">
                            <?= $rMin ?? '–' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="50" class="text-center py-2 px-4 text-white" style="background:#0f766e;font-size:11px">
                            ติดตั้งและตรวจสอบความถูกต้องโดย : <strong>กองสาธารณสุขและสิ่งแวดล้อม เทศบาลนครรังสิต</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap gap-x-4 gap-y-1 px-5 py-2.5 border-t border-slate-100 text-[11px] text-slate-500">
            <?php foreach ([
                ['#3BCCFF','#0c4a6e','0–15.0 ดีมาก'],
                ['#92D050','#14532d','15.1–25.0 ดี'],
                ['#FFFF00','#713f12','25.1–37.5 ปานกลาง'],
                ['#FFA200','#ffffff','37.6–75.0 เริ่มมีผลกระทบ'],
                ['#F04646','#ffffff','≥75.1 มีผลกระทบ'],
            ] as [$c, $tc, $l]): ?>
            <span class="flex items-center gap-1">
                <span class="w-3 h-3 rounded-sm inline-block border border-slate-200" style="background:<?= $c ?>"></span>
                <span style="color:<?= in_array($tc,['#fff','#ffffff']) ? $c : $tc ?>;font-weight:500"><?= $l ?></span>
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <?php elseif (!empty($sensors)): ?>
    <!-- No data state -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-16 text-center">
        <i class="fas fa-database text-4xl text-slate-200 mb-4 block"></i>
        <p class="text-slate-500 font-medium">ไม่พบข้อมูลในช่วงเวลาที่เลือก</p>
        <p class="text-slate-400 text-sm mt-1"><?= $start_date ?> ถึง <?= $end_date ?></p>
    </div>
    <?php endif; ?>

</div><!-- /max-w search section -->

<!-- ═══════════════════════════════════════════════════════════════════════════
     SECTION: รายงานรายปี (อ่านจาก pm25_daily_summary)
══════════════════════════════════════════════════════════════════════════════ -->
<?php
// ── yearly section params ─────────────────────────────────────────────────────
$yr_year    = (int)($_GET['yr_year'] ?? date('Y'));
$yr_cid     = $_GET['yr_cid'] ?? 'all';   // default = ค่าเฉลี่ยทั้งหมด
$yr_isAll   = ($yr_cid === 'all');
$yr_station = null;
if (!$yr_isAll) {
    foreach ($sensors as $s) { if ($s['cid'] === $yr_cid) { $yr_station = $s; break; } }
    if (!$yr_station) { $yr_cid = 'all'; $yr_isAll = true; }
}

// ── ดึงข้อมูลจาก pm25_daily_summary ─────────────────────────────────────────
$yrData     = []; // [month][day] => avg_pm25
$hasSummary = false;
try {
    $pdo->query("SELECT 1 FROM pm25_daily_summary LIMIT 1");
    $hasSummary = true;

    if ($yr_isAll) {
        // เฉลี่ยทุกสถานีในแต่ละวัน
        $stmt = $pdo->prepare("
            SELECT DAY(summary_date) AS d, MONTH(summary_date) AS m,
                   ROUND(AVG(avg_pm25), 1) AS avg_pm25
            FROM pm25_daily_summary
            WHERE YEAR(summary_date) = ?
              AND avg_pm25 IS NOT NULL
            GROUP BY m, d
            ORDER BY summary_date ASC
        ");
        $stmt->execute([$yr_year]);
    } else {
        $stmt = $pdo->prepare("
            SELECT DAY(summary_date) AS d, MONTH(summary_date) AS m, avg_pm25
            FROM pm25_daily_summary
            WHERE cid = ? AND YEAR(summary_date) = ?
            ORDER BY summary_date ASC
        ");
        $stmt->execute([$yr_cid, $yr_year]);
    }
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $yrData[(int)$r['m']][(int)$r['d']] = (float)$r['avg_pm25'];
    }
} catch (Exception $e) { $hasSummary = false; }

// ── available years ───────────────────────────────────────────────────────────
$availYears = [];
if ($hasSummary) {
    try {
        $availYears = $pdo->query("SELECT DISTINCT YEAR(summary_date) AS y FROM pm25_daily_summary ORDER BY y DESC")
                         ->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {}
}
if (empty($availYears)) $availYears = [date('Y')];

$monthFullTH2 = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                 'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

function pmLevelYr($v): array {
    if ($v === null) return ['hex'=>'','text'=>'#94a3b8','label'=>'–'];
    $v = (float)$v;
    if ($v <= 15.0) return ['hex'=>'#3BCCFF','text'=>'#0c4a6e','label'=>'ดีมาก'];
    if ($v <= 25.0) return ['hex'=>'#92D050','text'=>'#14532d','label'=>'ดี'];
    if ($v <= 37.5) return ['hex'=>'#FFFF00','text'=>'#713f12','label'=>'ปานกลาง'];
    if ($v <= 75.0) return ['hex'=>'#FFA200','text'=>'#ffffff','label'=>'เริ่มมีผลกระทบ'];
    return                 ['hex'=>'#F04646','text'=>'#ffffff','label'=>'มีผลกระทบ'];
}
?>
<a id="yearly"></a>
<div class="max-w-screen-xl mx-auto px-4 pb-10">

    <!-- header -->
    <div class="flex items-center gap-3 mb-5 mt-2">
        <div class="w-1 h-8 bg-teal-500 rounded-full"></div>
        <h2 class="text-lg font-bold text-slate-700">รายงานรายปี — ค่าเฉลี่ย PM2.5 รายวัน</h2>
    </div>

    <?php if (!$hasSummary): ?>
    <!-- ยังไม่มีตาราง / ข้อมูล -->
    <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6 text-center">
        <i class="fas fa-database text-amber-400 text-3xl mb-3 block"></i>
        <p class="font-semibold text-amber-800 mb-1">ยังไม่มีข้อมูล Daily Summary</p>
        <p class="text-amber-600 text-sm mb-4">กดปุ่มด้านล่างเพื่อสร้างและประมวลผลข้อมูลครั้งแรก (ใช้เวลาสักครู่)</p>
        <a href="pm25_summary_update.php" target="_blank"
           class="inline-flex items-center gap-2 px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition-colors">
            <i class="fas fa-sync-alt"></i> สร้างข้อมูล Daily Summary
        </a>
    </div>

    <?php else: ?>

    <!-- Controls -->
    <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 mb-5 flex flex-wrap items-end gap-3">
        <!-- keep search params -->
        <input type="hidden" name="period"     value="<?= htmlspecialchars($period) ?>">
        <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
        <input type="hidden" name="end_date"   value="<?= htmlspecialchars($end_date) ?>">

        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">สถานี</label>
            <select name="yr_cid" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 min-w-[220px]">
                <option value="all" <?= $yr_isAll ? 'selected' : '' ?>>
                    ค่าเฉลี่ยทั้งหมด <?= count($sensors) ?> จุด
                </option>
                <option disabled>──────────────────</option>
                <?php foreach ($sensors as $i => $s): ?>
                <option value="<?= htmlspecialchars($s['cid']) ?>" <?= $s['cid'] === $yr_cid ? 'selected' : '' ?>>
                    <?= ($i + 1) . '. ' . htmlspecialchars($s['location_name']) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex flex-col gap-1">
            <label class="text-xs text-slate-500 font-medium">ปี (พ.ศ.)</label>
            <select name="yr_year" class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                <?php foreach ($availYears as $y): ?>
                <option value="<?= $y ?>" <?= $y == $yr_year ? 'selected' : '' ?>><?= $y + 543 ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" name="yr_go" value="1"
                class="flex items-center gap-2 px-5 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition-colors">
            <i class="fas fa-search"></i> ดูรายงาน
        </button>

        <a href="#yearly" onclick="window.open('pm25_summary_update.php','_blank')"
           class="flex items-center gap-2 px-4 py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-sm transition-colors">
            <i class="fas fa-sync-alt text-teal-600"></i> อัปเดตข้อมูล
        </a>

        <div class="ml-auto text-xs text-slate-400">
            ข้อมูลจาก pm25_daily_summary · อัปเดตอัตโนมัติทุกวัน
        </div>
    </form>

    <!-- Title block + Export button -->
    <div class="flex items-center justify-between mb-4 flex-wrap gap-3">
        <div class="text-center flex-1">
            <p class="text-sm font-semibold text-slate-700">ตารางแสดงค่าเฉลี่ย PM2.5 รายวัน (µg/m³)</p>
        <p class="text-xs text-slate-500 mt-0.5">
            <?= $yr_isAll
                ? 'ค่าเฉลี่ยทั้งหมด ' . count($sensors) . ' สถานี · เทศบาลนครรังสิต'
                : htmlspecialchars($yr_station['location_name'] ?? '') ?>
            · ปี <?= $yr_year + 543 ?>
        </p>
        </div>
        <a href="?export=excel_yr&yr_year=<?= $yr_year ?>&yr_cid=<?= urlencode($yr_cid) ?>&period=<?= $period ?>&start_date=<?= $start_date ?>&end_date=<?= $end_date ?>"
           class="flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-semibold transition-colors shadow-sm whitespace-nowrap">
            <i class="fas fa-file-excel"></i> Export Excel
        </a>
    </div>

    <!-- Calendar Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-4">
        <div class="overflow-x-auto">
            <table class="border-collapse w-full text-xs">
                <thead>
                    <tr>
                        <th colspan="35" class="bg-teal-800 text-white text-center py-2 px-4" style="font-size:11px;letter-spacing:.02em">
                            <span class="font-bold">Rangsit Smart City (RSSC) - Open data </span>
                            &nbsp;·&nbsp; พัฒนาโดย : งานสถิติและข้อมูลสารสนเทศ &nbsp;ฝ่ายบริการและเผยแพร่วิชาการ &nbsp;กองยุทธศาสตร์และงบประมาณ
                        </th>
                    </tr>
                    <tr>
                        <th class="bg-slate-800 text-white px-4 py-3 font-semibold text-left sticky left-0 z-10 min-w-[90px]">เดือน</th>
                        <?php for ($d = 1; $d <= 31; $d++): ?>
                        <th class="bg-slate-800 text-slate-300 text-center py-3 px-0 font-medium min-w-[34px]"><?= $d ?></th>
                        <?php endfor; ?>
                        <th class="bg-slate-700 text-slate-300 text-center py-3 px-2 font-semibold min-w-[52px]">เฉลี่ย</th>
                        <th class="bg-slate-700 text-slate-300 text-center py-3 px-2 font-semibold min-w-[52px]">สูงสุด</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $yearAvgs = [];
                    for ($m = 1; $m <= 12; $m++):
                        $daysInM  = cal_days_in_month(CAL_GREGORIAN, $m, $yr_year);
                        $monthVals = [];
                        // skip future months
                        $isFuture = ($yr_year > (int)date('Y')) ||
                                    ($yr_year == (int)date('Y') && $m > (int)date('n'));
                        if ($isFuture) continue;
                    ?>
                    <tr class="border-t border-slate-100 <?= $m % 2 == 0 ? 'bg-slate-50/40' : '' ?>">
                        <td class="px-3 py-2 font-semibold text-slate-700 sticky left-0 <?= $m % 2 == 0 ? 'bg-slate-50' : 'bg-white' ?> border-r border-slate-100 z-10 whitespace-nowrap">
                            <?= $monthFullTH2[$m] ?>
                        </td>
                        <?php for ($d = 1; $d <= 31; $d++):
                            // วันเกินในเดือน
                            if ($d > $daysInM) {
                                echo '<td class="bg-slate-50/30"></td>';
                                continue;
                            }
                            // วันข้างหน้า
                            $cellDate = sprintf('%04d-%02d-%02d', $yr_year, $m, $d);
                            if ($cellDate > date('Y-m-d')) {
                                echo '<td class="bg-slate-50/30"></td>';
                                continue;
                            }
                            $v  = $yrData[$m][$d] ?? null;
                            $lv = pmLevelYr($v);
                            if ($v !== null) $monthVals[] = $v;
                        ?>
                        <td class="text-center py-2 px-0 font-bold leading-none"
                            title="<?= $cellDate ?>: <?= $v !== null ? $v.' µg/m³ ('.$lv['label'].')' : 'ไม่มีข้อมูล' ?>"
                            style="<?= $lv['hex'] ? 'background:'.$lv['hex'].';color:'.$lv['text'] : 'color:#cbd5e1' ?>">
                            <?php if ($v !== null):
                                $disp = number_format($v, 1);
                                // ตัวหนาขีดเส้นใต้ถ้าเกินมาตรฐาน (>37.5)
                                if ($v > 37.5) echo '<span style="text-decoration:underline">'.$disp.'</span>';
                                else echo $disp;
                            else: ?>
                            <span style="opacity:.3">–</span>
                            <?php endif; ?>
                        </td>
                        <?php endfor; ?>

                        <?php
                        $mAvg = $monthVals ? round(array_sum($monthVals)/count($monthVals),1) : null;
                        $mMax = $monthVals ? max($monthVals) : null;
                        $lvA  = pmLevelYr($mAvg); $lvX = pmLevelYr($mMax);
                        if ($mAvg !== null) $yearAvgs[] = $mAvg;
                        ?>
                        <td class="text-center py-2 px-2 font-bold" style="color:<?= $lvA['hex'] ?: '#94a3b8' ?>">
                            <?= $mAvg ?? '–' ?>
                        </td>
                        <td class="text-center py-2 px-2 font-bold" style="color:<?= $lvX['hex'] ?: '#94a3b8' ?>">
                            <?= $mMax ?? '–' ?>
                        </td>
                    </tr>
                    <?php endfor; ?>

                    <!-- Year average row -->
                    <?php
                    $yAvg = $yearAvgs ? round(array_sum($yearAvgs)/count($yearAvgs),1) : null;
                    $lvYA = pmLevelYr($yAvg);
                    ?>
                    <tr class="border-t-2 border-slate-300 bg-slate-100">
                        <td class="px-3 py-2.5 font-bold text-slate-800 sticky left-0 bg-slate-100 border-r border-slate-200 z-10">
                            เฉลี่ยทั้งปี
                        </td>
                        <td colspan="31"></td>
                        <td class="text-center py-2.5 px-2 font-bold text-base" style="color:<?= $lvYA['hex'] ?: '#94a3b8' ?>">
                            <?= $yAvg ?? '–' ?>
                        </td>
                        <td></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="35" class="text-center py-2 px-4 text-white" style="background:#0f766e;font-size:11px">
                            ติดตั้งและตรวจสอบความถูกต้องโดย : <strong>กองสาธารณสุขและสิ่งแวดล้อม เทศบาลนครรังสิต</strong>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 px-5 py-3 border-t border-slate-100 text-[11px]">
            <span class="text-slate-400 font-medium shrink-0">เกณฑ์ PM2.5 (µg/m³):</span>
            <?php foreach ([
                ['#3BCCFF','#0c4a6e','0–15.0 ดีมาก'],
                ['#92D050','#14532d','15.1–25.0 ดี'],
                ['#FFFF00','#713f12','25.1–37.5 ปานกลาง'],
                ['#FFA200','#ffffff','37.6–75.0 เริ่มมีผลกระทบ'],
                ['#F04646','#ffffff','≥75.1 มีผลกระทบ'],
            ] as [$bg,$tc,$l]): ?>
            <span class="flex items-center gap-1">
                <span class="w-4 h-4 rounded border border-slate-200 inline-block"
                      style="background:<?= $bg ?>"></span>
                <span class="text-slate-500"><?= $l ?></span>
            </span>
            <?php endforeach; ?>
            <span class="text-slate-400 ml-2"><span style="text-decoration:underline">ตัวเลข</span> = เกิน 37.5 µg/m³</span>
            <span class="text-slate-400 ml-auto text-right">หมายเหตุ: – = ไม่มีข้อมูล</span>
        </div>
    </div>

    <?php endif; // hasSummary ?>
</div><!-- /yearly section -->

<?php $privacy_category='pm25'; $privacy_page_title='รายงาน PM2.5 — เทศบาลนครรังสิต'; require_once __DIR__.'/includes/privacy_consent.php'; ?>
<footer class="text-center text-xs text-slate-400 pb-6 px-4 space-y-1">
    <div>ระบบติดตามคุณภาพอากาศ PM2.5 · เทศบาลนครรังสิต · <?= date('Y') ?></div>
    <div>พัฒนาโดย : งานสถิติและข้อมูลสารสนเทศ &nbsp;ฝ่ายบริการและเผยแพร่วิชาการ &nbsp;กองยุทธศาสตร์และงบประมาณ &nbsp;&nbsp;โทร 151</div>
</footer>

<script>
const today = new Date();
function fmt(d) {
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
}

function setPeriod(p) {
    document.getElementById('periodInput').value = p;
    document.querySelectorAll('.period-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('btn_' + p).classList.add('active');

    // set smart default dates
    const presets = {
        daily:   () => { setPreset('thisMonth'); },
        weekly:  () => { setPreset('thisMonth'); },
        monthly: () => { setPreset('thisYear'); },
        yearly:  () => {
            const s = new Date(today.getFullYear() - 2, 0, 1);
            document.getElementById('start_date').value = fmt(s);
            document.getElementById('end_date').value   = fmt(today);
        },
    };
    if (presets[p]) presets[p]();
}

function setPreset(name) {
    const y = today.getFullYear(), m = today.getMonth();
    let s, e = fmt(today);
    switch (name) {
        case 'thisMonth':
            s = fmt(new Date(y, m, 1)); break;
        case 'lastMonth':
            s = fmt(new Date(y, m-1, 1));
            e = fmt(new Date(y, m, 0)); break;
        case 'thisYear':
            s = fmt(new Date(y, 0, 1)); break;
        case 'last7':
            s = fmt(new Date(today - 6*86400000)); break;
        case 'last30':
            s = fmt(new Date(today - 29*86400000)); break;
        default: return;
    }
    document.getElementById('start_date').value = s;
    document.getElementById('end_date').value   = e;
}
</script>
</body>
</html>
