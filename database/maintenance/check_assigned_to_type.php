<?php
require_once 'config/database.php';

$result = $conn->query("DESCRIBE service_requests");

echo "<h3>Column assigned_to info:</h3>";
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'assigned_to') {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
}

$conn->close();
?>
