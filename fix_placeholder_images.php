<?php
/**
 * Fix Placeholder Images
 * Updates all via.placeholder.com URLs to empty string in the database
 */

require_once 'config/database.php';

echo "<h2>Fix Placeholder Images in Database</h2>";
echo "<p>Removing all via.placeholder.com URLs from database...</p>";

// Fix learning_resources table
$sql_lr = "UPDATE learning_resources
           SET cover_image = ''
           WHERE cover_image LIKE '%via.placeholder.com%'";

if ($conn->query($sql_lr) === TRUE) {
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'>✓ Updated $affected records in learning_resources table</p>";
} else {
    echo "<p style='color: red;'>Error updating learning_resources: " . $conn->error . "</p>";
}

// Fix tech_news table
$sql_tn = "UPDATE tech_news
           SET cover_image = ''
           WHERE cover_image LIKE '%via.placeholder.com%'";

if ($conn->query($sql_tn) === TRUE) {
    $affected = $conn->affected_rows;
    echo "<p style='color: green;'>✓ Updated $affected records in tech_news table</p>";
} else {
    echo "<p style='color: red;'>Error updating tech_news: " . $conn->error . "</p>";
}

echo "<hr>";
echo "<p><strong>Fix Complete!</strong></p>";
echo "<p>All placeholder.com URLs have been removed. The system will now use local SVG placeholders.</p>";
echo "<p><a href='index.php'>Go to Homepage</a></p>";

$conn->close();
?>
