<?php

namespace Core;

/**
 * HTTP Response Handler
 * 
 * Provides utilities for sending responses
 */
class Response
{
    protected int $statusCode = 200;
    protected array $headers = [];
    protected string $content = '';

    /**
     * Set status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set header
     */
    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Send JSON response
     */
    public function json(array $data, int $statusCode = 200): void
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');

        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Send success response
     */
    public function success(array | object | null $data, string $message = 'Success', int $statusCode = 200): void
    {
        $this->json([
            'success' => true,
            'message' => $message,
            'data' => $data ?? [],
        ], $statusCode);
        exit;
    }

    /**
     * Send error response
     */
    public function error(string $message = 'Error', int $statusCode = 400, array $data = []): void
    {
        $this->json([
            'success' => false,
            'message' => $message,
            'errors' => $data,
        ], $statusCode);
        exit;
    }

    /**
     * Redirect
     */
    public function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Send file download
     */
    public function download(string $filePath, string $fileName): void
    {
        if (!file_exists($filePath)) {
            $this->error('File not found', 404);
        }

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));

        readfile($filePath);
        exit;
    }

    /**
     * Send HTML view
     */
    public function view(string $viewName, array $data = []): void
    {
        extract($data);
        $viewPath = BASE_PATH . '/app/views/' . str_replace('.', '/', $viewName) . '.php';

        if (!file_exists($viewPath)) {
            throw new \Exception("View not found: {$viewName}");
        }

        http_response_code($this->statusCode);
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        include $viewPath;
        exit;
    }

    /**
     * Set cookie
     */
    public function cookie(
        string $name,
        string $value,
        int $expires,
        string $path = '/',
        bool $httpOnly = true,
        string $sameSite = 'Lax',
        bool $secure = false
    ): self {
        setcookie($name, $value, [
            'expires' => $expires,
            'path' => $path,
            'httponly' => $httpOnly,
            'samesite' => $sameSite,
            'secure' => $secure,
        ]);

        return $this;
    }
}
