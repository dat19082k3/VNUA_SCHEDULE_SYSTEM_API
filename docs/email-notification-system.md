# Tài Liệu Hệ Thống Thông Báo Email Sự Kiện

## 1. Tổng Quan

Hệ thống thông báo email sự kiện VNUA Schedule System được thiết kế để gửi thông báo tự động tới người dùng khi các sự kiện được tạo, cập nhật, phê duyệt hoặc phê duyệt lại. Hệ thống đảm bảo tất cả người liên quan đều nhận được thông tin cập nhật kịp thời.

## 2. Kiến Trúc Hệ Thống

### 2.1. Các Thành Phần Chính

- **EventNotificationService**: Dịch vụ chính xử lý việc gửi thông báo
- **EventNotificationMail**: Mailable class định nghĩa nội dung email
- **Email Templates**: Các file blade template cho các loại thông báo khác nhau
- **EmailLogger**: Dịch vụ ghi log hoạt động email

### 2.2. Luồng Xử Lý

1. Sự kiện được tạo/cập nhật/phê duyệt
2. `EventNotificationService` được gọi với sự kiện và loại thông báo
3. Dịch vụ xác định người nhận (người tạo, người tham gia, phòng ban)
4. Email được tạo và gửi đến từng người nhận
5. Kết quả gửi được ghi log

## 3. Các Loại Thông Báo

### 3.1. Thông Báo Phê Duyệt (Approved)
- **Template**: `emails.events.approved`
- **Mô tả**: Gửi khi sự kiện mới được phê duyệt
- **Người nhận**: Người tạo, người tham gia, phòng ban liên quan

### 3.2. Thông Báo Thay Đổi (Changed)
- **Template**: `emails.events.changed`
- **Mô tả**: Gửi khi sự kiện đã được phê duyệt có thay đổi
- **Người nhận**: Người tạo, người tham gia, phòng ban liên quan

### 3.3. Thông Báo Phê Duyệt Lại (Reapproved)
- **Template**: `emails.events.reapproved`
- **Mô tả**: Gửi khi sự kiện đã thay đổi được phê duyệt lại
- **Người nhận**: Người tạo, người tham gia, phòng ban liên quan

### 3.4. Thông Báo Tổng Quát (Generic)
- **Template**: `emails.events.generic`
- **Mô tả**: Mẫu thông báo chung cho các trường hợp khác
- **Người nhận**: Tùy theo ngữ cảnh sử dụng

## 4. EventNotificationService

### 4.1. Phương Thức Chính

```php
public static function notifyAllRelatedUsers(Event $event, string $notificationType, array $additionalData = []): array
```

#### Tham Số:
- `$event`: Đối tượng sự kiện cần gửi thông báo
- `$notificationType`: Loại thông báo (`approved`, `changed`, `reapproved`)
- `$additionalData`: Dữ liệu bổ sung cho thông báo (tùy chọn)

#### Giá Trị Trả Về:
- Array chứa thông tin về kết quả gửi (số lượng gửi thành công, thất bại, danh sách email đã gửi)

### 4.2. Hằng Số

```php
const NOTIFICATION_TYPE_APPROVED = 'approved';
const NOTIFICATION_TYPE_CHANGED = 'changed';
const NOTIFICATION_TYPE_REAPPROVED = 'reapproved';
```

### 4.3. Xác Định Người Nhận

Service sẽ tự động xác định người nhận dựa trên:
- Người tạo sự kiện (`creator`)
- Người tham gia (`participants` - các user có type="user")
- Thành viên của phòng ban liên quan (`preparers`)

### 4.4. Xử Lý Participants

Service xử lý các định dạng dữ liệu participants khác nhau:
- Mảng PHP
- Chuỗi JSON
- Dữ liệu từ Eloquent cast

Cấu trúc participants hợp lệ:
```json
[
  {"type": "user", "id": 1},
  {"type": "department", "id": 2}
]
```

## 5. EventNotificationMail

### 5.1. Cấu Trúc

```php
class EventNotificationMail extends Mailable
{
    public $event;
    public $notificationType;
    public $notifiable;
    public $additionalData;

    public function __construct(Event $event, string $notificationType, User $notifiable, array $additionalData = [])
    {
        // Khởi tạo thuộc tính
    }

    public function build()
    {
        // Xây dựng email
    }
}
```

### 5.2. View Templates

Các template được sử dụng:
- `emails.events.approved`
- `emails.events.changed`
- `emails.events.reapproved`
- `emails.events.generic`

## 6. Cấu Hình

### 6.1. Biến Môi Trường

```
MAIL_DRIVER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=example@example.com
MAIL_PASSWORD=password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="VNUA Schedule System"

FRONTEND_URL=http://localhost:3000
```

### 6.2. Email Test

Hệ thống luôn gửi một bản sao email tới địa chỉ test:
```
dat19082k3@gmail.com
```

## 7. Ghi Log

### 7.1. EmailLogger

Cung cấp các phương thức để ghi log hoạt động email:
- `logSent()`: Ghi log khi gửi email thành công
- `logError()`: Ghi log khi gặp lỗi

### 7.2. File Log

Log được lưu tại:
```
storage/logs/email.log
```

Format log:
```
[Timestamp] To: email@example.com | Subject: Event Notification | Message: Successfully sent approved notification for event #123
```

## 8. Kiểm Thử

### 8.1. Command Line

```bash
# Kiểm thử với sự kiện đầu tiên trong hệ thống
php artisan mail:test-bulk

# Kiểm thử với sự kiện cụ thể
php artisan mail:test-bulk <event_id>

# Kiểm thử với loại thông báo cụ thể
php artisan mail:test-bulk <event_id> --type=changed
```

### 8.2. Web Interface

Truy cập các URL sau (chỉ trong môi trường phát triển):
- `/test-bulk-mail`: Kiểm thử với sự kiện đầu tiên
- `/test-bulk-mail/<event_id>`: Kiểm thử với sự kiện cụ thể
- `/test-bulk-mail/<event_id>/<type>`: Kiểm thử với loại thông báo cụ thể

## 9. Xử Lý Sự Cố

### 9.1. Email Không Gửi Được

Kiểm tra:
- Cấu hình SMTP trong `.env`
- Log tại `storage/logs/email.log` và `storage/logs/laravel.log`
- Đảm bảo người dùng có email hợp lệ

### 9.2. Lỗi Định Dạng Participants

Kiểm tra:
- Định dạng JSON của trường `participants`
- Cấu trúc đúng: `[{"type": "user", "id": 1}, {"type": "department", "id": 2}]`

### 9.3. Liên Kết Không Hoạt Động

- Kiểm tra biến môi trường `FRONTEND_URL` trong `.env`
- Đảm bảo frontend đang chạy ở địa chỉ được cấu hình

## 10. Các Mẹo và Lưu Ý

### 10.1. Tránh Gửi Email Thừa

- Hệ thống tự động loại bỏ trùng lặp người nhận
- Kiểm tra người nhận trước khi gửi hàng loạt

### 10.2. Tùy Chỉnh Mẫu Email

- Các mẫu email sử dụng blade template, dễ dàng tùy chỉnh
- CSS được định nghĩa trong `emails.layouts.master`
- Các nút CTA sử dụng các lớp `.button-success`

### 10.3. Bảo Mật

- Không hiển thị thông tin nhạy cảm trong email
- URL luôn được tạo với hàm `url()` hoặc qua biến môi trường `FRONTEND_URL`

## 11. Phát Triển Trong Tương Lai

- Thêm chức năng lên lịch gửi email
- Tích hợp với hệ thống thông báo đẩy
- Tùy chỉnh template email theo người dùng
- Theo dõi tỷ lệ mở email và nhấp vào liên kết

## 12. Liên Hệ

Nếu bạn có câu hỏi hoặc gặp vấn đề với hệ thống thông báo email, vui lòng liên hệ:

- Email: dat19082k3@gmail.com
- GitHub: github.com/dat19082k3
