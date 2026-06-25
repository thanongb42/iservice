<?php
/**
 * pm25_summary_update.php
 * อัปเดต/สร้าง pm25_daily_summary (daily cache)
 * เรียกได้จาก browser (admin) หรือ cron
 *
 * Cron ตัวอย่าง (ทำงานทุก 00:05 ของทุกวัน):
 *   5 0 * * * /usr/local/bin/php /path/to/pm25_summary_update.php
 */
date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/config/database.php';
$pdo = getPDO();

$isCron = (php_sapi_name() === 'cli');
$log    = [];

function logMsg(string $msg, array &$log): void {
    $line = '[' . date('H:i:s') . '] ' . $msg;
    $log[] = $line;
    if (php_sapi_name() === 'cli') echo $line . "\n";
}

// ── 1. สร้างตารางถ้ายังไม่มี ─────────────────────────────────────────────────
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `pm25_daily_summary` (
            `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `cid`          VARCHAR(32)       NOT NULL,
            `summary_date` DATE              NOT NULL,
            `avg_pm25`     DECIMAL(6,2)      DEFAULT NULL,
            `max_pm25`     DECIMAL(6,2)      DEFAULT NULL,
            `min_pm25`     DECIMAL(6,2)      DEFAULT NULL,
            `avg_pm10`     DECIMAL(6,2)      DEFAULT NULL,
            `avg_temp`     DECIMAL(5,2)      DEFAULT NULL,
            `avg_humi`     DECIMAL(5,2)      DEFAULT NULL,
            `avg_co2`      DECIMAL(8,2)      DEFAULT NULL,
            `data_points`  SMALLINT UNSIGNED DEFAULT 0,
            `updated_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_cid_date` (`cid`, `summary_date`),
            KEY `idx_date` (`summary_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    logMsg('ตรวจสอบตาราง pm25_daily_summary OK', $log);
} catch (Exception $e) {
    logMsg('ERROR สร้างตาราง: ' . $e->getMessage(), $log);
    exit(1);
}

// ── 2. หาวันที่ยังไม่มีใน summary (วันที่ผ่านไปแล้ว ไม่รวมวันนี้) ────────────
try {
    $missingDates = $pdo->query("
        SELECT DISTINCT DATE(FROM_UNIXTIME(sensor_timestamp)) AS d
        FROM pm25_data
        WHERE DATE(FROM_UNIXTIME(sensor_timestamp)) < CURDATE()
          AND pm25 IS NOT NULL
          AND DATE(FROM_UNIXTIME(sensor_timestamp)) NOT IN (
              SELECT summary_date FROM pm25_daily_summary
          )
        ORDER BY d ASC
    ")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $missingDates = [];
    logMsg('ERROR query missing dates: ' . $e->getMessage(), $log);
}

// ── 3. เพิ่มวันนี้เสมอ (partial day update) ──────────────────────────────────
$today = date('Y-m-d');
$datesToProcess = array_merge($missingDates, [$today]);

logMsg('วันที่รอประมวลผล: ' . count($datesToProcess) . ' วัน (รวมวันนี้)', $log);

// ── 4. ประมวลผลและ upsert ─────────────────────────────────────────────────────
$upsert = $pdo->prepare("
    INSERT INTO pm25_daily_summary
        (cid, summary_date, avg_pm25, max_pm25, min_pm25, avg_pm10, avg_temp, avg_humi, avg_co2, data_points)
    VALUES
        (:cid, :date, :avg25, :max25, :min25, :avg10, :temp, :humi, :co2, :cnt)
    ON DUPLICATE KEY UPDATE
        avg_pm25    = VALUES(avg_pm25),
        max_pm25    = VALUES(max_pm25),
        min_pm25    = VALUES(min_pm25),
        avg_pm10    = VALUES(avg_pm10),
        avg_temp    = VALUES(avg_temp),
        avg_humi    = VALUES(avg_humi),
        avg_co2     = VALUES(avg_co2),
        data_points = VALUES(data_points),
        updated_at  = CURRENT_TIMESTAMP
");

$query = $pdo->prepare("
    SELECT cid,
           ROUND(AVG(pm25),  2) AS avg25,
           ROUND(MAX(pm25),  2) AS max25,
           ROUND(MIN(pm25),  2) AS min25,
           ROUND(AVG(pm10),  2) AS avg10,
           ROUND(AVG(temperature), 2) AS temp,
           ROUND(AVG(humidity),    2) AS humi,
           ROUND(AVG(co2),  2) AS co2,
           COUNT(*)             AS cnt
    FROM pm25_data
    WHERE DATE(FROM_UNIXTIME(sensor_timestamp)) = ?
      AND pm25 IS NOT NULL
    GROUP BY cid
");

$inserted = 0;
$skipped  = 0;

foreach ($datesToProcess as $date) {
    try {
        $query->execute([$date]);
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { $skipped++; continue; }
        foreach ($rows as $r) {
            $upsert->execute([
                ':cid'   => $r['cid'],
                ':date'  => $date,
                ':avg25' => $r['avg25'],
                ':max25' => $r['max25'],
                ':min25' => $r['min25'],
                ':avg10' => $r['avg10'],
                ':temp'  => $r['temp'],
                ':humi'  => $r['humi'],
                ':co2'   => $r['co2'],
                ':cnt'   => $r['cnt'],
            ]);
            $inserted++;
        }
    } catch (Exception $e) {
        logMsg('ERROR ' . $date . ': ' . $e->getMessage(), $log);
    }
}

logMsg("เสร็จสิ้น — upsert: {$inserted} rows, skip (ไม่มีข้อมูล): {$skipped} วัน", $log);

// ── Output (browser) ──────────────────────────────────────────────────────────
if (!$isCron): ?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<title>อัปเดต PM2.5 Daily Summary</title>
<link rel="icon" type="image/png" href="public/assets/images/logo/rangsit-small-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@400;500&display=swap" rel="stylesheet">
<style>* { font-family:'Kanit',sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 max-w-lg w-full">
    <h1 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
        <span class="text-2xl">✅</span> อัปเดต Daily Summary เสร็จสิ้น
    </h1>
    <div class="bg-slate-800 rounded-xl p-4 font-mono text-xs text-green-400 space-y-1 mb-5">
        <?php foreach ($log as $l): ?>
        <div><?= htmlspecialchars($l) ?></div>
        <?php endforeach; ?>
    </div>
    <div class="flex gap-3">
        <a href="pm25_report.php#yearly"
           class="flex-1 text-center py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-sm font-semibold transition-colors">
            ดูรายงาน
        </a>
        <a href="pm25_summary_update.php"
           class="flex-1 text-center py-2.5 border border-slate-200 hover:bg-slate-50 text-slate-600 rounded-xl text-sm transition-colors">
            รันอีกครั้ง
        </a>
    </div>
</div>
</body>
</html>
<?php endif;
