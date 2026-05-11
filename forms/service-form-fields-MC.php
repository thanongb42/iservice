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
            <div class="relative">
                <input type="text" id="mc_time_start" name="event_time_start" required
                       placeholder="00:00" readonly
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent cursor-pointer bg-white">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="fas fa-clock"></i>
                </span>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">เวลาสิ้นสุด</label>
            <div class="relative">
                <input type="text" id="mc_time_end" name="event_time_end"
                       placeholder="00:00" readonly
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent cursor-pointer bg-white">
                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none">
                    <i class="fas fa-clock"></i>
                </span>
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

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-paperclip mr-1 text-teal-600"></i>
            แนบเอกสารอ้างอิง <span class="text-red-500">*</span>
        </label>
        <p class="text-xs text-gray-500 mb-2">
            เช่น หนังสือสั่งการ, หนังสืออ้างอิง, บันทึกข้อความ, หนังสือขอเข้าศึกษาดูงาน
            — เพื่อให้พิธีกรเตรียมตัวและผู้จัดการมอบหมายได้เหมาะสม<br>
            รองรับ PDF, DOCX, JPG, PNG (ขนาดสูงสุด 10MB)
        </p>
        <label id="mc-doc-label"
               class="flex flex-col items-center justify-center w-full border-2 border-dashed border-teal-400 rounded-lg p-5 cursor-pointer bg-teal-50 hover:bg-teal-100 transition"
               for="mc_doc_input">
            <i class="fas fa-file-upload text-3xl text-teal-500 mb-2"></i>
            <span id="mc-doc-text" class="text-sm font-semibold text-teal-700">คลิกหรือลากไฟล์มาวางที่นี่</span>
            <span class="text-xs text-gray-400 mt-1">เลือกได้หลายไฟล์</span>
        </label>
        <input type="file" id="mc_doc_input" name="attachments[]" multiple
               accept=".jpg,.jpeg,.png,.pdf,.doc,.docx"
               required
               class="sr-only">
        <div id="mc-doc-list" class="mt-2 space-y-1 text-sm text-gray-600"></div>
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

const _mcTimeCfg = { enableTime: true, noCalendar: true, time_24hr: true, dateFormat: "H:i" };
flatpickr("#mc_time_start", _mcTimeCfg);
flatpickr("#mc_time_end",   _mcTimeCfg);

// MC file uploader — supports add-multiple-times + individual X removal
(function() {
    var mcFiles = [];

    function mcRender() {
        var input = document.getElementById('mc_doc_input');
        var list  = document.getElementById('mc-doc-list');
        var text  = document.getElementById('mc-doc-text');
        list.innerHTML = '';
        if (mcFiles.length === 0) {
            text.textContent = 'คลิกหรือลากไฟล์มาวางที่นี่';
            var dt0 = new DataTransfer();
            input.files = dt0.files;
            return;
        }
        text.textContent = 'เลือกแล้ว ' + mcFiles.length + ' ไฟล์';
        mcFiles.forEach(function(f, i) {
            var div = document.createElement('div');
            div.className = 'flex items-center gap-2 px-3 py-1.5 bg-teal-50 border border-teal-200 rounded';
            div.innerHTML = '<i class="fas fa-file-alt text-teal-500 flex-shrink-0"></i>'
                + '<span class="flex-1 truncate text-gray-700">' + f.name + '</span>'
                + '<span class="text-gray-400 text-xs flex-shrink-0">(' + (f.size / 1024).toFixed(0) + ' KB)</span>'
                + '<button type="button" title="ลบไฟล์" class="flex-shrink-0 w-5 h-5 flex items-center justify-center rounded-full bg-red-100 text-red-500 hover:bg-red-200 hover:text-red-700 text-xs font-bold leading-none" data-i="' + i + '">&times;</button>';
            list.appendChild(div);
        });
        // Rebuild DataTransfer so input.files reflects current list
        var dt = new DataTransfer();
        mcFiles.forEach(function(f) { dt.items.add(f); });
        input.files = dt.files;
        // Attach remove handlers
        list.querySelectorAll('button[data-i]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                mcFiles.splice(parseInt(btn.getAttribute('data-i')), 1);
                mcRender();
            });
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('mc_doc_input');
        var label = document.getElementById('mc-doc-label');
        if (!input) return;

        input.addEventListener('change', function() {
            Array.from(input.files).forEach(function(f) { mcFiles.push(f); });
            mcRender();
            // Reset input so same file can be re-added after removal
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
            Array.from(e.dataTransfer.files).forEach(function(f) { mcFiles.push(f); });
            mcRender();
        });
    });
})();
</script>