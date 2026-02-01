# My Service Management System

ระบบจัดการบริการต่างๆ แบบ Dynamic โดยใช้ Database พร้อม Service Cards สวยงาม

## 📁 โครงสร้างไฟล์

```
green_theme/
├── admin/
│   ├── my_service.php         # หน้าจัดการบริการ (CRUD)
│   └── nav_menu.php           # หน้าจัดการเมนู
├── config/
│   └── database.php           # ไฟล์ตั้งค่าฐานข้อมูล
├── database/
│   ├── my_service.sql         # SQL Script สำหรับสร้างตาราง my_service
│   └── nav_menu.sql           # SQL Script สำหรับสร้างตาราง nav_menu
├── includes/
│   ├── service_loader.php     # ไฟล์โหลดบริการจาก database
│   └── nav_menu_loader.php    # ไฟล์โหลดเมนูจาก database
└── index.php                  # หน้าแรก (แสดง service cards)
```

## 🚀 วิธีการติดตั้ง

### ขั้นตอนที่ 1: Import Database

1. เปิด phpMyAdmin (`http://localhost/phpmyadmin`)
2. เลือก Database `green_theme_db` (ถ้ายังไม่มีให้สร้างใหม่)
3. ไปที่แท็บ "SQL"
4. คัดลอกและรันคำสั่งจากไฟล์ `database/my_service.sql`
5. กดปุ่ม "Go"

### ขั้นตอนที่ 2: ทดสอบระบบ

1. **ดูหน้าแรก**: `http://localhost/green_theme/index.php`
   - จะเห็น Service Cards ทั้งหมดแสดงในส่วน "เลือกประเภทบริการ"

2. **เข้าหน้า Admin**: `http://localhost/green_theme/admin/my_service.php`
   - จัดการบริการแบบ CRUD ได้ทันที

## 📊 โครงสร้างตาราง my_service

| Column | Type | Description | Example |
|--------|------|-------------|---------|
| id | INT | Primary Key | 1, 2, 3 |
| service_code | VARCHAR(50) | รหัสบริการ (UNIQUE) | EMAIL, INTERNET, IT_SUPPORT |
| service_name | VARCHAR(100) | ชื่อบริการ (ไทย) | อีเมลเทศบาล |
| service_name_en | VARCHAR(100) | ชื่อบริการ (อังกฤษ) | Email Service |
| description | TEXT | คำอธิบายบริการ | ขอเปิดใช้งานอีเมลใหม่... |
| icon | VARCHAR(50) | Font Awesome icon | fas fa-envelope |
| color_code | VARCHAR(20) | สีของ card | blue, red, green |
| service_url | VARCHAR(255) | URL ที่จะไปเมื่อคลิก | service-email.php |
| is_active | TINYINT(1) | สถานะ (1=แสดง, 0=ซ่อน) | 1 |
| display_order | INT | ลำดับการแสดงผล | 1, 2, 3 |
| created_at | TIMESTAMP | วันที่สร้าง | 2025-12-29 13:00:00 |
| updated_at | TIMESTAMP | วันที่แก้ไขล่าสุด | 2025-12-29 14:00:00 |

## 🎨 Color Codes ที่รองรับ

| Color Code | สี | เหมาะสำหรับ |
|------------|-----|-------------|
| blue | น้ำเงิน | Email, ข้อมูลทั่วไป |
| indigo | น้ำเงินม่วง | Internet, Network |
| red | แดง | IT Support, Emergency |
| orange | ส้ม | Storage, Warning |
| purple | ม่วง | QR Code, Special |
| pink | ชมพู | Photography, Creative |
| teal | เขียวน้ำทะเล | Web Design, Tech |
| green | เขียว | Printer, Success |
| gray | เทา | Archived, Disabled |
| yellow | เหลือง | ไว้ใช้ในอนาคต |

## 🎯 วิธีการใช้งาน

### การเพิ่มบริการใหม่

1. เข้าหน้าจัดการบริการ: `admin/my_service.php`
2. กรอกข้อมูลในฟอร์มด้านขวา:
   - **รหัสบริการ**: ภาษาอังกฤษเท่านั้น เช่น `VIDEO_CONFERENCE`
   - **ชื่อบริการ (TH)**: เช่น `ห้องประชุมออนไลน์`
   - **ชื่อบริการ (EN)**: เช่น `Video Conference`
   - **คำอธิบาย**: รายละเอียดบริการ
   - **Icon**: เลือก Font Awesome icon (ดูได้จาก https://fontawesome.com/icons)
   - **สี**: เลือกสีที่เหมาะสม
   - **URL**: หน้าที่จะลิงก์ไป
   - **ลำดับ**: ตัวเลขลำดับการแสดงผล
   - **เปิดใช้งาน**: เช็คถ้าต้องการให้แสดง
3. กดปุ่ม "เพิ่มบริการ"

### การแก้ไขบริการ

1. คลิกไอคอน "✏️" (Edit) ที่บริการที่ต้องการแก้ไข
2. ข้อมูลจะแสดงในฟอร์มด้านขวา
3. แก้ไขข้อมูล
4. กดปุ่ม "บันทึกการแก้ไข"

### การลบบริการ

1. คลิกไอคอน "🗑️" (Delete) ที่บริการที่ต้องการลบ
2. ยืนยันการลบ

### การเปิด/ปิดบริการ

1. คลิกที่ไอคอน Toggle (สีเขียว = เปิด, สีแดง = ปิด)
2. บริการที่ปิดจะไม่แสดงในหน้าเว็บ แต่ยังอยู่ใน database

## 🖼️ ตัวอย่างการแสดงผล Service Card

```html
┌─────────────────────────────┐
│  [📧]                       │
│                             │
│  อีเมลเทศบาล                │
│  EMAIL SERVICE              │
│                             │
│  ขอเปิดใช้งานอีเมลใหม่...  │
│                             │
│  [ ยื่นคำขอ ]               │
└─────────────────────────────┘
```

## 📝 ตัวอย่างข้อมูลบริการเริ่มต้น

ระบบมาพร้อมข้อมูลตัวอย่าง 8 บริการ:

1. **อีเมลเทศบาล** (EMAIL) - สีน้ำเงิน
2. **อินเทอร์เน็ต / WiFi** (INTERNET) - สีม่วงน้ำเงิน
3. **แจ้งซ่อมระบบ IT** (IT_SUPPORT) - สีแดง
4. **พื้นที่เก็บข้อมูล NAS** (NAS) - สีส้ม
5. **สร้าง QR Code** (QR_CODE) - สีม่วง
6. **บริการถ่ายภาพ** (PHOTOGRAPHY) - สีชมพู
7. **ออกแบบเว็บไซต์** (WEB_DESIGN) - สีเขียวน้ำทะเล
8. **เครื่องพิมพ์และสแกนเนอร์** (PRINTER) - สีเขียว

## 🔧 การปรับแต่ง

### เปลี่ยนจำนวน Columns ใน Grid

แก้ไขในไฟล์ `index.php` บรรทัดที่ 330:

```html
<!-- เดิม: 4 columns บนจอใหญ่ -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 md:gap-8">

<!-- เปลี่ยนเป็น 3 columns -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8">
```

### เพิ่มสีใหม่

1. แก้ไขไฟล์ `includes/service_loader.php` ฟังก์ชัน `get_color_classes()`
2. เพิ่ม color code ใหม่:

```php
'navy' => [
    'bg' => 'bg-blue-900',
    'text' => 'text-blue-900',
    'hover' => 'hover:border-blue-900',
    'btn' => 'bg-blue-900 hover:bg-blue-800'
],
```

3. อัพเดท dropdown ในไฟล์ `admin/my_service.php`

### ซ่อนลิงก์ Admin (Production)

ลบหรือคอมเมนต์บรรทัด 335-339 ใน `index.php`:

```html
<!-- Link to Admin (ลบออกใน production) -->
<!-- <div class="text-center mt-12">
    <a href="admin/my_service.php">...</a>
</div> -->
```

## ❓ FAQ

**Q: บริการไม่แสดงในหน้าเว็บ?**
A: ตรวจสอบ:
- Database connection ถูกต้อง
- บริการมีสถานะ `is_active = 1`
- ไฟล์ `includes/service_loader.php` ถูก include ใน index.php

**Q: ต้องการเปลี่ยนสีที่มีอยู่แล้ว?**
A: แก้ไขในหน้า Admin > เลือกบริการ > Edit > เลือกสีใหม่

**Q: Icon ไม่แสดง?**
A: ตรวจสอบว่า:
- Font Awesome CDN โหลดสำเร็จ
- รหัส Icon ถูกต้อง (เช่น `fas fa-envelope`)
- ดู icon ที่ https://fontawesome.com/icons

**Q: ต้องการเปลี่ยนลำดับบริการ?**
A: แก้ไขค่า "ลำดับการแสดงผล" ระบบจะเรียงจากน้อยไปมาก

**Q: สามารถเพิ่มบริการได้กี่รายการ?**
A: ไม่จำกัด แต่แนะนำ 8-12 รายการ เพื่อ UX ที่ดี

## 🔗 เอกสารเพิ่มเติม

- [Font Awesome Icons](https://fontawesome.com/icons)
- [Tailwind CSS Colors](https://tailwindcss.com/docs/customizing-colors)
- [PHP MySQLi Documentation](https://www.php.net/manual/en/book.mysqli.php)

## 📞 การบำรุงรักษา

### Backup Database
```sql
-- Export ตาราง my_service
SELECT * FROM my_service INTO OUTFILE 'my_service_backup.csv';
```

### Restore Sample Data
```sql
-- ลบข้อมูลเก่า
DELETE FROM my_service;

-- Run SQL script อีกครั้ง
SOURCE database/my_service.sql;
```

---

**สร้างโดย**: Green Theme Development Team
**เวอร์ชัน**: 1.0.0
**อัพเดทล่าสุด**: 29 ธันวาคม 2568
