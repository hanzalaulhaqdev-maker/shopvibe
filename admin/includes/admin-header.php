<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ShopVibe Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-wrapper">
    <?php include 'admin-sidebar.php'; ?>
        <div class="admin-main">
            <div class="admin-header">
                <button class="btn btn-sm btn-outline-dark d-lg-none" id="sidebar-toggle">
                    <i class="bi bi-list"></i>
                </button>
                <div class="d-flex align-items-center gap-3">
                    <span class="small text-muted">Welcome, <?php echo $_SESSION['admin_name'] ?? 'Admin'; ?></span>
                    <a href="logout.php" class="btn btn-sm btn-outline-danger"><i class="bi bi-box-arrow-right"></i></a>
                </div>
            </div>
            <div class="admin-content">