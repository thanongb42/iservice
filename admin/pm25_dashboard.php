<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$page_title   = 'Dashboard PM2.5';
$current_page = 'pm25_dashboard';
require_once '../config/database.php';
$pdo = getPDO();

$user = [
    'username'   => $_SESSION['username']  ?? 'User',
    'email'      => $_SESSION['email']     ?? '',
    'full_name'  => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User',
    'first_name' => $_SESSION['first_name'] ?? 'User',
];

// ── Step 1: ดึงค่าล่าสุดจาก pm25_data ทุก CID ──────────────────────────────
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

// ── Step 2: ดึงข้อมูลสถานีจาก pm25_sensors ────────────────────────────────
$sensors = [];
try {
    $rows = $pdo->query("SELECT * FROM pm25_sensors WHERE is_active=1 ORDER BY id")
                ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $s) {
        $d = $allLatest[$s['cid']] ?? null;
        $sensors[] = [
            'id'            => $s['id'],
            'location_name' => $s['location_name'],
            'cid'           => $s['cid'],
            'serial_number' => $s['serial_number'],
            'sim_number'    => $s['sim_number'],
            'lat'           => $s['lat'],
            'lng'           => $s['lng'],
            'pm25_val'      => $d ? (float)$d['pm25']            : null,
            'last_ts'       => $d ? (int)$d['sensor_timestamp']  : null,
        ];
    }
} catch (Exception $e) {}

$now       = time();
$onlineCnt = 0;
$pmValues  = [];
foreach ($sensors as $s) {
    if ($s['last_ts'] && ($now - $s['last_ts']) < 1800) $onlineCnt++;
    if ($s['pm25_val'] !== null) $pmValues[] = $s['pm25_val'];
}
$avgPM = $pmValues ? round(array_sum($pmValues) / count($pmValues), 1) : null;
$maxPM = $pmValues ? max($pmValues) : null;

function pmLevel($v): array {
    if ($v === null) return ['hex' => '#94a3b8', 'label' => 'ไม่มีข้อมูล'];
    $v = (float)$v;
    if ($v <= 25)  return ['hex' => '#3b82f6', 'label' => 'ดีมาก'];
    if ($v <= 37)  return ['hex' => '#22c55e', 'label' => 'ดี'];
    if ($v <= 50)  return ['hex' => '#eab308', 'label' => 'ปานกลาง'];
    if ($v <= 90)  return ['hex' => '#f97316', 'label' => 'เริ่มมีผลกระทบ'];
    return             ['hex' => '#ef4444', 'label' => 'มีผลกระทบต่อสุขภาพ'];
}

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>
    * { font-family: 'Kanit', sans-serif; }
    #map { height: 400px; border-radius: 0.75rem; }
    .pm-ring {
        width: 96px; height: 96px; border-radius: 50%;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        margin: 0 auto .625rem;
        box-shadow: 0 4px 14px rgba(0,0,0,.18);
    }
</style>

<div class="p-6 max-w-screen-xl mx-auto">

    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-wind text-teal-600"></i>
                Dashboard คุณภาพอากาศ PM2.5
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">ข้อมูลอัปเดตล่าสุด — <?= date('d/m/Y H:i') ?> น.</p>
        </div>
        <div class="flex gap-2">
            <a href="pm25_sensors.php"
               class="flex items-center gap-2 px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                <i class="fas fa-cog"></i> จัดการสถานี
            </a>
            <a href="../pm25_dashboard.php" target="_blank"
               class="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors">
                <i class="fas fa-external-link-alt"></i> หน้าสาธารณะ
            </a>
        </div>
    </div>

    <!-- Stats Bar -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">สถานีทั้งหมด</div>
            <div class="text-2xl font-bold text-gray-800"><?= count($sensors) ?></div>
            <div class="text-xs text-gray-400 mt-0.5">สถานีตรวจวัด</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">ออนไลน์</div>
            <div class="text-2xl font-bold text-green-600"><?= $onlineCnt ?></div>
            <div class="text-xs text-gray-400 mt-0.5">ส่งข้อมูลภายใน 30 นาที</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">PM2.5 เฉลี่ย</div>
            <div class="text-2xl font-bold text-blue-600"><?= $avgPM !== null ? $avgPM : '--' ?></div>
            <div class="text-xs text-gray-400 mt-0.5">µg/m³</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
            <div class="text-xs text-gray-400 mb-1">PM2.5 สูงสุด</div>
            <div class="text-2xl font-bold text-orange-500"><?= $maxPM !== null ? $maxPM : '--' ?></div>
            <div class="text-xs text-gray-400 mt-0.5">µg/m³</div>
        </div>
    </div>

    <!-- Sensor Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <?php foreach ($sensors as $s):
        $pm       = $s['pm25_val'];
        $level    = pmLevel($pm);
        $isOnline = $s['last_ts'] && ($now - $s['last_ts']) < 1800;
        $lastTs   = $s['last_ts'] ?? 0;
    ?>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 flex flex-col">

        <!-- Status -->
        <div class="flex justify-between items-center mb-3">
            <span class="text-[11px] font-medium px-2 py-0.5 rounded-full
                <?= $isOnline ? 'bg-green-50 text-green-700' : 'bg-slate-100 text-slate-400' ?>">
                <?= $isOnline ? '● ออนไลน์' : '○ ออฟไลน์' ?>
            </span>
            <span class="text-[10px] text-gray-300 font-mono">#<?= str_pad($s['id'], 2, '0', STR_PAD_LEFT) ?></span>
        </div>

        <!-- PM2.5 circle -->
        <div class="pm-ring" style="background:<?= $level['hex'] ?>">
            <div class="text-white font-bold text-2xl leading-none">
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

        <!-- Location -->
        <h3 class="text-center text-sm font-semibold text-gray-700 leading-snug
                    min-h-[2.4rem] flex items-center justify-center mb-3">
            <?= htmlspecialchars($s['location_name']) ?>
        </h3>

        <!-- Hardware info (admin only) -->
        <div class="mt-auto border-t border-gray-100 pt-3 space-y-1">
            <div class="flex justify-between text-[11px]">
                <span class="text-gray-400">CID</span>
                <span class="font-mono text-gray-600"><?= htmlspecialchars($s['cid']) ?></span>
            </div>
            <div class="flex justify-between text-[11px]">
                <span class="text-gray-400">S/N</span>
                <span class="font-mono text-gray-600"><?= htmlspecialchars($s['serial_number'] ?? '-') ?></span>
            </div>
            <div class="flex justify-between text-[11px]">
                <span class="text-gray-400">SIM</span>
                <span class="text-gray-600"><?= htmlspecialchars($s['sim_number'] ?? '-') ?></span>
            </div>
            <div class="flex justify-between text-[11px] pt-1 border-t border-gray-50">
                <span class="text-gray-400">อัปเดต</span>
                <span class="text-gray-500">
                    <?= $lastTs ? date('d/m H:i', $lastTs).' น.' : 'ไม่มีข้อมูล' ?>
                </span>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

    <!-- Map -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <i class="fas fa-map-marked-alt text-teal-600"></i>
            <h2 class="text-base font-semibold text-gray-700">แผนที่สถานีตรวจวัด</h2>
        </div>
        <div id="map"></div>
    </div>

    <!-- AQI Legend -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
        <h3 class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">เกณฑ์คุณภาพอากาศ PM2.5 (µg/m³)</h3>
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
                <span class="text-gray-500"><?= $label ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
const sensors = <?= json_encode(array_map(function ($s) use ($now) {
    return [
        'id'     => (int)$s['id'],
        'name'   => $s['location_name'],
        'cid'    => $s['cid'],
        'lat'    => $s['lat']      ? (float)$s['lat']  : null,
        'lng'    => $s['lng']      ? (float)$s['lng']  : null,
        'pm25'   => $s['pm25_val'],
        'ts'     => $s['last_ts'],
        'online' => $s['last_ts'] && ($now - $s['last_ts']) < 1800,
    ];
}, $sensors)) ?>;

const map = L.map('map').setView([14.0208, 100.7511], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap', maxZoom: 19
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
    const tsStr = s.ts ? new Date(s.ts * 1000).toLocaleString('th-TH') : 'ไม่มีข้อมูล';
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
</script>

<?php include 'admin-layout/footer.php'; ?>
