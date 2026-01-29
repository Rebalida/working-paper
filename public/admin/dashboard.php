<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/Auth.php';
require_once __DIR__ . '/../../app/models/WorkingPaper.php';

Auth::requireAdmin();

$user = Auth::user();
$pageTitle = 'Dashboard';

// Get filter
$statusFilter = $_GET['status'] ?? 'all';

// Get working papers
$wpModel = new WorkingPaper();
if ($statusFilter === 'all') {
    $workingPapers = $wpModel->getAllWithClient();
} else {
    $workingPapers = $wpModel->getByStatus($statusFilter);
}

// Count by status
$statusCounts = [
    'draft' => 0,
    'sent' => 0,
    'submitted' => 0,
    'returned' => 0,
    'approved' => 0
];

$allPapers = $wpModel->getAllWithClient();
foreach ($allPapers as $wp) {
    if (isset($statusCounts[$wp['status']])) {
        $statusCounts[$wp['status']]++;
    }
}

// Helper function for badge colors
function getStatusBadgeClass($status) {
    $classes = [
        'draft' => 'secondary',
        'sent' => 'info',
        'submitted' => 'warning',
        'returned' => 'danger',
        'approved' => 'success'
    ];
    return $classes[$status] ?? 'secondary';
}

ob_start();
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Working Papers Dashboard</h2>
            <a href="/public/admin/working-papers/create.php" class="btn btn-primary">
                + Create New Working Paper
            </a>
        </div>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'deleted'): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <strong>Working paper deleted successfully!</strong>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Status Filter Tabs -->
        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'all' ? 'active' : '' ?>" 
                   href="?status=all">
                    All (<?= count($allPapers) ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'draft' ? 'active' : '' ?>" 
                   href="?status=draft">
                    Draft (<?= $statusCounts['draft'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'sent' ? 'active' : '' ?>" 
                   href="?status=sent">
                    Sent (<?= $statusCounts['sent'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'submitted' ? 'active' : '' ?>" 
                   href="?status=submitted">
                    Submitted (<?= $statusCounts['submitted'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'returned' ? 'active' : '' ?>" 
                   href="?status=returned">
                    Returned (<?= $statusCounts['returned'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $statusFilter === 'approved' ? 'active' : '' ?>" 
                   href="?status=approved">
                    Approved (<?= $statusCounts['approved'] ?>)
                </a>
            </li>
        </ul>

        <!-- Working Papers Table -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($workingPapers)): ?>
                    <div class="alert alert-info" role="alert">
                        No working papers found. 
                        <a href="/public/admin/working-papers/create.php" class="alert-link">Create your first one!</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Client</th>
                                    <th>Service</th>
                                    <th>Job Ref</th>
                                    <th>Period</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($workingPapers as $wp): ?>
                                    <tr>
                                        <td><?= $wp['id'] ?></td>
                                        <td><?= htmlspecialchars($wp['client_name']) ?></td>
                                        <td><?= htmlspecialchars($wp['service']) ?></td>
                                        <td><?= htmlspecialchars($wp['job_reference']) ?></td>
                                        <td><?= htmlspecialchars($wp['period']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusBadgeClass($wp['status']) ?>">
                                                <?= ucfirst($wp['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($wp['created_at'])) ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="/public/admin/working-papers/view.php?id=<?= $wp['id'] ?>" 
                                                   class="btn btn-outline-primary">
                                                    View
                                                </a>
                                                <?php if ($wp['status'] === 'draft'): ?>
                                                    <a href="/public/admin/working-papers/send.php?id=<?= $wp['id'] ?>" 
                                                       class="btn btn-outline-success">
                                                        Send
                                                    </a>
                                                <?php elseif ($wp['status'] === 'submitted'): ?>
                                                    <a href="/public/admin/working-papers/review.php?id=<?= $wp['id'] ?>" 
                                                       class="btn btn-outline-warning">
                                                        Review
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" 
                                                        class="btn btn-outline-danger" 
                                                        onclick="confirmDeleteFromDashboard(<?= $wp['id'] ?>, '<?= htmlspecialchars($wp['client_name'], ENT_QUOTES) ?>')">
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteDashboardModal" tabindex="-1" aria-labelledby="deleteDashboardModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteDashboardModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this working paper?</strong></p>
                <p id="deleteClientName" class="mb-3"></p>
                <div class="alert alert-danger mb-0" role="alert">
                    <strong>Warning:</strong> This will permanently delete the working paper, all expenses, documents, and history. This action cannot be undone!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="/public/admin/working-papers/delete.php" id="deleteDashboardForm">
                    <input type="hidden" name="working_paper_id" id="deleteDashboardWpId" value="">
                    <button type="submit" class="btn btn-danger">Delete Permanently</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmDeleteFromDashboard(wpId, clientName) {
        document.getElementById('deleteDashboardWpId').value = wpId;
        document.getElementById('deleteClientName').innerHTML = '<strong>Client:</strong> ' + clientName;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteDashboardModal'));
        deleteModal.show();
    }
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../../views/layouts/admin.php';