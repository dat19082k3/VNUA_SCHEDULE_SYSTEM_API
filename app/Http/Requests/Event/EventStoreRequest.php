<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;

class EventStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => [
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value !== 0 && !DB::table('locations')->where('id', $value)->exists()) {
                        $fail("ID địa điểm {$value} không tồn tại trong hệ thống.");
                    }
                },
            ],
            'custom_locations' => ['nullable', 'string', 'max:255'],
            'custom_preparers' => ['nullable', 'string', 'max:255'],
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'host_id' => ['required', 'integer', 'exists:users,id'],
            'participants' => ['nullable', 'array'],
            'participants.*.type' => ['required_with:participants', 'string', 'in:user,department'],
            'participants.*.id' => ['required_with:participants', 'integer'],
            'preparer_ids' => ['required', 'array', 'min:1'],
            'preparer_ids.*' => [
                'integer',
                function ($attribute, $value, $fail) {
                    if ($value !== 0 && !DB::table('departments')->where('id', $value)->exists()) {
                        $fail("ID đơn vị chuẩn bị {$value} không tồn tại trong hệ thống.");
                    }
                },
            ],
            'reminder_type' => ['nullable', 'in:none,calendar'],
            'reminder_time' => ['nullable', 'date', 'required_if:reminder_type,calendar'],
            'attachments' => ['sometimes', 'array'],
            'attachments.*' => [
                'required_with:attachments',
                'integer',
                'exists:attachments,id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Tiêu đề sự kiện là bắt buộc.',
            'title.string' => 'Tiêu đề sự kiện phải là chuỗi ký tự.',
            'title.max' => 'Tiêu đề sự kiện không được vượt quá 255 ký tự.',
            'location_ids.array' => 'Danh sách địa điểm phải là một mảng các ID.',
            'location_ids.*.integer' => 'ID địa điểm phải là số nguyên.',
            'location_ids.*.exists' => 'Một hoặc nhiều ID địa điểm không tồn tại trong hệ thống.',
            'custom_locations.string' => 'Địa điểm tùy chỉnh phải là chuỗi ký tự.',
            'custom_locations.max' => 'Địa điểm tùy chỉnh không được vượt quá 255 ký tự.',
            'custom_preparers.string' => 'Đơn vị tùy chỉnh phải là chuỗi ký tự.',
            'custom_preparers.max' => 'Đơn vị tùy chỉnh không được vượt quá 255 ký tự.',
            'preparer_ids.required' => 'Danh sách đơn vị chuẩn bị là bắt buộc.',
            'preparer_ids.array' => 'Danh sách đơn vị chuẩn bị phải là một mảng các ID.',
            'preparer_ids.min' => 'Phải chọn ít nhất một đơn vị chuẩn bị.',
            'preparer_ids.*.integer' => 'ID đơn vị chuẩn bị phải là số nguyên.',
            'preparer_ids.*.exists' => 'Một hoặc nhiều ID đơn vị chuẩn bị không tồn tại trong hệ thống.',
            'end_time.after_or_equal' => 'Thời gian kết thúc phải bằng hoặc sau thời gian bắt đầu.',
            'reminder_type.in' => 'Loại nhắc nhở phải là "none" hoặc "calendar".',
            'reminder_time.required_if' => 'Thời gian nhắc nhở là bắt buộc khi loại nhắc nhở là "calendar".',
            'reminder_time.date' => 'Thời gian nhắc nhở phải là định dạng ngày hợp lệ.',
            'attachments.array' => 'Tệp đính kèm phải là một mảng các ID.',
            'attachments.*.required_with' => 'Mỗi tệp đính kèm phải có ID hợp lệ.',
            'attachments.*.integer' => 'ID tệp đính kèm phải là một số nguyên.',
            'attachments.*.exists' => 'ID tệp đính kèm không tồn tại trong hệ thống.',
            'participants.array' => 'Danh sách người tham gia phải là một mảng.',
            'participants.*.type.required_with' => 'Loại người tham gia là bắt buộc.',
            'participants.*.type.in' => 'Loại người tham gia phải là "user" hoặc "department".',
            'participants.*.id.required_with' => 'ID người tham gia là bắt buộc.',
            'participants.*.id.integer' => 'ID người tham gia phải là số nguyên.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('attachments') && is_string($this->attachments)) {
            $this->merge([
                'attachments' => json_decode($this->attachments, true) ?? [],
            ]);
        }
        if ($this->has('location_ids') && is_string($this->location_ids)) {
            $this->merge([
                'location_ids' => json_decode($this->location_ids, true) ?? [],
            ]);
        }
        if ($this->has('preparer_ids') && is_string($this->preparer_ids)) {
            $this->merge([
                'preparer_ids' => json_decode($this->preparer_ids, true) ?? [],
            ]);
        }
        if ($this->has('participants') && is_string($this->participants)) {
            $this->merge([
                'participants' => json_decode($this->participants, true) ?? [],
            ]);
        }
    }
}
