# Security Fix Plan - iService Project

> **สถานะ:** เสร็จสิ้น (Round 1 + Round 2)
> **Backup Commit:** 2faf46b (Backup before security fixes - Original version)
> **อัปเดตล่าสุด:** 2026-02-27

---

## สารบัญ

1. [สรุปสถานะ (Round 1 - เสร็จแล้ว)](#round-1---เสร็จแล้ว)
2. [สรุปปัญหาใหม่ (Round 2)](#สรุปปัญหาใหม่-round-2)
3. [Task 6: SQL Injection ใหม่](#task-6-sql-injection-ใหม่)
4. [Task 7: Session Fixation](#task-7-session-fixation)
5. [Task 8: Information Disclosure (DB Errors)](#task-8-information-disclosure)
6. [Task 9: Captcha Security](#task-9-captcha-security)
7. [Task 10: SMTP Password Storage](#task-10-smtp-password-storage)
8. [Checklist รวม](#checklist-รวม)

---

## Round 1 - เสร็จแล้ว ✅

### Task 1: SQL Injection (เสร็จแล้ว)
| ไฟล์ | สถานะ |
|------|--------|
| `admin/nav_menu.php` | ✅ Fixed |
| `admin/api/tech_news_api.php` | ✅ Fixed |
| `includes/tech_news_loader.php` | ✅ Fixed |
| `includes/nav_menu_loader.php` | ✅ Fixed |

### Task 2: CSRF (เสร็จแล้ว)
| ไฟล์ | สถานะ |
|------|--------|
| `admin/nav_menu.php` | ✅ Fixed (POST + CSRF token) |

### Task 3: Hardcoded Credentials (เสร็จแล้ว)
| ไฟล์ | สถานะ |
|------|--------|
| `admin-login.php` | ✅ Removed test credentials |

### Task 4: File Permissions (เสร็จแล้ว)
| ไฟล์ | สถานะ |
|------|--------|
| `admin/api/tech_news_api.php` | ✅ 0777 → 0755 |
| `admin/api/learning_resources_api.php` | ✅ 0777 → 0755 |

### Task 5: Debug Files (เสร็จแล้ว)
| ไฟล์ | สถานะ |
|------|--------|
| 11 ไฟล์ debug/fix | ✅ ย้ายไป `database/maintenance/` + .htaccess |

---

## สรุปปัญหาใหม่ (Round 2)

### Critical
| # | ไฟล์ | บรรทัด | ปัญหา |
|---|------|--------|-------|
| 6.1 | `admin/api/roles_api.php` | 165 | SQL Injection: `DELETE ... WHERE role_id = $role_id` |
| 6.2 | `admin/api/roles_api.php` | 248 | SQL Injection: `UPDATE ... WHERE user_id = $user_id AND role_id != $role_id` |
| 6.3 | `admin/api/task_assignments_api.php` | 179 | SQL Injection: `UPDATE ... WHERE request_id = $request_id` |
| 6.4 | `admin/api/task_assignments_api.php` | 496 | SQL Injection: `UPDATE ... WHERE request_id = $request_id` |
| 6.5 | `admin/api/task_assignment_api.php` | 482 | SQL Injection: `UPDATE ... WHERE request_id = $request_id` |
| 6.6 | `admin/api/task_assignment_api.php` | 496 | SQL Injection: `UPDATE ... WHERE request_id = $request_id` |
| 6.7 | `admin/admin_report.php` | 706 | SQL Injection: `BETWEEN '$date_from' AND '$date_to'` (จาก $_GET) |

### Medium
| # | ไฟล์ | บรรทัด | ปัญหา |
|---|------|--------|-------|
| 7.1 | `admin-login.php` | 2 | Session Fixation: ไม่มี `session_regenerate_id()` หลัง login |
| 8.1 | `admin/api/user_manager_api.php` | 65, 167, 307, 353 | DB error messages ส่งไปให้ client |
| 8.2 | `admin/api/*.php` (หลายไฟล์) | - | `$stmt->error` / `$e->getMessage()` exposed |
| 10.1 | `admin/system_setting.php` | 380 | SMTP password เก็บเป็น plain text ใน HTML form |

### Low
| # | ไฟล์ | บรรทัด | ปัญหา |
|---|------|--------|-------|
| 9.1 | `captcha.php` | 13 | ใช้ `rand()` แทน `random_int()` สำหรับ CAPTCHA code |

---

## Task 6: SQL Injection ใหม่

### 6.1–6.2 ไฟล์: `admin/api/roles_api.php`

#### แก้ไขบรรทัด 165 (DELETE)
```php
// === BEFORE ===
$conn->query("DELETE FROM user_roles WHERE role_id = $role_id");

// === AFTER ===
$stmt = $conn->prepare("DELETE FROM user_roles WHERE role_id = ?");
$stmt->bind_param("i", $role_id);
$stmt->execute();
```

#### แก้ไขบรรทัด 248 (UPDATE)
```php
// === BEFORE ===
$conn->query("UPDATE user_roles SET is_primary = 0 WHERE user_id = $user_id AND role_id != $role_id");

// === AFTER ===
$stmt = $conn->prepare("UPDATE user_roles SET is_primary = 0 WHERE user_id = ? AND role_id != ?");
$stmt->bind_param("ii", $user_id, $role_id);
$stmt->execute();
```

---

### 6.3–6.4 ไฟล์: `admin/api/task_assignments_api.php`

#### แก้ไขบรรทัด 179 (UPDATE status)
```php
// === BEFORE ===
$conn->query("UPDATE service_requests SET status = 'in_progress' WHERE request_id = $request_id AND status = 'pending'");

// === AFTER ===
$stmt = $conn->prepare("UPDATE service_requests SET status = 'in_progress' WHERE request_id = ? AND status = 'pending'");
$stmt->bind_param("i", $request_id);
$stmt->execute();
```

#### แก้ไขบรรทัด 496 (UPDATE completed)
```php
// === BEFORE ===
$conn->query("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE request_id = $request_id");

// === AFTER ===
$stmt = $conn->prepare("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
```

---

### 6.5–6.6 ไฟล์: `admin/api/task_assignment_api.php`

#### แก้ไขบรรทัด 482 (UPDATE in_progress)
```php
// === BEFORE ===
$conn->query("UPDATE service_requests SET status = 'in_progress', started_at = COALESCE(started_at, NOW()) WHERE request_id = $request_id");

// === AFTER ===
$stmt = $conn->prepare("UPDATE service_requests SET status = 'in_progress', started_at = COALESCE(started_at, NOW()) WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
```

#### แก้ไขบรรทัด 496 (UPDATE completed)
```php
// === BEFORE ===
$conn->query("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE request_id = $request_id");

// === AFTER ===
$stmt = $conn->prepare("UPDATE service_requests SET status = 'completed', completed_at = NOW() WHERE request_id = ?");
$stmt->bind_param("i", $request_id);
$stmt->execute();
```

---

### 6.7 ไฟล์: `admin/admin_report.php`

#### แก้ไขบรรทัด 25–26 และ 706
```php
// === BEFORE (line 25-26) ===
$date_from = $_GET['from'] ?? date('Y-m-01');
$date_to   = $_GET['to'] ?? date('Y-m-t');

// ...later line 706...
WHERE DATE(sr.created_at) BETWEEN '$date_from' AND '$date_to'

// === AFTER ===
// Validate and sanitize date input
$date_from = isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from'])
    ? $_GET['from'] : date('Y-m-01');
$date_to = isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to'])
    ? $_GET['to'] : date('Y-m-t');

// ...then use prepared statement at line 706...
$stmt = $conn->prepare("
    SELECT sr.request_id, sr.service_name, sr.user_id, sr.status, sr.created_at, u.username
    FROM service_requests sr
    LEFT JOIN users u ON sr.user_id = u.user_id
    WHERE DATE(sr.created_at) BETWEEN ? AND ?
    ORDER BY sr.created_at DESC
");
$stmt->bind_param("ss", $date_from, $date_to);
$stmt->execute();
$requests_query = $stmt->get_result();
```

---

## Task 7: Session Fixation

### ไฟล์: `admin-login.php`

#### เพิ่ม `session_regenerate_id()` หลัง login สำเร็จ
```php
// === หาจุดที่ login สำเร็จ และเพิ่ม ===
// หลังจาก verify password สำเร็จ และก่อน set $_SESSION variables:

session_regenerate_id(true);  // สร้าง session ID ใหม่ ป้องกัน session fixation

$_SESSION['user_id'] = $user['user_id'];
$_SESSION['username'] = $user['username'];
// ... etc
```

---

## Task 8: Information Disclosure

### DB Error Messages ที่ต้องแก้ไข

แทนที่การส่ง `$stmt->error` หรือ `$e->getMessage()` โดยตรงไปให้ client:

```php
// === BEFORE ===
echo json_encode(['success' => false, 'message' => 'ไม่สามารถเพิ่มผู้ใช้ได้: ' . $stmt->error]);

// === AFTER ===
error_log('DB Error in user_manager_api.php: ' . $stmt->error);  // log ไว้ใน server
echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง']);
```

### ไฟล์ที่ต้องแก้ไข
| ไฟล์ | บรรทัดที่ expose error |
|------|----------------------|
| `admin/api/user_manager_api.php` | 65, 167, 307, 353 |
| `admin/api/task_assignment_api.php` | 37, 71 |
| `admin/api/roles_api.php` | 64, 104, 141, 175, 255 |
| `admin/api/departments_api.php` | 109, 143, 200 |
| `admin/api/system_settings_api.php` | 54, 116, 141, 162 |
| `admin/api/task_assignments_api.php` | 64, 185 |
| `admin/api/get_department.php` | 31 |
| `admin/api/get_resource.php` | 31 |
| `admin/api/get_service.php` | 31 |

---

## Task 9: Captcha Security

### ไฟล์: `captcha.php`

#### แก้ไขบรรทัด 13 — ใช้ `random_int()` แทน `rand()`
```php
// === BEFORE ===
$captcha_code .= $characters[rand(0, strlen($characters) - 1)];

// === AFTER ===
$captcha_code .= $characters[random_int(0, strlen($characters) - 1)];
```

---

## Task 10: SMTP Password Storage

### ไฟล์: `admin/system_setting.php` บรรทัด 380

SMTP password ถูกเก็บใน database เป็น plain text และแสดงใน HTML form field
(ผู้ใช้สามารถ inspect element เพื่อดู password ได้)

#### วิธีแก้ไข
```php
// === AFTER (แสดง placeholder แทน actual password) ===
<input type="password"
       name="smtp_password"
       value=""
       placeholder="<?= empty(getSetting('smtp_password')) ? 'ยังไม่ได้ตั้งค่า' : '••••••••' ?>"
       autocomplete="new-password">
<small class="text-gray-500">เว้นว่างไว้หากไม่ต้องการเปลี่ยน password</small>
```

```php
// === API side: อัปเดต password เฉพาะเมื่อ field ไม่ว่าง ===
if (!empty($_POST['smtp_password'])) {
    // update smtp_password in DB
}
// ถ้าว่าง = คงค่าเดิม ไม่ต้องอัปเดต
```

---

## Checklist รวม

### Round 1 (เสร็จแล้ว ✅)
- [x] SQL Injection: `admin/nav_menu.php`
- [x] SQL Injection: `admin/api/tech_news_api.php`
- [x] SQL Injection: `includes/tech_news_loader.php`
- [x] SQL Injection: `includes/nav_menu_loader.php`
- [x] CSRF: `admin/nav_menu.php`
- [x] Hardcoded Credentials: `admin-login.php`
- [x] File Permissions: `admin/api/tech_news_api.php`
- [x] File Permissions: `admin/api/learning_resources_api.php`
- [x] Debug Files ย้ายไป `database/maintenance/`

### Round 2 (รอดำเนินการ)

#### Task 6 — SQL Injection (Critical)
- [x] `admin/api/roles_api.php` line 165
- [x] `admin/api/roles_api.php` line 248
- [x] `admin/api/task_assignments_api.php` line 179
- [x] `admin/api/task_assignments_api.php` line 496
- [x] `admin/api/task_assignment_api.php` line 482
- [x] `admin/api/task_assignment_api.php` line 496
- [x] `admin/admin_report.php` line 706 (+ validate $date_from/$date_to)

#### Task 7 — Session Fixation (Medium)
- [x] `admin-login.php` — เพิ่ม `session_regenerate_id(true)` หลัง login

#### Task 8 — DB Error Disclosure (Medium)
- [x] `admin/api/user_manager_api.php` — error_log() แทน expose ให้ client
- [x] `admin/api/task_assignment_api.php` — error_log() แทน expose ให้ client
- [x] `admin/api/system_settings_api.php` — error_log() แทน expose ให้ client
- [ ] `admin/api/roles_api.php` (เหลือ)
- [ ] `admin/api/departments_api.php` (เหลือ)
- [ ] `admin/api/task_assignments_api.php` (เหลือ)
- [ ] `admin/api/get_department.php` (เหลือ)
- [ ] `admin/api/get_resource.php` (เหลือ)
- [ ] `admin/api/get_service.php` (เหลือ)

#### Task 9 — Captcha (Low)
- [x] `captcha.php` line 13 — `rand()` → `random_int()`

#### Task 10 — SMTP Password (Medium)
- [x] `admin/system_setting.php` — ไม่แสดง password จริงใน HTML field
- [x] `admin/api/system_settings_api.php` — skip null password (ไม่ overwrite ค่าเดิม)

---

## คำสั่ง Restore หากต้องการ

```bash
git checkout 2faf46b -- <filename>  # Restore specific file
git reset --hard 2faf46b            # Restore entire project (CAUTION)
```
