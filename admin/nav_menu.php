<?php
/**
 * Nav Menu Management Page (AJAX + SweetAlert Modal)
 * หน้าจัดการเมนูนำทาง (CRUD with AJAX)
 */

// Include database config
require_once '../config/database.php';

// Start session
session_start();

$page_title = 'จัดการเมนูนำทาง';
$current_page = 'nav_menu';
$breadcrumb = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการเมนูนำทาง']
];

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // ADD/UPDATE menu
        if ($action === 'save') {
            $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : NULL;
            $menu_name = clean_input($_POST['menu_name'] ?? '');
            $menu_name_en = clean_input($_POST['menu_name_en'] ?? '');
            $menu_url = clean_input($_POST['menu_url'] ?? '');
            $menu_icon = clean_input($_POST['menu_icon'] ?? '');
            $menu_order = (int)($_POST['menu_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $target = clean_input($_POST['target'] ?? '_self');
            $description = clean_input($_POST['description'] ?? '');
            
            if (empty($menu_name) || empty($menu_url)) {
                echo json_encode(['success' => false, 'message' => 'ชื่อเมนูและ URL เป็นข้อมูลจำเป็น']);
                exit;
            }
            
            if ($id) {
                // UPDATE
                $sql = "UPDATE nav_menu SET parent_id=?, menu_name=?, menu_name_en=?, menu_url=?, menu_icon=?,
                        menu_order=?, is_active=?, target=?, description=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssiissi", $parent_id, $menu_name, $menu_name_en, $menu_url, $menu_icon, $menu_order, $is_active, $target, $description, $id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'แก้ไขเมนูสำเร็จ!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            } else {
                // INSERT
                $sql = "INSERT INTO nav_menu (parent_id, menu_name, menu_name_en, menu_url, menu_icon, menu_order, is_active, target, description)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issssiiss", $parent_id, $menu_name, $menu_name_en, $menu_url, $menu_icon, $menu_order, $is_active, $target, $description);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'เพิ่มเมนูสำเร็จ!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            }
            exit;
        }
        
        // GET menu data for edit
        if ($action === 'get') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("SELECT * FROM nav_menu WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $menu = $result->fetch_assoc();
            echo json_encode($menu);
            exit;
        }
        
        // DELETE menu
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            
            // Delete children first
            $stmt = $conn->prepare("DELETE FROM nav_menu WHERE parent_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            // Delete parent
            $stmt = $conn->prepare("DELETE FROM nav_menu WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'ลบเมนูสำเร็จ!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
            exit;
        }
        
        // TOGGLE active status
        if ($action === 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE nav_menu SET is_active = NOT is_active WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'เปลี่ยนสถานะสำเร็จ!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด']);
            }
            exit;
        }
    }
}

// Fetch all menus for display
$menus = [];
$result = $conn->query("SELECT * FROM nav_menu WHERE parent_id IS NULL ORDER BY menu_order ASC");
while ($row = $result->fetch_assoc()) {
    $menu_id = $row['id'];
    $row['children'] = [];
    
    $child_stmt = $conn->prepare("SELECT * FROM nav_menu WHERE parent_id = ? ORDER BY menu_order ASC");
    $child_stmt->bind_param("i", $menu_id);
    $child_stmt->execute();
    $child_result = $child_stmt->get_result();
    while ($child = $child_result->fetch_assoc()) {
        $row['children'][] = $child;
    }
    
    $menus[] = $row;
}

// Get parent menus for dropdown
$parent_menus = $conn->query("SELECT id, menu_name FROM nav_menu WHERE parent_id IS NULL ORDER BY menu_name ASC");
$parent_menu_array = [];
while ($p = $parent_menus->fetch_assoc()) {
    $parent_menu_array[] = $p;
}
?>
<?php
include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<main class="main-content-transition lg:ml-0">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Page Title -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-bars text-teal-600"></i> จัดการเมนูนำทาง
                </h1>
                <p class="mt-2 text-gray-600">เพิ่ม แก้ไข ลบ และจัดการลำดับเมนู</p>
            </div>
            <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2 transition">
                <i class="fas fa-plus"></i> เพิ่มเมนูใหม่
            </button>
        </div>

        <!-- Menu List Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ลำดับ</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">ชื่อเมนู</th>
                            <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">URL</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">สถานะ</th>
                            <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($menus as $menu): ?>
                            <!-- Parent Menu -->
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900"><?= $menu['menu_order'] ?></td>
                                <td class="px-6 py-4">
                                    <div class="font-semibold text-gray-800"><?= htmlspecialchars($menu['menu_name']) ?></div>
                                    <?php if ($menu['menu_name_en']): ?>
                                        <div class="text-xs text-gray-500"><?= htmlspecialchars($menu['menu_name_en']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($menu['menu_url']) ?></td>
                                <td class="px-6 py-4 text-center">
                                    <button onclick="toggleStatus(<?= $menu['id'] ?>)" class="inline-block cursor-pointer">
                                        <?php if ($menu['is_active']): ?>
                                            <span class="bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full font-semibold">
                                                <i class="fas fa-check"></i> เปิด
                                            </span>
                                        <?php else: ?>
                                            <span class="bg-red-100 text-red-800 text-xs px-3 py-1 rounded-full font-semibold">
                                                <i class="fas fa-times"></i> ปิด
                                            </span>
                                        <?php endif; ?>
                                    </button>
                                </td>
                                <td class="px-6 py-4 text-center space-x-2">
                                    <button onclick="openEditModal(<?= $menu['id'] ?>)" class="text-blue-600 hover:text-blue-800 transition">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteMenu(<?= $menu['id'] ?>)" class="text-red-600 hover:text-red-800 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Child Menus -->
                            <?php foreach ($menu['children'] as $child): ?>
                                <tr class="bg-gray-50 hover:bg-gray-100 transition">
                                    <td class="px-6 py-3 text-sm pl-12 text-gray-500"><?= $child['menu_order'] ?></td>
                                    <td class="px-6 py-3 pl-12">
                                        <i class="fas fa-level-up-alt fa-rotate-90 text-gray-400 mr-2"></i>
                                        <span class="text-gray-700"><?= htmlspecialchars($child['menu_name']) ?></span>
                                        <?php if ($child['menu_icon']): ?>
                                            <i class="<?= htmlspecialchars($child['menu_icon']) ?> text-gray-400 ml-2"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-3 text-sm text-gray-600"><?= htmlspecialchars($child['menu_url']) ?></td>
                                    <td class="px-6 py-3 text-center">
                                        <button onclick="toggleStatus(<?= $child['id'] ?>)" class="inline-block cursor-pointer">
                                            <?php if ($child['is_active']): ?>
                                                <span class="bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full">
                                                    <i class="fas fa-check"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-red-100 text-red-800 text-xs px-3 py-1 rounded-full">
                                                    <i class="fas fa-times"></i>
                                                </span>
                                            <?php endif; ?>
                                        </button>
                                    </td>
                                    <td class="px-6 py-3 text-center space-x-2">
                                        <button onclick="openEditModal(<?= $child['id'] ?>)" class="text-blue-600 hover:text-blue-800 transition">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="deleteMenu(<?= $child['id'] ?>)" class="text-red-600 hover:text-red-800 transition">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
const parentMenus = <?= json_encode($parent_menu_array) ?>;

function openAddModal() {
    const form = `
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Parent Menu (ถ้าเป็น Submenu)</label>
                <select id="parent_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="">-- ไม่มี (Parent Menu) --</option>
                    ${parentMenus.map(p => `<option value="${p.id}">${p.menu_name}</option>`).join('')}
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อเมนู (TH) *</label>
                <input type="text" id="menu_name" placeholder="เช่น: บริการ" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อเมนู (EN)</label>
                <input type="text" id="menu_name_en" placeholder="e.g. Services" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">URL *</label>
                <input type="text" id="menu_url" placeholder="#, page.php, https://..." class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Icon (Font Awesome)</label>
                <input type="text" id="menu_icon" placeholder="fas fa-home" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับ *</label>
                <input type="number" id="menu_order" value="0" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Target</label>
                <select id="target" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="_self">_self (หน้าเดิม)</option>
                    <option value="_blank">_blank (หน้าใหม่)</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                <textarea id="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="is_active" checked class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
            </div>
        </div>
    `;
    
    Swal.fire({
        title: 'เพิ่มเมนูใหม่',
        html: form,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#0d9488',
        width: '600px',
        didOpen: () => {
            document.getElementById('menu_name').focus();
        }
    }).then((result) => {
        if (result.isConfirmed) {
            saveMenu();
        }
    });
}

function openEditModal(id) {
    fetch('nav_menu.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=get&id=' + id
    })
    .then(r => r.json())
    .then(data => {
        const form = `
            <input type="hidden" id="edit_id" value="${data.id}">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Parent Menu</label>
                    <select id="parent_id" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <option value="">-- ไม่มี (Parent Menu) --</option>
                        ${parentMenus.map(p => `<option value="${p.id}" ${p.id == data.parent_id ? 'selected' : ''}>${p.menu_name}</option>`).join('')}
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อเมนู (TH) *</label>
                    <input type="text" id="menu_name" value="${data.menu_name}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อเมนู (EN)</label>
                    <input type="text" id="menu_name_en" value="${data.menu_name_en || ''}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">URL *</label>
                    <input type="text" id="menu_url" value="${data.menu_url}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Icon (Font Awesome)</label>
                    <input type="text" id="menu_icon" value="${data.menu_icon || ''}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับ *</label>
                    <input type="number" id="menu_order" value="${data.menu_order}" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Target</label>
                    <select id="target" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <option value="_self" ${data.target == '_self' ? 'selected' : ''}>_self (หน้าเดิม)</option>
                        <option value="_blank" ${data.target == '_blank' ? 'selected' : ''}>_blank (หน้าใหม่)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">คำอธิบาย</label>
                    <textarea id="description" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500 focus:border-transparent">${data.description || ''}</textarea>
                </div>
                <div class="flex items-center">
                    <input type="checkbox" id="is_active" ${data.is_active ? 'checked' : ''} class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                    <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
                </div>
            </div>
        `;
        
        Swal.fire({
            title: 'แก้ไขเมนู',
            html: form,
            icon: 'info',
            showCancelButton: true,
            confirmButtonText: 'บันทึก',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#0d9488',
            width: '600px',
            didOpen: () => {
                document.getElementById('menu_name').focus();
            }
        }).then((result) => {
            if (result.isConfirmed) {
                saveMenu(true);
            }
        });
    });
}

function saveMenu(isEdit = false) {
    const formData = new FormData();
    formData.append('action', 'save');
    
    if (isEdit) {
        formData.append('id', document.getElementById('edit_id').value);
    }
    
    formData.append('parent_id', document.getElementById('parent_id').value);
    formData.append('menu_name', document.getElementById('menu_name').value);
    formData.append('menu_name_en', document.getElementById('menu_name_en').value);
    formData.append('menu_url', document.getElementById('menu_url').value);
    formData.append('menu_icon', document.getElementById('menu_icon').value);
    formData.append('menu_order', document.getElementById('menu_order').value);
    formData.append('target', document.getElementById('target').value);
    formData.append('description', document.getElementById('description').value);
    formData.append('is_active', document.getElementById('is_active').checked ? 1 : 0);
    
    fetch('nav_menu.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'สำเร็จ!',
                text: data.message,
                icon: 'success',
                confirmButtonColor: '#0d9488'
            }).then(() => location.reload());
        } else {
            Swal.fire('ข้อผิดพลาด', data.message, 'error');
        }
    });
}

function deleteMenu(id) {
    Swal.fire({
        title: 'ต้องการลบเมนูนี้หรือไม่?',
        text: 'การลบจะเกี่ยวข้องกับเมนูย่อยทั้งหมด',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);
            
            fetch('nav_menu.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        title: 'สำเร็จ!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonColor: '#0d9488'
                    }).then(() => location.reload());
                } else {
                    Swal.fire('ข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

function toggleStatus(id) {
    const formData = new FormData();
    formData.append('action', 'toggle');
    formData.append('id', id);
    
    fetch('nav_menu.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                title: 'สำเร็จ!',
                text: data.message,
                icon: 'success',
                timer: 1500,
                showConfirmButton: false,
                confirmButtonColor: '#0d9488'
            }).then(() => location.reload());
        }
    });
}
</script>

<?php
include 'admin-layout/footer.php';
?>
