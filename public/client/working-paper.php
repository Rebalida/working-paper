<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/ClientAuth.php';
require_once __DIR__ . '/../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../app/models/Client.php';
require_once __DIR__ . '/../../app/models/Expense.php';
require_once __DIR__ . '/../../app/models/AccessToken.php';

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

// Valid token - get data
$tokenData = $result['token_data'];
$workingPaper = $result['working_paper'];
$timeRemaining = ClientAuth::getTimeRemaining($tokenData['expires_at']);
$canSubmit = ClientAuth::canSubmit($token);

// Get client info
$clientModel = new Client();
$client = $clientModel->find($workingPaper['client_id']);

// Get expenses
$expenseModel = new Expense();
$expenses = $expenseModel->getByWorkingPaperId($workingPaper['id']);

$pageTitle = 'Working Paper Review';

$success = $_GET['success'] ?? '';
$error = $_GET['error'] ?? '';

// Start content buffer
ob_start();
?>

<!-- Success/Error Messages -->
<?php if ($success === 'saved'): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>✓ Changes saved successfully!</strong>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error:</strong> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Timer Alert -->
<?php if (!$timeRemaining['expired']): ?>
    <div class="alert alert-warning" role="alert">
        <strong>This link expires in: <span id="timer"><?= $timeRemaining['formatted'] ?></span></strong>
    </div>
<?php else: ?>
    <div class="alert alert-danger" role="alert">
        <strong>This link has expired</strong>
        <p class="mb-0">Please contact us to request a new access link.</p>
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
                <p><strong>Client:</strong> <?= htmlspecialchars($client['name']) ?></p>
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
<?php if ($tokenData['used_at']): ?>
    <div class="alert alert-info" role="alert">
        <h5 class="alert-heading">✓ Already Submitted</h5>
        <p class="mb-0">This working paper was submitted on <?= date('M d, Y H:i', strtotime($tokenData['used_at'])) ?>.</p>
        <p class="mb-0">You can still view the expenses below, but you cannot make changes.</p>
    </div>
<?php endif; ?>

<!-- Instructions -->
<?php if ($canSubmit): ?>
    <div class="alert alert-info" role="alert">
        <h5 class="alert-heading">Instructions</h5>
        <ol class="mb-0">
            <li>Review each expense below</li>
            <li>Add your comments for each expense (optional)</li>
            <li>Upload supporting documents (receipts, invoices, etc.) if needed</li>
            <li>Click "Submit " when you're done</li>
        </ol>
    </div>
<?php endif; ?>

<!-- Expenses Form -->
<form method="POST" action="/public/client/submit.php" enctype="multipart/form-data" id="expensesForm">
    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
    <input type="hidden" name="working_paper_id" value="<?= $workingPaper['id'] ?>">
    
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Expenses (<?= count($expenses) ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (empty($expenses)): ?>
                <p class="text-muted">No expenses to review.</p>
            <?php else: ?>
                <?php foreach ($expenses as $index => $expense): ?>
                    <div class="card mb-3 <?= $index > 0 ? 'mt-3' : '' ?>">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Expense #<?= $index + 1 ?></h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <p><strong>Description:</strong><br>
                                        <?= htmlspecialchars($expense['description']) ?>
                                    </p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Amount:</strong><br>
                                        <span class="fs-5 text-primary">$<?= number_format($expense['amount'], 2) ?></span>
                                    </p>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="comment_<?= $expense['id'] ?>" class="form-label">
                                        <strong>Your Comment</strong> <span class="text-muted">(Optional)</span>
                                    </label>
                                    <textarea 
                                        class="form-control" 
                                        id="comment_<?= $expense['id'] ?>" 
                                        name="expenses[<?= $expense['id'] ?>][comment]" 
                                        rows="3"
                                        placeholder="Add any comments or explanations for this expense..."
                                        <?= !$canSubmit ? 'readonly' : '' ?>
                                    ><?= htmlspecialchars($expense['client_comment'] ?? '') ?></textarea>
                                </div>

                                <div class="col-md-12">
                                    <label class="form-label">
                                        <strong>Supporting Documents</strong> <span class="text-muted">(Optional)</span>
                                    </label>
                                    
                                    <?php if ($canSubmit): ?>
                                        <input 
                                            type="file" 
                                            class="form-control" 
                                            name="expenses[<?= $expense['id'] ?>][documents][]"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            multiple
                                        >
                                        <small class="form-text text-muted">
                                            Accepted formats: PDF, JPG, PNG (Max 5MB per file)
                                        </small>
                                    <?php endif; ?>

                                    <!-- Show existing documents -->
                                    <?php
                                    require_once __DIR__ . '/../../app/models/ExpenseDocument.php';
                                    $docModel = new ExpenseDocument();
                                    $documents = $docModel->getByExpenseId($expense['id']);
                                    ?>

                                    <?php if (!empty($documents)): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Uploaded documents:</small>
                                            <ul class="list-group list-group-sm mt-1">
                                                <?php foreach ($documents as $doc): ?>
                                                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                                        <span>
                                                            <small><?= htmlspecialchars($doc['file_path']) ?></small>
                                                        </span>
                                                        <span class="badge bg-secondary">
                                                            <?= ucfirst($doc['uploaded_by']) ?>
                                                        </span>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Total -->
                <div class="card bg-light">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h5 class="mb-0">Total Amount:</h5>
                            </div>
                            <div class="col-md-4 text-end">
                                <h5 class="mb-0 text-primary">
                                    $<?= number_format(array_sum(array_column($expenses, 'amount')), 2) ?>
                                </h5>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <?php if ($canSubmit && !$timeRemaining['expired']): ?>
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1">Ready to submit?</h5>
                        <p class="text-muted mb-0">Make sure you've reviewed all expenses and added your comments.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" name="action" value="save" class="btn btn-outline-primary">
                            Save Draft
                        </button>
                        <button type="submit" name="action" value="submit" class="btn btn-success" 
                                onclick="return confirm('Are you sure you want to submit this working paper? You will not be able to make changes after submission.')">
                            Submit
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</form>

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