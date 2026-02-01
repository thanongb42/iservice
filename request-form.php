<?php
/**
 * Service Request Form
 * ฟอร์มส่งคำขอบริการ (รองรับทุกบริการ)
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/nav_menu_loader.php';

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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->begin_transaction();

    try {
        // Generate request code
        $year = date('Y');
        $last_request = $conn->query("SELECT request_code FROM service_requests WHERE request_code LIKE 'REQ-$year-%' ORDER BY id DESC LIMIT 1")->fetch_assoc();

        if ($last_request) {
            $last_num = intval(substr($last_request['request_code'], -4));
            $new_num = str_pad($last_num + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $new_num = '0001';
        }

        $request_code = "REQ-$year-$new_num";

        // Get department name from department_id
        $dept_id = intval($_POST['department']);
        $dept_query = $conn->prepare("SELECT department_name FROM departments WHERE department_id = ?");
        $dept_query->bind_param("i", $dept_id);
        $dept_query->execute();
        $dept_result = $dept_query->get_result()->fetch_assoc();
        $dept = $dept_result ? $dept_result['department_name'] : '';

        // Insert main request
        $stmt = $conn->prepare("INSERT INTO service_requests (request_code, service_code, requester_name, requester_email, requester_phone, department, position, priority, requested_date, target_date, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $name = clean_input($_POST['requester_name']);
        $email = clean_input($_POST['requester_email']);
        $phone = clean_input($_POST['requester_phone']);
        $position = clean_input($_POST['position']);
        $priority = clean_input($_POST['priority']);
        $requested_date = date('Y-m-d');
        $target_date = !empty($_POST['target_date']) ? clean_input($_POST['target_date']) : NULL;
        $notes = clean_input($_POST['notes']);

        $stmt->bind_param("sssssssssss", $request_code, $service_code, $name, $email, $phone, $dept, $position, $priority, $requested_date, $target_date, $notes);
        $stmt->execute();

        $request_id = $conn->insert_id;

        // Insert service-specific details
        switch ($service_code) {
            case 'EMAIL':
                $stmt = $conn->prepare("INSERT INTO request_email_details (request_id, requested_username, email_format, quota_mb, purpose, is_new_account, existing_email) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $username = clean_input($_POST['requested_username']);
                $email_format = clean_input($_POST['email_format']);
                $quota = 2048; // Default quota 2GB (ไม่ให้ผู้ใช้เลือก)
                $purpose = clean_input($_POST['purpose']);
                $is_new = intval($_POST['is_new_account']);
                $existing = clean_input($_POST['existing_email'] ?? '');
                $stmt->bind_param("issisis", $request_id, $username, $email_format, $quota, $purpose, $is_new, $existing);
                break;

            case 'NAS':
                $stmt = $conn->prepare("INSERT INTO request_nas_details (request_id, folder_name, storage_size_gb, permission_type, shared_with, purpose, backup_required) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $folder = clean_input($_POST['folder_name']);
                $size = intval($_POST['storage_size_gb']);
                $permission = clean_input($_POST['permission_type']);
                $shared = clean_input($_POST['shared_with']);
                $purpose = clean_input($_POST['purpose']);
                $backup = intval($_POST['backup_required']);
                $stmt->bind_param("sisssi", $request_id, $folder, $size, $permission, $shared, $purpose, $backup);
                break;

            case 'IT_SUPPORT':
                $stmt = $conn->prepare("INSERT INTO request_it_support_details (request_id, issue_type, device_type, device_brand, symptoms, location, urgency_level, error_message, when_occurred) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $issue_type = clean_input($_POST['issue_type']);
                $device_type = clean_input($_POST['device_type']);
                $device_brand = clean_input($_POST['device_brand']);
                $symptoms = clean_input($_POST['symptoms']);
                $location = clean_input($_POST['location']);
                $urgency = clean_input($_POST['urgency_level']);
                $error_msg = clean_input($_POST['error_message'] ?? '');
                $when = clean_input($_POST['when_occurred']);
                $stmt->bind_param("issssssss", $request_id, $issue_type, $device_type, $device_brand, $symptoms, $location, $urgency, $error_msg, $when);
                break;

            case 'INTERNET':
                $stmt = $conn->prepare("INSERT INTO request_internet_details (request_id, request_type, location, building, room_number, number_of_users, required_speed, current_issue) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $req_type = clean_input($_POST['request_type']);
                $location = clean_input($_POST['location']);
                $building = clean_input($_POST['building']);
                $room = clean_input($_POST['room_number']);
                $users = intval($_POST['number_of_users']);
                $speed = clean_input($_POST['required_speed']);
                $issue = clean_input($_POST['current_issue'] ?? '');
                $stmt->bind_param("isssiss", $request_id, $req_type, $location, $building, $room, $users, $speed, $issue);
                break;

            case 'QR_CODE':
                $stmt = $conn->prepare("INSERT INTO request_qrcode_details (request_id, qr_type, qr_content, qr_size, color_primary, color_background, logo_url, output_format, quantity, purpose) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $qr_type = clean_input($_POST['qr_type']);
                $content = clean_input($_POST['qr_content']);
                $size = clean_input($_POST['qr_size']);
                $color1 = clean_input($_POST['color_primary']);
                $color2 = clean_input($_POST['color_background']);
                $logo = clean_input($_POST['logo_url'] ?? '');
                $format = clean_input($_POST['output_format']);
                $qty = intval($_POST['quantity']);
                $purpose = clean_input($_POST['purpose'] ?? '');
                $stmt->bind_param("issssssis", $request_id, $qr_type, $content, $size, $color1, $color2, $logo, $format, $qty, $purpose);
                break;

            case 'PHOTOGRAPHY':
                $stmt = $conn->prepare("INSERT INTO request_photography_details (request_id, event_name, event_type, event_date, event_time_start, event_time_end, event_location, number_of_photographers, video_required, drone_required, delivery_format, special_requirements) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $event_name = clean_input($_POST['event_name']);
                $event_type = clean_input($_POST['event_type']);
                $event_date = clean_input($_POST['event_date']);
                $time_start = clean_input($_POST['event_time_start']);
                $time_end = clean_input($_POST['event_time_end']);
                $event_loc = clean_input($_POST['event_location']);
                $photographers = intval($_POST['number_of_photographers']);
                $video = intval($_POST['video_required']);
                $drone = intval($_POST['drone_required']);
                $delivery = clean_input($_POST['delivery_format']);
                $special = clean_input($_POST['special_requirements'] ?? '');
                $stmt->bind_param("isssssiiiss", $request_id, $event_name, $event_type, $event_date, $time_start, $time_end, $event_loc, $photographers, $video, $drone, $delivery, $special);
                break;

            case 'WEB_DESIGN':
                $stmt = $conn->prepare("INSERT INTO request_webdesign_details (request_id, website_type, project_name, purpose, target_audience, number_of_pages, features_required, has_existing_site, existing_url, domain_name, hosting_required, reference_sites, color_preferences, budget) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $web_type = clean_input($_POST['website_type']);
                $proj_name = clean_input($_POST['project_name']);
                $purpose = clean_input($_POST['purpose']);
                $audience = clean_input($_POST['target_audience']);
                $pages = intval($_POST['number_of_pages']);
                $features = clean_input($_POST['features_required'] ?? '');
                $has_site = intval($_POST['has_existing_site']);
                $existing_url = clean_input($_POST['existing_url'] ?? '');
                $domain = clean_input($_POST['domain_name'] ?? '');
                $hosting = intval($_POST['hosting_required']);
                $references = clean_input($_POST['reference_sites'] ?? '');
                $colors = clean_input($_POST['color_preferences'] ?? '');
                $budget = clean_input($_POST['budget'] ?? '');
                $stmt->bind_param("issssisisssss", $request_id, $web_type, $proj_name, $purpose, $audience, $pages, $features, $has_site, $existing_url, $domain, $hosting, $references, $colors, $budget);
                break;

            case 'PRINTER':
                $stmt = $conn->prepare("INSERT INTO request_printer_details (request_id, issue_type, printer_type, printer_brand, printer_model, serial_number, location, problem_description, error_code, toner_color, supplies_needed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $issue = clean_input($_POST['issue_type']);
                $printer_type = clean_input($_POST['printer_type'] ?? '');
                $brand = clean_input($_POST['printer_brand'] ?? '');
                $model = clean_input($_POST['printer_model'] ?? '');
                $serial = clean_input($_POST['serial_number'] ?? '');
                $location = clean_input($_POST['location']);
                $problem = clean_input($_POST['problem_description']);
                $error_code = clean_input($_POST['error_code'] ?? '');
                $toner = clean_input($_POST['toner_color'] ?? '');
                $supplies = clean_input($_POST['supplies_needed'] ?? '');
                $stmt->bind_param("issssssssss", $request_id, $issue, $printer_type, $brand, $model, $serial, $location, $problem, $error_code, $toner, $supplies);
                break;
        }

        $stmt->execute();

        $conn->commit();

        // Redirect to success page
        header("Location: request-success.php?code=$request_code");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
}

// Load nav menu
$nav_menus = get_menu_structure();
$nav_html = render_nav_menu($nav_menus);
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ยื่นคำขอ<?= htmlspecialchars($service['service_name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Sarabun', sans-serif; }
        .service-field { display: none; }
        .service-field.active { display: block; }
        
        /* Fixed green header */
        header.bg-teal-700 {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
        }
        
        body {
            padding-top: 80px; /* Adjust based on header height */
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-teal-700 text-white">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-3">
                <div class="flex items-center space-x-4">
                    <img src="public/assets/images/logo/rangsit-small-logo.png" alt="Rangsit Logo" class="h-14 w-auto">
                    <div>
                        <h1 class="text-lg font-bold">เทศบาลนครรังสิต</h1>
                        <p class="text-xs">ฟอร์มยื่นคำขอบริการ</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Navigation -->
    <header class="bg-white shadow-sm">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <img src="public/assets/images/logo/rangsit-small-logo.png" alt="Rangsit Logo" class="h-12 w-auto">
                    <div class="border-l-2 border-gray-300 pl-4">
                        <i class="<?= $service['icon'] ?> text-3xl text-<?= $service['color_code'] ?>-600"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?= htmlspecialchars($service['service_name']) ?></h1>
                        <p class="text-sm text-gray-600"><?= htmlspecialchars($service['service_name_en']) ?></p>
                    </div>
                </div>
                <a href="index.php" class="text-gray-600 hover:text-teal-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>กลับ
                </a>
            </div>
        </div>
    </header>

    <!-- Form Section -->
    <section class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <?php if (isset($error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded-lg">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl mr-3"></i>
                    <p class="text-red-700"><?= $error ?></p>
                </div>
            </div>
            <?php endif; ?>

            <form method="POST" class="bg-white rounded-xl shadow-lg p-8">
                <!-- Service Description -->
                <div class="bg-gradient-to-r from-<?= $service['color_code'] ?>-50 to-<?= $service['color_code'] ?>-100 rounded-lg p-6 mb-8">
                    <h2 class="text-xl font-bold text-<?= $service['color_code'] ?>-900 mb-2">
                        <i class="<?= $service['icon'] ?> mr-2"></i><?= htmlspecialchars($service['service_name']) ?>
                    </h2>
                    <p class="text-<?= $service['color_code'] ?>-700"><?= htmlspecialchars($service['description']) ?></p>
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
                            <input type="hidden" name="department" id="department_final" required>
                            <p class="text-xs text-gray-500 mt-2" id="dept_display"></p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">ตำแหน่ง</label>
                            <input type="text" name="position"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>

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

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">วันที่ต้องการให้เสร็จ</label>
                            <input type="date" name="target_date"
                                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                        </div>
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
                </div>

                <!-- Submit Button -->
                <div class="flex gap-4">
                    <button type="submit"
                            class="flex-1 bg-gradient-to-r from-teal-600 to-blue-600 hover:from-teal-700 hover:to-blue-700 text-white font-bold py-4 px-8 rounded-lg transition-all transform hover:scale-105 shadow-lg">
                        <i class="fas fa-paper-plane mr-2"></i>ส่งคำขอ
                    </button>
                    <a href="index.php"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold py-4 px-8 rounded-lg transition">
                        ยกเลิก
                    </a>
                </div>
            </form>
        </div>
    </section>

    <script>
        // Auto-set minimum date for target_date
        const targetDateInput = document.querySelector('input[name="target_date"]');
        if (targetDateInput) {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            targetDateInput.min = tomorrow.toISOString().split('T')[0];
        }

        // Department Cascade System
        const deptSelects = {
            level1: document.getElementById('dept_level1'),
            level2: document.getElementById('dept_level2'),
            level3: document.getElementById('dept_level3'),
            level4: document.getElementById('dept_level4')
        };

        const deptFinal = document.getElementById('department_final');
        const deptDisplay = document.getElementById('dept_display');
        let selectedDepts = { level1: null, level2: null, level3: null, level4: null };

        // Load Level 1 (สำนัก/กอง) on page load
        async function loadLevel1() {
            try {
                const response = await fetch('api/get_departments.php?level=1');
                const result = await response.json();

                if (result.success) {
                    deptSelects.level1.innerHTML = '<option value="">-- เลือกสำนัก/กอง --</option>';
                    result.data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.department_id;
                        option.textContent = dept.department_name + (dept.short_name ? ` (${dept.short_name})` : '');
                        option.dataset.name = dept.department_name;
                        deptSelects.level1.appendChild(option);
                    });
                }
            } catch (error) {
                console.error('Error loading departments:', error);
            }
        }

        // Load child departments
        async function loadChildDepts(level, parentId) {
            try {
                const response = await fetch(`api/get_departments.php?level=${level}&parent_id=${parentId}`);
                const result = await response.json();

                const selectElement = deptSelects[`level${level}`];
                const labels = {
                    2: 'ส่วน',
                    3: 'ฝ่าย/กลุ่มงาน',
                    4: 'งาน'
                };

                selectElement.innerHTML = `<option value="">-- เลือก${labels[level]} --</option>`;

                if (result.success && result.data.length > 0) {
                    result.data.forEach(dept => {
                        const option = document.createElement('option');
                        option.value = dept.department_id;
                        option.textContent = dept.department_name + (dept.short_name ? ` (${dept.short_name})` : '');
                        option.dataset.name = dept.department_name;
                        selectElement.appendChild(option);
                    });
                    selectElement.disabled = false;
                } else {
                    selectElement.disabled = true;
                }

                return result.data.length > 0;
            } catch (error) {
                console.error('Error loading child departments:', error);
                return false;
            }
        }

        // Update final department value
        function updateFinalDepartment() {
            const parts = [];
            let finalId = null;

            for (let i = 1; i <= 4; i++) {
                const select = deptSelects[`level${i}`];
                const selectedOption = select.options[select.selectedIndex];

                if (select.value) {
                    finalId = select.value;
                    const name = selectedOption.dataset.name || selectedOption.textContent;
                    parts.push(name);
                }
            }

            // Set hidden field to last selected department ID
            deptFinal.value = finalId || '';

            // Display full path
            if (parts.length > 0) {
                deptDisplay.textContent = 'หน่วยงานที่เลือก: ' + parts.join(' > ');
                deptDisplay.classList.remove('text-gray-500');
                deptDisplay.classList.add('text-teal-700', 'font-medium');
            } else {
                deptDisplay.textContent = '';
            }
        }

        // Level 1 change handler
        deptSelects.level1.addEventListener('change', async function() {
            const level1Id = this.value;
            selectedDepts.level1 = level1Id;

            // Reset lower levels
            ['level2', 'level3', 'level4'].forEach(level => {
                deptSelects[level].innerHTML = `<option value="">-- เลือก --</option>`;
                deptSelects[level].disabled = true;
                selectedDepts[level] = null;
            });

            if (level1Id) {
                await loadChildDepts(2, level1Id);
            }

            updateFinalDepartment();
        });

        // Level 2 change handler
        deptSelects.level2.addEventListener('change', async function() {
            const level2Id = this.value;
            selectedDepts.level2 = level2Id;

            // Reset lower levels
            ['level3', 'level4'].forEach(level => {
                deptSelects[level].innerHTML = `<option value="">-- เลือก --</option>`;
                deptSelects[level].disabled = true;
                selectedDepts[level] = null;
            });

            if (level2Id) {
                await loadChildDepts(3, level2Id);
            }

            updateFinalDepartment();
        });

        // Level 3 change handler
        deptSelects.level3.addEventListener('change', async function() {
            const level3Id = this.value;
            selectedDepts.level3 = level3Id;

            // Reset level 4
            deptSelects.level4.innerHTML = `<option value="">-- เลือก --</option>`;
            deptSelects.level4.disabled = true;
            selectedDepts.level4 = null;

            if (level3Id) {
                await loadChildDepts(4, level3Id);
            }

            updateFinalDepartment();
        });

        // Level 4 change handler
        deptSelects.level4.addEventListener('change', function() {
            selectedDepts.level4 = this.value;
            updateFinalDepartment();
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!deptFinal.value) {
                e.preventDefault();
                alert('กรุณาเลือกหน่วยงาน');
                deptSelects.level1.focus();
            }
        });

        // Initialize on page load
        loadLevel1();
    </script>
</body>
</html>
<?php $conn->close(); ?>
