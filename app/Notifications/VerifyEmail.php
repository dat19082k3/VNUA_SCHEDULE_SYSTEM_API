<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VerifyEmail extends BaseVerifyEmail
{
    /**
     * Tạo URL xác minh với temporary signed route.
     * Ta sử dụng token lưu trong cache làm giá trị cho tham số {hash}.
     */
    protected function verificationUrl($notifiable)
    {
        $cacheKey = 'email_verification_' . $notifiable->id;
        $token = Cache::get($cacheKey);

        // Nếu token chưa tồn tại (bất thường), tạo token mới và lưu vào cache
        if (!$token) {
            $token = Str::random(64);
            Cache::put($cacheKey, $token, now()->addMinutes(30));
        }

        return URL::temporarySignedRoute(
            'verification.verify',        // Tên route đã định nghĩa
            now()->addMinutes(30),         // Thời gian hết hạn của link
            [
                'id'   => $notifiable->getKey(),
                'hash' => $token,         // Sử dụng token làm giá trị của {hash}
            ]
        );
    }
}
