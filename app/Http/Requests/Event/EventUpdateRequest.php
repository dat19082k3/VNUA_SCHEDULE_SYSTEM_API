<?php

namespace App\Http\Requests\Event;
use Illuminate\Foundation\Http\FormRequest;

class EventUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return false;
    }

    public function rules(): array
    {
        return [
            'description'    => ['sometimes', 'nullable', 'string'],
            'location'       => ['sometimes', 'nullable', 'string', 'max:255'],
            'start_time'     => ['sometimes', 'date'],
            'end_time'       => ['sometimes', 'date', 'after_or_equal:start_time'],
            'host_id'        => ['sometimes', 'integer', 'exists:users,id'],
            'participants'   => ['sometimes', 'nullable', 'string', 'max:255'],
            'reminder_type'  => ['sometimes', 'in:none,calendar'],
            'reminder_time'  => ['nullable', 'date', 'required_if:reminder_type,calendar'],
            'attachments'    => ['sometimes', 'array'],
            'attachments.*.id'       => ['sometimes', 'exists:attachments,id'],
            'attachments.*.file_name'=> ['required_with:attachments', 'string'],
            'attachments.*.file_url' => ['required_with:attachments', 'url'],
            'attachments.*.file_type'=> ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after_or_equal' => 'End time must be equal or after start time.',
            'reminder_time.required_if' => 'Reminder time is required when reminder type is calendar.',
            'attachments.array' => 'Attachments must be an array of files.',
            'attachments.*.file_url.url' => 'Each attachment URL must be a valid URL.',
        ];
    }
}
