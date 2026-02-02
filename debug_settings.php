<?php
require_once 'config/database.php';
$sql = "SELECT * FROM system_settings";
$result = $conn->query($sql);
while ($row = $result->fetch_assoc()) {
    echo $row['setting_key'] . ": " . $row['setting_value'] . "\n";
}
?>