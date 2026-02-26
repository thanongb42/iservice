<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ชื่องาน/กิจกรรม <span class="text-red-500">*</span>
        </label>
        <input type="text" name="event_name" required
               placeholder="เช่น งานประชุมคณะกรรมการเทศบาล ครั้งที่ 1/2568"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทงาน <span class="text-red-500">*</span>
        </label>
        <input type="text" name="event_type" required
               placeholder="เช่น ประชุม, สัมมนา, พิธีเปิด, งานกิจกรรม"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วันที่จัดงาน <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input type="text" id="event_date_display" placeholder="วว/ดด/ปปปป" required readonly
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent cursor-pointer bg-white">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fas fa-calendar-alt"></i>
            </span>
        </div>
        <input type="hidden" name="event_date" id="event_date_hidden">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เวลาเริ่ม <span class="text-red-500">*</span>
        </label>
        <div class="flex items-center space-x-2">
            <select name="event_time_start_h" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option value="">ชั่วโมง</option>
                <?php for ($h = 0; $h <= 23; $h++): ?>
                <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d', $h) ?></option>
                <?php endfor; ?>
            </select>
            <span class="text-gray-500 font-bold">:</span>
            <select name="event_time_start_m" required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option value="">นาที</option>
                <?php for ($m = 0; $m <= 55; $m += 5): ?>
                <option value="<?= sprintf('%02d', $m) ?>"><?= sprintf('%02d', $m) ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เวลาสิ้นสุด (โดยประมาณ)
        </label>
        <div class="flex items-center space-x-2">
            <select name="event_time_end_h"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option value="">ชั่วโมง</option>
                <?php for ($h = 0; $h <= 23; $h++): ?>
                <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d', $h) ?></option>
                <?php endfor; ?>
            </select>
            <span class="text-gray-500 font-bold">:</span>
            <select name="event_time_end_m"
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option value="">นาที</option>
                <?php for ($m = 0; $m <= 55; $m += 5): ?>
                <option value="<?= sprintf('%02d', $m) ?>"><?= sprintf('%02d', $m) ?></option>
                <?php endfor; ?>
            </select>
        </div>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สถานที่จัดงาน <span class="text-red-500">*</span>
        </label>
        <input type="text" name="event_location" required
               placeholder="เช่น ห้องประชุมใหญ่ ชั้น 5 อาคารอำนวยการ"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            จำนวนช่างภาพที่ต้องการ
        </label>
        <select name="number_of_photographers"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="1" selected>1 คน</option>
            <option value="2">2 คน</option>
            <option value="3">3 คน</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            รูปแบบการส่งมอบไฟล์
        </label>
        <input type="text" name="delivery_format" value="Digital (Google Drive)"
               placeholder="เช่น Digital (Drive), CD/DVD, Flash Drive"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">ประเภทการถ่าย</label>
        <div class="flex items-center space-x-6">
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="photo_type[]" value="photo"
                       class="w-5 h-5 text-teal-600 border-gray-300 rounded focus:ring-2 focus:ring-teal-500">
                <span class="text-sm font-medium text-gray-700">ภาพนิ่ง</span>
            </label>
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="photo_type[]" value="video"
                       class="w-5 h-5 text-teal-600 border-gray-300 rounded focus:ring-2 focus:ring-teal-500">
                <span class="text-sm font-medium text-gray-700">ภาพวิดีโอ</span>
            </label>
        </div>
    </div>

</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
flatpickr("#event_date_display", {
    locale: "th",
    dateFormat: "d/m/Y",
    minDate: "today",
    onChange: function(selectedDates, dateStr, instance) {
        if (selectedDates.length > 0) {
            const d = selectedDates[0];
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day   = String(d.getDate()).padStart(2, '0');
            // store CE date for DB
            document.getElementById('event_date_hidden').value = `${year}-${month}-${day}`;
            // display BE year
            const yearBE = year + 543;
            instance.input.value = `${day}/${month}/${yearBE}`;
        }
    }
});
</script>
