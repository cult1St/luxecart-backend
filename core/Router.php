<?php

namespace Core;

/**
 * URL Router
 * 
 * Handles routing and dispatches requests to controllers
 */
class Router
{
    protected array $routes = [];
    protected Request $request;
    protected Response $response;

    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Register GET route
     */
    public function get(string $path, string $controller, string $method = 'index'): void
    {
        $this->registerRoute('GET', $path, $controller, $method);
    }

    /**
     * Register POST route
     */
    public function post(string $path, string $controller, string $method = 'store'): void
    {
        $this->registerRoute('POST', $path, $controller, $method);
    }

    /**
     * Register PUT route
     */
    public function put(string $path, string $controller, string $method = 'update'): void
    {
        $this->registerRoute('PUT', $path, $controller, $method);
    }

    /**
     * Register DELETE route
     */
    public function delete(string $path, string $controller, string $method = 'destroy'): void
    {
        $this->registerRoute('DELETE', $path, $controller, $method);
    }

    /**
     * Register route
     */
    protected function registerRoute(string $httpMethod, string $path, string $controller, string $method): void
    {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $this->normalizePath($path),
            'controller' => $controller,
            'action' => $method,
        ];
    }

    /**
     * Normalize path
     */
    protected function normalizePath(string $path): string
    {
        $path = trim($path, '/');
        return '/' . $path;
    }

    /**
     * Dispatch request
     */
    public function dispatch(Database $db): void
    {
        $path = $this->request->getPath();
        $method = $this->request->getMethod();

        foreach ($this->routes as $route) {
            if ($this->matchRoute($path, $method, $route)) {
                $this->handleRoute($route, $db);
                return;
            }
        }

        $this->response->error('Route not found', [], 404);
    }

    /**
     * Match route pattern
     */
    protected function matchRoute(string $path, string $method, array $route): bool
    {
        if ($route['method'] !== $method) {
            return false;
        }

        $pattern = preg_replace('/\{[^}]+\}/', '([^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';

        return preg_match($pattern, $path);
    }

    /**
     * Handle matched route
     */
    protected function handleRoute(array $route, Database $db): void
    {
        $controllerName = 'App\\Controllers\\' . ucfirst($route['controller']) . 'Controller';
        
        if (!class_exists($controllerName)) {
            throw new \Exception("Controller not found: {$controllerName}");
        }

        $controller = new $controllerName($db, $this->request, $this->response);
        $action = $route['action'];

        if (!method_exists($controller, $action)) {
            throw new \Exception("Action not found: {$action}");
        }

        call_user_func([$controller, $action]);
    }
}
