<?php
require_once 'config/database.php';

$result = $conn->query("SELECT id, title, cover_image, resource_url FROM learning_resources LIMIT 5");

echo "<h2>Debug Image Paths</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Title</th><th>Cover Image Path</th><th>Resource URL Path</th><th>Image Preview</th></tr>";

while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
    echo "<td><code>" . htmlspecialchars($row['cover_image']) . "</code></td>";
    echo "<td><code>" . htmlspecialchars($row['resource_url']) . "</code></td>";
    echo "<td>";

    if (!empty($row['cover_image'])) {
        // ลองแสดงรูปด้วย path ที่บันทึกไว้
        echo "<img src='" . htmlspecialchars($row['cover_image']) . "' style='max-width: 150px; max-height: 100px; border: 2px solid red;' onerror=\"this.parentElement.innerHTML += '<br><strong style=color:red>ERROR: Image not found</strong>'\">";

        // ตรวจสอบว่าไฟล์มีอยู่จริงหรือไม่
        $file_path = $row['cover_image'];
        if (file_exists($file_path)) {
            echo "<br><span style='color: green;'>✓ File exists</span>";
        } else {
            echo "<br><span style='color: red;'>✗ File NOT found at: " . $file_path . "</span>";

            // ลอง path อื่น
            $alt_path = str_replace('../', '', $file_path);
            if (file_exists($alt_path)) {
                echo "<br><span style='color: blue;'>✓ Found at: " . $alt_path . "</span>";
            }
        }
    } else {
        echo "<em>No image</em>";
    }

    echo "</td>";
    echo "</tr>";
}

echo "</table>";
?>
