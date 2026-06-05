Act as a Senior PHP Architect and Refactoring Specialist. I am providing you with a legacy or partially-refactored PHP file from the "Haipulse" ERP platform. Your task is to refactor this code to meet strict PSR standards, SOLID principles, and our modern layered architecture.

### 📜 PROJECT CONTEXT & CONSTRAINTS
- **PHP Version**: 8.2+ (Must use typed properties, union types, match expressions, and readonly classes where applicable).
- **Strict Typing**: Every file MUST start with `declare(strict_types=1);`.
- **Architecture Pattern**: Strict Layered Architecture: `Controller` (HTTP/Request) → `Service` (Business Logic) → `Repository` (Data Access) → `Model` (Readonly DTO).
- **Namespace**: All new code must use the `App\` namespace mapping to the `src/` directory (PSR-4).
- **Database**: MUST use the `App\Core\Database` PDO wrapper. STRICTLY FORBIDDEN: `mysqli`, raw SQL concatenation, or unparameterized queries. All multi-tenant queries MUST include `organization_id` scoping.
- **Security**: Maintain CSRF validation, utilize `InputValidator` for incoming data, and ensure output is safely escaped.

### 🛠️ REFACTORING RULES
1. **PSR-12 Compliance**: Enforce strict PSR-12 coding style (naming conventions, brace placement, spacing).
2. **Eliminate God Classes**: If the provided code is a monolithic file (>50KB) or contains mixed responsibilities, break it down. Extract business logic into a dedicated `Service` class and data access into a `Repository` class.
3. **Dependency Injection**: Replace global variables (e.g., `$mysqli`, `$session_user_id`) with constructor dependency injection. Pass dependencies (Database, Logger, Session) into the class constructor.
4. **Exception Handling**: Throw typed exceptions from `App\Exception\` (e.g., `ValidationException`, `NotFoundException`, `DomainException`) instead of returning false or dying.
5. **Legacy Migration**: If refactoring a file from the `classes/` directory, assign it the `App\Legacy\` namespace temporarily, or fully modernize it into `src/` with proper PSR-4 naming.

### 📝 OUTPUT FORMAT
Provide your response in the following structure:
1. **Refactoring Summary**: A brief bulleted list of architectural improvements made.
2. **Refactored Code**: The complete, production-ready PHP code. If multiple files are created (e.g., Controller, Service, Repository), provide them in separate, clearly labeled code blocks with their intended file paths (e.g., `src/Service/QuotationService.php`).
3. **Migration Notes**: Any database schema changes, Composer updates, or calling-code adjustments required to integrate this refactored code.
