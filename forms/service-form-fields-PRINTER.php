<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทปัญหา/คำขอ <span class="text-red-500">*</span>
        </label>
        <select name="issue_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="repair">ซ่อมแซม/แจ้งปัญหา</option>
            <option value="toner_replacement">เติมหมึก/เปลี่ยนตลับหมึก</option>
            <option value="paper_jam">กระดาษติด</option>
            <option value="driver_install">ติดตั้ง Driver/เชื่อมต่อเครื่อง</option>
            <option value="new_installation">ติดตั้งเครื่องใหม่</option>
            <option value="other">อื่นๆ</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทเครื่อง
        </label>
        <select name="printer_type"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="">-เลือก-</option>
            <option value="inkjet">Inkjet (พิมพ์หมึกฉีด)</option>
            <option value="laser">Laser (เลเซอร์)</option>
            <option value="multifunction">Multifunction (All-in-One)</option>
            <option value="scanner">Scanner (สแกนเนอร์)</option>
            <option value="plotter">Plotter (พิมพ์ขนาดใหญ่)</option>
            <option value="3d_printer">3D Printer</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ยี่ห้อ
        </label>
        <input type="text" name="printer_brand"
               placeholder="เช่น HP, Canon, Epson, Brother"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            รุ่น/Model
        </label>
        <input type="text" name="printer_model"
               placeholder="เช่น LaserJet Pro M404, Pixma G3010"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            S/N (Serial Number)
        </label>
        <input type="text" name="serial_number"
               placeholder="หมายเลขเครื่อง (ถ้ามี)"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สถานที่/ห้อง <span class="text-red-500">*</span>
        </label>
        <input type="text" name="location" required
               placeholder="เช่น อาคาร 2 ชั้น 3 ห้องการเงิน"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            อธิบายปัญหา <span class="text-red-500">*</span>
        </label>
        <textarea name="problem_description" required rows="4"
                  placeholder="อธิบายปัญหาที่พบโดยละเอียด เช่น พิมพ์ไม่ออก, พิมพ์ตัวอักษรไม่ชัด, ไฟกระพริบ"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Error Code (ถ้ามี)
        </label>
        <input type="text" name="error_code"
               placeholder="เช่น E01, 49.4C02"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สีหมึก (กรณีขอเติม)
        </label>
        <input type="text" name="toner_color"
               placeholder="เช่น ดำ, สี (C/M/Y/K), ทั้งหมด"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วัสดุสิ้นเปลืองที่ต้องการ
        </label>
        <textarea name="supplies_needed" rows="2"
                  placeholder="เช่น ตลับหมึก, กระดาษ A4, Drum Unit (ระบุเฉพาะที่ต้องการ)"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>
