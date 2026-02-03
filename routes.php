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



    /*
    |--------------------------------------------------------------------------
    | Public / Storefront Routes
    |--------------------------------------------------------------------------
    */

    $router->get('/products', 'Product', 'index');
    $router->get('/products/related', 'Product', 'related');
    $router->get('/products/{id}', 'Product', 'show');

    $router->get('/cart', 'Cart', 'index');
    $router->post('/cart/add', 'Cart', 'add');
    $router->put('/cart/remove', 'Cart', 'remove');
    $router->put('/cart/update_quantity', 'Cart', 'updateQuantity');

    $router->post('/checkout/shipping', 'Checkout', 'saveShippingInfo');
    $router->get('/checkout/shipping', 'Checkout', 'getShippingInfo');
    $router->put('/checkout/shipping', 'Checkout', 'updateShippingInfo');

    $router->get('/orders/order-history', 'Order', 'history');

    $router->group('/auth', function ($router) {

        /*
    |--------------------------------------------------------------------------
    | User Auth & Account
    |--------------------------------------------------------------------------
    */

        // Auth
        $router->post('/signup', 'User\Auth', 'signup');
        $router->post('/login', 'User\Auth', 'login');
        $router->post('/logout', 'User\Auth', 'logout');
        $router->get('/me', 'User\Auth', 'me');

        $router->post('/verify-email', 'User\Auth', 'verifyEmail');
        $router->post('/resend-code', 'User\Auth', 'resendCode');
        $router->post('/google', 'User\Auth', 'googleAuth');

        // Forgot password
        $router->post('/forgot-password', 'User\Auth', 'forgotPassword');
        $router->post('/verify-reset-token', 'User\Auth', 'verifyResetToken');
        $router->post('/reset-password', 'User\Auth', 'resetPassword');

        // Account
        $router->get('/account', 'User\Account', 'index');
        $router->post('/account/update', 'User\Account', 'update');
    });

    /*
    |--------------------------------------------------------------------------
    | User Dashboard
    |--------------------------------------------------------------------------
    */

    $router->get('/dashboard', 'User\Dashboard', 'index');

    /*
    \--------------------------------------------------------------------------
    \ Payment Processing 
    \--------------------------------------------------------------------------
    */
    $router->post('/proceed-to-payment', 'User\Payment', 'index');
    $router->post('/verify-payment/{reference?}', 'User\Payment', 'verify');

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    */

    $router->group('/admin', function ($router) {

        /*
        |-------------------------
        | Admin Auth
        |-------------------------
        */

        $router->group('/auth', function ($router) {
            $router->post('/login', 'Admin\Auth', 'login');
            $router->get('/me', 'Admin\Auth', 'me');
            $router->post('/logout', 'Admin\Auth', 'logout');

            // Forgot password
            $router->post('/forgot-password', 'Admin\Auth', 'forgotPassword');
            $router->post('/verify-reset-token', 'Admin\Auth', 'verifyResetToken');
            $router->post('/reset-password', 'Admin\Auth', 'resetPassword');
        });

        /*
        |-------------------------
        | Admin Notifications
        |-------------------------
        */

        $router->group('/notifications', function ($router) {
            $router->get('/', 'Admin\Notification', 'index');
            $router->get('/unread', 'Admin\Notification', 'unread');
            $router->get('/read', 'Admin\Notification', 'read');
            $router->post('/mark-as-read/{id}', 'Admin\Notification', 'markAsRead');
            $router->post('/mark-all-as-read', 'Admin\Notification', 'markAllAsRead');
        });

        /*
        |-------------------------
        | Admin Products
        |-------------------------
        */

        $router->group('/products', function ($router) {
            $router->get('/', 'Admin\Product', 'index');
            $router->get('/next-id', 'Admin\Product', 'getNextProductId');
            $router->post('/store', 'Admin\Product', 'store');
            $router->get('/{id}', 'Admin\Product', 'show');
            $router->post('/update/{id}', 'Admin\Product', 'update');
            $router->post('/delete/{id}', 'Admin\Product', 'destroy');
        });
    });
});
