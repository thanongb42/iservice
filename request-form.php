<?php
/**
 * Service Request Form
 * ฟอร์มส่งคำขอบริการ (รองรับทุกบริการ)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/nav_menu_loader.php';
require_once 'includes/email_helper.php'; // Add Email Helper

// Get service code from URL
$service_code = isset($_GET['service']) ? clean_input($_GET['service']) : '';

if (empty($service_code)) {
    header('Location: index.php');
    exit;
}

// Get service details
$stmt = $conn->prepare("SELECT * FROM my_service WHERE service_code = ? AND is_active = 1");
$stmt->bind_param("s", $service_code);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();

if (!$service) {
    header('Location: index.php');
    exit;
}

// Load nav menu
$nav_menus = get_menu_structure();
$nav_html = render_nav_menu($nav_menus);

$page_title = 'ยื่นคำขอ' . htmlspecialchars($service['service_name']);
$extra_styles = '
    .service-field { display: none; }
    .service-field.active { display: block; }
';
$extra_head_content = '
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
';

include __DIR__ . '/includes/header_public.php';
?>

    <!-- Form Section -->
    <section class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            
                        <form id="serviceRequestForm" enctype="multipart/form-data" class="bg-white rounded-xl shadow-lg p-8">

                <!-- Add Hidden Service Code -->
                <input type="hidden" name="service_code" value="<?= htmlspecialchars($service_code) ?>">

                <!-- Service Description -->
                <div class="bg-gradient-to-r from-<?= $service['color_code'] ?>-50 to-<?= $service['color_code'] ?>-100 rounded-lg p-6 mb-8">
                    <!-- Service Title with Icon -->
                    <div class="flex items-center mb-4">
                        <div class="p-3 bg-white rounded-lg shadow-sm mr-4">
                            <i class="<?= $service['icon'] ?> text-2xl text-<?= $service['color_code'] ?>-600"></i>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($service['service_name']) ?></h2>
                            <p class="text-sm text-gray-600"><?= htmlspecialchars($service['service_name_en']) ?></p>
                        </div>
                    </div>
                    
                    <p class="text-<?= $service['color_code'] ?>-800 bg-white/50 p-4 rounded-lg border border-<?= $service['color_code'] ?>-200 text-sm leading-relaxed">
                        <?= htmlspecialchars($service['description']) ?>
                    </p>
                </div>


                <!-- Common Fields -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">ข้อมูลผู้ขอ</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ชื่อ-นามสกุล <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="requester_name" required
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">อีเมล</label>
                            <input type="email" name="requester_email"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">เบอร์โทร</label>
                            <input type="tel" name="requester_phone"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <!-- 4 Level Department Cascade -->
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                แผนก/หน่วยงาน <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Level 1: สำนัก/กอง -->
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ระดับ 1: สำนัก/กอง</label>
                                    <select id="dept_level1" name="dept_level1" required
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                        <option value="">-- เลือกสำนัก/กอง --</option>
                                    </select>
                                </div>

                                <!-- Level 2: ส่วน -->
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ระดับ 2: ส่วน</label>
                                    <select id="dept_level2" name="dept_level2"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            disabled>
                                        <option value="">-- เลือกส่วน --</option>
                                    </select>
                                </div>

                                <!-- Level 3: ฝ่าย/กลุ่มงาน -->
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ระดับ 3: ฝ่าย/กลุ่มงาน</label>
                                    <select id="dept_level3" name="dept_level3"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            disabled>
                                        <option value="">-- เลือกฝ่าย/กลุ่มงาน --</option>
                                    </select>
                                </div>

                                <!-- Level 4: งาน -->
                                <div>
                                    <label class="block text-xs text-gray-600 mb-1">ระดับ 4: งาน</label>
                                    <select id="dept_level4" name="dept_level4"
                                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                            disabled>
                                        <option value="">-- เลือกงาน --</option>
                                    </select>
                                </div>
                            </div>
                            <input type="hidden" name="department" id="department_final">
                            <p class="text-xs text-gray-500 mt-2" id="dept_display"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ตำแหน่ง</label>
                            <input type="text" name="position"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

                        <?php if ($service_code !== 'EMAIL'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                ความสำคัญ <span class="text-red-500">*</span>
                            </label>
                            <select name="priority" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                                <option value="medium">ปานกลาง</option>
                                <option value="low">ต่ำ</option>
                                <option value="high">สูง</option>
                                <option value="urgent">เร่งด่วน</option>
                            </select>
                        </div>
                        <?php endif; ?>

                        <?php if ($service_code !== 'EMAIL' && $service_code !== 'MC'): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">วันที่ต้องการให้เสร็จ</label>
                            <input type="date" name="target_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Service-Specific Fields -->
                <div class="mb-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">รายละเอียดเพิ่มเติม</h3>

                    <!-- Include service-specific form fields based on service_code -->
                    <?php include "forms/service-form-fields-{$service_code}.php"; ?>

                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุเพิ่มเติม</label>
                        <textarea name="notes" rows="4"
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                                  placeholder="ระบุรายละเอียดเพิ่มเติม (ถ้ามี)"></textarea>
                    </div>

                    <div class="mt-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                             <i class="fas fa-paperclip mr-2 text-gray-500"></i>แนบเอกสารเพิ่มเติม (ถ้ามี)
                        </label>
                        <p class="text-xs text-gray-500 mb-2">รองรับไฟล์รูปภาพ (JPG, PNG) และเอกสาร (PDF, DOCX) สูงสุด 5 ไฟล์ (ขนาดรวมไม่เกิน 10MB) เช่น โครงการ, หนังสือเชิญ, กำหนดการ</p>
                        <input type="file" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
                               class="block w-full text-sm text-gray-500
                                file:mr-4 file:py-2 file:px-4
                                file:rounded-full file:border-0
                                file:text-sm file:font-semibold
                                file:bg-teal-50 file:text-teal-700
                                hover:file:bg-teal-100
                                border border-gray-300 rounded-lg cursor-pointer">
                    </div>
                </div>

                    <!-- CAPTCHA -->
                    <div class="mb-6 bg-white p-6 rounded-lg border border-gray-200 shadow-sm">
                        <label class="block text-sm font-bold text-gray-700 mb-3">
                             <i class="fas fa-shield-alt text-teal-600 mr-2"></i>ระบบรักษาความปลอดภัย <span class="text-red-500">*</span>
                        </label>
                        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                            <div class="flex items-center gap-2">
                                <div class="bg-gray-100 p-2 rounded-lg border border-gray-300 select-none">
                                    <img src="captcha.php" alt="CAPTCHA" id="captchaImage" class="h-12 w-32 object-cover rounded">
                                </div>
                                <button type="button" onclick="document.getElementById('captchaImage').src='captcha.php?'+Math.random();" 
                                        class="p-2 text-gray-500 hover:text-teal-600 hover:bg-teal-50 rounded-full transition-all" title="เปลี่ยนรหัส (Refresh)">
                                    <i class="fas fa-sync-alt text-lg"></i>
                                </button>
                            </div>
                            <input type="text" name="captcha" required placeholder="กรอกรหัสความปลอดภัย"
                                   class="flex-1 px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex items-center gap-4 pt-6 border-t border-gray-100">
                        <button type="submit" class="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-bold py-4 px-8 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                            <i class="fas fa-paper-plane mr-2"></i> ส่งคำขอ
                        </button>
                        <a href="index.php" class="px-6 py-4 rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200 font-semibold transition">
                            ยกเลิก
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </section>

    <!-- Scripts -->
    <?php include 'includes/footer_public.php'; ?>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const levels = [
                { el: document.getElementById('dept_level1'), label: '-- เลือกสำนัก/กอง --' },
                { el: document.getElementById('dept_level2'), label: '-- เลือกส่วน --' },
                { el: document.getElementById('dept_level3'), label: '-- เลือกฝ่าย/กลุ่มงาน --' },
                { el: document.getElementById('dept_level4'), label: '-- เลือกงาน --' }
            ];
            
            const finalInput = document.getElementById('department_final');
            const display = document.getElementById('dept_display');

            // Initialize Level 1
            loadDepartments(0, null);

            function loadDepartments(levelIndex, parentId) {
                if (levelIndex >= levels.length) return;
                
                const levelObj = levels[levelIndex];
                const select = levelObj.el;

                let url = `api/get_departments.php?level=${levelIndex + 1}`;
                if (parentId) {
                    url += `&parent_id=${parentId}`;
                }

                fetch(url)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data.length > 0) {
                            select.innerHTML = `<option value="">${levelObj.label}</option>`;
                            result.data.forEach(dept => {
                                const option = document.createElement('option');
                                option.value = dept.department_id;
                                option.textContent = dept.department_name;
                                select.appendChild(option);
                            });
                            select.disabled = false;
                        } else {
                            // If no data, keep it handled by the reset logic
                        }
                    })
                    .catch(err => console.error('Error loading departments:', err));
            }

            // Bind change events
            levels.forEach((obj, index) => {
                obj.el.addEventListener('change', function() {
                    const selectedId = this.value;
                    
                    // Reset lower levels
                    for(let i = index + 1; i < levels.length; i++) {
                        const lower = levels[i];
                        lower.el.innerHTML = `<option value="">${lower.el.getAttribute('data-placeholder') || lower.label}</option>`; // Use placeholder or default label
                        lower.el.disabled = true;
                        lower.el.value = '';
                    }

                    // Load next level if current one has value
                    if (selectedId && index < levels.length - 1) {
                        loadDepartments(index + 1, selectedId);
                    }

                    updateFinalValue();
                });
            });

            function updateFinalValue() {
                let lastSelectedId = '';
                let names = [];
                
                for (let obj of levels) {
                    if (obj.el.value) {
                        lastSelectedId = obj.el.value;
                        names.push(obj.el.options[obj.el.selectedIndex].text);
                    }
                }
                
                finalInput.value = lastSelectedId;
                display.textContent = names.length > 0 ? 'ที่เลือก: ' + names.join(' > ') : '';
            }
        });
        
        document.getElementById('serviceRequestForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            
            // Basic HTML5 validation already passed (due to 'required' attributes)
            // But let's check department hidden field manually
            if (!document.getElementById('department_final').value) {
                Swal.fire({
                    icon: 'warning',
                    title: 'กรุณาเลือกหน่วยงาน',
                    text: 'โปรดระบุหน่วยงานของท่านให้ครบถ้วน',
                     confirmButtonColor: '#fbbf24'
                });
                return;
            }

            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> กำลังส่งข้อมูล...';
            
            const formData = new FormData(form);
            
            fetch('api/process_request.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'สำเร็จ!',
                        text: data.message,
                        confirmButtonText: 'ตกลง',
                        confirmButtonColor: '#0d9488'
                    }).then((result) => {
                        if (result.isConfirmed) {
                             form.reset(); // Reset form
                             window.location.href = data.redirect_url;
                        }
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'เกิดข้อผิดพลาด',
                        text: data.message,
                        confirmButtonText: 'รับทราบ',
                        confirmButtonColor: '#ef4444'
                    });
                    // Refresh Captcha on error
                    document.getElementById('captchaImage').src = 'captcha.php?' + Math.random();
                    form.querySelector('input[name="captcha"]').value = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'System Error',
                    text: 'เกิดข้อผิดพลาดในการเชื่อมต่อกับเซิร์ฟเวอร์',
                    confirmButtonText: 'ปิด'
                });
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        });
    </script>
<?php $conn->close(); ?>
