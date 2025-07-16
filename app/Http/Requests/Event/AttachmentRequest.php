<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator;

class AttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'files' => 'required|array|min:1',
            'files.*' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,csv,txt,zip|max:10240',
            'uids' => 'required|array',
            'uids.*' => 'required|string',
            'event_ids' => 'nullable|array',
            'event_ids.*' => ['nullable', Rule::exists('events', 'id')],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            $files = $this->file('files', []);
            $fileCount = count($files);

            // Kiểm tra kích thước mảng uids
            if (count($this->input('uids', [])) !== $fileCount) {
                $validator->errors()->add('uids', 'Số lượng UID phải khớp với số lượng tệp.');
            }

            // Kiểm tra kích thước mảng event_ids nếu có
            if ($this->has('event_ids') && count($this->input('event_ids', [])) !== $fileCount) {
                $validator->errors()->add('event_ids', 'Số lượng event_id phải khớp với số lượng tệp.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Vui lòng chọn ít nhất một tệp để tải lên.',
            'files.*.mimes' => 'Tệp :attribute phải có định dạng pdf, doc, docx, xls, xlsx, ppt, pptx, jpg, jpeg, png, gif, csv, txt hoặc zip.',
            'files.*.max' => 'Tệp :attribute không được vượt quá 10MB.',
            'uids.required' => 'Danh sách UID là bắt buộc.',
            'uids.*.required' => 'Mỗi tệp phải có UID.',
            'event_ids.*.exists' => 'Event ID không hợp lệ.',
        ];
    }
}
