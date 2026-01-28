<?php

namespace Core;

use Core\Database;
use Core\Request;
use Core\Response;
use Exception;

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
    protected string $groupPrefix = '';

    public function __construct(Request $request, Response $response)
    {
        $this->request  = $request;
        $this->response = $response;
    }

    /* =========================
     * Route registration
     * ========================= */

    public function get(string $path, string $controller, string $method = 'index'): void
    {
        $this->registerRoute('GET', $path, $controller, $method);
    }

    public function post(string $path, string $controller, string $method = 'store'): void
    {
        $this->registerRoute('POST', $path, $controller, $method);
    }

    public function put(string $path, string $controller, string $method = 'update'): void
    {
        $this->registerRoute('PUT', $path, $controller, $method);
    }

    public function delete(string $path, string $controller, string $method = 'destroy'): void
    {
        $this->registerRoute('DELETE', $path, $controller, $method);
    }

    protected function registerRoute(
        string $httpMethod,
        string $path,
        string $controller,
        string $method
    ): void {
        $this->routes[] = [
            'method'     => strtoupper($httpMethod),
            'path'       => $this->normalizePath($this->groupPrefix .$path),
            'controller' => $controller,
            'action'     => $method,
        ];
    }

    /* =========================
     * Dispatching
     * ========================= */

    public function dispatch(Database $db): void
    {
        $path   = $this->normalizePath($this->request->getPath());
        $method = strtoupper($this->request->getMethod());

        foreach ($this->routes as $route) {
            $params = [];

            if ($this->matchRoute($path, $method, $route, $params)) {
                $this->handleRoute($route, $params, $db);
                return;
            }
        }

        $this->response->error('Route not found', [], 404);
    }

    /* =========================
     * Route matching
     * ========================= */

    protected function matchRoute(
        string $path,
        string $method,
        array $route,
        array &$params
    ): bool {
        if ($route['method'] !== $method) {
            return false;
        }

        // Convert /users/{id} → regex
        $pattern = preg_replace('#\{([\w]+)\}#', '(?P<$1>[^/]+)', $route['path']);
        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $path, $matches)) {
            return false;
        }

        // Extract named parameters
        foreach ($matches as $key => $value) {
            if (!is_int($key)) {
                $params[$key] = $value;
            }
        }

        return true;
    }

    /* =========================
     * Route handling
     * ========================= */

    protected function handleRoute(array $route, array $params, Database $db): void
    {
        $controllerClass = $this->resolveController($route['controller']);
        $action          = $route['action'];

        if (!class_exists($controllerClass)) {
            throw new Exception("Controller not found: {$controllerClass}");
        }

        $controller = new $controllerClass($db, $this->request, $this->response);

        if (!method_exists($controller, $action)) {
            throw new Exception("Action '{$action}' not found in {$controllerClass}");
        }

        call_user_func_array([$controller, $action], $params);
    }

    /* =========================
     * Helpers
     * ========================= */

    protected function normalizePath(string $path): string
    {
        $path = trim($path);
        $path = rtrim($path, '/');
        return $path === '' ? '/' : $path;
    }

    protected function resolveController(string $controller): string
    {
        // Allows: 'User' or 'Admin/User'
        $controller = str_replace('/', '\\', $controller);

        return 'App\\Controllers\\' . $controller . 'Controller';
    }
     /* =========================
     * Route grouping
     * ========================= */

    public function group(string $prefix, callable $callback): void
    {
        // To be implemented: group routing functionality
        $prefix = $this->normalizePath($prefix);
        $previousPrefix = $this->groupPrefix;
        $this->groupPrefix = $previousPrefix . $prefix;
        $callback($this);
        $this->groupPrefix = $previousPrefix;   

    }
}
