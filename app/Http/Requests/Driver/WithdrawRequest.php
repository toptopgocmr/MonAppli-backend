<?php

namespace App\Http\Requests\Driver;

use Illuminate\Foundation\Http\FormRequest;

class WithdrawRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'       => 'required|numeric|min:500',
            'method'       => 'required|in:mtn,orange,airtel,moov',
            'phone_number' => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min' => 'Le montant minimum de retrait est 500.',
        ];
    }
}
