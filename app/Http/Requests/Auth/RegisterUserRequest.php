<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'phone'      => 'required|string|unique:users,phone',
            'email'      => 'nullable|email|unique:users,email',
            'country'    => 'required|string|max:100',
            'city'       => 'required|string|max:100',
            'password'   => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique' => 'Ce numéro est déjà utilisé.',
            'email.unique' => 'Cet email est déjà utilisé.',
        ];
    }
}
