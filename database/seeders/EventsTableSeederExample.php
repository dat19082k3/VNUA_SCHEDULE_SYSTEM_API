<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;

class EventsTableSeederExample extends Seeder
{
    /**
     * Generate random participants for an event
     * @param array $departments
     * @return array
     */
    private function getRandomParticipants($departments)
    {
        $participants = [];

        // Add random users
        $userCount = rand(2, 8);
        $users = User::inRandomOrder()->limit($userCount)->get();
        foreach ($users as $user) {
            $participants[] = [
                'type' => 'user',
                'id' => $user->id
            ];
        }

        // Add random departments
        $departmentCount = rand(1, 3);
        $departmentKeys = array_rand($departments, $departmentCount);
        if (!is_array($departmentKeys)) {
            $departmentKeys = [$departmentKeys];
        }

        foreach ($departmentKeys as $key) {
            $department = $departments[array_keys($departments)[$key]];
            $participants[] = [
                'type' => 'department',
                'id' => $department->id
            ];
        }

        return $participants;
    }

    public function run()
    {
        // Create or reference departments
        $departments = [
            'Khoa CNTT' => Department::firstOrCreate(['name' => 'Khoa CNTT'], ['id' => 1]),
            'Ban CSVC và ĐT' => Department::firstOrCreate(['name' => 'Ban CSVC và ĐT']),
            'VPĐU' => Department::firstOrCreate(['name' => 'VPĐU']),
            // Add more departments here...
        ];

        // Create or reference locations
        $locations = [
            'Phòng họp Bằng Lăng' => Location::firstOrCreate(['name' => 'Phòng họp Bằng Lăng']),
            'Hội trường 4A' => Location::firstOrCreate(['name' => 'Hội trường 4A']),
            // Add more locations here...
        ];

        // Lấy tất cả người dùng để chọn ngẫu nhiên làm người tạo sự kiện
        $users = User::all();

        // Events from the schedule - just a few examples
        $events = [
            [
                'title' => 'Họp Hội đồng thanh lý tài sản',
                'description' => 'Thành viên Hội đồng theo QĐ của GĐHV',
                'start_time' => Carbon::create(2025, 7, 7, 9, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                'end_time' => Carbon::create(2025, 7, 7, 11, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                'host' => 'Nguyễn Công Tiệp',
                'participants' => $this->getRandomParticipants($departments),
                'status' => 'approved',
                'creator_id' => $users->random()->id,
                'locations' => ['Phòng họp Bằng Lăng'],
                'preparers' => ['Ban CSVC và ĐT'],
            ],
            [
                'title' => 'Tham dự Hội nghị nhân sự',
                'description' => 'Xe đón tại sảnh Nhà Trung tâm lúc 13h30',
                'start_time' => Carbon::create(2025, 7, 7, 14, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                'end_time' => Carbon::create(2025, 7, 7, 16, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                'host' => 'Ban Tổ chức',
                'participants' => $this->getRandomParticipants($departments),
                'status' => 'approved',
                'creator_id' => $users->random()->id,
                'locations' => ['Hội trường 4A'],
                'preparers' => ['VPĐU'],
            ],
            // Add more events here...
        ];

        // Update all events to use random creator and participants
        foreach ($events as &$eventData) {
            $eventData['creator_id'] = $users->random()->id;
            if (is_string($eventData['participants']) && !is_array(json_decode($eventData['participants'], true))) {
                $eventData['participants'] = $this->getRandomParticipants($departments);
            }
        }

        foreach ($events as $eventData) {
            $eventLocations = $eventData['locations'];
            $preparers = $eventData['preparers'];

            // Convert participants to JSON if it's not already
            if (!is_string($eventData['participants'])) {
                $eventData['participants'] = json_encode($eventData['participants']);
            }

            unset($eventData['locations'], $eventData['preparers']);

            // Create event
            $event = Event::create($eventData);

            // Attach locations
            foreach ($eventLocations as $locationName) {
                if (isset($locations[$locationName])) {
                    $event->locations()->attach($locations[$locationName]->id);
                } else {
                    throw new \Exception("Location not found: {$locationName}");
                }
            }

            // Attach preparer departments
            foreach ($preparers as $departmentName) {
                if (isset($departments[$departmentName])) {
                    $event->preparers()->attach($departments[$departmentName]->id);
                } else {
                    throw new \Exception("Department not found: {$departmentName}");
                }
            }
        }
    }
}
