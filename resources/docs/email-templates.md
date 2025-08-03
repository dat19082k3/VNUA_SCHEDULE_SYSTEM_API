# Email Notification Templates

Hệ thống thông báo email đã được cải tiến với các template chuyên nghiệp, thống nhất và thân thiện với người dùng.

## Cấu trúc Template

### Layout chung (Master Layout)

Tất cả email đều sử dụng layout chung tại `resources/views/emails/layouts/master.blade.php`, bao gồm:

- Header với logo và tên hệ thống
- Body chứa nội dung thông báo
- Footer với thông tin liên hệ và bản quyền

### Templates cho các loại thông báo

1. **Thông báo phê duyệt sự kiện** (`emails/events/approved.blade.php`)
   - Gửi khi sự kiện được phê duyệt
   - Hiển thị đầy đủ thông tin sự kiện
   - Kèm nút để xem chi tiết sự kiện

2. **Thông báo thay đổi sự kiện** (`emails/events/changed.blade.php`)
   - Gửi khi sự kiện được cập nhật
   - Hiển thị so sánh các thay đổi (giá trị cũ và mới)
   - Hiển thị thông tin người thực hiện thay đổi

3. **Thông báo phê duyệt lại sự kiện** (`emails/events/reapproved.blade.php`)
   - Gửi khi sự kiện được phê duyệt lại sau khi chỉnh sửa
   - Hiển thị các thay đổi và trạng thái phê duyệt

4. **Template thông báo chung** (`emails/events/generic.blade.php`)
   - Sử dụng cho các loại thông báo khác
   - Dễ dàng tùy chỉnh với các tham số linh hoạt

## Cách Test Email Templates

### Test qua giao diện web

Truy cập các đường dẫn sau trong môi trường phát triển:

- Test gửi thông báo phê duyệt: `/test-mail?type=approved`
- Test gửi thông báo thay đổi: `/test-mail?type=changed`
- Test gửi thông báo phê duyệt lại: `/test-mail?type=reapproved`
- Xem trước template (không gửi email): `/test-mail?type=template-preview&template=approved`
- Xem trước các template khác: `/test-mail?type=template-preview&template=changed` hoặc `template=reapproved`
- Xem trước template chung: `/test-mail?type=template-preview&template=generic`
- Test gửi tất cả các loại thông báo: `/test-mail?type=all`

### Test qua Artisan Command

Sử dụng lệnh sau trong terminal:

```bash
php artisan mail:test-all [email_address]
```

## Quy định Style và UX/UI

### Màu sắc

- Màu chính: Xanh lá (#43A047) - Dùng cho các nút và nhấn mạnh
- Màu thông tin: Xanh dương (#17a2b8) - Dùng cho thông tin bổ sung
- Màu cảnh báo: Vàng (#ffc107) - Dùng cho trạng thái chờ xử lý
- Màu quan trọng: Đỏ (#dc3545) - Dùng cho các sự kiện quan trọng

### Typography

- Font: Roboto hoặc hệ font sans-serif tương tự
- Tiêu đề: Đậm, cỡ lớn
- Nội dung: Dễ đọc, khoảng cách dòng 1.6

### Components

- Bảng thông tin sự kiện: Hiển thị rõ ràng các thông tin sự kiện
- Bảng so sánh thay đổi: Hiển thị rõ ràng giá trị cũ và mới
- Nút hành động: Nổi bật, dễ nhận diện
- Tag trạng thái: Nhận diện nhanh trạng thái sự kiện

## Cách sử dụng trong code

Trong các Notification class, sử dụng view để render template:

```php
public function toMail(object $notifiable): MailMessage
{
    return (new MailMessage)
        ->subject('Tiêu đề thông báo')
        ->view('emails.events.template-name', [
            'event' => $this->event,
            'notifiable' => $notifiable,
            // Các tham số khác tùy theo loại template
        ]);
}
```

## Tùy chỉnh và mở rộng

Để tạo template mới, tạo file blade mới trong thư mục `resources/views/emails/events/` và đảm bảo extends từ master layout:

```php
@extends('emails.layouts.master')

@section('content')
    <!-- Nội dung template -->
@endsection
```
