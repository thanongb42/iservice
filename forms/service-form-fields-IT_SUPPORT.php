<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทปัญหา <span class="text-red-500">*</span>
        </label>
        <select name="issue_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="hardware">ฮาร์ดแวร์ (Hardware)</option>
            <option value="software">ซอฟต์แวร์ (Software)</option>
            <option value="network">เครือข่าย (Network)</option>
            <option value="other">อื่นๆ</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ระดับความเร่งด่วน <span class="text-red-500">*</span>
        </label>
        <select name="urgency_level" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="low">ต่ำ - ยังใช้งานได้</option>
            <option value="medium" selected>ปานกลาง - ส่งผลกระทบบางส่วน</option>
            <option value="high">สูง - ส่งผลกระทบมาก</option>
            <option value="critical">วิกฤต - ไม่สามารถทำงานได้</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทอุปกรณ์
        </label>
        <input type="text" name="device_type"
               placeholder="เช่น Desktop, Laptop, Printer, Scanner"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ยี่ห้อ/รุ่น
        </label>
        <input type="text" name="device_brand"
               placeholder="เช่น HP LaserJet, Dell Inspiron"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สถานที่/ห้อง <span class="text-red-500">*</span>
        </label>
        <input type="text" name="location" required
               placeholder="เช่น อาคาร 2 ชั้น 3 ห้องการเงิน"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            อาการ/ปัญหาที่พบ <span class="text-red-500">*</span>
        </label>
        <textarea name="symptoms" required rows="4"
                  placeholder="อธิบายปัญหาที่พบโดยละเอียด เช่น คอมพิวเตอร์เปิดไม่ติด, โปรแกรมค้าง, เน็ตช้า"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ข้อความ Error (ถ้ามี)
        </label>
        <input type="text" name="error_message"
               placeholder="ข้อความ Error ที่แสดงบนหน้าจอ"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เกิดขึ้นเมื่อไร <span class="text-red-500">*</span>
        </label>
        <input type="text" name="when_occurred" required
               placeholder="เช่น วันนี้ช่วงเช้า, เมื่อ 2 วันที่แล้ว, บ่อยครั้งหลังจากเปิดเครื่อง"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>
</div>
