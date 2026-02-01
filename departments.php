<?php
/**
 * Departments Management Page
 * หน้าจัดการสำหนัก/กอง และฝ่ายงาน
 */

require_once 'config/database.php';

// Initialize tables if not exists
$create_departments_table = "
CREATE TABLE IF NOT EXISTS departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    parent_id INT,
    type ENUM('division', 'department') DEFAULT 'division',
    status ENUM('active', 'inactive') DEFAULT 'active',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES departments(id) ON DELETE SET NULL,
    INDEX (parent_id),
    INDEX (type),
    INDEX (status)
)";

if (!$conn->query($create_departments_table)) {
    die("Error creating table: " . $conn->error);
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $type = $_POST['type'] ?? 'department';

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'กรุณาป้อนชื่อหน่วยงาน']);
            exit;
        }

        $parent_id_sql = $parent_id ? $parent_id : 'NULL';
        $insert_query = "INSERT INTO departments (name, description, parent_id, type) 
                        VALUES (?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $conn->error]);
            exit;
        }

        $stmt->bind_param('ssii', $name, $description, $parent_id, $type);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'id' => $stmt->insert_id, 'message' => 'เพิ่มหน่วยงานสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        
        $delete_query = "DELETE FROM departments WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param('i', $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'ลบหน่วยงานสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }

    if ($_POST['action'] === 'update') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'กรุณาป้อนชื่อหน่วยงาน']);
            exit;
        }

        $update_query = "UPDATE departments SET name = ?, description = ? WHERE id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param('ssi', $name, $description, $id);
        
        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'อัปเดตสำเร็จ']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $stmt->error]);
        }
        $stmt->close();
        exit;
    }
}

// Function to get department tree recursively
function getDepartmentTree($conn, $parent_id = null) {
    $query = "SELECT * FROM departments WHERE parent_id " . ($parent_id ? "= " . intval($parent_id) : "IS NULL") . " ORDER BY sort_order, name";
    $result = $conn->query($query);
    
    if (!$result) {
        return [];
    }
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['children'] = getDepartmentTree($conn, $row['id']);
        $items[] = $row;
    }
    
    return $items;
}

// Get complete tree structure
$tree = getDepartmentTree($conn);

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการสำหนัก/กอง และฝ่ายงาน</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Sarabun', sans-serif;
        }

        .tree-container {
            position: relative;
        }

        .tree-item {
            margin-left: 0;
            transition: all 0.3s ease;
            position: relative;
        }

        /* Tree connecting lines */
        .tree-item.has-children > .tree-content {
            position: relative;
            border-left: 2px solid #d1d5db;
            margin-left: 1rem;
            padding-left: 1rem;
        }

        .tree-item.has-children > .tree-content.collapsed {
            border-left-color: #e5e7eb;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
        }

        .tree-item .department-item {
            position: relative;
        }

        /* Connecting line from parent */
        .tree-item .department-item::before {
            content: '';
            position: absolute;
            left: -1.5rem;
            top: 50%;
            width: 1rem;
            height: 2px;
            background-color: #d1d5db;
            transform: translateY(-50%);
        }

        .tree-item:first-child .department-item::before {
            display: none;
        }

        .expand-btn {
            cursor: pointer;
            user-select: none;
            display: inline-flex;
            align-items: center;
            width: 24px;
            height: 24px;
            justify-content: center;
            transition: transform 0.3s ease;
            font-size: 0.875rem;
            color: #6b7280;
        }

        .expand-btn:hover {
            color: #0d9488;
            transform: scale(1.2);
        }

        .expand-btn.collapsed {
            transform: rotate(0deg);
        }

        .expand-btn.expanded {
            transform: rotate(90deg);
        }

        .tree-content {
            display: block;
            animation: slideDown 0.3s ease;
            max-height: 10000px;
            overflow: visible;
            transition: all 0.3s ease;
        }

        .tree-content.collapsed {
            display: none;
            max-height: 0;
            overflow: hidden;
        }

        .tree-content.show {
            display: block;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 50;
            justify-content: center;
            align-items: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            max-width: 500px;
            width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .btn-primary {
            background: #0d9488;
            color: white;
        }

        .btn-primary:hover {
            background: #0f766e;
        }

        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .btn-secondary:hover {
            background: #e5e7eb;
        }

        .btn-danger {
            background: #ef4444;
            color: white;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .btn-add {
            background: #10b981;
            color: white;
            padding: 0.375rem 0.75rem;
            font-size: 0.8rem;
        }

        .btn-add:hover {
            background: #059669;
        }

        .add-btn-container {
            margin-top: 0.5rem;
            margin-left: 2rem;
            padding: 0.5rem;
        }

        .department-item {
            padding: 1rem;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            margin-bottom: 0.5rem;
        }

        .department-item.division {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            margin-top: 1rem;
            padding: 1.25rem;
        }

        .department-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .department-info h3 {
            margin: 0;
            color: #111827;
            font-weight: 600;
            font-size: 1.125rem;
        }

        .department-info p {
            margin: 0.25rem 0 0 0;
            color: #6b7280;
            font-size: 0.875rem;
        }

        .department-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .toast {
            position: fixed;
            bottom: 1rem;
            right: 1rem;
            background: #10b981;
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 0.375rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            animation: slideInRight 0.3s ease;
        }

        .toast.error {
            background: #ef4444;
        }

        @keyframes slideInRight {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        input, textarea, select {
            padding: 0.5rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-family: 'Sarabun', sans-serif;
            font-size: 1rem;
        }

        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #0d9488;
            box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
        }

        label {
            display: block;
            margin-top: 1rem;
            font-weight: 500;
            color: #374151;
        }

        label:first-child {
            margin-top: 0;
        }

        .form-group {
            margin-bottom: 1rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <div class="bg-white shadow-md sticky top-0 z-40">
        <div class="container mx-auto px-4 py-4">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-800">จัดการสำหนัก/กอง และฝ่ายงาน</h1>
                <button id="addDepartmentBtn" class="btn btn-primary" onclick="openAddModal(null, '')">
                    <span>➕</span> เพิ่มสำหนัก/กอง
                </button>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <?php if (empty($tree)): ?>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                <p class="text-blue-800">ยังไม่มีสำหนัก/กอง กรุณาเพิ่มข้อมูล</p>
            </div>
        <?php else: ?>
            <div class="tree-container">
                <?php 
                // Recursive function to render tree
                function renderTree($items, $level = 0) {
                    foreach ($items as $item):
                        $hasChildren = !empty($item['children']);
                        $isRoot = is_null($item['parent_id']);
                ?>
                        <div class="tree-item" style="margin-left: <?php echo ($level * 2); ?>rem;">
                            <div class="department-item <?php echo $isRoot ? 'division' : 'sub-department'; ?>">
                                <div class="department-header">
                                    <div style="display: flex; align-items: center; gap: 0.75rem; flex: 1;">
                                        <?php if ($hasChildren): ?>
                                            <span class="expand-btn" onclick="toggleChildren(this, 'tree-<?php echo $item['id']; ?>')">▶</span>
                                        <?php else: ?>
                                            <span class="expand-btn" style="visibility: hidden;"></span>
                                        <?php endif; ?>
                                        <div class="department-info" style="flex: 1;">
                                            <h3>
                                                <?php echo $isRoot ? '📁 ' : '└─ '; ?>
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </h3>
                                            <?php if ($item['description']): ?>
                                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="department-actions">
                                        <button class="btn btn-secondary" onclick="openEditModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', '<?php echo htmlspecialchars($item['description']); ?>')">✏️</button>
                                        <button class="btn btn-add" onclick="openAddModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>')">➕</button>
                                        <button class="btn btn-danger" onclick="deleteDepartment(<?php echo $item['id']; ?>)">🗑️</button>
                                    </div>
                                </div>
                            </div>

                            <?php if ($hasChildren): ?>
                                <div class="tree-content show" id="tree-<?php echo $item['id']; ?>">
                                    <?php renderTree($item['children'], $level + 1); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php 
                    endforeach;
                }
                
                renderTree($tree);
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add/Edit Modal -->
    <div id="modal" class="modal">
        <div class="modal-content">
            <h2 id="modalTitle" class="text-xl font-bold mb-4 text-gray-800">เพิ่มสำหนัก/กอง</h2>
            
            <form id="departmentForm" onsubmit="return false;">
                <div class="form-group">
                    <label for="departmentName">ชื่อหน่วยงาน *</label>
                    <input type="text" id="departmentName" name="name" placeholder="กรุณาป้อนชื่อหน่วยงาน" required>
                </div>

                <div class="form-group">
                    <label for="departmentDescription">รายละเอียด</label>
                    <textarea id="departmentDescription" name="description" placeholder="กรุณาป้อนรายละเอียด (ไม่บังคับ)" rows="3"></textarea>
                </div>

                <div id="parentInfo"></div>

                <input type="hidden" id="departmentId" name="id">
                <input type="hidden" id="parentId" name="parent_id">

                <div style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem;">
                    <button type="button" class="btn btn-secondary" id="closeModal">ยกเลิก</button>
                    <button type="button" class="btn btn-primary" onclick="saveDepartment()">บันทึก</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast" style="display: none;"></div>

    <script>
        // Toggle tree children with animation
        function toggleChildren(btn, treeId) {
            const content = document.getElementById(treeId);
            if (content) {
                const isCollapsed = content.classList.contains('collapsed');
                
                if (isCollapsed) {
                    // Expand
                    content.classList.remove('collapsed');
                    btn.classList.remove('collapsed');
                    btn.classList.add('expanded');
                } else {
                    // Collapse
                    content.classList.add('collapsed');
                    btn.classList.remove('expanded');
                    btn.classList.add('collapsed');
                }
            }
        }

        // Open modal for adding new department
        function openAddModal(parentId, parentName) {
            document.getElementById('modalTitle').textContent = 'เพิ่มสำหนัก/กอง';
            document.getElementById('departmentForm').reset();
            document.getElementById('departmentId').value = '';
            document.getElementById('parentId').value = parentId;
            document.getElementById('parentInfo').innerHTML = parentName ? `<small style="color: #666;">เป็นส่วนย่อยของ: <strong>${parentName}</strong></small>` : '';
            document.getElementById('modal').classList.add('show');
        }

        // Open modal for editing
        function openEditModal(id, name, description) {
            document.getElementById('modalTitle').textContent = 'แก้ไขสำหนัก/กอง';
            document.getElementById('departmentForm').reset();
            document.getElementById('departmentId').value = id;
            document.getElementById('departmentName').value = name;
            document.getElementById('departmentDescription').value = description;
            document.getElementById('parentId').value = '';
            document.getElementById('parentInfo').innerHTML = '';
            document.getElementById('modal').classList.add('show');
        }

        // Save department
        function saveDepartment() {
            const id = document.getElementById('departmentId').value;
            const name = document.getElementById('departmentName').value.trim();
            const description = document.getElementById('departmentDescription').value.trim();
            const parentId = document.getElementById('parentId').value || null;

            if (!name) {
                showToast('กรุณากรอกชื่อสำหนัก/กอง', 'error');
                return;
            }

            const data = new FormData();
            data.append('action', id ? 'update' : 'add');
            data.append('id', id);
            data.append('name', name);
            data.append('description', description);
            if (parentId) data.append('parent_id', parentId);

            fetch('departments.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast(id ? 'อัพเดตสำเร็จ' : 'เพิ่มสำหนัก/กองสำเร็จ', 'success');
                    document.getElementById('modal').classList.remove('show');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('เกิดข้อผิดพลาด', 'error');
            });
        }

        // Delete department
        function deleteDepartment(id) {
            if (!confirm('คุณแน่ใจหรือว่าต้องการลบสำหนัก/กองนี้?')) return;

            const data = new FormData();
            data.append('action', 'delete');
            data.append('id', id);

            fetch('departments.php', {
                method: 'POST',
                body: data
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    showToast('ลบสำหนัก/กองสำเร็จ', 'success');
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('เกิดข้อผิดพลาด: ' + result.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('เกิดข้อผิดพลาด', 'error');
            });
        }

        // Show toast notification
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            toast.textContent = message;
            toast.className = 'toast ' + (type === 'error' ? 'error' : type);
            toast.style.display = 'block';
            
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }

        // Modal controls
        document.getElementById('closeModal').onclick = () => {
            document.getElementById('modal').classList.remove('show');
        };

        window.onclick = (event) => {
            const modal = document.getElementById('modal');
            if (event.target === modal) {
                modal.classList.remove('show');
            }
        };

        // Add new department at top level
        document.getElementById('addDepartmentBtn')?.addEventListener('click', () => {
            openAddModal(null, '');
        });
    </script>
</body>
</html>
