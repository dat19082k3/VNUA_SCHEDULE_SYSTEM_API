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

    /**
     * Quan hệ nhiều-nhiều với chính nó để xác định các phòng ban cha.
     */
    public function parents()
    {
        return $this->belongsToMany(self::class, 'department_parents', 'department_id', 'parent_id');
    }

    /**
     * Quan hệ nhiều-nhiều với chính nó để xác định các phòng ban con.
     */
    public function children()
    {
        return $this->belongsToMany(self::class, 'department_parents', 'parent_id', 'department_id');
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

    /**
     * Phương thức để lấy thông tin phòng ban dưới dạng mảng
     */
    public function toArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'parent_ids' => $this->parents()->pluck('id')->toArray(),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * Phương thức để kiểm tra xem phòng ban có tồn tại hay không
     */
    public static function exists(int $id): bool
    {
        return self::where('id', $id)->exists();
    }

    /**
     * Thiết lập các phòng ban cha
     */
    public function setParents(array $parentIds)
    {
        $this->parents()->sync($parentIds);
    }
}
