<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\EmailLogger;

class CheckQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kiểm tra trạng thái hàng đợi email';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Kiểm tra xem bảng jobs có tồn tại không
            if (!$this->tableExists('jobs')) {
                $this->error('Bảng jobs không tồn tại. Vui lòng chạy migration.');
                return 1;
            }

            // Kiểm tra số lượng jobs đang chờ xử lý
            $pendingJobs = DB::table('jobs')->count();
            $this->info("Có $pendingJobs email đang chờ trong hàng đợi.");

            // Kiểm tra số lượng jobs thất bại
            if ($this->tableExists('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
                $this->info("Có $failedJobs email đã thất bại.");

                // Hiển thị thông tin về các jobs thất bại gần đây
                if ($failedJobs > 0) {
                    $recentFailedJobs = DB::table('failed_jobs')
                        ->select(['uuid', 'failed_at', 'exception'])
                        ->orderBy('failed_at', 'desc')
                        ->limit(5)
                        ->get();

                    $this->info("\nCác email thất bại gần đây:");
                    foreach ($recentFailedJobs as $job) {
                        $this->line("UUID: {$job->uuid}");
                        $this->line("Thời gian: {$job->failed_at}");
                        $this->line("Lỗi: " . substr($job->exception, 0, 100) . "...");
                        $this->line("-----------------------------");
                    }
                }
            }

            // Kiểm tra cài đặt queue trong .env
            $queueConnection = config('queue.default');
            $this->info("Kết nối queue hiện tại: $queueConnection");

            // Kiểm tra cài đặt mail
            $mailDriver = config('mail.default');
            $mailHost = config('mail.mailers.smtp.host');
            $mailPort = config('mail.mailers.smtp.port');
            $mailUsername = config('mail.mailers.smtp.username');

            $this->info("Cấu hình mail:");
            $this->line("- Driver: $mailDriver");
            $this->line("- Host: $mailHost");
            $this->line("- Port: $mailPort");
            $this->line("- Username: $mailUsername");

            // Kiểm tra log email
            $this->newLine();
            $this->info("Kiểm tra log email:");
            $emailLogInfo = EmailLogger::checkEmailLog();

            $this->line("- Log file: " . $emailLogInfo['path']);
            $this->line("- Tồn tại: " . ($emailLogInfo['exists'] ? 'Có' : 'Không'));
            $this->line("- Kích thước: " . round($emailLogInfo['size'] / 1024, 2) . " KB");
            $this->line("- Lần cuối chỉnh sửa: " . ($emailLogInfo['last_modified'] ? date('Y-m-d H:i:s', $emailLogInfo['last_modified']) : 'N/A'));

            if (!empty($emailLogInfo['recent_logs'])) {
                $this->newLine();
                $this->info("5 dòng log email gần nhất:");
                $count = 0;
                foreach ($emailLogInfo['recent_logs'] as $line) {
                    $this->line($line);
                    $count++;
                    if ($count >= 5) break;
                }
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Lỗi: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Kiểm tra xem bảng có tồn tại không
     */
    protected function tableExists($table)
    {
        try {
            DB::select("SELECT 1 FROM $table LIMIT 1");
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
