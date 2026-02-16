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
    protected array $groupStack = [];
    protected Request $request;
    protected Response $response;

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
        $fullPath = $this->normalizePath(
            $this->getGroupPrefix() . '/' . trim($path, '/')
        );

        $this->routes[] = [
            'method'     => strtoupper($httpMethod),
            'path'       => $fullPath,
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

        $this->response->error('Route not found', 404);
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

        // Convert /users/{id?} → regex
        $pattern = preg_replace_callback(
            '#\{([\w]+)(\?)?\}#',
            function ($matches) {
                $name = $matches[1];
                $optional = isset($matches[2]) && $matches[2] === '?';
                return $optional ? '(?P<' . $name . '>[^/]*)?' : '(?P<' . $name . '>[^/]+)';
            },
            $route['path']
        );


        $pattern = '#^' . $pattern . '$#';

        if (!preg_match($pattern, $path, $matches)) {
            return false;
        }

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

    
        $controller = new $controllerClass(
            $db,
            $this->request,
            $this->response
        );

        if (!method_exists($controller, $action)) {
            throw new Exception(
                "Action '{$action}' not found in {$controllerClass}"
            );
        }

        call_user_func_array([$controller, $action], $params);
    }

    /* =========================
     * Route grouping
     * ========================= */

    public function group(string $prefix, callable $callback): void
    {
        $this->groupStack[] = trim($prefix, '/');

        $callback($this);

        array_pop($this->groupStack);
    }


    protected function getGroupPrefix(): string
    {
        if (empty($this->groupStack)) {
            return '';
        }

        return '/' . implode('/', $this->groupStack);
    }


    /* =========================
     * Helpers
     * ========================= */

    protected function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    protected function resolveController(string $controller): string
    {
        // Allows: 'User' or 'Admin/User'
        $controller = str_replace('/', '\\', $controller);

        return 'App\\Controllers\\' . $controller . 'Controller';
    }
}
