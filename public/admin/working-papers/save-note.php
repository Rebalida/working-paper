<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/Expense.php';

Auth::requireAdmin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

$expenseId = $_POST['expense_id'] ?? null;
$note = trim($_POST['note'] ?? '');

if (!$expenseId || !$note) {
    echo json_encode(['success' => false, 'error' => 'Missing data']);
    exit;
}

try {
    $expenseModel = new Expense();
    
    // Get current internal comment
    $expense = $expenseModel->find($expenseId);
    $currentNote = $expense['internal_comment'] ?? '';
    
    // Append new note with timestamp
    $timestamp = date('Y-m-d H:i');
    $newNote = $currentNote . "\n[Review - $timestamp]: $note";
    
    $expenseModel->addInternalComment($expenseId, $newNote);
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}