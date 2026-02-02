<?php
/**
 * Tech News Loader
 * โหลดข่าวเทคโนโลยีจาก database และสร้าง HTML
 */

require_once __DIR__ . '/../config/database.php';

/**
 * ดึงข่าวที่ปักหมุด (สูงสุด 4 ข่าว)
 */
function get_pinned_tech_news() {
    global $conn;

    $sql = "SELECT * FROM tech_news
            WHERE is_active = 1 AND is_pinned = 1
            ORDER BY display_order ASC
            LIMIT 4";

    $result = $conn->query($sql);

    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}

/**
 * ดึงข่าวล่าสุด (ไม่รวมข่าวที่ปักหมุด)
 */
function get_latest_tech_news($limit = 3) {
    global $conn;

    $sql = "SELECT * FROM tech_news
            WHERE is_active = 1 AND is_pinned = 0
            ORDER BY published_date DESC, created_at DESC
            LIMIT " . intval($limit);

    $result = $conn->query($sql);

    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}

/**
 * ดึงข่าวทั้งหมดที่เปิดใช้งาน
 */
function get_all_tech_news($limit = null) {
    global $conn;

    $sql = "SELECT * FROM tech_news
            WHERE is_active = 1
            ORDER BY is_pinned DESC, display_order ASC, published_date DESC";

    if ($limit !== null) {
        $sql .= " LIMIT " . intval($limit);
    }

    $result = $conn->query($sql);

    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    return [];
}

/**
 * ดึงรายละเอียดข่าวเดี่ยว
 */
function get_tech_news_detail($id) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM tech_news WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Increment view count using prepared statement for SQL injection prevention
        $update_stmt = $conn->prepare("UPDATE tech_news SET view_count = view_count + 1 WHERE id = ?");
        $update_stmt->bind_param("i", $id);
        $update_stmt->execute();
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * แสดงผลข่าวเทคโนโลยีแบบ Card
 */
function render_tech_news_cards($news_list) {
    if (empty($news_list)) {
        return '<div class="col-span-full text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-6xl mb-4"></i>
                    <p>ยังไม่มีข่าวเทคโนโลยี</p>
                </div>';
    }

    $category_colors = [
        'blue' => ['bg' => 'bg-blue-600', 'badge_bg' => 'bg-blue-100', 'badge_text' => 'text-blue-700'],
        'green' => ['bg' => 'bg-green-600', 'badge_bg' => 'bg-green-100', 'badge_text' => 'text-green-700'],
        'red' => ['bg' => 'bg-red-600', 'badge_bg' => 'bg-red-100', 'badge_text' => 'text-red-700'],
        'purple' => ['bg' => 'bg-purple-600', 'badge_bg' => 'bg-purple-100', 'badge_text' => 'text-purple-700'],
        'orange' => ['bg' => 'bg-orange-600', 'badge_bg' => 'bg-orange-100', 'badge_text' => 'text-orange-700'],
        'teal' => ['bg' => 'bg-teal-600', 'badge_bg' => 'bg-teal-100', 'badge_text' => 'text-teal-700'],
        'indigo' => ['bg' => 'bg-indigo-600', 'badge_bg' => 'bg-indigo-100', 'badge_text' => 'text-indigo-700'],
    ];

    $category_icons = [
        'AI' => 'fa-robot',
        'ปัญญาประดิษฐ์' => 'fa-brain',
        'Cloud' => 'fa-cloud',
        'Cloud Computing' => 'fa-cloud-upload-alt',
        'Security' => 'fa-shield-alt',
        'Cybersecurity' => 'fa-lock',
        'Database' => 'fa-database',
        'Programming' => 'fa-code',
        'Web' => 'fa-globe',
        'Mobile' => 'fa-mobile-alt',
    ];

    $html = '';

    foreach ($news_list as $news) {
        $colors = $category_colors[$news['category_color']] ?? $category_colors['blue'];

        // Fix image path
        $cover_image = $news['cover_image'];
        if (!empty($cover_image) && !preg_match('/^https?:\/\//', $cover_image)) {
            $cover_image = str_replace('../', '', $cover_image);
        }
        if (empty($cover_image)) {
            // Use SVG data URI for placeholder (no external dependency)
            $cover_image = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'250\'%3E%3Crect fill=\'%23E5E7EB\' width=\'400\' height=\'250\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E';
        }

        // Get icon for category
        $icon = 'fa-newspaper';
        foreach ($category_icons as $cat => $cat_icon) {
            if (stripos($news['category'], $cat) !== false) {
                $icon = $cat_icon;
                break;
            }
        }

        $html .= '
        <div class="bg-white rounded-lg shadow-md overflow-hidden card-hover transition-all group">
            <div class="relative overflow-hidden">
                <img src="' . htmlspecialchars($cover_image) . '"
                     alt="' . htmlspecialchars($news['title']) . '"
                     class="w-full h-48 object-cover transform group-hover:scale-110 transition-transform duration-500"
                     onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\\\'http://www.w3.org/2000/svg\\\' width=\\\'400\\\' height=\\\'250\\\'%3E%3Crect fill=\\\'%23E5E7EB\\\' width=\\\'400\\\' height=\\\'250\\\'/%3E%3Ctext fill=\\\'%236B7280\\\' font-family=\\\'Arial,sans-serif\\\' font-size=\\\'18\\\' x=\\\'50%25\\\' y=\\\'50%25\\\' text-anchor=\\\'middle\\\' dominant-baseline=\\\'middle\\\'%3ENo Image%3C/text%3E%3C/svg%3E\'; this.onerror=null;">

                <div class="absolute top-3 right-3">
                    <span class="' . $colors['bg'] . ' text-white text-xs px-3 py-1 rounded-full shadow-lg">
                        <i class="fas ' . $icon . ' mr-1"></i>' . htmlspecialchars($news['category']) . '
                    </span>
                </div>';

        if ($news['is_pinned']) {
            $html .= '
                <div class="absolute top-3 left-3">
                    <span class="bg-yellow-400 text-yellow-900 text-xs px-3 py-1 rounded-full shadow-lg font-bold">
                        <i class="fas fa-star mr-1"></i>ปักหมุด
                    </span>
                </div>';
        }

        $html .= '
            </div>

            <div class="p-4">
                <span class="' . $colors['badge_bg'] . ' ' . $colors['badge_text'] . ' text-xs px-2 py-1 rounded inline-block mb-2">
                    <i class="fas ' . $icon . ' mr-1"></i>' . htmlspecialchars($news['category']) . '
                </span>

                <h4 class="font-bold mt-2 mb-2 text-sm md:text-base group-hover:text-teal-600 transition-colors line-clamp-2">
                    ' . htmlspecialchars($news['title']) . '
                </h4>

                <p class="text-gray-600 text-xs md:text-sm line-clamp-3 mb-3">
                    ' . htmlspecialchars($news['description']) . '
                </p>

                <div class="flex items-center justify-between text-xs text-gray-500 mt-3">
                    <span><i class="far fa-calendar mr-1"></i>' . date('d/m/Y', strtotime($news['published_date'])) . '</span>
                    <span><i class="far fa-eye mr-1"></i>' . number_format($news['view_count']) . ' views</span>
                </div>
            </div>
        </div>';
    }

    return $html;
}

/**
 * แสดงผลข่าวอัพเดทแบบรายการ (สำหรับ sidebar)
 */
function render_tech_updates($news_list, $limit = 3) {
    if (empty($news_list)) {
        return '<p class="text-gray-500 text-sm">ยังไม่มีข่าวอัพเดท</p>';
    }

    $category_icons = [
        'AI' => '🤖',
        'ปัญญาประดิษฐ์' => '🤖',
        'Cloud' => '☁️',
        'Cloud Computing' => '☁️',
        'Security' => '🔐',
        'Cybersecurity' => '🔐',
        'Database' => '📊',
        'Programming' => '💻',
    ];

    $category_colors = [
        'blue' => 'text-blue-600',
        'green' => 'text-green-600',
        'red' => 'text-red-600',
        'purple' => 'text-purple-600',
        'orange' => 'text-orange-600',
        'teal' => 'text-teal-600',
    ];

    $html = '';
    $count = 0;

    foreach ($news_list as $news) {
        if ($count >= $limit) break;

        $icon = '📰';
        foreach ($category_icons as $cat => $cat_icon) {
            if (stripos($news['category'], $cat) !== false) {
                $icon = $cat_icon;
                break;
            }
        }

        $color = $category_colors[$news['category_color']] ?? 'text-gray-600';

        $html .= '
        <div class="bg-white p-3 rounded-lg shadow-sm hover:shadow-md transition-all flex items-start group cursor-pointer">
            <span class="' . $color . ' mr-2 text-lg flex-shrink-0">' . $icon . '</span>
            <div>
                <p class="text-xs md:text-sm font-medium group-hover:text-teal-600 transition-colors">
                    ' . htmlspecialchars($news['title']) . '
                </p>
                <p class="text-xs text-gray-500">' . date('d/m/Y', strtotime($news['published_date'])) . '</p>
            </div>
        </div>';

        $count++;
    }

    return $html;
}
?>
