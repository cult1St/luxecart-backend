<?php
require_once 'bootstrap.php';

try {
    $sql = require 'database/schema.php';
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME'),
        env('DB_USER'),
        env('DB_PASSWORD')
    );

    $pdo->exec($sql);
    echo "Database schema imported successfully!\n";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
