<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ชื่อโฟลเดอร์ที่ต้องการ <span class="text-red-500">*</span>
        </label>
        <input type="text" name="folder_name" required
               placeholder="เช่น ฝ่ายการเงิน_งบประมาณ"
               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ขนาด Storage (GB) <span class="text-red-500">*</span>
        </label>
        <select name="storage_size_gb" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="10">10 GB</option>
            <option value="20">20 GB</option>
            <option value="50">50 GB</option>
            <option value="100" selected>100 GB</option>
            <option value="200">200 GB</option>
            <option value="500">500 GB</option>
            <option value="1000">1 TB (1000 GB)</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            สิทธิ์การเข้าถึง <span class="text-red-500">*</span>
        </label>
        <select name="permission_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="read_only">อ่านอย่างเดียว (Read Only)</option>
            <option value="read_write" selected>อ่าน-เขียน (Read & Write)</option>
            <option value="full_control">ควบคุมเต็ม (Full Control)</option>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ต้องการ Backup อัตโนมัติ <span class="text-red-500">*</span>
        </label>
        <select name="backup_required" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="1" selected>ต้องการ Backup</option>
            <option value="0">ไม่ต้องการ</option>
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            แชร์ให้กับใครบ้าง
        </label>
        <textarea name="shared_with" rows="2"
                  placeholder="ระบุชื่อหรืออีเมลของผู้ที่ต้องการแชร์ (คั่นด้วย comma)"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            วัตถุประสงค์การใช้งาน <span class="text-red-500">*</span>
        </label>
        <textarea name="purpose" required rows="3"
                  placeholder="เช่น เก็บเอกสารงบประมาณประจำปี, แชร์ไฟล์งานภายในแผนก"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>
