<?php
/**
 * Learning Resources Management Page (AJAX + SweetAlert Modal)
 * หน้าจัดการศูนย์รวมการเรียนรู้ (CRUD with AJAX Modal)
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

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // GET all resources
        if ($action === 'list') {
            $result = $conn->query("SELECT * FROM learning_resources ORDER BY display_order ASC, created_at DESC");
            $resources = [];
            while ($row = $result->fetch_assoc()) {
                $resources[] = $row;
            }
            echo json_encode(['success' => true, 'resources' => $resources]);
            exit;
        }
        
        // GET single resource for edit
        if ($action === 'get') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("SELECT * FROM learning_resources WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $resource = $result->fetch_assoc();
            echo json_encode($resource);
            exit;
        }
        
        // SAVE (add/update)
        if ($action === 'save') {
            $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;
            $title = clean_input($_POST['title'] ?? '');
            $description = clean_input($_POST['description'] ?? '');
            $resource_type = clean_input($_POST['resource_type'] ?? '');
            $resource_url = clean_input($_POST['resource_url'] ?? '');
            $display_order = (int)($_POST['display_order'] ?? 0);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $is_featured = isset($_POST['is_featured']) ? 1 : 0;
            $cover_image = clean_input($_POST['cover_image'] ?? '');
            
            if (empty($title) || empty($description) || empty($resource_type)) {
                echo json_encode(['success' => false, 'message' => 'กรุณากรอกข้อมูลที่จำเป็น']);
                exit;
            }
            
            if ($id) {
                // UPDATE
                $sql = "UPDATE learning_resources SET title=?, description=?, resource_type=?, resource_url=?, cover_image=?, display_order=?, is_active=?, is_featured=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssiiii", $title, $description, $resource_type, $resource_url, $cover_image, $display_order, $is_active, $is_featured, $id);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'แก้ไขทรัพยากรสำเร็จ!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            } else {
                // INSERT
                $sql = "INSERT INTO learning_resources (title, description, resource_type, resource_url, cover_image, display_order, is_active, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssiii", $title, $description, $resource_type, $resource_url, $cover_image, $display_order, $is_active, $is_featured);
                
                if ($stmt->execute()) {
                    echo json_encode(['success' => true, 'message' => 'เพิ่มทรัพยากรสำเร็จ!']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
                }
            }
            exit;
        }
        
        // DELETE
        if ($action === 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM learning_resources WHERE id = ?");
            $stmt->bind_param("i", $id);
            
            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'ลบทรัพยากรสำเร็จ!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $conn->error]);
            }
            exit;
        }
        
        // TOGGLE active/inactive
        if ($action === 'toggle') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("UPDATE learning_resources SET is_active = NOT is_active WHERE id = ?");
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

// Fetch all resources for display
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
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <i class="fas fa-graduation-cap text-teal-600"></i> จัดการศูนย์รวมการเรียนรู้
                </h1>
                <p class="mt-2 text-gray-600">เพิ่ม แก้ไข ลบ คู่มือ หลักสูตร Blog และทรัพยากรการเรียนรู้ต่างๆ</p>
            </div>
            <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-semibold transition flex items-center space-x-2">
                <i class="fas fa-plus"></i>
                <span>เพิ่มทรัพยากร</span>
            </button>
        </div>

        <!-- Resources Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="resourcesGrid">
            <!-- Loaded via AJAX -->
        </div>
    </div>
</main>

<!-- SweetAlert Modal Form -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const resourceTypes = ['pdf', 'video', 'podcast', 'blog', 'sourcecode', 'youtube', 'flipbook'];

// Load resources on page load
document.addEventListener('DOMContentLoaded', function() {
    loadResources();
});

function loadResources() {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'action=list'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            renderResources(data.resources);
        }
    })
    .catch(err => console.error('Error loading resources:', err));
}

function renderResources(resources) {
    const grid = document.getElementById('resourcesGrid');
    grid.innerHTML = '';
    
    const typeIcons = {
        'pdf': 'fa-file-pdf text-red-500',
        'video': 'fa-video text-blue-500',
        'podcast': 'fa-podcast text-purple-500',
        'blog': 'fa-blog text-green-500',
        'sourcecode': 'fa-code text-gray-700',
        'youtube': 'fa-youtube text-red-600',
        'flipbook': 'fa-book-open text-teal-500'
    };
    
    resources.forEach(resource => {
        const icon = typeIcons[resource.resource_type] || 'fa-file text-gray-500';
        const coverImg = resource.cover_image ? `/iservice/${resource.cover_image}` : 'https://via.placeholder.com/300x200?text=No+Image';
        
        const html = `
            <div class="border rounded-xl overflow-hidden hover:shadow-lg transition ${!resource.is_active ? 'opacity-50' : ''}">
                <div class="relative h-40 bg-gray-200 overflow-hidden">
                    <img src="${coverImg}" alt="${resource.title}" class="w-full h-full object-cover" 
                         onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                    
                    <div class="absolute top-2 left-2 bg-white/90 backdrop-blur-sm px-3 py-1 rounded-full text-xs font-semibold flex items-center space-x-1">
                        <i class="fas ${icon}"></i>
                        <span>${resource.resource_type.toUpperCase()}</span>
                    </div>
                    
                    ${resource.is_featured ? '<div class="absolute top-2 right-2 bg-yellow-400 p-2 rounded-full"><i class="fas fa-star text-white"></i></div>' : ''}
                </div>
                
                <div class="p-4">
                    <h3 class="font-bold text-gray-800 line-clamp-2">${resource.title}</h3>
                    <p class="text-sm text-gray-600 line-clamp-2 mt-2">${resource.description}</p>
                    
                    <div class="flex items-center justify-between mt-4 pt-3 border-t">
                        <div class="flex space-x-2">
                            <button onclick="editResource(${resource.id})" class="text-blue-600 hover:text-blue-800 transition" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteResource(${resource.id})" class="text-red-600 hover:text-red-800 transition" title="ลบ">
                                <i class="fas fa-trash"></i>
                            </button>
                            <button onclick="toggleResource(${resource.id}, ${resource.is_active})" class="text-gray-600 hover:text-gray-800 transition" title="เปลี่ยนสถานะ">
                                <i class="fas ${resource.is_active ? 'fa-eye' : 'fa-eye-slash'}"></i>
                            </button>
                        </div>
                        <span class="text-xs px-2 py-1 rounded-full ${resource.is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
                            ${resource.is_active ? 'เปิด' : 'ปิด'}
                        </span>
                    </div>
                </div>
            </div>
        `;
        grid.innerHTML += html;
    });
}

function openAddModal() {
    showResourceModal();
}

function editResource(id) {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=get&id=${id}`
    })
    .then(r => r.json())
    .then(resource => showResourceModal(resource))
    .catch(err => console.error('Error:', err));
}

function deleteResource(id) {
    Swal.fire({
        title: 'ลบทรัพยากร?',
        text: 'คุณแน่ใจหรือว่าต้องการลบทรัพยากรนี้?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก'
    }).then(result => {
        if (result.isConfirmed) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=delete&id=${id}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('ลบสำเร็จ!', '', 'success');
                    loadResources();
                } else {
                    Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
                }
            });
        }
    });
}

function toggleResource(id, currentStatus) {
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=toggle&id=${id}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            loadResources();
        }
    });
}

function showResourceModal(resource = null) {
    const isEdit = !!resource;
    const title = isEdit ? 'แก้ไขทรัพยากร' : 'เพิ่มทรัพยากรใหม่';
    
    const typeOptions = resourceTypes.map(type => 
        `<option value="${type}" ${isEdit && resource.resource_type === type ? 'selected' : ''}>${type.toUpperCase()}</option>`
    ).join('');
    
    const html = `
        <div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">ชื่อทรัพยากร *</label>
                <input type="text" id="modal_title" placeholder="ชื่อทรัพยากร" 
                       value="${isEdit ? resource.title : ''}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">รายละเอียด *</label>
                <textarea id="modal_description" placeholder="รายละเอียด" rows="3"
                          class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">${isEdit ? resource.description : ''}</textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">ประเภท *</label>
                <select id="modal_resource_type" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
                    ${typeOptions}
                </select>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">URL/Link</label>
                <input type="text" id="modal_resource_url" placeholder="https://..." 
                       value="${isEdit ? (resource.resource_url || '') : ''}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">ลำดับแสดง</label>
                <input type="number" id="modal_display_order" placeholder="0" 
                       value="${isEdit ? resource.display_order : 0}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">ภาพหน้าปก (URL)</label>
                <input type="text" id="modal_cover_image" placeholder="https://..." 
                       value="${isEdit ? (resource.cover_image || '') : ''}"
                       class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-teal-500">
            </div>
            
            <div class="mb-4 space-y-2">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="modal_is_active" ${isEdit && resource.is_active ? 'checked' : ''} class="rounded">
                    <span class="text-sm text-gray-700">เปิดใช้งาน</span>
                </label>
                <label class="flex items-center space-x-2">
                    <input type="checkbox" id="modal_is_featured" ${isEdit && resource.is_featured ? 'checked' : ''} class="rounded">
                    <span class="text-sm text-gray-700">ทรัพยากรแนะนำ</span>
                </label>
            </div>
        </div>
    `;
    
    Swal.fire({
        title: title,
        html: html,
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: isEdit ? 'บันทึก' : 'เพิ่ม',
        cancelButtonText: 'ยกเลิก',
        didOpen: () => {
            document.getElementById('modal_title').focus();
        }
    }).then(result => {
        if (result.isConfirmed) {
            saveResource(isEdit ? resource.id : null);
        }
    });
}

function saveResource(id = null) {
    const formData = new FormData();
    formData.append('action', 'save');
    if (id) formData.append('id', id);
    formData.append('title', document.getElementById('modal_title').value);
    formData.append('description', document.getElementById('modal_description').value);
    formData.append('resource_type', document.getElementById('modal_resource_type').value);
    formData.append('resource_url', document.getElementById('modal_resource_url').value);
    formData.append('display_order', document.getElementById('modal_display_order').value);
    formData.append('is_active', document.getElementById('modal_is_active').checked ? 1 : 0);
    formData.append('is_featured', document.getElementById('modal_is_featured').checked ? 1 : 0);
    formData.append('cover_image', document.getElementById('modal_cover_image').value);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            Swal.fire('สำเร็จ!', data.message, 'success');
            loadResources();
        } else {
            Swal.fire('เกิดข้อผิดพลาด', data.message, 'error');
        }
    })
    .catch(err => {
        Swal.fire('เกิดข้อผิดพลาด', err.message, 'error');
    });
}

// CSS for line-clamp
const style = document.createElement('style');
style.textContent = `
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
`;
document.head.appendChild(style);
</script>

<?php
include 'admin-layout/footer.php';
?>
