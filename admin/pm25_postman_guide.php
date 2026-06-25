<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'] ?? '', ['admin','staff'])) {
    header('Location: ../login.php'); exit;
}
// เฉพาะ thanong เท่านั้น
if (($_SESSION['username'] ?? '') !== 'thanong') {
    http_response_code(403);
    echo '<p style="font-family:sans-serif;padding:40px;color:#ef4444">403 — ไม่มีสิทธิ์เข้าถึงหน้านี้</p>';
    exit;
}

require_once '../config/database.php';
$current_page = 'pm25_postman_guide';

// ── ดึงรายการ CID จาก sensors ────────────────────────────────────────────────
$sensors = [];
try {
    $pdo     = getPDO();
    $sensors = $pdo->query("SELECT id, location_name, cid FROM pm25_sensors WHERE is_active=1 ORDER BY id")
                   ->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';

$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhY2NvdW50Ijp7ImVtYWlsIjoicmFuZ3NpdDYwMDBAZ21haWwuY29tIiwiYXBpIjp7ImFsbG93Ijp0cnVlLCJkZXZpY2UiOjgsImV4cGlyZSI6eyJfc2Vjb25kcyI6MTg0MDcyNjc1NSwiX25hbm9zZWNvbmRzIjowfX19LCJpYXQiOjE3Nzc0Mzc4MDA5NTIsImV4cCI6MTc3NzUyNDIwMDk1Mn0.Hmxg5PooqCf1c2Rj6PMwVFXzBhSlGCGxel7DYeuk10M';
$apiUrl = 'https://app.freshnergy.com/api/v2/device';
?>

<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
* { font-family: 'Kanit', sans-serif; }
.code-block {
    background: #0f172a; color: #e2e8f0; border-radius: 10px;
    padding: 16px 20px; font-family: 'Courier New', monospace;
    font-size: 13px; line-height: 1.7; overflow-x: auto; position: relative;
}
.code-block .key   { color: #93c5fd; }
.code-block .val   { color: #86efac; }
.code-block .str   { color: #fde68a; }
.code-block .cmt   { color: #475569; }
.token-box {
    background: #0f172a; color: #34d399; border-radius: 10px;
    padding: 14px 18px; font-family: 'Courier New', monospace;
    font-size: 11.5px; word-break: break-all; line-height: 1.6; position: relative;
}
.copy-btn {
    position: absolute; top: 10px; right: 10px;
    background: #1e293b; color: #94a3b8; border: 1px solid #334155;
    border-radius: 6px; padding: 4px 10px; font-size: 11px; cursor: pointer;
    font-family: 'Kanit', sans-serif; transition: all .2s;
}
.copy-btn:hover { background: #0d9488; color: #fff; border-color: #0d9488; }
.step-num {
    width: 28px; height: 28px; border-radius: 50%;
    background: #0d9488; color: #fff;
    display: flex; align-items: center; justify-content: center;
    font-size: 13px; font-weight: 700; flex-shrink: 0;
}
.tag { display: inline-block; padding: 2px 10px; border-radius: 999px; font-size: 11px; font-weight: 600; }
.tag-post { background: #fef3c7; color: #92400e; }
.tag-get  { background: #d1fae5; color: #065f46; }
.tag-auth { background: #ede9fe; color: #5b21b6; }
.img-step { border: 2px solid #e2e8f0; border-radius: 10px; }
table.params td, table.params th { padding: 8px 14px; font-size: 13px; }
table.params th { background: #f8fafc; color: #475569; font-weight: 600; }
table.params td { border-top: 1px solid #f1f5f9; }
</style>

<div class="p-6 max-w-4xl mx-auto">

    <!-- Title -->
    <div class="flex items-center gap-3 mb-6">
        <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
            <i class="fas fa-book-open text-orange-600"></i>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-800">คู่มือทดสอบ Freshnergy API ด้วย Postman</h1>
            <p class="text-sm text-gray-400">สำหรับตรวจสอบว่าค่าที่ได้จาก API ตรงกับที่บันทึกในฐานข้อมูล</p>
        </div>
        <span class="ml-auto text-xs text-slate-400">🔒 เฉพาะผู้ดูแลระบบ</span>
    </div>

    <!-- ─── 1. ข้อมูล API ─────────────────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <h2 class="text-base font-bold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fas fa-server text-teal-500"></i> ข้อมูล API Endpoint
        </h2>
        <table class="w-full text-sm mb-4">
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-4 text-gray-400 font-medium w-32">Endpoint</td>
                <td class="py-2 font-mono text-blue-600"><?= $apiUrl ?></td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-4 text-gray-400 font-medium">Method</td>
                <td class="py-2"><span class="tag tag-post">POST</span></td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-4 text-gray-400 font-medium">Content-Type</td>
                <td class="py-2 font-mono text-sm">application/json</td>
            </tr>
            <tr>
                <td class="py-2 pr-4 text-gray-400 font-medium">Auth</td>
                <td class="py-2"><span class="tag tag-auth">Bearer Token (JWT)</span></td>
            </tr>
        </table>

        <p class="text-sm font-semibold text-gray-600 mb-2">Authorization Token (ใช้งานได้ปัจจุบัน):</p>
        <div class="token-box relative">
            <button class="copy-btn" onclick="copyText('token-val', this)"><i class="fas fa-copy"></i> คัดลอก</button>
            <span id="token-val"><?= $token ?></span>
        </div>
    </div>

    <!-- ─── 2. รายการ CID ────────────────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <h2 class="text-base font-bold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fas fa-satellite-dish text-teal-500"></i> รายการ CID (Device ID) ทั้ง <?= count($sensors) ?> สถานี
        </h2>
        <table class="w-full params rounded-xl overflow-hidden border border-gray-100">
            <thead><tr>
                <th class="text-left">#</th>
                <th class="text-left">สถานี</th>
                <th class="text-left">CID</th>
                <th class="text-left">คัดลอก</th>
            </tr></thead>
            <tbody>
            <?php foreach ($sensors as $s): ?>
            <tr>
                <td class="text-gray-400"><?= $s['id'] ?></td>
                <td><?= htmlspecialchars($s['location_name']) ?></td>
                <td><code class="bg-gray-50 px-2 py-0.5 rounded text-blue-600 text-xs"><?= $s['cid'] ?></code></td>
                <td>
                    <button onclick="copyText('cid_<?= $s['id'] ?>', this)" class="text-xs text-teal-600 hover:text-teal-800">
                        <i class="fas fa-copy"></i>
                    </button>
                    <span id="cid_<?= $s['id'] ?>" style="display:none"><?= $s['cid'] ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ─── 3. วิธีใช้ Postman ──────────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <h2 class="text-base font-bold text-gray-700 flex items-center gap-2 mb-5">
            <i class="fas fa-vial text-orange-500"></i> ขั้นตอนทดสอบด้วย Postman
        </h2>

        <!-- Step 1 -->
        <div class="flex gap-3 mb-6">
            <div class="step-num">1</div>
            <div class="flex-1">
                <p class="font-semibold text-gray-700 mb-2">ตั้งค่า Request ใหม่</p>
                <ul class="text-sm text-gray-500 space-y-1 list-disc list-inside">
                    <li>เปิด Postman → <strong>New</strong> → <strong>HTTP Request</strong></li>
                    <li>เปลี่ยน Method เป็น <span class="tag tag-post">POST</span></li>
                    <li>ใส่ URL: <code class="bg-gray-100 px-2 py-0.5 rounded text-blue-600 text-xs"><?= $apiUrl ?></code></li>
                </ul>
            </div>
        </div>

        <!-- Step 2 -->
        <div class="flex gap-3 mb-6">
            <div class="step-num">2</div>
            <div class="flex-1">
                <p class="font-semibold text-gray-700 mb-2">ตั้งค่า Authorization</p>
                <ul class="text-sm text-gray-500 space-y-1 list-disc list-inside mb-3">
                    <li>ไปที่ Tab <strong>Headers</strong></li>
                    <li>เพิ่ม Key: <code class="bg-gray-100 px-2 rounded">Authorization</code></li>
                    <li>Value: ใส่ token ด้านล่างทั้งหมด (ไม่ต้องใส่คำว่า Bearer นำหน้า)</li>
                </ul>
                <div class="token-box text-xs relative">
                    <button class="copy-btn" onclick="copyText('token-val2', this)"><i class="fas fa-copy"></i> คัดลอก</button>
                    <span id="token-val2"><?= $token ?></span>
                </div>
            </div>
        </div>

        <!-- Step 3 -->
        <div class="flex gap-3 mb-6">
            <div class="step-num">3</div>
            <div class="flex-1">
                <p class="font-semibold text-gray-700 mb-2">ตั้งค่า Body (JSON)</p>
                <ul class="text-sm text-gray-500 space-y-1 list-disc list-inside mb-3">
                    <li>ไปที่ Tab <strong>Body</strong> → เลือก <strong>raw</strong> → เลือกประเภท <strong>JSON</strong></li>
                    <li>ใส่ข้อมูลดังนี้ (เลือก 1 CID ต่อ request)</li>
                </ul>
                <div class="code-block relative">
                    <button class="copy-btn" onclick="copyText('body-ex', this)"><i class="fas fa-copy"></i> คัดลอก</button>
                    <pre id="body-ex">{
  <span class="key">"cid"</span>: [<span class="str">"<?= $sensors[0]['cid'] ?? '349454E09A5C' ?>"</span>]
}</pre>
                </div>
                <p class="text-xs text-gray-400 mt-2">💡 ส่งทีละ 1 CID เท่านั้น — หลาย CID พร้อมกันอาจได้ HTTP 500</p>
            </div>
        </div>

        <!-- Step 4 -->
        <div class="flex gap-3 mb-6">
            <div class="step-num">4</div>
            <div class="flex-1">
                <p class="font-semibold text-gray-700 mb-2">กด <strong>Send</strong> และตรวจสอบ Response</p>
                <p class="text-sm text-gray-500 mb-3">Response ที่ถูกต้องจะมีโครงสร้างดังนี้:</p>
                <div class="code-block">
<pre>{
  <span class="key">"status"</span>: <span class="str">"success"</span>,
  <span class="key">"data"</span>: [
    {
      <span class="key">"cid"</span>: <span class="str">"349454E09A5C"</span>,
      <span class="key">"sensor"</span>: {
        <span class="key">"co2"</span>:   <span class="val">437</span>,
        <span class="key">"humi"</span>:  <span class="val">65.1</span>,
        <span class="key">"pm1"</span>:   <span class="val">9</span>,
        <span class="key">"pm10"</span>:  <span class="val">19</span>,
        <span class="key">"pm2_5"</span>: <span class="val">25</span>,   <span class="cmt">← ชื่อ field pm2_5 (underscore) บันทึกเป็นคอลัมน์ pm25</span>
        <span class="key">"temp"</span>:  <span class="val">32.6</span>
      },
      <span class="key">"timeStamp"</span>: <span class="val">1778512095</span>  <span class="cmt">← Unix timestamp อยู่นอก sensor object</span>
    }
  ]
}</pre>
                </div>
            </div>
        </div>

        <!-- Step 5 -->
        <div class="flex gap-3">
            <div class="step-num">5</div>
            <div class="flex-1">
                <p class="font-semibold text-gray-700 mb-2">เปรียบเทียบกับข้อมูลในฐานข้อมูล</p>
                <div class="code-block mb-3">
                    <pre><span class="cmt">-- ดูค่าล่าสุดของสถานีนั้น</span>
SELECT pm25, pm1, pm10, temperature, humidity, co2,
       FROM_UNIXTIME(sensor_timestamp) AS recorded_at,
       created_at
FROM pm25_data
WHERE cid = <span class="str">'349454E09A5C'</span>
ORDER BY sensor_timestamp DESC
LIMIT 5;</pre>
                </div>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800">
                    <p class="font-semibold mb-2">⚠️ สิ่งที่ต้องตรวจสอบ:</p>
                    <ul class="space-y-1 list-disc list-inside text-amber-700">
                        <li><code>pm2_5</code> ใน API = <code>pm25</code> ในตาราง (field name ต่างกัน)</li>
                        <li><code>timeStamp</code> ใน API = <code>sensor_timestamp</code> ในตาราง</li>
                        <li><code>temp</code> ใน API = <code>temperature</code> ในตาราง</li>
                        <li><code>humi</code> ใน API = <code>humidity</code> ในตาราง</li>
                        <li>ค่า <code>sensor_timestamp</code> ต้องตรงกัน — ถ้า cron ยังไม่รันจะยังไม่เห็นข้อมูลใหม่</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- ─── 4. Field Mapping ─────────────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <h2 class="text-base font-bold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fas fa-exchange-alt text-teal-500"></i> Field Mapping: API → Database
        </h2>
        <table class="w-full params rounded-xl overflow-hidden border border-gray-100">
            <thead><tr>
                <th class="text-left">API Response Field</th>
                <th class="text-left">DB Column (pm25_data)</th>
                <th class="text-left">หมายเหตุ</th>
            </tr></thead>
            <tbody>
            <?php foreach ([
                ['data[].cid',            'cid',              'Device ID เหมือนกันทั้งคู่'],
                ['data[].timeStamp',       'sensor_timestamp', 'Unix timestamp (อยู่นอก sensor{})'],
                ['data[].sensor.pm2_5',    'pm25',             'ชื่อต่างกัน: underscore vs ไม่มี'],
                ['data[].sensor.pm1',      'pm1',              '–'],
                ['data[].sensor.pm10',     'pm10',             '–'],
                ['data[].sensor.temp',     'temperature',      'ชื่อย่อ vs ชื่อเต็ม'],
                ['data[].sensor.humi',     'humidity',         'ชื่อย่อ vs ชื่อเต็ม'],
                ['data[].sensor.co2',      'co2',              '–'],
                ['(server time INSERT)',    'created_at',       'เวลาที่ cron บันทึก ≠ sensor_timestamp'],
            ] as [$api, $db, $note]): ?>
            <tr>
                <td><code class="text-blue-600 text-xs bg-blue-50 px-2 py-0.5 rounded"><?= $api ?></code></td>
                <td><code class="text-green-700 text-xs bg-green-50 px-2 py-0.5 rounded"><?= $db ?></code></td>
                <td class="text-gray-400 text-xs"><?= $note ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- ─── 5. Cron Info ─────────────────────────────────────────────────────── -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 mb-5">
        <h2 class="text-base font-bold text-gray-700 flex items-center gap-2 mb-4">
            <i class="fas fa-clock text-teal-500"></i> ข้อมูล Cron Job
        </h2>
        <table class="w-full text-sm">
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-4 text-gray-400 font-medium w-36">Script</td>
                <td class="py-2 font-mono text-xs">/home/rangsitadmin/public_html/iservice.rangsitcity.go.th/pm25_cron.php</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-4 text-gray-400 font-medium">ตัวรัน</td>
                <td class="py-2">cron-job.org (ฟรี) — cPanel cron ไม่ทำงานบน hosting นี้</td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-4 text-gray-400 font-medium">ความถี่</td>
                <td class="py-2">ทุก <strong>5 นาที</strong></td>
            </tr>
            <tr class="border-b border-gray-100">
                <td class="py-2 pr-4 text-gray-400 font-medium">Log file</td>
                <td class="py-2 font-mono text-xs">storage/pm25_cron.log</td>
            </tr>
            <tr>
                <td class="py-2 pr-4 text-gray-400 font-medium">Account API</td>
                <td class="py-2">rangsit6000@gmail.com · quota 8 devices</td>
            </tr>
        </table>
        <div class="mt-4">
            <a href="../storage/pm25_cron.log" target="_blank"
               class="inline-flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-xl text-sm hover:bg-slate-700 transition-colors">
                <i class="fas fa-file-alt"></i> ดู Log ล่าสุด
            </a>
        </div>
    </div>

</div>

<script>
function copyText(id, btn) {
    const el = document.getElementById(id);
    const text = el.textContent || el.innerText;
    navigator.clipboard.writeText(text.trim()).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> คัดลอกแล้ว';
        btn.style.background = '#0d9488';
        btn.style.color = '#fff';
        setTimeout(() => { btn.innerHTML = orig; btn.style.background = ''; btn.style.color = ''; }, 2000);
    });
}
</script>

<?php include 'admin-layout/footer.php'; ?>
