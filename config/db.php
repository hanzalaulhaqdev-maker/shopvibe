<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'shopvibe';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

session_start();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
?>