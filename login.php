<?php
/**
 * Login Page — Full-page split layout
 */

session_start();

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

require_once 'config/database.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch system settings
$system_settings = [];
if (isset($conn)) {
    $settings_query = $conn->query("SELECT setting_key, setting_value FROM system_settings");
    if ($settings_query) {
        while ($row = $settings_query->fetch_assoc()) {
            $system_settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

$app_name  = !empty($system_settings['app_name'])           ? $system_settings['app_name']           : 'ระบบบริการดิจิทัล';
$org_name  = !empty($system_settings['organization_name'])  ? $system_settings['organization_name']  : 'เทศบาลนครรังสิต';
$logo_path = !empty($system_settings['logo_image']) && file_exists($system_settings['logo_image'])
           ? $system_settings['logo_image']
           : 'images/logo/rangsit-big-logo.png';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// ── LINE Login OAuth ────────────────────────────────────────────────────────

define('LINE_STATE_SECRET', 'RCM_iService_LINE_OAuth_2026_@rangsitcity');

function detectProtocol(): string {
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https') return 'https';
    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && $_SERVER['HTTP_X_FORWARDED_SSL'] === 'on') return 'https';
    if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) === 'on') return 'https';
    if (($_SERVER['SERVER_PORT'] ?? '') == '443') return 'https';
    return 'http';
}

if (isset($_GET['action']) && $_GET['action'] === 'line_login') {
    $channel_id = $system_settings['line_login_channel_id'] ?? '';
    if (empty($channel_id)) {
        header('Location: login.php?error=' . urlencode('LINE Login ยังไม่ได้ตั้งค่า กรุณาติดต่อผู้ดูแลระบบ'));
        exit;
    }
    $ts      = time();
    $payload = 'login.' . $ts;
    $hmac    = substr(hash_hmac('sha256', $payload, LINE_STATE_SECRET), 0, 24);
    $state   = 'login.' . $ts . '.' . $hmac;

    $saved_url    = $system_settings['line_callback_url'] ?? '';
    $callback_url = '';
    if (!empty($saved_url)) {
        $derived = str_replace('/admin/line_callback.php', '/line_login_callback.php', $saved_url);
        if ($derived !== $saved_url) $callback_url = $derived;
    }
    if (empty($callback_url)) {
        $protocol     = detectProtocol();
        $dir          = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
        $callback_url = $protocol . '://' . $_SERVER['HTTP_HOST'] . $dir . '/line_login_callback.php';
    }

    header('Location: https://access.line.me/oauth2/v2.1/authorize'
        . '?response_type=code'
        . '&client_id='    . urlencode($channel_id)
        . '&redirect_uri=' . urlencode($callback_url)
        . '&state='        . urlencode($state)
        . '&scope=profile%20openid');
    exit;
}

$line_error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';

// ── POST: Login ─────────────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    header('Content-Type: application/json');

    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(['success' => false, 'message' => 'Security validation failed (CSRF)']);
        exit;
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'กรุณากรอกชื่อผู้ใช้และรหัสผ่าน']);
        exit;
    }

    try {
        $stmt = $conn->prepare("SELECT u.user_id, u.first_name, u.last_name, u.email, u.password, u.role, u.status, p.prefix_name, u.username
                                FROM users u
                                LEFT JOIN prefixes p ON u.prefix_id = p.prefix_id
                                WHERE u.email = ? OR u.username = ?");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            sleep(1);
            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
            exit;
        }

        $user = $result->fetch_assoc();

        if ($user['status'] !== 'active') {
            echo json_encode(['success' => false, 'message' => 'บัญชีของคุณถูกระงับ กรุณาติดต่อผู้ดูแลระบบ']);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง']);
            exit;
        }

        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['user_id'];
        $_SESSION['username']   = $user['username'];
        $_SESSION['prefix_name']= $user['prefix_name'] ?? '';
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['email']      = $user['email'];
        $_SESSION['role']       = $user['role'];
        $_SESSION['full_name']  = trim(($user['prefix_name'] ?? '') . ' ' . $user['first_name'] . ' ' . $user['last_name']);

        $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $update_stmt->bind_param("i", $user['user_id']);
        $update_stmt->execute();

        $redirect = $user['role'] === 'admin' ? 'admin/index.php'
                  : ($user['role'] === 'staff' ? 'admin/my_tasks.php' : 'index.php');

        echo json_encode(['success' => true, 'message' => 'เข้าสู่ระบบสำเร็จ', 'redirect' => $redirect]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด: ' . $e->getMessage()]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ — <?= htmlspecialchars($app_name) ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($logo_path) ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { font-family: 'Sarabun', sans-serif; }

        /* ── Left hero panel ── */
        .hero-panel {
            background: linear-gradient(145deg, #064e3b 0%, #065f46 30%, #0f766e 70%, #0d9488 100%);
            position: relative;
            overflow: hidden;
        }
        .hero-panel::before {
            content: '';
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse at 20% 20%, rgba(20,184,166,.25) 0%, transparent 60%),
                radial-gradient(ellipse at 80% 80%, rgba(6,78,59,.4) 0%, transparent 60%);
        }
        /* Animated circles */
        .blob {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: .18;
            animation: blobFloat 8s ease-in-out infinite;
        }
        .blob-1 { width:380px;height:380px;background:#2dd4bf;top:-80px;left:-80px;animation-delay:0s; }
        .blob-2 { width:280px;height:280px;background:#34d399;bottom:60px;right:-60px;animation-delay:3s; }
        .blob-3 { width:200px;height:200px;background:#6ee7b7;top:50%;left:55%;animation-delay:5s; }
        @keyframes blobFloat {
            0%,100% { transform: translate(0,0) scale(1); }
            50%      { transform: translate(15px,-20px) scale(1.05); }
        }

        /* Grid decoration */
        .grid-bg {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
            background-size: 40px 40px;
        }

        /* Feature cards */
        .feature-card {
            background: rgba(255,255,255,.08);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,.15);
            border-radius: 12px;
            transition: all .3s ease;
        }
        .feature-card:hover {
            background: rgba(255,255,255,.14);
            transform: translateX(4px);
        }

        /* Stats */
        .stat-card {
            background: rgba(255,255,255,.1);
            border: 1px solid rgba(255,255,255,.2);
            border-radius: 10px;
        }

        /* ── Right form panel ── */
        .form-panel {
            background: #f8fafc;
        }
        .input-wrap {
            position: relative;
        }
        .input-wrap .icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #0f766e;
            z-index: 2;
        }
        .input-wrap input {
            padding-left: 2.75rem;
            transition: all .25s;
        }
        .input-wrap input:focus {
            outline: none;
            border-color: #0f766e;
            box-shadow: 0 0 0 3px rgba(15,118,110,.12);
        }
        .btn-primary {
            background: linear-gradient(135deg, #065f46, #0f766e);
            transition: all .3s;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #064e3b, #0d9488);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(15,118,110,.35);
        }
        .btn-primary:active { transform: translateY(0); }

        .btn-line {
            background: #06c755;
            transition: all .3s;
        }
        .btn-line:hover {
            background: #05a847;
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(6,199,85,.35);
        }

        /* Fade animations */
        .fade-left  { animation: fadeLeft  .7s ease both; }
        .fade-right { animation: fadeRight .7s ease both; }
        .fade-up    { animation: fadeUp    .6s ease both; }
        @keyframes fadeLeft  { from{opacity:0;transform:translateX(-30px)} to{opacity:1;transform:none} }
        @keyframes fadeRight { from{opacity:0;transform:translateX(30px)}  to{opacity:1;transform:none} }
        @keyframes fadeUp    { from{opacity:0;transform:translateY(20px)}  to{opacity:1;transform:none} }

        .delay-1 { animation-delay:.1s }
        .delay-2 { animation-delay:.2s }
        .delay-3 { animation-delay:.3s }
        .delay-4 { animation-delay:.4s }
        .delay-5 { animation-delay:.5s }

        /* Scrollbar */
        .form-panel::-webkit-scrollbar { width:4px; }
        .form-panel::-webkit-scrollbar-thumb { background:#cbd5e1;border-radius:4px; }
    </style>
</head>
<body class="overflow-hidden">
<div class="flex h-screen">

    <!-- ══════════════════ LEFT — HERO PANEL ══════════════════ -->
    <div class="hero-panel hidden lg:flex lg:w-[58%] flex-col justify-between p-10 xl:p-14 relative text-white">
        <!-- Decorations -->
        <div class="grid-bg"></div>
        <div class="blob blob-1"></div>
        <div class="blob blob-2"></div>
        <div class="blob blob-3"></div>

        <!-- Top: Logo + Name -->
        <div class="relative z-10 fade-left">
            <div class="flex items-center gap-4 mb-8">
                <div class="w-16 h-16 rounded-2xl bg-white/15 border border-white/25 flex items-center justify-center shadow-lg p-1">
                    <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="text-xl font-bold leading-tight"><?= htmlspecialchars($app_name) ?></h1>
                    <p class="text-teal-200 text-sm"><?= htmlspecialchars($org_name) ?></p>
                </div>
            </div>

            <!-- Headline -->
            <div class="mb-8">
                <h2 class="text-4xl xl:text-5xl font-extrabold leading-tight mb-3">
                    ระบบจัดการ<br>
                    <span class="text-teal-300">บริการดิจิทัล</span>
                </h2>
                <p class="text-teal-100 text-base leading-relaxed max-w-md">
                    แพลตฟอร์มกลางสำหรับรับ-จัดการ-ติดตามคำขอบริการ
                    ของเทศบาลนครรังสิต อย่างมีประสิทธิภาพ
                </p>
            </div>

            <!-- Feature list -->
            <div class="space-y-3 mb-8">
                <?php
                $features = [
                    ['icon'=>'fa-inbox',         'color'=>'text-teal-300',  'title'=>'รับคำขอบริการออนไลน์',        'desc'=>'ประชาชนยื่นคำขอผ่านเว็บได้ทันที'],
                    ['icon'=>'fa-tasks',          'color'=>'text-emerald-300','title'=>'มอบหมายและติดตามงาน',         'desc'=>'ระบบ assign งานให้เจ้าหน้าที่แบบ real-time'],
                    ['icon'=>'fa-chart-bar',      'color'=>'text-cyan-300',  'title'=>'รายงานและสถิติ',               'desc'=>'Dashboard ภาพรวมและ export รายงาน'],
                    ['icon'=>'fa-bell',           'color'=>'text-yellow-300','title'=>'แจ้งเตือนผ่าน LINE',           'desc'=>'เจ้าหน้าที่รับแจ้งเตือนงานใหม่ทันที'],
                    ['icon'=>'fa-building',       'color'=>'text-purple-300','title'=>'จัดการหน่วยงาน 4 ระดับ',      'desc'=>'สำนัก → กอง → ฝ่าย → งาน'],
                ];
                foreach ($features as $i => $f): ?>
                <div class="feature-card flex items-center gap-3 px-4 py-3 fade-left delay-<?= $i+1 ?>">
                    <div class="w-9 h-9 rounded-lg bg-white/10 flex items-center justify-center flex-shrink-0">
                        <i class="fas <?= $f['icon'] ?> <?= $f['color'] ?> text-sm"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-sm leading-tight"><?= $f['title'] ?></p>
                        <p class="text-teal-200 text-xs"><?= $f['desc'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Bottom: Stats -->
        <div class="relative z-10 fade-left delay-5">
            <?php
            $total_requests = 0; $total_staff = 0; $total_services = 0;
            if (isset($conn)) {
                $r = $conn->query("SELECT COUNT(*) as c FROM service_requests"); if($r) $total_requests = $r->fetch_assoc()['c'] ?? 0;
                $r = $conn->query("SELECT COUNT(*) as c FROM users WHERE role IN ('admin','staff') AND status='active'"); if($r) $total_staff = $r->fetch_assoc()['c'] ?? 0;
                $r = $conn->query("SELECT COUNT(*) as c FROM my_service"); if($r) $total_services = $r->fetch_assoc()['c'] ?? 0;
            }
            ?>
            <div class="grid grid-cols-3 gap-3">
                <div class="stat-card text-center py-3 px-2">
                    <p class="text-2xl font-extrabold text-teal-300"><?= number_format($total_requests) ?></p>
                    <p class="text-xs text-teal-200 mt-1">คำขอทั้งหมด</p>
                </div>
                <div class="stat-card text-center py-3 px-2">
                    <p class="text-2xl font-extrabold text-emerald-300"><?= number_format($total_staff) ?></p>
                    <p class="text-xs text-teal-200 mt-1">เจ้าหน้าที่</p>
                </div>
                <div class="stat-card text-center py-3 px-2">
                    <p class="text-2xl font-extrabold text-cyan-300"><?= number_format($total_services) ?></p>
                    <p class="text-xs text-teal-200 mt-1">ประเภทบริการ</p>
                </div>
            </div>
            <p class="text-center text-teal-300/60 text-xs mt-4">
                <i class="fas fa-shield-alt mr-1"></i>
                ระบบรักษาความปลอดภัยมาตรฐานสากล
            </p>
        </div>
    </div>

    <!-- ══════════════════ RIGHT — FORM PANEL ══════════════════ -->
    <div class="form-panel w-full lg:w-[42%] flex flex-col justify-center overflow-y-auto px-6 sm:px-10 xl:px-16 py-10">

        <!-- Mobile logo (shown only < lg) -->
        <div class="lg:hidden flex items-center justify-center gap-3 mb-8 fade-up">
            <img src="<?= htmlspecialchars($logo_path) ?>" alt="Logo" class="w-12 h-12 object-contain">
            <div>
                <p class="font-bold text-gray-800"><?= htmlspecialchars($app_name) ?></p>
                <p class="text-xs text-gray-500"><?= htmlspecialchars($org_name) ?></p>
            </div>
        </div>

        <div class="max-w-sm mx-auto w-full">

            <!-- Heading -->
            <div class="mb-8 fade-right">
                <h2 class="text-3xl font-extrabold text-gray-800 mb-1">ยินดีต้อนรับ</h2>
                <p class="text-gray-500 text-sm">เข้าสู่ระบบเพื่อจัดการบริการดิจิทัล</p>
                <div class="h-1 w-12 bg-gradient-to-r from-teal-700 to-teal-400 rounded-full mt-3"></div>
            </div>

            <!-- Login Form -->
            <form id="loginForm" class="space-y-5 fade-right delay-1">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="login" value="1">

                <!-- Username -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">ชื่อผู้ใช้หรืออีเมล</label>
                    <div class="input-wrap">
                        <i class="fas fa-user icon text-sm"></i>
                        <input type="text" name="username" id="username"
                               class="w-full py-3 pl-10 pr-4 border border-gray-200 rounded-xl bg-white text-gray-800 text-sm shadow-sm"
                               placeholder="username หรือ email" required autocomplete="username">
                    </div>
                </div>

                <!-- Password -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1.5">รหัสผ่าน</label>
                    <div class="input-wrap">
                        <i class="fas fa-lock icon text-sm"></i>
                        <input type="password" name="password" id="password"
                               class="w-full py-3 pl-10 pr-12 border border-gray-200 rounded-xl bg-white text-gray-800 text-sm shadow-sm"
                               placeholder="รหัสผ่าน" required autocomplete="current-password">
                        <button type="button" onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-teal-700 transition z-10">
                            <i class="fas fa-eye text-sm" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember / Forgot -->
                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2 text-gray-600 cursor-pointer select-none">
                        <input type="checkbox" name="remember" class="rounded text-teal-600 border-gray-300 focus:ring-teal-500">
                        จดจำการเข้าสู่ระบบ
                    </label>
                    <a href="forgot_password.php" class="text-teal-700 hover:text-teal-900 font-medium">ลืมรหัสผ่าน?</a>
                </div>

                <!-- Submit -->
                <button type="submit" id="btnSubmit"
                        class="btn-primary w-full text-white font-bold py-3 rounded-xl shadow-md flex items-center justify-center gap-2">
                    <i class="fas fa-sign-in-alt" id="btnIcon"></i>
                    <span id="btnText">เข้าสู่ระบบ</span>
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6 fade-right delay-2">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center">
                    <span class="px-4 bg-slate-50 text-gray-400 text-xs font-medium">หรือเข้าสู่ระบบด้วย</span>
                </div>
            </div>

            <!-- LINE Login -->
            <?php if (!empty($system_settings['line_login_channel_id'])): ?>
            <a href="login.php?action=line_login"
               class="btn-line w-full flex items-center justify-center gap-2 text-white font-bold py-3 rounded-xl shadow-md mb-6 fade-right delay-3">
                <i class="fab fa-line text-xl"></i>
                เข้าสู่ระบบด้วย LINE
            </a>
            <?php endif; ?>

            <!-- Register -->
            <div class="text-center text-sm text-gray-500 fade-right delay-4">
                ยังไม่มีบัญชี?
                <a href="register.php" class="text-teal-700 hover:text-teal-900 font-semibold ml-1">สมัครสมาชิก</a>
            </div>

            <!-- Back to home -->
            <div class="text-center mt-4 fade-right delay-5">
                <a href="index.php" class="text-gray-400 hover:text-teal-700 transition text-xs inline-flex items-center gap-1">
                    <i class="fas fa-arrow-left text-xs"></i> กลับหน้าหลัก
                </a>
            </div>

            <!-- System role badges -->
            <div class="mt-8 pt-6 border-t border-gray-200 fade-right delay-5">
                <p class="text-xs text-gray-400 text-center mb-3">ระดับการเข้าถึงในระบบ</p>
                <div class="flex justify-center gap-2">
                    <span class="inline-flex items-center gap-1 bg-purple-50 text-purple-700 border border-purple-200 text-xs font-medium px-3 py-1.5 rounded-full">
                        <i class="fas fa-crown text-xs"></i> Admin
                    </span>
                    <span class="inline-flex items-center gap-1 bg-teal-50 text-teal-700 border border-teal-200 text-xs font-medium px-3 py-1.5 rounded-full">
                        <i class="fas fa-user-tie text-xs"></i> เจ้าหน้าที่
                    </span>
                    <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 border border-blue-200 text-xs font-medium px-3 py-1.5 rounded-full">
                        <i class="fas fa-user text-xs"></i> ประชาชน
                    </span>
                </div>
            </div>
        </div>
    </div>

</div><!-- end flex -->

<script>
    // LINE error alert
    <?php if (!empty($line_error)): ?>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: 'error',
            title: 'เข้าสู่ระบบด้วย LINE ไม่สำเร็จ',
            text: <?= json_encode($line_error) ?>,
            confirmButtonColor: '#0f766e'
        });
    });
    <?php endif; ?>

    function togglePassword() {
        const input = document.getElementById('password');
        const icon  = document.getElementById('toggleIcon');
        input.type = input.type === 'password' ? 'text' : 'password';
        icon.classList.toggle('fa-eye');
        icon.classList.toggle('fa-eye-slash');
    }

    document.getElementById('loginForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const btn     = document.getElementById('btnSubmit');
        const btnText = document.getElementById('btnText');
        const btnIcon = document.getElementById('btnIcon');

        btn.disabled = true;
        btnText.textContent = 'กำลังตรวจสอบ…';
        btnIcon.className = 'fas fa-spinner fa-spin';

        try {
            const res    = await fetch('login.php', { method:'POST', body: new FormData(this) });
            const result = await res.json();

            if (result.success) {
                btnText.textContent = 'สำเร็จ!';
                btnIcon.className = 'fas fa-check';
                Swal.fire({
                    icon: 'success',
                    title: 'ยินดีต้อนรับ!',
                    text: result.message,
                    showConfirmButton: false,
                    timer: 1200
                }).then(() => { window.location.href = result.redirect; });
            } else {
                Swal.fire({ icon:'error', title:'เข้าสู่ระบบไม่สำเร็จ', text: result.message, confirmButtonColor:'#0f766e' });
                btn.disabled = false;
                btnText.textContent = 'เข้าสู่ระบบ';
                btnIcon.className = 'fas fa-sign-in-alt';
            }
        } catch {
            Swal.fire({ icon:'error', title:'เกิดข้อผิดพลาด!', text:'ไม่สามารถเชื่อมต่อกับเซิร์ฟเวอร์ได้' });
            btn.disabled = false;
            btnText.textContent = 'เข้าสู่ระบบ';
            btnIcon.className = 'fas fa-sign-in-alt';
        }
    });

    document.getElementById('password').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') document.getElementById('loginForm').dispatchEvent(new Event('submit'));
    });
</script>
</body>
</html>
