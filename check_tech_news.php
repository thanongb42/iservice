<?php
/**
 * Check Tech News Database
 * ตรวจสอบตาราและข้อมูล tech_news
 */

require_once 'config/database.php';

echo "=== Tech News Database Check ===\n\n";

// Check if table exists
$tables = $conn->query("SHOW TABLES LIKE 'tech_news'")->fetch_all();
echo "1. Table Exists: " . (!empty($tables) ? "YES ✓" : "NO ✗") . "\n";

if (empty($tables)) {
    echo "\nTable 'tech_news' does not exist. Need to create it.\n";
    exit;
}

// Get table structure
echo "\n2. Table Structure:\n";
$structure = $conn->query("DESCRIBE tech_news")->fetch_all(MYSQLI_ASSOC);
foreach ($structure as $col) {
    echo "   - {$col['Field']}: {$col['Type']} ({$col['Null']}, Default: {$col['Default']})\n";
}

// Count records
$count = $conn->query("SELECT COUNT(*) as cnt FROM tech_news")->fetch_assoc();
echo "\n3. Total Records: " . $count['cnt'] . "\n";

// Show pinned news
$pinned = $conn->query("SELECT COUNT(*) as cnt FROM tech_news WHERE is_pinned = 1")->fetch_assoc();
echo "4. Pinned News: " . $pinned['cnt'] . "\n";

// Show active news
$active = $conn->query("SELECT COUNT(*) as cnt FROM tech_news WHERE is_active = 1")->fetch_assoc();
echo "5. Active News: " . $active['cnt'] . "\n";

// Show sample of records
echo "\n6. Sample Records:\n";
$sample = $conn->query("SELECT id, title, category_color, is_active, is_pinned, created_at FROM tech_news ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

if (empty($sample)) {
    echo "   NO RECORDS FOUND - Database is empty!\n";
} else {
    foreach ($sample as $row) {
        echo "   ID: {$row['id']}, Title: {$row['title']}, Active: {$row['is_active']}, Pinned: {$row['is_pinned']}\n";
    }
}

echo "\n=== End Check ===\n";
?>
