<?php

require_once __DIR__ . '/../Model.php';

class StatusHistory extends Model {
    protected $table = 'status_history';

    /**
     * Get history for a working paper
     */
    public function getByWorkingPaperId($workingPaperId) {
        $sql = "
            SELECT 
                sh.*,
                CASE 
                    WHEN sh.changed_by IS NULL THEN 'Client (System)'
                    ELSE u.name
                END as changed_by_name,
                u.email as changed_by_email
            FROM {$this->table} sh
            LEFT JOIN users u ON sh.changed_by = u.id
            WHERE sh.working_paper_id = ?
            ORDER BY sh.changed_at DESC
        ";
        $stmt = $this->query($sql, [$workingPaperId]);
        return $stmt->fetchAll();
    }

    /**
     * Get latest status change
     */
    public function getLatest($workingPaperId) {
        $sql = "
            SELECT 
                sh.*,
                CASE 
                    WHEN sh.changed_by IS NULL THEN 'Client (System)'
                    ELSE u.name
                END as changed_by_name
            FROM {$this->table} sh
            LEFT JOIN users u ON sh.changed_by = u.id
            WHERE sh.working_paper_id = ?
            ORDER BY sh.changed_at DESC
            LIMIT 1
        ";
        $stmt = $this->query($sql, [$workingPaperId]);
        return $stmt->fetch();
    }

    /**
     * Get changed by name (handle system/client submissions)
     */
    public function getChangedByName($changedById) {
        if ($changedById == 0) {
            return 'Client (System)';
        }
        
        require_once __DIR__ . '/User.php';
        $userModel = new User();
        $user = $userModel->find($changedById);
        
        return $user ? $user['name'] : 'Unknown';
    }
}