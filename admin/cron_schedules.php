<?php
/**
 * Cron Schedules — self-service job scheduler (replaces cron-job.org)
 * One OS-level crontab entry runs cron/runner.php every minute; jobs added/edited
 * here take effect immediately, no SSH needed.
 */

require_once '../config/database.php';
session_start();
require_admin_role();

$page_title   = 'Cron Jobs';
$current_page = 'cron_schedules';
$breadcrumb   = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'Cron Jobs']
];

$pdo = getPDO();
$msg = '';

// ── Create ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create') {
    $name     = trim($_POST['name'] ?? '');
    $url      = trim($_POST['target_url'] ?? '');
    $interval = max(1, (int)($_POST['interval_minutes'] ?? 15));

    if ($name && filter_var($url, FILTER_VALIDATE_URL)) {
        $pdo->prepare("INSERT INTO cron_schedules (name, target_url, interval_minutes) VALUES (?, ?, ?)")
            ->execute([$name, $url, $interval]);
        $msg = ['type' => 'success', 'text' => 'เพิ่ม Cron Job สำเร็จ'];
    } else {
        $msg = ['type' => 'error', 'text' => 'กรุณากรอกชื่อและ URL ให้ถูกต้อง'];
    }
}

// ── Update ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $url      = trim($_POST['target_url'] ?? '');
    $interval = max(1, (int)($_POST['interval_minutes'] ?? 15));

    if ($id && $name && filter_var($url, FILTER_VALIDATE_URL)) {
        $pdo->prepare("UPDATE cron_schedules SET name = ?, target_url = ?, interval_minutes = ? WHERE id = ?")
            ->execute([$name, $url, $interval, $id]);
        $msg = ['type' => 'success', 'text' => 'บันทึกการเปลี่ยนแปลงสำเร็จ'];
    } else {
        $msg = ['type' => 'error', 'text' => 'กรุณากรอกชื่อและ URL ให้ถูกต้อง'];
    }
}

// ── Toggle active ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("UPDATE cron_schedules SET is_active = 1 - is_active WHERE id = ?")->execute([$id]);
    }
    header('Location: cron_schedules.php'); exit;
}

// ── Delete ───────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id) {
        $pdo->prepare("DELETE FROM cron_schedules WHERE id = ?")->execute([$id]);
    }
    header('Location: cron_schedules.php'); exit;
}

// ── Run now ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run_now') {
    $id  = (int)($_POST['id'] ?? 0);
    $job = $pdo->prepare("SELECT * FROM cron_schedules WHERE id = ?");
    $job->execute([$id]);
    $job = $job->fetch(PDO::FETCH_ASSOC);

    if ($job) {
        $start = microtime(true);
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $job['target_url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body     = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);
        $durationMs = (int)round((microtime(true) - $start) * 1000);

        if ($curlErr) {
            $status = 'error'; $message = "cURL error: $curlErr";
        } elseif ($httpCode >= 200 && $httpCode < 300) {
            $status = 'success'; $message = substr((string)$body, 0, 500);
        } else {
            $status = 'error'; $message = "HTTP $httpCode" . ($body ? ' — ' . substr((string)$body, 0, 300) : '');
        }

        $pdo->prepare("UPDATE cron_schedules SET last_run_at = NOW(), last_status = ?, last_http_code = ?, last_message = ? WHERE id = ?")
            ->execute([$status, $httpCode ?: null, $message, $id]);
        $pdo->prepare("INSERT INTO cron_schedule_runs (schedule_id, status, http_code, message, duration_ms) VALUES (?, ?, ?, ?, ?)")
            ->execute([$id, $status, $httpCode ?: null, $message, $durationMs]);

        $msg = $status === 'success'
            ? ['type' => 'success', 'text' => "รัน &quot;{$job['name']}&quot; สำเร็จ (HTTP $httpCode, {$durationMs}ms)"]
            : ['type' => 'error',   'text' => "รัน &quot;{$job['name']}&quot; ล้มเหลว: " . htmlspecialchars($message)];
    }
}

$jobs = $pdo->query("SELECT * FROM cron_schedules ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Recent run history per job (last 5 each), for the expandable history row
$history = [];
if ($jobs) {
    $ids = implode(',', array_map('intval', array_column($jobs, 'id')));
    $rows = $pdo->query("
        SELECT * FROM (
            SELECT *, ROW_NUMBER() OVER (PARTITION BY schedule_id ORDER BY ran_at DESC) AS rn
            FROM cron_schedule_runs WHERE schedule_id IN ($ids)
        ) t WHERE rn <= 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $history[$r['schedule_id']][] = $r;
    }
}

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<div class="p-6 max-w-5xl mx-auto">

    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fas fa-clock text-teal-600"></i> Cron Jobs
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                ตั้งเวลายิง URL อัตโนมัติ (ทดแทน cron-job.org) — ทำงานผ่าน <code class="text-xs">cron/runner.php</code>
                ที่รันทุกนาทีบนเซิร์ฟเวอร์ จากตารางนี้โดยตรง ไม่ต้อง SSH
            </p>
        </div>
    </div>

    <?php if ($msg): ?>
    <div class="mb-4 p-4 rounded-xl <?= $msg['type'] === 'success' ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800' ?>">
        <?= $msg['text'] ?>
    </div>
    <?php endif; ?>

    <!-- Create Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-plus text-teal-500"></i> เพิ่ม Cron Job ใหม่
        </h2>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อ Job <span class="text-red-400">*</span></label>
                    <input type="text" name="name" required placeholder="เช่น PM2.5 Data Sync"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Target URL <span class="text-red-400">*</span></label>
                    <input type="url" name="target_url" required placeholder="https://iservice.rangsitcity.go.th/xxx.php"
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">ความถี่</label>
                    <select name="interval_minutes" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                        <option value="5">ทุก 5 นาที</option>
                        <option value="15" selected>ทุก 15 นาที</option>
                        <option value="30">ทุก 30 นาที</option>
                        <option value="60">ทุก 1 ชั่วโมง</option>
                        <option value="1440">ทุก 1 วัน</option>
                    </select>
                </div>
            </div>
            <div class="mt-4">
                <button type="submit"
                        class="px-5 py-2 bg-teal-600 text-white rounded-lg text-sm font-medium hover:bg-teal-700 transition-colors flex items-center gap-2">
                    <i class="fas fa-plus"></i> เพิ่ม Job
                </button>
            </div>
        </form>
    </div>

    <!-- Jobs Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">ชื่อ Job</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase">URL</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">ความถี่</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">รันล่าสุด</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">สถานะ</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">เปิด/ปิด</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
            <?php if (empty($jobs)): ?>
            <tr><td colspan="7" class="text-center py-10 text-gray-300 text-sm">ยังไม่มี Cron Job</td></tr>
            <?php endif; ?>
            <?php foreach ($jobs as $j):
                $isActive = (int)$j['is_active'] === 1;
            ?>
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($j['name']) ?></td>
                <td class="px-4 py-3">
                    <code class="text-xs font-mono text-slate-600 break-all"><?= htmlspecialchars($j['target_url']) ?></code>
                </td>
                <td class="px-4 py-3 text-center text-xs text-gray-600">
                    <?= $j['interval_minutes'] >= 1440 ? 'ทุกวัน' : "ทุก {$j['interval_minutes']} นาที" ?>
                </td>
                <td class="px-4 py-3 text-center text-xs text-gray-500">
                    <?= $j['last_run_at'] ? date('d/m H:i:s', strtotime($j['last_run_at'])) : '— ยังไม่รัน —' ?>
                </td>
                <td class="px-4 py-3 text-center">
                    <?php if ($j['last_status'] === 'success'): ?>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-700">✓ <?= $j['last_http_code'] ?></span>
                    <?php elseif ($j['last_status'] === 'error'): ?>
                        <span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700" title="<?= htmlspecialchars($j['last_message'] ?? '') ?>">✗ <?= $j['last_http_code'] ?: 'error' ?></span>
                    <?php else: ?>
                        <span class="text-xs text-gray-300">—</span>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-3 text-center">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= $j['id'] ?>">
                        <button type="submit"
                                class="text-xs px-2 py-0.5 rounded-full font-medium <?= $isActive ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $isActive ? '● เปิด' : '○ ปิด' ?>
                        </button>
                    </form>
                </td>
                <td class="px-4 py-3 text-center whitespace-nowrap">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="run_now">
                        <input type="hidden" name="id" value="<?= $j['id'] ?>">
                        <button type="submit" title="Run Now" class="text-teal-500 hover:text-teal-700 text-xs mr-2">
                            <i class="fas fa-play-circle"></i>
                        </button>
                    </form>
                    <button type="button" title="แก้ไข" onclick='openEdit(<?= json_encode($j) ?>)' class="text-blue-400 hover:text-blue-600 text-xs mr-2">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" class="inline" onsubmit="return confirm('ลบ Cron Job นี้?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $j['id'] ?>">
                        <button type="submit" title="ลบ" class="text-red-400 hover:text-red-600 text-xs">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php if (!empty($history[$j['id']])): ?>
            <tr>
                <td colspan="7" class="px-4 py-2 bg-gray-50">
                    <div class="text-xs text-gray-400 flex flex-wrap gap-3">
                        <span class="font-semibold text-gray-500">ประวัติล่าสุด:</span>
                        <?php foreach ($history[$j['id']] as $h): ?>
                        <span class="<?= $h['status'] === 'success' ? 'text-green-600' : 'text-red-500' ?>">
                            <?= date('d/m H:i', strtotime($h['ran_at'])) ?> (<?= $h['http_code'] ?: '—' ?>, <?= $h['duration_ms'] ?>ms)
                        </span>
                        <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endif; ?>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-md mx-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-edit text-blue-500"></i> แก้ไข Cron Job
        </h3>
        <form method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="id" id="edit_id">
            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">ชื่อ Job</label>
                    <input type="text" name="name" id="edit_name" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">Target URL</label>
                    <input type="url" name="target_url" id="edit_target_url" required
                           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-500 mb-1">ความถี่</label>
                    <select name="interval_minutes" id="edit_interval" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                        <option value="5">ทุก 5 นาที</option>
                        <option value="15">ทุก 15 นาที</option>
                        <option value="30">ทุก 30 นาที</option>
                        <option value="60">ทุก 1 ชั่วโมง</option>
                        <option value="1440">ทุก 1 วัน</option>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <button type="button" onclick="closeEdit()" class="px-4 py-2 text-sm rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50">ยกเลิก</button>
                <button type="submit" class="px-4 py-2 text-sm rounded-lg bg-teal-600 text-white hover:bg-teal-700">บันทึก</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(job) {
    document.getElementById('edit_id').value = job.id;
    document.getElementById('edit_name').value = job.name;
    document.getElementById('edit_target_url').value = job.target_url;
    document.getElementById('edit_interval').value = job.interval_minutes;
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}
function closeEdit() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}
</script>

<?php include 'admin-layout/footer.php'; ?>
