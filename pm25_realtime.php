<?php
// pm25_realtime.php - แสดงข้อมูล PM2.5 แบบ realtime และกราฟ
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();
// ดึงข้อมูลล่าสุดต่อ sensor
$stmt = $pdo->query("SELECT t1.* FROM pm25_data t1 INNER JOIN (SELECT cid, MAX(sensor_timestamp) AS max_ts FROM pm25_data GROUP BY cid) t2 ON t1.cid = t2.cid AND t1.sensor_timestamp = t2.max_ts ORDER BY t1.cid");
$latest = $stmt->fetchAll();
// ดึงข้อมูลย้อนหลัง 12 ชั่วโมง (สำหรับกราฟ)
$stmt2 = $pdo->query("SELECT * FROM pm25_data WHERE sensor_timestamp >= UNIX_TIMESTAMP(NOW())-43200 ORDER BY sensor_timestamp ASC, cid ASC");
$rows = $stmt2->fetchAll();
$chartData = [];
foreach ($rows as $row) {
    $chartData[$row['cid']][] = [
        'pm25' => $row['pm25'],
        'time' => date('H:i', $row['sensor_timestamp'])
    ];
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>PM2.5 Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta http-equiv="refresh" content="60">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Kanit', sans-serif; background: #f4f8fb; margin: 0; padding: 0; }
        .container { max-width: 1100px; margin: 2em auto; padding: 1em; }
        h2 { text-align: center; color: #0d9488; margin-bottom: 1.5em; }
        .dashboard { display: flex; flex-wrap: wrap; gap: 2em; justify-content: center; }
        .card {
            background: #fff;
            border-radius: 1em;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 2em 2em 1.5em 2em;
            min-width: 260px;
            max-width: 320px;
            flex: 1 1 260px;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }
        .pm25-value {
            font-size: 2.8em;
            font-weight: bold;
            color: #0d9488;
            margin-bottom: 0.2em;
        }
        .cid-label {
            font-size: 1.1em;
            color: #888;
            margin-bottom: 0.7em;
        }
        .time-label {
            font-size: 0.95em;
            color: #aaa;
            margin-bottom: 1em;
        }
        .card canvas { max-width: 100%; margin-top: 0.5em; min-height: 180px; height: 180px; }
        @media (max-width: 900px) {
            .dashboard { flex-direction: column; align-items: center; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>PM2.5 Dashboard (Realtime)</h2>
        <div class="dashboard">
        <?php foreach ($latest as $row): ?>
            <div class="card">
                <div class="pm25-value"><?=htmlspecialchars($row['pm25'])?></div>
                <div class="cid-label">CID: <?=htmlspecialchars($row['cid'])?></div>
                <div class="time-label">อัปเดตล่าสุด: <?=date('Y-m-d H:i', $row['sensor_timestamp'])?></div>
                <canvas id="chart_<?=$row['cid']?>"></canvas>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <script>
    <?php foreach ($latest as $row): 
        $cid = $row['cid'];
        $points = $chartData[$cid] ?? [];
    ?>
    new Chart(document.getElementById('chart_<?=$cid?>').getContext('2d'), {
        type: 'line',
        data: {
            labels: <?=json_encode(array_column($points, 'time'))?>,
            datasets: [{
                label: 'PM2.5',
                data: <?=json_encode(array_column($points, 'pm25'))?>,
                borderColor: '#0d9488',
                backgroundColor: 'rgba(13,148,136,0.08)',
                fill: true,
                tension: 0.3,
                pointRadius: 2
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero:true } },
            responsive: true,
            maintainAspectRatio: true,
            animation: false
        }
    });
    <?php endforeach; ?>
    </script>
</body>
</html>
