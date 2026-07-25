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
