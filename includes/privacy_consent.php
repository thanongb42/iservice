<?php
/**
 * Privacy Consent Banner + Visitor Tracker + Satisfaction Survey Widget
 * วิธีใช้: <?php require_once __DIR__ . '/includes/privacy_consent.php'; ?>
 * วางไว้ก่อน </body>
 *
 * ตัวแปรที่ตั้งล่วงหน้าได้:
 *   $privacy_category = 'pm25' | 'iservice' | 'general'  (default: 'general')
 *   $privacy_page_title = 'ชื่อหน้า'
 */
if (defined('RSSC_CONSENT_LOADED')) return;
define('RSSC_CONSENT_LOADED', true);

$_priv_cat   = $privacy_category  ?? 'general';
$_priv_title = $privacy_page_title ?? '';
// Base URL: auto-detect จาก script path (ใช้ได้ทั้ง local /iservice/ และ production /)
$_script_dir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$_base_url   = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http')
               . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_script_dir;
?>
<!-- ═══ Privacy Consent + Tracker + Survey ═══════════════════════════════ -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ── Cookie Banner ─────────────────────────────────────────── */
#rssc-consent{
    position:fixed;bottom:0;left:0;right:0;z-index:9999;
    background:#0f172a;color:#e2e8f0;
    padding:16px 24px;
    display:flex;flex-wrap:wrap;align-items:center;gap:12px;
    box-shadow:0 -4px 20px rgba(0,0,0,.35);
    font-family:'Kanit',Tahoma,sans-serif;font-size:13px;
    border-top:2px solid #0d9488;
}
#rssc-consent a{color:#5eead4;text-decoration:underline}
#rssc-consent-btns{display:flex;gap:8px;flex-shrink:0;margin-left:auto}
.rssc-btn-accept{background:#0d9488;color:#fff;border:none;border-radius:8px;padding:8px 20px;font-size:13px;font-family:inherit;cursor:pointer;font-weight:600}
.rssc-btn-accept:hover{background:#0f766e}
.rssc-btn-decline{background:transparent;color:#94a3b8;border:1px solid #334155;border-radius:8px;padding:8px 16px;font-size:13px;font-family:inherit;cursor:pointer}
.rssc-btn-decline:hover{background:#1e293b;color:#e2e8f0}

/* ── Survey Widget ─────────────────────────────────────────── */
#rssc-survey-btn{
    position:fixed;bottom:24px;right:24px;z-index:9000;
    background:#0d9488;color:#fff;border:none;border-radius:50px;
    padding:10px 18px;font-family:inherit;font-size:13px;font-weight:600;
    cursor:pointer;box-shadow:0 4px 14px rgba(13,148,136,.5);
    display:flex;align-items:center;gap:8px;transition:all .2s;
}
#rssc-survey-btn:hover{background:#0f766e;transform:translateY(-2px)}
#rssc-survey-btn i{font-size:15px}

#rssc-survey-modal{
    position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.5);
    display:none;align-items:center;justify-content:center;padding:20px;
}
#rssc-survey-modal.open{display:flex}
#rssc-survey-box{
    background:#fff;border-radius:20px;padding:28px 32px;
    max-width:420px;width:100%;box-shadow:0 20px 60px rgba(0,0,0,.2);
    font-family:'Kanit',Tahoma,sans-serif;
}
.rssc-stars{display:flex;gap:8px;justify-content:center;margin:16px 0}
.rssc-star{font-size:36px;cursor:pointer;opacity:.3;transition:all .15s;user-select:none}
.rssc-star.on{opacity:1}
.rssc-star-label{text-align:center;font-size:13px;color:#64748b;min-height:20px;margin-bottom:12px}
.rssc-textarea{width:100%;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;
    font-family:inherit;font-size:13px;resize:vertical;min-height:80px;outline:none}
.rssc-textarea:focus{border-color:#0d9488}
.rssc-submit{width:100%;background:#0d9488;color:#fff;border:none;border-radius:10px;
    padding:12px;font-family:inherit;font-size:14px;font-weight:700;cursor:pointer;margin-top:12px}
.rssc-submit:hover{background:#0f766e}
.rssc-submit:disabled{opacity:.5;cursor:not-allowed}
.rssc-close{float:right;background:none;border:none;font-size:20px;color:#94a3b8;cursor:pointer;margin:-4px -8px 0 0}
.rssc-done{text-align:center;padding:20px 0}
.rssc-done i{font-size:48px;color:#0d9488;display:block;margin-bottom:12px}
</style>

<!-- Cookie Banner -->
<div id="rssc-consent" style="display:none">
    <div style="flex:1;min-width:200px">
        <strong>🍪 เราใช้คุกกี้</strong>
        เว็บไซต์นี้ใช้คุกกี้เพื่อเก็บสถิติการใช้งานและปรับปรุงบริการ
        <a href="<?= $_base_url ?>/privacy_notice.php" target="_blank">อ่านนโยบายความเป็นส่วนตัว</a>
    </div>
    <div id="rssc-consent-btns">
        <button class="rssc-btn-decline" onclick="RSSC.decline()">ปฏิเสธ</button>
        <button class="rssc-btn-accept" onclick="RSSC.accept()">ยอมรับทั้งหมด</button>
    </div>
</div>

<!-- Survey Button -->
<button id="rssc-survey-btn" style="display:none" onclick="RSSC.openSurvey()">
    <i class="fas fa-star"></i> ประเมินความพึงพอใจ
</button>

<!-- Survey Modal -->
<div id="rssc-survey-modal">
    <div id="rssc-survey-box">
        <div>
            <button class="rssc-close" onclick="RSSC.closeSurvey()">✕</button>
            <h3 style="margin:0 0 4px;font-size:17px;color:#0f172a">ประเมินความพึงพอใจ</h3>
            <p style="margin:0 0 4px;font-size:12px;color:#64748b">เทศบาลนครรังสิต · Rangsit Smart City</p>
        </div>
        <div id="rssc-survey-form">
            <div class="rssc-stars" id="rssc-stars">
                <span class="rssc-star" data-v="1" onclick="RSSC.rate(1)" title="ปรับปรุง">😞</span>
                <span class="rssc-star" data-v="2" onclick="RSSC.rate(2)" title="พอใช้">😐</span>
                <span class="rssc-star" data-v="3" onclick="RSSC.rate(3)" title="ดี">🙂</span>
                <span class="rssc-star" data-v="4" onclick="RSSC.rate(4)" title="ดีมาก">😊</span>
                <span class="rssc-star" data-v="5" onclick="RSSC.rate(5)" title="ยอดเยี่ยม">😍</span>
            </div>
            <div class="rssc-star-label" id="rssc-star-label">กรุณาเลือกระดับความพึงพอใจ</div>
            <textarea class="rssc-textarea" id="rssc-comment" placeholder="ความคิดเห็นเพิ่มเติม (ไม่บังคับ)"></textarea>
            <button class="rssc-submit" id="rssc-submit" onclick="RSSC.submitSurvey()" disabled>ส่งแบบประเมิน</button>
        </div>
        <div id="rssc-survey-done" class="rssc-done" style="display:none">
            <i class="fas fa-check-circle"></i>
            <strong style="font-size:16px;display:block;color:#0f172a">ขอบคุณสำหรับความคิดเห็น!</strong>
            <span style="font-size:13px;color:#64748b">ข้อมูลของท่านจะช่วยพัฒนาบริการของเรา</span>
        </div>
    </div>
</div>

<script>
(function(){
// ── Config ────────────────────────────────────────────────────────────────────
const APP_PATH = '<?= $_script_dir ?>';          // '' on prod, '/iservice' on local
const CAT      = '<?= htmlspecialchars($_priv_cat) ?>';
const TITLE    = '<?= htmlspecialchars($_priv_title) ?>';
const PAGE     = window.location.href;
const TRACK_URL  = APP_PATH + '/api/track_visit.php';
const SURVEY_URL = APP_PATH + '/api/survey_submit.php';
const PRIVACY_URL = APP_PATH + '/privacy_notice.php';

// update privacy link dynamically
const pl = document.querySelector('#rssc-consent a');
if (pl) pl.href = PRIVACY_URL;

// ── Session ID (per browser tab) ──────────────────────────────────────────────
let sid = sessionStorage.getItem('rssc_sid');
if (!sid) {
    sid = 'sid_' + Date.now().toString(36) + Math.random().toString(36).slice(2,8);
    sessionStorage.setItem('rssc_sid', sid);
}

function getConsent() {
    try { return localStorage.getItem('rssc_consent'); }
    catch(e) { return null; }
}

// ── Main init ─────────────────────────────────────────────────────────────────
function init() {
    const c = getConsent();

    // 1. Banner: แสดงถ้ายังไม่เคยตัดสินใจ
    if (c === null) {
        setTimeout(() => {
            const el = document.getElementById('rssc-consent');
            if (el) el.style.display = 'flex';
        }, 1000);
    }

    // 2. Track visit (เฉพาะคนที่ยินยอม)
    if (c === 'yes') trackVisit();

    // 3. Survey button: แสดงทุกคนหลัง 4 วินาที (ไม่ต้องรอ consent)
    const surveyed = sessionStorage.getItem('rssc_sv_' + location.pathname);
    if (!surveyed) {
        setTimeout(() => {
            const btn = document.getElementById('rssc-survey-btn');
            if (btn) btn.style.display = 'flex';
        }, 4000);
    }
}

// ── Consent actions ───────────────────────────────────────────────────────────
window.RSSC = {
    _rating: 0,

    accept() {
        try { localStorage.setItem('rssc_consent','yes'); } catch(e) {}
        document.cookie = 'rssc_consent=yes;max-age=31536000;path=/;SameSite=Lax';
        const el = document.getElementById('rssc-consent');
        if (el) el.style.display = 'none';
        trackVisit();
    },

    decline() {
        try { localStorage.setItem('rssc_consent','no'); } catch(e) {}
        const el = document.getElementById('rssc-consent');
        if (el) el.style.display = 'none';
    },

    openSurvey() {
        document.getElementById('rssc-survey-modal').classList.add('open');
        document.getElementById('rssc-survey-btn').style.display = 'none';
    },

    closeSurvey() {
        document.getElementById('rssc-survey-modal').classList.remove('open');
        sessionStorage.setItem('rssc_sv_' + location.pathname, '1');
    },

    rate(v) {
        this._rating = v;
        const labels = ['','ปรับปรุง','พอใช้','ดี','ดีมาก','ยอดเยี่ยม'];
        const lbl = document.getElementById('rssc-star-label');
        if (lbl) lbl.textContent = labels[v];
        document.querySelectorAll('.rssc-star').forEach((el,i) => el.classList.toggle('on', i < v));
        const sb = document.getElementById('rssc-submit');
        if (sb) sb.disabled = false;
    },

    submitSurvey() {
        if (!this._rating) return;
        const btn = document.getElementById('rssc-submit');
        btn.disabled = true;
        btn.textContent = 'กำลังส่ง...';

        fetch(SURVEY_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                session_id: sid,
                page_url:   PAGE,
                rating:     this._rating,
                comment:    (document.getElementById('rssc-comment') || {}).value || '',
                category:   CAT,
            }),
        })
        .then(r => r.ok ? r.json() : Promise.reject('http_'+r.status))
        .then(data => {
            if (data.ok || data.message === 'already_submitted') {
                const form = document.getElementById('rssc-survey-form');
                const done = document.getElementById('rssc-survey-done');
                if (form) form.style.display = 'none';
                if (done) done.style.display = 'block';
                sessionStorage.setItem('rssc_sv_' + location.pathname, '1');
                setTimeout(() => this.closeSurvey(), 2500);
            } else {
                throw new Error(data.message || 'error');
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = 'ลองใหม่อีกครั้ง';
            btn.style.background = '#ef4444';
        });
    },
};

// ── Track visit ───────────────────────────────────────────────────────────────
function trackVisit() {
    const key = 'rssc_tr_' + location.pathname;
    if (sessionStorage.getItem(key)) return;
    sessionStorage.setItem(key, '1');
    fetch(TRACK_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
            session_id:    sid,
            page_url:      PAGE,
            page_title:    TITLE || document.title,
            page_category: CAT,
            referrer:      document.referrer,
            consent:       1,
        }),
        keepalive: true,
    }).catch(()=>{});
}

// ── Boot ──────────────────────────────────────────────────────────────────────
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

})();
</script>
<!-- ═══ End Privacy Consent ═══════════════════════════════════════════════ -->
