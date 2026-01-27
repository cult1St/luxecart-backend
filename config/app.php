<?php

/**
 * Application Configuration
 */

return [

    'app' => [
        'name'     => env('APP_NAME', 'Frisan'),
        'url'      => env('APP_URL', 'http://localhost'),
        'timezone' => env('APP_TIMEZONE', 'UTC'),
        'debug'    => env('APP_DEBUG', false),
    ],

    'database' => [
        'driver'   => env('DB_CONNECTION', 'mysql'),
        'host'     => env('DB_HOST', '127.0.0.1'),
        'port'     => env('DB_PORT', 3306),
        'dbname' => env('DB_DATABASE', 'frisan'),
        'user' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset'  => 'utf8mb4',
    ],

    'mail' => [
        'driver' => env('MAIL_DRIVER', 'smtp'),
        'host'   => env('MAIL_HOST', 'smtp.mailtrap.io'),
        'port'   => env('MAIL_PORT', 465),
        'username' => env('MAIL_USERNAME', ''),
        'password' => env('MAIL_PASSWORD', ''),
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@frisan.com'),
            'name'    => env('MAIL_FROM_NAME', 'frisan'),
        ],
    ],

    'payment' => [
        'providers' => [
            'paystack' => [
                'public_key' => env('PAYSTACK_PUBLIC_KEY', ''),
                'secret_key' => env('PAYSTACK_SECRET_KEY', ''),
            ],
            'stripe' => [
                'public_key' => env('STRIPE_PUBLIC_KEY', ''),
                'secret_key' => env('STRIPE_SECRET_KEY', ''),
            ],
        ],
    ],

    'shop' => [
        'currency' => env('SHOP_CURRENCY', 'USD'),
        'tax_rate' => env('SHOP_TAX_RATE', 0.1),
        'free_shipping_threshold' => env('FREE_SHIPPING_THRESHOLD', 100),
    ],

    'pagination' => [
        'per_page' => env('PAGINATION_PER_PAGE', 15),
    ],
];
