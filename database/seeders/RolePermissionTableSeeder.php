<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionTableSeeder extends Seeder
{
    public function run()
    {
        // Định nghĩa tất cả các quyền (permissions) với tên hiển thị và mô tả
        $permissions = [
            // Quyền cho tài nguyên Events
            [
                'name' => 'super_admin',
                'display_name' => 'Quản trị viên hệ thống',
                'description' => 'Toàn quyền quản lý hệ thống, bao gồm tất cả các quyền hạn',
            ],
            [
                'name' => 'view_events',
                'display_name' => 'Xem sự kiện',
                'description' => 'Xem danh sách và chi tiết tất cả sự kiện'
            ],
            [
                'name' => 'view_own_events',
                'display_name' => 'Xem sự kiện cá nhân',
                'description' => 'Xem các sự kiện do bản thân tạo'
            ],
            [
                'name' => 'create_events',
                'display_name' => 'Tạo sự kiện',
                'description' => 'Tạo mới sự kiện'
            ],
            [
                'name' => 'edit_own_events',
                'display_name' => 'Sửa sự kiện cá nhân',
                'description' => 'Chỉnh sửa các sự kiện do bản thân tạo'
            ],
            [
                'name' => 'edit_all_events',
                'display_name' => 'Sửa mọi sự kiện',
                'description' => 'Chỉnh sửa tất cả sự kiện trong hệ thống'
            ],
            [
                'name' => 'delete_own_events',
                'display_name' => 'Xóa sự kiện cá nhân',
                'description' => 'Xóa các sự kiện do bản thân tạo'
            ],
            [
                'name' => 'delete_all_events',
                'display_name' => 'Xóa mọi sự kiện',
                'description' => 'Xóa bất kỳ sự kiện nào trong hệ thống'
            ],
            [
                'name' => 'approve_events',
                'display_name' => 'Duyệt sự kiện',
                'description' => 'Phê duyệt các sự kiện được gửi lên'
            ],

            // Quyền cho tài nguyên Departments
            [
                'name' => 'view_departments',
                'display_name' => 'Xem phòng ban',
                'description' => 'Xem danh sách và chi tiết phòng ban'
            ],
            [
                'name' => 'create_departments',
                'display_name' => 'Tạo phòng ban',
                'description' => 'Tạo mới phòng ban'
            ],
            [
                'name' => 'edit_departments',
                'display_name' => 'Sửa phòng ban',
                'description' => 'Chỉnh sửa thông tin phòng ban'
            ],
            [
                'name' => 'delete_departments',
                'display_name' => 'Xóa phòng ban',
                'description' => 'Xóa phòng ban khỏi hệ thống'
            ],

            // Quyền cho tài nguyên Locations
            [
                'name' => 'view_locations',
                'display_name' => 'Xem địa điểm',
                'description' => 'Xem danh sách và chi tiết địa điểm'
            ],
            [
                'name' => 'create_locations',
                'display_name' => 'Tạo địa điểm',
                'description' => 'Tạo mới địa điểm'
            ],
            [
                'name' => 'edit_locations',
                'display_name' => 'Sửa địa điểm',
                'description' => 'Chỉnh sửa thông tin địa điểm'
            ],
            [
                'name' => 'delete_locations',
                'display_name' => 'Xóa địa điểm',
                'description' => 'Xóa địa điểm khỏi hệ thống'
            ],

            // Quyền cho tài nguyên Attachments
            [
                'name' => 'view_attachments',
                'display_name' => 'Xem tệp đính kèm',
                'description' => 'Xem danh sách và chi tiết tệp đính kèm'
            ],
            [
                'name' => 'create_attachments',
                'display_name' => 'Tạo tệp đính kèm',
                'description' => 'Tải lên tệp đính kèm'
            ],
            [
                'name' => 'edit_attachments',
                'display_name' => 'Sửa tệp đính kèm',
                'description' => 'Chỉnh sửa thông tin tệp đính kèm'
            ],
            [
                'name' => 'delete_attachments',
                'display_name' => 'Xóa tệp đính kèm',
                'description' => 'Xóa tệp đính kèm khỏi hệ thống'
            ],

            // Quyền quản lý người dùng
            [
                'name' => 'view_users',
                'display_name' => 'Xem người dùng',
                'description' => 'Xem danh sách và thông tin người dùng'
            ],
            [
                'name' => 'assign_roles',
                'display_name' => 'Gán vai trò',
                'description' => 'Gán vai trò cho người dùng'
            ],
            [
                'name' => 'assign_permissions',
                'display_name' => 'Gán quyền',
                'description' => 'Gán quyền cho vai trò'
            ],
            [
                'name' => 'delete_users',
                'display_name' => 'Xóa người dùng',
                'description' => 'Xóa người dùng khỏi hệ thống'
            ],
            [
                'name' => 'view_roles',
                'display_name' => 'Xem vai trò',
                'description' => 'Xem danh sách và thông tin vai trò'
            ],
            [
                'name' => 'view_permissions',
                'display_name' => 'Xem quyền hạn',
                'description' => 'Xem danh sách và thông tin quyền hạn'
            ],

            // Quyền cá nhân
            [
                'name' => 'view_profile',
                'display_name' => 'Xem hồ sơ',
                'description' => 'Xem thông tin cá nhân'
            ],
            [
                'name' => 'update_profile',
                'display_name' => 'Cập nhật hồ sơ',
                'description' => 'Cập nhật thông tin cá nhân'
            ],
            [
                'name' => 'refresh_token',
                'display_name' => 'Làm mới token',
                'description' => 'Làm mới token truy cập'
            ],
        ];

        // Tạo các quyền trong cơ sở dữ liệu
        foreach ($permissions as $permission) {
            Permission::create([
                'name' => $permission['name'],
                'display_name' => $permission['display_name'],
                'description' => $permission['description'],
                'guard_name' => 'web'
            ]);
        }

        // Định nghĩa các vai trò với mô tả
        $roles = [
            [
                'name' => 'chief_of_office',
                'display_name' => 'Chánh văn phòng',
                'description' => 'Quản lý toàn bộ hệ thống sự kiện, có quyền cao nhất',
                'permissions' => [
                    'view_events',
                    'view_own_events',
                    'create_events',
                    'edit_own_events',
                    'edit_all_events',
                    'delete_own_events',
                    'delete_all_events',
                    'approve_events',
                    'view_departments',
                    'create_departments',
                    'edit_departments',
                    'delete_departments',
                    'view_locations',
                    'create_locations',
                    'edit_locations',
                    'delete_locations',
                    'view_attachments',
                    'create_attachments',
                    'edit_attachments',
                    'delete_attachments',
                    'view_users',
                    'assign_roles',
                    'assign_permissions',
                    'delete_users',
                    'view_profile',
                    'update_profile',
                    'refresh_token',
                    'view_roles',
                    'view_permissions'
                ]
            ],
            [
                'name' => 'institute_secretary',
                'display_name' => 'Văn thư Học viện',
                'description' => 'Quản lý sự kiện cấp học viện',
                'permissions' => [
                    'view_events',
                    'view_own_events',
                    'create_events',
                    'edit_all_events',
                    'edit_own_events',
                    'delete_all_events',
                    'delete_own_events',
                    'view_departments',
                    'view_locations',
                    'view_attachments',
                    'create_attachments',
                    'edit_attachments',
                    'delete_attachments',
                    'view_profile',
                    'update_profile',
                    'refresh_token'
                ]
            ],
            [
                'name' => 'department_secretary',
                'display_name' => 'Văn thư đơn vị',
                'description' => 'Quản lý sự kiện cấp đơn vị (khoa/phòng)',
                'permissions' => [
                    'view_events',
                    'view_own_events',
                    'create_events',
                    'edit_own_events',
                    'delete_own_events',
                    'view_departments',
                    'view_locations',
                    'view_attachments',
                    'create_attachments',
                    'edit_attachments',
                    'delete_attachments',
                    'view_profile',
                    'update_profile',
                    'refresh_token'
                ]
            ],
            [
                'name' => 'staff',
                'display_name' => 'Cán bộ viên chức',
                'description' => 'Xem thông tin sự kiện và quản lý cá nhân',
                'permissions' => [
                    'view_events',
                    'view_own_events',
                    'view_departments',
                    'view_locations',
                    'view_attachments',
                    'view_profile',
                    'update_profile',
                    'refresh_token'
                ]
            ]
        ];

        // Tạo các vai trò và gán quyền
        foreach ($roles as $role) {
            $newRole = Role::create([
                'name' => $role['name'],
                'display_name' => $role['display_name'],
                'description' => $role['description'],
                'guard_name' => 'web'
            ]);

            $newRole->givePermissionTo($role['permissions']);
        }
    }
}
