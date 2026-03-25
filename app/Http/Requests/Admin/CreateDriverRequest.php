<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CreateDriverRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'birth_date'    => 'required|date',
            'birth_place'   => 'required|string|max:150',
            'country_birth' => 'required|string|max:100',
            'phone'         => 'required|string|unique:drivers,phone',
            'password'      => 'required|string|min:6',
            'vehicle_plate' => 'nullable|string|unique:drivers,vehicle_plate',
            'vehicle_brand' => 'nullable|string|max:100',
            'vehicle_model' => 'nullable|string|max:100',
            'vehicle_type'  => 'nullable|in:Standard,Confort,Van,PMR',
            'vehicle_color' => 'nullable|string|max:50',
            'vehicle_country'=> 'nullable|string|max:100',
            'vehicle_city'  => 'nullable|string|max:100',
        ];
    }
}
