# Database Migration Instructions

## คำแนะนำการอัปเดตฐานข้อมูล

### ขั้นตอนที่ 1: เพิ่มตาราง prefixes และฟิลด์ prefix_id
รันไฟล์ `prefixes.sql` ผ่าน phpMyAdmin หรือ command line:

```sql
-- เปิดไฟล์ database/prefixes.sql แล้วรันทั้งหมด
```

ไฟล์นี้จะทำสิ่งต่อไปนี้:
1. สร้างตาราง `prefixes` พร้อมคำนำหน้าทั้งหมด (นาย, นาง, นางสาว, ยศทหาร, ยศตำรวจ, คำนำหน้าทางวิชาการ)
2. เพิ่มฟิลด์ `prefix_id` ในตาราง `users`
3. สร้าง foreign key relationship
4. สร้าง index สำหรับการค้นหาที่เร็วขึ้น

### ขั้นตอนที่ 2: เพิ่มฟิลด์ username
รันไฟล์ `add_username_field.sql` ผ่าน phpMyAdmin หรือ command line:

```sql
-- เปิดไฟล์ database/add_username_field.sql แล้วรันทั้งหมด
```

ไฟล์นี้จะทำสิ่งต่อไปนี้:
1. เพิ่มฟิลด์ `username` ในตาราง `users`
2. อัปเดต username ให้กับผู้ใช้เดิม (ใช้ส่วนก่อน @ ของอีเมล)
3. ตั้งค่า username เป็น UNIQUE และ NOT NULL
4. สร้าง index สำหรับ username

### ขั้นตอนที่ 3: ตรวจสอบการ Migration

```sql
-- ตรวจสอบตาราง prefixes
SELECT * FROM prefixes LIMIT 10;

-- ตรวจสอบฟิลด์ใหม่ในตาราง users
DESC users;

-- ตรวจสอบผู้ใช้ที่มี username แล้ว
SELECT user_id, username, prefix_id, first_name, last_name FROM users LIMIT 5;
```

### ขั้นตอนที่ 4: อัปเดตข้อมูลผู้ใช้เดิม (ถ้ามี)

หากมีผู้ใช้เดิมอยู่ในระบบ ให้อัปเดตคำนำหน้าด้วยตนเอง:

```sql
-- ตัวอย่าง: อัปเดตคำนำหน้าสำหรับผู้ใช้ ID 1 เป็น "นาย"
UPDATE users
SET prefix_id = (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาย')
WHERE user_id = 1;

-- ตัวอย่าง: อัปเดตคำนำหน้าสำหรับผู้ใช้ ID 2 เป็น "นาง"
UPDATE users
SET prefix_id = (SELECT prefix_id FROM prefixes WHERE prefix_name = 'นาง')
WHERE user_id = 2;
```

### หมายเหตุ

- **สำคัญ**: กรุณา backup ฐานข้อมูลก่อนทำการ migrate
- หลังจาก migrate เสร็จแล้ว ระบบจะบังคับให้ผู้ใช้ใหม่เลือกคำนำหน้าทุกครั้งเมื่อสมัครสมาชิก
- ผู้ใช้สามารถ login ด้วย username หรือ email ก็ได้
- Session จะเก็บ `$_SESSION['full_name']` ที่รวมคำนำหน้า ชื่อ และนามสกุลแล้ว

### คำนำหน้าที่รองรับ

1. **คำนำหน้าทั่วไป**: นาย, นาง, นางสาว, เด็กชาย, เด็กหญิง
2. **ยศทหารบก**: ว่าที่ร้อยตรี, ร้อยตรี, พันตรี, พลตรี, ฯลฯ (รวมทั้งหญิง)
3. **ยศทหารเรือ**: นาวาตรี, นาวาโท, นาวาเอก, ฯลฯ
4. **ยศทหารอากาศ**: เรืออากาศตรี, เรืออากาศโท, เรืออากาศเอก, ฯลฯ
5. **ยศตำรวจ**: ร้อยตำรวจตรี, พันตำรวจตรี, พลตำรวจตรี, ฯลฯ
6. **คำนำหน้าทางวิชาการ**: ดร., ผศ., รศ., ศ., ผศ.ดร., รศ.ดร., ศ.ดร., ฯลฯ

### การเพิ่มคำนำหน้าใหม่

หากต้องการเพิ่มคำนำหน้าใหม่:

```sql
INSERT INTO prefixes (prefix_name, prefix_short, prefix_type, display_order)
VALUES ('คำนำหน้าใหม่', 'ย่อ', 'general', 999);
```

โดย `prefix_type` สามารถเป็น: `general`, `military_army`, `military_navy`, `military_air`, `police`, `academic`
