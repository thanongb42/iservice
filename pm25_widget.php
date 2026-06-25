<?php
/**
 * PM2.5 Widget — สำหรับ embed ใน WordPress หรือเว็บไซต์อื่น
 * ใช้งาน: <iframe src="https://iservice.rangsitcity.go.th/pm25_widget.php" ...>
 */
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/pm25_helpers.php';
$pdo = getPDO();

$sensors = [];
$summary = ['online' => 0, 'avg_pm25' => null, 'avg_temp' => null, 'avg_humi' => null, 'last_fetch' => null];

try {
    $stmt = $pdo->query("
        SELECT s.id, s.location_name, s.lat, s.lng,
               d.pm25, d.temperature, d.humidity, d.co2,
               d.sensor_timestamp, d.created_at
        FROM pm25_sensors s
        LEFT JOIN pm25_data d ON s.cid = d.cid
          AND d.sensor_timestamp = (SELECT MAX(sensor_timestamp) FROM pm25_data WHERE cid = s.cid)
        WHERE s.is_active = 1 ORDER BY s.id
    ");
    $sensors = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $now = time();
    $pmV = $tpV = $hmV = [];
    foreach ($sensors as $s) {
        if ($s['sensor_timestamp'] && ($now - (int)$s['sensor_timestamp']) < 1800) $summary['online']++;
        if ($s['pm25'] !== null) $pmV[] = (float)$s['pm25'];
        if (!empty($s['temperature'])) $tpV[] = (float)$s['temperature'];
        if (!empty($s['humidity']))    $hmV[] = (float)$s['humidity'];
    }
    if ($pmV) $summary['avg_pm25'] = round(array_sum($pmV) / count($pmV), 1);
    if ($tpV) $summary['avg_temp'] = round(array_sum($tpV) / count($tpV), 1);
    if ($hmV) $summary['avg_humi'] = round(array_sum($hmV) / count($hmV), 1);

    $r = $pdo->query("SELECT UNIX_TIMESTAMP(MAX(created_at)) t FROM pm25_data")->fetch();
    $summary['last_fetch'] = $r['t'] ? date('d/m/Y H:i', (int)$r['t']).' น.' : null;
} catch (Exception $e) {}

$avgLevel = pmLevel($summary['avg_pm25']);
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>PM2.5 Widget — เทศบาลนครรังสิต</title>
<link rel="icon" type="image/png" href="public/assets/images/logo/rangsit-small-logo.png">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Kanit', sans-serif; }
body { background: #f8fafc; }
.widget-wrap { width: 100%; overflow: hidden; }

/* Header */
.w-header {
    background: linear-gradient(135deg, #065f46 0%, #0d9488 100%);
    color: white; padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between; gap: 12px;
}
.w-header-title { font-size: 1rem; font-weight: 700; line-height: 1.3; }
.w-header-sub   { font-size: 0.72rem; opacity: .75; margin-top: 2px; }
.w-badge {
    font-size: 0.65rem; background: rgba(255,255,255,.15);
    padding: 3px 10px; border-radius: 999px; white-space: nowrap;
}

/* Stats row */
.w-stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: #e2e8f0; }
.w-stat  { background: #fff; padding: 12px 8px; text-align: center; }
.w-stat-val  { font-size: 1.4rem; font-weight: 700; line-height: 1; }
.w-stat-label{ font-size: 0.65rem; color: #94a3b8; margin-top: 3px; }

/* Map */
#wmap { height: 320px; }

/* Sensors grid */
.w-sensors { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1px; background: #f1f5f9; }
.w-sensor  {
    background: #fff; padding: 10px 14px;
    display: flex; align-items: center; gap: 10px;
}
.w-dot { width: 38px; height: 38px; border-radius: 50%; flex-shrink: 0;
         display: flex; flex-direction: column; align-items: center; justify-content: center; }
.w-dot-val  { font-size: 12px; font-weight: 700; color: #fff; line-height: 1; }
.w-dot-unit { font-size: 8px;  color: rgba(255,255,255,.8); line-height: 1.2; }
.w-sensor-name { font-size: 0.72rem; color: #374151; font-weight: 500; line-height: 1.3; flex: 1; }
.w-sensor-meta { font-size: 0.65rem; color: #94a3b8; margin-top: 2px; }

/* Footer */
.w-footer {
    background: #fff; border-top: 1px solid #f1f5f9;
    padding: 10px 16px;
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px;
}
.w-legend { display: flex; flex-wrap: wrap; gap: 6px; }
.w-leg-item { display: flex; align-items: center; gap: 4px; font-size: 0.65rem; color: #64748b; }
.w-leg-dot  { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.w-link {
    font-size: 0.72rem; font-weight: 600; color: #0d9488; white-space: nowrap;
    text-decoration: none; padding: 5px 12px; border: 1.5px solid #0d9488;
    border-radius: 999px; transition: all .2s;
}
.w-link:hover { background: #0d9488; color: #fff; }

@media (max-width: 480px) {
    .w-sensors { grid-template-columns: 1fr; }
    .w-stats   { grid-template-columns: repeat(2, 1fr); }
    #wmap { height: 240px; }
}
</style>
</head>
<body>
<div class="widget-wrap">

    <!-- Header -->
    <div class="w-header">
        <div>
            <div class="w-header-title">
                <i class="fas fa-wind" style="margin-right:6px;opacity:.8"></i>
                คุณภาพอากาศ PM2.5
            </div>
            <div class="w-header-sub">เทศบาลนครรังสิต · ตรวจวัด <?= count($sensors) ?> จุด</div>
        </div>
        <div>
            <?php if ($summary['last_fetch']): ?>
            <div class="w-badge">
                <i class="fas fa-clock" style="margin-right:4px;opacity:.7"></i>
                <?= $summary['last_fetch'] ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Stats -->
    <div class="w-stats">
        <div class="w-stat">
            <div class="w-stat-val" style="color:#16a34a"><?= $summary['online'] ?>/<?= count($sensors) ?></div>
            <div class="w-stat-label">ออนไลน์</div>
        </div>
        <div class="w-stat">
            <div class="w-stat-val" style="color:<?= $avgLevel['hex'] ?>"><?= $summary['avg_pm25'] ?? '--' ?></div>
            <div class="w-stat-label">PM2.5 เฉลี่ย (µg/m³)</div>
        </div>
        <div class="w-stat">
            <div class="w-stat-val" style="color:#f97316"><?= $summary['avg_temp'] ?? '--' ?></div>
            <div class="w-stat-label">อุณหภูมิ (°C)</div>
        </div>
        <div class="w-stat">
            <div class="w-stat-val" style="color:#3b82f6"><?= $summary['avg_humi'] ?? '--' ?></div>
            <div class="w-stat-label">ความชื้น (%)</div>
        </div>
    </div>

    <!-- Map -->
    <div id="wmap"></div>

    <!-- Sensor list -->
    <div class="w-sensors">
    <?php foreach ($sensors as $s):
        $pm  = $s['pm25'] !== null ? (float)$s['pm25'] : null;
        $lv  = pmLevel($pm);
        $now = time();
        $online = $s['sensor_timestamp'] && ($now - (int)$s['sensor_timestamp']) < 1800;
    ?>
    <div class="w-sensor">
        <div class="w-dot" style="background:<?= $lv['hex'] ?>">
            <div class="w-dot-val" style="color:<?= $lv['text'] ?>"><?= $pm !== null ? number_format($pm, 1) : '--' ?></div>
            <div class="w-dot-unit" style="color:<?= $lv['text'] ?>;opacity:.8">µg/m³</div>
        </div>
        <div>
            <div class="w-sensor-name"><?= htmlspecialchars($s['location_name']) ?></div>
            <div class="w-sensor-meta">
                <?= $online ? '<span style="color:#16a34a">●</span> ออนไลน์' : '<span style="color:#94a3b8">○</span> ออฟไลน์' ?>
                &nbsp;·&nbsp;<span style="color:<?= $lv['hex'] ?>;font-weight:600"><?= $lv['label'] ?></span>
            </div>
            <div class="w-sensor-meta" style="margin-top:1px">💡 <?= $lv['advice'] ?></div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Footer -->
    <div class="w-footer">
        <div class="w-legend">
            <?php foreach ([
                ['#3BCCFF','0–15.0'],['#92D050','15.1–25.0'],
                ['#FFFF00','25.1–37.5'],['#FFA200','37.6–75.0'],['#F04646','≥75.1'],
            ] as [$c,$r]): ?>
            <span class="w-leg-item">
                <span class="w-leg-dot" style="background:<?= $c ?>;border:1px solid #e2e8f0"></span><?= $r ?>
            </span>
            <?php endforeach; ?>
        </div>
        <a href="https://iservice.rangsitcity.go.th/pm25_dashboard.php"
           target="_blank" class="w-link">
            ดูทั้งหมด <i class="fas fa-arrow-right" style="font-size:.6rem"></i>
        </a>
    </div>

</div>

<script>
const map = L.map('wmap', { zoomControl: true, attributionControl: false })
             .setView([13.983, 100.632], 12);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }).addTo(map);

const sensors = <?= json_encode(array_map(function($s) {
    return [
        'name' => $s['location_name'],
        'lat'  => $s['lat']  ? (float)$s['lat']  : null,
        'lng'  => $s['lng']  ? (float)$s['lng']  : null,
        'pm25' => $s['pm25'] !== null ? (float)$s['pm25'] : null,
        'temp' => !empty($s['temperature']) ? (float)$s['temperature'] : null,
        'humi' => !empty($s['humidity'])    ? (float)$s['humidity']    : null,
    ];
}, $sensors)) ?>;

const PM25_LEVELS = [
    [0,    15.0, '#3BCCFF','#0c4a6e','ดีมาก',             'ดำเนินชีวิตได้ตามปกติ'],
    [15.1, 25.0, '#92D050','#14532d','ดี',                 'ทำกิจกรรมกลางแจ้งได้ตามปกติ'],
    [25.1, 37.5, '#FFFF00','#713f12','ปานกลาง',            'ลดระยะเวลากิจกรรมกลางแจ้ง'],
    [37.6, 75.0, '#FFA200','#ffffff','เริ่มมีผลกระทบ',     'ใช้หน้ากาก PM2.5 ทุกครั้ง'],
    [75.1, 9999, '#F04646','#ffffff','มีผลกระทบต่อสุขภาพ','งดกิจกรรมกลางแจ้ง'],
];
function pmGet(v) {
    if (v === null) return {bg:'#94a3b8',tc:'#fff',label:'ไม่มีข้อมูล',advice:''};
    for (const [lo,hi,bg,tc,label,advice] of PM25_LEVELS) {
        if (v <= hi) return {bg,tc,label,advice};
    }
    return {bg:'#F04646',tc:'#fff',label:'มีผลกระทบต่อสุขภาพ',advice:'งดกิจกรรมกลางแจ้ง'};
}

const bounds = [];
sensors.forEach(s => {
    if (!s.lat || !s.lng) return;
    const lv = pmGet(s.pm25);
    const v  = s.pm25 !== null ? s.pm25.toFixed(1) : '?';
    L.marker([s.lat, s.lng], { icon: L.divIcon({
        html: `<div style="background:${lv.bg};width:44px;height:44px;border-radius:50%;border:3px solid white;
                    box-shadow:0 2px 8px rgba(0,0,0,.28);display:flex;flex-direction:column;
                    align-items:center;justify-content:center;">
                 <span style="color:${lv.tc};font-size:11px;font-weight:700;font-family:Kanit,sans-serif;line-height:1">${v}</span>
                 <span style="color:${lv.tc};font-size:7px;opacity:.8;line-height:1.3">µg/m³</span>
               </div>`,
        className: '', iconSize: [44, 44], iconAnchor: [22, 22]
    })}).addTo(map).bindPopup(
        `<div style="font-family:Kanit,sans-serif;min-width:170px">
         <div style="font-weight:700;font-size:13px;margin-bottom:6px;border-bottom:1px solid #f1f5f9;padding-bottom:4px">${s.name}</div>
         <div style="background:${lv.bg};border-radius:8px;padding:6px 10px;margin-bottom:6px">
           <div style="color:${lv.tc};font-size:20px;font-weight:700;line-height:1">${s.pm25 !== null ? s.pm25.toFixed(1)+' µg/m³' : '–'}</div>
           <div style="color:${lv.tc};font-size:11px;font-weight:600;opacity:.9">PM 2.5 · ${lv.label}</div>
         </div>
         <div style="font-size:10px;color:#64748b;background:#f8fafc;border-radius:6px;padding:4px 8px;margin-bottom:6px">💡 ${lv.advice}</div>
         ${s.temp ? `<div style="font-size:11px;color:#64748b">🌡️ ${s.temp}°C &nbsp; 💧 ${s.humi}%</div>` : ''}
         </div>`
    );
    bounds.push([s.lat, s.lng]);
});
if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30] });
</script>
</body>
</html>
