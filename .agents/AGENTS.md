# FuelFleet Workspace Rules

## 1. PHP Subdirectory Routing and Redirects Guardrail
When implementing page redirects or generating URLs to other pages:
- **Never** use hardcoded domain-relative paths (e.g., `header("Location: /path")`) directly. This bypasses the base path and causes 404 errors in subdirectory deployments.
- **Use the Redirect Helper**: Always prefer using the `$response->redirect($path)` method when `$response` is available.
- **Manual Redirects**: If redirecting outside a controller (e.g., in a static middleware class), always retrieve the dynamic base path via `\App\Core\Request::getBasePath()` and prepend it to the path:
  ```php
  $basePath = \App\Core\Request::getBasePath();
  header("Location: " . $basePath . "/target-path");
  exit;
  ```
