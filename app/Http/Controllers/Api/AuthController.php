<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\OtpNotification;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'role' => 'sometimes|in:passenger,driver',
            'country_code' => 'sometimes|string|max:5',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $this->formatPhone($request->phone),
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'passenger',
            'country_code' => $request->country_code ?? 'CG',
        ]);

        // Create wallet for drivers
        if ($user->isDriver()) {
            Wallet::create([
                'user_id' => $user->id,
                'balance' => 0,
                'currency' => 'XAF',
            ]);
        }

        // Generate and send OTP
        $otp = $this->generateOtp($user->phone);
        $this->sendOtp($user, $otp);

        return $this->success([
            'user' => $this->formatUser($user),
            'requires_verification' => true,
        ], 'Inscription réussie. Veuillez vérifier votre numéro de téléphone.', 201);
    }

    /**
     * Login user
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $phone = $this->formatPhone($request->phone);
        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return $this->error('Identifiants incorrects', 401);
        }

        if (!$user->is_active) {
            return $this->error('Votre compte a été désactivé', 403);
        }

        // Check if phone is verified
        if (!$user->is_phone_verified) {
            $otp = $this->generateOtp($user->phone);
            $this->sendOtp($user, $otp);

            return $this->error('Veuillez vérifier votre numéro de téléphone', 403, [
                'requires_verification' => true,
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Connexion réussie');
    }

    /**
     * Verify phone with OTP
     */
    public function verifyPhone(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $phone = $this->formatPhone($request->phone);
        $cacheKey = 'otp_' . $phone;
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp !== $request->otp) {
            return $this->error('Code OTP invalide ou expiré', 400);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return $this->error('Utilisateur non trouvé', 404);
        }

        $user->update([
            'is_phone_verified' => true,
            'phone_verified_at' => now(),
        ]);

        Cache::forget($cacheKey);

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => $this->formatUser($user),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Numéro vérifié avec succès');
    }

    /**
     * Resend OTP
     */
    public function resendOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $phone = $this->formatPhone($request->phone);
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return $this->error('Utilisateur non trouvé', 404);
        }

        // Rate limiting
        $rateLimitKey = 'otp_rate_' . $phone;
        if (Cache::has($rateLimitKey)) {
            return $this->error('Veuillez attendre avant de demander un nouveau code', 429);
        }

        $otp = $this->generateOtp($phone);
        $this->sendOtp($user, $otp);

        Cache::put($rateLimitKey, true, 60); // 1 minute cooldown

        return $this->success(null, 'Code OTP envoyé');
    }

    /**
     * Forgot password - send reset OTP
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $phone = $this->formatPhone($request->phone);
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            // Don't reveal if user exists
            return $this->success(null, 'Si ce numéro existe, un code de réinitialisation a été envoyé');
        }

        $otp = $this->generateOtp($phone, 'reset_');
        $this->sendOtp($user, $otp, 'reset');

        return $this->success(null, 'Code de réinitialisation envoyé');
    }

    /**
     * Reset password with OTP
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $phone = $this->formatPhone($request->phone);
        $cacheKey = 'reset_otp_' . $phone;
        $storedOtp = Cache::get($cacheKey);

        if (!$storedOtp || $storedOtp !== $request->otp) {
            return $this->error('Code invalide ou expiré', 400);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return $this->error('Utilisateur non trouvé', 404);
        }

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revoke all tokens
        $user->tokens()->delete();

        Cache::forget($cacheKey);

        return $this->success(null, 'Mot de passe réinitialisé avec succès');
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Déconnexion réussie');
    }

    /**
     * Refresh token
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $user = $request->user();

        // Delete current token
        $user->currentAccessToken()->delete();

        // Create new token
        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Token rafraîchi');
    }

    /**
     * Generate OTP and store in cache
     */
    protected function generateOtp(string $phone, string $prefix = ''): string
    {
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $cacheKey = $prefix . 'otp_' . $phone;

        Cache::put($cacheKey, $otp, 600); // 10 minutes

        return $otp;
    }

    /**
     * Send OTP to user
     */
    protected function sendOtp(User $user, string $otp, string $type = 'verification'): void
    {
        // In production, send via SMS using Peex or other provider
        // For now, we'll use notifications
        try {
            $user->notify(new OtpNotification($otp, $type));
        } catch (\Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to send OTP: ' . $e->getMessage());
        }
    }

    /**
     * Format phone number
     */
    protected function formatPhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);

        if (strlen($phone) === 9) {
            $phone = '242' . $phone;
        }

        return $phone;
    }

    /**
     * Format user for response
     */
    protected function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'avatar' => $user->avatar,
            'is_phone_verified' => $user->is_phone_verified,
            'country_code' => $user->country_code,
            'created_at' => $user->created_at,
        ];
    }
}
