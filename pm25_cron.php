<?php
# pm25_cron.php — ดึงข้อมูล PM2.5 จาก Freshnergy API + Jumbo Bus-Stop API บันทึกลง DB
# รันทุก 5 นาที ผ่าน cron-job.org
# URL: https://iservice.rangsitcity.go.th/pm25_cron.php

date_default_timezone_set('Asia/Bangkok');
define('LOG_FILE', __DIR__ . '/storage/pm25_cron.log');

function log_msg(string $msg): void {
    file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
}

# ── Freshnergy: POST + JWT Authorization, one CID per call ────────────────────
function api_fetch_freshnergy(string $apiUrl, string $apiKey, string $cid): ?array {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode(['cid' => [$cid]]),
        CURLOPT_HTTPHEADER     => ["Authorization: $apiKey", 'Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
    curl_close($ch);

    if ($curlErr) { log_msg("[$cid] cURL error: $curlErr"); return null; }
    if ($httpCode !== 200) { log_msg("[$cid] HTTP $httpCode — Freshnergy"); return null; }
    $data = json_decode($response, true);
    return ($data['status'] ?? '') === 'success' ? ($data['data'] ?? []) : null;
}

# ── Jumbo: GET x-api-key, /bus-stop/{stationId}/latest ───────────────────────
function api_fetch_jumbo(string $baseUrl, string $apiKey, string $stationId): ?array {
    $url = rtrim($baseUrl, '/') . '/' . $stationId . '/latest';
    $ch  = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => ["x-api-key: $apiKey"],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_errno($ch) ? curl_error($ch) : null;
    curl_close($ch);

    if ($curlErr) { log_msg("[$stationId] cURL error: $curlErr"); return null; }
    if ($httpCode !== 200) { log_msg("[$stationId] HTTP $httpCode — Jumbo"); return null; }
    $data = json_decode($response, true);
    return ($data['success'] ?? false) ? ($data['data'] ?? null) : null;
}

require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

# ── 1. โหลด sensors พร้อม api_provider ───────────────────────────────────────
$sensors = [];
try {
    $sensors = $pdo->query("SELECT cid, api_provider FROM pm25_sensors WHERE is_active=1 ORDER BY id")
                   ->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    log_msg('pm25_sensors not found / api_provider column missing, falling back to hardcoded Freshnergy CIDs');
}

if (empty($sensors)) {
    $sensors = array_map(fn($c) => ['cid' => $c, 'api_provider' => 'freshnergy'], [
        '349454E09A5C', // เทศบาลนครรังสิต
        '8C4B1432EBB0', // รร.นครรังสิตสิริเวชชะพันธ์
        '58BF25FD48FC', // หอประชุม 100 ปี ธัญญบุรี
        '58BF25FDB358', // รร.นครรังสิตเปรมปรีดิ์
        '349454E089E4', // รร.มัธยมนครรังสิต
        '8C4B1432F538', // รร.ดวงกมล
        '349454E08778', // รร.นครรังสิตเทพธัญญะอุปถัมภ์
        '8C4B14327C18', // รร.เพียรปัญญา
    ]);
}

# ── 2. Config ─────────────────────────────────────────────────────────────────
$freshUrl = 'https://app.freshnergy.com/api/v2/device';
$freshKey = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJlbWFpbCI6InJhbmdzaXQ2MDAwQGdtYWlsLmNvbSIsImlkIjoiZ3hoZnpZRER4VVhQWnowU1BKVjYiLCJpYXQiOjE3ODA1NTgxNjgyOTF9.uAYHLlKLTZIjj13YMJCmNuG0YD3muoF9I-JSuoiSV-o';

$jumboUrl = 'https://rsweather.jumboelec.co.th/api/v1/weather/bus-stop';
$jumboKey = 'rangsit1@weather';

# ── 3. Prepared statement (ใช้ร่วมกันทั้ง 2 provider) ─────────────────────────
$stmt = $pdo->prepare("
    INSERT IGNORE INTO pm25_data (cid, pm25, temperature, humidity, co2, pm1, pm10, sensor_timestamp)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$inserted = 0;
$skipped  = 0;
$errors   = 0;

# ── 4. วน sensors — route ตาม api_provider ────────────────────────────────────
foreach ($sensors as $sensor) {
    $cid      = $sensor['cid'];
    $provider = $sensor['api_provider'] ?? 'freshnergy';

    if ($provider === 'jumbo') {
        # Jumbo: GET /bus-stop/{stationId}/latest
        # Response: { success, data: { stationId, temperature, humidity, pm25, pm10, createdAt } }
        $item = api_fetch_jumbo($jumboUrl, $jumboKey, $cid);
        if ($item === null) { $errors++; continue; }

        $pm25 = $item['pm25']        ?? null;
        $temp = $item['temperature'] ?? null;
        $humi = $item['humidity']    ?? null;
        $pm10 = $item['pm10']        ?? null;
        # pm1, co2 ไม่มีใน Jumbo API → NULL
        $ts = null;
        if (!empty($item['createdAt'])) {
            try { $ts = (new DateTime($item['createdAt']))->getTimestamp(); } catch (Exception $e) {}
        }

        if ($pm25 === null || !$ts) { $skipped++; continue; }
        $stmt->execute([$cid, $pm25, $temp, $humi, null, null, $pm10, $ts]);
        $stmt->rowCount() > 0 ? $inserted++ : $skipped++;

    } else {
        # Freshnergy: POST /api/v2/device, JWT Auth
        # Response: { status:"success", data: [{ cid, sensor:{pm2_5,temp,humi,co2,pm1,pm10}, timeStamp }] }
        $items = api_fetch_freshnergy($freshUrl, $freshKey, $cid);
        if ($items === null) { $errors++; continue; }

        foreach ($items as $item) {
            $s    = $item['sensor'] ?? [];
            $pm25 = $s['pm2_5'] ?? null;
            $temp = $s['temp']  ?? null;
            $humi = $s['humi']  ?? null;
            $co2  = $s['co2']   ?? null;
            $pm1  = $s['pm1']   ?? null;
            $pm10 = $s['pm10']  ?? null;
            # timeStamp อยู่นอก sensor (ไม่ใช่ใน sensor)
            $ts   = $item['timeStamp'] ?? null;

            if ($pm25 === null || !$ts) { $skipped++; continue; }
            $stmt->execute([$cid, $pm25, $temp, $humi, $co2, $pm1, $pm10, $ts]);
            $stmt->rowCount() > 0 ? $inserted++ : $skipped++;
        }
    }
}

log_msg("Done — inserted: $inserted, dup/skip: $skipped, api_error: $errors / " . count($sensors) . " sensors");
