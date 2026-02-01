<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Username ที่ต้องการ <span class="text-red-500">*</span>
        </label>
        <input type="text" name="requested_username" required
               placeholder="เช่น somchai.j"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
        <p class="text-xs text-gray-500 mt-1">รูปแบบ: ชื่อ.นามสกุลย่อ</p>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            รูปแบบอีเมล
        </label>
        <input type="text" name="email_format"
               placeholder="somchai.j@rangsit.go.th"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทคำขอ <span class="text-red-500">*</span>
        </label>
        <select name="is_new_account" required onchange="toggleExistingEmail(this)"
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="1">สร้างบัญชีใหม่</option>
            <option value="0">ขอเพิ่ม Quota / Reset Password</option>
        </select>
    </div>

    <div id="existingEmailDiv" class="md:col-span-2" style="display:none;">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            อีเมลเดิม
        </label>
        <input type="email" name="existing_email"
               placeholder="อีเมลปัจจุบันที่ต้องการแก้ไข"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วัตถุประสงค์การใช้งาน <span class="text-red-500">*</span>
        </label>
        <textarea name="purpose" required rows="3"
                  placeholder="เช่น ใช้งานราชการทั่วไป ติดต่อประสานงาน"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>

<script>
function toggleExistingEmail(select) {
    const div = document.getElementById('existingEmailDiv');
    div.style.display = select.value == '0' ? 'block' : 'none';
}
</script>
