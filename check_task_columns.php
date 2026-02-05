<?php
require 'config/database.php';

echo "task_assignments columns:\n";
$result = $conn->query('DESCRIBE task_assignments');
while($row = $result->fetch_assoc()) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
