<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'full_name'   => ['required', 'string', 'max:255'],
            'username'    => ['required', 'string', 'max:255', 'unique:Users,username'],
            'email'       => ['required', 'string', 'email', 'max:255', 'unique:Users,email'],
            'phone'       => ['required', 'string', 'unique:Users,phone'],
            'governorate' => ['required', Rule::in(['north', 'gaza', 'middle', 'khanyonis', 'rafah'])],
            'district'    => ['required', 'string', 'max:255'],
            'password'    => ['required', 'string', 'min:8', 'confirmed'],
            'terms'       => ['required', 'accepted'],
        ];
    }
}
