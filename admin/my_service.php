<?php
/**
 * My Service Management Page
 * หน้าจัดการบริการต่างๆ (CRUD) - AJAX Version
 */

// Include database config
require_once '../config/database.php';

// Start session
session_start();

$page_title = 'จัดการบริการ';
$current_page = 'my_service';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการบริการ']
];

// Fetch all services
$services = [];
$result = $conn->query("SELECT * FROM my_service ORDER BY display_order ASC");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Color definitions for preview
$color_map = [
    'blue' => ['bg' => 'bg-blue-500', 'text' => 'text-blue-600', 'border' => 'border-blue-200'],
    'indigo' => ['bg' => 'bg-indigo-500', 'text' => 'text-indigo-600', 'border' => 'border-indigo-200'],
    'red' => ['bg' => 'bg-red-500', 'text' => 'text-red-600', 'border' => 'border-red-200'],
    'orange' => ['bg' => 'bg-orange-500', 'text' => 'text-orange-600', 'border' => 'border-orange-200'],
    'purple' => ['bg' => 'bg-purple-500', 'text' => 'text-purple-600', 'border' => 'border-purple-200'],
    'pink' => ['bg' => 'bg-pink-500', 'text' => 'text-pink-600', 'border' => 'border-pink-200'],
    'teal' => ['bg' => 'bg-teal-500', 'text' => 'text-teal-600', 'border' => 'border-teal-200'],
    'green' => ['bg' => 'bg-green-500', 'text' => 'text-green-600', 'border' => 'border-green-200'],
    'gray' => ['bg' => 'bg-gray-500', 'text' => 'text-gray-600', 'border' => 'border-gray-200'],
    'yellow' => ['bg' => 'bg-yellow-500', 'text' => 'text-yellow-600', 'border' => 'border-yellow-200'],
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
                <i class="fas fa-briefcase text-teal-600"></i> จัดการบริการ
            </h1>
            <p class="mt-2 text-gray-600">เพิ่ม แก้ไข ลบ และจัดการบริการที่มีให้ผู้ใช้งาน</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Left: Service Cards -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-th-large text-teal-600"></i> รายการบริการทั้งหมด (<?= count($services) ?>)
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" id="serviceList">
                        <?php foreach ($services as $service):
                            $colors = $color_map[$service['color_code']] ?? $color_map['blue'];
                        ?>
                            <div class="border-2 <?= $colors['border'] ?> rounded-xl p-4 hover:shadow-lg transition <?= !$service['is_active'] ? 'opacity-50' : '' ?>">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="w-12 h-12 <?= $colors['bg'] ?> rounded-lg flex items-center justify-center text-white text-2xl">
                                        <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
                                    </div>
                                    <div class="flex space-x-2">
                                        <button onclick="toggleActive(<?= $service['id'] ?>, <?= $service['is_active'] ? 0 : 1 ?>)" class="text-gray-400 hover:text-gray-600" title="เปิด/ปิด">
                                            <?php if ($service['is_active']): ?>
                                                <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                                            <?php else: ?>
                                                <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                                            <?php endif; ?>
                                        </button>
                                        <button onclick="editService(<?= $service['id'] ?>)" class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteService(<?= $service['id'] ?>)" class="text-red-600 hover:text-red-800">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                                <h3 class="font-bold text-gray-800 mb-1"><?= htmlspecialchars($service['service_name']) ?></h3>
                                <p class="text-xs text-gray-500 mb-2 uppercase font-semibold"><?= htmlspecialchars($service['service_name_en']) ?></p>
                                <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($service['description']) ?></p>
                                <div class="flex items-center justify-between text-xs text-gray-500">
                                    <span class="<?= $colors['bg'] ?> text-white px-2 py-1 rounded"><?= $service['color_code'] ?></span>
                                    <span>ลำดับ: <?= $service['display_order'] ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if (empty($services)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-6xl mb-4"></i>
                            <p>ยังไม่มีบริการในระบบ</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Add/Edit Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold text-gray-800 mb-4" id="formTitle">
                        <i class="fas fa-plus text-teal-600"></i> เพิ่มบริการใหม่
                    </h2>

                    <form id="serviceForm" class="space-y-4">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="serviceId">

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">รหัสบริการ (CODE) *</label>
                            <input type="text" name="service_code" id="service_code" required
                                   placeholder="EMAIL, INTERNET, IT_SUPPORT"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 uppercase focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                            <p class="text-xs text-gray-500 mt-1">ภาษาอังกฤษเท่านั้น ไม่มีช่องว่าง (ใช้ _ แทน)</p>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อบริการ (TH) *</label>
                            <input type="text" name="service_name" id="service_name" required
                                   placeholder="อีเมลเทศบาล"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อบริการ (EN) *</label>
                            <input type="text" name="service_name_en" id="service_name_en" required
                                   placeholder="Email Service"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                            <textarea name="description" id="description" rows="3"
                                      placeholder="รายละเอียดบริการ..."
                                      class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                Icon (Font Awesome) *
                                <a href="https://fontawesome.com/icons" target="_blank" class="text-xs text-blue-600 ml-2">
                                    <i class="fas fa-external-link-alt"></i> ค้นหา Icon
                                </a>
                            </label>
                            <div class="relative">
                                <input type="text" name="icon" id="iconInput" required
                                       value="fas fa-star"
                                       placeholder="fas fa-envelope"
                                       class="w-full border border-gray-300 rounded-lg px-4 py-2 pr-12 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <div class="absolute right-3 top-2 text-2xl" id="iconPreview">
                                    <i class="fas fa-star"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">สี (Color Code) *</label>
                            <select name="color_code" id="colorSelect" required
                                    class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <?php foreach ($color_map as $color_name => $color_classes): ?>
                                    <option value="<?= $color_name ?>" <?= $color_name == 'blue' ? 'selected' : '' ?>>
                                        <?= ucfirst($color_name) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2 p-3 border-2 rounded-lg text-center" id="colorPreview">
                                <div class="w-12 h-12 mx-auto rounded-lg flex items-center justify-center text-white text-2xl bg-blue-500">
                                    <i class="fas fa-palette"></i>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">URL</label>
                            <input type="text" name="service_url" id="service_url"
                                   value="#"
                                   placeholder="service-email.php, #, https://..."
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับการแสดงผล *</label>
                            <input type="number" name="display_order" id="display_order" required
                                   value="0"
                                   class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div class="flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" checked
                                   class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                            <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                        </div>

                        <div class="flex space-x-3">
                            <button type="submit" id="submitBtn"
                                    class="flex-1 bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                                <i class="fas fa-plus mr-2"></i> เพิ่มบริการ
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
        // Icon preview
        document.getElementById('iconInput').addEventListener('input', function() {
            const iconClass = this.value || 'fas fa-star';
            document.getElementById('iconPreview').innerHTML = '<i class="' + iconClass + '"></i>';
        });

        // Color preview
        const colorMap = <?= json_encode($color_map) ?>;
        document.getElementById('colorSelect').addEventListener('change', function() {
            const selectedColor = this.value;
            const colorClass = colorMap[selectedColor]?.bg || 'bg-blue-500';

            document.getElementById('colorPreview').innerHTML =
                '<div class="w-12 h-12 mx-auto rounded-lg flex items-center justify-center text-white text-2xl ' + colorClass + '">' +
                '<i class="fas fa-palette"></i></div>';
        });

        // Form submit handler
        document.getElementById('serviceForm').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            try {
                const response = await fetch('api/my_service_api.php', {
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

        // Edit service
        async function editService(id) {
            try {
                // Fetch service data
                const response = await fetch(`api/get_service.php?id=${id}`);
                const service = await response.json();

                if (service) {
                    // Populate form
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('serviceId').value = service.id;
                    document.getElementById('service_code').value = service.service_code;
                    document.getElementById('service_name').value = service.service_name;
                    document.getElementById('service_name_en').value = service.service_name_en;
                    document.getElementById('description').value = service.description;
                    document.getElementById('iconInput').value = service.icon;
                    document.getElementById('colorSelect').value = service.color_code;
                    document.getElementById('service_url').value = service.service_url;
                    document.getElementById('display_order').value = service.display_order;
                    document.getElementById('is_active').checked = service.is_active == 1;

                    // Update icon preview
                    document.getElementById('iconPreview').innerHTML = '<i class="' + service.icon + '"></i>';

                    // Update color preview
                    const colorClass = colorMap[service.color_code]?.bg || 'bg-blue-500';
                    document.getElementById('colorPreview').innerHTML =
                        '<div class="w-12 h-12 mx-auto rounded-lg flex items-center justify-center text-white text-2xl ' + colorClass + '">' +
                        '<i class="fas fa-palette"></i></div>';

                    // Update form title and button
                    document.getElementById('formTitle').innerHTML = '<i class="fas fa-edit text-teal-600"></i> แก้ไขบริการ';
                    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-save mr-2"></i> บันทึกการแก้ไข';
                    document.getElementById('cancelBtn').style.display = 'block';

                    // Scroll to form
                    document.getElementById('serviceForm').scrollIntoView({ behavior: 'smooth' });
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

        // Delete service
        async function deleteService(id) {
            const result = await Swal.fire({
                icon: 'warning',
                title: 'ยืนยันการลบ',
                text: 'ต้องการลบบริการนี้หรือไม่?',
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

                    const response = await fetch('api/my_service_api.php', {
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

                const response = await fetch('api/my_service_api.php', {
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
            document.getElementById('serviceForm').reset();
            document.getElementById('formAction').value = 'add';
            document.getElementById('serviceId').value = '';
            document.getElementById('iconInput').value = 'fas fa-star';
            document.getElementById('iconPreview').innerHTML = '<i class="fas fa-star"></i>';
            document.getElementById('colorSelect').value = 'blue';
            document.getElementById('colorPreview').innerHTML =
                '<div class="w-12 h-12 mx-auto rounded-lg flex items-center justify-center text-white text-2xl bg-blue-500">' +
                '<i class="fas fa-palette"></i></div>';
            document.getElementById('formTitle').innerHTML = '<i class="fas fa-plus text-teal-600"></i> เพิ่มบริการใหม่';
            document.getElementById('submitBtn').innerHTML = '<i class="fas fa-plus mr-2"></i> เพิ่มบริการ';
            document.getElementById('cancelBtn').style.display = 'none';
        }
    </script>
</main>

<?php
include 'admin-layout/footer.php';
?>
