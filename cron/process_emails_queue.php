#!/usr/bin/env php
<?php

use App\Services\MailService;

/**
 * Email Queue Processor
 *
 * Sends queued emails from storage/queue/emails/.
 * Should be scheduled to run every minute via cron:
 *
 *   crontab -e
 *   * * * * * php /path/to/frisan/cron/process-email-queue.php >> /path/to/frisan/storage/logs/cron.log 2>&1
 */

require_once __DIR__ . '/../bootstrap.php';

$result = MailService::processQueue();

dd($result);
if ($result['sent'] > 0 || $result['failed'] > 0) {
    echo '[' . date('Y-m-d H:i:s') . "] Email queue: {$result['sent']} sent, {$result['failed']} failed." . PHP_EOL;
}
