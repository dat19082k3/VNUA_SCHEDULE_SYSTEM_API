<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    /**
     * Xác định xem người dùng có quyền gửi request này không.
     */
    public function authorize(): bool
    {
        return true; // Điều chỉnh logic phân quyền nếu cần.
    }

    /**
     * Quy tắc validation cho request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\Rule|array|string>
     */
    public function rules(): array
    {
        return [
            'avatar' => 'nullable|url|max:255', // Đường dẫn ảnh hợp lệ
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|digits:10', // Số điện thoại bắt buộc 10 số
            'password' => 'required|min:8|confirmed', // Password cần confirmed
            'status' => 'nullable|in:0,1', // Chỉ nhận 0 hoặc 1
            'protected' => 'nullable|boolean',
            'department_id' => 'nullable|exists:departments,id', // Phòng ban phải tồn tại
        ];
    }

    /**
     * Tùy chỉnh thông báo lỗi.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'avatar.url' => 'Ảnh đại diện phải là một đường dẫn hợp lệ.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không đúng định dạng.',
            'email.unique' => 'Email này đã được sử dụng.',
            'phone.digits' => 'Số điện thoại phải đúng 10 số.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 8 ký tự.',
            'password.confirmed' => 'Mật khẩu xác nhận không khớp.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'protected.boolean' => 'Giá trị protected phải là true hoặc false.',
            'department_id.exists' => 'Phòng ban không tồn tại.',
        ];
    }

    /**
     * Tiền xử lý dữ liệu đầu vào - Loại bỏ khoảng trắng thừa, chuyển email lowercase, boolean casting...
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'email' => $this->email ? strtolower(trim($this->email)) : null,
            'phone' => $this->phone ? trim($this->phone) : null,
            'avatar' => $this->avatar ? trim($this->avatar) : null,
            'protected' => filter_var($this->protected, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);
    }
}
