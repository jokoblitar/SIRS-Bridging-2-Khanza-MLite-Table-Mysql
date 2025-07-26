<?php
$host = 'localhost';
$user = 'root';
$pass = 'pass';
$db = 'sik';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper Unicode support
$conn->set_charset("utf8mb4");
?>
