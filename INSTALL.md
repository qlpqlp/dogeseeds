# DogeSeeds.org — Installation Guide

Deploy DogeSeeds.org to any PHP shared hosting (cPanel, Plesk, etc.) by uploading files to `public_html`.

**Live reference install:** [https://dogeseeds.org](https://dogeseeds.org)

## Requirements

| Requirement | Minimum |
|-------------|---------|
| PHP | 8.0+ |
| MySQL | 5.7+ or MariaDB 10.3+ |
| Extensions | PDO, pdo_mysql, json, session |
| Server | Apache with mod_rewrite (recommended) |

## Quick Install (5 steps)

### 1. Upload files

Upload **all files and folders** from this project into your server's `public_html` directory.

Your structure should look like:

```
public_html/
├── index.php
├── .htaccess
├── api/
├── assets/
├── config/
├── database/
├── includes/
├── install/
├── lang/
└── ...
```

You can drag and drop the entire folder contents via:
- **cPanel File Manager** → Upload or Extract ZIP
- **FTP/SFTP** (FileZilla, WinSCP)
- **Git** clone into public_html (if SSH access available)

### 2. Create a MySQL database

In cPanel → **MySQL Databases**:

1. Create a new database (e.g. `youruser_dogeseeds`)
2. Create a database user with a strong password
3. Add the user to the database with **ALL PRIVILEGES**

Note the host (usually `localhost`), database name, username, and password.

### 3. Set folder permissions

Ensure the `config/` folder is writable by the web server:

```
chmod 755 config/
```

In cPanel File Manager: right-click `config` → Permissions → `755`.

### 4. Run the install wizard

Open your browser and go to:

```
https://yourdomain.com/install/
```

Follow the 4-step wizard:

1. **Requirements** — checks PHP version and extensions
2. **Database** — enter MySQL credentials (schema is imported automatically)
3. **Site & Admin** — set site URL, admin account, DOGE wallet, default language
4. **Done** — installation complete

### 5. Secure your installation

After a successful install:

1. **Delete or rename** the `install/` folder
2. Optionally uncomment this line in `.htaccess` to block install access:
   ```
   RewriteRule ^install/ - [F,L]
   ```
3. Keep `config/config.php` private (`.htaccess` already blocks direct access)

---

## New installs vs migrations

**Fresh installation:** run the install wizard only. It imports `database/schema.sql` automatically. You do **not** need any `migrate-v*.sql` files.

**Optional sample data:** after install, import `database/seed-global-orgs.sql` in phpMyAdmin if you want worldwide sample NGOs, scouts, and volunteer hubs on the map. This is optional.

**Existing sites** that were installed before a schema update may need `database/migrate-v*.sql` files applied in order. New deployments never need those.

---

## Optional: Global sample data

To populate the map with sample organizations worldwide (NGOs, scouts, volunteer hubs), import after install:

phpMyAdmin → select your database → **Import** → `database/seed-global-orgs.sql`

Skip this if you want an empty map and will add real listings yourself.

---

## Configuration

After install, settings are stored in the database `settings` table:

| Key | Description |
|-----|-------------|
| `doge_wallet` | Dogecoin wallet address (DOGE only) |
| `default_language` | `en`, `pt`, `es`, `fr`, `de`, `zh`, or `ja` |
| `map_default_lat` | Default map latitude |
| `map_default_lng` | Default map longitude |
| `map_default_zoom` | Default zoom level |

Edit via phpMyAdmin or add an admin settings page later.

---

## Manual install (without wizard)

1. Copy `config/config.example.php` to `config/config.php` and fill in values
2. Import `database/schema.sql` into your MySQL database (no migration files needed)
3. Optionally import `database/seed-global-orgs.sql` for sample map data
4. Create an admin user manually in the `users` table
5. Set `site_installed` to `1` in the `settings` table

---

## Troubleshooting

### Blank page or 500 error
- Check PHP error log in cPanel
- Ensure PHP 8.0+ is selected (cPanel → MultiPHP Manager)
- Verify `config/config.php` exists and has correct DB credentials

### Install wizard won't connect to database
- Confirm database user has privileges on the database
- Try host `localhost` or `127.0.0.1`
- Some hosts use a prefix like `localhost:/tmp/mysql.sock`

### Map not loading
- Ensure your site is served over HTTPS (geolocation works better)
- Check browser console for API errors
- Verify `.htaccess` rewrite rules are active (Apache `mod_rewrite`)

### API returns 404
- Confirm `.htaccess` is uploaded (hidden files must be visible in FTP)
- On Nginx, add equivalent rewrite rules (see below)

### Nginx configuration

If not using Apache, add to your server block:

```nginx
location /api/ {
    rewrite ^/api/(.+)$ /api/index.php?route=$1 last;
}

location ~ ^/(config|database|includes)/ {
    deny all;
}
```

---

## User roles

| Role | Can do |
|------|--------|
| `user` | View map, reserve items |
| `business` | Register organization, post donations |
| `volunteer` | Register volunteer hub, post items |
| `ngo` | Register NGO, post donations |
| `admin` | Full access (set in database) |

---

## Adding languages

See **[README.md — Adding a new translation](README.md#adding-a-new-translation)** for the full guide.

Quick summary:

1. Copy `lang/en.json` to `lang/xx.json`
2. Translate all string values (keep keys and `%s` placeholders unchanged)
3. Register the language in `includes/languages.php`

Community contributions via GitHub are welcome.

---

## Support

**DogeSeeds.org** — Do Only Good Everyday.

Donations (DOGE only) support hosting and verified distribution. Not for personal profit.
