<?php
/**
 * Get Department Children
 * Returns child departments for a given parent_id (for cascading selectors)
 */

require_once '../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$parent_id = intval($_GET['parent_id'] ?? 0);

if ($parent_id <= 0) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT department_id, department_name, level_type
    FROM departments
    WHERE parent_department_id = ? AND status = 'active'
    ORDER BY department_name ASC
");
$stmt->bind_param('i', $parent_id);
$stmt->execute();
$result = $stmt->get_result();

$children = [];
while ($row = $result->fetch_assoc()) {
    $children[] = $row;
}

echo json_encode($children, JSON_UNESCAPED_UNICODE);
