<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../../app/models/Client.php';
require_once __DIR__ . '/../../../app/models/Expense.php';
require_once __DIR__ . '/../../../app/models/ExpenseDocument.php';
require_once __DIR__ . '/../../../app/models/StatusHistory.php';

Auth::requireAdmin();

$user = Auth::user();

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

// Check if status is submitted or returned
if (!in_array($wp['status'], ['submitted', 'returned'])) {
    header('Location: /public/admin/working-papers/view.php?id=' . $wpId);
    exit;
}

// Get client
$clientModel = new Client();
$client = $clientModel->find($wp['client_id']);

// Get expenses with documents
$expenseModel = new Expense();
$expenses = $expenseModel->getByWorkingPaperId($wpId);

// Get documents for each expense
$docModel = new ExpenseDocument();

// Get status history
$historyModel = new StatusHistory();
$history = $historyModel->getByWorkingPaperId($wpId);

$pageTitle = 'Review Working Paper';

ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Review Working Paper</h2>
            <a href="/public/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <!-- Working Paper Header  -->
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Working Paper Information</h5>
                <span class="badge bg-dark"><?= strtoupper($wp['status']) ?></span>
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

        <!-- Status History -->
        <?php if (!empty($history)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Status History</h5>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Changed By</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $h): ?>
                                    <tr>
                                        <td><?= date('M d, Y H:i', strtotime($h['changed_at'])) ?></td>
                                        <td>
                                            <?php if ($h['old_status']): ?>
                                                <span class="badge bg-secondary"><?= ucfirst($h['old_status']) ?></span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-info"><?= ucfirst($h['new_status']) ?></span></td>
                                        <td><?= htmlspecialchars($h['changed_by_name']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Expense Review -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Expenses Review (<?= count($expenses) ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($expenses)): ?>
                    <p class="text-muted">No expenses to review</p>
                <?php else: ?>
                    <?php 
                    $total = 0;
                    foreach ($expenses as $index => $expense): 
                        $total += $expense['amount'];
                        $documents = $docModel->getByExpenseId($expense['id']);
                    ?>
                        <div class="card mb-3 <?= $index > 0 ? 'mt-3' : '' ?>">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Expense #<?= $index + 1 ?></h6>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-8">
                                        <p><strong>Description:</strong></br>
                                            <?= htmlspecialchars($expense['description']) ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4">
                                        <p><strong>Amount:</strong><br>
                                            <span class="fs-5 text-primary">$<?= number_format($expense['amount'], 2) ?></span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Internal Comment (from admin during creation) -->
                                <?php if ($expense['internal_comment']): ?>
                                    <div class="alert alert-secondary mb-3" role="alert">
                                        <strong>Internal Note (Original):</strong><br>
                                        <?= nl2br(htmlspecialchars($expense['internal_comment'])) ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Client Comment -->
                                <?php if ($expense['client_comment']): ?>
                                    <div class="alert alert-info mb-3" role="alert">
                                        <strong>Client Comment:</strong><br>
                                        <?= nl2br(htmlspecialchars($expense['client_comment'])) ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted"><em>No client comment provided</em></p>
                                <?php endif; ?>

                                <!-- Supporting Documents -->
                                <?php if (!empty($documents)): ?>
                                    <div class="mb-3">
                                        <strong>Supporting Documents:</strong>
                                        <ul class="list-group mt-2">
                                            <?php foreach ($documents as $doc): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="badge bg-<?= $doc['uploaded_by'] === 'client' ? 'info' : 'secondary' ?>">
                                                            <?= ucfirst($doc['uploaded_by']) ?>
                                                        </span>
                                                        <?= htmlspecialchars($doc['file_path']) ?>
                                                        <br>
                                                        <small class="text-muted">
                                                            Uploaded: <?= date('M d, Y H:i', strtotime($doc['created_at'])) ?>
                                                        </small>
                                                    </div>
                                                    <a href="/public/view-file.php?file=<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        View
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted"><em>No supporting documents uploaded</em></p>
                                <?php endif; ?>

                                <hr>
                                
                                <!-- Add/Update Internal Review Comment -->
                                <div>
                                    <label class="form-label">
                                        <strong>Add Review Note (Internal - not visible to client):</strong>
                                    </label>
                                    <textarea class="form-control" id="review_note_<?= $expense['id'] ?>" rows="2" placeholder="Add internal review notes for this expense..."></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" onclick="saveReviewNote(<?= $expense['id'] ?>)">
                                        Save Note
                                    </button>
                                    <span id="save_status_<?= $expense['id'] ?>" class="ms-2"></span>
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
                                        $<?= number_format($total, 2) ?>
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Decision Form -->
        <div class="card">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0">Make Decision</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="/public/admin/working-papers/decision.php" id="decisionForm">
                    <input type="hidden" name="working_paper_id" value="<?= $wpId ?>">

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="admin_notes" class="form-label"><strong>Notes to Client (Optional)</strong></label>
                            <textarea class="form-control" id="admin_notes" name="admin_notes" rows="4" placeholder="If returning for revision, explain what needs to be changed..."></textarea>
                            <small class="form-text text-muted">
                                These notes will be sent to the client if you return the working paper.
                            </small>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="/public/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
                        <div class="d-flex gap-2">
                            <button type="submit" name="decision" value="return" class="btn btn-warning" onclick="return confirm('Are you sure you want to return this working paper to the client for revision?')">Return for Revision</button>
                            <button type="submit" name="decision" value="approve" class="btn btn-success" onclick="return confirm('Are you sure you want to approve this working paper?')">Approve</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Save review note via AJAX (optional feature)
function saveReviewNote(expenseId) {
    const noteText = document.getElementById('review_note_' + expenseId).value;
    const statusSpan = document.getElementById('save_status_' + expenseId);
    
    if (!noteText.trim()) {
        alert('Please enter a note first');
        return;
    }
    
    statusSpan.innerHTML = '<span class="text-muted">Saving...</span>';
    
    fetch('/public/admin/working-papers/save-note.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'expense_id=' + expenseId + '&note=' + encodeURIComponent(noteText)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            statusSpan.innerHTML = '<span class="text-success">✓ Saved</span>';
            setTimeout(() => {
                statusSpan.innerHTML = '';
            }, 3000);
        } else {
            statusSpan.innerHTML = '<span class="text-danger">✗ Error</span>';
        }
    })
    .catch(error => {
        statusSpan.innerHTML = '<span class="text-danger">✗ Error</span>';
    });
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../../views/layouts/admin.php';