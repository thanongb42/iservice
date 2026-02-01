<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทคำขอ <span class="text-red-500">*</span>
        </label>
        <select name="request_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="new_wifi">ติดตั้ง WiFi ใหม่</option>
            <option value="password_reset">ขอรหัสผ่าน WiFi / Reset</option>
            <option value="signal_issue">สัญญาณ WiFi อ่อน/ไม่เสถียร</option>
            <option value="speed_issue">ความเร็วอินเทอร์เน็ตช้า</option>
            <option value="other">อื่นๆ</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สถานที่/ห้อง <span class="text-red-500">*</span>
        </label>
        <input type="text" name="location" required
               placeholder="ระบุสถานที่ที่ต้องการติดตั้ง/มีปัญหา"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            อาคาร
        </label>
        <input type="text" name="building"
               placeholder="เช่น อาคาร 1, อาคารอำนวยการ"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เลขห้อง
        </label>
        <input type="text" name="room_number"
               placeholder="เช่น 301, ห้องประชุมใหญ่"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            จำนวนผู้ใช้งาน (โดยประมาณ)
        </label>
        <select name="number_of_users"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="1">1-5 คน</option>
            <option value="10" selected>6-10 คน</option>
            <option value="20">11-20 คน</option>
            <option value="50">21-50 คน</option>
            <option value="100">มากกว่า 50 คน</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ความเร็วที่ต้องการ
        </label>
        <select name="required_speed"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="50mbps">50 Mbps</option>
            <option value="100mbps" selected>100 Mbps</option>
            <option value="200mbps">200 Mbps</option>
            <option value="500mbps">500 Mbps</option>
            <option value="1gbps">1 Gbps</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ปัญหาที่พบ (กรณีแจ้งปัญหา)
        </label>
        <textarea name="current_issue" rows="3"
                  placeholder="อธิบายปัญหาที่พบ เช่น ต่อไม่ได้บางครั้ง, ช้ามากในช่วงเช้า"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>
