<?php
/**
 * Department Management Page
 * หน้าจัดการโครงสร้างหน่วยงาน (CRUD) - TreeView with Expand/Collapse + Connector Lines
 */

session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$user = [
    'username'   => $_SESSION['username'] ?? 'Admin',
    'email'      => $_SESSION['email'] ?? '',
    'full_name'  => $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Admin',
    'first_name' => $_SESSION['first_name'] ?? 'Admin'
];

// Fetch all departments
$departments = [];
$result = $conn->query("SELECT * FROM departments ORDER BY level ASC, department_name ASC");
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

// Build tree structure
function buildDepartmentTree($departments, $parent_id = null) {
    $tree = [];
    foreach ($departments as $dept) {
        if ($dept['parent_department_id'] == $parent_id) {
            $dept['children'] = buildDepartmentTree($departments, $dept['department_id']);
            $tree[] = $dept;
        }
    }
    return $tree;
}

$departmentTree = buildDepartmentTree($departments);

// Count total nodes
function countNodes($tree) {
    $count = 0;
    foreach ($tree as $node) {
        $count++;
        if (!empty($node['children'])) $count += countNodes($node['children']);
    }
    return $count;
}

$level_types = [
    1 => ['สำนัก', 'กอง', 'พิเศษ'],
    2 => ['ส่วน', 'ฝ่าย'],
    3 => ['ฝ่าย', 'กลุ่มงาน'],
    4 => ['งาน']
];

$page_title    = 'จัดการหน่วยงาน';
$current_page  = 'departments';
$breadcrumb    = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home'],
    ['label' => 'จัดการหน่วยงาน']
];

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>
<style>
/* ══ Tree Structure ═══════════════════════════════════════════════════════ */

.tree-root {
    list-style: none;
    padding: 0;
    margin: 0;
}

.tree-children {
    list-style: none;
    padding: 0;
    margin: 0;
}

/* Every node INSIDE a tree-children gets indent + connector lines */
.tree-children > .tree-node {
    position: relative;
    padding-left: 32px;
}

/* ─── Vertical line ─────────────────────────────────────────────────────── */
/* Non-last sibling: line goes full height (continues past children below) */
.tree-children > .tree-node:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e2e8f0;
    border-radius: 1px;
}
/* Last sibling: line only goes to mid-row (creates the └ corner) */
.tree-children > .tree-node:last-child::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    height: 24px;
    width: 2px;
    background: #e2e8f0;
    border-radius: 1px;
}

/* ─── Horizontal connector ───────────────────────────────────────────────── */
.tree-children > .tree-node::after {
    content: '';
    position: absolute;
    left: 10px;
    top: 22px;            /* vertical center of a ~44px row */
    width: 22px;          /* spans from vertical line to content */
    height: 2px;
    background: #e2e8f0;
    border-radius: 1px;
}

/* ══ Row ══════════════════════════════════════════════════════════════════ */
.tree-row {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 5px 8px;
    border-radius: 8px;
    min-height: 44px;
    transition: background 0.12s;
}
.tree-row:hover { background: #f8fafc; }

/* ── Toggle button ─────────────────────────────────────────────────────── */
.tree-toggle {
    width: 22px; height: 22px;
    flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
    border: none; background: transparent; cursor: pointer;
    border-radius: 4px; color: #94a3b8;
    transition: background 0.1s, color 0.1s;
}
.tree-toggle:hover { background: #e2e8f0; color: #475569; }
.tree-toggle i { transition: transform 0.2s ease; }
.tree-toggle.collapsed i { transform: rotate(-90deg); }

/* ── Leaf icon ─────────────────────────────────────────────────────────── */
.tree-leaf {
    width: 22px; height: 22px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center;
}
.tree-leaf i { font-size: 5px; color: #d1d5db; }

/* ── Node content ──────────────────────────────────────────────────────── */
.tree-node-content {
    flex: 1; min-width: 0;
    display: flex; align-items: center; flex-wrap: wrap; gap: 5px;
}
.dept-name  { font-weight: 600; color: #1e293b; font-size: 0.875rem; }
.dept-short { color: #64748b; font-size: 0.8rem; }
.dept-code  { color: #94a3b8; font-size: 0.72rem; font-family: 'Courier New', monospace;
              background: #f1f5f9; padding: 1px 5px; border-radius: 4px; }
.dept-child-count {
    font-size: 0.68rem; color: #94a3b8;
    background: #f1f5f9; padding: 1px 6px; border-radius: 10px;
}
.inactive-badge {
    font-size: 0.68rem; color: #dc2626;
    background: #fee2e2; padding: 1px 6px; border-radius: 10px;
}

/* ── Level & Type badges ───────────────────────────────────────────────── */
.level-badge {
    display: inline-block; padding: 1px 7px; border-radius: 4px;
    font-size: 10px; font-weight: 700; flex-shrink: 0;
}
.level-1 { background: #3b82f6; color: #fff; }
.level-2 { background: #10b981; color: #fff; }
.level-3 { background: #f59e0b; color: #fff; }
.level-4 { background: #8b5cf6; color: #fff; }
.type-badge {
    font-size: 0.7rem; color: #475569;
    background: #e2e8f0; padding: 1px 6px; border-radius: 10px;
}

/* ── Actions (visible on hover) ────────────────────────────────────────── */
.tree-actions {
    display: flex; align-items: center; gap: 1px;
    flex-shrink: 0; opacity: 0; transition: opacity 0.15s;
}
.tree-row:hover .tree-actions { opacity: 1; }
.tree-actions button {
    width: 28px; height: 28px; border: none; background: transparent;
    cursor: pointer; border-radius: 4px; font-size: 13px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.1s;
}
.tree-actions button:hover { background: #e2e8f0; }

/* ── Level colour accents on connector lines ───────────────────────────── */
/* Level 1's children get blue-tinted connectors */
.tree-root > .tree-node > .tree-children > .tree-node:not(:last-child)::before,
.tree-root > .tree-node > .tree-children > .tree-node:last-child::before,
.tree-root > .tree-node > .tree-children > .tree-node::after
{ background: #bfdbfe; }   /* blue-200 */

/* Level 2's children get green-tinted */
.tree-root > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node:not(:last-child)::before,
.tree-root > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node:last-child::before,
.tree-root > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node::after
{ background: #a7f3d0; }   /* emerald-200 */

/* Level 3's children get amber */
.tree-root > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node:not(:last-child)::before,
.tree-root > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node:last-child::before,
.tree-root > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node > .tree-children > .tree-node::after
{ background: #fde68a; }   /* amber-200 */

/* ── Collapsed animation ───────────────────────────────────────────────── */
.tree-children { overflow: hidden; }

/* ══ SweetAlert2 modal ════════════════════════════════════════════════════ */
.swal2-popup.dept-modal { width: 42em !important; max-width: 95vw; }
.dept-form-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 12px; text-align: left;
}
.dept-form-grid .full-width { grid-column: 1 / -1; }
.dept-form-grid label {
    display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;
}
.dept-form-grid input,
.dept-form-grid select {
    width: 100%; padding: 8px 12px; border: 1px solid #d1d5db;
    border-radius: 8px; font-size: 0.875rem; transition: border-color 0.15s;
}
.dept-form-grid input:focus,
.dept-form-grid select:focus {
    outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,.1);
}
.dept-form-grid .code-wrapper { position: relative; }
.dept-form-grid .code-check-icon { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); }
.dept-form-grid .code-msg { font-size: 0.7rem; margin-top: 2px; }

/* ── Search highlight ──────────────────────────────────────────────────── */
mark.search-hl { background: #fef08a; color: inherit; border-radius: 2px; padding: 0 2px; }
</style>

<!-- ── Page card ──────────────────────────────────────────────────────── -->
<div class="bg-white rounded-xl shadow p-6">

    <!-- Header toolbar -->
    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
        <div>
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-sitemap text-teal-600 mr-2"></i>โครงสร้างหน่วยงาน
            </h2>
            <p class="text-sm text-gray-400 mt-0.5">
                <?= count($departmentTree) ?> สำนัก/กอง &nbsp;·&nbsp; <?= count($departments) ?> หน่วยงานทั้งหมด
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Search -->
            <div class="relative">
                <input type="text" id="deptSearch" placeholder="ค้นหาชื่อ / รหัส..."
                       oninput="searchTree(this.value)"
                       class="pl-9 pr-9 py-2 text-sm border border-gray-300 rounded-lg w-52 focus:outline-none focus:border-teal-500 transition">
                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                <button id="clearSearch" onclick="clearSearch()" style="display:none;"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>
            <!-- Expand / Collapse -->
            <button onclick="expandAll()"
                    class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                <i class="fas fa-angle-double-down mr-1 text-xs"></i>ขยายทั้งหมด
            </button>
            <button onclick="collapseAll()"
                    class="px-3 py-2 text-sm bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition">
                <i class="fas fa-angle-double-up mr-1 text-xs"></i>ย่อทั้งหมด
            </button>
            <!-- Add -->
            <button onclick="openAddModal()"
                    class="px-4 py-2 text-sm bg-teal-600 hover:bg-teal-700 text-white rounded-lg transition">
                <i class="fas fa-plus mr-1"></i>เพิ่มหน่วยงาน
            </button>
        </div>
    </div>

    <!-- Legend -->
    <div class="flex flex-wrap items-center gap-3 mb-4 text-xs text-gray-500">
        <span><span class="level-badge level-1">Lv1</span> สำนัก / กอง</span>
        <span><span class="level-badge level-2">Lv2</span> ส่วน</span>
        <span><span class="level-badge level-3">Lv3</span> ฝ่าย / กลุ่มงาน</span>
        <span><span class="level-badge level-4">Lv4</span> งาน</span>
        <span class="ml-auto text-gray-400 italic">วางเมาส์บนแถวเพื่อแก้ไข</span>
    </div>

    <!-- Tree -->
    <div id="treeContainer" class="overflow-y-auto pr-1" style="max-height: 72vh;">
        <?php if (empty($departmentTree)): ?>
        <div class="text-center py-16 text-gray-400">
            <i class="fas fa-sitemap text-5xl block opacity-20 mb-4"></i>
            <p>ยังไม่มีหน่วยงานในระบบ</p>
        </div>
        <?php else: ?>
        <ul class="tree-root" id="deptTree">
            <?php
            /**
             * Render a single tree node recursively.
             * $expandDefault = true → children <ul> starts visible
             */
            function renderNode(array $dept, bool $expandDefault): void {
                $id          = $dept['department_id'];
                $hasChildren = !empty($dept['children']);
                $childCount  = count($dept['children'] ?? []);
                $isActive    = $dept['status'] === 'active';
                $lvl         = (int)$dept['level'];
            ?>
            <li class="tree-node" id="node-<?= $id ?>">
                <div class="tree-row" id="row-<?= $id ?>">

                    <!-- Toggle / Leaf -->
                    <?php if ($hasChildren): ?>
                    <button class="tree-toggle <?= !$expandDefault ? 'collapsed' : '' ?>"
                            id="toggle-<?= $id ?>"
                            onclick="toggleNode(<?= $id ?>)"
                            title="<?= $expandDefault ? 'ย่อ' : 'ขยาย' ?>">
                        <i class="fas fa-chevron-down text-xs"></i>
                    </button>
                    <?php else: ?>
                    <span class="tree-leaf"><i class="fas fa-circle"></i></span>
                    <?php endif; ?>

                    <!-- Content -->
                    <div class="tree-node-content">
                        <span class="level-badge level-<?= $lvl ?>">Lv<?= $lvl ?></span>

                        <?php if ($dept['level_type']): ?>
                        <span class="type-badge"><?= htmlspecialchars($dept['level_type']) ?></span>
                        <?php endif; ?>

                        <span class="dept-name"><?= htmlspecialchars($dept['department_name']) ?></span>

                        <?php if ($dept['short_name']): ?>
                        <span class="dept-short">(<?= htmlspecialchars($dept['short_name']) ?>)</span>
                        <?php endif; ?>

                        <span class="dept-code"><?= htmlspecialchars($dept['department_code']) ?></span>

                        <?php if ($hasChildren): ?>
                        <span class="dept-child-count"><i class="fas fa-layer-group mr-1 opacity-60"></i><?= $childCount ?></span>
                        <?php endif; ?>

                        <?php if (!$isActive): ?>
                        <span class="inactive-badge"><i class="fas fa-ban mr-1"></i>ปิดใช้งาน</span>
                        <?php endif; ?>
                    </div>

                    <!-- Action buttons -->
                    <div class="tree-actions">
                        <button onclick="toggleStatus(<?= $id ?>, '<?= $isActive ? 'inactive' : 'active' ?>')"
                                title="<?= $isActive ? 'ปิดการใช้งาน' : 'เปิดการใช้งาน' ?>">
                            <i class="fas fa-toggle-<?= $isActive ? 'on text-green-500' : 'off text-gray-400' ?> text-base"></i>
                        </button>
                        <button onclick="editDepartment(<?= $id ?>)" title="แก้ไข">
                            <i class="fas fa-edit text-blue-500"></i>
                        </button>
                        <button onclick="deleteDepartment(<?= $id ?>)" title="ลบ">
                            <i class="fas fa-trash text-red-400"></i>
                        </button>
                    </div>
                </div>

                <?php if ($hasChildren): ?>
                <ul class="tree-children" id="children-<?= $id ?>"
                    style="<?= !$expandDefault ? 'display:none;' : '' ?>">
                    <?php foreach ($dept['children'] as $child):
                        renderNode($child, false); // level 2+ collapsed by default
                    endforeach; ?>
                </ul>
                <?php endif; ?>
            </li>
            <?php
            } // end renderNode()

            foreach ($departmentTree as $rootDept) {
                renderNode($rootDept, true); // level 1 expanded by default
            }
            ?>
        </ul>
        <!-- Empty search result message -->
        <div id="emptySearch" class="hidden text-center py-10 text-gray-400">
            <i class="fas fa-search text-3xl block opacity-30 mb-3"></i>
            <p>ไม่พบหน่วยงานที่ค้นหา</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// ══ Data ══════════════════════════════════════════════════════════════════
const levelTypes      = <?= json_encode($level_types) ?>;
const allDepartments  = <?= json_encode($departments) ?>;
let   codeCheckTimeout = null;
let   isCodeAvailable  = false;

// Define valid child types based on parent's type
const parentChildTypeMap = {
    'สำนัก': ['ส่วน', 'ฝ่าย'],
    'กอง':   ['ฝ่าย'],
    'พิเศษ': ['ฝ่าย'],
    'ส่วน':  ['ฝ่าย'],
    'ฝ่าย':  ['งาน'],
    'กลุ่มงาน': ['งาน']
};

// ══ Tree Expand / Collapse ════════════════════════════════════════════════

function toggleNode(id) {
    const ul     = document.getElementById('children-' + id);
    const btn    = document.getElementById('toggle-' + id);
    if (!ul || !btn) return;

    const opening = ul.style.display === 'none';

    if (opening) {
        // Slide down
        ul.style.display = 'block';
        ul.style.overflow = 'hidden';
        ul.style.maxHeight = '0';
        requestAnimationFrame(() => {
            ul.style.transition = 'max-height 0.22s ease';
            ul.style.maxHeight  = ul.scrollHeight + 'px';
        });
        setTimeout(() => {
            ul.style.maxHeight = '';
            ul.style.overflow  = '';
            ul.style.transition = '';
        }, 230);
        btn.classList.remove('collapsed');
        btn.title = 'ย่อ';
    } else {
        // Slide up
        ul.style.overflow  = 'hidden';
        ul.style.maxHeight = ul.scrollHeight + 'px';
        requestAnimationFrame(() => {
            ul.style.transition = 'max-height 0.18s ease';
            ul.style.maxHeight  = '0';
        });
        setTimeout(() => {
            ul.style.display    = 'none';
            ul.style.maxHeight  = '';
            ul.style.overflow   = '';
            ul.style.transition = '';
        }, 190);
        btn.classList.add('collapsed');
        btn.title = 'ขยาย';
    }
}

function expandAll() {
    document.querySelectorAll('#deptTree .tree-children').forEach(ul => {
        ul.style.display    = 'block';
        ul.style.maxHeight  = '';
        ul.style.overflow   = '';
        ul.style.transition = '';
    });
    document.querySelectorAll('#deptTree .tree-toggle').forEach(btn => {
        btn.classList.remove('collapsed');
        btn.title = 'ย่อ';
    });
}

function collapseAll() {
    document.querySelectorAll('#deptTree .tree-children').forEach(ul => {
        ul.style.display    = 'none';
        ul.style.maxHeight  = '';
        ul.style.overflow   = '';
        ul.style.transition = '';
    });
    document.querySelectorAll('#deptTree .tree-toggle').forEach(btn => {
        btn.classList.add('collapsed');
        btn.title = 'ขยาย';
    });
}

// ══ Search ════════════════════════════════════════════════════════════════

function searchTree(query) {
    const q = query.trim().toLowerCase();
    const clearBtn = document.getElementById('clearSearch');
    clearBtn.style.display = q ? 'block' : 'none';

    if (!q) {
        restoreDefaultExpand();
        document.getElementById('emptySearch').classList.add('hidden');
        return;
    }

    // Recursively decide visibility; returns true if self or any descendant matches
    function processNode(nodeEl) {
        const id      = nodeEl.id.replace('node-', '');
        const content = (nodeEl.querySelector(':scope > .tree-row .tree-node-content')?.textContent || '').toLowerCase();
        const selfMatch = content.includes(q);

        const childUl = document.getElementById('children-' + id);
        let childMatch = false;
        if (childUl) {
            childUl.querySelectorAll(':scope > .tree-node').forEach(child => {
                if (processNode(child)) childMatch = true;
            });
            childUl.style.display    = (selfMatch || childMatch) ? 'block' : 'none';
            childUl.style.maxHeight  = '';
            childUl.style.overflow   = '';
            childUl.style.transition = '';
            const btn = document.getElementById('toggle-' + id);
            if (btn) {
                if (selfMatch || childMatch) btn.classList.remove('collapsed');
                else                         btn.classList.add('collapsed');
            }
        }

        const visible = selfMatch || childMatch;
        nodeEl.style.display = visible ? '' : 'none';
        return visible;
    }

    let anyVisible = false;
    document.querySelectorAll('#deptTree > .tree-node').forEach(n => {
        if (processNode(n)) anyVisible = true;
    });

    document.getElementById('emptySearch').classList.toggle('hidden', anyVisible);
}

function clearSearch() {
    document.getElementById('deptSearch').value = '';
    searchTree('');
}

function restoreDefaultExpand() {
    // Show all nodes
    document.querySelectorAll('#deptTree .tree-node').forEach(n => n.style.display = '');
    // Level-1 children visible, deeper collapsed
    document.querySelectorAll('#deptTree .tree-children').forEach(ul => {
        const isLv1Children = ul.parentElement?.parentElement?.id === 'deptTree';
        ul.style.display    = isLv1Children ? 'block' : 'none';
        ul.style.maxHeight  = '';
        ul.style.overflow   = '';
        ul.style.transition = '';
    });
    // Sync toggle states
    document.querySelectorAll('#deptTree .tree-toggle').forEach(btn => {
        const id = btn.id.replace('toggle-', '');
        const ul = document.getElementById('children-' + id);
        if (ul && ul.style.display !== 'none') {
            btn.classList.remove('collapsed');
            btn.title = 'ย่อ';
        } else {
            btn.classList.add('collapsed');
            btn.title = 'ขยาย';
        }
    });
}

// ══ Modal & CRUD (unchanged from original) ════════════════════════════════

function buildFormHtml(data = {}) {
    const isEdit = !!data.department_id;
    return `
    <div class="dept-form-grid">
        <div>
            <label>ระดับ <span class="text-red-500">*</span></label>
            <select id="swal-level" onchange="onLevelChange()" required>
                <option value="">-- เลือกระดับ --</option>
                <option value="1" ${data.level == 1 ? 'selected' : ''}>1 - สำนัก/กอง</option>
                <option value="2" ${data.level == 2 ? 'selected' : ''}>2 - ส่วน</option>
                <option value="3" ${data.level == 3 ? 'selected' : ''}>3 - ฝ่าย/กลุ่มงาน</option>
                <option value="4" ${data.level == 4 ? 'selected' : ''}>4 - งาน</option>
            </select>
        </div>
        <div id="swal-parent-container" style="display:${data.level > 1 ? 'block' : 'none'};">
            <label>หน่วยงานแม่ <span class="text-red-500">*</span></label>
            <select id="swal-parent-id" onchange="onParentChange()">
                <option value="">-- เลือกหน่วยงานแม่ --</option>
            </select>
            <p class="code-msg text-gray-500" id="swal-parent-hint"></p>
        </div>
        <div>
            <label>รหัสหน่วยงาน <span class="text-red-500">*</span></label>
            <div class="code-wrapper">
                <input type="text" id="swal-code" value="${data.department_code || ''}" placeholder="D001, 53603.1" style="text-transform:uppercase; padding-right:35px;" required>
                <span class="code-check-icon" id="swal-code-icon"></span>
            </div>
            <p class="code-msg" id="swal-code-msg"></p>
        </div>
        <div>
            <label>ชื่อหน่วยงาน <span class="text-red-500">*</span></label>
            <input type="text" id="swal-name" value="${escapeHtml(data.department_name || '')}" placeholder="สำนักปลัดเทศบาล" required>
        </div>
        <div>
            <label>ชื่อย่อ (สูงสุด 5 ตัว)</label>
            <input type="text" id="swal-short" value="${escapeHtml(data.short_name || '')}" maxlength="5" placeholder="สป" style="text-transform:uppercase;">
        </div>
        <div>
            <label>ประเภท</label>
            <select id="swal-level-type">
                <option value="">-- เลือกประเภท --</option>
            </select>
        </div>
        <div>
            <label>อาคาร</label>
            <input type="text" id="swal-building" value="${escapeHtml(data.building || '')}">
        </div>
        <div>
            <label>ชั้น</label>
            <input type="text" id="swal-floor" value="${escapeHtml(data.floor || '')}">
        </div>
        <div>
            <label>โทรศัพท์</label>
            <input type="tel" id="swal-phone" value="${escapeHtml(data.phone || '')}">
        </div>
        <div>
            <label>อีเมล</label>
            <input type="email" id="swal-email" value="${escapeHtml(data.email || '')}">
        </div>
        <div>
            <label>รหัสงบประมาณ</label>
            <input type="text" id="swal-budget" value="${escapeHtml(data.budget_code || '')}">
        </div>
        <div class="flex items-center" style="align-self:end; padding-bottom:8px;">
            <input type="checkbox" id="swal-status" ${(data.status || 'active') == 'active' ? 'checked' : ''} style="width:auto; margin-right:8px;">
            <label style="margin:0; cursor:pointer;" for="swal-status">เปิดใช้งาน</label>
        </div>
    </div>`;
}

function escapeHtml(str) {
    if (!str) return '';
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}

function openAddModal() {
    isCodeAvailable = false;
    Swal.fire({
        title: '<i class="fas fa-plus text-teal-600"></i> เพิ่มหน่วยงานใหม่',
        html: buildFormHtml(),
        customClass: { popup: 'dept-modal' },
        showCancelButton: true,
        confirmButtonText: '<i class="fas fa-plus mr-1"></i> เพิ่มหน่วยงาน',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#0d9488',
        cancelButtonColor: '#6b7280',
        didOpen: () => { setupCodeCheck(null); },
        preConfirm: () => validateAndCollect('add', null)
    }).then(result => {
        if (result.isConfirmed && result.value) submitDepartment(result.value);
    });
}

async function editDepartment(id) {
    try {
        const response = await fetch(`api/get_department.php?id=${id}`);
        const dept = await response.json();
        if (!dept) throw new Error('No data');
        isCodeAvailable = true;

        Swal.fire({
            title: '<i class="fas fa-edit text-teal-600"></i> แก้ไขหน่วยงาน',
            html: buildFormHtml(dept),
            customClass: { popup: 'dept-modal' },
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึกการแก้ไข',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#6b7280',
            didOpen: () => {
                populateParentOptions(dept.level, dept.parent_department_id);
                setTimeout(async () => {
                    await populateLevelTypeOptions(dept.parent_department_id, dept.level);
                    const ltSelect = document.getElementById('swal-level-type');
                    if (ltSelect && dept.level_type) ltSelect.value = dept.level_type;
                }, 150);
                setupCodeCheck(dept.department_id);
            },
            preConfirm: () => validateAndCollect('update', dept.department_id)
        }).then(result => {
            if (result.isConfirmed && result.value) submitDepartment(result.value);
        });
    } catch {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้', confirmButtonColor: '#0d9488' });
    }
}

function populateParentOptions(level, selectedParentId) {
    const parentSelect    = document.getElementById('swal-parent-id');
    const parentContainer = document.getElementById('swal-parent-container');
    const parentHint      = document.getElementById('swal-parent-hint');

    if (!level || parseInt(level) === 1) {
        parentContainer.style.display = 'none';
        return;
    }
    parentContainer.style.display = 'block';
    const eligible = allDepartments.filter(d => d.level < parseInt(level));
    parentSelect.innerHTML = '<option value="">-- เลือกหน่วยงานแม่ --</option>';
    eligible.forEach(d => {
        const indent = '　'.repeat(d.level - 1);
        const opt = new Option(indent + d.department_name + (d.short_name ? ` (${d.short_name})` : ''), d.department_id);
        opt.dataset.level = d.level;
        parentSelect.add(opt);
    });
    if (selectedParentId) parentSelect.value = selectedParentId;
    const levelNames = { 2: 'สำนัก/กอง (ระดับ 1)', 3: 'สำนัก/กอง หรือ ส่วน (ระดับ 1-2)', 4: 'สำนัก/กอง, ส่วน หรือ ฝ่าย (ระดับ 1-3)' };
    if (parentHint) parentHint.textContent = `เลือกได้จาก: ${levelNames[level] || ''}`;
}

async function populateLevelTypeOptions(parentId, level) {
    const ltSelect = document.getElementById('swal-level-type');
    if (!ltSelect) return;
    ltSelect.innerHTML = '<option value="">-- เลือกประเภท --</option>';
    if (parentId) {
        try {
            const res    = await fetch(`api/get_department.php?id=${parentId}`);
            const parent = await res.json();
            if (parent?.level_type) {
                const validTypes = parentChildTypeMap[parent.level_type];
                if (validTypes) { validTypes.forEach(t => ltSelect.add(new Option(t, t))); return; }
            }
        } catch { /* fallback */ }
    }
    if (levelTypes[level]) levelTypes[level].forEach(t => ltSelect.add(new Option(t, t)));
}

function onLevelChange() {
    const level = document.getElementById('swal-level').value;
    populateParentOptions(level, null);
    populateLevelTypeOptions(null, level);
}

async function onParentChange() {
    const parentId = document.getElementById('swal-parent-id').value;
    const level    = document.getElementById('swal-level').value;
    await populateLevelTypeOptions(parentId, level);
}

function setupCodeCheck(excludeId) {
    const codeInput = document.getElementById('swal-code');
    if (!codeInput) return;
    codeInput.addEventListener('input', function() {
        this.value = this.value.toUpperCase();
        clearTimeout(codeCheckTimeout);
        codeCheckTimeout = setTimeout(() => checkCode(excludeId), 500);
    });
}

async function checkCode(excludeId) {
    const codeInput = document.getElementById('swal-code');
    const code = (codeInput?.value || '').trim().toUpperCase();
    const icon = document.getElementById('swal-code-icon');
    const msg  = document.getElementById('swal-code-msg');

    if (!code) {
        if (icon) icon.innerHTML = '';
        if (msg)  { msg.textContent = ''; msg.className = 'code-msg'; }
        isCodeAvailable = false;
        return;
    }
    if (icon) icon.innerHTML = '<i class="fas fa-spinner fa-spin text-gray-400"></i>';
    if (msg)  { msg.textContent = 'กำลังตรวจสอบ...'; msg.className = 'code-msg text-gray-500'; }

    try {
        const url    = `api/check_department_code.php?code=${encodeURIComponent(code)}${excludeId ? '&exclude_id=' + excludeId : ''}`;
        const res    = await fetch(url);
        const result = await res.json();
        if (result.available) {
            if (icon) icon.innerHTML = '<i class="fas fa-check-circle text-green-500"></i>';
            if (msg)  { msg.textContent = result.message; msg.className = 'code-msg text-green-600'; }
            isCodeAvailable = true;
        } else {
            if (icon) icon.innerHTML = '<i class="fas fa-times-circle text-red-500"></i>';
            if (msg)  { msg.textContent = result.message; msg.className = 'code-msg text-red-600'; }
            isCodeAvailable = false;
        }
    } catch {
        if (icon) icon.innerHTML = '<i class="fas fa-exclamation-circle text-yellow-500"></i>';
        if (msg)  { msg.textContent = 'ไม่สามารถตรวจสอบได้'; msg.className = 'code-msg text-yellow-600'; }
        isCodeAvailable = false;
    }
}

function validateAndCollect(action, departmentId) {
    const level    = document.getElementById('swal-level').value;
    const parentId = document.getElementById('swal-parent-id').value;
    const code     = (document.getElementById('swal-code').value || '').trim().toUpperCase();
    const name     = (document.getElementById('swal-name').value || '').trim();

    if (!level)                                    { Swal.showValidationMessage('กรุณาเลือกระดับ'); return false; }
    if (parseInt(level) > 1 && !parentId)          { Swal.showValidationMessage('กรุณาเลือกหน่วยงานแม่'); return false; }
    if (!code)                                     { Swal.showValidationMessage('กรุณากรอกรหัสหน่วยงาน'); return false; }
    if (!name)                                     { Swal.showValidationMessage('กรุณากรอกชื่อหน่วยงาน'); return false; }
    if (action === 'add' && !isCodeAvailable)      { Swal.showValidationMessage('รหัสหน่วยงานนี้ถูกใช้งานแล้ว กรุณาใช้รหัสอื่น'); return false; }

    return {
        action: action,
        id: departmentId || '',
        level, parent_department_id: parseInt(level) === 1 ? '' : parentId,
        department_code: code,
        department_name: name,
        short_name:      (document.getElementById('swal-short').value || '').trim(),
        level_type:      document.getElementById('swal-level-type').value,
        building:        (document.getElementById('swal-building').value || '').trim(),
        floor:           (document.getElementById('swal-floor').value || '').trim(),
        phone:           (document.getElementById('swal-phone').value || '').trim(),
        email:           (document.getElementById('swal-email').value || '').trim(),
        budget_code:     (document.getElementById('swal-budget').value || '').trim(),
        status:          document.getElementById('swal-status').checked ? 'active' : 'inactive',
        manager_user_id: ''
    };
}

async function submitDepartment(data) {
    try {
        const formData = new FormData();
        for (const key in data) formData.append(key, data[key]);
        const response = await fetch('api/departments_api.php', { method: 'POST', body: formData });
        const result   = await response.json();
        if (result.success) {
            await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: result.message, confirmButtonColor: '#0d9488' });
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message, confirmButtonColor: '#0d9488' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
    }
}

async function deleteDepartment(id) {
    const result = await Swal.fire({
        icon: 'warning',
        title: 'ยืนยันการลบ',
        text: 'ต้องการลบหน่วยงานนี้หรือไม่? (ต้องลบหน่วยงานลูกก่อน)',
        showCancelButton: true,
        confirmButtonText: 'ลบ',
        cancelButtonText: 'ยกเลิก',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    if (!result.isConfirmed) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('id', id);
        const response = await fetch('api/departments_api.php', { method: 'POST', body: formData });
        const data     = await response.json();
        if (data.success) {
            await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message, confirmButtonColor: '#0d9488' });
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message, confirmButtonColor: '#0d9488' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
    }
}

async function toggleStatus(id, status) {
    try {
        const formData = new FormData();
        formData.append('action', 'toggle_status');
        formData.append('id', id);
        formData.append('status', status);
        const response = await fetch('api/departments_api.php', { method: 'POST', body: formData });
        const result   = await response.json();
        if (result.success) {
            await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: result.message, confirmButtonColor: '#0d9488', timer: 1500, showConfirmButton: false });
            location.reload();
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message, confirmButtonColor: '#0d9488' });
        }
    } catch {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
    }
}
</script>

<?php include 'admin-layout/footer.php'; ?>
