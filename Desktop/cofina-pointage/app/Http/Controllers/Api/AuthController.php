<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\VerifyOtpRequest;
use App\Models\Otp;
use App\Models\User;
use App\Services\PointageOtpService;
use App\Support\MobileApiUserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private readonly PointageOtpService $otpService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = mb_strtolower(trim($validated['email']));

        $user = User::query()
            ->whereRaw('LOWER(TRIM(email)) = ?', [$email])
            ->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Identifiants incorrects'], 401);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Compte désactivé'], 403);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::query()->where('identifier', $email)->whereNull('used_at')->delete();

        Otp::query()->create([
            'identifier' => $email,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(10),
        ]);

        $mailResult = $this->otpService->sendOtpEmailToAddress((string) $user->email, $code, 'POINTRUST');
        if (! $mailResult['ok']) {
            Log::warning('POINTRUST API login : OTP non envoyé par e-mail', [
                'user_id' => $user->id,
                'email' => $user->email,
                'message' => $mailResult['message'] ?? null,
            ]);
        }

        $user->tokens()->delete();
        $plainToken = $user->createToken($validated['device_name'], ['otp-pending'])->plainTextToken;

        $response = [
            'token' => $plainToken,
            'requires_otp' => true,
            'requiresOtp' => true,
            'message' => $mailResult['ok']
                ? ($mailResult['via_log_fallback']
                    ? 'Code OTP enregistré (e-mail journalisé dans storage/logs — configurez SMTP ou Mailtrap).'
                    : 'Code OTP envoyé')
                : 'Code OTP généré (échec envoi e-mail — voir les logs serveur ou debug_otp en local).',
        ];

        if ($mailResult['via_log_fallback'] ?? false) {
            $response['otp_delivery'] = 'log';
        }

        if (config('pointrust.debug_otp_in_login_response')) {
            $response['debug_otp'] = $code;
            Log::info('POINTRUST API login — code OTP (dev uniquement)', [
                'email' => $email,
                'debug_otp' => $code,
            ]);
        }

        return response()->json($response);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $rawIdentifier = trim($validated['identifier']);
        $identifierKey = str_contains($rawIdentifier, '@')
            ? mb_strtolower($rawIdentifier)
            : $rawIdentifier;

        $otp = Otp::query()
            ->where('identifier', $identifierKey)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('id')
            ->first();

        if ($otp === null || ! $otp->matchesPlainCode($validated['code'])) {
            return response()->json(['message' => 'Code invalide ou expiré'], 422);
        }

        $otp->forceFill(['used_at' => now()])->save();

        $user = str_contains($identifierKey, '@')
            ? User::query()->whereRaw('LOWER(TRIM(email)) = ?', [$identifierKey])->first()
            : User::query()->where('matricule', $identifierKey)->first();

        if (! $user || ! $user->is_active) {
            return response()->json(['message' => 'Code invalide ou expiré'], 422);
        }

        $user->tokens()->delete();
        $plain = $user->createToken('mobile', ['*'])->plainTextToken;

        return response()->json([
            'access_token' => $plain,
            'token_type' => 'Bearer',
            'requires_device_registration' => true,
            'requiresDeviceRegistration' => true,
            'user' => MobileApiUserResource::toArray($user, $request),
        ]);
    }
}
