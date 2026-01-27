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
    // Validate required fields
    $clientId = $_POST['client_id'] ?? null;
    $service = trim($_POST['service'] ?? '');
    $jobReference = trim($_POST['job_reference'] ?? '');
    $period = $_POST['period'] ?? '';
    $expenses = $_POST['expenses'] ?? [];

    if (!$clientId || !$service || !$jobReference || !$period || empty($expenses)) {
        throw new Exception('All fields are required');
    }

    // Generate UUID for working paper
    $uuid = \Ramsey\Uuid\Uuid::uuid4()->toString();

    // Create working paper
    $wpModel = new WorkingPaper();
    $wpId = $wpModel->create([
        'uuid' => $uuid,
        'client_id' => $clientId,
        'service' => $service,
        'job_reference' => $jobReference,
        'period' => $period,
        'status' => 'draft'
    ]);

    // Create expenses
    $expenseModel = new Expense();
    foreach ($expenses as $expense) {
        $description = trim($expense['description'] ?? '');
        $amount = floatval($expense['amount'] ?? 0);
        $internalComment = trim($expense['internal_comment'] ?? '');

        if ($description && $amount > 0) {
            $expenseModel->create([
                'working_paper_id' => $wpId,
                'description' => $description,
                'amount' => $amount,
                'internal_comment' => $internalComment
            ]);
        }
    }

    // Redirect to working paper view
    header('Location: /public/admin/working-papers/view.php?id=' . $wpId . '&success=created');
    exit;
} catch (Exception $e) {
    // Redirect back with error
    header('Location: /public/admin/working-papers/create.php?error=' . urlencode($e->getMessage()));
    exit;
}