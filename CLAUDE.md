# CLAUDE.md — iService Project Guide

## Project Overview

**iService** is a digital municipal service request system for **เทศบาลนครรังสิต (Rangsit Municipality)**. It allows citizens to submit IT/digital service requests online, and allows admin/staff to manage, assign, and track those requests.

- **Stack**: PHP 7.4+, MySQL (MySQLi + PDO), Apache/XAMPP, Tailwind CSS, jQuery, SweetAlert2, FontAwesome
- **Local DB**: `iservice_db` on `localhost` (user: `root`, password: empty)
- **Production DB**: `rangsitadmin_iservice_db` — credentials in `config/database.php` (commented out)
- **Base URL (local)**: `http://localhost/iservice/`
- **Composer namespace**: `App\` → `app/` (PSR-4)

## Architecture

The project is **partially refactored to MVC** (~45% complete). Two patterns coexist:

### Legacy (flat PHP — still dominant)
- Root-level pages: `index.php`, `login.php`, `register.php`, `request-form.php`, `tracking.php`, etc.
- Admin pages: `admin/*.php`
- Config: `config/database.php` (provides `$conn` mysqli + `getPDO()`)
- Shared includes: `includes/` (header/footer, email, nav, loaders)
- API endpoints: `api/*.php` and `admin/api/`
- Service form partials: `forms/service-form-fields-*.php`

### MVC (in progress — `app/` + `public/`)
- `app/Config/`, `app/Core/`, `app/Controllers/`, `app/Models/`, `app/Views/`, `app/Services/`, `app/Middleware/`, `app/Helpers/`
- Front controller: `public/index.php`
- Routes: `routes.php`

## Key Files

| File | Purpose |
|------|---------|
| `config/database.php` | DB connection (`$conn` mysqli, `getPDO()` PDO), `clean_input()`, `fix_asset_path()`, `table_exists()` |
| `includes/email_helper.php` | Email notifications to admin/staff on new requests |
| `includes/header_public.php` / `footer_public.php` | Public page layout wrappers |
| `admin/admin_dashboard.php` | Main admin dashboard with statistics |
| `admin/service_requests.php` | Admin: list/filter/assign/update service requests |
| `admin/departments.php` | Admin: 4-level department CRUD |
| `admin/my_tasks.php` | Staff task list |
| `admin/roles_manager.php` | Role management with list/pagination |
| `api/process_request.php` | Handles service request submission |
| `request-form.php` | Public service request form |
| `tracking.php` | Public request status tracking |
| `database/iservice_db.sql` | Main DB dump |
| `database/views.sql` | SQL views (uses `SQL SECURITY INVOKER`) |

## Database

- **Primary connection**: MySQLi (`$conn` global from `config/database.php`)
- **Secondary**: `getPDO()` function for prepared statements
- **Charset**: `utf8mb4`
- Views must use `SQL SECURITY INVOKER` (not DEFINER) to avoid import errors

## Service Types

Forms in `forms/service-form-fields-*.php`:
`EMAIL`, `NAS`, `IT_SUPPORT`, `INTERNET`, `QR_CODE`, `PHOTOGRAPHY`, `WEB_DESIGN`, `PRINTER`, `LED`, `MC`

## Department Structure

4-level hierarchy: สำนัก/กอง → ส่วน → ฝ่าย/กลุ่มงาน → งาน

## User Roles

- `admin` — full access
- `staff` — manage assigned tasks/requests
- `user` — submit and track own requests

## Coding Conventions

- Use MySQLi prepared statements or `getPDO()` — never raw string queries
- Use `clean_input()` only when MySQLi raw query is unavoidable
- Use `fix_asset_path($path, $from_admin)` for upload/asset paths
- Admin pages require session check for `admin` or `staff` role at top of file
- AJAX responses use JSON: `{"success": true/false, "message": "..."}`
- UI: Tailwind CSS utility classes, SweetAlert2 for confirmations/alerts
- Thai language strings are expected throughout UI

## .htaccess Notes

- HTTPS redirect is active (causes issues on local HTTP — comment out the `RewriteRule ^ https://...` block locally if needed)
- `RewriteBase /iservice/`
- Protected dirs (no rewrite): `admin`, `api`, `app`, `config`, `includes`, `storage`, `database`, `forms`, `logs`

## Local Development

- Server: XAMPP on Windows (`C:\xampp\htdocs\iservice\`)
- Start Apache + MySQL via XAMPP Control Panel
- DB admin: `http://localhost/phpmyadmin/`
- No build step — PHP is served directly; Tailwind is loaded via CDN

## Cleanup Notes

Many debug/check files in root (e.g. `check_*.php`, `debug_*.php`, `test_*.php`) are temporary and can be removed after verification. Do not rely on them for production logic.
