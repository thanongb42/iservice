<?php
session_start();
require_once '../../config/database.php';
require_manager_or_admin();

header('Content-Type: application/json; charset=utf-8');

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date   = $_GET['end_date']   ?? date('Y-m-d');

// Validate
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) $start_date = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date))   $end_date   = date('Y-m-d');

if ($start_date > $end_date) {
    [$start_date, $end_date] = [$end_date, $start_date];
}

if (!function_exists('table_exists') || !table_exists('visitor_stats')) {
    echo json_encode(['success' => false, 'message' => 'visitor_stats table not found']);
    exit;
}

$stmt = $conn->prepare(
    "SELECT visit_date, SUM(visit_count) AS total_hits
     FROM visitor_stats
     WHERE visit_date BETWEEN ? AND ?
     GROUP BY visit_date
     ORDER BY visit_date ASC"
);
$stmt->bind_param('ss', $start_date, $end_date);
$stmt->execute();
$res = $stmt->get_result();

$labels = [];
$data   = [];
$total  = 0;

while ($row = $res->fetch_assoc()) {
    $labels[] = $row['visit_date'];
    $d = (int)$row['total_hits'];
    $data[]  = $d;
    $total  += $d;
}
$stmt->close();

// Summary stats
$max  = !empty($data) ? max($data) : 0;
$avg  = !empty($data) ? round($total / count($data), 1) : 0;

echo json_encode([
    'success'    => true,
    'labels'     => $labels,
    'data'       => $data,
    'total'      => $total,
    'max'        => $max,
    'avg'        => $avg,
    'days'       => count($data),
    'start_date' => $start_date,
    'end_date'   => $end_date,
]);
