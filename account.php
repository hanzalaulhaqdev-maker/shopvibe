<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Get user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Update profile
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $address = sanitize($_POST['address'] ?? '');

    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email';
    } else {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $name, $email, $phone, $address, $user_id);
        if ($stmt->execute()) {
            $success = 'Profile updated successfully';
            $_SESSION['user_name'] = $name;
            $user['name'] = $name;
            $user['email'] = $email;
            $user['phone'] = $phone;
            $user['address'] = $address;
        }
        $stmt->close();
    }
}

// Change password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error = 'All password fields are required';
    } elseif (strlen($new) < 6) {
        $error = 'New password must be at least 6 characters';
    } elseif ($new !== $confirm) {
        $error = 'Passwords do not match';
    } else {
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $hash = $stmt->get_result()->fetch_assoc()['password'];
        $stmt->close();

        if (!password_verify($current, $hash)) {
            $error = 'Current password is incorrect';
        } else {
            $new_hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $new_hash, $user_id);
            $stmt->execute();
            $stmt->close();
            $success = 'Password changed successfully';
        }
    }
}

// Get orders
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$order_stmt->bind_param("i", $user_id);
$order_stmt->execute();
$orders = $order_stmt->get_result();
$order_stmt->close();

include 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <h1 class="fw-bold mb-4">My Account</h1>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-lg-3">
                <div class="account-sidebar bg-white border">
                    <div class="nav flex-column nav-pills" id="account-tabs" role="tablist">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="pill" data-bs-target="#profile" type="button">Profile</button>
                        <button class="nav-link" id="orders-tab" data-bs-toggle="pill" data-bs-target="#orders" type="button">Orders</button>
                        <button class="nav-link" id="password-tab" data-bs-toggle="pill" data-bs-target="#password" type="button">Change Password</button>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="tab-content" id="account-tabs-content">
                    <!-- Profile Tab -->
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4">Profile Information</h5>
                                <form method="POST" action="">
                                    <input type="hidden" name="update_profile" value="1">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Full Name</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo $user['name']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo $user['email']; ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input type="tel" name="phone" class="form-control" value="<?php echo $user['phone'] ?? ''; ?>">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Address</label>
                                            <textarea name="address" class="form-control" rows="2"><?php echo $user['address'] ?? ''; ?></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-dark mt-3">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Orders Tab -->
                    <div class="tab-pane fade" id="orders">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4">Order History</h5>
                                <?php if ($orders->num_rows === 0): ?>
                                <p class="text-muted">No orders yet.</p>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Total</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = $orders->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo $order['order_number']; ?></td>
                                                <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                                <td><?php echo formatPrice($order['total']); ?></td>
                                                <td><span class="badge bg-<?php echo $order['status'] === 'delivered' ? 'success' : ($order['status'] === 'cancelled' ? 'danger' : 'warning'); ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Password Tab -->
                    <div class="tab-pane fade" id="password">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4">Change Password</h5>
                                <form method="POST" action="">
                                    <input type="hidden" name="change_password" value="1">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <label class="form-label">Current Password</label>
                                            <input type="password" name="current_password" class="form-control" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">New Password</label>
                                            <input type="password" name="new_password" class="form-control" required minlength="6">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Confirm New Password</label>
                                            <input type="password" name="confirm_password" class="form-control" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-dark mt-3">Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>