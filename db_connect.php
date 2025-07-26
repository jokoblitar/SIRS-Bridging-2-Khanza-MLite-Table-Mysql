<?php
$host = '192.168.168.8';
$user = 'medik1';
$pass = 'mk1';
$db = 'sik';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for proper Unicode support
$conn->set_charset("utf8mb4");
?>