<?php
session_start();
/**
 * Learning Resource Detail Page
 * แสดงรายละเอียดทรัพยากรการเรียนรู้แต่ละรายการ
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/learning_resources_loader.php';
require_once __DIR__ . '/includes/nav_menu_loader.php';

// Get resource ID from URL
$resource_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($resource_id <= 0) {
    header('Location: index.php');
    exit;
}

// Get resource details
$resource = get_learning_resource($resource_id);

if (!$resource) {
    header('Location: index.php');
    exit;
}

// Increment view count
increment_view_count($resource_id);

// Get related resources (same category, limit 3)
$related_sql = "SELECT * FROM learning_resources
                WHERE is_active = 1
                AND id != ?
                AND category = ?
                ORDER BY RAND()
                LIMIT 3";
$stmt = $conn->prepare($related_sql);
$stmt->bind_param("is", $resource_id, $resource['category']);
$stmt->execute();
$related_result = $stmt->get_result();
$related_resources = $related_result->fetch_all(MYSQLI_ASSOC);

// Fetch system settings
$app_name = 'เทศบาลนครรังสิต';
$org_name = 'เทศบาลนครรังสิต';
$app_description = 'ระบบบริการภายใน ฝ่ายบริการและเผยแพร่วิชาการ';
$logo_path = 'images/logo/rangsit-big-logo.png';

$system_settings = [];
if (isset($conn)) {
    $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($settings_query) {
        while ($row = $settings_query->fetch_assoc()) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

if (!empty($system_settings['app_name'])) $app_name = $system_settings['app_name'];
if (!empty($system_settings['organization_name'])) $org_name = $system_settings['organization_name'];
if (!empty($system_settings['app_description'])) $app_description = $system_settings['app_description'];
if (!empty($system_settings['logo_image']) && file_exists($system_settings['logo_image'])) $logo_path = $system_settings['logo_image'];

// Load navigation menu
$nav_menus = get_menu_structure();
$nav_html = render_nav_menu($nav_menus);

// Get resource type configuration
$type_config = get_resource_type_config($resource['resource_type']);

$page_title = htmlspecialchars($resource['title']) . " - ศูนย์รวมการเรียนรู้";
$extra_styles = "
        .content-wrapper {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* Flipbook Styles */
        .flipbook-wrapper {
            perspective: 2000px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 40px 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        #flipbook {
            margin: 0 auto;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
        }

        #flipbook .page {
            background: white;
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
        }

        #flipbook .page img {
            max-width: 100%;
            max-height: 100%;
            display: block;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255,255,255,0.95);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .spinner {
            border: 5px solid #f3f4f6;
            border-top: 5px solid #0d9488;
            border-radius: 50%;
            width: 60px;
            height: 60px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .flipbook-controls {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }

        .page-shadow {
            box-shadow:
                inset 0 0 30px rgba(0,0,0,0.1),
                0 0 20px rgba(0,0,0,0.3);
        }

        #pdfCanvas {
            max-width: 100%;
            height: auto;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
";

// Add specific scripts for Turn.js and PDF.js to head using extra_styles workaround or add before header
// Since header_public includes <head>, we need to inject scripts there or use $extra_head variable if I added it.
// I added extra_styles in header which is inside <style> tag.
// I need to add script tags also. I should update header_public.php to support extra_head_content.

// Let's assume I can put scripts in extra_styles if I close style tag? No that's messy.
// I should update header_public.php first to allow extra content in head.
?>
<?php
// Define extra head content
$extra_head_content = "
    <!-- jQuery (required for Turn.js) -->
    <script src='https://code.jquery.com/jquery-3.6.0.min.js'></script>

    <!-- PDF.js Library -->
    <script src='https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js'></script>

    <!-- Turn.js for Flipbook Effect -->
    <script src='https://cdnjs.cloudflare.com/ajax/libs/turn.js/3/turn.min.js'></script>
";

include __DIR__ . '/includes/header_public.php';
?>
    <!-- Breadcrumb -->
    <section class="bg-white border-b py-4">
        <div class="container mx-auto px-4">
            <nav class="flex items-center space-x-2 text-sm text-gray-600">
                <a href="index.php" class="hover:text-teal-600 transition">
                    <i class="fas fa-home"></i> หน้าแรก
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <a href="index.php#learning" class="hover:text-teal-600 transition">
                    ศูนย์รวมการเรียนรู้
                </a>
                <i class="fas fa-chevron-right text-xs"></i>
                <span class="text-gray-900 font-medium"><?= htmlspecialchars($resource['title']) ?></span>
            </nav>
        </div>
    </section>

    <!-- Main Content -->
    <section class="py-12">
        <div class="container mx-auto px-4">
            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Main Content Area -->
                <div class="lg:col-span-2">
                    <!-- Resource Header -->
                    <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                        <!-- Cover Image -->
                        <div class="relative h-96">
                            <img src="<?= htmlspecialchars($resource['cover_image']) ?>"
                                 alt="<?= htmlspecialchars($resource['title']) ?>"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'1200\' height=\'600\'%3E%3Crect fill=\'%23E5E7EB\' width=\'1200\' height=\'600\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'24\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ELearning Resource%3C/text%3E%3C/svg%3E'; this.onerror=null;">

                            <!-- Type Badge -->
                            <div class="absolute top-4 left-4">
                                <span class="<?= $type_config['bg'] ?> <?= $type_config['text'] ?> px-4 py-2 rounded-full font-semibold text-sm flex items-center gap-2 shadow-lg">
                                    <i class="<?= $type_config['icon'] ?>"></i>
                                    <?= $type_config['label'] ?>
                                </span>
                            </div>

                            <?php if ($resource['is_featured']): ?>
                            <div class="absolute top-4 right-4">
                                <span class="bg-yellow-400 text-yellow-900 px-4 py-2 rounded-full font-bold text-sm flex items-center gap-2 shadow-lg">
                                    <i class="fas fa-star"></i> แนะนำ
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Content -->
                        <div class="p-8">
                            <!-- Category -->
                            <?php if (!empty($resource['category'])): ?>
                            <span class="inline-block bg-teal-100 text-teal-700 px-3 py-1 rounded-full text-sm font-semibold mb-4">
                                <?= htmlspecialchars($resource['category']) ?>
                            </span>
                            <?php endif; ?>

                            <!-- Title -->
                            <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                                <?= htmlspecialchars($resource['title']) ?>
                            </h1>

                            <!-- Meta Information -->
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-600 mb-6 pb-6 border-b">
                                <?php if (!empty($resource['author'])): ?>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-user-circle text-teal-600"></i>
                                    <span><?= htmlspecialchars($resource['author']) ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($resource['duration'])): ?>
                                <div class="flex items-center gap-2">
                                    <i class="far fa-clock text-teal-600"></i>
                                    <span><?= htmlspecialchars($resource['duration']) ?></span>
                                </div>
                                <?php endif; ?>

                                <?php if (!empty($resource['file_size'])): ?>
                                <div class="flex items-center gap-2">
                                    <i class="far fa-file text-teal-600"></i>
                                    <span><?= htmlspecialchars($resource['file_size']) ?></span>
                                </div>
                                <?php endif; ?>

                                <div class="flex items-center gap-2">
                                    <i class="far fa-eye text-teal-600"></i>
                                    <span><?= number_format($resource['view_count']) ?> ครั้ง</span>
                                </div>

                                <div class="flex items-center gap-2">
                                    <i class="far fa-calendar text-teal-600"></i>
                                    <span><?= date('d/m/Y', strtotime($resource['created_at'])) ?></span>
                                </div>
                            </div>

                            <!-- Description -->
                            <div class="prose max-w-none mb-8">
                                <p class="text-gray-700 text-lg leading-relaxed">
                                    <?= nl2br(htmlspecialchars($resource['description'])) ?>
                                </p>
                            </div>

                            <!-- Resource Content Area -->
                            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                                <?php
                                $resource_url = htmlspecialchars($resource['resource_url']);

                                switch ($resource['resource_type']) {
                                    case 'pdf':
                                        $pdf_path = str_replace('../', '', $resource_url);
                                        echo '
                                        <div id="pdfFlipbookViewer">
                                            <!-- Flipbook Controls -->
                                            <div class="flipbook-controls">
                                                <div class="flex flex-wrap items-center justify-between gap-4">
                                                    <div class="flex items-center gap-3">
                                                        <button id="firstPage" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg transition-all shadow hover:shadow-lg" title="หน้าแรก">
                                                            <i class="fas fa-fast-backward"></i>
                                                        </button>
                                                        <button id="prevPage" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg transition-all shadow hover:shadow-lg" title="ก่อนหน้า">
                                                            <i class="fas fa-chevron-left mr-2"></i>ก่อนหน้า
                                                        </button>
                                                        <div class="bg-gray-100 px-4 py-2 rounded-lg">
                                                            <span class="text-gray-700 font-semibold">
                                                                หน้า <span id="currentPage">1</span> / <span id="totalPages">-</span>
                                                            </span>
                                                        </div>
                                                        <button id="nextPage" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg transition-all shadow hover:shadow-lg" title="ถัดไป">
                                                            ถัดไป<i class="fas fa-chevron-right ml-2"></i>
                                                        </button>
                                                        <button id="lastPage" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg transition-all shadow hover:shadow-lg" title="หน้าสุดท้าย">
                                                            <i class="fas fa-fast-forward"></i>
                                                        </button>
                                                    </div>

                                                    <div class="flex items-center gap-3">
                                                        <button id="zoomOut" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg transition" title="ซูมออก">
                                                            <i class="fas fa-search-minus"></i>
                                                        </button>
                                                        <span class="text-gray-700 font-semibold min-w-[60px] text-center" id="zoomLevel">100%</span>
                                                        <button id="zoomIn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg transition" title="ซูมเข้า">
                                                            <i class="fas fa-search-plus"></i>
                                                        </button>
                                                        <button id="fullscreen" class="bg-gray-200 hover:bg-gray-300 text-gray-700 px-3 py-2 rounded-lg transition" title="เต็มจอ">
                                                            <i class="fas fa-expand"></i>
                                                        </button>
                                                        <a href="' . htmlspecialchars($resource_url) . '" download class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition inline-flex items-center" title="ดาวน์โหลด">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Loading Overlay -->
                                            <div id="loadingPDF" class="relative bg-gray-100 rounded-lg" style="min-height: 500px;">
                                                <div class="loading-overlay">
                                                    <div>
                                                        <div class="spinner mx-auto mb-4"></div>
                                                        <p class="text-gray-600 font-semibold">กำลังโหลด PDF...</p>
                                                        <p class="text-gray-500 text-sm mt-2">กรุณารอสักครู่</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Flipbook Container -->
                                            <div class="flipbook-wrapper" id="flipbookWrapper" style="display: none;">
                                                <div id="flipbook"></div>
                                            </div>
                                        </div>

                                        <script>
                                            const pdfUrl = "' . htmlspecialchars($pdf_path) . '";
                                            let pdfDoc = null;
                                            let currentPageNum = 1;
                                            let scale = 1.2;
                                            let pageWidth = 500;
                                            let pageHeight = 700;
                                            let totalPagesCount = 0;

                                            // Configure PDF.js worker
                                            pdfjsLib.GlobalWorkerOptions.workerSrc = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";

                                            // Load and render PDF as Flipbook
                                            async function loadPDFFlipbook() {
                                                try {
                                                    console.log("Loading PDF:", pdfUrl);
                                                    pdfDoc = await pdfjsLib.getDocument(pdfUrl).promise;
                                                    totalPagesCount = pdfDoc.numPages;
                                                    document.getElementById("totalPages").textContent = totalPagesCount;

                                                    await createFlipbook();

                                                    document.getElementById("loadingPDF").style.display = "none";
                                                    document.getElementById("flipbookWrapper").style.display = "block";
                                                } catch (error) {
                                                    console.error("Error loading PDF:", error);
                                                    document.getElementById("loadingPDF").innerHTML =
                                                        `<div class="text-center py-12">
                                                            <i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-4"></i>
                                                            <p class="text-red-600 text-lg font-semibold">ไม่สามารถโหลด PDF ได้</p>
                                                            <p class="text-gray-600 mt-2">กรุณาลองใหม่อีกครั้ง หรือดาวน์โหลดไฟล์</p>
                                                            <a href="' . htmlspecialchars($resource_url) . '" download
                                                               class="inline-block mt-4 bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-lg transition">
                                                                <i class="fas fa-download mr-2"></i>ดาวน์โหลด PDF
                                                            </a>
                                                        </div>`;
                                                }
                                            }

                                            async function createFlipbook() {
                                                const flipbook = document.getElementById("flipbook");
                                                flipbook.innerHTML = "";

                                                // Create canvas pages from PDF
                                                for (let pageNum = 1; pageNum <= totalPagesCount; pageNum++) {
                                                    const page = await pdfDoc.getPage(pageNum);
                                                    const viewport = page.getViewport({ scale: scale });

                                                    const canvas = document.createElement("canvas");
                                                    const context = canvas.getContext("2d");
                                                    canvas.width = viewport.width;
                                                    canvas.height = viewport.height;

                                                    await page.render({
                                                        canvasContext: context,
                                                        viewport: viewport
                                                    }).promise;

                                                    const pageDiv = document.createElement("div");
                                                    pageDiv.className = "page page-shadow";
                                                    pageDiv.appendChild(canvas);
                                                    flipbook.appendChild(pageDiv);
                                                }

                                                // Calculate dimensions
                                                const firstCanvas = flipbook.querySelector("canvas");
                                                if (firstCanvas) {
                                                    pageWidth = firstCanvas.width;
                                                    pageHeight = firstCanvas.height;
                                                }

                                                // Initialize Turn.js flipbook
                                                $("#flipbook").turn({
                                                    width: pageWidth * 2,
                                                    height: pageHeight,
                                                    autoCenter: true,
                                                    duration: 1000,
                                                    gradients: true,
                                                    elevation: 50,
                                                    acceleration: true,
                                                    when: {
                                                        turned: function(event, page, view) {
                                                            currentPageNum = page;
                                                            document.getElementById("currentPage").textContent = page;
                                                        }
                                                    }
                                                });

                                                setupFlipbookControls();
                                            }

                                            function setupFlipbookControls() {
                                                // Navigation
                                                document.getElementById("firstPage").addEventListener("click", () => {
                                                    $("#flipbook").turn("page", 1);
                                                });

                                                document.getElementById("prevPage").addEventListener("click", () => {
                                                    $("#flipbook").turn("previous");
                                                });

                                                document.getElementById("nextPage").addEventListener("click", () => {
                                                    $("#flipbook").turn("next");
                                                });

                                                document.getElementById("lastPage").addEventListener("click", () => {
                                                    $("#flipbook").turn("page", totalPagesCount);
                                                });

                                                // Zoom
                                                document.getElementById("zoomIn").addEventListener("click", () => {
                                                    if (scale < 3) {
                                                        scale += 0.2;
                                                        updateZoom();
                                                    }
                                                });

                                                document.getElementById("zoomOut").addEventListener("click", () => {
                                                    if (scale > 0.5) {
                                                        scale -= 0.2;
                                                        updateZoom();
                                                    }
                                                });

                                                // Fullscreen
                                                document.getElementById("fullscreen").addEventListener("click", () => {
                                                    const wrapper = document.getElementById("flipbookWrapper");
                                                    if (wrapper.requestFullscreen) {
                                                        wrapper.requestFullscreen();
                                                    } else if (wrapper.webkitRequestFullscreen) {
                                                        wrapper.webkitRequestFullscreen();
                                                    } else if (wrapper.msRequestFullscreen) {
                                                        wrapper.msRequestFullscreen();
                                                    }
                                                });

                                                // Keyboard navigation
                                                document.addEventListener("keydown", (e) => {
                                                    if (e.key === "ArrowLeft") $("#flipbook").turn("previous");
                                                    if (e.key === "ArrowRight") $("#flipbook").turn("next");
                                                });
                                            }

                                            function updateZoom() {
                                                document.getElementById("zoomLevel").textContent = Math.round(scale * 100) + "%";
                                                $("#flipbook").turn("destroy");
                                                document.getElementById("loadingPDF").style.display = "block";
                                                document.getElementById("flipbookWrapper").style.display = "none";
                                                setTimeout(() => createFlipbook(), 300);
                                            }

                                            // Load PDF on page load
                                            window.addEventListener("DOMContentLoaded", loadPDFFlipbook);
                                        </script>
                                        ';
                                        break;

                                    case 'video':
                                        echo '<div class="aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                                <video controls class="w-full h-full">
                                                    <source src="' . $resource_url . '" type="video/mp4">
                                                    เบราว์เซอร์ของคุณไม่รองรับการเล่นวิดีโอ
                                                </video>
                                              </div>';
                                        break;

                                    case 'youtube':
                                        // Extract YouTube video ID
                                        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/', $resource_url, $matches);
                                        $video_id = $matches[1] ?? '';

                                        if ($video_id) {
                                            echo '<div class="aspect-video bg-gray-900 rounded-lg overflow-hidden">
                                                    <iframe width="100%" height="100%"
                                                            src="https://www.youtube.com/embed/' . $video_id . '"
                                                            frameborder="0"
                                                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                                            allowfullscreen>
                                                    </iframe>
                                                  </div>';
                                        } else {
                                            echo '<div class="text-center">
                                                    <a href="' . $resource_url . '" target="_blank"
                                                       class="inline-flex items-center bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg font-semibold transition">
                                                        <i class="fab fa-youtube mr-2"></i> ดูบน YouTube
                                                    </a>
                                                  </div>';
                                        }
                                        break;

                                    case 'podcast':
                                        echo '<div class="text-center">
                                                <i class="fas fa-podcast text-green-500 text-6xl mb-4"></i>
                                                <h3 class="text-xl font-bold mb-4">Podcast</h3>
                                                <audio controls class="w-full mb-4">
                                                    <source src="' . $resource_url . '" type="audio/mpeg">
                                                    เบราว์เซอร์ของคุณไม่รองรับการเล่นเสียง
                                                </audio>
                                              </div>';
                                        break;

                                    case 'blog':
                                        echo '<div class="text-center">
                                                <i class="fas fa-blog text-blue-500 text-6xl mb-4"></i>
                                                <h3 class="text-xl font-bold mb-4">บทความ Blog</h3>
                                                <a href="' . $resource_url . '"
                                                   class="inline-flex items-center bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded-lg font-semibold transition">
                                                    <i class="fas fa-arrow-right mr-2"></i> อ่านบทความ
                                                </a>
                                              </div>';
                                        break;

                                    case 'sourcecode':
                                        echo '<div class="text-center">
                                                <i class="fas fa-code text-gray-600 text-6xl mb-4"></i>
                                                <h3 class="text-xl font-bold mb-4">Source Code</h3>
                                                <a href="' . $resource_url . '" target="_blank"
                                                   class="inline-flex items-center bg-gray-800 hover:bg-gray-900 text-white px-8 py-3 rounded-lg font-semibold transition">
                                                    <i class="fab fa-github mr-2"></i> ดูบน GitHub
                                                </a>
                                              </div>';
                                        break;

                                    case 'flipbook':
                                        echo '<div class="aspect-video bg-gray-100 rounded-lg overflow-hidden">
                                                <iframe src="' . $resource_url . '"
                                                        width="100%"
                                                        height="100%"
                                                        frameborder="0"
                                                        allowfullscreen>
                                                </iframe>
                                              </div>';
                                        break;

                                    default:
                                        echo '<div class="text-center">
                                                <a href="' . $resource_url . '" target="_blank"
                                                   class="inline-flex items-center bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded-lg font-semibold transition">
                                                    <i class="fas fa-external-link-alt mr-2"></i> เปิดทรัพยากร
                                                </a>
                                              </div>';
                                }
                                ?>
                            </div>

                            <!-- Tags -->
                            <?php if (!empty($resource['tags'])): ?>
                            <div class="mb-6">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">แท็ก:</h3>
                                <div class="flex flex-wrap gap-2">
                                    <?php
                                    $tags = explode(',', $resource['tags']);
                                    foreach ($tags as $tag):
                                        $tag = trim($tag);
                                    ?>
                                    <span class="bg-gray-200 text-gray-700 px-3 py-1 rounded-full text-sm">
                                        #<?= htmlspecialchars($tag) ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Share Buttons -->
                            <div class="border-t pt-6">
                                <h3 class="text-sm font-semibold text-gray-700 mb-3">แชร์:</h3>
                                <div class="flex gap-3">
                                    <a href="#" class="flex items-center justify-center w-10 h-10 bg-blue-600 hover:bg-blue-700 text-white rounded-full transition">
                                        <i class="fab fa-facebook-f"></i>
                                    </a>
                                    <a href="#" class="flex items-center justify-center w-10 h-10 bg-blue-400 hover:bg-blue-500 text-white rounded-full transition">
                                        <i class="fab fa-twitter"></i>
                                    </a>
                                    <a href="#" class="flex items-center justify-center w-10 h-10 bg-green-500 hover:bg-green-600 text-white rounded-full transition">
                                        <i class="fab fa-line"></i>
                                    </a>
                                    <button onclick="copyLink()" class="flex items-center justify-center w-10 h-10 bg-gray-600 hover:bg-gray-700 text-white rounded-full transition">
                                        <i class="fas fa-link"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <!-- Related Resources -->
                    <?php if (!empty($related_resources)): ?>
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-bookmark text-teal-600 mr-2"></i>
                            ทรัพยากรที่เกี่ยวข้อง
                        </h3>

                        <div class="space-y-4">
                            <?php foreach ($related_resources as $related):
                                $related_type_config = get_resource_type_config($related['resource_type']);
                            ?>
                            <a href="resource-detail.php?id=<?= $related['id'] ?>"
                               class="block group hover:bg-gray-50 rounded-lg p-3 transition">
                                <div class="flex gap-3">
                                    <img src="<?= htmlspecialchars($related['cover_image']) ?>"
                                         alt="<?= htmlspecialchars($related['title']) ?>"
                                         class="w-20 h-20 object-cover rounded-lg flex-shrink-0"
                                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'80\' height=\'80\'%3E%3Crect fill=\'%23E5E7EB\' width=\'80\' height=\'80\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'12\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3EImage%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 group-hover:text-teal-600 transition line-clamp-2 mb-1">
                                            <?= htmlspecialchars($related['title']) ?>
                                        </h4>
                                        <div class="flex items-center gap-2 text-xs text-gray-600">
                                            <span class="<?= $related_type_config['bg'] ?> <?= $related_type_config['text'] ?> px-2 py-0.5 rounded">
                                                <i class="<?= $related_type_config['icon'] ?>"></i>
                                                <?= $related_type_config['label'] ?>
                                            </span>
                                            <span><i class="far fa-eye"></i> <?= number_format($related['view_count']) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Back to Learning Center -->
                    <a href="index.php#learning"
                       class="block w-full bg-gradient-to-r from-teal-600 to-blue-600 hover:from-teal-700 hover:to-blue-700 text-white text-center px-6 py-4 rounded-xl font-semibold transition shadow-lg">
                        <i class="fas fa-arrow-left mr-2"></i>
                        กลับไปศูนย์การเรียนรู้
                    </a>
                </div>
            </div>
        </div>
    </section>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
                        <li><a href="#" class="hover:text-white transition-colors">โครงการตู้น้ำดื่มอัจฉริยะ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">โครงการป้ายรถเมล์อัจฉริยะ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">โครงการรังสิตซิตี้แอพ</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">ข้อมูล</h5>
                    <ul class="space-y-2 text-xs md:text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">ศูนย์ข้อมูลข่าวสาร</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">คำถามที่พบบ่อย</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">ดาวน์โหลด</a></li>
                        <li><a href="admin-login.php" class="hover:text-yellow-400 transition-colors flex items-center">
                            <i class="fas fa-user-shield mr-2"></i>Admin Panel
                        </a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">ติดต่อเรา</h5>
                    <p class="text-xs md:text-sm mb-2">ที่อยู่ เลขที่ 151 ถนนรังสิต-ปทุมธานี </p>
                    <p class="text-xs md:text-sm mb-2">ตำบลประชาธิปัตย์ อำเภอธัญบุรี </p>
                    <p class="text-xs md:text-sm">จังหวัดปทุมธานี 12130</p>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center text-xs md:text-sm">
                <p>&copy; 2569 Rangsit City Municipality . All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Start Session only if not already started
        <?php if (session_status() === PHP_SESSION_NONE) echo "/* session not started in JS context */"; ?>
        
        // Mobile Menu Toggle logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');

                // Toggle icon between bars and times
                const icon = this.querySelector('i');
                if (mobileMenu.classList.contains('hidden')) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                } else {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                }
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideMenu = mobileMenu.contains(event.target);
                const isClickOnButton = mobileMenuBtn.contains(event.target);

                if (!isClickInsideMenu && !isClickOnButton && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                    mobileMenuBtn.querySelector('i').classList.remove('fa-times');
                    mobileMenuBtn.querySelector('i').classList.add('fa-bars');
                }
            });
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>
