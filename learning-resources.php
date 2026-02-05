<?php
/**
 * Learning Resources Page
 * หน้าแสดงศูนย์รวมการเรียนรู้สำหรับผู้ใช้งาน
 */

require_once 'config/database.php';

// Get filter parameters
$category_filter = $_GET['category'] ?? '';
$type_filter = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$sql = "SELECT * FROM learning_resources WHERE is_active = 1";
$params = [];
$types = "";

if (!empty($category_filter)) {
    $sql .= " AND category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

if (!empty($type_filter)) {
    $sql .= " AND resource_type = ?";
    $params[] = $type_filter;
    $types .= "s";
}

if (!empty($search)) {
    $sql .= " AND (title LIKE ? OR description LIKE ? OR tags LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$sql .= " ORDER BY is_featured DESC, display_order ASC, created_at DESC";

// Execute query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

$resources = [];
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

// Get featured resources
$featured_resources = array_filter($resources, function($r) {
    return $r['is_featured'] == 1;
});

// Get all unique categories
$categories_result = $conn->query("SELECT DISTINCT category FROM learning_resources WHERE is_active = 1 AND category != '' ORDER BY category");
$categories = [];
while ($row = $categories_result->fetch_assoc()) {
    $categories[] = $row['category'];
}

// Resource type icons and labels
$type_config = [
    'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'red', 'label' => 'PDF Document'],
    'video' => ['icon' => 'fa-video', 'color' => 'blue', 'label' => 'Video'],
    'podcast' => ['icon' => 'fa-podcast', 'color' => 'purple', 'label' => 'Podcast'],
    'blog' => ['icon' => 'fa-blog', 'color' => 'green', 'label' => 'Blog'],
    'sourcecode' => ['icon' => 'fa-code', 'color' => 'gray', 'label' => 'Source Code'],
    'youtube' => ['icon' => 'fa-youtube', 'color' => 'red', 'label' => 'YouTube'],
    'flipbook' => ['icon' => 'fa-book-open', 'color' => 'teal', 'label' => 'Flipbook']
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ศูนย์รวมการเรียนรู้ - Green Theme</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-gradient-to-r from-teal-600 to-green-600 text-white shadow-lg">
        <div class="container mx-auto px-4 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl md:text-4xl font-bold flex items-center">
                        <i class="fas fa-graduation-cap mr-3"></i>
                        ศูนย์รวมการเรียนรู้
                    </h1>
                    <p class="text-teal-100 mt-2">คู่มือ หลักสูตร บทความ และทรัพยากรการเรียนรู้ต่างๆ</p>
                </div>
                <a href="index.php" class="bg-white/20 hover:bg-white/30 text-white px-6 py-2 rounded-lg transition backdrop-blur-sm">
                    <i class="fas fa-home mr-2"></i>หน้าแรก
                </a>
            </div>
        </div>
    </header>

    <!-- Search and Filter Section -->
    <section class="bg-white shadow-md sticky top-0 z-10">
        <div class="container mx-auto px-4 py-4">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <!-- Search -->
                <div class="md:col-span-2">
                    <div class="relative">
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                               placeholder="ค้นหาทรัพยากร..."
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Category Filter -->
                <div>
                    <select name="category" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <option value="">ทุกหมวดหมู่</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category_filter == $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Type Filter -->
                <div>
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <option value="">ทุกประเภท</option>
                        <?php foreach ($type_config as $type => $config): ?>
                            <option value="<?= $type ?>" <?= $type_filter == $type ? 'selected' : '' ?>>
                                <?= $config['label'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Hidden submit on change -->
                <script>
                    document.querySelectorAll('select').forEach(select => {
                        select.addEventListener('change', function() {
                            this.form.submit();
                        });
                    });
                </script>
            </form>

            <!-- Active Filters Display -->
            <?php if ($category_filter || $type_filter || $search): ?>
                <div class="mt-3 flex items-center gap-2 flex-wrap">
                    <span class="text-sm text-gray-600">ตัวกรอง:</span>
                    <?php if ($search): ?>
                        <a href="?<?= http_build_query(array_filter(['category' => $category_filter, 'type' => $type_filter])) ?>"
                           class="inline-flex items-center bg-teal-100 text-teal-800 text-sm px-3 py-1 rounded-full">
                            ค้นหา: "<?= htmlspecialchars($search) ?>"
                            <i class="fas fa-times ml-2"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($category_filter): ?>
                        <a href="?<?= http_build_query(array_filter(['search' => $search, 'type' => $type_filter])) ?>"
                           class="inline-flex items-center bg-blue-100 text-blue-800 text-sm px-3 py-1 rounded-full">
                            <?= htmlspecialchars($category_filter) ?>
                            <i class="fas fa-times ml-2"></i>
                        </a>
                    <?php endif; ?>
                    <?php if ($type_filter): ?>
                        <a href="?<?= http_build_query(array_filter(['search' => $search, 'category' => $category_filter])) ?>"
                           class="inline-flex items-center bg-purple-100 text-purple-800 text-sm px-3 py-1 rounded-full">
                            <?= $type_config[$type_filter]['label'] ?>
                            <i class="fas fa-times ml-2"></i>
                        </a>
                    <?php endif; ?>
                    <a href="learning-resources.php" class="text-sm text-red-600 hover:text-red-800">
                        <i class="fas fa-redo mr-1"></i>ล้างตัวกรอง
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Resources -->
    <?php if (!empty($featured_resources) && !$category_filter && !$type_filter && !$search): ?>
        <section class="container mx-auto px-4 py-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-star text-yellow-500 mr-2"></i>
                ทรัพยากรแนะนำ
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach (array_slice($featured_resources, 0, 3) as $resource):
                    $type = $type_config[$resource['resource_type']] ?? $type_config['pdf'];
                ?>
                    <?php $fixed_cover = fix_asset_path($resource['cover_image'] ?? ''); ?>
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-2xl transition-all transform hover:-translate-y-1">
                        <!-- Cover Image -->
                        <div class="relative h-48 bg-gray-200 overflow-hidden">
                            <?php if ($fixed_cover): ?>
                                <img src="<?= htmlspecialchars($fixed_cover) ?>"
                                     alt="<?= htmlspecialchars($resource['title']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gradient-to-br from-teal-400 to-blue-500\'><i class=\'fas fa-image text-white text-5xl opacity-50\'></i></div>';">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-teal-400 to-blue-500">
                                    <i class="fas fa-image text-white text-5xl opacity-50"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Type Badge -->
                            <div class="absolute top-3 left-3 bg-<?= $type['color'] ?>-500 text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center space-x-1">
                                <i class="fas <?= $type['icon'] ?>"></i>
                                <span class="uppercase"><?= $resource['resource_type'] ?></span>
                            </div>

                            <!-- Featured Badge -->
                            <div class="absolute top-3 right-3 bg-yellow-400 p-2 rounded-full">
                                <i class="fas fa-star text-white"></i>
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="p-5">
                            <?php if ($resource['category']): ?>
                                <span class="inline-block bg-teal-100 text-teal-700 text-xs px-2 py-1 rounded mb-2">
                                    <?= htmlspecialchars($resource['category']) ?>
                                </span>
                            <?php endif; ?>

                            <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2">
                                <?= htmlspecialchars($resource['title']) ?>
                            </h3>

                            <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                <?= htmlspecialchars($resource['description']) ?>
                            </p>

                            <!-- Meta Info -->
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-4">
                                <?php if ($resource['author']): ?>
                                    <span><i class="fas fa-user mr-1"></i><?= htmlspecialchars($resource['author']) ?></span>
                                <?php endif; ?>
                                <?php if ($resource['duration']): ?>
                                    <span><i class="fas fa-clock mr-1"></i><?= htmlspecialchars($resource['duration']) ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-eye mr-1"></i><?= number_format($resource['view_count']) ?></span>
                            </div>

                            <!-- Action Button -->
                            <?php if ($resource['resource_url']): ?>
                                <a href="<?= htmlspecialchars($resource['resource_url']) ?>"
                                   target="_blank"
                                   onclick="incrementViewCount(<?= $resource['id'] ?>)"
                                   class="block w-full bg-gradient-to-r from-teal-600 to-green-600 hover:from-teal-700 hover:to-green-700 text-white text-center px-4 py-2 rounded-lg font-semibold transition">
                                    <i class="fas fa-external-link-alt mr-2"></i>เข้าถึงทรัพยากร
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- All Resources -->
    <section class="container mx-auto px-4 py-8">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <i class="fas fa-th-large text-teal-600 mr-2"></i>
                <?php if ($search || $category_filter || $type_filter): ?>
                    ผลการค้นหา (<?= count($resources) ?>)
                <?php else: ?>
                    ทรัพยากรทั้งหมด (<?= count($resources) ?>)
                <?php endif; ?>
            </h2>
        </div>

        <?php if (empty($resources)): ?>
            <!-- No Results -->
            <div class="bg-white rounded-xl shadow-md p-12 text-center">
                <i class="fas fa-inbox text-gray-300 text-6xl mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">ไม่พบทรัพยากร</h3>
                <p class="text-gray-500 mb-4">ไม่พบทรัพยากรที่ตรงกับเงื่อนไขการค้นหา</p>
                <a href="learning-resources.php" class="inline-block bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg transition">
                    <i class="fas fa-redo mr-2"></i>ดูทรัพยากรทั้งหมด
                </a>
            </div>
        <?php else: ?>
            <!-- Resources Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($resources as $resource):
                    $type = $type_config[$resource['resource_type']] ?? $type_config['pdf'];
                ?>
                    <?php $fixed_cover = fix_asset_path($resource['cover_image'] ?? ''); ?>
                    <div class="bg-white rounded-xl shadow-md overflow-hidden hover:shadow-xl transition-all transform hover:-translate-y-1">
                        <!-- Cover Image -->
                        <div class="relative h-40 bg-gray-200 overflow-hidden">
                            <?php if ($fixed_cover): ?>
                                <img src="<?= htmlspecialchars($fixed_cover) ?>"
                                     alt="<?= htmlspecialchars($resource['title']) ?>"
                                     class="w-full h-full object-cover"
                                     onerror="this.onerror=null; this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400\'><i class=\'fas fa-image text-gray-500 text-4xl opacity-50\'></i></div>';">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
                                    <i class="fas fa-image text-gray-500 text-4xl opacity-50"></i>
                                </div>
                            <?php endif; ?>

                            <!-- Type Badge -->
                            <div class="absolute top-2 left-2 bg-<?= $type['color'] ?>-500 text-white px-2 py-1 rounded-full text-xs font-semibold flex items-center space-x-1">
                                <i class="fas <?= $type['icon'] ?>"></i>
                                <span class="uppercase"><?= $resource['resource_type'] ?></span>
                            </div>

                            <?php if ($resource['is_featured']): ?>
                                <div class="absolute top-2 right-2 bg-yellow-400 p-1.5 rounded-full">
                                    <i class="fas fa-star text-white text-xs"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div class="p-4">
                            <?php if ($resource['category']): ?>
                                <span class="inline-block bg-teal-100 text-teal-700 text-xs px-2 py-1 rounded mb-2">
                                    <?= htmlspecialchars($resource['category']) ?>
                                </span>
                            <?php endif; ?>

                            <h3 class="font-bold text-gray-800 mb-2 line-clamp-2 text-sm">
                                <?= htmlspecialchars($resource['title']) ?>
                            </h3>

                            <p class="text-gray-600 text-xs mb-3 line-clamp-2">
                                <?= htmlspecialchars($resource['description']) ?>
                            </p>

                            <!-- Meta Info -->
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                <?php if ($resource['author']): ?>
                                    <span class="truncate"><i class="fas fa-user mr-1"></i><?= htmlspecialchars($resource['author']) ?></span>
                                <?php endif; ?>
                                <span><i class="fas fa-eye mr-1"></i><?= number_format($resource['view_count']) ?></span>
                            </div>

                            <!-- Action Button -->
                            <?php if ($resource['resource_url']): ?>
                                <a href="<?= htmlspecialchars($resource['resource_url']) ?>"
                                   target="_blank"
                                   onclick="incrementViewCount(<?= $resource['id'] ?>)"
                                   class="block w-full bg-teal-600 hover:bg-teal-700 text-white text-center px-3 py-2 rounded-lg text-sm font-semibold transition">
                                    <i class="fas fa-external-link-alt mr-1"></i>เข้าถึง
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12 py-8">
        <div class="container mx-auto px-4 text-center">
            <p class="text-gray-400">
                <i class="fas fa-graduation-cap mr-2"></i>
                ศูนย์รวมการเรียนรู้ - Green Theme
            </p>
            <p class="text-gray-500 text-sm mt-2">
                © <?= date('Y') ?> All Rights Reserved
            </p>
        </div>
    </footer>

    <script>
        // Increment view count when accessing resource
        function incrementViewCount(resourceId) {
            fetch('api/increment_view.php?id=' + resourceId + '&type=resource')
                .catch(error => console.log('View count error:', error));
        }

        // Auto-submit search form on Enter
        document.querySelector('input[name="search"]').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                this.form.submit();
            }
        });
    </script>
</body>
</html>
