<?php

namespace Database\Seeders;

use Throwable;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run each seeder individually to avoid transaction issues
        try {
            $this->call([
                DepartmentsTableSeeder::class,
                LocationsTableSeeder::class,
                RolePermissionTableSeeder::class,
                UsersTableSeeder::class,
                UserDepartmentsSeeder::class,
                EventsTableSeeder::class,
                UserEventsSeeder::class,
                // AttachmentsTableSeeder::class,
                // Thêm các seeder khác nếu có
            ]);

        } catch (Throwable $e) {
            // In ra lỗi để debug
            $this->command->error('Seeder failed: ' . $e->getMessage());
            throw $e; // Ném lỗi tiếp cho Artisan thấy
        }
    }
}
