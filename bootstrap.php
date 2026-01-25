<?php

/**
 * Bootstrap Application
 * 
 * This file initializes the application and loads all configuration
 */

// Define base path
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

// Load environment variables
if (file_exists(BASE_PATH . '/.env')) {
    $env = parse_ini_file(BASE_PATH . '/.env');
    foreach ($env as $key => $value) {
        putenv("{$key}={$value}");
    }
}

// Load composer autoloader
require_once BASE_PATH . '/vendor/autoload.php';

// Load global helper functions
require_once BASE_PATH . '/helpers/functions.php';

// Start session
session_start();

// Set timezone
date_default_timezone_set(env('APP_TIMEZONE', 'UTC'));

// Error handling
if (env('APP_DEBUG', false)) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_PRODUCTION);
    ini_set('display_errors', 0);
}

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $logFile = BASE_PATH . '/storage/logs/' . date('Y-m-d') . '.log';
    $message = "[" . date('H:i:s') . "] Error ($errno): $errstr in $errfile on line $errline" . PHP_EOL;
    
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $message, FILE_APPEND);
    
    if (env('APP_DEBUG')) {
        echo $message;
    }
});

// Initialize application container
return new \stdClass();
