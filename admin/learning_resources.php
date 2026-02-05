<?php
/**
 * Learning Resources Management Page
 * หน้าจัดการศูนย์รวมการเรียนรู้ (CRUD) - AJAX Version
 */

// Include database config
require_once '../config/database.php';

// Start session
session_start();

$page_title = 'จัดการศูนย์รวมการเรียนรู้';
$current_page = 'learning_resources';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการศูนย์รวมการเรียนรู้']
];

// Fetch all resources
$resources = [];
$result = $conn->query("SELECT * FROM learning_resources ORDER BY display_order ASC, created_at DESC");
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
?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<main class="main-content-transition lg:ml-0">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-graduation-cap text-teal-600"></i> จัดการศูนย์รวมการเรียนรู้
            </h1>
            <p class="mt-2 text-gray-600">เพิ่ม แก้ไข ลบ คู่มือ หลักสูตร Blog และทรัพยากรการเรียนรู้ต่างๆ</p>
        </div>

        <style>
            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        </style>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Resource Cards -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-teal-600"></i> รายการทรัพยากรทั้งหมด (<?= count($resources) ?>)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php foreach ($resources as $resource):
                            $icon_class = $type_icons[$resource['resource_type']] ?? 'fa-file text-gray-500';
                        ?>
                            <div class="border rounded-xl overflow-hidden hover:shadow-lg transition <?= !$resource['is_active'] ? 'opacity-50' : '' ?>">
                                <!-- Cover Image -->
                                <div class="relative h-40 bg-gray-200 overflow-hidden">
                                    <?php if ($resource['cover_image']):
                                        $cover_img = fix_asset_path($resource['cover_image'], true);
                                    ?>
                                        <img src="<?= htmlspecialchars($cover_img) ?>"
                                             alt="<?= htmlspecialchars($resource['title']) ?>"
                                             class="w-full h-full object-cover"
                                             onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center bg-gray-300\'><i class=\'fas fa-image-slash text-gray-500 text-4xl\'></i></div>'">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-teal-400 to-blue-500">
                                            <i class="fas fa-image text-white text-4xl opacity-50"></i>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Type Badge -->
                                    <div class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold flex items-center space-x-1">
                                        <i class="fas <?= $icon_class ?>"></i>
                                        <span class="uppercase"><?= $resource['resource_type'] ?></span>
                                    </div>

                                    <!-- Featured Star -->
                                    <?php if ($resource['is_featured']): ?>
                                        <div class="absolute top-2 right-2 bg-yellow-400 p-2 rounded-full">
                                            <i class="fas fa-star text-white"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Content -->
                                <div class="p-4">
                                    <h3 class="font-bold text-gray-800 mb-2 line-clamp-2"><?= htmlspecialchars($resource['title']) ?></h3>

                                    <?php if ($resource['category']): ?>
                                        <span class="inline-block bg-teal-100 text-teal-700 text-xs px-2 py-1 rounded mb-2">
                                            <?= htmlspecialchars($resource['category']) ?>
                                        </span>
                                    <?php endif; ?>

                                    <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($resource['description']) ?></p>

                                    <!-- Meta Info -->
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                                        <?php if ($resource['author']): ?>
                                            <span><i class="fas fa-user mr-1"></i><?= htmlspecialchars($resource['author']) ?></span>
                                        <?php endif; ?>
                                        <span><i class="fas fa-eye mr-1"></i><?= $resource['view_count'] ?></span>
                                    </div>

                                    <!-- Actions -->
                                    <div class="flex items-center justify-between pt-3 border-t">
                                        <div class="flex space-x-2">
                                            <button onclick="toggleActive(<?= $resource['id'] ?>, <?= $resource['is_active'] ? 0 : 1 ?>)" class="text-sm" title="เปิด/ปิด">
                                                <?php if ($resource['is_active']): ?>
                                                    <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                                <?php endif; ?>
                                            </button>
                                            <button onclick="toggleFeatured(<?= $resource['id'] ?>, <?= $resource['is_featured'] ? 0 : 1 ?>)" class="text-sm" title="แนะนำ">
                                                <?php if ($resource['is_featured']): ?>
                                                    <i class="fas fa-star text-yellow-500 text-xl"></i>
                                                <?php else: ?>
                                                    <i class="far fa-star text-gray-400 text-xl"></i>
                                                <?php endif; ?>
                                            </button>
                                        </div>
                                        <div class="flex space-x-2">
                                            <button onclick="editResource(<?= $resource['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteResource(<?= $resource['id'] ?>)" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($resources)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-6xl mb-4"></i>
                            <p>ยังไม่มีทรัพยากรในระบบ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">
                        <i class="fas fa-plus text-teal-600"></i> เพิ่มทรัพยากรใหม่
                    </h2>

                    <form id="resourceForm" enctype="multipart/form-data" class="space-y-4">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="resourceId">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">หัวข้อ/ชื่อเรื่อง *</label>
                            <input type="text" name="title" id="title" required
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                            <textarea name="description" id="description" rows="3"
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภท *</label>
                            <select name="resource_type" id="resource_type" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                                <option value="pdf">PDF Document</option>
                                <option value="video">Video</option>
                                <option value="podcast">Podcast</option>
                                <option value="blog">Blog</option>
                                <option value="sourcecode">Source Code</option>
                                <option value="youtube">YouTube</option>
                                <option value="flipbook">Flipbook</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-file-upload"></i> อัปโหลดไฟล์ทรัพยากร
                            </label>
                            <input type="file" name="resource_file" id="resource_file"
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.mp4,.mp3,.zip,.rar"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> ไฟล์ที่รองรับ: PDF, DOC, XLS, PPT, MP4, MP3, ZIP (สูงสุด 50MB)
                            </p>

                            <!-- Optional URL Input -->
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">หรือใส่ URL/Link ภายนอก</label>
                                <input type="text" name="resource_url" id="resource_url"
                                       placeholder="https://... (ใช้แทนการอัปโหลดไฟล์)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div id="currentResourceFile" class="mt-2 p-2 bg-blue-50 rounded text-xs text-blue-700" style="display:none;">
                                <i class="fas fa-link"></i> ไฟล์ปัจจุบัน: <span id="currentResourceFileName"></span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                <i class="fas fa-image"></i> อัปโหลดภาพหน้าปก
                            </label>
                            <input type="file" name="cover_image_file" id="cover_image_file"
                                   accept="image/*"
                                   onchange="previewCoverImage(event)"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            <p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-info-circle"></i> รูปภาพ: JPG, PNG, GIF, WEBP (สูงสุด 50MB)
                            </p>

                            <!-- Image Preview -->
                            <div id="coverImagePreview" class="mt-3 hidden">
                                <img id="previewImg" src="" alt="Preview" class="w-full h-32 object-cover rounded-lg border">
                            </div>

                            <!-- Optional URL Input -->
                            <div class="mt-3">
                                <label class="block text-xs font-medium text-gray-600 mb-1">หรือใส่ URL รูปภาพภายนอก</label>
                                <input type="text" name="cover_image" id="cover_image"
                                       placeholder="https://... (ใช้แทนการอัปโหลดไฟล์)"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500">
                            </div>

                            <div id="currentCoverImage" class="mt-2 p-2 bg-blue-50 rounded" style="display:none;">
                                <p class="text-xs text-blue-700 mb-2">
                                    <i class="fas fa-image"></i> รูปปัจจุบัน:
                                </p>
                                <img id="currentCoverImg" src="" alt="Current cover" class="w-full h-24 object-cover rounded border">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">หมวดหมู่</label>
                            <input type="text" name="category" id="category"
                                   placeholder="คู่มือ, หลักสูตร, บทความ"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ผู้เขียน/ผู้สร้าง</label>
                            <input type="text" name="author" id="author"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ระยะเวลา</label>
                                <input type="text" name="duration" id="duration"
                                       placeholder="15:30"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">ขนาดไฟล์</label>
                                <input type="text" name="file_size" id="file_size"
                                       placeholder="2.5 MB"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Tags (คั่นด้วยคอมม่า)</label>
                            <input type="text" name="tags" id="tags"
                                   placeholder="tutorial,video,การใช้งาน"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับการแสดงผล *</label>
                            <input type="number" name="display_order" id="display_order" required value="0"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                        </div>

                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" name="is_featured" id="is_featured"
                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                                <label for="is_featured" class="ml-2 text-sm font-medium text-gray-700">
                                    <i class="fas fa-star text-yellow-500"></i> แนะนำ
                                </label>
                            </div>

                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" checked
                                       class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                            </div>
                        </div>

                        <div class="flex space-x-3 pt-4">
                            <button type="submit" id="submitBtn"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-plus mr-2"></i> เพิ่มทรัพยากร
                            </button>

                            <button type="button" id="cancelBtn" onclick="resetForm()" style="display:none;"
                                    class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-times mr-2"></i>ยกเลิก
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Image preview functionality
        function previewCoverImage(event) {
            const file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('previewImg').src = e.target.result;
                    document.getElementById('coverImagePreview').classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            }
        }

        // Form submit handler
        document.getElementById('resourceForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('api/learning_resources_api.php', {
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

        // Edit resource
        async function editResource(id) {
            try {
                // Fetch resource data
                const response = await fetch(`api/get_resource.php?id=${id}`);
                const resource = await response.json();

                if (resource) {
                    // Populate form
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('resourceId').value = resource.id;
                    document.getElementById('title').value = resource.title;
                    document.getElementById('description').value = resource.description || '';
                    document.getElementById('resource_type').value = resource.resource_type;
                    document.getElementById('resource_url').value = resource.resource_url || '';
                    document.getElementById('cover_image').value = resource.cover_image || '';
                    document.getElementById('category').value = resource.category || '';
                    document.getElementById('author').value = resource.author || '';
                    document.getElementById('duration').value = resource.duration || '';
                    document.getElementById('file_size').value = resource.file_size || '';
                    document.getElementById('tags').value = resource.tags || '';
                    document.getElementById('display_order').value = resource.display_order;
                    document.getElementById('is_featured').checked = resource.is_featured == 1;
                    document.getElementById('is_active').checked = resource.is_active == 1;

                    // Show current resource file
                    if (resource.resource_url) {
                        document.getElementById('currentResourceFileName').textContent = resource.resource_url.split('/').pop();
                        document.getElementById('currentResourceFile').style.display = 'block';
                    }

                    // Show current cover image
                    if (resource.cover_image) {
                        let coverImgPath = resource.cover_image;
                        if (!coverImgPath.match(/^https?:\/\//) && !coverImgPath.startsWith('data:')) {
                            coverImgPath = coverImgPath.replace(/^(\.\.\/)+/, '');
                            if (!coverImgPath.startsWith('public/')) {
                                coverImgPath = 'public/' + coverImgPath;
                            }
                            coverImgPath = '../' + coverImgPath;
                        }
                        document.getElementById('currentCoverImg').src = coverImgPath;
                        document.getElementById('currentCoverImage').style.display = 'block';
                    }

                    // Update form title and button
                    document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-teal-600"></i> แก้ไขทรัพยากร';
                    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i> บันทึกการแก้ไข';
                    document.getElementById('cancelBtn').style.display = 'block';

                    // Scroll to form
                    document.getElementById('resourceForm').scrollIntoView({ behavior: 'smooth' });
                }
            } catch (error) {
                Swal.fire({
                    icon: 'error',
                    title: 'เกิดข้อผิดพลาด',
                    text: 'ไม่สามารถโหลดข้อมูลได้',
                    confirmButtonColor: '#0d9488'
                });
            }
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
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280'
            });

            if (result.isConfirmed) {
                try {
                    const formData = new FormData();
                    formData.append('action', 'delete');
                    formData.append('id', id);

                    const response = await fetch('api/learning_resources_api.php', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        await Swal.fire({
                            icon: 'success',
                            title: 'สำเร็จ!',
                            text: data.message,
                            confirmButtonColor: '#0d9488'
                        });
                        location.reload();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'เกิดข้อผิดพลาด',
                            text: data.message,
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
        async function toggleActive(id, isActive) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_active');
                formData.append('id', id);
                formData.append('is_active', isActive);

                const response = await fetch('api/learning_resources_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonColor: '#0d9488',
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

        // Toggle featured status
        async function toggleFeatured(id, isFeatured) {
            try {
                const formData = new FormData();
                formData.append('action', 'toggle_featured');
                formData.append('id', id);
                formData.append('is_featured', isFeatured);

                const response = await fetch('api/learning_resources_api.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    await Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: result.message,
                        confirmButtonColor: '#0d9488',
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

        // Reset form
        function resetForm() {
            document.getElementById('resourceForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('resourceId').value = '';
            document.getElementById('coverImagePreview').classList.add('hidden');
            document.getElementById('currentResourceFile').style.display = 'none';
            document.getElementById('currentCoverImage').style.display = 'none';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus text-teal-600"></i> เพิ่มทรัพยากรใหม่';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus mr-2"></i> เพิ่มทรัพยากร';
            document.getElementById('cancelBtn').style.display = 'none';
        }
    </script>
</main>

<?php
include 'admin-layout/footer.php';
?>
