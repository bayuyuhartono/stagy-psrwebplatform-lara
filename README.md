# Pesoros Web Platform

A headless, API-first **company-profile CMS and public website platform** built on
Laravel 13 / PHP 8.5.

The CMS admin and the public website are both clients of the same versioned HTTP API.
V1 renders the public site with Blade themes, but every page consumes the same
endpoints a future Next.js frontend would — nothing reads Eloquent models across that
boundary.

| | |
| --- | --- |
| **Stack** | Laravel 13 · PHP 8.5 · MySQL · Tailwind CSS v4 · Vite · vanilla JS |
| **API** | 13 public + 94 admin endpoints under `/api/v1` |
| **Tests** | 258 passing (905 assertions), Pest 5 |
| **Auth** | Sanctum tokens (API) · session (CMS) |

**Docs:** [Implementation status](docs/IMPLEMENTATION_PLAN.md) · [Deployment](docs/DEPLOYMENT.md) · [PRD](docs/PRD.md)

---

## Quick start

```bash
composer setup                # install, .env, key, migrate, npm install, build
php artisan storage:link      # required — media lives on the `public` disk
php artisan db:seed           # roles, permissions, settings, pages, menus, demo content
composer run dev              # server + queue listener + log tail + vite, concurrently
```

> `composer setup` does **not** run `storage:link` or `db:seed`. Skip the symlink and
> every uploaded image 404s; skip the seed and there are no roles, permissions, or
> website settings.

Seeded super admin: **`admin@example.com` / `password`** — change it before any
deployment. The CMS lives at `/cms`.

---

## Commands

```bash
composer run dev                          # full dev environment
composer test                             # clears config cache, runs the suite
php artisan test --compact
php artisan test --compact --filter=aTestName

vendor/bin/pint --dirty --format agent    # required after touching any PHP file
npm run build                             # or `npm run dev` for the Vite watcher
php artisan pail                          # tail application logs
```

---

## Architecture

Code is grouped **by feature**, not by technical type. Each feature under
`app/Features/` owns its Actions, Services, Repositories, DTOs, and Enums.

```
HTTP → Controller → Action → Service → Repository → Model
```

Controllers stay thin: validate via a Form Request, delegate to an Action, return an
API Resource. Business rules live in Actions and Services; query construction lives in
Repositories. Enforced by `arch()` rules in [tests/Feature/ArchitectureTest.php](tests/Feature/ArchitectureTest.php).

**Features:** Audit · ContactMessages · ContentBlocks · Dashboard · EmailSettings ·
Integrations · Media · Menus · Pages · Portfolios · Posts · Roles · Search · Seo ·
Services · Settings · System · Taxonomies · Themes · Users · WebsiteSettings

### Settled data-model decisions

- **UUIDs** as identifiers throughout
- **Hybrid JSON block storage** — page content is a block engine; blocks are JSON on the owning record, not one table per block type
- **Polymorphic SEO and media** — one `seos` table and one `media` table serve every content type
- **Generic Post module** — Blog and News are one `Post` discriminated by `type`, not separate entities
- **Soft deletes** throughout, backed by an audit log with restore history

---

## Key concepts

### Theme engine

Themes are self-contained packages under `resources/themes/{slug}/`, each with a
`theme.json` manifest and a `views/` tree. `ThemeManager` prepends theme view paths to
Blade's finder at boot, giving a lookup chain of:

```
active theme → fallback theme → resources/views
```

A theme therefore only ships the views it wants to change. Settings are declared in the
manifest, stored in the `theme` website-settings group, and edited from a schema-driven
panel at **Settings → Theme** — the form fields change with the theme.

Switch themes with `THEME=your-theme` in `.env`.

### Content blocks

Block types are declared in [config/content_blocks.php](config/content_blocks.php) with
their field schema and validation rules, exposed to the editor via
`GET /api/v1/admin/content-blocks/schema`, and rendered by the theme's
`components/public/block.blade.php`.

Currently: `rich_text`, `hero`, `image`, `call_to_action`, `feature_grid`.

### Feature flags

Every CMS module is individually enable/disable-able. Routes are gated with the
`feature:` middleware — a disabled module 404s rather than erroring. This is also the
groundwork for later multi-tenancy. Gate new modules from the start.

### Roles and permissions

Five default roles, seeded by [RolePermissionSeeder](database/seeders/RolePermissionSeeder.php),
which is the single source of truth and **syncs** — removing a grant there revokes it on
the next seed.

| Role | Scope |
| --- | --- |
| `super-admin` | Everything |
| `admin` | All but feature flags, system settings, and deleting roles |
| `editor` | All content, media, taxonomies, menus, SEO; no users or configuration |
| `author` | Write posts and upload media; **own posts only** |
| `sales-support` | Contact inbox; read-only on content |

Always authorize through Policies — never role-name string checks. `posts.update` is
author-scoped; the separate `posts.update.any` grant lifts it to all posts.

---

## Testing

Pest 5. `phpunit.xml` forces sqlite `:memory:`, `array` cache/session/mail, and `sync`
queue, so anything relying on real queue or mail behaviour needs explicit test doubles.

`tests/Pest.php` leaves `RefreshDatabase` commented out — a test that touches the
database must `uses(RefreshDatabase::class)` (or `LazilyRefreshDatabase`) itself.

`memory_limit` is raised to 512M because the architecture tests hold a parsed AST per
application file.

---

## Deployment

Target is **shared hosting (cPanel / Plesk)** — see [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).
Docker and Nginx are not used.

Two cron jobs are mandatory, and both fail **silently** if missed:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty --max-time=55 --tries=3 >> /dev/null 2>&1
```

Without the queue worker, contact-form submissions save and the visitor sees success,
but **the notification email is never sent**.

---

## Conventions and traps

- **`CLAUDE.md` and `AGENTS.md` are byte-identical and partly generated.** Laravel Boost
  owns the `<laravel-boost-guidelines>` block and rewrites it on `boost:update`. Edit
  only outside that block, and mirror changes into both files.
- **Committed rules live in [.ai/rules/](.ai/rules/)** — `index.md` maps file globs to
  rule files. Read the matching ones before editing.
- **Never add a static `public/robots.txt`.** It shadows the `seo.robots` route on every
  real web server, silently dropping the sitemap directive and the `/api/` block. The
  test suite asserts the file is absent.
- Tailwind v4 is CSS-first via `@tailwindcss/vite` — no `tailwind.config.js`; tokens live
  in `resources/css/theme.css`. The CMS (`cms-*`) and public site (`public-*`) use
  separate palettes; do not mix them.
- There is **no Alpine.js**. Front-end behaviour is vanilla JS in `resources/js/app.js`
  using the shared `apiRequest` helper.
- `composer.json` declares `php: ^8.3` while the runtime is 8.5 — don't use 8.4+ syntax
  without raising the constraint.
# stagy-psrwebplatform-lara
# stagy-psrwebplatform-lara
# stagy-psrwebplatform-lara
