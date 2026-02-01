<?php
/**
 * Tech News Management (CRUD) - AJAX Version
 * หน้าจัดการข่าวเทคโนโลยี
 */

require_once '../config/database.php';
session_start();

// Fetch all news
$news_list = [];
$result = $conn->query("SELECT * FROM tech_news ORDER BY is_pinned DESC, display_order ASC, created_at DESC");
while ($row = $result->fetch_assoc()) {
    $news_list[] = $row;
}

// Category colors
$category_colors = [
    'blue' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
    'green' => ['bg' => 'bg-green-100', 'text' => 'text-green-700'],
    'red' => ['bg' => 'bg-red-100', 'text' => 'text-red-700'],
    'purple' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
    'orange' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
    'teal' => ['bg' => 'bg-teal-100', 'text' => 'text-teal-700'],
    'indigo' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'],
];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการข่าวเทคโนโลยี - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Quill Editor (Free, No API Key Required) -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        <i class="fas fa-newspaper text-teal-600"></i> จัดการข่าวเทคโนโลยี
                    </h1>
                    <p class="text-gray-600 mt-2">เพิ่ม แก้ไข ลบ และปักหมุดข่าว (สูงสุด 4 ข่าว)</p>
                </div>
                <div class="flex space-x-3">
                    <a href="learning_resources.php" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-graduation-cap mr-2"></i>จัดการศูนย์เรียนรู้
                    </a>
                    <a href="../index.php" class="bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg transition">
                        <i class="fas fa-arrow-left mr-2"></i>กลับหน้าแรก
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- News List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-teal-600"></i> รายการข่าวทั้งหมด (<span id="totalNews"><?= count($news_list) ?></span>)
                        <span class="text-sm text-gray-500 ml-2">
                            (ปักหมุด: <span id="pinnedCount"><?= count(array_filter($news_list, fn($n) => $n['is_pinned'])) ?></span>/4)
                        </span>
                    </h2>

                    <div id="newsList" class="space-y-4">
                        <?php foreach ($news_list as $news):
                            $color = $category_colors[$news['category_color']] ?? $category_colors['blue'];
                        ?>
                            <div class="border rounded-xl overflow-hidden hover:shadow-lg transition <?= !$news['is_active'] ? 'opacity-50' : '' ?>" data-news-id="<?= $news['id'] ?>">
                                <div class="flex gap-4 p-4">
                                    <!-- Image -->
                                    <div class="w-32 h-32 flex-shrink-0 rounded-lg overflow-hidden bg-gray-200">
                                        <?php if ($news['cover_image']):
                                            $img_src = $news['cover_image'];
                                            if (!preg_match('/^https?:\/\//', $img_src) && !str_starts_with($img_src, '../')) {
                                                $img_src = '../' . $img_src;
                                            }
                                        ?>
                                            <img src="<?= htmlspecialchars($img_src) ?>"
                                                 alt="<?= htmlspecialchars($news['title']) ?>"
                                                 class="w-full h-full object-cover"
                                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'128\' height=\'128\'%3E%3Crect fill=\'%23E5E7EB\' width=\'128\' height=\'128\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'12\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                                        <?php else: ?>
                                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
                                                <i class="fas fa-image text-gray-500 text-3xl"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between mb-2">
                                            <div class="flex items-center gap-2">
                                                <span class="<?= $color['bg'] ?> <?= $color['text'] ?> text-xs px-2 py-1 rounded">
                                                    <?= htmlspecialchars($news['category']) ?>
                                                </span>
                                                <?php if ($news['is_pinned']): ?>
                                                    <span class="bg-yellow-100 text-yellow-700 text-xs px-2 py-1 rounded flex items-center">
                                                        <i class="fas fa-thumbtack mr-1"></i>ปักหมุด
                                                    </span>
                                                <?php endif; ?>
                                                <span class="text-xs text-gray-500">
                                                    ลำดับ: <?= $news['display_order'] ?>
                                                </span>
                                            </div>
                                        </div>

                                        <h3 class="font-bold text-gray-900 line-clamp-2 mb-1">
                                            <?= htmlspecialchars($news['title']) ?>
                                        </h3>

                                        <p class="text-sm text-gray-600 line-clamp-2 mb-2">
                                            <?= htmlspecialchars($news['description']) ?>
                                        </p>

                                        <div class="flex items-center gap-4 text-xs text-gray-500">
                                            <span><i class="far fa-eye mr-1"></i><?= number_format($news['view_count']) ?></span>
                                            <span><i class="far fa-calendar mr-1"></i><?= date('d/m/Y', strtotime($news['published_date'])) ?></span>
                                            <?php if ($news['author']): ?>
                                                <span><i class="far fa-user mr-1"></i><?= htmlspecialchars($news['author']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Actions -->
                                        <div class="flex items-center gap-2 mt-3 pt-3 border-t">
                                            <button onclick="toggleActive(<?= $news['id'] ?>, <?= $news['is_active'] ?>)"
                                               class="text-sm"
                                               title="เปิด/ปิด">
                                                <?php if ($news['is_active']): ?>
                                                    <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                                <?php endif; ?>
                                            </button>

                                            <button onclick="togglePin(<?= $news['id'] ?>, <?= $news['is_pinned'] ?>)"
                                               class="text-sm ml-2"
                                               title="ปักหมุด/ยกเลิก">
                                                <?php if ($news['is_pinned']): ?>
                                                    <i class="fas fa-thumbtack text-yellow-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-thumbtack text-gray-400 text-xl"></i>
                                                <?php endif; ?>
                                            </button>

                                            <div class="flex-1"></div>

                                            <button onclick='editNews(<?= json_encode($news, JSON_HEX_APOS) ?>)'
                                               class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded transition">
                                                <i class="fas fa-edit mr-1"></i>แก้ไข
                                            </button>
                                            <button onclick="deleteNews(<?= $news['id'] ?>)"
                                               class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded transition">
                                                <i class="fas fa-trash mr-1"></i>ลบ
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($news_list)): ?>
                            <div class="text-center py-12 text-gray-500">
                                <i class="fas fa-inbox text-6xl mb-4"></i>
                                <p>ยังไม่มีข่าวในระบบ</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-plus text-teal-600" id="formIcon"></i>
                        <span id="formTitle">เพิ่มข่าวใหม่</span>
                    </h2>

                    <form id="newsForm" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="id" id="newsId">
                        <input type="hidden" name="action" id="formAction" value="add">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">หัวข้อข่าว *</label>
                            <input type="text" name="title" id="title" required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบายสั้น *</label>
                            <textarea name="description" id="description" required rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">เนื้อหาข่าว</label>
                            <div id="quill-editor" class="bg-white border border-gray-300 rounded-lg" style="min-height: 200px;"></div>
                            <input type="hidden" name="content" id="content">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">หมวดหมู่ *</label>
                                <input type="text" name="category" id="category" required
                                       placeholder="AI, Cloud, Security"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">สี</label>
                                <select name="category_color" id="category_color" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                                    <?php foreach ($category_colors as $color_key => $color_val): ?>
                                        <option value="<?= $color_key ?>"><?= ucfirst($color_key) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-image"></i> อัปโหลดภาพหน้าปก
                            </label>
                            <input type="file" name="cover_image" id="cover_image_file" accept="image/*"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF (สูงสุด 10MB)</p>

                            <div class="mt-2">
                                <label class="block text-xs font-medium text-gray-600 mb-1">หรือใส่ URL รูปภาพ</label>
                                <input type="text" name="cover_image_url" id="cover_image_url"
                                       placeholder="https://..."
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ผู้เขียน</label>
                            <input type="text" name="author" id="author" value="ทีมข่าวเทคโนโลยี"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tags</label>
                            <input type="text" name="tags" id="tags"
                                   placeholder="AI,Cloud,Tech (คั่นด้วยคอมม่า)"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">วันที่เผยแพร่</label>
                                <input type="date" name="published_date" id="published_date" value="<?= date('Y-m-d') ?>"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับ *</label>
                                <input type="number" name="display_order" id="display_order" required value="0"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_pinned" id="is_pinned"
                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                                <label for="is_pinned" class="ml-2 text-sm font-medium text-gray-700">
                                    <i class="fas fa-thumbtack text-yellow-500"></i> ปักหมุด (สูงสุด 4 ข่าว)
                                </label>
                            </div>
                        </div>

                        <div class="flex space-x-3 pt-4">
                            <button type="submit"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-save mr-2"></i>
                                <span id="submitBtnText">เพิ่มข่าว</span>
                            </button>

                            <button type="button" id="cancelBtn" onclick="resetForm()" style="display:none"
                                class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold text-center transition">
                                <i class="fas fa-times mr-2"></i>ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Quill Editor
        var quill = new Quill('#quill-editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['clean']
                ]
            },
            placeholder: 'เขียนเนื้อหาข่าวที่นี่...'
        });

        // Form submit handler
        document.getElementById('newsForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            // Update content from Quill
            document.getElementById('content').value = quill.root.innerHTML;

            const formData = new FormData(this);
            const action = document.getElementById('formAction').value;

            try {
                const response = await fetch('api/tech_news_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonColor: '#0d9488'
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message,
                        confirmButtonColor: '#0d9488'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#0d9488'
                });
            }
        });

        // Edit news
        function editNews(news) {
            document.getElementById('formTitle').textContent = 'แก้ไขข่าว';
            document.getElementById('formIcon').className = 'fas fa-edit text-teal-600';
            document.getElementById('submitBtnText').textContent = 'บันทึกการแก้ไข';
            document.getElementById('formAction').value = 'update';
            document.getElementById('cancelBtn').style.display = 'block';

            // Fill form
            document.getElementById('newsId').value = news.id;
            document.getElementById('title').value = news.title;
            document.getElementById('description').value = news.description;
            quill.root.innerHTML = news.content || '';
            document.getElementById('category').value = news.category;
            document.getElementById('category_color').value = news.category_color;
            document.getElementById('cover_image_url').value = news.cover_image || '';
            document.getElementById('author').value = news.author || '';
            document.getElementById('tags').value = news.tags || '';
            document.getElementById('published_date').value = news.published_date;
            document.getElementById('display_order').value = news.display_order;
            document.getElementById('is_pinned').checked = news.is_pinned == 1;

            // Scroll to form
            document.getElementById('newsForm').scrollIntoView({ behavior: 'smooth' });
        }

        // Reset form
        function resetForm() {
            document.getElementById('formTitle').textContent = 'เพิ่มข่าวใหม่';
            document.getElementById('formIcon').className = 'fas fa-plus text-teal-600';
            document.getElementById('submitBtnText').textContent = 'เพิ่มข่าว';
            document.getElementById('formAction').value = 'add';
            document.getElementById('cancelBtn').style.display = 'none';

            document.getElementById('newsForm').reset();
            document.getElementById('newsId').value = '';
            quill.root.innerHTML = '';
            document.getElementById('published_date').value = '<?= date('Y-m-d') ?>';
        }

        // Delete news
        async function deleteNews(id) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: 'ต้องการลบข่าวนี้หรือไม่?',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    const response = await fetch('api/tech_news_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const apiResult = await response.json();

                    if (apiResult.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: apiResult.message,
                            confirmButtonColor: '#0d9488'
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: apiResult.message,
                            confirmButtonColor: '#0d9488'
                        });
                    }
                } catch (error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                        confirmButtonColor: '#0d9488'
                    });
                }
            }
        }

        // Toggle active status
        async function toggleActive(id, currentStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_active');
            formData.append('id', id);
            formData.append('is_active', currentStatus == 1 ? 0 : 1);

            try {
                const response = await fetch('api/tech_news_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message,
                        confirmButtonColor: '#0d9488'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#0d9488'
                });
            }
        }

        // Toggle pin status
        async function togglePin(id, currentStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_pin');
            formData.append('id', id);
            formData.append('is_pinned', currentStatus == 1 ? 0 : 1);

            try {
                const response = await fetch('api/tech_news_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        timer: 1500,
                        showConfirmButton: false
                    });
                    location.reload();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: result.message,
                        confirmButtonColor: '#0d9488'
                    });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้',
                    confirmButtonColor: '#0d9488'
                });
            }
        }
    </script>
</body>
</html>
<?php $conn->close(); ?>
