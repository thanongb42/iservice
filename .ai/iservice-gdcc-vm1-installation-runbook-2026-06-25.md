# iService GDCC VM1 Installation Runbook - 2026-06-25

> Security note: real passwords/secrets are redacted. Use `.env.gdcc`, compose history, or the official credential store for real values.

## Quick Reference

```text
VM1 IP: 112.121.157.74
SSH user: rssc
Docker root: /home/rssc/web-server
Project path: /home/rssc/web-server/project-php/iservice
DB service: id-booking-db
PHP service: php-app
Nginx service: reverse-proxy
DB name: iservicedb
DB user: iservice_user
Domain: iservice.rangsitcity.go.th, www.iservice.rangsitcity.go.th
```

## 1. SSH and Check Stack

```powershell
ssh rssc@112.121.157.74
```

```bash
cd ~/web-server
sudo docker compose ps
```

Expected key containers:

```text
id_booking_db
id_booking_phpmyadmin
php_app
reverse_proxy
```

## 2. Create Database and User

```bash
sudo docker compose exec id-booking-db mariadb -u root -p'[REDACTED_ROOT_PASSWORD]'
```

```sql
CREATE DATABASE iservicedb CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'iservice_user'@'%' IDENTIFIED BY '[REDACTED]';
CREATE USER 'iservice_user'@'localhost' IDENTIFIED BY '[REDACTED]';
GRANT ALL PRIVILEGES ON iservicedb.* TO 'iservice_user'@'%';
GRANT ALL PRIVILEGES ON iservicedb.* TO 'iservice_user'@'localhost';
FLUSH PRIVILEGES;
SHOW DATABASES LIKE 'iservicedb';
exit;
```

## 3. Upload and Import SQL

VM1:

```bash
mkdir -p ~/web-server/project-php/iservice/database_Export
```

Windows PowerShell:

```powershell
scp "C:\xampp\htdocs\iservice\database_Export\rangsitadmin_iservice_db.vm1.sql" rssc@112.121.157.74:/home/rssc/web-server/project-php/iservice/database_Export/
```

VM1 verify:

```bash
ls -lh ~/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql
grep -n "DEFINER" ~/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql | head
```

Import:

```bash
sudo docker compose exec -T id-booking-db mariadb -u iservice_user -p'[REDACTED]' iservicedb < ~/web-server/project-php/iservice/database_Export/rangsitadmin_iservice_db.vm1.sql
```

Verify:

```bash
sudo docker compose exec id-booking-db mariadb -u iservice_user -p'[REDACTED]' iservicedb -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='iservicedb';"
sudo docker compose exec id-booking-db mariadb -u iservice_user -p'[REDACTED]' iservicedb -e "SELECT COUNT(*) AS users_count FROM users; SELECT COUNT(*) AS departments_count FROM departments; SELECT COUNT(*) AS requests_count FROM service_requests;"
```

Observed:

```text
49 tables/views
20 users
171 departments
32 service_requests
```

## 4. Upload Project Files

Use FileZilla SFTP:

```text
Host: 112.121.157.74
User: rssc
Port: 22
Local: C:\xampp\htdocs\iservice
Remote: /home/rssc/web-server/project-php/iservice
```

Verify:

```bash
ls -l ~/web-server/project-php/iservice/index.php ~/web-server/project-php/iservice/config/database.php ~/web-server/project-php/iservice/.htaccess
```

## 5. Create `.env.gdcc`

```bash
nano ~/web-server/project-php/iservice/.env.gdcc
```

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

```bash
chmod 600 ~/web-server/project-php/iservice/.env.gdcc
ls -l ~/web-server/project-php/iservice/.env.gdcc
```

## 6. Patch DB Config If Docker Env Wins

```bash
cp ~/web-server/project-php/iservice/config/database.php ~/web-server/project-php/iservice/config/database.php.bak
perl -0pi -e "s/if \(\$key !== '' && getenv\(\$key\) === false\) \{/if (\$key !== '') {/" ~/web-server/project-php/iservice/config/database.php
sudo docker compose exec php-app php -l /var/www/html/iservice/config/database.php
```

Verify:

```bash
sudo docker compose exec php-app php -r "require '/var/www/html/iservice/config/database.php'; echo DB_HOST.' '.DB_NAME.' '.(IS_LOCAL ? 'local' : 'prod').PHP_EOL; echo table_exists('users') ? 'users-table-ok' : 'users-table-missing';"
```

Expected:

```text
id-booking-db iservicedb prod
users-table-ok
```

If DB access fails, reset DB user password from `.env.gdcc` without printing it:

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

## 7. Rebuild PHP With Required Extensions

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

```bash
sudo docker compose up -d --build php-app
sudo docker compose exec php-app php -m | grep -E "mysqli|pdo_mysql|mbstring|gd|fileinfo|curl|zip|intl"
```

Expected modules include `gd`, `zip`, `intl`.

## 8. Nginx HTTP Config

Create `~/web-server/nginx/conf.d/iservice.conf`:

```nginx
server {
    listen 80;
    server_name iservice.rangsitcity.go.th www.iservice.rangsitcity.go.th;

    client_max_body_size 220m;

    location = /.env.gdcc { return 404; }
    location = /.env { return 404; }

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
```

```bash
sudo docker compose exec reverse-proxy nginx -t
sudo docker compose exec reverse-proxy nginx -s reload
```

Disable Apache redirect on VM1 while staging/ACME is pending:

```bash
cp ~/web-server/project-php/iservice/.htaccess ~/web-server/project-php/iservice/.htaccess.bak
perl -0pi -e 's/    # ── Force HTTP → HTTPS redirect.*?    # ────────────────────────────────────────────────────────────────────────\n/    # HTTPS redirect is handled by Nginx on VM1.\n/s' ~/web-server/project-php/iservice/.htaccess
```

## 9. HTTP Tests Before SSL

```bash
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/request-form.php
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/api/get_departments.php?level=1
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/admin/admin_dashboard.php
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/config/database.php
curl --resolve iservice.rangsitcity.go.th:80:112.121.157.74 -s -o /dev/null -w "%{http_code}\n" http://iservice.rangsitcity.go.th/.env.gdcc
```

Observed:

```text
200
200
200
302
404
404
```

## 10. DNS and SSL

Wait until both resolve to VM1:

```bash
getent hosts iservice.rangsitcity.go.th
getent hosts www.iservice.rangsitcity.go.th
```

Expected:

```text
112.121.157.74 iservice.rangsitcity.go.th
112.121.157.74 www.iservice.rangsitcity.go.th
```

Then issue SSL:

```bash
sudo certbot certonly --webroot \
  -w /home/rssc/web-server/project-php/iservice \
  -d iservice.rangsitcity.go.th \
  -d www.iservice.rangsitcity.go.th
```

Add HTTPS server block with same proxy rules and:

```nginx
listen 443 ssl;
server_name iservice.rangsitcity.go.th www.iservice.rangsitcity.go.th;
ssl_certificate /etc/letsencrypt/live/iservice.rangsitcity.go.th/fullchain.pem;
ssl_certificate_key /etc/letsencrypt/live/iservice.rangsitcity.go.th/privkey.pem;
proxy_set_header X-Forwarded-Proto https;
```

Test/reload:

```bash
sudo docker compose exec reverse-proxy nginx -t
sudo docker compose exec reverse-proxy nginx -s reload
```

Verify HTTPS:

```bash
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/request-form.php
curl -s -o /dev/null -w "%{http_code}\n" https://iservice.rangsitcity.go.th/api/get_departments.php?level=1
curl -s -o /dev/null -w "%{http_code}\n" https://www.iservice.rangsitcity.go.th/
```

## 11. phpMyAdmin Tunnel

Windows:

```powershell
ssh -L 8081:127.0.0.1:8081 rssc@112.121.157.74
```

Browser:

```text
http://127.0.0.1:8081/
```

Do not close the tunnel while using phpMyAdmin.

## 12. Continuation: HTTPS Live But Chrome Still Shows Not Secure

This section records the current state after SSL completion.

Completed:

- DNS root and `www` point to `112.121.157.74`.
- Certbot succeeded for both names.
- HTTPS Nginx block added and reloaded.
- HTTPS checks return `200` for root, `www`, request form, and API.
- Sensitive files `/config/database.php` and `/.env.gdcc` return `404`.
- HTTP now redirects to HTTPS with `301`.
- Runtime DB was fixed by changing `.env.gdcc` permission from `600` to `644` so Apache/PHP can read it inside the container.
- `_dbcheck.php` was removed and returns `404`.
- Mixed content cleanup done:
  - DB `nav_menu` URLs changed from `http://iservice.rangsitcity.go.th` to `https://iservice.rangsitcity.go.th`.
  - `includes/privacy_consent.php` now respects `HTTP_X_FORWARDED_PROTO=https`.
  - `http://www.w3.org/2000/svg` in data URI SVG fallbacks changed to `https://www.w3.org/2000/svg`.
- Server-side scans of `/`, `/login.php`, and redirected `/admin/admin_dashboard.php` show no `http://` strings.

Still unresolved:

- Chrome still shows `Not secure` / broken HTTPS for the user.
- Chrome Security tab says active content with certificate errors was recently allowed to run.
- Certificate shown in Chrome is correct: `iservice.rangsitcity.go.th`, Let's Encrypt YE2, valid until 2026-09-23.
- `chrome://settings/content/insecureContent` showed no custom sites.

Next debugging steps:

1. Open Chrome DevTools > Security, reload, and inspect the resource/origin list on the left.
2. Find the red origin/resource with certificate errors.
3. Open DevTools > Network, enable Preserve log, reload, and inspect red/failed requests.
4. Test a fresh Chrome profile or a different browser to determine whether this is browser-profile state.
5. Do not mark Not secure as fixed until browser Security tab is clean after a fresh reload.
