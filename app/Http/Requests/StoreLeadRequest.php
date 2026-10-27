<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        $phone = is_string($phone) ? preg_replace('/[\s().-]+/u', '', trim($phone)) : '';
        $phone = is_string($phone) ? $phone : '';

        if (str_starts_with($phone, '+84')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '0084')) {
            $phone = '0'.substr($phone, 4);
        } elseif (str_starts_with($phone, '84')) {
            $phone = '0'.substr($phone, 2);
        }

        $this->merge(['phone' => $phone]);
    }

    public function rules(): array
    {
        return [
            'request_type' => ['required', Rule::in(['quote', 'test_drive'])],
            'source' => ['nullable', Rule::in(['website', 'popup-zalo'])],
            'submission_token' => ['nullable', 'uuid'],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'regex:/^0(?:[35789][0-9]{8}|2[0-9]{8,9})$/'],
            'vehicle_id' => [
                'nullable',
                'integer',
                Rule::exists('vehicles', 'id')->where(fn ($query) => $query->where('is_published', true)),
            ],
            'variant_id' => ['nullable', 'integer', Rule::exists('variants', 'id')->where(fn ($query) => $query->where('is_published', true))],
            'vehicle_color_id' => ['nullable', 'integer', Rule::exists('vehicle_colors', 'id')->where(fn ($query) => $query->where('is_active', true))],
            'desired_date' => ['required_if:request_type,test_drive', 'nullable', 'date', 'after_or_equal:today'],
            'region' => ['required', Rule::in(array_keys(config('lead.regions')))],
            'customer_note' => ['nullable', 'string', 'max:2000'],
            'consent_given' => ['accepted'],
            'marketing_consent' => ['nullable', 'boolean'],
            'campaign_landing_page_id' => [
                'nullable',
                'integer',
                Rule::exists('campaign_landing_pages', 'id')->where(fn ($query) => $query->where('is_published', true)),
            ],
            'utm_source' => ['nullable', 'string', 'max:255'],
            'utm_medium' => ['nullable', 'string', 'max:255'],
            'utm_campaign' => ['nullable', 'string', 'max:255'],
            'utm_content' => ['nullable', 'string', 'max:255'],
            'utm_term' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'Vui lòng chọn nhu cầu nhận báo giá hoặc lái thử.',
            'request_type.in' => 'Loại yêu cầu không hợp lệ.',
            'submission_token.uuid' => 'Mã gửi biểu mẫu không hợp lệ. Vui lòng tải lại trang.',
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'full_name.min' => 'Họ tên cần có ít nhất 2 ký tự.',
            'full_name.max' => 'Họ tên không được dài quá 150 ký tự.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.regex' => 'Số điện thoại không hợp lệ. Hãy nhập số Việt Nam dạng 0xxxxxxxxx hoặc +84xxxxxxxxx.',
            'vehicle_id.exists' => 'Vui lòng chọn dòng xe đang được kinh doanh.',
            'variant_id.exists' => 'Phiên bản xe không hợp lệ.',
            'vehicle_color_id.exists' => 'Màu xe không hợp lệ.',
            'desired_date.required_if' => 'Vui lòng chọn ngày lái thử mong muốn.',
            'desired_date.date' => 'Ngày lái thử không hợp lệ.',
            'desired_date.after_or_equal' => 'Ngày lái thử không được ở quá khứ.',
            'region.required' => 'Vui lòng chọn khu vực đăng ký xe.',
            'region.in' => 'Khu vực đã chọn không hợp lệ.',
            'customer_note.max' => 'Ghi chú không được dài quá 2.000 ký tự.',
            'consent_given.accepted' => 'Vui lòng đồng ý với chính sách xử lý dữ liệu cá nhân.',
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->filled('website')) {
                $validator->errors()->add('form', 'Không thể tiếp nhận yêu cầu này.');
            }
        }];
    }
}
