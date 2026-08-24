<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'full_name'   => ['required', 'string', 'max:255'],
            'username'    => ['required', 'string', 'max:255', Rule::unique('Users', 'username')->ignore($userId)],
            'email'       => ['required', 'email', 'max:255', Rule::unique('Users', 'email')->ignore($userId)],
            'phone'       => ['required', 'string', 'max:20', Rule::unique('Users', 'phone')->ignore($userId)],
            'governorate' => ['required', Rule::in(['north', 'gaza', 'middle', 'khanyonis', 'rafah'])],
            'district'    => ['required', 'string', 'max:255'],
            'password'    => ['nullable', 'string', 'min:8'],
            'avatar'      => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
        ];
    }
}
