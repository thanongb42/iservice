<?php
/**
 * Tech News Management (CRUD) - Modal Version
 * หน้าจัดการข่าวเทคโนโลยี (SweetAlert2 Modal + Quill Editor)
 */

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = 'จัดการข่าวเทคโนโลยี';
$current_page = 'tech_news';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการข่าวเทคโนโลยี']
];

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

$category_color_keys = array_keys($category_colors);
?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<!-- Quill Editor CDN -->
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

<main class="main-content-transition lg:ml-0">

    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        /* Quill in SweetAlert2 */
        .swal2-html-container .ql-toolbar { border-radius: 0.5rem 0.5rem 0 0; }
        .swal2-html-container .ql-container { border-radius: 0 0 0.5rem 0.5rem; min-height: 150px; }
        .swal2-html-container .ql-editor { min-height: 150px; }
    </style>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-newspaper text-teal-600"></i> จัดการข่าวเทคโนโลยี
                </h1>
                <p class="mt-2 text-gray-600">
                    เพิ่ม แก้ไข ลบ และปักหมุดข่าว |
                    ทั้งหมด: <strong><?= count($news_list) ?></strong> |
                    ปักหมุด: <strong><?= count(array_filter($news_list, fn($n) => $n['is_pinned'])) ?></strong>/4
                </p>
            </div>
            <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2 transition">
                <i class="fas fa-plus"></i> เพิ่มข่าวใหม่
            </button>
        </div>

        <!-- News List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div id="newsList" class="space-y-4">
                <?php foreach ($news_list as $news):
                    $color = $category_colors[$news['category_color']] ?? $category_colors['blue'];
                ?>
                    <div class="border rounded-xl overflow-hidden hover:shadow-lg transition <?= !$news['is_active'] ? 'opacity-50' : '' ?>" data-news-id="<?= $news['id'] ?>">
                        <div class="flex gap-4 p-4">
                            <!-- Image -->
                            <div class="w-32 h-32 flex-shrink-0 rounded-lg overflow-hidden bg-gray-200">
                                <?php if ($news['cover_image']):
                                    $img_src = fix_asset_path($news['cover_image'], true);
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
                                    <div class="flex items-center gap-2 flex-wrap">
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
                                       class="text-sm" title="เปิด/ปิด">
                                        <?php if ($news['is_active']): ?>
                                            <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                        <?php else: ?>
                                            <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                        <?php endif; ?>
                                    </button>

                                    <button onclick="togglePin(<?= $news['id'] ?>, <?= $news['is_pinned'] ?>)"
                                       class="text-sm ml-2" title="ปักหมุด/ยกเลิก">
                                        <?php if ($news['is_pinned']): ?>
                                            <i class="fas fa-thumbtack text-yellow-500 text-xl"></i>
                                        <?php else: ?>
                                            <i class="fas fa-thumbtack text-gray-400 text-xl"></i>
                                        <?php endif; ?>
                                    </button>

                                    <div class="flex-1"></div>

                                    <button onclick='openEditModal(<?= $news["id"] ?>)'
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

    <script>
        // News data from PHP
        const allNewsData = <?= json_encode($news_list, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const categoryColorKeys = <?= json_encode($category_color_keys) ?>;

        // Build color options HTML
        function buildColorOptions(selected) {
            return categoryColorKeys.map(c =>
                `<option value="${c}" ${c === selected ? 'selected' : ''}>${c.charAt(0).toUpperCase() + c.slice(1)}</option>`
            ).join('');
        }

        // Build modal form HTML
        function buildNewsForm(data = null) {
            const isEdit = data !== null;
            return `
                ${isEdit ? `<input type="hidden" id="modal_id" value="${data.id}">` : ''}
                <div class="space-y-3 text-left" style="max-height:70vh;overflow-y:auto;padding-right:4px;">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">หัวข้อข่าว *</label>
                        <input type="text" id="modal_title" value="${isEdit ? (data.title || '').replace(/"/g, '&quot;') : ''}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">คำอธิบายสั้น *</label>
                        <textarea id="modal_description" rows="2"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">${isEdit ? (data.description || '') : ''}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">เนื้อหาข่าว</label>
                        <div id="modal-quill-editor" style="min-height:150px;"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">หมวดหมู่ *</label>
                            <input type="text" id="modal_category" value="${isEdit ? (data.category || '') : ''}" placeholder="AI, Cloud, Security"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">สี</label>
                            <select id="modal_category_color" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                ${buildColorOptions(isEdit ? data.category_color : 'blue')}
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1"><i class="fas fa-image"></i> อัปโหลดภาพหน้าปก</label>
                        <input type="file" id="modal_cover_image_file" accept="image/*"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF (สูงสุด 5MB)</p>
                        <div class="mt-1">
                            <label class="block text-xs text-gray-600 mb-1">หรือใส่ URL รูปภาพ</label>
                            <input type="text" id="modal_cover_image_url" value="${isEdit ? (data.cover_image || '') : ''}" placeholder="https://..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">ผู้เขียน</label>
                        <input type="text" id="modal_author" value="${isEdit ? (data.author || '') : 'ทีมข่าวเทคโนโลยี'}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tags</label>
                        <input type="text" id="modal_tags" value="${isEdit ? (data.tags || '') : ''}" placeholder="AI,Cloud,Tech (คั่นด้วยคอมม่า)"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">วันที่เผยแพร่</label>
                            <input type="date" id="modal_published_date" value="${isEdit ? data.published_date : '<?= date('Y-m-d') ?>'}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">ลำดับ</label>
                            <input type="number" id="modal_display_order" value="${isEdit ? data.display_order : 0}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="flex items-center">
                        <input type="checkbox" id="modal_is_pinned" ${isEdit && data.is_pinned == 1 ? 'checked' : ''}
                               class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                        <label for="modal_is_pinned" class="ml-2 text-sm font-medium text-gray-700">
                            <i class="fas fa-thumbtack text-yellow-500"></i> ปักหมุด (สูงสุด 4 ข่าว)
                        </label>
                    </div>
                </div>
            `;
        }

        // Collect form data from modal
        function collectNewsFormData(isEdit) {
            const title = document.getElementById('modal_title').value.trim();
            const description = document.getElementById('modal_description').value.trim();
            const category = document.getElementById('modal_category').value.trim();

            if (!title || !description || !category) {
                Swal.showValidationMessage('กรุณากรอก หัวข้อ, คำอธิบาย และหมวดหมู่');
                return false;
            }

            // Get Quill content
            const quillEditor = document.querySelector('#modal-quill-editor .ql-editor');
            const content = quillEditor ? quillEditor.innerHTML : '';

            const formData = new FormData();
            formData.append('action', isEdit ? 'update' : 'add');
            formData.append('title', title);
            formData.append('description', description);
            formData.append('content', content);
            formData.append('category', category);
            formData.append('category_color', document.getElementById('modal_category_color').value);
            formData.append('author', document.getElementById('modal_author').value);
            formData.append('tags', document.getElementById('modal_tags').value);
            formData.append('published_date', document.getElementById('modal_published_date').value);
            formData.append('display_order', document.getElementById('modal_display_order').value);

            if (document.getElementById('modal_is_pinned').checked) {
                formData.append('is_pinned', '1');
            }

            // File upload
            const fileInput = document.getElementById('modal_cover_image_file');
            if (fileInput && fileInput.files.length > 0) {
                formData.append('cover_image', fileInput.files[0]);
            } else {
                const urlVal = document.getElementById('modal_cover_image_url').value.trim();
                if (urlVal) formData.append('cover_image_url', urlVal);
            }

            if (isEdit) {
                formData.append('id', document.getElementById('modal_id').value);
            }

            return formData;
        }

        // Save news via AJAX
        function saveNews(formData) {
            fetch('api/tech_news_api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ title: 'สำเร็จ!', text: data.message, icon: 'success', confirmButtonColor: '#0d9488' })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
        }

        // Initialize Quill inside modal
        function initModalQuill(content) {
            setTimeout(() => {
                const editorEl = document.getElementById('modal-quill-editor');
                if (!editorEl) return;

                const quill = new Quill('#modal-quill-editor', {
                    theme: 'snow',
                    modules: {
                        toolbar: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline', 'strike'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                            [{ 'align': [] }],
                            ['link', 'image'],
                            ['clean']
                        ]
                    },
                    placeholder: 'เขียนเนื้อหาข่าวที่นี่...'
                });

                if (content) {
                    quill.root.innerHTML = content;
                }
            }, 100);
        }

        // Open Add Modal
        function openAddModal() {
            Swal.fire({
                title: '<i class="fas fa-plus text-teal-600 mr-2"></i> เพิ่มข่าวใหม่',
                html: buildNewsForm(),
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> เพิ่มข่าว',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#0d9488',
                width: '700px',
                didOpen: () => {
                    initModalQuill('');
                    document.getElementById('modal_title').focus();
                },
                preConfirm: () => {
                    const formData = collectNewsFormData(false);
                    if (!formData) return false;
                    return formData;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    saveNews(result.value);
                }
            });
        }

        // Open Edit Modal
        function openEditModal(id) {
            const news = allNewsData.find(n => n.id == id);
            if (!news) return;

            Swal.fire({
                title: '<i class="fas fa-edit text-teal-600 mr-2"></i> แก้ไขข่าว',
                html: buildNewsForm(news),
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึก',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#0d9488',
                width: '700px',
                didOpen: () => {
                    initModalQuill(news.content || '');
                    document.getElementById('modal_title').focus();
                },
                preConfirm: () => {
                    const formData = collectNewsFormData(true);
                    if (!formData) return false;
                    return formData;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    saveNews(result.value);
                }
            });
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
                confirmButtonColor: '#ef4444'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('api/tech_news_api.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({ title: 'สำเร็จ!', text: data.message, icon: 'success', confirmButtonColor: '#0d9488' })
                                .then(() => location.reload());
                        } else {
                            Swal.fire('ข้อผิดพลาด', data.message, 'error');
                        }
                    })
                    .catch(() => Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
            }
        }

        // Toggle active
        async function toggleActive(id, currentStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_active');
            formData.append('id', id);
            formData.append('is_active', currentStatus == 1 ? 0 : 1);

            fetch('api/tech_news_api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message, timer: 1500, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
        }

        // Toggle pin
        async function togglePin(id, currentStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_pin');
            formData.append('id', id);
            formData.append('is_pinned', currentStatus == 1 ? 0 : 1);

            fetch('api/tech_news_api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message, timer: 1500, showConfirmButton: false })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                })
                .catch(() => Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
        }
    </script>
</main>

<?php
include 'admin-layout/footer.php';
$conn->close();
?>
