<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateAdminUserRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name'  => 'required|string|max:100',
            'email'      => 'required|email|unique:admin_users,email',
            'phone'      => 'nullable|string|max:20',
            'role_id'    => 'required|exists:roles,id',
            'password'   => 'required|string|min:6',
        ];
    }
}
