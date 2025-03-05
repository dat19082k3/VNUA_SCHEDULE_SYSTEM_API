<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Permission::class => \App\Policies\PermissionPolicy::class,
        \App\Models\PermissionType::class => \App\Policies\PermissionTypePolicy::class,
        \App\Models\PermissionGroup::class => \App\Policies\PermissionGroupPolicy::class,
    ];

    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
            ->subject('Xác minh địa chỉ email của bạn')
            ->greeting('Xin chào ' . $notifiable->name . ',')
            ->line('Chúng tôi vừa nhận được yêu cầu xác minh địa chỉ email của bạn.')
            ->line('Vui lòng nhấn vào nút bên dưới để hoàn tất quá trình xác minh:')
            ->action('Xác minh email', $url)
            ->line('Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.')
            ->line('Liên hệ ngay với chúng tôi nếu bạn cần hỗ trợ.')
            ->salutation('Trân trọng, ' . config('app.name'));
        });
    }
}
