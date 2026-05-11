<?php
# pm25_cron.php - ดึงข้อมูล PM2.5 จาก Freshnergy API บันทึกลง DB
# รันทุก 15 นาที (Windows Task Scheduler / cPanel Cron)
# Windows : "C:\xampp\php\php.exe" "C:\xampp\htdocs\iservice\pm25_cron.php"
# cPanel  : */15 * * * * /usr/local/bin/php /home/.../iservice.rangsitcity.go.th/pm25_cron.php

define('LOG_FILE', __DIR__ . '/storage/pm25_cron.log');

function log_msg(string $msg): void {
    file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

function api_fetch(string $apiUrl, string $apiKey, string $cid): ?array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['cid' => [$cid]]),
        CURLOPT_HTTPHEADER     => [
            "Authorization: $apiKey",
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
    curl_close($ch);

    if ($curlErr) {
        log_msg("[$cid] cURL error: $curlErr");
        return null;
    }
    if ($httpCode !== 200) {
        log_msg("[$cid] HTTP $httpCode — อาจยังไม่ได้ลงทะเบียนใน API");
        return null;
    }
    $data = json_decode($response, true);
    return ($data['status'] ?? '') === 'success' ? ($data['data'] ?? []) : null;
}

require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

# ── 1. โหลด CID จาก pm25_sensors (fallback: hardcoded จาก new_point.txt) ────
$cids = [];
try {
    $cids = $pdo->query("SELECT cid FROM pm25_sensors WHERE is_active=1 ORDER BY id")
               ->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    log_msg('pm25_sensors not found, using hardcoded CIDs');
}

if (empty($cids)) {
    $cids = [
        '349454E09A5C', // เทศบาลนครรังสิต
        '8C4B1432EBB0', // โรงเรียนนครรังสิตสิริเวชชะพันธ์
        '58BF25FD48FD', // หอประชุมอาคาร 100 ปี ธัญญบุรี
        '58BF25FDB358', // โรงเรียนนครรังสิตเปรมปรีดิ์
        '349454E089E4', // โรงเรียนมัธยมนครรังสิต
        '8C4B1432F538', // โรงเรียนดวงกมล
        '349454E08778', // โรงเรียนนครรังสิตเทพธัญญะอุปถัมภ์
        '8C4B14327C18', // โรงเรียนเพียรปัญญา
    ];
}

# ── 2. เตรียม prepared statement ─────────────────────────────────────────────
$stmt = $pdo->prepare("
    INSERT IGNORE INTO pm25_data (cid, pm25, co2, pm1, pm10, pm4, sensor_timestamp)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");

$apiUrl  = 'https://app.freshnergy.com/api/v2/device';
$apiKey  = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhY2NvdW50Ijp7ImVtYWlsIjoicmFuZ3NpdDYwMDBAZ21haWwuY29tIiwiYXBpIjp7ImFsbG93Ijp0cnVlLCJkZXZpY2UiOjgsImV4cGlyZSI6eyJfc2Vjb25kcyI6MTg0MDcyNjc1NSwiX25hbm9zZWNvbmRzIjowfX19LCJpYXQiOjE3Nzc0Mzc4MDA5NTIsImV4cCI6MTc3NzUyNDIwMDk1Mn0.Hmxg5PooqCf1c2Rj6PMwVFXzBhSlGCGxel7DYeuk10M';

$inserted = 0;
$skipped  = 0;
$errors   = 0;

# ── 3. วนเรียก API ทีละ CID (ถ้า CID ใดพัง ไม่กระทบตัวอื่น) ───────────────
foreach ($cids as $cid) {
    $items = api_fetch($apiUrl, $apiKey, $cid);
    if ($items === null) {
        $errors++;
        continue;
    }

    foreach ($items as $item) {
        $sensor = $item['sensor'] ?? [];
        $pm25   = $sensor['pm2_5'] ?? null;
        $co2    = $sensor['co2']   ?? null;
        $pm1    = $sensor['pm1']   ?? null;
        $pm10   = $sensor['pm10']  ?? null;
        // timeStamp อยู่นอก sensor (ไม่ใช่ใน sensor)
        $ts     = $item['timeStamp'] ?? null;

        if (!$cid || $pm25 === null || !$ts) {
            $skipped++;
            continue;
        }

        $stmt->execute([$cid, $pm25, $co2, $pm1, $pm10, null, $ts]);
        $stmt->rowCount() > 0 ? $inserted++ : $skipped++;
    }
}

log_msg("Done — inserted: $inserted, dup/skip: $skipped, api_error: $errors / " . count($cids) . " sensors");
