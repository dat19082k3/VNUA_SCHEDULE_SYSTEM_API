<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use SoftDeletes, HasFactory, Notifiable, HasApiTokens, HasRoles;

    // Các trường được phép insert/ cập nhật
    protected $fillable = [
        'sso_id',
        'user_name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'role_sso',
        'status',
        'code',
        'primary_department_id',
        'faculty_id',
        'protected'
    ];

    // Các trường ẩn khi chuyển đổi sang mảng hoặc JSON
    protected $hidden = [
        'remember_token',
        'deleted_at',
    ];

    // Các trường dạng boolean (để lưu trữ dưới dạng 1/0 thay vì true/false)
    protected $casts = [];
    
    /**
     * Route notifications for the mail channel.
     */
    public function routeNotificationForMail(): string
    {
        return $this->email;
    }

    // Mối quan hệ với bảng Department (phòng ban chính)
    public function primaryDepartment()
    {
        return $this->belongsTo(Department::class, 'primary_department_id');
    }

    // Mối quan hệ nhiều-nhiều với bảng Department (tất cả phòng ban)
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'user_departments')
                    ->withTimestamps();
    }

    /**
     * Relationship với các sự kiện mà người dùng đã đánh dấu/xem
     */
    public function events()
    {
        return $this->belongsToMany(Event::class, 'user_events')
                    ->withPivot('is_marked', 'is_viewed')
                    ->withTimestamps();
    }

    /**
     * Lấy các sự kiện mà người dùng đã ghim
     */
    public function markedEvents()
    {
        return $this->belongsToMany(Event::class, 'user_events')
                    ->wherePivot('is_marked', true)
                    ->withPivot('is_marked', 'is_viewed')
                    ->withTimestamps();
    }

    /**
     * Lấy các sự kiện mà người dùng đã xem
     */
    public function viewedEvents()
    {
        return $this->belongsToMany(Event::class, 'user_events')
                    ->wherePivot('is_viewed', true)
                    ->withPivot('is_marked', 'is_viewed')
                    ->withTimestamps();
    }

    // Mối quan hệ với các Attachments mà user đã tải lên
    public function uploadedAttachments()
    {
        return $this->hasMany(Attachment::class, 'uploader_id');
    }

    // Mối quan hệ với các sự kiện mà người dùng đã tạo
    public function createdEvents()
    {
        return $this->hasMany(Event::class, 'creator_id');
    }

    // Thêm các phương thức hữu ích cho model này, ví dụ như fullname
    public function getFullNameAttribute()
    {
        return $this->last_name . ' ' . $this->first_name;
    }

    /**
     * Đánh dấu một sự kiện
     * @param int $eventId
     * @return bool
     */
    public function markEvent(int $eventId): bool
    {
        $exists = $this->markedEvents()->where('event_id', $eventId)->exists();

        if ($exists) {
            return $this->markedEvents()->updateExistingPivot($eventId, [
                'is_marked' => true
            ]);
        }

        return (bool) $this->markedEvents()->attach($eventId, [
            'is_marked' => true,
            'is_viewed' => false
        ]);
    }

    /**
     * Bỏ đánh dấu một sự kiện
     * @param int $eventId
     * @return bool
     */
    public function unmarkEvent(int $eventId): bool
    {
        $exists = $this->markedEvents()->where('event_id', $eventId)->exists();

        if ($exists) {
            return $this->markedEvents()->updateExistingPivot($eventId, [
                'is_marked' => false
            ]);
        }

        return true;
    }

    /**
     * Đánh dấu một sự kiện đã xem
     * @param int $eventId
     * @return bool
     */
    public function markEventAsViewed(int $eventId): bool
    {
        $exists = $this->markedEvents()->where('event_id', $eventId)->exists();

        if ($exists) {
            return $this->markedEvents()->updateExistingPivot($eventId, [
                'is_viewed' => true
            ]);
        }

        return (bool) $this->markedEvents()->attach($eventId, [
            'is_marked' => false,
            'is_viewed' => true
        ]);
    }

    /**
     * Kiểm tra xem sự kiện có được đánh dấu không
     * @param int $eventId
     * @return bool
     */
    public function hasMarkedEvent(int $eventId): bool
    {
        return $this->markedEvents()
                    ->where('event_id', $eventId)
                    ->wherePivot('is_marked', true)
                    ->exists();
    }

    /**
     * Kiểm tra xem sự kiện đã được xem chưa
     * @param int $eventId
     * @return bool
     */
    public function hasViewedEvent(int $eventId): bool
    {
        return $this->markedEvents()
                    ->where('event_id', $eventId)
                    ->wherePivot('is_viewed', true)
                    ->exists();
    }
}
