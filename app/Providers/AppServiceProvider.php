<?php
namespace App\Providers;

use App\Services\DepartmentService;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    protected $policies = [
        \App\Models\Permission::class      => \App\Policies\PermissionPolicy::class,
        \App\Models\PermissionType::class  => \App\Policies\PermissionTypePolicy::class,
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
        $this->app->singleton(DepartmentService::class, function ($app) {
            return new DepartmentService();
        });
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

        Broadcast::routes(['middleware' => ['auth:sanctum']]); // Bảo vệ route
    }
}
