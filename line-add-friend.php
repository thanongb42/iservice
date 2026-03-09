<?php
/**
 * LINE Add Friend Page
 * หน้าเพิ่ม RCM-iService Bot เป็นเพื่อน
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/nav_menu_loader.php';

$nav_menus  = get_menu_structure();
$nav_html   = render_nav_menu($nav_menus);

// Fetch LINE bot settings
$system_settings = [];
$sq = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($sq) {
    while ($r = $sq->fetch_assoc()) {
        $system_settings[$r['setting_key']] = $r['setting_value'];
    }
}

$basic_id   = trim($system_settings['line_bot_basic_id'] ?? '');  // e.g. @abc1234d
$org_name   = $system_settings['organization_name'] ?? 'เทศบาลนครรังสิต';
$app_name   = $system_settings['app_name'] ?? 'RCM-iService';

// Build URLs
$clean_id   = ltrim($basic_id, '@');     // abc1234d  (used for QR URL)
$add_url    = $basic_id ? 'https://line.me/R/ti/p/' . urlencode($basic_id) : '#';
$qr_url     = $clean_id ? "https://qr-official.line.me/sid/M/{$clean_id}.png" : '';

$page_title = 'เพิ่มเพื่อน LINE Bot — ' . $app_name;

include __DIR__ . '/includes/header_public.php';
?>

<!-- ───────── Hero ───────── -->
<section class="min-h-screen bg-gradient-to-br from-[#06C755]/10 via-white to-[#06C755]/5 flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md">

        <!-- Card -->
        <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">

            <!-- Green top banner -->
            <div class="bg-[#06C755] px-6 py-8 text-center relative overflow-hidden">
                <!-- Decorative circles -->
                <div class="absolute -top-6 -right-6 w-28 h-28 bg-white/10 rounded-full"></div>
                <div class="absolute -bottom-8 -left-8 w-36 h-36 bg-white/10 rounded-full"></div>

                <!-- LINE icon + bot name -->
                <div class="relative z-10">
                    <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                        <i class="fab fa-line text-[#06C755] text-5xl"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-white"><?= htmlspecialchars($app_name) ?></h1>
                    <p class="text-green-100 text-sm mt-1"><?= htmlspecialchars($org_name) ?></p>
                    <?php if ($basic_id): ?>
                    <span class="inline-block mt-2 bg-white/20 text-white text-xs font-mono px-3 py-1 rounded-full">
                        <?= htmlspecialchars($basic_id) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Body -->
            <div class="px-6 py-8 text-center">

                <?php if ($qr_url): ?>
                <!-- QR Code -->
                <p class="text-gray-500 text-sm mb-4">สแกน QR Code เพื่อเพิ่มเพื่อน</p>
                <div class="inline-block p-3 bg-white border-2 border-[#06C755]/30 rounded-2xl shadow-inner mb-6">
                    <img src="<?= htmlspecialchars($qr_url) ?>"
                         alt="LINE QR Code — <?= htmlspecialchars($app_name) ?>"
                         class="w-52 h-52 object-contain"
                         onerror="this.closest('.qr-wrapper')?.classList.add('hidden')">
                </div>

                <!-- Divider -->
                <div class="flex items-center gap-3 mb-6">
                    <div class="flex-1 h-px bg-gray-200"></div>
                    <span class="text-gray-400 text-xs">หรือ</span>
                    <div class="flex-1 h-px bg-gray-200"></div>
                </div>
                <?php endif; ?>

                <!-- Add Friend Button (LINE-style) -->
                <?php if ($basic_id): ?>
                <a href="<?= htmlspecialchars($add_url) ?>"
                   target="_blank" rel="noopener"
                   class="flex items-center justify-center gap-3 w-full bg-[#06C755] hover:bg-[#05b34c] active:bg-[#049840] text-white font-bold text-lg py-4 px-6 rounded-xl shadow-lg shadow-[#06C755]/30 transition-all duration-200 hover:scale-[1.02] active:scale-100 mb-4">
                    <i class="fab fa-line text-2xl"></i>
                    เพิ่มเพื่อน (Add Friend)
                </a>

                <!-- Copy ID button -->
                <button onclick="copyBasicId()" id="copyBtn"
                        class="flex items-center justify-center gap-2 w-full border border-gray-200 hover:border-[#06C755] text-gray-600 hover:text-[#06C755] text-sm py-3 px-6 rounded-xl transition-all duration-200 mb-6">
                    <i class="fas fa-copy"></i>
                    คัดลอก Basic ID: <span class="font-mono font-semibold"><?= htmlspecialchars($basic_id) ?></span>
                </button>
                <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 text-yellow-700 text-sm mb-6">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    ยังไม่ได้ตั้งค่า LINE Bot Basic ID<br>
                    <span class="text-xs text-yellow-600">กรุณาตั้งค่าใน Admin → System Settings → LINE</span>
                </div>
                <?php endif; ?>

                <!-- Steps -->
                <div class="bg-gray-50 rounded-2xl p-5 text-left">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-4">วิธีเพิ่มเพื่อน</p>
                    <div class="space-y-3">
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-7 h-7 bg-[#06C755] text-white text-xs font-bold rounded-full flex items-center justify-center">1</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">เปิดแอป LINE</p>
                                <p class="text-xs text-gray-500">บนมือถือ iOS หรือ Android</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-7 h-7 bg-[#06C755] text-white text-xs font-bold rounded-full flex items-center justify-center">2</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">สแกน QR หรือกดปุ่ม "เพิ่มเพื่อน"</p>
                                <p class="text-xs text-gray-500">กดปุ่มสีเขียวด้านบน หรือสแกน QR Code</p>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex-shrink-0 w-7 h-7 bg-[#06C755] text-white text-xs font-bold rounded-full flex items-center justify-center">3</span>
                            <div>
                                <p class="text-sm font-medium text-gray-800">เชื่อมต่อบัญชีในระบบ</p>
                                <p class="text-xs text-gray-500">Login → โปรไฟล์ → เชื่อมต่อบัญชี LINE เพื่อรับแจ้งเตือน</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notification preview -->
                <div class="mt-5 rounded-2xl border border-gray-100 p-4 text-left">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">แจ้งเตือนที่คุณจะได้รับ</p>
                    <div class="space-y-2">
                        <?php
                        $notifications = [
                            ['icon' => 'fa-clipboard-list text-blue-500',  'text' => 'เมื่อมีการมอบหมายงานใหม่'],
                            ['icon' => 'fa-times-circle text-red-500',     'text' => 'เมื่องานถูกยกเลิกการมอบหมาย'],
                            ['icon' => 'fa-check-circle text-green-500',   'text' => 'เมื่องานเสร็จสิ้น'],
                        ];
                        foreach ($notifications as $n): ?>
                        <div class="flex items-center gap-3 text-sm text-gray-600">
                            <i class="fas <?= $n['icon'] ?> w-4 text-center"></i>
                            <span><?= $n['text'] ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

            </div><!-- /body -->

            <!-- Footer note -->
            <div class="px-6 pb-6 text-center">
                <p class="text-xs text-gray-400">
                    หลังเพิ่มเพื่อนแล้ว ต้อง
                    <a href="<?php
                        if (isset($_SESSION['user_id'])) {
                            echo 'admin/user_profile.php';
                        } else {
                            echo 'login.php';
                        }
                    ?>" class="text-[#06C755] underline font-medium">เชื่อมต่อบัญชี LINE ในโปรไฟล์</a>
                    ด้วยจึงจะรับแจ้งเตือนได้
                </p>
            </div>

        </div><!-- /card -->

        <!-- Back link -->
        <div class="mt-6 text-center">
            <a href="index.php" class="inline-flex items-center gap-2 text-gray-500 hover:text-gray-700 text-sm transition-colors">
                <i class="fas fa-arrow-left text-xs"></i>
                กลับหน้าหลัก
            </a>
        </div>

    </div>
</section>

<script>
function copyBasicId() {
    const id = <?= json_encode($basic_id) ?>;
    if (!id) return;
    navigator.clipboard.writeText(id).then(() => {
        const btn = document.getElementById('copyBtn');
        const original = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check text-green-500"></i> คัดลอกแล้ว!';
        btn.classList.add('border-green-400', 'text-green-600');
        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('border-green-400', 'text-green-600');
        }, 2000);
    }).catch(() => {
        prompt('คัดลอก Basic ID:', id);
    });
}
</script>

<?php include __DIR__ . '/includes/footer_public.php'; ?>
