<?php

namespace App\Core;

/**
 * Router Class
 * จัดการ URL routing และ dispatch requests
 */
class Router
{
    private $routes = [];
    private $groupPrefix = '';
    private $middlewares = [];

    /**
     * Define GET route
     */
    public function get($path, $handler)
    {
        $this->addRoute('GET', $path, $handler);
        return $this;
    }

    /**
     * Define POST route
     */
    public function post($path, $handler)
    {
        $this->addRoute('POST', $path, $handler);
        return $this;
    }

    /**
     * Define PUT route
     */
    public function put($path, $handler)
    {
        $this->addRoute('PUT', $path, $handler);
        return $this;
    }

    /**
     * Define DELETE route
     */
    public function delete($path, $handler)
    {
        $this->addRoute('DELETE', $path, $handler);
        return $this;
    }

    /**
     * Route group with prefix and middleware
     */
    public function group($attributes, $callback)
    {
        $previousPrefix = $this->groupPrefix;
        $previousMiddlewares = $this->middlewares;

        $this->groupPrefix = ($attributes['prefix'] ?? '');
        $this->middlewares = array_merge($this->middlewares, $attributes['middleware'] ?? []);

        $callback($this);

        $this->groupPrefix = $previousPrefix;
        $this->middlewares = $previousMiddlewares;
    }

    /**
     * Add route to routes array
     */
    private function addRoute($method, $path, $handler)
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $this->groupPrefix . $path,
            'handler' => $handler,
            'middlewares' => $this->middlewares
        ];
    }

    /**
     * Dispatch request to appropriate controller
     */
    public function dispatch($method, $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {
                return $this->executeRoute($route, $params);
            }
        }

        // 404 Not Found
        $this->handle404();
    }

    /**
     * Match path with route pattern
     */
    private function matchPath($routePath, $uri, &$params = [])
    {
        // Convert /user/{id} to regex pattern
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '([^/]+)', $routePath);
        $pattern = "#^" . $pattern . "$#";

        if (preg_match($pattern, $uri, $matches)) {
            // Extract parameter names
            preg_match_all('/\{([a-zA-Z0-9_]+)\}/', $routePath, $paramNames);

            // Map parameter values
            array_shift($matches); // Remove full match
            if (!empty($paramNames[1])) {
                $params = array_combine($paramNames[1], $matches);
            }

            return true;
        }

        return false;
    }

    /**
     * Execute route handler
     */
    private function executeRoute($route, $params = [])
    {
        // Execute middlewares
        foreach ($route['middlewares'] as $middleware) {
            $middlewareClass = "App\\Middleware\\{$middleware}";
            if (class_exists($middlewareClass)) {
                $middlewareInstance = new $middlewareClass();
                $middlewareInstance->handle();
            }
        }

        // Parse handler (Controller@method format)
        if (is_string($route['handler']) && strpos($route['handler'], '@') !== false) {
            list($controller, $method) = explode('@', $route['handler']);

            // Add App\Controllers namespace if not present
            if (strpos($controller, '\\') === false) {
                $controller = "App\\Controllers\\{$controller}";
            }

            if (!class_exists($controller)) {
                throw new \Exception("Controller {$controller} not found");
            }

            $controllerInstance = new $controller();

            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method {$method} not found in {$controller}");
            }

            return call_user_func_array([$controllerInstance, $method], $params);
        }

        // Handle closure
        if (is_callable($route['handler'])) {
            return call_user_func_array($route['handler'], $params);
        }

        throw new \Exception("Invalid route handler");
    }

    /**
     * Handle 404 Not Found
     */
    private function handle404()
    {
        http_response_code(404);

        if ($_ENV['APP_DEBUG'] === 'true') {
            echo '<h1>404 - Page Not Found</h1>';
            echo '<p>URI: ' . $_SERVER['REQUEST_URI'] . '</p>';
        } else {
            echo '404 - Page Not Found';
        }
    }

    /**
     * Get all routes
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}
