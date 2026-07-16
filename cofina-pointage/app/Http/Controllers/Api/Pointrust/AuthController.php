<?php

namespace App\Http\Controllers\Api\Pointrust;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Pointrust\PointrustJwtService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request, PointrustJwtService $jwt): JsonResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:120',
        ]);

        $user = User::query()->where('email', $validated['email'])->first();
        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Compte désactivé'], 403);
        }

        if (config('pointrust.login_requires_otp')) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $cacheKey = 'pointrust_login_otp:'.mb_strtolower($validated['email']);
            Cache::put($cacheKey, [
                'hash' => Hash::make($code),
                'attempts' => 0,
            ], now()->addSeconds((int) config('pointrust.otp_ttl_seconds')));

            try {
                Mail::raw(
                    "Votre code de connexion PointRust : {$code}\n\nCe code expire dans 5 minutes.",
                    function ($message) use ($user) {
                        $message->to($user->email)->subject('Code de connexion PointRust');
                    }
                );
            } catch (\Throwable) {
                // L’OTP est quand même en cache ; en dev consulter les logs si SMTP indisponible.
            }

            return response()->json([
                'requires_otp' => true,
                'requiresOtp' => true,
                'message' => 'Un code OTP a été envoyé à votre email',
            ]);
        }

        $access = $jwt->issueAccessToken($user);
        $refresh = $jwt->issueRefreshToken($user);

        return response()->json([
            'token' => $access,
            'access_token' => $access,
            'accessToken' => $access,
            'refresh_token' => $refresh,
            'refreshToken' => $refresh,
            'requires_otp' => false,
            'requiresOtp' => false,
            'message' => 'Connexion réussie',
            'user' => $this->userPayload($user),
        ]);
    }

    public function verifyOtp(Request $request, PointrustJwtService $jwt): JsonResponse
    {
        $validated = $request->validate([
            'identifier' => 'required|email',
            'code' => 'required|string|size:6',
        ]);

        $cacheKey = 'pointrust_login_otp:'.mb_strtolower($validated['identifier']);
        $row = Cache::get($cacheKey);
        if (! is_array($row) || empty($row['hash'])) {
            return response()->json(['message' => 'Code OTP invalide ou expiré'], 422);
        }

        if (! Hash::check($validated['code'], $row['hash'])) {
            return response()->json(['message' => 'Code OTP invalide ou expiré'], 422);
        }

        Cache::forget($cacheKey);

        $user = User::query()->where('email', $validated['identifier'])->first();
        if (! $user || ! $user->is_active) {
            return response()->json(['message' => 'Utilisateur introuvable'], 422);
        }

        $access = $jwt->issueAccessToken($user);
        $refresh = $jwt->issueRefreshToken($user);

        return response()->json([
            'access_token' => $access,
            'accessToken' => $access,
            'token' => $access,
            'refresh_token' => $refresh,
            'refreshToken' => $refresh,
            'requires_device_registration' => true,
            'requiresDeviceRegistration' => true,
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user): array
    {
        $user->profilCollaborateurAssocie();
        $p = $user->profil;

        return [
            'id' => (string) $user->id,
            'full_name' => $user->name,
            'fullName' => $user->name,
            'name' => $user->name,
            'email' => $user->email,
            'matricule' => $p?->matricule ?? 'EMP-'.str_pad((string) $user->id, 3, '0', STR_PAD_LEFT),
            'avatar_url' => null,
            'avatarUrl' => null,
        ];
    }
}
