<?php
/**
 * Fix Image Paths in Database
 * แก้ไข path รูปภาพที่มี ../ prefix ให้เป็น path ที่ถูกต้อง
 */

require_once 'config/database.php';

echo "<h2>Fix Image Paths Script</h2>";
echo "<p>กำลังแก้ไข path รูปภาพในฐานข้อมูล...</p>";

// Get all resources with cover_image or resource_url
$result = $conn->query("SELECT id, cover_image, resource_url FROM learning_resources");

$updated_count = 0;
$errors = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $cover_image = $row['cover_image'];
    $resource_url = $row['resource_url'];
    $needs_update = false;

    // Fix cover_image path
    $new_cover_image = $cover_image;
    if (!empty($cover_image) && !preg_match('/^https?:\/\//', $cover_image)) {
        $new_cover_image = str_replace('../', '', $cover_image);
        if ($new_cover_image !== $cover_image) {
            $needs_update = true;
        }
    }

    // Fix resource_url path
    $new_resource_url = $resource_url;
    if (!empty($resource_url) && !preg_match('/^https?:\/\//', $resource_url)) {
        $new_resource_url = str_replace('../', '', $resource_url);
        if ($new_resource_url !== $resource_url) {
            $needs_update = true;
        }
    }

    // Update if needed
    if ($needs_update) {
        $stmt = $conn->prepare("UPDATE learning_resources SET cover_image = ?, resource_url = ? WHERE id = ?");
        $stmt->bind_param("ssi", $new_cover_image, $new_resource_url, $id);

        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ แก้ไข ID {$id}:</p>";
            if ($cover_image !== $new_cover_image) {
                echo "<p style='margin-left: 20px;'>Cover: <code>{$cover_image}</code> → <code>{$new_cover_image}</code></p>";
            }
            if ($resource_url !== $new_resource_url) {
                echo "<p style='margin-left: 20px;'>Resource: <code>{$resource_url}</code> → <code>{$new_resource_url}</code></p>";
            }
            $updated_count++;
        } else {
            $errors[] = "Error updating ID {$id}: " . $conn->error;
        }
    }
}

echo "<hr>";
echo "<h3>สรุปผลลัพธ์:</h3>";
echo "<p><strong>จำนวนรายการที่แก้ไข:</strong> {$updated_count}</p>";

if (!empty($errors)) {
    echo "<p style='color: red;'><strong>Errors:</strong></p>";
    foreach ($errors as $error) {
        echo "<p style='color: red;'>✗ {$error}</p>";
    }
} else {
    echo "<p style='color: green;'>✓ ไม่มีข้อผิดพลาด</p>";
}

if ($updated_count === 0) {
    echo "<p style='color: blue;'>ℹ️ ไม่มีข้อมูลที่ต้องแก้ไข หรือข้อมูลถูกต้องแล้ว</p>";
}

echo "<hr>";
echo "<p><a href='index.php' style='color: blue; text-decoration: underline;'>กลับไปหน้าแรก</a> | ";
echo "<a href='check_learning_resources.php' style='color: blue; text-decoration: underline;'>ตรวจสอบข้อมูล</a></p>";

$conn->close();
?>
