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
        'department_id',
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

    // Mối quan hệ với bảng Department
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    // Mối quan hệ với các Attachments mà user đã tải lên
    public function uploadedAttachments()
    {
        return $this->hasMany(Attachment::class, 'uploader_id');
    }

    // Thêm các phương thức hữu ích cho model này, ví dụ như fullname
    public function getFullNameAttribute()
    {
        return $this->last_name . ' ' . $this->first_name;
    }
}
