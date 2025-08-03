<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class EmailLogger
{
    /**
     * Log file path for email logs
     */
    private static function getLogPath(): string
    {
        return storage_path('logs/email.log');
    }

    /**
     * Log when an email is sent
     */
    public static function logSent(string $to, string $subject, string $message): void
    {
        $logMessage = sprintf(
            "[%s] Email sent to %s - Subject: %s - Message: %s",
            now()->format('Y-m-d H:i:s'),
            $to,
            $subject,
            $message
        );

        Log::channel('email')->info($logMessage);
    }

    /**
     * Log when there's an error sending email
     */
    public static function logError(string $to, string $subject, \Exception $error): void
    {
        $logMessage = sprintf(
            "[%s] Error sending email to %s - Subject: %s - Error: %s\n%s",
            now()->format('Y-m-d H:i:s'),
            $to,
            $subject,
            $error->getMessage(),
            $error->getTraceAsString()
        );

        Log::channel('email')->error($logMessage);
    }

    /**
     * Check email log file and return its information
     */
    public static function checkEmailLog(): array
    {
        $logPath = self::getLogPath();
        $exists = file_exists($logPath);

        return [
            'path' => $logPath,
            'exists' => $exists,
            'size' => $exists ? filesize($logPath) : 0,
            'last_modified' => $exists ? filemtime($logPath) : null,
            'recent_logs' => $exists ? array_slice(file($logPath), -20) : [] // Get last 20 lines
        ];
    }
}
