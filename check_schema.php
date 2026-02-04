<?php
require 'config/database.php';
echo "=== task_assignments columns ===\n";
$result = $conn->query('DESC task_assignments');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}

echo "\n=== service_requests columns ===\n";
$result = $conn->query('DESC service_requests');
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' (' . $row['Type'] . ")\n";
}
?>
