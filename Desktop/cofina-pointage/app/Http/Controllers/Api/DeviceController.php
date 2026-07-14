<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterDeviceRequest;
use App\Models\User;
use App\Support\MobileApiAccountResource;
use App\Support\MobileDeviceRegistration;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;

class DeviceController extends Controller
{
    public function store(RegisterDeviceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->optionalUserFromBearer($request->bearerToken());
        if ($user instanceof User) {
            MobileDeviceRegistration::register($user, $validated);
            $user->profilCollaborateurAssocie();

            return response()->json([
                'message' => 'Appareil enregistré',
                'user' => MobileApiAccountResource::toArray($user, $request),
            ], 200);
        }

        return response()->json(['message' => 'Appareil enregistré'], 200);
    }

    private function optionalUserFromBearer(?string $bearer): ?User
    {
        if ($bearer === null || $bearer === '') {
            return null;
        }

        $pat = PersonalAccessToken::findToken($bearer);
        $model = $pat?->tokenable;

        return $model instanceof User ? $model : null;
    }
}
