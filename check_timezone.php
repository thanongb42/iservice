<?php
echo "=== Server Timezone ===" . PHP_EOL;
echo "Default timezone: " . date_default_timezone_get() . PHP_EOL;
echo "Current time: " . date('Y-m-d H:i:s T') . PHP_EOL;
echo "Current UTC offset: " . date('P') . PHP_EOL;

echo "\n=== Test Date Parse ===" . PHP_EOL;
$startTime = "2026-02-07 08:31:00";
$date = new DateTime($startTime);
echo "Start time string: $startTime" . PHP_EOL;
echo "DateTime object: " . $date->format('Y-m-d H:i:s') . PHP_EOL;
echo "DateTime ISO: " . $date->format(DateTime::ISO8601) . PHP_EOL;
echo "DateTime timestamp: " . $date->getTimestamp() . PHP_EOL;

// Try with UTC
$dateUtc = new DateTime($startTime, new DateTimeZone('UTC'));
echo "\nAs UTC: " . $dateUtc->format('Y-m-d H:i:s') . PHP_EOL;
echo "UTC timestamp: " . $dateUtc->getTimestamp() . PHP_EOL;
?>
