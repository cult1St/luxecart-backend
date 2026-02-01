<?php

/**
 * Helper Functions
 * 
 * Global utility functions
 */

if (!function_exists('dd')) {
    /**
     * Dump and die
     */
    function dd($data)
    {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
        exit;
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable
     */
    function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: $default;
    }
}

if (!function_exists('route')) {
    /**
     * Generate URL
     */
    function route(string $path = '/', array $params = []): string
    {
        $url = BASE_PATH . $path;
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $url = str_replace('{' . $key . '}', $value, $url);
            }
        }
        return $url;
    }
}

if (!function_exists('asset')) {
    /**
     * Get asset URL
     */
    function asset(string $path): string
    {
        return route('/assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('flash')) {
    /**
     * Set flash message
     */
    function flash(string $key, $value): void
    {
        $_SESSION['flash'][$key] = $value;
    }
}

if (!function_exists('getFlash')) {
    /**
     * Get flash message
     */
    function getFlash(string $key, $default = null)
    {
        $value = $_SESSION['flash'][$key] ?? $default;
        unset($_SESSION['flash'][$key]);
        return $value;
    }
}

if (!function_exists('hasFlash')) {
    /**
     * Check if flash message exists
     */
    function hasFlash(string $key): bool
    {
        return isset($_SESSION['flash'][$key]);
    }
}

if (!function_exists('validate')) {
    /**
     * Validate email
     */
    function validate(string $type, $value): bool
    {
        return match ($type) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'ip' => filter_var($value, FILTER_VALIDATE_IP) !== false,
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'float' => filter_var($value, FILTER_VALIDATE_FLOAT) !== false,
            default => false,
        };
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize input
     */
    function sanitize(string $type, $value)
    {
        return match ($type) {
            'email' => filter_var($value, FILTER_SANITIZE_EMAIL),
            'url' => filter_var($value, FILTER_SANITIZE_URL),
            'string' => filter_var($value, FILTER_SANITIZE_STRING),
            'int' => filter_var($value, FILTER_SANITIZE_NUMBER_INT),
            'float' => filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT),
            default => $value,
        };
    }
}

if (!function_exists('abort')) {
    /**
     * Abort with error
     */
    function abort(int $code, string $message = '')
    {
        http_response_code($code);
        die($message ?: "Error {$code}");
    }
}

if (!function_exists('now')) {
    /**
     * Get current timestamp
     */
    function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}

if (!function_exists('today')) {
    /**
     * Get today's date
     */
    function today(): string
    {
        return date('Y-m-d');
    }
}


if (!function_exists('error_logger')) {
    /**
     * Log a message
     */
    function error_logger(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');

        $logMessage = "[{$timestamp}] [" . strtoupper($level) . "] {$message}";

        if (!empty($context)) {
            $logMessage .= " Context: " . json_encode($context, JSON_UNESCAPED_UNICODE);
        }

        $logMessage .= PHP_EOL;

        $logFile = BASE_PATH . '/storage/logs/' . date('Y-m-d') . '.log';

        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
