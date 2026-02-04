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
        <input type="date" name="event_date" required
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="grid grid-cols-2 gap-2">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">เวลาเริ่ม <span class="text-red-500">*</span></label>
            <input type="time" name="event_time_start" required
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">เวลาสิ้นสุด</label>
            <input type="time" name="event_time_end"
                   class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
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

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">หมายเหตุ / ความต้องการพิเศษ</label>
        <textarea name="note" rows="3"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>