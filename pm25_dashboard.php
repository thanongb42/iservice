<?php
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

// ── Step 1: ดึงค่าล่าสุดจาก pm25_data ทุก CID (เหมือน pm25_realtime.php) ──────
$allLatest = [];
try {
    $stmt = $pdo->query("
        SELECT t1.* FROM pm25_data t1
        INNER JOIN (
            SELECT cid, MAX(sensor_timestamp) AS max_ts FROM pm25_data GROUP BY cid
        ) t2 ON t1.cid = t2.cid AND t1.sensor_timestamp = t2.max_ts
        ORDER BY t1.cid
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $allLatest[$r['cid']] = $r;
    }
} catch (Exception $e) {}

// ── Step 2: ดึงข้อมูลสถานีจาก pm25_sensors (ถ้ามี) ──────────────────────────
$sensorRecords = [];
try {
    $rows = $pdo->query("SELECT * FROM pm25_sensors WHERE is_active=1 ORDER BY id")
                ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $sensorRecords[$r['cid']] = $r;
    }
} catch (Exception $e) {}

// ── Step 3: รวมข้อมูลทุก metric ─────────────────────────────────────────────
function nullFloat($v): ?float { return $v !== null && $v !== '' ? (float)$v : null; }

$sensors = [];
if (!empty($sensorRecords)) {
    foreach ($sensorRecords as $cid => $s) {
        $d = $allLatest[$cid] ?? null;
        $sensors[] = [
            'id'            => $s['id'],
            'location_name' => $s['location_name'],
            'cid'           => $cid,
            'lat'           => $s['lat'],
            'lng'           => $s['lng'],
            'pm25'  => $d ? nullFloat($d['pm25'])        : null,
            'pm1'   => $d ? nullFloat($d['pm1'])         : null,
            'pm10'  => $d ? nullFloat($d['pm10'])        : null,
            'temp'  => $d ? nullFloat($d['temperature'] ?? null) : null,
            'humi'  => $d ? nullFloat($d['humidity']    ?? null) : null,
            'co2'   => $d ? nullFloat($d['co2'])         : null,
            'last_ts' => $d ? (int)$d['sensor_timestamp'] : null,
        ];
    }
} else {
    $i = 1;
    foreach ($allLatest as $cid => $d) {
        $sensors[] = [
            'id'            => $i++,
            'location_name' => 'CID: ' . $cid,
            'cid'           => $cid,
            'lat'           => null, 'lng' => null,
            'pm25'  => nullFloat($d['pm25']),
            'pm1'   => nullFloat($d['pm1']   ?? null),
            'pm10'  => nullFloat($d['pm10']  ?? null),
            'temp'  => nullFloat($d['temperature'] ?? null),
            'humi'  => nullFloat($d['humidity']    ?? null),
            'co2'   => nullFloat($d['co2']   ?? null),
            'last_ts' => (int)$d['sensor_timestamp'],
        ];
    }
}

$now = time();
$onlineCnt = 0;
$pmValues  = [];
foreach ($sensors as $s) {
    if ($s['last_ts'] && ($now - $s['last_ts']) < 1800) $onlineCnt++;
    if ($s['pm25'] !== null) $pmValues[] = $s['pm25'];
}
$avgPM = $pmValues ? round(array_sum($pmValues) / count($pmValues), 1) : null;
$maxPM = $pmValues ? max($pmValues) : null;

// เวลาที่ cron บันทึกข้อมูลล่าสุด (created_at = เวลาเซิร์ฟเวอร์จริง)
$lastFetchTs = null;
try {
    $r = $pdo->query("SELECT UNIX_TIMESTAMP(MAX(created_at)) AS t FROM pm25_data")->fetch(PDO::FETCH_ASSOC);
    $lastFetchTs = $r['t'] ? (int)$r['t'] : null;
} catch (Exception $e) {}

// ── กราฟเส้น 24 ชั่วโมง (ทุก metric) ────────────────────────────────────────
$chartRawByCid = []; // [cid] => [[ts, pm25, pm1, pm10, temp, humi, co2], ...]
$allTs = [];
try {
    $cids = array_column($sensors, 'cid');
    if (!empty($cids)) {
        $in   = implode(',', array_fill(0, count($cids), '?'));
        $stmt = $pdo->prepare("
            SELECT cid, pm25, pm1, pm10,
                   temperature AS temp, humidity AS humi, co2,
                   sensor_timestamp AS ts
            FROM pm25_data
            WHERE cid IN ($in)
              AND sensor_timestamp >= UNIX_TIMESTAMP(NOW()) - 86400
            ORDER BY sensor_timestamp ASC
        ");
        $stmt->execute($cids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $ts = (int)$r['ts'];
            $allTs[$ts] = date('H:i', $ts);
            $chartRawByCid[$r['cid']][$ts] = [
                'pm25' => nullFloat($r['pm25']),
                'pm1'  => nullFloat($r['pm1']),
                'pm10' => nullFloat($r['pm10']),
                'temp' => nullFloat($r['temp']),
                'humi' => nullFloat($r['humi']),
                'co2'  => nullFloat($r['co2']),
            ];
        }
    }
} catch (Exception $e) {}

ksort($allTs);
$chartLabels = array_values($allTs);
$chartTsKeys = array_keys($allTs);
$hasChart    = !empty($chartLabels);

function pmLevel($v): array {
    if ($v === null) return ['hex' => '#94a3b8', 'label' => 'ไม่มีข้อมูล'];
    $v = (float)$v;
    if ($v <= 25)  return ['hex' => '#16a34a', 'label' => 'ดีมาก'];
    if ($v <= 37)  return ['hex' => '#84cc16', 'label' => 'ดี'];
    if ($v <= 50)  return ['hex' => '#eab308', 'label' => 'ปานกลาง'];
    if ($v <= 90)  return ['hex' => '#f97316', 'label' => 'เริ่มมีผลกระทบ'];
    return             ['hex' => '#ef4444', 'label' => 'มีผลกระทบต่อสุขภาพ'];
}

$sensorMapData = array_map(function ($s) use ($now) {
    return [
        'id'     => (int)$s['id'],
        'name'   => $s['location_name'],
        'lat'    => $s['lat'] ? (float)$s['lat'] : null,
        'lng'    => $s['lng'] ? (float)$s['lng'] : null,
        'pm25'   => $s['pm25'],
        'pm1'    => $s['pm1'],
        'pm10'   => $s['pm10'],
        'temp'   => $s['temp'],
        'humi'   => $s['humi'],
        'co2'    => $s['co2'],
        'ts'     => $s['last_ts'],
        'online' => $s['last_ts'] && ($now - $s['last_ts']) < 1800,
    ];
}, $sensors);

$pinnedCount = count(array_filter($sensors, function ($s) {
    return !empty($s['lat']) && !empty($s['lng']);
}));
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบติดตามคุณภาพอากาศ PM2.5 - เทศบาลนครรังสิต</title>
    <meta http-equiv="refresh" content="60">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { font-family: 'Kanit', sans-serif; }
        #map { height: 440px; border-radius: 0.75rem; }
        .sensor-card { transition: transform .15s, box-shadow .15s; }
        .sensor-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.12); }
        .pm-ring {
            width: 108px; height: 108px; border-radius: 50%;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            margin: 0 auto .75rem;
            box-shadow: 0 4px 16px rgba(0,0,0,.18);
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen">

<!-- ─── Header ─── -->
<header class="bg-gradient-to-r from-teal-800 to-teal-600 text-white shadow-lg sticky top-0 z-10">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center gap-4">
        <img src="public/assets/images/logo/rangsit-small-logo.png" alt="logo"
             class="h-12 w-auto rounded" onerror="this.style.display='none'">
        <div class="flex-1">
            <h1 class="text-lg font-bold leading-tight">ระบบติดตามคุณภาพอากาศ PM2.5</h1>
            <p class="text-teal-200 text-xs">เทศบาลนครรังสิต · อัปเดตอัตโนมัติทุก 60 วินาที</p>
        </div>
        <div class="flex items-center gap-2 ml-auto">
            <div class="text-right text-sm text-teal-100 hidden sm:block mr-2">
                <div><?= date('d/m/Y') ?></div>
                <div id="clockTime" class="font-mono font-semibold"><?= date('H:i:s') ?></div>
            </div>
            <a href="index.php"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white/15 hover:bg-white/25 text-white transition-colors">
                <i class="fas fa-home"></i>
                <span class="hidden sm:inline">หน้าหลัก</span>
            </a>
            <a href="login.php"
               class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-white text-teal-700 hover:bg-teal-50 transition-colors">
                <i class="fas fa-sign-in-alt"></i>
                <span class="hidden sm:inline">เข้าสู่ระบบ</span>
            </a>
        </div>
    </div>
</header>

<!-- ─── Stats Bar ─── -->
<div class="bg-white border-b border-slate-200 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-2.5 flex flex-wrap gap-x-6 gap-y-1 text-sm">
        <div class="flex items-center gap-2">
            <i class="fas fa-satellite-dish text-teal-600 text-xs"></i>
            <span class="text-slate-500">สถานีทั้งหมด</span>
            <span class="font-bold text-slate-800"><?= count($sensors) ?> สถานี</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500 inline-block animate-pulse"></span>
            <span class="text-slate-500">ออนไลน์</span>
            <span class="font-bold text-green-700"><?= $onlineCnt ?> สถานี</span>
        </div>
        <div class="flex items-center gap-2">
            <i class="fas fa-chart-line text-blue-500 text-xs"></i>
            <span class="text-slate-500">เฉลี่ย PM2.5</span>
            <span class="font-bold text-slate-800"><?= $avgPM !== null ? $avgPM . ' µg/m³' : '--' ?></span>
        </div>
        <div class="flex items-center gap-2">
            <i class="fas fa-arrow-up text-red-500 text-xs"></i>
            <span class="text-slate-500">สูงสุด</span>
            <span class="font-bold text-slate-800"><?= $maxPM !== null ? $maxPM . ' µg/m³' : '--' ?></span>
        </div>
        <?php if ($lastFetchTs): ?>
        <div class="flex items-center gap-2">
            <i class="fas fa-clock text-teal-500 text-xs"></i>
            <span class="text-slate-500">ดึงข้อมูลล่าสุด</span>
            <span class="font-semibold text-slate-700"><?= date('d/m/Y H:i', $lastFetchTs) ?> น.</span>
        </div>
        <?php endif; ?>
        <div class="ml-auto flex items-center gap-1 text-slate-400 text-xs">
            <i class="fas fa-sync-alt"></i>
            <span>รีเฟรชใน <span id="countdown">60</span>s</span>
        </div>
    </div>
</div>

<!-- ─── AQI Legend Bar ─── -->
<div class="bg-white border-b border-slate-100">
    <div class="max-w-7xl mx-auto px-4 py-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-xs">
        <span class="text-slate-400 font-medium shrink-0">เกณฑ์ PM2.5:</span>
        <?php foreach ([
            ['#16a34a','0–25','ดีมาก'],
            ['#84cc16','26–37','ดี'],
            ['#eab308','38–50','ปานกลาง'],
            ['#f97316','51–90','เริ่มมีผลกระทบ'],
            ['#ef4444','≥91','มีผลกระทบ'],
        ] as [$c,$r,$l]): ?>
        <div class="flex items-center gap-1">
            <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $c ?>"></span>
            <span style="color:<?= $c ?>" class="font-semibold"><?= $r ?></span>
            <span class="text-slate-400"><?= $l ?></span>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ─── Main ─── -->
<div class="max-w-7xl mx-auto px-4 py-6">

    <?php if (empty($sensors)): ?>
    <div class="text-center py-20 text-slate-400">
        <i class="fas fa-satellite-dish text-5xl mb-4"></i>
        <p class="text-lg font-medium">ยังไม่มีข้อมูล pm25_data</p>
        <p class="text-sm mt-1">อุปกรณ์ยังไม่ได้ส่งข้อมูลมา หรือยังไม่มีตาราง pm25_data ในฐานข้อมูล</p>
    </div>
    <?php else: ?>

    <!-- ─── Sensor Cards ─── -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <?php foreach ($sensors as $s):
            $pm       = $s['pm25'];
            $level    = pmLevel($pm);
            $isOnline = $s['last_ts'] && ($now - $s['last_ts']) < 1800;
            $lastTs   = $s['last_ts'] ?? 0;
        ?>
        <div class="sensor-card bg-white rounded-2xl shadow-sm border border-slate-100 p-4 flex flex-col">

            <!-- status row -->
            <div class="flex justify-between items-center mb-3">
                <span class="text-[11px] font-medium px-2 py-0.5 rounded-full
                    <?= $isOnline ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                    <?= $isOnline ? '● ออนไลน์' : '○ ออฟไลน์' ?>
                </span>
                <span class="text-[10px] text-slate-300 font-mono">#<?= str_pad($s['id'], 2, '0', STR_PAD_LEFT) ?></span>
            </div>

            <!-- PM2.5 circle -->
            <div class="pm-ring" style="background:<?= $level['hex'] ?>">
                <div class="text-white font-bold text-[1.8rem] leading-none">
                    <?= $pm !== null ? number_format($pm, 1) : '--' ?>
                </div>
                <div class="text-white text-[10px] opacity-80 mt-0.5">µg/m³</div>
            </div>

            <!-- AQI label -->
            <div class="text-center mb-2">
                <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full"
                      style="background:<?= $level['hex'] ?>1a; color:<?= $level['hex'] ?>">
                    PM2.5 · <?= $level['label'] ?>
                </span>
            </div>

            <!-- Location name -->
            <h3 class="text-center text-sm font-semibold text-slate-700 leading-snug
                        min-h-[2.4rem] flex items-center justify-center mb-3">
                <?= htmlspecialchars($s['location_name']) ?>
            </h3>

            <!-- All metrics grid -->
            <div class="grid grid-cols-2 gap-x-3 gap-y-1.5 text-xs mb-3">
                <div class="flex items-center gap-1.5 text-slate-600">
                    <i class="fas fa-thermometer-half text-orange-400 w-3"></i>
                    <span class="text-slate-400">อุณหภูมิ</span>
                    <span class="ml-auto font-medium"><?= $s['temp'] !== null ? number_format($s['temp'],1).'°C' : '--' ?></span>
                </div>
                <div class="flex items-center gap-1.5 text-slate-600">
                    <i class="fas fa-tint text-blue-400 w-3"></i>
                    <span class="text-slate-400">ความชื้น</span>
                    <span class="ml-auto font-medium"><?= $s['humi'] !== null ? number_format($s['humi'],1).'%' : '--' ?></span>
                </div>
                <div class="flex items-center gap-1.5 text-slate-600">
                    <i class="fas fa-wind text-slate-400 w-3"></i>
                    <span class="text-slate-400">PM1</span>
                    <span class="ml-auto font-medium"><?= $s['pm1'] !== null ? number_format($s['pm1'],0) : '--' ?> <span class="text-slate-300">µg</span></span>
                </div>
                <div class="flex items-center gap-1.5 text-slate-600">
                    <i class="fas fa-smog text-slate-400 w-3"></i>
                    <span class="text-slate-400">PM10</span>
                    <span class="ml-auto font-medium"><?= $s['pm10'] !== null ? number_format($s['pm10'],0) : '--' ?> <span class="text-slate-300">µg</span></span>
                </div>
                <div class="col-span-2 flex items-center gap-1.5 text-slate-600">
                    <i class="fas fa-cloud text-green-400 w-3"></i>
                    <span class="text-slate-400">CO2</span>
                    <span class="ml-auto font-medium"><?= $s['co2'] !== null ? number_format($s['co2'],0).' ppm' : '--' ?></span>
                </div>
            </div>

            <!-- Last update -->
            <div class="mt-auto border-t border-slate-100 pt-2 text-center">
                <?php if ($lastTs): ?>
                <span class="text-[11px] text-slate-400">อัปเดต <?= date('d/m H:i', $lastTs) ?> น.</span>
                <?php else: ?>
                <span class="text-[11px] text-slate-300">ยังไม่มีข้อมูล</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ─── Line Chart ─── -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <i class="fas fa-chart-line text-teal-600"></i>
            <h2 class="text-base font-semibold text-slate-700">กราฟย้อนหลัง 24 ชั่วโมง</h2>
        </div>

        <?php if (!$hasChart): ?>
        <div class="text-center py-10 text-slate-300 text-sm">
            <i class="fas fa-chart-line text-3xl mb-2 block"></i>
            ยังไม่มีข้อมูลกราฟ
        </div>
        <?php else: ?>

        <!-- Metric Tabs -->
        <div class="flex flex-wrap gap-1.5 mb-4">
            <?php foreach ([
                ['pm25','PM2.5','#16a34a'],['pm10','PM10','#0ea5e9'],['pm1','PM1','#8b5cf6'],
                ['temp','อุณหภูมิ','#f97316'],['humi','ความชื้น','#3b82f6'],['co2','CO2','#84cc16'],
            ] as [$mk,$ml,$mc]): ?>
            <button onclick="selectMetric('<?= $mk ?>')" id="metricBtn<?= $mk ?>"
                    class="metric-btn px-3 py-1 rounded-lg text-xs font-medium border transition-all"
                    style="border-color:<?= $mc ?>;color:<?= $mc ?>">
                <?= $ml ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Station Selector -->
        <div class="flex flex-wrap gap-2 mb-4" id="stationBtns">
            <?php foreach ($sensors as $idx => $s):
                $color = ['#16a34a','#84cc16','#f97316','#8b5cf6','#ef4444','#eab308','#06b6d4','#ec4899'][$idx % 8];
            ?>
            <button onclick="selectStation(<?= $idx ?>)"
                    id="stationBtn<?= $idx ?>"
                    class="station-btn px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
                    style="border-color:<?= $color ?>;color:<?= $color ?>">
                <?= htmlspecialchars($s['location_name']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Chart info -->
        <div class="flex items-baseline gap-2 mb-2">
            <span id="chartStationName" class="text-sm font-semibold text-slate-700"></span>
            <span id="chartMetricLabel" class="text-xs text-slate-400"></span>
            <span id="chartCurrentVal" class="ml-auto text-sm font-bold"></span>
        </div>

        <div class="relative" style="height:280px">
            <canvas id="pm25Chart"></canvas>
        </div>
        <?php endif; ?>
    </div>

    <!-- ─── Map ─── -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <i class="fas fa-map-marked-alt text-teal-600"></i>
            <h2 class="text-base font-semibold text-slate-700">แผนที่สถานีตรวจวัด</h2>
            <span class="text-xs text-slate-400">
                (<?= $pinnedCount ?> / <?= count($sensors) ?> สถานีที่กำหนดพิกัดแล้ว)
            </span>
        </div>
        <?php if ($pinnedCount === 0): ?>
        <div class="text-center py-6 text-slate-300 text-sm mb-2">
            <i class="fas fa-map-pin text-2xl mb-2"></i>
            <p>ยังไม่ได้กำหนดพิกัดสถานี — สามารถตั้งค่าได้ที่ <a href="admin/pm25_sensors.php" class="text-teal-600 underline">Admin → จัดการสถานี PM2.5</a></p>
        </div>
        <?php endif; ?>
        <div id="map"></div>
    </div>

    <!-- ─── AQI Legend ─── -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
        <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">เกณฑ์คุณภาพอากาศ PM2.5 (µg/m³)</h3>
        <div class="flex flex-wrap gap-2 text-[12px]">
            <?php foreach ([
                ['#16a34a', '0 – 25',  'ดีมาก'],
                ['#84cc16', '26 – 37', 'ดี'],
                ['#eab308', '38 – 50', 'ปานกลาง'],
                ['#f97316', '51 – 90', 'เริ่มมีผลกระทบต่อสุขภาพ'],
                ['#ef4444', '≥ 91',    'มีผลกระทบต่อสุขภาพ'],
                ['#94a3b8', '–',       'ไม่มีข้อมูล'],
            ] as [$c, $range, $label]): ?>
            <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg" style="background:<?= $c ?>18">
                <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:<?= $c ?>"></span>
                <span style="color:<?= $c ?>" class="font-semibold"><?= $range ?></span>
                <span class="text-slate-500"><?= $label ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php endif; ?>
</div>

<footer class="text-center text-xs text-slate-400 pb-6 px-4">
    ระบบติดตามคุณภาพอากาศ PM2.5 · เทศบาลนครรังสิต · <?= date('Y') ?>
</footer>

<script>
// ─── Chart (metric × station) ────────────────────────────────────────────────
<?php if ($hasChart): ?>
const chartLabels  = <?= json_encode($chartLabels) ?>;
const chartTsKeys  = <?= json_encode($chartTsKeys) ?>;
const chartRawData = <?= json_encode($chartRawByCid) ?>; // {cid: {ts: {pm25,pm1,...}}}
const sensorList   = <?= json_encode(array_map(fn($s) => ['cid'=>$s['cid'],'name'=>$s['location_name']], $sensors)) ?>;
const stationColors= ['#16a34a','#84cc16','#f97316','#8b5cf6','#ef4444','#eab308','#06b6d4','#ec4899'];
const metricConfig = {
    pm25: { label:'PM2.5', unit:'µg/m³', color:'#16a34a' },
    pm10: { label:'PM10',  unit:'µg/m³', color:'#0ea5e9' },
    pm1:  { label:'PM1',   unit:'µg/m³', color:'#8b5cf6' },
    temp: { label:'อุณหภูมิ', unit:'°C',color:'#f97316' },
    humi: { label:'ความชื้น', unit:'%', color:'#3b82f6' },
    co2:  { label:'CO2',   unit:'ppm',   color:'#84cc16' },
};

let pm25Chart = null, activeStation = 0, activeMetric = 'pm25';

function getChartData(cidIdx, metricKey) {
    const cid  = sensorList[cidIdx].cid;
    const raw  = chartRawData[cid] || {};
    return chartTsKeys.map(ts => {
        const row = raw[ts];
        return row ? (row[metricKey] ?? null) : null;
    });
}

function updateChart() {
    const color = stationColors[activeStation % stationColors.length];
    const mc    = metricConfig[activeMetric];
    const data  = getChartData(activeStation, activeMetric);
    const latestVal = [...data].reverse().find(v => v !== null);

    document.getElementById('chartStationName').textContent = sensorList[activeStation].name;
    document.getElementById('chartMetricLabel').textContent = mc.label;
    const valEl = document.getElementById('chartCurrentVal');
    valEl.textContent = latestVal != null ? latestVal + ' ' + mc.unit : '--';
    valEl.style.color = color;

    if (pm25Chart) {
        pm25Chart.data.datasets[0].data            = data;
        pm25Chart.data.datasets[0].borderColor     = color;
        pm25Chart.data.datasets[0].backgroundColor = color + '20';
        pm25Chart.options.scales.y.title.text      = mc.label + ' (' + mc.unit + ')';
        pm25Chart.update('none');
    }
}

function selectStation(idx) {
    activeStation = idx;
    document.querySelectorAll('.station-btn').forEach((btn, i) => {
        const c = stationColors[i % stationColors.length];
        btn.style.background  = i === idx ? c       : 'transparent';
        btn.style.color       = i === idx ? '#fff'  : c;
        btn.style.borderColor = c;
    });
    updateChart();
}

function selectMetric(key) {
    activeMetric = key;
    document.querySelectorAll('.metric-btn').forEach(btn => {
        const mk = btn.id.replace('metricBtn','');
        const c  = metricConfig[mk].color;
        const on = mk === key;
        btn.style.background  = on ? c : 'transparent';
        btn.style.color       = on ? '#fff' : c;
        btn.style.borderColor = c;
    });
    updateChart();
}

(function () {
    const ctx = document.getElementById('pm25Chart').getContext('2d');
    pm25Chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                data: [], borderColor: '#16a34a', backgroundColor: '#16a34a20',
                borderWidth: 2, pointRadius: 3, fill: true, tension: 0.3, spanGaps: true,
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: c => ` ${c.parsed.y !== null ? c.parsed.y : '-'}` } }
            },
            scales: {
                x: { ticks: { maxTicksLimit: 12, font: { family:'Kanit',size:11 }, maxRotation: 0 }, grid: { color:'#f1f5f9' } },
                y: { beginAtZero: true, title: { display: true, text:'', font:{ family:'Kanit',size:11 } }, grid: { color:'#f1f5f9' } }
            },
            animation: false,
        }
    });
    selectMetric('pm25');
    selectStation(0);
})();
<?php endif; ?>

// ─── Map ───────────────────────────────────────────────
const sensors = <?= json_encode($sensorMapData) ?>;

const map = L.map('map').setView([14.0208, 100.7511], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 19
}).addTo(map);

function pmColor(v) {
    if (v === null) return '#94a3b8';
    if (v <= 25) return '#16a34a';
    if (v <= 37) return '#84cc16';
    if (v <= 50) return '#eab308';
    if (v <= 90) return '#f97316';
    return '#ef4444';
}

const bounds = [];
sensors.forEach(s => {
    if (!s.lat || !s.lng) return;
    const c = pmColor(s.pm25);
    const v = s.pm25 !== null ? Math.round(s.pm25) : '?';
    const icon = L.divIcon({
        html: `<div style="background:${c};width:46px;height:46px;border-radius:50%;border:3px solid white;
                    box-shadow:0 3px 10px rgba(0,0,0,.28);display:flex;flex-direction:column;
                    align-items:center;justify-content:center;font-family:'Kanit',sans-serif;">
                 <span style="color:white;font-size:13px;font-weight:700;line-height:1">${v}</span>
                 <span style="color:white;font-size:8px;opacity:.8;line-height:1.2">µg</span>
               </div>`,
        className: '', iconSize: [46, 46], iconAnchor: [23, 23]
    });
    const d = s.ts ? new Date(s.ts * 1000) : null;
    const tsStr = d ? `${d.getDate()}/${d.getMonth()+1}/${d.getFullYear()} ${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')} น.` : 'ไม่มีข้อมูล';
    const fmt = (v, unit) => v !== null ? v + ' ' + unit : '--';
    const row = (icon, label, val) =>
        `<tr><td style="color:#94a3b8;padding:2px 8px 2px 0;font-size:11px">${icon} ${label}</td>
             <td style="font-weight:600;font-size:12px;text-align:right">${val}</td></tr>`;
    L.marker([s.lat, s.lng], {icon}).addTo(map)
     .bindPopup(`<div style="font-family:'Kanit',sans-serif;min-width:190px">
         <div style="font-size:13px;font-weight:700;margin-bottom:6px;border-bottom:1px solid #f1f5f9;padding-bottom:4px">${s.name}</div>
         <div style="color:${c};font-size:22px;font-weight:700;margin-bottom:4px">${fmt(s.pm25,'µg/m³')} <span style="font-size:12px;color:#64748b">PM2.5</span></div>
         <table style="width:100%;border-collapse:collapse">
             ${row('🌡️','อุณหภูมิ', fmt(s.temp,'°C'))}
             ${row('💧','ความชื้น', fmt(s.humi,'%'))}
             ${row('💨','PM1',      fmt(s.pm1,'µg/m³'))}
             ${row('🌫️','PM10',    fmt(s.pm10,'µg/m³'))}
             ${row('☁️','CO2',     fmt(s.co2,'ppm'))}
         </table>
         <div style="color:#94a3b8;font-size:10px;margin-top:6px;border-top:1px solid #f1f5f9;padding-top:4px">อัปเดต: ${tsStr}</div>
     </div>`, { maxWidth: 220 });
    bounds.push([s.lat, s.lng]);
});

if (bounds.length > 1) map.fitBounds(bounds, { padding: [50, 50] });
else if (bounds.length === 1) map.setView(bounds[0], 15);

// ─── Countdown ──────────────────────────────────────────
let t = 60;
const cdEl = document.getElementById('countdown');
setInterval(() => { cdEl.textContent = --t > 0 ? t : (t = 60); }, 1000);

// ─── Live clock ─────────────────────────────────────────
function tick() {
    const d = new Date();
    const hms = [d.getHours(), d.getMinutes(), d.getSeconds()]
        .map(n => String(n).padStart(2, '0')).join(':');
    const el = document.getElementById('clockTime');
    if (el) el.textContent = hms;
}
setInterval(tick, 1000);
</script>
</body>
</html>
