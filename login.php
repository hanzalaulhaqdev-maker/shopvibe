<?php
require_once 'config/db.php';
require_once 'includes/functions.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $conn->prepare("SELECT id, name, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            redirect('index.php');
        } else {
            $error = 'Invalid email or password';
        }
    }
}

include 'includes/header.php';
?>

<section class="py-5">
    <div class="container">
        <div class="auth-card card">
            <div class="card-body">
                <h3 class="fw-bold text-center mb-4">Welcome Back</h3>
                <p class="text-muted text-center mb-4">Sign in to your ShopVibe account</p>

                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" action="" id="login-form">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required minlength="6">
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="#" class="text-dark small">Forgot password?</a>
                    </div>
                    <button type="submit" class="btn btn-dark w-100">Sign In</button>
                </form>

                <p class="text-center mt-4 mb-0">Don't have an account? <a href="register.php" class="fw-bold">Create one</a></p>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>