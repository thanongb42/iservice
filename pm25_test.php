<?php
// ไฟล์ทดสอบชั่วคราว — ลบออกหลังใช้งาน
date_default_timezone_set('Asia/Bangkok');
header('Content-Type: text/plain; charset=utf-8');

echo "=== PM2.5 Cron Test === " . date('Y-m-d H:i:s') . "\n\n";

// 1. PHP version
echo "PHP: " . PHP_VERSION . "\n";
echo "OS: " . PHP_OS . "\n";
echo "__DIR__: " . __DIR__ . "\n\n";

// 2. DB connection
echo "--- Database ---\n";
try {
    require_once __DIR__ . '/config/database.php';
    $pdo = getPDO();
    $r = $pdo->query("SELECT COUNT(*) as cnt FROM pm25_data")->fetch();
    echo "Connection: OK\n";
    echo "pm25_data rows: " . $r['cnt'] . "\n";
    $r2 = $pdo->query("SELECT MAX(created_at) as last FROM pm25_data")->fetch();
    echo "Last insert: " . ($r2['last'] ?? 'none') . "\n\n";
} catch (Exception $e) {
    echo "Connection ERROR: " . $e->getMessage() . "\n\n";
    exit;
}

// 3. API test (1 CID)
echo "--- Freshnergy API ---\n";
$apiUrl = 'https://app.freshnergy.com/api/v2/device';
$apiKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhY2NvdW50Ijp7ImVtYWlsIjoicmFuZ3NpdDYwMDBAZ21haWwuY29tIiwiYXBpIjp7ImFsbG93Ijp0cnVlLCJkZXZpY2UiOjgsImV4cGlyZSI6eyJfc2Vjb25kcyI6MTg0MDcyNjc1NSwiX25hbm9zZWNvbmRzIjowfX19LCJpYXQiOjE3Nzc0Mzc4MDA5NTIsImV4cCI6MTc3NzUyNDIwMDk1Mn0.Hmxg5PooqCf1c2Rj6PMwVFXzBhSlGCGxel7DYeuk10M';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL            => $apiUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['cid' => ['349454E09A5C']]),
    CURLOPT_HTTPHEADER     => ["Authorization: $apiKey", 'Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
]);
$resp     = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err      = curl_errno($ch) ? curl_error($ch) : null;
curl_close($ch);

if ($err) {
    echo "cURL ERROR: $err\n";
} else {
    echo "HTTP: $httpCode\n";
    $data = json_decode($resp, true);
    if (isset($data['data'][0])) {
        $item = $data['data'][0];
        echo "CID: " . $item['cid'] . "\n";
        echo "PM2.5: " . ($item['sensor']['pm2_5'] ?? '?') . "\n";
        echo "timeStamp: " . ($item['timeStamp'] ?? '?') . "\n";
    } else {
        echo "Response: " . substr($resp, 0, 200) . "\n";
    }
}

// 4. Log file
echo "\n--- Log File ---\n";
$log = __DIR__ . '/storage/pm25_cron.log';
echo "Path: $log\n";
echo "Exists: " . (file_exists($log) ? 'YES' : 'NO') . "\n";
if (file_exists($log)) {
    $lines = file($log);
    $last5 = array_slice($lines, -5);
    echo "Last 5 lines:\n" . implode('', $last5);
}

echo "\n\n=== Done ===\n";
echo "ลบไฟล์ pm25_test.php หลังใช้งานเสร็จ\n";
