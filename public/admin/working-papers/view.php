<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../../app/models/Client.php';
require_once __DIR__ . '/../../../app/models/Expense.php';
require_once __DIR__ . '/../../../app/ClientAuth.php';

Auth::requireAdmin();

$user = Auth::user();

// Get working paper ID
$wpId = $_GET['id'] ?? null;
if (!$wpId) {
    header('Location: /public/admin/dashboard.php');
    exit;
}

// Get working paper
$wpModel = new WorkingPaper();
$wp = $wpModel->find($wpId);

if (!$wp) {
    header('Location: /public/admin/dashboard.php');
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
            <a href="/public/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <?php if ($success === 'created'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Working Paper created successfully!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($success === 'sent'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Working paper sent to client successfully!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($success === 'approved'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Working paper approved successfully!</strong> The client has been notified.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($success === 'returned'): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>↩ Working paper returned for revision.</strong> A new link has been sent to the client.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php elseif ($success === 'updated'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>✓ Working paper updated successfully!</strong>
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

        <!-- Token Information (if sent) -->
        <?php if (in_array($wp['status'], ['sent', 'submitted', 'returned', 'approved'])): ?>
            <?php
            require_once __DIR__ . '/../../../app/models/AccessToken.php';
            $tokenModel = new AccessToken();
            $activeToken = $tokenModel->getActiveToken($wpId);

            $allTokens = $tokenModel->getAllByWorkingPaperId($wpId);
            ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Access Information</h5>
                </div>
                <div class="card-body">
                    <?php if ($activeToken): ?>
                        <?php
                        $timeRemaining = ClientAuth::getTimeRemaining($activeToken['expires_at']);
                        ?>
                        <div class="alert alert-info" role="alert">
                            <strong>Active Link:</strong> 
                            <?php if (!$timeRemaining['expired']): ?>
                                Expires in <strong><?= $timeRemaining['formatted'] ?></strong>
                            <?php else: ?>
                                <span class="text-danger">Expired</span>
                            <?php endif; ?>
                        </div>

                        <p><strong>Client Link:</strong></p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" readonly value="<?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] ?>/public/client/working-paper.php?token=<?= $activeToken['token'] ?>" id="clientLink">
                            <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">
                                Copy Link
                            </button>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No active access token</p>
                    <?php endif; ?>

                    <?php if (count($allTokens) > 1): ?>
                        <details class="mt-3">
                            <summary style="cursor: pointer;">View all tokens (<?= count($allTokens) ?>)</summary>
                            <div class="table-responsive mt-2">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Created</th>
                                            <th>Expires</th>
                                            <th>Used</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($allTokens as $token): ?>
                                            <tr>
                                                <td><?= date('M d, H:i', strtotime($token['created_at'])) ?></td>
                                                <td><?= date('M d, H:i', strtotime($token['expires_at'])) ?></td>
                                                <td><?= $token['used_at'] ? date('M d, H:i', strtotime($token['used_at'])) : '-' ?></td>
                                                <td>
                                                    <?php if ($token['used_at']): ?>
                                                        <span class="badge bg-success">Used</span>
                                                    <?php elseif (strtotime($token['expires_at']) < time()): ?>
                                                        <span class="badge bg-danger">Expired</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info">Active</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </details>
                    <?php endif; ?>
                </div>
            </div>

            <script>
                function copyLink() {
                    const linkInput = document.getElementById('clientLink');
                    linkInput.select();
                    linkInput.setSelectionRange(0, 99999);

                    navigator.clipboard.writeText(linkInput.value).then(() => {
                        alert('Link copied to clipboard!');
                    });
                }
            </script>
        <?php endif; ?>

        <!-- Client-Added Expenses (if any) -->
        <?php
        $clientAddedExpenses = array_filter($expenses, function($exp) {
            return $exp['added_by'] === 'client';
        });
        ?>
        
        <?php if (!empty($clientAddedExpenses)): ?>
            <div class="card mb-4 border-info">
                <div class="card-header text-white" style="background: #17a2b8;">
                    <h5 class="mb-0">Client-Added Expenses</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Client Comment</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $clientTotal = 0;
                                $clientIndex = 1;
                                foreach ($clientAddedExpenses as $expense): 
                                    $clientTotal += $expense['amount'];
                                ?>
                                    <tr>
                                        <td><?= $clientIndex++ ?></td>
                                        <td>
                                            <span class="badge bg-info">Client Added</span>
                                            <?= htmlspecialchars($expense['description']) ?>
                                        </td>
                                        <td>$<?= number_format($expense['amount'], 2) ?></td>
                                        <td>
                                            <?php if ($expense['client_comment']): ?>
                                                <?= htmlspecialchars($expense['client_comment']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info">
                                    <td colspan="2" class="text-end"><strong>Client Expenses Total:</strong></td>
                                    <td colspan="2"><strong>$<?= number_format($clientTotal, 2) ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

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
                <div class="d-flex gap-2 align-items-center">
                    <?php if ($wp['status'] === 'draft'): ?>
                        <a href="/public/admin/working-papers/send.php?id=<?= $wpId ?>" 
                           class="btn btn-success">
                            Send to Client
                        </a>
                        <a href="/public/admin/working-papers/edit.php?id=<?= $wpId ?>" 
                           class="btn btn-warning">
                            Edit
                        </a>
                    <?php elseif ($wp['status'] === 'submitted'): ?>
                        <a href="/public/admin/working-papers/review.php?id=<?= $wpId ?>" 
                           class="btn btn-primary">
                            Review & Approve
                        </a>
                    <?php elseif ($wp['status'] === 'approved'): ?>
                        <span class="badge bg-success pb-2 fs-6">Approved</span>
                    <?php endif; ?>

                    <button type="button" class="btn btn-danger" onclick="confirmDelete(<?= $wpId ?>)">
                        Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this working paper?</strong></p>
                <p>This action will permanently delete:</p>
                <ul>
                    <li>The working paper</li>
                    <li>All expenses (admin and client-added)</li>
                    <li>All uploaded documents</li>
                    <li>All access tokens</li>
                    <li>All status history</li>
                </ul>
                <div class="alert alert-danger mb-0" role="alert">
                    <strong>Warning:</strong> This action cannot be undone!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="/public/admin/working-papers/delete.php" id="deleteForm">
                    <input type="hidden" name="working_paper_id" id="deleteWpId" value="">
                    <button type="submit" class="btn btn-danger">Delete Permanently</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(wpId) {
    document.getElementById('deleteWpId').value = wpId;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../../views/layouts/admin.php';