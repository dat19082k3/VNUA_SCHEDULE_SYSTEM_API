<?php

use Illuminate\Support\Facades\Route;
use App\Services\EmailLogger;

Route::get('/', function () {
    return view('welcome');
});

// Chỉ sử dụng cho môi trường phát triển
if (app()->environment('local')) {
    // Route test gửi mail - phiên bản đơn giản
    Route::get('/test-mail', function () {
        try {
            // Chạy artisan command để test
            \Illuminate\Support\Facades\Artisan::call('mail:test-all');
            $output = \Illuminate\Support\Facades\Artisan::output();

            // Hiển thị kết quả dưới dạng HTML
            return view('mail.test-result', [
                'output' => $output,
                'logInfo' => \App\Services\EmailLogger::checkEmailLog()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });

    // Route test gửi mail thông báo sự kiện (đơn/hàng loạt)
    Route::get('/test-bulk-mail/{event_id?}/{type?}', function ($event_id = null, $type = 'approved') {
        try {
            // Validate notification type
            if (!in_array($type, ['approved', 'changed', 'reapproved'])) {
                $type = 'approved';
            }

            // Build command
            $command = 'mail:test-bulk';
            if ($event_id) {
                $command .= ' ' . $event_id;
            }
            $command .= ' --type=' . $type;

            // Run command and get output
            \Illuminate\Support\Facades\Artisan::call($command);
            $output = \Illuminate\Support\Facades\Artisan::output();

            // Display results
            return view('mail.test-result', [
                'output' => $output,
                'logInfo' => \App\Services\EmailLogger::checkEmailLog()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    });
}
