<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทเว็บไซต์ <span class="text-red-500">*</span>
        </label>
        <select name="website_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="">-- เลือกประเภท --</option>
            <option value="main_site">เว็บไซต์หลัก</option>
            <option value="division_page">หน้า Page ย่อยของ สำนัก/กอง</option>
            <option value="add_page">เพิ่มหน้าใหม่</option>
            <option value="new_system">สร้างใหม่แยกระบบ</option>
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

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            <i class="fas fa-paperclip mr-1 text-gray-500"></i>แนบเอกสารอ้างอิง (ถ้ามี)
        </label>
        <p class="text-xs text-gray-500 mb-2">เช่น เอกสารโครงการ, ตัวอย่างงาน, โลโก้, รูปภาพประกอบ (JPG, PNG, PDF, DOCX)</p>
        <label id="wd-doc-label" class="flex flex-col items-center justify-center w-full border-2 border-dashed border-teal-400 rounded-lg p-5 cursor-pointer bg-teal-50 hover:bg-teal-100 transition" for="wd_doc_input">
            <i class="fas fa-file-upload text-3xl text-teal-500 mb-2"></i>
            <span id="wd-doc-text" class="text-sm font-semibold text-teal-700">คลิกหรือลากไฟล์มาวางที่นี่</span>
            <span class="text-xs text-gray-400 mt-1">รองรับสูงสุด 5 ไฟล์ รวมไม่เกิน 10MB</span>
        </label>
        <input type="file" id="wd_doc_input" name="attachments[]" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" class="sr-only">
        <div id="wd-doc-list" class="mt-2 space-y-1 text-sm text-gray-600"></div>
    </div>
</div>

<script>
function toggleExistingUrl(checkbox) {
    const input = document.getElementById('existingUrlInput');
    input.disabled = !checkbox.checked;
    if (!checkbox.checked) input.value = '';
}

// WEB_DESIGN file uploader — supports add-multiple-times + individual X removal
(function() {
    var wdFiles = [];

    function wdRender() {
        var input = document.getElementById('wd_doc_input');
        var list  = document.getElementById('wd-doc-list');
        var text  = document.getElementById('wd-doc-text');
        list.innerHTML = '';
        if (wdFiles.length === 0) {
            text.textContent = 'คลิกหรือลากไฟล์มาวางที่นี่';
            var dt0 = new DataTransfer();
            input.files = dt0.files;
            return;
        }
        text.textContent = 'เลือกแล้ว ' + wdFiles.length + ' ไฟล์';
        wdFiles.forEach(function(f, i) {
            var div = document.createElement('div');
            div.className = 'flex items-center gap-2 px-3 py-1.5 bg-teal-50 border border-teal-200 rounded';
            div.innerHTML = '<i class="fas fa-file-alt text-teal-500 flex-shrink-0"></i>'
                + '<span class="flex-1 truncate text-gray-700">' + f.name + '</span>'
                + '<span class="text-gray-400 text-xs flex-shrink-0">(' + (f.size / 1024).toFixed(0) + ' KB)</span>'
                + '<button type="button" title="ลบไฟล์" class="flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-full bg-red-100 text-red-500 hover:bg-red-200 hover:text-red-700 text-xs font-bold leading-none" data-i="' + i + '">&times;</button>';
            list.appendChild(div);
        });
        var dt = new DataTransfer();
        wdFiles.forEach(function(f) { dt.items.add(f); });
        input.files = dt.files;
        list.querySelectorAll('button[data-i]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                wdFiles.splice(parseInt(btn.getAttribute('data-i')), 1);
                wdRender();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('wd_doc_input');
        var label = document.getElementById('wd-doc-label');
        if (!input) return;

        input.addEventListener('change', function() {
            Array.from(input.files).forEach(function(f) { wdFiles.push(f); });
            wdRender();
            input.value = '';
        });

        label.addEventListener('dragover', function(e) {
            e.preventDefault();
            label.classList.add('border-teal-600', 'bg-teal-100');
        });
        label.addEventListener('dragleave', function() {
            label.classList.remove('border-teal-600', 'bg-teal-100');
        });
        label.addEventListener('drop', function(e) {
            e.preventDefault();
            label.classList.remove('border-teal-600', 'bg-teal-100');
            Array.from(e.dataTransfer.files).forEach(function(f) { wdFiles.push(f); });
            wdRender();
        });
    });
})();
</script>
