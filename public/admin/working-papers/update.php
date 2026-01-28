<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../../app/models/Expense.php';

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /public/admin/dashboard.php');
    exit;
}

try {
    $wpId = $_POST['working_paper_id'] ?? null;
    
    if (!$wpId) {
        throw new Exception('Working paper ID is required');
    }

    // Validate required fields
    $clientId = $_POST['client_id'] ?? null;
    $service = trim($_POST['service'] ?? '');
    $jobReference = trim($_POST['job_reference'] ?? '');
    $period = $_POST['period'] ?? '';
    $expenses = $_POST['expenses'] ?? [];

    if (!$clientId || !$service || !$jobReference || !$period || empty($expenses)) {
        throw new Exception('All fields are required');
    }

    // Update working paper
    $wpModel = new WorkingPaper();
    $wpModel->update($wpId, [
        'client_id' => $clientId,
        'service' => $service,
        'job_reference' => $jobReference,
        'period' => $period
    ]);

    // Get existing expense IDs
    $expenseModel = new Expense();
    $existingExpenses = $expenseModel->getByWorkingPaperId($wpId);
    $existingIds = array_column($existingExpenses, 'id');

    $processedIds = [];

    // Update or create expenses
    foreach ($expenses as $key => $expense) {
        $description = trim($expense['description'] ?? '');
        $amount = floatval($expense['amount'] ?? 0);
        $internalComment = trim($expense['internal_comment'] ?? '');

        if ($description && $amount > 0) {
            // Check if this is an existing expense (has numeric key) or new (has 'new_' prefix)
            if (is_numeric($key)) {
                // Update existing expense
                $expenseModel->update($key, [
                    'description' => $description,
                    'amount' => $amount,
                    'internal_comment' => $internalComment
                ]);
                $processedIds[] = $key;
            } else {
                // Create new expense (key starts with 'new_')
                $expenseModel->create([
                    'working_paper_id' => $wpId,
                    'description' => $description,
                    'amount' => $amount,
                    'internal_comment' => $internalComment
                ]);
            }
        }
    }

    // Delete expenses that were removed
    $deletedIds = array_diff($existingIds, $processedIds);
    foreach ($deletedIds as $deletedId) {
        $expenseModel->delete($deletedId);
    }

    // Redirect to working paper view
    header('Location: /public/admin/working-papers/view.php?id=' . $wpId . '&success=updated');
    exit;

} catch (Exception $e) {
    // Redirect back with error
    $wpId = $_POST['working_paper_id'] ?? '';
    header('Location: /public/admin/working-papers/edit.php?id=' . $wpId . '&error=' . urlencode($e->getMessage()));
    exit;
}