<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

Project rules in `.cursor/rules/` override this file when they disagree.

This is the same guidance as `AGENTS.md` for Cursor. This application is a **JSON API** (Property Offers): Laravel 13, PHP 8.5 in Docker, no Inertia/Vue, no auth.

## Foundational Context

Confirm package versions from `composer.json` / `composer show --direct`. Do not assume Fortify, Sanctum, Wayfinder, Inertia, Echo, or Vue.

Direct PHP: `laravel/framework` ^13, `laravel/tinker`, `laravel/boost`, `laravel/pail`, `laravel/pao`, `laravel/pint`, `nunomaduro/larastan` ^3, `phpunit/phpunit` ^13, `mockery/mockery`.

## Skills Activation

Use `.cursor/skills/`: `laravel-best-practices`, `api-v1-development`, `api-swagger-docs`.

## Conventions

Follow `.cursor/rules/` (`property-offers-api`, `laravel-architecture`, `api-v1`, `docker`, `backend-testing`, `code-quality`). Services + interfaces, not Action classes. Enums: `*Enumerator`.

## Verification Scripts

Do not add one-off scripts or tinker when tests already cover the behavior.

## Application Structure

No new top-level folders without approval. No dependency changes without approval. `/api/v1` uses `ApiServiceInterface` for JSON.

## Documentation Files

Only if the user asks.

## Replies

Be concise.

=== boost rules ===

# Laravel Boost

Prefer Boost MCP (`database-query`, `database-schema`, `search-docs`, `get-absolute-url`) when useful. Do not use `browser-logs` for this API.

Use `search-docs` for installed Laravel APIs only.

Artisan, Composer, Pint, and tests run in Docker:

```bash
docker compose exec -T php php artisan <command> --no-interaction
docker compose exec -T php vendor/bin/pint --dirty --format agent
docker compose exec -T php php artisan test --compact
```

=== php rules ===

# PHP

Curly braces always. Constructor promotion. Explicit types. PHPDoc array shapes. Enum cases TitleCase; class names `*Enumerator`.

=== tests rules ===

# Tests

PHPUnit only (`make:test --phpunit`). See `backend-testing`. Run the narrowest `--compact` filter in the `php` container.

=== laravel/core rules ===

# Laravel

`make:` commands in Docker. API Resources + `/api/v1`. Named routes. Factories in tests. Do not add Horizon, Sanctum, or a frontend unless asked.

=== pint/core rules ===

# Pint

`docker compose exec -T php vendor/bin/pint --dirty --format agent` after PHP edits.

=== phpunit/core rules ===

# PHPUnit

PHPUnit 13 classes. Do not delete tests without approval. Re-run the changed test file.

</laravel-boost-guidelines>
