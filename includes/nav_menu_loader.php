<?php
/**
 * Nav Menu Loader
 * โหลดเมนูจาก database และสร้าง HTML
 */

// Include database config if not already included
if (!isset($conn)) {
    require_once __DIR__ . '/../config/database.php';
}

/**
 * Get menu structure from database
 * @return array Hierarchical menu array
 */
function get_menu_structure() {
    global $conn;

    $menus = [];

    // Get parent menus
    $parent_query = "SELECT * FROM nav_menu WHERE parent_id IS NULL AND is_active = 1 ORDER BY menu_order ASC";
    $parent_result = $conn->query($parent_query);

    if ($parent_result) {
        while ($parent = $parent_result->fetch_assoc()) {
            $parent['children'] = [];

            // Get child menus
            $child_query = "SELECT * FROM nav_menu WHERE parent_id = {$parent['id']} AND is_active = 1 ORDER BY menu_order ASC";
            $child_result = $conn->query($child_query);

            if ($child_result) {
                while ($child = $child_result->fetch_assoc()) {
                    $parent['children'][] = $child;
                }
            }

            $menus[] = $parent;
        }
    }

    return $menus;
}

/**
 * Render navigation menu HTML
 * @param array $menus Menu structure
 * @return string HTML output
 */
function render_nav_menu($menus) {
    $html = '';

    foreach ($menus as $menu) {
        $has_children = !empty($menu['children']);

        if ($has_children) {
            // Parent menu with dropdown
            $html .= '<div class="relative group">';
            $html .= '<button class="px-4 py-2 text-gray-900 font-semibold hover:bg-teal-50 rounded-lg transition flex items-center">';
            $html .= htmlspecialchars($menu['menu_name']);
            $html .= ' <i class="fas fa-chevron-down ml-2 text-xs text-gray-400 group-hover:text-teal-700"></i>';
            $html .= '</button>';

            // Dropdown submenu
            $html .= '<div class="absolute top-full right-0 w-64 bg-white shadow-xl rounded-xl mt-2 py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-300 transform origin-top-right border border-gray-100">';

            foreach ($menu['children'] as $index => $child) {
                $border_class = ($index < count($menu['children']) - 1) ? 'border-b border-gray-50' : '';
                $icon = $child['menu_icon'] ? '<i class="' . htmlspecialchars($child['menu_icon']) . ' w-6 text-center mr-2 text-gray-400"></i>' : '';

                $html .= '<a href="' . htmlspecialchars($child['menu_url']) . '" ';
                $html .= 'target="' . htmlspecialchars($child['target']) . '" ';
                $html .= 'class="block px-6 py-3 text-gray-700 hover:bg-teal-50 hover:text-teal-700 ' . $border_class . '">';
                $html .= $icon . htmlspecialchars($child['menu_name']);
                $html .= '</a>';
            }

            $html .= '</div>';
            $html .= '</div>';

        } else {
            // Simple link (no children)
            $html .= '<a href="' . htmlspecialchars($menu['menu_url']) . '" ';
            $html .= 'target="' . htmlspecialchars($menu['target']) . '" ';
            $html .= 'class="px-4 py-2 text-gray-900 font-semibold hover:bg-teal-50 rounded-lg transition">';
            $html .= htmlspecialchars($menu['menu_name']);
            $html .= '</a>';
        }
    }

    return $html;
}

// Get menus and render (if called directly)
if (basename($_SERVER['PHP_SELF']) != 'index.php') {
    // This is being included, so just prepare the data
    $nav_menus = get_menu_structure();
    $nav_html = render_nav_menu($nav_menus);
}
?>
