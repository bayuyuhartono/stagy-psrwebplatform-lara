# Implementation Plan & Status

Status of the codebase against [PRD.md](PRD.md), audited **2026-08-09** on branch `feat/brandpallette`.

Legend: `[x]` done · `[~]` partial · `[ ]` not started

**Current:** 152 routes · 22 features under `app/Features` · 27 migrations · **258 tests passing (905 assertions)**.
*Baseline at first audit: 143 routes · 191 tests · 705 assertions.*

> **Caveat:** `docs/PRD.md` is still a headings-only outline. Every item below is derived from a PRD heading plus what the code actually does — not from written acceptance criteria. Items marked ⚠️ need the user to define "done" before they can be built or verified.

---

## 1. Foundation & Architecture (PRD §11, §12, §23, §27)

- [x] Feature-first layering — `HTTP → Controller → Action → Service → Repository → Model` across all 20 features
- [x] `routes/api.php` registered; versioned under `api/v1`
- [x] Public vs Admin API separated — different route groups, auth, resources
- [x] UUID primary keys throughout
- [x] Soft deletes throughout, with restore endpoints
- [x] Polymorphic SEO (`seos` table, `nullableUuidMorphs`)
- [x] Polymorphic media (`media` table, `nullableUuidMorphs('mediable')`)
- [x] Generic `Post` module — `PostType::Blog` / `PostType::News`, one model
- [x] Hybrid JSON block storage — `pages.blocks` JSON column
- [x] Eloquent API Resources for every response
- [x] Form Requests for validation; Policies for authorization
- [x] DTOs, Observers, Jobs, Events in place
- [x] Rate limiting — `public-api` 60/min, `admin-api` 120/min, `login` 5/min, `contact-form` 5/min
- [x] JSON exception rendering for `api/*`
- [ ] **ADR documents written up** — decisions are implemented in code but not recorded in the PRD

---

## 2. CMS Modules (PRD §5, §9)

All modules have backend (Action/Service/Repository/Policy), admin API, and a JS-driven Blade admin UI.

| Module | Backend + API | Admin UI | Notes |
| --- | --- | --- | --- |
| Dashboard | `[x]` | `[x]` | widgets, system health, storage, recent activity |
| Pages | `[x]` | `[x]` | index + create/edit block editor |
| Services | `[x]` | `[x]` | index + create/edit |
| Portfolio | `[x]` | `[x]` | index + create/edit |
| Posts | `[x]` | `[x]` | index + create/edit, blog/news discriminator |
| Menus | `[x]` | `[x]` | menu + nested menu-item builder |
| Media Library | `[~]` | `[x]` | upload/browse/pick + responsive variants; usage tracking pending → §6 |
| Contact Messages | `[x]` | `[x]` | list, status update, restore |
| SEO | `[x]` | `[x]` | editor + full public head output → §5 |
| Integrations | `[x]` | `[x]` | all 8 providers, injected into public site → §7 |
| Website Settings | `[x]` | `[x]` | groups: general, branding, contact, social, seo, experience, theme |
| Users & Roles | `[x]` | `[x]` | full permission matrix seeded → §8 |
| Audit Logs | `[x]` | `[x]` | read-only list; 365-day retention pruned nightly → §8 |
| System Settings | `[x]` | `[x]` | health + settings endpoints |
| Global Search | `[x]` | `[x]` | `api/v1/admin/search` |
| Feature Flags | `[x]` | `[x]` | 14 flags seeded, `feature:` middleware enforced |

---

## 3. Theme Engine (PRD §7) — ✅ BUILT

- [x] Theme architecture — self-contained packages under `resources/themes/{slug}/`, each with a `theme.json` manifest and a `views/` tree
- [x] Theme configuration — `config/themes.php` (active, fallback, path, manifest cache)
- [x] `ThemeManifest` DTO validates manifests and rejects any missing `name`/`version`
- [x] Runtime resolution — `ThemeManager::boot()` prepends theme view paths to Blade's finder, producing an **active theme → fallback theme → `resources/views`** lookup chain. A theme only ships the views it wants to change
- [x] Public Blade views moved into `resources/themes/default/views/` via `git mv` (history preserved). `resources/views/` now holds only CMS and mail views
- [x] Theme switching via the `THEME` env var, with fallback to the default theme
- [x] User-editable theme settings — declared in the manifest, stored in the `theme` website-settings group, merged over manifest defaults, and surfaced in a schema-driven CMS panel at Settings → Theme. Validation rules are derived from the manifest at request time
- [x] `GET api/v1/admin/themes` exposes the manifest, its settings schema, and current values
- [ ] Future Next.js implementation ⚠️ *(V2+, out of MVP scope — confirm)*
- [ ] Theme settings share a single `theme` group, so switching themes does not preserve per-theme values. Acceptable for V1; revisit if theme switching becomes routine

---

## 4. Content Block Engine (PRD §6)

- [x] Block architecture — JSON on owning record, registry-driven
- [x] `ContentBlockRegistry` + `config/content_blocks.php` schema/validation
- [x] `GET api/v1/admin/content-blocks/schema` exposes the registry
- [x] Content builder UI in the page editor
- [x] Renderer — `components/public/block.blade.php`
- [~] **Supported block types — only 5 built:** `rich_text`, `hero`, `image`, `call_to_action`, `feature_grid`
  - [ ] ⚠️ Full block-type list needs defining (gallery, testimonial, FAQ, pricing, team, stats, video, embed?)
- [ ] **Reusable / global block strategy** — no shared-block model exists
- [ ] **Block lifecycle** — no revisions, versioning, or draft-preview of block state

---

## 5. SEO (PRD §13)

- [x] SEO metadata storage — polymorphic `seos` table
- [x] SEO editor UI with live score
- [x] `SeoScoreCalculator`
- [x] Focus keyword field
- [x] `sitemap.xml` — `SitemapService` + `SitemapCacheObserver` invalidation
- [x] `robots.txt` — `RobotsService`
- [x] Canonical URLs — rendered in public layout
- [x] Open Graph title/description/type/url — rendered
- [x] Twitter card type — rendered
- [x] **`og:image` + `twitter:image` + `og:image:alt`** — resolved from page SEO, falling back to the site-wide `seo.social_image_id`
- [x] **Structured data** — emitted as `application/ld+json`, hardened with `JSON_HEX_TAG` against `</script>` breakout
- [x] `og:site_name`
- [ ] Per-type JSON-LD defaults (Organization, Article, Service, BreadcrumbList) ⚠️ *needs spec — today the field is free-form per record*

---

## 6. Media Management (PRD §14)

- [x] Upload — `UploadMediaAction` / `MediaService`
- [x] Duplicate detection — unique `(disk, checksum)` index, SHA-256
- [x] Folder management — `folder` column, indexed
- [x] Storage providers — disk-driven, multi-disk capable
- [x] Alt text, caption, metadata
- [x] Soft delete + restore
- [x] Image metadata extracted (dimensions)
- [x] **Responsive images** — `ResponsiveImageGenerator` derives 320/640/1024/1600 variants on upload via GD (no new dependency; Imagick also available). Never upscales, skips GIF to preserve animation, and logs-and-continues on failure so an upload never fails over a derivative
- [x] `Media::$srcset` accessor emits a full `srcset`; the `image` block renders it with `sizes`, intrinsic `width`/`height`, `loading="lazy"` and `decoding="async"`
- [x] Re-encode on variant generation (quality 82) — configurable in `config/media.responsive`
- [~] **Video support** — `mp4`/`webm` upload and store fine; missing poster-frame extraction, duration metadata, and transcoding
- [ ] **Optimization of the original** — the source file is stored untouched; only derivatives are re-encoded
- [ ] **Usage tracking** — cannot answer "what content uses this asset?"
- [ ] Variants are not purged when media is force-deleted (the existing delete path leaves originals on disk too — consistent, but worth fixing together)

---

## 7. Integrations (PRD §15)

All 8 PRD providers configured: `google-analytics`, `google-search-console`, `google-tag-manager`, `meta-pixel`, `microsoft-clarity`, `google-maps`, `recaptcha`, `custom-scripts`.

- [x] Integration model, admin CRUD, public read endpoint
- [x] Enable/disable per provider, public vs private config split
- [x] **Google Search Console** — renders the `google-site-verification` meta tag
- [x] **Custom scripts** — raw `head_html` / `body_html`, capped at 20k chars each
- [x] **Scripts injected into the public site** — `PublicIntegrationResolver` feeds a view composer bound to `components.public.layout`; head and body partials render GA4, GTM (incl. `<noscript>` iframe), Meta Pixel (incl. `<noscript>` pixel), and Clarity. Public config only — secrets stay in the encrypted `secret_config`, asserted by test
- [x] **reCAPTCHA enforced on the contact form** — `RecaptchaVerifier` + `ValidRecaptcha` rule. The token is only required while the provider is enabled, so unconfigured installs keep a working form. Verification **fails closed** when Google is unreachable or no secret is configured. The secret is read from the encrypted `secret_config` and never rendered; the token is stripped before persistence
- [ ] Google Maps is deliberately **not** blanket-injected — it belongs to whichever component renders a map, and loading the Maps JS API site-wide is billable

---

## 8. Security, Roles & Auditing (PRD §17, §18, §19)

- [x] Authentication — Sanctum tokens (API) + session (CMS)
- [x] Authorization via Policies, never role-name checks
- [x] CSRF, XSS escaping, SQL-injection safety (Eloquent/parameter binding)
- [x] Rate limiting on login, contact form, and both API surfaces
- [x] File validation on upload (mime, size)
- [x] Audit log with restore history
- [x] 5 default roles created — `super-admin`, `admin`, `editor`, `author`, `sales-support`
- [x] **Granular permission matrix** — `RolePermissionSeeder` is the single source of truth and **syncs** (so removing a grant here revokes it on the next run). Allocation: `super-admin` 63 · `admin` 59 · `editor` 40 · `sales-support` 11 · `author` 9
  - `admin` — everything except `feature-flags.*`, `system-settings.view`, `roles.delete`
  - `editor` — full content CRUD + media/taxonomies/menus/SEO + contact-message triage; no users, roles, or configuration
  - `author` — write posts, upload media, read taxonomies/SEO; no deletes, no other modules
  - `sales-support` — full contact inbox, read-only on content
- [x] **Post ownership scoping** — added `posts.update.any`. `posts.update` alone limits editing to posts the user authored; `PostPolicy::update()` enforces it. Kept as an explicit permission rather than inferred from `posts.delete`, so update scope never shifts as a side effect of an unrelated grant. Authorless posts are not author-editable
- [x] **Password policy** — `Password::defaults()` now requires 12 chars with mixed case, numbers, and symbols; `uncompromised()` (HIBP) is applied in production only, so tests and local dev do not depend on an external API
- [x] **Audit log data retention** — `AuditLog` uses `MassPrunable`; `config('audit.retention_days')` defaults to **365** (`AUDIT_RETENTION_DAYS`), and `model:prune` is scheduled daily with `onOneServer()` + `withoutOverlapping()`. A retention of `0` disables pruning via an explicitly non-matching query — an unconstrained prunable builder would delete the whole table
- [~] **SVG sanitization** — no sanitizer exists, but `config/media.allowed_types` excludes `svg`, so the vector is closed. Blocks adding SVG to the allowlist; not an active hole
- [ ] ⚠️ **Retention period is an assumption.** 365 days was chosen as a common default without a stated compliance requirement — confirm it suits this deployment

---

## 9. Public Website (PRD §8)

- [x] Home — `Public\HomeController`
- [x] Services index + detail
- [x] Portfolio index + detail
- [x] Blog index + detail
- [x] News index + detail
- [x] Contact — form posts to `api/v1/public/contact-messages`
- [x] 404 — `errors/404.blade.php`
- [x] Dynamic `{slug}` catch-all for CMS-managed pages
- [x] Public API consumption pattern established
- [x] Menu-driven navigation — layout reads `$menu?->rootItems` with a hardcoded-anchor fallback when no menu exists
- [x] 🐞 **`public/robots.txt` deleted.** The stock Laravel static file shadowed the `seo.robots` route on every real web server (`artisan serve`, nginx `try_files $uri`, Apache), so production served `Disallow:` with no `Sitemap:` directive and no `/api/` block. `SeoInfrastructureTest` passed throughout because the test kernel dispatches straight to the router and never consults `public/` — `PublicHeadOutputTest` now asserts the file's absence on the filesystem instead
- [x] **About, Privacy Policy, Terms & Conditions** — seeded and published by `PageSeeder`, which `DatabaseSeeder` had never called. Placeholder copy; the two legal pages carry an explicit "not legal advice" notice
- [x] **Header and footer menus** — seeded by `MenuSeeder`, so nav no longer falls back to homepage-only anchors
- [x] `PageSeeder` invalidates the sitemap cache explicitly — `DatabaseSeeder`'s `WithoutModelEvents` suppresses `SitemapCacheObserver`, which otherwise leaves `sitemap.xml` stale after seeding
- [x] Pagination on all three index pages (posts, services, portfolio)
- [x] Contact form verified end-to-end — `POST api/v1/public/contact-messages` returns 201 and persists
- [x] **Public taxonomy archives** — `/blog/category/{slug}`, `/blog/tag/{slug}` and the `news` equivalents. Registered before the `{slug}` catch-alls (a test covers the collision where a post is literally slugged `category`). Unknown terms 404 rather than rendering a thin empty archive. Categories and tags on post pages are now links
- [x] **Demo content seeded** — `DemoContentSeeder` adds 3 services, 3 portfolio projects, and 3 posts with categories/tags, so no public page renders an empty state
- [x] 🐞 **Contact form was inert in browsers.** It was written with Alpine directives (`x-data`, `x-ref`, `@submit.prevent`) but **Alpine is not a dependency of this project** — it appears in no import, `package.json`, or layout. The markup rendered and the button did nothing; only direct API calls worked. Rewritten in vanilla JS against the existing `apiRequest` helper, matching every other script in the codebase
- [ ] Site name — `og:title` renders `config('app.name')` via `general.site_name`. Reported set in `.env`; verify Website Settings → General reflects it (the seeded row captured the old value and is not re-synced)
- [ ] Archive pages are excluded from `sitemap.xml` by design (content, not listings) and are left indexable

---

## 10. Email System (PRD §16)

- [x] SMTP configuration — `EmailSetting` model + admin API
- [x] Test email endpoint — `POST api/v1/admin/email-settings/test`
- [x] Auto reply — toggleable, `contact-message-auto-reply.blade.php`
- [x] Queue support — `SendContactMessageEmails` job
- [x] Templates — notification, auto-reply, test
- [ ] General-purpose editable email templates (if the PRD means CMS-editable bodies) ⚠️ *ambiguous heading*

---

## 11. Testing (PRD §24)

- [x] Feature tests — **233 tests, 839 assertions, all passing**
- [x] API tests — public + admin surfaces covered per module
- [x] **Architecture tests** — 8 `arch()` rules enforcing feature-first layering (controllers/requests/policies/resources stay off `DB`, models stay off HTTP, feature enums string-backed, strict equality, no debug helpers). Scoped per namespace deliberately: a blanket `expect('App')` parses every file and exhausts memory. `phpunit.xml` now sets `memory_limit=512M`, which arch testing genuinely needs
- [ ] **Browser tests** — deferred by decision (needs `pest-plugin-browser` + Playwright, ~150MB). The CMS admin UI and the public contact form remain without end-to-end coverage
- [ ] **Unit tests** — only the generated `ExampleTest` exists
- [ ] Acceptance tests ⚠️ *needs criteria*

---

## 12. Deployment & Non-Functional (PRD §22, §25)

**Target confirmed: shared hosting (cPanel / Plesk).** Docker, Nginx, and Laravel Cloud are out of scope for this deployment.

- [x] **Shared hosting guide** — [DEPLOYMENT.md](DEPLOYMENT.md): document root, first deploy, required `.env` changes, both cron jobs, post-deploy verification, redeploy procedure, and platform constraints
- [x] Apache — `public/.htaccess` ships with correct rewrite rules; no extra config needed
- [x] New config documented in `.env.example` — `THEME`, `THEME_CACHE`, `AUDIT_RETENTION_DAYS`, `MEDIA_RESPONSIVE`
- [x] Environment docs — production `.env` requirements are tabulated in the deploy guide
- [ ] ~~Docker setup~~ — **not needed** for this deployment
- [ ] ~~Nginx config~~ — **not needed**; hosting is Apache-based
- [ ] Staging environment — no separate staging documented yet
- [ ] Cloud storage — media stays on the local `public` disk; S3/Spaces config is untested
- [ ] Performance budget, accessibility target, browser support matrix ⚠️ *all need targets defined*

### Two cron jobs are mandatory on this platform

Without them the app degrades **silently** — no error, no log:

1. `schedule:run` every minute → drives the nightly audit-log prune
2. `queue:work --stop-when-empty --max-time=55` every minute → delivers contact-form email. Without it, submissions save fine and the visitor sees success, but **nobody is ever notified**

---

## Recommended Sequence

### ~~Phase 0 — Unblock the product~~ ✅ DONE 2026-08-09
1. ~~Define and seed the permission matrix~~ ✅ (§8) — all five roles now carry real allocations, plus post ownership scoping. The CMS can be handed to a second person.

### ~~Phase 1 — Close the "collected but unused" gaps~~ ✅ DONE 2026-08-09

2. ~~Delete `public/robots.txt`~~ ✅ (§9)
3. ~~Render `og:image` and JSON-LD `structured_data` in the public layout~~ ✅ (§5)
4. ~~Inject integration scripts into the public layout~~ ✅ (§7)
5. ~~Add the Google Search Console and Custom Scripts providers~~ ✅ (§7)
6. ~~Seed About / Privacy Policy / Terms pages and header/footer menus~~ ✅ (§9) — `APP_NAME` still needs setting in `.env`

Suite at that point: **199 tests / 731 assertions**.

### ~~Phase 2 — Security hardening~~ ✅ DONE 2026-08-09
6. ~~Configure `Password::defaults()`~~ ✅ (§8)
7. ~~Audit log retention / pruning~~ ✅ (§8)
8. SVG sanitization — deferred by design; only needed if `svg` is added to `config/media.allowed_types` (§8)

### ~~Phase 3 — Theme engine~~ ✅ DONE 2026-08-09
9. ~~Theme package structure + config contract~~ ✅
10. ~~Runtime resolution and view-path registration~~ ✅
11. ~~Migrate public views into a default theme~~ ✅
12. ~~Theme settings surfaced in the CMS~~ ✅

### Phase 4 — Content & media depth
13. Expand block types; add reusable/global blocks; block revisions (§4)
14. ~~Responsive variants~~ ✅ — remaining: media usage tracking, video posters, original-file optimization (§6)
15. Render hero-block `media_id` — heroes accept a media reference that no view displays (§4)

### Phase 5 — Quality & ship
16. ~~Arch tests for layering~~ ✅ — browser tests still deferred (§11)
17. ~~Deployment docs~~ ✅ — shared-hosting guide written; Docker/nginx dropped as not applicable (§12)

---

## Blocked on User Input

These cannot be built to spec until the PRD headings are filled in:

- The **full supported block-type list** and each block's field schema (§6)
- The **granular permission matrix** — which of the 40+ permissions each of the 5 roles gets (§18)
- **Audit log retention period** (§19)
- **Non-functional targets** — performance budget, accessibility level (WCAG A/AA?), browser support matrix (§22)
- Whether **reCAPTCHA enforcement** on the contact form is in V1 scope (§15)
- Whether **CMS-editable email template bodies** are in V1 scope, or only the fixed Blade templates (§16)
- Whether the **Next.js frontend** is V1 or deferred (§7)
