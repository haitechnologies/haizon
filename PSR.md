# AI INSTRUCTIONS: PHP/MYSQL BACKEND STANDARDS

## 1. CORE ARCHITECTURE & STACK
- Stack: PHP 8.2+ (Strict Types, Enums, Readonly DTOs), MySQL 8.0+ (InnoDB, utf8mb4), Composer PSR-4.
- Pattern: Strict Layered Architecture. Controllers (Validation/Routing) → Services (Business Logic) → Repositories (Data Access, 1 file = 1 table) → Models (Readonly DTOs).
- Prefix: ALWAYS use `erp_` for database tables (e.g., `erp_users`).

## 2. STRICT PROHIBITIONS (NEVER DO THIS)
- NO raw SQL, NO `SELECT *`, NO string interpolation in queries. Use PDO prepared statements exclusively.
- NO `die()`, `exit()`, `var_dump()`, `eval()`, globals, singletons, or magic methods (`__get`/`__set`).
- NO implicit type coercion. NO returning `false`/`null` on errors. Throw typed Domain Exceptions.
- NO mixed naming conventions. NO undocumented magic numbers (use Enums/Constants).
- NO modifying existing migration files. Append new ones: `YYYYMMDD_HHMMSS_action.sql`.

## 3. NAMING & CONVENTIONS
- PHP Files/Classes: `PascalCase.php`
- Methods/Variables: `camelCase`
- DB Tables/Columns: `snake_case` (Tables MUST be singular: `user`, not `users`).
- DB Indexes/Keys: `idx_table_column`, `fk_table_column`.
- Functions: Max 40 lines. Enforce Single Responsibility Principle.

## 4. PHP 8.2+ & DATABASE RULES
- ALWAYS start files with `<?php declare(strict_types=1);`.
- USE native type hints for ALL parameters, returns, and properties.
- USE `readonly class` for DTOs. USE `enum` for state/status mappings.
- DB Primary Keys: `BIGINT UNSIGNED AUTO_INCREMENT` or `CHAR(36)` (UUID).
- DB Audit Columns: ALWAYS include `created_at` and `updated_at` (TIMESTAMP).
- DB Comments: Document every column with inline `COMMENT 'business_purpose'`.

## 5. AI EXECUTION & OUTPUT PROTOCOLS
1. **Context First**: Read existing codebase before generating. Match established patterns exactly.
2. **Output Format**: Wrap all code in `<file path="src/...">` tags. Include ALL `use` statements.
3. **Token Efficiency**: 
   - For NEW files: Output the complete file.
   - For EXISTING files >100 lines: Output ONLY the modified methods using standard Search/Replace blocks to save tokens.
4. **No Hallucinations**: If schema/context is ambiguous, ASK for clarification. Do not invent tables/columns.
5. **Documentation**: PHPDoc on public methods (explain WHY, not WHAT). Add inline comments for complex business logic.
6. **Testing**: Mock DB/External APIs. Return strongly-typed DTOs, never raw arrays or PDOStatements.