<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/ClientAuth.php';
require_once __DIR__ . '/../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../app/models/Client.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('No access token provided');
}

// Verify token
$result = ClientAuth::verifyToken($token);

if (!$result['valid']) {
    header('Location: /public');
    exit;
}

$workingPaper = $result['working_paper'];
$clientModel = new Client();
$client = $clientModel->find($workingPaper['client_id']);

$pageTitle = 'Submission Successful';

ob_start();
?>

<div class="row justify-content-center mt-5">
    <div class="col mb-8">
        <div class="text-center mb-4">
            <div class="display-1 text-success">✓</div>
            <h1 class="text-success">Submission Successful!</h1>
        </div>

        <div class="card">
            <div class="card-body p-4">
                <h5 class="card-title">Thank you, <?= htmlspecialchars($client['name']) ?>!</h5>
                <p class="card-text">Your working paper has been successfully submitted to EndureGo.</p>

                <div class="alert alert-info mt-4" role="alert">
                    <h6 class="alert-heading">What happens next?</h6>
                    <ol class="mb-0">
                        <li>Our team will review your submission</li>
                        <li>We'll verify all expenses and supporting documents</li>
                        <li>You'll receive an email notification once the review is complete</li>
                        <li>If any changes are needed, we'll send you a new link to make revisions</li>
                    </ol>
                </div>

                <div class="bg-light p-3 rounded mt-4">
                    <h6>Submission Details:</h6>
                    <p class="mb-1"><strong>Service:</strong> <?= htmlspecialchars($workingPaper['service']) ?></p>
                    <p class="mb-1"><strong>Period:</strong> <?= htmlspecialchars($workingPaper['period']) ?></p>
                    <p class="mb-1"><strong>Job Reference:</strong> <?= htmlspecialchars($workingPaper['job_reference']) ?></p>
                    <p class="mb-0"><strong>Submitted:</strong> <?= date('M d, Y H:i') ?></p>
                </div>

                <div class="text-center mt-4">
                    <p class="text-muted">You can now close this window.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../views/layouts/client.php';