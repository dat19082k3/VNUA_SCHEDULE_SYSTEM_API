<?php

namespace App\Http\Controllers;

use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(private readonly DepartmentService $departmentService) {}

    /**
     * Lấy tất cả department
     */
    public function index(): JsonResponse
    {
        $departments = $this->departmentService->getAllDepartments();

        return $this->sendSuccess(['departments' => $departments], 'Lấy danh sách phòng ban thành công');
    }

    /**
     * Lấy thông tin department theo ID
     */
    public function show(int $id): JsonResponse
    {
        try {
            $department = $this->departmentService->getDepartmentById($id);
        } catch (\Exception $e) {
            return $this->sendError('Department not found', 404);
        }

        return $this->sendSuccess(['department' => $department], 'Lấy thông tin phòng ban thành công');
    }

    /**
     * Tạo mới department
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $department = $this->departmentService->createDepartment($validated['name'], $validated['description']);

        return $this->sendSuccess(['department' => $department], 'Tạo phòng ban mới thành công');
    }

    /**
     * Cập nhật department
     */
    public function update(int $id, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        try {
            $department = $this->departmentService->updateDepartment($id, $validated['name'], $validated['description']);
        } catch (\Exception $e) {
            return $this->sendError('Department not found', 404);
        }

        return $this->sendSuccess(['department' => $department], 'Cập nhật thông tin phòng ban thành công');
    }

    /**
     * Xóa department
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->departmentService->deleteDepartment($id);
        } catch (\Exception $e) {
            return $this->sendError('Department not found', 404);
        }

        return $this->sendSuccess(null, 'Xóa phòng ban thành công');
    }
}
