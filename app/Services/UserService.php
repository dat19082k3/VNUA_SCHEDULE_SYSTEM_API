<?php

namespace App\Services;

use App\Models\User;
use App\Dtos\UserDto;
use Illuminate\Auth\Events\Registered;
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
}
