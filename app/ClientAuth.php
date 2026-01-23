<?php

require_once __DIR__ . '/models/AccessToken.php';
require_once __DIR__ . '/models/WorkingPaper.php';

class ClientAuth {

    /**
     * Verify a client access token
     * Returns working paper data if valid, false otherwise
     */
    public static function verifyToken($token) {
        if (empty($token)) {
            return false;
        }

        $tokenModel = new AccessToken();
        $tokenData = $tokenModel->findByToken($token);
        
        if (!$tokenData) {
            return [
                'valid' => false,
                'error' => 'Invalid token'
            ];
        }

        // Check if expired
        if (strtotime($tokenData['expires_at']) < time()) {
            return [
                'valid' => false,
                'error' => 'Token has expired',
                'expired' => true
            ];
        }

        // Check if already used (for submission)
        // Note: We allow viewing even if used, but not submitting
        
        // Get working paper
        $wpModel = new WorkingPaper();
        $workingPaper = $wpModel->find($tokenData['working_paper_id']);
        
        if (!$workingPaper) {
            return [
                'valid' => false,
                'error' => 'Working paper not found'
            ];
        }

        return [
            'valid' => true,
            'token_data' => $tokenData,
            'working_paper' => $workingPaper
        ];
    }

    /**
     * Check if token allows submission (not used and not expired)
     */
    public static function canSubmit($token) {
        $result = self::verifyToken($token);
        
        if (!$result['valid']) {
            return false;
        }

        // Check if already used
        if ($result['token_data']['used_at'] !== null) {
            return false;
        }

        // Check if status allows submission
        $status = $result['working_paper']['status'];
        if (!in_array($status, ['sent', 'returned'])) {
            return false;
        }

        return true;
    }

    /**
     * Get time remaining for token
     */
    public static function getTimeRemaining($expiresAt) {
        $now = time();
        $expires = strtotime($expiresAt);
        $remaining = $expires - $now;

        if ($remaining <= 0) {
            return [
                'expired' => true,
                'seconds' => 0,
                'minutes' => 0,
                'formatted' => 'Expired'
            ];
        }

        $minutes = floor($remaining / 60);
        $seconds = $remaining % 60;

        return [
            'expired' => false,
            'seconds' => $remaining,
            'minutes' => $minutes,
            'formatted' => sprintf('%02d:%02d', $minutes, $seconds)
        ];
    }
}