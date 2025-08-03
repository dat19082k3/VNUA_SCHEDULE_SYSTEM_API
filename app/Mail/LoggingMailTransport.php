<?php

namespace App\Mail;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\SentMessage;
use Illuminate\Support\Facades\File;

class LoggingMailTransport implements TransportInterface
{
    /**
     * Transport sẽ được sử dụng để thực sự gửi email
     *
     * @var \Symfony\Component\Mailer\Transport\TransportInterface
     */
    protected $transport;

    /**
     * Tạo một instance mới của LoggingMailTransport.
     *
     * @param  \Symfony\Component\Mailer\Transport\TransportInterface  $transport
     * @return void
     */
    public function __construct(TransportInterface $transport)
    {
        $this->transport = $transport;
    }

    /**
     * {@inheritdoc}
     */
    public function send(RawMessage $message, Envelope $envelope = null): ?SentMessage
    {
        try {
            // Trích xuất thông tin từ message
            $email = $message instanceof Email ? $message : null;
            $to = $envelope ? $envelope->getRecipients() : [];
            $toAddresses = [];

            foreach ($to as $recipient) {
                $toAddresses[] = $recipient->getAddress();
            }

            $subject = $email ? $email->getSubject() : 'No subject';

            // Đảm bảo thư mục logs tồn tại
            $logPath = storage_path('logs');
            if (!File::isDirectory($logPath)) {
                File::makeDirectory($logPath, 0755, true);
            }

            // Ghi log trực tiếp vào file thay vì qua Log Facade
            $logFile = storage_path('logs/email.log');
            $logData = json_encode([
                'time' => now()->format('Y-m-d H:i:s'),
                'message' => 'Đang gửi email',
                'to' => $toAddresses,
                'subject' => $subject,
                'body_size' => $message->toString() ? strlen($message->toString()) : 0
            ]);

            File::append($logFile, "[" . now() . "] {$logData}\n");

            Log::channel('email')->info('Đang gửi email', [
                'to' => $toAddresses,
                'subject' => $subject,
                'headers' => $this->getHeadersAsString($message),
                'body_size' => $message->toString() ? strlen($message->toString()) : 0
            ]);

            // Gửi email bằng transport thực tế
            $sentMessage = $this->transport->send($message, $envelope);

            // Log kết quả
            $logData = json_encode([
                'time' => now()->format('Y-m-d H:i:s'),
                'message' => 'Email đã được gửi thành công',
                'to' => $toAddresses,
                'subject' => $subject,
                'message_id' => $sentMessage ? $sentMessage->getMessageId() : null
            ]);

            File::append($logFile, "[" . now() . "] {$logData}\n");

            Log::channel('email')->info('Email đã được gửi', [
                'to' => $toAddresses,
                'subject' => $subject,
                'message_id' => $sentMessage ? $sentMessage->getMessageId() : null
            ]);

            return $sentMessage;
        } catch (\Exception $e) {
            // Log lỗi
            $logData = json_encode([
                'time' => now()->format('Y-m-d H:i:s'),
                'message' => 'Lỗi khi gửi email',
                'to' => $toAddresses ?? [],
                'subject' => $subject ?? 'Unknown',
                'error' => $e->getMessage()
            ]);

            File::append($logFile, "[" . now() . "] ERROR {$logData}\n");

            Log::channel('email')->error('Lỗi khi gửi email', [
                'to' => $toAddresses ?? [],
                'subject' => $subject ?? 'Unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    /**
     * Lấy chuỗi header từ message.
     *
     * @param  RawMessage  $message
     * @return string
     */
    protected function getHeadersAsString(RawMessage $message)
    {
        if ($message instanceof Email) {
            $headers = [];
            foreach ($message->getHeaders()->all() as $name => $header) {
                $headers[$name] = $header->getBodyAsString();
            }
            return json_encode($headers);
        }

        return json_encode(['headers' => 'Not available']);
    }

    /**
     * Convert to string.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'logging-transport';
    }
}
