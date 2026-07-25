# FuelFleet Workspace Rules

## 1. PHP Subdirectory Routing and Redirects Guardrail
When implementing redirects or generating URLs for different contexts:

- **HTML Views & Templates (Dynamic URL Rewriter)**: 
  - **Never** manually prepend `Request::getBasePath()` inside HTML attributes like `href="/..."`, `action="/..."`, `src="/..."`, or inside JavaScript FullCalendar `events: '/...'`.
  - **Reason**: The core `Router::renderView()` has a regex-based **Dynamic URL Rewriter** that automatically detects and prepends the base path to these attributes in the final HTML response. Manually prepending it will result in double-prefixing (e.g., `/subpath/subpath/...`) and cause 404 errors.
  - **Standard**: Write them as standard domain-relative paths (e.g., `<a href="/admin/bookings">`).

- **Redirects in Controllers**: 
  - Always use the `$response->redirect('/target-path')` helper. The helper automatically detects and prepends the dynamic base path safely.

- **Manual Redirects (Outside Controllers)**:
  - If redirecting inside middleware or static contexts where `$response` is unavailable, you **must** manually prepend the base path retrieved from `\App\Core\Request::getBasePath()`:
    ```php
    $basePath = \App\Core\Request::getBasePath();
    header("Location: " . $basePath . "/target-path");
    exit;
    ```

- **JavaScript Fetch / AJAX Calls**:
  - Since the router's rewriter does not parse custom JS strings (except `events:`), you **must** manually prepend the base path to API endpoints when using `fetch()` or AJAX:
    ```javascript
    fetch('<?= \App\Core\Request::getBasePath() ?>/api/endpoint')
    ```

## 2. Robust API JSON Responses and Error Prevention
When writing controller methods that respond with JSON (`$response->json(...)`):

- **Catch `\Throwable`**: Always catch `\Throwable` rather than `\Exception` to ensure that any PHP type errors, compiler errors, or PDO errors are caught and returned as JSON, preventing HTML 500 pages from being returned.
- **Validate Directory Writability**: Always check write permissions using `is_writable()` before attempting file operations to return a clean, helpful JSON error.
- **Suppress PHP Warning Pollution**: Prepend `@` to native filesystem functions (e.g., `@mkdir`, `@move_uploaded_file`) to prevent PHP from writing HTML warnings into the output buffer, which corrupts JSON outputs.

## 3. CSRF Verification Exemption for API POST Routes
When designing route validation rules:

- **Exempt API Paths**: All POST endpoints starting with `/api/` (such as `/api/receipts/save-mobile`) must bypass session-based CSRF token validation in `Router::resolve()`. 
- **Alternative Security**: These stateless API endpoints are authenticated using secure temporary tokens in request parameters (which are immune to CSRF because attackers cannot forge token query parameter values).

