    <!-- Footer -->
    <footer class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-gray-300 py-12 md:py-16">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 mb-10">
                <div class="space-y-4">
                    <h3 class="text-2xl font-display font-bold text-white mb-4 flex items-center">
                        <span class="w-10 h-10 bg-teal-600 rounded-lg flex items-center justify-center mr-3 text-sm">
                            <i class="fas fa-city text-white"></i>
                        </span>
                        <?php echo isset($org_name) ? htmlspecialchars($org_name) : 'เทศบาลนครรังสิต'; ?>
                    </h3>
                    <p class="text-gray-400 text-sm leading-relaxed mb-6">
                        มุ่งมั่นพัฒนาระบบบริการสาธารณะด้วยเทคโนโลยีทันสมัย เพื่อคุณภาพชีวิตที่ดีของประชาชน และการบริหารจัดการที่มีประสิทธิภาพ
                    </p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-blue-600 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-facebook-f text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-sky-500 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-twitter text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-green-600 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-line text-white"></i>
                        </a>
                        <a href="#" class="w-10 h-10 bg-gray-700 hover:bg-red-600 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-youtube text-white"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">บริการ</h5>
                    <ul class="space-y-2 text-xs md:text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">โครงการตู้น้ำดื่มอัจฉริยะ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">โครงการป้ายรถเมล์อัจฉริยะ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">โครงการรังสิตซิตี้แอพ</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">ข้อมูล</h5>
                    <ul class="space-y-2 text-xs md:text-sm">
                        <li><a href="#" class="hover:text-white transition-colors">ศูนย์ข้อมูลข่าวสาร</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">คำถามที่พบบ่อย</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">ดาวน์โหลด</a></li>
                        <li><a href="admin-login.php" class="hover:text-yellow-400 transition-colors flex items-center">
                            <i class="fas fa-user-shield mr-2"></i>Admin Panel
                        </a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="font-bold mb-3 text-white text-sm md:text-base">ติดต่อเรา</h5>
                    <p class="text-xs md:text-sm mb-2">ที่อยู่ เลขที่ 151 ถนนรังสิต-ปทุมธานี </p>
                    <p class="text-xs md:text-sm mb-2">ตำบลประชาธิปัตย์ อำเภอธัญบุรี </p>
                    <p class="text-xs md:text-sm">จังหวัดปทุมธานี 12130</p>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-6 text-center text-xs md:text-sm">
                <p>&copy; 2569 Rangsit City Municipality . All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Start Session only if not already started
        <?php if (session_status() === PHP_SESSION_NONE) echo "/* session not started in JS context */"; ?>
        
        // Mobile Menu Toggle logic
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const mobileMenu = document.getElementById('mobileMenu');

        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', function() {
                mobileMenu.classList.toggle('hidden');

                // Toggle icon between bars and times
                const icon = this.querySelector('i');
                if (mobileMenu.classList.contains('hidden')) {
                    icon.classList.remove('fa-times');
                    icon.classList.add('fa-bars');
                } else {
                    icon.classList.remove('fa-bars');
                    icon.classList.add('fa-times');
                }
            });

            // Close mobile menu when clicking outside
            document.addEventListener('click', function(event) {
                const isClickInsideMenu = mobileMenu.contains(event.target);
                const isClickOnButton = mobileMenuBtn.contains(event.target);

                if (!isClickInsideMenu && !isClickOnButton && !mobileMenu.classList.contains('hidden')) {
                    mobileMenu.classList.add('hidden');
                    mobileMenuBtn.querySelector('i').classList.remove('fa-times');
                    mobileMenuBtn.querySelector('i').classList.add('fa-bars');
                }
            });

            // Close menu when clicking on a menu item
            mobileMenu.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function() {
                    mobileMenu.classList.add('hidden');
                    mobileMenuBtn.querySelector('i').classList.remove('fa-times');
                    mobileMenuBtn.querySelector('i').classList.add('fa-bars');
                });
            });
        }
        
        <?php echo isset($extra_scripts) ? $extra_scripts : ''; ?>
    </script>
</body>
</html>