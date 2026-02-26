<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ชื่อโครงการ/กิจกรรม <span class="text-red-500">*</span>
        </label>
        <input type="text" name="event_name" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทงาน <span class="text-red-500">*</span>
        </label>
        <select name="event_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="">-- เลือกประเภทงาน --</option>
            <option value="formal">พิธีการ/ทางการ</option>
            <option value="entertainment">สันทนาการ/รื่นเริง</option>
            <option value="seminar">อบรม/สัมมนา</option>
            <option value="press">แถลงข่าว</option>
            <option value="other">อื่นๆ</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">สถานที่จัดงาน <span class="text-red-500">*</span></label>
        <input type="text" name="location" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">วันที่จัดงาน <span class="text-red-500">*</span></label>
        <div class="relative">
            <input type="text" id="mc_event_date_display" placeholder="วว/ดด/ปปปป" required readonly
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent cursor-pointer bg-white">
            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                <i class="fas fa-calendar-alt"></i>
            </span>
        </div>
        <input type="hidden" name="event_date" id="mc_event_date_hidden">
    </div>

    <div class="grid grid-cols-2 gap-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">เวลาเริ่ม <span class="text-red-500">*</span></label>
            <div class="flex items-center space-x-1">
                <select name="event_time_start_h" required
                        class="w-full px-2 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="">ชม.</option>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                    <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d', $h) ?></option>
                    <?php endfor; ?>
                </select>
                <span class="text-gray-500 font-bold">:</span>
                <select name="event_time_start_m" required
                        class="w-full px-2 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="">นาที</option>
                    <?php for ($m = 0; $m <= 55; $m += 5): ?>
                    <option value="<?= sprintf('%02d', $m) ?>"><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">เวลาสิ้นสุด</label>
            <div class="flex items-center space-x-1">
                <select name="event_time_end_h"
                        class="w-full px-2 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="">ชม.</option>
                    <?php for ($h = 0; $h <= 23; $h++): ?>
                    <option value="<?= sprintf('%02d', $h) ?>"><?= sprintf('%02d', $h) ?></option>
                    <?php endfor; ?>
                </select>
                <span class="text-gray-500 font-bold">:</span>
                <select name="event_time_end_m"
                        class="w-full px-2 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                    <option value="">นาที</option>
                    <?php for ($m = 0; $m <= 55; $m += 5): ?>
                    <option value="<?= sprintf('%02d', $m) ?>"><?= sprintf('%02d', $m) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">จำนวนพิธีกรที่ต้องการ (คน)</label>
        <input type="number" name="mc_count" value="1" min="1" max="10"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">ภาษา</label>
        <select name="language"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="TH">ไทย</option>
            <option value="EN">อังกฤษ</option>
            <option value="BOTH">ไทย + อังกฤษ</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">สถานะบทพูด (Script)</label>
        <select name="script_status"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="not_ready">ยังไม่มี (ขอให้พิธีกรเตรียม)</option>
            <option value="draft">มีร่างให้</option>
            <option value="ready">มีบทสมบูรณ์ให้</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">การแต่งกาย (Dress Code)</label>
        <input type="text" name="dress_code" placeholder="เช่น ชุดข้าราชการ, สากลนิยม, เสื้อโปโล"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/th.js"></script>
<script>
flatpickr("#mc_event_date_display", {
    locale: "th",
    dateFormat: "d/m/Y",
    minDate: "today",
    onChange: function(selectedDates, dateStr, instance) {
        if (selectedDates.length > 0) {
            const d = selectedDates[0];
            const year  = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day   = String(d.getDate()).padStart(2, '0');
            document.getElementById('mc_event_date_hidden').value = `${year}-${month}-${day}`;
            instance.input.value = `${day}/${month}/${year + 543}`;
        }
    }
});
</script>