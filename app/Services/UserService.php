<?php

namespace App\Services;

use App\Models\User;
use App\Dtos\UserDto;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService {
    public function createUser(UserDto $user):User {
        return DB::transaction(function () use ($user) {
            $user = User::create([
                'avatar' => $user->getAvatar() ?? null,
                'email' => $user->getEmail(),
                'phone' => $user->getPhone() ?? null,
                'password' => Hash::make($user->getPassword()),
                'status' =>  1,
                'protected' => 0,
                'department_id' => $user->getDepartmentId() ?? null,
            ]);
            $user->sendEmailVerificationNotification();
            return $user;
        });
    }

    public function updateUser(UserDto $userDto): User {
        return DB::transaction(function () use ($userDto) {
            // Tìm user cần update theo ID từ UserDto (giả sử UserDto có phương thức getId())
            $user = User::findOrFail($userDto->getId());
    
            // Tạo mảng dữ liệu cần cập nhật
            $data = [
                // Nếu không truyền avatar mới, giữ nguyên avatar cũ
                'avatar' => $userDto->getAvatar() ?? $user->avatar,
                'first_name' => $userDto->getFirstName() ?? $user->first_name,
                'last_name' => $userDto->getLastName() ?? $user->last_name,
                'phone' => $userDto->getPhone() ?? $user->phone,
                // Nếu có password mới thì update (với Hash::make), nếu không giữ nguyên password cũ
                'password' => $userDto->getPassword() ? Hash::make($userDto->getPassword()) : $user->password,
                // Cập nhật department_id nếu có, giữ nguyên nếu không truyền
                'department_id' => $userDto->getDepartmentId() ?? $user->department_id,
                // Bạn có thể cập nhật thêm các trường khác nếu cần (ví dụ: status, protected) theo yêu cầu
            ];
    
            // Thực hiện update dữ liệu
            $user->update($data);
    
            // Nếu muốn đảm bảo dữ liệu mới nhất sau update, trả về user đã fresh lại
            return $user->fresh();
        });
    }
}
