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

// Auto-fix: Normalize menu_order (1,2,3...) per group to prevent duplicates
function normalizeMenuOrder($conn) {
    // Fix parent menus (parent_id IS NULL)
    $result = $conn->query("SELECT id FROM nav_menu WHERE parent_id IS NULL ORDER BY menu_order ASC, id ASC");
    $stmt = $conn->prepare("UPDATE nav_menu SET menu_order = ? WHERE id = ?");
    $order = 1;
    while ($row = $result->fetch_assoc()) {
        $stmt->bind_param("ii", $order, $row['id']);
        $stmt->execute();
        $order++;
    }

    // Fix child menus per parent
    $parents = $conn->query("SELECT DISTINCT parent_id FROM nav_menu WHERE parent_id IS NOT NULL");
    while ($p = $parents->fetch_assoc()) {
        $pid = (int)$p['parent_id'];
        $children = $conn->prepare("SELECT id FROM nav_menu WHERE parent_id = ? ORDER BY menu_order ASC, id ASC");
        $children->bind_param("i", $pid);
        $children->execute();
        $childResult = $children->get_result();
        $childOrder = 1;
        while ($child = $childResult->fetch_assoc()) {
            $stmt->bind_param("ii", $childOrder, $child['id']);
            $stmt->execute();
            $childOrder++;
        }
    }
}

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
            $is_active = intval($_POST['is_active'] ?? 0);
            $target = clean_input($_POST['target'] ?? '_self');
            $description = clean_input($_POST['description'] ?? '');
            
            if (empty($menu_name) || empty($menu_url)) {
                echo json_encode(['success' => false, 'message' => 'ชื่อเมนูและ URL เป็นข้อมูลจำเป็น']);
                exit;
            }

            // Auto-calculate next menu_order for new items
            if (!$id) {
                if ($parent_id) {
                    $orderStmt = $conn->prepare("SELECT COALESCE(MAX(menu_order), 0) + 1 AS next_order FROM nav_menu WHERE parent_id = ?");
                    $orderStmt->bind_param("i", $parent_id);
                } else {
                    $orderStmt = $conn->prepare("SELECT COALESCE(MAX(menu_order), 0) + 1 AS next_order FROM nav_menu WHERE parent_id IS NULL");
                }
                $orderStmt->execute();
                $menu_order = $orderStmt->get_result()->fetch_assoc()['next_order'];
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
        
        // REORDER menus
        if ($action === 'reorder') {
            $ids = $_POST['ids'] ?? [];
            $parent_id = isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? (int)$_POST['parent_id'] : null;

            if (!is_array($ids) || empty($ids)) {
                echo json_encode(['success' => false, 'message' => 'ไม่มีข้อมูลลำดับ']);
                exit;
            }

            $stmt = $conn->prepare("UPDATE nav_menu SET menu_order = ? WHERE id = ?");
            $order = 1;
            foreach ($ids as $id) {
                $id = (int)$id;
                $stmt->bind_param("ii", $order, $id);
                $stmt->execute();
                $order++;
            }

            echo json_encode(['success' => true, 'message' => 'จัดลำดับเมนูสำเร็จ!']);
            exit;
        }

        // RESET ORDER - fix all duplicates
        if ($action === 'reset_order') {
            normalizeMenuOrder($conn);
            echo json_encode(['success' => true, 'message' => 'รีเซ็ตลำดับเมนูทั้งหมดสำเร็จ!']);
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

// Auto-fix duplicates on page load
normalizeMenuOrder($conn);

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
            <div class="flex gap-2">
                <button onclick="resetOrder()" class="bg-amber-500 hover:bg-amber-600 text-white px-4 py-3 rounded-lg font-semibold flex items-center gap-2 transition" title="รีเซ็ตลำดับเมนูทั้งหมดใหม่ (1,2,3...)">
                    <i class="fas fa-sort-numeric-down"></i> รีเซ็ตลำดับ
                </button>
                <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold flex items-center gap-2 transition">
                    <i class="fas fa-plus"></i> เพิ่มเมนูใหม่
                </button>
            </div>
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
                    <tbody class="divide-y divide-gray-200" id="parentMenuBody">
                        <?php foreach ($menus as $menu): ?>
                            <!-- Parent Menu -->
                            <tr class="hover:bg-gray-50 transition parent-row" data-id="<?= $menu['id'] ?>">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <span class="drag-handle cursor-grab text-gray-400 hover:text-gray-600 mr-2" title="ลากเพื่อจัดลำดับ"><i class="fas fa-grip-vertical"></i></span>
                                    <?= $menu['menu_order'] ?>
                                </td>
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
                                    <button onclick="openEditModal(<?= $menu['id'] ?>)" class="text-blue-600 hover:text-blue-800 transition" title="แก้ไข">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="deleteMenu(<?= $menu['id'] ?>)" class="text-red-600 hover:text-red-800 transition" title="ลบ">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>

                            <!-- Child Menus -->
                            <?php if (!empty($menu['children'])): ?>
                            <tr class="child-group-row" data-parent-id="<?= $menu['id'] ?>">
                                <td colspan="5" class="p-0">
                                    <table class="w-full">
                                        <tbody class="child-sortable divide-y divide-gray-100" data-parent-id="<?= $menu['id'] ?>">
                                            <?php foreach ($menu['children'] as $index => $child): ?>
                                                <tr class="bg-gray-50 hover:bg-gray-100 transition child-row" data-id="<?= $child['id'] ?>">
                                                    <td class="px-6 py-3 text-sm pl-12 text-gray-500" style="width:15%;">
                                                        <span class="drag-handle cursor-grab text-gray-400 hover:text-gray-600 mr-2" title="ลากเพื่อจัดลำดับ"><i class="fas fa-grip-vertical"></i></span>
                                                        <span class="inline-flex gap-1 mr-2">
                                                            <button onclick="moveChild(<?= $child['id'] ?>, <?= $menu['id'] ?>, 'up')" class="text-gray-400 hover:text-teal-600 transition <?= $index === 0 ? 'opacity-30 pointer-events-none' : '' ?>" title="เลื่อนขึ้น"><i class="fas fa-chevron-up text-xs"></i></button>
                                                            <button onclick="moveChild(<?= $child['id'] ?>, <?= $menu['id'] ?>, 'down')" class="text-gray-400 hover:text-teal-600 transition <?= $index === count($menu['children']) - 1 ? 'opacity-30 pointer-events-none' : '' ?>" title="เลื่อนลง"><i class="fas fa-chevron-down text-xs"></i></button>
                                                        </span>
                                                        <?= $child['menu_order'] ?>
                                                    </td>
                                                    <td class="px-6 py-3" style="width:30%;">
                                                        <i class="fas fa-level-up-alt fa-rotate-90 text-gray-400 mr-2"></i>
                                                        <span class="text-gray-700"><?= htmlspecialchars($child['menu_name']) ?></span>
                                                        <?php if ($child['menu_icon']): ?>
                                                            <i class="<?= htmlspecialchars($child['menu_icon']) ?> text-gray-400 ml-2"></i>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-6 py-3 text-sm text-gray-600" style="width:25%;"><?= htmlspecialchars($child['menu_url']) ?></td>
                                                    <td class="px-6 py-3 text-center" style="width:15%;">
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
                                                    <td class="px-6 py-3 text-center space-x-2" style="width:15%;">
                                                        <button onclick="openEditModal(<?= $child['id'] ?>)" class="text-blue-600 hover:text-blue-800 transition" title="แก้ไข">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button onclick="deleteMenu(<?= $child['id'] ?>)" class="text-red-600 hover:text-red-800 transition" title="ลบ">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>

<style>
.sortable-ghost { opacity: 0.4; background: #d1fae5 !important; }
.sortable-chosen { background: #ecfdf5 !important; }
.drag-handle:active { cursor: grabbing; }
</style>

<script>
const parentMenus = <?= json_encode($parent_menu_array) ?>;

function buildMenuForm(data = null) {
    const isEdit = data !== null;
    return `
        ${isEdit ? `<input type="hidden" id="edit_id" value="${data.id}">` : ''}
        <div class="space-y-3 text-left" style="max-height:65vh;overflow-y:auto;padding-right:4px;">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Parent Menu</label>
                <select id="parent_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="">-- ไม่มี (Parent Menu) --</option>
                    ${parentMenus.map(p => `<option value="${p.id}" ${isEdit && p.id == data.parent_id ? 'selected' : ''}>${p.menu_name}</option>`).join('')}
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">ชื่อเมนู (TH) *</label>
                    <input type="text" id="menu_name" value="${isEdit ? data.menu_name : ''}" placeholder="เช่น: บริการ" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">ชื่อเมนู (EN)</label>
                    <input type="text" id="menu_name_en" value="${isEdit ? (data.menu_name_en || '') : ''}" placeholder="e.g. Services" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">URL *</label>
                <input type="text" id="menu_url" value="${isEdit ? data.menu_url : ''}" placeholder="#, page.php, https://..." class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Icon</label>
                    <input type="text" id="menu_icon" value="${isEdit ? (data.menu_icon || '') : ''}" placeholder="fas fa-home" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">ลำดับ *</label>
                    <input type="number" id="menu_order" value="${isEdit ? data.menu_order : 0}" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Target</label>
                    <select id="target" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        <option value="_self" ${isEdit && data.target == '_self' ? 'selected' : ''}>_self</option>
                        <option value="_blank" ${isEdit && data.target == '_blank' ? 'selected' : ''}>_blank</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">คำอธิบาย</label>
                <textarea id="description" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent">${isEdit ? (data.description || '') : ''}</textarea>
            </div>
            <div class="flex items-center">
                <input type="checkbox" id="is_active" ${!isEdit || data.is_active ? 'checked' : ''} class="w-4 h-4 text-teal-600 border-gray-300 rounded focus:ring-teal-500">
                <label for="is_active" class="ml-2 text-sm font-medium text-gray-700">เปิดใช้งาน</label>
            </div>
        </div>
    `;
}

function collectFormData(isEdit) {
    const menuName = document.getElementById('menu_name').value;
    const menuUrl = document.getElementById('menu_url').value;

    if (!menuName || !menuUrl) {
        Swal.showValidationMessage('กรุณากรอกชื่อเมนูและ URL');
        return false;
    }

    const data = {
        parent_id: document.getElementById('parent_id').value,
        menu_name: menuName,
        menu_name_en: document.getElementById('menu_name_en').value,
        menu_url: menuUrl,
        menu_icon: document.getElementById('menu_icon').value,
        menu_order: document.getElementById('menu_order').value,
        target: document.getElementById('target').value,
        description: document.getElementById('description').value,
        is_active: document.getElementById('is_active').checked ? 1 : 0
    };

    if (isEdit) {
        data.id = document.getElementById('edit_id').value;
    }

    return data;
}

function saveMenu(formValues) {
    const formData = new FormData();
    formData.append('action', 'save');

    for (const [key, value] of Object.entries(formValues)) {
        formData.append(key, value);
    }

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
    })
    .catch(() => {
        Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error');
    });
}

function openAddModal() {
    Swal.fire({
        title: 'เพิ่มเมนูใหม่',
        html: buildMenuForm(),
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึก',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#0d9488',
        width: '520px',
        didOpen: () => {
            document.getElementById('menu_name').focus();
        },
        preConfirm: () => collectFormData(false)
    }).then((result) => {
        if (result.isConfirmed && result.value) {
            saveMenu(result.value);
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
        Swal.fire({
            title: 'แก้ไขเมนู',
            html: buildMenuForm(data),
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึก',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#0d9488',
            width: '520px',
            didOpen: () => {
                document.getElementById('menu_name').focus();
            },
            preConfirm: () => collectFormData(true)
        }).then((result) => {
            if (result.isConfirmed && result.value) {
                saveMenu(result.value);
            }
        });
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

function resetOrder() {
    Swal.fire({
        title: 'รีเซ็ตลำดับเมนูทั้งหมด?',
        text: 'ระบบจะจัดลำดับใหม่เป็น 1, 2, 3, ... ตามลำดับปัจจุบัน (แก้ลำดับซ้ำ)',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'รีเซ็ต',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#f59e0b'
    }).then((result) => {
        if (result.isConfirmed) {
            const formData = new FormData();
            formData.append('action', 'reset_order');
            fetch('nav_menu.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({ title: 'สำเร็จ!', text: data.message, icon: 'success', confirmButtonColor: '#0d9488' })
                            .then(() => location.reload());
                    } else {
                        Swal.fire('ข้อผิดพลาด', data.message, 'error');
                    }
                });
        }
    });
}

// --- Reorder functions ---
function saveOrder(ids, parentId) {
    const formData = new FormData();
    formData.append('action', 'reorder');
    if (parentId !== null && parentId !== undefined) {
        formData.append('parent_id', parentId);
    }
    ids.forEach(id => formData.append('ids[]', id));

    fetch('nav_menu.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire({
                    title: 'สำเร็จ!',
                    text: data.message,
                    icon: 'success',
                    timer: 1200,
                    showConfirmButton: false,
                    confirmButtonColor: '#0d9488'
                }).then(() => location.reload());
            } else {
                Swal.fire('ข้อผิดพลาด', data.message, 'error');
            }
        })
        .catch(() => Swal.fire('ข้อผิดพลาด', 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้', 'error'));
}

function moveChild(childId, parentId, direction) {
    const tbody = document.querySelector(`.child-sortable[data-parent-id="${parentId}"]`);
    if (!tbody) return;
    const rows = [...tbody.querySelectorAll('.child-row')];
    const idx = rows.findIndex(r => r.dataset.id == childId);
    if (idx === -1) return;

    if (direction === 'up' && idx > 0) {
        tbody.insertBefore(rows[idx], rows[idx - 1]);
    } else if (direction === 'down' && idx < rows.length - 1) {
        tbody.insertBefore(rows[idx + 1], rows[idx]);
    } else {
        return;
    }

    const newIds = [...tbody.querySelectorAll('.child-row')].map(r => r.dataset.id);
    saveOrder(newIds, parentId);
}

// Initialize SortableJS on parent menu tbody
document.addEventListener('DOMContentLoaded', function() {
    // Parent menus drag-and-drop
    const parentBody = document.getElementById('parentMenuBody');
    if (parentBody) {
        new Sortable(parentBody, {
            handle: '.parent-row .drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            draggable: '.parent-row',
            onEnd: function() {
                const ids = [...parentBody.querySelectorAll('.parent-row')].map(r => r.dataset.id);
                saveOrder(ids, null);
            }
        });
    }

    // Child menus drag-and-drop (each group)
    document.querySelectorAll('.child-sortable').forEach(tbody => {
        new Sortable(tbody, {
            handle: '.drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            draggable: '.child-row',
            onEnd: function() {
                const parentId = tbody.dataset.parentId;
                const ids = [...tbody.querySelectorAll('.child-row')].map(r => r.dataset.id);
                saveOrder(ids, parentId);
            }
        });
    });
});

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
