<?php

/**
 * Application Routes
 * 
 * Define all application routes here
 */

// Frontend routes
$router->get('/', 'Home', 'index');
$router->get('/shop', 'Shop', 'index');
$router->get('/product/{id}', 'Product', 'show');
$router->get('/category/{slug}', 'Category', 'show');

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







