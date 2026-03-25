<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Get current user profile
     */
    public function profile(): JsonResponse
    {
        $user = Auth::user();
        $user->load(['wallet', 'driverProfile']);

        $data = [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'avatar' => $user->avatar ? Storage::url($user->avatar) : null,
            'date_of_birth' => $user->date_of_birth,
            'gender' => $user->gender,
            'country_code' => $user->country_code,
            'is_phone_verified' => $user->is_phone_verified,
            'is_email_verified' => $user->is_email_verified,
            'preferred_payment_method' => $user->preferred_payment_method,
            'rating' => $user->getAverageRating(),
            'total_rides' => $user->getTotalRides(),
            'created_at' => $user->created_at,
        ];

        if ($user->isDriver()) {
            $data['wallet'] = [
                'balance' => $user->wallet?->balance ?? 0,
                'pending_balance' => $user->wallet?->pending_balance ?? 0,
                'currency' => $user->wallet?->currency ?? 'XAF',
            ];

            $data['driver_profile'] = $user->driverProfile ? [
                'vehicle_brand' => $user->driverProfile->vehicle_brand,
                'vehicle_model' => $user->driverProfile->vehicle_model,
                'vehicle_plate' => $user->driverProfile->vehicle_plate_number,
                'vehicle_type' => $user->driverProfile->vehicle_type,
                'kyc_status' => $user->driverProfile->kyc_status,
                'is_online' => $user->driverProfile->is_online,
                'rating_average' => $user->driverProfile->rating_average,
                'total_rides' => $user->driverProfile->total_rides,
                'total_earnings' => $user->driverProfile->total_earnings,
            ] : null;
        }

        return $this->success($data);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|string|max:100',
            'last_name' => 'sometimes|string|max:100',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'date_of_birth' => 'sometimes|date|before:today',
            'gender' => 'sometimes|in:male,female,other',
            'preferred_payment_method' => 'sometimes|in:peex,mtn_momo,airtel_money,stripe',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user->update($request->only([
            'first_name',
            'last_name',
            'email',
            'date_of_birth',
            'gender',
            'preferred_payment_method',
        ]));

        return $this->success($user->fresh(), 'Profil mis à jour');
    }

    /**
     * Update avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB max
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();

        // Delete old avatar
        if ($user->avatar) {
            Storage::delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar' => $path]);

        return $this->success([
            'avatar' => Storage::url($path),
        ], 'Photo de profil mise à jour');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return $this->error('Mot de passe actuel incorrect', 400);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        return $this->success(null, 'Mot de passe mis à jour');
    }

    /**
     * Update FCM token for push notifications
     */
    public function updateFcmToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        Auth::user()->update([
            'fcm_token' => $request->fcm_token,
        ]);

        return $this->success(null, 'Token FCM mis à jour');
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = Auth::user();

        if (!Hash::check($request->password, $user->password)) {
            return $this->error('Mot de passe incorrect', 400);
        }

        // Check for active rides
        $activeRides = $user->ridesAsPassenger()->active()->count();
        if ($user->isDriver()) {
            $activeRides += $user->ridesAsDriver()->active()->count();
        }

        if ($activeRides > 0) {
            return $this->error('Vous avez des trajets en cours. Veuillez les terminer avant de supprimer votre compte.', 400);
        }

        // Soft delete
        $user->update(['is_active' => false]);
        $user->tokens()->delete();
        $user->delete();

        return $this->success(null, 'Compte supprimé');
    }

    /*
    |--------------------------------------------------------------------------
    | Admin Methods
    |--------------------------------------------------------------------------
    */

    /**
     * List all users (admin)
     */
    public function adminIndex(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->role) {
            $query->where('role', $request->role);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->is_active !== null) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return $this->paginated($users);
    }

    /**
     * Get user details (admin)
     */
    public function adminShow(int $id): JsonResponse
    {
        $user = User::with(['wallet', 'driverProfile', 'transactions'])
            ->findOrFail($id);

        return $this->success($user);
    }

    /**
     * Update user status (admin)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::findOrFail($id);

        $user->update([
            'is_active' => $request->is_active,
        ]);

        if (!$request->is_active) {
            $user->tokens()->delete();
        }

        return $this->success($user, $request->is_active ? 'Utilisateur activé' : 'Utilisateur désactivé');
    }
}
