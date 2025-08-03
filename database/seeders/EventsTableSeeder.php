<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Event;
use App\Models\Location;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;

class EventsTableSeeder extends Seeder
{
    /**
     * Get a random user ID with a role other than "staff"
     * @return int
     */
    private function getRandomNonStaffCreatorId()
    {
        // Get users that don't have the "staff" role
        $nonStaffUsers = User::whereHas('roles', function ($query) {
            $query->where('name', '!=', 'staff');
        })->inRandomOrder()->get();

        // Fallback to any user if no non-staff users found
        if ($nonStaffUsers->isEmpty()) {
            return User::inRandomOrder()->first()->id;
        }

        return $nonStaffUsers->random()->id;
    }

    /**
     * Generate random participants for an event
     * @param array $departments
     * @param \Illuminate\Database\Eloquent\Collection $users All available users
     * @return array
     */
    private function getRandomParticipants($departments, $users)
    {
        $participants = [];

        // Add random users (2-8 users)
        $userCount = rand(2, 8);
        $selectedUsers = $users->random(min($userCount, $users->count()));
        foreach ($selectedUsers as $user) {
            $participants[] = [
                'type' => 'user',
                'id' => $user->id
            ];
        }

        // Add random departments (1-3 departments)
        $departmentCount = min(rand(1, 3), count($departments));
        $departmentKeys = array_rand($departments, $departmentCount);
        if (!is_array($departmentKeys)) {
            $departmentKeys = [$departmentKeys];
        }

        foreach ($departmentKeys as $key) {
            // Get department directly using the key returned by array_rand
            $department = $departments[$key];
            $participants[] = [
                'type' => 'department',
                'id' => $department->id
            ];
        }

        return $participants;
    }

    public function run()
    {
        try {
            // Create or reference departments
            $departments = [
                'Khoa CNTT' => Department::firstOrCreate(['name' => 'Khoa CNTT'], ['id' => 1]),
                'Ban CSVC và ĐT' => Department::firstOrCreate(['name' => 'Ban CSVC và ĐT']),
                'VPĐU' => Department::firstOrCreate(['name' => 'VPĐU']),
                'Tổ công tác' => Department::firstOrCreate(['name' => 'Tổ công tác']),
                'Ban KHCN' => Department::firstOrCreate(['name' => 'Ban KHCN']),
                'TT QHCC&HTSV' => Department::firstOrCreate(['name' => 'TT QHCC&HTSV']),
                'NXB Học viện NN' => Department::firstOrCreate(['name' => 'NXB Học viện NN']),
                'Ban TCKT' => Department::firstOrCreate(['name' => 'Ban TCKT']),
                'Đoàn TN' => Department::firstOrCreate(['name' => 'Đoàn TN']),
                'Khoa TNMT' => Department::firstOrCreate(['name' => 'Khoa TNMT']),
                'Khoa Cơ Điện' => Department::firstOrCreate(['name' => 'Khoa Cơ Điện']),
                'Ban ĐBCL và Pháp chế' => Department::firstOrCreate(['name' => 'Ban ĐBCL và Pháp chế']),
                'Ban HTQT' => Department::firstOrCreate(['name' => 'Ban HTQT']),
                'TT GDTCTT' => Department::firstOrCreate(['name' => 'TT GDTCTT']),
                'TT NCXS&ĐMST' => Department::firstOrCreate(['name' => 'TT NCXS&ĐMST']),
                'Ban QLĐT' => Department::firstOrCreate(['name' => 'Ban QLĐT']),
                'Ban CTCT&CTSV' => Department::firstOrCreate(['name' => 'Ban CTCT&CTSV']),
                'VPHV' => Department::firstOrCreate(['name' => 'VPHV']),
                'TT CUNNL' => Department::firstOrCreate(['name' => 'TT CUNNL']),
                'Viện AMI' => Department::firstOrCreate(['name' => 'Viện AMI']),
                'Khoa KT&QL' => Department::firstOrCreate(['name' => 'Khoa KT&QL']),
                'Khoa DL&NN' => Department::firstOrCreate(['name' => 'Khoa DL&NN']),
                'Khoa KT&QTKD' => Department::firstOrCreate(['name' => 'Khoa KT&QTKD']),
                'Khoa Thủy sản' => Department::firstOrCreate(['name' => 'Khoa Thủy sản']),
                'TT Tin học' => Department::firstOrCreate(['name' => 'TT Tin học']),
                'Khoa Chăn nuôi' => Department::firstOrCreate(['name' => 'Khoa Chăn nuôi']),
                'Khoa Kinh tế' => Department::firstOrCreate(['name' => 'Khoa Kinh tế']),
                'Khoa KT&QLNN' => Department::firstOrCreate(['name' => 'Khoa KT&QLNN']),
                'Khoa Kinh tế và PTNT' => Department::firstOrCreate(['name' => 'Khoa Kinh tế và PTNT']),
                'Khoa Thú y' => Department::firstOrCreate(['name' => 'Khoa Thú y']),
                'TT giống vật nuôi CLC' => Department::firstOrCreate(['name' => 'TT giống vật nuôi CLC']),
            ];

            // Create or reference locations
            $locations = [
                'Phòng họp Bằng Lăng' => Location::firstOrCreate(['name' => 'Phòng họp Bằng Lăng']),
                'Hội trường 4A, Trụ sở Thàng ủy' => Location::firstOrCreate(['name' => 'Hội trường 4A, Trụ sở Thàng ủy']),
                'Phòng họp Giáng Hương' => Location::firstOrCreate(['name' => 'Phòng họp Giáng Hương']),
                'Hội trường Trung Tâm' => Location::firstOrCreate(['name' => 'Hội trường Trung Tâm']),
                'Nhà xuất bản Học viện NN' => Location::firstOrCreate(['name' => 'Nhà xuất bản Học viện NN']),
                'Giảng trường trung tâm' => Location::firstOrCreate(['name' => 'Giảng trường trung tâm']),
                'Bộ Khoa học và Công nghệ' => Location::firstOrCreate(['name' => 'Bộ Khoa học và Công nghệ']),
                'Công trường thi công' => Location::firstOrCreate(['name' => 'Công trường thi công']),
                'Trường Đại học Cần Thơ' => Location::firstOrCreate(['name' => 'Trường Đại học Cần Thơ']),
                'Trụ sở HĐND-UBND xã Thạch Thất' => Location::firstOrCreate(['name' => 'Trụ sở HĐND-UBND xã Thạch Thất']),
                'Hội trường Nguyệt Quế' => Location::firstOrCreate(['name' => 'Hội trường Nguyệt Quế']),
                'Phòng họp Hoa Sứ' => Location::firstOrCreate(['name' => 'Phòng họp Hoa Sứ']),
                'KS JW Marirott' => Location::firstOrCreate(['name' => 'KS JW Marirott']),
                'Phòng họp Anh Đào' => Location::firstOrCreate(['name' => 'Phòng họp Anh Đào']),
                'Phòng Tiếp dân' => Location::firstOrCreate(['name' => 'Phòng Tiếp dân']),
                'Phòng 405, tòa nhà Bùi Huy Đáp' => Location::firstOrCreate(['name' => 'Phòng 405, tòa nhà Bùi Huy Đáp']),
                'Khoa Du lịch và Ngoại ngữ' => Location::firstOrCreate(['name' => 'Khoa Du lịch và Ngoại ngữ']),
            ];

            // Get users with roles different from "staff" to be event creators
            $creatorUsers = User::whereDoesntHave('roles', function ($query) {
                $query->where('name', 'staff');
            })->get();

            // Make sure we have at least one creator user
            if ($creatorUsers->isEmpty()) {
                $creatorUsers = User::all(); // Fallback to all users if no non-staff users found
            }

            // Get a set of users that can be hosts
            $hostUsers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'manager', 'leader']);
            })->get();

            // Fallback if there are no users with these roles
            if ($hostUsers->isEmpty()) {
                $hostUsers = User::take(10)->get();
            }

            // Get all users for participants
            $users = User::all();

            // Events from the schedule
            $events = [
                [
                    'title' => 'Họp Hội đồng thanh lý tài sản',
                    'description' => 'Thành viên Hội đồng theo QĐ của GĐHV',
                    'start_time' => Carbon::create(2025, 7, 7, 9, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 7, 11, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng họp Bằng Lăng'],
                    'preparers' => ['Ban CSVC và ĐT'],
                ],
                [
                    'title' => 'Tham dự Hội nghị thực hiện quy trình nhân sự lần đầu tham gia cấp ủy ĐBK nhiệm kỳ 2025 - 2030',
                    'description' => 'Xe đón thầy/cô tại sảnh Nhà Trung tâm lúc 13h30',
                    'start_time' => Carbon::create(2025, 7, 7, 14, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 7, 16, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Hội trường 4A, Trụ sở Thàng ủy'],
                    'preparers' => ['VPĐU'],
                ],
                [
                    'title' => 'Họp chuẩn bị làm việc với Bộ KHCN về đề án TT ĐMST Nông nghiệp Quốc gia',
                    'description' => 'Ban Giám đốc, Trưởng các đơn vị: KHCN, HTQT, TCKT, CSVC&ĐT, QLĐT, CTCT&CTSV, TCCB',
                    'start_time' => Carbon::create(2025, 7, 7, 16, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 7, 17, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng họp Giáng Hương'],
                    'preparers' => ['Tổ công tác'],
                ],
                [
                    'title' => 'Hội nghị phổ biến thông tin tuyển sinh trình độ đại học, thạc sĩ, tiến sĩ',
                    'description' => 'Cán bộ, viên chức, người lao động có mặt tại Hội trường trước 15 phút để điểm danh',
                    'start_time' => Carbon::create(2025, 7, 8, 8, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 8, 10, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Hội trường Trung Tâm'],
                    'preparers' => ['TT QHCC&HTSV'],
                ],
                [
                    'title' => 'Họp sơ kết 6 tháng đầu năm 2025, triển khai nhiệm vụ 6 tháng cuối năm của NXB',
                    'description' => 'Toàn thể cán bộ, viên chức, người lao động thuộc Nhà xuất bản',
                    'start_time' => Carbon::create(2025, 7, 8, 8, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 8, 11, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Nhà xuất bản Học viện NN'],
                    'preparers' => ['NXB Học viện NN'],
                ],
                [
                    'title' => 'Gặp mặt đoàn đại biểu tham dự Đại hội đại biểu CĐNĐ Trung ương lần thứ IV',
                    'description' => 'Ban Giám đốc, Ban Thường vụ Công đoàn trường, Ban Thường vụ Đoàn thanh niên, Ban chấp hành Hội sinh viên, Đoàn Đại biểu tham dự Đại hội đại biểu CĐNĐ Trung ương lần thứ IV (Dự kiến 25 đại biểu)',
                    'start_time' => Carbon::create(2025, 7, 8, 9, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 8, 10, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng họp Giáng Hương'],
                    'preparers' => ['Đoàn TN'],
                ],
                [
                    'title' => 'Hội nghị Tổng kết năm học 2024-2025 và triển khai kế hoạch năm học 2025-2026',
                    'description' => 'Đảng ủy, BGĐ HV, Trưởng, Phó các đơn vị, Bí thư Đảng bộ, chi bộ và toàn thể giảng viên',
                    'start_time' => Carbon::create(2025, 7, 8, 14, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 8, 16, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Giảng trường trung tâm'],
                    'preparers' => ['Ban QLĐT'],
                ],
                [
                    'title' => 'Buổi làm việc với Bộ Khoa học và Công nghệ về đề án TT ĐMST Nông nghiệp Quốc gia',
                    'description' => 'Giám đốc, các Phó GĐ, Trưởng các đơn vị: KHCN, HTQT, TCKT, CSVC&ĐT, QLĐT, CTCT&CTSV, TCCB',
                    'start_time' => Carbon::create(2025, 7, 9, 8, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 9, 11, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Bộ Khoa học và Công nghệ'],
                    'preparers' => ['Ban KHCN'],
                ],
                [
                    'title' => 'Tổng nghiệm thu công trình xây dựng Toà nhà văn phòng làm việc',
                    'description' => 'Thành viên Hội đồng nghiệm thu, đại diện đơn vị quản lý dự án, đại diện nhà thầu thi công',
                    'start_time' => Carbon::create(2025, 7, 9, 9, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 9, 11, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Công trường thi công'],
                    'preparers' => ['Ban CSVC và ĐT'],
                ],
                [
                    'title' => 'Hội thảo chia sẻ kinh nghiệm giảng dạy và nghiên cứu khoa học',
                    'description' => 'Giảng viên các khoa, bộ môn trong Học viện',
                    'start_time' => Carbon::create(2025, 7, 9, 13, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 9, 17, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Hội trường Nguyệt Quế'],
                    'preparers' => ['Ban KHCN'],
                ],
                [
                    'title' => 'Gặp mặt và làm việc với đoàn chuyên gia Đại học Cần Thơ',
                    'description' => 'Ban Giám đốc, đại diện các đơn vị: HTQT, KHCN, QLĐT, Khoa NTTS, Khoa CN',
                    'start_time' => Carbon::create(2025, 7, 10, 9, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 10, 11, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng họp Giáng Hương'],
                    'preparers' => ['Ban HTQT'],
                ],
                [
                    'title' => 'Đoàn công tác đi thăm và làm việc với UBND xã Thạch Thất',
                    'description' => 'Ban Giám đốc, đại diện các đơn vị: Ban CTCT&CTSV, Ban KHCN',
                    'start_time' => Carbon::create(2025, 7, 10, 14, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 10, 17, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Trụ sở HĐND-UBND xã Thạch Thất'],
                    'preparers' => ['Ban CTCT&CTSV'],
                ],
                [
                    'title' => 'Hội nghị cán bộ chủ chốt lấy phiếu giới thiệu nhân sự bổ sung Phó Hiệu trưởng',
                    'description' => 'Đảng ủy, BGĐ HV, Trưởng các đơn vị, Bí thư Đảng bộ, chi bộ',
                    'start_time' => Carbon::create(2025, 7, 11, 8, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 11, 11, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Hội trường Trung Tâm'],
                    'preparers' => ['VPĐU'],
                ],
                [
                    'title' => 'Tiếp công dân định kỳ tháng 7/2025',
                    'description' => 'Ban tiếp công dân theo QĐ của Giám đốc Học viện',
                    'start_time' => Carbon::create(2025, 7, 11, 8, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 11, 17, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng Tiếp dân'],
                    'preparers' => ['Ban ĐBCL và Pháp chế'],
                ],
                [
                    'title' => 'Hội nghị tổng kết công tác Đoàn và phong trào thanh niên năm học 2024-2025',
                    'description' => 'Ban Thường vụ Đoàn Thanh niên, Liên Chi Đoàn các đơn vị',
                    'start_time' => Carbon::create(2025, 7, 11, 14, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 11, 17, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Hội trường Nguyệt Quế'],
                    'preparers' => ['Đoàn TN'],
                ],
                [
                    'title' => 'Họp Ban tổ chức Hội nghị Quốc tế về Công nghệ sinh học nông nghiệp',
                    'description' => 'Trưởng Ban, Phó Ban và các thành viên BTC theo QĐ',
                    'start_time' => Carbon::create(2025, 7, 12, 9, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 12, 11, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng họp Bằng Lăng'],
                    'preparers' => ['Ban KHCN'],
                ],
                [
                    'title' => 'Họp chuẩn bị Hội thảo khoa học Quốc gia về KHCN trong lĩnh vực nông nghiệp',
                    'description' => 'Trưởng các đơn vị: Ban KHCN, HTQT, QLĐT, VPĐU, Khoa CNSH, Khoa NTTS',
                    'start_time' => Carbon::create(2025, 7, 12, 14, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 12, 16, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng 405, tòa nhà Bùi Huy Đáp'],
                    'preparers' => ['Ban KHCN'],
                ],
                [
                    'title' => 'Diễn đàn tiếng Anh định kỳ tháng 7',
                    'description' => 'Cán bộ, giảng viên và sinh viên quan tâm',
                    'start_time' => Carbon::create(2025, 7, 13, 9, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 13, 11, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Khoa Du lịch và Ngoại ngữ'],
                    'preparers' => ['Khoa DL&NN'],
                ],
                [
                    'title' => 'Họp triển khai kế hoạch tổ chức kỳ thi tuyển sinh sau đại học đợt 2 năm 2025',
                    'description' => 'Phó Giám đốc phụ trách đào tạo SĐH, Đại diện: Ban QLĐT, TCKT, CSVC, TT CNTT, Các khoa đào tạo SĐH',
                    'start_time' => Carbon::create(2025, 7, 13, 14, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 13, 16, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng họp Anh Đào'],
                    'preparers' => ['Ban QLĐT'],
                ],
                [
                    'title' => 'Tiếp đoàn SFSI về dự án Cooperative Innovation Ecosystem Development',
                    'description' => 'Thảo luận kế hoạch triển khai dự án',
                    'start_time' => Carbon::create(2025, 7, 14, 14, 0, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'end_time' => Carbon::create(2025, 7, 14, 16, 30, 0, 'Asia/Ho_Chi_Minh')->utc(),
                    'host_id' => $hostUsers->random()->id,
                    'participants' => $this->getRandomParticipants($departments, $users),
                    'status' => 'approved',
                    'creator_id' => $creatorUsers->random()->id,
                    'locations' => ['Phòng họp Hoa Sứ'],
                    'preparers' => ['Ban HTQT'],
                ],
            ];

            // Tạo thêm các sự kiện cho 2 tuần tới (từ 18/07/2025 đến 01/08/2025)
            $eventTitles = [
                'Họp Ban chỉ đạo dự án %s',
                'Thảo luận kế hoạch %s',
                'Hội thảo về %s',
                'Làm việc với đối tác về %s',
                'Phổ biến quy định mới về %s',
                'Đánh giá tiến độ %s',
                'Gặp gỡ doanh nghiệp về %s',
                'Triển khai dự án %s',
                'Tổng kết hoạt động %s',
                'Buổi chia sẻ kinh nghiệm về %s',
                'Đào tạo kỹ năng %s',
                'Seminar chuyên đề %s',
                'Tiếp đoàn công tác về %s',
                'Nghiệm thu dự án %s',
                'Hướng dẫn sử dụng %s'
            ];

            $topics = [
                'hợp tác quốc tế',
                'chuyển đổi số',
                'đổi mới sáng tạo',
                'chăn nuôi bền vững',
                'nông nghiệp công nghệ cao',
                'bảo vệ môi trường',
                'an toàn thực phẩm',
                'thực hành nông nghiệp tốt',
                'khởi nghiệp sinh viên',
                'đảm bảo chất lượng đào tạo',
                'ứng dụng CNTT trong giảng dạy',
                'tăng cường kỹ năng mềm',
                'hợp tác doanh nghiệp',
                'phát triển nguồn nhân lực',
                'quản trị đại học',
                'phát triển cơ sở vật chất',
                'tuyển sinh năm học mới',
                'nghiên cứu khoa học sinh viên',
                'đánh giá kết quả học tập',
                'phát triển chương trình đào tạo'
            ];

            $timeSlots = [
                ['start' => 8, 'end' => 10],
                ['start' => 9, 'end' => 12],
                ['start' => 14, 'end' => 16],
                ['start' => 14, 'end' => 17],
                ['start' => 15, 'end' => 18],
                ['start' => 8, 'end' => 11],
                ['start' => 13, 'end' => 16],
                ['start' => 10, 'end' => 12],
                ['start' => 16, 'end' => 18]
            ];

            // Get a set of users that can be hosts
            $hostUsers = User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'manager', 'leader']);
            })->get();

            // Fallback if there are no users with these roles
            if ($hostUsers->isEmpty()) {
                $hostUsers = User::take(10)->get();
            }

            // Generate 60 events over the next 2 weeks
            $startDate = Carbon::create(2025, 7, 18, 0, 0, 0, 'Asia/Ho_Chi_Minh');
            $endDate = Carbon::create(2025, 8, 1, 0, 0, 0, 'Asia/Ho_Chi_Minh');

            // Danh sách các ngày trong khoảng (loại trừ Chủ Nhật)
            $availableDays = [];
            $currentDate = clone $startDate;

            while ($currentDate <= $endDate) {
                // Nếu không phải Chủ Nhật (0 = Chủ Nhật, 1-6 = Thứ 2 - Thứ 7)
                if ($currentDate->dayOfWeek !== 0) {
                    $availableDays[] = clone $currentDate;
                }
                $currentDate->addDay();
            }

            // Thêm các sự kiện mới
            $newEvents = [];

            // Đảm bảo mỗi ngày có ít nhất 2 sự kiện (trừ Chủ Nhật)
            $eventsPerDay = [];
            foreach ($availableDays as $index => $availableDay) {
                $eventsPerDay[$availableDay->format('Y-m-d')] = 0;
            }

            // First, redefine available days to cover July 15 to August 1
            $availableDays = [];
            $startDate = Carbon::create(2025, 7, 15, 0, 0, 0, 'Asia/Ho_Chi_Minh');
            $endDate = Carbon::create(2025, 8, 1, 0, 0, 0, 'Asia/Ho_Chi_Minh');

            $currentDate = clone $startDate;
            while ($currentDate <= $endDate) {
                // Only include weekdays (exclude Sundays)
                if ($currentDate->dayOfWeek !== 0) {
                    $availableDays[] = clone $currentDate;
                }
                $currentDate->addDay();
            }

            // Initialize events per day counter
            $eventsPerDay = [];
            foreach ($availableDays as $availableDay) {
                $eventsPerDay[$availableDay->format('Y-m-d')] = 0;
            }

            // For each available day, add 2-5 random events
            foreach ($availableDays as $availableDay) {
                $dayKey = $availableDay->format('Y-m-d');
                // Randomly decide how many events for this day (2-5)
                $eventsForToday = rand(2, 5);

                for ($j = 0; $j < $eventsForToday; $j++) {
                    $eventDay = clone $availableDay;

                    // Choose a random time slot
                    $timeSlot = $timeSlots[array_rand($timeSlots)];

                    // Create start and end times
                    $startTime = clone $eventDay;
                    $startTime->setHour($timeSlot['start']);
                    $startTime->setMinute(rand(0, 3) * 15); // 0, 15, 30, 45

                    $endTime = clone $startTime;
                    $endTime->setHour($timeSlot['end']);
                    $endTime->setMinute(rand(0, 3) * 15); // 0, 15, 30, 45

                    // Ensure maximum duration is 4 hours
                    $hoursDiff = $endTime->diffInHours($startTime);
                    if ($hoursDiff > 4) {
                        $endTime = clone $startTime;
                        $endTime->addHours(4);
                    }

                    // Create event title
                    $title = sprintf(
                        $eventTitles[array_rand($eventTitles)],
                        $topics[array_rand($topics)]
                    );

                    // Select random locations and preparing departments
                    $locationKeys = array_keys($locations);
                    $randomLocationKeys = array_rand($locationKeys, rand(1, 2));
                    if (!is_array($randomLocationKeys)) $randomLocationKeys = [$randomLocationKeys];
                    $eventLocations = [];
                    foreach ($randomLocationKeys as $key) {
                        $eventLocations[] = $locationKeys[$key];
                    }

                    $departmentKeys = array_keys($departments);
                    $randomDepartmentKeys = array_rand($departmentKeys, rand(1, 2));
                    if (!is_array($randomDepartmentKeys)) $randomDepartmentKeys = [$randomDepartmentKeys];
                    $eventPreparers = [];
                    foreach ($randomDepartmentKeys as $key) {
                        $eventPreparers[] = $departmentKeys[$key];
                    }

                    // Create short description
                    $descriptions = [
                        'Yêu cầu chuẩn bị báo cáo và tài liệu liên quan.',
                        'Thành viên tham dự đúng giờ và mang theo tài liệu.',
                        'Buổi làm việc nhằm đạt được thỏa thuận về các vấn đề liên quan.',
                        'Đề nghị các đơn vị chuẩn bị ý kiến đóng góp.',
                        'Thành phần tham dự: Ban Giám đốc và Trưởng các đơn vị liên quan.',
                        'Chuẩn bị máy chiếu và tài liệu phục vụ cuộc họp.',
                        'Ghi chép và lập biên bản cuộc họp.',
                        'Chuẩn bị báo cáo tổng hợp và đề xuất giải pháp.',
                        'Đề nghị xác nhận tham dự trước ngày diễn ra sự kiện.',
                        'Chuẩn bị các nội dung thảo luận và giải pháp đề xuất.',
                        'Mời các chuyên gia tham gia đóng góp ý kiến.',
                        'Hội thảo có sự tham gia của các đối tác trong và ngoài nước.',
                        'Buổi làm việc trực tiếp kết hợp trực tuyến qua MS Teams.'
                    ];

                    $newEvent = [
                        'title' => $title,
                        'description' => $descriptions[array_rand($descriptions)],
                        'start_time' => $startTime->utc(),
                        'end_time' => $endTime->utc(),
                        'host_id' => $hostUsers->random()->id,
                        'status' => 'approved',
                        'creator_id' => $creatorUsers->random()->id,
                        'locations' => $eventLocations,
                        'preparers' => $eventPreparers,
                        'participants' => $this->getRandomParticipants($departments, $users),
                    ];

                    $newEvents[] = $newEvent;
                    $eventsPerDay[$dayKey]++;
                }
            }

            // Add remaining events randomly to reach approximately 60 events total
            $totalEventsNeeded = 60;
            $currentEventCount = count($newEvents);

            // Calculate how many more events we need
            $remainingEvents = max(0, $totalEventsNeeded - $currentEventCount);

            for ($i = 0; $i < $remainingEvents; $i++) {
                // Chọn ngẫu nhiên một ngày từ danh sách các ngày
                $randomDayIndex = array_rand($availableDays);
                $eventDay = clone $availableDays[$randomDayIndex];

                // Chọn ngẫu nhiên time slot
                $timeSlot = $timeSlots[array_rand($timeSlots)];

                // Create and add a new event similar to above
                // (this code was incomplete in original)
            }

            // Kết hợp các sự kiện mới với các sự kiện đã có
            $events = array_merge($events, $newEvents);

            // Create new array with processed events
            $processedEvents = [];

            // Process each event to ensure all required fields are present
            foreach ($events as $event) {
                // Create a new event array with required fields
                $newEvent = $event;

                // Ensure locations exists
                if (!isset($newEvent['locations'])) {
                    $newEvent['locations'] = ['Phòng họp Bằng Lăng']; // Default location
                }

                // Ensure preparers exists
                if (!isset($newEvent['preparers'])) {
                    $newEvent['preparers'] = ['Ban KHCN']; // Default preparer
                }

                // Set random creator
                $newEvent['creator_id'] = $creatorUsers->random()->id;

                // Set random participants
                $newEvent['participants'] = $this->getRandomParticipants($departments, $users);

                $processedEvents[] = $newEvent;
            }

            // Replace original events with processed ones
            $events = $processedEvents;

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
        } catch (\Exception $e) {
            // Log the error or handle it
            echo "Error in EventsTableSeeder: " . $e->getMessage() . "\n";
            throw $e; // Re-throw to stop execution
        }
    }
}
