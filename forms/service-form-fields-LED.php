<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ชื่อสื่อ/หัวข้อ <span class="text-red-500">*</span>
        </label>
        <input type="text" name="media_title" required
               placeholder="เช่น ประชาสัมพันธ์งานลอยกระทง 2569"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2 grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                วันเริ่มแสดง <span class="text-red-500">*</span>
            </label>
            <input type="text" id="led_date_start" name="display_date_start" required
                   placeholder="เลือกวันที่"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white cursor-pointer">
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                วันสิ้นสุดการแสดง
            </label>
            <input type="text" id="led_date_end" name="display_date_end"
                   placeholder="เลือกวันที่ (ถ้ามี)"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent bg-white cursor-pointer">
        </div>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
(function() {
    const thaiMonths = ['ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];

    function thaiFormatDate(date) {
        return date.getDate() + ' ' + thaiMonths[date.getMonth()] + ' ' + (date.getFullYear() + 543);
    }

    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);

    const fpStart = flatpickr('#led_date_start', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j M Y',
        allowInput: false,
        monthSelectorType: 'static',
        minDate: tomorrow,
        formatDate: function(date, format) {
            if (format === 'j M Y') return thaiFormatDate(date);
            return flatpickr.formatDate(date, format);
        },
        onChange: function(selectedDates) {
            if (selectedDates[0]) {
                fpEnd.set('minDate', selectedDates[0]);
            }
        }
    });

    const fpEnd = flatpickr('#led_date_end', {
        locale: 'th',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'j M Y',
        allowInput: false,
        monthSelectorType: 'static',
        minDate: tomorrow,
        formatDate: function(date, format) {
            if (format === 'j M Y') return thaiFormatDate(date);
            return flatpickr.formatDate(date, format);
        }
    });
})();
</script>
