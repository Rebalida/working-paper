<?php

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../app/ClientAuth.php';
require_once __DIR__ . '/../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../app/models/Expense.php';
require_once __DIR__ . '/../../app/models/ExpenseDocument.php';
require_once __DIR__ . '/../../app/models/AccessToken.php';
require_once __DIR__ . '/../../app/FileUpload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /public');
    exit;
}

$token = $_POST['token'] ?? '';
$action = $_POST['action'] ?? '';

// Verify token
$result = ClientAuth::verifyToken($token);

if (!$result['valid']) {
    header('Location: /public/client/working-paper.php?token=' . $token . '&error=' . urlencode($result['error']));
    exit;
}

$workingPaper = $result['working_paper'];
$tokenData = $result['token_data'];

try {
    // Get expenses data
    $expensesData = $_POST['expenses'] ?? [];

    if (empty($expensesData)) {
        throw new Exception('No expense data provided');
    }

    $expenseModel = new Expense();
    $docModel = new ExpenseDocument();
    $fileUpload = new FileUpload();

    // Process each expense
    foreach ($expensesData as $expenseId => $data) {
        // Update client comment
        $comment = trim($data['comment'] ?? '');
        if ($comment) {
            $expenseModel->addClientComment($expenseId, $comment);
        }

        // Handle file uploads
        if (isset($_FILES['expenses']['name'][$expenseId]['documents'])) {
            $files = [
                'name' => $_FILES['expenses']['name'][$expenseId]['documents'],
                'type' => $_FILES['expenses']['type'][$expenseId]['documents'],
                'tmp_name' => $_FILES['expenses']['tmp_name'][$expenseId]['documents'],
                'error' => $_FILES['expenses']['error'][$expenseId]['documents'],
                'size' => $_FILES['expenses']['size'][$expenseId]['documents']
            ];

            $uploadedFiles = $fileUpload->uploadMultiple($files);

            // Save file records to database
            foreach ($uploadedFiles as $filename) {
                $docModel->create([
                    'expense_id' => $expenseId,
                    'file_path' => $filename,
                    'uploaded_by' => 'client'
                ]);
            }
        }
    }

    if ($action === 'submit') {
        // Check if can submit
        if (!ClientAuth::canSubmit($token)) {
            throw new Exception('This working paper cannot be submitted at this time');
        }

        // Update working paper status
        $wpModel = new WorkingPaper();
        $wpModel->update($workingPaper['id'], ['status' => 'submitted']);

        // Mark token as used
        $tokenModel = new AccessToken();
        $tokenModel->markAsUsed($token);

        // Log status change 
        require_once __DIR__ . '/../../app/models/StatusHistory.php';
        $statusHistory = new StatusHistory();
        $statusHistory->create([
            'working_paper_id' => $workingPaper['id'],
            'old_status' => $workingPaper['status'],
            'new_status' => 'submitted',
            'changed_by' => null // System/Client
        ]);

        // Redirect to success page
        header('Location: /public/client/success.php?token=' . $token);
        exit;
    } else {
        // Just saving draft
        header('Location: /public/client/working-paper.php?token=' . $token . '&success=saved');
        exit;
    }
} catch (Exception $e) {
    header('Location: /public/client/working-paper.php?token=' . $token . '&error=' . urlencode($e->getMessage()));
    exit;
}