# Deployment Guide — Nirav Hair Storm

This guide covers production deployment for **shared hosting (cPanel)** and a **Linux VPS (Apache + MySQL)**. It also covers subdirectory installs, first-run setup, hardening, and backups.

---

## 1. Prerequisites

- **PHP 8.1+** with `pdo_mysql` enabled
- **MySQL 5.7+** or MariaDB (typically already available on both platforms)
- A domain/subdomain (or an IP / subdirectory for testing)

---

## 2. Option A — Shared Hosting (cPanel / Hostinger / GoDaddy)

### 2.1 Create the database
1. cPanel → **MySQL Databases**.
2. Create database, e.g. `user_nirav`.
3. Create a user with a strong password, and **Add User to Database** granting **ALL PRIVILEGES**.
4. Open **phpMyAdmin**, select the new database, then **Import** → upload `database/schema.sql`, then `database/seed.sql`.
   - *Skip this step if you prefer the web installer (2.4) to do it for you.*

### 2.2 Upload files
1. Zip the project (excluding `.git`, `uploads/`, `public/uploads/` content).
2. Upload and **Extract** into:
   - **`public_html/`** — if the app is your main site, move everything **from `public/`** up into `public_html/`, so `index.php` sits directly in `public_html/`. The root `.htaccess` handles this automatically (see note below).
   - **`public_html/salon/`** — if installing in a subdirectory, extract the whole project there.

   > **Docroot note:** The project ships a root `.htaccess` that rewrites all traffic into `/public` and blocks direct access to `app/`, `config/`, `database/`, `routes/`, `vendor/`, `uploads/`. **You may keep `public/` as-is** and point the app at the project root — the rewrite handles it. Many cPanel setups can't change the document root, so this works out of the box. If you *can* set the document root, point it at `public/` instead (cleanest).

### 2.3 Configure credentials
Edit `config/database.php`:

```php
'host'     => 'localhost',           // cPanel MySQL host
'database' => 'user_nirav',
'username' => 'user_nirav',
'password' => 'YOUR_STRONG_PASSWORD',
```

### 2.4 Run the installer
1. Visit `https://your-domain.com/install.php` (or `https://your-domain.com/salon/install.php`).
2. Fill in the admin account details and click **Install Application**.
   - If you already imported the SQL manually, the installer will still succeed (it uses `CREATE TABLE IF NOT EXISTS` / `ON DUPLICATE KEY`).
3. **Delete `public/install.php` immediately.**
4. Sign in at `/auth/login`.

---

## 3. Option B — Linux VPS / Dedicated (Apache + MySQL)

Tested with Debian/Ubuntu (adjust package names for RHEL/Alma/Rocky).

### 3.1 Install packages
```bash
sudo apt update
sudo apt install -y apache2 php php-mysql php-mbstring php-curl php-zip unzip git
```

Verify the module:
```bash
php -m | grep -i pdo_mysql
```

### 3.2 Create the database and user
```bash
sudo mysql
```
```sql
CREATE DATABASE nirav_hairstorm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'nirav_app'@'localhost' IDENTIFIED BY 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON nirav_hairstorm.* TO 'nirav_app'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3.3 Deploy the code
```bash
sudo mkdir -p /var/www/salon
cd /var/www/salon
sudo git clone git@hs-codes:harshshah-codes/hair-salon-billing.git .
# or: unzip hair-salon-billing.zip

sudo chown -R www-data:www-data /var/www/salon
sudo find /var/www/salon -type d -exec chmod 755 {} \;
sudo find /var/www/salon -type f -exec chmod 644 {} \;
sudo chown -R www-data:www-data /var/www/salon/public/uploads /var/www/salon/uploads
```

### 3.4 Configure Apache virtual host
Create `/etc/apache2/sites-available/salon.conf`:

```apache
<VirtualHost *:80>
    ServerName salon.yourdomain.com
    DocumentRoot /var/www/salon/public

    <Directory /var/www/salon/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/salon_error.log
    CustomLog ${APACHE_LOG_DIR}/salon_access.log combined
</VirtualHost>
```

Enable and reload:
```bash
sudo a2enmod rewrite
sudo a2ensite salon.conf
sudo systemctl reload apache2
```

### 3.5 Run the installer
1. Visit `http://salon.yourdomain.com/install.php`
2. Create the admin account, then **delete `public/install.php`**.
3. Sign in at `/auth/login`.

---

## 4. HTTPS (TLS)

### VPS with Certbot
```bash
sudo apt install -y certbot python3-certbot-apache
sudo certbot --apache -d salon.yourdomain.com
```

### cPanel
AutoSSL usually enables HTTPS automatically. Otherwise use **Let's Encrypt** from the Security section.

> The app does not force HTTPS itself — terminate TLS at the web server.

---

## 5. Post-Install Hardening Checklist

- [ ] Delete `public/install.php`
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false` in the web-server environment (or `config/config.php`)
- [ ] Use a non-root database user with a strong password (`config/database.php`)
- [ ] Change the default admin password after first login
- [ ] Confirm `public/uploads` and `uploads/` are writable but **not** executable via PHP
- [ ] Confirm the root `.htaccess` (or vhost deny rules) blocks `app/`, `config/`, `database/`, `routes/`, `vendor/` from the web
- [ ] Keep the system (PHP/MySQL/Apache) updated
- [ ] Restrict `/settings/backup` access to admins only (route already requires `settings.edit`)

---

## 6. Backups

### 6.1 Automated MySQL dump (cron)
```bash
# /etc/cron.d/salon-backup
30 3 * * * root mysqldump --defaults-file=/etc/mysql/debian.cnf nirav_hairstorm | gzip > /var/backups/salon_$(date +\%F).sql.gz
```
Also copy `/var/www/salon/public/uploads` and `/var/www/salon/uploads` periodically.

### 6.2 Manual
- **In-app:** Settings → Backup downloads a full `.sql` dump.
- **CLI:**
  ```bash
  mysqldump -u nirav_app -p nirav_hairstorm > backup.sql
  ```

### 6.3 Restore
```bash
mysql -u nirav_app -p nirav_hairstorm < backup.sql
```

---

## 7. Upgrading

1. Back up database + uploads (see §6).
2. Pull new code (`git pull`) or upload the new archive.
3. Run `database/schema.sql` again against the DB (idempotent — `IF NOT EXISTS`).
4. If a new seed row is needed, run `database/seed.sql` (idempotent — `ON DUPLICATE KEY UPDATE`).
5. Clear any opcache: `sudo systemctl reload php-fpm` (or restart Apache/PHP-FPM).
6. Smoke-test sign-in, a billing flow, and invoice PDF.

---

## 8. Troubleshooting

| Problem | Likely fix |
|---------|-----------|
| `PDOException: could not find driver` | Install/enable `pdo_mysql` (PHP 8.5 on Arch: run PHP with `PHP_INI_SCAN_DIR` pointing at a dir containing `extension=pdo_mysql`, or add it to `/etc/php/conf.d`) |
| Blank 500 on login | Check `APP_DEBUG=true` temporarily, or inspect web-server logs (`/var/log/apache2/error.log`) |
| 404 on non-root routes | Ensure `mod_rewrite` is enabled and `.htaccess` is honored (`AllowOverride All`) |
| Installer "Could not read database files" | Confirm `database/schema.sql`/`seed.sql` exist and PHP can read them |
| Uploads not saving | `chown`/`chmod` `public/uploads` writable by the web-server user |
| Redirect loop | Confirm `APP_URL`/base path matches the install location (root vs subdirectory) |

---

## 9. Subdirectory Install (no subdomain)

If the app lives at `https://example.com/salon/` instead of a subdomain:

1. Extract the project into `public_html/salon/`.
2. Keep the root `.htaccess` (it rewrites `/salon/...` into `/salon/public/...` and blocks internal dirs).
3. Set `APP_URL` accordingly in `config/config.php` (used by `url()` for links/redirects).
4. Run installer at `https://example.com/salon/install.php`.

---

*Last updated for v1.0.0.*
