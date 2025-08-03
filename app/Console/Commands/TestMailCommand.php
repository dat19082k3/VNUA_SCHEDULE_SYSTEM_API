<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Event;
use App\Notifications\EventApprovedNotification;
use App\Notifications\EventChangedNotification;
use App\Notifications\EventReapprovedNotification;
use App\Services\EmailLogger;
use Illuminate\Console\Command;

class TestMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-all {email? : The email address to send test notifications to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test all email notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $testEmail = $this->argument('email') ?? 'duongtiendat19082003@gmail.com';
        $this->info("Starting email tests for: $testEmail");

        // Find or create test user
        $user = User::where('email', $testEmail)->first();
        $originalEmail = null;

        if (!$user) {
            $user = User::first();
            if (!$user) {
                $this->error('No users found in the system to test with.');
                return 1;
            }

            $originalEmail = $user->email;
            $user->email = $testEmail;
            $this->info("Using existing user and temporarily changing email to: $testEmail");
        }

        // Find test event
        $event = Event::with(['locations', 'preparers', 'creator'])->first();
        if (!$event) {
            $this->error('No events found in the system to test with.');
            return 1;
        }

        try {
            // Test EventApprovedNotification
            $this->info("\nTesting EventApprovedNotification...");
            EmailLogger::logSent($testEmail, "Test EventApprovedNotification", "Sending test notification");
            $user->notify(new EventApprovedNotification($event));
            $this->info("✓ EventApprovedNotification sent successfully");

            // Test EventChangedNotification
            $this->info("\nTesting EventChangedNotification...");
            $editor = User::where('id', '!=', $user->id)->first() ?? $user;
            $changes = [
                'title' => ['old_value' => 'Cuộc họp cũ', 'new_value' => 'Cuộc họp mới'],
                'start_time' => ['old_value' => '2025-07-18 09:00:00', 'new_value' => '2025-07-19 10:00:00']
            ];
            EmailLogger::logSent($testEmail, "Test EventChangedNotification", "Sending test notification");
            $user->notify(new EventChangedNotification($event, $changes, $editor));
            $this->info("✓ EventChangedNotification sent successfully");

            // Test EventReapprovedNotification
            $this->info("\nTesting EventReapprovedNotification...");
            $changes = [
                'title' => ['old_value' => 'Cuộc họp cũ', 'new_value' => 'Cuộc họp mới'],
                'location' => ['old_value' => 'Phòng A', 'new_value' => 'Phòng B']
            ];
            EmailLogger::logSent($testEmail, "Test EventReapprovedNotification", "Sending test notification");
            $user->notify(new EventReapprovedNotification($event, $changes));
            $this->info("✓ EventReapprovedNotification sent successfully");

            // Restore original email if needed
            if ($originalEmail) {
                $user->email = $originalEmail;
                $this->info("\nRestored original user email: $originalEmail");
            }

            $this->info("\nAll email notifications tested successfully!");
            $this->info("Check your inbox at: $testEmail");

            // Show log information
            $logInfo = EmailLogger::checkEmailLog();
            $this->info("\nEmail log information:");
            $this->info("Log file: {$logInfo['path']}");
            $this->info("Size: " . round($logInfo['size'] / 1024, 2) . " KB");
            $this->info("Last modified: " . ($logInfo['last_modified'] ? date('Y-m-d H:i:s', $logInfo['last_modified']) : 'N/A'));

            // Display recent logs if available
            if (!empty($logInfo['recent_logs'])) {
                $this->newLine();
                $this->line('<comment>Recent logs:</comment>');

                foreach ($logInfo['recent_logs'] as $line) {
                    $this->line($line);
                }

                $this->newLine();
                $this->line('For full logs, open the file: <options=bold>' . $logInfo['path'] . '</>');
            } else {
                $this->warn('No recent logs found. Notifications might be queued if using queue driver.');
            }

            return 0;

        } catch (\Exception $e) {
            EmailLogger::logError($testEmail, "Test email notifications", $e);
            $this->error("\nError occurred while testing notifications:");
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());

            // Restore original email if needed
            if ($originalEmail) {
                $user->email = $originalEmail;
            }

            return 1;
        }
    }
}
