<div class="w-full max-w-2xl">
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-user-plus text-4xl text-green-700"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 font-display">สมัครสมาชิก</h1>
            <p class="text-gray-600 text-sm mt-2">สร้างบัญชีผู้ใช้งานใหม่</p>
        </div>

        <!-- Register Form -->
        <form id="registerForm" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Username -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ชื่อผู้ใช้ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="username" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="ชื่อผู้ใช้ (ภาษาอังกฤษ)">
                </div>

                <!-- Email -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        อีเมล <span class="text-red-500">*</span>
                    </label>
                    <input type="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="example@rangsit.go.th">
                </div>

                <!-- Prefix -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        คำนำหน้า <span class="text-red-500">*</span>
                    </label>
                    <select name="prefix_id" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">-- เลือกคำนำหน้า --</option>
                        <?php foreach ($prefixes as $prefix): ?>
                            <option value="<?= $prefix['prefix_id'] ?>"><?= $prefix['prefix_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- First Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ชื่อ <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="ชื่อ">
                </div>

                <!-- Last Name -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        นามสกุล <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="นามสกุล">
                </div>

                <!-- Department -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        หน่วยงาน
                    </label>
                    <select name="department_id"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">-- เลือกหน่วยงาน --</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?= $dept['department_id'] ?>"><?= $dept['department_name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Position -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ตำแหน่ง
                    </label>
                    <input type="text" name="position"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="ตำแหน่งงาน">
                </div>

                <!-- Phone -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        เบอร์โทรศัพท์
                    </label>
                    <input type="tel" name="phone"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="08X-XXX-XXXX">
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        รหัสผ่าน <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="รหัสผ่าน (อย่างน้อย 6 ตัวอักษร)">
                </div>

                <!-- Confirm Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">
                        ยืนยันรหัสผ่าน <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                           placeholder="ยืนยันรหัสผ่าน">
                </div>
            </div>

            <button type="submit" id="registerBtn"
                    class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-xl transition">
                <i class="fas fa-user-plus mr-2"></i>สมัครสมาชิก
            </button>
        </form>

        <!-- Login Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                มีบัญชีอยู่แล้ว?
                <a href="/login" class="text-green-700 hover:text-green-800 font-semibold">เข้าสู่ระบบ</a>
            </p>
        </div>
    </div>
</div>

<script>
document.getElementById('registerForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('registerBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังสมัครสมาชิก...';
    btn.disabled = true;

    const formData = new FormData(this);

    try {
        const response = await fetch('/register', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: result.message
            });

            window.location.href = result.redirect || '/login';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'สมัครสมาชิกไม่สำเร็จ',
                text: result.message
            });

            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้'
        });

        btn.innerHTML = originalText;
        btn.disabled = false;
    }
});
</script>
