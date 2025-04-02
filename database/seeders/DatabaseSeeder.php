<?php

namespace Database\Seeders;

use Throwable;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->call([
                DepartmentsTableSeeder::class,
                UsersTableSeeder::class,
                RolesTableSeeder::class,
                PermissionsTableSeeder::class,
                PermissionGroupsTableSeeder::class,
                PermissionTypesTableSeeder::class,
                EventsTableSeeder::class,
                AttachmentsTableSeeder::class,
                // Thêm các seeder khác nếu có
            ]);

            // Commit nếu tất cả thành công
            DB::commit();
        } catch (Throwable $e) {
            // Rollback nếu có bất kỳ lỗi nào
            DB::rollBack();

            // In ra lỗi để debug
            $this->command->error('Seeder failed: ' . $e->getMessage());
            throw $e; // Ném lỗi tiếp cho Artisan thấy
        }
    }
}
