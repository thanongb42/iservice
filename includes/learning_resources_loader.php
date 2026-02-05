<?php
/**
 * Learning Resources Loader
 * ====================================
 * โหลดทรัพยากรการเรียนรู้จากฐานข้อมูลและแสดงผลในรูปแบบ Cards
 * รองรับ PDF, Video, Podcast, Blog, Source Code, YouTube, Flipbook
 */

require_once __DIR__ . '/../config/database.php';

/**
 * ดึงทรัพยากรการเรียนรู้ทั้งหมดที่เปิดใช้งาน
 *
 * @param int|null $limit จำนวนรายการที่ต้องการดึง (null = ทั้งหมด)
 * @param bool $featured_only แสดงเฉพาะรายการแนะนำ (true/false)
 * @return array
 */
function get_learning_resources($limit = null, $featured_only = false) {
    global $conn;

    $sql = "SELECT * FROM learning_resources WHERE is_active = 1";

    if ($featured_only) {
        $sql .= " AND is_featured = 1";
    }

    $sql .= " ORDER BY display_order ASC, created_at DESC";

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
 * ดึงรายละเอียดทรัพยากรเดี่ยว
 *
 * @param int $id
 * @return array|null
 */
function get_learning_resource($id) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM learning_resources WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * เพิ่มจำนวนการเข้าชม
 *
 * @param int $id
 */
function increment_view_count($id) {
    global $conn;

    $stmt = $conn->prepare("UPDATE learning_resources SET view_count = view_count + 1 WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

/**
 * ดึง icon และสี badge ตามประเภททรัพยากร
 *
 * @param string $type
 * @return array
 */
function get_resource_type_config($type) {
    $configs = [
        'pdf' => [
            'icon' => 'fas fa-file-pdf',
            'bg' => 'bg-red-100',
            'text' => 'text-red-600',
            'label' => 'PDF'
        ],
        'video' => [
            'icon' => 'fas fa-video',
            'bg' => 'bg-purple-100',
            'text' => 'text-purple-600',
            'label' => 'Video'
        ],
        'podcast' => [
            'icon' => 'fas fa-podcast',
            'bg' => 'bg-green-100',
            'text' => 'text-green-600',
            'label' => 'Podcast'
        ],
        'blog' => [
            'icon' => 'fas fa-blog',
            'bg' => 'bg-blue-100',
            'text' => 'text-blue-600',
            'label' => 'Blog'
        ],
        'sourcecode' => [
            'icon' => 'fas fa-code',
            'bg' => 'bg-gray-100',
            'text' => 'text-gray-600',
            'label' => 'Source Code'
        ],
        'youtube' => [
            'icon' => 'fab fa-youtube',
            'bg' => 'bg-red-100',
            'text' => 'text-red-600',
            'label' => 'YouTube'
        ],
        'flipbook' => [
            'icon' => 'fas fa-book-open',
            'bg' => 'bg-indigo-100',
            'text' => 'text-indigo-600',
            'label' => 'Flipbook'
        ]
    ];

    return $configs[$type] ?? [
        'icon' => 'fas fa-file',
        'bg' => 'bg-gray-100',
        'text' => 'text-gray-600',
        'label' => 'Other'
    ];
}

/**
 * แสดงผล Learning Resources Cards
 *
 * @param array $resources
 * @return string HTML
 */
function render_learning_resources($resources) {
    if (empty($resources)) {
        return '<div class="col-span-full text-center py-12">
                    <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                    <p class="text-gray-500">ยังไม่มีทรัพยากรการเรียนรู้</p>
                </div>';
    }

    $html = '';

    foreach ($resources as $resource) {
        $type_config = get_resource_type_config($resource['resource_type']);

        // Fix image path - ensure correct public/ prefix
        $cover_image = $resource['cover_image'] ?: '';
        if (!empty($cover_image)) {
            $cover_image = fix_asset_path($cover_image);
        } else {
            // Use SVG data URI for placeholder (no external dependency)
            $cover_image = 'data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'300\'%3E%3Crect fill=\'%23E5E7EB\' width=\'400\' height=\'300\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'18\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E';
        }

        // สร้าง URL สำหรับดูรายละเอียด
        $detail_url = 'resource-detail.php?id=' . $resource['id'];

        // แสดง badge "แนะนำ" ถ้าเป็นรายการแนะนำ
        $featured_badge = '';
        if ($resource['is_featured'] == 1) {
            $featured_badge = '<span class="absolute top-2 right-2 bg-yellow-400 text-yellow-900 text-xs font-bold px-2 py-1 rounded-full flex items-center gap-1 shadow-lg">
                                    <i class="fas fa-star"></i> แนะนำ
                                </span>';
        }

        // แสดง Duration หรือ File Size
        $meta_info = '';
        if (!empty($resource['duration'])) {
            $meta_info = '<span class="flex items-center gap-1 text-gray-600">
                            <i class="far fa-clock"></i> ' . htmlspecialchars($resource['duration']) . '
                          </span>';
        } elseif (!empty($resource['file_size'])) {
            $meta_info = '<span class="flex items-center gap-1 text-gray-600">
                            <i class="far fa-file"></i> ' . htmlspecialchars($resource['file_size']) . '
                          </span>';
        }

        // แสดงจำนวนการเข้าชม
        $view_count = '<span class="flex items-center gap-1 text-gray-600">
                        <i class="far fa-eye"></i> ' . number_format($resource['view_count']) . '
                       </span>';

        $html .= '
        <div class="group bg-white rounded-xl shadow-md hover:shadow-2xl transition-all duration-300 overflow-hidden transform hover:-translate-y-2">
            <!-- Cover Image -->
            <div class="relative h-48 overflow-hidden">
                <img src="' . htmlspecialchars($cover_image) . '"
                     alt="' . htmlspecialchars($resource['title']) . '"
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300"
                     onerror="this.src=\'data:image/svg+xml,%3Csvg xmlns=\\\'http://www.w3.org/2000/svg\\\' width=\\\'400\\\' height=\\\'300\\\'%3E%3Crect fill=\\\'%23E5E7EB\\\' width=\\\'400\\\' height=\\\'300\\\'/%3E%3Ctext fill=\\\'%236B7280\\\' font-family=\\\'Arial,sans-serif\\\' font-size=\\\'18\\\' x=\\\'50%25\\\' y=\\\'50%25\\\' text-anchor=\\\'middle\\\' dominant-baseline=\\\'middle\\\'%3ENo Image%3C/text%3E%3C/svg%3E\'; this.onerror=null;">

                <!-- Featured Badge -->
                ' . $featured_badge . '

                <!-- Resource Type Badge -->
                <span class="absolute bottom-2 left-2 ' . $type_config['bg'] . ' ' . $type_config['text'] . ' text-xs font-semibold px-3 py-1 rounded-full flex items-center gap-1 shadow">
                    <i class="' . $type_config['icon'] . '"></i>
                    ' . $type_config['label'] . '
                </span>
            </div>

            <!-- Content -->
            <div class="p-5">
                <!-- Category -->
                ' . (!empty($resource['category']) ?
                '<span class="text-teal-600 text-xs font-semibold uppercase tracking-wider">' .
                htmlspecialchars($resource['category']) . '</span>' : '') . '

                <!-- Title -->
                <h3 class="mt-2 text-lg font-bold text-gray-900 line-clamp-2 min-h-[3.5rem]">
                    ' . htmlspecialchars($resource['title']) . '
                </h3>

                <!-- Description -->
                <p class="mt-2 text-gray-600 text-sm line-clamp-3 min-h-[4.5rem]">
                    ' . htmlspecialchars($resource['description']) . '
                </p>

                <!-- Author -->
                ' . (!empty($resource['author']) ?
                '<p class="mt-3 text-gray-500 text-xs flex items-center gap-1">
                    <i class="fas fa-user-circle"></i> ' . htmlspecialchars($resource['author']) . '
                </p>' : '') . '

                <!-- Meta Info -->
                <div class="mt-4 flex items-center justify-between text-xs border-t pt-3">
                    <div class="flex items-center gap-3">
                        ' . $meta_info . '
                        ' . $view_count . '
                    </div>
                </div>

                <!-- View Button -->
                <a href="' . $detail_url . '"
                   class="mt-4 block w-full text-center bg-gradient-to-r from-teal-600 to-blue-600 hover:from-teal-700 hover:to-blue-700 text-white font-semibold py-2.5 px-4 rounded-lg transition-all duration-300 transform hover:scale-105">
                    <i class="fas fa-arrow-right mr-2"></i>ดูเพิ่มเติม
                </a>
            </div>
        </div>';
    }

    return $html;
}

/**
 * แสดง Section หัวข้อศูนย์การเรียนรู้
 *
 * @return string HTML
 */
function render_learning_center_header() {
    return '
    <div class="text-center mb-12">
        <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
            <i class="fas fa-graduation-cap text-teal-600"></i>
            ศูนย์รวมการเรียนรู้
        </h2>
        <p class="text-gray-600 text-lg max-w-2xl mx-auto">
            คู่มือการใช้งาน หลักสูตรฟรี บทความ และแหล่งความรู้ต่างๆ เพื่อพัฒนาทักษะด้าน IT
        </p>
    </div>';
}
?>
