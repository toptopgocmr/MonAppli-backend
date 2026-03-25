<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ride;
use App\Models\User;
use App\Models\Rating;
use App\Models\DriverProfile;
use App\Services\Payment\PaymentService;
use App\Events\RideCreated;
use App\Events\RideCancelled;
use App\Notifications\NewRideRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RideController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Estimate ride price
     */
    public function estimatePrice(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pickup_latitude' => 'required|numeric|between:-90,90',
            'pickup_longitude' => 'required|numeric|between:-180,180',
            'dropoff_latitude' => 'required|numeric|between:-90,90',
            'dropoff_longitude' => 'required|numeric|between:-180,180',
            'vehicle_type' => 'sometimes|in:standard,comfort,premium',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $distance = $this->calculateDistance(
            $request->pickup_latitude,
            $request->pickup_longitude,
            $request->dropoff_latitude,
            $request->dropoff_longitude
        );

        $vehicleType = $request->vehicle_type ?? 'standard';
        $price = Ride::calculatePrice($distance, $vehicleType);
        $duration = $this->estimateDuration($distance);

        $breakdown = $this->paymentService->estimatePaymentBreakdown($price);

        return $this->success([
            'distance_km' => round($distance, 2),
            'duration_minutes' => $duration,
            'price' => $price,
            'currency' => 'XAF',
            'vehicle_type' => $vehicleType,
            'breakdown' => $breakdown,
            'prices_by_type' => [
                'standard' => Ride::calculatePrice($distance, 'standard'),
                'comfort' => Ride::calculatePrice($distance, 'comfort'),
                'premium' => Ride::calculatePrice($distance, 'premium'),
            ],
        ]);
    }

    /**
     * Create a new ride request
     */
    public function create(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'pickup_address' => 'required|string|max:500',
            'pickup_latitude' => 'required|numeric|between:-90,90',
            'pickup_longitude' => 'required|numeric|between:-180,180',
            'dropoff_address' => 'required|string|max:500',
            'dropoff_latitude' => 'required|numeric|between:-90,90',
            'dropoff_longitude' => 'required|numeric|between:-180,180',
            'vehicle_type' => 'sometimes|in:standard,comfort,premium',
            'seats_requested' => 'sometimes|integer|min:1|max:6',
            'scheduled_at' => 'sometimes|date|after:now',
            'notes' => 'sometimes|string|max:500',
            'payment_method' => 'sometimes|in:peex,mtn_momo,airtel_money,stripe',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();

        // Check for active ride
        $activeRide = Ride::where('passenger_id', $user->id)
            ->active()
            ->first();

        if ($activeRide) {
            return $this->error('Vous avez déjà un trajet en cours', 400, [
                'active_ride_id' => $activeRide->id,
            ]);
        }

        $distance = $this->calculateDistance(
            $request->pickup_latitude,
            $request->pickup_longitude,
            $request->dropoff_latitude,
            $request->dropoff_longitude
        );

        $vehicleType = $request->vehicle_type ?? 'standard';
        $price = Ride::calculatePrice($distance, $vehicleType);
        $duration = $this->estimateDuration($distance);

        $ride = Ride::create([
            'passenger_id' => $user->id,
            'pickup_address' => $request->pickup_address,
            'pickup_latitude' => $request->pickup_latitude,
            'pickup_longitude' => $request->pickup_longitude,
            'dropoff_address' => $request->dropoff_address,
            'dropoff_latitude' => $request->dropoff_latitude,
            'dropoff_longitude' => $request->dropoff_longitude,
            'distance_km' => $distance,
            'duration_minutes' => $duration,
            'price' => $price,
            'price_per_km' => config("rides.price_per_km.{$vehicleType}", 150),
            'currency' => 'XAF',
            'status' => Ride::STATUS_PENDING,
            'payment_status' => 'pending',
            'payment_method' => $request->payment_method ?? $user->preferred_payment_method,
            'vehicle_type' => $vehicleType,
            'seats_requested' => $request->seats_requested ?? 1,
            'scheduled_at' => $request->scheduled_at,
            'notes' => $request->notes,
        ]);

        // Notify nearby drivers
        $this->notifyNearbyDrivers($ride);

        // Broadcast event
        event(new RideCreated($ride));

        return $this->success([
            'ride' => $this->formatRide($ride),
        ], 'Demande de trajet créée', 201);
    }

    /**
     * Get user's rides
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Ride::where('passenger_id', $user->id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $rides = $query->with(['driver:id,first_name,last_name,phone,avatar'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($rides);
    }

    /**
     * Get active ride
     */
    public function getActiveRide(): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::where('passenger_id', $user->id)
            ->active()
            ->with(['driver.driverProfile'])
            ->first();

        if (!$ride) {
            return $this->success(null, 'Aucun trajet en cours');
        }

        return $this->success($this->formatRide($ride, true));
    }

    /**
     * Get ride details
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::with(['passenger', 'driver.driverProfile', 'transactions', 'rating'])
            ->findOrFail($id);

        // Check access
        if ($ride->passenger_id !== $user->id && $ride->driver_id !== $user->id && !$user->isAdmin()) {
            return $this->error('Non autorisé', 403);
        }

        return $this->success($this->formatRide($ride, true));
    }

    /**
     * Cancel a ride
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::findOrFail($id);

        if ($ride->passenger_id !== $user->id) {
            return $this->error('Non autorisé', 403);
        }

        if (!$ride->canBeCancelled()) {
            return $this->error('Ce trajet ne peut plus être annulé', 400);
        }

        $validator = Validator::make($request->all(), [
            'reason' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        DB::beginTransaction();

        try {
            $ride->cancel($user->id, $request->reason);

            // Refund if payment was made
            if ($ride->payment_status === 'escrowed') {
                $this->paymentService->refundRidePayment($ride, null, 'cancelled_by_passenger');
            }

            DB::commit();

            event(new RideCancelled($ride, $user));

            return $this->success($this->formatRide($ride->fresh()), 'Trajet annulé');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Erreur lors de l\'annulation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Rate a completed ride
     */
    public function rate(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::findOrFail($id);

        if ($ride->passenger_id !== $user->id) {
            return $this->error('Non autorisé', 403);
        }

        if (!$ride->isCompleted()) {
            return $this->error('Vous ne pouvez noter qu\'un trajet terminé', 400);
        }

        // Check if already rated
        $existingRating = Rating::where('ride_id', $ride->id)
            ->where('rater_user_id', $user->id)
            ->first();

        if ($existingRating) {
            return $this->error('Vous avez déjà noté ce trajet', 400);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $rating = Rating::create([
            'ride_id' => $ride->id,
            'rater_user_id' => $user->id,
            'rated_user_id' => $ride->driver_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'type' => Rating::TYPE_PASSENGER_TO_DRIVER,
        ]);

        // Update driver's average rating
        $driverProfile = DriverProfile::where('user_id', $ride->driver_id)->first();
        if ($driverProfile) {
            $driverProfile->updateRating($request->rating);
        }

        return $this->success($rating, 'Merci pour votre évaluation');
    }

    /**
     * Search for available drivers nearby
     */
    public function searchDrivers(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'radius_km' => 'sometimes|numeric|min:1|max:50',
            'vehicle_type' => 'sometimes|in:standard,comfort,premium',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $radius = $request->radius_km ?? 10;

        $query = DriverProfile::query()
            ->with('user:id,first_name,last_name,avatar')
            ->available()
            ->kycApproved()
            ->nearby($request->latitude, $request->longitude, $radius);

        if ($request->vehicle_type) {
            $query->where('vehicle_type', $request->vehicle_type);
        }

        $drivers = $query->limit(20)->get();

        $formattedDrivers = $drivers->map(function ($profile) {
            return [
                'id' => $profile->user_id,
                'name' => $profile->user->full_name,
                'avatar' => $profile->user->avatar,
                'vehicle' => [
                    'brand' => $profile->vehicle_brand,
                    'model' => $profile->vehicle_model,
                    'color' => $profile->vehicle_color,
                    'plate' => $profile->vehicle_plate_number,
                    'type' => $profile->vehicle_type,
                ],
                'rating' => $profile->rating_average,
                'total_rides' => $profile->total_rides,
                'distance_km' => round($profile->distance, 2),
                'location' => [
                    'latitude' => $profile->current_latitude,
                    'longitude' => $profile->current_longitude,
                ],
            ];
        });

        return $this->success([
            'drivers' => $formattedDrivers,
            'count' => $formattedDrivers->count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Methods
    |--------------------------------------------------------------------------
    */

    /**
     * List all rides (admin)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = Ride::with(['passenger:id,first_name,last_name', 'driver:id,first_name,last_name']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $rides = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($rides);
    }

    /**
     * Get ride details (admin)
     */
    public function adminShow(int $id): JsonResponse
    {
        $ride = Ride::with(['passenger', 'driver.driverProfile', 'transactions', 'rating'])
            ->findOrFail($id);

        return $this->success($ride);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Calculate distance between two coordinates (Haversine formula)
     */
    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Estimate duration based on distance
     */
    protected function estimateDuration(float $distanceKm): int
    {
        // Average speed: 30 km/h in city
        $averageSpeed = 30;
        $minutes = ($distanceKm / $averageSpeed) * 60;

        return max((int) ceil($minutes), 5); // Minimum 5 minutes
    }

    /**
     * Notify nearby drivers about new ride
     */
    protected function notifyNearbyDrivers(Ride $ride): void
    {
        $drivers = DriverProfile::query()
            ->with('user')
            ->available()
            ->kycApproved()
            ->where('vehicle_type', $ride->vehicle_type)
            ->nearby($ride->pickup_latitude, $ride->pickup_longitude, 10)
            ->limit(10)
            ->get();

        foreach ($drivers as $driverProfile) {
            try {
                $driverProfile->user->notify(new NewRideRequest($ride));
            } catch (\Exception $e) {
                \Log::error('Failed to notify driver: ' . $e->getMessage());
            }
        }
    }

    /**
     * Format ride for response
     */
    protected function formatRide(Ride $ride, bool $detailed = false): array
    {
        $data = [
            'id' => $ride->id,
            'pickup' => [
                'address' => $ride->pickup_address,
                'latitude' => $ride->pickup_latitude,
                'longitude' => $ride->pickup_longitude,
            ],
            'dropoff' => [
                'address' => $ride->dropoff_address,
                'latitude' => $ride->dropoff_latitude,
                'longitude' => $ride->dropoff_longitude,
            ],
            'distance_km' => $ride->distance_km,
            'duration_minutes' => $ride->duration_minutes,
            'price' => $ride->price,
            'currency' => $ride->currency,
            'status' => $ride->status,
            'payment_status' => $ride->payment_status,
            'payment_method' => $ride->payment_method,
            'vehicle_type' => $ride->vehicle_type,
            'created_at' => $ride->created_at,
        ];

        if ($ride->driver) {
            $data['driver'] = [
                'id' => $ride->driver->id,
                'name' => $ride->driver->full_name,
                'phone' => $ride->driver->phone,
                'avatar' => $ride->driver->avatar,
                'rating' => $ride->driver->driverProfile?->rating_average,
            ];

            if ($ride->driver->driverProfile) {
                $data['driver']['vehicle'] = [
                    'brand' => $ride->driver->driverProfile->vehicle_brand,
                    'model' => $ride->driver->driverProfile->vehicle_model,
                    'color' => $ride->driver->driverProfile->vehicle_color,
                    'plate' => $ride->driver->driverProfile->vehicle_plate_number,
                ];

                if ($ride->isActive()) {
                    $data['driver']['location'] = [
                        'latitude' => $ride->driver->driverProfile->current_latitude,
                        'longitude' => $ride->driver->driverProfile->current_longitude,
                    ];
                }
            }
        }

        if ($detailed) {
            $data['passenger'] = $ride->passenger ? [
                'id' => $ride->passenger->id,
                'name' => $ride->passenger->full_name,
                'phone' => $ride->passenger->phone,
            ] : null;

            $data['timestamps'] = [
                'scheduled_at' => $ride->scheduled_at,
                'accepted_at' => $ride->accepted_at,
                'started_at' => $ride->started_at,
                'completed_at' => $ride->completed_at,
                'cancelled_at' => $ride->cancelled_at,
            ];

            $data['notes'] = $ride->notes;
            $data['cancellation_reason'] = $ride->cancellation_reason;

            if ($ride->rating) {
                $data['rating'] = [
                    'value' => $ride->rating->rating,
                    'comment' => $ride->rating->comment,
                ];
            }
        }

        return $data;
    }
}
