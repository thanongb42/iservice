<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }

$page_title   = 'จัดการ API Keys PM2.5';
$current_page = 'pm25_api_keys';
date_default_timezone_set('Asia/Bangkok');
require_once '../config/database.php';
$pdo = getPDO();

$msg = '';

// ── Create key ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $name    = trim($_POST['name']    ?? '');
    $desc    = trim($_POST['desc']    ?? '');
    $expires = trim($_POST['expires'] ?? '') ?: null;

    if ($name) {
        $key = 'PM25_' . strtoupper(bin2hex(random_bytes(16)));
        $pdo->prepare("INSERT INTO pm25_api_keys (api_key, name, description, expires_at) VALUES (?,?,?,?)")
            ->execute([$key, $name, $desc ?: null, $expires]);
        $msg = ['type' => 'success', 'text' => "สร้าง API Key สำเร็จ: <code class='font-mono'>$key</code>"];
    }
}

// ── Toggle active ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("UPDATE pm25_api_keys SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    }
    header('Location: pm25_api_keys.php'); exit;
}

// ── Delete key ───────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("DELETE FROM pm25_api_keys WHERE id = ?")->execute([$id]);
    }
    header('Location: pm25_api_keys.php'); exit;
}

$keys = $pdo->query("SELECT * FROM pm25_api_keys ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<div class="p-6 max-w-5xl mx-auto">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-key text-teal-600"></i>
                จัดการ API Keys PM2.5
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">สำหรับอนุญาตระบบภายนอกดึงข้อมูล PM2.5</p>
        </div>
        <a href="pm25_dashboard.php" class="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white rounded-lg text-sm hover:bg-teal-700">
            <i class="fas fa-chart-area"></i> Dashboard
        </a>
    </div>

    <?php if ($msg): ?>
    <div class="mb-4 p-4 rounded-xl <?= $msg['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
        <?= $msg['text'] ?>
    </div>
    <?php endif; ?>

    <!-- API Usage -->
    <div class="bg-slate-800 text-slate-100 rounded-xl p-5 mb-6 text-sm font-mono">
        <div class="text-slate-400 text-xs mb-3 font-sans font-semibold uppercase tracking-wide">วิธีใช้งาน API</div>
        <div class="space-y-2 text-xs">
            <div><span class="text-green-400">GET</span> <span class="text-slate-200">https://iservice.rangsitcity.go.th/api/pm25_public.php</span></div>
            <div class="text-slate-400"># Header</div>
            <div><span class="text-yellow-300">Authorization:</span> Bearer &lt;api_key&gt;</div>
            <div class="text-slate-400 mt-2"># หรือ Query param</div>
            <div><span class="text-slate-200">?api_key=</span>&lt;api_key&gt;</div>
            <div class="mt-3 text-slate-400"># Parameters</div>
            <div><span class="text-blue-300">?type=latest</span> <span class="text-slate-400">— ค่าล่าสุดทุกสถานี (default)</span></div>
            <div><span class="text-blue-300">?type=history&amp;hours=24</span> <span class="text-slate-400">— ย้อนหลัง 24 ชั่วโมง (max 168)</span></div>
            <div><span class="text-blue-300">&amp;cid=349454E09A5C</span> <span class="text-slate-400">— กรองสถานีเดียว (optional)</span></div>
        </div>
    </div>

    <!-- Create Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-plus text-teal-500"></i> สร้าง API Key ใหม่
        </h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อระบบ / องค์กร <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="เช่น ระบบ Dashboard กรมฯ"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">หมายเหตุ</label>
                    <input type="text" name="desc" placeholder="รายละเอียดการใช้งาน"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div class="sm:col-span-1">
                    <label class="block text-xs font-semibold text-gray-500 mb-1">วันหมดอายุ (ว่าง = ไม่หมดอายุ)</label>
                    <input type="date" name="expires"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-plus"></i> สร้าง API Key
                </button>
            </div>
        </form>
    </div>

    <!-- Keys Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ชื่อระบบ</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">API Key</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">หมดอายุ</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">ใช้ล่าสุด</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Requests</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
            <?php if (empty($keys)): ?>
            <tr><td colspan="7" class="text-center py-10 text-gray-300 text-sm">ยังไม่มี API Key</td></tr>
            <?php endif; ?>
            <?php foreach ($keys as $k):
                $isActive  = (int)$k['is_active'] === 1;
                $isExpired = $k['expires_at'] && $k['expires_at'] < date('Y-m-d');
            ?>
            <tr class="hover:bg-gray-50 <?= $isExpired ? 'opacity-60' : '' ?>">
                <td class="px-4 py-3">
                    <div class="font-medium text-gray-800"><?= htmlspecialchars($k['name']) ?></div>
                    <?php if ($k['description']): ?>
                    <div class="text-xs text-gray-400 mt-0.5"><?= htmlspecialchars($k['description']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <code class="text-xs font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700 select-all" id="key<?= $k['id'] ?>">
                            <?= htmlspecialchars($k['api_key']) ?>
                        </code>
                        <button onclick="copyKey('key<?= $k['id'] ?>')" title="Copy"
                                class="text-slate-400 hover:text-teal-600 transition-colors text-xs">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-center text-xs">
                    <?php if ($k['expires_at']): ?>
                        <span class="<?= $isExpired ? 'text-red-500 font-semibold' : 'text-gray-600' ?>">
                            <?= $isExpired ? '⚠️ ' : '' ?><?= $k['expires_at'] ?>
                        </span>
                    <?php else: ?>
                        <span class="text-gray-300">ไม่หมดอายุ</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center text-xs text-gray-500">
                    <?= $k['last_used_at'] ? date('d/m H:i', strtotime($k['last_used_at'])) : '—' ?>
                </td>
                <td class="px-4 py-3 text-center text-xs font-mono text-gray-600">
                    <?= number_format($k['request_count']) ?>
                </td>
                <td class="px-4 py-3 text-center">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button type="submit"
                                class="text-xs px-2 py-0.5 rounded-full font-medium
                                    <?= $isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $isActive ? '● เปิด' : '○ ปิด' ?>
                        </button>
                    </form>
                </td>
                <td class="px-4 py-3 text-center">
                    <form method="POST" onsubmit="return confirm('ลบ API Key นี้?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<script>
function copyKey(id) {
    const el = document.getElementById(id);
    navigator.clipboard.writeText(el.textContent.trim()).then(() => {
        Swal.fire({ icon: 'success', title: 'คัดลอกแล้ว', timer: 1000, showConfirmButton: false });
    });
}
</script>

<?php include 'admin-layout/footer.php'; ?>
