<?php
// Database connection settings
$host = 'localhost'; // Change if not running locally
$user = 'root';      // Change to your MySQL username
$pass = '';          // Change to your MySQL password
$db   = 'iebc_voting'; // Change to your database name

// Create connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Usage: include this file and use $conn for queries
?> 