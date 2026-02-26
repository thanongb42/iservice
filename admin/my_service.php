<?php
/**
 * My Service Management Page
 * หน้าจัดการบริการต่างๆ - Modal CRUD with SweetAlert2
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

// Fetch all services (client-side view toggle + pagination)
$services = [];
$total_results = $conn->query("SELECT COUNT(*) as count FROM my_service")->fetch_assoc()['count'];
$result = $conn->query("SELECT * FROM my_service ORDER BY display_order ASC");
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}

// Fetch notification emails grouped by service_id
$service_emails = [];
$conn->query("CREATE TABLE IF NOT EXISTS `service_notification_emails` (
  `id` int(11) NOT NULL AUTO_INCREMENT, `service_id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL, `name` varchar(100) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1, `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`), UNIQUE KEY `unique_service_email` (`service_id`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$se_result = $conn->query("SELECT * FROM service_notification_emails ORDER BY name ASC, email ASC");
if ($se_result) {
    while ($row = $se_result->fetch_assoc()) {
        $service_emails[$row['service_id']][] = $row;
    }
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

<style>
    /* SweetAlert2 custom styling for service modal */
    .swal2-popup.svc-modal {
        width: 40em !important;
        max-width: 95vw;
    }
    .svc-form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 12px;
        text-align: left;
    }
    .svc-form-grid .full-width {
        grid-column: 1 / -1;
    }
    .svc-form-grid label {
        display: block;
        font-size: 0.8rem;
        font-weight: 600;
        color: #374151;
        margin-bottom: 4px;
    }
    .svc-form-grid input,
    .svc-form-grid select,
    .svc-form-grid textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 0.875rem;
        transition: border-color 0.15s;
        font-family: 'Sarabun', sans-serif;
    }
    .svc-form-grid input:focus,
    .svc-form-grid select:focus,
    .svc-form-grid textarea:focus {
        outline: none;
        border-color: #0d9488;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.1);
    }
    .svc-icon-preview {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 8px;
        color: white;
        font-size: 1.1rem;
        vertical-align: middle;
        margin-left: 8px;
    }

    /* View toggle */
    .view-toggle { display: inline-flex; border: 1px solid #e5e7eb; border-radius: 0.5rem; overflow: hidden; }
    .view-btn {
        padding: 0.375rem 0.75rem;
        background: white;
        color: #6b7280;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        transition: all 0.15s ease;
    }
    .view-btn + .view-btn { border-left: 1px solid #e5e7eb; }
    .view-btn.active { background: #0d9488; color: white; }
    .view-btn:hover:not(.active) { background: #f9fafb; }

    /* List view */
    #listView table { width: 100%; border-collapse: collapse; }
    #listView thead { background-color: #f9fafb; border-bottom: 2px solid #e5e7eb; }
    #listView th {
        padding: 0.875rem 1rem;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 600;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.05em;
    }
    #listView td { padding: 1rem; border-bottom: 1px solid #f3f4f6; font-size: 0.875rem; color: #374151; }
    #listView tbody tr:hover td { background: #f9fafb; }

    /* Pagination */
    .pag-bar { display:flex; align-items:center; justify-content:space-between; padding:0.75rem 1rem; border-top:1px solid #e5e7eb; flex-wrap:wrap; gap:0.5rem; }
    .pag-btn { min-width:2rem; height:2rem; padding:0 0.5rem; border:1px solid #e5e7eb; background:white; color:#374151; border-radius:0.375rem; cursor:pointer; font-size:0.8rem; display:inline-flex; align-items:center; justify-content:center; transition:all 0.15s; }
    .pag-btn:hover:not(:disabled) { background:#f3f4f6; }
    .pag-btn.active { background:#0d9488; border-color:#0d9488; color:white; font-weight:600; }
    .pag-btn:disabled { opacity:0.35; cursor:not-allowed; }
</style>

<!-- Full Width Service Cards -->
<div class="bg-white rounded-lg shadow-md p-6">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800">
            <i class="fas fa-briefcase text-teal-600"></i> รายการบริการทั้งหมด (<?= $total_results ?>)
        </h2>
        <div class="flex items-center gap-2">
            <!-- View Switcher -->
            <div class="view-toggle">
                <button class="view-btn active" id="btnGridView" onclick="switchView('grid')">
                    <i class="fas fa-th-large"></i> Grid
                </button>
                <button class="view-btn" id="btnListView" onclick="switchView('list')">
                    <i class="fas fa-list"></i> List
                </button>
            </div>
            <button onclick="openAddModal()" class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg text-sm transition">
                <i class="fas fa-plus mr-2"></i>เพิ่มบริการใหม่
            </button>
        </div>
    </div>

    <!-- Grid View -->
    <div id="gridView">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4" id="serviceList">
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
                            <button onclick="editService(<?= $service['id'] ?>)" class="text-blue-600 hover:text-blue-800" title="แก้ไข">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteService(<?= $service['id'] ?>)" class="text-red-600 hover:text-red-800" title="ลบ">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <h3 class="font-bold text-gray-800 mb-1"><?= htmlspecialchars($service['service_name']) ?></h3>
                    <p class="text-xs text-gray-500 mb-2 uppercase font-semibold"><?= htmlspecialchars($service['service_name_en']) ?></p>
                    <p class="text-sm text-gray-600 mb-3 line-clamp-2"><?= htmlspecialchars($service['description']) ?></p>
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-3">
                        <span class="<?= $colors['bg'] ?> text-white px-2 py-1 rounded"><?= $service['color_code'] ?></span>
                        <span>ลำดับ: <?= $service['display_order'] ?></span>
                    </div>
                    <!-- Notification Emails Section -->
                    <?php $emails = $service_emails[$service['id']] ?? []; ?>
                    <div class="border-t border-gray-200 pt-2 mt-1">
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-semibold text-gray-500">
                                <i class="fas fa-envelope text-gray-400 mr-1"></i> อีเมลแจ้งเตือน (<?= count($emails) ?>)
                            </span>
                            <button onclick="manageEmails(<?= $service['id'] ?>, <?= htmlspecialchars(json_encode($service['service_name'])) ?>)"
                                    class="text-xs text-teal-600 hover:text-teal-800 font-semibold">
                                <i class="fas fa-cog"></i> จัดการ
                            </button>
                        </div>
                        <?php if (!empty($emails)): ?>
                            <div class="flex flex-wrap gap-1">
                                <?php foreach (array_slice($emails, 0, 3) as $em): ?>
                                    <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full <?= !$em['is_active'] ? 'opacity-50 line-through' : '' ?>"
                                          title="<?= htmlspecialchars($em['name'] ?? '') ?>">
                                        <?= htmlspecialchars($em['email']) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($emails) > 3): ?>
                                    <span class="text-xs text-gray-400">+<?= count($emails) - 3 ?> อีก</span>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-gray-400">ยังไม่มีอีเมล</p>
                        <?php endif; ?>
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

    <!-- List View (client-side pagination) -->
    <div id="listView" style="display:none;" class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="w-full table-auto">
            <thead>
                <tr>
                    <th>ลำดับ</th>
                    <th class="text-center">Icon</th>
                    <th>รหัสบริการ</th>
                    <th>ชื่อบริการ</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($services as $idx => $service):
                    $colors = $color_map[$service['color_code']] ?? $color_map['blue'];
                ?>
                <tr class="list-row" data-index="<?= $idx ?>">
                    <td class="text-gray-400"><?= $service['display_order'] ?></td>
                    <td class="text-center">
                        <span class="w-8 h-8 <?= $colors['bg'] ?> rounded-lg inline-flex items-center justify-center text-white text-lg mx-auto">
                            <i class="<?= htmlspecialchars($service['icon']) ?>"></i>
                        </span>
                    </td>
                    <td class="font-mono"><?= htmlspecialchars($service['service_code']) ?></td>
                    <td>
                        <div class="font-semibold text-gray-900"><?= htmlspecialchars($service['service_name']) ?></div>
                        <div class="text-xs text-gray-500"><?= htmlspecialchars($service['service_name_en']) ?></div>
                    </td>
                    <td class="text-center">
                        <?php if ($service['is_active']): ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">เปิดใช้งาน</span>
                        <?php else: ?>
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">ปิด</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center whitespace-nowrap">
                        <button onclick="toggleActive(<?= $service['id'] ?>, <?= $service['is_active'] ? 0 : 1 ?>)" class="text-gray-400 hover:text-gray-600" title="เปิด/ปิด">
                            <?php if ($service['is_active']): ?>
                                <i class="fas fa-toggle-on text-green-500 text-xl"></i>
                            <?php else: ?>
                                <i class="fas fa-toggle-off text-red-500 text-xl"></i>
                            <?php endif; ?>
                        </button>
                        <button onclick="editService(<?= $service['id'] ?>)" class="text-blue-600 hover:text-blue-800 ml-2" title="แก้ไข">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteService(<?= $service['id'] ?>)" class="text-red-600 hover:text-red-800 ml-2" title="ลบ">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button onclick="manageEmails(<?= $service['id'] ?>, <?= htmlspecialchars(json_encode($service['service_name'])) ?>)" class="text-teal-600 hover:text-teal-800 ml-2" title="จัดการอีเมลแจ้งเตือน">
                            <i class="fas fa-envelope"></i>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($services)): ?>
                <tr><td colspan="6" class="text-center py-12 text-gray-400">
                    <i class="fas fa-inbox text-4xl mb-3 opacity-30 block"></i>ยังไม่มีบริการในระบบ
                </td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <div class="pag-bar">
            <span id="pagInfo" class="text-sm text-gray-500"></span>
            <div id="pagBtns" class="flex items-center gap-1 flex-wrap"></div>
        </div>
    </div>
</div>

<script>
    // ── View toggle ──────────────────────────────────────────────
    const ITEMS_PER_PAGE = 10;
    let currentPage = 1;

    function switchView(view) {
        const gridView = document.getElementById('gridView');
        const listView = document.getElementById('listView');
        const btnGrid  = document.getElementById('btnGridView');
        const btnList  = document.getElementById('btnListView');

        if (view === 'list') {
            gridView.style.display = 'none';
            listView.style.display = 'block';
            btnGrid.classList.remove('active');
            btnList.classList.add('active');
            renderPage(currentPage);
        } else {
            listView.style.display = 'none';
            gridView.style.display = 'block';
            btnList.classList.remove('active');
            btnGrid.classList.add('active');
        }
        localStorage.setItem('myServiceView', view);
    }

    function renderPage(page) {
        const rows = document.querySelectorAll('#listView .list-row');
        const total = rows.length;
        const totalPages = Math.ceil(total / ITEMS_PER_PAGE) || 1;
        currentPage = Math.max(1, Math.min(page, totalPages));

        const start = (currentPage - 1) * ITEMS_PER_PAGE;
        const end   = start + ITEMS_PER_PAGE;

        rows.forEach((row, i) => {
            row.style.display = (i >= start && i < end) ? '' : 'none';
        });

        document.getElementById('pagInfo').textContent =
            `แสดง ${start + 1}–${Math.min(end, total)} จาก ${total} รายการ`;

        const container = document.getElementById('pagBtns');
        container.innerHTML = '';

        const prev = document.createElement('button');
        prev.className = 'pag-btn';
        prev.innerHTML = '<i class="fas fa-chevron-left"></i>';
        prev.disabled = currentPage === 1;
        prev.onclick = () => renderPage(currentPage - 1);
        container.appendChild(prev);

        const startP = Math.max(1, currentPage - 2);
        const endP   = Math.min(totalPages, startP + 4);
        for (let p = startP; p <= endP; p++) {
            const btn = document.createElement('button');
            btn.className = 'pag-btn' + (p === currentPage ? ' active' : '');
            btn.textContent = p;
            btn.onclick = ((_p) => () => renderPage(_p))(p);
            container.appendChild(btn);
        }

        const next = document.createElement('button');
        next.className = 'pag-btn';
        next.innerHTML = '<i class="fas fa-chevron-right"></i>';
        next.disabled = currentPage === totalPages;
        next.onclick = () => renderPage(currentPage + 1);
        container.appendChild(next);
    }

    // Restore saved view preference on load
    (function() {
        const saved = localStorage.getItem('myServiceView');
        if (saved === 'list') switchView('list');
    })();

    console.log("Service management script loaded.");
    const colorMap = <?= json_encode($color_map) ?>;
    const colorOptions = Object.keys(colorMap).map(c =>
        `<option value="${c}">${c.charAt(0).toUpperCase() + c.slice(1)}</option>`
    ).join('');

    function escapeHtml(str) {
        if (!str) return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Build modal form HTML
    function buildFormHtml(data = {}) {
        const iconVal = escapeHtml(data.icon || 'fas fa-star');
        const colorVal = data.color_code || 'blue';
        const bgClass = colorMap[colorVal]?.bg || 'bg-blue-500';

        return `
        <div class="svc-form-grid">
            <div>
                <label>รหัสบริการ (CODE) <span class="text-red-500">*</span></label>
                <input type="text" id="swal-code" value="${escapeHtml(data.service_code || '')}" placeholder="EMAIL, IT_SUPPORT" style="text-transform:uppercase;" required>
                <p style="font-size:0.7rem; color:#9ca3af; margin-top:2px;">ภาษาอังกฤษ ใช้ _ แทนช่องว่าง</p>
            </div>
            <div>
                <label>ลำดับแสดงผล <span class="text-red-500">*</span></label>
                <input type="number" id="swal-order" value="${data.display_order ?? 0}" required>
            </div>
            <div>
                <label>ชื่อบริการ (TH) <span class="text-red-500">*</span></label>
                <input type="text" id="swal-name-th" value="${escapeHtml(data.service_name || '')}" placeholder="อีเมลเทศบาล" required>
            </div>
            <div>
                <label>ชื่อบริการ (EN) <span class="text-red-500">*</span></label>
                <input type="text" id="swal-name-en" value="${escapeHtml(data.service_name_en || '')}" placeholder="Email Service" required>
            </div>
            <div class="full-width">
                <label>คำอธิบาย</label>
                <textarea id="swal-desc" rows="2" placeholder="รายละเอียดบริการ...">${escapeHtml(data.description || '')}</textarea>
            </div>
            <div>
                <label>Icon (Font Awesome) <span class="text-red-500">*</span></label>
                <div style="display:flex; align-items:center; gap:8px;">
                    <input type="text" id="swal-icon" value="${iconVal}" placeholder="fas fa-envelope" style="flex:1;" oninput="document.getElementById('swal-icon-preview').innerHTML='<i class=\\''+this.value+'\\'></i>'" required>
                    <span id="swal-icon-preview" class="svc-icon-preview ${bgClass}"><i class="${iconVal}"></i></span>
                </div>
            </div>
            <div>
                <label>สี <span class="text-red-500">*</span></label>
                <select id="swal-color" onchange="var bg=colorMap[this.value]?.bg||'bg-blue-500';document.getElementById('swal-icon-preview').className='svc-icon-preview '+bg;" required>
                    ${colorOptions.replace(`value="${colorVal}"`, `value="${colorVal}" selected`)}
                </select>
            </div>
            <div>
                <label>URL</label>
                <input type="text" id="swal-url" value="${escapeHtml(data.service_url || '#')}" placeholder="service-email.php, #">
            </div>
            <div style="display:flex; align-items:end; padding-bottom:8px;">
                <label style="display:flex; align-items:center; gap:8px; cursor:pointer; margin:0;">
                    <input type="checkbox" id="swal-active" ${(data.is_active === undefined || data.is_active == 1) ? 'checked' : ''} style="width:auto;">
                    เปิดใช้งาน
                </label>
            </div>
        </div>`;
    }

    // Validate and collect form data
    function validateAndCollect(action, serviceId) {
        const code = (document.getElementById('swal-code').value || '').trim().toUpperCase();
        const nameTh = (document.getElementById('swal-name-th').value || '').trim();
        const nameEn = (document.getElementById('swal-name-en').value || '').trim();
        const icon = (document.getElementById('swal-icon').value || '').trim();

        if (!code) { Swal.showValidationMessage('กรุณากรอกรหัสบริการ'); return false; }
        if (!nameTh) { Swal.showValidationMessage('กรุณากรอกชื่อบริการ (TH)'); return false; }
        if (!nameEn) { Swal.showValidationMessage('กรุณากรอกชื่อบริการ (EN)'); return false; }
        if (!icon) { Swal.showValidationMessage('กรุณากรอก Icon'); return false; }

        return {
            action: action,
            id: serviceId || '',
            service_code: code,
            service_name: nameTh,
            service_name_en: nameEn,
            description: (document.getElementById('swal-desc').value || '').trim(),
            icon: icon,
            color_code: document.getElementById('swal-color').value,
            service_url: (document.getElementById('swal-url').value || '#').trim(),
            display_order: document.getElementById('swal-order').value || 0,
            is_active: document.getElementById('swal-active').checked ? 1 : 0
        };
    }

    // Submit service data
    async function submitService(data) {
        try {
            const formData = new FormData();
            for (const key in data) formData.append(key, data[key]);

            const response = await fetch('api/my_service_api.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: result.message, confirmButtonColor: '#0d9488' });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message, confirmButtonColor: '#0d9488' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
        }
    }

    // Open Add Modal
    function openAddModal() {
        Swal.fire({
            title: '<i class="fas fa-plus text-teal-600"></i> เพิ่มบริการใหม่',
            html: buildFormHtml(),
            customClass: { popup: 'svc-modal' },
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-plus mr-1"></i> เพิ่มบริการ',
            cancelButtonText: 'ยกเลิก',
            confirmButtonColor: '#0d9488',
            cancelButtonColor: '#6b7280',
            preConfirm: () => validateAndCollect('add', null)
        }).then(result => {
            if (result.isConfirmed && result.value) {
                submitService(result.value);
            }
        });
    }

    // Edit Service - open modal with pre-filled data
    async function editService(id) {
        console.log("editService called with ID:", id);
        try {
            const response = await fetch(`api/get_service.php?id=${id}`);
            const service = await response.json();
            if (!service) throw new Error('No data');

            Swal.fire({
                title: '<i class="fas fa-edit text-teal-600"></i> แก้ไขบริการ',
                html: buildFormHtml(service),
                customClass: { popup: 'svc-modal' },
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-save mr-1"></i> บันทึกการแก้ไข',
                cancelButtonText: 'ยกเลิก',
                confirmButtonColor: '#0d9488',
                cancelButtonColor: '#6b7280',
                preConfirm: () => validateAndCollect('update', service.id)
            }).then(result => {
                if (result.isConfirmed && result.value) {
                    submitService(result.value);
                }
            });
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้', confirmButtonColor: '#0d9488' });
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

                const response = await fetch('api/my_service_api.php', { method: 'POST', body: formData });
                const data = await response.json();

                if (data.success) {
                    await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: data.message, confirmButtonColor: '#0d9488' });
                    location.reload();
                } else {
                    Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message, confirmButtonColor: '#0d9488' });
                }
            } catch (error) {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
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

            const response = await fetch('api/my_service_api.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                await Swal.fire({ icon: 'success', title: 'สำเร็จ!', text: result.message, confirmButtonColor: '#0d9488', timer: 1500, showConfirmButton: false });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: result.message, confirmButtonColor: '#0d9488' });
            }
        } catch (error) {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้', confirmButtonColor: '#0d9488' });
        }
    }

    // ===== Manage Notification Emails =====
    async function manageEmails(serviceId, serviceName) {
        try {
            const res = await fetch(`api/service_emails_api.php?action=list&service_id=${serviceId}`);
            const result = await res.json();
            const emails = result.data || [];

            let listHtml = '';
            if (emails.length === 0) {
                listHtml = '<p class="text-sm text-gray-400 py-4 text-center">ยังไม่มีอีเมลแจ้งเตือน</p>';
            } else {
                listHtml = '<div style="max-height:250px; overflow-y:auto; text-align:left;">';
                emails.forEach(em => {
                    const activeClass = em.is_active == 1 ? '' : 'opacity-50';
                    const toggleIcon = em.is_active == 1 ? 'fa-toggle-on text-green-500' : 'fa-toggle-off text-red-400';
                    listHtml += `
                    <div class="flex items-center justify-between py-2 border-b border-gray-100 ${activeClass}" id="em-row-${em.id}">
                        <div>
                            <span class="text-sm font-medium text-gray-700">${escapeHtml(em.email)}</span>
                            ${em.name ? '<span class="text-xs text-gray-400 ml-1">(' + escapeHtml(em.name) + ')</span>' : ''}
                        </div>
                        <div class="flex gap-2">
                            <button onclick="swalToggleEmail(${em.id}, ${em.is_active == 1 ? 0 : 1}, ${serviceId})" class="text-gray-400 hover:text-gray-600">
                                <i class="fas ${toggleIcon} text-lg"></i>
                            </button>
                            <button onclick="swalDeleteEmail(${em.id}, ${serviceId})" class="text-red-400 hover:text-red-600">
                                <i class="fas fa-trash text-sm"></i>
                            </button>
                        </div>
                    </div>`;
                });
                listHtml += '</div>';
            }

            Swal.fire({
                title: `<i class="fas fa-envelope text-teal-600"></i> อีเมลแจ้งเตือน`,
                html: `
                    <p class="text-sm text-gray-600 mb-3">บริการ: <strong>${serviceName}</strong></p>
                    <div id="swal-email-list">${listHtml}</div>
                    <hr class="my-3">
                    <div style="text-align:left;">
                        <label style="font-size:0.8rem; font-weight:600; color:#374151;">เพิ่มอีเมลใหม่</label>
                        <div class="flex gap-2 mt-1">
                            <input type="email" id="swal-new-email" placeholder="user@example.com" style="flex:1; padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:0.85rem;">
                            <input type="text" id="swal-new-name" placeholder="ชื่อ (ไม่บังคับ)" style="width:120px; padding:6px 10px; border:1px solid #d1d5db; border-radius:6px; font-size:0.85rem;">
                            <button onclick="swalAddEmail(${serviceId}, '${serviceName.replace(/'/g, "\\'")}')" style="padding:6px 14px; background:#0d9488; color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.85rem;">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                `,
                width: '36em',
                showConfirmButton: false,
                showCloseButton: true,
            });
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถโหลดข้อมูลได้' });
        }
    }

    async function swalAddEmail(serviceId, serviceName) {
        const email = document.getElementById('swal-new-email').value.trim();
        const name = document.getElementById('swal-new-name').value.trim();

        if (!email) { Swal.showValidationMessage('กรุณากรอกอีเมล'); return; }
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email)) { Swal.showValidationMessage('รูปแบบอีเมลไม่ถูกต้อง'); return; }

        try {
            const formData = new FormData();
            formData.append('action', 'add');
            formData.append('service_id', serviceId);
            formData.append('email', email);
            formData.append('name', name);

            const res = await fetch('api/service_emails_api.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                Swal.close();
                await Swal.fire({ icon: 'success', title: 'สำเร็จ', text: data.message, timer: 1200, showConfirmButton: false });
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message }).then(() => {
                    manageEmails(serviceId, serviceName);
                });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
        }
    }

    async function swalDeleteEmail(id, serviceId) {
        try {
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', id);

            const res = await fetch('api/service_emails_api.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                const row = document.getElementById('em-row-' + id);
                if (row) row.remove();
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
        }
    }

    async function swalToggleEmail(id, isActive, serviceId) {
        try {
            const formData = new FormData();
            formData.append('action', 'toggle_active');
            formData.append('id', id);
            formData.append('is_active', isActive);

            const res = await fetch('api/service_emails_api.php', { method: 'POST', body: formData });
            const data = await res.json();

            if (data.success) {
                location.reload();
            } else {
                Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: data.message });
            }
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'ผิดพลาด', text: 'ไม่สามารถเชื่อมต่อเซิร์ฟเวอร์ได้' });
        }
    }
</script>

<?php include 'admin-layout/footer.php'; ?>
