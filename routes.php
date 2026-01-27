<?php

/**
 * Application Routes
 * 
 * Define all application routes here
 */


// // Cart routes
// $router->get('/cart', 'Cart', 'index');
// $router->post('/cart/add', 'Cart', 'add');
// $router->post('/cart/update', 'Cart', 'update');
// $router->post('/cart/remove', 'Cart', 'remove');
// $router->post('/cart/clear', 'Cart', 'clear');

// Checkout routes
$router->get('/checkout', 'Checkout', 'index');
$router->post('/checkout/process', 'Checkout', 'process');
$router->get('/order/{id}', 'Order', 'show');

// Auth routes
$router->get('/login', 'Auth', 'loginForm');
$router->post('/login', 'Auth', 'login');
$router->get('/register', 'Auth', 'registerForm');
$router->post('/register', 'Auth', 'register');
$router->post('/logout', 'Auth', 'logout');

// Customer routes
$router->get('/account', 'Account', 'index');
$router->get('/account/orders', 'Account', 'orders');
$router->get('/account/address', 'Account', 'address');
$router->post('/account/update', 'Account', 'update');

// Orders routes
$router->get('/orders/history', 'Order', 'history');


// Admin routes
$router->get('/admin', 'Admin\Dashboard', 'index');
$router->get('/admin/products', 'Admin\Product', 'index');
$router->post('/admin/products/create', 'Admin\Product', 'create');
$router->post('/admin/products/update/{id}', 'Admin\Product', 'update');
$router->post('/admin/products/delete/{id}', 'Admin\Product', 'delete');

$router->get('/admin/orders', 'Admin\Order', 'index');
$router->post('/admin/orders/update/{id}', 'Admin\Order', 'update');

$router->get('/admin/customers', 'Admin\Customer', 'index');
$router->get('/admin/categories', 'Admin\Category', 'index');
$router->get('/admin/reports', 'Admin\Report', 'index');


$router->get('/products', 'Product', 'index');
$router->get('/products/related', 'product', 'related');
$router->get('/products/{id}', 'Product', 'show');

$router->get('/cart', 'Cart', 'index');
$router->post('/cart/add', 'Cart', 'add');
$router->put('/cart/remove', 'Cart', 'remove');
$router->put('/cart/update_quantity', 'Cart', 'updateQuantity');

$router->post('/checkout/shipping', 'Checkout', 'saveShippingInfo');
$router->get('/checkout/shipping', 'Checkout', 'getShippingInfo');
$router->put('/checkout/shipping', 'Checkout', 'updateShippingInfo');







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
