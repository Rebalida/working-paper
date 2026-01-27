<?php

require_once __DIR__ . '/../Model.php';

class AccessToken extends Model {
    protected $table = 'access_tokens';

    /**
     * Find by token string
     */
    public function findByToken($token) {
        $stmt = $this->query("SELECT * FROM {$this->table} WHERE token = ?", [$token]);
        return $stmt->fetch();
    }

    /**
     * Check if token is valid (not expired and not used)
     */
    public function isValid($token) {
        $tokenData = $this->findByToken($token);

        if (!$tokenData) {
            return false;
        }

        if (strtotime($tokenData['expires_at']) < time()) {
            return false;
        }

        if ($tokenData['used_at'] !== null) {
            return false;
        }

        return true;
    }

    /**
     * Mark token as used
     */
    public function markAsUsed($token) {
        $tokenData = $this->findByToken($token);
        if ($tokenData) {
            return $this->update($tokenData['id'], [
                'used_at' => date('Y-m-d H:i:s')
            ]);
        }
        return false;
    }

    /**
     * Generate a new token for a working paper
     */
    public function generateToken($workingPaperId, $expiryHours = 1) {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$expiryHours} hours"));

        $this->create([
            'working_paper_id' => $workingPaperId,
            'token' => $token,
            'expires_at' => $expiresAt
        ]);

        return $token;
    }

    /**
     * Get active token for a working paper
     */
    public function getActiveToken($workingPaperId) {
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE working_paper_id = ? 
            AND expires_at > NOW() 
            AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ";
        $stmt = $this->query($sql, [$workingPaperId]);
        return $stmt->fetch();
    }

    public function getAllByWorkingPaperId($workingPaperId) {
        $stmt = $this->query(
            "SELECT * FROM access_tokens WHERE working_paper_id = ? ORDER BY created_at DESC",
            [$workingPaperId]
        );

        return $stmt->fetchAll();
    }
}