<?php

namespace App\Dtos;

use Carbon\Carbon;
use App\Models\User;
use App\Interfaces\DtoInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Http\FormRequest;

class UserDto implements DtoInterface{

    private ?int $id;
    private ?string $avatar;
    private string $email;
    private ?string $first_name;
    private ?string $last_name;
    private ?string $phone;
    private string $password;
    private ?string $status;
    private ?int $department_id;
    private ?Carbon $created_at;
    private ?Carbon $updated_at;

    public function getId() {
        return $this->id;
    }
    public function getAvatar() {
        return $this->avatar;
    }
    public function getEmail() {
        return $this->email;
    }
    public function getFirstName() {
        return $this->first_name;
    }
    public function getLastName() {
        return $this->last_name;
    }
    public function getPhone() {
        return $this->phone;
    }
    public function getPassword() {
        return $this->password;
    }
    public function getStatus() {
        return $this->status;
    }
    public function getDepartmentId() {
        return $this->department_id;
    }
    public function getCreatedAt() {
        return $this->created_at;
    }
    public function getUpdatedAt() {
        return $this->updated_at;
    }

    public function setId($value) {
        $this->id = $value;
    }
    public function setAvatar($value) {
        $this->avatar = $value;
    }
    public function setEmail($value) {
        $this->email = $value;
    }
    public function setFirstName($value) {
        $this->first_name = $value;
    }
    public function setLastName($value) {
        $this->last_name = $value;
    }
    public function setPhone($value) {
        $this->phone = $value;
    }
    public function setPassword($value) {
        $this->password = $value;
    }
    public function setStatus($value) {
        $this->status = $value;
    }
    public function setDepartmentId($value) {
        $this->department_id = $value;
    }
    public function setCreatedAt($value) {
        $this->created_at = $value;
    }
    public function setUpdatedAt($value) {
        $this->updated_at = $value;
    }

    public static function fromApiFormRequest(FormRequest $request): DtoInterface
    {
        $userDto = new self();
        $userDto->setAvatar($request->input("avatar"));
        $userDto->setEmail($request->input("email"));
        $userDto->setFirstName($request->input("first_name"));
        $userDto->setLastName($request->input("last_name"));
        $userDto->setPhone($request->input("phone"));
        $userDto->setPassword($request->input("password"));
        $userDto->setStatus($request->input("status", 1));
        $userDto->setDepartmentId($request->input("department_id"));

        return $userDto;
    }

    public static function formModel(Model|User $model):DtoInterface{
        $userDto = new UserDto();
        $userDto->setId($model->id);
        $userDto->setAvatar($model->avatar);
        $userDto->setEmail($model->email);
        $userDto->setFirstName($model->firstName);
        $userDto->setLastName($model->lastName);
        $userDto->setPhone($model->phone);
        $userDto->setPassword($model->password);
        $userDto->setStatus($model->status);
        $userDto->setDepartmentId($model->department_id);
        $userDto->setCreatedAt($model->created_at);
        $userDto->setUpdatedAt($model->updated_at);

        return $userDto;
    }

    public static function toArray(Model|User $model): array
    {
        return [
            'id' => $model->id,
            'avatar'=> $model->avatar,
            'email'=> $model->email,
            'first_name'=> $model->firstName,
            'last_name'=> $model->lastName,
            'phone'=> $model->phone,
            'status'=> $model->status,
            'department_id'=> $model->department_id,
            'created_at'=> $model->created_at,
            'updated_at'=> $model->updated_at,
        ];
    }
}
