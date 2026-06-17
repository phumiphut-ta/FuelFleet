<?php
namespace App\Core;

class Router {
    protected array $routes = [];
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response) {
        $this->request = $request;
        $this->response = $response;
    }

    public function get(string $path, mixed $callback): void {
        $this->routes['GET'][$path] = $callback;
    }

    public function post(string $path, mixed $callback): void {
        $this->routes['POST'][$path] = $callback;
    }

    public function resolve() {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();
        
        // Trim trailing slash except if path is "/"
        if ($path !== '/' && str_ends_with($path, '/')) {
            $path = rtrim($path, '/');
        }

        // Validate CSRF for POST requests
        if ($method === 'POST') {
            $token = $_POST['csrf_token'] ?? null;
            if (!\App\Core\Csrf::validateToken($token)) {
                $this->response->setStatusCode(403);
                return $this->renderView('error', ['message' => 'การตรวจสอบความปลอดภัยล้มเหลว (CSRF token invalid)']);
            }
        }

        $callback = $this->routes[$method][$path] ?? false;

        if ($callback === false) {
            // Check dynamic routes
            foreach ($this->routes[$method] ?? [] as $routePath => $routeCallback) {
                // e.g. /admin/cars/{id} -> #^/admin/cars/([^/]+)$#
                $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '([^/]+)', $routePath);
                $pattern = '#^' . $pattern . '$#';
                
                if (preg_match($pattern, $path, $matches)) {
                    array_shift($matches); // Remove first match
                    
                    if (is_array($routeCallback)) {
                        $controller = new $routeCallback[0]();
                        return call_user_func_array([$controller, $routeCallback[1]], [$this->request, $this->response, ...$matches]);
                    }
                    if (is_callable($routeCallback)) {
                        return call_user_func_array($routeCallback, [$this->request, $this->response, ...$matches]);
                    }
                }
            }

            $this->response->setStatusCode(404);
            return $this->renderView('404');
        }

        if (is_array($callback)) {
            $controller = new $callback[0]();
            return call_user_func([$controller, $callback[1]], $this->request, $this->response);
        }

        if (is_callable($callback)) {
            return call_user_func($callback, $this->request, $this->response);
        }

        $this->response->setStatusCode(404);
        return $this->renderView('404');
    }

    public function renderView(string $view, array $params = []): string {
        $layoutContent = $this->layoutContent($view);
        $viewContent = $this->renderOnlyView($view, $params);
        $output = str_replace('{{content}}', $viewContent, $layoutContent);
        
        // Inject CSRF token into POST forms dynamically
        $output = preg_replace_callback(
            '#(<form\b[^>]*\bmethod=["\']?post["\']?[^>]*>)#i',
            function($matches) {
                return $matches[1] . "\n" . '<input type="hidden" name="csrf_token" value="' . \App\Core\Csrf::generateToken() . '">';
            },
            $output
        );

        // Dynamically prepend the subdirectory base path to absolute URLs
        $basePath = Request::getBasePath();
        if (!empty($basePath)) {
            // Replaces href="/...", action="/...", src="/..."
            $output = preg_replace(
                '#(href|action|src)="/(?![/])#', 
                '$1="' . $basePath . '/', 
                $output
            );
            // Replaces events: '/...' in JavaScript
            $output = preg_replace(
                '#events:\s*\'/(?![/])#', 
                "events: '" . $basePath . '/', 
                $output
            );
        }
        
        echo $output;
        return $output;
    }

    protected function layoutContent(string $view): string {
        if (str_starts_with($view, 'public/liff_')) {
            return '{{content}}';
        }

        $layout = 'public';
        if (str_starts_with($view, 'admin/') && $view !== 'admin/login') {
            $layout = 'admin';
        }
        
        $layoutPath = dirname(__DIR__) . "/Views/layouts/{$layout}.php";
        if (file_exists($layoutPath)) {
            ob_start();
            include $layoutPath;
            return ob_get_clean();
        }
        return '{{content}}';
    }

    protected function renderOnlyView(string $view, array $params): string {
        foreach ($params as $key => $value) {
            $$key = $value;
        }
        $viewPath = dirname(__DIR__) . "/Views/{$view}.php";
        if (file_exists($viewPath)) {
            ob_start();
            include $viewPath;
            return ob_get_clean();
        }
        return "View file '{$view}' not found.";
    }
}
