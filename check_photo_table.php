<?php
require 'config/database.php';

echo "request_photography_details columns:\n";
$result = $conn->query('DESCRIBE request_photography_details');
if ($result) {
    while($row = $result->fetch_assoc()) {
        echo $row['Field'] . ' - ' . $row['Type'] . "\n";
    }
} else {
    echo "Table does not exist\n";
}
?>
