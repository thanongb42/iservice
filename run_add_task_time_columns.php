<?php
/**
 * Migration: เพิ่ม start_time/end_time columns ใน task_assignments
 * รันไฟล์นี้บน Production: http://iservice.rangsitcity.go.th/run_add_task_time_columns.php
 */
require_once 'config/database.php';

echo "<h2>Migration: Add start_time/end_time to task_assignments</h2>";
echo "<pre>";

// Check current columns
$result = $conn->query("SHOW COLUMNS FROM task_assignments");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}
echo "Current columns: " . implode(', ', $columns) . "\n\n";

$changes = 0;

// Add start_time if missing
if (!in_array('start_time', $columns)) {
    $sql = "ALTER TABLE task_assignments ADD COLUMN start_time DATETIME DEFAULT NULL COMMENT 'เวลาเริ่มงาน (จาก event)' AFTER started_at";
    if ($conn->query($sql)) {
        echo "✅ Added 'start_time' column\n";
        $changes++;
    } else {
        echo "❌ Failed to add 'start_time': " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ 'start_time' column already exists\n";
}

// Add end_time if missing
if (!in_array('end_time', $columns)) {
    // Find position - after completed_at or after start_time
    $after = in_array('start_time', $columns) || $changes > 0 ? 'completed_at' : 'started_at';
    $sql = "ALTER TABLE task_assignments ADD COLUMN end_time DATETIME DEFAULT NULL COMMENT 'เวลาสิ้นสุดงาน (จาก event)' AFTER completed_at";
    if ($conn->query($sql)) {
        echo "✅ Added 'end_time' column\n";
        $changes++;
    } else {
        echo "❌ Failed to add 'end_time': " . $conn->error . "\n";
    }
} else {
    echo "ℹ️ 'end_time' column already exists\n";
}

echo "\n--- After migration ---\n";
$result = $conn->query("SHOW COLUMNS FROM task_assignments");
while ($row = $result->fetch_assoc()) {
    echo $row['Field'] . " | " . $row['Type'] . " | " . ($row['Null'] === 'YES' ? 'NULL' : 'NOT NULL') . " | " . ($row['Default'] ?? 'none') . "\n";
}

echo "\n" . ($changes > 0 ? "✅ Migration completed with $changes changes" : "ℹ️ No changes needed") . "\n";
echo "</pre>";
?>
