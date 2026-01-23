<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../../app/models/Client.php';
require_once __DIR__ . '/../../../app/models/Expense.php';

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

// Get client
$clientModel = new Client();
$client = $clientModel->find($wp['client_id']);

// Get expenses
$expenseModel = new Expense();
$expenses = $expenseModel->getByWorkingPaperId($wpId);

$pageTitle = 'View Working Paper';

$success = $_GET['success'] ?? '';

ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Working Paper Details</h2>
            <a href="/working-paper/public/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if ($success === 'created'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Working Paper created successfully!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Working Paper Header -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Working Paper Information</h5>
                <span class="badge bg-light text-dark"><?= strtoupper($wp['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Client Name:</strong> <?= htmlspecialchars($client['name']) ?></p>
                        <p><strong>Client Email:</strong> <?= htmlspecialchars($client['email']) ?></p>
                        <p><strong>Service:</strong> <?= htmlspecialchars($wp['service']) ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Job Reference:</strong> <?= htmlspecialchars($wp['job_reference']) ?></p>
                        <p><strong>Period:</strong> <?= htmlspecialchars($wp['period']) ?></p>
                        <p><strong>Created:</strong> <?= date('M d, Y H:i', strtotime($wp['created_at'])) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Expenses</h5>
            </div>
            <div class="card-body">
                <?php if (empty($expenses)): ?>
                    <p class="text-muted">No expenses added yet.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Internal Comment</th>
                                    <th>Client Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = 0;
                                foreach ($expenses as $index => $expense): 
                                    $total += $expense['amount'];
                                ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($expense['description']) ?></td>
                                        <td>$<?= number_format($expense['amount'], 2) ?></td>
                                        <td>
                                            <?php if ($expense['internal_comment']): ?>
                                                <span class="badge bg-secondary">Internal</span>
                                                <?= htmlspecialchars($expense['internal_comment']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($expense['client_comment']): ?>
                                                <?= htmlspecialchars($expense['client_comment']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-secondary">
                                    <td colspan="2" class="text-end"><strong>Total:</strong></td>
                                    <td colspan="3"><strong>$<?= number_format($total, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Actions</h5>
                <div class="d-flex gap-2">
                    <?php if ($wp['status'] === 'draft'): ?>
                        <a href="/working-paper/public/admin/working-papers/send.php?id=<?= $wpId ?>" 
                           class="btn btn-success">
                            Send to Client
                        </a>
                        <a href="/working-paper/public/admin/working-papers/edit.php?id=<?= $wpId ?>" 
                           class="btn btn-warning">
                            Edit
                        </a>
                    <?php elseif ($wp['status'] === 'submitted'): ?>
                        <a href="/working-paper/public/admin/working-papers/review.php?id=<?= $wpId ?>" 
                           class="btn btn-primary">
                            Review & Approve
                        </a>
                    <?php elseif ($wp['status'] === 'approved'): ?>
                        <span class="badge bg-success fs-5">✓ Approved</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../../views/layouts/admin.php';