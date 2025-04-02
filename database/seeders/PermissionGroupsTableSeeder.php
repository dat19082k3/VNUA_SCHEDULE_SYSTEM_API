<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionGroupsTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissionGroups = [
            [
                'id' => 'PG1',
                'code' => 'user_management',
                'name' => 'Quản lý người dùng',
                'description' => 'Quản lý các thông tin liên quan tới người dùng',
                'parent_code' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'PG2',
                'code' => 'calendar_management',
                'name' => 'Quản lý lịch',
                'description' => 'Quản lý các sự kiện và lịch làm việc',
                'parent_code' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 'PG3',
                'code' => 'permission_management',
                'name' => 'Quản lý quyền hạn',
                'description' => 'Quản lý các quyền hạn hệ thống',
                'parent_code' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Có thể thêm các nhóm quyền khác nếu cần
        ];

        DB::table('permission_groups')->insert($permissionGroups);
    }
}
