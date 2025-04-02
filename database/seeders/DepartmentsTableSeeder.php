<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DepartmentsTableSeeder extends Seeder
{
    public function run()
    {
        $currentTime = Carbon::now(); // Lấy thời gian hiện tại

        $departments = [
            ['id' => 44, 'name' => 'Ban Giám Đốc', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 257, 'name' => 'Hội đồng Học viện Nông nghiệp Việt Nam', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 53, 'name' => 'Văn phòng Đảng ủy', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 39, 'name' => 'Công đoàn Học viện', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 252, 'name' => 'Đoàn thanh niên', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 262, 'name' => 'Hội sinh viên', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 238, 'name' => 'Văn phòng Học viện', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 42, 'name' => 'Ban Tổ chức cán bộ', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 123, 'name' => 'Ban Quản lý đào tạo', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 131, 'name' => 'Ban Thanh tra', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 125, 'name' => 'Ban Tài chính Kế toán', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 126, 'name' => 'Ban Hợp tác quốc tế', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 127, 'name' => 'Ban CTCT&CTSV', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 128, 'name' => 'Ban Quản lý CSVC', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 129, 'name' => 'Ban Khoa học công nghệ', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 263, 'name' => 'Ban Quản lý Đầu tư', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 197, 'name' => 'Trạm Y tế', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 130, 'name' => 'TT Thông tin thư viện Lương Định Của', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 124, 'name' => 'Trung tâm đảm bảo chất lượng', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 35, 'name' => 'Khoa Chăn nuôi', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 40, 'name' => 'Khoa Thủy sản', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 88, 'name' => 'Khoa Công nghệ sinh học', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 73, 'name' => 'KHOA MÔI TRƯỜNG', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 38, 'name' => 'Khoa CNTT', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 75, 'name' => 'Khoa Cơ điện', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 76, 'name' => 'Khoa Công nghệ thực phẩm', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 77, 'name' => 'Khoa Kế toán và QTKD', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 79, 'name' => 'Khoa Kinh tế và PTNT', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 254, 'name' => 'Khoa Giáo dục quốc phòng', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 85, 'name' => 'Khoa Khoa học xã hội', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 84, 'name' => 'Khoa Nông học', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 72, 'name' => 'Du lịch và ngoại ngữ', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 172, 'name' => 'Khoa Thú Y', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 192, 'name' => 'Khoa Tài nguyên môi trường', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 50, 'name' => 'TT Giáo dục thể chất và Thể thao', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 195, 'name' => 'Viện Sinh học Nông nghiệp', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 196, 'name' => 'Viện Nghiên cứu và phát triển cây trồng', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 199, 'name' => 'Viện phát triển công nghệ cơ diện', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 198, 'name' => 'Viện đào tạo và phát triển quốc tế', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 191, 'name' => 'Viện kinh tế và phát triển', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 189, 'name' => 'Trung tâm sinh thái nông nghiệp', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 188, 'name' => 'Trung tâm tư vấn KHCN và TNMT', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 36, 'name' => 'Trung tâm nghiên cứu liên ngành và PTNT', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 256, 'name' => 'Trung tâm Tư vấn việc làm và Hỗ trợ sinh viên', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 193, 'name' => 'Trung tâm dạy nghề và đào tạo lái xe', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 259, 'name' => 'Trung tâm Đào tạo kỹ năng mềm', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 244, 'name' => 'Công ty Đầu tư PT và Dịch vụ HVNNVN', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 243, 'name' => 'Trung tâm thực nghiệm và đào tạo nghề', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 245, 'name' => 'Trung tâm ngoại ngữ', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 246, 'name' => 'Trung tâm bệnh cây nhiệt đới', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 247, 'name' => 'Trung tâm tài nguyên đất và môi trường', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 264, 'name' => 'Nhà xuất bản HVNN', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 249, 'name' => 'TT nghiên cứu sinh thái nông nghiệp á nhiệt đới', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 251, 'name' => 'Trung tâm bảo tồn và phát triển nguồn gen cây trồng', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 258, 'name' => 'Trung tâm Tin học HVNNVN', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 265, 'name' => 'Trung tâm Đổi mới sáng tạo', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 260, 'name' => 'Trung tâm cung ứng nguồn nhân lực', 'created_at' => $currentTime, 'updated_at' => $currentTime],
            ['id' => 261, 'name' => 'TT Quan hệ công chúng và HTSV', 'created_at' => $currentTime, 'updated_at' => $currentTime],
        ];

        DB::table('departments')->insert($departments);
    }
}
