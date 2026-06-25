<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Language toggle ────────────────────────────────────────────────────────
if (isset($_GET['lang']) && in_array($_GET['lang'], ['th', 'en'])) {
    setcookie('site_lang', $_GET['lang'], time() + 60 * 60 * 24 * 365, '/');
    // Redirect back without the lang param
    $back = $_SERVER['REQUEST_URI'] ?? '/';
    $back = preg_replace('/([?&])lang=[^&]*/u', '$1', $back);
    $back = rtrim(preg_replace('/[?&]+$/u', '', $back), '?&');
    header('Location: ' . $back);
    exit;
}
$site_lang = (isset($_COOKIE['site_lang']) && $_COOKIE['site_lang'] === 'en') ? 'en' : 'th';
// ──────────────────────────────────────────────────────────────────────────

// Ensure database connection
require_once __DIR__ . '/../config/database.php';

// Auto logout หลังไม่ใช้งาน 30 นาที (หน้า public ที่ล็อกอินอยู่)
if (function_exists('check_session_timeout')) {
    check_session_timeout(1800, 'login.php');
}

// Simple visitor counter (daily aggregate)
if (function_exists('table_exists') && table_exists('visitor_stats')) {
    try {
        $today = date('Y-m-d');
        if (isset($conn) && $conn instanceof mysqli) {
            $stmt = $conn->prepare("
                INSERT INTO visitor_stats (visit_date, visit_count)
                VALUES (?, 1)
                ON DUPLICATE KEY UPDATE visit_count = visit_count + 1
            ");
            if ($stmt) {
                $stmt->bind_param('s', $today);
                $stmt->execute();
                $stmt->close();
            }
        }
    } catch (Throwable $e) {
        // เงียบไว้ ไม่ให้มีผลกับผู้ใช้งานหากนับสถิติล้มเหลว
    }
}

// Load navigation menu if not already loaded
if (!isset($nav_html)) {
    require_once __DIR__ . '/nav_menu_loader.php';
    $nav_menus = get_menu_structure();
    $nav_html = render_nav_menu($nav_menus, $site_lang);
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
<html lang="<?= $site_lang === 'en' ? 'en' : 'th' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php
    $seo_site_url    = 'https://iservice.rangsitcity.go.th';
    $seo_title       = isset($page_title) ? $page_title . ' | ' . $org_name : $org_name . ' — ' . $app_description;
    $seo_description = isset($meta_description) ? $meta_description : $app_description . ' ฝ่ายบริการและเผยแพร่วิชาการ กองยุทธศาสตร์และงบประมาณ เทศบาลนครรังสิต — ยื่นคำร้อง ติดตามสถานะ และรับบริการดิจิทัล';
    $seo_keywords    = isset($meta_keywords) ? $meta_keywords : 'เทศบาลนครรังสิต, เทศบาล, นครรังสิต, รังสิต, ปทุมธานี, iService, ฝ่ายบริการและเผยแพร่วิชาการ, ฝ่ายบริการ, เผยแพร่วิชาการ, กองยุทธศาสตร์และงบประมาณ, กองยุทธศาสตร์, งบประมาณ, บริการดิจิทัล, บริการออนไลน์, ยื่นคำร้อง, คำร้อง, ติดตามสถานะ, ระบบบริการออนไลน์, ระบบงานภายใน';
    $seo_image       = $seo_site_url . '/' . ltrim($logo_path, '/');
    $seo_current_url = $seo_site_url . '/' . ltrim($_SERVER['REQUEST_URI'] ?? '', '/');
    ?>
    <title><?php echo htmlspecialchars($seo_title); ?></title>

    <!-- Basic SEO -->
    <meta name="description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seo_keywords); ?>">
    <meta name="author" content="เทศบาลนครรังสิต">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($seo_current_url); ?>">
    <link rel="sitemap" type="application/xml" title="Sitemap" href="<?php echo $seo_site_url; ?>/sitemap.xml">

    <!-- Open Graph / Facebook -->
    <meta property="og:type"        content="website">
    <meta property="og:url"         content="<?php echo htmlspecialchars($seo_current_url); ?>">
    <meta property="og:site_name"   content="<?php echo htmlspecialchars($org_name); ?>">
    <meta property="og:title"       content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta property="og:image"       content="<?php echo htmlspecialchars($seo_image); ?>">
    <meta property="og:image:width"  content="512">
    <meta property="og:image:height" content="512">
    <meta property="og:locale"      content="th_TH">

    <!-- Twitter Card -->
    <meta name="twitter:card"        content="summary">
    <meta name="twitter:title"       content="<?php echo htmlspecialchars($seo_title); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seo_description); ?>">
    <meta name="twitter:image"       content="<?php echo htmlspecialchars($seo_image); ?>">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($logo_path); ?>">
    <link rel="apple-touch-icon"      href="<?php echo htmlspecialchars($logo_path); ?>">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "GovernmentOrganization",
        "name": "<?php echo addslashes($org_name); ?>",
        "description": "<?php echo addslashes($seo_description); ?>",
        "url": "<?php echo $seo_site_url; ?>",
        "logo": "<?php echo $seo_image; ?>",
        "image": "<?php echo $seo_image; ?>",
        "address": {
            "@type": "PostalAddress",
            "addressLocality": "รังสิต",
            "addressRegion": "ปทุมธานี",
            "addressCountry": "TH"
        },
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "customer service",
            "availableLanguage": "Thai"
        }
    }
    </script>
    <!-- Preconnect to speed up external resources -->
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Tailwind CSS CDN (must be synchronous — cannot defer) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Sarabun font — async non-render-blocking -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap"></noscript>

    <!-- Font Awesome loaded async (non-render-blocking) -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"></noscript>
    <style>
        /* Font Awesome — force font-display: swap to avoid invisible text */
        @font-face { font-family: 'Font Awesome 6 Free'; font-display: swap; }
        @font-face { font-family: 'Font Awesome 6 Brands'; font-display: swap; }

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

        /* Fix: sticky navbar floats to middle of page and covers content when printing */
        @media print {
            .sticky {
                position: static !important;
            }
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
                    <div class="flex items-center text-sm font-semibold bg-white/10 rounded-full overflow-hidden border border-white/30">
                        <?php
                        $cur = $_SERVER['REQUEST_URI'] ?? '/';
                        $cur_clean = preg_replace('/([?&])lang=[^&]*/u', '$1', $cur);
                        $cur_clean = rtrim(preg_replace('/[?&]+$/u', '', $cur_clean), '?&');
                        $sep = (strpos($cur_clean, '?') !== false) ? '&' : '?';
                        ?>
                        <a href="<?= htmlspecialchars($cur_clean . $sep . 'lang=th') ?>"
                           class="px-3 py-1 transition <?= $site_lang === 'th' ? 'bg-white text-teal-800' : 'text-white hover:bg-white/20' ?>">
                            TH
                        </a>
                        <a href="<?= htmlspecialchars($cur_clean . $sep . 'lang=en') ?>"
                           class="px-3 py-1 transition <?= $site_lang === 'en' ? 'bg-white text-teal-800' : 'text-white hover:bg-white/20' ?>">
                            EN
                        </a>
                    </div>
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
                        <img src="<?php echo htmlspecialchars($logo_path); ?>" alt="<?php echo htmlspecialchars($org_name); ?>" class="w-full h-full object-contain p-1" width="64" height="64" loading="eager" fetchpriority="high">
                    </div>
                    <div>
                        <h1 class="text-xl md:text-2xl font-display font-bold text-gray-900 leading-tight"><?php echo htmlspecialchars($org_name); ?></h1>
                        <p class="text-sm md:text-base text-gray-600 font-medium">
                            <?= $site_lang === 'en'
                                ? 'Service and Academic Affairs Division · <span class="text-teal-700">Strategy and Budget Division</span>'
                                : 'ฝ่ายบริการและเผยแพร่วิชาการ <span class="text-teal-700">กองยุทธศาสตร์และงบประมาณ</span>'
                            ?>
                        </p>
                    </div>
                </div>

                <nav class="hidden lg:flex space-x-1 items-center">
                    <?php echo $nav_html; ?>
                    <a href="track.php" class="ml-2 flex items-center text-teal-700 hover:text-teal-900 font-medium px-3 py-2 rounded-md transition-colors border-b-2 border-transparent hover:border-teal-700">
                        <i class="fas fa-search mr-2"></i>
                        <span><?= $site_lang === 'en' ? 'Track Request' : 'ติดตามงาน' ?></span>
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
                    <a href="login.php" class="ml-4 bg-gradient-to-r from-yellow-500 to-orange-500 hover:from-yellow-600 hover:to-orange-600 text-gray-900 px-4 py-2 rounded-lg font-bold transition-all transform hover:scale-105 shadow-md flex items-center space-x-2">
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
                    <?php
                    $nav_html_mobile = render_nav_menu($nav_menus, $site_lang);
                    echo str_replace('flex items-center text-gray-700 hover:text-teal-700 font-medium px-3 py-2 rounded-md transition-colors', 'block text-gray-700 hover:text-teal-700 font-medium px-3 py-2 rounded-md transition-colors border-b border-gray-100', $nav_html_mobile);
                    ?>
                    
                    <a href="track.php" class="block text-teal-700 hover:text-teal-900 font-medium px-3 py-2 rounded-md transition-colors border-b border-gray-100">
                        <i class="fas fa-search mr-2"></i>
                        <span><?= $site_lang === 'en' ? 'Track Request' : 'ติดตามงาน' ?></span>
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
                    <a href="login.php" class="flex items-center space-x-3 bg-gradient-to-r from-yellow-500 to-orange-500 text-gray-900 px-4 py-3 rounded-lg font-bold hover:from-yellow-600 hover:to-orange-600 transition-all shadow-md mt-2">
                        <i class="fas fa-user-shield text-xl"></i>
                        <span>Admin Login</span>
                    </a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>