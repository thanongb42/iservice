<?php
/**
 * Admin Layout - Header
 * ส่วน <head> รวม CSS, Tailwind config, และ styles
 *
 * ตัวแปรที่ต้องกำหนดก่อน include:
 * - $page_title: ชื่อหน้า (optional, default: 'Admin Dashboard')
 */

$page_title = $page_title ?? 'Admin Dashboard';

// Fetch favicon if not already available
$favicon_path = '';
if (!isset($system_settings)) {
    // Assuming db connection is already included by parent script
    if (isset($conn)) {
        $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
        if ($settings_query) {
            while ($row = $settings_query->fetch_assoc()) {
                $settings = []; // Temp
                $settings[$row['setting_key']] = $row['setting_value'];
                
                // If found logo
                if ($row['setting_key'] == 'logo_image') {
                     $favicon_path = '../' . $row['setting_value'];
                }
            }
        }
    }
} else {
     $favicon_path = !empty($system_settings['logo_image']) ? '../' . $system_settings['logo_image'] : '';
}

// Fallback for admin pages (assuming running from admin/)
if (empty($favicon_path) || !file_exists($favicon_path)) {
     // Try to see if we can find one
     if (isset($logo_path)) {
        $favicon_path = $logo_path;
     }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - ระบบบริการดิจิทัล</title>
    <?php if (!empty($favicon_path)): ?>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($favicon_path); ?>">
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Smooth transitions */
        .sidebar-transition {
            transition: all 0.3s ease-in-out;
        }

        /* Sidebar collapse animation */
        .sidebar-collapsed {
            width: 80px;
        }

        .sidebar-expanded {
            width: 280px;
        }

        /* Main content transition */
        .main-content-transition {
            transition: margin-left 0.3s ease-in-out;
        }

        /* Mobile menu overlay */
        .mobile-overlay {
            display: none;
        }

        /* Sidebar overflow for tooltip */
        #sidebar {
            overflow: visible !important;
        }

        #sidebar nav {
            overflow-y: auto;
            overflow-x: visible;
        }

        /* When collapsed, allow tooltip to overflow */
        #sidebar.sidebar-collapsed nav {
            overflow: visible;
        }

        /* Tooltip for collapsed sidebar */
        .sidebar-tooltip {
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background-color: #0f766e;
            color: white;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .sidebar-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 6px solid transparent;
            border-right-color: #0f766e;
        }

        .sidebar-item {
            position: relative;
            overflow: visible;
        }

        /* Show tooltip only when sidebar is collapsed and on desktop */
        @media (min-width: 1024px) {
            #sidebar.sidebar-collapsed .sidebar-item {
                overflow: visible;
            }

            #sidebar.sidebar-collapsed .sidebar-item:hover .sidebar-tooltip {
                opacity: 1 !important;
                visibility: visible !important;
                display: block !important;
            }

            #sidebar.sidebar-collapsed .sidebar-tooltip {
                display: block;
            }
        }

        /* Hide tooltip when sidebar is expanded */
        #sidebar.sidebar-expanded .sidebar-tooltip {
            display: none !important;
            opacity: 0 !important;
            visibility: hidden !important;
        }

        /* Mobile and Tablet - Sidebar hidden by default */
        @media (max-width: 1023px) {
            .mobile-overlay {
                display: block;
            }
            .sidebar-mobile {
                transform: translateX(-100%);
            }
            .sidebar-mobile.active {
                transform: translateX(0);
            }
            #sidebar {
                transform: translateX(-100%);
            }
            #sidebar.active {
                transform: translateX(0);
            }
        }

        /* Menu text visibility */
        .menu-text {
            transition: opacity 0.2s ease-in-out;
        }

        #sidebar.sidebar-collapsed .menu-text {
            opacity: 0;
            width: 0;
            overflow: hidden;
        }

        /* Collapsed sidebar adjustments */
        #sidebar.sidebar-collapsed .sidebar-item {
            padding: 0.375rem;
            justify-content: center;
        }

        #sidebar.sidebar-collapsed .sidebar-item i {
            width: auto;
            font-size: 1rem;
        }

        #sidebar.sidebar-collapsed nav {
            padding: 0.375rem;
        }

        #sidebar.sidebar-collapsed nav .space-y-1 {
            gap: 0.125rem;
        }

        #sidebar.sidebar-collapsed nav .space-y-1 > * + * {
            margin-top: 0.125rem;
        }

        #sidebar.sidebar-collapsed .nav-section {
            padding-top: 0.375rem;
            margin-top: 0.375rem;
            gap: 0.125rem;
        }

        #sidebar.sidebar-collapsed .nav-section > * + * {
            margin-top: 0.125rem;
        }

        /* Collapsed header and user section */
        #sidebar.sidebar-collapsed .sidebar-header {
            padding: 0.375rem;
            justify-content: center;
        }

        #sidebar.sidebar-collapsed .sidebar-user {
            padding: 0.375rem;
            justify-content: center;
        }

        #sidebar.sidebar-collapsed .sidebar-user .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
        }

        #sidebar.sidebar-collapsed .sidebar-footer {
            padding: 0.375rem;
        }

        /* ปรับ collapse button ให้ขนาดเหมาะสม */
        .sidebar-footer #collapseBtn {
            min-height: auto;
            height: auto;
            padding: 0.375rem 0.5rem;
            border-radius: 0.375rem;
        }

        .sidebar-footer #collapseBtn i {
            font-size: 0.75rem;
            height: 14px;
            line-height: 1;
        }

        .sidebar-footer #collapseBtn span {
            font-size: 0.7rem;
            line-height: 1;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Mobile Overlay -->
    <div id="mobileOverlay" class="mobile-overlay fixed inset-0 bg-black bg-opacity-50 z-40 hidden" onclick="toggleMobileSidebar()"></div>
