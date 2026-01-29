<?php

/**
 * Application Routes
 * 
 * Define all application routes here
 */


// USER AUTH API
$router->group('/api/auth', function($router) {

    // Auth
    $router->post('/signup', 'User\Auth', 'signup');
    $router->post('/login', 'User\Auth', 'login');
    $router->post('/verify-email', 'User\Auth', 'verifyEmail');
    $router->post('/resend-code', 'User\Auth', 'resendCode');
    $router->post('/google', 'User\Auth', 'googleAuth');
    $router->post('/logout', 'User\Auth', 'logout');
    $router->get('/me', 'User\Auth', 'me');

    // Forgot password
    $router->post('/forgot-password', 'User\Auth', 'forgotPassword');
    $router->post('/verify-reset-token', 'User\Auth', 'verifyResetToken');
    $router->post('/reset-password', 'User\Auth', 'resetPassword');

    // Account
    $router->get('/account', 'User\Account', 'index');
    $router->post('/account/update', 'User\Account', 'update');
});


// User DASHBOARD API 
$router->group('/api', function($router) {
    $router->get('/dashboard', 'User\Dashboard', 'index');
});


//  ADMIN AUTH API 
$router->group('/api/admin/auth', function($router) {
    $router->post('/login', 'Admin\Auth', 'login');
    $router->get('/me', 'Admin\Auth', 'me');
    $router->post('/logout', 'Admin\Auth', 'logout');
});

// ADMIN ORDERS API
$router->group('/api/admin/orders', function($router) {
    $router->get('', 'Admin\Order', 'index');
    $router->get('/stats', 'Admin\Order', 'stats');
    $router->get('/search', 'Admin\Order', 'search');
    $router->get('/:id', 'Admin\Order', 'show');
    $router->get('/:id/items', 'Admin\Order', 'items');
    $router->put('/:id/status', 'Admin\Order', 'updateStatus');
    $router->post('/:id/cancel', 'Admin\Order', 'cancel');
});
