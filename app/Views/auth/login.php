<div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-2xl p-8">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 mx-auto bg-green-100 rounded-full flex items-center justify-center mb-4">
                <i class="fas fa-user-circle text-4xl text-green-700"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-800 font-display">เข้าสู่ระบบ</h1>
            <p class="text-gray-600 text-sm mt-2">ระบบบริการอิเล็กทรอนิกส์ภายใน</p>
            <p class="text-green-700 font-semibold text-sm">เทศบาลนครรังสิต</p>
        </div>

        <!-- Login Form -->
        <form id="loginForm" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-user mr-2"></i>ชื่อผู้ใช้ หรือ อีเมล
                </label>
                <input type="text" name="username" id="username" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       placeholder="กรอกชื่อผู้ใช้หรืออีเมล">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="fas fa-lock mr-2"></i>รหัสผ่าน
                </label>
                <input type="password" name="password" id="password" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                       placeholder="กรอกรหัสผ่าน">
            </div>

            <div class="flex items-center justify-between">
                <label class="flex items-center">
                    <input type="checkbox" class="w-4 h-4 text-green-600 border-gray-300 rounded focus:ring-green-500">
                    <span class="ml-2 text-sm text-gray-600">จดจำฉันไว้</span>
                </label>
                <a href="#" class="text-sm text-green-700 hover:text-green-800 font-semibold">ลืมรหัสผ่าน?</a>
            </div>

            <button type="submit" id="loginBtn"
                    class="w-full bg-green-700 hover:bg-green-800 text-white font-bold py-3 rounded-lg shadow-lg hover:shadow-xl transition transform hover:-translate-y-0.5">
                <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
            </button>
        </form>

        <!-- Register Link -->
        <div class="mt-6 text-center">
            <p class="text-sm text-gray-600">
                ยังไม่มีบัญชี?
                <a href="/register" class="text-green-700 hover:text-green-800 font-semibold">สมัครสมาชิก</a>
            </p>
        </div>

        <!-- Home Link -->
        <div class="mt-4 text-center">
            <a href="/" class="text-sm text-gray-500 hover:text-gray-700">
                <i class="fas fa-home mr-1"></i>กลับหน้าแรก
            </a>
        </div>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('loginBtn');
    const originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังเข้าสู่ระบบ...';
    btn.disabled = true;

    const formData = new FormData(this);

    try {
        const response = await fetch('/login', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            await Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: result.message,
                timer: 1500,
                showConfirmButton: false
            });

            window.location.href = result.redirect || '/';
        } else {
            Swal.fire({
                icon: 'error',
                title: 'เข้าสู่ระบบไม่สำเร็จ',
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
