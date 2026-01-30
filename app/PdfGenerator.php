<?php

use Dompdf\Dompdf;
use Dompdf\Options;

class PdfGenerator {

    /**
     * Generate PDF for a working paper
     */
    public function generateWorkingPaperPdf($workingPaperId) {
        // Get working paper data
        require_once __DIR__ . '/models/WorkingPaper.php';
        require_once __DIR__ . '/models/Client.php';
        require_once __DIR__ . '/models/Expense.php';
        require_once __DIR__ . '/models/ExpenseDocument.php';
        require_once __DIR__ . '/models/StatusHistory.php';
        
        $wpModel = new WorkingPaper();
        $wp = $wpModel->find($workingPaperId);
        
        if (!$wp) {
            throw new Exception('Working paper not found');
        }
        
        $clientModel = new Client();
        $client = $clientModel->find($wp['client_id']);
        
        $expenseModel = new Expense();
        $allExpenses = $expenseModel->getByWorkingPaperId($workingPaperId);
        
        // Separate admin and client expenses
        $adminExpenses = [];
        $clientExpenses = [];
        foreach ($allExpenses as $expense) {
            if ($expense['added_by'] === 'client') {
                $clientExpenses[] = $expense;
            } else {
                $adminExpenses[] = $expense;
            }
        }
        
        $docModel = new ExpenseDocument();
        
        $historyModel = new StatusHistory();
        $history = $historyModel->getByWorkingPaperId($workingPaperId);
        
        // Generate HTML
        $html = $this->generateHtml($wp, $client, $adminExpenses, $clientExpenses, $docModel, $history);
        
        // Configure DomPDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Arial');
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf;
    }

    /**
     * Generate HTML template for PDF
     */
    private function generateHtml($wp, $client, $adminExpenses, $clientExpenses, $docModel, $history) {
        // Combine all expenses
        $allExpenses = array_merge($adminExpenses, $clientExpenses);
        $grandTotal = array_sum(array_column($allExpenses, 'amount'));
        
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    border-bottom: 3px solid #FBAC1B;
                    padding-bottom: 20px;
                }
                .header h1 {
                    margin: 0;
                    color: #FBAC1B;
                    font-size: 28px;
                }
                .header p {
                    margin: 5px 0;
                    color: #666;
                }
                .info-section {
                    margin-bottom: 20px;
                    background: #f9f9f9;
                    padding: 15px;
                    border-left: 4px solid #FBAC1B;
                }
                .info-section h2 {
                    margin: 0 0 10px 0;
                    font-size: 16px;
                    color: #333;
                }
                .info-row {
                    margin: 5px 0;
                }
                .info-label {
                    font-weight: bold;
                    display: inline-block;
                    width: 150px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 20px;
                }
                table th {
                    background: #FBAC1B;
                    color: white;
                    padding: 10px;
                    text-align: left;
                    font-size: 11px;
                }
                table td {
                    padding: 8px;
                    border-bottom: 1px solid #ddd;
                    font-size: 11px;
                }
                table tr:nth-child(even) {
                    background: #f9f9f9;
                }
                .total-row {
                    background: #333 !important;
                    color: white;
                    font-weight: bold;
                    font-size: 13px;
                }
                .badge {
                    display: inline-block;
                    padding: 3px 8px;
                    border-radius: 3px;
                    font-size: 10px;
                    font-weight: bold;
                }
                .badge-admin {
                    background: #6c757d;
                    color: white;
                }
                .badge-client {
                    background: #17a2b8;
                    color: white;
                }
                .badge-status {
                    background: #28a745;
                    color: white;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 15px;
                    border-top: 2px solid #ddd;
                    text-align: center;
                    font-size: 10px;
                    color: #666;
                }
                .page-break {
                    page-break-after: always;
                }
            </style>
        </head>
        <body>
            <!-- Header -->
            <div class="header">
                <h1>Working Paper</h1>
                <p>EndureGo Working Paper System</p>
                <p>Generated on <?= date('F d, Y H:i') ?></p>
            </div>
            
            <!-- Working Paper Information -->
            <div class="info-section">
                <h2>Working Paper Information</h2>
                <div class="info-row">
                    <span class="info-label">Client Name:</span>
                    <?= htmlspecialchars($client['name']) ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Client Email:</span>
                    <?= htmlspecialchars($client['email']) ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Service:</span>
                    <?= htmlspecialchars($wp['service']) ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Job Reference:</span>
                    <?= htmlspecialchars($wp['job_reference']) ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Period:</span>
                    <?= htmlspecialchars($wp['period']) ?>
                </div>
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="badge badge-status"><?= strtoupper($wp['status']) ?></span>
                </div>
                <div class="info-row">
                    <span class="info-label">Created:</span>
                    <?= date('M d, Y H:i', strtotime($wp['created_at'])) ?>
                </div>
            </div>
            
            <!-- Expenses Table -->
            <?php if (!empty($allExpenses)): ?>
                <h2 style="margin: 20px 0 10px 0; font-size: 16px;">Expenses (<?= count($allExpenses) ?>)</h2>
                <table>
                    <thead>
                        <tr>
                            <th width="5%">#</th>
                            <th width="10%">Added By</th>
                            <th width="50%">Description</th>
                            <th width="15%">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allExpenses as $index => $expense): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>
                                    <span class="badge badge-<?= $expense['added_by'] === 'client' ? 'client' : 'admin' ?>">
                                        <?= ucfirst($expense['added_by']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($expense['description']) ?></td>
                                <td>$<?= number_format($expense['amount'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3" style="text-align: right;">TOTAL</td>
                            <td>$<?= number_format($grandTotal, 2) ?></td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <!-- Status History -->
            <?php if (!empty($history)): ?>
                <div class="page-break"></div>
                <div class="info-section">
                    <h2>Status History</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>From Status</th>
                                <th>To Status</th>
                                <th>Changed By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history as $h): ?>
                                <tr>
                                    <td><?= date('M d, Y H:i', strtotime($h['changed_at'])) ?></td>
                                    <td><?= $h['old_status'] ? ucfirst($h['old_status']) : '-' ?></td>
                                    <td><?= ucfirst($h['new_status']) ?></td>
                                    <td><?= htmlspecialchars($h['changed_by_name']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <!-- Footer -->
            <div class="footer">
                <p>This document was generated by EndureGo Working Paper System</p>
                <p>Working Paper ID: <?= $wp['id'] ?> | UUID: <?= $wp['uuid'] ?></p>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }
}