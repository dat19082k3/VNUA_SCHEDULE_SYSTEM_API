<?php

namespace App\Http\Requests\Department;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('departments')->ignore($this->route('id'))
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Tên phòng ban là bắt buộc.',
            'name.string' => 'Tên phòng ban phải là chuỗi ký tự.',
            'name.max' => 'Tên phòng ban không được vượt quá 100 ký tự.',
            'name.unique' => 'Tên phòng ban đã tồn tại trong hệ thống.',
            'description.string' => 'Mô tả phải là chuỗi ký tự.',
            'description.max' => 'Mô tả không được vượt quá 1000 ký tự.',
        ];
    }
}
