# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What This Project Is

A headless, API-first **company-profile CMS + public website platform** (Laravel 13 / PHP 8.5). The full product spec is [docs/PRD.md](docs/PRD.md).

**Read this first:** `docs/PRD.md` is currently an *outline* — section headings only, no filled-in requirements. Treat its headings as scope and vocabulary, not as a specification. Do not invent acceptance criteria from a heading; ask the user for the detail before building against it.

## Current State

The repo is a **stock Laravel 13 skeleton**. None of the PRD exists yet:

- `app/` contains only `Models/User.php`, `Http/Controllers/Controller.php`, `Providers/AppServiceProvider.php`
- `routes/` has only `web.php` (returns the `welcome` view) and `console.php` — **there is no `routes/api.php`**
- Migrations are the three Laravel defaults (users, cache, jobs); the only factory is `UserFactory`
- `tests/` holds the two generated `ExampleTest` files

Practical consequence: whoever writes the first feature *establishes* the conventions for everything after it. Follow the target architecture below rather than improvising, and record decisions with the Boost `record-rule` tool as you go.

## Commands

```bash
composer setup                  # first run: install, .env, key, migrate, npm install, build
composer run dev                # server + queue:listen + pail (logs) + vite, concurrently

composer test                   # clears config cache, then runs the suite
php artisan test --compact
php artisan test --compact --filter=aTestName
php artisan test --compact tests/Feature/SomeTest.php

vendor/bin/pint --dirty --format agent   # required after touching any PHP file
npm run build                            # or `npm run dev` for the Vite watcher
php artisan pail                         # tail application logs
```

### Testing notes

- Pest 5. `tests/Pest.php` binds `TestCase` to the `Feature` suite but **leaves `RefreshDatabase` commented out** — a test that touches the database must `uses(RefreshDatabase::class)` itself, or uncomment it there once the schema is real.
- `phpunit.xml` forces sqlite `:memory:`, `array` cache/session/mail, and `sync` queue. Local dev uses the `database/database.sqlite` file instead, so anything relying on real queue or mail behavior needs explicit test doubles.

## Target Architecture (from the PRD)

### API-first / headless

The CMS admin and the public website are both **clients of a versioned HTTP API**; nothing reads Eloquent models directly across that boundary. V1 renders the public site with Blade themes, but every page is specified to consume the same API a future Next.js frontend would. Keep public and admin APIs separated — different route groups, different auth, different resources.

`bootstrap/app.php` already JSON-renders exceptions for `api/*` requests, **but no API route file is registered**. Adding one means running `php artisan install:api` or wiring `api:` into `->withRouting(...)` yourself — don't assume `routes/api.php` is loaded.

### Feature-first layering

```
HTTP → Controller → Action → Service → Repository → Model
```

Controllers stay thin: validate via Form Request, delegate to an Action, return an API Resource. Business rules live in Actions/Services; query construction lives in Repositories. Supporting pieces the PRD calls for: DTOs, Policies, Observers, Events/Listeners, Jobs. Code is grouped **by feature**, not by technical type.

### Data model decisions

These are settled ADRs in the PRD — follow them rather than the Laravel default:

- **UUIDs** as identifiers.
- **Hybrid JSON block storage** — page content is a Content Block Engine; blocks are stored as JSON on the owning record rather than as one table per block type.
- **Polymorphic SEO** and **polymorphic media** relations — one SEO table and one media table serve every content type.
- **Generic Post module** — Blog and News are not separate entities; they are one `Post` type discriminated by a field. Resist adding a parallel `News` model.
- **Soft deletes** throughout, backed by an audit log with restore history.

### Theme engine

Themes are self-contained packages with their own configuration and user-editable settings, resolved at runtime. Public-site Blade views belong to a theme, not to `resources/views` at large.

### Feature flags

Modules are individually enable/disable-able per project, and the flag layer is the groundwork for later multi-tenancy. New CMS modules should be gated from the start.

### CMS module scope (V1)

Dashboard · Pages · Services · Portfolio · Posts · Menus · Media Library · Contact Messages · SEO · Integrations · Website Settings · Users & Roles · Audit Logs · System Settings.

Default roles: Super Admin, Admin, Editor, Author, Sales/Support — with a granular permission matrix, so authorize through Policies, never role-name string checks.

## Repo Conventions & Traps

- **The Laravel Boost guidelines block at the end of this file is generated.** Boost rewrites it on `php artisan boost:update` (which runs on `composer update`); `AGENTS.md` contains only that block. Edit only *outside* it. Never write the block's opening tag literally anywhere in this file — Boost locates the block by its first occurrence and will overwrite everything after it.
- `.ai/rules/index.md` maps file globs to rule files; read the matching rules before editing, as the generated block instructs.
- Project skills live in `.claude/skills/` (mirrored to `.agents/skills/`): `laravel-best-practices`, `testing-best-practices`, `tailwindcss-development`, `infer-conventions`. Activate the matching one when you enter its domain.
- Frontend is Tailwind v4 via `@tailwindcss/vite` — configuration is CSS-first in `resources/css/app.css`; there is no `tailwind.config.js`.
- `composer.json` declares `php: ^8.3`, but the locked dependencies need PHP ≥ 8.4.1 and the runtime is 8.5. Don't use 8.5-only syntax without raising the constraint first. On this machine Homebrew links `php` to 8.2 — use `/opt/homebrew/opt/php@8.5/bin/php` if `php -v` is below 8.4.

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.5. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Add or update tests for behavior and logic changes when a test provides meaningful regression coverage.
- Pure copy, styling, and layout-only changes do not require new or updated tests.
- When test coverage applies, run the affected tests and ensure they pass.
- Test the changed behavior and its important failure modes, but do not add tests beyond them.
- Read the `testing-best-practices` skill before writing tests.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

# Pest

- This project uses Pest. Create tests with `php artisan make:test --pest {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.
- Do not delete tests or test files without approval. They are part of the application.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `php artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/pest` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
