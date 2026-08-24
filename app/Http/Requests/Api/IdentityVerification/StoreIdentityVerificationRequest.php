<?php

namespace App\Http\Requests\Api\IdentityVerification;

use Illuminate\Foundation\Http\FormRequest;

class StoreIdentityVerificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'id_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
            'selfie_image' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'],
        ];
    }
}
