<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure database connection
require_once __DIR__ . '/../config/database.php';

// Load navigation menu if not already loaded
if (!isset($nav_html)) {
    require_once __DIR__ . '/nav_menu_loader.php';
    $nav_menus = get_menu_structure();
    $nav_html = render_nav_menu($nav_menus);
}

// Fetch system settings if not already fetched
if (!isset($app_name) || !isset($org_name) || !isset($logo_path)) {
    $system_settings = [];
    if (isset($conn)) {
        $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
        if ($settings_query) {
            while ($row = $settings_query->fetch_assoc()) {
                $system_settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    $app_name = !empty($system_settings['app_name']) ? $system_settings['app_name'] : 'เทศบาลนครรังสิต';
    $org_name = !empty($system_settings['organization_name']) ? $system_settings['organization_name'] : 'เทศบาลนครรังสิต';
    $app_description = !empty($system_settings['app_description']) ? $system_settings['app_description'] : 'ระบบบริการภายใน ฝ่ายบริการและเผยแพร่วิชาการ';
    
    // Logic for logo path
    $db_logo = !empty($system_settings['logo_image']) ? $system_settings['logo_image'] : '';
    // Check if file exists relative to the project root (assuming includes/ is one level deep)
    if (!empty($db_logo) && file_exists(__DIR__ . '/../' . $db_logo)) {
        $logo_path = $db_logo;
    } else {
        // Fallback or empty if fallback doesn't exist
        $logo_path = 'images/logo/rangsit-big-logo.png';
    }
    // Check if we are in includes folder or root, adjust path if needed. 
    // Usually header is included from root files, so path should be relative to root.
    // If $logo_path comes from DB as 'images/logo/...', and we are in index.php, it works.
    // If we are in resource-detail.php, it works.
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : $org_name; ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($logo_path); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Sarabun', sans-serif;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Responsive Typography */
        h1 { font-size: clamp(1.25rem, 5vw, 1.875rem); }
        h2 { font-size: clamp(1.875rem, 6vw, 2.25rem); }
        h3 { font-size: clamp(1.5rem, 5vw, 1.875rem); }
        h4 { font-size: clamp(1.125rem, 4vw, 1.5rem); }
        p { font-size: clamp(0.875rem, 2vw, 1rem); }

        /* Mobile Menu Styles */
        #mobileMenu a {
            display: block;
            padding: 0.75rem 1rem;
            color: #374151;
            font-weight: 500;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }

        #mobileMenu a:hover {
            background-color: #f3f4f6;
            color: #0d9488;
        }

        #mobileMenu a.active {
            background-color: #ccfbf1;
            color: #0f766e;
        }

        /* Submenu in mobile */
        #mobileMenu .group {
            position: relative;
        }

        #mobileMenu .group > div {
            padding-left: 2rem;
        }

        #mobileMenu .group > div a {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
        }

        /* Sticky navbar with scroll effect */
        nav.bg-white {
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        header.bg-teal-700 {
            position: relative;
            z-index: 999;
        }

    <!-- Image Responsive -->
    <style>
        img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        <?php echo isset($extra_styles) ? $extra_styles : ''; ?>
    </style>
    <?php echo isset($extra_head_content) ? $extra_head_content : ''; ?>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-teal-700 text-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-4">
                   
                    <div>
                        <h1 class="text-lg font-bold"><?php echo htmlspecialchars($org_name); ?></h1>
                        <p class="text-xs"><?php echo htmlspecialchars($app_description); ?></p>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <form class="flex items-center">
                        <input type="search" placeholder="ค้นหา" class="px-3 py-1 rounded text-gray-800 text-sm">
                        <button type="submit" class="ml-2 text-white hover:text-yellow-300 transition-colors">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <button class="text-sm">TH | EN</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto px-4 relative">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center overflow-hidden border-2 border-teal-500 shadow-md">
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($org_name); ?>" class="w-full h-full object-contain p-1">
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-display font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($org_name); ?></h1>
                        <p class="text-sm md:text-base text-gray-600 font-medium">ฝ่ายบริการและเผยแพร่วิชาการ <span class="text-teal-700">กองยุทธศาสตร์และงบประมาณ</span></p>
                    </div>
                </div>

                <nav class="hidden lg:flex space-x-1 items-center">
                    <?php echo $nav_html; ?>
                    <a href="track.php" class="ml-2 flex items-center text-teal-700 hover:text-teal-900 font-medium px-3 py-2 rounded-md transition-colors border-b-2 border-transparent hover:border-teal-700">
                        <i class="fas fa-tracking mr-2"></i>
                        <span>ติดตามงาน</span>
                    </a>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/admin_dashboard.php" class="ml-2 bg-gradient-to-r from-green-500 to-green-600 hover:from-green-600 hover:to-green-700 text-white px-4 py-2 rounded-lg font-bold transition-all transform hover:scale-105 shadow-md flex items-center space-x-2">
                            <i class="fas fa-cogs"></i>
                            <span>Admin Panel</span>
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="ml-2 bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-lg font-bold transition-all transform hover:scale-105 shadow-md flex items-center space-x-2">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                    <a href="admin-login.php" class="ml-4 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-gray-900 px-4 py-2 rounded-lg font-bold transition-all transform hover:scale-105 shadow-md flex items-center space-x-2">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin</span>
                    </a>
                    <?php endif; ?>
                </nav>

                <!-- Hamburger Button -->
                <button id="mobileMenuBtn" class="lg:hidden text-gray-900 text-2xl focus:outline-none p-2 rounded-lg hover:bg-gray-100 transition-colors">
                    <i class="fas fa-bars"></i>
                </button>
            </div>

            <!-- Mobile Menu (Hidden by default) -->
            <nav id="mobileMenu" class="hidden lg:hidden bg-white border-t border-gray-200 py-4 absolute top-full left-0 right-0 shadow-lg px-4 z-50">
                <div class="flex flex-col space-y-2">
                    <?php echo str_replace('flex items-center text-gray-700 hover:text-teal-700 font-medium px-3 py-2 rounded-md transition-colors', 'block text-gray-700 hover:text-teal-700 font-medium px-3 py-2 rounded-md transition-colors border-b border-gray-100', $nav_html); ?>
                    
                    <a href="track.php" class="block text-teal-700 hover:text-teal-900 font-medium px-3 py-2 rounded-md transition-colors border-b border-gray-100">
                        <i class="fas fa-tracking mr-2"></i>
                        <span>ติดตามงาน</span>
                    </a>

                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <a href="admin/admin_dashboard.php" class="flex items-center space-x-3 bg-gradient-to-r from-green-500 to-green-600 text-white px-4 py-3 rounded-lg font-bold hover:from-green-600 hover:to-green-700 transition-all shadow-md mt-2">
                            <i class="fas fa-cogs text-xl"></i>
                            <span>Admin Panel</span>
                        </a>
                        <?php endif; ?>
                        <a href="logout.php" class="flex items-center space-x-3 bg-gradient-to-r from-red-500 to-red-600 text-white px-4 py-3 rounded-lg font-bold hover:from-red-600 hover:to-red-700 transition-all shadow-md mt-2">
                            <i class="fas fa-sign-out-alt text-xl"></i>
                            <span>Logout</span>
                        </a>
                    <?php else: ?>
                    <a href="admin-login.php" class="flex items-center space-x-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-gray-900 px-4 py-3 rounded-lg font-bold hover:from-yellow-600 hover:to-orange-600 transition-all shadow-md mt-2">
                        <i class="fas fa-user-shield text-xl"></i>
                        <span>Admin Login</span>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>