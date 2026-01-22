<?php

require_once __DIR__ . '/../Model.php';

class ExpenseDocument extends Model {
    protected $table = 'expense_documents';

    /**
     * Get all documents for an expense
     */
    public function getByExpenseId($expenseId) {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE expense_id = ? ORDER BY created_at ASC", 
            [$expenseId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Get all documents for a working paper
     */
    public function getByWorkingPaperId($workingPaperId) {
        $sql = "
            SELECT ed.* 
            FROM {$this->table} ed
            JOIN expenses e ON ed.expense_id = e.id
            WHERE e.working_paper_id = ?
            ORDER BY ed.created_at ASC
        ";
        $stmt = $this->query($sql, [$workingPaperId]);
        return $stmt->fetchAll();
    }

    /**
     * Delete all documents for an expense
     */
    public function deleteByExpenseId($expenseId) {
        $stmt = $this->query("DELETE FROM {$this->table} WHERE expense_id = ?", [$expenseId]);
        return $stmt->rowCount();
    }
}