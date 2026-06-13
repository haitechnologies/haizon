# .context/ — LLM/AI Agent Context Directory

This directory contains consolidated documentation for AI coding agents working on the Haipulse ERP codebase.

## Files

| File | Purpose |
|------|---------|
| `rules.md` | Coding rules, security requirements, and strict avoidance list |
| `architecture.md` | System architecture, layer patterns, database access, multi-tenancy |
| `conventions.md` | File naming, table naming, column conventions, permission patterns |

## Usage

When starting a new task, an AI agent should read these files in order:
1. `.context/rules.md` — What you can and cannot do
2. `.context/architecture.md` — How the system is structured
3. `.context/conventions.md` — How to name things correctly

For database details, also read:
- `src/Core/DB.php` — All table constants with alias map
- `docs/codebase_and_db_summary.md` — Current table inventory and standards
- `docs/DATABASE_OPTIMIZATION_PLAN.md` — Executed optimization plan

## Keep Updated

When adding new modules, tables, or patterns, update the relevant `.context/` file.
