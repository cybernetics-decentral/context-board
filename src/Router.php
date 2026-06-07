<?php

/**
 * Router — maps URL patterns to controller methods.
 *
 * SDD Reference: Section 3.2
 */
class Router
{
    private array $routes = [];

    /**
     * Register a route.
     */
    public function addRoute(string $method, string $pattern, object $controller, string $methodName): void
    {
        $this->routes[] = [$method, $pattern, $controller, $methodName];
    }

    /**
     * Dispatch the request to a matching route.
     */
    public function dispatch(string $httpMethod, string $uri): void
    {
        foreach ($this->routes as [$method, $pattern, $controller, $methodName]) {
            if ($method !== $httpMethod) {
                continue;
            }

            $paramNames = [];
            $regex = $this->patternToRegex($pattern, $paramNames);

            if (preg_match($regex, $uri, $matches)) {
                $params = [];
                foreach ($paramNames as $name) {
                    $params[] = $matches[$name] ?? null;
                }
                call_user_func_array([$controller, $methodName], $params);
                return;
            }
        }

        // No route matched
        http_response_code(404);
        echo '<h1>404 Not Found</h1><p>The requested page was not found.</p>';
    }

    /**
     * Convert a route pattern like /boards/{board_id} to a regex.
     */
    private function patternToRegex(string $pattern, array &$paramNames): string
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_]+)\}/', function ($matches) use (&$paramNames) {
            $paramNames[] = $matches[1];
            return '([^/]+)';
        }, $pattern);

        return '#^' . $regex . '$#';
    }
}
