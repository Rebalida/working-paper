<?php

require_once __DIR__ . '/../../../vendor/autoload.php';
require_once __DIR__ . '/../../../app/Auth.php';
require_once __DIR__ . '/../../../app/PdfGenerator.php';

Auth::requireAdmin();

$wpId = $_GET['id'] ?? null;

if (!$wpId) {
    header('Location: /public/admin/dashboard.php');
    exit;
}

try {
    $pdfGenerator = new PdfGenerator();
    $dompdf = $pdfGenerator->generateWorkingPaperPdf($wpId);
    
    // Get working paper for filename
    require_once __DIR__ . '/../../../app/models/WorkingPaper.php';
    $wpModel = new WorkingPaper();
    $wp = $wpModel->find($wpId);
    
    $filename = 'WorkingPaper_' . $wp['job_reference'] . '_' . $wp['period'] . '.pdf';
    
    // Output PDF
    $dompdf->stream($filename, ['Attachment' => true]);
    
} catch (Exception $e) {
    header('Location: /public/admin/working-papers/view.php?id=' . $wpId . '&error=' . urlencode($e->getMessage()));
    exit;
}