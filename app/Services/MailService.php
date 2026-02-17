<?php

namespace App\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

class MailService
{
    const QUEUE_DIR = BASE_PATH . "/storage/queue/emails";


    public static function processQueue(): array
    {
        $dir = self::QUEUE_DIR;
        if (!is_dir($dir)) {
            return ['sent' => 0, 'failed' => 0];
        }
        $files = glob($dir .'/mail_*.json');
        $limit = 50;
        $sent = 0;
        $failed = 0;
        for( $i = 0; $i < min($limit, count($files)); $i++ ){
            $file = $files[$i];
            $jobJson = file_get_contents($file);
            if(!$jobJson || empty($jobJson)) {
                continue;
            }
            $job = json_decode($jobJson, true);
            if(!$job || empty($job)){
                continue;
            }
            ///process email sending
            try{
                self::send(
                    $job['to'], 
                    $job['subject'], 
                    $job['body'], 
                    $job['from'], 
                    $job['message_type']
                );
                unlink($file);
                $sent++;
            }catch(Throwable $e){
                error_log("Queue mail failed [{$job['to']}]: " . $e->getMessage());
                $failed++;
                //get total attempts
                $attempts = isset($job['attempts']) && !empty($job['attempts']) ? $job['attempts'] :0;
                $attempts++;
                if($attempts >= 3){
                    //save to failed
                    $failedDir = self::QUEUE_DIR ."/failed/";
                    if(!is_dir($failedDir)) {
                        mkdir($failedDir, 0755, true);
                    }
                    rename($file, $failedDir . basename($file));
                }else{
                    $job['attempts'] = $attempts;
                    file_put_contents($file, json_encode($job, JSON_PRETTY_PRINT));
                }
            }
        }
        
        return ['sent' => $sent, 'failed' => $failed];
    }

    /**
     * Create a queue
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $from
     * @param string $messageType
     * @return bool
     */
    public static function queue(
        string $to,
        string $subject,
        string $body,
        string $from = "LuxeCart Admin",
        string $messageType = "html"
    ) {
        //create queue process
        if (!is_dir(self::QUEUE_DIR)) {
            mkdir(self::QUEUE_DIR, 0777, true);
        }

        $job = [
            "to" => $to,
            "subject" => $subject,
            "body" => $body,
            "from" => $from,
            "message_type" => $messageType,
            "created_at" => date("Y-m-d H:i:s"),
        ];

        $filename = self::QUEUE_DIR . "/" . uniqid("mail_", true) . ".json";
        return file_put_contents($filename, json_encode($job, JSON_PRETTY_PRINT)) != false;
    }

    /**
     * Static method to send emails
     * @param string $to
     * @param string $subject
     * @param string $message
     * @param string $from
     * @param string $messageType
     * @throws Exception
     * @return bool
     */
    public static function send(
        string $to,
        string $subject,
        string $message,
        string $from = "LuxeCart Admin",
        string $messageType = "html"
    ) {
        try {
            $mailer = new self();
            return $mailer->phpmailer($to, $subject, $message, $from, $messageType);
        } catch (Throwable $e) {
            error_log('Mail error: ' . $e->getMessage());
            // Re-throw as-is to let controller format the error
            throw new Exception($e->getMessage());
        }
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
            $mail->Host       = env('MAIL_HOST') ?? 'smtp.luxecart.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = env('MAIL_USERNAME') ?? 'user@luxecart.com';
            $mail->Password   = env('MAIL_PASSWORD') ?? 'secret';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = env('MAIL_PORT') ?? 587;

            // Sender
            $mail->setFrom(
                env('MAIL_FROM_ADDRESS') ?? 'no-reply@luxecart.com',
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
        } catch (Throwable $e) {
            error_log('PHPMailer error: ' . $mail->ErrorInfo);
            throw $e;
        }
    }
}
