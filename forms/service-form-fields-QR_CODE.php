<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภท QR Code <span class="text-red-500">*</span>
        </label>
        <select id="qr_type" name="qr_type" required
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
        <select id="qr_size" name="qr_size" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="200">เล็ก (200x200 px)</option>
            <option value="400" selected>กลาง (400x400 px)</option>
            <option value="800">ใหญ่ (800x800 px)</option>
            <option value="1200">ใหญ่มาก (1200x1200 px)</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เนื้อหา/URL ที่ต้องการ <span class="text-red-500">*</span>
        </label>
        <textarea id="qr_content" name="qr_content" required rows="3"
                  placeholder="เช่น https://example.com หรือข้อความที่ต้องการให้แสดงเมื่อสแกน"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">สีหลัก</label>
        <input type="color" id="color_primary" name="color_primary" value="#000000"
               class="w-full h-12 px-2 py-1 border border-gray-300 rounded-lg cursor-pointer">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">สีพื้นหลัง</label>
        <input type="color" id="color_background" name="color_background" value="#FFFFFF"
               class="w-full h-12 px-2 py-1 border border-gray-300 rounded-lg cursor-pointer">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">รูปแบบไฟล์</label>
        <select id="output_format" name="output_format"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="png" selected>PNG</option>
            <option value="svg">SVG</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">Logo กลาง QR</label>
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="flex-1">
                <label class="block text-xs text-gray-500 mb-1">Upload ไฟล์</label>
                <input type="file" id="logo_file" accept=".png,.jpg,.jpeg,.svg,.webp"
                       class="block w-full text-sm text-gray-500
                        file:mr-3 file:py-2 file:px-4
                        file:rounded-full file:border-0
                        file:text-sm file:font-semibold
                        file:bg-teal-50 file:text-teal-700
                        hover:file:bg-teal-100
                        border border-gray-300 rounded-lg cursor-pointer">
            </div>
            <div class="flex-1">
                <label class="block text-xs text-gray-500 mb-1">หรือใส่ URL</label>
                <input type="url" id="logo_url" name="logo_url"
                       placeholder="https://example.com/logo.png"
                       class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent text-sm">
            </div>
        </div>
        <!-- Logo Preview -->
        <div id="logoPreview" class="mt-2 hidden">
            <div class="flex items-center gap-2 bg-gray-50 rounded-lg p-2">
                <img id="logoPreviewImg" src="" alt="Logo" class="h-10 w-10 object-contain rounded">
                <span id="logoPreviewName" class="text-xs text-gray-600 flex-1 truncate"></span>
                <button type="button" id="logoRemoveBtn" class="text-red-500 hover:text-red-700 text-xs font-semibold px-2">
                    <i class="fas fa-times mr-1"></i>ลบ
                </button>
            </div>
        </div>
    </div>
</div>

<!-- QR Code Preview -->
<div id="qrResultArea" class="mt-8 hidden">
    <div class="border-t pt-6">
        <h4 class="text-lg font-bold text-gray-900 mb-4">ผลลัพธ์ QR Code</h4>
        <div class="flex flex-col items-center gap-4">
            <div id="qrPreview" class="bg-white p-4 rounded-xl border-2 border-dashed border-gray-200 inline-block"></div>
            <div class="flex gap-3">
                <button type="button" id="btnDownloadPng"
                        class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-lg shadow transition">
                    <i class="fas fa-download mr-2"></i>Download PNG
                </button>
                <button type="button" id="btnDownloadSvg"
                        class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow transition">
                    <i class="fas fa-download mr-2"></i>Download SVG
                </button>
            </div>
        </div>
    </div>
</div>
