<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$page_title = 'จัดการสถานี PM2.5';
require_once '../config/database.php';
$pdo = getPDO();

$user = [
    'username'   => $_SESSION['username']  ?? 'User',
    'email'      => $_SESSION['email']     ?? '',
    'full_name'  => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User',
    'first_name' => $_SESSION['first_name'] ?? 'User',
];

$sensors  = [];
$latestPM = [];
try {
    $sensors = $pdo->query("SELECT * FROM pm25_sensors ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);

    $cids = array_column($sensors, 'cid');
    if (!empty($cids)) {
        $in   = implode(',', array_fill(0, count($cids), '?'));
        $stmt = $pdo->prepare("
            SELECT t1.* FROM pm25_data t1
            INNER JOIN (
                SELECT cid, MAX(sensor_timestamp) max_ts
                FROM pm25_data WHERE cid IN ($in) GROUP BY cid
            ) t2 ON t1.cid = t2.cid AND t1.sensor_timestamp = t2.max_ts
        ");
        $stmt->execute($cids);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $latestPM[$r['cid']] = $r;
        }
    }
} catch (Exception $e) {
    // pm25_sensors not created yet
}

$now = time();

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<style>
    #mapModal { display: none; }
    #mapModal.open { display: flex; }
    #editMap { height: 380px; border-radius: 0.5rem; overflow: hidden; }
    * { font-family: 'Kanit', sans-serif; }
</style>

<div class="p-6 max-w-screen-xl mx-auto">

    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-wind text-teal-600"></i>
                จัดการสถานีตรวจวัด PM2.5
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                <?= count($sensors) ?> สถานีทั้งหมด ·
                <?= count(array_filter($sensors, function ($s) { return !empty($s['lat']) && !empty($s['lng']); })) ?>
                สถานีที่กำหนดพิกัดแล้ว
            </p>
        </div>
        <a href="../pm25_dashboard.php" target="_blank"
           class="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors">
            <i class="fas fa-chart-area"></i> เปิด Dashboard
        </a>
    </div>

    <?php if (empty($sensors)): ?>
    <!-- Empty State -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 text-center text-yellow-800">
        <i class="fas fa-exclamation-triangle text-3xl mb-3"></i>
        <p class="font-semibold">ยังไม่มีข้อมูลสถานี</p>
        <p class="text-sm mt-1">กรุณา import ไฟล์
            <code class="bg-yellow-100 px-1 rounded">database/pm25_sensors.sql</code>
            ใน phpMyAdmin ก่อน
        </p>
    </div>
    <?php else: ?>

    <!-- Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-x-auto">
        <table class="w-full text-sm min-w-[800px]">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">#</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">ชื่อสถานที่</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">CID</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">S/N</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">SIM</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">PM2.5 ล่าสุด</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">พิกัด</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">แก้ไข</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
            <?php foreach ($sensors as $s):
                $d        = $latestPM[$s['cid']] ?? null;
                $pm       = $d ? (float)$d['pm25'] : null;
                $isOnline = $d && ($now - (int)$d['sensor_timestamp']) < 1800;
                $hasPin   = !empty($s['lat']) && !empty($s['lng']);
                $sData    = json_encode([
                    'id'     => $s['id'],
                    'name'   => $s['location_name'],
                    'cid'    => $s['cid'],
                    'sn'     => $s['serial_number'],
                    'sim'    => $s['sim_number'],
                    'lat'    => $s['lat'],
                    'lng'    => $s['lng'],
                    'active' => $s['is_active'],
                ], JSON_HEX_QUOT | JSON_HEX_APOS);
            ?>
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-4 py-3 text-gray-400 font-mono text-xs"><?= $s['id'] ?></td>
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($s['location_name']) ?></td>
                <td class="px-4 py-3 font-mono text-gray-600 text-xs"><?= htmlspecialchars($s['cid']) ?></td>
                <td class="px-4 py-3 font-mono text-gray-500 text-xs"><?= htmlspecialchars($s['serial_number'] ?? '-') ?></td>
                <td class="px-4 py-3 text-gray-500 text-xs"><?= htmlspecialchars($s['sim_number'] ?? '-') ?></td>
                <td class="px-4 py-3 text-center">
                    <?php if ($pm !== null): ?>
                        <span class="font-bold text-gray-800"><?= number_format($pm, 1) ?></span>
                        <span class="text-xs text-gray-400">µg/m³</span>
                        <?php if ($isOnline): ?>
                        <span class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-green-500 align-middle"></span>
                        <?php endif; ?>
                        <?php if ($d): ?>
                        <div class="text-[10px] text-gray-400 mt-0.5"><?= date('d/m H:i', (int)$d['sensor_timestamp']) ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <span class="text-gray-300 text-xs">ไม่มีข้อมูล</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center">
                    <?php if ($hasPin): ?>
                        <span class="text-xs text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full font-mono">
                            <?= number_format((float)$s['lat'], 4) ?>,
                            <?= number_format((float)$s['lng'], 4) ?>
                        </span>
                    <?php else: ?>
                        <span class="text-xs text-amber-600 bg-amber-50 px-2 py-0.5 rounded-full">
                            <i class="fas fa-map-pin mr-1"></i>ยังไม่ได้กำหนด
                        </span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center">
                    <button onclick='openEdit(<?= $sData ?>)'
                            class="px-3 py-1.5 bg-blue-600 text-white text-xs rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-edit mr-1"></i>แก้ไข
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<!-- ─── Edit Modal ────────────────────────────────────────── -->
<div id="mapModal"
     class="fixed inset-0 bg-black bg-opacity-50 z-50 items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[92vh] flex flex-col">

        <!-- Modal Header -->
        <div class="p-5 border-b border-gray-100 flex items-center justify-between shrink-0">
            <h3 class="font-bold text-gray-800 text-lg flex items-center gap-2">
                <i class="fas fa-edit text-blue-500"></i>
                แก้ไขข้อมูลสถานี
            </h3>
            <button onclick="closeEdit()"
                    class="text-gray-400 hover:text-gray-600 w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-xl">
                &times;
            </button>
        </div>

        <!-- Modal Body -->
        <div class="p-5 overflow-y-auto space-y-5">
            <input type="hidden" id="editId">

            <!-- General Info -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">ชื่อสถานที่</label>
                    <input type="text" id="editName"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">CID (Device ID)</label>
                    <input type="text" id="editCid"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono bg-gray-50 text-gray-500"
                           readonly>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Serial Number</label>
                    <input type="text" id="editSn"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">หมายเลข SIM</label>
                    <input type="text" id="editSim"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="editActive" class="w-4 h-4 accent-teal-600 cursor-pointer">
                    <label for="editActive" class="text-sm text-gray-700 cursor-pointer">เปิดใช้งาน</label>
                </div>
            </div>

            <!-- Map Pin -->
            <div>
                <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                    <label class="text-xs font-semibold text-gray-600">
                        พิกัดบนแผนที่
                        <span class="font-normal text-gray-400 ml-1">— คลิกบนแผนที่เพื่อกำหนด / ลากหมุดเพื่อปรับตำแหน่ง</span>
                    </label>
                </div>
                <div class="flex gap-3 mb-3">
                    <div class="flex-1">
                        <label class="block text-[10px] text-gray-400 mb-0.5">ละติจูด (Lat)</label>
                        <input type="number" id="editLat" step="0.0000001" placeholder="14.0208..."
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-teal-400">
                    </div>
                    <div class="flex-1">
                        <label class="block text-[10px] text-gray-400 mb-0.5">ลองจิจูด (Lng)</label>
                        <input type="number" id="editLng" step="0.0000001" placeholder="100.7511..."
                               class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-teal-400">
                    </div>
                    <div class="flex items-end">
                        <button onclick="clearPin()"
                                class="px-3 py-2 border border-gray-200 text-gray-500 rounded-lg text-xs hover:bg-gray-50 transition-colors whitespace-nowrap">
                            <i class="fas fa-times mr-1"></i>ล้างพิกัด
                        </button>
                    </div>
                </div>
                <div id="editMap" class="border border-gray-200"></div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="p-5 border-t border-gray-100 flex justify-end gap-3 shrink-0">
            <button onclick="closeEdit()"
                    class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg text-sm hover:bg-gray-50 transition-colors">
                ยกเลิก
            </button>
            <button onclick="saveEdit()"
                    class="px-5 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors flex items-center gap-2">
                <i class="fas fa-save"></i> บันทึก
            </button>
        </div>
    </div>
</div>

<script>
let editMap = null, editMarker = null;

function openEdit(s) {
    document.getElementById('editId').value    = s.id;
    document.getElementById('editName').value  = s.name  || '';
    document.getElementById('editCid').value   = s.cid   || '';
    document.getElementById('editSn').value    = s.sn    || '';
    document.getElementById('editSim').value   = s.sim   || '';
    document.getElementById('editLat').value   = s.lat   || '';
    document.getElementById('editLng').value   = s.lng   || '';
    document.getElementById('editActive').checked = s.active == 1;

    document.getElementById('mapModal').classList.add('open');

    // Leaflet must init after the container is visible
    setTimeout(() => {
        if (!editMap) {
            editMap = L.map('editMap').setView([14.0208, 100.7511], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap', maxZoom: 19
            }).addTo(editMap);
            editMap.on('click', e => placeMarker(e.latlng.lat, e.latlng.lng));
        }
        editMap.invalidateSize();

        if (editMarker) { editMap.removeLayer(editMarker); editMarker = null; }

        const lat = parseFloat(s.lat), lng = parseFloat(s.lng);
        if (!isNaN(lat) && !isNaN(lng)) {
            placeMarker(lat, lng);
            editMap.setView([lat, lng], 16);
        } else {
            editMap.setView([14.0208, 100.7511], 13);
        }
    }, 120);
}

function placeMarker(lat, lng) {
    if (editMarker) editMap.removeLayer(editMarker);
    editMarker = L.marker([lat, lng], { draggable: true })
        .addTo(editMap)
        .bindPopup('<span style="font-family:Kanit,sans-serif">ลากเพื่อปรับตำแหน่ง</span>')
        .openPopup();
    editMarker.on('dragend', function (e) {
        const p = e.target.getLatLng();
        document.getElementById('editLat').value = p.lat.toFixed(7);
        document.getElementById('editLng').value = p.lng.toFixed(7);
    });
    document.getElementById('editLat').value = lat.toFixed(7);
    document.getElementById('editLng').value = lng.toFixed(7);
}

function clearPin() {
    if (editMarker) { editMap.removeLayer(editMarker); editMarker = null; }
    document.getElementById('editLat').value = '';
    document.getElementById('editLng').value = '';
}

function closeEdit() {
    document.getElementById('mapModal').classList.remove('open');
}

function saveEdit() {
    const data = {
        action: 'update',
        id:     document.getElementById('editId').value,
        name:   document.getElementById('editName').value.trim(),
        sn:     document.getElementById('editSn').value.trim(),
        sim:    document.getElementById('editSim').value.trim(),
        lat:    document.getElementById('editLat').value,
        lng:    document.getElementById('editLng').value,
        active: document.getElementById('editActive').checked ? 1 : 0,
    };

    if (!data.name) {
        Swal.fire({ icon: 'warning', title: 'กรุณากรอกชื่อสถานที่', confirmButtonColor: '#0d9488' });
        return;
    }

    fetch('api/pm25_sensor_api.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            Swal.fire({ icon: 'success', title: 'บันทึกสำเร็จ', timer: 1500, showConfirmButton: false })
                .then(() => location.reload());
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: res.message || '' });
        }
    })
    .catch(() => Swal.fire({ icon: 'error', title: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' }));
}

// Update marker when lat/lng inputs change manually
['editLat', 'editLng'].forEach(id => {
    document.getElementById(id).addEventListener('change', () => {
        const lat = parseFloat(document.getElementById('editLat').value);
        const lng = parseFloat(document.getElementById('editLng').value);
        if (!isNaN(lat) && !isNaN(lng) && editMap) {
            placeMarker(lat, lng);
            editMap.setView([lat, lng], 16);
        }
    });
});
</script>

<?php include 'admin-layout/footer.php'; ?>
