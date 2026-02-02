<?php
/**
 * Test Tech News Display
 * ทดสอบการแสดงรายการข่าวจาก tech_news.php
 */

require_once 'config/database.php';
session_start();

// ดึงข้อมูลเหมือนใน tech_news.php
$news_list = [];
$result = $conn->query("SELECT * FROM tech_news ORDER BY is_pinned DESC, display_order ASC, created_at DESC");

if (!$result) {
    echo "Query Error: " . $conn->error . "\n";
    exit;
}

while ($row = $result->fetch_assoc()) {
    $news_list[] = $row;
}

echo "Total News Items: " . count($news_list) . "\n";
echo "Pinned Count: " . count(array_filter($news_list, fn($n) => $n['is_pinned'])) . "\n\n";

echo "News List:\n";
foreach ($news_list as $i => $news) {
    echo ($i + 1) . ". [ID: {$news['id']}] {$news['title']}\n";
    echo "   Category: {$news['category']}, Active: {$news['is_active']}, Pinned: {$news['is_pinned']}\n";
    echo "   Description: " . substr($news['description'], 0, 50) . "...\n";
}
?>
