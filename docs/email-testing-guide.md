# Hướng Dẫn Kiểm Thử Thông Báo Email VNUA Schedule System

## Tổng Quan

Tài liệu này hướng dẫn cách kiểm thử hệ thống thông báo email cho sự kiện trong VNUA Schedule System.

## Các Loại Thông Báo

1. **Approved** - Thông báo sự kiện được phê duyệt
2. **Changed** - Thông báo sự kiện đã thay đổi
3. **Reapproved** - Thông báo sự kiện đã được phê duyệt lại

## Người Nhận Thông Báo

- Người tạo sự kiện
- Người tham gia sự kiện (user type)
- Thành viên của các phòng ban liên quan
- Email kiểm thử: dat19082k3@gmail.com

## Cách Kiểm Thử

### Kiểm Thử Qua Dòng Lệnh

```bash
# Kiểm thử cơ bản với sự kiện đầu tiên trong DB
php artisan mail:test-bulk

# Kiểm thử với sự kiện cụ thể
php artisan mail:test-bulk 123

# Kiểm thử với loại thông báo cụ thể
php artisan mail:test-bulk 123 --type=changed
```

### Kiểm Thử Qua Web

Truy cập URL (chỉ có trong môi trường phát triển):

- `/test-bulk-mail` - Kiểm thử với sự kiện đầu tiên
- `/test-bulk-mail/123` - Kiểm thử với ID sự kiện 123
- `/test-bulk-mail/123/changed` - Kiểm thử với ID sự kiện 123, loại thông báo "changed"

## Xem Kết Quả

- Kết quả kiểm thử sẽ hiển thị trong terminal hoặc web
- Log chi tiết được lưu trong `storage/logs/email.log`
- Các email thử nghiệm luôn được gửi đến `dat19082k3@gmail.com`

## Mẹo Kiểm Thử

1. **Kiểm tra định dạng JSON của participants**:
   ```php
   DB::table('events')->where('id', 123)->value('participants');
   ```

2. **Thêm người tham gia vào sự kiện**:
   ```php
   $event = Event::find(123);
   $event->addParticipant('user', 1); // Thêm user ID 1 vào sự kiện
   ```

3. **Tạo tài khoản kiểm thử**:
   ```php
   User::create([
       'name' => 'Test User',
       'email' => 'your-email@example.com',
       'password' => Hash::make('password')
   ]);
   ```

## Tài Liệu Đầy Đủ

Tham khảo tài liệu đầy đủ về hệ thống thông báo email tại:
`docs/email-notification-system.md`

## Liên Hệ Hỗ Trợ

Nếu gặp vấn đề khi kiểm thử, liên hệ: dat19082k3@gmail.com
