<?php
/**
 * PM2.5 Public API — เทศบาลนครรังสิต
 *
 * Auth : Authorization: Bearer <api_key>
 *        หรือ ?api_key=<api_key>
 *
 * GET /api/pm25_public.php
 *   ?type=latest          ค่าล่าสุดทุกสถานี (default)
 *   ?type=history
 *   &hours=24             ย้อนหลัง N ชั่วโมง (max 168 = 7 วัน)
 *   &cid=349454E09A5C     กรอง CID เดียว (optional)
 */

date_default_timezone_set('Asia/Bangkok');
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Cache-Control: no-cache');

function respond(int $code, array $body): void {
    http_response_code($code);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

require_once __DIR__ . '/../config/database.php';
$pdo = getPDO();

// ── 1. ตรวจสอบ API Key ──────────────────────────────────────────────────────
$apiKey = '';

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (preg_match('/^Bearer\s+(.+)$/i', $authHeader, $m)) {
    $apiKey = trim($m[1]);
}
if (!$apiKey) {
    $apiKey = trim($_GET['api_key'] ?? '');
}

if (!$apiKey) {
    respond(401, ['status' => 'error', 'message' => 'Missing API key. Use Authorization: Bearer <key> or ?api_key=<key>']);
}

$stmt = $pdo->prepare("
    SELECT * FROM pm25_api_keys
    WHERE api_key = ? AND is_active = 1
      AND (expires_at IS NULL OR expires_at >= CURDATE())
    LIMIT 1
");
$stmt->execute([$apiKey]);
$keyRow = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$keyRow) {
    respond(403, ['status' => 'error', 'message' => 'Invalid or expired API key']);
}

// อัปเดต last_used + request_count (non-blocking)
try {
    $pdo->prepare("UPDATE pm25_api_keys SET last_used_at=NOW(), request_count=request_count+1 WHERE id=?")
        ->execute([$keyRow['id']]);
} catch (Exception $e) {}

// ── มาตรฐานสี PM2.5 (กรมควบคุมมลพิษ) ─────────────────────────────────────────
function pmLevel(?float $v): array {
    if ($v === null) return [
        'level'  => 'N/A',
        'label'  => 'ไม่มีข้อมูล',
        'label_en' => 'No Data',
        'color'  => '#94a3b8',
        'advice' => '–',
    ];
    if ($v <= 15.0) return [
        'level'    => 1,
        'label'    => 'ดีมาก',
        'label_en' => 'Very Good',
        'color'    => '#3BCCFF',
        'advice'   => 'ประชาชนทุกคนสามารถดำเนินชีวิตได้ตามปกติ',
    ];
    if ($v <= 25.0) return [
        'level'    => 2,
        'label'    => 'ดี',
        'label_en' => 'Good',
        'color'    => '#92D050',
        'advice'   => 'ทำกิจกรรมกลางแจ้งได้ตามปกติ กลุ่มเสี่ยงควรสังเกตอาการ',
    ];
    if ($v <= 37.5) return [
        'level'    => 3,
        'label'    => 'ปานกลาง',
        'label_en' => 'Moderate',
        'color'    => '#FFFF00',
        'advice'   => 'ลดระยะเวลากิจกรรมกลางแจ้ง กลุ่มเสี่ยงใช้หน้ากาก PM2.5',
    ];
    if ($v <= 75.0) return [
        'level'    => 4,
        'label'    => 'เริ่มมีผลกระทบต่อสุขภาพ',
        'label_en' => 'Starting to Affect Health',
        'color'    => '#FFA200',
        'advice'   => 'ใช้หน้ากาก PM2.5 ทุกครั้งที่ออกนอกอาคาร จำกัดเวลาทำกิจกรรมกลางแจ้ง',
    ];
    return [
        'level'    => 5,
        'label'    => 'มีผลกระทบต่อสุขภาพ',
        'label_en' => 'Affecting Health',
        'color'    => '#F04646',
        'advice'   => 'งดกิจกรรมกลางแจ้ง ใช้หน้ากาก PM2.5 ทุกครั้ง หากมีอาการให้รีบพบแพทย์',
    ];
}

// ── 2. Parse params ─────────────────────────────────────────────────────────
$type  = $_GET['type']  ?? 'latest';
$hours = min((int)($_GET['hours'] ?? 24), 168); // max 7 วัน
$cid   = trim($_GET['cid'] ?? '');

// ── 3. ดึงข้อมูลสถานี ───────────────────────────────────────────────────────
$sensorMap = [];
try {
    $rows = $pdo->query("SELECT * FROM pm25_sensors WHERE is_active=1 ORDER BY id")
               ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $sensorMap[$r['cid']] = $r;
} catch (Exception $e) {}

// ── 4. ดึงข้อมูลตามประเภท ───────────────────────────────────────────────────
if ($type === 'latest') {

    $params = [];
    $where  = '';
    if ($cid) {
        $where  = 'AND d.cid = ?';
        $params = [$cid];
    }

    $stmt = $pdo->prepare("
        SELECT d.* FROM pm25_data d
        INNER JOIN (
            SELECT cid, MAX(sensor_timestamp) max_ts FROM pm25_data GROUP BY cid
        ) t ON d.cid = t.cid AND d.sensor_timestamp = t.max_ts
        $where
        ORDER BY d.cid
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $s = $sensorMap[$r['cid']] ?? null;
        $data[] = [
            'id'          => $s ? (int)$s['id']       : null,
            'location'    => $s ? $s['location_name'] : $r['cid'],
            'cid'         => $r['cid'],
            'lat'         => $s && $s['lat'] ? (float)$s['lat'] : null,
            'lng'         => $s && $s['lng'] ? (float)$s['lng'] : null,
            'pm25'        => $r['pm25']  !== null ? (float)$r['pm25']  : null,
            'pm1'         => $r['pm1']   !== null ? (float)$r['pm1']   : null,
            'pm10'        => $r['pm10']  !== null ? (float)$r['pm10']  : null,
            'temperature' => isset($r['temperature']) && $r['temperature'] !== null ? (float)$r['temperature'] : null,
            'humidity'    => isset($r['humidity'])    && $r['humidity']  !== null ? (float)$r['humidity']    : null,
            'co2'         => $r['co2']   !== null ? (float)$r['co2']   : null,
            'online'      => (time() - (int)$r['sensor_timestamp']) < 1800,
            'recorded_at' => date('Y-m-d H:i:s', (int)$r['sensor_timestamp']),
            'fetched_at'  => $r['created_at'] ?? null,
            'aqi'         => pmLevel($r['pm25'] !== null ? (float)$r['pm25'] : null),
        ];
    }

    // ── คำนวณค่าเฉลี่ย / สรุปทุก metric ─────────────────────────────────────
    function avg(array $data, string $key): ?float {
        $vals = array_filter(array_column($data, $key), fn($v) => $v !== null);
        return $vals ? round(array_sum($vals) / count($vals), 2) : null;
    }
    function maxVal(array $data, string $key): ?float {
        $vals = array_filter(array_column($data, $key), fn($v) => $v !== null);
        return $vals ? (float)max($vals) : null;
    }
    function minVal(array $data, string $key): ?float {
        $vals = array_filter(array_column($data, $key), fn($v) => $v !== null);
        return $vals ? (float)min($vals) : null;
    }

    $onlineCount = count(array_filter($data, fn($d) => $d['online']));

    $summary = [
        'stations_total'  => count($data),
        'stations_online' => $onlineCount,
        'pm25' => [
            'avg' => avg($data, 'pm25'),
            'max' => maxVal($data, 'pm25'),
            'min' => minVal($data, 'pm25'),
        ],
        'pm1' => [
            'avg' => avg($data, 'pm1'),
            'max' => maxVal($data, 'pm1'),
            'min' => minVal($data, 'pm1'),
        ],
        'pm10' => [
            'avg' => avg($data, 'pm10'),
            'max' => maxVal($data, 'pm10'),
            'min' => minVal($data, 'pm10'),
        ],
        'temperature' => [
            'avg' => avg($data, 'temperature'),
            'max' => maxVal($data, 'temperature'),
            'min' => minVal($data, 'temperature'),
        ],
        'humidity' => [
            'avg' => avg($data, 'humidity'),
            'max' => maxVal($data, 'humidity'),
            'min' => minVal($data, 'humidity'),
        ],
        'co2' => [
            'avg' => avg($data, 'co2'),
            'max' => maxVal($data, 'co2'),
            'min' => minVal($data, 'co2'),
        ],
    ];

    $summary['aqi_overall'] = pmLevel($summary['pm25']['avg'] !== null ? (float)$summary['pm25']['avg'] : null);

    respond(200, [
        'status'       => 'success',
        'type'         => 'latest',
        'generated_at' => date('Y-m-d H:i:s'),
        'count'        => count($data),
        'aqi_standard' => [
            'source'     => 'กรมควบคุมมลพิษ (Pollution Control Department, Thailand)',
            'unit'       => 'µg/m³ (24-hour average)',
            'levels'     => [
                ['level'=>1, 'range'=>'0–15.0',    'label'=>'ดีมาก',                    'color'=>'#3BCCFF'],
                ['level'=>2, 'range'=>'15.1–25.0',  'label'=>'ดี',                       'color'=>'#92D050'],
                ['level'=>3, 'range'=>'25.1–37.5',  'label'=>'ปานกลาง',                  'color'=>'#FFFF00'],
                ['level'=>4, 'range'=>'37.6–75.0',  'label'=>'เริ่มมีผลกระทบต่อสุขภาพ',  'color'=>'#FFA200'],
                ['level'=>5, 'range'=>'≥75.1',      'label'=>'มีผลกระทบต่อสุขภาพ',       'color'=>'#F04646'],
            ],
        ],
        'summary'      => $summary,
        'data'         => $data,
    ]);

} elseif ($type === 'history') {

    $params = [time() - ($hours * 3600)];
    $where  = '';
    if ($cid) {
        $where    = 'AND cid = ?';
        $params[] = $cid;
    }

    $stmt = $pdo->prepare("
        SELECT cid, pm25, pm1, pm10,
               temperature, humidity, co2,
               sensor_timestamp, created_at
        FROM pm25_data
        WHERE sensor_timestamp >= ? $where
        ORDER BY sensor_timestamp ASC, cid ASC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $data = [];
    foreach ($rows as $r) {
        $s = $sensorMap[$r['cid']] ?? null;
        $data[] = [
            'location'    => $s ? $s['location_name'] : $r['cid'],
            'cid'         => $r['cid'],
            'pm25'        => $r['pm25']        !== null ? (float)$r['pm25']        : null,
            'pm1'         => $r['pm1']         !== null ? (float)$r['pm1']         : null,
            'pm10'        => $r['pm10']        !== null ? (float)$r['pm10']        : null,
            'temperature' => isset($r['temperature']) && $r['temperature'] !== null ? (float)$r['temperature'] : null,
            'humidity'    => isset($r['humidity'])    && $r['humidity']    !== null ? (float)$r['humidity']    : null,
            'co2'         => $r['co2']         !== null ? (float)$r['co2']         : null,
            'recorded_at' => date('Y-m-d H:i:s', (int)$r['sensor_timestamp']),
            'aqi'         => pmLevel($r['pm25'] !== null ? (float)$r['pm25'] : null),
        ];
    }

    respond(200, [
        'status'       => 'success',
        'type'         => 'history',
        'hours'        => $hours,
        'generated_at' => date('Y-m-d H:i:s'),
        'count'        => count($data),
        'data'         => $data,
    ]);

} else {
    respond(400, ['status' => 'error', 'message' => 'Invalid type. Use: latest, history']);
}
