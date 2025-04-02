<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ProfileRequest extends FormRequest
{
    /**
     * Xác định xem người dùng có quyền gửi request này không.
     */
    public function authorize(): bool
    {
        return true; // Có thể điều chỉnh logic phân quyền nếu cần.
    }

    /**
     * Quy tắc xác thực dữ liệu đầu vào.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => ['nullable', 'url', 'max:255'], // Đảm bảo đường dẫn ảnh hợp lệ
            'first_name' => ['nullable', 'string', 'max:255'], // Tên không quá 255 ký tự
            'last_name' => ['nullable', 'string', 'max:255'], // Họ không quá 255 ký tự
            'phone' => ['nullable', 'digits:10'], // Số điện thoại phải có đúng 10 chữ số
            'department_id' => ['nullable', 'exists:departments,id'], // Phòng ban phải hợp lệ
        ];
    }

    /**
     * Tùy chỉnh thông điệp lỗi hiển thị.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.url' => 'Ảnh đại diện phải là một đường dẫn URL hợp lệ.',
            'phone.digits' => 'Số điện thoại phải chứa đúng 10 chữ số.',
            'department_id.exists' => 'Phòng ban không tồn tại trong hệ thống.',
        ];
    }

    /**
     * Tiền xử lý dữ liệu trước khi xác thực.
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'phone' => $this->phone ? trim($this->phone) : null,
            'avatar' => $this->avatar ? trim($this->avatar) : null,
        ]);
    }
}
