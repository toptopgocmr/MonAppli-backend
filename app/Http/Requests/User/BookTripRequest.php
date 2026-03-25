<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class BookTripRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'pickup_address'  => 'required|string|max:255',
            'pickup_lat'      => 'required|numeric|between:-90,90',
            'pickup_lng'      => 'required|numeric|between:-180,180',
            'dropoff_address' => 'required|string|max:255',
            'dropoff_lat'     => 'required|numeric|between:-90,90',
            'dropoff_lng'     => 'required|numeric|between:-180,180',
            'vehicle_type'    => 'required|in:Standard,Confort,Van,PMR',
            'amount'          => 'required|numeric|min:100',
            'method'          => 'required|in:mtn,orange,airtel,moov,visa,mastercard',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.min'   => 'Le montant minimum est 100.',
            'method.in'    => 'Méthode de paiement non supportée.',
        ];
    }
}
