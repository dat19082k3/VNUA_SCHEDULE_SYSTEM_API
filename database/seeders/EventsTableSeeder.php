<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EventsTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('events')->insert([
            'description' => 'Lớp học về lập trình',
            'location' => 'Hội trường A',
            'start_time' => Carbon::now()->addDays(1)->toDateTimeString(),
            'end_time' => Carbon::now()->addDays(1)->addHours(2)->toDateTimeString(),
            'host' => 'Giảng viên A',
            'participants' => 'Giảng viên A, Sinh viên B, Sinh viên C',
            'reminder_type' => 'calendar',
            'reminder_time' => Carbon::now()->addDays(1)->subMinutes(30)->toDateTimeString(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
    }
}
