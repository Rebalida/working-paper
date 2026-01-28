<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
require_once __DIR__ . '/../../../app/models/Client.php';
require_once __DIR__ . '/../../../app/models/AccessToken.php';
require_once __DIR__ . '/../../../app/models/StatusHistory.php';
require_once __DIR__ . '/../../../app/EmailService.php';

Auth::requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /public/admin/dashboard.php');
    exit;
}

$user = Auth::user();
$wpId = $_POST['working_paper_id'] ?? null;
$decision = $_POST['decision'] ?? '';
$adminNotes = trim($_POST['admin_notes'] ?? '');

if (!$wpId || !in_array($decision, ['approve', 'return'])) {
    header('Location: /public/admin/dashboard.php?error=invalid_request');
    exit;
}

try {
    // Get working paper
    $wpModel = new WorkingPaper();
    $wp = $wpModel->find($wpId);
    
    if (!$wp) {
        throw new Exception('Working paper not found');
    }

    // Get client
    $clientModel = new Client();
    $client = $clientModel->find($wp['client_id']);

    $emailService = new EmailService();

    if ($decision === 'approve') {
        // APPROVE the working paper
        
        // Update status
        $wpModel->updateStatus($wpId, 'approved', $user['id']);

        // Send approval email to client
        $emailService->sendApprovalNotification(
            $client['email'],
            $client['name'],
            $wp
        );

        // Redirect with success
        header('Location: /public/admin/working-papers/view.php?id=' . $wpId . '&success=approved');
        exit;

    } elseif ($decision === 'return') {
        // RETURN for revision
        
        // Update status
        $wpModel->updateStatus($wpId, 'returned', $user['id']);

        // Generate new access token
        $tokenModel = new AccessToken();
        $newToken = $tokenModel->generateToken($wpId, 1); 

        // Send returned email with new link
        $emailService->sendReturnedNotification(
            $client['email'],
            $client['name'],
            $wp,
            $newToken,
            $adminNotes
        );

        // Redirect with success
        header('Location: /public/admin/working-papers/view.php?id=' . $wpId . '&success=returned');
        exit;
    }

} catch (Exception $e) {
    header('Location: /public/admin/working-papers/review.php?id=' . $wpId . '&error=' . urlencode($e->getMessage()));
    exit;
}