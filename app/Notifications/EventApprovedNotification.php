<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Event $event;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event)
    {
        $this->event = $event;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Thông báo: Sự kiện đã được phê duyệt')
            ->view('emails.events.approved', [
                'event' => $this->event,
                'notifiable' => $notifiable
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'start_time' => $this->event->start_time,
            'end_time' => $this->event->end_time,
            'status' => $this->event->status,
            'type' => 'event_approved',
        ];
    }
}
