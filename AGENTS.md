<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

Project rules in `.cursor/rules/` override this file when they disagree.

## Foundational Context

This is a **JSON API** (Property Offers). No SPA, Inertia, Vue UI, session, or auth.

Runtime: **PHP 8.5** in Docker (`php:8.5-fpm`). `composer.json` requires `php: ^8.3`.

Installed PHP (trust `composer.json` / `composer.lock`, not a guessed roster):

- php - 8.5 (container)
- laravel/framework (LARAVEL) - ^13
- laravel/tinker - ^3
- laravel/boost (BOOST) - ^2
- laravel/pail (PAIL) - ^1
- laravel/pao (PAO) - ^1
- laravel/pint (PINT) - ^1
- nunomaduro/larastan (LARASTAN) - ^3
- phpunit/phpunit (PHPUNIT) - ^13
- mockery/mockery - ^1.6

Do **not** assume Fortify, Sanctum, Wayfinder, Inertia, Echo, Reverb, Sail, or Vue are installed.

## Skills Activation

Skills live in `.cursor/skills/`. Activate before working in that domain:

- `laravel-best-practices` — Laravel PHP
- `api-v1-development` — `/api/v1` endpoints and envelope
- `api-swagger-docs` — OpenAPI / L5-Swagger

Do not activate Inertia/Vue/Fortify/Wayfinder/Echo skills — they are not in this repo.

## Conventions

- Follow `.cursor/rules/` first (`property-offers-api`, `laravel-architecture`, `api-v1`, `docker`, `backend-testing`, `code-quality`).
- Check sibling files; match existing patterns.
- Use descriptive names. Domain enums: `*Enumerator` in `app/Services/<Name>/Enumerators/`.
- Business logic in `app/Services/<Name>/` with `*ServiceInterface`. No Action classes.

## Verification Scripts

- Do not create verification scripts or tinker when tests already cover the behavior.

## Application Structure

- Stick to the existing directory structure; don't create new base folders without approval.
- Do not change dependencies without approval.
- Versioned HTTP: `app/Http/Controllers/Api/V1/`. JSON envelope via `ApiServiceInterface` — not `view()`, `redirect()`, or raw `response()->json()` on `/api/v1`.

## Documentation Files

- Only create documentation files if the user explicitly asks.

## Replies

- Be concise. Focus on what matters.

=== boost rules ===

# Laravel Boost

## Tools

- Prefer Boost MCP tools when they work: `database-query` (read-only), `database-schema`, `search-docs`, `get-absolute-url`.
- Skip `browser-logs` unless there is a browser UI (this API has none).

## Searching Documentation

- Use `search-docs` for Laravel ecosystem APIs that **are installed**. Skip for copy-only edits.
- Pass a `packages` array when you know the package. Do not invent Inertia/Fortify queries.

### Search Syntax

1. Words = AND: `rate limit`
2. `"quoted phrases"` for adjacent words
3. Mixed: `middleware "rate limit"`
4. OR: `queries=["routing", "validation"]`

## Artisan

Run **inside Docker** (see `docker` rule). Do not run `php` / `composer` / `php artisan` on the host unless the user asks.

```bash
docker compose exec -T php php artisan route:list --path=api
docker compose exec -T php php artisan config:show app.name
```

Pass `--no-interaction` on artisan commands.

## Tinker

Prefer tests and factories over tinker. If you use tinker, still via the `php` container. Single quotes around `--execute` to avoid shell expansion.

=== php rules ===

# PHP

- Curly braces on all control structures.
- Constructor property promotion. No empty public zero-parameter constructors unless private.
- Explicit parameter and return types.
- PHPDoc for array shapes; prefer PHPDoc over noisy inline comments.
- Enum **cases** TitleCase; enum **class names** follow `*Enumerator`.

=== tests rules ===

# Test Enforcement

- Test every code change. Run the smallest set of tests that covers it.
- PHPUnit classes only (`php artisan make:test --phpunit {Name}`). Convert Pest if you see it.
- See `backend-testing` for service vs feature coverage.

```bash
docker compose exec -T php php artisan test --compact --filter=testName
docker compose exec -T php php artisan test --compact tests/Feature/ExampleTest.php
```

=== laravel/core rules ===

# Do Things the Laravel Way

- `php artisan make:` (in Docker) for migrations, controllers, models, tests. Generic class: `make:class`.
- APIs: Eloquent API Resources + URL versioning (`/api/v1`). Follow `api-v1`.
- Named routes for URLs.
- Factories and factory states in tests. `fake()` as used in this app.
- Do not add Horizon, Sanctum, or a frontend without an explicit user request.

=== pint/core rules ===

# Laravel Pint

After PHP edits:

```bash
docker compose exec -T php vendor/bin/pint --dirty --format agent
```

Do not use `pint --test` to format; that only checks.

=== phpunit/core rules ===

# PHPUnit

- PHPUnit 13. Class tests, `#[Test]`, descriptive snake_case names.
- Do not put `Feature/` or `Unit/` in the `{name}` argument to `make:test`.
- Do not delete tests without approval.
- After a test file changes, re-run that file. Ask before the full suite.
- Cover happy path, failures, and meaningful edge cases.

</laravel-boost-guidelines>
