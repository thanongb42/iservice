<?php
/**
 * Admin Create Job
 * Admin creates a service ticket on behalf of a user
 */

require_once '../config/database.php';
session_start();

// Admin check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$page_title    = 'สร้างงาน';
$current_page  = 'create_job';
$breadcrumb    = [
    ['label' => 'หน้าหลัก', 'icon' => 'fa-home', 'url' => 'admin_dashboard.php'],
    ['label' => 'สร้างงาน'],
];

// Fetch active services
$services_result = $conn->query("SELECT service_code, service_name, icon, color_code FROM my_service WHERE is_active = 1 ORDER BY display_order ASC");
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}

// Fetch Level-1 departments only (children are loaded via AJAX)
$depts_result = $conn->query("SELECT department_id, department_name, level_type FROM departments WHERE status = 'active' AND level = 1 ORDER BY department_name ASC");
$departments = [];
while ($row = $depts_result->fetch_assoc()) {
    $departments[] = $row;
}

// Fetch assignable users (roles with can_be_assigned = 1)
$assignable_result = $conn->query("
    SELECT DISTINCT u.user_id, u.username, u.first_name, u.last_name,
           p.prefix_name, d.department_name,
           GROUP_CONCAT(DISTINCT r.role_name ORDER BY r.display_order SEPARATOR ', ') as roles
    FROM users u
    LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
    LEFT JOIN departments d ON u.department_id = d.department_id
    JOIN user_roles ur ON u.user_id = ur.user_id AND ur.is_active = 1
    JOIN roles r ON ur.role_id = r.role_id AND r.is_active = 1 AND r.can_be_assigned = 1
    WHERE u.status = 'active'
    GROUP BY u.user_id
    ORDER BY u.first_name ASC
");
$assignable_users = [];
while ($row = $assignable_result->fetch_assoc()) {
    $assignable_users[] = $row;
}

include 'admin-layout/header.php';
include 'admin-layout/sidebar.php';
include 'admin-layout/topbar.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<main class="p-4 md:p-6 lg:p-8">
<div class="max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
            <i class="fas fa-plus-circle text-green-600"></i>
            สร้างงาน
        </h1>
        <p class="text-gray-500 text-sm mt-1">สร้างคำขอบริการแทนผู้ใช้งาน (Walk-in / โทรศัพท์ / ภายใน)</p>
    </div>

    <form id="createJobForm" enctype="multipart/form-data">

    <!-- Section 1: Service Card Selector -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <h2 class="text-base font-semibold text-gray-700 mb-4 flex items-center gap-2">
            <i class="fas fa-concierge-bell text-green-500"></i>
            เลือกประเภทบริการ <span class="text-red-500">*</span>
        </h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3" id="serviceCardGrid">
            <?php foreach ($services as $svc): ?>
            <?php
                $color = $svc['color_code'] ?? 'gray';
                $colorMap = [
                    'green'  => ['bg' => 'bg-green-50',  'border' => 'border-green-200',  'text' => 'text-green-700',  'icon' => 'text-green-500',  'active' => 'ring-green-500'],
                    'blue'   => ['bg' => 'bg-blue-50',   'border' => 'border-blue-200',   'text' => 'text-blue-700',   'icon' => 'text-blue-500',   'active' => 'ring-blue-500'],
                    'red'    => ['bg' => 'bg-red-50',    'border' => 'border-red-200',    'text' => 'text-red-700',    'icon' => 'text-red-500',    'active' => 'ring-red-500'],
                    'orange' => ['bg' => 'bg-orange-50', 'border' => 'border-orange-200', 'text' => 'text-orange-700', 'icon' => 'text-orange-500', 'active' => 'ring-orange-500'],
                    'purple' => ['bg' => 'bg-purple-50', 'border' => 'border-purple-200', 'text' => 'text-purple-700', 'icon' => 'text-purple-500', 'active' => 'ring-purple-500'],
                    'pink'   => ['bg' => 'bg-pink-50',   'border' => 'border-pink-200',   'text' => 'text-pink-700',   'icon' => 'text-pink-500',   'active' => 'ring-pink-500'],
                    'indigo' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'text' => 'text-indigo-700', 'icon' => 'text-indigo-500', 'active' => 'ring-indigo-500'],
                    'teal'   => ['bg' => 'bg-teal-50',   'border' => 'border-teal-200',   'text' => 'text-teal-700',   'icon' => 'text-teal-500',   'active' => 'ring-teal-500'],
                    'gray'   => ['bg' => 'bg-gray-50',   'border' => 'border-gray-200',   'text' => 'text-gray-700',   'icon' => 'text-gray-500',   'active' => 'ring-gray-500'],
                ];
                $c = $colorMap[$color] ?? $colorMap['gray'];
            ?>
            <button type="button"
                class="service-card flex flex-col items-center justify-center p-4 rounded-xl border-2 <?php echo $c['border']; ?> <?php echo $c['bg']; ?> hover:shadow-md transition-all duration-150 cursor-pointer group"
                data-code="<?php echo htmlspecialchars($svc['service_code']); ?>"
                data-name="<?php echo htmlspecialchars($svc['service_name']); ?>"
                data-icon="<?php echo htmlspecialchars($svc['icon']); ?>"
                data-color="<?php echo htmlspecialchars($color); ?>">
                <i class="<?php echo htmlspecialchars($svc['icon']); ?> text-2xl mb-2 <?php echo $c['icon']; ?> group-hover:scale-110 transition-transform"></i>
                <span class="text-xs font-medium text-center <?php echo $c['text']; ?> leading-tight"><?php echo htmlspecialchars($svc['service_name']); ?></span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Section 2: Main Form (hidden until service selected) -->
    <div id="formSection" class="hidden">

        <!-- Selected Service Banner -->
        <div id="selectedBanner" class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-xl px-5 py-3 mb-5">
            <i id="bannerIcon" class="text-xl text-green-600"></i>
            <div>
                <p class="text-xs text-green-600 font-medium uppercase tracking-wide">บริการที่เลือก</p>
                <p id="bannerName" class="font-semibold text-green-800 text-sm"></p>
            </div>
            <button type="button" onclick="clearService()" class="ml-auto text-green-400 hover:text-green-600 p-1 rounded-lg hover:bg-green-100">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            <!-- LEFT: Request Form -->
            <div class="lg:col-span-2 space-y-5">

                <!-- Requester Info Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-user text-blue-500"></i>
                        ข้อมูลผู้ขอรับบริการ
                    </h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">ชื่อ-นามสกุล <span class="text-red-500">*</span></label>
                            <input type="text" name="requester_name" id="requester_name" required
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none"
                                placeholder="กรอกชื่อ-นามสกุล">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">เบอร์โทรศัพท์</label>
                            <input type="tel" name="requester_phone"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none"
                                placeholder="0812345678">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">อีเมล</label>
                            <input type="email" name="requester_email"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none"
                                placeholder="email@rangsit.go.th">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ตำแหน่ง</label>
                            <input type="text" name="requester_position"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none"
                                placeholder="เจ้าหน้าที่...">
                        </div>
                        <!-- Cascading Department Selector (spans 2 cols) -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-2">
                                หน่วยงาน <span class="text-red-500">*</span>
                                <span class="text-gray-400 font-normal ml-1">— เลือกให้ลึกที่สุดเท่าที่ทราบ</span>
                            </label>
                            <!-- Hidden field that holds the final selected department_id -->
                            <input type="hidden" name="department_id" id="department_id_hidden" required>
                            <div id="deptCascade" class="space-y-2">
                                <!-- Level 1 (required) -->
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex-shrink-0">1</span>
                                    <select id="dept_L1"
                                        class="dept-level-select flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white"
                                        onchange="onDeptChange(1, this.value)"
                                        required>
                                        <option value="">-- เลือกสำนัก / กอง --</option>
                                        <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['department_name']); ?><?php if (!empty($dept['level_type'])): ?> <span class="text-gray-400">(<?php echo htmlspecialchars($dept['level_type']); ?>)</span><?php endif; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <!-- Levels 2, 3, 4 — injected by JS -->
                                <div id="dept_L2_wrap" class="hidden flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-green-100 text-green-600 text-xs font-bold flex-shrink-0">2</span>
                                    <select id="dept_L2"
                                        class="dept-level-select flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white"
                                        onchange="onDeptChange(2, this.value)">
                                    </select>
                                </div>
                                <div id="dept_L3_wrap" class="hidden flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-yellow-100 text-yellow-600 text-xs font-bold flex-shrink-0">3</span>
                                    <select id="dept_L3"
                                        class="dept-level-select flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white"
                                        onchange="onDeptChange(3, this.value)">
                                    </select>
                                </div>
                                <div id="dept_L4_wrap" class="hidden flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-orange-100 text-orange-600 text-xs font-bold flex-shrink-0">4</span>
                                    <select id="dept_L4"
                                        class="dept-level-select flex-1 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white"
                                        onchange="onDeptChange(4, this.value)">
                                    </select>
                                </div>
                            </div>
                            <!-- Breadcrumb of selected path -->
                            <div id="deptBreadcrumb" class="hidden mt-2 flex flex-wrap items-center gap-1 text-xs text-gray-500"></div>
                        </div>
                    </div>
                </div>

                <!-- Service-Specific Fields Card -->
                <div id="serviceSpecificCard" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 hidden">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-sliders-h text-purple-500"></i>
                        รายละเอียดเฉพาะบริการ
                    </h3>
                    <div id="serviceFormContainer">
                        <div class="flex justify-center py-4">
                            <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-500"></div>
                        </div>
                    </div>
                </div>

                <!-- Request Details Card -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-clipboard text-orange-500"></i>
                        รายละเอียดคำขอ
                    </h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">คำอธิบาย / รายละเอียดเพิ่มเติม</label>
                            <textarea name="description" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none resize-none"
                                placeholder="อธิบายรายละเอียดเพิ่มเติม..."></textarea>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">ระดับความสำคัญ</label>
                                <select name="priority"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white">
                                    <option value="low">ต่ำ (Low)</option>
                                    <option value="medium" selected>ปานกลาง (Medium)</option>
                                    <option value="high">สูง (High)</option>
                                    <option value="urgent">เร่งด่วน (Urgent)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">วันที่ต้องการให้แล้วเสร็จ</label>
                                <div class="relative">
                                    <input type="text" id="expected_completion_date" name="expected_completion_date" readonly
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white cursor-pointer"
                                        placeholder="เลือกวันที่">
                                    <i class="fas fa-calendar-alt absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">เอกสารแนบ</label>
                            <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                                class="w-full text-sm text-gray-500 border border-gray-300 rounded-lg px-3 py-2 file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            <p class="text-xs text-gray-400 mt-1">รองรับ: JPG, PNG, PDF, DOC, DOCX (สูงสุด 5MB/ไฟล์)</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Assignment Card -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 sticky top-6">
                    <h3 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                        <i class="fas fa-user-check text-green-500"></i>
                        มอบหมายงาน
                    </h3>

                    <!-- Toggle: assign immediately -->
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg mb-4">
                        <input type="checkbox" id="assignImmediately" name="assign_immediately" value="1"
                            class="w-4 h-4 rounded text-green-600 cursor-pointer"
                            onchange="toggleAssignForm(this.checked)">
                        <label for="assignImmediately" class="text-sm text-gray-700 cursor-pointer font-medium">มอบหมายงานทันที</label>
                    </div>

                    <div id="assignFormFields" class="space-y-4 opacity-40 pointer-events-none">
                        <!-- Staff selector -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">เจ้าหน้าที่ที่รับผิดชอบ</label>
                            <select name="assign_to" id="assign_to"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white">
                                <option value="">-- เลือกเจ้าหน้าที่ --</option>
                                <?php foreach ($assignable_users as $u): ?>
                                <option value="<?php echo $u['user_id']; ?>">
                                    <?php
                                    $full = trim(($u['prefix_name'] ?? '') . ' ' . ($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
                                    if (empty(trim($full))) $full = $u['username'];
                                    echo htmlspecialchars($full);
                                    if (!empty($u['roles'])) echo ' — ' . htmlspecialchars($u['roles']);
                                    ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Assignment priority -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">ลำดับความสำคัญงาน</label>
                            <select name="assign_priority"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white">
                                <option value="low">ต่ำ</option>
                                <option value="normal" selected>ปกติ</option>
                                <option value="high">สูง</option>
                                <option value="urgent">เร่งด่วน</option>
                            </select>
                        </div>

                        <!-- Due date for assignment -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">กำหนดส่งงาน</label>
                            <div class="relative">
                                <input type="text" id="assign_due_date" name="assign_due_date" readonly
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none bg-white cursor-pointer"
                                    placeholder="เลือกวันที่และเวลา">
                                <i class="fas fa-calendar-alt absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"></i>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">หมายเหตุการมอบหมาย</label>
                            <textarea name="assign_notes" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-green-400 focus:border-transparent outline-none resize-none"
                                placeholder="รายละเอียดเพิ่มเติมสำหรับเจ้าหน้าที่..."></textarea>
                        </div>
                    </div>

                    <div class="mt-5 pt-4 border-t border-gray-100 text-xs text-gray-400">
                        <i class="fas fa-info-circle mr-1"></i>
                        หากไม่ติ๊กมอบหมาย คำขอจะถูกสร้างในสถานะ "รอดำเนินการ"
                    </div>
                </div>
            </div>

        </div><!-- end grid -->

        <!-- Footer Buttons -->
        <div class="flex items-center justify-end gap-3 mt-6 pb-6">
            <a href="service_requests.php" class="px-5 py-2.5 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                <i class="fas fa-times mr-1"></i> ยกเลิก
            </a>
            <button type="submit" id="submitBtn"
                class="px-6 py-2.5 text-sm font-semibold text-white bg-green-600 rounded-lg hover:bg-green-700 transition flex items-center gap-2 shadow-sm">
                <i class="fas fa-plus-circle"></i>
                <span id="submitBtnText">สร้างงาน</span>
            </button>
        </div>

    </div><!-- end #formSection -->

    </form>

</div><!-- end max-w -->
</main>

<?php include 'admin-layout/footer.php'; ?>

<script>
let currentServiceCode = '';

// ── Service Card Selection ──────────────────────────────────────
document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('click', () => {
        selectService(card.dataset.code, card.dataset.name, card.dataset.icon);
    });
});

function selectService(code, name, icon) {
    currentServiceCode = code;

    // Highlight selected card
    document.querySelectorAll('.service-card').forEach(c => {
        c.classList.remove('ring-2', 'ring-offset-1', 'shadow-lg');
    });
    const selected = document.querySelector(`.service-card[data-code="${code}"]`);
    if (selected) {
        selected.classList.add('ring-2', 'ring-offset-1', 'shadow-lg');
        const color = selected.dataset.color || 'green';
        selected.classList.add('ring-' + color + '-500');
    }

    // Update banner
    document.getElementById('bannerIcon').className = icon + ' text-xl text-green-600';
    document.getElementById('bannerName').textContent = name;

    // Show form section
    document.getElementById('formSection').classList.remove('hidden');

    // Load service-specific fields
    loadServiceForm(code);

    // Scroll to form
    setTimeout(() => {
        document.getElementById('formSection').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);
}

function clearService() {
    currentServiceCode = '';
    document.querySelectorAll('.service-card').forEach(c => {
        c.classList.remove('ring-2', 'ring-offset-1', 'shadow-lg');
        // Remove any ring-*-500 classes
        c.className = c.className.replace(/ring-\S+-500/g, '');
    });
    document.getElementById('formSection').classList.add('hidden');
    document.getElementById('serviceFormContainer').innerHTML = '';
}

function loadServiceForm(code) {
    const container = document.getElementById('serviceFormContainer');
    const card = document.getElementById('serviceSpecificCard');

    container.innerHTML = '<div class="flex justify-center py-4"><div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-500"></div></div>';
    card.classList.remove('hidden');

    fetch('api/get_service_form.php?code=' + encodeURIComponent(code))
        .then(r => r.text())
        .then(html => {
            const trimmed = html.trim();
            if (trimmed.length === 0 || trimmed.includes('ไม่มีฟอร์มเพิ่มเติม')) {
                card.classList.add('hidden');
                container.innerHTML = '';
            } else {
                container.innerHTML = trimmed;
                card.classList.remove('hidden');
                // Re-run any inline scripts in loaded HTML
                container.querySelectorAll('script').forEach(oldScript => {
                    const newScript = document.createElement('script');
                    newScript.textContent = oldScript.textContent;
                    document.body.appendChild(newScript);
                    oldScript.remove();
                });
            }
        })
        .catch(() => {
            card.classList.add('hidden');
            container.innerHTML = '';
        });
}

// ── Cascading Department Selector ──────────────────────────────
const deptLevels = [1, 2, 3, 4];
// Track name of selected dept at each level for breadcrumb
const deptSelectedName = { 1: '', 2: '', 3: '', 4: '' };

function onDeptChange(level, value) {
    // Clear all levels below this one
    for (let l = level + 1; l <= 4; l++) {
        hideDeptLevel(l);
        deptSelectedName[l] = '';
    }

    if (!value) {
        // User cleared this level: bubble up to find the deepest non-empty level
        let deepest = '';
        for (let l = level - 1; l >= 1; l--) {
            const sel = document.getElementById('dept_L' + l);
            if (sel && sel.value) { deepest = sel.value; break; }
        }
        document.getElementById('department_id_hidden').value = deepest;
        updateDeptBreadcrumb(level - 1);
        return;
    }

    // Save name for breadcrumb
    const sel = document.getElementById('dept_L' + level);
    deptSelectedName[level] = sel.options[sel.selectedIndex]?.text?.trim() || '';

    // Set the hidden field to current deepest
    document.getElementById('department_id_hidden').value = value;
    updateDeptBreadcrumb(level);

    // Fetch children if there can be a next level
    if (level < 4) {
        fetch('api/get_dept_children.php?parent_id=' + encodeURIComponent(value))
            .then(r => r.json())
            .then(children => {
                if (children && children.length > 0) {
                    buildDeptLevel(level + 1, children);
                }
                // If no children, current level stays as final selection
            })
            .catch(() => {/* silent */});
    }
}

function buildDeptLevel(level, children) {
    const sel = document.getElementById('dept_L' + level);
    const wrap = document.getElementById('dept_L' + level + '_wrap');
    if (!sel || !wrap) return;

    // Build options
    const labelMap = { 2: 'ส่วน / ฝ่าย', 3: 'งาน / กลุ่ม', 4: 'หน่วย' };
    let html = '<option value="">-- ทั้งหมด (ใช้ระดับก่อนหน้า) --</option>';
    children.forEach(c => {
        const type = c.level_type ? ' (' + c.level_type + ')' : '';
        html += `<option value="${c.department_id}">${escHtml(c.department_name)}${escHtml(type)}</option>`;
    });
    sel.innerHTML = html;
    wrap.classList.remove('hidden');
}

function hideDeptLevel(level) {
    const sel = document.getElementById('dept_L' + level);
    const wrap = document.getElementById('dept_L' + level + '_wrap');
    if (sel) sel.innerHTML = '';
    if (wrap) wrap.classList.add('hidden');
}

function updateDeptBreadcrumb(upToLevel) {
    const bc = document.getElementById('deptBreadcrumb');
    const parts = [];
    for (let l = 1; l <= upToLevel; l++) {
        const name = deptSelectedName[l];
        if (name) parts.push(name);
    }
    if (parts.length === 0) { bc.classList.add('hidden'); return; }
    bc.innerHTML = '<i class="fas fa-map-marker-alt mr-1 text-green-500"></i>' +
        parts.map((p, i) => {
            const isLast = i === parts.length - 1;
            return isLast
                ? `<span class="font-medium text-gray-700">${escHtml(p)}</span>`
                : `<span>${escHtml(p)}</span><i class="fas fa-chevron-right mx-1 text-gray-300 text-xs"></i>`;
        }).join('');
    bc.classList.remove('hidden');
}

function escHtml(str) {
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

// ── Assignment toggle ───────────────────────────────────────────
function toggleAssignForm(checked) {
    const fields = document.getElementById('assignFormFields');
    if (checked) {
        fields.classList.remove('opacity-40', 'pointer-events-none');
    } else {
        fields.classList.add('opacity-40', 'pointer-events-none');
    }
}

// ── Form Submission ─────────────────────────────────────────────
document.getElementById('createJobForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    if (!currentServiceCode) {
        Swal.fire({ icon: 'warning', title: 'กรุณาเลือกประเภทบริการ', confirmButtonColor: '#16a34a' });
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    const submitText = document.getElementById('submitBtnText');
    submitBtn.disabled = true;
    submitText.textContent = 'กำลังสร้างงาน...';
    submitBtn.querySelector('.fa-plus-circle')?.classList.replace('fa-plus-circle', 'fa-spinner');
    submitBtn.querySelector('.fa-spinner')?.classList.add('animate-spin');

    try {
        const fd = new FormData(this);
        fd.set('service_code', currentServiceCode);

        // Only send assign fields if checkbox is ticked
        const assignCheck = document.getElementById('assignImmediately');
        if (!assignCheck.checked) {
            fd.delete('assign_to');
            fd.delete('assign_priority');
            fd.delete('assign_notes');
            fd.delete('assign_due_date');
        }

        const res = await fetch('api/create_job_api.php', { method: 'POST', body: fd });
        const data = await res.json();

        if (data.success) {
            await Swal.fire({
                icon: 'success',
                title: 'สร้างงานสำเร็จ!',
                html: 'รหัสคำขอ: <strong>' + data.request_code + '</strong>',
                confirmButtonColor: '#16a34a',
                confirmButtonText: 'ดูรายละเอียด'
            });
            window.location.href = 'request_detail.php?id=' + data.request_id;
        } else {
            Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: data.message, confirmButtonColor: '#16a34a' });
        }
    } catch (err) {
        Swal.fire({ icon: 'error', title: 'เกิดข้อผิดพลาด', text: 'ไม่สามารถเชื่อมต่อได้', confirmButtonColor: '#16a34a' });
    } finally {
        submitBtn.disabled = false;
        submitText.textContent = 'สร้างงาน';
        const spinner = submitBtn.querySelector('.fa-spinner');
        if (spinner) spinner.classList.replace('fa-spinner', 'fa-plus-circle'), spinner.classList.remove('animate-spin');
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
(function() {
    const thaiMonths = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

    // Shared formatDate: shows Buddhist year + Thai month abbreviation
    function thaiFormatDate(date, format) {
        const day   = date.getDate();
        const month = thaiMonths[date.getMonth()];
        const year  = date.getFullYear() + 543;
        if (format === 'datetime') {
            const hh = String(date.getHours()).padStart(2, '0');
            const mm = String(date.getMinutes()).padStart(2, '0');
            return `${day} ${month} ${year}  ${hh}:${mm} น.`;
        }
        return `${day} ${month} ${year}`;
    }

    // ── วันที่ต้องการให้แล้วเสร็จ (date only) ──────────────────
    flatpickr('#expected_completion_date', {
        locale: 'th',
        dateFormat: 'Y-m-d',        // stored value sent to server
        altInput: true,              // show Thai-formatted text to user
        altFormat: 'j M Y',         // placeholder format (overridden below)
        allowInput: false,
        monthSelectorType: 'static',
        formatDate: function(date, format) {
            if (format === 'j M Y') return thaiFormatDate(date, 'date');
            return flatpickr.formatDate(date, format);
        }
    });

    // ── กำหนดส่งงาน (date + time) ──────────────────────────────
    flatpickr('#assign_due_date', {
        locale: 'th',
        enableTime: true,
        time_24hr: true,
        dateFormat: 'Y-m-d H:i',    // stored value sent to server
        altInput: true,
        altFormat: 'j M Y H:i น.',  // placeholder format (overridden below)
        allowInput: false,
        monthSelectorType: 'static',
        formatDate: function(date, format) {
            if (format === 'j M Y H:i น.') return thaiFormatDate(date, 'datetime');
            return flatpickr.formatDate(date, format);
        }
    });
})();
</script>
