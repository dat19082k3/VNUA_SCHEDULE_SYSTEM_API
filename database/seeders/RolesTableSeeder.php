<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            'name' => 'Admin',
            'description' => 'Quản trị viên hệ thống',
            'protected' => 1,
            'parent_id' => null,  // Vai trò cấp cao không có parent
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
