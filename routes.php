<?php

/**
 * Application Routes
 * 
 * Define all application routes here
 */


/// Forgot Password Routes
$router->post('/api/auth/forgot-password', 'User\Auth', 'forgotPassword');
$router->post('/api/auth/verify-reset-token', 'User\Auth', 'verifyResetToken');
$router->post('/api/auth/reset-password', 'User\Auth', 'resetPassword');


/// dashboard route
$router->get('/api/dashboard', 'User\Dashboard', 'index');
// Auth routes - API
$router->post('/api/auth/signup', 'User\Auth', 'signup');
$router->post('/api/auth/login', 'User\Auth', 'login');
$router->post('/api/auth/verify-email', 'User\Auth', 'verifyEmail');
$router->post('/api/auth/resend-code', 'User\Auth', 'resendCode');
$router->post('/api/auth/google', 'User\Auth', 'googleAuth');
$router->post('/api/auth/logout', 'User\Auth', 'logout');
$router->get('/api/auth/me', 'User\Auth', 'me');

// Account routes - API
$router->get('/api/auth/account', 'User\Account', 'index');
$router->post('/api/auth/account/update', 'User\Account', 'update');