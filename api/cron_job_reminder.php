<?php
# Cron Job: LINE Reminder for Internal Jobs
# รันทุก 15 นาที ผ่าน Cron (ตั้งค่าบน server):
#   crontab -e  แล้วเพิ่ม:
#   15min: php /var/www/html/iservice/api/cron_job_reminder.php
#
# ส่ง LINE push 3 รอบ:
#   day_before : วันก่อนงาน เวลา 08:00-08:14
#   one_hour   : 60-75 นาทีก่อน start_time หรือ 07:00-07:14 ของวันงาน
#   on_start   : 0-15 นาทีก่อน start_time

// Allow CLI or internal server call only
$is_cli  = php_sapi_name() === 'cli';
$is_self = !empty($_SERVER['HTTP_X_CRON_SECRET'])
        && $_SERVER['HTTP_X_CRON_SECRET'] === (defined('CRON_SECRET') ? CRON_SECRET : getenv('CRON_SECRET'));

if (!$is_cli && !$is_self) {
    http_response_code(403);
    exit('Forbidden');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/line_helper.php';

$now      = new DateTime('now');
$today    = $now->format('Y-m-d');
$tomorrow = (new DateTime('tomorrow'))->format('Y-m-d');
$nowH     = (int)$now->format('H');
$nowMin   = (int)$now->format('i');
$nowTotal = $nowH * 60 + $nowMin; // minutes since midnight

$sent = 0; $failed = 0;

// ── Fetch active jobs with assignees (via job_assignments) ───
// Each row = one job × one assignee so we send to all assigned staff
$jobs_stmt = $conn->prepare("
    SELECT ij.job_id, ij.job_code, ij.title, ij.scheduled_date,
           ij.start_time, ij.end_time, ij.location, ij.status,
           u.line_user_id, u.first_name, u.last_name
    FROM internal_jobs ij
    JOIN job_assignments ja ON ij.job_id = ja.job_id
    JOIN users u ON ja.user_id = u.user_id
    WHERE ij.status IN ('scheduled', 'in_progress')
      AND ij.scheduled_date IN (?, ?)
      AND u.line_user_id IS NOT NULL
      AND u.line_user_id != ''
");
$jobs_stmt->bind_param('ss', $today, $tomorrow);
$jobs_stmt->execute();
$jobs = $jobs_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

foreach ($jobs as $job) {
    $job_id      = $job['job_id'];
    $line_uid    = $job['line_user_id'];
    $sched_date  = $job['scheduled_date'];
    $is_today    = ($sched_date === $today);
    $is_tomorrow = ($sched_date === $tomorrow);

    // Resolve numeric user_id for dedup (look up by line_user_id)
    $uid_row = $conn->query("SELECT user_id FROM users WHERE line_user_id = '" . $conn->real_escape_string($line_uid) . "' LIMIT 1")->fetch_assoc();
    $uid = $uid_row ? (int)$uid_row['user_id'] : 0;

    // Parse start_time → minutes since midnight
    $start_min = null;
    if (!empty($job['start_time'])) {
        [$sh, $sm] = explode(':', $job['start_time']);
        $start_min = (int)$sh * 60 + (int)$sm;
    }

    $name = trim($job['first_name'] . ' ' . $job['last_name']) ?: 'คุณ';

    // ── Helper: check already sent (per job + user + type) ──
    $already_sent = function(string $type) use ($conn, $job_id, $uid): bool {
        $chk = $conn->prepare("SELECT id FROM job_notification_log WHERE job_id = ? AND user_id = ? AND notify_type = ?");
        $chk->bind_param('iis', $job_id, $uid, $type);
        $chk->execute();
        return $chk->get_result()->num_rows > 0;
    };

    // ── Helper: record send ──────────────────────────────────
    $record = function(string $type, bool $ok, string $err = '') use ($conn, $job_id, $uid): void {
        $ins = $conn->prepare("INSERT IGNORE INTO job_notification_log (job_id, user_id, notify_type, success, error_msg) VALUES (?, ?, ?, ?, ?)");
        $s = $ok ? 1 : 0;
        $ins->bind_param('iiisi', $job_id, $uid, $type, $s, $err);
        $ins->execute();
    };

    // ── Format date Thai ────────────────────────────────────
    $thai_months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.',
                    'ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
    $dt   = new DateTime($sched_date);
    $d    = (int)$dt->format('j');
    $m    = $thai_months[(int)$dt->format('n')];
    $y    = (int)$dt->format('Y') + 543;
    $date_thai = "{$d} {$m} {$y}";

    $time_str = '';
    if (!empty($job['start_time'])) {
        $time_str = substr($job['start_time'], 0, 5);
        if (!empty($job['end_time'])) $time_str .= '–' . substr($job['end_time'], 0, 5);
        $time_str .= ' น.';
    }
    $loc_str = !empty($job['location']) ? "\n📍 " . $job['location'] : '';

    $detail_url = "https://iservice.rangsitcity.go.th/admin/internal_job_detail.php?id={$job_id}";

    // ══════════════════════════════════════════════════════════
    // 1. DAY_BEFORE — ส่งวันก่อน เวลา 08:00–08:14
    // ══════════════════════════════════════════════════════════
    if ($is_tomorrow && $nowH === 8 && $nowMin < 15) {
        if (!$already_sent('day_before')) {
            $msg = "🔔 แจ้งเตือนงานพรุ่งนี้\n\n"
                 . "📋 {$job['title']}\n"
                 . "🔑 {$job['job_code']}\n"
                 . "📅 {$date_thai}"
                 . ($time_str ? " เวลา {$time_str}" : '')
                 . $loc_str . "\n\n"
                 . "ดูรายละเอียด:\n{$detail_url}\n\n"
                 . "— iService เทศบาลนครรังสิต";
            $ok = send_line_push_to_user($job['line_user_id'], $msg, $conn);
            $record('day_before', $ok);
            $ok ? $sent++ : $failed++;
        }
    }

    // ══════════════════════════════════════════════════════════
    // 2. ONE_HOUR — ส่ง 60–75 นาทีก่อน start_time (วันนี้)
    //    ถ้าไม่มี start_time → ส่ง 07:00–07:14 ของวันงาน
    // ══════════════════════════════════════════════════════════
    if ($is_today && !$already_sent('one_hour')) {
        $trigger = false;
        if ($start_min !== null) {
            $diff = $start_min - $nowTotal;      // minutes remaining
            $trigger = ($diff >= 60 && $diff < 75);
        } else {
            $trigger = ($nowH === 7 && $nowMin < 15);
        }
        if ($trigger) {
            $when = $start_min !== null ? "อีก 1 ชั่วโมง" : "วันนี้";
            $msg  = "⏰ แจ้งเตือนงาน{$when}\n\n"
                  . "📋 {$job['title']}\n"
                  . "🔑 {$job['job_code']}\n"
                  . "📅 {$date_thai}"
                  . ($time_str ? " เวลา {$time_str}" : '')
                  . $loc_str . "\n\n"
                  . "ดูรายละเอียด:\n{$detail_url}\n\n"
                  . "— iService เทศบาลนครรังสิต";
            $ok = send_line_push_to_user($job['line_user_id'], $msg, $conn);
            $record('one_hour', $ok);
            $ok ? $sent++ : $failed++;
        }
    }

    // ══════════════════════════════════════════════════════════
    // 3. ON_START — ส่ง 0–15 นาทีก่อน start_time (วันนี้ + มี start_time)
    // ══════════════════════════════════════════════════════════
    if ($is_today && $start_min !== null && !$already_sent('on_start')) {
        $diff = $start_min - $nowTotal;
        if ($diff >= 0 && $diff < 15) {
            $msg = "🚀 ถึงเวลางานของคุณแล้ว!\n\n"
                 . "📋 {$job['title']}\n"
                 . "🔑 {$job['job_code']}\n"
                 . "🕐 เวลา {$time_str}"
                 . $loc_str . "\n\n"
                 . "ดูรายละเอียด:\n{$detail_url}\n\n"
                 . "— iService เทศบาลนครรังสิต";
            $ok = send_line_push_to_user($job['line_user_id'], $msg, $conn);
            $record('on_start', $ok);
            $ok ? $sent++ : $failed++;
        }
    }
}

$log = "[" . $now->format('Y-m-d H:i:s') . "] cron_job_reminder: checked=" . count($jobs)
     . " sent={$sent} failed={$failed}";
error_log($log);
if ($is_cli) echo $log . PHP_EOL;
