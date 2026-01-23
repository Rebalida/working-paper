<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Auth.php';

Auth::init();

// If already logged in, redirect to dashboard
if (Auth::check()) {
    header('Location: /working-paper/public/admin/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (Auth::login($email, $password)) {
        header('Location: /working-paper/public/admin/dashboard.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}

$pageTitle = 'Login';

ob_start();
?>

<div class="d-flex align-items-center justify-content-center min-vh-100" style="">
    <div class="card shadow-lg" style="max-width: 400px; width: 100%; border-radius: 15px;">
        <div class="card-header text-white text-center py-4" style="background: #FBAC1B; border-radius: 15px 15px 0 0;">
            <h2 class="mb-0">Working Paper</h2>
            <p class="mb-0 mt-2">Working Paper System</p>
        </div>
        <div class="card-body p-4">
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
                
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required autofocus>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-primary btn-lg">Login</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../views/layouts/guest.php';