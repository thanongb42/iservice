<?php
/**
 * Learning Resources Management (CRUD) - Modal Version
 * หน้าจัดการศูนย์รวมการเรียนรู้ (SweetAlert2 Modal + Image/File Upload)
 */

session_start();
require_once '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$page_title = 'จัดการศูนย์รวมการเรียนรู้';
$current_page = 'learning_resources';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการศูนย์รวมการเรียนรู้']
];

// Fetch all resources
$resources = [];
$result = $conn->query("SELECT * FROM learning_resources ORDER BY is_featured DESC, display_order ASC, created_at DESC");
while ($row = $result->fetch_assoc()) {
    $resources[] = $row;
}

// Resource type icons
$type_icons = [
    'pdf' => 'fa-file-pdf text-red-500',
    'video' => 'fa-video text-blue-500',
    'podcast' => 'fa-podcast text-purple-500',
    'blog' => 'fa-blog text-green-500',
    'sourcecode' => 'fa-code text-gray-700',
    'youtube' => 'fa-youtube text-red-600',
    'flipbook' => 'fa-book-open text-teal-500'
];
$resource_types = array_keys($type_icons);
?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<main class="main-content-transition lg:ml-0">

    <style>
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-graduation-cap text-teal-600"></i> จัดการศูนย์รวมการเรียนรู้
                </h1>
                <p class="mt-2 text-gray-600">
                    เพิ่ม แก้ไข ลบ คู่มือ หลักสูตร Blog และทรัพยากรการเรียนรู้ต่างๆ |
                    ทั้งหมด: <strong><?= count($resources) ?></strong> |
                    แนะนำ: <strong><?= count(array_filter($resources, fn($r) => $r['is_featured'])) ?></strong>
                </p>
            </div>
            <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2 transition">
                <i class="fas fa-plus"></i> เพิ่มทรัพยากร
            </button>
        </div>

        <!-- Resources Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($resources as $resource):
                $icon = $type_icons[$resource['resource_type']] ?? 'fa-file text-gray-500';
                $cover_src = $resource['cover_image'] ? fix_asset_path($resource['cover_image'], true) : '';
            ?>
                <div class="border rounded-xl overflow-hidden hover:shadow-lg transition <?= !$resource['is_active'] ? 'opacity-50' : '' ?>">
                    <div class="relative h-40 bg-gray-200 overflow-hidden">
                        <?php if ($cover_src): ?>
                            <img src="<?= htmlspecialchars($cover_src) ?>" alt="<?= htmlspecialchars($resource['title']) ?>"
                                 class="w-full h-full object-cover"
                                 onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'300\' height=\'200\'%3E%3Crect fill=\'%23E5E7EB\' width=\'300\' height=\'200\'/%3E%3Ctext fill=\'%236B7280\' font-family=\'Arial,sans-serif\' font-size=\'14\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dominant-baseline=\'middle\'%3ENo Image%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-300 to-gray-400">
                                <i class="fas fa-image text-gray-500 text-3xl"></i>
                            </div>
                        <?php endif; ?>

                        <div class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
                            <i class="fas <?= $icon ?>"></i>
                            <span><?= htmlspecialchars(strtoupper($resource['resource_type'])) ?></span>
                        </div>

                        <?php if ($resource['is_featured']): ?>
                            <div class="absolute top-2 right-2 bg-yellow-400 p-2 rounded-full" title="แนะนำ">
                                <i class="fas fa-star text-white"></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="p-4">
                        <?php if (!empty($resource['category'])): ?>
                            <span class="inline-block bg-teal-100 text-teal-700 text-xs px-2 py-1 rounded mb-1">
                                <?= htmlspecialchars($resource['category']) ?>
                            </span>
                        <?php endif; ?>
                        <h3 class="font-bold text-gray-800 line-clamp-2"><?= htmlspecialchars($resource['title']) ?></h3>
                        <p class="text-sm text-gray-600 line-clamp-2 mt-1"><?= htmlspecialchars($resource['description'] ?? '') ?></p>

                        <div class="flex items-center gap-3 text-xs text-gray-500 mt-2">
                            <span><i class="far fa-eye mr-1"></i><?= number_format($resource['view_count'] ?? 0) ?></span>
                            <?php if (!empty($resource['author'])): ?>
                                <span><i class="far fa-user mr-1"></i><?= htmlspecialchars($resource['author']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($resource['duration'])): ?>
                                <span><i class="far fa-clock mr-1"></i><?= htmlspecialchars($resource['duration']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-between mt-3 pt-3 border-t">
                            <div class="flex items-center gap-2">
                                <button onclick="toggleActive(<?= $resource['id'] ?>, <?= $resource['is_active'] ?>)" title="เปิด/ปิดใช้งาน">
                                    <?php if ($resource['is_active']): ?>
                                        <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                    <?php else: ?>
                                        <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                    <?php endif; ?>
                                </button>
                                <button onclick="toggleFeatured(<?= $resource['id'] ?>, <?= $resource['is_featured'] ?>)" title="แนะนำ/ยกเลิกแนะนำ">
                                    <?php if ($resource['is_featured']): ?>
                                        <i class="fas fa-star text-yellow-500 text-xl"></i>
                                    <?php else: ?>
                                        <i class="fas fa-star text-gray-300 text-xl"></i>
                                    <?php endif; ?>
                                </button>
                            </div>
                            <div class="flex items-center gap-2">
                                <button onclick='openEditModal(<?= $resource["id"] ?>)' class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-3 py-1 rounded transition">
                                    <i class="fas fa-edit mr-1"></i>แก้ไข
                                </button>
                                <button onclick="deleteResource(<?= $resource['id'] ?>)" class="bg-red-500 hover:bg-red-600 text-white text-xs px-3 py-1 rounded transition">
                                    <i class="fas fa-trash mr-1"></i>ลบ
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <?php if (empty($resources)): ?>
                <div class="col-span-full text-center py-12 text-gray-500">
                    <i class="fas fa-inbox text-6xl mb-4"></i>
                    <p>ยังไม่มีทรัพยากรในระบบ</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Resources data from PHP
        const allResourcesData = <?= json_encode($resources, JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const resourceTypes = <?= json_encode($resource_types) ?>;

        // Build resource type options HTML
        function buildTypeOptions(selected) {
            return resourceTypes.map(t =>
                `<option value="${t}" ${t === selected ? 'selected' : ''}>${t.toUpperCase()}</option>`
            ).join('');
        }

        // Switch upload/link tabs for resource file and cover image
        function switchTab(prefix, tab) {
            const uploadBtn   = document.getElementById(prefix + '_tab_upload');
            const linkBtn     = document.getElementById(prefix + '_tab_link');
            const uploadPanel = document.getElementById(prefix + '_panel_upload');
            const linkPanel   = document.getElementById(prefix + '_panel_link');
            const activeClass = 'flex-1 py-1.5 text-xs font-semibold transition bg-teal-600 text-white';
            const inactiveClass = 'flex-1 py-1.5 text-xs font-semibold transition bg-gray-100 text-gray-600 hover:bg-gray-200';
            if (tab === 'upload') {
                uploadBtn.className = activeClass;
                linkBtn.className   = inactiveClass;
                uploadPanel.classList.remove('hidden');
                linkPanel.classList.add('hidden');
            } else {
                linkBtn.className   = activeClass;
                uploadBtn.className = inactiveClass;
                linkPanel.classList.remove('hidden');
                uploadPanel.classList.add('hidden');
            }
        }

        // Build modal form HTML
        function buildResourceForm(data = null) {
            const isEdit = data !== null;

            const resIsUrl    = isEdit && data.resource_url  && /^https?:\/\//.test(data.resource_url);
            const coverIsUrl  = isEdit && data.cover_image   && /^https?:\/\//.test(data.cover_image);
            const resExisting   = isEdit && !resIsUrl   ? (data.resource_url  || '') : '';
            const coverExisting = isEdit && !coverIsUrl ? (data.cover_image   || '') : '';

            const resUploadCls   = !resIsUrl   ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200';
            const resLinkCls     = resIsUrl    ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200';
            const coverUploadCls = !coverIsUrl ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200';
            const coverLinkCls   = coverIsUrl  ? 'bg-teal-600 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200';

            const resFileName   = resExisting   ? resExisting.split('/').pop()   : '';
            const coverFileName = coverExisting ? coverExisting.split('/').pop() : '';

            return `
                ${isEdit ? `<input type="hidden" id="modal_id" value="${data.id}">` : ''}
                <div class="space-y-3 text-left" style="max-height:70vh;overflow-y:auto;padding-right:4px;">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">ชื่อทรัพยากร *</label>
                        <input type="text" id="modal_title" value="${isEdit ? (data.title || '').replace(/"/g, '&quot;') : ''}"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">รายละเอียด *</label>
                        <textarea id="modal_description" rows="3"
                                  class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">${isEdit ? (data.description || '') : ''}</textarea>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">ประเภท *</label>
                            <select id="modal_resource_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                ${buildTypeOptions(isEdit ? data.resource_type : 'pdf')}
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">หมวดหมู่</label>
                            <input type="text" id="modal_category" value="${isEdit ? (data.category || '') : ''}" placeholder="เช่น คู่มือ, หลักสูตร, Blog"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Resource File / Link -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-paperclip"></i> ไฟล์/ลิงก์ทรัพยากร
                        </label>
                        <div class="flex rounded-lg overflow-hidden border border-gray-300 mb-2">
                            <button type="button" id="res_tab_upload" onclick="switchTab('res','upload')"
                                    class="flex-1 py-1.5 text-xs font-semibold transition ${resUploadCls}">
                                <i class="fas fa-upload mr-1"></i> อัปโหลดไฟล์
                            </button>
                            <button type="button" id="res_tab_link" onclick="switchTab('res','link')"
                                    class="flex-1 py-1.5 text-xs font-semibold transition ${resLinkCls}">
                                <i class="fas fa-link mr-1"></i> ลิงก์ URL
                            </button>
                        </div>
                        <div id="res_panel_upload" ${resIsUrl ? 'class="hidden"' : ''}>
                            <input type="file" id="modal_resource_file"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">PDF, Word, Excel, PowerPoint, วิดีโอ, เสียง, ZIP (สูงสุด 50MB)</p>
                            ${resFileName ? `<p class="text-xs text-blue-600 mt-1"><i class="fas fa-file-alt mr-1"></i>ไฟล์ปัจจุบัน: <strong>${resFileName}</strong></p>` : ''}
                            <input type="hidden" id="modal_resource_existing" value="${resExisting.replace(/"/g, '&quot;')}">
                        </div>
                        <div id="res_panel_link" ${!resIsUrl ? 'class="hidden"' : ''}>
                            <input type="text" id="modal_resource_url"
                                   value="${isEdit && resIsUrl ? (data.resource_url || '').replace(/"/g, '&quot;') : ''}"
                                   placeholder="https://..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">ลิงก์ URL ไปยัง PDF, วิดีโอ YouTube, Blog หรือทรัพยากรออนไลน์</p>
                        </div>
                    </div>

                    <!-- Cover Image / Link -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">
                            <i class="fas fa-image"></i> ภาพหน้าปก
                        </label>
                        <div class="flex rounded-lg overflow-hidden border border-gray-300 mb-2">
                            <button type="button" id="cover_tab_upload" onclick="switchTab('cover','upload')"
                                    class="flex-1 py-1.5 text-xs font-semibold transition ${coverUploadCls}">
                                <i class="fas fa-upload mr-1"></i> อัปโหลดรูปภาพ
                            </button>
                            <button type="button" id="cover_tab_link" onclick="switchTab('cover','link')"
                                    class="flex-1 py-1.5 text-xs font-semibold transition ${coverLinkCls}">
                                <i class="fas fa-link mr-1"></i> ลิงก์ URL
                            </button>
                        </div>
                        <div id="cover_panel_upload" ${coverIsUrl ? 'class="hidden"' : ''}>
                            <input type="file" id="modal_cover_image_file" accept="image/*"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">JPG, PNG, GIF, WEBP (สูงสุด 50MB)</p>
                            ${coverFileName ? `<p class="text-xs text-blue-600 mt-1"><i class="fas fa-image mr-1"></i>รูปปัจจุบัน: <strong>${coverFileName}</strong></p>` : ''}
                            <input type="hidden" id="modal_cover_existing" value="${coverExisting.replace(/"/g, '&quot;')}">
                        </div>
                        <div id="cover_panel_link" ${!coverIsUrl ? 'class="hidden"' : ''}>
                            <input type="text" id="modal_cover_image_url"
                                   value="${isEdit && coverIsUrl ? (data.cover_image || '').replace(/"/g, '&quot;') : ''}"
                                   placeholder="https://..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">ลิงก์ URL รูปภาพออนไลน์</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">ผู้เขียน/ผู้จัดทำ</label>
                            <input type="text" id="modal_author" value="${isEdit ? (data.author || '') : ''}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">ระยะเวลา/ความยาว</label>
                            <input type="text" id="modal_duration" value="${isEdit ? (data.duration || '') : ''}" placeholder="เช่น 15 นาที, 3 ชั่วโมง"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">ขนาดไฟล์</label>
                            <input type="text" id="modal_file_size" value="${isEdit ? (data.file_size || '') : ''}" placeholder="เช่น 2.5 MB"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 mb-1">ลำดับแสดง</label>
                            <input type="number" id="modal_display_order" value="${isEdit ? data.display_order : 0}"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Tags</label>
                        <input type="text" id="modal_tags" value="${isEdit ? (data.tags || '') : ''}" placeholder="เช่น excel,office,คู่มือ (คั่นด้วยคอมม่า)"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    </div>
                    <div class="flex items-center gap-6 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="modal_is_active" ${!isEdit || data.is_active == 1 ? 'checked' : ''}
                                   class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <span class="text-sm font-medium text-gray-700">เปิดใช้งาน</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" id="modal_is_featured" ${isEdit && data.is_featured == 1 ? 'checked' : ''}
                                   class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <span class="text-sm font-medium text-gray-700"><i class="fas fa-star text-yellow-500"></i> ทรัพยากรแนะนำ</span>
                        </label>
                    </div>
                </div>
            `;
        }

        // Collect form data from modal
        function collectResourceFormData(isEdit) {
            const title = document.getElementById('modal_title').value.trim();
            const description = document.getElementById('modal_description').value.trim();
            const resourceType = document.getElementById('modal_resource_type').value;

            if (!title || !description || !resourceType) {
                Swal.showValidationMessage('กรุณากรอก ชื่อทรัพยากร, รายละเอียด และประเภท');
                return false;
            }

            const formData = new FormData();
            formData.append('action', isEdit ? 'update' : 'add');
            formData.append('title', title);
            formData.append('description', description);
            formData.append('resource_type', resourceType);
            formData.append('category', document.getElementById('modal_category').value.trim());
            formData.append('author', document.getElementById('modal_author').value.trim());
            formData.append('duration', document.getElementById('modal_duration').value.trim());
            formData.append('file_size', document.getElementById('modal_file_size').value.trim());
            formData.append('tags', document.getElementById('modal_tags').value.trim());
            formData.append('display_order', document.getElementById('modal_display_order').value);

            if (document.getElementById('modal_is_active').checked) formData.append('is_active', '1');
            if (document.getElementById('modal_is_featured').checked) formData.append('is_featured', '1');

            // Resource file: upload tab or link tab
            const resUploadActive = document.getElementById('res_tab_upload')?.classList.contains('bg-teal-600');
            if (resUploadActive) {
                const resFile = document.getElementById('modal_resource_file');
                if (resFile && resFile.files.length > 0) {
                    formData.append('resource_file', resFile.files[0]);
                } else {
                    // Preserve existing uploaded file when no new file chosen
                    const existing = document.getElementById('modal_resource_existing')?.value || '';
                    if (existing) formData.append('resource_url', existing);
                }
            } else {
                const resUrl = document.getElementById('modal_resource_url')?.value.trim() || '';
                if (resUrl) formData.append('resource_url', resUrl);
            }

            // Cover image: upload tab or link tab
            const coverUploadActive = document.getElementById('cover_tab_upload')?.classList.contains('bg-teal-600');
            if (coverUploadActive) {
                const coverFile = document.getElementById('modal_cover_image_file');
                if (coverFile && coverFile.files.length > 0) {
                    formData.append('cover_image_file', coverFile.files[0]);
                } else {
                    // Preserve existing uploaded cover when no new file chosen
                    const existing = document.getElementById('modal_cover_existing')?.value || '';
                    if (existing) formData.append('cover_image', existing);
                }
            } else {
                const coverUrl = document.getElementById('modal_cover_image_url')?.value.trim() || '';
                if (coverUrl) formData.append('cover_image', coverUrl);
            }

            if (isEdit) {
                formData.append('id', document.getElementById('modal_id').value);
            }

            return formData;
        }

        // Save resource via AJAX
        function saveResource(formData) {
            fetch('api/learning_resources_api.php', { method: 'POST', body: formData })
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

        // Open Add Modal
        function openAddModal() {
            Swal.fire({
                title: '<i class="fas fa-plus text-teal-600 mr-2"></i> เพิ่มทรัพยากรใหม่',
                html: buildResourceForm(),
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> เพิ่ม',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#0d9488',
                width: '700px',
                didOpen: () => {
                    document.getElementById('modal_title').focus();
                },
                preConfirm: () => {
                    const formData = collectResourceFormData(false);
                    if (!formData) return false;
                    return formData;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    saveResource(result.value);
                }
            });
        }

        // Open Edit Modal
        function openEditModal(id) {
            const resource = allResourcesData.find(r => r.id == id);
            if (!resource) return;

            Swal.fire({
                title: '<i class="fas fa-edit text-teal-600 mr-2"></i> แก้ไขทรัพยากร',
                html: buildResourceForm(resource),
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึก',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#0d9488',
                width: '700px',
                didOpen: () => {
                    document.getElementById('modal_title').focus();
                },
                preConfirm: () => {
                    const formData = collectResourceFormData(true);
                    if (!formData) return false;
                    return formData;
                }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    saveResource(result.value);
                }
            });
        }

        // Delete resource
        async function deleteResource(id) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: 'ต้องการลบทรัพยากรนี้หรือไม่?',
                showCancelButton: true,
                confirmButtonText: 'ลบ',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#ef4444'
            });

            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('id', id);

                fetch('api/learning_resources_api.php', { method: 'POST', body: formData })
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

            fetch('api/learning_resources_api.php', { method: 'POST', body: formData })
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

        // Toggle featured
        async function toggleFeatured(id, currentStatus) {
            const formData = new FormData();
            formData.append('action', 'toggle_featured');
            formData.append('id', id);
            formData.append('is_featured', currentStatus == 1 ? 0 : 1);

            fetch('api/learning_resources_api.php', { method: 'POST', body: formData })
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
