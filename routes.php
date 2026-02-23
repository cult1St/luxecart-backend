<?php

/**
 * Application Routes
 *
 * Define all application routes here
 */



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

$router->group('/api', function ($router) {

    $router->group('/auth', function ($router) {
        $router->post('/register', 'User\Auth', 'register');
        $router->post('/verify-email', 'User\Auth', 'verifyEmail');
        $router->post('/login', 'User\Auth', 'login');
    });
});




