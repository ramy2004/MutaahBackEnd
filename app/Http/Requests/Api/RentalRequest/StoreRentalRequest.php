<?php

namespace App\Http\Requests\Api\RentalRequest;

use Illuminate\Foundation\Http\FormRequest;

class StoreRentalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'exists:Products,id'],
            'start_time' => ['required', 'date', 'after_or_equal:now'],
            'end_time' => ['required', 'date', 'after:start_time'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'رقم المنتج مطلوب',
            'product_id.exists' => 'المنتج المحدد غير موجود',
            'start_time.required' => 'تاريخ البداية مطلوب',
            'start_time.date' => 'صيغة تاريخ البداية غير صحيحة',
            'start_time.after_or_equal' => 'تاريخ البداية لا يمكن أن يكون في الماضي',
            'end_time.required' => 'تاريخ النهاية مطلوب',
            'end_time.date' => 'صيغة تاريخ النهاية غير صحيحة',
            'end_time.after' => 'تاريخ النهاية يجب أن يكون بعد تاريخ البداية',
        ];
    }
}
