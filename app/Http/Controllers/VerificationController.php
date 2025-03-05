<?php

namespace App\Http\Controllers;

use App\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Events\Verified;
use App\Notifications\VerifyEmail;

class VerificationController extends Controller
{
    use ApiResponseTrait;

    /**
     * Xác minh email dựa vào token trong URL và chữ ký của route.
     */
    public function verify(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->sendSuccess($user, "Email đã được xác minh");
        }

        // Lấy token từ tham số {hash} trong URL
        $token = $request->route('hash');
        if (!$token) {
            return $this->sendError("Link xác minh không hợp lệ", 400);
        }

        $cacheKey = 'email_verification_' . $user->id;
        $cachedToken = Cache::get($cacheKey);

        // Nếu token không trùng khớp hoặc không tồn tại thì báo lỗi
        if (!$cachedToken || $token !== $cachedToken) {
            return $this->sendError("Link xác minh không hợp lệ hoặc đã bị vô hiệu hóa", 400);
        }

        // Đánh dấu email đã xác minh và kích hoạt sự kiện Verified
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Xóa token khỏi cache sau khi xác minh thành công
        Cache::forget($cacheKey);

        return $this->sendSuccess($user, "Xác minh email thành công");
    }

    /**
     * Gửi lại email xác minh: tạo token mới (vô hiệu hóa link cũ) và gửi thông báo.
     */
    public function resend(Request $request)
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->sendSuccess($user, "Email đã được xác minh");
        }

        $cacheKey = 'email_verification_' . $user->id;
        // Tạo token mới và lưu vào cache với thời hạn 30 phút
        $token = Str::random(64);
        Cache::put($cacheKey, $token, now()->addMinutes(30));

        // Gửi thông báo xác minh sử dụng notification CustomVerifyEmail (template mặc định của Laravel)
        $user->notify(new VerifyEmail());

        return $this->sendSuccess($user, "Đã gửi lại thư xác minh. Vui lòng kiểm tra email của bạn.");
    }
}
