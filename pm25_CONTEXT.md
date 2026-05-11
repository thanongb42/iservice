# PM2.5 Monitoring System — AI Context File

> อ่านไฟล์นี้ไฟล์เดียวแล้วทำงานต่อได้ทันที ไม่ต้องถามซ้ำ

---

## 1. ภาพรวม

ระบบตรวจวัดคุณภาพอากาศ PM2.5 ของเทศบาลนครรังสิต
ทำงานอยู่บน iService (PHP/MySQL บน XAMPP/cPanel hosting)

**URLs:**
- Public : `https://iservice.rangsitcity.go.th/pm25_dashboard.php`
- Admin  : `https://iservice.rangsitcity.go.th/admin/pm25_dashboard.php`
- Local  : `http://localhost/iservice/pm25_dashboard.php`

---

## 2. ไฟล์ทั้งหมดของระบบ PM2.5

```
pm25_dashboard.php              # Public dashboard (ไม่ต้อง login)
pm25_cron.php                   # Cron script ดึงข้อมูล API → DB
pm25_test.php                   # Debug tool (ลบออกหลังใช้งาน)
admin/pm25_dashboard.php        # Admin dashboard (ต้อง login)
admin/pm25_sensors.php          # จัดการสถานี + วาง pin แผนที่
admin/api/pm25_sensor_api.php   # AJAX สำหรับบันทึกข้อมูลสถานี
admin/admin-layout/sidebar.php  # เพิ่ม menu "PM2.5 Dashboard" + "จัดการสถานี"
database/pm25_sensors.sql           # สร้างตาราง + data 8 สถานี
database/pm25_data_unique_key.sql   # UNIQUE KEY (cid, sensor_timestamp)
database/pm25_add_temp_humi.sql     # เพิ่มคอลัมน์ temperature, humidity
database/pm25_production_import.sql # รวม SQL สำหรับ import production ครั้งเดียว
storage/pm25_cron.log           # Log การทำงาน cron
```

---

## 3. Database Schema

### ตาราง `pm25_sensors` — ข้อมูลอุปกรณ์

```sql
id            INT AUTO_INCREMENT PK
location_name VARCHAR(255)        -- ชื่อสถานที่
cid           VARCHAR(20) UNIQUE  -- Device CID (key เชื่อมกับ pm25_data)
serial_number VARCHAR(50)
sim_number    VARCHAR(20)
lat           DECIMAL(10,7)       -- ละติจูด (ตั้งผ่าน admin map)
lng           DECIMAL(10,7)       -- ลองจิจูด
is_active     TINYINT(1) DEFAULT 1
created_at    TIMESTAMP
updated_at    TIMESTAMP ON UPDATE
```

### ตาราง `pm25_data` — ข้อมูลเซ็นเซอร์

```sql
id               INT AUTO_INCREMENT PK
cid              VARCHAR(32)
pm25             FLOAT
temperature      FLOAT            -- อุณหภูมิ °C
humidity         FLOAT            -- ความชื้น %
co2              FLOAT            -- CO2 ppm
pm1              FLOAT
pm10             FLOAT
pm4              FLOAT            -- ไม่มีใน API (always NULL)
sensor_timestamp INT              -- Unix timestamp จากอุปกรณ์
created_at       TIMESTAMP        -- เวลาที่ cron บันทึก (ใช้แสดง "ดึงข้อมูลล่าสุด")
UNIQUE KEY uq_cid_ts (cid, sensor_timestamp)
```

### 8 สถานี (ข้อมูลจาก new_point.txt)

| id | location_name | cid | lat | lng |
|----|--------------|-----|-----|-----|
| 1 | เทศบาลนครรังสิต | 349454E09A5C | 13.9866344 | 100.6095498 |
| 2 | รร.นครรังสิตสิริเวชชะพันธ์ | 8C4B1432EBB0 | 13.9923837 | 100.6239872 |
| 3 | หอประชุม 100 ปี ธัญญบุรี | 58BF25FD48FC | 13.9926483 | 100.6464740 |
| 4 | รร.นครรังสิตเปรมปรีดิ์ | 58BF25FDB358 | 13.9827666 | 100.6547142 |
| 5 | รร.มัธยมนครรังสิต | 349454E089E4 | 13.9799920 | 100.6555639 |
| 6 | รร.ดวงกมล | 8C4B1432F538 | 13.9843323 | 100.6391633 |
| 7 | รร.นครรังสิตเทพธัญญะอุปถัมภ์ | 349454E08778 | 13.9750619 | 100.6256365 |
| 8 | รร.เพียรปัญญา | 8C4B14327C18 | 13.9734251 | 100.6108696 |

> หมายเหตุ: สถานีที่ 3 CID เดิมใน new_point.txt คือ `58BF25FD48FD` (D) แต่ใน API จริงและ DB คือ `58BF25FD48FC` (C)

---

## 4. Freshnergy API

```
Endpoint : POST https://app.freshnergy.com/api/v2/device
Auth     : Header "Authorization: <JWT_TOKEN>"
Body     : {"cid": ["CID1", "CID2", ...]}
```

**API Token** (อยู่ใน `pm25_cron.php` บรรทัด `$apiKey`):
```
eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJhY2NvdW50Ijp7ImVtYWlsIjoicmFuZ3NpdDYwMDBAZ21haWwuY29tIi...
```
- Email account: `rangsit6000@gmail.com`
- Device quota: 8 อุปกรณ์

**Response structure:**
```json
{
  "status": "success",
  "data": [
    {
      "cid": "349454E09A5C",
      "sensor": {
        "co2": 437, "humi": 65.1, "pm1": 9,
        "pm10": 19, "pm2_5": 25, "temp": 32.6
      },
      "timeStamp": 1778512095
    }
  ]
}
```

**สำคัญ:**
- `timeStamp` อยู่นอก `sensor` (ไม่ใช่ใน sensor)
- API field คือ `pm2_5` (underscore) → บันทึกใน DB คอลัมน์ `pm25`
- ส่ง CID ทีละตัว (individual call) ไม่ batch เพราะ CID ที่ไม่ exist ทำให้ HTTP 500

---

## 5. Cron Setup

### ตัว Script: `pm25_cron.php`

Logic:
1. โหลด CID จาก `pm25_sensors` (active=1) → fallback hardcoded 8 CIDs
2. วนเรียก API ทีละ CID
3. `INSERT IGNORE` ลง `pm25_data` (UNIQUE KEY ป้องกัน duplicate)
4. Log ผลลงใน `storage/pm25_cron.log`

### Production Cron: **cron-job.org** (ฟรี)

- URL: `https://iservice.rangsitcity.go.th/pm25_cron.php`
- Schedule: ทุก 5 นาที
- เหตุผลที่ใช้ cron-job.org แทน cPanel: cPanel cron ไม่ทำงานบน hosting นี้
- cPanel cron ควร **ลบออก** เพื่อไม่ให้ซ้ำซ้อน

### Log format:
```
[2026-05-11 21:31:33] Done — inserted: 8, dup/skip: 0, api_error: 0 / 8 sensors
[2026-05-11 21:04:36] [58BF25FD48FD] HTTP 500 — อาจยังไม่ได้ลงทะเบียนใน API
```

---

## 6. สิ่งที่ Dashboard แสดง

### Public (`pm25_dashboard.php`)
- Header: ปุ่ม "หน้าหลัก" + "เข้าสู่ระบบ"
- Stats bar: จำนวนสถานี, ออนไลน์, PM2.5 เฉลี่ย/สูงสุด, เวลาดึงข้อมูลล่าสุด
- AQI legend bar (แถบสีเกณฑ์ PM2.5)
- Sensor cards (8 cards): PM2.5 วงกลมสี, temp, humi, PM1, PM10, CO2
- กราฟเส้น: เลือก metric (PM2.5/PM10/PM1/อุณหภูมิ/ความชื้น/CO2) × เลือกสถานี
- แผนที่ Leaflet + popup แสดงทุกค่าเมื่อคลิก pin
- ไม่แสดง CID/S/N/SIM (ความปลอดภัย)

### Admin (`admin/pm25_dashboard.php`)
- เหมือน Public แต่มีเพิ่ม: CID, S/N, SIM ใน card
- ปุ่ม "จัดการสถานี" และ "หน้าสาธารณะ"

### Admin Sensor Manager (`admin/pm25_sensors.php`)
- ตาราง 8 สถานีพร้อม PM2.5 ล่าสุด
- ปุ่ม "แก้ไข" → Modal พร้อม Leaflet map
- ปุ่ม **"ใช้ตำแหน่งปัจจุบัน"** → Geolocation API → วาง pin
- บันทึกผ่าน `admin/api/pm25_sensor_api.php` (JSON POST)

---

## 7. เกณฑ์สี PM2.5 (Thailand EPA)

| ช่วง µg/m³ | สี | Label |
|-----------|-----|-------|
| 0 – 25 | `#16a34a` เขียวเข้ม | ดีมาก |
| 26 – 37 | `#84cc16` เขียวอ่อน | ดี |
| 38 – 50 | `#eab308` เหลือง | ปานกลาง |
| 51 – 90 | `#f97316` ส้ม | เริ่มมีผลกระทบ |
| ≥ 91 | `#ef4444` แดง | มีผลกระทบต่อสุขภาพ |
| null | `#94a3b8` เทา | ไม่มีข้อมูล |

---

## 8. Stack & Libraries

- **PHP 8.2** (production Linux), **PHP 7.4+** (local Windows XAMPP)
- **MySQL** + PDO (`getPDO()` จาก `config/database.php`)
- **Leaflet.js 1.9.4** + OpenStreetMap (map, ไม่ต้อง API key)
- **Chart.js 4.4.0** (กราฟเส้น)
- **Tailwind CSS CDN**
- **FontAwesome 6.4.0**
- `date_default_timezone_set('Asia/Bangkok')` ตั้งทุกไฟล์

---

## 9. DB Connection Pattern

```php
// config/database.php — auto-detect environment
$_is_local = in_array($_SERVER['SERVER_NAME'] ?? '', ['localhost', '127.0.0.1'])
          || (php_sapi_name() === 'cli' && DIRECTORY_SEPARATOR === '\\'); // Windows CLI = local

// Local  : iservice_db (root, no password)
// Prod   : rangsitadmin_iservice_db (rangsitadmin_iservice, IService@2026)
```

---

## 10. สถานะ Production (อัปเดต 2026-05-11)

- [x] ไฟล์ PHP ทุกตัว FTP ขึ้น production แล้ว
- [x] `pm25_production_import.sql` import ใน phpMyAdmin แล้ว (pm25_sensors + UNIQUE KEY)
- [x] `pm25_add_temp_humi.sql` import แล้ว (temperature, humidity columns)
- [x] cron-job.org ตั้งค่าแล้ว: `https://iservice.rangsitcity.go.th/pm25_cron.php` ทุก 5 นาที
- [ ] ลบ `pm25_test.php` ออกจาก production (มี API key)
- [ ] ลบ cPanel cron job ออก (ซ้ำซ้อนกับ cron-job.org)

---

## 11. Known Issues

1. **API Token หมดอายุ** (JWT exp 24 ชั่วโมง) → ต้องขอ token ใหม่จาก app.freshnergy.com ด้วย account `rangsit6000@gmail.com`
2. **สถานีที่ 3** CID `58BF25FD48FC` เคยมีปัญหา HTTP 500 แต่แก้แล้วหลังอัปเดต CID ใน DB
3. **cPanel cron** ไม่ทำงานบน hosting นี้ → ใช้ cron-job.org แทน

---

## 12. Git

- Branch: `2026-03-08-ji93`
- Remote: `https://github.com/thanongb42/iservice.git`
- Latest commit: `11210f4` (Add login/home buttons to public dashboard)
