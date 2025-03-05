<?php

namespace App\Constants;

class Permissions
{
    /**
     * Quyền truy cập theo thao tác: LIST (xem danh sách)
     */
    public const LIST = [
        // Quản lý người dùng
        'users'        => 'list-user-management',
        // Quản lý vai trò
        'roles'        => 'list-role-management',
        // Quản lý quyền hạn
        'permissions'  => 'list-permission-management',
        // Quản lý phòng ban
        'departments'  => 'list-department-management',
        // Quản lý sự kiện
        'events'       => 'list-event-management',
        // Quản lý tài liệu đính kèm
        'attachments'  => 'list-attachment-management',
        // Quản lý nhóm quyền hạn
        'permission_groups' => 'list-permission-group-management',
        // Quản lý loại quyền hạn
        'permission_types'  => 'list-permission-type-management',
    ];

    /**
     * Quyền truy cập theo thao tác: CREATE (thêm mới)
     */
    public const CREATE = [
        'users'        => 'create-user-management',
        'roles'        => 'create-role-management',
        'permissions'  => 'create-permission-management',
        'departments'  => 'create-department-management',
        'events'       => 'create-event-management',
        'attachments'  => 'create-attachment-management',
        'permission_groups' => 'create-permission-group-management',
        'permission_types'  => 'create-permission-type-management',
    ];

    /**
     * Quyền truy cập theo thao tác: UPDATE (chỉnh sửa)
     */
    public const UPDATE = [
        'users'        => 'update-user-management',
        'roles'        => 'update-role-management',
        'permissions'  => 'update-permission-management',
        'departments'  => 'update-department-management',
        'events'       => 'update-event-management',
        'attachments'  => 'update-attachment-management',
        'permission_groups' => 'update-permission-group-management',
        'permission_types'  => 'update-permission-type-management',
    ];

    /**
     * Quyền truy cập theo thao tác: DELETE (xóa)
     */
    public const DELETE = [
        'users'        => 'delete-user-management',
        'roles'        => 'delete-role-management',
        'permissions'  => 'delete-permission-management',
        'departments'  => 'delete-department-management',
        'events'       => 'delete-event-management',
        'attachments'  => 'delete-attachment-management',
        'permission_groups' => 'delete-permission-group-management',
        'permission_types'  => 'delete-permission-type-management',
    ];

    /**
     * Quyền truy cập theo thao tác: REFRESH (Tải lại)
     */
    public const REFRESH = [
        'token'        => 'refresh-token-management',
    ];

    /**
     * Lấy tất cả quyền dưới dạng một mảng có cấu trúc phân nhóm thao tác.
     *
     * @return array
     */
    public static function all(): array
    {
        return array_values(array_merge(self::LIST, self::CREATE, self::UPDATE, self::DELETE));
    }
}
