<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ชื่อสื่อ/หัวข้อ <span class="text-red-500">*</span>
        </label>
        <input type="text" name="media_title" required
               placeholder="เช่น ประชาสัมพันธ์งานลอยกระทง 2569"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทสื่อ <span class="text-red-500">*</span>
        </label>
        <select name="media_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="">-- เลือกประเภทสื่อ --</option>
            <option value="image">ภาพนิ่ง (Image)</option>
            <option value="video">วิดีโอ (Video)</option>
            <option value="animation">แอนิเมชัน (Animation/GIF)</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ตำแหน่งจอ LED <span class="text-red-500">*</span>
        </label>
        <select name="display_location" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="">-- เลือกตำแหน่งจอ --</option>
            <option value="หน้าอาคารสำนักงาน">หน้าอาคารสำนักงาน</option>
            <option value="ห้องโถงชั้น 1">ห้องโถงชั้น 1</option>
            <option value="ลานจอดรถ">ลานจอดรถ</option>
            <option value="อื่นๆ">อื่นๆ (ระบุในหมายเหตุ)</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วันเริ่มแสดง <span class="text-red-500">*</span>
        </label>
        <input type="date" name="display_date_start" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วันสิ้นสุดการแสดง
        </label>
        <input type="date" name="display_date_end"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เวลาเริ่มแสดง
        </label>
        <input type="time" name="display_time_start"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
        <p class="text-xs text-gray-400 mt-1">หากต้องการแสดงตลอดทั้งวัน ไม่ต้องระบุ</p>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เวลาสิ้นสุดแสดง
        </label>
        <input type="time" name="display_time_end"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ระยะเวลาแสดงต่อรอบ (วินาที)
        </label>
        <select name="duration_seconds"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="10">10 วินาที</option>
            <option value="15" selected>15 วินาที</option>
            <option value="30">30 วินาที</option>
            <option value="60">60 วินาที</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ความละเอียดไฟล์
        </label>
        <select name="resolution"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="">-- ไม่ระบุ --</option>
            <option value="1920x1080">1920x1080 (Full HD)</option>
            <option value="3840x2160">3840x2160 (4K)</option>
            <option value="1080x1920">1080x1920 (แนวตั้ง Full HD)</option>
            <option value="อื่นๆ">อื่นๆ</option>
        </select>
    </div>

    <!-- Media File / URL Section -->
    <div class="md:col-span-2">
        <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h4 class="text-sm font-semibold text-blue-800 mb-3">
                <i class="fas fa-cloud-upload-alt mr-1"></i> ไฟล์สื่อ (เลือกอย่างใดอย่างหนึ่ง หรือทั้งสองอย่าง)
            </h4>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-video mr-1"></i> อัปโหลดไฟล์สื่อ
                    </label>
                    <input type="file" name="media_file"
                           accept="video/*,image/*,.gif,.mp4,.avi,.mov,.wmv,.mkv,.jpg,.jpeg,.png"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-transparent file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200">
                    <p class="text-xs text-gray-500 mt-1">รองรับไฟล์วิดีโอ/ภาพ ขนาดไม่เกิน 200 MB (mp4, avi, mov, jpg, png, gif)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-link mr-1"></i> หรือวาง URL / Google Drive Link
                    </label>
                    <input type="url" name="media_url"
                           placeholder="https://drive.google.com/file/d/... หรือ URL อื่นๆ"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">วาง link Google Drive, OneDrive, YouTube หรือ URL ที่เข้าถึงไฟล์ได้</p>
                </div>
            </div>
        </div>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วัตถุประสงค์/รายละเอียดสื่อ <span class="text-red-500">*</span>
        </label>
        <textarea name="purpose" rows="3" required
                  placeholder="ระบุวัตถุประสงค์ เช่น ประชาสัมพันธ์กิจกรรม, แจ้งข่าวสาร, โฆษณาโครงการ"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ความต้องการพิเศษ
        </label>
        <textarea name="special_requirements" rows="2"
                  placeholder="เช่น ต้องการให้ออกแบบสื่อให้, ต้องการแสดงซ้ำทุกๆ 5 นาที, มีไฟล์สื่อแนบมาด้วย"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>

<script>
// Set minimum date to tomorrow
const ledDateStart = document.querySelector('input[name="display_date_start"]');
const ledDateEnd = document.querySelector('input[name="display_date_end"]');
if (ledDateStart) {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    ledDateStart.min = tomorrow.toISOString().split('T')[0];

    // Ensure end date >= start date
    ledDateStart.addEventListener('change', function() {
        if (ledDateEnd) {
            ledDateEnd.min = this.value;
            if (ledDateEnd.value && ledDateEnd.value < this.value) {
                ledDateEnd.value = this.value;
            }
        }
    });
}
</script>
