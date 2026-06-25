<?php
date_default_timezone_set('Asia/Bangkok');
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>นโยบายความเป็นส่วนตัว — เทศบาลนครรังสิต</title>
<link rel="icon" type="image/png" href="public/assets/images/logo/rangsit-small-logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>* { font-family:'Kanit',sans-serif; } .prose h2{font-size:1.1rem;font-weight:700;margin:1.5rem 0 .5rem;color:#0f172a} .prose p,.prose li{font-size:.9rem;color:#374151;line-height:1.8} .prose ul{list-style:disc;padding-left:1.5rem;margin:.5rem 0}</style>
</head>
<body class="bg-slate-50 min-h-screen">

<header class="bg-gradient-to-r from-teal-800 to-teal-600 text-white shadow-lg">
    <div class="max-w-3xl mx-auto px-4 py-4 flex items-center gap-4">
        <img src="public/assets/images/logo/rangsit-small-logo.png" alt="logo" class="h-10 w-auto rounded" onerror="this.style.display='none'">
        <div>
            <h1 class="text-base font-bold">นโยบายความเป็นส่วนตัว</h1>
            <p class="text-teal-200 text-xs">เทศบาลนครรังสิต · Rangsit Smart City (RSSC)</p>
        </div>
        <a href="javascript:history.back()" class="ml-auto text-xs bg-white/15 hover:bg-white/25 px-3 py-1.5 rounded-lg transition-colors">
            ← ย้อนกลับ
        </a>
    </div>
</header>

<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-8 prose">

        <p class="text-xs text-slate-400 mb-6">อัปเดตล่าสุด: <?= date('d/m/') . (date('Y')+543) ?></p>

        <h2>1. บทนำ</h2>
        <p>เทศบาลนครรังสิต ("เทศบาล") ให้ความสำคัญกับความเป็นส่วนตัวของท่านและมุ่งมั่นในการปกป้องข้อมูลส่วนบุคคลที่ท่านให้ไว้ผ่านการใช้งานเว็บไซต์และระบบบริการดิจิทัล iService รวมถึงระบบติดตามคุณภาพอากาศ PM2.5</p>

        <h2>2. ข้อมูลที่เราเก็บรวบรวม</h2>
        <ul>
            <li><strong>ข้อมูลการเข้าชม:</strong> URL หน้าที่เข้าชม, เวลา, ประเภทอุปกรณ์, เบราว์เซอร์</li>
            <li><strong>ที่อยู่ IP:</strong> เราจะแปลง IP เป็นรหัสแฮช (SHA-256) เพื่อนับจำนวนผู้เยี่ยมชมเท่านั้น ไม่สามารถระบุตัวตนได้</li>
            <li><strong>ข้อมูลการประเมิน:</strong> คะแนนและความคิดเห็นที่ท่านให้ผ่านแบบประเมินความพึงพอใจ (ไม่มีชื่อ)</li>
            <li><strong>Session ID:</strong> รหัสชั่วคราวที่สร้างในเบราว์เซอร์ ไม่เชื่อมโยงกับบัญชีผู้ใช้ใดๆ</li>
        </ul>

        <h2>3. วัตถุประสงค์ในการเก็บข้อมูล</h2>
        <ul>
            <li>วิเคราะห์สถิติการเข้าชมเพื่อปรับปรุงบริการ</li>
            <li>วัดระดับความพึงพอใจในการใช้งาน</li>
            <li>พัฒนาคุณภาพเนื้อหาและฟีเจอร์ของระบบ</li>
        </ul>

        <h2>4. คุกกี้ (Cookies)</h2>
        <p>เว็บไซต์ใช้คุกกี้ประเภทต่อไปนี้:</p>
        <ul>
            <li><strong>คุกกี้จำเป็น (Strictly Necessary):</strong> ใช้สำหรับการทำงานพื้นฐานของระบบ เช่น session การเข้าสู่ระบบ</li>
            <li><strong>คุกกี้วิเคราะห์ (Analytics):</strong> ใช้เก็บสถิติการเข้าชม (ต้องได้รับความยินยอมจากท่าน)</li>
        </ul>
        <p>ท่านสามารถเลือกปฏิเสธคุกกี้วิเคราะห์ได้ โดยคลิก "ปฏิเสธ" ที่แบนเนอร์</p>

        <h2>5. การแบ่งปันข้อมูล</h2>
        <p>เราไม่แบ่งปันข้อมูลส่วนบุคคลกับบุคคลภายนอก ยกเว้นกรณีที่กฎหมายกำหนด</p>

        <h2>6. ระยะเวลาในการเก็บข้อมูล</h2>
        <p>ข้อมูลสถิติการเข้าชมจะถูกเก็บไว้ไม่เกิน 2 ปี ข้อมูลแบบประเมินไม่มีการลบเพื่อใช้วิเคราะห์แนวโน้มระยะยาว</p>

        <h2>7. สิทธิ์ของท่าน</h2>
        <ul>
            <li>ขอดูข้อมูลที่เราเก็บ (หมายเหตุ: ข้อมูล IP ถูกแฮชแล้ว ไม่สามารถระบุย้อนกลับได้)</li>
            <li>ถอนความยินยอมได้ทุกเวลา โดยล้าง cookies ในเบราว์เซอร์</li>
        </ul>

        <h2>8. ติดต่อเรา</h2>
        <p>
            <strong>งานสถิติและข้อมูลสารสนเทศ</strong><br>
            ฝ่ายบริการและเผยแพร่วิชาการ กองยุทธศาสตร์และงบประมาณ<br>
            เทศบาลนครรังสิต โทร. 02-567-6000 ต่อ 151
        </p>

    </div>
</div>

<footer class="text-center text-xs text-slate-400 py-6">
    เทศบาลนครรังสิต · Rangsit Smart City (RSSC) · <?= date('Y') ?>
</footer>
</body>
</html>
