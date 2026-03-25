<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterDriverRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'birth_date'    => 'required|date|before:-18 years',
            'birth_place'   => 'required|string|max:150',
            'country_birth' => 'required|string|max:100',
            'phone'         => 'required|string|unique:drivers,phone',
            'password'      => 'required|string|min:6|confirmed',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.unique'       => 'Ce numéro de téléphone est déjà utilisé.',
            'birth_date.before'  => 'Le chauffeur doit avoir au moins 18 ans.',
            'password.confirmed' => 'Les mots de passe ne correspondent pas.',
        ];
    }
}
