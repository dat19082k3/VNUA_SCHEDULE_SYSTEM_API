<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Event;
use App\Models\User;
use App\Notifications\EventApprovedNotification;
use App\Notifications\EventChangedNotification;
use App\Notifications\EventReapprovedNotification;
use App\Services\EmailLogger;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;

class TestMailScript extends Command
{
    protected $signature = 'mail:test-all {email? : Email để test}';
    protected $description = 'Script test tất cả các loại email thông báo';

    /**
     * Thực thi script test
     */
    public function handle()
    {
        try {
            $this->info('Bắt đầu test gửi email...');

            // 1. Chuẩn bị dữ liệu test
            $testEmail = $this->argument('email') ?? 'duongtiendat19082003@gmail.com';
            $user = $this->prepareTestUser($testEmail);
            $event = $this->prepareTestEvent();

            if (!$user || !$event) {
                return 1;
            }

            $this->info('Đã chuẩn bị dữ liệu test:');
            $this->line("- User: {$user->name} ({$user->email})");
            $this->line("- Event: {$event->title}");

            // 2. Test từng loại thông báo
            $this->testEventApproved($user, $event);
            $this->testEventChanged($user, $event);
            $this->testEventReapproved($user, $event);

            // 3. Kiểm tra log
            $this->checkEmailLogs();

            // 4. Khởi động queue worker
            $this->info('Đang xử lý queue...');
            $this->call('queue:work', ['--once' => true]);

            $this->info('Test hoàn tất! Kiểm tra email tại: ' . $testEmail);
            return 0;

        } catch (\Exception $e) {
            $this->error('Lỗi: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }

    /**
     * Chuẩn bị user test
     */
    protected function prepareTestUser(string $email): ?User
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->info("Không tìm thấy user với email $email. Đang dùng user đầu tiên...");
            $user = User::first();

            if (!$user) {
                $this->error('Không tìm thấy user nào trong hệ thống!');
                return null;
            }

            $originalEmail = $user->email;
            $user->email = $email;
            $this->line("Đã thay đổi email tạm thời từ $originalEmail thành $email");
        }

        return $user;
    }

    /**
     * Chuẩn bị event test
     */
    protected function prepareTestEvent(): ?Event
    {
        $event = Event::first();

        if (!$event) {
            $this->error('Không tìm thấy event nào trong hệ thống!');
            return null;
        }

        return $event;
    }

    /**
     * Test thông báo phê duyệt sự kiện
     */
    protected function testEventApproved(User $user, Event $event)
    {
        $this->info('1. Test thông báo phê duyệt sự kiện...');

        try {
            EmailLogger::logSent($user->email, "Test thông báo phê duyệt sự kiện", "Bắt đầu gửi");
            $user->notify(new EventApprovedNotification($event));
            $this->line('✓ Đã gửi thông báo phê duyệt sự kiện');

        } catch (\Exception $e) {
            EmailLogger::logError($user->email, "Test thông báo phê duyệt sự kiện", $e);
            $this->error('✗ Lỗi khi gửi thông báo phê duyệt sự kiện: ' . $e->getMessage());
        }
    }

    /**
     * Test thông báo thay đổi sự kiện
     */
    protected function testEventChanged(User $user, Event $event)
    {
        $this->info('2. Test thông báo thay đổi sự kiện...');

        try {
            $editor = User::where('id', '!=', $user->id)->first() ?? $user;
            $changes = [
                'title' => ['old_value' => 'Cuộc họp cũ', 'new_value' => 'Cuộc họp mới'],
                'start_time' => ['old_value' => '2025-07-18 09:00:00', 'new_value' => '2025-07-19 10:00:00']
            ];

            EmailLogger::logSent($user->email, "Test thông báo thay đổi sự kiện", "Bắt đầu gửi");
            $user->notify(new EventChangedNotification($event, $changes, $editor));
            $this->line('✓ Đã gửi thông báo thay đổi sự kiện');

        } catch (\Exception $e) {
            EmailLogger::logError($user->email, "Test thông báo thay đổi sự kiện", $e);
            $this->error('✗ Lỗi khi gửi thông báo thay đổi sự kiện: ' . $e->getMessage());
        }
    }

    /**
     * Test thông báo phê duyệt lại sự kiện
     */
    protected function testEventReapproved(User $user, Event $event)
    {
        $this->info('3. Test thông báo phê duyệt lại sự kiện...');

        try {
            $changes = [
                'title' => ['old_value' => 'Cuộc họp cũ', 'new_value' => 'Cuộc họp mới'],
                'location' => ['old_value' => 'Phòng A', 'new_value' => 'Phòng B']
            ];

            EmailLogger::logSent($user->email, "Test thông báo phê duyệt lại sự kiện", "Bắt đầu gửi");
            $user->notify(new EventReapprovedNotification($event, $changes));
            $this->line('✓ Đã gửi thông báo phê duyệt lại sự kiện');

        } catch (\Exception $e) {
            EmailLogger::logError($user->email, "Test thông báo phê duyệt lại sự kiện", $e);
            $this->error('✗ Lỗi khi gửi thông báo phê duyệt lại sự kiện: ' . $e->getMessage());
        }
    }

    /**
     * Kiểm tra log email
     */
    protected function checkEmailLogs()
    {
        $this->info("\nKiểm tra log email:");
        $emailLogInfo = EmailLogger::checkEmailLog();

        $this->line("- File: {$emailLogInfo['path']}");
        $this->line("- Tồn tại: " . ($emailLogInfo['exists'] ? 'Có' : 'Không'));
        $this->line("- Kích thước: " . round($emailLogInfo['size'] / 1024, 2) . " KB");
        $this->line("- Cập nhật lần cuối: " . ($emailLogInfo['last_modified'] ? date('Y-m-d H:i:s', $emailLogInfo['last_modified']) : 'N/A'));

        if (!empty($emailLogInfo['recent_logs'])) {
            $this->info("\nCác log gần đây:");
            foreach ($emailLogInfo['recent_logs'] as $log) {
                $this->line($log);
            }
        }
    }
}
