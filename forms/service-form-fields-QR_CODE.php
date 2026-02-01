<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภท QR Code <span class="text-red-500">*</span>
        </label>
        <select name="qr_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="url" selected>URL/เว็บไซต์</option>
            <option value="text">ข้อความ</option>
            <option value="vcard">นามบัตร (vCard)</option>
            <option value="wifi">WiFi</option>
            <option value="payment">QR Payment</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ขนาด QR Code <span class="text-red-500">*</span>
        </label>
        <select name="qr_size" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="small">เล็ก (200x200 px)</option>
            <option value="medium" selected>กลาง (400x400 px)</option>
            <option value="large">ใหญ่ (800x800 px)</option>
            <option value="xlarge">ใหญ่มาก (1200x1200 px)</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เนื้อหา/URL ที่ต้องการ <span class="text-red-500">*</span>
        </label>
        <textarea name="qr_content" required rows="3"
                  placeholder="เช่น https://example.com หรือข้อความที่ต้องการให้แสดงเมื่อสแกน"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สีหลัก
        </label>
        <input type="color" name="color_primary" value="#000000"
               class="w-full h-12 px-2 py-1 border border-gray-300 rounded-lg cursor-pointer">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สีพื้นหลัง
        </label>
        <input type="color" name="color_background" value="#FFFFFF"
               class="w-full h-12 px-2 py-1 border border-gray-300 rounded-lg cursor-pointer">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            รูปแบบไฟล์ <span class="text-red-500">*</span>
        </label>
        <select name="output_format" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="png" selected>PNG</option>
            <option value="svg">SVG</option>
            <option value="pdf">PDF</option>
            <option value="jpg">JPG</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            จำนวนที่ต้องการ
        </label>
        <input type="number" name="quantity" value="1" min="1" max="100"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Logo กลาง QR (URL ภาพ)
        </label>
        <input type="url" name="logo_url"
               placeholder="https://example.com/logo.png (ถ้าต้องการ)"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วัตถุประสงค์การใช้งาน
        </label>
        <textarea name="purpose" rows="2"
                  placeholder="เช่น ประชาสัมพันธ์โครงการ, ลงทะเบียนกิจกรรม"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>
