<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionTypesTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissionTypes = [
            [
                'code' => 'read',
                'name' => 'Xem',
                'position' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'create',
                'name' => 'Tạo mới',
                'position' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'update',
                'name' => 'Cập nhật',
                'position' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'delete',
                'name' => 'Xóa',
                'position' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('permission_types')->insert($permissionTypes);
    }
}
