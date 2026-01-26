<?php
// Test session persistence
session_start();

echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
var_dump($_SESSION);

// Set a test value
$_SESSION['test'] = 'value123';
echo "\nAfter setting test: " . $_SESSION['test'] . "\n";
