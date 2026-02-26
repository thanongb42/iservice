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
        <div class="flex items-center space-x-2">
            <select name="event_date_day" required
                    class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option value="">วัน</option>
                <?php for ($d = 1; $d <= 31; $d++): ?>
                <option value="<?= sprintf('%02d', $d) ?>"><?= $d ?></option>
                <?php endfor; ?>
            </select>
            <select name="event_date_month" required
                    class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option value="">เดือน</option>
                <?php
                $months = ['มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
                           'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
                foreach ($months as $i => $m): ?>
                <option value="<?= sprintf('%02d', $i + 1) ?>"><?= $m ?></option>
                <?php endforeach; ?>
            </select>
            <select name="event_date_year" required
                    class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
                <option value="">ปี (พ.ศ.)</option>
                <?php
                $currentYear = (int)date('Y') + 543;
                for ($y = $currentYear; $y <= $currentYear + 5; $y++): ?>
                <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <input type="hidden" name="event_date" id="event_date_combined">
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

<script>
// Combine Thai date selects into hidden event_date field (YYYY-MM-DD in CE)
function updateEventDate() {
    const day   = document.querySelector('[name="event_date_day"]').value;
    const month = document.querySelector('[name="event_date_month"]').value;
    const yearBE = document.querySelector('[name="event_date_year"]').value;
    if (day && month && yearBE) {
        const yearCE = parseInt(yearBE) - 543;
        document.getElementById('event_date_combined').value = `${yearCE}-${month}-${day}`;
    }
}
document.querySelector('[name="event_date_day"]').addEventListener('change', updateEventDate);
document.querySelector('[name="event_date_month"]').addEventListener('change', updateEventDate);
document.querySelector('[name="event_date_year"]').addEventListener('change', updateEventDate);
</script>
