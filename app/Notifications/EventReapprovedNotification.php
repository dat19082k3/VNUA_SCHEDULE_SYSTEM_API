<?php

namespace App\Notifications;

use App\Models\Event;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EventReapprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Event $event;
    protected array $changes;

    /**
     * Create a new notification instance.
     */
    public function __construct(Event $event, array $changes = [])
    {
        $this->event = $event;
        $this->changes = $changes;
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
            ->subject('Thông báo: Sự kiện đã được phê duyệt lại')
            ->view('emails.events.reapproved', [
                'event' => $this->event,
                'changes' => $this->changes,
                'notifiable' => $notifiable
            ]);
    }

    /**
     * Get human-readable field name
     */
    protected function getFieldName(string $field): string
    {
        $mapping = [
            'title' => 'Tiêu đề',
            'description' => 'Mô tả',
            'start_time' => 'Thời gian bắt đầu',
            'end_time' => 'Thời gian kết thúc',
            'host' => 'Người chủ trì',
            'participants' => 'Người tham gia',
            'status' => 'Trạng thái',
            'reminder_type' => 'Loại nhắc nhở',
            'reminder_time' => 'Thời gian nhắc nhở',
            'locations' => 'Địa điểm',
            'preparers' => 'Đơn vị chuẩn bị',
            'attachments' => 'Tệp đính kèm',
        ];

        return $mapping[$field] ?? $field;
    }

    /**
     * Format value for display
     */
    protected function formatValue(string $field, $value): string
    {
        if ($field === 'start_time' || $field === 'end_time' || $field === 'reminder_time') {
            return $value ? Carbon::parse($value)->format('d/m/Y H:i') : 'không có';
        }

        if (in_array($field, ['locations', 'preparers', 'attachments', 'participants'])) {
            if (is_string($value) && $this->isJson($value)) {
                $decodedValue = json_decode($value, true);
                if (is_array($decodedValue)) {
                    if ($field === 'participants') {
                        return $this->formatParticipants($decodedValue);
                    }
                    return implode(', ', $decodedValue);
                }
            }
        }

        if ($field === 'status') {
            $statusMap = [
                'pending' => 'Chờ phê duyệt',
                'approved' => 'Đã phê duyệt',
                'declined' => 'Đã từ chối',
            ];
            return $statusMap[$value] ?? $value;
        }

        return (string) $value;
    }

    /**
     * Format participants array for display
     */
    protected function formatParticipants(array $participants): string
    {
        $result = [];
        foreach ($participants as $participant) {
            $type = $participant['type'] ?? '';
            $id = $participant['id'] ?? '';

            if ($type === 'user') {
                $user = User::find($id);
                $result[] = $user ? $user->name : "Người dùng #$id";
            } elseif ($type === 'department') {
                $dept = \App\Models\Department::find($id);
                $result[] = $dept ? $dept->name : "Phòng ban #$id";
            }
        }

        return implode(', ', $result);
    }

    /**
     * Check if string is valid JSON
     */
    protected function isJson($string): bool
    {
        if (!is_string($string)) return false;
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'event_id' => $this->event->id,
            'title' => $this->event->title,
            'changes' => $this->changes,
            'type' => 'event_reapproved',
        ];
    }
}
