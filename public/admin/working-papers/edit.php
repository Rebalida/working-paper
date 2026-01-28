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

// Only allow editing drafts
if ($wp['status'] !== 'draft') {
    header('Location: /public/admin/working-papers/view.php?id=' . $wpId . '&error=cannot_edit');
    exit;
}

// Get all clients for dropdown
$clientModel = new Client();
$clients = $clientModel->all();

// Get expenses
$expenseModel = new Expense();
$expenses = $expenseModel->getByWorkingPaperId($wpId);

$pageTitle = 'Edit Working Paper';

ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit Working Paper</h2>
            <a href="/public/admin/working-papers/view.php?id=<?= $wpId ?>" class="btn btn-secondary">← Back</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="/public/admin/working-papers/update.php" id="wpForm">
                    <input type="hidden" name="working_paper_id" value="<?= $wpId ?>">
                    
                    <!-- Client & Job Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Client Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">-- Select Client --</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>" <?= $wp['client_id'] == $client['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="service" class="form-label">Service <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="service" name="service" 
                                   value="<?= htmlspecialchars($wp['service']) ?>"
                                   placeholder="e.g. Service 1" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="job_reference" class="form-label">Job Reference Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="job_reference" name="job_reference" 
                                   value="<?= htmlspecialchars($wp['job_reference']) ?>"
                                   placeholder="e.g. Job 1" required>
                        </div>

                        <div class="col-md-6">
                            <label for="period" class="form-label">Period <span class="text-danger">*</span></label>
                            <select class="form-select" id="period" name="period" required>
                                <option value="">-- Select Year --</option>
                                <?php for ($year = 2017; $year <= 2030; $year++): ?>
                                    <option value="<?= $year ?>" <?= $wp['period'] == $year ? 'selected' : '' ?>>
                                        <?= $year ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Expenses Section -->
                    <h4 class="mb-3">Expenses</h4>
                    
                    <div id="expenses-container">
                        <?php if (empty($expenses)): ?>
                            <!-- Initial empty expense row -->
                            <div class="expense-row card mb-3">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Description <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="expenses[new_0][description]" 
                                                   placeholder="e.g. Office Supplies" required>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <label class="form-label">Amount <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control" name="expenses[new_0][amount]" 
                                                   placeholder="0.00" required>
                                        </div>
                                        <div class="col-md-3 mb-3 d-flex align-items-end">
                                            <button type="button" class="btn btn-danger w-100" onclick="removeExpense(this)">
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="form-label">Internal Comment (Optional)</label>
                                            <textarea class="form-control" name="expenses[new_0][internal_comment]" 
                                                      rows="2" placeholder="Internal notes (not visible to client)"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($expenses as $index => $expense): ?>
                                <div class="expense-row card mb-3" data-expense-id="<?= $expense['id'] ?>">
                                    <div class="card-body">
                                        <input type="hidden" name="expenses[<?= $expense['id'] ?>][id]" value="<?= $expense['id'] ?>">
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Description <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" 
                                                       name="expenses[<?= $expense['id'] ?>][description]" 
                                                       value="<?= htmlspecialchars($expense['description']) ?>"
                                                       placeholder="e.g. Office Supplies" required>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="form-label">Amount <span class="text-danger">*</span></label>
                                                <input type="number" step="0.01" class="form-control" 
                                                       name="expenses[<?= $expense['id'] ?>][amount]" 
                                                       value="<?= $expense['amount'] ?>"
                                                       placeholder="0.00" required>
                                            </div>
                                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                                <button type="button" class="btn btn-danger w-100" onclick="removeExpense(this)">
                                                    Remove
                                                </button>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <label class="form-label">Internal Comment (Optional)</label>
                                                <textarea class="form-control" 
                                                          name="expenses[<?= $expense['id'] ?>][internal_comment]" 
                                                          rows="2" 
                                                          placeholder="Internal notes (not visible to client)"><?= htmlspecialchars($expense['internal_comment'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <button type="button" class="btn btn-outline-primary mb-4" onclick="addExpense()">
                        + Add Another Expense
                    </button>

                    <hr class="my-4">

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/public/admin/working-papers/view.php?id=<?= $wpId ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Working Paper</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
let expenseCount = <?= count($expenses) ?>;

function addExpense() {
    const container = document.getElementById('expenses-container');
    const newExpense = `
        <div class="expense-row card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="expenses[new_${expenseCount}][description]" 
                               placeholder="e.g. Office Supplies" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="expenses[new_${expenseCount}][amount]" 
                               placeholder="0.00" required>
                    </div>
                    <div class="col-md-3 mb-3 d-flex align-items-end">
                        <button type="button" class="btn btn-danger w-100" onclick="removeExpense(this)">
                            Remove
                        </button>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label class="form-label">Internal Comment (Optional)</label>
                        <textarea class="form-control" name="expenses[new_${expenseCount}][internal_comment]" 
                                  rows="2" placeholder="Internal notes (not visible to client)"></textarea>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', newExpense);
    expenseCount++;
}

function removeExpense(button) {
    const expenseRow = button.closest('.expense-row');
    const container = document.getElementById('expenses-container');
    
    // Don't allow removing if it's the only expense
    if (container.children.length > 1) {
        expenseRow.remove();
    } else {
        alert('You must have at least one expense');
    }
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../../views/layouts/admin.php';