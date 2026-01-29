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
    $expenseModel = new Expense();
    $docModel = new ExpenseDocument();
    $fileUpload = new FileUpload();

    // Process existing admin expenses (comments + documents only)
    $existingExpenses = $_POST['existing_expenses'] ?? [];
    foreach ($existingExpenses as $expenseId => $data) {
        // Update client comment
        $comment = trim($data['comment'] ?? '');
        if ($comment) {
            $expenseModel->addClientComment($expenseId, $comment);
        }

        // Handle file uploads for existing expenses
        if (isset($_FILES['existing_expenses']['name'][$expenseId]['documents'])) {
            $files = [
                'name' => $_FILES['existing_expenses']['name'][$expenseId]['documents'],
                'type' => $_FILES['existing_expenses']['type'][$expenseId]['documents'],
                'tmp_name' => $_FILES['existing_expenses']['tmp_name'][$expenseId]['documents'],
                'error' => $_FILES['existing_expenses']['error'][$expenseId]['documents'],
                'size' => $_FILES['existing_expenses']['size'][$expenseId]['documents']
            ];

            $uploadedFiles = $fileUpload->uploadMultiple($files);

            foreach ($uploadedFiles as $filename) {
                $docModel->create([
                    'expense_id' => $expenseId,
                    'file_path' => $filename,
                    'uploaded_by' => 'client'
                ]);
            }
        }
    }

    // Process existing client-added expenses (update)
    $clientExpenses = $_POST['client_expenses'] ?? [];
    foreach ($clientExpenses as $expenseId => $data) {
        $description = trim($data['description'] ?? '');
        $amount = floatval($data['amount'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if ($description && $amount > 0) {
            // Update expense
            $expenseModel->update($expenseId, [
                'description' => $description,
                'amount' => $amount,
                'client_comment' => $comment
            ]);

            // Handle file uploads
            if (isset($_FILES['client_expenses']['name'][$expenseId]['documents'])) {
                $files = [
                    'name' => $_FILES['client_expenses']['name'][$expenseId]['documents'],
                    'type' => $_FILES['client_expenses']['type'][$expenseId]['documents'],
                    'tmp_name' => $_FILES['client_expenses']['tmp_name'][$expenseId]['documents'],
                    'error' => $_FILES['client_expenses']['error'][$expenseId]['documents'],
                    'size' => $_FILES['client_expenses']['size'][$expenseId]['documents']
                ];

                $uploadedFiles = $fileUpload->uploadMultiple($files);

                foreach ($uploadedFiles as $filename) {
                    $docModel->create([
                        'expense_id' => $expenseId,
                        'file_path' => $filename,
                        'uploaded_by' => 'client'
                    ]);
                }
            }
        }
    }

    // Process NEW client-added expenses
    $newClientExpenses = $_POST['new_client_expenses'] ?? [];
    foreach ($newClientExpenses as $key => $data) {
        $description = trim($data['description'] ?? '');
        $amount = floatval($data['amount'] ?? 0);
        $comment = trim($data['comment'] ?? '');

        if ($description && $amount > 0) {
            // Create new expense with added_by = 'client'
            $newExpenseId = $expenseModel->create([
                'working_paper_id' => $workingPaper['id'],
                'description' => $description,
                'amount' => $amount,
                'client_comment' => $comment,
                'added_by' => 'client'
            ]);

            // Handle file uploads for new expense
            if (isset($_FILES['new_client_expenses']['name'][$key]['documents'])) {
                $files = [
                    'name' => $_FILES['new_client_expenses']['name'][$key]['documents'],
                    'type' => $_FILES['new_client_expenses']['type'][$key]['documents'],
                    'tmp_name' => $_FILES['new_client_expenses']['tmp_name'][$key]['documents'],
                    'error' => $_FILES['new_client_expenses']['error'][$key]['documents'],
                    'size' => $_FILES['new_client_expenses']['size'][$key]['documents']
                ];

                $uploadedFiles = $fileUpload->uploadMultiple($files);

                foreach ($uploadedFiles as $filename) {
                    $docModel->create([
                        'expense_id' => $newExpenseId,
                        'file_path' => $filename,
                        'uploaded_by' => 'client'
                    ]);
                }
            }
        }
    }

    // Handle deletions (client removing their own expenses)
    $deleteExpenses = $_POST['delete_expenses'] ?? [];
    foreach ($deleteExpenses as $expenseId) {
        $expense = $expenseModel->find($expenseId);
        if ($expense && $expense['added_by'] === 'client') {
            $expenseModel->delete($expenseId);
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

        require_once __DIR__ . '/../../app/models/StatusHistory.php';
        $statusHistory = new StatusHistory();
        $statusHistory->create([
            'working_paper_id' => $workingPaper['id'],
            'old_status' => $workingPaper['status'],
            'new_status' => 'submitted',
            'changed_by' => null
        ]);

        header('Location: /public/client/success.php?token=' . $token);
        exit;
    } else {
        header('Location: /public/client/working-paper.php?token=' . $token . '&success=saved');
        exit;
    }
} catch (Exception $e) {
    header('Location: /public/client/working-paper.php?token=' . token . '&error=' . urlencode(e->getMessage()));
    exit;
}