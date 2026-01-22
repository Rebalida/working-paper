<?php

require_once __DIR__ . '/../Model.php';

class WorkingPaper extends Model {
    protected $table = 'working_papers';

    /**
     * Find by UUID
     */
    public function findByUuid($uuid) {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE uuid = ?", [$uuid]);
        return $stmt->fetch();
    }

    /**
     * Get all working papers with client information
     */
    public function getAllWithClient() {
        $sql = "
            SELECT 
                wp.*,
                c.name as client_name,
                c.email as client_email
            FROM {$this->table} wp
            LEFT JOIN clients c ON wp.client_id = c.id
            ORDER BY wp.created_at DESC
        ";
        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Get working papers by status
     */
    public function getByStatus($status) {
        $sql = "
            SELECT 
                wp.*,
                c.name as client_name,
                c.email as client_email
            FROM {$this->table} wp
            LEFT JOIN clients c ON wp.client_id = c.id
            WHERE wp.status = ?
            ORDER BY wp.created_at DESC
        ";
        $stmt = $this->query($sql, [$status]);
        return $stmt->fetchAll();
    }

    /**
     * Update status
     */
    public function updateStatus($id, $newStatus, $userId) {
        $current = $this->find($id);
        $oldStatus = $current['status'];

        $this->update($id, ['status' => $newStatus]);

        require_once __DIR__ . '/StatusHistory.php';
        $statusHistory = new StatusHistory();
        $statusHistory->create([
            'working_paper_id' => $id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId
        ]);

        return true;
    }
}