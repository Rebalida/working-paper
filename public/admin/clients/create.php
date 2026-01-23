<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';

Auth::requireAdmin();

$user = Auth::user();
$pageTitle = 'Add New Client';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../../../app/models/Client.php';

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($name) || empty($email)) {
        $error = 'Name and email are required';
    } else {
        $clientModel = new Client();

        // Check if email already exists
        $existing = $clientModel->findByEmail($email);
        if ($existing) {
            $error = 'A client with this email already exists';
        } else {
            try {
                $clientModel->create([
                    'name' => $name,
                    'email' => $email
                ]);
                $success = 'Client added successfully!';

                // Clear form
                $name = '';
                $email = '';
            } catch (Exception $e) {
                $error = 'Error: ' . $e->getMessage();
            }
        }
    }
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Add New Client</h2>
            <a href="/working-paper/public/admin/working-papers/create.php" class="btn btn-secondary">← Back</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="name" class="form-label">Client Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($name ?? '') ?>" placeholder="e.g. Peter Black" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($email ?? '') ?>" placeholder="e.g. peter@example.com" required>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="/working-paper/public/admin/working-papers/create.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Add Client</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../../views/layouts/admin.php';