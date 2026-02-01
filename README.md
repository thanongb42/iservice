# ระบบบริการดิจิทัลเทศบาลนครรังสิต (MVC Architecture)

![Version](https://img.shields.io/badge/version-2.0.0--dev-blue)
![PHP](https://img.shields.io/badge/PHP-7.4+-purple)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange)
![Architecture](https://img.shields.io/badge/Architecture-MVC-brightgreen)
![Composer](https://img.shields.io/badge/Composer-PSR--4-orange)
![License](https://img.shields.io/badge/license-MIT-green)

ระบบบริการดิจิทัลสำหรับเทศบาลนครรังสิต ที่ช่วยให้ประชาชนสามารถขอใช้บริการต่างๆ ผ่านระบบออนไลน์ได้อย่างสะดวกรวดเร็ว

**🚧 Status: กำลังพัฒนา (Refactoring to MVC) - 45% Complete**

---

## 📋 สารบัญ

- [คุณสมบัติหลัก](#คุณสมบัติหลัก)
- [MVC Architecture](#mvc-architecture)
- [เทคโนโลยีที่ใช้](#เทคโนโลยีที่ใช้)
- [ความต้องการของระบบ](#ความต้องการของระบบ)
- [การติดตั้ง](#การติดตั้ง)
- [โครงสร้างโปรเจค MVC](#โครงสร้างโปรเจค-mvc)
- [Progress Tracking](#progress-tracking)
- [Developer Guide](#developer-guide)
- [API Documentation](#api-documentation)

---

## ✨ คุณสมบัติหลัก

### 🔐 ระบบผู้ใช้งาน
- ✅ สมัครสมาชิกพร้อมเลือกคำนำหน้า (นาย/นาง/นางสาว/ยศทหาร/ยศตำรวจ/คำนำหน้าทางวิชาการ)
- ✅ เข้าสู่ระบบด้วย Username หรือ Email
- ✅ ระบบสิทธิ์ 3 ระดับ (Admin, Staff, User)
- ✅ จัดการโปรไฟล์ส่วนตัว
- ✅ Password hashing ด้วย bcrypt

### 🏢 ระบบจัดการหน่วยงาน
- ✅ โครงสร้างแบบ 4 ระดับ (สำนัก/กอง → ส่วน → ฝ่าย/กลุ่มงาน → งาน)
- ✅ CRUD หน่วยงานแบบ Real-time
- ✅ Tree structure display
- ✅ Real-time duplicate code checking
- ✅ Level-based parent selection

### 📝 ระบบคำขอบริการ
- ✅ แบบฟอร์มขอใช้บริการหลากหลายประเภท
  - Email Account
  - NAS Storage
  - IT Support
  - Internet
  - QR Code
  - Photography
  - Web Design
  - Printer
- ✅ ติดตามสถานะคำขอ Real-time
- ✅ Upload เอกสารประกอบ
- ✅ Cascade dropdown เลือกหน่วยงาน 4 ระดับ

### 👨‍💼 ระบบ Admin
- ✅ Dashboard แสดงสถิติ
- ✅ จัดการคำขอบริการ (อนุมัติ/ปฏิเสธ/มอบหมาย)
- ✅ Bulk actions (อัปเดตหลายรายการพร้อมกัน)
- ✅ ระบบกรอง และค้นหา
- ✅ Export ข้อมูล

### 🎨 UI/UX
- ✅ Responsive Design (Mobile, Tablet, Desktop)
- ✅ โทนสีเขียว (Teal Theme)
- ✅ SweetAlert2 notifications
- ✅ AJAX (ไม่ reload หน้า)
- ✅ Smooth animations
- ✅ FontAwesome icons

---

## 🏗 MVC Architecture

โปรเจกต์นี้ถูก refactor เป็น **MVC Architecture แบบ Basic** โดยใช้ Composer และ PSR-4 Autoloading

### โครงสร้าง MVC

```
app/
├── Config/         # Database, App configuration
├── Core/           # Framework core classes (Router, Controller, Model, View)
├── Models/         # Database models
├── Controllers/    # Request handlers
├── Views/          # Template files
├── Middleware/     # Request filters (Auth, Admin, Guest)
├── Services/       # Business logic
└── Helpers/        # Helper functions

public/             # Web root (only accessible folder)
├── index.php       # Front controller
└── assets/         # CSS, JS, images
```

### Namespace Structure (PSR-4)

```php
App\Core\Controller
App\Controllers\HomeController
App\Controllers\Admin\DashboardController
App\Models\User
App\Services\AuthService
```

### Key Features

- ✅ **Front Controller Pattern**: เข้าผ่าน `public/index.php` เพียงจุดเดียว
- ✅ **Routing System**: URL mapping พร้อม middleware และ parameters
- ✅ **Service Layer**: Business logic แยกจาก Controllers
- ✅ **Base Classes**: Controller, Model ที่ extend ได้
- ✅ **Dependency Injection**: Constructor injection ใน Controllers
- ✅ **Security**: PDO Prepared Statements, CSRF protection, Input validation
- ✅ **Session Management**: Authentication state handling
- ✅ **View Rendering**: Layout system with components

---

## 🛠 เทคโนโลยีที่ใช้

### Backend
- **PHP** 7.4+
- **Composer** - Dependency management & PSR-4 autoloading
- **MySQL** 5.7+ / MariaDB 10.3+
- **PDO** for database (with Prepared Statements)
- **bcrypt** for password hashing
- **Custom MVC Framework** - Lightweight and fast

### Frontend
- **HTML5**
- **CSS3** + Tailwind CSS 3.x
- **JavaScript** (ES6+)
- **jQuery** 3.x
- **SweetAlert2** 11.x
- **FontAwesome** 6.x

### Development Tools
- **XAMPP** / WAMP / MAMP
- **phpMyAdmin**
- **Git** for version control

---

## 💻 ความต้องการของระบบ

### Server Requirements
- Apache 2.4+
- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.3+
- mod_rewrite enabled

### PHP Extensions
- mysqli
- pdo_mysql
- mbstring
- gd
- fileinfo
- json

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## 🚀 การติดตั้ง

### ขั้นตอนที่ 1: Clone Repository
```bash
cd C:\xampp\htdocs\
git clone https://github.com/your-repo/green_theme.git
cd green_theme
```

### ขั้นตอนที่ 2: สร้างฐานข้อมูล
```sql
CREATE DATABASE green_theme CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### ขั้นตอนที่ 3: Import ฐานข้อมูล
รัน SQL files ตามลำดับ:
```bash
1. database/prefixes.sql
2. database/departments.sql
3. database/users.sql
4. database/service_requests.sql (ถ้ามี)
```

### ขั้นตอนที่ 4: ตั้งค่า Config
แก้ไขไฟล์ `config/database.php`:
```php
$host = 'localhost';
$dbname = 'green_theme';
$username = 'root';
$password = '';
```

### ขั้นตอนที่ 5: เปิดใช้งาน
```
http://localhost/green_theme/
```

### ขั้นตอนที่ 6: Login ด้วย Admin
```
Username: admin
Password: admin123
```

📖 **คำแนะนำเพิ่มเติม**: อ่าน [INSTALLATION_GUIDE.md](database/INSTALLATION_GUIDE.md)

---

## 📁 โครงสร้างโปรเจค

```
green_theme/
├── admin/                      # Admin panel
│   ├── api/                   # Admin API endpoints
│   │   ├── departments_api.php
│   │   ├── service_requests_api.php
│   │   └── check_department_code.php
│   ├── departments.php        # Department management
│   ├── service_requests.php   # Service requests management
│   └── index.php              # Admin dashboard
│
├── api/                       # Public API endpoints
│   ├── get_departments.php
│   └── submit_request.php
│
├── config/                    # Configuration files
│   └── database.php
│
├── css/                       # Stylesheets
│   └── styles.css
│
├── database/                  # Database files
│   ├── prefixes.sql           # Prefixes table
│   ├── departments.sql        # Departments table
│   ├── users.sql              # Users table
│   ├── add_username_field.sql
│   ├── MIGRATION_INSTRUCTIONS.md
│   └── INSTALLATION_GUIDE.md
│
├── forms/                     # Service request forms
│   ├── service-form-fields-EMAIL.php
│   ├── service-form-fields-NAS.php
│   └── ...
│
├── images/                    # Images and logos
│   └── logo/
│       └── rangsit-big-logo.png
│
├── js/                        # JavaScript files
│   └── main.js
│
├── uploads/                   # Uploaded files
│   └── requests/
│
├── index.php                  # Homepage
├── login.php                  # Login page
├── register.php               # Registration page
├── logout.php                 # Logout handler
├── request-form.php           # Service request form
├── get_request_status.php     # Check request status
└── README.md
```

---

## 📚 การใช้งาน

### สำหรับผู้ใช้ทั่วไป

#### 1. สมัครสมาชิก
```
http://localhost/green_theme/register.php
```
- กรอกข้อมูล: Username, คำนำหน้า, ชื่อ-นามสกุล, Email, รหัสผ่าน
- กด "สมัครสมาชิก"

#### 2. เข้าสู่ระบบ
```
http://localhost/green_theme/login.php
```
- ใส่ Username หรือ Email + Password
- กด "เข้าสู่ระบบ"

#### 3. ขอใช้บริการ
```
http://localhost/green_theme/request-form.php?service=EMAIL
```
- เลือกประเภทบริการ
- กรอกข้อมูล
- Upload เอกสาร (ถ้ามี)
- ส่งคำขอ

#### 4. ติดตามสถานะ
```
http://localhost/green_theme/get_request_status.php?request_id=XXX
```

### สำหรับ Admin

#### 1. เข้า Admin Panel
```
http://localhost/green_theme/admin/
```

#### 2. จัดการหน่วยงาน
```
http://localhost/green_theme/admin/departments.php
```
- เพิ่ม/แก้ไข/ลบ หน่วยงาน
- เลือก Level → เลือก Parent → กรอกข้อมูล
- Real-time check รหัสซ้ำ

#### 3. จัดการคำขอบริการ
```
http://localhost/green_theme/admin/service_requests.php
```
- ดูรายการคำขอทั้งหมด
- กรองตาม: สถานะ, ประเภทบริการ, ความสำคัญ
- อัปเดตสถานะ
- มอบหมายงาน
- ลบคำขอ
- Bulk actions

---

## 🔌 API Documentation

### Public APIs

#### Get Departments (Cascade)
```http
GET /api/get_departments.php?level=1&parent_id=0
```

**Response**:
```json
{
  "success": true,
  "departments": [
    {
      "department_id": 1,
      "department_code": "DEPT001",
      "department_name": "สำนักปลัด",
      "level": 1
    }
  ]
}
```

#### Submit Request
```http
POST /api/submit_request.php
```

**Body**:
```json
{
  "service_code": "EMAIL",
  "department": "สำนักปลัด",
  "description": "ขอ email account",
  ...
}
```

### Admin APIs

#### Update Request Status
```http
POST /admin/api/service_requests_api.php
```

**Body**:
```
action=update_status
id=123
status=completed
admin_notes=อนุมัติแล้ว
```

#### Check Department Code
```http
GET /admin/api/check_department_code.php?code=DEPT001
```

**Response**:
```json
{
  "available": false,
  "message": "รหัสนี้ถูกใช้งานแล้ว"
}
```

---

## 📸 Screenshots

### Homepage
![Homepage](screenshots/homepage.png)

### Login Page
![Login](screenshots/login.png)

### Register Page
![Register](screenshots/register.png)

### Request Form
![Request Form](screenshots/request-form.png)

### Admin Dashboard
![Admin Dashboard](screenshots/admin-dashboard.png)

### Department Management
![Departments](screenshots/departments.png)

---

## 🔒 Security Features

- ✅ Password hashing with bcrypt
- ✅ SQL Injection prevention (Prepared Statements)
- ✅ XSS protection
- ✅ CSRF protection
- ✅ Session management
- ✅ Input validation
- ✅ File upload validation
- ✅ Role-based access control

---

## 🤝 Contributing

เรายินดีรับ contributions!

1. Fork โปรเจค
2. สร้าง feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to branch (`git push origin feature/AmazingFeature`)
5. เปิด Pull Request

---

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

---

## 👥 ทีมพัฒนา

- **Lead Developer**: ทีมพัฒนาระบบดิจิทัล
- **UI/UX Designer**: ทีมออกแบบ
- **Project Manager**: เทศบาลนครรังสิต

---

## 📞 ติดต่อ

- **Email**: support@rangsit.go.th
- **Tel**: 02-XXX-XXXX
- **Website**: https://www.rangsit.go.th

---

## 🙏 Acknowledgments

- [Tailwind CSS](https://tailwindcss.com/)
- [SweetAlert2](https://sweetalert2.github.io/)
- [FontAwesome](https://fontawesome.com/)
- [PHP](https://www.php.net/)
- [MySQL](https://www.mysql.com/)

---

**Made with ❤️ by เทศบาลนครรังสิต**

**เวอร์ชัน**: 1.0.0 | **วันที่อัปเดต**: 30 ธันวาคม 2568
