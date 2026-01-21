<?php

/**
 * Application Entry Point
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use Core\Database;
use Core\Request;
use Core\Response;
use Core\Router;

try {
    // Initialize core components
    $config = require BASE_PATH . '/config/app.php';
    $db = new Database($config['database']);
    $request = new Request();
    $response = new Response();
    $router = new Router($request, $response);

    // Define application routes
    require_once BASE_PATH . '/routes.php';

    // Dispatch request
    $router->dispatch($db);

} catch (\Throwable $e) {
    http_response_code(500);
    
    if (env('APP_DEBUG')) {
        echo '<pre>';
        echo $e->getMessage() . "\n\n";
        echo $e->getTraceAsString();
        echo '</pre>';
    } else {
        echo 'Something went wrong. Please try again later.';
    }
}
