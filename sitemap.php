<?php
/**
 * Dynamic Sitemap Generator
 * https://iservice.rangsitcity.go.th/sitemap.xml
 */
ob_start();

$base  = 'https://iservice.rangsitcity.go.th';
$today = date('Y-m-d');

$static_pages = [
    ['loc' => '/',                'priority' => '1.0', 'freq' => 'daily'],
    ['loc' => '/request-form.php','priority' => '0.9', 'freq' => 'monthly'],
    ['loc' => '/tracking.php',   'priority' => '0.8', 'freq' => 'weekly'],
    ['loc' => '/login.php',      'priority' => '0.5', 'freq' => 'monthly'],
];

$resources = [];

try {
    require_once __DIR__ . '/config/database.php';
    if (isset($conn) && $conn instanceof mysqli) {
        $r = $conn->query("SELECT id, updated_at FROM learning_resources WHERE is_active = 1 ORDER BY display_order ASC");
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $resources[] = $row;
            }
        }
    }
} catch (Throwable $e) {
    // DB unavailable — serve static pages only
}

ob_end_clean();

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($static_pages as $p) {
    echo "  <url>\n";
    echo "    <loc>" . $base . htmlspecialchars($p['loc']) . "</loc>\n";
    echo "    <lastmod>{$today}</lastmod>\n";
    echo "    <changefreq>{$p['freq']}</changefreq>\n";
    echo "    <priority>{$p['priority']}</priority>\n";
    echo "  </url>\n";
}

foreach ($resources as $res) {
    $lastmod = !empty($res['updated_at']) ? date('Y-m-d', strtotime($res['updated_at'])) : $today;
    echo "  <url>\n";
    echo "    <loc>{$base}/resource-detail.php?id=" . intval($res['id']) . "</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>monthly</changefreq>\n";
    echo "    <priority>0.7</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>';
