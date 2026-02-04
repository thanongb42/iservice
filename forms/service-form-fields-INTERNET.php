<?php
// Load Internet Request Types from database
$internet_request_types = [];
$types_query = $conn->query("SELECT type_code, type_name, icon FROM internet_request_types WHERE is_active = 1 ORDER BY display_order ASC");
if ($types_query && $types_query->num_rows > 0) {
    while ($row = $types_query->fetch_assoc()) {
        $internet_request_types[] = $row;
    }
}

// Fallback to default if table doesn't exist or is empty
if (empty($internet_request_types)) {
    $internet_request_types = [
        ['type_code' => 'new_wifi', 'type_name' => 'ขอรหัสผ่าน WiFi ใหม่', 'icon' => 'fa-key'],
        ['type_code' => 'password_reset', 'type_name' => 'Reset รหัสผ่าน Internet', 'icon' => 'fa-sync'],
        ['type_code' => 'other', 'type_name' => 'อื่นๆ', 'icon' => 'fa-ellipsis-h']
    ];
}
?>
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ประเภทคำขอ <span class="text-red-500">*</span>
        </label>
        <select name="request_type" required
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            <option value="">-- เลือกประเภทคำขอ --</option>
            <?php foreach ($internet_request_types as $type): ?>
                <option value="<?= htmlspecialchars($type['type_code']) ?>">
                    <?= htmlspecialchars($type['type_name']) ?>
                </option>
            <?php endforeach; ?>
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

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            ปัญหาที่พบ (กรณีแจ้งปัญหา)
        </label>
        <textarea name="current_issue" rows="3"
                  placeholder="อธิบายปัญหาที่พบ เช่น ต่อไม่ได้บางครั้ง, ช้ามากในช่วงเช้า"
                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"></textarea>
    </div>
</div>
