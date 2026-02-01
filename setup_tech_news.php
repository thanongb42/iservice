<?php
/**
 * Setup Tech News Database Table
 * สร้างตารางข่าวเทคโนโลยี
 */

require_once 'config/database.php';

echo "<h2>Setup Tech News Database</h2>";

// Create tech_news table
$sql = "CREATE TABLE IF NOT EXISTS tech_news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(500) NOT NULL,
    description TEXT NOT NULL,
    content TEXT,
    category VARCHAR(100) NOT NULL,
    category_color VARCHAR(50) DEFAULT 'blue',
    cover_image VARCHAR(500),
    author VARCHAR(200),
    view_count INT DEFAULT 0,
    is_pinned TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    display_order INT DEFAULT 0,
    tags VARCHAR(500),
    published_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pinned (is_pinned, display_order),
    INDEX idx_active (is_active),
    INDEX idx_category (category),
    INDEX idx_published (published_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Table 'tech_news' created successfully!</p>";
} else {
    echo "<p style='color: red;'>Error creating table: " . $conn->error . "</p>";
}

// Insert sample data
$sample_news = [
    [
        'title' => 'AI และ Machine Learning ปฏิวัติวงการเทคโนโลยี',
        'description' => 'เทคโนโลยี AI กำลังเปลี่ยนแปลงโลกด้วยความสามารถในการเรียนรู้และประมวลผลข้อมูลขนาดใหญ่',
        'content' => '<p>ปัญญาประดิษฐ์หรือ AI กำลังกลายเป็นเทคโนโลยีหลักที่ขับเคลื่อนการเปลี่ยนแปลงในทุกอุตสาหกรรม จากการวิเคราะห์ข้อมูล ไปจนถึงการพัฒนาผลิตภัณฑ์ใหม่ๆ</p><p>Machine Learning ช่วยให้ระบบสามารถเรียนรู้จากข้อมูลและปรับปรุงประสิทธิภาพได้เอง โดยไม่จำเป็นต้องเขียนโปรแกรมทุกกรณี</p>',
        'category' => 'ปัญญาประดิษฐ์',
        'category_color' => 'blue',
        'cover_image' => '',
        'author' => 'ทีมข่าวเทคโนโลยี',
        'is_pinned' => 1,
        'display_order' => 1,
        'tags' => 'AI,Machine Learning,ปัญญาประดิษฐ์,เทคโนโลยี',
        'published_date' => date('Y-m-d')
    ],
    [
        'title' => 'Cloud Technology สำหรับองค์กรยุคดิจิทัล',
        'description' => 'เทคโนโลยี Cloud Computing ช่วยให้องค์กรสามารถจัดเก็บและประมวลผลข้อมูลได้อย่างมีประสิทธิภาพ',
        'content' => '<p>Cloud Computing เป็นเทคโนโลยีที่ช่วยลดต้นทุนการดูแลระบบ IT และเพิ่มความยืดหยุ่นในการขยายระบบ</p><p>องค์กรสามารถเลือกใช้บริการแบบ IaaS, PaaS หรือ SaaS ตามความต้องการ</p>',
        'category' => 'Cloud Computing',
        'category_color' => 'green',
        'cover_image' => '',
        'author' => 'ทีมข่าวเทคโนโลยี',
        'is_pinned' => 1,
        'display_order' => 2,
        'tags' => 'Cloud,AWS,Azure,GCP,Cloud Computing',
        'published_date' => date('Y-m-d', strtotime('-1 day'))
    ],
    [
        'title' => 'ความปลอดภัยไซเบอร์ในยุคดิจิทัล',
        'description' => 'แนวทางการป้องกันภัยคุกคามทางไซเบอร์และการรักษาความปลอดภัยข้อมูลสำหรับองค์กร',
        'content' => '<p>การรักษาความปลอดภัยข้อมูลเป็นสิ่งสำคัญที่สุดในยุคดิจิทัล องค์กรต้องมีมาตรการป้องกันที่แข็งแกร่ง</p><p>รวมถึงการใช้ Encryption, Multi-Factor Authentication และการฝึกอบรมพนักงาน</p>',
        'category' => 'Cybersecurity',
        'category_color' => 'red',
        'cover_image' => '',
        'author' => 'ทีมข่าวเทคโนโลยี',
        'is_pinned' => 1,
        'display_order' => 3,
        'tags' => 'Security,Cybersecurity,ความปลอดภัย,Encryption',
        'published_date' => date('Y-m-d', strtotime('-2 days'))
    ],
    [
        'title' => 'Google เปิดตัว AI Model ใหม่ Gemini 2.0 พร้อมความสามารถขั้นสูง',
        'description' => 'Google ประกาศเปิดตัว Gemini 2.0 โมเดล AI รุ่นใหม่ที่มีความสามารถในการประมวลผลและเข้าใจบริบทได้ดีขึ้น',
        'content' => '<p>Gemini 2.0 เป็น AI Model ที่พัฒนาขึ้นใหม่จาก Google มีความสามารถในการเข้าใจและตอบสนองต่อคำถามที่ซับซ้อนได้ดีขึ้น</p>',
        'category' => 'ปัญญาประดิษฐ์',
        'category_color' => 'blue',
        'cover_image' => '',
        'author' => 'ทีมข่าวเทคโนโลยี',
        'is_pinned' => 0,
        'display_order' => 4,
        'tags' => 'Google,AI,Gemini,Machine Learning',
        'published_date' => date('Y-m-d', strtotime('-3 days'))
    ],
    [
        'title' => 'AWS ประกาศบริการ Cloud Computing รุ่นใหม่ประหยัดพลังงาน 40%',
        'description' => 'Amazon Web Services เปิดตัวบริการ Cloud Computing รุ่นใหม่ที่ช่วยลดการใช้พลังงานและต้นทุน',
        'content' => '<p>AWS ได้พัฒนา Data Center รุ่นใหม่ที่ใช้พลังงานอย่างมีประสิทธิภาพ ช่วยลดค่าใช้จ่ายและลดผลกระทบต่อสิ่งแวดล้อม</p>',
        'category' => 'Cloud Computing',
        'category_color' => 'green',
        'cover_image' => '',
        'author' => 'ทีมข่าวเทคโนโลยี',
        'is_pinned' => 0,
        'display_order' => 5,
        'tags' => 'AWS,Cloud,Green Computing,ประหยัดพลังงาน',
        'published_date' => date('Y-m-d', strtotime('-4 days'))
    ],
    [
        'title' => 'Quantum Encryption เทคโนโลยีการเข้ารหัสยุคใหม่ป้องกันแฮกเกอร์',
        'description' => 'เทคโนโลยีการเข้ารหัสแบบควอนตัมที่ไม่สามารถถูกถอดรหัสได้ด้วยคอมพิวเตอร์ทั่วไป',
        'content' => '<p>Quantum Encryption ใช้หลักการของกลศาสตร์ควอนตัมในการเข้ารหัสข้อมูล ทำให้มีความปลอดภัยสูงสุด</p>',
        'category' => 'Cybersecurity',
        'category_color' => 'red',
        'cover_image' => '',
        'author' => 'ทีมข่าวเทคโนโลยี',
        'is_pinned' => 0,
        'display_order' => 6,
        'tags' => 'Quantum,Encryption,Security,ควอนตัม',
        'published_date' => date('Y-m-d', strtotime('-5 days'))
    ]
];

foreach ($sample_news as $news) {
    $stmt = $conn->prepare("INSERT INTO tech_news (title, description, content, category, category_color, cover_image, author, is_pinned, display_order, tags, published_date)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssiiis",
        $news['title'],
        $news['description'],
        $news['content'],
        $news['category'],
        $news['category_color'],
        $news['cover_image'],
        $news['author'],
        $news['is_pinned'],
        $news['display_order'],
        $news['tags'],
        $news['published_date']
    );

    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Added: " . htmlspecialchars($news['title']) . "</p>";
    } else {
        echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
    }
}

echo "<hr>";
echo "<p><strong>Setup Complete!</strong></p>";
echo "<p><a href='index.php'>Go to Homepage</a> | <a href='admin/tech_news.php'>Manage Tech News</a></p>";

$conn->close();
?>
