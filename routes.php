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
    

    // Forgot password
    $router->post('/forgot-password', 'Admin\Auth', 'forgotPassword');
    $router->post('/verify-reset-token', 'Admin\Auth', 'verifyResetToken');
    $router->post('/reset-password', 'Admin\Auth', 'resetPassword');
});

//  ADMIN NOTIFICATIONS API 
$router->group('/api/admin/notifications', function($router) {
    $router->get('/', 'Admin\Notification', 'index');
    $router->get('/unread', 'Admin\Notification', 'unread');
    $router->get('/read', 'Admin\Notification', 'read');
    $router->post('/mark-as-read/{id}', 'Admin\Notification', 'markAsRead');
    $router->post('/mark-all-as-read', 'Admin\Notification', 'markAllAsRead');
});

//  ADMIN PRODUCTS API 
$router->group('/api/admin/products', function($router) {
    $router->get('/', 'Admin\Product', 'index');
    $router->get('/next-id', 'Admin\Product', 'getNextProductId');
    $router->post('/store', 'Admin\Product', 'store');
    $router->get('/{id}', 'Admin\Product', 'show');
    $router->post('/update/{id}', 'Admin\Product', 'update');
    $router->post('/delete/{id}', 'Admin\Product', 'destroy');
    
});

