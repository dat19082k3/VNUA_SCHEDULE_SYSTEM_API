<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Định nghĩa rules để validate request.
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string'],
            'email' => ['nullable', 'email'],
        ];
    }

    /**
     * Tùy chỉnh thông báo lỗi.
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Vui lòng nhập mã đăng nhập.',
            'email.email' => 'Email không hợp lệ.',
        ];
    }

    /**
     * Chuẩn hóa dữ liệu đầu vào: flatten 'input' thành trực tiếp.
     */
    protected function prepareForValidation()
    {
        if ($this->has('input')) {
            $this->merge($this->input('input'));
        }
    }
}
