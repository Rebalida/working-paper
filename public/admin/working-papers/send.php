<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../../app/models/Client.php';
require_once __DIR__ . '/../../../app/models/AccessToken.php';
require_once __DIR__ . '/../../../app/models/StatusHistory.php';
require_once __DIR__ . '/../../../app/EmailService.php';

Auth::requireAdmin();

$user = Auth::user();

// Get working paper ID
$wpId = $_GET['id'] ?? null;
if (!$wpId) {
    header('Location: /working-paper/public/admin/dashboard.php');
    exit;
}

// Get working paper
$wpModel = new WorkingPaper();
$wp = $wpModel->find($wpId);

if (!$wp) {
    header('Location: /working-paper/public/admin/dashboard.php');
    exit;
}

// Check if already sent
if ($wp['status'] !== 'draft') {
    header('Location: /working-paper/public/admin/working-papers/view.php?id=' . $wpId . '&error=already_sent');
    exit;
}

// Get client
$clientModel = new Client();
$client = $clientModel->find($wp['client_id']);

$pageTitle = 'Send to Client';

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Generate access token
        $tokenModel = new AccessToken();
        $token = $tokenModel->generateToken($wpId, 1); // 1 hour expiry

        // Update working paper status
        $wpModel->updateStatus($wpId, 'sent', $user['id']);

        // Send email
        $emailService = new EmailService();
        $emailSent = $emailService->sendInitialLink(
            $client['email'],
            $client['name'],
            $wp,
            $token
        );

        if ($emailSent) {
            header('Location: /working-paper/public/admin/working-papers/view.php?id=' . $wpId . '&success=sent');
            exit;
        } else {
            $error = 'Working paper status updated, but email failed to send. Please contact the client manually.';
        }

    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

ob_start();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Send Working Paper to Client</h2>
            <a href="/working-paper/public/admin/working-papers/view.php?id=<?= $wpId ?>" class="btn btn-secondary">← Back</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Confirmation Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Confirm Sending</h5>
            </div>
            <div class="card-body">
                <p>You are about to send this working paper to the client for review.</p>
                
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Client:</strong> <?= htmlspecialchars($client['name']) ?></p>
                        <p><strong>Email:</strong> <?= htmlspecialchars($client['email']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Service:</strong> <?= htmlspecialchars($wp['service']) ?></p>
                        <p><strong>Period:</strong> <?= htmlspecialchars($wp['period']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- What will happen -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">What will happen:</h5>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>A secure access token will be generated (valid for 1 hour)</li>
                    <li>An email will be sent to <strong><?= htmlspecialchars($client['email']) ?></strong></li>
                    <li>The client will receive a link to review and submit the working paper</li>
                    <li>The working paper status will change from <span class="badge bg-secondary">Draft</span> to <span class="badge bg-info">Sent</span></li>
                </ul>
            </div>
        </div>

        <!-- Action Buttons -->
        <form method="POST" action="">
            <div class="d-flex justify-content-end gap-2">
                <a href="/working-paper/public/admin/working-papers/view.php?id=<?= $wpId ?>" class="btn btn-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-success">
                    Send to Client
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../../views/layouts/admin.php';