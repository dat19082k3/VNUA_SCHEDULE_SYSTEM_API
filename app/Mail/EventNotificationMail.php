<?php

namespace App\Mail;

use App\Models\Event;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class EventNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The event instance.
     *
     * @var Event
     */
    public $event;

    /**
     * The notification data.
     *
     * @var array
     */
    public $data;

    /**
     * The notification type.
     *
     * @var string
     */
    public $type;

    /**
     * The user receiving the notification.
     *
     * @var User|null
     */
    public $notifiable;

    /**
     * Create a new message instance.
     */
    public function __construct(Event $event, string $type, User $notifiable = null, array $data = [])
    {
        $this->event = $event;
        $this->type = $type;
        $this->notifiable = $notifiable;
        $this->data = $data;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $subject = match ($this->type) {
            'approved' => 'Thông báo: Sự kiện đã được phê duyệt',
            'changed' => 'Thông báo: Sự kiện đã được cập nhật',
            'reapproved' => 'Thông báo: Sự kiện đã được phê duyệt lại',
            default => 'Thông báo về sự kiện: ' . $this->event->title,
        };

        $view = 'emails.events.' . $this->type;

        // Combine event data with additional data
        $viewData = array_merge([
            'event' => $this->event,
            'notifiable' => $this->notifiable,
        ], $this->data);

        return $this->subject($subject)
                    ->view($view, $viewData);
    }
}
