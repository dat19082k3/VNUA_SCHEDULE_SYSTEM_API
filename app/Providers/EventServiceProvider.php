<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        // Thêm các event cho notification
        NotificationSent::class => [
            // Xử lý khi thông báo được gửi thành công
        ],

        NotificationFailed::class => [
            // Xử lý khi thông báo gửi thất bại
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Đăng ký listener cho sự kiện notification đã gửi thành công
        Event::listen(
            NotificationSent::class,
            function (NotificationSent $event) {
                // Ghi log thông báo đã gửi thành công
                Log::channel('email')->info('Thông báo đã được gửi thành công', [
                    'channel' => $event->channel,
                    'notifiable' => get_class($event->notifiable),
                    'notifiable_id' => $event->notifiable->id ?? null,
                    'notifiable_email' => $event->notifiable->email ?? null,
                    'notification' => get_class($event->notification),
                    'response' => $event->response,
                    'time' => now()->format('Y-m-d H:i:s')
                ]);
            }
        );

        // Đăng ký listener cho sự kiện notification gửi thất bại
        Event::listen(
            NotificationFailed::class,
            function (NotificationFailed $event) {
                // Ghi log thông báo gửi thất bại
                Log::channel('email')->error('Thông báo gửi thất bại', [
                    'channel' => $event->channel,
                    'notifiable' => get_class($event->notifiable),
                    'notifiable_id' => $event->notifiable->id ?? null,
                    'notifiable_email' => $event->notifiable->email ?? null,
                    'notification' => get_class($event->notification),
                    'error' => 'Không gửi được thông báo',
                    'time' => now()->format('Y-m-d H:i:s')
                ]);
            }
        );
    }
}
