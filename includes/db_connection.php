<?php
// Database configuration
$host = getenv('DB_HOST') ?: 'localhost'; 
$dbname = getenv('DB_NAME') ?: 'boarding_house_system'; 
$username = getenv('DB_USER') ?: 'root'; 
$password = getenv('DB_PASSWORD') ?: ''; 

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");
