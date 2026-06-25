# iService GDCC VM1 Cutover Plan - 2026-06-25

## Goal

Move `https://iservice.rangsitcity.go.th/` from the old hosting IP to GDCC VM1 using the same Docker/Nginx pattern as `discover_rangsit`.

## Current Baseline

- Current public DNS: `iservice.rangsitcity.go.th -> 203.150.199.248`
- Current public checks passed before cutover:
  - `https://iservice.rangsitcity.go.th/` -> 200
  - `https://iservice.rangsitcity.go.th/request-form.php` -> 200
  - `https://iservice.rangsitcity.go.th/api/get_departments.php?level=1` -> 200
- Target VM1 IP: `112.121.157.74`
- Target project path: `/home/rssc/web-server/project-php/iservice`
- Actual storage root on VM1: `/data/rssc/web-server`
- PHP service: `php-app`
- Reverse proxy service: `reverse-proxy`
- MariaDB container: `id_booking_db`

## Local Prep Already Done

- `config/database.php` now supports VM1 overrides from `.env.gdcc` without committing real credentials.
- `.env.gdcc` is ignored by git.
- `.htaccess` HTTPS redirect now respects `X-Forwarded-Proto: https`, preventing reverse-proxy redirect loops.

## Files/Data To Upload

Upload the full local folder:

```text
C:\xampp\htdocs\iservice
```

to:

```text
/home/rssc/web-server/project-php/iservice
```

Important writable/public data folders:

```text
storage/uploads/
storage/backups/
public/uploads/
uploads/
storage/pm25_cron.log
```

Do not upload local-only debug/check files unless intentionally needed. The repo currently contains many `check_*.php`, `debug_*.php`, and `test_*.php` files.

## VM1 Environment File

Create this file on VM1 only:

```bash
nano /home/rssc/web-server/project-php/iservice/.env.gdcc
```

Template:

```dotenv
DB_HOST=id-booking-db
DB_NAME=iservicedb
DB_USER=iservice_user
DB_PASS=REPLACE_WITH_REAL_PASSWORD
DB_CHARSET=utf8mb4
APP_ENV=production
APP_DEBUG=false
APP_URL=https://iservice.rangsitcity.go.th/
```

Never commit or paste the real password in docs/chat.

## Database

Recommended VM1 database/user:

```text
Database: iservicedb
User: iservice_user
Container: id_booking_db
```

Create DB/user inside MariaDB container, then import a sanitized SQL dump.

Latest production dump received:

```text
database_Export/rangsitadmin_iservice_db.sql
```

Prepared VM1-safe import copy:

```text
database_Export/rangsitadmin_iservice_db.vm1.sql
```

The VM1 copy has `DEFINER=...` removed from views and has no `CREATE DATABASE` or `USE ...` statements, so import it into the selected VM1 database explicitly.

Import pattern:

```bash
sudo docker compose exec -T id-booking-db mariadb \
  -u iservice_user -p iservicedb < rangsitadmin_iservice_db.vm1.sql
```

## Nginx HTTP Config For Staging/Test

Create:

```bash
nano ~/web-server/nginx/conf.d/iservice.conf
```

HTTP-first config:

```nginx
server {
    listen 80;
    server_name iservice.rangsitcity.go.th;

    client_max_body_size 220m;

    location ~* /(\.env|\.git|composer\.(json|lock)|api_token\.txt|.*\.sql)$ {
        return 404;
    }

    location ^~ /config/ { return 404; }
    location ^~ /database/ { return 404; }
    location ^~ /app/ { return 404; }
    location ^~ /includes/ { return 404; }
    location ^~ /logs/ { return 404; }
    location ^~ /vendor/ { return 404; }

    # Support legacy absolute URLs such as /iservice/storage/...
    location ^~ /iservice/ {
        proxy_pass http://php-app:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto http;
    }

    location / {
        proxy_pass http://php-app:80/iservice/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto http;
    }
}
```

Test with forced DNS before changing cPanel DNS:

```bash
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/request-form.php
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/api/get_departments.php?level=1
```

Expected: `200`, `200`, `200`.

## DNS Cutover

Change cPanel DNS:

```text
iservice.rangsitcity.go.th.  A  112.121.157.74
```

Check on VM1:

```bash
getent hosts iservice.rangsitcity.go.th
```

Expected:

```text
112.121.157.74 iservice.rangsitcity.go.th
```

## SSL

Only run Certbot after DNS points to VM1:

```bash
sudo certbot certonly --webroot \
  -w /home/rssc/web-server/project-php/iservice \
  -d iservice.rangsitcity.go.th
```

Expected cert paths:

```text
/etc/letsencrypt/live/iservice.rangsitcity.go.th/fullchain.pem
/etc/letsencrypt/live/iservice.rangsitcity.go.th/privkey.pem
```

## HTTPS Nginx Block

After SSL is issued, add a 443 server block using the same proxy rules as HTTP, with:

```nginx
listen 443 ssl;
server_name iservice.rangsitcity.go.th;
ssl_certificate /etc/letsencrypt/live/iservice.rangsitcity.go.th/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/iservice.rangsitcity.go.th/privkey.pem;
```

Set:

```nginx
proxy_set_header X-Forwarded-Proto https;
```

Then test/reload:

```bash
cd ~/web-server
sudo docker compose exec reverse-proxy nginx -t
sudo docker compose exec reverse-proxy nginx -s reload
```

## Final Verification

Use GET-style curl checks, not only `curl -I`:

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/request-form.php
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/api/get_departments.php?level=1
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/admin/admin_dashboard.php
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/pm25_dashboard.php
```

Expected:

- Public pages/API: 200
- Admin pages while logged out: 200 login page or 302 redirect to login

Also verify in browser:

- homepage
- service request form
- tracking page
- admin login
- PM2.5 pages
- uploaded attachments/images
- LINE login/callback URLs
- cron URLs

## Cron / External Integrations

Update external services after cutover if needed:

- cron-job.org URL: `https://iservice.rangsitcity.go.th/pm25_cron.php`
- internal job reminder cron: `api/cron_job_reminder.php`
- LINE Login callback URLs:
  - `https://iservice.rangsitcity.go.th/admin/line_callback.php`
  - `https://iservice.rangsitcity.go.th/line_login_callback.php`

## Do Not Do Too Early

- Do not delete the old cPanel folder/domain until VM1 has been stable for 1-2 days.
- Do not print or commit `.env.gdcc`, DB passwords, private keys, LINE channel secrets, or access tokens.
- Do not rely on `curl -I` alone; use GET-style status checks.