<?php

namespace App\Services;

use App\Models\Department;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DepartmentService
{
    /**
     * Tạo mới một department
     */
    public function createDepartment(string $name, ?string $description): Department
    {
        return Department::create([
            'name' => $name,
            'description' => $description,
        ]);
    }

    /**
     * Cập nhật thông tin department
     */
    public function updateDepartment(int $id, string $name, ?string $description): Department
    {
        $department = Department::find($id);
        
        if (!$department) {
            throw new ModelNotFoundException('Department not found');
        }

        $department->update([
            'name' => $name,
            'description' => $description,
        ]);

        return $department;
    }

    /**
     * Lấy tất cả các department
     */
    public function getAllDepartments(): Collection
    {
        return Department::all();
    }

    /**
     * Lấy thông tin department theo ID
     */
    public function getDepartmentById(int $id): Department
    {
        $department = Department::find($id);

        if (!$department) {
            throw new ModelNotFoundException('Department not found');
        }

        return $department;
    }

    /**
     * Xóa department
     */
    public function deleteDepartment(int $id): bool
    {
        $department = Department::find($id);

        if (!$department) {
            throw new ModelNotFoundException('Department not found');
        }

        return $department->delete();
    }
}
