<?php
require_once 'config/database.php';
$result = $conn->query('DESCRIBE service_requests');
echo "Columns in service_requests:\n";
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
?>
