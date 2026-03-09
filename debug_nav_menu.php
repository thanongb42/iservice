<?php
require_once 'config/database.php';

echo "<h2>Nav Menu Debug</h2>";

// Check total menus
$result = $conn->query("SELECT COUNT(*) as cnt FROM nav_menu");
$data = $result->fetch_assoc();
echo "<p>Total menus: " . $data['cnt'] . "</p>";

// List all menus
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Menu Name</th><th>Menu Order</th><th>Parent ID</th><th>Active</th><th>URL</th></tr>";

$result = $conn->query("SELECT id, menu_name, menu_order, parent_id, is_active, menu_url FROM nav_menu ORDER BY parent_id IS NULL DESC, parent_id, menu_order");
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['menu_name'] . "</td>";
    echo "<td>" . $row['menu_order'] . "</td>";
    echo "<td>" . ($row['parent_id'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "<td>" . $row['menu_url'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check for orphaned menus (parent_id doesn't exist)
echo "<h3>Orphaned Menus Check:</h3>";
$result = $conn->query("SELECT m.id, m.menu_name, m.parent_id FROM nav_menu m WHERE m.parent_id IS NOT NULL AND m.parent_id NOT IN (SELECT id FROM nav_menu)");
if ($result->num_rows > 0) {
    echo "<p style='color:red;'>Found orphaned menus:</p>";
    while ($row = $result->fetch_assoc()) {
        echo "ID: " . $row['id'] . " | " . $row['menu_name'] . " | Parent ID: " . $row['parent_id'] . "<br>";
    }
} else {
    echo "<p style='color:green;'>No orphaned menus found.</p>";
}
?>
