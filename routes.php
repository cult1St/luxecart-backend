<?php

/**
 * Application Routes
 * 
 * Define all application routes here
 */


/// Forgot Password Routes
$router->post('/frisan/api/auth/forgot-password', 'User\Auth', 'forgotPassword');
$router->post('/frisan/api/auth/verify-reset-token', 'User\Auth', 'verifyResetToken');
$router->post('/frisan/api/auth/reset-password', 'User\Auth', 'resetPassword');


/// dashboard route
$router->get('/frisan/api/dashboard', 'User\Dashboard', 'index');