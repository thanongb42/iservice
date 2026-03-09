<?php
require_once __DIR__ . '/config/database.php';
$r = $conn->query('DESCRIBE task_assignments');
while($row = $r->fetch_assoc()) {
    echo $row['Field'] . ' | ' . $row['Type'] . ' | ' . $row['Null'] . ' | ' . ($row['Default'] ?? 'NULL') . PHP_EOL;
}
