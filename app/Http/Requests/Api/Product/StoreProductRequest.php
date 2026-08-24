<?php

namespace App\Http\Requests\Api\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Step 1: البيانات والصور
            'title'          => ['required', 'string', 'max:255'],
            'category'       => ['required', Rule::in([
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
            'description'    => ['required', 'string'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['required', 'numeric', 'min:0'],
            'images'         => ['required', 'array', 'min:1', 'max:4'],
            'images.*'       => ['image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],

            // Step 2: التواريخ والأوقات
            'available_dates'   => ['required', 'array', 'min:1'],
            'available_dates.*' => ['date_format:Y-m-d'],
            'start_time'        => ['nullable', 'date_format:H:i'],
            'end_time'          => ['nullable', 'date_format:H:i'],
            'is_all_day'        => ['nullable', 'boolean'],
        ];
    }
}
