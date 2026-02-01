# Security Fix Plan - iService Project

> **สถานะ:** พร้อมดำเนินการ
> **Backup Commit:** 2faf46b (Backup before security fixes - Original version)
> **วันที่สร้าง:** 2026-02-01

---

## สารบัญ

1. [สรุปปัญหาที่พบ](#สรุปปัญหาที่พบ)
2. [Task 1: แก้ไข SQL Injection](#task-1-แก้ไข-sql-injection)
3. [Task 2: แก้ไข CSRF Vulnerabilities](#task-2-แก้ไข-csrf-vulnerabilities)
4. [Task 3: ลบ Hardcoded Credentials](#task-3-ลบ-hardcoded-credentials)
5. [Task 4: แก้ไข File Permissions](#task-4-แก้ไข-file-permissions)
6. [Task 5: ย้าย Debug Files](#task-5-ย้าย-debug-files)
7. [การทดสอบ](#การทดสอบ)
8. [Checklist](#checklist)

---

## สรุปปัญหาที่พบ

### 1. SQL Injection (Critical) - 4 ไฟล์
| ไฟล์ | บรรทัด | ปัญหา |
|------|--------|-------|
| `admin/nav_menu.php` | 74, 77, 87, 99, 114 | Direct variable interpolation ใน query |
| `admin/api/tech_news_api.php` | 39, 159 | Direct variable interpolation |
| `includes/tech_news_loader.php` | 85 | Direct variable interpolation |
| `includes/nav_menu_loader.php` | 30 | Direct variable interpolation |

### 2. CSRF Vulnerabilities (High) - 1 ไฟล์
| ไฟล์ | บรรทัด | ปัญหา |
|------|--------|-------|
| `admin/nav_menu.php` | 70, 85 | DELETE/TOGGLE ผ่าน GET request |

### 3. Hardcoded Credentials (Critical) - 1 ไฟล์
| ไฟล์ | บรรทัด | ปัญหา |
|------|--------|-------|
| `admin-login.php` | 238-243 | แสดง Username: admin, Password: admin123 |

### 4. Insecure File Permissions (Medium) - 2 ไฟล์
| ไฟล์ | บรรทัด | ปัญหา |
|------|--------|-------|
| `admin/api/tech_news_api.php` | 66 | `mkdir($dir, 0777)` |
| `admin/api/learning_resources_api.php` | 42 | `mkdir($dir, 0777)` |

### 5. Debug Files ที่เปิดเผยข้อมูลสำคัญ (High) - 11 ไฟล์
ไฟล์เหล่านี้อยู่ใน root directory และเข้าถึงได้โดยตรง

---

## Task 1: แก้ไข SQL Injection

### 1.1 ไฟล์: `admin/nav_menu.php`

#### แก้ไขบรรทัด 74, 77 (DELETE operations)
```php
// === BEFORE (line 74, 77) ===
$conn->query("DELETE FROM nav_menu WHERE parent_id = $id");
$conn->query("DELETE FROM nav_menu WHERE id = $id");

// === AFTER ===
$stmt = $conn->prepare("DELETE FROM nav_menu WHERE parent_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

$stmt = $conn->prepare("DELETE FROM nav_menu WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

#### แก้ไขบรรทัด 87 (TOGGLE operation)
```php
// === BEFORE (line 87) ===
$conn->query("UPDATE nav_menu SET is_active = NOT is_active WHERE id = $id");

// === AFTER ===
$stmt = $conn->prepare("UPDATE nav_menu SET is_active = NOT is_active WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
```

#### แก้ไขบรรทัด 99 (SELECT children)
```php
// === BEFORE (line 99) ===
$child_result = $conn->query("SELECT * FROM nav_menu WHERE parent_id = $menu_id ORDER BY menu_order ASC");

// === AFTER ===
$child_stmt = $conn->prepare("SELECT * FROM nav_menu WHERE parent_id = ? ORDER BY menu_order ASC");
$child_stmt->bind_param("i", $menu_id);
$child_stmt->execute();
$child_result = $child_stmt->get_result();
```

#### แก้ไขบรรทัด 114 (SELECT by ID)
```php
// === BEFORE (line 114) ===
$result = $conn->query("SELECT * FROM nav_menu WHERE id = $id");

// === AFTER ===
$stmt = $conn->prepare("SELECT * FROM nav_menu WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
```

---

### 1.2 ไฟล์: `admin/api/tech_news_api.php`

#### แก้ไขบรรทัด 39 (check pinned count for update)
```php
// === BEFORE (line 39) ===
$pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1 AND id != $id")->fetch_assoc()['count'];

// === AFTER ===
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1 AND id != ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pinned_count = $stmt->get_result()->fetch_assoc()['count'];
```

#### แก้ไขบรรทัด 159 (check pinned count for toggle)
```php
// === BEFORE (line 159) ===
$pinned_count = $conn->query("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1 AND id != $id")->fetch_assoc()['count'];

// === AFTER ===
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM tech_news WHERE is_pinned = 1 AND id != ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$pinned_count = $stmt->get_result()->fetch_assoc()['count'];
```

---

### 1.3 ไฟล์: `includes/tech_news_loader.php`

#### แก้ไขบรรทัด 85 (increment view count)
```php
// === BEFORE (line 85) ===
$conn->query("UPDATE tech_news SET view_count = view_count + 1 WHERE id = $id");

// === AFTER ===
$update_stmt = $conn->prepare("UPDATE tech_news SET view_count = view_count + 1 WHERE id = ?");
$update_stmt->bind_param("i", $id);
$update_stmt->execute();
```

---

### 1.4 ไฟล์: `includes/nav_menu_loader.php`

#### แก้ไขบรรทัด 26-36 (get child menus)
```php
// === BEFORE (lines 26-36) ===
while ($parent = $parent_result->fetch_assoc()) {
    $parent['children'] = [];

    // Get child menus
    $child_query = "SELECT * FROM nav_menu WHERE parent_id = {$parent['id']} AND is_active = 1 ORDER BY menu_order ASC";
    $child_result = $conn->query($child_query);

    if ($child_result) {
        while ($child = $child_result->fetch_assoc()) {
            $parent['children'][] = $child;
        }
    }

    $menus[] = $parent;
}

// === AFTER ===
while ($parent = $parent_result->fetch_assoc()) {
    $parent['children'] = [];

    // Get child menus using prepared statement
    $child_stmt = $conn->prepare("SELECT * FROM nav_menu WHERE parent_id = ? AND is_active = 1 ORDER BY menu_order ASC");
    $child_stmt->bind_param("i", $parent['id']);
    $child_stmt->execute();
    $child_result = $child_stmt->get_result();

    if ($child_result) {
        while ($child = $child_result->fetch_assoc()) {
            $parent['children'][] = $child;
        }
    }

    $menus[] = $parent;
}
```

---

## Task 2: แก้ไข CSRF Vulnerabilities

### ไฟล์: `admin/nav_menu.php`

#### 2.1 เพิ่ม CSRF token generation (หลังบรรทัด 11)
```php
// หลังจาก session_start();
// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
```

#### 2.2 แก้ไข DELETE action (lines 70-82)
```php
// === BEFORE ===
if ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // ... delete logic
}

// === AFTER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete' && isset($_POST['id'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
    } else {
        $id = (int)$_POST['id'];

        // Delete children first
        $stmt = $conn->prepare("DELETE FROM nav_menu WHERE parent_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        // Delete parent
        $stmt = $conn->prepare("DELETE FROM nav_menu WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $message = "ลบเมนูสำเร็จ!";
        } else {
            $error = "เกิดข้อผิดพลาด: " . $conn->error;
        }
    }
}
```

#### 2.3 แก้ไข TOGGLE action (lines 85-89)
```php
// === BEFORE ===
if ($action === 'toggle' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->query("UPDATE nav_menu SET is_active = NOT is_active WHERE id = $id");
    $message = "เปลี่ยนสถานะสำเร็จ!";
}

// === AFTER ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'toggle' && isset($_POST['id'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid CSRF token";
    } else {
        $id = (int)$_POST['id'];
        $stmt = $conn->prepare("UPDATE nav_menu SET is_active = NOT is_active WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $message = "เปลี่ยนสถานะสำเร็จ!";
    }
}
```

#### 2.4 แก้ไข HTML Toggle buttons (ประมาณบรรทัด 193, 229)
```php
// === BEFORE ===
<a href="?action=toggle&id=<?= $menu['id'] ?>" class="inline-block">

// === AFTER ===
<form method="POST" action="?action=toggle" style="display:inline;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="id" value="<?= $menu['id'] ?>">
    <button type="submit" class="inline-block" style="background:none;border:none;cursor:pointer;">
```

#### 2.5 แก้ไข HTML Delete buttons (ประมาณบรรทัด 209, 245)
```php
// === BEFORE ===
<a href="?action=delete&id=<?= $menu['id'] ?>" class="text-red-600 hover:text-red-800"
   onclick="return confirm('ต้องการลบเมนูนี้และเมนูย่อยทั้งหมดหรือไม่?')">

// === AFTER ===
<form method="POST" action="?action=delete" style="display:inline;"
      onsubmit="return confirm('ต้องการลบเมนูนี้และเมนูย่อยทั้งหมดหรือไม่?')">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
    <input type="hidden" name="id" value="<?= $menu['id'] ?>">
    <button type="submit" class="text-red-600 hover:text-red-800" style="background:none;border:none;cursor:pointer;">
        <i class="fas fa-trash"></i>
    </button>
</form>
```

---

## Task 3: ลบ Hardcoded Credentials

### ไฟล์: `admin-login.php`

#### ลบ info box (lines 238-243)
```php
// === ลบส่วนนี้ออกทั้งหมด ===
            <!-- Info Box for Testing -->
            <div class="info-box">
                <strong><i class="fas fa-info-circle mr-1"></i>ข้อมูลสำหรับทดสอบ:</strong>
                <p><strong>Username:</strong> admin</p>
                <p><strong>Password:</strong> admin123</p>
            </div>
```

---

## Task 4: แก้ไข File Permissions

### 4.1 ไฟล์: `admin/api/tech_news_api.php`

#### แก้ไขบรรทัด 66
```php
// === BEFORE ===
mkdir($upload_dir, 0777, true);

// === AFTER ===
mkdir($upload_dir, 0755, true);
```

### 4.2 ไฟล์: `admin/api/learning_resources_api.php`

#### แก้ไขบรรทัด 42
```php
// === BEFORE ===
mkdir($upload_dir, 0777, true);

// === AFTER ===
mkdir($upload_dir, 0755, true);
```

---

## Task 5: ย้าย Debug Files

### ไฟล์ที่ต้องย้าย (11 ไฟล์)
จาก root directory ไปยัง `database/maintenance/`:

1. `fix_admin_password.php`
2. `debug_admin.php`
3. `check_table_structure.php`
4. `check_learning_resources.php`
5. `debug_images.php`
6. `fix_image_paths.php`
7. `fix_placeholder_images.php`
8. `fix_service_requests_view.php`
9. `fix_view_add_service_name.php`
10. `fix_view_quick.php`
11. `check_assigned_to_type.php`

### ขั้นตอนการย้าย

#### 5.1 สร้าง folder (ถ้ายังไม่มี)
```bash
mkdir -p database/maintenance
```

#### 5.2 ย้ายไฟล์
```bash
mv fix_admin_password.php database/maintenance/
mv debug_admin.php database/maintenance/
mv check_table_structure.php database/maintenance/
mv check_learning_resources.php database/maintenance/
mv debug_images.php database/maintenance/
mv fix_image_paths.php database/maintenance/
mv fix_placeholder_images.php database/maintenance/
mv fix_service_requests_view.php database/maintenance/
mv fix_view_add_service_name.php database/maintenance/
mv fix_view_quick.php database/maintenance/
mv check_assigned_to_type.php database/maintenance/
```

#### 5.3 สร้าง .htaccess ป้องกันการเข้าถึง
สร้างไฟล์ `database/maintenance/.htaccess`:
```apache
# Deny access to all files in this directory
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
```

---

## การทดสอบ

### 1. SQL Injection Test
- ทดสอบใส่ `' OR '1'='1` ใน input fields
- ทดสอบ URL parameter: `?id=1 OR 1=1`
- ตรวจสอบว่าไม่มี error message แสดง SQL syntax

### 2. CSRF Test
- ตรวจสอบว่า DELETE/TOGGLE ต้องใช้ POST method
- ทดสอบส่ง request โดยไม่มี csrf_token (ควร error)
- ทดสอบส่ง request ด้วย csrf_token ผิด (ควร error)

### 3. Credentials Test
- เปิด `admin-login.php` และตรวจสอบว่าไม่มี default credentials แสดง

### 4. File Permissions Test
- ลบ folder uploads แล้ว upload ไฟล์ใหม่
- ตรวจสอบว่า folder ที่สร้างใหม่มี permission 755 (ไม่ใช่ 777)

### 5. Debug Files Test
- ทดสอบเข้าถึง `database/maintenance/fix_admin_password.php` โดยตรง
- ควรได้รับ 403 Forbidden

---

## Checklist

### Pre-Implementation
- [x] Git backup created (commit: 2faf46b)
- [ ] Reviewed all files to be modified

### Task 1: SQL Injection
- [ ] `admin/nav_menu.php` - Lines 74, 77, 87, 99, 114
- [ ] `admin/api/tech_news_api.php` - Lines 39, 159
- [ ] `includes/tech_news_loader.php` - Line 85
- [ ] `includes/nav_menu_loader.php` - Line 30

### Task 2: CSRF
- [ ] Added CSRF token generation
- [ ] Modified DELETE action to POST
- [ ] Modified TOGGLE action to POST
- [ ] Updated HTML toggle buttons
- [ ] Updated HTML delete buttons

### Task 3: Hardcoded Credentials
- [ ] Removed info box from `admin-login.php`

### Task 4: File Permissions
- [ ] `admin/api/tech_news_api.php` - Line 66
- [ ] `admin/api/learning_resources_api.php` - Line 42

### Task 5: Debug Files
- [ ] Created `database/maintenance/` folder
- [ ] Moved all 11 debug files
- [ ] Created `.htaccess` protection

### Post-Implementation
- [ ] All tests passed
- [ ] Git commit with security fixes
- [ ] Verified no functionality broken

---

## Notes for AI Continuation

1. **Backup is ready:** Commit `2faf46b` contains the original code before any fixes
2. **Start with Task 1:** SQL Injection fixes are the highest priority
3. **Test after each task:** Verify functionality after each major change
4. **Commit after each task:** Create separate commits for each task for easy rollback
5. **Don't skip CSRF:** The CSRF fix is more complex as it requires HTML changes

### Commands to restore backup if needed:
```bash
git checkout 2faf46b -- <filename>  # Restore specific file
git reset --hard 2faf46b            # Restore entire project (CAUTION: loses all changes)
```
