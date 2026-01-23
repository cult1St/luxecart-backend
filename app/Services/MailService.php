<?php 

namespace App\Services;

/**
 * Mail Service
 * 
 * Handles email sending operations
 */
class MailService
{
    /**
     * Send Email
     */
    public static function send($to, $subject, $message, $from = 'Frisan Admin', $message_type = ""){
        // Placeholder for mail sending logic
        //for now log emails
        error_log("Sending email to {$to}: Subject: {$subject}, Message: {$message}");
        return true;
    }
}