<?php

namespace Helpers;

use App\Services\MailService;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Mailer Helper
 * 
 * Handles email sending using PHPMailer
 * Configure SMTP settings in your .env or environment
 */
class Mailer
{
    protected PHPMailer $mailer;
    protected string $from;
    protected string $fromName;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        // SMTP Configuration
        $this->mailer->isSMTP();
        $this->mailer->Host = $_ENV['MAIL_HOST'] ?? 'smtp.mailtrap.io';
        $this->mailer->Port = $_ENV['MAIL_PORT'] ?? 2525;
        $this->mailer->SMTPAuth = true;
        $this->mailer->Username = $_ENV['MAIL_USERNAME'] ?? '';
        $this->mailer->Password = $_ENV['MAIL_PASSWORD'] ?? '';
        $this->mailer->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? 'tls';

        // From address
        $this->from = $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@luxecart.com';
        $this->fromName = $_ENV['MAIL_FROM_NAME'] ?? 'Luxecart';
    }

    /**
     * Send email verification code
     * 
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @param string $code 6-digit verification code
     * @return bool True if email sent successfully
     */
    public function sendVerificationCode(string $email, string $name, string $code): bool
    {
        try {
          
            $subject = 'Verify Your Email Address - Luxecart';
            // HTML email body
            $body = $this->getVerificationEmailTemplate($name, $code);
            return MailService::queue($email, $subject, $body);

        } catch (Exception $e) {
            error_log("Email sending failed: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    /**
     * Send welcome email after verification
     * 
     * @param string $email Recipient email
     * @param string $name Recipient name
     * @return bool True if email sent successfully
     */
    public function sendWelcomeEmail(string $email, string $name): bool
    {
        try {
            $body = $this->getWelcomeEmailTemplate($name);
           //utilize the mail service queue
           return MailService::queue($email, "Welcome To Luxecart", $body, $this->from, "html");
        } catch (Exception $e) {
            error_log("Email sending failed: {$this->mailer->ErrorInfo}");
            return false;
        }
    }

    /**
     * Get HTML template for verification email
     * 
     * @param string $name User name
     * @param string $code Verification code
     * @return string HTML email content
     */
    private function getVerificationEmailTemplate(string $name, string $code): string
    {
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .code-box { background: white; border: 2px solid #667eea; padding: 20px; text-align: center; margin: 20px 0; border-radius: 5px; }
                .code { font-size: 32px; font-weight: bold; letter-spacing: 5px; color: #667eea; }
                .footer { color: #666; font-size: 12px; margin-top: 20px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
                .warning { color: #e74c3c; font-size: 12px; margin-top: 10px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Verify Your Email</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>{$name}</strong>,</p>
                    
                    <p>Thank you for signing up for Luxecart! To complete your registration, please verify your email address using the code below:</p>
                    
                    <div class="code-box">
                        <div class="code">{$code}</div>
                    </div>
                    
                    <p>Please enter this code in your verification page. Your verification code is valid for <strong>15 minutes</strong>.</p>
                    
                    <p><strong>Didn't sign up for Luxecart?</strong> If this wasn't you, you can ignore this email.</p>
                    
                    <div class="footer">
                        <p>© 2026 Luxecart. All rights reserved.</p>
                        <p>This is an automated message, please do not reply to this email.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    /**
     * Get HTML template for welcome email
     * 
     * @param string $name User name
     * @return string HTML email content
     */
    private function getWelcomeEmailTemplate(string $name): string
    {
        $year = date('Y');
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 5px 5px; }
                .button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { color: #666; font-size: 12px; margin-top: 20px; text-align: center; border-top: 1px solid #ddd; padding-top: 20px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>Welcome to Luxecart!</h1>
                </div>
                <div class="content">
                    <p>Hi <strong>{$name}</strong>,</p>
                    
                    <p>Your email has been successfully verified! Your Luxecart account is now active and ready to use.</p>
                    
                    <p>You can now:</p>
                    <ul>
                        <li>Browse our extensive product catalog</li>
                        <li>Add items to your cart</li>
                        <li>Make purchases</li>
                        <li>Track your orders</li>
                        <li>Manage your account</li>
                    </ul>
                    
                    <p>If you have any questions or need assistance, feel free to contact our support team.</p>
                    
                    <div class="footer">
                        <p>© {$year} Luxecart. All rights reserved.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        HTML;
    }
}
