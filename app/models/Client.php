<?php

require_once __DIR__ . '/../Model.php';

class Client extends Model {
    protected $table = 'clients';

    /**
     * Find a client by email
     */
    public function findByEmail($email) {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE email = ?", [$email]);
        return $stmt->fetch();
    }

    /**
     * Get all working papers for this client
     */
    public function getWorkingPapers($clientId) {
        $stmt = $this->query(
            "SELECT * FROM working_papers WHERE client_id = ? ORDER BY created_at DESC", 
            [$clientId]
        );
        return $stmt->fetchAll();
    }
}