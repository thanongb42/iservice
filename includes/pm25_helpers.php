<?php
/**
 * PM2.5 shared helpers — ใช้ร่วมกันทุกหน้า
 * require_once __DIR__ . '/includes/pm25_helpers.php';   (from root)
 * require_once __DIR__ . '/../includes/pm25_helpers.php'; (from subdirs)
 */

function nullFloat($v): ?float {
    return $v !== null && $v !== '' ? (float)$v : null;
}

function pmLevel($v): array {
    if ($v === null) return ['hex'=>'#94a3b8','text'=>'#fff','label'=>'ไม่มีข้อมูล','advice'=>'–'];
    $v = (float)$v;
    if ($v <= 15.0) return ['hex'=>'#3BCCFF','text'=>'#0c4a6e','label'=>'ดีมาก',
        'advice'=>'ประชาชนทุกคนสามารถดำเนินชีวิตได้ตามปกติ'];
    if ($v <= 25.0) return ['hex'=>'#92D050','text'=>'#14532d','label'=>'ดี',
        'advice'=>'ทำกิจกรรมกลางแจ้งได้ตามปกติ · กลุ่มเสี่ยงควรสังเกตอาการผิดปกติ'];
    if ($v <= 37.5) return ['hex'=>'#FFFF00','text'=>'#713f12','label'=>'ปานกลาง',
        'advice'=>'ลดระยะเวลากิจกรรมกลางแจ้ง · กลุ่มเสี่ยงใช้หน้ากาก PM2.5'];
    if ($v <= 75.0) return ['hex'=>'#FFA200','text'=>'#fff','label'=>'เริ่มมีผลกระทบต่อสุขภาพ',
        'advice'=>'ใช้หน้ากาก PM2.5 ทุกครั้งที่ออกนอกอาคาร · จำกัดเวลาทำกิจกรรมกลางแจ้ง'];
    return             ['hex'=>'#F04646','text'=>'#fff','label'=>'มีผลกระทบต่อสุขภาพ',
        'advice'=>'งดกิจกรรมกลางแจ้ง · ใช้หน้ากาก PM2.5 ทุกครั้ง · หากมีอาการให้รีบพบแพทย์'];
}

// PM2.5 breakpoints (ใช้ใน API response)
const PM25_STANDARD = [
    ['level'=>1,'range'=>'0–15.0',   'label'=>'ดีมาก',                   'color'=>'#3BCCFF'],
    ['level'=>2,'range'=>'15.1–25.0','label'=>'ดี',                      'color'=>'#92D050'],
    ['level'=>3,'range'=>'25.1–37.5','label'=>'ปานกลาง',                 'color'=>'#FFFF00'],
    ['level'=>4,'range'=>'37.6–75.0','label'=>'เริ่มมีผลกระทบต่อสุขภาพ', 'color'=>'#FFA200'],
    ['level'=>5,'range'=>'≥75.1',    'label'=>'มีผลกระทบต่อสุขภาพ',      'color'=>'#F04646'],
];
