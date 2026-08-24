<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'            => ['sometimes', 'string', 'max:255'],
            'category'         => ['sometimes', Rule::in([
                'cameras',
                'clothes',
                'electronics',
                'items',
                'camping',
                'medical items',
                'instruments',
                'books',
                'house items'
            ])],
            'description'      => ['nullable', 'string'],
            'price_per_hour'   => ['sometimes', 'numeric', 'min:0'],
            'deposit_amount'   => ['sometimes', 'numeric', 'min:0'],
            'status'           => ['sometimes', Rule::in(['pending', 'active', 'frozen', 'deleted'])],
            'images'           => ['nullable', 'array', 'min:1', 'max:4'],
            'images.*'         => ['image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'available_dates'  => ['nullable', 'array', 'min:1'],
            'available_dates.*' => ['date_format:Y-m-d'],
            'start_time'       => ['nullable', 'date_format:H:i'],
            'end_time'         => ['nullable', 'date_format:H:i'],
            'is_all_day'       => ['nullable', 'boolean'],
        ];
    }
}
