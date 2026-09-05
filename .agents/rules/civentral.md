---
trigger: always_on
---

# Civentral Project Rules

These rules apply to work in this repository. Civentral is a municipal health, sanitation, and urban services management system.

(Global debugging + token-efficiency + general change rules already apply via global Agent rules — this file covers Civentral-specific stack, architecture, and security only.)

## Project Stack

- Backend: PHP 8.x with a custom MVC-lite architecture.
- Database: PostgreSQL through Supabase and its PostgREST API.
- Frontend: PHP-rendered HTML, vanilla JavaScript, Tailwind CSS, ApexCharts, Leaflet, FontAwesome, and Tabler Icons.
- PHP dependencies are managed with Composer; frontend dependencies are managed with npm.
- Keep changes compatible with the existing mixed procedural and object-oriented PHP style.
- **No PSR-4 Autoloader**: Composer does not define an autoloader for `app/`. All classes, models, services, middleware, and helpers must be explicitly loaded using `require_once __DIR__ . '/...'` paths.

## Architecture

- `api/`: HTTP API entry points.
- `app/Controllers/`: request validation and business orchestration.
- `app/Models/`: data access and model behavior.
- `app/services/`: reusable business services.
- `app/repositories/`: domain data access repositories.
- `app/Middleware/`: route and authorization middleware (e.g., `AuthorizationMiddleware.php`).
- `app/validators/`: server-side request/data validation.
- `app/encryption/`: field and column encryption helpers (`EncryptionManager.php`).
- `app/Constants/`: canonical system constants, including `Permissions.php`.
- `Core/`: bootstrap, environment loading, requests, responses, routing, and shared framework code.
- `config/`: database, paths, and navigation configuration (`config/paths.php`, `config/database.php`).
- `database/migrations/`: Supabase PostgreSQL schema and migration scripts.
- `modules/`: departmental operational features (note filesystem spelling: `modules/surveillence/` with an 'e').
- `pages/`: dashboard, analytics, reporting, and other views.
- `includes/`: shared layout components and data masking (`header.php`, `sidebar.php`, `data-mask.php`).
- `management/`: administrative settings, users, and system logs.
- `assets/`: CSS, JavaScript, images, and other static files.

Preserve these boundaries. Put new behavior in the layer that owns it instead of duplicating logic across API endpoints or pages.

## Database and API Rules

- Load configuration through `Core/Env.php` and environment variables.
- Never hardcode passwords, API keys, service keys, encryption keys, tokens, or other credentials.
- Use the database abstraction in `config/database.php` for Supabase/PostgREST access (`Database::getInstance()`).
- Use PostgREST filters and the existing database methods rather than introducing ad-hoc SQL or direct database connections.
- Preserve least-privilege behavior and HTTPS certificate verification in database requests.
- API endpoints must use the standardized response helpers in `Core/Response.php`, especially `Response::success()` and `Response::error()`.
- Always use `site_url($path)` (defined in `config/paths.php`) for links, API endpoints, and asset paths instead of hardcoding `/capstone/` or fragile relative traversal (`../`).
- Validate request data on the server before using it in database operations or business logic.
- Preserve existing pagination, filtering, error, and HTTP status conventions.

## Authentication and Authorization

- Authentication and authorization must be enforced server-side.
- Never trust roles, departments, permissions, or user identity supplied by the client.
- RBAC has two separate checks:
  - Capability: what the user may do, such as `patients.view` or `patients.create`.
  - Department scope: which department's data the user may access.
- Reference `app/Constants/Permissions.php` (`App\Constants\Permissions`) for capability permission strings rather than ad-hoc literals.
- Keep capability checks separate from department-scope checks.
- Apply department filters in shared query functions for non-admin users.
- Administrators retain full scope where the existing RBAC design grants it.
- Admin-only features such as compliance, settings, and role/user administration must have explicit hard exclusions; they must not become available through department fallback.
- Do not duplicate shared functions for each department.
- Do not alter role identifiers or the ordering used by existing role definitions without a specific migration plan.

## Security and Privacy

- Treat patient, medical, employee, permit, payment, and operational data as sensitive.
- Preserve CSRF protection, session controls, rate limiting, encryption, data masking, and audit logging.
- Reuse `includes/data-mask.php` and existing encryption and audit services where applicable.
- Escape output appropriately for its context and prevent XSS, injection, IDOR, and privilege-escalation vulnerabilities.
- Do not expose secrets or sensitive records in logs, API errors, frontend JavaScript, documentation, screenshots, or test fixtures.
- Do not copy credentials from README files, load-test scripts, or other repository artifacts into new code.

## Frontend Rules

- Follow the existing Tailwind styles and component patterns.
- Reuse the project's existing icon, chart, map, layout, and notification libraries.
- Keep UI behavior responsive and accessible.
- Keep authorization decisions on the server; frontend visibility is not a security boundary.
- Keep frontend changes scoped to the relevant page or module and avoid unrelated visual rewrites.

## Change and Testing Rules (Project-Specific)

- Run focused validation after changes. Useful commands include:
  - `composer install`
  - `npm install`
  - `find app Core config api modules pages -name "*.php" -exec php -l {} +` (fast PHP syntax verification)
  - `php -S 0.0.0.0:8080 -t .`
<<<<<<< HEAD:GEMINI.md
  - `php /tmp/phpstan.phar analyse app Core config --level=1`
  - `php tests_archive_do_not_deploy/load/run-load-test.php --help`
  - `k6 run tests_archive_do_not_deploy/load/k6-load-test.js`
=======
  - `php tests/load/run-load-test.php --help`
  - `k6 run tests/load/k6-load-test.js`
>>>>>>> e06427a1a3d2f513f190359b00304f0463937c4b:.agents/rules/civentral.md
  - `php bin/scheduler.php --help`
- Note on PHPStan: `/tmp/phpstan.phar` is ephemeral and may not exist in every environment. When unavailable, use `php -l` for syntax validation or download PHPStan before running analysis.
- `npm test` is currently a placeholder that exits with an error; do not report it as a passing test suite.

## Documentation References

For detailed rules, consult these repository documents before making security-sensitive or architectural changes:

- `README.md` for current project and deployment context.
- `RBAC_ARCHITECTURE.md` for capability and department-scope authorization.
- `DATABASE_SECURITY.md` for database security requirements.
- `docs/system/PROJECT_STRUCTURE.md` for directory responsibilities.
- `docs/security/roles_permissions_matrix.md` for permission definitions.
- `docs/security/` for security analysis and validation guidance.