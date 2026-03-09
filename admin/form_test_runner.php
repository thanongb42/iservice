<?php
/**
 * Form Test Runner — ทดสอบการส่งฟอร์มคำขอบริการโดยอัตโนมัติ
 * เข้าถึงได้เฉพาะ admin เท่านั้น
 */
session_start();
require_once '../config/database.php';
require_manager_or_admin();

$page_title   = 'ทดสอบฟอร์มคำขอ (Auto Test)';
$current_page = 'form_test_runner';
$breadcrumb   = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'ทดสอบฟอร์มคำขอ'],
];

// ── Detect base URL ──────────────────────────────────────────────────────────
$proto     = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) $proto = strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']);
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
$base_url  = $proto . '://127.0.0.1' . $base_path;   // ใช้ 127.0.0.1 เสมอ (local call)
$api_url   = $base_url . '/api/process_request.php';

// ── Fetch services & departments จาก DB ─────────────────────────────────────
$services = [];
$res = $conn->query("SELECT service_code, service_name FROM my_service WHERE is_active=1 ORDER BY service_code");
if ($res) while ($r = $res->fetch_assoc()) $services[$r['service_code']] = $r['service_name'];

$dept_id = 0;
$dept_name = '';
$res = $conn->query("SELECT department_id, department_name FROM departments LIMIT 1");
if ($res && $r = $res->fetch_assoc()) { $dept_id = $r['department_id']; $dept_name = $r['department_name']; }

$prefix_id = 1;
$res = $conn->query("SELECT prefix_id FROM prefixes ORDER BY prefix_id LIMIT 1");
if ($res && $r = $res->fetch_assoc()) $prefix_id = $r['prefix_id'];

// ── Test case definitions ─────────────────────────────────────────────────────
$test_cases = [
    [
        'name'        => 'IT_SUPPORT — ทดสอบกรอกฟอร์มแจ้งปัญหา IT',
        'service_code'=> 'IT_SUPPORT',
        'fields'      => [
            'service_code'        => 'IT_SUPPORT',
            'requester_prefix_id' => $prefix_id,
            'requester_firstname' => 'ทดสอบ',
            'requester_lastname'  => 'อัตโนมัติ',
            'requester_email'     => 'autotest@rangsitcity.go.th',
            'requester_phone'     => '0800000001',
            'position'            => 'นักทดสอบระบบ',
            'department'          => $dept_id,
            'priority'            => 'medium',
            'notes'               => '[AUTO TEST] ทดสอบการส่งฟอร์มอัตโนมัติ IT Support — ' . date('Y-m-d H:i:s'),
            'it_problem_type'     => 'computer',
            'it_device_type'      => 'desktop',
        ],
    ],
    [
        'name'        => 'EMAIL — ทดสอบขอ email account',
        'service_code'=> 'EMAIL',
        'fields'      => [
            'service_code'        => 'EMAIL',
            'requester_prefix_id' => $prefix_id,
            'requester_firstname' => 'ทดสอบ',
            'requester_lastname'  => 'อีเมล',
            'requester_email'     => 'autotest@rangsitcity.go.th',
            'requester_phone'     => '0800000002',
            'department'          => $dept_id,
            'priority'            => 'low',
            'notes'               => '[AUTO TEST] ขอ email account — ' . date('Y-m-d H:i:s'),
            'email_type'          => 'new',
            'email_requested'     => 'test.autouser',
        ],
    ],
    [
        'name'        => 'INTERNET — ทดสอบขอใช้อินเทอร์เน็ต',
        'service_code'=> 'INTERNET',
        'fields'      => [
            'service_code'        => 'INTERNET',
            'requester_prefix_id' => $prefix_id,
            'requester_firstname' => 'ทดสอบ',
            'requester_lastname'  => 'อินเทอร์เน็ต',
            'requester_email'     => 'autotest@rangsitcity.go.th',
            'requester_phone'     => '0800000003',
            'department'          => $dept_id,
            'priority'            => 'medium',
            'notes'               => '[AUTO TEST] ขอใช้อินเทอร์เน็ต — ' . date('Y-m-d H:i:s'),
            'internet_type'       => 'new',
            'internet_location'   => 'ห้องประชุม ชั้น 3',
        ],
    ],
    [
        'name'        => 'PHOTOGRAPHY — ทดสอบขอช่างภาพ',
        'service_code'=> 'PHOTOGRAPHY',
        'fields'      => [
            'service_code'           => 'PHOTOGRAPHY',
            'requester_prefix_id'    => $prefix_id,
            'requester_firstname'    => 'ทดสอบ',
            'requester_lastname'     => 'ช่างภาพ',
            'requester_email'        => 'autotest@rangsitcity.go.th',
            'requester_phone'        => '0800000004',
            'department'             => $dept_id,
            'priority'               => 'high',
            'target_date'            => date('Y-m-d', strtotime('+7 days')),
            'notes'                  => '[AUTO TEST] ขอช่างภาพงานประชุม — ' . date('Y-m-d H:i:s'),
            'photo_event_name'       => 'งานประชุมทดสอบ',
            'photo_event_date'       => date('Y-m-d', strtotime('+7 days')),
            'photo_event_location'   => 'ห้องประชุม',
            'photo_attendees'        => '50',
        ],
    ],
];

// ── Run tests via cURL ────────────────────────────────────────────────────────
$results = [];

if (isset($_POST['run_tests'])) {
    $selected = $_POST['selected_tests'] ?? array_keys($test_cases);

    foreach ($selected as $idx) {
        if (!isset($test_cases[$idx])) continue;
        $tc     = $test_cases[$idx];
        $cookie = tempnam(sys_get_temp_dir(), 'test_cookie_');

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $api_url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => http_build_query($tc['fields']),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_COOKIEFILE     => $cookie,   // new session = no captcha
            CURLOPT_COOKIEJAR      => $cookie,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER     => [
                'X-Requested-With: XMLHttpRequest',
                'Accept: application/json',
            ],
        ]);

        $start    = microtime(true);
        $raw      = curl_exec($ch);
        $elapsed  = round((microtime(true) - $start) * 1000);
        $curl_err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        @unlink($cookie);

        $decoded = $raw ? json_decode($raw, true) : null;
        $success = !$curl_err && $http_code === 200 && !empty($decoded['success']);

        $results[] = [
            'name'      => $tc['name'],
            'service'   => $tc['service_code'],
            'success'   => $success,
            'http_code' => $http_code,
            'elapsed'   => $elapsed,
            'message'   => $decoded['message'] ?? ($curl_err ?: 'No JSON response'),
            'raw'       => $raw,
            'redirect'  => $decoded['redirect_url'] ?? '',
            'req_code'  => $decoded['request_code']  ?? '',
        ];
    }
}

// ── Check for [AUTO TEST] records in DB ──────────────────────────────────────
$auto_records = [];
$res = $conn->query("
    SELECT request_id, request_code, service_code, service_name, requester_name, status, created_at
    FROM service_requests
    WHERE description LIKE '[AUTO TEST]%'
    ORDER BY created_at DESC
    LIMIT 20
");
if ($res) while ($r = $res->fetch_assoc()) $auto_records[] = $r;

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<!-- Page Header -->
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
            <i class="fas fa-flask text-purple-600"></i>
            ทดสอบฟอร์มคำขอบริการ (Auto Test)
        </h1>
        <p class="text-gray-500 text-sm mt-1">
            ส่ง HTTP POST ไปยัง <code class="bg-gray-100 px-1 rounded text-xs"><?php echo htmlspecialchars($api_url); ?></code>
            โดยไม่ต้องใช้คนจริง
        </p>
    </div>
</div>

<!-- Info Banner -->
<div class="bg-blue-50 border border-blue-200 rounded-xl p-4 mb-6 flex gap-3">
    <i class="fas fa-info-circle text-blue-500 text-lg mt-0.5 flex-shrink-0"></i>
    <div class="text-sm text-blue-800">
        <p class="font-medium mb-1">วิธีการทดสอบ</p>
        <ul class="list-disc ml-4 space-y-0.5 text-blue-700">
            <li>สคริปต์ใช้ <strong>cURL</strong> ส่ง HTTP POST ไปยัง <code>api/process_request.php</code> จริง</li>
            <li>Session ใหม่ทุกครั้ง → <strong>ข้าม CAPTCHA อัตโนมัติ</strong> (captcha_code ไม่อยู่ใน session)</li>
            <li>ข้อมูลที่ส่งจะถูกบันทึกใน DB จริง โดยมี <code>[AUTO TEST]</code> ใน description</li>
            <li>สามารถลบ record ทดสอบได้ในตารางด้านล่าง</li>
        </ul>
    </div>
</div>

<!-- Test Form -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fas fa-vial text-purple-500"></i> เลือก Test Cases ที่ต้องการรัน
    </h2>
    <form method="post">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-5">
            <?php foreach ($test_cases as $i => $tc): ?>
            <label class="flex items-start gap-3 p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-purple-50 hover:border-purple-300 transition">
                <input type="checkbox" name="selected_tests[]" value="<?php echo $i; ?>"
                       class="mt-0.5 accent-purple-600" checked>
                <div>
                    <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($tc['name']); ?></p>
                    <p class="text-xs text-gray-500 mt-0.5">
                        service_code: <code class="bg-gray-100 px-1 rounded"><?php echo htmlspecialchars($tc['service_code']); ?></code>
                        &nbsp;|&nbsp; dept_id: <code class="bg-gray-100 px-1 rounded"><?php echo $dept_id; ?></code>
                    </p>
                </div>
            </label>
            <?php endforeach; ?>
        </div>

        <div class="flex items-center gap-3">
            <button type="submit" name="run_tests"
                    class="px-5 py-2.5 bg-purple-600 text-white font-medium rounded-lg hover:bg-purple-700 transition flex items-center gap-2">
                <i class="fas fa-play"></i> รันการทดสอบ
            </button>
            <span class="text-sm text-gray-500">
                Target DB: <strong><?php echo htmlspecialchars($dept_name ?: 'ไม่พบหน่วยงาน'); ?></strong>
                (dept_id: <?php echo $dept_id; ?>)
            </span>
        </div>
    </form>
</div>

<!-- Test Results -->
<?php if (!empty($results)): ?>
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
        <i class="fas fa-clipboard-check text-green-500"></i> ผลการทดสอบ
        <span class="ml-2 text-sm font-normal text-gray-500">
            ผ่าน <?php echo count(array_filter($results, fn($r) => $r['success'])); ?>/<?php echo count($results); ?> test
        </span>
    </h2>
    <div class="space-y-3">
        <?php foreach ($results as $r): ?>
        <div class="border rounded-lg overflow-hidden <?php echo $r['success'] ? 'border-green-200' : 'border-red-200'; ?>">
            <div class="flex items-center justify-between px-4 py-3 <?php echo $r['success'] ? 'bg-green-50' : 'bg-red-50'; ?>">
                <div class="flex items-center gap-3">
                    <i class="fas <?php echo $r['success'] ? 'fa-check-circle text-green-600' : 'fa-times-circle text-red-600'; ?> text-lg"></i>
                    <div>
                        <p class="text-sm font-semibold <?php echo $r['success'] ? 'text-green-800' : 'text-red-800'; ?>">
                            <?php echo htmlspecialchars($r['name']); ?>
                        </p>
                        <p class="text-xs <?php echo $r['success'] ? 'text-green-600' : 'text-red-600'; ?>">
                            HTTP <?php echo $r['http_code']; ?>
                            &nbsp;·&nbsp; <?php echo $r['elapsed']; ?>ms
                            <?php if ($r['req_code']): ?>
                                &nbsp;·&nbsp; <strong><?php echo htmlspecialchars($r['req_code']); ?></strong>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                <span class="text-sm font-medium <?php echo $r['success'] ? 'text-green-700' : 'text-red-700'; ?>">
                    <?php echo $r['success'] ? 'PASS ✓' : 'FAIL ✗'; ?>
                </span>
            </div>
            <div class="px-4 py-2 bg-white text-sm text-gray-700 border-t <?php echo $r['success'] ? 'border-green-100' : 'border-red-100'; ?>">
                <span class="font-medium">Message:</span> <?php echo htmlspecialchars($r['message']); ?>
                <?php if ($r['redirect']): ?>
                    &nbsp;|&nbsp; <a href="<?php echo htmlspecialchars(str_replace('127.0.0.1', $_SERVER['HTTP_HOST'], $r['redirect'])); ?>"
                       target="_blank" class="text-blue-600 hover:underline text-xs">
                        ดูผลลัพธ์ <i class="fas fa-external-link-alt text-xs"></i>
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!$r['success'] && $r['raw']): ?>
            <div class="px-4 py-2 bg-gray-50 border-t border-gray-100">
                <details>
                    <summary class="text-xs text-gray-500 cursor-pointer">Raw Response</summary>
                    <pre class="text-xs text-gray-700 mt-2 overflow-x-auto whitespace-pre-wrap"><?php echo htmlspecialchars($r['raw']); ?></pre>
                </details>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Auto Test Records in DB -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
            <i class="fas fa-database text-gray-500"></i>
            Record ทดสอบใน Database
            <span class="text-sm font-normal text-gray-500">(description มี [AUTO TEST])</span>
        </h2>
        <?php if (!empty($auto_records)): ?>
        <form method="post" onsubmit="return confirm('ลบ record [AUTO TEST] ทั้งหมด?')">
            <button type="submit" name="delete_auto_tests"
                    class="px-3 py-1.5 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition flex items-center gap-1">
                <i class="fas fa-trash text-xs"></i> ลบทั้งหมด
            </button>
        </form>
        <?php endif; ?>
    </div>

    <?php if (isset($_POST['delete_auto_tests'])): ?>
    <?php
        $del = $conn->query("DELETE FROM service_requests WHERE description LIKE '[AUTO TEST]%'");
        $deleted = $conn->affected_rows;
        echo "<div class='mb-3 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700'>
            <i class='fas fa-check mr-1'></i> ลบ {$deleted} record เรียบร้อยแล้ว
        </div>";
        // Reload records
        $auto_records = [];
        $res = $conn->query("SELECT request_id, request_code, service_code, service_name, requester_name, status, created_at FROM service_requests WHERE description LIKE '[AUTO TEST]%' ORDER BY created_at DESC LIMIT 20");
        if ($res) while ($r = $res->fetch_assoc()) $auto_records[] = $r;
    ?>
    <?php endif; ?>

    <?php if (empty($auto_records)): ?>
    <div class="text-center py-8 text-gray-400">
        <i class="fas fa-inbox text-3xl mb-2"></i>
        <p class="text-sm">ยังไม่มี record ทดสอบ</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Request Code</th>
                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Service</th>
                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">ผู้ยื่น</th>
                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">Status</th>
                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase">วันที่</th>
                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-500 uppercase"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php foreach ($auto_records as $r): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-3 py-2 font-mono text-xs text-indigo-700"><?php echo htmlspecialchars($r['request_code']); ?></td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-0.5 bg-gray-100 text-gray-700 text-xs rounded"><?php echo htmlspecialchars($r['service_code']); ?></span>
                    </td>
                    <td class="px-3 py-2 text-gray-700"><?php echo htmlspecialchars($r['requester_name']); ?></td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-0.5 rounded text-xs font-medium
                            <?php echo match($r['status']) {
                                'pending'     => 'bg-yellow-100 text-yellow-700',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                'completed'   => 'bg-green-100 text-green-700',
                                'cancelled'   => 'bg-red-100 text-red-700',
                                default       => 'bg-gray-100 text-gray-600',
                            }; ?>">
                            <?php echo htmlspecialchars($r['status']); ?>
                        </span>
                    </td>
                    <td class="px-3 py-2 text-gray-500 text-xs"><?php echo thdate('d/m/Y H:i', strtotime($r['created_at'])); ?></td>
                    <td class="px-3 py-2">
                        <a href="request_detail.php?id=<?php echo $r['request_id']; ?>" target="_blank"
                           class="text-indigo-600 hover:underline text-xs">ดูรายละเอียด</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include 'admin-layout/footer.php'; ?>
