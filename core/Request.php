<?php

namespace Core;

/**
 * HTTP Request Handler
 * 
 * Provides easy access to request data
 */
class Request
{
    protected array $get;
    protected array $post;
    protected array $files;
    protected array $server;
    protected array $headers;
    protected array $json = [];

    public function __construct()
    {
        $this->get = $_GET;
        $this->post = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
        // getallheaders() is not available in CLI mode
        $this->headers = function_exists('getallheaders') ? getallheaders() : [];
        
        // Parse JSON body if Content-Type is application/json
        $contentType = $this->header('Content-Type', '');
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            if (!empty($input)) {
                $this->json = json_decode($input, true) ?? [];
            }
        }
    }

    /**
     * Get request method
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Get POST data
     */
    public function post(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    /**
     * Get GET data
     */
    public function get(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    /**
     * Get all input (GET + POST + JSON)
     */
    public function all(): array
    {
        // If JSON data exists, merge it with GET and POST
        if (!empty($this->json)) {
            return array_merge($this->get, $this->post, $this->json);
        }
        return array_merge($this->get, $this->post);
    }

    /**
     * Get specific input
     */
    public function input(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Get file
     */
    public function file(string $key)
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get URI
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Get path
     */
    public function getPath(): string
    {
        $path = parse_url($this->getUri(), PHP_URL_PATH);
        $path = $path ?? '/';
        
        // Remove base directory from path if it exists
        // e.g., /frisan/api/auth/login becomes /api/auth/login
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        // Get the directory containing the public folder (e.g., /frisan from /frisan/public/index.php)
        $baseDir = dirname(dirname($scriptName));
        
        if ($baseDir !== '/' && $baseDir !== '' && strpos($path, $baseDir) === 0) {
            $path = substr($path, strlen($baseDir));
        }
        
        return $path ?: '/';
    }

    /**
     * Get header
     */
    public function header(string $key, $default = null)
    {
        return $this->headers[$key] ?? $default;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return strtolower($this->header('X-Requested-With') ?? '') === 'xmlhttprequest';
    }

    /**
     * Get remote IP
     */
    public function getIp(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
