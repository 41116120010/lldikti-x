<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'identity' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Custom attribute names for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'identity' => 'NIP, Username, atau Email',
        ];
    }

    /**
     * Custom validation error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'identity.required' => 'Mohon masukkan NIP (18 Digit), Username, atau Email kedinasan Anda.',
            'identity.string' => 'Format identitas tidak valid.',
            'identity.max' => 'Identitas tidak boleh melebihi 255 karakter.',
        ];
    }
}
