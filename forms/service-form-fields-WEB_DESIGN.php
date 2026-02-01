<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทเว็บไซต์ <span class="text-red-500">*</span>
        </label>
        <select name="website_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="landing_page">Landing Page (หน้าเดียว)</option>
            <option value="corporate" selected>Corporate (หน่วยงาน/บริษัท)</option>
            <option value="blog">Blog/ข่าวสาร</option>
            <option value="ecommerce">E-Commerce (ขายสินค้า)</option>
            <option value="portal">Portal (ระบบ Web App)</option>
            <option value="other">อื่นๆ</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ชื่อโครงการ <span class="text-red-500">*</span>
        </label>
        <input type="text" name="project_name" required
               placeholder="เช่น เว็บไซต์ฝ่ายการเงิน"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วัตถุประสงค์/เป้าหมาย <span class="text-red-500">*</span>
        </label>
        <textarea name="purpose" required rows="3"
                  placeholder="อธิบายวัตถุประสงค์และเป้าหมายของเว็บไซต์"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            กลุ่มเป้าหมาย
        </label>
        <input type="text" name="target_audience"
               placeholder="เช่น ประชาชนทั่วไป, พนักงานภายใน"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            จำนวนหน้า (โดยประมาณ)
        </label>
        <select name="number_of_pages"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="1">1-5 หน้า</option>
            <option value="10" selected>6-10 หน้า</option>
            <option value="20">11-20 หน้า</option>
            <option value="50">มากกว่า 20 หน้า</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ฟีเจอร์ที่ต้องการ
        </label>
        <textarea name="features_required" rows="2"
                  placeholder="เช่น ระบบข่าวสาร, แบบฟอร์มติดต่อ, Gallery รูปภาพ, ระบบค้นหา (คั่นด้วย comma)"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div>
        <label class="flex items-center space-x-2 mb-2">
            <input type="checkbox" name="has_existing_site" value="1" onchange="toggleExistingUrl(this)"
                   class="w-5 h-5 text-teal-600 border-gray-300 rounded focus:ring-2 focus:ring-teal-500">
            <span class="text-sm font-medium text-gray-700">มีเว็บเก่าอยู่แล้ว</span>
        </label>
        <input type="url" name="existing_url" id="existingUrlInput" disabled
               placeholder="URL เว็บเก่า (ถ้ามี)"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent disabled:bg-gray-100">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Domain ที่ต้องการ
        </label>
        <input type="text" name="domain_name"
               placeholder="เช่น finance.rangsit.go.th"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="flex items-center space-x-2">
            <input type="checkbox" name="hosting_required" value="1" checked
                   class="w-5 h-5 text-teal-600 border-gray-300 rounded focus:ring-2 focus:ring-teal-500">
            <span class="text-sm font-medium text-gray-700">ต้องการ Hosting จากทางเทศบาล</span>
        </label>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สีที่ต้องการ/ชอบ
        </label>
        <input type="text" name="color_preferences"
               placeholder="เช่น เขียว, น้ำเงิน, ตามโทนสีของเทศบาล"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            เว็บไซต์อ้างอิง (ที่ชอบ)
        </label>
        <textarea name="reference_sites" rows="2"
                  placeholder="ระบุ URL เว็บไซต์ที่ชอบหรือต้องการอ้างอิง (แยกบรรทัด)"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            งบประมาณ (ถ้ามีกำหนด)
        </label>
        <input type="text" name="budget"
               placeholder="เช่น 20,000 บาท หรือไม่กำหนด"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>
</div>

<script>
function toggleExistingUrl(checkbox) {
    const input = document.getElementById('existingUrlInput');
    input.disabled = !checkbox.checked;
    if (!checkbox.checked) input.value = '';
}
</script>
