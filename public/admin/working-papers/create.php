<?php
require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/Client.php';

Auth::requireAdmin();

$user = Auth::user();
$pageTitle = 'Create Working Paper';

// Get all clients for dropdown
$clientModel = new Client();
$clients = $clientModel->all();

ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Create Working Paper</h2>
            <a href="/working-paper/public/admin/dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        </div>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="/working-paper/public/admin/working-papers/store.php" id="wpForm">
                    
                    <!-- Client & Job Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label">Client Name <span class="text-danger">*</span></label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value="">-- Select Client --</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?= $client['id'] ?>">
                                        <?= htmlspecialchars($client['name']) ?> (<?= htmlspecialchars($client['email']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <a href="/working-paper/public/admin/clients/create.php">+ Add New Client</a>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="service" class="form-label">Service <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="service" name="service" 
                                   placeholder="e.g. Service 1" required>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label for="job_reference" class="form-label">Job Reference Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="job_reference" name="job_reference" 
                                   placeholder="e.g. Job 1" required>
                        </div>

                        <div class="col-md-6">
                            <label for="period" class="form-label">Period <span class="text-danger">*</span></label>
                            <select class="form-select" id="period" name="period" required>
                                <option value="">-- Select Year --</option>
                                <?php for ($year = 2017; $year <= 2030; $year++): ?>
                                    <option value="<?= $year ?>" <?= $year == date('Y') ? 'selected' : '' ?>>
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
                        <!-- Initial expense row -->
                        <div class="expense-row card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Description <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="expenses[0][description]" 
                                               placeholder="e.g. Office Supplies" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" name="expenses[0][amount]" 
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
                                        <textarea class="form-control" name="expenses[0][internal_comment]" 
                                                  rows="2" placeholder="Internal notes (not visible to client)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-primary mb-4" onclick="addExpense()">
                        + Add Another Expense
                    </button>

                    <hr class="my-4">

                    <!-- Submit Buttons -->
                    <div class="d-flex justify-content-end gap-2">
                        <a href="/working-paper/public/admin/dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Create Working Paper</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>

<script>
let expenseCount = 1;

function addExpense() {
    const container = document.getElementById('expenses-container');
    const newExpense = `
        <div class="expense-row card mb-3">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="expenses[${expenseCount}][description]" 
                               placeholder="e.g. Office Supplies" required>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" class="form-control" name="expenses[${expenseCount}][amount]" 
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
                        <textarea class="form-control" name="expenses[${expenseCount}][internal_comment]" 
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