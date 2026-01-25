<?php

/**
 * Frisan Database Schema
 * Use this in your schema.php file for database migrations
 */

return [
    'tables' => [
        'admin_users' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'name' => 'varchar(255) NOT NULL',
            'email' => 'varchar(255) NOT NULL UNIQUE',
            'password' => 'varchar(255) NOT NULL',
            'role' => "enum('admin','manager','staff') DEFAULT 'staff'",
            'is_active' => 'tinyint(1) DEFAULT 1',
            'last_login_at' => 'timestamp NULL',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'email_2' => ['email'],
                'is_active' => ['is_active'],
            ],
        ],
        
        'api_tokens' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'bigint UNSIGNED NOT NULL',
            'token' => 'longtext NOT NULL',
            'ip_address' => 'varchar(255)',
            'expires_at' => 'timestamp NULL',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'user_id' => ['user_id'],
            ],
            'foreign_keys' => [
                'user_id' => 'users(id) ON DELETE CASCADE',
            ],
        ],
        
        'countries' => [
            'id' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'name' => 'varchar(100) NOT NULL UNIQUE',
        ],
        
        'states' => [
            'id' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'country_id' => 'int NOT NULL',
            'name' => 'varchar(100) NOT NULL',
            'indexes' => [
                'country_id' => ['country_id'],
            ],
            'foreign_keys' => [
                'country_id' => 'countries(id) ON DELETE CASCADE',
            ],
        ],
        
        'cities' => [
            'id' => 'int NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'state_id' => 'int NOT NULL',
            'name' => 'varchar(100) NOT NULL',
            'indexes' => [
                'state_id' => ['state_id'],
            ],
            'foreign_keys' => [
                'state_id' => 'states(id) ON DELETE CASCADE',
            ],
        ],
        
        'users' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'name' => 'varchar(100) NOT NULL',
            'email' => 'varchar(150) NOT NULL UNIQUE',
            'password' => 'varchar(255)',
            'google_id' => 'varchar(255) UNIQUE',
            'phone' => 'varchar(20)',
            'is_active' => 'tinyint(1) DEFAULT 1',
            'is_verified' => 'tinyint(1) DEFAULT 0',
            'last_login_at' => 'timestamp NULL',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'email_2' => ['email'],
                'is_active' => ['is_active'],
            ],
        ],
        
        'customer_addresses' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'bigint UNSIGNED NOT NULL',
            'type' => "enum('shipping','billing') DEFAULT 'shipping'",
            'address' => 'longtext NOT NULL',
            'city_id' => 'int NOT NULL',
            'state_id' => 'int NOT NULL',
            'country_id' => 'int NOT NULL',
            'postal_code' => 'varchar(20)',
            'is_default' => 'tinyint(1) DEFAULT 0',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'user_id' => ['user_id'],
                'city_id' => ['city_id'],
                'state_id' => ['state_id'],
                'country_id' => ['country_id'],
            ],
            'foreign_keys' => [
                'user_id' => 'users(id) ON DELETE CASCADE',
                'city_id' => 'cities(id)',
                'state_id' => 'states(id)',
                'country_id' => 'countries(id)',
            ],
        ],
        
        'products' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'name' => 'varchar(150) NOT NULL',
            'slug' => 'varchar(255) NOT NULL UNIQUE',
            'description' => 'longtext',
            'price' => 'decimal(10,2) NOT NULL',
            'stock_quantity' => 'int UNSIGNED DEFAULT 0',
            'sold' => 'int UNSIGNED DEFAULT 0',
            'images' => 'longtext',
            'is_active' => 'tinyint(1) DEFAULT 1',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'slug_2' => ['slug'],
            ],
            'fulltext' => [
                'name' => ['name', 'description'],
            ],
        ],
        
        'orders' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'bigint UNSIGNED NOT NULL',
            'order_id' => 'varchar(50) NOT NULL UNIQUE',
            'status' => "enum('pending','paid','processing','shipped','delivered','cancelled') DEFAULT 'pending'",
            'total_amount' => 'decimal(10,2) NOT NULL',
            'tax_amount' => 'decimal(10,2) DEFAULT 0.00',
            'shipping_amount' => 'decimal(10,2) DEFAULT 0.00',
            'discount_amount' => 'decimal(10,2) DEFAULT 0.00',
            'final_amount' => 'decimal(10,2) NOT NULL',
            'delivered_at' => 'timestamp NULL',
            'notes' => 'longtext',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'user_id' => ['user_id'],
                'status' => ['status'],
                'order_id_2' => ['order_id'],
            ],
            'foreign_keys' => [
                'user_id' => 'users(id) ON DELETE RESTRICT',
            ],
        ],
        
        'order_items' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'order_id' => 'bigint UNSIGNED NOT NULL',
            'product_id' => 'bigint UNSIGNED NOT NULL',
            'quantity' => 'int UNSIGNED NOT NULL',
            'price' => 'decimal(10,2) NOT NULL',
            'subtotal' => 'decimal(10,2) NOT NULL',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'order_id' => ['order_id'],
                'product_id' => ['product_id'],
            ],
            'foreign_keys' => [
                'order_id' => 'orders(id) ON DELETE CASCADE',
                'product_id' => 'products(id) ON DELETE RESTRICT',
            ],
        ],
        
        'payments' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'order_id' => 'bigint UNSIGNED NOT NULL',
            'user_id' => 'bigint UNSIGNED NOT NULL',
            'amount' => 'decimal(10,2) NOT NULL',
            'payment_method' => 'varchar(50) NOT NULL',
            'transaction_id' => 'varchar(255) UNIQUE',
            'status' => "enum('pending','success','failed','cancelled') DEFAULT 'pending'",
            'gateway_response' => 'longtext',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'order_id' => ['order_id'],
                'user_id' => ['user_id'],
                'status' => ['status'],
            ],
            'foreign_keys' => [
                'order_id' => 'orders(id) ON DELETE CASCADE',
                'user_id' => 'users(id) ON DELETE CASCADE',
            ],
        ],
        
        'reset_requests' => [
            'id' => 'bigint UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY',
            'user_id' => 'bigint UNSIGNED NOT NULL',
            'request_link' => 'varchar(255) NOT NULL UNIQUE',
            'expires_at' => 'timestamp NOT NULL',
            'created_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP',
            'updated_at' => 'timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
            'indexes' => [
                'user_id' => ['user_id'],
            ],
            'foreign_keys' => [
                'user_id' => 'users(id) ON DELETE CASCADE',
            ],
        ],
    ],
    
    'seed_data' => [
        'countries' => [
            ['id' => 1, 'name' => 'Nigeria'],
        ],
        
        'states' => [
            ['id' => 1, 'country_id' => 1, 'name' => 'Abia'],
            ['id' => 2, 'country_id' => 1, 'name' => 'Adamawa'],
            ['id' => 3, 'country_id' => 1, 'name' => 'Akwa Ibom'],
            ['id' => 4, 'country_id' => 1, 'name' => 'Anambra'],
            ['id' => 5, 'country_id' => 1, 'name' => 'Bauchi'],
            ['id' => 6, 'country_id' => 1, 'name' => 'Bayelsa'],
            ['id' => 7, 'country_id' => 1, 'name' => 'Benue'],
            ['id' => 8, 'country_id' => 1, 'name' => 'Borno'],
            ['id' => 9, 'country_id' => 1, 'name' => 'Cross River'],
            ['id' => 10, 'country_id' => 1, 'name' => 'Delta'],
            ['id' => 11, 'country_id' => 1, 'name' => 'Ebonyi'],
            ['id' => 12, 'country_id' => 1, 'name' => 'Edo'],
            ['id' => 13, 'country_id' => 1, 'name' => 'Ekiti'],
            ['id' => 14, 'country_id' => 1, 'name' => 'Enugu'],
            ['id' => 15, 'country_id' => 1, 'name' => 'Gombe'],
            ['id' => 16, 'country_id' => 1, 'name' => 'Imo'],
            ['id' => 17, 'country_id' => 1, 'name' => 'Jigawa'],
            ['id' => 18, 'country_id' => 1, 'name' => 'Kaduna'],
            ['id' => 19, 'country_id' => 1, 'name' => 'Kano'],
            ['id' => 20, 'country_id' => 1, 'name' => 'Katsina'],
            ['id' => 21, 'country_id' => 1, 'name' => 'Kebbi'],
            ['id' => 22, 'country_id' => 1, 'name' => 'Kogi'],
            ['id' => 23, 'country_id' => 1, 'name' => 'Kwara'],
            ['id' => 24, 'country_id' => 1, 'name' => 'Lagos'],
            ['id' => 25, 'country_id' => 1, 'name' => 'Nasarawa'],
            ['id' => 26, 'country_id' => 1, 'name' => 'Niger'],
            ['id' => 27, 'country_id' => 1, 'name' => 'Ogun'],
            ['id' => 28, 'country_id' => 1, 'name' => 'Ondo'],
            ['id' => 29, 'country_id' => 1, 'name' => 'Osun'],
            ['id' => 30, 'country_id' => 1, 'name' => 'Oyo'],
            ['id' => 31, 'country_id' => 1, 'name' => 'Plateau'],
            ['id' => 32, 'country_id' => 1, 'name' => 'Rivers'],
            ['id' => 33, 'country_id' => 1, 'name' => 'Sokoto'],
            ['id' => 34, 'country_id' => 1, 'name' => 'Taraba'],
            ['id' => 35, 'country_id' => 1, 'name' => 'Yobe'],
            ['id' => 36, 'country_id' => 1, 'name' => 'Zamfara'],
            ['id' => 37, 'country_id' => 1, 'name' => 'FCT'],
            ['id' => 38, 'country_id' => 1, 'name' => 'Foreign'],
        ],
        
        'users' => [
            [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@gmail.com',
                'password' => 'pass123',
                'phone' => '0803556677',
                'is_active' => 1,
                'is_verified' => 0,
                'created_at' => '2026-01-22 17:21:09',
                'updated_at' => '2026-01-22 17:21:09',
            ],
        ],
    ],
];
?>