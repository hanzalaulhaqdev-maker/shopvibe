<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['applied_coupon']);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false]);
}
