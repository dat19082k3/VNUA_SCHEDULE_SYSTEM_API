<?php

namespace App\Services;

use App\Mail\EventNotificationMail;
use App\Models\Event;
use App\Models\User;
use App\Services\EmailLogger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EventNotificationService
{
    /**
     * Test email address to always include in notifications
     *
     * @var string
     */
    protected const TEST_EMAIL = 'dat19082k3@gmail.com';

    /**
     * Valid notification types
     */
    public const NOTIFICATION_TYPE_APPROVED = 'approved';
    public const NOTIFICATION_TYPE_CHANGED = 'changed';
    public const NOTIFICATION_TYPE_REAPPROVED = 'reapproved';

    /**
     * Send event notification to all related users
     *
     * @param Event $event The event instance
     * @param string $notificationType Type of notification: 'approved', 'changed', 'reapproved'
     * @param array $additionalData Additional data for the notification (like changes, editor)
     * @return array Information about sent emails
     */
    public static function notifyAllRelatedUsers(Event $event, string $notificationType, array $additionalData = []): array
    {
        // Validate notification type
        if (!in_array($notificationType, [
            self::NOTIFICATION_TYPE_APPROVED,
            self::NOTIFICATION_TYPE_CHANGED,
            self::NOTIFICATION_TYPE_REAPPROVED
        ])) {
            throw new \InvalidArgumentException("Invalid notification type: {$notificationType}");
        }

        $recipients = self::getRelatedUsers($event);
        $sentCount = 0;
        $errorCount = 0;
        $sentTo = [];
        $failedEmails = [];

        // Get test user or create a placeholder
        $testUser = User::where('email', self::TEST_EMAIL)->first();
        if (!$testUser) {
            $testUser = new User([
                'name' => 'Test User',
                'email' => self::TEST_EMAIL
            ]);
        }

        // Always add test email if not already in recipients
        if (!$recipients->contains('email', self::TEST_EMAIL)) {
            $recipients->push($testUser);
        }

        foreach ($recipients as $user) {
            try {
                // Log before sending
                EmailLogger::logSent(
                    $user->email,
                    "Event Notification: {$notificationType}",
                    "Preparing to send {$notificationType} notification for event #{$event->id}"
                );

                // Send the email
                Mail::to($user->email)
                    ->send(new EventNotificationMail($event, $notificationType, $user, $additionalData));

                // Log success
                EmailLogger::logSent(
                    $user->email,
                    "Event Notification: {$notificationType}",
                    "Successfully sent {$notificationType} notification for event #{$event->id}"
                );

                $sentCount++;
                $sentTo[] = $user->email;

            } catch (\Exception $e) {
                // Log error
                EmailLogger::logError(
                    $user->email,
                    "Event Notification: {$notificationType}",
                    $e
                );

                $errorCount++;
                $failedEmails[$user->email] = $e->getMessage();

                Log::error("Failed to send {$notificationType} notification", [
                    'event_id' => $event->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'sent_count' => $sentCount,
            'error_count' => $errorCount,
            'sent_to' => $sentTo,
            'failed' => $failedEmails,
            'notification_type' => $notificationType,
            'success' => $sentCount // Alias for easier access
        ];
    }

    /**
     * Get all users related to an event
     *
     * @param Event $event
     * @return Collection
     */
    protected static function getRelatedUsers(Event $event): Collection
    {
        $users = new Collection();

        // Add creator if exists
        if ($event->creator) {
            $users->push($event->creator);
        }

        // Add user participants
        try {
            // Get participants data - safely handling all possible formats
            $participants = [];

            // Method 1: Direct access with explicit array check
            if (isset($event->participants) && is_array($event->participants)) {
                $participants = $event->participants;
            }
            // Method 2: Try to access raw data from the database
            else if (method_exists($event, 'getRawOriginal')) {
                $rawValue = $event->getRawOriginal('participants');
                if (is_string($rawValue)) {
                    $decoded = json_decode($rawValue, true);
                    if (is_array($decoded)) {
                        $participants = $decoded;
                    }
                }
            }
            // Method 3: Last resort - try to convert to string and parse
            else if (isset($event->participants)) {
                $stringValue = (string)$event->participants;
                if (!empty($stringValue)) {
                    $decoded = json_decode($stringValue, true);
                    if (is_array($decoded)) {
                        $participants = $decoded;
                    }
                }
            }

            // Process participants if we have any
            if (!empty($participants)) {
                $userParticipants = array_filter($participants, function($p) {
                    return isset($p['type']) && $p['type'] === 'user' && isset($p['id']);
                });

                foreach ($userParticipants as $participant) {
                    if (isset($participant['id'])) {
                        $user = User::find($participant['id']);
                        if ($user && !$users->contains('id', $user->id)) {
                            $users->push($user);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error processing event participants", [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        // Add department users if they have user_department relation
        if ($event->preparers) {
            foreach ($event->preparers as $department) {
                // Check if there's a users relationship (Many-to-Many)
                if (method_exists($department, 'users')) {
                    $departmentUsers = $department->users;
                    foreach ($departmentUsers as $user) {
                        if (!$users->contains('id', $user->id)) {
                            $users->push($user);
                        }
                    }
                }
            }
        }

        return $users->unique('id');
    }
}
