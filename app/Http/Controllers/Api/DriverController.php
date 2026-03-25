<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Ride;
use App\Models\Rating;
use App\Models\DriverProfile;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\Payment\PaymentService;
use App\Events\RideAccepted;
use App\Events\RideStarted;
use App\Events\RideCompleted;
use App\Notifications\RideStatusUpdate;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class DriverController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    /**
     * Get driver profile
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return $this->error('Profil chauffeur non trouvé', 404);
        }

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
            ],
            'profile' => [
                'license_number' => $profile->license_number,
                'license_expiry' => $profile->license_expiry,
                'vehicle_brand' => $profile->vehicle_brand,
                'vehicle_model' => $profile->vehicle_model,
                'vehicle_year' => $profile->vehicle_year,
                'vehicle_color' => $profile->vehicle_color,
                'vehicle_plate_number' => $profile->vehicle_plate_number,
                'vehicle_type' => $profile->vehicle_type,
                'seats_available' => $profile->seats_available,
                'kyc_status' => $profile->kyc_status,
                'kyc_rejected_reason' => $profile->kyc_rejected_reason,
                'is_online' => $profile->is_online,
                'is_available' => $profile->is_available,
            ],
            'stats' => [
                'total_rides' => $profile->total_rides,
                'total_earnings' => $profile->total_earnings,
                'rating_average' => $profile->rating_average,
                'rating_count' => $profile->rating_count,
            ],
            'wallet' => [
                'balance' => $user->wallet?->balance ?? 0,
                'pending_balance' => $user->wallet?->pending_balance ?? 0,
                'currency' => 'XAF',
            ],
        ]);
    }

    /**
     * Submit KYC documents
     */
    public function submitKyc(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'license_number' => 'required|string|max:50',
            'license_expiry' => 'required|date|after:today',
            'license_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'id_card_number' => 'required|string|max:50',
            'id_card_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'vehicle_brand' => 'required|string|max:50',
            'vehicle_model' => 'required|string|max:50',
            'vehicle_year' => 'required|integer|min:2000|max:' . (date('Y') + 1),
            'vehicle_color' => 'required|string|max:30',
            'vehicle_plate_number' => 'required|string|max:20',
            'vehicle_registration_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'vehicle_insurance_image' => 'sometimes|image|mimes:jpeg,png,jpg|max:5120',
            'vehicle_type' => 'sometimes|in:standard,comfort,premium',
            'seats_available' => 'sometimes|integer|min:1|max:8',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();

        // Upload images
        $licenseImage = $request->file('license_image')->store('kyc/licenses', 'public');
        $idCardImage = $request->file('id_card_image')->store('kyc/id_cards', 'public');
        $registrationImage = $request->file('vehicle_registration_image')->store('kyc/registrations', 'public');
        $insuranceImage = $request->hasFile('vehicle_insurance_image')
            ? $request->file('vehicle_insurance_image')->store('kyc/insurance', 'public')
            : null;

        $profileData = [
            'user_id' => $user->id,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'license_image' => $licenseImage,
            'id_card_number' => $request->id_card_number,
            'id_card_image' => $idCardImage,
            'vehicle_brand' => $request->vehicle_brand,
            'vehicle_model' => $request->vehicle_model,
            'vehicle_year' => $request->vehicle_year,
            'vehicle_color' => $request->vehicle_color,
            'vehicle_plate_number' => $request->vehicle_plate_number,
            'vehicle_registration_image' => $registrationImage,
            'vehicle_insurance_image' => $insuranceImage,
            'vehicle_type' => $request->vehicle_type ?? 'standard',
            'seats_available' => $request->seats_available ?? 4,
            'kyc_status' => DriverProfile::KYC_PENDING,
        ];

        $profile = DriverProfile::updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        // Create wallet if not exists
        Wallet::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'currency' => 'XAF']
        );

        return $this->success([
            'kyc_status' => $profile->kyc_status,
            'message' => 'Documents soumis. Vérification en cours.',
        ], 'KYC soumis avec succès', 201);
    }

    /**
     * Get KYC status
     */
    public function kycStatus(): JsonResponse
    {
        $profile = Auth::user()->driverProfile;

        if (!$profile) {
            return $this->success([
                'status' => 'not_submitted',
                'message' => 'Veuillez soumettre vos documents KYC',
            ]);
        }

        return $this->success([
            'status' => $profile->kyc_status,
            'verified_at' => $profile->kyc_verified_at,
            'rejected_reason' => $profile->kyc_rejected_reason,
        ]);
    }

    /**
     * Go online
     */
    public function goOnline(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return $this->error('Profil chauffeur non trouvé', 404);
        }

        if (!$profile->isKycApproved()) {
            return $this->error('Votre KYC n\'est pas encore approuvé', 403);
        }

        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $profile->update([
            'is_online' => true,
            'is_available' => true,
            'current_latitude' => $request->latitude,
            'current_longitude' => $request->longitude,
            'last_location_update' => now(),
        ]);

        return $this->success([
            'is_online' => true,
            'is_available' => true,
        ], 'Vous êtes maintenant en ligne');
    }

    /**
     * Go offline
     */
    public function goOffline(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return $this->error('Profil chauffeur non trouvé', 404);
        }

        // Check for active ride
        $activeRide = Ride::where('driver_id', $user->id)->active()->first();
        if ($activeRide) {
            return $this->error('Vous avez un trajet en cours', 400);
        }

        $profile->goOffline();

        return $this->success([
            'is_online' => false,
            'is_available' => false,
        ], 'Vous êtes maintenant hors ligne');
    }

    /**
     * Update location
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $profile = Auth::user()->driverProfile;

        if (!$profile) {
            return $this->error('Profil chauffeur non trouvé', 404);
        }

        $profile->updateLocation($request->latitude, $request->longitude);

        return $this->success([
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'updated_at' => now(),
        ]);
    }

    /**
     * Get available rides nearby
     */
    public function availableRides(Request $request): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->driverProfile;

        if (!$profile || !$profile->is_online) {
            return $this->error('Vous devez être en ligne pour voir les courses', 400);
        }

        $rides = Ride::query()
            ->with('passenger:id,first_name,last_name,phone,avatar')
            ->where('status', Ride::STATUS_PENDING)
            ->where('vehicle_type', $profile->vehicle_type)
            ->whereNull('driver_id')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Calculate distance to pickup for each ride
        $ridesWithDistance = $rides->map(function ($ride) use ($profile) {
            $distance = $this->calculateDistance(
                $profile->current_latitude,
                $profile->current_longitude,
                $ride->pickup_latitude,
                $ride->pickup_longitude
            );

            return [
                'id' => $ride->id,
                'pickup' => [
                    'address' => $ride->pickup_address,
                    'latitude' => $ride->pickup_latitude,
                    'longitude' => $ride->pickup_longitude,
                    'distance_km' => round($distance, 2),
                ],
                'dropoff' => [
                    'address' => $ride->dropoff_address,
                    'latitude' => $ride->dropoff_latitude,
                    'longitude' => $ride->dropoff_longitude,
                ],
                'passenger' => [
                    'name' => $ride->passenger->full_name,
                    'avatar' => $ride->passenger->avatar,
                ],
                'price' => $ride->price,
                'distance_km' => $ride->distance_km,
                'duration_minutes' => $ride->duration_minutes,
                'created_at' => $ride->created_at,
            ];
        })->sortBy('pickup.distance_km')->values();

        return $this->success([
            'rides' => $ridesWithDistance,
            'count' => $ridesWithDistance->count(),
        ]);
    }

    /**
     * Accept a ride
     */
    public function acceptRide(int $id): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->driverProfile;

        if (!$profile || !$profile->canAcceptRides()) {
            return $this->error('Vous ne pouvez pas accepter de courses actuellement', 400);
        }

        $ride = Ride::findOrFail($id);

        if (!$ride->isPending()) {
            return $this->error('Cette course n\'est plus disponible', 400);
        }

        DB::beginTransaction();

        try {
            $ride->update([
                'driver_id' => $user->id,
                'status' => Ride::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);

            $profile->setAvailable(false);

            DB::commit();

            // Notify passenger
            $ride->passenger->notify(new RideStatusUpdate($ride, 'accepted'));

            event(new RideAccepted($ride));

            return $this->success([
                'ride' => $this->formatRideForDriver($ride->fresh()),
            ], 'Course acceptée');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Erreur lors de l\'acceptation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Notify arrival at pickup
     */
    public function arriveAtPickup(int $id): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::where('id', $id)
            ->where('driver_id', $user->id)
            ->where('status', Ride::STATUS_ACCEPTED)
            ->firstOrFail();

        $ride->update([
            'status' => Ride::STATUS_DRIVER_ARRIVING,
        ]);

        $ride->passenger->notify(new RideStatusUpdate($ride, 'driver_arriving'));

        return $this->success([
            'ride' => $this->formatRideForDriver($ride),
        ], 'Le passager a été notifié de votre arrivée');
    }

    /**
     * Start the ride
     */
    public function startRide(int $id): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::where('id', $id)
            ->where('driver_id', $user->id)
            ->where('status', Ride::STATUS_DRIVER_ARRIVING)
            ->firstOrFail();

        // Check payment status
        if ($ride->payment_status !== 'escrowed' && $ride->payment_status !== 'completed') {
            return $this->error('Le paiement n\'a pas encore été effectué', 400);
        }

        $ride->update([
            'status' => Ride::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        $ride->passenger->notify(new RideStatusUpdate($ride, 'in_progress'));

        event(new RideStarted($ride));

        return $this->success([
            'ride' => $this->formatRideForDriver($ride),
        ], 'Course démarrée');
    }

    /**
     * Complete the ride
     */
    public function completeRide(int $id): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::where('id', $id)
            ->where('driver_id', $user->id)
            ->where('status', Ride::STATUS_IN_PROGRESS)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            $ride->update([
                'status' => Ride::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);

            // Release payment to driver
            $paymentResult = $this->paymentService->completeRidePayment($ride);

            // Update driver profile
            $profile = $user->driverProfile;
            $profile->incrementStats($paymentResult['driver_credited'] ?? 0);
            $profile->setAvailable(true);

            DB::commit();

            $ride->passenger->notify(new RideStatusUpdate($ride, 'completed'));

            event(new RideCompleted($ride));

            return $this->success([
                'ride' => $this->formatRideForDriver($ride->fresh()),
                'earnings' => $paymentResult['driver_credited'] ?? 0,
                'wallet_balance' => $paymentResult['wallet_balance'] ?? 0,
            ], 'Course terminée');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Erreur lors de la finalisation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Cancel a ride (as driver)
     */
    public function cancelRide(Request $request, int $id): JsonResponse
    {
        $user = Auth::user();

        $ride = Ride::where('id', $id)
            ->where('driver_id', $user->id)
            ->whereIn('status', [Ride::STATUS_ACCEPTED, Ride::STATUS_DRIVER_ARRIVING])
            ->firstOrFail();

        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        DB::beginTransaction();

        try {
            $ride->update([
                'status' => Ride::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
                'cancellation_reason' => $request->reason,
                'driver_id' => null, // Allow another driver to accept
            ]);

            // Reset ride to pending so another driver can accept
            $ride->update([
                'status' => Ride::STATUS_PENDING,
            ]);

            $user->driverProfile->setAvailable(true);

            DB::commit();

            $ride->passenger->notify(new RideStatusUpdate($ride, 'driver_cancelled'));

            return $this->success(null, 'Course annulée');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Erreur lors de l\'annulation: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get ride history
     */
    public function rideHistory(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Ride::where('driver_id', $user->id)
            ->with('passenger:id,first_name,last_name,avatar');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $rides = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($rides);
    }

    /**
     * Get driver stats
     */
    public function stats(): JsonResponse
    {
        $user = Auth::user();
        $profile = $user->driverProfile;

        $today = now()->startOfDay();
        $thisWeek = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        $todayRides = Ride::where('driver_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->where('completed_at', '>=', $today)
            ->count();

        $weekRides = Ride::where('driver_id', $user->id)
            ->where('status', Ride::STATUS_COMPLETED)
            ->where('completed_at', '>=', $thisWeek)
            ->count();

        $todayEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'driver_credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $today)
            ->sum('amount');

        $weekEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'driver_credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $thisWeek)
            ->sum('amount');

        $monthEarnings = Transaction::where('user_id', $user->id)
            ->where('type', 'driver_credit')
            ->where('status', 'completed')
            ->where('created_at', '>=', $thisMonth)
            ->sum('amount');

        return $this->success([
            'today' => [
                'rides' => $todayRides,
                'earnings' => $todayEarnings,
            ],
            'this_week' => [
                'rides' => $weekRides,
                'earnings' => $weekEarnings,
            ],
            'this_month' => [
                'earnings' => $monthEarnings,
            ],
            'all_time' => [
                'total_rides' => $profile->total_rides,
                'total_earnings' => $profile->total_earnings,
                'rating' => $profile->rating_average,
                'rating_count' => $profile->rating_count,
            ],
            'wallet_balance' => $user->wallet?->balance ?? 0,
        ]);
    }

    /**
     * Get earnings history
     */
    public function earnings(Request $request): JsonResponse
    {
        $user = Auth::user();

        $query = Transaction::where('user_id', $user->id)
            ->where('type', 'driver_credit')
            ->with('ride:id,pickup_address,dropoff_address');

        if ($request->from_date) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        $transactions = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($transactions);
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Methods
    |--------------------------------------------------------------------------
    */

    /**
     * List all drivers (admin)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = User::where('role', 'driver')
            ->with('driverProfile', 'wallet');

        if ($request->kyc_status) {
            $query->whereHas('driverProfile', function ($q) use ($request) {
                $q->where('kyc_status', $request->kyc_status);
            });
        }

        if ($request->is_online !== null) {
            $query->whereHas('driverProfile', function ($q) use ($request) {
                $q->where('is_online', $request->boolean('is_online'));
            });
        }

        $drivers = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($drivers);
    }

    /**
     * Get drivers with pending KYC (admin)
     */
    public function pendingKyc(): JsonResponse
    {
        $drivers = User::where('role', 'driver')
            ->whereHas('driverProfile', function ($q) {
                $q->where('kyc_status', DriverProfile::KYC_PENDING);
            })
            ->with('driverProfile')
            ->orderBy('created_at', 'asc')
            ->get();

        return $this->success($drivers);
    }

    /**
     * Approve KYC (admin)
     */
    public function approveKyc(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $profile = $user->driverProfile;

        if (!$profile) {
            return $this->error('Profil chauffeur non trouvé', 404);
        }

        $profile->update([
            'kyc_status' => DriverProfile::KYC_APPROVED,
            'kyc_verified_at' => now(),
            'kyc_rejected_reason' => null,
        ]);

        // Notify driver
        // $user->notify(new KycApproved());

        return $this->success($profile, 'KYC approuvé');
    }

    /**
     * Reject KYC (admin)
     */
    public function rejectKyc(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::findOrFail($id);
        $profile = $user->driverProfile;

        if (!$profile) {
            return $this->error('Profil chauffeur non trouvé', 404);
        }

        $profile->update([
            'kyc_status' => DriverProfile::KYC_REJECTED,
            'kyc_rejected_reason' => $request->reason,
        ]);

        // Notify driver
        // $user->notify(new KycRejected($request->reason));

        return $this->success($profile, 'KYC rejeté');
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $latDelta = deg2rad($lat2 - $lat1);
        $lonDelta = deg2rad($lon2 - $lon1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function formatRideForDriver(Ride $ride): array
    {
        return [
            'id' => $ride->id,
            'passenger' => [
                'id' => $ride->passenger->id,
                'name' => $ride->passenger->full_name,
                'phone' => $ride->passenger->phone,
                'avatar' => $ride->passenger->avatar,
            ],
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
            'price' => $ride->price,
            'distance_km' => $ride->distance_km,
            'duration_minutes' => $ride->duration_minutes,
            'status' => $ride->status,
            'payment_status' => $ride->payment_status,
            'notes' => $ride->notes,
            'created_at' => $ride->created_at,
            'accepted_at' => $ride->accepted_at,
            'started_at' => $ride->started_at,
        ];
    }
}
