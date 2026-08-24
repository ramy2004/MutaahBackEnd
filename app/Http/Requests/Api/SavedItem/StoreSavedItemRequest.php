<?php

namespace App\Http\Requests\Api\SavedItem;

use Illuminate\Foundation\Http\FormRequest;

class StoreSavedItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'string', 'exists:Products,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.required' => 'رقم المنتج مطلوب',
            'product_id.exists'   => 'المنتج المحدد غير موجود',
        ];
    }
}
