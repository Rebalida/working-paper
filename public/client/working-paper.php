<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/ClientAuth.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('No access token provided');
}

// Verify token
$result = ClientAuth::verifyToken($token);

if (!$result['valid']) {
    $pageTitle = 'Access Denied';
    ob_start();
    ?>
    <div class="row justify-content-center mt-5">
        <div class="col-md-6">
            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading">Access Denied</h4>
                <p><?= htmlspecialchars($result['error']) ?></p>
                <?php if (isset($result['expired']) && $result['expired']): ?>
                    <hr>
                    <p class="mb-0">Please contact us to request a new access link.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php
    $content = ob_get_clean();
    require_once __DIR__ . '/../../views/layouts/client.php';
    exit;
}

// Valid token - show working paper
$tokenData = $result['token_data'];
$workingPaper = $result['working_paper'];
$timeRemaining = ClientAuth::getTimeRemaining($tokenData['expires_at']);
$canSubmit = ClientAuth::canSubmit($token);

$pageTitle = 'Working Paper Review';

ob_start();
?>

<!-- Timer Alert -->
<?php if (!$timeRemaining['expired']): ?>
    <div class="alert alert-warning" role="alert">
        <strong>This link expires in: <span id="timer"><?= $timeRemaining['formatted'] ?></span></strong>
    </div>
<?php else: ?>
    <div class="alert alert-danger" role="alert">
        <strong>This link has expired</strong>
    </div>
<?php endif; ?>

<!-- Working Paper Header -->
<div class="card mb-4">
    <div class="card-header bg-primary text-white">
        <h4 class="mb-0">Working Paper Review</h4>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Service:</strong> <?= htmlspecialchars($workingPaper['service']) ?></p>
                <p><strong>Job Reference:</strong> <?= htmlspecialchars($workingPaper['job_reference']) ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Period:</strong> <?= htmlspecialchars($workingPaper['period']) ?></p>
                <p><strong>Status:</strong> 
                    <span class="badge bg-info"><?= ucfirst($workingPaper['status']) ?></span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Status Messages -->
<?php if (!$canSubmit && $tokenData['used_at']): ?>
    <div class="alert alert-info" role="alert">
        <strong>This working paper has already been submitted</strong>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <h5 class="card-title">Expenses</h5>
        <p class="text-muted"></p>

        <p class="mb-0">
            <strong>Token Valid:</strong> Yes <br>
            <strong>Can Submit:</strong> <?= $canSubmit ? 'Yes' : 'No' ?><br>
            <strong>Working Paper ID:</strong> <?= $workingPaper['id'] ?>
        </p>
    </div>
</div>

<?php
$content = ob_get_clean();

// Scripts section for countdown timer
if (!$timeRemaining['expired']) {
    ob_start();
    ?>
    <script>
        // Countdown timer
        let remainingSeconds = <?= $timeRemaining['seconds'] ?>;
        
        const timerElement = document.getElementById('timer');
        
        const countdown = setInterval(() => {
            remainingSeconds--;
            
            if (remainingSeconds <= 0) {
                clearInterval(countdown);
                location.reload();
                return;
            }
            
            const minutes = Math.floor(remainingSeconds / 60);
            const seconds = remainingSeconds % 60;
            
            timerElement.textContent = 
                String(minutes).padStart(2, '0') + ':' + 
                String(seconds).padStart(2, '0');
            
        }, 1000);
    </script>
    <?php
    $scripts = ob_get_clean();
}

require_once __DIR__ . '/../../views/layouts/client.php';