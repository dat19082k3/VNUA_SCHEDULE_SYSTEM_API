<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'departments';

    protected $fillable = [
        'name',
        'description'
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    // Mối quan hệ nhiều-nhiều với bảng User
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_departments')
                    ->withTimestamps();
    }

    // Mối quan hệ một-nhiều với bảng User (người dùng có phòng ban này là primary)
    public function primaryUsers()
    {
        return $this->hasMany(User::class, 'primary_department_id');
    }

    // Mối quan hệ nhiều-nhiều với bảng Event qua bảng event_preparers
    public function preparedEvents()
    {
        return $this->belongsToMany(Event::class, 'event_preparers', 'department_id', 'event_id')
                    ->withTimestamps();
    }

    /**
     * Scope: Lọc các phòng ban theo tên
     */
    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'LIKE', "%{$name}%");
    }

    /**
     * Scope: Lọc các phòng ban không bị xóa
     */
    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    /**
     * Phương thức để lấy các phòng ban theo tên
     */
    public static function getDepartmentsByName(string $name)
    {
        return self::active()->byName($name)->get();
    }

    /**
     * Phương thức để xóa mềm phòng ban
     */
    public function deleteDepartment(): bool
    {
        return $this->delete();
    }

    /**
     * Phương thức để khôi phục phòng ban đã bị xóa mềm
     */
    public static function restoreDepartment(int $id): bool
    {
        $department = self::withTrashed()->find($id);
        if ($department) {
            return $department->restore();
        }
        return false;
    }
}
