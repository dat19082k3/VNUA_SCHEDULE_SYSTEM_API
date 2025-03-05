<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    use SoftDeletes, HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'avatar', 'first_name', 'last_name', 'email', 'phone', 'password', 'status', 'protected', 'department_id'
    ];

    protected $hidden = ['password', 'deleted_at'];

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function uploadedAttachments()
    {
        return $this->hasMany(Attachment::class, 'uploader_id');
    }

}
