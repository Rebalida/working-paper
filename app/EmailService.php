<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;

    public function __construct() {
        $this->mailer = new PHPMailer(true);
        
        // SMTP Configuration
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['MAIL_HOST'];
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['MAIL_USER'];
        $this->mailer->Password = $_ENV['MAIL_PASS'];
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mailer->Port = $_ENV['MAIL_PORT'];
        
        // Sender
        $this->mailer->setFrom('noreply@gmail.com', 'Admin');
        $this->mailer->isHTML(true);
    }

    /**
     * Send initial working paper link to client
     */
    public function sendInitialLink($clientEmail, $clientName, $workingPaper, $token) {
        try {
            $link = $this->getBaseUrl() . "/public/client/working-paper.php?token=" . $token;
            
            $this->mailer->addAddress($clientEmail, $clientName);
            $this->mailer->Subject = 'Working Paper Review Required - Admin';
            
            $body = $this->getInitialLinkTemplate($clientName, $workingPaper, $link);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send returned for revision notification
     */
    public function sendReturnedNotification($clientEmail, $clientName, $workingPaper, $token, $adminNotes = '') {
        try {
            $link = $this->getBaseUrl() . "/public/client/working-paper.php?token=" . $token;
            
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($clientEmail, $clientName);
            $this->mailer->Subject = 'Working Paper Returned for Revision - Admin';
            
            $body = $this->getReturnedTemplate($clientName, $workingPaper, $link, $adminNotes);
            $this->mailer->Body = $body;
            
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Send approval notification
     */
    public function sendApprovalNotification($clientEmail, $clientName, $workingPaper) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($clientEmail, $clientName);
            $this->mailer->Subject = 'Working Paper Approved - Admin';

            $body = $this->getApprovedTemplate($clientName, $workingPaper);
            $this->mailer->Body = $body;

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Email Error: " . $this->mailer->ErrorInfo);
            return false;
        }
    }

    /**
     * Get base URL for links
     */
    private function getBaseUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $protocol . '://' . $host;
    }

    /**
     * Initial link email template
     */
    private function getInitialLinkTemplate($clientName, $workingPaper, $link) {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="margin: 0; font-size: 28px;">EndureGo</h1>
                    <p style="margin: 10px 0 0 0; font-size: 16px;">Working Paper System</p>
                </div>
                
                <div style="background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #333; margin-top: 0;">Working Paper Review Required</h2>
                    
                    <p>Dear ' . htmlspecialchars($clientName) . ',</p>
                    
                    <p>A new working paper is ready for your review. Please review the expenses, add your comments, and upload any supporting documents.</p>
                    
                    <div style="background: white; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0;">
                        <p style="margin: 5px 0;"><strong>Service:</strong> ' . htmlspecialchars($workingPaper['service']) . '</p>
                        <p style="margin: 5px 0;"><strong>Period:</strong> ' . htmlspecialchars($workingPaper['period']) . '</p>
                        <p style="margin: 5px 0;"><strong>Job Reference:</strong> ' . htmlspecialchars($workingPaper['job_reference']) . '</p>
                    </div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $link . '" style="display: inline-block; background: #667eea; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                            Review Working Paper
                        </a>
                    </div>
                    
                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin: 20px 0;">
                        <p style="margin: 0; color: #856404;">
                            <strong>⏰ Important:</strong> This link will expire in <strong>1 hour</strong> for security purposes.
                        </p>
                    </div>
                    
                    <p>If you have any questions, please don\'t hesitate to contact us.</p>
                    
                    <p style="margin-top: 30px;">
                        Best regards,<br>
                        <strong>EndureGo Team</strong>
                    </p>
                </div>
                
                <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
                    <p>This is an automated message from EndureGo Working Paper System.</p>
                </div>
            </body>
            </html>
        ';
    }
    
    /**
     * Returned for revision email template
     */
    private function getReturnedTemplate($clientName, $workingPaper, $link, $adminNotes) {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="margin: 0; font-size: 28px;">EndureGo</h1>
                    <p style="margin: 10px 0 0 0; font-size: 16px;">Working Paper System</p>
                </div>
                
                <div style="background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #dc3545; margin-top: 0;">Working Paper Returned for Revision</h2>
                    
                    <p>Dear ' . htmlspecialchars($clientName) . ',</p>
                    
                    <p>Your working paper has been reviewed and requires some revisions before approval.</p>
                    
                    <div style="background: white; border-left: 4px solid #667eea; padding: 15px; margin: 20px 0;">
                        <p style="margin: 5px 0;"><strong>Service:</strong> ' . htmlspecialchars($workingPaper['service']) . '</p>
                        <p style="margin: 5px 0;"><strong>Period:</strong> ' . htmlspecialchars($workingPaper['period']) . '</p>
                        <p style="margin: 5px 0;"><strong>Job Reference:</strong> ' . htmlspecialchars($workingPaper['job_reference']) . '</p>
                    </div>
                    
                    ' . ($adminNotes ? '
                    <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;">
                        <p style="margin: 0 0 10px 0;"><strong>Review Notes:</strong></p>
                        <p style="margin: 0;">' . nl2br(htmlspecialchars($adminNotes)) . '</p>
                    </div>
                    ' : '') . '
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $link . '" style="display: inline-block; background: #dc3545; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 16px;">
                            Make Revisions
                        </a>
                    </div>
                    
                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; padding: 15px; margin: 20px 0;">
                        <p style="margin: 0; color: #856404;">
                            <strong>⏰ Important:</strong> This new link will expire in <strong>1 hour</strong>.
                        </p>
                    </div>
                    
                    <p>Please review the notes above and make the necessary changes.</p>
                    
                    <p style="margin-top: 30px;">
                        Best regards,<br>
                        <strong>EndureGo Team</strong>
                    </p>
                </div>
                
                <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
                    <p>This is an automated message from EndureGo Working Paper System.</p>
                </div>
            </body>
            </html>
        ';
    }

    /**
     * Approval email template
     */
    private function getApprovedTemplate($clientName, $workingPaper) {
        return '
            <!DOCTYPE html>
            <html>
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
            </head>
            <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;">
                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;">
                    <h1 style="margin: 0; font-size: 28px;">EndureGo</h1>
                    <p style="margin: 10px 0 0 0; font-size: 16px;">Working Paper System</p>
                </div>
                
                <div style="background: #f9f9f9; padding: 30px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 10px 10px;">
                    <h2 style="color: #28a745; margin-top: 0;">✓ Working Paper Approved</h2>
                    
                    <p>Dear ' . htmlspecialchars($clientName) . ',</p>
                    
                    <p>Great news! Your working paper has been reviewed and <strong>approved</strong>.</p>
                    
                    <div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0;">
                        <p style="margin: 5px 0;"><strong>Service:</strong> ' . htmlspecialchars($workingPaper['service']) . '</p>
                        <p style="margin: 5px 0;"><strong>Period:</strong> ' . htmlspecialchars($workingPaper['period']) . '</p>
                        <p style="margin: 5px 0;"><strong>Job Reference:</strong> ' . htmlspecialchars($workingPaper['job_reference']) . '</p>
                        <p style="margin: 5px 0;"><strong>Status:</strong> <span style="color: #28a745; font-weight: bold;">Approved</span></p>
                    </div>
                    
                    <p>Thank you for your cooperation and timely submission.</p>
                    
                    <p style="margin-top: 30px;">
                        Best regards,<br>
                        <strong>EndureGo Team</strong>
                    </p>
                </div>
                
                <div style="text-align: center; padding: 20px; color: #666; font-size: 12px;">
                    <p>This is an automated message from EndureGo Working Paper System.</p>
                </div>
            </body>
            </html>
        ';
    }

}