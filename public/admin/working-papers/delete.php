<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../../app/models/Expense.php';
require_once __DIR__ . '/../../../app/models/ExpenseDocument.php';
require_once __DIR__ . '/../../../app/FileUpload.php';

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /public/admin/dashboard.php');
    exit;
}

$wpId = $_POST['working_paper_id'] ?? null;

if (!$wpId) {
    header('Location: /public/admin/dashboard.php?error=invalid_request');
    exit;
}

try {
    $wpModel = new WorkingPaper();
    $wp = $wpModel->find($wpId);
    
    if (!$wp) {
        throw new Exception('Working paper not found');
    }
    $expenseModel = new Expense();
    $expenses = $expenseModel->getByWorkingPaperId($wpId);

    $docModel = new ExpenseDocument();
    $fileUpload = new FileUpload();
    
    foreach ($expenses as $expense) {
        $documents = $docModel->getByExpenseId($expense['id']);
        
        foreach ($documents as $doc) {
            $fileUpload->delete($doc['file_path']);
        }
        
    }

    $wpModel->delete($wpId);

    header('Location: /public/admin/dashboard.php?success=deleted');
    exit;

} catch (Exception $e) {
    header('Location: /public/admin/working-papers/view.php?id=' . $wpId . '&error=' . urlencode($e->getMessage()));
    exit;
}