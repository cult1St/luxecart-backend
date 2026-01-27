<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Mail Service
 *
 * Handles email sending operations
 */
class MailService
{
    /**
     * Send Email (static entry point)
     */
    public static function send(
        string $to,
        string $subject,
        string $message,
        string $from = 'Frisan Admin',
        string $messageType = 'html'
    ): bool {
        return true;
        try {
            $mailer = new self();
            return $mailer->phpmailer($to, $subject, $message, $from, $messageType);
        } catch (\Throwable $e) {
            error_log('Mail error: ' . $e->getMessage());
            throw new Exception('Failed to send email: ' . $e->getMessage());
            
        }
        return false;
    }

    /**
     * PHPMailer implementation
     */
    protected function phpmailer(
        string $to,
        string $subject,
        string $body,
        string $from,
        string $messageType = 'html'
    ): bool {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = env('MAIL_HOST') ?? 'smtp.frisan.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME') ?? 'user@frisan.com';
            $mail->Password   = env('MAIL_PASSWORD') ?? 'secret';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = env('MAIL_PORT') ?? 587;

            // Sender
            $mail->setFrom(
                env('MAIL_FROM_ADDRESS') ?? 'no-reply@frisan.com',
                $from
            );

            // Recipient
            $mail->addAddress($to);

            // Content
            $mail->Subject = $subject;

            if ($messageType === 'html') {
                $mail->isHTML(true);
                $mail->Body = $body;
                $mail->AltBody = strip_tags($body);
            } else {
                $mail->Body = $body;
            }

            return $mail->send();

        } catch (Exception $e) {
            error_log('PHPMailer error: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
