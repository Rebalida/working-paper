<?php

require_once __DIR__ . '/../Model.php';

class Expense extends Model {
    protected $table = 'expenses';

    /**
     * Get all expenses for a working paper
     */
    public function getByWorkingPaperId($workingPaperId) {
        $stmt = $this->query(
            "SELECT * FROM {$this->table} WHERE working_paper_id = ? ORDER BY created_at ASC", 
            [$workingPaperId]
        );
        return $stmt->fetchAll();
    }

    /**
     * Get expenses with their documents
     */
    public function getWithDocuments($workingPaperId) {
        $sql = "
            SELECT 
                e.*,
                GROUP_CONCAT(ed.id) as document_ids,
                GROUP_CONCAT(ed.file_path) as document_paths
            FROM {$this->table} e
            LEFT JOIN expense_documents ed ON e.id = ed.expense_id
            WHERE e.working_paper_id = ?
            GROUP BY e.id
            ORDER BY e.created_at ASC
        ";
        $stmt = $this->query($sql, [$workingPaperId]);
        return $stmt->fetchAll();
    }

    /**
     * Add client comment to expense
     */
    public function addClientComment($id, $comment) {
        return $this->update($id, ['client_comment' => $comment]);
    }

    /**
     * Add internal comment to expense
     */
    public function addInternalComment($id, $comment) {
        return $this->update($id, ['internal_comment' => $comment]);
    }
}