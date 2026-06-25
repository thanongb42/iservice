<?php
/**
 * Cron Runner — triggers due jobs from `cron_schedules`.
 * CLI-only. One OS-level crontab entry calls this every minute; it decides
 * which jobs are actually due and hits their target_url. Manage jobs via
 * admin/cron_schedules.php instead of editing the OS crontab directly.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

date_default_timezone_set('Asia/Bangkok');
require_once __DIR__ . '/../config/database.php';

$pdo = getPDO();
$minutesSinceEpoch = intdiv(time(), 60);

$jobs = $pdo->query("SELECT * FROM cron_schedules WHERE is_active = 1")->fetchAll(PDO::FETCH_ASSOC);

foreach ($jobs as $job) {
    $interval = max(1, (int)$job['interval_minutes']);
    if ($minutesSinceEpoch % $interval !== 0) {
        continue; // not due this minute
    }
    if (!empty($job['last_run_at']) && (time() - strtotime($job['last_run_at'])) < 50) {
        continue; // already ran this minute (guards against double-trigger)
    }

    run_job($pdo, $job);
}

function run_job(PDO $pdo, array $job): void {
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
        $status  = 'error';
        $message = "cURL error: $curlErr";
    } elseif ($httpCode >= 200 && $httpCode < 300) {
        $status  = 'success';
        $message = substr((string)$body, 0, 500);
    } else {
        $status  = 'error';
        $message = "HTTP $httpCode" . ($body ? ' — ' . substr((string)$body, 0, 300) : '');
    }

    $pdo->prepare("
        UPDATE cron_schedules
        SET last_run_at = NOW(), last_status = ?, last_http_code = ?, last_message = ?
        WHERE id = ?
    ")->execute([$status, $httpCode ?: null, $message, $job['id']]);

    $pdo->prepare("
        INSERT INTO cron_schedule_runs (schedule_id, status, http_code, message, duration_ms)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$job['id'], $status, $httpCode ?: null, $message, $durationMs]);
}
