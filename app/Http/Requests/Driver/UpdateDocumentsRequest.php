<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'id_card_front'          => 'nullable|image|max:5120',
            'id_card_back'           => 'nullable|image|max:5120',
            'license_front'          => 'nullable|image|max:5120',
            'license_back'           => 'nullable|image|max:5120',
            'vehicle_registration'   => 'nullable|image|max:5120',
            'insurance'              => 'nullable|image|max:5120',
            'id_card_issue_date'     => 'nullable|date',
            'id_card_expiry_date'    => 'nullable|date|after:id_card_issue_date',
            'id_card_issue_city'     => 'nullable|string|max:100',
            'id_card_issue_country'  => 'nullable|string|max:100',
            'license_issue_date'     => 'nullable|date',
            'license_expiry_date'    => 'nullable|date|after:license_issue_date',
            'license_issue_city'     => 'nullable|string|max:100',
            'license_issue_country'  => 'nullable|string|max:100',
        ];
    }
}
