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
    protected array $cookies;
    protected array $body = [];

    public function __construct()
    {
        $this->get     = $_GET;
        $this->post    = $_POST;
        $this->files   = $_FILES;
        $this->server  = $_SERVER;
        $this->headers = getallheaders();
        $this->cookies = $_COOKIE;

        $this->parseRawBody();
    }

    protected function parseRawBody(): void
    {
        $method = $this->getMethod();
        $raw = file_get_contents('php://input');
        if (!$raw) {
            return;
        }

        // Try JSON first
        $json = json_decode($raw, true);
        if (is_array($json)) {
            $this->body = $json;
            return;
        }

        // Fallback: form-encoded
        parse_str($raw, $parsed);
        if (is_array($parsed)) {
            $this->body = $parsed;
        }
    }

    /**
     * Get request method
     */
    public function getMethod(): string
    {
        return strtoupper($this->server['REQUEST_METHOD'] ?? 'GET');
    }

    public function isGet(): bool
    {
        return $this->getMethod() === 'GET';
    }

    public function isPost(): bool
    {
        return $this->getMethod() === 'POST';
    }

    /**
     * Get POST data (form or JSON)
     */
    public function post(?string $key = null, $default = null)
    {
        // Merge form POST and JSON data
        $postData = array_merge($this->post, $this->body);
        
        if ($key === null) {
            return $postData;
        }
        return $postData[$key] ?? $default;
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
     * Get all input (GET + POST + BODY)
     */
    public function json(?string $key = null, $default = null)
    {
        return array_merge($this->get, $this->post, $this->body);
    }

    /**
     * Get all input (GET + POST + JSON)
     */
    public function all(): array
    {
        return array_merge($this->get, $this->post, $this->body);
    }

    /**
     * Get specific input from all sources (GET + POST + JSON)
     */
    public function input(string $key, $default = null)
    {
        return $this->all()[$key] ?? $default;
    }

    /**
     * Check if request has JSON content
     */
    public function isJson(): bool
    {
        return !empty($this->json);
    }

    /**
     * Get content type
     */
    public function getContentType(): string
    {
        return $this->headers['Content-Type'] ?? 'text/html';
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
        // Try exact match first
        if (isset($this->headers[$key])) {
            return $this->headers[$key];
        }
        
        // Try case-insensitive match
        foreach ($this->headers as $headerKey => $headerValue) {
            if (strtolower($headerKey) === strtolower($key)) {
                return $headerValue;
            }
        }
        
        
         // 2. Fallback to $_SERVER (VERY IMPORTANT for Authorization)
        

         $serverKey = 'HTTP_' . str_replace('-', '_', strtoupper($key));

         if (isset($this->server[$serverKey])) {
             return $this->server[$serverKey];
         }
         return $default;

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

    /**
     * Get cookie value
     */
    public function cookie(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->cookies;
        }

        return $this->cookies[$key] ?? $default;
    }
}
