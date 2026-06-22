# Contributing to Haizon ERP

## Prerequisites

- PHP 8.2+
- MySQL 8.0+
- Composer

## Local Setup

```bash
git clone <repo>
cd haizon
cp .env.example .env
# Edit .env — set DB_HOST, DB_NAME, DB_USER, DB_PASS, STRIPE_KEY, etc.
composer install
php database/migrate.php
```

## Running Tests

```bash
composer test                  # PHPUnit
php tests/run_all_tests.php    # All tests
```

## Static Analysis & Code Style

```bash
composer phpstan      # PHPStan (level as configured in phpstan.neon)
composer phpcs        # PHP CodeSniffer
composer phpcbf       # Auto-fix code style
php -l <file>         # Syntax check
```

## Coding Conventions

1. `declare(strict_types=1)` on all `src/` files
2. PSR-4 autoload: `App\` → `src/`
3. No `SELECT *` — always specify columns
4. Use `DB::*` constants for table names — never hardcode
5. Use named parameters (`:param`) for DB queries
6. Scope all tenant queries by `organization_id`
7. Soft deletes (`is_active = 0`) — no hard deletes
8. No `@` error suppression
9. No comments unless requested
10. New CRUD: follow the 7-step template in `docs/AGENTS.md`

## Git Workflow

- Branch from `main`: `feature/description`, `fix/description`
- Write descriptive commit messages
- Run `composer phpcs && composer phpstan` before committing
- Keep PRs focused on a single concern
