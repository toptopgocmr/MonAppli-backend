<?php

namespace App\Services;

use App\Models\Trip;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\WalletTransaction;

class TripService
{
    /**
     * Commission par défaut (20%)
     */
    const COMMISSION_RATE = 0.20;

    /**
     * Créer une course avec booking et paiement
     */
    public function createTrip(array $data, int $userId): Trip
    {
        $commission = $data['amount'] * self::COMMISSION_RATE;
        $driverNet  = $data['amount'] - $commission;

        $trip = Trip::create([
            'user_id'         => $userId,
            'driver_id'       => $data['driver_id'] ?? null,
            'pickup_address'  => $data['pickup_address'],
            'pickup_lat'      => $data['pickup_lat'],
            'pickup_lng'      => $data['pickup_lng'],
            'dropoff_address' => $data['dropoff_address'],
            'dropoff_lat'     => $data['dropoff_lat'],
            'dropoff_lng'     => $data['dropoff_lng'],
            'vehicle_type'    => $data['vehicle_type'],
            'distance_km'     => $data['distance_km'] ?? null,
            'amount'          => $data['amount'],
            'commission'      => $commission,
            'driver_net'      => $driverNet,
            'status'          => 'pending',
        ]);

        Booking::create([
            'user_id'   => $userId,
            'trip_id'   => $trip->id,
            'status'    => 'confirmed',
            'booked_at' => now(),
        ]);

        Payment::create([
            'user_id'    => $userId,
            'trip_id'    => $trip->id,
            'driver_id'  => $data['driver_id'] ?? 0,
            'amount'     => $data['amount'],
            'commission' => $commission,
            'driver_net' => $driverNet,
            'method'     => $data['method'],
            'status'     => 'pending',
            'country'    => $data['country'] ?? null,
            'city'       => $data['city'] ?? null,
        ]);

        return $trip->load('payment');
    }

    /**
     * Terminer une course et créditer le wallet du chauffeur
     */
    public function completeTrip(Trip $trip): void
    {
        $trip->update(['status' => 'completed', 'completed_at' => now()]);

        // Mettre à jour le paiement
        $trip->payment?->update(['status' => 'success', 'paid_at' => now()]);

        // Créditer le wallet
        $wallet = Wallet::where('driver_id', $trip->driver_id)->first();
        if ($wallet && $trip->driver_net > 0) {
            $before = $wallet->balance;
            $wallet->increment('balance', $trip->driver_net);

            WalletTransaction::create([
                'wallet_id'      => $wallet->id,
                'type'           => 'credit',
                'amount'         => $trip->driver_net,
                'balance_before' => $before,
                'balance_after'  => $wallet->fresh()->balance,
                'description'    => "Course #{$trip->id} — net après commission",
                'reference'      => "trip_{$trip->id}",
            ]);
        }
    }

    /**
     * Calculer la distance en km entre deux points GPS (formule Haversine)
     */
    public function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * Calculer le prix estimé selon la distance et le type de véhicule
     */
    public function estimatePrice(float $distanceKm, string $vehicleType): float
    {
        $baseRates = [
            'Standard' => ['base' => 500, 'per_km' => 300],
            'Confort'  => ['base' => 800, 'per_km' => 450],
            'Van'      => ['base' => 1000, 'per_km' => 600],
            'PMR'      => ['base' => 1000, 'per_km' => 500],
        ];

        $rate = $baseRates[$vehicleType] ?? $baseRates['Standard'];

        return round($rate['base'] + ($distanceKm * $rate['per_km']));
    }
}
