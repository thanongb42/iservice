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

// ── Step 3: รวมข้อมูล ────────────────────────────────────────────────────────
// ถ้ามี pm25_sensors → แสดงตามลำดับ sensors + enrich ด้วย pm25_data
// ถ้าไม่มี → fallback แสดง CID จาก pm25_data โดยตรง
$sensors = [];
if (!empty($sensorRecords)) {
    foreach ($sensorRecords as $cid => $s) {
        $d = $allLatest[$cid] ?? null;
        $sensors[] = [
            'id'            => $s['id'],
            'location_name' => $s['location_name'],
            'cid'           => $cid,
            'serial_number' => $s['serial_number'],
            'sim_number'    => $s['sim_number'],
            'lat'           => $s['lat'],
            'lng'           => $s['lng'],
            'pm25_val'      => $d ? (float)$d['pm25'] : null,
            'last_ts'       => $d ? (int)$d['sensor_timestamp'] : null,
        ];
    }
} else {
    // Fallback: สร้าง card จาก pm25_data โดยตรง (ก่อน import pm25_sensors.sql)
    $i = 1;
    foreach ($allLatest as $cid => $d) {
        $sensors[] = [
            'id'            => $i++,
            'location_name' => 'CID: ' . $cid,
            'cid'           => $cid,
            'serial_number' => null,
            'sim_number'    => null,
            'lat'           => null,
            'lng'           => null,
            'pm25_val'      => (float)$d['pm25'],
            'last_ts'       => (int)$d['sensor_timestamp'],
        ];
    }
}

$now       = time();
$onlineCnt = 0;
$pmValues  = [];

foreach ($sensors as $s) {
    if ($s['last_ts'] && ($now - $s['last_ts']) < 1800) {
        $onlineCnt++;
    }
    if ($s['pm25_val'] !== null) {
        $pmValues[] = $s['pm25_val'];
    }
}

$avgPM = $pmValues ? round(array_sum($pmValues) / count($pmValues), 1) : null;
$maxPM = $pmValues ? max($pmValues) : null;

// เวลาที่ cron บันทึกข้อมูลล่าสุด (created_at = เวลาเซิร์ฟเวอร์จริง)
$lastFetchTs = null;
try {
    $r = $pdo->query("SELECT UNIX_TIMESTAMP(MAX(created_at)) AS t FROM pm25_data")->fetch(PDO::FETCH_ASSOC);
    $lastFetchTs = $r['t'] ? (int)$r['t'] : null;
} catch (Exception $e) {}

// ── กราฟเส้น 24 ชั่วโมง ─────────────────────────────────────────────────────
$chartRaw = [];
try {
    $cids = array_column($sensors, 'cid');
    if (!empty($cids)) {
        $in   = implode(',', array_fill(0, count($cids), '?'));
        $stmt = $pdo->prepare("
            SELECT cid, pm25, sensor_timestamp
            FROM pm25_data
            WHERE cid IN ($in)
              AND sensor_timestamp >= UNIX_TIMESTAMP(NOW()) - 86400
            ORDER BY sensor_timestamp ASC
        ");
        $stmt->execute($cids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $chartRaw[$r['cid']][] = ['ts' => (int)$r['sensor_timestamp'], 'pm25' => (float)$r['pm25']];
        }
    }
} catch (Exception $e) {}

$allTs = [];
foreach ($chartRaw as $points) {
    foreach ($points as $p) $allTs[$p['ts']] = date('H:i', $p['ts']);
}
ksort($allTs);
$chartLabels = array_values($allTs);
$chartTsKeys = array_keys($allTs);

$chartColors  = ['#3b82f6','#22c55e','#f97316','#8b5cf6','#ef4444','#eab308','#06b6d4','#ec4899'];
$chartDatasets = [];
foreach ($sensors as $idx => $s) {
    $tsMap = [];
    foreach ($chartRaw[$s['cid']] ?? [] as $p) $tsMap[$p['ts']] = $p['pm25'];
    $color = $chartColors[$idx % count($chartColors)];
    $chartDatasets[] = [
        'label'           => $s['location_name'],
        'data'            => array_map(fn($ts) => $tsMap[$ts] ?? null, $chartTsKeys),
        'borderColor'     => $color,
        'backgroundColor' => $color . '20',
        'borderWidth'     => 2,
        'pointRadius'     => 2,
        'fill'            => false,
        'tension'         => 0.3,
        'spanGaps'        => true,
    ];
}

function pmLevel($v): array {
    if ($v === null) return ['hex' => '#94a3b8', 'label' => 'ไม่มีข้อมูล'];
    $v = (float)$v;
    if ($v <= 25)  return ['hex' => '#3b82f6', 'label' => 'ดีมาก'];
    if ($v <= 37)  return ['hex' => '#22c55e', 'label' => 'ดี'];
    if ($v <= 50)  return ['hex' => '#eab308', 'label' => 'ปานกลาง'];
    if ($v <= 90)  return ['hex' => '#f97316', 'label' => 'เริ่มมีผลกระทบ'];
    return             ['hex' => '#ef4444', 'label' => 'มีผลกระทบต่อสุขภาพ'];
}

$sensorMapData = array_map(function ($s) use ($now) {
    return [
        'id'     => (int)$s['id'],
        'name'   => $s['location_name'],
        'cid'    => $s['cid'],
        'lat'    => $s['lat']       ? (float)$s['lat']   : null,
        'lng'    => $s['lng']       ? (float)$s['lng']   : null,
        'pm25'   => $s['pm25_val'],
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
        <div class="text-right text-sm text-teal-100 hidden sm:block">
            <div><?= date('d/m/Y') ?></div>
            <div id="clockTime" class="font-mono font-semibold"><?= date('H:i:s') ?></div>
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
            $pm       = $s['pm25_val'];
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
                    <?= $level['label'] ?>
                </span>
            </div>

            <!-- Location name -->
            <h3 class="text-center text-sm font-semibold text-slate-700 leading-snug
                        min-h-[2.6rem] flex items-center justify-center mb-3">
                <?= htmlspecialchars($s['location_name']) ?>
            </h3>

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
            <h2 class="text-base font-semibold text-slate-700">กราฟ PM2.5 ย้อนหลัง 24 ชั่วโมง</h2>
        </div>

        <?php if (empty($chartLabels)): ?>
        <div class="text-center py-10 text-slate-300 text-sm">
            <i class="fas fa-chart-line text-3xl mb-2 block"></i>
            ยังไม่มีข้อมูลกราฟ
        </div>
        <?php else: ?>

        <!-- Station Selector -->
        <div class="flex flex-wrap gap-2 mb-4" id="stationBtns">
            <?php foreach ($sensors as $idx => $s):
                $color = ['#3b82f6','#22c55e','#f97316','#8b5cf6','#ef4444','#eab308','#06b6d4','#ec4899'][$idx % 8];
            ?>
            <button onclick="selectStation(<?= $idx ?>)"
                    id="stationBtn<?= $idx ?>"
                    class="station-btn px-3 py-1.5 rounded-full text-xs font-medium border transition-all"
                    style="border-color:<?= $color ?>;color:<?= $color ?>">
                <?= htmlspecialchars($s['location_name']) ?>
            </button>
            <?php endforeach; ?>
        </div>

        <!-- Chart title -->
        <div class="mb-3">
            <span id="chartStationName" class="text-sm font-semibold text-slate-700"></span>
            <span id="chartCurrentPM" class="ml-2 text-sm font-bold"></span>
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
                ['#3b82f6', '0 – 25',  'ดีมาก'],
                ['#22c55e', '26 – 37', 'ดี'],
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
// ─── Line Chart (แสดงทีละสถานี) ─────────────────────────────────────────────
<?php if (!empty($chartLabels)): ?>
const chartLabels   = <?= json_encode($chartLabels) ?>;
const chartDatasets = <?= json_encode($chartDatasets) ?>;
const chartColors   = ['#3b82f6','#22c55e','#f97316','#8b5cf6','#ef4444','#eab308','#06b6d4','#ec4899'];

let pm25Chart = null;
let activeIdx = 0;

function selectStation(idx) {
    activeIdx = idx;
    const ds    = chartDatasets[idx];
    const color = chartColors[idx % chartColors.length];

    // อัปเดต title
    document.getElementById('chartStationName').textContent = ds.label;
    const latestVal = [...ds.data].reverse().find(v => v !== null);
    const pmEl = document.getElementById('chartCurrentPM');
    pmEl.textContent = latestVal !== undefined ? latestVal + ' µg/m³' : '';
    pmEl.style.color = color;

    // อัปเดตปุ่ม
    document.querySelectorAll('.station-btn').forEach((btn, i) => {
        if (i === idx) {
            btn.style.background = color;
            btn.style.color      = '#fff';
            btn.style.borderColor = color;
        } else {
            btn.style.background  = color + '00';
            btn.style.color       = chartColors[i % chartColors.length];
            btn.style.borderColor = chartColors[i % chartColors.length];
        }
    });

    // อัปเดตกราฟ
    if (pm25Chart) {
        pm25Chart.data.datasets[0].data        = ds.data;
        pm25Chart.data.datasets[0].borderColor = color;
        pm25Chart.data.datasets[0].backgroundColor = color + '20';
        pm25Chart.data.datasets[0].label       = ds.label;
        pm25Chart.update('none');
    }
}

(function () {
    const ctx = document.getElementById('pm25Chart').getContext('2d');
    pm25Chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartLabels,
            datasets: [{
                label:           '',
                data:            [],
                borderColor:     '#3b82f6',
                backgroundColor: '#3b82f620',
                borderWidth:     2,
                pointRadius:     3,
                fill:            true,
                tension:         0.3,
                spanGaps:        true,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: c => ` ${c.dataset.label}: ${c.parsed.y !== null ? c.parsed.y + ' µg/m³' : '-'}`
                    }
                }
            },
            scales: {
                x: {
                    ticks: { maxTicksLimit: 12, font: { family: 'Kanit', size: 11 }, maxRotation: 0 },
                    grid: { color: '#f1f5f9' }
                },
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'PM2.5 (µg/m³)', font: { family: 'Kanit', size: 11 } },
                    grid: { color: '#f1f5f9' }
                }
            },
            animation: false,
        }
    });

    // แสดง default สถานีแรก
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
    if (v <= 25) return '#3b82f6';
    if (v <= 37) return '#22c55e';
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
    const pmStr = s.pm25 !== null ? s.pm25 + ' µg/m³' : 'ไม่มีข้อมูล';
    L.marker([s.lat, s.lng], {icon}).addTo(map)
     .bindPopup(`<div style="font-family:'Kanit',sans-serif;min-width:170px;padding:2px">
         <b style="font-size:13px;display:block;margin-bottom:4px">${s.name}</b>
         <span style="color:${c};font-size:20px;font-weight:700">${pmStr}</span><br>
         <small style="color:#888">อัปเดต: ${tsStr}</small>
     </div>`);
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
