<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Auth.php';

Auth::requireAdmin();

$user = Auth::user();
$pageTitle = 'Dashboard';

ob_start();
?>

<div class="wrap">
    <div class="col-md-12">
        <div class="alert alert-success" role="alert">
            <h4 class="alert-heading">Authentication Working!</h4>
            <p>You are successfully logged in as an admin.</p>
            <hr>
            <p class="mb-0">
                <strong>User ID:</strong> <?= $user['id']?><br>
                <strong>Name: </strong> <?= htmlspecialchars($user['name']) ?><br>
                <strong>Email: </strong> <?= htmlspecialchars($user['email']) ?><br>
                <strong>Role: </strong> <?= htmlspecialchars($user['role']) ?><br>
            </p>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Working Papers Dashboard</h5>
                <p class="card-text"></p>
                <p class="text-muted"></p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../views/layouts/admin.php';