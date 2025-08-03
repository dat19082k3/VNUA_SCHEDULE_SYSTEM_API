<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Services\EmailLogger;
use App\Services\EventNotificationService;
use Illuminate\Console\Command;

class TestBulkMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test-bulk {event_id? : ID of the event to use} {--type=approved : Type of notification (approved, changed, reapproved)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test sending bulk mail notifications for an event';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $eventId = $this->argument('event_id');
        $type = $this->option('type');

        if (!in_array($type, ['approved', 'changed', 'reapproved'])) {
            $this->error('Invalid notification type. Use one of: approved, changed, reapproved');
            return 1;
        }

        // Find event
        $event = null;
        if ($eventId) {
            $event = Event::find($eventId);
            if (!$event) {
                $this->error("Event with ID {$eventId} not found");
                return 1;
            }
        } else {
            $event = Event::first();
            if (!$event) {
                $this->error('No events found in the database');
                return 1;
            }
        }

        $this->info("Using event ID: {$event->id}, Title: {$event->title}");

        // Display event participants information for debugging
        $this->info("Event participants data:");
        $participants = $event->participants;
        $this->info("Participant data type: " . gettype($participants));

        if (is_array($participants)) {
            $this->info("Participants array count: " . count($participants));
            foreach ($participants as $index => $participant) {
                $this->info("Participant {$index}: " . json_encode($participant));
            }
        } else {
            $this->info("Raw participants data: " . print_r($participants, true));
        }

        // Send notification
        $this->info("Sending {$type} notifications...");

        try {
            // Use the notifyAllRelatedUsers method with appropriate notification type
            $result = EventNotificationService::notifyAllRelatedUsers($event, $type);

            $this->info("Notification sent to {$result['success']} recipients");
            $this->info("Recipients: " . implode(", ", $result['sent_to']));

            if (isset($result['failed']) && count($result['failed']) > 0) {
                $this->warn("Failed to send to " . count($result['failed']) . " recipients");
                foreach ($result['failed'] as $email => $error) {
                    $this->error("- {$email}: {$error}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Error sending notification: {$e->getMessage()}");
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
