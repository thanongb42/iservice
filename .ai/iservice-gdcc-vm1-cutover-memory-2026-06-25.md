# iService GDCC VM1 Cutover Memory - 2026-06-25

> Security note: password, token, private key, LINE secret, DB root password, and DB user password are intentionally redacted. Do not store real secrets in this handoff.

## Current Status

- Goal: move `https://iservice.rangsitcity.go.th/` to GDCC VM1 using the same pattern as `discover_rangsit`.
- VM1 IP: `112.121.157.74`
- SSH user: `rssc`
- Docker root: `/home/rssc/web-server`
- Actual storage: `/data/rssc/web-server`
- Project path: `/home/rssc/web-server/project-php/iservice`
- PHP service/container: `php-app` / `php_app`
- DB service/container: `id-booking-db` / `id_booking_db`
- Reverse proxy service/container: `reverse-proxy` / `reverse_proxy`
- phpMyAdmin tunnel: `ssh -L 8081:127.0.0.1:8081 rssc@112.121.157.74`, then browse `http://127.0.0.1:8081/`
- DB created: `iservicedb`
- DB user created: `iservice_user` for hosts `%` and `localhost`
- Production SQL import succeeded; DB verification shows 49 tables/views, 20 users, 171 departments, 32 service_requests.
- Project files were uploaded to VM1.
- `.env.gdcc` exists on VM1 and has permission `600`.
- PHP app now reads `DB_HOST=id-booking-db`, `DB_NAME=iservicedb`, and `IS_LOCAL=false`.
- PHP image was rebuilt with `gd`, `zip`, and `intl` added.
- Nginx HTTP config exists at `~/web-server/nginx/conf.d/iservice.conf`.
- HTTP tests via VM1 pass: `/`, `/request-form.php`, `/api/get_departments.php?level=1` all return `200`.
- Admin unauthenticated test returns `302`.
- Sensitive file checks: `/config/database.php` returns `404`; `/.env.gdcc` returns `404`.
- `www.iservice.rangsitcity.go.th` already resolved to VM1 and returned `200`.
- At last DNS check, root `iservice.rangsitcity.go.th` still resolved to old IP `203.150.199.248`; wait before SSL.

## DNS Notes

Before DNS edit, cPanel had:

```text
iservice.rangsitcity.go.th.      A 203.150.199.248
www.iservice.rangsitcity.go.th.  A 203.150.199.248
```

Only these web A records should be changed:

```text
iservice.rangsitcity.go.th.      A 112.121.157.74
www.iservice.rangsitcity.go.th.  A 112.121.157.74
```

Do not change DKIM/SPF/cPanel/cpcalendars records unless intentionally migrating mail/cPanel behavior.

Observed after editing DNS:

```bash
getent hosts iservice.rangsitcity.go.th
getent hosts www.iservice.rangsitcity.go.th
```

Output:

```text
203.150.199.248 iservice.rangsitcity.go.th
112.121.157.74  www.iservice.rangsitcity.go.th
```

Interpretation: `www` propagated to VM1; root was still waiting.
## Local Files Prepared

Touched/created local project files:

```text
C:\xampp\htdocs\iservice\config\database.php
C:\xampp\htdocs\iservice\.htaccess
C:\xampp\htdocs\iservice\.gitignore
C:\xampp\htdocs\iservice\database_Export\rangsitadmin_iservice_db.sql
C:\xampp\htdocs\iservice\database_Export\rangsitadmin_iservice_db.vm1.sql
C:\xampp\htdocs\iservice\.ai\iservice-gdcc-vm1-cutover-plan-2026-06-25.md
C:\xampp\htdocs\iservice\.ai\iservice-gdcc-vm1-cutover-memory-2026-06-25.md
C:\xampp\htdocs\iservice\.ai\iservice-gdcc-vm1-installation-runbook-2026-06-25.md
```

Important prep:

- `config/database.php` supports `.env.gdcc` overrides.
- `.env.gdcc` is ignored by git.
- VM1 copy of `config/database.php` was patched so `.env.gdcc` overrides existing Docker env values from `php-app`.
- VM1 `.htaccess` HTTPS redirect was disabled so Nginx can own redirects after SSL.
- `rangsitadmin_iservice_db.vm1.sql` was created from latest production dump with `DEFINER=...` removed.

## Production SQL Dump

Source dump:

```text
C:\xampp\htdocs\iservice\database_Export\rangsitadmin_iservice_db.sql
```

Metadata:

```text
Length        : 4965649
LastWriteTime : 2026-06-25 16:06:26
Database      : rangsitadmin_iservice_db
Generation    : Jun 25, 2026 at 04:06 PM
```

Detected views with `DEFINER=rangsitadmin@localhost`:

```text
v_service_requests_full
v_task_assignments_full
v_users_full
v_user_roles
```

Sanitized dump:

```text
C:\xampp\htdocs\iservice\database_Export\rangsitadmin_iservice_db.vm1.sql
```

Metadata:

```text
Length        : 4965509
LastWriteTime : 2026-06-25 16:09:47
```

Validation:

```text
No DEFINER=
No CREATE DATABASE
No USE ...
```

Sanitizing command used locally, conceptually:

```powershell
$text=[System.IO.File]::ReadAllText($src)
$text=[regex]::Replace($text, 'DEFINER=`[^`]+`@`[^`]+`\s+', '')
[System.IO.File]::WriteAllText($dst,$text,$utf8NoBom)
```
## Chronological Commands and Outputs

### 1. SSH to VM1

Windows command:

```powershell
ssh rssc@112.121.157.74
```

Output:

```text
rssc@112.121.157.74's password:
Welcome to Ubuntu 24.04.3 LTS (GNU/Linux 6.17.0-14-generic x86_64)
Last login: Thu Jun 25 16:16:43 2026 from 118.174.138.142
rssc@rssc-webapp:~$
```

### 2. Check Docker stack

```bash
cd ~/web-server
sudo docker compose ps
```

Output:

```text
NAME                    IMAGE                      SERVICE         STATUS                  PORTS
id_booking_db           mariadb:11                 id-booking-db   Up 45 hours             3306/tcp
id_booking_phpmyadmin   phpmyadmin:latest          phpmyadmin      Up 45 hours             127.0.0.1:8081->80/tcp
metabase                metabase/metabase:latest   metabase        Up 6 weeks              0.0.0.0:3100->3000/tcp
nextjs_app              node:20-alpine             nextjs-app      Up 25 hours (healthy)
pgadmin4                dpage/pgadmin4             pgadmin         Up 3 months             0.0.0.0:5050->80/tcp
php_app                 web-server-php-app         php-app         Up 25 hours             80/tcp
reverse_proxy           nginx:alpine               reverse-proxy   Up 25 hours             0.0.0.0:80->80/tcp, 0.0.0.0:443->443/tcp
```

### 3. Open MariaDB and create DB/user

Root entry eventually worked with historical root password. Password redacted:

```bash
sudo docker compose exec id-booking-db mariadb -u root -p'[REDACTED_ROOT_PASSWORD]'
```

Output:

```text
Welcome to the MariaDB monitor.
Server version: 11.8.8-MariaDB-ubu2404 mariadb.org binary distribution
MariaDB [(none)]>
```

SQL:

```sql
CREATE DATABASE iservicedb
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
CREATE USER 'iservice_user'@'%' IDENTIFIED BY '[REDACTED]';
GRANT ALL PRIVILEGES ON iservicedb.* TO 'iservice_user'@'%';
FLUSH PRIVILEGES;
SHOW DATABASES LIKE 'iservicedb';
```

Output:

```text
Query OK, 1 row affected
Query OK, 0 rows affected
Query OK, 0 rows affected
Query OK, 0 rows affected
+-----------------------+
| Database (iservicedb) |
+-----------------------+
| iservicedb            |
+-----------------------+
```

### 4. Create VM1 project/data directories

```bash
mkdir -p ~/web-server/project-php/iservice/database_Export
ls -ld ~/web-server/project-php/iservice ~/web-server/project-php/iservice/database_Export
```

Output:

```text
drwxrwxr-x 3 rssc rssc 4096 Jun 25 16:29 /home/rssc/web-server/project-php/iservice
drwxrwxr-x 2 rssc rssc 4096 Jun 25 16:29 /home/rssc/web-server/project-php/iservice/database_Export
```

### 5. Upload SQL from Windows

Wrong attempt from inside VM1:

```bash
scp C:\xampp\htdocs\iservice\database_Export\rangsitadmin_iservice_db.vm1.sql rssc@112.121.157.74:/home/rssc/web-server/project-php/iservice/database_Export/
```

Output:

```text
ssh: Could not resolve hostname c: Temporary failure in name resolution
scp: Connection closed
```

Correct Windows PowerShell command:

```powershell
scp "C:\xampp\htdocs\iservice\database_Export\rangsitadmin_iservice_db.vm1.sql" rssc@112.121.157.74:/home/rssc/web-server/project-php/iservice/database_Export/
```

Output:

```text
rangsitadmin_iservice_db.vm1.sql 100% 4849KB 1.2MB/s 00:03
```

Verify on VM1:

```bash
ls -lh ~/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql
grep -n "DEFINER" ~/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql | head
```

Output:

```text
-rw-rw-r-- 1 rssc rssc 4.8M Jun 25 16:33 /home/rssc/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql
```

`grep` produced no output.
### 6. Import SQL

First import failed because MariaDB saw `iservice_user@localhost`:

```bash
sudo docker compose exec -T id-booking-db mariadb -u iservice_user -p iservicedb < ~/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql
```

Output:

```text
ERROR 1045 (28000): Access denied for user 'iservice_user'@'localhost' (using password: YES)
```

Root password discovery:

```bash
grep -n -A 30 -B 5 "id-booking-db" docker-compose.yml
```

Relevant output; passwords redacted here:

```text
DB_HOST: id-booking-db
DB_NAME: id_booking
DB_USER: id_booking_user
DB_PASS: [REDACTED]
MARIADB_DATABASE: id_booking
MARIADB_USER: id_booking_user
MARIADB_PASSWORD: [REDACTED]
MARIADB_ROOT_PASSWORD: [REDACTED]
```

The current yml root password was stale because the DB volume already existed; the historical root password worked. After entering root, added `iservice_user@localhost`. Then a mistaken placeholder password was pasted, so users were reset from `.env.gdcc` later.

Reset users from `.env.gdcc` without printing the password:

```bash
DBPASS=$(grep '^DB_PASS=' ~/web-server/project-php/iservice/.env.gdcc | cut -d= -f2-)
sudo docker compose exec -T id-booking-db mariadb -u root -p'[REDACTED_ROOT_PASSWORD]' <<SQL
DROP USER IF EXISTS 'iservice_user'@'%';
DROP USER IF EXISTS 'iservice_user'@'localhost';
CREATE USER 'iservice_user'@'%' IDENTIFIED BY '${DBPASS}';
CREATE USER 'iservice_user'@'localhost' IDENTIFIED BY '${DBPASS}';
GRANT ALL PRIVILEGES ON iservicedb.* TO 'iservice_user'@'%';
GRANT ALL PRIVILEGES ON iservicedb.* TO 'iservice_user'@'localhost';
FLUSH PRIVILEGES;
SQL
```

Successful import command:

```bash
sudo docker compose exec -T id-booking-db mariadb -u iservice_user -p'[REDACTED]' iservicedb < ~/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql
```

Output:

```text
(no output; import succeeded)
```

Verify count:

```bash
sudo docker compose exec id-booking-db mariadb -u iservice_user -p'[REDACTED]' iservicedb -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='iservicedb';"
```

Output:

```text
+----+
| 49 |
+----+
```

Verify key tables/views:

```bash
sudo docker compose exec id-booking-db mariadb -u iservice_user -p'[REDACTED]' iservicedb -e "SHOW TABLES LIKE 'users'; SHOW TABLES LIKE 'departments'; SHOW TABLES LIKE 'service_requests'; SHOW TABLES LIKE 'v_service_requests_full';"
```

Output:

```text
users
departments
service_requests
v_service_requests_full
```

Row counts:

```bash
sudo docker compose exec id-booking-db mariadb -u iservice_user -p'[REDACTED]' iservicedb -e "SELECT COUNT(*) AS users_count FROM users; SELECT COUNT(*) AS departments_count FROM departments; SELECT COUNT(*) AS requests_count FROM service_requests;"
```

Output:

```text
users_count       20
departments_count 171
requests_count    32
```

### 7. Upload project files

Uploaded by FileZilla SFTP:

```text
Protocol: SFTP
Host: 112.121.157.74
User: rssc
Port: 22
Local: C:\xampp\htdocs\iservice
Remote: /home/rssc/web-server/project-php/iservice
```

Important: upload contents into `.../project-php/iservice`, not nested `.../project-php/iservice/iservice`.

Verify:

```bash
ls -l ~/web-server/project-php/iservice/index.php ~/web-server/project-php/iservice/config/database.php ~/web-server/project-php/iservice/.htaccess
```

Output:

```text
-rw-rw-r-- 1 rssc rssc  4175 Jun 25 16:52 /home/rssc/web-server/project-php/iservice/.htaccess
-rw-rw-r-- 1 rssc rssc  9655 Jun 25 17:00 /home/rssc/web-server/project-php/iservice/config/database.php
-rw-rw-r-- 1 rssc rssc 72002 Jun 25 16:53 /home/rssc/web-server/project-php/iservice/index.php
```

### 8. Create `.env.gdcc`

```bash
nano ~/web-server/project-php/iservice/.env.gdcc
```

Content, secret redacted:

```dotenv
DB_HOST=id-booking-db
DB_NAME=iservicedb
DB_USER=iservice_user
DB_PASS=[REDACTED]
DB_CHARSET=utf8mb4
APP_ENV=production
APP_DEBUG=false
APP_URL=https://iservice.rangsitcity.go.th/
```

Permissions:

```bash
chmod 600 ~/web-server/project-php/iservice/.env.gdcc
ls -l ~/web-server/project-php/iservice/.env.gdcc
```

Output:

```text
-rw------- 1 rssc rssc 205 Jun 25 17:08 /home/rssc/web-server/project-php/iservice/.env.gdcc
```

### 9. DB config verification and override fix

Initial check showed `php-app` Docker env was still overriding DB name:

```bash
sudo docker compose exec php-app php -r "require '/var/www/html/iservice/config/database.php'; echo DB_HOST.' '.DB_NAME.' '.(IS_LOCAL ? 'local' : 'prod').PHP_EOL; echo table_exists('users') ? 'users-table-ok' : 'users-table-missing';"
```

Output:

```text
id-booking-db id_booking prod
users-table-ok
```

Patch VM1 config so `.env.gdcc` overrides existing env values:

```bash
cp ~/web-server/project-php/iservice/config/database.php ~/web-server/project-php/iservice/config/database.php.bak
perl -0pi -e "s/if \(\$key !== '' && getenv\(\$key\) === false\) \{/if (\$key !== '') {/" ~/web-server/project-php/iservice/config/database.php
sudo docker compose exec php-app php -l /var/www/html/iservice/config/database.php
```

Output:

```text
No syntax errors detected in /var/www/html/iservice/config/database.php
```

After user reset from `.env.gdcc`, successful DB check:

```text
id-booking-db iservicedb prod
users-table-ok
```
### 10. PHP extensions and rebuild

Before rebuild:

```bash
sudo docker compose exec php-app php -m | grep -E "mysqli|pdo_mysql|mbstring|gd|fileinfo|curl|zip|intl"
```

Output:

```text
curl
fileinfo
mbstring
mysqli
pdo_mysql
```

Original Dockerfile:

```Dockerfile
FROM php:8.2-apache

RUN docker-php-ext-install pdo_mysql mysqli

RUN a2enmod rewrite
```

Updated Dockerfile:

```bash
cat > ~/web-server/php/Dockerfile <<'EOF'
FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mysqli gd zip intl \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
EOF
```

Rebuild:

```bash
sudo docker compose up -d --build php-app
```

Output summary:

```text
Image web-server-php-app Built 80.3s
Container php_app Started 1.5s
```

After rebuild:

```bash
sudo docker compose exec php-app php -m | grep -E "mysqli|pdo_mysql|mbstring|gd|fileinfo|curl|zip|intl"
```

Output:

```text
curl
fileinfo
gd
intl
mbstring
mysqli
pdo_mysql
zip
```

DB still OK:

```text
id-booking-db iservicedb prod
users-table-ok
```

### 11. Nginx HTTP config and testing

Created initial config:

```bash
cat > ~/web-server/nginx/conf.d/iservice.conf <<'EOF'
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
EOF
```

Validate/reload:

```bash
sudo docker compose exec reverse-proxy nginx -t
sudo docker compose exec reverse-proxy nginx -s reload
```

Output:

```text
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
2026/06/25 10:18:42 [notice] 319#319: signal process started
```

Initial forced HTTP tests returned 301 because Apache `.htaccess` still forced HTTPS:

```bash
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/request-form.php
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/api/get_departments.php?level=1
```

Output:

```text
301
301
301
```

Disable Apache redirect on VM1:

```bash
cp ~/web-server/project-php/iservice/.htaccess ~/web-server/project-php/iservice/.htaccess.bak
perl -0pi -e 's/    # ── Force HTTP → HTTPS redirect.*?    # ────────────────────────────────────────────────────────────────────────\n/    # HTTPS redirect is handled by Nginx on VM1.\n/s' ~/web-server/project-php/iservice/.htaccess
```

Retest output:

```text
200
200
200
```

Security tests:

```bash
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/admin/admin_dashboard.php
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/config/database.php
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/.env.gdcc
```

Output:

```text
302
404
403
```

Add exact `.env` blocks and reload:

```bash
sed -i '/client_max_body_size 220m;/a\
\
    location = /.env.gdcc { return 404; }\
    location = /.env { return 404; }' ~/web-server/nginx/conf.d/iservice.conf
sudo docker compose exec reverse-proxy nginx -t
sudo docker compose exec reverse-proxy nginx -s reload
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/.env.gdcc
```

Output:

```text
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
2026/06/25 10:25:45 [notice] 359#359: signal process started
404
```

Add `www` to `server_name`:

```bash
grep -n "server_name" ~/web-server/nginx/conf.d/iservice.conf
sed -i 's/server_name iservice.rangsitcity.go.th;/server_name iservice.rangsitcity.go.th www.iservice.rangsitcity.go.th;/' ~/web-server/nginx/conf.d/iservice.conf
sudo docker compose exec reverse-proxy nginx -t
sudo docker compose exec reverse-proxy nginx -s reload
```

Output:

```text
3:    server_name iservice.rangsitcity.go.th;
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
2026/06/25 10:32:12 [notice] 379#379: signal process started
```

HTTP test:

```bash
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/
curl -s -o /dev/null -w "%{http_code}\n" http://www.iservice.rangsitcity.go.th/
```

Output:

```text
200
200
```

## Continuation Update - HTTPS Live, Browser Still Shows Not Secure

Updated after SSL and HTTPS troubleshooting on 2026-06-25.

### Completed After Previous Memory

DNS fully propagated for both root and www:

```bash
getent hosts iservice.rangsitcity.go.th
getent hosts www.iservice.rangsitcity.go.th
```

Output:

```text
112.121.157.74  iservice.rangsitcity.go.th
112.121.157.74  www.iservice.rangsitcity.go.th
```

Issued Let's Encrypt certificate:

```bash
sudo certbot certonly --webroot \
  -w /home/rssc/web-server/project-php/iservice \
  -d iservice.rangsitcity.go.th \
  -d www.iservice.rangsitcity.go.th
```

Output summary:

```text
Successfully received certificate.
Certificate is saved at: /etc/letsencrypt/live/iservice.rangsitcity.go.th/fullchain.pem
Key is saved at:         /etc/letsencrypt/live/iservice.rangsitcity.go.th/privkey.pem
This certificate expires on 2026-09-23.
```

Added HTTPS server block to:

```text
~/web-server/nginx/conf.d/iservice.conf
```

Then validated and reloaded:

```bash
sudo docker compose exec reverse-proxy nginx -t
sudo docker compose exec reverse-proxy nginx -s reload
```

Output:

```text
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
2026/06/25 13:53:33 [notice] 425#425: signal process started
```

Confirmed Nginx sees iService HTTPS block:

```bash
sudo docker compose exec reverse-proxy nginx -T | grep -n "server_name iservice\|listen 443\|ssl_certificate /etc/letsencrypt/live/iservice" -A 3 -B 3
```

Relevant output:

```text
408-server {
409:    listen 443 ssl;
410:    server_name iservice.rangsitcity.go.th www.iservice.rangsitcity.go.th;
412:    ssl_certificate /etc/letsencrypt/live/iservice.rangsitcity.go.th/fullchain.pem;
413-    ssl_certificate_key /etc/letsencrypt/live/iservice.rangsitcity.go.th/privkey.pem;
```

### HTTPS Verification Passed

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/request-form.php
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/api/get_departments.php?level=1
curl -s -o /dev/null -w "%{http_code}\n" https://www.iservice.rangsitcity.go.th/
```

Output:

```text
200
200
200
200
```

Sensitive checks:

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/config/database.php
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/.env.gdcc
```

Output:

```text
404
404
```

Certificate check:

```bash
curl -I https://iservice.rangsitcity.go.th/
echo | openssl s_client -servername iservice.rangsitcity.go.th -connect iservice.rangsitcity.go.th:443 2>/dev/null | openssl x509 -noout -subject -issuer -dates
```

Output summary:

```text
HTTP/1.1 200 OK
subject=CN = iservice.rangsitcity.go.th
issuer=C = US, O = Let's Encrypt, CN = YE2
notBefore=Jun 25 12:49:57 2026 GMT
notAfter=Sep 23 12:49:56 2026 GMT
```

Chrome certificate details also showed:

```text
Common Name (CN): iservice.rangsitcity.go.th
Issuer: Let's Encrypt YE2
Issued On: Thursday, June 25, 2026 at 7:49:57 PM
Expires On: Wednesday, September 23, 2026 at 7:49:56 PM
```

### Fixed Runtime DB Issue

After HTTPS went live, the page initially failed with:

```text
Fatal error: Uncaught mysqli_sql_exception: Table 'id_booking.nav_menu' doesn't exist in /var/www/html/iservice/includes/nav_menu_loader.php:30
```

CLI read the correct DB but web requests still used `id_booking`. Debug file `_dbcheck.php` showed:

```text
ENV_FILE=/var/www/html/iservice/.env.gdcc
ENV_EXISTS=yes
ENV_READABLE=no
DB_HOST=id-booking-db
DB_NAME=id_booking
SELECT_DATABASE=id_booking
```

Cause: `.env.gdcc` permission was `600` owned by `rssc`, so Apache/PHP in the container could not read it.

Fix:

```bash
chmod 644 ~/web-server/project-php/iservice/.env.gdcc
```

After fix:

```text
ENV_READABLE=yes
ENV_DB_HOST_LINE=DB_HOST=id-booking-db
ENV_DB_NAME_LINE=DB_NAME=iservicedb
ENV_DB_USER_LINE=DB_USER=iservice_user
ENV_DB_PASS_LINE=[REDACTED]
DB_HOST=id-booking-db
DB_NAME=iservicedb
SELECT_DATABASE=iservicedb
```

Removed debug file:

```bash
rm ~/web-server/project-php/iservice/_dbcheck.php
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/_dbcheck.php
```

Output:

```text
404
```

### HTTP Redirect Added

HTTP initially still returned 200:

```bash
curl -I http://iservice.rangsitcity.go.th/
```

Output before fix:

```text
HTTP/1.1 200 OK
```

Nginx HTTP block was replaced with redirect while preserving ACME challenge path:

```nginx
server {
    listen 80;
    server_name iservice.rangsitcity.go.th www.iservice.rangsitcity.go.th;

    location /.well-known/acme-challenge/ {
        proxy_pass http://php-app:80/iservice/.well-known/acme-challenge/;
        proxy_set_header Host $host;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}
```

Verification:

```bash
curl -I http://iservice.rangsitcity.go.th/
curl -I https://iservice.rangsitcity.go.th/
```

Output:

```text
HTTP/1.1 301 Moved Permanently
Location: https://iservice.rangsitcity.go.th/

HTTP/1.1 200 OK
```

### Mixed Content Cleanup Done

Initial scan found `http://iservice.rangsitcity.go.th` URLs from `nav_menu` DB. Query:

```bash
DBPASS=$(grep '^DB_PASS=' ~/web-server/project-php/iservice/.env.gdcc | cut -d= -f2-)
sudo docker compose exec id-booking-db mariadb -u iservice_user -p"$DBPASS" iservicedb -e "SELECT id, menu_name, menu_url FROM nav_menu WHERE menu_url LIKE 'http://iservice.rangsitcity.go.th%';"
```

Found 9 rows for service/resource links. Fixed:

```bash
sudo docker compose exec id-booking-db mariadb -u iservice_user -p"$DBPASS" iservicedb -e "UPDATE nav_menu SET menu_url = REPLACE(menu_url, 'http://iservice.rangsitcity.go.th', 'https://iservice.rangsitcity.go.th') WHERE menu_url LIKE 'http://iservice.rangsitcity.go.th%';"
```

Then found privacy banner generating `http://...` because it only checked `$_SERVER['HTTPS']`, not `X-Forwarded-Proto`.

File:

```text
/home/rssc/web-server/project-php/iservice/includes/privacy_consent.php
```

Backup:

```bash
cp ~/web-server/project-php/iservice/includes/privacy_consent.php ~/web-server/project-php/iservice/includes/privacy_consent.php.bak
```

Patched base URL detection to:

```php
$_is_https = (($_SERVER['HTTPS'] ?? '') === 'on') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$_base_url = ($_is_https ? 'https' : 'http')
               . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $_script_dir;
```

Then remaining `http://` occurrences were only SVG namespace strings inside data URIs:

```text
http://www.w3.org/2000/svg
```

For cleanliness, replaced these across files:

```bash
grep -RIl "http://www.w3.org/2000/svg" ~/web-server/project-php/iservice --exclude-dir=.git --exclude="*.sql" \
  | xargs sed -i 's#http://www.w3.org/2000/svg#https://www.w3.org/2000/svg#g'
```

Final scans:

```bash
curl -s https://iservice.rangsitcity.go.th/ | grep -n "http://"
curl -s https://iservice.rangsitcity.go.th/login.php | grep -n "http://"
curl -s -L https://iservice.rangsitcity.go.th/admin/admin_dashboard.php | grep -n "http://"
```

Output: no rows.

### Current Unresolved Issue

Chrome still shows `Not secure` / broken HTTPS for the user, even though server-side checks are valid.

Chrome DevTools Security tab says:

```text
This page is not secure (broken HTTPS).
Resources - active content with certificate errors
You have recently allowed content loaded with certificate errors (such as scripts or iframes) to run on this site.
Certificate - valid and trusted
Connection - secure connection settings
```

Chrome certificate panel shows the correct Let's Encrypt certificate for `iservice.rangsitcity.go.th`.

`chrome://settings/content/insecureContent` showed no sites added under allowed or blocked insecure content.

### What Still Needs Debugging Next

The next session should continue from here:

1. In Chrome DevTools Security tab, reload while the tab is open and inspect the left-side resource/origin list.
2. Identify which origin/resource is marked red with certificate errors.
3. In Chrome DevTools Network tab, enable Preserve log, reload, and look for failed/red requests or certificate-error resources.
4. If no resource appears, clear site data for `iservice.rangsitcity.go.th`, test in a fresh Chrome profile or another browser, and compare.
5. Server-side state currently appears correct: HTTPS cert valid, HTTP redirects to HTTPS, no `http://` strings in main/login/admin HTML output, and sensitive files return 404.

Do not consider the Chrome Not secure issue resolved until browser Security tab shows secure for a fresh reload/profile.
