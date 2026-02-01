# คำแนะนำการติดตั้งระบบ (Installation Guide)

## ระบบบริการดิจิทัลเทศบาลนครรังสิต

---

## ขั้นตอนการติดตั้ง (Installation Steps)

### 1. เตรียม Environment

#### ความต้องการของระบบ:
- **Web Server**: Apache 2.4+
- **PHP**: 7.4 หรือสูงกว่า
- **MySQL/MariaDB**: 5.7+ / 10.3+
- **XAMPP/WAMP/MAMP** (แนะนำ)

#### Extensions ที่ต้องการ:
- php_mysqli
- php_pdo_mysql
- php_mbstring
- php_gd (สำหรับจัดการรูปภาพ)
- php_fileinfo

---

### 2. สร้างฐานข้อมูล

เปิด phpMyAdmin หรือ MySQL Command Line:

```sql
CREATE DATABASE green_theme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

---

### 3. รัน SQL Files ตามลำดับ

#### ⚠️ **สำคัญ**: ต้องรันตามลำดับดังนี้

#### ขั้นตอนที่ 1: สร้างตาราง Prefixes
```bash
รันไฟล์: database/prefixes.sql
```
ไฟล์นี้จะสร้าง:
- ตาราง `prefixes` (คำนำหน้าชื่อ)
- ข้อมูลคำนำหน้าทั้งหมด (นาย, นาง, นางสาว, ยศทหาร, ยศตำรวจ, ฯลฯ)

#### ขั้นตอนที่ 2: สร้างตาราง Departments
```bash
รันไฟล์: database/departments.sql
```
ไฟล์นี้จะสร้าง:
- ตาราง `departments` (หน่วยงาน)
- โครงสร้างแบบ 4 ระดับ (Parent-Child Hierarchy)

#### ขั้นตอนที่ 3: สร้างตาราง Users
```bash
รันไฟล์: database/users.sql
```
ไฟล์นี้จะสร้าง:
- ตาราง `users` (ผู้ใช้งาน)
- View `v_users_full`
- Stored Procedures
- Triggers
- ข้อมูลผู้ใช้ตัวอย่าง (รวม admin)

#### ขั้นตอนที่ 4: สร้างตาราง Service Requests
```bash
รันไฟล์: database/service_requests.sql (ถ้ามี)
```

---

### 4. ตั้งค่าไฟล์ Config

แก้ไขไฟล์ `config/database.php`:

```php
<?php
$host = 'localhost';
$dbname = 'green_theme';
$username = 'root';      // เปลี่ยนตามของคุณ
$password = '';          // เปลี่ยนตามของคุณ

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die("Database connection error: " . $e->getMessage());
}
?>
```

---

### 5. ทดสอบการเข้าสู่ระบบ

เปิดเบราว์เซอร์ไปที่:
```
http://localhost/green_theme/login.php
```

#### บัญชีผู้ดูแลระบบ (Admin):
- **Username**: `admin`
- **Password**: `admin123`
- **Email**: `admin@rangsit.go.th`

#### บัญชีทดสอบอื่นๆ:
| Username | Password | Role | ชื่อ-นามสกุล |
|----------|----------|------|--------------|
| somchai | admin123 | staff | นาย สมชาย ใจดี |
| somsri | admin123 | user | นาง สมศรี รักดี |
| suree | admin123 | user | นางสาว สุรีย์ สว่างใจ |
| military_user | admin123 | user | ว่าที่ร้อยตรี ธนพล มั่นคง |
| doctor_user | admin123 | user | ดร. วิชัย ปัญญา |

---

## โครงสร้างฐานข้อมูล (Database Structure)

### ตาราง `prefixes` (คำนำหน้าชื่อ)
```
prefix_id (PK)
prefix_name       - ชื่อคำนำหน้า
prefix_short      - ตัวย่อ
prefix_type       - ประเภท (general, military_army, military_navy, etc.)
is_active         - สถานะใช้งาน
display_order     - ลำดับการแสดงผล
```

### ตาราง `departments` (หน่วยงาน)
```
department_id (PK)
parent_department_id (FK) - หน่วยงานแม่
department_code           - รหัสหน่วยงาน
department_name           - ชื่อหน่วยงาน
short_name               - ชื่อย่อ
level                    - ระดับ (1-4)
level_type               - ประเภทระดับ
manager_user_id (FK)     - ผู้จัดการ
building                 - อาคาร
floor                    - ชั้น
phone                    - เบอร์โทร
email                    - อีเมล
budget_code              - รหัสงบประมาณ
status                   - สถานะ
```

### ตาราง `users` (ผู้ใช้งาน)
```
user_id (PK)
prefix_id (FK)      - คำนำหน้า
username            - ชื่อผู้ใช้
first_name          - ชื่อ
last_name           - นามสกุล
email               - อีเมล
phone               - เบอร์โทร
password            - รหัสผ่าน (hashed)
role                - บทบาท (admin/staff/user)
status              - สถานะ (active/inactive/suspended)
department_id (FK)  - หน่วยงาน
position            - ตำแหน่ง
profile_image       - รูปโปรไฟล์
last_login          - Login ครั้งล่าสุด
created_at          - วันที่สร้าง
updated_at          - วันที่อัปเดต
```

### View `v_users_full`
View ที่รวมข้อมูล users + prefixes + departments
```sql
SELECT * FROM v_users_full;
```

### Stored Procedures
- `sp_get_user_login(login_identifier)` - ดึงข้อมูล user สำหรับ login
- `sp_update_last_login(user_id)` - อัปเดตเวลา login

---

## การใช้งานระบบ

### หน้า Public (ไม่ต้อง Login)
- `/index.php` - หน้าแรก
- `/login.php` - เข้าสู่ระบบ
- `/register.php` - สมัครสมาชิก
- `/request-form.php?service=XXX` - แบบฟอร์มขอใช้บริการ

### หน้า Admin (ต้อง Login เป็น admin)
- `/admin/index.php` - Dashboard
- `/admin/departments.php` - จัดการหน่วยงาน
- `/admin/service_requests.php` - จัดการคำขอบริการ
- `/admin/users.php` - จัดการผู้ใช้งาน (ถ้ามี)

---

## ฟีเจอร์ที่มีในระบบ

### ✅ ระบบผู้ใช้
- [x] สมัครสมาชิก (พร้อมเลือกคำนำหน้า)
- [x] เข้าสู่ระบบ (Username/Email)
- [x] ออกจากระบบ
- [x] จัดการโปรไฟล์
- [x] ระบบสิทธิ์ (Admin/Staff/User)

### ✅ ระบบหน่วยงาน
- [x] CRUD หน่วยงาน (4 ระดับ)
- [x] โครงสร้างแบบ Tree
- [x] เลือก Level ก่อน
- [x] Real-time duplicate code check

### ✅ ระบบคำขอบริการ
- [x] แบบฟอร์มขอใช้บริการ
- [x] ติดตามสถานะคำขอ
- [x] Admin จัดการคำขอ (CRUD)
- [x] อัปเดตสถานะ
- [x] มอบหมายงาน
- [x] Bulk actions

### ✅ UI/UX
- [x] Responsive Design
- [x] SweetAlert2
- [x] AJAX (ไม่ reload หน้า)
- [x] โทนสีเขียว (Teal)
- [x] Tailwind CSS
- [x] FontAwesome Icons

---

## การ Backup ฐานข้อมูล

### ผ่าน phpMyAdmin:
1. เลือกฐานข้อมูล `green_theme`
2. คลิก Export
3. เลือก Custom
4. เลือก SQL format
5. ดาวน์โหลด

### ผ่าน Command Line:
```bash
mysqldump -u root -p green_theme > backup_$(date +%Y%m%d).sql
```

---

## Troubleshooting

### ปัญหา: ไม่สามารถเชื่อมต่อฐานข้อมูล
**วิธีแก้**:
1. ตรวจสอบ `config/database.php`
2. ตรวจสอบ MySQL service ทำงานหรือไม่
3. ตรวจสอบ username/password

### ปัญหา: Foreign Key Error
**วิธีแก้**:
1. ต้องรัน `prefixes.sql` ก่อน `users.sql`
2. ต้องรัน `departments.sql` ก่อน `users.sql`

### ปัญหา: รูปภาพโหลดไม่ขึ้น
**วิธีแก้**:
1. ตรวจสอบ path ใน `images/logo/`
2. ตรวจสอบสิทธิ์ folder (755)

### ปัญหา: Session หมดอายุเร็ว
**วิธีแก้**:
แก้ไข `php.ini`:
```ini
session.gc_maxlifetime = 3600
```

---

## การอัปเดตระบบ

### เพิ่มคำนำหน้าใหม่:
```sql
INSERT INTO prefixes (prefix_name, prefix_short, prefix_type, display_order)
VALUES ('คำนำหน้าใหม่', 'ย่อ', 'general', 999);
```

### เพิ่ม Admin ใหม่:
```sql
INSERT INTO users (prefix_id, username, first_name, last_name, email, password, role, status)
VALUES (
    1,
    'newadmin',
    'Admin',
    'ใหม่',
    'newadmin@rangsit.go.th',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'admin',
    'active'
);
```

---

## Security Best Practices

1. **เปลี่ยนรหัสผ่าน admin** ทันทีหลังติดตั้ง
2. **ตั้งค่า .htaccess** ป้องกัน directory listing
3. **อัปเดต PHP** เป็นเวอร์ชันล่าสุด
4. **Backup** ฐานข้อมูลสม่ำเสมอ
5. **ใช้ HTTPS** ใน production

---

## การติดต่อและสนับสนุน

หากพบปัญหาหรือต้องการความช่วยเหลือ:
- Email: support@rangsit.go.th
- Tel: 02-XXX-XXXX

---

**เวอร์ชัน**: 1.0.0
**วันที่อัปเดต**: 2025-12-30
**ผู้พัฒนา**: ทีมพัฒนาระบบดิจิทัลเทศบาลนครรังสิต
