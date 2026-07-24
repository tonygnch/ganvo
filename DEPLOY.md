# Production deploy — ganvo.bg

First-time setup of Ganvo on your Contabo server.
Re-deploys after the first one use [`deploy/deploy.sh`](deploy/deploy.sh).

**Target setup:**
- Domain: `ganvo.bg` (DNS already pointing at the server)
- App path: `/var/www/ganvo`
- Web server: Caddy (auto HTTPS via Let's Encrypt)
- PHP: **8.4+** (composer.lock pins Symfony 8.x which requires 8.4)
- Database: MySQL 8

---

## 0. Prerequisites — verify on the server

SSH in and check each:

```bash
caddy version           # any v2.x
php --version           # 8.4 or newer (NOT 8.3 — see below)
composer --version
mysql --version         # 8.0+
node --version          # 20 LTS or newer
npm --version
git --version
```

Plus PHP extensions Laravel needs:

```bash
php -m | grep -iE 'pdo_mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo|curl|intl|zip|gd'
```

If your distro ships an older PHP (e.g. Debian 12 has 8.2, Ubuntu 24.04 has 8.3), install 8.4 from the third-party repo:

```bash
# Debian (sury.org repo):
sudo apt install -y apt-transport-https lsb-release ca-certificates curl
sudo curl -sSLo /usr/share/keyrings/deb.sury.org-php.gpg https://packages.sury.org/php/apt.gpg
echo "deb [signed-by=/usr/share/keyrings/deb.sury.org-php.gpg] https://packages.sury.org/php/ $(lsb_release -sc) main" | sudo tee /etc/apt/sources.list.d/php.list
sudo apt update

# Ubuntu (ondrej PPA):
# sudo add-apt-repository -y ppa:ondrej/php && sudo apt update

sudo apt install -y \
    php8.4-fpm php8.4-mysql php8.4-mbstring php8.4-xml php8.4-curl \
    php8.4-bcmath php8.4-intl php8.4-zip php8.4-gd php8.4-tokenizer
sudo update-alternatives --set php /usr/bin/php8.4
```

PHP-FPM must be running:

```bash
sudo systemctl enable --now php8.4-fpm
ls /run/php/                        # confirms the socket: php8.4-fpm.sock
```

---

## 1. Create the MySQL database + user

```bash
sudo mysql
```

Inside the mysql shell:

```sql
CREATE DATABASE ganvo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'ganvo'@'localhost' IDENTIFIED BY 'CHANGE_ME_TO_A_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON ganvo.* TO 'ganvo'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

Generate a strong password ahead of time:

```bash
openssl rand -hex 24
```

Save that password — you'll paste it into `.env` in step 4.

---

## 2. Clone the repo

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/tonygnch/ganvo.git
sudo chown -R "$USER":www-data ganvo
cd ganvo
```

> If you'd rather not own the directory yourself, use a dedicated deploy
> user (e.g. `sudo adduser deploy`, then SSH as that user for everything
> below). Don't run anything in /var/www as root.

---

## 3. Install dependencies

```bash
composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction
npm ci --no-audit --no-fund        # use `npm install` if package-lock.json is missing
npm run build                       # builds public/build/* via Vite
```

---

## 4. Configure `.env`

```bash
cp .env.production.example .env
nano .env                           # or vim, whatever
```

Fill in:

- `APP_KEY` — leave blank for now, the next command sets it
- `DB_PASSWORD` — the password you generated in step 1
- `COMING_SOON_BYPASS_TOKEN` — generate with `php -r "echo bin2hex(random_bytes(16));"`

Generate the app key:

```bash
php artisan key:generate
```

(This writes `APP_KEY=base64:...` into your `.env`.)

---

## 5. Set file permissions

Caddy + PHP-FPM run as `www-data`. They need write access to two paths:

```bash
sudo chown -R "$USER":www-data /var/www/ganvo
sudo find /var/www/ganvo -type d -exec chmod 755 {} \;
sudo find /var/www/ganvo -type f -exec chmod 644 {} \;
sudo chmod -R 775 storage bootstrap/cache
sudo chown -R "$USER":www-data storage bootstrap/cache
```

The `775` + group-write means both your SSH user AND `www-data` can write to logs, sessions, and the view cache.

---

## 6. Run migrations + seed

```bash
php artisan migrate --force                                   # --force is required outside `local` env
php artisan db:seed --force                                   # seeds plans, roles, the demo super admin
php artisan storage:link                                      # public/storage → storage/app/public
```

The seeder creates `super@ganvo.test / password` — **change this immediately** after first login by logging into `/super` and editing the user via System → Admins.

---

## 7. Cache config / routes / views for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

(Skipping these works but every request reparses every blade + route file. Always cache in prod.)

---

## 8. Wire up Caddy

Copy the site block into your Caddyfile:

```bash
sudo cp /var/www/ganvo/deploy/Caddyfile.ganvo.bg /etc/caddy/Caddyfile.ganvo.bg
```

In `/etc/caddy/Caddyfile`, add at the bottom:

```caddy
import /etc/caddy/Caddyfile.ganvo.bg
```

Validate + reload:

```bash
sudo caddy validate --config /etc/caddy/Caddyfile
sudo systemctl reload caddy
```

Caddy will provision the Let's Encrypt certificate automatically on the first request to `https://ganvo.bg`.

Watch the cert provisioning + first requests:

```bash
sudo journalctl -u caddy -f
```

---

## 9. Smoke test

```bash
# Coming-soon splash
curl -I https://ganvo.bg                                       # → 200 + Cache-Control: private, max-age=300

# Superadmin login screen
curl -I https://ganvo.bg/super                                  # → 302 to /super/login

# Static asset (Vite hash)
ls public/build/                                                # → should list manifest.json + assets/*
curl -I https://ganvo.bg/build/assets/$(ls public/build/assets/ | head -1)
# → 200 + Cache-Control: public, max-age=31536000, immutable
```

Open `https://ganvo.bg` in a browser — should show the **coming-soon splash**.
Open `https://ganvo.bg/super/login` — log in as `super@ganvo.test / password`,
then immediately change that account's email + password via **System → Admins**.

---

## 10. Verify the bypass + flip when you're ready

While `COMING_SOON_ENABLED=true`, the real marketing home is reachable via:

```
https://ganvo.bg/?preview=<the token from step 4>
```

When you're ready to expose the real homepage:

```bash
nano /var/www/ganvo/.env
# COMING_SOON_ENABLED=false
php artisan config:cache
```

(No restart needed — `config:cache` reflects the change immediately.)

---

## Re-deploying (every subsequent push)

Once the first deploy is done, every future deploy is one command:

```bash
cd /var/www/ganvo
bash deploy/deploy.sh
```

Which does: fetch latest master → composer install → npm build → migrate → cache rebuild — with the app **live the whole time** (zero-downtime; no maintenance mode). Idempotent. Safe to re-run.

---

## Tenant storefronts on subdomains (`slug.ganvo.bg`)

Current state: the zone is proxied through Cloudflare and a **wildcard DNS
record already exists** — any `slug.ganvo.bg` resolves to Cloudflare's edge,
and Cloudflare's Universal SSL gives browsers valid TLS for it. What's missing
until you do the one-time setup below is the **origin side**: without a
`*.ganvo.bg` vhost, Cloudflare's connection to the server fails and visitors
see **error 525** (SSL handshake failed).

One-time setup (~10 minutes):

1. **Create the Origin CA certificate** — Cloudflare dashboard → your
   `ganvo.bg` zone → **SSL/TLS → Origin Server → Create Certificate**. Keep
   the defaults (RSA, 15 years); hostnames must include `ganvo.bg` and
   `*.ganvo.bg` (they do by default). You get two PEM blobs: the certificate
   and the private key. **Copy the key now — it is shown only once.**
2. **Install it on the server:**

   ```bash
   sudo mkdir -p /etc/caddy/certs
   sudo nano /etc/caddy/certs/ganvo-bg-origin.pem   # paste the certificate
   sudo nano /etc/caddy/certs/ganvo-bg-origin.key   # paste the private key
   sudo chmod 600 /etc/caddy/certs/ganvo-bg-origin.key
   sudo chown caddy:caddy /etc/caddy/certs/ganvo-bg-origin.*
   ```

3. **Pull latest master** (brings the `*.ganvo.bg` block in
   `deploy/Caddyfile.ganvo.bg`) and reload Caddy:

   ```bash
   cd /var/www/ganvo && git pull
   sudo systemctl reload caddy
   ```

   If you pasted the site blocks into `/etc/caddy/Caddyfile` instead of using
   `import /var/www/ganvo/deploy/Caddyfile.ganvo.bg`, copy the new
   `*.ganvo.bg` block over manually before reloading.

4. **Check the Cloudflare SSL mode** — SSL/TLS → Overview should be **Full
   (strict)**. The Origin CA cert satisfies strict mode; if the zone is on
   "Flexible", switch it to Full (strict) or the app will see http and
   generate mixed-scheme URLs.

Verify: `curl -I https://<a-live-store-slug>.ganvo.bg` → 200 (an unknown slug
correctly 404s — that's the app refusing non-existent stores, not a config
problem). Also confirm `SESSION_DOMAIN=null` in the server's `.env` (host-only
cookies — one store's customer login must not leak into another store).

**Custom domains** (merchant's own domain) still need manual ops per domain
today: the merchant points DNS at this server, and you add a site block for
that hostname to the Caddyfile and reload. Automating this with Caddy
on-demand TLS is a separate follow-up.

---

## Troubleshooting

**500 error with no detail in the browser:**
`APP_DEBUG=false` in prod suppresses stack traces. Check the log:

```bash
tail -50 /var/www/ganvo/storage/logs/laravel.log
```

**Caddy keeps trying for a cert but failing:**
- Confirm DNS A record points at the server: `dig ganvo.bg`
- Confirm port 80 + 443 are open in the firewall: `sudo ufw status`
- Watch `journalctl -u caddy -f` for the actual ACME error.

**"target class does not exist" or other class-not-found:**
Composer autoload is stale. Re-run:

```bash
composer dump-autoload --optimize
php artisan config:clear
```

**Storage permission errors:**
Re-apply step 5 — `storage` and `bootstrap/cache` need group-write for `www-data`.

**MySQL connection refused:**
- `sudo systemctl status mysql`
- `mysql -u ganvo -p ganvo` — verify creds work from the shell.
- Check `bind-address` in `/etc/mysql/mysql.conf.d/mysqld.cnf` is `127.0.0.1` (default).
