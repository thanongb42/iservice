<?php
/**
 * Service Loader
 * โหลดบริการจาก database และสร้าง HTML Service Cards
 */

// Include database config if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * Get all active services from database
 * @return array Services array
 */
function get_services() {
    global $conn;

    $services = [];
    $query = "SELECT * FROM my_service WHERE is_active = 1 ORDER BY display_order ASC";
    $result = $conn->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $services[] = $row;
        }
    }

    return $services;
}

/**
 * Get color classes for service card
 * @param string $color_code Color code from database
 * @return array Color classes
 */
function get_color_classes($color_code) {
    $color_map = [
        'blue' => [
            'bg' => 'bg-blue-50',
            'text' => 'text-blue-600',
            'hover' => 'hover:border-blue-200',
            'btn' => 'bg-blue-600 hover:bg-blue-700'
        ],
        'indigo' => [
            'bg' => 'bg-indigo-50',
            'text' => 'text-indigo-600',
            'hover' => 'hover:border-indigo-200',
            'btn' => 'bg-indigo-600 hover:bg-indigo-700'
        ],
        'red' => [
            'bg' => 'bg-red-50',
            'text' => 'text-red-600',
            'hover' => 'hover:border-red-200',
            'btn' => 'bg-red-600 hover:bg-red-700'
        ],
        'orange' => [
            'bg' => 'bg-orange-50',
            'text' => 'text-orange-600',
            'hover' => 'hover:border-orange-200',
            'btn' => 'bg-orange-600 hover:bg-orange-700'
        ],
        'purple' => [
            'bg' => 'bg-purple-50',
            'text' => 'text-purple-600',
            'hover' => 'hover:border-purple-200',
            'btn' => 'bg-purple-600 hover:bg-purple-700'
        ],
        'pink' => [
            'bg' => 'bg-pink-50',
            'text' => 'text-pink-600',
            'hover' => 'hover:border-pink-200',
            'btn' => 'bg-pink-600 hover:bg-pink-700'
        ],
        'teal' => [
            'bg' => 'bg-teal-50',
            'text' => 'text-teal-600',
            'hover' => 'hover:border-teal-200',
            'btn' => 'bg-teal-600 hover:bg-teal-700'
        ],
        'green' => [
            'bg' => 'bg-green-50',
            'text' => 'text-green-600',
            'hover' => 'hover:border-green-200',
            'btn' => 'bg-green-600 hover:bg-green-700'
        ],
        'gray' => [
            'bg' => 'bg-gray-100',
            'text' => 'text-gray-600',
            'hover' => 'hover:border-gray-300',
            'btn' => 'bg-gray-700 hover:bg-gray-800'
        ],
        'yellow' => [
            'bg' => 'bg-yellow-50',
            'text' => 'text-yellow-600',
            'hover' => 'hover:border-yellow-200',
            'btn' => 'bg-yellow-600 hover:bg-yellow-700'
        ],
    ];

    return $color_map[$color_code] ?? $color_map['blue'];
}

/**
 * Render service cards HTML
 * @param array $services Services array
 * @return string HTML output
 */
function render_service_cards($services) {
    $html = '';

    foreach ($services as $service) {
        $colors = get_color_classes($service['color_code']);

        $html .= '
        <div class="group bg-white rounded-2xl p-8 shadow-md border border-transparent ' . $colors['hover'] . ' transition-all duration-300 hover:shadow-xl hover:-translate-y-2 relative overflow-hidden">

            <!-- Background Circle Effect -->
            <div class="absolute -right-6 -top-6 w-24 h-24 rounded-full ' . $colors['bg'] . ' opacity-50 group-hover:scale-150 transition-transform duration-500"></div>

            <div class="relative z-10">
                <!-- Icon -->
                <div class="w-16 h-16 rounded-2xl ' . $colors['bg'] . ' ' . $colors['text'] . ' flex items-center justify-center text-3xl mb-6 shadow-sm">
                    <i class="' . htmlspecialchars($service['icon']) . '"></i>
                </div>

                <!-- Service Name -->
                <h4 class="text-xl font-bold text-gray-800 mb-1 group-hover:' . $colors['text'] . ' transition-colors">
                    ' . htmlspecialchars($service['service_name']) . '
                </h4>
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">
                    ' . htmlspecialchars($service['service_name_en']) . '
                </span>

                <!-- Description -->
                <p class="text-gray-600 text-sm mt-4 mb-6 leading-relaxed h-10 overflow-hidden text-ellipsis line-clamp-2">
                    ' . htmlspecialchars($service['description']) . '
                </p>

                <!-- Button -->
                <a href="request-form.php?service=' . htmlspecialchars($service['service_code']) . '" class="inline-flex items-center justify-center w-full py-3 px-4 rounded-lg text-white font-medium text-sm transition shadow-md ' . $colors['btn'] . '">
                    <i class="far fa-edit mr-2"></i> ยื่นคำขอ
                </a>
            </div>
        </div>';
    }

    // If no services
    if (empty($services)) {
        $html = '
        <div class="col-span-full text-center py-12 text-gray-500">
            <i class="fas fa-inbox text-6xl mb-4"></i>
            <p class="text-xl">ยังไม่มีบริการในระบบ</p>
            <a href="admin/my_service.php" class="inline-block mt-4 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg">
                <i class="fas fa-plus mr-2"></i>เพิ่มบริการ
            </a>
        </div>';
    }

    return $html;
}

// Auto-load services if called directly
if (basename($_SERVER['PHP_SELF']) == 'index.php') {
    $services = get_services();
    $service_cards_html = render_service_cards($services);
}
?>
