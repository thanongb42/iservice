<?php
/**
 * Check Learning Resources Data
 * ตรวจสอบข้อมูลในตาราง learning_resources
 */

require_once 'config/database.php';

echo "<h2>Learning Resources Data Check</h2>";

// Check if table exists
$table_check = $conn->query("SHOW TABLES LIKE 'learning_resources'");

if ($table_check->num_rows === 0) {
    echo "<p style='color: red;'>❌ Table 'learning_resources' does NOT exist!</p>";
    echo "<p>Please run setup.php to create the table.</p>";
    exit;
}

echo "<p style='color: green;'>✓ Table 'learning_resources' exists</p>";

// Count total records
$result = $conn->query("SELECT COUNT(*) as total FROM learning_resources");
$row = $result->fetch_assoc();
$total = $row['total'];

echo "<p><strong>Total records:</strong> {$total}</p>";

// Show sample data
if ($total > 0) {
    echo "<h3>Sample Data (First 5 records):</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr>
            <th>ID</th>
            <th>Title</th>
            <th>Type</th>
            <th>Category</th>
            <th>Active</th>
            <th>Featured</th>
            <th>Views</th>
          </tr>";

    $result = $conn->query("SELECT id, title, resource_type, category, is_active, is_featured, view_count FROM learning_resources ORDER BY created_at DESC LIMIT 5");

    while ($row = $result->fetch_assoc()) {
        $active_icon = $row['is_active'] ? '✓' : '✗';
        $featured_icon = $row['is_featured'] ? '⭐' : '';

        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>" . htmlspecialchars($row['title']) . "</td>";
        echo "<td>{$row['resource_type']}</td>";
        echo "<td>" . htmlspecialchars($row['category']) . "</td>";
        echo "<td>{$active_icon}</td>";
        echo "<td>{$featured_icon}</td>";
        echo "<td>{$row['view_count']}</td>";
        echo "</tr>";
    }

    echo "</table>";

    // Check active resources
    $active_result = $conn->query("SELECT COUNT(*) as active_count FROM learning_resources WHERE is_active = 1");
    $active_row = $active_result->fetch_assoc();

    echo "<p><strong>Active resources:</strong> {$active_row['active_count']}</p>";
    echo "<p style='color: blue;'>ℹ️ These active resources will be displayed on the homepage.</p>";

} else {
    echo "<p style='color: orange;'>⚠️ No data in table. Please add some learning resources via admin panel.</p>";
    echo "<p><a href='admin/learning_resources.php'>Go to Learning Resources Admin →</a></p>";
}

$conn->close();
?>
