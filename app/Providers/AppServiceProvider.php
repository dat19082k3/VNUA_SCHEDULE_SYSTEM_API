<?php

namespace App\Providers;

use App\Services\DepartmentService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(optional($request->user())->id ?: $request->ip());
        });
    }

    protected function configureLogging()
    {
        // Log SQL queries trong môi trường dev
        if (config('app.debug')) {
            DB::listen(function ($query) {
                Log::debug(
                    'SQL Query',
                    [
                        'query' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                    ]
                );
            });
        }
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Đăng ký LoggingMailTransport
        $this->app->extend('mail.manager', function ($manager, $app) {
            $manager->extend('logging', function ($config) {
                // Lấy transport chính từ cấu hình
                $baseTransport = app('mail.manager')->createSymfonyTransport(
                    config('mail.mailers.' . config('mail.default'))
                );

                return new \App\Mail\LoggingMailTransport($baseTransport);
            });

            return $manager;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
        $this->configureLogging();
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
        });

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

        Broadcast::routes(['middleware' => ['auth:sanctum']]); // Bảo vệ route
    }
}
