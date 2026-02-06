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
        $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key = 'logo_image' LIMIT 1");
        if ($settings_query) {
            $logo_row = $settings_query->fetch_assoc();
            if ($logo_row && !empty($logo_row['setting_value'])) {
                $favicon_path = '../' . $logo_row['setting_value'];
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
                            50: '#e6fff0',
                            100: '#ccffe0',
                            200: '#99ffc2',
                            300: '#66ffa3',
                            400: '#33cc70',
                            500: '#009933',
                            600: '#007a29',
                            700: '#006622',
                            800: '#00521b',
                            900: '#003d14',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Green Button Styles */
        .btn-primary {
            background-color: #009933 !important;
            border-color: #009933 !important;
            color: white !important;
        }
        .btn-primary:hover {
            background-color: #007a29 !important;
            border-color: #007a29 !important;
        }
        .bg-primary-500, .bg-teal-500, .bg-teal-600 {
            background-color: #009933 !important;
        }
        .bg-primary-600, .bg-teal-700 {
            background-color: #007a29 !important;
        }
        .hover\:bg-primary-600:hover, .hover\:bg-teal-600:hover, .hover\:bg-teal-700:hover {
            background-color: #007a29 !important;
        }
        .text-primary-500, .text-primary-600, .text-teal-500, .text-teal-600 {
            color: #009933 !important;
        }
        .border-primary-500, .border-teal-500, .border-teal-600 {
            border-color: #009933 !important;
        }
        .ring-primary-500, .ring-teal-500 {
            --tw-ring-color: #009933 !important;
        }
        .focus\:ring-primary-500:focus, .focus\:ring-teal-500:focus {
            --tw-ring-color: #009933 !important;
        }

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

        /* Main content margin when sidebar expanded */
        #mainContent.lg\:ml-\[280px\] {
            margin-left: 280px !important;
        }

        /* Main content margin when sidebar collapsed */
        #mainContent.lg\:ml-\[80px\] {
            margin-left: 80px !important;
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

        /* Tooltip for collapsed sidebar - Clean Style */
        .sidebar-tooltip {
            position: absolute;
            left: calc(100% + 12px);
            top: 50%;
            transform: translateY(-50%);
            background-color: #1f2937;
            color: white;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 500;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.15s ease, visibility 0.15s ease;
            z-index: 9999;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .sidebar-tooltip::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border: 5px solid transparent;
            border-right-color: #1f2937;
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
