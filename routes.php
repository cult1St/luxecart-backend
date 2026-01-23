<?php

/**
 * Application Routes
 * 
 * Define all application routes here
 */


/// Forgot Password Routes
$router->post('/forgot-password', 'Auth', 'forgotPassword');
$router->post('/verify-reset-token', 'Auth', 'verifyResetToken');
$router->post('/reset-password', 'Auth', 'resetPassword');


/// dashboard route
$router->get('/dashboard', 'Dashboard', 'index');