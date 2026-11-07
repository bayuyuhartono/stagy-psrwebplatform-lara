# Deployment — Shared Hosting (cPanel / Plesk)

Target: a standard shared-hosting account with cPanel or Plesk, no root access, no
persistent background processes. Docker is **not** required and is not used here.

> Verify PHP **8.3+** (8.5 recommended) and the **gd** extension before you start.
> `composer.json` declares `php: ^8.3`; the responsive image pipeline needs gd.
> In cPanel: *MultiPHP Manager* for the version, *Select PHP Version → Extensions* for gd.

---

## 1. Document root

Laravel must **not** be served from the project root — that would expose `.env`,
`storage/`, and `vendor/` to the public internet.

**Preferred:** upload the project outside the web root and point the domain's
document root at its `public/` directory.

```
/home/youruser/
├── app-psr/            ← project lives here
│   ├── app/
│   ├── public/         ← document root points HERE
│   └── .env
└── public_html/        ← unused, or symlinked to app-psr/public
```

In cPanel this is *Domains → Manage → Document Root*. In Plesk it is
*Hosting Settings → Document root*.

**If your host locks the document root to `public_html`**, symlink instead of moving files:

```bash
rm -rf ~/public_html
ln -s ~/app-psr/public ~/public_html
```

Editing `public/index.php` to point at a parent directory also works, but the symlink
survives upgrades without re-patching a framework file.

`public/.htaccess` already ships with the correct rewrite rules — no Apache config needed.

---

## 2. First deploy

```bash
cd ~/app-psr

# If the host has no SSH/composer, run this locally and upload vendor/ with the code.
composer install --no-dev --optimize-autoloader

cp .env.example .env
php artisan key:generate

# Media lives on the `public` disk. Without this symlink every uploaded image 404s.
php artisan storage:link

php artisan migrate --force
php artisan db:seed --force        # first deploy only — creates roles, permissions, settings

npm ci && npm run build            # or build locally and upload public/build/
```

### Required `.env` changes

| Key | Value | Why |
| --- | --- | --- |
| `APP_ENV` | `production` | enables the `uncompromised()` password check |
| `APP_DEBUG` | `false` | **critical** — `true` leaks stack traces and env values |
| `APP_URL` | `https://yourdomain.com` | used by `sitemap.xml`, `robots.txt`, canonicals, OG tags |
| `APP_NAME` | your site name | seeds `general.site_name`, shown in `og:title` / `og:site_name` |
| `MAIL_MAILER` | `smtp` | defaults to `log` — nothing sends until you change it |
| `DB_*` | host credentials | |

Then cache the config:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> Re-run those three after **every** `.env` change. A cached config ignores edits.

---

## 3. Cron jobs (both are required)

Shared hosting has no supervisor, so cron does the work. Add both in
*cPanel → Cron Jobs* (Plesk: *Scheduled Tasks*).

### Scheduler — every minute

```cron
* * * * * cd /home/youruser/app-psr && php artisan schedule:run >> /dev/null 2>&1
```

Currently drives the nightly audit-log prune (`config/audit.php`). Without it,
`audit_logs` grows without bound.

### Queue worker — every minute

```cron
* * * * * cd /home/youruser/app-psr && php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> /dev/null 2>&1
```

**This one is easy to miss and fails silently.** Contact form submissions are saved
immediately, but the notification and auto-reply emails are queued
(`SendContactMessageEmails`). With no worker they sit in the `jobs` table forever —
the visitor sees success and nobody is ever notified.

`--stop-when-empty` exits once the queue drains and `--max-time=55` guarantees the
process ends before the next minute's run, so the two never overlap.

### Why not `QUEUE_CONNECTION=sync`?

`sync` runs mail inline during the request. If SMTP is slow the visitor waits; if it
fails they get a 500 **after** their message was already saved, and the retry/backoff
behaviour the mail layer relies on is lost. The cron worker keeps delivery
asynchronous and retried.

---

## 4. Post-deploy checks

```bash
curl -sI https://yourdomain.com                     # 200
curl -s  https://yourdomain.com/robots.txt          # must show "Sitemap:" and "Disallow: /api/"
curl -s  https://yourdomain.com/sitemap.xml | head  # lists published content
```

- **`robots.txt` must NOT be a static file.** It is served by a route. If a static
  `public/robots.txt` ever reappears, the web server serves it instead and you
  silently lose the sitemap directive and the `/api/` block. This bug shipped once
  already; `PublicHeadOutputTest` asserts the file is absent.
- Log in at `/cms`, upload an image, confirm it renders (proves `storage:link`).
- Submit the contact form, then confirm the email arrives within ~60s (proves the queue cron).
- Settings → Integrations: enable analytics, then view source on the homepage and
  confirm the script is present.

---

## 5. Redeploying

```bash
cd ~/app-psr
php artisan down

git pull                                     # or upload changed files
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build

php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

`db:seed` is **not** part of a redeploy. It is idempotent (everything is keyed on a
natural key) but `RolePermissionSeeder` *syncs* role permissions — re-running it
discards any per-role permission changes made through the CMS. Run it deliberately
after adding new permissions, not as routine.

---

## 6. Known constraints on shared hosting

| Constraint | Impact |
| --- | --- |
| No persistent workers | Queue latency is up to ~60s (cron interval), not instant |
| `MEDIA_MAX_SIZE=50mb` | Must be ≤ the host's `upload_max_filesize` / `post_max_size` |
| Responsive variants use gd | Large uploads are memory-hungry; raise `memory_limit` or set `MEDIA_RESPONSIVE=false` |
| No Redis on most plans | `CACHE_STORE`/`SESSION_DRIVER`/`QUEUE_CONNECTION` stay on `database` — fine at this scale |
| Outbound HTTPS may be blocked | `uncompromised()` fails **open**, so password changes still work; reCAPTCHA verification fails **closed**, so the contact form would reject submissions. Verify outbound access before enabling reCAPTCHA |
