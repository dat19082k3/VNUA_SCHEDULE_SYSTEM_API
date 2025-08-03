<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class UserDepartmentsSeeder extends Seeder
{
    public function run()
    {
        // Get all users and departments
        $users = User::all();
        $departments = Department::all();

        // Clear existing relationships to prevent duplicates
        DB::table('user_departments')->truncate();

        foreach ($users as $user) {
            // Skip users without a primary department
            if (!$user->primary_department_id) {
                continue;
            }

            // Always add the primary department as a regular department
            DB::table('user_departments')->insert([
                'user_id' => $user->id,
                'department_id' => $user->primary_department_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Add 0-3 random additional departments
            $additionalDepartmentsCount = rand(0, 3);
            if ($additionalDepartmentsCount > 0) {
                $departmentIds = $departments->where('id', '!=', $user->primary_department_id)
                    ->random($additionalDepartmentsCount)
                    ->pluck('id');

                foreach ($departmentIds as $departmentId) {
                    DB::table('user_departments')->insert([
                        'user_id' => $user->id,
                        'department_id' => $departmentId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
