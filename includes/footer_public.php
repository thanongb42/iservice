<?php
// Ensure database connection
require_once __DIR__ . '/../config/database.php';

// Fetch system settings if not already fetched
if (!isset($footer_org_name) || !isset($footer_logo)) {
    $system_settings = [];
    if (isset($conn)) {
        $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
        if ($settings_query) {
            while ($row = $settings_query->fetch_assoc()) {
                $system_settings[$row['setting_key']] = $row['setting_value'];
            }
        }
    }

    $footer_org_name = !empty($system_settings['organization_name']) ? $system_settings['organization_name'] : 'เทศบาลนครรังสิต';
    
    // Get logo from database
    $db_logo = !empty($system_settings['logo_image']) ? $system_settings['logo_image'] : '';
    if (!empty($db_logo) && file_exists(__DIR__ . '/../' . $db_logo)) {
        $footer_logo = $db_logo;
    } else {
        $footer_logo = 'images/logo/rangsit-big-logo.png';
    }
}
?>
    <!-- Footer -->
    <footer class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 text-gray-300 pt-12 pb-6 md:pt-16 md:pb-8">
        <div class="container mx-auto px-4">

            <!-- Top grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10 mb-10">

                <!-- Col 1: Brand + social -->
                <div class="space-y-5">
                    <div class="flex items-center gap-3">
                        <img src="<?php echo htmlspecialchars($footer_logo); ?>" alt="Logo" class="w-12 h-12 object-contain rounded-lg flex-shrink-0">
                        <div>
                            <div class="text-white font-bold text-base leading-tight"><?php echo htmlspecialchars($footer_org_name); ?></div>
                            <div class="text-gray-400 text-xs mt-0.5">ติดตามข่าวสารเทศบาลนครรังสิต</div>
                        </div>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">
                        มุ่งมั่นพัฒนาระบบบริการสาธารณะด้วยเทคโนโลยีทันสมัย เพื่อคุณภาพชีวิตที่ดีของประชาชน
                    </p>
                    <!-- Social icons -->
                    <div class="flex flex-wrap gap-3">
                        <a href="https://www.rangsitcity.go.th" target="_blank" rel="noopener"
                           title="เว็บไซต์เทศบาลนครรังสิต"
                           class="w-10 h-10 bg-teal-700 hover:bg-teal-500 rounded-full flex items-center justify-center transition-colors" >
                            <i class="fas fa-globe text-white text-sm"></i>
                        </a>
                        <a href="https://lin.ee/mNAHC1Z" target="_blank" rel="noopener"
                           title="Line Official @rangsitcity"
                           class="w-10 h-10 bg-green-700 hover:bg-green-500 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-line text-white text-sm"></i>
                        </a>
                        <a href="https://www.facebook.com/rangsitcity2016" target="_blank" rel="noopener"
                           title="Facebook Page เทศบาลนครรังสิต"
                           class="w-10 h-10 bg-blue-700 hover:bg-blue-500 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-facebook-f text-white text-sm"></i>
                        </a>
                        <a href="https://youtube.com/@rangsitcity2016/" target="_blank" rel="noopener"
                           title="YouTube เทศบาลนครรังสิต"
                           class="w-10 h-10 bg-red-700 hover:bg-red-500 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-youtube text-white text-sm"></i>
                        </a>
                        <a href="https://www.tiktok.com/@rangsitcity_official" target="_blank" rel="noopener"
                           title="TikTok @rangsitcity_official"
                           class="w-10 h-10 bg-gray-700 hover:bg-gray-500 rounded-full flex items-center justify-center transition-colors">
                            <i class="fab fa-tiktok text-white text-sm"></i>
                        </a>
                    </div>
                </div>

                <!-- Col 2: Contact info -->
                <div>
                    <h5 class="font-bold mb-4 text-white text-sm uppercase tracking-wide">ติดต่อเรา</h5>
                    <ul class="space-y-3 text-sm">
                        <li class="flex items-start gap-3">
                            <i class="fas fa-map-marker-alt text-teal-400 mt-0.5 w-4 text-center flex-shrink-0"></i>
                            <span class="text-gray-400">เลขที่ 151 ถนนรังสิต-ปทุมธานี ต.ประชาธิปัตย์ อ.ธัญบุรี จ.ปทุมธานี 12130</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-phone-alt text-teal-400 w-4 text-center flex-shrink-0"></i>
                            <a href="tel:025676000" class="text-gray-400 hover:text-white transition-colors">0 2567 6000</a>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-envelope text-teal-400 w-4 text-center flex-shrink-0"></i>
                            <a href="mailto:saraban@rangsitcity.go.th" class="text-gray-400 hover:text-white transition-colors">saraban@rangsitcity.go.th</a>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fab fa-line text-green-400 w-4 text-center flex-shrink-0"></i>
                            <a href="https://lin.ee/mNAHC1Z" target="_blank" rel="noopener" class="text-gray-400 hover:text-white transition-colors">@rangsitcity</a>
                        </li>
                        <li class="flex items-center gap-3">
                            <i class="fas fa-globe text-teal-400 w-4 text-center flex-shrink-0"></i>
                            <a href="https://www.rangsitcity.go.th" target="_blank" rel="noopener" class="text-gray-400 hover:text-white transition-colors">www.rangsitcity.go.th</a>
                        </li>
                    </ul>
                </div>

                <!-- Col 3: Rangsit City App -->
                <div>
                    <h5 class="font-bold mb-4 text-white text-sm uppercase tracking-wide">Rangsit City App</h5>
                    <p class="text-gray-400 text-sm mb-4">แจ้งเรื่องร้องเรียน หรือขอรับบริการสาธารณะ ได้ที่แอปพลิเคชัน</p>
                    <div class="flex flex-col gap-3">
                        <a href="https://apps.apple.com/app/rangsit-city-app/id6472018970" target="_blank" rel="noopener"
                           class="flex items-center gap-3 bg-gray-800 hover:bg-gray-700 border border-gray-600 hover:border-gray-400 rounded-xl px-4 py-2.5 transition-colors">
                            <i class="fab fa-apple text-white text-2xl flex-shrink-0"></i>
                            <div>
                                <div class="text-gray-400 text-xs">Download on the</div>
                                <div class="text-white text-sm font-semibold leading-tight">App Store</div>
                            </div>
                        </a>
                        <a href="https://play.google.com/store/apps/details?id=th.go.rangsitcity.app" target="_blank" rel="noopener"
                           class="flex items-center gap-3 bg-gray-800 hover:bg-gray-700 border border-gray-600 hover:border-gray-400 rounded-xl px-4 py-2.5 transition-colors">
                            <i class="fab fa-google-play text-white text-2xl flex-shrink-0"></i>
                            <div>
                                <div class="text-gray-400 text-xs">Get it on</div>
                                <div class="text-white text-sm font-semibold leading-tight">Google Play</div>
                            </div>
                        </a>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-700">
                        <a href="login.php" class="flex items-center gap-2 text-gray-500 hover:text-yellow-400 text-xs transition-colors">
                            <i class="fas fa-user-shield"></i> Admin Panel
                        </a>
                    </div>
                </div>

            </div>

            <!-- Bottom bar -->
            <div class="border-t border-gray-700 pt-6 flex flex-col md:flex-row items-center justify-between gap-2 text-xs text-gray-500">
                <p>&copy; 2569 เทศบาลนครรังสิต (Rangsit City Municipality). All rights reserved.</p>
                <p>iService — ระบบรับบริการดิจิทัล</p>
            </div>
        </div>
    </footer>

<?php
// Privacy consent + survey widget (ทุก public page)
if (!isset($privacy_category))   $privacy_category   = 'general';
if (!isset($privacy_page_title)) $privacy_page_title = '';
require_once __DIR__ . '/privacy_consent.php';
?>
    <script>
        // Mobile Menu Toggle
        if (!window.mobileMenuInitialized) {
            window.mobileMenuInitialized = true;
            
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
        }
        
        <?php echo isset($extra_scripts) ? $extra_scripts : ''; ?>
    </script>
</body>
</html>