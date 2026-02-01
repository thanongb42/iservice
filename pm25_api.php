<?php
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
$stmt = $pdo->query("SELECT t1.* FROM pm25_data t1 INNER JOIN (SELECT cid, MAX(sensor_timestamp) AS max_ts FROM pm25_data GROUP BY cid) t2 ON t1.cid = t2.cid AND t1.sensor_timestamp = t2.max_ts ORDER BY t1.cid");
$latest = $stmt->fetchAll();
$stmt2 = $pdo->query("SELECT * FROM pm25_data WHERE sensor_timestamp >= UNIX_TIMESTAMP(NOW())-43200 ORDER BY sensor_timestamp ASC, cid ASC");
$rows = $stmt2->fetchAll();
$chartData = [];
foreach ($rows as $row) {
    $chartData[$row['cid']][] = [
        'pm25' => $row['pm25'],
        'time' => date('H:i', $row['sensor_timestamp'])
    ];
}
header('Content-Type: application/json');
echo json_encode([
    'latest' => $latest,
    'chartData' => $chartData
]);
