<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionsTableSeeder extends Seeder
{
    public function run()
    {
        // Thêm các quyền hạn cơ bản
        DB::table('permissions')->insert([
            [
                'code' => 'create_event',
                'name' => 'Tạo sự kiện',
                'description' => 'Quyền tạo sự kiện mới',
                'permission_group_code' => 'event_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'edit_event',
                'name' => 'Sửa sự kiện',
                'description' => 'Quyền sửa sự kiện',
                'permission_group_code' => 'event_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'delete_event',
                'name' => 'Xoá sự kiện',
                'description' => 'Quyền xoá sự kiện',
                'permission_group_code' => 'event_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'view_event',
                'name' => 'Xem sự kiện',
                'description' => 'Quyền xem chi tiết sự kiện',
                'permission_group_code' => 'event_management',
                'permission_type_code' => 'read',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'manage_users',
                'name' => 'Quản lý người dùng',
                'description' => 'Quyền quản lý người dùng hệ thống',
                'permission_group_code' => 'user_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'view_users',
                'name' => 'Xem người dùng',
                'description' => 'Quyền xem thông tin người dùng',
                'permission_group_code' => 'user_management',
                'permission_type_code' => 'read',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'edit_user_permissions',
                'name' => 'Chỉnh sửa quyền người dùng',
                'description' => 'Quyền chỉnh sửa quyền hạn của người dùng',
                'permission_group_code' => 'user_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'delete_user',
                'name' => 'Xoá người dùng',
                'description' => 'Quyền xoá người dùng khỏi hệ thống',
                'permission_group_code' => 'user_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'create_attachments',
                'name' => 'Tạo tài liệu đính kèm',
                'description' => 'Quyền tạo tài liệu đính kèm cho sự kiện',
                'permission_group_code' => 'attachment_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'view_attachments',
                'name' => 'Xem tài liệu đính kèm',
                'description' => 'Quyền xem tài liệu đính kèm của sự kiện',
                'permission_group_code' => 'attachment_management',
                'permission_type_code' => 'read',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'delete_attachment',
                'name' => 'Xoá tài liệu đính kèm',
                'description' => 'Quyền xoá tài liệu đính kèm của sự kiện',
                'permission_group_code' => 'attachment_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'manage_roles',
                'name' => 'Quản lý vai trò',
                'description' => 'Quyền quản lý vai trò của người dùng',
                'permission_group_code' => 'role_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'view_roles',
                'name' => 'Xem vai trò',
                'description' => 'Quyền xem thông tin vai trò người dùng',
                'permission_group_code' => 'role_management',
                'permission_type_code' => 'read',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'code' => 'assign_roles',
                'name' => 'Gán vai trò',
                'description' => 'Quyền gán vai trò cho người dùng',
                'permission_group_code' => 'role_management',
                'permission_type_code' => 'write',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}
