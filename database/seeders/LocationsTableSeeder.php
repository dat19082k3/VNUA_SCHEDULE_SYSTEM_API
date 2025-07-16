<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
         $locations = [
            ['id' => 41, 'name' => 'Hội trường A'],
            ['id' => 40, 'name' => 'Hội trường B'],
            ['id' => 62, 'name' => 'Hội trường C'],
            ['id' => 19, 'name' => 'Hội trường Nguyệt Quế'],
            ['id' => 42, 'name' => 'Hội trường Trung Tâm'],
            ['id' => 39, 'name' => 'Phòng họp Thảo'],
            ['id' => 38, 'name' => 'Phòng họp Anh Đào'],
            ['id' => 37, 'name' => 'Phòng họp Bằng Lăng'],
            ['id' => 59, 'name' => 'Phòng họp Dỗ Quyên'],
            ['id' => 21, 'name' => 'Phòng họp Giáng Hương'],
            ['id' => 22, 'name' => 'Phòng họp Hoa Mộc'],
            ['id' => 20, 'name' => 'Phòng họp Hoa Sữa'],
            ['id' => 36, 'name' => 'Phòng họp Hoa Sứ'],
        ];

        foreach ($locations as $location) {
            DB::table('locations')->insert([
                'id' => $location['id'],
                'name' => $location['name'],
                // Nếu là id = 0 thì slug theo tên, còn lại slug = id
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
