<?php

use Illuminate\Support\Facades\Route;
use App\Services\EmailLogger;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

// Tạo route test mail với template mới
Route::get('/test-mail', function () {
    // Đảm bảo chỉ chạy trong môi trường local
    if (!app()->environment('local')) {
        return 'Route này chỉ khả dụng trong môi trường phát triển';
    }

    try {
        // Lấy user đầu tiên trong hệ thống
        $user = \App\Models\User::first();
        if (!$user) {
            return 'Không tìm thấy người dùng để test';
        }

        // Lấy sự kiện đầu tiên trong hệ thống
        $event = \App\Models\Event::with(['locations', 'preparers', 'creator'])->first();
        if (!$event) {
            return 'Không tìm thấy sự kiện để test';
        }

        // Test notification
        $notificationType = request('type', 'approved');

        switch ($notificationType) {
            case 'approved':
                $user->notify(new \App\Notifications\EventApprovedNotification($event));
                $message = 'Đã gửi thông báo phê duyệt sự kiện';
                break;

            case 'changed':
                $editor = \App\Models\User::where('id', '!=', $user->id)->first() ?? $user;
                $changes = [
                    'title' => ['old_value' => 'Cuộc họp cũ', 'new_value' => 'Cuộc họp mới'],
                    'start_time' => ['old_value' => '2025-07-18 09:00:00', 'new_value' => '2025-07-19 10:00:00'],
                    'location' => ['old_value' => 'Phòng A', 'new_value' => 'Phòng B']
                ];
                $user->notify(new \App\Notifications\EventChangedNotification($event, $changes, $editor));
                $message = 'Đã gửi thông báo thay đổi sự kiện';
                break;

            case 'reapproved':
                $changes = [
                    'title' => ['old_value' => 'Cuộc họp cũ', 'new_value' => 'Cuộc họp mới'],
                    'location' => ['old_value' => 'Phòng A', 'new_value' => 'Phòng B']
                ];
                $user->notify(new \App\Notifications\EventReapprovedNotification($event, $changes));
                $message = 'Đã gửi thông báo phê duyệt lại sự kiện';
                break;

            case 'template-preview':
                // Test case để xem trước các template mẫu (không gửi email)
                $templateType = request('template', 'approved');
                $viewData = [
                    'event' => $event,
                    'notifiable' => $user,
                    'changes' => [
                        'title' => ['old_value' => 'Cuộc họp cũ', 'new_value' => 'Cuộc họp mới'],
                        'start_time' => ['old_value' => '2025-07-18 09:00:00', 'new_value' => '2025-07-19 10:00:00'],
                        'location' => ['old_value' => 'Phòng A', 'new_value' => 'Phòng B']
                    ],
                    'editor' => $user,
                ];

                if ($templateType === 'generic') {
                    $viewData['title'] = 'Tiêu đề thông báo mẫu';
                    $viewData['message'] = 'Đây là nội dung thông báo mẫu.';
                    $viewData['tags'] = [
                        'info' => 'Thông tin',
                        'important' => 'Quan trọng',
                        'approved' => 'Đã duyệt'
                    ];
                    $viewData['additionalContent'] = '<p>Đây là nội dung bổ sung cho template.</p>';
                }

                return view('emails.events.' . $templateType, $viewData);

            case 'all':
                // Chạy lệnh artisan test tất cả các loại mail
                \Illuminate\Support\Facades\Artisan::call('mail:test-all', ['email' => $user->email]);
                $output = \Illuminate\Support\Facades\Artisan::output();

                return view('mail.test-result', [
                    'output' => $output,
                    'logInfo' => \App\Services\EmailLogger::checkEmailLog()
                ]);

            default:
                return 'Loại thông báo không hợp lệ. Sử dụng: approved, changed, reapproved, template-preview, all';
        }

        return $message . " tới " . $user->email;

    } catch (\Exception $e) {
        return 'Lỗi: ' . $e->getMessage() . '<br><pre>' . $e->getTraceAsString() . '</pre>';
    }
});
