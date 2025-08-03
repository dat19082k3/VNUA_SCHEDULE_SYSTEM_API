# VNUA Schedule System API

<p align="center">
  <img src="https://vn.joboko.com/upload/employer_logo/71/0c74ddb6a5cab613a8117b25398a7a97.jpg" width="200" alt="VNUA Logo">
</p>

<p align="center">
  <b>Hệ thống quản lý lịch trình và sự kiện cho Học viện Nông nghiệp Việt Nam</b>
</p>

## Giới thiệu

Hệ thống API quản lý lịch trình và sự kiện của Học viện Nông nghiệp Việt Nam được phát triển trên nền tảng Laravel 12, hỗ trợ việc quản lý và tổ chức các sự kiện, cuộc họp và lịch trình trong toàn Học viện. Hệ thống hỗ trợ nhiều vai trò người dùng, quản lý phòng ban, địa điểm và thông báo qua email.

## Yêu cầu hệ thống

- PHP 8.2 hoặc cao hơn
- Composer 2.x
- MySQL 8.0
- Redis (cho cache và queue)
- Node.js 18+ và NPM (cho việc biên dịch tài nguyên frontend, nếu cần)

## Cài đặt và thiết lập

### 1. Cài đặt thủ công

#### Clone dự án

```bash
git clone https://github.com/dat19082k3/VNUA_SCHEDULE_SYSTEM_API.git
cd VNUA_SCHEDULE_SYSTEM_API
```

#### Cài đặt các dependencies PHP

```bash
composer install
```

#### Cấu hình môi trường

```bash
cp .env.example .env
php artisan key:generate
```

Chỉnh sửa file `.env` với các thông tin cấu hình cần thiết:

```
APP_NAME="VNUA Schedule System"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
FRONTEND_URL=http://localhost:3000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=vnua_schedule
DB_USERNAME=root
DB_PASSWORD=your_password

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your_email@gmail.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### Thiết lập cơ sở dữ liệu

```bash
php artisan migrate
php artisan db:seed
```

#### Chạy ứng dụng

```bash
php artisan serve
```

Ứng dụng sẽ chạy tại `http://localhost:8000`.

### 2. Cài đặt với Docker

Dự án đã được cấu hình để chạy với Docker thông qua `docker-compose`.

```bash
# Clone dự án
git clone https://github.com/dat19082k3/VNUA_SCHEDULE_SYSTEM_API.git
cd VNUA_SCHEDULE_SYSTEM_API

# Cấu hình môi trường
cp .env.example .env

# Chạy Docker
docker-compose up -d

# Cài đặt dependencies và thực hiện migrate
docker-compose exec php composer install
docker-compose exec php php artisan key:generate
docker-compose exec php php artisan migrate
docker-compose exec php php artisan db:seed
```

Ứng dụng sẽ chạy tại `http://localhost:8080`.

## Cấu trúc dự án

```
VNUA_SCHEDULE_SYSTEM_API/
├── app/                        # Mã nguồn chính của ứng dụng
│   ├── Console/                # Các lệnh Artisan
│   ├── Constants/              # Các hằng số và enum
│   ├── Dtos/                   # Data Transfer Objects
│   ├── Exceptions/             # Xử lý ngoại lệ
│   ├── Http/                   # Controllers, Middleware, Requests, Resources
│   ├── Interfaces/             # Các interface
│   ├── Models/                 # Các model Eloquent
│   ├── Notifications/          # Các thông báo
│   ├── Providers/              # Service providers
│   └── Services/               # Các service xử lý logic nghiệp vụ
├── bootstrap/                  # Bootstrap của ứng dụng
├── config/                     # Cấu hình ứng dụng
├── database/                   # Migrations, seeders, factories
├── public/                     # Thư mục public
├── resources/                  # Views, assets và ngôn ngữ
├── routes/                     # Định nghĩa routes
├── storage/                    # Tệp đã tải lên, logs, cache
├── tests/                      # Unit và feature tests
├── .env.example                # Mẫu file môi trường
├── composer.json               # Quản lý dependencies PHP
├── docker-compose.yml          # Cấu hình Docker Compose
└── Dockerfile                  # Cấu hình Docker
```

## Các tính năng chính

1. **Quản lý người dùng và phân quyền**:
   - Đăng nhập, đăng ký, quản lý thông tin cá nhân
   - Vai trò và quyền hạn (admin, manager, staff, etc.)

2. **Quản lý sự kiện và lịch trình**:
   - Tạo, chỉnh sửa, xóa sự kiện
   - Phê duyệt sự kiện
   - Xem lịch theo ngày, tuần, tháng

3. **Quản lý phòng ban và địa điểm**:
   - Thêm, sửa, xóa phòng ban
   - Quản lý địa điểm tổ chức sự kiện

4. **Thông báo và nhắc nhở**:
   - Thông báo qua email khi sự kiện được tạo, cập nhật, phê duyệt
   - Nhắc nhở về các sự kiện sắp diễn ra

5. **Tìm kiếm và lọc**:
   - Tìm kiếm sự kiện theo từ khóa
   - Lọc theo thời gian, người tạo, địa điểm

## API Documentation

API documentation được tự động sinh ra bằng cách chạy lệnh sau:

```bash
php artisan l5-swagger:generate
```

Sau đó, bạn có thể truy cập API documentation tại đường dẫn:
- Local: `http://localhost:8000/api/documentation`
- Docker: `http://localhost:8080/api/documentation`

## Quy trình phát triển

1. **Quy trình phát triển**:
   - Fork dự án và tạo nhánh cho tính năng mới
   - Phát triển và kiểm thử tính năng
   - Tạo Pull Request để review và merge

2. **Coding Standards**:
   - Tuân thủ PSR-12
   - Sử dụng Laravel Pint cho việc định dạng code: `php artisan pint`

3. **Testing**:
   - Chạy tests: `php artisan test`
   - Coverage: `php artisan test --coverage`

## Triển khai (Deployment)

Hướng dẫn triển khai lên môi trường production:

1. **Shared Hosting**:
   - Upload code lên server
   - Cấu hình `.env` cho môi trường production
   - Chạy migrations và seeders

2. **VPS/Cloud**:
   - Sử dụng Docker với docker-compose.yml
   - Cấu hình Nginx reverse proxy
   - Thiết lập SSL với Let's Encrypt

## Khắc phục sự cố

### Các vấn đề thường gặp

1. **Lỗi kết nối database**:
   - Kiểm tra thông tin kết nối trong file `.env`
   - Đảm bảo MySQL service đang chạy

2. **Lỗi gửi email**:
   - Kiểm tra cấu hình SMTP trong `.env`
   - Với Gmail, sử dụng App Password thay vì mật khẩu thông thường

3. **Lỗi không tạo được seeders**:
   - Đảm bảo các dependency seeders đã được chạy trước
   - Kiểm tra logs tại `storage/logs/laravel.log`

### Logs và Debugging

- Logs chính: `storage/logs/laravel.log`
- Debug mode: Đặt `APP_DEBUG=true` trong file `.env`
- PHP errors: Kiểm tra logs của web server

## License

Dự án này được phát hành dưới [Giấy phép MIT](https://opensource.org/licenses/MIT).
