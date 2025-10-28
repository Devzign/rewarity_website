# Rewarity Code Style

This project follows pragmatic standards focused on readability, security, and consistency.

- PHP: PSR-12 via `phpcs` (config: `phpcs.xml.dist`)
- Editors: use `.editorconfig` in the repo for indentation, line endings, and UTF-8.
- JSON, YAML, JS, CSS, HTML: 2-space indentation, LF line endings.

## PHP
- Use strict mysqli with exceptions (enabled in `includes/config.php`).
- Prefer prepared statements for all SQL (already used by APIs).
- Use helper functions from `api/helpers.php` for:
  - `json_response()` to send JSON + status codes consistently
  - `require_auth()` for API/session auth
  - `get_json_body()` for JSON payload parsing
  - `next_numeric_id()` for non-AI tables
- New API files should:
  1. `require_once` config and helpers
  2. Call `require_auth()`
  3. Route by method and validate inputs
  4. Return all responses via `json_response()`

## JavaScript (Admin UI)
- Avoid global variables; wrap page logic in an IIFE.
- Use `const`/`let`, template strings, and async/await.
- Keep DOM queries at top; small helper functions for rendering and network calls.
- Show user feedback via alert components; hide/show with utility classes.

## Naming
- Tables: `snake_case_master` pattern from existing schema.
- Columns: `PascalCase` to match current DB (e.g., `ColorName`, `IsActive`).
- API JSON: `snake_case` for fields where multiple words are returned (`is_active`, `created_on`).

## Security
- Authentication enforced for admin APIs via `require_auth()`.
- Image uploads are type/size checked; saved under `/uploads`.
- CORS headers in `helpers.php` allow same-origin apps; OPTIONS returns 204.

## OpenAPI
- Document new endpoints in `api/openapi.yaml`. Reuse existing schemas or add new ones under `components.schemas`.

## How to lint (optional)
If PHP_CodeSniffer is available locally:

```
phpcs --version
phpcs
```

You can auto-fix many issues with:

```
phpcbf
```

> Note: Composer installation is optional; these configs are safe to keep in the repo even if tools aren’t installed on the server.

